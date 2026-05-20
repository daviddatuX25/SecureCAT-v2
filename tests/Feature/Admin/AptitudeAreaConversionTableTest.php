<?php

namespace Tests\Feature\Admin;

use App\Models\AptitudeArea;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AptitudeAreaConversionTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function testAdministrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        return $user;
    }

    public function test_create_aptitude_area_with_conversion_table(): void
    {
        $response = $this->actingAs($this->testAdministrator())
            ->post(route('admin.aptitude-areas.store'), [
                'name' => 'Spatial Awareness',
                'code' => 'SAC',
                'max_items' => 25,
                'scoring_method' => 'conversion_table',
                'conversion_table' => [
                    ['raw_score' => 0, 'percentile_output' => 'N/A'],
                    ['raw_score' => 10, 'percentile_output' => '50th'],
                    ['raw_score' => 25, 'percentile_output' => '99+'],
                ],
                'display_order' => 0,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aptitude_areas', [
            'code' => 'SAC',
            'scoring_method' => 'conversion_table',
        ]);

        $area = AptitudeArea::where('code', 'SAC')->first();
        $this->assertDatabaseCount('percentile_conversions', 3);
        $this->assertDatabaseHas('percentile_conversions', [
            'aptitude_area_id' => $area->id,
            'raw_score' => 10,
            'percentile_output' => '50th',
        ]);
    }

    public function test_create_aptitude_area_with_formula_stores_no_conversions(): void
    {
        $response = $this->actingAs($this->testAdministrator())
            ->post(route('admin.aptitude-areas.store'), [
                'name' => 'Numerical Ability',
                'code' => 'NAC',
                'max_items' => 25,
                'formula' => '(x / max_items) * 100',
                'scoring_method' => 'formula',
                'display_order' => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('percentile_conversions', 0);
    }

    public function test_update_aptitude_area_switches_to_conversion_table(): void
    {
        $area = AptitudeArea::factory()->create([
            'formula' => '(x / max_items) * 100',
            'scoring_method' => 'formula',
        ]);

        $response = $this->actingAs($this->testAdministrator())
            ->put(route('admin.aptitude-areas.update', $area), [
                'name' => $area->name,
                'code' => $area->code,
                'max_items' => $area->max_items,
                'scoring_method' => 'conversion_table',
                'conversion_table' => [
                    ['raw_score' => 5, 'percentile_output' => '60th'],
                ],
                'display_order' => $area->display_order,
                'is_active' => $area->is_active,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $response->assertSessionHasNoErrors();
        $area->refresh();
        $this->assertSame('conversion_table', $area->scoring_method);
        $this->assertDatabaseHas('percentile_conversions', [
            'aptitude_area_id' => $area->id,
            'raw_score' => 5,
            'percentile_output' => '60th',
        ]);
    }

    public function test_edit_page_returns_conversion_table_data(): void
    {
        $area = AptitudeArea::factory()->create(['scoring_method' => 'conversion_table']);
        $area->percentileConversions()->create(['raw_score' => 10, 'percentile_output' => '85th']);

        $response = $this->actingAs($this->testAdministrator())
            ->get(route('admin.aptitude-areas.edit', $area));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/AptitudeAreas/Edit')
            ->where('aptitude_area.scoring_method', 'conversion_table')
            ->where('aptitude_area.percentile_conversions', fn ($rows) => count($rows) === 1)
        );
    }

    public function test_index_page_returns_scoring_method(): void
    {
        AptitudeArea::factory()->create(['scoring_method' => 'conversion_table', 'code' => 'SAD']);

        $response = $this->actingAs($this->testAdministrator())
            ->get(route('admin.aptitude-areas.index'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $areas = $page->toArray()['props']['aptitude_areas'];
            $area = collect($areas)->firstWhere('code', 'SAD');
            $this->assertSame('conversion_table', $area['scoring_method']);
        });
    }
}
