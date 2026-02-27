<?php

namespace App\Http\Controllers\Grading;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGradingSessionRequest;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Season;
use App\Services\GradingSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradingController extends Controller
{
    public function __construct(
        private GradingSessionService $gradingService
    ) {}

    public function index(Request $request): Response
    {
        $activeSeason = Season::active();

        $gradingQuery = GradingSession::query()
            ->with(['examSession.room', 'examSession.season'])
            ->withCount('applicants')
            ->orderByDesc('opened_at');
        if ($activeSeason !== null) {
            $gradingQuery->whereHas('examSession', fn ($q) => $q->forSeason($activeSeason));
        }
        $gradingSessions = $gradingQuery->get()
            ->map(function (GradingSession $gs) {
                $scored = $gs->applicantScores()->distinct()->count('applicant_id');
                return [
                    'id' => $gs->id,
                    'exam_session_id' => $gs->exam_session_id,
                    'exam_date' => $gs->examSession?->date?->format('Y-m-d'),
                    'exam_time' => $gs->examSession?->start_time,
                    'room_name' => $gs->examSession?->room?->name ?? '—',
                    'status' => $gs->status,
                    'opened_at' => $gs->opened_at?->toIso8601String(),
                    'finalized_at' => $gs->finalized_at?->toIso8601String(),
                    'applicants_total' => $gs->applicants_count ?? 0,
                    'applicants_scored' => $scored,
                ];
            });

        $completedQuery = ExamSession::query()
            ->where('status', ExamSession::STATUS_COMPLETED)
            ->whereDoesntHave('gradingSession')
            ->with('room')
            ->withCount('applicants')
            ->orderByDesc('date');
        if ($activeSeason !== null) {
            $completedQuery->forSeason($activeSeason);
        }
        $completedWithoutGrading = $completedQuery->get()
            ->map(fn ($es) => [
                'id' => $es->id,
                'exam_date' => $es->date?->format('Y-m-d'),
                'exam_time' => $es->start_time,
                'room_name' => $es->room?->name ?? '—',
                'applicants_count' => $es->applicants_count ?? 0,
            ]);

        return Inertia::render('Grading/Dashboard', [
            'title' => 'Grading',
            'description' => 'Input and manage exam scores.',
            'grading_sessions' => $gradingSessions->values()->all(),
            'completed_exams_without_grading' => $completedWithoutGrading->values()->all(),
        ]);
    }

    public function store(StoreGradingSessionRequest $request): RedirectResponse
    {
        $examSession = ExamSession::findOrFail($request->validated('exam_session_id'));
        $session = $this->gradingService->openForExamSession($examSession, $request->user());

        return redirect()->route('grading.sessions.show', $session->id)
            ->with('success', 'Grading session opened.');
    }
}
