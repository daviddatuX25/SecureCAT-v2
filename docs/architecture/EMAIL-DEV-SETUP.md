### Email Dev Setup (Mailpit + Sail)

- **Purpose**: Let developers test all email flows (applicant setup, portal reset password, future notifications) without a real SMTP server.
- **Tooling**: [Mailpit](https://github.com/axllent/mailpit) running as a Sail service.

#### 1. Starting Mailpit

- Mailpit is defined as a Sail service in `docker-compose.yml`:
  - Service name: `mailpit`
  - SMTP: `localhost:1025`
  - Web UI: `http://localhost:8025`
- To start everything (including Mailpit):

```bash
./vendor/bin/sail up -d
```

- To start just Mailpit (if the app is already running):

```bash
./vendor/bin/sail up -d mailpit
```

Then open `http://localhost:8025` in your browser to see all emails sent from the app.

#### 2. Local mail configuration

- `.env.example` is configured for Mailpit in local development:
  - `MAIL_MAILER=smtp`
  - `MAIL_HOST=mailpit`
  - `MAIL_PORT=1025`
  - `MAIL_USERNAME=null`
  - `MAIL_PASSWORD=null`
  - `MAIL_FROM_ADDRESS="hello@example.com"`
  - `MAIL_FROM_NAME="${APP_NAME}"`

For your **local `.env`**, you can usually keep these defaults. When deploying to production, override `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_*` with your real SMTP provider (e.g. Hostinger) — no code changes required.

#### 3. Queue: why emails might not appear in Mailpit

- **Applicant setup email** (on Accept and Resend setup email) is sent via a **queued job** (`SendApplicantSetupEmail`). With `QUEUE_CONNECTION=database`, the job is only run when a queue worker is processing jobs.
- **Two options for local testing:**
  1. **Run a queue worker** so queued emails are sent:
     ```bash
     ./vendor/bin/sail artisan queue:work
     ```
     Keep this running in a terminal; then trigger Accept or Resend setup email and the message should appear in Mailpit shortly.
  2. **Use the sync driver** so jobs run during the request (no worker needed). In your local `.env` set:
     ```
     QUEUE_CONNECTION=sync
     ```
     Then Accept / Resend setup email will send immediately and show up in Mailpit right away.
- **Portal forgot-password** sends mail directly (no queue), so that email appears in Mailpit without a worker.

#### 4. What to expect in dev

- All mails from:
  - Application acceptance (applicant setup link),
  - Resend setup email,
  - Applicant portal forgot-password / reset,
  - Any future mailables
  will appear in the Mailpit inbox once the job runs (see section 3) or when sent synchronously.
- This is safe to use even with fake/staging data.

#### 5. Quick manual test

1. Start Sail with Mailpit: `./vendor/bin/sail up -d`.
2. Either run `./vendor/bin/sail artisan queue:work` in another terminal, or set `QUEUE_CONNECTION=sync` in `.env`.
3. Trigger an email flow (e.g. accept an application, resend setup email, or portal forgot password).
4. Open `http://localhost:8025` and confirm the email appears with the expected subject and links.

#### 6. Relationship to in-app notifications & limits

- **Primary channel** for applicants will be **in-app portal notifications / status surfaces** (portal dashboard, status tracker, etc.).
- **Email is treated as best-effort**:
  - Helpful for links (setup, reset, reminders) but not the only way applicants see critical information.
  - If the production SMTP provider (e.g. Hostinger) enforces daily send limits (e.g. 1000/day), the system should continue to function using in-app notifications even if some emails are delayed or dropped.
- **Future work (separate beads)** can add:
  - Centralized mail queue workers with simple rate awareness (e.g. cap sends per minute / day).
  - Optional UI messaging like “Email may arrive later; you can always see updates in your portal”.
  - In-app notification storage and display (BD-4nx / BD-2b3).


