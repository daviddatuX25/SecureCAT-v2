# Consultation Feature — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the clean-build consultation feature — scheduling, session tracking, and result release — removing the rules engine and counselor role entirely.

**Architecture:** Laravel backend with Inertia/Svelte frontend. Backend cleanup removes DecisionRuleService and simplifies ConsultationApplicantController. New ConsultationSummaryService handles only getOrCreate and release. Frontend adds 4 new Svelte pages behind a `consultation_enabled` feature flag gate. Sidebar nav respects the flag.

**Tech Stack:** Laravel 12, Svelte (Inertia), shadcn-svelte components, MySQL/DB (existing tables), PHPStan, Pint

---

## File Map

```
Backend (PHP)
  app/Http/Controllers/Consultation/
    ConsultationRulesController.php        ← DELETE
    ConsultationApplicantController.php   ← MODIFY (remove update(), DecisionRuleService)
    ConsultationController.php            ← KEEP
    ConsultationDayController.php         ← KEEP
    ConsultationScheduleController.php     ← KEEP
    ConsultationLookupController.php      ← KEEP
  app/Http/Requests/
    StoreDecisionRuleRequest.php         ← DELETE
    UpdateDecisionRuleRequest.php         ← DELETE
    UpdateConsultationSummaryRequest.php  ← DELETE
  app/Models/
    DecisionRule.php                      ← DELETE
    ConsultationSummary.php               ← KEEP (add released_by, released_at setter)
    ConsultationSchedule.php              ← KEEP
  app/Services/
    ConsultationSummaryService.php        ← CREATE (2 methods only)
    DecisionRuleService.php               ← DELETE
  routes/
    web.php                               ← MODIFY (remove rules routes, applicants.summary PUT)

Frontend (Svelte)
  resources/js/Layouts/
    AuthenticatedLayout.svelte            ← MODIFY (featureFlag + canSee update)
  resources/js/Pages/Consultation/
    Dashboard.svelte                     ← CREATE
    ApplicantView.svelte                 ← CREATE
    ScheduleDay.svelte                   ← CREATE
    ConsultationDay.svelte               ← CREATE
```

---

## Task 1: Create ConsultationSummaryService

**Files:**
- Create: `app/Services/ConsultationSummaryService.php`
- Test: `tests/Unit/Services/ConsultationSummaryServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Services;

use App\Models\Applicant;
use App\Models\ConsultationSummary;
use App\Models\User;
use App\Services\ConsultationSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConsultationSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConsultationSummaryService();
    }

    public function test_getOrCreateForApplicant_creates_summary_when_none_exists(): void
    {
        $applicant = Applicant::factory()->create();

        $summary = $this->service->getOrCreateForApplicant($applicant->id);

        $this->assertInstanceOf(ConsultationSummary::class, $summary);
        $this->assertEquals($applicant->id, $summary->applicant_id);
        $this->assertEquals(ConsultationSummary::STATUS_PENDING, $summary->status);
    }

    public function test_getOrCreateForApplicant_returns_existing_summary(): void
    {
        $applicant = Applicant::factory()->create();
        $existing = ConsultationSummary::create([
            'applicant_id' => $applicant->id,
            'status' => ConsultationSummary::STATUS_PENDING,
        ]);

        $summary = $this->service->getOrCreateForApplicant($applicant->id);

        $this->assertEquals($existing->id, $summary->id);
    }

    public function test_release_sets_status_and_released_at(): void
    {
        $applicant = Applicant::factory()->create();
        $user = User::factory()->create();
        $summary = ConsultationSummary::create([
            'applicant_id' => $applicant->id,
            'status' => ConsultationSummary::STATUS_PENDING,
        ]);

        $this->service->release($summary, $user);

        $this->assertEquals(ConsultationSummary::STATUS_RELEASED, $summary->status);
        $this->assertNotNull($summary->released_at);
        $this->assertEquals($user->id, $summary->released_by);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ConsultationSummaryServiceTest`
Expected: FAIL — class does not exist

- [ ] **Step 3: Create minimal ConsultationSummaryService**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Applicant;
use App\Models\ConsultationSummary;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConsultationSummaryService
{
    public function getOrCreateForApplicant(int $applicantId): ConsultationSummary
    {
        $summary = ConsultationSummary::firstOrCreate(
            ['applicant_id' => $applicantId],
            ['status' => ConsultationSummary::STATUS_PENDING]
        );

        return $summary;
    }

    public function release(ConsultationSummary $summary, User $user): void
    {
        DB::transaction(function () use ($summary, $user) {
            $summary->update([
                'status' => ConsultationSummary::STATUS_RELEASED,
                'released_at' => now(),
                'released_by' => $user->id,
            ]);
        });
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ConsultationSummaryServiceTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/ConsultationSummaryService.php tests/Unit/Services/ConsultationSummaryServiceTest.php
git commit -m "feat: add ConsultationSummaryService with getOrCreate and release"
```

---

## Task 2: Remove DecisionRuleService and decision-rule files

**Files:**
- Delete: `app/Services/DecisionRuleService.php`
- Delete: `app/Http/Controllers/Consultation/ConsultationRulesController.php`
- Delete: `app/Http/Requests/StoreDecisionRuleRequest.php`
- Delete: `app/Http/Requests/UpdateDecisionRuleRequest.php`
- Delete: `app/Http/Requests/UpdateConsultationSummaryRequest.php`
- Delete: `app/Models/DecisionRule.php`

- [ ] **Step 1: Verify no remaining references to DecisionRule outside migrations**

Run: `grep -r "DecisionRule" app/ database/ --include="*.php" | grep -v "database/migrations"`
Expected: Only migration files reference DecisionRule

- [ ] **Step 2: Delete all decision-rule files**

```bash
rm app/Services/DecisionRuleService.php
rm app/Http/Controllers/Consultation/ConsultationRulesController.php
rm app/Http/Requests/StoreDecisionRuleRequest.php
rm app/Http/Requests/UpdateDecisionRuleRequest.php
rm app/Http/Requests/UpdateConsultationSummaryRequest.php
rm app/Models/DecisionRule.php
```

- [ ] **Step 3: Verify phpstan or composer dump-autoload passes**

Run: `composer dump-autoload && php artisan about | head -20`
Expected: No errors

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "refactor: remove decision rules engine (external ML owns recommendations)"
```

---

## Task 3: Simplify ConsultationApplicantController

**Files:**
- Modify: `app/Http/Controllers/Consultation/ConsultationApplicantController.php`

- [ ] **Step 1: Read current file and write updated version**

Remove:
- `use App\Http\Requests\UpdateConsultationSummaryRequest;`
- `use App\Services\DecisionRuleService;`
- `$ruleService` constructor injection
- `update()` method
- `matched_rules` from `show()` response
- `recommended_course_id`, `recommended_course_name`, `counselor_comments`, `courses` from response
- Remove `$this->ruleService->matchRulesForApplicant(...)` call from `show()`

New constructor: inject only `ConsultationSummaryService`
New `show()` response keys: `applicant`, `scores`, `consultation_summary` (status, released_at only)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\GradingSession;
use App\Services\ConsultationSummaryService;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationApplicantController extends Controller
{
    public function __construct(
        private ConsultationSummaryService $summaryService
    ) {}

    public function show(Applicant $applicant): Response
    {
        $this->ensureApplicantInConsultationScope($applicant);

        $applicant->load('application');
        $gs = GradingSession::query()
            ->where('status', GradingSession::STATUS_FINALIZED)
            ->whereHas('applicantScores', fn ($q) => $q->where('applicant_id', $applicant->id))
            ->first();

        $scores = ApplicantScore::query()
            ->where('grading_session_id', $gs->id)
            ->where('applicant_id', $applicant->id)
            ->with('domain')
            ->get();

        $summary = $this->summaryService->getOrCreateForApplicant($applicant->id);

        return Inertia::render('Consultation/ApplicantView', [
            'applicant' => [
                'id' => $applicant->id,
                'name' => $applicant->application
                    ? trim(implode(' ', array_filter([$applicant->application->first_name, $applicant->application->middle_name, $applicant->application->last_name, $applicant->application->suffix])))
                    : '—',
                'email' => $applicant->application?->email ?? $applicant->email ?? '—',
                'reference' => $applicant->application?->reference_number ?? '—',
            ],
            'scores' => $scores->map(fn ($s) => [
                'domain' => $s->domain?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
            ])->values()->all(),
            'consultation_summary' => [
                'status' => $summary->status,
                'released_at' => $summary->released_at?->toISOString(),
            ],
        ]);
    }

    public function release(Applicant $applicant): RedirectResponse
    {
        $this->ensureApplicantInConsultationScope($applicant);
        $summary = $this->summaryService->getOrCreateForApplicant($applicant->id);
        $this->summaryService->release($summary, request()->user());

        return redirect()->route('consultation.index')->with('success', 'Consultation released.');
    }

    private function ensureApplicantInConsultationScope(Applicant $applicant): void
    {
        $hasFinalizedScores = GradingSession::query()
            ->where('status', GradingSession::STATUS_FINALIZED)
            ->whereHas('applicantScores', fn ($q) => $q->where('applicant_id', $applicant->id))
            ->exists();

        if (! $hasFinalizedScores) {
            abort(404, 'Applicant has no finalized exam scores and is not in consultation scope.');
        }
    }
}
```

- [ ] **Step 2: Run phpstan**

Run: `vendor/bin/phpstan analyse app/Http/Controllers/Consultation/ConsultationApplicantController.php --level=5`
Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Consultation/ConsultationApplicantController.php
git commit -m "refactor: simplify ConsultationApplicantController — remove update(), rules engine"
```

---

## Task 4: Update routes — remove rules routes and applicants.summary PUT

**Files:**
- Modify: `routes/web.php` (lines 156–168)

- [ ] **Step 1: Read the route block and write the replacement**

Replace:
```php
Route::middleware(['role:super_admin,test_administrator', 'consultation.enabled'])->prefix('consultation')->name('consultation.')->group(function () {
    Route::get('/', [ConsultationController::class, 'index'])->name('index');
    Route::get('/schedule', [ConsultationScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule', [ConsultationScheduleController::class, 'store'])->name('schedule.store');
    Route::get('/day', [ConsultationDayController::class, 'index'])->name('day.index');
    Route::get('/rules', [ConsultationRulesController::class, 'index'])->name('rules.index');
    Route::post('/rules', [ConsultationRulesController::class, 'store'])->name('rules.store');
    Route::put('/rules/{decision_rule}', [ConsultationRulesController::class, 'update'])->name('rules.update');
    Route::delete('/rules/{decision_rule}', [ConsultationRulesController::class, 'destroy'])->name('rules.destroy');
    Route::get('/applicants/{applicant}', [ConsultationApplicantController::class, 'show'])->name('applicants.show');
    Route::put('/applicants/{applicant}/summary', [ConsultationApplicantController::class, 'update'])->name('applicants.summary');
    Route::post('/applicants/{applicant}/release', [ConsultationApplicantController::class, 'release'])->name('applicants.release');
});
```

With:
```php
Route::middleware(['role:super_admin,test_administrator', 'consultation.enabled'])->prefix('consultation')->name('consultation.')->group(function () {
    Route::get('/', [ConsultationController::class, 'index'])->name('index');
    Route::get('/schedule', [ConsultationScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule', [ConsultationScheduleController::class, 'store'])->name('schedule.store');
    Route::get('/day', [ConsultationDayController::class, 'index'])->name('day.index');
    Route::get('/applicants/{applicant}', [ConsultationApplicantController::class, 'show'])->name('applicants.show');
    Route::post('/applicants/{applicant}/release', [ConsultationApplicantController::class, 'release'])->name('applicants.release');
});
```

- [ ] **Step 2: Verify route list**

Run: `php artisan route:list --path=consultation`
Expected: Only 5 routes: index, schedule.index, schedule.store, day.index, applicants.show, applicants.release

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "refactor: remove consultation.rules and applicants.summary routes"
```

---

## Task 5: Update AuthenticatedLayout — featureFlag in sidebar nav

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.svelte`

- [ ] **Step 1: Add featureFlag to the consultation nav item and update canSee**

In the Guidance section, change:
```js
{ href: '/consultation', label: 'Consultation', icon: MessageSquare, roles: ['super_admin', 'test_administrator'] },
```

To:
```js
{ href: '/consultation', label: 'Consultation', icon: MessageSquare, roles: ['super_admin', 'test_administrator'], featureFlag: 'consultation_enabled' },
```

Update `canSee` function:
```js
function canSee(requiredRoles, item) {
  if (requiredRoles.includes('*')) return true;
  if (!requiredRoles.some((r) => hasRole(r))) return false;
  if (item.featureFlag && !$page.props[item.featureFlag]) return false;
  return true;
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "feat: respect consultation_enabled feature flag in sidebar"
```

---

## Task 6: Create Consultation/Dashboard.svelte

**Files:**
- Create: `resources/js/Pages/Consultation/Dashboard.svelte`
- Tests: `tests/Feature/Consultation/DashboardTest.php`

- [ ] **Step 1: Write the Dashboard page**

```svelte
<script>
  import { Link } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Badge } from '@/Components/ui/badge';
  import { Button } from '@/Components/ui/button';

  let { applicants_pending = [], applicants_released = [], stats = {} } = $props();

  const tabs = ['pending', 'released'];
  let activeTab = $state('pending');

  const displayApplicants = $derived(activeTab === 'pending' ? applicants_pending : applicants_released);
</script>

<div class="space-y-6">
  <!-- Stats bar -->
  <div class="flex gap-4 text-sm text-muted-foreground">
    <span>{stats.pending ?? 0} pending</span>
    <span>·</span>
    <span>{stats.released ?? 0} released</span>
    <span>·</span>
    <span>{stats.total_with_scores ?? 0} total with scores</span>
  </div>

  <!-- Tabs -->
  <div class="flex gap-1 border-b">
    {#each tabs as tab}
      <button
        type="button"
        class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px {activeTab === tab
          ? 'border-primary text-primary'
          : 'border-transparent text-muted-foreground hover:text-foreground'}"
        onclick={() => (activeTab = tab)}
      >
        {tab === 'pending' ? 'Pending' : 'Released'}
      </button>
    {/each}
  </div>

  <!-- Applicant list -->
  <Card>
    <CardContent class="p-0">
      {#if displayApplicants.length === 0}
        <div class="p-8 text-center text-muted-foreground text-sm">
          No {activeTab} applicants.
        </div>
      {:else}
        <div class="divide-y">
          {#each displayApplicants as applicant}
            <div class="flex items-center justify-between px-6 py-4">
              <div class="min-w-0">
                <p class="font-medium truncate">{applicant.name}</p>
                <p class="text-sm text-muted-foreground">{applicant.reference}</p>
              </div>
              <div class="flex items-center gap-4">
                {#if activeTab === 'pending'}
                  <span class="text-sm text-muted-foreground">
                    Finalized {applicant.finalized_date ?? '—'}
                  </span>
                  <Button variant="outline" size="sm" as="a" href={`/consultation/applicants/${applicant.id}`}>
                    View
                  </Button>
                {:else}
                  <span class="text-sm text-muted-foreground">
                    Released {applicant.released_date ?? '—'}
                  </span>
                {/if}
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </CardContent>
  </Card>
</div>
```

- [ ] **Step 2: Write the feature test**

```php
<?php

namespace Tests\Feature\Consultation;

use App\Models\Applicant;
use App\Models\GradingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_pending_and_released_applicants(): void
    {
        $user = User::factory()->create()->assignRole('test_administrator');
        $applicant = Applicant::factory()->create();
        $gs = GradingSession::factory()->finalized()->create();
        $gs->applicantScores()->create(['applicant_id' => $applicant->id, 'domain_id' => null, 'raw_score' => 70, 'max_score' => 100]);

        $this->actingAs($user, 'web')
            ->get('/consultation')
            ->assertInertia(fn ($page) => $page
                ->component('Consultation/Dashboard')
                ->where('stats.pending', 1)
                ->where('stats.released', 0)
            );
    }
}
```

- [ ] **Step 3: Run test**

Run: `php artisan test --filter DashboardTest`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Consultation/Dashboard.svelte tests/Feature/Consultation/DashboardTest.php
git commit -m "feat: add Consultation/Dashboard page with pending/released tabs"
```

---

## Task 7: Create Consultation/ApplicantView.svelte

**Files:**
- Create: `resources/js/Pages/Consultation/ApplicantView.svelte`

- [ ] **Step 1: Write the ApplicantView page**

```svelte
<script>
  import { Link } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Badge } from '@/Components/ui/badge';
  import { Button } from '@/Components/ui/button';

  let { applicant = {}, scores = [], consultation_summary = {} } = $props();

  const isReleased = $derived(consultation_summary.status === 'released');
</script>

<div class="space-y-6">
  <!-- Back link -->
  <Link href="/consultation" class="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1">
    ← Back to Dashboard
  </Link>

  <!-- Header -->
  <div class="flex items-start justify-between">
    <div>
      <h2 class="text-xl font-semibold">{applicant.name}</h2>
      <p class="text-muted-foreground text-sm">{applicant.reference}</p>
    </div>
    <Badge variant={isReleased ? 'success' : 'warning'}>
      {isReleased ? 'Released' : 'Pending'}
    </Badge>
  </div>

  <!-- Score breakdown -->
  <Card>
    <CardHeader>
      <CardTitle>Score Breakdown</CardTitle>
    </CardHeader>
    <CardContent class="p-0">
      <div class="divide-y">
        {#each scores as score}
          <div class="flex items-center justify-between px-6 py-3">
            <span class="font-medium">{score.domain}</span>
            <div class="flex items-center gap-3">
              <span class="text-sm text-muted-foreground">{score.raw}/{score.max}</span>
              <div class="w-24 h-2 bg-muted rounded-full overflow-hidden">
                <div class="h-full bg-primary" style="width: {score.pct}%"></div>
              </div>
              <span class="text-sm font-medium w-10 text-right">{score.pct}%</span>
            </div>
          </div>
        {/each}
      </div>
    </CardContent>
  </Card>

  <!-- Release action -->
  <div class="flex justify-end">
    <form method="POST" action={`/consultation/applicants/${applicant.id}/release`}>
      <Button type="submit" disabled={isReleased}>
        {isReleased ? 'Already Released' : 'Release Results'}
      </Button>
    </form>
  </div>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Consultation/ApplicantView.svelte
git commit -m "feat: add Consultation/ApplicantView — score breakdown and release action"
```

---

## Task 8: Create Consultation/ScheduleDay.svelte

**Files:**
- Create: `resources/js/Pages/Consultation/ScheduleDay.svelte`

- [ ] **Step 1: Write the ScheduleDay page**

```svelte
<script>
  import { router } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';

  let { batches = [], flash = {} } = $props();

  let expandedBatch = $state(null);
  let selectedDate = $state('');
</script>

<div class="space-y-6">
  {#if flash?.success}
    <div class="p-3 rounded-lg bg-green-50 text-green-800 text-sm">{flash.success}</div>
  {/if}
  {#if flash?.error}
    <div class="p-3 rounded-lg bg-red-50 text-red-800 text-sm">{flash.error}</div>
  {/if}

  <Card>
    <CardHeader>
      <CardTitle>Schedule Applicants by Batch</CardTitle>
    </CardHeader>
    <CardContent class="p-0 divide-y">
      {#each batches as batch}
        <div>
          <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-muted/50" onclick={() => expandedBatch = expandedBatch === batch.id ? null : batch.id}>
            <div>
              <p class="font-medium">{batch.name}</p>
              <p class="text-sm text-muted-foreground">
                Exam date: {batch.exam_date} · Printed: {batch.printed_count}/{batch.total}
              </p>
            </div>
            <Badge variant={batch.printed_count === batch.total ? 'success' : 'secondary'}>
              {batch.printed_count}/{batch.total}
            </Badge>
          </div>

          {#if expandedBatch === batch.id}
            <div class="px-6 py-4 bg-muted/30 border-t">
              <p class="text-sm font-medium mb-3">Scheduled Applicants</p>
              <div class="space-y-2 mb-4">
                {#each (batch.applicants ?? []) as applicant}
                  <div class="flex items-center justify-between text-sm">
                    <span>{applicant.name}</span>
                    <span class="text-muted-foreground">{applicant.reference}</span>
                  </div>
                {/each}
                {#if !batch.applicants?.length}
                  <p class="text-sm text-muted-foreground">No applicants scheduled.</p>
                {/if}
              </div>
              <div class="flex items-center gap-3">
                <input
                  type="date"
                  bind:value={selectedDate}
                  class="text-sm border rounded px-3 py-2"
                />
                <Button
                  size="sm"
                  onclick={() => {
                    if (!selectedDate) return;
                    router.post('/consultation/schedule', {
                      grading_session_id: batch.id,
                      scheduled_date: selectedDate,
                    });
                  }}
                >
                  Schedule
                </Button>
              </div>
            </div>
          {/if}
        </div>
      {/each}
    </CardContent>
  </Card>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Consultation/ScheduleDay.svelte
git commit -m "feat: add Consultation/ScheduleDay — batch scheduling interface"
```

---

## Task 9: Create Consultation/ConsultationDay.svelte

**Files:**
- Create: `resources/js/Pages/Consultation/ConsultationDay.svelte`

- [ ] **Step 1: Write the ConsultationDay page**

```svelte
<script>
  import { Link, router } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';

  let { applicants = [], scheduledApplicantIds = [] } = $props();

  let search = $state('');
  let debouncedSearch = $state('');

  $effect(() => {
    const timer = setTimeout(() => {
      debouncedSearch = search;
      if (search.length >= 2) {
        router.get('/consultation/day', { search }, { preserveState: true });
      } else if (!search) {
        router.get('/consultation/day', {}, { preserveState: true });
      }
    }, 300);
    return () => clearTimeout(timer);
  });

  const filteredApplicants = $derived(
    debouncedSearch.length >= 2
      ? applicants
      : applicants
  );
</script>

<div class="space-y-6">
  <Card>
    <CardHeader>
      <CardTitle>Today's Consultation</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <!-- Search -->
      <Input
        type="search"
        placeholder="Search by name or reference (min 2 chars)..."
        bind:value={search}
        class="max-w-sm"
      />

      <!-- Applicant list -->
      {#if filteredApplicants.length === 0}
        <div class="text-center py-8 text-muted-foreground text-sm">
          No scheduled applicants found.
        </div>
      {:else}
        <div class="divide-y">
          {#each filteredApplicants as applicant}
            <div class="flex items-center justify-between py-4">
              <div class="min-w-0">
                <p class="font-medium truncate">{applicant.name}</p>
                <p class="text-sm text-muted-foreground">{applicant.reference}</p>
              </div>
              <div class="flex items-center gap-3">
                {#if applicant.score_pct !== undefined}
                  <span class="text-sm font-medium">{applicant.score_pct}%</span>
                {/if}
                <Link
                  href={`/consultation/applicants/${applicant.id}`}
                  class="text-sm text-primary hover:underline"
                >
                  View →
                </Link>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </CardContent>
  </Card>
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Consultation/ConsultationDay.svelte
git commit -m "feat: add Consultation/ConsultationDay — daily consultation view with search"
```

---

## Task 10: Integration verification — full flow test

**Files:** (existing — no new files)

- [ ] **Step 1: Run phpstan on full app**

Run: `vendor/bin/phpstan analyse app/ --level=5`
Expected: No errors related to consultation

- [ ] **Step 2: Verify all consultation routes are correct**

Run: `php artisan route:list --path=consultation`
Expected: 6 routes (index, schedule.index, schedule.store, day.index, applicants.show, applicants.release)

- [ ] **Step 3: Verify feature flag gate still works**

Check `HandleInertiaRequests` middleware passes `consultation_enabled` to all pages

- [ ] **Step 4: Run all tests**

Run: `php artisan test`
Expected: All tests pass

- [ ] **Step 5: Commit any remaining changes**

```bash
git add -A
git commit -m "chore: verify consultation feature integration"
```

---

## Spec Coverage Check

| Spec Section | Task(s) |
|---|---|
| Backend cleanup — remove DecisionRule + rules controller + requests | Task 2, Task 3 |
| Simplify ConsultationApplicantController | Task 3 |
| Create ConsultationSummaryService (getOrCreate + release) | Task 1 |
| Routes cleanup (remove rules + applicants.summary PUT) | Task 4 |
| Sidebar fix (featureFlag + canSee) | Task 5 |
| Dashboard.svelte | Task 6 |
| ApplicantView.svelte | Task 7 |
| ScheduleDay.svelte | Task 8 |
| ConsultationDay.svelte | Task 9 |
| DB tables left in place (no migration needed) | Covered — no task needed |
| consultation_enabled gate unchanged | Task 5 verified in Task 10 |

**All spec items covered.** No placeholder content found.

---

## Type Consistency Check

| Item | Definition |
|---|---|
| `ConsultationSummaryService::getOrCreateForApplicant(int)` | Task 1 |
| `ConsultationSummaryService::release(ConsultationSummary, User)` | Task 1 |
| `consultation_summary.status` | Task 1, Task 3, Task 7 |
| `consultation_summary.released_at` | Task 3, Task 7 |
| Feature flag key `'consultation_enabled'` | Task 5 |

All method signatures and prop names are consistent across tasks.
