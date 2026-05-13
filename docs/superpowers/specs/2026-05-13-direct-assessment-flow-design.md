# Direct Assessment Flow — Design Spec

**Feature:** Walk-in / Direct Exam Session (bypass scheduling)
**Date:** 2026-05-13
**Status:** Approved — all open questions resolved, ready for implementation
**Decision:** Extend `ExamSession` with a `type` column. No schema surgery on `GradingSession` or `ApplicantScore`.

---

## 1. Problem Statement

The current pipeline is **strictly linear** and all steps are mandatory:

```
Application (pending)
  → [Staff accepts / applicant self-applies]
  → Application (accepted)
  → Applicant record created
  → ExamSession created + published
  → Applicant assigned to ExamSession
  → GradingSession opened (from ExamSession)
  → Scores encoded per AptitudeArea
  → GradingSession finalized
```

**Two use-cases are currently impossible without this feature:**

1. **Small intake / walk-in scenario** — admin just wants to create applicants and immediately encode their scores without touching Exam Scheduling at all.
2. **Offline encoding** — exam was administered on paper in the field; staff needs to retrospectively enter results without creating a real scheduled session.

---

## 2. Chosen Approach: `ExamSession.type` Enum Extension

### Why this approach wins

| Approach | Schema disruption | Existing code breaks | Verdict |
|---|---|---|---|
| Add `type` enum to `ExamSession` | 1 column, 1 migration | Zero | ✅ **Chosen** |
| Nullable `exam_session_id` on `GradingSession` | Nullable FK + null guards everywhere | High | ❌ Rejected |
| Separate `DirectGradingSession` model | New table + parallel logic | Medium | ❌ Rejected |
| Auto-phantom session behind the scenes | None visible | Zero (but opaque) | ❌ Rejected — bad auditability |

`GradingSession`, `ApplicantScore`, `GradingSessionService::openForExamSession()`, and all reporting queries remain **unchanged**. A "direct" session is just an `ExamSession` with a different `type` — all existing relationships hold.

---

## 3. Current Architecture (Annotated)

### Models involved

```
ExamSession
  - academic_year_id   (FK, NOT NULL)
  - room_id            (FK, NOT NULL in migration → second migration needed to make nullable)
  - date               (date, NOT NULL)
  - start_time         (time, NOT NULL)
  - end_time           (time, nullable)
  - status             ENUM: draft | published | in_progress | completed | cancelled
  - [NO type column]   ← GAP

GradingSession
  - exam_session_id    (FK, NOT NULL) ← coupling point; NOT changing
  - status             ENUM: open | in_progress | review | finalized

ApplicantScore
  - grading_session_id (FK, NOT NULL)
  - applicant_id       (FK, NOT NULL)
  - aptitude_area_id   (FK, NOT NULL)
  - raw_score / max_score / normalized_score
  - scored_by / scored_at

exam_session_applicant (pivot)
  - exam_session_id, applicant_id
  - attendance_status, attendance_marked_at, attendance_marked_by
  - submission_status, submitted_at, submitted_to

grading_session_applicant (pivot)
  - grading_session_id, applicant_id
  - result_printed_at
```

### GradingSessionService (existing — do not modify)

```php
// GradingSessionService::openForExamSession()
// - Creates GradingSession row linked to ExamSession
// - Copies all exam_session_applicant entries into grading_session_applicant
// - Returns loaded session
// ✅ Works unchanged for direct sessions — just receives a "direct" type ExamSession
```

### Existing short-circuit (already works)

`ApplicationController::storeAdmin()` supports `accept_immediately = true`:
- Sets `application.status = accepted` directly
- Creates the `Applicant` record immediately
- Dispatches setup email
- Bypasses application window check

This is the **entry point** for the direct flow. Staff creates the applicant via this path, then proceeds to Direct Assessment.

---

## 4. What Changes

### 4.1 Migration: Add `type` to `exam_sessions`

```php
// New columns (single migration)
$table->string('type')->default('scheduled')->after('status');
$table->string('label')->nullable()->after('type');
// type values: 'scheduled' | 'direct'
```

**Constraints for `direct` type sessions:**
- `room_id` → null (no physical room needed)
- `date` → auto-set to `today()`
- `start_time` → auto-set to `now()->format('H:i:s')` (NOT NULL constraint stays — we always fill it)
- `end_time` → null (already nullable)
- `label` → optional admin label (e.g. "Walk-in Batch 3")
- No room-conflict validation runs
- No `isWithinStartWindow()` / `isWithinExamWindow()` checks run

> [!RESOLVED]
> `room_id` is currently NOT NULL in the original migration (`foreignId('room_id')->constrained()`). A second migration is required to make it nullable. `start_time` stays NOT NULL — the service always auto-fills it with current time for direct sessions. This preserves audit value ("when was the session opened?") and avoids unnecessary schema churn.

### 4.2 Model: `ExamSession` additions

```php
// New constants
public const TYPE_SCHEDULED = 'scheduled';
public const TYPE_DIRECT    = 'direct';

// New fillable
'type',
'label',

// New helper
public function isDirect(): bool
{
    return $this->type === self::TYPE_DIRECT;
}
```

### 4.3 New Service Method: `DirectAssessmentService` (or extend `GradingSessionService`)

**Chosen: New `DirectAssessmentService`** (single responsibility, doesn't grow GradingSessionService):

```php
class DirectAssessmentService
{
    public function create(
        AcademicYear $academicYear,
        array $applicantIds,   // accepted Applicants to include
        User $openedBy,
        ?string $label = null  // optional admin label e.g. "Walk-in Batch 3"
    ): GradingSession {
        return DB::transaction(function () use (...) {
            // 1. Create ExamSession with type=direct
            $examSession = ExamSession::create([
                'academic_year_id' => $academicYear->id,
                'type'             => ExamSession::TYPE_DIRECT,
                'label'            => $label,
                'status'           => ExamSession::STATUS_IN_PROGRESS,
                'room_id'          => null,
                'date'             => today(),
                'start_time'       => now()->format('H:i:s'),
                'end_time'         => null,
                'created_by'       => $openedBy->id,
            ]);

            // 2. Assign applicants to exam session pivot (auto-present)
            foreach ($applicantIds as $id) {
                $examSession->applicants()->attach($id, [
                    'attendance_status'  => 'present',
                    'attendance_marked_at' => now(),
                    'attendance_marked_by'  => $openedBy->id,
                ]);
            }

            // 3. Delegate to existing GradingSessionService — no changes needed there
            return app(GradingSessionService::class)
                ->openForExamSession($examSession, $openedBy);
        });
    }
}
```

### 4.4 Controller: `DirectAssessmentController`

```
POST   /direct-assessments          → DirectAssessmentController::store()
```

**Request payload:**
```json
{
  "academic_year_id": 1,
  "applicant_ids": [42, 43, 44],
  "label": "Walk-in Batch 3"   // optional
}
```

**Validation rules:**
- `academic_year_id` — required, exists in `academic_years`
- `applicant_ids` — required, array, min:1, each must exist in `applicants` table
- Each applicant must have `application.status = accepted` (custom rule)
- Each applicant must NOT already be in an active grading session (idempotency guard)
- `label` — nullable, string, max:100

**Response:** Redirect to the existing grading session score-encoding page (`/grading-sessions/{id}`)

### 4.5 SystemSetting flag (in-scope)

```
Key:     allow_direct_assessment
Type:    boolean
Default: true
Label:   "Allow score encoding without a scheduled exam session"
```

If `false`, the Direct Assessment UI entry points are hidden and the controller returns 403.

---

## 5. UI Changes

### 5.1 Entry points (where to add the button)

| Location | Element | Action |
|---|---|---|
| Applicants index (`/applicants`) | "Direct Assessment" button (top-right, secondary style) | Opens modal |
| Applicant show page | "Open Direct Assessment" action | Pre-selects this applicant |
| Exam Sessions index | "New Direct Session" button | Alternative entry point |

### 5.2 Modal / Form

**"Create Direct Assessment Session"**

Fields:
- Academic Year (pre-filled from current active year)
- Applicants selector (multi-select, filtered to `accepted` status applicants not already in a grading session)
- Label (optional, text input)

**On submit:**
- POST to `DirectAssessmentController::store()`
- On success: redirect to grading session page (score encoding starts immediately)

### 5.3 Display / filtering

**Exam Sessions list:**
- Direct sessions shown with a `Direct` badge (e.g. blue pill)
- Filtered out of "schedule calendar" view by default (or clearly labelled)
- No room/time/proctor columns shown for direct sessions

**Grading Sessions list:**
- Direct sessions shown with a `Direct` badge in the session type column
- No functional change to score encoding UI — it's identical

---

## 6. Attendance / Pivot Semantics for Direct Sessions

The `exam_session_applicant` pivot has `attendance_status`. For direct sessions:

**Decision:** Set `attendance_status = 'present'` automatically — simpler, backward-compatible with all attendance queries. Also set `attendance_marked_at` and `attendance_marked_by` to timestamp the auto-attendance.

In reports, direct sessions count as 100% attendance. Reports can filter by `exam_session.type` if they need to distinguish physical vs. direct attendance.

---

## 7. Validation Guards (What Direct Sessions Skip)

The following checks in `ExamSession` are **skipped for `type = direct`**:

| Method | Skipped? | Reason |
|---|---|---|
| `hasRoomConflict()` | ✅ Yes | No room assigned |
| `isWithinStartWindow()` | ✅ Yes | No scheduled time |
| `isWithinExamWindow()` | ✅ Yes | No scheduled time |
| `isPastEndTime()` | ✅ Yes | No end time |

The following checks **still apply** to direct sessions:

| Check | Still Applies? | Why |
|---|---|---|
| Applicant must be `accepted` | ✅ Yes | Core business rule |
| Applicant not in active grading session | ✅ Yes | Prevents duplicate encoding |
| `academic_year_id` must be valid | ✅ Yes | Audit / reporting |
| `ensureAllApplicantsScored()` before finalize | ✅ Yes | Unchanged — score integrity |

---

## 8. Impact Analysis (What Does NOT Change)

| Component | Change Required | Notes |
|---|---|---|
| `GradingSession` model | ❌ None | `exam_session_id` stays NOT NULL |
| `ApplicantScore` model | ❌ None | Schema unchanged |
| `GradingSessionService::openForExamSession()` | ❌ None | Called as-is from new service |
| `GradingSessionService::updateWorkflowStatus()` | ❌ None | Finalization logic unchanged |
| `GradingSessionService::ensureAllApplicantsScored()` | ❌ None | Still enforced |
| All score encoding UI/controllers | ❌ None | Grading session page is identical |
| Reporting queries | ❌ None | All trace through same chain |
| Application + Applicant creation | ❌ None | `accept_immediately` path already works |

---

## 9. Migration Plan

### Files to create

| File | Purpose |
|---|---|
| `database/migrations/XXXX_add_type_and_label_to_exam_sessions.php` | Adds `type` (default `scheduled`) + `label` (nullable) columns |
| `database/migrations/XXXX_make_room_nullable_on_exam_sessions.php` | Makes `room_id` nullable (start_time stays NOT NULL — always auto-filled) |
| `app/Services/DirectAssessmentService.php` | New service |
| `app/Http/Controllers/DirectAssessmentController.php` | New controller |
| `app/Http/Requests/StoreDirectAssessmentRequest.php` | Validation |
| `routes/web.php` (edit) | Register new route |
| Svelte pages (new or modal) | UI entry points |

### Files to edit (minimal)

| File | Change |
|---|---|
| `app/Models/ExamSession.php` | Add `TYPE_DIRECT`/`TYPE_SCHEDULED` constants + `isDirect()` + `type`/`label` to `$fillable` |
| `database/factories/ExamSessionFactory.php` | Add `'type' => TYPE_SCHEDULED` default + `->direct()` state for tests |
| `app/Models/SystemSetting.php` | Add `allowDirectAssessment()` accessor |
| `app/Http/Controllers/Admin/ExamSessionController.php` | Skip `hasRoomConflict()` / `isWithinStartWindow()` / `isWithinExamWindow()` when `type = direct` |
| `app/Http/Requests/StoreExamSessionRequest.php` | Change `room_id` to `required_if:type,scheduled` (nullable for direct sessions) |
| `app/Http/Controllers/Admin/SettingsController.php` | Add `allow_direct_assessment` toggle |
| Exam Sessions index Svelte page | Add `Direct` badge, exclude direct sessions from calendar view |
| Grading Sessions index Svelte page | Add `Direct` badge |
| Settings Svelte page | Add toggle for `allow_direct_assessment` |

---

## 10. Resolved Questions

| # | Question | Decision | Rationale |
|---|---|---|---|
| Q1 | Is `room_id` currently NOT NULL? | **Yes, NOT NULL.** Second migration needed to make it nullable. | Confirmed: `$table->foreignId('room_id')->constrained()` creates NOT NULL column. |
| Q2 | Should direct sessions appear in the Exam Schedule calendar? | **No — exclude from calendar, show in list view only.** | Calendar is for physical room/time scheduling. Direct sessions have neither — showing them adds noise. |
| Q3 | Should `label` be persisted on `ExamSession`? | **Yes — add nullable `label` column.** | Useful for audit ("Walk-in Batch 3" beats "Direct #47"). Low cost, high traceability. |
| Q4 | Edge case: 0 applicants at creation? | **Reject at validation — `applicant_ids` min:1.** | A grading session with zero applicants is meaningless. Request validation enforces this. |
| Q5 | Is `SystemSetting` flag in-scope? | **Yes — `allow_direct_assessment` boolean, default true.** | SystemSetting pattern already exists (AI companion toggle, notify_on_publish). Low effort, high control. |
| Q6 | No proctor on direct sessions — acceptable for audit? | **Yes — `type=direct` sessions skip proctor assignment.** | No physical exam = no proctor needed. The `proctors()` relation returns empty for direct sessions. |
| Q7 | Attendance metrics for direct sessions? | **Auto-mark `present` — count as 100% attendance.** | Simpler than a new enum value. Reports can filter by `exam_session.type` if they need to distinguish. |

---

## 11. Acceptance Criteria (UAT)

- [ ] Staff can create a direct assessment session from the Applicants page with ≥1 accepted applicant
- [ ] Direct session bypasses room, date, time, and proctor requirements
- [ ] Staff is redirected to score encoding immediately after creating the session
- [ ] Score encoding works identically to a scheduled session
- [ ] Finalization enforces all applicants scored (no regression)
- [ ] Direct sessions show a `Direct` badge in Exam Sessions list and Grading Sessions list
- [ ] Direct sessions do not appear in the Exam Schedule calendar (or are visually separated)
- [ ] An accepted applicant already in an active grading session cannot be added to a new direct session
- [ ] A pending/rejected applicant cannot be added to a direct session (validation error)
- [ ] All existing scheduled exam session flows work without regression (no NULL room_id issues)
- [ ] Disabling `allow_direct_assessment` in Settings hides all Direct Assessment UI entry points and returns 403 on direct POST

---

## 12. Implementation Order

| Phase | Scope | Depends on |
|---|---|---|
| P1 | Migrations: `type` + `label` columns, `room_id` + `start_time` nullable | — |
| P2 | `ExamSession` model: constants, `isDirect()`, `$fillable`, `$casts` | P1 |
| P3 | `DirectAssessmentService` + `StoreDirectAssessmentRequest` | P2 |
| P4 | `DirectAssessmentController` + route registration | P3 |
| P5 | `ExamSessionController` guard: skip room/window checks for direct sessions | P2 |
| P6 | `SystemSetting::allowDirectAssessment()` + Settings controller toggle | — |
| P7 | Svelte UI: Direct Assessment modal, badges on lists, Settings toggle | P3, P4, P6 |
| P8 | Tests: feature tests for controller, service, validation guards | P3, P4, P5 |

---

## 13. References

- `app/Models/ExamSession.php` — current model with status constants and room-conflict logic
- `app/Models/GradingSession.php` — coupling point (exam_session_id NOT NULL)
- `app/Models/ApplicantScore.php` — score chain terminus
- `app/Services/GradingSessionService.php` — `openForExamSession()` to reuse unchanged
- `app/Http/Controllers/ApplicationController.php:212` — `accept_immediately` precedent
- `app/Http/Requests/StoreApplicationRequest.php:33` — existing admin-create request pattern
