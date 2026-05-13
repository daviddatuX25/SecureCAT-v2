<?php

namespace Tests\Feature;

use App\Models\ExamSession;
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
        $this->assertEquals(ExamSession::TYPE_DIRECT, $session->type);
        $this->assertNull($session->room_id);
        $this->assertNull($session->end_time);
        $this->assertEquals('in_progress', $session->status);
        $this->assertNotNull($session->label);
    }
}
