<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class MarkPrintedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'grader']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     * Per E-022: applicant_ids must belong to the grading session.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'applicant_ids' => [
                'required',
                'array',
                $this->applicantsMustBeSessionMembers(),
            ],
            'applicant_ids.*' => ['integer', 'exists:applicants,id'],
            'printed' => ['required', 'boolean'],
        ];
    }

    private function applicantsMustBeSessionMembers(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $gradingSession = $this->route('grading_session');
            if (! $gradingSession) {
                return;
            }
            $memberIds = $gradingSession->applicants()->pluck('applicants.id')->all();
            $invalid = array_diff(array_map('intval', (array) $value), $memberIds);
            if (! empty($invalid)) {
                $fail('One or more applicants are not part of this grading session.');
            }
        };
    }
}
