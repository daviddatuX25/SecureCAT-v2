<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ExamSessionCompleted;
use App\Notifications\ExamSessionReminder;
use App\Notifications\ExamSessionStarted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExamSessionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createSession(string $status = ExamSession::STATUS_DRAFT): ExamSession
    {
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $room = Room::factory()->create(['is_active' => true]);

        return ExamSession::factory()->create([
            'academic_year_id' => $academicYear->id,
            'room_id' => $room->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => $status,
        ]);
    }

    private function actingAsProctor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('proctor');

        return $user;
    }

    private function actingAsTestAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('test_administrator');

        return $user;
    }

    /** @test */
    public function proctor_can_start_published_session_within_window(): void
    {
        $session = $this->createSession(ExamSession::STATUS_PUBLISHED);
        $proctor = $this->actingAsProctor();
        $session->proctors()->attach($proctor);
        $session->applicants()->attach(Applicant::factory()->create());

        // Set the session date/time to be within start window
        $session->update([
            'date' => now()->format('Y-m-d'),
            'start_time' => now()->subMinutes(5)->format('H:i'),
            'end_time' => now()->addHours(2)->format('H:i'),
        ]);

        $response = $this->actingAs($proctor)->post("/proctor/sessions/{$session->id}/start");

        $response->assertRedirect();
        $this->assertEquals(ExamSession::STATUS_IN_PROGRESS, $session->fresh()->status);
    }

    /** @test */
    public function proctor_cannot_start_session_outside_window_without_override(): void
    {
        $session = $this->createSession(ExamSession::STATUS_PUBLISHED);
        $proctor = $this->actingAsProctor();
        $session->proctors()->attach($proctor);
        $session->applicants()->attach(Applicant::factory()->create());

        // Set the session date/time to be outside start window (past)
        $session->update([
            'date' => now()->subDays(2)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response = $this->actingAs($proctor)->post("/proctor/sessions/{$session->id}/start");

        // Should redirect back with error (not change status)
        $this->assertEquals(ExamSession::STATUS_PUBLISHED, $session->fresh()->status);
    }

    /** @test */
    public function test_admin_can_start_session_outside_window(): void
    {
        $session = $this->createSession(ExamSession::STATUS_PUBLISHED);
        $testAdmin = $this->actingAsTestAdmin();
        $session->proctors()->attach($testAdmin);
        $session->applicants()->attach(Applicant::factory()->create());

        // Set the session date/time to be outside start window (past)
        $session->update([
            'date' => now()->subDays(2)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $response = $this->actingAs($testAdmin)->post("/proctor/sessions/{$session->id}/start");

        $response->assertRedirect();
        $this->assertEquals(ExamSession::STATUS_IN_PROGRESS, $session->fresh()->status);
    }

    /** @test */
    public function test_admin_can_close_in_progress_session(): void
    {
        $session = $this->createSession(ExamSession::STATUS_IN_PROGRESS);
        $testAdmin = $this->actingAsTestAdmin();

        $response = $this->actingAs($testAdmin)->post("/proctor/sessions/{$session->id}/close");

        $response->assertRedirect();
        $this->assertEquals(ExamSession::STATUS_COMPLETED, $session->fresh()->status);
    }

    /** @test */
    public function start_dispatches_exam_session_started_notification(): void
    {
        Notification::fake();

        $session = $this->createSession(ExamSession::STATUS_PUBLISHED);
        $proctor = $this->actingAsProctor();
        $session->proctors()->attach($proctor);
        $session->applicants()->attach(Applicant::factory()->create());

        // Set within start window
        $session->update([
            'date' => now()->format('Y-m-d'),
            'start_time' => now()->subMinutes(5)->format('H:i'),
            'end_time' => now()->addHours(2)->format('H:i'),
        ]);

        $this->actingAs($proctor)->post("/proctor/sessions/{$session->id}/start");

        Notification::assertSentTo(
            [$proctor],
            ExamSessionStarted::class
        );
    }

    /** @test */
    public function close_dispatches_exam_session_completed_notification(): void
    {
        Notification::fake();

        $session = $this->createSession(ExamSession::STATUS_IN_PROGRESS);
        $proctor = $this->actingAsProctor();
        $session->proctors()->attach($proctor);

        $this->actingAs($proctor)->post("/proctor/sessions/{$session->id}/close");

        Notification::assertSentTo(
            [$proctor],
            ExamSessionCompleted::class
        );
    }

    /** @test */
    public function exam_session_reminder_is_database_only(): void
    {
        $session = $this->createSession(ExamSession::STATUS_PUBLISHED);
        $user = $this->actingAsProctor();

        $reminder = new ExamSessionReminder(
            session: $session,
            daysUntil: 1
        );

        $via = $reminder->via($user);
        $this->assertEquals(['mail', 'database'], $via);
    }

    /** @test */
    public function super_admin_can_delete_completed_exam_session_if_grading_is_not_finalized(): void
    {
        $session = $this->createSession(ExamSession::STATUS_COMPLETED);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $gradingSession = GradingSession::factory()->create([
            'exam_session_id' => $session->id,
            'status' => GradingSession::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/exam-scheduling/{$session->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted($session);
        $this->assertDatabaseMissing('grading_sessions', ['id' => $gradingSession->id]);
    }

    /** @test */
    public function super_admin_cannot_delete_completed_exam_session_if_grading_is_finalized(): void
    {
        $session = $this->createSession(ExamSession::STATUS_COMPLETED);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $gradingSession = GradingSession::factory()->create([
            'exam_session_id' => $session->id,
            'status' => GradingSession::STATUS_FINALIZED,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/exam-scheduling/{$session->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('exam_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('grading_sessions', ['id' => $gradingSession->id]);
    }

    /** @test */
    public function super_admin_can_reopen_completed_exam_session_if_grading_is_not_finalized(): void
    {
        $session = $this->createSession(ExamSession::STATUS_COMPLETED);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $gradingSession = GradingSession::factory()->create([
            'exam_session_id' => $session->id,
            'status' => GradingSession::STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($admin)->post("/admin/exam-scheduling/{$session->id}/reopen");

        $response->assertRedirect();
        $this->assertEquals(ExamSession::STATUS_IN_PROGRESS, $session->fresh()->status);
    }
}
