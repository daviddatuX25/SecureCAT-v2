<?php

namespace Tests\Unit\Services;

use App\Services\ResultSheetOdtService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResultSheetOdtServiceTest extends TestCase
{
    private ResultSheetOdtService $service;

    private string $tempDir;

    private static function zipAvailable(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    private static function odtFixturePath(): string
    {
        return base_path('tests/fixtures/test_template.odt');
    }

    private static function odtSplitFixturePath(): string
    {
        return base_path('tests/fixtures/test_template_split.odt');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResultSheetOdtService;
        $this->tempDir = storage_path('app/temp/tests/odt_service');
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

    #[Test]
    public function render_from_full_path_replaces_placeholders(): void
    {
        if (! self::zipAvailable() || ! file_exists(self::odtFixturePath())) {
            $this->markTestSkipped('ZipArchive not available or fixture missing');
        }

        $html = $this->service->renderFromFullPath(self::odtFixturePath(), [
            'applicant_name' => 'Juan Dela Cruz',
            'applicant_reference' => 'ICAT-2026-00042',
            'exam_date' => 'May 20, 2026',
            'overall_pct' => '82',
            'spatial_awareness' => '80',
            'BSIT_check' => '✔',
            'applicant_name_2' => 'Maria Santos',
            'applicant_reference_2' => 'ICAT-2026-00043',
            'spatial_awareness_2' => '72',
        ]);

        $this->assertStringContainsString('Juan Dela Cruz', $html);
        $this->assertStringContainsString('ICAT-2026-00042', $html);
        $this->assertStringContainsString('Maria Santos', $html);
    }

    #[Test]
    public function render_from_full_path_returns_error_on_missing_file(): void
    {
        $html = $this->service->renderFromFullPath('/nonexistent/file.odt', []);

        $this->assertStringContainsString('not found', $html);
    }

    #[Test]
    public function render_from_storage_path_returns_placeholder_when_null(): void
    {
        $html = $this->service->renderFromStoragePath(null, []);

        $this->assertStringContainsString('No document template', $html);
    }

    #[Test]
    public function validate_template_detects_missing_required_placeholders(): void
    {
        if (! self::zipAvailable() || ! file_exists(self::odtFixturePath())) {
            $this->markTestSkipped('ZipArchive not available or fixture missing');
        }

        $categorized = [
            'required' => ['applicant_name', 'missing_placeholder'],
            'recommended' => [],
            'optional' => [],
            'html_only' => [],
            'domain' => [],
            'personnel' => [],
            'institution' => [],
            'applicant2' => [],
        ];

        $result = $this->service->validateTemplate(self::odtFixturePath(), $categorized, true);

        $this->assertFalse($result->valid);
        $this->assertContains('missing_placeholder', $result->missing);
        $this->assertContains('applicant_name', $result->found);
    }

    #[Test]
    public function validate_template_passes_with_all_required_placeholders(): void
    {
        if (! self::zipAvailable() || ! file_exists(self::odtFixturePath())) {
            $this->markTestSkipped('ZipArchive not available or fixture missing');
        }

        $categorized = [
            'required' => ['applicant_name', 'applicant_reference'],
            'recommended' => ['exam_date', 'overall_pct'],
            'optional' => ['spatial_awareness', 'BSIT_check'],
            'html_only' => [],
            'domain' => [],
            'personnel' => [],
            'institution' => [],
            'applicant2' => ['applicant_name_2', 'applicant_reference_2', 'spatial_awareness_2'],
        ];

        $result = $this->service->validateTemplate(self::odtFixturePath(), $categorized, true);

        $this->assertTrue($result->valid);
        $this->assertEmpty($result->missing);
    }

    #[Test]
    public function validate_template_returns_error_for_missing_file(): void
    {
        $categorized = [
            'required' => ['applicant_name'],
            'recommended' => [],
            'optional' => [],
            'html_only' => [],
            'domain' => [],
            'personnel' => [],
            'institution' => [],
            'applicant2' => [],
        ];

        $result = $this->service->validateTemplate('/nonexistent/file.odt', $categorized, true);

        $this->assertFalse($result->valid);
        $this->assertNotEmpty($result->missing);
    }

    #[Test]
    public function get_repaired_template_merges_split_placeholders(): void
    {
        if (! self::zipAvailable() || ! file_exists(self::odtSplitFixturePath())) {
            $this->markTestSkipped('ZipArchive not available or fixture missing');
        }

        $repairedPath = $this->service->getRepairedTemplate(self::odtSplitFixturePath());

        $this->assertNotNull($repairedPath);
        $this->assertFileExists($repairedPath);

        $zip = new \ZipArchive;
        $zip->open($repairedPath);
        $content = $zip->getFromName('content.xml');
        $zip->close();

        $this->assertStringContainsString('{{applicant_name}}', $content, 'Split placeholder should be merged after repair');

        @unlink($repairedPath);
    }

    #[Test]
    public function it_merges_split_spans_in_headings_and_handles_spaces(): void
    {
        $reflection = new \ReflectionMethod(ResultSheetOdtService::class, 'repairOdtXml');
        $reflection->setAccessible(true);

        // Test with split braces inside a heading
        $xmlHeading = '<text:h><text:span text:style-name="T1">{</text:span><text:span text:style-name="T1">{applicant_name}</text:span><text:span text:style-name="T1">}</text:span><text:span text:style-name="T1">}</text:span></text:h>';
        $repaired = $reflection->invoke($this->service, $xmlHeading);
        $this->assertStringContainsString('<text:span text:style-name="T1">{{applicant_name}}}</text:span>', $repaired);

        // Test with text:s space tags
        $xmlSpaces = '<text:p><text:span text:style-name="T1">hello</text:span><text:span text:style-name="T1"><text:s text:c="3"/></text:span><text:span text:style-name="T1">world</text:span></text:p>';
        $repairedSpaces = $reflection->invoke($this->service, $xmlSpaces);
        $this->assertStringContainsString('hello   world', $repairedSpaces);
    }
}
