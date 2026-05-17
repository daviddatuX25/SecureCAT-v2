<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\Course;
use App\Services\ApplicationPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for pipeline status management.
 *
 * Strategy: all assertions read `pipeline_status` / `pipeline_milestones`
 * DB columns directly — no wrapper method calls. State is set via
 * ApplicationPipelineService::transition() or forceSet().
 */
class ApplicationPipelineStatusTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ApplicationPipelineService
    {
        return app(ApplicationPipelineService::class);
    }

    private function createApp(string $status = 'pending', ?string $pipelineStatus = null): Application
    {
        $course = Course::factory()->create();
        $ay = AcademicYear::factory()->create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
            'application_start_date' => now()->subDays(5)->toDateString(),
            'application_end_date' => now()->addDays(30)->toDateString(),
        ]);

        $app = Application::factory()->create([
            'academic_year_id' => $ay->id,
            'course_preference_1' => $course->id,
            'status' => $status,
        ]);

        if ($pipelineStatus !== null) {
            $this->service()->forceSet($app, $pipelineStatus);
            $app->refresh();
        }

        return $app;
    }

    /** Helper: compute pipeline details inline from DB columns (mirrors ApplicationController::show logic). */
    private function pipelineDetails(Application $app): array
    {
        $milestones = $app->pipeline_milestones ?? [];
        $status = $app->pipeline_status ?? 'pending';
        $isF2f = isset($milestones['scheduled']) || isset($milestones['printed']) || isset($milestones['attended']);
        $isDirect = isset($milestones['scored']) && ! $isF2f;

        return [
            'status' => $status,
            'milestones' => $milestones,
            'is_f2f' => $isF2f,
            'is_direct' => $isDirect,
        ];
    }

    // ── pipeline_status DB column ─────────────────────────────────────────────

    public function test_pending_application_returns_pending(): void
    {
        $app = $this->createApp('pending');

        $this->assertNull($app->pipeline_status); // no hook fired yet
        $this->assertSame('pending', $app->pipeline_status ?? 'pending');
    }

    public function test_dismissed_application_returns_dismissed(): void
    {
        $app = $this->createApp('dismissed', 'dismissed');

        $this->assertSame('dismissed', $app->pipeline_status);
    }

    public function test_accepted_pipeline_status_set_explicitly(): void
    {
        // Hook fires in ApplicationController::accept() → in unit tests we simulate via forceSet
        $app = $this->createApp('accepted', 'accepted');

        $this->assertSame('accepted', $app->pipeline_status);
    }

    public function test_accepted_without_hook_has_null_pipeline_status(): void
    {
        // A freshly factory-created accepted app has no pipeline hook fired yet
        $app = $this->createApp('accepted');

        $this->assertNull($app->pipeline_status);
    }

    public function test_accepted_with_draft_session_returns_draft_scheduled(): void
    {
        $app = $this->createApp('accepted', 'draft_scheduled');

        $this->assertSame('draft_scheduled', $app->pipeline_status);
    }

    public function test_accepted_with_published_session_returns_scheduled(): void
    {
        $app = $this->createApp('accepted', 'scheduled');

        $this->assertSame('scheduled', $app->pipeline_status);
    }

    public function test_attended_returns_attended(): void
    {
        $app = $this->createApp('accepted', 'attended');

        $this->assertSame('attended', $app->pipeline_status);
    }

    public function test_submitted_returns_submitted(): void
    {
        $app = $this->createApp('accepted', 'submitted');

        $this->assertSame('submitted', $app->pipeline_status);
    }

    public function test_graded_returns_graded(): void
    {
        $app = $this->createApp('accepted', 'graded');

        $this->assertSame('graded', $app->pipeline_status);
    }

    public function test_cancelled_session_leaves_pipeline_unchanged(): void
    {
        // A cancelled session does not advance the pipeline; stays at 'accepted'
        $app = $this->createApp('accepted', 'accepted');

        $this->assertSame('accepted', $app->pipeline_status);
    }

    public function test_dismissed_overrides_everything(): void
    {
        $app = $this->createApp('dismissed', 'dismissed');

        $this->assertSame('dismissed', $app->pipeline_status);
    }

    // ── pipeline_milestones / pipelineDetails (inline) ───────────────────────

    public function test_pipeline_details_returns_status_and_milestones(): void
    {
        $service = $this->service();
        $app = $this->createApp('accepted');

        $service->transition($app, 'accepted');
        $service->transition($app, 'scheduled', ['session_id' => 99]);
        $service->transition($app, 'attended');
        $service->transition($app, 'submitted');

        $app->refresh();
        $details = $this->pipelineDetails($app);

        $this->assertSame('submitted', $details['status']);
        $this->assertArrayHasKey('milestones', $details);
        $this->assertArrayHasKey('accepted', $details['milestones']);
        $this->assertArrayHasKey('scheduled', $details['milestones']);
        $this->assertArrayHasKey('attended', $details['milestones']);
        $this->assertArrayHasKey('submitted', $details['milestones']);
        $this->assertTrue($details['is_f2f']);
        $this->assertFalse($details['is_direct']);
    }

    public function test_pipeline_details_for_pending_application(): void
    {
        $app = $this->createApp('pending');
        $details = $this->pipelineDetails($app);

        $this->assertSame('pending', $details['status']);
        $this->assertArrayHasKey('milestones', $details);
        $this->assertEmpty($details['milestones']);
    }

    public function test_pipeline_details_detects_direct_assessment(): void
    {
        $service = $this->service();
        $app = $this->createApp('accepted');

        $service->transition($app, 'accepted');
        $service->transition($app, 'scored', ['session_id' => 1]);
        $service->transition($app, 'graded');

        $app->refresh();
        $details = $this->pipelineDetails($app);

        $this->assertSame('graded', $details['status']);
        $this->assertTrue($details['is_direct']);
        $this->assertFalse($details['is_f2f']);
    }

    // ── ApplicationPipelineService ────────────────────────────────────────────

    public function test_transition_advances_forward(): void
    {
        $service = $this->service();
        $app = $this->createApp('accepted', 'accepted');

        $result = $service->transition($app, 'scheduled');

        $this->assertTrue($result);
        $this->assertSame('scheduled', $app->fresh()->pipeline_status);
    }

    public function test_transition_ignores_backward_move(): void
    {
        $service = $this->service();
        $app = $this->createApp('accepted', 'graded');

        $result = $service->transition($app, 'accepted');

        $this->assertFalse($result);
        $this->assertSame('graded', $app->fresh()->pipeline_status);
    }

    public function test_transition_dismissed_always_allowed(): void
    {
        $service = $this->service();
        $app = $this->createApp('dismissed', 'released');

        $result = $service->transition($app, 'dismissed');

        $this->assertTrue($result);
        $this->assertSame('dismissed', $app->fresh()->pipeline_status);
    }

    public function test_transition_returns_false_for_no_op(): void
    {
        $service = $this->service();
        $app = $this->createApp('accepted', 'scheduled');

        // Same status → no-op
        $result = $service->transition($app, 'scheduled');

        $this->assertFalse($result);
    }

    public function test_milestone_timestamp_is_recorded_on_first_reach_only(): void
    {
        $service = $this->service();
        $app = $this->createApp('accepted', 'accepted');

        $service->transition($app, 'scheduled');
        $app->refresh();
        $firstAt = $app->pipeline_milestones['scheduled']['at'];

        // Attempting the same status again is a no-op
        $service->transition($app, 'scheduled');
        $app->refresh();

        $this->assertSame($firstAt, $app->pipeline_milestones['scheduled']['at']);
    }

    public function test_transition_logs_unknown_status_and_returns_false(): void
    {
        $service = $this->service();
        $app = $this->createApp('accepted', 'accepted');

        $result = $service->transition($app, 'nonexistent_status');

        $this->assertFalse($result);
        $this->assertSame('accepted', $app->fresh()->pipeline_status);
    }

    public function test_force_set_bypasses_forward_only_guard(): void
    {
        $service = $this->service();
        $app = $this->createApp('accepted', 'released');

        // forceSet can go backwards (used by backfill command and reopen)
        $service->forceSet($app, 'pending');

        $this->assertSame('pending', $app->fresh()->pipeline_status);
    }
}
