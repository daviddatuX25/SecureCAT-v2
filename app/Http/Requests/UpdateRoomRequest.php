<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        $room = $this->route('room');
        $building = $this->input('building', $room->building);

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('rooms', 'name')->where('building', $building)->ignore($room),
            ],
            'building' => ['sometimes', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:20'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'facilities' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
