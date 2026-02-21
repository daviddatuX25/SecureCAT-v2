<?php

namespace App\Http\Requests;

use App\Models\ExamSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        $session = $this->route('exam_session');
        return [
            'room_id' => ['sometimes', 'integer', 'exists:rooms,id'],
            'date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'string', 'date_format:H:i'],
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
            /** @var ExamSession $session */
            $session = $this->route('exam_session');
            if ($session->status === ExamSession::STATUS_COMPLETED) {
                $validator->errors()->add('status', 'Cannot edit a completed session.');
                return;
            }
            $roomId = (int) ($this->input('room_id') ?? $session->room_id);
            $date = $this->input('date') ?? $session->date->format('Y-m-d');
            $startTime = $this->input('start_time') ?? substr($session->start_time, 0, 5);
            $endTime = $this->input('end_time');
            if ($endTime === null && $session->end_time) {
                $endTime = substr($session->end_time, 0, 5);
            }
            if (ExamSession::hasRoomConflict($roomId, $date, $startTime, $endTime, $session->id)) {
                $validator->errors()->add('room_id', 'This room is already scheduled at the selected date and time.');
            }
        });
    }
}
