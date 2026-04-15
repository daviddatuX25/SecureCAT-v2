# Phase 2 — UI/UX Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement 8 UI/UX fixes covering compact tables, sticky nav, a reusable InfoPopover, apply/login/applications page polish, and a full staff dashboard revamp with role-scoped KPIs.

**Architecture:** Frontend-only changes for items 2.1–2.3 and 2.6–2.8. Item 2.7 (InfoPopover) is a new self-contained Svelte component. Items 2.4–2.5 require both a `DashboardService` refactor (backend) and a `Dashboard.svelte` rewrite (frontend) to replace generic `StatCard` with role-gated `KpiCard` sections.

**Tech Stack:** Svelte 5 (runes), Inertia.js v2, Laravel 12, Tailwind CSS v4, PHPUnit

---

## Pre-flight: What's Already Done

**2.8 Sticky Nav** is complete — `AuthenticatedLayout.svelte:228` already has `sticky top-0 z-20`. Task 2 below is a quick verify-and-commit.

---

## File Map

| File | Change |
|------|--------|
| `resources/js/Components/ui/table/table-cell.svelte` | Padding → `py-2 px-3.5` |
| `resources/js/Components/ui/table/table-head.svelte` | Padding → `py-2 px-3.5`, remove `h-10` |
| `resources/js/Layouts/AuthenticatedLayout.svelte` | Verify sticky; remove Admission Slip Templates nav link |
| `resources/js/Components/InfoPopover.svelte` | **New** — reusable info icon + popover |
| `resources/js/Pages/Applications/Apply.svelte` | `justify-end` on action buttons; `text-center` on title |
| `resources/js/Pages/Applications/Success.svelte` | "test scheduling" → "exam scheduling" |
| `resources/js/Pages/Auth/Login.svelte` | localStorage tab persistence via `onMount` |
| `resources/js/Pages/Applications/Index.svelte` | Import button guard; remove `incomplete_documents` from UI |
| `resources/js/Pages/Dashboard.svelte` | Full rewrite: role-scoped KpiCard sections + institution info |
| `app/Http/Controllers/DashboardController.php` | Pass named props: `applicationStats`, `sessionStats`, `gradingStats` |
| `app/Services/DashboardService.php` | New role-scoped methods returning separate stat groups |
| `tests/Feature/DashboardControllerTest.php` | **New** — verify props returned per role |

---

## Task 1: Compact Table UI (2.6)

**Files:**
- Modify: `resources/js/Components/ui/table/table-cell.svelte`
- Modify: `resources/js/Components/ui/table/table-head.svelte`

- [ ] **Step 1: Edit table-cell padding**

In `resources/js/Components/ui/table/table-cell.svelte`, change the `class` string from:
```
"bg-clip-padding p-2 align-middle whitespace-nowrap [&:has([role=checkbox])]:pe-0"
```
to:
```
"bg-clip-padding py-2 px-3.5 align-middle whitespace-nowrap [&:has([role=checkbox])]:pe-0"
```

- [ ] **Step 2: Edit table-head padding**

In `resources/js/Components/ui/table/table-head.svelte`, change the `class` string from:
```
"text-foreground h-10 bg-clip-padding px-2 text-start align-middle font-medium whitespace-nowrap [&:has([role=checkbox])]:pe-0"
```
to:
```
"text-foreground bg-clip-padding py-2 px-3.5 text-start align-middle font-medium whitespace-nowrap [&:has([role=checkbox])]:pe-0"
```
(Note: `h-10` removed in favour of padding.)

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/ui/table/table-cell.svelte resources/js/Components/ui/table/table-head.svelte
git commit -m "style: compact table cell and header padding (2.6)"
```

---

## Task 2: Verify Sticky Nav (2.8)

**Files:**
- Verify: `resources/js/Layouts/AuthenticatedLayout.svelte:228`

- [ ] **Step 1: Confirm header classes**

Open `resources/js/Layouts/AuthenticatedLayout.svelte` at line 228. Confirm the `<header>` already has `sticky top-0 z-20` and `glass-panel` (which is opaque). No code change needed.

- [ ] **Step 2: Remove Admission Slip Templates nav link**

Per spec 2.5, remove the nav item for Admission Slip Templates. In the `navSections` derived array (around line 69–77), remove this object from the `Administration` section items:
```js
{ href: '/admin/admission-slip-templates', label: 'Admission Slip Templates', icon: FileStack, roles: ['super_admin'] },
```
Also remove `FileStack` from the import on line 4 if it is no longer used elsewhere.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "feat: remove admission slip templates nav link (2.5/2.8)"
```

---

## Task 3: InfoPopover Component (2.7)

**Files:**
- Create: `resources/js/Components/InfoPopover.svelte`

No shadcn Popover primitives are installed, so this is a self-contained Svelte component using state + click-outside handling.

- [ ] **Step 1: Create the component**

Create `resources/js/Components/InfoPopover.svelte` with the following content:

```svelte
<script>
  import { Info } from 'lucide-svelte';

  let { content, label = null } = $props();

  let open = $state(false);
  let buttonEl = $state(null);
  let panelEl = $state(null);

  function toggle() {
    open = !open;
  }

  function handleKeydown(e) {
    if (e.key === 'Escape') open = false;
  }

  function handleOutsideClick(e) {
    if (!open) return;
    if (buttonEl && buttonEl.contains(e.target)) return;
    if (panelEl && panelEl.contains(e.target)) return;
    open = false;
  }
</script>

<svelte:window onkeydown={handleKeydown} onclick={handleOutsideClick} />

<span class="relative inline-flex items-center gap-1">
  {#if label}
    <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground">{label}</span>
  {/if}
  <button
    bind:this={buttonEl}
    type="button"
    class="inline-flex h-5 w-5 items-center justify-center rounded-full text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
    onclick={toggle}
    aria-expanded={open}
    aria-label="More info"
  >
    <Info class="h-4 w-4" />
  </button>

  {#if open}
    <div
      bind:this={panelEl}
      role="tooltip"
      class="absolute bottom-full left-1/2 z-50 mb-2 w-64 -translate-x-1/2 rounded-xl border border-border bg-card p-3 text-sm text-foreground shadow-lg"
    >
      {content}
      <div class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-border" aria-hidden="true"></div>
    </div>
  {/if}
</span>
```

- [ ] **Step 2: Verify the component renders — quick visual check**

The component has no test (pure UI, no business logic). Move on.

- [ ] **Step 3: Wire InfoPopover into AI Exam Scheduler modal**

Open `resources/js/Pages/Admin/TestScheduling/Show.svelte`. Find the inline description paragraph about the AI scheduler (search for "Chat with the assistant" or "describe dates"). Replace the description `<p>` with:

```svelte
<InfoPopover
  content="Chat with the assistant to refine your schedule. After you get a reply, click Generate Schedule to create a preview."
  label="Beta"
/>
```

Add the import at the top of the `<script>` block:
```js
import InfoPopover from '@/Components/InfoPopover.svelte';
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/InfoPopover.svelte resources/js/Pages/Admin/TestScheduling/Show.svelte
git commit -m "feat: add InfoPopover component, wire into AI Exam Scheduler (2.7)"
```

---

## Task 4: Apply + Success Page Fixes (2.1)

**Files:**
- Modify: `resources/js/Pages/Applications/Apply.svelte:87` (CardTitle text-center)
- Modify: `resources/js/Pages/Applications/Apply.svelte:176` (actions justify-end)
- Modify: `resources/js/Pages/Applications/Success.svelte:29` ("test scheduling" → "exam scheduling")

- [ ] **Step 1: Center the page title**

In `Apply.svelte` at line 87, the `<CardTitle>` line reads:
```svelte
<CardTitle>Submit an application</CardTitle>
```
Change to:
```svelte
<CardTitle class="text-center">Submit an application</CardTitle>
```

- [ ] **Step 2: Right-align action buttons**

In `Apply.svelte` at line 176, change:
```svelte
<div class="flex gap-4 pt-4">
```
to:
```svelte
<div class="flex justify-end gap-4 pt-4">
```

- [ ] **Step 3: Fix "test scheduling" wording in Success page**

In `Success.svelte` at line 29, change:
```
To proceed with your application and test scheduling, you must personally submit
```
to:
```
To proceed with your application and exam scheduling, you must personally submit
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Applications/Apply.svelte resources/js/Pages/Applications/Success.svelte
git commit -m "fix: apply page title centering, right-align buttons, exam scheduling wording (2.1)"
```

---

## Task 5: Login Tab Persistence (2.2)

**Files:**
- Modify: `resources/js/Pages/Auth/Login.svelte`

- [ ] **Step 1: Add onMount and tab persistence**

In `Login.svelte`, add `onMount` to the imports on line 2:
```js
import { onMount } from 'svelte';
```

Replace line 8 (`let activeTab = $state('applicant');`) with:
```js
let activeTab = $state('applicant');

onMount(() => {
  const saved = localStorage.getItem('loginTab');
  if (saved === 'applicant' || saved === 'staff') {
    activeTab = saved;
  }
});
```

- [ ] **Step 2: Persist tab on change**

The tab switch happens in two `onclick` handlers. Change:
```svelte
onclick={() => (activeTab = 'applicant')}
```
to:
```svelte
onclick={() => { activeTab = 'applicant'; localStorage.setItem('loginTab', 'applicant'); }}
```

And:
```svelte
onclick={() => (activeTab = 'staff')}
```
to:
```svelte
onclick={() => { activeTab = 'staff'; localStorage.setItem('loginTab', 'staff'); }}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Auth/Login.svelte
git commit -m "feat: persist login tab selection via localStorage (2.2)"
```

---

## Task 6: Applications Role Guard + Status Cleanup (2.3)

**Files:**
- Modify: `resources/js/Pages/Applications/Index.svelte`

- [ ] **Step 1: Get roles in Index.svelte**

At the top of the `<script>` block in `Index.svelte`, add role detection (after the existing imports and `let { ... } = $props();`):

```js
const page = usePage();  // already imported
// Add these two lines below the existing page/usePage setup:
const authUser = $derived($page.props.auth?.user ?? null);
const roles = $derived(authUser?.roles?.map((r) => r.name) ?? []);
function hasRole(r) { return roles.includes(r); }
```

Note: `usePage` is already imported at line 3 of Index.svelte.

- [ ] **Step 2: Add Import button (super_admin only)**

In `Index.svelte`, inside the header actions row (around line 88, the `<div class="flex flex-wrap items-center gap-3">` containing the ToggleGroup), add the Import button **before** the ToggleGroup:

```svelte
{#if hasRole('super_admin')}
  <Link href="/admin/applications/import">
    <Button variant="outline" class="min-h-[44px] gap-2">
      <UploadCloud class="h-4 w-4" />
      Import
    </Button>
  </Link>
{/if}
```

Add `UploadCloud` to the lucide import at the top of the script:
```js
import { Eye, Filter, ChevronDown, Table2, LayoutGrid, MonitorSmartphone, CheckCircle, XCircle, UploadCloud } from 'lucide-svelte';
```

- [ ] **Step 3: Remove incomplete_documents from statusVariant and statusLabel**

In `statusVariant()` (line 44–50), remove the `incomplete_documents` line:
```js
// Remove this line:
if (status === 'incomplete_documents') return 'warning';
```

The `statusLabel()` function uses the `statuses` prop from the server — no change needed in the frontend. The backend simply stops including it in the `statuses` array (see note below).

**Note:** The `incomplete_documents` status value stays in the database. To stop it appearing in filter dropdowns, the `statuses` prop must not include it. Find where `statuses` is built in the backend controller (`ApplicationController`) and remove `incomplete_documents` from the list. Search for the prop in:
```
app/Http/Controllers/ApplicationController.php
```
and remove `incomplete_documents` from whatever `statuses` array is passed.

- [ ] **Step 4: Remove incomplete_documents from inline status checks**

In `Index.svelte`, there are two locations that check `status === 'incomplete_documents'` to show Accept/Dismiss buttons (line 220 and 296). Change both occurrences from:
```svelte
{#if app.status === 'pending' || app.status === 'incomplete_documents'}
```
to:
```svelte
{#if app.status === 'pending'}
```

- [ ] **Step 5: Remove Print Slips link from Applications header if present**

Search the file for "print-slips" or "Print Slips". If present, remove that Link/Button. (In the current file there is none — skip if not found.)

- [ ] **Step 6: Run PHPUnit to verify no regressions**

```bash
php artisan test --compact --filter=Application
```
Expected: all existing application tests pass.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Applications/Index.svelte
git commit -m "feat: role-guard Import button, remove incomplete_documents status from UI (2.3)"
```

---

## Task 7: DashboardService Refactor (Backend for 2.4)

**Files:**
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Create: `tests/Feature/DashboardControllerTest.php`

### What changes
Current: one flat `getStatsForUser()` method returns a mixed array.
New: three focused methods returning typed arrays. Controller passes them as named Inertia props.

- [ ] **Step 1: Write the failing test first**

Create `tests/Feature/DashboardControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Season;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    public function test_admin_sees_application_stats(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('applicationStats')
                ->has('sessionStats')
                ->has('gradingStats')
            );
    }

    public function test_super_admin_sees_all_stat_groups(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('applicationStats')
                ->has('sessionStats')
                ->has('gradingStats')
            );
    }

    public function test_proctor_sees_session_stats_not_application_stats(): void
    {
        $proctor = User::factory()->create();
        $proctor->assignRole('proctor');

        $this->actingAs($proctor)
            ->get('/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('applicationStats', [])
                ->has('sessionStats')
            );
    }
}
```

- [ ] **Step 2: Run the test — expect FAIL**

```bash
php artisan test --compact --filter=DashboardControllerTest
```
Expected: FAIL — `applicationStats` prop does not exist yet.

- [ ] **Step 3: Rewrite DashboardService**

Replace the contents of `app/Services/DashboardService.php` with:

```php
<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    /**
     * Application-level KPI stats (admin / super_admin only).
     *
     * @return array<int, array{key: string, label: string, value: int|string, href?: string}>
     */
    public function getApplicationStats(User $user): array
    {
        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            return [];
        }

        $activeSeason = Season::active();

        $base = Application::query();
        if ($activeSeason !== null) {
            $base->forSeason($activeSeason);
        }

        return [
            [
                'key'   => 'applications_pending',
                'label' => 'Pending',
                'value' => (clone $base)->where('status', 'pending')->count(),
                'href'  => '/applications',
            ],
            [
                'key'   => 'applications_accepted',
                'label' => 'Accepted',
                'value' => (clone $base)->where('status', 'accepted')->count(),
                'href'  => '/applications',
            ],
            [
                'key'   => 'applications_dismissed',
                'label' => 'Dismissed',
                'value' => (clone $base)->where('status', 'dismissed')->count(),
                'href'  => '/applications',
            ],
        ];
    }

    /**
     * Session-level KPI stats (proctor / test_administrator / super_admin).
     *
     * @return array<int, array{key: string, label: string, value: int|string, href?: string}>
     */
    public function getSessionStats(User $user): array
    {
        if (! $user->hasAnyRole(['super_admin', 'proctor', 'test_administrator'])) {
            return [];
        }

        if (! Schema::hasTable('exam_sessions')) {
            return [];
        }

        $activeSeason = Season::active();

        $upcomingQuery = ExamSession::query()
            ->whereIn('status', [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS]);

        if ($activeSeason !== null) {
            $upcomingQuery->where('season_id', $activeSeason->id);
        }

        $upcoming = $upcomingQuery->count();

        $attendanceDue = DB::table('exam_session_applicant')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'exam_session_applicant.exam_session_id')
            ->whereIn('exam_sessions.status', [ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED])
            ->where('exam_session_applicant.attendance_status', 'pending')
            ->count();

        $submissionsDue = DB::table('exam_session_applicant')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'exam_session_applicant.exam_session_id')
            ->whereIn('exam_sessions.status', [ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED])
            ->where('exam_session_applicant.submission_status', 'pending')
            ->count();

        return [
            [
                'key'   => 'sessions_upcoming',
                'label' => 'Upcoming Sessions',
                'value' => $upcoming,
                'href'  => '/admin/test-scheduling',
            ],
            [
                'key'   => 'attendance_due',
                'label' => 'Attendance Due',
                'value' => $attendanceDue,
                'href'  => '/admin/test-scheduling',
            ],
            [
                'key'   => 'submissions_due',
                'label' => 'Submissions Due',
                'value' => $submissionsDue,
                'href'  => '/admin/test-scheduling',
            ],
        ];
    }

    /**
     * Grading + release KPI stats (test_administrator / super_admin only).
     *
     * @return array<int, array{key: string, label: string, value: int|string, href?: string}>
     */
    public function getGradingStats(User $user): array
    {
        if (! $user->hasAnyRole(['super_admin', 'test_administrator'])) {
            return [];
        }

        $pendingGrading = GradingSession::query()
            ->whereIn('status', [GradingSession::STATUS_OPEN, GradingSession::STATUS_IN_PROGRESS, GradingSession::STATUS_REVIEW])
            ->count();

        $pendingRelease = ConsultationSummary::query()
            ->where('status', ConsultationSummary::STATUS_DRAFT)
            ->count();

        return [
            [
                'key'   => 'grading_pending',
                'label' => 'Pending Grading',
                'value' => $pendingGrading,
                'href'  => '/grading',
            ],
            [
                'key'   => 'release_pending',
                'label' => 'Pending Release',
                'value' => $pendingRelease,
                'href'  => '/release',
            ],
        ];
    }
}
```

- [ ] **Step 4: Update DashboardController**

Replace `app/Http/Controllers/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'user'             => $user,
            'applicationStats' => $this->dashboardService->getApplicationStats($user),
            'sessionStats'     => $this->dashboardService->getSessionStats($user),
            'gradingStats'     => $this->dashboardService->getGradingStats($user),
        ]);
    }
}
```

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/pint app/Services/DashboardService.php app/Http/Controllers/DashboardController.php --format agent
```

- [ ] **Step 6: Run the test — expect PASS**

```bash
php artisan test --compact --filter=DashboardControllerTest
```
Expected: all 3 tests PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Services/DashboardService.php app/Http/Controllers/DashboardController.php tests/Feature/DashboardControllerTest.php
git commit -m "feat: refactor DashboardService into role-scoped stat groups (2.4)"
```

---

## Task 8: Dashboard Frontend Revamp (2.4 + 2.5)

**Files:**
- Modify: `resources/js/Pages/Dashboard.svelte`

This replaces the generic `StatCard` grid with role-gated `KpiCard` sections and adds the Institution Information section for admin/super_admin.

- [ ] **Step 1: Rewrite Dashboard.svelte**

Replace the full contents of `resources/js/Pages/Dashboard.svelte`:

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import KpiCard from '@/Components/KpiCard.svelte';
  import { FileText, Calendar, GraduationCap, SendHorizonal, Users, DoorOpen, BookOpen, Sparkles } from 'lucide-svelte';

  let { user, applicationStats, sessionStats, gradingStats } = $props();

  const breadcrumbs = [{ label: 'Dashboard' }];
  const page = usePage();
  const authUser = $derived($page.props.auth?.user ?? null);
  const roles = $derived(authUser?.roles?.map((r) => r.name) ?? user?.roles?.map((r) => r.name) ?? []);

  function hasRole(r) {
    return roles.includes(r);
  }

  const safeApplicationStats = $derived(Array.isArray(applicationStats) ? applicationStats : []);
  const safeSessionStats = $derived(Array.isArray(sessionStats) ? sessionStats : []);
  const safeGradingStats = $derived(Array.isArray(gradingStats) ? gradingStats : []);

  // Quick actions per role — no "Print Admission Slip"
  const quickActions = $derived([
    (hasRole('admin') || hasRole('super_admin')) && { href: '/applications', label: 'View Applications', icon: FileText },
    (hasRole('proctor') || hasRole('test_administrator') || hasRole('super_admin')) && { href: '/admin/test-scheduling', label: 'My Sessions', icon: Calendar },
    (hasRole('test_administrator') || hasRole('super_admin')) && { href: '/grading', label: 'Grading', icon: GraduationCap },
    (hasRole('test_administrator') || hasRole('super_admin')) && { href: '/release', label: 'Release Results', icon: SendHorizonal },
    (hasRole('admin') || hasRole('super_admin')) && { href: '/admin/users', label: 'Manage Users', icon: Users },
  ].filter(Boolean));

  const showAiExamScheduler = $derived(hasRole('super_admin') || hasRole('admin'));
  const showInstitutionInfo = $derived(hasRole('super_admin') || hasRole('admin'));
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-8 min-w-0">
    <p class="text-muted-foreground">Welcome back, {user?.name ?? 'User'}.</p>

    <!-- Application KPIs — admin / super_admin -->
    {#if safeApplicationStats.length > 0}
      <section>
        <h2 class="mb-4 text-base font-semibold text-foreground">Applications</h2>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
          {#each safeApplicationStats as stat (stat.key)}
            <KpiCard
              label={stat.label}
              value={stat.value}
              href={stat.href}
            />
          {/each}
        </div>
      </section>
    {/if}

    <!-- Session KPIs — proctor / test_administrator / super_admin -->
    {#if safeSessionStats.length > 0}
      <section>
        <h2 class="mb-4 text-base font-semibold text-foreground">Sessions</h2>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
          {#each safeSessionStats as stat (stat.key)}
            <KpiCard
              label={stat.label}
              value={stat.value}
              href={stat.href}
            />
          {/each}
        </div>
      </section>
    {/if}

    <!-- Grading + Release KPIs — test_administrator / super_admin -->
    {#if safeGradingStats.length > 0}
      <section>
        <h2 class="mb-4 text-base font-semibold text-foreground">Grading & Release</h2>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
          {#each safeGradingStats as stat (stat.key)}
            <KpiCard
              label={stat.label}
              value={stat.value}
              href={stat.href}
            />
          {/each}
        </div>
      </section>
    {/if}

    <!-- Quick Actions -->
    {#if quickActions.length > 0}
      <section>
        <div class="glass-panel p-6 rounded-2xl">
          <h2 class="text-lg font-bold mb-4 text-foreground">Quick Actions</h2>
          <div class="flex flex-wrap gap-3">
            {#each quickActions as action}
              <Link
                href={action.href}
                class="inline-flex items-center gap-2 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors min-h-[44px]"
              >
                <action.icon class="h-4 w-4 shrink-0" />
                {action.label}
              </Link>
            {/each}
          </div>
        </div>
      </section>
    {/if}

    <!-- Institution Information — admin / super_admin (2.5) -->
    {#if showInstitutionInfo}
      <section>
        <div class="glass-panel p-6 rounded-2xl">
          <h2 class="text-lg font-bold mb-4 text-foreground">Institution Information</h2>
          <div class="flex flex-wrap gap-3">
            <Link
              href="/admin/rooms"
              class="inline-flex items-center gap-2 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors min-h-[44px]"
            >
              <DoorOpen class="h-4 w-4 shrink-0" />
              Room Management
            </Link>
            <Link
              href="/admin/courses"
              class="inline-flex items-center gap-2 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors min-h-[44px]"
            >
              <BookOpen class="h-4 w-4 shrink-0" />
              Course Management
            </Link>
          </div>
        </div>
      </section>
    {/if}

    <!-- AI Exam Scheduler promo — admin / super_admin -->
    {#if showAiExamScheduler}
      <section>
        <div class="rounded-2xl border border-border bg-muted/50 p-6">
          <div class="flex items-center gap-2 mb-2">
            <Sparkles class="h-5 w-5 text-primary" />
            <h3 class="font-bold text-lg text-foreground">AI Exam Scheduler</h3>
          </div>
          <p class="text-sm text-muted-foreground mb-4 line-clamp-2">
            Plan exam sessions with AI: describe dates, rooms, and capacity. The assistant suggests a schedule; apply to create sessions and assign applicants.
          </p>
          <Link
            href="/admin/test-scheduling?open=schedule-assistant"
            class="inline-flex items-center justify-center rounded-lg border border-primary bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors w-full"
          >
            Open AI Scheduler
          </Link>
        </div>
      </section>
    {/if}

    {#if safeApplicationStats.length === 0 && safeSessionStats.length === 0 && safeGradingStats.length === 0 && quickActions.length === 0}
      <p class="text-muted-foreground">Use the sidebar to navigate.</p>
    {/if}
  </div>
</AuthenticatedLayout>
```

- [ ] **Step 2: Run the test suite**

```bash
php artisan test --compact --filter=DashboardControllerTest
```
Expected: all 3 tests PASS (props are now sent; Svelte is compiled separately).

- [ ] **Step 3: Build assets and visually verify**

```bash
npm run build
```
Then open the dashboard in the browser as each role to confirm:
- `admin` sees Application KPIs + Quick Actions + Institution Information
- `proctor` sees Session KPIs only
- `test_administrator` sees Session KPIs + Grading & Release KPIs
- `super_admin` sees all sections
- No "Print Admission Slip" appears for any role

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Dashboard.svelte
git commit -m "feat: dashboard role-scoped KPI sections, institution info, remove print slips (2.4, 2.5)"
```

---

## Self-Review Against Spec

| Spec item | Task | Status |
|-----------|------|--------|
| 2.1 — Apply page: justify-end, text-center title, "exam scheduling", no duplicate Back to Home | Task 4 | ✅ Covered |
| 2.2 — Login tab localStorage | Task 5 | ✅ Covered |
| 2.3 — Import button guard, incomplete_documents removed | Task 6 | ✅ Covered |
| 2.4 — Staff dashboard role-scoped KPIs, Quick Actions, no Print Slip | Task 7 + Task 8 | ✅ Covered |
| 2.5 — Institution Information section (admin/super_admin) | Task 8 | ✅ Covered |
| 2.6 — Compact table padding | Task 1 | ✅ Covered |
| 2.7 — InfoPopover component | Task 3 | ✅ Covered |
| 2.8 — Sticky nav header | Task 2 | ✅ Already done; verify + Admission Slip Templates nav removal |

**Admission Slip Templates note:** Spec 2.5 says "remove from nav" (not from routes/file). Task 2 covers nav removal. The route and `AdmissionSlipTemplates/Index.svelte` are left in place per spec instruction.

**Duplicate "Back to Home" note:** The current `Success.svelte` has only one Back to Home button. No removal needed.

**No placeholders found.** All steps have concrete code.

**Type consistency:** `KpiCard` props used are `label`, `value`, `href` — matches `KpiCard.svelte` lines 6–13. `applicationStats`, `sessionStats`, `gradingStats` keys are consistent between service and frontend.
