<?php

namespace App\Jobs;

use App\Models\PrintJob;
use App\Models\ResultSheetTemplate;
use App\Services\ResultSheetPdfService;
use App\Services\ResultSheetTemplateService;
use App\ValueObjects\RenderResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateBulkResultSheetPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public string $printJobId,
    ) {}

    public function handle(
        ResultSheetTemplateService $templateService,
        ResultSheetPdfService $pdfService,
    ): void {
        $printJob = PrintJob::findOrFail($this->printJobId);
        $printJob->update(['status' => 'processing']);

        try {
            $template = ResultSheetTemplate::where('is_active', true)->firstOrFail();
            $meta = RenderResult::fromTemplate($template);

            $applicantIds = $printJob->applicant_ids;
            $chunkSize = 10;
            $total = count($applicantIds);
            $sheetsHtml = [];

            foreach (array_chunk($applicantIds, $chunkSize) as $i => $chunk) {
                $sheetsHtml = array_merge(
                    $sheetsHtml,
                    $templateService->buildSheetsForApplicantIds($chunk, $template, $printJob->grading_session_id)
                );
                $processed = min(($i + 1) * $chunkSize, $total);
                $progress = (int) round(($processed / $total) * 100);
                $printJob->update(['progress' => min($progress, 99)]);
            }

            $path = "print-jobs/{$printJob->id}.pdf";
            $pdfContent = $pdfService->generateBulkPdfContent($sheetsHtml, $meta, $printJob->copies);
            Storage::disk('local')->put($path, $pdfContent);

            $printJob->update([
                'status' => 'completed',
                'progress' => 100,
                'pdf_path' => $path,
            ]);
        } catch (\Throwable $e) {
            $printJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
