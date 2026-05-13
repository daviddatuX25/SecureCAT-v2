<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\Application;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationPipelineStatusTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;

    private function user(): User
    {
        return $this->user ??= User::factory()->create();
    }

    private function createApp(string $status = 'pending'): Application
    {
        $course = Course::factory()->create();
        $ay = AcademicYear::factory()->create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
            'application_start_date' => now()->subDays(5)->toDateString(),
            'application_end_date' => now()->addDays(30)->toDateString(),
        ]);

        return Application::factory()->create([
            'academic_year_id' => $ay->id,
            'course_preference_1' => $course->id,
            'status' => $status,
        ]);
    }

    public function test_pending_application_returns_pending(): void
    {
        $app = $this->createApp('pending');
        $this->assertSame('pending', $app->pipelineStatus());
    }

    public function test_dismissed_application_returns_dismissed(): void
    {
        $app = $this->createApp('dismissed');
        $this->assertSame('dismissed', $app->pipelineStatus());
    }

    public function test_accepted_without_applicant_returns_accepted(): void
    {
        $app = $this->createApp('accepted');
        $this->assertSame('accepted', $app->pipelineStatus());
    }

    public function test_accepted_without_exam_session_returns_accepted(): void
    {
        $app = $this->createApp('accepted');
        Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $app->load('applicant.examSessions');
        $this->assertSame('accepted', $app->pipelineStatus());
    }

    public function test_accepted_with_draft_session_returns_draft_scheduled(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => $this->user()->id,
        ]);
        $session->applicants()->attach($applicant);

        $app->load('applicant.examSessions');
        $this->assertSame('draft_scheduled', $app->pipelineStatus());
    }

    public function test_accepted_with_published_session_not_attended_returns_scheduled(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_PUBLISHED,
            'created_by' => $this->user()->id,
        ]);
        $session->applicants()->attach($applicant, ['attendance_status' => 'pending']);

        $app->load('applicant.examSessions');
        $this->assertSame('scheduled', $app->pipelineStatus());
    }

    public function test_attended_returns_attended(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_PUBLISHED,
            'created_by' => $this->user()->id,
        ]);
        $session->applicants()->attach($applicant, [
            'attendance_status' => 'present',
            'attendance_marked_at' => now(),
        ]);

        $app->load('applicant.examSessions');
        $this->assertSame('attended', $app->pipelineStatus());
    }

    public function test_submitted_returns_submitted(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_COMPLETED,
            'created_by' => $this->user()->id,
        ]);
        $session->applicants()->attach($applicant, [
            'attendance_status' => 'present',
            'attendance_marked_at' => now(),
            'submission_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $app->load('applicant.examSessions');
        $this->assertSame('submitted', $app->pipelineStatus());
    }

    public function test_graded_returns_graded(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_COMPLETED,
            'created_by' => $this->user()->id,
        ]);
        $session->applicants()->attach($applicant, [
            'attendance_status' => 'present',
            'attendance_marked_at' => now(),
            'submission_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $gradingSession = GradingSession::create([
            'exam_session_id' => $session->id,
            'status' => GradingSession::STATUS_FINALIZED,
            'opened_by' => $this->user()->id,
            'finalized_by' => $this->user()->id,
            'finalized_at' => now(),
        ]);

        ApplicantScore::create([
            'grading_session_id' => $gradingSession->id,
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => 1,
            'raw_score' => 85,
            'max_score' => 100,
            'normalized_score' => 85.0,
            'scored_by' => $this->user()->id,
            'scored_at' => now(),
        ]);

        $app->load('applicant.examSessions', 'applicant.applicantScores');
        $this->assertSame('graded', $app->pipelineStatus());
    }

    public function test_cancelled_session_returns_accepted(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_CANCELLED,
            'created_by' => $this->user()->id,
        ]);
        $session->applicants()->attach($applicant);

        $app->load('applicant.examSessions');
        $this->assertSame('accepted', $app->pipelineStatus());
    }

    public function test_dismissed_overrides_everything(): void
    {
        $app = $this->createApp('dismissed');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_PUBLISHED,
            'created_by' => $this->user()->id,
        ]);
        $session->applicants()->attach($applicant, ['attendance_status' => 'present']);

        $app->load('applicant.examSessions');
        $this->assertSame('dismissed', $app->pipelineStatus());
    }
}
