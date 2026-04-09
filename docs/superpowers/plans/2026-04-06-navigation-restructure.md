# Navigation & Route Restructure Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restructure the sidebar navigation and rename the exam scheduling route to reflect the finalized UX design — role-aware sections, no nested nav items, templates moved to Administration.

**Architecture:** Navigation is defined in `AuthenticatedLayout.svelte` as a derived `navSections` array. Routes are defined in `routes/web.php`. Renaming `/admin/exam-sessions` to `/admin/test-scheduling` requires updating all route names, controller references, and Inertia links across the Svelte pages. In-page "Add Course" and "Add Room" buttons are implemented as primary action buttons on the parent pages.

**Tech Stack:** Laravel 12 (routes, controllers), Svelte + Inertia (navigation, pages), PHP 8.2

---

## File Map

### Files Modified
| File | Responsibility |
|------|----------------|
| `routes/web.php` | Rename `exam-sessions` route prefix → `test-scheduling` |
| `app/Http/Controllers/Admin/ExamSessionController.php` | Update ~15 `redirect()->route('admin.exam-sessions.*')` calls → `admin.test-scheduling.*` |
| `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php` | Update 1 redirect route name |
| `app/Http/Middleware/HandleInertiaRequests.php` | Update breadcrumb `admin.exam-sessions` route name |
| `app/Services/DashboardService.php` | Update hardcoded `/admin/exam-sessions` href |
| `resources/js/Layouts/AuthenticatedLayout.svelte` | Rewrite `navSections` per role matrix; add collapsible Administration |
| `resources/js/Components/ScheduleAssistantPanel.svelte` | Update 3 hardcoded `/admin/exam-sessions/...` URLs |
| `resources/js/Components/SessionRoster.svelte` | Update `/admin/exam-sessions/...` URLs (different from Proctor/SessionRoster.svelte) |
| `resources/js/Pages/Admin/ExamSessions/Index.svelte` | Rename to `TestScheduling/Index.svelte`, add "Add Room" button |
| `resources/js/Pages/Admin/ExamSessions/Create.svelte` | Rename to `TestScheduling/Create.svelte` |
| `resources/js/Pages/Admin/ExamSessions/Edit.svelte` | Rename to `TestScheduling/Edit.svelte` |
| `resources/js/Pages/Admin/ExamSessions/EditForm.svelte` | Rename to `TestScheduling/EditForm.svelte`, update URLs |
| `resources/js/Pages/Admin/ExamSessions/Monitoring.svelte` | Rename to `TestScheduling/Monitoring.svelte` |
| `resources/js/Pages/Admin/ExamSessions/Show.svelte` | Rename to `TestScheduling/Show.svelte` |
| `resources/js/Pages/Admin/Seasons/Index.svelte` | Add "Add Course" button inside the page |
| `resources/js/Pages/Dashboard.svelte` | Update 4 hardcoded exam-sessions references |
| `resources/js/Components/blocks/Hero.svelte` | Update any hardcoded nav links |
| `docs/superpowers/plans/2026-04-06-navigation-restructure.md` | This file |

### No Changes Needed
- `resources/js/Pages/Admin/Courses/` — stays at `/admin/courses`, accessed via in-page button only (not in nav)
- `resources/js/Pages/Admin/Rooms/` — stays at `/admin/rooms`, accessed via in-page button only (not in nav)
- `resources/js/Pages/Admin/ResultSheetTemplates/` — stays in Administration
- `resources/js/Pages/Admin/AdmissionSlipTemplates/` — stays in Administration
- `resources/js/Pages/Proctor/SessionRoster.svelte` — a different file from `Components/SessionRoster.svelte`; still needs updating but covered in Task 7

---

## Role Navigation Matrix

| Item | super_admin | admin | test_administrator | proctor | staff |
|------|-------------|-------|-------------------|--------|-------|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| **REGISTRAR OFFICE** section label | ✓ | ✗ | ✗ | ✗ | ✗ |
| Seasons | ✓ | ✓ | ✗ | ✗ | ✗ |
| Applications | ✓ | ✓ | ✓ | ✗ | ✓ |
| Test Scheduling | ✓ | ✓ | ✗ | ✗ | ✗ |
| **GUIDANCE OFFICE** section label | ✓ | ✗ | ✗ | ✗ | ✗ |
| My Sessions | ✓ | ✗ | ✓ | ✓ | ✗ |
| Session Monitor | ✓ | ✗ | ✓ | ✓ | ✗ |
| Grading | ✓ | ✗ | ✓ | ✗ | ✗ |
| Release & Consultation | ✓ | ✗ | ✓ | ✗ | ✗ |
| **ADMINISTRATION** section (collapsible) | ✓ | ✗ | ✗ | ✗ | ✗ |
| Users | ✓ | ✗ | ✗ | ✗ | ✗ |
| Settings | ✓ | ✗ | ✗ | ✗ | ✗ |
| Audit Log | ✓ | ✗ | ✗ | ✗ | ✗ |
| Knowledge Docs | ✓ | ✗ | ✗ | ✗ | ✗ |
| Exam Domains | ✓ | ✗ | ✗ | ✗ | ✗ |
| Admission Slip Templates | ✓ | ✗ | ✗ | ✗ | ✗ |
| Result Templates | ✓ | ✗ | ✗ | ✗ | ✗ |

**In-page buttons (no sidebar nav entry):**
- Seasons page → "Add Course" button
- Test Scheduling page → "Add Room" button

---

## Task 1: Rename Routes — `exam-sessions` → `test-scheduling`

**File:** `routes/web.php:91-119`

- [ ] **Step 1: Update route prefix and names**

In `routes/web.php`, update all `exam-sessions` occurrences within the admin route group:

- `Route::get('exam-sessions', ...)` → `Route::get('test-scheduling', ...)`
- `Route::get('exam-sessions/create', ...)` → `Route::get('test-scheduling/create', ...)`
- `Route::get('exam-sessions/monitoring', ...)` → `Route::get('test-scheduling/monitoring', ...)`
- All other `exam-sessions/*` paths → `test-scheduling/*`
- Route names: `exam-sessions.index` → `test-scheduling.index`, etc.
- Route::resource `exam-sessions` → `test-scheduling`

**Important:** `Route::resource('seasons', ...)` and `Route::resource('courses', ...)` stay unchanged — they are separate resources.

- [ ] **Step 2: Verify `Route::resource` auto-generated routes**

The `Route::resource('test-scheduling', ...)` call generates these RESTful routes:
```
GET     /admin/test-scheduling           → index
GET     /admin/test-scheduling/create    → create
POST    /admin/test-scheduling           → store
GET     /admin/test-scheduling/{exam_session}         → show
GET     /admin/test-scheduling/{exam_session}/edit   → edit
PUT     /admin/test-scheduling/{exam_session}         → update
```

The `exam-sessions/{exam_session}` route for `publish`, `unpublish`, `release-date`, `reopen`, `assign-applicants`, `remove-applicant` should also use `test-scheduling/{exam_session}` path and name.

- [ ] **Step 3: Update redirect route**

```php
Route::get('/proctor', fn () => redirect()->route('admin.test-scheduling.index'))->middleware('role:super_admin,proctor');
Route::get('/admin/exam-sessions/schedule-assistant', fn () => redirect()->route('admin.test-scheduling.index'))->name('admin.test-scheduling.schedule-assistant.index');
```

- [ ] **Step 4: Commit**

```bash
git add routes/web.php
git commit -m "refactor: rename exam-sessions route to test-scheduling"
```

---

## Task 1.5: Update PHP Controller Route References

> Critical: Complete this after Task 1 (routes renamed) but before Task 3 (pages moved/renamed). Failing to do this first will cause 500 errors on every redirect.

**Files:** PHP controllers, middleware, and services that call `redirect()->route('admin.exam-sessions.*')`

- [ ] **Step 1: Update `ExamSessionController.php`**

Do a global find/replace in `ExamSessionController.php`:
```
redirect()->route('admin.exam-sessions.index')  → redirect()->route('admin.test-scheduling.index')
redirect()->route('admin.exam-sessions.create')  → redirect()->route('admin.test-scheduling.create')
redirect()->route('admin.exam-sessions.show')    → redirect()->route('admin.test-scheduling.show')
redirect()->route('admin.exam-sessions.edit')    → redirect()->route('admin.test-scheduling.edit')
redirect()->route('admin.exam-sessions.store')  → redirect()->route('admin.test-scheduling.store')
redirect()->route('admin.exam-sessions.update') → redirect()->route('admin.test-scheduling.update')
```
All `admin.exam-sessions.*` route names → `admin.test-scheduling.*`.

- [ ] **Step 2: Update `ExamSchedulingAssistantController.php`**

Update the `schedule-assistant.index` redirect:
```php
redirect()->route('admin.exam-sessions.schedule-assistant.index')
→ redirect()->route('admin.test-scheduling.schedule-assistant.index')
```
Also update the route name definition in web.php from `exam-sessions.schedule-assistant.index` to `test-scheduling.schedule-assistant.index`.

- [ ] **Step 3: Update `HandleInertiaRequests.php` middleware**

In `app/Http/Middleware/HandleInertiaRequests.php`, update any breadcrumb or page title route references:
```php
'route' => 'admin.exam-sessions.index'  → 'route' => 'admin.test-scheduling.index'
```

- [ ] **Step 4: Update `DashboardService.php`**

In `app/Services/DashboardService.php`, update hardcoded href:
```php
'/admin/exam-sessions'  → '/admin/test-scheduling'
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/ExamSessionController.php
git add app/Http/Controllers/Admin/ExamSchedulingAssistantController.php
git add app/Http/Middleware/HandleInertiaRequests.php
git add app/Services/DashboardService.php
git commit -m "fix: update PHP route name references from exam-sessions to test-scheduling"
```

---

## Task 2: Update `AuthenticatedLayout.svelte` — Nav Structure + Collapsible Administration

**File:** `resources/js/Layouts/AuthenticatedLayout.svelte:44-71`

- [ ] **Step 1: Write the new navSections array**

Replace the `navSections` derived value with this structure:

```javascript
const isSuperAdmin = $derived(roles.includes('super_admin'));

const navSections = $derived(() => {
  const sections = [];

  // Dashboard — always first
  sections.push({
    label: null,
    items: [{ href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['*'] }],
  });

  // REGISTRAR OFFICE — super_admin and admin only
  if (hasRole('super_admin') || hasRole('admin')) {
    const registrarItems = [
      { href: '/admin/seasons', label: 'Seasons', icon: CalendarRange, roles: ['super_admin', 'admin'] },
      { href: '/applications', label: 'Applications', icon: FileText, roles: ['super_admin', 'admin', 'staff', 'test_administrator'] },
      { href: '/admin/test-scheduling', label: 'Test Scheduling', icon: Calendar, roles: ['super_admin', 'admin'] },
    ];
    sections.push({
      label: isSuperAdmin ? 'Registrar Office' : null,
      items: registrarItems.filter((item) => canSee(item.roles, item)),
    });
  }

  // GUIDANCE OFFICE — test_administrator and proctor
  if (hasRole('test_administrator') || hasRole('proctor') || hasRole('super_admin')) {
    const guidanceItems = [
      { href: '/admin/test-scheduling', label: 'My Sessions', icon: Calendar, roles: ['proctor'] },
      { href: '/admin/test-scheduling/monitoring', label: 'Session Monitor', icon: Activity, roles: ['super_admin', 'test_administrator', 'proctor'] },
      { href: '/grading', label: 'Grading', icon: GraduationCap, roles: ['super_admin', 'test_administrator'] },
      { href: '/consultation', label: 'Release & Consultation', icon: MessageSquare, roles: ['super_admin', 'test_administrator'], featureFlag: 'consultation_enabled' },
    ];
    sections.push({
      label: isSuperAdmin ? 'Guidance Office' : null,
      items: guidanceItems.filter((item) => canSee(item.roles, item)),
    });
  }

  // ADMINISTRATION — super_admin only, collapsible
  if (hasRole('super_admin')) {
    sections.push({
      label: 'Administration',
      collapsible: true,
      items: [
        { href: '/admin/users', label: 'Users', icon: Users, roles: ['super_admin'] },
        { href: '/admin/settings', label: 'Settings', icon: Settings, roles: ['super_admin'] },
        { href: '/admin/logs', label: 'Audit Log', icon: ScrollText, roles: ['super_admin'] },
        { href: '/admin/knowledge-documents', label: 'Knowledge Docs', icon: BookOpen, roles: ['super_admin'] },
        { href: '/admin/exam-domains', label: 'Exam Domains', icon: Layers, roles: ['super_admin'] },
        { href: '/admin/admission-slip-templates', label: 'Admission Slip Templates', icon: FileStack, roles: ['super_admin'] },
        { href: '/admin/result-sheet-templates', label: 'Result Templates', icon: FileText, roles: ['super_admin'] },
      ],
    });
  }

  return sections.filter((section) => section.items.length > 0);
})();
```

- [ ] **Step 2: Add collapsible state for Administration**

Add a `$state` for tracking which collapsible sections are open:

```javascript
let adminExpanded = $state(true); // default open for super_admin
```

- [ ] **Step 3: Update the nav template to render collapsible sections**

Replace the section rendering block (`{#each navSections as section}`) with:

```svelte
{#each navSections as section}
  <div class="space-y-2">
    {#if section.label}
      {#if section.collapsible}
        <button
          type="button"
          class="w-full flex items-center justify-between px-4 text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2 hover:text-foreground transition-colors"
          onclick={() => { adminExpanded = !adminExpanded; }}
        >
          {section.label}
          <ChevronDown class="h-4 w-4 transition-transform {adminExpanded ? 'rotate-180' : ''}" />
        </button>
        {#if adminExpanded}
          {#each section.items as item}
            <Link ...>...</Link>
          {/each}
        {/if}
      {:else}
        <p class="px-4 text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
          {section.label}
        </p>
        {#each section.items as item}
          <Link ...>...</Link>
        {/each}
      {/if}
    {:else}
      {#each section.items as item}
        <Link ...>...</Link>
      {/each}
    {/if}
  </div>
{/each}
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "feat: restructure nav with role-aware sections and collapsible Administration"
```

---

## Task 3: Move ExamSessions Pages to TestScheduling Directory + Update Svelte Component References

**File:** `resources/js/Pages/Admin/ExamSessions/` → rename folder to `TestScheduling/`

- [ ] **Step 1: Rename the folder**

Rename `resources/js/Pages/Admin/ExamSessions/` to `resources/js/Pages/Admin/TestScheduling/`

- [ ] **Step 2: Update all Inertia link and router references inside the moved files**

In each file inside `TestScheduling/`, update:
- `href="/admin/exam-sessions/...` → `href="/admin/test-scheduling/..."`
- `href="/admin/exam-sessions"` → `href="/admin/test-scheduling"`
- Any `$page.url` checks for exam-sessions → test-scheduling

Files to update inside the folder:
- `Index.svelte` — the index page, rename page title to "Test Scheduling"
- `Create.svelte` — page title "Schedule Test" or "New Test Session"
- `Edit.svelte`
- `EditForm.svelte` — has route refs at lines 32 and 104
- `Show.svelte`
- `Monitoring.svelte`
- Any component file that references the old route

- [ ] **Step 3: Update `ScheduleAssistantPanel.svelte`**

In `resources/js/Components/ScheduleAssistantPanel.svelte`, update 3 hardcoded URLs:
```
Line 92:  /admin/exam-sessions/schedule-assistant/chat     → /admin/test-scheduling/schedule-assistant/chat
Line 155: /admin/exam-sessions/schedule-assistant/apply-schedule → /admin/test-scheduling/schedule-assistant/apply-schedule
Line 179: router.visit('/admin/exam-sessions', ...)        → router.visit('/admin/test-scheduling', ...)
```

- [ ] **Step 4: Update `Components/SessionRoster.svelte`**

In `resources/js/Components/SessionRoster.svelte` (line ~246), update any `/admin/exam-sessions/...` URLs to `/admin/test-scheduling/...`

- [ ] **Step 5: Update `Pages/Dashboard.svelte`**

In `resources/js/Pages/Dashboard.svelte` (lines ~70, 71, 79, 163), update all references:
```
/admin/exam-sessions  → /admin/test-scheduling
exam-sessions.create → test-scheduling.create
```

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Admin/
git add resources/js/Components/ScheduleAssistantPanel.svelte
git add resources/js/Components/SessionRoster.svelte
git add resources/js/Pages/Dashboard.svelte
git commit -m "refactor: rename ExamSessions pages to TestScheduling and update all Svelte route refs"
```

---

## Task 4: Add In-Page "Add Course" Button to Seasons Index

**File:** `resources/js/Pages/Admin/Seasons/Index.svelte`

- [ ] **Step 1: Add "Add Course" primary button next to "Add Season"**

In the header area of `Seasons/Index.svelte`, add a secondary action button:

```svelte
<div class="flex gap-3">
  <Link href="/admin/seasons/create">
    <Button class="min-h-[44px]">
      <Plus class="mr-2 h-4 w-4" />
      Add Season
    </Button>
  </Link>
  <Link href="/admin/courses">
    <Button variant="outline" class="min-h-[44px]">
      <BookOpen class="mr-2 h-4 w-4" />
      Add Course
    </Button>
  </Link>
</div>
```

The "Add Course" button links to the existing `/admin/courses` page (which is still fully functional, just no longer in the nav).

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Admin/Seasons/Index.svelte
git commit -m "feat: add Add Course button to Seasons index page"
```

---

## Task 5: Add In-Page "Add Room" Button to TestScheduling Index

**File:** `resources/js/Pages/Admin/TestScheduling/Index.svelte`

- [ ] **Step 1: Add "Add Room" secondary button**

In the header of `TestScheduling/Index.svelte`:

```svelte
<div class="flex gap-3">
  <Link href="/admin/test-scheduling/create">
    <Button class="min-h-[44px]">
      <Plus class="mr-2 h-4 w-4" />
      Schedule Test
    </Button>
  </Link>
  <Link href="/admin/rooms">
    <Button variant="outline" class="min-h-[44px]">
      <DoorOpen class="mr-2 h-4 w-4" />
      Add Room
    </Button>
  </Link>
</div>
```

- [ ] **Step 2: Update page title**

Change `<title>` from "Exam Sessions" to "Test Scheduling".

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Index.svelte
git commit -m "feat: add Add Room button to Test Scheduling index, rename page"
```

---

## Task 6: Verify No Broken Links — Cross-Reference Scan

- [ ] **Step 1: Search for any remaining `/admin/exam-sessions` references**

```bash
grep -r "exam-sessions" resources/js/ --include="*.svelte" --include="*.js"
```

Fix any remaining hardcoded paths pointing to the old route.

- [ ] **Step 2: Search for any nav items pointing to Courses or Rooms pages**

```bash
grep -r "/admin/courses\|/admin/rooms" resources/js/ --include="*.svelte"
```

Courses and Rooms pages should only be linked from in-page buttons (Seasons and Test Scheduling pages), not from other nav items.

- [ ] **Step 3: Commit any fixes**

---

## Task 7: Update Any Other Pages Linking to Old Routes

**Files:** `resources/js/Components/blocks/Hero.svelte`, `resources/js/Pages/Admin/ExamSessions/Monitoring.svelte` (moved), and any other Svelte files.

- [ ] **Step 1: Find all Inertia links to `/admin/exam-sessions`**

```bash
grep -r "exam-sessions" resources/js/ --include="*.svelte" -l
```

- [ ] **Step 2: Update each reference to use `/admin/test-scheduling`**

- [ ] **Step 3: Commit**

---

## Verification Checklist

After all tasks:

- [ ] `php artisan route:list` shows `/admin/test-scheduling` routes, no `/admin/exam-sessions`
- [ ] Login as each role and visually verify nav items match the Role Navigation Matrix
- [ ] super_admin sees all 3 section labels: "Registrar Office", "Guidance Office", "Administration"
- [ ] admin sees flat nav, no section labels, no Grading, no Release & Consultation, no Session Monitor
- [ ] test_administrator sees flat nav, no section labels, no Seasons, no Test Scheduling, no Applications
- [ ] proctor sees flat nav, My Sessions + Session Monitor only
- [ ] staff sees Dashboard + Applications only
- [ ] Administration section is collapsible and starts expanded
- [ ] "Add Course" button visible inside Seasons page
- [ ] "Add Room" button visible inside Test Scheduling page
- [ ] All template pages (Admission Slip Templates, Result Templates) only accessible via Administration
- [ ] No broken links anywhere in the app

---

## Execution Order

```
Task 1 → Task 1.5 → Task 2 → Task 3 → Task 4 → Task 5 → Task 6 → Task 7 → Verification
```

Each task is self-contained and can be committed independently.

> **Task 1.5 is critical** — it must run after Task 1 (route rename) and before Task 3 (page rename). It updates all PHP `redirect()->route()` calls so controllers don't 500-error after the route rename.
