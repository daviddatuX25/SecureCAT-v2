<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Models\Application;
use App\Models\Appointment;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    /**
     * List applications with filters. Per 08-API-SPEC-PHASE1 and 09-UI-ROUTES-PHASE1.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Application::class);

        $query = Application::query()
            ->with(['coursePreference1:id,name,code', 'coursePreference2:id,name,code', 'coursePreference3:id,name,code']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($status = $request->input('status')) {
            if (in_array($status, ['pending', 'accepted', 'rejected'], true)) {
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

        return Inertia::render('Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'accepted', 'label' => 'Accepted'],
                ['value' => 'rejected', 'label' => 'Rejected'],
            ],
        ]);
    }

    /**
     * Show single application. Per 08-API-SPEC-PHASE1 and 09-UI-ROUTES-PHASE1.
     */
    public function show(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['coursePreference1:id,name,code', 'coursePreference2:id,name,code', 'coursePreference3:id,name,code', 'appointment']);

        $courses = $this->getCourses();

        $appointmentLabel = null;
        if ($application->appointment) {
            $apt = $application->appointment;
            $appointmentLabel = $apt->date->format('Y-m-d') . ' ' . substr($apt->time_slot, 0, 5);
        }

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
        ]);
    }

    public function create(): Response
    {
        $courses = $this->getCourses();
        $appointments = $this->getAppointments();

        return Inertia::render('Applications/Apply', [
            'courses' => $courses,
            'appointments' => $appointments,
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $birthdate = Carbon::parse($validated['birthdate']);
        $age = (int) $birthdate->diffInYears(now());

        $referenceNumber = Application::nextReferenceNumber();

        $application = Application::create([
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
            'course_preference_2' => $validated['course_preference_2'],
            'course_preference_3' => $validated['course_preference_3'],
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
            $appointmentDetails = $apt ? $apt->date->format('Y-m-d') . ' ' . substr($apt->time_slot, 0, 5) : null;
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
                return $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'code' => $r->code])->all();
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
                'label' => $r->date->format('Y-m-d') . ' ' . substr($r->time_slot, 0, 5),
            ])->all();
        }

        return [];
    }
}
