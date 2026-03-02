<?php

namespace Tests\Feature\Portal;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);
    }

    public function test_dashboard_redirects_when_not_authenticated(): void
    {
        $response = $this->get(route('portal.dashboard'));

        $response->assertStatus(302);
        $this->assertStringContainsString('login', $response->headers->get('Location', ''));
    }

    public function test_dashboard_shows_status_tracker_with_accepted_application(): void
    {
        $season = Season::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
        ]);

        $course = Course::first();
        $application = Application::create([
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
            'status' => 'accepted',
            'processed_at' => now(),
            'submitted_at' => now()->subDay(),
        ]);

        $applicant = Applicant::factory()->create([
            'application_id' => $application->id,
            'email' => $application->email,
            'password' => bcrypt('password'),
            'setup_token' => null,
            'setup_token_expires_at' => null,
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->get(route('portal.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/Dashboard')
            ->has('status_tracker')
            ->has('applicant')
            ->where('applicant.reference_number', $application->reference_number)
        );

        $statusTracker = $response->original->getData()['page']['props']['status_tracker'];
        $this->assertIsArray($statusTracker);

        $admittedStage = collect($statusTracker)->firstWhere('stage', 'Successfully admitted');
        $this->assertNotNull($admittedStage, 'Status tracker should include Successfully admitted stage');
        $this->assertTrue($admittedStage['completed'], 'Successfully admitted should be completed when application is accepted');
    }

    public function test_dashboard_returns_exam_schedule_null_when_not_assigned(): void
    {
        $season = Season::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
        ]);

        $course = Course::first();
        $application = Application::create([
            'season_id' => $season->id,
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'birthdate' => '2005-01-15',
            'age' => 20,
            'sex' => 'female',
            'email' => 'jane2@example.com',
            'course_preference_1' => $course->id,
            'course_preference_2' => $course->id,
            'course_preference_3' => $course->id,
            'status' => 'accepted',
            'processed_at' => now(),
            'submitted_at' => now()->subDay(),
        ]);

        $applicant = Applicant::factory()->create([
            'application_id' => $application->id,
            'email' => $application->email,
            'password' => bcrypt('password'),
            'setup_token' => null,
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->get(route('portal.dashboard'));

        $response->assertStatus(200);
        $props = $response->original->getData()['page']['props'];
        $this->assertNull($props['exam_schedule']);
        $this->assertNull($props['score_release']);
    }
}
