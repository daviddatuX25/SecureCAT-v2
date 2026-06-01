# Chapter 1 & 2 Task Distribution Plan
## SecureCAT-v2 Capstone | Deadline: June 10, 2026

> **Last Updated:** June 1, 2026
> **Status:** Final Assignment Distribution

---

## Team Members & Roles

| Member | Role | Email/Discord | Claimed Hours | Available Capacity |
|--------|------|--------------|---------------|-------------------|
| **David** | Team Leader / Product Owner / Lead Developer | daviddatu_ | 33-46h | ~55h total |
| **Christine** | Team Member — Local Literature & Significance | Christine | 6-9h | TBD |
| **Jaypee** | Team Member — Background & QA Tasks | Jaypee | 20-30h (assigned) | TBD |

---

## Assignment Strategy

**Distribution Principles:**
1. **David** — Technical architecture, literature reviews (RBAC, AI, PWA, DPA), framework tasks, narrative consistency
2. **Christine** — Local context research, significance, admission/guidance systems (ISPSC focus)
3. **Jaypee** — Background paragraphs (global/national), related systems review, formatting QA, citations

**Assumptions for Jaypee (Member 3):**
- Standard BSIT student with basic academic writing capability
- Can perform web research and follow formatting guides
- Needs detailed direction for each task (will create specific guides)
- Assigned 20-30 hours (reasonable for completing unassigned tasks)

---

## Complete Task Assignment Matrix

### Chapter 1 Assignments

| Task ID | Task Name | Assigned | Est. Hours | Deadline | Dependencies |
|---------|-----------|----------|------------|----------|--------------|
| C1-01 | Background P1 — Core Problem Statement | **Jaypee** | 2-3h | Jun 4 | None |
| C1-02 | Background P2 — Global Context | **Jaypee** | 4-6h | Jun 5 | None |
| C1-03 | Background P3 — National Context (PH) | **Jaypee** | 4-6h | Jun 5 | None |
| C1-04 | Background P4 — Local Context (ISPSC) | **Christine** | 3-5h | Jun 5 | None |
| C1-05 | Background P5 — Synthesis & Gap | **David** | 3-4h | Jun 6 | C1-02, C1-03, C1-04 |
| C1-06 | Background P6 — Clinching Statement | **David** | 2-3h | Jun 7 | C1-02, C1-03, C1-04 |
| C1-07 | Conceptual Framework — IPO Diagram | **David** | 2-3h | Jun 3 | None |
| C1-08 | Conceptual Framework — Narrative | **David** | 2-3h | Jun 4 | C1-07 |
| C1-09 | Objectives of the Study | **David** | 2-3h | Jun 3 | None |
| C1-10 | Research Questions | **Jaypee** | 1-2h | Jun 4 | C1-09 |
| C1-11 | Scope and Delimitations | **David** | 2-3h | Jun 4 | None |
| C1-12 | Significance of the Study | **Christine** | 2-3h | Jun 5 | None |

### Chapter 2 Assignments

| Task ID | Task Name | Assigned | Est. Hours | Deadline | Dependencies |
|---------|-----------|----------|------------|----------|--------------|
| C2-01 | Lit Review — RBAC + Zero-Trust | **David** | 4-6h | Jun 5 | None |
| C2-02 | Lit Review — OMR/CV Scoring | **Christine** | 4-6h | Jun 5 | None |
| C2-03 | Lit Review — AI/RAG in Education | **David** | 4-6h | Jun 5 | None |
| C2-04 | Lit Review — PWA/Offline Systems | **David** | 4-6h | Jun 6 | None |
| C2-05 | Lit Review — DPA/Multi-Tenancy | **David** | 3-5h | Jun 5 | None |
| C2-06 | Review of Related Systems | **Jaypee** | 4-6h | Jun 6 | None |
| C2-07 | Technical Framework | **David** | 3-4h | Jun 6 | None |
| C2-08 | Conceptual Framework Prose (Ch2) | **David** | 2-3h | Jun 7 | C1-07, C1-08 |

### Cross-Cutting Assignments

| Task ID | Task Name | Assigned | Est. Hours | Deadline | Dependencies |
|---------|-----------|----------|------------|----------|--------------|
| CC-01 | References List Compilation | **Christine** | 3-4h | Jun 8 | All writing tasks |
| CC-02 | Formatting QA Review | **Jaypee** | 2-3h | Jun 8 | All writing tasks |
| CC-03 | Narrative Consistency Review | **David** | 4-6h | Jun 9 | All writing tasks |
| CC-04 | Citation Cross-Check | **Jaypee** | 1-2h | Jun 8 | All writing tasks |

---

## Workload Summary

| Member | Total Tasks | Total Hours | Utilization | Status |
|--------|-------------|-------------|--------------|--------|
| **David** | 11 tasks | 33-46h | 60-84% of 55h | ✅ Balanced |
| **Christine** | 3 tasks | 9-12h | TBD capacity | ✅ Focused |
| **Jaypee** | 5 tasks | 15-23h | TBD capacity | ✅ Assignments complete |

**Total Project Effort:** ~57-81 hours across all members

---

## Detailed Task Breakdowns

### Chapter 1 — Background of the Study

#### C1-01: Background P1 — Core Problem Statement (Jaypee)
**Effort:** 2-3 hours  
**Due:** June 4  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Identify Core Problem (30 min)**
   - Read SecureCAT system description in SYSTEM_FEATURES.md
   - Identify the exact technical problem: manual admission testing with no role-based coordination between Guidance and Registrar
   - List observable symptoms: paper-based test routing, manual scoring, lack of audit trails

2. **Write Problem Paragraph (60-90 min)**
   - Draft 10-15 sentences in own words (no citations)
   - Structure: symptoms → technical root cause → why current systems fail
   - Key terms to include: "manual admission testing", "role-based access control", "Guidance Office", "Registrar", "test security", "audit trail"
   - DO NOT sound like public administration — keep it technical/IT-focused

3. **Review & Refine (30 min)**
   - Check paragraph has 10-15 sentences
   - Verify no citations (P1 is citation-free)
   - Ensure IT/system framing (not management/admin framing)

**Deliverable:** One 10-15 sentence paragraph, citation-free, clearly naming the technical gap

---

#### C1-02: Background P2 — Global Context (Jaypee)
**Effort:** 4-6 hours  
**Due:** June 5  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Research Global Admission Testing (90 min)**
   - Search Google Scholar for: "admission testing automation", "computer-based testing systems", "educational assessment software"
   - Filter: 2022-2026, peer-reviewed journals
   - Target: 5-7 sources covering:
     - Global trends in admission testing digitization
     - Efficiency studies comparing manual vs. automated systems
     - Security architectures for high-stakes testing
     - Role-based access in educational software

2. **Extract Key Findings (60 min)**
   - For each source, extract: main finding, methodology, relevance to SecureCAT
   - Group findings by theme: efficiency, security, coordination
   - Note citation details (author, year, journal, DOI/URL)

3. **Write Global Context Paragraph (90-120 min)**
   - Draft 10-15 sentences synthesizing global patterns
   - Include minimum 5 APA in-text citations (Author, Year)
   - Structure: global trends → efficiency evidence → security practices → adoption patterns
   - DO NOT list authors one-by-one — synthesize by theme

4. **Compile Draft References (30 min)**
   - Create APA 7 references for all 5+ sources
   - Save for CC-01 (References Compilation)

**Deliverable:** One 10-15 sentence paragraph with 5+ citations (2022-2026), draft APA reference entries

---

#### C1-03: Background P3 — National Context (Philippines) (Jaypee)
**Effort:** 4-6 hours  
**Due:** June 5  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Research Philippine Education & Admission Systems (90 min)**
   - Search: "Philippines college admission automation", "CHED admission policies", "state universities ICT systems"
   - Filter: 2022-2026, CHED/DPa/deped/SUC sources
   - Target: 5-7 sources covering:
     - CHED policies on digital transformation
     - State university admission testing practices
     - Data privacy in education (RA 10173 context)
     - ICT initiatives in Philippine SUCs

2. **Extract Policy & Implementation Details (60 min)**
   - Identify specific mandates (e.g., CHED memoranda, RA citations)
     - Note agency directives relevant to admission processes
     - Document Philippine context constraints (infrastructure, budget)

3. **Write National Context Paragraph (90-120 min)**
   - Draft 10-15 sentences connecting policy to practice
   - Include minimum 5 APA in-text citations
   - Structure: national mandates → current practices → gaps/opportunities
   - Connect policies to why SecureCAT is needed at ISPSC

4. **Compile Draft References (30 min)**
   - Create APA 7 references for all 5+ sources
   - Save for CC-01

**Deliverable:** One 10-15 sentence paragraph with 5+ citations (2022-2026), draft APA references

---

#### C1-04: Background P4 — Local Context (ISPSC Tagudin) (Christine)
**Effort:** 3-5 hours  
**Due:** June 5  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Document ISPSC Tagudin Context (60 min)**
   - Leverage first-hand knowledge as enrolled student
   - Identify: Guidance Office processes, Registrar workflows, current admission testing practices
   - Note infrastructure realities (internet connectivity, computer availability)

2. **Research Comparable Regional Institutions (60 min)**
   - Search: "Ilocos Region university admission systems", "Philippine SUC guidance office automation"
   - Filter: 2022-2026, regional studies if available
   - Target: 3-5 sources on similar institutions or regional ICT in education

3. **Write Local Context Paragraph (90-120 min)**
   - Draft 10-15 sentences describing ISPSC Tagudin environment
   - Include minimum 5 citations (mix of local/regional sources)
   - Structure: ISPSC context → current manual processes → operational constraints → precedent from comparable institutions
   - Be specific about Guidance and Registrar workflows

4. **Compile Draft References (30 min)**
   - Create APA 7 references for all sources
   - Save for CC-01

**Deliverable:** One 10-15 sentence paragraph with 5+ citations, specific to ISPSC Tagudin

---

#### C1-05: Background P5 — Synthesis & Gap Identification (David)
**Effort:** 3-4 hours  
**Due:** June 6  
**Dependencies:** C1-02, C1-03, C1-04  

**Detailed Subtasks:**
1. **Review All Background Paragraphs (30 min)**
   - Read P2 (global), P3 (national), P4 (local)
   - Identify recurring themes across contexts
   - Note contrasts: global tech exists, but local implementation gaps

2. **Synthesize Findings (60-90 min)**
   - Group ideas: global X + national Y + local Z → reveals gap
   - Identify what NO existing study/system addresses
   - Distinguish existing solutions from SecureCAT's unique contribution
   - Key gap to identify: role-based, multi-office admission testing coordination with offline-first PWA architecture

3. **Write Synthesis Paragraph (60-90 min)**
   - Draft 10-15 sentences synthesizing (NOT summarizing)
   - Structure: grouped ideas → revealed gap → what SecureCAT does differently
   - DO NOT list authors — weave findings into themes

**Deliverable:** One 10-15 sentence synthesis paragraph explicitly naming the research gap

---

#### C1-06: Background P6 — Clinching Statement (David)
**Effort:** 2-3 hours  
**Due:** June 7  
**Dependencies:** C1-02, C1-03, C1-04  

**Detailed Subtasks:**
1. **Connect Literature to Study Design (30 min)**
   - Explain how reviewed literature (P2-P5) shaped study structure
   - Note which findings influenced system features

2. **State Rationale for Topic Selection (30 min)**
   - Include direct observation of problem at ISPSC Tagudin
   - Describe specific manual processes witnessed

3. **Write Clinching Paragraph (60-90 min)**
   - Draft 10-15 sentences with three required components:
     1. How literature structured the study
     2. Why topic was selected (direct observation)
     3. Why SecureCAT is the critical solution
   - Optional: Connect to SDG 4 (Quality Education) or SDG 9 (Industry/Innovation)

**Deliverable:** One 10-15 sentence clinching paragraph with all three required components

---

### Chapter 1 — Conceptual Framework

#### C1-07: Conceptual Framework — IPO Diagram (David)
**Effort:** 2-3 hours  
**Due:** June 3  
**Dependencies:** None  

**Detailed Subtasks:**
1. **List System Inputs (30-45 min)**
   - Use numbered list (not bullets)
   - Identify ONLY what system actually receives:
     - User-submitted test requests and forms
     - Test configuration parameters (test type, time limits, passing scores)
     - Student records and academic history
     - User role assignments (Guidance staff, Registrar staff, Administrator)
     - Test questions and answer keys

2. **Define Process (15-30 min)**
   - Process box text: "SecureCAT: A Role-Based College Admission Testing System"

3. **List System Outputs (30-45 min)**
   - Use numbered list (not bullets)
   - Identify ONLY what system produces:
     - Generated test schedules and room assignments
     - Scored test results with pass/fail determinations
     - Audit logs of all test-related activities
     - Role-based dashboards for Guidance and Registrar
     - Student performance reports

4. **Format Diagram (30-45 min)**
   - Create three-box diagram: Input → Process → Output
   - Ensure readable on standard page
   - Use simple formatting (boxes, arrows, numbered lists inside)

**Deliverable:** Clean, readable IPO diagram with Input (numbered) → Process → Output (numbered)

---

#### C1-08: Conceptual Framework — Narrative (David)
**Effort:** 2-3 hours  
**Due:** June 4  
**Dependencies:** C1-07  

**Detailed Subtasks:**
1. **Write Paragraph 1 — Inputs (45-60 min)**
   - Explain each input component from C1-07
   - Describe why each input is necessary for the system
   - Connect inputs to SecureCAT's role-based design

2. **Write Paragraph 2 — Process & Output (45-60 min)**
   - Explain how system processes inputs → outputs
   - Make mechanical connection explicit (transformation logic, role-based routing)
   - Describe each output and its purpose

3. **Review Against Diagram (15-30 min)**
   - Verify narrative matches IPO diagram exactly
   - Ensure no new inputs/outputs introduced in text

**Deliverable:** Two-paragraph narrative explaining inputs (P1) and process→outputs (P2)

---

#### C1-09: Objectives of the Study (David)
**Effort:** 2-3 hours  
**Due:** June 3  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Write General Objective (30-45 min)**
   - One paragraph naming SecureCAT and overarching purpose
   - Include: system name, target offices (Guidance & Registrar), main goal

2. **Write Specific Objectives (60-90 min)**
   - Use numbered list (not bullets)
   - Standard three-objective structure:
     1. Identify — existing processes, gaps, requirements at ISPSC Tagudin
     2. Develop — SecureCAT with specific features (name key features)
     3. Evaluate — usability using System Usability Scale (SUS)
   - **CRITICAL:** Use ONLY "evaluate the usability" (not "usability and acceptability")

3. **Verify Objective Alignment (15-30 min)**
   - Ensure objectives align with Chapter 2 methodology
   - Check that Objective 3 uses SUS terminology correctly

**Deliverable:** One general objective paragraph + 3-item numbered specific objectives list

---

#### C1-10: Research Questions (Jaypee)
**Effort:** 1-2 hours  
**Due:** June 4  
**Dependencies:** C1-09  

**Detailed Subtasks:**
1. **Review Objectives (15 min)**
   - Read C1-09 specific objectives
   - Each objective should have a corresponding research question

2. **Draft Research Questions (30-60 min)**
   - Convert objectives to question form (typically 3 questions)
   - Structure:
     - RQ1: What are the existing processes...?
     - RQ2: What features and functionalities...?
     - RQ3: What is the usability level of...?
   - Ensure questions align with objectives

3. **Refine Question Wording (15-30 min)**
   - Ensure questions are clear, specific, and answerable
   - Check alignment with methodology (e.g., RQ3 → SUS evaluation)

**Deliverable:** 3-item numbered research question list aligned with objectives

---

#### C1-11: Scope and Delimitations (David)
**Effort:** 2-3 hours  
**Due:** June 4  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Write Scope Paragraph (60-90 min)**
   - Paragraph form ONLY (no bullets/numbered lists)
   - Include:
     - Authorized user types (Guidance staff, Registrar staff, Administrator)
     - Modules included (test scheduling, test administration, scoring, reporting, audit logs)
     - Locale (ISPSC Tagudin, Ilocos Sur)
     - Timeframe (development period: [dates])
     - Principal variables (test data, student records, role assignments)
     - Justification for boundaries

2. **Write Limitations Paragraph (60-90 min)**
   - Paragraph form ONLY
   - Include:
     - What system does NOT do (no LMS integration, no payment processing, etc.)
     - Hardware/network dependencies (internet for PWA sync, device requirements)
     - Single-site constraint (ISPSC Tagudin only)
     - Manual processes remaining (test content creation, policy decisions)
     - Data privacy: name RA 10173, describe compliance

**Deliverable:** Two-paragraph scope + delimitations section, paragraph-form only

---

#### C1-12: Significance of the Study (Christine)
**Effort:** 2-3 hours  
**Due:** June 5  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Identify Beneficiary Groups (30 min)**
   - List direct beneficiaries:
     - ISPSC Tagudin Community (students, applicants)
     - Client Institution (ISPSC Tagudin)
     - Respondents (Guidance staff, Registrar staff)
     - BSIT College/Department
     - Student Researchers (team members)
     - Future Researchers

2. **Write One Paragraph Per Group (90-120 min)**
   - Each paragraph explains specific benefits to that group
   - DO NOT include generic "new knowledge" paragraph — weave contributions into each group
   - For ISPSC Community: improved service, faster results
   - For Client Institution: operational efficiency, reduced manual work
   - For Respondents: streamlined workflows, role-based access

3. **Review for Direct Beneficiaries Only (15-30 min)**
   - Ensure all groups are DIRECT beneficiaries
   - Remove vague or indirect beneficiaries

**Deliverable:** 6 paragraphs, one per beneficiary group, focused on direct benefits

---

### Chapter 2 Assignments — Literature Reviews

#### C2-01: Lit Review — RBAC + Zero-Trust Security (David)
**Effort:** 4-6 hours  
**Due:** June 5  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Research RBAC Literature (90 min)**
   - Search: "role-based access control education systems", "RBAC multi-tenant applications", "zero-trust security educational software"
   - Filter: 2022-2026, peer-reviewed
   - Target: 5-7 sources covering:
     - RBAC principles and implementations
     - Zero-trust security models
     - HMAC/authentication in web apps
     - Multi-tenant security architectures

2. **Extract Technical Findings (60 min)**
   - For each source: RBAC patterns, security practices, threat models
   - Group by theme: authorization, authentication, multi-tenancy

3. **Write Literature Review (90-120 min)**
   - Structure: thematic synthesis (not author-by-author)
   - Minimum 5 citations, all 2022-2026
   - Connect findings to SecureCAT's role-based design

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 3-4 paragraph literature review (thematic synthesis), 5+ citations

---

#### C2-02: Lit Review — OMR/CV Scoring + Philippine Admission Systems (Christine)
**Effort:** 4-6 hours  
**Due:** June 5  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Research Philippine Admission & Guidance Systems (90 min)**
   - Search: "Philippines college admission testing", "guidance office information systems Philippines", "automated testing Philippine universities"
   - Filter: 2022-2026, Philippine/regional context
   - Target: 5-7 sources covering:
     - Philippine admission testing practices
     - Guidance office automation in Philippines
     - OMR/computer vision scoring if applicable (or scoring automation broadly)

2. **Extract Local Context Findings (60 min)**
   - Identify: current practices, pain points, existing solutions
   - Note ISPSC-relevant patterns

3. **Write Literature Review (90-120 min)**
   - Focus on Philippine/local context
   - Thematic synthesis: national practices → local implementations → gaps
   - Minimum 5 citations, 2022-2026
   - Connect to SecureCAT's impact on Guidance & Registrar

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 3-4 paragraph literature review on Philippine admission/guidance systems

---

#### C2-03: Lit Review — AI/RAG in Education (David)
**Effort:** 4-6 hours  
**Due:** June 5  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Research AI in Education (90 min)**
   - Search: "AI companions education", "RAG knowledge bases", "LLM scheduling assistants", "AI educational chatbots"
   - Filter: 2022-2026, peer-reviewed
   - Target: 5-7 sources covering:
     - AI assistants in educational contexts
     - RAG (Retrieval-Augmented Generation) architectures
     - LLM-powered scheduling/knowledge systems

2. **Extract AI Architecture Findings (60 min)**
   - Identify: RAG patterns, embedding strategies, context management
   - Note: AI companion use cases in education

3. **Write Literature Review (90-120 min)**
   - Structure: AI in education → RAG architectures → SecureCAT's AI Companion
   - Minimum 5 citations, 2022-2026
   - Explain how SecureCAT's AI Companion (RAG + MixedBread) fits this literature

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 3-4 paragraph literature review on AI/RAG in education

---

#### C2-04: Lit Review — PWA + Offline-First Systems (David)
**Effort:** 4-6 hours  
**Due:** June 6  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Research PWA/Offline-First (90 min)**
   - Search: "progressive web apps education", "offline-first web applications", "PWA service worker strategies"
   - Filter: 2022-2026, peer-reviewed
   - Target: 5-7 sources covering:
     - PWA adoption patterns
     - Offline-first architectures
     - Service worker design patterns
     - PWA benefits for low-connectivity contexts

2. **Extract Technical Patterns (60 min)**
   - Identify: PWA features, sync strategies, connectivity handling
   - Note relevance to Philippine/ISPSC context

3. **Write Literature Review (90-120 min)**
   - Structure: PWA benefits → offline-first patterns → SecureCAT's PWA architecture
   - Minimum 5 citations, 2022-2026
   - Connect to SecureCAT's offline-first testing capability

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 3-4 paragraph literature review on PWA/offline-first systems

---

#### C2-05: Lit Review — DPA/Multi-Tenancy (David)
**Effort:** 3-5 hours  
**Due:** June 5  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Research Data Privacy & Multi-Tenancy (60 min)**
   - Search: "Data Privacy Act education Philippines", "multi-tenant database architecture", "tenant isolation SaaS", "RA 10173 implementation"
   - Filter: 2022-2026, peer-reviewed
   - Target: 4-6 sources covering:
     - RA 10173 (Data Privacy Act) in education
     - Multi-tenant database patterns
     - Tenant isolation strategies

2. **Extract Compliance & Architecture (45 min)**
   - Identify: DPA requirements for educational data
   - Note: multi-tenant architectural patterns

3. **Write Literature Review (60-90 min)**
   - Structure: DPA compliance → multi-tenant patterns → SecureCAT's architecture
   - Minimum 4 citations, 2022-2026
   - Explain how SecureCAT complies with RA 10173

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 2-3 paragraph literature review on DPA compliance + multi-tenancy

---

#### C2-06: Review of Related Systems (Jaypee)
**Effort:** 4-6 hours  
**Due:** June 6  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Identify Related Systems (60-90 min)**
   - Search for existing admission testing systems, guidance office software
   - Find 3-5 systems (commercial or academic)
   - Target: systems with comparable features (admission testing, role-based access, educational use)

2. **Create Comparison Table (60-90 min)**
   - Columns: System Name, Features, Technology, Limitations
   - Include SecureCAT in comparison
   - Identify what SecureCAT does differently

3. **Write Narrative (60-90 min)**
   - 2-3 paragraphs summarizing related systems
   - Highlight gap: no existing system combines PWA + role-based multi-office coordination + AI Companion
   - Include citations if sources exist for compared systems

**Deliverable:** Comparison table + 2-3 paragraph narrative summarizing related systems

---

#### C2-07: Technical Framework (David)
**Effort:** 3-4 hours  
**Due:** June 6  
**Dependencies:** None  

**Detailed Subtasks:**
1. **Define Technical Stack (45-60 min)**
   - Document: Laravel 12, Inertia v2, Svelte 5, Tailwind 4
   - Explain architectural decisions (why PWA, why offline-first, why multi-tenant)

2. **Describe System Architecture (60-90 min)**
   - Frontend: Svelte components, Tailwind styling
   - Backend: Laravel controllers, policies, middleware
   - Database: Multi-tenant schema, tenant isolation
   - PWA: Service worker, offline-first sync
   - AI: MixedBread embeddings, RAG pattern

3. **Write Technical Framework (60-90 min)**
   - 2-3 paragraphs explaining architecture
   - Include diagram if possible (system architecture figure)
   - Reference specific features in SYSTEM_FEATURES.md

**Deliverable:** 2-3 paragraph technical framework description + optional architecture diagram

---

#### C2-08: Conceptual Framework Prose — Chapter 2 Expansion (David)
**Effort:** 2-3 hours  
**Due:** June 7  
**Dependencies:** C1-07, C1-08  

**Detailed Subtasks:**
1. **Review Chapter 1 Conceptual Framework (30 min)**
   - Read C1-07 (IPO diagram) and C1-08 (narrative)
   - Ensure Chapter 2 prose is consistent but expanded

2. **Write Expanded Framework Narrative (60-90 min)**
   - 2-3 paragraphs explaining framework in more depth
   - Connect framework to research design and methodology
   - Explain how framework guides system development

3. **Align with Methodology (15-30 min)**
   - Ensure framework aligns with Chapter 2 methodology sections

**Deliverable:** 2-3 paragraph expanded conceptual framework narrative for Chapter 2

---

### Cross-Cutting Tasks

#### CC-01: References List Compilation (Christine)
**Effort:** 3-4 hours  
**Due:** June 8  
**Dependencies:** All writing tasks  

**Detailed Subtasks:**
1. **Collect All References from Team (60 min)**
   - Gather draft references from all task owners
   - Compile into master list

2. **Format All References in APA 7 (90-120 min)**
   - Ensure alphabetical order by first author surname
   - Check hanging indent format
   - Verify: Author, A. A. (Year). Title. *Source*, vol(issue), pages. DOI/URL

3. **Cross-Check Citations (30-60 min)**
   - Verify every in-text citation has a reference entry
   - Verify every reference entry is cited in text
   - Flag any 2022-2026 violations (sources outside range)

**Deliverable:** Complete, alphabetized APA 7 References list, cross-checked against all citations

---

#### CC-02: Formatting QA Review (Jaypee)
**Effort:** 2-3 hours  
**Due:** June 8  
**Dependencies:** All writing tasks  

**Detailed Subtasks:**
1. **Review Formatting Guide (30 min)**
   - Read GUIDE-1-FORMATTING.md thoroughly
   - Note: margins (1"), fonts (Times New Roman 12), spacing (double), page numbers

2. **Check All Formatting Rules (60-90 min)**
   - Margins: 1" all sides
   - Font: Times New Roman, 12pt throughout
   - Spacing: Double-spaced, no extra spaces between paragraphs
   - Headings: Correct level hierarchy (Level 1, Level 2, Level 3)
   - Tables/figures: 1pt borders, captions above/below correctly placed
   - Page numbers: Top-right, continuous

3. **Create Formatting Checklist (15-30 min)**
   - Mark which formatting rules passed/failed
   - List corrections needed

**Deliverable:** Formatting QA report with checklist of violations/corrections

---

#### CC-03: Narrative Consistency Review (David)
**Effort:** 4-6 hours  
**Due:** June 9  
**Dependencies:** All writing tasks  

**Detailed Subtasks:**
1. **Read All Chapters End-to-End (60-90 min)**
   - Chapter 1: Background → Framework → Objectives → Scope → Significance
   - Chapter 2: Research Design → Software Model → Project Plan → Assignments → Population → Instruments → Analysis

2. **Check Consistency Across Sections (90-120 min)**
   - Background problem aligns with objectives
   - Objectives align with research questions
   - Framework aligns with methodology
   - Scope aligns with features described
   - Citations are consistent (author/year matches references)

3. **Fix Inconsistencies (60-90 min)**
   - Edit text to ensure narrative flow
   - Align terminology (e.g., "SecureCAT" used consistently)
   - Ensure logical progression from problem → solution → evaluation

**Deliverable:** Cleaned manuscript with consistent narrative, terminology, and logical flow

---

#### CC-04: Citation Cross-Check (Jaypee)
**Effort:** 1-2 hours  
**Due:** June 8  
**Dependencies:** All writing tasks, CC-01  

**Detailed Subtasks:**
1. **Extract All In-Text Citations (30-45 min)**
   - Scan document for all (Author, Year) citations
   - List each citation with location

2. **Verify Against References (30-45 min)**
   - Check each citation has matching reference entry
   - Check each reference entry is cited in text
   - Flag mismatches

3. **Create Cross-Check Report (15-30 min)**
   - List missing citations
   - List orphaned references
   - List citation/reference mismatches

**Deliverable:** Citation cross-check report with mismatches flagged

---

## Timeline Overview

### Week 1: June 1-3 (Foundation)
- **Focus:** Framework, objectives, early background
- David: C1-07, C1-09 (IPO, Objectives)
- Jaypee: C1-01 (Core Problem)
- Christine: C1-12 draft (Significance)

### Week 1: June 4-5 (Background + Literature)
- **Focus:** Background paragraphs, literature reviews
- Jaypee: C1-02, C1-03, C1-10 (Global/National, RQs)
- Christine: C1-04, C2-02 (Local Context, OMR/Philippine systems)
- David: C1-08, C1-11, C2-01, C2-03, C2-05 (Narrative, Scope, Lit reviews)

### Week 2: June 6-7 (Synthesis + Framework)
- **Focus:** Background synthesis, conceptual framework, technical framework
- David: C1-05, C1-06, C2-07, C2-08 (Synthesis, Clinching, Frameworks)
- Jaypee: C2-06 (Related Systems)

### Week 2: June 8 (QA & References)
- **Focus:** References, formatting, citations
- Christine: CC-01 (References)
- Jaypee: CC-02, CC-04 (Formatting, Citations)

### Week 2: June 9 (Final Review)
- **Focus:** Narrative consistency
- David: CC-03 (Consistency review)

### Deadline: June 10
- **Final submission**

---

## Communication & Coordination

### Daily Check-ins (Brief)
- Each member posts progress in team Discord/group
- Flag blockers immediately
- Share draft references as they're created

### Milestone Reviews
- **June 3:** Framework & Objectives complete (David)
- **June 5:** Background paragraphs complete (Jaypee/Christine)
- **June 7:** Literature reviews complete (David/Christine)
- **June 8:** QA tasks begin (Christine/Jaypee)
- **June 9:** Final review (David)

### File Sharing
- All drafts stored in: `capstone/drafts/chapter1_2/`
- Naming convention: `[TaskID]_[Member]_Draft.md`
- Example: `C1-02_Jaypee_Draft.md`

---

## Risk Mitigation

| Risk | Mitigation | Owner |
|------|------------|-------|
| Jaypee unclear on task directions | Create detailed per-member DIRECTION.md files | David |
| Christine overloaded (ISPSC enrollment) | Keep her focused on local context tasks | David |
| Literature search returns few 2022-2026 sources | Use broader search terms, supplement with earlier sources if justified | All |
| References compilation becomes chaotic | All members submit draft references with their tasks | Christine |
| Formatting violations discovered late | Jaypee runs formatting QA early (June 6) | Jaypee |

---

## Success Criteria

- ✅ All 21 tasks assigned with clear deadlines
- ✅ All tasks have detailed subtasks (4-6 steps each)
- ✅ Workload balanced across team members
- ✅ Dependencies mapped and respected
- ✅ Clear deliverables defined for each task
- ✅ Communication plan established
- ✅ Risk mitigation strategies defined

---

**Next Steps:**
1. ✅ Create per-member DIRECTION.md files (with detailed task directions)
2. ✅ Create interactive Gantt chart HTML
3. Share this plan with team members
4. Begin Week 1 tasks immediately
