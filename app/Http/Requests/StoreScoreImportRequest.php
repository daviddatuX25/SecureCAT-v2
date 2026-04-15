<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'test_administrator']);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'grading_session_id' => ['required', 'integer', 'exists:grading_sessions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a CSV file.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'File must be a CSV (.csv or .txt).',
            'file.max' => 'File must not exceed 10MB.',
            'grading_session_id.required' => 'Please select a grading session.',
            'grading_session_id.exists' => 'The selected grading session is invalid.',
        ];
    }
}
