<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('courses', 'code')->ignore($course)],
            'quota' => ['nullable', 'integer', 'min:1'],
            'score_cutoff' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

