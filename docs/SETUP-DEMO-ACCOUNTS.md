# SecureCAT Demo — Setup & Runbook

One-page reference for running a full demo with seeded data.

---

## 1. Prepare the demo

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
```

Optional: for applicant **setup-by-email** flows, use `QUEUE_CONNECTION=sync` in `.env` or run `./vendor/bin/sail artisan queue:work` so setup emails are sent.

---

## 2. All logins (cheat sheet)

| Where | Email | Password |
|-------|--------|----------|
| **Staff app** `/login` | `admin@example.com` | `Password1!` |
| **Staff app** `/login` | `lorna.santos@example.com` | `password` |
| **Staff app** `/login` | `juan.delacruz@example.com` | `password` |
| **Staff app** `/login` | `ana.garcia@example.com` | `password` |
| **Staff app** `/login` | `pedro.ramos@example.com` | `password` |
| **Staff app** `/login` | `sonny.jalorina@example.com` | `password` |
| **Staff app** `/login` | `jalorina.reyes@example.com` | `password` |
| **Staff app** `/login` | `miguel.reyes@example.com` | `password` |
| **Applicant portal** `/portal/login` | `demo@applicant.test` | `password` |

Super Admin credentials can be overridden with `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD`, `SUPER_ADMIN_NAME` in `.env`.

---

## 3. Who to use for what

| Role | Account | Use for |
|------|---------|--------|
| **Super Admin** | `admin@example.com` | Users, roles, full admin; audit logs; templates. |
| **Registrar Admin** | `lorna.santos@example.com` | Applications list, accept/reject; scheduling; courses/rooms. |
| **Registrar Staff** | `juan.delacruz@example.com` (or Ana, Pedro) | Same as admin with staff-level access. |
| **Proctor / Grader / Counselor** | `sonny.jalorina@example.com` (or Jalorina Reyes, Miguel Reyes) | Exam roster, scan attendance, grading session, consultation schedule. |
| **Applicant** | `demo@applicant.test` | Portal: view status, admission slip, consultation. |

---

## 4. Suggested demo flow

1. **Super Admin** — Log in as `admin@example.com`. Show users, roles, exam sessions, rooms, result sheet / admission slip templates.
2. **Registrar** — Log in as `lorna.santos@example.com`. Show Applications (pending / accepted / rejected), accept or reject one, scheduling.
3. **Proctor** — Log in as `sonny.jalorina@example.com`. Open a published or in-progress exam session → Session Roster; show attendance and submission statuses.
4. **Grading** — Same or another grader. Open Grading → session with submitted papers; show data entry / print batch.
5. **Applicant portal** — Log in at `/portal/login` as `demo@applicant.test` / `password`. Show dashboard, admission slip, consultation (if released).

---

## 5. What gets seeded

| Data | Count / description |
|------|----------------------|
| **Roles** | super_admin, staff, admin, proctor, grader, counselor |
| **Courses** | BSIT, BSCS, BSDS (College of IT) |
| **Rooms** | 5 (IT 101/202, Main 301, Science Lab A, AVR 1) |
| **Applications** | 15 pending, 20 accepted (first = `demo@applicant.test`), 5 rejected |
| **Exam sessions** | 1 draft, 1 published (future), 1 in progress (today), 1 completed (with grading session) |
| **Proctor demo** | At least one session with applicants and mixed attendance/submission |

Seeder order: foundation (roles, courses, domains, templates) → **DemoAccountSeeder** → **RealisticDataSeeder** → **ProctorDemoSeeder**. See `database/seeders/DatabaseSeeder.php`.

---

## 6. Re-seed without wiping

To add or update seeded data without running migrations:

```bash
./vendor/bin/sail artisan db:seed
```

This does not truncate tables; seeders use `firstOrCreate` / `updateOrCreate` where applicable.
