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

1. **Identify** the existing admission testing processes, operational gaps, role-based coordination requirements, data integrity vulnerabilities, infrastructure constraints, and current baseline task workloads at ISPSC Tagudin Campus that necessitate a unified, cryptographically-secured, role-based digital platform bridging the Guidance Office and Registrar Office workflows.

2. **Develop** SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin, incorporating role-based access control with zero-trust data governance, cryptographic score integrity using HMAC-SHA256 signature verification, automated scoring with computer-vision-based OMR answer sheet ingestion, offline-resilient proctoring via a Progressive Web App with IndexedDB caching and background synchronization, an enhanced AI Companion powered by retrieval-augmented generation for applicant guidance and course recommendations, an enhanced AI-assisted scheduling system with human-in-the-loop constraint optimization, multi-tenant database isolation aligned with the Philippine Data Privacy Act (RA 10173), and validating the system's technical architecture through confusion matrix accuracy metrics for automated grading, expert rubric evaluation for AI faithfulness, and automated penetration and offline resilience audits.

3. **Evaluate** the usability and perceived task workload of the developed system using the System Usability Scale (SUS) and the NASA Task Load Index (NASA-TLX) as administered to intended users, including Registrar Office staff, Guidance Office counselors, proctors, test administrators, and applicants at ISPSC Tagudin Campus.

---

## Critical Evaluation Notes

### Objective 1 — Identify (Operational Assessment)

**Alignment check:**
- Maps to Research Question 1 (operational needs + role-based access needs + security needs + resilience needs + baseline workload)
- Feeds into C2-01 (Research Design — descriptive component) and C2-07 (Data Analysis)
- Informs the "Identify" phase of the AIDLC/RAD methodology in Chapter 2

**Critical rationale:**
- "Current baseline task workloads" has been added to establish a "before" metric. You cannot prove that SecureCAT makes staff jobs easier unless you formally identify how difficult the manual process currently is.

### Objective 2 — Develop (System Construction & Technical Validation)

**Alignment check:**
- Maps to Research Question 2 (security needs + operational resilience needs + architectural requirements + technical validation)
- Feeds into C2-02 (Software Model), C2-03 (Project Plan), and the testing phases of Chapter 3
- Corresponds to the "Develop and Test" phases of the AIDLC/RAD methodology

**Critical rationale:**
- The technical validation metrics (Confusion Matrix, RAGAS-inspired expert rubric, Lighthouse/ZAP audits) are now explicitly woven into the development objective. This successfully shields these advanced engineering features from being judged by a simple usability survey. By categorizing them as "technical architecture validations," you prove they function correctly before the users ever see them.

### Objective 3 — Evaluate the Usability (Evaluation)

**Alignment check:**
- Maps to Research Question 3 (usability evaluation using SUS + workload evaluation using NASA-TLX)
- Feeds into C2-06 (Research Instruments — SUS and NASA-TLX questionnaires) and C2-07 (Data Analysis — Scoring comparisons)
- Corresponds to the "Evaluate" phase of the AIDLC/RAD methodology

**Critical rationale:**
- The structural rule is maintained: "usability" is used, and the forbidden word "acceptability" is strictly avoided.
- The addition of "perceived task workload" justifies the inclusion of the NASA Task Load Index. While the SUS proves the system is easy to learn and navigate, the NASA-TLX proves it actively reduces the mental, physical, and temporal frustration of the Guidance and Registrar staff.

### Structural Compliance Verification

| Rule | Status | Notes |
|------|--------|-------|
| General objective names system by full title | ✅ | "SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin" — exact match |
| Specific objectives use numbered list | ✅ | Items 1, 2, 3 |
| No bullets used | ✅ | Numbered only |
| Three-objective structure: Identify → Develop → Evaluate | ✅ | Standard capstone structure |
| Objective 3 says "usability" only (not "acceptability") | ✅ | SUS measures usability; NASA-TLX measures workload |
| General objective is one paragraph | ✅ | Single paragraph |
| No bold body text (only structural labels) | ✅ | Bold only on "Identify," "Develop," "Evaluate" as structural labels |
| Objectives align with Chapter 2 methodology | ✅ | Identify → Research Design; Develop → Software Model + Technical Validation; Evaluate → Research Instruments + Data Analysis |
| SUS terminology correct | ✅ | "System Usability Scale (SUS)" named with expansion |
| NASA-TLX terminology correct | ✅ | "NASA Task Load Index (NASA-TLX)" named with expansion |
| Target offices named in general objective | ✅ | "Guidance Office and Registrar Office" |
| Target institution named | ✅ | "ISPSC Tagudin Campus" |

### Objective-to-Research-Question Mapping (Preview for C1-10)

| Objective | Dimension Coverage | Research Question (Preview) |
|-----------|-------------------|----------------------------|
| Obj 1 (Identify) | Operational needs + Role-based access needs + Security needs + Resilience needs + Baseline workload | "What are the existing admission testing processes, operational gaps, role-based coordination requirements, data integrity vulnerabilities, infrastructure constraints, and current baseline task workloads at ISPSC Tagudin that necessitate a unified, cryptographically-secured, role-based digital platform?" |
| Obj 2 (Develop) | Security needs + Operational resilience needs + Architectural requirements + Technical validation | "What are the features, security mechanisms, and architectural components required to develop a role-based, cryptographically-secured admission testing system with offline resilience, AI-assisted guidance, multi-tenant data isolation, and validated technical accuracy?" |
| Obj 3 (Evaluate) | Usability evaluation + Workload evaluation | "What is the usability level and perceived task workload of the developed system as evaluated using the System Usability Scale (SUS) and the NASA Task Load Index (NASA-TLX) by intended users at ISPSC Tagudin Campus?" |

> **Note:** This mapping is provided as a bridge to C1-10 (Research Questions). The exact question wording will be finalized in the C1-10 deliverable, but the one-to-one correspondence and dimension coverage are validated here to ensure C1-09 and C1-10 remain perfectly aligned.
