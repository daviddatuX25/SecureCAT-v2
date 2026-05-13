<?php

namespace App\Http\Requests;

use App\Models\Applicant;
use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreDirectAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! SystemSetting::allowDirectAssessment()) {
            return false;
        }

        return $this->user()?->hasAnyRole(['super_admin', 'registrar_administrator', 'test_administrator']) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'applicant_ids' => ['required', 'array', 'min:1'],
            'applicant_ids.*' => [
                'integer',
                'exists:applicants,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $applicant = Applicant::find($value);
                    if (! $applicant) {
                        return;
                    }

                    if (! $applicant->application || $applicant->application->status !== 'accepted') {
                        $fail("Applicant ID {$value} does not have an accepted application.");
                    }

                    $inActiveSession = $applicant->examSessions()
                        ->whereHas('gradingSession', fn ($q) => $q->whereNotIn('status', ['finalized']))
                        ->exists();

                    if ($inActiveSession) {
                        $fail("Applicant ID {$value} is already in an active grading session.");
                    }
                },
            ],
            'label' => ['nullable', 'string', 'max:100'],
        ];
    }
}
