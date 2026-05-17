<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Application $application,
        public readonly string $oldStatus,
        public readonly string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        // Skip mail for 'accepted' — the account setup email covers the acceptance notification
        if ($this->newStatus === 'accepted') {
            return ['database'];
        }

        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $applicantName = $this->application->first_name ?? 'Applicant';

        $mail = (new MailMessage)
            ->subject('SecureCAT — Application Update')
            ->greeting("Hello, {$applicantName}.");

        if ($this->application->reference_number) {
            $mail->line("Regarding your application **{$this->application->reference_number}**:");
        }

        if ($this->newStatus === 'dismissed') {
            $mail->line('After careful review, we regret to inform you that your application was not accepted at this time.');

            if ($this->application->rejection_reason) {
                $mail->line("**Reason:** {$this->application->rejection_reason}");
            }

            $mail->line('If you have any questions about this decision, please contact the admissions office.');
        } else {
            $statusLabels = [
                'pending' => 'Pending Review',
                'accepted' => 'Accepted',
                'dismissed' => 'Dismissed',
            ];
            $newLabel = $statusLabels[$this->newStatus] ?? ucfirst($this->newStatus);
            $mail->line("Your application status has been updated to **{$newLabel}**.");
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'application_status_changed',
            'application_id' => $this->application->id,
            'message' => "Your application status changed from {$this->oldStatus} to {$this->newStatus}",
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
