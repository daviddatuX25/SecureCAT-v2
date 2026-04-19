<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExamSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamSessionCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ExamSession $session) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->session->date?->format('F j, Y') ?? 'TBA';

        return (new MailMessage)
            ->subject('Exam session cancelled')
            ->greeting('Hello, '.($notifiable->name ?? 'Applicant').'!')
            ->line("The exam session scheduled for {$date} has been cancelled.")
            ->action('View in Portal', url('/portal'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'session_cancelled',
            'session_id' => $this->session->id,
            'message' => "The exam session for {$this->session->room?->name} has been cancelled.",
        ];
    }
}
