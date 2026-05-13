<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        return $user;
    }

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
}
