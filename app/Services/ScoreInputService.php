<?php

namespace App\Services;

use App\Models\ApplicantScore;
use App\Models\GradingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScoreInputService
{
    public function saveScores(GradingSession $session, int $applicantId, array $scores, User $scoredBy): void
    {
        DB::transaction(function () use ($session, $applicantId, $scores, $scoredBy) {
            if ($session->status === GradingSession::STATUS_OPEN) {
                $session->update(['status' => GradingSession::STATUS_IN_PROGRESS]);
            }

            foreach ($scores as $domainId => $data) {
                $raw = (int) ($data['raw_score'] ?? 0);
                $max = (int) ($data['max_score'] ?? 0);
                $normalized = $max > 0 ? round(($raw / $max) * 100, 2) : null;

                ApplicantScore::updateOrCreate(
                    [
                        'grading_session_id' => $session->id,
                        'applicant_id' => $applicantId,
                        'domain_id' => $domainId,
                    ],
                    [
                        'raw_score' => $raw,
                        'max_score' => $max,
                        'normalized_score' => $normalized,
                        'scored_by' => $scoredBy->id,
                        'scored_at' => now(),
                    ]
                );
            }
        });
    }
}
