# GUIDE 2: CHAPTER 1 — CONTENT REQUIREMENTS
## Introduction

---

## Literature Rule (Applies to All Sections of Chapter 1)

- All cited literature must be **published between 2022 and 2026**.
- Every citation used in the text **must appear in the References list**.
- Cross-check before submission: every reference cited in the manuscript must be present in References.

---

## Section 1: Background of the Study

### Structure: Exactly 6 Paragraphs

The Background follows a **funnel structure** — from broad (global) down to specific (local), then synthesizing, then closing.

---

### Paragraph 1 — Core Problem (No citations required)

- Written **entirely in your own words**.
- Focus explicitly on the **keywords from your system title** — name the exact problem your system solves.
- This is your IT framing paragraph. Do **not** make it sound like a public administration or management paper. State the actual technical gap: what digital intervention is missing, and why its absence causes the documented problem.
- Name the observable symptoms first (manual processes, inefficiencies, errors) then pivot to the underlying technical root cause.

---

### Paragraph 2 — Global Context (Minimum 5 citations)

- Discuss your **topic domain internationally** — how it is handled globally, what research says, what technologies exist.
- Cover market trends, efficiency findings, architectural debates, and adoption patterns relevant to your domain.
- All 5+ sources must be 2022–2026.

---

### Paragraph 3 — National Context (Minimum 5 citations)

- Discuss the **Philippine scenario** — national policies, government mandates, or sector-wide conditions that make your problem relevant at the national level.
- Name specific legislation, agency directives, or national programs that create the operational context your system addresses.
- All 5+ sources must be 2022–2026.

---

### Paragraph 4 — Local Context (Minimum 5 citations)

- Detail the **specific operational environment of your client** — the office, institution, or organization where the system will be deployed.
- Include: socioeconomic constraints, infrastructure realities, existing manual processes, and any audit or compliance pressures relevant to the locale.
- Cite studies from the same region or from comparable nearby institutions to establish local precedent.
- All 5+ sources must be 2022–2026.

---

### Paragraph 5 — Synthesis and Gap Identification

- Do **not** summarize each author one by one. **Synthesize** — group ideas, contrast findings, and show how they collectively reveal a gap.
- Explicitly name the research gap: what no existing study or system has adequately addressed.
- Distinguish what existing systems do (e.g., static information management, cloud-based records) from what your system does (e.g., dynamic real-time orchestration, offline-first operation).

**What synthesis looks like vs. summary:**

| Summary (avoid) | Synthesis (do this) |
|---|---|
| "Author A says X. Author B says Y. Author C says Z." | "While global studies confirm X (Author A, 2024), local contexts require Y due to Z constraint (Author B, 2023), a gap that existing implementations have not resolved." |

---

### Paragraph 6 — Clinching Statement

Three required components:
1. Explain **how the reviewed literature assisted in structuring the present study**.
2. State **why you selected this research topic** — include your direct observation of the problem at the client site.
3. Conclude by highlighting **why your proposed system is the critical solution** to the identified gap.

Optional but strengthens the argument: connect to a relevant Sustainable Development Goal (e.g., SDG 16 for governance, SDG 3 for health, SDG 4 for education).

---

## Section 2: Conceptual Framework of the Study

### IPO Diagram

- The diagram has three boxes: **Input → Process → Output**
- Use **numbered lists** (not bullets) inside the Input and Output boxes.
- Input should include **only the data, configurations, and parameters the system actually receives** — do not pad with vague descriptions.
- Output should include **only what the system actually produces** — do not include process verbs or activities.
- The center Process box contains your **system name and subtitle**.

### Checklist for IPO content:

**Input examples (adapt to your system):**
- User-submitted data (forms, requests, records)
- Configuration parameters (settings, rules, schedules)
- Source documents or existing records
- User roles and access levels

**Output examples (adapt to your system):**
- Generated reports or documents
- Real-time displays or notifications
- Audit logs
- Computed results or recommendations
- Stored records accessible to users

### Narrative Paragraphs

- Split into **exactly two paragraphs**.
- Paragraph 1: Explain what each input component is and why it is necessary for the system.
- Paragraph 2: Explain how the system processes those inputs and what outputs are produced as a result.
- Make the **mechanical connection explicit** — not just "inputs go in and outputs come out" but *how* the transformation happens inside the system.

---

## Section 3: Objectives of the Study

### Format

- One **general objective** paragraph naming the system and its overarching purpose.
- Specific objectives in a **numbered list** (not bullets).

### Standard three-objective structure for IT capstones:

1. **Objective 1 — Identify:** Document the existing manual processes, operational gaps, and requirements at the client site.
2. **Objective 2 — Develop:** Build the system with the specific features and architecture described in your scope.
3. **Objective 3 — Evaluate:** Assess the system using a named instrument (e.g., System Usability Scale).

### Objective 3 — Usability vs. Acceptability (choose one, commit)

You must use a single, precise term. The choice determines your instrument and analysis method.

| Term | What it measures | Correct instrument |
|---|---|---|
| **Usability** | How easily users can operate the system | System Usability Scale (SUS) |
| **Acceptability** | Whether users are willing to adopt the system | Separate survey or hybrid design |

If you are using SUS, your Objective 3 must say **"evaluate the usability"** — not "usability and acceptability," not "acceptability." SUS is a usability instrument. Mixing terms without separate instruments for each is methodologically weak and will be challenged by panelists.

---

## Section 4: Scope and Limitation of the Study

### Format Rules

- Written in **paragraph form only** — no bullets, no numbered lists.
- **Two paragraphs**: one for scope, one for limitations.

### Scope Paragraph must include:

- Authorized user types and their roles
- Modules and features included in the system
- Locale (institution name, location)
- Timeframe of development and deployment
- Principal variables the system handles
- Justification for why these boundaries were chosen

### Limitation Paragraph must include:

- What the system explicitly does **not** do (integrations excluded, data it does not handle)
- Hardware or network dependencies
- Single-site or access constraints
- Any manual processes that remain outside the system
- Data privacy boundaries — name the relevant law (e.g., RA 10173 Data Privacy Act of 2012) and describe how the system handles or avoids sensitive data

---

## Section 5: Importance of the Study

### Format Rules

- One paragraph per beneficiary group.
- Include only **direct beneficiaries** — people or institutions directly affected by the system.
- Do not include a general "new knowledge" paragraph at the end — weave contributions into each group's paragraph instead.
- Do not include vague or indirect beneficiaries.

### Standard beneficiary groups for an IT capstone:

| Group | What to address |
|---|---|
| The Community | How the system improves quality of service or access for end users |
| The Client Institution | Operational gains, reduced manual workload, improved accountability |
| The Respondents | Direct benefits experienced by those who participated and will use the system |
| The College / Department | How the capstone demonstrates practical IT application |
| The Students | Skills and knowledge gained through building the system |
| The Researchers | Competencies developed across the development lifecycle |
| Future Researchers | How this system serves as a baseline or reference for related future work |
