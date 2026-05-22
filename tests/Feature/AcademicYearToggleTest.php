<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    public function test_deactivate_sets_is_active_to_false(): void
    {
        $ay = AcademicYear::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->post("/admin/academic-years/{$ay->id}/deactivate")
            ->assertRedirect();

        $this->assertFalse($ay->fresh()->is_active);
    }

    public function test_deactivate_returns_403_for_staff(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $ay = AcademicYear::factory()->create(['is_active' => true]);

        $this->actingAs($staff)
            ->post("/admin/academic-years/{$ay->id}/deactivate")
            ->assertForbidden();
    }
}
