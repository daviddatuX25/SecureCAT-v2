<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BulkAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $proctor;
    private ExamSession $session;
    private array $applicantIds;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proctor = User::factory()->create();
        $this->proctor->assignRole('super_admin');
        $this->session = ExamSession::factory()->create(['status' => 'in_progress']);

        $this->applicantIds = [];
        for ($i = 0; $i < 3; $i++) {
            $app = Application::factory()->create(['status' => 'accepted']);
            $applicant = Applicant::factory()->create(['application_id' => $app->id]);
            DB::table('exam_session_applicant')->insert([
                'exam_session_id' => $this->session->id,
                'applicant_id' => $applicant->id,
                'attendance_status' => 'pending',
                'submission_status' => 'pending',
            ]);
            $this->applicantIds[] = $applicant->id;
        }
    }

    public function test_bulk_attendance_marks_applicants_present(): void
    {
        $this->actingAs($this->proctor)
            ->post("/proctor/sessions/{$this->session->id}/bulk-attendance", [
                'applicant_ids' => $this->applicantIds,
                'status' => 'present',
            ])
            ->assertRedirect();

        foreach ($this->applicantIds as $id) {
            $this->assertDatabaseHas('exam_session_applicant', [
                'exam_session_id' => $this->session->id,
                'applicant_id' => $id,
                'attendance_status' => 'present',
            ]);
        }
    }

    public function test_bulk_attendance_marks_applicants_absent(): void
    {
        $this->actingAs($this->proctor)
            ->post("/proctor/sessions/{$this->session->id}/bulk-attendance", [
                'applicant_ids' => $this->applicantIds,
                'status' => 'absent',
            ])
            ->assertRedirect();

        foreach ($this->applicantIds as $id) {
            $this->assertDatabaseHas('exam_session_applicant', [
                'exam_session_id' => $this->session->id,
                'applicant_id' => $id,
                'attendance_status' => 'absent',
            ]);
        }
    }

    public function test_bulk_attendance_marks_applicants_submitted(): void
    {
        $this->actingAs($this->proctor)
            ->post("/proctor/sessions/{$this->session->id}/bulk-attendance", [
                'applicant_ids' => $this->applicantIds,
                'status' => 'submitted',
            ])
            ->assertRedirect();

        foreach ($this->applicantIds as $id) {
            $this->assertDatabaseHas('exam_session_applicant', [
                'exam_session_id' => $this->session->id,
                'applicant_id' => $id,
                'submission_status' => 'submitted',
            ]);
        }
    }
}