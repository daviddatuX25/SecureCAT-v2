# SecureCAT Demo — Setup & Runbook

One-page reference for running a full demo with seeded data.

---

## 0. Verify you're on the right Sail instance

**This project is SecureCAT-v2.** It uses:

- **APP_URL**: `http://localhost:8080` (see `.env`)
- **Vite dev server**: `http://localhost:5174` (VITE_DEV_SERVER_URL in `.env`)
- **Container prefix**: `securecat-v2-` (from project directory name)

If you see a different app (e.g. another project on port 80 or 8888), you're on the wrong instance.

**Quick check — am I in the SecureCAT repo?**

```bash
cd /path/to/your/project
grep -E '^APP_URL=|^APP_NAME=' .env
# SecureCAT-v2 .env has: APP_URL=http://localhost:8080
cat docker-compose.yml | head -5
# Same file as this repo = same project
./vendor/bin/sail ps
# SecureCAT shows: securecat-v2-laravel.test-1, securecat-v2-mysql-1, securecat-v2-redis-1, etc.
```

To check exam domains are seeded:

```bash
./vendor/bin/sail artisan tinker --execute="echo 'Exam domains: ' . \App\Models\ExamDomain::count();"
# Should show: Exam domains: 6
```

If it shows 0, run `./vendor/bin/sail artisan db:seed` (or `migrate:fresh --seed` for a full reset).

---

### Full reset (SecureCAT only): stop → rebuild → up → migrate → seed → npm run dev

Use this when you want to be sure you're running this repo and nothing else for SecureCAT:

```bash
cd /home/sarmi/projects/SecureCAT-v2   # or your SecureCAT-v2 path

# 1. Stop only this project's containers (no other Docker projects are touched)
./vendor/bin/sail down

# 2. Rebuild images and start containers (first time or after Dockerfile changes)
./vendor/bin/sail up -d --build
# Wait until containers are healthy (e.g. mysql ready). Then:

# 3. Migrate and seed
./vendor/bin/sail artisan migrate:fresh --seed

# 4. Start the Vite dev server (for frontend hot reload)
./vendor/bin/sail npm run dev
```

Leave `sail npm run dev` running in a terminal. Use **http://localhost:8080** in the browser (and ensure no other app is bound to port 8080).

**Blank page?** If you see a blank page and only a tiny `app.css` (e.g. 127 B) in the Network tab, Laravel is still trying to load assets from the Vite dev server (because `public/hot` exists) but the dev server may not be running or reachable. Either:
- **Option A:** Run `sail npm run dev` and keep it running, then reload. The app uses `VITE_DEV_SERVER_URL=http://localhost:5174` and Docker maps host 5174 → container 5174 so the browser can load Vite assets.
- **Option B:** Use the built assets: remove the hot file and reload — `rm -f public/hot` (Laravel will then serve from `public/build/`).

**"Port 5173 is already in use"?** The project is configured so Vite runs on port 5174 in Sail (see `vite.config.js` and `docker-compose.yml`). If you still see a port conflict, restart Sail: `sail down && sail up -d`, then run `sail npm run dev` again.

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
| **Proctor / Test Administrator** | `sonny.jalorina@example.com` (or Jalorina Reyes, Miguel Reyes) | Exam roster, scan attendance, grading session, consultation schedule. |
| **Applicant** | `demo@applicant.test` | Portal: view status, admission slip, consultation. |

---

## 4. Suggested demo flow

1. **Super Admin** — Log in as `admin@example.com`. Show users, roles, exam sessions, rooms, result sheet / admission slip templates.
2. **Registrar** — Log in as `lorna.santos@example.com`. Show Applications (pending / accepted / rejected), accept or reject one, scheduling.
3. **Proctor** — Log in as `sonny.jalorina@example.com`. Open a published or in-progress exam session → Session Roster; show attendance and submission statuses.
4. **Grading** — Same or another test administrator. Open Grading → session with submitted papers; show data entry / print batch.
5. **Applicant portal** — Log in at `/portal/login` as `demo@applicant.test` / `password`. Show dashboard, admission slip, consultation (if released).

---

## 5. What gets seeded

| Data | Count / description |
|------|----------------------|
| **Roles** | super_admin, staff, admin, proctor, test_administrator |
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
