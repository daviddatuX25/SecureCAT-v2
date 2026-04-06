<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\ConsultationSummary;
use App\Models\GradingSession;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationController extends Controller
{
    public function index(): Response
    {
        $applicantIdsWithScores = ApplicantScore::query()
            ->whereHas('gradingSession', fn ($q) => $q->where('status', GradingSession::STATUS_FINALIZED))
            ->distinct()
            ->pluck('applicant_id');

        $releasedQuery = ConsultationSummary::query()
            ->where('status', ConsultationSummary::STATUS_RELEASED)
            ->with('applicant.application');

        $releasedTotal = $releasedQuery->count();
            
        $released = $releasedQuery->paginate(20, ['*'], 'released_page')
            ->through(fn ($cs) => [
                'id' => $cs->applicant_id,
                'name' => $cs->applicant?->application ? trim(implode(' ', array_filter([$cs->applicant->application->first_name, $cs->applicant->application->middle_name, $cs->applicant->application->last_name, $cs->applicant->application->suffix]))) : '—',
                'reference' => $cs->applicant?->application?->reference_number ?? '—',
                'released_date' => $cs->released_at?->format('Y-m-d'),
            ]);

        $pendingIds = $applicantIdsWithScores->diff(ConsultationSummary::query()->where('status', ConsultationSummary::STATUS_RELEASED)->pluck('applicant_id'))->values();
        $pendingTotal = $pendingIds->count();

        $pending = Applicant::query()
            ->whereIn('id', $pendingIds)
            ->with('application')
            ->paginate(20, ['*'], 'pending_page')
            ->through(function ($a) {
                $gs = GradingSession::query()
                    ->where('status', GradingSession::STATUS_FINALIZED)
                    ->whereHas('applicantScores', fn ($q) => $q->where('applicant_id', $a->id))
                    ->first();
                return [
                    'id' => $a->id,
                    'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                    'reference' => $a->application?->reference_number ?? '—',
                    'finalized_date' => $gs?->finalized_at?->format('Y-m-d'),
                ];
            });

        return Inertia::render('Consultation/Dashboard', [
            'title' => 'Release & Consultation',
            'description' => 'Review scores and release consultations to applicants.',
            'applicants_pending' => $pending,
            'applicants_released' => $released,
            'stats' => [
                'pending' => $pendingTotal,
                'released' => $releasedTotal,
                'total_with_scores' => $applicantIdsWithScores->count(),
            ],
        ]);
    }
}
