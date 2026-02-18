# Agent Instructions

This project follows a **phase-driven agentic workflow** with **beads** (bd) for issue tracking. Run `bd onboard` to get started.

**Related rules** (in `.cursor/rules/`):
- `agentic-workflow.mdc` — Session Start Ritual, Implementation, Session End, Decision Points
- `plan-first-and-bead-scope.mdc` — Plan-first scope, bead scoping, dependencies
- `stack-conventions.mdc` — Laravel, Inertia, Svelte 5, anti-patterns
- `environment.mdc` — Sail/Docker, commands
- `developing-gotchas.mdc` — Bead daemon, frontend/Inertia/Svelte gotchas (apply when bd errors or Svelte/Inertia issues)
- `developing-conventions.mdc` — UI/convention patterns (Badge, status mapping, tables, forms)
- `mockup-builder-agent.mdc` — Mockup-first mode for UI work

---

## Core Principles

1. **Contract over Creativity** — Implementation follows specs in `docs/architecture/`, not imagination
2. **Phases over Perfection** — Deliver current phase completely before touching later phases
3. **Beads = Long-Term Memory** — Beads persist all work items; if it's not a bead, create one
4. **Session = Current Scope Only** — Focus on the current bead's goal; new work = new bead
5. **Ask Before Assuming** — One question up front beats ten rollbacks later
6. **Test-First for Behavior** — Write the failing test, then implement until green

---

## Workflow at a Glance

| Phase | What to Do | Key Commands |
|-------|------------|--------------|
| **1. Plan-First** | Surface ready/blocked work, ask user what to work on, get explicit confirmation | `bd ready`, `bd list --status=open`, `bd dep` |
| **2. Session Start Ritual** | Claim bead, verify phase alignment, read contracts, draft plan, explore edge cases, transpose to beads, confirm with user | `bd show <id>`, `bd update <id> --status in_progress`, `bd create`, `bd dep add` |
| **3. Implementation** | TDD loop, code by contract, no placeholders | `./vendor/bin/sail artisan test`, `./vendor/bin/sail npx playwright test` |
| **4. Session End Ritual** | Update beads, push, explore gaps, handoff report, prompt next action | `bd update <id> --status done`, `bd sync`, `git push` |

See `agentic-workflow.mdc` for full detail.

---

## Starting Work

**When the user says "start working", "let's work", "proceed", or similar:**

1. **Surface scope** — Run `bd ready` to see unblocked work
2. **Present snapshot** — Show what's ready and what (if anything) is blocked
3. **Ask** — "What should we work on?" or "I see BD-X ready. Proceed with BD-X?"
4. **Confirm** — Wait for explicit choice (specific bead, "next ready task", or new goal)
5. **Then** — Run the full Session Start Ritual (`agentic-workflow.mdc`): claim bead, read contracts, plan, confirm, implement

Do **not** auto-start the first ready bead without user confirmation. Get explicit scope first.

### Quick Bead Reference

```bash
bd ready                              # Find available work
bd show <id>                           # View issue details
bd list --status=open                  # All open work
bd dep                                 # Dependencies and blockers
bd update <id> --status in_progress    # Claim work
bd update <id> --status done           # Complete work
bd create "Title" --type=task          # Create new bead
bd dep add <issue> <depends-on>        # Add dependency
bd sync                                # Sync with git
```

---

## Landing the Plane (Session Completion)

**When ending a work session**, you MUST complete ALL steps below. Work is NOT complete until `git push` succeeds.

**MANDATORY WORKFLOW:**

1. **File issues for remaining work** — Create beads for anything that needs follow-up
2. **Run quality gates** (if code changed) — Tests, linters, builds
3. **Update bead status** — `bd update <id> --status done` for finished work
4. **PUSH TO REMOTE** — This is MANDATORY:
   ```bash
   git pull --rebase origin main
   bd sync
   git push
   git status  # MUST show "up to date with origin"
   ```
5. **Handoff report** — Session summary with COMPLETED, IN PROGRESS, NEXT UP, NEW BEADS, BLOCKERS, QUALITY GATES
6. **Prompt next action** — "Would you like me to: A) Start BD-ZZZ, B) Address [optional case], C) Something else?"

**CRITICAL RULES:**
- Work is NOT complete until `git push` succeeds
- NEVER stop before pushing — that leaves work stranded locally
- NEVER say "ready to push when you are" — YOU must push
- If push fails, resolve and retry until it succeeds

---

## Anti-Patterns to Avoid

| Anti-Pattern | Fix |
|--------------|-----|
| Scope Creep | Only do what bead asks; suggest extras as new beads |
| Spec Violation | If spec seems wrong, ask to update it |
| Assumption Coding | Ask "What specifically should X do?" |
| Over-Explaining | Brief references to specs |
| Invisible Work | Always provide session summary |
| Silently fixing issues | Present options and ask (A/B/C) before changing |

---

## Decision Points

- **"Continue" or "Next"** → Run `bd ready`, scan for obvious next; otherwise ask
- **Spot a discrepancy** → Do NOT silently fix; present options and ask
- **Out-of-scope request** → Offer: create Phase X bead & defer, or simplify current-phase version, or update scope freeze

---

## Environment & Stack

- **Environment:** Laravel Sail (Docker). Prefer `./vendor/bin/sail` for PHP, Composer, Artisan, MySQL, npm. See `environment.mdc`.
- **Stack:** Laravel 12, Inertia v2, Svelte 5, Tailwind 4, shadcn-svelte. See `stack-conventions.mdc` for anti-patterns and conventions.
