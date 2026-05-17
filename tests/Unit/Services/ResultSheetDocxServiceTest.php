<?php

namespace Tests\Unit\Services;

use App\Services\ResultSheetDocxService;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResultSheetDocxServiceTest extends TestCase
{
    private ResultSheetDocxService $service;

    private string $tempDir;

    private static function zipAvailable(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResultSheetDocxService;
        $this->tempDir = storage_path('app/temp/tests/docx_service');
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir.'/*');
        if ($files) {
            array_map('unlink', $files);
        }
        parent::tearDown();
    }

    private function createDocxWithPlaceholders(array $placeholders): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $text = collect($placeholders)->map(fn (string $p) => '{{'.$p.'}}')->implode(' ');
        $section->addText($text);

        $path = $this->tempDir.'/test_'.uniqid().'.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        return $path;
    }

    #[Test]
    public function render_returns_html(): void
    {
        if (! self::zipAvailable()) {
            $this->markTestSkipped('ZipArchive extension not loaded');
        }
        $path = $this->createDocxWithPlaceholders(['applicant_name']);
        $html = $this->service->renderDocxFromFullPath($path, ['applicant_name' => 'Juan']);

        $this->assertStringContainsString('Juan', $html);
    }

    #[Test]
    public function render_returns_error_for_missing_file(): void
    {
        $html = $this->service->renderDocxFromFullPath('/nonexistent/file.docx', []);

        $this->assertStringContainsString('DOCX file not found', $html);
    }

    #[Test]
    public function render_storage_path_returns_error_for_null(): void
    {
        $html = $this->service->renderDocxFromStoragePath(null, []);

        $this->assertStringContainsString('No DOCX template', $html);
    }

    #[Test]
    public function validate_returns_valid_for_complete_file(): void
    {
        if (! self::zipAvailable()) {
            $this->markTestSkipped('ZipArchive extension not loaded');
        }
        $required = ['applicant_name', 'applicant_reference'];
        $path = $this->createDocxWithPlaceholders($required);

        $result = $this->service->validateDocxTemplate($path, $required, true);

        $this->assertTrue($result->valid);
        $this->assertEmpty($result->missing);
    }

    #[Test]
    public function validate_reports_missing_placeholders(): void
    {
        if (! self::zipAvailable()) {
            $this->markTestSkipped('ZipArchive extension not loaded');
        }
        $required = ['applicant_name', 'applicant_reference', 'exam_date'];
        $path = $this->createDocxWithPlaceholders(['applicant_name']);

        $result = $this->service->validateDocxTemplate($path, $required, true);

        $this->assertFalse($result->valid);
        $this->assertContains('applicant_reference', $result->missing);
        $this->assertContains('exam_date', $result->missing);
        $this->assertNotContains('applicant_name', $result->missing);
    }

    #[Test]
    public function validate_filters_crosswise_in_full_mode(): void
    {
        if (! self::zipAvailable()) {
            $this->markTestSkipped('ZipArchive extension not loaded');
        }
        $required = ['applicant_name', 'applicant_name_2'];
        $path = $this->createDocxWithPlaceholders(['applicant_name']);

        $result = $this->service->validateDocxTemplate($path, $required, false);

        $this->assertTrue($result->valid);
    }

    #[Test]
    public function validate_requires_crosswise_in_half_mode(): void
    {
        if (! self::zipAvailable()) {
            $this->markTestSkipped('ZipArchive extension not loaded');
        }
        $required = ['applicant_name', 'applicant_name_2'];
        $path = $this->createDocxWithPlaceholders(['applicant_name']);

        $result = $this->service->validateDocxTemplate($path, $required, true);

        $this->assertFalse($result->valid);
        $this->assertContains('applicant_name_2', $result->missing);
    }

    #[Test]
    public function validate_reports_extra_unknowns(): void
    {
        if (! self::zipAvailable()) {
            $this->markTestSkipped('ZipArchive extension not loaded');
        }
        $required = ['applicant_name'];
        $path = $this->createDocxWithPlaceholders(['applicant_name', 'applcant_name']);

        $result = $this->service->validateDocxTemplate($path, $required, true);

        $this->assertTrue($result->valid);
        $this->assertContains('applcant_name', $result->extra);
    }
}
