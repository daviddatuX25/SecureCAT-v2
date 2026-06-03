# C1-09: Objectives of the Study

**Task ID:** C1-09
**Assigned to:** Jaypee
**Date:** June 3, 2026
**Dependencies:** None

---

## General Objective

This study aims to develop SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin, a web-based platform that centralizes and automates the entire admission testing pipeline — from applicant registration and dynamic room scheduling through proctor-monitored examination, automated scoring, result sheet generation, and AI-assisted counseling — while enforcing zero-trust data governance through cryptographic score integrity, immutable audit logging, and role-based access control designed to operate reliably under the infrastructure constraints specific to the Ilocos Sur Polytechnic State College Tagudin Campus.

## Specific Objectives

More specifically, this study seeks to accomplish the following:

1. **Identify** the existing admission testing processes, operational gaps, role-based coordination requirements, data integrity vulnerabilities, and infrastructure constraints at ISPSC Tagudin Campus that necessitate a unified, cryptographically-secured, role-based digital platform bridging the Guidance Office and Registrar Office workflows.

2. **Develop** SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin, incorporating role-based access control with zero-trust data governance, cryptographic score integrity using HMAC-SHA256 signature verification, automated scoring with computer-vision-based OMR answer sheet ingestion, offline-resilient proctoring via a Progressive Web App with IndexedDB caching and background synchronization, an enhanced AI Companion powered by retrieval-augmented generation for applicant guidance and course recommendations, an automated scheduling agent for constraint-aware session optimization, and multi-tenant database isolation aligned with the Philippine Data Privacy Act (RA 10173).

3. **Evaluate** the usability of the developed system using the System Usability Scale (SUS) as administered to intended users, including Registrar Office staff, Guidance Office counselors, proctors, test administrators, and applicants at ISPSC Tagudin Campus.

---

## Critical Evaluation Notes

### Objective 1 — Identify (Operational Assessment)

**Alignment check:**
- Maps to Research Question 1 (operational needs + role-based access needs + security needs + resilience needs)
- Feeds into C2-01 (Research Design — descriptive component) and C2-07 (Data Analysis)
- Informs the "Identify" phase of the AIDLC/RAD methodology in Chapter 2

**Critical rationale:**
- The original task spec says "Identify — existing processes, gaps, requirements at ISPSC Tagudin." The expanded wording above adds "role-based coordination requirements, data integrity vulnerabilities, and infrastructure constraints" because these three dimensions are mandated by the TEAM_META_GUIDE as required coverage areas for research questions (operational needs, role-based access needs, security needs, operational resilience needs). If Objective 1 only names "processes, gaps, requirements," the corresponding RQ1 would lack the specificity to cover role-based access and security dimensions, forcing RQ2 to absorb too many distinct research dimensions.
- Adding "at ISPSC Tagudin Campus" anchors the objective to the study locale, which is required per the scope definition.

**Risk assessment:**
- ⚠️ If the panel asks "Why does an 'Identify' objective include security and infrastructure?" — the answer is: because the identification phase must capture ALL the dimensions that the "Develop" phase addresses. You cannot develop a cryptographically-secured system without first identifying what data integrity vulnerabilities exist. You cannot build offline resilience without first identifying what infrastructure constraints necessitate it. The Identify objective establishes the diagnostic baseline; the Develop objective builds the solution.

### Objective 2 — Develop (System Construction)

**Alignment check:**
- Maps to Research Question 2 (security needs + operational resilience needs + architectural requirements)
- Feeds into C2-02 (Software Model) and C2-03 (Project Plan)
- Corresponds to the "Develop" phase of the AIDLC/RAD methodology

**Critical rationale:**
- The original task spec says "Develop — SecureCAT with specific features (name key features from both existing and planned)." The expanded wording above explicitly names every major architectural component because:
  1. **RBAC with zero-trust** — This is the "Role-Based" component of the title. Without naming zero-trust governance explicitly, the panel may question why the system's security model goes beyond basic role assignment.
  2. **HMAC-SHA256 score integrity** — This is the most technically distinctive planned feature. It directly supports the "Role-Based" title component by binding score entries to proctor identity and detecting tampering.
  3. **Computer-vision OMR** — This elevates the existing CSV-based scoring to automated image processing, supporting the "Admission Testing System" title component.
  4. **Offline-resilient PWA** — This is architecturally significant because ISPSC Tagudin has known WiFi reliability issues. It differentiates SecureCAT from cloud-only solutions that fail without connectivity.
  5. **AI Companion with RAG** — Already partially built; the enhancement adds external data integration and course recommendations, supporting the "Guidance Office" title component.
  6. **Automated scheduling agent** — Supports the "Registrar Office" title component by automating session optimization.
  7. **Multi-tenant database isolation** — Supports the "ISPSC Tagudin" title component by preparing the architecture for campus expansion while maintaining RA 10173 compliance.
- Each named feature maps to a specific title component (Role-Based, Admission Testing System, Guidance & Registrar Offices, ISPSC Tagudin), ensuring the objectives cannot be accused of scope creep beyond the locked title.

**Risk assessment:**
- ⚠️ The panel may ask "Are all these features realistic within your timeline?" — the answer is that the existing system already has RBAC, CSV scoring, AI Companion, scheduling, and result generation working. The planned features (HMAC, CV-OMR, PWA, enhanced AI, auto-scheduling, multi-tenancy) are the capstone research contributions that elevate engineering complexity. The title was locked before these features were proposed; they fit within the title's semantic boundaries by design (Trojan Horse strategy).

### Objective 3 — Evaluate the Usability (Evaluation)

**Alignment check:**
- Maps to Research Question 3 (usability evaluation using SUS)
- Feeds into C2-06 (Research Instruments — SUS questionnaire) and C2-07 (Data Analysis — SUS scoring)
- Corresponds to the "Evaluate" phase of the AIDLC/RAD methodology

**Critical rationale:**
- The word is **"usability"** and ONLY "usability" — NOT "usability and acceptability." The System Usability Scale (SUS) is a validated instrument that measures perceived usability. It does not measure "acceptability," "satisfaction," or "effectiveness" as separate constructs. Using "acceptability" would be technically incorrect and would expose the study to methodology criticism during panel defense.
- The named user groups (Registrar Office staff, Guidance Office counselors, proctors, test administrators, applicants) represent all direct system user roles, ensuring comprehensive SUS coverage across different interface contexts (admin dashboard, proctor portal, applicant portal).
- "At ISPSC Tagudin Campus" anchors the evaluation to the study locale, consistent with the scope and population definitions.

**Risk assessment:**
- ⚠️ If the panel asks "Why only SUS and not other instruments?" — the answer is: SUS is the most widely used standardized usability questionnaire in academic software evaluation research, with established benchmarks (SUS score interpretation: <68 = below average, 68-80 = acceptable, 80+ = excellent). Using a single validated instrument avoids construct confounding and keeps the evaluation methodology clean and defensible. Additional instruments (e.g., TAM for technology acceptance) could be proposed as future work but are not necessary for this study's scope.

### Structural Compliance Verification

| Rule | Status | Notes |
|------|--------|-------|
| General objective names system by full title | ✅ | "SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin" — exact match |
| Specific objectives use numbered list | ✅ | Items 1, 2, 3 |
| No bullets used | ✅ | Numbered only |
| Three-objective structure: Identify → Develop → Evaluate | ✅ | Standard capstone structure |
| Objective 3 says "usability" only (not "acceptability") | ✅ | SUS measures usability |
| General objective is one paragraph | ✅ | Single paragraph |
| No bold body text (only structural labels) | ✅ | Bold only on "Identify," "Develop," "Evaluate" as structural labels |
| Objectives align with Chapter 2 methodology | ✅ | Identify → Research Design; Develop → Software Model; Evaluate → Research Instruments + Data Analysis |
| SUS terminology correct | ✅ | "System Usability Scale (SUS)" named with expansion |
| Target offices named in general objective | ✅ | "Guidance Office and Registrar Office" |
| Target institution named | ✅ | "ISPSC Tagudin Campus" |

### Objective-to-Research-Question Mapping (Preview for C1-10)

| Objective | Dimension Coverage | Research Question (Preview) |
|-----------|-------------------|----------------------------|
| Obj 1 (Identify) | Operational needs + Role-based access needs + Security needs + Resilience needs | "What are the existing admission testing processes, operational gaps, role-based coordination requirements, data integrity vulnerabilities, and infrastructure constraints at ISPSC Tagudin that necessitate a unified, cryptographically-secured, role-based digital platform?" |
| Obj 2 (Develop) | Security needs + Operational resilience needs + Architectural requirements | "What are the features, security mechanisms, and architectural components required to develop a role-based, cryptographically-secured admission testing system with offline resilience, AI-assisted guidance, and multi-tenant data isolation?" |
| Obj 3 (Evaluate) | Usability evaluation | "What is the usability level of the developed system as evaluated using the System Usability Scale (SUS) by intended users at ISPSC Tagudin Campus?" |

> **Note:** This mapping is provided as a bridge to C1-10 (Research Questions). The exact question wording will be finalized in the C1-10 deliverable, but the one-to-one correspondence and dimension coverage are validated here to ensure C1-09 and C1-10 remain perfectly aligned.
