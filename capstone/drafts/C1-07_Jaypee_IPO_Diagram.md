# C1-07: Conceptual Framework — IPO Diagram

**Task ID:** C1-07
**Assigned to:** Jaypee
**Date:** June 3, 2026
**Dependencies:** SYSTEM_FEATURES.md, research/Existing_and_Planned_Features.md

---

## Input-Process-Output (IPO) Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              INPUT                                               │
│                                                                                 │
│   1. Applicant data                                                             │
│      (personal information, academic preferences, application forms,            │
│       GWA records, course selections)                                           │
│                                                                                 │
│   2. Exam configurations                                                        │
│      (aptitude area definitions, scoring formulas, rating scales,               │
│       session parameters, academic year settings)                               │
│                                                                                 │
│   3. OMR images/scans                                                           │
│      (physical answer sheet photographs, bubble sheet scans for                │
│       computer-vision-based grading and CSV score imports)                      │
│                                                                                 │
│   4. Role credentials                                                           │
│      (user authentication data, role-based access tokens, Google OAuth          │
│       identifiers, proctor and administrator authorization credentials)         │
│                                                                                 │
│   5. QR scans                                                                   │
│      (examinee QR code reads at exam room doors for attendance                  │
│       verification, identity confirmation, and session check-in)                │
│                                                                                 │
│   6. Natural language queries                                                   │
│      (applicant questions about admission requirements, course                  │
│       recommendations, exam schedules, and institutional policies               │
│       submitted to the AI Companion)                                            │
│                                                                                 │
└──────────────────────────────────┬──────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              PROCESS                                            │
│                                                                                 │
│        SecureCAT: A Role-Based College Admission Testing System                 │
│           for the Guidance and Registrar Offices at ISPSC Tagudin               │
│                                                                                 │
│   • Role-based access control (RBAC) with zero-trust data governance            │
│   • Cryptographic score integrity via HMAC-SHA256 signature verification        │
│   • Immutable write-only audit logging with before/after state capture          │
│   • Automated scoring pipeline (CSV import, direct assessment,                  │
│     and planned computer-vision OMR ingestion)                                  │
│   • Offline-resilient proctoring via PWA with IndexedDB caching                 │
│     and background synchronization                                              │
│   • AI-assisted operations using retrieval-augmented generation (RAG)           │
│     with local vector embeddings for applicant guidance, course                 │
│     recommendations, and scheduling optimization                                │
│   • Multi-tenant database isolation architecture aligned with the               │
│     Philippine Data Privacy Act (RA 10173)                                      │
│                                                                                 │
└──────────────────────────────────┬──────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              OUTPUT                                             │
│                                                                                 │
│   1. Status tracking displays                                                   │
│      (real-time applicant lifecycle visualization from application              │
│       submission through result release)                                        │
│                                                                                 │
│   2. Exam schedules                                                             │
│      (session assignments, room allocations, proctor assignments,               │
│       and time slot configurations)                                             │
│                                                                                 │
│   3. Score reports                                                              │
│      (per-applicant aptitude scores, percentile rankings, and                   │
│       component-level performance breakdowns)                                   │
│                                                                                 │
│   4. Audit logs                                                                 │
│      (immutable, append-only records of security-sensitive actions              │
│       with actor identity, IP address, user agent, and before/after             │
│       database snapshots)                                                       │
│                                                                                 │
│   5. Result sheets/PDFs                                                         │
│      (customizable, print-ready result documents with dynamic                   │
│       watermarks in single, bulk PDF, and bulk DOCX formats)                    │
│                                                                                 │
│   6. Consultation summaries                                                     │
│      (guidance counselor notes, course recommendations, and                     │
│       applicant disposition records)                                            │
│                                                                                 │
│   7. AI Companion responses                                                    │
│      (natural language answers to applicant inquiries, intelligent              │
│       course recommendations based on test results, and admission               │
│       guidance via RAG-powered conversational interface)                        │
│                                                                                 │
│   8. Offline-cached records                                                    │
│      (locally persisted QR scan data and attendance records in                  │
│       IndexedDB, synchronized upon network restoration)                         │
│                                                                                 │
│   9. Statistical reports                                                       │
│      (admission analytics, application pipeline metrics, and                    │
│       exportable CSV/Excel data for institutional reporting)                    │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

**Figure 1.** Conceptual Framework Diagram (Input-Process-Output) of SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin

---

## Critical Evaluation Notes

### Input Validation Checklist

| # | Input Item | Type | Justification (Existing / Planned / Both) |
|---|-----------|------|-------------------------------------------|
| 1 | Applicant data | Data | **Both** — Application intake exists; GWA/course preferences are existing. Profile data feeds into the AI Companion (planned enhancement). |
| 2 | Exam configurations | Data | **Both** — Aptitude areas, rating scales, scoring formulas are built. Session parameters and academic year settings are built. Enhanced AI-assisted scheduling (planned) will consume these configs as constraint inputs for suggestion generation. | |
| 3 | OMR images/scans | Data | **Both** — CSV-based score import exists (machine-readable). Computer-vision image ingestion for physical bubble sheets is planned. Both represent the same input category (score source material) at different automation levels. |
| 4 | Role credentials | Data | **Both** — Authentication, RBAC via Laravel policies, Google OAuth exist. HMAC signing adds proctor identity binding (planned). Zero-trust enforcement strengthens credential validation (planned). |
| 5 | QR scans | Data | **Both** — QR code generation exists. Offline PWA QR scanning at exam room doors with IndexedDB caching is planned. Same input, expanded resilience. |
| 6 | Natural language queries | Data | **Planned** — The existing AI Companion uses RAG with Mixedbread vector embeddings for basic applicant queries. The planned enhancement adds external data integration (course catalogs, admission statistics, institutional policies) and intelligent course recommendations. This input represents the applicant-facing AI interaction channel. |

### Output Validation Checklist

| # | Output Item | Type | Justification (Existing / Planned / Both) |
|---|------------|------|-------------------------------------------|
| 1 | Status tracking displays | Artifact | **Existing** — Real-time applicant lifecycle tracker from submission through result release. |
| 2 | Exam schedules | Artifact | **Existing** — Session CRUD, publish/unpublish, assign/remove applicants, monitoring view. Planned enhanced AI-assisted scheduling will generate optimized schedule suggestions for registrar admin approval (human-in-the-loop). | |
| 3 | Score reports | Artifact | **Both** — Per-applicant score breakdowns exist. HMAC-integrity verification flags on reports are planned. |
| 4 | Audit logs | Artifact | **Both** — Audit log viewing and export exist. Immutable write-only enforcement with before/after state capture is planned. |
| 5 | Result sheets/PDFs | Artifact | **Existing** — Template-based rendering in single, bulk PDF, and bulk DOCX with dynamic watermarks. |
| 6 | Consultation summaries | Artifact | **Existing** — Counselor notes and course recommendation interface. |
| 7 | AI Companion responses | Artifact | **Both** — Existing RAG-powered chat with Mixedbread embeddings. Planned enhancement adds external data integration, course recommendations based on test results, and admission statistics querying. |
| 8 | Offline-cached records | Artifact | **Planned** — IndexedDB-cached QR scan data and attendance records persisted during network drops, with background sync upon reconnection. |
| 9 | Statistical reports | Artifact | **Existing** — Report export endpoints, application analytics CSV/Excel export. |

### Design Decisions and Rationale

1. **Inputs are THINGS the system receives, not processes.** Each input is a concrete data artifact or signal fed into the system. No process verbs (e.g., "scheduling," "grading," "tracking") appear in the input list — only the nouns representing what arrives at the system boundary.

2. **Outputs are THINGS the system produces, not activities.** Each output is a tangible artifact the system generates. No action words describe how outputs are created — the transformation mechanics belong in the Process box and the C1-08 narrative.

3. **Process box contains only the full system title.** Per GUIDE-2 and TEAM_META_GUIDE specifications, the center box names the system by its complete registered title. The sub-bullets inside the Process box provide the architectural mechanisms that transform inputs into outputs — these are the transformation verbs that are explicitly excluded from Input and Output boxes.

4. **Both existing and planned features are represented as ONE unified system.** The IPO diagram does not distinguish between "current" and "future" — it presents the complete system as designed, consistent with the conceptual framework requirement to describe the system holistically. The distinction between existing and planned scope is documented in `SYSTEM_FEATURES.md` and `Existing_and_Planned_Features.md`.

5. **6 inputs and 9 outputs match the TEAM_META_GUIDE specification exactly.** No additional items were added or removed. The descriptions inside each numbered item provide specificity without violating the input/output categorization rules.

### Compliance Verification

| Rule | Status | Notes |
|------|--------|-------|
| Numbered lists (not bullets) inside Input box | ✅ | Items 1-6 |
| Numbered lists (not bullets) inside Output box | ✅ | Items 1-9 |
| Inputs are data/config the system receives | ✅ | No process verbs |
| Outputs are things the system produces | ✅ | No process verbs |
| Process box contains full system title | ✅ | Exact title match |
| Figure caption placed BELOW the figure, bold | ✅ | "**Figure 1.**" below diagram |
| References SYSTEM_FEATURES.md | ✅ | Cross-verified against baseline + advanced scope |
| References Existing_and_Planned_Features.md | ✅ | All features validated as existing or planned |
| Readable on standard page | ✅ | Text-based diagram, printable |
