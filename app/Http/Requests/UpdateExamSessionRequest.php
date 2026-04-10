<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['sometimes', 'nullable', 'integer', 'exists:academic_years,id'],
            'room_id' => ['sometimes', 'integer', 'exists:rooms,id'],
            'date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'string', 'date_format:H:i'],
            'end_time' => ['nullable', 'string', 'date_format:H:i'],
            'proctor_ids' => ['sometimes', 'array'],
            'proctor_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
