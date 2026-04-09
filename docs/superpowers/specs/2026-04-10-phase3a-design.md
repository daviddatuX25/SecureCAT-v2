# Phase 3A — Terminology & Removals Design

**Date:** 2026-04-10
**Project:** SecureCAT-v2
**Scope:** Cleanup pass — label renames, dead code removal, and one full model rename.
**No new features.** No business logic changes except where explicitly noted.

---

## Overview

Phase 3A addresses 7 items from the QA findings. All items are grouped into 3 execution batches ordered by risk. Each batch ships as its own atomic git commit.

| Batch | Items | Risk |
|-------|-------|------|
| 1 — UI Label Renames | 3.4, 3.5, 3.12 | Low |
| 2 — Code Removals | 3.1, 3.11, 3.17 | Medium |
| 3 — Season → AcademicYear Rename | 3.2 | High (isolated) |

---

## Batch 1 — UI Label Renames

### 3.4 — "Domains" → "Aptitude Areas"

**What:** The grading and test admin pages still display "domains" as a label (e.g., "3 / 6 domains"). The model is already named `AptitudeArea` — this is a display-only fix.

**Files to touch:**
- `resources/js/Pages/Grading/ScoreInput.svelte`
- `resources/js/Pages/Grading/Session.svelte`
- `resources/js/Pages/Admin/TestAdmin/Index.svelte`
- `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte`

**Change:** Text search for `domain` (case-insensitive) in those files. Replace all visible labels with `aptitude area` / `Aptitude Areas`. No backend changes.

---

### 3.5 — "Roster" → "Examinees"

**What:** The exam monitoring and proctor sessions pages use "roster" in headings and labels. Rename to "examinees" in all visible text.

**Files to touch:**
- `resources/js/Components/SessionRoster.svelte` — label text inside the component
- `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte`
- `resources/js/Pages/Admin/TestAdmin/Index.svelte`

**Constraint:** The component filename `SessionRoster.svelte` and its import references stay unchanged to avoid a cascade of import renames. Only the user-visible text is changed.

---

### 3.12 — Applicant Portal Status Labels

**What:** Labels-only cleanup on the applicant portal dashboard. No status enum changes, no backend changes.

**File to touch:**
- `resources/js/Pages/Portal/Dashboard.svelte`

**Changes:**
- Remove the "Consultation released" display block and any related conditional (`consultation.status === 'released'`) that renders a card or label for the consultation status.
- Rename any instance of "Results available" → "Results released" in visible text.

---

## Batch 2 — Code Removals

### 3.1 — Remove `incomplete_documents` Status

**What:** The `incomplete_documents` status is no longer needed — `pending` covers the same case. Remove all references from code. No data migration (demo DB is fresh).

**Files to touch:**
- `app/Http/Controllers/ApplicationController.php` — remove from status filter list, validation enum
- `app/Policies/ApplicationPolicy.php` — remove from any gate that checks `incomplete_documents`
- `resources/js/Pages/Applications/Index.svelte` — remove from `statusVariant()`, `statusLabel()`, filter dropdown `<option>`, and action conditionals (e.g., `if app.status === 'pending' || app.status === 'incomplete_documents'`)
- `resources/js/Pages/Applications/Show.svelte` — same pattern

**Result:** Only valid statuses are `pending`, `accepted`, `dismissed`.

---

### 3.11 — Remove Score Release Date

**What:** The `score_release_date` field on the exam scheduling form is redundant — the release module handles this separately. Remove the field from the form and from any applicant-facing display.

**Files to touch:**
- `resources/js/Pages/Admin/TestScheduling/Show.svelte` — remove the `score_release_date` form field
- `app/Http/Controllers/Admin/ExamSessionController.php` — remove from `store`/`update` handling
- `app/Models/ExamSession.php` — remove from `$fillable` and `$casts`
- `resources/js/Pages/Portal/Dashboard.svelte` — remove any display of `score_release_date` to the applicant

**Note:** Do NOT drop the DB column in this batch — leave it nullable in the DB for safety. The column can be dropped in a future cleanup migration once confirmed unused.

---

### 3.17 — Remove QR Scan Feature from Attendance

**What:** The QR scan option on the proctor session page is removed entirely.

**Backend — files to touch:**
- `routes/web.php` — remove `POST /proctor/sessions/{exam_session}/scan-attendance` route
- `app/Http/Controllers/Proctor/SessionRosterController.php` — remove `scanAttendance()` method
- `app/Http/Requests/Proctor/ScanAttendanceRequest.php` — delete this file

**Frontend — files to touch:**
- `resources/js/Pages/Proctor/SessionRoster.svelte`:
  - Remove `scanMode` state variable
  - Remove QR input field and "Scan for attendance" toggle button
  - Remove `handleScan()` / scan-related handler functions
  - Remove any `scanMode` conditionals in the template

---

## Batch 3 — Season → AcademicYear Full Rename

### 3.2 — Full Rename: `Season` → `AcademicYear`

**What:** The `Season` model, DB table, routes, controllers, policies, and Svelte pages are all renamed to use `AcademicYear` / `academic_year` terminology. This ships as a single atomic commit.

**Database migration (new migration file):**
- Rename table `seasons` → `academic_years`
- Rename FK column `season_id` → `academic_year_id` on `applications` table
- Rename FK column `season_id` → `academic_year_id` on `exam_sessions` table
- Update any indexes or constraints that reference `season_id`

**PHP — files to rename/update:**
- `app/Models/Season.php` → `app/Models/AcademicYear.php` (class name, namespace)
- `app/Policies/SeasonPolicy.php` → `app/Policies/AcademicYearPolicy.php`
- `app/Http/Controllers/Admin/SeasonController.php` (if exists) → `AcademicYearController.php`
- `app/Models/Application.php` — update `season()` relation → `academicYear()`, FK column ref
- `app/Models/ExamSession.php` — same relation update
- All other models/controllers that reference `Season::class`, `season_id`, `Season::active()`
- Update `AuthServiceProvider` / `AppServiceProvider` policy registration
- Update `RoleSeeder` / any seeders referencing `Season`

**Routes (`routes/web.php`):**
- Rename route group prefix `admin/seasons` → `admin/academic-years`
- Rename named route group `admin.seasons.*` → `admin.academic-years.*`

**Svelte pages:**
- Rename directory `resources/js/Pages/Admin/Seasons/` → `resources/js/Pages/Admin/AcademicYears/`
- Rename files inside: `Index.svelte`, `Create.svelte`, `Edit.svelte`
- Update all internal labels from "Season" / "Add Season" → "Academic Year" / "Add Academic Year"
- Update Inertia route references (`route('admin.academic-years.*')`)
- Update `AuthenticatedLayout.svelte` nav link if it points to the seasons route

**Verification after Batch 3:**
- Run `php artisan test --compact` — all tests must pass
- Run `php artisan route:list | grep season` — must return empty
- Run `grep -r "season\|Season" app/ resources/ --include="*.php" --include="*.svelte"` — review remaining hits for legitimacy (e.g., comments, git history)

---

## Constraints & Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| 3.1 — data migration | None | Demo DB is fresh |
| 3.2 — rename scope | Full (model + DB + routes + UI) | User-confirmed |
| 3.5 — component filename | Keep `SessionRoster.svelte` | Avoids import cascade |
| 3.11 — DB column drop | Deferred | Safety; drop in future cleanup |
| 3.12 — status enum | Labels only | User-confirmed |

---

## Execution Order

```
Batch 1 (UI labels) → Batch 2 (removals) → Batch 3 (Season rename)
     commit 1              commit 2               commit 3
```

Batch 3 must be done last — it has the highest blast radius and benefits from having Batch 1 and 2 already stable and tested.

---

## Out of Scope for 3A

The following Phase 3 items are deferred to the **Phase 3B** spec:
- 3.3 Academic Year toggle (feature, belongs with AcademicYear post-rename)
- 3.6 Test Administrator role rename and scope fix
- 3.7–3.10 Bulk actions (applications + proctor sessions)
- 3.13–3.15 AI Scheduler adjustments
- 3.16 Email expiry ENV override
