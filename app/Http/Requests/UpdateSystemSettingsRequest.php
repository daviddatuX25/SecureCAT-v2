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
            'notify_on_publish'         => ['sometimes', 'boolean'],
            'release_mode'              => ['sometimes', 'in:online,f2f,both'],
        ];
    }
}
