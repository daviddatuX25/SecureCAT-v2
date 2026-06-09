# C2-02: Software Model

**Task ID:** C2-02
**Assigned to:** David
**Date:** June 5, 2026
**Dependencies:** C2-01 (Research Design)

---

## Software Model

[indent] The development of SecureCAT follows the AI-Driven Development Lifecycle (AIDLC), a software engineering framework that replaces traditional human-only development models with structured AI-human collaboration workflows (Addla, 2026; Raja, 2025). Traditional lifecycles, such as the Rapid Application Development (RAD) model, were formulated for human-only team dynamics and do not account for the code generation and testing patterns characteristic of current software development (Raja, 2025). In contrast, the AIDLC framework uses artificial intelligence agents as partners in the design, construction, and verification of software systems, enabling a single developer to cover roles traditionally distributed across entire teams. By adopting this model, the researcher provides an accurate representation of the development process of SecureCAT, which was constructed using AI assistants (Gemini, Claude, and GitHub Copilot) for code generation, architectural planning, and test automation under human oversight (Addla, 2026). The AIDLC model has three linear phases: Inception, Construction, and Operations.

           AI-Driven Development Lifecycle (AIDLC) software model
           
┌─────────────────────────┐     ┌─────────────────────────┐     ┌─────────────────────────┐
│        INCEPTION        │     │      CONSTRUCTION       │     │       OPERATIONS        │
├─────────────────────────┤     ├─────────────────────────┤     ├─────────────────────────┤
│ • Mob Elaboration       │     │ • Mob Construction      │     │ • Pilot Deployment      │
│ • Requirements Trans.  │     │ • AI-assisted Coding    │     │ • AI-assisted Monitor   │
│ • Schema & Architecture │ ──> │ • Automated Unit Tests  │ ──> │ • Audit Verification    │
│ • AI-Agent Guidance     │     │ • Human Review & QA     │     │ • SUS & TLX Evaluation  │
│                         │     │ • Rapid Prototyping     │     │ • ML Microservice Valid.│
└─────────────────────────┘     └─────────────────────────┘     └─────────────────────────┘

**Figure 2.** AI-Driven Development Lifecycle (AIDLC) Phases of SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin

[indent] Inception. In this phase, the development requirements and software specifications were conceptualized through Mob Elaboration, wherein the researcher collaborated with AI assistants to translate observed operational challenges at ISPSC Tagudin Campus into structured technical and architectural requirements. These challenges were identified through interview data gathering with guidance and registrar office staff at ISPSC Tagudin, which confirmed critical process gaps: manual scoring of answer sheets using paper-based keys, informal scheduling of entrance examinations without centralized coordination, and the absence of audit trails for score modifications and examinee records [EVIDENCE: interview data — confirmed process gaps: manual scoring, informal scheduling, no audit trails]. During this phase, the researcher used conversational and code-generation models to design the multi-tenant database schema, outline the system's role-based access control (RBAC) rules, plan the application's service architecture, and define the technical interfaces for the proctor, guidance staff, and registrar roles. The deliverables of the Inception phase included the conceptual data models, API endpoints, mockups of the public and admin portals, and the feature specifications for the offline-resilient Progressive Web Application (PWA), cryptographic score verification, and AI-assisted scheduling modules.

[indent] Construction. In this phase, the system modules were developed and tested using a Mob Construction workflow, where AI coding agents generated backend and frontend code while the researcher acted as the architect, reviewer, and quality gate. The backend was constructed using PHP 8.4 and the Laravel 12 framework, using Inertia.js v2 to bridge server-side routing with the Svelte 5 frontend component library, with styling powered by Tailwind CSS v4. One capability built during this phase is the Optical Mark Recognition (OMR) scanning module, which was designed and implemented from scratch as a software-based image processing feature that enables the system to capture and score examinee answer sheets directly from scanned images — this is a newly developed capability within SecureCAT and does not replace any pre-existing OMR system at ISPSC [EVIDENCE: OMR scanning is a new built capability, not a replacement of existing OMR infrastructure]. Additionally, the ML-assisted course triage module was constructed by adopting school-owned K-Means algorithm source code from prior ISPSC institutional studies (Yukee et al., 2025; Ballesteros et al., 2025) and wrapping it as a Python microservice (FastAPI) that communicates with the Laravel backend via internal API — this architectural decision keeps the ML engine in its native language (scikit-learn) while enabling bidirectional data streaming between the admission platform and the clustering service. During coding, the AI agents generated the core database migrations, Eloquent models, controllers, and services — specifically implementing the HMAC-SHA256 score signing algorithm, the write-only immutable audit logging system, the OMR image processing pipeline, the ML triage microservice integration layer, and the offline IndexedDB synchronization middleware. Automated unit and feature testing was integrated into the construction loop; tests ran using PHPUnit 11 to validate the behavioral correctness of the AI-generated code, so code quality defects or security vulnerabilities were flagged and corrected before code integration.

[indent] Operations. In this phase, the system underwent pilot deployment, automated monitoring, and empirical evaluation. The SecureCAT application was deployed on a local server environment to replicate the network conditions of the ISPSC Tagudin campus, and database seeders were executed to populate the system with simulated applicant records and test schedules. The researcher used automated scripts and AI diagnostic assistants to monitor system resource consumption, audit log performance, and OMR image scanning accuracy — validating the newly built OMR scanning capability under realistic conditions — confirming that the platform operates reliably under simulated peak load [EVIDENCE: OMR scanning validated in Operations as a newly constructed feature, not a legacy integration]. Finally, the operations phase ended with the administrative preparation for the usability and workload assessment, where the evaluation procedures using the System Usability Scale (SUS) and NASA Task Load Index (NASA-TLX) were finalized to gather quantitative and qualitative feedback from the target respondents.

---

## References used (Draft entries for CC-01 compilation)

Addla, N. (2026). AI-Driven Development Lifecycle (AI-DLC): Reimagining software engineering for the AI era. *International Journal of Artificial Intelligence, Data Science, and Machine Learning*, 7(1), 266–270. https://doi.org/10.63282/3050-9262.IJAIDSML-V7I1P145

Raja, S. P. (2025, August 12). AI-driven development life cycle: Reimagining software engineering. *AWS DevOps Blog*. https://aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/

---

## Source verification log

| Source | Verification Method | Status |
|--------|-------------------|--------|
| Addla (2026) | Crossref API — DOI 10.63282/3050-9262.IJAIDSML-V7I1P145 confirmed. Title: "AI-Driven Development Lifecycle (AI-DLC): Reimagining Software Engineering for the AI Era." *IJAIDSML*, 7(1), 266–270. | ✅ Verified |
| Raja (2025) | HTTP 200 confirmed at aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/. Author: S. P. Raja, Principal Solutions Architect at AWS. Title verified in page `<title>` tag. | ✅ Verified |

---

## Compliance verification

| Rule | Status | Notes |
|------|--------|-------|
| Subheading reads exactly "Software Model" | ✅ | Matches spelling and capitalization rules |
| Three phases named: Inception, Construction, Operations | ✅ | Matches AIDLC specification |
| Figure shows the AIDLC three-phase linear diagram | ✅ | ASCII diagram represents the three-phase linear flow |
| Figure caption placed BELOW the figure, bold | ✅ | Included below the diagram in bold font |
| Addla (2026) cited in the body | ✅ | Primary academic reference cited |
| Raja (2025) cited in the body | ✅ | Industry authority reference cited |
| All phase descriptions in paragraph form only | ✅ | No bullets or lists are used in the phase descriptions |
| Names specific tools and technologies in Construction | ✅ | Names PHP 8.4, Laravel 12, Inertia.js v2, Svelte 5, Tailwind CSS v4, PHPUnit 11, and AI tools |
| Phase descriptions use "Phase Name. In this phase..." | ✅ | Follows the exact formatting pattern |
