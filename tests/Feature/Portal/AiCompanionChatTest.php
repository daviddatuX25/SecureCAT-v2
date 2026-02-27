<?php

namespace Tests\Feature\Portal;

use App\Models\AiCompanionMessage;
use App\Models\Applicant;
use App\Models\ConsultationSummary;
use App\Models\KnowledgeDocument;
use App\Models\SystemSetting;
use App\Services\AiCompanionService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiCompanionChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_403_when_companion_disabled(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', false);
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'Hello']);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'AI companion is not enabled.']);
    }

    public function test_returns_403_when_consultation_not_released(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $applicant = Applicant::factory()->create();
        ConsultationSummary::create([
            'applicant_id' => $applicant->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'Hello']);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Results have not been released yet.');
    }

    public function test_returns_401_when_not_authenticated(): void
    {
        $response = $this->postJson('/portal/ai-companion/chat', ['message' => 'Hello']);

        $response->assertStatus(401);
    }

    public function test_validation_requires_message(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_validation_rejects_message_over_max_length(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => str_repeat('x', 2001)]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_returns_reply_when_ok(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        $this->mock(AiCompanionService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn(['reply' => 'Test reply from AI.']);
        });

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'What course fits my scores?']);

        $response->assertStatus(200);
        $response->assertJson(['reply' => 'Test reply from AI.']);
    }

    /** T5: System prompt includes institutional block; when no docs, contains "No institutional data available." */
    public function test_system_prompt_includes_institutional_data_block(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);
        $service = app(AiCompanionService::class);

        $prompt = $service->buildSystemPrompt($applicant);

        $this->assertStringContainsString('Institutional data (use only this; do not invent):', $prompt);
        $this->assertStringContainsString('No institutional data available.', $prompt);
        $this->assertStringContainsString('--- Applicant data ---', $prompt);
    }

    /** T5: When knowledge docs exist, system prompt includes retrieved content. */
    public function test_system_prompt_includes_retrieved_docs_when_present(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);
        KnowledgeDocument::create([
            'title' => 'Test doc',
            'content' => 'Institutional content for context.',
            'metadata' => [],
            'source' => 'manual',
            'is_active' => true,
        ]);
        $service = app(AiCompanionService::class);

        $prompt = $service->buildSystemPrompt($applicant);

        $this->assertStringContainsString('Institutional data (use only this; do not invent):', $prompt);
        $this->assertStringContainsString('Source: Test doc', $prompt);
        $this->assertStringContainsString('Institutional content for context.', $prompt);
        $this->assertStringNotContainsString('No institutional data available.', $prompt);
    }

    /** T7.1: First message → user + assistant stored. */
    public function test_first_message_stores_user_and_assistant(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        $this->bindFakeGuzzleResponse('I can help with that.');

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'Hello']);

        $response->assertStatus(200);
        $response->assertJson(['reply' => 'I can help with that.']);

        $this->assertDatabaseCount('ai_companion_messages', 2);
        $userMsg = AiCompanionMessage::where('applicant_id', $applicant->id)->where('role', 'user')->first();
        $assistantMsg = AiCompanionMessage::where('applicant_id', $applicant->id)->where('role', 'assistant')->first();
        $this->assertSame('Hello', $userMsg->content);
        $this->assertSame('I can help with that.', $assistantMsg->content);
    }

    /** T7.2: Follow-up message includes history. */
    public function test_follow_up_includes_history(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        AiCompanionMessage::create([
            'applicant_id' => $applicant->id,
            'role' => 'user',
            'content' => 'First question',
            'created_at' => now()->subMinute(),
        ]);
        AiCompanionMessage::create([
            'applicant_id' => $applicant->id,
            'role' => 'assistant',
            'content' => 'First answer',
            'created_at' => now(),
        ]);

        $this->bindFakeGuzzleResponse('Follow-up answer.');

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'Follow-up question']);

        $response->assertStatus(200);
        $this->assertDatabaseCount('ai_companion_messages', 4); // 2 existing + 2 new
    }

    /** T7.4: Clear history deletes all messages. */
    public function test_clear_history_deletes_messages(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        AiCompanionMessage::create([
            'applicant_id' => $applicant->id,
            'role' => 'user',
            'content' => 'Hello',
            'created_at' => now(),
        ]);
        AiCompanionMessage::create([
            'applicant_id' => $applicant->id,
            'role' => 'assistant',
            'content' => 'Hi there',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/clear-history');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'History cleared.']);
        $this->assertDatabaseCount('ai_companion_messages', 0);
    }

    /** T7: Chat page passes messages. */
    public function test_chat_page_passes_messages(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        AiCompanionMessage::create([
            'applicant_id' => $applicant->id,
            'role' => 'user',
            'content' => 'Previous',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($applicant, 'applicant')
            ->get('/portal/ai-companion');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Portal/AiCompanion')
            ->has('messages')
            ->where('messages.0.role', 'user')
            ->where('messages.0.content', 'Previous')
        );
    }

    private function createReleasedConsultation(Applicant $applicant): void
    {
        ConsultationSummary::create([
            'applicant_id' => $applicant->id,
            'status' => 'released',
        ]);
    }

    private function bindFakeGuzzleResponse(string $content): void
    {
        $json = json_encode([
            'id' => 'test-id',
            'model' => 'test',
            'object' => 'chat.completion',
            'created' => time(),
            'choices' => [
                ['message' => ['content' => $content]],
            ],
        ]);

        $mock = Mockery::mock(ClientInterface::class);
        $mock->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $this->app->instance(ClientInterface::class, $mock);
    }
}
