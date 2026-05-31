1|# TEAM META-GUIDE: Chapter 1 & 2 Collaborative Drafting
2|
3|## SecureCAT-v2 Capstone — Research Leader's Operations Manual
4|
5|This document is the **single source of truth** for how the 3-person team will draft Chapters 1 and 2 of the SecureCAT-v2 capstone manuscript. It combines the 5 official formatting/content guides into actionable work packets with ready-to-use prompts, guardrails, and quality checks.
6|
7|**Phase 0 (Capability Assessment) must be completed before any task assignments are made.**
8|
9|**READ THIS ENTIRE DOCUMENT BEFORE WRITING A SINGLE WORD.**
10|
11|---
12|
13|## TABLE OF CONTENTS
14|
15|1. Team Roster & Capability Assessment (Phase 0)
16|2. Open Questions & Decisions Tracker
17|3. The Golden Rules (from All 5 Guides)
18|4. Manuscript Formatting Cheat Sheet (Guide 1)
19|5. Chapter 1 — Section-by-Section Work Packets
20|6. Chapter 2 — Section-by-Section Work Packets
21|7. Master Task Pool (Unassigned — Awaiting Capability Results)
22|8. Workflow & Integration Protocol
23|9. Research Prompt Library
24|10. Quality Gates & Submission Checklist
25|11. Appendix: Blending Strategy & Title Mapping
26|
27|---
28|
29|## 1. TEAM ROSTER & CAPABILITY ASSESSMENT (Phase 0)
30|
31|### 1.1 Team Overview
32|
33|```
34| +-------------------+
35| |   TEAM LEADER     |  <-- Research Leader
36| |                   |      Primary developer of the existing
37| |   (You)           |      SecureCAT codebase. Leads direction,
38| +---------+---------+      quality control, integration, defense
39|           |                  strategy. Handles the highest-judgment
40|    +------+------+          writing tasks.
41|    |             |
42|+---+---+   +----+----+
43|| MEMBER A    | MEMBER B
44||             |
45|+---+---+     +----+----+
46|    |              |
47| More technical,   Less technical,
48| but sometimes     can handle
49| busy; schedule    formatting,
50| TBD               utility support,
51|                    non-coding tasks
52|```
53|
54|**The Team:**
55|- **Team Leader** — Primary contributor to the existing SecureCAT project. Deep system knowledge. Leads research direction, quality control, and defense strategy.
56|- **Member A** — More technical capability, but availability is variable (sometimes busy). Can handle research-heavy and technically demanding tasks when available.
57|- **Member B** — Less technical. Strong at detail work, formatting checks, and utility support tasks outside of coding (document formatting, logistics, etc.).
58|
59|### 1.2 Individual Capability Checklist
60|
61|Each member fills out this checklist independently. The Team Leader collects results and uses them to assign tasks from the Master Task Pool (Section 7).
62|
63|**Instructions:** For each skill, rate yourself honestly using:
64|- **YES** = I can do this confidently and independently
65|- **MAYBE** = I can do this with some guidance or with more time
66|- **NO** = I cannot do this or would need significant help
67|
68|---
69|
70|#### Team Leader — Capability Self-Assessment
71|
72|| # | Skill / Capability | Rating (YES / MAYBE / NO) | Notes |
73||---|---|---|---|
74|| L1 | I can write formal academic paragraphs (synthesis, analysis, clinching statements) | | |
75|| L2 | I can evaluate whether a paragraph synthesizes vs. summarizes literature | | |
76|| L3 | I understand the SecureCAT system architecture deeply enough to write about it | | |
77|| L4 | I can identify and enforce the formatting rules from Guide 1 | | |
78|| L5 | I can review and unify the voice/tone across multiple sections written by different people | | |
79|| L6 | I can lead the panel defense strategy and handle panel questions | | |
80|| L7 | I can commit consistent hours per week for the drafting period | | Estimated hours/week: ___ |
81|| L8 | I am available for synchronous review sessions with the team | | Preferred times: ___ |
82|
83|---
84|
85|#### Member A — Capability Self-Assessment
86|
87|| # | Skill / Capability | Rating (YES / MAYBE / NO) | Notes |
88||---|---|---|---|
89|| A1 | I can search for academic literature using Google Scholar, ResearchGate, or similar databases | | |
90|| A2 | I can evaluate whether a source is credible and peer-reviewed | | |
91|| A3 | I can format citations in APA 7th Edition | | |
92|| A4 | I can write 10-15 sentence academic paragraphs with proper in-text citations | | |
93|| A5 | I understand concepts like RBAC, zero-trust security, OMR, PWA, AI/RAG at a level sufficient to write about them | | |
94|| A6 | I can synthesize multiple sources into a cohesive narrative (not just list what each author said) | | |
95|| A7 | I can commit consistent hours per week for the drafting period | | Estimated hours/week: ___ |
96|| A8 | My schedule allows me to work on heavy research tasks (these have longer time requirements) | | When am I most available: ___ |
97|| A9 | I can handle technically demanding writing (e.g., describing AIDLC phases, research design, data analysis methods) | | |
98|| A10 | I can cross-check citations against a reference list for completeness | | |
99|
100|---
101|
102|#### Member B — Capability Self-Assessment
103|
104|| # | Skill / Capability | Rating (YES / MAYBE / NO) | Notes |
105||---|---|---|---|
106|| B1 | I can follow detailed formatting rules (margins, fonts, spacing, caption placement) precisely | | |
107|| B2 | I can write clear, formal paragraphs describing beneficiary groups or institutional contexts | | |
108|| B3 | I can compile and organize a reference list in alphabetical order | | |
109|| B4 | I can proofread text for formatting violations (bullet points where there shouldn't be, bold in wrong places, etc.) | | |
110|| B5 | I can create or format tables with proper borders, captions, and alignment | | |
111|| B6 | I can commit consistent hours per week for the drafting period | | Estimated hours/week: ___ |
112|| B7 | I can handle logistics and coordination tasks (scheduling, reminders, document organization) | | |
113|| B8 | I can do web searches to find specific information (institution details, respondent counts, calendar dates) | | |
114|| B9 | I feel comfortable writing sections that are more descriptive and less technically complex | | |
115|| B10 | I am willing to learn basic APA citation format if given a reference guide | | |
116|
117|---
118|
119|### 1.3 Capability-to-Task Mapping Guide
120|
121|After collecting all three checklists, the Team Leader uses this mapping to decide assignments:
122|
123|| Task Category | Key Capabilities Needed | Ideal Candidate Profile |
124||---|---|---|
125|| Heavy research + citation paragraphs | A1, A2, A3, A4, A6 | Someone with mostly YES on Member A checklist |
126|| Technical writing (AIDLC, research design, data analysis) | A5, A9, A6 | Someone who understands the tech stack and methodology |
127|| Synthesis + strategic paragraphs | L1, L2, L5 | Team Leader (these require the most judgment) |
128|| Descriptive / beneficiary writing | B2, B9 | Someone with MAYBE or YES on B2 and B9 |
129|| Formatting QA + reference compilation | B1, B3, B4, B5 | Someone with YES on B1 and B4 |
130|| Logistics + coordination | B7, B8 | Anyone available and willing |
131|| Final integration review | L3, L5, L6 | Team Leader (requires full system knowledge) |
132|
133|### 1.4 Assignment Decision Process
134|
135|```
136|STEP 1: All three members complete their capability checklists
137|         (this document, Section 1.2)
138|         
139|STEP 2: Team Leader collects and reviews all checklists
140|         - Identify who has YES on heavy research capabilities
141|         - Identify who has YES on formatting/detail capabilities
142|         - Identify availability constraints (hours/week, busy periods)
143|
144|STEP 3: Team Leader proposes initial task assignments using
145|         the Master Task Pool (Section 7)
146|         - Match tasks to capabilities, not to predefined roles
147|         - Respect availability constraints
148|         - Balance load based on each person's actual capacity
149|
150|STEP 4: Team meeting to discuss and finalize assignments
151|         - Each member reviews their proposed tasks
152|         - Raise concerns about tasks they're unsure about
153|         - Identify any tasks where no one feels confident
154|         - Flag tasks that may need the Team Leader to handle personally
155|
156|STEP 5: Document final assignments in Section 7 (fill in the Owner column)
157|         - This becomes the active assignment sheet
158|         - Can be revised later if circumstances change
159|```
160|
161|---
162|
163|---

## 2. OPEN QUESTIONS & DECISIONS TRACKER

This section captures unresolved questions that need team input before assignments can be finalized. The Team Leader should bring these to the first team meeting.

### 2.1 Questions for All Members

| # | Question | Who Answers | Answer | Status |
|---|----------|-------------|--------|--------|
| Q1 | What is each member's realistic weekly hour commitment for capstone writing? | Each member | ___ hours/week | Open |
| Q2 | What are each member's blocked/unavailable dates over the next 3 weeks? | Each member | ___ | Open |
| Q3 | Has each member read all 5 Guides (GUIDE-1 through GUIDE-5)? | Each member | Yes / No / Partial | Open |
| Q4 | Does each member have access to Google Scholar or any academic database? | Each member | Yes / No | Open |
| Q5 | Is each member comfortable using the team's chosen communication channel for async updates? | Each member | Yes / No | Open |

### 2.2 Questions for Member A

| # | Question | Answer | Status |
|---|----------|--------|--------|
| QA1 | Are you available for heavy research tasks (Background paragraphs 2-4) that require finding and citing 15+ academic sources? | | Open |
| QA2 | Are you comfortable writing about technical concepts (AIDLC, research design, data analysis) in academic language? | | Open |
| QA3 | How many days notice do you need before a deadline due to your busy schedule? | | Open |
| QA4 | Would you prefer fewer but harder tasks, or more but simpler tasks? | | Open |

### 2.3 Questions for Member B

| # | Question | Answer | Status |
|---|----------|--------|--------|
| QB1 | Would you be comfortable doing formatting QA checks across the entire manuscript? (You would be the final formatting gatekeeper.) | | Open |
| QB2 | Would you be willing to learn basic APA citation format to help with the References list? | | Open |
| QB3 | Are you comfortable writing descriptive paragraphs (e.g., beneficiary groups, locale description)? | | Open |
| QB4 | Would you prefer writing tasks or formatting/logistics tasks? | | Open |
| QB5 | Are there any non-writing, non-coding support tasks you would prefer to focus on? (e.g., organizing files, scheduling meetings, printing, logistics) | | Open |

### 2.4 Decisions to Make as a Team

| # | Decision | Options | Decision | Date Decided |
|---|----------|---------|----------|-------------|
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

## 3. THE GOLDEN RULES (Distilled from All 5 Guides)
164|
165|These rules apply to EVERY section of BOTH chapters. Violate any of these and the manuscript will be flagged.
166|
167|| # | Rule | Source |
168||---|------|--------|
169|| 1 | **Times New Roman, 12pt, double-spaced, justified** — no exceptions for any body text | Guide 1 |
170|| 2 | **No bullet points anywhere in the manuscript** — use numbered lists only where explicitly allowed (IPO boxes, specific objectives) | Guide 1 |
171|| 3 | **Bold is ONLY for**: chapter headings, subheadings, figure captions, table captions — never for body text emphasis | Guide 1 |
172|| 4 | **Paragraph indent = exactly 5 spaces** at start of every paragraph | Guide 1 |
173|| 5 | **Left margin = 1.5 inches**; all other margins = 1 inch | Guide 1 |
174|| 6 | **Table captions: left-aligned, above the table, bold** | Guide 1 |
175|| 7 | **Figure captions: below the figure, bold** | Guide 1 |
176|| 8 | **All cited literature must be 2022-2026** — flag anything outside this range | Guide 2 |
177|| 9 | **Every in-text citation MUST appear in the References list** — cross-check before submission | Guide 2 |
178|| 10 | **Paragraph form only** for Scope, Limitations, Importance of the Study, and ALL Methodology sections | Guide 2 |
179|| 11 | **Objective 3 uses ONE precise term** — "usability" (if using SUS) or "acceptability" — never both | Guide 2 |
180|| 12 | **Page numbers on every page EXCEPT the first page of each chapter** | Guide 1 |
181|| 13 | **Header/footer borders must fit entirely within the 1-inch margins** | Guide 1 |
182|| 14 | **No extra spacing between paragraphs** — set space before/after to 0pt | Guide 1 |
183|
184|---
185|
186|## 4. MANUSCRIPT FORMATTING CHEAT SHEET (Quick Reference)
187|
188|When formatting any section, verify against this checklist:
189|
190|```
191|FORMAT PRE-FLIGHT CHECKLIST (Apply to EVERY draft before submission)
192|==============================================================
193|[ ] Font: Times New Roman 12pt
194|[ ] Line spacing: Double
195|[ ] Paragraph indent: 5 spaces
196|[ ] Alignment: Justified
197|[ ] No bold in body text (only headings/captions)
198|[ ] No bullet points (numbered lists only where allowed)
199|[ ] No extra space between paragraphs
200|[ ] Left margin: 1.5" | Right/Top/Bottom: 1.0"
201|```
202|
203|---
204|
205|## 5. CHAPTER 1 — SECTION-BY-SECTION WORK PACKETS
206|
207|Each work packet below contains: the owner, the guide rules, what to write, research prompts, and acceptance criteria.
208|
209|---
210|
211|### 4.1 BACKGROUND OF THE STUDY
212|
213|**Structure: Exactly 6 paragraphs, funnel format (broad to specific)**
214|
215|#### PARAGRAPH 1 — Core Problem (NO CITATIONS)
216|
217|- **Suggested capability profile:** A4, A9, A1 (academic writing + methodology understanding)
218|- **Rules:** Own words only. No citations. Focus on IT problem framing, not admin/management framing.
219|- **Length:** 1 solid paragraph (8-12 sentences)
220|
221|**What to write:**
222|Name the exact problem SecureCAT solves. Start with observable symptoms (manual admission workflows, fragmented scoring, paper-based OMR, lack of audit trails), then pivot to the underlying technical root cause (absence of a unified, cryptographically-secured, role-based digital platform that enforces data integrity and operational continuity).
223|
224|**Research Prompt to expand or use as starting point:**
225|
226|> Write a 10-12 sentence paragraph in academic tone describing the core IT problem that SecureCAT addresses. The paragraph must:
227|> - Name the system: "SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin"
228|> - Describe observable symptoms: manual applicant intake, paper-based testing workflows, fragmented scoring processes, absence of real-time status tracking, lack of audit mechanisms
229|> - Pivot to the technical root cause: the absence of a unified, cryptographically-secured, role-based digital admission platform with automated score verification, offline resilience, and AI-assisted operations
230|> - Frame this as an IT/architectural gap, not an administrative inconvenience
231|> - Use present tense, formal academic language
232|> - Do NOT use any citations — this is entirely in your own words
233|> - Do NOT use bullet points or bold text
234|
235|**Acceptance Criteria:**
236|- [ ] Written entirely in own words — zero citations
237|- [ ] Names the system and its exact problem
238|- [ ] Frames the gap as a technical/IT problem (not management or governance)
239|- [ ] Moves from symptoms to root cause within the paragraph
240|- [ ] No bullet points, no bold text in body
241|
242|---
243|
244|#### PARAGRAPH 2 — Global Context (Minimum 5 citations, all 2022-2026)
245|
246|- **Suggested capability profile:** A1, A4, A9 (literature search + academic writing)
247|- **Rules:** International scope. Market trends, efficiency findings, architectural debates, adoption patterns. 5+ sources all 2022-2026.
248|
249|**What to write:**
250|Discuss how admission testing systems are handled globally. Cover: digital transformation of higher education admissions, automated testing and scoring platforms, role-based access control in educational systems, computer-vision-based OMR scanning, AI-assisted administrative operations in universities, offline-first application architectures, zero-trust security models in academic software.
251|
252|**Research Prompt:**
253|
254|> Research and write a 12-15 sentence paragraph providing the global context for role-based college admission testing systems. The paragraph must:
255|> - Cover at least 3 of these global themes: (1) digital transformation of university admissions, (2) automated test scoring technologies including OMR and computer vision, (3) role-based access control and zero-trust security in educational platforms, (4) AI-powered administrative assistants in higher education, (5) offline-capable web applications for exam proctoring
256|> - Cite a minimum of 5 academic/industry sources, all published between 2022-2026
257|> - Synthesize findings — do NOT list authors one by one (no "Author A says X. Author B says Y.")
258|> - Use parenthetical APA citations: (Author, Year)
259|> - End by establishing that the global trend supports integrated, secure, AI-augmented admission platforms
260|> - Write in formal academic paragraph form, justified, no bullets
261|
262|**Suggested research starting points (Member 1: verify these exist and find the actual sources):**
263|- UNESCO or World Bank reports on digital transformation in education (2022-2025)
264|- IEEE/ACM papers on automated test scoring and OMR (2023-2026)
265|- Industry reports on zero-trust architecture adoption (2023-2025)
266|- Studies on AI chatbots in university administration (2023-2026)
267|- Papers on PWA/offline-first architectures for critical operations (2022-2025)
268|
269|**Acceptance Criteria:**
270|- [ ] Minimum 5 citations, all 2022-2026
271|- [ ] International/global scope
272|- [ ] Synthesized (not author-by-author summary)
273|- [ ] All citations will appear in References list
274|- [ ] Paragraph form, no bullets, no bold body text
275|
276|---
277|
278|#### PARAGRAPH 3 — National Context (Minimum 5 citations, all 2022-2026)
279|
280|- **Owner:** Member 1 (Technical Writer)
281|- **Rules:** Philippine scenario. National policies, government mandates, CHED directives, DPA compliance. 5+ sources all 2022-2026.
282|
283|**What to write:**
284|Discuss the Philippine higher education admission landscape. Cover: CHED policies on admission testing standardization, the Philippine Data Privacy Act of 2012 (RA 10173) and its implications for student data handling, digitalization efforts in SUCs (State Universities and Colleges), national government pushes for e-governance, challenges of connectivity in Philippine campuses, and any relevant DepEd or CHED memoranda from 2022-2026.
285|
286|**Research Prompt:**
287|
288|> Research and write a 12-15 sentence paragraph providing the Philippine national context for digital admission testing systems. The paragraph must:
289|> - Cover: (1) CHED policies or memoranda on admission testing or educational technology, (2) RA 10173 (Data Privacy Act of 2012) compliance requirements for student data, (3) digitalization efforts in Philippine SUCs, (4) connectivity challenges in Philippine higher education institutions, (5) government e-governance or digital transformation initiatives affecting education
290|> - Cite a minimum of 5 sources, all published between 2022-2026
291|> - Name specific legislation, agencies, or programs (not vague references)
292|> - Use APA parenthetical citations
293|> - Synthesize findings — group ideas, contrast conditions, show relevance
294|> - Write in formal academic paragraph form
295|
296|**Suggested research starting points:**
297|- CHED Memorandum Orders (CMOs) related to admissions or technology (2022-2025)
298|- National Privacy Commission circulars on educational data handling (2022-2025)
299|- DICT or DOST reports on digitalization in education (2022-2025)
300|- Philippine academic journals on IT adoption in SUCs (2022-2026)
301|- News or government reports on connectivity infrastructure in Ilocos Sur or northern Luzon (2022-2025)
302|
303|**Acceptance Criteria:**
304|- [ ] Minimum 5 citations, all 2022-2026
305|- [ ] Philippine national scope
306|- [ ] Names specific legislation, agencies, or programs
307|- [ ] All citations will appear in References list
308|- [ ] Paragraph form
309|
310|---
311|
312|#### PARAGRAPH 4 — Local Context (Minimum 5 citations, all 2022-2026)
313|
314|- **Owner:** Member 1 (Technical Writer)
315|- **Rules:** Specific operational environment of ISPSC Tagudin. Socioeconomic constraints, infrastructure realities, manual processes, audit pressures. 5+ sources from same region or comparable institutions.
316|
317|**What to write:**
318|Detail the specific operational environment at ISPSC Tagudin Campus. Cover: the current manual admission workflow (application intake, exam scheduling, proctor assignment, paper-based scoring, manual result compilation, consultation and recommendation), infrastructure constraints (campus WiFi reliability, limited IT staff, aging computer labs), the Guidance and Registrar offices' operational challenges during peak admission periods, and any compliance pressures specific to the institution.
319|
320|**Research Prompt:**
321|
322|> Research and write a 12-15 sentence paragraph providing the local context for the admission testing system at ISPSC Tagudin Campus. The paragraph must:
323|> - Describe the specific operational environment: ISPSC Tagudin, its Guidance Office and Registrar Office workflows
324|> - Include: current manual processes (application intake, exam scheduling, proctor management, paper-based scoring, result release), infrastructure realities (WiFi reliability, limited IT support, computer lab conditions), socioeconomic constraints of the student population, and compliance pressures (DPA, institutional audit requirements)
325|> - Cite studies from the same region (Ilocos Sur, northern Luzon) or from comparable Philippine SUCs
326|> - Cite a minimum of 5 sources, all published between 2022-2026
327|> - If direct studies about ISPSC are unavailable, cite studies about comparable institutions (other SUCs with similar admission workflows and constraints)
328|> - Use APA parenthetical citations
329|> - Write in formal academic paragraph form
330|
331|**Acceptance Criteria:**
332|- [ ] Minimum 5 citations, all 2022-2026
333|- [ ] Specific to ISPSC Tagudin or comparable local institutions
334|- [ ] Describes actual operational environment
335|- [ ] All citations will appear in References list
336|- [ ] Paragraph form
337|
338|---
339|
340|#### PARAGRAPH 5 — Synthesis and Gap Identification
341|
342|- **Owner:** TEAM LEADER (This is the Leader's primary writing responsibility)
343|- **Rules:** SYNTHESIZE — group ideas, contrast findings, show the gap. Do NOT summarize author-by-author.
344|
345|**What to write:**
346|Draw from the citations in paragraphs 2-4 to show what the collective body of literature reveals. Explicitly name the research gap: no existing system combines role-based access with cryptographic score integrity, automated OMR scoring via computer vision, offline-resilient proctoring, and AI-assisted office operations — all in a single platform deployable for Philippine SUC admission testing. Distinguish what existing systems do (static records, basic scheduling, cloud-based forms) from what SecureCAT does (real-time orchestration with zero-trust governance and AI augmentation).
347|
348|**Research Prompt:**
349|
350|> Based on the literature reviewed in the global, national, and local context paragraphs, write a 10-12 sentence synthesis paragraph that:
351|> - Groups findings by theme rather than by author (e.g., "While global studies confirm that automated scoring reduces human error by 60-80% (Author A, 2024; Author C, 2023), Philippine SUCs continue to rely on manual paper-based processes due to infrastructure constraints (Author D, 2023; Author E, 2025)")
352|> - Contrasts at least 2 pairs of findings to build tension toward the gap
353|> - Explicitly names the research gap in 2-3 clear sentences: "No existing study or system has adequately addressed [specific gap]"
354|> - Distinguishes what existing systems provide vs. what SecureCAT introduces
355|> - Does NOT list authors one by one ("Author A says X. Author B says Y." is FORBIDDEN here)
356|> - Use the following synthesis template patterns:
357|>   * "While [finding A], [contrasting finding B], revealing that..."
358|>   * "Existing implementations achieve [X], but lack [Y]..."
359|>   * "Collectively, the reviewed literature indicates [pattern], yet no study has..."
360|> - Write in formal academic paragraph form
361|
362|**Acceptance Criteria:**
363|- [ ] Synthesized (not author-by-author)
364|- [ ] Research gap explicitly named
365|- [ ] Distinguishes existing systems from SecureCAT's contribution
366|- [ ] References citations from paragraphs 2-4 (no new citations needed here)
367|- [ ] Paragraph form
368|
369|---
370|
371|#### PARAGRAPH 6 — Clinching Statement
372|
373|- **Owner:** TEAM LEADER (Second primary writing responsibility)
374|- **Rules:** Three mandatory components. Optionally connect to an SDG.
375|
376|**What to write (3 components):**
377|1. How the reviewed literature assisted in structuring the study
378|2. Why you selected this research topic (direct observation of the problem at ISPSC Tagudin)
379|3. Why SecureCAT is the critical solution to the identified gap
380|
381|**Research Prompt:**
382|
383|> Write an 8-10 sentence clinching paragraph that closes the Background of the Study. The paragraph must contain three explicit components:
384|> 
385|> Component 1 (2-3 sentences): Explain how the reviewed literature assisted in structuring the present study. Be specific — name which aspects of the literature guided which aspects of the system design (e.g., "global best practices in zero-trust security informed the HMAC score integrity model; national DPA compliance requirements shaped the audit logging architecture; local infrastructure constraints dictated the offline-resilient PWA approach").
386|> 
387|> Component 2 (2-3 sentences): State why you selected this research topic. Include direct observation of the problem at ISPSC Tagudin — reference actual conditions you observed (manual processes, peak-period bottlenecks, scoring errors, lack of digital status tracking).
388|> 
389|> Component 3 (2-3 sentences): Conclude by highlighting why SecureCAT is the critical solution to the identified gap. Frame it as the system that bridges the divide between [what exists] and [what is needed].
390|> 
391|> Optional strengthening: Connect to SDG 4 (Quality Education) or SDG 16 (Peace, Justice, and Strong Institutions) — "This study further contributes to the United Nations Sustainable Development Goal 4, which advocates for inclusive and equitable quality education, by ensuring that admission processes are transparent, efficient, and technologically accessible."
392|> 
393|> Use formal academic paragraph form. No citations required (though you may reference themes from earlier paragraphs without formal citation).
394|
395|**Acceptance Criteria:**
396|- [ ] Component 1 present: literature's role in structuring the study
397|- [ ] Component 2 present: personal motivation + direct observation at ISPSC
398|- [ ] Component 3 present: why SecureCAT is the critical solution
399|- [ ] Optional SDG connection included
400|- [ ] Paragraph form
401|
402|---
403|
404|### 4.2 CONCEPTUAL FRAMEWORK OF THE STUDY
405|
406|- **Owner:** Member 1 (Technical Writer) — IPO diagram + narrative paragraphs
407|- **Member 2 assists with:** Formatting check (numbered lists in IPO, caption placement)
408|
409|**Rules (Guide 2):**
410|- IPO diagram: Input (numbered list) → Process (system name) → Output (numbered list)
411|- Input: ONLY data/config/parameters the system receives
412|- Output: ONLY what the system produces (no process verbs)
413|- Narrative: Exactly 2 paragraphs
414|- Paragraph 1: Explain what each input is and why necessary
415|- Paragraph 2: Explain how inputs are transformed into outputs (the mechanism)
416|
417|**Research Prompt for IPO Content:**
418|
419|> Design the Input-Process-Output (IPO) diagram content for SecureCAT. The IPO must reflect both existing built features AND planned research features as one unified system.
420|> 
421|> INPUT items (use numbered list, be specific):
422|> Consider including these categories adapted to SecureCAT:
423|> 1. Applicant registration data (personal information, academic background, program preferences)
424|> 2. Examination session configurations (schedule, room assignments, proctor assignments, test component definitions)
425|> 3. Test response data (OMR answer sheet images, direct assessment entries, CSV score imports)
426|> 4. User role credentials and access permissions (Applicant, Registrar Staff, Guidance/Proctor, Test Administrator, Super Admin)
427|> 5. System configuration parameters (academic year settings, rating scales, aptitude area definitions, privacy policy content)
428|> 6. Proctoring session data (QR code scans, attendance records, offline-cached verification data)
429|> 7. Natural language queries from staff (for the AI copilot and scheduling assistant)
430|> 
431|> PROCESS box: "SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin"
432|> 
433|> OUTPUT items (use numbered list, be specific):
434|> Consider including:
435|> 1. Applicant status lifecycle tracking and admission slips (PDF)
436|> 2. Automated examination schedules with room and proctor assignments
437|> 3. Score reports and graded results with HMAC integrity signatures
438|> 4. Audit trail logs with actor identity, timestamps, and state-change records
439|> 5. Result sheets (single, bulk PDF, bulk DOCX) for release to applicants
440|> 6. Consultation summaries and course recommendations
441|> 7. Natural language query responses from the RAG copilot
442|> 8. Offline-synced attendance and verification records
443|> 9. Statistical reports and data exports for institutional analysis
444|> 
445|> After listing the IPO items, write exactly 2 narrative paragraphs:
446|> - Paragraph 1: Explain what each input is and why it is necessary for the system to function
447|> - Paragraph 2: Explain the mechanical transformation — how the system processes these inputs through its role-based access control, automated scoring pipeline (including computer vision OMR), offline-resilient proctoring, AI-assisted operations, and cryptographic verification to produce the listed outputs
448|> 
449|> IMPORTANT: Inputs must be THINGS the system receives. Outputs must be THINGS the system produces. No process verbs in either list.
450|
451|**Acceptance Criteria:**
452|- [ ] IPO diagram present as figure with caption below
453|- [ ] Input uses numbered list (not bullets)
454|- [ ] Output uses numbered list (not bullets)
455|- [ ] Input contains only data/config the system receives
456|- [ ] Output contains only what the system produces (no process verbs)
457|- [ ] Process box contains the full system name
458|- [ ] Narrative is exactly 2 paragraphs
459|- [ ] Paragraph 1 explains inputs
460|- [ ] Paragraph 2 explains the transformation mechanism
461|- [ ] Both existing and planned features are covered
462|
463|---
464|
465|### 4.3 OBJECTIVES OF THE STUDY
466|
467|- **Owner:** TEAM LEADER (Strategic alignment — Leader writes this)
468|- **Member 2 assists with:** Formatting check (numbered list for specific objectives)
469|
470|**Rules (Guide 2):**
471|- 1 general objective paragraph (names the system, overarching purpose)
472|- Specific objectives in numbered list
473|- Standard 3-objective structure: Identify → Develop → Evaluate
474|- Objective 3 uses ONE term: "usability" (since we are using SUS)
475|
476|**Research Prompt:**
477|
478|> Write the Objectives of the Study section for SecureCAT. This section has two parts:
479|> 
480|> GENERAL OBJECTIVE (1 paragraph):
481|> "The general objective of this study is to develop [full system title], a [brief description of what it does — unified digital platform that enforces role-based access, cryptographic score integrity, automated test scoring, offline-resilient proctoring, and AI-assisted office operations] for [client institution and location]."
482|> 
483|> SPECIFIC OBJECTIVES (numbered list, exactly 3):
484|> 1. To identify and document the existing manual processes, operational gaps, and specific requirements of the admission testing workflow at ISPSC Tagudin, including application intake procedures, examination scheduling practices, scoring methodologies, and result release mechanisms.
485|> 2. To develop a role-based college admission testing system that integrates automated test scoring with computer vision OMR processing, cryptographic score integrity verification using HMAC signatures, offline-resilient proctoring via progressive web application technology, AI-assisted scheduling and natural language database querying, and immutable audit logging — all governed by zero-trust data access policies aligned with the Data Privacy Act of 2012.
486|> 3. To evaluate the usability of the developed system using the System Usability Scale (SUS) among identified respondents at ISPSC Tagudin Campus.
487|> 
488|> IMPORTANT RULES:
489|> - Objective 3 must say "usability" not "usability and acceptability" — SUS measures usability only
490|> - Specific objectives must be numbered (1, 2, 3) — not bulleted
491|> - The general objective must name the system by its full title
492|
493|**Acceptance Criteria:**
494|- [ ] General objective: 1 paragraph, names system, states purpose
495|- [ ] Specific objectives: numbered list (not bullets)
496|- [ ] Exactly 3 objectives following the Identify → Develop → Evaluate structure
497|- [ ] Objective 3 says "usability" only (not "acceptability")
498|- [ ] Objective 3 references SUS as the instrument
499|
500|---
501|