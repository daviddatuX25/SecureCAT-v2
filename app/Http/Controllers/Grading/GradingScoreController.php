<?php

namespace App\Http\Controllers\Grading;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateScoresRequest;
use App\Models\Applicant;
use App\Models\AptitudeArea;
use App\Models\ConsultationSummary;
use App\Models\GradingSession;
use App\Models\SystemSetting;
use App\Services\AuditService;
use App\Services\ScoreInputService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GradingScoreController extends Controller
{
    public function __construct(
        private ScoreInputService $scoreService
    ) {}

    public function show(GradingSession $grading_session, Applicant $applicant): Response
    {
        if (! $grading_session->applicants()->where('applicants.id', $applicant->id)->exists()) {
            abort(404, 'Applicant is not part of this grading session.');
        }

        $session = $grading_session->load(['examSession.room']);
        $domains = AptitudeArea::query()->where('is_active', true)->orderBy('display_order')->get();
        $existingScores = $grading_session->applicantScores()
            ->where('applicant_id', $applicant->id)
            ->get()
            ->keyBy('aptitude_area_id');

        $applicant->load('application');

        $enableNormalizedScores = SystemSetting::enableNormalizedScores();

        return Inertia::render('Grading/ScoreInput', [
            'sessionId' => (string) $grading_session->id,
            'applicantId' => (string) $applicant->id,
            'workflowStatus' => $grading_session->workflowStatus(),
            'enableNormalizedScores' => $enableNormalizedScores,
            'applicant' => [
                'id' => $applicant->id,
                'name' => $applicant->application ? trim(implode(' ', array_filter([$applicant->application->first_name, $applicant->application->middle_name, $applicant->application->last_name, $applicant->application->suffix]))) : '—',
                'reference' => $applicant->application?->reference_number ?? '—',
                'exam_date' => $session->examSession?->date?->format('F j, Y'),
                'room_name' => $session->examSession?->room?->name ?? '—',
            ],
            'domains' => $domains->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'max_items' => $d->max_items,
                'has_formula' => $d->formula !== null,
            ])->values()->all(),
            'existing_scores' => $existingScores->map(fn ($s) => [
                'aptitude_area_id' => $s->aptitude_area_id,
                'raw_score' => $s->raw_score,
                'max_score' => $s->max_score,
                'normalized_score' => $s->normalized_score,
            ])->values()->all(),
        ]);
    }

    public function update(UpdateScoresRequest $request, GradingSession $grading_session, Applicant $applicant): RedirectResponse
    {
        if (! $grading_session->applicants()->where('applicants.id', $applicant->id)->exists()) {
            abort(404, 'Applicant is not part of this grading session.');
        }
        if ($grading_session->status === GradingSession::STATUS_FINALIZED) {
            return redirect()->back()->with('error', 'Scores cannot be edited when the grading session is finalized.');
        }

        $this->scoreService->saveScores(
            $grading_session,
            $applicant->id,
            $request->validated('scores'),
            $request->user(),
            SystemSetting::enableNormalizedScores()
        );

        app(AuditService::class)->log('score.entered', GradingSession::class, $grading_session->id, [], [
            'grading_session_id' => $grading_session->id,
            'applicant_id' => $applicant->id,
            'scores' => $request->validated('scores'),
        ], "Scores entered for applicant {$applicant->id} in grading session {$grading_session->id}");

        $this->ensureDraftSummary($grading_session, $applicant->id);

        return redirect()->route('admin.grading.sessions.show', $grading_session->id)
            ->with('success', 'Scores saved.');
    }

    private function ensureDraftSummary(GradingSession $gradingSession, int $applicantId): void
    {
        $activeAreaCount = AptitudeArea::where('is_active', true)->count();
        $scoredCount = $gradingSession->applicantScores()
            ->where('applicant_id', $applicantId)
            ->count();

        if ($scoredCount < $activeAreaCount) {
            return;
        }

        $summary = ConsultationSummary::firstOrCreate(
            ['applicant_id' => $applicantId],
            ['status' => ConsultationSummary::STATUS_DRAFT]
        );

        if ($summary->status === ConsultationSummary::STATUS_PENDING) {
            $summary->update(['status' => ConsultationSummary::STATUS_DRAFT]);
        }
    }
}
