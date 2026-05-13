<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignApplicantsRequest;
use App\Http\Requests\StoreExamSessionRequest;
use App\Http\Requests\UpdateExamSessionRequest;
use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\ExamSchedulingConversation;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Room;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\ExamSessionCancelled;
use App\Notifications\ExamSessionCompleted;
use App\Notifications\ExamSessionPublished;
use App\Notifications\ExamSessionStarted;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ExamSessionController extends Controller
{
    private static function statusesList(): array
    {
        return [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'published', 'label' => 'Published'],
            ['value' => 'in_progress', 'label' => 'In progress'],
            ['value' => 'completed', 'label' => 'Completed'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ExamSession::class);

        $user = $request->user();
        $isProctorView = $user->hasAnyRole(['proctor']) && ! $user->hasAnyRole(['super_admin', 'registrar_administrator']);

        $activeAcademicYear = AcademicYear::active();
        $academicYearId = $request->input('academic_year_id');
        if ($academicYearId !== null && $academicYearId !== '') {
            $queryAcademicYearId = (int) $academicYearId;
        } else {
            $queryAcademicYearId = $activeAcademicYear?->id;
        }

        $query = ExamSession::query()
            ->with(['room:id,name,building,capacity', 'proctors:id,name', 'academicYear:id,academic_year,semester'])
            ->orderBy('date')
            ->orderBy('start_time');

        if ($queryAcademicYearId !== null) {
            $query->forAcademicYear($queryAcademicYearId);
        }

        if ($isProctorView) {
            $query->whereHas('proctors', fn ($q) => $q->where('users.id', $user->id));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->get('search').'%';
            $query->whereHas('room', function ($r) use ($term) {
                $r->where('name', 'like', $term)->orWhere('building', 'like', $term);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->get('date_to'));
        }

        $sessions = $query->paginate(15)->withQueryString();

        $academicYears = AcademicYear::query()->orderByDesc('academic_year')->orderBy('semester')->get(['id', 'academic_year', 'semester', 'is_active']);

        $payload = [
            'sessions' => $sessions,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'academic_year_id']),
            'seasons' => $academicYears,
            'active_season_id' => $activeAcademicYear?->id,
            'statuses' => self::statusesList(),
            'view' => $isProctorView ? 'proctor' : 'admin',
        ];

        if (! $isProctorView) {
            $payload['schedule_assistant'] = self::scheduleAssistantPayload($request, $queryAcademicYearId ?? $activeAcademicYear?->id);
        }

        return Inertia::render('Admin/TestScheduling/Index', $payload);
    }

    /**
     * Payload for the schedule assistant modal (rooms, draft sessions, messages, etc.).
     * Same data as ExamSchedulingAssistantController::index for consistent behavior.
     */
    private static function scheduleAssistantPayload(Request $request, ?int $academicYearId): array
    {
        $applicantCount = Applicant::query()
            ->whereHas('application', fn ($q) => $q->where('status', 'accepted'))
            ->whereDoesntHave('examSessions')
            ->count();

        $rooms = Room::query()
            ->where('is_active', true)
            ->orderBy('building')
            ->orderBy('name')
            ->get(['id', 'name', 'building', 'capacity'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'building' => $r->building,
                'capacity' => $r->capacity,
            ])
            ->values()
            ->all();

        $draftSessions = [];
        if ($academicYearId !== null) {
            $draftSessions = ExamSession::query()
                ->where('status', ExamSession::STATUS_DRAFT)
                ->forAcademicYear($academicYearId)
                ->with('room:id,name,building,capacity')
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'room_id' => $s->room_id,
                    'room' => $s->room ? [
                        'id' => $s->room->id,
                        'name' => $s->room->name,
                        'building' => $s->room->building,
                        'capacity' => $s->room->capacity,
                    ] : null,
                    'date' => $s->date?->format('Y-m-d'),
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'current_count' => $s->applicants()->count(),
                    'capacity' => $s->room?->capacity ?? 0,
                ])
                ->values()
                ->all();
        }

        $conversation = ExamSchedulingConversation::query()
            ->latestForUser($request->user()->id)
            ->first();

        return [
            'applicant_count' => $applicantCount,
            'rooms' => $rooms,
            'draft_sessions' => $draftSessions,
            'messages' => $conversation?->messages ?? [],
            'openrouter_configured' => (bool) config('services.openrouter.key'),
            'csrf_token' => csrf_token(),
        ];
    }

    public function create(): Response
    {
        $this->authorize('create', ExamSession::class);

        $activeAcademicYear = AcademicYear::active();
        $rooms = Room::query()->where('is_active', true)->orderBy('building')->orderBy('name')->get(['id', 'name', 'building', 'capacity']);
        $proctors = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'proctor'))->orderBy('name')->get(['id', 'name']);
        $academicYears = AcademicYear::query()->orderByDesc('academic_year')->orderBy('semester')->get(['id', 'academic_year', 'semester', 'is_active']);

        return Inertia::render('Admin/TestScheduling/Create', [
            'rooms' => $rooms,
            'proctors' => $proctors,
            'seasons' => $academicYears,
            'active_season_id' => $activeAcademicYear?->id,
        ]);
    }

    public function store(StoreExamSessionRequest $request): RedirectResponse
    {
        $this->authorize('create', ExamSession::class);

        $validated = $request->validated();

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::active()?->id;

        $type = $validated['type'] ?? 'scheduled';
        if ($type === 'scheduled' && isset($validated['room_id'])) {
            if (ExamSession::hasRoomConflict(
                $validated['room_id'],
                $validated['date'],
                $validated['start_time'],
                $validated['end_time'] ?? null
            )) {
                return back()->withErrors(['room_id' => 'This room is already booked for the selected date and time.'])->withInput();
            }
        }

        $session = ExamSession::create([
            'academic_year_id' => $academicYearId,
            'room_id' => $validated['room_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'] ?? null,
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => $request->user()->id,
        ]);
        if (! empty($validated['proctor_ids'])) {
            $session->proctors()->sync($validated['proctor_ids']);
        }

        return redirect()->route('admin.exam-scheduling.index')->with('success', 'Exam session created.');
    }

    public function show(ExamSession $exam_session): Response
    {
        $this->authorize('view', $exam_session);

        $user = request()->user();
        $isProctorView = $user->hasAnyRole(['proctor']) && ! $user->hasAnyRole(['super_admin', 'registrar_administrator']);

        $exam_session->load(['room:id,name,building,capacity', 'proctors:id,name']);

        $assigned_applicants = $exam_session->applicants()
            ->with('application:id,reference_number,first_name,middle_name,last_name,suffix')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'session_applicant_id' => $a->pivot->id,
                'reference_number' => $a->application?->reference_number,
                'name' => trim(implode(' ', array_filter([$a->application?->first_name, $a->application?->middle_name, $a->application?->last_name, $a->application?->suffix]))),
            ]);

        $available_applicants = Applicant::query()
            ->whereHas('application', fn ($q) => $q->where('status', 'accepted'))
            ->whereDoesntHave('examSessions')
            ->with('application:id,reference_number,first_name,middle_name,last_name,suffix')
            ->orderBy('email')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'reference_number' => $a->application?->reference_number,
                'name' => trim(implode(' ', array_filter([$a->application?->first_name, $a->application?->middle_name, $a->application?->last_name, $a->application?->suffix]))),
                'email' => $a->email,
            ]);

        $proctors = $isProctorView ? [] : User::query()->whereHas('roles', fn ($q) => $q->where('name', 'proctor'))->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/TestScheduling/Show', [
            'session' => $exam_session,
            'assigned_applicants' => $assigned_applicants,
            'available_applicants' => $available_applicants,
            'proctors' => $proctors,
            'view' => $isProctorView ? 'proctor' : 'admin',
        ]);
    }

    public function edit(ExamSession $exam_session): Response
    {
        $this->authorize('update', $exam_session);

        $exam_session->load(['room:id,name,building,capacity', 'proctors:id']);
        $rooms = Room::query()->where('is_active', true)->orderBy('building')->orderBy('name')->get(['id', 'name', 'building', 'capacity']);
        $proctors = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'proctor'))->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/TestScheduling/Edit', [
            'session' => $exam_session,
            'rooms' => $rooms,
            'proctors' => $proctors,
        ]);
    }

    public function update(UpdateExamSessionRequest $request, ExamSession $exam_session): RedirectResponse
    {
        $this->authorize('update', $exam_session);

        $validated = $request->validated();
        $sessionData = collect($validated)->only(['academic_year_id', 'room_id', 'date', 'start_time', 'end_time'])->filter()->all();
        if (! empty($sessionData)) {
            $exam_session->update($sessionData);
        }
        if (array_key_exists('proctor_ids', $validated)) {
            $exam_session->proctors()->sync($validated['proctor_ids'] ?? []);
        }

        return redirect()->route('admin.exam-scheduling.show', $exam_session)->with('success', 'Exam session updated.');
    }

    /**
     * Destroy (delete) an exam session.
     */
    public function destroy(ExamSession $exam_session): RedirectResponse
    {
        $this->authorize('delete', $exam_session);

        $exam_session->delete();

        return redirect()->route('admin.exam-scheduling.index')->with('success', 'Session deleted.');
    }

    private function redirectIfCompleted(ExamSession $exam_session, string $actionMessage): ?RedirectResponse
    {
        if ($exam_session->status === ExamSession::STATUS_COMPLETED) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)->with('error', $actionMessage);
        }

        return null;
    }

    /**
     * Block schedule actions (publish, release date) when exam is in progress or cancelled.
     * Per EDGE-CASES: publish = announce schedule; cannot announce something already in progress.
     */
    private function redirectIfNotPublishable(ExamSession $exam_session, string $actionMessage): ?RedirectResponse
    {
        if ($exam_session->status === ExamSession::STATUS_IN_PROGRESS) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)->with('error', $actionMessage);
        }
        if ($exam_session->status === ExamSession::STATUS_CANCELLED) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)->with('error', $actionMessage);
        }

        return null;
    }

    /**
     * Assign applicants to exam session. Per 08-API-SPEC-PHASE1: capacity, accepted only, no duplicate sessions.
     */
    public function assignApplicants(AssignApplicantsRequest $request, ExamSession $exam_session): RedirectResponse|JsonResponse
    {
        if ($redirect = $this->redirectIfCompleted($exam_session, 'You cannot assign applicants to a completed exam session.')) {
            return $redirect;
        }
        $applicantIds = array_values(array_unique(array_map('intval', $request->validated('applicant_ids'))));
        $alreadyAttached = $exam_session->applicants()->whereIn('applicants.id', $applicantIds)->pluck('applicants.id')->all();
        $toAttach = array_diff($applicantIds, $alreadyAttached);
        if (! empty($toAttach)) {
            $exam_session->applicants()->attach($toAttach);
        }

        $exam_session->loadCount('applicants');
        if ($request->wantsJson()) {
            return response()->json([
                'session' => $exam_session->fresh(['room:id,name,building,capacity'])->loadCount('applicants'),
                'assigned_count' => $exam_session->applicants_count,
            ], 200);
        }

        return redirect()->route('admin.exam-scheduling.show', $exam_session)->with('success', 'Applicants assigned.');
    }

    public function removeApplicant(Request $request, ExamSession $exam_session): RedirectResponse
    {
        if ($redirect = $this->redirectIfCompleted($exam_session, 'You cannot remove applicants from a completed exam session.')) {
            return $redirect;
        }
        $this->authorize('update', $exam_session);
        $data = $request->validate(['session_applicant_id' => 'required|integer']);
        $pivotId = (int) $data['session_applicant_id'];
        DB::table('exam_session_applicant')->where('id', $pivotId)->where('exam_session_id', $exam_session->id)->delete();

        return redirect()->route('admin.exam-scheduling.show', $exam_session)->with('success', 'Applicant removed.');
    }

    public function publish(ExamSession $exam_session): RedirectResponse
    {
        if ($redirect = $this->redirectIfCompleted($exam_session, 'You cannot change a completed exam session.')) {
            return $redirect;
        }
        if ($redirect = $this->redirectIfNotPublishable($exam_session, 'You cannot publish an exam session that is in progress or cancelled.')) {
            return $redirect;
        }
        $this->authorize('update', $exam_session);

        if ($exam_session->applicants()->count() === 0) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)
                ->with('error', 'Assign at least one applicant before publishing.');
        }

        $exam_session->update([
            'status' => ExamSession::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        if (SystemSetting::notifyOnPublish()) {
            $exam_session->applicants->each(
                fn ($applicant) => $applicant->notify(new ExamSessionPublished($exam_session))
            );
        }

        // Notify assigned proctors and test_admins on publish
        $exam_session->load('proctors');
        $recipients = $exam_session->proctors;
        $testAdmins = User::whereHas('roles', fn ($q) => $q->where('name', 'test_administrator'))->get();
        Notification::send(
            $recipients->merge($testAdmins)->unique('id'),
            new ExamSessionPublished($exam_session)
        );

        return redirect()->route('admin.exam-scheduling.show', $exam_session)->with('success', 'Session published.');
    }

    public function unpublish(ExamSession $exam_session): RedirectResponse
    {
        if ($exam_session->status !== ExamSession::STATUS_PUBLISHED) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)
                ->with('error', 'Only published sessions can be unpublished.');
        }
        $this->authorize('unpublish', $exam_session);

        $exam_session->update([
            'status' => ExamSession::STATUS_DRAFT,
            'published_at' => null,
        ]);

        return redirect()->route('admin.exam-scheduling.show', $exam_session)->with('success', 'Session unpublished.');
    }

    /**
     * Cancel an in-progress exam session.
     */
    public function cancel(ExamSession $exam_session): RedirectResponse
    {
        if ($exam_session->status !== ExamSession::STATUS_IN_PROGRESS) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)
                ->with('error', 'Only in-progress sessions can be cancelled.');
        }

        $exam_session->update(['status' => ExamSession::STATUS_CANCELLED]);

        // Notify applicants on cancel
        $exam_session->load('applicants');
        $exam_session->applicants->each(
            fn ($applicant) => $applicant->notify(new ExamSessionCancelled($exam_session))
        );

        // Notify assigned proctors and test_admins on cancel
        $recipients = $exam_session->proctors;
        $testAdmins = User::whereHas('roles', fn ($q) => $q->where('name', 'test_administrator'))->get();
        Notification::send(
            $recipients->merge($testAdmins)->unique('id'),
            new ExamSessionCancelled($exam_session)
        );

        return redirect()->route('admin.exam-scheduling.index')->with('success', 'Session cancelled.');
    }

    public function start(ExamSession $exam_session): RedirectResponse
    {
        $this->authorize('start', $exam_session);

        if (! $exam_session->isWithinStartWindow()) {
            $user = request()->user();
            if (! $user->hasAnyRole(['super_admin', 'test_administrator'])) {
                return redirect()->route('admin.exam-scheduling.show', $exam_session)
                    ->with('error', 'Cannot start this session outside the scheduled time window.');
            }
        }

        $exam_session->update([
            'status' => ExamSession::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        // Notify assigned proctors and test_admins
        $recipients = $exam_session->proctors;
        $testAdmins = User::whereHas('roles', fn ($q) => $q->where('name', 'test_administrator'))->get();
        Notification::send(
            $recipients->merge($testAdmins)->unique('id'),
            new ExamSessionStarted($exam_session)
        );

        return back()->with('success', 'Session started.');
    }

    public function complete(ExamSession $exam_session): RedirectResponse
    {
        $this->authorize('complete', $exam_session);

        $exam_session->update([
            'status' => ExamSession::STATUS_COMPLETED,
            'closed_at' => now(),
        ]);

        // Notify assigned proctors and test_admins
        $recipients = $exam_session->proctors;
        $testAdmins = User::whereHas('roles', fn ($q) => $q->where('name', 'test_administrator'))->get();
        Notification::send(
            $recipients->merge($testAdmins)->unique('id'),
            new ExamSessionCompleted($exam_session)
        );

        return back()->with('success', 'Session completed.');
    }

    /**
     * Reopen a completed exam session (set status back to in_progress).
     * Admin/super_admin only; e.g. for late examinee or accidental close.
     * Per E-020: Cannot reopen if grading session exists and is finalized.
     */
    public function reopen(ExamSession $exam_session): RedirectResponse
    {
        $this->authorize('reopen', $exam_session);

        if ($exam_session->status !== ExamSession::STATUS_COMPLETED) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)
                ->with('error', 'Only completed sessions can be reopened.');
        }

        $gradingSession = $exam_session->gradingSession;
        if ($gradingSession && $gradingSession->status === GradingSession::STATUS_FINALIZED) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)
                ->with('error', 'Cannot reopen: grading for this exam is already finalized.');
        }

        $exam_session->update([
            'status' => ExamSession::STATUS_IN_PROGRESS,
            'closed_at' => null,
        ]);

        // Notify assigned proctors and test_admins on reopen
        $exam_session->load('proctors');
        $recipients = $exam_session->proctors;
        $testAdmins = User::whereHas('roles', fn ($q) => $q->where('name', 'test_administrator'))->get();
        Notification::send(
            $recipients->merge($testAdmins)->unique('id'),
            new ExamSessionStarted($exam_session)
        );

        return redirect()->route('admin.exam-scheduling.show', $exam_session)
            ->with('success', 'Session reopened. Proctors can continue marking attendance and submissions.');
    }

    /**
     * Session list for Test Administrators.
     * - test_administrator (only): sessions where they are assigned as a proctor.
     * - admin / super_admin: all sessions.
     * Date-grouped per D-15 with policy-based can_start / can_complete flags per D-16.
     */
    public function testAdminIndex(Request $request): Response
    {
        $user = $request->user();
        $isTestAdminOnly = $user->hasAnyRole(['test_administrator']) && ! $user->hasAnyRole(['super_admin', 'registrar_administrator']);

        $activeAcademicYear = AcademicYear::active();
        $queryAcademicYearId = $activeAcademicYear?->id;
        if ($request->filled('academic_year_id')) {
            $queryAcademicYearId = (int) $request->input('academic_year_id');
        }

        $query = ExamSession::query()
            ->with(['room:id,name,building,capacity', 'proctors:id,name', 'academicYear:id,academic_year,semester'])
            ->withCount('applicants')
            ->orderBy('date')
            ->orderBy('start_time');

        if ($queryAcademicYearId !== null) {
            $query->forAcademicYear($queryAcademicYearId);
        }

        if ($isTestAdminOnly) {
            $query->whereHas('proctors', fn ($q) => $q->where('users.id', $user->id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $sessions = $query->get();

        // Date-group the sessions server-side per D-15 with policy-based flags per D-16
        $grouped = $sessions->map(fn ($s) => [
            'id' => $s->id,
            'date' => $s->date?->format('Y-m-d'),
            'start_time' => $s->start_time,
            'end_time' => $s->end_time,
            'room_name' => $s->room?->name,
            'building' => $s->room?->building,
            'status' => $s->status,
            'applicants_count' => $s->applicants_count,
            'is_within_start_window' => $s->isWithinStartWindow(),
            'is_past_end' => $s->isPastEndTime(),
            'can_start' => $user->can('start', $s),
            'can_complete' => $user->can('complete', $s),
        ]);

        $today = $grouped->filter(fn ($s) => Carbon::parse($s['date'])->isToday())->values();
        $upcoming = $grouped->filter(fn ($s) => Carbon::parse($s['date'])->isFuture() && ! Carbon::parse($s['date'])->isToday())->values();
        $past = $grouped->filter(fn ($s) => Carbon::parse($s['date'])->isPast() && ! Carbon::parse($s['date'])->isToday())->values();

        return Inertia::render('Admin/TestAdmin/Index', [
            'today' => $today,
            'upcoming' => $upcoming,
            'past' => $past,
            'filters' => $request->only(['status', 'academic_year_id']),
            'statuses' => self::statusesList(),
        ]);
    }

    /**
     * Full session roster for Test Administrators (and Admin / Super Admin).
     * Passes fullPermissions (all flags true) to the shared SessionRoster component.
     */
    public function testAdminRoster(ExamSession $exam_session, Request $request): Response
    {
        $user = $request->user();

        // Authorization: must be admin/super_admin OR be an assigned proctor on this session.
        if (! $user->hasAnyRole(['super_admin', 'registrar_administrator'])) {
            $assigned = $exam_session->proctors()->where('users.id', $user->id)->exists();
            abort_unless($assigned, 403, 'You are not assigned to this session.');
        }

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

        $isWithinStartWindow = $exam_session->isWithinStartWindow();
        // Test administrators also have override capability (same as admin).
        $canOverrideSchedule = true;

        $fullPermissions = [
            'canStart' => true,
            'canClose' => true,
            'canMarkAttendance' => true,
            'canLogSubmission' => true,
            'canBulkSubmit' => true,
            'canOverrideSchedule' => true,
            'canRemoveApplicant' => true,
            'canReassignRoom' => true,
            'canAddApplicant' => true,
            'showAnalytics' => true,
        ];

        return Inertia::render('Admin/TestAdmin/Roster', [
            'session' => array_merge($exam_session->toArray(), [
                'is_within_start_window' => $isWithinStartWindow,
                'can_override_schedule' => $canOverrideSchedule,
                'is_past_end' => $exam_session->isPastEndTime(),
            ]),
            'applicants' => $applicants->values()->all(),
            'stats' => $stats,
            'permissions' => $fullPermissions,
        ]);
    }

    public function monitoring(Request $request): Response
    {
        $this->authorize('viewAny', ExamSession::class);

        $user = $request->user();
        $isProctorView = $user->hasAnyRole(['proctor']) && ! $user->hasAnyRole(['super_admin', 'registrar_administrator']);
        $activeAcademicYear = AcademicYear::active();

        $query = ExamSession::query()
            ->with(['room:id,name,building,capacity', 'proctors:id,name'])
            ->whereIn('status', [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS])
            ->orderBy('date')
            ->orderBy('start_time');

        if ($activeAcademicYear !== null) {
            $query->forAcademicYear($activeAcademicYear);
        }
        if ($isProctorView) {
            $query->whereHas('proctors', fn ($q) => $q->where('users.id', $user->id));
        }

        $sessions = $query->get();

        return Inertia::render('Admin/TestScheduling/Monitoring', [
            'sessions' => $sessions,
        ]);
    }
}
