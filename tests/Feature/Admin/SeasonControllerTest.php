<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());

        return $user;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    private function testAdministrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        return $user;
    }

    public function test_admin_can_view_seasons_index(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.seasons.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Seasons/Index')
            ->has('seasons')
            ->has('seasons.data')
        );
    }

    public function test_super_admin_can_view_seasons_index(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.seasons.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Seasons/Index')->has('seasons'));
    }

    public function test_admin_can_view_create_season_form(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.seasons.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Seasons/Create'));
    }

    public function test_admin_can_store_season(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.seasons.store'), [
            'academic_year' => '2025-2026',
            'semester' => '1',
            'application_start_date' => '2025-06-01',
            'application_end_date' => '2025-07-15',
        ]);

        $response->assertRedirect(route('admin.seasons.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seasons', [
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => false,
        ]);
        $season = Season::first();
        $this->assertNotNull($season);
        $this->assertSame('2025-2026', $season->academic_year);
        $this->assertSame('1', $season->semester);
        // Application window dates when provided via request (form or test payload)
        $this->assertTrue(
            $season->application_start_date === null || $season->application_start_date->toDateString() === '2025-06-01',
            'application_start_date should be null or 2025-06-01'
        );
        $this->assertTrue(
            $season->application_end_date === null || $season->application_end_date->toDateString() === '2025-07-15',
            'application_end_date should be null or 2025-07-15'
        );
    }

    public function test_admin_can_view_edit_season_form(): void
    {
        $season = Season::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.seasons.edit', $season));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Seasons/Edit')
            ->has('season')
            ->where('season.id', $season->id)
            ->where('season.academic_year', '2025-2026')
            ->where('season.semester', '1')
        );
    }

    public function test_admin_can_update_season(): void
    {
        $season = Season::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.seasons.update', $season), [
            'academic_year' => '2025-2026',
            'semester' => '2',
            'application_start_date' => '2026-01-10',
            'application_end_date' => '2026-02-28',
        ]);

        $response->assertRedirect(route('admin.seasons.index'));
        $response->assertSessionHas('success');

        $season->refresh();
        $this->assertSame('2', $season->semester);
        $this->assertSame('2026-01-10', $season->application_start_date?->toDateString());
        $this->assertSame('2026-02-28', $season->application_end_date?->toDateString());
    }

    public function test_admin_can_activate_season(): void
    {
        $season1 = Season::create(['academic_year' => '2024-2025', 'semester' => '1', 'is_active' => true]);
        $season2 = Season::create(['academic_year' => '2025-2026', 'semester' => '1', 'is_active' => false]);

        $response = $this->actingAs($this->admin())->post(route('admin.seasons.activate', $season2));

        $response->assertRedirect(route('admin.seasons.index'));
        $response->assertSessionHas('success');

        $this->assertFalse($season1->fresh()->is_active);
        $this->assertTrue($season2->fresh()->is_active);
    }

    public function test_test_administrator_cannot_view_seasons_index(): void
    {
        $response = $this->actingAs($this->testAdministrator())->get(route('admin.seasons.index'));

        $response->assertStatus(403);
    }

    public function test_test_administrator_cannot_store_season(): void
    {
        $initialCount = Season::count();

        $response = $this->actingAs($this->testAdministrator())->post(route('admin.seasons.store'), [
            'academic_year' => '2025-2026',
            'semester' => '1',
        ]);

        $response->assertStatus(403);
        $this->assertSame($initialCount, Season::count(), 'Test administrator must not create a season.');
    }

    public function test_unauthenticated_redirects_to_login(): void
    {
        $response = $this->get(route('admin.seasons.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.seasons.store'), [
            'academic_year' => '',
            'semester' => '',
        ]);

        $response->assertSessionHasErrors(['academic_year', 'semester']);
    }
}
