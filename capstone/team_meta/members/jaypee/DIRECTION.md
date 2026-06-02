# Member Task Direction — Jaypee
## Team Member — Local Context, Research Questions, References & Citations

> **Role:** Local context (ISPSC Tagudin), research questions, significance of the study, references compilation, citation cross-check
> **Total Assigned Tasks:** 5 tasks (C1-04, C1-10, C1-12, CC-01, CC-04)
> **Estimated Effort:** 10-16 hours
> **Focus:** Detail-oriented tasks requiring research, organization, analytical significance framing, and quality assurance
> **Hard Deadline:** June 10, 2026

---

## Your Tasks at a Glance

> [!IMPORTANT]
> **REASSIGNMENT ALERT (June 2):** Task **C1-12 (Significance of the Study)** has been reassigned from **Christine** to you to balance workload and align tasks with technical strengths. C1-12 requires detailing system-specific features such as PWA offline-first caching, RAG AI chatbot, multi-tenant DB isolation, and cryptographic HMAC integrity. Please follow the instructions below to draft this section.

### Chapter 1
| Task ID | Task | Sentences | Citations | Hours | Due | Dependencies |
|---------|------|-----------|-----------|-------|-----|--------------|
| C1-04 | Background P4 — Local Context (ISPSC Tagudin) | **12-15** | **Min 5 APA** | 3-5h | Jun 5 | None |
| C1-10 | Research Questions | — | None | 1-2h | Jun 4 | C1-09 (David) |
| C1-12 | Significance of the Study | — | None | 2-3h | Jun 5 | None |

### Cross-Cutting
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| CC-01 | References List Compilation | 3-4h | Jun 8 | All writing tasks |
| CC-04 | Citation Cross-Check | 1-2h | Jun 8 | All writing tasks, CC-01 |

> **Note:** Jaypee has NO Chapter 2 tasks. Chapter 2 is titled "METHODOLOGY" (not a literature review) and all its tasks are assigned to David (C2-01 through C2-04, C2-06, C2-07) and Christine (C2-05 Population and Locale).

---

## CRITICAL RULES — Read Before Starting Any Task

1. **Paragraph sentence counts are FIXED.** Do not deviate:
   - C1-04: exactly **12-15 sentences** (NOT 10-15, NOT 15-20)
   - If someone tells you "10-15 sentences," ignore them. Follow this guide.

2. **No bullet points ANYWHERE in any manuscript text.** All content must be in paragraph form.

3. **No bold body text.** Bold is ONLY for structural labels (headings, subheadings, figure captions, table captions).

4. **All citations must be 2022-2026.** No exceptions.

5. **Synthesize, never summarize.** FORBIDDEN pattern: "Author A says X. Author B says Y." Weave findings into themes instead.

6. **For CC-01 (References):** You must collect references from ALL team members (David, Christine, yourself) and compile one final alphabetized APA 7 list.

---

## Detailed Task Directions

### Week 1 (June 1-5): Local Context & Research Questions

#### C1-04: Background P4 — Local Context (ISPSC Tagudin) (June 5)

**Priority:** HIGH — Local foundation for the study

**Target:** 1 paragraph, **12-15 sentences**, minimum 5 APA citations (all 2022-2026)

#### Step 1: Get ISPSC Context from Christine (30 min)

- Ask Christine for first-hand knowledge of ISPSC Tagudin
- What you need to know:
  - Guidance Office processes: How do they schedule tests? What do they do manually?
  - Registrar workflows: How do they record scores? How do they process results?
  - Infrastructure: How reliable is WiFi? Are there enough computers? How many IT staff?
  - Peak admission challenges: What bottlenecks happen when hundreds of applicants arrive?
  - Compliance pressures: Does ISPSC need to follow RA 10173? How do they handle student data privacy?

#### Step 2: Research Comparable Regional Institutions (60 min)

- Go to **Google Scholar** (scholar.google.com). Search terms:
  - "Ilocos Region university admission systems"
  - "Philippine SUC guidance office automation"
  - "state university admission testing Philippines"
  - "manual admission workflow Philippine higher education"

- Filter: Date 2022-2026, peer-reviewed articles.

- If regional studies are scarce, broaden to:
  - "Philippine university admission processes"
  - "guidance office information systems Philippines"
  - "digital transformation Philippine SUCs"

- Target: **5 or more sources** on comparable institutions or regional ICT in education.

#### Step 3: Write Local Context Paragraph (90-120 min)

Write **12-15 sentences** describing ISPSC Tagudin's specific operational environment.

**Structure:**
- **Sentences 1-3:** ISPSC Tagudin context — location, student population, programs offered, institutional mandate.
- **Sentences 4-7:** Current manual processes in the Guidance Office and Registrar Office — describe the actual admission testing workflow, paper-based scheduling, manual score recording, disconnected systems between offices, and the lack of audit trails.
- **Sentences 8-11:** Operational constraints — WiFi reliability issues, limited IT staff, computer lab availability, and how these constraints compound during peak admission periods when both offices face heavy workloads simultaneously.
- **Sentences 12-15:** Compliance pressures and precedent from comparable institutions — RA 10173 compliance challenges with manual data handling, and what other Philippine SUCs are doing to modernize admission testing.

**Include minimum 5 in-text citations** (Author, Year) — mix of local/regional sources.

**Be specific:** Name actual processes, constraints, and institutions.

**Example sentence:** "At ISPSC Tagudin, the Guidance Office manually schedules admission tests using paper forms, while the Registrar separately records scores in spreadsheets, a process that results in delayed results and no audit trail (Author, 2024)."

#### Step 4: Compile Draft References (30 min)

- Create APA 7 draft references for all sources.
- Format: `Author, A. A. (Year). Title. *Source*, vol(issue), pages. DOI/URL`
- Save these for CC-01 (you will compile the final list yourself).

**Reference docs:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 1, Paragraph 4; `SYSTEM_FEATURES.md` for system context

**Deliverable:** `C1-04_Jaypee_Local_Context.md` — 1 paragraph (12-15 sentences, 5+ APA citations, all 2022-2026) + draft APA reference entries

---

#### C1-10: Research Questions (June 4)

**Priority:** MEDIUM — Aligns with specific objectives

**Target:** Numbered list of research questions

#### Step 1: Wait for David's C1-09 (Objectives) (due June 3)

- Ask David for his completed C1-09 specific objectives.
- Each specific objective must have a **one-to-one corresponding** research question.

#### Step 2: Convert Objectives to Questions (30-60 min)

Read David's C1-09 specific objectives (typically 3 objectives in Identify → Develop → Evaluate structure). Convert each to question form.

**Per TEAM_META_GUIDE, your questions MUST cover these four dimensions:**
1. **Operational needs:** What are the existing processes, gaps, and requirements?
2. **Role-based access needs:** How should role-based access be designed for multi-office coordination?
3. **Security needs:** How can data integrity and cryptographic verification be ensured?
4. **Operational resilience needs:** How can the system operate reliably under connectivity constraints?

**Standard 3-question structure:**
- RQ1 (from Objective 1 — Identify): "What are the existing admission testing processes, operational gaps, and system requirements at ISPSC Tagudin that necessitate a role-based digital platform?" — covers operational needs + role-based access needs
- RQ2 (from Objective 2 — Develop): "What are the features, security mechanisms, and architectural components required to develop a role-based, cryptographically-secured admission testing system with offline resilience?" — covers security needs + operational resilience needs
- RQ3 (from Objective 3 — Evaluate): "What is the usability level of the developed system as evaluated using the System Usability Scale (SUS) by intended users?" — covers evaluation

**Key rule:** RQ3 must say **"usability"** (not "acceptability") because SUS measures usability.

#### Step 3: Refine Question Wording (15-30 min)

- Ensure questions are clear, specific, and answerable
- Verify one-to-one correspondence with specific objectives
- Verify coverage of all four dimensions (operational, role-based, security, resilience)
- Check alignment with Chapter 2 methodology

**Deliverable:** `C1-10_Jaypee_Research_Questions.md` — numbered list of research questions, one-to-one with specific objectives, covering all four required dimensions

---

#### C1-12: Significance of the Study (June 5)
**Priority:** HIGH — Beneficiary impact description

**What to Do:**

1. **Identify Beneficiary Groups** (30 min):
   - The beneficiary groups are system-specific and directly tied to SecureCAT's operational context. These are the ONLY groups to include:
     - Registrar Office staff
     - Guidance Office counselors
     - Proctors and Test Administrators
     - Applicants and Examinees
     - ISPSC Administration
     - Future Researchers
   - Do NOT include generic groups such as "The Community," "The College/Department," "The Students," or "The Researchers."
   - Do NOT include a generic "new knowledge" paragraph — weave contributions into each group's paragraph instead.

2. **Write One Paragraph Per Beneficiary Group** (90-120 min):
   - Exactly 6 paragraphs, one per beneficiary group listed above.
   - Each paragraph must be in paragraph form only — no bullets, no numbered lists, no bold body text.
   - Each paragraph structure:
     - First sentence: Name the beneficiary group specifically.
     - Middle sentences: Explain specific benefits they receive from SecureCAT, grounded in system features.
     - Last sentence: Connect to broader impact or institutional value.
   - Key themes to weave across paragraphs:
     - DPA compliance — how SecureCAT's role-based access, audit logging, and data handling support RA 10173 requirements.
     - Operational continuity — how the system ensures admission workflows continue functioning even under infrastructure constraints (offline PWA, cached data).
     - Institutional reporting value — how automated scoring, audit trails, and real-time dashboards improve reporting and decision-making.

   - Content guidance per group:

     **Registrar Office staff.** SecureCAT provides a centralized digital application pipeline replacing manual paper-based review and approval workflows. Staff benefit from automated application processing, bulk import capabilities, real-time status tracking, and room and course management tools. The system's audit logging ensures accountability and compliance with RA 10173, while role-based access prevents unauthorized data access. This reduces manual data entry errors, accelerates processing during peak admission periods, and provides a verifiable record trail for institutional reporting.

     **Guidance Office counselors.** The system streamlines test administration through session roster management, proctor assignment, and digital attendance tracking. Counselors benefit from automated scoring via OMR CSV import (and planned computer vision ingestion), consultation summary documentation, and aptitude area management. The enhanced AI Companion (planned) will provide applicants with course recommendations and admission guidance via natural language, reducing repetitive applicant inquiries and allowing counselors to focus on student guidance rather than administrative overhead.

     **Proctors and Test Administrators.** SecureCAT equips proctors with real-time session management tools, digital attendance confirmation, and QR-based applicant verification. The planned offline-resilient PWA allows proctors to continue scanning applicant QR codes at exam room doors even when campus WiFi is unreliable, with data cached locally and synchronized automatically upon reconnection. Computer vision-based OMR answer sheet processing (planned) will enable instant automated scoring, eliminating manual grading and reducing turnaround time for result release.

     **Applicants and Examinees.** Applicants benefit from a transparent, real-time status tracker showing their progression from application submission through exam scheduling, attendance, score processing, and result release. The system provides admission slip generation with printable PDF rendering, reducing the need for physical office visits. Token-based account activation ensures secure access to personal records, while the AI companion chatbot provides instant guidance on application status and requirements. Faster score processing and automated result generation mean applicants receive outcomes more quickly and reliably.

     **ISPSC Administration.** The administration gains institutional-level visibility into admission operations through automated reporting, audit logs, and real-time dashboards. The system's role-based architecture ensures data governance aligned with RA 10173, while cryptographic score integrity (planned HMAC signatures) provides tamper-evident records for institutional accountability. Multi-tenant database isolation (planned) prepares the institution for future campus expansion without compromising data privacy. These capabilities strengthen the institution's capacity for evidence-based decision-making, regulatory compliance, and operational reporting.

     **Future Researchers.** SecureCAT serves as a reference implementation for role-based admission testing systems in Philippine state universities. The system's architecture — including RBAC with zero-trust data governance, computer vision OMR processing, offline-resilient PWA proctoring, applicant-facing AI Companion with RAG, and multi-tenant database isolation — provides a comprehensive baseline for future studies in educational technology, automated assessment, and institutional digital transformation. Future researchers can build upon the design patterns, security models, and architectural decisions documented in this study.

3. **Review for Compliance** (15-30 min):
   - Verify all 6 paragraphs are in paragraph form only — no bullets, no bold body text.
   - Verify each paragraph names a specific, direct beneficiary group.
   - Verify DPA compliance, operational continuity, and institutional reporting value are addressed across the paragraphs.
   - Remove any vague or indirect beneficiaries.

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 5; `SYSTEM_FEATURES.md`; `Existing_and_Planned_Features.md`

**Deliverable:** `C1-12_Jaypee_Significance.md` with 6 paragraphs (one per beneficiary group, paragraph form only)

---

### Week 2 (June 8): References & Citations

#### CC-01: References List Compilation (June 8)

**Priority:** HIGH — Final references for all chapters

**Target:** Complete alphabetized APA 7th Edition References list

#### Step 1: Collect All References from Team (60 min)

You need to gather draft references from ALL team members:
- **David:** C1-01, C1-02, C1-03 (background paragraphs), C2-01 through C2-04, C2-06, C2-07 (Chapter 2 METHODOLOGY tasks)
- **Christine:** C2-05 (Population and Locale)
- **Yourself:** C1-04 (Local Context), C1-10 (Research Questions)

**Ask team members to submit their draft references by June 7.**

Create a master list of all sources.

#### Step 2: Format All References in APA 7th Edition (90-120 min)

- **Alphabetical order:** Sort by first author's surname (A-Z).
- **APA 7 Format templates:**
  - **Journal Article:** Author, A. A., and Author, B. B. (Year). Title of article. *Title of Periodical, xx*(x), pp-pp. https://doi.org/xxxxx
  - **Book:** Author, A. A. (Year). *Title of work*. Publisher. DOI/URL
  - **Website:** Author, A. A. (Year). *Title of work*. Site Name. URL
- **Hanging indent:** First line flush left, subsequent lines indented.
- **Italics:** Italicize journal titles, volume numbers, and book titles.
- **Capitalization:** Sentence case for article titles, title case for journal and book titles.

#### Step 3: Cross-Check Citations Against References (30-60 min)

**Check 1 — Every citation has a reference:**
- Take each in-text citation and find its matching entry in the References list
- Flag: "Chapter 1, P3: (Doe, 2023) cited but no reference entry — MISSING REFERENCE"

**Check 2 — Every reference has a citation:**
- Go through References list alphabetically
- For each entry, search the manuscript for its in-text citation
- Flag: "References list: (Lee, 2022) exists but never cited in text — ORPHANED REFERENCE"

**Check 3 — Year and name consistency:**
- Verify author names match exactly between citation and reference
- Verify years match exactly
- Flag: "Chapter 2: (Smith, 2024) in text but reference says (Smith, 2023) — YEAR MISMATCH"

**Check 4 — All sources are 2022-2026:**
- Flag any source outside this range
- Example: "Reference (Johnson, 2021) is outside 2022-2026 range — REPLACE"

#### Step 4: Create Final References List (30 min)

- Compile all verified references into a single alphabetized list.
- Save as `CC-01_References_List.md`.
- This will be inserted into the final manuscript.

**Reference:** `guides/GUIDE-1-FORMATTING.md` (APA 7 reference formatting); `guides/GUIDE-3-CHAPTER2-CONTENT.md` (References section requirements)

**Deliverable:** `CC-01_Jaypee_References_Compiled.md` with complete alphabetized APA 7th Edition References list

---

#### CC-04: Citation Cross-Check (June 8)

**Priority:** MEDIUM — Ensures citation-reference integrity

**Target:** Cross-check report with flagged issues

#### Step 1: Wait for CC-01 Completion

CC-04 should be done AFTER CC-01, since you'll use the references list you just compiled.

#### Step 2: Extract All In-Text Citations (30-45 min)

Scan ALL chapters (Chapter 1 and Chapter 2) for in-text citations. Look for:
- `(Author, Year)` format
- `(Author, Year, p. X)` format
- Narrative citations like "Author (Year) found that..."

For each citation, record:
- Author name
- Year
- Location (chapter, section, paragraph)
- Example: "Chapter 1, Background P2: (Smith, 2024)"

#### Step 3: Verify Against References (30-45 min)

**Check 1 — Every citation has a reference:**
- Take each in-text citation and find its matching entry in the References list
- Flag: "Chapter 1, P3: (Doe, 2023) cited but no reference entry — MISSING REFERENCE"

**Check 2 — Every reference has a citation:**
- Go through References list alphabetically
- For each entry, search the manuscript for its in-text citation
- Flag: "References list: (Lee, 2022) exists but never cited in text — ORPHANED REFERENCE"

**Check 3 — Year and name consistency:**
- Verify author names match exactly between citation and reference
- Verify years match exactly
- Flag: "Chapter 2: (Smith, 2024) in text but reference says (Smith, 2023) — YEAR MISMATCH"

**Check 4 — All sources are 2022-2026:**
- Flag any source outside this range
- Example: "Reference (Johnson, 2021) is outside 2022-2026 range — REPLACE"

#### Step 4: Create Cross-Check Report (15-30 min)

Organize findings into categories:
- **Missing references** (citations without reference entries)
- **Orphaned references** (references without citations)
- **Year mismatches** (citation year ≠ reference year)
- **Name mismatches** (citation name ≠ reference name)
- **Out-of-range sources** (pre-2022 or post-2026)

**Deliverable:** `CC-04_Jaypee_Citation_Cross_Check.md` — cross-check report organized by issue category

---

### Week 1 (June 1-5): Local Context, Research Questions & Significance
- [ ] **June 1-2:** Get ISPSC context from Christine
- [ ] **June 2-3:** Research regional institutions for C1-04
- [ ] **June 3-4:** Write C1-04 (Local Context) — 12-15 sentences with 5+ citations
- [ ] **June 2-3:** Wait for David's C1-09 (Objectives)
- [ ] **June 3-4:** C1-10 (Research Questions) — convert objectives to questions covering all 4 dimensions
- [ ] **June 4-5:** C1-12 (Significance of the Study) — draft 6 beneficiary paragraphs in paragraph form
- [ ] **June 5:** Final review and polish of C1-04, C1-10, and C1-12

### Week 2 (June 6-8): References & Citations
- [ ] **June 6:** Light day — review C1-04 and C1-10, begin organizing any sources found during research
- [ ] **June 7:** Collect draft references from David and Christine
- [ ] **June 8 (morning):** CC-01 (References Compilation) — format in APA 7, cross-check, finalize
- [ ] **June 8 (afternoon):** CC-04 (Citation Cross-Check) — verify every citation has a reference and vice versa

---

## Communication Responsibilities

1. **Daily Progress Updates:**
   - Post brief updates in group Discord
   - Example: "Finished C1-04 local context paragraph (14 sentences, 6 citations). Starting C1-10 research questions."
   - Flag blockers immediately

2. **Coordinate with Christine:**
   - Ask Christine for ISPSC context by June 2 (she's enrolled there)
   - What you need: Guidance Office processes, Registrar workflows, infrastructure constraints, peak admission challenges

3. **Coordinate with David:**
   - Wait for David's C1-09 (Objectives) before starting C1-10 (Research Questions)
   - Ask David for objectives by June 3
   - Ask David for help if unsure about task directions

4. **Collect References for CC-01:**
   - Ask David and Christine for their draft references by June 7
   - David has: C1-01, C1-02, C1-03, C2-01, C2-02, C2-03, C2-04, C2-06, C2-07
   - Christine has: C2-05 (Population and Locale of the Study)
   - You have: C1-04, C1-10, C1-12

5. **Ask for Help Early:**
   - If you're stuck finding sources, ask David or Christine for search term suggestions
   - If unsure about APA formatting, consult `guides/GUIDE-1-FORMATTING.md` or ask David
   - Don't guess — ask and get it right the first time

---

## Teammate Awareness: Who Has What

### Chapter 2 METHODOLOGY Tasks (NOT your responsibility)

All Chapter 2 tasks are now METHODOLOGY tasks (not literature review). These are assigned as follows:

- **David:** C2-01 (Research Design), C2-02 (Software Model), C2-03 (Project Plan), C2-04 (Project Assignment), C2-06 (Research Instruments), C2-07 (Data Analysis)
- **Christine:** C2-05 (Population and Locale of the Study)

You do NOT need to write anything for Chapter 2. However, any research you previously gathered for related systems review can still be useful — see the note below.

### Reusing Previous Related Systems Research

> If you previously began researching related admission systems, comparison platforms, or assessment tools for what was formerly C2-06, do not discard that work. Relevant findings about ISPSC's operational environment, comparable Philippine SUCs, or regional ICT adoption can be woven into **C1-04 (Background P4 — Local Context)** as supporting evidence in sentences 12-15. This strengthens the local context paragraph by showing awareness of what similar institutions are doing.

---

## Your Strengths (Lean Into These)

Based on your preferred tasks:

- **Detail-oriented work:** You prefer focused tasks with clear deliverables (references, citations)
- **Research skills:** You can use Google Scholar and evaluate sources
- **Organization:** You can compile and alphabetize reference lists systematically
- **Quality assurance:** You can spot inconsistencies in citations and references

Focus on what you're good at: research, organization, and quality checks.

---

## What to Avoid (Tasks Assigned to Others)

- Background global and national context paragraphs — David's tasks now
- Formatting QA — David's task (CC-02)
- All Chapter 2 METHODOLOGY tasks — David (C2-01 through C2-04, C2-06, C2-07) and Christine (C2-05)
- IPO diagram, objectives, scope, synthesis, clinching statement — David's tasks
- OMR/CV literature review — Christine's tasks

Your focus: Local context (with Christine's guidance), research questions, significance of the study, references compilation, citation cross-check.

---

## Quick Reference: Your Guides

- Formatting: `guides/GUIDE-1-FORMATTING.md` (especially APA 7 references, margins, paragraph rules)
- Chapter 1 Content: `guides/GUIDE-2-CHAPTER1-CONTENT.md` (Background P4, Research Questions)
- Chapter 2 Content: `guides/GUIDE-3-CHAPTER2-CONTENT.md` (METHODOLOGY structure, References)
- System Features: `SYSTEM_FEATURES.md` (to understand SecureCAT's impact on ISPSC)
- Existing vs Planned: `research/Existing_and_Planned_Features.md` (baseline and research features)
- Task Distribution: `TASK_DISTRIBUTION_PLAN.md`

---

## Success Criteria for You

- [ ] C1-04 (Local Context) completed by June 5 — 12-15 sentences, 5+ APA citations, covers manual workflow, infrastructure constraints, Guidance/Registrar challenges, compliance pressures.
- [ ] C1-10 (Research Questions) completed by June 4 — numbered list covering all 4 dimensions (operational, role-based, security, resilience).
- [ ] C1-12 (Significance of the Study) completed by June 5 — 6 paragraphs in paragraph form only, system-specific beneficiary groups (Registrar Office staff, Guidance Office counselors, Proctors/Test Administrators, Applicants/Examinees, ISPSC Administration, Future Researchers), highlights DPA compliance, operational continuity, institutional reporting value.
- [ ] Draft references submitted by June 7 (from yourself, David, Christine).
- [ ] CC-01 (References Compilation) completed by June 8 — APA 7th Edition, alphabetized, every citation matched to a reference and vice versa, all sources 2022-2026.
- [ ] CC-04 (Citation Cross-Check) completed by June 8 — cross-check report organized by issue category.
- [ ] No bullet points, no bold body text, paragraph indent 5 spaces in any deliverable.

---

## Instructor Submission Instructions

> To all groups with approved title, construct your manuscript with the following contents:
> Title page, Table of Contents, Chapter 1, Chapter 2, Appendices (letter to conduct, Use Case Diagram).
> To check the formatting and template, run your document using your account in the docuCheck system as you have created in Activity 1.
> Attach here the following: Brandname.docx, Proof of running the document in the docuCheck system.
>
> **Deadline:** June 10, 2026
> **Mode:** Google Classroom (or physical copy)
> **Margins:** Top 1.5", Left 1.5", Right/Bottom 1.0"
> **Paper:** A4

---

You are the local context researcher, references specialist, and citation quality assurer. Your attention to detail will ensure the References list is complete and accurate. Coordinate with Christine for ISPSC context, and collect references from all team members for CC-01.
