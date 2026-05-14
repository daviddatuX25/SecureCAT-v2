<?php

namespace Tests\Feature\Grading;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\GradingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class GradingSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_includes_printed_flag_for_applicants(): void
    {
        $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_IN_PROGRESS]);
        $application = Application::factory()->create();
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $session->applicants()->attach($applicant->id, ['result_printed_at' => now()]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.grading.sessions.show', $session));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('applicants', 1)
            ->where('applicants.0.printed', true)
        );
    }

    public function test_show_printed_flag_is_false_when_not_printed(): void
    {
        $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_IN_PROGRESS]);
        $application = Application::factory()->create();
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $session->applicants()->attach($applicant->id, ['result_printed_at' => null]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.grading.sessions.show', $session));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('applicants', 1)
            ->where('applicants.0.printed', false)
        );
    }
}
