<?php

namespace Tests\Feature\Admin;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $superAdmin;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->first());
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->roles()->attach(Role::where('name', 'super_admin')->first());
        $this->room = Room::factory()->create();
    }

    public function test_admin_can_list_exam_sessions(): void
    {
        ExamSession::factory()->count(2)->create([
            'room_id' => $this->room->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/exam-sessions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ExamSessions/Index')
            ->has('sessions')
            ->has('statuses')
        );
    }

    public function test_admin_can_create_exam_session(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/exam-sessions', [
            'room_id' => $this->room->id,
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $response->assertRedirect(route('admin.exam-sessions.index'));
        $this->assertDatabaseHas('exam_sessions', [
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_create_exam_session_with_proctors(): void
    {
        $proctorRole = Role::where('name', 'proctor')->first();
        $user1 = User::factory()->create();
        $user1->roles()->attach($proctorRole);
        $user2 = User::factory()->create();
        $user2->roles()->attach($proctorRole);

        $response = $this->actingAs($this->admin)->post('/admin/exam-sessions', [
            'room_id' => $this->room->id,
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '14:00',
            'proctor_ids' => [$user1->id, $user2->id],
        ]);

        $response->assertRedirect(route('admin.exam-sessions.index'));
        $session = ExamSession::where('room_id', $this->room->id)->first();
        $this->assertCount(2, $session->proctors);
    }

    public function test_room_conflict_returns_validation_error(): void
    {
        ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/exam-sessions', [
            'room_id' => $this->room->id,
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $response->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('exam_sessions', 1);
    }

    public function test_admin_can_view_exam_session(): void
    {
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/exam-sessions/{$session->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ExamSessions/Show')
            ->has('session')
            ->where('session.id', $session->id)
        );
    }

    public function test_admin_can_update_exam_session(): void
    {
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'date' => now()->addDays(14)->format('Y-m-d'),
            'start_time' => '09:00',
            'created_by' => $this->admin->id,
        ]);
        $otherRoom = Room::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/exam-sessions/{$session->id}", [
            'room_id' => $otherRoom->id,
            'date' => $session->date->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '13:00',
        ]);

        $response->assertRedirect(route('admin.exam-sessions.show', $session));
        $session->refresh();
        $this->assertSame((int) $otherRoom->id, (int) $session->room_id);
        $this->assertSame('10:00', substr($session->start_time, 0, 5));
    }

    public function test_cannot_edit_completed_session(): void
    {
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_COMPLETED,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/exam-sessions/{$session->id}", [
            'start_time' => '11:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    /** E-004: Admin cannot edit cancelled sessions. */
    public function test_cannot_edit_cancelled_session(): void
    {
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_CANCELLED,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/exam-sessions/{$session->id}", [
            'start_time' => '11:00',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_reopen_completed_session(): void
    {
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_COMPLETED,
            'closed_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/reopen");

        $response->assertRedirect(route('admin.exam-sessions.show', $session));
        $response->assertSessionHas('success');
        $session->refresh();
        $this->assertSame(ExamSession::STATUS_IN_PROGRESS, $session->status);
        $this->assertNull($session->closed_at);
    }

    public function test_reopen_non_completed_session_returns_403(): void
    {
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_IN_PROGRESS,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/reopen");

        $response->assertStatus(403);
        $session->refresh();
        $this->assertSame(ExamSession::STATUS_IN_PROGRESS, $session->status);
    }

    /** E-020: Cannot reopen when grading session is finalized. */
    public function test_cannot_reopen_when_grading_finalized(): void
    {
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_COMPLETED,
            'closed_at' => now(),
            'created_by' => $this->admin->id,
        ]);
        \App\Models\GradingSession::create([
            'exam_session_id' => $session->id,
            'status' => \App\Models\GradingSession::STATUS_FINALIZED,
            'opened_at' => now(),
            'opened_by' => $this->admin->id,
            'finalized_at' => now(),
            'finalized_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/reopen");

        $response->assertRedirect(route('admin.exam-sessions.show', $session));
        $response->assertSessionHas('error');
        $session->refresh();
        $this->assertSame(ExamSession::STATUS_COMPLETED, $session->status);
    }

    public function test_proctor_cannot_reopen_session(): void
    {
        $proctor = User::factory()->create();
        $proctor->roles()->attach(Role::where('name', 'proctor')->first());
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_COMPLETED,
            'created_by' => $this->admin->id,
        ]);
        $session->proctors()->attach($proctor->id);

        $response = $this->actingAs($proctor)->post("/admin/exam-sessions/{$session->id}/reopen");

        $response->assertStatus(403);
        $session->refresh();
        $this->assertSame(ExamSession::STATUS_COMPLETED, $session->status);
    }

    public function test_guest_cannot_access_exam_sessions(): void
    {
        $response = $this->get('/admin/exam-sessions');

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_access_exam_sessions(): void
    {
        $staff = User::factory()->create();
        $staff->roles()->attach(Role::where('name', 'staff')->first());

        $response = $this->actingAs($staff)->get('/admin/exam-sessions');

        $response->assertStatus(403);
    }

    /** Create an accepted application and its applicant. Returns [Application, Applicant]. */
    private function createAcceptedApplicant(string $email = 'accepted@example.com'): array
    {
        $courses = Course::orderBy('id')->limit(3)->pluck('id')->all();
        if (count($courses) < 3) {
            $this->seed(\Database\Seeders\CourseSeeder::class);
            $courses = Course::orderBy('id')->limit(3)->pluck('id')->all();
        }
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

    public function test_assign_applicants_success(): void
    {
        $this->seed(\Database\Seeders\CourseSeeder::class);
        [$_, $a1] = $this->createAcceptedApplicant('a1@example.com');
        [$_, $a2] = $this->createAcceptedApplicant('a2@example.com');
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/assign-applicants", [
            'applicant_ids' => [$a1->id, $a2->id],
        ]);

        $response->assertRedirect(route('admin.exam-sessions.show', $session));
        $response->assertSessionHas('success');
        $session->refresh();
        $this->assertCount(2, $session->applicants);
        $this->assertTrue($session->applicants->pluck('id')->contains($a1->id));
        $this->assertTrue($session->applicants->pluck('id')->contains($a2->id));
    }

    public function test_assign_applicants_capacity_exceeded_returns_422(): void
    {
        $this->seed(\Database\Seeders\CourseSeeder::class);
        $smallRoom = Room::factory()->create(['capacity' => 2]);
        [$_, $a1] = $this->createAcceptedApplicant('cap1@example.com');
        [$_, $a2] = $this->createAcceptedApplicant('cap2@example.com');
        [$_, $a3] = $this->createAcceptedApplicant('cap3@example.com');
        $session = ExamSession::factory()->create([
            'room_id' => $smallRoom->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/assign-applicants", [
            'applicant_ids' => [$a1->id, $a2->id, $a3->id],
        ]);

        $response->assertSessionHasErrors('applicant_ids');
        $this->assertStringContainsString('capacity', $response->getSession()->get('errors')->first('applicant_ids'));
        $session->refresh();
        $this->assertCount(0, $session->applicants);
    }

    public function test_assign_applicants_only_accepted_allowed(): void
    {
        $this->seed(\Database\Seeders\CourseSeeder::class);
        $courses = Course::orderBy('id')->limit(3)->pluck('id')->all();
        $pendingApp = Application::create([
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Pending',
            'last_name' => 'User',
            'birthdate' => '2004-01-01',
            'age' => 20,
            'sex' => 'male',
            'email' => 'pending@example.com',
            'course_preference_1' => $courses[0],
            'course_preference_2' => $courses[1],
            'course_preference_3' => $courses[2],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        $pendingApplicant = Applicant::create([
            'application_id' => $pendingApp->id,
            'email' => $pendingApp->email,
        ]);
        [$_, $accepted] = $this->createAcceptedApplicant('accepted-only@example.com');
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/assign-applicants", [
            'applicant_ids' => [$accepted->id, $pendingApplicant->id],
        ]);

        $response->assertSessionHasErrors('applicant_ids');
        $session->refresh();
        $this->assertCount(0, $session->applicants);
    }

    public function test_assign_applicants_no_duplicate_sessions(): void
    {
        $this->seed(\Database\Seeders\CourseSeeder::class);
        [$_, $a1] = $this->createAcceptedApplicant('dup1@example.com');
        $room2 = Room::factory()->create();
        $session1 = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'created_by' => $this->admin->id,
        ]);
        $session2 = ExamSession::factory()->create([
            'room_id' => $room2->id,
            'created_by' => $this->admin->id,
        ]);
        $session1->applicants()->attach($a1->id);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session2->id}/assign-applicants", [
            'applicant_ids' => [$a1->id],
        ]);

        $response->assertSessionHasErrors('applicant_ids');
        $this->assertStringContainsString('already assigned', $response->getSession()->get('errors')->first('applicant_ids'));
        $session2->refresh();
        $this->assertCount(0, $session2->applicants);
    }

    public function test_publish_when_in_progress_returns_error(): void
    {
        [$_, $applicant] = $this->createAcceptedApplicant();
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'created_by' => $this->admin->id,
        ]);
        $session->applicants()->attach($applicant->id);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/publish");

        $response->assertRedirect(route('admin.exam-sessions.show', $session));
        $response->assertSessionHas('error');
        $session->refresh();
        $this->assertSame(ExamSession::STATUS_IN_PROGRESS, $session->status);
    }

    public function test_publish_when_cancelled_returns_error(): void
    {
        [$_, $applicant] = $this->createAcceptedApplicant();
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'status' => ExamSession::STATUS_CANCELLED,
            'created_by' => $this->admin->id,
        ]);
        $session->applicants()->attach($applicant->id);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/publish");

        $response->assertRedirect(route('admin.exam-sessions.show', $session));
        $response->assertSessionHas('error');
        $session->refresh();
        $this->assertSame(ExamSession::STATUS_CANCELLED, $session->status);
    }

    public function test_assign_applicants_validation_required(): void
    {
        $session = ExamSession::factory()->create([
            'room_id' => $this->room->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/exam-sessions/{$session->id}/assign-applicants", [
            'applicant_ids' => [],
        ]);

        $response->assertSessionHasErrors('applicant_ids');
    }
}
