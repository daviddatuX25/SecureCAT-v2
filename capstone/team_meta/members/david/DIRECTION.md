# Member Task Direction — David
## Team Leader / Product Owner / Lead Developer

> **Role:** Technical architecture, background paragraphs (P1-P3), literature reviews (RBAC, AI, PWA, DPA), framework tasks, narrative consistency, formatting QA
> **Total Claimed Tasks:** 15 tasks
> **Estimated Effort:** 45-55 hours (out of ~55h available)
> **Focus:** Full workload — technical writing, literature synthesis, framework design, quality assurance

---

## Your Tasks at a Glance

### Chapter 1
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C1-01 | Background P1 — Core Problem Statement | 2-3h | Jun 4 | None |
| C1-02 | Background P2 — Global Context | 4-6h | Jun 5 | None |
| C1-03 | Background P3 — National Context (PH) | 4-6h | Jun 5 | None |
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
| CC-02 | Formatting QA Review | 2-3h | Jun 8 | All writing tasks |
| CC-03 | Narrative Consistency Review and Integration | 4-6h | Jun 9 | All writing tasks |

---

## Summary of June 2 Reassignment

**Added tasks:**
- C1-01 (Background P1 — Core Problem) — from Jaypee
- C1-02 (Background P2 — Global Context) — from Jaypee
- C1-03 (Background P3 — National Context) — from Jaypee
- CC-02 (Formatting QA Review) — from Jaypee

**Given up:**
- None (you now have 15 tasks, 45-55h — full capacity)

**Jaypee's new focus:** Local context (C1-04), research questions (C1-10), related systems (C2-06), references (CC-01), citations (CC-04)

**Christine's focus:** OMR/CV literature review (C2-02), significance (C1-12)

---

## Detailed Task Directions

### Week 1 (June 1-5): Background Tasks + Foundation

#### C1-01: Background P1 — Core Problem Statement (June 4)

**Priority:** HIGH — First paragraph of Chapter 1, sets the tone for the entire manuscript

**Target:** 1 paragraph, **8-12 sentences**, citation-free

**What to Do:**

1. **Identify the Core Problem (30 min):**
   - Read `SYSTEM_FEATURES.md` and `drafts/Existing_and_Planned_Features.md` to understand what SecureCAT does.
   - The core problem: ISPSC Tagudin currently handles admission testing through manual, paper-based workflows with no unified digital platform. Specifically:
     - **Manual admission workflows:** Test papers are physically routed between the Guidance Office and Registrar with no digital coordination.
     - **Fragmented scoring:** Scores are computed manually using answer keys; there is no automated scoring pipeline.
     - **Paper-based OMR:** Answer sheets are processed by hand (no computer-vision or automated OMR scanning).
     - **Lack of audit trails:** No record of who accessed what data, who changed a score, or when an action was performed.
     - **No role-based access control:** Anyone with system access can view or modify anything — no permission boundaries between offices.

   The **technical root cause** is the **absence of a unified, cryptographically-secured, role-based digital platform** for admission testing.

2. **Write the Problem Paragraph (60-90 min):**
   - Write **8-12 sentences** in your own words. **NO CITATIONS ALLOWED** — this is the only citation-free paragraph.
   - **Structure:**
     - **Sentences 1-4:** Name the observable symptoms — manual admission workflows, paper-based test routing between Guidance Office and Registrar, manual scoring using answer keys, fragmented processes, no audit trails, no role-based access.
     - **Sentences 5-8:** Pivot to the technical root cause — the absence of a unified, cryptographically-secured, role-based digital platform. Explain why the current manual approach creates vulnerabilities in test security, delays in result processing, and no accountability mechanism.
     - **Sentences 9-12 (if needed):** State what technical intervention is needed — a role-based admission testing system with automated scoring, offline resilience, and cryptographic data integrity.
   - **Key terms to include:** "manual admission testing", "role-based access control", "Guidance Office", "Registrar", "test security", "audit trail", "cryptographically-secured", "unified digital platform"
   - **Critical framing rule:** Make it sound like an **IT/system paper**, NOT a public administration or management paper. State the actual technical gap.

3. **Review & Refine (30 min):**
   - [ ] Count sentences: must be **8-12** (not 7, not 13)
   - [ ] Verify NO citations — P1 is the only citation-free paragraph
   - [ ] Verify IT/system framing (not management/admin framing)
   - [ ] Verify no bullet points or bold body text
   - [ ] Verify the paragraph names the specific problem SecureCAT solves

**Deliverable:** `C1-01_David_Core_Problem.md` — 1 paragraph, 8-12 sentences, citation-free

---

#### C1-02: Background P2 — Global Context (June 5)

**Priority:** HIGH — Global foundation with 5+ citations

**Target:** 1 paragraph, **12-15 sentences**, minimum 5 APA citations (all 2022-2026)

**What to Do:**

1. **Research Global Admission Testing (90 min):**
   - Search Google Scholar for: "admission testing automation", "computer-based testing systems", "educational assessment software"
   - Filter: **2022-2026 only**, peer-reviewed journals
   - Target: 5-7 sources covering:
     - Digital transformation of higher education admissions
     - Automated testing and scoring platforms
     - RBAC in educational systems
     - Computer-vision-based OMR scanning
     - AI-assisted administrative operations
     - Offline-first architectures
     - Zero-trust security models

2. **Extract Key Findings (60 min):**
   - For each source, extract: main finding, methodology, relevance to SecureCAT
   - Group findings by theme (not author-by-author):
     - Theme 1: Efficiency gains from automated admission systems
     - Theme 2: Security architectures (RBAC, zero-trust, cryptographic integrity)
     - Theme 3: Emerging technologies (CV-based OMR, AI assistants, offline-first PWA)

3. **Write Global Context Paragraph (90-120 min):**
   - Write **12-15 sentences** synthesizing global patterns. Include **minimum 5 in-text citations** (Author, Year).
   - **Structure:**
     - **Sentences 1-3:** Global trends in digital transformation of higher education admissions (2-3 citations)
     - **Sentences 4-7:** Automated testing/scoring platforms, RBAC in education, zero-trust security models (2-3 citations)
     - **Sentences 8-11:** Emerging technologies — CV-based OMR, AI-assisted operations, offline-first architectures (1-2 citations)
     - **Sentences 12-15:** How global context sets the stage for local implementation needs (optional citations)
   - **FORBIDDEN pattern:** ❌ "Author A says X. Author B says Y. Author C says Z."
   - **Required synthesis pattern:** ✅ "Global studies demonstrate that automated admission testing systems reduce processing time significantly compared to manual methods (Author A, 2024; Author B, 2023), while role-based access controls have become a standard security practice for test administration (Author C, 2025; Author D, 2024)."

4. **Compile Draft References (30 min):**
   - Create APA 7 draft references for all 5+ sources. Format: `Author, A. A. (Year). Title. *Source*, vol(issue), pages. DOI/URL`
   - Save for Jaypee's CC-01 (References Compilation).

**Reference docs:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 1 Paragraph 2

**Deliverable:** `C1-02_David_Global_Context.md` — 1 paragraph (12-15 sentences, 5+ APA citations, all 2022-2026) + draft APA reference entries

---

#### C1-03: Background P3 — National Context (Philippines) (June 5)

**Priority:** HIGH — Philippine context with 5+ citations

**Target:** 1 paragraph, **12-15 sentences**, minimum 5 APA citations (all 2022-2026)

**What to Do:**

1. **Research Philippine Education & Admission Systems (90 min):**
   - Search: "Philippines college admission automation", "CHED admission policies", "state universities ICT systems"
   - Filter: 2022-2026, CHED/DPA/DepEd/SUC sources
   - Target: 5-7 sources covering:
     - CHED policies on digital transformation
     - RA 10173 (Data Privacy Act of 2012) compliance for student data
     - Digitalization efforts in SUCs
     - Government e-governance initiatives
     - Connectivity challenges in Philippine higher education

2. **Extract Policy & Implementation Details (60 min):**
   - Identify specific mandates (e.g., CHED memoranda, RA citations, agency directives)
   - Note agency directives relevant to admission processes
   - Document Philippine context constraints (infrastructure, budget)
   - Must name specific legislation, agencies, or programs (no vague references)

3. **Write National Context Paragraph (90-120 min):**
   - Write **12-15 sentences** connecting policy to practice. Include **minimum 5 APA in-text citations**.
   - **Structure:**
     - **Sentences 1-4:** National mandates and policies (CHED directives, RA 10173, government e-governance programs) (2-3 citations)
     - **Sentences 5-8:** Current SUC practices and digitalization efforts (1-2 citations)
     - **Sentences 9-11:** Connectivity challenges and infrastructure constraints in Philippine higher education (1-2 citations)
     - **Sentences 12-15:** How national context creates the need for SecureCAT at ISPSC (bridge to local context)

4. **Compile Draft References (30 min):**
   - Create APA 7 references for all 5+ sources
   - Save for Jaypee's CC-01

**Reference docs:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 1 Paragraph 3

**Deliverable:** `C1-03_David_National_Context.md` — 1 paragraph (12-15 sentences, 5+ APA citations, all 2022-2026) + draft APA references

---

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

**Deliverable:** `C1-07_David_IPO_Diagram.md` with Input (6 numbered items) → Process (full title) → Output (9 numbered items)

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

### Week 1-2 (June 3-5): Literature Reviews

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
   - Save for Jaypee's CC-01 (References Compilation)

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

### Week 1-2 (June 3-7): Framework Tasks

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
**Priority:** HIGH — Depends on C1-02, C1-03 (yours) and C1-04 (Jaypee's)

**What to Do:**

1. **Review All Background Paragraphs (30 min):**
   - Read your P2 (global context) and P3 (national context)
   - Read Jaypee's P4 (local context)
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

### Week 1-2 (June 3-6): Literature Reviews (continued)

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

### Week 2 (June 8-9): QA Tasks

#### CC-02: Formatting QA Review (June 8)

**Priority:** HIGH — Final formatting check before submission

**Target:** Annotated manuscript with formatting corrections marked

**What to Do:**

1. **Review GUIDE-1 Formatting Rules (30 min):**
   Read `guides/GUIDE-1-FORMATTING.md` thoroughly. Here are the **exact rules** you must verify:

   | Rule | Correct Value | Common Mistake |
   |------|--------------|----------------|
   | **Left margin** | **1.5 inches** | ❌ 1 inch — WRONG |
   | **Right margin** | **1.0 inch** | — |
   | **Top margin** | **1.0 inch** | — |
   | **Bottom margin** | **1.0 inch** | — |
   | **Font** | Times New Roman, 12pt | — |
   | **Line spacing** | Double throughout | — |
   | **Paragraph indent** | **Exactly 5 spaces** | ❌ Tab or 3 spaces |
   | **Alignment** | Justified | — |
   | **Bullet points** | **NONE anywhere** | ❌ Bullets in scope/significance |
   | **Bold body text** | **NONE** — bold ONLY for headings, subheadings, figure captions, table captions | ❌ Bold for emphasis |
   | **Table captions** | **Above** table, **left-aligned**, **bold** | ❌ Below or centered |
   | **Figure captions** | **Below** figure, **bold** | ❌ Above figure |
   | **Table borders** | 1pt line width | ❌ 0.5pt or no borders |
   | **Page numbers** | Every page **except** first page of each chapter | ❌ Missing or on chapter first page |
   | **Header/footer borders** | Must sit entirely within 1-inch top/bottom margins | ❌ Bleeding outside margins |
   | **Extra spacing between paragraphs** | **0pt** space before/after | ❌ Extra blank lines between paragraphs |

2. **Check All Formatting Rules (60-90 min):**
   Go through the entire manuscript and verify every rule:
   - [ ] **Margins:** Left 1.5", Right 1.0", Top 1.0", Bottom 1.0" on ALL pages
   - [ ] **Font:** All text is Times New Roman 12pt (headings can be bold/italic but same font and size)
   - [ ] **Spacing:** Double-spaced, NO extra space between paragraphs (space before/after = 0pt)
   - [ ] **Paragraph indent:** Exactly 5 spaces at the start of every paragraph
   - [ ] **No bullets:** Zero bullet points anywhere in the manuscript — numbered lists ONLY in IPO boxes and specific objectives
   - [ ] **No bold body text:** Bold ONLY for chapter headings, subheadings, figure captions, table captions
   - [ ] **Tables:** 1pt borders, caption above (left-aligned, bold)
   - [ ] **Figures:** Caption below (bold, "Figure X. Title")
   - [ ] **Page numbers:** Top-right, continuous, hidden on first page of each chapter
   - [ ] **Headers/footers:** Borders within margin boundaries

3. **Create Formatting Report (15-30 min):**
   List every violation found with location and correction needed:
   - "Page 5: Extra space between paragraphs — remove (set space after to 0pt)"
   - "Table 2: Border is 0.5pt — change to 1pt"
   - "Section 2.3: Bullet points used in scope paragraph — convert to paragraph form"
   - "Page 12: Left margin is 1.0" — change to 1.5""

**Deliverable:** `CC-02_David_Formatting_QA.md` — complete formatting checklist with violations and corrections

---

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

### Week 1 (June 1-5): Foundation + Background + Literature
- [ ] **June 1-2:** C1-07 (IPO Diagram) — 6 inputs, 9 outputs, numbered lists only
- [ ] **June 1-2:** C1-09 (Objectives) — general objective + 3 numbered specific objectives
- [ ] **June 2-3:** C1-01 (Core Problem) — write 8-12 sentence citation-free paragraph
- [ ] **June 2-4:** C1-02 (Global Context) — 12-15 sentences with 5+ citations
- [ ] **June 2-4:** C1-03 (National Context) — 12-15 sentences with 5+ citations
- [ ] **June 3-4:** C1-08 (Framework Narrative) — exactly 2 paragraphs
- [ ] **June 3-4:** C1-11 (Scope and Delimitations) — 2 paragraphs covering existing AND planned features
- [ ] **June 3-5:** C2-01 (RBAC Review) — minimum 5 citations, all 2022-2026
- [ ] **June 3-5:** C2-03 (AI/RAG Review) — minimum 5 citations, all 2022-2026
- [ ] **June 3-5:** C2-05 (DPA/Multi-Tenancy Review) — minimum 5 citations, all 2022-2026
- [ ] **June 5:** C1-01, C1-02, C1-03, C2-01, C2-03, C2-05 due

### Week 1-2 (June 5-7): Synthesis, Clinching, PWA, Framework
- [ ] **June 5-6:** C2-04 (PWA Review) — minimum 5 citations, all 2022-2026
- [ ] **June 5-6:** C2-07 (Technical Framework) — 1-2 paragraphs covering ALL required components
- [ ] **June 5-6:** C1-05 (Synthesis and Gap) — 10-12 sentences, wait for Jaypee's C1-04
- [ ] **June 6-7:** C1-06 (Clinching) — 8-10 sentences with 3 explicit components
- [ ] **June 6-7:** C2-08 (Framework Prose Ch2) — 2-3 paragraphs expanding IPO
- [ ] **June 7:** C1-05, C1-06, C2-04, C2-07, C2-08 due

### Week 2 (June 8-9): QA
- [ ] **June 8:** CC-02 (Formatting QA) — check ALL formatting rules per GUIDE-1
- [ ] **June 9:** CC-03 (Narrative Consistency) — read entire manuscript, fix inconsistencies, verify paragraph lengths

---

## Communication Responsibilities

1. **Daily Progress Updates:**
   - Post brief updates in group Discord
   - Example: "Finished C1-01 core problem paragraph (10 sentences, no citations). Starting C1-02 global research."
   - Flag blockers immediately

2. **Coordinate with Jaypee:**
   - Provide C1-09 (Objectives) to Jaypee by June 3 for C1-10 (Research Questions)
   - Wait for Jaypee's C1-04 (Local Context) before starting C1-05 (Synthesis)
   - Ask for Jaypee's CC-01 (References) by June 8 for citation checks

3. **Coordinate with Christine:**
   - Provide ISPSC context to Jaypee if he asks (you know the system features)
   - Answer technical questions about SecureCAT architecture

4. **Submit Draft References:**
   - Your draft references for C1-01, C1-02, C1-03, C2-01, C2-03, C2-04, C2-05 should be ready by June 6
   - Send these to Jaypee for CC-01 (References Compilation)

---

## Summary of Your 15 Tasks

**Chapter 1 (9 tasks):**
- C1-01, C1-02, C1-03: Background paragraphs (P1-P3) — 8-15 sentences each
- C1-05: Synthesis and Gap — 10-12 sentences
- C1-06: Clinching Statement — 8-10 sentences
- C1-07: IPO Diagram — 6 inputs, 9 outputs
- C1-08: Framework Narrative — exactly 2 paragraphs
- C1-09: Objectives — general + 3 specific
- C1-11: Scope and Delimitations — 2 paragraphs

**Chapter 2 (6 tasks):**
- C2-01, C2-03, C2-04, C2-05: Literature reviews — 1-2 paragraphs each, 5+ citations
- C2-07: Technical Framework — 1-2 paragraphs covering full stack
- C2-08: Framework Prose — 2-3 paragraphs

**Cross-Cutting (2 tasks):**
- CC-02: Formatting QA — check all GUIDE-1 rules
- CC-03: Narrative Consistency — final integration review

**Total estimated effort: 45-55 hours** — full capacity

---

You are carrying the team's full technical and narrative workload. Focus on synthesis, technical accuracy, and narrative consistency. Your role is to ensure all pieces fit together into a coherent manuscript that clearly presents SecureCAT as the solution to ISPSC Tagudin's admission testing challenges.
