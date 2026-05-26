<?php

namespace Tests\Unit\Services;

use App\Services\DocxMergeService;
use Tests\TestCase;

class DocxMergeServiceTest extends TestCase
{
    private DocxMergeService $service;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocxMergeService();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    private function createMockDocx(string $content = 'Hello'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mock_docx_');
        $this->tempFiles[] = $path;

        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document><w:body><w:p><w:t>' . $content . '</w:t></w:p></w:body></w:document>');
            $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>');
            $zip->close();
        }

        return $path;
    }

    public function test_merge_throws_exception_on_empty_paths(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No DOCX files provided for merging.');

        $this->service->merge([], 'some_output_path.docx');
    }

    public function test_merge_copies_single_file_and_returns(): void
    {
        $mockFile = $this->createMockDocx('Single');
        $outputPath = tempnam(sys_get_temp_dir(), 'out_docx_');
        $this->tempFiles[] = $outputPath;

        $this->service->merge([$mockFile], $outputPath);

        $this->assertFileExists($outputPath);
        $this->assertEquals(file_get_contents($mockFile), file_get_contents($outputPath));
    }

    public function test_merge_multiple_docx_files_adds_alt_chunks(): void
    {
        $baseFile = $this->createMockDocx('Base');
        $file1 = $this->createMockDocx('Content 1');
        $file2 = $this->createMockDocx('Content 2');

        $outputPath = tempnam(sys_get_temp_dir(), 'out_docx_');
        $this->tempFiles[] = $outputPath;

        $this->service->merge([$baseFile, $file1, $file2], $outputPath);

        $this->assertFileExists($outputPath);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($outputPath));

        // Check if chunks are added
        $this->assertNotFalse($zip->getFromName('word/chunk1.docx'));
        $this->assertNotFalse($zip->getFromName('word/chunk2.docx'));

        // Check relationship definitions
        $rels = $zip->getFromName('word/_rels/document.xml.rels');
        $this->assertStringContainsString('rIdChunk1', $rels);
        $this->assertStringContainsString('rIdChunk2', $rels);
        $this->assertStringContainsString('http://schemas.openxmlformats.org/officeDocument/2006/relationships/aFChunk', $rels);

        // Check content type definitions
        $contentTypes = $zip->getFromName('[Content_Types].xml');
        $this->assertStringContainsString('PartName="/word/chunk1.docx"', $contentTypes);
        $this->assertStringContainsString('PartName="/word/chunk2.docx"', $contentTypes);

        // Check document XML includes altChunk placeholders
        $docXml = $zip->getFromName('word/document.xml');
        $this->assertStringContainsString('<w:altChunk r:id="rIdChunk1"/>', $docXml);
        $this->assertStringContainsString('<w:altChunk r:id="rIdChunk2"/>', $docXml);
        $this->assertStringContainsString('<w:br w:type="page"/>', $docXml);

        $zip->close();
    }
}
