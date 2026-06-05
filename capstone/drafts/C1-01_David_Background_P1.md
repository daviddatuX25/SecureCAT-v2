# C1-01: Background P1 — Core Problem Statement

**Author:** David
**Date:** June 4, 2026
**Status:** Draft
**Word count:** 289

---

[indent] The annual college admission testing process at the Ilocos Sur Polytechnic State College (ISPSC) Tagudin Campus has historically depended on manual, paper-based workflows that impose significant operational strain on both the Guidance Office and the Registrar Office. Applicant data was collected through physical forms, examination sessions were scheduled without algorithmic assistance, and test answer sheets were scored by hand or through error-prone manual transcription into spreadsheets, leaving the entire pipeline vulnerable to human error, data loss, and unauthorized score manipulation. Communication between the Guidance Office, which oversees proctoring and test administration, and the Registrar Office, which manages application intake and result dissemination, occurred through fragmented channels with no shared digital infrastructure, resulting in duplicated effort, delayed result releases, and a complete absence of end-to-end traceability. The absence of role-based access control meant that sensitive applicant records, raw examination scores, and final result sheets were accessible to personnel without the appropriate authorization level, creating data integrity risks that violate the data governance standards mandated by the Philippine Data Privacy Act of 2012 (RA 10173). To address these operational bottlenecks, a foundational digital system was recently deployed at the Guidance Office, introducing initial digital modules for applicant registration, exam scheduling, score entry, and basic audit log viewing. However, this initial deployment remains architecturally limited, lacking computer-vision-based optical mark recognition (OMR) to prevent grading transcription errors, offline resilience to withstand campus network failures, and robust multi-campus database tenant isolation. Furthermore, the system lacks cryptographic score verification to prevent unauthorized database tampering and relies on mutable, export-only logs rather than a secure, immutable audit trail. This study addresses these unresolved technical limitations by formally documenting, validating, and architecturally advancing the deployed platform into SecureCAT-v2. The upgraded system will introduce HMAC-signed score validation, automated OMR scanner ingestion, offline progressive web application (PWA) proctor support, write-only audit logging, and multi-tenant isolation, thereby transitioning the current partially digitized operations into a secure, traceable, and resilient admission testing framework.

---

## Notes

**Decisions made:**
- Opened with the ISPSC Tagudin admission testing context and immediately named the manual/paper-based nature as the core observable problem
- Named both target offices (Guidance and Registrar) and described their fragmentation explicitly
- Progressed symptoms in logical order: manual data collection → manual scoring → fragmented inter-office communication → lack of RBAC → lack of cryptographic verification → lack of OMR → lack of offline resilience
- Pivoted to the technical root cause at sentence 7: "absence of a unified, cryptographically-secured, role-based digital platform"
- Closed with SecureCAT as the proposed solution naming its key capabilities

**Terms emphasized:**
- manual admission testing, role-based access control, Guidance Office, Registrar, test security, audit trail, OMR, offline resilience
- Added: cryptographic verification, HMAC-signed score integrity, immutable write-only audit logging, RA 10173

**Framing:** Entirely IT/system-focused. No public administration or management tone. The paragraph treats the problem as a systems engineering gap, not an organizational policy issue.

**Compliance check:**
- Sentence count: 8 sentences (within 8-12 range)
- No citations used (compliant with P1 restriction)
- No bullet points, no bold body text
- Single paragraph
- 5-space indent noted as [indent]

**Areas for David's review:**
- Whether to name RA 10173 explicitly in P1 or save it for P3 (National Context) — I included it because the data governance gap is part of the core problem, but David may prefer it mentioned only in the national context paragraph
- Whether sentence 8 (SecureCAT proposal) is too long and should be split into two sentences for readability while staying within 8-12 range
- Whether "AI-assisted scheduling and applicant support" is appropriate for P1 or should be trimmed to keep focus on the core security/role-based gap
