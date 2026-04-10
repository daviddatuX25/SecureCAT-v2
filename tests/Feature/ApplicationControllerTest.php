<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Course;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff')->first());

        return $user;
    }

    private function createApplicationWithSeason(bool $windowOpen = true): Application
    {
        $course = Course::first();
        $start = $windowOpen ? now()->subDays(5)->toDateString() : now()->addDays(5)->toDateString();
        $end = $windowOpen ? now()->addDays(30)->toDateString() : now()->addDays(10)->toDateString();
        $season = Season::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
            'application_start_date' => $start,
            'application_end_date' => $end,
        ]);

        return Application::create([
            'season_id' => $season->id,
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

    public function test_index_returns_statuses_including_dismissed(): void
    {
        $response = $this->actingAs($this->staff())->get(route('applications.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Index')
            ->has('statuses')
            ->where('statuses.0.value', 'pending')
            ->where('statuses.1.value', 'accepted')
            ->where('statuses.2.value', 'dismissed')
        );
    }

    public function test_show_passes_within_application_window_when_season_window_open(): void
    {
        $application = $this->createApplicationWithSeason(true);

        $response = $this->actingAs($this->staff())->get(route('applications.show', $application));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Show')
            ->where('within_application_window', true)
            ->has('application_window_label')
        );
    }

    public function test_show_passes_within_application_window_false_when_season_window_closed(): void
    {
        $application = $this->createApplicationWithSeason(false);

        $response = $this->actingAs($this->staff())->get(route('applications.show', $application));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Show')
            ->where('within_application_window', false)
        );
    }

    public function test_accept_from_pending_within_window_succeeds(): void
    {
        $application = $this->createApplicationWithSeason(true);

        $response = $this->actingAs($this->staff())->put(route('applications.accept', $application));

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHas('success');
        $application->refresh();
        $this->assertSame('accepted', $application->status);
    }

    public function test_accept_outside_window_returns_error(): void
    {
        $application = $this->createApplicationWithSeason(false);

        $response = $this->actingAs($this->staff())->put(route('applications.accept', $application));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $application->refresh();
        $this->assertSame('pending', $application->status);
    }

    public function test_dismiss_within_window_succeeds(): void
    {
        $application = $this->createApplicationWithSeason(true);

        $response = $this->actingAs($this->staff())->put(route('applications.dismiss', $application), [
            'reason' => 'Missing documents',
        ]);

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHas('success');
        $application->refresh();
        $this->assertSame('dismissed', $application->status);
        $this->assertSame('Missing documents', $application->rejection_reason);
    }

    public function test_dismiss_outside_window_returns_error(): void
    {
        $application = $this->createApplicationWithSeason(false);

        $response = $this->actingAs($this->staff())->put(route('applications.dismiss', $application), []);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $application->refresh();
        $this->assertSame('pending', $application->status);
    }

    public function test_set_incomplete_documents_within_window_succeeds(): void
    {
        $application = $this->createApplicationWithSeason(true);

        $response = $this->actingAs($this->staff())->put(route('applications.set-incomplete-documents', $application));

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHas('success');
        $application->refresh();
        $this->assertSame('incomplete_documents', $application->status);
    }

    public function test_accept_from_dismissed_within_window_succeeds(): void
    {
        $application = $this->createApplicationWithSeason(true);
        $application->update(['status' => 'dismissed']);

        $response = $this->actingAs($this->staff())->put(route('applications.accept', $application));

        $response->assertRedirect(route('applications.show', $application));
        $response->assertSessionHas('success');
        $application->refresh();
        $this->assertSame('accepted', $application->status);
    }

    public function test_reject_route_does_not_exist(): void
    {
        $application = $this->createApplicationWithSeason(true);

        $response = $this->actingAs($this->staff())->put("/applications/{$application->id}/reject", ['reason' => 'Test']);

        $response->assertStatus(404);
    }
}
