# Breadcrumb Header Navigation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the duplicate sticky-header + in-page-header pattern with a clean breadcrumb-based sticky header. The sticky header renders `Root > Child` where Root is clickable (back navigation) and Child is the entity identifier.

**Architecture:** The sticky header in `AuthenticatedLayout.svelte` will accept a `breadcrumbs` prop — an array of `{label, href?}` objects. When two or more items exist, ancestors are rendered as clickable `Link` elements and the last item as the current-page indicator, separated by `ChevronRight` icons. Pages remove their redundant in-body header blocks (back button, large h1, name, status badge, action buttons) and instead pass breadcrumb data to the layout. For single-item breadcrumbs (list views), the header shows only the section name with no separator.

**Tech Stack:** Svelte 5, Inertia.js (`@inertiajs/svelte`), `lucide-svelte`

---

## File Map

### Modified Files

| File | Responsibility |
|------|----------------|
| `resources/js/Layouts/AuthenticatedLayout.svelte` | Sticky header: add `breadcrumbs` prop + `ChevronRight` import, render breadcrumb nav, keep search/theme/notifications |
| `resources/js/Pages/Applications/Show.svelte` | Detail page: add breadcrumbs prop, strip back btn + h1 + name + badge + actions block |
| `resources/js/Pages/Applications/Index.svelte` | List page: add breadcrumbs prop, strip h1 "Applications" block |
| `resources/js/Pages/Applications/PrintSlips.svelte` | Tool page: add breadcrumbs prop, strip "Back to applications" link |
| `resources/js/Pages/Admin/TestScheduling/Show.svelte` | Detail page: add breadcrumbs prop (view-aware), strip back link |
| `resources/js/Pages/Admin/TestScheduling/Index.svelte` | List page: add breadcrumbs prop (view-aware), strip pageTitle h1 block |
| `resources/js/Pages/Admin/Users/Index.svelte` | List page: add breadcrumbs prop, strip h1 "User Management" block |
| `resources/js/Pages/Proctor/SessionRoster.svelte` | Detail page: add breadcrumbs prop, strip back link |
| `resources/js/Pages/Proctor/Dashboard.svelte` | List page: add breadcrumbs prop, strip h1 block |
| `resources/js/Pages/Grading/Session.svelte` | Detail page: add breadcrumbs prop, strip back link |
| `resources/js/Pages/Grading/ScoreInput.svelte` | Deep-detail page: add 3-level breadcrumbs prop, strip back link |
| `resources/js/Pages/Grading/Dashboard.svelte` | List page: add breadcrumbs prop, strip h1 title block |
| `resources/js/Pages/Grading/PrintBatch.svelte` | Tool page: add breadcrumbs prop, strip "Back to session" link |
| `resources/js/Pages/Consultation/ApplicantView.svelte` | Detail page: add breadcrumbs prop, strip back link + profile card header |
| `resources/js/Pages/Consultation/Dashboard.svelte` | List page: add breadcrumbs prop (no in-body h1 to strip) |
| `resources/js/Pages/Consultation/ConsultationDay.svelte` | Sub-page: add breadcrumbs prop, strip h1 "Today's Consultations" |
| `resources/js/Pages/Consultation/ScheduleDay.svelte` | Sub-page: add breadcrumbs prop, strip h1 "Schedule Consultations" |

---

## Task 1: Update AuthenticatedLayout — Add Breadcrumb Rendering

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.svelte`

- [ ] **Step 1: Read the current header section**

  Read lines 1-15 and lines 215-250 of `resources/js/Layouts/AuthenticatedLayout.svelte` to confirm imports and header markup.

- [ ] **Step 2: Add `ChevronRight` to the existing import and add `breadcrumbs` prop**

  The current import (line 4) is:
  ```svelte
  import { ChevronDown, Menu, LayoutDashboard, Users, FileText, Calendar, GraduationCap, BookOpen, Settings, MessageSquare, ScrollText, FileStack, Activity, CalendarRange, Layers, ShieldCheck, Sun, Moon, Bell, Search } from 'lucide-svelte';
  ```

  Add `ChevronRight` to it — **do not remove `ChevronDown`**, it is used in the user dropdown:
  ```svelte
  import { ChevronDown, ChevronRight, Menu, LayoutDashboard, Users, FileText, Calendar, GraduationCap, BookOpen, Settings, MessageSquare, ScrollText, FileStack, Activity, CalendarRange, Layers, ShieldCheck, Sun, Moon, Bell, Search } from 'lucide-svelte';
  ```

  Find line 7:
  ```svelte
  let { children } = $props();
  ```
  Change to:
  ```svelte
  let { children, breadcrumbs = [] } = $props();
  ```

- [ ] **Step 3: Replace `<h1>` in header with breadcrumb renderer**

  Find line 222:
  ```svelte
  <h1 class="text-2xl font-semibold tracking-tight text-foreground">{pageTitle}</h1>
  ```

  Replace with:

  ```svelte
  {#if breadcrumbs.length === 0}
    <h1 class="text-2xl font-semibold tracking-tight text-foreground">{pageTitle}</h1>
  {:else if breadcrumbs.length === 1}
    <h1 class="text-2xl font-semibold tracking-tight text-foreground">{breadcrumbs[0].label}</h1>
  {:else}
    <nav class="flex items-center gap-2 text-sm" aria-label="Breadcrumb">
      {#each breadcrumbs as crumb, i}
        {#if i > 0}
          <ChevronRight class="h-4 w-4 text-muted-foreground/50 shrink-0" aria-hidden="true" />
        {/if}
        {#if crumb.href && i < breadcrumbs.length - 1}
          <Link
            href={crumb.href}
            class="text-muted-foreground hover:text-foreground transition-colors font-medium shrink-0"
          >
            {crumb.label}
          </Link>
        {:else}
          <span
            class="font-semibold text-foreground truncate max-w-[180px] sm:max-w-none"
            aria-current={i === breadcrumbs.length - 1 ? 'page' : undefined}
          >
            {crumb.label}
          </span>
        {/if}
      {/each}
    </nav>
  {/if}
  ```

  > **UX Note:** The last item (current page) is always `font-semibold text-foreground` — the most prominent. Parent links are `text-muted-foreground`. `aria-current="page"` is set on the last item for screen-reader accessibility. `truncate max-w-[180px] sm:max-w-none` prevents overflow on mobile for long names or 3-level breadcrumbs.

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Layouts/AuthenticatedLayout.svelte
  git commit -m "feat: add breadcrumbs prop to AuthenticatedLayout header"
  ```

---

## Task 2: Update Applications/Show.svelte — Remove Redundant Header

**Files:**
- Modify: `resources/js/Pages/Applications/Show.svelte`

Props available: `application` (object with `reference_number`).

- [ ] **Step 1: Read the file**

  Read `resources/js/Pages/Applications/Show.svelte` to locate:
  - The `<AuthenticatedLayout>` opening tag (around line 91)
  - The redundant header div (lines 93–143): back button, `<h1>` with reference number, name subtitle, status badge, action buttons

- [ ] **Step 2: Add `breadcrumbs` derived value**

  In the `<script>` section, add before `</script>`:

  ```svelte
  const breadcrumbs = $derived([
    { label: 'Applications', href: '/applications' },
    { label: application?.reference_number ?? 'Application' }
  ]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove redundant header block (lines 93–143)**

  Delete the entire `<div class="flex flex-col gap-4 sm:flex-row...">` block containing:
  - `<Link href="/applications">` with `ArrowLeft` button
  - `<h1 class="text-2xl font-bold">{application?.reference_number...}`
  - `<p class="mt-1 text-sm text-muted-foreground">{fullName}</p>`
  - Badge, action buttons

  The page content should start directly with the first meaningful card/content block.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Applications/Show.svelte
  git commit -m "refactor: use breadcrumb header, remove redundant page header"
  ```

---

## Task 3: Update Applications/Index.svelte — Remove Redundant H1

**Files:**
- Modify: `resources/js/Pages/Applications/Index.svelte`

Props available: `applications`, `filters`, `seasons`, `active_season_id`, `statuses`.

- [ ] **Step 1: Read the file**

  Read lines 70–100 to find the `<div>` containing the h1 and description paragraph.

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([{ label: 'Applications' }]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the redundant h1 div**

  Remove only the `<h1 class="text-2xl font-bold">Applications</h1>` element. Keep the description paragraph (`View and manage applications by season`) in the content area — it provides useful context.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Applications/Index.svelte
  git commit -m "refactor: use breadcrumb header in Applications list"
  ```

---

## Task 4: Update Applications/PrintSlips.svelte — Remove Back Link

**Files:**
- Modify: `resources/js/Pages/Applications/PrintSlips.svelte`

Props available: `applications` (array). No session identifier — this is a batch print tool reached from the Applications list.

- [ ] **Step 1: Read the file**

  Locate the `<Link href="/applications">` block with `ArrowLeft` (around lines 75–80).

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([
    { label: 'Applications', href: '/applications' },
    { label: 'Print Slips' }
  ]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the `<Link href="/applications">` back button block**

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Applications/PrintSlips.svelte
  git commit -m "refactor: use breadcrumb header in print slips"
  ```

---

## Task 5: Update Admin/TestScheduling/Show.svelte — Add Breadcrumb, Remove Back Link

**Files:**
- Modify: `resources/js/Pages/Admin/TestScheduling/Show.svelte`

Props available: `session` (object with `.id`), `view` (string, `'admin'` or `'proctor'`).

> **Important:** The view-awareness variable is `view === 'proctor'` — do **not** reference `isProctorView` which does not exist by that name in source.

- [ ] **Step 1: Read the file**

  Find the back link block (around line 105) and the `<AuthenticatedLayout>` tag.

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived(
    view === 'proctor'
      ? [
          { label: 'My Sessions', href: '/admin/test-scheduling?view=proctor' },
          { label: session?.id ? 'Session #' + session.id : 'Session' }
        ]
      : [
          { label: 'Test Scheduling', href: '/admin/test-scheduling' },
          { label: session?.id ? 'Session #' + session.id : 'Session' }
        ]
  );
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the back link div**

  Delete the `<Link href="...">` block containing `ArrowLeft` and label `Back to my sessions` / `Back to exam sessions`.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Admin/TestScheduling/Show.svelte
  git commit -m "refactor: use breadcrumb header in session detail page"
  ```

---

## Task 6: Update Admin/TestScheduling/Index.svelte — Simplify Header

**Files:**
- Modify: `resources/js/Pages/Admin/TestScheduling/Index.svelte`

Props available: `view` (string). The `pageTitle` derived already exists.

> **Important:** Use `view === 'proctor'` directly — the local alias is `α1` in compiled form, but in source the human-readable form is `view === 'proctor'`.

- [ ] **Step 1: Read lines 15–25 and 100–160**

  Confirm the `pageTitle`/`pageDescription` derived block and find the in-body h1 div.

- [ ] **Step 2: Add breadcrumbs derived**

  Place this after the existing `pageTitle` derived (so ordering is clear):

  ```svelte
  const breadcrumbs = $derived(
    view === 'proctor'
      ? [{ label: 'My Sessions' }]
      : [{ label: 'Test Scheduling' }]
  );
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the redundant h1 block**

  Remove the `text-2xl font-bold` div with `pageTitle` and `pageDescription`. Keep the `pageDescription` text in the content area if it is useful.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Admin/TestScheduling/Index.svelte
  git commit -m "refactor: use breadcrumb header in test scheduling list"
  ```

---

## Task 7: Update Admin/Users/Index.svelte — Simplify Header

**Files:**
- Modify: `resources/js/Pages/Admin/Users/Index.svelte`

Props available: `users`, `roles`, `filters`.

- [ ] **Step 1: Read lines 50–70**

  Find the h1 "User Management" block.

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([{ label: 'Users' }]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the redundant h1 div**

  Keep the "Add User" button in the content area — do not remove it.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Admin/Users/Index.svelte
  git commit -m "refactor: use breadcrumb header in users list"
  ```

---

## Task 8: Update Proctor/SessionRoster.svelte — Remove Back Link

**Files:**
- Modify: `resources/js/Pages/Proctor/SessionRoster.svelte`

Props available: `session` (object with `.id`), `applicants`, `stats`.

- [ ] **Step 1: Read the file**

  Find the back link div (around lines 247–254) and `<AuthenticatedLayout>` tag.

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([
    { label: 'My Sessions', href: '/admin/test-scheduling?view=proctor' },
    { label: session?.id ? 'Session #' + session.id : 'Session' }
  ]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the back link div**

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Proctor/SessionRoster.svelte
  git commit -m "refactor: use breadcrumb header in session roster"
  ```

---

## Task 9: Update Proctor/Dashboard.svelte — Simplify Header

**Files:**
- Modify: `resources/js/Pages/Proctor/Dashboard.svelte`

Props available: `title` (string, default `'My Sessions'`).

- [ ] **Step 1: Read the file**

  Find the `<h1 class="text-2xl font-bold">{title}</h1>` block (around line 15).

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([{ label: title }]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the redundant h1 block**

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Proctor/Dashboard.svelte
  git commit -m "refactor: use breadcrumb header in proctor dashboard"
  ```

---

## Task 10: Update Grading/Session.svelte — Remove Back Link

**Files:**
- Modify: `resources/js/Pages/Grading/Session.svelte`

Props available: `sessionId` (string/number), `session` (object with `.exam_session_id`), `applicants`, `workflowStatus`.

- [ ] **Step 1: Read the file**

  Find the back link div with `ArrowLeft` (around line 57).

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([
    { label: 'Grading', href: '/grading' },
    { label: session?.exam_session_id ? 'Session #' + session.exam_session_id : 'Session' }
  ]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the back link div**

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Grading/Session.svelte
  git commit -m "refactor: use breadcrumb header in grading session"
  ```

---

## Task 11: Update Grading/ScoreInput.svelte — Remove Back Link

**Files:**
- Modify: `resources/js/Pages/Grading/ScoreInput.svelte`

Props available: `sessionId` (string/number), `applicantId` (string/number), `applicant` (object with `.name`), `domains`, `existing_scores`, `workflowStatus`.

> **Important:** `sid` is already declared as `const sid = $derived(String(sessionId))`. Place the `breadcrumbs` derived **after** `sid` in the script to ensure correct ordering.

- [ ] **Step 1: Read lines 1–15 and lines 80–95**

  Confirm `const sid = $derived(...)` location and find the ArrowLeft back link block.

- [ ] **Step 2: Add breadcrumbs derived (after `const sid`)**

  ```svelte
  const breadcrumbs = $derived([
    { label: 'Grading', href: '/grading' },
    { label: 'Session #' + sid, href: `/grading/sessions/${sid}` },
    { label: applicant?.name ?? 'Applicant' }
  ]);
  ```

  > This generates a 3-level breadcrumb: `Grading > Session #N > Applicant Name`. The layout renderer handles truncation on mobile automatically via `truncate max-w-[180px] sm:max-w-none`.

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the back link div (around lines 80–88)**

  Delete the `<Link href={...}>` block containing `ArrowLeft` and "Back" label.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Grading/ScoreInput.svelte
  git commit -m "refactor: use breadcrumb header in score input"
  ```

---

## Task 12: Update Grading/Dashboard.svelte — Simplify Header

**Files:**
- Modify: `resources/js/Pages/Grading/Dashboard.svelte`

- [ ] **Step 1: Read the file**

  Find the h1 block (around lines 48–51).

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([{ label: 'Grading' }]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the redundant h1 div** — keep description text in content area.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Grading/Dashboard.svelte
  git commit -m "refactor: use breadcrumb header in grading dashboard"
  ```

---

## Task 13: Update Grading/PrintBatch.svelte — Remove Back Link

**Files:**
- Modify: `resources/js/Pages/Grading/PrintBatch.svelte`

Props available: `sessionId` (string/number), `session` (object), `applicants`.
The page already derives `const sid = $derived(String(sessionId))`.

> **Important:** Place `breadcrumbs` derived **after** `const sid`.

- [ ] **Step 1: Read the file**

  Find `const sid` declaration and the `<Link href={...}>` back link block (around lines 82–89).

- [ ] **Step 2: Add breadcrumbs derived (after `const sid`)**

  ```svelte
  const breadcrumbs = $derived([
    { label: 'Grading', href: '/grading' },
    { label: 'Session #' + sid, href: `/grading/sessions/${sid}` },
    { label: 'Print Batch' }
  ]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the `<Link href={...}>` back link block**

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Grading/PrintBatch.svelte
  git commit -m "refactor: use breadcrumb header in grading print batch"
  ```

---

## Task 14: Update Consultation/ApplicantView.svelte — Remove Redundant Header

**Files:**
- Modify: `resources/js/Pages/Consultation/ApplicantView.svelte`

Props available: `applicant` (object with `.name`, `.id`), `scores`, `consultation_summary`.

- [ ] **Step 1: Read the file**

  Find the back link block (around lines 45–50).

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([
    { label: 'Release & Consultation', href: '/consultation' },
    { label: applicant?.name ?? 'Applicant' }
  ]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the back link block**

  Delete the `<Link href="/consultation">` block with `ArrowLeft`. The applicant profile card in the content area should remain intact.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Consultation/ApplicantView.svelte
  git commit -m "refactor: use breadcrumb header in applicant view"
  ```

---

## Task 15: Update Consultation/Dashboard.svelte — Add Breadcrumb

**Files:**
- Modify: `resources/js/Pages/Consultation/Dashboard.svelte`

Props available: `applicants_pending`, `applicants_released`, `stats`. No in-body h1 to strip.

- [ ] **Step 1: Read lines 55–65**

  Confirm there is no in-body h1 that duplicates the header.

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([{ label: 'Release & Consultation' }]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Pages/Consultation/Dashboard.svelte
  git commit -m "refactor: add breadcrumb header in consultation dashboard"
  ```

---

## Task 16: Update Consultation/ConsultationDay.svelte — Strip In-body H1

**Files:**
- Modify: `resources/js/Pages/Consultation/ConsultationDay.svelte`

Props available: `applicants`, `scheduledApplicantIds`.

- [ ] **Step 1: Read lines 40–60**

  Find the `<h1 class="text-2xl font-bold tracking-tight text-foreground">Today's Consultations</h1>` block (around line 47).

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([
    { label: 'Release & Consultation', href: '/consultation' },
    { label: "Today's Consultations" }
  ]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the in-body h1**

  Delete the `<h1>Today's Consultations</h1>` element only. Keep the date picker and other controls in the content area.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Consultation/ConsultationDay.svelte
  git commit -m "refactor: use breadcrumb header in consultation day view"
  ```

---

## Task 17: Update Consultation/ScheduleDay.svelte — Strip In-body H1

**Files:**
- Modify: `resources/js/Pages/Consultation/ScheduleDay.svelte`

Props available: `batches`, `flash`.

- [ ] **Step 1: Read lines 35–50**

  Find the `<h1 class="text-2xl font-bold tracking-tight text-foreground">Schedule Consultations</h1>` block (around line 39).

- [ ] **Step 2: Add breadcrumbs derived**

  ```svelte
  const breadcrumbs = $derived([
    { label: 'Release & Consultation', href: '/consultation' },
    { label: 'Schedule Consultations' }
  ]);
  ```

- [ ] **Step 3: Pass breadcrumbs to AuthenticatedLayout**

  ```svelte
  <AuthenticatedLayout breadcrumbs={breadcrumbs}>
  ```

- [ ] **Step 4: Remove the in-body h1**

  Delete the `<h1>Schedule Consultations</h1>` element only. Keep the batch form and all controls intact.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Consultation/ScheduleDay.svelte
  git commit -m "refactor: use breadcrumb header in schedule day view"
  ```

---

## Task 18: Final Verification — Check for Missed Pages

**Files:**
- Search: `resources/js/Pages/`

- [ ] **Step 1: Search for pages with `ArrowLeft` that may still have unhandled back buttons**

  ```bash
  grep -r "ArrowLeft" resources/js/Pages --include="*.svelte" -l
  ```

  For each found file, check whether it uses `AuthenticatedLayout`. If yes and it still has an in-body back button, apply the breadcrumb pattern. The following files use `ArrowLeft` but are **intentionally excluded** (standalone print/portal views, not `AuthenticatedLayout` pages):
  - `Grading/ResultSheet.svelte` — standalone print view, no layout
  - `Grading/ResultSheetBulk.svelte` — standalone print view, no layout
  - `Portal/AiCompanion.svelte` — portal page, not `AuthenticatedLayout`
  - `Applications/AdmissionSlipSingle.svelte` — standalone slip view
  - `Applications/AdmissionSlipBulk.svelte` — standalone slip view

- [ ] **Step 2: Search for pages using `AuthenticatedLayout` that may not have been updated**

  ```bash
  grep -r "AuthenticatedLayout" resources/js/Pages --include="*.svelte" -l
  ```

  Confirm each one either has `breadcrumbs={...}` passed or is a page that genuinely has no title to show (e.g., `Placeholder.svelte`).

- [ ] **Step 3: Commit any additional fixes found**

---

## Success Criteria

- [ ] Sticky header renders `Applications > APP-2024-00142` for application detail
- [ ] Sticky header renders `Applications` for application list
- [ ] Sticky header renders `Grading > Session #5 > Juan Dela Cruz` for score input (3-level)
- [ ] Clicking a parent label in the breadcrumb navigates to the correct route
- [ ] No page has both a sticky header AND an in-body header with the same information
- [ ] No orphaned back buttons remain in any `AuthenticatedLayout` page content area
- [ ] `ChevronDown` is preserved — the sidebar user dropdown arrow still renders
- [ ] Mobile: long applicant names in the breadcrumb truncate cleanly without overflowing the header
- [ ] Last breadcrumb item has `aria-current="page"` (verified in browser devtools)
- [ ] All existing pages render without layout regressions
- [ ] Theme toggle and notifications remain functional in sticky header
- [ ] No console errors from undefined variables (`session`, `applicant`, `sid`, etc.)
