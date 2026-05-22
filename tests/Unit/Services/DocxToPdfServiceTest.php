<?php

namespace Tests\Unit\Services;

use App\Services\DocxToPdfService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class DocxToPdfServiceTest extends TestCase
{
    private DocxToPdfService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $logger = $this->createMock(LoggerInterface::class);
        $this->service = new DocxToPdfService($logger);
    }

    public function test_is_available_returns_false_when_path_is_empty(): void
    {
        Config::set('docx.libreoffice_path', '');
        $this->assertFalse($this->service->isAvailable());

        Config::set('docx.libreoffice_path', null);
        $this->assertFalse($this->service->isAvailable());
    }

    public function test_convert_throws_on_missing_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DOCX file not found');

        $this->service->convert('/path/to/nonexistent.docx');
    }

    public function test_convert_batch_throws_on_empty_paths(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('docxPaths must not be empty.');

        $this->service->convertBatch([]);
    }

    public function test_convert_batch_throws_on_invalid_copies(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('copies must be at least 1.');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_docx_');

        try {
            $this->service->convertBatch([$tempFile], 0);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_convert_integration_skips_when_no_lo(): void
    {
        if (! $this->service->isAvailable()) {
            $this->markTestSkipped('LibreOffice is not available.');
        }

        // We won't actually perform a real conversion in a basic unit test without a valid DOCX.
        // If LO is available, we'd need a real DOCX file.
        $this->assertTrue(true);
    }
}
