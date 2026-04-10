<?php

namespace App\Http\Controllers;

use App\Http\Requests\DismissApplicationRequest;
use App\Http\Requests\StoreApplicationRequest;
use App\Jobs\SendApplicantSetupEmail;
use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Appointment;
use App\Models\Course;
use App\Services\AdmissionSlipService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    /**
     * List applications with filters. Per 08-API-SPEC-PHASE1 and 09-UI-ROUTES-PHASE1.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Application::class);

        $activeAcademicYear = AcademicYear::active();
        $academicYearId = $request->input('academic_year_id');
        if ($academicYearId !== null && $academicYearId !== '') {
            $queryAcademicYearId = (int) $academicYearId;
        } else {
            $queryAcademicYearId = $activeAcademicYear?->id;
        }

        $query = Application::query()
            ->with(['coursePreference1:id,name,code', 'coursePreference2:id,name,code', 'coursePreference3:id,name,code', 'academicYear:id,academic_year,semester']);

        if ($queryAcademicYearId !== null) {
            $query->forAcademicYear($queryAcademicYearId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($status = $request->input('status')) {
            if (in_array($status, ['pending', 'accepted', 'dismissed'], true)) {
                $query->where('status', $status);
            }
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('submitted_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('submitted_at', '<=', $dateTo);
        }

        $applications = $query->orderByDesc('submitted_at')->paginate(15)->withQueryString();

        $applications->getCollection()->transform(function (Application $app) {
            $parts = array_filter([$app->first_name, $app->middle_name, $app->last_name, $app->suffix]);
            $fullName = implode(' ', $parts);
            $courses = [
                ['rank' => 1, 'course' => $app->coursePreference1 ? ['id' => $app->coursePreference1->id, 'code' => $app->coursePreference1->code, 'name' => $app->coursePreference1->name] : null],
                ['rank' => 2, 'course' => $app->coursePreference2 ? ['id' => $app->coursePreference2->id, 'code' => $app->coursePreference2->code, 'name' => $app->coursePreference2->name] : null],
                ['rank' => 3, 'course' => $app->coursePreference3 ? ['id' => $app->coursePreference3->id, 'code' => $app->coursePreference3->code, 'name' => $app->coursePreference3->name] : null],
            ];

            return [
                'id' => $app->id,
                'reference_number' => $app->reference_number,
                'full_name' => $fullName,
                'email' => $app->email,
                'status' => $app->status,
                'submitted_at' => $app->submitted_at?->toIso8601String(),
                'course_preferences' => $courses,
            ];
        });

        $academicYears = AcademicYear::query()->orderByDesc('academic_year')->orderBy('semester')->get(['id', 'academic_year', 'semester', 'is_active']);

        return Inertia::render('Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'academic_year_id']),
            'seasons' => $academicYears,
            'active_season_id' => $activeAcademicYear?->id,
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'accepted', 'label' => 'Accepted'],
                ['value' => 'dismissed', 'label' => 'Dismissed'],
            ],
        ]);
    }

    /**
     * Show single application. Per 08-API-SPEC-PHASE1 and 09-UI-ROUTES-PHASE1.
     */
    public function show(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['coursePreference1:id,name,code', 'coursePreference2:id,name,code', 'coursePreference3:id,name,code', 'appointment', 'academicYear:id,application_start_date,application_end_date']);

        $courses = $this->getCourses();

        $appointmentLabel = null;
        if ($application->appointment) {
            $apt = $application->appointment;
            $appointmentLabel = $apt->date->format('Y-m-d').' '.substr($apt->time_slot, 0, 5);
        }

        $academicYear = $application->academicYear;
        $withinApplicationWindow = $academicYear?->isApplicationWindowOpen() ?? false;
        $applicationWindowLabel = $academicYear?->applicationWindowLabel() ?? null;

        $applicationData = [
            'id' => $application->id,
            'reference_number' => $application->reference_number,
            'first_name' => $application->first_name,
            'middle_name' => $application->middle_name,
            'last_name' => $application->last_name,
            'suffix' => $application->suffix,
            'birthdate' => $application->birthdate->format('Y-m-d'),
            'age' => $application->age,
            'sex' => $application->sex,
            'email' => $application->email,
            'phone' => $application->phone,
            'address_line' => $application->address_line,
            'city' => $application->city,
            'province' => $application->province,
            'zip_code' => $application->zip_code,
            'course_preference_1' => $application->course_preference_1,
            'course_preference_2' => $application->course_preference_2,
            'course_preference_3' => $application->course_preference_3,
            'course_preference_1_label' => $application->coursePreference1?->name,
            'course_preference_2_label' => $application->coursePreference2?->name,
            'course_preference_3_label' => $application->coursePreference3?->name,
            'status' => $application->status,
            'processed_at' => $application->processed_at?->toIso8601String(),
            'rejection_reason' => $application->rejection_reason,
            'appointment_id' => $application->appointment_id,
            'appointment_label' => $appointmentLabel,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'created_at' => $application->created_at?->toIso8601String(),
        ];

        return Inertia::render('Applications/Show', [
            'application' => $applicationData,
            'courses' => $courses,
            'within_application_window' => $withinApplicationWindow,
            'application_window_label' => $applicationWindowLabel,
        ]);
    }

    public function create(): Response
    {
        $activeAcademicYear = AcademicYear::active();
        $courses = $this->getCourses();
        $appointments = $this->getAppointments();

        $allowApply = $activeAcademicYear !== null && $activeAcademicYear->isApplicationWindowOpen();

        return Inertia::render('Applications/Apply', [
            'courses' => $courses,
            'appointments' => $appointments,
            'active_season' => $activeAcademicYear ? ['id' => $activeAcademicYear->id, 'academic_year' => $activeAcademicYear->academic_year, 'semester' => $activeAcademicYear->semester, 'semester_label' => $activeAcademicYear->semesterLabel()] : null,
            'allow_apply' => $allowApply,
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $activeAcademicYear = AcademicYear::active();
        if ($activeAcademicYear === null || ! $activeAcademicYear->isApplicationWindowOpen()) {
            abort(422, 'The application window is currently closed. Please try again later or contact the office.');
        }

        $validated = $request->validated();

        $birthdate = Carbon::parse($validated['birthdate']);
        $age = (int) $birthdate->diffInYears(now());

        $referenceNumber = Application::nextReferenceNumber();

        $application = Application::create([
            'academic_year_id' => $activeAcademicYear->id,
            'reference_number' => $referenceNumber,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'birthdate' => $validated['birthdate'],
            'age' => $age,
            'sex' => $validated['sex'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address_line' => $validated['address_line'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'course_preference_1' => $validated['course_preference_1'],
            'course_preference_2' => ! empty($validated['course_preference_2']) ? (int) $validated['course_preference_2'] : null,
            'course_preference_3' => ! empty($validated['course_preference_3']) ? (int) $validated['course_preference_3'] : null,
            'status' => 'pending',
            'appointment_id' => $validated['appointment_id'] ?? null,
            'submitted_at' => now(),
        ]);

        if (! empty($validated['appointment_id'])) {
            Appointment::where('id', $validated['appointment_id'])->increment('booked_count');
        }

        $appointmentDetails = null;
        if ($application->appointment_id) {
            $apt = $application->appointment;
            $appointmentDetails = $apt ? $apt->date->format('Y-m-d').' '.substr($apt->time_slot, 0, 5) : null;
        }

        return redirect()
            ->route('applications.success')
            ->with('reference_number', $application->reference_number)
            ->with('appointment_details', $appointmentDetails);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Applications/Success', [
            'reference_number' => $request->session()->get('reference_number', 'APP-MOCK-00001'),
            'appointment_details' => $request->session()->get('appointment_details'),
        ]);
    }

    /**
     * Accept application. Per 08-API-SPEC-PHASE1: create applicant, send setup email, audit.
     * Allowed from pending or dismissed only when within application window.
     */
    public function accept(Application $application): RedirectResponse
    {
        $this->authorize('accept', $application);

        $allowedStatuses = ['pending', 'dismissed'];
        if (! in_array($application->status, $allowedStatuses, true)) {
            return redirect()
                ->back()
                ->with('error', 'Application has already been accepted.');
        }

        $withinWindow = $application->academicYear?->isApplicationWindowOpen() ?? false;
        if (! $withinWindow) {
            return redirect()
                ->back()
                ->with('error', 'Status can only be changed while the application window is open.');
        }

        DB::transaction(function () use ($application) {
            $application->update([
                'status' => 'accepted',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $applicant = Applicant::where('application_id', $application->id)->first();
            if (! $applicant) {
                $setupToken = Applicant::generateSetupToken();
                $expiresAt = now()->addHours(72);
                $applicant = Applicant::create([
                    'application_id' => $application->id,
                    'email' => $application->email,
                    'setup_token' => $setupToken,
                    'setup_token_expires_at' => $expiresAt,
                ]);
                SendApplicantSetupEmail::dispatch($applicant);
            }

            Log::info('Application accepted', [
                'application_id' => $application->id,
                'reference_number' => $application->reference_number,
                'processed_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Application accepted. Setup email has been sent to the applicant.');
    }

    /**
     * Resend portal setup email for an accepted application (e.g. applicant didn't receive it).
     */
    public function resendSetupEmail(Application $application): RedirectResponse
    {
        $this->authorize('resendSetupEmail', $application);

        if ($application->status !== 'accepted') {
            return redirect()
                ->back()
                ->with('error', 'Only accepted applications can have the setup email resent.');
        }

        $applicant = Applicant::where('application_id', $application->id)->first();

        if (! $applicant) {
            return redirect()
                ->back()
                ->with('error', 'No applicant record found for this application.');
        }

        if ($applicant->hasCompletedSetup()) {
            return redirect()
                ->route('applications.show', $application)
                ->with('success', 'This applicant has already set up their portal account. They can sign in at the portal.');
        }

        $applicant->setup_token = Applicant::generateSetupToken();
        $applicant->setup_token_expires_at = now()->addHours(72);
        $applicant->save();

        SendApplicantSetupEmail::dispatch($applicant);

        Log::info('Setup email resent', [
            'application_id' => $application->id,
            'reference_number' => $application->reference_number,
            'by' => auth()->id(),
        ]);

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Portal setup email has been resent to the applicant.');
    }

    /**
     * Dismiss application. Allowed only when within application window.
     */
    public function dismiss(DismissApplicationRequest $request, Application $application): RedirectResponse
    {
        $withinWindow = $application->academicYear?->isApplicationWindowOpen() ?? false;
        if (! $withinWindow) {
            return redirect()
                ->back()
                ->with('error', 'Dismiss is only allowed while the application window is open.');
        }

        $allowedStatuses = ['pending'];
        if (! in_array($application->status, $allowedStatuses, true)) {
            return redirect()
                ->back()
                ->with('error', 'Application cannot be dismissed from its current status.');
        }

        if ($application->appointment_id) {
            Appointment::where('id', $application->appointment_id)->where('booked_count', '>', 0)->decrement('booked_count');
        }

        $application->update([
            'status' => 'dismissed',
            'rejection_reason' => $request->validated('reason'),
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        Log::info('Application dismissed', [
            'application_id' => $application->id,
            'reference_number' => $application->reference_number,
            'processed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Application has been dismissed.');
    }

    /**
     * Download admission slip PDF. Per 08-API-SPEC-PHASE1: 403 if not accepted.
     */
    public function admissionSlip(Application $application): StreamedResponse|\Illuminate\Http\Response
    {
        $this->authorize('admissionSlip', $application);

        if ($application->status !== 'accepted') {
            abort(403, 'Admission slip is only available for accepted applications.');
        }

        $pdf = app(AdmissionSlipService::class)->generatePdf($application);
        $filename = "admission-slip-{$application->reference_number}.pdf";

        return $pdf->download($filename);
    }

    private function getCourses(): array
    {
        $defaults = [
            ['id' => 1, 'name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT'],
            ['id' => 2, 'name' => 'Bachelor of Science in Computer Science', 'code' => 'BSCS'],
            ['id' => 3, 'name' => 'Bachelor of Science in Data Science', 'code' => 'BSDS'],
        ];

        if (DB::getSchemaBuilder()->hasTable('courses')) {
            $rows = Course::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
            if ($rows->isNotEmpty()) {
                return $rows->unique('id')->values()->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'code' => $r->code])->all();
            }
        }

        return $defaults;
    }

    private function getAppointments(): array
    {
        if (DB::getSchemaBuilder()->hasTable('appointments')) {
            $rows = Appointment::query()
                ->where('is_active', true)
                ->whereColumn('booked_count', '<', 'max_slots')
                ->where('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->orderBy('time_slot')
                ->limit(50)
                ->get();

            return $rows->map(fn ($r) => [
                'id' => $r->id,
                'date' => $r->date->format('Y-m-d'),
                'time_slot' => $r->time_slot,
                'duration_minutes' => $r->duration_minutes,
                'max_slots' => $r->max_slots,
                'booked_count' => $r->booked_count,
                'label' => $r->date->format('Y-m-d').' '.substr($r->time_slot, 0, 5),
            ])->all();
        }

        return [];
    }
}
