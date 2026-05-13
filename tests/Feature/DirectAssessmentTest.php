<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Room;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DirectAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DirectAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_direct_assessment_page_renders_inertia_component(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);

        config(['inertia.testing.ensure_pages_exist' => false]);

        $response = $this->get(route('admin.direct-assessments.create'));
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/DirectAssessment/Create')
            ->has('academicYears')
            ->has('applicants')
            ->has('activeAcademicYearId')
        );
    }

    public function test_create_direct_assessment_page_returns_403_when_disabled(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', false);

        $response = $this->get(route('admin.direct-assessments.create'));
        $response->assertForbidden();
    }

    public function test_exam_session_has_type_constants(): void
    {
        $this->assertSame('scheduled', ExamSession::TYPE_SCHEDULED);
        $this->assertSame('direct', ExamSession::TYPE_DIRECT);
    }

    public function test_is_direct_returns_true_for_direct_type(): void
    {
        $session = ExamSession::factory()->make(['type' => 'direct']);
        $this->assertTrue($session->isDirect());
    }

    public function test_is_direct_returns_false_for_scheduled_type(): void
    {
        $session = ExamSession::factory()->make(['type' => 'scheduled']);
        $this->assertFalse($session->isDirect());
    }

    public function test_direct_factory_state_sets_correct_attributes(): void
    {
        $session = ExamSession::factory()->direct()->make();
        $this->assertSame(ExamSession::TYPE_DIRECT, $session->type);
        $this->assertNull($session->room_id);
        $this->assertNull($session->end_time);
        $this->assertSame('in_progress', $session->status);
        $this->assertNotNull($session->label);
    }

    public function test_direct_assessment_creates_exam_session_and_grading_session(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);

        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        $service = app(DirectAssessmentService::class);
        $gradingSession = $service->create(
            academicYear: $academicYear,
            applicantIds: [$applicant->id],
            openedBy: $admin,
            label: 'Walk-in Batch 1'
        );

        $this->assertInstanceOf(GradingSession::class, $gradingSession);
        $this->assertSame('open', $gradingSession->status);

        $examSession = $gradingSession->examSession;
        $this->assertSame('direct', $examSession->type);
        $this->assertSame('Walk-in Batch 1', $examSession->label);
        $this->assertSame('in_progress', $examSession->status);
        $this->assertNull($examSession->room_id);
        $this->assertEquals($academicYear->id, $examSession->academic_year_id);

        $this->assertTrue($examSession->applicants()->where('applicant_id', $applicant->id)->exists());
        $this->assertSame('present', $examSession->applicants()->first()->pivot->attendance_status);
    }

    public function test_direct_assessment_rejects_non_accepted_applicant(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);

        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['status' => 'pending', 'academic_year_id' => $academicYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        $response = $this->post(route('admin.direct-assessments.store'), [
            'academic_year_id' => $academicYear->id,
            'applicant_ids' => [$applicant->id],
        ]);

        $response->assertSessionHasErrors('applicant_ids.0');
    }

    public function test_direct_assessment_rejects_applicant_already_in_active_grading(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);

        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        // Create first direct assessment
        $firstResponse = $this->post(route('admin.direct-assessments.store'), [
            'academic_year_id' => $academicYear->id,
            'applicant_ids' => [$applicant->id],
        ]);
        $firstResponse->assertRedirect();

        // Attempt second — should fail
        $response = $this->post(route('admin.direct-assessments.store'), [
            'academic_year_id' => $academicYear->id,
            'applicant_ids' => [$applicant->id],
        ]);

        $response->assertSessionHasErrors('applicant_ids.0');
    }

    public function test_store_direct_assessment_redirects_to_grading_session(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);

        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        $response = $this->post(route('admin.direct-assessments.store'), [
            'academic_year_id' => $academicYear->id,
            'applicant_ids' => [$applicant->id],
            'label' => 'Walk-in Batch 3',
        ]);

        $gradingSession = GradingSession::latest()->first();
        $response->assertRedirect(route('admin.grading.sessions.show', $gradingSession->id));
    }

    public function test_store_direct_assessment_returns_403_when_disabled(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', false);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $response = $this->post(route('admin.direct-assessments.store'), [
            'academic_year_id' => $academicYear->id,
            'applicant_ids' => [1],
        ]);

        $response->assertForbidden();
    }

    public function test_scheduled_exam_session_still_requires_room(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        AcademicYear::factory()->create(['is_active' => true]);

        $response = $this->post(route('admin.exam-scheduling.store'), [
            'academic_year_id' => AcademicYear::first()->id,
            'room_id' => null,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $response->assertSessionHasErrors('room_id');
    }

    public function test_allow_direct_assessment_defaults_to_true(): void
    {
        $this->assertTrue(SystemSetting::allowDirectAssessment());
    }

    public function test_allow_direct_assessment_can_be_toggled(): void
    {
        SystemSetting::set('allow_direct_assessment', false);
        $this->assertFalse(SystemSetting::allowDirectAssessment());

        SystemSetting::set('allow_direct_assessment', true);
        $this->assertTrue(SystemSetting::allowDirectAssessment());
    }

    public function test_direct_session_has_auto_present_attendance(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        $service = app(DirectAssessmentService::class);
        $gradingSession = $service->create($academicYear, [$applicant->id], $admin);

        $examSession = $gradingSession->examSession;
        $pivot = $examSession->applicants()->where('applicant_id', $applicant->id)->first()->pivot;

        $this->assertSame('present', $pivot->attendance_status);
        $this->assertNotNull($pivot->attendance_marked_at);
        $this->assertEquals($admin->id, $pivot->attendance_marked_by);
    }

    public function test_direct_session_has_no_room(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        $service = app(DirectAssessmentService::class);
        $gradingSession = $service->create($academicYear, [$applicant->id], $admin);

        $this->assertNull($gradingSession->examSession->room_id);
    }

    public function test_direct_session_sets_date_to_today(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        $service = app(DirectAssessmentService::class);
        $gradingSession = $service->create($academicYear, [$applicant->id], $admin);

        $this->assertSame(now()->format('Y-m-d'), $gradingSession->examSession->date->format('Y-m-d'));
    }

    public function test_direct_session_status_is_in_progress(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        SystemSetting::set('allow_direct_assessment', true);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);

        $service = app(DirectAssessmentService::class);
        $gradingSession = $service->create($academicYear, [$applicant->id], $admin);

        $this->assertSame('in_progress', $gradingSession->examSession->status);
    }

    public function test_existing_scheduled_flows_unchanged(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $room = Room::factory()->create(['is_active' => true]);

        $response = $this->post(route('admin.exam-scheduling.store'), [
            'academic_year_id' => $academicYear->id,
            'room_id' => $room->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('exam_sessions', [
            'room_id' => $room->id,
            'type' => 'scheduled',
            'status' => 'draft',
        ]);
    }
}
