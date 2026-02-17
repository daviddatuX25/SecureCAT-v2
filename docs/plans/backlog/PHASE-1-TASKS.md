# SecureCAT — Phase 1 Tasks (Bead-Ready)

This document contains implementation tasks sized for 1-2 hour sprints. Each task is bead-ready with explicit dependencies and testable acceptance criteria.

> **Mockup-First**: Tasks prefixed with `[MOCK]` are mockup/UI tasks. Tasks prefixed with `[BACK]` are backend tasks. Follow mockup → backend order within each module.

---

## Foundation Module (MOD-01)

### BD-001: Project Scaffolding
**Type**: Foundation  
**Dependencies**: None  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Laravel 12 project initialized
- [ ] Sail configured with PHP 8.2, MySQL 8, Node 20
- [ ] `.env.example` configured for development
- [ ] `sail up` starts containers successfully
- [ ] `sail artisan` works

**Context**: 01-SYSTEM-OVERVIEW.md (Technology Stack)

---

### BD-002: Inertia + Svelte 5 Setup
**Type**: Foundation  
**Dependencies**: BD-001  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Inertia.js v2 installed and configured
- [ ] Svelte 5 configured with runes syntax
- [ ] `@inertiajs/svelte` v2 installed
- [ ] Vite configured with Svelte plugin
- [ ] Test page renders via Inertia
- [ ] Path aliases configured (`@/` → `resources/js/`)

**Context**: stack-conventions.mdc, developing-gotchas.mdc

---

### BD-003: TailwindCSS 4 + shadcn-svelte Setup
**Type**: Foundation  
**Dependencies**: BD-002  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] TailwindCSS 4 installed and configured
- [ ] shadcn-svelte initialized
- [ ] Core components installed (Button, Input, Card, Form)
- [ ] Component path alias works (`@/Components/ui`)
- [ ] Test component renders with styling

**Context**: stack-conventions.mdc, developing-gotchas.mdc

---

### BD-004: Database Migrations - Core Tables
**Type**: Foundation  
**Dependencies**: BD-001  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] users table migration
- [ ] roles table migration
- [ ] role_user pivot migration
- [ ] permissions table migration (optional)
- [ ] departments table migration
- [ ] courses table migration
- [ ] Migrations run without errors

**Context**: 04-DATA-MODEL.md (users, roles, departments, courses)

---

### BD-005: Database Migrations - Application Tables
**Type**: Foundation  
**Dependencies**: BD-004  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] applicants table migration
- [ ] applications table migration
- [ ] appointments table migration
- [ ] All foreign keys valid

**Context**: 04-DATA-MODEL.md (applicants, applications, appointments)

---

### BD-006: Database Migrations - Scheduling Tables
**Type**: Foundation  
**Dependencies**: BD-004  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] rooms table migration
- [ ] proctors table migration
- [ ] exam_sessions table migration
- [ ] exam_session_proctor pivot migration
- [ ] session_applicants table migration

**Context**: 04-DATA-MODEL.md (rooms, proctors, exam_sessions, session_applicants)

---

### BD-007: Database Migrations - Grading & Consultation Tables
**Type**: Foundation  
**Dependencies**: BD-004  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] exam_domains table migration
- [ ] grading_sessions table migration
- [ ] applicant_scores table migration
- [ ] score_items table migration
- [ ] decision_rules table migration
- [ ] consultation_summaries table migration

**Context**: 04-DATA-MODEL.md (grading, consultation tables)

---

### BD-008: Database Migrations - System Tables
**Type**: Foundation  
**Dependencies**: BD-004  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] notifications table migration (Laravel default + customizations)
- [ ] audit_logs table migration
- [ ] All migrations run in sequence

**Context**: 04-DATA-MODEL.md (notifications, audit_logs)

---

### BD-009: Database Seeders - Roles & Permissions
**Type**: Foundation  
**Dependencies**: BD-004  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] Seed 6 roles: super_admin, staff, admin, proctor, grader, counselor
- [ ] Seed Super Admin user (configurable via .env)
- [ ] Seeder is idempotent (can run multiple times)

**Context**: 04-DATA-MODEL.md (roles seed data), 05-SECURITY-CONTROLS.md

---

### BD-010: Database Seeders - Reference Data
**Type**: Foundation  
**Dependencies**: BD-004, BD-009  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] Seed sample departments (3-5)
- [ ] Seed sample courses (5-10 across departments)
- [ ] Seed exam domains (6 domains with placeholder names)
- [ ] Seed sample rooms (5-10)
- [ ] Seeder configurable for dev vs production

**Context**: 04-DATA-MODEL.md (departments, courses, exam_domains, rooms)

---

### BD-011: Eloquent Models - Core
**Type**: Foundation  
**Dependencies**: BD-004  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] User model with role relationship
- [ ] Role model with users relationship
- [ ] Department model
- [ ] Course model with department relationship
- [ ] Fillable, casts, and relationships defined

**Context**: 04-DATA-MODEL.md

---

### BD-012: Eloquent Models - Application
**Type**: Foundation  
**Dependencies**: BD-005, BD-011  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Applicant model with application relationship
- [ ] Application model with all relationships (courses, appointment, applicant)
- [ ] Appointment model
- [ ] Reference number generation accessor

**Context**: 04-DATA-MODEL.md

---

### BD-013: Eloquent Models - Scheduling & Examination
**Type**: Foundation  
**Dependencies**: BD-006, BD-011  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Room model
- [ ] Proctor model with optional user relationship
- [ ] ExamSession model with all relationships
- [ ] SessionApplicant model (pivot with extra attributes)

**Context**: 04-DATA-MODEL.md

---

### BD-014: Eloquent Models - Grading & Consultation
**Type**: Foundation  
**Dependencies**: BD-007, BD-011  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] ExamDomain model
- [ ] GradingSession model
- [ ] ApplicantScore model
- [ ] ScoreItem model
- [ ] DecisionRule model
- [ ] ConsultationSummary model

**Context**: 04-DATA-MODEL.md

---

### BD-015: Authentication - Staff Login
**Type**: Foundation  
**Dependencies**: BD-002, BD-003, BD-011  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Login controller with validation
- [ ] Login Form Request
- [ ] Session-based authentication
- [ ] Rate limiting (5 attempts / 15 min)
- [ ] Login event logged to audit

**Context**: 05-SECURITY-CONTROLS.md, 08-API-SPEC-PHASE1.md (POST /login)

---

### BD-016: [MOCK] Guest Layout + Login Page
**Type**: Foundation (UI)  
**Dependencies**: BD-002, BD-003  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] GuestLayout.svelte created (centered card, logo)
- [ ] Login.svelte page with email, password, remember checkbox
- [ ] Form uses shadcn components
- [ ] Validation error display
- [ ] Responsive design

**Context**: 09-UI-ROUTES-PHASE1.md (Staff Login)

---

### BD-017: [MOCK] Authenticated Layout + Dashboard Shell
**Type**: Foundation (UI)  
**Dependencies**: BD-016  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] AuthenticatedLayout.svelte with sidebar
- [ ] Role-aware navigation (show/hide based on roles prop)
- [ ] User dropdown with logout
- [ ] Dashboard.svelte shell page
- [ ] Responsive sidebar (collapsible on mobile)

**Context**: 09-UI-ROUTES-PHASE1.md (Layouts, Navigation Structure)

---

### BD-018: Authorization - Role Middleware & Helpers
**Type**: Foundation  
**Dependencies**: BD-009, BD-011  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] `role` middleware to check user roles
- [ ] User model `hasRole()` method
- [ ] User model `hasAnyRole()` method
- [ ] Inertia shares user roles to frontend
- [ ] Test: middleware blocks unauthorized access

**Context**: 05-SECURITY-CONTROLS.md (Authorization Model)

---

### BD-019: [MOCK] User Management Pages (Super Admin)
**Type**: Foundation (UI)  
**Dependencies**: BD-017  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] Users/Index.svelte — data table with search, role filter
- [ ] Users/Create.svelte — form with role multi-select
- [ ] Users/Edit.svelte — edit form
- [ ] Delete confirmation modal
- [ ] Uses shadcn Table, Input, Select, Button

**Context**: 09-UI-ROUTES-PHASE1.md (User Management)

---

### BD-020: [BACK] User Management API
**Type**: Foundation  
**Dependencies**: BD-018, BD-015  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] UserController with index, store, update, destroy
- [ ] StoreUserRequest, UpdateUserRequest Form Requests
- [ ] Role assignment on create/update
- [ ] Cannot delete self, cannot remove own super_admin
- [ ] Audit logging on user changes
- [ ] Tests for CRUD + authorization

**Context**: 08-API-SPEC-PHASE1.md (/admin/users/*)

---

### BD-021: Audit Logging Service
**Type**: Foundation  
**Dependencies**: BD-008, BD-011  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] AuditLog model
- [ ] AuditService with `log()` method
- [ ] Model observer or trait for auto-logging
- [ ] Captures: actor, event, old/new values, IP, timestamp
- [ ] Logs are immutable (no update/delete)

**Context**: 04-DATA-MODEL.md (audit_logs), 05-SECURITY-CONTROLS.md (Audit Requirements)

---

---

## Application Module (MOD-02)

### BD-022: [MOCK] Public Application Form
**Type**: Feature (UI)  
**Dependencies**: BD-016, BD-003  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] Apply.svelte with multi-section form
- [ ] Personal info section (name, birthdate, sex)
- [ ] Contact section (email, phone, address)
- [ ] Course preferences section (3 dropdowns, no duplicates)
- [ ] Optional appointment slot picker
- [ ] Form validation indicators
- [ ] Mobile responsive

**Context**: 09-UI-ROUTES-PHASE1.md (Public Application Form)

---

### BD-023: [MOCK] Application Success Page
**Type**: Feature (UI)  
**Dependencies**: BD-022  
**Estimate**: 30 min

**Acceptance Criteria**:
- [ ] Success.svelte showing reference number
- [ ] Appointment details (if booked)
- [ ] Clear call-to-action / next steps

**Context**: 09-UI-ROUTES-PHASE1.md (Application Success)

---

### BD-024: [BACK] Application Submission API
**Type**: Feature  
**Dependencies**: BD-012, BD-021  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] ApplicationController@store
- [ ] StoreApplicationRequest with all validations
- [ ] Reference number generation (APP-YEAR-SEQUENCE)
- [ ] Course preference duplicate validation
- [ ] Appointment booking increment (if provided)
- [ ] Rate limiting (10/hour per IP)
- [ ] Audit log on submission
- [ ] Tests for happy path + validation errors

**Context**: 08-API-SPEC-PHASE1.md (POST /applications)

---

### BD-025: [MOCK] Applications List (Staff)
**Type**: Feature (UI)  
**Dependencies**: BD-017  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Applications/Index.svelte with data table
- [ ] Search by name/reference
- [ ] Filter by status (pending, accepted, rejected)
- [ ] Date range filter
- [ ] Pagination
- [ ] Click row → details

**Context**: 09-UI-ROUTES-PHASE1.md (Applications List)

---

### BD-026: [MOCK] Application Details + Actions
**Type**: Feature (UI)  
**Dependencies**: BD-025  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Applications/Show.svelte with full applicant info
- [ ] Course preferences displayed with ranks
- [ ] Accept button (staff only)
- [ ] Reject button with modal for reason
- [ ] Download admission slip button (if accepted)
- [ ] Status badge

**Context**: 09-UI-ROUTES-PHASE1.md (Application Details)

---

### BD-027: [BACK] Application List & Details API
**Type**: Feature  
**Dependencies**: BD-012, BD-018  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] ApplicationController@index with filters
- [ ] ApplicationController@show
- [ ] ApplicationPolicy for authorization
- [ ] Eager load relationships (courses, appointment)
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (GET /applications, GET /applications/{id})

---

### BD-028: [BACK] Accept Application + Account Provisioning
**Type**: Feature  
**Dependencies**: BD-027, BD-021  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] ApplicationController@accept
- [ ] Status validation (must be pending)
- [ ] Create Applicant record with setup token
- [ ] Queue setup email job
- [ ] Audit log on acceptance
- [ ] Tests for accept flow

**Context**: 08-API-SPEC-PHASE1.md (PUT /applications/{id}/accept)

---

### BD-029: [BACK] Reject Application
**Type**: Feature  
**Dependencies**: BD-027  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] ApplicationController@reject
- [ ] Rejection reason required
- [ ] Status validation (must be pending)
- [ ] Audit log
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (PUT /applications/{id}/reject)

---

### BD-030: [BACK] Admission Slip PDF Generation
**Type**: Feature  
**Dependencies**: BD-028  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] PDF generation service (DomPDF or similar)
- [ ] Slip includes: reference number, name, photo placeholder, QR code placeholder
- [ ] ApplicationController@admissionSlip endpoint
- [ ] Authorization: staff or applicant (own)
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (GET /applications/{id}/admission-slip)

---

### BD-031: [BACK] Appointment Slots API
**Type**: Feature  
**Dependencies**: BD-012  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] AppointmentController with index, store
- [ ] Public listing (for application form)
- [ ] Admin create endpoint
- [ ] Available slot calculation
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/appointments/*)

---

---

## Applicant Portal - Setup Flow (MOD-07 partial)

### BD-032: [MOCK] Portal Guest Layout + Login
**Type**: Feature (UI)  
**Dependencies**: BD-003  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] PortalGuestLayout.svelte (portal-branded)
- [ ] Portal/Login.svelte
- [ ] Forgot password link
- [ ] Responsive

**Context**: 09-UI-ROUTES-PHASE1.md (Portal Login)

---

### BD-033: [MOCK] Password Setup Page
**Type**: Feature (UI)  
**Dependencies**: BD-032  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] Portal/Setup.svelte
- [ ] Password + confirmation fields
- [ ] Password strength indicator
- [ ] Error handling for invalid token

**Context**: 09-UI-ROUTES-PHASE1.md (Password Setup)

---

### BD-034: [BACK] Applicant Authentication
**Type**: Feature  
**Dependencies**: BD-012, BD-015  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] Separate guard for applicants
- [ ] PortalAuthController (login, logout)
- [ ] Rate limiting
- [ ] Session management
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (POST /portal/login)

---

### BD-035: [BACK] Password Setup Flow
**Type**: Feature  
**Dependencies**: BD-034, BD-028  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] GET /portal/setup/{token} — validate token, show form
- [ ] POST /portal/setup/{token} — set password
- [ ] Token invalidation after use
- [ ] Token expiry check (72h)
- [ ] Password policy enforcement
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/portal/setup/*)

---

### BD-036: [BACK] Setup Email Job
**Type**: Feature  
**Dependencies**: BD-028, BD-035  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Mailable class for setup email
- [ ] Queue job for async dispatch
- [ ] Email contains setup link with token
- [ ] Email template (HTML)
- [ ] Tests (mail fake)

**Context**: Notification Engine requirements

---

---

## Scheduling Module (MOD-03)

### BD-037: [MOCK] Rooms Management Pages
**Type**: Feature (UI)  
**Dependencies**: BD-017  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Admin/Rooms/Index.svelte — table with CRUD actions
- [ ] Create/Edit modal or page
- [ ] Facilities as checkboxes
- [ ] Delete confirmation

**Context**: 09-UI-ROUTES-PHASE1.md (Rooms)

---

### BD-038: [BACK] Rooms CRUD API
**Type**: Feature  
**Dependencies**: BD-013, BD-018  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] RoomController with full CRUD
- [ ] RoomPolicy (admin, super_admin)
- [ ] Cannot delete if assigned to future sessions
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/admin/rooms/*)

---

### BD-039: [MOCK] Proctors Management Pages
**Type**: Feature (UI)  
**Dependencies**: BD-017  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Admin/Proctors/Index.svelte
- [ ] Create/Edit with optional user linking
- [ ] Uses shadcn components

**Context**: 09-UI-ROUTES-PHASE1.md (Proctors)

---

### BD-040: [BACK] Proctors CRUD API
**Type**: Feature  
**Dependencies**: BD-013, BD-018  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] ProctorController with CRUD
- [ ] ProctorPolicy
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/admin/proctors/*)

---

### BD-041: [MOCK] Exam Sessions List & Create
**Type**: Feature (UI)  
**Dependencies**: BD-017  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] Admin/ExamSessions/Index.svelte — table with status badges
- [ ] Create.svelte — date/time pickers, room dropdown, proctor multi-select
- [ ] Status filter

**Context**: 09-UI-ROUTES-PHASE1.md (Exam Sessions)

---

### BD-042: [MOCK] Exam Session Details + Applicant Assignment
**Type**: Feature (UI)  
**Dependencies**: BD-041  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] Admin/ExamSessions/Show.svelte
- [ ] Assigned applicants table
- [ ] Available applicants table with bulk select
- [ ] Assign/remove actions
- [ ] Publish button
- [ ] Set release date input

**Context**: 09-UI-ROUTES-PHASE1.md (Exam Session Details)

---

### BD-043: [BACK] Exam Session CRUD API
**Type**: Feature  
**Dependencies**: BD-013, BD-018  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] ExamSessionController with CRUD
- [ ] Room conflict detection
- [ ] Proctor assignment
- [ ] ExamSessionPolicy
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/admin/exam-sessions/*)

---

### BD-044: [BACK] Applicant Assignment API
**Type**: Feature  
**Dependencies**: BD-043  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] POST .../assign-applicants endpoint
- [ ] Capacity validation
- [ ] Prevent duplicate assignment
- [ ] Only accepted applicants
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (POST /admin/exam-sessions/{id}/assign-applicants)

---

### BD-045: [BACK] Schedule Publication + Notification
**Type**: Feature  
**Dependencies**: BD-043, BD-044  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] POST .../publish endpoint
- [ ] Status change to published
- [ ] Trigger notification to assigned applicants
- [ ] Set published_at timestamp
- [ ] Audit log
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (POST /admin/exam-sessions/{id}/publish)

---

### BD-046: [BACK] Score Release Date API
**Type**: Feature  
**Dependencies**: BD-043  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] PUT .../release-date endpoint
- [ ] Notification on date change
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (PUT /admin/exam-sessions/{id}/release-date)

---

---

## Examination Module (MOD-04)

### BD-047: [MOCK] Proctor Dashboard
**Type**: Feature (UI)  
**Dependencies**: BD-017  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] Proctor/Dashboard.svelte
- [ ] List of assigned sessions
- [ ] Today's sessions highlighted
- [ ] Navigate to roster

**Context**: 09-UI-ROUTES-PHASE1.md (Proctor Dashboard)

---

### BD-048: [MOCK] Session Roster Page
**Type**: Feature (UI)  
**Dependencies**: BD-047  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] Proctor/SessionRoster.svelte
- [ ] Applicant search input
- [ ] Table with attendance status, submission status
- [ ] Mark present/absent buttons per row
- [ ] Log submission button (only for present)
- [ ] Start/close session buttons
- [ ] Live stats counter

**Context**: 09-UI-ROUTES-PHASE1.md (Session Roster)

---

### BD-049: [BACK] Proctor Sessions API
**Type**: Feature  
**Dependencies**: BD-013, BD-018  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] ProctorController@sessions — list assigned
- [ ] ProctorController@roster — applicants with status
- [ ] Scoped to assigned proctors only
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/proctor/sessions/*)

---

### BD-050: [BACK] Attendance & Submission API
**Type**: Feature  
**Dependencies**: BD-049, BD-021  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] POST .../attendance endpoint
- [ ] POST .../submission endpoint
- [ ] Validation rules per spec
- [ ] Immutable once set
- [ ] Audit logging
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (attendance, submission endpoints)

---

### BD-051: [BACK] Session Control API (Start/Close)
**Type**: Feature  
**Dependencies**: BD-049  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] POST .../start endpoint
- [ ] POST .../close endpoint
- [ ] Status transitions validated
- [ ] Timestamp logging
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (start, close endpoints)

---

---

## Grading Module (MOD-05)

### BD-052: [MOCK] Grading Dashboard
**Type**: Feature (UI)  
**Dependencies**: BD-017  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] Grading/Dashboard.svelte
- [ ] List grading sessions by status
- [ ] Completed exams without grading session
- [ ] Open new grading session action

**Context**: 09-UI-ROUTES-PHASE1.md (Grading Dashboard)

---

### BD-053: [MOCK] Grading Session + Score Input
**Type**: Feature (UI)  
**Dependencies**: BD-052  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] Grading/Session.svelte — applicant list with progress
- [ ] Grading/ScoreInput.svelte — 6 domain score inputs
- [ ] Number inputs with max validation
- [ ] Save and next navigation
- [ ] Finalize button on session page

**Context**: 09-UI-ROUTES-PHASE1.md (Grading Session, Score Input)

---

### BD-054: [BACK] Grading Session API
**Type**: Feature  
**Dependencies**: BD-014, BD-018  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] GradingController with session CRUD
- [ ] POST create — linked to completed exam
- [ ] Trigger processing notification
- [ ] GET session with applicants
- [ ] POST finalize
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/grading/sessions/*)

---

### BD-055: [BACK] Score Input API
**Type**: Feature  
**Dependencies**: BD-054, BD-021  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] GET scores per applicant
- [ ] PUT scores — input per domain
- [ ] Validation (0 to max_items)
- [ ] Audit logging on score changes
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (score endpoints)

---

---

## Consultation Module (MOD-06)

### BD-056: [MOCK] Consultation Dashboard
**Type**: Feature (UI)  
**Dependencies**: BD-017  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] Consultation/Dashboard.svelte
- [ ] Pending vs released tabs/filters
- [ ] Search applicants
- [ ] Link to rules management

**Context**: 09-UI-ROUTES-PHASE1.md (Consultation Dashboard)

---

### BD-057: [MOCK] Decision Rules Management
**Type**: Feature (UI)  
**Dependencies**: BD-056  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Consultation/Rules/Index.svelte
- [ ] Filter by course/domain
- [ ] Create/edit modal
- [ ] Delete with confirmation

**Context**: 09-UI-ROUTES-PHASE1.md (Decision Rules)

---

### BD-058: [MOCK] Applicant Consultation View
**Type**: Feature (UI)  
**Dependencies**: BD-056  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] Consultation/ApplicantView.svelte
- [ ] Applicant info section
- [ ] Scores per domain (table or chart)
- [ ] Matched rules highlight
- [ ] Editable summary form (recommendation, comments)
- [ ] Release button with confirmation

**Context**: 09-UI-ROUTES-PHASE1.md (Applicant Consultation View)

---

### BD-059: [BACK] Decision Rules API
**Type**: Feature  
**Dependencies**: BD-014, BD-018  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] DecisionRuleController with CRUD
- [ ] Counselor-only authorization
- [ ] Score range validation (min < max)
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/consultation/rules/*)

---

### BD-060: [BACK] Consultation API
**Type**: Feature  
**Dependencies**: BD-014, BD-059  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] ConsultationController@applicants — list with scores
- [ ] ConsultationController@show — full applicant data
- [ ] PUT summary — update draft
- [ ] POST release — publish to applicant
- [ ] Auto-populate system_notes from matched rules
- [ ] Trigger release notification
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/consultation/applicants/*)

---

---

## Applicant Portal - Complete (MOD-07)

### BD-061: [MOCK] Portal Layout + Dashboard
**Type**: Feature (UI)  
**Dependencies**: BD-032  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] PortalLayout.svelte (authenticated portal layout)
- [ ] Portal/Dashboard.svelte with 4 surface cards
- [ ] Status tracker component (stepper)
- [ ] Exam schedule card
- [ ] Countdown component
- [ ] Consultation summary card (pending/released states)
- [ ] Notification bell
- [ ] Mobile responsive

**Context**: 09-UI-ROUTES-PHASE1.md (Portal Dashboard)

---

### BD-062: [BACK] Portal Dashboard API
**Type**: Feature  
**Dependencies**: BD-034, BD-012, BD-013, BD-014  
**Estimate**: 2 hours

**Acceptance Criteria**:
- [ ] PortalController@dashboard
- [ ] Aggregates all 4 surfaces data
- [ ] Status tracker from application/session events
- [ ] Exam schedule from session_applicants
- [ ] Countdown from score_release_date
- [ ] Consultation from consultation_summaries
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (GET /portal/dashboard)

---

### BD-063: [BACK] Portal Notifications API
**Type**: Feature  
**Dependencies**: BD-062  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] GET /portal/notifications
- [ ] POST .../read
- [ ] Scoped to applicant only
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (/portal/notifications/*)

---

### BD-064: [BACK] OTP Password Recovery
**Type**: Feature  
**Dependencies**: BD-034  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] POST /portal/forgot-password
- [ ] OTP generation and email
- [ ] OTP verification endpoint
- [ ] Password reset with valid OTP
- [ ] Rate limiting
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (forgot-password)

---

---

## Notification Engine (MOD-08)

### BD-065: Notification Service Infrastructure
**Type**: Foundation  
**Dependencies**: BD-008  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] NotificationService class
- [ ] Laravel notification channels configured (mail, database)
- [ ] Email configuration (SES/SMTP via .env)
- [ ] Queue configuration for async dispatch

**Context**: Architecture Reference Section 6

---

### BD-066: Schedule Assigned Notification
**Type**: Feature  
**Dependencies**: BD-065, BD-045  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] ExamScheduleAssigned notification class
- [ ] Email template with room, date, time
- [ ] Database notification for in-app
- [ ] Triggered on schedule publish
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (publish triggers notification)

---

### BD-067: Scores Processing Notification
**Type**: Feature  
**Dependencies**: BD-065, BD-054  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] ScoresProcessing notification class
- [ ] Triggered on grading session open
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (grading session open triggers notification)

---

### BD-068: Consultation Released Notification
**Type**: Feature  
**Dependencies**: BD-065, BD-060  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] ConsultationReleased notification class
- [ ] Triggered on release
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (release triggers notification)

---

### BD-069: Exam Day Reminder (Scheduled)
**Type**: Feature  
**Dependencies**: BD-065  
**Estimate**: 1-2 hours

**Acceptance Criteria**:
- [ ] Scheduled command for T-1 reminders
- [ ] ExamDayReminder notification class
- [ ] Scheduler entry in Kernel
- [ ] Tests

**Context**: Architecture Reference Section 6 (Exam Day Reminder)

---

---

## Reference Data & Polish

### BD-070: [BACK] Courses & Departments API
**Type**: Feature  
**Dependencies**: BD-011  
**Estimate**: 1 hour

**Acceptance Criteria**:
- [ ] GET /courses (public)
- [ ] GET /departments (public)
- [ ] Admin CRUD for courses/departments
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (courses, departments)

---

### BD-071: [BACK] Exam Domains API
**Type**: Feature  
**Dependencies**: BD-014  
**Estimate**: 30 min

**Acceptance Criteria**:
- [ ] GET /exam-domains
- [ ] Grader/counselor authorization
- [ ] Tests

**Context**: 08-API-SPEC-PHASE1.md (exam-domains)

---

---

## Summary Statistics

| Type | Count |
|------|-------|
| Foundation | 21 |
| Feature (UI/Mock) | 22 |
| Feature (Backend) | 28 |
| **Total** | **71** |

---

## Task Dependency Chain (Critical Path)

```
BD-001 → BD-002 → BD-003 → BD-016 → BD-017
              ↓
         BD-004 → BD-005/006/007/008 → BD-009/010 → BD-011/012/013/014
              ↓
         BD-015 → BD-018 → BD-020
              ↓
         BD-022 → BD-024 (Application Submit)
              ↓
         BD-025 → BD-027 → BD-028 (Accept + Account)
              ↓
         BD-032 → BD-034 → BD-035 (Portal Auth)
              ↓
         BD-037 → BD-038 → BD-041 → BD-043 → BD-045 (Scheduling)
              ↓
         BD-047 → BD-049 → BD-050/051 (Examination)
              ↓
         BD-052 → BD-054 → BD-055 (Grading)
              ↓
         BD-056 → BD-059 → BD-060 (Consultation)
              ↓
         BD-061 → BD-062 (Portal Complete)
```
