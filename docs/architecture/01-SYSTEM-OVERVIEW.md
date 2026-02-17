# SecureCAT — System Overview

## Purpose

SecureCAT (Computerized Admission & Testing) digitizes the full admission lifecycle from application through consultation. It serves two organizational units (Registrar Office, Guidance Office) and provides a self-service portal for applicants.

---

## Module Map

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              SecureCAT System                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    Cross-Cutting Modules                             │   │
│  │  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐   │   │
│  │  │   Auth Module    │  │ Notification     │  │  Applicant       │   │   │
│  │  │                  │  │ Engine           │  │  Portal          │   │   │
│  │  │ - User mgmt      │  │ - Event queue    │  │ - Dashboard      │   │   │
│  │  │ - Role/perms     │  │ - Email dispatch │  │ - Status tracker │   │   │
│  │  │ - Session mgmt   │  │ - In-app alerts  │  │ - Schedule view  │   │   │
│  │  └──────────────────┘  └──────────────────┘  └──────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    Registrar Office Modules                          │   │
│  │  ┌──────────────────────────┐  ┌──────────────────────────────────┐ │   │
│  │  │  Application Module      │  │  Scheduling Module               │ │   │
│  │  │  (Phase 1)               │  │  (Phase 2)                       │ │   │
│  │  │                          │  │                                  │ │   │
│  │  │  - Application intake    │  │  - Room management               │ │   │
│  │  │  - Appointment booking   │  │  - Proctor management            │ │   │
│  │  │  - Application lookup    │  │  - Session scheduling            │ │   │
│  │  │  - Acceptance workflow   │  │  - Applicant assignment          │ │   │
│  │  │  - Admission slip gen    │  │  - Schedule publication          │ │   │
│  │  └──────────────────────────┘  └──────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    Guidance Office Modules                           │   │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────────────┐ │   │
│  │  │ Examination    │  │ Grading        │  │ Consultation           │ │   │
│  │  │ Module (P3)    │  │ Module (P4)    │  │ Module (P5)            │ │   │
│  │  │                │  │                │  │                        │ │   │
│  │  │ - Roster view  │  │ - Score input  │  │ - Score review         │ │   │
│  │  │ - Attendance   │  │ - Domain scores│  │ - Decision rules       │ │   │
│  │  │ - Submission   │  │ - Session mgmt │  │ - Comments             │ │   │
│  │  │ - Session ctrl │  │                │  │ - Release workflow     │ │   │
│  │  └────────────────┘  └────────────────┘  └────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Module Responsibilities & Interfaces

### Auth Module
| Aspect | Details |
|--------|---------|
| **Responsibility** | User management, authentication, role/permission assignment, session management |
| **Exposes** | Login/logout endpoints, user CRUD (Super Admin), role assignment, permission checks |
| **Consumes** | Nothing (foundational) |
| **Phase** | Phase 1 (foundation) |

### Application Module
| Aspect | Details |
|--------|---------|
| **Responsibility** | Applicant data intake, appointment booking, application processing, acceptance workflow, account provisioning |
| **Exposes** | Application submission, lookup, acceptance/rejection, admission slip generation |
| **Consumes** | Auth (account creation), Notification (setup email) |
| **Phase** | Phase 1 |

### Scheduling Module
| Aspect | Details |
|--------|---------|
| **Responsibility** | Room/proctor management, exam session creation, applicant assignment, schedule publication |
| **Exposes** | Room CRUD, proctor CRUD, session CRUD, publication trigger, release date setting |
| **Consumes** | Application (accepted applicant list), Notification (schedule notifications) |
| **Phase** | Phase 1 |

### Examination Module
| Aspect | Details |
|--------|---------|
| **Responsibility** | Exam day operations — roster display, attendance logging, submission logging, session control |
| **Exposes** | Roster view, attendance marking, submission logging, session state changes |
| **Consumes** | Scheduling (session/room assignments) |
| **Phase** | Phase 1 |

### Grading Module
| Aspect | Details |
|--------|---------|
| **Responsibility** | Score input (manual), domain-based scoring, grading session management |
| **Exposes** | Grading session CRUD, score input per applicant/domain, session finalization |
| **Consumes** | Examination (submission records), Notification (processing notification) |
| **Phase** | Phase 1 |

### Consultation Module
| Aspect | Details |
|--------|---------|
| **Responsibility** | Score review, decision rule management, counselor comments, consultation release |
| **Exposes** | Applicant score view, rule CRUD, comment input, release action |
| **Consumes** | Grading (finalized scores), Notification (release notification) |
| **Phase** | Phase 1 |

### Notification Engine
| Aspect | Details |
|--------|---------|
| **Responsibility** | Event-driven notifications via email and in-app alerts |
| **Exposes** | Dispatch trigger (internal), notification inbox (portal) |
| **Consumes** | Events from all modules |
| **Phase** | Phase 1 |

### Applicant Portal
| Aspect | Details |
|--------|---------|
| **Responsibility** | Self-service dashboard for applicants — status, schedule, countdown, consultation |
| **Exposes** | Dashboard view, notification inbox |
| **Consumes** | All phases (read-only status data) |
| **Phase** | Phase 1 |

---

## Core Value Loop — Data Flow

```
Application          Scheduling           Examination          Grading             Consultation
   │                    │                     │                   │                    │
   │  Accepted          │                     │                   │                    │
   │  Applicant ────────▶  Assigned to        │                   │                    │
   │  List              │  Exam Session ──────▶  Attendance &     │                    │
   │                    │                     │  Submission ──────▶  Scores            │
   │                    │                     │  Records          │  Input ────────────▶  Consultation
   │                    │                     │                   │                    │  Summary
   │                    │                     │                   │                    │  Released
   │                    │                     │                   │                    │
   └────────────────────┴─────────────────────┴───────────────────┴────────────────────┘
                                              │
                                              ▼
                                    ┌──────────────────┐
                                    │ Applicant Portal │
                                    │ (Status Updates) │
                                    └──────────────────┘
```

---

## Phase 1 vs Phase 2 Module Features

| Module | Phase 1 (MVP) | Phase 2 (Deferred) |
|--------|---------------|-------------------|
| **Application** | Data intake, acceptance, admission slip | File attachments, QR scanning |
| **Scheduling** | Manual scheduling, room/proctor mgmt | AI scheduling assistant |
| **Examination** | Manual attendance, submission logging | QR-based attendance |
| **Grading** | Manual score input per domain | OMR auto-scoring, normalization engine |
| **Consultation** | Rule-based notes, manual release | Heatmaps, statistics, historical data |
| **Portal** | All 4 surfaces (basic) | Enhanced analytics, PDF download |
| **Notifications** | Email + in-app alerts | SMS, push notifications |

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Database | MySQL 8.0+ |
| Frontend | Inertia.js v2 + Svelte 5 (runes) |
| Styling | TailwindCSS 4.x, shadcn-svelte |
| Queue | Laravel Queue (Redis when needed) |
| Real-time | Laravel Reverb (when needed) |
| Email | Amazon SES / SMTP (configurable) |
| Dev Environment | Laravel Sail (Docker) |
