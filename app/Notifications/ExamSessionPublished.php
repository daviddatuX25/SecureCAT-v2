<?php

namespace App\Notifications;

use App\Models\ExamSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExamSessionPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ExamSession $session) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->session->scheduled_at?->format('F j, Y') ?? $this->session->date?->format('F j, Y') ?? 'TBA';
        $time = $this->session->scheduled_at?->format('g:i A') ?? '';
        $room = $this->session->room?->name ?? 'TBA';

        return (new MailMessage)
            ->subject('Your exam has been scheduled')
            ->greeting('Hello, ' . ($notifiable->name ?? 'Applicant') . '!')
            ->line('Your exam session has been confirmed.')
            ->line('**Date:** ' . $date)
            ->when($time, fn ($mail) => $mail->line('**Time:** ' . $time))
            ->line('**Room:** ' . $room)
            ->action('View in Portal', url('/portal'))
            ->line('Please arrive 15 minutes early with a valid ID.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'exam_session_published',
            'session_id' => $this->session->id,
            'message'    => 'Your exam session has been scheduled.',
        ];
    }
}
