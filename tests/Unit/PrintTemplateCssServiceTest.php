<?php

namespace Tests\Unit;

use App\Services\PrintTemplateCssService;
use Tests\TestCase;

class PrintTemplateCssServiceTest extends TestCase
{
    private PrintTemplateCssService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PrintTemplateCssService::class);
    }

    public function test_wrap_wraps_html_in_print_template_div(): void
    {
        $result = $this->service->wrap('<p>Hello</p>');

        $this->assertStringContainsString('<div class="print-template"><p>Hello</p></div>', $result);
        $this->assertStringContainsString('<style>', $result);
        $this->assertStringContainsString('@scope (.print-template)', $result);
    }

    public function test_wrap_dual_wraps_two_blocks_in_dual_container(): void
    {
        $result = $this->service->wrapDual('<p>Applicant 1</p>', '<p>Applicant 2</p>');

        $this->assertStringContainsString('print-template--dual', $result);
        $this->assertStringContainsString('print-template--half', $result);
        $this->assertStringContainsString('<p>Applicant 1</p>', $result);
        $this->assertStringContainsString('<p>Applicant 2</p>', $result);
        $this->assertEquals(1, substr_count($result, '<style>'));
    }

    public function test_wrap_dual_contains_both_applicant_blocks(): void
    {
        $result = $this->service->wrapDual('<p>A1</p>', '<p>A2</p>');

        $this->assertMatchesRegularExpression('/print-template--dual.*print-template--half.*A1.*print-template--half.*A2/s', $result);
    }
}
