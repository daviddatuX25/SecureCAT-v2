# Phase 4 — Demo Preparation Design

**Date:** 2026-04-10
**Covers:** changes.txt §4.1, §4.2, §4.3
**Goal:** Make the defense demo linear, complete, and cause-effect clear — with a full applicant lifecycle walkthrough and pre-release data that can be demonstrated live.

---

## Problem Statement

The existing `DEMO.md` and `DefenseDemoSeeder` have three gaps:

1. **Missing account setup step** — the flow jumps from "staff accepts" to a fully-logged-in applicant, skipping the email → setup link → first portal view chain.
2. **Session B consultation data is pre-filled** — `recommended_course_id` and `counselor_comments` are seeded, so the test admin cannot demonstrate entering them live. The demo looks pre-staged.
3. **Portal reveal is disconnected from release** — applicant portal steps appear after print steps, breaking the cause-and-effect between "admin releases" and "applicant sees result."

---

## Approach: DEMO.md Rewrite + Seeder Fix + Setup Command Polish

**No new routes, no new UI, no migrations.**

Three files change:

| File | Change type | Why |
|------|-------------|-----|
| `DEMO.md` | Full rewrite | Linear 6-act flow, Mailpit step, cause-effect ordering, f2f shortcut |
| `database/seeders/DefenseDemoSeeder.php` | 2-line fix | Clear Session B `recommended_course_id` and `counselor_comments` to `null` |
| `app/Console/Commands/DemoSetupCommand.php` | Enhancement | Auto-print session IDs; simplify quick setup |
| `tests/Feature/DefenseDemoSeederTest.php` | Assertion fix | Update Session B consultation assertion to expect `null` course ID |

---

## Demo Structure: 6 Acts

**Setup:** Two browser windows + Mailpit tab.
- **Browser A** — Staff/Admin side (`http://localhost:8000`)
- **Browser B** — Applicant Portal (same base URL, `/login` or portal routes)
- **Mailpit** — `http://localhost:8025` (local mail catcher)

Browser B is only switched to at three natural moments: Step 4 (account setup), Step 11 (portal reveal after release), Step 13 (Lorena checks exam).

---

### Act 1 — Application Lifecycle (~5 min)

**Narrative:** *"Let's follow an applicant from their very first interaction with the system."*

| Step | Actor | URL | Action |
|------|-------|-----|--------|
| 0 | Public | `/` | Home page — 30s scene-setter. Entry point for applicants. |
| 1 | Public | `/apply` | Submit Geraldine Santos' application live (form data provided in DEMO.md). |
| 2 | Staff — Maria | `/applications` | Show pending list. Accept Geraldine. Also open Carlos (dismissed) and Rodolfo (incomplete docs) for context. |
| 3 | — | Mailpit | Show setup email arrived for Geraldine. Copy the setup link. |
| 4 | Geraldine | Browser B → setup link | Click link → set password → portal dashboard. Shows "Accepted — awaiting exam scheduling." Say: *"We'll come back to explore the portal after the exam cycle."* |

**Key talking point:** Zero paper, zero manual account creation. Every action is logged.

---

### Act 2 — Exam Administration (~5 min)

**Narrative:** *"Now let's see how admins manage the exam pipeline."*

| Step | Actor | URL | Action |
|------|-------|-----|--------|
| 5 | Admin — Josefina | `/admin/test-scheduling` | 4 sessions at different lifecycle stages. |
| 6 | Admin | Session D detail | Assign Natividad, Virgilio, Erlinda to Session D. |
| 7 *(bonus)* | Admin | AI Scheduler | Ask: *"How many applicants are unassigned?"* or *"Suggest a schedule."* |

---

### Act 3 — Live Exam (~3 min)

**Narrative:** *"Session C is happening today. Let's watch the proctor work in real-time."*

| Step | Actor | URL | Action |
|------|-------|-----|--------|
| 8 | Proctor — Eduardo | `/proctor/sessions/{C}` | Lorena already present. Mark Roberto → Present. Maribel → Present. Arturo → Absent. |

---

### Act 4 — Scoring & Release (~5 min)

**Narrative:** *"Session B completed 5 days ago. The test administrator finishes grading and releases results."*

| Step | Actor | URL | Action |
|------|-------|-----|--------|
| 9 | Test Admin — Analiza | `/grading/sessions/{B}` | SA/NA/VR already entered (3 domains). Enter AR/LR/PSA for Rowena and Danilo → Finalize. |
| 10 | Test Admin | `/release` | Fields blank (seeder fix). Enter counselor notes + recommended course for Rowena (BSIT) and Danilo (BSCS) → Release each. |

> **f2f shortcut (Act 4):** If time is tight, skip counselor notes — use bulk release directly. Announce: *"In a real session, counselors add notes; for now we'll release directly."*

**Score data to enter live (Step 9):**

| Domain | Rowena | Danilo |
|--------|--------|--------|
| AR | 15 | 17 |
| LR | 14 | 16 |
| PSA | 13 | 15 |

**Counselor notes to type live (Step 10):**
- Rowena: *"Good aptitude scores overall. Recommended for BSIT based on SA and VR performance."*
- Danilo: *"Strong numerical ability. Recommended for BSCS."*

---

### Act 5 — Portal Reveal (~4 min)

**Narrative:** *"Now let's see what just happened on the applicant's side."*

This act immediately follows Act 4 — release happens in Step 10, portal reveal is Step 11. Cause-and-effect is visible to the panel.

| Step | Actor | URL | Action |
|------|-------|-----|--------|
| 11 | Rowena | Browser B → login | Result JUST released. Dashboard shows recommendation: BSIT. |
| 12 | Juan | Browser B → login | Session A — full result: scores per domain, counselor comments, recommended course. |
| 12B *(bonus)* | Juan | `/portal/ai-companion` | Ask: *"What does BSIT involve?"* or *"What do my scores mean?"* |
| 13 | Lorena | Browser B → login | Today's exam details — room, time, proctor name. Shows real-time scheduling. |

**Key talking point (Step 11):** *"Rowena's result was released 30 seconds ago. No delay, no batch job — the system is live."*

---

### Act 6 — Admin Tools (bonus, ~3 min)

**Narrative:** *"Finally, let's look at what the super admin sees."*

| Step | Actor | URL | Action |
|------|-------|-----|--------|
| 14 | Test Admin | `/grading/sessions/{A}` | Session A finalized — 3 score profiles (high, borderline, low). |
| 15 | Test Admin | Session A print view | Result sheet preview → browser print. |
| 16 | — | `/login` | Email + password login. Google Sign-In button if configured. |
| 17 | Super Admin — Ricardo | `/admin/logs` | Full audit trail of all actions in the demo. Filter by user and event type. |
| 18 | Super Admin | `/admin/settings` | AI toggle, release mode — *"configurable without code."* |

---

## Timing Guide

| Act | Core time | With bonus |
|-----|-----------|------------|
| Act 1 — Application | ~5 min | — |
| Act 2 — Admin | ~4 min | +2 min AI scheduler |
| Act 3 — Proctor | ~3 min | — |
| Act 4 — Scoring | ~5 min | — |
| Act 5 — Portal | ~4 min | +2 min AI companion |
| Act 6 — Admin tools | — | ~3 min |
| **Total** | **~21 min** | **~28 min** |

---

## Code Change Specs

### 1. DefenseDemoSeeder.php — seedSessionB()

In both `ConsultationSummary::query()->updateOrCreate(...)` calls (Rowena and Danilo):

```php
// Before
'recommended_course_id' => $courseId,   // pre-filled
'counselor_comments'    => null,

// After
'recommended_course_id' => null,         // blank — entered live in demo
'counselor_comments'    => null,
```

Remove the `$bsitId` / `$bscsId` variable lookups that are no longer used.

### 2. DemoSetupCommand.php

After `printSummary()`, add `printSessionIds()`:

```php
private function printSessionIds(): void
{
    $this->line('  <info>Session IDs (use these in URLs):</info>');
    ExamSession::with('room')
        ->orderBy('date')
        ->get()
        ->each(function ($s) {
            $this->line(sprintf(
                '    ID %-4d | %-12s | %-10s | %s / %s',
                $s->id,
                $s->date,
                $s->status,
                $s->room->building,
                $s->room->name
            ));
        });
    $this->line('');
    $this->line('  <info>Mailpit (local mail):</info> http://localhost:8025');
    $this->line('');
}
```

Call it in `handle()` after `printSummary()`.

### 3. DEMO.md

Full rewrite following the 6-act structure above. Key structural changes from current:
- Quick Setup → single `php artisan demo:setup` command
- Pre-Flight Checklist → add Mailpit tab; remove tinker step (IDs now printed by command)
- Replace 13-step flat list with 6 labelled acts
- `/portal/login` references → `/login` (per Phase 1 item 1.8)
- f2f shortcut callouts after Act 4 steps
- Add live form data table for Geraldine (Step 1)
- Add score entry tables for Step 9 and counselor note text for Step 10
- Move portal steps to immediately follow release (Act 5 after Act 4)
- Keep: Credentials Reference, Pre-Seeded Data Summary, Test Suite, Troubleshooting tables

### 4. DefenseDemoSeederTest.php

Find the Session B consultation assertion and update:

```php
// Before
$this->assertNotNull($consultation->recommended_course_id);

// After
$this->assertNull($consultation->recommended_course_id);
$this->assertNull($consultation->counselor_comments);
```

---

## Success Criteria

- [ ] `php artisan demo:setup` is the only pre-flight command — prints session IDs automatically
- [ ] DEMO.md flow is fully linear — no step requires backtracking or re-explaining context
- [ ] Act 4 → Act 5 transition demonstrates immediate release-to-portal cause-and-effect
- [ ] Session B grading shows blank notes/course fields — test admin fills them live
- [ ] Mailpit is referenced in pre-flight and Step 3
- [ ] All `DefenseDemoSeederTest` tests pass with the seeder change
- [ ] No admission slip references remain in DEMO.md
- [ ] `/portal/login` URL replaced with `/login` throughout
