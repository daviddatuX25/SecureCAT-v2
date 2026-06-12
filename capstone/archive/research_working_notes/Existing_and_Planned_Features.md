# SecureCAT-v2: Existing vs Planned Features

This document reports the current system state and planned research/enhancement features. It is intended to support narrative alignment in Chapter 1 and Chapter 2 of the capstone manuscript.

## Executive Summary

SecureCAT-v2 is already implemented as a role-based admission testing system for ISPSC Tagudin. Advanced features have been proposed under the “Trojan Horse” strategic framing to support higher research complexity while preserving the locked title.

---

## Existing System State (as of current V2 codebase)

### Scope Boundaries
- **Primary users:** Applicants, Registrar Staff, Guidance/Proctors, Test Administrators, Super Administrators.
- **Primary workflows:** Application intake, applicant account activation, examination scheduling, proctor/roster management, score intake, consultation summary, result release, document generation.

### Role Access Model
- Role-based access is implemented through Laravel policy and route middleware groups.
- Staff/portal separation exists with distinct instructors for portal applicants and authenticated staff/admin access.

### Applicant/Examinee Features
- Web-based application intake with form validation.
- Token-based account setup and password reset workflow.
- Real-time application status lifecycle tracking.
- Admission slip generation with printable PDF rendering.
- Direct assessment workflow for walk-in grading cases.
- AI companion chat experience inside the applicant portal (RAG-powered with Mixedbread vector embeddings for course recommendations and applicant inquiries).

### Registrar Portal Features
- Application pipeline review and approval/rejection ops.
- Accept, dismiss, bulk accept, bulk dismiss, reopen ops.
- Room and course setup/management.
- Academic year setup/management.
- Bulk application import workflow including analyze, preview, and confirm stages.
- Privacy policy management.

### Guidance/Proctor Portal Features
- Aptitude area and rating scale management.
- Test component definitions such as Verbal, Math, Abstract Reasoning and formula evaluation support.
- Session roster operations and proctor assignment.
- Attendance and bulk attendance operations.
- Submission and bulk submission workflows.
- Session lifecycle controls such as start, close, extend.

### Grading Features
- Grading dashboard and grading session operations.
- Score import with CSV analysis, template export, preview, confirm, and fallback GET redirect behavior to avoid refresh errors.
- Score edit and delete operations at applicant level.

### Exam Scheduling Features
- Session CRUD plus publish, unpublish, cancel, start, reopen, assign applicants, remove applicant.
- Monitoring view.
- AI assisted scheduling chat assistant for registrar administrators.

### Release and Result Sheet Features
- Release summary management including bulk release and release all.
- Result sheet printing in single, bulk PDF, bulk DOCX modes.
- Result sheet template validation, preview, activation, deactivation.

### Notifications
- Staff notification inbox with read and read-all operations.
- Portal notification inbox for applicants.

### Reporting
- Report export endpoints.
- Audit log viewing and export for security-sensitive events.

### Super Administrator Features
- User creation and account activation provisioning.
- Role mapping and permission assignments for personnel.
- System-wide audit log access and export controls.
- **Deployment Status:** Currently deployed and accessible to the Guidance Office under a shared Super Admin account for initial operational evaluation.

### Integrations
- Google OAuth support for candidate or staff login.
- QR code generation.
- CSV/spreadsheet parsing.
- Document generation using DOMPDF, PHPWord/FPDI pipelines.

### Frontend Stack
- Laravel 12 with Inertia v2.
- Svelte 5.
- Tailwind 4.
- shadcn-svelte components.

### Backend Stack
- Laravel 12.
- Eloquent ORM with policies and form request validation.
- Queue-related scripts for jobs such as exam session auto-close.

---

## Planned Advanced Features (Proposed Capstone Research Scope)

The following features are planned additions to elevate the system for capstone evaluation and research depth.

### 1. Zero Trust Data Governance Model
- HMAC based score integrity model using server-side secret key bound to applicant UUID, test score, and proctor UUID.
- Tamper detection when scores are modified outside authorized workflow.
- Immutable write-only audit ledgers with metadata including timestamp, actor identity, IP, user agent, and before/after database states.
- Enforcement through backend policy classes preventing route/action bypass even if client-side UI is altered.

### 2. Computer Vision Ingestion for OMR
- Image based OMR scanning where a proctor captures or uploads a physical answer sheet.
- Bubble detection, answer extraction, and automatic scoring.
- Direct DB ingestion via service layer after grading.

### 3. Offline Resilient Proctor Portal
- PWA implementation with service worker caching for offline operation.
- QR code scanning at exam room door when network is unavailable.
- IndexedDB local caching and background sync restoration when connectivity resumes.

### 4. Enhanced AI Companion with External Data Integration
- RAG-based applicant-facing assistant using vector embeddings (Mixedbread).
- Natural language querying of course catalogs, program requirements, admission statistics, and institutional policies.
- Intelligent course recommendations based on applicant profiles and test results.
- Answers applicant questions about exam schedules, required documents, and campus information.

### 5. Enhanced AI-Assisted Scheduling (Human-in-the-Loop)
- Evolution of the existing AI scheduling chat assistant into a robust suggestion-based system.
- AI-powered constraint analysis (room capacity, proctor availability, time slots) generates scheduling proposals.
- Human-in-the-loop: all scheduling suggestions require explicit Registrar Admin approval before execution.
- Shares Laravel AI SDK infrastructure with the AI Companion for unified, scalable AI integration.
- Credit/utilization management via libraries like Laravel AI Orbit for API cost tracking across both AI features.

### 6. Multi-Tenant SaaS Architecture Preparation
- Isolated database segregation principles supporting per tenant data isolation.
- Alignment with Philippine Data Privacy Act requirements.
- Architectural readiness for future campus expansion.

---

## Mapping Title Components to Research-Enhanced Scope

| Title Component | Existing Scope | Planned Research Features |
|---|---|---|
| Role-Based | RBAC via policies and routes | Cryptographic integrity, audit immutability |
| Admission Testing System | Scheduling, roster, grading, direct assessment | OMR computer vision, offline PWA |
| Guidance and Registrar | Session management, consultation summaries, reporting | Enhanced AI Companion with RAG for applicant guidance, enhanced AI-assisted scheduling (human-in-the-loop) |
| ISPSC Tagudin | Single campus deployment | Multi-tenant database architecture |

---

## Deliverable Status Snapshot

| Item | Status |
|---|---|
| Capstone README and index | Exists |
| Roadmap timeline | Exists |
| System features baseline | Exists |
| Pre-proposal defense notes | Exists |
| Existing vs planned report | Created in this document |
| Chapter 1 drafting plan | Planned |
| Chapter 2 drafting plan | Planned |
| SRS document draft | Planned |
| ERD document draft | Planned |
| UAT report draft | Planned |

---

## Notes for Chapter Writing
- Use this document to justify each planned feature by pairing it to an already working system capability.
- Emphasize that the locked title is preserved while the engineering scope is raised.
- Reference the Trojan Horse strategy and panel interaction notes from `pre_proposal_defense.md` when framing scope justification.
