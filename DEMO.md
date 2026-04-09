# SecureCAT v2 — Defense Demo Guide

> **ISPSC Tagudin Thesis Panel Defense**
> Full lifecycle walkthrough: application → exam → grading → results → portal

---

## What is SecureCAT?

SecureCAT v2 is a web-based **College Admission Test (CAT) Management System** for ISPSC Tagudin. It digitizes the entire CAT pipeline:

1. **Applicants** submit online and track their status through a secure portal
2. **Staff** review and accept applications
3. **Admins** schedule exam sessions, assign applicants to rooms, and manage seasons
4. **Proctors** manage attendance and exam submission in real-time
5. **Test Administrators** enter scores, finalize grading, and release consultation summaries
6. **Applicants** view results, counselor recommendations, and chat with an AI companion

**Tech stack:** Laravel 12 · Svelte 5 · Inertia.js · MySQL · Laravel Reverb (WebSockets)

---

## Quick Setup (run before the defense)

```bash
# 1. Fresh database + foundation data (roles, courses, aptitude areas, templates)
php artisan migrate:fresh --seed --seeder=DatabaseSeeder

# 2. Defense demo data (20 applications, 5 staff, 12 applicant accounts, 4 sessions)
php artisan db:seed --class=DefenseDemoSeeder

# 3. Start the local server
php artisan serve

# 4. Verify counts
php artisan tinker --execute="
echo 'Applications: ' . App\Models\Application::count() . PHP_EOL;
echo 'Applicants:   ' . App\Models\Applicant::count() . PHP_EOL;
echo 'Sessions:     ' . App\Models\ExamSession::count() . PHP_EOL;
echo 'Rooms:        ' . App\Models\Room::count() . PHP_EOL;
echo 'Consultations:' . App\Models\ConsultationSummary::count() . PHP_EOL;
"
```

**Expected output:**
```
Applications: 20
Applicants:   12
Sessions:     4
Rooms:        4
Consultations:5
```

### Get session IDs (needed for direct URL access)

```bash
php artisan tinker --execute="
App\Models\ExamSession::with('room')
    ->orderBy('date')
    ->get(['id','date','status'])
    ->each(fn(\$s) => print('ID ' . \$s->id . ' | ' . \$s->date . ' | ' . \$s->status . PHP_EOL));
"
```

---

## Pre-Flight Checklist (30 min before defense)

- [ ] Run seeder commands above, verify counts
- [ ] Open **Browser A** (Staff/Admin) — log in as Maria to start
- [ ] Open **Browser B** (Applicant Portal) — keep at `http://localhost:8000/portal/login`
- [ ] Copy the **Live Submission Data** below — paste into `/apply` form during Step 1
- [ ] Note the Session IDs from the tinker command above
- [ ] Confirm `php artisan serve` is running
- [ ] (Optional) If Google OAuth is configured — verify the Sign-In button appears on `/login`

---

## The Big Picture: What's Already Seeded

The seeder creates a full snapshot of a live CAT cycle with sessions in **different lifecycle stages** — so you can demonstrate every part of the system without waiting:

| Session | Date | Status | What it demonstrates |
|---------|------|--------|----------------------|
| **Session A** | 14 days ago | `completed` + results released | Fully finalized — show grading + consultation results |
| **Session B** | 5 days ago | `completed` + grading in progress | Partial scores entered — complete grading live |
| **Session C** | **Today** | `published` | Live attendance marking by proctor |
| **Session D** | 5 days from now | `published` | Upcoming session — shows forward scheduling |

---

## Demo Flow (follow in order)

---

### Step 0: Home Page — Set the Scene (30 seconds)

**URL:** `http://localhost:8000/`

- Open the home page — briefly point out: "This is the public-facing entry point of SecureCAT."
- Mention that applicants arrive here to submit their application — no login required.
- The staff/admin system is accessed via the `/login` page.

---

### Step 1: Live Application Submission (public — no login)

**URL:** `http://localhost:8000/apply`

**What to say:** *"Any student can submit an application online. The form is publicly accessible — no account needed."*

Fill in the form using this data:

| Field | Value |
|-------|-------|
| First Name | Geraldine |
| Last Name | Santos |
| Birthdate | 2006-05-20 |
| Sex | Female |
| Email | `geraldine.santos@ispsc-demo.local` |
| Phone | 09171009021 |
| Address | 456 Rizal St. |
| City | Tagudin |
| Province | Ilocos Sur |
| Zip | 2714 |
| Course Preference 1 | BSIT |
| Course Preference 2 | BSCS |
| Course Preference 3 | BSDS |

**After submitting:**
- System shows a success page with a **reference number** (e.g., `ISPSC-2026-0021`)
- Application is created as `pending`
- **Point out:** No portal account yet — that only happens after staff accepts the application

---

### Step 2: Staff Reviews & Accepts Applications

**Login:** `maria@securecat.local` / `password`
**URL:** `http://localhost:8000/applications`

**What to show:**

1. Go to Applications list — show it sorted by date
2. **Point out statuses:** pending (4), accepted, dismissed, incomplete_documents
3. Find **Geraldine Santos** (just submitted) — open her application
4. Show the application details: personal info, course preferences, submitted timestamp
5. Click **Accept** → confirm
   - Portal account is created automatically
   - Status changes to `accepted`
   - Setup email is sent (show the "Resend setup email" button)
6. Optional: Also accept **Rosalinda Aquino** (submitted today, idx=16) to show a second accept

**Also show:**
- **Dismiss flow:** Open a dismissed application (Carlos Vargas) — show the rejection reason logged
- **Incomplete documents:** Open Rodolfo Lacsamana — show "Missing PSA birth certificate" reason

**Key talking point:** *"Staff process applications without needing to use paper forms. Every action is logged for audit."*

---

### Step 3: Admin — Sessions Dashboard

**Login:** `josefina@securecat.local` / `password`
**URL:** `http://localhost:8000/admin/test-scheduling`

**What to show:**

1. Sessions list — all 4 sessions visible with their statuses
2. **Highlight Session C** (today) — `published` status, show room + time
3. Open Session A (completed) — show it's closed, grading finalized
4. Open Session D (future) — show it's scheduled but not started

**Point out:** *"The admin can see the full pipeline at a glance — what's upcoming, in progress, and completed."*

---

### Step 3B: Assign Applicants to a Session (bonus)

**Same login as Step 3**

- Open **Session D** (upcoming, 5 days from now)
- Click **Assign Applicants**
- Show the list of accepted/unassigned applicants: Natividad, Virgilio, Erlinda
- Assign them to Session D
- **Point out:** *"Admins control which applicants go to which session — useful when managing multiple rooms and dates."*

---

### Step 3C: AI Scheduling Assistant (bonus — impressive feature)

**Login:** `josefina@securecat.local` / `password`
**URL:** `http://localhost:8000/admin/test-scheduling`

- Click the **AI Scheduling Assistant** button (if visible on the index or session page)
- Type a natural language prompt, e.g.:
  - *"How many applicants are unassigned?"*
  - *"Suggest a schedule for the remaining applicants."*
- Show the AI response
- **Point out:** *"The assistant is context-aware — it knows your rooms, capacity, and current applicant load."*

---

### Step 4: Proctor — Mark Attendance Live

**Login:** `eduardo@securecat.local` / `password`
**URL:** `http://localhost:8000/proctor/sessions/{SESSION_C_ID}`

*(Replace `{SESSION_C_ID}` with the ID from the tinker command)*

**What to show:**

1. Open Session C roster
2. **Point out:** Lorena Tamayo is already marked **present** (she arrived before the demo started)
3. Live demo actions:
   - **Roberto Libed** → Mark **Present** ✓
   - **Maribel Pagulayan** → Mark **Present** ✓
   - **Arturo Madriaga** → Mark **Absent** ✗ (optional — shows absent tracking)
4. Show the present count updating

**Key talking point:** *"Proctors mark attendance in real-time. No paper rosters — the system is the single source of truth."*

---

### Step 5: Test Admin — Complete Session B Grading

**Login:** `analiza@securecat.local` / `password`
**URL:** `http://localhost:8000/grading/sessions/{SESSION_B_GRADING_ID}`

**What to show:**

1. Open Session B grading
2. **Point out:** SA, NA, VR scores already entered — grading is partially done
3. Open **Rowena Ballesteros** → Enter the remaining domain scores:
   | Domain | Score |
   |--------|-------|
   | AR | 15 |
   | LR | 14 |
   | PSA | 13 |
4. Save Rowena's scores
5. Open **Danilo Espiritu Jr.** → Enter:
   | Domain | Score |
   |--------|-------|
   | AR | 17 |
   | LR | 16 |
   | PSA | 15 |
6. Save Danilo's scores
7. Click **Finalize Grading** — confirm

**Key talking point:** *"Scores are entered per aptitude area (SA, NA, VR, AR, LR, PSA). Finalization locks the session — no further edits."*

---

### Step 6: Test Admin — Release Consultation Summaries (Session B)

**Login:** `analiza@securecat.local` / `password`
**URL:** `http://localhost:8000/release`

**What to show:**

1. Open Release Management — shows pending summaries for Rowena and Danilo
2. Open **Rowena Ballesteros** — add counselor comments:
   > *"Good aptitude scores overall. Recommended for BSIT based on SA and VR performance."*
3. Set recommended course: **BSIT**
4. Click **Release**
5. Do the same for **Danilo Espiritu Jr.**:
   > *"Strong numerical ability. Recommended for BSCS."*
6. Set recommended course: **BSCS**
7. Release Danilo
8. Optional: use **Bulk Release** to release both at once

**Key talking point:** *"Release management is a deliberate step — counselors review scores before applicants can see results. Prevents premature disclosure."*

---

### Step 7: Test Admin — View Session A Finalized Results

**Login:** `analiza@securecat.local` / `password`
**URL:** `http://localhost:8000/grading/sessions/{SESSION_A_GRADING_ID}`

**What to show:**

1. Open Session A grading — status: **Finalized**
2. Show all three applicants with full scores:

   | Applicant | SA | NA | VR | AR | LR | PSA | Result |
   |-----------|----|----|----|----|----|----|--------|
   | Juan Carlo Agustin | 22 | 20 | 21 | 17 | 20 | 16 | High — BSIT |
   | Maricel Dacumos | 14 | 13 | 15 | 10 | 13 | 11 | Borderline — BSCS |
   | Reynaldo Soriano | 8 | 9 | 7 | 6 | 8 | 7 | Low — retake advised |

3. Show that consultation summaries are already released for all three
4. **Highlight:** *"The system supports three outcomes — pass with recommendation, borderline, and retake advised."*

---

### Step 8: Print Result Sheets & Admission Slips

**Login:** `analiza@securecat.local` / `password`
**URL:** `http://localhost:8000/grading/sessions/{SESSION_A_GRADING_ID}/print`

**What to show:**

1. Go to the Print view for Session A
2. Show the **result sheet preview** for Juan — formatted with scores per domain
3. Click **Print** (browser print dialog)
4. Go back to Applications → open Juan's application → show **Admission Slip** button
5. Preview/print the admission slip

**Key talking point:** *"All printable outputs are generated from live data — no manual formatting. The template is configurable by the super admin."*

---

### Step 9: Applicant Portal — Juan Views His Result

**Browser B — URL:** `http://localhost:8000/portal/login`
**Login:** `juan.agustin@ispsc-demo.local` / `password`

**What to show:**

1. Login to the applicant portal
2. Dashboard shows Session A result — status: **Released**
3. Recommended course: **BSIT**
4. Counselor comments: *"Excellent performance. Highly recommended for BSIT."*
5. **Point out:** Applicant can see their result independently — no need to come to the office

---

### Step 9B: AI Companion — Applicant Asks Questions (bonus — impressive)

**Same login as Step 9**
**URL:** `http://localhost:8000/portal/ai-companion`

**What to show:**

1. Open the AI Companion tab in Juan's portal
2. Ask a question like:
   - *"What does BSIT involve?"*
   - *"What should I prepare for enrollment?"*
   - *"What do my scores mean?"*
3. Show the AI response (contextual, aware of ISPSC)
4. **Point out:** *"The AI companion is configured by the admin with ISPSC-specific knowledge documents — it gives relevant, localized answers."*

---

### Step 10: Applicant Portal — Lorena Checks Today's Exam

**Browser B — URL:** `http://localhost:8000/portal/login`
**Login:** `lorena.tamayo@ispsc-demo.local` / `password`

**What to show:**

1. Dashboard shows Session C — status: **Published** (today's exam)
2. Shows: room (Main Building / Room 102), time (9:00 AM – 11:00 AM), proctor name
3. **Point out:** *"Once the admin publishes a session, assigned applicants immediately see their exam details in the portal — no separate notification needed."*

---

### Step 11: Staff Login — Both Methods

**URL:** `http://localhost:8000/login`

**What to show:**

#### Method A: Email + Password
1. Enter: `maria@securecat.local` / `password`
2. Login → redirects to dashboard
3. **Point out:** Standard credential login with role-based access

#### Method B: Google Sign-In *(if Google OAuth is configured)*
1. On the Staff tab, show the **"Sign in with Google"** button
2. Click → redirects to Google's consent screen
3. **Point out:** *"Staff can link their institutional Google account for one-click login. The system matches by email — no new registration needed."*
4. After login: show the user is logged in with their role intact

> **Note:** Google Sign-In requires `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `.env`. The button only appears when these are configured.

---

### Step 12: Super Admin — Audit Logs (bonus)

**Login:** `admin@securecat.local` / `password`
**URL:** `http://localhost:8000/admin/logs`

**What to show:**

1. Full audit log of all actions during the demo
2. Filter by user — show Maria's application accepts
3. Filter by event type
4. Export to CSV
5. **Point out:** *"Every state-changing action is logged with the user, timestamp, and before/after values — full traceability for institutional accountability."*

---

### Step 13: Super Admin — System Settings (bonus)

**Login:** `admin@securecat.local` / `password`
**URL:** `http://localhost:8000/admin/settings`

**What to show:**

- AI Companion enable/disable toggle
- Release mode settings
- **Point out:** *"System behavior can be configured without touching code."*

---

## Credentials Reference

### Staff Accounts

| Role | Email | Password | Primary Demo Steps |
|------|-------|----------|--------------------|
| `super_admin` | `admin@securecat.local` | `password` | Audit logs, settings, users |
| `admin` | `josefina@securecat.local` | `password` | Sessions, scheduling, AI assistant |
| `staff` | `maria@securecat.local` | `password` | Application review & acceptance |
| `proctor` | `eduardo@securecat.local` | `password` | Attendance marking (Session C) |
| `test_administrator` | `analiza@securecat.local` | `password` | Grading, scoring, result release |

### Applicant Portal Accounts

| Name | Email | Password | Portal Status |
|------|-------|----------|---------------|
| Juan Carlo Agustin | `juan.agustin@ispsc-demo.local` | `password` | Session A — result **released** ✓ |
| Maricel Dacumos | `maricel.dacumos@ispsc-demo.local` | `password` | Session A — result **released** ✓ |
| Reynaldo Soriano | `reynaldo.soriano@ispsc-demo.local` | `password` | Session A — result **released** ✓ |
| Rowena Ballesteros | `rowena.ballesteros@ispsc-demo.local` | `password` | Session B — pending release |
| Danilo Espiritu Jr. | `danilo.espiritu@ispsc-demo.local` | `password` | Session B — pending release |
| Lorena Tamayo | `lorena.tamayo@ispsc-demo.local` | `password` | Session C — today's exam |
| Roberto Libed | `roberto.libed@ispsc-demo.local` | `password` | Session C — pending attendance |
| Maribel Pagulayan | `maribel.pagulayan@ispsc-demo.local` | `password` | Session C — pending attendance |
| Arturo Madriaga | `arturo.madriaga@ispsc-demo.local` | `password` | Session C — pending attendance |
| Natividad Ramirez | `natividad.ramirez@ispsc-demo.local` | `password` | Accepted — unassigned |
| Virgilio Castillo | `virgilio.castillo@ispsc-demo.local` | `password` | Accepted — unassigned |
| Erlinda De Vera | `erlinda.devera@ispsc-demo.local` | `password` | Accepted — unassigned |

> **Note:** 8 additional applicants (pending, dismissed, incomplete_documents) have **no portal account** — portal access is granted only after staff acceptance.

---

## Pre-Seeded Data Summary

### Applications (20 total)

| Status | Count | Notes |
|--------|-------|-------|
| `accepted` | 12 | Portal accounts created, assigned to sessions |
| `pending` | 4 | Nestor, Imelda, Ferdinand, Rosalinda — awaiting staff review |
| `dismissed` | 2 | Carlos, Analiza — "Did not appear for appointment" |
| `incomplete_documents` | 2 | Rodolfo (missing PSA), Teresita (missing Form 138) |

### Sessions

| Session | Date | Room | Status | Applicants |
|---------|------|------|--------|------------|
| Session A | Today − 14 days | Main Building / Room 101 | `completed` | Juan, Maricel, Reynaldo |
| Session B | Today − 5 days | Academic Building / Room 201 | `completed` | Rowena, Danilo |
| Session C | **Today** | Main Building / Room 102 | `published` | Lorena *(present)*, Roberto, Maribel, Arturo |
| Session D | Today + 5 days | Vocational Building / Lab Room 1 | `published` | *(empty — assign live in demo)* |

### Consultation Summaries

| Applicant | Status | Recommendation |
|-----------|--------|----------------|
| Juan Carlo Agustin | `released` | BSIT — *"Excellent performance"* |
| Maricel Dacumos | `released` | BSCS — *"Borderline scores"* |
| Reynaldo Soriano | `released` | BSIT — *"Low scores. Advised to retake."* |
| Rowena Ballesteros | `pending` | BSIT *(release live in Step 6)* |
| Danilo Espiritu Jr. | `pending` | BSCS *(release live in Step 6)* |

---

## Test Suite

```bash
# Run the full integration test suite
php artisan test tests/Feature/DefenseDemoSeederTest.php

# Run with verbose output
php artisan test tests/Feature/DefenseDemoSeederTest.php -v

# Run a specific test
php artisan test tests/Feature/DefenseDemoSeederTest.php --filter="session_a"

# Run full suite (all tests)
php artisan test
```

**Expected:** 10/10 DefenseDemoSeeder tests PASS.

---

## Google Sign-In Setup (optional — for Step 11B)

To enable Google Sign-In during the defense, add to `.env`:

```ini
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

Then restart: `php artisan serve`

The **"Sign in with Google"** button will appear on the Staff tab at `/login`. Without these values, the feature is silently hidden — no errors, no broken UI.

> See `docs/superpowers/plans/2026-04-09-google-signin.md` for full implementation details.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| "Database empty / seeder counts wrong" | Re-run: `php artisan migrate:fresh --seed --seeder=DatabaseSeeder && php artisan db:seed --class=DefenseDemoSeeder` |
| "Login fails for staff" | Verify `DefenseDemoSeeder` ran — check `users` table for `maria@securecat.local` |
| "Portal login fails" | Only accepted applicants have portal accounts — check application status |
| "Session C has wrong applicants" | Re-run `DefenseDemoSeeder` — it uses `updateOrCreate` and is idempotent |
| "Google button not showing" | Check `.env` for `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` |
| "AI Companion not responding" | Check `OPENAI_API_KEY` or configured AI provider in `.env` |
| "Print page blank" | Ensure `AdmissionSlipTemplateSeeder` ran (part of `DatabaseSeeder`) |
