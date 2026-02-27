<?php

namespace App\Http\Requests\Proctor;

use Illuminate\Foundation\Http\FormRequest;

class ScanAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageRoster', $this->route('exam_session'));
    }

    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:50'],
        ];
    }
}
