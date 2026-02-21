<?php

namespace App\Http\Requests;

use App\Models\DecisionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDecisionRuleRequest extends FormRequest
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
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'domain_id' => ['nullable', 'integer', 'exists:exam_domains,id'],
            'min_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'note' => ['sometimes', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Configure the validator. Ensure the score range does not overlap with existing
     * rules for the same course and domain (pillar), excluding the rule being updated.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rule = $this->route('decision_rule');
            if (! $rule instanceof DecisionRule) {
                return;
            }

            $minScore = (float) ($this->input('min_score') ?? $rule->min_score);
            $maxScore = (float) ($this->input('max_score') ?? $rule->max_score);
            if ($this->has('min_score') || $this->has('max_score')) {
                if ($minScore > $maxScore) {
                    $validator->errors()->add('min_score', 'Min score cannot exceed max score.');
                    return;
                }
            }

            $courseId = (int) ($this->input('course_id') ?? $rule->course_id);
            $domainId = $this->has('domain_id')
                ? ($this->input('domain_id') ? (int) $this->input('domain_id') : null)
                : $rule->domain_id;

            $overlapping = DecisionRule::query()
                ->where('id', '!=', $rule->id)
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
