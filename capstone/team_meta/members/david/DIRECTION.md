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
| C1-05 | Background P5 — Synthesis & Gap | 3-4h | Jun 6 | C1-02, C1-03, C1-04 |
| C1-06 | Background P6 — Clinching Statement | 2-3h | Jun 7 | C1-02, C1-03, C1-04 |
| C1-07 | Conceptual Framework — IPO Diagram | 2-3h | Jun 3 | None |
| C1-08 | Conceptual Framework — Narrative | 2-3h | Jun 4 | C1-07 |
| C1-09 | Objectives of the Study | 2-3h | Jun 3 | None |
| C1-11 | Scope and Delimitations | 2-3h | Jun 4 | None |

### Chapter 2
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C2-01 | Lit Review — RBAC + Zero-Trust | 4-6h | Jun 5 | None |
| C2-03 | Lit Review — AI/RAG in Education | 4-6h | Jun 5 | None |
| C2-04 | Lit Review — PWA/Offline Systems | 4-6h | Jun 6 | None |
| C2-05 | Lit Review — DPA/Multi-Tenancy | 3-5h | Jun 5 | None |
| C2-07 | Technical Framework | 3-4h | Jun 6 | None |
| C2-08 | Conceptual Framework Prose | 2-3h | Jun 7 | C1-07, C1-08 |

### Cross-Cutting
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| CC-03 | Narrative Consistency Review | 4-6h | Jun 9 | All writing tasks |

---

## Detailed Task Directions

### Week 1 (June 1-3): Foundation Tasks

#### C1-07: Conceptual Framework — IPO Diagram (June 3)
**Priority:** HIGH — Blocks C1-08 and C2-08

**What to Do:**
1. List all system **Inputs** (numbered list, not bullets):
   - User-submitted data: admission test requests, student records, test forms
   - Configuration: test types, time limits, passing scores, question banks
   - Role assignments: Guidance staff, Registrar staff, Administrator permissions
   - Source documents: existing manual test records, student academic history

2. Define the **Process** box:
   - Text: "SecureCAT: A Role-Based College Admission Testing System"

3. List all system **Outputs** (numbered list, not bullets):
   - Generated: test schedules, room assignments, score reports
   - Real-time: role-based dashboards (Guidance view, Registrar view)
   - Audit: all test-related activity logs
   - Stored: scored test results, pass/fail determinations

4. Create the diagram (3 boxes: Input → Process → Output):
   - Use simple formatting (boxes with numbered lists inside)
   - Ensure readable on a standard page

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 2

**Deliverable:** Clean IPO diagram saved as `C1-07_David_IPO_Diagram.md` (text-based diagram)

---

#### C1-09: Objectives of the Study (June 3)
**Priority:** HIGH — Blocks C1-10 (Jaypee's task)

**What to Do:**
1. **General Objective** (one paragraph):
   - Name SecureCAT
   - State overarching purpose: develop role-based admission testing for ISPSC Tagudin Guidance and Registrar offices

2. **Specific Objectives** (numbered list, NOT bullets):
   1. **Identify** — Document existing manual admission testing processes, operational gaps, and requirements at ISPSC Tagudin
   2. **Develop** — SecureCAT with role-based access control, offline-first PWA architecture, and AI Companion features for coordinated admission testing
   3. **Evaluate** — the usability of SecureCAT using the System Usability Scale (SUS)

3. **CRITICAL CHECK:**
   - Objective 3 must say **"evaluate the usability"** ONLY
   - Do NOT mix terms: "usability and acceptability" is incorrect
   - SUS measures usability — if you want acceptability, you need a separate instrument

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 3

**Deliverable:** `C1-09_David_Objectives.md` with general objective paragraph + 3-item numbered list

---

### Week 1-2 (June 3-5): Chapter 2 Literature Reviews

#### C2-01: Lit Review — RBAC + Zero-Trust Security (June 5)
**Priority:** HIGH — Technical foundation

**What to Do:**
1. **Research** (90 min):
   - Search Google Scholar: "role-based access control education systems", "zero-trust security educational software", "HMAC authentication web applications", "multi-tenant security architecture"
   - Filter: **2022-2026 only**, peer-reviewed journals
   - Target: 5-7 sources

2. **Extract Findings** (60 min):
   - For each source, extract:
     - RBAC implementation patterns
     - Zero-trust security models
     - Multi-tenant isolation strategies
     - Authentication/authorization practices
   - Group findings by theme (not author-by-author)

3. **Write Review** (90-120 min):
   - **Thematic synthesis** (3-4 paragraphs):
     - Paragraph 1: RBAC principles in educational/administrative systems
     - Paragraph 2: Zero-trust security and authentication (HMAC)
     - Paragraph 3: Multi-tenant security architectures
     - Paragraph 4: How SecureCAT's role-based design aligns with this literature
   - Minimum 5 in-text citations (Author, Year)
   - **DO NOT** summarize author-by-author — synthesize by theme

4. **Compile References** (30 min):
   - Create draft APA 7 references for all sources
   - Save for CC-01 (Christine will compile final list)

**Reference:** `GUIDE-3-CHAPTER2-CONTENT.md` (literature review pattern)

**Deliverable:** `C2-01_David_RBAC_Review.md` with 3-4 paragraphs + draft references

---

#### C2-03: Lit Review — AI/RAG in Education (June 5)
**Priority:** HIGH — AI Companion component

**What to Do:**
1. **Research** (90 min):
   - Search: "AI companions education", "RAG knowledge bases", "LLM scheduling assistants", "AI educational chatbots"
   - Filter: **2022-2026 only**, peer-reviewed
   - Target: 5-7 sources

2. **Extract Findings** (60 min):
   - For each source, extract:
     - AI assistant use cases in education
     - RAG (Retrieval-Augmented Generation) architectures
     - Embedding strategies (vector databases, semantic search)
     - Context management in LLM applications

3. **Write Review** (90-120 min):
   - **Thematic synthesis** (3-4 paragraphs):
     - Paragraph 1: AI assistants in educational contexts
     - Paragraph 2: RAG architectures and knowledge bases
     - Paragraph 3: LLM-powered scheduling and information systems
     - Paragraph 4: How SecureCAT's AI Companion (RAG + MixedBread embeddings) fits this literature
   - Minimum 5 in-text citations (Author, Year)

4. **Compile References** (30 min):
   - Create draft APA 7 references for all sources

**Reference:** `SYSTEM_FEATURES.md` for AI Companion details

**Deliverable:** `C2-03_David_AI_RAG_Review.md` with 3-4 paragraphs + draft references

---

#### C2-04: Lit Review — PWA/Offline-First Systems (June 6)
**Priority:** HIGH — PWA architecture

**What to Do:**
1. **Research** (90 min):
   - Search: "progressive web apps education", "offline-first web applications", "PWA service worker strategies", "PWA low-connectivity contexts"
   - Filter: **2022-2026 only**, peer-reviewed
   - Target: 5-7 sources

2. **Extract Findings** (60 min):
   - For each source, extract:
     - PWA adoption patterns and benefits
     - Offline-first architectural patterns
     - Service worker design strategies
     - Sync strategies for reconnection

3. **Write Review** (90-120 min):
   - **Thematic synthesis** (3-4 paragraphs):
     - Paragraph 1: PWA benefits and adoption trends
     - Paragraph 2: Offline-first architectures and service workers
     - Paragraph 3: PWAs in low-connectivity contexts (relevant to Philippines)
     - Paragraph 4: How SecureCAT's PWA architecture enables offline testing
   - Minimum 5 in-text citations (Author, Year)

4. **Compile References** (30 min):
   - Create draft APA 7 references for all sources

**Reference:** `SYSTEM_FEATURES.md` for PWA details

**Deliverable:** `C2-04_David_PWA_Review.md` with 3-4 paragraphs + draft references

---

#### C2-05: Lit Review — DPA/Multi-Tenancy (June 5)
**Priority:** MEDIUM — Compliance and architecture

**What to Do:**
1. **Research** (60 min):
   - Search: "Data Privacy Act education Philippines", "multi-tenant database architecture", "tenant isolation SaaS", "RA 10173 implementation"
   - Filter: **2022-2026 only**, peer-reviewed
   - Target: 4-6 sources

2. **Extract Findings** (45 min):
   - For each source, extract:
     - RA 10173 (Data Privacy Act) requirements for educational data
     - Multi-tenant database patterns
     - Tenant isolation strategies (row-level security, separate schemas)

3. **Write Review** (60-90 min):
   - **Thematic synthesis** (2-3 paragraphs):
     - Paragraph 1: Data privacy compliance in Philippine education (RA 10173)
     - Paragraph 2: Multi-tenant architectural patterns for tenant isolation
     - Paragraph 3: How SecureCAT's multi-tenant architecture complies with RA 10173
   - Minimum 4 in-text citations (Author, Year)

4. **Compile References** (30 min):
   - Create draft APA 7 references for all sources

**Reference:** `SYSTEM_FEATURES.md` for multi-tenant architecture details

**Deliverable:** `C2-05_David_DPA_MultiTenancy_Review.md` with 2-3 paragraphs + draft references

---

#### C2-07: Technical Framework (June 6)
**Priority:** HIGH — System architecture documentation

**What to Do:**
1. **Define Technical Stack** (45-60 min):
   - Backend: Laravel 12 (PHP 8.4), Inertia v2
   - Frontend: Svelte 5, Tailwind 4
   - PWA: Service worker, offline-first sync
   - Database: Multi-tenant PostgreSQL, tenant isolation
   - AI: MixedBread embeddings, RAG pattern, LLM integration

2. **Describe System Architecture** (60-90 min):
   - Write 2-3 paragraphs explaining:
     - **Frontend:** Svelte components, Tailwind styling, Inertia for seamless navigation
     - **Backend:** Laravel controllers, policies for role-based access, middleware for authentication
     - **Database:** Multi-tenant schema, tenant isolation strategies (row-level security or separate schemas)
     - **PWA:** Service worker for offline-first testing, sync strategies for reconnection
     - **AI:** MixedBread embeddings for semantic search, RAG pattern for AI Companion knowledge base
   - Reference `SYSTEM_FEATURES.md` for specific features

3. **Optional: Create Architecture Diagram** (if time permits):
   - System architecture figure showing layers (Frontend → Backend → Database → External Services)

**Reference:** `SYSTEM_FEATURES.md`, AGENTS.md

**Deliverable:** `C2-07_David_Technical_Framework.md` with 2-3 paragraphs

---

### Week 1-2 (June 3-7): Chapter 1 Framework Tasks

#### C1-08: Conceptual Framework — Narrative (June 4)
**Priority:** HIGH — Depends on C1-07

**What to Do:**
1. **Paragraph 1 — Explain Inputs** (45-60 min):
   - Read your C1-07 IPO diagram
   - For each input component, explain:
     - What it is
     - Why it's necessary for the system
     - How it connects to role-based design
   - Example: "User role assignments are necessary input to ensure that only authorized Guidance staff can schedule tests and Registrar staff can view scores, enforcing role-based access control."

2. **Paragraph 2 — Explain Process → Outputs** (45-60 min):
   - Explain how the system processes inputs and produces outputs
   - Make the mechanical connection explicit:
     - How test requests are routed through role-based workflows
     - How scoring is computed and stored
     - How audit logs capture all activities
   - Describe each output and its purpose

3. **Review Against Diagram** (15-30 min):
   - Ensure narrative matches IPO diagram exactly
   - No new inputs/outputs introduced in text

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 2

**Deliverable:** `C1-08_David_Framework_Narrative.md` with 2 paragraphs

---

#### C1-11: Scope and Delimitations (June 4)
**Priority:** HIGH — System boundaries

**What to Do:**
1. **Scope Paragraph** (60-90 min):
   - **Paragraph form ONLY** (no bullets, no numbered lists)
   - Include:
     - **Authorized user types:** Guidance staff (test scheduling), Registrar staff (results viewing), Administrator (system configuration)
     - **Modules included:** test scheduling, test administration, automatic scoring, reporting, audit logs, AI Companion
     - **Locale:** ISPSC Tagudin, Ilocos Sur, Philippines
     - **Timeframe:** Development from [start date] to [end date], deployment for [academic year/semester]
     - **Principal variables:** test data, student records, role assignments, test configurations
     - **Justification:** Why these boundaries (focus on ISPSC admission testing context)

2. **Delimitation Paragraph** (60-90 min):
   - **Paragraph form ONLY**
   - Include:
     - **What system does NOT do:** No LMS integration, no payment processing, no test content generation (tests are pre-configured)
     - **Hardware/network dependencies:** Internet connection for PWA sync, modern web browser, device requirements (desktop/tablet)
     - **Single-site constraint:** ISPSC Tagudin only (no multi-campus deployment)
     - **Manual processes remaining:** Test content creation, policy decisions, student eligibility determination
     - **Data privacy:** Complies with RA 10173 (Data Privacy Act of 2012); system stores only necessary student data (name, program, test scores); access controlled via role-based permissions

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 4

**Deliverable:** `C1-11_David_Scope_Delimitations.md` with 2 paragraphs

---

#### C1-05: Background P5 — Synthesis & Gap (June 6)
**Priority:** HIGH — Depends on Jaypee's C1-02, C1-03, Christine's C1-04

**What to Do:**
1. **Review All Background Paragraphs** (30 min):
   - Read Jaypee's P2 (global context) and P3 (national context)
   - Read Christine's P4 (local context)
   - Identify recurring themes: automation trends, security needs, coordination gaps

2. **Synthesize Findings** (60-90 min):
   - **DO NOT summarize author-by-author**
   - Group ideas thematically:
     - Global: Admission testing digitization trends + security architectures
     - National: Philippine mandates + current practices
     - Local: ISPSC manual processes + operational constraints
   - Identify the **research gap**: What no existing study/system addresses
     - Gap: Role-based, multi-office (Guidance + Registrar) admission testing coordination with offline-first PWA architecture and AI-assisted scheduling

3. **Write Synthesis Paragraph** (60-90 min):
   - **10-15 sentences** synthesizing (NOT summarizing)
   - Structure: Grouped ideas → Revealed gap → What SecureCAT does differently
   - Example: "While global studies demonstrate the efficiency of automated admission testing (Author A, 2024; Author B, 2023) and national mandates emphasize digital transformation in Philippine SUCs (Author C, 2025), local implementations at institutions like ISPSC Tagudin remain manual and uncoordinated between offices. This gap — the lack of role-based, multi-office coordination with offline-first capability — is what SecureCAT addresses through its PWA architecture and AI-assisted workflows."

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 1, Paragraph 5

**Deliverable:** `C1-05_David_Synthesis_Gap.md` with 1 synthesis paragraph

---

#### C1-06: Background P6 — Clinching Statement (June 7)
**Priority:** HIGH — Depends on C1-02, C1-03, C1-04

**What to Do:**
1. **Connect Literature to Study Design** (30 min):
   - Explain how the reviewed literature (P2-P5) shaped the study's structure
   - Note which findings influenced system features (e.g., PWA for low-connectivity contexts, RBAC for security)

2. **State Rationale for Topic Selection** (30 min):
   - Include direct observation of the problem at ISPSC Tagudin
   - Describe specific manual processes witnessed (paper-based test routing, lack of audit trails)

3. **Write Clinching Paragraph** (60-90 min):
   - **10-15 sentences** with three required components:
     1. **How literature assisted:** "The reviewed literature on automated admission testing, role-based access control, and progressive web apps guided the selection of SecureCAT's technical architecture and feature set."
     2. **Why topic selected:** "Direct observation of manual admission testing processes at ISPSC Tagudin revealed inefficiencies in test routing between Guidance and Registrar offices, lack of audit trails, and vulnerability to scoring errors."
     3. **Why SecureCAT is the critical solution:** "SecureCAT addresses these gaps by providing role-based coordination, offline-first testing capability, and AI-assisted scheduling — making it the critical intervention for modernizing ISPSC Tagudin's admission testing workflow."
   - **Optional:** Connect to SDG 4 (Quality Education) or SDG 9 (Industry and Innovation)

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 1, Paragraph 6

**Deliverable:** `C1-06_David_Clinching.md` with 1 clinching paragraph

---

#### C2-08: Conceptual Framework Prose — Chapter 2 Expansion (June 7)
**Priority:** MEDIUM — Expansion of Chapter 1 framework

**What to Do:**
1. **Review Chapter 1 Conceptual Framework** (30 min):
   - Read your C1-07 (IPO diagram) and C1-08 (narrative)
   - Ensure Chapter 2 prose is consistent but expanded

2. **Write Expanded Framework Narrative** (60-90 min):
   - **2-3 paragraphs** explaining framework in more depth
   - Paragraph 1: Reiterate IPO framework and how it guides system development
   - Paragraph 2: Explain how framework connects to research design (objectives → methodology)
   - Paragraph 3 (optional): Describe how framework ensures SecureCAT meets ISPSC Tagudin's needs

3. **Align with Methodology** (15-30 min):
   - Ensure framework aligns with Chapter 2 methodology sections (research design, software model)

**Reference:** `GUIDE-3-CHAPTER2-CONTENT.md` (Chapter 2 structure)

**Deliverable:** `C2-08_David_Framework_Prose_Ch2.md` with 2-3 paragraphs

---

### Week 2 (June 9): Cross-Cutting Task

#### CC-03: Narrative Consistency Review (June 9)
**Priority:** CRITICAL — Final review before submission

**What to Do:**
1. **Read All Chapters End-to-End** (60-90 min):
   - Chapter 1: Background → Framework → Objectives → Scope → Significance
   - Chapter 2: Research Design → Software Model → Project Plan → Assignments → Population → Instruments → Analysis
   - Read as a continuous narrative, not isolated sections

2. **Check Consistency Across Sections** (90-120 min):
   - **Problem statement alignment:**
     - Background problem matches objectives
     - Objectives align with research questions
   - **Framework alignment:**
     - Framework aligns with methodology
     - IPO inputs/outputs match technical framework
   - **Scope alignment:**
     - Scope matches features described in literature reviews
     - Delimitations match what system does/doesn't do
   - **Citation consistency:**
     - All (Author, Year) citations match reference entries
     - No orphaned citations or references

3. **Fix Inconsistencies** (60-90 min):
   - Edit text to ensure narrative flow
   - Align terminology (e.g., "SecureCAT" used consistently throughout)
   - Ensure logical progression from problem → solution → evaluation
   - Fix citation/reference mismatches

**Reference:** All Chapter 1 and Chapter 2 guides

**Deliverable:** Edited manuscript files with consistent narrative, terminology, and citations

---

## Your Week--by-Week Schedule

### Week 1 (June 1-3): Foundation
- [ ] **June 1-2:** C1-07 (IPO Diagram) + C1-09 (Objectives)
- [ ] **June 2-3:** C2-01 (RBAC Review) starts

### Week 1 (June 3-5): Literature Reviews
- [ ] **June 3-4:** C2-03 (AI/RAG Review) + C2-05 (DPA Review)
- [ ] **June 4-5:** C1-08 (Framework Narrative) + C1-11 (Scope)
- [ ] **June 5:** C2-01, C2-03, C2-05 due

### Week 2 (June 6-7): Synthesis & Framework
- [ ] **June 6:** C2-04 (PWA Review) + C2-07 (Technical Framework)
- [ ] **June 6:** C1-05 (Synthesis & Gap) — wait for Jaypee/Christine background
- [ ] **June 7:** C1-06 (Clinching) + C2-08 (Framework Prose)

### Week 2 (June 9): Final Review
- [ ] **June 9:** CC-03 (Narrative Consistency Review)

---

## Communication Responsibilities

As Team Leader, you are responsible for:

1. **Daily Check-ins:**
   - Post brief progress updates in group Discord
   - Ask team members for their progress
   - Flag blockers immediately

2. **Task Coordination:**
   - Remind Jaypee of his June 4-5 deadlines (C1-02, C1-03, C1-10)
   - Remind Christine of her June 5 deadline (C1-04, C2-02)
   - Ensure all draft references are submitted to Christine by June 7

3. **Quality Gates:**
   - Review Jaypee's background paragraphs before June 6 (needed for your C1-05 synthesis)
   - Review Christine's local context before June 6 (needed for your C1-05 synthesis)
   - Coordinate with Christine on CC-01 (References) by June 8

4. **Final Integration:**
   - Collect all drafts by June 8
   - Run CC-03 (Narrative Consistency Review) on June 9
   - Ensure final manuscript is ready by June 10

---

## Your Avoid List (What NOT to Do)

Based on your self-assessment, you should **avoid** these tasks (assigned to Jaypee/Christine):
- ❌ Background P1, P2, P3 (Jaypee)
- ❌ Background P4 (Christine)
- ❌ Research Questions (Jaypee)
- ❌ Significance of the Study (Christine)
- ❌ Related Systems Review (Jaypee)
- ❌ References Compilation (Christine)
- ❌ Formatting QA (Jaypee)
- ❌ Citation Cross-Check (Jaypee)

**Focus on your strengths:** Technical writing, synthesis, framework design, literature reviews

---

## Quick Reference: Your Guides

- **Formatting:** `guides/GUIDE-1-FORMATTING.md`
- **Chapter 1 Content:** `guides/GUIDE-2-CHAPTER1-CONTENT.md`
- **Chapter 2 Content:** `guides/GUIDE-3-CHAPTER2-CONTENT.md`
- **System Features:** `SYSTEM_FEATURES.md`
- **Task Distribution:** `team_meta/TASK_DISTRIBUTION_PLAN.md`

---

## Success Criteria for You

✅ All 11 tasks completed by their deadlines  
✅ All literature reviews use 2022-2026 sources only  
✅ All draft references submitted to Christine by June 7  
✅ Narrative consistency review completed by June 9  
✅ Team coordination maintained throughout (daily check-ins)  

---

**You're the technical anchor of this project. Your literature reviews and framework tasks set the foundation for everything else. Stay on schedule, communicate blockers early, and keep the narrative consistent.**
