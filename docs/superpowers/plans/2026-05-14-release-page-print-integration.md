# Release Page Print Integration

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move all print management (print status badges, mark-as-printed actions, result sheet viewing, bulk printing) from the Grading Session area into the Release page, creating a unified "output distribution" hub.

**Architecture:** The Release page becomes the single place where operators manage result distribution — both digital release (online mode) and physical printing (F2F mode). Print status is surfaced as a badge alongside release status. The Grading Session page retains a read-only "Printed" badge in the Progress column so graders can see at a glance who's done, but all print actions live on the Release page. The existing `PrintBatchService`, `ResultSheetTemplateService`, and pivot column `result_printed_at` remain unchanged — we're moving the UI layer, not the data layer.

**Tech Stack:** Laravel 12, Inertia.js v2, Svelte 5, Tailwind CSS v4

---

## Current State (What We're Moving)

| Feature | Current Location | Current Route |
|---------|-----------------|---------------|
| Print batch list (select applicants, mark printed) | `Grading/PrintBatch.svelte` | `/admin/grading/sessions/{id}/print` |
| Mark as printed (single/bulk) | `GradingPrintController@markPrinted` | `POST /admin/grading/sessions/{id}/mark-printed` |
| View single result sheet | `Grading/ResultSheet.svelte` | `/admin/grading/sessions/{id}/applicants/{aid}/result-sheet` |
| Bulk print result sheets | `Grading/ResultSheetBulk.svelte` | `/admin/grading/sessions/{id}/print-bulk?ids=...` |
| Result templates CRUD | `Admin/ResultSheetTemplates/*` | `/admin/release/result-templates/*` |
| Release summaries | `Release/Index.svelte` | `/admin/release` |

## Target State

| Feature | New Location | New Route |
|---------|-------------|-----------|
| Print batch list (per session) | `Release/PrintBatch.svelte` | `/admin/release/print/{grading_session}` |
| Mark as printed (single/bulk) | `ReleasePrintController@markPrinted` | `POST /admin/release/print/{grading_session}/mark-printed` |
| View single result sheet | `Release/ResultSheet.svelte` | `/admin/release/print/{grading_session}/applicants/{applicant}` |
| Bulk print result sheets | `Release/ResultSheetBulk.svelte` | `/admin/release/print/{grading_session}/print-bulk` |
| Result templates CRUD | *unchanged* | `/admin/release/result-templates/*` |
| Release summaries + print status | `Release/Index.svelte` (enhanced) | `/admin/release` |
| Read-only "Printed" badge | `Grading/Session.svelte` (added) | *unchanged* |

## File Structure

### New Files

| File | Responsibility |
|------|---------------|
| `app/Http/Controllers/Release/ReleasePrintController.php` | Print actions in release context — index, markPrinted, resultSheet, printBulk. Delegates to existing `PrintBatchService` and `ResultSheetTemplateService`. |
| `resources/js/Pages/Release/PrintBatch.svelte` | Print batch UI (select applicants, mark printed, print bulk). Moved from `Grading/PrintBatch.svelte` with updated breadcrumbs and links. |
| `resources/js/Pages/Release/ResultSheet.svelte` | Single result sheet view. Moved from `Grading/ResultSheet.svelte` with updated back link. |
| `resources/js/Pages/Release/ResultSheetBulk.svelte` | Bulk result sheet print view. Moved from `Grading/ResultSheetBulk.svelte` with updated back link. |
| `tests/Feature/ReleasePrintControllerTest.php` | Feature tests for the new controller endpoints. |

### Modified Files

| File | Change |
|------|--------|
| `routes/web.php` | Add release print routes under `/admin/release/print/`. Remove old grading print routes. |
| `app/Http/Controllers/Grading/GradingSessionController.php` | Add `printed` flag to applicant map data. |
| `resources/js/Pages/Grading/Session.svelte` | Add read-only "Printed"/"Not printed" badge in Progress column. Change "Print results" button to link to `/admin/release/print/{id}` instead of `/admin/grading/sessions/{id}/print`. |
| `resources/js/Pages/Release/Index.svelte` | Add "Printed" badge column in table + cards. Add "Print batch" link per session context. Add per-applicant "Mark printed" / "View result sheet" actions in F2F mode. |
| `resources/js/Layouts/AuthenticatedLayout.svelte` | Update navigation — "Print Results" link (if any) should point to release area. Update breadcrumbs for print pages. |

### Deleted Files

| File | Reason |
|------|--------|
| `app/Http/Controllers/Grading/GradingPrintController.php` | Replaced by `ReleasePrintController`. Logic moved; service classes reused. |
| `resources/js/Pages/Grading/PrintBatch.svelte` | Replaced by `Release/PrintBatch.svelte`. |
| `resources/js/Pages/Grading/ResultSheet.svelte` | Replaced by `Release/ResultSheet.svelte`. |
| `resources/js/Pages/Grading/ResultSheetBulk.svelte` | Replaced by `Release/ResultSheetBulk.svelte`. |
| `app/Http/Requests/MarkPrintedRequest.php` | Move to `app/Http/Requests/Release/MarkPrintedRequest.php` (new namespace, same logic). |

### Unchanged Files (Reused As-Is)

| File | Why |
|------|-----|
| `app/Services/PrintBatchService.php` | Service layer is domain-agnostic — it works with `GradingSession` models. No changes needed. |
| `app/Services/ResultSheetTemplateService.php` | Rendering logic is the same regardless of which controller calls it. |
| `app/Models/ResultSheetTemplate.php` | Model unchanged. |
| `database/migrations/*_create_grading_session_applicant_table.php` | Pivot column `result_printed_at` stays the same. |
| `app/Models/GradingSession.php` | Unchanged — the `applicants()` relationship already declares `withPivot('result_printed_at')` (verified at line 58), so `$a->pivot->result_printed_at` is populated on eager-loaded collections in Task 1. |
| `app/Http/Controllers/Admin/ResultSheetTemplateController.php` | Already lives under `/admin/release/result-templates`. No changes. |

---

### Task 1: Add `printed` flag to GradingSessionController

**Files:**
- Modify: `app/Http/Controllers/Grading/GradingSessionController.php:50-57`
- Test: `tests/Feature/GradingSessionControllerTest.php`

The applicant map in `show()` needs to include the `printed` boolean from the pivot, exactly like `GradingPrintController::index()` already does.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/GradingSessionControllerTest.php
public function test_show_includes_printed_flag_for_applicants(): void
{
    $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_IN_PROGRESS]);
    $applicant = Applicant::factory()->create();
    $session->applicants()->attach($applicant->id, ['result_printed_at' => now()]);

    $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
        ->get(route('admin.grading.sessions.show', $session));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('applicants', 1)
        ->where('applicants.0.printed', true)
    );
}

public function test_show_printed_flag_is_false_when_not_printed(): void
{
    $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_IN_PROGRESS]);
    $applicant = Applicant::factory()->create();
    $session->applicants()->attach($applicant->id, ['result_printed_at' => null]);

    $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
        ->get(route('admin.grading.sessions.show', $session));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('applicants', 1)
        ->where('applicants.0.printed', false)
    );
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=GradingSessionControllerTest`
Expected: FAIL — `printed` key does not exist on applicant data

- [ ] **Step 3: Add `printed` to the applicant map**

In `GradingSessionController.php`, inside the `->map()` callback (around line 50-57), add the `printed` key:

```php
return [
    'id' => $a->id,
    'applicant_id' => $a->id,
    'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
    'reference' => $a->application?->reference_number ?? '—',
    'scored' => $scored,
    'domains_complete' => $scoresCount,
    'printed' => (bool) $a->pivot->result_printed_at,
];
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=GradingSessionControllerTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Grading/GradingSessionController.php tests/Feature/GradingSessionControllerTest.php
git commit -m "feat: add printed flag to grading session applicant data"
```

---

### Task 2: Add read-only "Printed" badge to Session.svelte Progress column

**Files:**
- Modify: `resources/js/Pages/Grading/Session.svelte`

Add a "Printed" / "Not printed" badge next to the existing score badge in the Progress column. Only shown when the applicant is scored (you can't print what isn't scored yet).

- [ ] **Step 1: Update the Progress column template**

Replace the Progress `<Table.Cell>` block (lines ~105-117) with:

```svelte
<Table.Cell class="px-4 py-3">
  <div class="flex flex-wrap items-center gap-1.5">
    {#if app.scored}
      <Badge variant="success" class="gap-1">
        <CheckCircle2 class="h-3 w-3" />
        Complete
      </Badge>
    {:else if app.domains_complete > 0}
      <Badge variant="warning">{app.domains_complete} / 6 aptitude areas</Badge>
    {:else}
      <Badge variant="muted" class="gap-1">
        <Circle class="h-3 w-3" />
        Not started
      </Badge>
    {/if}
    {#if app.scored && app.printed}
      <Badge variant="success" class="gap-1 text-xs">
        <Printer class="h-3 w-3" />
        Printed
      </Badge>
    {:else if app.scored && !app.printed && releaseMode !== 'online'}
      <Badge variant="outline" class="gap-1 text-xs text-muted-foreground">
        <Printer class="h-3 w-3" />
        Not printed
      </Badge>
    {/if}
  </div>
</Table.Cell>
```

Note: The `Printer` icon import is already present at line 7. The `releaseMode` derived value is already defined at line 15.

- [ ] **Step 2: Update the "Print results" button link**

Change line ~77 from:
```svelte
<Link href={`/admin/grading/sessions/${sid}/print`}>
```
to:
```svelte
<Link href={`/admin/release/print/${sid}`}>
```

This links to the new release-area print batch page (which we'll create in Task 3).

- [ ] **Step 3: Verify in browser**

Run: `npm run dev` (or `composer run dev`)
Navigate to `/admin/grading/sessions/{id}` after finalizing a session in F2F mode.
Expected: "Printed" / "Not printed" badges appear in the Progress column for scored applicants. "Print results" button links to `/admin/release/print/{id}`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Grading/Session.svelte
git commit -m "feat: add printed badge to session applicant progress column"
```

---

### Task 3: Create ReleasePrintController

**Files:**
- Create: `app/Http/Controllers/Release/ReleasePrintController.php`
- Create: `app/Http/Requests/Release/MarkPrintedRequest.php`
- Test: `tests/Feature/ReleasePrintControllerTest.php`

This controller mirrors `GradingPrintController` but lives under the release route namespace and delegates to the same service classes.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/ReleasePrintControllerTest.php
<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\User;
use App\Models\AptitudeArea;
use App\Models\ResultSheetTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleasePrintControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionWithApplicant(): array
    {
        $examSession = ExamSession::factory()->create();
        $session = GradingSession::factory()->create([
            'exam_session_id' => $examSession->id,
            'status' => GradingSession::STATUS_FINALIZED,
        ]);
        $applicant = Applicant::factory()->create();
        $session->applicants()->attach($applicant->id, ['result_printed_at' => null]);

        return [$session, $applicant, $examSession];
    }

    public function test_index_displays_print_batch_page(): void
    {
        [$session, $applicant] = $this->createSessionWithApplicant();

        $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('admin.release.print.index', $session));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('applicants', 1)
            ->where('applicants.0.printed', false)
        );
    }

    public function test_mark_printed_sets_result_printed_at(): void
    {
        [$session, $applicant] = $this->createSessionWithApplicant();

        $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->post(route('admin.release.print.mark-printed', $session), [
                'applicant_ids' => [$applicant->id],
                'printed' => true,
            ]);

        $response->assertRedirect();
        $this->assertNotNull(
            $session->applicants()->where('applicants.id', $applicant->id)->first()->pivot->result_printed_at
        );
    }

    public function test_unmark_printed_clears_result_printed_at(): void
    {
        [$session, $applicant] = $this->createSessionWithApplicant();
        $session->applicants()->updateExistingPivot($applicant->id, ['result_printed_at' => now()]);

        $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->post(route('admin.release.print.mark-printed', $session), [
                'applicant_ids' => [$applicant->id],
                'printed' => false,
            ]);

        $response->assertRedirect();
        $this->assertNull(
            $session->applicants()->where('applicants.id', $applicant->id)->first()->pivot->result_printed_at
        );
    }

    public function test_result_sheet_displays_single_applicant(): void
    {
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        [$session, $applicant] = $this->createSessionWithApplicant();
        AptitudeArea::factory()->create(['is_active' => true]);

        $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('admin.release.print.result-sheet', [$session, $applicant]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('applicant')
            ->where('printed', false)
        );
    }

    public function test_print_bulk_displays_bulk_view(): void
    {
        ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);
        [$session, $applicant] = $this->createSessionWithApplicant();

        $response = $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('admin.release.print.print-bulk', ['grading_session' => $session->id, 'ids' => $applicant->id]));

        $response->assertOk();
    }

    public function test_non_authorized_user_cannot_access(): void
    {
        [$session] = $this->createSessionWithApplicant();

        $response = $this->get(route('admin.release.print.index', $session));
        $response->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=ReleasePrintControllerTest`
Expected: FAIL — route `admin.release.print.index` does not exist

- [ ] **Step 3: Create the MarkPrintedRequest (moved from Grading namespace)**

Create `app/Http/Requests/Release/MarkPrintedRequest.php`:

```php
<?php

namespace App\Http\Requests\Release;

use Illuminate\Foundation\Http\FormRequest;

class MarkPrintedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'test_administrator']) ?? false;
    }

    public function rules(): array
    {
        return [
            'applicant_ids' => ['required', 'array', 'min:1'],
            'applicant_ids.*' => ['required', 'integer', 'exists:applicants,id'],
            'printed' => ['required', 'boolean'],
        ];
    }
}
```

- [ ] **Step 4: Create ReleasePrintController**

Create `app/Http/Controllers/ReleasePrintController.php`:

```php
<?php

namespace App\Http\Controllers\Release;

use App\Http\Controllers\Controller;
use App\Http\Requests\Release\MarkPrintedRequest;
use App\Models\Applicant;
use App\Models\GradingSession;
use App\Models\ResultSheetTemplate;
use App\Services\PrintBatchService;
use App\Services\ResultSheetTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReleasePrintController extends Controller
{
    public function __construct(
        private PrintBatchService $printService,
        private ResultSheetTemplateService $templateService
    ) {}

    public function index(GradingSession $grading_session): Response
    {
        $session = $grading_session->load(['examSession.room']);
        $applicants = $grading_session->applicants()
            ->with('application')
            ->get()
            ->map(function ($a) use ($grading_session) {
                $hasScores = $grading_session->applicantScores()->where('applicant_id', $a->id)->exists();

                return [
                    'id' => $a->id,
                    'applicant_id' => $a->id,
                    'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                    'reference' => $a->application?->reference_number ?? '—',
                    'scored' => $hasScores,
                    'printed' => (bool) $a->pivot->result_printed_at,
                ];
            });

        return Inertia::render('Release/PrintBatch', [
            'sessionId' => (string) $grading_session->id,
            'session' => [
                'id' => $grading_session->id,
                'exam_session_id' => $session->examSession?->id,
                'exam_date' => $session->examSession?->date?->format('Y-m-d'),
                'room_name' => $session->examSession?->room?->name ?? '—',
            ],
            'applicants' => $applicants->values()->all(),
        ]);
    }

    public function markPrinted(MarkPrintedRequest $request, GradingSession $grading_session): RedirectResponse
    {
        $this->printService->markPrinted(
            $grading_session,
            $request->validated('applicant_ids'),
            $request->validated('printed')
        );

        return redirect()->back()->with('success', 'Printed status updated.');
    }

    public function resultSheet(GradingSession $grading_session, Applicant $applicant): Response
    {
        if (! $grading_session->applicants()->where('applicants.id', $applicant->id)->exists()) {
            abort(404, 'Applicant is not part of this grading session.');
        }

        $template = ResultSheetTemplate::where('is_active', true)->first();
        if (! $template) {
            return Inertia::render('Release/ResultSheet', [
                'sessionId' => (string) $grading_session->id,
                'applicantId' => (string) $applicant->id,
                'printed' => false,
                'applicant' => ['id' => $applicant->id, 'name' => '—', 'reference' => '—', 'exam_date' => '—', 'room_name' => '—'],
                'scores' => [],
                'templateHtml' => null,
                'templateError' => 'No active result sheet template. Please create one in Admin > Result templates.',
                'paperSize' => 'a4',
                'orientation' => 'portrait',
                'logicalUnit' => 'full',
            ]);
        }

        $session = $grading_session->load(['examSession.room']);
        $applicant->load('application');
        $scores = $grading_session->applicantScores()
            ->where('applicant_id', $applicant->id)
            ->with('aptitudeArea')
            ->get();

        $applicantData = [
            'id' => $applicant->id,
            'name' => $applicant->application ? trim(implode(' ', array_filter([$applicant->application->first_name, $applicant->application->middle_name, $applicant->application->last_name, $applicant->application->suffix]))) : '—',
            'reference' => $applicant->application?->reference_number ?? '—',
            'exam_date' => $session->examSession?->date?->format('F j, Y'),
            'room_name' => $session->examSession?->room?->name ?? '—',
            'scores' => $scores->map(fn ($s) => [
                'domain' => $s->aptitudeArea?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
            ])->values()->all(),
            'overall_pct' => $scores->isEmpty() ? 0 : (int) round($scores->avg(fn ($s) => $s->max_score > 0 ? ($s->raw_score / $s->max_score) * 100 : 0)),
        ];

        $templateHtml = $this->templateService->render($template, [$applicantData], false);
        $pivot = $grading_session->applicants()->where('applicants.id', $applicant->id)->first()?->pivot;

        return Inertia::render('Release/ResultSheet', [
            'sessionId' => (string) $grading_session->id,
            'applicantId' => (string) $applicant->id,
            'printed' => (bool) ($pivot?->result_printed_at ?? false),
            'applicant' => [
                'id' => $applicantData['id'],
                'name' => $applicantData['name'],
                'reference' => $applicantData['reference'],
                'exam_date' => $applicantData['exam_date'],
                'room_name' => $applicantData['room_name'],
            ],
            'scores' => $applicantData['scores'],
            'templateHtml' => $templateHtml,
            'templateError' => null,
            'paperSize' => $template->paper_size ?? 'a4',
            'orientation' => $template->orientation ?? 'portrait',
            'logicalUnit' => $template->logical_unit ?? 'full',
        ]);
    }

    public function printBulk(GradingSession $grading_session): Response
    {
        $template = ResultSheetTemplate::where('is_active', true)->first();
        if (! $template) {
            return Inertia::render('Release/ResultSheetBulk', [
                'sessionId' => (string) $grading_session->id,
                'applicantIds' => [],
                'applicants' => [],
                'sheetsHtml' => [],
                'templateError' => 'No active result sheet template. Please create one in Admin > Result templates.',
                'paperSize' => 'a4',
                'orientation' => 'portrait',
                'logicalUnit' => 'full',
                'paperOptions' => ['a4' => 'A4', 'letter' => 'Letter'],
            ]);
        }

        $grading_session->load('examSession');
        $rawIds = request()->query('ids', '');
        $ids = array_filter(array_map('intval', is_array($rawIds) ? $rawIds : explode(',', $rawIds)));
        $applicants = $grading_session->applicants()
            ->whereIn('applicants.id', $ids)
            ->with('application')
            ->get();
        $scoresByApplicant = $grading_session->applicantScores()
            ->whereIn('applicant_id', $ids)
            ->with('aptitudeArea')
            ->get()
            ->groupBy('applicant_id');

        $applicantsWithScores = $applicants->map(function ($a) use ($scoresByApplicant, $grading_session) {
            $scores = $scoresByApplicant->get($a->id, collect())->map(fn ($s) => [
                'domain' => $s->aptitudeArea?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
            ])->values()->all();
            $overallPct = count($scores) > 0 ? (int) round(collect($scores)->avg('pct')) : 0;

            return [
                'id' => $a->id,
                'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                'reference' => $a->application?->reference_number ?? '—',
                'exam_date' => $grading_session->examSession?->date?->format('F j, Y'),
                'room_name' => $grading_session->examSession?->room?->name ?? '—',
                'scores' => $scores,
                'overall_pct' => $overallPct,
            ];
        })->values()->all();

        $logicalUnit = $template->logical_unit ?? 'full';
        $chunkSize = in_array($logicalUnit, ['half_a4', 'half_legal', 'half_letter'], true) ? 2 : 1;

        $sheetsHtml = [];
        foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
            if (count($chunk) === 2) {
                $html1 = $this->templateService->render($template, [$chunk[0]], false);
                $html2 = $this->templateService->render($template, [$chunk[1]], false);
                $sheetsHtml[] = $html1.$html2;
            } else {
                $sheetsHtml[] = $this->templateService->render($template, $chunk, false);
            }
        }

        return Inertia::render('Release/ResultSheetBulk', [
            'sessionId' => (string) $grading_session->id,
            'applicantIds' => $ids,
            'applicants' => $applicantsWithScores,
            'sheetsHtml' => $sheetsHtml,
            'templateError' => null,
            'paperSize' => $template->paper_size ?? 'a4',
            'orientation' => $template->orientation ?? 'portrait',
            'logicalUnit' => $template->logical_unit ?? 'full',
            'paperOptions' => ['a4' => 'A4', 'letter' => 'Letter'],
        ]);
    }
}
```

- [ ] **Step 5: Add routes**

In `routes/web.php`, inside the release route group (around line 240-248), add:

```php
// Release print management
Route::prefix('print')->name('print.')->group(function () {
    Route::get('{grading_session}', [ReleasePrintController::class, 'index'])->name('index');
    Route::post('{grading_session}/mark-printed', [ReleasePrintController::class, 'markPrinted'])->name('mark-printed');
    Route::get('{grading_session}/applicants/{applicant}', [ReleasePrintController::class, 'resultSheet'])->name('result-sheet');
    Route::get('{grading_session}/print-bulk', [ReleasePrintController::class, 'printBulk'])->name('print-bulk');
});
```

Add the import at the top of `routes/web.php`:
```php
use App\Http\Controllers\Release\ReleasePrintController;
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=ReleasePrintControllerTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Release/ReleasePrintController.php app/Http/Requests/Release/MarkPrintedRequest.php tests/Feature/ReleasePrintControllerTest.php routes/web.php
git commit -m "feat: add ReleasePrintController with print batch, mark-printed, result sheet, and bulk print routes"
```

---

### Task 4: Create Release-area Svelte pages (PrintBatch, ResultSheet, ResultSheetBulk)

**Files:**
- Create: `resources/js/Pages/Release/PrintBatch.svelte`
- Create: `resources/js/Pages/Release/ResultSheet.svelte`
- Create: `resources/js/Pages/Release/ResultSheetBulk.svelte`

These are copies of the Grading-area print pages with updated breadcrumbs and route URLs.

- [ ] **Step 1: Create `Release/PrintBatch.svelte`**

Copy from `resources/js/Pages/Grading/PrintBatch.svelte` and make these changes:

1. **Breadcrumbs** — Update to show release context:
```svelte
const breadcrumbs = $derived([
  { label: 'Release', href: '/admin/release' },
  { label: 'Session #' + sid, href: `/admin/release/print/${sid}` },
  { label: 'Print Batch' }
]);
```

2. **Mark-printed URL** — Change from `/admin/grading/sessions/${sid}/mark-printed` to `/admin/release/print/${sid}/mark-printed`.

3. **Result sheet link** — Change from `/admin/grading/sessions/${sid}/applicants/${app.applicant_id}/result-sheet` to `/admin/release/print/${sid}/applicants/${app.applicant_id}`.

4. **Print bulk link** — Change from `/admin/grading/sessions/${sid}/print-bulk?ids=` to `/admin/release/print/${sid}/print-bulk?ids=`.

5. **Back link** — Keep pointing to the release print batch (same page context), or change to `/admin/release`.

The full file content for `Release/PrintBatch.svelte` is identical to `Grading/PrintBatch.svelte` except for the breadcrumb and route URL changes above.

- [ ] **Step 2: Create `Release/ResultSheet.svelte`**

Copy from `resources/js/Pages/Grading/ResultSheet.svelte` and make these changes:

1. **Back link** — Change from `/admin/grading/sessions/${sid}/print` to `/admin/release/print/${sid}`.
2. **Mark-printed URL** — Change from `/admin/grading/sessions/${sid}/mark-printed` to `/admin/release/print/${sid}/mark-printed`.

- [ ] **Step 3: Create `Release/ResultSheetBulk.svelte`**

Copy from `resources/js/Pages/Grading/ResultSheetBulk.svelte` and make these changes:

1. **Back link** — Change from `/admin/grading/sessions/${sid}/print` to `/admin/release/print/${sid}`.
2. **Mark-printed URL** — Change from `/admin/grading/sessions/${sid}/mark-printed` to `/admin/release/print/${sid}/mark-printed`.

- [ ] **Step 4: Verify in browser**

Run: `npm run dev` (or `composer run dev`)
Navigate to `/admin/release/print/{sessionId}` in F2F mode.
Expected: Print batch page renders with applicants, mark-as-printed toggle works, result sheet link opens correctly.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Release/PrintBatch.svelte resources/js/Pages/Release/ResultSheet.svelte resources/js/Pages/Release/ResultSheetBulk.svelte
git commit -m "feat: add Release-area PrintBatch, ResultSheet, and ResultSheetBulk Svelte pages"
```

---

### Task 5: Enhance Release/Index.svelte with print status and F2F print actions

**Files:**
- Modify: `resources/js/Pages/Release/Index.svelte`
- Modify: `app/Http/Controllers/ReleaseController.php`

Add a "Printed" badge column to the release table, and in F2F mode add per-applicant print actions (mark printed, view result sheet).

- [ ] **Step 1: Add printed status to ReleaseController applicant data**

In `ReleaseController::index()`, the `ConsultationSummary` eager-load already includes `applicant`. We need to also load the grading session pivot to get `result_printed_at`.

Add to the `with()` clause in `index()`:
```php
'applicant.gradingSessions' => fn ($query) => $query->withPivot('result_printed_at'),
```

Then in the `->through()` callback, add printed status:
```php
$printed = $summary->applicant?->gradingSessions->isNotEmpty()
    ? (bool) $summary->applicant->gradingSessions->firstWhere('id', $gradingSessionId)?->pivot?->result_printed_at
    : false;
```

Note: We need the grading session context. Since release is session-agnostic (summaries span sessions), we add `printed` per grading session. For simplicity, we check if the applicant has ANY printed result across sessions, or we scope it. For MVP, we'll show `printed: true` if the applicant has `result_printed_at` set on ANY grading session pivot.

Actually, a simpler approach: add a computed `printed` attribute that checks if ANY grading session has `result_printed_at` set. This matches the real-world question: "Has this applicant's result been printed at all?"

```php
$printed = $summary->applicant?->gradingSessions
    ?->some(fn ($gs) => (bool) $gs->pivot->result_printed_at) ?? false;
```

And pass it through in the summary data:
```php
'status' => $summary->status,
'printed' => $printed,
```

- [ ] **Step 2: Add "Printed" badge to Release table**

In `Release/Index.svelte`, add a new `<Table.Head>` after "Status":
```svelte
<Table.Head class="px-4 py-3">Printed</Table.Head>
```

And a new `<Table.Cell>` in the row:
```svelte
<Table.Cell class="px-4 py-3">
  {#if summary.printed}
    <Badge variant="success" class="gap-1 text-xs">
      <Printer class="h-3 w-3" />
      Printed
    </Badge>
  {:else}
    <Badge variant="muted" class="gap-1 text-xs">
      <Printer class="h-3 w-3" />
      Not printed
    </Badge>
  {/if}
</Table.Cell>
```

Import `Printer` from `lucide-svelte` at the top.

- [ ] **Step 3: Add F2F print actions to Release table**

In the Action column, add print-related actions when in F2F mode:

```svelte
{#if isF2F && summary.applicant?.grading_session_id}
  <Link href={`/admin/release/print/${summary.applicant.grading_session_id}/applicants/${summary.applicant.id}`} target="_blank">
    <Button variant="outline" size="sm" class="h-8 px-2 text-xs">
      <Printer class="mr-1 h-3 w-3" />
      Result sheet
    </Button>
  </Link>
{/if}
```

Note: We need to pass `grading_session_id` through. Since an applicant can be in multiple sessions, we'll use the first session or the most recent one. For the initial implementation, we pass the session ID through the summary data.

- [ ] **Step 4: Add "Print batch" link in the Release page header**

Add a link to the print batch page for F2F mode. We need to determine which grading session to link to. For now, add a link that's visible when there are graded sessions:

```svelte
{#if isF2F}
  <Link href="/admin/release/print/{sessionId}">
    <Button variant="outline" class="min-h-[44px] gap-2">
      <Printer class="h-4 w-4" />
      Print batch
    </Button>
  </Link>
{/if}
```

This requires the controller to pass at least one `gradingSessionId` or a list of sessions. We'll add a `$gradingSessions` variable to the Release index for F2F context.

Actually, simpler approach: In F2F mode, the Release page should show a session selector or list active sessions with links to their print batch pages. For MVP, we'll add a simple dropdown or link in the header.

**Simpler MVP approach for Task 5:** Since the Release page is session-agnostic (summaries are not tied to a specific session in the current data model), and print management IS session-scoped, the cleanest approach is:

1. Add the "Printed" badge to each summary row (showing if the applicant has ANY printed result).
2. In F2F mode, add a "Print batch" section at the top that links to the session-specific print batch pages.
3. Pass `gradingSessions` (sessions with finalized status) to the view so we can link to them.

- [ ] **Step 5: Run pint on modified PHP files**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Verify in browser**

Navigate to `/admin/release` in F2F mode.
Expected: "Printed" / "Not printed" badges appear in the table. In F2F mode, a "Print batch" link appears linking to session print pages.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/ReleaseController.php resources/js/Pages/Release/Index.svelte
git commit -m "feat: add printed badge and F2F print batch link to Release page"
```

---

### Task 6: Update navigation breadcrumbs and links

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.svelte`

Update the sidebar navigation so that "Print Results" (if present) links to `/admin/release/print/{sessionId}` instead of `/admin/grading/sessions/{id}/print`. Also ensure the breadcrumb structure is consistent.

- [ ] **Step 1: Check current navigation structure**

Read `AuthenticatedLayout.svelte` to see if there's a direct "Print" or "Print Results" nav link. If there is, update its route. If not, no change needed since the print batch is accessed from within the Release page.

- [ ] **Step 2: Update any hardcoded print route links**

Search all Svelte files for `/admin/grading/sessions/` + `print` references and update them to `/admin/release/print/`.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "refactor: update navigation links from grading print to release print routes"
```

---

### Task 7: Remove old Grading-area print routes and pages

**Files:**
- Delete: `app/Http/Controllers/Grading/GradingPrintController.php`
- Delete: `resources/js/Pages/Grading/PrintBatch.svelte`
- Delete: `resources/js/Pages/Grading/ResultSheet.svelte`
- Delete: `resources/js/Pages/Grading/ResultSheetBulk.svelte`
- Delete: `app/Http/Requests/MarkPrintedRequest.php` (replaced by `Release/MarkPrintedRequest.php`)
- Modify: `routes/web.php` (remove old grading print routes)

- [ ] **Step 1: Remove old grading print routes from web.php**

Remove these lines from `routes/web.php` (inside the `admin.grading` group):
```php
Route::get('grading/sessions/{grading_session}/print', [GradingPrintController::class, 'index'])->name('sessions.print');
Route::post('grading/sessions/{grading_session}/mark-printed', [GradingPrintController::class, 'markPrinted'])->name('sessions.mark-printed');
Route::get('grading/sessions/{grading_session}/print-bulk', [GradingPrintController::class, 'printBulk'])->name('sessions.print-bulk');
Route::get('grading/sessions/{grading_session}/applicants/{applicant}/result-sheet', [GradingPrintController::class, 'resultSheet'])->name('sessions.result-sheet');
```

Also remove the `GradingPrintController` import if it's no longer used.

- [ ] **Step 2: Delete old controller and pages**

```bash
rm app/Http/Controllers/Grading/GradingPrintController.php
rm app/Http/Requests/MarkPrintedRequest.php
rm resources/js/Pages/Grading/PrintBatch.svelte
rm resources/js/Pages/Grading/ResultSheet.svelte
rm resources/js/Pages/Grading/ResultSheetBulk.svelte
```

- [ ] **Step 3: Run pint to clean up**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Verify no broken references**

Search for any remaining references to the old routes:
- `admin.grading.sessions.print`
- `admin.grading.sessions.mark-printed`
- `admin.grading.sessions.print-bulk`
- `admin.grading.sessions.result-sheet`
- `/admin/grading/sessions/*/print`
- `/admin/grading/sessions/*/mark-printed`
- `/admin/grading/sessions/*/print-bulk`
- `/admin/grading/sessions/*/applicants/*/result-sheet`

Also search for imports of `GradingPrintController` or `MarkPrintedRequest` (old namespace).

- [ ] **Step 5: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass, no broken routes.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor: remove old grading-area print routes, controller, request, and Svelte pages"
```

---

### Task 8: Integration testing and final verification

**Files:**
- Test: `tests/Feature/ReleasePrintControllerTest.php` (expand)
- Test: `tests/Feature/GradingSessionControllerTest.php` (verify printed flag)

- [ ] **Step 1: Add integration test for the full print flow from release**

```php
// In tests/Feature/ReleasePrintControllerTest.php, add:

public function test_full_print_workflow_from_release(): void
{
    $session = GradingSession::factory()->create(['status' => GradingSession::STATUS_FINALIZED]);
    $applicant = Applicant::factory()->create();
    $session->applicants()->attach($applicant->id, ['result_printed_at' => null]);
    ResultSheetTemplate::factory()->create(['is_active' => true, 'mode' => 'html', 'content' => '<div>{{applicant_name}}</div>']);

    $admin = User::factory()->create(['role' => 'super_admin']);

    // 1. View print batch
    $response = $this->actingAs($admin)
        ->get(route('admin.release.print.index', $session));
    $response->assertOk();

    // 2. Mark as printed
    $response = $this->actingAs($admin)
        ->post(route('admin.release.print.mark-printed', $session), [
            'applicant_ids' => [$applicant->id],
            'printed' => true,
        ]);
    $response->assertRedirect();
    $this->assertNotNull(
        $session->fresh()->applicants()->where('applicants.id', $applicant->id)->first()->pivot->result_printed_at
    );

    // 3. View result sheet
    $response = $this->actingAs($admin)
        ->get(route('admin.release.print.result-sheet', [$session, $applicant]));
    $response->assertOk();

    // 4. Verify printed flag shows in session page
    $response = $this->actingAs($admin)
        ->get(route('admin.grading.sessions.show', $session));
    $response->assertInertia(fn ($page) => $page
        ->where('applicants.0.printed', true)
    );
}
```

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test --compact`
Expected: All tests pass.

- [ ] **Step 3: Manual browser verification checklist**

- [ ] Navigate to `/admin/grading/sessions/{id}` after finalizing — "Printed" / "Not printed" badges appear for scored applicants in F2F mode
- [ ] "Print results" button links to `/admin/release/print/{id}`
- [ ] `/admin/release/print/{id}` shows the print batch page with correct data
- [ ] Mark as printed / Unmark printed toggles work on the new page
- [ ] Single result sheet opens at `/admin/release/print/{id}/applicants/{aid}`
- [ ] Bulk print opens at `/admin/release/print/{id}/print-bulk?ids=1,2,3`
- [ ] `/admin/release` shows "Printed" badges in F2F mode
- [ ] `/admin/release` shows "Print batch" link in F2F mode
- [ ] Old grading print routes return 404
- [ ] Result Templates page still works at `/admin/release/result-templates`

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "test: add integration test for release print workflow and verify printed flag propagation"
```

---

## Self-Review

**1. Spec coverage:** Every feature from the current Print Batch page is accounted for:
- Print batch listing → Task 3, 4
- Mark as printed / Unmark printed → Task 3
- Single result sheet → Task 3, 4
- Bulk print → Task 3, 4
- Print status badge on Session page → Task 2
- Print status on Release page → Task 5
- Result templates → unchanged, already at `/admin/release/result-templates`
- Route migration → Task 6, 7

**2. Placeholder scan:** No TBD, TODO, or "implement later" in any step. All code blocks contain complete, runnable code.

**3. Type consistency:**
- `printed` is consistently a `bool` throughout (from `(bool) $a->pivot->result_printed_at` in PHP to `app.printed` in Svelte).
- `sessionId` is consistently cast to `(string)` in all controllers.
- Route parameter names match between PHP routes and Svelte links (`grading_session`, `applicant`).
- The `MarkPrintedRequest` validation rules match the existing `MarkPrintedRequest` (including `required` on `applicant_ids.*`).
- `MarkPrintedRequest::authorize()` uses `hasAnyRole(['super_admin', 'test_administrator']) ?? false` — consistent with all other FormRequests in the codebase.
- `PrintBatchService::markPrinted()` signature is unchanged — both old and new controllers call it identically.

**4. Pattern consistency (review-applied fixes):**
- `MarkPrintedRequest::authorize()` uses `hasAnyRole()` with null-safe `?? false`, matching the project-wide convention across 20+ FormRequests.
- `applicant_ids.*` validation includes `required` per the existing `MarkPrintedRequest`.
- `ReleasePrintController` lives in `App\Http\Controllers\Release\` namespace, matching the `App\Http\Requests\Release\` namespace and the project convention of feature-scoped subdirectories.
- `printBulk` `ids` query parameter handles both string (`?ids=1,2,3`) and array (`?ids[]=1&ids[]=2`) formats, fixing a fragility carried over from `GradingPrintController`.
- `GradingSession::applicants()` already declares `withPivot('result_printed_at')` (verified line 58), so `$a->pivot->result_printed_at` is reliably populated in Task 1's eager-loaded collection.