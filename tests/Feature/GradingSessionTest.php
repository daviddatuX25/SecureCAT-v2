<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\Application;
use App\Models\ExamDomain;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\ResultSheetTemplate;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingSessionTest extends TestCase
{
    use RefreshDatabase;

    protected User $grader;

    protected ExamSession $examSession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);
        $this->seed(\Database\Seeders\ExamDomainSeeder::class);

        $this->grader = User::factory()->create();
        $this->grader->roles()->attach(Role::where('name', 'grader')->first());

        $room = Room::factory()->create();
        $this->examSession = ExamSession::factory()->create([
            'room_id' => $room->id,
            'status' => ExamSession::STATUS_COMPLETED,
            'created_by' => User::factory()->create()->id,
        ]);
    }

    public function test_grader_can_view_grading_dashboard(): void
    {
        $response = $this->actingAs($this->grader)->get('/grading');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Grading/Dashboard')
            ->has('grading_sessions')
            ->has('completed_exams_without_grading')
        );
    }

    protected function createApplicationRecord(array $overrides = []): Application
    {
        $courses = \App\Models\Course::orderBy('id')->pluck('id')->all();
        if (count($courses) < 3) {
            $this->markTestSkipped('Need courses seeded.');
        }

        return Application::create(array_merge([
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birthdate' => '2005-01-15',
            'age' => 19,
            'sex' => 'male',
            'email' => 'juan@example.com',
            'status' => 'accepted',
            'submitted_at' => now(),
            'course_preference_1' => $courses[0],
            'course_preference_2' => $courses[1],
            'course_preference_3' => $courses[2],
        ], $overrides));
    }

    public function test_grader_can_open_grading_session(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $response = $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grading_sessions', [
            'exam_session_id' => $this->examSession->id,
            'status' => GradingSession::STATUS_OPEN,
        ]);
    }

    /** E-012: First score input updates session to in_progress. */
    public function test_first_score_input_updates_session_to_in_progress(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();
        $this->assertSame(GradingSession::STATUS_OPEN, $gs->status);

        $domain = ExamDomain::where('is_active', true)->first();
        $this->actingAs($this->grader)->put("/grading/sessions/{$gs->id}/applicants/{$applicant->id}/scores", [
            'scores' => [$domain->id => ['raw_score' => 5, 'max_score' => 10]],
        ]);

        $gs->refresh();
        $this->assertSame(GradingSession::STATUS_IN_PROGRESS, $gs->status);
    }

    /** E-022: Mark-printed rejects applicants not in grading session. */
    public function test_mark_printed_rejects_non_member_applicants(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();
        $otherApplicant = Applicant::create([
            'application_id' => $this->createApplicationRecord(['email' => 'nonmember@example.com'])->id,
            'email' => 'nonmember@example.com',
        ]);

        $response = $this->actingAs($this->grader)->post("/grading/sessions/{$gs->id}/mark-printed", [
            'applicant_ids' => [$otherApplicant->id],
            'printed' => true,
        ]);

        $response->assertSessionHasErrors('applicant_ids');
    }

    /** E-005: Grading can only be opened for completed exam sessions. */
    public function test_cannot_open_grading_for_non_completed_exam(): void
    {
        $this->examSession->update(['status' => ExamSession::STATUS_PUBLISHED]);

        $response = $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $response->assertSessionHasErrors('exam_session_id');
        $this->assertDatabaseMissing('grading_sessions', ['exam_session_id' => $this->examSession->id]);
    }

    /** E-009: Duplicate grading sessions not allowed per exam. */
    public function test_cannot_open_duplicate_grading_session(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $response = $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $response->assertSessionHasErrors('exam_session_id');
        $this->assertStringContainsString('already exists', $response->getSession()->get('errors')->first('exam_session_id'));
        $this->assertDatabaseCount('grading_sessions', 1);
    }

    /** E-010: Cannot finalize when not all applicants have scores. */
    public function test_cannot_finalize_without_all_applicants_scored(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();
        $domains = ExamDomain::where('is_active', true)->get();

        $response = $this->actingAs($this->grader)->put("/grading/sessions/{$gs->id}/workflow", [
            'status' => 'finalized',
        ]);

        $response->assertSessionHasErrors('status');
        $gs->refresh();
        $this->assertNotSame(GradingSession::STATUS_FINALIZED, $gs->status);
    }

    /** E-010: Can finalize when all applicants have scores for all active domains. */
    public function test_can_finalize_when_all_applicants_scored(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();
        foreach (ExamDomain::where('is_active', true)->get() as $domain) {
            ApplicantScore::create([
                'grading_session_id' => $gs->id,
                'applicant_id' => $applicant->id,
                'domain_id' => $domain->id,
                'raw_score' => 5,
                'max_score' => 10,
                'normalized_score' => 50,
                'scored_by' => $this->grader->id,
                'scored_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->grader)->put("/grading/sessions/{$gs->id}/workflow", [
            'status' => 'finalized',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $gs->refresh();
        $this->assertSame(GradingSession::STATUS_FINALIZED, $gs->status);
    }

    /** E-006: Cannot update scores when grading session is finalized. */
    public function test_cannot_update_scores_when_finalized(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();
        $domain = ExamDomain::where('is_active', true)->first();
        ApplicantScore::create([
            'grading_session_id' => $gs->id,
            'applicant_id' => $applicant->id,
            'domain_id' => $domain->id,
            'raw_score' => 5,
            'max_score' => 10,
            'normalized_score' => 50,
            'scored_by' => $this->grader->id,
            'scored_at' => now(),
        ]);
        foreach (ExamDomain::where('is_active', true)->get() as $d) {
            if ($d->id !== $domain->id) {
                ApplicantScore::create([
                    'grading_session_id' => $gs->id,
                    'applicant_id' => $applicant->id,
                    'domain_id' => $d->id,
                    'raw_score' => 5,
                    'max_score' => 10,
                    'normalized_score' => 50,
                    'scored_by' => $this->grader->id,
                    'scored_at' => now(),
                ]);
            }
        }
        $gs->update(['status' => GradingSession::STATUS_FINALIZED, 'finalized_at' => now(), 'finalized_by' => $this->grader->id]);

        $scores = [];
        foreach (ExamDomain::where('is_active', true)->get() as $d) {
            $scores[$d->id] = ['raw_score' => 6, 'max_score' => 10];
        }

        $response = $this->actingAs($this->grader)->put("/grading/sessions/{$gs->id}/applicants/{$applicant->id}/scores", [
            'scores' => $scores,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(5, ApplicantScore::where('grading_session_id', $gs->id)->where('applicant_id', $applicant->id)->where('domain_id', $domain->id)->first()->raw_score);
    }

    /** E-013: Applicant must belong to grading session for score input. */
    public function test_404_when_applicant_not_in_grading_session(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();
        $otherApplicant = Applicant::create([
            'application_id' => $this->createApplicationRecord(['email' => 'other@example.com'])->id,
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($this->grader)->get("/grading/sessions/{$gs->id}/applicants/{$otherApplicant->id}");

        $response->assertStatus(404);
    }

    /** E-014: raw_score cannot exceed max_score. */
    public function test_raw_score_cannot_exceed_max_score(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();
        $domain = ExamDomain::where('is_active', true)->first();

        $response = $this->actingAs($this->grader)->put("/grading/sessions/{$gs->id}/applicants/{$applicant->id}/scores", [
            'scores' => [
                $domain->id => ['raw_score' => 15, 'max_score' => 10],
            ],
        ]);

        $response->assertSessionHasErrors('scores');
        $this->assertDatabaseMissing('applicant_scores', [
            'grading_session_id' => $gs->id,
            'applicant_id' => $applicant->id,
            'raw_score' => 15,
        ]);
    }

    /** E-015: Domain IDs must be valid active exam domains. */
    public function test_rejects_invalid_domain_ids_in_scores(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();

        $response = $this->actingAs($this->grader)->put("/grading/sessions/{$gs->id}/applicants/{$applicant->id}/scores", [
            'scores' => [
                99999 => ['raw_score' => 5, 'max_score' => 10],
            ],
        ]);

        $response->assertSessionHasErrors('scores');
    }

    /** E-021: Result sheet returns 404 for applicant not in grading session. */
    public function test_result_sheet_404_when_applicant_not_in_session(): void
    {
        $app = $this->createApplicationRecord();
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        $this->examSession->applicants()->attach($applicant->id);

        $this->actingAs($this->grader)->post('/grading', [
            'exam_session_id' => $this->examSession->id,
        ]);

        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();
        $otherApplicant = Applicant::create([
            'application_id' => $this->createApplicationRecord(['email' => 'outsider@example.com'])->id,
            'email' => 'outsider@example.com',
        ]);

        $response = $this->actingAs($this->grader)->get("/grading/sessions/{$gs->id}/applicants/{$otherApplicant->id}/result-sheet");

        $response->assertStatus(404);
    }

    /** Half layout: 3 applicants produce 2 physical pages (app1+app2 on page 1, app3 on page 2). */
    public function test_print_bulk_half_layout_three_applicants_produces_two_pages(): void
    {
        $content = '<div><p>{{applicant_name}}</p><tbody><tr class="scores-rows-placeholder"><td></td></tr></tbody></div>';
        ResultSheetTemplate::create([
            'name' => 'Half',
            'mode' => 'html',
            'logical_unit' => 'half_a4',
            'content' => $content,
            'is_active' => true,
        ]);

        $apps = [
            $this->createApplicationRecord(['first_name' => 'Alice', 'last_name' => 'One', 'email' => 'alice@example.com']),
            $this->createApplicationRecord(['first_name' => 'Bob', 'last_name' => 'Two', 'email' => 'bob@example.com']),
            $this->createApplicationRecord(['first_name' => 'Carol', 'last_name' => 'Three', 'email' => 'carol@example.com']),
        ];
        $applicants = [];
        foreach ($apps as $app) {
            $applicants[] = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        }
        foreach ($applicants as $a) {
            $this->examSession->applicants()->attach($a->id);
        }

        $this->actingAs($this->grader)->post('/grading', ['exam_session_id' => $this->examSession->id]);
        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();

        foreach ($applicants as $a) {
            foreach (ExamDomain::where('is_active', true)->get() as $d) {
                ApplicantScore::create([
                    'grading_session_id' => $gs->id,
                    'applicant_id' => $a->id,
                    'domain_id' => $d->id,
                    'raw_score' => 5,
                    'max_score' => 10,
                    'normalized_score' => 50,
                    'scored_by' => $this->grader->id,
                    'scored_at' => now(),
                ]);
            }
        }

        $ids = $applicants[0]->id . ',' . $applicants[1]->id . ',' . $applicants[2]->id;
        $response = $this->actingAs($this->grader)->get("/grading/sessions/{$gs->id}/print-bulk?ids={$ids}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Grading/ResultSheetBulk')
            ->has('sheetsHtml', 2)
        );
        $sheetsHtml = $response->viewData('page')['props']['sheetsHtml'] ?? [];
        $this->assertCount(2, $sheetsHtml);
        $this->assertStringContainsString('Alice One', $sheetsHtml[0]);
        $this->assertStringContainsString('Bob Two', $sheetsHtml[0]);
        $this->assertStringContainsString('Carol Three', $sheetsHtml[1]);
    }

    /** Full layout: 2 applicants produce 2 physical pages. */
    public function test_print_bulk_full_layout_two_applicants_produces_two_pages(): void
    {
        $content = '<div><p>{{applicant_name}}</p><tbody><tr class="scores-rows-placeholder"><td></td></tr></tbody></div>';
        ResultSheetTemplate::create([
            'name' => 'Full',
            'mode' => 'html',
            'logical_unit' => 'full',
            'content' => $content,
            'is_active' => true,
        ]);

        $apps = [
            $this->createApplicationRecord(['first_name' => 'Dave', 'last_name' => 'First', 'email' => 'dave@example.com']),
            $this->createApplicationRecord(['first_name' => 'Eve', 'last_name' => 'Second', 'email' => 'eve@example.com']),
        ];
        $applicants = [];
        foreach ($apps as $app) {
            $applicants[] = Applicant::create(['application_id' => $app->id, 'email' => $app->email]);
        }
        foreach ($applicants as $a) {
            $this->examSession->applicants()->attach($a->id);
        }

        $this->actingAs($this->grader)->post('/grading', ['exam_session_id' => $this->examSession->id]);
        $gs = GradingSession::where('exam_session_id', $this->examSession->id)->first();

        foreach ($applicants as $a) {
            foreach (ExamDomain::where('is_active', true)->get() as $d) {
                ApplicantScore::create([
                    'grading_session_id' => $gs->id,
                    'applicant_id' => $a->id,
                    'domain_id' => $d->id,
                    'raw_score' => 5,
                    'max_score' => 10,
                    'normalized_score' => 50,
                    'scored_by' => $this->grader->id,
                    'scored_at' => now(),
                ]);
            }
        }

        $ids = $applicants[0]->id . ',' . $applicants[1]->id;
        $response = $this->actingAs($this->grader)->get("/grading/sessions/{$gs->id}/print-bulk?ids={$ids}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('sheetsHtml', 2));
        $sheetsHtml = $response->viewData('page')['props']['sheetsHtml'] ?? [];
        $this->assertCount(2, $sheetsHtml);
        $this->assertStringContainsString('Dave First', $sheetsHtml[0]);
        $this->assertStringContainsString('Eve Second', $sheetsHtml[1]);
    }

}
