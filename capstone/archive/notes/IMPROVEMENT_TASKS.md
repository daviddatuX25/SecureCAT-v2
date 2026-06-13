# SecureCAT-v2 — Improvement Tasks & Prompts

> Source: Adviser meeting transcript + handwritten notes (`for improve`).
> Every directive extracted below. Nothing omitted.

---

## TABLE OF CONTENTS

1. [Fact Corrections (Interview / Simulated Data)](#1-fact-corrections-interview--simulated-data)
2. [Background of the Study — Rewrites](#2-background-of-the-study--rewrites)
3. [Deduplication & Structural Cleanup](#3-deduplication--structural-cleanup)
4. [Terminology & Variable Naming (IPO)](#4-terminology--variable-naming-ipo)
5. [Security Emphases](#5-security-emphases)
6. [RAG / AI Companion Framing](#6-rag--ai-companion-framing)
7. [Machine Learning Section — Transition & Cohesion](#7-machine-learning-section--transition--cohesion)
8. [Multi-Tenancy — Remove from Scope](#8-multi-tenancy--remove-from-scope)
9. [Capstone / Research Notes — Items to Record](#9-capstone--research-notes--items-to-record)
10. [Final Unification Pass](#10-final-unification-pass)
11. [Summary Checklist](#11-summary-checklist)

---

## 1. Fact Corrections (Interview / Simulated Data)

These are factual errors or misrepresentations found in the current drafts that must be corrected.

### 1.1 Current Application Process — NOT Google Forms

**What is wrong:** The current drafts state or imply that the application/intake process uses Google Forms.

**The reality:** The current process is primarily through a registration system that the school already has in place (the registrar's system). This system is NOT connected to the guidance office's system. The real dilemma is the lack of collaboration/integration between these separate systems, and the lack of a faster intake pipeline.

**PROMPT:**
> Scan all capstone modules/drafts for any mention of "Google Forms" or phrasing that implies the applicant intake is done via Google Forms. Replace every instance with the accurate description: the current applicant intake is handled through the registrar's own registration system, which is separate from the guidance office system. Emphasize that the problem is the lack of system integration and the slow intake process, not the use of Google Forms.

---

### 1.2 Internet Outage Frequency — FALSE Data

**What is wrong:** The drafts state internet outages happen "once or twice weekly" or similarly frequent intervals.

**The reality:**
- Internet is SOMETIMES slow, but it is NOT down "once or twice weekly."
- The campus internet is SHARED infrastructure.
- Occasional OTC outages happen roughly once a month or once every 2 weeks.
- The REAL scenario: students often do NOT have access to the school's internet at all. Wi-Fi is primarily reserved for campus staff (teachers, faculty), NOT for student use.
- The problem to highlight is the INFRASTRUCTURE CONSTRAINT — limited internet availability for students, not frequent outages.

**PROMPT:**
> Find all references to internet outage frequency (e.g., "once or twice weekly," frequent outages, etc.) in the capstone drafts. Replace with the accurate picture: (a) internet is sometimes slow but not frequently down; (b) the Wi-Fi is primarily for campus staff/faculty, not student use; (c) the infrastructure constraint is the limited availability of internet access for students, not the frequency of outages. Frame this as a resource/infrastructure limitation, not an outage problem.

---

### 1.3 Registrar & Guidance Already Have Systems

**What is wrong:** The drafts frame the current workflow as "purely manual paper-based."

**The reality:**
- ISPSC already uses systems. The registrar has its own system. The guidance office has one. There is also the MIS (Student Information Management System / SIMS).
- You CANNOT say "purely manual paper-based admission testing workflow" because systems exist.
- The caveat is that these systems are disconnected from each other and have their own limitations.

**PROMPT:**
> Find all statements in the drafts that characterize the current admission process as "purely manual," "entirely paper-based," or similar absolute claims. Rewrite to acknowledge that ISPSC already operates with systems (registrar system, guidance office system, MIS/SIMS). The problem is not the absence of systems, but the lack of integration between them, their limited capabilities, and the manual gaps that remain between these disconnected systems.

---

### 1.4 Word Template vs. System for Printing/Processing

**What is wrong:** The drafts describe using Word templates for generating documents/results.

**The reality:** David has confirmed from 3 years of direct experience that they DO have a system for functions like automated printing — it is NOT done with Word templates.

**PROMPT:**
> Search for any references to "Word template," "word document," or manual document generation in the drafts. Replace with the accurate description: the school uses an existing system for automated printing and processing functions. Word templates are not the method used.

---

### 1.5 Guidance Scoring Uses Stencil Method

**Fact to include:** The guidance office uses the "stencil method" for scoring, which is faster than manual visual checking. This is a long-standing practice. When transitioning to OMR, point out the differences and advantages of OMR over the stencil method.

**PROMPT:**
> Where the drafts discuss current scoring/checking methods, ensure the stencil method is mentioned as the current practice of the guidance office for scoring answer sheets. Describe it as faster than pure manual visual checking but note that OMR (Optical Mark Recognition) offers further advantages in speed, accuracy, and automation. Frame OMR as the next step beyond the stencil method, not just beyond pure manual checking.

---

### 1.6 Applicant Volume Data

**Fact to include:** For first-year/freshman applicants, the school typically receives 500+ to 1000+ applicants per school year. This data should be accurately reflected.

**PROMPT:**
> Verify that all references to applicant volumes in the drafts use accurate figures: approximately 500 to 1000+ freshman applicants per school year, with the trend being more or less around 1000. Remove any fabricated or exaggerated figures.

---

### 1.7 Facebook Page as Communication Channel

**Fact to include:** The guidance office uses a Facebook page for outreach — handling inquiries about applicant statuses, posting exam release dates by batch. The term used is "consultation and release." The office is also open for physical follow-ups.

**PROMPT:**
> Where the drafts discuss current communication channels for applicants, include that the guidance office uses a Facebook page for: (a) applicant inquiries about statuses, (b) posting exam schedule/release dates by batch. Also note the office accepts physical follow-ups. The term "consultation and release" is used to describe this process. This is a recent improvement that helps but is still limited.

---

### 1.8 Course Recommendations — Trained Counselors

**Fact to include:** Course recommendations are generated by the guidance counselor and assistant — they are trained on this. The process follows standards set by the guidance office.

**PROMPT:**
> Where course recommendations are discussed, note that the guidance counselor and assistant have the expertise and training to generate course recommendations. They follow established standards (including the undisclosed conversion/scoring formula). This human-driven process is the baseline that SecureCAT aims to augment, not replace.

---

### 1.9 Post-Pandemic Phrase

**Fact to include:** The statement about the shift to post-ad-hoc processes due to the pandemic is a good phrase to include in the background of the study.

**PROMPT:**
> Include a reference in the background of the study to how the pandemic drove changes in the admission process (post-ad-hoc adjustments). This provides historical context for why digitization and system improvements became necessary.

---

### 1.10 Verify Interview Facts via Questionnaire

**What to do:** Every factual claim in the simulated interview responses (`research/interview_simulated.md`) needs verification from an actual ISPSC staff member who has institutional knowledge of the Guidance, Registrar, Dean, and Campus Director offices.

**What was created:** A comprehensive verification questionnaire at `research/FACT_VERIFICATION_QUESTIONNAIRE.md` covering 6 sections:
- **Section A (Registrar):** Application intake method, Word templates vs. existing system, status notifications, scheduling coordination, document storage, applicant volume, data protection, reports
- **Section B (Guidance):** Scoring method (stencil vs. OMR vs. manual), proctoring, attendance tracking, results processing, conversion table confidentiality, existing Guidance system security, course recommendations, exam content, scoring volume, results consultation
- **Section C (Director/Dean):** Final admission decision authority, MIS/SIMS system, communication channels, digital transformation support
- **Section D (Infrastructure):** Internet availability and who has access (staff vs. students), server infrastructure
- **Section E (Overall):** Full admission cycle verification, office-to-office system connectivity, post-pandemic changes, pain points, applicant experience difficulties
- **Section F:** Respondent information

**Action for David:** Convert the questionnaire into a Google Form and submit it to someone from the Guidance Office (or a similarly knowledgeable person) who can verify facts across all offices. Time is limited — one knowledgeable respondent is sufficient. When real responses come back, replace all [SIM-XXX] tags in the drafts with [VER-XXX] (verified) tags.

**Key discrepancies the questionnaire specifically targets:**
| Topic | Simulated Claim | Adviser's Correction |
|-------|----------------|----------------------|
| Intake method | Google Forms | Registrar's own system (not Google Forms) |
| Admission slips | Word templates | Existing system with automated printing |
| Internet outages | 1-2x per week | Not that frequent; real issue is Wi-Fi is for staff only |
| Applicant volume | 300-400 per cycle | 500-1000+ per year |
| "Purely manual" framing | Paper-based workflows | Systems exist but are disconnected |
| Scoring method | Manual answer key comparison | Stencil method (faster than visual check) |

**PROMPT:**
> Convert `research/FACT_VERIFICATION_QUESTIONNAIRE.md` into a Google Form. Submit to a knowledgeable Guidance Office respondent for verification. When responses are received: (a) mark each verified claim with [VER-XXX] tags, (b) flag any claims that are confirmed as FALSE and need correction in the drafts, (c) update all draft modules accordingly using the verified data.

---

### 1.11 Real Data vs. Simulated Data

**Task:** The drafts should clarify what is real data (from David's experience, confirmed facts) vs. what is simulated/hypothetical. David wants the real data reflected.

**PROMPT:**
> Go through the drafts and identify which claims are based on real data (verified by David's experience) vs. simulated/hypothetical data. Mark or flag simulated data clearly. Prioritize replacing simulated data with real, verified facts wherever possible.

---

## 2. Background of the Study — Rewrites

### 2.1 Opening Paragraph — "Historically Dependent on Manual"

**What is wrong:** The opening sentence says the process is "historically dependent on manual."

**Fix:** Acknowledge that while there are manual elements, the registrar, guidance office, and MIS/SIMS already use systems. The problem is the disconnectedness and limitations of these systems, not a purely manual process.

**PROMPT:**
> Rewrite the opening paragraph of the Background of the Study. Do NOT say "historically dependent on manual" as the opening framing. Instead, acknowledge that ISPSC already uses systems (registrar, guidance, MIS/SIMS) but that these systems are disconnected, limited in capability, and leave manual gaps. The problem is integration and capability, not the absence of technology.

---

### 2.2 National vs. Local Context Boundaries

**What to fix:** Ensure national context stays national and local context stays local. Don't mix the two.

**PROMPT:**
> Review the Background of the Study section boundaries. Ensure that statements about the national context (Philippine education landscape) are clearly separated from local context (ISPSC-specific). Verify that no local-context data bleeds into national-context paragraphs and vice versa. The transition from national to local should be explicit and deliberate.

---

### 2.3 Don't Mention SecureCAT as Solution in Background of the Problem

**What is wrong:** The background of the problem already mentions that SecureCAT addresses the issues.

**Fix:** The background of the problem should ONLY state the problem and the need. Do NOT jump ahead to saying the system addresses it. Save the solution for later sections.

**PROMPT:**
> Search the "Background of the Problem" section for any mention of SecureCAT, the system name, or language that implies the system already solves the stated problems. Remove all such references. The background of the problem should only describe: what the problem is, why it matters, and what is needed. The solution should be presented in later sections, not in the problem statement itself.

---

### 2.4 Research Gap — Mentioning System Name

**Question to resolve:** Is it appropriate to already mention the full system name/title ("SecureCAT: Idonomic Real-Time Examination Orchestration Platform featuring Offline-First") in the research gap section? Check standard conventions for this.

**PROMPT:**
> Review the research gap section where the system name/title is mentioned ("SecureCAT, idonomic real-time examination orchestration platform featuring offline-first"). Verify against capstone manuscript conventions whether it is standard practice to name the proposed system at the end of the research gap. If it is premature, remove the explicit system naming and instead describe the type of system needed without naming it. If naming is standard, keep it but ensure the phrasing is appropriate.

---

### 2.5 Hosting / Infrastructure — Don't Focus Background on This

**What to fix:** The background of the study should NOT focus on the self-hosting vs. cloud hosting decision. Just define the constraints (resource limitations, server availability). The hosting decision is for the campus to make and should be limited to a recommendation.

**PROMPT:**
> Remove from the Background of the Study any extended discussion about cloud hosting vs. self-hosting decisions. The background should only note the infrastructure constraint (limited server resources, shared campus internet). The hosting decision should be deferred to the Recommendations section. The background should define the cards being played with, not the deployment strategy.

---

### 2.6 Advanced University Comparison — Frame as Resource Constraint

**What to do:** When comparing to advanced universities, focus on the difference in resource utilization (servers, hosting, infrastructure). Frame it as a resource constraint/availability issue, not just a technology gap.

**PROMPT:**
> Where the drafts compare ISPSC to advanced universities (e.g., those in urban/innovated cities), ensure the comparison focuses on the utilization of resources — specifically server infrastructure and hosting capabilities. Frame the gap as a resource constraint/availability issue, not just a lack of technology adoption. The problem is that the campus lacks the server infrastructure that advanced institutions have. Since acceess and efforts for reeeeseerach and technology is available to any univeeersity, resource constraints is wheere ethings usually is not the same .

---

### 2.7 Admission Cycle Accuracy

**What to do:** Make sure the drafts accurately reflect the actual admission cycle used by ISPSC (the upper-secondary/college admission cycle as described in the meeting).

**PROMPT:**
> Verify that all references to the admission cycle in the drafts accurately reflect the ISPSC admission process. The cycle involves: (a) application/intake through the registrar, (b) entrance examination administered by guidance, (c) scoring using stencil method with confidential conversion tables, (d) results consultation(course recommendation by trained counselors) and release (via Facebook page, physical follow-ups, and batch releases). Ensure no steps are misrepresented or omitted.

---

## 3. Deduplication & Structural Cleanup

### 3.1 Remove Duplicated Sections and Phrases

**What is wrong:** There are lots of duplicated sections, keynotes, and phrases — likely a byproduct of merging different modules/drafts into files.

**PROMPT:**
> Scan the entire document for duplicated paragraphs, sentences, or phrases. These are likely conversion errors from merging different module drafts. Remove all duplicates, keeping only the best-written instance of each unique point. After cleanup, every statement should appear exactly once in the document.. Sincne most likely afteer all task is done, the bg of the problem is very long due to these reemphasis or dupplicaiton of facts eetc.

---

### 3.2 Fix Bad Transition Phrases

**What is wrong:** Transition phrases like "the literature review above and the researchers or its traditional inquiry, etc." are unfitting for starting a paragraph. It's already one document — don't reference "above" sections awkwardly.

**PROMPT:**
> Find all awkward transition phrases between paragraphs, especially those that reference other parts of the same document (e.g., "the literature review above," "as stated previously," "the researchers' traditional inquiry"). Replace with smooth, natural transitions that fit a cohesive single document. The document should read as one unified paper, not a collection of separate modules pasted together.

---

### 3.3 Remove [SIM-GUID-XX] Tags

**What is wrong:** Text artifacts like "[SIM-GUID-08]." appear in the drafts. These are internal tags/labels that should not be in the final document.

**PROMPT:**
> Search all capstone module/draft files for patterns like "[SIM-GUID-XX]" where XX is any number. Remove all such tags completely. These are internal reference labels that do not belong in the final manuscript. and i think theeey present the patern of having followed up by summary phrases which is should not be included.

---

### 3.4 Organize Background of the Study — 6-Paragraph Structure with Citation Floors

**What is wrong:** The background of the study has disorganized thoughts — duplicated emphasis, poor paragraph flow, jumping between topics, and missing the required paragraph-by-paragraph structure with citation minimums.

**The Required Structure (from previously saved team plan):**

The Background of the Study MUST follow this exact 6-paragraph format:

| Paragraph | Label | Owner | Sentences | Citations | Content |
|-----------|-------|-------|-----------|-----------|---------|
| **P1** | Core Problem Statement | David | 8-12 | **0 (citation-free)** | Observable symptoms at ISPSC: manual workflows, paper-based routing between Guidance/Registrar, manual scoring, no audit trails, no RBAC. Pivot to the technical root cause: absence of a unified, cryptographically-secured, role-based digital platform. State what intervention is needed. IT/system framing, NOT public administration framing. |
| **P2** | Global Context | David | 12-15 | **Min 5 APA (2022-2026)** | Global trends in digital transformation of higher ed admissions. Automated testing/scoring platforms. RBAC, zero-trust security models. Emerging tech: CV-based OMR, AI-assisted operations, offline-first architectures. How global context sets the stage for local needs. **Synthesis writing only** — FORBIDDEN to list authors one by one. |
| **P3** | National Context (Philippines) | David | 12-15 | **Min 5 APA (2022-2026)** | National mandates: CHED directives, RA 10173 (Data Privacy Act), government e-governance programs. Current SUC practices and digitalization efforts. Connectivity challenges and infrastructure constraints in Philippine higher education. Bridge to local context. Must name specific legislation, agencies, or programs — no vague references. |
| **P4** | Local Context (ISPSC Tagudin) | Jaypee | 12-15 | **Min 5 APA (2022-2026)** | ISPSC's actual state: disconnected registrar/guidance/MIS systems, existing systems with security gaps (basic PHP, minimal authentication), infrastructure constraints (shared campus internet, Wi-Fi for staff not students), applicant volume (500-1000+ per year), stencil scoring method, limited counselors, physical follow-ups, Facebook page for outreach. Include comparable Philippine SUCs and regional ICT adoption as supporting evidence. |
| **P5** | Synthesis & Research Gap | David | 10-12 | (built from P2-P4 citations) | Synthesize themes across all 3 contexts: global X + national Y + local Z → reveals gap. Identify what NO existing study or system addresses. Key gap: role-based, multi-office admission testing coordination with offline-first architecture, AI-assisted workflows, and cryptographic data integrity. **Explicitly name the research gap in 2-3 sentences.** Synthesis writing only — DO NOT summarize author-by-author. Use pattern: "While [finding A], [contrasting finding B]..." |
| **P6** | Clinching Statement | David | 8-10 | (built from prior citations) | Three explicit components: (1) How the reviewed literature (P2-P5) assisted in structuring the study — which findings influenced system features and research design decisions. (2) Why this topic was selected — must include **direct observation of the problem at ISPSC Tagudin** (paper-based test routing, lack of audit trails, scoring errors, fragmented coordination during peak admission). (3) Why the proposed system is the critical solution — connect capabilities to the documented problems. Optional: connect to SDG 4 or SDG 16. |

**Key rules across ALL paragraphs:**
- Paragraph form ONLY — no bullet points, no numbered lists in body text
- No bold body text (bold only for section headings)
- **FORBIDDEN pattern:** "Author A says X. Author B says Y. Author C says Z."
- **Required synthesis pattern:** "Global studies demonstrate that [finding] (Author A, 2024; Author B, 2023), while [contrasting finding] (Author C, 2025)."
- Every transition between paragraphs must be smooth and deliberate — this is ONE cohesive document, not modules pasted together
- No duplicated emphasis across paragraphs

**PROMPT:**
> Reorganize the Background of the Study into exactly 6 paragraphs following the structure above:
>
> **P1 (Core Problem, 8-12 sentences, 0 citations):** Name the observable symptoms — manual admission workflows, paper-based test routing between Guidance Office and Registrar, manual scoring using answer keys/stencil method, fragmented processes, no audit trails, no role-based access. Pivot to the technical root cause: the absence of a unified, cryptographically-secured, role-based digital platform. State what technical intervention is needed. IT/system framing only. CORRECTIONS FROM SECTION 1 MUST APPLY: (a) do NOT say "purely manual" or "Google Forms" — acknowledge existing systems (registrar, guidance, MIS/SIMS) but emphasize their disconnectedness and limitations; (b) frame the problem as integration + capability gaps, not absence of technology.
>
> **P2 (Global Context, 12-15 sentences, min 5 APA citations 2022-2026):** Digital transformation of higher education admissions globally. Automated testing/scoring platforms. RBAC, zero-trust security models in educational systems. Emerging technologies: CV-based OMR, AI-assisted administrative operations, offline-first architectures. How global trends create expectations for local modernization. Synthesis writing only — weave findings thematically, never author-by-author. Note: include a mention of inherent security risks of AI/cloud-based systems processing sensitive student records (see Section 5.3).
>
> **P3 (National Context, 12-15 sentences, min 5 APA citations 2022-2026):** Philippine mandates — CHED directives on digital transformation, RA 10173 (Data Privacy Act) compliance for student data, government e-governance programs. Current SUC practices and digitalization efforts. Connectivity challenges and infrastructure constraints in Philippine higher education. Name specific legislation, agencies, programs. Bridge to ISPSC local context. Include post-pandemic shift context (see Section 1.9).
>
> **P4 (Local Context, 12-15 sentences, min 5 APA citations 2022-2026):** ISPSC Tagudin's real operational state. Existing systems: registrar's system, guidance office system (basic PHP, minimal authentication — see Section 5.2), MIS/SIMS. The disconnectedness between these systems. Infrastructure: shared campus internet, Wi-Fi primarily for staff/faculty NOT students (see Section 1.2), limited server resources. Applicant volume: 500-1000+ per year (see Section 1.6). Current scoring: stencil method (see Section 1.5). Communication: Facebook page for inquiries and exam release dates by batch (see Section 1.7). Pain points: limited counselors, applicants waiting in hallways, multiple return visits for results (see Section 6.3). Comparable Philippine SUCs and regional ICT adoption as supporting evidence.
>
> **P5 (Synthesis & Gap, 10-12 sentences):** Synthesize themes across P2-P4. Identify what NO existing study or system has adequately addressed. Key gap: the lack of role-based, multi-office (Guidance + Registrar) admission testing coordination with offline-first capability, AI-assisted support, and cryptographic data integrity. Explicitly name the research gap in 2-3 sentences. Use synthesis pattern: "While global studies demonstrate [X] and national mandates emphasize [Y], local implementations at institutions like ISPSC Tagudin remain [Z]." Do NOT name SecureCAT as the solution here unless convention allows it (see Section 2.4). After naming the gap, include a smooth transition: frame the NEED for ML-based course recommendation and smart insights (see Section 7.1), then mention the two ISPSC research studies (K-means clustering, socioeconomic/academic indicators — see Section 7.3) that validate ML applicability, then bridge to the integration need (see Section 7.2).
>
> **P6 (Clinching Statement, 8-10 sentences):** Three explicit components: (1) How reviewed literature structured the study — which findings influenced system features (PWA for low-connectivity → offline-first; RBAC literature → role-based design; AI/RAG research → AI Companion). (2) Why this topic was selected — direct observation at ISPSC: paper-based test routing between offices, lack of audit trails, scoring vulnerabilities, fragmented coordination during peak admission. (3) Why the proposed system is the critical solution — connect capabilities to documented problems. Frame as necessary intervention, not just convenience. Optional SDG 4 / SDG 16 connection.
>
> After reorganizing: verify NO duplicated phrases or emphasis between paragraphs, verify all fact corrections from Section 1 are applied, verify all transitions are smooth, verify the document reads as one cohesive paper.

---

## 4. Terminology & Variable Naming (IPO)

### 4.1 Replace "Scoring Formulas" with "Conversion Table Values"

**What is wrong:** The IPO and other sections use "scoring formulas" or "scoring for [variable]" as variable names.

**Fix:** Replace with "conversion table values" or "standardized conversion table values."

**PROMPT:**
> In the IPO (Input-Process-Output) diagram descriptions and any related sections, replace all instances of "scoring formulas," "scoring for [variable]," or similar terms with "conversion table values" or "standardized conversion table values." The guidance office uses a conversion table (not a formula) to transform raw scores into standardized aptitude scores. This conversion table is confidential and must not be exposed.

---

### 4.2 Replace "Rating Scales" with "Aptitude Scores"

**What is wrong:** Variable names use "rating scales."

**Fix:** Use "aptitude scores" instead.

**PROMPT:**
> Search for all uses of "rating scales," "rating scale," or "rating" as a variable/data field name in the IPO and related sections. Replace with "aptitude scores" or the appropriate domain-specific term. The system deals with aptitude test scores, not generic ratings.

---

### 4.3 Clarify "Adopted" Terminology

**What to fix:** The word "adopted" is used without clear definition. Be explicit about what it means in context.

**PROMPT:**
> Find where the term "adopted" is used in the background of the problem or related sections. Clarify what "adopted" means in this specific context — adopted from where? Adopted by whom? Adopted into what? Replace vague uses of "adopted" with a precise description of what was adopted and the source/context of adoption.

---

### 4.4 Phase Out Scoring Formula References from Modules

**What to do:** Remove references to scoring formulas from the various module drafts. The formulas should not be exposed or detailed in the documentation.

**PROMPT:**
> Search all module/draft files for any detailed descriptions or listings of scoring formulas, scoring algorithms, or conversion calculations. Remove or abstract these into high-level descriptions (e.g., "standardized conversion table" or "confidential scoring methodology"). The specific formulas and conversion logic must not be documented in the manuscript.

---

### 4.5 Consistent Variable Naming Across IPO

**What to do:** Ensure all variable names in the IPO are consistent, proper, and use the correct terminology.

**PROMPT:**
> Review the entire IPO section and normalize all variable/field names to use consistent, domain-appropriate terminology:
> - "Aptitude scores" (not "rating scales" or "ratings")
> - "Conversion table values" (not "scoring formulas" or "raw conversion")
> - "Course recommendations" (not "course assignments" or "placements")
> - "Applicant profiles" (not "student profiles" or "examinee profiles" unless specifically referring to enrolled students vs. applicants)
> - "Entrance examination results" (not "test scores" or "exam results" unless contextually appropriate)
>
> Ensure the same term is used for the same concept everywhere — no synonyms switching back and forth.

---

## 5. Security Emphases

### 5.1 Conversion Table Formula — CONFIDENTIAL

**Key requirement:** The guidance conversion table/formula must NEVER be exposed to anyone. This is a critical security requirement for SecureCAT.

**PROMPT:**
> Ensure the documentation explicitly states that the guidance office's conversion table (used to transform raw aptitude scores into standardized values for course recommendation) is strictly confidential. SecureCAT must implement security measures to ensure this conversion table is never exposed — not to applicants, not to unauthorized staff, not in the system's API responses, and not in any exportable data. Add this as a key security requirement in the system requirements section.

---

### 5.2 Existing System Security Flaws

**Fact to include:** The current guidance system is basically a PHP application with minimal restrictions and no robust authentication. This is the security gap SecureCAT addresses.

**PROMPT:**
> Where the drafts discuss the current guidance office system's limitations, include: (a) the system is a basic PHP application with minimal security restrictions, (b) it lacks robust authentication mechanisms, (c) sensitive student data (aptitude scores, conversion tables, applicant records) is handled by this insecure system. This justifies SecureCAT's emphasis on security. Note: do NOT claim that the current system is entirely insecure — the registrar's system's data protection is their concern. Focus on the guidance office's system gaps.

---

### 5.3 AI/Cloud Security Note for Background of the Study

**Fact to include:** In the background of the study, note the inherent security concerns with AI-based evaluation — particularly the trend of using cloud services for processing sensitive student records. Records should be handled with maximum sensitivity. This provides context for why SecureCAT's security-first approach matters.

**PROMPT:**
> Add a note in the Background of the Study (likely in the national context or ML section) about the inherent security risks of AI-based systems in education: (a) many institutions adopt cloud-based AI services to process student records, (b) sensitive data (aptitude scores, personal information) is transmitted to and stored on third-party servers, (c) this creates data privacy and security risks, (d) SecureCAT's approach prioritizes local/controlled data handling. Frame this as a known concern in the field, not as criticism of any specific institution.

---

### 5.4 Role-Based Access — Flexible and Scalable

**Design decision:** The system should still be role-based, but with flexibility — different roles can be assigned to a single user. Not tied to rigid rules. This supports scalability and policy changes.

**PROMPT:**
> Where the system's access control is described, ensure it reflects the following design: (a) role-based access control (RBAC) as the foundation, (b) flexible role assignment — a single user can have multiple roles (e.g., a user could be both examiner and data encoder), (c) not hardcoded to specific fixed roles — the system should be configurable to accommodate policy changes, (d) the super admin has the flexibility to assign/reassign roles as needed. This supports scalability and future-proofing.

---

### 5.5 OMR Accuracy and Applicant Identification

**Fact to include:** OMR uses probabilistic algorithms that are already accurate. For applicant identification on score sheets: options include printed QR codes per applicant attached to score sheets, or manual encoding of applicant data (names). Handwritten names would be probabilistic and less reliable.

**PROMPT:**
> Where OMR processing is discussed, include: (a) OMR algorithms are probabilistic and already highly accurate for bubble-sheet reading, (b) for applicant identification on score sheets, the options are: (i) pre-printed QR codes for each applicant affixed to their score sheet (most reliable), or (ii) manual encoding of applicant names/IDs into the system (reliable but manual). Handwritten name recognition is probabilistic and less reliable, so it should not be the primary identification method. Frame this as a design consideration for the OMR module.

---

## 6. RAG / AI Companion Framing

### 6.1 Define RAG and Its Purpose

**What is wrong:** The drafts mention the AI Companion but don't properly define RAG (Retrieval-Augmented Generation) or frame the problem it solves.

**Fix:** Define RAG, explain why it's needed, and describe the data it will use.

**PROMPT:**
> In the Background of the Study or the feature description section, add a clear explanation of RAG (Retrieval-Augmented Generation):
> - What RAG is: a technique where the AI retrieves relevant documents/data from a knowledge base before generating responses.
> - Why it's needed: to provide contextually accurate, institution-specific responses to applicants rather than generic AI responses.
> - What data feeds the RAG system: (a) institutional/constitutional data (school policies, admission requirements, course offerings, program details), (b) Q&A scenarios (frequently asked questions, common applicant inquiries, guidance counselor knowledge base).
> - How it works: applicants interact with the AI Companion, which retrieves relevant data from the knowledge base and generates personalized, accurate responses.
>
> Frame the PROBLEM first: applicants need guidance outside of limited counselor availability hours. Then frame the SOLUTION: an AI companion powered by RAG that provides accurate, institution-specific guidance.

---

### 6.2 AI Companion Use Cases

**What to include:** The AI companion serves applicants in two scenarios:
1. **Pre-examination:** Recommendations and guidance for applicants about to take the entrance exam.
2. **Post-examination:** Insights and recommendations for applicants who have received their results — validating what counselors would say, available 24/7 even after applicants have gone home.

**PROMPT:**
> Ensure the AI Companion feature description includes both use cases:
> (a) Pre-examination: guidance for applicants preparing to take the entrance exam (what to expect, how to prepare, what documents to bring).
> (b) Post-examination: recommendations and insights for applicants who have received results — course recommendations, alternative options, next steps.
> Note that this does NOT replace human counselors. It AUGMENTS them by being available when counselors are not (after hours, during high-volume periods). The companion's responses are validated against the institutional knowledge base via RAG.

---

### 6.3 Results Consultation as a Pain Point

**Fact to include:** One of the current pain points is having too few counselors to serve all applicants on consultation days. The AI companion addresses this by providing validated insights post-examination.

**PROMPT:**
> Include in the problem statement or background: the limited number of guidance counselors creates a bottleneck during results consultation periods. Applicants often have to wait or return multiple times. The AI Companion feature addresses this by: (a) providing immediate preliminary insights after results are released, (b) being available outside of office hours, (c) reducing the load on counselors for routine questions so they can focus on complex cases that require human judgment.

---

## 7. Machine Learning Section — Transition & Cohesion

### 7.1 Proper Transition to ML Discussion

**What is wrong:** The statement "recent work at the same institution has explored machine learning techniques" jumps into ML without proper setup.

**Fix:** First frame the NEED for ML (course recommendation, smart insights, profiling), then introduce the research that applies ML.

**PROMPT:**
> Rewrite the transition into the machine learning discussion in the Background of the Study. Before mentioning any ML research studies:
> 1. First establish WHY machine learning is relevant to the admission process: (a) the need for data-driven course recommendations based on aptitude profiles, (b) the potential for smart insights from applicant data, (c) the value of applicant profiling for better placement decisions.
> 2. THEN introduce the research studies that validate this approach (K-means clustering study, socioeconomic/academic indicator study).
> 3. AFTER mentioning the studies, have a bridging statement that connects to the proposed integration/solution concept.
>
> The flow should be: NEED → EVIDENCE (research) → BRIDGE (to solution). Not a sudden jump to "recent work explored ML."

---

### 7.2 After ML Research — Bridge to Integration

**What to do:** After mentioning the ML research studies, have a statement that reveals the need for an integrated solution that leverages ML for the identified problems.

**PROMPT:**
> After the ML research mentions, add a bridging paragraph that: (a) summarizes what the research shows (ML can effectively profile and recommend courses for applicants), (b) identifies the gap (no integrated system exists that combines OMR, AI-assisted evaluation, ML-based triage, and applicant support in a single platform), (c) sets up the research gap. This bridge should smoothly lead into the statement of the problem/research gap without naming the solution yet.

---

### 7.3 Duplicated Research Mentions

**What is wrong:** Research findings are mentioned multiple times (e.g., the K-means clustering finding about "aptitude-based profiles among ISPSC applicants" and "socioeconomic academic indicators").

**Fix:** Mention each research finding exactly once, in the ML section, with a good transition.

**PROMPT:**
> Find all duplicated references to research studies (especially the K-means clustering study and the socioeconomic/academic indicator study) in the drafts. Consolidate each research mention into a single, well-placed instance in the ML section of the Background of the Study. Remove all other mentions. Ensure smooth transitions before and after the consolidated research mention.

---

## 8. Multi-Tenancy — Remove from Scope

### 8.1 Remove Multi-Tenancy from Current Features/Context

**Decision:** Multi-tenancy should be REMOVED from the current feature set and system context. The system has too many features already. Multi-tenancy can be mentioned as an optional/future feature in the Recommendations section, but should NOT be part of the current scope.

**PROMPT:**
> Search all capstone modules, drafts, and documentation for mentions of "multi-tenancy," "multi-tenant," "multi-campus," or similar concepts. Remove these from the current system scope, feature list, and main body of the document. Multi-tenancy should only appear in the Recommendations section as a future enhancement suggestion. Do NOT include it in the system's defined features, architecture, or problem context.

---

## 9. Capstone / Research Notes — Items to Record

These are items that should be added to the capstone/research notes file for future reference, NOT to the manuscript itself.

### 9.1 Self-Hosted Infrastructure Recommendation

**PROMPT:**
> Add to the capstone/research notes: Recommend a self-hosted infrastructure approach for ISPSC as a future consideration. Self-hosting maximizes security for managing private student data. The decision between cloud hosting and self-hosting is for the campus to make, but the recommendation should lean toward self-hosting for data sensitivity reasons. This is NOT part of the Background of the Study — it goes in the Recommendations section of the manuscript and in the research notes.

---

### 9.2 Multi-Tenancy as Future Recommendation

**PROMPT:**
> Add to the capstone/research notes: Multi-tenancy (supporting multiple ISPSC campuses or programs from a single deployment) is identified as a valuable future feature but is removed from current scope to manage complexity. Include it as a recommendation for future development, noting that the current role-based access system is designed to be flexible enough to eventually support multi-tenancy.

---

### 9.3 Hosting Decision — Limit to Recommendation

**PROMPT:**
> Add to the capstone/research notes: The decision to self-host or use cloud hosting providers is a campus-level decision that this research should not prescribe. The research should limit itself to providing a recommendation (leaning toward self-hosting for security) and presenting the trade-offs. The deployment for the study itself will use local hosting.

---

## 10. Final Unification Pass

### 10.1 Unify All Content into Single Markdown Before Converting to Document

**Process:** After all individual corrections are done, unify all content into a single markdown file. Then review for:
- Duplicated sections
- Inconsistent terminology
- Poor transitions
- Missing context
- Factual errors

Only after this unified markdown is verified should it be converted to the final document format.

**PROMPT:**
> After all the above corrections have been applied to individual modules/drafts:
> 1. Merge all content into a SINGLE markdown file, organized by section (Background of the Study, Statement of the Problem, IPO, etc.).
> 2. Review the unified document top-to-bottom for: (a) duplicated paragraphs or sentences (remove all duplicates), (b) inconsistent terminology (normalize using the terminology rules in Section 4), (c) poor transitions between sections (fix all transitions to read as one cohesive document), (d) factual accuracy (verify against the corrections in Section 1), (e) missing context (ensure all items from Sections 2-7 are included).
> 3. Verify that no [SIM-GUID-XX] tags remain.
> 4. Verify that no scoring formulas or conversion details are exposed.
> 5. Verify that SecureCAT is not mentioned as a solution in the problem statement.
> 6. Once the unified markdown passes all checks, convert to the final document format.

---

## 11. Summary Checklist

Big-picture tasks from the adviser's final summary:

| # | Task | Status |
|---|------|--------|
| 1 | Modify certain facts about the interview/simulated interview data (Section 1) | [ ] |
| 2 | Rewrite the account of the study for quality — reorganize Background of the Study (Sections 2-3) | [ ] |
| 3 | IPO — consistent naming, proper terminology, remove scoring formulas (Section 4) | [ ] |
| 4 | Remove [SIM-GUID-XX] tags from all drafts (Section 3.3) | [x] |
| 5 | Security emphases — conversion table confidentiality, system flaws, AI/cloud risks (Section 5) | [ ] |
| 6 | RAG/AI Companion — define RAG, frame the problem, describe use cases (Section 6) | [ ] |
| 7 | ML section — proper transition, remove duplicates, bridge to integration (Section 7) | [ ] |
| 8 | Remove multi-tenancy from scope, move to recommendations (Section 8) | [ ] |
| 9 | Add notes to capstone/research notes file (Section 9) | [ ] |
| 10 | David reviews simulated interview responses for accuracy (Section 1.10) | [ ] |
| 11 | Final unification pass — single markdown, deduplicate, then convert (Section 10) | [ ] |
| 12 | Verify real data vs. simulated data is properly distinguished (Section 1.11) | [ ] |

---

*Generated from adviser meeting transcript. All directives captured. No detail left unsaid.*
