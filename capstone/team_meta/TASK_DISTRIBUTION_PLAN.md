# Chapter 1 & 2 Task Distribution Plan
## SecureCAT-v2 Capstone | Deadline: June 10, 2026

> **Last Updated:** June 2, 2026
> **Status:** Final Assignment Distribution (Post-Reassignment)

---

## Team Members & Roles

| Member | Role | Email/Discord | Claimed Hours | Available Capacity |
|--------|------|--------------|---------------|-------------------|
| **David** | Team Leader / Product Owner / Lead Developer | daviddatu_ | 45-55h | ~55h total |
| **Christine** | Team Member — OMR/CV Literature & Significance | Christine | 6-9h | TBD |
| **Jaypee** | Team Member — Local Context, Systems, Research & References | Jaypee | 20-30h | TBD |

---

## Assignment Strategy

**Distribution Principles:**
1. **David** — Technical architecture, background paragraphs (P1-P3), literature reviews (RBAC, AI, PWA, DPA), framework tasks, narrative consistency, formatting QA
2. **Christine** — Automated scoring/OMR literature review, significance of the study
3. **Jaypee** — Local context (ISPSC), research questions, related systems review, references compilation, citation cross-check

**June 2 Reassignment:**
- Jaypee prefers focused, detail-oriented tasks (systems review, references, citations)
- Jaypee takes: C1-04 (Local Context), C1-10 (Research Qs), C2-06 (Related Systems), CC-01 (References), CC-04 (Citation Cross-Check)
- Jaypee gives up: C1-01, C1-02, C1-03 (background paragraphs), CC-02 (Formatting QA) → moved to David
- Christine gives up: C1-04 (Local Context), CC-01 (References) → moved to Jaypee
- David takes on: C1-01, C1-02, C1-03, CC-02 to balance workload

---

## Complete Task Assignment Matrix

### Chapter 1 Assignments

| Task ID | Task Name | Assigned | Est. Hours | Deadline | Dependencies |
|---------|-----------|----------|------------|----------|--------------|
| C1-01 | Background P1 — Core Problem Statement | **David** | 2-3h | Jun 4 | None |
| C1-02 | Background P2 — Global Context | **David** | 4-6h | Jun 5 | None |
| C1-03 | Background P3 — National Context (PH) | **David** | 4-6h | Jun 5 | None |
| C1-04 | Background P4 — Local Context (ISPSC) | **Jaypee** | 3-5h | Jun 5 | None |
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
| C2-02 | Lit Review — Automated Scoring & OMR Technologies | **Christine** | 4-6h | Jun 5 | None |
| C2-03 | Lit Review — AI/RAG in Education | **David** | 4-6h | Jun 5 | None |
| C2-04 | Lit Review — PWA/Offline Systems | **David** | 4-6h | Jun 6 | None |
| C2-05 | Lit Review — DPA/Multi-Tenancy | **David** | 3-5h | Jun 5 | None |
| C2-06 | Review of Related Systems | **Jaypee** | 4-6h | Jun 6 | None |
| C2-07 | Technical Framework | **David** | 3-4h | Jun 6 | None |
| C2-08 | Conceptual Framework Prose (Ch2) | **David** | 2-3h | Jun 7 | C1-07, C1-08 |

### Cross-Cutting Assignments

| Task ID | Task Name | Assigned | Est. Hours | Deadline | Dependencies |
|---------|-----------|----------|------------|----------|--------------|
| CC-01 | References List Compilation | **Jaypee** | 3-4h | Jun 8 | All writing tasks |
| CC-02 | Formatting QA Review | **David** | 2-3h | Jun 8 | All writing tasks |
| CC-03 | Narrative Consistency Review | **David** | 4-6h | Jun 9 | All writing tasks |
| CC-04 | Citation Cross-Check | **Jaypee** | 1-2h | Jun 8 | All writing tasks |

---

## Workload Summary

| Member | Total Tasks | Total Hours | Utilization | Status |
|--------|-------------|-------------|--------------|--------|
| **David** | 15 tasks | 45-55h | 82-100% of 55h | ✅ Full capacity |
| **Christine** | 2 tasks | 6-9h | TBD capacity | ✅ Focused |
| **Jaypee** | 5 tasks | 14-22h | TBD capacity | ✅ Balanced |

**Total Project Effort:** ~65-86 hours across all members

**Summary of June 2 Reassignment:**
- **David** adds C1-01, C1-02, C1-03 (background paragraphs), CC-02 (Formatting QA) → 15 tasks, 45-55h
- **Christine** keeps C1-12 (Significance), C2-02 (OMR/CV Review) → 2 tasks, 6-9h
- **Jaypee** keeps C1-10 (Research Qs), C2-06 (Related Systems), CC-04 (Citation Cross-Check); takes C1-04 (Local Context), CC-01 (References) → 5 tasks, 14-22h

---

## Detailed Task Breakdowns

### Chapter 1 — Background of the Study

#### C1-01: Background P1 — Core Problem Statement (David)
**Effort:** 2-3 hours
**Due:** June 4
**Dependencies:** None
**Target Length:** 8-12 sentences (per TEAM_META_GUIDE)

**Detailed Subtasks:**
1. **Identify Core Problem (30 min)**
   - Read SecureCAT system description in SYSTEM_FEATURES.md
   - Identify the exact technical problem: manual admission testing with no role-based coordination between Guidance and Registrar
   - List observable symptoms: paper-based test routing, manual scoring, lack of audit trails, fragmented scoring, absence of cryptographically-secured platform

2. **Write Problem Paragraph (60-90 min)**
   - Draft 8-12 sentences in own words (no citations)
   - Structure: symptoms → technical root cause → why current systems fail
   - Start with observable symptoms (manual admission workflows, fragmented scoring, paper-based OMR, lack of audit trails), then pivot to the technical root cause (absence of a unified, cryptographically-secured, role-based digital platform)
   - Key terms to include: "manual admission testing", "role-based access control", "Guidance Office", "Registrar", "test security", "audit trail"
   - DO NOT sound like public administration — keep it technical/IT-focused

3. **Review & Refine (30 min)**
   - Check paragraph has 8-12 sentences
   - Verify no citations (P1 is citation-free per TEAM_META_GUIDE restrictions)
   - Ensure IT/system framing (not management/admin framing)
   - Confirm no bullet points, no bold body text

**Deliverable:** One 8-12 sentence paragraph, citation-free, clearly naming the technical gap

---

#### C1-02: Background P2 — Global Context (David)
**Effort:** 4-6 hours
**Due:** June 5
**Dependencies:** None
**Target Length:** 12-15 sentences (per TEAM_META_GUIDE)

**Detailed Subtasks:**
1. **Research Global Admission Testing (90 min)**
   - Search Google Scholar for: "admission testing automation", "computer-based testing systems", "educational assessment software"
   - Filter: 2022-2026, peer-reviewed journals
   - Target: 5-7 sources covering:
     - Digital transformation of higher education admissions
     - Automated testing and scoring platforms
     - RBAC in educational systems
     - Computer-vision-based OMR scanning
     - AI-assisted administrative operations
     - Offline-first architectures
     - Zero-trust security models

2. **Extract Key Findings (60 min)**
   - For each source, extract: main finding, methodology, relevance to SecureCAT
   - Group findings by theme: efficiency, security, coordination
   - Note citation details (author, year, journal, DOI/URL)

3. **Write Global Context Paragraph (90-120 min)**
   - Draft 12-15 sentences synthesizing global patterns
   - Include minimum 5 APA in-text citations (Author, Year)
   - Structure: global trends → efficiency evidence → security practices → adoption patterns
   - DO NOT list authors one-by-one — synthesize by theme
   - FORBIDDEN pattern: "Author A says X. Author B says Y." — use synthesis instead

4. **Compile Draft References (30 min)**
   - Create APA 7 references for all 5+ sources
   - Save for CC-01 (References Compilation)

**Deliverable:** One 12-15 sentence paragraph with minimum 5 citations (all 2022-2026), draft APA reference entries

---

#### C1-03: Background P3 — National Context — Philippines (David)
**Effort:** 4-6 hours
**Due:** June 5
**Dependencies:** None
**Target Length:** 12-15 sentences (per TEAM_META_GUIDE)

**Detailed Subtasks:**
1. **Research Philippine Education & Admission Systems (90 min)**
   - Search: "Philippines college admission automation", "CHED admission policies", "state universities ICT systems"
   - Filter: 2022-2026, CHED/DPA/DepEd/SUC sources
   - Target: 5-7 sources covering:
     - CHED policies on digital transformation
     - RA 10173 (Data Privacy Act of 2012) compliance for student data
     - Digitalization efforts in SUCs
     - Government e-governance initiatives
     - Connectivity challenges in Philippine higher education

2. **Extract Policy & Implementation Details (60 min)**
   - Identify specific mandates (e.g., CHED memoranda, RA citations)
   - Note agency directives relevant to admission processes
   - Document Philippine context constraints (infrastructure, budget)
   - Must name specific legislation, agencies, or programs (no vague references)

3. **Write National Context Paragraph (90-120 min)**
   - Draft 12-15 sentences connecting policy to practice
   - Include minimum 5 APA in-text citations
   - Structure: national mandates → current practices → gaps/opportunities
   - Connect policies to why SecureCAT is needed at ISPSC

4. **Compile Draft References (30 min)**
   - Create APA 7 references for all 5+ sources
   - Save for CC-01

**Deliverable:** One 12-15 sentence paragraph with minimum 5 citations (all 2022-2026), draft APA references

---

#### C1-04: Background P4 — Local Context — ISPSC Tagudin (Jaypee)
**Effort:** 3-5 hours
**Due:** June 5
**Dependencies:** None
**Target Length:** 12-15 sentences (per TEAM_META_GUIDE)

**Detailed Subtasks:**
1. **Document ISPSC Tagudin Context (60 min)**
   - Coordinate with Christine (enrolled student) for first-hand knowledge
   - Identify: Guidance Office processes, Registrar workflows, current admission testing practices
   - Note infrastructure realities (internet connectivity, computer availability, WiFi reliability, limited IT staff, computer labs)
   - Document operational challenges during peak admission periods
   - Note compliance pressures specific to the institution

2. **Research Comparable Regional Institutions (60 min)**
   - Search: "Ilocos Region university admission systems", "Philippine SUC guidance office automation"
   - Filter: 2022-2026, regional studies if available
   - Target: 5-7 sources on similar institutions or regional ICT in education
   - If direct ISPSC studies unavailable, use comparable SUCs

3. **Write Local Context Paragraph (90-120 min)**
   - Draft 12-15 sentences describing ISPSC Tagudin environment
   - Include minimum 5 citations (mix of local/regional sources)
   - Structure: ISPSC context → current manual processes → operational constraints → precedent from comparable institutions
   - Be specific about Guidance and Registrar workflows
   - Describe actual operational environment

4. **Compile Draft References (30 min)**
   - Create APA 7 references for all sources
   - Save for CC-01 (you will compile final list)

**Deliverable:** One 12-15 sentence paragraph with minimum 5 citations (all 2022-2026), specific to ISPSC Tagudin

---

#### C1-05: Background P5 — Synthesis & Gap Identification (David)
**Effort:** 3-4 hours
**Due:** June 6
**Dependencies:** C1-02, C1-03, C1-04
**Target Length:** 10-12 sentences (per TEAM_META_GUIDE)

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
   - Show what existing systems do vs. what SecureCAT introduces
   - Use synthesis patterns: "While [finding A], [contrasting finding B]..."

3. **Write Synthesis Paragraph (60-90 min)**
   - Draft 10-12 sentences synthesizing (NOT summarizing)
   - Structure: grouped ideas → revealed gap → what SecureCAT does differently
   - DO NOT list authors — weave findings into themes
   - FORBIDDEN to list authors one by one
   - Must explicitly name the research gap in 2-3 sentences

**Deliverable:** One 10-12 sentence synthesis paragraph explicitly naming the research gap

---

#### C1-06: Background P6 — Clinching Statement (David)
**Effort:** 2-3 hours
**Due:** June 7
**Dependencies:** C1-02 through C1-05
**Target Length:** 8-10 sentences, 3 explicit components (per TEAM_META_GUIDE and GUIDE-2)

**Detailed Subtasks:**
1. **Component 1: How Literature Structured the Study (30 min)**
   - Explain how the reviewed literature (P2-P5) assisted in structuring the present study
   - Note which findings influenced system features and research design decisions

2. **Component 2: Why This Topic Was Selected (30 min)**
   - State why you selected this research topic
   - Must include direct observation of the problem at ISPSC Tagudin
   - Describe specific manual processes witnessed firsthand

3. **Component 3: Why SecureCAT Is the Critical Solution (30 min)**
   - Conclude by highlighting why the proposed system is the critical solution to the identified gap
   - Connect the system's capabilities to the problem documented in P1

4. **Write Clinching Paragraph (30-60 min)**
   - Draft 8-10 sentences incorporating all three components above
   - Optional but recommended: connect to SDG 4 (Quality Education) or SDG 16 (Peace, Justice and Strong Institutions)
   - Ensure all three components are explicitly present and identifiable

**Deliverable:** One 8-10 sentence clinching paragraph with 3 explicit components: (1) how literature structured the study, (2) why topic was selected with direct observation, (3) why SecureCAT is the critical solution

---

### Chapter 1 — Conceptual Framework

#### C1-07: Conceptual Framework — IPO Diagram (David)
**Effort:** 2-3 hours
**Due:** June 3
**Dependencies:** Must reference SYSTEM_FEATURES.md and drafts/Existing_and_Planned_Features.md

**Detailed Subtasks:**
1. **List System Inputs (30-45 min)**
   - Use numbered list (not bullets)
   - Identify ONLY what system actually receives (data/config the system receives):
     1. Applicant data
     2. Exam configurations
     3. OMR images/scans
     4. Role credentials
     5. QR scans
     6. Natural language queries
   - Reference SYSTEM_FEATURES.md and TEAM_META_GUIDE C1-07 description exactly

2. **Define Process (15-30 min)**
   - Process box text: "SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin" (full system title)

3. **List System Outputs (30-45 min)**
   - Use numbered list (not bullets)
   - Identify ONLY what system produces (things the system produces):
     1. Status tracking displays
     2. Exam schedules
     3. Score reports
     4. Audit logs
     5. Result sheets/PDFs
     6. Consultation summaries
     7. Copilot responses
     8. Offline-cached records
     9. Statistical reports
   - Reference SYSTEM_FEATURES.md and TEAM_META_GUIDE C1-07 description exactly

4. **Format Diagram (30-45 min)**
   - Create three-box diagram: Input → Process → Output
   - Ensure readable on standard page
   - Use simple formatting (boxes, arrows, numbered lists inside)
   - Figure caption placed BELOW the figure, bold

**Deliverable:** Clean, readable IPO diagram with Input (numbered, 6 items) → Process (full system title) → Output (numbered, 9 items)

---

#### C1-08: Conceptual Framework — Narrative (David)
**Effort:** 2-3 hours
**Due:** June 4
**Dependencies:** C1-07

**Detailed Subtasks:**
1. **Write Paragraph 1 — Inputs (45-60 min)**
   - Explain what each input component from C1-07 is and why it is necessary
   - Cover all 6 inputs: applicant data, exam configurations, OMR images/scans, role credentials, QR scans, natural language queries
   - Connect inputs to SecureCAT's role-based design

2. **Write Paragraph 2 — Process & Output (45-60 min)**
   - Explain how inputs are transformed into outputs through:
     - Role-based access control
     - Automated scoring
     - Offline-resilient proctoring
     - AI-assisted operations
     - Cryptographic verification
   - Make the mechanical connection explicit — not just "inputs go in and outputs come out" but how the transformation happens
   - Describe each output and its purpose
   - Present both existing and planned features as one unified process

3. **Review Against Diagram (15-30 min)**
   - Verify narrative matches IPO diagram exactly
   - Ensure no new inputs/outputs introduced in text that are not in the diagram

**Deliverable:** Exactly 2 paragraphs explaining inputs (P1) and process→outputs (P2)

---

### Chapter 1 — Objectives & Research Questions

#### C1-09: Objectives of the Study (David)
**Effort:** 2-3 hours
**Due:** June 3
**Dependencies:** None

**Detailed Subtasks:**
1. **Write General Objective (30-45 min)**
   - One paragraph naming SecureCAT by full title and overarching purpose
   - Include: system name, target offices (Guidance & Registrar), main goal
   - General objective must name system by full title

2. **Write Specific Objectives (60-90 min)**
   - Use numbered list (not bullets)
   - Standard three-objective structure:
     1. Identify — existing processes, gaps, requirements at ISPSC Tagudin
     2. Develop — SecureCAT with specific features (name key features from both existing and planned)
     3. Evaluate — usability using System Usability Scale (SUS)
   - **CRITICAL:** Use ONLY "evaluate the usability" (not "usability and acceptability") — SUS measures usability only

3. **Verify Objective Alignment (15-30 min)**
   - Ensure objectives align with Chapter 2 methodology
   - Check that Objective 3 uses SUS terminology correctly
   - Verify general objective names system by full title

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
   - Questions must cover these four dimensions (per TEAM_META_GUIDE):
     - **Operational needs:** What are the existing processes, gaps, and requirements?
     - **Role-based access needs:** How should role-based access be designed for multi-office coordination?
     - **Security needs:** How can data integrity and cryptographic verification be ensured?
     - **Operational resilience needs:** How can the system operate reliably under connectivity constraints?
   - Must correspond one-to-one with the specific objectives

3. **Refine Question Wording (15-30 min)**
   - Ensure questions are clear, specific, and answerable
   - Check alignment with methodology (e.g., RQ3 → SUS evaluation)

**Deliverable:** Numbered list of research questions covering operational needs, role-based access needs, security needs, and operational resilience needs

---

### Chapter 1 — Scope & Significance

#### C1-11: Scope and Delimitations (David)
**Effort:** 2-3 hours
**Due:** June 4
**Dependencies:** Must reference SYSTEM_FEATURES.md

**Detailed Subtasks:**
1. **Write Scope Paragraph (60-90 min)**
   - Paragraph form ONLY (no bullets, no numbered lists per GUIDE-1)
   - Must cover BOTH existing system modules AND planned research modules:
     - **Existing system modules:** application intake, scheduling, roster, grading, OMR CSV import, consultation summaries, result release, document generation, audit logs, notifications, AI companion, AI scheduling assistant
     - **Planned research modules:** HMAC integrity, CV-based OMR, offline PWA, RAG copilot, auto-scheduling agent, multi-tenant architecture
   - Include: authorized user types, locale (ISPSC Tagudin, Ilocos Sur), timeframe, principal variables
   - Justification for boundaries
   - Include explicit delimitations that allow advanced features to appear as research contributions rather than out-of-scope expansion

2. **Write Delimitations Paragraph (60-90 min)**
   - Paragraph form ONLY
   - Include:
     - What system does NOT do (no LMS integration, no payment processing, etc.)
     - Hardware/network dependencies (internet for PWA sync, device requirements)
     - Single-site constraint (ISPSC Tagudin only)
     - Manual processes remaining (test content creation, policy decisions)
     - Data privacy: name RA 10173 (Data Privacy Act of 2012), describe compliance

**Deliverable:** Two-paragraph scope + delimitations section, paragraph-form only, covering both existing and planned features

---

#### C1-12: Significance of the Study (Christine)
**Effort:** 2-3 hours
**Due:** June 5
**Dependencies:** None

**Detailed Subtasks:**
1. **Identify Beneficiary Groups (30 min)**
   - List direct beneficiaries per TEAM_META_GUIDE (NOT generic groups):
     1. Registrar Office staff
     2. Guidance Office counselors
     3. Proctors / Test Administrators
     4. Applicants / Examinees
     5. ISPSC Administration
     6. Future Researchers
   - DO NOT use generic groups like "The Community", "The College/Department", "The Students (Researchers)"
   - These must be system-specific beneficiaries

2. **Write One Paragraph Per Group (90-120 min)**
   - Each paragraph explains specific benefits to that group
   - DO NOT include generic "new knowledge" paragraph — weave contributions into each group
   - For Registrar Office: streamlined workflows, role-based access, audit trails, operational efficiency
   - For Guidance Office: consultation summaries, AI copilot, reduced manual cognitive load
   - For Proctors/Test Administrators: offline-resilient scanning, roster management, QR-based attendance
   - For Applicants/Examinees: improved service, faster results, transparent status tracking
   - For ISPSC Administration: institutional reporting value, DPA compliance, operational continuity
   - For Future Researchers: baseline/reference for related future work
   - Highlight DPA compliance, operational continuity, institutional reporting value throughout

3. **Review for Direct Beneficiaries Only (15-30 min)**
   - Ensure all groups are DIRECT beneficiaries of the system
   - Remove vague or indirect beneficiaries
   - Verify no bullet points used (paragraph form only)

**Deliverable:** 6 paragraphs, one per beneficiary group (Registrar Office staff, Guidance Office counselors, Proctors/Test Administrators, Applicants/Examinees, ISPSC Administration, Future Researchers), focused on direct benefits

---

### Chapter 2 — Literature Reviews

#### C2-01: Lit Review — RBAC and Zero-Trust Security (David)
**Effort:** 4-6 hours
**Due:** June 5
**Dependencies:** None

**Detailed Subtasks:**
1. **Research RBAC & Zero-Trust Literature (90 min)**
   - Search: "role-based access control education systems", "RBAC multi-tenant applications", "zero-trust security educational software", "HMAC authentication web applications"
   - Filter: 2022-2026, peer-reviewed
   - Target: 5-7 sources covering:
     - RBAC principles and implementations
     - Zero-trust security models
     - Cryptographic data integrity mechanisms (HMAC)
     - Multi-tenant security architectures
     - Security in educational or assessment platforms

2. **Extract Technical Findings (60 min)**
   - For each source: RBAC patterns, security practices, threat models
   - Group by theme: authorization, authentication, multi-tenancy

3. **Write Literature Review (90-120 min)**
   - 1-2 paragraphs, thematic synthesis (not author-by-author)
   - Minimum 5 APA citations, all 2022-2026
   - Connect findings to SecureCAT's role-based design and HMAC integrity model
   - Synthesized writing only — FORBIDDEN pattern: "Author A says X. Author B says Y."

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 1-2 paragraph literature review on RBAC and zero-trust security, minimum 5 APA citations (all 2022-2026)

---

#### C2-02: Lit Review — Automated Scoring and OMR Technologies (Christine)
**Effort:** 4-6 hours
**Due:** June 5
**Dependencies:** None

**Detailed Subtasks:**
1. **Research Automated Scoring & OMR Literature (90 min)**
   - Search: "automated test scoring methods", "optical mark recognition OMR", "computer vision answer sheet processing", "automated vs manual scoring accuracy", "image-based OMR grading"
   - Filter: 2022-2026, peer-reviewed
   - Target: 5-7 sources covering:
     - Automated test scoring methods
     - Optical mark recognition (OMR) technologies
     - Computer vision-based answer sheet processing
     - Accuracy comparisons between manual and automated scoring
     - Bubble detection and answer extraction techniques

2. **Extract Technical Findings (60 min)**
   - For each source: scoring methodology, accuracy metrics, OMR/CV techniques
   - Group by theme: scoring automation, accuracy, computer vision approaches
   - Note relevance to SecureCAT's planned CV-based OMR feature

3. **Write Literature Review (90-120 min)**
   - 1-2 paragraphs, thematic synthesis
   - Focus on automated scoring methods, OMR technologies, computer vision-based answer sheet processing, and accuracy comparisons between manual and automated scoring
   - Minimum 5 APA citations, all 2022-2026
   - Connect to SecureCAT's planned CV-based OMR answer sheet ingestion
   - Synthesized writing only — do not list authors one-by-one

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 1-2 paragraph literature review on automated scoring and OMR technologies, minimum 5 APA citations (all 2022-2026)

---

#### C2-03: Lit Review — AI Assistants and RAG in Education (David)
**Effort:** 4-6 hours
**Due:** June 5
**Dependencies:** None

**Detailed Subtasks:**
1. **Research AI in Education (90 min)**
   - Search: "AI companions education", "RAG knowledge bases", "LLM scheduling assistants", "AI educational chatbots", "natural language interfaces database querying"
   - Filter: 2022-2026, peer-reviewed
   - Target: 5-7 sources covering:
     - AI-powered chatbots and assistants in educational administration
     - Retrieval-augmented generation (RAG) architectures
     - Natural language interfaces for database querying in academic settings
     - Vector embeddings for knowledge retrieval

2. **Extract AI Architecture Findings (60 min)**
   - Identify: RAG patterns, embedding strategies, context management
   - Note: AI companion use cases in education
   - Group by theme: AI assistants, RAG architectures, natural language querying

3. **Write Literature Review (90-120 min)**
   - 1-2 paragraphs, thematic synthesis
   - Structure: AI in education → RAG architectures → natural language querying
   - Minimum 5 APA citations, all 2022-2026
   - Explain how SecureCAT's RAG Copilot (vector embeddings + natural language querying) fits this literature
   - Synthesized writing only

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 1-2 paragraph literature review on AI assistants and RAG in education, minimum 5 APA citations (all 2022-2026)

---

#### C2-04: Lit Review — Offline-Resilient and PWA Systems (David)
**Effort:** 4-6 hours
**Due:** June 6
**Dependencies:** None

**Detailed Subtasks:**
1. **Research PWA/Offline-First (90 min)**
   - Search: "progressive web apps education", "offline-first web applications", "PWA service worker strategies", "IndexedDB local caching", "background sync mechanisms"
   - Filter: 2022-2026, peer-reviewed
   - Target: 5-7 sources covering:
     - Progressive Web Apps (PWA)
     - Service workers
     - Offline-first architectures
     - IndexedDB for local caching
     - Background sync mechanisms in critical operational environments

2. **Extract Technical Patterns (60 min)**
   - Identify: PWA features, sync strategies, connectivity handling
   - Note relevance to Philippine/ISPSC context (low-connectivity environments)

3. **Write Literature Review (90-120 min)**
   - 1-2 paragraphs, thematic synthesis
   - Structure: PWA benefits → offline-first patterns → service worker strategies → SecureCAT's PWA architecture
   - Minimum 5 APA citations, all 2022-2026
   - Connect to SecureCAT's offline-resilient proctor portal

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 1-2 paragraph literature review on PWA/offline-first systems, minimum 5 APA citations (all 2022-2026)

---

#### C2-05: Lit Review — Philippine Data Privacy Act and Multi-Tenancy (David)
**Effort:** 3-5 hours
**Due:** June 5
**Dependencies:** None

**Detailed Subtasks:**
1. **Research Data Privacy & Multi-Tenancy (60 min)**
   - Search: "Data Privacy Act education Philippines", "RA 10173 compliance educational software", "multi-tenant database architecture", "tenant isolation SaaS", "data isolation strategies SUC"
   - Filter: 2022-2026, peer-reviewed
   - Target: 5-7 sources covering:
     - Philippine Data Privacy Act (RA 10173) compliance in educational software
     - Multi-tenant database architecture patterns
     - Data isolation strategies for SUC systems

2. **Extract Compliance & Architecture (45 min)**
   - Identify: DPA requirements for educational data
   - Note: multi-tenant architectural patterns and tenant isolation strategies
   - Group by theme: legal compliance, architecture patterns

3. **Write Literature Review (60-90 min)**
   - 1-2 paragraphs, thematic synthesis
   - Structure: DPA compliance → multi-tenant patterns → data isolation → SecureCAT's architecture
   - Minimum 5 APA citations, all 2022-2026
   - Explain how SecureCAT complies with RA 10173 and implements multi-tenant data isolation

4. **Compile Draft References (30 min)**
   - APA 7 references for all sources
   - Save for CC-01

**Deliverable:** 1-2 paragraph literature review on DPA compliance and multi-tenancy, minimum 5 APA citations (all 2022-2026)

---

### Chapter 2 — Related Systems & Frameworks

#### C2-06: Review of Related Systems (Jaypee)
**Effort:** 4-6 hours
**Due:** June 6
**Dependencies:** None

**Detailed Subtasks:**
1. **Identify Related Systems (60-90 min)**
   - Search for existing admission systems (local and international), electronic assessment platforms, OMR grading systems, and document generation systems in academic workflows
   - Find 3-5 systems (commercial or academic)
   - Target: systems with comparable features (admission testing, role-based access, educational use, OMR scoring)

2. **Create Comparison Table (60-90 min)**
   - Columns: System Name, Features, Technology, Limitations
   - Include SecureCAT in comparison
   - Identify what SecureCAT does differently
   - Table caption: left-aligned, above the table, bold (per GUIDE-1)
   - Border: 1pt line width

3. **Write Narrative (60-90 min)**
   - 2-3 paragraphs summarizing related systems with feature comparison
   - Highlight gap: no existing system combines PWA + role-based multi-office coordination + AI Companion + CV-based OMR + HMAC integrity
   - Include minimum 5 APA citations if sources exist for compared systems (all 2022-2026)

**Deliverable:** 2-3 paragraphs with feature comparison table, minimum 5 APA citations (all 2022-2026)

---

#### C2-07: Technical Framework (David)
**Effort:** 3-4 hours
**Due:** June 6
**Dependencies:** Must reference SYSTEM_FEATURES.md

**Detailed Subtasks:**
1. **Define Technical Stack (45-60 min)**
   - Document the full technology stack per TEAM_META_GUIDE:
     - Laravel 12 / Inertia v2 / Svelte 5 / Tailwind 4
   - HMAC security model
   - Vector embeddings for RAG
   - PWA service worker architecture
   - Multi-tenant database isolation concepts
   - DOMPDF/PHPWord document generation pipeline
   - Explain architectural decisions (why PWA, why offline-first, why multi-tenant, why HMAC)

2. **Describe System Architecture (60-90 min)**
   - Frontend: Svelte 5 components, Tailwind 4 styling, shadcn-svelte
   - Backend: Laravel 12 controllers, policies, middleware, form request validation
   - Database: Multi-tenant schema, tenant isolation, Eloquent ORM
   - PWA: Service worker, offline-first sync, IndexedDB caching
   - AI: Vector embeddings (MixedBread), RAG pattern, natural language querying
   - Security: HMAC signature locks, immutable audit logs, Laravel Policy route gating
   - Document Generation: DOMPDF for PDF rendering, PHPWord/FPDI for DOCX generation

3. **Write Technical Framework (60-90 min)**
   - 1-2 paragraphs explaining architecture in academic language
   - Include technical architecture diagram
   - Figure caption: placed below the figure, bold (per GUIDE-1)
   - Reference specific features in SYSTEM_FEATURES.md
   - Ensure DOMPDF/PHPWord document generation pipeline is explicitly covered

**Deliverable:** 1-2 paragraph technical framework covering Laravel 12 / Inertia v2 / Svelte 5 / Tailwind 4 stack, HMAC security model, vector embeddings for RAG, PWA service worker architecture, multi-tenant database isolation, and DOMPDF/PHPWord document generation pipeline, with technical architecture diagram

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
   - 2-3 paragraphs expanding the IPO into narrative for Chapter 2
   - Describe the system workflow stages in detail
   - Connect inputs through processing to outputs with both existing and planned features
   - Connect framework to research design and methodology

3. **Align with Methodology (15-30 min)**
   - Ensure framework aligns with Chapter 2 methodology sections
   - Verify IPO items map to real processing stages

**Deliverable:** 2-3 paragraph expanded conceptual framework narrative for Chapter 2, connecting inputs through processing to outputs with both existing and planned features

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
   - All sources must be 2022-2026

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
   - Key rules to verify:
     - Left margin: 1.5 inches (NOT 1 inch)
     - Right margin: 1.0 inch
     - Top/Bottom margins: 1.0 inch
     - Font: Times New Roman, 12pt
     - Spacing: Double-spaced, no extra spaces between paragraphs
     - Page numbers: top-right, continuous (except first page of each chapter)

2. **Check All Formatting Rules (60-90 min)**
   - **No bullet points ANYWHERE in manuscript** — numbered lists only where explicitly allowed (IPO boxes, specific objectives)
   - **No bold body text for emphasis** — bold is ONLY for chapter headings, subheadings, figure captions, table captions
   - **Paragraph indent: exactly 5 spaces**
   - **Table captions: left-aligned, above the table, bold**
   - **Figure captions: below the figure, bold**
   - **Tables: 1pt border line width**
   - **Header/footer borders must sit entirely within the 1-inch top/bottom margins**
   - **No extra spacing between paragraphs** — space before/after = 0pt

3. **Create Formatting Checklist (15-30 min)**
   - Mark which formatting rules passed/failed
   - List corrections needed
   - Use the Format Pre-Flight Checklist from TEAM_META_GUIDE Part 4

**Deliverable:** Formatting QA report with checklist of violations/corrections per GUIDE-1 rules

---

#### CC-03: Narrative Consistency Review and Integration (David)
**Effort:** 4-6 hours
**Due:** June 9
**Dependencies:** All writing tasks

**Detailed Subtasks:**
1. **Read All Chapters End-to-End (60-90 min)**
   - Chapter 1: Background → Framework → Objectives → Research Questions → Scope → Significance
   - Chapter 2: Literature Reviews → Related Systems → Technical Framework → Conceptual Framework Prose

2. **Check Consistency Across Sections (90-120 min)**
   - Background problem aligns with objectives
   - Objectives align with research questions
   - Framework aligns with methodology
   - Scope aligns with features described
   - Existing features and planned features are presented as one coherent system story
   - Citations are consistent (author/year matches references)
   - Terminology consistent (e.g., "SecureCAT" used consistently)

3. **Fix Inconsistencies (60-90 min)**
   - Unify voice and tone across sections written by different people
   - Ensure transitions between sections are smooth
   - Align terminology throughout
   - Ensure logical progression from problem → solution → evaluation

**Deliverable:** Final integrated manuscript with unified voice, consistent narrative, and smooth transitions

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
   - Verify all sources are 2022-2026

3. **Create Cross-Check Report (15-30 min)**
   - List missing citations
   - List orphaned references
   - List citation/reference mismatches

**Deliverable:** Citation cross-check report with mismatches flagged

---

## Timeline Overview

### Week 1: June 1-3 (Foundation)
- **Focus:** Framework, objectives, early background
- David: C1-07, C1-09 (IPO Diagram, Objectives)
- Jaypee: C1-01 (Core Problem — 8-12 sentences)
- Christine: C1-12 draft (Significance)

### Week 1: June 4-5 (Background + Literature)
- **Focus:** Background paragraphs, literature reviews
- Jaypee: C1-02, C1-03, C1-10 (Global Context 12-15 sentences, National Context 12-15 sentences, Research Questions)
- Christine: C1-04, C2-02 (Local Context 12-15 sentences, Automated Scoring & OMR Technologies)
- David: C1-08, C1-11, C2-01, C2-03, C2-05 (Narrative, Scope, Lit reviews)

### Week 2: June 6-7 (Synthesis + Framework)
- **Focus:** Background synthesis, conceptual framework, technical framework
- David: C1-05, C1-06, C2-04, C2-07, C2-08 (Synthesis 10-12 sentences, Clinching 8-10 sentences, PWA Lit, Technical Framework incl. DOMPDF/PHPWord, Ch2 Prose)
- Jaypee: C2-06 (Related Systems)

### Week 2: June 8 (QA & References)
- **Focus:** References, formatting, citations
- Christine: CC-01 (References)
- Jaypee: CC-02, CC-04 (Formatting QA per GUIDE-1 with 1.5" left margin, Citations)

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

## Formatting Quick Reference (from GUIDE-1)

| Rule | Value |
|------|-------|
| Left margin | 1.5 inches |
| Right margin | 1.0 inch |
| Top margin | 1.0 inch |
| Bottom margin | 1.0 inch |
| Font | Times New Roman, 12pt |
| Line spacing | Double throughout |
| Paragraph indent | Exactly 5 spaces |
| Alignment | Justified |
| Bullet points | NONE anywhere in manuscript |
| Bold | ONLY for headings, subheadings, figure captions, table captions |
| Table captions | Left-aligned, above the table, bold |
| Figure captions | Below the figure, bold |
| Table borders | 1pt line width |
| Page numbers | Every page except first page of each chapter |
| Header/footer borders | Within 1-inch margins |
| Space between paragraphs | 0pt (no extra spacing) |

---

## Risk Mitigation

| Risk | Mitigation | Owner |
|------|------------|-------|
| Jaypee unclear on task directions | Create detailed per-member DIRECTION.md files | David |
| Christine overloaded (ISPSC enrollment) | Keep her focused on local context tasks (3 tasks only) | David |
| Literature search returns few 2022-2026 sources | Use broader search terms, supplement with earlier sources if justified | All |
| References compilation becomes chaotic | All members submit draft references with their tasks | Christine |
| Formatting violations discovered late | Jaypee runs formatting QA early (June 8) with correct GUIDE-1 rules | Jaypee |
| Paragraph lengths don't match spec | Verify sentence counts during CC-03 narrative review | David |

---

## Paragraph Length Quick Reference

| Task | Paragraph | Target Sentences | Citations Required |
|------|-----------|------------------|--------------------|
| C1-01 | P1 — Core Problem | 8-12 sentences | None (citation-free) |
| C1-02 | P2 — Global Context | 12-15 sentences | Minimum 5 (2022-2026) |
| C1-03 | P3 — National Context | 12-15 sentences | Minimum 5 (2022-2026) |
| C1-04 | P4 — Local Context | 12-15 sentences | Minimum 5 (2022-2026) |
| C1-05 | P5 — Synthesis & Gap | 10-12 sentences | Draw from P2-P4 |
| C1-06 | P6 — Clinching | 8-10 sentences, 3 components | None required |

---

## Success Criteria

- ✅ All 21 tasks assigned with clear deadlines
- ✅ All tasks have detailed subtasks (4-6 steps each)
- ✅ Workload balanced across team members
- ✅ Dependencies mapped and respected
- ✅ Clear deliverables defined for each task
- ✅ Communication plan established
- ✅ Risk mitigation strategies defined
- ✅ Paragraph lengths match TEAM_META_GUIDE exactly
- ✅ IPO inputs/outputs grounded in SYSTEM_FEATURES.md
- ✅ Significance beneficiaries are system-specific (not generic)
- ✅ C2-02 correctly covers Automated Scoring & OMR Technologies
- ✅ Technical Framework includes DOMPDF/PHPWord pipeline
- ✅ Scope covers both existing and planned features
- ✅ Research questions cover operational, role-based, security, and resilience dimensions
- ✅ Formatting rules match GUIDE-1 exactly (1.5" left margin, no bullets, 5-space indent)

---

**Next Steps:**
1. ✅ Create per-member DIRECTION.md files (with detailed task directions)
2. ✅ Create interactive Gantt chart HTML
3. Share this plan with team members
4. Begin Week 1 tasks immediately
