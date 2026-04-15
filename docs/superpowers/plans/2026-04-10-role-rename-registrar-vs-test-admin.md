# Role Rename: registrar_administrator & test_administrator Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename the `admin` DB role to `registrar_administrator` (Registrar Office admin) and `registrar_administrator` to `test_administrator` (Guidance Office admin), then align all routes, FormRequests, policies, controllers, navigation, seeders, and tests to match the clean two-office access model.

**Architecture:** Two role renames ripple through DB migration → backend (routes, requests, policies, controllers, services, seeders) → frontend (Svelte nav) → tests. Each layer is a separate task. The DB migration runs first and must be complete before any role-string changes are tested.

**Tech Stack:** Laravel 12, PHPUnit 11, Svelte/Inertia v2

---

## Role Map (Before → After)

| Old slug | Old display | New slug | New display | Office |
|---|---|---|---|---|
| `admin` | Admin | `registrar_administrator` | Registrar Administrator | Registrar Office |
| `registrar_administrator` | Test Administrator | `test_administrator` | Test Administrator | Guidance Office |
| `staff` | Staff | `staff` | Staff | Registrar Office (limited) |
| `proctor` | Proctor | `proctor` | Proctor | Guidance Office (limited) |
| `super_admin` | Super Admin | `super_admin` | Super Admin | All |

## Access Matrix (Post-Rename)

| Area | registrar_administrator | staff | test_administrator | proctor |
|---|---|---|---|---|
| Applications (view + process) | ✓ | ✓ limited | — | — |
| Academic Years (CRUD) | ✓ | — | — | — |
| Courses (CRUD) | ✓ | — | — | — |
| Rooms (CRUD) | ✓ | — | — | — |
| Exam Scheduling (manage) | ✓ | — | — | — |
| Admission Slip Templates | super_admin only | — | — | — |
| Exam Monitoring | — | — | ✓ | ✓ |
| My Sessions | — | — | — | ✓ |
| Session Roster (proctor actions) | — | — | ✓ | ✓ |
| Grading | — | — | ✓ | — |
| Release | — | — | ✓ | — |
| Aptitude Areas (CRUD) | — | — | ✓ | — |
| Result Sheet Templates (CRUD) | — | — | ✓ | — |

## Current State Warning

`routes/web.php` already has a partial split applied in this session (line 116 group was split into registrar/test-admin/shared groups). That split used the **old** role names (`admin`, `registrar_administrator`). Task 3 will replace those with the new names.

---

## Files Modified

| File | Change |
|---|---|
| `database/migrations/2026_04_10_105612_rename_admin_to_registrar_and_registrar_to_test_administrator_roles.php` | DB migration: rename role slugs + display names |
| `database/seeders/RoleSeeder.php` | Update role definitions |
| `database/seeders/DefenseDemoSeeder.php` | Update role slug + variable references |
| `database/seeders/DemoDashboardSeeder.php` | Update role slug reference |
| `app/Console/Commands/DemoSetupCommand.php` | Update role slug |
| `routes/web.php` | Replace old slugs with new, align groups to access matrix |
| `app/Http/Requests/AssignApplicantsRequest.php` | `admin` → `registrar_administrator` |
| `app/Http/Requests/StoreExamSessionRequest.php` | `admin` → `registrar_administrator` |
| `app/Http/Requests/UpdateExamSessionRequest.php` | `admin` → `registrar_administrator` |
| `app/Http/Requests/StoreCourseRequest.php` | `admin` → `registrar_administrator` |
| `app/Http/Requests/UpdateCourseRequest.php` | `admin` → `registrar_administrator` |
| `app/Http/Requests/StoreRoomRequest.php` | `admin` → `registrar_administrator` |
| `app/Http/Requests/UpdateRoomRequest.php` | `admin` → `registrar_administrator` |
| `app/Http/Requests/MarkSlipPrintedRequest.php` | `admin,registrar_administrator` → `registrar_administrator` |
| `app/Http/Requests/StoreAdmissionSlipTemplateRequest.php` | `admin,registrar_administrator` → `registrar_administrator` (super_admin already handles via route) |
| `app/Http/Requests/UpdateAdmissionSlipTemplateRequest.php` | same |
| `app/Http/Requests/StoreAptitudeAreaRequest.php` | `registrar_administrator` → `test_administrator` |
| `app/Http/Requests/UpdateAptitudeAreaRequest.php` | `registrar_administrator` → `test_administrator` |
| `app/Http/Requests/StoreGradingSessionRequest.php` | `admin,registrar_administrator` → `test_administrator` |
| `app/Http/Requests/StoreResultSheetTemplateRequest.php` | `admin,registrar_administrator` → `test_administrator` |
| `app/Http/Requests/UpdateResultSheetTemplateRequest.php` | `admin,registrar_administrator` → `test_administrator` |
| `app/Http/Requests/MarkPrintedRequest.php` | `admin,registrar_administrator` → `test_administrator` |
| `app/Policies/AcademicYearPolicy.php` | `admin` → `registrar_administrator` |
| `app/Policies/ApplicationPolicy.php` | `admin` → `registrar_administrator`; remove `registrar_administrator` (old slug) from view — test_admin has no app access |
| `app/Policies/AptitudeAreaPolicy.php` | `registrar_administrator` → `test_administrator` |
| `app/Http/Controllers/Admin/ExamSessionController.php` | `admin` → `registrar_administrator`; `registrar_administrator` → `test_administrator` |
| `app/Http/Controllers/Proctor/SessionRosterController.php` | `admin` → `registrar_administrator` |
| `app/Services/DashboardService.php` | `admin` → `registrar_administrator`; `registrar_administrator` → `test_administrator` |
| `resources/js/Layouts/AuthenticatedLayout.svelte` | Update all role strings; move Aptitude Areas + Result Sheet Templates to Guidance Office section |
| `tests/Feature/Admin/AcademicYearControllerTest.php` | `admin` → `registrar_administrator`; `registrar_administrator` → `test_administrator` |
| `tests/Feature/Admin/AptitudeAreaControllerTest.php` | `registrar_administrator` → `test_administrator`; `admin` → `registrar_administrator` |
| `tests/Feature/Admin/CourseActivationTest.php` | `admin` → `registrar_administrator` |
| `tests/Feature/Admin/ExamSessionValidationTest.php` | `admin` → `registrar_administrator` |
| `tests/Feature/Admin/RoomActivationTest.php` | `admin` → `registrar_administrator` |
| `tests/Feature/DashboardControllerTest.php` | `admin` → `registrar_administrator` |
| `tests/Feature/DashboardTest.php` | `admin` → `registrar_administrator` |

---

## Task 1: Database Migration

**Files:**
- Modify: `database/migrations/2026_04_10_105612_rename_admin_to_registrar_and_registrar_to_test_administrator_roles.php`

- [ ] **Step 1: Write the migration**

The rename must be done in two steps to avoid a unique-constraint conflict: first rename `registrar_administrator` to a temp value, then rename `admin` to `registrar_administrator`, then rename the temp to `test_administrator`.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: park old registrar_administrator to avoid slug collision
        DB::table('roles')
            ->where('name', 'registrar_administrator')
            ->update(['name' => 'test_administrator', 'display_name' => 'Test Administrator']);

        // Step 2: rename admin → registrar_administrator
        DB::table('roles')
            ->where('name', 'admin')
            ->update(['name' => 'registrar_administrator', 'display_name' => 'Registrar Administrator']);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'registrar_administrator')
            ->update(['name' => 'admin', 'display_name' => 'Admin']);

        DB::table('roles')
            ->where('name', 'test_administrator')
            ->update(['name' => 'registrar_administrator', 'display_name' => 'Test Administrator']);
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_04_10_105612_rename_admin_to_registrar_and_registrar_to_test_administrator_roles` then `Migrated` with no errors.

- [ ] **Step 3: Verify roles in DB**

```bash
php artisan tinker --execute 'App\Models\Role::pluck("display_name", "name")->each(fn($d, $n) => print("$n => $d\n"));'
```

Expected output includes:
```
registrar_administrator => Registrar Administrator
test_administrator => Test Administrator
staff => Staff
proctor => Proctor
super_admin => Super Admin
```

---

## Task 2: RoleSeeder + Seeders

**Files:**
- Modify: `database/seeders/RoleSeeder.php`
- Modify: `database/seeders/DefenseDemoSeeder.php`
- Modify: `database/seeders/DemoDashboardSeeder.php`
- Modify: `app/Console/Commands/DemoSetupCommand.php`

- [ ] **Step 1: Update RoleSeeder**

```php
// database/seeders/RoleSeeder.php — $roles array
$roles = [
    ['name' => 'super_admin',            'display_name' => 'Super Admin',             'description' => 'System administrator, manages users and roles'],
    ['name' => 'staff',                  'display_name' => 'Staff',                   'description' => 'Registrar staff, processes applications'],
    ['name' => 'registrar_administrator','display_name' => 'Registrar Administrator', 'description' => 'Registrar admin, manages scheduling, courses, rooms, applications'],
    ['name' => 'proctor',                'display_name' => 'Proctor',                 'description' => 'Guidance office, monitors assigned exam sessions'],
    ['name' => 'test_administrator',     'display_name' => 'Test Administrator',      'description' => 'Guidance office, manages grading, release, aptitude areas'],
];
```

- [ ] **Step 2: Update DefenseDemoSeeder**

Find and replace the two role references:

```php
// Line ~82: change role slug and variable key
'registrar_admin' => $this->upsertUserWithRole('josefina@securecat.local', 'Josefina Gaerlan', 'registrar_administrator'),
'test_admin'      => $this->upsertUserWithRole('analiza@securecat.local',  'Analiza Barroga',  'test_administrator'),
```

Then update all `$users['admin']` references to `$users['registrar_admin']` in that seeder (there should be ~5 lines referencing the old `'admin'` key for `josefina`).

- [ ] **Step 3: Update DemoDashboardSeeder**

```php
// Find the test_admin line (~line 54)
'registrar_admin' => $this->upsertUserWithRole('reginadmin@demo.local', 'Registrar Admin Demo', 'registrar_administrator'),
'test_admin'      => $this->upsertUserWithRole('testadmin@demo.local',  'Test Admin Demo',       'test_administrator'),
```

- [ ] **Step 4: Update DemoSetupCommand**

```php
// app/Console/Commands/DemoSetupCommand.php — update the role matrix lines
['registrar_administrator', 'josefina@securecat.local'],
// ...
['test_administrator',      'analiza@securecat.local'],
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_04_10_105612_rename_admin_to_registrar_and_registrar_to_test_administrator_roles.php database/seeders/RoleSeeder.php database/seeders/DefenseDemoSeeder.php database/seeders/DemoDashboardSeeder.php app/Console/Commands/DemoSetupCommand.php
git commit -m "feat: rename admin→registrar_administrator and registrar_administrator→test_administrator roles"
```

---

## Task 3: Routes

**Files:**
- Modify: `routes/web.php`

The file currently has a partial split using old role names. Replace the three split groups (lines ~116–153 area) with correctly named groups matching the access matrix.

- [ ] **Step 1: Replace the three split groups in routes/web.php**

Find the block that starts with `// Registrar Admin: exam scheduling management, courses, rooms` and ends with the closing `});` of the "Shared: academic years" group. Replace the entire block:

```php
    // Registrar Administrator: exam scheduling, courses, rooms, academic years
    Route::middleware('role:super_admin,registrar_administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::post('test-scheduling/schedule-assistant/chat', [ExamSchedulingAssistantController::class, 'chat'])->name('test-scheduling.schedule-assistant.chat');
        Route::post('test-scheduling/schedule-assistant/apply-schedule', [ExamSchedulingAssistantController::class, 'applySchedule'])->name('test-scheduling.schedule-assistant.apply');
        Route::post('test-scheduling/{exam_session}/assign-applicants', [ExamSessionController::class, 'assignApplicants'])->name('test-scheduling.assign-applicants');
        Route::post('test-scheduling/{exam_session}/remove-applicant', [ExamSessionController::class, 'removeApplicant'])->name('test-scheduling.remove-applicant');
        Route::post('test-scheduling/{exam_session}/publish', [ExamSessionController::class, 'publish'])->name('test-scheduling.publish');
        Route::post('test-scheduling/{exam_session}/unpublish', [ExamSessionController::class, 'unpublish'])->name('test-scheduling.unpublish');
        Route::post('test-scheduling/{exam_session}/reopen', [ExamSessionController::class, 'reopen'])->name('test-scheduling.reopen');
        Route::post('test-scheduling', [ExamSessionController::class, 'store'])->name('test-scheduling.store');
        Route::get('test-scheduling/{exam_session}/edit', [ExamSessionController::class, 'edit'])->name('test-scheduling.edit');
        Route::put('test-scheduling/{exam_session}', [ExamSessionController::class, 'update'])->name('test-scheduling.update');
        Route::resource('academic-years', AcademicYearController::class)->except('show', 'destroy')->parameters(['academic_years' => 'academic_year']);
        Route::post('academic-years/{academic_year}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
        Route::post('academic-years/{academic_year}/deactivate', [AcademicYearController::class, 'deactivate'])->name('academic-years.deactivate');
        Route::resource('courses', CourseController::class)->except('show')->parameters(['courses' => 'course']);
        Route::post('courses/{course}/activate', [CourseController::class, 'activate'])->name('courses.activate');
        Route::post('courses/{course}/deactivate', [CourseController::class, 'deactivate'])->name('courses.deactivate');
        Route::post('courses/{course}/restore', [CourseController::class, 'restore'])->name('courses.restore');
        Route::resource('rooms', RoomController::class)->except('show')->parameters(['rooms' => 'room']);
        Route::post('rooms/{room}/activate', [RoomController::class, 'activate'])->name('rooms.activate');
        Route::post('rooms/{room}/deactivate', [RoomController::class, 'deactivate'])->name('rooms.deactivate');
        Route::post('rooms/{room}/restore', [RoomController::class, 'restore'])->name('rooms.restore');
    });

    // Test Administrator: aptitude areas, result sheet templates
    Route::middleware('role:super_admin,test_administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('aptitude-areas', AptitudeAreaController::class)->except('show', 'destroy')->parameters(['aptitude_areas' => 'aptitude_area']);
        Route::post('result-sheet-templates/preview', [ResultSheetTemplateController::class, 'preview'])->name('result-sheet-templates.preview');
        Route::resource('result-sheet-templates', ResultSheetTemplateController::class)->except('show')->parameters(['result_sheet_templates' => 'result_sheet_template']);
    });
```

- [ ] **Step 2: Fix remaining route references to old slugs**

Find and replace in the same file:

| Find | Replace |
|---|---|
| `role:super_admin,admin,proctor` | `role:super_admin,registrar_administrator,proctor` |
| `role:super_admin,admin,proctor,registrar_administrator` | `role:super_admin,registrar_administrator,proctor,test_administrator` |
| `role:super_admin,admin,registrar_administrator` (test-admin sessions group) | `role:super_admin,registrar_administrator,test_administrator` |
| `role:super_admin,registrar_administrator` (grading group) | `role:super_admin,test_administrator` |
| `role:super_admin,registrar_administrator` (release group) | `role:super_admin,test_administrator` |
| `role:super_admin,staff,admin,registrar_administrator` (applications view) | `role:super_admin,registrar_administrator,staff` |

- [ ] **Step 3: Verify route list has no stale `admin` slug**

```bash
php artisan route:list --except-vendor | grep "admin\b" | grep -v "super_admin\|test_admin\|registrar_admin\|ai-companion\|admin\."
```

Expected: no output (no bare `admin` role in middleware).

- [ ] **Step 4: Commit**

```bash
git add routes/web.php
git commit -m "fix: align routes to registrar_administrator and test_administrator role slugs"
```

---

## Task 4: FormRequests — Registrar Office

**Files (all in `app/Http/Requests/`):**
- `AssignApplicantsRequest.php`
- `StoreExamSessionRequest.php`
- `UpdateExamSessionRequest.php`
- `StoreCourseRequest.php`
- `UpdateCourseRequest.php`
- `StoreRoomRequest.php`
- `UpdateRoomRequest.php`
- `MarkSlipPrintedRequest.php`
- `StoreAdmissionSlipTemplateRequest.php`
- `UpdateAdmissionSlipTemplateRequest.php`

- [ ] **Step 1: Update all Registrar Office requests**

For each file listed, change the `authorize()` method to:

```php
// AssignApplicantsRequest, StoreExamSessionRequest, UpdateExamSessionRequest,
// StoreCourseRequest, UpdateCourseRequest, StoreRoomRequest, UpdateRoomRequest
return $this->user()?->hasAnyRole(['super_admin', 'registrar_administrator']) ?? false;

// MarkSlipPrintedRequest, StoreAdmissionSlipTemplateRequest, UpdateAdmissionSlipTemplateRequest
return $this->user()?->hasAnyRole(['super_admin', 'registrar_administrator']) ?? false;
```

- [ ] **Step 2: Run a quick smoke test**

```bash
php artisan test --compact --filter=CourseActivation
```

Expected: PASS (or at minimum no authorization failures — actual failures are fixed in Task 8).

---

## Task 5: FormRequests — Guidance Office

**Files (all in `app/Http/Requests/`):**
- `StoreAptitudeAreaRequest.php`
- `UpdateAptitudeAreaRequest.php`
- `StoreGradingSessionRequest.php`
- `StoreResultSheetTemplateRequest.php`
- `UpdateResultSheetTemplateRequest.php`
- `MarkPrintedRequest.php`

- [ ] **Step 1: Update all Guidance Office requests**

```php
// StoreAptitudeAreaRequest, UpdateAptitudeAreaRequest,
// StoreGradingSessionRequest,
// StoreResultSheetTemplateRequest, UpdateResultSheetTemplateRequest,
// MarkPrintedRequest
return $this->user()?->hasAnyRole(['super_admin', 'test_administrator']) ?? false;
```

- [ ] **Step 2: Commit Tasks 4 + 5**

```bash
git add app/Http/Requests/
git commit -m "fix: update FormRequest authorize() for registrar_administrator and test_administrator"
```

---

## Task 6: Policies

**Files:**
- `app/Policies/AcademicYearPolicy.php`
- `app/Policies/ApplicationPolicy.php`
- `app/Policies/AptitudeAreaPolicy.php`

- [ ] **Step 1: Update AcademicYearPolicy**

Every method currently has `['super_admin', 'admin']` — change all to:

```php
return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
```

- [ ] **Step 2: Update ApplicationPolicy**

```php
// viewAny, view — Registrar Office + staff only (test_administrator removed)
return $user->hasAnyRole(['super_admin', 'registrar_administrator', 'staff']);

// accept, dismiss, resendSetupEmail, update (bulk), admissionSlip
return $user->hasAnyRole(['super_admin', 'registrar_administrator', 'staff']);

// delete — registrar_administrator + super_admin only (was admin)
return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
```

Also update the PHPDoc comment on `viewAny`: change `admin, registrar_administrator` → `registrar_administrator, staff`.

- [ ] **Step 3: Update AptitudeAreaPolicy**

```php
// All three methods
return $user->hasAnyRole(['super_admin', 'test_administrator']);
```

- [ ] **Step 4: Commit**

```bash
git add app/Policies/
git commit -m "fix: update Policies for registrar_administrator and test_administrator"
```

---

## Task 7: Controllers and Services

**Files:**
- `app/Http/Controllers/Admin/ExamSessionController.php`
- `app/Http/Controllers/Proctor/SessionRosterController.php`
- `app/Services/DashboardService.php`

- [ ] **Step 1: ExamSessionController**

Find all occurrences and replace:

| Find | Replace |
|---|---|
| `hasAnyRole(['proctor']) && ! $user->hasAnyRole(['super_admin', 'admin'])` | `hasAnyRole(['proctor']) && ! $user->hasAnyRole(['super_admin', 'registrar_administrator'])` |
| `hasAnyRole(['registrar_administrator']) && ! $user->hasAnyRole(['super_admin', 'admin'])` | `hasAnyRole(['test_administrator']) && ! $user->hasAnyRole(['super_admin', 'registrar_administrator'])` |
| `hasAnyRole(['super_admin', 'admin'])` (line ~475 gate) | `hasAnyRole(['super_admin', 'registrar_administrator'])` |

- [ ] **Step 2: SessionRosterController**

```php
// Two occurrences of canOverride/canOverrideSchedule
$canOverrideSchedule = $user->hasAnyRole(['registrar_administrator', 'super_admin']);
$canOverride = $user->hasAnyRole(['registrar_administrator', 'super_admin']);
```

- [ ] **Step 3: DashboardService**

```php
// Line ~23 — application KPI gate
if (! $user->hasAnyRole(['super_admin', 'registrar_administrator'])) {

// Line ~63 — session KPI gate
if (! $user->hasAnyRole(['super_admin', 'proctor', 'test_administrator'])) {

// Line ~123 — grading KPI gate
if (! $user->hasAnyRole(['super_admin', 'test_administrator'])) {
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/ app/Services/DashboardService.php
git commit -m "fix: update controller and service role checks"
```

---

## Task 8: Svelte Navigation

**Files:**
- `resources/js/Layouts/AuthenticatedLayout.svelte`

- [ ] **Step 1: Replace navSections**

Find the `navSections` `$derived` block and replace the entire sections array:

```js
const navSections = $derived([
  { label: null, items: [{ href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['*'] }] },
  { label: 'Registrar Office', items: [
    { href: '/admin/academic-years', label: 'Academic Years', icon: CalendarRange, roles: ['super_admin', 'registrar_administrator'] },
    { href: '/applications', label: 'Applications', icon: FileText, roles: ['super_admin', 'registrar_administrator', 'staff'] },
    { href: '/admin/test-scheduling', label: 'Exam Scheduling', icon: Calendar, roles: ['super_admin', 'registrar_administrator'] },
  ]},
  { label: 'Guidance Office', items: [
    { href: '/admin/test-scheduling', label: 'My Sessions', icon: Calendar, roles: ['proctor'] },
    { href: '/admin/test-scheduling/monitoring', label: 'Exam Monitoring', icon: Activity, roles: ['super_admin', 'test_administrator', 'proctor'] },
    { href: '/grading', label: 'Grading', icon: GraduationCap, roles: ['super_admin', 'test_administrator'] },
    { href: '/release', label: 'Release', icon: SendHorizonal, roles: ['super_admin', 'test_administrator'] },
    { href: '/admin/aptitude-areas', label: 'Aptitude Areas', icon: Layers, roles: ['super_admin', 'test_administrator'] },
    { href: '/admin/result-sheet-templates', label: 'Result Sheet Templates', icon: FileText, roles: ['super_admin', 'test_administrator'] },
  ]},
  { label: 'Administration', collapsible: true, items: [
    { href: '/admin/users', label: 'Users', icon: Users, roles: ['super_admin'] },
    { href: '/admin/settings', label: 'Settings', icon: Settings, roles: ['super_admin'] },
    { href: '/admin/logs', label: 'Audit Log', icon: ScrollText, roles: ['super_admin'] },
    { href: '/admin/ai-companion', label: 'AI Companion', icon: Bot, roles: ['super_admin'], featureFlag: 'ai_exam_companion_enabled' },
  ]},
].map((section) => ({
  ...section,
  items: section.items.filter((item) => canSee(item.roles, item)),
})).filter((section) => section.items.length > 0));
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "fix: update nav role strings and move aptitude areas/result sheets to Guidance Office"
```

---

## Task 9: Tests

**Files:**
- `tests/Feature/Admin/AcademicYearControllerTest.php`
- `tests/Feature/Admin/AptitudeAreaControllerTest.php`
- `tests/Feature/Admin/CourseActivationTest.php`
- `tests/Feature/Admin/ExamSessionValidationTest.php`
- `tests/Feature/Admin/RoomActivationTest.php`
- `tests/Feature/DashboardControllerTest.php`
- `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Update AcademicYearControllerTest.php**

Find role references and update:

```php
// Line ~25: was 'admin'
$user->roles()->attach(Role::where('name', 'registrar_administrator')->first());

// Line ~41: was 'registrar_administrator' (old slug — now unauthorized for academic years)
// This test was checking that old registrar_administrator (now test_administrator) is DENIED.
// Update to verify test_administrator is denied:
$user->roles()->attach(Role::where('name', 'test_administrator')->first());
```

- [ ] **Step 2: Update AptitudeAreaControllerTest.php**

```php
// Line ~25: was 'registrar_administrator' (the authorized role)
$user->roles()->attach(Role::where('name', 'test_administrator')->first());

// Line ~32: was 'admin' (the unauthorized role for aptitude areas)
$user->roles()->attach(Role::where('name', 'registrar_administrator')->first());
```

- [ ] **Step 3: Update remaining test files**

For `CourseActivationTest.php`, `ExamSessionValidationTest.php`, `RoomActivationTest.php`:

```php
// Line ~24 in each: was 'admin'
$user->roles()->attach(Role::where('name', 'registrar_administrator')->first());
```

For `DashboardControllerTest.php` and `DashboardTest.php`:

```php
// Role::create / factory line: was name 'admin'
Role::factory()->create(['name' => 'registrar_administrator']);
// or
'name' => 'registrar_administrator',
```

- [ ] **Step 4: Run affected tests**

```bash
php artisan test --compact tests/Feature/Admin/AcademicYearControllerTest.php tests/Feature/Admin/AptitudeAreaControllerTest.php tests/Feature/Admin/CourseActivationTest.php tests/Feature/Admin/ExamSessionValidationTest.php tests/Feature/Admin/RoomActivationTest.php tests/Feature/DashboardControllerTest.php tests/Feature/DashboardTest.php
```

Expected: all PASS.

- [ ] **Step 5: Run full suite**

```bash
php artisan test --compact
```

Expected: all PASS (or same pass rate as before this plan — no new failures).

- [ ] **Step 6: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add tests/
git commit -m "test: update role slugs in feature tests for registrar_administrator and test_administrator"
```

---

## Self-Review Checklist

- [x] Spec coverage: DB rename → seeder → routes → requests → policies → controllers → nav → tests — all covered
- [x] No placeholders: all code blocks are complete
- [x] Type consistency: `registrar_administrator` and `test_administrator` used consistently in all tasks
- [x] Access matrix enforced in routes (Task 3) matches FormRequests (Tasks 4–5) matches Policies (Task 6) matches Nav (Task 8)
- [x] The `admin` slug disappears entirely after Task 2; no task re-introduces it
- [x] Migration `down()` is reversible
