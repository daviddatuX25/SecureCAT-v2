# Strategic Notes & Defense Positions
## SecureCAT-v2 Capstone — Persistent Reference

> **Purpose:** Capture strategic decisions, rationales, and defense positions for panel defense preparation. These are the "why" behind methodological and instrument choices.
>
> **Last Updated:** June 3, 2026

---

## 1. Research Instruments: SUS + NASA-TLX (Dual Instrument Strategy)

### Decision
Use **both** the System Usability Scale (SUS) and the NASA Task Load Index (NASA-TLX) as research instruments for Objective 3 (Evaluate).

### Rationale
- **SUS** measures perceived usability — "Is the system easy to learn and navigate?" (attitude-based)
- **NASA-TLX** measures perceived task workload — "Does the system actually reduce the mental, physical, and temporal burden on staff?" (performance-based)
- Together they prove both **ease of adoption** (SUS) and **operational relief** (NASA-TLX) — a stronger evaluation than SUS alone
- This dual-instrument approach elevates the evaluation beyond the typical single-survey BSIT capstone, which aligns with the "Trojan Horse" strategy of locked title but elevated scope

### Source of This Decision
C1-09 (Objectives of the Study) draft explicitly names both instruments. The specific objective 3 reads: "Evaluate the usability and perceived task workload of the developed system using the System Usability Scale (SUS) and the NASA Task Load Index (NASA-TLX)."

---

## 2. Software Model: AIDLC — Chosen for Honesty and Accuracy

### Decision
Use **AI-Driven Development Lifecycle (AIDLC)** as the software model (not RAD).

### Rationale — Why AIDLC Over RAD

**Primary reason: Honesty.** The development of SecureCAT-v2 genuinely involved AI agents (Hermes, Claude, Cursor, Copilot, or similar) as the primary engine of code generation, testing, and iteration. Choosing RAD when the actual workflow was AI-assisted would be a misrepresentation of the development process.

**Secondary reasons:**
- AIDLC accurately names what was actually done — it is the truthful description of the workflow
- It has formal academic backing: Addla (2026) in *International Journal of AI, Data Science, and Machine Learning* and Raja (2025) from the AWS DevOps Blog
- It was designed specifically for solo/small-team AI-assisted development — which matches the capstone reality
- RAD was designed in the 1990s for human-only coding sprints — it does not account for autonomous code generation or AI-led testing

### Citations Ready
- Addla, N. (2026). AI-Driven Development Lifecycle (AI-DLC): Reimagining software engineering for the AI era. *International Journal of Artificial Intelligence, Data Science, and Machine Learning*, 7(1), 266–270.
- Raja, S. P. (2025, August 12). AI-driven development life cycle: Reimagining software engineering. *AWS DevOps Blog.*

---

## 3. AIDLC Defense Position: Rebuttal to "AI-Generated Code Is Unqualified"

### The Anticipated Criticism
Panelists may challenge AIDLC with the angle that AI-generated code is unreliable, unqualified, or that the student "didn't really build it." The concern is that AI-assisted development shortcuts learning and produces unverified code.

### The Rebuttal

**AI-generated code is not shipped unreviewed.** Every line of AI-generated code undergoes consistent and thorough code review by the capstone IT students. The workflow is:

1. **AI generates** — the AI agent produces initial code based on specifications
2. **Human reviews** — the student developer reviews every generated line for correctness, security, architectural fit, and adherence to project conventions
3. **Human validates** — automated test suites (unit tests, integration tests) are run against the generated code to verify behavioral correctness
4. **Human iterates** — if code fails review or tests, the human guides corrections or rewrites

This is not passive acceptance of AI output. It is **active human oversight** at every step. The student is the architect, reviewer, and quality gate — the AI is a force multiplier that accelerates the typing, not the thinking.

**Key framing:** AIDLC does not replace the developer's competence — it amplifies it. The developer still needs to:
- Understand the architecture deeply enough to evaluate AI output
- Identify security vulnerabilities the AI may miss
- Ensure code aligns with Laravel best practices, project conventions, and business logic
- Write and maintain test suites that catch AI mistakes
- Make all final architectural and design decisions

**Industry validation:** This is not a student invention. Enterprise companies (Wipro, Dhan, EPAM Systems) use this exact workflow in production. Wipro compressed 3 months of work into 20 hours using AI-DLC — but their engineers still reviewed every line before deployment.

### Panel Script (If Challenged)

> "The concern about AI-generated code quality is valid and precisely why our development process includes rigorous human review at every stage. Every code segment produced by AI assistance was reviewed, tested, and validated by the student developer before integration. We maintain automated test suites, follow Laravel coding standards, and enforce architectural conventions that catch errors — whether human-written or AI-generated. AIDLC does not remove the developer from the loop — it repositions the developer from writer to reviewer and architect, which is the same transformation happening across the professional software industry today."

---

## Quick Reference: AIDLC vs RAD Comparison

| Dimension | RAD | AIDLC |
|-----------|-----|-------|
| Design era | 1990s — human-only sprints | 2025–2026 — AI-human collaboration |
| Code generation | Manual by developer | AI-assisted via agents |
| Testing | Manual QA cycles | AI-led automated test generation |
| Iteration speed | Days to weeks | Hours to days |
| Fit for solo developer | Good | Excellent — AI fills the roles of the rest of the team |
| Honesty of description | Misrepresents AI-assisted workflow | Accurately describes what actually happened |
| Academic backing | Traditional | Addla (2026), Raja (2025), AWS Labs |
| Defense risk | Low (familiar to panel) | Medium (may need rebuttal — see above) |

---

*This document should be referenced when writing C2-01 (Research Design), C2-02 (Software Model), C2-06 (Research Instruments), C2-07 (Data Analysis), and during panel defense preparation.*
