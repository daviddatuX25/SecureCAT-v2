<?php

namespace App\Http\Controllers\Release;

use App\Http\Controllers\Controller;
use App\Http\Requests\Release\MarkPrintedRequest;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\GradingSession;
use App\Models\ResultSheetTemplate;
use App\Services\PrintBatchService;
use App\Services\ResultSheetPdfService;
use App\Services\ResultSheetTemplateService;
use App\ValueObjects\RenderResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReleasePrintController extends Controller
{
    public function __construct(
        private PrintBatchService $printService,
        private ResultSheetTemplateService $templateService,
        private ResultSheetPdfService $pdfService,
    ) {}

    public function index(GradingSession $grading_session): Response
    {
        $session = $grading_session->load(['examSession.room']);
        $scoredApplicantIds = $grading_session->applicantScores()
            ->pluck('applicant_id')
            ->flip()
            ->all();
        $applicants = $grading_session->applicants()
            ->with('application')
            ->get()
            ->map(function ($a) use ($scoredApplicantIds) {
                return [
                    'id' => $a->id,
                    'applicant_id' => $a->id,
                    'name' => $this->formatName($a),
                    'reference' => $a->application?->reference_number ?? '—',
                    'scored' => isset($scoredApplicantIds[$a->id]),
                    'printed' => (bool) $a->pivot->result_printed_at,
                ];
            });

        return Inertia::render('Release/PrintBatch', [
            'sessionId' => (string) $grading_session->id,
            'session' => [
                'id' => $grading_session->id,
                'exam_session_id' => $session->examSession?->id,
                'exam_date' => $session->examSession?->date?->format('Y-m-d'),
                'room_name' => $session->examSession?->room?->name ?? '—',
            ],
            'applicants' => $applicants->values()->all(),
        ]);
    }

    public function markPrinted(MarkPrintedRequest $request, GradingSession $grading_session): RedirectResponse
    {
        $this->printService->markPrinted(
            $grading_session,
            $request->validated('applicant_ids'),
            $request->validated('printed')
        );

        return redirect()->back()->with('success', 'Printed status updated.');
    }

    public function resultSheet(GradingSession $grading_session, Applicant $applicant): Response
    {
        $pivot = $grading_session->applicants()->where('applicants.id', $applicant->id)->first()?->pivot;

        if (! $pivot) {
            abort(404, 'Applicant is not part of this grading session.');
        }

        $template = ResultSheetTemplate::where('is_active', true)->first();
        if (! $template) {
            return Inertia::render('Release/ResultSheet', [
                'sessionId' => (string) $grading_session->id,
                'applicantId' => (string) $applicant->id,
                'printed' => false,
                'applicant' => ['id' => $applicant->id, 'name' => '—', 'reference' => '—', 'exam_date' => '—', 'room_name' => '—'],
                'scores' => [],
                'templateHtml' => null,
                'templateError' => 'No active result sheet template. Please create one in Admin > Result templates.',
                'paperSize' => 'a4',
                'orientation' => 'portrait',
                'logicalUnit' => 'full',
            ]);
        }

        $session = $grading_session->load(['examSession.room']);
        $applicant->load('application');
        $scores = $grading_session->applicantScores()
            ->where('applicant_id', $applicant->id)
            ->with('aptitudeArea')
            ->get();

        $applicantData = $this->buildApplicantData($applicant, $session, $scores);
        $result = $this->templateService->render($template, [$applicantData], false);

        return Inertia::render('Release/ResultSheet', [
            'sessionId' => (string) $grading_session->id,
            'applicantId' => (string) $applicant->id,
            'printed' => (bool) ($pivot->result_printed_at ?? false),
            'applicant' => [
                'id' => $applicantData['id'],
                'name' => $applicantData['name'],
                'reference' => $applicantData['reference'],
                'exam_date' => $applicantData['exam_date'],
                'room_name' => $applicantData['room_name'],
            ],
            'scores' => $applicantData['scores'],
            'templateHtml' => $result->html,
            'templateError' => null,
            'paperSize' => $result->paperSize,
            'orientation' => $result->orientation,
            'logicalUnit' => $result->logicalUnit,
        ]);
    }

    public function resultSheetPdf(GradingSession $grading_session, Applicant $applicant): SymfonyResponse
    {
        $pivot = $grading_session->applicants()->where('applicants.id', $applicant->id)->first()?->pivot;

        if (! $pivot) {
            abort(404, 'Applicant is not part of this grading session.');
        }

        $template = ResultSheetTemplate::where('is_active', true)->first();
        abort_if(! $template, 404, 'No active result sheet template.');

        $session = $grading_session->load(['examSession.room']);
        $applicant->load('application');
        $scores = $grading_session->applicantScores()
            ->where('applicant_id', $applicant->id)
            ->with('aptitudeArea')
            ->get();

        $applicantData = $this->buildApplicantData($applicant, $session, $scores);
        $result = $this->templateService->render($template, [$applicantData], false);

        $filename = str_replace(' ', '_', $applicantData['name']).'_result_sheet.pdf';

        return $this->pdfService->inline($result, $filename);
    }

    public function printBulk(GradingSession $grading_session): Response
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        if (! $template) {
            return Inertia::render('Release/ResultSheetBulk', $this->noTemplatePayload());
        }

        $grading_session->load('examSession.room');
        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
        $applicants = $grading_session->applicants()
            ->whereIn('applicants.id', $ids)
            ->with('application')
            ->get();
        $scoresByApplicant = $grading_session->applicantScores()
            ->whereIn('applicant_id', $ids)
            ->with('aptitudeArea')
            ->get()
            ->groupBy('applicant_id');

        $applicantsWithScores = $applicants->map(function ($a) use ($scoresByApplicant, $grading_session) {
            $scores = $this->mapScores($scoresByApplicant->get($a->id, collect()));

            return $this->buildApplicantData($a, $grading_session, null, $scores);
        })->values()->all();

        $sheetsHtml = $this->templateService->buildSheetsFromApplicantData($applicantsWithScores, $template);

        return Inertia::render('Release/ResultSheetBulk', [
            'sessionId' => (string) $grading_session->id,
            'applicantIds' => $ids,
            'applicants' => $applicantsWithScores,
            'sheetsHtml' => $sheetsHtml,
            'templateError' => null,
            'paperSize' => $template->paper_size ?? 'a4',
            'orientation' => $template->orientation ?? 'portrait',
            'logicalUnit' => $template->logical_unit ?? 'full',
            'paperOptions' => ['a4' => 'A4', 'letter' => 'Letter'],
        ]);
    }

    public function printBulkPdf(GradingSession $grading_session): SymfonyResponse
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        abort_if(! $template, 404, 'No active result sheet template.');

        $copies = (int) request()->query('copies', 1);
        abort_if($copies < 1 || $copies > 10, 422, 'Copies must be between 1 and 10.');

        $grading_session->load('examSession.room');
        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
        $applicants = $grading_session->applicants()
            ->whereIn('applicants.id', $ids)
            ->with('application')
            ->get();
        $scoresByApplicant = $grading_session->applicantScores()
            ->whereIn('applicant_id', $ids)
            ->with('aptitudeArea')
            ->get()
            ->groupBy('applicant_id');

        $applicantsWithScores = $applicants->map(function ($a) use ($scoresByApplicant, $grading_session) {
            $scores = $this->mapScores($scoresByApplicant->get($a->id, collect()));

            return $this->buildApplicantData($a, $grading_session, null, $scores);
        })->values()->all();

        $sheetsHtml = $this->templateService->buildSheetsFromApplicantData($applicantsWithScores, $template);

        $meta = RenderResult::fromTemplate($template);

        return $this->pdfService->bulkDownload(
            $sheetsHtml,
            $meta,
            "session_{$grading_session->id}_result_sheets.pdf",
            $copies
        );
    }

    public function printBulkAgnostic(): Response
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);

        if (! $template) {
            return Inertia::render('Release/ResultSheetBulk', $this->noTemplatePayload($ids));
        }

        $applicants = Applicant::whereIn('id', $ids)
            ->with('application', 'gradingSessions.examSession.room')
            ->get();

        $applicantSessionMap = [];
        foreach ($applicants as $applicant) {
            $gs = $applicant->gradingSessions->where('status', GradingSession::STATUS_FINALIZED)->first() ?? $applicant->gradingSessions->first();
            if ($gs) {
                $applicantSessionMap[$applicant->id] = $gs->id;
            }
        }

        $allScores = ApplicantScore::whereIn('applicant_id', array_keys($applicantSessionMap))
            ->whereIn('grading_session_id', array_unique(array_values($applicantSessionMap)))
            ->with('aptitudeArea')
            ->get()
            ->groupBy('applicant_id');

        $applicantsWithScores = $applicants->map(function ($a) use ($allScores) {
            $gs = $a->gradingSessions->where('status', GradingSession::STATUS_FINALIZED)->first() ?? $a->gradingSessions->first();
            $scores = $this->mapScores(
                $allScores->get($a->id, collect())
                    ->filter(fn ($s) => $gs && $s->grading_session_id === $gs->id)
            );

            return $this->buildApplicantData($a, $gs, null, $scores);
        })->values()->all();

        $sheetsHtml = $this->templateService->buildSheetsFromApplicantData($applicantsWithScores, $template);

        return Inertia::render('Release/ResultSheetBulk', [
            'sessionId' => null,
            'applicantIds' => $ids,
            'applicants' => $applicantsWithScores,
            'sheetsHtml' => $sheetsHtml,
            'templateError' => null,
            'paperSize' => $template->paper_size ?? 'a4',
            'orientation' => $template->orientation ?? 'portrait',
            'logicalUnit' => $template->logical_unit ?? 'full',
            'paperOptions' => ['a4' => 'A4', 'letter' => 'Letter'],
        ]);
    }

    public function printBulkAgnosticPdf(): SymfonyResponse
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        abort_if(! $template, 404, 'No active result sheet template.');

        $copies = (int) request()->query('copies', 1);
        abort_if($copies < 1 || $copies > 10, 422, 'Copies must be between 1 and 10.');

        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
        $applicants = Applicant::whereIn('id', $ids)
            ->with('application', 'gradingSessions.examSession.room')
            ->get();

        $applicantSessionMap = [];
        foreach ($applicants as $applicant) {
            $gs = $applicant->gradingSessions->where('status', GradingSession::STATUS_FINALIZED)->first() ?? $applicant->gradingSessions->first();
            if ($gs) {
                $applicantSessionMap[$applicant->id] = $gs->id;
            }
        }

        $allScores = ApplicantScore::whereIn('applicant_id', array_keys($applicantSessionMap))
            ->whereIn('grading_session_id', array_unique(array_values($applicantSessionMap)))
            ->with('aptitudeArea')
            ->get()
            ->groupBy('applicant_id');

        $applicantsWithScores = $applicants->map(function ($a) use ($allScores) {
            $gs = $a->gradingSessions->where('status', GradingSession::STATUS_FINALIZED)->first() ?? $a->gradingSessions->first();
            $scores = $this->mapScores(
                $allScores->get($a->id, collect())
                    ->filter(fn ($s) => $gs && $s->grading_session_id === $gs->id)
            );

            return $this->buildApplicantData($a, $gs, null, $scores);
        })->values()->all();

        $sheetsHtml = $this->templateService->buildSheetsFromApplicantData($applicantsWithScores, $template);

        $meta = RenderResult::fromTemplate($template);

        return $this->pdfService->bulkDownload($sheetsHtml, $meta, 'result_sheets.pdf', $copies);
    }

    public function dispatchBulkPdfJob(): JsonResponse
    {
        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
        abort_if(empty($ids), 422, 'No applicant IDs provided.');

        $copies = (int) request()->query('copies', 1);
        abort_if($copies < 1 || $copies > 10, 422, 'Copies must be between 1 and 10.');

        $sessionId = request()->query('grading_session_id');
        $gradingSessionId = $sessionId ? (int) $sessionId : null;

        $printJob = PrintJob::create([
            'user_id' => auth()->id(),
            'grading_session_id' => $gradingSessionId,
            'applicant_ids' => $ids,
            'copies' => $copies,
            'status' => 'pending',
        ]);

        GenerateBulkResultSheetPdf::dispatch($printJob->id);

        return response()->json(['jobId' => $printJob->id]);
    }

    public function printJobStatus(PrintJob $printJob): JsonResponse
    {
        abort_if($printJob->user_id !== auth()->id(), 403, 'Access denied.');

        return response()->json([
            'id' => $printJob->id,
            'status' => $printJob->status,
            'progress' => $printJob->progress,
            'errorMessage' => $printJob->error_message,
            'pdfUrl' => $printJob->isCompleted() ? route('admin.release.print.print-job-download', $printJob->id) : null,
        ]);
    }

    public function printJobDownload(PrintJob $printJob): SymfonyResponse
    {
        abort_if($printJob->user_id !== auth()->id(), 403, 'Access denied.');
        abort_if(! $printJob->isCompleted(), 404, 'PDF is not ready yet.');
        abort_if(! $printJob->pdf_path, 404, 'PDF file not found.');

        $fullPath = Storage::disk('local')->path($printJob->pdf_path);
        abort_unless(file_exists($fullPath), 404, 'PDF file missing from disk.');

        return response()->download($fullPath, "result_sheets_{$printJob->id}.pdf");
    }

    // -- Private Helpers ---------------------------------------------------

    private function formatName(Applicant $applicant): string
    {
        if (! $applicant->application) {
            return '—';
        }

        return trim(implode(' ', array_filter([
            $applicant->application->first_name,
            $applicant->application->middle_name,
            $applicant->application->last_name,
            $applicant->application->suffix,
        ])));
    }

    /**
     * Build applicant data array for template rendering.
     *
     * @param  Collection<int, array{domain: string, raw: int|float|null, max: int|float|null, pct: int}>|null  $rawScores
     * @param  array<int, array{domain: string, raw: int|float|null, max: int|float|null, pct: int}>|null  $mappedScores
     * @return array<string, mixed>
     */
    private function buildApplicantData(Applicant $applicant, ?GradingSession $session, ?Collection $rawScores = null, ?array $mappedScores = null): array
    {
        $scores = $mappedScores ?? $this->mapScores($rawScores ?? collect());
        $overallPct = count($scores) > 0
            ? (int) round(collect($scores)->avg('pct'))
            : 0;

        return [
            'id' => $applicant->id,
            'name' => $this->formatName($applicant),
            'reference' => $applicant->application?->reference_number ?? '—',
            'exam_date' => $session?->examSession?->date?->format('F j, Y') ?? '—',
            'room_name' => $session?->examSession?->room?->name ?? '—',
            'scores' => $scores,
            'overall_pct' => $overallPct,
        ];
    }

    /**
     * @param  Collection<int, ApplicantScore>  $scores
     * @return array<int, array{domain: string, raw: int|float|null, max: int|float|null, pct: int}>
     */
    private function mapScores(Collection $scores): array
    {
        return $scores->map(fn ($s) => [
            'domain' => $s->aptitudeArea?->name ?? '—',
            'raw' => $s->raw_score,
            'max' => $s->max_score,
            'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function noTemplatePayload(?array $ids = null): array
    {
        return [
            'sessionId' => null,
            'applicantIds' => $ids ?? [],
            'applicants' => [],
            'sheetsHtml' => [],
            'templateError' => 'No active result sheet template. Please create one in Admin > Result templates.',
            'paperSize' => 'a4',
            'orientation' => 'portrait',
            'logicalUnit' => 'full',
            'paperOptions' => ['a4' => 'A4', 'letter' => 'Letter'],
        ];
    }
}
