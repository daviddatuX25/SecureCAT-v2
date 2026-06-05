# Research Argument Bank — SecureCAT-v2
## Structured Arguments by Chapter Section

**Purpose:** Every team member should reference this file before writing their assigned section. Each entry gives you the *argument to make*, the *evidence to cite*, and the *connection to the system*.

**Usage:** Find your task ID → Read the arguments listed → Build your paragraph around them.

> **NOTE ON CHAPTER 2 (June 2026 Update):** Chapter 2 has been restructured to match the BSIT Capstone Template and is now titled **METHODOLOGY** (Research Design, Software Model, Project Plan, Project Assignment, Population and Locale, Research Instruments, Data Analysis). The old C2-01 through C2-08 sections that previously covered "Review of Related Literature" topics (RBAC, OMR, AI/RAG, PWA, Data Architecture, Related Systems, Technical Framework, Conceptual Framework Prose) have been removed from this section. Their arguments and evidence are still valid — use them when writing **Chapter 1 Background paragraphs (P2-P4)** instead. Additionally, the old "Related Systems" comparative analysis content is useful for **C1-05 (Synthesis & Gap)**. The new METHODOLOGY tasks (C2-01 through C2-07) are described below.

---

## Chapter 1 Arguments

---

### C1-01: Core Problem Statement
**Goal:** Establish that SUC admission testing is operationally fragmented, insecure, and non-scalable — in 8-12 sentences, no citations.

| # | Argument | Detail |
|---|----------|--------|
| 1 | **Operational fragmentation (Historical Manual State)** | Prior to the deployment of the foundational digital platform, admission workflows were split across disconnected manual processes — paper forms, spreadsheets, physical rosters, handwritten scores. No single system managed the full lifecycle, and the subsequent foundational deployment remains architecturally fragmented without resolving core security and scalability gaps. |
| 2 | **Informal office boundaries with unverified task delineation** | The Registrar Office typically manages admission intake (requirements, submissions, scheduling) and the Guidance Office handles test activities (proctoring, scoring, releasing), but the precise boundaries are informally understood, not systematically enforced. Overlaps and gaps remain unverified — to be formally documented in the descriptive phase. This ambiguity manifests as fragmented communication, duplicated effort, and delayed releases. |
| 3 | **Guidance staff serve as proctors (consolidated test-side role)** | Guidance personnel handle proctoring, scoring, and result management directly — no delegation to separate proctoring staff. This consolidated test-side responsibility ensures operational security but concentrates workload, creating a bottleneck during peak periods. |
| 4 | **Scoring vulnerability** | Test scores pass through multiple human transcription points (paper → spreadsheet → record). Each transcription is an error-injection opportunity. There is no cryptographic verification that a score hasn't been altered. |
| 5 | **Data silo risk for multi-campus institutions** | ISPSC operates multiple campuses. A single-campus standalone system isolates Tagudin's data from the wider institutional network, preventing future cross-campus analytics and capacity sharing. |
| 6 | **Native app overhead for seasonal users** | Applicants interact with the admission system once or twice in their academic lifetime. Requiring a native mobile app installation for this brief interaction is disproportionate — especially for applicants from rural areas with budget devices and limited storage. |
| 7 | **Seasonal volume spikes** | Admission periods create predictable but intense surges. Current manual processes cannot absorb these surges without proportional staffing increases, which SUCs rarely have budget for. |
| 8 | **Lack of document automation** | Admission slips, result sheets, and consultation summaries are prepared manually. This is slow, error-prone, and inconsistent across applicants. |

**Narrative flow:** Start with observable symptoms (1, 2, 3, 7) → pivot to underlying technical root causes (4, 5, 6, 8) → frame as an IT problem, not a public administration problem. **Key framing:** The system solves boundary ambiguity through explicit RBAC — replacing informal conventions with systematic, auditable authorization.

---

### C1-02: Global Context
**Goal:** 12-15 sentences showing how this problem is addressed internationally. Min 5 APA citations (2022-2026).

| # | Argument | What to cite |
|---|----------|-------------|
| 1 | **Global shift to online admission** | Universities worldwide have transitioned to digital intake — cite efficiency gains, error reduction, applicant satisfaction studies |
| 2 | **Secure assessment platforms** | International education systems increasingly use role-based access and data integrity measures for assessment data |
| 3 | **AI in university admission guidance** | Post-2023 explosion of chatbots and AI assistants in higher education enrollment contexts |
| 4 | **Automated scoring technologies** | Global adoption of OMR, computer-aided assessment, and image-based grading — especially in resource-constrained settings |
| 5 | **Lightweight web access over native apps** | Trend toward PWAs and web-based access for institutional services — particularly for infrequent/seasonal users |
| 6 | **Scalable multi-campus systems** | How multi-site universities manage shared infrastructure while maintaining data isolation |

**Key synthesis to build toward:** "Globally, admission systems are converging toward integrated digital platforms that combine security, automation, and intelligent guidance — but this convergence has not reached Philippine state university campuses."

---

### C1-03: National Context — Philippines
**Goal:** 12-15 sentences on Philippine-specific context. Min 5 APA citations (2022-2026).

| # | Argument | What to cite |
|---|----------|-------------|
| 1 | **CHED digitization mandates** | CMO directives on HEI information systems, enrollment management, quality assurance |
| 2 | **RA 10173 (Data Privacy Act)** | Data privacy requirements for educational institutions handling applicant PII |
| 3 | **SUC digital readiness gap** | Studies showing that Philippine state universities lag behind private HEIs in IT adoption |
| 4 | **National enrollment system initiatives** | CHED's push for standardized enrollment/admission platforms |
| 5 | **Infrastructure constraints in regional SUCs** | Internet connectivity, device availability, and IT staffing challenges in provincial campuses |
| 6 | **PH education sector digitization post-pandemic** | How COVID-19 accelerated digital transformation in PH HEIs but left admission workflows behind |

**Key synthesis to build toward:** "While national policy mandates digitization, the implementation gap in regional SUCs means admission testing — a high-stakes, data-sensitive process — remains largely manual."

---

### C1-04: Local Context — ISPSC Tagudin
**Goal:** 12-15 sentences on ISPSC Tagudin specifically. Min 5 APA citations (2022-2026).

| # | Argument | Source |
|---|----------|--------|
| 1 | **ISPSC's multi-campus structure** | ISPSC has campuses across Ilocos Sur — Tagudin is one of several, each currently operating in isolation |
| 2 | **Informal office boundaries; task delineation unverified** | The Registrar Office typically manages admission intake (requirements, submissions, scheduling) and the Guidance Office handles test activities (proctoring, scoring, releasing), but precise boundaries are informally understood, not systematically enforced. Overlaps and gaps remain unverified — to be formally documented in the capstone's descriptive phase. This ambiguity manifests as fragmented communication, duplicated effort, and delayed releases. |
| 3 | **Guidance staff serve as proctors (consolidated test-side role)** | Guidance personnel handle proctoring, scoring, and result management directly — no delegation to separate proctoring staff. This consolidated test-side responsibility ensures operational security but concentrates workload, creating a bottleneck during peaks. |
| 4 | **Foundational digital system deployed but architecturally limited** | A first-generation digital admission system was developed and deployed at ISPSC Tagudin through prior institutional consultation (Phase 1, see Development Chronology). The Guidance Office has access and has been suggested to use it for result generation, new applications, and direct assessment. However, the system lacks cryptographic score integrity, role-based policy enforcement at system level, offline resilience, and scalable multi-campus architecture. Crucially, the extent of actual adoption versus remaining manual processes requires formal verification through the capstone's descriptive phase. |
| 5 | **Capstone formally validates the pre-existing institutional initiative** | The foundational system was built by the researcher during 3rd year (pre-capstone) through institutional consultation. The capstone now applies a descriptive-developmental research design to: (a) formally document and validate Phase 1 features through research — proving alignment with best practices, measuring usability, gathering user feedback; and (b) engineer advanced capabilities Phase 1 lacked (HMAC score integrity, immutable audit logging, CV-OMR, offline PWA, enhanced AI scheduling with human-in-the-loop, multi-tenant architecture). This dual function — confirmatory validation + developmental advancement — is the study's methodological core. |
| 6 | **System solves boundary ambiguity through RBAC (not office conventions)** | The system enforces permissions through explicit role-based access control — defining what each role can access and perform — thereby replacing informal office-based conventions with systematic, auditable authorization. This is a core architectural contribution of SecureCAT. |
| 7 | **Staff multitasking burden** | Guidance counselors handle proctoring, scoring, attendance, AND counseling during admission periods |
| 8 | **Seasonal applicant volume** | Admission periods create concentrated demand spikes that exceed current staff capacity |
| 9 | **Campus infrastructure constraints** | Wi-Fi reliability, available computers, physical office space limitations |
| 10 | **Regional institutional context** | Compare with nearby SUCs or Ilocos Sur institutions to establish local precedent |

**Note:** All team members are at ISPSC Tagudin — this is your direct observation context. Cite regional studies, ISPSC publications, or comparable Ilocos/Region I institution studies.

---

### C1-05: Synthesis & Gap
**Goal:** 10-12 sentences synthesizing C1-02 through C1-04. NO author-by-author listing.

**The gap statement (the most important sentence in the manuscript):**

> "While individual digital solutions exist for applicant tracking, exam scheduling, automated scoring, and institutional data management, no existing Philippine SUC admission system integrates cryptographic score verification, AI-assisted applicant guidance, automated assessment ingestion, offline-resilient proctor operations, and multi-tenant data architecture into a unified platform that addresses the full admission lifecycle within a privacy-compliant, scalable framework."

**Supporting synthesis points:**
1. Global literature confirms that digital admission systems improve efficiency — but these are designed for well-resourced institutions, not connectivity-constrained provincial campuses
2. National policy mandates digitization — but implementation at the SUC level is fragmented
3. Local observation at ISPSC Tagudin confirms the gap is operational, not just technological
4. Existing systems (see Related Systems comparative analysis) address individual concerns but none integrate all layers
5. The absence of role-separated task management, real-time applicant tracking, and tamper-proof scoring creates measurable operational risk

---

### C1-06: Clinching Statement
**Goal:** 8-10 sentences with three required components.

| Component | Argument |
|-----------|----------|
| **1. How literature structured the study** | "The reviewed literature established a convergent pattern: secure assessment platforms require role-based access governance, automated scoring reduces transcription error, AI-assisted guidance deflects repetitive applicant inquiries, lightweight PWA delivery suits seasonal users, and multi-campus institutions require scalable multi-tenant data architecture. This pattern directly informed the design of SecureCAT as an integrated system addressing all dimensions simultaneously." |
| **2. Why this topic was selected** | "The researchers' direct observation at ISPSC Tagudin, including the development and deployment of a foundational digital admission system during the pre-capstone period (3rd year, 2nd semester) through institutional consultation with the Guidance Office, revealed that key operational bottlenecks and security concerns were left unresolved by basic digitization — confirming that the gap identified in literature exists in active operational practice. The capstone now formally validates this pre-existing initiative through descriptive-developmental research: documenting its utilization, measuring usability, and engineering the advanced capabilities it lacks." |
| **3. Why SecureCAT is the critical solution** | "SecureCAT addresses this integrated gap by providing a role-based, zero-trust-secured, AI-enhanced, offline-resilient, multi-tenant admission testing platform — engineered specifically for the operational realities of Philippine state university campuses." |
| **Optional: SDG tie-in** | SDG 4 (Quality Education) — by reducing administrative friction in the admission pipeline and streamlining the experience for students (the primary stakeholders), SecureCAT contributes to more accessible and equitable higher education intake processes. The digitization of campus processes directly reduces the queue burden that plagues public institutions, often described by the connotation "basta public ay mahaba pila." |

---

### C1-09: Objectives
**Standard three-objective structure:**

| Objective | Content | Notes |
|-----------|---------|-------|
| **General** | "To develop SecureCAT, a role-based college admission testing system for the Guidance and Registrar Offices at ISPSC Tagudin" | Must match title exactly |
| **Specific 1 — Identify** | Document existing admission processes, operational workflows, manual process dependencies, current utilization and operational limitations of the deployed foundational digital system, and institutional requirements at ISPSC Tagudin | Covers: current workflows, deployed system usage, architectural gaps, pain points, staff roles, applicant journey, infrastructure constraints |
| **Specific 2 — Develop** | Build the system with RBAC + zero-trust security, AI-assisted guidance, automated scoring capabilities, offline-resilient proctoring, and multi-tenant data architecture | Covers: all six pillars as features |
| **Specific 3 — Evaluate** | Assess the **usability** of the system using the System Usability Scale (SUS) | Use "usability" only — not "acceptability" — because SUS is a usability instrument |

---

### C1-11: Scope & Delimitations

**Scope paragraph must include:**
- User types: Applicants, Registrar Staff, Guidance Counselors, Proctors, Test Administrators, Super Administrators
- Modules: Application intake, exam scheduling, proctor/roster management, score recording (manual + CSV + OMR), AI companion, consultation summaries, result release, document generation
- Locale: ISPSC Tagudin, Tagudin, Ilocos Sur
- Timeframe: [development period]
- **PWA justification:** "Delivered as a PWA rather than a native mobile application because admission applicants are seasonal users for whom native app installation represents disproportionate overhead."
- **Multi-tenant scope:** "While the system utilizes a multi-tenant backend designed to support the entire ISPSC network, the onboarding of other campuses is beyond the scope of this study."

**Delimitations paragraph must include:**
- Cross-campus features NOT included (visualizations, applicant remapping, capacity balancing)
- External integrations excluded (CHED API, other university systems)
- Hardware dependencies (camera for OMR, network for sync)
- RA 10173 data privacy boundaries
- Manual processes that remain (physical document verification, face-to-face counseling)

---

### C1-12: Significance of the Study

| Beneficiary | Key argument |
|-------------|-------------|
| **Registrar Office Staff** | Centralized digital pipeline replacing manual paper-based review; automated application processing, bulk import, real-time status tracking, room/course management. Audit logging ensures RA 10173 compliance; role-based access prevents unauthorized data access. Digital handoff from Guidance Office eliminates fragmented communication and duplicated effort in result processing. |
| **Guidance Office Counselors** | Streamlined test administration: session roster, proctor assignment, digital attendance. Automated scoring via OMR CSV import (+ planned CV ingestion), consultation summaries, aptitude management. Enhanced AI Companion reduces repetitive applicant inquiries. Cryptographic score integrity (HMAC) and immutable audit logging provide tamper-evident records for accountability. |
| **Proctors and Test Administrators** | Real-time session management, QR-based applicant verification, digital attendance confirmation. Offline-resilient PWA allows scanning even when campus WiFi is unreliable — cached data syncs automatically on reconnection. Guidance staff serving as proctors benefit from consolidated tooling that reduces manual workload concentration. |
| **Applicants and Examinees** | Real-time status tracker from application through result release; admission slip PDF generation; token-based secure account activation; AI companion for instant guidance. Faster scoring and automated result generation reduce waiting. **Digitization of campus admission processes directly streamlines the experience for the primary stakeholders — the students — reducing the queue burden that plagues public institutions, often described by the connotation "basta public ay mahaba pila."** Web-based PWA access eliminates native app installation overhead for these seasonal users. |
| **ISPSC Administration** | Institutional-level visibility: audit logs, automated reporting, real-time dashboards. HMAC score integrity (planned) provides tamper-evident records. Multi-tenant isolation (planned) prepares for future campus expansion while maintaining data privacy (RA 10173). |
| **Future Researchers** | SecureCAT provides a reference implementation for RBAC admission systems in Philippine SUCs — covering zero-trust governance, CV OMR, offline-resilient PWA proctoring, AI Companion with RAG, and multi-tenant isolation. The descriptive-developmental methodology demonstrates how a pre-capstone institutional initiative can be formally validated and advanced through structured research. |

---

## Chapter 2 Arguments — Methodology

---

### C2-01: Research Design — Descriptive Developmental
**Goal:** Establish the overall research design and justify why Descriptive-Developmental is appropriate for this capstone. 8-12 sentences.

| # | Argument | Detail |
|---|----------|--------|
| 1 | **Descriptive phase** | The study describes the existing admission processes, workflows, manual dependencies, current utilization, and operational limitations of the deployed foundational digital system at ISPSC Tagudin. This grounds the development in real institutional needs rather than assumed requirements. |
| 2 | **Developmental phase** | Based on the descriptive findings, the study develops a software solution (SecureCAT) that addresses the identified gaps. The developmental phase is iterative — build, test, refine. |
| 3 | **Why this design fits** | BSIT capstone projects are applied research — the goal is to solve a real institutional problem, not to test theoretical hypotheses. Descriptive-Developmental is the standard design for tool/system development studies in Philippine HEI capstones. |
| 4 | **Descriptive instruments** | Observation, interviews, and document analysis of current ISPSC admission workflows feed into the developmental requirements. |
| 5 | **Developmental output** | The output is a functional, deployable system — not just a prototype or proof-of-concept. SUS evaluation validates the developmental output. |

---

### C2-02: Software Model — RAD or AIDLC
**Goal:** Justify the software development methodology. 8-10 sentences.

| # | Argument | Detail |
|---|----------|--------|
| 1 | **RAD (Rapid Application Development)** | If using RAD: emphasize the iterative prototyping cycle, time-boxed development, and user feedback integration. RAD suits projects with clear functional scope and a small, co-located development team. |
| 2 | **AIDLC (Agile Iterative Development Life Cycle)** | If using AIDLC: emphasize sprint-based iterations, continuous stakeholder feedback, and adaptive planning. AIDLC suits projects where requirements may evolve during development. |
| 3 | **Justification for the chosen model** | The capstone has a fixed timeline, a small team (3 members), and well-defined functional requirements derived from the descriptive phase. The chosen model must accommodate rapid iteration with limited resources. |
| 4 | **Phases mapped to project** | Map the chosen model's phases to actual project activities: requirements gathering (descriptive phase), design, implementation (developmental phase), testing, and deployment. |
| 5 | **Risk mitigation** | The iterative nature of RAD/AIDLC allows early detection of technical risks (OMR accuracy, offline sync, AI response quality) before they compound. |

---

### C2-03: Project Plan — Gantt Chart
**Goal:** Present the project timeline as a Gantt chart. Brief explanatory paragraph.

| # | Argument | Detail |
|---|----------|--------|
| 1 | **Phase breakdown** | The Gantt chart must show major phases: requirements gathering, system design, development (by module), testing, SUS evaluation, documentation, and final presentation. |
| 2 | **Milestone markers** | Key milestones should be clearly marked — design review, alpha build, beta build, SUS administration, manuscript submission. |
| 3 | **Parallel tracks** | Development tasks may overlap (e.g., frontend and backend modules developed concurrently by different team members). Show task assignments alongside the timeline. |
| 4 | **Timeframe realism** | The plan must reflect the actual capstone semester timeline, accounting for academic breaks, exam periods, and coordination overhead. |

---

### C2-04: Project Assignment — Table 1
**Goal:** Present a table showing task-to-team-member assignments. Brief explanatory paragraph.

| # | Argument | Detail |
|---|----------|--------|
| 1 | **Task decomposition** | Break down all capstone tasks (research, development, testing, documentation) and assign each to a specific team member. |
| 2 | **Balance of workload** | Demonstrate equitable distribution — each member handles research writing AND development tasks. No member is solely a "writer" or "developer." |
| 3 | **Accountability** | Each task has a clear owner — this prevents overlap and gaps in responsibility. |
| 4 | **Format** | Use the BSIT Capstone Template "Table 1" format: Task | Assigned To | Role/Responsibility. |

---

### C2-05: Population and Locale of the Study
**Goal:** Define who will participate in the evaluation and where the study takes place. 8-10 sentences.

| # | Argument | Detail |
|---|----------|--------|
| 1 | **Population** | Define the target respondents for SUS evaluation — typically ISPSC staff (guidance counselors, registrar staff, proctors) and a sample of applicants/examinees who interact with the system. |
| 2 | **Locale** | The study is conducted at ISPSC — Main Campus, Tagudin, Ilocos Sur. Specify the offices involved (Guidance Office, Registrar Office). |
| 3 | **Sampling method** | Justify the sampling approach — purposive sampling is appropriate for usability evaluation since respondents must have direct experience with the system. |
| 4 | **Sample size** | State the intended number of respondents. For SUS, a minimum of 5-10 respondents provides usable scores, though larger samples improve reliability. |
| 5 | **Inclusion criteria** | Respondents must have actually used SecureCAT features relevant to their role (e.g., proctor must have used the proctoring module, applicant must have completed the application flow). |

---

### C2-06: Research Instruments — SUS
**Goal:** Describe the System Usability Scale as the evaluation instrument. 8-12 sentences.

| # | Argument | Detail |
|---|----------|--------|
| 1 | **Instrument description** | The System Usability Scale (SUS) is a 10-item Likert-type questionnaire that produces a composite usability score from 0 to 100. It is the most widely used usability questionnaire in industry and academia. |
| 2 | **Why SUS** | SUS is technology-agnostic, quick to administer, and produces a single quantifiable score — making it ideal for capstone-level evaluation where statistical rigor must be practical. |
| 3 | **Validity and reliability** | SUS has established validity and reliability across thousands of studies. Cite Brooke (1996) as the original source and subsequent meta-analyses confirming its psychometric properties. |
| 4 | **Administration protocol** | Describe how SUS will be administered: after respondents complete a structured interaction with SecureCAT, they fill out the 10-item questionnaire. The system should be evaluated in context — not just demonstrated. |
| 5 | **Scoring interpretation** | SUS scores are interpreted against established benchmarks: below 50 = poor, 50-70 = marginal, 70-85 = good, above 85 = excellent. A score above 68 is considered above average. |
| 6 | **Adaptation** | If any SUS items are adapted for the admission testing context, document the modifications and justify them. Otherwise, use the standard wording. |

---

### C2-07: Data Analysis
**Goal:** Describe how SUS data will be analyzed and interpreted. 6-8 sentences.

| # | Argument | Detail |
|---|----------|--------|
| 1 | **SUS score computation** | Describe the standard SUS scoring formula: sum of odd items (after subtracting 1 from each) plus sum of even items (after subtracting each from 5), multiplied by 2.5. |
| 2 | **Descriptive statistics** | Report the mean, median, and standard deviation of SUS scores per respondent group (staff vs. applicants, if applicable). |
| 3 | **Interpretive framework** | Map computed SUS scores to the adjective rating scale (Best Imaginable → Worst Imaginable) and the acceptability ranges (Acceptable / Marginal / Not Acceptable). |
| 4 | **Qualitative support** | If open-ended feedback is collected alongside SUS, summarize qualitative themes to contextualize the numeric scores. |
| 5 | **Limitations of analysis** | Acknowledge that with small sample sizes, SUS scores are indicative rather than statistically generalizable. This is acceptable for capstone-level evaluation. |

---

## Cross-Cutting Arguments

### CC-01 through CC-04 (References, Formatting QA, Narrative Consistency, Citation Cross-Check)
These are mechanical/quality tasks — no research arguments needed, but the **narrative consistency review (CC-03)** should verify that:
1. Every section supports the integration thesis
2. Existing features are present alongside advanced features (not separated)
3. The proctor role / status tracker / scheduling / bulk ops appear in at least C1-01, C1-04, and C1-12
4. Multi-tenancy appears in C1-01, C1-11, and the methodology where relevant
5. The PWA-vs-native argument appears in C1-01 and C1-11
