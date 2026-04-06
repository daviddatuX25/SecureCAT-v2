# Test Administrator Session Roster — Design Spec

**Date:** 2026-04-06
**Project:** SecureCAT-v2

---

## Context

Test Administrator currently has access to `Admin/ExamSessions` CRUD (create, edit, index, show, monitoring) but lacks the full session management capability available to Proctors — the ability to start/close sessions, scan QR codes for attendance and submission, and manage applicant attendance/submission states during an active session.

The goal is to give Test Administrators a complete Proctor-style session management view, with greater privileges than regular Proctors (override schedule timing, room reassignment, applicant removal/addition during session), plus monitoring/analytics.

---

## Architecture

### Navigation — `AuthenticatedLayout.svelte`

Add a "My Sessions" nav item under the "Guidance" section for `test_administrator`, `proctor`, `admin`, and `super_admin` roles:

```js
{ href: '/admin/test-admin/sessions', label: 'My Sessions', icon: Calendar, roles: ['super_admin', 'admin', 'test_administrator', 'proctor'] }
```

Test Administrators see both "My Sessions" and "Exam Scheduling" (for admin CRUD tasks). Proctors see only "My Sessions" under Guidance.

---

### Routing — `routes/web.php`

New route group for Test Admin session management:

```php
Route::middleware('role:super_admin,admin,test_administrator')
    ->prefix('admin/test-admin')
    ->name('admin.test-admin.')
    ->group(function () {
        Route::get('sessions', [ExamSessionController::class, 'testAdminIndex'])
            ->name('sessions.index');
        Route::get('sessions/{exam_session}/roster', [ExamSessionController::class, 'testAdminRoster'])
            ->name('sessions.roster');
    });
```

Additionally, add `test_administrator` to the existing Proctor middleware so Test Admins can also access `/proctor/sessions/{id}` as a second path:

```php
Route::middleware('role:super_admin,admin,proctor,test_administrator')
    ->prefix('proctor')
    ->name('proctor.')
    ->group(function () { ... });
```

---

### Shared Component — `resources/js/Components/SessionRoster.svelte`

Extract session management logic from `Proctor/SessionRoster.svelte` into a reusable component.

**Props:**
- `session` — exam session object
- `applicants` — assigned applicants with attendance/submission states
- `stats` — aggregate counts (total, present, absent, pending, submitted)
- `permissions` — object dictating which actions are enabled

**Permission-driven rendering:**

| Permission | Proctor | Test Admin | Admin | Super Admin |
|---|---|---|---|---|
| `canStart` | yes | yes | yes | yes |
| `canClose` | yes | yes | yes | yes |
| `canMarkAttendance` | yes | yes | yes | yes |
| `canLogSubmission` | yes | yes | yes | yes |
| `canBulkSubmit` | yes | yes | yes | yes |
| `canOverrideSchedule` | no | yes | yes | yes |
| `canRemoveApplicant` | no | yes | yes | yes |
| `canReassignRoom` | no | yes | yes | yes |
| `canAddApplicant` | no | yes | yes | yes |
| `showAnalytics` | no | yes | yes | yes |

The component renders conditionally based on these permissions — no role checking inside the component, just boolean flags passed from the parent/page.

**All features from `Proctor/SessionRoster.svelte` are preserved:**
- Session info header (date, time, room, status, started/closed timestamps)
- Start / Close session buttons
- Stats bar (total, present, absent, pending, submitted)
- Applicant table with search
- QR scanner for attendance and submission
- Manual Mark Present / Mark Absent / Log submission actions
- Bulk "mark all present as submitted"
- Error/success flash handling

**Additional features for Test Admin+:**
- "Remove applicant" action per row (Test Admin, Admin, Super Admin)
- "Override schedule" hint when outside start window
- Analytics panel (stats, per-session history)

---

### Page: Test Admin Roster — `resources/js/Pages/Admin/TestAdmin/Roster.svelte`

```svelte
<AuthenticatedLayout>
  <SessionRoster {session} {applicants} {stats} permissions={fullPermissions} />
</AuthenticatedLayout>
```

Canonical URL: `/admin/test-admin/sessions/{id}/roster`

Full permissions passed to the component.

---

### Page: Test Admin Index — `resources/js/Pages/Admin/TestAdmin/Index.svelte`

Session list for Test Admin. Shows sessions assigned to the logged-in Test Admin. Admin/Super Admin see all sessions.

- Lists sessions with date, time, room, status, assigned applicant count
- "Open roster" action per row
- "Create session" action (Test Admin can create sessions via existing Admin flow)

---

### Controller — `ExamSessionController`

**`testAdminIndex(Request $request)`**
- Query exam sessions
- If `test_administrator` role: filter to sessions where user is assigned as proctor
- If `admin` or `super_admin` role: return all sessions
- Return paginated sessions with session metadata
- Pass `view='test-admin'` to Inertia render

**`testAdminRoster(ExamSession $exam_session, Request $request)`**
- Authorize: user must have `test_administrator`, `admin`, or `super_admin` role AND (is assigned as proctor OR is admin/super_admin)
- Load applicants with attendance/submission data
- Compute stats
- Build `fullPermissions` object (all flags true)
- Return `Inertia::render('Admin/TestAdmin/Roster', [...])`

---

### Show.svelte — "Open Roster" Link

Add roster access button to `Admin/ExamSessions/Show.svelte` for Test Admin and Admin:

```svelte
{#if hasRosterAccess}
  <Link href="/admin/test-admin/sessions/{session.id}/roster" class="...">
    Open roster
  </Link>
{/if}
```

Where `hasRosterAccess` = `test_administrator`, `admin`, or `super_admin` role.

---

## Data Flow

```
Test Admin clicks "My Sessions"
    → GET /admin/test-admin/sessions
    → ExamSessionController@testAdminIndex
    → Filter sessions by assignment
    → Admin/TestAdmin/Index.svelte

Test Admin clicks session row "Open roster"
    → GET /admin/test-admin/sessions/{id}/roster
    → ExamSessionController@testAdminRoster
    → Load session + applicants + stats + fullPermissions
    → Admin/TestAdmin/Roster.svelte
    → <SessionRoster {session} {applicants} {stats} permissions={fullPermissions} />

QR scan / Mark attendance / Log submission
    → Existing Proctor API endpoints (/proctor/sessions/{id}/...)
    → router.reload() after success
```

---

## Summary of Changes

| File | Change |
|---|---|
| `routes/web.php` | Add `test_administrator` to Proctor middleware; add new Test Admin route group |
| `ExamSessionController` | Add `testAdminIndex` and `testAdminRoster` methods |
| `resources/js/Components/SessionRoster.svelte` | New shared component (extracted from Proctor/SessionRoster) |
| `resources/js/Pages/Admin/TestAdmin/Index.svelte` | New page — session list for Test Admin |
| `resources/js/Pages/Admin/TestAdmin/Roster.svelte` | New page — session roster with full permissions |
| `resources/js/Pages/Admin/ExamSessions/Show.svelte` | Add "Open roster" button for authorized roles |
| `resources/js/Layouts/AuthenticatedLayout.svelte` | Add "My Sessions" nav item |

---

## Out of Scope

- New database tables or columns
- Changes to applicant assignment API (already exists)
- Proctor Dashboard enhancement (placeholder stub remains as-is)
- Changes to Exam Session CRUD (Create, Edit, Index already functional)