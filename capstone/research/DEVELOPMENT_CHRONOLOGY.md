# Development Chronology — SecureCAT
## Canonical Timeline Reference

> **Purpose:** Single source of truth for the system's development history.
> All capstone documents should reference this file for timeline accuracy.
>
> **Created:** June 4, 2026
> **Status:** Internal reference — not for inclusion in the manuscript directly,
> but informs C1-01, C1-04, C1-11, C2-01, and defense preparation.

---

## Timeline

### Phase 0: Conception (2nd Year, OJT Period)

| Detail | Value |
|--------|-------|
| **What happened** | David conceived plans to build an admission testing system for ISPSC Tagudin |
| **Outcome** | Attempted during OJT → unsuccessful |
| **Significance** | Establishes the problem was identified over a year before the capstone. The researcher had institutional familiarity and a pre-existing understanding of the operational pain points. |
| **Documentation** | None — informal |
| **Research framework** | None |

---

### Phase 1: Foundational System (3rd Year, 2nd Semester)

| Detail | Value |
|--------|-------|
| **What happened** | David successfully built and deployed the foundational digital system |
| **Context** | Done through institutional consultation with the Guidance Office |
| **Client's original goal** | Get the result sheets printed |
| **What David delivered** | Result sheet generation AND proactively built beyond: application intake, scheduling, proctor management, scoring pipeline, AI companion, consultation summaries, bulk operations, document generation |
| **Deployment status** | System deployed and accessible by the Guidance Office |
| **Account handoff** | Super Admin account left with the Guidance Office for exploration |
| **Suggested uses** | (1) Report/result sheet generation, (2) new applications, (3) direct assessment (walk-in grading, skipping scheduling) |
| **Verified usage** | ⚠️ **NOT FORMALLY VERIFIED** — David suggested these uses but has not confirmed which features the Guidance Office actually adopted. Formal verification is required as part of the capstone's descriptive phase. |
| **Research framework** | None — informal development, no academic documentation |
| **Code state** | Codebase exists as the foundation that SecureCAT-v2 builds upon |

#### Features Built in Phase 1 (Existing Modules)
- Web-based application intake with form validation
- Applicant account activation via time-limited tokens
- Real-time status lifecycle tracking (6-stage)
- Admission slip generation (PDF)
- Direct assessment workflow (walk-in grading)
- AI companion chat (RAG with Mixedbread vector embeddings)
- Application pipeline review and approval/rejection
- Room and course management
- Academic year management
- Bulk application import (analyze, preview, confirm)
- Privacy policy management
- Aptitude area and rating scale management
- Test component definitions with formula evaluation
- Session roster operations and proctor assignment
- Attendance and bulk attendance operations
- Submission and bulk submission workflows
- Session lifecycle controls
- Grading dashboard and score import (CSV)
- Score edit/delete operations
- AI-assisted scheduling chat assistant
- Session CRUD, publish, cancel, monitoring
- Release summary management (bulk release)
- Result sheet printing (single, bulk PDF, bulk DOCX)
- Result sheet template management
- Staff and applicant notification systems
- Audit log viewing and export
- Google OAuth support
- QR code generation
- Document generation (DOMPDF, PHPWord/FPDI)

---

### Phase 2: Capstone Research (3rd Year Midyear → 4th Year 1st Semester)

| Detail | Value |
|--------|-------|
| **What happens** | David + capstone team formally research, document, validate, and upgrade |
| **Methodology** | AIDLC (AI-Driven Development Lifecycle) |
| **Research design** | Descriptive-developmental |
| **Evaluation instruments** | SUS + NASA-TLX |
| **Capstone timeline** | May 2026 → August 2026 (academic milestones) |

#### Dual Research Function

| Function | Scope |
|----------|-------|
| **Confirmatory validation** | Formally document and validate Phase 1 features through research — proving alignment with best practices, measuring usability, gathering user feedback |
| **Developmental advancement** | Build advanced features that Phase 1 lacked |

#### Planned Advanced Features (Capstone Contributions)
- Cryptographic score integrity (HMAC-SHA256 signature locks)
- Immutable write-only audit logging
- Computer-vision-based OMR answer sheet ingestion
- Offline-resilient proctor portal (PWA with Service Workers + IndexedDB)
- Enhanced AI companion with external data integration
- Enhanced AI-assisted scheduling (human-in-the-loop)
- Multi-tenant database architecture (tenant data isolation)

---

### Pre-Capstone Data Gathering Required

Before finalizing manuscript sections, the capstone team must formally gather from the Guidance Office:

- [ ] Which Phase 1 features were actually used
- [ ] How many applicants were processed through the system
- [ ] Whether direct assessment was used
- [ ] Whether new applications were entered digitally
- [ ] Staff feedback on the system (informal observations)
- [ ] What processes remain fully manual
- [ ] Whether features beyond the suggested ones were explored
- [ ] Problems or limitations encountered
- [ ] Whether other staff besides the primary contact used the system

> This data feeds into C1-01, C1-04, C1-06, C1-11, C2-05, and the SUS/NASA-TLX evaluation design.

---

## Role Model (Current State)

| # | Role | Status | Notes |
|---|------|--------|-------|
| 1 | Applicant / Examinee | Deployed | End users of the applicant portal |
| 2 | Proctor | Deployed | Session-level test administration |
| 3 | Test Administrator | Deployed | Broader test management authority |
| 4 | Guidance Counselor | Deployed | Consultation, results, scoring |
| 5 | Registrar Staff / Registrar Administrator | Deployed | Application pipeline, scheduling, rooms, courses |
| 6 | Super Administrator | Deployed (left with Guidance Office) | All functions + exclusive user creation and role assignment |

### Roles Under Consideration (Research-Dependent)

| Candidate Role | Rationale | Status |
|----------------|-----------|--------|
| **Campus Administrator** | Multi-campus ISPSC structure may need a campus-level admin between Registrar Admin and Super Admin — manages campus config without system-wide privileges | Open question |
| Additional role granularity | The Guidance Office using Super Admin for operational tasks suggests role boundaries may need tightening | Open question |

---

*This document is the canonical timeline reference. If any capstone document contradicts it, the
capstone document should be updated, not this file.*
