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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'pending' => 'Pending Review',
            'accepted' => 'Accepted',
            'dismissed' => 'Dismissed',
        ];

        $oldLabel = $statusLabels[$this->oldStatus] ?? ucfirst($this->oldStatus);
        $newLabel = $statusLabels[$this->newStatus] ?? ucfirst($this->newStatus);

        return (new MailMessage)
            ->subject("Application Status Updated: {$newLabel}")
            ->greeting('Hello, '.($notifiable->name ?? 'Applicant').'!')
            ->line("Your application status has been updated from **{$oldLabel}** to **{$newLabel}**.")
            ->when($this->newStatus === 'accepted', fn ($mail) => $mail
                ->line('Congratulations! Your application has been accepted.'))
            ->when($this->newStatus === 'dismissed', fn ($mail) => $mail
                ->line('We regret to inform you that your application was not accepted at this time.'))
            ->action('View Application', url('/portal/application'));
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
