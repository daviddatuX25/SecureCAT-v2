<?php

namespace App\Services;

use App\ValueObjects\RenderResult;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\Response;

class ResultSheetPdfService
{
    /**
     * Stream a single-applicant result sheet PDF inline (for "View PDF").
     */
    public function inline(RenderResult $result, string $filename = 'result-sheet.pdf'): Response
    {
        return $this->builder($result)->inline($filename)->toResponse(request());
    }

    /**
     * Download a single-applicant result sheet PDF (for "Download PDF").
     */
    public function download(RenderResult $result, string $filename = 'result-sheet.pdf'): Response
    {
        return $this->builder($result)->download($filename)->toResponse(request());
    }

    /**
     * Stream a multi-applicant bulk PDF inline, with page breaks between sheets.
     *
     * @param  string[]  $sheetsHtml  One HTML blob per logical sheet.
     */
    public function bulkInline(array $sheetsHtml, RenderResult $meta, string $filename = 'result-sheets.pdf'): Response
    {
        return $this->builder($meta, $this->combineSheets($sheetsHtml))
            ->inline($filename)
            ->toResponse(request());
    }

    /**
     * Download a multi-applicant bulk PDF.
     *
     * @param  string[]  $sheetsHtml  One HTML blob per logical sheet.
     */
    public function bulkDownload(array $sheetsHtml, RenderResult $meta, string $filename = 'result-sheets.pdf'): Response
    {
        return $this->builder($meta, $this->combineSheets($sheetsHtml))
            ->download($filename)
            ->toResponse(request());
    }

    /**
     * Return pixel/mm dimensions for iframe preview sizing.
     *
     * @return array{width: int, height: int}
     */
    public function previewDimensions(RenderResult $result): array
    {
        return $result->pageDimensions();
    }

    // -- Private Helpers ---------------------------------------------------

    private function builder(RenderResult $meta, ?string $html = null): PdfBuilder
    {
        $html ??= $meta->html;

        $builder = Pdf::html($html)
            ->format($meta->paperSize)
            ->margins(0, 0, 0, 0);

        if ($meta->orientation === 'landscape') {
            $builder->landscape();
        }

        return $builder;
    }

    private function combineSheets(array $sheetsHtml): string
    {
        return implode('<div style="page-break-after: always;"></div>', $sheetsHtml);
    }
}
