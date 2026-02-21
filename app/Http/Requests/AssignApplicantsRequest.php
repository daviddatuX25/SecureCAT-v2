<?php

namespace App\Http\Requests;

use App\Models\Applicant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignApplicantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role check only; "completed session" is handled in controller for a proper redirect + flash message
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'applicant_ids' => ['required', 'array'],
            'applicant_ids.*' => ['integer', 'exists:applicants,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            /** @var \App\Models\ExamSession $examSession */
            $examSession = $this->route('exam_session');
            $examSession->load('room:id,capacity');
            $applicantIds = array_values(array_unique(array_map('intval', $this->input('applicant_ids', []))));

            // Only accepted applicants (per 08-API-SPEC-PHASE1)
            $acceptedIds = Applicant::query()
                ->whereIn('id', $applicantIds)
                ->whereHas('application', fn ($q) => $q->where('status', 'accepted'))
                ->pluck('id')
                ->all();
            $notAccepted = array_diff($applicantIds, $acceptedIds);
            if (! empty($notAccepted)) {
                $validator->errors()->add('applicant_ids', 'All applicants must have accepted applications. Invalid: '.implode(', ', $notAccepted));
                return;
            }

            // Applicants already assigned to another session (not this one)
            $alreadyInOtherSession = Applicant::query()
                ->whereIn('id', $acceptedIds)
                ->whereHas('examSessions', fn ($q) => $q->where('exam_sessions.id', '!=', $examSession->id))
                ->pluck('id')
                ->all();
            if (! empty($alreadyInOtherSession)) {
                $validator->errors()->add('applicant_ids', 'Some applicants are already assigned to another exam session.');
                return;
            }

            // Capacity: current + new (not already in this session) <= room capacity
            $currentCount = $examSession->applicants()->count();
            $alreadyInThisSession = $examSession->applicants()->whereIn('applicants.id', $acceptedIds)->pluck('applicants.id')->all();
            $newCount = count($acceptedIds) - count($alreadyInThisSession);
            $capacity = (int) $examSession->room->capacity;
            if ($currentCount + $newCount > $capacity) {
                $validator->errors()->add('applicant_ids', "Room capacity is {$capacity}. Current assignments: {$currentCount}. Adding {$newCount} would exceed capacity.");
            }
        });
    }
}
