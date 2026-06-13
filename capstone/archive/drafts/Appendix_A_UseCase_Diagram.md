# Appendix A: Use Case Diagram
**Document:** SecureCAT — Chapter 1 Appendix
**Author:** David
**Date:** June 10, 2026

---

## Use Case Diagram

The diagram below shows the primary actors and their corresponding interactions with SecureCAT. Each actor is mapped to the system functions they are authorized to perform based on their assigned role.

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                                   <<System>>                                        │
│            SecureCAT: Role-Based College Admission Testing System                   │
│                                                                                     │
│  ┌──────────────────────────────────────────────────────────────────────────────┐   │
│  │  APPLICANT                                                                   │   │
│  │  ○── Submit application form                                                 │   │
│  │  ○── Activate account via token                                              │   │
│  │  ○── Track application status                                                │   │
│  │  ○── Download admission slip                                                 │   │
│  │  ○── Receive exam result                                                     │   │
│  │  ○── Query AI Companion (pre- and post-exam guidance)                        │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
│  ┌──────────────────────────────────────────────────────────────────────────────┐   │
│  │  PROCTOR / TEST ADMINISTRATOR                                                │   │
│  │  ○── View assigned exam session roster                                       │   │
│  │  ○── Scan applicant QR code for check-in (offline-capable)                   │   │
│  │  ○── Record and submit attendance                                            │   │
│  │  ○── Sync offline data upon reconnection                                     │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
│  ┌──────────────────────────────────────────────────────────────────────────────┐   │
│  │  GUIDANCE COUNSELOR                                                          │   │
│  │  ○── View applicant examination results                                      │   │
│  │  ○── View K-Means triage profile suggestions                                 │   │
│  │  ○── Record consultation summary and course recommendation                   │   │
│  │  ○── Release applicant results                                               │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
│  ┌──────────────────────────────────────────────────────────────────────────────┐   │
│  │  REGISTRAR STAFF                                                             │   │
│  │  ○── Process and verify application records                                  │   │
│  │  ○── Import examination scores (CSV)                                         │   │
│  │  ○── Generate admission slips and result documents                           │   │
│  │  ○── Send applicant and staff notifications                                  │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
│  ┌──────────────────────────────────────────────────────────────────────────────┐   │
│  │  REGISTRAR ADMINISTRATOR                                                     │   │
│  │  ○── Configure aptitude areas and exam settings                              │   │
│  │  ○── Create and publish examination sessions                                 │   │
│  │  ○── Assign proctors to sessions                                             │   │
│  │  ○── Review and approve AI-generated scheduling proposals                    │   │
│  │  ○── Manage application pipeline and status overrides                        │   │
│  │  ○── Export reports and statistical data                                     │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
│  ┌──────────────────────────────────────────────────────────────────────────────┐   │
│  │  SUPER ADMINISTRATOR                                                         │   │
│  │  ○── Create and manage user accounts                                         │   │
│  │  ○── Assign and revoke roles                                                 │   │
│  │  ○── Access all system functions across all roles                            │   │
│  │  ○── View and export audit logs                                              │   │
│  │  ○── Manage system-wide configuration                                        │   │
│  └──────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                     │
└─────────────────────────────────────────────────────────────────────────────────────┘

External Actors:
  [Applicant] ──────────────────────────────────────────────────── interacts with system
  [Proctor / Test Administrator] ───────────────────────────────── interacts with system
  [Guidance Counselor] ─────────────────────────────────────────── interacts with system
  [Registrar Staff] ────────────────────────────────────────────── interacts with system
  [Registrar Administrator] ────────────────────────────────────── interacts with system
  [Super Administrator] (extends all other roles) ──────────────── interacts with system
```

**Figure A1.** Use Case Diagram of SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin

---

## Actor Descriptions

| Actor | Role Description | Key Use Cases |
|-------|-----------------|---------------|
| Applicant | College admission candidate seeking enrollment at ISPSC Tagudin | Application submission, status tracking, result access, AI Companion queries |
| Proctor / Test Administrator | Faculty or staff member assigned to supervise an examination session | Roster access, QR-based attendance, offline check-in, data sync |
| Guidance Counselor | Licensed counselor who reviews results and advises applicants on course placement | Result review, triage profile viewing, consultation recording, result release |
| Registrar Staff | Office personnel responsible for application processing and document generation | Application verification, score import, document generation, notifications |
| Registrar Administrator | Senior registrar personnel overseeing scheduling and overall exam operations | Session creation, proctor assignment, AI scheduling approval, report export |
| Super Administrator | Highest-privilege system user; manages accounts and all configurations | Account creation, role assignment, audit log access, full system access |

---

## Use Case Notes

1. **Role overlap is by design.** A single user may hold more than one role simultaneously. For example, a faculty member may serve as both Proctor and Guidance Counselor. The Super Administrator inherits the permissions of all other roles.

2. **Offline use cases are bounded.** The offline-capable use cases for Proctors (QR scanning, attendance recording) function only after an initial network connection has been established for service worker registration.

3. **AI-assisted use cases require human confirmation.** The AI scheduling proposals visible to Registrar Administrators do not execute automatically. They must be reviewed and explicitly approved before any schedule changes take effect.

4. **Confidential data is invisible to Applicants.** Applicants can view their final results but cannot access raw aptitude scores, conversion table values, or the details of the triage classification logic.

---

## Notes

- This appendix supplements the Scope and Limitation section (C1-11) and provides a visual overview of actor–system interactions.
- The use case diagram reflects the six roles defined in the system scope and the capabilities described in the Objectives of the Study (C1-09).
- For the full IPO Conceptual Framework, see C1-07 and the diagram in `capstone/diagrams/ipo_diagram.mmd`.
