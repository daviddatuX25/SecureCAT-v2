<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\DecisionRule;
use App\Models\GradingSession;
use Illuminate\Support\Collection;

class DecisionRuleService
{
    /**
     * Match applicant's normalized scores against decision rules.
     * Returns rules that match (for course recommendation).
     */
    public function matchRulesForApplicant(Applicant $applicant, GradingSession $gradingSession): Collection
    {
        $scores = ApplicantScore::query()
            ->where('grading_session_id', $gradingSession->id)
            ->where('applicant_id', $applicant->id)
            ->with('domain')
            ->get()
            ->keyBy('domain_id');

        $rules = DecisionRule::query()
            ->where('is_active', true)
            ->with('course')
            ->get();

        $matched = collect();
        foreach ($rules as $rule) {
            $domainId = $rule->domain_id;
            $score = $domainId ? $scores->get($domainId)?->normalized_score : $scores->avg('normalized_score');
            if ($score !== null && $score >= $rule->min_score && $score <= $rule->max_score) {
                $matched->push($rule);
            }
        }

        return $matched;
    }
}
