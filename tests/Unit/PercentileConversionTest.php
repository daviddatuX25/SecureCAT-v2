<?php

namespace Tests\Unit;

use App\Models\AptitudeArea;
use App\Models\PercentileConversion;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PercentileConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_be_created(): void
    {
        $area = AptitudeArea::factory()->create();
        $conversion = PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 10,
            'percentile_output' => '85th',
        ]);

        $this->assertDatabaseHas('percentile_conversions', [
            'id' => $conversion->id,
            'aptitude_area_id' => $area->id,
            'raw_score' => 10,
            'percentile_output' => '85th',
        ]);
    }

    public function test_raw_score_is_cast_to_integer(): void
    {
        $area = AptitudeArea::factory()->create();
        $conversion = PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => '42',
            'percentile_output' => '99+',
        ]);

        $this->assertSame(42, $conversion->fresh()->raw_score);
    }

    public function test_belongs_to_aptitude_area(): void
    {
        $area = AptitudeArea::factory()->create();
        $conversion = PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 0,
            'percentile_output' => 'N/A',
        ]);

        $this->assertInstanceOf(AptitudeArea::class, $conversion->aptitudeArea);
        $this->assertSame($area->id, $conversion->aptitudeArea->id);
    }

    public function test_deleting_aptitude_area_cascades_to_conversions(): void
    {
        $area = AptitudeArea::factory()->create();
        PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 5,
            'percentile_output' => '90th',
        ]);

        $area->delete();

        $this->assertDatabaseEmpty('percentile_conversions');
    }

    public function test_unique_constraint_on_aptitude_area_and_raw_score(): void
    {
        $area = AptitudeArea::factory()->create();
        PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 7,
            'percentile_output' => '80th',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);
        PercentileConversion::create([
            'aptitude_area_id' => $area->id,
            'raw_score' => 7,
            'percentile_output' => '81st',
        ]);
    }
}
