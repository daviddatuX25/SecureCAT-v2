# Context Update — June 4, 2026 (Rev 3 — Final)
## SecureCAT-v2 Capstone — Critical Clarifications

> **Purpose:** Capture the complete, truthful development history and its implications for the capstone manuscript.
> **Rev 3 note:** Adds deployment specifics — what was suggested vs. what's verified, super admin handoff,
> and the need for formal data gathering from the school.
>
> **Companion files:**
> - `DEVELOPMENT_CHRONOLOGY.md` — Timeline reference (this directory)
> - Redirection Audit — artifact file (see conversation artifacts)

---

## Clarification 1: Super Administrator Role

### What Was Said
> *"We also do have the super administrator, which decisively could do all functions — but distinctively,
> it has the ability to create users and assign their roles."*

### Settled Facts

| Attribute | Detail |
|-----------|--------|
| **Role name** | Super Administrator |
| **Privilege level** | Maximum — can execute all functions accessible to any other role |
| **Distinctive capability** | **Exclusively**: user account creation + role assignment (no other role can do this) |
| **Current deployment status** | The Super Admin account was left with the Guidance Office for them to fully explore the system |
| **Separation of concerns** | Precise boundary between Super Admin and other admin roles is still evolving |

### Open Questions (Deferred)
- [ ] Research: Is "super admin does all operational functions" best practice, or should it be
      scoped strictly to identity/role management? (NIST, RBAC literature)
- [ ] Verify: Exact policy-level separation between Super Admin and Registrar Admin in the codebase
- [ ] Consider: Should a **Campus Administrator** role exist for multi-campus ISPSC deployments?
      This role would manage campus-level configuration without having system-wide super admin privileges.
- [ ] Consider: Are additional roles needed now that deployment reality is clearer? The existing
      6-role model may need refinement based on how the Guidance Office actually uses the system.

---

## Clarification 2: The Complete Development History

### The Truth — Full Timeline

```
2nd Year (OJT Period)
  └─ David conceived plans to build an admission testing system for ISPSC Tagudin
     → Attempted to build it during OJT
     → Unsuccessful
     → Establishes: the problem had been on the researcher's radar for over a year

3rd Year, 2nd Semester (Just Before Capstone Timeline)
  └─ David successfully built the foundational digital system
     → Done through institutional consultation with the Guidance Office
     → Informal — no research framework, no measurement instruments, no academic documentation
     → The Guidance Office's original practical goal: get the result sheets printed
     → David delivered that AND built beyond it — proactively including features like
        application intake, scheduling, proctor management, scoring, AI companion, etc.
     → The system was deployed and is currently accessible by the Guidance Office

Capstone Timeline (3rd Year Midyear → 4th Year 1st Semester)
  └─ David + capstone team formally research, document, validate, and upgrade
     → AIDLC methodology, formal research design, SUS + NASA-TLX evaluation
     → The capstone ABSORBS the full arc
     → Dual function: confirmatory validation + developmental advancement
```

See `DEVELOPMENT_CHRONOLOGY.md` for the detailed, canonical timeline.

---

## Clarification 3: Deployment Status — What's Suggested vs. What's Verified

### What David Suggested to the Guidance Office
David suggested the following uses to the Guidance Office staff:

1. **Result sheet generation / report generation** — printing result sheets (this was the original practical goal)
2. **New applications** — accepting new applicants into the system digitally
3. **Direct assessment** — using the direct assessment workflow (skipping scheduling) for walk-in grading cases

### What's NOT Verified
David has **not formally verified** whether the Guidance Office has actually used all of these features.
The suggestions were made, and the Super Admin account was left with them for exploration, but:

- There is no confirmed record of how many applicants were processed digitally
- There is no confirmed record of whether they used direct assessment
- There is no confirmed record of which features they explored beyond result sheet printing
- The Guidance Office may have used features beyond what was suggested, since they have full Super Admin access

### Why This Matters for the Research

> [!IMPORTANT]
> **This is Objective 1 material.** The capstone's descriptive phase MUST formally gather this data
> from the school before the proposal defense. What the Guidance Office actually used, how they used it,
> and what their experience was — this is PRIMARY DATA that feeds into:
> - C1-04 (Local Context) — describing the current operational state accurately
> - C1-01 (Core Problem) — framing what's been addressed vs. what gaps remain
> - C1-06 (Clinching Statement) — "direct observation at ISPSC Tagudin" includes observation of deployed system usage
> - C2-05 (Population and Locale) — respondents include people who have already used the system
> - The SUS/NASA-TLX evaluation — some respondents will have prior experience with the system

### What Needs to Happen (Formal Data Gathering)

The capstone team should conduct a **formal data-gathering session** with the Guidance Office to establish:

| Data Point | Method | Purpose |
|-----------|--------|---------|
| Which features were actually used | Interview + system audit logs (if available) | Accurate C1-04 and C1-01 framing |
| How many applicants were processed digitally | Database query or staff interview | Quantifiable evidence for the manuscript |
| Whether direct assessment was used | Staff interview | Confirms or refutes this workflow's deployment |
| Staff feedback on the system (informal) | Interview / observation | Early usability signal; informs SUS administration |
| What processes remain fully manual | Interview / observation | Identifies the remaining gap the capstone addresses |
| Whether they explored features beyond what was suggested | Interview | Reveals organic adoption patterns |
| What problems or limitations they encountered | Interview | Feeds directly into the "limitations of existing system" argument |

> This formal gathering should be part of the **Client Coordination & Data Gathering phase**
> (Phase 2 on the ROADMAP, currently in progress).

---

## Clarification 4: Role Model Considerations

### Current Role Model (6 roles)
1. Applicant / Examinee
2. Proctor
3. Test Administrator
4. Guidance Counselor
5. Registrar Staff / Registrar Administrator
6. Super Administrator

### Emerging Questions
With the deployment reality clearer and ISPSC's multi-campus structure in scope:

- **Campus Administrator** — Should there be a campus-level admin role between Registrar Admin and
  Super Admin? This would manage campus-specific configuration (rooms, courses, academic years) without
  having system-wide privileges like user creation.
- **Role granularity** — The Guidance Office is currently using a Super Admin account to do
  guidance-level tasks. This suggests the role boundaries may need tightening so that operational
  staff use properly scoped accounts, not an all-access admin account.
- **Research opportunity** — The role model refinement could itself be a finding from the descriptive
  phase. If the formal data gathering reveals that the Guidance Office needed capabilities not covered
  by their natural role, that's evidence for the RBAC pillar.

### Status
These are **open design questions**, not decisions. They should be informed by:
1. The formal data gathering from the Guidance Office
2. RBAC best-practice research (NIST 800-53, RBAC literature)
3. The actual Laravel policies in the codebase

---

## What Stays the Same

- Central thesis (integration gap) — unchanged
- Six research pillars — unchanged
- AIDLC software model — unchanged
- SUS + NASA-TLX instruments — unchanged
- Trojan Horse strategy — unchanged
- Multi-tenancy as data silo prevention — unchanged

---

*Document created: June 4, 2026. Rev 3 (Final): Added deployment specifics, verification gap,
formal data gathering requirement, and role model considerations.*
