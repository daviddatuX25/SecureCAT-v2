<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_and_includes_dashboard_payload_for_admin_roles(): void
    {
        $role = Role::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => null,
        ]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('applicationStats')
            ->has('sessionStats')
            ->has('gradingStats')
        );
    }
}

