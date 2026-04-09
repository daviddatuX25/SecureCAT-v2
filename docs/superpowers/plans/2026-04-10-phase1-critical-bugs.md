# Phase 1: Critical Bugs — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all 8 Phase 1 blocking bugs across the AI Exam Scheduler, session time enforcement, role access, and login routing.

**Architecture:** Two batches — Batch 1 touches only frontend Svelte components and the AI scheduler backend; Batch 2 touches the PHP session roster controller, ExamSession model, route middleware, and two Svelte pages. Each task is self-contained with its own commit.

**Tech Stack:** Laravel 12, Svelte 5 (Inertia v2), PHP 8.2, Carbon, PHPUnit feature tests

**Design Spec:** `docs/superpowers/specs/2026-04-10-phase1-critical-bugs-design.md`

---

## File Map

### Batch 1 — AI Scheduler Frontend

| File | Role |
|---|---|
| `resources/js/Components/ScheduleAssistantPanel.svelte` | AI chat + preview UI — fixes 1.1a, 1.3, 1.4 |
| `resources/js/Pages/Admin/TestScheduling/Show.svelte` | Session detail page — fix 1.1b |
| `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php` | Chat endpoint — fix 1.3 backend normalisation |

### Batch 2 — Backend / Routing

| File | Role |
|---|---|
| `app/Models/ExamSession.php` | Add `isWithinExamWindow()` + `isPastEndTime()` methods |
| `app/Http/Controllers/Proctor/SessionRosterController.php` | Pass time flags to frontend; enforce time gate in attendance/submission |
| `resources/js/Pages/Proctor/SessionRoster.svelte` | Update `canMarkAttendance` + `canLogSubmission` derived conditions |
| `routes/web.php` | Add `test_administrator` to monitoring route middleware |
| `resources/js/Pages/Home.svelte` | Fix two `/portal/login` links → `/login` |
| `resources/js/Pages/Portal/ForgotPassword.svelte` | Fix "Back to sign in" link → `/login` |
| `tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php` | New: time-gate feature tests |

---

## BATCH 1: AI Scheduler Frontend

---

### Task 1: Fix infinite reactive loop in ScheduleAssistantPanel (Bug 1.1a)

**Files:**
- Modify: `resources/js/Components/ScheduleAssistantPanel.svelte`

**Background:** `messages` is initialised to `[]`, then a `$effect` checks `messages.length === 0` and writes `messages`. When `initialMessages` is empty, the write triggers the effect again, causing an infinite loop (`effect_update_depth_exceeded`). The fix is to initialise directly from the prop.

- [ ] **Step 1: Open the file and find the broken initialisation**

  Open `resources/js/Components/ScheduleAssistantPanel.svelte`. Find this block near the top of the `<script>` (around line 18–21):

  ```js
  let messages = $state([]);
  $effect(() => {
    if (messages.length === 0) messages = [...(initialMessages ?? [])];
  });
  ```

- [ ] **Step 2: Replace with direct initialisation**

  Delete those 3 lines and replace with a single line:

  ```js
  let messages = $state([...(initialMessages ?? [])]);
  ```

- [ ] **Step 3: Verify the change manually**

  Open the app, go to Exam Scheduling, open the "Schedule with AI" modal. Open the browser console. Confirm:
  - No `effect_update_depth_exceeded` error
  - Chat area loads (empty or with prior messages)
  - Send button is enabled when you type in the textarea

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Components/ScheduleAssistantPanel.svelte
  git commit -m "fix: remove reactive loop in ScheduleAssistantPanel messages init"
  ```

---

### Task 2: Fix TypeError on Show.svelte — releaseDateForm.transform (Bug 1.1b)

**Files:**
- Modify: `resources/js/Pages/Admin/TestScheduling/Show.svelte`

**Background:** `releaseDateForm` is a Svelte 5 store returned by `useForm()`. Calling `.transform()` on the store wrapper (not the form object `$releaseDateForm`) throws `TypeError: releaseDateForm.transform is not a function`. The transform is also a no-op (identity), so the correct fix is to remove the line entirely.

- [ ] **Step 1: Find the broken line**

  Open `resources/js/Pages/Admin/TestScheduling/Show.svelte`. Find the `submitReleaseDate` function (around line 102–106):

  ```js
  function submitReleaseDate(e) {
    e.preventDefault();
    releaseDateForm.transform((data) => data);   // ← this line is wrong
    $releaseDateForm.put(`/admin/test-scheduling/${session.id}/release-date`);
  }
  ```

- [ ] **Step 2: Remove the broken line**

  The function should read:

  ```js
  function submitReleaseDate(e) {
    e.preventDefault();
    $releaseDateForm.put(`/admin/test-scheduling/${session.id}/release-date`);
  }
  ```

- [ ] **Step 3: Verify**

  Navigate to any session detail page (`/admin/test-scheduling/{id}`). Open the browser console. Confirm no TypeError appears on load. Click "Set release date" — it should submit without errors.

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Pages/Admin/TestScheduling/Show.svelte
  git commit -m "fix: remove invalid releaseDateForm.transform call on Show.svelte"
  ```

---

### Task 3: Robust JSON field normalisation in ScheduleAssistantPanel (Bug 1.3)

**Files:**
- Modify: `resources/js/Components/ScheduleAssistantPanel.svelte`

**Background:** The AI may return field names that differ from the canonical schema (`room_id`, `date`, `start_time`, `end_time`, `applicant_ids`). `getScheduleRows()` only handles the canonical form, so anything different shows as `undefined` or `—`. We add a `normalizeSession()` function that tries multiple known aliases before giving up gracefully.

- [ ] **Step 1: Add `normalizeSession()` after the `draftMap` derived**

  In `resources/js/Components/ScheduleAssistantPanel.svelte`, after the `draftMap` block, add:

  ```js
  /**
   * Normalise a raw session object from the AI to the canonical field set.
   * Tries multiple known aliases so the UI is robust to AI hallucinations.
   */
  function normalizeSession(s) {
    return {
      exam_session_id: s.exam_session_id ?? s.session_id ?? null,
      room_id:        s.room_id   ?? s.roomId   ?? s.room?.id   ?? null,
      room_name:      s.room_name ?? s.room?.name ?? null,
      date:           s.date      ?? s.session_date ?? s.exam_date ?? null,
      start_time:     s.start_time ?? s.startTime ?? s.time_start ?? null,
      end_time:       s.end_time   ?? s.endTime   ?? s.time_end   ?? null,
      applicant_ids:  s.applicant_ids ?? s.applicantIds ?? s.applicants ?? [],
    };
  }
  ```

- [ ] **Step 2: Update `getScheduleRows()` to use `normalizeSession()`**

  Replace the existing `getScheduleRows` function:

  ```js
  function getScheduleRows(schedule) {
    if (!schedule?.sessions?.length) return [];
    return schedule.sessions.map((raw) => {
      const s = normalizeSession(raw);
      if (s.exam_session_id) {
        const draft = draftMap[s.exam_session_id];
        return {
          type: 'Existing draft',
          room: draft?.room?.name ?? '—',
          date: draft?.date ?? '—',
          time: draft ? [draft.start_time, draft.end_time].filter(Boolean).join('–') : '—',
          applicant_count: (s.applicant_ids ?? []).length,
        };
      }
      return {
        type: 'New',
        room: roomMap[s.room_id]?.name ?? s.room_name ?? `Room ${s.room_id ?? '?'}`,
        date: s.date ?? '—',
        time: [s.start_time, s.end_time].filter(Boolean).join('–') || '—',
        applicant_count: (s.applicant_ids ?? []).length,
      };
    });
  }
  ```

- [ ] **Step 3: Verify**

  Open the AI scheduler modal, chat with the assistant, click "Generate schedule". The preview table should show room name, date, and time — not `undefined`. If the room lookup fails (AI returned an unrecognised room_id), it will show `Room ?` rather than blank.

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Components/ScheduleAssistantPanel.svelte
  git commit -m "fix: normalise AI JSON field aliases in getScheduleRows"
  ```

---

### Task 4: Backend normalisation in chat endpoint (Bug 1.3 — backend side)

**Files:**
- Modify: `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php`

**Background:** The `chat()` method returns `structured_schedule` from the AI directly without validating field names. This is where the non-canonical field names originate. We add a normaliser so the canonical schema reaches the frontend regardless of what the AI model returns.

- [ ] **Step 1: Add a private `normalizeStructuredSchedule()` method**

  Open `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php`. At the bottom of the class (before the closing `}`), add:

  ```php
  /**
   * Coerce AI-returned structured schedule to the canonical field set.
   * Handles common hallucinated aliases so the frontend always gets clean data.
   */
  private function normalizeStructuredSchedule(array $schedule): array
  {
      $sessions = $schedule['sessions'] ?? [];
      $normalised = [];

      foreach ($sessions as $s) {
          $normalised[] = [
              'exam_session_id' => $s['exam_session_id'] ?? $s['session_id'] ?? null,
              'room_id'         => $s['room_id']     ?? $s['roomId']    ?? $s['room']['id']   ?? null,
              'date'            => $s['date']         ?? $s['session_date'] ?? $s['exam_date'] ?? null,
              'start_time'      => $s['start_time']   ?? $s['startTime'] ?? $s['time_start']  ?? null,
              'end_time'        => $s['end_time']     ?? $s['endTime']   ?? $s['time_end']    ?? null,
              'applicant_ids'   => $s['applicant_ids'] ?? $s['applicantIds'] ?? $s['applicants'] ?? [],
          ];
      }

      return ['sessions' => $normalised];
  }
  ```

- [ ] **Step 2: Apply normalisation before returning `structured_schedule` from `chat()`**

  Inside the `chat()` method, find the block that sets `$response['structured_schedule']` (around line 130–131):

  ```php
  if (isset($result['structured_schedule'])) {
      $response['structured_schedule'] = $result['structured_schedule'];
  ```

  Change it to:

  ```php
  if (isset($result['structured_schedule'])) {
      $response['structured_schedule'] = $this->normalizeStructuredSchedule($result['structured_schedule']);
  ```

  Also find where the schedule is attached to the last message (around line 115–116):

  ```php
  if (isset($result['structured_schedule'])) {
      $lastMessage['schedule'] = $result['structured_schedule'];
  ```

  Change it to:

  ```php
  if (isset($result['structured_schedule'])) {
      $lastMessage['schedule'] = $this->normalizeStructuredSchedule($result['structured_schedule']);
  ```

- [ ] **Step 3: Run Pint to format**

  ```bash
  vendor/bin/pint --dirty --format agent
  ```

- [ ] **Step 4: Commit**

  ```bash
  git add app/Http/Controllers/Admin/ExamSchedulingAssistantController.php
  git commit -m "fix: normalise structured schedule field names in chat response"
  ```

---

### Task 5: Remove duplicate in-chat table (Bug 1.4)

**Files:**
- Modify: `resources/js/Components/ScheduleAssistantPanel.svelte`

**Background:** When the AI returns a structured schedule, it's stored in both `msg.schedule` (renders a table inside the chat bubble) and `structuredSchedule` (renders the preview table below). Both show at the same time. The fix: only render the in-chat table when no preview exists yet.

- [ ] **Step 1: Find the in-chat table render block**

  In `resources/js/Components/ScheduleAssistantPanel.svelte`, inside the messages loop, find:

  ```svelte
  {#if msg.schedule?.sessions?.length}
    <div class="rounded-lg border ...">
  ```

- [ ] **Step 2: Add the `!structuredSchedule` guard**

  Change that opening condition to:

  ```svelte
  {#if msg.schedule?.sessions?.length && !structuredSchedule}
    <div class="rounded-lg border ...">
  ```

  Everything else inside the block stays identical — only the `{#if}` condition changes.

- [ ] **Step 3: Add a hint label under the Generate button when disabled (Bug 1.2)**

  Find the closing `</Button>` for the Generate schedule button. Directly after it, add:

  ```svelte
  {#if !canGenerate}
    <p class="text-xs text-muted-foreground w-full md:w-auto">
      Send a message and get a reply first to unlock Generate schedule.
    </p>
  {/if}
  ```

- [ ] **Step 4: Verify**

  Generate a schedule in the AI modal. Confirm:
  - While no preview table exists yet: the in-chat table renders correctly
  - After "Generate schedule" returns a structured plan: only the preview table below shows; the in-chat table is hidden
  - When the Generate button is locked: the hint text appears below it

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Components/ScheduleAssistantPanel.svelte
  git commit -m "fix: hide in-chat table when preview present; add generate button hint"
  ```

---

## BATCH 2: Backend / Routing

---

### Task 6: Add time-window helpers to ExamSession model (Bugs 1.5 + 1.6 foundation)

**Files:**
- Modify: `app/Models/ExamSession.php`
- Test: `tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php` (create)

**Background:** The controller needs to know (a) whether we are currently within the exam window (`start_time` ≤ now ≤ `end_time`) and (b) whether we are past the end time. `isWithinStartWindow()` already exists but covers only the start window, not the full exam period.

- [ ] **Step 1: Create the test file**

  ```bash
  php artisan make:test Proctor/SessionRosterTimeEnforcementTest --phpunit
  ```

- [ ] **Step 2: Write the failing tests**

  Replace the contents of `tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php` with:

  ```php
  <?php

  namespace Tests\Feature\Proctor;

  use App\Models\ExamSession;
  use Carbon\Carbon;
  use Illuminate\Foundation\Testing\RefreshDatabase;
  use Tests\TestCase;

  class SessionRosterTimeEnforcementTest extends TestCase
  {
      use RefreshDatabase;

      private function makeSession(string $date, string $start, ?string $end): ExamSession
      {
          return ExamSession::factory()->create([
              'date'       => $date,
              'start_time' => $start,
              'end_time'   => $end,
              'status'     => ExamSession::STATUS_PUBLISHED,
          ]);
      }

      public function test_is_within_exam_window_during_window(): void
      {
          Carbon::setTestNow('2026-05-01 10:00:00');
          $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
          $this->assertTrue($session->isWithinExamWindow());
      }

      public function test_is_not_within_exam_window_before_start(): void
      {
          Carbon::setTestNow('2026-05-01 08:59:00');
          $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
          $this->assertFalse($session->isWithinExamWindow());
      }

      public function test_is_not_within_exam_window_after_end(): void
      {
          Carbon::setTestNow('2026-05-01 12:01:00');
          $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
          $this->assertFalse($session->isWithinExamWindow());
      }

      public function test_is_past_end_time_after_end(): void
      {
          Carbon::setTestNow('2026-05-01 12:01:00');
          $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
          $this->assertTrue($session->isPastEndTime());
      }

      public function test_is_not_past_end_time_during_window(): void
      {
          Carbon::setTestNow('2026-05-01 10:00:00');
          $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
          $this->assertFalse($session->isPastEndTime());
      }

      public function test_is_not_past_end_time_when_no_end_time(): void
      {
          Carbon::setTestNow('2026-05-01 23:00:00');
          $session = $this->makeSession('2026-05-01', '09:00:00', null);
          $this->assertFalse($session->isPastEndTime());
      }

      protected function tearDown(): void
      {
          Carbon::setTestNow();
          parent::tearDown();
      }
  }
  ```

- [ ] **Step 3: Run tests — expect FAIL**

  ```bash
  php artisan test --compact tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php
  ```

  Expected: FAIL — `Call to undefined method App\Models\ExamSession::isWithinExamWindow()`

- [ ] **Step 4: Add the two methods to ExamSession model**

  Open `app/Models/ExamSession.php`. Find where `isWithinStartWindow()` is defined (around line 136). Add the two new methods directly after it:

  ```php
  /**
   * True when the current time is between the session's start_time and end_time.
   * If no end_time is set, any time after start is considered within window.
   */
  public function isWithinExamWindow(?Carbon $now = null): bool
  {
      $now ??= Carbon::now();
      $sessionDate = Carbon::parse($this->date);
      $start = $sessionDate->copy()->setTimeFromTimeString($this->start_time);

      if ($now->lt($start)) {
          return false;
      }

      if (! $this->end_time) {
          return true;
      }

      $end = $sessionDate->copy()->setTimeFromTimeString($this->end_time);

      return $now->lte($end);
  }

  /**
   * True when the current time is past the session's end_time.
   * Returns false when end_time is not set (open-ended sessions never expire).
   */
  public function isPastEndTime(?Carbon $now = null): bool
  {
      if (! $this->end_time) {
          return false;
      }

      $now ??= Carbon::now();
      $sessionDate = Carbon::parse($this->date);
      $end = $sessionDate->copy()->setTimeFromTimeString($this->end_time);

      return $now->gt($end);
  }
  ```

  Make sure `Carbon\Carbon` is already imported at the top of the file (it should be from the existing `isWithinStartWindow` method — if not, add `use Carbon\Carbon;`).

- [ ] **Step 5: Run tests — expect PASS**

  ```bash
  php artisan test --compact tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php
  ```

  Expected: 6 tests, 6 passed.

- [ ] **Step 6: Run Pint**

  ```bash
  vendor/bin/pint --dirty --format agent
  ```

- [ ] **Step 7: Commit**

  ```bash
  git add app/Models/ExamSession.php tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php
  git commit -m "feat: add isWithinExamWindow and isPastEndTime to ExamSession model"
  ```

---

### Task 7: Pass time-window flags from controller to frontend (Bugs 1.5 + 1.6)

**Files:**
- Modify: `app/Http/Controllers/Proctor/SessionRosterController.php`
- Test: `tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php` (extend)

**Background:** `SessionRosterController::show()` already passes `is_within_start_window` but not `is_within_window` (full exam window) or `is_past_end`. The frontend needs both to gate the action buttons correctly.

- [ ] **Step 1: Add a controller test for the show response**

  Append this test to `tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php` (inside the class, before the closing `}`):

  ```php
  public function test_show_passes_time_flags_to_inertia(): void
  {
      $this->seed(\Database\Seeders\RoleSeeder::class);

      $proctor = \App\Models\User::factory()->create();
      $proctor->roles()->attach(\App\Models\Role::where('name', 'proctor')->first());

      Carbon::setTestNow('2026-05-01 10:00:00');

      $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
      $session->update(['status' => ExamSession::STATUS_PUBLISHED]);
      $session->proctors()->attach($proctor->id);

      $response = $this->actingAs($proctor)
          ->get("/proctor/sessions/{$session->id}");

      $response->assertInertia(fn ($page) => $page
          ->has('session.is_within_window')
          ->has('session.is_past_end')
          ->where('session.is_within_window', true)
          ->where('session.is_past_end', false)
      );
  }
  ```

- [ ] **Step 2: Run — expect FAIL**

  ```bash
  php artisan test --compact --filter test_show_passes_time_flags_to_inertia
  ```

  Expected: FAIL — property `is_within_window` not found in Inertia response.

- [ ] **Step 3: Update `SessionRosterController::show()` to include the flags**

  Open `app/Http/Controllers/Proctor/SessionRosterController.php`. Find the `return Inertia::render(...)` block at the bottom of `show()` (around line 68–76):

  ```php
  return Inertia::render('Proctor/SessionRoster', [
      'session' => array_merge($exam_session->toArray(), [
          'is_within_start_window' => $isWithinStartWindow,
          'can_override_schedule' => $canOverrideSchedule,
      ]),
      'applicants' => $applicants->values()->all(),
      'stats' => $stats,
  ]);
  ```

  Replace with:

  ```php
  return Inertia::render('Proctor/SessionRoster', [
      'session' => array_merge($exam_session->toArray(), [
          'is_within_start_window' => $isWithinStartWindow,
          'can_override_schedule'  => $canOverrideSchedule,
          'is_within_window'       => $exam_session->isWithinExamWindow(),
          'is_past_end'            => $exam_session->isPastEndTime(),
      ]),
      'applicants' => $applicants->values()->all(),
      'stats' => $stats,
  ]);
  ```

- [ ] **Step 4: Run — expect PASS**

  ```bash
  php artisan test --compact --filter test_show_passes_time_flags_to_inertia
  ```

  Expected: PASS.

- [ ] **Step 5: Run Pint**

  ```bash
  vendor/bin/pint --dirty --format agent
  ```

- [ ] **Step 6: Commit**

  ```bash
  git add app/Http/Controllers/Proctor/SessionRosterController.php tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php
  git commit -m "fix: pass is_within_window and is_past_end flags to SessionRoster"
  ```

---

### Task 8: Enforce time gate in attendance + submission endpoints (Bug 1.5)

**Files:**
- Modify: `app/Http/Controllers/Proctor/SessionRosterController.php`
- Test: `tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php` (extend)

**Background:** `storeAttendance()` and `storeSubmission()` check session status but not whether end time has passed. After end time, both must be blocked.

- [ ] **Step 1: Add enforcement tests**

  Append these two tests to `tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php`:

  ```php
  public function test_attendance_blocked_after_end_time(): void
  {
      $this->seed(\Database\Seeders\RoleSeeder::class);

      $proctor = \App\Models\User::factory()->create();
      $proctor->roles()->attach(\App\Models\Role::where('name', 'proctor')->first());

      Carbon::setTestNow('2026-05-01 13:00:00'); // past 12:00 end

      $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
      $session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
      $session->proctors()->attach($proctor->id);

      $applicant = \App\Models\Applicant::factory()->create();
      $session->applicants()->attach($applicant->id, [
          'attendance_status' => 'pending',
          'submission_status' => 'pending',
      ]);

      $response = $this->actingAs($proctor)->post("/proctor/sessions/{$session->id}/attendance", [
          'applicant_id' => $applicant->id,
          'status'       => 'present',
      ]);

      $response->assertStatus(422);
      $this->assertDatabaseMissing('exam_session_applicant', [
          'attendance_status' => 'present',
      ]);
  }

  public function test_submission_blocked_after_end_time(): void
  {
      $this->seed(\Database\Seeders\RoleSeeder::class);

      $proctor = \App\Models\User::factory()->create();
      $proctor->roles()->attach(\App\Models\Role::where('name', 'proctor')->first());

      Carbon::setTestNow('2026-05-01 13:00:00'); // past 12:00 end

      $session = $this->makeSession('2026-05-01', '09:00:00', '12:00:00');
      $session->update(['status' => ExamSession::STATUS_IN_PROGRESS]);
      $session->proctors()->attach($proctor->id);

      $applicant = \App\Models\Applicant::factory()->create();
      $session->applicants()->attach($applicant->id, [
          'attendance_status' => 'present',
          'submission_status' => 'pending',
      ]);

      $response = $this->actingAs($proctor)->post("/proctor/sessions/{$session->id}/submission", [
          'applicant_id' => $applicant->id,
      ]);

      $response->assertStatus(422);
      $this->assertDatabaseMissing('exam_session_applicant', [
          'submission_status' => 'submitted',
      ]);
  }
  ```

- [ ] **Step 2: Run — expect FAIL**

  ```bash
  php artisan test --compact --filter "test_attendance_blocked_after_end_time|test_submission_blocked_after_end_time"
  ```

  Expected: FAIL (currently returns 302/200 instead of 422).

- [ ] **Step 3: Add time-gate check to `storeAttendance()`**

  In `SessionRosterController.php`, find `storeAttendance()`. After the existing status check (the `if (! in_array($session->status, [...]) ...)` block), add:

  ```php
  if ($session->isPastEndTime()) {
      $message = 'The exam window has ended. Attendance cannot be marked.';
      if ($request->wantsJson()) {
          return response()->json(['message' => $message], 422);
      }
      return back()->with('error', $message);
  }
  ```

- [ ] **Step 4: Add time-gate check to `storeSubmission()`**

  In `SessionRosterController.php`, find `storeSubmission()`. After the existing status check, add:

  ```php
  if ($session->isPastEndTime()) {
      $message = 'The exam window has ended. Submissions cannot be recorded.';
      return response()->json(['message' => $message], 422);
  }
  ```

- [ ] **Step 5: Run — expect PASS**

  ```bash
  php artisan test --compact --filter "test_attendance_blocked_after_end_time|test_submission_blocked_after_end_time"
  ```

  Expected: 2 tests, 2 passed.

- [ ] **Step 6: Run Pint**

  ```bash
  vendor/bin/pint --dirty --format agent
  ```

- [ ] **Step 7: Commit**

  ```bash
  git add app/Http/Controllers/Proctor/SessionRosterController.php tests/Feature/Proctor/SessionRosterTimeEnforcementTest.php
  git commit -m "fix: block attendance and submission after exam end time"
  ```

---

### Task 9: Update frontend gate conditions in SessionRoster (Bugs 1.5 + 1.6)

**Files:**
- Modify: `resources/js/Pages/Proctor/SessionRoster.svelte`

**Background:** `canMarkAttendance` and `canLogSubmission` only check `session.status`. Bug 1.6: submission button never shows when status is `published` (session hasn't been manually started). Bug 1.5: no frontend guard when past end time (backend already enforces it, but UX should disable buttons too).

- [ ] **Step 1: Find the existing derived conditions**

  Open `resources/js/Pages/Proctor/SessionRoster.svelte`. Find these lines (around line 155–159):

  ```js
  const canMarkAttendance = $derived(
    session.status === 'published' || session.status === 'in_progress'
  );
  const canLogSubmission = $derived(session.status === 'in_progress');
  ```

- [ ] **Step 2: Replace with time-aware conditions**

  ```js
  const canMarkAttendance = $derived(
    (session.status === 'published' || session.status === 'in_progress')
    && !session.is_past_end
  );
  const canLogSubmission = $derived(
    (session.status === 'in_progress' ||
      (session.status === 'published' && session.is_within_window))
    && !session.is_past_end
  );
  ```

- [ ] **Step 3: Add an end-time notice to the template**

  In the template, find the `<div class="rounded-lg border border-border bg-card p-6">` section that contains the "Applicants" heading. Add a notice just above the table (after the search/button row):

  ```svelte
  {#if session.is_past_end}
    <div class="mt-3 rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">
      The exam window has ended. Attendance and submission actions are locked.
    </div>
  {/if}
  ```

- [ ] **Step 4: Verify manually**

  1. During a session that is `published` and within the exam time window: confirm "Log submission" button appears for present applicants.
  2. After end time (or set `end_time` to a past time in Tinker): confirm both attendance and submission buttons are gone and the notice appears.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Proctor/SessionRoster.svelte
  git commit -m "fix: enable submission during window for published sessions; lock both after end time"
  ```

---

### Task 10: Fix 403 on Exam Monitoring for Test Administrator (Bug 1.7) — STANDALONE

**Files:**
- Modify: `routes/web.php`

**Background:** The monitoring route lives inside a middleware group that excludes `test_administrator`. Moving it out and applying its own middleware with all four allowed roles fixes the 403.

> This task is intentionally isolated. Do not bundle it with other route changes.

- [ ] **Step 1: Find the monitoring route**

  Open `routes/web.php`. Find line ~107:

  ```php
  Route::middleware('role:super_admin,admin,proctor')->prefix('admin')->name('admin.')->group(function () {
      // ...
      Route::get('test-scheduling/monitoring', [ExamSessionController::class, 'monitoring'])->name('test-scheduling.monitoring');
      // ...
  });
  ```

- [ ] **Step 2: Move the monitoring route outside its current group**

  Remove the monitoring route line from that group and add it as a standalone route (place it just after the group closes):

  ```php
  Route::get('admin/test-scheduling/monitoring', [ExamSessionController::class, 'monitoring'])
      ->name('admin.test-scheduling.monitoring')
      ->middleware(['web', 'auth', 'role:super_admin,admin,proctor,test_administrator']);
  ```

  Make sure to import `ExamSessionController` at the top of `web.php` if not already present (check existing `use` statements — it should already be there).

- [ ] **Step 3: Verify the route is registered correctly**

  ```bash
  php artisan route:list --name=admin.test-scheduling.monitoring
  ```

  Expected output shows the route with the correct URI and middleware.

- [ ] **Step 4: Test manually**

  Log in as a user with `test_administrator` role. Navigate to `/admin/test-scheduling/monitoring`. Confirm no 403. Log in as `proctor` — also confirm access. Log in as a role not in the list (e.g. `staff`) — confirm 403.

- [ ] **Step 5: Commit**

  ```bash
  git add routes/web.php
  git commit -m "fix: allow test_administrator to access exam monitoring route"
  ```

---

### Task 11: Fix old /portal/login page references (Bug 1.8)

**Files:**
- Modify: `resources/js/Pages/Home.svelte`
- Modify: `resources/js/Pages/Portal/ForgotPassword.svelte`

**Background:** The combined login page is at `/login`. Two frontend files still link to `/portal/login` (the old dedicated applicant page). Only page links need changing — the backend POST endpoint at `/portal/login` stays intact (it is the form target for the applicant tab in `Auth/Login.svelte`).

- [ ] **Step 1: Fix Home.svelte — two references**

  Open `resources/js/Pages/Home.svelte`. Find and replace both occurrences:

  ```js
  // Line ~43 — button href
  href: '/portal/login'
  // Change to:
  href: '/login'
  ```

  ```svelte
  <!-- Line ~60 — Link component -->
  <Link href="/portal/login" ...>
  <!-- Change to: -->
  <Link href="/login" ...>
  ```

- [ ] **Step 2: Fix ForgotPassword.svelte**

  Open `resources/js/Pages/Portal/ForgotPassword.svelte`. Find line ~60:

  ```svelte
  <Link href="/portal/login" class="text-primary hover:underline">Back to sign in</Link>
  ```

  Change to:

  ```svelte
  <Link href="/login" class="text-primary hover:underline">Back to sign in</Link>
  ```

- [ ] **Step 3: Verify**

  1. Go to the home page — click "Applicant Portal" button. Confirm it opens `/login` (combined login page), not `/portal/login`.
  2. Go to `/portal/forgot-password` — click "Back to sign in". Confirm it goes to `/login`.
  3. Confirm the applicant login form on `/login` still works (it POSTs to `/portal/login` — the backend endpoint — which must stay intact).

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Pages/Home.svelte resources/js/Pages/Portal/ForgotPassword.svelte
  git commit -m "fix: update portal/login page links to /login across Home and ForgotPassword"
  ```

---

## Final Verification

After all tasks are complete, run the full test suite:

```bash
php artisan test --compact
```

Expected: all tests pass. If any fail, investigate before marking Phase 1 complete.

Then do a manual smoke test of the two critical flows:

**AI Scheduler flow:**
1. Open AI Scheduler modal — no console errors
2. Type a message, send — reply appears
3. Click "Generate schedule" — preview table shows room, date, time (not undefined)
4. Confirm only the preview table shows; no duplicate table in chat

**Proctor session flow:**
1. Open a session roster during the exam time window — "Log submission" button visible for present applicants
2. Open a session roster after end time — both attendance and submission buttons gone; notice visible
3. Visit `/admin/test-scheduling/monitoring` as Test Administrator — no 403
