# Live Exam Monitoring Insights Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Upgrade the live exam monitoring page with robust database-driven counts, top-level KPI cards, dual progress tracking (completion + elapsed time), overtime warnings, seat capacity details, and proctor tags.

**Architecture:** Fetch applicant attendance/submission statuses in a single database round-trip using Eloquent `withCount` subqueries on the `ExamSessionController@monitoring` endpoint, and derive overall statistics dynamically on the Svelte 5 frontend using reactive `$derived` runes.

**Tech Stack:** Laravel 12, Inertia v2, Svelte 5, Tailwind CSS, Lucide-svelte.

---

### Task 1: Backend Controller Live Data Query

**Files:**
- Modify: `app/Http/Controllers/Admin/ExamSessionController.php`
- Modify: `tests/Feature/ExamSessionConflictTest.php`

**Step 1: Write the failing test**

We will add a new test method to `tests/Feature/ExamSessionConflictTest.php` to verify that the `/admin/exam-monitoring` page receives correct stats in the `sessions` Inertia prop.

Add this method to `tests/Feature/ExamSessionConflictTest.php`:
```php
    public function test_monitoring_endpoint_returns_correct_applicant_statistics(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $session = ExamSession::factory()->create([
            'status' => ExamSession::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $applicant1 = Applicant::factory()->create();
        $applicant2 = Applicant::factory()->create();

        // Attach applicants with attendance and submission statuses
        $session->applicants()->attach($applicant1->id, [
            'attendance_status' => 'present',
            'submission_status' => 'submitted',
        ]);
        $session->applicants()->attach($applicant2->id, [
            'attendance_status' => 'present',
            'submission_status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/exam-monitoring');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/TestScheduling/Monitoring')
            ->has('sessions', 1, fn ($pageSession) => $pageSession
                ->where('total_count', 2)
                ->where('present_count', 2)
                ->where('submitted_count', 1)
                ->where('absent_count', 0)
                ->etc()
            )
        );
    }
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter test_monitoring_endpoint_returns_correct_applicant_statistics`  
Expected: FAIL (either `total_count` doesn't exist, or it is not matching since counts aren't loaded).

**Step 3: Write minimal implementation**

Modify the `monitoring` function in `app/Http/Controllers/Admin/ExamSessionController.php`:
```php
    public function monitoring(Request $request): Response
    {
        $this->authorize('viewAny', ExamSession::class);

        $user = $request->user();
        $isProctorView = $user->hasAnyRole(['proctor']) && ! $user->hasAnyRole(['super_admin', 'registrar_administrator']);
        $activeAcademicYear = AcademicYear::active();

        $query = ExamSession::query()
            ->with(['room:id,name,building,capacity', 'proctors:id,name'])
            ->withCount([
                'applicants as total',
                'applicants as present' => function ($q) {
                    $q->where('exam_session_applicant.attendance_status', 'present');
                },
                'applicants as absent' => function ($q) {
                    $q->where('exam_session_applicant.attendance_status', 'absent');
                },
                'applicants as submitted' => function ($q) {
                    $q->where('exam_session_applicant.submission_status', 'submitted');
                }
            ])
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

        $breadcrumbParent = $isProctorView
            ? ['label' => 'My Sessions', 'href' => '/proctor/my-sessions']
            : ['label' => 'Exam Monitoring', 'href' => '/admin/exam-monitoring'];

        return Inertia::render('Admin/TestScheduling/Monitoring', [
            'sessions' => $sessions,
            'view' => $isProctorView ? 'proctor' : 'admin',
            'breadcrumbParent' => $breadcrumbParent,
        ]);
    }
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter test_monitoring_endpoint_returns_correct_applicant_statistics`  
Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/ExamSessionController.php tests/Feature/ExamSessionConflictTest.php
git commit -m "feat: fetch live applicant counts inside monitoring controller"
```

---

### Task 2: Frontend State Derivations and Live Pulse Banner

**Files:**
- Modify: `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte`

**Step 1: Write minimal Svelte implementation for State Aggregations & live pulses**

Open `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte` and import `KpiCard`:
```svelte
  import KpiCard from '@/Components/KpiCard.svelte';
  import { Activity, Clock, CheckCircle, Users } from 'lucide-svelte';
```

Add reactive derived variables in the script tag using Svelte 5 `$derived`:
```svelte
  const totalSessions = $derived(sessions.length);
  const totalPresent = $derived(sessions.reduce((acc, s) => acc + (s.present_count ?? 0), 0));
  const totalExpected = $derived(sessions.reduce((acc, s) => acc + (s.total_count ?? 0), 0));
  const totalSubmitted = $derived(sessions.reduce((acc, s) => acc + (s.submitted_count ?? 0), 0));
  
  const attendanceRate = $derived(totalExpected > 0 ? Math.round((totalPresent / totalExpected) * 100) : 0);
  const completionRate = $derived(totalPresent > 0 ? Math.round((totalSubmitted / totalPresent) * 100) : 0);
```

**Step 2: Add dynamic page header & live pulse banner**

Replace the current description header in `Monitoring.svelte`:
```svelte
    <p class="text-sm text-muted-foreground">
      Live status of in-progress exam sessions. Data refreshes every 15 seconds.
    </p>
```
With a premium live indicator:
```svelte
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-border/50 pb-5">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Live Exam Monitoring</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Real-time status of active testing rooms. Data automatically refreshes.
        </p>
      </div>
      <div class="flex items-center gap-2 self-start sm:self-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
          <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Live Feed Active (15s)
        </span>
      </div>
    </div>
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Monitoring.svelte
git commit -m "style: add live feed header banner and derived metrics to monitoring dashboard"
```

---

### Task 3: Top-level KPI Panels Render

**Files:**
- Modify: `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte`

**Step 1: Render the dynamic stats cards at the top of the page**

Insert the dynamic KpiCard layout grid under the new live header block:
```svelte
    <!-- KPI Summary Dashboard -->
    {#if sessions.length > 0}
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-2">
        <KpiCard
          label="Active Sessions"
          value={totalSessions}
          status="ok"
          secondaryItems={[{ label: 'Rooms Live', value: totalSessions }]}
        />
        <KpiCard
          label="Live Testers"
          value={totalPresent}
          status="ok"
          secondaryItems={[{ label: 'Expected Total', value: totalExpected }]}
        />
        <KpiCard
          label="Attendance Rate"
          value={`${attendanceRate}%`}
          status={attendanceRate >= 80 ? 'ok' : attendanceRate >= 50 ? 'warn' : 'critical'}
          secondaryItems={[{ label: 'Checked In', value: `${totalPresent} / ${totalExpected}` }]}
        />
        <KpiCard
          label="Overall Progress"
          value={`${completionRate}%`}
          status={completionRate === 100 ? 'ok' : 'warn'}
          secondaryItems={[{ label: 'Finished', value: `${totalSubmitted} / ${totalPresent}` }]}
        />
      </div>
    {/if}
```

**Step 2: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Monitoring.svelte
git commit -m "style: add top-level KPI cards to live monitoring page"
```

---

### Task 4: Svelte List View Row & Card Enhancements (Dual Progress + Proctor Tags)

**Files:**
- Modify: `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte`

**Step 1: Implement inline Javascript calculations for time remaining & overtime alerting**

In the `<script>` tag of `Monitoring.svelte`, add a helper function to calculate time progress and detect overtime:
```svelte
  function calculateTimeStats(session) {
    if (!session.started_at) return { percent: 0, text: 'Not started', isOvertime: false };
    
    const start = new Date(session.started_at).getTime();
    
    // Parse duration
    const startParts = (session.start_time || '00:00:00').split(':');
    const endParts = (session.end_time || '00:00:00').split(':');
    
    const startMins = parseInt(startParts[0], 10) * 60 + parseInt(startParts[1], 10);
    const endMins = parseInt(endParts[0], 10) * 60 + parseInt(endParts[1], 10);
    
    // Calculate allotted minutes
    let allottedMins = endMins - startMins;
    if (allottedMins <= 0) allottedMins = 120; // default 2 hours if parsing error or past midnight
    
    // Handle extended end time if present
    if (session.extended_end_time) {
      const extParts = session.extended_end_time.split(':');
      const extMins = parseInt(extParts[0], 10) * 60 + parseInt(extParts[1], 10);
      const extendedAllotted = extMins - startMins;
      if (extendedAllotted > allottedMins) allottedMins = extendedAllotted;
    }
    
    const totalDurationMs = allottedMins * 60 * 1000;
    const now = Date.now();
    const elapsedMs = now - start;
    
    const percent = Math.min(Math.round((elapsedMs / totalDurationMs) * 100), 100);
    const elapsedMinsTotal = Math.floor(elapsedMs / (60 * 1000));
    
    const isOvertime = elapsedMs > totalDurationMs;
    
    return {
      percent,
      elapsedText: `${elapsedMinsTotal}m elapsed / ${allottedMins}m total`,
      isOvertime
    };
  }
```

**Step 2: Update Table Rows with Real Data, Proctor tags, Capacity stats, and Progress bars**

Update the Table body rows to render correct metadata:
- Show Proctor names as small tags.
- Capacity: `session.present_count` / `session.room.capacity`.
- Student Progress: visual progress bar for `submitted_count / present_count`.
- Time Progress: visual progress bar showing elapsed time percent, showing an "Overtime" badge if needed.

**Step 3: Update Cards View with identical metrics**

Mirror this beautiful styling in the Card grid view for complete visual consistency across responsive layouts.

**Step 4: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Monitoring.svelte
git commit -m "feat: complete Svelte monitoring page visual enhancements with dual progress bars and proctor/room tags"
```

---

### Task 5: Empty/No Active Session State Polish

**Files:**
- Modify: `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte`

**Step 1: Redesign the empty state block to look premium**

When no sessions are in progress:
- Render a beautiful glassmorphic container with an illustrative layout, encouraging admins to start sessions from the scheduler.

**Step 2: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Monitoring.svelte
git commit -m "style: polish empty state card container in monitoring page"
```
