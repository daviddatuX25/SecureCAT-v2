<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Course;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireSeasonApplicationsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);
    }

    public function test_expire_command_marks_pending_applications_as_dismissed_when_window_closed(): void
    {
        $course = Course::first();
        $season = Season::create([
            'academic_year' => '2024-2025',
            'semester' => '2',
            'is_active' => false,
            'application_start_date' => now()->subDays(60),
            'application_end_date' => now()->subDays(1),
        ]);
        $app = Application::create([
            'season_id' => $season->id,
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2004-01-01',
            'age' => 21,
            'sex' => 'male',
            'email' => 'test@example.com',
            'course_preference_1' => $course->id,
            'course_preference_2' => $course->id,
            'course_preference_3' => $course->id,
            'status' => 'pending',
            'submitted_at' => now()->subDays(10),
        ]);

        $this->artisan('seasons:expire-applications')
            ->assertSuccessful();

        $app->refresh();
        $this->assertSame('dismissed', $app->status);
        $this->assertSame('Application window closed', $app->rejection_reason);
    }
}
