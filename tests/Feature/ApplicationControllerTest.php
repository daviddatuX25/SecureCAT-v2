<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CourseSeeder::class);
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff')->first());

        return $user;
    }

    private function createApplicationWithAcademicYear(bool $windowOpen = true): Application
    {
        $course = Course::first();
        $start = $windowOpen ? now()->subDays(5)->toDateString() : now()->addDays(5)->toDateString();
        $end = $windowOpen ? now()->addDays(30)->toDateString() : now()->addDays(10)->toDateString();
        $academicYear = AcademicYear::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
            'application_start_date' => $start,
            'application_end_date' => $end,
        ]);

        return Application::create([
            'academic_year_id' => $academicYear->id,
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'birthdate' => '2005-01-15',
            'age' => 20,
            'sex' => 'female',
            'email' => 'jane@example.com',
            'course_preference_1' => $course->id,
            'course_preference_2' => $course->id,
            'course_preference_3' => $course->id,
            'status' => 'pending',
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function test_index_returns_statuses_with_full_pipeline_values(): void
    {
        $response = $this->actingAs($this->staff())->get(route('admin.applications.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Index')
            ->has('statuses', 11)
            ->where('statuses.0.value', 'pending')
            ->where('statuses.1.value', 'accepted')
            ->where('statuses.2.value', 'draft_scheduled')
            ->where('statuses.10.value', 'dismissed')
        );
    }

    public function test_show_passes_within_application_window_when_season_window_open(): void
    {
        $application = $this->createApplicationWithAcademicYear(true);

        $response = $this->actingAs($this->staff())->get(route('admin.applications.admin-show', $application));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Show')
            ->where('within_application_window', true)
            ->has('application_window_label')
        );
    }

    public function test_show_passes_within_application_window_false_when_season_window_closed(): void
    {
        $application = $this->createApplicationWithAcademicYear(false);

        $response = $this->actingAs($this->staff())->get(route('admin.applications.admin-show', $application));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Show')
            ->where('within_application_window', false)
        );
    }

    public function test_accept_from_pending_within_window_succeeds(): void
    {
        $application = $this->createApplicationWithAcademicYear(true);

        $response = $this->actingAs($this->staff())->put(route('admin.applications.accept', $application));

        $response->assertRedirect(route('admin.applications.admin-show', $application));
        $response->assertSessionHas('success');
        $application->refresh();
        $this->assertSame('accepted', $application->status);
    }

    public function test_accept_outside_window_returns_error(): void
    {
        $application = $this->createApplicationWithAcademicYear(false);

        $response = $this->actingAs($this->staff())->put(route('admin.applications.accept', $application));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $application->refresh();
        $this->assertSame('pending', $application->status);
    }

    public function test_dismiss_within_window_succeeds(): void
    {
        $application = $this->createApplicationWithAcademicYear(true);

        $response = $this->actingAs($this->staff())->put(route('admin.applications.dismiss', $application), [
            'reason' => 'Missing documents',
        ]);

        $response->assertRedirect(route('admin.applications.admin-show', $application));
        $response->assertSessionHas('success');
        $application->refresh();
        $this->assertSame('dismissed', $application->status);
        $this->assertSame('Missing documents', $application->rejection_reason);
    }

    public function test_dismiss_outside_window_returns_error(): void
    {
        $application = $this->createApplicationWithAcademicYear(false);

        $response = $this->actingAs($this->staff())->put(route('admin.applications.dismiss', $application), []);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $application->refresh();
        $this->assertSame('pending', $application->status);
    }

    public function test_accept_from_dismissed_within_window_succeeds(): void
    {
        $application = $this->createApplicationWithAcademicYear(true);
        $application->update(['status' => 'dismissed']);

        $response = $this->actingAs($this->staff())->put(route('admin.applications.accept', $application));

        $response->assertRedirect(route('admin.applications.admin-show', $application));
        $response->assertSessionHas('success');
        $application->refresh();
        $this->assertSame('accepted', $application->status);
    }

    public function test_reject_route_does_not_exist(): void
    {
        $application = $this->createApplicationWithAcademicYear(true);

        $response = $this->actingAs($this->staff())->put("/admin/applications/{$application->id}/reject", ['reason' => 'Test']);

        $response->assertStatus(404);
    }
}
