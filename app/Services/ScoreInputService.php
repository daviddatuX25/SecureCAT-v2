<?php

namespace App\Services;

use App\Models\ApplicantScore;
use App\Models\GradingSession;
use App\Models\User;

class ScoreInputService
{
    /**
     * Upsert scores for an applicant in a grading session.
     *
     * @param  array<array{domain_id: int, raw_score: int, max_score: int}>  $scores
     */
    public function saveScores(GradingSession $gradingSession, int $applicantId, array $scores, User $scoredBy): void
    {
        $now = now();

        foreach ($scores as $entry) {
            $domainId = (int) $entry['domain_id'];
            $rawScore = (int) $entry['raw_score'];
            $maxScore = (int) $entry['max_score'];

            ApplicantScore::updateOrCreate(
                [
                    'grading_session_id' => $gradingSession->id,
                    'applicant_id' => $applicantId,
                    'domain_id' => $domainId,
                ],
                [
                    'raw_score' => $rawScore,
                    'max_score' => $maxScore,
                    'scored_by' => $scoredBy->id,
                    'scored_at' => $now,
                ]
            );
        }
    }
}
