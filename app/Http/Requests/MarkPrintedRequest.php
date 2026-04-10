<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkPrintedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'test_administrator']) ?? false;
    }

    public function rules(): array
    {
        return [
            'applicant_ids' => ['required', 'array', 'min:1'],
            'applicant_ids.*' => ['required', 'integer', 'exists:applicants,id'],
            'printed' => ['required', 'boolean'],
        ];
    }
}
