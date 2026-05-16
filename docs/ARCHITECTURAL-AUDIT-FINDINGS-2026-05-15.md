# SecureCAT-v2 Comprehensive Architectural & Coding Practices Audit

> Document Date: 2026-05-15
> Scope: Full-stack Laravel 12 + Inertia/Svelte application
> Approach: Static analysis of controllers, models, services, migrations, routes, and frontend components

---

## TABLE OF CONTENTS

1. [P0 — Critical (Scalability + Architecture)](#p0--critical-scalability--architecture)
2. [P1 — High (Reusability + Standardization)](#p1--high-reusability--standardization)
3. [P2 — Medium (Architecture + Frontend)](#p2--medium-architecture--frontend)
4. [P3 — Low (Standards + Polish)](#p3--low-standards--polish)
5. [Database-Level Issues Summary](#database-level-issues-summary)
6. [Prioritized Remediation Roadmap](#prioritized-remediation-roadmap)

---

## P0 — Critical (Scalability + Architecture)

### 1. ApplicationController is a God Class (981 lines)
**File:** `app/Http/Controllers/ApplicationController.php`

**Problem:** This single controller handles public application creation, staff application list/index with complex filtering, staff CRUD, application acceptance/dismissal/reopen, bulk accept/dismiss, admission slip generation, portal views, setup email resending, and more.

**Refactor:** Split into:
- `PublicApplicationController` (public apply flow)
- `AdminApplicationController` (staff CRUD, bulk ops)
- `PortalApplicationController` (applicant portal views)

---

### 2. ApplicationController::index() Loads ALL Records Into Memory for Pipeline Sorting
**File:** `app/Http/Controllers/ApplicationController.php` (lines 62–117)

**Problem:** When filtering by `pipeline_status` or sorting by it, the controller executes `$query->orderByDesc('submitted_at')->get()` (loading ALL applications into memory), transforms them in PHP, filters/sorts the collection, then manually creates a `LengthAwarePaginator`. With thousands of applications, this will exhaust memory and timeout.

**Refactor:** Move pipeline status computation to a database-computable field (generated column or cached value), or use a database view. If not feasible, add pagination BEFORE transformation.

---

### 3. Missing DB Composite Indexes on High-Volume Tables
**Files:** Various migrations

| Table | Missing Index | Impact |
|-------|--------------|--------|
| `applications` | Composite on `(academic_year_id, status, submitted_at)` | Nearly every query filters by these; full table scan |
| `applications` | Index on `appointment_id` | Appointment booking/unbooking does `where('id', $appointmentId)` on applications |
| `exam_sessions` | Index on `academic_year_id` | `forAcademicYear()` scope used everywhere |
| `applicant_scores` | Index on `applicant_id` | Score lookups by applicant scan entire table |
| `applicant_scores` | Index on `aptitude_area_id` | Score lookups by area scan entire table |
| `applicant_scores` | FK constraint on `aptitude_area_id` | Defined as `unsignedBigInteger->nullable()` without FK; data integrity not enforced |

**Refactor:** Add missing indexes and FK constraints via new migrations.

---

### 4. ExamSessionController::show() Loads ALL Unassigned Applicants Without Pagination
**File:** `app/Http/Controllers/Admin/ExamSessionController.php` (lines 206–226)

**Problem:** `$available_applicants` loads every accepted applicant not in a session, with full application relationship. At scale (thousands of applicants), this is unbounded.

**Refactor:** Paginate available applicants, or use a search-enabled API endpoint for applicant assignment.

---

## P1 — High (Reusability + Standardization)

### 5. Date/Time Formatting Functions Duplicated Across 10+ Svelte Pages
**Files:** `resources/js/Pages/Admin/TestScheduling/Index.svelte`, `Admin/TestAdmin/Index.svelte`, `Grading/Session.svelte`, `Proctor/MySessions.svelte`, `Proctor/SessionRoster.svelte`, `Release/PrintBatch.svelte`, `Admin/KnowledgeDocuments/Index.svelte`, `Admin/TestScheduling/Monitoring.svelte`, `Admin/TestScheduling/Show.svelte`, `Dashboard.svelte`, `Components/SessionRoster.svelte`

**Problem:** Every page reimplements `formatDate()`, `formatTime()`, and `formatDateTime()` with nearly identical logic.

**Refactor:** Create a shared utility module at `resources/js/lib/formatters.js` exporting:
```javascript
export function formatDate(value, options = {})
export function formatTime(value, options = {})
export function formatDateTime(value, options = {})
```

---

### 6. Delete Confirmation Modal Duplicated in 11 Pages
**Files:** `resources/js/Pages/Admin/AcademicYears/Index.svelte`, `Admin/AdmissionSlipTemplates/Index.svelte`, `Admin/AptitudeAreas/Index.svelte`, `Admin/Courses/Edit.svelte`, `Admin/Courses/Index.svelte`, `Admin/KnowledgeDocuments/Index.svelte`, `Admin/ResultSheetTemplates/Index.svelte`, `Admin/Rooms/EditForm.svelte`, `Admin/Rooms/Index.svelte`, `Admin/TestScheduling/Index.svelte`, `Admin/Users/Index.svelte`

**Problem:** Each page has its own `deleteId`, `confirmDelete()`, `cancelDelete()`, `doDelete()` state and a raw HTML delete confirmation dialog (~30 lines of identical boilerplate per page).

**Refactor:** Extract a reusable `ConfirmDeleteDialog.svelte` component in `resources/js/Components/` that accepts `open`, `title`, `description`, `onConfirm`, and `onCancel` props.

---

### 7. Filter UI Pattern Duplicated in 6+ Pages
**Files:** `resources/js/Pages/Admin/KnowledgeDocuments/Index.svelte`, `Admin/Logs/Index.svelte`, `Admin/Rooms/Index.svelte`, `Admin/TestScheduling/Index.svelte`, `Admin/Users/Index.svelte`, `Applications/Index.svelte`

**Problem:** Desktop filter row + mobile collapsible `<details>` filter panel with `applyFilters()` function is copy-pasted. IDs are manually namespaced (`filter-status-desk` vs `filter-status-mob`).

**Refactor:** Create a `FilterBar.svelte` component that accepts a `filters` config array and an `onApply` callback, rendering both desktop and mobile views internally.

---

### 8. Name Building Logic Duplicated in 9 Controllers
**Files:** `app/Http/Controllers/Admin/ExamSessionController.php`, `AdmissionSlipPrintController.php`, `DirectAssessmentController.php`, `Grading/GradingScoreController.php`, `Grading/GradingSessionController.php`, `Proctor/SessionRosterController.php`, `Release/ReleasePrintController.php`, `ReleaseController.php`

**Problem:** The pattern `trim(implode(' ', array_filter([$firstName, $middleName, $lastName, $suffix])))` appears identically in 9 controllers.

**Refactor:** Add a `getFullName()` accessor or method to the `Application` model, or create a `NameFormatter` service/utility class.

---

### 9. Inconsistent Casts Declaration Pattern Across Models
**Files:** `app/Models/Application.php` (uses `$casts` property), `app/Models/ExamSession.php` (uses `casts()` method), `app/Models/Applicant.php` (uses `casts()` method), `app/Models/AptitudeArea.php` (uses `casts()` method), `app/Models/GradingSession.php` (uses `casts()` method)

**Problem:** Some models use the legacy `$casts` property; others use the Laravel 11+ `casts()` method.

**Refactor:** Standardize on `casts()` method for all models (Laravel 12 best practice). `Application.php` should be updated.

---

### 10. Inconsistent Error Handling Patterns
**Files:** Across all controllers

**Problem:** Controllers use a mix of:
- `abort(403, 'message')`
- `abort_unless($condition, 403, 'message')`
- `return redirect()->back()->with('error', 'message')`
- `return back()->withErrors(['error' => 'message'])`
- `throw new \InvalidArgumentException('message')` (in services)

**Refactor:** Standardize on a single approach. For controllers, prefer `abort()` for authorization failures and `redirect()->back()->with('error', ...)` for business logic failures. For services, use custom exceptions with a global exception handler.

---

### 11. Inconsistent Dependency Injection vs. `app()` Helper
**Files:** `app/Http/Controllers/ApplicationController.php` (uses `app(AdmissionSlipService::class)`), `app/Http/Controllers/Admin/UserController.php` (uses `app(AuditService::class)`), `app/Http/Controllers/Grading/GradingController.php` (uses constructor injection)

**Problem:** Some controllers resolve services via `app()` helper mid-method; others use constructor property promotion.

**Refactor:** Standardize on constructor injection for all service dependencies.

---

### 12. Inconsistent Logging vs. Audit Service Usage
**Files:** `app/Http/Controllers/Admin/UserController.php` (uses both `Log::info` and `AuditService`), `app/Http/Controllers/ApplicationController.php` (uses `Log::info` only)

**Problem:** Some controllers log via `Log::info()`; others also call `AuditService::log()`. There is no clear rule.

**Refactor:** Define a clear boundary: `Log::` for developer/debug logs, `AuditService` for business-auditable events. Ensure all state-mutating controller actions use `AuditService`.

---

### 13. ScoreImportService Loads All Applications With Deep Eager Loading
**File:** `app/Services/ScoreImportService.php` (lines 155–159, 318–322)

**Problem:** `$applicationMap = Application::whereIn('reference_number', $referenceNumbers)->with('applicant.examSessions.gradingSession.examSession')->get()->keyBy('reference_number');` loads all matching applications with deep eager loading. For bulk imports with hundreds of rows, this loads excessive data.

**Refactor:** Batch the lookup. Process records in chunks of 100, loading only needed columns and relationships per batch.

---

## P2 — Medium (Architecture + Frontend)

### 14. Business Logic in Controllers Instead of Services
**Files:** `ApplicationController::accept()`, `ApplicationController::bulkAccept()`, `ApplicationController::bulkDismiss()`, `ExamSessionController::publish()`, `ExamSessionController::start()`, `ExamSessionController::complete()`

**Problem:** These methods contain complex business logic (status transitions, applicant creation, email dispatching, notification sending) directly in controllers.

**Refactor:** Extract state-machine services:
- `ApplicationWorkflowService` (accept, dismiss, reopen transitions)
- `ExamSessionWorkflowService` (publish, start, complete, cancel transitions)

---

### 15. No Repository Pattern / Direct Eloquent Queries Everywhere
**Files:** All controllers

**Problem:** Controllers directly query Eloquent models. This makes testing harder, prevents query optimization centralization, and scatters data access logic.

**Refactor:** Introduce repository interfaces for high-volume entities (`Application`, `ExamSession`, `Applicant`). Start with `ApplicationRepository` and `ExamSessionRepository`.

---

### 16. SessionRoster.svelte is Overly Large (557 lines) with Mixed Concerns
**File:** `resources/js/Components/SessionRoster.svelte`

**Problem:** Handles session info display, analytics, applicant table rendering, QR scanner integration, attendance marking, submission logging, bulk submission, session start/close, client-side time window calculations, and error handling.

**Refactor:** Decompose into:
- `SessionInfoCard.svelte`
- `SessionAnalyticsPanel.svelte`
- `ApplicantRosterTable.svelte`
- `QrScannerModal.svelte`
- `SessionActionBar.svelte`

---

### 17. ResultSheetTemplateService Has ~85% Duplicated Methods
**File:** `app/Services/ResultSheetTemplateService.php` (lines 55–94 vs 105–144)

**Problem:** `render()` and `renderHtmlContent()` share almost identical replacement logic. `render()` delegates to `renderHtml()` after building replacements; `renderHtmlContent()` rebuilds the same replacements from scratch.

**Refactor:** Extract a `buildReplacements(array $applicants, bool $useSampleData): array` private method and reuse it in both entry points.

---

### 18. ApplicantImportService and ScoreImportService Share Similar Spreadsheet Parsing Logic
**Files:** `app/Services/ApplicantImportService.php`, `app/Services/ScoreImportService.php`

**Problem:** Both services independently implement Excel/CSV parsing, header validation, and row filtering (~150 lines of near-identical spreadsheet handling).

**Refactor:** Extract a `SpreadsheetParser` concern or service that both import services delegate to.

---

### 19. ReleasePrintController Duplicates Applicant Data Transformation 3x
**File:** `app/Http/Controllers/Release/ReleasePrintController.php` (lines 95–112, 185–198, 245–258)

**Problem:** The same applicant-to-array mapping logic appears three times in different methods (`resultSheet`, `printBulk`, `printBulkAgnostic`).

**Refactor:** Extract a private `mapApplicantForPrint($applicant, $scores)` method or an `ApplicantPrintDataTransformer`.

---

### 20. Dashboard.svelte is Large (392 lines) and Likely Has Mixed Concerns
**File:** `resources/js/Pages/Dashboard.svelte`

**Problem:** Likely renders role-specific dashboards (staff, proctor, test admin, applicant) in a single file.

**Refactor:** Split into role-specific dashboard components loaded conditionally:
- `StaffDashboard.svelte`
- `ProctorDashboard.svelte`
- `ApplicantDashboard.svelte`

---

### 21. Course Preference Deduplication Logic Copy-Pasted Across 3 Forms
**Files:** `resources/js/Pages/Portal/ApplicationEdit.svelte`, `Applications/Apply.svelte`, `Admin/Applications/Edit.svelte`

**Problem:** `optionsFor2`, `optionsFor3`, auto-clearing on conflict logic duplicated across the public apply form, admin edit form, and portal edit form.

**Refactor:** Extract a `CoursePreferenceSelect.svelte` component that encapsulates the three dropdowns and their interdependent validation logic.

---

## P3 — Low (Standards + Polish)

### 22. Hardcoded Route Strings in Svelte Components
**Files:** Many Svelte pages

**Problem:** Routes like `/admin/exam-scheduling/${session.id}/publish`, `/proctor/sessions/${session.id}/attendance`, `/portal/application/edit` are hardcoded as strings in Svelte components instead of using named routes.

**Refactor:** Share named routes from Laravel to the frontend via Inertia shared props, or use a JavaScript route helper (e.g., `ziggy-js`) that consumes `Ziggy` config.

---

### 23. Hardcoded Accessibility / Touch Target Values (~200+ occurrences)
**Files:** Nearly every Svelte page

**Problem:** `min-h-[44px]` is copy-pasted ~200+ times across the frontend for WCAG touch targets. If the design system changes the minimum touch target size, every file must be updated.

**Refactor:** Define a Tailwind plugin or utility class (e.g., `.touch-target`) in the CSS layer, or use a shared constant in a design-token config.

---

### 24. Inconsistent Route Naming Conventions
**File:** `routes/web.php`

**Problem:** Route names mix kebab-case (`exam-scheduling.index`), snake_case (`admin.applications.admin-show`), and dot-namespaced (`admin.grading.index`).

**Refactor:** Establish a naming convention document. Prefer dot-namespaced kebab-case for all routes.

---

### 25. `decision_rules` Migration References Dropped `exam_domains` Table
**File:** `database/migrations/2026_02_19_000005_create_decision_rules_table.php`

**Problem:** Migration has `$table->foreignId('domain_id')->nullable()->constrained('exam_domains')`, but `exam_domains` was dropped in migration `2026_04_09_000004_drop_exam_domains_table.php`. This is a migration history inconsistency.

**Refactor:** Ensure migration order is correct. Verify a subsequent migration updates the FK to `aptitude_areas`.

---

### 26. Missing Soft Deletes on Critical Tables
**Files:** Various migrations

**Problem:** `applications`, `grading_sessions`, `consultation_summaries`, `decision_rules`, and `aptitude_areas` have inconsistent or missing soft-delete coverage. `applications` has no soft deletes, meaning accidental deletion is permanent.

**Refactor:** Evaluate which entities need soft deletes. At minimum, `applications` and `grading_sessions` should have them. Add `deleted_at` columns via migrations.

---

### 27. Notifications Loaded Eagerly on Every Inertia Request
**File:** `app/Http/Middleware/HandleInertiaRequests.php` (lines 78–89)

**Problem:** The middleware loads the user's 20 most recent notifications on EVERY Inertia request, regardless of whether the page uses them.

**Refactor:** Use Inertia deferred props or lazy evaluation. Only load notifications for routes that display them.

---

## Database-Level Issues Summary

| Table | Issue |
|-------|-------|
| `applications` | Missing composite index on `(academic_year_id, status, submitted_at)`; missing index on `appointment_id`; no soft deletes |
| `exam_sessions` | Missing index on `academic_year_id` |
| `applicant_scores` | Missing indexes on `applicant_id`, `aptitude_area_id`; missing FK constraint on `aptitude_area_id` |
| `grading_sessions`, `consultation_summaries`, `decision_rules` | Inconsistent soft-delete coverage |
| `decision_rules` | FK references dropped `exam_domains` table |

---

## Prioritized Remediation Roadmap

| Priority | Category | Item | Effort |
|----------|----------|------|--------|
| P0 | Scalability | Fix `ApplicationController::index()` memory pagination | Medium |
| P0 | Scalability | Add missing DB indexes (`applications`, `exam_sessions`, `applicant_scores`) | Low |
| P0 | Architecture | Split `ApplicationController` god class | High |
| P1 | Reusability | Extract shared date/time formatter utility | Low |
| P1 | Reusability | Extract `ConfirmDeleteDialog` component | Low |
| P1 | Reusability | Extract `FilterBar` component | Medium |
| P1 | Architecture | Extract workflow services (`ApplicationWorkflowService`, `ExamSessionWorkflowService`) | Medium |
| P1 | Database | Add soft deletes to `applications` and `grading_sessions` | Low |
| P2 | Reusability | Extract `CoursePreferenceSelect` component | Low |
| P2 | Architecture | Introduce repository pattern for `Application` and `ExamSession` | High |
| P2 | Frontend | Decompose `SessionRoster.svelte` | Medium |
| P2 | Standards | Standardize on constructor injection + `casts()` method | Medium |
| P3 | Standards | Fix hardcoded route strings (adopt Ziggy) | Medium |
| P3 | Standards | Standardize error handling patterns | Medium |
| P3 | Database | Fix `decision_rules` migration FK inconsistency | Low |

---

> **Next Step:** Pick a priority tier and begin remediation. Recommend starting with P0 items to prevent production scaling issues, then moving to P1 for rapid wins via shared components and services.
