<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_application_stats(): void
    {
        $role = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => null,
        ]);

        $admin = User::factory()->create();
        $admin->roles()->attach($role);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('applicationStats')
                ->has('sessionStats')
                ->has('gradingStats')
            );
    }

    public function test_super_admin_sees_all_stat_groups(): void
    {
        $role = Role::query()->create([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'description' => null,
        ]);

        $superAdmin = User::factory()->create();
        $superAdmin->roles()->attach($role);

        $this->actingAs($superAdmin)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('applicationStats')
                ->has('sessionStats')
                ->has('gradingStats')
            );
    }

    public function test_proctor_sees_session_stats_not_application_stats(): void
    {
        $role = Role::query()->create([
            'name' => 'proctor',
            'display_name' => 'Proctor',
            'description' => null,
        ]);

        $proctor = User::factory()->create();
        $proctor->roles()->attach($role);

        $this->actingAs($proctor)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('applicationStats', [])
                ->has('sessionStats')
            );
    }

    public function test_proctor_does_not_see_grading_stats(): void
    {
        $role = Role::query()->create([
            'name' => 'proctor',
            'display_name' => 'Proctor',
            'description' => null,
        ]);

        $proctor = User::factory()->create();
        $proctor->roles()->attach($role);

        $this->actingAs($proctor)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gradingStats', [])
            );
    }

    public function test_test_administrator_sees_grading_stats(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'test_administrator',
            'display_name' => 'Test Administrator',
            'description' => 'Guidance office, inputs scores and releases consultations',
        ]);

        $ta = User::factory()->create();
        $ta->roles()->attach($role);

        $this->actingAs($ta)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('gradingStats')
            );
    }
}
