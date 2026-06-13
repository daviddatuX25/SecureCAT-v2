# SecureCAT-v2 — Improvement Tasks & Prompts (Part 2)

> Source: Project alignment beads, adviser notes (`for improve`), and proposal defense guidelines.
> This document tracks the required tense adjustments (past/present to future tense) and factual/thematic realignments needed to finalize Chapters 1 & 2 of the manuscript for the Proposal Defense.

---

## TABLE OF CONTENTS

1. [Proposal Tense Alignment (Past/Present to Future)](#1-proposal-tense-alignment-pastpresent-to-future)
2. [Factual & Thematic Realignment (Bead Trends Verification)](#2-factual--thematic-realignment-bead-trends-verification)
3. [Unification and Compilation Workflow](#3-unification-and-compilation-workflow)
4. [Proposal Defense Checklist](#4-proposal-defense-checklist)

---

## 1. Proposal Tense Alignment (Past/Present to Future)

In an academic capstone proposal, the research activities (development, testing, administration of surveys, and analysis of data) are activities that **will occur** in the future. Therefore, Chapter 2 (Methodology) and certain sections of Chapter 1 (e.g., Significance of the Study) must describe the system operations, evaluation, and research actions in the **future tense** (e.g., *will be constructed*, *will undergo*, *will be administered*), while historical context and the pre-existing baseline digital platform remain in the past/present tense.

Below are the specific sections, files, and proposed changes to align the manuscript with the proposal perspective.

### 1.1 C2-01: Research Design
* **File:** `capstone/drafts/C2-01_David_Research_Design.md`
* **Current Phrasing (Present Tense):**
  > "...The developmental component involves the iterative design, construction, and validation of SecureCAT using the AI-Driven Development Lifecycle (AIDLC) as the software model, wherein AI-assisted code generation is governed by human review, automated testing, and architectural oversight at every phase..."
* **Proposed Adjustment (Future Tense):**
  > "...The developmental component **will involve** the iterative design, construction, and validation of SecureCAT using the AI-Driven Development Lifecycle (AIDLC) as the software model, wherein AI-assisted code generation **will be governed** by human review, automated testing, and architectural oversight at every phase..."

---

### 1.2 C2-02: Software Model
* **File:** `capstone/drafts/C2-02_David_Software_Model.md`
* **Current Phrasing (Past Tense in Phases):**
  * **Software Model Intro:** 
    > "...By adopting this model, the researcher provides an accurate representation of the development process of SecureCAT, which was constructed using AI assistants (Gemini, Claude, and GitHub Copilot)..."
    * *Adjustment:* Change to "...which **will be constructed** using AI assistants..." or "...which **is proposed to be constructed** using AI assistants..."
  * **Inception Phase:**
    > "Inception. In this phase, the development requirements and software specifications were conceptualized through Mob Elaboration, wherein the researcher collaborated with AI assistants to translate observed operational challenges... These challenges were identified... the researcher used conversational and code-generation models to design... The deliverables of the Inception phase included..."
    * *Adjustment:* Change to "Inception. In this phase, the development requirements and software specifications **will be conceptualized** through Mob Elaboration, wherein the researcher **will collaborate** with AI assistants to translate observed operational challenges... These challenges **will be identified**... the researcher **will use** conversational and code-generation models to design... The deliverables of the Inception phase **will include**..."
  * **Construction Phase:**
    > "Construction. In this phase, the system modules were developed and tested using a Mob Construction workflow, where AI coding agents generated backend and frontend code while the researcher acted as the architect... The backend was constructed using PHP 8.4 and the Laravel 12 framework... One capability built... was designed and implemented... the ML-assisted course triage module was constructed... during coding, the AI agents generated... Automated unit and feature testing was integrated... tests ran... were flagged and corrected..."
    * *Adjustment:* Change to "Construction. In this phase, the system modules **will be developed and tested** using a Mob Construction workflow, where AI coding agents **will generate** backend and frontend code while the researcher **will act** as the architect... The backend **will be constructed** using PHP 8.4 and the Laravel 12 framework... One capability **to be built** during this phase is the Optical Mark Recognition (OMR) scanning module, which **will be designed and implemented**... the ML-assisted course triage module **will be constructed**... during coding, the AI agents **will generate**... Automated unit and feature testing **will be integrated**... tests **will run** using PHPUnit 11... **will be flagged and corrected** before code integration."
  * **Operations Phase:**
    > "Operations. In this phase, the system underwent pilot deployment, automated monitoring, and empirical evaluation. The SecureCAT application was deployed... database seeders were executed... The researcher used automated scripts... validating the newly built... confirming that... operations phase ended... were finalized..."
    * *Adjustment:* Change to "Operations. In this phase, the system **will undergo** pilot deployment, automated monitoring, and empirical evaluation. The SecureCAT application **will be deployed**... database seeders **will be executed**... The researcher **will use** automated scripts... **to validate** the newly built... **to confirm** that... operations phase **will end**... **will be finalized** to gather quantitative and qualitative feedback..."

---

### 1.3 C2-05: Population and Locale of the Study
* **File:** `capstone/drafts/C2-05_Christine_Population_Locale.md`
* **Current Phrasing (Present Tense in Sampling):**
  > "To evaluate the developed system, a purposive sampling technique selects the administrative and test-monitoring respondents, while convenience intercept sampling is used for the applicant cohort... In this study, the administrative respondent group splits into... examinee respondent group is selected through..."
* **Proposed Adjustment (Future Tense):**
  > "To evaluate the developed system, a purposive sampling technique **will select** the administrative and test-monitoring respondents, while convenience intercept sampling **will be used** for the applicant cohort... In this study, the administrative respondent group **will be split** into... examinee respondent group **will be selected** through..."

---

### 1.4 C2-06: Research Instruments
* **File:** `capstone/drafts/C2-06_Christine_Research_Instruments.md`
* **Current Phrasing (Present Tense):**
  > "This study uses two peer-validated research instruments... the research instruments are administered to Registrar staff and Guidance staff... The System Usability Scale is administered... The NASA Task Load Index is also administered..."
* **Proposed Adjustment (Future Tense):**
  > "This study **will use** two peer-validated research instruments... the research instruments **will be administered** to Registrar staff and Guidance staff... The System Usability Scale **will be administered**... The NASA Task Load Index **will also be administered**..."

---

### 1.5 C2-07: Data Analysis
* **File:** `capstone/drafts/C2-07_David_Data_Analysis.md`
* **Current Phrasing (Present/Past mix in analysis narrative):**
  * *Intro:* "...data analysis procedures... follow..." -> Change to "...**will follow**..."
  * *Feedback:* "This iterative feedback loop ensures..." -> Change to "...**will ensure**..."
  * *SUS:* "The SUS yields..." -> Change to "The SUS **will yield**..."
  * *Comparison:* "Comparing the workload profiles... will show whether the software successfully reduced... while improving..."
    * *Adjustment:* Change to "...will show whether the software successfully **reduces** (or **will reduce**) mental demand... while **improving** (or **will improve**) performance."

---

### 1.6 C1-12: Significance of the Study
* **File:** `capstone/drafts/C1-12_Jaypee_Significance.md`
* **Current Phrasing (Present/Past mix on benefits):**
  * **The Community:** "...benefits..." -> Change to "...**will benefit**..."; "...reduces..." -> Change to "...**will reduce**..."; "...minimizes..." -> Change to "...**will minimize**..."
  * **The Client Institution:** "...benefits..." -> Change to "...**will benefit**..."; "...addresses..." -> Change to "...**will address**..."; "...halves..." -> Change to "...**will halve**..."; "...replaces..." -> Change to "...**will replace**..."; "...assist..." -> Change to "...**will assist**..."; "...supports..." -> Change to "...**will support**..."
  * **The Respondents:** "...benefit..." -> Change to "...**will benefit**..."; "...experience..." -> Change to "...**will experience**..."; "...reduces..." -> Change to "...**will reduce**..."; "...compresses..." -> Change to "...**will compress**..."; "...introduces..." -> Change to "...**will introduce**..."; "...replaces..." -> Change to "...**will replace**..."; "...allows..." -> Change to "...**will allow**..."; "...extends..." -> Change to "...**will extend**..."; "...reduces..." -> Change to "...**will reduce**..."
  * **The College or Department:** "...benefits..." -> Change to "...**will benefit**..."; "...demonstrates..." -> Change to "...**will demonstrate**..."; "...models..." -> Change to "...**will model**..."; "...validates..." -> Change to "...**will validate**..."; "...contributing..." -> Change to "...**will contribute**..."
  * **The Students:** "...benefit..." -> Change to "...**will benefit**..."; "...ensures..." -> Change to "...**will ensure**..."; "...eliminates..." -> Change to "...**will eliminate**..."; "...lets..." -> Change to "...**will let**..."
  * **The Researchers:** "...benefit..." -> Change to "...**will benefit**..."; "...developed..." -> Change to "...**will develop**..."; "...developed..." -> Change to "...**will also develop**..."
  * **Future Researchers:** "...provides..." -> Change to "...**will provide**..."; "...offers..." -> Change to "...**will offer**..."; "...operationalizes..." -> Change to "...**will operationalize**..."

---

## 2. Factual & Thematic Realignment (Bead Trends Verification)

This section outlines specific checkpoints to ensure the compiled manuscript aligns with the recent core improvements made to the project.

### 2.1 Thematic Consistency Checkpoints

| Topic / Concept | Required Alignment | Status / Verification Method |
|---|---|---|
| **Intake Process** | Must reference the **Registrar's Registration System** (disconnected system). **No Google Forms.** | Checked. No occurrences of "Google Forms" in compiled manuscript. |
| **Outage Frequency** | Must describe **infrastructure/resource constraints** (campus bandwidth shared, Wi-Fi reserved for staff, students have limited access). No "frequent weekly outages." | Verified. Background and Scope drafts updated to reflect limited campus Wi-Fi infrastructure. |
| **Existing Systems** | Framed as **disconnected legacy systems** with manual gaps (Registrar, Guidance, MIS/SIMS). Avoid "purely manual" or "entirely paper-based" absolute statements. | Verified. "Purely manual" references removed. |
| **Scoring Baseline** | Acknowledge that the Guidance Office uses the **stencil method** for scoring. It is faster than visual grading but lacks automation, notifications, and security. | Checked. C1-04, C1-08, C1-09, and C1-12 successfully updated. |
| **Conversion Table** | Emphasize that the entrance exam **conversion table values are strictly confidential** and role-locked (never exposed to applicants or unauthorized staff). | Checked. Conceptual Framework and Scope drafts include this security boundary. |
| **RAG / AI Companion** | Framed as **augmenting counselor reach** (24/7 pre-examination preparation and post-examination course guidance validated against local knowledge base) to solve counselor bottlenecks. | Checked. C1-08, C1-11, and C1-12 emphasize RAG augmenting the human counselor. |
| **Machine Learning** | Triage module performs **live K-Means clustering** (Option C: adopt & tweak school-owned code from prior studies) via Python FastAPI microservice. No "static rules." | Checked. Narrative describes dynamic clustering at the point of consultation. |
| **Multi-Tenancy** | Moved completely **out of current scope** to Recommendations (future enhancement). | Verified. Scoped for ISPSC Tagudin only; multi-tenancy deferred to C1-11 recommendations. |
| **Role Assignment** | Role-based architecture allows **multiple roles assigned to a single user** (e.g. examiner + data encoder) via Super Admin interface for administrative flexibility. | Checked. Chapter 2 Project Assignments and Chapter 1 Scope updated. |

---

## 3. Unification and Compilation Workflow

To compile the updated drafts into the final Markdown and Word document templates, execute the following steps:

1. **Apply Draft Edits:** Update the individual draft files in `/home/user/Projects/SecureCAT-v2/capstone/drafts` using the tense adjustments outlined in Section 1.
2. **Re-assemble Markdown Manuscript:** Run the assembly script from the terminal to rebuild the single Markdown manuscript:
   ```bash
   python3 /home/user/Projects/SecureCAT-v2/capstone/assemble_manuscript_md.py
   ```
3. **Re-assemble Word Manuscript:** Run the Docx assembly script to generate the Microsoft Word version with standard three-line APA tables:
   ```bash
   python3 /home/user/Projects/SecureCAT-v2/capstone/assemble_manuscript.py
   ```
4. **Inspect & Verify:** Verify that the output files `/home/user/Projects/SecureCAT-v2/capstone/SecureCAT_Ch1_Ch2_Manuscript.md` and `/home/user/Projects/SecureCAT-v2/capstone/SecureCAT_Ch1_Ch2_Manuscript.docx` have compiled successfully without errors.

---

## 4. Proposal Defense Checklist

Before submitting Chapters 1 & 2, verify the following compliance items:
* [ ] All planned system operations are written in the **future tense** (Chapter 2 and beneficiary section of Chapter 1).
* [ ] No references to "Google Forms" or "purely manual paper testing" remain.
* [ ] The stencil scoring method is highlighted as the manual-adjacent baseline.
* [ ] The K-Means algorithm is clearly framed as an adopt-and-tweak integration of school-owned code, not a new algorithm designed by the researcher.
* [ ] The System Usability Scale (SUS) is paired with the NASA Task Load Index (NASA-TLX) for user evaluation, and "acceptability" is excluded in favor of "usability" and "workload."
* [ ] The conversion table remains confidential and role-locked in all architectural descriptions.
* [ ] Multi-tenancy is omitted from the Scope and placed in the future Recommendations section.
