<?php

namespace Tests\Unit\Services;

use App\Services\ResultSheetDocxService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class ResultSheetDocxServiceRepairTest extends TestCase
{
    private ResultSheetDocxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResultSheetDocxService;
    }

    private function invokeMergeAdjacentRuns(string $content): string
    {
        $method = new ReflectionMethod($this->service, 'mergeAdjacentRuns');

        return $method->invoke($this->service, $content);
    }

    private function invokeStripXmlFromMacros(string $content): string
    {
        $method = new ReflectionMethod($this->service, 'stripXmlFromMacros');

        return $method->invoke($this->service, $content);
    }

    #[Test]
    public function test_merge_adjacent_runs_reunites_split_placeholder(): void
    {
        $input = '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>{{scores</w:t></w:r><w:r><w:rPr><w:b/></w:rPr><w:t>_rows}}</w:t></w:r></w:p>';

        $result = $this->invokeMergeAdjacentRuns($input);

        $this->assertStringContainsString('{{scores_rows}}', $result);
        $this->assertEquals(1, substr_count($result, '</w:r>'));
    }

    #[Test]
    public function test_merge_preserves_different_formatting_runs(): void
    {
        $input = '<w:p>'
            .'<w:r><w:rPr><w:b/></w:rPr><w:t>bold</w:t></w:r>'
            .'<w:r><w:rPr><w:i/></w:rPr><w:t>italic</w:t></w:r>'
            .'</w:p>';

        $result = $this->invokeMergeAdjacentRuns($input);

        $this->assertStringContainsString('bold', $result);
        $this->assertStringContainsString('italic', $result);
        $this->assertEquals(2, substr_count($result, '</w:r>'));
    }

    #[Test]
    public function test_merge_handles_single_run_paragraph(): void
    {
        $input = '<w:p><w:r><w:t>hello</w:t></w:r></w:p>';

        $result = $this->invokeMergeAdjacentRuns($input);

        $this->assertEquals($input, $result);
    }

    #[Test]
    public function test_strip_xml_macros_only_targets_double_braces(): void
    {
        $input = '<w:r><w:t>r:id="{rId1}" and {{app_name}}</w:t></w:r>';

        $result = $this->invokeStripXmlFromMacros($input);

        $this->assertStringContainsString('{rId1}', $result);
        $this->assertStringContainsString('{{app_name}}', $result);
    }

    #[Test]
    public function test_strip_xml_does_not_corrupt_relationship_ids(): void
    {
        $input = '<Relationship Id="rId1" Type="http://schemas" Target="doc.xml"/>';

        $result = $this->invokeStripXmlFromMacros($input);

        $this->assertEquals($input, $result);
    }
}
