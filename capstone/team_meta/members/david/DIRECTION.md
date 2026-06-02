# Member Task Direction — David
## Team Leader / Product Owner / Lead Developer

> **Role:** Technical architecture, background paragraphs (P1-P3), Chapter 2 METHODOLOGY sections (Research Design, Software Model, Project Plan, Project Assignment, Research Instruments, Data Analysis), narrative consistency, formatting QA
> **Total Claimed Tasks:** 17 tasks
> **Estimated Effort:** 43-64 hours (out of ~55h available)
> **Focus:** Full workload — technical writing, methodology design, project planning, quality assurance

> **IMPORTANT — Literature from Old Lit Reviews:** The literature previously gathered for the old Chapter 2 literature review tasks (RBAC, AI/RAG, PWA, DPA) is NOT lost. It feeds directly into Chapter 1 Background paragraphs P2 (Global Context) and P3 (National Context). Use relevant findings and citations from those research efforts when writing C1-02 and C1-03.

---

## Instructor Submission Instructions

> "To all groups with approved title, construct your manuscript with the following contents:
> Title page, Table of Contents, Chapter 1, Chapter 2, Appendices (letter to conduct, Use Case Diagram).
> To check the formatting and template, run your document using your account in the docuCheck system as you have created in Activity 1.
> Attach here the following: Brandname.docx, Proof of running the document in the docuCheck system."
>
> **Deadline:** June 10, 2026. **Mode:** Google Classroom (or physical copy).
> **Margins:** Top 1.5", Left 1.5", Right/Bottom 1.0". **Paper:** A4.

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

### Chapter 2 — METHODOLOGY
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C2-01 | Research Design — Descriptive Developmental | 3-4h | Jun 5 | C1-09 |
| C2-02 | Software Model — RAD or AIDLC | 3-4h | Jun 5 | None |
| C2-03 | Project Plan — Gantt Chart | 3-4h | Jun 6 | C2-02 |
| C2-04 | Project Assignment — Table 1 | 2-3h | Jun 5 | None |
| C2-06 | Research Instruments — SUS | 3-4h | Jun 6 | C1-09 |
| C2-07 | Data Analysis — Methods and SUS Interpretation | 3-4h | Jun 6 | C2-06, C1-09 |

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

**Chapter 2 restructured (new METHODOLOGY tasks):**
- C2-01: Research Design (replaces old Lit Review — RBAC)
- C2-02: Software Model — RAD or AIDLC (new task)
- C2-03: Project Plan — Gantt Chart (replaces old Lit Review — AI/RAG)
- C2-04: Project Assignment (replaces old Lit Review — PWA)
- C2-06: Research Instruments — SUS (replaces old Lit Review — DPA)
- C2-07: Data Analysis (replaces old Technical Framework)

**Old tasks REMOVED:** C2-01 (RBAC lit review), C2-03 (AI/RAG lit review), C2-04 (PWA lit review), C2-05 (DPA lit review), C2-07 (Technical Framework), C2-08 (Conceptual Framework Prose)

**Literature reuse note:** Literature gathered for the removed lit review tasks feeds into Chapter 1 Background P2 (Global) and P3 (National).

**Jaypee's focus:** Local context (C1-04), research questions (C1-10), significance of the study (C1-12), references (CC-01), citations (CC-04)

**Christine's focus:** Population and Locale of the Study (C2-05 in new scheme)

---

## Detailed Task Directions

### Week 1 (June 1-5): Background Tasks + Foundation

#### C1-01: Background P1 — Core Problem Statement (June 4)

**Priority:** HIGH — First paragraph of Chapter 1, sets the tone for the entire manuscript

**Target:** 1 paragraph, **8-12 sentences**, citation-free

**What to Do:**

1. **Identify the Core Problem (30 min):**
   - Read `SYSTEM_FEATURES.md` and `research/Existing_and_Planned_Features.md` to understand what SecureCAT does.
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
   - **Literature reuse:** Incorporate relevant findings from old RBAC, AI/RAG, PWA, and DPA literature reviews where they fit the global context.

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
   - **Literature reuse:** Incorporate relevant DPA/RA 10173 findings from old DPA literature review.

**Reference docs:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 1 Paragraph 3

**Deliverable:** `C1-03_David_National_Context.md` — 1 paragraph (12-15 sentences, 5+ APA citations, all 2022-2026) + draft APA references

---

### Week 1 (June 1-3): Foundation Tasks

#### C1-07: Conceptual Framework — IPO Diagram (June 3)
**Priority:** HIGH — Blocks C1-08

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
   7. AI Companion responses (course recommendations, applicant inquiries)
   8. Offline-cached records
   9. Statistical reports

4. **Format the diagram:**
   Create the three-box diagram (Input → Process → Output). Use simple formatting with boxes, arrows, and numbered lists inside. Ensure readable on a standard page. Figure caption placed BELOW the figure, bold.

**Critical Restrictions:**
- Inputs must be THINGS the system receives (data, configs, parameters). No process verbs.
- Outputs must be THINGS the system produces. No process verbs or activities.
- No bullet points anywhere. Numbered lists only inside IPO boxes.

**Reference:** `SYSTEM_FEATURES.md`, `research/Existing_and_Planned_Features.md`

**Deliverable:** `C1-07_David_IPO_Diagram.md` with Input (6 numbered items) → Process (full title) → Output (9 numbered items)

---

#### C1-09: Objectives of the Study (June 3)
**Priority:** HIGH — Blocks C1-10 (Jaypee's task) and C2-01, C2-06, C2-07

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

### Week 1 (June 3-5): Chapter 2 METHODOLOGY Tasks (Part 1)

#### C2-01: Research Design — Descriptive Developmental (June 5)
**Priority:** HIGH — Opens Chapter 2, sets methodological foundation

**What to Do:**

1. **Research Descriptive Developmental Research Design (60-90 min):**
   - Search Google Scholar for: "descriptive developmental research design", "descriptive developmental methodology IT capstone", "developmental research design software engineering"
   - Filter: **2022-2026 only**, peer-reviewed journals and methodological references
   - Target: 3-5 sources covering:
     - Definition of descriptive developmental research design
     - Application to software/information technology projects
     - How descriptive methods pair with developmental outputs
     - Methodological frameworks for systems development research

2. **Draft Research Design Paragraph (60-90 min):**
   - Write **1-2 paragraphs** in paragraph form (no bullets, no numbered lists)
   - **Structure:**
     - Define "descriptive developmental" research design — cite methodological sources
     - Explain the "descriptive" component: the study describes the current manual admission testing processes at ISPSC Tagudin to identify operational gaps and requirements
     - Explain the "developmental" component: the study develops SecureCAT as a software product to address the identified gaps
     - Connect to the study's three objectives (identify, develop, evaluate) from C1-09
   - **Minimum 3 in-text citations (Author, Year)**, all 2022-2026
   - Use synthesis writing — explain how this design applies to SecureCAT specifically

3. **Review and Refine (30 min):**
   - [ ] Paragraph form only — no bullets or numbered lists in body text
   - [ ] Descriptive component clearly explained with ISPSC context
   - [ ] Developmental component clearly explained with SecureCAT as the product
   - [ ] Connections to specific objectives (C1-09) are explicit
   - [ ] Minimum 3 methodological citations, all 2022-2026
   - [ ] No bold body text (bold only for section heading)

4. **Compile References (15 min):**
   - Create draft APA 7 references for all sources
   - Save for Jaypee's CC-01 (References Compilation)

**Reference:** `guides/GUIDE-3-CHAPTER2-CONTENT.md` (Research Design section)

**Deliverable:** `C2-01_David_Research_Design.md` with 1-2 paragraphs defining descriptive developmental research design, applying it to SecureCAT, minimum 3 APA citations + draft references

---

#### C2-02: Software Model — RAD or AIDLC (June 5)
**Priority:** HIGH — Defines the development methodology and phases for the project

**What to Do:**

1. **Research RAD and AIDLC Models (60-90 min):**
   - Search Google Scholar for: "Rapid Application Development RAD methodology", "Agile Iterative Development Life Cycle AIDLC", "RAD vs AIDLC software development", "software development models for IT capstone"
   - Filter: **2022-2026 only**, peer-reviewed journals
   - Target: 3-5 sources covering:
     - Rapid Application Development (RAD) — phases, principles, advantages
     - Agile Iterative Development Life Cycle (AIDLC) — phases, principles, advantages
     - Comparison of RAD and AIDLC for web application development
     - Application to educational/institutional software

2. **Choose the Software Model (30 min):**
   - Evaluate both RAD and AIDLC against SecureCAT's characteristics:
     - SecureCAT has clear modules (scheduling, OMR, AI companion, PWA offline, RBAC)
     - Development involves iterative feature delivery (existing system + planned modules)
     - Requirements may evolve during development
   - Select the model that best fits the project's iterative, module-based nature
   - Document your rationale in 2-3 sentences

3. **Draft Software Model Description (60-90 min):**
   - Write **1-2 paragraphs** in paragraph form describing the chosen model and its phases
   - **Structure:**
     - Name the chosen model (RAD or AIDLC) and cite the source
     - Describe each phase of the model in paragraph form:
       - If RAD: Requirements Planning → User Design → Rapid Construction → Cutover
       - If AIDLC: Planning → Analysis → Design → Implementation → Testing → Deployment (with iteration)
     - Explain how each phase applies to SecureCAT's development
     - Minimum 3 in-text citations (Author, Year), all 2022-2026
   - **Paragraph form ONLY** — no bullets or numbered lists in body text
   - FORBIDDEN: listing phases as bullet points or numbered items in body text

4. **Create Software Model Figure (30 min):**
   - Create a visual diagram showing the phases of the chosen model
   - Show iteration loops where applicable
   - Figure caption: **"Figure X. [Model Name] Phases"** — placed BELOW the figure, bold
   - Keep the diagram clean and readable on A4 paper

5. **Review and Refine (30 min):**
   - [ ] Model is clearly named and cited
   - [ ] All phases described in paragraph form (no bullets)
   - [ ] Each phase is connected to SecureCAT's development activities
   - [ ] Figure included with correct caption format (below figure, bold)
   - [ ] Minimum 3 citations, all 2022-2026
   - [ ] Paragraph form only — no bold body text

6. **Compile References (15 min):**
   - Create draft APA 7 references for all sources
   - Save for Jaypee's CC-01

**Reference:** `guides/GUIDE-3-CHAPTER2-CONTENT.md` (Software Model section), `SYSTEM_FEATURES.md`

**Deliverable:** `C2-02_David_Software_Model.md` with 1-2 paragraphs describing chosen model (RAD or AIDLC) in paragraph form, figure showing phases, minimum 3 APA citations + draft references

---

#### C2-04: Project Assignment — Table 1 (June 5)
**Priority:** MEDIUM — Team role assignments table

**What to Do:**

1. **Review Team Roles and Assignments (30 min):**
   - Review `TEAM_META_GUIDE.md` for standard capstone role structure
   - Identify the 5 standard capstone roles:
     1. Project Manager / Team Leader
     2. Lead Developer
     3. Developer
     4. QA / Tester
     5. Documentation / Technical Writer
   - Map each role to the actual team member(s):
      - David — Project Manager / Team Leader, Lead Developer
      - Christine — Developer
      - Jaypee — Developer, QA / Tester, Documentation / Technical Writer
    - Ensure role assignments match actual contributions to the project

2. **Draft Project Assignment Table (30-45 min):**
   - Create **Table 1** with the following structure:

     | Name | Role | Task Assignment |
     |------|------|-----------------|
     | David | Project Manager / Team Leader | Overall project coordination, technical architecture, Chapter 1 background (P1-P3), Chapter 2 methodology sections (Research Design, Software Model, Project Plan, Project Assignment, Research Instruments, Data Analysis), formatting QA, narrative consistency |
     | Christine | Developer | Chapter 2 population and locale, system modules development, testing |
     | Jaypee | Developer / QA | Chapter 1 local context, research questions, Chapter 1 significance of the study, references compilation, testing, documentation |

   - Adjust task assignments to reflect the corrected Chapter 2 METHODOLOGY structure
   - Ensure all 5 standard roles are represented (even if some members hold multiple roles)

3. **Write Narrative Paragraph (30-45 min):**
   - Write **1 paragraph** in paragraph form introducing Table 1
   - Describe how roles were assigned based on individual competencies and project needs
   - Mention that the team follows a collaborative approach with defined responsibilities
   - Paragraph form only — no bullets or numbered lists

4. **Format Table (15-30 min):**
   - Table caption: **"Table 1. Project Assignment"** — placed ABOVE the table, left-aligned, bold
   - Table borders: 1pt line width
   - Column headers: bold
   - Content: Times New Roman 12pt
   - Ensure table fits within page margins (Left 1.5", Right 1.0")

5. **Review and Refine (15 min):**
   - [ ] Table 1 contains exactly 5 standard roles (may be held by 3 members)
   - [ ] All team members are listed with their roles and task assignments
   - [ ] Task assignments reflect corrected Chapter 2 METHODOLOGY structure
   - [ ] Table caption is above the table, left-aligned, bold
   - [ ] Table borders are 1pt
   - [ ] Narrative paragraph introduces the table
   - [ ] Paragraph form only — no bullets in body text

**Reference:** `TEAM_META_GUIDE.md`, team member role assignments

**Deliverable:** `C2-04_David_Project_Assignment.md` with 1 narrative paragraph + Table 1 (5 standard roles, team member assignments, task descriptions)

---

### Week 1-2 (June 3-7): Chapter 1 Framework and Scope Tasks

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
   - Describe each output (all 9: status tracking displays, exam schedules, score reports, audit logs, result sheets/PDFs, consultation summaries, AI Companion responses (course recommendations, applicant inquiries), offline-cached records, statistical reports) and its purpose
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
     - **Existing system modules:** application intake, scheduling, roster, grading, OMR CSV import, consultation summaries, result release, document generation, audit logs, notifications, AI companion (RAG-powered), AI scheduling assistant
     - **Planned research modules:** HMAC integrity, CV-based OMR, offline PWA, enhanced AI Companion with external data integration and course recommendations, auto-scheduling agent, multi-tenant architecture
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

**Reference:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 4, `SYSTEM_FEATURES.md`, `research/Existing_and_Planned_Features.md`

**Deliverable:** `C1-11_David_Scope_Delimitations.md` with 2 paragraphs (scope + delimitations), paragraph-form only, covering both existing and planned features

---

### Week 1-2 (June 5-6): Chapter 2 METHODOLOGY Tasks (Part 2)

#### C2-03: Project Plan — Gantt Chart (June 6)
**Priority:** HIGH — Shows project timeline, phases must match software model from C2-02

**What to Do:**

1. **Review Software Model Phases (30 min):**
   - Read your C2-02 (Software Model) output
   - List the phases of the chosen model (RAD or AIDLC)
   - These phases MUST be the basis for the Gantt chart timeline
   - Example if RAD: Requirements Planning → User Design → Rapid Construction → Cutover
   - Example if AIDLC: Planning → Analysis → Design → Implementation → Testing → Deployment (iterated)

2. **Define Project Timeline and Milestones (45-60 min):**
   - Define the overall project timeframe (e.g., June 2026 – March 2027, or per academic calendar)
   - Break down each software model phase into specific activities:
     - Planning/Requirements: literature review, requirements gathering, system analysis
     - Design: UI/UX design, database design, architecture design
     - Development/Construction: frontend development, backend development, integration
     - Testing: unit testing, integration testing, usability testing (SUS)
     - Deployment: system deployment, user training, documentation
   - Assign realistic durations to each activity
   - Identify milestones and deliverables for each phase

3. **Draft Narrative Paragraph (45-60 min):**
   - Write **1 paragraph** in paragraph form describing the project plan
   - Include specific dates or date ranges for each phase
   - Connect phases to the software model chosen in C2-02
   - Example: "The project follows a [RAD/AIDLC] approach spanning [X months], beginning with [phase] in [month year] and concluding with [phase] in [month year]. The requirements planning phase, scheduled from [date] to [date], involves..."
   - **Paragraph form ONLY** — no bullets or numbered lists in body text
   - Minimum 2-3 sentences describing each major phase with dates

4. **Create Gantt Chart Figure (60-90 min):**
   - Create a Gantt chart showing:
     - X-axis: timeline (months or weeks)
     - Y-axis: project phases and activities
     - Bars showing duration of each activity
     - Milestones marked where appropriate
   - Phases must match the software model from C2-02
   - Figure caption: **"Figure X. Gantt Chart of Project [Name]"** — placed BELOW the figure, bold
   - Ensure chart is readable on A4 paper with proper margins
   - Use a clean, professional layout

5. **Review and Refine (30 min):**
   - [ ] Gantt chart phases match software model from C2-02
   - [ ] Narrative paragraph describes each phase with specific dates
   - [ ] Figure caption is below the figure, bold
   - [ ] Timeline is realistic for a capstone project
   - [ ] Paragraph form only — no bullets in body text
   - [ ] Chart is readable and properly formatted

**Reference:** `guides/GUIDE-3-CHAPTER2-CONTENT.md` (Project Plan section), C2-02 output

**Deliverable:** `C2-03_David_Gantt_Chart.md` with 1 narrative paragraph + Gantt chart figure, phases matching software model from C2-02

---

#### C2-06: Research Instruments — System Usability Scale (June 6)
**Priority:** HIGH — Defines the evaluation instrument, connects to C1-09 Objective 3

**What to Do:**

1. **Research the System Usability Scale (60-90 min):**
   - Search Google Scholar for: "System Usability Scale SUS", "SUS usability measurement", "Brooke SUS 1996", "SUS scoring 0-100", "SUS Likert scale usability"
   - Target: 3-5 sources covering:
     - Original SUS development by Brooke (1996) — foundational citation
     - SUS structure: 10 items, 5-point Likert scale (Strongly Disagree to Strongly Agree)
     - SUS scoring formula: odd items (subtract 1 from user response), even items (subtract 5 from user response), sum and multiply by 2.5 for score range 0-100
     - SUS interpretation: 0-100 scale with adjective ratings
     - Application of SUS to educational software and web applications
   - Include both the original Brooke (1996) reference and recent (2022-2026) applications

2. **Draft Research Instruments Description (60-90 min):**
   - Write **1-2 paragraphs** in paragraph form describing the SUS instrument
   - **Structure:**
     - Introduce the System Usability Scale (SUS) as the chosen instrument for evaluating SecureCAT's usability
     - Describe the SUS structure: 10 questionnaire items on a 5-point Likert scale (1 = Strongly Disagree, 5 = Strongly Agree)
     - Explain the SUS scoring procedure: odd-numbered items are scored as (response − 1), even-numbered items as (5 − response); the sum is multiplied by 2.5 to yield a score from 0 to 100
     - Cite Brooke (1996) as the original source and at least 2 recent applications
     - Explain why SUS is appropriate for evaluating SecureCAT (brief, validated, widely used for software usability)
   - **Minimum 3 in-text citations**, including Brooke (1996) and recent sources
   - **Paragraph form ONLY** — no bullets or numbered lists in body text
   - **DO NOT** reproduce the full 10 SUS items verbatim (they are copyrighted); describe the structure

3. **Review and Refine (30 min):**
   - [ ] SUS is described with: 10 items, 5-point Likert, 0-100 scoring range
   - [ ] Brooke (1996) is cited as the original source
   - [ ] At least 2 additional recent (2022-2026) citations included
   - [ ] Scoring procedure is clearly explained in paragraph form
   - [ ] Connection to Objective 3 (evaluate usability using SUS) is explicit
   - [ ] Paragraph form only — no bullets, no bold body text
   - [ ] No verbatim reproduction of SUS items

4. **Compile References (15 min):**
   - Create draft APA 7 references for all sources (including Brooke, 1996)
   - Save for Jaypee's CC-01

**Reference:** `guides/GUIDE-3-CHAPTER2-CONTENT.md` (Research Instruments section), C1-09 Objectives

**Deliverable:** `C2-06_David_Research_Instruments.md` with 1-2 paragraphs describing SUS (10 items, 5-point Likert, 0-100 scoring), minimum 3 citations (Brooke 1996 + 2 recent) + draft references

---

#### C2-07: Data Analysis — Methods and SUS Interpretation (June 6)
**Priority:** HIGH — Links objectives to analysis methods, defines how SUS data is interpreted

**What to Do:**

1. **Map Objectives to Analysis Methods (45-60 min):**
   - Review C1-09 (Objectives of the Study) — 3 specific objectives
   - For each objective, define the corresponding data analysis method:
     - **Objective 1 (Identify):** Qualitative analysis of gathered requirements — describe how needs assessment data, process documentation, and gap analysis results will be organized and analyzed (thematic analysis or descriptive summary)
     - **Objective 2 (Develop):** Development process documentation — describe how the development process follows the chosen software model (from C2-02) and how outputs are validated against requirements
     - **Objective 3 (Evaluate):** SUS statistical analysis — describe how SUS scores will be computed and interpreted using the SUS scale
   - **NO significance testing** — the study uses descriptive interpretation of SUS scores, not hypothesis testing

2. **Draft Data Analysis Description (60-90 min):**
   - Write **1-2 paragraphs** in paragraph form describing data analysis methods
   - **Structure:**
     - Paragraph 1: Link each specific objective to its corresponding analysis method (identify → qualitative description, develop → development validation, evaluate → SUS scoring)
     - Paragraph 2: Describe the SUS interpretation framework — how raw scores are converted to 0-100 scale and what score ranges mean (reference Table 3)
   - **Minimum 2-3 in-text citations** for SUS interpretation methodology
   - **Paragraph form ONLY** — no bullets or numbered lists in body text

3. **Create Table 3 — SUS Interpretation Scale (30-45 min):**
   - Create **Table 3** showing the SUS score interpretation:

     | SUS Score Range | Adjective Rating | Interpretation |
     |-----------------|-------------------|----------------|
     | 0-25 | Worst Imaginable | Not acceptable |
     | 25-39 | Poor | Marginal |
     | 39-52 | OK | Marginal |
     | 52-73 | Good | Acceptable |
     | 73-85 | Excellent | Acceptable |
     | 85-100 | Best Imaginable | Acceptable |

   - Adjust ranges based on your cited source (Bangor et al., 2009 or Brooke, 1996)
   - Table caption: **"Table 3. SUS Score Interpretation"** — placed ABOVE the table, left-aligned, bold
   - Table borders: 1pt line width

4. **Review and Refine (30 min):**
   - [ ] Each specific objective (C1-09) is linked to a data analysis method
   - [ ] SUS interpretation is described in paragraph form with Table 3 reference
   - [ ] **NO significance testing** mentioned (no t-test, ANOVA, p-values, etc.)
   - [ ] Table 3 has correct caption format (above, left-aligned, bold) and 1pt borders
   - [ ] Minimum 2-3 citations for SUS methodology
   - [ ] Paragraph form only — no bullets in body text
   - [ ] Descriptive analysis only — SUS scores interpreted via adjective ratings

5. **Compile References (15 min):**
   - Create draft APA 7 references for all sources
   - Save for Jaypee's CC-01

**Reference:** `guides/GUIDE-3-CHAPTER2-CONTENT.md` (Data Analysis section), C1-09 Objectives, C2-06 Research Instruments

**Deliverable:** `C2-07_David_Data_Analysis.md` with 1-2 paragraphs linking objectives to analysis methods + Table 3 (SUS interpretation), no significance testing, minimum 2-3 citations + draft references

---

### Week 1-2 (June 5-7): Chapter 1 Synthesis Tasks

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
   - Example: studies on PWA for low-connectivity contexts informed the offline-first architecture; RBAC literature guided role-based design; AI/RAG research shaped the applicant-facing AI Companion feature

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
   | **Top margin** | **1.5 inches** | ❌ 1 inch — WRONG |
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

   **NOTE:** Per instructor instructions, top margin is **1.5 inches** (not 1.0 inch). Left margin is **1.5 inches**. Right and bottom margins are **1.0 inch**. Paper size: **A4**.

2. **Check All Formatting Rules (60-90 min):**
   Go through the entire manuscript and verify every rule:
   - [ ] **Margins:** Top 1.5", Left 1.5", Right 1.0", Bottom 1.0" on ALL pages
   - [ ] **Paper size:** A4
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
   - "Page 12: Left margin is 1.0\" — change to 1.5\""

**Deliverable:** `CC-02_David_Formatting_QA.md` — complete formatting checklist with violations and corrections

---

#### CC-03: Narrative Consistency Review and Integration (June 9)

**Priority:** CRITICAL — Final review before submission

**What to Do:**

1. **Read All Chapters End-to-End (60-90 min):**
   - Chapter 1: Background (P1-P6) → Framework (IPO + Narrative) → Objectives → Research Questions → Scope → Significance
   - Chapter 2: Research Design → Software Model → Project Plan → Project Assignment → Population and Locale → Research Instruments → Data Analysis
   - Read as a continuous narrative, not isolated sections

2. **Check Consistency Across Sections (90-120 min):**
   - **Background problem alignment:**
     - Background problem matches objectives
     - Objectives align with research questions
   - **Framework alignment:**
     - Framework aligns with methodology
     - IPO inputs/outputs match system description
   - **Methodology alignment:**
     - Research design matches the descriptive-developal approach
     - Software model phases match Gantt chart phases (C2-02 ↔ C2-03)
     - Research instruments (SUS) match evaluation objective (C1-09 Objective 3)
     - Data analysis methods link back to each specific objective
     - No significance testing mentioned anywhere in data analysis
   - **Scope alignment:**
     - Scope matches features described in methodology
     - Delimitations match what system does and does not do
     - Both existing and planned features presented coherently
   - **Citation consistency:**
     - All (Author, Year) citations match reference entries
     - No orphaned citations or references
     - All sources within 2022-2026 range (except foundational like Brooke 1996)
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

### Week 1 (June 1-3): Foundation Tasks
- [ ] **June 1-2:** C1-07 (IPO Diagram) — 6 inputs, 9 outputs, numbered lists only
- [ ] **June 1-2:** C1-09 (Objectives) — general objective + 3 numbered specific objectives
- [ ] **June 2-3:** C1-01 (Core Problem) — write 8-12 sentence citation-free paragraph

### Week 1 (June 3-5): Background + Methodology Foundation
- [ ] **June 3-4:** C1-02 (Global Context) — 12-15 sentences with 5+ citations (reuse old RBAC/AI/PWA lit)
- [ ] **June 3-4:** C1-03 (National Context) — 12-15 sentences with 5+ citations (reuse old DPA lit)
- [ ] **June 3-4:** C1-08 (Framework Narrative) — exactly 2 paragraphs
- [ ] **June 3-4:** C1-11 (Scope and Delimitations) — 2 paragraphs covering existing AND planned features
- [ ] **June 3-5:** C2-01 (Research Design) — define descriptive developmental, 3+ citations
- [ ] **June 3-5:** C2-02 (Software Model) — choose RAD or AIDLC, describe phases in paragraph form, include figure
- [ ] **June 3-5:** C2-04 (Project Assignment) — Table 1 with 5 standard roles + narrative paragraph
- [ ] **June 5:** C1-01, C1-02, C1-03, C1-08, C1-11, C2-01, C2-02, C2-04 due

### Week 1-2 (June 5-7): Methodology + Synthesis
- [ ] **June 5-6:** C2-03 (Project Plan — Gantt Chart) — phases match C2-02, narrative paragraph with dates
- [ ] **June 5-6:** C2-06 (Research Instruments — SUS) — describe SUS, 10 items, 5-point Likert, 0-100, cite Brooke
- [ ] **June 5-6:** C2-07 (Data Analysis) — link objectives to methods, Table 3 SUS interpretation, NO significance testing
- [ ] **June 5-6:** C1-05 (Synthesis and Gap) — 10-12 sentences, wait for Jaypee's C1-04
- [ ] **June 6-7:** C1-06 (Clinching) — 8-10 sentences with 3 explicit components
- [ ] **June 7:** C2-03, C2-06, C2-07, C1-05, C1-06 due

### Week 2 (June 8-9): QA
- [ ] **June 8:** CC-02 (Formatting QA) — check ALL formatting rules per GUIDE-1, A4 paper, correct margins
- [ ] **June 9:** CC-03 (Narrative Consistency) — read entire manuscript, fix inconsistencies, verify methodology alignment

### Week 2 (June 10): Submission
- [ ] **June 10:** Final manuscript assembled as Brandname.docx
- [ ] **June 10:** Run document through docuCheck system
- [ ] **June 10:** Submit via Google Classroom: Brandname.docx + docuCheck proof

---

## Communication Responsibilities

1. **Daily Progress Updates:**
   - Post brief updates in group Discord
   - Example: "Finished C1-01 core problem paragraph (10 sentences, no citations). Starting C2-01 Research Design."
   - Flag blockers immediately

2. **Coordinate with Jaypee:**
   - Provide C1-09 (Objectives) to Jaypee by June 3 for C1-10 (Research Questions)
   - Wait for Jaypee's C1-04 (Local Context) before starting C1-05 (Synthesis)
   - Ask for Jaypee's CC-01 (References) by June 8 for citation checks
   - Coordinate with Jaypee on C1-12 (Significance of the Study) if he needs technical details on AI Companion or HMAC signatures

3. **Coordinate with Christine:**
   - Provide Christine with Chapter 2 methodology context for her task (C2-05 Locale/Population)
   - Ensure C2-05 (Population and Locale — Christine's task) aligns with your C2-01 Research Design

4. **Submit Draft References:**
   - Your draft references for C1-02, C1-03, C2-01, C2-02, C2-06, C2-07 should be ready by June 6
   - Send these to Jaypee for CC-01 (References Compilation)

---

## Summary of Your 17 Tasks

**Chapter 1 (9 tasks):**
- C1-01, C1-02, C1-03: Background paragraphs (P1-P3) — 8-15 sentences each
- C1-05: Synthesis and Gap — 10-12 sentences
- C1-06: Clinching Statement — 8-10 sentences
- C1-07: IPO Diagram — 6 inputs, 9 outputs
- C1-08: Framework Narrative — exactly 2 paragraphs
- C1-09: Objectives — general + 3 specific
- C1-11: Scope and Delimitations — 2 paragraphs

**Chapter 2 — METHODOLOGY (6 tasks):**
- C2-01: Research Design — descriptive developmental, 3+ citations
- C2-02: Software Model — RAD or AIDLC, paragraph form, figure
- C2-03: Project Plan — Gantt chart, phases match C2-02, narrative with dates
- C2-04: Project Assignment — Table 1 with 5 standard roles
- C2-06: Research Instruments — SUS description (10 items, 5-point Likert, 0-100)
- C2-07: Data Analysis — objectives-to-methods mapping, Table 3 SUS interpretation, no significance testing

**Cross-Cutting (2 tasks):**
- CC-02: Formatting QA — check all GUIDE-1 rules, A4, correct margins
- CC-03: Narrative Consistency — final integration review, verify methodology alignment

**Total estimated effort: 45-55 hours** — full capacity

---

## Pre-Submission Checklist

Before June 10 submission:

- [ ] All Chapter 1 tasks complete and reviewed
- [ ] All Chapter 2 METHODOLOGY tasks complete and reviewed
- [ ] Software model phases (C2-02) match Gantt chart phases (C2-03)
- [ ] Research instruments (C2-06 SUS) match evaluation objective (C1-09 Objective 3)
- [ ] Data analysis methods (C2-07) link back to each specific objective
- [ ] No significance testing anywhere in data analysis section
- [ ] Formatting QA complete (CC-02)
- [ ] Narrative consistency review complete (CC-03)
- [ ] Title page included
- [ ] Table of Contents included
- [ ] Appendices included (Signed Letter to Conduct scan/photo, Use Case Diagram)
- [ ] Margins: Top 1.5", Left 1.5", Right/Bottom 1.0"
- [ ] Paper: A4
- [ ] Font: Times New Roman 12pt, double-spaced
- [ ] No bullet points anywhere in manuscript
- [ ] No bold body text
- [ ] All figure captions below figure, bold
- [ ] All table captions above table, left-aligned, bold
- [ ] Document saved as Brandname.docx
- [ ] Document run through docuCheck system
- [ ] Proof of docuCheck run captured
- [ ] Both files attached on Google Classroom

---

You are carrying the team's full technical and narrative workload. Focus on methodology accuracy, technical consistency across chapters, and narrative flow. Your role is to ensure all pieces fit together into a coherent manuscript that clearly presents SecureCAT as the solution to ISPSC Tagudin's admission testing challenges, with a properly structured METHODOLOGY chapter that aligns research design, software model, project plan, and evaluation approach.
