<?php

namespace Tests\Feature\Proctor;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionRosterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $proctor;

    protected User $proctorOther;

    protected ExamSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->first());

        $this->proctor = User::factory()->create();
        $this->proctor->roles()->attach(Role::where('name', 'proctor')->first());

        $this->proctorOther = User::factory()->create();
        $this->proctorOther->roles()->attach(Role::where('name', 'proctor')->first());

        $room = Room::factory()->create();
        $this->session = ExamSession::factory()->create([
            'room_id' => $room->id,
            'status' => ExamSession::STATUS_PUBLISHED,
            'published_at' => now(),
            'created_by' => $this->admin->id,
        ]);
        $this->session->proctors()->attach($this->proctor->id);

        [$_, $a1] = $this->createAcceptedApplicant('roster1@example.com');
        [$_, $a2] = $this->createAcceptedApplicant('roster2@example.com');
        $this->session->applicants()->attach([$a1->id, $a2->id]);
    }

    private function createAcceptedApplicant(string $email): array
    {
        $courses = Course::orderBy('id')->limit(3)->pluck('id')->all();
        $app = Application::create([
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'Applicant',
            'suffix' => null,
            'birthdate' => '2004-01-01',
            'age' => 20,
            'sex' => 'male',
            'email' => $email,
            'phone' => null,
            'address_line' => null,
            'city' => null,
            'province' => null,
            'zip_code' => null,
            'course_preference_1' => $courses[0],
            'course_preference_2' => $courses[1],
            'course_preference_3' => $courses[2],
            'status' => 'accepted',
            'submitted_at' => now(),
        ]);
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
        ]);

        return [$app, $applicant];
    }

    public function test_proctor_assigned_can_view_roster(): void
    {
        $response = $this->actingAs($this->proctor)->get("/proctor/sessions/{$this->session->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Proctor/SessionRoster')
            ->has('session')
            ->where('session.id', $this->session->id)
            ->has('session.is_within_start_window')
            ->has('session.can_override_schedule')
            ->has('applicants')
            ->has('stats')
            ->where('stats.total', 2)
            ->has('stats.present_pending_submission')
        );
    }

    public function test_roster_includes_timestamps_and_present_pending_submission(): void
    {
        $applicants = $this->session->applicants()->get();
        // First applicant: present + submitted
        DB::table('exam_session_applicant')
            ->where('exam_session_id', $this->session->id)
            ->where('applicant_id', $applicants[0]->id)
            ->update([
                'attendance_status' => 'present',
                'attendance_marked_at' => now(),
                'attendance_marked_by' => $this->proctor->id,
                'submission_status' => 'submitted',
                'submitted_at' => now(),
                'submitted_to' => $this->proctor->id,
            ]);
        // Second applicant: present + pending submission
        DB::table('exam_session_applicant')
            ->where('exam_session_id', $this->session->id)
            ->where('applicant_id', $applicants[1]->id)
            ->update([
                'attendance_status' => 'present',
                'attendance_marked_at' => now(),
                'attendance_marked_by' => $this->proctor->id,
            ]);

        $response = $this->actingAs($this->proctor)->get("/proctor/sessions/{$this->session->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('applicants', 2)
            ->has('applicants.0.attendance_marked_at')
            ->has('applicants.0.submitted_at')
            ->where('stats.present_pending_submission', 1)
        );
    }

    public function test_admin_can_view_roster(): void
    {
        $response = $this->actingAs($this->admin)->get("/proctor/sessions/{$this->session->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Proctor/SessionRoster')
            ->has('session')
            ->has('applicants')
            ->has('stats')
        );
    }

    public function test_proctor_not_assigned_cannot_view_roster(): void
    {
        $response = $this->actingAs($this->proctorOther)->get("/proctor/sessions/{$this->session->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_redirected_to_login_for_roster(): void
    {
        $response = $this->get("/proctor/sessions/{$this->session->id}");

        $response->assertRedirect(route('login'));
    }

    public function test_attendance_mark_present_returns_200_and_updates_pivot(): void
    {
        $applicant = $this->session->applicants()->first();

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/attendance", [
            'applicant_id' => $applicant->id,
            'status' => 'present',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('exam_session_applicant', [
            'exam_session_id' => $this->session->id,
            'applicant_id' => $applicant->id,
            'attendance_status' => 'present',
        ]);
    }

    public function test_attendance_mark_absent_returns_200(): void
    {
        $applicant = $this->session->applicants()->first();

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/attendance", [
            'applicant_id' => $applicant->id,
            'status' => 'absent',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('exam_session_applicant', [
            'exam_session_id' => $this->session->id,
            'applicant_id' => $applicant->id,
            'attendance_status' => 'absent',
        ]);
    }

    public function test_attendance_already_marked_returns_409(): void
    {
        $applicant = $this->session->applicants()->first();
        DB::table('exam_session_applicant')
            ->where('exam_session_id', $this->session->id)
            ->where('applicant_id', $applicant->id)
            ->update([
                'attendance_status' => 'present',
                'attendance_marked_at' => now(),
                'attendance_marked_by' => $this->proctor->id,
            ]);

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/attendance", [
            'applicant_id' => $applicant->id,
            'status' => 'absent',
        ]);

        $response->assertStatus(409);
    }

    public function test_attendance_invalid_status_returns_422(): void
    {
        $applicant = $this->session->applicants()->first();

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/attendance", [
            'applicant_id' => $applicant->id,
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_submission_when_present_returns_200(): void
    {
        $this->session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
        $applicant = $this->session->applicants()->first();
        DB::table('exam_session_applicant')
            ->where('exam_session_id', $this->session->id)
            ->where('applicant_id', $applicant->id)
            ->update([
                'attendance_status' => 'present',
                'attendance_marked_at' => now(),
                'attendance_marked_by' => $this->proctor->id,
            ]);

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/submission", [
            'applicant_id' => $applicant->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('exam_session_applicant', [
            'exam_session_id' => $this->session->id,
            'applicant_id' => $applicant->id,
            'submission_status' => 'submitted',
        ]);
    }

    public function test_submission_when_not_present_returns_409(): void
    {
        $this->session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
        $applicant = $this->session->applicants()->first();
        // leave attendance as pending

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/submission", [
            'applicant_id' => $applicant->id,
        ]);

        $response->assertStatus(409);
    }

    public function test_submission_already_submitted_returns_409(): void
    {
        $this->session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
        $applicant = $this->session->applicants()->first();
        DB::table('exam_session_applicant')
            ->where('exam_session_id', $this->session->id)
            ->where('applicant_id', $applicant->id)
            ->update([
                'attendance_status' => 'present',
                'attendance_marked_at' => now(),
                'attendance_marked_by' => $this->proctor->id,
                'submission_status' => 'submitted',
                'submitted_at' => now(),
                'submitted_to' => $this->proctor->id,
            ]);

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/submission", [
            'applicant_id' => $applicant->id,
        ]);

        $response->assertStatus(409);
    }

    public function test_submission_bulk_marks_all_present_as_submitted(): void
    {
        $this->session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
        $applicants = $this->session->applicants()->get();
        foreach ($applicants as $a) {
            DB::table('exam_session_applicant')
                ->where('exam_session_id', $this->session->id)
                ->where('applicant_id', $a->id)
                ->update([
                    'attendance_status' => 'present',
                    'attendance_marked_at' => now(),
                    'attendance_marked_by' => $this->proctor->id,
                ]);
        }

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/submission-bulk");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        foreach ($applicants as $a) {
            $this->assertDatabaseHas('exam_session_applicant', [
                'exam_session_id' => $this->session->id,
                'applicant_id' => $a->id,
                'submission_status' => 'submitted',
            ]);
        }
    }

    public function test_submission_bulk_when_not_in_progress_returns_409(): void
    {
        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/submission-bulk");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Session must be in progress.');
    }

    public function test_submission_bulk_with_no_eligible_applicants_returns_success(): void
    {
        $this->session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
        // All still pending attendance - none present

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/submission-bulk");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'No applicants to mark as submitted.');
    }

    public function test_start_session_when_published_and_within_window_returns_200(): void
    {
        $this->session->update([
            'date' => now()->format('Y-m-d'),
            'start_time' => '00:00',
            'end_time' => '23:59',
        ]);

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/start");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->session->refresh();
        $this->assertSame(ExamSession::STATUS_IN_PROGRESS, $this->session->status);
        $this->assertNotNull($this->session->started_at);
    }

    public function test_start_session_outside_window_as_proctor_returns_409(): void
    {
        $this->session->update([
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $response = $this->actingAs($this->proctor)->postJson("/proctor/sessions/{$this->session->id}/start");

        $response->assertStatus(409);
        $response->assertJsonPath('message', 'Outside scheduled window. Only an admin can start the session outside the schedule.');
        $this->session->refresh();
        $this->assertSame(ExamSession::STATUS_PUBLISHED, $this->session->status);
        $this->assertNull($this->session->started_at);
    }

    public function test_start_session_outside_window_as_admin_returns_200(): void
    {
        $this->session->update([
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $response = $this->actingAs($this->admin)->post("/proctor/sessions/{$this->session->id}/start");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->session->refresh();
        $this->assertSame(ExamSession::STATUS_IN_PROGRESS, $this->session->status);
        $this->assertNotNull($this->session->started_at);
    }

    public function test_start_session_when_not_published_returns_409(): void
    {
        $this->session->update(['status' => ExamSession::STATUS_DRAFT]);

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/start");

        $response->assertStatus(409);
    }

    public function test_close_session_when_in_progress_returns_200(): void
    {
        $this->session->update([
            'status' => ExamSession::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/close");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->session->refresh();
        $this->assertSame(ExamSession::STATUS_COMPLETED, $this->session->status);
        $this->assertNotNull($this->session->closed_at);
    }

    public function test_close_session_when_not_in_progress_returns_409(): void
    {
        $response = $this->actingAs($this->proctor)->post("/proctor/sessions/{$this->session->id}/close");

        $response->assertStatus(409);
    }

    public function test_super_admin_can_view_roster(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->roles()->attach(Role::where('name', 'super_admin')->first());

        $response = $this->actingAs($superAdmin)->get("/proctor/sessions/{$this->session->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Proctor/SessionRoster'));
    }
}
