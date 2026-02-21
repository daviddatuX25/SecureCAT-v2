<?php

namespace App\Http\Requests;

use App\Models\ExamSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string', 'date_format:H:i'],
            'end_time' => ['nullable', 'string', 'date_format:H:i', 'after:start_time'],
            'proctor_ids' => ['nullable', 'array'],
            'proctor_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $roomId = (int) $this->input('room_id');
            $date = $this->input('date');
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');
            if (ExamSession::hasRoomConflict($roomId, $date, $startTime, $endTime)) {
                $validator->errors()->add('room_id', 'This room is already scheduled at the selected date and time.');
            }
        });
    }
}
