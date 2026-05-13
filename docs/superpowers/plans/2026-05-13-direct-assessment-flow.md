# Direct Assessment Flow — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add walk-in / direct exam sessions that bypass scheduling, letting staff create applicants and immediately encode scores without a physical room or time window.

**Architecture:** Extend `ExamSession` with a `type` enum column (`scheduled` | `direct`). A new `DirectAssessmentService` creates an `ExamSession(type=direct)` and delegates to the existing `GradingSessionService::openForExamSession()`. All downstream code (grading, scoring, reporting) is unchanged — a direct session is just an exam session with different `type`.

**Tech Stack:** Laravel 12, Svelte 5, Inertia.js v2, Tailwind CSS v4, PHPUnit 11

---

## File Structure

### New files

| File | Responsibility |
|------|---------------|
| `database/migrations/2026_05_13_000001_add_type_and_label_to_exam_sessions.php` | Adds `type` (default `scheduled`) + `label` (nullable) columns |
| `database/migrations/2026_05_13_000002_make_room_nullable_on_exam_sessions.php` | Makes `room_id` nullable for direct sessions |
| `app/Services/DirectAssessmentService.php` | Creates ExamSession(type=direct) + delegates to GradingSessionService |
| `app/Http/Controllers/DirectAssessmentController.php` | Handles POST /direct-assessments |
| `app/Http/Requests/StoreDirectAssessmentRequest.php` | Validates request payload |
| `tests/Feature/DirectAssessmentTest.php` | Feature tests for the entire flow |
| `resources/js/Pages/Admin/DirectAssessment/Create.svelte` | Modal/page for creating a direct assessment |

### Modified files

| File | Change |
|------|--------|
| `app/Models/ExamSession.php` | Add `TYPE_DIRECT` / `TYPE_SCHEDULED` constants, `isDirect()`, `type`+`label` to `$fillable`, `type` to `$casts` |
| `database/factories/ExamSessionFactory.php` | Add `'type' => 'scheduled'` default + `direct()` state |
| `app/Models/SystemSetting.php` | Add `allowDirectAssessment()` accessor |
| `app/Http/Controllers/Admin/SettingsController.php` | Pass `allow_direct_assessment` to view, handle toggle on update |
| `app/Http/Requests/UpdateSystemSettingsRequest.php` | Add `allow_direct_assessment` validation rule |
| `app/Http/Controllers/Admin/ExamSessionController.php` | Skip room/window validation when `type = direct` in `store()` and `update()` |
| `app/Http/Requests/StoreExamSessionRequest.php` | Change `room_id` to `required_if:type,scheduled` |
| `app/Http/Requests/UpdateExamSessionRequest.php` | Change `room_id` to `sometimes|required_if:type,scheduled` |
| `routes/web.php` | Register `direct-assessments` route |
| `resources/js/Pages/Admin/Settings/Index.svelte` | Add Direct Assessment toggle card |
| `resources/js/Pages/Admin/TestScheduling/Index.svelte` | Show `Direct` badge, exclude direct sessions from calendar |
| `resources/js/Pages/Grading/Session.svelte` | Show `Direct` badge in grading session header |
| `resources/js/Pages/Grading/Dashboard.svelte` | Show `Direct` badge in grading sessions list |

---

## Task 1: Migrations — Add `type`, `label`, and nullable `room_id`

**Files:**
- Create: `database/migrations/2026_05_13_000001_add_type_and_label_to_exam_sessions.php`
- Create: `database/migrations/2026_05_13_000002_make_room_nullable_on_exam_sessions.php`

- [ ] **Step 1: Create the first migration for `type` and `label` columns**

Run: `php artisan make:migration add_type_and_label_to_exam_sessions --table=exam_sessions`

Then replace the generated file content with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('type')->default('scheduled')->after('status');
            $table->string('label')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['type', 'label']);
        });
    }
};
```

- [ ] **Step 2: Create the second migration to make `room_id` nullable**

Run: `php artisan make:migration make_room_nullable_on_exam_sessions --table=exam_sessions`

Then replace the generated file content with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 3: Run migrations and verify**

Run: `php artisan migrate`

Expected: Two migrations run successfully. `exam_sessions` now has `type` (default `scheduled`), `label` (nullable), and `room_id` (nullable).

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_05_13_000001_add_type_and_label_to_exam_sessions.php database/migrations/2026_05_13_000002_make_room_nullable_on_exam_sessions.php
git commit -m "feat: add type/label columns and nullable room_id to exam_sessions"
```

---

## Task 2: ExamSession Model — Constants, helper, fillable, casts

**Files:**
- Modify: `app/Models/ExamSession.php`
- Modify: `database/factories/ExamSessionFactory.php`

- [ ] **Step 1: Write a failing test for `isDirect()` and type constants**

Create `tests/Feature/DirectAssessmentTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        return $user;
    }

    public function test_exam_session_has_type_constants(): void
    {
        $this->assertSame('scheduled', ExamSession::TYPE_SCHEDULED);
        $this->assertSame('direct', ExamSession::TYPE_DIRECT);
    }

    public function test_is_direct_returns_true_for_direct_type(): void
    {
        $session = ExamSession::factory()->make(['type' => 'direct']);
        $this->assertTrue($session->isDirect());
    }

    public function test_is_direct_returns_false_for_scheduled_type(): void
    {
        $session = ExamSession::factory()->make(['type' => 'scheduled']);
        $this->assertFalse($session->isDirect());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: FAIL — `ExamSession::TYPE_DIRECT` and `isDirect()` do not exist yet.

- [ ] **Step 3: Update `ExamSession` model**

In `app/Models/ExamSession.php`, add after the existing status constants:

```php
public const TYPE_SCHEDULED = 'scheduled';

public const TYPE_DIRECT = 'direct';
```

Add to `$fillable` array (after `'created_by'`):

```php
'type',
'label',
```

Add `'type'` cast in `casts()`:

```php
protected function casts(): array
{
    return [
        'date' => 'date',
        'published_at' => 'datetime',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'type' => 'string',
    ];
}
```

Add helper method after `isPastEndTime()`:

```php
public function isDirect(): bool
{
    return $this->type === self::TYPE_DIRECT;
}
```

- [ ] **Step 4: Update `ExamSessionFactory`**

In `database/factories/ExamSessionFactory.php`, add `'type' => 'scheduled'` to the `definition()` return array, and add a `direct()` state:

```php
public function definition(): array
{
    return [
        'academic_year_id' => AcademicYear::factory(),
        'room_id' => Room::factory(),
        'date' => $this->faker->dateTimeBetween('+1 days', '+30 days')->format('Y-m-d'),
        'start_time' => '09:00',
        'end_time' => '12:00',
        'status' => 'draft',
        'type' => 'scheduled',
        'created_by' => User::factory(),
    ];
}

public function direct(): static
{
    return $this->state(fn (array $attributes) => [
        'type' => 'direct',
        'room_id' => null,
        'date' => now()->format('Y-m-d'),
        'start_time' => now()->format('H:i:s'),
        'end_time' => null,
        'status' => 'in_progress',
        'label' => 'Walk-in ' . $this->faker->numberBetween(1, 99),
    ]);
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: PASS — all three model tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Models/ExamSession.php database/factories/ExamSessionFactory.php tests/Feature/DirectAssessmentTest.php
git commit -m "feat: add type constants, isDirect(), and factory direct() state to ExamSession"
```

---

## Task 3: DirectAssessmentService + StoreDirectAssessmentRequest

**Files:**
- Create: `app/Services/DirectAssessmentService.php`
- Create: `app/Http/Requests/StoreDirectAssessmentRequest.php`

- [ ] **Step 1: Write failing tests for the service**

Add to `tests/Feature/DirectAssessmentTest.php`:

```php
use App\Models\Applicant;
use App\Models\Application;
use App\Services\DirectAssessmentService;
use App\Models\GradingSession;

public function test_direct_assessment_creates_exam_session_and_grading_session(): void
{
    $admin = $this->actingAsAdmin();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
    $applicant = Applicant::factory()->create(['application_id' => $application->id]);

    $service = app(DirectAssessmentService::class);
    $gradingSession = $service->create(
        academicYear: $academicYear,
        applicantIds: [$applicant->id],
        openedBy: $admin,
        label: 'Walk-in Batch 1'
    );

    $this->assertInstanceOf(GradingSession::class, $gradingSession);
    $this->assertEquals('open', $gradingSession->status);

    $examSession = $gradingSession->examSession;
    $this->assertEquals('direct', $examSession->type);
    $this->assertEquals('Walk-in Batch 1', $examSession->label);
    $this->assertEquals('in_progress', $examSession->status);
    $this->assertNull($examSession->room_id);
    $this->assertEquals($academicYear->id, $examSession->academic_year_id);

    $this->assertTrue($examSession->applicants()->where('applicant_id', $applicant->id)->exists());
    $this->assertEquals('present', $examSession->applicants()->first()->pivot->attendance_status);
}

public function test_direct_assessment_rejects_non_accepted_applicant(): void
{
    $admin = $this->actingAsAdmin();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $application = Application::factory()->create(['status' => 'pending', 'academic_year_id' => $academicYear->id]);
    $applicant = Applicant::factory()->create(['application_id' => $application->id]);

    $validator = app('validator')->make(
        ['academic_year_id' => $academicYear->id, 'applicant_ids' => [$applicant->id]],
        (new StoreDirectAssessmentRequest())->rules()
    );

    $this->assertTrue($validator->fails());
}

public function test_direct_assessment_rejects_applicant_already_in_active_grading(): void
{
    $admin = $this->actingAsAdmin();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
    $applicant = Applicant::factory()->create(['application_id' => $application->id]);

    // Create a prior direct assessment with this applicant
    $service = app(DirectAssessmentService::class);
    $service->create(
        academicYear: $academicYear,
        applicantIds: [$applicant->id],
        openedBy: $admin,
    );

    // Attempting again should fail validation
    $validator = app('validator')->make(
        ['academic_year_id' => $academicYear->id, 'applicant_ids' => [$applicant->id]],
        (new StoreDirectAssessmentRequest())->rules()
    );

    $this->assertTrue($validator->fails());
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: FAIL — `DirectAssessmentService` and `StoreDirectAssessmentRequest` do not exist yet.

- [ ] **Step 3: Create `StoreDirectAssessmentRequest`**

Create `app/Http/Requests/StoreDirectAssessmentRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Models\Applicant;
use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class StoreDirectAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! SystemSetting::allowDirectAssessment()) {
            return false;
        }

        return $this->user()?->hasAnyRole(['super_admin', 'registrar_administrator', 'test_administrator']) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'applicant_ids' => ['required', 'array', 'min:1'],
            'applicant_ids.*' => [
                'integer',
                'exists:applicants,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $applicant = Applicant::find($value);
                    if (! $applicant) {
                        return;
                    }

                    // Must have an accepted application
                    if (! $applicant->application || $applicant->application->status !== 'accepted') {
                        $fail("Applicant ID {$value} does not have an accepted application.");
                    }

                    // Must not already be in an active grading session
                    $inActiveSession = $applicant->examSessions()
                        ->whereHas('gradingSession', fn ($q) => $q->whereNotIn('status', ['finalized']))
                        ->exists();

                    if ($inActiveSession) {
                        $fail("Applicant ID {$value} is already in an active grading session.");
                    }
                },
            ],
            'label' => ['nullable', 'string', 'max:100'],
        ];
    }
}
```

- [ ] **Step 4: Create `DirectAssessmentService`**

Create `app/Services/DirectAssessmentService.php`:

```php
<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DirectAssessmentService
{
    public function __construct(
        private GradingSessionService $gradingService
    ) {}

    /**
     * Create a direct assessment: ExamSession(type=direct) + GradingSession + auto-attendance.
     *
     * @param  array<int>  $applicantIds  Accepted applicant IDs to include
     */
    public function create(
        AcademicYear $academicYear,
        array $applicantIds,
        User $openedBy,
        ?string $label = null
    ): GradingSession {
        return DB::transaction(function () use ($academicYear, $applicantIds, $openedBy, $label) {
            // 1. Create ExamSession with type=direct
            $examSession = ExamSession::create([
                'academic_year_id' => $academicYear->id,
                'type' => ExamSession::TYPE_DIRECT,
                'label' => $label,
                'status' => ExamSession::STATUS_IN_PROGRESS,
                'room_id' => null,
                'date' => now()->format('Y-m-d'),
                'start_time' => now()->format('H:i:s'),
                'end_time' => null,
                'created_by' => $openedBy->id,
            ]);

            // 2. Assign applicants with auto-present attendance
            foreach ($applicantIds as $id) {
                $examSession->applicants()->attach($id, [
                    'attendance_status' => 'present',
                    'attendance_marked_at' => now(),
                    'attendance_marked_by' => $openedBy->id,
                ]);
            }

            // 3. Delegate to existing GradingSessionService
            return $this->gradingService->openForExamSession($examSession, $openedBy);
        });
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: PASS — service creates direct exam session + grading session, request validates correctly.

- [ ] **Step 6: Commit**

```bash
git add app/Services/DirectAssessmentService.php app/Http/Requests/StoreDirectAssessmentRequest.php tests/Feature/DirectAssessmentTest.php
git commit -m "feat: add DirectAssessmentService and StoreDirectAssessmentRequest"
```

---

## Task 4: DirectAssessmentController + Route Registration

**Files:**
- Create: `app/Http/Controllers/DirectAssessmentController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing test for the controller endpoint**

Add to `tests/Feature/DirectAssessmentTest.php`:

```php
use App\Models\SystemSetting;

public function test_store_direct_assessment_redirects_to_grading_session(): void
{
    $admin = $this->actingAsAdmin();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    SystemSetting::set('allow_direct_assessment', true);
    $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
    $applicant = Applicant::factory()->create(['application_id' => $application->id]);

    $response = $this->post('/admin/direct-assessments', [
        'academic_year_id' => $academicYear->id,
        'applicant_ids' => [$applicant->id],
        'label' => 'Walk-in Batch 3',
    ]);

    $gradingSession = GradingSession::latest()->first();
    $response->assertRedirect(route('admin.grading.sessions.show', $gradingSession->id));
}

public function test_store_direct_assessment_returns_403_when_disabled(): void
{
    $admin = $this->actingAsAdmin();
    SystemSetting::set('allow_direct_assessment', false);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);

    $response = $this->post('/admin/direct-assessments', [
        'academic_year_id' => $academicYear->id,
        'applicant_ids' => [1],
    ]);

    $response->assertForbidden();
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: FAIL — route `/admin/direct-assessments` does not exist yet.

- [ ] **Step 3: Create `DirectAssessmentController`**

Create `app/Http/Controllers/DirectAssessmentController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDirectAssessmentRequest;
use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Services\DirectAssessmentService;
use Inertia\Inertia;
use Inertia\Response;

class DirectAssessmentController extends Controller
{
    public function __construct(
        private DirectAssessmentService $service
    ) {}

    public function create(): Response
    {
        $academicYears = AcademicYear::query()
            ->orderByDesc('academic_year')
            ->orderBy('semester')
            ->get(['id', 'academic_year', 'semester', 'is_active']);

        $activeAcademicYear = AcademicYear::active();

        // Eligible applicants: accepted, not in an active grading session
        $applicants = Applicant::query()
            ->whereHas('application', fn ($q) => $q->where('status', 'accepted'))
            ->whereDoesntHave('examSessions', fn ($q) => $q->whereHas('gradingSession', fn ($gs) => $gs->whereNotIn('status', ['finalized'])))
            ->with('application:id,applicant_id,first_name,middle_name,last_name,suffix,reference_number')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                'reference' => $a->application?->reference_number ?? '—',
            ])
            ->values()
            ->all();

        return Inertia::render('Admin/DirectAssessment/Create', [
            'academicYears' => $academicYears,
            'applicants' => $applicants,
            'activeAcademicYearId' => $activeAcademicYear?->id,
        ]);
    }

    public function store(StoreDirectAssessmentRequest $request)
    {
        $validated = $request->validated();

        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);

        $gradingSession = $this->service->create(
            academicYear: $academicYear,
            applicantIds: $validated['applicant_ids'],
            openedBy: $request->user(),
            label: $validated['label'] ?? null
        );

        return redirect()
            ->route('admin.grading.sessions.show', $gradingSession->id)
            ->with('success', 'Direct assessment session created. You can now encode scores.');
    }
}
```

- [ ] **Step 4: Register the routes**

In `routes/web.php`, add the import at the top:

```php
use App\Http\Controllers\DirectAssessmentController;
```

Add the following route inside the `Route::middleware('role:super_admin,registrar_administrator')` admin group (the same group that contains exam-scheduling store), before the exam-scheduling store route:

```php
Route::get('direct-assessments/create', [DirectAssessmentController::class, 'create'])->name('direct-assessments.create');
Route::post('direct-assessments', [DirectAssessmentController::class, 'store'])->name('direct-assessments.store');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: PASS — POST creates direct assessment and redirects to grading session; disabled setting returns 403.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/DirectAssessmentController.php routes/web.php tests/Feature/DirectAssessmentTest.php
git commit -m "feat: add DirectAssessmentController and route registration"
```

---

## Task 5: ExamSessionController Guards — Skip Room/Window Checks for Direct Sessions

**Files:**
- Modify: `app/Http/Controllers/Admin/ExamSessionController.php`
- Modify: `app/Http/Requests/StoreExamSessionRequest.php`
- Modify: `app/Http/Requests/UpdateExamSessionRequest.php`

- [ ] **Step 1: Write failing test for scheduled validation bypass**

Add to `tests/Feature/DirectAssessmentTest.php`:

```php
use App\Models\Room;

public function test_scheduled_exam_session_still_requires_room(): void
{
    $admin = $this->actingAsAdmin();
    AcademicYear::factory()->create(['is_active' => true]);

    $response = $this->post('/admin/exam-scheduling', [
        'academic_year_id' => AcademicYear::first()->id,
        'room_id' => null,
        'date' => now()->addDay()->format('Y-m-d'),
        'start_time' => '09:00',
        'end_time' => '12:00',
    ]);

    $response->assertSessionHasErrors('room_id');
}
```

- [ ] **Step 2: Run test to verify it fails (or already passes)**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

This test should already pass since `room_id` is currently `required` — but we're about to make it conditional, so we need this guard.

- [ ] **Step 3: Update `StoreExamSessionRequest`**

In `app/Http/Requests/StoreExamSessionRequest.php`, update the `rules()` method:

```php
public function rules(): array
{
    return [
        'academic_year_id' => ['sometimes', 'nullable', 'integer', 'exists:academic_years,id'],
        'room_id' => ['required_if:type,scheduled', 'nullable', 'integer', 'exists:rooms,id'],
        'date' => ['required', 'date', 'after_or_equal:today'],
        'start_time' => ['required', 'string', 'date_format:H:i'],
        'end_time' => ['nullable', 'string', 'date_format:H:i'],
        'type' => ['sometimes', 'in:scheduled,direct'],
        'proctor_ids' => ['sometimes', 'array'],
        'proctor_ids.*' => ['integer', 'exists:users,id'],
    ];
}
```

- [ ] **Step 4: Update `UpdateExamSessionRequest`**

In `app/Http/Requests/UpdateExamSessionRequest.php`, update the `rules()` method:

```php
public function rules(): array
{
    return [
        'academic_year_id' => ['sometimes', 'nullable', 'integer', 'exists:academic_years,id'],
        'room_id' => ['sometimes', 'required_if:type,scheduled', 'nullable', 'integer', 'exists:rooms,id'],
        'date' => ['sometimes', 'date'],
        'start_time' => ['sometimes', 'string', 'date_format:H:i'],
        'end_time' => ['nullable', 'string', 'date_format:H:i'],
        'type' => ['sometimes', 'in:scheduled,direct'],
        'proctor_ids' => ['sometimes', 'array'],
        'proctor_ids.*' => ['integer', 'exists:users,id'],
    ];
}
```

- [ ] **Step 5: Update `ExamSessionController::store()` to skip room conflict check for direct sessions**

In `app/Http/Controllers/Admin/ExamSessionController.php`, find the `store()` method and locate where `hasRoomConflict` is called. Wrap it in a type check so it only runs for scheduled sessions:

```php
// Only check room conflicts for scheduled sessions
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
```

Note: Read the current `store()` method first to find the exact location and context for this change.

- [ ] **Step 6: Run all direct assessment tests**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/StoreExamSessionRequest.php app/Http/Requests/UpdateExamSessionRequest.php app/Http/Controllers/Admin/ExamSessionController.php tests/Feature/DirectAssessmentTest.php
git commit -m "feat: skip room/window validation for direct exam sessions"
```

---

## Task 6: SystemSetting Toggle — `allow_direct_assessment`

**Files:**
- Modify: `app/Models/SystemSetting.php`
- Modify: `app/Http/Controllers/Admin/SettingsController.php`
- Modify: `app/Http/Requests/UpdateSystemSettingsRequest.php`

- [ ] **Step 1: Write failing test for the setting**

Add to `tests/Feature/DirectAssessmentTest.php`:

```php
public function test_allow_direct_assessment_defaults_to_true(): void
{
    $this->assertTrue(SystemSetting::allowDirectAssessment());
}

public function test_allow_direct_assessment_can_be_toggled(): void
{
    SystemSetting::set('allow_direct_assessment', false);
    $this->assertFalse(SystemSetting::allowDirectAssessment());

    SystemSetting::set('allow_direct_assessment', true);
    $this->assertTrue(SystemSetting::allowDirectAssessment());
}
```

Ensure `use App\Models\SystemSetting;` is imported at the top.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: FAIL — `SystemSetting::allowDirectAssessment()` does not exist yet.

- [ ] **Step 3: Add `allowDirectAssessment()` to `SystemSetting`**

In `app/Models/SystemSetting.php`, add after the `personaPrompt()` method:

```php
/**
 * Whether direct assessment (walk-in scoring without scheduling) is enabled. Default: true.
 */
public static function allowDirectAssessment(): bool
{
    return (bool) self::get('allow_direct_assessment', true);
}
```

Also add `'allow_direct_assessment'` to the boolean key list in the `get()` method (find the `in_array` check that already includes `'ai_exam_companion_enabled'`, `'online_release_enabled'`, `'notify_on_publish'`):

```php
if (in_array($key, ['ai_exam_companion_enabled', 'online_release_enabled', 'notify_on_publish', 'allow_direct_assessment'], true)) {
```

- [ ] **Step 4: Update `SettingsController` to pass the setting to the view**

In `app/Http/Controllers/Admin/SettingsController.php`, add to the `index()` method's Inertia data:

```php
'allow_direct_assessment' => SystemSetting::allowDirectAssessment(),
```

Add to the `update()` method, after the existing `release_mode` block:

```php
if (array_key_exists('allow_direct_assessment', $validated)) {
    SystemSetting::set('allow_direct_assessment', (bool) $validated['allow_direct_assessment']);
}
```

- [ ] **Step 5: Update `UpdateSystemSettingsRequest`**

In `app/Http/Requests/UpdateSystemSettingsRequest.php`, add to the `rules()` array:

```php
'allow_direct_assessment' => ['sometimes', 'boolean'],
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=DirectAssessmentTest`

Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Models/SystemSetting.php app/Http/Controllers/Admin/SettingsController.php app/Http/Requests/UpdateSystemSettingsRequest.php tests/Feature/DirectAssessmentTest.php
git commit -m "feat: add allow_direct_assessment SystemSetting toggle"
```

---

## Task 7: Svelte UI — Settings Toggle, Direct Badge, and Direct Assessment Creation Page

**Files:**
- Modify: `resources/js/Pages/Admin/Settings/Index.svelte`
- Create: `resources/js/Pages/Admin/DirectAssessment/Create.svelte`
- Modify: `resources/js/Pages/Admin/TestScheduling/Index.svelte`
- Modify: `resources/js/Pages/Grading/Session.svelte`
- Modify: `resources/js/Pages/Grading/Dashboard.svelte`
- Modify: `app/Http/Controllers/Grading/GradingSessionController.php`

- [ ] **Step 1: Add Direct Assessment toggle to Settings page**

In `resources/js/Pages/Admin/Settings/Index.svelte`:

1. Add `allow_direct_assessment` to the props destructuring:
```svelte
let { ai_exam_companion_enabled = false, notify_on_publish = false, release_mode = 'online', allow_direct_assessment = true } = $props();
```

2. Add to the `form` object:
```svelte
const form = useForm({
    ai_exam_companion_enabled,
    notify_on_publish,
    release_mode,
    allow_direct_assessment,
});
```

3. Add to the `$effect`:
```svelte
form.update((f) => ({
    ...f,
    ai_exam_companion_enabled,
    notify_on_publish,
    release_mode,
    allow_direct_assessment,
}));
```

4. Add to `submitSettings` transform:
```svelte
allow_direct_assessment: !!data.allow_direct_assessment,
```

5. Import the `FileCheck` icon:
```svelte
import { Bot, Bell, Share2, FileCheck } from 'lucide-svelte';
```

6. Add a new Card block after the Result Release Mode card, before the Save button:

```svelte
<Card>
    <CardHeader>
        <CardTitle class="flex items-center gap-2">
            <FileCheck class="h-5 w-5" />
            Direct Assessment
        </CardTitle>
        <CardDescription>
            When enabled, staff can create direct assessment sessions to encode scores immediately without scheduling a physical exam session. Useful for walk-in applicants or offline score entry.
        </CardDescription>
    </CardHeader>
    <CardContent class="flex items-center gap-4">
        <Switch
            checked={$form.allow_direct_assessment}
            onCheckedChange={(checked) => form.update((f) => ({ ...f, allow_direct_assessment: checked }))}
            aria-label="Enable direct assessment"
        />
        <span class="text-sm font-medium">
            {$form.allow_direct_assessment ? 'Enabled' : 'Disabled'}
        </span>
    </CardContent>
</Card>
```

- [ ] **Step 2: Create the Direct Assessment creation page**

Create `resources/js/Pages/Admin/DirectAssessment/Create.svelte`:

```svelte
<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
import { useForm } from '@inertiajs/svelte';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import Label from '@/Components/ui/label/Label.svelte';
import Input from '@/Components/ui/input/Input.svelte';

let { academicYears, applicants, activeAcademicYearId } = $props();

const breadcrumbs = [
    { label: 'Exam Scheduling', href: '/admin/exam-scheduling' },
    { label: 'Direct Assessment' },
];

const form = useForm({
    academic_year_id: activeAcademicYearId || '',
    applicant_ids: [] as number[],
    label: '',
});

let selectedApplicantIds = $state<number[]>([]);

function toggleApplicant(id: number) {
    const idx = selectedApplicantIds.indexOf(id);
    if (idx >= 0) {
        selectedApplicantIds = selectedApplicantIds.filter((i) => i !== id);
    } else {
        selectedApplicantIds = [...selectedApplicantIds, id];
    }
    form.update((f) => ({ ...f, applicant_ids: selectedApplicantIds }));
}

function submit(e: Event) {
    e.preventDefault();
    form.post('/admin/direct-assessments');
}
</script>

<AuthenticatedLayout {breadcrumbs}>
    <div class="space-y-6 min-w-0">
        <div>
            <h1 class="text-2xl font-semibold">Create Direct Assessment Session</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Create a grading session for walk-in or offline score entry. No room or time scheduling required.
            </p>
        </div>

        <form onsubmit={submit} class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Session Details</CardTitle>
                    <CardDescription>Select the academic year and an optional label for this session.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="academic_year_id">Academic Year</Label>
                        <select
                            id="academic_year_id"
                            bind:value={$form.academic_year_id}
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            {#each academicYears as ay}
                                <option value={ay.id}>{ay.academic_year} — Semester {ay.semester}</option>
                            {/each}
                        </select>
                        {#if $form.errors.academic_year_id}
                            <p class="text-sm text-destructive">{$form.errors.academic_year_id}</p>
                        {/if}
                    </div>

                    <div class="space-y-2">
                        <Label for="label">Label (optional)</Label>
                        <Input id="label" bind:value={$form.label} placeholder="e.g. Walk-in Batch 3" />
                        {#if $form.errors.label}
                            <p class="text-sm text-destructive">{$form.errors.label}</p>
                        {/if}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Select Applicants</CardTitle>
                    <CardDescription>Choose accepted applicants who are not already in an active grading session.</CardDescription>
                </CardHeader>
                <CardContent>
                    {#if applicants.length === 0}
                        <p class="text-sm text-muted-foreground">No eligible applicants found.</p>
                    {:else}
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            {#each applicants as applicant (applicant.id)}
                                <label class="flex items-center gap-3 p-2 rounded-md hover:bg-muted/50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={selectedApplicantIds.includes(applicant.id)}
                                        onchange={() => toggleApplicant(applicant.id)}
                                        class="h-4 w-4 rounded border-input"
                                    />
                                    <div class="flex-1">
                                        <span class="text-sm font-medium">{applicant.name}</span>
                                        <span class="text-xs text-muted-foreground ml-2">{applicant.reference}</span>
                                    </div>
                                </label>
                            {/each}
                        </div>
                    {/if}
                    {#if $form.errors.applicant_ids}
                        <p class="text-sm text-destructive mt-2">{$form.errors.applicant_ids}</p>
                    {/if}
                </CardContent>
            </Card>

            <div class="flex items-center justify-end gap-3">
                <Button type="button" variant="outline" onclick={() => window.history.back()}>Cancel</Button>
                <Button type="submit" disabled={$form.processing || selectedApplicantIds.length === 0}>
                    {$form.processing ? 'Creating...' : 'Create Session'}
                </Button>
            </div>
        </form>
    </div>
</AuthenticatedLayout>
```

- [ ] **Step 3: Add Direct badge to Exam Sessions index**

In `resources/js/Pages/Admin/TestScheduling/Index.svelte`, find where exam sessions are rendered in the table/list. Add a condition that shows a `Direct` badge when the session type is `direct`:

```svelte
{#if session.type === 'direct'}
<span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">Direct</span>
{/if}
```

If the page has a calendar view, filter direct sessions out or visually distinguish them:

```svelte
// Filter for calendar display: only scheduled sessions
const calendarSessions = sessions.data.filter(s => s.type !== 'direct');
```

- [ ] **Step 4: Add Direct badge to Grading Session page**

In `app/Http/Controllers/Grading/GradingSessionController.php`, add `exam_session_type` to the session data in the `show()` method:

```php
'exam_session_type' => $session->examSession?->type ?? 'scheduled',
```

In `resources/js/Pages/Grading/Session.svelte`, find the session header area and add:

```svelte
{#if session.exam_session_type === 'direct'}
<span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">Direct</span>
{/if}
```

- [ ] **Step 5: Add Direct badge to Grading Dashboard**

In `resources/js/Pages/Grading/Dashboard.svelte`, find where grading sessions are listed and add a `Direct` badge for sessions whose `exam_session_type` is `direct`. Ensure the GradingController passes `exam_session_type` in its data.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Admin/Settings/Index.svelte resources/js/Pages/Admin/DirectAssessment/Create.svelte resources/js/Pages/Admin/TestScheduling/Index.svelte resources/js/Pages/Grading/Session.svelte resources/js/Pages/Grading/Dashboard.svelte app/Http/Controllers/Grading/GradingSessionController.php
git commit -m "feat: add Direct Assessment UI — settings toggle, creation form, and Direct badges"
```

---

## Task 8: Comprehensive Feature Tests

**Files:**
- Modify: `tests/Feature/DirectAssessmentTest.php`

- [ ] **Step 1: Add comprehensive test cases**

Add the following tests to `tests/Feature/DirectAssessmentTest.php`:

```php
public function test_direct_session_has_auto_present_attendance(): void
{
    $admin = $this->actingAsAdmin();
    SystemSetting::set('allow_direct_assessment', true);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
    $applicant = Applicant::factory()->create(['application_id' => $application->id]);

    $service = app(DirectAssessmentService::class);
    $gradingSession = $service->create($academicYear, [$applicant->id], $admin);

    $examSession = $gradingSession->examSession;
    $pivot = $examSession->applicants()->where('applicant_id', $applicant->id)->first()->pivot;

    $this->assertEquals('present', $pivot->attendance_status);
    $this->assertNotNull($pivot->attendance_marked_at);
    $this->assertEquals($admin->id, $pivot->attendance_marked_by);
}

public function test_direct_session_has_no_room(): void
{
    $admin = $this->actingAsAdmin();
    SystemSetting::set('allow_direct_assessment', true);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
    $applicant = Applicant::factory()->create(['application_id' => $application->id]);

    $service = app(DirectAssessmentService::class);
    $gradingSession = $service->create($academicYear, [$applicant->id], $admin);

    $this->assertNull($gradingSession->examSession->room_id);
}

public function test_direct_session_sets_date_to_today(): void
{
    $admin = $this->actingAsAdmin();
    SystemSetting::set('allow_direct_assessment', true);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
    $applicant = Applicant::factory()->create(['application_id' => $application->id]);

    $service = app(DirectAssessmentService::class);
    $gradingSession = $service->create($academicYear, [$applicant->id], $admin);

    $this->assertEquals(now()->format('Y-m-d'), $gradingSession->examSession->date->format('Y-m-d'));
}

public function test_direct_session_status_is_in_progress(): void
{
    $admin = $this->actingAsAdmin();
    SystemSetting::set('allow_direct_assessment', true);
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $application = Application::factory()->create(['status' => 'accepted', 'academic_year_id' => $academicYear->id]);
    $applicant = Applicant::factory()->create(['application_id' => $application->id]);

    $service = app(DirectAssessmentService::class);
    $gradingSession = $service->create($academicYear, [$applicant->id], $admin);

    $this->assertEquals('in_progress', $gradingSession->examSession->status);
}

public function test_existing_scheduled_flows_unchanged(): void
{
    // Verify that creating a scheduled exam session still works
    $admin = $this->actingAsAdmin();
    $academicYear = AcademicYear::factory()->create(['is_active' => true]);
    $room = \App\Models\Room::factory()->create(['is_active' => true]);

    $response = $this->post('/admin/exam-scheduling', [
        'academic_year_id' => $academicYear->id,
        'room_id' => $room->id,
        'date' => now()->addDay()->format('Y-m-d'),
        'start_time' => '09:00',
        'end_time' => '12:00',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('exam_sessions', [
        'room_id' => $room->id,
        'type' => 'scheduled',
        'status' => 'draft',
    ]);
}
```

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test --compact`

Expected: All tests pass — no regressions in existing scheduled exam session flows.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/DirectAssessmentTest.php
git commit -m "test: add comprehensive direct assessment feature tests"
```

---

## Self-Review Checklist

### 1. Spec Coverage

| Spec Section | Covered by Task |
|---|---|
| §2 `ExamSession.type` enum extension | Task 1 (migration) + Task 2 (model) |
| §4.1 Migration: `type` + `label` columns | Task 1 |
| §4.1 Migration: `room_id` nullable | Task 1 |
| §4.2 `ExamSession` model constants + `isDirect()` | Task 2 |
| §4.3 `DirectAssessmentService` | Task 3 |
| §4.4 `DirectAssessmentController` + route | Task 4 |
| §4.5 `SystemSetting::allowDirectAssessment()` | Task 6 |
| §5.1 UI entry points | Task 7 |
| §5.2 Modal/Form | Task 7 |
| §5.3 Display/filtering badges | Task 7 |
| §6 Attendance auto-present | Task 3 (service) + Task 8 (test) |
| §7 Validation guards (skip room/time) | Task 5 |
| §8 Impact analysis (no changes) | Verified by Task 8 regression test |
| §9 Migration plan | Task 1 |
| §10 Resolved questions | All addressed inline |
| §11 Acceptance criteria | Covered by Tasks 3, 4, 5, 6, 7, 8 |

### 2. Placeholder Scan

No TBD, TODO, or placeholder patterns found in this plan.

### 3. Type Consistency

- `ExamSession::TYPE_DIRECT` and `ExamSession::TYPE_SCHEDULED` used consistently across all tasks
- `DirectAssessmentService::create()` signature matches call in `DirectAssessmentController::store()`
- `SystemSetting::allowDirectAssessment()` referenced in `StoreDirectAssessmentRequest::authorize()`
- Route names `direct-assessments.store` and `direct-assessments.create` match controller methods
- `GradingSessionService::openForExamSession()` called with same signature as existing code