# SecureCAT-v2 System Audit — Findings & Edge Cases

> Document Date: 2026-04-07
> Purpose: Comprehensive record of all discovered behaviors, gaps, and planned amendments for future reference and development sessions.

---

## TABLE OF CONTENTS

1. [Publishing & Visibility Flow](#1-publishing--visibility-flow)
2. [Scanning & Attendance](#2-scanning--attendance)
3. [Season vs Academic Year Terminology](#3-season-vs-academic-year-terminology)
4. [Application Window](#4-application-window)
5. [Validation Strength](#5-validation-strength)
6. [Orphan Records & Cascade](#6-orphan-records--cascade)
7. [Scheduler & Auto-Expiry](#7-scheduler--auto-expiry)
8. [Role-Based Access Control](#8-role-based-access-control)
9. [Breadcrumb Header](#9-breadcrumb-header)
10. [Pagination & Performance](#10-pagination--performance)
11. [Hardcoded Business Values](#11-hardcoded-business-values)
12. [Authentication Flows](#12-authentication-flows)
13. [File Uploads](#13-file-uploads)
14. [AI Integration](#14-ai-integration)
15. [Notification Architecture](#15-notification-architecture)
16. [Error Handling & Logging](#16-error-handling--logging)
17. [Entity Status Machines](#17-entity-status-machines)
18. [Database Schema](#18-database-schema)
19. [User Amendments & Planned Changes](#19-user-amendments--planned-changes)

---

## 1. PUBLISHING & VISIBILITY FLOW

### How Publish Works

**Endpoint:** `POST /admin/test-scheduling/{exam_session}/publish`

**File:** `app/Http/Controllers/Admin/ExamSessionController.php` (lines 339–360)

**Requirements to publish:**
- Session must NOT be `in_progress` or `cancelled`
- At least one applicant must be assigned (`count > 0`)
- Sets `status` to `published` and `published_at` to current timestamp

**Unpublish:**
- Only published sessions can be unpublished
- Resets `status` to `draft`, clears `published_at`

### Publish → No Notifications Sent

The architecture reference states publishing auto-triggers the Notification Engine. **This is aspirational — not implemented.** The `publish()` method has NO notification dispatch code.

### Session Status Lifecycle

```
[draft] --publish--> [published] --start_session--> [in_progress] --close_session--> [completed]
                         |                              |
                         |_unpublish_                   |_reopen_ (back to in_progress)
                                                      |
                                          [cancelled] <--cancel__
```

### Status Constants (app/Enums/ExamSessionStatus.php)

```php
STATUS_DRAFT = 'draft';
STATUS_PUBLISHED = 'published';
STATUS_IN_PROGRESS = 'in_progress';
STATUS_COMPLETED = 'completed';
STATUS_CANCELLED = 'cancelled';
```

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 2. SCANNING & ATTENDANCE

### Scan Requires `in_progress` Status

Proctors can **ONLY** mark attendance/submission when session is `in_progress`, not merely `published`.

**File:** `app/Http/Controllers/Proctor/SessionRosterController.php` (line 82)

```php
if (! in_array($exam_session->status, [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS], true)) {
    return response()->json(['message' => 'Session must be published or in progress.'], 409);
}
```

### Attendance Status Flow

```
pending (default)
    |
    +---> present (scanned or manually marked)
    |
    +---> absent (manually marked — e.g., no-show)
```

### Submission Status Flow

```
pending
    |
    +---> submitted (only if attendance === 'present')
```

### Applicant Pivot Table

**Table:** `exam_session_applicant`
**Unique constraint:** `[exam_session_id, applicant_id]` — prevents double-assignment of same applicant to same session.
**Also unique on:** `applicant_id` alone — prevents same applicant being in multiple exam sessions at all.

### DEFERRED: Admission Slip & QR Scan

**Decision (2026-04-07):** Deferred indefinitely. Do not build.
- Remove QR code generation from admission slips
- Remove `QrCodeService` references
- Rely on **name or reference number search** to find applicants for scoring and attendance

### Applicant Search Method (Future)

Find applicants by:
- `reference_number` (exact match, e.g., `APP-2026-00001`)
- Full name search (LIKE query on `first_name`, `middle_name`, `last_name`)

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 3. SEASON VS ACADEMIC YEAR TERMINOLOGY

### Current Term: "Season"

The codebase uses "Season" to mean an Academic Year + Semester combination.

**Display format:** `"A.Y. {academic_year} – {semester"` (e.g., "A.Y. 2025-2026 – 1")

### Season Model Fields

**File:** `app/Models/Season.php`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigint PK | |
| `academic_year` | string(20) | e.g., "2025-2026" |
| `semester` | string(50) | e.g., "1", "2", "Summer" |
| `is_active` | boolean | Default `false` |
| `application_start_date` | date (nullable) | |
| `application_end_date` | date (nullable) | |

### Season Model Relationships

```php
public function applications(): HasMany
public function examSessions(): HasMany
public static function active(): ?self   // Returns first where is_active = true
public function isApplicationWindowOpen(): bool  // Checks date range
public function activate(): void  // Sets this = active, all others = false
```

### All "Season" References (18+ files)

**PHP Models/Controllers:**
- `app/Models/Season.php`
- `app/Models/ExamSession.php` — `season()` BelongsTo
- `app/Models/Application.php` — `season()` BelongsTo
- `app/Http/Controllers/HomeController.php`
- `app/Http/Controllers/Admin/ExamSessionController.php`
- `app/Http/Controllers/Admin/SeasonController.php`
- `app/Http/Controllers/ApplicationController.php`

**Svelte Pages:**
- `resources/js/Pages/Home/Index.svelte`
- `resources/js/Pages/Applications/Apply.svelte`
- `resources/js/Pages/Admin/Seasons/Create.svelte`
- `resources/js/Pages/Admin/Seasons/Edit.svelte`

**Database:**
- `database/migrations/2024_01_01_000006_create_seasons_table.php`
- `database/migrations/2024_01_01_000029_add_foreign_keys.php` (season FK on applications)
- `database/migrations/2024_01_01_000030_add_more_foreign_keys.php` (season FK on exam_sessions)

### All "Season" Display Strings

| File | Display |
|------|---------|
| `Home/Index.svelte` | `"Admissions are open for {activeSeason.name}"` |
| `Applications/Apply.svelte` | `"A.Y. {active_season.academic_year} – {active_season.semester}"` |
| `Admin/Seasons/Index.svelte` | `"A.Y. {season.academic_year} – {season.semester}"` |

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 4. APPLICATION WINDOW

### Homepage Display

**File:** `app/Http/Controllers/HomeController.php` (lines 10–21)

```php
$activeSeason = Season::where('is_active', true)->first();
return Inertia::render('Home/Index', [
    'activeSeason' => $activeSeason ? [
        'name' => 'A.Y. ' . $activeSeason->academic_year,
        'application_start' => ...,
        'application_end' => ...,
    ] : null,
]);
```

**Svelte:** `resources/js/Pages/Home/Index.svelte` (lines 53–58)

```svelte
{#if activeSeason}
  <Badge>Admissions are open for {activeSeason.name}</Badge>
{/if}
```

### Behavior by State

| State | Homepage | /apply page |
|-------|----------|-------------|
| Active season + window open | Shows badge | Shows application form |
| Active season + window closed | Shows badge | Shows "Application window closed" card |
| No active season | No badge | Shows "Application window closed" card |

### Defaulting to Active Season

When `season_id` is not provided in a request:

| Operation | Behavior | Location |
|-----------|----------|----------|
| Create exam session | Defaults to `Season::active()?->id` | `ExamSessionController.php:186` |
| List applications | Defaults to `Season::active()?->id` | `ApplicationController.php:32–38` |

**Edge case:** If NO active season exists, `season_id` becomes `null`.

### Application Status Flow

```
pending
    |
    +---> accepted (accept action)
    |
    +---> dismissed (dismiss action)
    |
    +---> incomplete_documents (setIncompleteDocuments action)
```

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 5. VALIDATION STRENGTH

### StoreExamSessionRequest — ISSUES FOUND

**File:** `app/Http/Requests/StoreExamSessionRequest.php` (lines 14–25)

```php
'season_id'   => ['sometimes', 'nullable', 'integer', 'exists:seasons,id'],
'room_id'     => ['required', 'integer', 'exists:rooms,id'],
'date'        => ['required', 'date'],                    // ⚠️ No past-date check
'start_time'  => ['required', 'string'],                 // ⚠️ NO format validation
'end_time'    => ['nullable', 'string'],                 // ⚠️ NO format validation
'proctor_ids' => ['sometimes', 'array'],
'proctor_ids.*' => ['integer', 'exists:users,id'],
```

**Problems:**
- `start_time` accepts any string — could be `"25:99"` or `"abc"`
- `end_time` accepts any string
- `date` has no `after_or_equal:today` check — sessions could be created for 2020
- Inconsistent with `UpdateExamSessionRequest` which validates `start_time` as `date_format:H:i`

### StoreSeasonRequest — STRONG ✓

**File:** `app/Http/Requests/StoreSeasonRequest.php` (lines 14–22)

```php
'academic_year' => ['required', 'string', 'max:20'],
'semester'      => ['required', 'string', 'max:50'],
'application_start_date' => ['nullable', 'date'],
'application_end_date'   => ['nullable', 'date', 'after_or_equal:application_start_date'],
```

### StoreApplicationRequest — STRONG ✓

**File:** `app/Http/Requests/StoreApplicationRequest.php` (lines 14–34)

- Age range: 15–50 years (`before:-15 years`, `after:-50 years`)
- Email format validated
- Course preferences: `different:` cross-field validation
- Sex: strict `in:male,female`

### Application Birthdate Age Calculation

```php
$age = (int) $birthdate->diffInYears(now());
```

Computed server-side in `ApplicationController::store()` (line 194).

### All Form Requests

| Request | File | Issues |
|---------|-------|--------|
| `StoreExamSessionRequest` | `app/Http/Requests/StoreExamSessionRequest.php` | start_time/end_time no format, date no past check |
| `UpdateExamSessionRequest` | `app/Http/Requests/UpdateExamSessionRequest.php` | start_time has `date_format:H:i` (stronger than store) |
| `StoreSeasonRequest` | `app/Http/Requests/StoreSeasonRequest.php` | OK |
| `StoreApplicationRequest` | `app/Http/Requests/StoreApplicationRequest.php` | OK |
| `StoreConsultationScheduleRequest` | `app/Http/Requests/StoreConsultationScheduleRequest.php` | `scheduled_date` has `after_or_equal:today` ✓ |
| `UpdateScoresRequest` | `app/Http/Requests/UpdateScoresRequest.php` | OK |

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 6. ORPHAN RECORDS & CASCADE

### No Cascade Delete

**Pivot table:** `exam_session_applicant`
- No `onDelete('cascade')` found on foreign keys
- If an exam session is deleted, applicant assignments become orphaned
- **No `SoftDeletes` trait** in any model — all deletes are hard deletes

### Exam Session Deletion

- No explicit `destroy()` method found in `ExamSessionController` for actual deletion
- Routes exist for `DELETE /admin/test-scheduling/{exam_session}` but controller method not found in initial review — **needs verification**

### Applicant Double-Assignment Prevention

**Migration:** `2024_01_01_000017_create_exam_session_applicant_table.php`

```php
$table->unique(['exam_session_id', 'applicant_id']);
$table->unique('applicant_id');  // Prevents applicant in multiple sessions
```

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 7. SCHEDULER & AUTO-EXPIRY

### ExpireSeasonApplications Command — NOT SCHEDULED

**File:** `app/Console/Commands/ExpireSeasonApplications.php`

**Command:** `php artisan seasons:expire-applications`

**Action:** Marks pending applications as dismissed when season's application window closes.

**Problem:** This command is **NOT registered in any `Kernel.php` `schedule()` method**. Applications never auto-expire.

### Queue Jobs

**File:** `app/Jobs/SendApplicantSetupEmail.php`
- Implements `ShouldQueue`
- Sends `ApplicantSetupMail` to accepted applicants

### No Other Scheduled Tasks

No other recurring jobs, cron tasks, or scheduled commands found.

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 8. ROLE-BASED ACCESS CONTROL

### Roles

| Role | Description |
|------|-------------|
| `super_admin` | Full system access |
| `admin` | Administrative access |
| `staff` | Registrar office — applications only |
| `test_administrator` | Exam sessions, grading |
| `proctor` | Can proctor sessions, scan attendance |
| `applicant` | Public applicant portal |

### ExamSession Policy

| Action | super_admin | admin | proctor | test_administrator |
|--------|:-----------:|:-----:|:-------:|:------------------:|
| viewAny | ✓ | ✓ | assigned only | ✗ |
| view | ✓ | ✓ | assigned only | ✗ |
| create | ✓ | ✓ | ✗ | ✗ |
| update | ✓ | ✓ | ✗ | ✗ |
| delete | ✓ | ✓ | ✗ | ✗ |
| viewRoster | ✓ | ✓ | assigned only | ✗ |
| manageRoster | ✓ | ✓ | assigned only | ✗ |
| unpublish | ✓ | ✓ | ✗ | ✗ |
| reopen | ✓ | ✓ | ✗ | ✗ |

### Navigation Visibility by Role

**File:** `resources/js/Layouts/AuthenticatedLayout.svelte` (lines 45–70)

```
Dashboard              → all authenticated
Registrar Office:
  /admin/seasons       → super_admin, admin
  /applications        → super_admin, admin, staff, test_administrator
  /admin/test-scheduling → super_admin, admin
Guidance Office:
  /admin/test-scheduling (My Sessions) → proctor
  /admin/test-scheduling/monitoring → super_admin, test_administrator, proctor
  /grading             → super_admin, test_administrator
Administration:
  /admin/users, rooms, courses, etc. → super_admin only
```

### Applicant vs Staff Auth — Two Separate Systems

| System | Guard | Table | Flow |
|--------|-------|-------|------|
| Staff | `Auth::guard('default')` | `users` | Email + password, session |
| Applicant | `Auth::guard('applicant')` | `applicants` | Email + password, token setup (72h), password reset (15min token) |

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 9. BREADCRUMB HEADER

### How It Works

**File:** `resources/js/Layouts/AuthenticatedLayout.svelte` (lines 222–249)

- If `breadcrumbs` array is empty → shows `pageTitle`
- If 1 crumb → shows label
- If 2+ crumbs → renders full breadcrumb nav with chevrons

### Pages WITH Breadcrumbs

- [x] `Proctor/SessionRoster.svelte` — `[{label: 'My Sessions', href: '/...'}, {label: 'Session #' + id}]`
- [x] `Admin/TestScheduling/Show.svelte` — conditional based on `view=proctor`
- [x] `Applications/Index.svelte` — `[{label: 'Applications'}]`

### Pages WITHOUT Breadcrumbs (TO DO)

- [ ] `Applications/Show.svelte` — needs breadcrumbs
- [ ] `Applications/PrintSlips.svelte` — needs breadcrumbs

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 10. PAGINATION & PERFORMANCE

| Resource | Page Size |
|----------|:---------:|
| Applications | 15 |
| Exam Sessions | 15 |
| Audit Logs | 25 |
| Consultations | 20 |
| Courses | 15 |
| Rooms | 15 |
| Knowledge Documents | 15 |
| **Users** | **NOT PAGINATED** ⚠️ |

### Users Index — Needs Fix

`UserController@index` does not paginate — uses `User::all()` or similar. For large user lists, this is a performance issue.

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 11. HARDCODED BUSINESS VALUES

| Value | Number | Location |
|-------|:------:|---------|
| Grace period before start | 15 min | `ExamSession::isWithinStartWindow()` |
| Grace period after end | 30 min | `ExamSession::isWithinStartWindow()` |
| Setup token expiry | 72 hours | `Applicant` model |
| Password reset token length | 64 chars | `PortalAuthController` |
| Password reset token expiry | 15 min | `PortalAuthController` |
| Login throttle attempts | 5 | `AppServiceProvider` |
| Login throttle decay | 15 min | `AppServiceProvider` |
| Application age min | 15 years | `StoreApplicationRequest` |
| Application age max | 50 years | `StoreApplicationRequest` |
| AI max chat history | 20 msgs | `AiCompanionService::DEFAULT_MAX_HISTORY` |
| OpenRouter max tokens (chat) | 1024 | `AiCompanionService` |
| OpenRouter max tokens (schedule) | 2048 | `ExamSchedulingAssistantService` |
| Knowledge max docs | 10 | `KnowledgeRetrievalService::DEFAULT_MAX_DOCS` |
| Knowledge max chars | 8000 | `KnowledgeRetrievalService::DEFAULT_MAX_TOTAL_CHARS` |
| CSV max rows | 5000 | `CsvToNarrativeService::MAX_ROWS` |
| CSV max file size | 2MB | `CsvToNarrativeService::MAX_FILE_SIZE_BYTES` |

### Should Be Configurable (Env/DB)

- [ ] `application.age_min` / `application.age_max` — currently in form request validation
- [ ] `session.grace_minutes_before` / `session.grace_minutes_after`
- [ ] `ai.max_history`
- [ ] `ai.max_tokens`

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 12. AUTHENTICATION FLOWS

### Staff Login

```php
Auth::attempt($credentials)  // email + password
session()->regenerate()       // on success
Log::info('User login', ...)  // audit
Log::info('User login failed', ...)  // on failure
```

### Applicant Setup Flow

```
1. Application accepted (by admin/staff)
2. System generates setup_token (64 random chars, 72h expiry)
3. SendApplicantSetupEmail queued → applicant clicks link
4. GET /portal/setup/{token} → validates token + expiry
5. POST /portal/setup (PortalSetupRequest) → sets password
6. Redirect /portal/dashboard
```

### Applicant Password Reset

```
1. POST /portal/forgot-password → generates 64-char token (15min expiry)
2. Send ApplicantResetPasswordMail → applicant clicks link
3. GET /portal/reset/{token} → validates token + expiry
4. POST /portal/reset → updates applicants.password
```

### Email Enumeration Prevention

`PortalAuthController::forgotPassword()` — always returns 200, even if email not found in `applicants` table.

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 13. FILE UPLOADS

| Type | Max Size | MIME | Route |
|------|:--------:|------|-------|
| CSV (knowledge import) | 2MB | csv, txt | `POST /admin/knowledge-documents/import` |
| DOCX (admission slip) | 5MB | docx | `POST /admin/admission-slip-templates` |
| DOCX (result sheet) | 5MB | docx | `POST /admin/result-sheet-templates` |

**No file uploads for:**
- Profile photos
- Application documents
- Any other user-generated content

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 14. AI INTEGRATION

### OpenRouter Integration

**Config:** `config/services.php`
```php
'openrouter' => [
    'key'   => env('OPENROUTER_KEY'),
    'model' => env('OPENROUTER_MODEL', 'openrouter/free'),
],
```

### AI Companion (Applicant Portal)

**Service:** `app/Services/AiCompanionService.php`
- Builds system prompt with applicant summary
- Max 20 message history
- 1024 max output tokens
- Role: `user` or `assistant`
- Rate limit (429) → user-friendly message: "AI service rate limit exceeded. Please try again later."

**Controller:** `app/Http/Controllers/AiCompanionController.php`
- `GET /portal/ai-companion` — renders chat UI
- `POST /portal/ai-companion/chat` — sends message
- `DELETE /portal/ai-companion/clear` — clears history

### Exam Scheduling Assistant (Admin)

**Service:** `app/Services/ExamSchedulingAssistantService.php`
- Structured JSON schema for schedule suggestions
- 2048 max output tokens
- Used via `POST /ai-scheduling/chat` and `POST /ai-scheduling/apply` (API routes)

### AI Error Handling Pattern

```php
catch (ErrorData $e) {
    throw new RuntimeException(
        match ($e->code) {
            429 => 'AI service rate limit exceeded. Please try again later.',
            default => 'AI service unavailable. Please try again later.',
        }
    );
}
```

**⚠️ Issue:** Original error is discarded — makes debugging harder.

### Rate Limiting

- No explicit rate limit on `/portal/ai-companion/chat`
- API routes `/ai-scheduling/*` have no rate limit middleware visible

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 15. NOTIFICATION ARCHITECTURE

### No Notification Classes

No `app/Notifications/` classes found. Email sent via Mailable classes directly:

| Mailable | View | Subject |
|----------|------|---------|
| `ApplicantSetupMail` | `emails.applicant-setup` | "SecureCAT — Set Up Your Applicant Portal Account" |
| `ApplicantResetPasswordMail` | `emails.applicant-reset-password` | "SecureCAT — Reset Your Applicant Portal Password" |

### Publish → No Notifications

As noted in Section 1, publishing an exam session does NOT send notifications to assigned applicants. This was in the architecture docs but never implemented.

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 16. ERROR HANDLING & LOGGING

### Logged Events

| Event | File | Level |
|-------|------|-------|
| User login | `AuthController.php` | INFO |
| User login failed | `AuthController.php` | INFO |
| User logout | `AuthController.php` | INFO |
| User created/updated/deleted | `UserController.php` | INFO |
| Application accepted | `ApplicationController.php` | INFO |
| Application dismissed | `ApplicationController.php` | INFO |
| Application incomplete | `ApplicationController.php` | INFO |
| Setup email resent | `ApplicationController.php` | INFO |
| AI Companion chat error | `AiCompanionController.php` | WARNING |
| AI Scheduling chat error | `ExamSchedulingAssistantController.php` | WARNING |
| AI Scheduling apply error | `ExamSchedulingAssistantController.php` | ERROR |

### Generic Error → Generic Message

Services catch specific AI errors and rethrow as `RuntimeException` with user-friendly messages — original stack trace lost in production.

### Global Handler

Standard Laravel `app/Exceptions/Handler.php`.

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 17. ENTITY STATUS MACHINES

### ExamSession

```
draft ──publish──> published ──start──> in_progress ──close──> completed
  ^                    │
  │_unpublish_         │
                        └───────cancel──> cancelled
  ^                                           │
  |_reopen_ (from completed/cancelled)________|
```

**Actions:**
- `publish` — admin only
- `unpublish` — admin only, only from published
- `reopen` — admin only, from completed or cancelled

### Application

```
pending ──accept──> accepted
  │
  ├─dismiss──> dismissed
  │
  └─setIncompleteDocuments──> incomplete_documents ──accept──> accepted
```

### GradingSession

```
open ──openForExamSession──> in_progress ──saveScores──> review ──finalize──> finalized
```

### ConsultationSummary

```
pending ──> draft ──release──> released
```

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 18. DATABASE SCHEMA

### All Tables (26 total)

| Table | Migration | Key Fields |
|-------|-----------|-----------|
| `departments` | `2024_01_01_000001` | id, name, code |
| `courses` | `2024_01_01_000002` | department_id FK, name, code, **score_cutoff**, **quota**, is_active |
| `roles` | `2024_01_01_000003` | id, name, display_name |
| `users` | `2024_01_01_000004` | id, name, email, password |
| `role_user` | `2024_01_01_000005` | user_id FK, role_id FK |
| `seasons` | `2024_01_01_000006` | academic_year, semester, is_active, application_start_date, application_end_date |
| `rooms` | `2024_01_01_000007` | name, building, floor, capacity, **facilities (JSON)**, is_active |
| `exam_domains` | `2024_01_01_000008` | id, name, code, description, max_score, weight |
| `applications` | `2024_01_01_000009` | reference_number, season_id FK, names, birthdate, age, sex, email, phone, address, course_preference_1/2/3 FK, status, processed_by FK |
| `exam_sessions` | `2024_01_01_000010` | season_id FK, room_id FK, date, start_time, end_time, status, published_at, started_at, closed_at, score_release_date, created_by FK |
| `exam_session_user` | `2024_01_01_000011` | exam_session_id FK, user_id FK (proctors) |
| `applicants` | `2024_01_01_000012` | application_id FK, email (unique), password, setup_token, setup_token_expires_at |
| `applicant_password_reset_tokens` | `2024_01_01_000013` | email PK, token, expires_at |
| `grading_sessions` | `2024_01_01_000014` | exam_session_id FK, status, opened_at, finalized_at |
| `grading_session_applicant` | `2024_01_01_000015` | grading_session_id FK, applicant_id FK |
| `applicant_scores` | `2024_01_01_000016` | grading_session_id FK, applicant_id FK, domain_id FK, raw_score, max_score, normalized_score |
| `exam_session_applicant` | `2024_01_01_000017` | exam_session_id FK, applicant_id FK, attendance_status, submission_status |
| `admission_slip_templates` | `2024_01_01_000018` | name, mode, paper_size, orientation, logical_unit, content, is_active |
| `result_sheet_templates` | `2024_01_01_000019` | name, content, is_active |
| `consultation_summaries` | `2024_01_01_000020` | applicant_id FK (unique), status, recommended_course_id FK, counselor_comments |
| `consultation_schedules` | `2024_01_01_000021` | scheduled_date, grading_session_id FK |
| `consultation_schedule_applicant` | `2024_01_01_000022` | consultation_schedule_id FK, applicant_id FK |
| `audit_logs` | `2024_01_01_000023` | auditable_type/id, event, old_values/new_values JSON, actor_type/id |
| `ai_companion_messages` | `2024_01_01_000024` | applicant_id FK, role, content |
| `exam_scheduling_conversations` | `2024_01_01_000025` | user_id FK, messages JSON |
| `appointments` | `2024_01_01_000026` | date, time_slot, duration_minutes (default 30), booked_count, is_active |
| `system_settings` | `2024_01_01_000027` | key PK, value |
| `knowledge_documents` | `2024_01_01_000028` | title, content, metadata JSON, source, is_active |

### All Foreign Keys

```
applications.season_id          → seasons.id
applications.processed_by       → users.id
applications.appointment_id     → appointments.id
applications.course_preference_1 → courses.id
applications.course_preference_2 → courses.id
applications.course_preference_3 → courses.id
exam_sessions.season_id         → seasons.id
exam_sessions.room_id           → rooms.id
exam_sessions.created_by        → users.id
exam_session_user.exam_session_id → exam_sessions.id
exam_session_user.user_id       → users.id
applicants.application_id       → applications.id
grading_sessions.exam_session_id → exam_sessions.id
grading_sessions.opened_by      → users.id
grading_sessions.finalized_by    → users.id
applicant_scores.grading_session_id → grading_sessions.id
applicant_scores.applicant_id   → applicants.id
applicant_scores.domain_id      → exam_domains.id
applicant_scores.scored_by      → users.id
consultation_summaries.applicant_id → applicants.id
consultation_summaries.recommended_course_id → courses.id
consultation_summaries.counselor_id → users.id
consultation_summaries.released_by → users.id
consultation_schedules.grading_session_id → grading_sessions.id
consultation_schedules.created_by → users.id
consultation_schedule_applicant.consultation_schedule_id → consultation_schedules.id
consultation_schedule_applicant.applicant_id → applicants.id
courses.department_id           → departments.id
ai_companion_messages.applicant_id → applicants.id
exam_scheduling_conversations.user_id → users.id
```

### All Unique Indexes

| Table | Unique On |
|-------|-----------|
| courses | code |
| roles | name |
| users | email |
| rooms | [building, name] |
| appointments | [date, time_slot] |
| applicants | email |
| applicants | application_id |
| applicants | setup_token |
| exam_session_applicant | [exam_session_id, applicant_id] |
| exam_session_applicant | applicant_id |
| applicant_scores | [grading_session_id, applicant_id, domain_id] |
| consultation_summaries | applicant_id |
| departments | code |

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## 19. USER AMENDMENTS & PLANNED CHANGES

> Recorded 2026-04-07. These are binding decisions that override any conflicting aspirational documentation.

### AMENDMENT 1: Attendance Requires `in_progress`, Not Just `published`

**Current behavior:** Proctors can scan attendance when session is `published` or `in_progress`.

**Decision:** Attendance marking (and by extension scoring) should ONLY be possible when session is `in_progress`.

**Reason:** Published sessions are not yet active. Only actively in-progress sessions should allow proctor actions.

**Action required:** Update `SessionRosterController.php` to check `STATUS_IN_PROGRESS` only.

---

### AMENDMENT 2: Remove Admission Slip & QR Scan Feature

**Current behavior:** Admission slips contain QR codes linking to `/admission/{reference_number}`.

**Decision:** Deferred indefinitely. Do not build.

**Actions required:**
- Remove QR code generation from `AdmissionSlipService` and `QrCodeService`
- Remove `QrCodeService` entirely
- Remove QR-related placeholders from admission slip templates
- Applicant lookup for scoring/attendance → use **name search** and **reference number search** instead

---

### AMENDMENT 3: Room Index — Activation Toggle in Action Column

**Current behavior:** Activation/deactivation of rooms requires navigating to the Edit page.

**Decision:** Add an activation toggle button directly in the actions column of the rooms table.

**Actions required:**
- Update `resources/js/Pages/Admin/Rooms/Index.svelte` to add activate/deactivate button in actions column
- Add `PATCH /admin/rooms/{room}/toggle-active` or similar endpoint, or reuse existing update endpoint with `is_active` field

---

### AMENDMENT 4: Course Index — Activation Toggle in Action Column

**Current behavior:** Activation/deactivation of courses requires navigating to the Edit page.

**Decision:** Add an activation toggle button directly in the actions column of the courses table.

**Actions required:**
- Update `resources/js/Pages/Admin/Courses/Index.svelte` to add activate/deactivate button in actions column

---

### AMENDMENT 5: Remove `facilities` Field from Rooms

**Current behavior:** `rooms` table has `facilities` JSON column storing room facilities.

**Decision:** Remove this field entirely.

**Actions required:**
- Migration to drop `facilities` column from `rooms` table
- Remove from `RoomController` create/update validation
- Remove from `Room` model `$fillable`
- Remove from Svelte form (`Admin/Rooms/Create.svelte`, `Edit.svelte`)
- Remove from rooms table UI if displayed anywhere

---

### AMENDMENT 6: Remove `score_cutoff` Field from Courses

**Current behavior:** `courses` table has `score_cutoff` column.

**Decision:** Remove this field entirely.

**Actions required:**
- Migration to drop `score_cutoff` column from `courses` table
- Remove from `CourseController` create/update validation
- Remove from `Course` model `$fillable`
- Remove from Svelte form (`Admin/Courses/Create.svelte`, `Edit.svelte`)

---

### AMENDMENT 7: Remove `quota` Field from Courses

**Current behavior:** `courses` table has `quota` column.

**Decision:** Remove this field entirely.

**Actions required:**
- Migration to drop `quota` column from `courses` table
- Remove from `CourseController` create/update validation
- Remove from `Course` model `$fillable`
- Remove from Svelte form

---

### AMENDMENT 8: Remove `department` Field from Courses

**Current behavior:** `courses` table has `department_id` foreign key to `departments` table.

**Decision:** Remove this field and the `departments` relationship entirely. Courses are no longer tied to departments.

**Actions required:**
- Migration to drop `department_id` column from `courses` table
- Migration to drop `departments` table (or leave if other uses found)
- Remove `department()` relationship from `Course` model
- Remove `courses()` relationship from `Department` model
- Remove department from `CourseController` create/update validation
- Remove from Svelte form
- Check all places where `department` is displayed or filtered — remove

---

### AMENDMENT 9: Add `date_format:H:i` Validation to `start_time` on Create

**Current behavior:** `StoreExamSessionRequest` validates `start_time` as string only.

**Decision:** Add `date_format:H:i` validation on create (matching update validation).

**Actions required:**
- Update `app/Http/Requests/StoreExamSessionRequest.php` to add format validation

---

### AMENDMENT 10: Add Past-Date Prevention on Exam Session `date`

**Current behavior:** `date` field has no `after_or_equal:today` check.

**Decision:** Add `after_or_equal:today` validation to prevent creating sessions in the past.

**Actions required:**
- Update `StoreExamSessionRequest.php` and `UpdateExamSessionRequest.php`

---

### AMENDMENT 11: Schedule `ExpireSeasonApplications` Command

**Current behavior:** The `seasons:expire-applications` command exists but is never run automatically.

**Decision:** Register it in the scheduler to run daily (or at season end).

**Actions required:**
- Add to `app/Console/Kernel.php` `schedule()` method

---

### AMENDMENT 12: Paginate Users Index

**Current behavior:** `UserController@index` loads all users at once.

**Decision:** Paginate the users list (use 15 per page, matching other resources).

---

### AMENDMENT 13: Rename "Season" to "Academic Year" (Display Only)

**Decision:** The internal term "season" is acceptable in code. Only the **display/UI labels** should say "Academic Year" instead.

**Actions required (display only):**
- `Home/Index.svelte` — "Admissions are open for A.Y. ..."
- `Applications/Apply.svelte` — "A.Y. ..."
- `Admin/Seasons/Index.svelte` — column header labels
- Any other UI labels showing "season"

> Do NOT rename database columns, model names, or controller/variable names.

**[⬆ Back to Table of Contents](#table-of-contents)**

---

## QUICK REFERENCE: ALL 78 ROUTES

### Public (14)
```
GET  /                        → HomeController@index
GET  /about                   → HomeController@about
GET  /login                   → AuthController@showLoginForm
POST /login                   → AuthController@login
POST /logout                  → AuthController@logout
GET  /apply                   → ApplicationController@create
POST /apply                   → ApplicationController@store
GET  /portal/setup/{token}    → PortalAuthController@setup
POST /portal/setup            → PortalAuthController@setupStore
GET  /portal/login            → PortalAuthController@showLoginForm
POST /portal/login            → PortalAuthController@login
POST /portal/forgot-password  → PortalAuthController@forgotPassword
GET  /portal/reset/{token}   → PortalAuthController@reset
POST /portal/reset            → PortalAuthController@resetStore
```

### Authenticated Staff (62)
```
Dashboard & Admin:
  GET  /dashboard
  GET  /admin/test-scheduling
  GET  /admin/test-scheduling/create
  POST /admin/test-scheduling
  GET  /admin/test-scheduling/{exam_session}/edit
  PUT  /admin/test-scheduling/{exam_session}
  DELETE /admin/test-scheduling/{exam_session}
  GET  /admin/test-scheduling/{exam_session}/monitoring
  GET  /admin/test-scheduling/{exam_session}/roster
  POST /admin/test-scheduling/{exam_session}/publish
  POST /admin/test-scheduling/{exam_session}/unpublish
  POST /admin/test-scheduling/{exam_session}/reopen
  GET  /admin/test-scheduling/{exam_session}/attendance
  POST /admin/test-scheduling/{exam_session}/attendance
  POST /admin/test-scheduling/{exam_session}/submission
  POST /admin/test-scheduling/{exam_session}/submission-bulk

Applications:
  GET  /applications
  GET  /applications/{application}
  POST /applications/{application}/accept
  POST /applications/{application}/dismiss
  POST /applications/{application}/incomplete-documents
  POST /applications/{application}/resend-setup-email
  GET  /applications/admission-slip/{application}
  GET  /applications/admission-slip-bulk
  POST /applications/admission-slip-bulk/print-slips

Rooms, Courses, Seasons, Exam Domains:
  CRUD for /admin/rooms, /admin/courses, /admin/seasons, /admin/exam-domains

Knowledge Documents:
  CRUD + import for /admin/knowledge-documents

Templates:
  CRUD for /admin/admission-slip-templates, /admin/result-sheet-templates

Grading:
  GET  /grading
  GET  /grading/sessions/{grading_session}
  POST /grading/sessions/{grading_session}/scores
  GET  /grading/sessions/{grading_session}/print-batch
  GET  /grading/sessions/{grading_session}/result-sheet
  GET  /grading/sessions/{grading_session}/result-sheet-bulk

Consultation:
  GET  /consultation
  GET  /consultation/day
  POST /consultation/schedule
  GET  /consultation/applicant/{applicant}
  POST /consultation/summary/{applicant}/release

Proctor:
  GET  /proctor
  GET  /proctor/session/{exam_session}
  POST /proctor/session/{exam_session}/attendance
  POST /proctor/session/{exam_session}/submission

Settings & Logs:
  GET  /admin/settings, PUT /admin/settings
  GET  /admin/logs
  CRUD for /admin/users
```

### API (2)
```
POST /api/ai-scheduling/chat      → ExamSchedulingAssistantController@chat
POST /api/ai-scheduling/apply     → ExamSchedulingAssistantController@applySchedule
```

### Applicant Portal (4)
```
GET  /portal/dashboard             → PortalDashboardController@index
GET  /portal/ai-companion          → AiCompanionController@index
POST /portal/ai-companion/chat     → AiCompanionController@chat
DELETE /portal/ai-companion/clear  → AiCompanionController@clear
```

**[⬆ Back to Table of Contents](#table-of-contents)**
