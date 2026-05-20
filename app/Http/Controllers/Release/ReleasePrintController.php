<?php

namespace App\Http\Controllers\Release;

use App\Http\Controllers\Controller;
use App\Http\Requests\Release\MarkPrintedRequest;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\GradingSession;
use App\Models\ResultSheetTemplate;
use App\Services\AuditService;
use App\Services\DocxToPdfService;
use App\Services\PrintBatchService;
use App\Services\ResultSheetDocxService;
use App\Services\ResultSheetPdfService;
use App\Services\ResultSheetTemplateService;
use App\ValueObjects\RenderResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReleasePrintController extends Controller
{
    public function __construct(
        private PrintBatchService $printService,
        private ResultSheetTemplateService $templateService,
        private ResultSheetPdfService $pdfService,
        private DocxToPdfService $docxToPdfService,
        private ResultSheetDocxService $docxService,
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

        $filename = str_replace(' ', '_', $this->formatName($applicant)).'_result_sheet.pdf';

        if ($template->mode === ResultSheetTemplate::MODE_DOCX && $template->docx_path && $this->docxToPdfService->isAvailable()) {
            $applicantsWithScores = $this->templateService->fetchApplicantsWithScores(
                [$applicant->id],
                $grading_session->id,
            );

            if (empty($applicantsWithScores)) {
                abort(404, 'Applicant data not found.');
            }

            $docxFiles = $this->templateService->buildFilledDocxFiles($applicantsWithScores, $template);

            try {
                $pdfContent = $this->docxToPdfService->convert($docxFiles[0]);

                return request()->boolean('download')
                    ? response()->streamDownload(fn () => print ($pdfContent), $filename, ['Content-Type' => 'application/pdf'])
                    : response()->make($pdfContent, 200, ['Content-Type' => 'application/pdf']);
            } finally {
                foreach ($docxFiles as $f) {
                    @unlink($f);
                }
            }
        }

        $session = $grading_session->load(['examSession.room']);
        $applicant->load('application');
        $scores = $grading_session->applicantScores()
            ->where('applicant_id', $applicant->id)
            ->with('aptitudeArea')
            ->get();

        $applicantData = $this->buildApplicantData($applicant, $session, $scores);
        $result = $this->templateService->render($template, [$applicantData], false, true);

        return request()->boolean('download')
            ? $this->pdfService->download($result, $filename)
            : $this->pdfService->inline($result, $filename);
    }

    public function downloadDocx(GradingSession $grading_session, Applicant $applicant): SymfonyResponse
    {
        $template = ResultSheetTemplate::where('is_active', true)
            ->where('mode', ResultSheetTemplate::MODE_DOCX)
            ->first();

        if (! $template || ! $template->docx_path) {
            abort(404, 'No active DOCX template found.');
        }

        $fullPath = Storage::path($template->docx_path);
        if (! is_file($fullPath)) {
            abort(404, 'DOCX template file not found on disk.');
        }

        $applicantsWithScores = $this->templateService->fetchApplicantsWithScores(
            [$applicant->id],
            $grading_session->id,
        );

        if (empty($applicantsWithScores)) {
            abort(404, 'Applicant data not found.');
        }

        $replacements = $this->templateService->buildDocxReplacements($applicantsWithScores);

        $tempDir = storage_path('app/temp/phpword');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $repairedPath = $this->docxService->getRepairedDocx($fullPath) ?: $fullPath;

        $processor = new TemplateProcessor($repairedPath);
        $processor->setMacroChars('{{', '}}');

        foreach ($replacements as $key => $value) {
            $processor->setValue($key, $value);
        }

        $tempFile = tempnam($tempDir, 'docx_download_').'.docx';
        $processor->saveAs($tempFile);

        if ($repairedPath !== $fullPath && is_file($repairedPath)) {
            @unlink($repairedPath);
        }

        app(AuditService::class)->log('result_sheet.downloaded_docx', ResultSheetTemplate::class, $template->id, [], [
            'applicant_id' => $applicant->id,
            'grading_session' => $grading_session->id,
        ]);

        $filename = sprintf('Result-Sheet-%s.docx',
            Str::slug($applicant->application?->last_name ?? $applicant->id)
        );

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function printBulk(GradingSession $grading_session): Response
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        if (! $template) {
            return Inertia::render('Release/ResultSheetBulk', $this->noTemplatePayload());
        }

        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
        $applicantsWithScores = $this->templateService->fetchApplicantsWithScores($ids, $grading_session->id);

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

        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
        $applicantsWithScores = $this->templateService->fetchApplicantsWithScores($ids, $grading_session->id);

        $filename = "session_{$grading_session->id}_result_sheets.pdf";

        if ($template->mode === ResultSheetTemplate::MODE_DOCX && $template->docx_path && $this->docxToPdfService->isAvailable()) {
            $docxFiles = $this->templateService->buildFilledDocxFiles($applicantsWithScores, $template);

            try {
                $pdfContent = $this->docxToPdfService->convertBatch($docxFiles, $copies);

                return request()->boolean('download')
                    ? response()->streamDownload(fn () => print ($pdfContent), $filename, ['Content-Type' => 'application/pdf'])
                    : response()->make($pdfContent, 200, ['Content-Type' => 'application/pdf']);
            } finally {
                foreach ($docxFiles as $f) {
                    @unlink($f);
                }
            }
        }

        $sheetsHtml = $this->templateService->buildRawSheetsFromApplicantData($applicantsWithScores, $template);

        $meta = RenderResult::fromTemplate($template);

        return request()->boolean('download')
            ? $this->pdfService->bulkDownload($sheetsHtml, $meta, $filename, $copies)
            : $this->pdfService->bulkInline($sheetsHtml, $meta, $filename, $copies);
    }

    public function printBulkAgnostic(): Response
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);

        if (! $template) {
            return Inertia::render('Release/ResultSheetBulk', $this->noTemplatePayload($ids));
        }

        $applicantsWithScores = $this->templateService->fetchApplicantsWithScores($ids);

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
        $applicantsWithScores = $this->templateService->fetchApplicantsWithScores($ids);

        if ($template->mode === ResultSheetTemplate::MODE_DOCX && $template->docx_path && $this->docxToPdfService->isAvailable()) {
            $docxFiles = $this->templateService->buildFilledDocxFiles($applicantsWithScores, $template);

            try {
                $pdfContent = $this->docxToPdfService->convertBatch($docxFiles, $copies);

                return request()->boolean('download')
                    ? response()->streamDownload(fn () => print ($pdfContent), 'result_sheets.pdf', ['Content-Type' => 'application/pdf'])
                    : response()->make($pdfContent, 200, ['Content-Type' => 'application/pdf']);
            } finally {
                foreach ($docxFiles as $f) {
                    @unlink($f);
                }
            }
        }

        $sheetsHtml = $this->templateService->buildRawSheetsFromApplicantData($applicantsWithScores, $template);

        $meta = RenderResult::fromTemplate($template);

        return request()->boolean('download')
            ? $this->pdfService->bulkDownload($sheetsHtml, $meta, 'result_sheets.pdf', $copies)
            : $this->pdfService->bulkInline($sheetsHtml, $meta, 'result_sheets.pdf', $copies);
    }

    public function downloadBulkDocx(GradingSession $grading_session): SymfonyResponse
    {
        $template = ResultSheetTemplate::where('is_active', true)
            ->where('mode', ResultSheetTemplate::MODE_DOCX)
            ->first();

        if (! $template || ! $template->docx_path) {
            abort(404, 'No active DOCX template found.');
        }

        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
        $applicantsWithScores = $this->templateService->fetchApplicantsWithScores($ids, $grading_session->id);

        return $this->buildBulkDocxZip($applicantsWithScores, $template, "session_{$grading_session->id}_result_sheets.zip");
    }

    public function downloadBulkAgnosticDocx(): SymfonyResponse
    {
        $template = ResultSheetTemplate::where('is_active', true)
            ->where('mode', ResultSheetTemplate::MODE_DOCX)
            ->first();

        if (! $template || ! $template->docx_path) {
            abort(404, 'No active DOCX template found.');
        }

        $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
        $applicantsWithScores = $this->templateService->fetchApplicantsWithScores($ids);

        return $this->buildBulkDocxZip($applicantsWithScores, $template, 'result_sheets.zip');
    }

    /**
     * Build a ZIP archive containing filled DOCX files for each applicant.
     */
    private function buildBulkDocxZip(array $applicantsWithScores, ResultSheetTemplate $template, string $zipFilename): SymfonyResponse
    {
        $docxFiles = $this->templateService->buildFilledDocxFiles($applicantsWithScores, $template);

        $tempDir = storage_path('app/temp/phpword');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = tempnam($tempDir, 'docx_bulk_').'.zip';

        try {
            $zip = new \ZipArchive;
            $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach ($docxFiles as $i => $docxFile) {
                $applicantName = $applicantsWithScores[$i]['name'] ?? "applicant_{$i}";
                $entryName = sprintf('Result-Sheet-%s.docx', Str::slug($applicantName));
                $zip->addFile($docxFile, $entryName);
            }

            $zip->close();

            app(AuditService::class)->log('result_sheet.downloaded_bulk_docx', ResultSheetTemplate::class, $template->id, [], [
                'count' => count($applicantsWithScores),
            ]);

            return response()->download($zipPath, $zipFilename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } finally {
            foreach ($docxFiles as $f) {
                @unlink($f);
            }
        }
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
            ? (int) round(collect($scores)->avg('pct_numeric'))
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
     * @return array<int, array{domain: string, raw: int|float|null, max: int|float|null, pct: int, pct_string: string|null, pct_numeric: int}>
     */
    private function mapScores(Collection $scores): array
    {
        return $scores->map(function ($s) {
            if ($s->percentile_string !== null) {
                $numeric = preg_match('/(\d+)/', $s->percentile_string, $matches)
                    ? (int) ($matches[1] ?? 0)
                    : 0;

                return [
                    'domain' => $s->aptitudeArea?->name ?? '—',
                    'raw' => $s->raw_score,
                    'max' => $s->max_score,
                    'pct' => $numeric,
                    'pct_string' => $s->percentile_string,
                    'pct_numeric' => $numeric,
                ];
            }

            $pctVal = $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0;

            return [
                'domain' => $s->aptitudeArea?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $pctVal,
                'pct_string' => null,
                'pct_numeric' => $pctVal,
            ];
        })->values()->all();
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
