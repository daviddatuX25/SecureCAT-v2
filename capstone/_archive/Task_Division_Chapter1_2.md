# Team Work Division Strategy
## Chapters 1–2 Writing Tasks

This document defines the work distribution for three collaborators: **1 Team Lead** and **2 Members**.

---

## Role Definitions

### Team Lead
- Overall quality control and integration.
- Ensures narrative consistency across chapters.
- Resolves conflicts between member outputs.
- Prepares the final manuscript for proposal readiness checks.

### Member 1 — Chapter Lead
- Primary writer for Chapter 1.
- Compiles Chapter 2 literature support blocks assigned to Member 1.

### Member 2 — Chapter Lead
- Primary writer for Chapter 2.
- Compiles Chapter 2 framework and system-review deliverables assigned to Member 2.
- Provides feedback on Chapter 1 scope/structure where needed.

---

## Chapter 1 Writing Division

### Member 1 Responsibilities

| Section | Task | Output |
|---|---|---|
| 1. Introduction | Write narrative paragraphs on admission testing context and the system’s rationale. | `capstone/drafts/chapter01/introduction.md` |
| 2. Objectives | Draft General Objectives and Specific Objectives aligned to title and capstone features. | `capstone/drafts/chapter01/objectives.md` |
| 3. Research Questions | Formulate operational and research questions. | `capstone/drafts/chapter01/research_questions.md` |
| 4. Scope and Delimitations | Draft inclusion and delimitation statements; include both baseline and advanced scopes. | `capstone/drafts/chapter01/scope_delimitations.md` |
| 5. Significance | Write mapping of beneficiaries and study impact. | `capstone/drafts/chapter01/significance.md` |

### Member 2 Responsibilities

| Section | Task | Output |
|---|---|---|
| 1. Introduction review | Cross-check Member 1 introduction against roadmap and features. | Feedback in PR comment |
| 2. Diagram labels | Supply labels and captions for any figures introduced in Introduction. | `capstone/drafts/chapter01/figures_labeling.md` |
| 3. Scope review | Validate delimitation decisions with current implementation. | Feedback in PR comment |
| 4. Significance review | Supplement significance remarks with real institution context. | Feedback in PR comment |

---

## Chapter 2 Writing Division

### Member 2 Responsibilities

| Section | Task | Output |
|---|---|---|
| 1. Review of Related Literature | Write coverage of RBAC, zero trust, data integrity, admission testing, OMR, AI copilots, offline mobile systems, privacy compliance frameworks. | `capstone/drafts/chapter02/related_literature.md` |
| 2. Technical Framework | Write technical framework covering Laravel/Inertia/Svelte stack, HMAC security model, Vector embeddings service, Frontend PWA architecture, Multi-tenant database isolation concepts. | `capstone/drafts/chapter02/technical_framework.md` |
| 3. Conceptual framework diagram | Draft the concept map / IPO-style diagram. | `capstone/drafts/chapter02/conceptual_framework_diagram.md` |

### Member 1 Responsibilities

| Section | Task | Output |
|---|---|---|
| 1. Review of Related Systems | Write coverage of existing local and international admission systems; OMR graders; office automation systems. | `capstone/drafts/chapter02/related_systems.md` |
| 2. Conceptual framework prose | Expand conceptual framework into text narrative aligned with system workflow stages. | `capstone/drafts/chapter02/conceptual_framework_prose.md` |
| 3. Anti-pattern check | Review literature and framework for consistency and scope alignment. | Feedback in PR comment |

---

## Shared Preparation Tasks

| Task | Owner | Deadline |
|---|---|---|
| Create capstone/drafts directory tree | Member 1 | Day 1 |
| Prepare shared reference bibliography | Member 2 | Day 2 |
| Agree on document template and formatting | Both | Day 1 |
| Leadership review checklist preparation | Lead | Day 1 |

---

## Integration Workflow

1. Each member creates their assigned sections in `capstone/drafts/chapter0X/`.
2. Members open branch-specific PRs for review by the opposing member.
3. Team Lead performs a final integration review after all PRs are merged.
4. Lead publishes the compiled chapter drafts to the main branch for reference.

---






