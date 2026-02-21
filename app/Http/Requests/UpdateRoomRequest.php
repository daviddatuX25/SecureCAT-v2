<?php

namespace App\Http\Requests;

use App\Models\ExamSession;
use Closure;
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
            'capacity' => [
                'sometimes',
                'integer',
                'min:1',
                $this->capacityMustNotBeBelowAssignments($room),
            ],
            'facilities' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Per E-023: capacity cannot be reduced below current max assignments.
     * Max assignments = highest applicant count in any exam session using this room.
     */
    private function capacityMustNotBeBelowAssignments($room): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($room): void {
            $maxAssigned = ExamSession::query()
                ->where('room_id', $room->id)
                ->withCount('applicants')
                ->get()
                ->max('applicants_count') ?? 0;

            if ($maxAssigned > 0 && (int) $value < $maxAssigned) {
                $fail("Capacity cannot be reduced below {$maxAssigned} (current maximum applicants in any session using this room).");
            }
        };
    }
}
