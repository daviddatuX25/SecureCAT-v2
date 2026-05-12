# SecureCAT Tiered Licensing — Design Spec

**Date:** 2026-05-12  
**Status:** Revised (P0+P1 review findings incorporated)  
**Project:** SecureCAT-v2  

## 1. Overview

SecureCAT-v2 is a Laravel 12 + Inertia Svelte 5 exam management application distributed for self-hosting via Laragon. This spec defines a tiered licensing system with two paid tiers (Standard, Premium) plus a usage-limited Trial, a public-facing portal segregation strategy via Cloudflare tunnels, and a dual-purpose licensing server that also provides SMTP relay.

## 2. Tiers & Features

| Feature | Trial (free) | Standard ($) | Premium ($$) |
|---------|-------------|-------------|-------------|
| Local exam management | ✅ | ✅ | ✅ |
| Knowledge base (Ollama+pgvector) | ✅ | ✅ | ✅ |
| Staff email | Own SMTP | Own SMTP | Relay included |
| AI Companion | 50 chats¹ | ❌ | ✅ (unlimited) |
| AI Scheduling Assistant | 10 uses¹ | ❌ | ✅ (unlimited) |
| Public portal (Cloudflare subdomain) | 30 days¹ | ❌ | ✅ |
| Applicant email (setup, reminders, results) | 30 days¹ | ❌ | ✅ |
| Support | Community | Community | Dedicated |
| Training package | Basic docs | Basic docs | Full package |

¹ Trial limits are one-time, non-renewable.

### 2.1 Trial Lifecycle

**Activation:** Trial clock starts on first successful `LicenseService::resolve()` call (stored as `trial_activated_at` in `license_cache` table).

**Usage tracking:** Local-only for trial (no license key to auth against server). `license_usage` table tracks per-feature counts.

**Limits are independent:** Exhausting AI Companion chats does NOT affect AI Scheduling uses or portal access. Each feature has its own counter.

**Anti-reset:** `installation_id` is hardware-fingerprinted (MAC address + hostname hash), not a user-editable UUID. Reinstalling the application on the same hardware does not reset trial counters. Trial state is stored in the database, not in config files.

**Upgrade path:** Entering a valid Standard/Premium key during trial:
- Immediately applies new tier
- Trial usage counters are archived (not deleted)
- No data loss — all trial-period work is preserved

**Exhaustion UX:** When a trial limit is hit:
- Feature is disabled in UI with a specific message: "You've used all 50 AI Companion chats in your trial. Upgrade to Premium for unlimited access."
- Backend returns 403 with `error_code: trial_limit_exhausted`
- Admin dashboard shows a persistent upgrade banner

### Feature Flags

```php
// config/securecat.php
'features' => [
    'ai_companion'      => false,  // Premium/Trial
    'ai_scheduling'     => false,  // Premium/Trial
    'public_portal'     => false,  // Premium/Trial
    'applicant_email'   => false,  // Premium/Trial
    'smtp_relay'        => false,  // Premium (uses licensing server SMTP)
],
'limits' => [
    'ai_companion_chats'  => null,  // null = unlimited, int = max (trial)
    'ai_scheduling_uses'  => null,
    'trial_activated_at'  => null,  // Carbon timestamp
],
```

## 3. AI & Knowledge Base Architecture

### Embeddings: Ollama + pgvector (primary)

- Client runs Ollama locally with `mxbai-embed-large` model
- Laravel sends text to Ollama → receives embedding vector
- Vector stored in `knowledge_documents.embedding` column (pgvector)
- Semantic search via `SELECT ... ORDER BY embedding <=> $query_vector`
- Zero external dependency for embeddings — works fully offline
- Standard tier uses existing MySQL fulltext fallback (`KnowledgeRetrievalService::fallbackMysqlRetrieval`)

### LLM: Ollama default, OpenRouter optional

- `.env` controls LLM backend: `LLM_DRIVER=ollama` or `LLM_DRIVER=openrouter`
- Ollama: client runs model locally (e.g., llama3, qwen)
- OpenRouter: for clients with reliable internet who want better models
- `AiCompanionService` and `ExamSchedulingAssistantService` use a common `LlmService` that abstracts the driver

### Mixedbread (future phase)

- Mixedbread cloud integration remains as optional embedding provider
- `EmbeddingService` abstracts the driver (Ollama/Mixedbread) similar to LLM
- Not in scope for initial licensing implementation

## 4. Public-Facing Portal Segregation

### Domain-Based Routing

```php
// routes/web.php
Route::domain('portal.securecat.ph')
    ->middleware(['feature:public_portal', 'restrict.public.domain'])
    ->group(function () {
        Route::get('/apply', [ApplicationController::class, 'create']);
        Route::prefix('portal')->group(function () { ... });
    });

// Admin routes: localhost only, never tunneled
Route::middleware(['auth', 'role:super_admin,...'])->prefix('admin')->group(function () { ... });
```

### RestrictPublicDomain Middleware (Allowlist Approach)

When request arrives via public domain, ONLY routes named `portal.*` or `applications.create` are allowed. ALL other routes return 404. This is an **allowlist**, not a denylist.

When request arrives via localhost/LAN, all routes are accessible (standard auth applies).

```php
class RestrictPublicDomain {
    private const ALLOWED_ROUTE_PREFIXES = ['portal.', 'applications.'];

    public function handle($request, $next) {
        $publicDomain = config('securecat.public_domain');
        if ($request->getHost() === $publicDomain) {
            $route = $request->route();
            if ($route) {
                $name = $route->getName() ?? '';
                $allowed = collect(self::ALLOWED_ROUTE_PREFIXES)
                    ->some(fn($prefix) => str_starts_with($name, $prefix));
                if (!$allowed) {
                    abort(404);
                }
            }
        }
        return $next($request);
    }
}
```

### Trusted Proxy Configuration

Cloudflare IP ranges MUST be configured in `TrustProxies` middleware. `$request->getHost()` is only reliable when trusted proxies are set. Reference: https://developers.cloudflare.com/fundamentals/reference/cloudflare-ip-addresses/

### Cloudflare Tunnel Config (Premium clients)

```yaml
# ~/.cloudflared/config.yml
tunnel: <tunnel-id>
ingress:
  - hostname: portal.securecat.ph
    service: http://localhost:8000
  - service: http_status:404  # block everything else
```

## 5. Email Tiering

### Staff Email (both tiers)

- Admin, registrar, test administrator — always works
- Standard clients configure their own SMTP in `.env`
- Premium clients use licensing server SMTP relay

### Applicant Email (Premium only)

- Setup links, password reset, exam reminders, result notifications
- Tied to the portal feature — no portal, no applicant email
- Gated by `applicant_email` feature flag in `LicenseService`
- `SendApplicantSetupEmail` job checks `LicenseService::hasFeature('applicant_email')` before sending

### Licensing Server as SMTP Relay

- Runs Mailu or Postal (open-source mail server)
- Premium clients configure `.env`:
  ```env
  MAIL_HOST=smtp.securecat.ph
  MAIL_PORT=587
  MAIL_USERNAME=${SECURECAT_LICENSE_KEY}
  MAIL_PASSWORD=${SECURECAT_SMTP_TOKEN}
  ```
- SMTP credentials derived from license key — only Premium clients can relay
- Note: SMTP credential rotation schedule and relay outage fallback are defined in the Licensing Server phase spec (out of scope here)

## 6. License Validation Flow

### 6.1 Response Integrity

The licensing server signs all responses with HMAC-SHA256 using a per-installation shared secret. This prevents replay attacks, response forgery, and cache tampering.

- Shared secret is provisioned during initial license activation (one-time handshake)
- `LicenseService::resolve()` verifies HMAC before accepting any response
- Cached responses store the signature; re-verification on cache load
- `installation_id` is hardware-fingerprinted (MAC address + hostname hash), not a user-editable UUID

### 6.2 Anti-Sharing

- Licensing server tracks `installation_id` per key
- All tiers: max 1 active installation per license key
- Second activation from different `installation_id` → requires explicit deactivation of previous installation via licensing server admin panel

### 6.3 Validation Flow

```
App Boot
    │
    ▼
LicenseService::resolve()
    │
    ├─ Cache hit? ──YES──▶ Verify HMAC → Return cached tier+features
    │
    ├─ Cache miss ──▶ Call licensing server
    │   │
    │   ├─ Server reachable ──▶ Validate key
    │   │   ├─ Valid ──▶ Verify HMAC, cache result (24h), warm config
    │   │   ├─ Invalid ──▶ Degrade to Standard, cache denial (1h)
    │   │   └─ Trial expired ──▶ Degrade to Standard, clear trial features
    │   │
    │   └─ Server unreachable ──▶ Check DB fallback
    │       ├─ Last validation < 168h (7d) ago ──▶ Use last known tier+features
    │       ├─ Last validation 168-336h (7-14d) ago ──▶ Premium with warning banner
    │       └─ Last validation > 336h (14d) ago ──▶ Degrade to Standard
    │
    ▼
Config warmed with features
    │
    ▼
RequireFeature middleware gates routes
HandleInertiaRequests shares $page.props.license
```

### 6.4 Degradation Policy

| Scenario | Result | Rationale |
|----------|--------|-----------|
| Invalid/unknown key | Standard | Default safe state |
| Trial expired | Standard | Expected — they chose not to buy |
| Premium key, server unreachable < 7d | Last known Premium | Grace period for intermittent connectivity |
| Premium key, server unreachable 7-14d | Premium + warning banner | Extended grace, notify admin |
| Premium key, server unreachable > 14d | Standard + persistent notification | Protect revenue, avoid permanent lockout |
| Premium key expired | Standard + 30-day wind-down | Allow data export and transition |

### 6.5 Cache Strategy

| Scenario | TTL | Behavior |
|----------|-----|----------|
| Valid license | 24h | Cache in config + DB, full features |
| Invalid key | 1h | Short TTL to prevent server hammering |
| Server unreachable, last check < 7d | Until server reachable | Use last known state |
| Server unreachable, last check 7-14d | Until server reachable | Use last known state + show warning |
| Server unreachable, last check > 14d | Until server reachable | Degrade to Standard |

### Config Precedence Chain

```
Licensing server response > .env overrides > config/securecat.php defaults
```

### Trial Usage Tracking

- `license_usage` table tracks AI interactions per installation
- Incremented on each AI Companion chat and Scheduling Assistant use
- Checked before each AI call: `LicenseService::hasUsageRemaining('ai_companion')`
- When trial limits exhausted, feature flags flipped off, notification shown in UI

## 7. Licensing Server API (v1)

### Base URL: `https://api.securecat.ph/v1`
### Transport: HTTPS required (TLS 1.2+)
### Authentication: `Authorization: Bearer {SECURECAT_LICENSE_KEY}` header
### Rate Limit: 60 requests/hour per installation_id

### POST /v1/license/validate

**Request:**
```json
{
  "installation_id": "hw-fingerprint-hash",
  "domain": "portal.securecat.ph",
  "version": "1.0.0"
}
```

Headers:
- `Authorization: Bearer SCAT-XXXX-XXXX-XXXX`

**Response (valid):**
```json
{
  "valid": true,
  "tier": "premium",
  "features": ["ai_companion", "ai_scheduling", "public_portal", "applicant_email", "smtp_relay"],
  "limits": { "ai_companion_chats": null, "ai_scheduling_uses": null },
  "expires_at": "2027-05-12T00:00:00Z",
  "smtp": {
    "host": "smtp.securecat.ph",
    "port": 587,
    "username": "license-key-derived",
    "password": "rotating-token"
  },
  "signature": "hmac-sha256-hex"
}
```

**Error Response Schema (all endpoints):**

| HTTP Status | `error_code` | Meaning |
|-------------|-------------|---------|
| 401 | `invalid_key` | License key not recognized |
| 403 | `key_expired` | License key has expired |
| 403 | `domain_mismatch` | Registered domain doesn't match request |
| 403 | `installation_conflict` | Key active on different installation |
| 429 | `rate_limited` | Too many requests |
| 500 | `server_error` | Internal licensing server error |

Error response body:
```json
{
  "valid": false,
  "tier": "standard",
  "features": [],
  "error_code": "key_expired",
  "signature": "hmac-sha256-hex"
}
```

### POST /v1/license/usage

**Request:**
```json
{
  "installation_id": "hw-fingerprint-hash",
  "feature": "ai_companion",
  "increment": 1
}
```

Headers:
- `Authorization: Bearer SCAT-XXXX-XXXX-XXXX`
- `Idempotency-Key: {uuid}` (required — prevents double-counting on retry)

**Response:**
```json
{
  "allowed": true,
  "remaining": 42,
  "signature": "hmac-sha256-hex"
}
```

## 8. Implementation — New Files

| File | Purpose |
|------|---------|
| `config/securecat.php` | Feature flag defaults, license config, public domain |
| `app/Services/LicenseService.php` | Validates license, caches, resolves features, HMAC verification |
| `app/Services/LlmService.php` | Abstracts LLM driver (Ollama/OpenRouter) |
| `app/Http/Middleware/RequireFeature.php` | Route-level feature gating |
| `app/Http/Middleware/RestrictPublicDomain.php` | Allowlist-based portal route restriction |
| `database/migrations/*_create_license_cache_table.php` | Cache table for license validation results |
| `database/migrations/*_create_license_usage_table.php` | Usage tracking for trial limits |
| `app/Models/LicenseCache.php` | Eloquent model for license cache |
| `app/Models/LicenseUsage.php` | Eloquent model for usage tracking |

### license_cache table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| installation_id | string | Unique, hardware-fingerprinted |
| license_key | string | Encrypted at rest |
| tier | enum | trial, standard, premium |
| features | json | Feature flag array |
| limits | json | Usage limits |
| response_signature | string | HMAC for integrity verification |
| validated_at | timestamp | Last successful validation |
| trial_activated_at | timestamp | When trial started |
| expires_at | timestamp | License expiry |
| created_at / updated_at | timestamps | Standard |

### license_usage table

| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| installation_id | string | FK to license_cache |
| feature | string | e.g., ai_companion |
| usage_count | integer | Monotonic counter |
| last_used_at | timestamp | |
| created_at / updated_at | timestamps | Standard |

## 9. Implementation — Modified Files

| File | Change |
|------|--------|
| `bootstrap/app.php` | Register RequireFeature + RestrictPublicDomain middleware |
| `routes/web.php` | Domain-based route groups, feature middleware on AI/portal routes |
| `app/Http/Middleware/HandleInertiaRequests.php` | Share `$page.props.license` (tier, features, limits) to Svelte |
| `app/Providers/AppServiceProvider.php` | Register LicenseService as singleton |
| `app/Services/AiCompanionService.php` | Use LlmService instead of direct OpenRouter calls |
| `app/Services/ExamSchedulingAssistantService.php` | Use LlmService instead of direct OpenRouter calls |
| `app/Services/MixedbreadService.php` | Refactor into EmbeddingService (Ollama/Mixedbread driver) |
| `app/Jobs/SendApplicantSetupEmail.php` | Check `applicant_email` feature flag before sending |
| `.env.example` | Add `SECURECAT_LICENSE_KEY`, `LLM_DRIVER` (installation_id is auto-generated at first boot, not user-editable) |

## 10. Feature Gating — Defense in Depth

**Layer 1 — Backend (authoritative):**
- `RequireFeature` middleware on all gated routes
- Returns 403 with `feature_unavailable` error code
- This is the ONLY layer that enforces access control

**Layer 2 — Frontend (cosmetic):**
- `$page.props.license` hides/shows UI elements
- NEVER relied upon for security — only for UX
- Mismatch between frontend and backend is always resolved by backend (e.g., user sees button, clicks it, gets 403 → UI shows upgrade prompt via Inertia error handling)

**Rule:** Every gated frontend element MUST have a corresponding backend middleware. No exceptions.

```svelte
<!-- Frontend gating example (Layer 2 only — backend is Layer 1) -->
<script>
  const license = $page.props.license; // { tier, features, limits }
</script>

{#if license.features.includes('ai_companion')}
  <a href="/portal/ai-companion">AI Companion</a>
{:else}
  <span class="text-muted">AI Companion — upgrade to Premium</span>
{/if}

{#if license.features.includes('public_portal')}
  <a href="/apply">Apply Online</a>
{:else}
  <span class="text-muted">In-person applications only</span>
{/if}
```

## 11. Observability

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

## 12. Out of Scope (Future Phases)

- Ollama + pgvector migration (replacing Mixedbread cloud embeddings)
- Mixedbread as optional embedding driver
- Licensing server application (separate Laravel app)
- Cloudflare tunnel provisioning automation
- SMTP relay server setup (Mailu/Postal)
- SMTP credential rotation + relay outage handling
- Admin UI for license status/management
- Usage analytics dashboard on licensing server
- Automatic license key generation/rotation