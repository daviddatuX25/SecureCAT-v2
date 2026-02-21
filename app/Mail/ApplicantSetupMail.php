<?php

namespace App\Mail;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicantSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $setupUrl;

    public function __construct(
        public Applicant $applicant
    ) {
        $this->setupUrl = url("/portal/setup/{$applicant->setup_token}");
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SecureCAT — Set Up Your Applicant Portal Account',
            from: config('mail.from.address', 'noreply@securecat.local'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.applicant-setup',
        );
    }
}
