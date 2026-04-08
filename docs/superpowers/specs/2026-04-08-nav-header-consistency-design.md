# Nav & Header Consistency Design

**Date:** 2026-04-08  
**Project:** SecureCAT-v2  
**Status:** Approved

---

## Problem

Three sources of truth for page identity currently exist simultaneously:

1. The sidebar nav label (e.g., `Seasons`)
2. The top header bar title/breadcrumb (often falls back to `Dashboard`)
3. An `<h1>` inside the page body (e.g., `Academic Years`)

These are inconsistent with each other and with each other across different pages. Some pages pass `breadcrumbs`, some pass `pageTitle`, some pass nothing.

---

## Goals

- **Single source of truth:** page title/location lives only in the top header bar
- **Breadcrumb trail:** clickable ancestor links replace all "back to X" patterns
- **Consistent labels:** one canonical label per section, used everywhere
- **Mobile-friendly:** full trail on desktop; collapsed `••• › Current` with dropdown on mobile

---

## Canonical Label Map

These labels are used in sidebar nav, breadcrumbs, and `<svelte:head><title>` — everywhere.

| Section | Canonical Label |
|---------|----------------|
| Seasons (db) | **Academic Years** |
| Test Scheduling | **Exam Scheduling** |
| Exam Domains / Exam Pillars | **Aptitude Areas** |
| Knowledge Docs | **Knowledge Documents** |
| Result Templates | **Result Sheet Templates** |
| Admission Slip Templates | **Admission Slip Templates** *(unchanged)* |
| My Sessions | **My Sessions** *(unchanged)* |
| Session Monitor | **Exam Monitoring** |
| Applications | **Applications** *(unchanged)* |
| Grading | **Grading** *(unchanged)* |
| Users | **Users** *(unchanged)* |
| Settings | **Settings** *(unchanged)* |
| Audit Log | **Audit Log** *(unchanged)* |

> **Note:** `Seasons` is renamed at UI level only. Database/model/route renaming is out of scope for this plan.

---

## Architecture

### Approach: Frontend-only, per-page static breadcrumb arrays

Each Svelte page defines its own `breadcrumbs` array and passes it to `AuthenticatedLayout`. No backend changes required. All breadcrumb labels are static strings (no dynamic record names).

### Breadcrumb shape

```ts
type Crumb = { label: string; href?: string };
// Last crumb has no href (current page). All ancestors have href.
```

### Breadcrumb patterns by page type

| Page type | Pattern |
|-----------|---------|
| Index | `[{ label: 'Section Name' }]` |
| Create | `[{ label: 'Section Name', href: '/path' }, { label: 'Create' }]` |
| Edit | `[{ label: 'Section Name', href: '/path' }, { label: 'Edit' }]` |
| Import | `[{ label: 'Section Name', href: '/path' }, { label: 'Import' }]` |
| Detail/Show | `[{ label: 'Section Name', href: '/path' }, { label: 'View' }]` |
| 3-level | `[{ label: 'Parent', href: '...' }, { label: 'Child', href: '...' }, { label: 'Leaf' }]` |

---

## Component Changes

### 1. `AuthenticatedLayout.svelte`

#### A. Sidebar nav label updates

In `navSections`, update labels:

```js
{ href: '/admin/seasons',                    label: 'Academic Years' }
{ href: '/admin/test-scheduling',            label: 'Exam Scheduling' }        // Registrar Office
{ href: '/admin/test-scheduling',            label: 'Exam Scheduling' }        // Guidance Office (My Sessions stays)
{ href: '/admin/test-scheduling/monitoring', label: 'Exam Monitoring' }        // top-level, not nested under Exam Scheduling
{ href: '/admin/exam-domains',               label: 'Aptitude Areas' }
{ href: '/admin/knowledge-documents',        label: 'Knowledge Documents' }
{ href: '/admin/result-sheet-templates',     label: 'Result Sheet Templates' }
```

#### B. Mobile breadcrumb dropdown

Add state:
```js
let breadcrumbOpen = $state(false);
```

In the header, replace the current breadcrumb rendering with responsive logic:

**Desktop (`md+`):** Full inline trail always visible — unchanged from current behavior.

**Mobile (`< md`):** When `breadcrumbs.length > 1`, show a tappable row:
```
[☰]  [••• › Current Page  ▾]
```
Tapping toggles `breadcrumbOpen`. When open, a dropdown panel appears below the header bar showing the full trail:
- Each ancestor is a `<Link>` (tappable, navigates, closes dropdown)
- Current page shown as non-link, visually distinct
- Dropdown closes on: link tap, outside tap (via backdrop), route navigation

When `breadcrumbs.length === 1` on mobile, show the single label as plain text (no dropdown needed).

#### C. `pageTitle` prop removed

The `pageTitle` prop and its fallback logic are removed from the layout. `breadcrumbs` is the sole source. The layout always derives the display from `breadcrumbs`.

#### D. `<svelte:head><title>` in layout

Update to derive from breadcrumbs:
```js
const headTitle = $derived(
  breadcrumbs.length > 0
    ? `${breadcrumbs[breadcrumbs.length - 1].label} - SecureCAT`
    : 'SecureCAT'
);
```
Individual pages remove their own `<svelte:head>` blocks (no duplication).

---

### 2. Per-page changes (all ~25 files)

For every affected page:

1. **Remove** the `<h1>` element and its containing wrapper `<div>` (if that div only held the title). If a subtitle `<p>` was paired with the `<h1>`, keep the `<p>` — it stays in the body.
2. **Remove** `<svelte:head>` block (layout now handles `<title>`).
3. **Remove** `pageTitle` variable if present.
4. **Add** `breadcrumbs` const with the correct array.
5. **Pass** `breadcrumbs` to `<AuthenticatedLayout breadcrumbs={breadcrumbs}>`.

#### Full breadcrumb inventory

| File | Breadcrumbs |
|------|-------------|
| `Admin/Seasons/Index` | `[{ label: 'Academic Years' }]` |
| `Admin/Seasons/Create` | `[{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Create' }]` |
| `Admin/Seasons/Edit` | `[{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Edit' }]` |
| `Admin/TestScheduling/Index` | `[{ label: isProctorView ? 'My Sessions' : 'Exam Scheduling' }]` |
| `Admin/TestScheduling/Show` | `[{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'View' }]` |
| `Admin/TestScheduling/Create` | `[{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'Create' }]` |
| `Admin/TestScheduling/Edit` | `[{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'Edit' }]` |
| `Admin/TestScheduling/Monitoring` | `[{ label: 'Exam Monitoring' }]` |
| `Admin/ExamDomains/Index` | `[{ label: 'Aptitude Areas' }]` |
| `Admin/ExamDomains/Create` | `[{ label: 'Aptitude Areas', href: '/admin/exam-domains' }, { label: 'Create' }]` |
| `Admin/ExamDomains/Edit` | `[{ label: 'Aptitude Areas', href: '/admin/exam-domains' }, { label: 'Edit' }]` |
| `Admin/KnowledgeDocuments/Index` | `[{ label: 'Knowledge Documents' }]` |
| `Admin/KnowledgeDocuments/Create` | `[{ label: 'Knowledge Documents', href: '/admin/knowledge-documents' }, { label: 'Create' }]` |
| `Admin/KnowledgeDocuments/Edit` | `[{ label: 'Knowledge Documents', href: '/admin/knowledge-documents' }, { label: 'Edit' }]` |
| `Admin/KnowledgeDocuments/Import` | `[{ label: 'Knowledge Documents', href: '/admin/knowledge-documents' }, { label: 'Import' }]` |
| `Admin/ResultSheetTemplates/Index` | `[{ label: 'Result Sheet Templates' }]` |
| `Admin/ResultSheetTemplates/Create` | `[{ label: 'Result Sheet Templates', href: '/admin/result-sheet-templates' }, { label: 'Create' }]` |
| `Admin/ResultSheetTemplates/Edit` | `[{ label: 'Result Sheet Templates', href: '/admin/result-sheet-templates' }, { label: 'Edit' }]` |
| `Admin/AdmissionSlipTemplates/Index` | `[{ label: 'Admission Slip Templates' }]` |
| `Admin/Rooms/Edit` | `[{ label: 'Rooms', href: '/admin/rooms' }, { label: 'Edit' }]` |
| `Admin/Users/Create` | `[{ label: 'Users', href: '/admin/users' }, { label: 'Create' }]` |
| `Admin/Users/Edit` | `[{ label: 'Users', href: '/admin/users' }, { label: 'Edit' }]` |
| `Admin/Logs/Index` | `[{ label: 'Audit Log' }]` |
| `Admin/Settings/Index` | `[{ label: 'Settings' }]` |
| `Dashboard` | `[{ label: 'Dashboard' }]` |

Pages already passing correct breadcrumbs (verify labels only):
- Applications, Grading, Portal, Consultation — audit labels, remove any `<h1>` in body if present.

---

## What Does NOT Change

- Page subtitle/description `<p>` tags — remain in the page body
- All page content (tables, forms, cards)
- All existing `breadcrumbs` infrastructure in `AuthenticatedLayout` (desktop rendering is unchanged)
- Routes, controllers, models, database schema

---

## Out of Scope

- Database/model renaming (`seasons` table, `Season` model, route slugs)
- Any new pages or features
- Notification bell, search bar, theme toggle

---

## Success Criteria

- [ ] No `<h1>` exists inside any authenticated page body
- [ ] Every page passes a `breadcrumbs` array with the canonical label
- [ ] Sidebar nav labels match canonical label map exactly
- [ ] On mobile, breadcrumbs with 2+ crumbs show collapsed `••• › Current` with working dropdown
- [ ] `<title>` in browser tab reflects current page via layout (no per-page `<svelte:head>`)
- [ ] "Session Monitor" page no longer shows "Dashboard" in the header
