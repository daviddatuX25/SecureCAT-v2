# David — Directional Guide
## SecureCAT-v2 Capstone | Team Leader + Lead Developer

> **Based on:** [david_self_assessment.md](./Session%201%20-%20Meta%20Guide%20Responses/david_self_assessment.md)
> **Last updated:** June 1, 2026

---

## Profile Summary

| Dimension | Assessment |
|-----------|-----------|
| **Role** | Team Leader / Product Owner / Lead Developer |
| **Available hours** | ~55h (Jun 1–9, rest day Jun 7) |
| **Claimed tasks** | 11 tasks, ~33-46h estimated |
| **Buffer** | ~9-22h remaining after claimed work |
| **Task preference** | Fewer but harder |
| **Avoid** | Background paragraphs (C1-01 to C1-06), references, formatting QA, citation cross-check |

---

## Strengths (From Self-Assessment)

### Dominant Zones (YES)
- **Research (B1-B5):** Full sweep — all YES. Can find, evaluate, and use academic sources independently.
- **Technical Knowledge (C1, C2, C4, C6, C8):** RBAC, zero-trust/HMAC, PWA/offline, Laravel/Inertia/Svelte stack, and overall system architecture — all from direct development experience.
- **Strategic Writing (A4, A5):** Can identify research gaps and write clinching/concluding paragraphs.
- **Coordination (E1-E3):** Scheduling, web searches, willingness to learn APA.

### Growth Zones (MAYBE)
- **Academic Paragraph Writing (A1-A3):** Self-describes as "AI/search-aided; not fully independent." Can orchestrate but not solo-produce long academic paragraphs at speed.
- **Technical Depth Gaps (C3, C5, C7):** OMR/CV scoring, AI/RAG internals, multi-tenant DB — familiar but not deep. These are MAYBE but David chose to take C2-03 (AI/RAG) and C2-05 (DPA/Multi-Tenancy) anyway.

### Explicit Gaps (NO)
- **Formatting (D1-D5):** All NO — deliberately delegating all formatting, references, and cross-checking.

---

## Task Ownership — Execution Map

### Priority 1: Foundation Pieces (Jun 1-3)
These have **zero dependencies** and unlock other tasks.

| Task | What to Do | Effort | Unlocks |
|------|-----------|--------|---------|
| **C1-09** Objectives | Write General + 3 Specific Objectives. Use "usability" (not "acceptability") for Objective 3. Name system by full title. | 2-3h | C1-10 (Research Questions) |
| **C1-07** IPO Diagram | Design Input-Process-Output diagram. Reference `SYSTEM_FEATURES.md` and `Existing_and_Planned_Features.md`. Inputs = data received. Outputs = things produced. Numbered lists only. | 2-3h | C1-08, C2-08 |
| **C1-11** Scope & Delimitations | Paragraph form only. Include both existing modules and planned research modules. Frame advanced features as research contributions. | 2-3h | — |

### Priority 2: Framework & Narratives (Jun 3-5)
These depend on Priority 1 outputs.

| Task | What to Do | Effort | Depends On |
|------|-----------|--------|------------|
| **C1-08** Framework Narrative | Exactly 2 paragraphs: (1) explain each input, (2) explain transformation process through RBAC, scoring, offline, AI, crypto. | 2-3h | C1-07 |
| **C2-07** Technical Framework | Cover full stack: Laravel 12/Inertia v2/Svelte 5/Tailwind 4, HMAC, vector embeddings, PWA workers, multi-tenant, DOMPDF/PHPWord. Include architecture diagram. | 3-4h | — |

### Priority 3: Literature Reviews (Jun 4-6)
Heavy research blocks. 4 lit reviews × ~4-6h each = biggest time commitment.

| Task | Topic | Effort | Watch Out For |
|------|-------|--------|---------------|
| **C2-01** | RBAC + Zero-Trust + HMAC in educational/assessment platforms | 4-6h | You said C1=YES, C2=YES — strong fit. Synthesize, don't list author-by-author. |
| **C2-03** | AI/RAG + NL database querying in education | 4-6h | You said C5=MAYBE — you'll need to deepen on MixedBread embeddings and RAG internals. Use the system's own architecture as reference. |
| **C2-04** | PWA/Offline-first + service workers + IndexedDB | 4-6h | C4=YES — strong fit. Reference real-world critical-ops deployments. |
| **C2-05** | DPA (RA 10173) + multi-tenant DB architecture | 3-5h | C7=MAYBE — strengthen multi-tenancy knowledge. Check RA 10173 compliance requirements specifically. |

### Priority 4: Integration Work (Jun 7-9)

| Task | What to Do | Effort | Depends On |
|------|-----------|--------|------------|
| **C2-08** | Expand Ch1 IPO framework into 2-3 paragraph narrative for Ch2 | 2-3h | C1-07, C1-08 |
| **CC-03** | Read EVERYTHING end-to-end. Unify voice/tone. Fix transitions. Merge existing + planned features into one coherent story. | 4-6h | All writing tasks complete |

---

## Captaining Notes — What to Watch

### 1. Christine's Scope is Too Light
Christine claimed only **2 tasks (~6-9h)** vs. your 11 tasks (~33-46h). Even with Member 3 picking up a share, the Background paragraphs (C1-01 through C1-06) represent ~18-27h of work that currently has no owner. Christine's self-assessment shows:
- **B3=YES** (PH education context), **B4=YES** (ISPSC/SUC research) — she's a natural fit for **C1-03** (National Context) and **C1-04** (Local Context).
- **A1=YES** (academic paragraphs) — she can write the prose, she just needs structural guidance.

**Action:** When you hold the assignment meeting, consider redistributing C1-03 and C1-04 to Christine, and C1-01, C1-02, C1-05, C1-06 to Member 3.

### 2. Your MAYBE Zones Need a Plan
You're taking 4 lit reviews, two of which hit your MAYBE areas (C2-03 AI/RAG, C2-05 Multi-Tenancy). Plan for these to take longer than estimated. Front-load C2-01 (your strongest fit) to build momentum.

### 3. Christine's C2-02 Needs Clarification
Christine described her C2-02 claim as "Local Literature — Admission at Guidance Systems sa Pilipinas" — this diverges from the original C2-02 spec ("Automated Scoring and OMR Technologies"). Clarify whether she's writing:
- (a) The original C2-02 (OMR/CV) — which she'd need guidance on since her tech knowledge is mostly MAYBE
- (b) A localized version focusing on Philippine admission/guidance systems — which is her strength but changes the deliverable scope

### 4. CC-03 (Narrative Consistency) is Your Most Critical Task
This is the final integration pass. If earlier tasks slip, this gets squeezed. Protect Jun 8-9 for this. Don't let lit reviews consume your buffer.

### 5. Member 3 — What You Need From Them
When Member 3 submits their assessment, evaluate them against the **unassigned pool** (see `TEAM_TASK_CHECKLIST.md`). The ideal Member 3 profile would cover:
- Academic writing strength (A1-A3 = YES) to handle Background paragraphs
- Formatting discipline (D1-D5 = YES/MAYBE) to handle CC-01, CC-02, CC-04
- Research ability (B1-B5 = YES) to handle C2-06

---

## Suggested Daily Schedule

| Date | Focus | Tasks |
|------|-------|-------|
| **Jun 1 (Sun)** | Foundation sprint | C1-09 (Objectives), C1-07 (IPO Diagram) |
| **Jun 2 (Mon)** | Foundation + framework | C1-11 (Scope), C1-08 (Narrative) |
| **Jun 3 (Tue)** | Tech framework | C2-07 (Technical Framework) |
| **Jun 4 (Wed)** | Lit review 1 | C2-01 (RBAC/Zero-Trust) — your strongest fit |
| **Jun 5 (Thu)** | Lit review 2 | C2-05 (DPA/Multi-Tenancy) |
| **Jun 6 (Fri)** | Lit reviews 3-4 | C2-03 (AI/RAG), C2-04 (PWA/Offline) |
| **Jun 7 (Sat)** | ❌ REST DAY | — |
| **Jun 8 (Sun)** | Integration | C2-08 (Framework Prose), begin CC-03 |
| **Jun 9 (Mon)** | Final integration | CC-03 (Narrative Consistency), buffer |

---

## Reference Files

- [TEAM_META_GUIDE_Ch1_Ch2.md](./TEAM_META_GUIDE_Ch1_Ch2.md) — Full task specs
- [SYSTEM_FEATURES.md](../SYSTEM_FEATURES.md) — System features reference
- [Existing_and_Planned_Features.md](../drafts/Existing_and_Planned_Features.md) — Existing vs planned
- [GUIDE-2-CHAPTER1-CONTENT.md](../guides/GUIDE-2-CHAPTER1-CONTENT.md) — Ch1 content rules
- [GUIDE-3-CHAPTER2-CONTENT.md](../guides/GUIDE-3-CHAPTER2-CONTENT.md) — Ch2 content rules
