# Consultation Feature — Design Spec
**Date:** 2026-04-06
**Status:** Approved

## Context

SecureCAT's consultation feature manages the "Release & Consultation" workflow run by test administrators. After applicants complete their exams and scores are finalized, test administrators schedule and conduct consultation sessions, then release results to applicants.

An external ML system (outside SecureCAT) handles course recommendations. SecureCAT only manages the scheduling, session tracking, and release status.

The feature is gated by the `consultation_enabled` system setting. When disabled, the sidebar link is hidden and all routes return 403.

---

## Scope

### What's included
- Consultation dashboard (pending/released applicant lists)
- Consultation day view (today's scheduled applicants + search by reference)
- Schedule management (assign finalized grading batches to consultation dates)
- Applicant detail view (domain scores + release action)
- Sidebar respects `consultation_enabled` setting

### What's explicitly excluded
- Rules/decision engine — removed (external ML owns recommendations)
- Counselor notes / recommended course fields — removed (counselor role no longer exists)

---

## Backend Cleanup

### Remove entirely
| File | Reason |
|------|--------|
| `app/Http/Controllers/Consultation/ConsultationRulesController.php` | Rules engine removed |
| `app/Models/DecisionRule.php` | Rules engine removed |
| `app/Http/Requests/StoreDecisionRuleRequest.php` | Rules engine removed |
| `app/Http/Requests/UpdateDecisionRuleRequest.php` | Rules engine removed |
| `app/Http/Requests/UpdateConsultationSummaryRequest.php` | Notes/save action removed |

### Remove from routes
- All `consultation.rules.*` routes (4 routes)
- `consultation.applicants.summary` PUT route (save notes)
- Unused `ConsultationLookupController` import alias if present (keep the route itself)

### Simplify `ConsultationApplicantController`
- Remove `update()` method (save counselor notes)
- Remove `DecisionRuleService` constructor injection
- Remove `ConsultationSummaryService` constructor injection (inject only as needed per method)
- Keep `show()` and `release()`

### Create `app/Services/ConsultationSummaryService.php`
Two methods only:
```php
public function getOrCreateForApplicant(int $applicantId): ConsultationSummary
public function release(ConsultationSummary $summary, User $user): void
```
`release()` sets `status = 'released'` and `released_at = now()`, then fires an audit log entry.

### DB tables
Leave `decision_rules`, `consultation_summaries`, and related tables in place. No new migration needed — unused tables cause no runtime harm.

---

## Sidebar Fix

In `AuthenticatedLayout.svelte`, the `canSee()` function currently only checks roles. The consultation nav item must also check `consultation_enabled` from Inertia shared props.

```js
// Before
{ href: '/consultation', label: 'Consultation', icon: MessageSquare, roles: ['super_admin', 'test_administrator'] }

// After — add featureFlag field, canSee() reads page.props.consultation_enabled
{ href: '/consultation', label: 'Consultation', icon: MessageSquare, roles: ['super_admin', 'test_administrator'], featureFlag: 'consultation_enabled' }
```

`canSee()` updated to:
```js
function canSee(requiredRoles, item) {
  if (requiredRoles.includes('*')) return true;
  if (!requiredRoles.some((r) => hasRole(r))) return false;
  if (item.featureFlag && !$page.props[item.featureFlag]) return false;
  return true;
}
```

---

## Frontend Pages

All pages use `AuthenticatedLayout`, follow existing component patterns (shadcn-svelte `Card`, `Badge`, `Button`), and receive props via Inertia.

### 1. `Consultation/Dashboard.svelte`
**Route:** `GET /consultation`  
**Props:** `applicants_pending[]`, `applicants_released[]`, `stats{pending, released, total_with_scores}`

- Stats bar: `X pending · Y released · Z total with scores`
- Two-tab layout: **Pending** | **Released**
- Pending row: name, reference, finalized date → links to `ApplicantView`
- Released row: name, reference, released date

### 2. `Consultation/ApplicantView.svelte`
**Route:** `GET /consultation/applicants/{applicant}`  
**Props:** `applicant{id,name,email,reference}`, `scores[{domain,raw,max,pct}]`, `consultation_summary{status, released_at}`

- Header: applicant name + reference
- Score breakdown table: domain | raw/max | percentage (progress bar)
- Status badge: `Pending` (amber) or `Released` (green)
- **Release** button → POST `/consultation/applicants/{id}/release`; disabled when already released
- Back link to Dashboard

### 3. `Consultation/ScheduleDay.svelte`
**Route:** `GET /consultation/schedule`  
**Props:** `batches[{id,name,exam_date,printed_count,total}]`, `applicantsByBatch{[batchId]: applicant[]}`

- List of finalized grading batches with printed count / total
- Expandable per-batch applicant list
- Date picker + **Schedule** button → POST `/consultation/schedule`
- Success/error flash from Inertia shared props

### 4. `Consultation/ConsultationDay.svelte`
**Route:** `GET /consultation/day`  
**Props:** `applicants[]`, `scheduledApplicantIds[]`

- Default view: today's scheduled applicants
- Search bar (min 2 chars, debounced) — Inertia `router.get` with `?search=` param
- Each row: name, reference, overall score % badge, link to `ApplicantView`
- Empty state when no scheduled applicants today

---

## Data Flow Summary

```
Test Administrator
  → /consultation           (Dashboard — who needs consulting)
  → /consultation/schedule  (ScheduleDay — plan who comes in on which date)
  → /consultation/day       (ConsultationDay — run the session, find applicants)
  → /consultation/applicants/{id} (ApplicantView — review scores, release)
```

---

## What Is NOT Changed
- `ConsultationController` (dashboard data)
- `ConsultationScheduleController` (schedule management)
- `ConsultationDayController` (day view)
- `ConsultationLookupController` (JSON search endpoint)
- `EnsureConsultationEnabled` middleware
- All 3 consultation migrations and DB tables
- `ConsultationSchedule`, `ConsultationSummary` models
- `consultation_enabled` in `SystemSetting` and `HandleInertiaRequests`
