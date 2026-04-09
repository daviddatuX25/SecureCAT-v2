# Phase 1: Critical Bugs — Design Spec
**Date:** 2026-04-10  
**Project:** SecureCAT-v2  
**Approach:** Option B — Two grouped batches (AI Scheduler frontend + Backend/Routing)

---

## Scope

8 blocking bugs from `changes.txt` Phase 1. No new features, no refactors outside the fix boundary.

---

## Batch 1: AI Scheduler Frontend Fixes

Files touched: `ScheduleAssistantPanel.svelte`, `TestScheduling/Show.svelte`

### 1.1a — Infinite reactive loop in ScheduleAssistantPanel

**Root cause:** `$effect` reads `messages.length` and writes `messages`. When `initialMessages` is empty, this loops infinitely (`effect_update_depth_exceeded`).

**Fix:** Replace `$effect` with direct `$state` initialization.

```js
// Before
let messages = $state([]);
$effect(() => {
  if (messages.length === 0) messages = [...(initialMessages ?? [])];
});

// After
let messages = $state([...(initialMessages ?? [])]);
```

### 1.1b — TypeError on Show.svelte:104

**Root cause:** `releaseDateForm.transform(...)` calls `.transform` on the Svelte store wrapper (not the form object). It's also a no-op (identity transform).

**Fix:** Remove the line entirely. The `$releaseDateForm.put(...)` below it is correct.

### 1.2 — Generate Schedule button unlocks too early

**Root cause:** After fixing 1.1, the button's existing logic (`canGenerate = openrouter_configured && hasReplyThisSession`) is sound. No logic change needed. Add a visible hint label below the button when it is disabled, so the user knows why.

### 1.3 — JSON fields not mapping to preview table

**Root cause:** AI may return varied field names (`room_id`, `roomId`, `room.id`, `room.name`, `session_date`, `startTime`, etc.). `getScheduleRows()` only handles one variant.

**Fix — Frontend:** Add `normalizeSession()` that tries multiple field aliases before falling back:

```js
function normalizeSession(s) {
  return {
    exam_session_id: s.exam_session_id ?? s.session_id ?? null,
    room_id: s.room_id ?? s.roomId ?? s.room?.id ?? null,
    room_name: s.room_name ?? s.room?.name ?? null,
    date: s.date ?? s.session_date ?? s.exam_date ?? null,
    start_time: s.start_time ?? s.startTime ?? s.time_start ?? null,
    end_time: s.end_time ?? s.endTime ?? s.time_end ?? null,
    applicant_ids: s.applicant_ids ?? s.applicantIds ?? s.applicants ?? [],
  };
}
```

Room display: `roomMap[norm.room_id]?.name ?? norm.room_name ?? 'Unknown room'`

**Fix — Backend:** In `ScheduleAssistantController`, normalize the parsed structured schedule JSON before returning it in the response — coerce field names to the canonical set and validate that `room_id` values exist in the rooms table.

### 1.4 — Duplicate table in chat

**Root cause:** When structured schedule is received, it's stored in both `msg.schedule` (renders a table in the chat bubble) and `structuredSchedule` (renders the preview table below). Both show simultaneously.

**Fix:** Only render the in-chat table when no preview exists yet:

```svelte
{#if msg.schedule?.sessions?.length && !structuredSchedule}
  <!-- in-chat table -->
{/if}
```

---

## Batch 2: Backend / Routing Fixes

### 1.5 + 1.6 — Time-window enforcement (combined)

**Root cause:** Session status never transitions from `published` to `in_progress` automatically. Frontend gates submission on `status === 'in_progress'`, so `canLogSubmission` is always false for published sessions even during the exam window.

**Backend changes:**

1. Compute `is_within_window` and `is_past_end` in `ProctorSessionController@show` (and any other controller that serves `SessionRoster`):

```php
$now = now();
$sessionDate = Carbon::parse($session->date);
$start = $sessionDate->copy()->setTimeFromTimeString($session->start_time);
$end = $session->end_time
    ? $sessionDate->copy()->setTimeFromTimeString($session->end_time)
    : null;

$isWithinWindow = $now->greaterThanOrEqualTo($start)
    && ($end === null || $now->lessThanOrEqualTo($end));
$isPastEnd = $end !== null && $now->greaterThan($end);
```

2. Include these in the Inertia response as `is_within_window` and `is_past_end`.

3. Enforce in `attendance` and `submission` endpoints: if `is_past_end`, return HTTP 422 with message `"The exam window has ended."`.

**Frontend changes (`SessionRoster.svelte`):**

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

Show a read-only notice when `session.is_past_end === true`: "The exam window has ended. Attendance and submission actions are locked."

### 1.7 — 403 on Exam Monitoring for Test Administrator

**Root cause:** Monitoring route is inside `role:super_admin,admin,proctor` middleware group. `test_administrator` is excluded.

**Fix:** Extract the monitoring route from that group and apply its own middleware that includes `test_administrator`:

```php
Route::get('test-scheduling/monitoring', [ExamSessionController::class, 'monitoring'])
    ->name('admin.test-scheduling.monitoring')
    ->middleware(['auth', 'role:super_admin,admin,proctor,test_administrator']);
```

> **Note:** Treat as a focused standalone task — do not bundle with other route changes.

### 1.8 — Old /portal/login page references

**Root cause:** `Home.svelte` and `Portal/ForgotPassword.svelte` link to `/portal/login` (the old dedicated applicant login page). The combined login is now at `/login`.

The `/portal/login` backend POST endpoint stays — it is the correct form submission target for the applicant tab in `Auth/Login.svelte`.

**Changes:**

| File | Line | Before | After |
|---|---|---|---|
| `Home.svelte` | 43 | `href="/portal/login"` | `href="/login"` |
| `Home.svelte` | 60 | `href="/portal/login"` | `href="/login"` |
| `Portal/ForgotPassword.svelte` | 60 | `href="/portal/login"` | `href="/login"` |

---

## Constraints

- No changes to the exam session status machine (no cron jobs, no status auto-transitions in Phase 1)
- No changes to role definitions or role middleware internals — only route guard additions
- `/portal/login` POST route remains intact

---

## Success Criteria

- [ ] AI scheduler modal opens without console errors on first load
- [ ] Preview table shows correct room, date, and time from AI JSON
- [ ] No duplicate table rendered in the chat
- [ ] Proctors can mark attendance and log submission during the exam window
- [ ] Attendance/submission actions are blocked after end time
- [ ] Test Administrator can access exam monitoring without 403
- [ ] Home page and Forgot Password link to `/login`, not `/portal/login`
