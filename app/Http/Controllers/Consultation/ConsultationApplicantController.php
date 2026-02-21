<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConsultationSummaryRequest;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\GradingSession;
use App\Services\ConsultationSummaryService;
use App\Services\DecisionRuleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationApplicantController extends Controller
{
    public function __construct(
        private ConsultationSummaryService $summaryService,
        private DecisionRuleService $ruleService
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
        $matchedRules = $this->ruleService->matchRulesForApplicant($applicant, $gs);
        $summary = $this->summaryService->getOrCreateForApplicant($applicant->id);
        $summary->load('recommendedCourse');

        $courses = \App\Models\Course::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Consultation/ApplicantView', [
            'applicantId' => (string) $applicant->id,
            'applicant' => [
                'id' => $applicant->id,
                'name' => $applicant->application ? trim(implode(' ', array_filter([$applicant->application->first_name, $applicant->application->middle_name, $applicant->application->last_name, $applicant->application->suffix]))) : '—',
                'email' => $applicant->application?->email ?? $applicant->email ?? '—',
                'reference' => $applicant->application?->reference_number ?? '—',
            ],
            'application' => $applicant->application ? [
                'id' => $applicant->application->id,
                'status' => $applicant->application->status,
                'submitted_at' => $applicant->application->submitted_at?->format('Y-m-d'),
                'course_preferences' => [],
            ] : null,
            'scores' => $scores->map(fn ($s) => [
                'domain' => $s->domain?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
            ])->values()->all(),
            'matched_rules' => $matchedRules->map(fn ($r) => [
                'course_name' => $r->course?->name ?? '—',
                'domain_name' => $r->domain?->name ?? 'Any',
                'range' => "{$r->min_score} – {$r->max_score}",
                'note' => $r->note,
            ])->values()->all(),
            'consultation_summary' => [
                'status' => $summary->status,
                'recommended_course_id' => $summary->recommended_course_id,
                'recommended_course_name' => $summary->recommendedCourse?->name ?? '—',
                'counselor_comments' => $summary->counselor_comments,
            ],
            'courses' => $courses->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
        ]);
    }

    public function update(UpdateConsultationSummaryRequest $request, Applicant $applicant): RedirectResponse
    {
        $this->ensureApplicantInConsultationScope($applicant);
        $summary = $this->summaryService->getOrCreateForApplicant($applicant->id);
        $this->summaryService->save($summary, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Summary saved.');
    }

    public function release(Applicant $applicant): RedirectResponse
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
