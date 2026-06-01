# Member Task Direction — Jaypee
## Team Member — Background, Research Questions, Related Systems & QA

> **Role:** Background paragraphs (core problem, global context, national context), research questions, related systems review, formatting QA, citation cross-check
> **Total Assigned Tasks:** 7 tasks (C1-01, C1-02, C1-03, C1-10, C2-06, CC-02, CC-04)
> **Estimated Effort:** 15-23 hours
> **Focus:** Academic writing, web research, quality assurance
> **Hard Deadline:** June 10, 2026

---

## Your Tasks at a Glance

### Chapter 1
| Task ID | Task | Sentences | Citations | Hours | Due | Dependencies |
|---------|------|-----------|-----------|-------|-----|--------------|
| C1-01 | Background P1 — Core Problem Statement | **8-12** | **None** | 2-3h | Jun 4 | None |
| C1-02 | Background P2 — Global Context | **12-15** | **Min 5 APA** | 4-6h | Jun 5 | None |
| C1-03 | Background P3 — National Context (PH) | **12-15** | **Min 5 APA** | 4-6h | Jun 5 | None |
| C1-10 | Research Questions | — | None | 1-2h | Jun 4 | C1-09 (David) |

### Chapter 2
| Task ID | Task | Format | Citations | Hours | Due | Dependencies |
|---------|------|--------|-----------|-------|-----|--------------|
| C2-06 | Review of Related Systems | 2-3 paras + table | **Min 5 APA** | 4-6h | Jun 6 | None |

### Cross-Cutting
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| CC-02 | Formatting QA Review | 2-3h | Jun 8 | All writing tasks |
| CC-04 | Citation Cross-Check | 1-2h | Jun 8 | All writing tasks, CC-01 |

---

## ⚠️ CRITICAL RULES — Read Before Starting Any Task

1. **Paragraph sentence counts are FIXED.** Do not deviate:
   - C1-01: exactly **8-12 sentences** (NOT 10-15)
   - C1-02: exactly **12-15 sentences**
   - C1-03: exactly **12-15 sentences**
   - If someone tells you "10-15 sentences," ignore them. Follow this guide.

2. **No bullet points ANYWHERE in any manuscript text.** All content must be in paragraph form.

3. **No bold body text.** Bold is ONLY for structural labels (headings, subheadings, figure captions, table captions).

4. **All citations must be 2022-2026.** No exceptions.

5. **Synthesize, never summarize.** FORBIDDEN pattern: "Author A says X. Author B says Y." Weave findings into themes instead.

---

## Detailed Task Directions

---

### C1-01: Background P1 — Core Problem Statement (June 4)

**Priority:** HIGH — First paragraph of Chapter 1, sets the tone for the entire manuscript

**Target:** 1 paragraph, **8-12 sentences**, citation-free

#### Step 1: Understand the Core Problem (30 min)

Read `SYSTEM_FEATURES.md` and `drafts/Existing_and_Planned_Features.md` to understand what SecureCAT does.

The core problem is this: ISPSC Tagudin currently handles admission testing through manual, paper-based workflows with no unified digital platform. Specifically:

- **Manual admission workflows:** Test papers are physically routed between the Guidance Office and Registrar with no digital coordination.
- **Fragmented scoring:** Scores are computed manually using answer keys; there is no automated scoring pipeline.
- **Paper-based OMR:** Answer sheets are processed by hand (no computer-vision or automated OMR scanning).
- **Lack of audit trails:** No record of who accessed what data, who changed a score, or when an action was performed.
- **No role-based access control:** Anyone with system access can view or modify anything — no permission boundaries between offices.

The **technical root cause** is the **absence of a unified, cryptographically-secured, role-based digital platform** for admission testing.

#### Step 2: Write the Problem Paragraph (60-90 min)

Write **8-12 sentences** in your own words. **NO CITATIONS ALLOWED** — this is the only citation-free paragraph.

**Structure:**
- **Sentences 1-4:** Name the observable symptoms — manual admission workflows, paper-based test routing between Guidance Office and Registrar, manual scoring using answer keys, fragmented processes, no audit trails, no role-based access.
- **Sentences 5-8:** Pivot to the technical root cause — the absence of a unified, cryptographically-secured, role-based digital platform. Explain why the current manual approach creates vulnerabilities in test security, delays in result processing, and no accountability mechanism.
- **Sentences 9-12 (if needed):** State what technical intervention is needed — a role-based admission testing system with automated scoring, offline resilience, and cryptographic data integrity.

**Key terms to include:**
- "manual admission testing"
- "role-based access control"
- "Guidance Office"
- "Registrar"
- "test security"
- "audit trail"
- "cryptographically-secured"
- "unified digital platform"

**Critical framing rule:** Make it sound like an **IT/system paper**, NOT a public administration or management paper. State the actual technical gap.

**Example opening pattern:** "The current admission testing process at ISPSC Tagudin relies on manual coordination between the Guidance Office and Registrar, with test papers physically routed between offices and scoring performed manually without role-based access controls or audit trails."

#### Step 3: Review & Refine (30 min)

- [ ] Count sentences: must be **8-12** (not 7, not 13)
- [ ] Verify NO citations — P1 is the only citation-free paragraph
- [ ] Verify IT/system framing (not management/admin framing)
- [ ] Verify no bullet points or bold body text
- [ ] Verify the paragraph names the specific problem SecureCAT solves

**Deliverable:** `C1-01_Jaypee_Core_Problem.md` — 1 paragraph, 8-12 sentences, citation-free

---

### C1-02: Background P2 — Global Context (June 5)

**Priority:** HIGH — Global foundation with 5+ citations

**Target:** 1 paragraph, **12-15 sentences**, minimum 5 APA citations (all 2022-2026)

#### Step 1: Research Global Admission Testing (90 min)

Go to **Google Scholar** (scholar.google.com). Search terms:
- "digital transformation higher education admissions"
- "automated testing and scoring platforms education"
- "role-based access control educational systems"
- "computer vision OMR scanning assessment"
- "AI-assisted administrative operations universities"
- "offline-first architecture web applications"
- "zero-trust security models education"

**Filter:** Date 2022-2026, peer-reviewed articles only.

**Target:** 5-7 sources covering ALL of these topics (per TEAM_META_GUIDE):
- Digital transformation of higher education admissions
- Automated testing and scoring platforms
- RBAC in educational systems
- Computer-vision-based OMR scanning
- AI-assisted administrative operations
- Offline-first architectures
- Zero-trust security models

#### Step 2: Extract Key Findings (60 min)

For each source, extract:
- Main finding
- Methodology
- Relevance to SecureCAT
- Full citation details (author, year, journal, DOI/URL)

Group findings by theme (NOT by author):
- Theme 1: Efficiency gains from automated admission systems
- Theme 2: Security architectures (RBAC, zero-trust, cryptographic integrity)
- Theme 3: Emerging technologies (CV-based OMR, AI assistants, offline-first PWA)

#### Step 3: Write Global Context Paragraph (90-120 min)

Write **12-15 sentences** synthesizing global patterns. Include **minimum 5 in-text citations** (Author, Year).

**Structure:**
- **Sentences 1-3:** Global trends in digital transformation of higher education admissions (2-3 citations)
- **Sentences 4-7:** Automated testing/scoring platforms, RBAC in education, zero-trust security models (2-3 citations)
- **Sentences 8-11:** Emerging technologies — CV-based OMR, AI-assisted operations, offline-first architectures (1-2 citations)
- **Sentences 12-15:** How global context sets the stage for local implementation needs (optional citations)

**FORBIDDEN pattern:**
- ❌ "Author A says X. Author B says Y. Author C says Z."

**Required synthesis pattern:**
- ✅ "Global studies demonstrate that automated admission testing systems reduce processing time significantly compared to manual methods (Author A, 2024; Author B, 2023), while role-based access controls have become a standard security practice for test administration (Author C, 2025; Author D, 2024)."

#### Step 4: Compile Draft References (30 min)

Create APA 7 draft references for all 5+ sources. Format:
`Author, A. A. (Year). Title. *Source*, vol(issue), pages. DOI/URL`

Save draft references for CC-01 (Christine will compile the final list).

**Reference docs:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 1 Paragraph 2

**Deliverable:** `C1-02_Jaypee_Global_Context.md` — 1 paragraph (12-15 sentences, 5+ APA citations, all 2022-2026) + draft APA reference entries

---

### C1-03: Background P3 — National Context (Philippines) (June 5)

**Priority:** HIGH — Philippine context with 5+ citations

**Target:** 1 paragraph, **12-15 sentences**, minimum 5 APA citations (all 2022-2026)

#### Step 1: Research Philippine Education & Admission Systems (90 min)

Go to **Google Scholar**. Search terms:
- "CHED policies admission testing Philippines"
- "RA 10173 Data Privacy Act education"
- "state universities and colleges ICT systems Philippines"
- "digitalization SUCs Philippines"
- "e-governance initiatives Philippine higher education"
- "connectivity challenges Philippine higher education"

**Filter:** Date 2022-2026.

**Target:** 5-7 sources covering ALL of these topics (per TEAM_META_GUIDE):
- CHED policies on admission testing
- RA 10173 (Data Privacy Act of 2012) compliance for student data
- Digitalization efforts in SUCs (State Universities and Colleges)
- Government e-governance initiatives
- Connectivity challenges in Philippine higher education

#### Step 2: Extract Policy & Implementation Details (60 min)

For each source, extract:
- Specific mandates: CHED memoranda, RA citations, agency directives
- Current practices: How do Philippine SUCs handle admissions?
- Implementation gaps: What's not being done yet?
- Privacy/compliance: How is RA 10173 applied in educational contexts?

**MUST NAME SPECIFIC LEGISLATION, AGENCIES, OR PROGRAMS** — no vague references. Examples:
- "CHED Memorandum Order No. XX-2023"
- "Republic Act 10173 (Data Privacy Act of 2012)"
- "National Economic and Development Authority (NEDA)"
- "Department of Information and Communications Technology (DICT)"
- Specific government digital transformation programs

#### Step 3: Write National Context Paragraph (90-120 min)

Write **12-15 sentences** connecting policy to practice. Include **minimum 5 in-text citations**.

**Structure:**
- **Sentences 1-4:** National mandates and policies (CHED directives, RA 10173, government e-governance programs) (2-3 citations)
- **Sentences 5-8:** Current SUC practices and digitalization efforts (1-2 citations)
- **Sentences 9-11:** Connectivity challenges and infrastructure constraints in Philippine higher education (1-2 citations)
- **Sentences 12-15:** How national context creates the need for SecureCAT at ISPSC (bridge to local context)

**Be specific about policies:**
- "CHED Memorandum Order emphasizes digital transformation in SUC operations..."
- "Republic Act 10173 (Data Privacy Act of 2012) mandates that student data must be..."
- "The Department of Information and Communications Technology (DICT) has directed..."

#### Step 4: Compile Draft References (30 min)

Create APA 7 references. For government sources use:
`Agency Name. (Year). *Title* (Publication No.). Publisher. URL`

Save draft references for CC-01.

**Reference docs:** `guides/GUIDE-2-CHAPTER1-CONTENT.md` Section 1 Paragraph 3

**Deliverable:** `C1-03_Jaypee_National_Context.md` — 1 paragraph (12-15 sentences, 5+ APA citations, all 2022-2026) + draft APA reference entries

---

### C1-10: Research Questions (June 4)

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

### C2-06: Review of Related Systems (June 6)

**Priority:** MEDIUM — Demonstrates awareness of existing solutions vs. SecureCAT

**Target:** 2-3 paragraphs + comparison table, minimum 5 APA citations (all 2022-2026)

#### Step 1: Identify Related Systems (60-90 min)

Search for existing systems similar to SecureCAT. Per TEAM_META_GUIDE, review these categories:
- **Admission systems** (local and international)
- **Electronic assessment platforms**
- **OMR grading systems**
- **Document generation systems** in academic workflows

Search terms:
- "college admission testing system"
- "electronic assessment platform university"
- "OMR grading system software"
- "academic document generation system"
- "computer-based testing software education"

**Target:** 3-5 systems (commercial or academic). For each system, identify:
- System name and origin (local or international)
- Key features (what it does)
- Technology/approach (what it's built with)
- Limitations (what it doesn't do — this is critical)

#### Step 2: Create Comparison Table (60-90 min)

Create a table with these columns:

| System Name | Key Features | Technology/Approach | Strengths | Limitations |
|-------------|-------------|---------------------|-----------|-------------|

**Table formatting rules (per GUIDE-1):**
- Table caption: **left-aligned**, **above** the table, **bold**
- Example: `Table 1. Comparison of Related Admission and Assessment Systems`
- Border: **1pt line width**
- Include **SecureCAT** as the last row in the comparison

**Key gap to highlight in your analysis:** No existing system combines:
- Role-based multi-office coordination (Guidance + Registrar)
- Offline-first PWA architecture
- AI Companion / RAG Copilot
- Computer-vision-based OMR scanning
- HMAC cryptographic score integrity

#### Step 3: Write Narrative (60-90 min)

Write **2-3 paragraphs** with minimum 5 APA citations:

- **Paragraph 1:** Summarize existing admission systems and assessment platforms — their capabilities, technologies, and target markets. Cite sources where possible.
- **Paragraph 2:** Identify gaps — what are existing systems NOT doing? What features are missing across the board? Compare strengths and weaknesses against SecureCAT's feature set.
- **Paragraph 3:** How SecureCAT fills the gap. Position SecureCAT's unique combination of features as the differentiator.

**Deliverable:** `C2-06_Jaypee_Related_Systems.md` — 2-3 paragraphs with feature comparison table, minimum 5 APA citations (all 2022-2026)

---

### CC-02: Formatting QA Review (June 8)

**Priority:** HIGH — Final formatting check before submission

**Target:** Annotated manuscript with formatting corrections marked

#### Step 1: Review GUIDE-1 Formatting Rules (30 min)

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

#### Step 2: Check All Formatting Rules (60-90 min)

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

#### Step 3: Create Formatting Report (15-30 min)

List every violation found with location and correction needed:
- "Page 5: Extra space between paragraphs — remove (set space after to 0pt)"
- "Table 2: Border is 0.5pt — change to 1pt"
- "Section 2.3: Bullet points used in scope paragraph — convert to paragraph form"
- "Page 12: Left margin is 1.0" — change to 1.5""

**Deliverable:** `CC-02_Jaypee_Formatting_QA.md` — complete formatting checklist with violations and corrections

---

### CC-04: Citation Cross-Check (June 8)

**Priority:** MEDIUM — Ensures citation-reference integrity

**Target:** Cross-check report with flagged issues

#### Step 1: Wait for Christine's CC-01 (References) (due June 8)

Christine will compile the final References list. Get the final version from her before starting this task.

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

## Your Week-by-Week Schedule

### Week 1 (June 1-4): Foundation Tasks
- [ ] **June 1-2:** C1-01 (Core Problem) — write 8-12 sentence citation-free paragraph
- [ ] **June 2-3:** Wait for David's C1-09 (Objectives)
- [ ] **June 3-4:** C1-10 (Research Questions) — convert objectives to questions covering all 4 dimensions
- [ ] **June 3-4:** C1-02 (Global Context) — research and write 12-15 sentences with 5+ citations

### Week 1 (June 4-5): Context Tasks
- [ ] **June 4-5:** C1-03 (National Context) — research and write 12-15 sentences with 5+ citations

### Week 2 (June 6): Related Systems
- [ ] **June 5-6:** C2-06 (Related Systems) — identify 3-5 systems, create comparison table, write 2-3 paragraphs with 5+ citations

### Week 2 (June 8): QA Tasks
- [ ] **June 8 (morning):** CC-02 (Formatting QA) — check ALL formatting rules per GUIDE-1 (1.5" left margin!)
- [ ] **June 8 (afternoon):** CC-04 (Citation Cross-Check) — verify every citation has a reference and vice versa

---

## Communication Responsibilities

1. **Daily Progress Updates:**
   - Post brief updates in group Discord
   - Example: "Finished C1-01 core problem paragraph (10 sentences, no citations). Starting C1-02 global research."
   - Flag blockers immediately

2. **Coordinate with David:**
   - Wait for David's C1-09 (Objectives) before starting C1-10 (Research Questions)
   - Ask David for objectives by June 3
   - Ask David for help if unsure about task directions

3. **Submit Draft References:**
   - Your draft references for C1-02, C1-03, and C2-06 should be ready by June 6
   - Send these to Christine for CC-01 (References Compilation)

4. **Ask for Help Early:**
   - If you're stuck finding sources, ask David or Christine for search term suggestions
   - If unsure about APA formatting, consult `guides/GUIDE-1-FORMATTING.md` or ask Christine
   - Don't guess — ask and get it right the first time

---

## Your Strengths (Lean Into These)

- **Academic writing:** C1-01, C1-02, C1-03 all require paragraph-level writing
- **Web research:** C1-02, C1-03, C2-06 all require finding and evaluating sources
- **Attention to detail:** CC-02 and CC-04 require meticulous checking
- **Synthesis skills:** C1-02 and C1-03 require synthesizing multiple sources into cohesive narratives

**Focus on:** Quality writing, thorough research, careful QA

---

## Key Writing Tips

### 1. Synthesize, Don't Summarize
- ❌ BAD: "Author A says X. Author B says Y. Author C says Z."
- ✅ GOOD: "While global studies confirm that automated systems reduce processing time by 60% (Author A, 2024; Author B, 2023), local contexts face infrastructure barriers that demand offline-first architectures (Author C, 2025), creating a gap that SecureCAT addresses."

### 2. Paragraph Sentence Counts — Memorize These
| Task | Target | Citations |
|------|--------|-----------|
| C1-01 Core Problem | **8-12** | None |
| C1-02 Global Context | **12-15** | Min 5 |
| C1-03 National Context | **12-15** | Min 5 |
| C1-06 Clinching (David's task, for reference) | 8-10 | None required |

### 3. Follow Paragraph Structure (Funnel)
- Background paragraphs follow: broad → specific → gap
- C1-01 is the only citation-free paragraph
- Every other background paragraph needs minimum 5 citations from 2022-2026

### 4. Formatting Must-Memorize Rules
- Left margin: **1.5"** (NOT 1")
- No bullets anywhere
- Table captions: above, left-aligned, bold
- Figure captions: below, bold
- No bold body text
- Page numbers on every page except chapter first pages
- Paragraph indent: exactly 5 spaces

---

## Quick Reference: Your Guide Documents

- **Formatting:** `capstone/guides/GUIDE-1-FORMATTING.md` (especially for CC-02)
- **Chapter 1 Content:** `capstone/guides/GUIDE-2-CHAPTER1-CONTENT.md` (Background structure, research questions)
- **Chapter 2 Content:** `capstone/guides/GUIDE-3-CHAPTER2-CONTENT.md` (related systems pattern)
- **System Features:** `capstone/SYSTEM_FEATURES.md` (to understand what SecureCAT does)
- **Existing vs Planned:** `capstone/drafts/Existing_and_Planned_Features.md` (feature details)
- **Task Distribution:** `capstone/team_meta/TASK_DISTRIBUTION_PLAN.md` (who's doing what)
- **Official Task Inventory:** `capstone/team_meta/TEAM_META_GUIDE_Ch1_Ch2.md` (the authoritative source for all task specs)

---

## Success Criteria

- [ ] C1-01 (Core Problem) completed by June 4 — **8-12 sentences**, citation-free
- [ ] C1-02 (Global Context) completed by June 5 — **12-15 sentences**, minimum 5 APA citations (2022-2026)
- [ ] C1-03 (National Context) completed by June 5 — **12-15 sentences**, minimum 5 APA citations (2022-2026), specific legislation/agencies named
- [ ] C1-10 (Research Questions) completed by June 4 — numbered list, one-to-one with objectives, covering operational + role-based + security + resilience dimensions
- [ ] C2-06 (Related Systems) completed by June 6 — 2-3 paragraphs + comparison table, minimum 5 APA citations (2022-2026)
- [ ] CC-02 (Formatting QA) completed by June 8 — checklist with all GUIDE-1 rules verified (especially 1.5" left margin)
- [ ] CC-04 (Citation Cross-Check) completed by June 8 — cross-check report with all issues flagged
- [ ] All draft references submitted to Christine by June 7
- [ ] All sources are 2022-2026 only
- [ ] No bullet points in any manuscript text
- [ ] No bold body text in any manuscript text

---

**You're the research and QA anchor. Your background paragraphs set the global and national context, and your QA tasks ensure the final manuscript is polished. Take your time with research, synthesize carefully, and be thorough in formatting checks.**
