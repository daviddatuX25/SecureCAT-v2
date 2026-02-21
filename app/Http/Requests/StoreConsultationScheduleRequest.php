<?php

namespace App\Http\Requests;

use App\Models\GradingSession;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'counselor']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     * Per E-026: When grading_session_id provided, applicant_ids must belong to that session.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date'],
            'applicant_ids' => [
                'required',
                'array',
                $this->applicantsMustBelongToGradingSession(),
            ],
            'applicant_ids.*' => ['integer', 'exists:applicants,id'],
            'grading_session_id' => ['nullable', 'integer', 'exists:grading_sessions,id'],
        ];
    }

    private function applicantsMustBelongToGradingSession(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $gradingSessionId = $this->input('grading_session_id');
            if (! $gradingSessionId) {
                return;
            }
            $session = GradingSession::find($gradingSessionId);
            if (! $session) {
                return;
            }
            $memberIds = $session->applicants()->pluck('applicants.id')->all();
            $invalid = array_diff(array_map('intval', (array) $value), $memberIds);
            if (! empty($invalid)) {
                $fail('One or more applicants are not part of the selected grading session.');
            }
        };
    }
}
