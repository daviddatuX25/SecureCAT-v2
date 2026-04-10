<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkApplicationActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    public function test_bulk_accept_updates_pending_applications(): void
    {
        $ay = AcademicYear::factory()->create(['is_active' => true, 'application_start_date' => now()->subDay(), 'application_end_date' => now()->addDay()]);
        $a1 = Application::factory()->create(['status' => 'pending', 'academic_year_id' => $ay->id]);
        $a2 = Application::factory()->create(['status' => 'pending', 'academic_year_id' => $ay->id]);

        $this->actingAs($this->admin)
            ->post('/applications/bulk-accept', ['ids' => [$a1->id, $a2->id]])
            ->assertRedirect();

        $this->assertSame('accepted', $a1->fresh()->status);
        $this->assertSame('accepted', $a2->fresh()->status);
    }

    public function test_bulk_accept_skips_non_pending(): void
    {
        $ay = AcademicYear::factory()->create(['is_active' => true, 'application_start_date' => now()->subDay(), 'application_end_date' => now()->addDay()]);
        $pending = Application::factory()->create(['status' => 'pending', 'academic_year_id' => $ay->id]);
        $dismissed = Application::factory()->create(['status' => 'dismissed', 'academic_year_id' => $ay->id]);

        $this->actingAs($this->admin)
            ->post('/applications/bulk-accept', ['ids' => [$pending->id, $dismissed->id]])
            ->assertRedirect();

        $this->assertSame('accepted', $pending->fresh()->status);
        $this->assertSame('dismissed', $dismissed->fresh()->status);
    }

    public function test_bulk_dismiss_updates_pending_applications(): void
    {
        $ay = AcademicYear::factory()->create(['is_active' => true]);
        $a1 = Application::factory()->create(['status' => 'pending', 'academic_year_id' => $ay->id]);
        $a2 = Application::factory()->create(['status' => 'pending', 'academic_year_id' => $ay->id]);

        $this->actingAs($this->admin)
            ->post('/applications/bulk-dismiss', ['ids' => [$a1->id, $a2->id]])
            ->assertRedirect();

        $this->assertSame('dismissed', $a1->fresh()->status);
        $this->assertSame('dismissed', $a2->fresh()->status);
    }

    public function test_reopen_sets_dismissed_application_to_pending(): void
    {
        $ay = AcademicYear::factory()->create(['is_active' => true, 'application_start_date' => now()->subDay(), 'application_end_date' => now()->addDay()]);
        $application = Application::factory()->create(['status' => 'dismissed', 'academic_year_id' => $ay->id]);

        $this->actingAs($this->admin)
            ->put("/applications/{$application->id}/reopen")
            ->assertRedirect();

        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_reopen_fails_when_application_window_closed(): void
    {
        $ay = AcademicYear::factory()->create(['is_active' => true, 'application_end_date' => now()->subDay()]);
        $application = Application::factory()->create(['status' => 'dismissed', 'academic_year_id' => $ay->id]);

        $this->actingAs($this->admin)
            ->put("/applications/{$application->id}/reopen")
            ->assertSessionHasErrors('error');

        $this->assertSame('dismissed', $application->fresh()->status);
    }

    public function test_destroy_deletes_application(): void
    {
        $application = Application::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin)
            ->delete("/applications/{$application->id}")
            ->assertRedirect('/applications');

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }

    public function test_staff_cannot_delete_application(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $application = Application::factory()->create(['status' => 'pending']);

        $this->actingAs($staff)
            ->delete("/applications/{$application->id}")
            ->assertForbidden();
    }
}