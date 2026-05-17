# Solidify Pipeline Status — Improved Implementation Plan

> **For Antigravity:** Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.
>
> **Goal:** Add `pipeline_status` and `pipeline_milestones` DB columns to `applications` so filtering/sorting is a native SQL query instead of loading every row into memory (P0 scalability fix). Keep `status` column unchanged — it only holds `pending | accepted | dismissed`. The `pipeline_status` column holds the full computed lifecycle value and is kept in sync via hooks in specific action controllers.

---

## Architecture Decisions

| Decision | Rationale |
|---|---|
| Add `pipeline_status` column, **not** expand `status` | `status` is a different concept (acceptance status). Frontend, policies, and filters all expect them separately. |
| `pipeline_milestones` JSON column (not `status_milestones`) | Matches existing frontend prop name `pipeline_details.milestones` — zero frontend change needed. |
| Hook into specific action controllers, **not** generic Eloquent model observers | Observers on generic `updated` fire too broadly and risk N+1 loops. Hooks in specific action methods are precise. |
| Keep `pipelineStatus()` / `pipelineDetails()` accessor shells during transition | Existing 10-test suite calls these methods. Convert to thin DB wrappers so tests continue passing without a full rewrite. |
| Forward-only transitions; `dismissed` always wins | Prevents accidental regressions from out-of-order events. |

---

## Task 1: Database Migration

**File:** `database/migrations/YYYY_MM_DD_HHMMSS_add_pipeline_status_to_applications_table.php`

```bash
php artisan make:migration add_pipeline_status_to_applications_table --no-interaction
```

```php
public function up(): void
{
    Schema::table('applications', function (Blueprint $table) {
        $table->string('pipeline_status')->nullable()->after('status');
        $table->json('pipeline_milestones')->nullable()->after('pipeline_status');
        $table->index('pipeline_status'); // required for P0 fix
    });
}

public function down(): void
{
    Schema::table('applications', function (Blueprint $table) {
        $table->dropIndex(['pipeline_status']);
        $table->dropColumn(['pipeline_status', 'pipeline_milestones']);
    });
}
```

Run: `php artisan migrate`
Commit: `db: add pipeline_status and pipeline_milestones columns to applications`

---

## Task 2: Application Model — Refactor Accessors

**File:** `app/Models/Application.php`

### 2a. Add to `$fillable`:
```php
'pipeline_status',
'pipeline_milestones',
```

### 2b. Add to `$casts` (follow existing pattern):
```php
'pipeline_milestones' => 'array',
```

### 2c. Add `updatePipelineStatus()` — called only by `ApplicationPipelineService`:
```php
/**
 * Persist a new pipeline status and record its milestone timestamp.
 * Only set by ApplicationPipelineService — do not call directly.
 */
public function updatePipelineStatus(string $newStatus, array $extraMeta = []): void
{
    $milestones = $this->pipeline_milestones ?? [];

    // Only set timestamp on first reach of this milestone
    if (!isset($milestones[$newStatus])) {
        $milestones[$newStatus] = array_merge(['at' => now()->toIso8601String()], $extraMeta);
    }

    $this->update([
        'pipeline_status'    => $newStatus,
        'pipeline_milestones' => $milestones,
    ]);
}
```

### 2d. Replace `pipelineStatus()` body with thin DB wrapper (keep signature):
```php
/**
 * Returns the current pipeline status from DB.
 * @deprecated Read $application->pipeline_status directly.
 */
public function pipelineStatus(): string
{
    return $this->pipeline_status ?? $this->status ?? 'pending';
}
```

### 2e. Replace `pipelineDetails()` body to read from DB (keep signature):
```php
/**
 * Returns structured pipeline details from DB columns.
 * Maintains the same array shape as before for frontend compatibility.
 * @deprecated Read pipeline_status / pipeline_milestones directly.
 */
public function pipelineDetails(): array
{
    $milestones = $this->pipeline_milestones ?? [];
    $status = $this->pipelineStatus();

    // Infer pipeline type from milestone keys (set by hooks)
    $isF2f   = isset($milestones['scheduled']) || isset($milestones['printed']) || isset($milestones['attended']);
    $isDirect = isset($milestones['scored']) && !$isF2f;

    return [
        'status'     => $status,
        'milestones' => $milestones,
        'is_f2f'     => $isF2f,
        'is_direct'  => $isDirect,
    ];
}
```

> **Remove** the old computation logic (all the `$applicant->examSessions` loop, `$pivot`, `$summary` traversal inside both methods). The wrappers above are the complete replacement bodies.

Commit: `feat(model): add updatePipelineStatus(), convert accessors to db-backed wrappers`

---

## Task 3: ApplicationPipelineService

**File:** `app/Services/ApplicationPipelineService.php`

```php
<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Log;

class ApplicationPipelineService
{
    /** Ordered pipeline statuses. Higher index = further along. */
    public const PIPELINE_ORDER = [
        'pending'         => 0,
        'accepted'        => 1,
        'draft_scheduled' => 2,
        'scheduled'       => 3,
        'printed'         => 4,
        'attended'        => 5,
        'submitted'       => 6,
        'scored'          => 7,
        'graded'          => 8,
        'released'        => 9,
        'dismissed'       => 99, // always allowed
    ];

    /**
     * Transition an application forward in the pipeline.
     * No-ops if already at or past the requested status (except dismissed).
     *
     * @param array<string, mixed> $milestoneMeta Extra data stored in the milestone record.
     * @return bool True if the status was actually changed.
     */
    public function transition(Application $app, string $newStatus, array $milestoneMeta = []): bool
    {
        if (!array_key_exists($newStatus, self::PIPELINE_ORDER)) {
            Log::warning('ApplicationPipelineService: unknown status attempted', [
                'app_id' => $app->id, 'new_status' => $newStatus,
            ]);
            return false;
        }

        $currentOrder = self::PIPELINE_ORDER[$app->pipeline_status ?? 'pending'] ?? 0;
        $newOrder     = self::PIPELINE_ORDER[$newStatus];

        // dismissed always allowed; otherwise only advance
        if ($newStatus !== 'dismissed' && $newOrder <= $currentOrder) {
            return false;
        }

        $prev = $app->pipeline_status;
        $app->updatePipelineStatus($newStatus, $milestoneMeta);

        Log::info('Pipeline status transitioned', [
            'app_id' => $app->id,
            'from'   => $prev,
            'to'     => $newStatus,
        ]);

        return true;
    }

    /**
     * Force-set a status, bypassing the forward-only guard.
     * For use by the backfill command only.
     */
    public function forceSet(Application $app, string $status, array $milestoneMeta = []): void
    {
        $app->updatePipelineStatus($status, $milestoneMeta);
    }
}
```

Commit: `feat(service): add ApplicationPipelineService with ordered transition guard`

---

## Task 4: Hook Points (7 Transition Sites)

> Inject `ApplicationPipelineService` via `app()` or constructor DI. Each hook fires **after** the owning state change is persisted to DB.

### 4a. `accepted` — `ApplicationController`

In `accept()`, after `$app->update(['status' => 'accepted', ...])`:
```php
app(ApplicationPipelineService::class)->transition($app->fresh(), 'accepted');
```
Repeat in `bulkAccept()` (inside foreach loop) and `storeAdmin()` (when `$acceptImmediately` is true).

### 4b. `dismissed` — `ApplicationController`

In `dismiss()`, after `$app->update(['status' => 'dismissed', ...])`:
```php
app(ApplicationPipelineService::class)->transition($app->fresh(), 'dismissed');
```
Repeat in `bulkDismiss()` (inside foreach loop).

Also in `reopen()` — when reverting to `pending`, force-reset the pipeline status:
```php
app(ApplicationPipelineService::class)->forceSet($app->fresh(), 'pending');
```

### 4c. `draft_scheduled` / `scheduled` — `ExamSessionController`

After assigning applicants to a session, for each newly assigned applicant's application:
```php
$targetStatus = ($session->status === ExamSession::STATUS_DRAFT) ? 'draft_scheduled' : 'scheduled';
app(ApplicationPipelineService::class)->transition($applicant->application, $targetStatus, [
    'session_id' => $session->id,
]);
```

In `publish()`, after session status is updated, advance all assigned applicants:
```php
foreach ($exam_session->applicants()->with('application')->get() as $applicant) {
    if ($applicant->application) {
        app(ApplicationPipelineService::class)->transition($applicant->application, 'scheduled', [
            'session_id' => $exam_session->id,
        ]);
    }
}
```

### 4d. `printed` — `AdmissionSlipPrintService`

After `markPrinted()` sets `admission_slip_printed_at` on applications:
```php
$service = app(ApplicationPipelineService::class);
foreach ($applications as $application) {
    $service->transition($application, 'printed', [
        'printed_at' => now()->toIso8601String(),
    ]);
}
```

### 4e. `attended` / `submitted` — Attendance/submission controllers

After marking attendance as `present` on the pivot:
```php
app(ApplicationPipelineService::class)->transition($applicant->application, 'attended');
```

After marking submission as `submitted` on the pivot:
```php
app(ApplicationPipelineService::class)->transition($applicant->application, 'submitted');
```

### 4f. `graded` — `GradingSessionController` (after finalization)

After `GradingSession` is finalized (where `finalized_at` is set):
```php
foreach ($gradingSession->applicantScores()->with('applicant.application')->get() as $score) {
    if ($score->applicant?->application) {
        app(ApplicationPipelineService::class)->transition(
            $score->applicant->application,
            'graded',
            ['grading_session_id' => $gradingSession->id]
        );
    }
}
```

### 4g. `released` — `ReleaseController::release()`

After `$summary->update(['status' => ConsultationSummary::STATUS_RELEASED, ...])`:
```php
if ($summary->applicant?->application) {
    app(ApplicationPipelineService::class)->transition(
        $summary->applicant->application,
        'released',
        ['released_at' => now()->toIso8601String()]
    );
}
```

Commit: `feat(hooks): inject pipeline transitions into all 7 state-change sites`

---

## Task 5: Backfill Command

**File:** `app/Console/Commands/SyncPipelineStatusesCommand.php`

```bash
php artisan make:command SyncPipelineStatusesCommand --no-interaction
```

**Signature:** `pipeline:sync-statuses`

> **Critical:** Before removing the old computation logic from the model (Task 2), copy the full `pipelineStatus()` + `pipelineDetails()` computation into a private `compute()` method here. This makes the command self-contained for future re-runs.

```php
public function handle(ApplicationPipelineService $pipeline): int
{
    $this->info('Syncing pipeline_status for all applications...');
    $count = 0;

    Application::with([
        'applicant.examSessions',
        'applicant.applicantScores',
        'applicant.consultationSummary',
    ])->chunkById(100, function ($applications) use ($pipeline, &$count) {
        foreach ($applications as $app) {
            [$status, $milestones] = $this->compute($app);
            $pipeline->forceSet($app, $status, $milestones);
            $count++;
        }
    });

    $this->info("Done. Synced {$count} applications.");
    return Command::SUCCESS;
}

/** Inline copy of the pre-refactor computation logic — self-contained for re-runs. */
private function compute(Application $app): array
{
    // ... copy the ORIGINAL pipelineStatus() logic here to derive $status
    // ... copy the ORIGINAL pipelineDetails()['milestones'] logic here
    // Returns: [$status, $milestones]
}
```

Run: `php artisan pipeline:sync-statuses`
Commit: `feat(cli): add SyncPipelineStatusesCommand for backfill`

---

## Task 5b: Update Existing Tests

**File:** `tests/Unit/ApplicationPipelineStatusTest.php`

**Strategy:** Keep all 10 existing test scenarios. Adjust the assertion pattern:
- Instead of calling `pipelineStatus()` after loading relationships, call `ApplicationPipelineService::transition()` to set the column.
- Assert from `$app->fresh()->pipelineStatus()` (reads DB).

**Pattern:**
```php
// OLD:
$app->load('applicant.examSessions');
$this->assertSame('draft_scheduled', $app->pipelineStatus());

// NEW:
app(ApplicationPipelineService::class)->transition($app->fresh(), 'accepted');
app(ApplicationPipelineService::class)->transition($app->fresh(), 'draft_scheduled', ['session_id' => $session->id]);
$this->assertSame('draft_scheduled', $app->fresh()->pipelineStatus());
```

**Add 5 new service tests:**
- `test_transition_advances_forward()`
- `test_transition_ignores_backward_move()`
- `test_transition_dismissed_always_allowed()`
- `test_transition_returns_false_for_no_op()`
- `test_pipeline_details_reads_from_db()`

Run: `php artisan test --compact tests/Unit/ApplicationPipelineStatusTest.php`
Commit: `test: update pipeline status tests for db-backed approach`

---

## Task 6: Controller Pagination Fix (P0)

**File:** `app/Http/Controllers/ApplicationController.php` — `index()` method

### 6a. Delete the entire in-memory pagination block (P0 bug):
```php
// DELETE this entire if-block:
if ($pipelineStatus || $sortField === 'pipeline_status') {
    $all = $query->orderByDesc('submitted_at')->get(); // loads entire table
    // ...manual in-memory filter/sort/paginate...
}
$applications = $query->orderByDesc('submitted_at')->paginate(15)->withQueryString();
$applications->getCollection()->transform($transformApp);
```

### 6b. Replace with native DB pipeline:
```php
if ($pipelineStatus) {
    $query->where('pipeline_status', $pipelineStatus);
}

$sortableColumns = ['submitted_at', 'pipeline_status', 'last_name', 'first_name'];
$resolvedSort = in_array($sortField, $sortableColumns, true) ? $sortField : 'submitted_at';
$resolvedDir  = $sortDirection === 'asc' ? 'asc' : 'desc';

$applications = $query
    ->orderBy($resolvedSort, $resolvedDir)
    ->paginate(15)
    ->withQueryString();

$applications->getCollection()->transform($transformApp);
```

### 6c. In `$transformApp` closure, replace computed call:
```php
// Replace:
'pipeline_status' => $app->pipelineStatus(),
// With:
'pipeline_status' => $app->pipeline_status ?? 'pending',
```

### 6d. Remove the now-unused import:
```php
// Remove: use Illuminate\Pagination\LengthAwarePaginator;
```

### 6e. Remove the unnecessary eager-loaded relationships now that computation is gone:
```php
// Remove from the with() call (no longer needed for pipelineStatus computation):
// 'applicant.examSessions:id,status,type',
// 'applicant.applicantScores:id,applicant_id',
// 'applicant.consultationSummary:id,applicant_id,status,released_at',
```

> **Note:** Only remove these if `$transformApp` no longer accesses them. Verify there are no other uses in that closure.

Commit: `perf(controller): fix P0 — use db-native pipeline_status filter/sort`

---

## Task 7: Show Controller Cleanup

**File:** `app/Http/Controllers/ApplicationController.php` — `show()` method

```php
// Replace:
$pipelineStatus  = $application->pipelineStatus();
$pipelineDetails = $application->pipelineDetails();

// With:
$pipelineStatus  = $application->pipeline_status ?? 'pending';
$pipelineDetails = $application->pipelineDetails(); // now reads from DB
```

No eager-load changes needed — `pipeline_milestones` is on the `applications` table itself.

Commit: `refactor(controller): read pipeline_status from db in show()`

---

## Task 8: Frontend Verification

**No frontend changes needed.** The API response shape is identical:
- `app.pipeline_status` — same key name, now from DB
- `pipeline_details.milestones` — same shape, now from `pipeline_milestones` JSON column
- `pipeline_details.is_f2f` / `is_direct` — inferred from milestone keys in `pipelineDetails()` (Task 2e)

**Verify manually:**
1. Applications/Index — filter by `pipeline_status` dropdown → results are correct and fast
2. Applications/Index — sort by pipeline status column → works natively
3. Applications/Show — milestone timeline renders from DB milestones

---

## Execution Order

```
1.  Task 1  → php artisan migrate
2.  Task 3  → create ApplicationPipelineService (no deps)
3.  Task 5  → create SyncPipelineStatusesCommand (copies old compute logic before it's removed)
4.  Task 2  → model refactor (remove old computation, add wrappers + updatePipelineStatus)
5.  Run: php artisan pipeline:sync-statuses   (backfill existing rows)
6.  Task 5b → update + extend test suite
7.  Run: php artisan test --compact tests/Unit/ApplicationPipelineStatusTest.php
8.  Task 4  → inject hooks into 7 transition sites
9.  Task 6  → controller P0 fix
10. Task 7  → show controller cleanup
11. Run: php artisan test --compact tests/Feature/ApplicationControllerTest.php
12. Task 8  → manual verify
13. vendor/bin/pint --dirty --format agent
14. git push
```

---

## Quality Gates

- [ ] `php artisan test --compact tests/Unit/ApplicationPipelineStatusTest.php` — all 15 tests pass
- [ ] `php artisan test --compact tests/Feature/ApplicationControllerTest.php` — no regressions
- [ ] Applications/Index: pipeline_status filter → correct, fast results (no full-table load)
- [ ] Applications/Show: milestone timeline renders from DB
- [ ] `vendor/bin/pint --dirty --format agent` — clean
- [ ] `git push` — up to date with origin
