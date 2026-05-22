<?php

namespace Tests\Unit;

use App\Models\ResultSheetTemplate;
use App\Services\ResultSheetTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultSheetTemplateActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_filled_document_files_throws_on_null_document_path(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => ResultSheetTemplate::MODE_DOCX,
            'document_path' => null,
            'is_active' => true,
        ]);

        $service = app(ResultSheetTemplateService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template has no document file');

        $service->buildFilledDocumentFiles([], $template);
    }

    public function test_build_filled_document_files_throws_on_missing_disk_file(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => ResultSheetTemplate::MODE_DOCX,
            'document_path' => 'result-sheet-templates/99999.docx',
            'is_active' => true,
        ]);

        $service = app(ResultSheetTemplateService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Document template file not found on disk');

        $service->buildFilledDocumentFiles([], $template);
    }

    public function test_docx_mode_template_with_null_path_exists_without_crash(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => ResultSheetTemplate::MODE_DOCX,
            'document_path' => null,
            'is_active' => true,
        ]);

        $this->assertNull($template->document_path);
        $this->assertEquals(ResultSheetTemplate::MODE_DOCX, $template->mode);
    }
}
