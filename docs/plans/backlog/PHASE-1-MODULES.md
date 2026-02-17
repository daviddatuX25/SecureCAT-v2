# SecureCAT — Phase 1 Modules

This document describes the module-level breakdown for Phase 1 development. Each module maps to the system overview and links to relevant contract sections.

---

## Module Dependency Graph

```
┌──────────────────┐
│   Foundation     │ (Auth, Database, Core Setup)
└────────┬─────────┘
         │
         ▼
┌──────────────────┐     ┌──────────────────┐
│   Application    │────▶│   Scheduling     │
│   Module         │     │   Module         │
└────────┬─────────┘     └────────┬─────────┘
         │                        │
         │                        ▼
         │               ┌──────────────────┐
         │               │   Examination    │
         │               │   Module         │
         │               └────────┬─────────┘
         │                        │
         │                        ▼
         │               ┌──────────────────┐
         │               │   Grading        │
         │               │   Module         │
         │               └────────┬─────────┘
         │                        │
         │                        ▼
         └──────────────▶┌──────────────────┐
                         │   Consultation   │
                         │   Module         │
                         └────────┬─────────┘
                                  │
                                  ▼
                         ┌──────────────────┐
                         │   Applicant      │
                         │   Portal         │
                         └──────────────────┘
                                  │
                                  ▼
                         ┌──────────────────┐
                         │   Notification   │
                         │   Engine         │
                         └──────────────────┘
```

---

## MOD-01: Foundation

### Purpose
Project scaffolding, authentication, authorization, database schema, and core infrastructure.

### Phase
Phase 1 (Foundation)

### Responsibilities
- Laravel project setup with Sail
- Inertia.js + Svelte 5 configuration
- TailwindCSS 4 + shadcn-svelte setup
- Database migrations for all entities
- User authentication (staff)
- Role-based authorization (Super Admin, Staff, Admin, Proctor, Grader, Counselor)
- Audit logging infrastructure
- Base layouts (Guest, Authenticated, Portal)

### Dependencies
None (root module)

### Acceptance Criteria
- [ ] `sail up` starts development environment
- [ ] Staff can log in and see role-appropriate dashboard
- [ ] Super Admin can create users and assign roles
- [ ] All migrations run without errors
- [ ] Audit log captures login events
- [ ] Base layouts render correctly
- [ ] shadcn-svelte components available

### Contract References
- Data Model: All tables (04-DATA-MODEL.md)
- Security: Authentication, Authorization Matrix (05-SECURITY-CONTROLS.md)
- API: Auth endpoints (08-API-SPEC-PHASE1.md)
- UI: Login, Dashboard, User Management (09-UI-ROUTES-PHASE1.md)

---

## MOD-02: Application Module

### Purpose
Handle applicant data intake, appointment booking, application processing, and account provisioning.

### Phase
Phase 1

### Responsibilities
- Public application form (with 3 ranked course preferences)
- Appointment slot management
- Application lookup and search
- Accept/reject workflow
- Admission slip generation (PDF with QR placeholder)
- Applicant portal account creation on acceptance
- Setup email dispatch

### Dependencies
- MOD-01 (Foundation) — auth, database, layouts

### Acceptance Criteria
- [ ] Public user can submit application with required fields
- [ ] Application generates unique reference number
- [ ] Staff can search applications by name/reference
- [ ] Staff can accept application
- [ ] On accept: applicant account created, setup email sent
- [ ] Staff can reject application with reason
- [ ] Admission slip PDF downloads with applicant info
- [ ] Appointment slots can be created and booked

### Contract References
- Data Model: applications, applicants, appointments, courses (04-DATA-MODEL.md)
- Security: Application authorization matrix (05-SECURITY-CONTROLS.md)
- API: /applications/*, /appointments/* (08-API-SPEC-PHASE1.md)
- UI: /apply, /applications/* (09-UI-ROUTES-PHASE1.md)

---

## MOD-03: Scheduling Module

### Purpose
Manage exam logistics — rooms, proctors, exam sessions, applicant assignment, and schedule publication.

### Phase
Phase 1

### Responsibilities
- Room CRUD (name, building, floor, capacity, facilities)
- Proctor CRUD
- Exam session CRUD (date, time, room, proctors)
- Applicant-to-session assignment (respecting capacity)
- Schedule publication (triggers notifications)
- Score release date setting

### Dependencies
- MOD-01 (Foundation)
- MOD-02 (Application) — accepted applicants list

### Acceptance Criteria
- [ ] Admin can create/edit/delete rooms
- [ ] Admin can create/edit/delete proctors
- [ ] Admin can create exam session with room and time
- [ ] Admin can assign proctors to session
- [ ] Admin can assign accepted applicants to session
- [ ] System prevents over-capacity assignment
- [ ] Admin can publish schedule
- [ ] On publish: notifications sent to assigned applicants
- [ ] Admin can set/update score release date

### Contract References
- Data Model: rooms, proctors, exam_sessions, session_applicants (04-DATA-MODEL.md)
- Security: Scheduling authorization matrix (05-SECURITY-CONTROLS.md)
- API: /admin/rooms/*, /admin/proctors/*, /admin/exam-sessions/* (08-API-SPEC-PHASE1.md)
- UI: /admin/rooms/*, /admin/proctors/*, /admin/exam-sessions/* (09-UI-ROUTES-PHASE1.md)

---

## MOD-04: Examination Module

### Purpose
Support exam-day operations — roster access, attendance logging, submission logging, session control.

### Phase
Phase 1

### Responsibilities
- Proctor view of assigned sessions
- Session roster with applicant list
- Applicant search (by name)
- Attendance marking (present/absent)
- Submission logging (timestamped)
- Session start/close actions
- Real-time stats (present count, submitted count)

### Dependencies
- MOD-01 (Foundation)
- MOD-03 (Scheduling) — published sessions, applicant assignments

### Acceptance Criteria
- [ ] Proctor sees only assigned sessions
- [ ] Proctor can view roster for session
- [ ] Proctor can search applicants by name
- [ ] Proctor can mark attendance (present/absent)
- [ ] Attendance is timestamped and logged
- [ ] Proctor can log submission (only for present applicants)
- [ ] Submission is timestamped and logged
- [ ] Proctor can start session (status → in_progress)
- [ ] Proctor can close session (status → completed)
- [ ] Stats update reflect attendance/submission counts

### Contract References
- Data Model: session_applicants (attendance, submission fields) (04-DATA-MODEL.md)
- Security: Examination authorization, proctor scoping (05-SECURITY-CONTROLS.md)
- API: /proctor/* (08-API-SPEC-PHASE1.md)
- UI: /proctor/*, SessionRoster (09-UI-ROUTES-PHASE1.md)

---

## MOD-05: Grading Module

### Purpose
Score input for completed exams — domain-based scoring, grading session management.

### Phase
Phase 1

### Responsibilities
- Grading session creation (linked to completed exam session)
- Applicant score input per domain (6 domains)
- Raw score capture (item-level optional)
- Grading session finalization
- Processing notification trigger

### Dependencies
- MOD-01 (Foundation)
- MOD-04 (Examination) — completed exam sessions, submission records

### Acceptance Criteria
- [ ] Grader can open grading session for completed exam
- [ ] On open: notification sent ("scores being processed")
- [ ] Grader can view list of submitted applicants
- [ ] Grader can input raw score per domain for each applicant
- [ ] Scores are saved and audited
- [ ] Grader can finalize session (all applicants must be scored)
- [ ] Finalized scores are read-only

### Contract References
- Data Model: grading_sessions, applicant_scores, score_items, exam_domains (04-DATA-MODEL.md)
- Security: Grading authorization matrix (05-SECURITY-CONTROLS.md)
- API: /grading/* (08-API-SPEC-PHASE1.md)
- UI: /grading/*, ScoreInput (09-UI-ROUTES-PHASE1.md)

---

## MOD-06: Consultation Module

### Purpose
Counselor workflow — score review, decision rules, recommendations, consultation release.

### Phase
Phase 1

### Responsibilities
- View applicant scores and profile
- Decision rule management (score range → note per course/domain)
- Auto-population of matched rules
- Counselor comments input
- Recommended course selection
- Consultation summary release (gated)
- Release notification trigger

### Dependencies
- MOD-01 (Foundation)
- MOD-02 (Application) — applicant/application data
- MOD-05 (Grading) — finalized scores

### Acceptance Criteria
- [ ] Counselor can view applicants with finalized scores
- [ ] Counselor can create/edit/delete decision rules
- [ ] Counselor can view applicant's scores with matched rules highlighted
- [ ] Counselor can add comments to consultation summary
- [ ] Counselor can select recommended course
- [ ] Counselor can release consultation to applicant
- [ ] On release: notification sent, summary visible in portal
- [ ] Released consultations are immutable

### Contract References
- Data Model: decision_rules, consultation_summaries (04-DATA-MODEL.md)
- Security: Consultation authorization matrix (05-SECURITY-CONTROLS.md)
- API: /consultation/* (08-API-SPEC-PHASE1.md)
- UI: /consultation/*, ApplicantView, Rules (09-UI-ROUTES-PHASE1.md)

---

## MOD-07: Applicant Portal

### Purpose
Self-service dashboard for applicants — status tracking, schedule viewing, consultation access.

### Phase
Phase 1

### Responsibilities
- Applicant authentication (email + password setup flow)
- Password setup via email link
- OTP-based password recovery
- Dashboard with 4 surfaces:
  - Process Status Tracker
  - My Exam Schedule
  - Score Release Countdown
  - Consultation Summary
- Notification inbox

### Dependencies
- MOD-01 (Foundation)
- MOD-02 (Application) — applicant account creation
- MOD-03 (Scheduling) — exam schedule data
- MOD-06 (Consultation) — released consultation data

### Acceptance Criteria
- [ ] Applicant receives setup email on acceptance
- [ ] Applicant can set password via setup link
- [ ] Applicant can log in to portal
- [ ] Portal shows status tracker with all stages
- [ ] Portal shows exam schedule (after assignment)
- [ ] Portal shows countdown (after release date set)
- [ ] Portal shows consultation summary (after release)
- [ ] Applicant can view and dismiss notifications
- [ ] OTP recovery flow works

### Contract References
- Data Model: applicants, notifications (04-DATA-MODEL.md)
- Security: Applicant authentication, data isolation (05-SECURITY-CONTROLS.md)
- API: /portal/* (08-API-SPEC-PHASE1.md)
- UI: /portal/*, PortalLayout (09-UI-ROUTES-PHASE1.md)

---

## MOD-08: Notification Engine

### Purpose
Event-driven notifications via email and in-app alerts.

### Phase
Phase 1

### Responsibilities
- Event listener for notification triggers
- Email dispatch (Amazon SES / SMTP)
- In-app notification storage
- Notification read/unread state
- Exam day reminder (scheduled job)

### Dependencies
- MOD-01 (Foundation)
- All other modules (event sources)

### Acceptance Criteria
- [ ] Account setup email sent on applicant creation
- [ ] Schedule notification sent on publish
- [ ] Processing notification sent on grading session open
- [ ] Release notification sent on consultation release
- [ ] Exam day reminder sent T-1 day (scheduled)
- [ ] In-app notifications stored and retrievable
- [ ] Notifications can be marked as read
- [ ] Email delivery failures logged

### Contract References
- Data Model: notifications (04-DATA-MODEL.md)
- Architecture Reference: Notification Engine (Section 6)
- API: Notification-related endpoints (08-API-SPEC-PHASE1.md)

---

## Module Delivery Order (Recommended)

1. **MOD-01: Foundation** — Must be first (all others depend on it)
2. **MOD-02: Application** — Enables applicant data entry
3. **MOD-07: Applicant Portal** (partial) — Setup flow, basic dashboard
4. **MOD-03: Scheduling** — Enables exam logistics
5. **MOD-04: Examination** — Enables exam-day operations
6. **MOD-05: Grading** — Enables score input
7. **MOD-06: Consultation** — Enables result release
8. **MOD-07: Applicant Portal** (complete) — All surfaces
9. **MOD-08: Notification Engine** — Can be developed in parallel, integrated incrementally

---

## Mockup-First Approach

Per project requirements, mockups are prioritized:

1. **Mockup all pages** in module order before backend implementation
2. **Stakeholder review** at each module mockup milestone
3. **Backend implementation** after mockup approval
4. **Integration testing** connects frontend to backend

This allows stakeholders to see and interact with the system early.
