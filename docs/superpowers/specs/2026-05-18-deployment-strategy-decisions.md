# 🏔️ The Final Boss: Deployment, AI, Licensing & Infrastructure Strategy

**Date:** 2026-05-18  
**Status:** Decisions locked — reference document for future implementation phases  
**Context:** Strategic architecture decisions for SecureCAT distribution, licensing, and AI infrastructure

---

## Confirmed Decisions

| # | Decision | Choice | Rationale |
|---|----------|--------|-----------|
| 1 | Primary deployment | **Laragon** (Windows) | Legacy school hardware, 2GB RAM minimum, no Docker/WSL2 needed |
| 2 | AI hosting model | **Central Server** | Ollama on school PCs is unworkable (4GB RAM can't load 4.7GB model) |
| 3 | Vector storage | **pgvector** (local Postgres via Laragon) | Proper vector DB, Laragon supports Postgres natively |
| 4 | Embedding provider | **Mixedbread** (via central server proxy) | Cheap, reliable, no GPU needed on central server |
| 5 | LLM provider | **OpenRouter** (via central server proxy) | Cheap, multi-model, no GPU on server |
| 6 | Anti-piracy | **Server-side enforcement** | Premium features literally require central server; obfuscation is futile in PHP |
| 7 | Email service | **Brevo** (transactional email API) | Drop custom SMTP relay / Mailpit forwarding; Brevo handles everything |
| 8 | Installer format | **Both PS1 + Inno Setup .exe** | PS1 for dev/IT staff, .exe for non-technical school staff |

---

## 1. Deployment Model

### Primary: Laragon on Windows (Confirmed ✅)

- **Target:** Philippine schools, budget-constrained, possibly legacy Windows PCs
- **Why not Docker:** Docker Desktop needs Windows 10+ with WSL2/Hyper-V and 4GB+ RAM — non-starter for legacy machines
- **Install path:** `install.ps1` → `securecat:install` artisan command → Inno Setup `.exe` (already spec'd in `docs/deployment/installation-pathway-spec.md`)
- **Stack on client:** PHP 8.4 + MySQL + Postgres (pgvector) + Node.js, all via Laragon

### Future paths (not now):
```
Phase 1 (Now):     Laragon + Inno Setup (.exe) for Windows schools
Phase 2 (Later):   Docker Compose for schools with Linux servers
Phase 3 (Future):  Cloud-hosted SaaS option (you host everything)
```

---

## 2. AI Architecture: Central Server Model

### Why Not Ollama on Client

- `mxbai-embed-large` = ~670MB model download
- `llama3` 8B = ~4.7GB model download
- CPU-only inference = 30-60 seconds per response
- 4GB RAM school PCs can't even load the model
- School IT managing Ollama = never happening

### Architecture

```
┌─────────────────────────────────┐
│  School PC (Laragon)            │
│  ┌───────────────────────────┐  │
│  │  SecureCAT Application    │  │
│  │  - MySQL (app data)       │  │
│  │  - Postgres/pgvector      │  │
│  │    (cached embeddings)    │  │
│  │  - Knowledge docs (text)  │  │
│  │  - All exam management    │  │
│  └───────┬───────────────────┘  │
│          │ HTTPS                │
└──────────┼──────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────┐
│  api.securecat.ph (Central Server)          │
│  ┌────────────────────────────────────────┐ │
│  │  License Validation API               │ │
│  │  Embedding API (→ Mixedbread)         │ │
│  │  LLM Chat API (→ OpenRouter)          │ │
│  │  Admin Dashboard (license mgmt)       │ │
│  └────────────────────────────────────────┘ │
│                                             │
│  Email: Brevo API (not self-hosted SMTP)    │
│  Portal: Cloudflare tunnels (Premium)       │
└─────────────────────────────────────────────┘
```

### Knowledge Documents Flow

```
Admin uploads doc → Save to local MySQL (text content)
                  → Send text to api.securecat.ph/v1/embed
                  → Central server computes embedding via Mixedbread
                  → Returns vector (1024 dimensions)
                  → Client stores vector in local pgvector
                  → Semantic search runs LOCALLY (offline capable!)
```

**Key:** Embeddings are computed once per doc. Vectors cached locally. Only ingestion needs internet. Search works offline.

### AI Companion Flow

```
Applicant asks question → Local pgvector similarity search (top-3 chunks)
                        → Send context + question to api.securecat.ph/v1/chat
                        → Server proxies to OpenRouter
                        → Returns response → display to applicant
```

**Requires internet for every chat.** But AI Companion is Premium-only, tied to public portal which already requires internet.

### Offline Fallback

- MySQL fulltext search (already built in `KnowledgeRetrievalService::fallbackMysqlRetrieval`)
- AI Companion disabled (no LLM without server)
- All core exam management works perfectly offline
- Knowledge docs are viewable, just not AI-searchable

---

## 3. Central Server: Hosting & Technology Analysis

### The Vercel Problem

> [!CAUTION]
> **Vercel Hobby (free) plan prohibits commercial use.** SecureCAT is commercial software — using Vercel free to run the licensing server would violate their ToS and risk being shut down without notice.

| Vercel Tier | Cost | Commercial? | Function Timeout | Notes |
|-------------|------|-------------|-----------------|-------|
| Hobby (free) | $0/mo | ❌ **Prohibited** | 60s | Personal/non-commercial only |
| Pro | $20/mo per seat | ✅ Yes | 300s | Commercial allowed, team features |

**Verdict:** Vercel is only viable at Pro ($20/mo+). Not free.

### Hosting Comparison Matrix

| Platform | Cost | PHP/Laravel? | Node/Next.js? | Postgres? | Always-on? | Commercial? | Closest to PH |
|----------|------|-------------|---------------|-----------|------------|-------------|---------------|
| **Agila Hosting** | **~$1.30/mo** | ✅ (shared) | ❌ | ❌ (MySQL only) | ✅ | ✅ | 🇵🇭 Philippines |
| **Railway** | $5/mo (Hobby) | ✅ | ✅ | ✅ (included) | ✅ | ✅ | US/EU |
| **Render** | $7/mo (Starter) | ❌ | ✅ | ✅ ($7/mo extra) | ✅ (paid) | ✅ | US/EU (Singapore) |
| **Hetzner VPS** | ~€5-7/mo | ✅ (full control) | ✅ | ✅ | ✅ | ✅ | 🇸🇬 Singapore |
| **Vercel Pro** | $20/mo | ❌ | ✅ | ✅ (Neon) | Serverless | ✅ | Global CDN |
| **DigitalOcean** | $6/mo (droplet) | ✅ | ✅ | ✅ | ✅ | ✅ | 🇸🇬 Singapore |
| **Fly.io** | ~$3-5/mo | ✅ (Docker) | ✅ | ✅ | ✅ | ✅ | 🇸🇬 Singapore |

### Language/Framework Decision

The central server needs to:
1. Serve license validation API
2. Proxy embeddings to Mixedbread
3. Proxy LLM to OpenRouter
4. Call Brevo API for email
5. Admin dashboard for license management
6. Postgres database for licenses, usage tracking

| Option | Pros | Cons |
|--------|------|------|
| **Laravel (PHP)** | Same language as SecureCAT, deep expertise, Eloquent, code sharing possible | Limited serverless hosting, Railway/VPS required |
| **Next.js (Node/TS)** | Vercel Pro deployment trivial, API routes + admin UI built-in, edge functions | $20/mo minimum, user needs to learn React for admin pages |
| **Express/Hono (Node)** | Lightweight, runs anywhere (Railway, Render, VPS), user knows JS from Svelte | No built-in UI framework for admin dashboard |
| **AdonisJS (Node)** | Laravel-like patterns but in Node/TS, familiar paradigm | Smaller ecosystem, less community support |

### Recommendation: Phased Approach

```
Start (development):   Laravel on Agila Hosting (~$1.30/mo)
                       or Railway ($5/mo) if Agila can't do Postgres

Scale (10+ clients):   Hetzner VPS Singapore (~€5-7/mo)
                       Full control, Postgres, better latency for PH

Future (50+ clients):  Vercel Pro ($20/mo) or dedicated VPS
                       Global CDN, better uptime guarantees
```

> [!IMPORTANT]
> **Hosting decision is deferred** — depends on whether central server is Laravel (→ Agila/Railway/VPS) or Next.js (→ Vercel Pro). The language choice drives the hosting choice.
>
> **Leaning:** Laravel on Agila/Railway is cheapest and leverages existing expertise. Can always migrate later.

### Domain Strategy

```
securecat.ph (buy this domain)
├── www.securecat.ph           → Marketing website
├── api.securecat.ph           → Central server API (license, AI proxy)
├── {school}.securecat.ph      → Cloudflare tunnel → school's local machine (Premium)
│   e.g., sti-manila.securecat.ph → portal on STI Manila's Laragon
│   e.g., up-cebu.securecat.ph   → portal on UP Cebu's Laragon
└── admin.securecat.ph         → Central server admin dashboard (your eyes only)
```

**Subdomain model = selling point:** Premium clients get `{school-name}.securecat.ph` as their public portal URL. Professional branding for the school, managed by you via Cloudflare.

---

## 4. Email Strategy: Brevo (Confirmed ✅)

### What Changed

- **Drop:** Custom SMTP relay server (Mailu/Postal) — too complex, separate infrastructure to maintain
- **Drop:** Mailpit forwarding — dev-only tool, not production
- **Use:** Brevo transactional email API for all outbound email

### How It Works

```
SecureCAT Client → api.securecat.ph/v1/email/send → Brevo API → Recipient
                   (license key validates permission)
```

- **All tiers:** Staff email works with client's own SMTP (Gmail, Outlook, etc.)
- **Premium:** Applicant email routed through central server → Brevo API
- **From address:** `noreply@{school}.securecat.ph` (SPF/DKIM on securecat.ph domain)

### Brevo Pricing

| Tier | Cost | Volume | Notes |
|------|------|--------|-------|
| Free | $0/mo | 300 emails/day | Sufficient for early clients |
| Starter | $9/mo | 5,000 emails/mo | ~10 schools |
| Business | $18/mo | 10,000 emails/mo | ~25 schools |

### Revised Email Tiering

| Email Type | Trial | Standard | Premium |
|------------|-------|----------|---------|
| Staff email (own SMTP) | ✅ | ✅ | ✅ |
| Applicant setup email | 30 days¹ | ❌ | ✅ via Brevo |
| Exam reminders | 30 days¹ | ❌ | ✅ via Brevo |
| Result notifications | 30 days¹ | ❌ | ✅ via Brevo |
| **Staff invitation email** | ✅² | ✅² | ✅ via Brevo |

¹ Trial limits, non-renewable  
² Uses client's own SMTP configuration

---

## 5. Account System Enhancements (Document for Future Phase)

### 5.1 Dual Login: Username OR Email

**Current:** Login by username only.  
**Target:** Login with either username or email.

```
Login form:
┌──────────────────────────────┐
│  Username or Email           │
│  ┌────────────────────────┐  │
│  │ juan.delacruz          │  │  ← accepts username OR email
│  └────────────────────────┘  │
│  Password                    │
│  ┌────────────────────────┐  │
│  │ ••••••••               │  │
│  └────────────────────────┘  │
│                              │
│  [Sign In]                   │
└──────────────────────────────┘
```

**Backend logic:**
```php
// Attempt login by email first, then username
$field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
Auth::attempt([$field => $request->login, 'password' => $request->password]);
```

### 5.2 Staff Account Creation with Email Invitation

**Current:** Admin creates user → gives credentials manually.  
**Target:** Admin creates user → system emails credentials + setup link.

#### Flow A: Account with email (preferred)

```
Admin creates staff user
  → Fills: name, username, email, role, temp password
  → System saves user (status: pending_setup)
  → System sends email via Brevo (or client's own SMTP):
      Subject: "Your SecureCAT Account"
      Body:
        "Hi {name},
         Your account has been created.
         Username: {username}
         Temporary password: {temp_password}

         Click here to complete setup: {setup_link}
         This link expires in 72 hours."
  → User clicks link → redirected to Setup page
```

#### Flow B: Account without email (manual/offline)

```
Admin creates staff user
  → Fills: name, username, role, password
  → No email field → no email sent
  → Admin gives credentials verbally/on paper
  → User logs in → forced to change password on first login
```

### 5.3 First-Time Account Setup Page

When a user clicks the setup link OR logs in for the first time:

```
┌──────────────────────────────────────────┐
│  Welcome to SecureCAT!                   │
│  Complete your account setup             │
│                                          │
│  Full Name                               │
│  ┌────────────────────────────────────┐  │
│  │ Juan Dela Cruz                     │  │  ← pre-filled, editable
│  └────────────────────────────────────┘  │
│                                          │
│  New Password                            │
│  ┌────────────────────────────────────┐  │
│  │ ••••••••                           │  │
│  └────────────────────────────────────┘  │
│                                          │
│  Confirm Password                        │
│  ┌────────────────────────────────────┐  │
│  │ ••••••••                           │  │
│  └────────────────────────────────────┘  │
│                                          │
│  [Complete Setup]                        │
└──────────────────────────────────────────┘
```

**Rules:**
- Password change is **mandatory** (cannot skip)
- Name correction is **optional** (pre-filled from admin input, editable)
- After completing setup, user.status changes from `pending_setup` → `active`
- User is redirected to their role-appropriate dashboard
- Setup link becomes invalid after use (one-time token)
- Force password change flag (`must_change_password`) for Flow B users on first login

### 5.4 Database Changes Needed

```
users table additions:
  - email            VARCHAR(255) NULLABLE UNIQUE  (already exists?)
  - email_verified_at TIMESTAMP NULLABLE           (already exists via Laravel)
  - must_change_password BOOLEAN DEFAULT false
  - setup_token      VARCHAR(64) NULLABLE
  - setup_token_expires_at TIMESTAMP NULLABLE
  - status           ENUM('pending_setup', 'active', 'suspended') DEFAULT 'active'
```

---

## 6. Revised Tier Matrix (Final)

| Feature | Trial (free) | Standard ($) | Premium ($$) |
|---------|-------------|-------------|-------------|
| Local exam management | ✅ | ✅ | ✅ |
| Knowledge base (text, keyword search) | ✅ | ✅ | ✅ |
| Knowledge base (AI semantic search) | 50 searches¹ | ❌ | ✅ via central server |
| Staff email (own SMTP) | ✅ | ✅ | ✅ |
| AI Companion | 50 chats¹ | ❌ | ✅ via central server |
| AI Scheduling Assistant | 10 uses¹ | ❌ | ✅ via central server |
| Public portal (subdomain) | 30 days¹ | ❌ | ✅ `{school}.securecat.ph` |
| Applicant email (via Brevo) | 30 days¹ | ❌ | ✅ |
| Staff invitation email | ✅ (own SMTP) | ✅ (own SMTP) | ✅ (via Brevo) |
| Updates | Manual | Community | Priority + auto |
| Support | Community | Community | Dedicated |
| Training | Basic docs | Basic docs | Full package |

¹ Trial limits are one-time, non-renewable.

---

## 7. Anti-Counterfeit Strategy

### The Moat = Central Server

```
┌─────────────────────────────────────────────────────┐
│  PROTECTION LAYERS                                  │
│                                                     │
│  Layer 1: Hardware Fingerprint                      │
│  → MAC + hostname + install path hash               │
│  → Can't reuse key on different hardware            │
│                                                     │
│  Layer 2: Server-Side Enforcement (THE MOAT)        │
│  → AI, semantic search, portal, email               │
│  → These ONLY work via central server API           │
│  → No server access = feature doesn't exist         │
│  → Un-bypassable — value is server-side             │
│                                                     │
│  Layer 3: HMAC Response Signing                     │
│  → Per-installation shared secret                   │
│  → Can't forge/replay license responses             │
│                                                     │
│  Layer 4: Graceful Degradation                      │
│  → Pirated copy = Standard tier (still usable)      │
│  → Core exam management always works                │
│  → Free marketing — when they want AI, they pay     │
└─────────────────────────────────────────────────────┘
```

**Philosophy:** Make it cheaper to buy than to hack. A "pirated" copy is just a Standard-tier copy with no AI, no portal, no email — functional but limited. The Figma/Notion model.

---

## 8. Central Server API Spec (Revised)

### Base URL: `https://api.securecat.ph/v1`

```
POST /v1/license/validate     → Validate license key + installation_id
POST /v1/license/usage        → Track trial usage (idempotent)
POST /v1/embed                → Compute embeddings (→ Mixedbread)
POST /v1/chat                 → LLM proxy (→ OpenRouter)
POST /v1/email/send           → Send email via Brevo (Premium)
GET  /v1/health               → Server health check
```

### Cost Model (Your Operational Costs)

| Service | Cost per unit | 100 schools/mo estimate |
|---------|--------------|------------------------|
| Mixedbread embeddings | ~$0.0001/1K tokens | ~$0.50 |
| OpenRouter (Gemini Flash) | ~$0.075/1M tokens | ~$23 |
| Brevo email | ~$0.001/email | ~$5 |
| Hosting (Agila/Railway) | $1.30-5/mo flat | $5 |
| **Total** | — | **~$33/mo for 100 schools** |

---

## 9. Implementation Phases (Roadmap)

| Phase | What | Effort | Dependencies |
|-------|------|--------|-------------|
| **A** | Client-side licensing infrastructure | ~1 week | None |
| | `LicenseService`, `RequireFeature`, `RestrictPublicDomain`, migrations, feature flags, `securecat:install`, `securecat:status` | | |
| **B** | LLM + Embedding abstraction | ~3-4 days | Phase A |
| | `LlmService` (wrap OpenRouter), `EmbeddingService` (wrap Mixedbread), pgvector column on `knowledge_documents`, local cosine search | | |
| **C** | Central Server (separate Laravel/Next.js app) | ~2-3 weeks | Phase A |
| | License CRUD, `/v1/validate`, `/v1/embed`, `/v1/chat`, `/v1/email/send`, admin dashboard, Brevo integration, deploy to hosting | | |
| **D** | Account system enhancements | ~3-4 days | None (independent) |
| | Dual login (username/email), email invitation flow, first-time setup page, `must_change_password` flag | | |
| **E** | Installer + distribution | ~3-5 days | Phases A, B |
| | `install.ps1`, `setup-securecat.bat`, Inno Setup `.exe`, Postgres setup in installer, `INSTALL.md` | | |
| **F** | Portal + Cloudflare (Premium) | ~1 week | Phase C |
| | Cloudflare tunnel config, portal route segregation, subdomain setup docs, Premium onboarding guide | | |

---

## 10. Open Items (Deferred, Not Forgotten)

| Item | Status | Notes |
|------|--------|-------|
| Central server language (Laravel vs Next.js) | **Deferred** | Depends on hosting choice; leaning Laravel for expertise reuse |
| Central server hosting | **Deferred** | Agila ($1.30/mo) if Laravel; Railway ($5/mo) for more features; Vercel Pro ($20/mo) if Next.js |
| Docker deployment path | **Future** | For schools with Linux servers |
| Cloud SaaS option | **Future** | You host everything; schools just log in |
| Ollama as offline LLM option | **Killed** | Not viable for school hardware |
| Custom SMTP relay (Mailu/Postal) | **Killed** | Replaced by Brevo API |
| Mailpit forwarding | **Killed** | Dev-only, not production |
| Auto-update mechanism | **Future** | `git pull` + `composer install` for now |
| SMTP credential rotation | **N/A** | No longer needed (Brevo API, not SMTP relay) |

---

## 11. Risk Analysis

| Risk | Impact | Mitigation |
|------|--------|------------|
| School has no internet | AI features unusable | Core exam management works fully offline; AI is a Premium perk |
| Central server goes down | All Premium features break | 7-14 day grace period; cached pgvectors still work for local search |
| Mixedbread API changes/dies | Embeddings break | `EmbeddingService` abstraction allows provider swap |
| OpenRouter pricing spikes | Margins shrink | `LlmService` abstraction; switch to Gemini direct API |
| Brevo changes pricing | Email costs rise | Standard email abstraction; swap to Resend/Postmark |
| Client modifies PHP source | Bypasses license checks | Irrelevant — Premium features require central server |
| Legacy hardware can't run Postgres | pgvector unavailable | Installer checks prerequisites; Postgres runs fine on 2GB RAM |
| Agila hosting too limited | Central server can't scale | Migrate to Railway/Hetzner when revenue justifies it |

---

## 12. Cross-Reference: Installation Pathway Spec

**Source:** [`docs/deployment/installation-pathway-spec.md`](../../deployment/installation-pathway-spec.md)

The installation pathway spec was written before several key decisions in this document. Below is a reconciliation of what's **still valid**, what's **outdated**, and what's **additive**.

### Still Valid ✅

| Item | Notes |
|------|-------|
| `install.ps1` PowerShell bootstrap script | Core install flow unchanged |
| `setup-securecat.bat` double-click wrapper | Still needed for non-technical staff |
| `securecat:install` artisan command | Core structure valid; needs license flow updates |
| `securecat:status` diagnostic command | Still needed; add pgvector + Brevo checks |
| Inno Setup `.exe` phase (Phase 2 of that spec) | Still the plan for distribution |
| Prerequisites check (PHP, Composer, Node) | Still needed |
| Hardware fingerprint (`installation_id`) | Still used; fingerprint formula updated in licensing spec |
| `INSTALL.md` root-level quick start | Still needed |
| Chrome PDF dependency (spatie/laravel-pdf) | Still valid |
| Background task scheduler (Windows Task Scheduler) | Still valid, critical for auto-close |
| `setup-scheduler.bat` | Still needed |

### Outdated / Superseded ⚠️

| Item | Old | New (this document) |
|------|-----|-----|
| Ollama installation in `install.ps1` | Steps 2 installs Ollama + pulls models | **Remove entirely** — AI runs via central server, not locally |
| `--SkipOllama` / `--SkipModels` flags | Existed for optional Ollama skip | **Remove** — no Ollama at all |
| Ollama connectivity check in `securecat:install` | Step 5 checks `localhost:11434` | **Replace** with central server connectivity check (`api.securecat.ph/v1/health`) |
| PostgreSQL as sole DB mention | Listed PostgreSQL as only DB | **Dual DB**: MySQL (app data) + Postgres (pgvector only) |
| `mxbai-embed-large` model pull | Pulled during install | **Remove** — embeddings via central server |
| `llama3` model pull | Pulled during install | **Remove** — LLM via central server |
| `.env` Ollama config | `LLM_DRIVER=ollama` | Change to `LLM_DRIVER=openrouter` (via central server proxy) |
| Installer size estimate (470MB) | Included Ollama (~100MB) + models (~4GB post-install) | **Smaller** — no Ollama bundle, ~370MB |

### Additive (New in This Document)

| Item | What to Add to Installer |
|------|-------------------------|
| Postgres setup | `install.ps1` must configure Postgres alongside MySQL in Laragon |
| pgvector extension | Install `pgvector` extension into Laragon's Postgres |
| Central server check | `securecat:install` should ping `api.securecat.ph/v1/health` and report status |
| Brevo SMTP test | `securecat:status` should test Brevo API connectivity (if Premium) |
| Dual DB `.env` entries | `DB_CONNECTION=mysql` + `PGVECTOR_CONNECTION=pgsql` with separate config |

> [!NOTE]
> The installation-pathway-spec.md should be **updated** (not replaced) when implementation begins. This document provides the strategic corrections; the pathway spec provides the detailed implementation patterns.

---

## 13. Future Documentation Roadmap

Documentation to produce in later phases, for the public website and enterprise deployments:

### Website Documentation (www.securecat.ph/docs)

| Document | Audience | Phase |
|----------|----------|-------|
| Getting Started Guide | School IT / Admin | Phase E |
| Installation Walkthrough (with screenshots) | Non-technical staff | Phase E |
| Feature Tour / Demo Video | Decision makers (principals, registrars) | Phase F |
| Pricing & Tier Comparison | Purchasers | Phase F |
| FAQ | All | Phase E |
| API Documentation (for integrations) | Developers | Phase C |
| Changelog / Release Notes | Existing clients | Ongoing |

### Enterprise Deployment Guide (Premium clients)

| Document | Contents |
|----------|----------|
| Premium Setup Guide | Cloudflare tunnel config, Brevo email setup, subdomain provisioning |
| Network Requirements | Firewall rules, required domains to whitelist (`api.securecat.ph`, `cdn.mixedbread.ai`, etc.) |
| Data Privacy & Compliance | Where data lives (local MySQL + Postgres), what goes to central server (embeddings, chat, license checks) |
| Backup & Recovery | MySQL/Postgres backup scripts, restore procedures |
| Upgrade Guide | How to update SecureCAT versions, migration steps |
| Troubleshooting Handbook | Common issues + resolutions (expanded from current `troubleshooting.md`) |

### Internal Documentation (your eyes only)

| Document | Contents |
|----------|----------|
| Central Server Operations | Deployment, monitoring, scaling, incident response |
| License Key Management | How to generate, revoke, transfer keys |
| Client Onboarding Checklist | Step-by-step for setting up a new school |
| Revenue & Cost Tracking | Per-client cost breakdown (AI usage, email, hosting) |

---

## 14. Legal & Software Protection Strategy

### 14.1 End-User License Agreement (EULA)

A EULA must be presented during installation (Inno Setup license page) and displayed in-app (Settings > About). Key clauses:

| Clause | Purpose |
|--------|---------|
| **Single-installation license** | One key = one school = one machine. Transferable only via deactivation + reactivation. |
| **No reverse engineering** | Prohibits decompilation, modification of licensing code, or circumvention of feature gating. |
| **No redistribution** | Cannot share installer, license key, or derivative copies. |
| **Data ownership** | School owns all their data (applicants, scores, docs). SecureCAT owns the software. |
| **Central server dependency** | Premium features require internet connectivity to `api.securecat.ph`. Clarifies this is by design, not a defect. |
| **Termination** | License can be revoked for ToS violations. 30-day grace period for data export before deactivation. |
| **Counterfeit notice** | Unauthorized copies are traceable via embedded fingerprints (see §14.3). Violation may result in legal action under Philippine IP law (RA 8293). |
| **Warranty disclaimer** | Software provided "as-is" for educational institution use. |
| **Jurisdiction** | Philippine courts, governed by Philippine law. |

### 14.2 Philippine Legal Framework

| Law | Relevance |
|-----|-----------|
| **RA 8293** (IP Code of the Philippines) | Protects software as a literary work under copyright. Unauthorized reproduction = infringement. |
| **RA 10175** (Cybercrime Prevention Act) | Computer-related offenses including unauthorized access and data interference. |
| **RA 10173** (Data Privacy Act) | Governs how applicant/student data must be handled. Relevant for compliance documentation. |

> [!TIP]
> You don't need to be bulletproof — you need to be **legally defensible**. A clear EULA + traceable fingerprints + server-side enforcement makes piracy not worth the legal risk for a school (which is a government-supervised institution).

### 14.3 Per-Copy Software Fingerprinting (Steganographic Signatures)

Each distributed copy of SecureCAT should contain a **hidden, unique identifier** embedded in the codebase. If a pirated copy surfaces, you can trace it back to the original licensee.

#### Techniques (PHP-Compatible)

**Technique 1: Invisible Code Markers**
```php
// Embed a unique hash in a comment that looks like a build ID
// Different per client installation package
// File: app/Providers/AppServiceProvider.php
// Build: a7f3c9e2-d841-4b1f-9e3a → this is actually the license fingerprint
```

**Technique 2: Whitespace Steganography**
- Inject unique patterns of spaces/tabs at end of lines in select PHP files
- Invisible to the eye, survives code reading, detectable by scanning
- Each client build has a different whitespace pattern = unique fingerprint
- Tools: encode license key hash into trailing whitespace across 10-20 files

**Technique 3: Variable Naming Fingerprint**
```php
// Internal constants with "random" names that are actually derived from the license
private const BUILD_HASH = 'a7f3c9e2'; // ← derived from license key
private const INTEGRITY_TOKEN = 'd841'; // ← second segment
```

**Technique 4: Asset Fingerprinting**
- Embed invisible data in CSS/JS build output (comments stripped in minification, but a custom marker survives)
- Or embed in a small PNG/SVG asset (steganographic pixel data)

#### Implementation Plan

```
Build Pipeline (Inno Setup / distribution):
  1. Client purchases license key: SCAT-XXXX-YYYY-ZZZZ
  2. Build script generates fingerprint: sha256(license_key + secret_salt)
  3. Fingerprint injected into 10-15 files using Techniques 1-3
  4. Installer packaged with fingerprinted source
  5. Fingerprint hash stored in central server DB (per license)
  
Forensics (if pirated copy found):
  1. Extract fingerprint markers from the pirated copy
  2. Match against central server DB
  3. Identify original licensee
  4. EULA clause enables legal action
```

> [!IMPORTANT]
> **Don't over-invest here.** The central server is your real protection. Fingerprinting is a forensic tool for AFTER piracy is discovered, not a prevention mechanism. Implement as a simple build step — not a complex anti-tamper system.

#### Priority Order

```
Must-have:  Server-side enforcement (already planned)
Should-have: EULA with counterfeit clauses
Nice-to-have: Per-copy fingerprinting (build pipeline addition)
Overkill:   Code obfuscation (ionCube etc.) — skip this entirely
```

---

## 15. Deployment Ecosystem Summary

```
                    ┌──────────────────────────┐
                    │   www.securecat.ph        │
                    │   Marketing + Docs        │
                    │   (static site / Vercel)  │
                    └──────────────────────────┘
                               │
         ┌─────────────────────┼─────────────────────┐
         │                     │                     │
         ▼                     ▼                     ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ api.securecat.ph│  │ admin.securecat  │  │{school}.securecat│
│ Central Server  │  │ License Admin   │  │ Public Portal    │
│ - License API   │  │ Dashboard       │  │ (Cloudflare →    │
│ - Embed proxy   │  │ (your eyes only)│  │  school Laragon) │
│ - LLM proxy     │  │                 │  │                  │
│ - Brevo email   │  │                 │  │                  │
│ Hosting: TBD    │  │ Same server     │  │ Client-side      │
└────────┬────────┘  └─────────────────┘  └─────────────────┘
         │
         │ validates + serves
         ▼
┌──────────────────────────────────────────────────────┐
│  School Installation (Laragon on Windows)             │
│  ┌──────────────────────────────────────────────────┐│
│  │  SecureCAT Application                          ││
│  │  ├── MySQL (app data, users, applications...)   ││
│  │  ├── Postgres + pgvector (cached embeddings)    ││
│  │  ├── LicenseService → api.securecat.ph          ││
│  │  ├── EmbeddingService → api.securecat.ph/embed  ││
│  │  ├── LlmService → api.securecat.ph/chat         ││
│  │  └── Core exam mgmt (works fully offline)       ││
│  └──────────────────────────────────────────────────┘│
│  Protected by: EULA + hardware fingerprint +         │
│  server-side enforcement + per-copy signatures       │
└──────────────────────────────────────────────────────┘
```

### Related Specifications

| Document | Status | Relationship |
|----------|--------|-------------|
| [`2026-05-12-tiered-licensing-design.md`](2026-05-12-tiered-licensing-design.md) | **Partially superseded** | AI architecture changed (central server vs local Ollama), email changed (Brevo vs custom SMTP). License validation flow, feature flags, and tier matrix still valid with adjustments. |
| [`2026-05-12-tiered-licensing-review.md`](2026-05-12-tiered-licensing-review.md) | **Still valid** | All 7 review findings were addressed in the design spec. Security recommendations (HMAC, anti-sharing) carry forward. |
| [`installation-pathway-spec.md`](../../deployment/installation-pathway-spec.md) | **Partially outdated** | See §12 above. Install flow valid; Ollama sections must be removed; Postgres/pgvector must be added. |
| [`2026-04-11-ai-companion-rag-spec.md`](2026-04-11-ai-companion-rag-spec.md) | **Partially superseded** | Mixedbread integration architecture still valid. Storage changes: vectors now in local pgvector instead of Mixedbread Stores. Retrieval: local pgvector search replaces Mixedbread search API. Fallback to MySQL fulltext unchanged. |

---

*This document captures all strategic decisions from the 2026-05-18 architecture discussion. Reference this when implementing Phases A–F. Cross-reference with the specs listed in §15 for implementation details.*
