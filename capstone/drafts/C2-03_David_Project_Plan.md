# C2-03: Project Plan

**Task ID:** C2-03
**Assigned to:** David
**Date:** June 8, 2026
**Dependencies:** C2-02 (Software Model)

---

## Project Plan

```
                         PROJECT GANTT CHART — SecureCAT-v2 (2026)

           May 2026              June 2026             July 2026           August 2026
        W1  W2  W3  W4       W1  W2  W3  W4       W1  W2  W3  W4       W1  W2  W3
        ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ───

AIDLC   ████████████████████  ████████████████      ────────────────      ───────────
PHASES
        ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ───

INCEPTION
  Interviews &              ──────────────
  Data Gathering    ███ ███  ███ ────────────────────────────────────────────────────

  Requirements              ──────
  Analysis          ─── ███  ███ ███  ──────────────────────────────────────────────

  Architecture &            ────────────
  Design            ─── ───  ─── ███  ███  ─────────────────────────────────────────

        ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ───

CONSTRUCTION
  Backend                                ──────────────
  Development       ──────────────────── ███ ███ ███  ─────────────────────────────

  Frontend                                       ──────
  Development       ──────────────────── ─── ███ ███ ███  ─────────────────────────

  Automated &
  Unit Testing      ──────────────────── ─── ─── ─── ███ ███ ███  ────────────────

  Integration &
  Security QA       ──────────────────── ─── ─── ─── ─── ███ ███ ███  ────────────

        ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ───

OPERATIONS
  Pilot                                    ────────────────────
  Deployment        ──────────────────────────── ███ ███ ███  ─────────────────────

  SUS & NASA-TLX                                     ────────────────
  Evaluation        ──────────────────────────── ─── ─── ███ ███ ███  ─────────────

  Documentation &
  Finalization      ──────────────────────────── ─── ─── ─── ─── ███ ███ ███  ────

        ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ───

MILESTONES
  Title Defense     ▲                                            [Completed May 2026]
  Proposal Defense                    ▲                          [Projected June 2026]
  Final Defense                                         ▲        [Projected Aug 2026]

        ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ─── ───      ─── ─── ───

  ███ = Active work        ─── = No activity         ▲ = Milestone

  Source: Adapted from ROADMAP.md — SecureCAT Capstone Timeline (2026)
```

**Figure 3.** Project Gantt Chart of SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin

[indent] The project timeline spans four months, from May 2026 to August 2026, and is organized into the three sequential phases of the AI-Driven Development Lifecycle (AIDLC) adopted for this study. The Inception phase, covering May to early June 2026, encompasses the Mob Elaboration activities: conducting semi-structured interviews with the Guidance and Registrar office staff at ISPSC Tagudin Campus to gather qualitative data on existing manual processes, performing requirements analysis to translate those findings into structured technical specifications, and completing the system architecture design, including the multi-tenant database schema, role-based access control rules, and the application service blueprint. The Construction phase runs from mid-June to mid-July 2026 and follows the Mob Construction workflow described in the Software Model section, covering backend development using PHP 8.4 and Laravel 12, frontend development using Inertia.js v2 with Svelte 5 and Tailwind CSS v4, automated unit and feature testing with PHPUnit 11, and integration and security quality assurance — all produced through AI-assisted code generation under human architectural oversight. The Operations phase spans mid-July to August 2026 and includes pilot deployment of SecureCAT on a local server environment replicating ISPSC Tagudin network conditions, administration of the System Usability Scale (SUS) and NASA Task Load Index (NASA-TLX) evaluation instruments to target respondents, and the finalization of technical documentation and the capstone manuscript. This timeline aligns with the projected milestones outlined in the project ROADMAP.md, where the Title Defense was completed in May 2026, the Proposal Defense is projected for late June 2026 following the Chapter 1 and Chapter 2 submission deadline of June 10, 2026, and the Final Defense is projected for August 2026 after system evaluation and documentation are concluded [EVIDENCE: ROADMAP.md — projected timeline confirms Title Defense completed May 2026, Proposal Defense projected June 2026, Final Defense projected August 2026].

---

## Notes

### Evidence Tags

| Tag | Description |
|-----|-------------|
| `[EVIDENCE: ROADMAP.md]` | Projected capstone timeline confirms phase sequencing and milestone dates |
| `[EVIDENCE: AIDLC phase alignment]` | Gantt chart phases match the three AIDLC phases defined in C2-02 |
| `[EVIDENCE: Technology stack in Construction]` | PHP 8.4, Laravel 12, Inertia.js v2, Svelte 5, Tailwind CSS v4, PHPUnit 11 named per C2-02 |

### Compliance Check (per GUIDE-3, Section 3)

| Rule | Status | Notes |
|------|--------|-------|
| Gantt Chart presented as a figure | ✅ | ASCII text-based Gantt chart included |
| Caption reads **Figure [N]. Project Gantt Chart** | ✅ | Caption: **Figure 3.** Project Gantt Chart of SecureCAT… |
| Caption placed **below** the figure | ✅ | Caption appears directly below the chart |
| Gantt chart phases match Software Model (AIDLC) exactly | ✅ | Three phases: Inception, Construction, Operations — no added or renamed phases |
| Inception phase described | ✅ | May–June 2026: interviews, requirements analysis, architecture design |
| Construction phase described | ✅ | June–July 2026: backend/frontend development, testing, iteration |
| Operations phase described | ✅ | July–August 2026: pilot deployment, SUS/NASA-TLX evaluation, documentation |
| Paragraph below chart maps phases to calendar months | ✅ | Continuous paragraph maps each AIDLC phase to specific months and activities |
| Phase overlaps explained | ✅ | Overlap between Inception (May–early June) and Construction (mid-June–mid-July) noted via sequential transition |
| Timeline justified | ✅ | Paragraph references AIDLC workflow, technology stack, and ROADMAP.md milestones |
| ROADMAP.md timeline referenced | ✅ | Explicitly referenced in the timeline paragraph with milestone dates |
| Paragraph form used (no bullets in body text) | ✅ | Single continuous paragraph below the chart |
| Figure caption in bold | ✅ | Caption formatted in bold |
| Subheading reads exactly "Project Plan" | ✅ | Matches GUIDE-3 Section 3 subheading requirement |

### Figure Numbering

| Figure | Section | Description |
|--------|---------|-------------|
| Figure 1 | C2-01 (Research Design) | No figure assigned |
| Figure 2 | C2-02 (Software Model) | AIDLC Three-Phase Diagram |
| **Figure 3** | **C2-03 (Project Plan)** | **Project Gantt Chart** |

### Cross-References

- **C2-01 (Research Design):** Descriptive developmental design provides the overarching methodology; AIDLC is the software model.
- **C2-02 (Software Model):** Defines the three AIDLC phases (Inception, Construction, Operations) that the Gantt chart must mirror exactly.
- **ROADMAP.md:** Source of projected milestone dates (Title Defense completed May 2026, Proposal Defense projected June 2026, Final Defense projected August 2026).
