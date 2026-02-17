# SecureCAT — Phases Overview

## Phase 1 (Scope Freeze)

The first development phase delivers the **complete admission pipeline** with core functionality across all 5 system phases. Mockups are prioritized for stakeholder validation, followed by backend implementation.

### Capabilities (Testable)

#### System Phase 1 — Application
- [ ] Applicant can submit application with personal data and 3 ranked course preferences
- [ ] Staff can search/lookup applications by name or reference number
- [ ] Staff can mark application as accepted or rejected
- [ ] On acceptance: system generates admission slip (PDF with QR code placeholder)
- [ ] On acceptance: system auto-creates applicant portal account and sends setup email
- [ ] Appointment booking for application submission (prevents crowding)
- [ ] Walk-in (on-the-spot) application support

#### System Phase 2 — Scheduling
- [ ] Admin can create/manage rooms (name, building, floor, capacity, facilities)
- [ ] Admin can create/manage proctors
- [ ] Admin can manually create exam schedules (date, time, room, proctor assignments)
- [ ] Admin can assign applicants to exam sessions
- [ ] Admin can set score release countdown date
- [ ] Admin can publish schedule (triggers notifications to applicants)
- [ ] Scheduling is applicant-based, NOT course-based

#### System Phase 3 — Examination
- [ ] Proctor can view assigned room roster
- [ ] Proctor can search applicants by name and mark attendance
- [ ] Proctor can log exam submission per applicant (timestamped)
- [ ] Proctor can close exam session
- [ ] Real-time session status visible (present count, submitted count, elapsed time)

#### System Phase 4 — Grading
- [ ] Grader can open grading session for a completed exam session
- [ ] Grader can manually input scores per applicant per domain (6 domains)
- [ ] Grader can input item-level scores per domain
- [ ] System stores raw scores for later normalization
- [ ] Grader can finalize grading session
- [ ] On session open: notification sent to applicants ("scores being processed")

#### System Phase 5 — Consultation
- [ ] Counselor can view applicant scores and profile
- [ ] Counselor can create/manage decision rules (score range → notes per course)
- [ ] Counselor can add written comments per applicant
- [ ] Counselor can select recommended course(s)
- [ ] Counselor can explicitly release consultation summary to applicant
- [ ] On release: notification sent to applicant

#### Applicant Portal
- [ ] Applicant receives setup email and sets password
- [ ] Applicant can log in to portal
- [ ] Portal shows: Process Status Tracker (all stages)
- [ ] Portal shows: My Exam Schedule (after publication)
- [ ] Portal shows: Score Release Countdown (after admin sets date)
- [ ] Portal shows: Consultation Summary (after counselor releases)

#### Authentication & Authorization
- [ ] Super Admin can create users and assign roles
- [ ] Roles: Super Admin, Staff, Admin, Proctor, Grader, Counselor
- [ ] Role-based access control enforced on all endpoints
- [ ] Staff/Admin login via email + password
- [ ] Applicant login via email + password (setup via email link)
- [ ] OTP fallback for applicant account recovery

#### Notifications
- [ ] Email + in-app notification on exam schedule assignment
- [ ] Email + in-app notification on score processing start
- [ ] Email + in-app notification on consultation release
- [ ] Exam day reminder (T-1 day)

---

## Phase 2 (Icebox — Explicitly Deferred)

The following features are **not in Phase 1 scope**. They are documented for future development.

| Feature | Reason Deferred |
|---------|-----------------|
| **AI Scheduling Assistant** | Requires third-party API integration; manual scheduling sufficient for MVP |
| **OMR Auto-Scoring** | Requires OMRChecker integration; manual input is MVP |
| **QR Code Scanning for Attendance** | Admission slip has QR, but scanning deferred; manual search is MVP |
| **Score Normalization Engine** | Rules TBD; raw scores stored, normalization logic added later |
| **Course Distribution Heatmap** | Analytics feature; counselor works with raw data in Phase 1 |
| **Historical Enrollment Statistics** | Requires data import/SIS integration |
| **File Attachments (ID scans, etc.)** | Deferred pending requirements clarification |
| **SSO for Staff** | Email + password sufficient for MVP |
| **SIS/Counseling System Integration** | Future integration; adapter pattern planned |
| **Multiple Concurrent Admission Batches** | Single batch at a time for Phase 1 |

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Stakeholder validation | Mockups reviewed and approved before backend build |
| End-to-end flow | Applicant can go from application → consultation summary view |
| Staff workflow | Staff can process application → acceptance in < 5 minutes |
| Exam logistics | Admin can schedule 100+ applicants across rooms |
| Grading turnaround | Grader can input scores for 50 applicants in < 1 hour |
| Portal usability | Applicant can access all 4 surfaces without support |

---

## Definition of Done

### Per Feature
- [ ] Mockup reviewed by stakeholder
- [ ] Backend endpoint implemented per API spec
- [ ] Form validation per spec
- [ ] Authorization enforced per security controls
- [ ] Tests written (feature + edge cases)
- [ ] No critical linter errors

### Per Phase (System Phase)
- [ ] All features in that phase marked done
- [ ] End-to-end flow tested
- [ ] Notifications firing correctly
- [ ] Portal surfaces updating correctly

### Overall Phase 1 (Dev)
- [ ] All 5 system phases functional
- [ ] Applicant portal fully operational
- [ ] Role-based access working
- [ ] Deployment to staging environment
- [ ] Stakeholder sign-off
