# Audit Amendments Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement all 13 audit amendments from the 2026-04-07 system audit, in a safe-first order that minimizes risk and allows testing after each phase.

**Architecture:** Risk-first sequencing — safe code fixes first, then UI toggles, then database column removals, then full feature removal. Each phase ends with a git commit checkpoint so you can roll back cleanly if anything goes wrong.

**Tech Stack:** Laravel 12 (PHP 8.2), Svelte 5 (Inertia.js), SQLite/MySQL, PHPUnit via `php artisan test`

---

## ⚠️ PRE-READ: What's Already Done

Before starting, know that these items from the audit are **already implemented** — skip them:

| Audit Item | Status |
|---|---|
| Amendment 12: Paginate Users | ✅ Done — `UserController::index()` already uses `paginate(15)` |
| Section 9: Breadcrumbs on Applications/Show | ✅ Done — `Show.svelte` already has breadcrumbs |
| Section 9: Breadcrumbs on Applications/PrintSlips | ✅ Done — `PrintSlips.svelte` already has breadcrumbs |
| Amendments 3 & 4: Deactivate rooms/courses | ✅ Done — only the **Activate** button is missing |

---

## File Map

| File | Changed in Task(s) |
|---|---|
| `app/Http/Requests/StoreExamSessionRequest.php` | 1 |
| `bootstrap/app.php` | 2 |
| `routes/console.php` | 2 |
| `resources/js/Pages/Admin/Seasons/Index.svelte` | 3 |
| `resources/js/Pages/Applications/Apply.svelte` | 3 |
| `app/Http/Controllers/Admin/RoomController.php` | 4 |
| `routes/web.php` | 4, 5 |
| `resources/js/Pages/Admin/Rooms/Index.svelte` | 4 |
| `app/Http/Controllers/Admin/CourseController.php` | 5 |
| `resources/js/Pages/Admin/Courses/Index.svelte` | 5 |
| `app/Models/Room.php` | 6 |
| `app/Http/Requests/StoreRoomRequest.php` | 6 |
| `app/Http/Requests/UpdateRoomRequest.php` | 6 |
| `resources/js/Pages/Admin/Rooms/Create.svelte` | 6 |
| `resources/js/Pages/Admin/Rooms/EditForm.svelte` | 6 |
| `app/Models/Course.php` | 7, 8 |
| `app/Http/Requests/StoreCourseRequest.php` | 7, 8 |
| `app/Http/Requests/UpdateCourseRequest.php` | 7, 8 |
| `app/Http/Controllers/Admin/CourseController.php` | 7, 8 |
| `resources/js/Pages/Admin/Courses/Create.svelte` | 7, 8 |
| `resources/js/Pages/Admin/Courses/Edit.svelte` | 7, 8 |
| `resources/js/Pages/Admin/Courses/Index.svelte` | 7, 8 |
| `app/Models/Department.php` | 8 (delete) |
| `app/Services/AdmissionSlipService.php` | 9 |
| `app/Services/AdmissionSlipTemplateService.php` | 9 |
| `app/Services/ResultSheetTemplateService.php` | 9 |
| `app/Services/QrCodeService.php` | 9 (delete) |
| `resources/views/pdf/admission-slip.blade.php` | 9 |
| `tests/Unit/Services/QrCodeServiceTest.php` | 9 (delete) |

**New files created:**
| File | Task |
|---|---|
| `tests/Feature/Admin/ExamSessionValidationTest.php` | 1 |
| `tests/Feature/Admin/RoomActivationTest.php` | 4 |
| `tests/Feature/Admin/CourseActivationTest.php` | 5 |
| `database/migrations/..._drop_facilities_from_rooms_table.php` | 6 |
| `database/migrations/..._drop_quota_and_score_cutoff_from_courses_table.php` | 7 |
| `database/migrations/..._drop_department_from_courses_table.php` | 8 |

---

## ✅ PHASE 1 CHECKPOINT
> After Tasks 1–3: run `php artisan test` — all tests pass, app loads, UI labels updated. Then commit and tag the checkpoint.

## ✅ PHASE 2 CHECKPOINT
> After Tasks 4–5: manually test activate/deactivate for a room and a course in the browser.

## ✅ PHASE 3–5 CHECKPOINT
> After each migration task: run `php artisan migrate`, then `php artisan test`, then test the form in the browser.

## ✅ PHASE 6 CHECKPOINT
> After Task 9: generate an admission slip PDF or HTML preview — confirm no QR code appears.

---

## Task 1: Fix Exam Session Create Validation (A9 + A10)

> **What & Why:** When creating an exam session, `start_time` accepts any string like `"25:99"` or `"abc"` (should be `HH:MM` format), and `date` accepts past dates like `2020-01-01` (sessions should not be created in the past). We fix both in one request class.
>
> **Note:** We only fix `StoreExamSessionRequest` (create). The update request (`UpdateExamSessionRequest`) already has `date_format:H:i` on `start_time`, and we intentionally skip adding `after_or_equal:today` on update because admins may need to edit sessions that already occurred.

**Files:**
- Create: `tests/Feature/Admin/ExamSessionValidationTest.php`
- Modify: `app/Http/Requests/StoreExamSessionRequest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSessionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        return $user;
    }

    private function makeRoom(): Room
    {
        return Room::create([
            'name' => 'Room 101',
            'building' => 'Main Building',
            'capacity' => 30,
            'is_active' => true,
        ]);
    }

    public function test_store_rejects_past_date(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.test-scheduling.store'), [
                'room_id'    => $room->id,
                'date'       => '2020-01-01',
                'start_time' => '08:00',
            ]);

        $response->assertSessionHasErrors(['date']);
    }

    public function test_store_accepts_today_date(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.test-scheduling.store'), [
                'room_id'    => $room->id,
                'date'       => now()->toDateString(),
                'start_time' => '08:00',
            ]);

        // Should NOT have a date error (may fail on other fields, that's OK)
        $response->assertSessionMissing('errors.date');
    }

    public function test_store_rejects_invalid_start_time_format(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.test-scheduling.store'), [
                'room_id'    => $room->id,
                'date'       => now()->addDay()->toDateString(),
                'start_time' => '25:99',
            ]);

        $response->assertSessionHasErrors(['start_time']);
    }

    public function test_store_accepts_valid_start_time_format(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.test-scheduling.store'), [
                'room_id'    => $room->id,
                'date'       => now()->addDay()->toDateString(),
                'start_time' => '08:00',
            ]);

        $response->assertSessionMissing('errors.start_time');
    }

    public function test_store_rejects_invalid_end_time_format(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.test-scheduling.store'), [
                'room_id'    => $room->id,
                'date'       => now()->addDay()->toDateString(),
                'start_time' => '08:00',
                'end_time'   => 'not-a-time',
            ]);

        $response->assertSessionHasErrors(['end_time']);
    }
}
```

- [ ] **Step 2: Run the tests — confirm they FAIL**

```bash
php artisan test tests/Feature/Admin/ExamSessionValidationTest.php
```

Expected: 2–3 tests FAIL because the current validation is too loose (no `after_or_equal:today`, no `date_format:H:i`).

- [ ] **Step 3: Update `StoreExamSessionRequest.php`**

Open `app/Http/Requests/StoreExamSessionRequest.php`. Replace the entire `rules()` method:

```php
public function rules(): array
{
    return [
        'season_id'     => ['sometimes', 'nullable', 'integer', 'exists:seasons,id'],
        'room_id'       => ['required', 'integer', 'exists:rooms,id'],
        'date'          => ['required', 'date', 'after_or_equal:today'],
        'start_time'    => ['required', 'string', 'date_format:H:i'],
        'end_time'      => ['nullable', 'string', 'date_format:H:i'],
        'proctor_ids'   => ['sometimes', 'array'],
        'proctor_ids.*' => ['integer', 'exists:users,id'],
    ];
}
```

- [ ] **Step 4: Run the tests — confirm they PASS**

```bash
php artisan test tests/Feature/Admin/ExamSessionValidationTest.php
```

Expected output:
```
PASS  Tests\Feature\Admin\ExamSessionValidationTest
✓ store rejects past date
✓ store accepts today date
✓ store rejects invalid start time format
✓ store accepts valid start time format
✓ store rejects invalid end time format
```

- [ ] **Step 5: Run the full test suite to confirm nothing broke**

```bash
php artisan test
```

Expected: all existing tests still pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/StoreExamSessionRequest.php \
        tests/Feature/Admin/ExamSessionValidationTest.php
git commit -m "fix: add date_format and after_or_equal:today validation to StoreExamSessionRequest"
```

---

## Task 2: Schedule the ExpireSeasonApplications Command (A11)

> **What & Why:** The `seasons:expire-applications` artisan command exists but is never run automatically. We register it to run every day at 00:05 so pending applications are dismissed when the application window closes.
>
> **How Laravel 12 scheduling works:** In Laravel 12, you register scheduled commands in `routes/console.php` using the `Schedule` facade. This is different from older Laravel versions that used `app/Console/Kernel.php`.

**Files:**
- Modify: `routes/console.php`

- [ ] **Step 1: Open `routes/console.php` and add the schedule**

The file currently looks like this:

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

Add these lines at the end of the file:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('seasons:expire-applications')->dailyAt('00:05');
```

The full file should now look like:

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('seasons:expire-applications')->dailyAt('00:05');
```

- [ ] **Step 2: Verify the command appears in the schedule list**

```bash
php artisan schedule:list
```

Expected output (somewhere in the list):
```
0 5 * * *  php artisan seasons:expire-applications  Next Due: ...
```

- [ ] **Step 3: Test the command itself still runs correctly**

```bash
php artisan test tests/Feature/ExpireSeasonApplicationsCommandTest.php
```

Expected: all tests pass.

- [ ] **Step 4: Commit**

```bash
git add routes/console.php
git commit -m "feat: schedule seasons:expire-applications to run daily at 00:05"
```

---

## Task 3: Rename "Season" → "Academic Year" in UI Labels (A13)

> **What & Why:** The codebase stores data as "seasons" internally (model names, DB columns, variable names — those stay unchanged). But in the user-facing UI, the word "Season" is confusing. We rename display labels only: page titles, headings, and column headers. Two files need changes.
>
> ⚠️ Do NOT rename: database columns, model names, controller names, variable names, route names, or anything in PHP files. Display labels only.

**Files:**
- Modify: `resources/js/Pages/Admin/Seasons/Index.svelte`
- Modify: `resources/js/Pages/Applications/Apply.svelte`

- [ ] **Step 1: Update `Admin/Seasons/Index.svelte` — page title, heading, column header**

Make these 3 changes in `resources/js/Pages/Admin/Seasons/Index.svelte`:

**Change 1** — page title (line 17):
```svelte
<!-- BEFORE -->
<title>Seasons - SecureCAT</title>

<!-- AFTER -->
<title>Academic Years - SecureCAT</title>
```

**Change 2** — page heading (line 21):
```svelte
<!-- BEFORE -->
<h1 class="text-2xl font-bold">Seasons</h1>

<!-- AFTER -->
<h1 class="text-2xl font-bold">Academic Years</h1>
```

**Change 3** — table column header (line 52):
```svelte
<!-- BEFORE -->
<th class="px-4 py-3 text-left font-medium">Season</th>

<!-- AFTER -->
<th class="px-4 py-3 text-left font-medium">Academic Year</th>
```

- [ ] **Step 2: Update `Applications/Apply.svelte` — add semester to the display**

In `resources/js/Pages/Applications/Apply.svelte`, find this block (around line 75):

```svelte
{#if active_season}
  A.Y. {active_season.academic_year}
{:else}
```

Change it to:

```svelte
{#if active_season}
  A.Y. {active_season.academic_year} – {active_season.semester}
{:else}
```

- [ ] **Step 3: Verify in the browser**

Start the dev server if not already running:
```bash
npm run dev
```

1. Go to `/admin/seasons` — should show "Academic Years" as the page title and heading
2. Go to `/apply` (when a season is active) — should show "A.Y. 2025-2026 – 1" in the card subtitle

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Admin/Seasons/Index.svelte \
        resources/js/Pages/Applications/Apply.svelte
git commit -m "refactor: rename Season to Academic Year in UI display labels only"
```

---

## ✅ PHASE 1 DONE — Run Full Test Suite

```bash
php artisan test
```

All tests should pass. If any fail, fix them before proceeding.

---

## Task 4: Add Activate Button for Rooms (A3)

> **What & Why:** The Rooms index already has a "Deactivate" button for active rooms. What's missing is an "Activate" button for inactive rooms. We add a backend endpoint and a frontend button.
>
> **Route:** `POST /admin/rooms/{room}/activate` — returns a redirect with a success flash message.

**Files:**
- Create: `tests/Feature/Admin/RoomActivationTest.php`
- Modify: `app/Http/Controllers/Admin/RoomController.php`
- Modify: `routes/web.php`
- Modify: `resources/js/Pages/Admin/Rooms/Index.svelte`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Admin/RoomActivationTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        return $user;
    }

    public function test_admin_can_activate_inactive_room(): void
    {
        $room = Room::create([
            'name' => 'Room 101',
            'building' => 'Main Building',
            'capacity' => 30,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.rooms.activate', $room));

        $response->assertRedirect(route('admin.rooms.index'));
        $response->assertSessionHas('success');
        $this->assertTrue($room->fresh()->is_active);
    }

    public function test_proctor_cannot_activate_room(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'proctor')->first());

        $room = Room::create([
            'name' => 'Room 101',
            'building' => 'Main Building',
            'capacity' => 30,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('admin.rooms.activate', $room));

        $response->assertStatus(403);
        $this->assertFalse($room->fresh()->is_active);
    }
}
```

- [ ] **Step 2: Run the test — confirm it FAILS**

```bash
php artisan test tests/Feature/Admin/RoomActivationTest.php
```

Expected: FAIL — route `admin.rooms.activate` does not exist yet.

- [ ] **Step 3: Add the `activate()` method to `RoomController`**

Open `app/Http/Controllers/Admin/RoomController.php`. Add this method after the `destroy()` method:

```php
public function activate(Room $room): RedirectResponse
{
    $room->update(['is_active' => true]);

    return redirect()->route('admin.rooms.index')->with('success', 'Room activated.');
}
```

- [ ] **Step 4: Register the activate route in `routes/web.php`**

In `routes/web.php`, find this line:

```php
Route::resource('rooms', RoomController::class)->except('show')->parameters(['rooms' => 'room']);
```

Add a new route directly after it:

```php
Route::resource('rooms', RoomController::class)->except('show')->parameters(['rooms' => 'room']);
Route::post('rooms/{room}/activate', [RoomController::class, 'activate'])->name('rooms.activate');
```

- [ ] **Step 5: Run the test — confirm it PASSES**

```bash
php artisan test tests/Feature/Admin/RoomActivationTest.php
```

Expected: both tests pass.

- [ ] **Step 6: Add the Activate button to `resources/js/Pages/Admin/Rooms/Index.svelte`**

In the **table view** section, find the actions cell for each room. Look for this block (around line 161):

```svelte
<Table.Cell class="px-4 py-3 text-right">
  <div class="flex justify-end gap-2">
    <Link href={`/admin/rooms/${room.id}/edit`}>
      <Button variant="ghost" size="icon" aria-label="Edit">
        <Pencil class="h-4 w-4" />
      </Button>
    </Link>
    {#if room.is_active}
      <Button
        variant="ghost"
        size="icon"
        aria-label="Deactivate"
        class="text-destructive hover:text-destructive"
        onclick={() => confirmDelete(room.id)}
      >
        <Trash2 class="h-4 w-4" />
      </Button>
    {/if}
  </div>
</Table.Cell>
```

Replace it with:

```svelte
<Table.Cell class="px-4 py-3 text-right">
  <div class="flex justify-end gap-2">
    <Link href={`/admin/rooms/${room.id}/edit`}>
      <Button variant="ghost" size="icon" aria-label="Edit">
        <Pencil class="h-4 w-4" />
      </Button>
    </Link>
    {#if room.is_active}
      <Button
        variant="ghost"
        size="icon"
        aria-label="Deactivate"
        class="text-destructive hover:text-destructive"
        onclick={() => confirmDelete(room.id)}
      >
        <Trash2 class="h-4 w-4" />
      </Button>
    {:else}
      <Button
        variant="ghost"
        size="icon"
        aria-label="Activate"
        class="text-primary hover:text-primary"
        onclick={() => router.post(`/admin/rooms/${room.id}/activate`)}
      >
        <CheckCircle class="h-4 w-4" />
      </Button>
    {/if}
  </div>
</Table.Cell>
```

In the **card view** section, find the card actions (around line 222):

```svelte
<div class="mt-auto flex gap-2 pt-2">
  <Link href={`/admin/rooms/${room.id}/edit`} class="flex-1">
    <Button variant="outline" size="sm" class="w-full min-h-[44px]">
      <Pencil class="h-4 w-4 mr-1.5" />
      Edit
    </Button>
  </Link>
  {#if room.is_active}
    <Button
      variant="outline"
      size="sm"
      class="min-h-[44px] text-destructive hover:text-destructive"
      aria-label="Deactivate"
      onclick={() => confirmDelete(room.id)}
    >
      <Trash2 class="h-4 w-4" />
    </Button>
  {/if}
</div>
```

Replace with:

```svelte
<div class="mt-auto flex gap-2 pt-2">
  <Link href={`/admin/rooms/${room.id}/edit`} class="flex-1">
    <Button variant="outline" size="sm" class="w-full min-h-[44px]">
      <Pencil class="h-4 w-4 mr-1.5" />
      Edit
    </Button>
  </Link>
  {#if room.is_active}
    <Button
      variant="outline"
      size="sm"
      class="min-h-[44px] text-destructive hover:text-destructive"
      aria-label="Deactivate"
      onclick={() => confirmDelete(room.id)}
    >
      <Trash2 class="h-4 w-4" />
    </Button>
  {:else}
    <Button
      variant="outline"
      size="sm"
      class="min-h-[44px] text-primary hover:text-primary"
      aria-label="Activate"
      onclick={() => router.post(`/admin/rooms/${room.id}/activate`)}
    >
      <CheckCircle class="h-4 w-4" />
    </Button>
  {/if}
</div>
```

Also add `CheckCircle` to the imports at the top of the script block. Find this line:

```svelte
import { Plus, Pencil, Trash2, LayoutGrid, Table2, MonitorSmartphone } from 'lucide-svelte';
```

Change to:

```svelte
import { Plus, Pencil, Trash2, LayoutGrid, Table2, MonitorSmartphone, CheckCircle } from 'lucide-svelte';
```

- [ ] **Step 7: Verify in the browser**

1. Go to `/admin/rooms`
2. Create an inactive room (or deactivate an existing one)
3. Confirm the green checkmark button appears for inactive rooms
4. Click it — the room should become active, and a success toast should appear

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Admin/RoomController.php \
        routes/web.php \
        resources/js/Pages/Admin/Rooms/Index.svelte \
        tests/Feature/Admin/RoomActivationTest.php
git commit -m "feat: add activate button for inactive rooms (A3)"
```

---

## Task 5: Add Activate Button for Courses (A4)

> **What & Why:** Same pattern as Task 4, but for Courses. The deactivate endpoint (`destroy()`) already exists. We add an activate endpoint.

**Files:**
- Create: `tests/Feature/Admin/CourseActivationTest.php`
- Modify: `app/Http/Controllers/Admin/CourseController.php`
- Modify: `routes/web.php`
- Modify: `resources/js/Pages/Admin/Courses/Index.svelte`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Admin/CourseActivationTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        return $user;
    }

    public function test_admin_can_activate_inactive_course(): void
    {
        $course = Course::create([
            'name'      => 'Bachelor of Science in IT',
            'code'      => 'BSIT',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.courses.activate', $course));

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('success');
        $this->assertTrue($course->fresh()->is_active);
    }

    public function test_proctor_cannot_activate_course(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'proctor')->first());

        $course = Course::create([
            'name'      => 'Bachelor of Science in IT',
            'code'      => 'BSIT',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('admin.courses.activate', $course));

        $response->assertStatus(403);
        $this->assertFalse($course->fresh()->is_active);
    }
}
```

- [ ] **Step 2: Run the test — confirm it FAILS**

```bash
php artisan test tests/Feature/Admin/CourseActivationTest.php
```

Expected: FAIL — route `admin.courses.activate` does not exist yet.

- [ ] **Step 3: Add `activate()` to `CourseController`**

Open `app/Http/Controllers/Admin/CourseController.php`. Add this method after `destroy()`:

```php
public function activate(Course $course): RedirectResponse
{
    $course->update(['is_active' => true]);

    return redirect()->route('admin.courses.index')->with('success', 'Course activated.');
}
```

- [ ] **Step 4: Register the activate route in `routes/web.php`**

Find:

```php
Route::resource('courses', CourseController::class)->except('show')->parameters(['courses' => 'course']);
```

Add after it:

```php
Route::resource('courses', CourseController::class)->except('show')->parameters(['courses' => 'course']);
Route::post('courses/{course}/activate', [CourseController::class, 'activate'])->name('courses.activate');
```

- [ ] **Step 5: Run the test — confirm it PASSES**

```bash
php artisan test tests/Feature/Admin/CourseActivationTest.php
```

- [ ] **Step 6: Add the Activate button to `resources/js/Pages/Admin/Courses/Index.svelte`**

Add `CheckCircle` to the imports at the top:

```svelte
import { Plus, Pencil, Trash2, Table2, LayoutGrid, MonitorSmartphone, CheckCircle } from 'lucide-svelte';
```

In the **table view**, find the actions cell (around line 123):

```svelte
<td class="px-4 py-3 text-right">
  <div class="flex justify-end gap-2">
    <Link href={`/admin/courses/${course.id}/edit`}>
      <Button variant="ghost" size="icon" aria-label="Edit">
        <Pencil class="h-4 w-4" />
      </Button>
    </Link>
    {#if course.is_active}
      <Button
        variant="ghost"
        size="icon"
        aria-label="Deactivate"
        class="text-destructive hover:text-destructive"
        onclick={() => confirmDelete(course.id)}
      >
        <Trash2 class="h-4 w-4" />
      </Button>
    {/if}
  </div>
</td>
```

Replace with:

```svelte
<td class="px-4 py-3 text-right">
  <div class="flex justify-end gap-2">
    <Link href={`/admin/courses/${course.id}/edit`}>
      <Button variant="ghost" size="icon" aria-label="Edit">
        <Pencil class="h-4 w-4" />
      </Button>
    </Link>
    {#if course.is_active}
      <Button
        variant="ghost"
        size="icon"
        aria-label="Deactivate"
        class="text-destructive hover:text-destructive"
        onclick={() => confirmDelete(course.id)}
      >
        <Trash2 class="h-4 w-4" />
      </Button>
    {:else}
      <Button
        variant="ghost"
        size="icon"
        aria-label="Activate"
        class="text-primary hover:text-primary"
        onclick={() => router.post(`/admin/courses/${course.id}/activate`)}
      >
        <CheckCircle class="h-4 w-4" />
      </Button>
    {/if}
  </div>
</td>
```

In the **card view**, find the card actions (around line 172):

```svelte
<div class="mt-auto flex gap-2 pt-2">
  <Link href={`/admin/courses/${course.id}/edit`} class="flex-1">
    <Button variant="outline" size="sm" class="w-full min-h-[44px]">
      <Pencil class="h-4 w-4 mr-1.5" />
      Edit
    </Button>
  </Link>
  {#if course.is_active}
    <Button
      variant="outline"
      size="sm"
      class="min-h-[44px] text-destructive hover:text-destructive"
      aria-label="Deactivate"
      onclick={() => confirmDelete(course.id)}
    >
      <Trash2 class="h-4 w-4" />
    </Button>
  {/if}
</div>
```

Replace with:

```svelte
<div class="mt-auto flex gap-2 pt-2">
  <Link href={`/admin/courses/${course.id}/edit`} class="flex-1">
    <Button variant="outline" size="sm" class="w-full min-h-[44px]">
      <Pencil class="h-4 w-4 mr-1.5" />
      Edit
    </Button>
  </Link>
  {#if course.is_active}
    <Button
      variant="outline"
      size="sm"
      class="min-h-[44px] text-destructive hover:text-destructive"
      aria-label="Deactivate"
      onclick={() => confirmDelete(course.id)}
    >
      <Trash2 class="h-4 w-4" />
    </Button>
  {:else}
    <Button
      variant="outline"
      size="sm"
      class="min-h-[44px] text-primary hover:text-primary"
      aria-label="Activate"
      onclick={() => router.post(`/admin/courses/${course.id}/activate`)}
    >
      <CheckCircle class="h-4 w-4" />
    </Button>
  {/if}
</div>
```

- [ ] **Step 7: Verify in the browser**

1. Go to `/admin/courses`
2. Deactivate a course
3. The green checkmark button should appear for the inactive course
4. Click it — course becomes active again

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Admin/CourseController.php \
        routes/web.php \
        resources/js/Pages/Admin/Courses/Index.svelte \
        tests/Feature/Admin/CourseActivationTest.php
git commit -m "feat: add activate button for inactive courses (A4)"
```

---

## ✅ PHASE 2 DONE — Run Full Test Suite

```bash
php artisan test
```

---

## Task 6: Remove `facilities` Column from Rooms (A5)

> **What & Why:** The `facilities` field (a JSON column storing projector/AC/whiteboard booleans) is being removed — it adds complexity with no real business use in the current scope. We remove it from: the database, the model, the validation requests, the controller, and both Svelte form files.
>
> ⚠️ **DATABASE MIGRATION WARNING:** This step permanently removes the `facilities` column from the `rooms` table. Make sure all previous work is committed before running `php artisan migrate`.
>
> To undo: `php artisan migrate:rollback` (this restores the column — but any data already in the column will be gone).

**Files:**
- Create: `database/migrations/TIMESTAMP_drop_facilities_from_rooms_table.php`
- Modify: `app/Models/Room.php`
- Modify: `app/Http/Requests/StoreRoomRequest.php`
- Modify: `app/Http/Requests/UpdateRoomRequest.php`
- Modify: `app/Http/Controllers/Admin/RoomController.php`
- Modify: `resources/js/Pages/Admin/Rooms/Create.svelte`
- Modify: `resources/js/Pages/Admin/Rooms/EditForm.svelte`
- Modify: `resources/js/Pages/Admin/Rooms/Index.svelte`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration drop_facilities_from_rooms_table
```

This creates a file in `database/migrations/` with a name like `2026_04_08_000001_drop_facilities_from_rooms_table.php`. Open it and replace the contents with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('facilities');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->json('facilities')->nullable()->after('capacity');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected output:
```
Running migrations.
2026_04_08_..._drop_facilities_from_rooms_table ......... 3ms DONE
```

If you see an error, do NOT continue. Run `php artisan migrate:rollback` and investigate.

- [ ] **Step 3: Update `app/Models/Room.php`**

Remove `'facilities'` from `$fillable` and remove the `facilities` cast. Replace the file content:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'building',
        'floor',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

- [ ] **Step 4: Update `app/Http/Requests/StoreRoomRequest.php`**

Remove the `facilities` validation rule. The `rules()` method should be:

```php
public function rules(): array
{
    return [
        'name'     => ['required', 'string', 'max:255'],
        'building' => ['required', 'string', 'max:255'],
        'floor'    => ['nullable', 'string', 'max:50'],
        'capacity' => ['required', 'integer', 'min:1'],
    ];
}
```

- [ ] **Step 5: Update `app/Http/Requests/UpdateRoomRequest.php`**

```php
public function rules(): array
{
    return [
        'name'     => ['sometimes', 'string', 'max:255'],
        'building' => ['sometimes', 'string', 'max:255'],
        'floor'    => ['nullable', 'string', 'max:50'],
        'capacity' => ['sometimes', 'integer', 'min:1'],
    ];
}
```

- [ ] **Step 6: Update `app/Http/Controllers/Admin/RoomController.php`**

In the `store()` method, remove `'facilities'` from the `Room::create()` call:

```php
public function store(StoreRoomRequest $request): RedirectResponse
{
    $validated = $request->validated();

    Room::create([
        'name'      => $validated['name'],
        'building'  => $validated['building'],
        'floor'     => $validated['floor'] ?? null,
        'capacity'  => $validated['capacity'],
        'is_active' => true,
    ]);

    return redirect()->route('admin.rooms.index')->with('success', 'Room created.');
}
```

- [ ] **Step 7: Update `resources/js/Pages/Admin/Rooms/Create.svelte`**

Remove the `let facilities` state variable and the entire Facilities section from the form.

In the `<script>` block, remove this line:
```svelte
let facilities = $state({ projector: false, ac: false, whiteboard: false });
```

In the `submitForm` function, change:
```svelte
function submitForm(e) {
  e.preventDefault();
  $form.transform((data) => ({
    ...data,
    capacity: parseInt(data.capacity, 10) || 0,
    facilities,
  }));
  $form.post('/admin/rooms');
}
```

To:
```svelte
function submitForm(e) {
  e.preventDefault();
  $form.transform((data) => ({
    ...data,
    capacity: parseInt(data.capacity, 10) || 0,
  }));
  $form.post('/admin/rooms');
}
```

Remove the entire facilities section from the `<form>` (the `<div class="space-y-2">` block with Projector, Air conditioning, and Whiteboard checkboxes).

- [ ] **Step 8: Update `resources/js/Pages/Admin/Rooms/EditForm.svelte`**

In the `<script>` block, remove:
```svelte
const defaultFacilities = { projector: false, ac: false, whiteboard: false };
let facilities = $state({ ...defaultFacilities, ...(room?.facilities ?? {}) });
```

In the `submitForm` function, change the transform to remove `facilities`:
```svelte
function submitForm(e) {
  e.preventDefault();
  $form.transform((data) => ({
    ...data,
    capacity: parseInt(data.capacity, 10),
  }));
  $form.put(`/admin/rooms/${room.id}`);
}
```

Remove the entire Facilities `<div class="space-y-2">` block from the form (the one containing the projector/AC/whiteboard checkboxes).

- [ ] **Step 9: Update `resources/js/Pages/Admin/Rooms/Index.svelte`**

**Remove the `formatFacilities` function** from the script block:
```svelte
// DELETE this entire function:
function formatFacilities(facilities) {
  if (!facilities || typeof facilities !== 'object') return '—';
  const items = Object.entries(facilities)
    .filter(([, v]) => v)
    .map(([k]) => k.replace(/_/g, ' '));
  return items.length ? items.join(', ') : '—';
}
```

**In the table view**, remove the Facilities column header:
```svelte
<!-- DELETE this line: -->
<Table.Head class="px-4 py-3">Facilities</Table.Head>
```

And remove the Facilities data cell:
```svelte
<!-- DELETE this line: -->
<Table.Cell class="px-4 py-3">{formatFacilities(room.facilities)}</Table.Cell>
```

Also update the `colspan` in the empty state row from `colspan={7}` to `colspan={6}`.

**In the card view**, remove the Facilities row from the `<dl>`:
```svelte
<!-- DELETE these two lines: -->
<dt class="text-muted-foreground">Facilities</dt>
<dd class="col-span-1">{formatFacilities(room.facilities)}</dd>
```

- [ ] **Step 10: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 11: Verify in the browser**

1. Go to `/admin/rooms/create` — no Facilities checkboxes
2. Create a room — succeeds without facilities
3. Go to `/admin/rooms` — no Facilities column in the table

- [ ] **Step 12: Commit**

```bash
git add database/migrations/ \
        app/Models/Room.php \
        app/Http/Requests/StoreRoomRequest.php \
        app/Http/Requests/UpdateRoomRequest.php \
        app/Http/Controllers/Admin/RoomController.php \
        resources/js/Pages/Admin/Rooms/Create.svelte \
        resources/js/Pages/Admin/Rooms/EditForm.svelte \
        resources/js/Pages/Admin/Rooms/Index.svelte
git commit -m "feat: drop facilities column from rooms (A5)"
```

---

## Task 7: Remove `score_cutoff` and `quota` Columns from Courses (A6 + A7)

> **What & Why:** The `score_cutoff` and `quota` fields were planned for admission cutoff logic that was never implemented. Removing them reduces confusion and unused complexity. Combined into one migration since they're both Course columns.
>
> ⚠️ Same migration warning as Task 6 — commit before running `php artisan migrate`.

**Files:**
- Create: `database/migrations/TIMESTAMP_drop_quota_and_score_cutoff_from_courses_table.php`
- Modify: `app/Models/Course.php`
- Modify: `app/Http/Requests/StoreCourseRequest.php`
- Modify: `app/Http/Requests/UpdateCourseRequest.php`
- Modify: `app/Http/Controllers/Admin/CourseController.php`
- Modify: `resources/js/Pages/Admin/Courses/Create.svelte`
- Modify: `resources/js/Pages/Admin/Courses/Edit.svelte`
- Modify: `resources/js/Pages/Admin/Courses/Index.svelte`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration drop_quota_and_score_cutoff_from_courses_table
```

Open the generated file and replace with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['quota', 'score_cutoff']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->integer('quota')->nullable()->after('code');
            $table->decimal('score_cutoff', 5, 2)->nullable()->after('quota');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Update `app/Models/Course.php`**

Remove `'quota'` and `'score_cutoff'` from `$fillable`:

```php
protected $fillable = [
    'department_id',
    'name',
    'code',
    'is_active',
];
```

- [ ] **Step 4: Update `app/Http/Requests/StoreCourseRequest.php`**

```php
public function rules(): array
{
    return [
        'department_id' => ['required', 'integer', 'exists:departments,id'],
        'name'          => ['required', 'string', 'max:255'],
        'code'          => ['required', 'string', 'max:50'],
    ];
}
```

- [ ] **Step 5: Update `app/Http/Requests/UpdateCourseRequest.php`**

```php
public function rules(): array
{
    return [
        'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
        'name'          => ['sometimes', 'string', 'max:255'],
        'code'          => ['sometimes', 'string', 'max:50'],
    ];
}
```

- [ ] **Step 6: Update `app/Http/Controllers/Admin/CourseController.php`**

In the `store()` method, remove `quota` and `score_cutoff`:

```php
public function store(StoreCourseRequest $request): RedirectResponse
{
    $validated = $request->validated();

    Course::create([
        'department_id' => $validated['department_id'],
        'name'          => $validated['name'],
        'code'          => $validated['code'],
        'is_active'     => true,
    ]);

    return redirect()->route('admin.courses.index')->with('success', 'Course created.');
}
```

- [ ] **Step 7: Update `resources/js/Pages/Admin/Courses/Create.svelte`**

In the `<script>` block, remove `quota` and `score_cutoff` from the `useForm` call:

```svelte
const form = useForm({
  department_id: '',
  name: '',
  code: '',
});
```

In the `submitForm` transform, remove the `quota` and `score_cutoff` lines:

```svelte
function submitForm(e) {
  e.preventDefault();
  $form.transform((data) => ({
    ...data,
    department_id: parseInt(data.department_id, 10) || null,
  }));
  $form.post('/admin/courses');
}
```

Remove the entire `<div class="grid gap-4 sm:grid-cols-2">` block containing the Quota and Score cutoff inputs from the form.

- [ ] **Step 8: Update `resources/js/Pages/Admin/Courses/Edit.svelte`**

In the `useForm` initialization, remove `quota` and `score_cutoff`:

```svelte
const form = (() => {
  const c = course;
  return useForm({
    department_id: String(c.department_id),
    name: c.name,
    code: c.code,
    is_active: c.is_active,
  });
})();
```

In the `submitForm` transform, remove the quota/score_cutoff lines:

```svelte
function submitForm(e) {
  e.preventDefault();
  $form.transform((data) => ({
    ...data,
    department_id: parseInt(data.department_id, 10) || null,
  }));
  $form.put(`/admin/courses/${course.id}`);
}
```

Remove the entire `<div class="grid gap-4 sm:grid-cols-2">` block with Quota and Score cutoff inputs from the form.

- [ ] **Step 9: Update `resources/js/Pages/Admin/Courses/Index.svelte`**

In the `<script>` block, remove the `formatQuota` and `formatCutoff` helper functions:

```svelte
// DELETE these two functions entirely:
function formatQuota(q) { ... }
function formatCutoff(v) { ... }
```

In the **table view**, remove the Quota and Cutoff column headers:
```svelte
<!-- DELETE these two lines: -->
<th class="px-4 py-3 text-left font-medium">Quota</th>
<th class="px-4 py-3 text-left font-medium">Cutoff</th>
```

Remove the Quota and Cutoff data cells:
```svelte
<!-- DELETE these two lines: -->
<td class="px-4 py-3">{formatQuota(course.quota)}</td>
<td class="px-4 py-3">{formatCutoff(course.score_cutoff)}</td>
```

Update the empty-state row `colspan` from `7` to `5`.

In the **card view**, remove the Quota and Cutoff rows from the `<dl>`:
```svelte
<!-- DELETE these four lines: -->
<dt class="text-muted-foreground">Quota</dt>
<dd>{formatQuota(course.quota)}</dd>
<dt class="text-muted-foreground">Cutoff</dt>
<dd>{formatCutoff(course.score_cutoff)}</dd>
```

- [ ] **Step 10: Run tests and verify in browser**

```bash
php artisan test
```

Then check `/admin/courses/create` — no Quota or Score cutoff fields.

- [ ] **Step 11: Commit**

```bash
git add database/migrations/ \
        app/Models/Course.php \
        app/Http/Requests/StoreCourseRequest.php \
        app/Http/Requests/UpdateCourseRequest.php \
        app/Http/Controllers/Admin/CourseController.php \
        resources/js/Pages/Admin/Courses/Create.svelte \
        resources/js/Pages/Admin/Courses/Edit.svelte \
        resources/js/Pages/Admin/Courses/Index.svelte
git commit -m "feat: drop score_cutoff and quota columns from courses (A6 + A7)"
```

---

## Task 8: Remove `department` from Courses + Drop `departments` Table (A8)

> **What & Why:** Courses no longer need to be grouped by department. We remove the `department_id` foreign key from courses, then drop the `departments` table entirely. No other table references departments.
>
> ⚠️ This drops an entire table. Irreversible without rollback. Commit first.
>
> **Note:** After this task, `StoreCourseRequest` and `UpdateCourseRequest` must not reference `department_id`, and `CourseController::create()` and `edit()` must not load departments.

**Files:**
- Create: `database/migrations/TIMESTAMP_drop_department_from_courses_table.php`
- Modify: `app/Models/Course.php`
- Delete: `app/Models/Department.php`
- Modify: `app/Http/Requests/StoreCourseRequest.php`
- Modify: `app/Http/Requests/UpdateCourseRequest.php`
- Modify: `app/Http/Controllers/Admin/CourseController.php`
- Modify: `resources/js/Pages/Admin/Courses/Create.svelte`
- Modify: `resources/js/Pages/Admin/Courses/Edit.svelte`
- Modify: `resources/js/Pages/Admin/Courses/Index.svelte`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration drop_department_from_courses_table
```

Open the file and replace with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        Schema::dropIfExists('departments');
    }

    public function down(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Update `app/Models/Course.php`**

Remove `'department_id'` from `$fillable` and remove the `department()` relationship method. Replace the full file:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
```

- [ ] **Step 4: Delete `app/Models/Department.php`**

```bash
rm app/Models/Department.php
```

- [ ] **Step 5: Update `app/Http/Requests/StoreCourseRequest.php`**

Remove `department_id` from the rules:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'code' => ['required', 'string', 'max:50'],
    ];
}
```

- [ ] **Step 6: Update `app/Http/Requests/UpdateCourseRequest.php`**

```php
public function rules(): array
{
    return [
        'name' => ['sometimes', 'string', 'max:255'],
        'code' => ['sometimes', 'string', 'max:50'],
    ];
}
```

- [ ] **Step 7: Update `app/Http/Controllers/Admin/CourseController.php`**

Remove the `Department` import and all Department usage. Replace the full file:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $courses = Course::query()
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Courses/Create');
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Course::create([
            'name'      => $validated['name'],
            'code'      => $validated['code'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): Response
    {
        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course,
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return redirect()->route('admin.courses.index')->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->update(['is_active' => false]);

        return redirect()->route('admin.courses.index')->with('success', 'Course deactivated.');
    }

    public function activate(Course $course): RedirectResponse
    {
        $course->update(['is_active' => true]);

        return redirect()->route('admin.courses.index')->with('success', 'Course activated.');
    }
}
```

- [ ] **Step 8: Update `resources/js/Pages/Admin/Courses/Create.svelte`**

Remove `departments` from props, remove `department_id` from the form, and remove the Department select field.

Replace the `<script>` block:

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  const form = useForm({
    name: '',
    code: '',
  });

  function submitForm(e) {
    e.preventDefault();
    $form.post('/admin/courses');
  }
</script>
```

Remove the entire Department `<div class="space-y-2">` section from the form (the `<select>` for departments).

- [ ] **Step 9: Update `resources/js/Pages/Admin/Courses/Edit.svelte`**

Replace the `<script>` block:

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  const { course } = $props();

  const form = useForm({
    name: course.name,
    code: course.code,
    is_active: course.is_active,
  });

  function submitForm(e) {
    e.preventDefault();
    $form.put(`/admin/courses/${course.id}`);
  }
</script>
```

Remove the Department `<div class="space-y-2">` section from the form.

- [ ] **Step 10: Update `resources/js/Pages/Admin/Courses/Index.svelte`**

In the **table view**, remove the Department column header and its data cell:

```svelte
<!-- DELETE: -->
<th class="px-4 py-3 text-left font-medium">Department</th>
```

```svelte
<!-- DELETE: -->
<td class="px-4 py-3">{course.department?.code ?? '—'}</td>
```

Update the empty-state row `colspan` from `5` to `4`.

In the **card view**, remove the Department row from `<dl>`:

```svelte
<!-- DELETE these two lines: -->
<dt class="text-muted-foreground">Department</dt>
<dd>{course.department?.code ?? '—'}</dd>
```

- [ ] **Step 11: Run tests and verify**

```bash
php artisan test
```

Then check `/admin/courses/create` — no department field. Go to `/admin/courses` — no Department column.

- [ ] **Step 12: Commit**

```bash
git add database/migrations/ \
        app/Models/Course.php \
        app/Http/Requests/StoreCourseRequest.php \
        app/Http/Requests/UpdateCourseRequest.php \
        app/Http/Controllers/Admin/CourseController.php \
        resources/js/Pages/Admin/Courses/Create.svelte \
        resources/js/Pages/Admin/Courses/Edit.svelte \
        resources/js/Pages/Admin/Courses/Index.svelte
git rm app/Models/Department.php
git commit -m "feat: drop department_id from courses and drop departments table (A8)"
```

---

## ✅ PHASE 3–5 DONE — Run Full Test Suite

```bash
php artisan test
```

---

## Task 9: Remove QR Code Feature from Admission Slips (A2)

> **What & Why:** QR code generation is removed from admission slips — applicant lookup for scoring/attendance will use name search and reference number search instead. `QrCodeService` is called from three services: `AdmissionSlipService`, `AdmissionSlipTemplateService`, and `ResultSheetTemplateService`. We clean all three, then delete the service and its test.
>
> ⚠️ Check `ResultSheetTemplateService` carefully — it uses `qr_code` as a template placeholder. We make it output an empty string instead of a QR image.

**Files:**
- Modify: `app/Services/AdmissionSlipService.php`
- Modify: `app/Services/AdmissionSlipTemplateService.php`
- Modify: `app/Services/ResultSheetTemplateService.php`
- Modify: `resources/views/pdf/admission-slip.blade.php`
- Delete: `app/Services/QrCodeService.php`
- Delete: `tests/Unit/Services/QrCodeServiceTest.php`

- [ ] **Step 1: Update `app/Services/AdmissionSlipService.php`**

Remove all QR-related code. Replace the full file:

```php
<?php

namespace App\Services;

use App\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class AdmissionSlipService
{
    /**
     * Generate PDF admission slip.
     */
    public function generatePdf(Application $application): \Barryvdh\DomPDF\PDF
    {
        $application->load(['coursePreference1', 'coursePreference2', 'coursePreference3']);

        $fullName = implode(' ', array_filter([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
            $application->suffix,
        ]));

        $courseLabels = [
            $application->coursePreference1?->name ?? '—',
            $application->coursePreference2?->name ?? '—',
            $application->coursePreference3?->name ?? '—',
        ];

        return Pdf::loadView('pdf.admission-slip', [
            'referenceNumber' => $application->reference_number,
            'fullName'        => $fullName,
            'birthdate'       => $application->birthdate->format('F j, Y'),
            'sex'             => ucfirst($application->sex),
            'courseLabels'    => $courseLabels,
        ]);
    }

    /**
     * Render admission slip as HTML for browser print.
     */
    public function renderHtml(Application $application): string
    {
        $application->load(['coursePreference1', 'coursePreference2', 'coursePreference3']);

        $fullName = implode(' ', array_filter([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
            $application->suffix,
        ]));

        $courseLabels = [
            $application->coursePreference1?->name ?? '—',
            $application->coursePreference2?->name ?? '—',
            $application->coursePreference3?->name ?? '—',
        ];

        return View::make('pdf.admission-slip', [
            'referenceNumber' => $application->reference_number,
            'fullName'        => $fullName,
            'birthdate'       => $application->birthdate->format('F j, Y'),
            'sex'             => ucfirst($application->sex),
            'courseLabels'    => $courseLabels,
        ])->render();
    }
}
```

- [ ] **Step 2: Update `resources/views/pdf/admission-slip.blade.php`**

Remove the QR-related styles and the QR section. Replace the section near the bottom:

```blade
<!-- BEFORE (at bottom of body, ~line 56): -->
    <div class="section" style="margin-top: 32px;">
        <p style="color: #6b7280; font-size: 10px;">Exam schedule and room assignment will be provided after publication.</p>
        @if(isset($qrCodeDataUri))
        <img src="{{ $qrCodeDataUri }}" alt="QR Code" width="80" height="80" style="margin-top: 12px; display: inline-block;" />
        @else
        <div class="qr-placeholder" style="margin-top: 12px;">QR Code</div>
        @endif
    </div>

<!-- AFTER: -->
    <div class="section" style="margin-top: 32px;">
        <p style="color: #6b7280; font-size: 10px;">Exam schedule and room assignment will be provided after publication.</p>
    </div>
```

Also remove the `.qr-placeholder` CSS rule from the `<style>` block:

```blade
<!-- DELETE this line from <style>: -->
.qr-placeholder { width: 80px; height: 80px; border: 2px dashed #9ca3af; display: inline-block; text-align: center; line-height: 80px; color: #9ca3af; font-size: 10px; }
```

- [ ] **Step 3: Update `app/Services/AdmissionSlipTemplateService.php`**

The service has a `qrPlaceholder()` method that calls `QrCodeService`. Replace it with a static placeholder. Find this method (around line 185):

```php
protected function qrPlaceholder(string $reference): string
{
    if ($reference === '' || $reference === '—') {
        return sprintf(
            '<div class="qr-placeholder" style="width:80px;height:80px;border:2px dashed #9ca3af;display:inline-block;text-align:center;line-height:80px;color:#9ca3af;font-size:10px;">QR Code<br />%s</div>',
            htmlspecialchars($reference ?: '—')
        );
    }

    $qrService = app(QrCodeService::class);
    $dataUri = $qrService->admissionSlipDataUri($reference);

    return sprintf(
        '<img src="%s" alt="QR Code" width="80" height="80" style="display:inline-block;vertical-align:middle;" />',
        htmlspecialchars($dataUri)
    );
}
```

Replace it with:

```php
protected function qrPlaceholder(string $reference): string
{
    return sprintf(
        '<div class="qr-placeholder" style="width:80px;height:80px;border:2px dashed #9ca3af;display:inline-block;text-align:center;line-height:80px;color:#9ca3af;font-size:10px;">QR Code</div>'
    );
}
```

- [ ] **Step 4: Update `app/Services/ResultSheetTemplateService.php`**

The service has a `qrPlaceholder()` method that calls `QrCodeService`. Find this method (around line 247):

```php
protected function qrPlaceholder(string $reference): string
{
    if ($reference === '' || $reference === '—') {
        return sprintf(
            '<div class="w-20 h-20 border-2 border-dashed border-muted-foreground/50 rounded flex items-center justify-center text-xs text-muted-foreground text-center px-1">QR Code<br />%s</div>',
            htmlspecialchars($reference ?: '—')
        );
    }

    $qrService = app(QrCodeService::class);
    $dataUri = $qrService->consultationDataUri($reference);

    return sprintf(
        '<img src="%s" alt="QR Code" width="80" height="80" class="inline-block align-middle rounded" />',
        htmlspecialchars($dataUri)
    );
}
```

Replace it with:

```php
protected function qrPlaceholder(string $reference): string
{
    return '<div class="w-20 h-20 border-2 border-dashed border-muted-foreground/50 rounded flex items-center justify-center text-xs text-muted-foreground text-center px-1">QR Code</div>';
}
```

- [ ] **Step 5: Delete `app/Services/QrCodeService.php`**

```bash
rm app/Services/QrCodeService.php
```

- [ ] **Step 6: Delete `tests/Unit/Services/QrCodeServiceTest.php`**

```bash
rm tests/Unit/Services/QrCodeServiceTest.php
```

- [ ] **Step 7: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass. No references to `QrCodeService` should remain.

If you see "Class QrCodeService not found" errors, search for any remaining references:

```bash
php artisan tinker --execute="grep -r 'QrCodeService' app/ --include='*.php'"
```

Fix any remaining references.

- [ ] **Step 8: Verify in the browser**

1. Go to an accepted application (e.g., `/applications/1`)
2. Click "Print admission slip" or navigate to `/applications/1/admission-slip`
3. Confirm the PDF/HTML renders without a QR code block

- [ ] **Step 9: Commit**

```bash
git rm app/Services/QrCodeService.php \
       tests/Unit/Services/QrCodeServiceTest.php
git add app/Services/AdmissionSlipService.php \
        app/Services/AdmissionSlipTemplateService.php \
        app/Services/ResultSheetTemplateService.php \
        resources/views/pdf/admission-slip.blade.php
git commit -m "feat: remove QR code from admission slips and delete QrCodeService (A2)"
```

---

## ✅ ALL PHASES DONE

Run the final full test suite:

```bash
php artisan test
```

Expected: all tests pass, zero failures, zero errors.

---

## Summary of All Amendments

| Amendment | Task | Status |
|---|---|---|
| A1 — Attendance requires `in_progress` | Already correct in code | ✅ Pre-existing |
| A2 — Remove QR / Admission Slip QR | Task 9 | |
| A3 — Room activate toggle | Task 4 | |
| A4 — Course activate toggle | Task 5 | |
| A5 — Drop `facilities` from rooms | Task 6 | |
| A6 — Drop `score_cutoff` from courses | Task 7 | |
| A7 — Drop `quota` from courses | Task 7 | |
| A8 — Drop `department` from courses | Task 8 | |
| A9 — `start_time` format validation | Task 1 | |
| A10 — Past-date prevention on session create | Task 1 | |
| A11 — Schedule ExpireSeasonApplications | Task 2 | |
| A12 — Paginate Users | Already done | ✅ Pre-existing |
| A13 — Rename "Season" → "Academic Year" in UI | Task 3 | |
| Breadcrumbs on Applications/Show + PrintSlips | Already done | ✅ Pre-existing |
