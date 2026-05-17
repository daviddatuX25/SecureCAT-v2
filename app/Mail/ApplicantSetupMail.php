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

    public string $applicantName;

    public ?string $referenceNumber;

    public int $tokenExpiryHours;

    public function __construct(
        public Applicant $applicant
    ) {
        $this->applicant->loadMissing('application');
        $application = $this->applicant->application;

        $this->setupUrl = url("/portal/setup/{$applicant->setup_token}");
        $this->applicantName = trim(($application?->first_name ?? '').' '.($application?->last_name ?? '')) ?: 'Applicant';
        $this->referenceNumber = $application?->reference_number;
        $this->tokenExpiryHours = (int) config('auth.setup_token_expires_hours', 72);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SecureCAT — Application Accepted: Set Up Your Portal',
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
