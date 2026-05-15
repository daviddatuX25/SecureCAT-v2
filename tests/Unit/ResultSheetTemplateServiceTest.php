<?php

namespace Tests\Unit;

use App\Models\AptitudeArea;
use App\Models\ResultSheetTemplate;
use App\Services\ResultSheetTemplateService;
use App\ValueObjects\RenderResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultSheetTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResultSheetTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResultSheetTemplateService::class);
        AptitudeArea::factory()->create(['name' => 'Spatial Awareness', 'is_active' => true, 'display_order' => 0]);
        AptitudeArea::factory()->create(['name' => 'Numerical Ability', 'is_active' => true, 'display_order' => 1]);
    }

    public function test_render_returns_render_result(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'content' => '<p>{{applicant_name}}</p>',
            'is_active' => true,
        ]);

        $result = $this->service->render($template, [], true);

        $this->assertInstanceOf(RenderResult::class, $result);
        $this->assertSame('html', $result->mode);
        $this->assertSame('a4', $result->paperSize);
        $this->assertSame('portrait', $result->orientation);
        $this->assertSame('full', $result->logicalUnit);
        $this->assertStringContainsString('print-template', $result->html);
    }

    public function test_render_dual_returns_render_result_with_dual_html(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'half_a4',
            'content' => '<p>{{applicant_name}}</p>',
            'is_active' => true,
        ]);

        $applicant1 = ['name' => 'Alice', 'reference' => 'REF-001', 'exam_date' => '2026-01-01', 'room_name' => 'Room A', 'scores' => [], 'overall_pct' => 85];
        $applicant2 = ['name' => 'Bob', 'reference' => 'REF-002', 'exam_date' => '2026-01-01', 'room_name' => 'Room B', 'scores' => [], 'overall_pct' => 90];

        $result = $this->service->renderDual($template, $applicant1, $applicant2, true);

        $this->assertInstanceOf(RenderResult::class, $result);
        $this->assertTrue($result->isHalf());
        $this->assertStringContainsString('print-template--dual', $result->html);
        $this->assertStringContainsString('Alice', $result->html);
        $this->assertStringContainsString('Bob', $result->html);
        $this->assertEquals(1, substr_count($result->html, '<style>'));
    }

    public function test_render_full_page_has_no_dual_class(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'content' => '<p>{{applicant_name}}</p>',
            'is_active' => true,
        ]);

        $result = $this->service->render($template, [], true);

        // Check the HTML container (after </style>) lacks the dual class
        $htmlAfterStyle = preg_replace('/^.*<\/style>/s', '', $result->html);
        $this->assertStringNotContainsString('print-template--dual', $htmlAfterStyle);
        $this->assertStringContainsString('print-template', $htmlAfterStyle);
    }
}
