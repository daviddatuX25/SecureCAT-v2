# SecureCAT-v2 — Navigation & Settings Restructure Design Spec

**Date:** 2026-04-09
**Status:** Approved
**Scope:** R1–R8 (8 requirements covering nav hierarchy, settings additions, consultation removal, and AI Companion restructure)

---

## 0. Overview

This spec covers eight related changes that clean up the navigation structure, remove dead concepts (Consultation), add two new operational settings (publish notification + release mode), and properly house the AI Companion admin tools. All changes are isolated enough to be implemented independently but are grouped here because they share the same infrastructure touchpoint: `HandleInertiaRequests` shared props and the `SystemSetting` model.

### Requirement Summary

| ID | Title | Type |
|----|-------|------|
| R1 | Rooms under Exam Scheduling | Nav hierarchy + breadcrumbs |
| R2 | Courses under Academic Years | Nav hierarchy + breadcrumbs |
| R3 | AI Companion conditional hiding | Feature flag + route guard |
| R4 | Remove Consultation, add Release page | Delete + new page |
| R5 | notify_on_publish setting | New setting + full notification |
| R6 | release_mode setting | New setting + conditional UI |
| R7 (revised) | Print/portal effects from release_mode | Conditional UI |
| R8 | AI Companion admin restructure | New route + tab UI |

### Key Design Principles
- **No new tables.** `SystemSetting` is a key/value store — new settings are new rows, not new columns.
- **Shared props as the single source of truth** for anything that affects the sidebar or global UI.
- **Junior-friendly file structure** — each new concept gets its own clearly named controller and Svelte page.
- **Clean deletes** — Consultation code is fully removed, not commented out or feature-flagged.

---

## 1. Shared Settings Infrastructure

### 1.1 SystemSetting Model Changes

**File:** `app/Models/SystemSetting.php`

Add two new static helper methods:

```php
// Returns 'online' | 'f2f' | 'both'. Default: 'online'.
public static function releaseMode(): string
{
    return static::get('release_mode', 'online');
}

// Returns true if admin wants to notify applicants when exam sessions are published.
public static function notifyOnPublish(): bool
{
    return (bool) static::get('notify_on_publish', false);
}
```

Remove `consultationEnabled()` — it is no longer needed after R4.

### 1.2 HandleInertiaRequests Shared Props

**File:** `app/Http/Middleware/HandleInertiaRequests.php`

The `share()` method must expose these values to every page so the sidebar and any component can conditionally render without per-controller changes:

```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [...],
        'flash' => [...],
        // Feature flags — available on every page
        'ai_exam_companion_enabled' => SystemSetting::aiCompanionEnabled(),
        'release_mode'              => SystemSetting::releaseMode(),
        // NOTE: notify_on_publish is only relevant on the Settings page,
        // so it is NOT shared globally — the SettingsController passes it directly.
    ]);
}
```

**Remove** `consultation_enabled` from shared props (R4 cleanup).

**Why not `notify_on_publish` in shared props?** It has no effect on global layout. It only appears in Settings UI and in the publish action. Sharing it globally wastes a prop slot.

### 1.3 How the Sidebar Uses Shared Props

In `AuthenticatedLayout.svelte`, the `canSee()` function already supports `featureFlag`:

```js
if (item.featureFlag && !$page.props[item.featureFlag]) return false;
```

Any new nav item that needs conditional visibility just needs `featureFlag: 'the_prop_key'` added to its nav definition. The shared props make this work automatically.

---

## 2. Navigation Restructuring

### 2.1 R1 — Rooms Under Exam Scheduling

**Goal:** Rooms is no longer a standalone concept. It belongs to Exam Scheduling because rooms are configured specifically for scheduling sessions. The user navigates to Rooms FROM Exam Scheduling, and every Rooms page shows the parent in its breadcrumb trail.

**Access Point — `TestScheduling/Index.svelte`:**

Add a "Manage Rooms" button in the page header action bar, alongside the existing "New Session" button:

```svelte
<Link href="/admin/rooms">
  <Button variant="outline" class="min-h-[44px]">
    <DoorOpen class="mr-2 h-4 w-4" />
    Manage Rooms
  </Button>
</Link>
```

**Breadcrumb Updates — all 4 Rooms pages:**

| Page | Old breadcrumbs | New breadcrumbs |
|------|----------------|-----------------|
| `Rooms/Index.svelte` | `[{ label: 'Rooms' }]` | `[{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'Rooms' }]` |
| `Rooms/Create.svelte` | `[{ label: 'Rooms', href: '/admin/rooms' }, { label: 'Add Room' }]` | `[{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'Rooms', href: '/admin/rooms' }, { label: 'Add Room' }]` |
| `Rooms/Edit.svelte` | `[{ label: 'Rooms', href: '/admin/rooms' }, { label: 'Edit' }]` | `[{ label: 'Exam Scheduling', href: '/admin/test-scheduling' }, { label: 'Rooms', href: '/admin/rooms' }, { label: 'Edit' }]` |

No route changes. No sidebar changes.

### 2.2 R2 — Courses Under Academic Years

**Goal:** Same pattern as R1. Courses belong to Academic Years context.

**Access Point — `Seasons/Index.svelte`:**

The existing "Add Course" button already links to `/admin/courses`. Rename it to "Manage Courses" so it's clear it's a management page, not just a create action:

```svelte
<!-- Change label from "Add Course" to "Manage Courses" -->
<Link href="/admin/courses">
  <Button variant="outline" class="min-h-[44px]">
    <BookOpen class="mr-2 h-4 w-4" />
    Manage Courses
  </Button>
</Link>
```

**Breadcrumb Updates — all 3 Courses pages:**

| Page | Old breadcrumbs | New breadcrumbs |
|------|----------------|-----------------|
| `Courses/Index.svelte` | `[{ label: 'Courses' }]` | `[{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Courses' }]` |
| `Courses/Create.svelte` | `[{ label: 'Courses', href: '/admin/courses' }, { label: 'Add Course' }]` | `[{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Courses', href: '/admin/courses' }, { label: 'Add Course' }]` |
| `Courses/Edit.svelte` | `[{ label: 'Courses', href: '/admin/courses' }, { label: 'Edit Course' }]` | `[{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Courses', href: '/admin/courses' }, { label: 'Edit Course' }]` |

No route changes. No sidebar changes.

### 2.3 R8 — AI Companion Admin Section

**Goal:** Replace the standalone "Knowledge Documents" sidebar link with an "AI Companion" admin hub that houses both knowledge document management AND persona configuration in one place (tabs). The persona textarea moves out of Settings.

#### New Route + Controller

**File to create:** `app/Http/Controllers/Admin/AiCompanionAdminController.php`

```php
class AiCompanionAdminController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', KnowledgeDocument::class); // reuse same policy

        return Inertia::render('Admin/AiCompanion/Index', [
            'documents'           => KnowledgeDocument::orderBy('created_at', 'desc')->get(),
            'ai_companion_persona'=> SystemSetting::personaPrompt(),
        ]);
    }

    public function updatePersona(UpdatePersonaRequest $request): RedirectResponse
    {
        SystemSetting::set('ai_companion_persona', $request->validated('ai_companion_persona'));
        return back()->with('success', 'Persona updated.');
    }
}
```

**Routes to add in `web.php`** (inside `role:super_admin` group):

```php
Route::get('ai-companion', [AiCompanionAdminController::class, 'index'])
    ->name('ai-companion.index');
Route::put('ai-companion/persona', [AiCompanionAdminController::class, 'updatePersona'])
    ->name('ai-companion.persona.update');

// Old knowledge-documents index now redirects to the new hub
Route::get('knowledge-documents', fn() => redirect()->route('admin.ai-companion.index'));
```

All other `/admin/knowledge-documents/*` CRUD routes (create, store, edit, update, destroy, import) remain unchanged — the hub page links into them as before.

#### New Page

**File to create:** `resources/js/Pages/Admin/AiCompanion/Index.svelte`

Two-tab layout using the existing `ui/toggle-group` or a simple tab bar (whichever matches the project style):

- **Tab: Knowledge Documents** — renders a table of documents (title, created date, status) with Add / Edit / Delete links. Functionally identical to the existing `KnowledgeDocuments/Index.svelte`, but embedded as a tab.
- **Tab: Persona** — the `ai_companion_persona` textarea. Save button calls `PUT /admin/ai-companion/persona`. Flash message on success.

Breadcrumbs: `[{ label: 'AI Companion' }]`

#### Sidebar Change

In `AuthenticatedLayout.svelte`, Administration section — replace:
```js
{ href: '/admin/knowledge-documents', label: 'Knowledge Documents', icon: BookOpen, roles: ['super_admin'] }
```
with:
```js
{ href: '/admin/ai-companion', label: 'AI Companion', icon: Bot, roles: ['super_admin'], featureFlag: 'ai_exam_companion_enabled' }
```

`Bot` icon is already imported (used in Settings page). `BookOpen` import can be removed if no longer used elsewhere.

#### Settings Page Cleanup

Remove the "AI companion persona" Card entirely from `Settings/Index.svelte`. Remove `ai_companion_persona` from the `useForm()` fields and `$form.transform()`. Remove it from `UpdateSystemSettingsRequest`. Remove it from `SettingsController@update`.

The `ai_exam_companion_enabled` toggle remains in Settings (it controls the feature flag, which is separate from persona management).

#### Route Guard for R3

When `ai_exam_companion_enabled = false`, the sidebar link is hidden (via `featureFlag`). But a user could still navigate directly to `/admin/ai-companion`. Add middleware or a controller check:

```php
// In AiCompanionAdminController@index:
if (!SystemSetting::aiCompanionEnabled()) {
    abort(403, 'AI Companion is disabled.');
}
```

Apply the same guard to all `KnowledgeDocumentController` methods.

---

## 3. Consultation Removal + New Release Page (R4)

### 3.1 Files to Delete (Complete List)

**Svelte pages:**
- `resources/js/Pages/Consultation/Dashboard.svelte`
- `resources/js/Pages/Consultation/ScheduleDay.svelte`
- `resources/js/Pages/Consultation/ApplicantView.svelte`
- `resources/js/Pages/Consultation/ConsultationDay.svelte`

**PHP Controllers:**
- `app/Http/Controllers/Consultation/ConsultationController.php`
- `app/Http/Controllers/Consultation/ConsultationApplicantController.php`
- `app/Http/Controllers/Consultation/ConsultationDayController.php`
- `app/Http/Controllers/Consultation/ConsultationScheduleController.php`

**Middleware:**
- `app/Http/Middleware/EnsureConsultationEnabled.php` (or however it's named)
- Remove its registration from `bootstrap/app.php` (or `Kernel.php`)

### 3.2 Code to Remove from Existing Files

**`web.php`:** Delete the entire `consultation.*` route group (roughly 8 route definitions + controller imports at the top).

**`SystemSetting.php`:** Remove `consultationEnabled()` method.

**`HandleInertiaRequests.php`:** Remove `consultation_enabled` from shared props.

**`AuthenticatedLayout.svelte`:** Remove the "Release & Consultation" nav item from navSections. Add the new "Release" item (see below).

**`Settings/Index.svelte`:** Remove the Consultation Card, remove `consultation_enabled` from useForm, remove from `$form.transform()`.

**`SettingsController.php`:** Remove `consultation_enabled` from `index()` inertia props and `update()` handler.

**`UpdateSystemSettingsRequest.php`:** Remove `consultation_enabled` validation rule.

### 3.3 New Release Page

**File to create:** `app/Http/Controllers/ReleaseController.php`

> **Implementation note:** The existing `ConsultationSummary` model (table: `consultation_summaries`) is the
> release tracking record. It has `status` (pending/draft/released), `released_at`, and `released_by`.
> We reuse this model for the Release page — only the admin UI and routes change.
> Renaming the model/table to `ReleaseSummary` is a future refactor, not in scope here.

```php
class ReleaseController extends Controller
{
    public function index(): Response
    {
        // Load summaries that are in 'draft' or 'released' state (i.e., have been processed).
        $summaries = ConsultationSummary::with(['applicant.application', 'recommendedCourse'])
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
        if ($summary->status === 'released') {
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
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];

        $summaries = ConsultationSummary::whereIn('id', $ids)
            ->where('status', '!=', 'released')
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

        return back()->with('success', count($summaries) . ' results released.');
    }
}
```

**Routes to add in `web.php`:**

```php
Route::middleware('role:super_admin,test_administrator')->prefix('release')->name('release.')->group(function () {
    Route::get('/', [ReleaseController::class, 'index'])->name('index');
    Route::post('applicants/{applicant}/release', [ReleaseController::class, 'release'])->name('applicants.release');
    Route::post('applicants/bulk-release', [ReleaseController::class, 'releaseBulk'])->name('applicants.bulk-release');
});
```

**File to create:** `resources/js/Pages/Release/Index.svelte`

Page structure:
- Header: "Release Management" h1
- Info banner if `release_mode === 'f2f'`: "Results will be provided to applicants in person. Email delivery is disabled."
- Info banner if `release_mode === 'online'`: "Results will be sent to applicants via their portal and email."
- Table columns: Applicant name, Course, Session date, Result status, Released? (badge), Action button
- Bulk select checkboxes + "Release Selected" button at top
- Individual "Release" button per row (disabled if already released)
- "Release Selected" triggers POST to `release.applicants.bulk-release`
- Email option checkbox only rendered when `release_mode !== 'f2f'` (the controller always sends email when not f2f — the checkbox is informational/confirmatory)

Breadcrumbs: `[{ label: 'Release Management' }]`

**Sidebar update in `AuthenticatedLayout.svelte`:**

Replace old "Release & Consultation" item with:
```js
{ href: '/release', label: 'Release', icon: SendHorizonal, roles: ['super_admin', 'test_administrator'] }
```

No `featureFlag` — Release is always available.

**Notification to create:** `app/Notifications/ResultReleased.php`

```php
class ResultReleased extends Notification implements ShouldQueue
{
    // Accepts the ConsultationSummary (the release record).
    // The notifiable is the Applicant ($summary->applicant->notify(...)).
    public function __construct(public readonly ConsultationSummary $summary) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your exam results are now available')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Your exam results have been released and are now available in your portal.')
            ->action('View Results', url('/portal'))
            ->line('If you have any questions, please contact the guidance office.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'       => 'result_released',
            'summary_id' => $this->summary->id,
            'message'    => 'Your exam results are now available.',
        ];
    }
}
```

---

## 4. notify_on_publish — Full Implementation (R5)

### 4.1 SystemSetting + Request + Controller

`SystemSetting::notifyOnPublish()` returns `(bool) static::get('notify_on_publish', false)`.

**`UpdateSystemSettingsRequest`:** Add `'notify_on_publish' => 'sometimes|boolean'`.

**`SettingsController@index`:** Add `'notify_on_publish' => SystemSetting::notifyOnPublish()` to Inertia props.

**`SettingsController@update`:** Add handler:
```php
if (array_key_exists('notify_on_publish', $validated)) {
    SystemSetting::set('notify_on_publish', (bool) $validated['notify_on_publish']);
}
```

### 4.2 Settings UI

Add a new Card to `Settings/Index.svelte` (after the AI companion toggle):

```svelte
<Card>
  <CardHeader>
    <CardTitle class="flex items-center gap-2">
      <Bell class="h-5 w-5" />
      Exam schedule notifications
    </CardTitle>
    <CardDescription>
      When enabled, applicants receive an email when their exam session is published.
      Requires applicant email addresses to be set up.
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

Add `notify_on_publish` to `useForm()` initial state and `$form.transform()`.

### 4.3 Notification Class

**File to create:** `app/Notifications/ExamSessionPublished.php`

```php
class ExamSessionPublished extends Notification implements ShouldQueue
{
    public function __construct(public readonly ExamSession $session) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your exam has been scheduled')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Your exam session has been confirmed. Here are the details:')
            ->line('**Date:** ' . $this->session->scheduled_at->format('F j, Y'))
            ->line('**Time:** ' . $this->session->scheduled_at->format('g:i A'))
            ->line('**Room:** ' . ($this->session->room->name ?? 'TBA'))
            ->action('View in Portal', url('/portal'))
            ->line('Please arrive 15 minutes early with a valid ID.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'       => 'exam_session_published',
            'session_id' => $this->session->id,
            'message'    => 'Your exam has been scheduled for ' . $this->session->scheduled_at->format('F j, Y'),
        ];
    }
}
```

### 4.4 Dispatch on Publish

**`ExamSessionController@publish`:** After the session status is updated to published, add:

```php
if (SystemSetting::notifyOnPublish()) {
    $session->applicants->each(fn($applicant) =>
        $applicant->notify(new ExamSessionPublished($session))
    );
}
```

This runs through the queue (because `ShouldQueue` is implemented) — it will not slow down the publish HTTP response.

**Note for junior devs:** Make sure the queue worker is running (`php artisan queue:work`). In development, you can use `QUEUE_CONNECTION=sync` in `.env` to process notifications immediately without a worker.

---

## 5. release_mode — Setting + Conditional Effects (R6 + R7)

### 5.1 Settings UI — Two Toggles

Add a Card in `Settings/Index.svelte`:

```svelte
<Card>
  <CardHeader>
    <CardTitle class="flex items-center gap-2">
      <Share2 class="h-5 w-5" />
      Result release mode
    </CardTitle>
    <CardDescription>
      Controls how exam results are delivered to applicants.
      At least one mode must be enabled.
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
        <p class="text-xs text-muted-foreground">Results handed in person — portal view disabled</p>
      </div>
    </div>
    {#if !releaseOnline && !releasef2f}
      <p class="text-sm text-destructive">At least one release mode must be enabled.</p>
    {/if}
  </CardContent>
</Card>
```

**Svelte logic for the two toggles:**

```js
// Derived from release_mode prop
let releaseOnline = $state(release_mode === 'online' || release_mode === 'both');
let releasef2f = $state(release_mode === 'f2f' || release_mode === 'both');

// Compute enum value from toggles
function computeReleaseMode(online, f2f) {
  if (online && f2f) return 'both';
  if (online) return 'online';
  if (f2f) return 'f2f';
  return null; // invalid — blocked by validation
}

function handleReleaseOnlineChange(checked) {
  releaseOnline = checked;
  form.update(f => ({ ...f, release_mode: computeReleaseMode(checked, releasef2f) }));
}

function handleReleaseF2fChange(checked) {
  releasef2f = checked;
  form.update(f => ({ ...f, release_mode: computeReleaseMode(releaseOnline, checked) }));
}
```

Add `release_mode` to `useForm()`, `$form.transform()`, and the submit button's `disabled` condition (disable if `!releaseOnline && !releasef2f`).

### 5.2 Backend Handling

**`UpdateSystemSettingsRequest`:** Add `'release_mode' => 'sometimes|in:online,f2f,both'`.

**`SettingsController@index`:** Add `'release_mode' => SystemSetting::releaseMode()`.

**`SettingsController@update`:**
```php
if (array_key_exists('release_mode', $validated)) {
    SystemSetting::set('release_mode', $validated['release_mode']);
}
```

### 5.3 Effect: Admin Grading Bulk-Print Disabled When Online-Only

When `release_mode === 'online'`, bulk-print and individual result sheet print buttons in the Grading section are disabled.

**Files to update:**
- `resources/js/Pages/Grading/PrintBatch.svelte`
- `resources/js/Pages/Grading/ResultSheetBulk.svelte`
- `resources/js/Pages/Grading/ResultSheet.svelte` (print button)

In each file, read the shared prop:

```js
const page = usePage();
const releaseMode = $derived($page.props.release_mode ?? 'online');
const printDisabled = $derived(releaseMode === 'online');
```

Apply to print buttons:
```svelte
<Button
  disabled={printDisabled}
  title={printDisabled ? 'Printing is disabled in online release mode' : undefined}
>
  Print Results
</Button>
```

When `printDisabled` is true, show a small info note: "Switch to F2F or Both release mode in Settings to enable printing."

### 5.4 Effect: Applicant Portal Result View Blocked When F2F

When `release_mode === 'f2f'`, applicants must not see their results in the portal.

**Backend guard in `AiCompanionController@index` (applicant portal):**

Wait — the result display is likely in the portal dashboard or a specific results page. Check `Portal/Dashboard.svelte` and wherever result data is rendered.

The guard should be in the controller that serves the result data:

```php
// In any portal controller that returns result/grading data:
if (SystemSetting::releaseMode() === 'f2f') {
    // Pass flag instead of data — let frontend show the message
    return Inertia::render('Portal/Dashboard', [
        'results_available' => false,
        'results_blocked_reason' => 'f2f',
    ]);
}
```

**Frontend (`Portal/Dashboard.svelte` or the results section):**

```svelte
{#if !results_available && results_blocked_reason === 'f2f'}
  <div class="rounded-lg border border-border bg-muted/30 p-6 text-center">
    <p class="font-medium text-foreground">Results will be provided in person</p>
    <p class="mt-1 text-sm text-muted-foreground">
      Please visit the guidance office to receive your exam results.
    </p>
  </div>
{:else}
  <!-- normal result display -->
{/if}
```

**Note:** The specific portal page file(s) serving result data need to be identified at implementation time. The implementer should search for where grading/result data is passed to the portal and apply the guard there.

---

## 6. File Change Summary

### Files to Create (New)
| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/AiCompanionAdminController.php` | AI Companion admin hub (index + persona update) |
| `app/Http/Controllers/ReleaseController.php` | Release management (replaces Consultation) |
| `app/Notifications/ExamSessionPublished.php` | Notify applicants when session is published |
| `app/Notifications/ResultReleased.php` | Notify applicants when their result is released |
| `resources/js/Pages/Admin/AiCompanion/Index.svelte` | AI Companion admin hub UI (tabs) |
| `resources/js/Pages/Release/Index.svelte` | Release management UI |

### Files to Modify
| File | What Changes |
|------|-------------|
| `app/Models/SystemSetting.php` | Add `releaseMode()`, `notifyOnPublish()`. Remove `consultationEnabled()`. |
| `app/Http/Middleware/HandleInertiaRequests.php` | Add `ai_exam_companion_enabled`, `release_mode`. Remove `consultation_enabled`. |
| `app/Http/Requests/UpdateSystemSettingsRequest.php` | Add `notify_on_publish`, `release_mode`. Remove `consultation_enabled`. |
| `app/Http/Controllers/Admin/SettingsController.php` | Add new settings. Remove consultation. |
| `app/Http/Controllers/Admin/KnowledgeDocumentController.php` | Add 403 guard when AI companion disabled. |
| `app/Http/Controllers/Admin/ExamSessionController.php` | Dispatch `ExamSessionPublished` notification on publish. |
| `routes/web.php` | Add ai-companion, release routes. Remove consultation routes. Redirect old KD index. |
| `resources/js/Layouts/AuthenticatedLayout.svelte` | Update navSections: AI Companion item, Release item, remove Consultation. |
| `resources/js/Pages/Admin/Settings/Index.svelte` | Add notify_on_publish toggle, release_mode toggles. Remove consultation + persona. |
| `resources/js/Pages/Admin/Rooms/Index.svelte` | Update breadcrumbs. |
| `resources/js/Pages/Admin/Rooms/Create.svelte` | Update breadcrumbs. |
| `resources/js/Pages/Admin/Rooms/Edit.svelte` | Update breadcrumbs. |
| `resources/js/Pages/Admin/Courses/Index.svelte` | Update breadcrumbs. |
| `resources/js/Pages/Admin/Courses/Create.svelte` | Update breadcrumbs. |
| `resources/js/Pages/Admin/Courses/Edit.svelte` | Update breadcrumbs. |
| `resources/js/Pages/Admin/Seasons/Index.svelte` | Rename "Add Course" → "Manage Courses". |
| `resources/js/Pages/Admin/TestScheduling/Index.svelte` | Add "Manage Rooms" button. |
| `resources/js/Pages/Grading/PrintBatch.svelte` | Disable print when `release_mode === 'online'`. |
| `resources/js/Pages/Grading/ResultSheetBulk.svelte` | Disable print when `release_mode === 'online'`. |
| `resources/js/Pages/Grading/ResultSheet.svelte` | Disable print when `release_mode === 'online'`. |
| Portal result page(s) | Block result view when `release_mode === 'f2f'`. |

### Files to Delete
| File | Reason |
|------|--------|
| `resources/js/Pages/Consultation/Dashboard.svelte` | R4 — Consultation removed |
| `resources/js/Pages/Consultation/ScheduleDay.svelte` | R4 |
| `resources/js/Pages/Consultation/ApplicantView.svelte` | R4 |
| `resources/js/Pages/Consultation/ConsultationDay.svelte` | R4 |
| `app/Http/Controllers/Consultation/ConsultationController.php` | R4 |
| `app/Http/Controllers/Consultation/ConsultationApplicantController.php` | R4 |
| `app/Http/Controllers/Consultation/ConsultationDayController.php` | R4 |
| `app/Http/Controllers/Consultation/ConsultationScheduleController.php` | R4 |
| `app/Http/Middleware/EnsureConsultationEnabled.php` | R4 |

---

## 7. Implementation Order (Recommended for a Junior Dev)

The requirements have a dependency order. Do them in this sequence to avoid breaking the app mid-way:

1. **Shared props first** — Update `HandleInertiaRequests` and `SystemSetting`. This is foundational.
2. **R1 + R2** — Breadcrumbs and access points. Pure frontend, safe, isolated.
3. **R4 (Delete)** — Remove Consultation. Do the clean sweep before adding Release.
4. **R4 (Release page)** — Add the new Release controller + page + routes + sidebar update.
5. **R8 (AI Companion hub)** — New controller + page + redirect + sidebar rename.
6. **R3 (Route guard)** — Add the 403 guards to AiCompanionAdminController + KnowledgeDocumentController.
7. **R5 (notify_on_publish)** — Settings toggle + notification class + publish dispatch.
8. **R6 + R7 (release_mode)** — Settings UI + backend + Grading print conditional + portal block.

Test after each step. Each step is independently deployable.

---

## 8. Edge Cases and Notes

- **Consultation data in DB:** Any existing consultation/grading release records are preserved — we are only removing the UI and routes, not dropping tables or columns. The release mechanism reuses whatever column marks a result as "released".
- **Queue requirement:** R5's notifications use `ShouldQueue`. A queue worker must be running in production. In development, set `QUEUE_CONNECTION=sync` in `.env`.
- **Validation — release_mode:** The form submit button should be disabled and/or the form should show an error if both release toggles are off. The backend also validates `in:online,f2f,both` as a safety net.
- **KnowledgeDocuments index redirect:** When the user navigates to `/admin/knowledge-documents`, they get redirected to `/admin/ai-companion`. This is a 302 redirect — the CRUD sub-pages (`/admin/knowledge-documents/create`, etc.) are NOT redirected.
- **`Bot` icon import:** `Bot` is already imported in `AuthenticatedLayout.svelte` via lucide-svelte (check the import line — add it if missing). Remove `BookOpen` from the import if it's no longer used after R8.
- **Portal result block scope:** The exact portal page(s) that serve result data must be confirmed at implementation time. The implementer should search for `grading` or `result` in the `Portal/` pages directory to locate all render points.
