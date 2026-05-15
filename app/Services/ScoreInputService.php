<?php

namespace App\Services;

use App\Models\ApplicantScore;
use App\Models\AptitudeArea;
use App\Models\GradingSession;
use App\Models\User;

class ScoreInputService
{
    /**
     * Upsert scores for an applicant in a grading session.
     *
     * When $enableNormalizedScores is true: raw_score is entered and
     * normalized_score is auto-computed from the aptitude area formula.
     * When false: normalized_score is entered directly by the user.
     *
     * @param  array<array{aptitude_area_id: int, raw_score?: int|null, max_score?: int|null, normalized_score?: float|null}>  $scores
     */
    public function saveScores(GradingSession $gradingSession, int $applicantId, array $scores, User $scoredBy, bool $enableNormalizedScores): void
    {
        $now = now();

        foreach ($scores as $entry) {
            $domainId = (int) $entry['aptitude_area_id'];
            $attributes = [
                'scored_by' => $scoredBy->id,
                'scored_at' => $now,
            ];

            if ($enableNormalizedScores) {
                $rawScore = (int) ($entry['raw_score'] ?? 0);
                $maxScore = (int) ($entry['max_score'] ?? 0);
                $area = AptitudeArea::find($domainId);
                $normalizedScore = $area?->computeNormalizedScore((float) $rawScore);

                $attributes['raw_score'] = $rawScore;
                $attributes['max_score'] = $maxScore;
                $attributes['normalized_score'] = $normalizedScore;
            } else {
                $attributes['raw_score'] = null;
                $attributes['max_score'] = null;
                $attributes['normalized_score'] = $entry['normalized_score'] ?? null;
            }

            ApplicantScore::updateOrCreate(
                [
                    'grading_session_id' => $gradingSession->id,
                    'applicant_id' => $applicantId,
                    'aptitude_area_id' => $domainId,
                ],
                $attributes
            );
        }
    }
}
