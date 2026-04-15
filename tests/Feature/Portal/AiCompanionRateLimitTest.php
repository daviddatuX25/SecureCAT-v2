<?php

namespace Tests\Feature\Portal;

use App\Models\Applicant;
use App\Models\ConsultationSummary;
use App\Models\SystemSetting;
use Database\Seeders\RoleSeeder;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AiCompanionRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        SystemSetting::set('ai_exam_companion_enabled', true);
    }

    /**
     * T1: Rate limiting enforced per applicant (10 req/min).
     * Test: Make 11 requests in 1 minute, 11th should be rate limited.
     */
    public function test_rate_limit_blocks_11th_request_in_one_minute(): void
    {
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        // Clear any existing rate limit hits
        RateLimiter::clear('ai-companion:'.$applicant->id);

        // Make 10 requests - all should succeed (mock to avoid actual API calls)
        for ($i = 0; $i < 10; $i++) {
            $this->bindFakeGuzzleResponse("Response {$i}");

            $response = $this->actingAs($applicant, 'applicant')
                ->postJson('/portal/ai-companion/chat', ['message' => "Message {$i}"]);

            $response->assertStatus(200);
        }

        // 11th request should be rate limited (429)
        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'Message 11']);

        $response->assertStatus(429);
    }

    /**
     * T2: Users receive friendly error when rate limited.
     * Test: Assert response contains JSON with message about rate limit.
     */
    public function test_rate_limit_returns_friendly_error_message(): void
    {
        $applicant = Applicant::factory()->create();
        $this->createReleasedConsultation($applicant);

        // Clear any existing rate limit
        RateLimiter::clear('ai-companion:'.$applicant->id);

        // Exhaust the rate limit
        for ($i = 0; $i < 10; $i++) {
            $this->bindFakeGuzzleResponse("Response {$i}");
            $this->actingAs($applicant, 'applicant')
                ->postJson('/portal/ai-companion/chat', ['message' => "Message {$i}"]);
        }

        // Now the rate limit should kick in
        $response = $this->actingAs($applicant, 'applicant')
            ->postJson('/portal/ai-companion/chat', ['message' => 'Test message']);

        $response->assertStatus(429);
        $response->assertJson(['message' => 'Too many requests. Please wait a moment.']);
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

        $mock = $this->createMock(ClientInterface::class);
        $mock->expects($this->once())
            ->method('request')
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $this->app->instance(ClientInterface::class, $mock);
    }
}
