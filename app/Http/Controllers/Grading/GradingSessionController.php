<?php

namespace App\Http\Controllers\Grading;

use App\Http\Controllers\Controller;
use App\Models\GradingSession;
use App\Services\GradingSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradingSessionController extends Controller
{
    public function __construct(
        private GradingSessionService $gradingService
    ) {}

    public function show(GradingSession $grading_session): Response
    {
        $session = $grading_session->load(['examSession.room', 'applicants.application']);

        $domainsTotal = \App\Models\ExamDomain::where('is_active', true)->count();
        $applicants = $session->applicants->map(function ($a) use ($session, $domainsTotal) {
            $scoresCount = $session->applicantScores()->where('applicant_id', $a->id)->distinct()->count('domain_id');
            $scored = $domainsTotal > 0 && $scoresCount >= $domainsTotal;
            return [
                'id' => $a->id,
                'applicant_id' => $a->id,
                'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                'reference' => $a->application?->reference_number ?? '—',
                'scored' => $scored,
                'domains_complete' => $scoresCount,
            ];
        });

        return Inertia::render('Grading/Session', [
            'sessionId' => (string) $session->id,
            'session' => [
                'id' => $session->id,
                'exam_session_id' => $session->examSession?->id,
                'exam_date' => $session->examSession?->date?->format('Y-m-d'),
                'exam_time' => $session->examSession?->start_time,
                'room_name' => $session->examSession?->room?->name ?? '—',
                'opened_at' => $session->opened_at?->toIso8601String(),
            ],
            'applicants' => $applicants->values()->all(),
            'workflowStatus' => $session->workflowStatus(),
        ]);
    }

    public function updateWorkflowStatus(Request $request, GradingSession $grading_session): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:in_progress,finalized']])['status'];
        $backendStatus = $status === 'finalized' ? GradingSession::STATUS_FINALIZED : GradingSession::STATUS_IN_PROGRESS;
        $this->gradingService->updateWorkflowStatus($grading_session, $backendStatus);

        return redirect()->back()->with('success', 'Status updated.');
    }
}
