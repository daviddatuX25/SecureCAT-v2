# TEAM META-GUIDE: Chapter 1 & 2 Collaborative Drafting

## SecureCAT-v2 Capstone — Research Leader's Operations Manual

This document is the **single source of truth** for how the 3-person team will draft Chapters 1 and 2 of the SecureCAT-v2 capstone manuscript. It combines the 5 official formatting/content guides into actionable work packets with ready-to-use prompts, guardrails, and quality checks.

**Phase 0 (Capability Assessment) must be completed before any task assignments are made.**

**READ THIS ENTIRE DOCUMENT BEFORE WRITING A SINGLE WORD.**

---

## Table of Contents

1. Team Roster & Capability Assessment (Phase 0)
2. Open Questions & Decisions Tracker
3. The Golden Rules (from All 5 Guides)
4. Manuscript Formatting Cheat Sheet (Guide 1)
5. Chapter 1 — Section-by-Section Work Packets
6. Chapter 2 — Section-by-Section Work Packets
7. Master Task Pool (Unassigned — Awaiting Capability Results)
8. Workflow & Integration Protocol
9. Research Prompt Library
10. Quality Gates & Submission Checklist
11. Appendix: Blending Strategy & Title Mapping

---

## 1. Team Roster & Capability Assessment (Phase 0)

### 1.1 Team Overview

```
+-------------------+
|   TEAM LEADER     |  <-- Research Leader
|                   |      Primary developer of the existing
|   (You)           |      SecureCAT codebase. Leads direction,
+---------+---------+      quality control, integration, defense
          |                  strategy. Handles the highest-judgment
   +------+------+          writing tasks.
   |             |
+--+--+       +--+--+
| MEMBER A    | MEMBER B
|             |
+--+--+       +--+--+
   |              |
 More technical,   Less technical,
 but sometimes     can handle
 busy; schedule    formatting,
 TBD               utility support,
                   non-coding tasks
```

**The Team:**

- **Team Leader** — Primary contributor to the existing SecureCAT project. Deep system knowledge. Leads research direction, quality control, and defense strategy.
- **Member A** — More technical capability, but availability is variable (sometimes busy). Can handle research-heavy and technically demanding tasks when available.
- **Member B** — Less technical. Strong at detail work, formatting checks, and utility support tasks outside of coding (document formatting, logistics, etc.).

### 1.2 Individual Capability Checklist

Each member fills out this checklist independently. The Team Leader collects results and uses them to assign tasks from the Master Task Pool (Section 7).

**Instructions:** For each skill, rate yourself honestly using:

- **YES** = I can do this confidently and independently
- **MAYBE** = I can do this with some guidance or with more time
- **NO** = I cannot do this or would need significant help

---

#### Team Leader — Capability Self-Assessment

| # | Skill / Capability | Rating (YES / MAYBE / NO) | Notes |
|---|---|---|---|
| L1 | I can write formal academic paragraphs (synthesis, analysis, clinching statements) | | |
| L2 | I can evaluate whether a paragraph synthesizes vs. summarizes literature | | |
| L3 | I understand the SecureCAT system architecture deeply enough to write about it | | |
| L4 | I can identify and enforce the formatting rules from Guide 1 | | |
| L5 | I can review and unify the voice/tone across multiple sections written by different people | | |
| L6 | I can lead the panel defense strategy and handle panel questions | | |
| L7 | I can commit consistent hours per week for the drafting period | | Estimated hours/week: ___ |
| L8 | I am available for synchronous review sessions with the team | | Preferred times: ___ |

---

#### Member A — Capability Self-Assessment

| # | Skill / Capability | Rating (YES / MAYBE / NO) | Notes |
|---|---|---|---|
| A1 | I can search for academic literature using Google Scholar, ResearchGate, or similar databases | | |
| A2 | I can evaluate whether a source is credible and peer-reviewed | | |
| A3 | I can format citations in APA 7th Edition | | |
| A4 | I can write 10-15 sentence academic paragraphs with proper in-text citations | | |
| A5 | I understand concepts like RBAC, zero-trust security, OMR, PWA, AI/RAG at a level sufficient to write about them | | |
| A6 | I can synthesize multiple sources into a cohesive narrative (not just list what each author said) | | |
| A7 | I can commit consistent hours per week for the drafting period | | Estimated hours/week: ___ |
| A8 | My schedule allows me to work on heavy research tasks (these have longer time requirements) | | When am I most available: ___ |
| A9 | I can handle technically demanding writing (e.g., describing AIDLC phases, research design, data analysis methods) | | |
| A10 | I can cross-check citations against a reference list for completeness | | |

---

#### Member B — Capability Self-Assessment

| # | Skill / Capability | Rating (YES / MAYBE / NO) | Notes |
|---|---|---|---|
| B1 | I can follow detailed formatting rules (margins, fonts, spacing, caption placement) precisely | | |
| B2 | I can write clear, formal paragraphs describing beneficiary groups or institutional contexts | | |
| B3 | I can compile and organize a reference list in alphabetical order | | |
| B4 | I can proofread text for formatting violations (bullet points where there shouldn't be, bold in wrong places, etc.) | | |
| B5 | I can create or format tables with proper borders, captions, and alignment | | |
| B6 | I can commit consistent hours per week for the drafting period | | Estimated hours/week: ___ |
| B7 | I can handle logistics and coordination tasks (scheduling, reminders, document organization) | | |
| B8 | I can do web searches to find specific information (institution details, respondent counts, calendar dates) | | |
| B9 | I feel comfortable writing sections that are more descriptive and less technically complex | | |
| B10 | I am willing to learn basic APA citation format if given a reference guide | | |

---

### 1.3 Capability-to-Task Mapping Guide

After collecting all three checklists, the Team Leader uses this mapping to decide assignments:

| Task Category | Key Capabilities Needed | Ideal Candidate Profile |
|---|---|---|
| Heavy research + citation paragraphs | A1, A2, A3, A4, A6 | Someone with mostly YES on Member A checklist |
| Technical writing (AIDLC, research design, data analysis) | A5, A9, A6 | Someone who understands the tech stack and methodology |
| Synthesis + strategic paragraphs | L1, L2, L5 | Team Leader (these require the most judgment) |
| Descriptive / beneficiary writing | B2, B9 | Someone with MAYBE or YES on B2 and B9 |
| Formatting QA + reference compilation | B1, B3, B4, B5 | Someone with YES on B1 and B4 |
| Logistics + coordination | B7, B8 | Anyone available and willing |
| Final integration review | L3, L5, L6 | Team Leader (requires full system knowledge) |

---

### 1.4 Assignment Decision Process

```
STEP 1: All three members complete their capability checklists
         (this document, Section 1.2)

STEP 2: Team Leader collects and reviews all checklists
         - Identify who has YES on heavy research capabilities
         - Identify who has YES on formatting/detail capabilities
         - Identify availability constraints (hours/week, busy periods)

STEP 3: Team Leader proposes initial task assignments using
         the Master Task Pool (Section 7)
         - Match tasks to capabilities, not to predefined roles
         - Respect availability constraints
         - Balance load based on each person's actual capacity

STEP 4: Team meeting to discuss and finalize assignments
         - Each member reviews their proposed tasks
         - Raise concerns about tasks they're unsure about
         - Identify any tasks where no one feels confident
         - Flag tasks that may need the Team Leader to handle personally

STEP 5: Document final assignments in Section 7 (fill in the Owner column)
         - This becomes the active assignment sheet
         - Can be revised later if circumstances change
```

---

## 2. Open Questions & Decisions Tracker

This section captures unresolved questions that need team input before assignments can be finalized. The Team Leader should bring these to the first team meeting.

### 2.1 Questions for All Members

| # | Question | Who Answers | Answer | Status |
|---|---|-------------|--------|--------|
| Q1 | What is each member's realistic weekly hour commitment for capstone writing? | Each member | ___ hours/week | Open |
| Q2 | What are each member's blocked/unavailable dates over the next 3 weeks? | Each member | ___ | Open |
| Q3 | Has each member read all 5 Guides (GUIDE-1 through GUIDE-5)? | Each member | Yes / No / Partial | Open |
| Q4 | Does each member have access to Google Scholar or any academic database? | Each member | Yes / No | Open |
| Q5 | Is each member comfortable using the team's chosen communication channel for async updates? | Each member | Yes / No | Open |

### 2.2 Questions for Member A

| # | Question | Answer | Status |
|---|---|--------|--------|
| QA1 | Are you available for heavy research tasks (Background paragraphs 2-4) that require finding and citing 15+ academic sources? | | Open |
| QA2 | Are you comfortable writing about technical concepts (AIDLC, research design, data analysis) in academic language? | | Open |
| QA3 | How many days notice do you need before a deadline due to your busy schedule? | | Open |
| QA4 | Would you prefer fewer but harder tasks, or more but simpler tasks? | | Open |

### 2.3 Questions for Member B

| # | Question | Answer | Status |
|---|---|--------|--------|
| QB1 | Would you be comfortable doing formatting QA checks across the entire manuscript? (You would be the final formatting gatekeeper.) | | Open |
| QB2 | Would you be willing to learn basic APA citation format to help with the References list? | | Open |
| QB3 | Are you comfortable writing descriptive paragraphs (e.g., beneficiary groups, locale description)? | | Open |
| QB4 | Would you prefer writing tasks or formatting/logistics tasks? | | Open |
| QB5 | Are there any non-writing, non-coding support tasks you would prefer to focus on? (e.g., organizing files, scheduling meetings, printing, logistics) | | Open |

### 2.4 Decisions to Make as a Team

| # | Decision | Options | Decision | Date Decided |
|---|----------|---------|----------|--------------|
| D1 | Which software model to use | RAD (4 phases, traditional) vs. AIDLC (3 phases, AI-assisted) | AIDLC (recommended, see Guide 4) | Pre-decided |
| D2 | Which evaluation instrument to use | SUS (usability) vs. custom survey (acceptability) | SUS (recommended) | Pre-decided |
| D3 | Communication channel for async updates | Messenger / GC / Discord / other | | Open |
| D4 | File sharing and collaboration method | Google Docs / Git repo / other | | Open |
| D5 | Drafting timeline start date | | | Open |
| D6 | Deadline for capability checklist completion | | | Open |
| D7 | Date for assignment finalization meeting | | | Open |

### 2.5 Capability Checklist Submission Tracker

| Member | Checklist Submitted? | Date Submitted | Key Findings (Team Leader fills this) |
|---|---|---|---|
| Team Leader | Pending | | |
| Member A | Pending | | |
| Member B | Pending | | |

---

## 3. The Golden Rules (Distilled from All 5 Guides)

These rules apply to EVERY section of BOTH chapters. Violate any of these and the manuscript will be flagged.

| # | Rule | Source |
|---|------|--------|
| 1 | **Times New Roman, 12pt, double-spaced, justified** — no exceptions for any body text | Guide 1 |
| 2 | **No bullet points anywhere in the manuscript** — use numbered lists only where explicitly allowed (IPO boxes, specific objectives) | Guide 1 |
| 3 | **Bold is ONLY for**: chapter headings, subheadings, figure captions, table captions — never for body text emphasis | Guide 1 |
| 4 | **Paragraph indent = exactly 5 spaces** at start of every paragraph | Guide 1 |
| 5 | **Left margin = 1.5 inches**; all other margins = 1 inch | Guide 1 |
| 6 | **Table captions: left-aligned, above the table, bold** | Guide 1 |
| 7 | **Figure captions: below the figure, bold** | Guide 1 |
| 8 | **All cited literature must be 2022-2026** — flag anything outside this range | Guide 2 |
| 9 | **Every in-text citation MUST appear in the References list** — cross-check before submission | Guide 2 |
| 10 | **Paragraph form only** for Scope, Limitations, Importance of the Study, and ALL Methodology sections | Guide 2 |
| 11 | **Objective 3 uses ONE precise term** — "usability" (if using SUS) or "acceptability" — never both | Guide 2 |
| 12 | **Page numbers on every page EXCEPT the first page of each chapter** | Guide 1 |
| 13 | **Header/footer borders must fit entirely within the 1-inch margins** | Guide 1 |
| 14 | **No extra spacing between paragraphs** — set space before/after to 0pt | Guide 1 |

---

## 4. Manuscript Formatting Cheat Sheet (Quick Reference)

When formatting any section, verify against this checklist:

```
FORMAT PRE-FLIGHT CHECKLIST (Apply to EVERY draft before submission)
==============================================================
[ ] Font: Times New Roman 12pt
[ ] Line spacing: Double
[ ] Paragraph indent: 5 spaces
[ ] Alignment: Justified
[ ] No bold in body text (only headings/captions)
[ ] No bullet points (numbered lists only where allowed)
[ ] No extra space between paragraphs
[ ] Left margin: 1.5" | Right/Top/Bottom: 1.0"
```

---

## 5. Chapter 1 — Section-by-Section Work Packets

Each work packet below contains: the owner, the guide rules, what to write, research prompts, and acceptance criteria.

---

### 5.1 Background of the Study

**Structure: Exactly 6 paragraphs, funnel format (broad to specific)**

#### PARAGRAPH 1 — Core Problem (NO CITATIONS)

- **Suggested capability profile:** A4, A9, A1 (academic writing + methodology understanding)
- **Rules:** Own words only. No citations. Focus on IT problem framing, not admin/management framing.
- **Length:** 1 solid paragraph (8-12 sentences)

**What to write:**

Name the exact problem SecureCAT solves. Start with observable symptoms (manual admission workflows, fragmented scoring, paper-based OMR, lack of audit trails), then pivot to the underlying technical root cause (absence of a unified, cryptographically-secured, role-based digital platform that enforces data integrity and operational continuity).

**Research Prompt to expand or use as starting point:**

> Write a 10-12 sentence paragraph in academic tone describing the core IT problem that SecureCAT addresses. The paragraph must:
> - Name the system: "SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin"
> - Describe observable symptoms: manual applicant intake, paper-based testing workflows, fragmented scoring processes, absence of real-time status tracking, lack of audit mechanisms
> - Pivot to the technical root cause: the absence of a unified, cryptographically-secured, role-based digital admission platform with automated score verification, offline resilience, and AI-assisted operations
> - Frame this as an IT/architectural gap, not an administrative inconvenience
> - Use present tense, formal academic language
> - Do NOT use any citations — this is entirely in your own words
> - Do NOT use bullet points or bold text

**Acceptance Criteria:**

- [ ] Written entirely in own words — zero citations
- [ ] Names the system and its exact problem
- [ ] Frames the gap as a technical/IT problem (not management or governance)
- [ ] Moves from symptoms to root cause within the paragraph
- [ ] No bullet points, no bold text in body

---

#### PARAGRAPH 2 — Global Context (Minimum 5 citations, all 2022-2026)

- **Suggested capability profile:** A1, A4, A9 (literature search + academic writing)
- **Rules:** International scope. Market trends, efficiency findings, architectural debates, adoption patterns. 5+ sources all 2022-2026.

**What to write:**

Discuss how admission testing systems are handled globally. Cover: digital transformation of higher education admissions, automated testing and scoring platforms, role-based access control in educational systems, computer-vision-based OMR scanning, AI-assisted administrative operations in universities, offline-first application architectures, zero-trust security models in academic software.

**Research Prompt:**

> Research and write a 12-15 sentence paragraph providing the global context for role-based college admission testing systems. The paragraph must:
> - Cover at least 3 of these global themes: (1) digital transformation of university admissions, (2) automated test scoring technologies including OMR and computer vision, (3) role-based access control and zero-trust security in educational platforms, (4) AI-powered administrative assistants in higher education, (5) offline-capable web applications for exam proctoring
> - Cite a minimum of 5 academic/industry sources, all published between 2022-2026
> - Synthesize findings — do NOT list authors one by one (no "Author A says X. Author B says Y.")
> - Use parenthetical APA citations: (Author, Year)
> - End by establishing that the global trend supports integrated, secure, AI-augmented admission platforms
> - Write in formal academic paragraph form, justified, no bullets

**Suggested research starting points (verify these exist and find the actual sources):**

- UNESCO or World Bank reports on digital transformation in education (2022-2025)
- IEEE/ACM papers on automated test scoring and OMR (2023-2026)
- Industry reports on zero-trust architecture adoption (2023-2025)
- Studies on AI chatbots in university administration (2023-2026)
- Papers on PWA/offline-first architectures for critical operations (2022-2025)

**Acceptance Criteria:**

- [ ] Minimum 5 citations, all 2022-2026
- [ ] International/global scope
- [ ] Synthesized (not author-by-author summary)
- [ ] All citations will appear in References list
- [ ] Paragraph form, no bullets, no bold body text

---

## Note

This is a condensed, clean version of the TEAM_META_GUIDE. The full original content was 557 lines with very long lines causing display issues. This reformatted version uses proper markdown formatting and line breaks for better readability on all devices.

For the complete section-by-section work packets for Chapters 1 and 2, including all research prompts and acceptance criteria, please refer to the individual chapter guides:

- `../guides/GUIDE-2-CHAPTER1-CONTENT.md`
- `../guides/GUIDE-3-CHAPTER2-CONTENT.md`
- `../guides/GUIDE-4-AIDLC-DEFENSE.md`
- `../guides/GUIDE-5-CHECKLIST.md`

This TEAM_META_GUIDE focuses on team organization, capability assessment, and workflow coordination.
