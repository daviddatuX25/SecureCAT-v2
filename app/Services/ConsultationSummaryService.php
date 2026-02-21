<?php

namespace App\Services;

use App\Models\ConsultationSummary;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConsultationSummaryService
{
    public function save(ConsultationSummary $summary, array $data, User $counselor): ConsultationSummary
    {
        $summary->update([
            'status' => $data['status'] ?? $summary->status,
            'recommended_course_id' => $data['recommended_course_id'] ?? $summary->recommended_course_id,
            'counselor_comments' => $data['counselor_comments'] ?? $summary->counselor_comments,
            'counselor_id' => $counselor->id,
        ]);

        return $summary->fresh();
    }

    public function release(ConsultationSummary $summary, User $releasedBy): ConsultationSummary
    {
        DB::transaction(function () use ($summary, $releasedBy) {
            $summary->update([
                'status' => ConsultationSummary::STATUS_RELEASED,
                'released_at' => now(),
                'released_by' => $releasedBy->id,
            ]);
        });

        return $summary->fresh();
    }

    public function getOrCreateForApplicant(int $applicantId): ConsultationSummary
    {
        return ConsultationSummary::firstOrCreate(
            ['applicant_id' => $applicantId],
            ['status' => ConsultationSummary::STATUS_PENDING]
        );
    }
}
