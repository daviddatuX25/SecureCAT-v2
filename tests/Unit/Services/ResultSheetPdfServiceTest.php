<?php

namespace Tests\Unit\Services;

use App\Services\PrintTemplateCssService;
use App\Services\ResultSheetPdfService;
use App\ValueObjects\RenderResult;
use Illuminate\Http\Response;
use Mockery;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

class ResultSheetPdfServiceTest extends TestCase
{
    private ResultSheetPdfService $service;

    private $cssServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cssServiceMock = Mockery::mock(PrintTemplateCssService::class);
        $this->service = new ResultSheetPdfService($this->cssServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -- previewDimensions ---------------------------------------------------

    public function test_preview_dimensions_returns_correct_mm_for_a4_portrait()
    {
        $result = new RenderResult(html: '<h1>Test</h1>', mode: 'single', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');

        $dims = $this->service->previewDimensions($result);

        $this->assertEquals(['width' => 210, 'height' => 297], $dims);
    }

    public function test_preview_dimensions_halves_height_for_half_a4()
    {
        $result = new RenderResult(html: '<h1>Test</h1>', mode: 'single', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'half_a4');

        $dims = $this->service->previewDimensions($result);

        $this->assertEquals(['width' => 210, 'height' => 148], $dims);
    }

    public function test_preview_dimensions_swaps_for_landscape()
    {
        $result = new RenderResult(html: '<h1>Test</h1>', mode: 'single', paperSize: 'a4', orientation: 'landscape', logicalUnit: 'full');

        $dims = $this->service->previewDimensions($result);

        $this->assertEquals(['width' => 297, 'height' => 210], $dims);
    }

    public function test_preview_dimensions_combines_half_and_landscape()
    {
        $result = new RenderResult(html: '<h1>Test</h1>', mode: 'dual', paperSize: 'a4', orientation: 'landscape', logicalUnit: 'half_a4');

        $dims = $this->service->previewDimensions($result);

        $this->assertEquals(['width' => 297, 'height' => 105], $dims);
    }

    // -- Builder delegation --------------------------------------------------

    public function test_inline_builds_pdf_with_correct_options()
    {
        $result = new RenderResult(html: '<h1>Test</h1>', mode: 'single', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('html')->with('<h1>Test</h1>')->andReturn($builderMock);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheet.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->with('<h1>Test</h1>')->andReturn($builderMock);

        $response = $this->service->inline($result);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_download_builds_pdf_with_landscape()
    {
        $result = new RenderResult(html: '<h1>Test</h1>', mode: 'single', paperSize: 'a4', orientation: 'landscape', logicalUnit: 'full');

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('html')->with('<h1>Test</h1>')->andReturn($builderMock);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('landscape')->andReturn($builderMock);
        $builderMock->shouldReceive('download')->with('result-sheet.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->with('<h1>Test</h1>')->andReturn($builderMock);

        $response = $this->service->download($result);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_bulk_inline_combines_sheets_with_page_breaks()
    {
        $meta = new RenderResult(html: '', mode: 'bulk', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $sheets = ['<div>Sheet 1</div>', '<div>Sheet 2</div>'];

        $combinedHtml = '<div>Sheet 1</div><div style="page-break-after: always;"></div><div>Sheet 2</div>';
        $expectedHtml = '<wrapped>'.$combinedHtml.'</wrapped>';

        $this->cssServiceMock->shouldReceive('wrapBulkForPdf')
            ->once()
            ->with($combinedHtml, 'a4', 'portrait')
            ->andReturn($expectedHtml);

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('html')->with($expectedHtml)->andReturn($builderMock);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheets.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->with($expectedHtml)->andReturn($builderMock);

        $response = $this->service->bulkInline($sheets, $meta);

        $this->assertInstanceOf(Response::class, $response);
    }
}
