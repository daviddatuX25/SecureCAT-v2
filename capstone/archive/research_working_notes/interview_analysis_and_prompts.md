# Interview Analysis & Module Editing Prompts

**Source:** `interview_simulated.md` (simulated June 8 interview)
**Purpose:** Identify interview topics that affect/redirect existing draft modules, segregate by topic, and generate specific editing prompts for each module.

---

## Topic Segregation

### Topic 1: OMR Correction — No OMR Overlays Currently In Use
**Interview evidence:** `[SIM-GUID-01]` — Guidance scores manually using answer key comparison, NOT OMR overlays.
**Impact level:** CRITICAL — multiple drafts assumed OMR overlays exist
**Affected modules:** C1-01, C1-04, C1-07, C1-08, C1-11, C2-02

### Topic 2: Manual Encoding & Duplicate Data Entry
**Interview evidence:** `[SIM-REG-01]`, `[SIM-REG-17]`, `[SIM-REG-18]` — Google Form responses re-encoded into Excel, duplicates occur
**Impact level:** HIGH — reinforces problem narrative
**Affected modules:** C1-01, C1-04, C1-12

### Topic 3: Applicant Volume & Processing Time Data
**Interview evidence:** `[SIM-REG-10]` (300-400/cycle, 30-50/peak day), `[SIM-GUID-08]` (2-3 days for 50 applicants), `[SIM-GUID-13]` (1-2 weeks for results)
**Impact level:** HIGH — resolves STEER markers
**Affected modules:** C1-01, C1-04, C1-12, C2-05

### Topic 4: No Automated Status Notification
**Interview evidence:** `[SIM-REG-03]`, `[SIM-REG-15]` — applicants must return in person or call
**Impact level:** HIGH — reinforces problem and significance
**Affected modules:** C1-01, C1-04, C1-12

### Topic 5: Verbal/Informal Coordination Between Offices
**Interview evidence:** `[SIM-REG-04]`, `[SIM-REG-14]`, `[SIM-GUID-11]` — scheduling by text/verbal, no shared system
**Impact level:** MEDIUM — supports coordination gap argument
**Affected modules:** C1-01, C1-04

### Topic 6: No Audit Trail for Score Modifications
**Interview evidence:** `[SIM-GUID-05]`, `[SIM-GUID-17]` — Excel file with no modification tracking
**Impact level:** HIGH — data integrity vulnerability
**Affected modules:** C1-01, C1-04, C1-12

### Topic 7: Minimal Data Privacy Protections
**Interview evidence:** `[SIM-REG-19]` — no formal data privacy protocol, spreadsheet not password-protected
**Impact level:** HIGH — RA 10173 compliance gap
**Affected modules:** C1-04, C1-11, C1-12

### Topic 8: Offline Capability Need
**Interview evidence:** `[SIM-REG-20]`, `[SIM-REG-23]` — internet outages 1-2x/week during rainy season
**Impact level:** MEDIUM — infrastructure constraint
**Affected modules:** C1-04, C1-11

### Topic 9: Director Support for Digital Transformation
**Interview evidence:** `[SIM-DIR-01]` through `[SIM-DIR-08]` — full digital vision, dashboard, proctor delegation, AI companion, quota management
**Impact level:** MEDIUM — validates system requirements
**Affected modules:** C1-12, C2-01, C2-02

### Topic 10: Course Recommendation Manual Process
**Interview evidence:** `[SIM-GUID-20]`, `[SIM-GUID-25]` — manual cross-referencing against quota list
**Impact level:** MEDIUM — supports AI scheduling feature justification
**Affected modules:** C1-01, C1-12

### Topic 11: Respondent Group Confirmation
**Interview evidence:** Registrar staff, Guidance staff, Campus Director, and applicants confirmed as valid respondent groups
**Impact level:** MEDIUM — validates C2-05 respondent table
**Affected modules:** C2-05, C2-06

### Topic 12: Conversion Table / Rating Scale
**Interview evidence:** `[SIM-GUID-23]` — Guidance uses a conversion table for raw-to-standardized score mapping
**Impact level:** LOW — supports OMR feature design
**Affected modules:** C1-07, C1-08

---

## Editing Prompts Per Module

---

### C1-01_David_Background_P1.md

**Topic connections:** Topic 1 (OMR Correction), Topic 2 (Manual Encoding), Topic 3 (Applicant Volume), Topic 4 (No Notifications), Topic 5 (Informal Coordination), Topic 6 (No Audit Trail), Topic 10 (Manual Course Recommendation)

**EDITING PROMPT:**

Revise C1-01 (Background P1 — Core Problem Statement) based on the following interview findings:

1. **CORRECT the OMR reference:** The current text mentions "OMR overlay templates" as part of the existing process. This is inaccurate — Guidance does NOT use OMR. Remove any reference to existing OMR overlay usage. Instead, describe the actual manual scoring process: Guidance staff compare each answer sheet to a physical answer key, item by item, and tally scores by hand.

2. **Add specific processing data from interview:**
   - Replace `[N]` placeholder for annual applicant volume with "approximately 300 to 400 applicants per admission cycle" `[SIM-REG-10]`
   - Mention that manual scoring takes 2-3 days for a batch of 50 applicants `[SIM-GUID-08]`
   - Note that result release takes 1-2 weeks from exam date `[SIM-GUID-13]`
   - Note that admission slip generation takes 2-3 minutes per applicant using Word templates `[SIM-REG-02]`

3. **Strengthen the duplicate data entry problem:** Add that applicant data is entered twice — once in the Google Form and again manually in the Excel tracking sheet — creating opportunities for transcription errors and duplicate entries `[SIM-REG-01]`, `[SIM-REG-18]`.

4. **Add the communication gap:** Applicants must physically return to campus or call by phone to check their admission status, as there is no automated notification system `[SIM-REG-03]`.

5. **Mention the informal coordination:** Exam scheduling between Registrar and Guidance relies on verbal communication and text messages with no shared scheduling system `[SIM-REG-04]`.

6. **Add data integrity vulnerability:** Score results in Excel have no audit trail — modifications are undetectable `[SIM-GUID-05]`.

7. **Mention course recommendation burden:** Guidance manually cross-references exam scores against program quota lists to generate course recommendations `[SIM-GUID-20]`.

**Evidence tags to add:** `[SIM-REG-01]`, `[SIM-REG-02]`, `[SIM-REG-03]`, `[SIM-REG-04]`, `[SIM-REG-10]`, `[SIM-REG-18]`, `[SIM-GUID-01]`, `[SIM-GUID-05]`, `[SIM-GUID-08]`, `[SIM-GUID-13]`, `[SIM-GUID-20]`

---

### C1-04_Jaypee_Background_P4.md

**Topic connections:** Topic 1 (OMR Correction), Topic 2 (Manual Encoding), Topic 3 (Applicant Volume), Topic 4 (No Notifications), Topic 5 (Informal Coordination), Topic 6 (No Audit Trail), Topic 7 (Data Privacy), Topic 8 (Offline Capability)

**EDITING PROMPT:**

Revise C1-04 (Background P4 — Local Context) based on the following interview findings:

1. **Replace `[N]` applicant volume with "approximately 300 to 400"** per admission cycle, with peak days seeing 30 to 50 applicants `[SIM-REG-10]`.

2. **CORRECT any reference to OMR overlays in the local context.** If the text implies Guidance uses OMR technology, remove it. The local process is fully manual scoring.

3. **Add the offline/infrastructure context:** Internet outages occur 1-2 times per week during the rainy season, sometimes lasting an entire afternoon, which disrupts access to Google Form responses and digital coordination `[SIM-REG-20]`.

4. **Add data privacy gap:** The Registrar's tracking spreadsheet is not password-protected, physical folders are the primary document store with no digital backup, and there is no formal data privacy protocol beyond a locked filing cabinet and computer account password `[SIM-REG-19]`.

5. **Strengthen the narrative about the complete workflow:** The local pipeline involves Google Forms (application) → manual re-encoding to Excel → Word template admission slips → physical handoff to Guidance → paper-based exam → manual scoring against answer key → Excel result compilation → manual transfer to Registrar → applicants returning in person for results.

6. **Add the document loss and duplication risks:** Physical files are occasionally misplaced requiring applicant resubmission, and duplicate entries occur in the tracking spreadsheet `[SIM-REG-18]`.

**Evidence tags to add:** `[SIM-REG-01]`, `[SIM-REG-10]`, `[SIM-REG-18]`, `[SIM-REG-19]`, `[SIM-REG-20]`, `[SIM-GUID-01]`

---

### C1-07_Jaypee_IPO_Diagram.md

**Topic connections:** Topic 1 (OMR Correction), Topic 12 (Conversion Table)

**EDITING PROMPT:**

Revise C1-07 (IPO Diagram) based on interview findings:

1. **Review Input items for accuracy.** The diagram currently lists inputs based on the system's design. Verify that "Answer sheet image/scans for OMR processing" is framed as a PLANNED capability (the system will offer OMR scanning), not as something that currently exists at ISPSC. The current process has no OMR — only paper answer sheets scored manually.

2. **Ensure the Process section accurately reflects:** The system is being developed to INTRODUCE capabilities (OMR scanning, automated scoring, audit trails) that do not currently exist at the institution.

3. **Add conversion table/rating scale as an input if not already present.** Guidance uses a conversion table for mapping raw scores to standardized ratings `[SIM-GUID-23]`. This is an input the system must receive.

**Evidence tags to add:** `[SIM-GUID-01]`, `[SIM-GUID-23]`

---

### C1-08_Jaypee_Framework_Narrative.md

**Topic connections:** Topic 1 (OMR Correction), Topic 12 (Conversion Table)

**EDITING PROMPT:**

Revise C1-08 (Framework Narrative) based on interview findings:

1. **Check for any implicit assumption that OMR or automated scoring exists at ISPSC.** The narrative should make clear that these are NEW capabilities the system introduces. If the narrative says "the system receives answer sheets and processes them through OMR scanning," frame it as the system's designed capability, not the current institutional practice.

2. **Mention the conversion table in the narrative.** The system processes raw scores through a configurable conversion table that maps raw exam scores to standardized ratings, matching Guidance's existing rating scale methodology `[SIM-GUID-23]`.

**Evidence tags to add:** `[SIM-GUID-01]`, `[SIM-GUID-23]`

---

### C1-09_Jaypee_Objectives.md

**Topic connections:** Topic 3 (Applicant Volume), Topic 9 (Director Support)

**EDITING PROMPT:**

Minor revision to C1-09 (Objectives):

1. **Verify that Objective 1's scope includes** the specific gaps confirmed by the interview: manual scoring processes, informal office coordination, lack of audit trails, absence of automated notifications, and data privacy gaps.

2. **Verify that Objective 2's development scope** includes: automated OMR scanning (as a new capability), role-based proctor delegation, live dashboard for the Director, AI companion for applicant inquiries, and quota management — all confirmed as desired by the interview respondents.

3. **No structural changes needed** — objectives already reference SUS and NASA-TLX per STRATEGY_NOTES. Just ensure alignment with confirmed interview data.

**Evidence tags to add:** `[SIM-REG-03]`, `[SIM-GUID-01]`, `[SIM-GUID-10]`, `[SIM-DIR-04]`, `[SIM-DIR-07]`, `[SIM-DIR-08]`

---

### C1-10_Jaypee_Research_Questions.md

**Topic connections:** Topic 3 (Applicant Volume), Topic 6 (No Audit Trail), Topic 7 (Data Privacy)

**EDITING PROMPT:**

Minor revision to C1-10 (Research Questions):

1. **Verify RQ1** asks about existing processes including the specific confirmed gaps: manual answer-key scoring (not OMR), informal scheduling, no audit trails, no status notifications, and data privacy weaknesses.

2. **Verify RQ2** asks about required features including OMR scanning, RBAC for proctor delegation, live dashboard, AI companion, and quota tracking — all confirmed as desired by Director and staff.

3. **No structural changes** — just ensure specificity matches interview findings.

**Evidence tags to add:** `[SIM-GUID-01]`, `[SIM-GUID-05]`, `[SIM-DIR-04]`, `[SIM-DIR-05]`

---

### C1-11_David_Scope_Delimitations.md

**Topic connections:** Topic 1 (OMR Correction), Topic 7 (Data Privacy), Topic 8 (Offline Capability)

**EDITING PROMPT:**

Revise C1-11 (Scope and Limitations) based on interview findings:

1. **Verify scope includes OMR scanning as a system feature** (planned capability, not existing institutional practice). Make sure it's clear the system will introduce this, not replace an existing OMR system.

2. **Add offline capability to the scope** — the system is designed with offline-resilient features given the confirmed internet instability (1-2 outages per week during rainy season) `[SIM-REG-20]`.

3. **Verify limitations section mentions:** The system does not fully eliminate physical document submission (hybrid approach confirmed as preferred by Registrar) `[SIM-REG-26]`. Data privacy compliance is a system feature, but the institution's broader data privacy protocols are outside the system's scope.

4. **Verify RA 10173 reference** is present and accurate per Director's emphasis on compliance `[SIM-DIR-06]`.

**Evidence tags to add:** `[SIM-REG-20]`, `[SIM-REG-26]`, `[SIM-GUID-01]`, `[SIM-DIR-06]`

---

### C1-12_Jaypee_Significance.md

**Topic connections:** Topic 2 (Manual Encoding), Topic 3 (Processing Time), Topic 4 (No Notifications), Topic 6 (No Audit Trail), Topic 9 (Director Support), Topic 10 (Course Recommendations)

**EDITING PROMPT:**

Revise C1-12 (Significance of the Study) based on interview findings:

1. **For the Community/Community paragraph:** Add that applicants from remote municipalities currently travel up to two hours and make multiple physical visits due to the lack of online status tracking `[SIM-APP-03]`. The system reduces this burden.

2. **For the Client Institution paragraph:** Add specific workload reduction claims — automated slip generation could halve processing time per applicant `[SIM-REG-21]`, automated scoring could reduce 2-3 day scoring batches to minutes `[SIM-GUID-08]`, and a live dashboard replaces half-day manual report compilation `[SIM-REG-24]`.

3. **For the Respondents paragraph:** Mention that both Registrar and Guidance staff confirmed they would use and benefit from the proposed system features.

4. **Add data integrity significance:** The system introduces audit trails and role-based access control where currently none exist — Excel files with no modification tracking and spreadsheets without password protection `[SIM-GUID-05]`, `[SIM-REG-19]`.

5. **Add course recommendation significance:** Automated quota tracking and course recommendations replace the current manual cross-referencing process `[SIM-GUID-20]`, `[SIM-GUID-25]`.

**Evidence tags to add:** `[SIM-REG-21]`, `[SIM-REG-24]`, `[SIM-APP-03]`, `[SIM-GUID-05]`, `[SIM-GUID-08]`, `[SIM-GUID-20]`, `[SIM-GUID-25]`

---

### C2-01_David_Research_Design.md

**Topic connections:** Topic 9 (Director Support), Topic 11 (Respondent Confirmation)

**EDITING PROMPT:**

Minor revision to C2-01 (Research Design):

1. **Verify the descriptive component** accurately describes the data gathering method — interviews with Registrar staff, Guidance staff, Campus Director, and applicant intercept surveys. The interview confirmed all four respondent groups are accessible and willing.

2. **Verify the developmental component** description aligns with confirmed stakeholder requirements from the interview.

3. **No structural changes needed** — research design is methodologically sound. Just ensure the descriptive paragraph references the actual interview blocks (Registrar, Guidance, Director, Applicants).

**Evidence tags to add:** `[SIM-REG-01]`, `[SIM-GUID-01]`, `[SIM-DIR-01]`, `[SIM-APP-01]`

---

### C2-02_David_Software_Model.md

**Topic connections:** Topic 1 (OMR Correction), Topic 9 (Director Support)

**EDITING PROMPT:**

Minor revision to C2-02 (Software Model):

1. **In the Inception phase description:** Verify it describes how interviews and observations (the actual June 8 data gathering) informed technical specifications. Reference the confirmed process gaps — manual scoring, informal scheduling, no audit trails — as inputs to the requirements specification.

2. **In the Construction phase description:** Ensure OMR scanning is described as a NEW capability being built, not a replacement of existing OMR infrastructure. The current process has no OMR technology `[SIM-GUID-01]`.

3. **No structural changes** — AIDLC model choice is locked per STRATEGY_NOTES.

**Evidence tags to add:** `[SIM-GUID-01]`, `[SIM-REG-10]`

---

### C2-04_Christine_Project_Assignment.md

**Topic connections:** Minimal direct impact — project roles are team-internal

**EDITING PROMPT:**

Minimal revision needed:

1. **Verify role descriptions** are consistent with the interview data gathering activities — David as lead interviewer, Christine as audio/observer, Jaypee as roamer/process mapper. This validates the team's capacity to conduct the research.

2. **No content changes required** unless role functions need to explicitly mention "conducts interviews and observations at client site."

---

### C2-05_Christine_Population_Locale.md

**Topic connections:** Topic 3 (Applicant Volume), Topic 11 (Respondent Confirmation)

**EDITING PROMPT:**

Revise C2-05 (Population and Locale) based on interview findings:

1. **Replace `[N]` placeholders** in the respondent distribution table:
   - Registrar staff: 2-3 (estimated from interview context)
   - Guidance staff: 1-2 (estimated from interview context)
   - Campus Director: 1
   - Applicant intercepts: ~4-6 per data gathering day
   - **Total: ~8-12 respondents** (adjust based on final sampling decision)

2. **Verify the locale description** mentions ISPSC Tagudin as a single-campus deployment site with 300-400 applicants per cycle, supporting the purposive sampling rationale.

3. **Ensure the sampling technique paragraph** references the interview approach: purposive sampling targeting staff directly involved in the admission process plus convenience sampling for applicant intercepts.

**Evidence tags to add:** `[SIM-REG-10]`, `[SIM-REG-01]`, `[SIM-GUID-01]`, `[SIM-DIR-01]`

---

### C2-06_Christine_Research_Instruments.md

**Topic connections:** Topic 11 (Respondent Confirmation)

**EDITING PROMPT:**

Minor revision to C2-06 (Research Instruments):

1. **Verify respondent-instrument mapping** matches the interview confirmation:
   - SUS administered to Registrar staff and Guidance staff (actual system users)
   - NASA-TLX administered to the same groups to measure perceived workload reduction

2. **Resolve the STEER marker** about confirming respondent groups: Registrar staff, Guidance staff, and Campus Director are confirmed as the primary evaluation respondents. Applicant intercepts are for process understanding, not system evaluation.

3. **No structural changes** — dual instrument strategy (SUS + NASA-TLX) is locked per STRATEGY_NOTES.

**Evidence tags to add:** `[SIM-REG-01]`, `[SIM-GUID-01]`, `[SIM-DIR-01]`

---

## Prompts for NEW Modules (Tasks 4-9)

These prompts will be used when generating the missing modules. Each prompt references the interview data.

### C1-02 (Background P2 — Global Context) — Generation Prompt

Write the Global Context paragraph (P2) for the Background of the Study. Requirements per GUIDE-2:
- Minimum 5 citations from 2022-2026 sources
- Discuss the international landscape of college admission testing systems, digital transformation in higher education admissions, automated scoring technologies (including OMR), role-based access control in educational systems, and AI-assisted admission processes
- Frame the global context to build toward the research gap: despite global advances, regional Philippine SUCs like ISPSC Tagudin still rely on manual processes
- Interview-grounded elements: the global trend toward automated scoring (OMR) contrasts with ISPSC's fully manual answer-key scoring `[SIM-GUID-01]`; the global shift to applicant self-service portals contrasts with ISPSC's requirement for physical visits `[SIM-REG-03]`; international data privacy standards contrast with ISPSC's minimal protections `[SIM-REG-19]`

### C1-03 (Background P3 — National Context) — Generation Prompt

Write the National Context paragraph (P3) for the Background of the Study. Requirements per GUIDE-2:
- Minimum 5 citations from 2022-2026 sources
- Discuss Philippine higher education landscape, CHED policies on admission processes, RA 10931 (free tertiary education) and its impact on application volumes, RA 10173 (Data Privacy Act) compliance requirements for educational institutions, and the digitalization efforts of Philippine SUCs
- Frame the national context to establish: the surge in applicants under free tertiary education (300-400 per cycle at ISPSC) `[SIM-REG-10]`, the gap between national policy ambition and institutional infrastructure capacity, and the data privacy compliance urgency given current minimal protections
- Interview-grounded elements: RA 10931 drives applicant volume increases that strain manual processes `[SIM-REG-10]`, RA 10173 compliance is non-negotiable per Director `[SIM-DIR-06]`, the digitalization gap between metro universities and regional SUCs

### C1-05 (Background P5 — Synthesis & Gap Identification) — Generation Prompt

Write the Synthesis and Gap Identification paragraph (P5) for the Background of the Study. Requirements per GUIDE-2:
- Synthesize global, national, and local findings — do NOT summarize author by author
- Explicitly name the research gap: no existing study or system adequately addresses the combined challenges of (a) fully manual admission testing in a regional Philippine SUC, (b) absence of automated scoring despite high applicant volume, (c) lack of inter-office coordination systems, (d) no audit trails or data privacy safeguards, and (e) no applicant-facing self-service capabilities
- Ground the gap in confirmed interview data: manual scoring takes 2-3 days per batch `[SIM-GUID-08]`, no OMR technology in use `[SIM-GUID-01]`, no audit trails `[SIM-GUID-05]`, informal coordination `[SIM-REG-04]`, applicants must return in person `[SIM-REG-03]`

### C1-06 (Background P6 — Clinching Statement) — Generation Prompt

Write the Clinching Statement paragraph (P6) for the Background of the Study. Requirements per GUIDE-2:
- Three required components: (1) How the reviewed literature assisted in structuring the study, (2) Why the research topic was selected — include direct observation of the problem at ISPSC Tagudin, (3) Why SecureCAT is the critical solution
- Optional: connect to SDG 4 (Quality Education) or SDG 16 (Peace, Justice, and Strong Institutions)
- Interview-grounded: direct observation of the Registrar Office's manual encoding, the Guidance Office's hand-scoring process, the Director's expressed desire for digital transformation, and applicant frustration with long waits

### C2-03 (Project Plan) — Generation Prompt

Write the Project Plan section for Chapter 2. Requirements per GUIDE-3:
- Present a Gantt chart as a figure with caption "Figure [N]. Project Gantt Chart"
- Gantt phases MUST match exactly the AIDLC phases from C2-02: Inception, Construction, Operations
- Write a paragraph below the chart mapping each phase to actual calendar weeks/months based on the ROADMAP timeline:
  - Inception: May-June 2026 (interviews, requirements, architecture)
  - Construction: June-July 2026 (development, testing, iteration)
  - Operations: July-August 2026 (deployment, evaluation, documentation)
- Reference the ROADMAP.md timeline phases

### C2-07 (Data Analysis) — Generation Prompt

Write the Data Analysis section for Chapter 2. Requirements per GUIDE-3:
- Written entirely in paragraph form
- Link each analysis method to a specific research objective:
  - Objective 1 (Identify): Qualitative thematic analysis of interview transcripts and observational notes
  - Objective 2 (Develop): Design validation through iterative user feedback during development
  - Objective 3 (Evaluate): Descriptive statistics — mean SUS score, mean NASA-TLX subscale scores, standard deviation, frequency distribution
- State explicitly that statistical significance testing is NOT employed (appropriate for descriptive studies with purposive samples)
- Include the SUS Score Interpretation Table (per GUIDE-3)
- Include the NASA-TLX scoring methodology: six subscales (Mental Demand, Physical Demand, Temporal Demand, Performance, Effort, Frustration) rated 0-100, weighted or unweighted composite
- Reference dual instrument strategy from STRATEGY_NOTES
