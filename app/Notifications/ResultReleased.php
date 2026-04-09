<?php

namespace App\Notifications;

use App\Models\ConsultationSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultReleased extends Notification implements ShouldQueue
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
            ->subject('Your exam results are now available')
            ->greeting('Hello, ' . ($notifiable->name ?? 'Applicant') . '!')
            ->line('Your exam results have been released and are now available.')
            ->action('View in Portal', url('/portal'))
            ->line('If you have questions, please contact the guidance office.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'result_released',
            'summary_id' => $this->summary->id,
            'message'    => 'Your exam results are now available.',
        ];
    }
}
