<?php

namespace Tests\Feature\Admin;

use App\Models\AptitudeArea;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsConversionTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    public function test_can_enable_auto_compute_when_all_formula_areas_have_formula(): void
    {
        AptitudeArea::factory()->create([
            'is_active' => true,
            'scoring_method' => 'formula',
            'formula' => '(x / max_items) * 100',
        ]);

        $response = $this->actingAs($this->superAdmin())->put('/admin/settings', [
            'enable_normalized_scores' => true,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_can_enable_auto_compute_when_all_conversion_table_areas_have_rows(): void
    {
        $area = AptitudeArea::factory()->create([
            'is_active' => true,
            'scoring_method' => 'conversion_table',
            'formula' => null,
        ]);
        $area->percentileConversions()->create(['raw_score' => 0, 'percentile_output' => 'N/A']);

        $response = $this->actingAs($this->superAdmin())->put('/admin/settings', [
            'enable_normalized_scores' => true,
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_cannot_enable_auto_compute_when_formula_area_missing_formula(): void
    {
        AptitudeArea::factory()->create([
            'is_active' => true,
            'scoring_method' => 'formula',
            'formula' => null,
        ]);

        $response = $this->actingAs($this->superAdmin())->put('/admin/settings', [
            'enable_normalized_scores' => true,
        ]);

        $response->assertSessionHasErrors(['enable_normalized_scores']);
    }

    public function test_cannot_enable_auto_compute_when_conversion_table_area_has_no_rows(): void
    {
        AptitudeArea::factory()->create([
            'is_active' => true,
            'scoring_method' => 'conversion_table',
            'formula' => null,
        ]);

        $response = $this->actingAs($this->superAdmin())->put('/admin/settings', [
            'enable_normalized_scores' => true,
        ]);

        $response->assertSessionHasErrors(['enable_normalized_scores']);
    }

    public function test_mixed_areas_with_proper_config_pass(): void
    {
        $formulaArea = AptitudeArea::factory()->create([
            'is_active' => true,
            'scoring_method' => 'formula',
            'formula' => '(x / max_items) * 100',
        ]);
        $tableArea = AptitudeArea::factory()->create([
            'is_active' => true,
            'scoring_method' => 'conversion_table',
            'formula' => null,
        ]);
        $tableArea->percentileConversions()->create(['raw_score' => 0, 'percentile_output' => 'N/A']);

        $response = $this->actingAs($this->superAdmin())->put('/admin/settings', [
            'enable_normalized_scores' => true,
        ]);

        $response->assertSessionHasNoErrors();
    }
}
