<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExamSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamSessionReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ExamSession $session,
        public readonly int $daysUntil
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->session->scheduled_at?->format('F j, Y') ?? $this->session->date?->format('F j, Y') ?? 'TBA';
        $time = $this->session->scheduled_at?->format('g:i A') ?? '';

        $daysLabel = $this->daysUntil === 1 ? 'day' : 'days';

        return (new MailMessage)
            ->subject("Reminder: Your exam is {$this->daysUntil} {$daysLabel} away")
            ->greeting('Hello, '.($notifiable->name ?? 'Applicant').'!')
            ->line("This is a reminder that your exam session is scheduled for **{$date}**.")
            ->when($time, fn ($mail) => $mail->line("**Time:** {$time}"))
            ->line('Please arrive 15 minutes early with a valid ID.')
            ->action('View Details', url('/portal'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'exam_session_reminder',
            'session_id' => $this->session->id,
            'message' => "Reminder: Your exam is {$this->daysUntil} day(s) away",
            'days_until' => $this->daysUntil,
        ];
    }
}
