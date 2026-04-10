<?php

namespace Tests\Feature\Admin;

use App\Models\AptitudeArea;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AptitudeAreaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\AptitudeAreaSeeder::class);
    }

    private function testAdministrator(): User
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
        $response = $this->actingAs($this->testAdministrator())
            ->get(route('admin.aptitude-areas.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/AptitudeAreas/Index')
            ->has('aptitude_areas')
        );
    }

    public function test_test_administrator_can_create_aptitude_area(): void
    {
        $response = $this->actingAs($this->testAdministrator())
            ->post(route('admin.aptitude-areas.store'), [
                'name' => 'Critical Thinking',
                'code' => 'CT',
                'max_items' => 30,
                'display_order' => 7,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('aptitude_areas', [
            'code' => 'CT',
            'name' => 'Critical Thinking',
            'max_items' => 30,
        ]);
    }

    public function test_test_administrator_can_update_aptitude_area(): void
    {
        $area = AptitudeArea::first();

        $response = $this->actingAs($this->testAdministrator())
            ->put(route('admin.aptitude-areas.update', $area), [
                'name' => 'Updated Name',
                'code' => $area->code,
                'max_items' => 20,
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
}
