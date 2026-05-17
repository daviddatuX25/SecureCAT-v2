<?php

namespace Tests\Unit\Services;

use App\Models\ResultSheetTemplate;
use App\Services\ResultSheetPdfService;
use App\ValueObjects\RenderResult;
use Illuminate\Http\Response;
use Mockery;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

class ResultSheetPdfServiceCopiesTest extends TestCase
{
    private ResultSheetPdfService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResultSheetPdfService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_builder_injects_watermark_overlay_when_present()
    {
        $result = new RenderResult(
            html: '<h1>Test</h1>',
            mode: 'single',
            paperSize: 'a4',
            orientation: 'portrait',
            logicalUnit: 'full',
            watermarkText: 'DRAFT',
        );

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheet.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')
            ->once()
            ->with(Mockery::on(fn (string $html) => str_contains($html, 'watermark-overlay') && str_contains($html, 'DRAFT')))
            ->andReturn($builderMock);

        $response = $this->service->inline($result);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_builder_does_not_inject_watermark_when_null()
    {
        $result = new RenderResult(
            html: '<h1>Test</h1>',
            mode: 'single',
            paperSize: 'a4',
            orientation: 'portrait',
            logicalUnit: 'full',
            watermarkText: null,
        );

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheet.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')
            ->once()
            ->with(Mockery::on(fn (string $html) => ! str_contains($html, 'watermark-overlay')))
            ->andReturn($builderMock);

        $response = $this->service->inline($result);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_copies_duplications_are_collated_per_sheet()
    {
        $meta = new RenderResult(html: '', mode: 'bulk', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $sheets = ['<div>A</div>', '<div>B</div>'];

        $expectedHtml = '<div>A</div><div style="page-break-after: always;"></div><div>A</div><div style="page-break-after: always;"></div><div>B</div><div style="page-break-after: always;"></div><div>B</div>';

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheets.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->with($expectedHtml)->andReturn($builderMock);

        $this->service->bulkInline($sheets, $meta, 'result-sheets.pdf', 2);
    }

    public function test_copies_default_1_produces_same_output()
    {
        $meta = new RenderResult(html: '', mode: 'bulk', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $sheets = ['<div>Sheet 1</div>', '<div>Sheet 2</div>'];

        $expectedHtml = '<div>Sheet 1</div><div style="page-break-after: always;"></div><div>Sheet 2</div>';

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheets.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->with($expectedHtml)->andReturn($builderMock);

        $this->service->bulkInline($sheets, $meta, 'result-sheets.pdf', 1);
    }

    public function test_render_result_from_template_carries_watermark()
    {
        $template = ResultSheetTemplate::factory()->make([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'watermark_text' => 'FINAL',
        ]);

        $result = RenderResult::fromTemplate($template);

        $this->assertEquals('FINAL', $result->watermarkText);
    }

    public function test_render_result_from_template_null_watermark()
    {
        $template = ResultSheetTemplate::factory()->make([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'watermark_text' => null,
        ]);

        $result = RenderResult::fromTemplate($template);

        $this->assertNull($result->watermarkText);
    }
}
