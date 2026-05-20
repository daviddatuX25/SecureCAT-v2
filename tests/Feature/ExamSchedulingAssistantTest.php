<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ExamSchedulingAssistantController;
use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ExamSchedulingConversation;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use App\Services\ExamSchedulingAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSchedulingAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openrouter.key' => 'test-key']);
        config(['services.openrouter.model' => 'openrouter/free']);
    }

    public function test_scrub_replaces_hallucinated_zero_applicant_claims(): void
    {
        $controller = new ExamSchedulingAssistantController(
            new ExamSchedulingAssistantService
        );

        $messages = [
            ['role' => 'user', 'content' => 'How many are unassigned?'],
            ['role' => 'assistant', 'content' => 'There are no unassigned applicants yet.'],
        ];

        $method = new \ReflectionMethod($controller, 'scrubContradictingMessages');
        $method->setAccessible(true);
        $scrubbed = $method->invoke($controller, $messages, 4);

        $this->assertStringContainsString('contradicted', $scrubbed[1]['content']);
        $this->assertNotEquals('There are no unassigned applicants yet.', $scrubbed[1]['content']);
    }

    public function test_scrub_preserves_zero_count_when_applicants_are_actually_zero(): void
    {
        $controller = new ExamSchedulingAssistantController(
            new ExamSchedulingAssistantService
        );

        $messages = [
            ['role' => 'user', 'content' => 'How many are unassigned?'],
            ['role' => 'assistant', 'content' => 'There are no unassigned applicants yet.'],
        ];

        $method = new \ReflectionMethod($controller, 'scrubContradictingMessages');
        $method->setAccessible(true);
        $scrubbed = $method->invoke($controller, $messages, 0);

        // When applicantCount is 0, zero-applicant claims are accurate — leave them
        $this->assertEquals('There are no unassigned applicants yet.', $scrubbed[1]['content']);
    }

    public function test_scrub_preserves_non_hallucinated_messages(): void
    {
        $controller = new ExamSchedulingAssistantController(
            new ExamSchedulingAssistantService
        );

        $messages = [
            ['role' => 'user', 'content' => 'Schedule them in Lab Room 1.'],
            ['role' => 'assistant', 'content' => 'I can schedule them in Lab Room 1 next week. Which day works for you?'],
        ];

        $method = new \ReflectionMethod($controller, 'scrubContradictingMessages');
        $method->setAccessible(true);
        $scrubbed = $method->invoke($controller, $messages, 4);

        $this->assertEquals('I can schedule them in Lab Room 1 next week. Which day works for you?', $scrubbed[1]['content']);
    }

    public function test_clear_conversation_deletes_user_conversation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('registrar_administrator');

        ExamSchedulingConversation::create([
            'user_id' => $user->id,
            'messages' => [['role' => 'user', 'content' => 'hello']],
        ]);

        $this->actingAs($user)
            ->deleteJson('/admin/exam-scheduling/schedule-assistant/conversation')
            ->assertOk()
            ->assertJson(['message' => 'Conversation reset.']);

        $this->assertDatabaseMissing('exam_scheduling_conversations', ['user_id' => $user->id]);
    }

    public function test_clear_conversation_returns_403_for_unauthorized_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        $this->actingAs($user)
            ->deleteJson('/admin/exam-scheduling/schedule-assistant/conversation')
            ->assertForbidden();
    }

    public function test_system_prompt_contains_existing_sessions_constraint(): void
    {
        // buildSystemPrompt accepts raw arrays - test with explicit session data matching what controller builds
        $service = new ExamSchedulingAssistantService;
        $prompt = $service->buildSystemPrompt(
            applicantCount: 1,
            rooms: [['id' => 1, 'name' => 'Lab 1', 'capacity' => 30]],
            applicantSummary: [['id' => 1]],
            draftSessions: [],
            existingSessions: [[
                'id' => 1,
                'room_id' => 1,
                'room' => ['name' => 'Lab 1', 'capacity' => 30],
                'date' => '2026-04-15',
                'start_time' => '09:00',
                'end_time' => '12:00',
            ]]
        );

        $this->assertStringContainsString('SCHEDULED (non-draft)', $prompt);
        $this->assertStringContainsString('2026-04-15', $prompt);
        $this->assertStringContainsString('Lab 1', $prompt);
    }

    public function test_system_prompt_forbids_absolute_claims(): void
    {
        $room = Room::factory()->create(['name' => 'Lab 1', 'capacity' => 30, 'is_active' => true]);

        $service = new ExamSchedulingAssistantService;
        $prompt = $service->buildSystemPrompt(
            applicantCount: 4,
            rooms: [['id' => $room->id, 'name' => 'Lab 1', 'capacity' => 30]],
            applicantSummary: [],
            draftSessions: [],
            existingSessions: []
        );

        $this->assertStringContainsString('NEVER make absolute factual claims', $prompt);
        $this->assertStringContainsString('4 applicants waiting to be scheduled', $prompt);
        $this->assertStringContainsString('Assigned means confirmed placement', $prompt);
    }

    public function test_system_prompt_includes_draft_sessions(): void
    {
        $room = Room::factory()->create(['name' => 'Lab 1', 'capacity' => 30, 'is_active' => true]);
        $year = AcademicYear::factory()->create(['academic_year' => '2025-2026', 'semester' => '1', 'is_active' => true]);
        $user = User::factory()->create();

        $draftSession = ExamSession::factory()->create([
            'academic_year_id' => $year->id,
            'room_id' => $room->id,
            'date' => '2026-04-20',
            'start_time' => '14:00',
            'end_time' => '17:00',
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);

        $service = new ExamSchedulingAssistantService;
        $prompt = $service->buildSystemPrompt(
            applicantCount: 3,
            rooms: [['id' => $room->id, 'name' => 'Lab 1', 'capacity' => 30]],
            applicantSummary: [],
            draftSessions: [[
                'id' => $draftSession->id,
                'room_id' => $room->id,
                'room' => ['name' => 'Lab 1', 'capacity' => 30],
                'date' => '2026-04-20',
                'start_time' => '14:00',
                'end_time' => '17:00',
                'current_count' => 0,
                'capacity' => 30,
            ]],
            existingSessions: []
        );

        $this->assertStringContainsString('DRAFT', $prompt);
        $this->assertStringContainsString('2026-04-20', $prompt);
    }

    public function test_extract_json_various_formats(): void
    {
        $service = new ExamSchedulingAssistantService;
        $method = new \ReflectionMethod($service, 'extractJsonFromText');
        $method->setAccessible(true);

        // Format 1: Clean wrapped JSON
        $json = '{"sessions": [{"room_id": 1, "date": "2026-05-20"}]}';
        $res = $method->invoke($service, $json);
        $this->assertNotNull($res);
        $this->assertEquals(1, $res['sessions'][0]['room_id']);

        // Format 2: JSON in code fences
        $fenced = "Here is the schedule:\n```json\n{\"sessions\": [{\"room_id\": 2, \"date\": \"2026-05-21\"}]}\n```";
        $res = $method->invoke($service, $fenced);
        $this->assertNotNull($res);
        $this->assertEquals(2, $res['sessions'][0]['room_id']);

        // Format 3: Multiple separate JSON objects on different lines
        $multi = "{\n  \"room_id\": 3,\n  \"date\": \"2026-05-22\"\n}\n{\n  \"room_id\": 4,\n  \"date\": \"2026-05-23\"\n}";
        $res = $method->invoke($service, $multi);
        $this->assertNotNull($res);
        $this->assertCount(2, $res['sessions']);
        $this->assertEquals(3, $res['sessions'][0]['room_id']);
        $this->assertEquals(4, $res['sessions'][1]['room_id']);

        // Format 4: Bare array
        $array = '[{"room_id": 5, "date": "2026-05-24"}]';
        $res = $method->invoke($service, $array);
        $this->assertNotNull($res);
        $this->assertEquals(5, $res['sessions'][0]['room_id']);

        // Format 5: Single session object without wrapper
        $singleObj = '{"room_id": 6, "date": "2026-05-25"}';
        $res = $method->invoke($service, $singleObj);
        $this->assertNotNull($res);
        $this->assertEquals(6, $res['sessions'][0]['room_id']);
    }

    public function test_apply_schedule_allows_empty_applicant_ids(): void
    {
        $user = User::factory()->create();
        $user->assignRole('registrar_administrator');

        $year = AcademicYear::factory()->create(['academic_year' => '2025-2026', 'semester' => '1', 'is_active' => true]);
        $room = Room::factory()->create(['is_active' => true]);

        $payload = [
            'sessions' => [
                [
                    'room_id' => $room->id,
                    'date' => '2026-05-25',
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'applicant_ids' => [], // Empty applicant IDs
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson('/admin/exam-scheduling/schedule-assistant/apply-schedule', $payload)
            ->assertOk()
            ->assertJsonStructure(['message', 'redirect_url']);

        $this->assertDatabaseHas('exam_sessions', [
            'room_id' => $room->id,
            'date' => '2026-05-25 00:00:00',
            'start_time' => '09:00',
            'status' => ExamSession::STATUS_DRAFT,
        ]);
    }

    public function test_apply_schedule_can_edit_existing_draft_session(): void
    {
        $user = User::factory()->create();
        $user->assignRole('registrar_administrator');

        $year = AcademicYear::factory()->create(['academic_year' => '2025-2026', 'semester' => '1', 'is_active' => true]);
        $room1 = Room::factory()->create(['is_active' => true]);
        $room2 = Room::factory()->create(['is_active' => true]);

        $session = ExamSession::create([
            'academic_year_id' => $year->id,
            'room_id' => $room1->id,
            'date' => '2026-05-25',
            'start_time' => '09:00',
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);

        $payload = [
            'sessions' => [
                [
                    'action' => 'edit',
                    'exam_session_id' => $session->id,
                    'room_id' => $room2->id,
                    'date' => '2026-05-26',
                    'start_time' => '10:00',
                    'end_time' => '13:00',
                    'applicant_ids' => [],
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson('/admin/exam-scheduling/schedule-assistant/apply-schedule', $payload)
            ->assertOk();

        $this->assertDatabaseHas('exam_sessions', [
            'id' => $session->id,
            'room_id' => $room2->id,
            'date' => '2026-05-26 00:00:00',
            'start_time' => '10:00',
            'end_time' => '13:00',
        ]);
    }

    public function test_apply_schedule_prevents_conflict_on_edit(): void
    {
        $user = User::factory()->create();
        $user->assignRole('registrar_administrator');

        $year = AcademicYear::factory()->create(['academic_year' => '2025-2026', 'semester' => '1', 'is_active' => true]);
        $room = Room::factory()->create(['is_active' => true]);

        // Existing session that occupies the room at 2026-05-25 09:00 to 12:00
        $existingSession = ExamSession::create([
            'academic_year_id' => $year->id,
            'room_id' => $room->id,
            'date' => '2026-05-25',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);

        // Another draft session that we want to edit and change to the same slot
        $targetSession = ExamSession::create([
            'academic_year_id' => $year->id,
            'room_id' => $room->id,
            'date' => '2026-05-26',
            'start_time' => '09:00',
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);

        $payload = [
            'sessions' => [
                [
                    'action' => 'edit',
                    'exam_session_id' => $targetSession->id,
                    'date' => '2026-05-25',
                    'start_time' => '10:00', // overlaps with 09:00 - 12:00
                    'end_time' => '11:00',
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson('/admin/exam-scheduling/schedule-assistant/apply-schedule', $payload)
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => "Room {$room->id} has a conflict on 2026-05-25 at 10:00.",
            ]);
    }

    public function test_apply_schedule_can_delete_existing_draft_session(): void
    {
        $user = User::factory()->create();
        $user->assignRole('registrar_administrator');

        $year = AcademicYear::factory()->create(['academic_year' => '2025-2026', 'semester' => '1', 'is_active' => true]);
        $room = Room::factory()->create(['is_active' => true]);

        $session = ExamSession::create([
            'academic_year_id' => $year->id,
            'room_id' => $room->id,
            'date' => '2026-05-25',
            'start_time' => '09:00',
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);

        $application = Application::factory()->create([
            'status' => 'accepted',
            'pipeline_status' => 'draft_scheduled',
        ]);
        $applicant = Applicant::factory()->create([
            'application_id' => $application->id,
        ]);
        $session->applicants()->attach($applicant->id);

        $payload = [
            'sessions' => [
                [
                    'action' => 'delete',
                    'exam_session_id' => $session->id,
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson('/admin/exam-scheduling/schedule-assistant/apply-schedule', $payload)
            ->assertOk();

        // Verify the session is deleted
        $this->assertNull(ExamSession::find($session->id));

        // Verify the applicant is detached
        $this->assertFalse($session->applicants()->where('applicants.id', $applicant->id)->exists());

        // Verify the applicant application is reverted to accepted
        $this->assertEquals('accepted', $application->fresh()->status);
        $this->assertEquals('accepted', $application->fresh()->pipeline_status);

        // Verify audit log is recorded
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'exam_session.deleted',
            'auditable_type' => ExamSession::class,
            'auditable_id' => $session->id,
        ]);
    }

    public function test_apply_schedule_can_create_and_assign_via_service(): void
    {
        $user = User::factory()->create();
        $user->assignRole('registrar_administrator');

        $year = AcademicYear::factory()->create(['academic_year' => '2025-2026', 'semester' => '1', 'is_active' => true]);
        $room = Room::factory()->create(['is_active' => true, 'capacity' => 10]);

        $application = Application::factory()->create([
            'status' => 'accepted',
            'pipeline_status' => 'accepted',
            'academic_year_id' => $year->id,
        ]);
        $applicant = Applicant::factory()->create([
            'application_id' => $application->id,
        ]);

        $payload = [
            'sessions' => [
                [
                    'action' => 'create',
                    'room_id' => $room->id,
                    'date' => '2026-05-25',
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'applicant_ids' => [$applicant->id],
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson('/admin/exam-scheduling/schedule-assistant/apply-schedule', $payload)
            ->assertOk();

        // Verify the session is created
        $session = ExamSession::where('room_id', $room->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('2026-05-25', $session->date->format('Y-m-d'));
        $this->assertEquals('09:00', $session->start_time);
        $this->assertEquals('12:00', $session->end_time);
        $this->assertEquals(ExamSession::STATUS_DRAFT, $session->status);

        // Verify the applicant is attached
        $this->assertTrue($session->applicants()->where('applicants.id', $applicant->id)->exists());

        // Verify applicant's application status is transitioned
        $this->assertEquals('draft_scheduled', $application->fresh()->pipeline_status);
    }
}
