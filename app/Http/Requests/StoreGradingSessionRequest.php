<?php

namespace App\Http\Requests;

use App\Models\ExamSession;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreGradingSessionRequest extends FormRequest
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
     * Per spec (E-005): grading can only be opened for completed exam sessions.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_session_id' => [
                'required',
                'integer',
                'exists:exam_sessions,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $examSession = ExamSession::find($value);
                    if (! $examSession || $examSession->status !== ExamSession::STATUS_COMPLETED) {
                        $fail('Grading can only be opened for completed exam sessions.');
                    }
                    if ($examSession->gradingSession()->exists()) {
                        $fail('A grading session already exists for this exam.');
                    }
                },
            ],
        ];
    }
}
