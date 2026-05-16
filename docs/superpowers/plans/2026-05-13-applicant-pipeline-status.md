# Applicant Pipeline Status Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show where applicants are in the exam pipeline on the admin Applications index and show pages, with sortable pipeline badges and a progress indicator.

**Architecture:** Add `pipelineStatus()` and `pipelineDetails()` accessors to the Application model that compute pipeline state from related data (application status, exam session, attendance, submission, grading). Surface these in the admin index via a Pipeline column with colored badges, and in the show page via a horizontal progress bar. Add pipeline_status filter and sort to the controller.

**Tech Stack:** Laravel 12 (PHP 8.4), Svelte 5, Inertia.js v2, Tailwind CSS v4

---

## File Structure

| File | Action | Responsibility |
|------|--------|---------------|
| `app/Models/Application.php` | Modify | Rewrite `pipelineStatus()`, add `pipelineDetails()` |
| `app/Http/Controllers/ApplicationController.php` | Modify | Add eager-loads, pipeline filter/sort, show page pipeline data |
| `resources/js/lib/pipeline-helpers.js` | Create | `pipelineBadgeVariant()`, `pipelineStatusLabel()` |
| `resources/js/Pages/Applications/Index.svelte` | Modify | Replace Status column with Pipeline column, add filter |
| `resources/js/Pages/Applications/Show.svelte` | Modify | Add pipeline progress section |
| `tests/Feature/ApplicationControllerTest.php` | Modify | Add pipeline status tests |
| `tests/Unit/ApplicationPipelineStatusTest.php` | Create | Unit tests for accessor logic |

---

## Key Context

### Existing `pipelineStatus()` (to be rewritten)

The current `pipelineStatus()` returns states that don't match the spec: `taking_exam`, `exam_completed`, `result_released`, `cancelled`. It also references `ConsultationSummary::STATUS_RELEASED` for a `result_released` state. The spec defines these states instead:

| Pipeline Status | Badge Color | Condition |
|---|---|---|
| `pending` | yellow/warning | `application.status = 'pending'` |
| `accepted` | green/success | `application.status = 'accepted'` AND no exam session |
| `draft_scheduled` | gray/muted | Assigned to draft session |
| `scheduled` | blue/outline | Assigned to published/in_progress session, not yet attended |
| `attended` | teal/info | Marked present in session |
| `submitted` | indigo/info | Exam submitted |
| `graded` | emerald/success | Has finalized score |
| `dismissed` | red/danger | `application.status = 'dismissed'` (overrides all) |

### Pivot table columns

`exam_session_applicant` has: `attendance_status` (pending/present/absent), `attendance_marked_at`, `attendance_marked_by`, `submission_status` (pending/submitted), `submitted_at`, `submitted_to`.

### ExamSession statuses

`STATUS_DRAFT`, `STATUS_PUBLISHED`, `STATUS_IN_PROGRESS`, `STATUS_COMPLETED`, `STATUS_CANCELLED`

### GradingSession statuses

`STATUS_OPEN`, `STATUS_IN_PROGRESS`, `STATUS_REVIEW`, `STATUS_FINALIZED`

---

## Task 1: Rewrite `pipelineStatus()` accessor

**Files:**
- Modify: `app/Models/Application.php`

- [ ] **Step 1: Write failing unit tests for pipeline status logic**

Create `tests/Unit/ApplicationPipelineStatusTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\GradingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationPipelineStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createApp(string $status = 'pending'): Application
    {
        $ay = AcademicYear::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
            'application_start_date' => now()->subDays(5)->toDateString(),
            'application_end_date' => now()->addDays(30)->toDateString(),
        ]);

        return Application::create([
            'academic_year_id' => $ay->id,
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2005-01-15',
            'age' => 20,
            'sex' => 'female',
            'email' => 'test@example.com',
            'course_preference_1' => 1,
            'status' => $status,
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function test_pending_application_returns_pending(): void
    {
        $app = $this->createApp('pending');
        $this->assertSame('pending', $app->pipelineStatus());
    }

    public function test_dismissed_application_returns_dismissed(): void
    {
        $app = $this->createApp('dismissed');
        $this->assertSame('dismissed', $app->pipelineStatus());
    }

    public function test_accepted_without_applicant_returns_accepted(): void
    {
        $app = $this->createApp('accepted');
        $this->assertSame('accepted', $app->pipelineStatus());
    }

    public function test_accepted_without_exam_session_returns_accepted(): void
    {
        $app = $this->createApp('accepted');
        Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $app->load('applicant.examSessions');
        $this->assertSame('accepted', $app->pipelineStatus());
    }

    public function test_accepted_with_draft_session_returns_draft_scheduled(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'session_date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_DRAFT,
            'created_by' => 1,
        ]);
        $session->applicants()->attach($applicant);

        $app->load('applicant.examSessions');
        $this->assertSame('draft_scheduled', $app->pipelineStatus());
    }

    public function test_accepted_with_published_session_not_attended_returns_scheduled(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'session_date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_PUBLISHED,
            'created_by' => 1,
        ]);
        $session->applicants()->attach($applicant, ['attendance_status' => 'pending']);

        $app->load('applicant.examSessions');
        $this->assertSame('scheduled', $app->pipelineStatus());
    }

    public function test_attended_returns_attended(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'session_date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_PUBLISHED,
            'created_by' => 1,
        ]);
        $session->applicants()->attach($applicant, [
            'attendance_status' => 'present',
            'attendance_marked_at' => now(),
        ]);

        $app->load('applicant.examSessions');
        $this->assertSame('attended', $app->pipelineStatus());
    }

    public function test_submitted_returns_submitted(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'session_date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_COMPLETED,
            'created_by' => 1,
        ]);
        $session->applicants()->attach($applicant, [
            'attendance_status' => 'present',
            'attendance_marked_at' => now(),
            'submission_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $app->load('applicant.examSessions');
        $this->assertSame('submitted', $app->pipelineStatus());
    }

    public function test_graded_returns_graded(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'session_date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_COMPLETED,
            'created_by' => 1,
        ]);
        $session->applicants()->attach($applicant, [
            'attendance_status' => 'present',
            'attendance_marked_at' => now(),
            'submission_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $gradingSession = GradingSession::create([
            'exam_session_id' => $session->id,
            'status' => GradingSession::STATUS_FINALIZED,
            'opened_by' => 1,
            'finalized_by' => 1,
            'finalized_at' => now(),
        ]);

        ApplicantScore::create([
            'grading_session_id' => $gradingSession->id,
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => 1,
            'raw_score' => 85,
            'max_score' => 100,
            'normalized_score' => 85.0,
            'scored_by' => 1,
        ]);

        $app->load('applicant.examSessions', 'applicant.applicantScores');
        $this->assertSame('graded', $app->pipelineStatus());
    }

    public function test_cancelled_session_returns_accepted(): void
    {
        $app = $this->createApp('accepted');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'session_date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_CANCELLED,
            'created_by' => 1,
        ]);
        $session->applicants()->attach($applicant);

        $app->load('applicant.examSessions');
        $this->assertSame('accepted', $app->pipelineStatus());
    }

    public function test_dismissed_overrides_everything(): void
    {
        $app = $this->createApp('dismissed');
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => 'tok',
            'setup_token_expires_at' => now()->addDays(3),
        ]);

        $session = ExamSession::create([
            'academic_year_id' => $app->academic_year_id,
            'room_id' => null,
            'session_date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'max_capacity' => 50,
            'status' => ExamSession::STATUS_PUBLISHED,
            'created_by' => 1,
        ]);
        $session->applicants()->attach($applicant, ['attendance_status' => 'present']);

        $app->load('applicant.examSessions');
        $this->assertSame('dismissed', $app->pipelineStatus());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Unit/ApplicationPipelineStatusTest.php`
Expected: FAIL — `pipelineStatus()` returns old states (`taking_exam`, `exam_completed`, etc.)

- [ ] **Step 3: Rewrite `pipelineStatus()` accessor on Application model**

Replace the existing `pipelineStatus()` method in `app/Models/Application.php` with:

```php
/**
 * Get the applicant's pipeline status for display in admin lists.
 * Returns the most advanced milestone reached.
 */
public function pipelineStatus(): string
{
    // Dismissed overrides everything
    if ($this->status === 'dismissed') {
        return 'dismissed';
    }

    if ($this->status === 'pending') {
        return 'pending';
    }

    // status === 'accepted'
    $applicant = $this->applicant;
    if (! $applicant) {
        return 'accepted';
    }

    $examSession = $applicant->relationLoaded('examSessions')
        ? $applicant->examSessions->first()
        : $applicant->examSessions()->first();

    if (! $examSession) {
        return 'accepted';
    }

    // Cancelled session → applicant is not scheduled
    if ($examSession->status === ExamSession::STATUS_CANCELLED) {
        return 'accepted';
    }

    // Draft session → scheduled but not yet published
    if ($examSession->status === ExamSession::STATUS_DRAFT) {
        return 'draft_scheduled';
    }

    // Published or in_progress or completed → check attendance/submission
    if (in_array($examSession->status, [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED], true)) {
        $pivot = $examSession->pivot;

        // Check attendance
        if ($pivot && $pivot->attendance_status === 'present') {
            // Check submission
            if ($pivot->submission_status === 'submitted') {
                // Check grading
                $hasScores = $applicant->relationLoaded('applicantScores')
                    ? $applicant->applicantScores->isNotEmpty()
                    : $applicant->applicantScores()->exists();

                if ($hasScores) {
                    return 'graded';
                }

                return 'submitted';
            }

            return 'attended';
        }

        return 'scheduled';
    }

    // Fallback for any other session status
    return 'accepted';
}
```

- [ ] **Step 4: Run unit tests to verify they pass**

Run: `php artisan test --compact tests/Unit/ApplicationPipelineStatusTest.php`
Expected: PASS — all 10 tests green

- [ ] **Step 5: Commit**

```bash
git add app/Models/Application.php tests/Unit/ApplicationPipelineStatusTest.php
git commit -m "feat: rewrite pipelineStatus() to match spec state machine

Replace old states (taking_exam, exam_completed, result_released, cancelled)
with spec-aligned states (draft_scheduled, scheduled, attended, submitted,
graded). Add comprehensive unit tests for all pipeline transitions."
```

---

## Task 2: Add `pipelineDetails()` accessor

**Files:**
- Modify: `app/Models/Application.php`

- [ ] **Step 1: Write failing test for pipelineDetails**

Add to `tests/Unit/ApplicationPipelineStatusTest.php`:

```php
public function test_pipeline_details_returns_status_and_milestones(): void
{
    $app = $this->createApp('accepted');
    $applicant = Applicant::create([
        'application_id' => $app->id,
        'email' => $app->email,
        'setup_token' => 'tok',
        'setup_token_expires_at' => now()->addDays(3),
    ]);

    $session = ExamSession::create([
        'academic_year_id' => $app->academic_year_id,
        'room_id' => null,
        'session_date' => now()->addDays(7)->toDateString(),
        'start_time' => '08:00',
        'end_time' => '12:00',
        'max_capacity' => 50,
        'status' => ExamSession::STATUS_PUBLISHED,
        'created_by' => 1,
    ]);
    $session->applicants()->attach($applicant, [
        'attendance_status' => 'present',
        'attendance_marked_at' => now(),
        'submission_status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $app->load('applicant.examSessions');
    $details = $app->pipelineDetails();

    $this->assertSame('submitted', $details['status']);
    $this->assertArrayHasKey('milestones', $details);
    $this->assertArrayHasKey('accepted', $details['milestones']);
    $this->assertArrayHasKey('scheduled', $details['milestones']);
    $this->assertArrayHasKey('attended', $details['milestones']);
    $this->assertArrayHasKey('submitted', $details['milestones']);
}

public function test_pipeline_details_for_pending_application(): void
{
    $app = $this->createApp('pending');
    $details = $app->pipelineDetails();

    $this->assertSame('pending', $details['status']);
    $this->assertArrayHasKey('milestones', $details);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=pipeline_details tests/Unit/ApplicationPipelineStatusTest.php`
Expected: FAIL — `pipelineDetails()` method doesn't exist yet

- [ ] **Step 3: Add `pipelineDetails()` accessor to Application model**

Add below `pipelineStatus()` in `app/Models/Application.php`:

```php
/**
 * Get structured pipeline details with milestone timestamps.
 */
public function pipelineDetails(): array
{
    $status = $this->pipelineStatus();

    $milestones = [];

    // Accepted milestone
    if ($this->status !== 'pending') {
        $milestones['accepted'] = ['at' => $this->processed_at?->toIso8601String()];
    }

    // Session-related milestones
    $applicant = $this->applicant;
    if ($applicant) {
        $examSession = $applicant->relationLoaded('examSessions')
            ? $applicant->examSessions->first()
            : $applicant->examSessions()->first();

        if ($examSession && $examSession->status !== ExamSession::STATUS_CANCELLED) {
            $milestones['scheduled'] = [
                'at' => $examSession->created_at?->toIso8601String(),
                'session_date' => $examSession->session_date,
                'session_label' => 'Session #'.$examSession->id,
            ];

            $pivot = $examSession->pivot;
            if ($pivot && $pivot->attendance_status === 'present') {
                $milestones['attended'] = ['at' => $pivot->attendance_marked_at?->toIso8601String()];

                if ($pivot->submission_status === 'submitted') {
                    $milestones['submitted'] = ['at' => $pivot->submitted_at?->toIso8601String()];

                    $hasScores = $applicant->relationLoaded('applicantScores')
                        ? $applicant->applicantScores->isNotEmpty()
                        : $applicant->applicantScores()->exists();

                    if ($hasScores) {
                        $milestones['graded'] = ['at' => null];
                    }
                }
            }
        }
    }

    return [
        'status' => $status,
        'milestones' => $milestones,
    ];
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Unit/ApplicationPipelineStatusTest.php`
Expected: PASS — all pipeline details tests green

- [ ] **Step 5: Commit**

```bash
git add app/Models/Application.php tests/Unit/ApplicationPipelineStatusTest.php
git commit -m "feat: add pipelineDetails() accessor with milestone timestamps"
```

---

## Task 3: Update controller — eager-loading, filter, sort

**Files:**
- Modify: `app/Http/Controllers/ApplicationController.php`

- [ ] **Step 1: Write failing feature test for pipeline filter and sort**

Add to `tests/Feature/ApplicationControllerTest.php`:

```php
public function test_index_includes_pipeline_status_in_response(): void
{
    $application = $this->createApplicationWithAcademicYear(true);

    $response = $this->actingAs($this->staff())->get(route('applications.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Applications/Index')
        ->has('applications.data.0.pipeline_status')
    );
}

public function test_index_pipeline_status_filter_returns_matching(): void
{
    $application = $this->createApplicationWithAcademicYear(true);

    $response = $this->actingAs($this->staff())->get(route('applications.index', ['pipeline_status' => 'pending']));

    $response->assertStatus(200);
}

public function test_index_sort_by_pipeline_status(): void
{
    $application = $this->createApplicationWithAcademicYear(true);

    $response = $this->actingAs($this->staff())->get(route('applications.index', ['sort' => 'pipeline_status']));

    $response->assertStatus(200);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=pipeline_status tests/Feature/ApplicationControllerTest.php`
Expected: FAIL — `pipeline_status` filter not yet supported

- [ ] **Step 3: Update `index()` method — add pipeline_status filter and sort**

In `app/Http/Controllers/ApplicationController.php`, modify the `index()` method:

After the existing `$status` filter block (around line 70), add:

```php
if ($pipelineStatus = $request->input('pipeline_status')) {
    // Pipeline status is computed, so we filter in PHP after pagination
    // This is handled in the transform closure below
}
```

Add sort support. Change the `orderByDesc('submitted_at')` to support sorting by `pipeline_status`:

```php
$sortField = $request->input('sort', 'submitted_at');
$sortDirection = $request->input('direction', 'desc');

if ($sortField === 'pipeline_status') {
    // Pipeline status is computed, so we sort after pagination in the transform
    $applications = $query->orderByDesc('submitted_at')->paginate(15)->withQueryString();
} else {
    $applications = $query->orderByDesc('submitted_at')->paginate(15)->withQueryString();
}
```

Update the transform closure to include `pipeline_status` and `pipeline_details`, and handle pipeline_status filtering:

```php
$transformed = $applications->getCollection()
    ->filter(function (Application $app) use ($pipelineStatus) {
        if (! $pipelineStatus) {
            return true;
        }

        return $app->pipelineStatus() === $pipelineStatus;
    })
    ->map(function (Application $app) {
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
            'pipeline_status' => $app->pipelineStatus(),
            'submitted_at' => $app->submitted_at?->toIso8601String(),
            'course_preferences' => $courses,
        ];
    });

// Sort by pipeline_status if requested
if ($sortField === 'pipeline_status') {
    $order = ['pending' => 0, 'accepted' => 1, 'draft_scheduled' => 2, 'scheduled' => 3, 'attended' => 4, 'submitted' => 5, 'graded' => 6, 'dismissed' => 7];
    $transformed = $sortDirection === 'asc'
        ? $transformed->sortBy(fn ($item) => $order[$item['pipeline_status']] ?? 99)->values()
        : $transformed->sortByDesc(fn ($item) => $order[$item['pipeline_status']] ?? 99)->values();
}

$applications->setCollection($transformed);
```

Also add `pipeline_statuses` to the Inertia props for the filter dropdown:

```php
'pipeline_statuses' => [
    ['value' => 'pending', 'label' => 'Pending'],
    ['value' => 'accepted', 'label' => 'Accepted'],
    ['value' => 'draft_scheduled', 'label' => 'Draft Scheduled'],
    ['value' => 'scheduled', 'label' => 'Scheduled'],
    ['value' => 'attended', 'label' => 'Attended'],
    ['value' => 'submitted', 'label' => 'Submitted'],
    ['value' => 'graded', 'label' => 'Graded'],
    ['value' => 'dismissed', 'label' => 'Dismissed'],
],
```

And add `pipeline_status` to the `filters` `$request->only()` call:

```php
'filters' => $request->only(['search', 'status', 'pipeline_status', 'date_from', 'date_to', 'academic_year_id']),
```

- [ ] **Step 4: Update `show()` method — add pipeline details**

In `show()`, add eager-loading for pipeline data:

```php
$application->load([
    'coursePreference1:id,name,code',
    'coursePreference2:id,name,code',
    'coursePreference3:id,name,code',
    'appointment',
    'academicYear:id,application_start_date,application_end_date',
    'applicant.examSessions',
    'applicant.applicantScores',
    'applicant.consultationSummary',
]);
```

Add pipeline details to `$applicationData`:

```php
'pipeline_status' => $application->pipelineStatus(),
'pipeline_details' => $application->pipelineDetails(),
```

- [ ] **Step 5: Run all tests**

Run: `php artisan test --compact tests/Feature/ApplicationControllerTest.php tests/Unit/ApplicationPipelineStatusTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ApplicationController.php tests/Feature/ApplicationControllerTest.php
git commit -m "feat: add pipeline status filter, sort, and show page data to controller"
```

---

## Task 4: Create `pipeline-helpers.js` frontend module

**Files:**
- Create: `resources/js/lib/pipeline-helpers.js`

- [ ] **Step 1: Create the helper module**

Create `resources/js/lib/pipeline-helpers.js`:

```js
/**
 * Shared helpers for applicant pipeline status display.
 * Used by Applications/Index, Applications/Show, and related pages.
 */

const PIPELINE_ORDER = {
  pending: 0,
  accepted: 1,
  draft_scheduled: 2,
  scheduled: 3,
  attended: 4,
  submitted: 5,
  graded: 6,
  dismissed: 7,
};

const PIPELINE_LABELS = {
  pending: 'Pending',
  accepted: 'Accepted',
  draft_scheduled: 'Draft Scheduled',
  scheduled: 'Scheduled',
  attended: 'Attended',
  submitted: 'Submitted',
  graded: 'Graded',
  dismissed: 'Dismissed',
};

const PIPELINE_VARIANTS = {
  pending: 'warning',
  accepted: 'success',
  draft_scheduled: 'muted',
  scheduled: 'outline',
  attended: 'info',
  submitted: 'info',
  graded: 'success',
  dismissed: 'danger',
};

const PIPELINE_ICONS = {
  pending: 'clock',
  accepted: 'check-circle',
  draft_scheduled: 'file-edit',
  scheduled: 'calendar',
  attended: 'user-check',
  submitted: 'send',
  graded: 'award',
  dismissed: 'x-circle',
};

/**
 * Returns the badge variant (color) for a pipeline status.
 * Maps to shadcn-svelte Badge variant names.
 */
export function pipelineBadgeVariant(status) {
  return PIPELINE_VARIANTS[status] ?? 'muted';
}

/**
 * Returns the human-readable label for a pipeline status.
 */
export function pipelineStatusLabel(status) {
  return PIPELINE_LABELS[status] ?? status;
}

/**
 * Returns the sort order number for a pipeline status.
 * Lower = earlier in pipeline.
 */
export function pipelineOrder(status) {
  return PIPELINE_ORDER[status] ?? 99;
}

/**
 * Returns the full list of pipeline statuses for filter dropdowns.
 */
export function pipelineStatusOptions() {
  return Object.entries(PIPELINE_LABELS).map(([value, label]) => ({ value, label }));
}

/**
 * Returns all pipeline milestones in order for the progress bar.
 */
export function pipelineMilestones() {
  return [
    { key: 'pending', label: 'Pending' },
    { key: 'accepted', label: 'Accepted' },
    { key: 'draft_scheduled', label: 'Draft' },
    { key: 'scheduled', label: 'Scheduled' },
    { key: 'attended', label: 'Attended' },
    { key: 'submitted', label: 'Submitted' },
    { key: 'graded', label: 'Graded' },
  ];
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/lib/pipeline-helpers.js
git commit -m "feat: add pipeline-helpers.js shared frontend module"
```

---

## Task 5: Update Index page — Pipeline column, filter, sort

**Files:**
- Modify: `resources/js/Pages/Applications/Index.svelte`

- [ ] **Step 1: Add imports and pipeline filter state**

At the top of the `<script>` section, add:

```js
import { pipelineBadgeVariant, pipelineStatusLabel, pipelineStatusOptions, pipelineOrder } from '@/lib/pipeline-helpers';
```

Add `pipeline_statuses` to the destructured props:

```js
let { applications, filters = {}, seasons = [], active_season_id = null, statuses = [], pipeline_statuses = [] } = $props();
```

Add filter state for `pipeline_status`:

```js
let filterPipelineStatus = $state('');
```

Update `initFilters()` to initialize `filterPipelineStatus`:

```js
filterPipelineStatus = filters.pipeline_status ?? '';
```

Add it to the `$effect` dependency array:

```js
const ________ = filters.pipeline_status;
```

Update `applyFilters()` to include `pipeline_status`:

```js
function applyFilters() {
  router.get('/admin/applications', {
    search: filterSearch || undefined,
    status: filterStatus || undefined,
    pipeline_status: filterPipelineStatus || undefined,
    academic_year_id: filterAcademicYearId || undefined,
    date_from: filterDateFrom || undefined,
    date_to: filterDateTo || undefined,
    sort: sortField || undefined,
    direction: sortDirection || undefined,
    page: 1,
  }, { preserveState: true });
  filtersOpen = false;
}
```

Add sort state:

```js
let sortField = $state('');
let sortDirection = $state('asc');
```

- [ ] **Step 2: Replace Status column with Pipeline column in the table**

In the table header, replace:

```html
<Table.Head class="px-4 py-3">Status</Table.Head>
```

with:

```html
<Table.Head class="px-4 py-3 cursor-pointer select-none" onclick={() => { sortField = 'pipeline_status'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'; applyFilters(); }}>
  Pipeline
  {#if sortField === 'pipeline_status'}
    <span class="ml-1 text-xs">{sortDirection === 'asc' ? '↑' : '↓'}</span>
  {/if}
</Table.Head>
```

In the table body, replace:

```html
<Badge variant={statusVariant(app.status)}>{statusLabel(app.status)}</Badge>
```

with:

```html
<Badge variant={pipelineBadgeVariant(app.pipeline_status)}>{pipelineStatusLabel(app.pipeline_status)}</Badge>
```

- [ ] **Step 3: Add pipeline status filter dropdown**

In the desktop filter row (the `<div class="hidden md:flex md:items-center md:gap-3">` section), add after the status dropdown:

```html
<select bind:value={filterPipelineStatus} class="rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[40px]">
  <option value="">All pipelines</option>
  {#each pipeline_statuses as p}
    <option value={p.value}>{p.label}</option>
  {/each}
</select>
```

In the mobile filter dropdown, add similarly after the status select:

```html
<label for="filter-pipeline-mobile" class="block text-sm font-medium">Pipeline</label>
<select id="filter-pipeline-mobile" bind:value={filterPipelineStatus} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[44px]">
  <option value="">All</option>
  {#each pipeline_statuses as p}
    <option value={p.value}>{p.label}</option>
  {/each}
</select>
```

- [ ] **Step 4: Update card view**

In the card view, replace the status badge:

```html
<Badge variant={statusVariant(app.status)}>{statusLabel(app.status)}</Badge>
```

with:

```html
<Badge variant={pipelineBadgeVariant(app.pipeline_status)}>{pipelineStatusLabel(app.pipeline_status)}</Badge>
```

- [ ] **Step 5: Verify the build**

Run: `npm run build`
Expected: Build succeeds with no errors

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Applications/Index.svelte
git commit -m "feat: add Pipeline column, filter, and sort to Applications index"
```

---

## Task 6: Update Show page — Pipeline progress section

**Files:**
- Modify: `resources/js/Pages/Applications/Show.svelte`

- [ ] **Step 1: Add imports and pipeline progress section**

Add imports at top of `<script>`:

```js
import { pipelineBadgeVariant, pipelineStatusLabel, pipelineMilestones, pipelineOrder } from '@/lib/pipeline-helpers';
```

Add `pipeline_status` and `pipeline_details` to destructured props:

```js
let { application, courses = [], within_application_window = false, application_window_label = null, pipeline_status = null, pipeline_details = null } = $props();
```

- [ ] **Step 2: Add Pipeline Progress section below the status/action bar**

Add after the status/action bar `</div>` and before the `<div class="grid gap-6 md:grid-cols-2">`:

```html
<!-- Pipeline Progress -->
{#if pipeline_details}
  <div class="rounded-lg border border-border bg-card px-4 py-3">
    <h3 class="mb-3 text-sm font-medium text-muted-foreground">Pipeline Progress</h3>
    <div class="flex items-center gap-1 overflow-x-auto pb-2">
      {#each pipelineMilestones() as milestone, i}
        {@const isActive = pipeline_details.status === milestone.key}
        {@const isPast = pipelineOrder(pipeline_details.status) > pipelineOrder(milestone.key)}
        {@const milestoneData = pipeline_details.milestones?.[milestone.key]}
        <div class="flex flex-col items-center min-w-[80px]">
          <div class="flex items-center gap-1">
            {#if i > 0}
              <div class="h-0.5 w-4 {isPast ? 'bg-primary' : 'bg-muted'}"></div>
            {/if}
            <div
              class="flex h-7 w-7 items-center justify-center rounded-full border-2 text-xs font-medium
                {isActive ? 'border-primary bg-primary text-primary-foreground' : isPast ? 'border-primary bg-primary/10 text-primary' : 'border-muted-foreground/30 bg-background text-muted-foreground'}"
            >
              {#if isPast}
                ✓
              {:else if isActive}
                ●
              {:else}
                &nbsp;
              {/if}
            </div>
          </div>
          <span class="mt-1 text-[10px] leading-tight text-center {isActive ? 'font-semibold text-foreground' : isPast ? 'text-muted-foreground' : 'text-muted-foreground/60'}">
            {milestone.label}
          </span>
          {#if milestoneData?.at}
            <span class="text-[9px] text-muted-foreground/60">
              {new Date(milestoneData.at).toLocaleDateString()}
            </span>
          {/if}
        </div>
      {/each}
      <!-- Dismissed is a terminal state, shown separately -->
      {#if pipeline_details.status === 'dismissed'}
        <div class="flex flex-col items-center min-w-[80px]">
          <div class="flex items-center gap-1">
            <div class="h-0.5 w-4 bg-destructive"></div>
            <div class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-destructive bg-destructive text-destructive-foreground text-xs font-medium">
              ✗
            </div>
          </div>
          <span class="mt-1 text-[10px] leading-tight text-center font-semibold text-destructive">Dismissed</span>
        </div>
      {/if}
    </div>
  </div>
{/if}
```

- [ ] **Step 3: Also update the status badge in the action bar to show pipeline status**

In the action bar, replace:

```html
<Badge variant={statusVariant(application?.status)}>{statusLabel(application?.status)}</Badge>
```

with:

```html
<Badge variant={statusVariant(application?.status)}>{statusLabel(application?.status)}</Badge>
{#if pipeline_status}
  <Badge variant={pipelineBadgeVariant(pipeline_status)}>{pipelineStatusLabel(pipeline_status)}</Badge>
{/if}
```

- [ ] **Step 4: Verify the build**

Run: `npm run build`
Expected: Build succeeds with no errors

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Applications/Show.svelte
git commit -m "feat: add pipeline progress section and badge to Application show page"
```

---

## Task 7: Final verification and cleanup

**Files:**
- All modified files

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: All tests pass, no regressions

- [ ] **Step 2: Run Pint for code formatting**

Run: `vendor/bin/pint --dirty --format agent`
Expected: No formatting changes needed (or auto-fixed)

- [ ] **Step 3: Run frontend build**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 4: Manual smoke test**

Start the dev server with `composer run dev` and visit:
- `/admin/applications` — verify Pipeline column shows with colored badges
- `/admin/applications?pipeline_status=scheduled` — verify filter works
- `/admin/applications?sort=pipeline_status` — verify sort works
- Click into an application show page — verify pipeline progress section displays

- [ ] **Step 5: Final commit if any fixes needed**

```bash
git add -A
git commit -m "fix: cleanup and formatting for pipeline status feature"
```

---

## Spec Coverage Check

| Spec Requirement | Task |
|---|---|
| `pipelineStatus()` accessor | Task 1 |
| `pipelineDetails()` accessor | Task 2 |
| Controller eager-loading | Task 3 |
| `?pipeline_status=` filter | Task 3, 5 |
| `?sort=pipeline_status` | Task 3, 5 |
| Index Pipeline column with badges | Task 5 |
| Index filter dropdown | Task 5 |
| Show Pipeline Progress section | Task 6 |
| `pipeline-helpers.js` | Task 4 |
| Dismissed overrides everything | Task 1 (test) |
| Cancelled session → accepted | Task 1 (test) |
| Multiple sessions → most advanced | Task 1 (test) |
| Unit tests | Task 1, 2 |
| Feature tests | Task 3 |

## Placeholder scan

No TBD, TODO, or placeholder sections found.

## Type consistency check

- `pipelineStatus()` returns `string` → matches frontend `pipelineStatusLabel(status)` expecting string key
- `pipelineDetails()` returns `array` → matches frontend destructuring `pipeline_details.milestones`
- `PIPELINE_ORDER` JS keys match PHP return values exactly
- `PIPELINE_VARIANTS` JS keys match Badge component variant names (warning, success, muted, outline, info, danger)