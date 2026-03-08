<?php

namespace Tests\Feature\Consultation;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Role;
use App\Models\Room;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConsultationScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);
    }

    private function test_administrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        return $user;
    }

    private function createFinalizedGradingSession(array $options = []): GradingSession
    {
        $season = Season::firstOrCreate(
            ['academic_year' => '2025-2026', 'semester' => '1'],
            [
                'is_active' => true,
                'application_start_date' => now()->subDays(5),
                'application_end_date' => now()->addDays(30),
            ]
        );
        $room = Room::firstOrCreate(
            ['name' => 'Room A'],
            ['building' => 'Main', 'capacity' => 10, 'is_active' => true]
        );
        $examSession = ExamSession::create([
            'season_id' => $season->id,
            'room_id' => $room->id,
            'date' => $options['exam_date'] ?? now()->addDays(1),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => ExamSession::STATUS_PUBLISHED,
            'created_by' => $this->testAdministrator()->id,
        ]);
        $gs = GradingSession::create([
            'exam_session_id' => $examSession->id,
            'status' => GradingSession::STATUS_FINALIZED,
            'opened_at' => now()->subHour(),
            'opened_by' => $this->testAdministrator()->id,
            'finalized_at' => now(),
            'finalized_by' => $this->testAdministrator()->id,
        ]);

        return $gs;
    }

    /**
     * Create an applicant with application and attach to grading session (optionally marked printed).
     */
    private function attachApplicantToGradingSession(GradingSession $gs, array $appAttrs = [], bool $printed = false): Applicant
    {
        $season = $gs->examSession->season;
        $course = Course::first();
        $app = Application::create(array_merge([
            'season_id' => $season->id,
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'birthdate' => '2000-01-01',
            'age' => 24,
            'sex' => 'female',
            'email' => 'jane-'.uniqid().'@example.com',
            'course_preference_1' => $course->id,
            'course_preference_2' => $course->id,
            'course_preference_3' => $course->id,
            'status' => 'accepted',
            'submitted_at' => now()->subDay(),
        ], $appAttrs));
        $applicant = Applicant::factory()->create(['application_id' => $app->id]);

        $gs->applicants()->attach($applicant->id, [
            'result_printed_at' => $printed ? now() : null,
        ]);

        return $applicant;
    }

    /**
     * Index returns batches with total and printed_count from withCount (no N+1).
     */
    public function test_consultation_schedule_index_returns_batches_with_eager_loaded_data(): void
    {
        $gs = $this->createFinalizedGradingSession();
        $this->attachApplicantToGradingSession($gs, ['first_name' => 'Alice', 'last_name' => 'Smith'], true);
        $this->attachApplicantToGradingSession($gs, ['first_name' => 'Bob'], false);

        $response = $this->actingAs($this->testAdministrator())
            ->get(route('consultation.schedule.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('batches')
            ->has('applicantsByBatch')
        );
        $props = $response->original->getData()['page']['props'];
        $batches = $props['batches'] ?? [];
        $this->assertCount(1, $batches);
        $batch = $batches[0];
        $this->assertSame(2, $batch['total'], 'total should count all applicants');
        $this->assertSame(1, $batch['printed_count'], 'printed_count should count only printed');
    }

    /**
     * Index with no finalized grading sessions returns empty batches.
     */
    public function test_consultation_schedule_index_no_grading_sessions_returns_empty(): void
    {
        $response = $this->actingAs($this->testAdministrator())
            ->get(route('consultation.schedule.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('batches', [])
            ->where('applicantsByBatch', [])
        );
    }

    /**
     * Printed applicants appear in applicantsByBatch with name and reference.
     */
    public function test_consultation_schedule_index_with_printed_applicants_shows_name_and_reference(): void
    {
        $gs = $this->createFinalizedGradingSession();
        $appRef = 'REF-2025-001';
        $this->attachApplicantToGradingSession($gs, [
            'first_name' => 'Carl',
            'middle_name' => 'X',
            'last_name' => 'Lee',
            'reference_number' => $appRef,
        ], true);

        $response = $this->actingAs($this->testAdministrator())
            ->get(route('consultation.schedule.index'));

        $response->assertOk();
        $applicantsByBatch = ($response->original->getData()['page']['props'] ?? [])['applicantsByBatch'] ?? [];
        $this->assertArrayHasKey($gs->id, $applicantsByBatch);
        $applicants = $applicantsByBatch[$gs->id];
        $this->assertCount(1, $applicants);
        $this->assertStringContainsString('Carl', $applicants[0]['name']);
        $this->assertStringContainsString('X', $applicants[0]['name']);
        $this->assertStringContainsString('Lee', $applicants[0]['name']);
        $this->assertSame($appRef, $applicants[0]['reference']);
        $this->assertTrue($applicants[0]['printed']);
    }

    /**
     * Applicant without application shows dash for name and reference.
     */
    public function test_consultation_schedule_index_applicant_without_application_shows_dash(): void
    {
        $gs = $this->createFinalizedGradingSession();
        $course = Course::first();
        $app = Application::create([
            'season_id' => $gs->examSession->season_id,
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2000-01-01',
            'age' => 24,
            'sex' => 'male',
            'email' => 'orphan-'.uniqid().'@example.com',
            'course_preference_1' => $course->id,
            'course_preference_2' => $course->id,
            'course_preference_3' => $course->id,
            'status' => 'accepted',
            'submitted_at' => now()->subDay(),
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $app->id]);
        $gs->applicants()->attach($applicant->id, ['result_printed_at' => now()]);
        $app->delete();

        $response = $this->actingAs($this->testAdministrator())
            ->get(route('consultation.schedule.index'));

        $response->assertOk();
        $applicantsByBatch = ($response->original->getData()['page']['props'] ?? [])['applicantsByBatch'] ?? [];
        $this->assertArrayHasKey($gs->id, $applicantsByBatch);
        $applicants = $applicantsByBatch[$gs->id];
        $this->assertCount(1, $applicants);
        $this->assertSame('—', $applicants[0]['name']);
        $this->assertSame('—', $applicants[0]['reference']);
    }

    /**
     * Query count is bounded (no N+1 per grading session).
     */
    public function test_consultation_schedule_index_query_count_bounded(): void
    {
        $gs1 = $this->createFinalizedGradingSession(['exam_date' => now()->addDays(1)]);
        $gs2 = $this->createFinalizedGradingSession(['exam_date' => now()->addDays(2)]);
        $gs3 = $this->createFinalizedGradingSession(['exam_date' => now()->addDays(3)]);
        $this->attachApplicantToGradingSession($gs1, [], true);
        $this->attachApplicantToGradingSession($gs2, [], true);
        $this->attachApplicantToGradingSession($gs3, [], true);

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->actingAs($this->testAdministrator())
            ->get(route('consultation.schedule.index'));

        $this->assertLessThan(15, $queryCount, 'Consultation schedule index should avoid N+1 per grading session');
    }

    /**
     * Index requires authorized role (super_admin or test_administrator).
     */
    public function test_consultation_schedule_index_requires_authorized_role(): void
    {
        $staffUser = User::factory()->create();
        $staffUser->roles()->attach(Role::where('name', 'staff')->first());

        $response = $this->actingAs($staffUser)
            ->get(route('consultation.schedule.index'));

        $response->assertForbidden();
    }

    /**
     * Non-finalized grading sessions are excluded.
     */
    public function test_consultation_schedule_index_excludes_non_finalized_grading_sessions(): void
    {
        $gs = $this->createFinalizedGradingSession();
        $gs->update(['status' => GradingSession::STATUS_OPEN]);

        $response = $this->actingAs($this->testAdministrator())
            ->get(route('consultation.schedule.index'));

        $response->assertOk();
        $batches = ($response->original->getData()['page']['props'] ?? [])['batches'] ?? [];
        $this->assertCount(0, $batches);
    }

    /**
     * Multiple batches returned with correct exam_date and applicants keyed by batch id.
     */
    public function test_consultation_schedule_index_multiple_batches_returns_correct_structure(): void
    {
        $gs1 = $this->createFinalizedGradingSession(['exam_date' => now()->addDays(1)]);
        $gs2 = $this->createFinalizedGradingSession(['exam_date' => now()->addDays(2)]);
        $this->attachApplicantToGradingSession($gs1, ['first_name' => 'A'], true);
        $this->attachApplicantToGradingSession($gs2, ['first_name' => 'B'], true);

        $response = $this->actingAs($this->testAdministrator())
            ->get(route('consultation.schedule.index'));

        $response->assertOk();
        $batches = ($response->original->getData()['page']['props'] ?? [])['batches'] ?? [];
        $this->assertCount(2, $batches);
        $batchIds = collect($batches)->pluck('id')->all();
        $this->assertContains($gs1->id, $batchIds);
        $this->assertContains($gs2->id, $batchIds);

        $applicantsByBatch = ($response->original->getData()['page']['props'] ?? [])['applicantsByBatch'] ?? [];
        $this->assertArrayHasKey($gs1->id, $applicantsByBatch);
        $this->assertArrayHasKey($gs2->id, $applicantsByBatch);
        $this->assertCount(1, $applicantsByBatch[$gs1->id]);
        $this->assertCount(1, $applicantsByBatch[$gs2->id]);
    }
}
