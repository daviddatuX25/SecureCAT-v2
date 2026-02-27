<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'ai_exam_companion_enabled' => ['sometimes', 'boolean'],
            'ai_companion_persona' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'consultation_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Sanitize persona: plain text only (T3.4).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('ai_companion_persona') && is_string($this->ai_companion_persona)) {
            $this->merge(['ai_companion_persona' => strip_tags($this->ai_companion_persona)]);
        }
    }
}
