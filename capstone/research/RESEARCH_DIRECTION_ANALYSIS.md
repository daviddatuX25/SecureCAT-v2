# Research Direction Analysis — SecureCAT-v2 Capstone
## Chapters 1 & 2 Unified Research Narrative Review (Revised)

**Perspective:** Research Specialist — evaluating whether the *collective research direction* produces a defensible, coherent manuscript  
**Date:** June 2, 2026 (Revised — v2)  
**Sources reviewed:** COMPREHENSIVE_TASK_REPORT, GUIDE-2/3, SYSTEM_FEATURES, Existing_and_Planned_Features, Chapter_1_2_Drafting_Plan, pre_proposal_defense, CAPTAINING_OVERVIEW, TASK_DISTRIBUTION_PLAN, multi-tenant strategy context, team corrections on ISPSC insider context

> [!NOTE]
> **Revision Notes (v2):** This revision corrects several assessments from v1 based on team input:
> - Multi-tenancy upgraded from "weak link" to **strong pillar** (data silo prevention for multi-campus ISPSC network)
> - ISPSC insider context concern removed (all team members are at ISPSC Tagudin)
> - New sections added on admission digitization arguments and PWA-vs-native framing
> - Existing baseline features now woven into the research argument structure
> - All six pillars now assessed as defensible with proper framing

---

## 1. Executive Verdict

> [!IMPORTANT]
> **The research direction is ambitious, strategically sound, and now fully defensible across all six pillars.** The "Trojan Horse" strategy is not just clever — it maps to real operational problems. The key to the strongest Chapter 1 and 2 is not picking one "main" contribution, but framing all six pillars as **interconnected solutions to a single compound problem**: *Philippine SUC admission testing is fragmented, manual, insecure, and architecturally siloed — and no existing system addresses this holistically.*

### The Six Research Pillars — Revised Assessment

| # | Domain | Ch1 Tasks | Ch2 Tasks | Verdict |
|---|--------|-----------|-----------|---------|
| 1 | **RBAC + Zero-Trust + HMAC** | C1-01, C1-05, C1-06 | C2-01 | 🟢 **Strong** — well-defined, rich literature |
| 2 | **Automated Scoring / OMR / CV** | C1-05 (partial) | C2-02 | 🟢 **Strong** — frame as "automated assessment" broadly, not just OMR |
| 3 | **AI / RAG in Education** | C1-01, C1-05 | C2-03 | 🟢 **Strong** — trending topic, abundant 2022-2026 literature |
| 4 | **PWA / Offline-First Systems** | C1-01, C1-05 | C2-04 | 🟢 **Strong** — now framed with seasonal-applicant + native-app-overhead argument |
| 5 | **DPA / Multi-Tenancy** | C1-01, C1-05 | C2-05 | 🟢 **Strong** — reframed as data silo prevention for multi-campus ISPSC network |
| 6 | **Admission System Digitization** | C1-02, C1-03, C1-04 | C2-06 | 🟢 **Strong** — abundant comparatives + why-online-at-all argument |

---

## 2. What Works — Core Strengths (Unchanged from v1)

### 2.1 The "Trojan Horse" Narrative Strategy
Mapping advanced features to title keywords remains **excellent academic structure**. Keep it.

### 2.2 The Funnel Structure (C1-01 through C1-06)
Core Problem → Global → National → Local → Synthesis → Clinching is textbook correct.

### 2.3 RBAC + Zero-Trust as the Security Thesis (C2-01)
Zero-trust + HMAC score integrity + immutable audit logs = your **most technically novel** pillar. Rich 2022-2026 literature.

### 2.4 AI/RAG in Education (C2-03)
Post-ChatGPT explosion of literature. Easiest section to source.

### 2.5 The IPO Framework (C1-07, C1-08)
Mechanically clear, low-risk, blocks other tasks. Get it done early.

---

## 3. NEW — The Admission Digitization Argument (Missing from v1)

> [!WARNING]
> **This was entirely absent from the original analysis.** The manuscript currently has no planned space for the foundational question: *Why should admission processes be online at all?* This argument belongs in **C1-01 (Core Problem)** and **C1-02 (Global Context)** and forms the bedrock that every other pillar stands on.

### 3.1 The "Why Online?" Argument

This is the first-principles argument the panel expects. Before you talk about zero-trust, OMR, or AI, you need to establish:

**Why most admission processes should happen online:**
- **Reduction of physical queue bottlenecks** — walk-in application processing creates seasonal surges that overwhelm Guidance/Registrar staff
- **Data entry error elimination** — applicant-submitted data vs. staff-transcribed data has measurably different error rates
- **Status visibility** — applicants currently have no way to check where their application stands without physically visiting the office
- **Document permanence** — paper applications get lost, misfiled, or damaged; digital records persist
- **Time-to-decision compression** — online pipelines allow asynchronous review, removing the constraint of office hours

**What can and cannot be digitized (this is a nuanced argument the panel will appreciate):**

| Process | Can Be Digitized? | SecureCAT Approach |
|---------|-------------------|-------------------|
| Application form submission | ✅ Yes — fully digital intake | Web-based applicant portal with form validation |
| Requirements document submission | ⚠️ Partially — some originals must be physically verified | Digital upload for pre-screening; physical verification at exam day |
| Exam scheduling | ✅ Yes — algorithmic scheduling beats manual | AI-assisted scheduling assistant with constraint optimization |
| Exam proctoring & attendance | ⚠️ Partially — physical presence required | QR-based digital attendance tracking (with offline PWA fallback) |
| Score recording | ✅ Yes — CSV import or CV-based OMR | Multiple ingestion paths: direct entry, CSV import, image-based OMR |
| Consultation & advising | ⚠️ Partially — face-to-face counseling remains valuable | Digital consultation summaries + AI companion for FAQ-level queries |
| Result release | ✅ Yes — fully digital | Portal-based result release with PDF/DOCX generation |

**Why this matters for the manuscript:** This table doesn't go into the paper directly, but the *reasoning* behind it should permeate C1-01 and C1-02. It shows the panel that you've thought about where digitization is appropriate and where it isn't — which is exactly the kind of nuanced thinking that impresses.

### 3.2 Where This Argument Lives in the Task Map

| Task | What to add |
|------|-------------|
| **C1-01** (Core Problem) | Open with *why* admission workflows need digital transformation — not just "it's manual and inefficient" but *what specific harm* the manual process causes (seasonal overload, data loss, applicant uncertainty) |
| **C1-02** (Global Context) | Include global trends in online admission: how universities worldwide shifted to digital intake, what efficiency gains were measured, what barriers remain |
| **C1-03** (National Context) | CMO directives on HEI digitization, CHED's push for LMS/enrollment systems, the gap between policy mandate and SUC implementation |
| **C1-05** (Synthesis) | "While global evidence confirms that online admission systems reduce processing errors and improve applicant experience, Philippine SUCs — particularly those serving regional populations — lack integrated platforms that address the full admission lifecycle." |

---

## 4. NEW — PWA vs. Native Mobile: The Seasonal Applicant Argument

> [!IMPORTANT]
> **This is a strong, underappreciated argument that should appear in both C1 and C2-04.** The v1 analysis noted PWA literature was thin but missed the *killer framing* for why PWA is the right choice — not just technically, but from a user experience and institutional practicality standpoint.

### 4.1 The Core Argument

**Applicants are seasonal, one-time users.** A college admission applicant interacts with the system for a brief window — typically one to two admission cycles. Many are transferees who apply once. Requiring them to download and install a native mobile application for a one-time interaction creates:

1. **Installation overhead** — 50-100MB app download for a process that takes 15-30 minutes
2. **Storage pressure** — low-end Android devices (common among SUC applicants from rural Ilocos Sur) have limited storage
3. **Update maintenance** — native apps require ongoing updates; a seasonal system doesn't justify this
4. **Platform fragmentation** — building and maintaining separate iOS/Android versions for a seasonal tool is resource-wasteful
5. **Discovery friction** — applicants must find the app in the store, verify it's the correct one, and trust it

**A PWA eliminates all five problems:** instant access via URL, no installation, no storage overhead, automatic updates via service worker, single codebase. For a *seasonal admission system serving a regional SUC*, this is not a compromise — it's the *correct* architectural choice.

### 4.2 The Offline Resilience Add-On

On top of the PWA-vs-native argument, the **offline capability** becomes a natural extension:
- ISPSC Tagudin's campus Wi-Fi is not enterprise-grade
- On exam day, dozens of applicants + proctors hitting the network simultaneously causes congestion
- Proctors scanning QR codes at exam room doors *must not fail* due to a connectivity blip
- Service Worker + IndexedDB caching + background sync = resilience without native app overhead

### 4.3 Where This Lives in the Tasks

| Task | What to add |
|------|-------------|
| **C1-01** (Core Problem) | Mention that applicants are seasonal users — building a native app for a one-time interaction is disproportionate |
| **C1-04** (Local Context) | Reference ISPSC Tagudin's connectivity constraints and the applicant demographic (rural Ilocos Sur, likely budget Android devices) |
| **C2-04** (PWA/Offline Lit Review) | Lead with the seasonal-user argument, then support with offline resilience. Search terms: "progressive web application seasonal services" "lightweight mobile access education" "offline-capable web systems developing regions" |
| **C1-11** (Scope & Delimitations) | State explicitly: "The system is delivered as a PWA rather than a native mobile application because the seasonal nature of admission workflows makes native app installation an unnecessary overhead for applicants." |

### 4.4 Revised Citation Strategy for C2-04

The seasonal-user framing **broadens the literature pool** significantly:

| Old search (thin results) | New search (broader results) |
|--------------------------|------------------------------|
| "progressive web app education" | "lightweight mobile access higher education developing countries" |
| "PWA offline capability" | "offline-capable web applications institutional services" |
| "service worker architecture" | "connectivity-resilient systems rural education infrastructure" |
| — | "native app vs web app institutional systems cost analysis" |
| — | "progressive web applications seasonal services user adoption" |

---

## 5. NEW — Existing Features as Research Arguments (Missing from v1)

> [!WARNING]
> **The v1 analysis focused almost entirely on the "advanced" Trojan Horse features and neglected the already-built baseline system.** This is a mistake — the existing features solve real operational pain points and should be explicitly woven into the research narrative. They demonstrate that the system is not just a proposal; it's a working solution.

### 5.1 Feature-to-Research-Argument Map

| Existing Feature | Operational Problem It Solves | Research Argument | Where It Goes |
|-----------------|-------------------------------|-------------------|---------------|
| **Proctor role & session roster** | Staff task overload — Guidance counselors currently do everything (scheduling, proctoring, scoring, advising). A dedicated proctor role allows *task delegation* and *temporary staffing*. | "The system introduces role-specific task boundaries that enable institutions to distribute admission workload across temporary proctors, reducing the cognitive burden on permanent Guidance staff." | C1-01, C1-12 (Significance) |
| **Real-time status tracker** (6-stage lifecycle) | Applicant anxiety and information asymmetry — applicants currently have no visibility into their application status without visiting the office. | "Applicants gain persistent visibility into their admission lifecycle, eliminating the need for physical follow-up visits that consume both applicant time and office staff capacity." | C1-01, C1-02 |
| **AI companion (RAG)** | FAQ overload on Guidance staff — applicants repeatedly ask the same questions about requirements, schedules, courses. | "A retrieval-augmented conversational assistant handles routine inquiries, deflecting repetitive questions from overburdened Guidance staff to an always-available digital interface." | C1-01, C2-03 |
| **Bulk operations** (bulk accept, bulk import, bulk release) | Seasonal volume spikes — during admission periods, processing hundreds of applications individually is impractical. | "Batch processing capabilities allow staff to manage seasonal applicant surges without proportional staffing increases." | C1-04 (local context), C1-12 |
| **Document generation** (PDF admission slips, result sheets, DOCX export) | Manual document preparation — staff currently prepare these documents by hand or through generic templates. | "Automated document rendering eliminates manual template filling, ensuring consistency and reducing preparation time from hours to seconds." | C1-01, C2-07 |
| **Scheduling assistant** (AI-assisted + constraint optimization) | Manual exam scheduling — matching rooms, time slots, proctor availability, and applicant counts is currently done with spreadsheets or whiteboards. | "An algorithmic scheduling assistant automates constraint-based exam session planning, a task that currently consumes significant Registrar staff time during peak periods." | C1-01, C2-06 |
| **Consultation summaries** | Counselor notes are currently paper-based — not searchable, not persistent, not shareable across sessions. | "Digital consultation records create a persistent, searchable history that supports longitudinal applicant tracking and counselor continuity." | C1-04, C1-12 |
| **Score import (CSV + template)** | Manual score transcription from paper answer sheets to spreadsheets to database is error-prone and time-consuming. | "Structured score import with validation reduces transcription errors and provides a verifiable data pipeline from test administration to result release." | C2-02 (as baseline before CV-OMR) |

### 5.2 The Scalability Narrative

Your point about the **proctor role enabling institutional scaling** is a strong argument that should be explicit in the manuscript:

> *"As ISPSC Tagudin's applicant volume grows — whether through increased enrollment demand, new program offerings, or institutional consolidation — the system's role-based architecture allows the campus to scale its admission operations by assigning temporary proctors without expanding permanent staffing. This design treats personnel flexibility as a first-class system concern."*

This argument connects to:
- **C1-01** — The problem isn't just "manual processes" but "non-scalable processes"
- **C1-12** — Significance for the Client Institution (ISPSC administration)
- **C2-01** — RBAC literature (roles enable operational flexibility, not just security)

### 5.3 How to Weave Existing Features into Chapter Tasks

**The key principle:** Don't separate "existing features" from "research features" in the narrative. Instead, present the *complete system* as one solution where baseline features address *operational* problems and advanced features address *security*, *automation*, and *scalability* problems.

| Chapter Section | Existing Features to Reference |
|----------------|-------------------------------|
| **C1-01** (Core Problem) | Status tracker (information gap), proctor role (task overload), document generation (manual prep), scheduling assistant (planning burden) |
| **C1-02** (Global) | Cite how global admission systems include these features as standard — establish that ISPSC's lack of them is a measurable gap |
| **C1-04** (Local) | Bulk operations (seasonal surges), consultation summaries (paper-based records), CSV score import (current workaround) |
| **C1-05** (Synthesis) | "While individual solutions exist for scheduling, scoring, and applicant tracking, no existing Philippine SUC system integrates these into a unified, role-secured platform." |
| **C1-12** (Significance) | Proctor role → staff scalability (Client Institution), status tracker → applicant experience (Community), AI companion → reduced FAQ load (Respondents) |
| **C2-06** (Related Systems) | Compare existing features against each comparable system — "System X has scheduling but no automated scoring; System Y has scoring but no role-based access; none have AI-assisted applicant guidance." |

---

## 6. Multi-Tenancy — Revised Assessment (Upgraded from "Weak" to "Strong")

> [!NOTE]
> **The v1 analysis was wrong about multi-tenancy.** It treated multi-tenancy as a generic architectural decision. In the context of ISPSC — a multi-campus state university system — it's a **strategic engineering decision that prevents institutional data silos**.

### 6.1 The Actual Argument

ISPSC is not a single campus. It is a **multi-campus institution** with locations across Ilocos Sur. If SecureCAT is built as a single-tenant system for Tagudin:
- Tagudin's data is isolated from the rest of the ISPSC network
- Future expansion to other campuses requires rebuilding the database
- Cross-campus analytics (capacity balancing, applicant redistribution) become impossible
- Each campus would need its own system instance — violating DRY and creating maintenance nightmares

By architecting with multi-tenancy from Day 1:
- Tagudin serves as the pilot tenant
- The same codebase serves future campuses with data isolation via `tenant_id` partitioning
- Cross-campus features (applicant overflow routing, consolidated analytics) become possible *without database migration*
- RA 10173 (DPA) compliance is enforced at the architecture level through tenant-scoped data access

### 6.2 Research Literature Reframe

**Don't search for "multi-tenancy."** Search for:

| Old search (sparse) | New search (rich) |
|---------------------|-------------------|
| "multi-tenant SaaS education" | "institutional data silos higher education systems" |
| "multi-tenancy database architecture" | "scalable information systems multi-campus universities" |
| — | "data governance multi-site educational institutions" |
| — | "shared infrastructure state university systems Philippines" |
| — | "centralized vs decentralized student information systems" |
| — | "data privacy compliance multi-campus deployments RA 10173" |

**The framing:** C2-05 should be titled **"Scalable Data Architecture and Institutional Data Governance"** — not "DPA/Multi-Tenancy." This broader framing captures:
1. The data silo problem in multi-campus institutions (literature exists)
2. The scalability argument for shared infrastructure (literature exists)
3. DPA/RA 10173 compliance as the regulatory constraint (Philippine legal framework)
4. Multi-tenancy as the *engineering solution* to all three (technical implementation)

### 6.3 Key Statements for the Manuscript

**For C1-01 (Core Problem):**
> "ISPSC operates as a multi-campus institution where applicant distribution and faculty capacities fluctuate across different locations. Building a standalone, single-tenant system for one campus inadvertently creates a data silo that fragments institutional visibility."

**For C1-11 (Scope & Delimitations):**
> "While the system utilizes a multi-tenant backend designed to support the entire ISPSC network, the onboarding of other campuses is beyond the scope of this study. Advanced network-wide features — such as multi-campus data visualizations and cross-campus applicant remapping — are not included in the current development phase. The multi-tenant architecture is implemented solely as a structural foundation."

**For C2-05 (Literature Review):**
> Frame around: "How do multi-campus HEIs avoid data silos while maintaining per-campus data privacy?" → Multi-tenancy is the answer, supported by DPA compliance.

---

## 7. Revised Research Domain Priority Matrix

| Priority | Domain | Why | Tasks |
|----------|--------|-----|-------|
| **P0 — Critical** | **Core Problem + Background Funnel** | The backbone of Chapter 1. Every pillar feeds into this. | C1-01 through C1-06 |
| **P0 — Critical** | **Admission Digitization ("Why Online?")** | The foundational argument. Without it, the system has no premise. | C1-01, C1-02, C1-03 |
| **P0 — Critical** | **RBAC + Zero-Trust** | Most technically novel contribution. Concrete, measurable, defensible. | C2-01, C1-05, C1-06 |
| **P1 — High** | **Existing Features as Research Arguments** | Proctor role, status tracker, scheduling, bulk ops — these solve real problems and must be in the narrative. | C1-01, C1-04, C1-12, C2-06 |
| **P1 — High** | **AI/RAG in Education** | Strong literature, trending, easy to defend. | C2-03 |
| **P1 — High** | **Multi-Tenancy as Data Silo Prevention** | Now correctly framed as ISPSC multi-campus scalability — not a generic SaaS pattern. | C2-05 (reframed), C1-01 |
| **P1 — High** | **Admission Testing Systems (Related Systems)** | Gap analysis destination. Everything converges here. | C2-06 |
| **P2 — Medium** | **Automated Scoring / OMR** | Important but search terms need broadening. | C2-02 |
| **P2 — Medium** | **PWA as Seasonal-User Architecture** | Now strengthened with the native-app-overhead argument. | C2-04 |

---

## 8. Revised Chapter 1 — Narrative Spine

### The Central Thesis (Revised from v1)

**v1 said:** "Pick one main contribution (Zero-Trust) and treat the rest as supporting."  
**v2 says:** No — the contribution IS the integration. The thesis is:

> *"No existing system for Philippine SUC admission testing addresses the full operational lifecycle — from digital intake through secure scoring to intelligent applicant guidance — within a scalable, privacy-compliant architecture that prevents institutional data silos."*

Each pillar supports a different part of "the full operational lifecycle":
- **Admission digitization** → the intake layer
- **RBAC + Zero-Trust** → the security layer  
- **AI/RAG** → the guidance layer
- **OMR/CV** → the scoring layer
- **PWA/Offline** → the resilience layer
- **Multi-Tenancy** → the scalability layer

**The gap is not that one feature is missing — the gap is that no one has *integrated all the layers* for this context.**

### Paragraph-by-Paragraph Spine

```
C1-01 (Core Problem — No Citations):
  "College admission testing at Philippine state university campuses is 
  operationally fragmented. Guidance and Registrar offices manage applicant 
  intake, exam scheduling, score recording, and result release through 
  disconnected manual processes. Staff perform overlapping duties without 
  clear role boundaries, applicants lack visibility into their application 
  status, and test scores pass through multiple human transcription points 
  vulnerable to error. For multi-campus institutions, this fragmentation 
  compounds — each campus operates as a data silo, unable to share capacity 
  or analytics across the network. Meanwhile, requiring applicants — who 
  interact with the system only once or twice in their academic lifetime — 
  to install native mobile applications represents an overhead 
  disproportionate to their interaction frequency."

C1-02 (Global Context — 5+ citations):
  Global trends in online admission → efficiency gains documented 
  internationally → secure assessment platforms → AI-assisted student 
  guidance → lightweight web-based access patterns replacing native apps 
  for seasonal institutional services.

C1-03 (National Context — 5+ citations):
  CHED digitization mandates → RA 10173 DPA requirements → SUC enrollment 
  system adoption gaps → CMO directives on HEI information systems → 
  the gap between national policy and institutional implementation.

C1-04 (Local Context — 5+ citations):
  ISPSC Tagudin's specific workflows → manual processes in Guidance and 
  Registrar → seasonal volume surges → staff task overload (counselors 
  doing proctoring, scoring, and advising simultaneously) → 
  infrastructure constraints (campus Wi-Fi, device availability) → 
  ISPSC as multi-campus system where Tagudin data is currently siloed.

C1-05 (Synthesis & Gap):
  "While individual digital solutions exist for scheduling, scoring, and 
  applicant tracking, no existing Philippine SUC system integrates secure 
  role-based scoring verification, AI-assisted applicant guidance, 
  automated assessment ingestion, and offline-resilient operations within 
  a multi-tenant architecture designed for institutional scalability. This 
  integrated gap leaves campuses like ISPSC Tagudin dependent on manual 
  processes that cannot scale, cannot ensure data integrity, and cannot 
  share insights across the wider institutional network."

C1-06 (Clinching Statement):
  Three components: (1) how the literature structured the study, (2) direct 
  observation at ISPSC Tagudin confirming the gap, (3) SecureCAT as the 
  critical integrated solution. Optional SDG 4 (Quality Education) tie-in.
```

---

## 9. Revised Chapter 2 — Literature Architecture

### Section Structure (6 sections — all retained, C2-05 reframed)

| Section | Revised Title | Core Argument |
|---------|--------------|---------------|
| **C2-01** | RBAC, Zero-Trust Architecture, and Data Integrity in Assessment Systems | Security is not just login permissions — it's cryptographic score verification and immutable audit trails |
| **C2-02** | Automated Scoring and Computer-Aided Assessment Technologies | The evolution from manual transcription → CSV import → OMR → CV-based scoring |
| **C2-03** | AI-Assisted Guidance and RAG in Higher Education | How conversational AI deflects FAQ overload and provides personalized guidance |
| **C2-04** | Lightweight Web Access and Offline Resilience for Institutional Services | PWA as the correct architecture for seasonal users + connectivity constraints (NOT "PWA is cool") |
| **C2-05** | Scalable Data Architecture and Institutional Data Governance | How multi-campus HEIs avoid data silos; RA 10173 compliance; multi-tenancy as the engineering answer |
| **C2-06** | Review of Related Admission Systems | Comparative analysis showing no existing system integrates all layers |

### C2-06 Gap Analysis Table (for Jaypee)

The comparison table should evaluate each comparable system against SecureCAT's layers:

| Feature | System A | System B | System C | System D | **SecureCAT** |
|---------|----------|----------|----------|----------|---------------|
| Online application intake | ? | ? | ? | ? | ✅ |
| Real-time status tracking | ? | ? | ? | ? | ✅ |
| Role-based access control | ? | ? | ? | ? | ✅ |
| Cryptographic score integrity | ? | ? | ? | ? | ✅ |
| Immutable audit logs | ? | ? | ? | ? | ✅ |
| Automated scoring (OMR/CV) | ? | ? | ? | ? | ✅ |
| AI applicant guidance (RAG) | ? | ? | ? | ? | ✅ |
| Offline-capable proctoring | ? | ? | ? | ? | ✅ |
| Multi-tenant architecture | ? | ? | ? | ? | ✅ |
| Document generation (PDF/DOCX) | ? | ? | ? | ? | ✅ |

**The gap should be visually obvious**: no single system checks all boxes. SecureCAT does.

---

## 10. Tasks That Make or Break the Manuscript

### ⭐ C1-01: Core Problem (David, Jun 4)
Now more important than ever. This paragraph must establish:
1. Operational fragmentation (disconnected manual processes)
2. Staff task overload (roles not separated)
3. Applicant information asymmetry (no status visibility)
4. Data silo risk (multi-campus isolation)
5. Native app overhead for seasonal users
6. Scoring vulnerability (human transcription errors)

### ⭐ C1-05: Synthesis & Gap (David, Jun 6)
**Still the single most important paragraph.** But the gap statement is now richer:

> "No existing system integrates [security] + [AI guidance] + [automated scoring] + [offline resilience] + [scalable multi-tenant architecture] for Philippine SUC admission testing."

This is stronger than the v1 formulation because it identifies an *integration gap*, not a single-feature gap.

### ⭐ C2-01: RBAC + Zero-Trust (David, Jun 5)
Remains the flagship literature review. Should now also briefly touch on how RBAC enables *operational flexibility* (proctor role delegation) in addition to security.

### ⭐ C2-06: Related Systems (Jaypee, Jun 6)
The gap analysis table is where the entire argument converges visually. This must be thorough.

### ⭐ C2-05: Scalable Data Architecture (David, Jun 5)
Upgraded from "deprioritize" to "high." The data silo argument is strong and unique. With the reframed search terms (institutional data silos, multi-campus systems, centralized student information systems), citations will be findable.

---

## 11. Revised Citation Feasibility

| Domain | Availability | Revised Search Strategy |
|--------|-------------|------------------------|
| **Zero-Trust / RBAC** | 🟢 Abundant | "zero trust architecture education" "RBAC assessment systems" "data integrity verification education" |
| **AI / RAG in Education** | 🟢 Abundant | "retrieval augmented generation education" "AI student guidance" "chatbot higher education admission" |
| **Admission Digitization** | 🟢 Abundant | "online admission systems higher education" "digital transformation university enrollment" "paperless admission developing countries" |
| **Automated Scoring / OMR** | 🟢 Available (broadened) | "automated scoring systems education 2023" "computer-aided assessment" "image-based grading" "mobile assessment tools developing countries" |
| **PWA / Offline + Seasonal Users** | 🟢 Available (reframed) | "progressive web application seasonal services" "lightweight mobile access education" "offline-capable web systems rural infrastructure" "native app vs web app institutional services" |
| **Multi-Campus Data Governance** | 🟢 Available (reframed) | "institutional data silos higher education" "multi-campus student information systems" "data governance multi-site universities" "RA 10173 education compliance" |
| **Philippine HEI Context** | 🟢 Available | CHED publications, Philippine Journal of Education, ERIC, "state university college Philippines enrollment systems" |
| **Comparable Systems** | 🟢 Available | Google Scholar + product research, "college admission system architecture" "enrollment management software" |

---

## 12. Final Recommendations — Action Items

### Immediate (Today, June 2)

1. **Reframe C2-05 title** from "DPA/Multi-Tenancy" to **"Scalable Data Architecture and Institutional Data Governance"** — this captures the data silo prevention argument, RA 10173, and multi-tenancy as the solution.

2. **Add admission digitization arguments to C1-01 and C1-02** — the "why online at all?" and "what can/cannot be digitized" reasoning must be present in the first two background paragraphs.

3. **Add PWA-vs-native argument to C2-04 scope** — lead with "seasonal applicants don't need native apps" before discussing offline resilience.

4. **Create a research argument bank** (separate file) — a structured reference of arguments organized by chapter section, so every team member knows what points to hit.

5. **Create a search term cheat sheet** (separate file) — specific phrases for each literature review section, optimized for the revised framings.

### Before Writing Starts (June 2-3)

6. **Align on the revised central thesis:** "The gap is not one missing feature — it's that no Philippine SUC admission system integrates all the necessary layers (security + guidance + scoring + resilience + scalability) into one platform."

7. **Weave existing features into every section** — don't treat them as separate from the research features. The proctor role, status tracker, scheduling assistant, bulk ops, and document generation are all research arguments.

8. **David should write C1-01 first** (it's the foundation) → then C2-01 (anchor lit review) → then C2-05 (now strengthened) → then C2-03 (easiest) → then C2-04 (PWA, now better framed).

---

## Summary (Revised)

| Aspect | v1 Assessment | v2 Assessment |
|--------|--------------|---------------|
| **Overall direction** | 🟢 Solid | 🟢 **Strong** — all pillars now defensible |
| **Narrative coherence** | 🟢 Good | 🟢 **Better** — admission digitization argument added |
| **Central thesis clarity** | 🟡 Needed sharpening | 🟢 **Clear** — integration gap, not single-feature gap |
| **Literature feasibility** | 🟡 4/6 strong, 2 weak | 🟢 **All 6 strong** with revised search framings |
| **Existing features in narrative** | 🔴 Missing | 🟢 **Now mapped** to research arguments |
| **Multi-tenancy assessment** | 🔴 Wrong (called it weak) | 🟢 **Corrected** — data silo prevention is strong |
| **PWA argument** | 🟡 Thin | 🟢 **Strengthened** — seasonal user + native overhead |
| **Task distribution** | 🟢 Good | 🟢 Good (unchanged) |
| **Risk areas** | C2-05, C2-02, C1-04 | C2-02 (manageable with broader search), C1-04 (structured handoff needed) |

**The manuscript's strength is its integration thesis.** Each pillar is one layer of a complete solution. The gap is that no one has integrated them for this context. That's the story Chapters 1 and 2 need to tell.
