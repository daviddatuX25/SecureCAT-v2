# Captaining Overview — Strategic Team Assessment
## SecureCAT-v2 Capstone | Chapters 1 & 2 Sprint

> **Purpose:** Give the Team Leader (David) a bird's-eye view for evaluating paths,
> rebalancing work, and making critical decisions as the deadline approaches.
>
> **Deadline:** June 10, 2026 (9 days from today)
> **Last updated:** June 1, 2026

---

## Current State Snapshot

```
┌───────────────────────────────────────────────────────┐
│  24 total tasks  │  75-100h estimated total effort    │
├──────────────────┼────────────────────────────────────┤
│  David:    11 tasks │  33-46h claimed  │  ~55h avail  │
│  Christine: 2 tasks │   6-9h claimed   │  ??h avail   │
│  Member 3:  0 tasks │   0h claimed     │  ??h avail   │
│  Unassigned: 11 tasks │ 35-50h                        │
└───────────────────────────────────────────────────────┘
```

### Imbalance Warning ⚠️

The current distribution is **heavily skewed**. David carries ~60-65% of estimated effort. Christine carries ~8%. This is unsustainable even with David's 55h capacity.

If Member 3 doesn't arrive or has low capacity, the unassigned 35-50h pool creates a hard bottleneck.

---

## Skill Coverage Map

This maps all 24 tasks against available team skills to show coverage gaps.

### Legend: 🟢 = YES | 🟡 = MAYBE | 🔴 = NO | ❓ = Unknown

| Skill Domain | David | Christine | Member 3 | Tasks Needing This |
|-------------|-------|-----------|----------|-------------------|
| Academic paragraph writing | 🟡 | 🟢 | ❓ | C1-01 to C1-06, C1-08, C1-12, C2-08 |
| Synthesis of multiple sources | 🟡 | 🟡 | ❓ | C1-05, C2-01 to C2-05 |
| APA 7th Ed citations | 🟡 | 🟡 | ❓ | C1-02 to C1-04, C2-01 to C2-06, CC-01 |
| Research gap identification | 🟢 | 🟡 | ❓ | C1-05 |
| Lit search (Google Scholar etc.) | 🟢 | 🟢 | ❓ | C1-02 to C1-04, C2-01 to C2-06 |
| PH education context | 🟢 | 🟢 | ❓ | C1-03, C1-04, C2-05 |
| ISPSC Tagudin familiarity | 🟢 | 🟢 | ❓ | C1-04 |
| RBAC / Zero-trust / HMAC | 🟢 | 🟡 | ❓ | C2-01 |
| OMR / Computer vision | 🟡 | 🟡 | ❓ | C2-02 |
| AI / RAG / NL querying | 🟡 | 🟡 | ❓ | C2-03 |
| PWA / Offline-first | 🟢 | 🟡 | ❓ | C2-04 |
| Laravel/Inertia/Svelte stack | 🟢 | 🟡 | ❓ | C2-07 |
| Multi-tenant DB | 🟡 | 🟡 | ❓ | C2-05 |
| System architecture (deep) | 🟢 | 🟡 | ❓ | C1-07, C1-08, C2-07, C2-08 |
| Formatting precision | 🔴 | 🟡 | ❓ | CC-01, CC-02, CC-04 |
| Proofreading / QA | 🔴 | 🟡 | ❓ | CC-02, CC-03 |
| APA reference compilation | 🔴 | 🟡 | ❓ | CC-01 |

### Critical Finding

**Formatting and QA tasks (CC-01, CC-02, CC-04) have no strong owner.** David is 🔴 on all D-skills. Christine is 🟡. Member 3 is unknown. If Member 3 also lacks formatting skills, these tasks become the team's weakest link.

---

## Decision Matrix — Paths for Redistribution

### Scenario A: Member 3 Has Strong Writing + Formatting
*Best case. Even distribution possible.*

| Member | Tasks | Hours |
|--------|-------|-------|
| David | C1-07, C1-08, C1-09, C1-11, C2-01, C2-03, C2-04, C2-05, C2-07, C2-08, CC-03 | 33-46h |
| Christine | C1-03, C1-04, C1-12, C2-02, CC-04 | 15-22h |
| Member 3 | C1-01, C1-02, C1-05, C1-06, C1-10, C2-06, CC-01, CC-02 | 24-34h |

### Scenario B: Member 3 Has Moderate Skills
*Redistribute lighter tasks to Member 3, heavier synthesis stays with David.*

| Member | Tasks | Hours |
|--------|-------|-------|
| David | C1-05, C1-06, C1-07, C1-08, C1-09, C1-11, C2-01, C2-03, C2-04, C2-05, C2-07, C2-08, CC-03 | 42-58h ⚠️ |
| Christine | C1-03, C1-04, C1-12, C2-02, CC-04 | 15-22h |
| Member 3 | C1-01, C1-02, C1-10, C2-06, CC-01, CC-02 | 17-24h |

**Risk:** David exceeds 55h capacity. Need to drop or simplify something.

### Scenario C: Member 3 Doesn't Deliver / Very Low Capacity
*Emergency mode. Two-person sprint.*

| Member | Tasks | Hours |
|--------|-------|-------|
| David | C1-05, C1-06, C1-07, C1-08, C1-09, C1-11, C2-01, C2-03, C2-04, C2-05, C2-07, C2-08, CC-03 | 42-58h ⚠️⚠️ |
| Christine | C1-01, C1-03, C1-04, C1-10, C1-12, C2-02, C2-06, CC-01, CC-02, CC-04 | 30-44h |

**Risk:** Both members maxed out. Quality may drop. Consider asking for deadline extension or scoping down.

---

## Dependency Chain — Critical Path

```
Day 1-2 (Jun 1-2):  No-dep tasks can start in parallel
├── David:     C1-09, C1-07, C1-11
├── Christine: C1-12, (C1-03, C1-04 if assigned)
└── Member 3:  C1-01, C1-02, (C2-06 if assigned)

Day 3-4 (Jun 3-4):  Dependent tasks unlock
├── C1-08 (needs C1-07)
├── C1-10 (needs C1-09)
└── Lit reviews begin (C2-01 to C2-05 — all independent)

Day 5-6 (Jun 5-6):  Synthesis layer
├── C1-05 (needs C1-02 + C1-03 + C1-04) ← CRITICAL BOTTLENECK
├── C2-07, C2-08 can proceed
└── C2-06 (independent)

Day 7 (Jun 7):  Rest / buffer

Day 8-9 (Jun 8-9):  Integration & QA
├── C1-06 (needs C1-02 to C1-05)
├── CC-01 (needs all writing)
├── CC-02 (needs all writing)
├── CC-03 (needs all writing) — David's final pass
└── CC-04 (needs all writing)
```

### Bottleneck: C1-05 (Synthesis)
This task synthesizes findings from C1-02, C1-03, and C1-04. If any of those three are late, C1-05 can't start, which delays C1-06, which delays all CC tasks. **The Background paragraph chain is the critical path.**

---

## Open Decisions

| # | Decision | Options | Deadline |
|---|----------|---------|----------|
| 1 | Christine's C2-02 scope | (a) Original OMR/CV spec, (b) PH admission systems reframe | Jun 2 |
| 2 | Christine's task ID | Confirm C1-12 (Significance), drop C1-10 from her list | Jun 1 |
| 3 | Member 3 assessment | Collect and evaluate | Jun 2 |
| 4 | Christine additional tasks | Propose C1-03, C1-04, C1-01 expansion | After hours confirmed |
| 5 | Scenario selection | Choose A, B, or C based on Member 3 assessment | Jun 2-3 |

---

## File Structure

```
capstone/team_meta/
├── TEAM_META_GUIDE_Ch1_Ch2.md          # Master task inventory + self-assessment form
├── TEAM_TASK_CHECKLIST.md              # Live task tracker (single source of truth)
├── CAPTAINING_OVERVIEW.md             # This file — strategic assessment
├── Session 1 - Meta Guide Responses/
│   ├── david_self_assessment.md
│   ├── christine_self_assessment.md
│   └── member3_self_assessment.md      # ⏳ Pending
└── members/
    ├── david/
    │   └── DIRECTION.md                # David's personalized execution guide
    ├── christine/
    │   └── DIRECTION.md                # Christine's personalized execution guide
    └── member3/
        └── DIRECTION.md                # Placeholder until assessment received
```

---

## Next Steps (Immediate)

1. **Today (Jun 1):** Start your no-dep tasks (C1-09, C1-07, C1-11).
2. **Today (Jun 1):** Message Christine — confirm task IDs and available hours.
3. **Today/Tomorrow (Jun 1-2):** Message Member 3 — send assessment form, set Jun 2 deadline.
4. **Jun 2-3:** Hold virtual assignment meeting once all assessments are in. Select scenario.
5. **Jun 3+:** Everyone executes per their DIRECTION.md guide.
