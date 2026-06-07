# C1-08: Conceptual Framework — Narrative

**Task ID:** C1-08
**Assigned to:** Jaypee
**Date:** June 5, 2026
**Dependencies:** C1-07 (IPO Diagram)

---

## Narrative

The system receives six distinct categories of input that collectively drive the admission testing pipeline at ISPSC Tagudin. Applicant data, comprising personal information, academic preferences, application forms, general weighted average records, and course selections, enters the system during the enrollment phase and forms the foundation upon which all downstream processing depends. Exam configurations, which include aptitude area definitions, scoring formulas, rating scales, session parameters, and academic year settings, define the structural and evaluative rules the system applies when administering and grading tests. OMR images and scans—representing a new capability introduced by SecureCAT to replace ISPSC's current manual scoring workflow—capture physical answer sheet photographs and bubble sheet scans for computer-vision-based grading, alongside machine-readable CSV score imports, providing the raw score source material that the automated scoring pipeline consumes. Role credentials encompass user authentication data, role-based access tokens, Google OAuth identifiers, and proctor and administrator authorization credentials, which the system uses to enforce access boundaries through Laravel policy classes and zero-trust data governance. QR scans capture examinee QR code reads at exam room doors for attendance verification, identity confirmation, and session check-in, including during offline periods where data is persisted locally in IndexedDB. Natural language queries from applicants, covering questions about admission requirements, course recommendations, exam schedules, and institutional policies, are submitted to the AI Companion, which processes them through retrieval-augmented generation using local vector embeddings to produce contextually relevant responses. Together, these six input categories supply the data, configuration, authentication, and interaction signals that SecureCAT requires to execute its role-based admission testing workflow across the Guidance and Registrar Offices.

The system transforms these inputs through a coordinated set of mechanisms that enforce role-based access control with zero-trust data governance, ensuring that each authenticated user interacts only with the data and operations their assigned role permits. Cryptographic score integrity is maintained via HMAC-SHA256 signature verification, which locks finalized scores against unauthorized modification, while immutable write-only audit logging—a new system capability—captures every security-sensitive action with actor identity, IP address, user agent, and before-and-after database state snapshots. The automated scoring pipeline processes OMR images through computer-vision ingestion and CSV imports, applying the scoring formulas and rating scales defined in the exam configurations to produce per-applicant score breakdowns, percentile rankings, and component-level performance reports; raw scores are then mapped to standardized scores via the conversion table defined in the simulation guide [SIM-GUID-23], ensuring consistent and reproducible scaling across exam sessions. Offline-resilient proctoring operates through a Progressive Web App with Service Workers and IndexedDB caching, allowing proctors to continue scanning QR codes and confirming attendance even when campus connectivity is unreliable, with background synchronization restoring records once network access resumes. AI-assisted operations—a new system capability—use retrieval-augmented generation with local vector embeddings to power the AI Companion for applicant guidance and course recommendations, while the enhanced AI-assisted scheduling system generates optimized session proposals for registrar admin review through a human-in-the-loop constraint engine. Multi-tenant database isolation segregates tenant data in conformance with the Philippine Data Privacy Act (Republic Act No. 10173), preparing the architecture for future campus expansion. Through these mechanisms, the system produces nine categories of output: real-time status tracking displays that visualize the applicant lifecycle from submission through result release; exam schedules with session assignments, room allocations, and proctor assignments; score reports with per-applicant aptitude breakdowns; immutable audit logs with full action provenance; customizable result sheets in single, bulk PDF, and bulk DOCX formats with dynamic watermarks; consultation summaries documenting counselor notes and course recommendations; AI Companion responses delivering natural language answers and intelligent course recommendations; offline-cached records persisted in IndexedDB and synchronized upon reconnection; and statistical reports with admission analytics and exportable data for institutional reporting.

---

## Compliance Verification

| Rule | Status | Notes |
|------|--------|-------|
| Exactly 2 paragraphs | Pass | Paragraph 1 covers inputs; Paragraph 2 covers process and outputs |
| Paragraph 1 explains all 6 inputs | Pass | Applicant data, exam configs, OMR images, role credentials, QR scans, natural language queries |
| Paragraph 2 explains transformation mechanism | Pass | RBAC, HMAC, audit logging, automated scoring, offline PWA, AI/RAG, multi-tenancy |
| Paragraph 2 describes all 9 outputs | Pass | Status tracking, exam schedules, score reports, audit logs, result sheets, consultation summaries, AI responses, offline records, statistical reports |
| No new inputs introduced beyond C1-07 | Pass | All 6 inputs match the IPO diagram |
| No new outputs introduced beyond C1-07 | Pass | All 9 outputs match the IPO diagram |
| Existing + planned presented as unified system | Pass | No "current vs. future" language; all features described as one system |
| No bullet points | Pass | Paragraph form only |
| No bold body text | Pass | Bold only for this compliance table header row |
| No citations required | Pass | None included |
| Figure caption below (referenced from C1-07) | N/A | Figure is in C1-07 deliverable; narrative references the diagram |
