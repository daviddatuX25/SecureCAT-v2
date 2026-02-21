<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proctor\MarkAttendanceRequest;
use App\Http\Requests\Proctor\MarkSubmissionBulkRequest;
use App\Http\Requests\Proctor\MarkSubmissionRequest;
use App\Models\ExamSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionRosterController extends Controller
{
    public function show(ExamSession $exam_session): Response
    {
        $this->authorize('viewRoster', $exam_session);

        $exam_session->load(['room:id,name,building,capacity', 'proctors:id,name']);

        $applicants = $exam_session->applicants()
            ->with('application:id,reference_number,first_name,middle_name,last_name,suffix')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'session_applicant_id' => $a->pivot->id,
                'name' => trim(implode(' ', array_filter([
                    $a->application?->first_name,
                    $a->application?->middle_name,
                    $a->application?->last_name,
                    $a->application?->suffix,
                ]))),
                'reference_number' => $a->application?->reference_number ?? '',
                'attendance_status' => $a->pivot->attendance_status ?? 'pending',
                'submission_status' => $a->pivot->submission_status ?? 'pending',
                'attendance_marked_at' => $a->pivot->attendance_marked_at
                    ? Carbon::parse($a->pivot->attendance_marked_at)->toIso8601String()
                    : null,
                'submitted_at' => $a->pivot->submitted_at
                    ? Carbon::parse($a->pivot->submitted_at)->toIso8601String()
                    : null,
            ]);

        $stats = [
            'total' => $applicants->count(),
            'present' => $applicants->where('attendance_status', 'present')->count(),
            'absent' => $applicants->where('attendance_status', 'absent')->count(),
            'pending' => $applicants->where('attendance_status', 'pending')->count(),
            'submitted' => $applicants->where('submission_status', 'submitted')->count(),
            'present_pending_submission' => $applicants
                ->where('attendance_status', 'present')
                ->where('submission_status', 'pending')
                ->count(),
        ];

        $user = request()->user();
        $isWithinStartWindow = $exam_session->isWithinStartWindow();
        $canOverrideSchedule = $user->hasAnyRole(['admin', 'super_admin']);

        return Inertia::render('Proctor/SessionRoster', [
            'session' => array_merge($exam_session->toArray(), [
                'is_within_start_window' => $isWithinStartWindow,
                'can_override_schedule' => $canOverrideSchedule,
            ]),
            'applicants' => $applicants->values()->all(),
            'stats' => $stats,
        ]);
    }

    public function storeAttendance(MarkAttendanceRequest $request, ExamSession $exam_session): RedirectResponse|JsonResponse
    {
        $session = $exam_session;
        if (! in_array($session->status, [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS], true)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Session must be published or in progress.'], 409);
            }
            return back()->with('error', 'Session must be published or in progress.');
        }

        $applicantId = (int) $request->validated('applicant_id');
        $pivot = DB::table('exam_session_applicant')
            ->where('exam_session_id', $session->id)
            ->where('applicant_id', $applicantId)
            ->first();

        if (! $pivot) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Applicant not assigned to this session.'], 404);
            }
            return back()->with('error', 'Applicant not assigned to this session.');
        }

        $currentStatus = $pivot->attendance_status ?? 'pending';
        if ($currentStatus !== 'pending') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Attendance already marked.'], 409);
            }
            return response()->json(['message' => 'Attendance already marked.'], 409);
        }

        $status = $request->validated('status');
        DB::table('exam_session_applicant')
            ->where('id', $pivot->id)
            ->update([
                'attendance_status' => $status,
                'attendance_marked_at' => now(),
                'attendance_marked_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Attendance updated.'], 200);
        }
        return back()->with('success', 'Attendance marked.');
    }

    public function storeSubmission(MarkSubmissionRequest $request, ExamSession $exam_session): RedirectResponse|JsonResponse
    {
        $session = $exam_session;
        if ($session->status !== ExamSession::STATUS_IN_PROGRESS) {
            $message = 'Session must be in progress.';
            return $request->wantsJson() ? response()->json(['message' => $message], 409) : response()->json(['message' => $message], 409);
        }

        $applicantId = (int) $request->validated('applicant_id');
        $pivot = DB::table('exam_session_applicant')
            ->where('exam_session_id', $session->id)
            ->where('applicant_id', $applicantId)
            ->first();

        if (! $pivot) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Applicant not assigned to this session.'], 404);
            }
            return back()->with('error', 'Applicant not assigned to this session.');
        }

        if (($pivot->attendance_status ?? 'pending') !== 'present') {
            return response()->json(['message' => 'Applicant must be marked present first.'], 409);
        }

        if (($pivot->submission_status ?? 'pending') === 'submitted') {
            return response()->json(['message' => 'Already submitted.'], 409);
        }

        DB::table('exam_session_applicant')
            ->where('id', $pivot->id)
            ->update([
                'submission_status' => 'submitted',
                'submitted_at' => now(),
                'submitted_to' => $request->user()->id,
                'updated_at' => now(),
            ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Submission recorded.'], 200);
        }
        return back()->with('success', 'Submission recorded.');
    }

    public function storeSubmissionBulk(MarkSubmissionBulkRequest $request, ExamSession $exam_session): RedirectResponse|JsonResponse
    {
        $session = $exam_session;
        if ($session->status !== ExamSession::STATUS_IN_PROGRESS) {
            $message = 'Session must be in progress.';
            return $request->wantsJson() ? response()->json(['message' => $message], 409) : back()->with('error', $message);
        }

        $applicantIds = $request->validated('applicant_ids') ?? [];
        $pivots = DB::table('exam_session_applicant')
            ->where('exam_session_id', $session->id)
            ->where('attendance_status', 'present')
            ->where('submission_status', 'pending');

        if (! empty($applicantIds)) {
            $applicantIds = array_map('intval', $applicantIds);
            $pivots->whereIn('applicant_id', $applicantIds);
        }

        $pivots = $pivots->get();
        $user = $request->user();
        $now = now();

        foreach ($pivots as $pivot) {
            DB::table('exam_session_applicant')
                ->where('id', $pivot->id)
                ->update([
                    'submission_status' => 'submitted',
                    'submitted_at' => $now,
                    'submitted_to' => $user->id,
                    'updated_at' => $now,
                ]);
        }

        $count = count($pivots);
        $message = $count === 0
            ? 'No applicants to mark as submitted.'
            : "Submission recorded for {$count} applicant(s).";

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'count' => $count], 200);
        }
        return back()->with('success', $message);
    }

    public function start(ExamSession $exam_session): RedirectResponse|JsonResponse
    {
        $this->authorize('manageRoster', $exam_session);

        if ($exam_session->status !== ExamSession::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Session must be published to start.'], 409);
        }

        $withinWindow = $exam_session->isWithinStartWindow();
        $user = request()->user();
        $canOverride = $user->hasAnyRole(['admin', 'super_admin']);

        if (! $withinWindow && ! $canOverride) {
            $message = 'Outside scheduled window. Only an admin can start the session outside the schedule.';
            if (request()->wantsJson()) {
                return response()->json(['message' => $message], 409);
            }
            return back()->with('error', $message);
        }

        if (! $withinWindow && $canOverride) {
            // TODO: log session_start_override to audit when logging feature is implemented (actor_id, optional reason, timestamp).
        }

        $exam_session->update([
            'status' => ExamSession::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Session started.'], 200);
        }
        return back()->with('success', 'Session started.');
    }

    public function close(ExamSession $exam_session): RedirectResponse|JsonResponse
    {
        $this->authorize('manageRoster', $exam_session);

        if ($exam_session->status !== ExamSession::STATUS_IN_PROGRESS) {
            return response()->json(['message' => 'Session must be in progress to close.'], 409);
        }

        $exam_session->update([
            'status' => ExamSession::STATUS_COMPLETED,
            'closed_at' => now(),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Session closed.'], 200);
        }
        return back()->with('success', 'Session closed.');
    }
}
