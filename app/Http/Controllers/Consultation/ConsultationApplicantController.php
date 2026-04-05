<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\GradingSession;
use App\Services\ConsultationSummaryService;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationApplicantController extends Controller
{
    public function __construct(
        private ConsultationSummaryService $summaryService
    ) {}

    /**
     * Show consultation view for applicant. Per E-027: applicant must have finalized scores.
     */
    public function show(Applicant $applicant): Response
    {
        $gs = GradingSession::query()
            ->where('status', GradingSession::STATUS_FINALIZED)
            ->whereHas('applicantScores', fn ($q) => $q->where('applicant_id', $applicant->id))
            ->first();

        if (! $gs) {
            abort(404, 'Applicant has no finalized exam scores and is not in consultation scope.');
        }

        $applicant->load('application');
        $scores = ApplicantScore::query()
            ->where('grading_session_id', $gs->id)
            ->where('applicant_id', $applicant->id)
            ->with('domain')
            ->get();
        $summary = $this->summaryService->getOrCreateForApplicant($applicant->id);

        return Inertia::render('Consultation/ApplicantView', [
            'applicant' => [
                'id' => $applicant->id,
                'name' => $applicant->application ? trim(implode(' ', array_filter([$applicant->application->first_name, $applicant->application->middle_name, $applicant->application->last_name, $applicant->application->suffix]))) : '—',
                'email' => $applicant->application?->email ?? $applicant->email ?? '—',
                'reference' => $applicant->application?->reference_number ?? '—',
            ],
            'scores' => $scores->map(fn ($s) => [
                'domain' => $s->domain?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
            ])->values()->all(),
            'consultation_summary' => [
                'status' => $summary->status,
                'released_at' => $summary->released_at?->toISOString(),
            ],
        ]);
    }

    public function release(Applicant $applicant): \Illuminate\Http\RedirectResponse
    {
        $this->ensureApplicantInConsultationScope($applicant);
        $summary = $this->summaryService->getOrCreateForApplicant($applicant->id);
        $this->summaryService->release($summary, request()->user());

        return redirect()->route('consultation.index')->with('success', 'Consultation released.');
    }

    private function ensureApplicantInConsultationScope(Applicant $applicant): void
    {
        $hasFinalizedScores = GradingSession::query()
            ->where('status', GradingSession::STATUS_FINALIZED)
            ->whereHas('applicantScores', fn ($q) => $q->where('applicant_id', $applicant->id))
            ->exists();

        if (! $hasFinalizedScores) {
            abort(404, 'Applicant has no finalized exam scores and is not in consultation scope.');
        }
    }
}
