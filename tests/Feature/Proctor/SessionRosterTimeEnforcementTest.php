<?php

namespace Tests\Feature\Proctor;

use App\Models\Applicant;
use App\Models\ExamSession;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionRosterTimeEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(string $date, string $start, ?string $end): ExamSession
    {
        return ExamSession::factory()->create([
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => ExamSession::STATUS_PUBLISHED,
        ]);
    }

    public function test_is_within_exam_window_during_window(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertTrue($session->isWithinExamWindow());
    }

    public function test_is_not_within_exam_window_before_start(): void
    {
        Carbon::setTestNow('2026-05-01 08:59:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertFalse($session->isWithinExamWindow());
    }

    public function test_is_not_within_exam_window_after_end(): void
    {
        Carbon::setTestNow('2026-05-01 12:01:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertFalse($session->isWithinExamWindow());
    }

    public function test_is_past_end_time_after_end(): void
    {
        Carbon::setTestNow('2026-05-01 12:01:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertTrue($session->isPastEndTime());
    }

    public function test_is_not_past_end_time_during_window(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertFalse($session->isPastEndTime());
    }

    public function test_is_not_past_end_time_when_no_end_time(): void
    {
        Carbon::setTestNow('2026-05-01 23:00:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', null);
        $this->assertFalse($session->isPastEndTime());
    }

    public function test_show_passes_time_flags_to_inertia(): void
    {
        $this->seed(RoleSeeder::class);

        $proctor = User::factory()->create();
        $proctor->roles()->attach(Role::where('name', 'proctor')->first());

        Carbon::setTestNow('2026-05-01 10:00:00');

        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $session->update(['status' => ExamSession::STATUS_PUBLISHED]);
        $session->proctors()->attach($proctor->id);

        $response = $this->actingAs($proctor)
            ->get("/proctor/sessions/{$session->id}");

        $response->assertInertia(fn ($page) => $page
            ->has('session.is_within_window')
            ->has('session.is_past_end')
            ->where('session.is_within_window', true)
            ->where('session.is_past_end', false)
        );
    }

    public function test_attendance_blocked_when_session_published(): void
    {
        $this->seed(RoleSeeder::class);

        $proctor = User::factory()->create();
        $proctor->roles()->attach(Role::where('name', 'proctor')->first());

        Carbon::setTestNow('2026-05-01 10:00:00'); // within window

        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        // status is already PUBLISHED from makeSession
        $session->proctors()->attach($proctor->id);

        $applicant = Applicant::factory()->create();
        $session->applicants()->attach($applicant->id, [
            'attendance_status' => 'pending',
            'submission_status' => 'pending',
        ]);

        $response = $this->actingAs($proctor)->postJson("/proctor/sessions/{$session->id}/attendance", [
            'applicant_id' => $applicant->id,
            'status' => 'present',
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseMissing('exam_session_applicant', [
            'attendance_status' => 'present',
        ]);
    }

    public function test_attendance_blocked_after_end_time(): void
    {
        $this->seed(RoleSeeder::class);

        $proctor = User::factory()->create();
        $proctor->roles()->attach(Role::where('name', 'proctor')->first());

        Carbon::setTestNow('2026-05-01 13:00:00'); // past 12:00 end

        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
        $session->proctors()->attach($proctor->id);

        $applicant = Applicant::factory()->create();
        $session->applicants()->attach($applicant->id, [
            'attendance_status' => 'pending',
            'submission_status' => 'pending',
        ]);

        $response = $this->actingAs($proctor)->postJson("/proctor/sessions/{$session->id}/attendance", [
            'applicant_id' => $applicant->id,
            'status' => 'present',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exam_session_applicant', [
            'attendance_status' => 'present',
        ]);
    }

    public function test_submission_blocked_after_end_time(): void
    {
        $this->seed(RoleSeeder::class);

        $proctor = User::factory()->create();
        $proctor->roles()->attach(Role::where('name', 'proctor')->first());

        Carbon::setTestNow('2026-05-01 13:00:00'); // past 12:00 end

        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
        $session->proctors()->attach($proctor->id);

        $applicant = Applicant::factory()->create();
        $session->applicants()->attach($applicant->id, [
            'attendance_status' => 'present',
            'submission_status' => 'pending',
        ]);

        $response = $this->actingAs($proctor)->postJson("/proctor/sessions/{$session->id}/submission", [
            'applicant_id' => $applicant->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('exam_session_applicant', [
            'submission_status' => 'submitted',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
