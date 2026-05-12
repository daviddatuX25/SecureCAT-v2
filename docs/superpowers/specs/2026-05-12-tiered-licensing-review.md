# Tiered Licensing Design — Architectural Review

**Spec reviewed:** `docs/superpowers/specs/2026-05-12-tiered-licensing-design.md`  
**Review date:** 2026-05-12  
**Verdict:** Solid foundation — needs hardening in 7 areas before it's implementation-ready.

---

## Executive Summary

The spec correctly identifies the major moving parts (tiers, feature flags, license validation, portal segregation, email tiering). The validation flow diagram and cache strategy are well thought out. However, several areas need sharpening before this is ready to hand off to an implementation phase:

| Area | Severity | Summary |
|------|----------|---------|
| [1. Security & Anti-Tamper](#1-security--anti-tamper) | 🔴 High | License response can be replayed/forged; no signing or integrity checks |
| [2. Graceful Degradation](#2-graceful-degradation-semantics) | 🟡 Medium | "Degrade to Standard" is ambiguous — Standard has no AI, but Trial does |
| [3. Licensing Server Contract](#3-licensing-server-api-contract) | 🟡 Medium | Missing versioning, rate limiting, idempotency, error schema |
| [4. Trial Lifecycle](#4-trial-lifecycle-gaps) | 🟡 Medium | No activation date, no transition path, no re-trial policy |
| [5. Domain/Portal Security](#5-domainportal-security) | 🟡 Medium | Middleware bypass vectors; missing CORS/CSP strategy |
| [6. Email Architecture](#6-email-architecture) | 🟠 Low-Med | SMTP credential rotation undefined; no fallback for relay outage |
| [7. Frontend Gating](#7-frontend-gating-defense-in-depth) | 🟠 Low | Client-side only gating; needs backend enforcement contract |

---

## 1. Security & Anti-Tamper

### Gap: License response integrity

The spec shows the licensing server returning a plain JSON response with `tier`, `features`, and `limits`. This is cached for 24h locally. There is **no mechanism** to prevent:

- **Replay attacks** — a Premium response captured once and replayed forever
- **Response forgery** — modifying the cached JSON in the `license_cache` DB table
- **Key sharing** — using one Premium key across multiple installations (only `installation_id` is sent, but it's a self-reported UUID)

### Recommendations

Add a **Section 6.1 — License Response Integrity** to the spec:

```markdown
### 6.1 Response Integrity

- Licensing server signs responses with HMAC-SHA256 using a per-installation shared secret
- Shared secret is provisioned during initial license activation (one-time handshake)
- `LicenseService::resolve()` verifies HMAC before accepting any response
- Cached responses store the signature; re-verification on cache load
- `installation_id` is hardware-fingerprinted (MAC address + hostname hash), 
  not a user-editable UUID
```

Also specify:

```markdown
### 6.2 Anti-Sharing

- Licensing server tracks `installation_id` per key
- Premium key: max 1 active installation
- Standard key: max 1 active installation
- Second activation from different installation_id → requires explicit 
  deactivation of previous installation via admin panel
```

> [!IMPORTANT]
> Without response signing, the entire licensing system is a JSON file swap away from being bypassed. This is the single highest-priority gap.

---

## 2. Graceful Degradation Semantics

### Gap: "Degrade to Standard" is ambiguous

The validation flow says:
- Invalid key → Degrade to Standard
- Trial expired → Degrade to Standard
- Server unreachable > 48h → Degrade to Standard

But the feature matrix shows **Standard has fewer features than Trial** (no AI Companion, no AI Scheduling, no public portal, no applicant email). This means:

- A **Trial user whose trial expires** loses AI features ✅ (expected)
- A **Premium user whose server is unreachable for 48h** loses ALL premium features and drops to the same level as someone who paid nothing extra ⚠️ (punitive)

### Recommendations

Add a **degradation policy matrix**:

```markdown
### Degradation Policy

| Scenario | Result | Rationale |
|----------|--------|-----------|
| Invalid/unknown key | Trial (limited) | Let them evaluate before purchase |
| Trial expired | Standard | Expected — they chose not to buy |
| Premium key, server unreachable < 48h | Last known Premium | Grace period |
| Premium key, server unreachable 48-168h | Premium with warning banner | Extended grace for connectivity issues |
| Premium key, server unreachable > 168h | Standard + persistent notification | Protect revenue without punishing intermittent connectivity |
| Premium key expired | Standard + 30-day wind-down | Allow data export and transition |
```

> [!NOTE]
> 48h is quite aggressive for a self-hosted, potentially on-prem system. Consider extending to 7 days (168h) before full degradation.

---

## 3. Licensing Server API Contract

### Gaps

The API section (§7) defines request/response shapes but is missing several standard API design concerns:

| Missing | Why it matters |
|---------|----------------|
| **API versioning** | No `v1` prefix — how will you evolve the contract without breaking existing installations? |
| **Rate limiting** | No mention of rate limits — a misbehaving client could hammer the server |
| **Idempotency** | `POST /api/license/usage` with `increment: 1` — what happens on retry after timeout? Double-counted? |
| **Error schema** | Only `reason` field on invalid — no HTTP status code contract, no error code enum |
| **Request authentication** | The license key IS the auth — no separate API key/header scheme |
| **TLS requirement** | No mention that the licensing server MUST be HTTPS |
| **Webhook/push model** | Server can only respond to polls — no mechanism to revoke a license proactively |

### Recommendations

Rewrite §7 header as:

```markdown
## 7. Licensing Server API (v1)

### Base URL: `https://api.securecat.ph/v1`
### Transport: HTTPS required (TLS 1.2+)
### Authentication: `Authorization: Bearer {license_key}` header
### Rate Limit: 60 requests/hour per installation_id
### Idempotency: Usage endpoints accept `Idempotency-Key` header
```

Add error contract:

```markdown
### Error Response Schema (all endpoints)

| HTTP Status | `error_code` | Meaning |
|-------------|-------------|---------|
| 401 | `invalid_key` | License key not recognized |
| 403 | `key_expired` | License key has expired |
| 403 | `domain_mismatch` | Registered domain doesn't match request |
| 403 | `installation_conflict` | Key active on different installation |
| 429 | `rate_limited` | Too many requests |
| 500 | `server_error` | Internal licensing server error |
```

Add idempotency to usage:

```markdown
### POST /v1/license/usage

Headers:
- `Authorization: Bearer {license_key}`
- `Idempotency-Key: {uuid}` (required — prevents double-counting on retry)
```

---

## 4. Trial Lifecycle Gaps

### Gaps

The spec defines trial limits (50 chats, 10 scheduling uses, 30-day portal) but doesn't address:

| Missing | Question |
|---------|----------|
| **Activation date** | When does the 30-day clock start? First boot? First portal access? |
| **Usage tracking locality** | Is trial usage tracked locally only, or synced to licensing server? |
| **Re-trial** | Can a user reset by reinstalling with a fresh `installation_id`? |
| **Trial-to-paid transition** | What happens to trial data (AI chats, portal applications) when they upgrade? |
| **Limit exhaustion UX** | What does the user see when they hit 50 chats? Just a flag flip, or a specific prompt? |
| **Partial exhaustion** | Can they hit 50 AI chats but still have scheduling uses left? |

### Recommendations

Add a **§2.1 Trial Lifecycle**:

```markdown
### 2.1 Trial Lifecycle

**Activation:** Trial clock starts on first successful `LicenseService::resolve()` call 
(stored as `trial_activated_at` in `license_cache` table).

**Usage tracking:** Local-only for trial (no license key to auth against server). 
`license_usage` table tracks per-feature counts.

**Limits are independent:** Exhausting AI Companion chats does NOT affect 
AI Scheduling uses or portal access. Each feature has its own counter.

**Anti-reset:** `installation_id` is hardware-fingerprinted. Reinstalling the 
application on the same hardware does not reset trial counters. 
Trial state is stored in the database, not in config files.

**Upgrade path:** Entering a valid Standard/Premium key during trial:
- Immediately applies new tier
- Trial usage counters are archived (not deleted)
- No data loss — all trial-period work is preserved

**Exhaustion UX:** When a trial limit is hit:
- Feature is disabled in UI with a specific message: 
  "You've used all 50 AI Companion chats in your trial. Upgrade to Premium for unlimited access."
- Backend returns 403 with `error_code: trial_limit_exhausted`
- Admin dashboard shows a persistent upgrade banner
```

---

## 5. Domain/Portal Security

### Gaps

The `RestrictPublicDomain` middleware is a good start but has issues:

1. **Path-only check:** `$request->is('admin/*')` — what about `/horizon`, `/telescope`, `/pulse`, or any future debug route?
2. **Allowlist vs Denylist:** The middleware denies `admin/*` but allows everything else. This is a **denylist** approach. For a public-facing surface, an **allowlist** is safer.
3. **No CORS/CSP strategy:** The public portal will be on a subdomain — cross-origin concerns are unaddressed.
4. **Host header spoofing:** `$request->getHost()` can be manipulated if the app doesn't validate `trusted_proxies` config correctly behind Cloudflare.

### Recommendations

Rewrite the middleware to use an **allowlist** approach:

```markdown
### RestrictPublicDomain Middleware (Revised)

When request arrives via public domain:
- ONLY allow routes in the `portal` and `apply` route groups
- ALL other routes return 404
- This is an allowlist, not a denylist

When request arrives via localhost/LAN:
- All routes accessible (standard auth applies)

Implementation note: Use route naming convention. Portal routes 
are named `portal.*` and `apply.*`. Middleware checks 
`$route->getName()` starts with allowed prefixes.
```

Add:

```markdown
### Trusted Proxy Configuration

- Cloudflare IP ranges MUST be configured in `TrustProxies` middleware
- `$request->getHost()` is only reliable when trusted proxies are set
- Reference: https://developers.cloudflare.com/fundamentals/reference/cloudflare-ip-addresses/
```

---

## 6. Email Architecture

### Gaps

| Missing | Question |
|---------|----------|
| **SMTP credential rotation** | Spec says "rotating-token" for SMTP password — rotation schedule? How does client pick up new creds? |
| **Relay outage** | What happens when the licensing SMTP server is down? Emails silently fail? Queue? Fallback? |
| **Sender identity** | Who is the `From:` address? `noreply@securecat.ph`? `noreply@{client-subdomain}.securecat.ph`? |
| **SPF/DKIM/DMARC** | No mention of email authentication — relay emails will be spam-binned without this |
| **Email volume limits** | No rate limits per client — a misconfigured Premium client could exhaust relay capacity |

### Recommendations

Add **§5.1 Email Operations**:

```markdown
### 5.1 SMTP Relay Operations

**Credential rotation:**
- SMTP token rotates monthly via license validation response
- `LicenseService` updates `.env` SMTP config on token change
- Old token remains valid for 48h overlap period

**Relay outage handling:**
- Emails are queued via Laravel's queue system (standard behavior)
- Failed sends retry 3 times with exponential backoff
- After 3 failures, email is marked as failed in `failed_jobs`
- Admin notification shown: "Email delivery delayed — SMTP relay unreachable"

**Sender identity:**
- From: `noreply@{institution-slug}.securecat.ph`
- Institution slug derived from license registration
- SPF, DKIM, DMARC records managed centrally on securecat.ph domain

**Volume limits:**
- Standard: N/A (own SMTP)
- Premium: 500 emails/day per installation (sufficient for admission cycles)
- Overage: emails queued, delivered next day, admin notified
```

---

## 7. Frontend Gating — Defense in Depth

### Gap: Client-side only gating

The Svelte example in §10 shows:

```svelte
{#if license.features.includes('ai_companion')}
```

This is UI-only. A user who knows the route can still hit `/portal/ai-companion` directly.

### Current mitigation

The spec mentions `RequireFeature` middleware — but doesn't explicitly connect it to the frontend gating to form a **defense-in-depth** contract.

### Recommendations

Add a clear **dual-gating contract** to §10:

```markdown
### 10. Feature Gating — Defense in Depth

**Layer 1 — Backend (authoritative):**
- `RequireFeature` middleware on all gated routes
- Returns 403 with `feature_unavailable` error code
- This is the ONLY layer that enforces access control

**Layer 2 — Frontend (cosmetic):**
- `$page.props.license` hides/shows UI elements
- NEVER relied upon for security — only for UX
- Mismatch between frontend and backend is always resolved 
  by backend (e.g., user sees button, clicks it, gets 403 → 
  UI shows upgrade prompt via Inertia error handling)

**Rule:** Every gated frontend element MUST have a corresponding 
backend middleware. No exceptions.
```

---

## 8. Additional Structural Recommendations

### 8.1 Missing: Config precedence chain

The spec uses `config/securecat.php` for defaults and `.env` for overrides, but doesn't define the **precedence chain** when the licensing server provides values:

```
Licensing server response > .env overrides > config/securecat.php defaults
```

Add this explicitly.

### 8.2 Missing: Observability

No mention of:
- **Logging** — what gets logged during validation? (important for debugging client issues)
- **Health check** — how does the client know if the licensing server is healthy vs returning bad data?
- **Metrics** — license validation success/failure rates on the server side

Add a brief **§12 Observability** section:

```markdown
## 12. Observability

| Event | Log Level | Channel |
|-------|-----------|---------|
| License validated successfully | info | license |
| License validation failed (invalid key) | warning | license |
| License server unreachable | error | license |
| Degradation triggered | warning | license |
| Trial limit exhausted | info | license |
| SMTP credential rotation | info | mail |

- All license events use a dedicated `license` log channel
- License validation failures trigger an admin notification (in-app)
```

### 8.3 Missing: Database migration design

The spec lists two migration files but doesn't define schemas. At minimum, sketch the columns:

```markdown
### license_cache table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| installation_id | string | Unique |
| license_key | string | Encrypted at rest |
| tier | enum | trial, standard, premium |
| features | json | Feature flag array |
| limits | json | Usage limits |
| response_signature | string | HMAC for integrity |
| validated_at | timestamp | Last successful validation |
| expires_at | timestamp | License expiry |
| created_at / updated_at | timestamps | Standard |

### license_usage table
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| installation_id | string | FK |
| feature | string | e.g., ai_companion |
| usage_count | integer | Monotonic counter |
| last_used_at | timestamp | |
| created_at / updated_at | timestamps | Standard |
```

### 8.4 Revised Feature Matrix

The current matrix has an asymmetry that could confuse clients: Trial gets MORE features than Standard in some areas. Consider making this explicit with a footnote, or restructuring:

```markdown
| Feature | Trial (free) | Standard ($) | Premium ($$) |
|---------|-------------|-------------|-------------|
| Local exam management | ✅ | ✅ | ✅ |
| Knowledge base (Ollama) | ✅ | ✅ | ✅ |
| Staff email | Own SMTP | Own SMTP | Relay included |
| AI Companion | 50 chats¹ | ❌ | ✅ (unlimited) |
| AI Scheduling | 10 uses¹ | ❌ | ✅ (unlimited) |
| Public portal | 30 days¹ | ❌ | ✅ |
| Applicant email | 30 days¹ | ❌ | ✅ |
| Support | Community | Community | Dedicated |
| Training | Basic docs | Basic docs | Full package |

¹ Trial limits are one-time, non-renewable.
```

---

## Prioritized Action List

| Priority | Action | Section |
|----------|--------|---------|
| 🔴 P0 | Add response signing / HMAC integrity checks | §6.1 (new) |
| 🔴 P0 | Define anti-sharing / installation binding | §6.2 (new) |
| 🟡 P1 | Switch portal middleware from denylist to allowlist | §4 |
| 🟡 P1 | Add API versioning + error schema + idempotency | §7 |
| 🟡 P1 | Define trial lifecycle (activation, anti-reset, upgrade path) | §2.1 (new) |
| 🟡 P1 | Clarify degradation policy with extended grace periods | §6 |
| 🟡 P1 | Add defense-in-depth gating contract | §10 |
| 🟠 P2 | Define SMTP rotation + relay outage handling | §5.1 (new) |
| 🟠 P2 | Add observability / logging contract | §12 (new) |
| 🟠 P2 | Sketch migration schemas | §8 |
| 🟠 P2 | Add config precedence chain | §6 |
| ⚪ P3 | Add trusted proxy config for Cloudflare | §4 |
| ⚪ P3 | Clarify Standard vs Trial marketing positioning | §2 |
