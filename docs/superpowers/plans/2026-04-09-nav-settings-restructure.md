# Nav & Settings Restructure — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement R1–R8: restructure navigation hierarchy, remove Consultation, add Release page, add notify_on_publish + release_mode settings, and create an AI Companion admin hub.

**Architecture:** All feature flags flow through `HandleInertiaRequests` shared props so every page has access without per-controller changes. New settings are new rows in the existing `system_settings` key-value table — no schema changes needed. Consultation code is fully deleted (not feature-flagged) before the replacement Release code is added.

**Tech Stack:** Laravel 12, Inertia.js, Svelte 5 (`$state`, `$derived`, `$props`), shadcn-svelte UI components, Laravel Notifications with `ShouldQueue`.

**Spec:** `docs/superpowers/specs/2026-04-09-nav-settings-restructure-design.md`

---

## Task 1: SystemSetting — add releaseMode() and notifyOnPublish(), remove consultationEnabled()

**Files:**
- Modify: `app/Models/SystemSetting.php`

> **Context for junior devs:** `SystemSetting` is a key-value store where each row is one setting. The `get()` method has a hardcoded list of keys that should be cast to boolean — you must add `notify_on_publish` to that list or it will always return a string `"1"` instead of `true`.

- [ ] **Step 1: Open the file and read the current boolean-cast list**

  Open `app/Models/SystemSetting.php`. Find this line (around line 27):
  ```php
  if (in_array($key, ['ai_exam_companion_enabled', 'online_release_enabled', 'consultation_enabled'], true)) {
  ```
  You are going to add `notify_on_publish` to this list and remove `consultation_enabled`.

- [ ] **Step 2: Update the boolean-cast list**

  Replace:
  ```php
  if (in_array($key, ['ai_exam_companion_enabled', 'online_release_enabled', 'consultation_enabled'], true)) {
  ```
  With:
  ```php
  if (in_array($key, ['ai_exam_companion_enabled', 'online_release_enabled', 'notify_on_publish'], true)) {
  ```

- [ ] **Step 3: Remove consultationEnabled() and add releaseMode() + notifyOnPublish()**

  Delete the `consultationEnabled()` method entirely:
  ```php
  // DELETE THIS WHOLE METHOD:
  public static function consultationEnabled(): bool
  {
      return (bool) self::get('consultation_enabled', true);
  }
  ```

  Add these two new methods after `aiCompanionEnabled()`:
  ```php
  /**
   * Returns the result release mode: 'online', 'f2f', or 'both'. Default: 'online'.
   */
  public static function releaseMode(): string
  {
      return (string) self::get('release_mode', 'online');
  }

  /**
   * Whether to email applicants when their exam session is published. Default: false.
   */
  public static function notifyOnPublish(): bool
  {
      return (bool) self::get('notify_on_publish', false);
  }
  ```

- [ ] **Step 4: Verify the final state of the file**

  Run this to check no references to `consultationEnabled` remain in the model:
  ```bash
  grep -n "consultationEnabled\|consultation_enabled" app/Models/SystemSetting.php
  ```
  Expected: no output (zero matches).

- [ ] **Step 5: Commit**

  ```bash
  git add app/Models/SystemSetting.php
  git commit -m "feat: add releaseMode and notifyOnPublish to SystemSetting, remove consultationEnabled"
  ```

---

## Task 2: HandleInertiaRequests — swap shared props

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

> **Context:** Every Inertia page automatically receives whatever is returned from `share()`. Adding `ai_exam_companion_enabled` and `release_mode` here means the sidebar and any component can use them without any per-controller change. We remove `consultation_enabled` because the Consultation feature is being deleted entirely.

- [ ] **Step 1: Replace the shared props**

  In `app/Http/Middleware/HandleInertiaRequests.php`, find the `return array_merge(...)` block and replace it:

  **Before:**
  ```php
  return array_merge(parent::share($request), [
      'auth' => [
          'user' => $authUser,
      ],
      'csrf_token' => $request->session()->token(),
      'consultation_enabled' => SystemSetting::consultationEnabled(),
      'pageTitle' => $this->defaultPageTitle($request),
  ]);
  ```

  **After:**
  ```php
  return array_merge(parent::share($request), [
      'auth' => [
          'user' => $authUser,
      ],
      'csrf_token' => $request->session()->token(),
      'ai_exam_companion_enabled' => SystemSetting::aiCompanionEnabled(),
      'release_mode'              => SystemSetting::releaseMode(),
      'pageTitle' => $this->defaultPageTitle($request),
  ]);
  ```

- [ ] **Step 2: Also update the `defaultPageTitle` titles array**

  Find the `$titles` array inside `defaultPageTitle()`. Add the new AI Companion route and remove the knowledge-documents entry:

  **Before:**
  ```php
  'admin.knowledge-documents.index' => 'Knowledge docs',
  ```
  **After:**
  ```php
  'admin.ai-companion.index' => 'AI Companion',
  ```

- [ ] **Step 3: Verify**

  ```bash
  grep -n "consultation_enabled\|consultationEnabled" app/Http/Middleware/HandleInertiaRequests.php
  ```
  Expected: no output.

- [ ] **Step 4: Commit**

  ```bash
  git add app/Http/Middleware/HandleInertiaRequests.php
  git commit -m "feat: swap shared props — add ai_exam_companion_enabled + release_mode, remove consultation_enabled"
  ```

---

## Task 3: R1 — Rooms breadcrumbs + TestScheduling "Manage Rooms" rename

**Files:**
- Modify: `resources/js/Pages/Admin/Rooms/Index.svelte` (line 70)
- Modify: `resources/js/Pages/Admin/Rooms/Create.svelte` (line 22)
- Modify: `resources/js/Pages/Admin/Rooms/Edit.svelte` (line 8)
- Modify: `resources/js/Pages/Admin/TestScheduling/Index.svelte` (line 146–149)

> **Context:** Rooms now "belong to" Exam Scheduling in the UI. Every breadcrumb trail starts with "Exam Scheduling → Rooms → ...". The TestScheduling index already has an "Add Room" quick link — we rename it to "Manage Rooms" to match the new mental model.

- [ ] **Step 1: Update Rooms/Index.svelte breadcrumbs**

  File: `resources/js/Pages/Admin/Rooms/Index.svelte`

  Find:
  ```js
  const breadcrumbs = [{ label: 'Rooms' }];
  ```
  Replace with:
  ```js
  const breadcrumbs = [{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'Rooms' }];
  ```

- [ ] **Step 2: Update Rooms/Create.svelte breadcrumbs**

  File: `resources/js/Pages/Admin/Rooms/Create.svelte`

  Find:
  ```js
  const breadcrumbs = [{ label: 'Rooms', href: '/admin/rooms' }, { label: 'Add Room' }];
  ```
  Replace with:
  ```js
  const breadcrumbs = [{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'Rooms', href: '/admin/rooms' }, { label: 'Add Room' }];
  ```

- [ ] **Step 3: Update Rooms/Edit.svelte breadcrumbs**

  File: `resources/js/Pages/Admin/Rooms/Edit.svelte`

  Find:
  ```js
  const breadcrumbs = [{ label: 'Rooms', href: '/admin/rooms' }, { label: 'Edit' }];
  ```
  Replace with:
  ```js
  const breadcrumbs = [{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'Rooms', href: '/admin/rooms' }, { label: 'Edit' }];
  ```

- [ ] **Step 4: Rename "Add Room" → "Manage Rooms" in TestScheduling/Index.svelte**

  File: `resources/js/Pages/Admin/TestScheduling/Index.svelte`

  Find (around line 146–149):
  ```svelte
  <Link href="/admin/rooms">
  ```
  Look at the text label near it. Find the text `Add Room` and replace it with `Manage Rooms`. The surrounding `<Link>` and `<Button>` tags stay the same.

- [ ] **Step 5: Manual verify**

  Start your dev server if it isn't running:
  ```bash
  php artisan serve
  npm run dev
  ```
  Visit `/admin/rooms` — the breadcrumb in the header should read **Exam Scheduling › Rooms**.
  Visit `/admin/test-scheduling` — the button should read **Manage Rooms**.

- [ ] **Step 6: Commit**

  ```bash
  git add resources/js/Pages/Admin/Rooms/Index.svelte \
          resources/js/Pages/Admin/Rooms/Create.svelte \
          resources/js/Pages/Admin/Rooms/Edit.svelte \
          resources/js/Pages/Admin/TestScheduling/Index.svelte
  git commit -m "feat(R1): nest Rooms under Exam Scheduling in breadcrumbs, rename button"
  ```

---

## Task 4: R2 — Courses breadcrumbs + Seasons "Manage Courses" rename

**Files:**
- Modify: `resources/js/Pages/Admin/Courses/Index.svelte` (line 27)
- Modify: `resources/js/Pages/Admin/Courses/Create.svelte` (line 16)
- Modify: `resources/js/Pages/Admin/Courses/Edit.svelte` (line 32)
- Modify: `resources/js/Pages/Admin/Seasons/Index.svelte` (line 27–30)

> **Context:** Same pattern as Task 3 but for Courses → Academic Years.

- [ ] **Step 1: Update Courses/Index.svelte breadcrumbs**

  Find:
  ```js
  const breadcrumbs = [{ label: 'Courses' }];
  ```
  Replace with:
  ```js
  const breadcrumbs = [{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Courses' }];
  ```

- [ ] **Step 2: Update Courses/Create.svelte breadcrumbs**

  Find:
  ```js
  const breadcrumbs = [{ label: 'Courses', href: '/admin/courses' }, { label: 'Add Course' }];
  ```
  Replace with:
  ```js
  const breadcrumbs = [{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Courses', href: '/admin/courses' }, { label: 'Add Course' }];
  ```

- [ ] **Step 3: Update Courses/Edit.svelte breadcrumbs**

  Find:
  ```js
  const breadcrumbs = [{ label: 'Courses', href: '/admin/courses' }, { label: 'Edit Course' }];
  ```
  Replace with:
  ```js
  const breadcrumbs = [{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Courses', href: '/admin/courses' }, { label: 'Edit Course' }];
  ```

- [ ] **Step 4: Rename "Add Course" → "Manage Courses" in Seasons/Index.svelte**

  File: `resources/js/Pages/Admin/Seasons/Index.svelte`

  Find (around line 30):
  ```svelte
  Add Course
  ```
  Replace with:
  ```svelte
  Manage Courses
  ```
  The surrounding `<Link href="/admin/courses">` and `<Button>` stay unchanged.

- [ ] **Step 5: Manual verify**

  Visit `/admin/courses` — breadcrumb should read **Academic Years › Courses**.
  Visit `/admin/seasons` — button should read **Manage Courses**.

- [ ] **Step 6: Commit**

  ```bash
  git add resources/js/Pages/Admin/Courses/Index.svelte \
          resources/js/Pages/Admin/Courses/Create.svelte \
          resources/js/Pages/Admin/Courses/Edit.svelte \
          resources/js/Pages/Admin/Seasons/Index.svelte
  git commit -m "feat(R2): nest Courses under Academic Years in breadcrumbs, rename button"
  ```

---

## Task 5: R4 — Delete all Consultation code

**Files to delete:**
- `resources/js/Pages/Consultation/Dashboard.svelte`
- `resources/js/Pages/Consultation/ScheduleDay.svelte`
- `resources/js/Pages/Consultation/ApplicantView.svelte`
- `resources/js/Pages/Consultation/ConsultationDay.svelte`
- `app/Http/Controllers/Consultation/ConsultationController.php`
- `app/Http/Controllers/Consultation/ConsultationApplicantController.php`
- `app/Http/Controllers/Consultation/ConsultationDayController.php`
- `app/Http/Controllers/Consultation/ConsultationScheduleController.php`

**Files to modify:** `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php` (already done in Task 2), `bootstrap/app.php` (or `Kernel.php`)

> **Context:** We do the deletion BEFORE adding the replacement Release page so the app is in a clean state. The consultation middleware (`consultation.enabled`) protects routes that no longer exist — remove it from the route group and from `bootstrap/app.php`.

- [ ] **Step 1: Delete the Consultation Svelte pages**

  ```bash
  rm resources/js/Pages/Consultation/Dashboard.svelte
  rm resources/js/Pages/Consultation/ScheduleDay.svelte
  rm resources/js/Pages/Consultation/ApplicantView.svelte
  rm resources/js/Pages/Consultation/ConsultationDay.svelte
  rmdir resources/js/Pages/Consultation
  ```

- [ ] **Step 2: Delete the Consultation PHP controllers**

  ```bash
  rm app/Http/Controllers/Consultation/ConsultationController.php
  rm app/Http/Controllers/Consultation/ConsultationApplicantController.php
  rm app/Http/Controllers/Consultation/ConsultationDayController.php
  rm app/Http/Controllers/Consultation/ConsultationScheduleController.php
  rmdir app/Http/Controllers/Consultation
  ```

- [ ] **Step 3: Remove the consultation route group from web.php**

  Open `routes/web.php`. Delete lines 164–173 (the entire consultation route group):
  ```php
  // DELETE these lines:
  // Consultation (link always visible for role; access enforced by consultation.enabled)
  Route::middleware(['role:super_admin,test_administrator', 'consultation.enabled'])->prefix('consultation')->name('consultation.')->group(function () {
      Route::get('/', [ConsultationController::class, 'index'])->name('index');
      Route::get('/schedule', [ConsultationScheduleController::class, 'index'])->name('schedule.index');
      Route::post('/schedule', [ConsultationScheduleController::class, 'store'])->name('schedule.store');
      Route::get('/day', [ConsultationDayController::class, 'index'])->name('day.index');
      Route::get('/applicants/{applicant}', [ConsultationApplicantController::class, 'show'])->name('applicants.show');
      Route::post('/applicants/bulk-release', [ConsultationApplicantController::class, 'releaseBulk'])->name('applicants.bulk-release');
      Route::post('/applicants/{applicant}/release', [ConsultationApplicantController::class, 'release'])->name('applicants.release');
  });
  ```

  Also remove the `use` imports for those controllers at the top of `web.php`. Search for:
  ```bash
  grep -n "Consultation\\" routes/web.php
  ```
  Delete each line that imports a Consultation controller.

- [ ] **Step 4: Find and remove the consultation middleware registration**

  ```bash
  grep -rn "consultation.enabled\|EnsureConsultation" bootstrap/ app/Http/Kernel.php app/Http/ 2>/dev/null
  ```

  The middleware alias `consultation.enabled` is registered somewhere — likely in `bootstrap/app.php` (Laravel 12 style) or `app/Http/Kernel.php`. Find the line and delete it. Example of what to look for:

  In `bootstrap/app.php`:
  ```php
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->alias([
          'consultation.enabled' => \App\Http\Middleware\EnsureConsultationEnabled::class, // DELETE THIS LINE
      ]);
  })
  ```

  Also delete the middleware file itself:
  ```bash
  # Find and delete it (name may vary slightly):
  find app/Http/Middleware -name "*Consultation*" -o -name "*consultation*"
  # Then: rm app/Http/Middleware/EnsureConsultationEnabled.php
  ```

- [ ] **Step 5: Verify no broken references remain**

  ```bash
  grep -rn "ConsultationController\|ConsultationApplicant\|ConsultationDay\|ConsultationSchedule\|consultation\.enabled\|EnsureConsultation" app/ routes/ resources/ --include="*.php" --include="*.svelte"
  ```
  Expected: zero matches (any remaining `ConsultationSummary` references are fine — the model and table are kept).

- [ ] **Step 6: Check the app still boots**

  ```bash
  php artisan route:list | grep consultation
  ```
  Expected: no output. The app should not error on this command.

- [ ] **Step 7: Commit**

  ```bash
  git add -A
  git commit -m "feat(R4): delete all Consultation controllers, pages, routes, and middleware"
  ```

---

## Task 6: R4 — Create ReleaseController and routes

**Files:**
- Create: `app/Http/Controllers/ReleaseController.php`
- Modify: `routes/web.php`

> **Context:** The `ConsultationSummary` model (table: `consultation_summaries`) is the existing release tracking record — we reuse it. It has `status` ('pending'/'draft'/'released'), `released_at`, `released_by`, and a `STATUS_RELEASED` constant. The controller is straightforward: index (list), release one, release many.

- [ ] **Step 1: Create the controller**

  Create `app/Http/Controllers/ReleaseController.php`:

  ```php
  <?php

  namespace App\Http\Controllers;

  use App\Models\ConsultationSummary;
  use App\Models\SystemSetting;
  use App\Notifications\ResultReleased;
  use Illuminate\Http\RedirectResponse;
  use Illuminate\Http\Request;
  use Inertia\Inertia;
  use Inertia\Response;

  class ReleaseController extends Controller
  {
      public function index(): Response
      {
          $summaries = ConsultationSummary::with(['applicant', 'recommendedCourse'])
              ->whereIn('status', ['draft', 'released'])
              ->orderBy('updated_at', 'desc')
              ->paginate(50);

          return Inertia::render('Release/Index', [
              'summaries'    => $summaries,
              'release_mode' => SystemSetting::releaseMode(),
          ]);
      }

      public function release(ConsultationSummary $summary): RedirectResponse
      {
          if ($summary->status === ConsultationSummary::STATUS_RELEASED) {
              return back()->with('error', 'Already released.');
          }

          $summary->update([
              'status'      => ConsultationSummary::STATUS_RELEASED,
              'released_at' => now(),
              'released_by' => auth()->id(),
          ]);

          if (SystemSetting::releaseMode() !== 'f2f') {
              $summary->applicant->notify(new ResultReleased($summary));
          }

          return back()->with('success', 'Result released.');
      }

      public function releaseBulk(Request $request): RedirectResponse
      {
          $ids = $request->validate([
              'ids'   => 'required|array',
              'ids.*' => 'integer|exists:consultation_summaries,id',
          ])['ids'];

          $summaries = ConsultationSummary::whereIn('id', $ids)
              ->where('status', '!=', ConsultationSummary::STATUS_RELEASED)
              ->get();

          foreach ($summaries as $summary) {
              $summary->update([
                  'status'      => ConsultationSummary::STATUS_RELEASED,
                  'released_at' => now(),
                  'released_by' => auth()->id(),
              ]);

              if (SystemSetting::releaseMode() !== 'f2f') {
                  $summary->applicant->notify(new ResultReleased($summary));
              }
          }

          return back()->with('success', count($summaries) . ' result(s) released.');
      }
  }
  ```

- [ ] **Step 2: Add routes to web.php**

  Open `routes/web.php`. Add these routes inside the existing `auth` middleware group (after the grading routes, before the closing `});` of the outer auth group):

  ```php
  // Release Management
  Route::middleware('role:super_admin,test_administrator')
      ->prefix('release')
      ->name('release.')
      ->group(function () {
          Route::get('/', [ReleaseController::class, 'index'])->name('index');
          Route::post('/summaries/{summary}/release', [ReleaseController::class, 'release'])->name('summaries.release');
          Route::post('/summaries/bulk-release', [ReleaseController::class, 'releaseBulk'])->name('summaries.bulk-release');
      });
  ```

  Also add the `use` import at the top of `web.php`:
  ```php
  use App\Http\Controllers\ReleaseController;
  ```

- [ ] **Step 3: Verify routes are registered**

  ```bash
  php artisan route:list | grep release
  ```
  Expected output:
  ```
  POST   release/summaries/bulk-release     release.summaries.bulk-release
  GET    release                            release.index
  POST   release/summaries/{summary}/release release.summaries.release
  ```

- [ ] **Step 4: Commit**

  ```bash
  git add app/Http/Controllers/ReleaseController.php routes/web.php
  git commit -m "feat(R4): add ReleaseController and release routes"
  ```

---

## Task 7: R4 — Create ResultReleased notification

**Files:**
- Create: `app/Notifications/ResultReleased.php`

> **Context:** Laravel Notifications with `ShouldQueue` run in the background (queue worker must be running in production). In development, set `QUEUE_CONNECTION=sync` in `.env` to process notifications immediately without a worker. The `$notifiable` is the `Applicant` model — the notification is dispatched via `$summary->applicant->notify(new ResultReleased($summary))`.

- [ ] **Step 1: Create the notification class**

  Create `app/Notifications/ResultReleased.php`:

  ```php
  <?php

  namespace App\Notifications;

  use App\Models\ConsultationSummary;
  use Illuminate\Bus\Queueable;
  use Illuminate\Contracts\Queue\ShouldQueue;
  use Illuminate\Notifications\Messages\MailMessage;
  use Illuminate\Notifications\Notification;

  class ResultReleased extends Notification implements ShouldQueue
  {
      use Queueable;

      public function __construct(public readonly ConsultationSummary $summary) {}

      public function via(object $notifiable): array
      {
          return ['mail', 'database'];
      }

      public function toMail(object $notifiable): MailMessage
      {
          return (new MailMessage)
              ->subject('Your exam results are now available')
              ->greeting('Hello, ' . ($notifiable->name ?? 'Applicant') . '!')
              ->line('Your exam results have been released and are now available.')
              ->action('View in Portal', url('/portal'))
              ->line('If you have questions, please contact the guidance office.');
      }

      public function toArray(object $notifiable): array
      {
          return [
              'type'       => 'result_released',
              'summary_id' => $this->summary->id,
              'message'    => 'Your exam results are now available.',
          ];
      }
  }
  ```

- [ ] **Step 2: Verify it parses without errors**

  ```bash
  php artisan about
  ```
  This bootstraps the app — if the class has any syntax error you'll see it here. Expected: no errors.

- [ ] **Step 3: Commit**

  ```bash
  git add app/Notifications/ResultReleased.php
  git commit -m "feat(R4): add ResultReleased notification (mail + database)"
  ```

---

## Task 8: R4 — Create Release/Index.svelte page

**Files:**
- Create: `resources/js/Pages/Release/Index.svelte`

> **Context:** This page replaces the old Consultation dashboard. It shows all applicants whose summary is in 'draft' or 'released' state, allows bulk selection + release, and adapts its UI based on `release_mode` (passed as a prop by `ReleaseController@index`). Email notification is sent automatically by the controller — the checkbox is informational only.

- [ ] **Step 1: Create the directory and file**

  ```bash
  mkdir -p resources/js/Pages/Release
  ```

  Create `resources/js/Pages/Release/Index.svelte`:

  ```svelte
  <script>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
    import { router, usePage } from '@inertiajs/svelte';
    import { Button } from '@/Components/ui/button';
    import { Badge } from '@/Components/ui/badge';
    import * as Table from '@/Components/ui/table';

    let { summaries, release_mode = 'online' } = $props();

    const page = usePage();
    const flash = $derived($page.props.flash ?? {});
    const breadcrumbs = [{ label: 'Release Management' }];

    // Track selected summary IDs for bulk release
    let selectedIds = $state([]);

    const allSelected = $derived(
      summaries.data.length > 0 &&
      summaries.data
        .filter((s) => s.status !== 'released')
        .every((s) => selectedIds.includes(s.id))
    );

    function toggleAll() {
      const unreleasedIds = summaries.data
        .filter((s) => s.status !== 'released')
        .map((s) => s.id);
      if (allSelected) {
        selectedIds = [];
      } else {
        selectedIds = unreleasedIds;
      }
    }

    function toggleOne(id) {
      if (selectedIds.includes(id)) {
        selectedIds = selectedIds.filter((i) => i !== id);
      } else {
        selectedIds = [...selectedIds, id];
      }
    }

    function releaseOne(summaryId) {
      router.post(`/release/summaries/${summaryId}/release`, {}, { preserveScroll: true });
    }

    function releaseBulk() {
      if (selectedIds.length === 0) return;
      router.post('/release/summaries/bulk-release', { ids: selectedIds }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds = []; },
      });
    }

    function statusVariant(status) {
      if (status === 'released') return 'success';
      if (status === 'draft') return 'secondary';
      return 'muted';
    }
  </script>

  <AuthenticatedLayout {breadcrumbs}>
    <div class="space-y-6 min-w-0">

      {#if flash.success}
        <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">{flash.success}</div>
      {/if}
      {#if flash.error}
        <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{flash.error}</div>
      {/if}

      <!-- Mode banner -->
      {#if release_mode === 'f2f'}
        <div class="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
          <strong>F2F mode:</strong> Results will be provided to applicants in person. Email delivery is disabled.
        </div>
      {:else if release_mode === 'online'}
        <div class="rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-sm">
          <strong>Online mode:</strong> Releasing a result will send applicants a portal notification and email.
        </div>
      {:else}
        <div class="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
          <strong>Online + F2F mode:</strong> Applicants will receive a portal notification and email when released.
        </div>
      {/if}

      <!-- Bulk action bar -->
      <div class="flex items-center gap-3">
        <Button
          onclick={releaseBulk}
          disabled={selectedIds.length === 0}
          class="min-h-[44px]"
        >
          Release Selected ({selectedIds.length})
        </Button>
        {#if release_mode !== 'f2f' && selectedIds.length > 0}
          <span class="text-xs text-muted-foreground">
            Email notifications will be sent to selected applicants.
          </span>
        {/if}
      </div>

      <!-- Table -->
      <div class="glass-panel rounded-2xl overflow-hidden min-w-0 p-0">
        <div class="w-full overflow-x-auto">
          <Table.Root class="w-full min-w-[640px]">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="w-10 px-4 py-3">
                  <input
                    type="checkbox"
                    checked={allSelected}
                    onchange={toggleAll}
                    aria-label="Select all unreleased"
                    class="h-4 w-4 cursor-pointer"
                  />
                </Table.Head>
                <Table.Head class="px-4 py-3">Applicant</Table.Head>
                <Table.Head class="px-4 py-3">Recommended Course</Table.Head>
                <Table.Head class="px-4 py-3">Status</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each summaries.data as summary (summary.id)}
                <Table.Row class={summary.status === 'released' ? 'opacity-60' : ''}>
                  <Table.Cell class="px-4 py-3">
                    {#if summary.status !== 'released'}
                      <input
                        type="checkbox"
                        checked={selectedIds.includes(summary.id)}
                        onchange={() => toggleOne(summary.id)}
                        aria-label="Select {summary.applicant?.name ?? summary.id}"
                        class="h-4 w-4 cursor-pointer"
                      />
                    {/if}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <p class="font-medium">{summary.applicant?.name ?? '—'}</p>
                    <p class="text-xs text-muted-foreground">{summary.applicant?.email ?? ''}</p>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    {summary.recommended_course?.name ?? '—'}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Badge variant={statusVariant(summary.status)}>
                      {summary.status}
                    </Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">
                    {#if summary.status !== 'released'}
                      <Button
                        size="sm"
                        variant="outline"
                        onclick={() => releaseOne(summary.id)}
                        class="min-h-[36px]"
                      >
                        Release
                      </Button>
                    {:else}
                      <span class="text-xs text-muted-foreground">Released</span>
                    {/if}
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan={5} class="px-4 py-12 text-center text-muted-foreground">
                    No results ready for release yet.
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>

        {#if summaries.last_page > 1}
          <div class="flex items-center justify-between border-t border-border px-4 py-2">
            <p class="text-sm text-muted-foreground">
              Page {summaries.current_page} of {summaries.last_page}
            </p>
          </div>
        {/if}
      </div>
    </div>
  </AuthenticatedLayout>
  ```

- [ ] **Step 2: Manual verify**

  Visit `/release` in the browser. You should see the Release Management page with the mode banner.

  If you get a "Page not found" error — double-check Task 6 routes are saved.
  If you get a Svelte compile error — check the browser console and fix the syntax.

- [ ] **Step 3: Commit**

  ```bash
  git add resources/js/Pages/Release/Index.svelte
  git commit -m "feat(R4): add Release/Index.svelte page"
  ```

---

## Task 9: R4 — Update sidebar: replace Consultation with Release

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.svelte`

> **Context:** The sidebar nav currently has `{ href: '/consultation', label: 'Release & Consultation', featureFlag: 'consultation_enabled', ... }`. We remove that and add a plain `{ href: '/release', label: 'Release', ... }` with no feature flag (Release is always available).

- [ ] **Step 1: Remove the Consultation nav item and add Release**

  Open `resources/js/Layouts/AuthenticatedLayout.svelte`.

  Find in `navSections` (inside the Guidance Office section):
  ```js
  { href: '/consultation', label: 'Release & Consultation', icon: MessageSquare, roles: ['super_admin', 'test_administrator'], featureFlag: 'consultation_enabled' },
  ```

  Replace it with:
  ```js
  { href: '/release', label: 'Release', icon: SendHorizonal, roles: ['super_admin', 'test_administrator'] },
  ```

- [ ] **Step 2: Update the icon imports**

  Find the existing import at the top of the script block:
  ```js
  import { ChevronDown, ChevronRight, Menu, LayoutDashboard, Users, FileText, Calendar, GraduationCap, BookOpen, Settings, MessageSquare, ScrollText, FileStack, Activity, CalendarRange, Layers, ShieldCheck, Sun, Moon, Bell, Search } from 'lucide-svelte';
  ```

  - Remove `MessageSquare` (no longer used after removing Consultation nav item — double check it's not used elsewhere in the file first with Ctrl+F)
  - Add `SendHorizonal` (note: this is the correct lucide-svelte spelling — `Horizonal` not `Horizontal`)

  Updated import:
  ```js
  import { ChevronDown, ChevronRight, Menu, LayoutDashboard, Users, FileText, Calendar, GraduationCap, BookOpen, Settings, ScrollText, FileStack, Activity, CalendarRange, Layers, ShieldCheck, Sun, Moon, Bell, Search, SendHorizonal } from 'lucide-svelte';
  ```

- [ ] **Step 3: Manual verify**

  Reload the admin panel. Under **Guidance Office** in the sidebar, you should see **Release** instead of **Release & Consultation**.

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Layouts/AuthenticatedLayout.svelte
  git commit -m "feat(R4): replace Consultation sidebar link with Release"
  ```

---

## Task 10: R8 — Create AiCompanionAdminController + routes

**Files:**
- Create: `app/Http/Controllers/Admin/AiCompanionAdminController.php`
- Modify: `routes/web.php`

> **Context:** This controller serves the AI Companion admin hub — a tabbed page containing knowledge documents list AND the persona configuration textarea. The persona was previously on the Settings page; it moves here. We also redirect the old `/admin/knowledge-documents` index URL to the new hub so any bookmarked links still work.

- [ ] **Step 1: Create the controller**

  Create `app/Http/Controllers/Admin/AiCompanionAdminController.php`:

  ```php
  <?php

  namespace App\Http\Controllers\Admin;

  use App\Http\Controllers\Controller;
  use App\Http\Requests\UpdatePersonaRequest;
  use App\Models\KnowledgeDocument;
  use App\Models\SystemSetting;
  use Illuminate\Http\RedirectResponse;
  use Inertia\Inertia;
  use Inertia\Response;

  class AiCompanionAdminController extends Controller
  {
      public function index(): Response
      {
          if (! SystemSetting::aiCompanionEnabled()) {
              abort(403, 'AI Companion is disabled.');
          }

          $this->authorize('viewAny', KnowledgeDocument::class);

          return Inertia::render('Admin/AiCompanion/Index', [
              'documents'            => KnowledgeDocument::orderBy('created_at', 'desc')->get(),
              'ai_companion_persona' => SystemSetting::personaPrompt(),
          ]);
      }

      public function updatePersona(UpdatePersonaRequest $request): RedirectResponse
      {
          SystemSetting::set('ai_companion_persona', $request->validated('ai_companion_persona'));

          return back()->with('success', 'Persona updated.');
      }
  }
  ```

- [ ] **Step 2: Create UpdatePersonaRequest**

  Create `app/Http/Requests/UpdatePersonaRequest.php`:

  ```php
  <?php

  namespace App\Http\Requests;

  use Illuminate\Foundation\Http\FormRequest;

  class UpdatePersonaRequest extends FormRequest
  {
      public function authorize(): bool
      {
          return $this->user()?->hasRole('super_admin') ?? false;
      }

      public function rules(): array
      {
          return [
              'ai_companion_persona' => ['required', 'string', 'max:5000'],
          ];
      }

      protected function prepareForValidation(): void
      {
          if ($this->has('ai_companion_persona') && is_string($this->ai_companion_persona)) {
              $this->merge(['ai_companion_persona' => strip_tags($this->ai_companion_persona)]);
          }
      }
  }
  ```

- [ ] **Step 3: Add routes to web.php**

  Inside the existing `role:super_admin` admin group, add:

  ```php
  // AI Companion hub (replaces knowledge-documents index)
  Route::get('ai-companion', [AiCompanionAdminController::class, 'index'])
      ->name('ai-companion.index');
  Route::put('ai-companion/persona', [AiCompanionAdminController::class, 'updatePersona'])
      ->name('ai-companion.persona.update');

  // Redirect old knowledge-documents index → new hub
  Route::get('knowledge-documents', fn () => redirect()->route('admin.ai-companion.index'));
  ```

  > **Important:** The new `GET knowledge-documents` redirect replaces the existing index route. The CRUD sub-routes (`knowledge-documents/create`, `knowledge-documents/{id}/edit`, etc.) remain unchanged.

  Add `use` imports at the top of `web.php`:
  ```php
  use App\Http\Controllers\Admin\AiCompanionAdminController;
  ```

- [ ] **Step 4: Verify routes**

  ```bash
  php artisan route:list | grep "ai-companion\|knowledge-documents"
  ```
  You should see `GET admin/ai-companion` and `PUT admin/ai-companion/persona`, plus the redirect for `GET admin/knowledge-documents`.

- [ ] **Step 5: Commit**

  ```bash
  git add app/Http/Controllers/Admin/AiCompanionAdminController.php \
          app/Http/Requests/UpdatePersonaRequest.php \
          routes/web.php
  git commit -m "feat(R8): add AiCompanionAdminController, UpdatePersonaRequest, and routes"
  ```

---

## Task 11: R8 — Create Admin/AiCompanion/Index.svelte (tabbed hub)

**Files:**
- Create: `resources/js/Pages/Admin/AiCompanion/Index.svelte`

> **Context:** Two tabs — **Knowledge Documents** (existing doc list, just shown here instead of its own page) and **Persona** (the textarea that was previously in Settings). The existing `KnowledgeDocuments` CRUD pages remain; this hub just provides the entry point.

- [ ] **Step 1: Create the directory and file**

  ```bash
  mkdir -p resources/js/Pages/Admin/AiCompanion
  ```

  Create `resources/js/Pages/Admin/AiCompanion/Index.svelte`:

  ```svelte
  <script>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
    import { Link, router, usePage } from '@inertiajs/svelte';
    import { useForm } from '@inertiajs/svelte';
    import { Button } from '@/Components/ui/button';
    import { Badge } from '@/Components/ui/badge';
    import * as Card from '@/Components/ui/card';
    import * as Table from '@/Components/ui/table';
    import { Plus, Pencil, Trash2 } from 'lucide-svelte';

    let { documents = [], ai_companion_persona = '' } = $props();

    const page = usePage();
    const flash = $derived($page.props.flash ?? {});
    const breadcrumbs = [{ label: 'AI Companion' }];

    // Tab state
    let activeTab = $state('documents');

    // Persona form
    const form = useForm({ ai_companion_persona });

    $effect(() => {
      form.update((f) => ({ ...f, ai_companion_persona }));
    });

    let savingPersona = $state(false);

    function submitPersona(e) {
      e.preventDefault();
      savingPersona = true;
      $form.put('/admin/ai-companion/persona', {
        preserveScroll: true,
        onFinish: () => { savingPersona = false; },
      });
    }

    function deleteDocument(id) {
      if (confirm('Delete this document? This cannot be undone.')) {
        router.delete(`/admin/knowledge-documents/${id}`, { preserveScroll: true });
      }
    }
  </script>

  <AuthenticatedLayout {breadcrumbs}>
    <div class="space-y-6 min-w-0">

      {#if flash.success}
        <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">{flash.success}</div>
      {/if}

      <!-- Tab bar -->
      <div class="flex gap-1 border-b border-border">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium transition-colors {activeTab === 'documents'
            ? 'border-b-2 border-primary text-primary'
            : 'text-muted-foreground hover:text-foreground'}"
          onclick={() => (activeTab = 'documents')}
        >
          Knowledge Documents
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium transition-colors {activeTab === 'persona'
            ? 'border-b-2 border-primary text-primary'
            : 'text-muted-foreground hover:text-foreground'}"
          onclick={() => (activeTab = 'persona')}
        >
          Persona
        </button>
      </div>

      <!-- Tab: Knowledge Documents -->
      {#if activeTab === 'documents'}
        <div class="space-y-4">
          <div class="flex justify-end">
            <Link href="/admin/knowledge-documents/create">
              <Button class="min-h-[44px]">
                <Plus class="mr-2 h-4 w-4" />
                Add Document
              </Button>
            </Link>
          </div>

          <div class="glass-panel rounded-2xl overflow-hidden p-0">
            <Table.Root>
              <Table.Header class="bg-muted/50">
                <Table.Row>
                  <Table.Head class="px-4 py-3">Title</Table.Head>
                  <Table.Head class="px-4 py-3">Status</Table.Head>
                  <Table.Head class="px-4 py-3">Added</Table.Head>
                  <Table.Head class="px-4 py-3 text-right">Actions</Table.Head>
                </Table.Row>
              </Table.Header>
              <Table.Body>
                {#each documents as doc (doc.id)}
                  <Table.Row>
                    <Table.Cell class="px-4 py-3 font-medium">{doc.title ?? doc.name ?? '—'}</Table.Cell>
                    <Table.Cell class="px-4 py-3">
                      <Badge variant={doc.status === 'active' ? 'success' : 'muted'}>{doc.status ?? 'active'}</Badge>
                    </Table.Cell>
                    <Table.Cell class="px-4 py-3 text-sm text-muted-foreground">{doc.created_at ?? '—'}</Table.Cell>
                    <Table.Cell class="px-4 py-3 text-right">
                      <div class="flex justify-end gap-2">
                        <Link href={`/admin/knowledge-documents/${doc.id}/edit`}>
                          <Button variant="ghost" size="icon" aria-label="Edit document">
                            <Pencil class="h-4 w-4" />
                          </Button>
                        </Link>
                        <Button
                          variant="ghost"
                          size="icon"
                          class="text-destructive hover:text-destructive"
                          aria-label="Delete document"
                          onclick={() => deleteDocument(doc.id)}
                        >
                          <Trash2 class="h-4 w-4" />
                        </Button>
                      </div>
                    </Table.Cell>
                  </Table.Row>
                {:else}
                  <Table.Row>
                    <Table.Cell colspan={4} class="px-4 py-12 text-center text-muted-foreground">
                      No knowledge documents yet. Add one to get started.
                    </Table.Cell>
                  </Table.Row>
                {/each}
              </Table.Body>
            </Table.Root>
          </div>
        </div>
      {/if}

      <!-- Tab: Persona -->
      {#if activeTab === 'persona'}
        <Card.Root>
          <Card.Header>
            <Card.Title>AI companion persona</Card.Title>
            <Card.Description>
              System instructions for the AI advisor (tone, guardrails, scope). Used when applicants chat with the advisor.
              Plain text only — no HTML. If empty, a safe default is used.
            </Card.Description>
          </Card.Header>
          <Card.Content>
            <form onsubmit={submitPersona} class="space-y-4">
              <textarea
                bind:value={$form.ai_companion_persona}
                placeholder="e.g. You are an encouraging academic counselor. Base your advice only on the data provided."
                rows="8"
                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[160px]"
                maxlength="5000"
              ></textarea>
              {#if $form.errors?.ai_companion_persona}
                <p class="text-sm text-destructive">{$form.errors.ai_companion_persona}</p>
              {/if}
              <p class="text-xs text-muted-foreground">Max 5000 characters.</p>
              <Button type="submit" disabled={savingPersona} class="min-h-[44px]">
                {savingPersona ? 'Saving…' : 'Save persona'}
              </Button>
            </form>
          </Card.Content>
        </Card.Root>
      {/if}

    </div>
  </AuthenticatedLayout>
  ```

- [ ] **Step 2: Check what fields KnowledgeDocument actually has**

  The `doc.title ?? doc.name` fallback handles naming differences. Run this to confirm the actual field name:
  ```bash
  php artisan tinker --execute="echo App\Models\KnowledgeDocument::first()?->toJson();"
  ```
  If the field is named differently (e.g., `filename`), update the template accordingly.

- [ ] **Step 3: Manual verify**

  Visit `/admin/ai-companion`. You should see the two-tab layout. Knowledge Documents tab shows the list. Persona tab shows the textarea.
  Visit `/admin/knowledge-documents` — it should redirect to `/admin/ai-companion`.

- [ ] **Step 4: Commit**

  ```bash
  git add resources/js/Pages/Admin/AiCompanion/Index.svelte
  git commit -m "feat(R8): add AI Companion admin hub page with Documents + Persona tabs"
  ```

---

## Task 12: R8+R3 — Sidebar update + Settings cleanup + KnowledgeDocument guard

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.svelte`
- Modify: `resources/js/Pages/Admin/Settings/Index.svelte`
- Modify: `app/Http/Controllers/Admin/SettingsController.php`
- Modify: `app/Http/Requests/UpdateSystemSettingsRequest.php`
- Modify: `app/Http/Controllers/Admin/KnowledgeDocumentController.php`

> **Context:**
> - Sidebar: replace "Knowledge Documents" with "AI Companion" (featureFlag gated)
> - Settings page: remove `ai_companion_persona` textarea and `consultation_enabled` toggle (both deprecated)
> - KnowledgeDocumentController: add 403 guard when AI companion is disabled (R3)

- [ ] **Step 1: Update sidebar in AuthenticatedLayout.svelte**

  Find in `navSections` (Administration section):
  ```js
  { href: '/admin/knowledge-documents', label: 'Knowledge Documents', icon: BookOpen, roles: ['super_admin'] },
  ```
  Replace with:
  ```js
  { href: '/admin/ai-companion', label: 'AI Companion', icon: Bot, roles: ['super_admin'], featureFlag: 'ai_exam_companion_enabled' },
  ```

  Update the icon import — add `Bot`:
  ```js
  import { ..., Bot } from 'lucide-svelte';
  ```

  Remove `BookOpen` from the import IF it is no longer used anywhere else in the file (search with Ctrl+F first).

- [ ] **Step 2: Remove persona + consultation from Settings/Index.svelte**

  Open `resources/js/Pages/Admin/Settings/Index.svelte`.

  a) Remove `ai_companion_persona` and `consultation_enabled` from `$props()`:
  ```js
  // BEFORE:
  let { ai_exam_companion_enabled = false, ai_companion_persona = '', consultation_enabled = true } = $props();

  // AFTER:
  let { ai_exam_companion_enabled = false } = $props();
  ```

  b) Remove them from `useForm()`:
  ```js
  // BEFORE:
  const form = useForm({
    ai_exam_companion_enabled,
    ai_companion_persona: ai_companion_persona ?? '',
    consultation_enabled,
  });

  // AFTER:
  const form = useForm({
    ai_exam_companion_enabled,
  });
  ```

  c) Remove the `$effect` that syncs `ai_companion_persona` and `consultation_enabled`.

  d) Update `submitSettings` — change `$form.transform()` to only include the remaining field:
  ```js
  $form.transform((data) => ({
    ai_exam_companion_enabled: !!data.ai_exam_companion_enabled,
  }));
  ```

  e) In the template, delete the **Consultation** Card entirely and the **AI companion persona** Card entirely. Only the AI exam companion toggle Card remains.

- [ ] **Step 3: Update SettingsController**

  Open `app/Http/Controllers/Admin/SettingsController.php`.

  In `index()`, remove `ai_companion_persona` and `consultation_enabled` from the Inertia props:
  ```php
  // BEFORE:
  return Inertia::render('Admin/Settings/Index', [
      'ai_exam_companion_enabled' => SystemSetting::aiCompanionEnabled(),
      'ai_companion_persona' => SystemSetting::get('ai_companion_persona', '') ?: '',
      'consultation_enabled' => SystemSetting::consultationEnabled(),
  ]);

  // AFTER:
  return Inertia::render('Admin/Settings/Index', [
      'ai_exam_companion_enabled' => SystemSetting::aiCompanionEnabled(),
  ]);
  ```

  In `update()`, remove the `ai_companion_persona` and `consultation_enabled` blocks.

- [ ] **Step 4: Update UpdateSystemSettingsRequest**

  Open `app/Http/Requests/UpdateSystemSettingsRequest.php`.

  Remove `ai_companion_persona` and `consultation_enabled` from `rules()`. Also remove the `prepareForValidation()` method (it was only stripping tags from the persona):
  ```php
  // AFTER:
  public function rules(): array
  {
      return [
          'ai_exam_companion_enabled' => ['sometimes', 'boolean'],
      ];
  }

  // Delete prepareForValidation() entirely
  ```

- [ ] **Step 5: Add 403 guard to KnowledgeDocumentController**

  Open `app/Http/Controllers/Admin/KnowledgeDocumentController.php`.

  Add this check at the top of the `index()` method (and any other public methods that serve pages):
  ```php
  if (! SystemSetting::aiCompanionEnabled()) {
      abort(403, 'AI Companion is disabled.');
  }
  ```

  Add the import if not present:
  ```php
  use App\Models\SystemSetting;
  ```

- [ ] **Step 6: Verify Settings page still works**

  Visit `/admin/settings`. You should see only the AI Exam Companion toggle. The Consultation and Persona cards should be gone. Save the toggle — confirm it saves without error.

- [ ] **Step 7: Commit**

  ```bash
  git add resources/js/Layouts/AuthenticatedLayout.svelte \
          resources/js/Pages/Admin/Settings/Index.svelte \
          app/Http/Controllers/Admin/SettingsController.php \
          app/Http/Requests/UpdateSystemSettingsRequest.php \
          app/Http/Controllers/Admin/KnowledgeDocumentController.php
  git commit -m "feat(R8+R3): AI Companion sidebar item, settings cleanup, add 403 guard to KnowledgeDocumentController"
  ```

---

## Task 13: R5 — notify_on_publish backend (request + controller)

**Files:**
- Modify: `app/Http/Requests/UpdateSystemSettingsRequest.php`
- Modify: `app/Http/Controllers/Admin/SettingsController.php`

> **Context:** `notify_on_publish` is a boolean toggle in Settings. It is NOT shared as a global Inertia prop because it only affects the Settings page UI and the publish action — no layout component needs it.

- [ ] **Step 1: Add notify_on_publish to the form request**

  Open `app/Http/Requests/UpdateSystemSettingsRequest.php`.

  Update `rules()`:
  ```php
  public function rules(): array
  {
      return [
          'ai_exam_companion_enabled' => ['sometimes', 'boolean'],
          'notify_on_publish'         => ['sometimes', 'boolean'],
      ];
  }
  ```

- [ ] **Step 2: Update SettingsController to handle notify_on_publish**

  Open `app/Http/Controllers/Admin/SettingsController.php`.

  In `index()`:
  ```php
  return Inertia::render('Admin/Settings/Index', [
      'ai_exam_companion_enabled' => SystemSetting::aiCompanionEnabled(),
      'notify_on_publish'         => SystemSetting::notifyOnPublish(),
  ]);
  ```

  In `update()`, add after the existing `ai_exam_companion_enabled` block:
  ```php
  if (array_key_exists('notify_on_publish', $validated)) {
      SystemSetting::set('notify_on_publish', (bool) $validated['notify_on_publish']);
  }
  ```

- [ ] **Step 3: Commit**

  ```bash
  git add app/Http/Requests/UpdateSystemSettingsRequest.php \
          app/Http/Controllers/Admin/SettingsController.php
  git commit -m "feat(R5): add notify_on_publish to settings backend"
  ```

---

## Task 14: R5 — notify_on_publish Settings UI

**Files:**
- Modify: `resources/js/Pages/Admin/Settings/Index.svelte`

> **Context:** Add a toggle card for "Exam schedule notifications" right below the AI companion card.

- [ ] **Step 1: Update props, useForm, and transform**

  Open `resources/js/Pages/Admin/Settings/Index.svelte`.

  a) Add `notify_on_publish` to props:
  ```js
  let { ai_exam_companion_enabled = false, notify_on_publish = false } = $props();
  ```

  b) Add to `useForm()`:
  ```js
  const form = useForm({
    ai_exam_companion_enabled,
    notify_on_publish,
  });
  ```

  c) Update the `$effect` that syncs form with fresh props:
  ```js
  $effect(() => {
    form.update((f) => ({
      ...f,
      ai_exam_companion_enabled,
      notify_on_publish,
    }));
  });
  ```

  d) Update `$form.transform()` in `submitSettings`:
  ```js
  $form.transform((data) => ({
    ai_exam_companion_enabled: !!data.ai_exam_companion_enabled,
    notify_on_publish: !!data.notify_on_publish,
  }));
  ```

- [ ] **Step 2: Add the Bell icon import**

  Find the existing `import { Bot, MessageSquare }` line. Add `Bell`:
  ```js
  import { Bot, Bell } from 'lucide-svelte';
  ```

- [ ] **Step 3: Add the notify_on_publish Card to the template**

  In the `<form>` block, after the AI companion Card:

  ```svelte
  <Card>
    <CardHeader>
      <CardTitle class="flex items-center gap-2">
        <Bell class="h-5 w-5" />
        Exam schedule notifications
      </CardTitle>
      <CardDescription>
        When enabled, applicants receive an email when their exam session is published.
        Requires a queue worker in production (or QUEUE_CONNECTION=sync in .env for local dev).
      </CardDescription>
    </CardHeader>
    <CardContent class="flex items-center gap-4">
      <Switch
        checked={$form.notify_on_publish}
        onCheckedChange={(checked) => form.update((f) => ({ ...f, notify_on_publish: checked }))}
        aria-label="Enable exam schedule notifications"
      />
      <span class="text-sm font-medium">
        {$form.notify_on_publish ? 'Enabled' : 'Disabled'}
      </span>
    </CardContent>
  </Card>
  ```

- [ ] **Step 4: Manual verify**

  Visit `/admin/settings`. You should see both the AI companion toggle and the new Exam schedule notifications toggle.
  Toggle it on → Save → refresh → it should stay on.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Admin/Settings/Index.svelte
  git commit -m "feat(R5): add notify_on_publish toggle to Settings UI"
  ```

---

## Task 15: R5 — Create ExamSessionPublished notification + wire into publish action

**Files:**
- Create: `app/Notifications/ExamSessionPublished.php`
- Modify: `app/Http/Controllers/Admin/ExamSessionController.php`

> **Context:** When an admin publishes an exam session, all assigned applicants should be notified (if `notify_on_publish` is true). The notification uses `ShouldQueue` — it runs in the background so the publish action stays fast. `$session->applicants` is the relationship — check the ExamSession model to confirm the relationship name.

- [ ] **Step 1: Confirm the applicants relationship name on ExamSession**

  ```bash
  grep -n "function applicants\|HasMany.*Applicant\|belongsToMany.*Applicant" app/Models/ExamSession.php
  ```
  Note the relationship method name — it should be `applicants()`. If it's different, update Step 3 accordingly.

- [ ] **Step 2: Create the notification class**

  Create `app/Notifications/ExamSessionPublished.php`:

  ```php
  <?php

  namespace App\Notifications;

  use App\Models\ExamSession;
  use Illuminate\Bus\Queueable;
  use Illuminate\Contracts\Queue\ShouldQueue;
  use Illuminate\Notifications\Messages\MailMessage;
  use Illuminate\Notifications\Notification;

  class ExamSessionPublished extends Notification implements ShouldQueue
  {
      use Queueable;

      public function __construct(public readonly ExamSession $session) {}

      public function via(object $notifiable): array
      {
          return ['mail', 'database'];
      }

      public function toMail(object $notifiable): MailMessage
      {
          $date = $this->session->scheduled_at?->format('F j, Y') ?? $this->session->date?->format('F j, Y') ?? 'TBA';
          $time = $this->session->scheduled_at?->format('g:i A') ?? '';
          $room = $this->session->room?->name ?? 'TBA';

          return (new MailMessage)
              ->subject('Your exam has been scheduled')
              ->greeting('Hello, ' . ($notifiable->name ?? 'Applicant') . '!')
              ->line('Your exam session has been confirmed.')
              ->line('**Date:** ' . $date)
              ->when($time, fn ($mail) => $mail->line('**Time:** ' . $time))
              ->line('**Room:** ' . $room)
              ->action('View in Portal', url('/portal'))
              ->line('Please arrive 15 minutes early with a valid ID.');
      }

      public function toArray(object $notifiable): array
      {
          return [
              'type'       => 'exam_session_published',
              'session_id' => $this->session->id,
              'message'    => 'Your exam session has been scheduled.',
          ];
      }
  }
  ```

- [ ] **Step 3: Add dispatch to ExamSessionController@publish**

  Open `app/Http/Controllers/Admin/ExamSessionController.php`. Find the `publish()` method (around line 339). After the `$exam_session->update([...])` call, add the notification dispatch:

  **Before (end of publish method):**
  ```php
  $exam_session->update([
      'status' => ExamSession::STATUS_PUBLISHED,
      'published_at' => now(),
  ]);

  return redirect()->route('admin.test-scheduling.show', $exam_session)->with('success', 'Session published.');
  ```

  **After:**
  ```php
  $exam_session->update([
      'status' => ExamSession::STATUS_PUBLISHED,
      'published_at' => now(),
  ]);

  if (SystemSetting::notifyOnPublish()) {
      $exam_session->applicants->each(
          fn ($applicant) => $applicant->notify(new ExamSessionPublished($exam_session))
      );
  }

  return redirect()->route('admin.test-scheduling.show', $exam_session)->with('success', 'Session published.');
  ```

  Add imports at the top of the file (after existing `use` statements):
  ```php
  use App\Models\SystemSetting;
  use App\Notifications\ExamSessionPublished;
  ```

- [ ] **Step 4: Verify the publish action still works**

  In dev, temporarily set `QUEUE_CONNECTION=sync` in `.env` to see notifications fire immediately. Publish a test session — confirm no errors. Check `php artisan queue:failed` is empty.

  To test without a real applicant assigned, you can check the logic manually:
  ```bash
  php artisan tinker --execute="echo App\Models\SystemSetting::notifyOnPublish() ? 'on' : 'off';"
  ```

- [ ] **Step 5: Commit**

  ```bash
  git add app/Notifications/ExamSessionPublished.php \
          app/Http/Controllers/Admin/ExamSessionController.php
  git commit -m "feat(R5): add ExamSessionPublished notification + dispatch on publish"
  ```

---

## Task 16: R6 — release_mode backend (request + controller)

**Files:**
- Modify: `app/Http/Requests/UpdateSystemSettingsRequest.php`
- Modify: `app/Http/Controllers/Admin/SettingsController.php`

- [ ] **Step 1: Add release_mode to the form request**

  Open `app/Http/Requests/UpdateSystemSettingsRequest.php`.

  Update `rules()`:
  ```php
  public function rules(): array
  {
      return [
          'ai_exam_companion_enabled' => ['sometimes', 'boolean'],
          'notify_on_publish'         => ['sometimes', 'boolean'],
          'release_mode'              => ['sometimes', 'in:online,f2f,both'],
      ];
  }
  ```

- [ ] **Step 2: Update SettingsController**

  In `index()`:
  ```php
  return Inertia::render('Admin/Settings/Index', [
      'ai_exam_companion_enabled' => SystemSetting::aiCompanionEnabled(),
      'notify_on_publish'         => SystemSetting::notifyOnPublish(),
      'release_mode'              => SystemSetting::releaseMode(),
  ]);
  ```

  In `update()`, add after the existing `notify_on_publish` block:
  ```php
  if (array_key_exists('release_mode', $validated)) {
      SystemSetting::set('release_mode', $validated['release_mode']);
  }
  ```

- [ ] **Step 3: Commit**

  ```bash
  git add app/Http/Requests/UpdateSystemSettingsRequest.php \
          app/Http/Controllers/Admin/SettingsController.php
  git commit -m "feat(R6): add release_mode to settings backend"
  ```

---

## Task 17: R6 — release_mode Settings UI (two independent toggles)

**Files:**
- Modify: `resources/js/Pages/Admin/Settings/Index.svelte`

> **Context:** Two toggles (Online / F2F) that combine into one of three enum values: `online`, `f2f`, or `both`. The Save button is disabled if both toggles are off (which would be invalid). The `Share2` icon is used for this card.

- [ ] **Step 1: Add props, form fields, and toggle state**

  Open `resources/js/Pages/Admin/Settings/Index.svelte`.

  a) Add `release_mode` to props:
  ```js
  let { ai_exam_companion_enabled = false, notify_on_publish = false, release_mode = 'online' } = $props();
  ```

  b) Add to `useForm()`:
  ```js
  const form = useForm({
    ai_exam_companion_enabled,
    notify_on_publish,
    release_mode,
  });
  ```

  c) Add the two derived toggle states and the compute function (add after `let saving = $state(false);`):
  ```js
  let releaseOnline = $state(release_mode === 'online' || release_mode === 'both');
  let releasef2f    = $state(release_mode === 'f2f'    || release_mode === 'both');

  function computeReleaseMode(online, f2f) {
    if (online && f2f) return 'both';
    if (online) return 'online';
    if (f2f) return 'f2f';
    return null; // invalid state — blocked by UI
  }

  function handleReleaseOnlineChange(checked) {
    releaseOnline = checked;
    form.update((f) => ({ ...f, release_mode: computeReleaseMode(checked, releasef2f) }));
  }

  function handleReleaseF2fChange(checked) {
    releasef2f = checked;
    form.update((f) => ({ ...f, release_mode: computeReleaseMode(releaseOnline, checked) }));
  }

  const releaseModeInvalid = $derived(!releaseOnline && !releasef2f);
  ```

  d) Sync with fresh props in `$effect`:
  ```js
  $effect(() => {
    form.update((f) => ({
      ...f,
      ai_exam_companion_enabled,
      notify_on_publish,
      release_mode,
    }));
    releaseOnline = release_mode === 'online' || release_mode === 'both';
    releasef2f    = release_mode === 'f2f'    || release_mode === 'both';
  });
  ```

  e) Update `$form.transform()` in `submitSettings`:
  ```js
  $form.transform((data) => ({
    ai_exam_companion_enabled: !!data.ai_exam_companion_enabled,
    notify_on_publish: !!data.notify_on_publish,
    release_mode: data.release_mode,
  }));
  ```

- [ ] **Step 2: Add the Share2 icon import**

  ```js
  import { Bot, Bell, Share2 } from 'lucide-svelte';
  ```

- [ ] **Step 3: Add the release_mode Card to the template**

  After the `notify_on_publish` Card, add:

  ```svelte
  <Card>
    <CardHeader>
      <CardTitle class="flex items-center gap-2">
        <Share2 class="h-5 w-5" />
        Result release mode
      </CardTitle>
      <CardDescription>
        Controls how exam results are delivered to applicants. At least one mode must be enabled.
      </CardDescription>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="flex items-center gap-4">
        <Switch
          checked={releaseOnline}
          onCheckedChange={handleReleaseOnlineChange}
          aria-label="Enable online release"
        />
        <div>
          <p class="text-sm font-medium">Online release</p>
          <p class="text-xs text-muted-foreground">Results visible in portal + email delivery</p>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <Switch
          checked={releasef2f}
          onCheckedChange={handleReleaseF2fChange}
          aria-label="Enable F2F release"
        />
        <div>
          <p class="text-sm font-medium">F2F release</p>
          <p class="text-xs text-muted-foreground">Results handed in person — portal view disabled for applicants</p>
        </div>
      </div>
      {#if releaseModeInvalid}
        <p class="text-sm text-destructive">At least one release mode must be enabled.</p>
      {/if}
    </CardContent>
  </Card>
  ```

- [ ] **Step 4: Disable the Save button when release_mode is invalid**

  Find the Save button in the template:
  ```svelte
  <Button type="submit" disabled={saving} class="min-h-[44px]">
  ```
  Update to:
  ```svelte
  <Button type="submit" disabled={saving || releaseModeInvalid} class="min-h-[44px]">
  ```

- [ ] **Step 5: Manual verify**

  Visit `/admin/settings`. Toggle Online off → F2F on → save → reload. The `release_mode` should now be `f2f`.
  Toggle both off → save button should be disabled.

- [ ] **Step 6: Commit**

  ```bash
  git add resources/js/Pages/Admin/Settings/Index.svelte
  git commit -m "feat(R6): add release_mode two-toggle UI to Settings"
  ```

---

## Task 18: R7 — Disable Grading print buttons when release_mode is 'online'

**Files:**
- Modify: `resources/js/Pages/Grading/ResultSheet.svelte`
- Modify: `resources/js/Pages/Grading/PrintBatch.svelte`
- Modify: `resources/js/Pages/Grading/ResultSheetBulk.svelte`

> **Context:** When `release_mode === 'online'`, results are delivered digitally — physical printing is not needed (and could be misleading). The admin's print buttons are disabled with a tooltip explaining why. `release_mode` is available on every page via shared props (set up in Task 2).

- [ ] **Step 1: Update ResultSheet.svelte**

  Open `resources/js/Pages/Grading/ResultSheet.svelte`.

  Add `usePage` import and the print-disabled derived state at the top of the script:
  ```js
  import { usePage } from '@inertiajs/svelte'; // add this import

  // Add these two lines after the existing $props() destructuring:
  const _page = usePage();
  const printDisabled = $derived((_page.props.release_mode ?? 'online') === 'online');
  ```

  Find the print button:
  ```svelte
  <Button onclick={printSheet} class="min-h-[44px]">Print this sheet</Button>
  ```
  Replace with:
  ```svelte
  <Button
    onclick={printSheet}
    disabled={printDisabled}
    title={printDisabled ? 'Switch to F2F or Both release mode in Settings to enable printing.' : undefined}
    class="min-h-[44px]"
  >
    Print this sheet
  </Button>
  ```

  Add an info note below the buttons when print is disabled:
  ```svelte
  {#if printDisabled}
    <p class="text-xs text-muted-foreground">
      Printing is disabled in online-only release mode.
      <a href="/admin/settings" class="underline">Change in Settings</a>
    </p>
  {/if}
  ```

- [ ] **Step 2: Update PrintBatch.svelte**

  Open `resources/js/Pages/Grading/PrintBatch.svelte`.

  Add the same `printDisabled` derived state and disable the `printBulk` call button.

  First check what the bulk print button looks like:
  ```bash
  grep -n "printBulk\|Print\|Button" resources/js/Pages/Grading/PrintBatch.svelte | head -20
  ```

  Add after existing imports and props:
  ```js
  import { usePage } from '@inertiajs/svelte'; // add if not already imported

  const _page = usePage();
  const printDisabled = $derived((_page.props.release_mode ?? 'online') === 'online');
  ```

  Find any "Print" or "print" buttons in the template and add `disabled={printDisabled}` and a `title` attribute to each.

- [ ] **Step 3: Update ResultSheetBulk.svelte (if it exists)**

  ```bash
  ls resources/js/Pages/Grading/ResultSheetBulk.svelte
  ```

  If it exists, apply the same pattern:
  ```js
  const _page = usePage();
  const printDisabled = $derived((_page.props.release_mode ?? 'online') === 'online');
  ```
  Disable print buttons with `disabled={printDisabled}`.

- [ ] **Step 4: Manual verify**

  Set `release_mode` to `online` in Settings. Visit `/grading` → open a session → print page. The print buttons should be grayed out with the tooltip message.
  Set `release_mode` to `f2f`. The print buttons should be active again.

- [ ] **Step 5: Commit**

  ```bash
  git add resources/js/Pages/Grading/ResultSheet.svelte \
          resources/js/Pages/Grading/PrintBatch.svelte \
          resources/js/Pages/Grading/ResultSheetBulk.svelte
  git commit -m "feat(R7): disable Grading print buttons when release_mode is online-only"
  ```

---

## Task 19: R7 — Block portal result view when release_mode is 'f2f'

**Files:**
- Modify: `app/Http/Controllers/PortalAuthController.php` (or whichever controller serves `Portal/Dashboard`)
- Modify: `resources/js/Pages/Portal/Dashboard.svelte`

> **Context:** When `release_mode === 'f2f'`, applicants must not see their results in the portal. The portal dashboard currently checks `consultation.status === 'released'` to show results. We override this in the controller: if f2f mode, set `consultation` to pending status so no results card renders. We also show a clear "collect in person" message.

- [ ] **Step 1: Find the portal dashboard controller**

  ```bash
  grep -rn "Portal/Dashboard\|portal.dashboard" app/Http/Controllers/ --include="*.php"
  ```
  Note the controller and method name. It is likely `PortalAuthController.php`.

- [ ] **Step 2: Add the f2f gate in the portal controller**

  Open the portal controller. Find the method that renders `Portal/Dashboard`.

  Find where `consultation` data is built (look for `'consultation' =>` in the return). Add the f2f gate:

  ```php
  use App\Models\SystemSetting;

  // Inside the dashboard method, after building $consultation:
  $releaseMode = SystemSetting::releaseMode();

  // If f2f mode, hide result data from the portal
  if ($releaseMode === 'f2f') {
      $consultation = ['status' => 'pending', 'summary' => null];
  }

  return Inertia::render('Portal/Dashboard', [
      // ...existing props...
      'consultation' => $consultation,
      'results_blocked' => ($releaseMode === 'f2f'), // extra flag for the message
  ]);
  ```

  > **Note:** If the existing code already builds `$consultation` with real data, add the override block directly before the `Inertia::render()` call. If `$consultation` is built inline in the render call, extract it to a variable first, then override it.

- [ ] **Step 3: Update Portal/Dashboard.svelte to show the f2f message**

  Open `resources/js/Pages/Portal/Dashboard.svelte`.

  Add `results_blocked` to `$props()`:
  ```js
  let {
    applicant = {},
    status_tracker,
    exam_schedule = null,
    score_release = null,
    consultation = { status: 'pending', summary: null },
    ai_companion_enabled = false,
    notifications,
    results_blocked = false,  // ADD THIS
  } = $props();
  ```

  Find the consultation results section:
  ```svelte
  {#if consultation.status === 'released' && consultation.summary}
    <Card.Root>
      ...
    </Card.Root>
  {/if}
  ```

  Add a `results_blocked` banner BEFORE that block:
  ```svelte
  {#if results_blocked}
    <Card.Root>
      <Card.Content class="p-6 text-center space-y-2">
        <p class="font-medium text-foreground">Results will be provided in person</p>
        <p class="text-sm text-muted-foreground">
          Please visit the guidance office to receive your exam results.
        </p>
      </Card.Content>
    </Card.Root>
  {/if}
  ```

  The existing `{#if consultation.status === 'released' ...}` block will already be hidden because the controller set `consultation.status` to `'pending'` in f2f mode. The banner gives applicants a clear explanation.

- [ ] **Step 4: Manual verify**

  In Settings, set `release_mode` to `f2f`. Log in as a test applicant. The portal dashboard should show "Results will be provided in person" instead of any released results.
  Switch back to `online`. The normal results display should resume.

- [ ] **Step 5: Commit**

  ```bash
  git add app/Http/Controllers/PortalAuthController.php \
          resources/js/Pages/Portal/Dashboard.svelte
  git commit -m "feat(R7): block applicant portal result view when release_mode is f2f"
  ```

---

## Final Verification Checklist

After all 19 tasks are done, do a complete smoke test:

- [ ] `/admin/rooms` — breadcrumb shows **Exam Scheduling › Rooms**
- [ ] `/admin/test-scheduling` — shows **Manage Rooms** button
- [ ] `/admin/courses` — breadcrumb shows **Academic Years › Courses**
- [ ] `/admin/seasons` — shows **Manage Courses** button
- [ ] `/consultation` — returns 404 (route deleted)
- [ ] `/release` — Release Management page loads with mode banner
- [ ] `/admin/knowledge-documents` — redirects to `/admin/ai-companion`
- [ ] `/admin/ai-companion` — shows two tabs (Documents + Persona); 403 when AI companion disabled
- [ ] `/admin/settings` — shows 3 cards: AI companion, Notify on publish, Release mode; Consultation and Persona cards are gone
- [ ] Settings: toggle both release toggles off → Save button is disabled
- [ ] `/grading/.../print` — print buttons disabled when `release_mode = online`
- [ ] Portal dashboard — shows "in person" message when `release_mode = f2f`

```bash
# Final check: no orphan consultation references
grep -rn "consultation_enabled\|ConsultationController\|consultation\.enabled" \
  app/ routes/ resources/ --include="*.php" --include="*.svelte"
```
Expected: zero matches.

---

## Common Mistakes to Avoid

1. **`notify_on_publish` won't cast to bool** unless you added it to the boolean-cast list in `SystemSetting::get()` (Task 1, Step 2). If the toggle always saves as `false`, check that list.

2. **Queue notifications fail silently** in production if no queue worker is running. Set `QUEUE_CONNECTION=sync` in `.env` for local dev. In production, run `php artisan queue:work` or use Laravel Horizon.

3. **`computeReleaseMode` returns `null`** when both toggles are off. The backend validates `in:online,f2f,both` and will reject `null`. The Save button's disabled state prevents this — but if you bypass it, Laravel returns a validation error.

4. **`SendHorizonal`** — note the spelling in lucide-svelte: `Horizonal` (single 'l'), not `Horizontal`.

5. **KnowledgeDocument field name** — the page in Task 11 uses `doc.title ?? doc.name`. Run the tinker command in Task 11 Step 2 to confirm the actual field name before QA.
