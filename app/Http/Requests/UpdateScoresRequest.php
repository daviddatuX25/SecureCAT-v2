<?php

namespace App\Http\Requests;

use App\Models\ExamDomain;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScoresRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'grader']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     * Per E-014: raw_score cannot exceed max_score.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scores' => [
                'required',
                'array',
                $this->domainIdsMustBeActive(),
                $this->rawScoreMustNotExceedMax(),
            ],
            'scores.*' => ['array'],
            'scores.*.raw_score' => ['required', 'integer', 'min:0'],
            'scores.*.max_score' => ['required', 'integer', 'min:0'],
        ];
    }

    /** Per E-015: Score keys must be valid active exam domains. */
    private function domainIdsMustBeActive(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $keys = array_map('intval', array_keys((array) $value));
            $validIds = ExamDomain::where('is_active', true)->pluck('id')->all();
            $invalid = array_diff($keys, $validIds);
            if (! empty($invalid)) {
                $fail('Invalid or inactive domain IDs: ' . implode(', ', $invalid));
            }
        };
    }

    private function rawScoreMustNotExceedMax(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            foreach ((array) $value as $domainId => $data) {
                $raw = (int) ($data['raw_score'] ?? 0);
                $max = (int) ($data['max_score'] ?? 0);
                if ($max > 0 && $raw > $max) {
                    $fail("Raw score cannot exceed max score ({$raw} > {$max}).");
                    return;
                }
            }
        };
    }
}
