<?php

namespace App\Http\Controllers\Release;

use App\Http\Controllers\Controller;
use App\Http\Requests\Release\MarkPrintedRequest;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\GradingSession;
use App\Models\ResultSheetTemplate;
use App\Services\PrintBatchService;
use App\Services\ResultSheetTemplateService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReleasePrintController extends Controller
{
    public function __construct(
        private PrintBatchService $printService,
        private ResultSheetTemplateService $templateService
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
                    'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
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

        $applicantData = [
            'id' => $applicant->id,
            'name' => $applicant->application ? trim(implode(' ', array_filter([$applicant->application->first_name, $applicant->application->middle_name, $applicant->application->last_name, $applicant->application->suffix]))) : '—',
            'reference' => $applicant->application?->reference_number ?? '—',
            'exam_date' => $session->examSession?->date?->format('F j, Y'),
            'room_name' => $session->examSession?->room?->name ?? '—',
            'scores' => $scores->map(fn ($s) => [
                'domain' => $s->aptitudeArea?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
            ])->values()->all(),
            'overall_pct' => $scores->isEmpty() ? 0 : (int) round($scores->avg(fn ($s) => $s->max_score > 0 ? ($s->raw_score / $s->max_score) * 100 : 0)),
        ];

        $templateHtml = $this->templateService->render($template, [$applicantData], false);

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
            'templateHtml' => $templateHtml,
            'templateError' => null,
            'paperSize' => $template->paper_size ?? 'a4',
            'orientation' => $template->orientation ?? 'portrait',
            'logicalUnit' => $template->logical_unit ?? 'full',
        ]);
    }

    public function printBulk(GradingSession $grading_session): Response
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        if (! $template) {
            return Inertia::render('Release/ResultSheetBulk', [
                'sessionId' => (string) $grading_session->id,
                'applicantIds' => [],
                'applicants' => [],
                'sheetsHtml' => [],
                'templateError' => 'No active result sheet template. Please create one in Admin > Result templates.',
                'paperSize' => 'a4',
                'orientation' => 'portrait',
                'logicalUnit' => 'full',
                'paperOptions' => ['a4' => 'A4', 'letter' => 'Letter'],
            ]);
        }

        $grading_session->load('examSession.room');
        $ids = array_filter(array_map('intval', explode(',', request()->query('ids', ''))));
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
            $scores = $scoresByApplicant->get($a->id, collect())->map(fn ($s) => [
                'domain' => $s->aptitudeArea?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
            ])->values()->all();
            $overallPct = count($scores) > 0 ? (int) round(collect($scores)->avg('pct')) : 0;

            return [
                'id' => $a->id,
                'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                'reference' => $a->application?->reference_number ?? '—',
                'exam_date' => $grading_session->examSession?->date?->format('F j, Y'),
                'room_name' => $grading_session->examSession?->room?->name ?? '—',
                'scores' => $scores,
                'overall_pct' => $overallPct,
            ];
        })->values()->all();

        $logicalUnit = $template->logical_unit ?? 'full';
        $chunkSize = in_array($logicalUnit, ['half_a4', 'half_legal', 'half_letter'], true) ? 2 : 1;

        $sheetsHtml = [];
        foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
            if (count($chunk) === 2) {
                $html1 = $this->templateService->render($template, [$chunk[0]], false);
                $html2 = $this->templateService->render($template, [$chunk[1]], false);
                $sheetsHtml[] = $html1.$html2;
            } else {
                $sheetsHtml[] = $this->templateService->render($template, $chunk, false);
            }
        }

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

    public function printBulkAgnostic(): Response
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        $ids = array_filter(array_map('intval', explode(',', request()->query('ids', ''))));

        if (! $template) {
            return Inertia::render('Release/ResultSheetBulk', [
                'sessionId' => null,
                'applicantIds' => $ids,
                'applicants' => [],
                'sheetsHtml' => [],
                'templateError' => 'No active result sheet template. Please create one in Admin > Result templates.',
                'paperSize' => 'a4',
                'orientation' => 'portrait',
                'logicalUnit' => 'full',
                'paperOptions' => ['a4' => 'A4', 'letter' => 'Letter'],
            ]);
        }

        $applicants = Applicant::whereIn('id', $ids)
            ->with('application', 'gradingSessions.examSession.room')
            ->get();

        $applicantSessionMap = [];
        foreach ($applicants as $applicant) {
            $gs = $applicant->gradingSessions->first();
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
            $gs = $a->gradingSessions->first();
            $scores = $allScores->get($a->id, collect())
                ->filter(fn ($s) => $gs && $s->grading_session_id === $gs->id)
                ->map(fn ($s) => [
                    'domain' => $s->aptitudeArea?->name ?? '—',
                    'raw' => $s->raw_score,
                    'max' => $s->max_score,
                    'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
                ])->values()->all();
            $overallPct = count($scores) > 0 ? (int) round(collect($scores)->avg('pct')) : 0;

            return [
                'id' => $a->id,
                'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                'reference' => $a->application?->reference_number ?? '—',
                'exam_date' => $gs?->examSession?->date?->format('F j, Y') ?? '—',
                'room_name' => $gs?->examSession?->room?->name ?? '—',
                'scores' => $scores,
                'overall_pct' => $overallPct,
            ];
        })->values()->all();

        $logicalUnit = $template->logical_unit ?? 'full';
        $chunkSize = in_array($logicalUnit, ['half_a4', 'half_legal', 'half_letter'], true) ? 2 : 1;

        $sheetsHtml = [];
        foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
            if (count($chunk) === 2) {
                $html1 = $this->templateService->render($template, [$chunk[0]], false);
                $html2 = $this->templateService->render($template, [$chunk[1]], false);
                $sheetsHtml[] = $html1.$html2;
            } else {
                $sheetsHtml[] = $this->templateService->render($template, $chunk, false);
            }
        }

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
}
