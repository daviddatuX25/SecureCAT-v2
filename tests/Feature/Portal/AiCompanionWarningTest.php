<?php

namespace Tests\Feature\Portal;

use App\Models\AiCompanionMessage;
use App\Models\Applicant;
use App\Models\ConsultationSummary;
use App\Models\SystemSetting;
use App\Services\AiCompanionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiCompanionWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        SystemSetting::set('ai_exam_companion_enabled', true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * T3: Message length warnings appear at 1800+ chars.
     * Test: Send 1801 char message, assert response contains warning.
     */
    public function test_message_over_1800_chars_returns_warning(): void
    {
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        $longMessage = str_repeat('x', 1801);

        $this->mock(AiCompanionService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn(['reply' => 'Response']);
        });

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => $longMessage]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply', 'warning']);
        $response->assertJsonPath('warning.length', 'You are approaching the 2000 character limit.');
    }

    /**
     * T3: Message at exactly 1800 chars does NOT trigger warning.
     */
    public function test_message_at_1800_chars_no_warning(): void
    {
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        $message = str_repeat('x', 1800);

        $this->mock(AiCompanionService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn(['reply' => 'Response']);
        });

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => $message]);

        $response->assertStatus(200);
        $this->assertNull($response->json('warning'));
    }

    /**
     * T4: History limit warnings appear at 17+ messages.
     * Test: Create 17 messages, send 18th, assert response contains warning.
     */
    public function test_17_messages_returns_history_warning_on_18th(): void
    {
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        // Create 17 messages (8 user + 8 assistant + 1 user = 17)
        // Actually, let's create exactly 17 to reach the threshold
        for ($i = 0; $i < 17; $i++) {
            $role = $i % 2 === 0 ? 'user' : 'assistant';
            AiCompanionMessage::create([
                'applicant_id' => $applicant->id,
                'role' => $role,
                'content' => "Message {$i}",
                'created_at' => now(),
            ]);
        }

        $this->mock(AiCompanionService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn(['reply' => 'Response']);
        });

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'New message']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply', 'warning']);
        $response->assertJsonPath('warning.history', 'You are approaching the message history limit. Consider clearing history.');
    }

    /**
     * T4: 16 messages does NOT trigger history warning.
     */
    public function test_16_messages_no_history_warning(): void
    {
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        // Create 16 messages
        for ($i = 0; $i < 16; $i++) {
            $role = $i % 2 === 0 ? 'user' : 'assistant';
            AiCompanionMessage::create([
                'applicant_id' => $applicant->id,
                'role' => $role,
                'content' => "Message {$i}",
                'created_at' => now(),
            ]);
        }

        $this->mock(AiCompanionService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn(['reply' => 'Response']);
        });

        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'New message']);

        $response->assertStatus(200);
        $this->assertNull($response->json('warning'));
    }

    private function createReleasedConsultation(Applicant $applicant): void
    {
        ConsultationSummary::create([
            'applicant_id' => $applicant->id,
            'status' => 'released',
        ]);
    }
}
