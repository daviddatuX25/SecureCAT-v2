<?php

namespace Tests\Unit\Services;

use App\Models\AptitudeArea;
use App\Services\ResultSheetTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResultSheetTemplateServiceDocxTest extends TestCase
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

    #[Test]
    public function test_build_docx_replacements_empties_scores_rows(): void
    {
        $applicants = [
            ['name' => 'Alice', 'reference' => 'REF-001', 'exam_date' => '2026-01-01', 'room_name' => 'Room A', 'scores' => [['domain' => 'Spatial Awareness', 'raw' => 20, 'max' => 25, 'pct' => 80]], 'overall_pct' => 80],
        ];

        $docx = $this->service->buildDocxReplacements($applicants, false);

        $this->assertArrayHasKey('scores_rows', $docx);
        $this->assertSame('', $docx['scores_rows']);
    }

    #[Test]
    public function test_build_docx_replacements_strips_html_tags(): void
    {
        $applicants = [
            ['name' => 'Alice', 'reference' => 'REF-001', 'exam_date' => '2026-01-01', 'room_name' => 'Room A', 'scores' => [['domain' => 'Spatial Awareness', 'raw' => 20, 'max' => 25, 'pct' => 80]], 'overall_pct' => 80],
        ];

        $html = $this->service->buildReplacements($applicants, false);
        $docx = $this->service->buildDocxReplacements($applicants, false);

        $this->assertStringContainsString('<tr', $html['scores_rows']);
        $this->assertStringNotContainsString('<tr', $docx['scores_rows']);
    }

    #[Test]
    public function test_build_docx_replacements_preserves_plain_values(): void
    {
        $applicants = [
            ['name' => 'Juan Cruz', 'reference' => 'REF-001', 'exam_date' => '2026-01-01', 'room_name' => 'Room A', 'scores' => [], 'overall_pct' => 0],
        ];

        $docx = $this->service->buildDocxReplacements($applicants, false);

        $this->assertSame('Juan Cruz', $docx['applicant_name']);
        $this->assertSame('REF-001', $docx['applicant_reference']);
    }
}
