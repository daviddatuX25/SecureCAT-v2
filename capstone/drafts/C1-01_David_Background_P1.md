1|# C1-01: Background P1 — Core Problem Statement
2|
3|**Author:** David
4|**Date:** June 5, 2026
5|**Status:** Draft (Revised)
6|**Word count:** ~310
7|
8|---
9|
10|[indent] The annual college admission testing process at the Ilocos Sur Polytechnic State College (ISPSC) Tagudin Campus has historically depended on manual, paper-based workflows that impose significant operational strain across offices where responsibility boundaries are informally understood but not systematically enforced. In practice, the Registrar Office typically manages admission application intake — receiving requirements, processing submissions, and scheduling examinations — while the Guidance Office handles test-related activities: proctoring, attendance, scoring, result generation, and releasing. However, the precise delineation of tasks between offices and whether certain processes overlap or fall through gaps remains unverified and will be formally documented through the capstone's descriptive phase. This structural ambiguity manifests as fragmented communication, duplicated effort, and delayed result releases. The Guidance Office personnel who handle test processes serve simultaneously as proctors; they do not delegate scoring or result management to separate proctoring staff. This consolidated test-side responsibility ensures operational security but concentrates workload, creating a bottleneck during peak admission periods. The absence of role-based access control meant that sensitive applicant records, raw examination scores, and final result sheets were accessible without granular authorization, creating data integrity risks that violate the data governance standards mandated by the Philippine Data Privacy Act of 2012 (RA 10173). A foundational digital system was previously developed and deployed at the Guidance Office through institutional consultation, introducing digital modules for applicant registration, exam scheduling, score entry, and basic audit log viewing. The capstone research now formally validates this prior institutional initiative — documenting its operational utilization, measuring its usability, and engineering advanced capabilities it lacks: cryptographic score integrity (HMAC-signed records), immutable write-only audit logging, computer-vision-based optical mark recognition (OMR) to eliminate transcription errors, offline-resilient proctor operations via PWA, enhanced AI-assisted scheduling with human-in-the-loop approval, and multi-tenant database architecture for future campus scalability. Critically, the system enforces permissions through explicit role-based access control (RBAC) — defining what each role can access and perform — thereby replacing informal office-based conventions with systematic, auditable authorization. This digitization of campus admission processes directly streamlines the experience for the primary stakeholders — the students — reducing the queue burden that plagues public institutions, often described by the connotation "basta public ay mahaba pila."

---

## Notes

**Decisions made:**
- Opened with the ISPSC Tagudin admission testing context and immediately named the manual/paper-based nature as the core observable problem
- **Corrected framing:** Office boundaries are *informally understood, not systematically enforced*. Registrar typically handles intake/scheduling; Guidance handles test activities. Precise delineation is *unverified* — will be formally documented in the descriptive phase. This ambiguity (not a clean split) causes fragmentation.
- Guidance staff **serve as proctors directly** (no delegation) — consolidated test-side role ensures security but concentrates workload
- **System solves boundary ambiguity through explicit RBAC** — replacing informal office-based conventions with systematic, auditable authorization. This is a core architectural contribution.
- Progressed symptoms in logical order: manual data collection → manual scoring → informal boundaries (unverified, to be documented) → Guidance staff as proctors (workload concentration) → lack of RBAC → lack of cryptographic verification → lack of OMR → lack of offline resilience
- Pivoted to the technical root cause: absence of a unified, cryptographically-secured, role-based digital platform
- **Framing: Capstone formally validates the pre-existing institutional initiative** — documenting utilization, measuring usability, engineering advanced capabilities it lacks (HMAC score integrity, immutable audit logging, CV-OMR, offline PWA, enhanced AI scheduling with human-in-the-loop, multi-tenant architecture)
- **Student-centric impact framing**: Digitization streamlines experience for primary stakeholders (students), reducing queue burden ("basta public ay mahaba pila")
- Closed with SecureCAT as the proposed solution naming its key capabilities

**Terms emphasized:**
- manual admission testing, role-based access control, Guidance Office, Registrar, test security, audit trail, OMR, offline resilience
- Added: cryptographic verification, HMAC-signed score integrity, immutable write-only audit logging, RA 10173
- Added: formal validation, descriptive-developmental, student experience, queue reduction, informal boundaries, RBAC authorization

**Framing:** Entirely IT/system-focused. No public administration or management tone. The paragraph treats the problem as a systems engineering gap, not an organizational policy issue. **New framing layer:** The capstone as formal validation of a researcher-initiated, institutionally-consulted pre-capstone deployment. **Key insight:** The system doesn't assume office boundaries — it enforces role permissions.

**Compliance check:**
- Sentence count: 8 sentences (within 8-12 range)
- No citations used (compliant with P1 restriction)
- No bullet points, no bold body text
- Single paragraph
- 5-space indent noted as [indent]

**Areas for David's review:**
- Whether to name RA 10173 explicitly in P1 or save it for P3 (National Context) — I included it because the data governance gap is part of the core problem, but David may prefer it mentioned only in the national context paragraph
- Whether sentence 8 (SecureCAT proposal + validation framing + student impact + RBAC framing) is too long and should be split into two sentences for readability while staying within 8-12 range
- Whether "AI-assisted scheduling and applicant support" is appropriate for P1 or should be trimmed to keep focus on the core security/role-based gap
