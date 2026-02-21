# Edge Cases and Logic Issues

This document catalogs logic gaps, spec mismatches, and edge cases identified in the SecureCAT codebase. Use it as a backlog for prioritization and remediation.

---

## Severity Legend

| Severity | Meaning |
|----------|---------|
| **High** | Data corruption risk, security concern, or major workflow break |
| **Medium** | Inconsistent UX, spec violation, or moderate workflow impact |
| **Low** | Minor inconsistency, polish, or clarification needed |
| **Info** | Design choice or intentional behavior to document |

---

## 1. Exam Session Status & Publishing

### 1.1 Publish Semantics (Spec vs Implementation)

**Intended meaning (per 08-API-SPEC-PHASE1, securecat-architecture-reference):**

| Aspect | Spec / Architecture | Reference |
|--------|---------------------|-----------|
| Purpose | Publish = **announce exam schedule** to applicants so they see it in the portal | §5.2.2 My Exam Schedule, §6.1 Notification Event Matrix |
| On publish | Status → `published`, `published_at` set, **trigger notifications to assigned applicants** (email + in-app) | POST /admin/exam-sessions/{id}/publish |
| Portal | "My Exam Schedule" populated immediately; applicants see room, date, time | §5.2.2, §7.2 Portal Data Feeds |
| Event | "Exam Schedule Assigned" — Phase 2 publication event | §7.3 Notification Event Sources |

**Current implementation:**
- ✅ Status and `published_at` set
- ✅ Proctor gate: must be `published` to start session
- ❌ No notification dispatch (BD-066 ExamScheduleAssigned not implemented)
- ❌ Portal returns `exam_schedule: null` hardcoded (BD-2b3 full dashboard not implemented)

**Refactor note:** When implementing BD-066 and BD-2b3, publish will become the "announce" action. Blocking publish when `in_progress` or `completed` is correct — you do not "announce" a schedule that is already running or finished.

---

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-001 | **Publish allowed when exam is `in_progress`** | High | `ExamSessionController::publish`, `Admin/ExamSessions/Show.svelte` | `redirectIfCompleted` blocks only `completed`. For `in_progress`, publish runs and rolls exam back to `published`, losing `started_at`. Semantically wrong: publish = announce schedule; you cannot announce something already in progress. |
| E-002 | **Schedule actions (Publish/Release) shown for completed sessions** | Medium | `Admin/ExamSessions/Show.svelte` | Publish and release-date controls visible when status is `completed` or `cancelled`; backend blocks but UI suggests they're editable. |
| E-003 | **Schedule actions not status-gated** | Medium | `Admin/ExamSessions/Show.svelte` | Publish button disabled only when `status === 'published'`; should also disable for `in_progress`, `completed`, `cancelled`. |
| E-004 | **Admin can edit cancelled sessions** | Low | `ExamSessionPolicy::update` | Policy only blocks `completed`; cancelled sessions remain editable. |
| E-038 | **Publish does not trigger notifications or portal population** | High | `ExamSessionController::publish`, `PortalAuthController::dashboard` | Spec: on publish, trigger ExamScheduleAssigned (email + in-app) and populate portal exam_schedule. Currently: no notification (BD-066), portal `exam_schedule` hardcoded null (BD-2b3). |

---

## 2. Grading Module

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-005 | **Opening grading session not restricted to completed exams** | High | `StoreGradingSessionRequest`, `GradingSessionService::openForExamSession` | Spec: exam session must be `completed`. Code allows opening for draft/published/in_progress. |
| E-006 | **Scores editable when GradingSession is finalized** | High | `GradingScoreController`, `ScoreInputService`, `UpdateScoresRequest` | Spec: scores read-only when finalized. Backend does not block score updates. |
| E-007 | **Score form always editable; no read-only when finalized** | Medium | `ScoreInput.svelte` | Page does not receive `workflowStatus`; cannot disable form when finalized. |
| E-008 | **Score links always shown as "Edit" when finalized** | Medium | `Grading/Session.svelte` | Should show "View scores" instead of "Edit scores" when `workflowStatus === 'completed'`. |
| E-009 | **Duplicate grading sessions allowed per exam** | High | `GradingController::store`, `grading_sessions` migration | No unique constraint on `exam_session_id`; no validation. Spec: 409 if grading session already exists. |
| E-010 | **Finalize without requiring all applicants scored** | High | `GradingSessionController::updateWorkflowStatus`, `GradingSessionService` | Spec: all submitted applicants must have scores before finalize. Code does not check. |
| E-011 | **GradingSession `open` and `review` statuses unused** | Low | Migration, `GradingSessionController::updateWorkflowStatus` | Workflow only toggles `in_progress` ↔ `finalized`; `open` and `review` never used. |
| E-012 | **No status change on first score input** | Low | `ScoreInputService::saveScores` | Spec: update session to `in_progress` on first input. Service does not change status. |
| E-013 | **Applicant not validated as member of grading session** | Medium | `GradingScoreController::show`, `update` | Route binding loads any applicant; no check that applicant belongs to the grading session. |
| E-014 | **`raw_score` can exceed `max_score`** | High | `UpdateScoresRequest`, `ScoreInputService` | No validation `raw_score <= max_score`. Allows `normalized_score > 100`. |
| E-015 | **Domain IDs not validated in score input** | Medium | `UpdateScoresRequest`, `ScoreInputService` | No validation that score keys are valid/existing `exam_domains` or that domains are active. |
| E-016 | **Domains count uses all domains, not active only** | Low | `GradingSessionController::show` | `$domainsTotal = ExamDomain::count()`; "scored" logic may be wrong if inactive domains exist. |
| E-017 | **`opened_by` cascade on user delete** | Medium | `grading_sessions` migration | Deleting grader who opened session can cascade-delete grading session. |
| E-018 | **`scored_by` cascade on user delete** | Medium | `applicant_scores` migration | Deleting grader may cascade and affect scores; verify intended behavior. |

---

## 3. Proctor & Session Roster

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-019 | **`statusVariant` mixes session and attendance statuses** | Low | `SessionRoster.svelte` | Single function used for both; `published` and `in_progress` are session statuses, not attendance. |

---

## 4. Reopen & Completed Workflow

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-020 | **Reopen ignores existing/finalized grading session** | Medium | `ExamSessionController::reopen` | Reopen sets exam to `in_progress` without checking if `GradingSession` exists or is finalized. Creates inconsistent state. |
| E-021 | **Print result sheet for applicant not in session** | Low | `GradingPrintController::resultSheet` | No explicit check that applicant belongs to grading session; pivot may be null, page still renders. |

---

## 5. Print Batch

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-022 | **Mark-printed: applicants not validated as session members** | Low | `MarkPrintedRequest`, `PrintBatchService` | `applicant_ids` validated as `exists:applicants,id` only. Non-members silently ignored (no corruption, but API accepts invalid IDs). |

---

## 6. Rooms

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-023 | **Room capacity can be reduced below current assignments** | High | `UpdateRoomRequest` | No check that `capacity >= current assigned count`. |
| E-024 | **Room deactivation with future exam sessions** | Medium | `RoomController::destroy` | Comment/spec mention blocking deactivation if assigned to future sessions; not implemented. |
| E-025 | **Room name uniqueness when building changes** | Low | `UpdateRoomRequest` | Uniqueness is `(building, name)`; updating building could create duplicates or violate constraints. |

---

## 7. Consultation Module

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-026 | **Consultation schedule: applicants not validated against grading session** | Medium | `StoreConsultationScheduleRequest`, `ConsultationScheduleController` | `applicant_ids` validated as existing applicants only; not checked against `grading_session_id`. |
| E-027 | **Counselor can view any applicant** | Medium | `ConsultationApplicantController::show`, `update`, `release` | No check that applicant has finalized scores or is in consultation scope. |
| E-028 | **`result_printed_at` gates consultation scheduling** | Medium | `ConsultationScheduleController`, `ScheduleDay.svelte` | Only applicants with printed results appear. Applicants with finalized scores but unprinted results cannot be scheduled. |
| E-029 | **Decision rule min/max score ordering** | Low | `StoreDecisionRuleRequest`, `UpdateDecisionRuleRequest` | Verify validation that `min_score <= max_score`. |
| E-030 | **Recommended course not restricted to preferences** | Low | `UpdateConsultationSummaryRequest` | `recommended_course_id` can be any course; spec may require it to be in applicant's preferences. |

---

## 8. Applications

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-031 | **Reject on non-pending application** | Low | `ApplicationController::reject` | Verify that reject is blocked when status is not `pending`. |
| E-032 | **Appointment `booked_count` not decremented** | Medium | Application cancellation/deletion flows | Increment on book; no decrement on cancel/delete. Count can drift. |

---

## 9. Courses

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-033 | **Course delete cascades to applications** | High | `applications` migration (course_preference FKs) | `cascadeOnDelete` on courses; deleting a course can cascade-delete or break applications. Spec may prefer restrict or set null. |

---

## 10. Authorization & Visibility

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-034 | **Staff role and exam session visibility** | Low | Routes, `ExamSessionController` | Verify staff can view exam sessions per spec; routes may exclude staff. |
| E-035 | **Proctor view of draft sessions** | Info | `ExamSessionController::index` | Proctors see only assigned sessions; draft sessions may not appear even if they are assigned. |

---

## 11. Exam Sessions & Data Model

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-036 | **No exam session delete route** | Info | `routes/web.php`, `ExamSessionController` | No `destroy` for exam sessions; deletion requires DB or new endpoint. Cascades affect grading sessions. |

---

## 12. Portal & Auth

| ID | Issue | Severity | Location | Notes |
|----|-------|----------|----------|-------|
| E-037 | **Setup token expiration on use** | Low | `PortalAuthController` | Verify setup flow consistently checks `Applicant::isSetupTokenValid()` before proceeding. |

---

## Quick Reference: High Severity

| ID | Issue |
|----|-------|
| E-001 | Publish allowed when in_progress |
| E-005 | Grading openable for non-completed exams |
| E-006 | Scores editable when finalized |
| E-009 | Duplicate grading sessions allowed |
| E-010 | Finalize without all applicants scored |
| E-014 | raw_score > max_score allowed |
| E-023 | Room capacity below assignments |
| E-033 | Course delete cascades to applications |
| E-038 | Publish does not trigger notifications or portal population |

---

## Remediation Checklist

### Exam Session / Publish
- [x] Add status checks to `publish()` and `releaseDate()` for `in_progress` and `cancelled` (E-001)
- [x] Gate schedule actions in UI by status (E-002, E-003)
- [ ] Implement BD-066: ExamScheduleAssigned notification on publish (email + in-app)
- [ ] Implement BD-2b3: Portal dashboard — populate `exam_schedule` from published sessions where applicant is assigned

### Grading
- [x] Validate `ExamSession::STATUS_COMPLETED` in `StoreGradingSessionRequest` (E-005)
- [x] Block score updates when `GradingSession::status === finalized` (E-006)
- [x] Pass `workflowStatus` to `ScoreInput.svelte` and disable form when finalized (E-007)
- [x] Show "View scores" vs "Edit scores" in Session.svelte when finalized (E-008)
- [x] Add validation for one grading session per exam (E-009)
- [x] Validate all applicants scored before finalize (E-010)
- [x] Add `raw_score <= max_score` to `UpdateScoresRequest` (E-014)
- [x] Validate applicant belongs to grading session in `GradingScoreController` (E-013)
- [x] Add domain ID validation to `UpdateScoresRequest` (E-015)
- [x] Use active domains only for scored count in `GradingSessionController` (E-016)
- [x] Update session to in_progress on first score input in `ScoreInputService` (E-012)

### Rooms / Consultation / Applications
- [x] Add `capacity >= current count` to `UpdateRoomRequest` (E-023)
- [x] Block edit cancelled exam sessions in `ExamSessionPolicy` (E-004)
- [x] Block reopen when grading finalized in `ExamSessionController::reopen` (E-020)
- [x] Validate applicant in grading session for result sheet (E-021)
- [x] Add min_score <= max_score to `UpdateDecisionRuleRequest` (E-029)
- [x] Validate applicants in `MarkPrintedRequest` against grading session (E-022)
- [x] Block room deactivation when future exam sessions exist (E-024)
- [x] Validate applicants in `StoreConsultationScheduleRequest` against grading session (E-026)
- [x] Split statusVariant into sessionStatusVariant and attendanceStatusVariant in SessionRoster (E-019)
- [x] Scope counselor to applicants with finalized scores in ConsultationApplicantController (E-027)
- [x] Restrict recommended_course_id to applicant preferences in UpdateConsultationSummaryRequest (E-030)
- [x] Decrement appointment booked_count on reject in ApplicationController (E-032)
- [ ] Review course FK cascade behavior

---

*Last updated: 2026-02-21*
