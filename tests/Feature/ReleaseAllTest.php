<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\Course;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseAllTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $activeYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        AcademicYear::query()->update(['is_active' => false]);
        $this->activeYear = AcademicYear::factory()->active()->create();
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    private function createSummaryWithApplicant(string $status = 'draft'): ConsultationSummary
    {
        $course = Course::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        return ConsultationSummary::factory()->create([
            'applicant_id' => $applicant->id,
            'status' => $status,
            'recommended_course_id' => $course->id,
        ]);
    }

    public function test_release_all_releases_unreleased_summaries(): void
    {
        SystemSetting::set('release_mode', 'online');
        $admin = $this->createAdmin();
        $summary1 = $this->createSummaryWithApplicant('draft');
        $summary2 = $this->createSummaryWithApplicant('pending');

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/release/summaries/release-all');

        $response->assertRedirect();
        $this->assertEquals('released', $summary1->fresh()->status);
        $this->assertEquals('released', $summary2->fresh()->status);
    }

    public function test_release_all_skips_already_released(): void
    {
        SystemSetting::set('release_mode', 'online');
        $admin = $this->createAdmin();
        $released = $this->createSummaryWithApplicant('released');
        $draft = $this->createSummaryWithApplicant('draft');

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/release/summaries/release-all');

        $response->assertRedirect();
        $this->assertEquals('released', $released->fresh()->status);
        $this->assertEquals('released', $draft->fresh()->status);
        $this->assertStringContainsString('1 result(s) released', $response->getSession()->get('success'));
    }

    public function test_release_all_returns_info_when_nothing_to_release(): void
    {
        SystemSetting::set('release_mode', 'online');
        $admin = $this->createAdmin();
        $this->createSummaryWithApplicant('released');

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/release/summaries/release-all');

        $response->assertRedirect();
        $this->assertEquals('All results have already been released.', $response->getSession()->get('info'));
    }

    public function test_release_all_blocked_in_f2f_mode(): void
    {
        SystemSetting::set('release_mode', 'f2f');
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/release/summaries/release-all');

        $response->assertRedirect();
        $this->assertStringContainsString('not available', $response->getSession()->get('error'));
    }

    public function test_release_sends_result_released_in_online_mode(): void
    {
        SystemSetting::set('release_mode', 'online');
        $admin = $this->createAdmin();
        $summary = $this->createSummaryWithApplicant('draft');

        $this->actingAs($admin, 'web')
            ->post("/admin/release/summaries/{$summary->id}/release");

        $this->assertEquals('released', $summary->fresh()->status);
        $notifications = $summary->applicant->notifications;
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('ResultReleased', $notifications->first()->type);
    }

    public function test_release_sends_f2f_notification_in_f2f_mode(): void
    {
        SystemSetting::set('release_mode', 'f2f');
        $admin = $this->createAdmin();
        $summary = $this->createSummaryWithApplicant('draft');

        $this->actingAs($admin, 'web')
            ->post("/admin/release/summaries/{$summary->id}/release");

        $this->assertEquals('released', $summary->fresh()->status);
        $notifications = $summary->applicant->notifications;
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('ResultReleasedF2F', $notifications->first()->type);
    }

    public function test_release_with_online_context_in_both_mode(): void
    {
        SystemSetting::set('release_mode', 'both');
        $admin = $this->createAdmin();
        $summary = $this->createSummaryWithApplicant('draft');

        $this->actingAs($admin, 'web')
            ->post("/admin/release/summaries/{$summary->id}/release", [
                'release_context' => 'online',
            ]);

        $notifications = $summary->applicant->notifications;
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('ResultReleased', $notifications->first()->type);
        $this->assertStringNotContainsString('F2F', $notifications->first()->type);
    }

    public function test_release_with_f2f_context_in_both_mode(): void
    {
        SystemSetting::set('release_mode', 'both');
        $admin = $this->createAdmin();
        $summary = $this->createSummaryWithApplicant('draft');

        $this->actingAs($admin, 'web')
            ->post("/admin/release/summaries/{$summary->id}/release", [
                'release_context' => 'f2f',
            ]);

        $notifications = $summary->applicant->notifications;
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('ResultReleasedF2F', $notifications->first()->type);
    }
}
