# Member Task Direction — Jaypee
## Team Member — Background & QA Tasks

> **Role:** Background paragraphs (global/national), research questions, related systems review, formatting QA, citation cross-check
> **Total Assigned Tasks:** 5 tasks
> **Estimated Effort:** 15-23 hours
> **Focus:** Academic writing, web research, quality assurance

---

## Your Tasks at a Glance

### Chapter 1
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C1-01 | Background P1 — Core Problem Statement | 2-3h | Jun 4 | None |
| C1-02 | Background P2 — Global Context | 4-6h | Jun 5 | None |
| C1-03 | Background P3 — National Context (PH) | 4-6h | Jun 5 | None |
| C1-10 | Research Questions | 1-2h | Jun 4 | C1-09 (David's task) |

### Chapter 2
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C2-06 | Review of Related Systems | 4-6h | Jun 6 | None |

### Cross-Cutting
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| CC-02 | Formatting QA Review | 2-3h | Jun 8 | All writing tasks |
| CC-04 | Citation Cross-Check | 1-2h | Jun 8 | All writing tasks, CC-01 |

---

## Detailed Task Directions

### Week 1 (June 1-4): Core Problem & Research Questions

#### C1-01: Background P1 — Core Problem Statement (June 4)
**Priority:** HIGH — First paragraph of Chapter 1, sets the tone

**What to Do:**

1. **Understand the Core Problem** (30 min):
   - Read `SYSTEM_FEATURES.md` to understand what SecureCAT does
   - **Identify the exact technical problem:**
     - ISPSC Tagudin currently handles admission testing manually
     - No role-based coordination between Guidance Office and Registrar
     - Paper-based test routing, manual scoring, no audit trails
     - Risk of errors, delays, and security issues
   - **Observable symptoms:**
     - Test papers manually routed between offices
     - Manual scoring using answer keys
     - No role-based access (anyone can access anything)
     - No audit trail of who did what

2. **Write Problem Paragraph** (60-90 min):
   - **10-15 sentences** in your own words
   - **NO CITATIONS ALLOWED** — P1 is citation-free
   - **Structure:**
     - Sentences 1-3: Name the observable symptoms (manual processes, inefficiencies)
     - Sentences 4-7: Identify the technical root cause (no role-based system, manual coordination)
     - Sentences 8-10: Explain the consequences (errors, delays, security risks)
     - Sentences 11-15: State what technical intervention is needed (role-based admission testing system)
   - **Key terms to include:**
     - "manual admission testing"
     - "role-based access control"
     - "Guidance Office"
     - "Registrar"
     - "test security"
     - "audit trail"
   - **CRITICAL:** Make it sound like an IT/system paper, NOT public administration
   - **Example:** "The current admission testing process at ISPSC Tagudin relies on manual coordination between the Guidance Office and Registrar, with test papers physically routed between offices and scoring performed manually without role-based access controls or audit trails. This manual approach creates vulnerabilities in test security, delays in result processing, and no accountability mechanism for tracking test-related activities. A digital intervention is needed to automate test routing, enforce role-based permissions, and provide secure audit logging."

3. **Review & Refine** (30 min):
   - Check paragraph has 10-15 sentences
   - Verify NO citations (P1 is the only citation-free paragraph)
   - Ensure IT/system framing (not management/admin framing)

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 1, Paragraph 1

**Deliverable:** `C1-01_Jaypee_Core_Problem.md` with 1 paragraph (10-15 sentences), citation-free

---

#### C1-10: Research Questions (June 4)
**Priority:** MEDIUM — Aligns with objectives

**What to Do:**

1. **Wait for David's C1-09 (Objectives)** (due June 3):
   - Ask David for his objectives once completed
   - Each objective should have a corresponding research question

2. **Convert Objectives to Questions** (30-60 min):
   - Read David's C1-09 specific objectives
   - Convert each objective to question form
   - **Standard 3-question structure:**
     - RQ1: What are the existing processes...? (Aligns with Objective 1: Identify)
     - RQ2: What features and functionalities...? (Aligns with Objective 2: Develop)
     - RQ3: What is the usability level of...? (Aligns with Objective 3: Evaluate)
   - **Example:**
     - If Objective 1 is: "To identify the existing manual processes and requirements at ISPSC Tagudin..."
     - Then RQ1 is: "What are the existing manual admission testing processes and operational requirements at ISPSC Tagudin?"

3. **Refine Question Wording** (15-30 min):
   - Ensure questions are clear, specific, and answerable
   - Check that RQ3 uses "usability" (not "acceptability") to match SUS instrument
   - Verify questions align with Chapter 2 methodology

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 3 (objectives → questions alignment)

**Deliverable:** `C1-10_Jaypee_Research_Questions.md` with 3-item numbered question list

---

### Week 1 (June 1-5): Global & National Context

#### C1-02: Background P2 — Global Context (June 5)
**Priority:** HIGH — Global foundation with 5+ citations

**What to Do:**

1. **Research Global Admission Testing** (90 min):
   - Go to **Google Scholar** (scholar.google.com)
   - Search terms:
     - "admission testing automation"
     - "computer-based testing systems education"
     - "educational assessment software"
     - "role-based access educational systems"
   - **Filter settings:**
     - Date: Since 2022 (to get 2022-2026 range)
     - Sort by: Relevance
     - Include: Peer-reviewed articles only
   - **Target sources:** 5-7 sources covering:
     - Global trends in admission testing digitization
     - Efficiency studies (manual vs. automated systems)
     - Security architectures for high-stakes testing
     - Role-based access in educational software

2. **Extract Key Findings** (60 min):
   - For each source, extract:
     - Main finding: What did they discover?
     - Methodology: How did they study it?
     - Relevance: Why is this relevant to SecureCAT?
   - **Group findings by theme:**
     - Theme 1: Efficiency gains from automation
     - Theme 2: Security and access control practices
     - Theme 3: Adoption patterns and trends

3. **Write Global Context Paragraph** (90-120 min):
   - **10-15 sentences** synthesizing global patterns
   - **Include minimum 5 in-text citations** (Author, Year)
   - **DO NOT summarize author-by-author** — synthesize by theme
   - **Structure:**
     - Sentences 1-3: Global trends in admission testing digitization (2-3 citations)
     - Sentences 4-7: Efficiency and security findings (2-3 citations)
     - Sentences 8-10: Adoption patterns and architectural debates (1-2 citations)
     - Sentences 11-15: How global context sets stage for local implementation (optional citations)
   - **Thematic synthesis example:**
     - ❌ AVOID: "Author A says X. Author B says Y. Author C says Z."
     - ✅ DO THIS: "Global studies demonstrate that automated admission testing systems reduce processing time by 60% compared to manual methods (Author A, 2024; Author B, 2023), while role-based access controls have become standard for secure test administration (Author C, 2025; Author D, 2024)."

4. **Compile Draft References** (30 min):
   - Create APA 7 draft references for all 5+ sources
   - Format: Author, A. A. (Year). Title. *Source*, vol(issue), pages. DOI/URL
   - Save for CC-01 (Christine will compile final references)
   - **Example:**
     - Smith, J. A. (2024). Automated admission testing systems. *Journal of Educational Technology, 15*(3), 45-67. https://doi.org/10.1234/example

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 1, Paragraph 2

**Deliverable:** `C1-02_Jaypee_Global_Context.md` with 1 paragraph (10-15 sentences, 5+ citations) + draft APA references

---

#### C1-03: Background P3 — National Context (Philippines) (June 5)
**Priority:** HIGH — Philippine context with 5+ citations

**What to Do:**

1. **Research Philippine Education & Admission Systems** (90 min):
   - Go to **Google Scholar**
   - Search terms:
     - "Philippines college admission automation"
     - "CHED admission policies"
     - "state universities ICT systems"
     - "Data Privacy Act education Philippines"
   - **Filter settings:**
     - Date: Since 2022
     - Sort by: Relevance
   - **Target sources:** 5-7 sources covering:
     - CHED policies on digital transformation
     - State university (SUC) admission testing practices
     - Data privacy in education (RA 10173 context)
     - ICT initiatives in Philippine education

2. **Extract Policy & Implementation Details** (60 min):
   - For each source, extract:
     - Specific mandates: CHED memoranda, RA citations, agency directives
     - Current practices: How do Philippine SUCs handle admissions?
     - Implementation gaps: What's not being done yet?
     - Privacy/compliance: How is RA 10173 applied?
   - **Group by theme:**
     - Theme 1: National mandates and policies
     - Theme 2: Current SUC practices
     - Theme 3: Digital transformation efforts

3. **Write National Context Paragraph** (90-120 min):
   - **10-15 sentences** connecting policy to practice
   - **Include minimum 5 in-text citations** (Author, Year)
   - **Structure:**
     - Sentences 1-3: National mandates (CHED, RA citations) (2-3 citations)
     - Sentences 4-7: Current SUC practices (1-2 citations)
     - Sentences 8-10: Digital transformation and ICT efforts (1-2 citations)
     - Sentences 11-15: How national context creates need for SecureCAT at ISPSC (optional citations)
   - **Be specific about policies:**
     - "CHED Memorandum XX-2023 emphasizes..."
     - "Republic Act 10173 (Data Privacy Act of 2012) requires..."
     - "State universities have been directed to adopt..."

4. **Compile Draft References** (30 min):
   - Create APA 7 draft references for all 5+ sources
   - For government sources (CHED, RA), use:
     - **Government document:** Agency Name. (Year). *Title* (Publication No.). Publisher. URL
   - Save for CC-01

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 1, Paragraph 3

**Deliverable:** `C1-03_Jaypee_National_Context.md` with 1 paragraph (10-15 sentences, 5+ citations) + draft APA references

---

### Week 2 (June 6): Related Systems Review

#### C2-06: Review of Related Systems (June 6)
**Priority:** MEDIUM — Shows you know what exists vs. what SecureCAT does

**What to Do:**

1. **Identify Related Systems** (60-90 min):
   - Search for existing systems similar to SecureCAT:
     - "admission testing systems"
     - "guidance office software"
     - "registrar information systems"
     - "computer-based testing software"
   - **Target: 3-5 systems** (commercial or academic)
   - For each system, identify:
     - System name
     - Key features
     - Technology/approach
     - Limitations (what it doesn't do)
   - **Include SecureCAT in comparison** (you're comparing against existing systems)

2. **Create Comparison Table** (60-90 min):
   - Create a markdown table with 4 columns:
     - **System Name**
     - **Features** (What does it do?)
     - **Technology** (What's it built with?)
     - **Limitations** (What's missing?)
   - **Example:**
     | System Name | Features | Technology | Limitations |
     |-------------|----------|------------|-------------|
     | System A | Test scheduling, scoring | Web-based, no offline mode | No role-based access, online-only |
     | System B | Admission management | Mobile app, cloud | No offline testing, no PWA |
     | SecureCAT | Role-based testing, offline-first PWA, AI Companion | Laravel, Svelte, PWA | Single-site (ISPSC only) |

3. **Write Narrative** (60-90 min):
   - **2-3 paragraphs** summarizing related systems
   - **Structure:**
     - Paragraph 1: Summarize existing systems and their capabilities
     - Paragraph 2: Identify gaps — what are existing systems NOT doing?
     - Paragraph 3: How SecureCAT fills the gap (role-based + offline-first + AI Companion)
   - **Key gap to highlight:** No existing system combines role-based multi-office coordination + offline-first PWA + AI Companion
   - **Include citations** if sources exist for compared systems (optional for this task)

**Reference:** `SYSTEM_FEATURES.md` (to know SecureCAT's features), `GUIDE-3-CHAPTER2-CONTENT.md` (related systems pattern)

**Deliverable:** `C2-06_Jaypee_Related_Systems.md` with comparison table + 2-3 paragraph narrative

---

### Week 2 (June 8): QA Tasks

#### CC-02: Formatting QA Review (June 8)
**Priority:** HIGH — Final formatting check before submission

**What to Do:**

1. **Review Formatting Guide** (30 min):
   - Read `guides/GUIDE-1-FORMATTING.md` thoroughly
   - Note the key rules:
     - Margins: 1 inch (2.54 cm) on all sides
     - Font: Times New Roman, 12pt throughout
     - Spacing: Double-spaced, no extra spaces between paragraphs
     - Headings: Level 1 (bold, centered), Level 2 (bold, left), Level 3 (bold, italics, left)
     - Tables: 1pt borders, caption above (left-aligned)
     - Figures: Caption below (bold, left-aligned)
     - Page numbers: Top-right corner, continuous

2. **Check All Formatting Rules** (60-90 min):
   - **Margins:** Check all pages have 1" margins (visual check in Word/Docs)
   - **Font:** Ensure all text is Times New Roman 12pt (headings can be bold/italics, but same font and size)
   - **Spacing:** Check all text is double-spaced with NO extra spaces between paragraphs
   - **Headings:** Verify correct heading hierarchy:
     - Level 1: CHAPTER 1. INTRODUCTION (bold, centered)
     - Level 2: Background of the Study (bold, left)
     - Level 3: Global Context (bold, italics, left)
   - **Tables/Figures:**
     - Tables have 1pt borders, captions above (left-aligned, italicized)
     - Figures have captions below (bold, left-aligned, "Figure X. Title")
   - **Page numbers:** Top-right, starting from page 1 or first content page

3. **Create Formatting Checklist** (15-30 min):
   - Mark which formatting rules passed/failed
   - List corrections needed:
     - Example: "Page 5: Extra space between paragraphs — remove"
     - Example: "Table 2: Border is 0.5pt — change to 1pt"
     - Example: "Heading 2.3: Should be bold, left-aligned — fix"
   - Save as `CC-02_Jaypee_Formatting_Checklist.md`

**Reference:** `GUIDE-1-FORMATTING.md`

**Deliverable:** `CC-02_Jaypee_Formatting_QA.md` with checklist of violations/corrections

---

#### CC-04: Citation Cross-Check (June 8)
**Priority:** MEDIUM — Ensures citations match references

**What to Do:**

1. **Wait for Christine's CC-01 (References)** (due June 8):
   - Christine will compile the final References list
   - Once she's done, get the final references from her

2. **Extract All In-Text Citations** (30-45 min):
   - Scan all chapters for in-text citations
   - Look for: (Author, Year) or (Author, Year, Page)
   - List each citation with its location (chapter, section)
   - Example: "Chapter 1, Background P2: (Smith, 2024)"

3. **Verify Against References** (30-45 min):
   - For each citation, check if it has a matching reference entry:
     - Example: Citation (Smith, 2024) → Reference: Smith, J. (2024). Title... ✓
   - For each reference, check if it's cited in text:
     - Go through references list alphabetically
     - Check if each entry is cited somewhere
   - Flag mismatches:
     - **Missing citations:** Reference exists but no in-text citation
     - **Orphaned citations:** In-text citation exists but no reference entry
     - **Citation/reference mismatches:** (Smith, 2024) in text but reference says (Smith, 2023)

4. **Create Cross-Check Report** (15-30 min):
   - List all issues found:
     - "Chapter 1, P3: (Doe, 2023) cited but reference says (Doe, 2024) — year mismatch"
     - "Chapter 2, Lit Review: (Johnson, 2025) cited but no reference entry — missing reference"
     - "References list: (Lee, 2022) exists but never cited in text — orphaned reference"
   - Save as `CC-04_Jaypee_Citation_Cross_Check.md`

**Reference:** `GUIDE-3-CHAPTER2-CONTENT.md` (References section requirements)

**Deliverable:** `CC-04_Jaypee_Citation_Cross_Check.md` with list of mismatches

---

## Your Week-by-Week Schedule

### Week 1 (June 1-4): Foundation Tasks
- [ ] **June 1-2:** C1-01 (Core Problem) — write citation-free paragraph
- [ ] **June 2-3:** Wait for David's C1-09 (Objectives)
- [ ] **June 3-4:** C1-10 (Research Questions) — convert objectives to questions
- [ ] **June 3-4:** C1-02 (Global Context) — research and write

### Week 1 (June 4-5): Context Tasks
- [ ] **June 4-5:** C1-03 (National Context) — research and write

### Week 2 (June 6): Related Systems
- [ ] **June 5-6:** C2-06 (Related Systems) — identify systems, create table

### Week 2 (June 8): QA Tasks
- [ ] **June 8:** CC-02 (Formatting QA) — check all formatting rules
- [ ] **June 8:** CC-04 (Citation Cross-Check) — verify citations vs. references

---

## Communication Responsibilities

1. **Daily Progress Updates:**
   - Post brief updates in group Discord
   - Example: "Finished C1-01 core problem paragraph. Starting C1-02 global research."
   - Flag blockers immediately

2. **Coordinate with David:**
   - Wait for David's C1-09 (Objectives) before starting C1-10 (Research Questions)
   - Ask David for objectives by June 3

3. **Submit Draft References:**
   - Your draft references for C1-02 and C1-03 should be ready by June 5
   - Send these to Christine for CC-01 (References Compilation)

4. **Ask for Help:**
   - If you're stuck finding sources, ask David or Christine for search term suggestions
   - If you're unsure about APA formatting, consult `GUIDE-1-FORMATTING.md` or ask Christine

---

## Your Strengths (Lean Into These)

Based on your assigned tasks:

- ✅ **Academic writing:** C1-01, C1-02, C1-03 all require paragraph-level writing
- ✅ **Web research:** C1-02, C1-03, C2-06 all require finding and evaluating sources
- ✅ **Attention to detail:** CC-02 and CC-04 require careful checking
- ✅ **Synthesis skills:** C1-02 and C1-03 require synthesizing multiple sources (not summarizing)

**Focus on:** Quality writing, thorough research, careful QA

---

## Key Writing Tips for You

1. **Synthesize, Don't Summarize:**
   - ❌ BAD: "Author A says X. Author B says Y. Author C says Z."
   - ✅ GOOD: "While global studies confirm X (Author A, 2024), local contexts require Y due to Z constraints (Author B, 2023), creating a gap that SecureCAT addresses."

2. **Use the Required Sources:**
   - Background paragraphs (P2, P3, P4) need minimum 5 citations each
   - All citations must be 2022-2026 only
   - Every in-text citation must have a reference entry

3. **Follow Paragraph Structure:**
   - Background paragraphs should be 10-15 sentences
   - Follow the funnel structure (broad → specific → gap)
   - C1-01 is the only citation-free paragraph

4. **Ask for Clarification:**
   - If you're unsure about a task, ask David immediately
   - Don't guess — ask and get it right the first time

---

## Quick Reference: Your Guides

- **Formatting:** `guides/GUIDE-1-FORMATTING.md` (especially for CC-02)
- **Chapter 1 Content:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` (Background structure, research questions)
- **Chapter 2 Content:** `guides/GUIDE-3-CHAPTER2-CONTENT.md` (related systems pattern)
- **System Features:** `SYSTEM_FEATURES.md` (to understand what SecureCAT does)
- **Task Distribution:** `team_meta/TASK_DISTRIBUTION_PLAN.md` (to see who's doing what)

---

## Success Criteria for You

✅ C1-01 (Core Problem) completed by June 4, citation-free  
✅ C1-02 (Global Context) completed by June 5 with 5+ citations  
✅ C1-03 (National Context) completed by June 5 with 5+ citations  
✅ C1-10 (Research Questions) completed by June 4  
✅ C2-06 (Related Systems) completed by June 6 with comparison table  
✅ CC-02 (Formatting QA) completed by June 8 with checklist  
✅ CC-04 (Citation Cross-Check) completed by June 8 with report  
✅ All draft references submitted to Christine by June 7  
✅ All sources are 2022-2026 only  

---

**You're the research and QA anchor. Your background paragraphs set the global and national context, and your QA tasks ensure the final manuscript is polished. Take your time with research, synthesize carefully, and be thorough in formatting checks.**
