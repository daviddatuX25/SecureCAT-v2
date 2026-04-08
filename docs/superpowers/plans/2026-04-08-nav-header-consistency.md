# Nav & Header Consistency Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove duplicate `<h1>` elements from all admin page bodies, rename 5 sidebar nav labels to canonical terms, and add breadcrumb navigation (with a mobile dropdown) to every admin page.

**Architecture:** Frontend-only — no Laravel/PHP changes needed. Each Svelte page defines its own static `breadcrumbs` array and passes it to `AuthenticatedLayout`. The layout derives the browser `<title>` from the last crumb. Mobile collapses multi-crumb trails to `••• › Current ▾` with a tap-to-expand dropdown; desktop always shows the full inline trail.

**Tech Stack:** Svelte 5 (`$props()`, `$state()`, `$derived()`, `$effect()`), Inertia.js (`Link`, `router`, `usePage`), TailwindCSS, Lucide Svelte icons.

**Spec:** `docs/superpowers/specs/2026-04-08-nav-header-consistency-design.md`

---

## File Map

### Layout (shared — changes affect all pages)
- Modify: `resources/js/Layouts/AuthenticatedLayout.svelte`

### Pages
- Modify: `resources/js/Pages/Dashboard.svelte`
- Modify: `resources/js/Pages/Admin/Seasons/Index.svelte`
- Modify: `resources/js/Pages/Admin/Seasons/Create.svelte`
- Modify: `resources/js/Pages/Admin/Seasons/Edit.svelte`
- Modify: `resources/js/Pages/Admin/TestScheduling/Index.svelte`
- Modify: `resources/js/Pages/Admin/TestScheduling/Show.svelte`
- Modify: `resources/js/Pages/Admin/TestScheduling/Create.svelte`
- Modify: `resources/js/Pages/Admin/TestScheduling/Edit.svelte`
- Modify: `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte`
- Modify: `resources/js/Pages/Admin/TestAdmin/Index.svelte`
- Modify: `resources/js/Pages/Admin/ExamDomains/Index.svelte`
- Modify: `resources/js/Pages/Admin/ExamDomains/Create.svelte`
- Modify: `resources/js/Pages/Admin/ExamDomains/Edit.svelte`
- Modify: `resources/js/Pages/Admin/KnowledgeDocuments/Index.svelte`
- Modify: `resources/js/Pages/Admin/KnowledgeDocuments/Create.svelte`
- Modify: `resources/js/Pages/Admin/KnowledgeDocuments/Edit.svelte`
- Modify: `resources/js/Pages/Admin/KnowledgeDocuments/Import.svelte`
- Modify: `resources/js/Pages/Admin/ResultSheetTemplates/Index.svelte`
- Modify: `resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte`
- Modify: `resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte`
- Modify: `resources/js/Pages/Admin/AdmissionSlipTemplates/Index.svelte`
- Modify: `resources/js/Pages/Admin/Rooms/Edit.svelte`
- Modify: `resources/js/Pages/Admin/Users/Create.svelte`
- Modify: `resources/js/Pages/Admin/Users/Edit.svelte`
- Modify: `resources/js/Pages/Admin/Logs/Index.svelte`
- Modify: `resources/js/Pages/Admin/Settings/Index.svelte`

---

## Before You Start

**How breadcrumbs work in this codebase:**

`AuthenticatedLayout.svelte` accepts a `breadcrumbs` prop — an array of objects shaped `{ label: string, href?: string }`. The last item in the array is the current page (no `href`). Every ancestor gets an `href` so the header renders it as a clickable link.

```js
// Index page (you're here, no ancestors):
const breadcrumbs = [{ label: 'Academic Years' }];

// Create/Edit page (parent link + current action):
const breadcrumbs = [
  { label: 'Academic Years', href: '/admin/seasons' },
  { label: 'Create' },
];
```

**Run the dev server so you can check your work in a browser after each task:**
```bash
npm run dev
```
Log in as a `super_admin` user. You'll verify each task visually before committing.

---

## Task 1: Update sidebar nav labels in AuthenticatedLayout

**File:** `resources/js/Layouts/AuthenticatedLayout.svelte`

Five labels in the sidebar nav need renaming. They live inside the `navSections` array in the `<script>` block (around line 56).

- [ ] **Step 1: Rename "Seasons" → "Academic Years"**

Find:
```js
{ href: '/admin/seasons', label: 'Seasons', icon: CalendarRange, roles: ['super_admin', 'admin'] },
```
Replace with:
```js
{ href: '/admin/seasons', label: 'Academic Years', icon: CalendarRange, roles: ['super_admin', 'admin'] },
```

- [ ] **Step 2: Rename "Test Scheduling" → "Exam Scheduling"**

Find:
```js
{ href: '/admin/test-scheduling', label: 'Test Scheduling', icon: Calendar, roles: ['super_admin', 'admin'] },
```
Replace with:
```js
{ href: '/admin/test-scheduling', label: 'Exam Scheduling', icon: Calendar, roles: ['super_admin', 'admin'] },
```

- [ ] **Step 3: Rename "Session Monitor" → "Exam Monitoring"**

Find:
```js
{ href: '/admin/test-scheduling/monitoring', label: 'Session Monitor', icon: Activity, roles: ['super_admin', 'test_administrator', 'proctor'] },
```
Replace with:
```js
{ href: '/admin/test-scheduling/monitoring', label: 'Exam Monitoring', icon: Activity, roles: ['super_admin', 'test_administrator', 'proctor'] },
```

- [ ] **Step 4: Rename "Exam Domains" → "Aptitude Areas"**

Find:
```js
{ href: '/admin/exam-domains', label: 'Exam Domains', icon: Layers, roles: ['super_admin'] },
```
Replace with:
```js
{ href: '/admin/exam-domains', label: 'Aptitude Areas', icon: Layers, roles: ['super_admin'] },
```

- [ ] **Step 5: Rename "Knowledge Docs" → "Knowledge Documents"**

Find:
```js
{ href: '/admin/knowledge-documents', label: 'Knowledge Docs', icon: BookOpen, roles: ['super_admin'] },
```
Replace with:
```js
{ href: '/admin/knowledge-documents', label: 'Knowledge Documents', icon: BookOpen, roles: ['super_admin'] },
```

- [ ] **Step 6: Rename "Result Templates" → "Result Sheet Templates"**

Find:
```js
{ href: '/admin/result-sheet-templates', label: 'Result Templates', icon: FileText, roles: ['super_admin'] },
```
Replace with:
```js
{ href: '/admin/result-sheet-templates', label: 'Result Sheet Templates', icon: FileText, roles: ['super_admin'] },
```

- [ ] **Step 7: Verify in browser**

Open the app. In the sidebar confirm:
- "Seasons" is now "Academic Years"
- "Test Scheduling" is now "Exam Scheduling"
- "Session Monitor" is now "Exam Monitoring"
- "Exam Domains" is now "Aptitude Areas"
- "Knowledge Docs" is now "Knowledge Documents"
- "Result Templates" is now "Result Sheet Templates"

- [ ] **Step 8: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "feat: rename sidebar nav labels to canonical terms"
```

---

## Task 2: Update AuthenticatedLayout — headTitle, remove pageTitle

**File:** `resources/js/Layouts/AuthenticatedLayout.svelte`

Currently the layout reads `pageTitle` from Inertia page props (`$page.props.pageTitle`) as a fallback for the browser tab. We're removing that and instead deriving the `<title>` from the last breadcrumb item. Individual pages will no longer set `<svelte:head>` — the layout owns the browser title.

- [ ] **Step 1: Remove the `pageTitle` derivation**

Find this line in the `<script>` block (around line 14):
```js
const pageTitle = $derived($page.props.pageTitle ?? 'Dashboard');
```
Delete it entirely.

- [ ] **Step 2: Add `headTitle` derivation**

After `const roles = $derived(...)`, add:
```js
const headTitle = $derived(
  breadcrumbs.length > 0
    ? `${breadcrumbs[breadcrumbs.length - 1].label} - SecureCAT`
    : 'SecureCAT'
);
```

- [ ] **Step 3: Update `<svelte:head>` to use `headTitle`**

Find:
```html
<svelte:head>
  <title>SecureCAT</title>
</svelte:head>
```
Replace with:
```html
<svelte:head>
  <title>{headTitle}</title>
</svelte:head>
```

- [ ] **Step 4: Update the zero-breadcrumb fallback in the header**

Find (in the `<header>` section):
```html
{#if breadcrumbs.length === 0}
  <h1 class="text-2xl font-semibold tracking-tight text-foreground">{pageTitle}</h1>
{:else if breadcrumbs.length === 1}
```
Replace with:
```html
{#if breadcrumbs.length === 0}
  <span class="text-2xl font-semibold tracking-tight text-foreground">SecureCAT</span>
{:else if breadcrumbs.length === 1}
```
> **Why "SecureCAT" here?** `pageTitle` no longer exists. The zero-crumb case is just a safety net — every page in this plan will pass at least one breadcrumb. If somehow a page passes none, the header shows "SecureCAT" rather than crashing.

- [ ] **Step 5: Verify there are no remaining `{pageTitle}` references in the file**

Search the file for `pageTitle`. There should be zero occurrences left. If any exist, fix them.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "feat: derive browser title from breadcrumbs, remove pageTitle fallback"
```

---

## Task 3: Add mobile breadcrumb dropdown to AuthenticatedLayout

**File:** `resources/js/Layouts/AuthenticatedLayout.svelte`

On mobile (viewport < 768px `md`), multi-crumb trails currently wrap awkwardly. We'll show a collapsed `••• › Current ▾` row that expands to a dropdown with the full trail.

- [ ] **Step 1: Add `breadcrumbOpen` state**

In the `<script>` block, directly after `let sidebarOpen = $state(false);`, add:
```js
let breadcrumbOpen = $state(false);
```

- [ ] **Step 2: Auto-close dropdown on route change**

Directly after the new `let breadcrumbOpen` line, add:
```js
$effect(() => {
  $page.url; // re-runs whenever the URL changes
  breadcrumbOpen = false;
});
```
> **Why?** When a user taps an ancestor link in the dropdown, Inertia navigates without a full page reload. This `$effect` detects URL changes and closes the dropdown automatically.

- [ ] **Step 3: Add `relative` positioning to the `<header>` element**

Find:
```html
<header class="sticky top-0 z-20 h-20 glass-panel border-b border-l-0 flex items-center justify-between px-4 lg:px-8">
```
Replace with:
```html
<header class="sticky top-0 z-20 h-20 glass-panel border-b border-l-0 flex items-center justify-between px-4 lg:px-8 relative">
```
> **Why `relative`?** The dropdown panel uses `absolute top-full left-0 right-0`. Without a `relative` ancestor, it would position against the viewport, not the header bar.

- [ ] **Step 4: Replace the multi-crumb `{:else}` branch in the header**

Find the entire `{:else}` block that renders the desktop breadcrumb trail:
```html
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

Replace with:
```html
  {:else}
    <!-- Desktop (md+): full inline trail, always visible -->
    <nav class="hidden md:flex items-center gap-2 text-sm" aria-label="Breadcrumb">
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

    <!-- Mobile (< md): collapsed ••• › Current ▾ with dropdown -->
    <div class="flex items-center md:hidden">
      <button
        type="button"
        class="flex items-center gap-2 text-sm"
        onclick={() => (breadcrumbOpen = !breadcrumbOpen)}
        aria-expanded={breadcrumbOpen}
        aria-label="Breadcrumb navigation"
      >
        <span class="rounded bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground">•••</span>
        <ChevronRight class="h-4 w-4 text-muted-foreground/50 shrink-0" aria-hidden="true" />
        <span class="font-semibold text-foreground">{breadcrumbs[breadcrumbs.length - 1].label}</span>
        <ChevronDown class="h-4 w-4 text-muted-foreground ml-1 transition-transform {breadcrumbOpen ? 'rotate-180' : ''}" />
      </button>

      {#if breadcrumbOpen}
        <!-- Invisible backdrop: tapping anywhere outside the dropdown closes it -->
        <button
          type="button"
          class="fixed inset-0 z-10"
          aria-label="Close breadcrumb trail"
          onclick={() => (breadcrumbOpen = false)}
        ></button>

        <!-- Dropdown panel: appears directly below the sticky header bar -->
        <div class="absolute left-0 right-0 top-full z-20 border-b border-border bg-card shadow-lg">
          {#each breadcrumbs as crumb, i}
            <div class="flex items-center gap-3 border-b border-border/50 px-5 py-3 last:border-0">
              <div class="h-1.5 w-1.5 shrink-0 rounded-full {i === breadcrumbs.length - 1 ? 'bg-primary' : 'bg-muted-foreground/30'}"></div>
              {#if crumb.href && i < breadcrumbs.length - 1}
                <Link
                  href={crumb.href}
                  class="text-sm text-muted-foreground hover:text-foreground transition-colors"
                  onclick={() => (breadcrumbOpen = false)}
                >
                  {crumb.label}
                </Link>
              {:else}
                <span class="text-sm font-semibold text-foreground">{crumb.label}</span>
              {/if}
            </div>
          {/each}
        </div>
      {/if}
    </div>
  {/if}
```

- [ ] **Step 5: Verify the mobile dropdown in browser**

After a few per-page tasks are done (e.g. after Task 5), navigate to `/admin/seasons/create`. Open browser DevTools → toggle device toolbar → select a phone size (e.g. iPhone SE, 375px wide).

Expected on mobile:
- Header shows: `••• › Create ▾`
- Tapping it opens a dropdown with "Academic Years" (link) and "Create" (highlighted, no link)
- Tapping "Academic Years" navigates to `/admin/seasons` and closes the dropdown
- Tapping outside the dropdown also closes it

Expected on desktop (widen browser to > 768px):
- Header shows the full inline trail: `Academic Years › Create`
- No dropdown button visible

- [ ] **Step 6: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "feat: add mobile breadcrumb dropdown to AuthenticatedLayout header"
```

---

## Task 4: Dashboard page

**File:** `resources/js/Pages/Dashboard.svelte`

- [ ] **Step 1: Add `breadcrumbs` const**

In the `<script>` block, after the `let { ... } = $props()` line (or at the end of the variable declarations), add:
```js
const breadcrumbs = [{ label: 'Dashboard' }];
```

- [ ] **Step 2: Remove `<svelte:head>` block (if present)**

Search the file for `<svelte:head>`. If found, delete the entire block:
```html
<svelte:head>
  <title>...</title>
</svelte:head>
```
If there is no `<svelte:head>` in Dashboard.svelte, skip this step.

- [ ] **Step 3: Pass `breadcrumbs` to the layout**

Find:
```html
<AuthenticatedLayout>
```
Replace with:
```html
<AuthenticatedLayout {breadcrumbs}>
```

- [ ] **Step 4: Remove the `<h1>` from the page body**

Search for `<h1` inside Dashboard.svelte. The dashboard h1 uses class `text-3xl font-bold tracking-tight text-foreground`. Delete the entire `<h1>...</h1>` line. If the surrounding `<div>` contained only the h1 and nothing else, remove that div too. If the div also contained a subtitle `<p>`, keep the `<p>`.

- [ ] **Step 5: Verify in browser**

Navigate to `/dashboard`. Browser tab should say "Dashboard - SecureCAT". The header bar should show "Dashboard". No h1 inside the page body.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Dashboard.svelte
git commit -m "feat: add breadcrumbs to Dashboard, remove body h1"
```

---

## Task 5: Seasons section (Index, Create, Edit)

**Files:**
- `resources/js/Pages/Admin/Seasons/Index.svelte`
- `resources/js/Pages/Admin/Seasons/Create.svelte`
- `resources/js/Pages/Admin/Seasons/Edit.svelte`

### Seasons/Index

- [ ] **Step 1: Add `breadcrumbs` const**

In the `<script>` block, after `let { seasons } = $props();`, add:
```js
const breadcrumbs = [{ label: 'Academic Years' }];
```

- [ ] **Step 2: Remove `<svelte:head>` block**

Delete:
```html
<svelte:head>
  <title>Academic Years - SecureCAT</title>
</svelte:head>
```

- [ ] **Step 3: Pass `breadcrumbs` to the layout**

Change `<AuthenticatedLayout>` to `<AuthenticatedLayout {breadcrumbs}>`.

- [ ] **Step 4: Remove only the `<h1>` — keep the subtitle `<p>`**

Find and delete just this line:
```html
<h1 class="text-2xl font-bold">Academic Years</h1>
```
Keep the `<p class="mt-1 text-sm text-muted-foreground">` subtitle directly below it. The wrapping `<div>` can stay (it also contains the "Add Season" and "Add Course" buttons).

### Seasons/Create

- [ ] **Step 5: Add `breadcrumbs` const**

In the `<script>` block, after `function submitForm(e) {...}` or at the end of the script block, add:
```js
const breadcrumbs = [
  { label: 'Academic Years', href: '/admin/seasons' },
  { label: 'Create' },
];
```

- [ ] **Step 6: Remove `<svelte:head>` block**

Delete:
```html
<svelte:head>
  <title>Add Season - SecureCAT</title>
</svelte:head>
```

- [ ] **Step 7: Pass `breadcrumbs` to the layout**

Change `<AuthenticatedLayout>` to `<AuthenticatedLayout {breadcrumbs}>`.

- [ ] **Step 8: Remove the "Back to seasons" link and the `<h1>` together**

Find and delete this entire block (it's the first thing inside `<AuthenticatedLayout>`):
```html
<div class="flex items-center gap-4">
  <Link href="/admin/seasons" class="text-sm text-muted-foreground hover:text-foreground">Back to seasons</Link>
  <h1 class="text-2xl font-bold">Add Season</h1>
</div>
```
> **Why remove both?** The breadcrumb in the header now provides the "Academic Years" link for navigation back. The "Back to seasons" button is redundant.

### Seasons/Edit

- [ ] **Step 9: Add `breadcrumbs` const**

```js
const breadcrumbs = [
  { label: 'Academic Years', href: '/admin/seasons' },
  { label: 'Edit' },
];
```

- [ ] **Step 10: Remove `<svelte:head>` block**

Delete:
```html
<svelte:head>
  <title>Edit Season - SecureCAT</title>
</svelte:head>
```

- [ ] **Step 11: Pass `breadcrumbs` and remove "Back" link + h1**

Change layout tag. Find and delete:
```html
<div class="flex items-center gap-4">
  <Link href="/admin/seasons" class="text-sm text-muted-foreground hover:text-foreground">Back to seasons</Link>
  <h1 class="text-2xl font-bold">Edit Season</h1>
</div>
```

- [ ] **Step 12: Verify in browser**

| URL | Header shows | Tab title |
|-----|-------------|-----------|
| `/admin/seasons` | Academic Years | Academic Years - SecureCAT |
| `/admin/seasons/create` | Academic Years › Create | Create - SecureCAT |
| `/admin/seasons/{id}/edit` | Academic Years › Edit | Edit - SecureCAT |

No h1 inside page body. No "Back to seasons" link.

- [ ] **Step 13: Commit**

```bash
git add resources/js/Pages/Admin/Seasons/Index.svelte \
        resources/js/Pages/Admin/Seasons/Create.svelte \
        resources/js/Pages/Admin/Seasons/Edit.svelte
git commit -m "feat: add breadcrumbs to Seasons pages, remove body h1 and back links"
```

---

## Task 6: TestScheduling/Index — update existing breadcrumbs

**File:** `resources/js/Pages/Admin/TestScheduling/Index.svelte`

This page already passes breadcrumbs to the layout. Two things need fixing: (1) the label still says `'Test Scheduling'` instead of `'Exam Scheduling'`, and (2) it has a `pageTitle` derivation and `<svelte:head>` that must go.

- [ ] **Step 1: Update the breadcrumbs label**

Find:
```js
const breadcrumbs = $derived(
  view === 'proctor'
    ? [{ label: 'My Sessions' }]
    : [{ label: 'Test Scheduling' }]
);
```
Replace with:
```js
const breadcrumbs = $derived(
  view === 'proctor'
    ? [{ label: 'My Sessions' }]
    : [{ label: 'Exam Scheduling' }]
);
```

- [ ] **Step 2: Remove `pageTitle` derivation**

Find and delete:
```js
const pageTitle = $derived(isProctorView ? 'My Sessions' : 'Exam Scheduling');
```

- [ ] **Step 3: Remove `<svelte:head>` block**

Find and delete:
```html
<svelte:head>
  <title>{pageTitle} - SecureCAT</title>
</svelte:head>
```

- [ ] **Step 4: Check `pageDescription` is still intact**

Search the file for `pageDescription`. If it's used inside a `<p>` tag in the template, keep the `const pageDescription = $derived(...)` line — it's used for a subtitle paragraph and is not the same as `pageTitle`. Only `pageTitle` is removed.

- [ ] **Step 5: Confirm there's no `<h1>` in the TestScheduling/Index template**

Search for `<h1` in this file. The TestScheduling/Index renders a filter row and table/card view — it should have no `<h1>`. If one exists, remove it.

- [ ] **Step 6: Verify in browser**

As admin: `/admin/test-scheduling` → header "Exam Scheduling", tab "Exam Scheduling - SecureCAT".
As proctor: header "My Sessions", tab "My Sessions - SecureCAT".

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Index.svelte
git commit -m "feat: update TestScheduling/Index breadcrumb label to Exam Scheduling"
```

---

## Task 7: TestScheduling sub-pages (Show, Create, Edit)

**Files:**
- `resources/js/Pages/Admin/TestScheduling/Show.svelte`
- `resources/js/Pages/Admin/TestScheduling/Create.svelte`
- `resources/js/Pages/Admin/TestScheduling/Edit.svelte`

All three follow the same pattern: add breadcrumbs, remove `<svelte:head>`, pass breadcrumbs to layout, remove `<h1>` and any back link.

### TestScheduling/Show

- [ ] **Step 1: Add `breadcrumbs` const**

In the `<script>` block, after the `let { ... } = $props()` line:
```js
const breadcrumbs = [
  { label: 'Exam Scheduling', href: '/admin/test-scheduling' },
  { label: 'View' },
];
```

- [ ] **Step 2: Remove `<svelte:head>`, pass breadcrumbs, remove `<h1>` and back link**

Delete the `<svelte:head>` block. Change `<AuthenticatedLayout>` to `<AuthenticatedLayout {breadcrumbs}>`. Find and delete `<h1 class="text-2xl font-bold">Exam Session</h1>`. If there's a back link like `<Link href="/admin/test-scheduling" ...>Back to ...</Link>`, delete it.

### TestScheduling/Create

- [ ] **Step 3: Add `breadcrumbs` const**

```js
const breadcrumbs = [
  { label: 'Exam Scheduling', href: '/admin/test-scheduling' },
  { label: 'Create' },
];
```

- [ ] **Step 4: Remove `<svelte:head>`, pass breadcrumbs, remove `<h1>` and back link**

Delete `<svelte:head>`. Change layout tag. Remove `<h1 class="text-2xl font-bold">Create Exam Session</h1>`. Remove any back link.

### TestScheduling/Edit

- [ ] **Step 5: Add `breadcrumbs` const**

```js
const breadcrumbs = [
  { label: 'Exam Scheduling', href: '/admin/test-scheduling' },
  { label: 'Edit' },
];
```

- [ ] **Step 6: Remove `<svelte:head>`, pass breadcrumbs, remove `<h1>` and back link**

Delete `<svelte:head>`. Change layout tag. Remove `<h1 class="text-2xl font-bold">Edit Exam Session</h1>`. Remove any back link.

- [ ] **Step 7: Verify in browser**

| URL | Header |
|-----|--------|
| `/admin/test-scheduling/{id}` | Exam Scheduling › View |
| `/admin/test-scheduling/create` | Exam Scheduling › Create |
| `/admin/test-scheduling/{id}/edit` | Exam Scheduling › Edit |

- [ ] **Step 8: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Show.svelte \
        resources/js/Pages/Admin/TestScheduling/Create.svelte \
        resources/js/Pages/Admin/TestScheduling/Edit.svelte
git commit -m "feat: add breadcrumbs to TestScheduling Show/Create/Edit"
```

---

## Task 8: TestScheduling/Monitoring (the main bug fix)

**File:** `resources/js/Pages/Admin/TestScheduling/Monitoring.svelte`

This is the most visible bug: the Monitoring page shows "Dashboard" in the header. After this task it will show "Exam Monitoring".

- [ ] **Step 1: Add `breadcrumbs` const**

In the `<script>` block, after `let { sessions = [] } = $props();`:
```js
const breadcrumbs = [{ label: 'Exam Monitoring' }];
```

- [ ] **Step 2: Remove `<svelte:head>` block**

Delete:
```html
<svelte:head>
  <title>Session Monitor - SecureCAT</title>
</svelte:head>
```

- [ ] **Step 3: Pass `breadcrumbs` to the layout**

Change:
```html
<AuthenticatedLayout>
```
To:
```html
<AuthenticatedLayout {breadcrumbs}>
```

- [ ] **Step 4: Remove the h1 wrapper, keep the subtitle `<p>`**

Find and remove this entire block:
```html
<div>
  <h1 class="text-2xl font-bold flex items-center gap-2">
    <Activity class="h-6 w-6" />
    Session Monitor
  </h1>
  <p class="mt-1 text-sm text-muted-foreground">
    Live status of in-progress exam sessions. Data refreshes every 15 seconds.
  </p>
</div>
```

Replace with just the subtitle paragraph:
```html
<p class="text-sm text-muted-foreground">
  Live status of in-progress exam sessions. Data refreshes every 15 seconds.
</p>
```

- [ ] **Step 5: Clean up unused `Activity` import**

Since `Activity` was only used in the now-deleted h1, check the rest of the file for any other uses. Search for `Activity` — if it only appears in the import line and nowhere else in the template, update the import:

Change:
```js
import { ClipboardList, Activity } from 'lucide-svelte';
```
To:
```js
import { ClipboardList } from 'lucide-svelte';
```

- [ ] **Step 6: Verify in browser**

Navigate to `/admin/test-scheduling/monitoring`. The header should now say "Exam Monitoring" (previously showed "Dashboard"). Browser tab: "Exam Monitoring - SecureCAT". No h1 in page body — just the subtitle paragraph.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Monitoring.svelte
git commit -m "fix: Exam Monitoring page now shows correct header (was Dashboard)"
```

---

## Task 9: TestAdmin/Index (My Sessions — proctor route)

**File:** `resources/js/Pages/Admin/TestAdmin/Index.svelte`

- [ ] **Step 1: Add `breadcrumbs` const**

In the `<script>` block, add:
```js
const breadcrumbs = [{ label: 'My Sessions' }];
```

- [ ] **Step 2: Remove `<svelte:head>`, pass breadcrumbs, remove `<h1>`**

Delete the `<svelte:head>` block. Change `<AuthenticatedLayout>` to `<AuthenticatedLayout {breadcrumbs}>`. Find and delete `<h1 class="text-2xl font-bold">My Sessions</h1>` along with its wrapper div if the div only held the h1.

- [ ] **Step 3: Verify and commit**

```bash
git add resources/js/Pages/Admin/TestAdmin/Index.svelte
git commit -m "feat: add breadcrumbs to TestAdmin/Index (My Sessions)"
```

---

## Task 10: ExamDomains (Index, Create, Edit)

**Files:**
- `resources/js/Pages/Admin/ExamDomains/Index.svelte`
- `resources/js/Pages/Admin/ExamDomains/Create.svelte`
- `resources/js/Pages/Admin/ExamDomains/Edit.svelte`

Canonical label: **"Aptitude Areas"** — replaces both "Exam Domains" (nav) and "Exam pillars" (page h1).

### ExamDomains/Index

- [ ] **Step 1: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>`**

```js
const breadcrumbs = [{ label: 'Aptitude Areas' }];
```
Remove `<svelte:head>`. Change layout tag. Remove `<h1 class="text-2xl font-bold">Exam pillars</h1>`. Keep the subtitle `<p>`.

### ExamDomains/Create

- [ ] **Step 2: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Aptitude Areas', href: '/admin/exam-domains' },
  { label: 'Create' },
];
```
Remove `<svelte:head>`. Change layout tag. Remove `<h1 class="text-2xl font-bold">Add exam pillar</h1>`. Remove any back link.

### ExamDomains/Edit

- [ ] **Step 3: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Aptitude Areas', href: '/admin/exam-domains' },
  { label: 'Edit' },
];
```
Remove `<svelte:head>`. Change layout tag. Remove `<h1 class="text-2xl font-bold">Edit exam pillar</h1>`. Remove any back link.

- [ ] **Step 4: Verify in browser**

| URL | Header |
|-----|--------|
| `/admin/exam-domains` | Aptitude Areas |
| `/admin/exam-domains/create` | Aptitude Areas › Create |
| `/admin/exam-domains/{id}/edit` | Aptitude Areas › Edit |

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Admin/ExamDomains/Index.svelte \
        resources/js/Pages/Admin/ExamDomains/Create.svelte \
        resources/js/Pages/Admin/ExamDomains/Edit.svelte
git commit -m "feat: add breadcrumbs to ExamDomains pages, label is Aptitude Areas"
```

---

## Task 11: KnowledgeDocuments (Index, Create, Edit, Import)

**Files:**
- `resources/js/Pages/Admin/KnowledgeDocuments/Index.svelte`
- `resources/js/Pages/Admin/KnowledgeDocuments/Create.svelte`
- `resources/js/Pages/Admin/KnowledgeDocuments/Edit.svelte`
- `resources/js/Pages/Admin/KnowledgeDocuments/Import.svelte`

Canonical label: **"Knowledge Documents"**.

### Index

- [ ] **Step 1: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>`**

```js
const breadcrumbs = [{ label: 'Knowledge Documents' }];
```
Remove `<h1 class="text-2xl font-bold">Knowledge documents</h1>`. Keep subtitle `<p>`.

### Create

- [ ] **Step 2: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Knowledge Documents', href: '/admin/knowledge-documents' },
  { label: 'Create' },
];
```
Remove `<h1 class="text-2xl font-bold">Add knowledge document</h1>`.

### Edit

- [ ] **Step 3: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Knowledge Documents', href: '/admin/knowledge-documents' },
  { label: 'Edit' },
];
```
Remove `<h1 class="text-2xl font-bold">Edit knowledge document</h1>`.

### Import

- [ ] **Step 4: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Knowledge Documents', href: '/admin/knowledge-documents' },
  { label: 'Import' },
];
```
Remove `<h1 class="text-2xl font-bold">Import from CSV</h1>`.

- [ ] **Step 5: Verify and commit**

```bash
git add resources/js/Pages/Admin/KnowledgeDocuments/Index.svelte \
        resources/js/Pages/Admin/KnowledgeDocuments/Create.svelte \
        resources/js/Pages/Admin/KnowledgeDocuments/Edit.svelte \
        resources/js/Pages/Admin/KnowledgeDocuments/Import.svelte
git commit -m "feat: add breadcrumbs to KnowledgeDocuments pages"
```

---

## Task 12: ResultSheetTemplates (Index, Create, Edit)

**Files:**
- `resources/js/Pages/Admin/ResultSheetTemplates/Index.svelte`
- `resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte`
- `resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte`

Canonical label: **"Result Sheet Templates"**.

### Index

- [ ] **Step 1: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>`**

```js
const breadcrumbs = [{ label: 'Result Sheet Templates' }];
```
Remove `<h1 class="text-2xl font-bold">Result sheet templates</h1>`.

### Create

- [ ] **Step 2: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Result Sheet Templates', href: '/admin/result-sheet-templates' },
  { label: 'Create' },
];
```
Remove `<h1 class="text-2xl font-bold">Create result sheet template</h1>`.

### Edit

- [ ] **Step 3: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Result Sheet Templates', href: '/admin/result-sheet-templates' },
  { label: 'Edit' },
];
```
Remove `<h1 class="text-2xl font-bold">Edit result sheet template</h1>`.

- [ ] **Step 4: Verify and commit**

```bash
git add resources/js/Pages/Admin/ResultSheetTemplates/Index.svelte \
        resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte \
        resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte
git commit -m "feat: add breadcrumbs to ResultSheetTemplates pages"
```

---

## Task 13: AdmissionSlipTemplates/Index

**File:** `resources/js/Pages/Admin/AdmissionSlipTemplates/Index.svelte`

- [ ] **Step 1: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>`**

```js
const breadcrumbs = [{ label: 'Admission Slip Templates' }];
```
Remove `<h1 class="text-2xl font-bold">Admission slip templates</h1>`.

- [ ] **Step 2: Verify and commit**

```bash
git add resources/js/Pages/Admin/AdmissionSlipTemplates/Index.svelte
git commit -m "feat: add breadcrumbs to AdmissionSlipTemplates/Index"
```

---

## Task 14: Rooms/Edit

**File:** `resources/js/Pages/Admin/Rooms/Edit.svelte`

> Only `Rooms/Edit` is in scope per the spec. `Rooms/Index` and `Rooms/Create` are out of scope.

- [ ] **Step 1: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Rooms', href: '/admin/rooms' },
  { label: 'Edit' },
];
```
Remove `<svelte:head>`. Change `<AuthenticatedLayout>` to `<AuthenticatedLayout {breadcrumbs}>`. Remove `<h1 class="text-2xl font-bold">Edit Room</h1>`. Remove any back link.

- [ ] **Step 2: Verify and commit**

```bash
git add resources/js/Pages/Admin/Rooms/Edit.svelte
git commit -m "feat: add breadcrumbs to Rooms/Edit"
```

---

## Task 15: Users (Create, Edit)

**Files:**
- `resources/js/Pages/Admin/Users/Create.svelte`
- `resources/js/Pages/Admin/Users/Edit.svelte`

### Users/Create

- [ ] **Step 1: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Users', href: '/admin/users' },
  { label: 'Create' },
];
```
Remove `<h1 class="text-2xl font-bold">Create User</h1>`.

### Users/Edit

- [ ] **Step 2: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>` and back link**

```js
const breadcrumbs = [
  { label: 'Users', href: '/admin/users' },
  { label: 'Edit' },
];
```
Remove `<h1 class="text-2xl font-bold">Edit User</h1>`.

- [ ] **Step 3: Verify and commit**

```bash
git add resources/js/Pages/Admin/Users/Create.svelte \
        resources/js/Pages/Admin/Users/Edit.svelte
git commit -m "feat: add breadcrumbs to Users Create/Edit"
```

---

## Task 16: Logs/Index and Settings/Index

**Files:**
- `resources/js/Pages/Admin/Logs/Index.svelte`
- `resources/js/Pages/Admin/Settings/Index.svelte`

### Logs/Index

- [ ] **Step 1: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>`**

```js
const breadcrumbs = [{ label: 'Audit Log' }];
```
Remove `<h1 class="text-2xl font-bold">Audit log</h1>`.

### Settings/Index

- [ ] **Step 2: Add breadcrumbs, remove `<svelte:head>`, pass to layout, remove `<h1>`**

```js
const breadcrumbs = [{ label: 'Settings' }];
```
Remove `<h1 class="text-2xl font-bold">Settings</h1>`.

- [ ] **Step 3: Verify and commit**

```bash
git add resources/js/Pages/Admin/Logs/Index.svelte \
        resources/js/Pages/Admin/Settings/Index.svelte
git commit -m "feat: add breadcrumbs to Logs and Settings pages"
```

---

## Task 17: Audit remaining authenticated pages

**Folders to check:** `Applications/`, `Grading/`, `Consultation/`, `Portal/`

These pages are noted in the spec as already having breadcrumbs — we just need to verify labels are correct and remove any stray `<h1>` elements.

- [ ] **Step 1: Search for `<h1>` in these page folders**

Run in the terminal (from project root):
```bash
grep -rn "<h1" resources/js/Pages/Applications/
grep -rn "<h1" resources/js/Pages/Grading/
grep -rn "<h1" resources/js/Pages/Consultation/
grep -rn "<h1" resources/js/Pages/Portal/
```
For each `<h1>` found in a page that uses `AuthenticatedLayout`, remove it (keep any subtitle `<p>` below it).

- [ ] **Step 2: Search for `<svelte:head>` in these folders**

```bash
grep -rn "svelte:head" resources/js/Pages/Applications/
grep -rn "svelte:head" resources/js/Pages/Grading/
grep -rn "svelte:head" resources/js/Pages/Consultation/
grep -rn "svelte:head" resources/js/Pages/Portal/
```
For each `<svelte:head>` found in a page that uses `AuthenticatedLayout`, delete it (the layout now owns the `<title>`).

- [ ] **Step 3: Verify breadcrumb labels match canonical terms**

Open each of these pages in the browser and check the header. The canonical labels are: "Applications", "Grading", "Release & Consultation" (check sidebar nav), and whatever the Portal label is.

- [ ] **Step 4: Fix any issues and commit**

```bash
git add resources/js/Pages/Applications/ \
        resources/js/Pages/Grading/ \
        resources/js/Pages/Consultation/ \
        resources/js/Pages/Portal/
git commit -m "chore: audit and fix breadcrumbs/h1 in remaining authenticated pages"
```

---

## Task 18: Final verification sweep

- [ ] **Step 1: Search for any remaining `<h1>` tags in admin pages**

```bash
grep -rn "<h1" resources/js/Pages/Admin/
grep -rn "<h1" resources/js/Pages/Dashboard.svelte
```
Expected: **zero results**. If any remain, apply the standard fix to those pages (add breadcrumbs, remove h1).

- [ ] **Step 2: Search for any remaining `<svelte:head>` in authenticated pages**

```bash
grep -rn "svelte:head" resources/js/Pages/Admin/
grep -rn "svelte:head" resources/js/Pages/Dashboard.svelte
```
Expected: **zero results**.

- [ ] **Step 3: Search for any remaining `pageTitle` references in authenticated pages**

```bash
grep -rn "pageTitle" resources/js/Pages/
grep -rn "pageTitle" resources/js/Layouts/
```
Expected: **zero results**. If any remain, remove them.

- [ ] **Step 4: Verify all sidebar nav labels in browser**

Log in as super_admin. Expand all nav sections and confirm:

**Registrar Office:**
- Academic Years ✓
- Applications ✓
- Exam Scheduling ✓

**Guidance Office:**
- My Sessions ✓
- Exam Monitoring ✓
- Grading ✓
- Release & Consultation ✓ (if feature flag enabled)

**Administration:**
- Users ✓
- Settings ✓
- Audit Log ✓
- Knowledge Documents ✓
- Aptitude Areas ✓
- Admission Slip Templates ✓
- Result Sheet Templates ✓

- [ ] **Step 5: Verify breadcrumb header on key pages**

Visit each URL and confirm the header trail:

| URL | Expected header |
|-----|----------------|
| `/dashboard` | Dashboard |
| `/admin/seasons` | Academic Years |
| `/admin/seasons/create` | Academic Years › Create |
| `/admin/seasons/{id}/edit` | Academic Years › Edit |
| `/admin/test-scheduling` | Exam Scheduling |
| `/admin/test-scheduling/{id}` | Exam Scheduling › View |
| `/admin/test-scheduling/create` | Exam Scheduling › Create |
| `/admin/test-scheduling/{id}/edit` | Exam Scheduling › Edit |
| `/admin/test-scheduling/monitoring` | Exam Monitoring |
| `/admin/exam-domains` | Aptitude Areas |
| `/admin/exam-domains/create` | Aptitude Areas › Create |
| `/admin/knowledge-documents` | Knowledge Documents |
| `/admin/result-sheet-templates` | Result Sheet Templates |
| `/admin/admission-slip-templates` | Admission Slip Templates |
| `/admin/rooms/{id}/edit` | Rooms › Edit |
| `/admin/users/create` | Users › Create |
| `/admin/logs` | Audit Log |
| `/admin/settings` | Settings |

- [ ] **Step 6: Test mobile dropdown on a 2-crumb page**

Open `/admin/seasons/create`. In browser DevTools, toggle device toolbar and set width to 375px (iPhone SE).

Check:
- Header shows `••• › Create ▾` (not the full trail)
- Tapping `••• › Create ▾` reveals a dropdown with "Academic Years" (link) and "Create" (no link, highlighted with primary-colored dot)
- Tapping "Academic Years" in the dropdown navigates to `/admin/seasons` and closes the dropdown
- Tapping anywhere outside the dropdown closes it

- [ ] **Step 7: Test browser `<title>` matches breadcrumb**

On each page, check the browser tab. It should show `{Page Name} - SecureCAT`. For example: "Academic Years - SecureCAT", "Exam Monitoring - SecureCAT".

- [ ] **Step 8: Final commit**

If any last-minute fixes were made during verification:
```bash
git add -u
git commit -m "chore: final nav and header consistency cleanup"
```

---

## Success Criteria Checklist

These come from the spec. All must pass before this feature is complete:

- [ ] No `<h1>` exists inside any authenticated page body
- [ ] Every page passes a `breadcrumbs` array with the canonical label
- [ ] Sidebar nav labels match the canonical label map exactly
- [ ] On mobile, breadcrumbs with 2+ crumbs show collapsed `••• › Current` with working dropdown
- [ ] `<title>` in browser tab reflects current page via layout (no per-page `<svelte:head>`)
- [ ] `/admin/test-scheduling/monitoring` no longer shows "Dashboard" in the header
