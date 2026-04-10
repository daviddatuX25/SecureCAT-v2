<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ExamSchedulingAssistantController;
use App\Models\AcademicYear;
use App\Models\Applicant;
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
            ->deleteJson('/admin/test-scheduling/schedule-assistant/conversation')
            ->assertOk()
            ->assertJson(['message' => 'Conversation reset.']);

        $this->assertDatabaseMissing('exam_scheduling_conversations', ['user_id' => $user->id]);
    }

    public function test_clear_conversation_returns_403_for_unauthorized_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        $this->actingAs($user)
            ->deleteJson('/admin/test-scheduling/schedule-assistant/conversation')
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
}
