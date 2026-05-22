<?php

namespace Tests\Unit\Notifications;

use App\Models\Applicant;
use App\Models\ConsultationSummary;
use App\Models\Course;
use App\Notifications\ResultReleasedF2F;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultReleasedF2FTest extends TestCase
{
    use RefreshDatabase;

    private function createSummary(): ConsultationSummary
    {
        $course = Course::factory()->create(['is_active' => true]);
        $applicant = Applicant::factory()->create();

        return ConsultationSummary::factory()->create([
            'applicant_id' => $applicant->id,
            'recommended_course_id' => $course->id,
            'status' => 'draft',
        ]);
    }

    public function test_notification_stores_correct_data_array(): void
    {
        $summary = $this->createSummary();
        $applicant = $summary->applicant;
        $notification = new ResultReleasedF2F($summary);

        $array = $notification->toArray($applicant);

        $this->assertEquals('result_released_f2f', $array['type']);
        $this->assertEquals($summary->id, $array['summary_id']);
        $this->assertStringContainsString('face-to-face consultation', $array['message']);
    }

    public function test_notification_via_includes_mail_and_database(): void
    {
        $summary = $this->createSummary();
        $applicant = $summary->applicant;
        $notification = new ResultReleasedF2F($summary);

        $via = $notification->via($applicant);

        $this->assertContains('mail', $via);
        $this->assertContains('database', $via);
    }

    public function test_notification_is_queued(): void
    {
        $summary = $this->createSummary();
        $notification = new ResultReleasedF2F($summary);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
    }

    public function test_notification_has_no_action_button_in_mail(): void
    {
        $summary = $this->createSummary();
        $applicant = $summary->applicant;
        $notification = new ResultReleasedF2F($summary);

        $mailMessage = $notification->toMail($applicant);

        $rendered = $mailMessage->render();
        $this->assertStringNotContainsString('View in Portal', $rendered);
        $this->assertStringNotContainsString('actionUrl', $rendered);
    }
}
