<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\Course;
use App\Models\GradingSession;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\ResultReleasedF2F;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReleasePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function actingAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    private function createSummary(string $status = 'draft'): ConsultationSummary
    {
        $course = Course::factory()->create(['is_active' => true]);
        $applicant = Applicant::factory()->create();

        return ConsultationSummary::factory()->create([
            'applicant_id' => $applicant->id,
            'status' => $status,
            'recommended_course_id' => $course->id,
        ]);
    }

    private function seedSummaries(int $count, string $status = 'draft'): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createSummary($status);
        }
    }

    public function test_online_mode_returns_summaries_prop(): void
    {
        SystemSetting::set('release_mode', 'online');
        $this->seedSummaries(3);
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->get('/admin/release');

        $response->assertInertia(
            fn ($assert) => $assert
                ->has('summaries')
                ->where('release_mode', 'online')
        );
    }

    public function test_f2f_mode_returns_summaries_prop(): void
    {
        SystemSetting::set('release_mode', 'f2f');
        $this->seedSummaries(3);
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->get('/admin/release');

        $response->assertInertia(
            fn ($assert) => $assert
                ->has('summaries')
                ->where('release_mode', 'f2f')
        );
    }

    public function test_both_mode_returns_online_and_f2f_summaries_props(): void
    {
        SystemSetting::set('release_mode', 'both');
        $this->seedSummaries(5);
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->get('/admin/release');

        $response->assertInertia(
            fn ($assert) => $assert
                ->has('summaries')
                ->where('release_mode', 'both')
        );
    }

    public function test_release_all_endpoint_releases_unreleased_summaries(): void
    {
        SystemSetting::set('release_mode', 'online');
        // Create summaries with proper setup (like ReleaseAllTest)
        $draft1 = $this->createSummary('draft');
        $draft2 = $this->createSummary('draft');
        $draft3 = $this->createSummary('draft');
        $released = $this->createSummary('released');

        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->post('/admin/release/summaries/release-all');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Should only release the 3 drafts, not the already-released one
        $this->assertEquals('released', $draft1->fresh()->status);
        $this->assertEquals('released', $draft2->fresh()->status);
        $this->assertEquals('released', $draft3->fresh()->status);
        $this->assertEquals('released', $released->fresh()->status); // stays released
    }

    public function test_release_all_returns_info_when_all_already_released(): void
    {
        SystemSetting::set('release_mode', 'online');
        $this->seedSummaries(2, 'released');
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->post('/admin/release/summaries/release-all');

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_release_all_rejected_in_f2f_mode(): void
    {
        SystemSetting::set('release_mode', 'f2f');
        $this->seedSummaries(2);
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->post('/admin/release/summaries/release-all');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_single_release_sends_correct_notification_per_context(): void
    {
        SystemSetting::set('release_mode', 'both');
        $summary = ConsultationSummary::factory()->create(['status' => 'draft']);
        $admin = $this->actingAdmin();

        Notification::fake();

        $response = $this->actingAs($admin)->post(
            "/admin/release/summaries/{$summary->id}/release",
            ['release_context' => 'f2f']
        );

        $response->assertRedirect();
        Notification::assertSentTo(
            $summary->applicant,
            ResultReleasedF2F::class
        );
    }

    public function test_index_includes_printed_flag_in_summaries(): void
    {
        SystemSetting::set('release_mode', 'online');
        $application = Application::factory()->create();
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_FINALIZED]);
        $session->applicants()->attach($applicant->id, ['result_printed_at' => now()]);

        ConsultationSummary::factory()->create([
            'applicant_id' => $applicant->id,
            'status' => 'draft',
        ]);

        $admin = $this->actingAdmin();
        $response = $this->actingAs($admin)->get('/admin/release');

        $response->assertInertia(
            fn ($assert) => $assert
                ->has('summaries.data', 1)
                ->where('summaries.data.0.printed', true)
        );

        $session->applicants()->updateExistingPivot($applicant->id, ['result_printed_at' => null]);

        $response = $this->actingAs($admin)->get('/admin/release');

        $response->assertInertia(
            fn ($assert) => $assert
                ->has('summaries.data', 1)
                ->where('summaries.data.0.printed', false)
        );
    }

    public function test_index_includes_grading_session_id_when_applicant_has_sessions(): void
    {
        SystemSetting::set('release_mode', 'online');
        $application = Application::factory()->create();
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_FINALIZED]);
        $session->applicants()->attach($applicant->id);

        ConsultationSummary::factory()->create([
            'applicant_id' => $applicant->id,
            'status' => 'draft',
        ]);

        $admin = $this->actingAdmin();
        $response = $this->actingAs($admin)->get('/admin/release');

        $response->assertInertia(
            fn ($assert) => $assert
                ->has('summaries.data', 1)
                ->where('summaries.data.0.grading_session_id', $session->id)
        );
    }

    public function test_index_grading_session_id_is_null_when_applicant_has_no_sessions(): void
    {
        SystemSetting::set('release_mode', 'online');
        $summary = $this->createSummary('draft');
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->get('/admin/release');

        $response->assertInertia(
            fn ($assert) => $assert
                ->has('summaries.data', 1)
                ->where('summaries.data.0.grading_session_id', null)
        );
    }

    public function test_index_includes_finalized_grading_sessions_prop(): void
    {
        SystemSetting::set('release_mode', 'online');
        $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_FINALIZED]);
        $this->createSummary('draft');
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->get('/admin/release');

        $response->assertInertia(
            fn ($assert) => $assert
                ->has('gradingSessions', 1)
                ->where('gradingSessions.0.id', $session->id)
                ->where('gradingSessions.0.label', 'Session #'.$session->id)
        );
    }
}
