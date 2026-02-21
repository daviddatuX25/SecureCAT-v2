<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateConsultationSummaryRequest extends FormRequest
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
     * Per E-030: recommended_course_id must be in applicant's preferences when set.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:pending,draft,released'],
            'recommended_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'counselor_comments' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $courseId = $this->input('recommended_course_id');
            if ($courseId === null) {
                return;
            }
            $applicant = $this->route('applicant');
            if (! $applicant || ! $applicant->application) {
                return;
            }
            $prefs = [
                $applicant->application->course_preference_1,
                $applicant->application->course_preference_2,
                $applicant->application->course_preference_3,
            ];
            if (! in_array((int) $courseId, array_map('intval', array_filter($prefs)), true)) {
                $validator->errors()->add(
                    'recommended_course_id',
                    'Recommended course must be one of the applicant\'s course preferences.'
                );
            }
        });
    }
}
