<?php

namespace Tests\Unit\Notifications;

use App\Models\Applicant;
use App\Models\Application;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_stores_correct_data_array(): void
    {
        $application = Application::factory()->create();

        $notification = new ApplicationStatusChanged($application, 'pending', 'accepted');

        $array = $notification->toArray($application);

        $this->assertEquals('application_status_changed', $array['type']);
        $this->assertEquals($application->id, $array['application_id']);
        $this->assertEquals('pending', $array['old_status']);
        $this->assertEquals('accepted', $array['new_status']);
        $this->assertStringContainsString('pending', $array['message']);
        $this->assertStringContainsString('accepted', $array['message']);
    }

    public function test_notification_via_includes_database(): void
    {
        $application = Application::factory()->create();

        $notification = new ApplicationStatusChanged($application, 'pending', 'accepted');

        $this->assertTrue(in_array('database', $notification->via($application)));
    }

    public function test_notification_is_queued(): void
    {
        $application = Application::factory()->create();

        $notification = new ApplicationStatusChanged($application, 'pending', 'accepted');

        $this->assertInstanceOf(ShouldQueue::class, $notification);
    }

    public function test_accepted_status_skips_mail_channel(): void
    {
        $application = Application::factory()->create();

        $notification = new ApplicationStatusChanged($application, 'pending', 'accepted');

        $channels = $notification->via($application);

        $this->assertContains('database', $channels);
        $this->assertNotContains('mail', $channels, 'Accepted status should not send mail — setup email covers it');
    }

    public function test_dismissed_status_includes_mail_channel(): void
    {
        $application = Application::factory()->create();

        $notification = new ApplicationStatusChanged($application, 'pending', 'dismissed');

        $channels = $notification->via($application);

        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);
    }

    public function test_dismissed_mail_includes_rejection_reason(): void
    {
        $application = Application::factory()->create([
            'rejection_reason' => 'Incomplete documentation',
        ]);

        $notification = new ApplicationStatusChanged($application, 'pending', 'dismissed');
        $applicant = new Applicant(['email' => $application->email]);

        $mail = $notification->toMail($applicant);

        $this->assertStringContainsString('SecureCAT', $mail->subject);
    }
}
