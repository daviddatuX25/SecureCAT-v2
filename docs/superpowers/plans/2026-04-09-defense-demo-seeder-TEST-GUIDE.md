# DefenseDemoSeeder — Test & Demo Guide

## Running the Seeder

### Fresh database (recommended before defense)
```bash
php artisan migrate:fresh --seed --seeder=DatabaseSeeder
php artisan db:seed --class=DefenseDemoSeeder
```

### Idempotent re-run (safe to re-execute anytime)
```bash
php artisan db:seed --class=DefenseDemoSeeder
```

### Verify counts
```bash
php artisan tinker --execute="
echo 'Applications: ' . App\Models\Application::count();
echo 'Applicants: '   . App\Models\Applicant::count();
echo 'Exam sessions: '. App\Models\ExamSession::count();
echo 'Rooms: '        . App\Models\Room::count();
echo 'Consultations: '. App\Models\ConsultationSummary::count();
"
```

**Expected output:**
```
Applications: 20
Applicants: 12
Exam sessions: 4
Rooms: 4
Consultations: 5
```

---

## Staff Accounts (web app)

| Role | Email | Password | Used for |
|------|-------|----------|----------|
| super_admin | `admin@securecat.local` | `password` | Full access |
| admin | `josefina@securecat.local` | `password` | Manage sessions / rooms |
| staff | `maria@securecat.local` | `password` | Review & accept applications |
| proctor | `eduardo@securecat.local` | `password` | **Live demo** — mark attendance |
| test_administrator | `analiza@securecat.local` | `password` | Enter scores, release consultations |

---

## Applicant Portal Accounts

| Name | Email | Password | Status |
|------|-------|----------|--------|
| Juan Carlo Agustin | `juan.agustin@ispsc-demo.local` | `password` | Session A — released result |
| Maricel Dacumos | `maricel.dacumos@ispsc-demo.local` | `password` | Session A — released result |
| Reynaldo Soriano | `reynaldo.soriano@ispsc-demo.local` | `password` | Session A — released result |
| Rowena Ballesteros | `rowena.ballesteros@ispsc-demo.local` | `password` | Session B — pending |
| Danilo Espiritu Jr. | `danilo.espiritu@ispsc-demo.local` | `password` | Session B — pending |
| Lorena Tamayo | `lorena.tamayo@ispsc-demo.local` | `password` | Session C — today's exam |
| Roberto Libed | `roberto.libed@ispsc-demo.local` | `password` | Session C — present (proctor marks) |
| Maribel Pagulayan | `maribel.pagulayan@ispsc-demo.local` | `password` | Session C — pending |
| Arturo Madriaga | `arturo.madriaga@ispsc-demo.local` | `password` | Session C — pending |
| Natividad Ramirez | `natividad.ramirez@ispsc-demo.local` | `password` | Unassigned |
| Virgilio Castillo | `virgilio.castillo@ispsc-demo.local` | `password` | Unassigned |
| Erlinda De Vera | `erlinda.devera@ispsc-demo.local` | `password` | Unassigned |

> Note: 8 additional applicants are in `pending` / `dismissed` / `incomplete_documents` status and have **no portal account**.

---

## Exam Sessions

| Session | Date | Room | Status | Purpose |
|---------|------|------|--------|---------|
| Session A | 14 days ago | Main Building / Room 101 | Completed | Finalized — results released |
| Session B | 5 days ago | Academic Building / Room 201 | Completed | Grading in progress |
| Session C | **Today** | Main Building / Room 102 | Published | **Live demo slot** |
| Session D | 5 days from now | Vocational Building / Lab Room 1 | Published | Upcoming |

---

## Demo Flow (ISPSC Tagudin Defense)

### 1. Staff (Maria) — Applications list
**Login:** `maria@securecat.local` / `password`
- Go to Applications
- Show pending applications (Nestor, Imelda, Ferdinand, Rosalinda)
- Accept one live (e.g., approve Rosalinda)
- Show how status changes to `accepted` and portal account is created

### 2. Admin (Josefina) — Sessions overview
**Login:** `josefina@securecat.local` / `password`
- Go to Exam Sessions
- Show all 4 sessions in different states
- Point out Session C is today with `published` status

### 3. Proctor (Eduardo) — Mark attendance live
**Login:** `eduardo@securecat.local` / `password`
- Open Session C roster
- Roberto Libed → Mark **present**
- Maribel Pagulayan → Mark **present**
- Arturo Madriaga → Mark **absent** (optional)
- Show how `present` count updates in real-time

### 4. Test Admin (Analiza) — Complete Session B grading
**Login:** `analiza@securecat.local` / `password`
- Open Session B grading
- Enter remaining domain scores (AR, LR, PSA) for Rowena & Danilo
- Finalize Session B grading
- Release consultation summaries for Rowena & Danilo
- Applicants now see pending consultation results

### 5. Test Admin (Analiza) — View Session A finalized results
**Login:** `analiza@securecat.local` / `password`
- Open Session A
- Show finalized grading status
- Show consultation summaries already released
- Juan: `Excellent performance. Highly recommended for BSIT.`
- Maricel: `Borderline scores. Recommended to consider BSCS.`
- Reynaldo: `Low scores across domains. Advised to review and retake.`

### 6. Print result sheets
**Login:** `analiza@securecat.local` / `password`
- Print Session A result sheets for Juan, Maricel, Reynaldo
- Show admission slip printing flow

### 7. Applicant Portal — Juan logs in to see result
**Login:** `juan.agustin@ispsc-demo.local` / `password`
- Dashboard shows released result for Session A
- Shows recommended course: **BSIT**
- Shows counselor comments

### 8. Applicant Portal — Lorena sees today's exam
**Login:** `lorena.tamayo@ispsc-demo.local` / `password`
- Dashboard shows Session C (today's exam) in published state
- Shows room, time, proctor info

---

## Test Commands

```bash
# Run the full integration test suite
php artisan test tests/Feature/DefenseDemoSeederTest.php

# Run specific test
php artisan test tests/Feature/DefenseDemoSeederTest.php --filter="session_a"

# Run with verbose output
php artisan test tests/Feature/DefenseDemoSeederTest.php -v
```

**Expected: 10/10 tests PASS**
