<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\DecisionRule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    protected User $counselor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);
        $this->seed(\Database\Seeders\ExamDomainSeeder::class);

        $this->counselor = User::factory()->create();
        $this->counselor->roles()->attach(Role::where('name', 'counselor')->first());
    }

    public function test_counselor_can_view_consultation_dashboard(): void
    {
        $response = $this->actingAs($this->counselor)->get('/consultation');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Consultation/Dashboard')
            ->has('applicants_pending')
            ->has('applicants_released')
            ->has('stats')
        );
    }

    public function test_counselor_can_view_decision_rules(): void
    {
        $response = $this->actingAs($this->counselor)->get('/consultation/rules');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Consultation/Rules/Index')
            ->has('rules')
            ->has('courses')
            ->has('domains')
        );
    }

    public function test_counselor_can_create_decision_rule(): void
    {
        $course = Course::first();
        $domain = \App\Models\ExamDomain::first();

        $response = $this->actingAs($this->counselor)->post('/consultation/rules', [
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 70,
            'max_score' => 100,
            'note' => 'Strong performance.',
        ]);

        $response->assertRedirect(route('consultation.rules.index'));
        $this->assertDatabaseHas('decision_rules', [
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 70,
            'max_score' => 100,
        ]);
    }

    public function test_create_decision_rule_rejects_overlapping_score_range(): void
    {
        $course = Course::first();
        $domain = \App\Models\ExamDomain::first();

        DecisionRule::create([
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 70,
            'max_score' => 100,
            'note' => 'Existing rule',
            'created_by' => $this->counselor->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->counselor)->post('/consultation/rules', [
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 80,
            'max_score' => 90,
            'note' => 'Overlapping range',
        ]);

        $response->assertSessionHasErrors('min_score');
    }

    public function test_create_decision_rule_allows_non_overlapping_range_same_course_domain(): void
    {
        $course = Course::first();
        $domain = \App\Models\ExamDomain::first();

        DecisionRule::create([
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 70,
            'max_score' => 85,
            'note' => 'Existing rule',
            'created_by' => $this->counselor->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->counselor)->post('/consultation/rules', [
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 86,
            'max_score' => 100,
            'note' => 'Adjacent non-overlapping',
        ]);

        $response->assertRedirect(route('consultation.rules.index'));
        $this->assertDatabaseHas('decision_rules', [
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 86,
            'max_score' => 100,
        ]);
    }

    public function test_update_decision_rule_rejects_overlapping_score_range(): void
    {
        $course = Course::first();
        $domain = \App\Models\ExamDomain::first();

        $existing = DecisionRule::create([
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 70,
            'max_score' => 100,
            'note' => 'Existing rule',
            'created_by' => $this->counselor->id,
            'is_active' => true,
        ]);

        $other = DecisionRule::create([
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 0,
            'max_score' => 50,
            'note' => 'Other rule',
            'created_by' => $this->counselor->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->counselor)->put(route('consultation.rules.update', $other), [
            'min_score' => 40,
            'max_score' => 80,
            'note' => $other->note,
        ]);

        $response->assertSessionHasErrors('min_score');
    }

    public function test_update_decision_rule_allows_non_overlapping_change(): void
    {
        $course = Course::first();
        $domain = \App\Models\ExamDomain::first();

        $rule = DecisionRule::create([
            'course_id' => $course->id,
            'domain_id' => $domain->id,
            'min_score' => 70,
            'max_score' => 100,
            'note' => 'Rule to update',
            'created_by' => $this->counselor->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->counselor)->put(route('consultation.rules.update', $rule), [
            'note' => 'Updated note only',
        ]);

        $response->assertRedirect(route('consultation.rules.index'));
        $this->assertDatabaseHas('decision_rules', [
            'id' => $rule->id,
            'note' => 'Updated note only',
        ]);
    }

    /** E-026: Consultation schedule rejects applicants not in grading session. */
    public function test_consultation_schedule_rejects_applicants_not_in_grading_session(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $room = \App\Models\Room::factory()->create();
        $examSession = \App\Models\ExamSession::factory()->create([
            'room_id' => $room->id,
            'status' => \App\Models\ExamSession::STATUS_COMPLETED,
            'created_by' => User::factory()->create()->id,
        ]);
        $app1 = \App\Models\Application::create([
            'reference_number' => \App\Models\Application::nextReferenceNumber(),
            'first_name' => 'In',
            'last_name' => 'Session',
            'birthdate' => '2004-01-01',
            'age' => 20,
            'sex' => 'male',
            'email' => 'insession@example.com',
            'status' => 'accepted',
            'submitted_at' => now(),
            'course_preference_1' => Course::first()->id,
            'course_preference_2' => Course::skip(1)->first()->id,
            'course_preference_3' => Course::skip(2)->first()->id,
        ]);
        $applicantIn = \App\Models\Applicant::create(['application_id' => $app1->id, 'email' => $app1->email]);
        $app2 = \App\Models\Application::create([
            'reference_number' => \App\Models\Application::nextReferenceNumber(),
            'first_name' => 'Out',
            'last_name' => 'Session',
            'birthdate' => '2004-01-01',
            'age' => 20,
            'sex' => 'male',
            'email' => 'outsession@example.com',
            'status' => 'accepted',
            'submitted_at' => now(),
            'course_preference_1' => Course::first()->id,
            'course_preference_2' => Course::skip(1)->first()->id,
            'course_preference_3' => Course::skip(2)->first()->id,
        ]);
        $applicantOut = \App\Models\Applicant::create(['application_id' => $app2->id, 'email' => $app2->email]);

        $examSession->applicants()->attach($applicantIn->id);
        $gs = \App\Models\GradingSession::create([
            'exam_session_id' => $examSession->id,
            'status' => \App\Models\GradingSession::STATUS_FINALIZED,
            'opened_at' => now(),
            'opened_by' => $this->counselor->id,
            'finalized_at' => now(),
            'finalized_by' => $this->counselor->id,
        ]);
        $gs->applicants()->attach($applicantIn->id, ['result_printed_at' => now()]);

        $response = $this->actingAs($this->counselor)->post('/consultation/schedule', [
            'scheduled_date' => now()->addDays(1)->format('Y-m-d'),
            'applicant_ids' => [$applicantOut->id],
            'grading_session_id' => $gs->id,
        ]);

        $response->assertSessionHasErrors('applicant_ids');
        $this->assertDatabaseMissing('consultation_schedules', ['grading_session_id' => $gs->id]);
    }

    /** E-027: Counselor cannot view applicant without finalized scores. */
    public function test_consultation_applicant_404_when_no_finalized_scores(): void
    {
        $app = \App\Models\Application::create([
            'reference_number' => \App\Models\Application::nextReferenceNumber(),
            'first_name' => 'No',
            'last_name' => 'Scores',
            'birthdate' => '2004-01-01',
            'age' => 20,
            'sex' => 'male',
            'email' => 'noscores@example.com',
            'status' => 'accepted',
            'submitted_at' => now(),
            'course_preference_1' => Course::first()->id,
            'course_preference_2' => Course::skip(1)->first()->id,
            'course_preference_3' => Course::skip(2)->first()->id,
        ]);
        $applicant = \App\Models\Applicant::create(['application_id' => $app->id, 'email' => $app->email]);

        $response = $this->actingAs($this->counselor)->get("/consultation/applicants/{$applicant->id}");

        $response->assertStatus(404);
    }

    /** E-030: Recommended course must be in applicant preferences. */
    public function test_consultation_summary_rejects_recommended_course_not_in_preferences(): void
    {
        $room = \App\Models\Room::factory()->create();
        $examSession = \App\Models\ExamSession::factory()->create([
            'room_id' => $room->id,
            'status' => \App\Models\ExamSession::STATUS_COMPLETED,
            'created_by' => User::factory()->create()->id,
        ]);
        $allCourses = Course::orderBy('id')->get();
        $prefIds = [$allCourses[0]->id, $allCourses[1]->id, $allCourses[2]->id];
        $otherCourse = \App\Models\Course::factory()->create(['name' => 'Other Course', 'code' => 'OTHER']);
        $app = \App\Models\Application::create([
            'reference_number' => \App\Models\Application::nextReferenceNumber(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2004-01-01',
            'age' => 20,
            'sex' => 'male',
            'email' => 'prefstest@example.com',
            'status' => 'accepted',
            'submitted_at' => now(),
            'course_preference_1' => $prefIds[0],
            'course_preference_2' => $prefIds[1],
            'course_preference_3' => $prefIds[2],
        ]);
        $applicant = \App\Models\Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $examSession->applicants()->attach($applicant->id);
        $gs = \App\Models\GradingSession::create([
            'exam_session_id' => $examSession->id,
            'status' => \App\Models\GradingSession::STATUS_FINALIZED,
            'opened_at' => now(),
            'opened_by' => $this->counselor->id,
            'finalized_at' => now(),
            'finalized_by' => $this->counselor->id,
        ]);
        $gs->applicants()->attach($applicant->id);
        $domain = \App\Models\ExamDomain::first();
        \App\Models\ApplicantScore::create([
            'grading_session_id' => $gs->id,
            'applicant_id' => $applicant->id,
            'domain_id' => $domain->id,
            'raw_score' => 80,
            'max_score' => 100,
            'normalized_score' => 80,
            'scored_by' => $this->counselor->id,
            'scored_at' => now(),
        ]);
        foreach (\App\Models\ExamDomain::where('is_active', true)->get() as $d) {
            if ($d->id !== $domain->id) {
                \App\Models\ApplicantScore::create([
                    'grading_session_id' => $gs->id,
                    'applicant_id' => $applicant->id,
                    'domain_id' => $d->id,
                    'raw_score' => 80,
                    'max_score' => 100,
                    'normalized_score' => 80,
                    'scored_by' => $this->counselor->id,
                    'scored_at' => now(),
                ]);
            }
        }

        $response = $this->actingAs($this->counselor)->put("/consultation/applicants/{$applicant->id}/summary", [
            'recommended_course_id' => $otherCourse->id,
        ]);

        $response->assertSessionHasErrors('recommended_course_id');
    }
}
