<?php

namespace Tests\Unit;

use App\Models\AptitudeArea;
use App\Models\PercentileConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AptitudeAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_percentile_returns_matching_string(): void
    {
        $area = AptitudeArea::factory()->create(['scoring_method' => 'conversion_table']);
        PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 10,
            'percentile_output' => '85th',
        ]);

        $this->assertSame('85th', $area->lookupPercentile(10));
    }

    public function test_lookup_percentile_returns_null_for_unmapped_score(): void
    {
        $area = AptitudeArea::factory()->create(['scoring_method' => 'conversion_table']);
        PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 10,
            'percentile_output' => '85th',
        ]);

        $this->assertNull($area->lookupPercentile(99));
    }

    public function test_resolve_score_with_conversion_table(): void
    {
        $area = AptitudeArea::factory()->create(['scoring_method' => 'conversion_table', 'formula' => null]);
        PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 12,
            'percentile_output' => '99+',
        ]);

        $result = $area->resolveScore(12);

        $this->assertNull($result['normalized_score']);
        $this->assertSame('99+', $result['percentile_string']);
    }

    public function test_resolve_score_returns_na_for_unmapped_raw_score(): void
    {
        $area = AptitudeArea::factory()->create(['scoring_method' => 'conversion_table', 'formula' => null]);

        $result = $area->resolveScore(5);

        $this->assertNull($result['normalized_score']);
        $this->assertSame('N/A', $result['percentile_string']);
    }

    public function test_resolve_score_with_formula(): void
    {
        $area = AptitudeArea::factory()->create([
            'scoring_method' => 'formula',
            'formula' => '(x / max_items) * 100',
            'max_items' => 100,
        ]);

        $result = $area->resolveScore(85);

        $this->assertNotNull($result['normalized_score']);
        $this->assertSame(85.0, $result['normalized_score']);
        $this->assertNull($result['percentile_string']);
    }

    public function test_percentile_conversions_relationship(): void
    {
        $area = AptitudeArea::factory()->create(['scoring_method' => 'conversion_table']);
        PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 0,
            'percentile_output' => 'N/A',
        ]);

        $this->assertCount(1, $area->fresh()->percentileConversions);
    }
}
