# SecureCAT-v2 — Presentation Source

**Format:** 4 slides + live demo | **Total time:** 10 minutes
**Audience:** Thesis panel (ISPSC Guidance & Counseling Services)
**Style:** Dark theme, sharp contrast, academic/professional
**Source of truth:** `SecureCAT-pilot-proposal.md` (original scope) + implemented enhancements (exam-session workflow, notifications, AI Companion)

> **Timing budget (10 min)**
> Slide 1 Title · 0:20
> Slide 2 Problem & Objectives · 1:30
> Slide 3 Scope & Roles · 1:00
> Slide 4 Features & Tech · 1:10
> **Live Demo** · 5:30
> Closing / Q&A buffer · 0:30

---

## Slide 1 — Title (20 sec)

| Element | Content |
|---------|---------|
| Logo mark | **SC** (red box) |
| Tagline | Capstone Project — ISPSC · 2026 |
| Title | **SecureCAT-v2** |
| Subtitle | A Role-Based College Admission Testing System |
| Byline | Built with Information Assurance & Security principles |

**Speaker script (read aloud):**
> "Good [morning/afternoon], panel. I am [name], and with me is [name]. We present **SecureCAT-v2** — a Secure College Admission Testing system for the ISPSC Guidance and Counseling Services. It replaces the manual, paper-based admission workflow with a role-based web application grounded in the five pillars of Information Assurance: confidentiality, integrity, availability, accountability, and non-repudiation."

**Cue:** advance on the word *"non-repudiation"* — do not linger.

---

## Slide 2 — Problem & Objectives (90 sec)

**Layout:** Two columns — left lists problems, right maps each to a concrete objective. Red-to-green flow.

| The Problem (Today) | Our Objective (SecureCAT) |
|---------------------|----------------------------|
| Paper-based applications, lost forms, slow encoding | **Automate** the full application → scheduling → result lifecycle |
| Weak access control; anyone with the file can edit | **Role-based access** (Admin · Test Admin · Proctor · Applicant) |
| No identity check on exam day; impersonation risk | **QR-based verification** at room entry |
| No audit trail — tampering goes unnoticed | **Accountability** via activity logs & workflow transitions |
| Score release takes weeks; applicants are in the dark | **Real-time notifications** + instant result sheets |

**Speaker script:**
> "Current admission testing at ISPSC is manual, slow, and fragile. Applications are paper-based. Access is shared. Exam-day identity is verified by eyeballing an ID. There is no audit trail, and applicants wait weeks for results.
>
> SecureCAT addresses each problem directly. We automate the pipeline. We enforce role-based access. We verify identity with QR codes at room entry. Every critical action is logged. And applicants get notified the moment their status changes."

**Speaker cue:** gesture left-to-right across the two columns to show the one-to-one mapping.

---

## Slide 3 — Scope & Roles (60 sec)

**Layout:** 2×2 grid — top row = scope (covers / excludes), bottom row spans full width = four role cards.

### Top row

| What SecureCAT Covers | What It Does NOT Cover |
|-----------------------|------------------------|
| Online & walk-in application management | Online examination delivery |
| Exam scheduling + room assignment | Payment processing |
| QR check-in & proctor attendance | Native mobile apps |
| Score encoding + result sheet release | AI-based analytics / recommendations |
| Audit logs & notification workflow | — |

### Bottom row — four role cards

| Role | What they do |
|------|--------------|
| **Administrator** | Approves applicants, configures schedules, releases results |
| **Test Administrator** | Manages exam sessions, assigns proctors, publishes rooms |
| **Proctor** | Scans QR codes, marks attendance, runs exam-day workflow |
| **Applicant** | Applies online, tracks status, views results, chats with AI Companion |

**Speaker script:**
> "Our scope stays faithful to the approved proposal. We cover the full administrative pipeline — application, scheduling, QR verification, scoring, and release. We deliberately exclude online exam delivery, payments, native mobile apps, and advanced analytics; those belong to a future milestone.
>
> Four roles operate the system. The Administrator owns configuration and approvals. The Test Administrator runs exam sessions day-to-day. The Proctor handles the exam room. The Applicant self-serves — and gets help from our AI Companion when stuck."

**Speaker cue:** pause after each role name — the panel needs to map role to responsibility.

---

## Slide 4 — Features & Tech Stack (70 sec)

**Layout:** Split card — **top half** shows six security/UX features (what the system *does*), **bottom half** shows the actual tech stack (what it's *built with*). This fixes the old slide where features and tech were mixed.

### Top — Feature Pillars

| Pillar | Implementation |
|--------|---------------|
| **RBAC** | Laravel policies · 4 roles · route-level gates |
| **QR Identity** | Per-applicant QR · proctor scanner · attendance lock |
| **Workflow Engine** | Scheduled → Active → Completed transitions with state guards |
| **Notifications** | Toast + two-tier Web Audio · role-filtered · mobile dropdown |
| **AI Companion** | RAG chat widget for applicants · knowledge-grounded answers |
| **Audit Trail** | Per-action logs · immutable history for non-repudiation |

### Bottom — Tech Stack

| Layer | Choice |
|-------|--------|
| Backend | **Laravel 12** (PHP 8.2) — Eloquent, policies, scheduled commands |
| Frontend | **Svelte 5 + Inertia v2** — SPA UX, server-driven routing |
| Styling | **Tailwind CSS v4** |
| Data | **MySQL** — migrations, seeders, demo dataset |
| Tooling | Laravel Boost · Pint · PHPUnit · Vite |

**Speaker script:**
> "The top half of this slide is what the system delivers — six pillars that together realize the IAS principles we promised. Role-based access, QR identity, a workflow state machine, real-time notifications with a two-tier sound system, an AI Companion for applicants, and a full audit trail for non-repudiation.
>
> The bottom half is how we built it: Laravel 12 on the backend with policy-based authorization, Svelte 5 with Inertia for a single-page experience, Tailwind v4 for the design system, and MySQL for persistence. All battle-tested, production-grade, fully within the proposal's technology constraints."

**Speaker cue:** if running long, skip reading the tech stack aloud — the panel can see it. Just say *"standard Laravel/Svelte/MySQL stack"* and move on.

---

## Live Demo (5 min 30 sec)

Hand off from slides → demo window. See `Demo-template.md` for the minute-by-minute script.

High-level beats:
```
A. Admin Login + Dashboard         → 0:45
B. Exam Session Workflow           → 1:30
   · Create session, publish
   · Proctor receives notification
   · Session starts → completes
C. Applicant Portal + AI Companion → 1:30
D. Grading + Result Sheet          → 1:15
E. Audit Log quick peek            → 0:30
```

---

## Closing (30 sec)

**Script:**
> "SecureCAT-v2 modernizes ISPSC's admission testing without abandoning the institutional process our proposal committed to. Security is not bolted on — it is designed in from role boundaries to the audit log. We're ready for your questions."

---

## Backup Plan (if demo fails)

30-second fallback — jump to static screenshots:
1. **Admin dashboard** — KPI cards, sidebar
2. **Exam Session page** — workflow transition buttons
3. **AI Companion** — expanded chat panel
4. **Result sheet** — generated PDF

Narrate the same beats from Demo-template.md over the screenshots.

---

*HTML render:* `presentation-securecat.html` — keyboard: ↑ ↓ or Space · 4 slides.
