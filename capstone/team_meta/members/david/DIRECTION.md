# Member Task Direction — David
## Team Leader / Product Owner / Lead Developer

> **Role:** Technical architecture, literature reviews, framework tasks, narrative consistency
> **Total Claimed Tasks:** 11 tasks
> **Estimated Effort:** 33-46 hours (out of ~55h available)
> **Focus:** Fewer but harder tasks — technical writing, synthesis, framework design

---

## Your Tasks at a Glance

### Chapter 1
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C1-05 | Background P5 — Synthesis and Gap Identification | 3-4h | Jun 6 | C1-02, C1-03, C1-04 |
| C1-06 | Background P6 — Clinching Statement | 2-3h | Jun 7 | C1-02 through C1-05 |
| C1-07 | Conceptual Framework — IPO Diagram | 2-3h | Jun 3 | None |
| C1-08 | Conceptual Framework — Narrative | 2-3h | Jun 4 | C1-07 |
| C1-09 | Objectives of the Study | 2-3h | Jun 3 | None |
| C1-11 | Scope and Delimitations | 2-3h | Jun 4 | None |

### Chapter 2
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C2-01 | Lit Review — RBAC and Zero-Trust Security | 4-6h | Jun 5 | None |
| C2-03 | Lit Review — AI Assistants and RAG in Education | 4-6h | Jun 5 | None |
| C2-04 | Lit Review — Offline-Resilient and PWA Systems | 4-6h | Jun 6 | None |
| C2-05 | Lit Review — DPA and Multi-Tenancy | 3-5h | Jun 5 | None |
| C2-07 | Technical Framework | 3-4h | Jun 6 | None |
| C2-08 | Conceptual Framework Prose (Chapter 2) | 2-3h | Jun 7 | C1-07, C1-08 |

### Cross-Cutting
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| CC-03 | Narrative Consistency Review and Integration | 4-6h | Jun 9 | All writing tasks |

---

## Detailed Task Directions

### Week 1 (June 1-3): Foundation Tasks

#### C1-07: Conceptual Framework — IPO Diagram (June 3)
**Priority:** HIGH — Blocks C1-08 and C2-08

**What to Do:**

1. **List all system Inputs (numbered list, NOT bullets):**
   Use ONLY data and configurations the system receives per TEAM_META_GUIDE C1-07 and SYSTEM_FEATURES.md:
   1. Applicant data
   2. Exam configurations
   3. OMR images/scans
   4. Role credentials
   5. QR scans
   6. Natural language queries

2. **Define the Process box:**
   Text: "SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin" (full system title)

3. **List all system Outputs (numbered list, NOT bullets):**
   Use ONLY what the system produces per TEAM_META_GUIDE C1-07 and SYSTEM_FEATURES.md:
   1. Status tracking displays
   2. Exam schedules
   3. Score reports
   4. Audit logs
   5. Result sheets/PDFs
   6. Consultation summaries
   7. Copilot responses
   8. Offline-cached records
   9. Statistical reports

4. **Format the diagram:**
   Create the three-box diagram (Input → Process → Output). Use simple formatting with boxes, arrows, and numbered lists inside. Ensure readable on a standard page. Figure caption placed BELOW the figure, bold.

**Critical Restrictions:**
- Inputs must be THINGS the system receives (data, configs, parameters). No process verbs.
- Outputs must be THINGS the system produces. No process verbs or activities.
- No bullet points anywhere. Numbered lists only inside IPO boxes.

**Reference:** `SYSTEM_FEATURES.md`, `drafts/Existing_and_Planned_Features.md`

**Deliverable:** Clean IPO diagram saved as `C1-07_David_IPO_Diagram.md` with Input (6 numbered items) → Process (full title) → Output (9 numbered items)

---

#### C1-09: Objectives of the Study (June 3)
**Priority:** HIGH — Blocks C1-10 (Jaypee's task)

**What to Do:**

1. **General Objective (one paragraph):**
   Name SecureCAT by its full title. State the overarching purpose: develop a role-based college admission testing system for ISPSC Tagudin Guidance and Registrar offices.

2. **Specific Objectives (numbered list, NOT bullets):**
   Follow the standard three-objective structure:
   1. **Identify** — Document the existing manual admission testing processes, operational gaps, and requirements at ISPSC Tagudin
   2. **Develop** — SecureCAT with role-based access control, offline-first PWA architecture, AI companion features, and cryptographic data integrity for coordinated admission testing
   3. **Evaluate** — the usability of SecureCAT using the System Usability Scale (SUS)

3. **CRITICAL CHECK:**
   - Objective 3 must say **"evaluate the usability"** ONLY
   - Do NOT mix terms: "usability and acceptability" is incorrect
   - SUS measures usability — if you want acceptability, you need a separate instrument
   - General objective must name system by full title

**Reference:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 3

**Deliverable:** `C1-09_David_Objectives.md` with general objective paragraph + 3-item numbered specific objectives list

---

### Week 1-2 (June 3-5): Chapter 2 Literature Reviews

#### C2-01: Lit Review — RBAC and Zero-Trust Security (June 5)
**Priority:** HIGH — Technical foundation

**What to Do:**

1. **Research (90 min):**
   - Search Google Scholar: "role-based access control education systems", "zero-trust security educational software", "HMAC authentication web applications", "multi-tenant security architecture"
   - Filter: **2022-2026 only**, peer-reviewed journals
   - Target: 5-7 sources covering RBAC principles, zero-trust models, HMAC mechanisms, multi-tenant security, assessment platform security

2. **Extract Findings (60 min):**
   - For each source, extract: RBAC implementation patterns, zero-trust security models, cryptographic data integrity mechanisms, multi-tenant isolation strategies
   - Group findings by theme (not author-by-author)

3. **Write Review (90-120 min):**
   - **Thematic synthesis** (1-2 paragraphs):
     - RBAC principles in educational and administrative systems
     - Zero-trust security and cryptographic authentication (HMAC)
     - Multi-tenant security architectures
     - How SecureCAT's role-based design and HMAC integrity model aligns with this literature
   - **Minimum 5 in-text citations (Author, Year)**, all 2022-2026
   - **DO NOT** summarize author-by-author — synthesize by theme
   - FORBIDDEN pattern: "Author A says X. Author B says Y."

4. **Compile References (30 min):**
   - Create draft APA 7 references for all sources
   - Save for CC-01 (Christine will compile final list)

**Reference:** `guides/GUIDE-3-CHAPTER2-CONTENT.md` (literature review pattern)

**Deliverable:** `C2-01_David_RBAC_Review.md` with 1-2 paragraphs + draft references, minimum 5 APA citations (all 2022-2026)

---

#### C2-03: Lit Review — AI Assistants and RAG in Education (June 5)
**Priority:** HIGH — AI Companion component

**What to Do:**

1. **Research (90 min):**
   - Search: "AI companions education", "RAG knowledge bases", "LLM scheduling assistants", "AI educational chatbots", "natural language interfaces database querying"
   - Filter: **2022-2026 only**, peer-reviewed
   - Target: 5-7 sources covering AI-powered chatbots in educational administration, RAG architectures, vector embeddings for knowledge retrieval, natural language interfaces for database querying

2. **Extract Findings (60 min):**
   - For each source, extract: AI assistant use cases in education, RAG architectures and knowledge bases, embedding strategies (vector databases, semantic search), context management in LLM applications

3. **Write Review (90-120 min):**
   - **Thematic synthesis** (1-2 paragraphs):
     - AI assistants in educational contexts
     - RAG architectures and knowledge bases
     - LLM-powered scheduling and information systems
     - How SecureCAT's RAG Copilot (vector embeddings + natural language querying) fits this literature
   - **Minimum 5 in-text citations (Author, Year)**, all 2022-2026
   - Synthesized writing only — FORBIDDEN pattern: "Author A says X. Author B says Y."

4. **Compile References (30 min):**
   - Create draft APA 7 references for all sources

**Reference:** `SYSTEM_FEATURES.md` for AI Companion and RAG Copilot details

**Deliverable:** `C2-03_David_AI_RAG_Review.md` with 1-2 paragraphs + draft references, minimum 5 APA citations (all 2022-2026)

---

#### C2-04: Lit Review — Offline-Resilient and PWA Systems (June 6)
**Priority:** HIGH — PWA architecture

**What to Do:**

1. **Research (90 min):**
   - Search: "progressive web apps education", "offline-first web applications", "PWA service worker strategies", "IndexedDB local caching", "background sync mechanisms"
   - Filter: **2022-2026 only**, peer-reviewed
   - Target: 5-7 sources covering PWA adoption patterns, offline-first architectural patterns, service worker design strategies, IndexedDB for local caching, background sync mechanisms in critical operational environments

2. **Extract Findings (60 min):**
   - For each source, extract: PWA adoption patterns and benefits, offline-first architectures and service workers, sync strategies for reconnection, relevance to low-connectivity contexts (Philippines/ISPSC)

3. **Write Review (90-120 min):**
   - **Thematic synthesis** (1-2 paragraphs):
     - PWA benefits and adoption trends
     - Offline-first architectures and service workers
     - PWAs in low-connectivity contexts (relevant to Philippine higher education)
     - How SecureCAT's PWA architecture enables offline-resilient proctoring
   - **Minimum 5 in-text citations (Author, Year)**, all 2022-2026

4. **Compile References (30 min):**
   - Create draft APA 7 references for all sources

**Reference:** `SYSTEM_FEATURES.md` for PWA and offline-resilient portal details

**Deliverable:** `C2-04_David_PWA_Review.md` with 1-2 paragraphs + draft references, minimum 5 APA citations (all 2022-2026)

---

#### C2-05: Lit Review — Philippine Data Privacy Act and Multi-Tenancy (June 5)
**Priority:** MEDIUM — Compliance and architecture

**What to Do:**

1. **Research (60 min):**
   - Search: "Data Privacy Act education Philippines", "RA 10173 compliance educational software", "multi-tenant database architecture", "tenant isolation SaaS", "data isolation strategies SUC"
   - Filter: **2022-2026 only**, peer-reviewed
   - Target: 5-7 sources covering Philippine Data Privacy Act (RA 10173) compliance in educational software, multi-tenant database architecture patterns, data isolation strategies for SUC systems

2. **Extract Findings (45 min):**
   - For each source, extract: RA 10173 requirements for educational data, multi-tenant database patterns, tenant isolation strategies (row-level security, separate schemas), compliance frameworks

3. **Write Review (60-90 min):**
   - **Thematic synthesis** (1-2 paragraphs):
     - Data privacy compliance in Philippine education (RA 10173)
     - Multi-tenant architectural patterns for tenant isolation
     - How SecureCAT's multi-tenant architecture complies with RA 10173
   - **Minimum 5 in-text citations (Author, Year)**, all 2022-2026

4. **Compile References (30 min):**
   - Create draft APA 7 references for all sources

**Reference:** `SYSTEM_FEATURES.md` for multi-tenant architecture details

**Deliverable:** `C2-05_David_DPA_MultiTenancy_Review.md` with 1-2 paragraphs + draft references, minimum 5 APA citations (all 2022-2026)

---

#### C2-07: Technical Framework (June 6)
**Priority:** HIGH — System architecture documentation

**What to Do:**

1. **Define Technical Stack (45-60 min):**
   Document the full technology stack per TEAM_META_GUIDE C2-07. You MUST include ALL of the following:
   - **Core stack:** Laravel 12 / Inertia v2 / Svelte 5 / Tailwind 4
   - **Security model:** HMAC security model (SHA-256 HMAC signature locks for score integrity, tamper detection, immutable audit logs)
   - **AI pipeline:** Vector embeddings for RAG (MixedBread embeddings, semantic search, RAG pattern for AI Copilot)
   - **Offline architecture:** PWA service worker architecture (service workers, IndexedDB caching, background sync)
   - **Database isolation:** Multi-tenant database isolation concepts (tenant data segregation, row-level security or separate schemas)
   - **Document generation:** DOMPDF/PHPWord document generation pipeline (DOMPDF for PDF rendering, PHPWord/FPDI for DOCX generation)

2. **Describe System Architecture (60-90 min):**
   Write 1-2 paragraphs explaining architecture in academic language covering:
   - **Frontend:** Svelte 5 components, Tailwind 4 styling, shadcn-svelte components, Inertia v2 for seamless navigation
   - **Backend:** Laravel 12 controllers, policies for role-based access, middleware for authentication, form request validation
   - **Security:** HMAC signature locks (SHA-256 using server-side secret key + Applicant UUID + Test Score + Proctor UUID), immutable write-only audit logs, Laravel Policy route gating
   - **Database:** Multi-tenant schema, tenant isolation strategies, Eloquent ORM
   - **PWA:** Service worker for offline-first proctoring, IndexedDB caching, background sync for reconnection
   - **AI:** MixedBread vector embeddings for semantic search, RAG pattern for AI Copilot knowledge base, natural language querying
   - **Document generation:** DOMPDF for PDF rendering (admission slips, result sheets), PHPWord/FPDI for DOCX generation (bulk result sheets)

3. **Optional: Create Architecture Diagram:**
   System architecture figure showing layers (Frontend → Backend → Database → External Services). Figure caption placed below the figure, bold.

**Reference:** `SYSTEM_FEATURES.md`, `AGENTS.md`, `drafts/Existing_and_Planned_Features.md`

**Deliverable:** `C2-07_David_Technical_Framework.md` with 1-2 paragraphs covering ALL required components (Laravel 12 / Inertia v2 / Svelte 5 / Tailwind 4 stack, HMAC security model, vector embeddings for RAG, PWA service worker architecture, multi-tenant database isolation concepts, DOMPDF/PHPWord document generation pipeline), plus optional architecture diagram

---

### Week 1-2 (June 3-7): Chapter 1 Framework Tasks

#### C1-08: Conceptual Framework — Narrative (June 4)
**Priority:** HIGH — Depends on C1-07

**What to Do:**

1. **Write Paragraph 1 — Explain Inputs (45-60 min):**
   - Read your C1-07 IPO diagram
   - For each input component (all 6: applicant data, exam configurations, OMR images/scans, role credentials, QR scans, natural language queries), explain:
     - What it is
     - Why it is necessary for the system
     - How it connects to role-based design
   - Example: "Role credentials are necessary input to ensure that only authorized Guidance staff can schedule tests and Registrar staff can view scores, enforcing role-based access control through Laravel policies and middleware."

2. **Write Paragraph 2 — Explain Process → Outputs (45-60 min):**
   - Explain how the system processes inputs and produces outputs through:
     - Role-based access control
     - Automated scoring
     - Offline-resilient proctoring
     - AI-assisted operations
     - Cryptographic verification (HMAC)
   - Make the mechanical connection explicit — not just "inputs go in and outputs come out" but how the transformation happens inside the system
   - Describe each output (all 9: status tracking displays, exam schedules, score reports, audit logs, result sheets/PDFs, consultation summaries, copilot responses, offline-cached records, statistical reports) and its purpose
   - Present both existing and planned features as one unified process

3. **Review Against Diagram (15-30 min):**
   - Verify narrative matches IPO diagram exactly
   - Ensure no new inputs/outputs introduced in text that are not in the diagram

**CRITICAL:** You must write exactly **2 paragraphs**. Not 1, not 3 — exactly 2.

**Reference:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 2

**Deliverable:** `C1-08_David_Framework_Narrative.md` with exactly 2 paragraphs

---

#### C1-11: Scope and Delimitations (June 4)
**Priority:** HIGH — System boundaries

**What to Do:**

1. **Write Scope Paragraph (60-90 min):**
   - **Paragraph form ONLY** (no bullets, no numbered lists per GUIDE-1)
   - Must cover BOTH existing system modules AND planned research modules:
     - **Existing system modules:** application intake, scheduling, roster, grading, OMR CSV import, consultation summaries, result release, document generation, audit logs, notifications, AI companion, AI scheduling assistant
     - **Planned research modules:** HMAC integrity, CV-based OMR, offline PWA, RAG copilot, auto-scheduling agent, multi-tenant architecture
   - Include: authorized user types (Applicants, Registrar Staff, Guidance/Proctors, Test Administrators, Super Administrators), locale (ISPSC Tagudin, Ilocos Sur, Philippines), timeframe of development and deployment, principal variables (test data, student records, role assignments, test configurations)
   - Justification for why these boundaries were chosen
   - Include explicit delimitations that allow advanced features to appear as research contributions rather than out-of-scope expansion

2. **Write Delimitations Paragraph (60-90 min):**
   - **Paragraph form ONLY**
   - Include:
     - What system does NOT do: no LMS integration, no payment processing, no test content generation (tests are pre-configured)
     - Hardware/network dependencies: internet connection for PWA sync, modern web browser, device requirements (desktop/tablet)
     - Single-site constraint: ISPSC Tagudin only (no multi-campus deployment in current scope)
     - Manual processes remaining: test content creation, policy decisions, student eligibility determination
     - Data privacy: complies with RA 10173 (Data Privacy Act of 2012); system stores only necessary student data; access controlled via role-based permissions; multi-tenant architecture prepares for future campus expansion while maintaining data isolation
   - Write delimitations that frame planned features as research contributions, not as scope violations

**Reference:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 4, `SYSTEM_FEATURES.md`, `drafts/Existing_and_Planned_Features.md`

**Deliverable:** `C1-11_David_Scope_Delimitations.md` with 2 paragraphs (scope + delimitations), paragraph-form only, covering both existing and planned features

---

#### C1-05: Background P5 — Synthesis and Gap Identification (June 6)
**Priority:** HIGH — Depends on Jaypee's C1-02, C1-03, Christine's C1-04

**What to Do:**

1. **Review All Background Paragraphs (30 min):**
   - Read Jaypee's P2 (global context) and P3 (national context)
   - Read Christine's P4 (local context)
   - Identify recurring themes: automation trends, security needs, coordination gaps, connectivity challenges
   - Note contrasts: global tech exists, but local implementation gaps persist

2. **Synthesize Findings (60-90 min):**
   - **DO NOT summarize author-by-author** — FORBIDDEN to list authors one by one
   - Group ideas thematically:
     - Global: Admission testing digitization trends + security architectures
     - National: Philippine mandates + current practices in SUCs
     - Local: ISPSC manual processes + operational constraints
   - Identify the **research gap**: what no existing study or system has adequately addressed
   - Key gap: Role-based, multi-office (Guidance + Registrar) admission testing coordination with offline-first PWA architecture, AI-assisted scheduling, cryptographic data integrity, and multi-tenant compliance
   - Use synthesis patterns: "While [finding A], [contrasting finding B]..."

3. **Write Synthesis Paragraph (60-90 min):**
   - **10-12 sentences** synthesizing (NOT summarizing)
   - Structure: Grouped ideas → Revealed gap → What SecureCAT does differently
   - Must explicitly name the research gap in 2-3 sentences
   - Example: "While global studies demonstrate the efficiency of automated admission testing (Author A, 2024; Author B, 2023) and national mandates emphasize digital transformation in Philippine SUCs (Author C, 2025), local implementations at institutions like ISPSC Tagudin remain manual and uncoordinated between offices. This gap — the lack of role-based, multi-office coordination with offline-first capability and cryptographic data integrity — is what SecureCAT addresses through its PWA architecture, AI-assisted workflows, and HMAC-secured score verification."

**Reference:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 1, Paragraph 5

**Deliverable:** `C1-05_David_Synthesis_Gap.md` with 1 synthesis paragraph of **10-12 sentences** explicitly naming the research gap

---

#### C1-06: Background P6 — Clinching Statement (June 7)
**Priority:** HIGH — Depends on C1-02 through C1-05

**What to Do:**

1. **Component 1: How Literature Structured the Study (30 min):**
   - Explain how the reviewed literature (P2-P5) assisted in structuring the present study
   - Note which findings influenced system features and research design decisions
   - Example: studies on PWA for low-connectivity contexts informed the offline-first architecture; RBAC literature guided role-based design; AI/RAG research shaped the Copilot feature

2. **Component 2: Why This Topic Was Selected (30 min):**
   - State why you selected this research topic
   - Must include **direct observation of the problem at ISPSC Tagudin**
   - Describe specific manual processes witnessed firsthand (paper-based test routing between Guidance and Registrar offices, lack of audit trails, vulnerability to scoring errors, fragmented coordination during peak admission periods)

3. **Component 3: Why SecureCAT Is the Critical Solution (30 min):**
   - Conclude by highlighting why the proposed system is the critical solution to the identified gap
   - Connect the system's capabilities (role-based coordination, offline-first testing, AI-assisted scheduling, HMAC-secured data integrity) to the problems documented in P1
   - Frame SecureCAT as the necessary intervention, not just a convenience

4. **Write Clinching Paragraph (30-60 min):**
   - **8-10 sentences** incorporating all three components above
   - The three components must be explicitly present and identifiable in the text
   - **Optional but recommended:** Connect to SDG 4 (Quality Education) or SDG 16 (Peace, Justice and Strong Institutions)

**Reference:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 1, Paragraph 6

**Deliverable:** `C1-06_David_Clinching.md` with 1 clinching paragraph of **8-10 sentences** containing 3 explicit components: (1) how literature structured the study, (2) why topic was selected with direct observation at ISPSC, (3) why SecureCAT is the critical solution. Optional SDG 4 or SDG 16 connection.

---

#### C2-08: Conceptual Framework Prose — Chapter 2 Expansion (June 7)
**Priority:** MEDIUM — Expansion of Chapter 1 framework

**What to Do:**

1. **Review Chapter 1 Conceptual Framework (30 min):**
   - Read your C1-07 (IPO diagram) and C1-08 (narrative)
   - Ensure Chapter 2 prose is consistent but expanded

2. **Write Expanded Framework Narrative (60-90 min):**
   - **2-3 paragraphs** explaining framework in more depth
   - Paragraph 1: Reiterate IPO framework and how it guides system development
   - Paragraph 2: Explain how framework connects to research design (objectives → methodology)
   - Paragraph 3 (optional): Describe how framework ensures SecureCAT meets ISPSC Tagudin's needs
   - Connect inputs through processing to outputs with both existing and planned features
   - Describe the system workflow stages in detail

3. **Align with Methodology (15-30 min):**
   - Ensure framework aligns with Chapter 2 methodology sections (research design, software model)
   - Verify IPO items map to real processing stages

**Reference:** `guides/GUIDE-3-CHAPTER2-CONTENT.md` (Chapter 2 structure)

**Deliverable:** `C2-08_David_Framework_Prose_Ch2.md` with 2-3 paragraphs expanding the IPO into narrative for Chapter 2, connecting inputs through processing to outputs with both existing and planned features

---

### Week 2 (June 9): Cross-Cutting Task

#### CC-03: Narrative Consistency Review and Integration (June 9)
**Priority:** CRITICAL — Final review before submission

**What to Do:**

1. **Read All Chapters End-to-End (60-90 min):**
   - Chapter 1: Background (P1-P6) → Framework (IPO + Narrative) → Objectives → Research Questions → Scope → Significance
   - Chapter 2: Research Design → Software Model → Project Plan → Assignments → Population → Instruments → Analysis → Literature Reviews → Related Systems → Technical Framework → Conceptual Framework Prose
   - Read as a continuous narrative, not isolated sections

2. **Check Consistency Across Sections (90-120 min):**
   - **Background problem alignment:**
     - Background problem matches objectives
     - Objectives align with research questions
   - **Framework alignment:**
     - Framework aligns with methodology
     - IPO inputs/outputs match technical framework
   - **Scope alignment:**
     - Scope matches features described in literature reviews
     - Delimitations match what system does and does not do
     - Both existing and planned features presented coherently
   - **Citation consistency:**
     - All (Author, Year) citations match reference entries
     - No orphaned citations or references
     - All sources within 2022-2026 range
   - **Terminology consistency:**
     - "SecureCAT" used consistently throughout
     - System title consistent across all mentions

3. **Fix Inconsistencies (60-90 min):**
   - Edit text to ensure narrative flow
   - Unify voice and tone across sections written by different people
   - Ensure transitions between sections are smooth
   - Align terminology throughout
   - Ensure logical progression from problem → solution → evaluation
   - Fix citation/reference mismatches
   - Verify existing features and planned features are presented as one coherent system story

4. **Verify Paragraph Lengths (30 min):**
   - Check all paragraph sentence counts against TEAM_META_GUIDE specifications:
     - C1-01: 8-12 sentences
     - C1-02: 12-15 sentences
     - C1-03: 12-15 sentences
     - C1-04: 12-15 sentences
     - C1-05: 10-12 sentences
     - C1-06: 8-10 sentences with 3 explicit components
     - C1-08: exactly 2 paragraphs

**Reference:** All Chapter 1 and Chapter 2 guides

**Deliverable:** Final integrated manuscript with unified voice, consistent narrative, smooth transitions, correct paragraph lengths, and verified citations

---

## Your Week-by-Week Schedule

### Week 1 (June 1-3): Foundation
- [ ] **June 1-2:** C1-07 (IPO Diagram) — 6 inputs, 9 outputs, numbered lists only
- [ ] **June 1-2:** C1-09 (Objectives) — general objective + 3 numbered specific objectives
- [ ] **June 2-3:** C2-01 (RBAC Review) starts research

### Week 1-2 (June 3-5): Literature Reviews + Framework
- [ ] **June 3-4:** C1-08 (Framework Narrative) — exactly 2 paragraphs
- [ ] **June 3-4:** C1-11 (Scope and Delimitations) — 2 paragraphs covering existing AND planned features
- [ ] **June 3-5:** C2-01 (RBAC Review) — minimum 5 citations, all 2022-2026
- [ ] **June 3-5:** C2-03 (AI/RAG Review) — minimum 5 citations, all 2022-2026
- [ ] **June 3-5:** C2-05 (DPA/Multi-Tenancy Review) — minimum 5 citations, all 2022-2026
- [ ] **June 5:** C2-01, C2-03, C2-05 due

### Week 2 (June 6-7): Synthesis and Framework
- [ ] **June 6:** C2-04 (PWA Review) — minimum 5 citations, all 2022-2026
- [ ] **June 6:** C2-07 (Technical Framework) — MUST include DOMPDF/PHPWord pipeline
- [ ] **June 6:** C1-05 (Synthesis and Gap) — 10-12 sentences, wait for Jaypee/Christine background
- [ ] **June 7:** C1-06 (Clinching Statement) — 8-10 sentences, 3 explicit components
- [ ] **June 7:** C2-08 (Framework Prose Ch2) — 2-3 paragraphs

### Week 2 (June 9): Final Review
- [ ] **June 9:** CC-03 (Narrative Consistency Review) — verify all paragraph lengths, unified voice

---

## Communication Responsibilities

As Team Leader, you are responsible for:

1. **Daily Check-ins:**
   - Post brief progress updates in group Discord
   - Ask team members for their progress
   - Flag blockers immediately

2. **Task Coordination:**
   - Remind Jaypee of his June 4-5 deadlines (C1-01, C1-02, C1-03, C1-10)
   - Remind Christine of her June 5 deadline (C1-04, C2-02, C1-12)
   - Ensure all draft references are submitted to Christine by June 7

3. **Quality Gates:**
   - Review Jaypee's background paragraphs (C1-02, C1-03) before June 6 (needed for your C1-05 synthesis)
   - Review Christine's local context (C1-04) before June 6 (needed for your C1-05 synthesis)
   - Coordinate with Christine on CC-01 (References) by June 8

4. **Final Integration:**
   - Collect all drafts by June 8
   - Run CC-03 (Narrative Consistency Review) on June 9
   - Ensure final manuscript is ready by June 10

---

## Formatting Rules (from GUIDE-1 — Apply to ALL Your Drafts)

| Rule | Value |
|------|-------|
| Left margin | **1.5 inches** |
| Right margin | **1.0 inch** |
| Top margin | **1.0 inch** |
| Bottom margin | **1.0 inch** |
| Font | Times New Roman, 12pt |
| Line spacing | Double throughout |
| Paragraph indent | **Exactly 5 spaces** |
| Alignment | Justified |
| Bullet points | **NONE anywhere** in manuscript |
| Bold body text | **NOT allowed** — bold ONLY for headings, subheadings, figure captions, table captions |
| Table captions | Left-aligned, **above** the table, bold |
| Figure captions | **Below** the figure, bold |
| Table borders | 1pt line width |
| Page numbers | Every page except first page of each chapter |
| Space between paragraphs | 0pt (no extra spacing) |

### Format Pre-Flight Checklist (Apply to EVERY draft before submission)

- [ ] Font: Times New Roman 12pt
- [ ] Line spacing: Double
- [ ] Paragraph indent: 5 spaces
- [ ] Alignment: Justified
- [ ] No bold in body text (only headings/captions)
- [ ] No bullet points (numbered lists only where explicitly allowed: IPO boxes, specific objectives)
- [ ] No extra space between paragraphs
- [ ] Left margin: 1.5 inches | Right/Top/Bottom: 1.0 inches

---

## Paragraph Length Quick Reference

| Task | Paragraph | Target Sentences | Citations Required |
|------|-----------|------------------|--------------------|
| C1-05 | P5 — Synthesis and Gap | **10-12 sentences** | Draw from P2-P4 |
| C1-06 | P6 — Clinching | **8-10 sentences**, 3 explicit components | None required |
| C1-08 | Framework Narrative | **Exactly 2 paragraphs** | None |

Note: You do not write C1-01 through C1-04 (those are Jaypee and Christine), but during CC-03 you must verify their sentence counts:
- C1-01: 8-12 sentences
- C1-02: 12-15 sentences
- C1-03: 12-15 sentences
- C1-04: 12-15 sentences

---

## IPO Quick Reference (for C1-07)

**Inputs (6 items, numbered):**
1. Applicant data
2. Exam configurations
3. OMR images/scans
4. Role credentials
5. QR scans
6. Natural language queries

**Process:**
SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin

**Outputs (9 items, numbered):**
1. Status tracking displays
2. Exam schedules
3. Score reports
4. Audit logs
5. Result sheets/PDFs
6. Consultation summaries
7. Copilot responses
8. Offline-cached records
9. Statistical reports

---

## Technical Framework Quick Reference (for C2-07)

You MUST cover ALL of these in your technical framework:

1. **Laravel 12 / Inertia v2 / Svelte 5 / Tailwind 4** — core stack
2. **HMAC security model** — SHA-256 signature locks for score integrity
3. **Vector embeddings for RAG** — MixedBread embeddings for semantic search
4. **PWA service worker architecture** — offline-first, IndexedDB, background sync
5. **Multi-tenant database isolation concepts** — tenant data segregation
6. **DOMPDF/PHPWord document generation pipeline** — PDF rendering and DOCX generation

---

## Scope Quick Reference (for C1-11)

**Existing system modules to mention:**
application intake, scheduling, roster, grading, OMR CSV import, consultation summaries, result release, document generation, audit logs, notifications, AI companion, AI scheduling assistant

**Planned research modules to mention:**
HMAC integrity, CV-based OMR, offline PWA, RAG copilot, auto-scheduling agent, multi-tenant architecture

**Key framing:** Write delimitations that allow advanced features to appear as **research contributions** rather than out-of-scope expansion.

---

## Your Avoid List (What NOT to Do)

These tasks are assigned to Jaypee or Christine. Do not work on them:

- C1-01: Background P1 — Core Problem (Jaypee)
- C1-02: Background P2 — Global Context (Jaypee)
- C1-03: Background P3 — National Context (Jaypee)
- C1-04: Background P4 — Local Context (Christine)
- C1-10: Research Questions (Jaypee)
- C1-12: Significance of the Study (Christine)
- C2-02: Lit Review — Automated Scoring and OMR Technologies (Christine)
- C2-06: Review of Related Systems (Jaypee)
- CC-01: References List Compilation (Christine)
- CC-02: Formatting QA Review (Jaypee)
- CC-04: Citation Cross-Check (Jaypee)

**Focus on your strengths:** Technical writing, synthesis, framework design, literature reviews

---

## Quick Reference: Your Guides

- **Formatting:** `guides/GUIDE-1-FORMATTING.md`
- **Chapter 1 Content:** `guides/GUIDE-2-CHAPTER1-CONTENT.md`
- **Chapter 2 Content:** `guides/GUIDE-3-CHAPTER2-CONTENT.md`
- **System Features:** `SYSTEM_FEATURES.md`
- **Existing vs Planned:** `drafts/Existing_and_Planned_Features.md`
- **Task Distribution:** `team_meta/TASK_DISTRIBUTION_PLAN.md`
- **Team Meta Guide:** `team_meta/TEAM_META_GUIDE_Ch1_Ch2.md`

---

## Success Criteria for You

- All 11 tasks completed by their deadlines
- All literature reviews use minimum 5 APA citations, all 2022-2026 (including C2-05)
- C1-05 is exactly 10-12 sentences (not 10-15)
- C1-06 is exactly 8-10 sentences with 3 explicit components
- C1-08 is exactly 2 paragraphs (not 1, not 3)
- C1-07 IPO has exactly 6 inputs and 9 outputs per SYSTEM_FEATURES.md
- C2-07 includes DOMPDF/PHPWord document generation pipeline
- C1-11 covers BOTH existing and planned features with research contribution framing
- All draft references submitted to Christine by June 7
- Narrative consistency review completed by June 9
- No bullets anywhere in any draft; numbered lists only where explicitly allowed
- No bold body text in any draft
- Team coordination maintained throughout (daily check-ins)

---

**You are the technical anchor of this project. Your literature reviews and framework tasks set the foundation for everything else. Stay on schedule, communicate blockers early, and keep the narrative consistent.**
