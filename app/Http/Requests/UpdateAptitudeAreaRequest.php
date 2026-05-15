<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAptitudeAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'test_administrator']) ?? false;
    }

    public function rules(): array
    {
        $aptitudeArea = $this->route('aptitude_area');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', Rule::unique('aptitude_areas', 'code')->ignore($aptitudeArea?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_items' => ['required', 'integer', 'min:1', 'max:999'],
            'formula' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active') && $this->is_active === '') {
            $this->merge(['is_active' => true]);
        }
    }
}
