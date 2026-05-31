# GUIDE 3: CHAPTER 2 — METHODOLOGY CONTENT REQUIREMENTS

---

## Opening Paragraph (Mandatory Boilerplate)

Chapter 2 must open with this exact standard block **before any subheading**:

> "This chapter discusses the procedure that is followed in order to collect the information needed for the study and conduct the analysis that is required in the study. In this segment, we describe the research design, software model, project strategy, project assignments, population and locale, research instruments, and data analysis that are associated with the study. The details are also discussed regarding how the respondents were collected and how the research was sampled."

Use this verbatim. Do not paraphrase it.

---

## Section 1: Research Design

### Required content:

1. **Define "descriptive developmental research design"** with a citation. Use Siedlecki (2020) for the descriptive component. Find a 2022–2026 source for the developmental component — Richey & Klein (2014) is the classic reference but falls outside the required publication window, so supplement or replace it.
2. In a **second paragraph**, explain how descriptive developmental research applies **specifically to your project** — what the descriptive component does (observing and documenting existing conditions) and what the developmental component does (iteratively building and validating the system).

---

## Section 2: Software Model

### Subheading:
Use exactly **"Software Model"** — not "Software Development Model."

### Format rules:
- All discussion in **paragraph form** — no bullets, no numbered lists.
- Each phase must follow this pattern: **Phase Name. In this phase, [specific tasks and activities performed by the researcher].**
- Name specific tools, technologies, and activities actually used — generic descriptions are insufficient.

### Two valid model choices — pick one and commit:

---

### Option A: Rapid Application Development (RAD) — Standard Choice

Cite: Kendall & Kendall (2021) — note this is outside 2022–2026, so supplement with a 2022–2026 source that references or validates RAD in a similar context.

**Four phases:**

**Requirements Planning.** Describe the interviews, observations, and documentation activities you performed to define system requirements. Name the specific processes you studied at the client site. State the outputs of this phase (data schemas, architecture decisions, feature list).

**System Design.** Describe the prototyping and mockup activities. Name the interface components you designed (admin panel, user-facing screens, etc.). State that designs were iteratively refined through user feedback.

**Construction.** Name the specific technologies used in your stack. Describe the development and testing activities performed (unit testing, integration testing, debugging).

**Cutover.** Describe pilot deployment, user training, SUS evaluation administration, and documentation finalization.

---

### Option B: AI-Driven Development Lifecycle (AIDLC) — Advanced Choice

Use this if your development process involved AI agents (Cursor, Copilot, Claude, Amazon Q, etc.) as primary development tools — it accurately names what you actually did. See Guide 4 for the full defense strategy and panel scripts.

**Citation requirements:**
- Addla, N. (2026). AI-Driven Development Lifecycle (AI-DLC): Reimagining software engineering for the AI era. *International Journal of Artificial Intelligence, Data Science, and Machine Learning*, 7(1), 266–270. https://doi.org/10.63282/3050-9262.IJAIDSML-V7I1P145
- Raja, S. P. (2025, August 12). AI-driven development life cycle: Reimagining software engineering. *AWS DevOps Blog.* https://aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/

**Three phases:**

**Inception.** Describe the Mob Elaboration process — how AI assistants were used to translate client-site observations into technical specifications, data schemas, and architectural decisions.

**Construction.** Describe Mob Construction — how AI agents accelerated development of your backend and frontend, including autonomous code generation, automated unit testing, and rapid iteration based on user feedback.

**Operations.** Describe pilot deployment and AI-assisted monitoring — validation of system reliability, verification of audit trails or logging, and finalization for evaluation.

---

### Required regardless of model chosen:

- Include a **figure** (diagram of the model's phases and flow).
- Figure must be readable without dominating the entire page.
- Caption placed **below** the figure, bold: **Figure [N]. [Model Name]**

---

## Section 3: Project Plan

### Requirements:

1. Present a **Gantt Chart** as a figure. Caption: **Figure [N]. Project Gantt Chart**
2. The phases in the Gantt Chart must **match exactly** the phases described in the Software Model section — no new phases, no renamed phases.
3. Write a **paragraph below the chart** that maps each phase to actual calendar weeks or months, explains any phase overlaps, and justifies the overall timeline.

---

## Section 4: Project Assignments

### Format:

Present as a table with a 1pt border. Caption aligned left, above the table.

**Table 1. Project Roles and Responsibilities**

| Roles | Name | Functions |
|---|---|---|
| Project Manager | [Name] | Coordinates project activities; communicates with adviser and stakeholders; manages timeline and resources. |
| System Analyst and Designer | [Name] | Analyzes requirements; designs system architecture and database schema; creates interface mockups. |
| Lead Developer and Programmer | [Name] | Develops all core modules; implements backend and frontend; conducts unit and integration testing. |
| Quality Assurance Tester | [Name] | Develops test cases; conducts usability evaluation; validates system reliability; documents and resolves defects. |
| Technical Writer and Documenter | [Name] | Prepares the research manuscript, technical documentation, and user manuals. |

For a solo project, list the researcher's name for every role. This demonstrates comprehensive engagement across all project functions.

---

## Section 5: Population and Locale of the Study

### Format:

- Describe the **locale** (institution name, location, population served, programs or operations relevant to your system) in paragraph form.
- Describe the **sampling technique** used to select respondents (typically purposive sampling for IT capstones) in paragraph form.
- Present respondent distribution in a **table with 1pt border**. Caption aligned left, above the table.

**Table 2. Distribution of Respondents**

| Respondents | n |
|---|---|
| [Role / Group 1] | [n] |
| [Role / Group 2] | [n] |
| [Role / Group 3] | [n] |
| **TOTAL** | **[n]** |

Standard respondent groups for a government or institutional system: administrator(s), frontline staff, and IT experts or validators.

---

## Section 6: Research Instruments

### Requirements:

- State the name of the instrument: **System Usability Scale (SUS)**.
- Describe the instrument: 10 items, 5-point Likert scale (Strongly Disagree to Strongly Agree), composite score of 0–100.
- Note the benchmark: scores above 68 are considered above average.
- If citing Brooke (1996) as the original source, it falls outside the 2022–2026 window — supplement with a 2022–2026 study that uses or validates SUS in a comparable context.
- **All literature cited in this section must be 2022–2026.**
- Written entirely in **paragraph form**.

---

## Section 7: Data Analysis

### Requirements:

- Written in **paragraph form**.
- Explicitly link each analysis method to a specific research objective. Every objective must have a named analytical approach.

**Standard linkage for a three-objective IT capstone:**

| Objective | Method |
|---|---|
| Objective 1 — Identify existing processes | Qualitative thematic analysis of interview transcripts and observational notes |
| Objective 2 — Develop the system | Design validation through iterative user feedback during development |
| Objective 3 — Evaluate usability | Descriptive statistics: mean SUS score, standard deviation, frequency distribution |

- State explicitly that **statistical significance testing is not employed** (appropriate for descriptive studies with purposive samples).
- Include the SUS score interpretation table.

**Table 3. System Usability Scale Score Interpretation** *(caption aligned left, above the table, 1pt border)*

| SUS Score Range | Grade | Adjective Rating | Acceptability |
|---|---|---|---|
| 80.3 – 100 | A | Excellent | Very Highly Acceptable |
| 68.0 – 80.2 | B | Good | Highly Acceptable |
| 52.0 – 67.9 | C | Okay | Acceptable |
| 38.0 – 51.9 | D | Poor | Marginally Acceptable |
| 0 – 37.9 | F | Awful | Not Acceptable |

---

## References Section

- Format: **APA 7th Edition**
- Order: **Alphabetical by first author's last name**
- Validation checklist before submission:
  - [ ] Every citation in Chapter 1 Background is present in References
  - [ ] Every citation in Chapter 2 Software Model is present in References
  - [ ] Every citation in Chapter 2 Research Instruments is present in References
  - [ ] All entries fall within 2022–2026 (flag any outside this range and justify or replace)
  - [ ] If AIDLC is chosen: Addla (2026) and Raja (2025) are included
