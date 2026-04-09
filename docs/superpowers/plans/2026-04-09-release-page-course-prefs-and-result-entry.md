# Plan: Release Page — Course Preferences + Result Entry

## Overview

Add applicant course preferences to the Release page table and enable counselors to enter the recommended course + comments directly from the Release page (no separate consultation page needed).

---

## Context

- `ConsultationSummary` stores: `applicant_id`, `status`, `recommended_course_id`, `counselor_comments`
- Applicant course preferences live on `Application` model: `course_preference_1/2/3` (FKs to `courses`)
- `Applicant` belongsTo `Application` (`applicant.application.coursePreference1/2/3`)
- `release_mode` on `SystemSetting`: `online` | `f2f` | `both`

### Business Rules
- **Online mode**: `recommended_course_id` + `counselor_comments` **required** before release
- **F2F mode**: those fields are **optional**
- `ConsultationSummary` records are auto-created (via `ConsultationSummaryService::getOrCreateForApplicant`) when needed, or created on demand

---

## Files to Modify

| File | Change |
|------|--------|
| `app/Http/Controllers/ReleaseController.php` | Add `storeOrUpdate()` action + eager-load application+preferences |
| `app/Http/Requests/UpdateConsultationSummaryRequest.php` | Exists already — validate conditionally based on release_mode |
| `routes/web.php` | Add PUT route for `/release/summaries/{summary}` |
| `resources/js/Pages/Release/Index.svelte` | Add course-preferences column + edit side panel |
| `app/Models/ConsultationSummary.php` | No changes needed |

---

## Step 1 — Backend: ReleaseController load course preferences

**File:** `app/Http/Controllers/ReleaseController.php`

In `index()`, update the eager-load to include `applicant.application`:

```php
$summaries = ConsultationSummary::with([
    'applicant',
    'applicant.application.coursePreference1:id,name,code',
    'applicant.application.coursePreference2:id,name,code',
    'applicant.application.coursePreference3:id,name,code',
    'recommendedCourse',
])
```

The `Index.svelte` table already passes `$page.props.summaries` — no controller schema change needed, just the eager-load.

---

## Step 2 — ReleaseController: Add storeOrUpdate action

**File:** `app/Http/Controllers/ReleaseController.php`

Add a new method:

```php
public function storeOrUpdate(Request $request, ConsultationSummary $summary): RedirectResponse
{
    $releaseMode = SystemSetting::releaseMode();

    $rules = [
        'recommended_course_id' => ['nullable', 'integer', 'exists:courses,id'],
        'counselor_comments'    => ['nullable', 'string', 'max:5000'],
    ];

    if ($releaseMode === 'online') {
        $rules['recommended_course_id'] = ['required', 'integer', 'exists:courses,id'];
        $rules['counselor_comments']    = ['required', 'string', 'max:5000'];
    }

    $validated = $request->validate($rules);

    $summary->update([
        'recommended_course_id' => $validated['recommended_course_id'] ?? null,
        'counselor_comments'    => $validated['counselor_comments'] ?? null,
    ]);

    return back()->with('success', 'Summary updated.');
}
```

---

## Step 3 — Add PUT route

**File:** `routes/web.php`

In the release section:

```php
Route::put('/release/summaries/{summary}', [ReleaseController::class, 'storeOrUpdate'])
    ->name('release.summaries.storeOrUpdate')
    ->middleware('role:super_admin,admin,counselor');
```

---

## Step 4 — ReleaseController: Pass courses list to frontend

**File:** `app/Http/Controllers/ReleaseController.php`

In `index()`, also load active courses:

```php
$courses = Course::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
```

Return `courses` in the Inertia render:

```php
return Inertia::render('Release/Index', [
    'summaries'    => $summaries,
    'release_mode' => SystemSetting::releaseMode(),
    'courses'      => $courses,        // NEW
]);
```

---

## Step 5 — Frontend: Add course-preferences column to table

**File:** `resources/js/Pages/Release/Index.svelte`

In `Table.Body`, after the "Recommended Course" cell, add a new column:

```svelte
<Table.Cell class="px-4 py-3">
  {@const prefs = summary.applicant?.application ? [
    summary.applicant.application.coursePreference1,
    summary.applicant.application.coursePreference2,
    summary.applicant.application.coursePreference3,
  ].filter(Boolean) : []}
  {#if prefs.length}
    <div class="text-xs space-y-0.5">
      {#each prefs as pref, i}
        <span class="font-medium">{i + 1}.</span> {pref.name}
      {/each}
    </div>
  {:else}
    <span class="text-muted-foreground">—</span>
  {/if}
</Table.Cell>
```

Update the table header to include the new column:

```svelte
<Table.Head class="px-4 py-3">Course Preferences</Table.Head>
```

Add `"Course Preferences"` to the colspan if showing empty state:

```svelte
<Table.Cell colspan={6} ...>  <!-- was 5 -->
```

---

## Step 6 — Frontend: Add edit side panel

**File:** `resources/js/Pages/Release/Index.svelte`

Add state for the selected summary and whether the panel is open:

```svelte
let selectedSummary = $state(null);
let showPanel = $state(false);
let saving = $state(false);
let panelErrors = $state('');

// Recommended course and comments from form
let recCourseId = $state('');
let counselorComments = $state('');

function openPanel(summary) {
    selectedSummary = summary;
    recCourseId = summary.recommended_course?.id ?? '';
    counselorComments = summary.counselor_comments ?? '';
    showPanel = true;
}

function closePanel() {
    showPanel = false;
    selectedSummary = null;
}

function saveSummary() {
    saving = true;
    panelErrors = '';
    router.put(`/release/summaries/${selectedSummary.id}`, {
        recommended_course_id: recCourseId || null,
        counselor_comments: counselorComments || null,
    }, {
        preserveScroll: true,
        onError: (err) => {
            panelErrors = Object.values(err).flat().join(', ');
            saving = false;
        },
        onSuccess: () => {
            saving = false;
            closePanel();
        },
    });
}
```

Add a "Edit" button in the table Action cell (for unreleased summaries):

```svelte
<Button size="sm" variant="outline" onclick={() => openPanel(summary)}>
  Edit
</Button>
```

Add the side panel HTML after the table div:

```svelte
{#if showPanel && selectedSummary}
<div class="fixed inset-y-0 right-0 w-80 bg-background border-l border-border shadow-lg z-50 p-6 space-y-4 overflow-y-auto">
    <div class="flex items-center justify-between">
        <h3 class="font-semibold">Edit Result — {selectedSummary.applicant?.name}</h3>
        <button onclick={closePanel} class="text-muted-foreground hover:text-foreground text-2xl leading-none">&times;</button>
    </div>

    {#if panelErrors}
        <p class="text-sm text-destructive">{panelErrors}</p>
    {/if}

    <div class="space-y-4">
        <!-- Course Preferences (read-only) -->
        <div>
            <p class="text-xs font-medium text-muted-foreground mb-1">Applicant's Choices</p>
            {@const prefs = [
                selectedSummary.applicant?.application?.coursePreference1,
                selectedSummary.applicant?.application?.coursePreference2,
                selectedSummary.applicant?.application?.coursePreference3,
            ].filter(Boolean)}
            {#each prefs as pref, i}
                <p class="text-sm">{i + 1}. {pref.name}</p>
            {/each}
        </div>

        <!-- Recommended Course (dropdown) -->
        <div class="space-y-1.5">
            <label for="rec-course" class="text-sm font-medium">
                Recommended Course
                {#if release_mode === 'online'}
                    <span class="text-destructive">*</span>
                {:else}
                    <span class="text-xs text-muted-foreground">(optional for F2F)</span>
                {/if}
            </label>
            <select
                id="rec-course"
                bind:value={recCourseId}
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
                <option value="">— Select course —</option>
                {#each courses as course}
                    <option value={course.id}>{course.name} ({course.code})</option>
                {/each}
            </select>
        </div>

        <!-- Counselor Comments -->
        <div class="space-y-1.5">
            <label for="counselor-comments" class="text-sm font-medium">
                Counselor Comments
                {#if release_mode === 'online'}
                    <span class="text-destructive">*</span>
                {:else}
                    <span class="text-xs text-muted-foreground">(optional for F2F)</span>
                {/if}
            </label>
            <textarea
                id="counselor-comments"
                bind:value={counselorComments}
                rows="4"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
                placeholder="Enter comments or notes for the applicant..."
            ></textarea>
        </div>
    </div>

    <div class="flex gap-2 pt-4 border-t border-border">
        <Button onclick={saveSummary} disabled={saving} class="min-h-[44px] flex-1">
            {saving ? 'Saving…' : 'Save'}
        </Button>
        <Button variant="outline" onclick={closePanel} class="min-h-[44px]">Cancel</Button>
    </div>
</div>
{/if}
```

Add CSS for the overlay backdrop:

```svelte
{#if showPanel}
<div class="fixed inset-0 bg-black/20 z-40" onclick={closePanel}></div>
{/if}
```

---

## Step 7 — Backend: Handle "create summary if not exists"

The `storeOrUpdate` action above assumes the `ConsultationSummary` record already exists. For applicants who have **no summary record yet**, add a `getOrCreate` endpoint or merge logic.

**Option A — Merge in service:** Update `storeOrUpdate` to first ensure the record exists using `ConsultationSummaryService::getOrCreateForApplicant`, then update it.

In `ReleaseController::storeOrUpdate`:

```php
use App\Services\ConsultationSummaryService;

// Inside method:
$summary = app(ConsultationSummaryService::class)->getOrCreateForApplicant($summary->applicant_id);
```

**Option B — Auto-create on first edit:** The `ConsultationSummaryService::getOrCreateForApplicant` is already used elsewhere. We can reuse it here by creating a summary with status `draft` on demand when the user first opens the edit panel (via a separate GET endpoint that returns existing or creates).

**Recommended: Option A** — simplest, single PUT handles both create and update.

Update `storeOrUpdate`:

```php
public function storeOrUpdate(Request $request, ConsultationSummary $summary): RedirectResponse
{
    $releaseMode = SystemSetting::releaseMode();

    // If summary is actually a new applicant (no summary exists), create it first
    $existing = ConsultationSummary::where('applicant_id', $summary->applicant_id)->first();
    if (!$existing) {
        $existing = app(ConsultationSummaryService::class)->getOrCreateForApplicant($summary->applicant_id);
    }

    $rules = [...same as above...];
    $validated = $request->validate($rules);

    $existing->update([...]);
    return back()->with('success', 'Summary updated.');
}
```

Wait — route model binding on `$summary` means if the summary doesn't exist, we get a 404. We need a different route structure.

**Revised approach: use applicant_id instead of summary id**

Change the route to `/release/summaries/by-applicant/{applicantId}`:

```php
Route::put('/release/summaries/by-applicant/{applicantId}', [ReleaseController::class, 'storeOrUpdateByApplicant'])
    ->name('release.summaries.storeOrUpdate')
    ->middleware('role:super_admin,admin,counselor');
```

In `storeOrUpdateByApplicant`:

```php
public function storeOrUpdateByApplicant(Request $request, Applicant $applicant): RedirectResponse
{
    $summary = app(ConsultationSummaryService::class)->getOrCreateForApplicant($applicant->id);
    // then validate + update as above
}
```

On the frontend, pass `summary.applicant.id` instead of `summary.id` for the PUT URL.

---

## Validation Summary

| Field | F2F | Online |
|-------|-----|--------|
| `recommended_course_id` | nullable | **required** |
| `counselor_comments` | nullable | **required** |

---

## Testing Checklist

- [ ] Go to `/release` — table shows no course-prefs column yet
- [ ] After Step 5: table shows "1. BSIT 2. BSCS 3. —" for each applicant
- [ ] Click "Edit" on any row — side panel opens with applicant's 3 choices shown read-only
- [ ] Select a recommended course + add comments → Save → panel closes, success flash
- [ ] Open panel again → fields pre-filled with saved values
- [ ] Switch `release_mode` to `online` → try saving without recommended_course or comments → validation error shown
- [ ] Switch `release_mode` to `f2f` → saving with empty fields works fine
- [ ] Release a result → status changes to "released", Release button disappears

---

## File Summary

| Action | File |
|--------|------|
| Modify | `app/Http/Controllers/ReleaseController.php` |
| Modify | `routes/web.php` |
| Modify | `resources/js/Pages/Release/Index.svelte` |

No new files needed.
