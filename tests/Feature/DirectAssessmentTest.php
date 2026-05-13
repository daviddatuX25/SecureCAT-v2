<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\StoreDirectAssessmentRequest;
use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DirectAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectAssessmentTest extends TestCase
{
    use RefreshDatabase;

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

        $request = new StoreDirectAssessmentRequest;
        $request->merge([
            'academic_year_id' => $academicYear->id,
            'applicant_ids' => [$applicant->id],
        ]);

        $validator = app('validator')->make($request->all(), $request->rules());
        \Closure::bind(fn () => $request->withValidator($validator), $request, StoreDirectAssessmentRequest::class)();

        $this->assertTrue($validator->fails());
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

        $service = app(DirectAssessmentService::class);
        $service->create(
            academicYear: $academicYear,
            applicantIds: [$applicant->id],
            openedBy: $admin,
        );

        $request = new StoreDirectAssessmentRequest;
        $request->merge([
            'academic_year_id' => $academicYear->id,
            'applicant_ids' => [$applicant->id],
        ]);

        $validator = app('validator')->make($request->all(), $request->rules());
        \Closure::bind(fn () => $request->withValidator($validator), $request, StoreDirectAssessmentRequest::class)();

        $this->assertTrue($validator->fails());
    }
}
