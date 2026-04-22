# SecureCAT-v2 — System Defense Speaker Notes

**File map:**
- These notes → `system-defense.html` (8 slides, non-demo)
- Live demo script → `Demo-template.md` (Acts 1–9)

**Total time budget: ~17 min**
> Slide 1 Title · 0:20
> Slide 2 Background · 1:00
> Slide 3 Problem & Solution · 1:30
> Slide 4 Objectives · 1:30
> Slide 5 Scope & Target Users · 1:00
> Slide 6 Tech Stack · 1:00
> Slide 7 Demo Roadmap · 0:30 → hand off to Demo-template.md
> **Live Demo** · ~9 min (see Demo-template.md)
> Slide 8 Closing / Q&A · 0:20

---

## Slide 1 — Title · ⏱ 0:00–0:20

> "Good [morning/afternoon], panel members. I am David Datu Sarmiento, and with me is Christine Lopez. We present **SecureCAT** — a Role-Based College Admission Testing System for the Guidance and Counseling Services here at ISPSC Tagudin."

**Cue:** advance as soon as you say "ISPSC Tagudin."

---

## Slide 2 — Background · ⏱ 0:20–1:20

> "Let me give you the context first."

> "The College Admission Test — or CAT — is the gateway through which prospective students enter ISPSC Tagudin. Every applicant goes through it. The volume, the stakes, and the coordination demands make it one of the most operationally complex tasks in the Guidance Office."

> "Traditionally, this entire process runs on paper forms, manual scheduling across spreadsheets, and fragmented communication between the applicants, the proctors, and the administrators. Lost forms, delayed results, and no single source of truth."

> "SecureCAT digitizes the entire pipeline — from the moment an applicant submits their form online, through exam scheduling, real-time proctoring, score entry, and result release. Grounded in Information Assurance and Security principles."

**Cue:** advance after "IAS principles."

---

## Slide 3 — Problem & Solution · ⏱ 1:20–2:50

> "Three core problems, three direct solutions."

*Point left column:*

> "First — manual exam proctoring. It is error-prone, it is resource-intensive, and it leaves no verifiable record of who was actually in that room."

> "Second — staff are managing applicant data across disconnected systems. Spreadsheets, email threads, handwritten rosters. No unified view."

> "Third — there is no support for applicants between submission and results. They are left waiting, guessing, emailing the office for updates."

*Point right column:*

> "SecureCAT addresses each one directly. Scheduling, proctoring, and grading are automated and workflow-controlled. Every actor — registrar, proctor, applicant — works from a single unified portal with role-filtered views. And the AI Companion gives applicants 24/7 intelligent support without overloading the office staff."

**Cue:** advance on "without overloading the office staff."

---

## Slide 4 — Objectives · ⏱ 2:50–4:20

> "Our general objective is to develop SecureCAT — a role-based web application that streamlines the full CAT pipeline from registration through result release, grounded in IAS principles."

> "The specific objectives map to concrete system capabilities."

*Read through the grid, two per beat:*

> "Digitize applicant registration and automate exam scheduling with room assignment."

> "Enable real-time proctor attendance tracking and automate score computation."

> "Enforce role-based access control and deliver AI-powered applicant query support."

> "Push real-time notifications for every status change — and generate printable result sheets with counselor recommendations."

> "Each objective is implemented and testable. The live demo will demonstrate all eight."

**Cue:** advance on "all eight."

---

## Slide 5 — Scope & Target Users · ⏱ 4:20–5:20

> "Let me be clear about what SecureCAT covers and what it does not."

*Left column:*

> "We cover the full administrative CAT pipeline: scheduling, room assignment, proctor attendance tracking, score grading, result sheet generation, the AI Companion, role-based access control, and real-time notifications."

*Right column:*

> "We deliberately exclude physical exam materials handling, face-to-face interview coordination, any LMS functionality, external API integrations, payment processing, and physical infrastructure. Those are outside the approved scope of this proposal."

*Bottom bar:*

> "Four user roles operate the system: Registrar Administrator, Test Administrator, Proctor, and Applicant. Each role sees only what they need — nothing more."

**Cue:** advance on "nothing more."

---

## Slide 6 — Technology Stack · ⏱ 5:20–6:20

> "The stack is standard, production-grade, and fully within the technology constraints of our approved proposal."

*Walk the grid:*

> "**Laravel 12** on the backend — Eloquent ORM, policy-based authorization, scheduled commands."

> "**Svelte 5 with Inertia v2** on the frontend — a single-page experience with server-driven routing."

> "**RBAC** implemented through Laravel policies and route-level gates — four roles, cleanly separated."

> "The AI Companion uses **Mixedbread** for RAG embeddings and **OpenRouter** for multi-model LLM routing."

> "Development was AI-assisted using **Claude Code** with the GSD workflow — which itself demonstrates the kind of tooling that modern web development can leverage responsibly."

> "And **MySQL** for persistence — migrations, seeders, and a pre-built demo dataset."

**Cue:** advance on "demo dataset."

---

## Slide 7 — Demo Roadmap · ⏱ 6:20–6:50

> "Before I switch to the live system, here is what you are about to see."

> "Four acts, end to end."

> "Act 1: Application lifecycle — a live applicant submits, staff reviews and accepts, portal account is auto-created."

> "Act 2: Exam administration — Test Admin schedules a session, assigns the proctor and room, publishes it."

> "Act 3: Live proctoring — the proctor marks attendance in real-time on exam day."

> "Act 4: Grading and release — the registrar enters scores per aptitude area, finalizes, writes consultation summaries, and releases results. The applicant sees them immediately."

> "That is the complete lifecycle. Let me show you."

*Switch to the demo window. Follow Demo-template.md from Act 1.*

---

## Slide 8 — Closing · ⏱ after demo

*Alt+Tab back from the demo window.*

> "That was SecureCAT end-to-end — from application to result release. One system, four roles, designed with security and accountability built in from the start."

> "We built this to solve a real operational problem at a real institution, within the scope and technology constraints we committed to. We believe it is ready for pilot deployment."

> "Thank you, panel members. We welcome your questions."

---

## Common Q&A Prep

| Likely question | Short answer |
|----------------|--------------|
| "Why not include online exam delivery?" | Out of scope per approved proposal. CAT at ISPSC is a physical paper exam — delivering it online requires a separate security and infrastructure review. |
| "How does the AI Companion avoid giving wrong information?" | It is RAG-grounded — it only answers from a curated ISPSC-specific knowledge base. It cannot hallucinate admission policies it was not given. |
| "What happens if the proctor's device fails on exam day?" | Any authorized device on the LAN can open the proctor session. The attendance data persists server-side — no data lives only on the device. |
| "Can the grading be tampered with after finalization?" | No. Finalization locks the session and prevents further score edits. Every pre-finalization change is logged in the audit trail. |
| "Is this FERPA / data privacy compliant?" | The system uses role-based access, encrypted transit, and applicant data is only visible to authorized roles. Full DPA alignment is a production deployment concern covered in the pilot proposal. |

---

*Companion files:*
- `Demo-template.md` — minute-by-minute live demo script
- `system-defense.html` — the slide deck (↑↓ or Space to navigate)
- `SecureCAT-pilot-proposal.md` — approved scope document
