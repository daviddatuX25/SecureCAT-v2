<?php

namespace Tests\Unit;

use App\Models\ResultSheetTemplate;
use App\Services\ResultSheetTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultSheetTemplateActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_filled_docx_files_throws_on_null_docx_path(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => ResultSheetTemplate::MODE_DOCX,
            'docx_path' => null,
            'is_active' => true,
        ]);

        $service = app(ResultSheetTemplateService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template has no DOCX file');

        $service->buildFilledDocxFiles([], $template);
    }

    public function test_build_filled_docx_files_throws_on_missing_disk_file(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => ResultSheetTemplate::MODE_DOCX,
            'docx_path' => 'result-sheet-templates/99999.docx',
            'is_active' => true,
        ]);

        $service = app(ResultSheetTemplateService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DOCX template file not found on disk');

        $service->buildFilledDocxFiles([], $template);
    }

    public function test_docx_mode_template_with_null_path_exists_without_crash(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => ResultSheetTemplate::MODE_DOCX,
            'docx_path' => null,
            'is_active' => true,
        ]);

        $this->assertNull($template->docx_path);
        $this->assertEquals(ResultSheetTemplate::MODE_DOCX, $template->mode);
    }
}
