# Navigation Reform: Setup Hub Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Restructure the admin sidebar navigation to use workflow-focused sections (Admissions / Assessment) with a new Setup hub page that centralizes all configuration/reference-data management.

**Architecture:** The sidebar `navSections` in `AuthenticatedLayout.svelte` will be reorganized with renamed sections and a new "Setup" entry. A new `SetupController` and `Admin/Setup/Index.svelte` page will serve as a role-filtered card-grid hub linking to all configuration pages. No routes, controllers, or backend logic change — only the navigation layer and one new hub page. The dead Release sub-menu code will be cleaned up.

**Tech Stack:** Laravel (controller + route), Inertia.js/Svelte (page component), existing shadcn-svelte Card components, Lucide icons.

---

## Pre-Flight Checklist

Before starting, verify:
- `npm run dev` is running
- `php artisan schedule:work` is running (or at least the app is bootable)
- Run `php artisan test --compact` to establish a green baseline

---

### Task 1: Create the Setup Hub Controller

**Files:**
- Create: `app/Http/Controllers/Admin/SetupController.php`

**Step 1: Create controller via artisan**

Run:
```bash
./vendor/bin/sail php artisan make:class App/Http/Controllers/Admin/SetupController --no-interaction
```

**Step 2: Implement the controller**

Replace the generated file content with:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    /**
     * Show the setup hub — role-filtered card grid for all configuration pages.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Setup/Index', [
            'allowDirectAssessment' => SystemSetting::allowDirectAssessment(),
            'aiCompanionEnabled' => SystemSetting::aiCompanionEnabled(),
        ]);
    }
}
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/SetupController.php
git commit -m "feat: add SetupController for navigation hub"
```

---

### Task 2: Register the Setup Route

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

**Step 1: Add the setup route**

In `routes/web.php`, find the line (around line 127-130):
```php
    // Reports
    Route::middleware('role:super_admin,registrar_administrator,test_administrator')->prefix('admin')->name('admin.')->group(function () {
```

Add **before** that block:

```php
    // Setup Hub — accessible to anyone who manages configuration
    Route::middleware('role:super_admin,registrar_administrator,test_administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::get('setup', [\App\Http\Controllers\Admin\SetupController::class, 'index'])->name('setup.index');
    });
```

**Step 2: Add the page title mapping**

In `app/Http/Middleware/HandleInertiaRequests.php`, find the `$titles` array inside `defaultPageTitle()` and add after the `'admin.ai-companion.index'` entry (around line 101):

```php
'admin.setup.index' => 'Setup',
```

**Step 3: Verify route is registered**

Run:
```bash
./vendor/bin/sail php artisan route:list --name=admin.setup
```

Expected: One GET route `/admin/setup` pointing to `SetupController@index`.

**Step 4: Commit**

```bash
git add routes/web.php app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: register /admin/setup route and page title"
```

---

### Task 3: Create the Setup Hub Svelte Page

**Files:**
- Create: `resources/js/Pages/Admin/Setup/Index.svelte`

**Step 1: Create the Setup hub page**

Create `resources/js/Pages/Admin/Setup/Index.svelte` with a role-filtered card grid. Each card links to a configuration page and is only shown to users whose roles match.

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import {
    CalendarRange,
    BookOpen,
    DoorOpen,
    Brain,
    FileText,
    Shield,
    Bot,
    Settings,
    Ticket,
  } from 'lucide-svelte';

  let { allowDirectAssessment = false, aiCompanionEnabled = false } = $props();

  const breadcrumbs = [{ label: 'Setup' }];
  const page = usePage();
  const roles = $derived(
    $page.props.auth?.user?.roles?.map((r) => r.name) ?? []
  );

  function hasRole(r) {
    return roles.includes(r);
  }

  const setupCards = $derived(
    [
      {
        href: '/admin/academic-years',
        label: 'Academic Years',
        description: 'Manage academic year periods and application windows.',
        icon: CalendarRange,
        roles: ['super_admin', 'registrar_administrator'],
      },
      {
        href: '/admin/courses',
        label: 'Programs & Courses',
        description: 'Configure available courses and program offerings.',
        icon: BookOpen,
        roles: ['super_admin', 'registrar_administrator'],
      },
      {
        href: '/admin/rooms',
        label: 'Rooms & Facilities',
        description: 'Manage assessment rooms and seating capacity.',
        icon: DoorOpen,
        roles: ['super_admin', 'registrar_administrator'],
      },
      {
        href: '/admin/aptitude-areas',
        label: 'Aptitude Areas',
        description:
          'Define scoring domains, weights, and computation formulas.',
        icon: Brain,
        roles: ['super_admin', 'test_administrator'],
      },
      {
        href: '/admin/release/result-templates',
        label: 'Result Sheet Templates',
        description: 'Design and manage result sheet print templates.',
        icon: FileText,
        roles: ['super_admin', 'test_administrator'],
      },
      {
        href: '/admin/admission-slip-templates',
        label: 'Admission Slip Templates',
        description: 'Configure admission slip layout and content.',
        icon: Ticket,
        roles: ['super_admin'],
      },
      {
        href: '/admin/privacy-policies',
        label: 'Privacy Policies',
        description:
          'Manage privacy policy versions shown on the application form.',
        icon: Shield,
        roles: ['super_admin', 'registrar_administrator'],
      },
      {
        href: '/admin/ai-companion',
        label: 'AI Companion',
        description:
          'Configure AI advisor persona, knowledge base, and feature toggle.',
        icon: Bot,
        roles: ['super_admin'],
        badge: aiCompanionEnabled ? 'Active' : null,
      },
      {
        href: '/admin/settings',
        label: 'System Settings',
        description: 'Feature toggles, release mode, and system-wide configuration.',
        icon: Settings,
        roles: ['super_admin'],
      },
    ].filter((card) => card.roles.some((r) => hasRole(r)))
  );
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div>
      <p class="text-sm text-muted-foreground">
        Configure reference data, templates, and system-wide settings.
      </p>
    </div>

    {#if setupCards.length > 0}
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {#each setupCards as card}
          <Link
            href={card.href}
            class="group relative flex flex-col gap-3 rounded-2xl border border-border bg-card p-6 transition-all hover:border-primary/30 hover:shadow-md hover:shadow-primary/5 hover:-translate-y-0.5"
          >
            <div class="flex items-start justify-between">
              <div
                class="rounded-xl bg-muted p-2.5 transition-colors group-hover:bg-primary/10"
              >
                <card.icon
                  class="h-5 w-5 text-muted-foreground transition-colors group-hover:text-primary"
                />
              </div>
              {#if card.badge}
                <span
                  class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                >
                  {card.badge}
                </span>
              {/if}
            </div>
            <div>
              <h3 class="font-semibold text-foreground">{card.label}</h3>
              <p class="mt-1 text-sm text-muted-foreground leading-relaxed">
                {card.description}
              </p>
            </div>
            <div class="mt-auto pt-2">
              <span
                class="text-sm font-medium text-primary opacity-0 transition-opacity group-hover:opacity-100"
              >
                Configure →
              </span>
            </div>
          </Link>
        {/each}
      </div>
    {:else}
      <p class="py-12 text-center text-muted-foreground">
        No setup options available for your role.
      </p>
    {/if}
  </div>
</AuthenticatedLayout>
```

**Step 2: Verify the page compiles**

Check the Vite dev server terminal for compilation errors. There should be none.

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/Setup/Index.svelte
git commit -m "feat: create Setup hub page with role-filtered configuration cards"
```

---

### Task 4: Restructure the Sidebar Navigation

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.svelte`

**Step 1: Update the icon imports**

Replace the icon import line (line 4):

```javascript
import { ChevronDown, ChevronRight, Menu, LayoutDashboard, Users, FileText, Calendar, GraduationCap, Bot, Settings, ScrollText, Activity, CalendarRange, Layers, Sun, Moon, SendHorizonal, BarChart3 } from 'lucide-svelte';
```

With:

```javascript
import { ChevronDown, ChevronRight, Menu, LayoutDashboard, Users, FileText, Calendar, GraduationCap, Settings, ScrollText, Activity, Sun, Moon, SendHorizonal, BarChart3, Wrench } from 'lucide-svelte';
```

Changes: Removed `Bot`, `CalendarRange`, `Layers` (no longer needed in sidebar). Added `Wrench` (for Setup icon).

**Step 2: Replace the navSections definition**

Replace the entire `navSections` block (lines 65-93) with:

```javascript
  const navSections = $derived([
    { label: null, items: [
      { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['*'], activeFor: ['/dashboard'] },
    ]},
    { label: 'Admissions', items: [
      { href: '/admin/applications', label: 'Applications', icon: FileText, roles: ['super_admin', 'registrar_administrator', 'staff'], activeFor: ['/admin/applications', '/admin/privacy-policies', '/admin/admission-slip-templates'] },
      { href: '/admin/exam-scheduling', label: 'Exam Scheduling', icon: Calendar, roles: ['super_admin', 'registrar_administrator'], activeFor: ['/admin/exam-scheduling'] },
    ]},
    { label: 'Assessment', items: [
      { href: '/proctor/my-sessions', label: 'My Sessions', icon: Calendar, roles: ['proctor'], activeFor: ['/proctor/my-sessions', '/proctor/sessions'] },
      { href: '/admin/exam-monitoring', label: 'Exam Monitoring', icon: Activity, roles: ['super_admin', 'test_administrator'], activeFor: ['/admin/exam-monitoring', '/admin/test-admin'] },
      { href: '/admin/grading', label: 'Grading', icon: GraduationCap, roles: ['super_admin', 'test_administrator'], activeFor: ['/admin/grading'] },
      { href: '/admin/release', label: 'Release', icon: SendHorizonal, roles: ['super_admin', 'test_administrator'], activeFor: ['/admin/release'] },
    ]},
    { label: 'Reports', items: [
      { href: '/admin/reports', label: 'Reports', icon: BarChart3, roles: ['super_admin', 'registrar_administrator', 'test_administrator'], activeFor: ['/admin/reports'] },
    ]},
    { label: 'System', collapsible: true, items: [
      { href: '/admin/setup', label: 'Setup', icon: Wrench, roles: ['super_admin', 'registrar_administrator', 'test_administrator'], activeFor: ['/admin/setup', '/admin/academic-years', '/admin/courses', '/admin/rooms', '/admin/aptitude-areas', '/admin/settings', '/admin/ai-companion', '/admin/admission-slip-templates', '/admin/privacy-policies'] },
      { href: '/admin/users', label: 'Users', icon: Users, roles: ['super_admin'], activeFor: ['/admin/users'] },
      { href: '/admin/logs', label: 'Audit Log', icon: ScrollText, roles: ['super_admin'], activeFor: ['/admin/logs'] },
    ]},
  ].map((section) => ({
    ...section,
    items: section.items.filter((item) => canSee(item.roles, item)),
  })).filter((section) => section.items.length > 0));
```

Key changes:
- "Registrar Office" → **"Admissions"**
- "Guidance Office" → **"Assessment"**
- "Administration" → **"System"**
- Removed `Academic Years` from sidebar (now in Setup hub)
- Removed dead `items` sub-menu from Release (the rendering bug)
- Added `Setup` entry under System with broad `activeFor` covering all config pages
- Removed `Settings` and `AI Companion` as standalone sidebar items (now in Setup hub)

**Step 3: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "feat: restructure sidebar — Admissions/Assessment sections, Setup hub entry"
```

---

### Task 5: Update Breadcrumbs on Configuration Index Pages

**Files:**
- Modify: `resources/js/Pages/Admin/Courses/Index.svelte` (line 35)
- Modify: `resources/js/Pages/Admin/AcademicYears/Index.svelte` (line 13)
- Modify: `resources/js/Pages/Admin/Settings/Index.svelte` (line 19)
- Modify: `resources/js/Pages/Admin/Rooms/Index.svelte`
- Modify: `resources/js/Pages/Admin/AptitudeAreas/Index.svelte`
- Modify: `resources/js/Pages/Admin/AiCompanion/Index.svelte`
- Modify: `resources/js/Pages/Admin/AdmissionSlipTemplates/Index.svelte`
- Modify: `resources/js/Pages/Admin/PrivacyPolicies/Index.svelte`
- Modify: `resources/js/Pages/Admin/ResultSheetTemplates/Index.svelte`

All configuration pages need their breadcrumbs updated to trace back through the Setup hub.

**Step 1: Update each file's breadcrumbs**

For each file, find the `breadcrumbs` constant and prepend `{ label: 'Setup', href: '/admin/setup' }`:

| File | Before | After |
|------|--------|-------|
| Courses/Index | `[{ label: 'Academic Years', href: '/admin/academic-years' }, { label: 'Courses' }]` | `[{ label: 'Setup', href: '/admin/setup' }, { label: 'Courses' }]` |
| AcademicYears/Index | `[{ label: 'Academic Years' }]` | `[{ label: 'Setup', href: '/admin/setup' }, { label: 'Academic Years' }]` |
| Settings/Index | `[{ label: 'Settings' }]` | `[{ label: 'Setup', href: '/admin/setup' }, { label: 'System Settings' }]` |
| Rooms/Index | Find current value | Prepend Setup |
| AptitudeAreas/Index | Find current value | Prepend Setup |
| AiCompanion/Index | Find current value | Prepend Setup |
| AdmissionSlipTemplates/Index | Find current value | Prepend Setup |
| PrivacyPolicies/Index | Find current value | Prepend Setup |
| ResultSheetTemplates/Index | Find current value | Prepend Setup |

> **Important:** Read each file before modifying to find the exact current breadcrumbs value.

**Step 2: Commit**

```bash
git add resources/js/Pages/Admin/
git commit -m "feat: update breadcrumbs on all config index pages to trace through Setup hub"
```

---

### Task 6: Update Sub-Page Breadcrumbs (Create/Edit Pages)

**Files:**
- All `Create.svelte` and `Edit.svelte` files under configuration directories

**Step 1: Check and update each Create/Edit page's breadcrumbs**

For each file, read it first to find exact current breadcrumbs, then update to include Setup as the root:

- `AcademicYears/Create.svelte` → `[Setup, Academic Years, Create]`
- `AcademicYears/Edit.svelte` → `[Setup, Academic Years, Edit]`
- `Courses/Create.svelte` → `[Setup, Courses, Create]`
- `Courses/Edit.svelte` → `[Setup, Courses, Edit]`
- `Rooms/Create.svelte` → `[Setup, Rooms, Create]`
- `Rooms/Edit.svelte` → `[Setup, Rooms, Edit]`
- `AptitudeAreas/Create.svelte` → `[Setup, Aptitude Areas, Create]`
- `AptitudeAreas/Edit.svelte` → `[Setup, Aptitude Areas, Edit]`
- `ResultSheetTemplates/Create.svelte` → `[Setup, Result Templates, Create]`
- `ResultSheetTemplates/Edit.svelte` → `[Setup, Result Templates, Edit]`
- `PrivacyPolicies/Create.svelte` → `[Setup, Privacy Policies, Create]`
- `PrivacyPolicies/Edit.svelte` → `[Setup, Privacy Policies, Edit]`
- `KnowledgeDocuments/Create.svelte` → `[Setup, AI Companion, Create]`
- `KnowledgeDocuments/Edit.svelte` → `[Setup, AI Companion, Edit]`

> **Important:** Be careful with Edit pages that use dynamic data (e.g., `{ label: course.name }`). Preserve dynamic labels.

**Step 2: Commit**

```bash
git add resources/js/Pages/Admin/
git commit -m "feat: update sub-page breadcrumbs for Setup hub navigation chain"
```

---

### Task 7: Update "Guidance Office" References in Reports and Other Pages

**Files:**
- Modify: `resources/js/Pages/Admin/Reports/Index.svelte` (lines 195, 198)
- Modify: `resources/js/Components/blocks/CallToAction.svelte` (line 50)

**Step 1: Update Reports page section header**

In `resources/js/Pages/Admin/Reports/Index.svelte`:

Find: `<!-- Guidance Office Reports -->`
Replace: `<!-- Assessment Reports -->`

Find: `>Guidance Office</h2>`
Replace: `>Assessment</h2>`

**Step 2: Update CallToAction component**

In `resources/js/Components/blocks/CallToAction.svelte`:

Find: `>For Guidance Office</p>`
Replace: `>For Assessment Office</p>`

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/Reports/Index.svelte resources/js/Components/blocks/CallToAction.svelte
git commit -m "refactor: rename Guidance Office → Assessment in reports and CTA"
```

---

### Task 8: Verify Dashboard Quick Actions

**Files:**
- Review: `resources/js/Pages/Dashboard.svelte` (no changes expected)

**Step 1: Review the Dashboard quick actions**

The Dashboard has "Facilities" and "Programs" quick-action cards that link directly to `/admin/rooms` and `/admin/courses`. These are still valid shortcuts — they remain as convenient quick access. No changes needed.

**Step 2: Verify the `quickActions` array**

The `quickActions` array (line 46-53) uses hardcoded paths that all still exist:
- `/admin/applications` ✓
- `/proctor/my-sessions` ✓
- `/admin/exam-scheduling` ✓
- `/grading` ✓
- `/release` ✓
- `/admin/users` ✓

No commit needed for this task.

---

### Task 9: Run Full Test Suite & Verify

**Step 1: Run the full test suite**

Run:
```bash
./vendor/bin/sail php artisan test --compact
```

Expected: All tests pass. No routes were removed or renamed.

**Step 2: Run Pint for code formatting**

Run:
```bash
./vendor/bin/sail vendor/bin/pint --dirty --format agent
```

**Step 3: Manual smoke test**

Open the app in browser and verify:
1. Sidebar shows new section names (Admissions, Assessment, System)
2. Setup page loads at `/admin/setup` and shows role-filtered cards
3. Clicking each card navigates to the correct page
4. Breadcrumbs on all config pages show "Setup → [Page Name]"
5. Active state highlighting works (Setup stays highlighted when on any config page)
6. Proctors see only: Dashboard, My Sessions (under Assessment)
7. The System section collapses/expands correctly

**Step 4: Commit if any formatting fixes**

```bash
git add -A
git commit -m "style: pint formatting"
```

---

### Task 10: Push and Wrap Up

**Step 1: Push**

```bash
git pull --rebase origin main
git push
```

**Step 2: Verify**

```bash
git status
```

Expected: "Your branch is up to date with 'origin/main'."

---

## Route Impact Assessment

**No routes are changed or removed.** This is a navigation-layer-only change:

| Route | Status | Notes |
|-------|--------|-------|
| `/admin/setup` | **NEW** | New Setup hub page |
| `/admin/settings` | UNCHANGED | Still accessible, just no dedicated sidebar item |
| `/admin/ai-companion` | UNCHANGED | Still accessible, linked from Setup hub |
| `/admin/academic-years` | UNCHANGED | Still accessible, linked from Setup hub |
| `/admin/courses` | UNCHANGED | Still accessible, linked from Setup hub |
| `/admin/rooms` | UNCHANGED | Still accessible, linked from Setup hub |
| `/admin/aptitude-areas` | UNCHANGED | Still accessible, linked from Setup hub |
| `/admin/privacy-policies` | UNCHANGED | Still accessible, linked from Setup hub |
| `/admin/admission-slip-templates` | UNCHANGED | Still accessible, linked from Setup hub |
| `/admin/release/result-templates` | UNCHANGED | Still accessible, linked from Setup hub |
| All other routes | UNCHANGED | No modifications |

## Risk Assessment

| Risk | Likelihood | Mitigation |
|------|-----------|------------|
| Tests break from navigation changes | **None** | No routes removed; tests hit URLs directly |
| Users can't find Settings | **Low** | Setup hub card + breadcrumb back-link |
| `activeFor` misses a config page | **Low** | Comprehensive list in navSections + manual smoke test |
| Breadcrumb chains too deep | **Low** | Max 3 levels: Setup → Page → Create/Edit |
