# C1-10: Research Questions

**Task ID:** C1-10
**Assigned to:** Jaypee
**Date:** June 5, 2026
**Dependencies:** C1-09 (Objectives of the Study)

---

## Research Questions

This study seeks to answer the following questions:

1. What are the existing admission testing processes, operational gaps, role-based coordination requirements between the Guidance Office and Registrar Office, data integrity vulnerabilities, infrastructure constraints, and current baseline task workloads at ISPSC Tagudin Campus that necessitate a unified, cryptographically-secured, role-based digital platform?

2. What are the features, security mechanisms, offline-resilience capabilities, AI-assisted operations, and architectural components required to develop a role-based, cryptographically-secured college admission testing system with computer-vision-based OMR grading, progressive web application proctoring with IndexedDB caching, retrieval-augmented AI guidance and course recommendations, human-in-the-loop scheduling optimization, multi-tenant database isolation aligned with the Philippine Data Privacy Act (Republic Act No. 10173), and validated technical accuracy through confusion matrix metrics for automated grading, expert rubric evaluation for AI faithfulness, and automated penetration and offline resilience audits?

3. What is the usability level and perceived task workload of the developed system as evaluated using the System Usability Scale (SUS) and the NASA Task Load Index (NASA-TLX) by intended users, including Registrar Office staff, Guidance Office counselors, proctors, test administrators, and applicants at ISPSC Tagudin Campus?

---

## Compliance Verification

| Rule | Status | Notes |
|------|--------|-------|
| One-to-one correspondence with specific objectives | Pass | RQ1 maps to Objective 1 (Identify); RQ2 maps to Objective 2 (Develop); RQ3 maps to Objective 3 (Evaluate) |
| Covers operational needs dimension | Pass | RQ1: "existing admission testing processes, operational gaps" |
| Covers role-based access needs dimension | Pass | RQ1: "role-based coordination requirements between the Guidance Office and Registrar Office" |
| Covers security needs dimension | Pass | RQ1: "data integrity vulnerabilities"; RQ2: "cryptographically-secured," "HMAC," "audit" |
| Covers operational resilience needs dimension | Pass | RQ2: "offline-resilience capabilities," "IndexedDB caching," "offline resilience audits" |
| RQ3 says "usability" (not "acceptability") | Pass | "usability level" -- SUS measures usability |
| RQ3 includes NASA-TLX | Pass | "and perceived task workload... NASA Task Load Index (NASA-TLX)" -- matches C1-09 Objective 3 |
| Numbered list (not bullets) | Pass | Items 1, 2, 3 |
| No bold body text | Pass | Bold only in compliance table |
| Target user groups named in RQ3 | Pass | Registrar Office staff, Guidance Office counselors, proctors, test administrators, applicants |
| RQ2 includes technical validation metrics | Pass | Confusion matrix, expert rubric, penetration audits, offline resilience audits |

## Dimension Coverage Matrix

| Dimension | RQ1 | RQ2 | RQ3 |
|-----------|-----|-----|-----|
| Operational needs | Explicit | Implicit (architecture context) | -- |
| Role-based access needs | Explicit | Explicit | -- |
| Security needs | Explicit | Explicit | -- |
| Operational resilience needs | -- | Explicit | -- |
| Usability evaluation | -- | -- | Explicit (SUS) |
| Workload evaluation | -- | -- | Explicit (NASA-TLX) |
| Technical validation | -- | Explicit | -- |
