<?php

namespace Tests\Unit\Notifications;

use App\Models\ExamSession;
use App\Notifications\ExamSessionReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSessionReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_stores_correct_data_array(): void
    {
        $session = ExamSession::factory()->create([
            'date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        $notification = new ExamSessionReminder($session, 1);

        $array = $notification->toArray($session);

        $this->assertEquals('exam_session_reminder', $array['type']);
        $this->assertEquals($session->id, $array['session_id']);
        $this->assertEquals(1, $array['days_until']);
        $this->assertStringContainsString('1 day', $array['message']);
    }

    public function test_notification_via_includes_database(): void
    {
        $session = ExamSession::factory()->create();

        $notification = new ExamSessionReminder($session, 3);

        $this->assertTrue(in_array('database', $notification->via($session)));
    }

    public function test_notification_is_queued(): void
    {
        $session = ExamSession::factory()->create();

        $notification = new ExamSessionReminder($session, 1);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
    }
}
