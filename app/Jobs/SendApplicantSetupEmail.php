<?php

namespace App\Jobs;

use App\Mail\ApplicantSetupMail;
use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendApplicantSetupEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Applicant $applicant
    ) {}

    public function handle(): void
    {
        if (! $this->applicant->setup_token || ! $this->applicant->isSetupTokenValid()) {
            return;
        }

        Mail::to($this->applicant->email)->send(new ApplicantSetupMail($this->applicant));
    }
}
