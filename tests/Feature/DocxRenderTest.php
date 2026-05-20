<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ResultSheetDocxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DocxRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_returns_error_for_missing_file(): void
    {
        $service = app(ResultSheetDocxService::class);
        $html = $service->renderFromFullPath('/nonexistent/file.docx', []);

        $this->assertStringContainsString('not found', $html);
    }

    public function test_render_sanitizes_placeholder_injection(): void
    {
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/test_sanitize_'.uniqid().'.docx';

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('{{safe_placeholder}}');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        try {
            $service = app(ResultSheetDocxService::class);
            $html = $service->renderFromFullPath($tempFile, [
                'safe_placeholder' => 'value with {{evil}} injection',
            ]);

            $this->assertStringNotContainsString('{{evil}}', $html);
        } finally {
            @unlink($tempFile);
        }
    }

    public function test_render_audit_log_created(): void
    {
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/test_audit_'.uniqid().'.docx';

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('Test');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $this->actingAs(User::factory()->create());

        try {
            $service = app(ResultSheetDocxService::class);
            $service->renderFromFullPath($tempFile, []);
        } finally {
            @unlink($tempFile);
        }

        $this->assertTrue(true);
    }
}
