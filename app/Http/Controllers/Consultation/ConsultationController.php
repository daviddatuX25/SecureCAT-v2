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

        $released = ConsultationSummary::query()
            ->where('status', ConsultationSummary::STATUS_RELEASED)
            ->with('applicant.application')
            ->get()
            ->map(fn ($cs) => [
                'id' => $cs->applicant_id,
                'name' => $cs->applicant?->application ? trim(implode(' ', array_filter([$cs->applicant->application->first_name, $cs->applicant->application->middle_name, $cs->applicant->application->last_name, $cs->applicant->application->suffix]))) : '—',
                'reference' => $cs->applicant?->application?->reference_number ?? '—',
                'released_at' => $cs->released_at?->format('Y-m-d'),
            ]);

        $pendingIds = $applicantIdsWithScores->diff($released->pluck('id'))->values();
        $pending = Applicant::query()
            ->whereIn('id', $pendingIds)
            ->with('application')
            ->get()
            ->map(function ($a) {
                $gs = GradingSession::query()
                    ->where('status', GradingSession::STATUS_FINALIZED)
                    ->whereHas('applicantScores', fn ($q) => $q->where('applicant_id', $a->id))
                    ->first();
                return [
                    'id' => $a->id,
                    'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                    'reference' => $a->application?->reference_number ?? '—',
                    'scores_finalized_at' => $gs?->finalized_at?->format('Y-m-d'),
                ];
            });

        return Inertia::render('Consultation/Dashboard', [
            'title' => 'Consultation',
            'description' => 'Review scores and release consultations to applicants.',
            'applicants_pending' => $pending->values()->all(),
            'applicants_released' => $released->values()->all(),
            'stats' => [
                'pending' => $pending->count(),
                'released' => $released->count(),
                'total_with_scores' => $applicantIdsWithScores->count(),
            ],
        ]);
    }
}
