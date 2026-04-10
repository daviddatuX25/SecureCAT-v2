# Phase 3B — Logic Corrections & Feature Adjustments (Part 2)

**Date:** 2026-04-10
**Project:** SecureCAT-v2
**Scope:** Role slug rename, bulk actions, application lifecycle additions, AI scheduler UX cleanup, email expiry ENV, academic year toggle.
**No new UI frameworks.** Follow existing patterns (Rooms toggle, Applications actions, ScheduleAssistantPanel).

---

## Overview

Phase 3B covers the 10 items deferred from Phase 3A, organized into 4 atomic commits ordered by risk.

| Batch | Items | Risk |
|-------|-------|------|
| 1 — AI Scheduler UX + Email Expiry | 3.13, 3.14, 3.15, 3.16 | Low — frontend + .env only |
| 2 — Application & Proctor Bulk Actions | 3.7, 3.8, 3.9, 3.10 | Medium — new endpoints |
| 3 — Role Slug Rename | 3.6 | Medium — data migration + grep replace |
| 4 — Academic Year Toggle | 3.3 | Low-Medium — depends on 3A Batch 3 |

Batch 4 ships **only after Phase 3A Batch 3** (Season → AcademicYear rename) is merged and stable.

---

## Batch 1 — AI Scheduler UX + Email Expiry

### 3.13 + 3.15 — Collapse Inline Info into Beta Icon

**What:** `ScheduleAssistantPanel.svelte` currently renders two overlapping info blocks:
1. A `<p>` at the top of the panel (describes usage)
2. A `Card.Description` below the heading ("Chat with the assistant... Generate schedule unlocks...")

Both are removed. In their place, add a `[β]` badge next to the "AI Exam Scheduler" `Dialog.Title` in `Index.svelte`. Clicking/hovering the badge shows a tooltip or `Popover` with:

> "Beta feature — generates exam session proposals from natural language. Future versions will support editing existing schedules."

The `Dialog.Description` is replaced with a single terse accessibility-only line, e.g.:
> "Describe your scheduling needs and generate a session proposal."

**Files to touch:**
- `resources/js/Pages/Admin/TestScheduling/Index.svelte` — add beta badge + popover in `Dialog.Header`; update `Dialog.Description`
- `resources/js/Components/ScheduleAssistantPanel.svelte` — remove the top `<p>` info block and the `Card.Description` info block

---

### 3.14 — Hide "Generate Schedule" Until Preview Exists

**What:** The "Generate schedule" button is currently always visible in the action bar (may be disabled but still present). It should be **hidden** (not just disabled) until `parsedSchedule` is non-null — i.e. the AI returned valid JSON that was successfully mapped to the preview table.

**Change:** In `ScheduleAssistantPanel.svelte`, wrap the Generate button in `{#if parsedSchedule}` instead of rendering it unconditionally. Before any AI reply produces a valid schedule, the action bar shows only the send button.

**Files to touch:**
- `resources/js/Components/ScheduleAssistantPanel.svelte`

---

### 3.16 — Email Expiry ENV Override

**What:** Token expiry durations are hardcoded. Add two ENV variables so expiry can be shortened for demo/testing.

**Backend — new config keys in `config/auth.php`:**
```php
'setup_token_expires_hours' => (int) env('SETUP_TOKEN_EXPIRES_HOURS', 72),
'reset_token_expires_hours' => (int) env('RESET_TOKEN_EXPIRES_HOURS', 24),
```

**Usage:**
- `ApplicationController.php` line 328: `now()->addHours(72)` → `now()->addHours(config('auth.setup_token_expires_hours', 72))`
- `PortalAuthController.php` (password reset upsert): replace hardcoded expiry with `config('auth.reset_token_expires_hours', 24)`

**`.env.example` additions:**
```
# Token expiry (hours). Lower for demo to simulate expiry flows.
SETUP_TOKEN_EXPIRES_HOURS=72
RESET_TOKEN_EXPIRES_HOURS=24
```

**Files to touch:**
- `config/auth.php`
- `app/Http/Controllers/ApplicationController.php`
- `app/Http/Controllers/PortalAuthController.php`
- `.env.example`

---

## Batch 2 — Application & Proctor Bulk Actions

### 3.7 — /applications: Bulk Accept / Dismiss

**What:** Add row selection and a bulk action bar to the applications list.

**Frontend — `resources/js/Pages/Applications/Index.svelte`:**
- Add a checkbox column as the first column in the table header and each row
- `selectedIds` state (array of application IDs)
- "Select all" checkbox in header toggles all visible rows
- When `selectedIds.length > 0`, show a sticky bulk action bar (above or below the table) with:
  - `Accept selected` button → `router.post('/applications/bulk-accept', { ids: selectedIds })`
  - `Dismiss selected` button → `router.post('/applications/bulk-dismiss', { ids: selectedIds })`
- After action completes, clear `selectedIds`

**Backend — `app/Http/Controllers/ApplicationController.php`:**
- `bulkAccept(Request $request)`: validate `ids` array, update `status = 'accepted'` where `id IN ids AND status = 'pending'`. Non-pending rows are silently skipped.
- `bulkDismiss(Request $request)`: same pattern, `status = 'dismissed'`.
- Both use `$this->authorize('update', Application::class)` (or the existing policy gate).

**Routes — `routes/web.php`:**
```php
Route::post('/applications/bulk-accept', [ApplicationController::class, 'bulkAccept']);
Route::post('/applications/bulk-dismiss', [ApplicationController::class, 'bulkDismiss']);
```

---

### 3.8 — /applications: Re-open Dismissed

**What:** Allow moving a dismissed application back to `pending`. Gated: only if the academic year's application window is still open (i.e. `application_end_date` is today or in the future).

**Frontend — `resources/js/Pages/Applications/Index.svelte` (and `Show.svelte`):**
- Add "Re-open" action in the per-row actions dropdown, visible only when `app.status === 'dismissed'`
- `router.put('/applications/{id}/reopen')`

**Backend — `app/Http/Controllers/ApplicationController.php`:**
```php
public function reopen(Application $application): RedirectResponse
{
    $this->authorize('update', $application);

    if (! $application->season?->isApplicationWindowOpen()) {
        return back()->withErrors(['error' => 'The application window is closed.']);
    }

    $application->update(['status' => 'pending']);
    return back()->with('success', 'Application re-opened.');
}
```

**Note:** After Phase 3A Batch 3 renames `season` → `academicYear`, update the relation name here to `$application->academicYear`.

**Route:**
```php
Route::put('/applications/{application}/reopen', [ApplicationController::class, 'reopen']);
```

---

### 3.9 — /applications: Delete Application

**What:** Allow deletion of any application (any status) with a confirmation prompt. Hard delete — no soft delete needed for demo scope.

**Frontend — `resources/js/Pages/Applications/Index.svelte` (and `Show.svelte`):**
- Add "Delete" action in the per-row actions dropdown (all statuses)
- Show a `confirm('Delete this application? This cannot be undone.')` before calling `router.delete('/applications/{id}')`

**Backend — `app/Http/Controllers/ApplicationController.php`:**
```php
public function destroy(Application $application): RedirectResponse
{
    $this->authorize('delete', $application);
    $application->delete();
    return redirect('/applications')->with('success', 'Application deleted.');
}
```

**Policy — `app/Policies/ApplicationPolicy.php`:**
- `delete()`: allow `super_admin` and `admin`. Deny all others (including `registrar_administrator`, which per 3.6 must NOT manage applications).

**Route:** Already exists as `Route::delete('/applications/{application}', ...)` — verify it's wired to `destroy`.

---

### 3.10 — Proctor Sessions: Bulk Attendance & Submission

**What:** Add row selection and bulk action support to the proctor session roster page.

**Frontend — `resources/js/Pages/Proctor/SessionRoster.svelte`:**
- Add checkbox column to the applicants table
- `selectedIds` state (array of applicant IDs from the roster)
- "Select all" checkbox in header
- When selections exist, show a bulk action bar with:
  - `Mark present` → `router.post('/proctor/sessions/{id}/bulk-attendance', { applicant_ids: selectedIds, status: 'present' })`
  - `Mark absent` → same, `status: 'absent'`
  - `Mark submitted` → same, `status: 'submitted'`
- Actions only enabled when session status is `in_progress` (same guard as individual actions)

**Backend — `app/Http/Controllers/Proctor/SessionRosterController.php`:**
```php
public function bulkAttendance(Request $request, ExamSession $examSession): RedirectResponse
{
    $data = $request->validate([
        'applicant_ids' => ['required', 'array'],
        'applicant_ids.*' => ['integer'],
        'status' => ['required', Rule::in(['present', 'absent', 'submitted'])],
    ]);

    // Update session_applicants where applicant_id IN ids and session matches
    SessionApplicant::where('exam_session_id', $examSession->id)
        ->whereIn('applicant_id', $data['applicant_ids'])
        ->update(['attendance_status' => $data['status']]);

    return back()->with('success', 'Bulk update applied.');
}
```

**Route — `routes/web.php`:**
```php
Route::post('/proctor/sessions/{exam_session}/bulk-attendance', [SessionRosterController::class, 'bulkAttendance']);
```

---

## Batch 3 — Role Slug Rename

### 3.6 — `test_administrator` → `registrar_administrator`

**What:** Full rename of the role slug and display name. Ships as one atomic commit.

**Migration (new file):**
```php
DB::table('roles')
    ->where('name', 'test_administrator')
    ->update([
        'name' => 'registrar_administrator',
        'display_name' => 'Registrar Administrator',
        'description' => 'Guidance office, inputs scores and releases results.',
    ]);
```

**PHP — grep-replace `test_administrator` → `registrar_administrator` in:**
- `app/Policies/*.php` — all `hasAnyRole([..., 'test_administrator'])` calls
- `app/Http/Controllers/**/*.php` — any role checks
- `app/Http/Middleware/*.php` — any role middleware
- `database/seeders/RoleSeeder.php` — seed row name
- `database/seeders/DefenseDemoSeeder.php` — role string
- `database/seeders/DemoDashboardSeeder.php` — role string
- `database/seeders/DatabaseSeeder.php` — comment
- `app/Console/Commands/DemoSetupCommand.php` — role string
- `database/migrations/2026_03_02_*_merge_grader_counselor_into_test_administrator.php` — comment only, leave existing migration intact

**Svelte — `resources/js/Layouts/AuthenticatedLayout.svelte`:**
- All `roles: [..., 'test_administrator']` nav item arrays → `'registrar_administrator'`

**Verification after Batch 3:**
```bash
php artisan test --compact
php artisan route:list
grep -r "test_administrator" app/ database/ resources/ --include="*.php" --include="*.svelte"
# Must return empty (except the old migration comment and git history)
```

---

## Batch 4 — Academic Year Toggle

### 3.3 — Toggle for Active Academic Year

**Prerequisite:** Phase 3A Batch 3 (Season → AcademicYear full rename) must be merged first.

**What:** The current Seasons/Index.svelte has a one-way "Set active" button. Replace with a two-way toggle (mirrors Rooms pattern). Also gate the applicant `/apply` page when no academic year is active.

**Backend — `app/Http/Controllers/Admin/AcademicYearController.php`:**
Add a `deactivate()` method:
```php
public function deactivate(AcademicYear $academicYear): RedirectResponse
{
    $this->authorize('update', $academicYear);
    $academicYear->update(['is_active' => false]);
    return back()->with('success', 'Academic year deactivated. Applications are now closed.');
}
```

The existing `activate()` already deactivates all others before setting the target active — no change needed there.

**Route — `routes/web.php`:**
```php
Route::post('/admin/academic-years/{academic_year}/deactivate', [AcademicYearController::class, 'deactivate']);
```

**Frontend — `resources/js/Pages/Admin/AcademicYears/Index.svelte`:**
- Replace the "Set active" `Button` with a toggle pattern matching Rooms:
  - If `is_active`: render an active toggle button that POSTs to `/admin/academic-years/{id}/deactivate`
  - If not `is_active`: render an inactive toggle button that POSTs to `/admin/academic-years/{id}/activate`
- Show a warning banner at the top of the page when no academic year is active:
  > "No academic year is currently active. Applications are closed until one is activated."

**Frontend — `resources/js/Pages/Applications/Apply.svelte`:**
- The controller already passes `active_season_id` (will be `active_academic_year_id` after 3A). If null, instead of rendering the application form, render:
  > "No admission period is currently open. Contact the registrar's office for details."
- This is a conditional render — no routing change.

**Backend — `app/Http/Controllers/ApplicationController.php` (`create` method):**
- Pass `active` boolean or the active academic year to the Inertia render so the Svelte page can branch.

---

## Constraints & Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| 3.9 — delete guard | None (any status, with confirm) | User confirmed |
| 3.9 — soft vs hard delete | Hard delete | Demo scope, no audit trail needed |
| 3.8 — reopen gate | `isApplicationWindowOpen()` on academic year | Window date is the authoritative source |
| 3.7 — bulk skip behavior | Non-pending silently skipped | Avoids error noise on mixed selections |
| 3.10 — bulk guard | `in_progress` status only | Matches existing individual action guards |
| 3.6 — old migration | Leave intact, comment only | Migration history must not be rewritten |
| 3.3 — "no active year" message | "No admission period is currently open. Contact the registrar's office for details." | User confirmed |
| 3.4 — Batch 4 dependency | Ships after Phase 3A Batch 3 | Rename cascade must settle first |

---

## Execution Order

```
Batch 1 (AI UX + ENV)  →  Batch 2 (Bulk actions)  →  Batch 3 (Role rename)  →  [wait for 3A]  →  Batch 4 (AY toggle)
     commit 1                  commit 2                   commit 3                                       commit 4
```

Batch 3 must run after Batch 2 — the bulk action policy gates reference the role name and must reference `registrar_administrator` after the rename. Write Batch 2 with `test_administrator` and update in Batch 3, or write Batch 2 already using `registrar_administrator` if Batch 3 runs immediately after.

**Recommended:** Ship Batches 1–3 in sequence in the same session. Batch 4 in a separate session after 3A is confirmed merged.

---

## Out of Scope for 3B

- 3A items (already specced separately)
- Phase 4 demo preparation
- Phase 5 upcoming features (notifications, toast system, bulk import, etc.)
