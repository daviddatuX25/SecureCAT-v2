<?php

namespace Tests\Feature\Admin;

use App\Models\AptitudeArea;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AptitudeAreaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AptitudeAreaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(AptitudeAreaSeeder::class);
    }

    private function test_administrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        return $user;
    }

    private function registrarAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'registrar_administrator')->first());

        return $user;
    }

    public function test_registrar_admin_cannot_view_aptitude_areas_index(): void
    {
        $response = $this->actingAs($this->registrarAdmin())
            ->get(route('admin.aptitude-areas.index'));

        $response->assertForbidden();
    }

    public function test_registrar_admin_cannot_create_aptitude_area(): void
    {
        $response = $this->actingAs($this->registrarAdmin())
            ->post(route('admin.aptitude-areas.store'), [
                'name' => 'New Area',
                'code' => 'NA2',
                'max_items' => 30,
                'display_order' => 10,
                'is_active' => true,
            ]);

        $response->assertForbidden();
    }

    public function test_test_administrator_can_view_aptitude_areas_index(): void
    {
        $response = $this->actingAs($this->test_administrator())
            ->get(route('admin.aptitude-areas.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/AptitudeAreas/Index')
            ->has('aptitude_areas')
        );
    }

    public function test_test_administrator_can_create_aptitude_area(): void
    {
        $response = $this->actingAs($this->test_administrator())
            ->post(route('admin.aptitude-areas.store'), [
                'name' => 'Critical Thinking',
                'code' => 'CT',
                'max_items' => 30,
                'scoring_method' => 'formula',
                'display_order' => 7,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('aptitude_areas', [
            'code' => 'CT',
            'name' => 'Critical Thinking',
            'max_items' => 30,
            'scoring_method' => 'formula',
        ]);
    }

    public function test_test_administrator_can_update_aptitude_area(): void
    {
        $area = AptitudeArea::first();

        $response = $this->actingAs($this->test_administrator())
            ->put(route('admin.aptitude-areas.update', $area), [
                'name' => 'Updated Name',
                'code' => $area->code,
                'max_items' => 20,
                'scoring_method' => $area->scoring_method ?? 'formula',
                'display_order' => 1,
                'is_active' => false,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $response->assertSessionHas('success');
        $area->refresh();
        $this->assertSame('Updated Name', $area->name);
        $this->assertSame(20, $area->max_items);
        $this->assertFalse($area->is_active);
    }

    public function test_test_administrator_can_create_aptitude_area_with_conversion_table(): void
    {
        $response = $this->actingAs($this->test_administrator())
            ->post(route('admin.aptitude-areas.store'), [
                'name' => 'Logic Table',
                'code' => 'LT',
                'max_items' => 3,
                'scoring_method' => 'conversion_table',
                'conversion_table' => [
                    ['raw_score' => 0, 'percentile_output' => '10th'],
                    ['raw_score' => 1, 'percentile_output' => '50th'],
                    ['raw_score' => 2, 'percentile_output' => '90th'],
                ],
                'display_order' => 5,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $this->assertDatabaseHas('aptitude_areas', [
            'code' => 'LT',
            'scoring_method' => 'conversion_table',
        ]);
        $area = AptitudeArea::where('code', 'LT')->first();
        $this->assertCount(3, $area->percentileConversions);
    }

    public function test_test_administrator_switching_to_formula_deletes_conversion_table(): void
    {
        $area = AptitudeArea::factory()->create([
            'scoring_method' => 'conversion_table',
        ]);
        $area->percentileConversions()->createMany([
            ['raw_score' => 0, 'percentile_output' => '10th'],
            ['raw_score' => 1, 'percentile_output' => '90th'],
        ]);

        $this->assertCount(2, $area->percentileConversions);

        $response = $this->actingAs($this->test_administrator())
            ->put(route('admin.aptitude-areas.update', $area), [
                'name' => 'Updated Logic Table',
                'code' => $area->code,
                'max_items' => 25,
                'scoring_method' => 'formula',
                'formula' => 'x * 4',
                'display_order' => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $area->refresh();
        $this->assertSame('formula', $area->scoring_method);
        $this->assertSame('x * 4', $area->formula);
        $this->assertCount(0, $area->percentileConversions);
    }
}
