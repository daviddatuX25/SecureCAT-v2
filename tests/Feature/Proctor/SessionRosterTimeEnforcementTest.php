<?php

namespace Tests\Feature\Proctor;

use App\Models\ExamSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionRosterTimeEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(string $date, string $start, ?string $end): ExamSession
    {
        return ExamSession::factory()->create([
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => ExamSession::STATUS_PUBLISHED,
        ]);
    }

    public function test_is_within_exam_window_during_window(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertTrue($session->isWithinExamWindow());
    }

    public function test_is_not_within_exam_window_before_start(): void
    {
        Carbon::setTestNow('2026-05-01 08:59:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertFalse($session->isWithinExamWindow());
    }

    public function test_is_not_within_exam_window_after_end(): void
    {
        Carbon::setTestNow('2026-05-01 12:01:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertFalse($session->isWithinExamWindow());
    }

    public function test_is_past_end_time_after_end(): void
    {
        Carbon::setTestNow('2026-05-01 12:01:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertTrue($session->isPastEndTime());
    }

    public function test_is_not_past_end_time_during_window(): void
    {
        Carbon::setTestNow('2026-05-01 10:00:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
        $this->assertFalse($session->isPastEndTime());
    }

    public function test_is_not_past_end_time_when_no_end_time(): void
    {
        Carbon::setTestNow('2026-05-01 23:00:00');
        $session = $this->makeSession('2026-05-01', '09:00:00', null);
        $this->assertFalse($session->isPastEndTime());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
