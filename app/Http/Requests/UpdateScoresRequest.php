<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scores'              => ['required', 'array'],
            'scores.*.domain_id'  => ['required', 'integer', 'exists:exam_domains,id'],
            'scores.*.raw_score'  => ['nullable', 'integer', 'min:0'],
            'scores.*.max_score'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
