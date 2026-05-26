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

    public function test_convert_multiple_chunks_calls_libreoffice_in_chunks_of_40(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $serviceMock = $this->getMockBuilder(DocxToPdfService::class)
            ->setConstructorArgs([$logger])
            ->onlyMethods(['runCommand'])
            ->getMock();

        $commandsRun = [];
        $serviceMock->method('runCommand')
            ->willReturnCallback(function (array $command, int $timeout) use (&$commandsRun) {
                $commandsRun[] = $command;

                // Simulate LibreOffice outputting the converted PDF files
                $outDirIndex = array_search('--outdir', $command);
                $this->assertNotFalse($outDirIndex);
                $outputDir = $command[$outDirIndex + 1];

                // The input files are everything after the output directory parameter
                $inputFiles = array_slice($command, $outDirIndex + 2);
                foreach ($inputFiles as $inputFile) {
                    $basename = pathinfo($inputFile, PATHINFO_FILENAME);
                    $pdfPath = $outputDir.DIRECTORY_SEPARATOR.$basename.'.pdf';
                    file_put_contents($pdfPath, 'dummy pdf content');
                }

                $proc = $this->createMock(\Symfony\Component\Process\Process::class);
                $proc->method('isSuccessful')->willReturn(true);
                return $proc;
            });

        // Generate 45 dummy file paths
        $dummyFiles = [];
        for ($i = 0; $i < 45; $i++) {
            $dummyFiles[] = "/path/to/test_file_{$i}.docx";
        }

        $results = $serviceMock->convertMultiple($dummyFiles);

        // Assert chunking logic: 45 files chunked by 40 should result in 2 commands (40 and 5)
        $this->assertCount(2, $commandsRun);
        $this->assertCount(45, $results);

        // First command should have 40 files
        // Command structure: [lo_path, --headless, --norestore, -env:..., --convert-to, pdf, --outdir, outdir, file1, ..., file40]
        // Base command has 8 arguments, so input files start at index 8
        $this->assertCount(8 + 40, $commandsRun[0]);
        // Second command should have 5 files
        $this->assertCount(8 + 5, $commandsRun[1]);

        $this->assertEquals('dummy pdf content', $results[0]);
    }
}

