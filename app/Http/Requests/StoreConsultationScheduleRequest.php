<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin', 'counselor']) ?? false;
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'grading_session_id' => ['required', 'integer', 'exists:grading_sessions,id'],
            'applicant_ids' => ['required', 'array', 'min:1'],
            'applicant_ids.*' => ['required', 'integer', 'exists:applicants,id'],
        ];
    }
}
