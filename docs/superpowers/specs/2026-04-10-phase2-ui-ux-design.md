# Phase 2 — UI/UX Fixes Design Spec
**Date:** 2026-04-10  
**Project:** SecureCAT-v2  
**Scope:** changes.txt § Phase 2 (items 2.1–2.8)  
**Approach:** Option B — quick fixes first, dashboard revamp last (single plan)

---

## Implementation Order

1. 2.6 — Compact tables (shared infrastructure, affects all pages)
2. 2.8 — Sticky nav header
3. 2.7 — InfoPopover component (new reusable component)
4. 2.1 — `/apply` page fixes
5. 2.2 — `/login` tab persistence
6. 2.3 — `/applications` role-guard + status cleanup
7. 2.5 — Admin dashboard Institution Information section
8. 2.4 — Staff dashboard revamp (depends on KpiCard, InfoPopover, compact tables)

---

## 2.1 — `/apply` Page Fixes

**File:** `resources/js/Pages/Applications/Apply.svelte`

- Action buttons container: add `justify-end` to right-align
- Page title `<h1>`: add `text-center`
- Success message: replace `"test scheduling"` → `"exam scheduling"`
- After-apply state: remove duplicate "Back to Home" button (keep one only)

---

## 2.2 — `/login` Tab Persistence

**File:** `resources/js/Pages/Auth/Login.svelte`

- Strategy: `localStorage` key `loginTab`, values `'applicant'` | `'staff'`
- On mount (`onMount`): read `localStorage.getItem('loginTab')`, default to `'applicant'`
- On tab change: write `localStorage.setItem('loginTab', value)`
- No backend changes required

---

## 2.3 — `/applications` Role Guard + Status Cleanup

**File:** `resources/js/Pages/Applications/Index.svelte`

- **Import button:** hide for `admin` role; visible to `super_admin` only
  - Check existing `hasRole()` / prop pattern and apply consistently
- **Incomplete Documents status:** remove from `statusLabel()`, `statusVariant()`, and any filter dropdown options
  - No backend migration needed — status value simply stops being rendered

---

## 2.4 — Staff Dashboard Revamp

**Files:**
- `resources/js/Pages/Dashboard.svelte` (frontend)
- `app/Http/Controllers/DashboardController.php` (backend — extend or create)
- Route: `routes/web.php` (verify dashboard route passes new props)

### Role-Scoped KPI Cards

KPI data passed as Inertia props, computed per role on the backend.

| Role | KPI Cards |
|------|-----------|
| `admin` (Registrar Admin) | Pending applications, Accepted applications, Dismissed applications |
| `proctor` | Upcoming sessions, Attendance due (examinees), Submissions due (examinees) |
| `test_administrator` | Upcoming sessions, Attendance due, Submissions due, Pending grading (sessions), Pending release (batches) |
| `super_admin` | All of the above |

### Quick Actions (below KPIs)

| Role | Actions |
|------|---------|
| `admin` | → View Applications, → Manage Users |
| `proctor` | → My Sessions |
| `test_administrator` | → My Sessions, → Grading, → Release Results |
| `super_admin` | → View Applications, → My Sessions, → Grading, → Release Results, → Manage Users |

### Removals
- "Print Admission Slip" action removed from all role views

### Frontend notes
- Reuse existing `KpiCard` component (`Components/KpiCard.svelte`)
- Quick action buttons are styled as secondary links, not primary CTAs
- Dashboard.svelte uses `hasRole()` blocks to conditionally render each section

### Backend notes
- Dashboard controller queries counts using Eloquent, scoped by season where applicable
- Only query what the authenticated user's role needs (no over-fetching)
- Pass as named Inertia props: `applicationStats`, `sessionStats`, `gradingStats`

---

## 2.5 — Admin Dashboard: Institution Information Section

**File:** `resources/js/Pages/Dashboard.svelte` (same file as 2.4)

- Visible to `admin` and `super_admin` only
- Rendered below the KPI cards as a named section: **"Institution Information"**
- Contains two action buttons:
  - Room Management → `/admin/rooms`
  - Course Management → `/admin/courses`
- Styled as secondary action buttons consistent with the quick actions row

### Removals
- Admission Slip Template nav link removed from admin/superadmin navigation
- Note: The actual `AdmissionSlipTemplates/Index.svelte` page and its route may be left in place for now but de-linked from nav

---

## 2.6 — Compact Table UI

**Files:**
- `resources/js/Components/ui/table/table-cell.svelte`
- `resources/js/Components/ui/table/table-head.svelte`

- Change cell padding: default → `py-2 px-3.5`
- Change header padding: default → `py-2 px-3.5`
- Font size stays `text-sm` — no change
- All tables across the app inherit the fix automatically via these primitives

---

## 2.7 — InfoPopover Component

**File:** `resources/js/Components/InfoPopover.svelte` (new)

### Props
```ts
content: string       // The explanation text shown in the popover
label?: string        // Optional badge label e.g. "Beta"
```

### Behaviour
- Renders a small `ⓘ` icon button (or info circle icon from existing icon set)
- Click opens a shadcn `Popover` with `content` text
- Optional `label` renders as a small badge (e.g. "Beta") next to the icon
- Stays open until dismissed (click outside or press Escape)
- Uses existing shadcn Popover primitives already in the project

### Usage example
```svelte
<InfoPopover
  content="Chat with the assistant to refine your schedule. After you get a reply, click Generate Schedule to create a preview."
  label="Beta"
/>
```

### First usage
Replace inline description paragraph on the AI Exam Scheduler modal (`Admin/TestScheduling/Show.svelte`)

---

## 2.8 — Sticky Navigation Header

**File:** `resources/js/Layouts/AuthenticatedLayout.svelte`

- Find the top `<nav>` / header element
- Add Tailwind classes: `sticky top-0 z-50`
- Ensure background is opaque (add `bg-background` or equivalent) so page content doesn't bleed through on scroll
- Single targeted change — no layout restructuring needed

---

## Data / Backend Summary

| Item | Backend change needed? |
|------|----------------------|
| 2.1 | No |
| 2.2 | No |
| 2.3 | No (status values stay in DB, just removed from UI) |
| 2.4 | Yes — DashboardController passes role-scoped counts |
| 2.5 | No (links only) |
| 2.6 | No |
| 2.7 | No |
| 2.8 | No |

---

## Testing Notes

- 2.3: Verify Import button is hidden for `admin` and visible for `super_admin`
- 2.4: Verify each role sees only their KPI section (test with 4 role accounts)
- 2.4: Verify "Print Admission Slip" is gone from all views
- 2.5: Verify Institution Information section visible for admin/superadmin, hidden for others
- 2.6: Spot-check tables on Applications, Sessions, Rooms, Courses pages
- 2.7: Verify popover opens/closes on click and outside-click dismiss works
- 2.8: Verify nav stays visible on scroll on a long page (e.g. Applications list)
