<?php

namespace App\Http\Requests;

use App\Models\DecisionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDecisionRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'counselor']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'domain_id' => ['nullable', 'integer', 'exists:exam_domains,id'],
            'min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:100', 'gte:min_score'],
            'note' => ['required', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $courseId = (int) $this->input('course_id');
            $domainId = $this->input('domain_id') ? (int) $this->input('domain_id') : null;
            $minScore = (float) $this->input('min_score');
            $maxScore = (float) $this->input('max_score');

            $overlapping = DecisionRule::query()
                ->where('course_id', $courseId)
                ->where('domain_id', $domainId)
                ->where('is_active', true)
                ->where(function ($q) use ($minScore, $maxScore) {
                    $q->where('min_score', '<=', $maxScore)
                        ->where('max_score', '>=', $minScore);
                })
                ->exists();

            if ($overlapping) {
                $validator->errors()->add(
                    'min_score',
                    'This score range overlaps with an existing rule for the same course and pillar.'
                );
            }
        });
    }
}
