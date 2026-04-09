# Defense Demo Data — Design Spec
**Date:** 2026-04-09
**Project:** SecureCAT-v2
**School context:** ISPSC Tagudin (Ilocos Sur Polytechnic State College — Tagudin Campus)

---

## Overview

Replace `DemoDashboardSeeder` with a new `DefenseDemoSeeder` that seeds a realistic, defense-ready
snapshot of the system. All dates anchor to `CarbonImmutable::today()` at seed time, so the data
always looks "live" no matter when you run it. Names, addresses, and building references are
specific to ISPSC Tagudin.

Run with:
```bash
php artisan db:seed --class=DefenseDemoSeeder
```

---

## 1. Time Anchor

- All dates are computed relative to `$today = CarbonImmutable::today()` at the top of `run()`.
- No frozen-clock env var needed — just re-seed before the defense and everything recalculates.
- Season academic year auto-derives: if `today()->month >= 6`, use `YYYY–(YYYY+1)`; else `(YYYY-1)–YYYY`.
  - Example: April 2026 → `2025–2026`; July 2026 → `2026–2027`.
- Application window: opened 45 days ago, closes 14 days from today.

---

## 2. Season

| Field | Value |
|---|---|
| academic_year | Derived from today (see above) |
| semester | `1` |
| is_active | `true` |
| application_start_date | `today - 45 days` |
| application_end_date | `today + 14 days` |

---

## 3. Rooms (ISPSC Tagudin buildings)

| Building | Room Name | Floor | Capacity |
|---|---|---|---|
| Main Building | Room 101 | 1st Floor | 30 |
| Main Building | Room 102 | 1st Floor | 30 |
| Academic Building | Room 201 | 2nd Floor | 40 |
| Vocational Building | Lab Room 1 | Ground Floor | 25 |

---

## 4. Staff Users

All use password: `password`

| Role | Name | Email |
|---|---|---|
| super_admin | Ricardo Dela Cruz | admin@securecat.local |
| admin | Josefina Gaerlan | josefina@securecat.local |
| staff | Maria Corpuz | maria@securecat.local |
| proctor | Eduardo Fariñas | eduardo@securecat.local |
| test_administrator | Analiza Barroga | analiza@securecat.local |

---

## 5. Applicants (20 total — Ilocano names, Tagudin/Ilocos Sur addresses)

Accepted applicants (assigned + unassigned, 12 total) get an `Applicant` portal account with password: `password`. Pending and dismissed applicants do not get portal accounts (they haven't been accepted yet).

### Accepted + Assigned to Sessions (8)
These appear in exam session rosters. Realistic mix of male/female, born 2005–2007.

| # | Name | Sex | City | Session |
|---|---|---|---|---|
| 1 | Juan Carlo Agustin | M | Tagudin, Ilocos Sur | Session A (14 days ago) |
| 2 | Maricel Dacumos | F | Tagudin, Ilocos Sur | Session A (14 days ago) |
| 3 | Reynaldo Soriano | M | Candon City, Ilocos Sur | Session A (14 days ago) |
| 4 | Rowena Ballesteros | F | Narvacan, Ilocos Sur | Session B (5 days ago) |
| 5 | Danilo Espiritu Jr. | M | Tagudin, Ilocos Sur | Session B (5 days ago) |
| 6 | Lorena Tamayo | F | Santiago, Ilocos Sur | Session C (today — live demo) |
| 7 | Roberto Libed | M | Tagudin, Ilocos Sur | Session C (today — live demo) |
| 8 | Maribel Pagulayan | F | Sudipen, La Union | Session C (today — live demo) |

### Accepted + Unassigned (4)
Show the "accepted but not yet scheduled" backlog in the admin view.

| # | Name | Sex | City |
|---|---|---|---|
| 9 | Arturo Madriaga | M | Tagudin, Ilocos Sur |
| 10 | Natividad Ramirez | F | Candon City, Ilocos Sur |
| 11 | Virgilio Castillo | M | Vigan City, Ilocos Sur |
| 12 | Erlinda De Vera | F | Tagudin, Ilocos Sur |

### Pending (4)
Show the staff acceptance queue (applications awaiting review).

| # | Name | Sex | Submitted |
|---|---|---|---|
| 13 | Nestor Domingo | M | 3 days ago |
| 14 | Imelda Gaerlan | F | 1 day ago |
| 15 | Ferdinand Molina | M | 5 days ago |
| 16 | Rosalinda Aquino | F | today |

### Dismissed / Incomplete (4)
Show rejected/incomplete state in the applications list.

| # | Name | Sex | Status | Reason |
|---|---|---|---|---|
| 17 | Carlos Vargas | M | dismissed | Incomplete supporting documents |
| 18 | Analiza Marcos | F | dismissed | Did not appear for scheduled appointment |
| 19 | Rodolfo Lacsamana | M | incomplete_documents | Missing PSA birth certificate |
| 20 | Teresita Mirasol | F | incomplete_documents | Missing Form 138 |

---

## 6. Exam Sessions — The 4-State Pipeline

Each session represents a different stage of the pipeline, enabling demos of any workflow step.

### Session A — "Completed" (14 days ago)
- **Date:** `today - 14 days`, 9:00 AM – 11:00 AM
- **Room:** Main Building Room 101
- **Applicants:** #1 Juan Agustin, #2 Maricel Dacumos, #3 Reynaldo Soriano
- **Attendance:** All 3 marked present, all 3 marked submitted
- **Grading:** `STATUS_FINALIZED` — all domains scored, full scores seeded
- **Consultation:** All 3 summaries `released` — result sheets printable
- **Purpose:** Show the end-to-end completed pipeline; demonstrate result sheet printing.

### Session B — "Grading In Progress" (5 days ago)
- **Date:** `today - 5 days`, 1:00 PM – 3:00 PM
- **Room:** Academic Building Room 201
- **Applicants:** #4 Rowena Ballesteros, #5 Danilo Espiritu Jr.
- **Attendance:** Both marked present, both marked submitted
- **Grading:** `STATUS_IN_PROGRESS` — half of domains scored, rest empty
- **Consultation:** `pending`
- **Purpose:** Show the score entry / grading screen in action.

### Session C — "Today, Live Demo" (today)
- **Date:** `today`, 9:00 AM – 11:00 AM
- **Room:** Main Building Room 102
- **Applicants:** #6 Lorena Tamayo, #7 Roberto Libed, #8 Maribel Pagulayan
- **Attendance:** #6 Lorena marked present; #7 and #8 NOT yet marked
- **Grading:** `STATUS_OPEN` — no scores yet
- **Consultation:** none yet
- **Purpose:** **Hero live demo slot.** Proctor logs in, sees 2 pending, marks them present live.

### Session D — "Upcoming" (5 days from now)
- **Date:** `today + 5 days`, 9:00 AM – 11:00 AM
- **Room:** Vocational Building Lab Room 1
- **Applicants:** None assigned yet (or optionally assign #9 Arturo + #10 Natividad)
- **Grading:** none
- **Purpose:** Show the scheduling/upcoming view on the proctor and admin dashboards.

---

## 7. Grading & Scores

- **Session A:** All active `ExamDomain` records scored. `raw_score` varies per applicant (not all 10s) to look realistic. `GradingSession` status = `STATUS_FINALIZED`.
- **Session B:** First half of domains scored, second half null. `GradingSession` status = `STATUS_IN_PROGRESS`.
- **Session C/D:** `GradingSession` status = `STATUS_OPEN`, no scores.

Score values for Session A (to vary results):
- Applicant #1: high scores (passing)
- Applicant #2: borderline scores
- Applicant #3: low scores (failing)

---

## 8. Consultation Summaries

| Applicant | Status | Recommended Course | Notes |
|---|---|---|---|
| #1 Juan Agustin | released | BSIT | High performer |
| #2 Maricel Dacumos | released | BSCS | Borderline, counseled to BSCS |
| #3 Reynaldo Soriano | released | BSIT | Low scores, advised to retake |
| #4 Rowena Ballesteros | pending | BSIT | Awaiting counselor action |
| #5 Danilo Espiritu Jr. | pending | BSCS | Awaiting counselor action |

---

## 9. Applicant Portal Accounts

The 12 accepted applicants (#1–#12) get `Applicant` records for portal login. Credentials: email = seeded email, password = `password`.

- Session A applicants (#1–#3) → can see their released result in the portal
- Session C applicants (#6–#8) → can see "exam scheduled for today"
- Unassigned accepted (#9–#12) → can see "accepted, awaiting schedule"

---

## 10. Seeder Behavior

- **Idempotent:** Uses `updateOrCreate` on stable keys (reference numbers, emails, room names). Safe to re-run.
- **Standalone:** Can be called alone (`--class=DefenseDemoSeeder`) without wiping other seed data.
- **Depends on:** Active Season, active Courses (≥3), active ExamDomains (≥3) — asserts these exist at the top and warns + exits gracefully if not.
- **Does NOT call** `DatabaseSeeder` — run `DatabaseSeeder` first for base data, then `DefenseDemoSeeder`.

### Recommended seed order for fresh install:
```bash
php artisan migrate:fresh
php artisan db:seed                          # roles, courses, domains, base data
php artisan db:seed --class=DefenseDemoSeeder
```

---

## 11. Reference Numbers

Applications use the format `ISPSC-YYYY-NNNN` where `YYYY` = current year:
- `ISPSC-2026-0001` through `ISPSC-2026-0020`

---

## Out of Scope

- Faker/randomized data — all names and data are hardcoded for deterministic, presentation-safe results
- AI companion seeding
- Knowledge documents
- Audit log seeding
