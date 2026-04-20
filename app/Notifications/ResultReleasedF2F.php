<?php

namespace App\Notifications;

use App\Models\ConsultationSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultReleasedF2F extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ConsultationSummary $summary) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your exam results are available for consultation')
            ->greeting('Hello, '.($notifiable->name ?? 'Applicant').'!')
            ->line('Your exam results are now available for face-to-face consultation.')
            ->line('Please wait for further announcement regarding the venue and schedule for your consultation.')
            ->line('If you have questions, please contact the guidance office.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'result_released_f2f',
            'summary_id' => $this->summary->id,
            'message' => 'Your exam results are available for face-to-face consultation. Please wait for further announcement regarding the venue and schedule.',
        ];
    }
}
