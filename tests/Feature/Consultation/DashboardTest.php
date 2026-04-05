<?php

namespace Tests\Feature\Consultation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_consultation_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'test_administrator']);

        $this->actingAs($admin)
            ->get('/consultation')
            ->assertInertia()
            ->assertComponent('Consultation/Dashboard')
            ->assertHasProps([
                'applicants_pending',
                'applicants_released',
                'stats',
            ]);
    }

    public function test_stats_are_passed_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'test_administrator']);

        $stats = [
            'pending' => 5,
            'released' => 10,
            'total_with_scores' => 15,
        ];

        $this->actingAs($admin)
            ->get('/consultation')
            ->assertInertia('Consultation/Dashboard', [
                'stats' => $stats,
            ]);
    }
}