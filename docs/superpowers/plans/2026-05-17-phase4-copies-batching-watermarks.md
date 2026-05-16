# Phase 4: Copies + Bulk PDF Batching + Watermarks — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add copies duplication, CSS watermark overlay, and queue-based bulk PDF generation to the print rendering pipeline.

**Architecture:** Extend existing services (ResultSheetPdfService, ResultSheetTemplateService) with new methods. Add PrintJob model and queued job for async batch processing. Watermarks via CSS overlay injected at PDF generation time.

**Tech Stack:** Laravel 12, Spatie PDF, database queue, Svelte 5, Inertia 2

**Spec:** `docs/superpowers/specs/2026-05-16-phase4-copies-batching-watermarks-design.md`

---

## File Map

### New Files
| File | Purpose |
|------|---------|
| `database/migrations/2026_05_17_000001_add_watermark_text_to_result_sheet_templates_table.php` | Add watermark_text column |
| `database/migrations/2026_05_17_000002_create_print_jobs_table.php` | Print job tracking table |
| `app/Models/PrintJob.php` | Eloquent model for print_jobs |
| `app/Jobs/GenerateBulkResultSheetPdf.php` | Queued job for bulk PDF generation |
| `app/Console/Commands/CleanupPrintJobs.php` | Purge old completed jobs |
| `tests/Feature/ReleasePrint/BulkPdfJobTest.php` | Feature tests for job dispatch + download |
| `tests/Unit/Services/ResultSheetPdfServiceCopiesTest.php` | Unit tests for copies + watermark |

### Modified Files
| File | Changes |
|------|---------|
| `app/ValueObjects/RenderResult.php` | Add `$watermarkText` property + update `fromTemplate()` |
| `app/Models/ResultSheetTemplate.php` | Add `watermark_text` to `$fillable` |
| `app/Services/ResultSheetPdfService.php` | Copies expansion + watermark injection + `generateBulkPdfContent()` |
| `app/Services/ResultSheetTemplateService.php` | New `buildSheetsForApplicantIds()` method |
| `app/Http/Controllers/Release/ReleasePrintController.php` | Copies param + job dispatch + status/download routes + delegate to service |
| `app/Http/Controllers/Admin/ResultSheetTemplateController.php` | Pass `watermark_text` in store/update |
| `app/Http/Requests/StoreResultSheetTemplateRequest.php` | Add watermark_text validation |
| `app/Http/Requests/UpdateResultSheetTemplateRequest.php` | Add watermark_text validation |
| `resources/js/Pages/Release/PrintBatch.svelte` | Copies input + progress bar + job polling |
| `resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte` | Watermark text input |
| `resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte` | Watermark text input |
| `routes/web.php` | New print job routes |
| `routes/console.php` | Schedule cleanup command |

---

## Task 1: RenderResult Watermark Extension + Migration

**Files:**
- Create: `database/migrations/2026_05_17_000001_add_watermark_text_to_result_sheet_templates_table.php`
- Modify: `app/ValueObjects/RenderResult.php:15-31`
- Modify: `app/Models/ResultSheetTemplate.php:12-21`

- [ ] **Step 1: Create the migration**

Run:
```bash
php artisan make:migration add_watermark_text_to_result_sheet_templates_table --no-interaction
```

Then edit the generated file to contain:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_sheet_templates', function (Blueprint $table) {
            $table->string('watermark_text', 50)->nullable()->after('docx_path');
        });
    }

    public function down(): void
    {
        Schema::table('result_sheet_templates', function (Blueprint $table) {
            $table->dropColumn('watermark_text');
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate --no-interaction`
Expected: "Migration ran successfully" with no errors.

- [ ] **Step 3: Update RenderResult value object**

In `app/ValueObjects/RenderResult.php`, add `watermarkText` to the constructor and `fromTemplate()`:

```php
// Line 15-21: constructor — add watermarkText parameter
public function __construct(
    public readonly string $html,
    public readonly string $mode,
    public readonly string $paperSize,
    public readonly string $orientation,
    public readonly string $logicalUnit,
    public readonly ?string $watermarkText = null,
) {}
```

Update `fromTemplate()` to carry watermark:

```php
// Lines 23-32: fromTemplate — add watermarkText
public static function fromTemplate(ResultSheetTemplate $template, string $html = ''): self
{
    return new self(
        html: $html,
        mode: $template->mode,
        paperSize: $template->paper_size ?? ResultSheetTemplate::PAPER_A4,
        orientation: $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT,
        logicalUnit: $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL,
        watermarkText: $template->watermark_text,
    );
}
```

- [ ] **Step 4: Update ResultSheetTemplate model fillable**

In `app/Models/ResultSheetTemplate.php`, add `watermark_text` to the `$fillable` array:

```php
// Line 12-21: add watermark_text
protected $fillable = [
    'name',
    'mode',
    'paper_size',
    'orientation',
    'logical_unit',
    'content',
    'docx_path',
    'watermark_text',
    'is_active',
];
```

- [ ] **Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Run existing tests to verify no regression**

Run: `php artisan test --compact tests/Unit/Services/ResultSheetPdfServiceTest.php`
Expected: All tests pass. The new `watermarkText` parameter has a default of `null`, so existing `new RenderResult(...)` calls without it still work.

- [ ] **Step 7: Commit**

```bash
git add app/ValueObjects/RenderResult.php app/Models/ResultSheetTemplate.php database/migrations/*_add_watermark_text_to_result_sheet_templates_table.php
git commit -m "feat: add watermark_text to ResultSheetTemplate and RenderResult"
```

---

## Task 2: Watermark CSS Injection in ResultSheetPdfService

**Files:**
- Modify: `app/Services/ResultSheetPdfService.php:64-77`
- Create: `tests/Unit/Services/ResultSheetPdfServiceCopiesTest.php` (watermark tests)

- [ ] **Step 1: Write failing test for watermark injection**

Create `tests/Unit/Services/ResultSheetPdfServiceCopiesTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Services\ResultSheetPdfService;
use App\ValueObjects\RenderResult;
use Illuminate\Http\Response;
use Mockery;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

class ResultSheetPdfServiceCopiesTest extends TestCase
{
    private ResultSheetPdfService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResultSheetPdfService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -- Watermark tests ---------------------------------------------------

    public function test_builder_injects_watermark_overlay_when_present()
    {
        $result = new RenderResult(
            html: '<h1>Test</h1>',
            mode: 'single',
            paperSize: 'a4',
            orientation: 'portrait',
            logicalUnit: 'full',
            watermarkText: 'DRAFT',
        );

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('html')
            ->once()
            ->with(Mockery::on(fn (string $html) => str_contains($html, 'watermark-overlay') && str_contains($html, 'DRAFT')))
            ->andReturn($builderMock);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheet.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->andReturn($builderMock);

        $response = $this->service->inline($result);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_builder_does_not_inject_watermark_when_null()
    {
        $result = new RenderResult(
            html: '<h1>Test</h1>',
            mode: 'single',
            paperSize: 'a4',
            orientation: 'portrait',
            logicalUnit: 'full',
            watermarkText: null,
        );

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('html')
            ->once()
            ->with(Mockery::on(fn (string $html) => ! str_contains($html, 'watermark-overlay')))
            ->andReturn($builderMock);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheet.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->andReturn($builderMock);

        $response = $this->service->inline($result);
        $this->assertInstanceOf(Response::class, $response);
    }

    // -- Copies tests ------------------------------------------------------

    public function test_copies_duplications_are_collated_per_sheet()
    {
        $meta = new RenderResult(html: '', mode: 'bulk', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $sheets = ['<div>A</div>', '<div>B</div>'];

        // With copies=2, expect: A, A, B, B (collated)
        $expectedHtml = '<div>A</div><div style="page-break-after: always;"></div><div>A</div><div style="page-break-after: always;"></div><div>B</div><div style="page-break-after: always;"></div><div>B</div>';

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('html')->with($expectedHtml)->andReturn($builderMock);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheets.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->with($expectedHtml)->andReturn($builderMock);

        $this->service->bulkInline($sheets, $meta, 'result-sheets.pdf', 2);
    }

    public function test_copies_default_1_produces_same_output()
    {
        $meta = new RenderResult(html: '', mode: 'bulk', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $sheets = ['<div>Sheet 1</div>', '<div>Sheet 2</div>'];

        $expectedHtml = '<div>Sheet 1</div><div style="page-break-after: always;"></div><div>Sheet 2</div>';

        $builderMock = Mockery::mock(PdfBuilder::class);
        $builderMock->shouldReceive('html')->with($expectedHtml)->andReturn($builderMock);
        $builderMock->shouldReceive('format')->with('a4')->andReturn($builderMock);
        $builderMock->shouldReceive('margins')->with(0, 0, 0, 0)->andReturn($builderMock);
        $builderMock->shouldReceive('inline')->with('result-sheets.pdf')->andReturn($builderMock);
        $builderMock->shouldReceive('toResponse')->andReturn(new Response('pdf-content'));

        Pdf::shouldReceive('html')->once()->with($expectedHtml)->andReturn($builderMock);

        $this->service->bulkInline($sheets, $meta, 'result-sheets.pdf', 1);
    }

    public function test_render_result_from_template_carries_watermark()
    {
        $template = \App\Models\ResultSheetTemplate::factory()->make([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'watermark_text' => 'FINAL',
        ]);

        $result = RenderResult::fromTemplate($template);

        $this->assertEquals('FINAL', $result->watermarkText);
    }

    public function test_render_result_from_template_null_watermark()
    {
        $template = \App\Models\ResultSheetTemplate::factory()->make([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'watermark_text' => null,
        ]);

        $result = RenderResult::fromTemplate($template);

        $this->assertNull($result->watermarkText);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Unit/Services/ResultSheetPdfServiceCopiesTest.php`
Expected: `test_builder_injects_watermark_overlay_when_present` FAILS (no watermark injection yet). `test_copies_duplications_are_collated_per_sheet` FAILS (no copies param). `test_render_result_from_template_*` should PASS (implemented in Task 1).

- [ ] **Step 3: Implement watermark injection + copies expansion**

Replace `app/Services/ResultSheetPdfService.php` entirely with:

```php
<?php

namespace App\Services;

use App\ValueObjects\RenderResult;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\Response;

class ResultSheetPdfService
{
    /**
     * Stream a single-applicant result sheet PDF inline (for "View PDF").
     */
    public function inline(RenderResult $result, string $filename = 'result-sheet.pdf'): Response
    {
        return $this->builder($result)->inline($filename)->toResponse(request());
    }

    /**
     * Download a single-applicant result sheet PDF (for "Download PDF").
     */
    public function download(RenderResult $result, string $filename = 'result-sheet.pdf'): Response
    {
        return $this->builder($result)->download($filename)->toResponse(request());
    }

    /**
     * Stream a multi-applicant bulk PDF inline, with page breaks between sheets.
     *
     * @param  string[]  $sheetsHtml  One HTML blob per logical sheet.
     */
    public function bulkInline(array $sheetsHtml, RenderResult $meta, string $filename = 'result-sheets.pdf', int $copies = 1): Response
    {
        return $this->builder($meta, $this->combineSheets($sheetsHtml, $copies))
            ->inline($filename)
            ->toResponse(request());
    }

    /**
     * Download a multi-applicant bulk PDF.
     *
     * @param  string[]  $sheetsHtml  One HTML blob per logical sheet.
     */
    public function bulkDownload(array $sheetsHtml, RenderResult $meta, string $filename = 'result-sheets.pdf', int $copies = 1): Response
    {
        return $this->builder($meta, $this->combineSheets($sheetsHtml, $copies))
            ->download($filename)
            ->toResponse(request());
    }

    /**
     * Generate bulk PDF content as a string (for disk storage by queued jobs).
     *
     * @param  string[]  $sheetsHtml  One HTML blob per logical sheet.
     */
    public function generateBulkPdfContent(array $sheetsHtml, RenderResult $meta, int $copies = 1): string
    {
        $html = $this->combineSheets($sheetsHtml, $copies);

        $builder = Pdf::html($html)
            ->format($meta->paperSize)
            ->margins(0, 0, 0, 0);

        if ($meta->orientation === 'landscape') {
            $builder->landscape();
        }

        return $builder->content();
    }

    /**
     * Return pixel/mm dimensions for iframe preview sizing.
     *
     * @return array{width: int, height: int}
     */
    public function previewDimensions(RenderResult $result): array
    {
        return $result->pageDimensions();
    }

    // -- Private Helpers ---------------------------------------------------

    private function builder(RenderResult $meta, ?string $html = null): PdfBuilder
    {
        $html ??= $meta->html;

        if ($meta->watermarkText !== null) {
            $html = $this->injectWatermark($html, $meta->watermarkText);
        }

        $builder = Pdf::html($html)
            ->format($meta->paperSize)
            ->margins(0, 0, 0, 0);

        if ($meta->orientation === 'landscape') {
            $builder->landscape();
        }

        return $builder;
    }

    /**
     * Combine sheets into a single HTML document with page breaks.
     * Applies collated copies: each sheet is repeated N times in sequence.
     *
     * @param  string[]  $sheetsHtml
     */
    private function combineSheets(array $sheetsHtml, int $copies = 1): string
    {
        $expanded = [];
        foreach ($sheetsHtml as $sheet) {
            for ($i = 0; $i < $copies; $i++) {
                $expanded[] = $sheet;
            }
        }

        return implode('<div style="page-break-after: always;"></div>', $expanded);
    }

    private function injectWatermark(string $html, string $text): string
    {
        $overlay = '<div class="watermark-overlay"><span>'.htmlspecialchars($text).'</span></div>';
        $css = '<style>.watermark-overlay{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-45deg);font-size:4rem;color:rgba(200,200,200,0.4);pointer-events:none;z-index:9999;white-space:nowrap;user-select:none;}</style>';

        return $css.$overlay.$html;
    }
}
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Run all PDF service tests**

Run: `php artisan test --compact tests/Unit/Services/ResultSheetPdfServiceTest.php tests/Unit/Services/ResultSheetPdfServiceCopiesTest.php`
Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Services/ResultSheetPdfService.php tests/Unit/Services/ResultSheetPdfServiceCopiesTest.php
git commit -m "feat: add copies expansion and watermark injection to ResultSheetPdfService"
```

---

## Task 3: Extract buildSheetsForApplicantIds to Service

The controller currently has `buildSheetsFromApplicants()` and `buildApplicantData()` as private helpers. The queued job needs the same logic. Extract it to `ResultSheetTemplateService`.

**Files:**
- Modify: `app/Services/ResultSheetTemplateService.php` — add `buildSheetsForApplicantIds()`
- Modify: `app/Http/Controllers/Release/ReleasePrintController.php` — delegate to service

- [ ] **Step 1: Add buildSheetsForApplicantIds to ResultSheetTemplateService**

Add this method to `app/Services/ResultSheetTemplateService.php` after the `renderDual()` method (after line 79):

```php
/**
 * Fetch applicants + scores for given IDs, render into sheet HTML blobs.
 * When $gradingSessionId is null, resolves the best session per applicant (agnostic mode).
 *
 * @param  int[]  $applicantIds
 * @return string[]  One HTML blob per logical sheet
 */
public function buildSheetsForApplicantIds(
    array $applicantIds,
    ResultSheetTemplate $template,
    ?int $gradingSessionId = null,
): array {
    $logicalUnit = $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL;
    $chunkSize = in_array($logicalUnit, [ResultSheetTemplate::LOGICAL_HALF_A4, ResultSheetTemplate::LOGICAL_HALF_LEGAL, ResultSheetTemplate::LOGICAL_HALF_LETTER], true) ? 2 : 1;

    $applicantsWithScores = $this->fetchApplicantsWithScores($applicantIds, $gradingSessionId);

    $sheetsHtml = [];
    foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
        if (count($chunk) === 2) {
            $result = $this->renderDual($template, $chunk[0], $chunk[1], false);
            $sheetsHtml[] = $result->html;
        } else {
            $result = $this->render($template, $chunk, false);
            $sheetsHtml[] = $result->html;
        }
    }

    return $sheetsHtml;
}

/**
 * Fetch applicants with their scores, return data arrays ready for template rendering.
 *
 * @param  int[]  $applicantIds
 * @return array<int, array<string, mixed>>
 */
protected function fetchApplicantsWithScores(array $applicantIds, ?int $gradingSessionId = null): array
{
    if ($gradingSessionId !== null) {
        return $this->fetchApplicantsForSession($applicantIds, $gradingSessionId);
    }

    return $this->fetchApplicantsAgnostic($applicantIds);
}

/**
 * @param  int[]  $applicantIds
 * @return array<int, array<string, mixed>>
 */
protected function fetchApplicantsForSession(array $applicantIds, int $gradingSessionId): array
{
    $session = \App\Models\GradingSession::with('examSession.room')->findOrFail($gradingSessionId);
    $applicants = $session->applicants()
        ->whereIn('applicants.id', $applicantIds)
        ->with('application')
        ->get();

    $scoresByApplicant = $session->applicantScores()
        ->whereIn('applicant_id', $applicantIds)
        ->with('aptitudeArea')
        ->get()
        ->groupBy('applicant_id');

    return $applicants->map(function ($a) use ($scoresByApplicant) {
        $scores = $this->mapScores($scoresByApplicant->get($a->id, collect()));

        return $this->buildApplicantDataArray($a, null, $scores);
    })->values()->all();
}

/**
 * @param  int[]  $applicantIds
 * @return array<int, array<string, mixed>>
 */
protected function fetchApplicantsAgnostic(array $applicantIds): array
{
    $applicants = \App\Models\Applicant::whereIn('id', $applicantIds)
        ->with('application', 'gradingSessions.examSession.room')
        ->get();

    $applicantSessionMap = [];
    foreach ($applicants as $applicant) {
        $gs = $applicant->gradingSessions->where('status', \App\Models\GradingSession::STATUS_FINALIZED)->first()
            ?? $applicant->gradingSessions->first();
        if ($gs) {
            $applicantSessionMap[$applicant->id] = $gs->id;
        }
    }

    $allScores = \App\Models\ApplicantScore::whereIn('applicant_id', array_keys($applicantSessionMap))
        ->whereIn('grading_session_id', array_unique(array_values($applicantSessionMap)))
        ->with('aptitudeArea')
        ->get()
        ->groupBy('applicant_id');

    return $applicants->map(function ($a) use ($allScores) {
        $gs = $a->gradingSessions->where('status', \App\Models\GradingSession::STATUS_FINALIZED)->first()
            ?? $a->gradingSessions->first();
        $scores = $this->mapScores(
            $allScores->get($a->id, collect())
                ->filter(fn ($s) => $gs && $s->grading_session_id === $gs->id)
        );

        return $this->buildApplicantDataArray($a, $gs, $scores);
    })->values()->all();
}

/**
 * @param  \Illuminate\Support\Collection<int, \App\Models\ApplicantScore>  $scores
 * @return array<int, array{domain: string, raw: int|float|null, max: int|float|null, pct: int}>
 */
protected function mapScores(\Illuminate\Support\Collection $scores): array
{
    return $scores->map(fn ($s) => [
        'domain' => $s->aptitudeArea?->name ?? '—',
        'raw' => $s->raw_score,
        'max' => $s->max_score,
        'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
    ])->values()->all();
}

/**
 * @param  \App\Models\Applicant  $applicant
 * @param  \App\Models\GradingSession|null  $session
 * @param  array<int, array{domain: string, raw: int|float|null, max: int|float|null, pct: int}>  $scores
 * @return array<string, mixed>
 */
protected function buildApplicantDataArray(\App\Models\Applicant $applicant, ?\App\Models\GradingSession $session, array $scores): array
{
    $overallPct = count($scores) > 0
        ? (int) round(collect($scores)->avg('pct'))
        : 0;

    $name = '—';
    if ($applicant->application) {
        $name = trim(implode(' ', array_filter([
            $applicant->application->first_name,
            $applicant->application->middle_name,
            $applicant->application->last_name,
            $applicant->application->suffix,
        ])));
    }

    return [
        'id' => $applicant->id,
        'name' => $name,
        'reference' => $applicant->application?->reference_number ?? '—',
        'exam_date' => $session?->examSession?->date?->format('F j, Y') ?? '—',
        'room_name' => $session?->examSession?->room?->name ?? '—',
        'scores' => $scores,
        'overall_pct' => $overallPct,
    ];
}
```

Add the necessary `use` statements at the top of the file (if not already present):

```php
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\GradingSession;
use Illuminate\Support\Collection;
```

- [ ] **Step 2: Refactor ReleasePrintController to delegate to service**

In `app/Http/Controllers/Release/ReleasePrintController.php`, replace the private helpers with calls to the service. The controller keeps `buildApplicantData()` for the single-sheet view only (where it loads data inline). The bulk methods delegate to the service.

Replace the `buildSheetsFromApplicants()` method (lines 379-396) with a delegation:

```php
/**
 * @param  array<int, array<string, mixed>>  $applicantsWithScores
 * @return array<int, string>
 */
private function buildSheetsFromApplicants(array $applicantsWithScores, ResultSheetTemplate $template): array
{
    // Delegate to service for consistent logic between sync and async paths
    return $this->templateService->buildSheetsFromApplicantData($applicantsWithScores, $template);
}
```

Add `buildSheetsFromApplicantData()` to `ResultSheetTemplateService` as well — this accepts pre-built applicant data arrays (what the controller already has from its inline paths):

```php
/**
 * Build sheet HTML from pre-built applicant data arrays.
 *
 * @param  array<int, array<string, mixed>>  $applicantsWithScores
 * @return string[]
 */
public function buildSheetsFromApplicantData(array $applicantsWithScores, ResultSheetTemplate $template): array
{
    $logicalUnit = $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL;
    $chunkSize = in_array($logicalUnit, [ResultSheetTemplate::LOGICAL_HALF_A4, ResultSheetTemplate::LOGICAL_HALF_LEGAL, ResultSheetTemplate::LOGICAL_HALF_LETTER], true) ? 2 : 1;

    $sheetsHtml = [];
    foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
        if (count($chunk) === 2) {
            $result = $this->renderDual($template, $chunk[0], $chunk[1], false);
            $sheetsHtml[] = $result->html;
        } else {
            $result = $this->render($template, $chunk, false);
            $sheetsHtml[] = $result->html;
        }
    }

    return $sheetsHtml;
}
```

- [ ] **Step 3: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Run existing tests to verify no regression**

Run: `php artisan test --compact`
Expected: All existing tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Services/ResultSheetTemplateService.php app/Http/Controllers/Release/ReleasePrintController.php
git commit -m "refactor: extract buildSheetsForApplicantIds to ResultSheetTemplateService"
```

---

## Task 4: PrintJob Migration + Model

**Files:**
- Create: `database/migrations/2026_05_17_000002_create_print_jobs_table.php`
- Create: `app/Models/PrintJob.php`

- [ ] **Step 1: Create migration**

Run:
```bash
php artisan make:migration create_print_jobs_table --no-interaction
```

Edit the generated file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grading_session_id')->nullable()->constrained()->nullOnDelete();
            $table->json('applicant_ids');
            $table->unsignedInteger('copies')->default(1);
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('pdf_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate --no-interaction`
Expected: Success.

- [ ] **Step 3: Create PrintJob model**

Create `app/Models/PrintJob.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PrintJob extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'grading_session_id',
        'applicant_ids',
        'copies',
        'status',
        'progress',
        'pdf_path',
        'error_message',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $job) {
            if (empty($job->id)) {
                $job->id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'applicant_ids' => 'array',
            'copies' => 'integer',
            'progress' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gradingSession(): BelongsTo
    {
        return $this->belongsTo(GradingSession::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Commit**

```bash
git add app/Models/PrintJob.php database/migrations/*_create_print_jobs_table.php
git commit -m "feat: add PrintJob model and migration for async bulk PDF generation"
```

---

## Task 5: GenerateBulkResultSheetPdf Job

**Files:**
- Create: `app/Jobs/GenerateBulkResultSheetPdf.php`

- [ ] **Step 1: Create the job**

Run:
```bash
php artisan make:job GenerateBulkResultSheetPdf --no-interaction
```

Edit `app/Jobs/GenerateBulkResultSheetPdf.php`:

```php
<?php

namespace App\Jobs;

use App\Models\PrintJob;
use App\Models\ResultSheetTemplate;
use App\Services\ResultSheetPdfService;
use App\Services\ResultSheetTemplateService;
use App\ValueObjects\RenderResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateBulkResultSheetPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public string $printJobId,
    ) {}

    public function handle(
        ResultSheetTemplateService $templateService,
        ResultSheetPdfService $pdfService,
    ): void {
        $printJob = PrintJob::findOrFail($this->printJobId);
        $printJob->update(['status' => 'processing']);

        try {
            $template = ResultSheetTemplate::where('is_active', true)->firstOrFail();
            $meta = RenderResult::fromTemplate($template);

            $applicantIds = $printJob->applicant_ids;
            $chunkSize = 10;
            $total = count($applicantIds);
            $sheetsHtml = [];

            foreach (array_chunk($applicantIds, $chunkSize) as $i => $chunk) {
                $sheetsHtml = array_merge(
                    $sheetsHtml,
                    $templateService->buildSheetsForApplicantIds($chunk, $template, $printJob->grading_session_id)
                );
                $processed = min(($i + 1) * $chunkSize, $total);
                $progress = (int) round(($processed / $total) * 100);
                $printJob->update(['progress' => min($progress, 99)]);
            }

            $path = "print-jobs/{$printJob->id}.pdf";
            $pdfContent = $pdfService->generateBulkPdfContent($sheetsHtml, $meta, $printJob->copies);
            Storage::disk('local')->put($path, $pdfContent);

            $printJob->update([
                'status' => 'completed',
                'progress' => 100,
                'pdf_path' => $path,
            ]);
        } catch (\Throwable $e) {
            $printJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

- [ ] **Step 2: Ensure queue table exists**

Run: `php artisan queue:table --no-interaction` (if `jobs` migration doesn't exist yet), then `php artisan migrate --no-interaction`.

Check if `database/migrations/*_create_jobs_table.php` exists first. If it does, skip this step.

- [ ] **Step 3: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/GenerateBulkResultSheetPdf.php
git commit -m "feat: add GenerateBulkResultSheetPdf queued job"
```

---

## Task 6: Controller Updates — Copies + Job Dispatch + Status Routes

**Files:**
- Modify: `app/Http/Controllers/Release/ReleasePrintController.php`
- Modify: `routes/web.php:249-258`

- [ ] **Step 1: Add copies param to existing bulk PDF methods**

In `app/Http/Controllers/Release/ReleasePrintController.php`, update `printBulkPdf()` (around line 193) to read copies:

```php
public function printBulkPdf(GradingSession $grading_session): SymfonyResponse
{
    $template = ResultSheetTemplate::where('is_active', true)->first();
    abort_if(! $template, 404, 'No active result sheet template.');

    $copies = (int) request()->query('copies', 1);
    abort_if($copies < 1 || $copies > 10, 422, 'Copies must be between 1 and 10.');

    $grading_session->load('examSession.room');
    $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
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
        $scores = $this->mapScores($scoresByApplicant->get($a->id, collect()));

        return $this->buildApplicantData($a, $grading_session, null, $scores);
    })->values()->all();

    $sheetsHtml = $this->buildSheetsFromApplicants($applicantsWithScores, $template);
    $meta = RenderResult::fromTemplate($template);

    return $this->pdfService->bulkDownload(
        $sheetsHtml,
        $meta,
        "session_{$grading_session->id}_result_sheets.pdf",
        $copies
    );
}
```

Similarly update `printBulkAgnosticPdf()` to accept copies:

```php
public function printBulkAgnosticPdf(): SymfonyResponse
{
    $template = ResultSheetTemplate::where('is_active', true)->first();
    abort_if(! $template, 404, 'No active result sheet template.');

    $copies = (int) request()->query('copies', 1);
    abort_if($copies < 1 || $copies > 10, 422, 'Copies must be between 1 and 10.');

    $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
    $applicants = Applicant::whereIn('id', $ids)
        ->with('application', 'gradingSessions.examSession.room')
        ->get();

    $applicantSessionMap = [];
    foreach ($applicants as $applicant) {
        $gs = $applicant->gradingSessions->where('status', GradingSession::STATUS_FINALIZED)->first() ?? $applicant->gradingSessions->first();
        if ($gs) {
            $applicantSessionMap[$applicant->id] = $gs->id;
        }
    }

    $allScores = ApplicantScore::whereIn('applicant_id', array_keys($applicantSessionMap))
        ->whereIn('grading_session_id', array_unique(array_values($applicantSessionMap)))
        ->with('aptitudeArea')
        ->get()
        ->groupBy('applicant_id');

    $applicantsWithScores = $applicants->map(function ($a) use ($allScores) {
        $gs = $a->gradingSessions->where('status', GradingSession::STATUS_FINALIZED)->first() ?? $a->gradingSessions->first();
        $scores = $this->mapScores(
            $allScores->get($a->id, collect())
                ->filter(fn ($s) => $gs && $s->grading_session_id === $gs->id)
        );

        return $this->buildApplicantData($a, $gs, null, $scores);
    })->values()->all();

    $sheetsHtml = $this->buildSheetsFromApplicants($applicantsWithScores, $template);
    $meta = RenderResult::fromTemplate($template);

    return $this->pdfService->bulkDownload($sheetsHtml, $meta, 'result_sheets.pdf', $copies);
}
```

- [ ] **Step 2: Add job dispatch + status + download methods**

Add these imports to the controller:

```php
use App\Jobs\GenerateBulkResultSheetPdf;
use App\Models\PrintJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
```

Add these three methods to the controller (before the `// -- Private Helpers` section):

```php
/**
 * Dispatch a bulk PDF generation job.
 */
public function dispatchBulkPdfJob(): JsonResponse
{
    $ids = array_slice(array_filter(array_map('intval', explode(',', request()->query('ids', '')))), 0, 200);
    abort_if(empty($ids), 422, 'No applicant IDs provided.');

    $copies = (int) request()->query('copies', 1);
    abort_if($copies < 1 || $copies > 10, 422, 'Copies must be between 1 and 10.');

    $sessionId = request()->query('grading_session_id');
    $gradingSessionId = $sessionId ? (int) $sessionId : null;

    $printJob = PrintJob::create([
        'user_id' => auth()->id(),
        'grading_session_id' => $gradingSessionId,
        'applicant_ids' => $ids,
        'copies' => $copies,
        'status' => 'pending',
    ]);

    GenerateBulkResultSheetPdf::dispatch($printJob->id);

    return response()->json(['jobId' => $printJob->id]);
}

/**
 * Return the status of a print job (for polling).
 */
public function printJobStatus(PrintJob $printJob): JsonResponse
{
    return response()->json([
        'id' => $printJob->id,
        'status' => $printJob->status,
        'progress' => $printJob->progress,
        'errorMessage' => $printJob->error_message,
        'pdfUrl' => $printJob->isCompleted() ? route('admin.release.print.print-job-download', $printJob->id) : null,
    ]);
}

/**
 * Download the PDF for a completed print job.
 */
public function printJobDownload(PrintJob $printJob): SymfonyResponse
{
    abort_if(! $printJob->isCompleted(), 404, 'PDF is not ready yet.');
    abort_if(! $printJob->pdf_path, 404, 'PDF file not found.');

    $fullPath = Storage::disk('local')->path($printJob->pdf_path);
    abort_unless(file_exists($fullPath), 404, 'PDF file missing from disk.');

    return response()->download($fullPath, "result_sheets_{$printJob->id}.pdf");
}
```

- [ ] **Step 3: Add routes**

In `routes/web.php`, inside the `Route::prefix('print')->name('print.')->group(...)` block, add these routes after the existing ones (after line 257):

```php
// Print job (async bulk PDF)
Route::post('bulk-pdf-job', [ReleasePrintController::class, 'dispatchBulkPdfJob'])->name('bulk-pdf-job');
Route::get('print-job/{printJob}', [ReleasePrintController::class, 'printJobStatus'])->name('print-job-status');
Route::get('print-job/{printJob}/download', [ReleasePrintController::class, 'printJobDownload'])->name('print-job-download');
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Verify routes are registered**

Run: `php artisan route:list --name=print --compact`
Expected: Shows the three new routes (`bulk-pdf-job`, `print-job-status`, `print-job-download`) alongside existing routes.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Release/ReleasePrintController.php routes/web.php
git commit -m "feat: add copies param, job dispatch, and status routes for bulk PDF"
```

---

## Task 7: PrintBatch.svelte — Copies Input + Job Polling

**Files:**
- Modify: `resources/js/Pages/Release/PrintBatch.svelte`

- [ ] **Step 1: Add copies state and job polling logic**

Add after the existing `let selected = $state(new Set());` line (line 23):

```js
  let copies = $state(1);

  // Job polling state
  let activeJobId = $state(null);
  let jobStatus = $state(null); // { status, progress, errorMessage, pdfUrl }
  let polling = $state(false);
  let pollInterval = $state(null);
```

Add these functions before the `printBulk()` function:

```js
  function startBulkPdfJob() {
    const ids = Array.from(selected);
    if (ids.length === 0) return;

    const params = new URLSearchParams({ ids: ids.join(','), copies: String(copies), grading_session_id: sid });
    fetch(`/admin/release/print/bulk-pdf-job?${params}`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      },
    })
      .then((r) => r.ok ? r.json() : r.json().then((e) => { throw new Error(e.message || 'Failed to start job'); }))
      .then((data) => {
        activeJobId = data.jobId;
        jobStatus = { status: 'pending', progress: 0, errorMessage: null, pdfUrl: null };
        startPolling();
      })
      .catch((err) => {
        alert(err.message || 'Failed to start bulk PDF generation.');
      });
  }

  function startPolling() {
    if (polling) return;
    polling = true;
    pollInterval = setInterval(() => {
      if (!activeJobId) { stopPolling(); return; }
      fetch(`/admin/release/print/print-job/${activeJobId}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then((r) => r.json())
        .then((data) => {
          jobStatus = data;
          if (data.status === 'completed' || data.status === 'failed') {
            stopPolling();
          }
        })
        .catch(() => { stopPolling(); });
    }, 2000);
  }

  function stopPolling() {
    polling = false;
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
  }

  function resetJob() {
    stopPolling();
    activeJobId = null;
    jobStatus = null;
  }
```

- [ ] **Step 2: Add copies input and progress UI in the template**

Replace the "Download PDF" link area (around lines 111-115) with:

```svelte
          {#if !printDisabled && selected.size > 0}
            <div class="flex items-center gap-2">
              <label for="copies" class="text-sm text-muted-foreground">Copies</label>
              <input
                id="copies"
                type="number"
                min="1"
                max="10"
                bind:value={copies}
                class="w-16 rounded-md border border-input bg-transparent px-2 py-1 text-sm"
              />
            </div>
            <a href={`/admin/release/print/${sid}/print-bulk-pdf?ids=${Array.from(selected).join(',')}&copies=${copies}`} target="_blank" rel="noopener">
              <Button variant="secondary" class="min-h-[44px]">Download PDF</Button>
            </a>
            <Button variant="outline" onclick={startBulkPdfJob} class="min-h-[44px]">
              Generate PDF (async)
            </Button>
          {/if}

          {#if jobStatus}
            <div class="flex items-center gap-3 rounded-md border p-3 bg-muted/50">
              {#if jobStatus.status === 'pending' || jobStatus.status === 'processing'}
                <div class="flex-1">
                  <p class="text-sm font-medium">Generating PDF... {jobStatus.progress}%</p>
                  <div class="mt-1 h-2 rounded-full bg-muted-foreground/20 overflow-hidden">
                    <div class="h-full bg-primary transition-all" style="width: {jobStatus.progress}%"></div>
                  </div>
                </div>
              {:else if jobStatus.status === 'completed'}
                <p class="text-sm font-medium text-green-600">PDF ready!</p>
                <a href={jobStatus.pdfUrl} target="_blank" rel="noopener">
                  <Button variant="default" size="sm">Download</Button>
                </a>
                <Button variant="ghost" size="sm" onclick={resetJob}>Dismiss</Button>
              {:else if jobStatus.status === 'failed'}
                <div class="flex-1">
                  <p class="text-sm font-medium text-destructive">Generation failed</p>
                  <p class="text-xs text-muted-foreground">{jobStatus.errorMessage ?? 'Unknown error'}</p>
                </div>
                <Button variant="outline" size="sm" onclick={resetJob}>Retry</Button>
              {/if}
            </div>
          {/if}
```

- [ ] **Step 3: Run pint + verify frontend compiles**

Run: `npm run build 2>&1 | head -20`
Expected: Build succeeds with no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Release/PrintBatch.svelte
git commit -m "feat: add copies input and async PDF job UI to PrintBatch"
```

---

## Task 8: Template Watermark UI (Create + Edit)

**Files:**
- Modify: `resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte:34-43`
- Modify: `resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte:35-44`
- Modify: `app/Http/Controllers/Admin/ResultSheetTemplateController.php`
- Modify: `app/Http/Requests/StoreResultSheetTemplateRequest.php`
- Modify: `app/Http/Requests/UpdateResultSheetTemplateRequest.php`

- [ ] **Step 1: Add watermark_text to form requests**

Check `app/Http/Requests/StoreResultSheetTemplateRequest.php` and `UpdateResultSheetTemplateRequest.php` for the `rules()` method. Add `'watermark_text' => ['nullable', 'string', 'max:50']` to the rules array.

- [ ] **Step 2: Add watermark_text to controller store/update**

In `app/Http/Controllers/Admin/ResultSheetTemplateController.php`, in the `store()` method's `$data` array (around line 66), add:

```php
'watermark_text' => $request->validated('watermark_text'),
```

Do the same in the `update()` method's `$data` array (around line 123).

- [ ] **Step 3: Add watermark input to Create.svelte**

In `resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte`, add `watermark_text: null` to the `useForm` call (after `is_active: true`).

Then add the watermark input field after the "Active" switch (after the closing `</div>` of the is_active block, before the submit button):

```svelte
        <div>
          <label for="watermark_text" class="text-sm font-medium">Watermark text (optional)</label>
          <p class="text-xs text-muted-foreground mt-0.5">Leave blank for no watermark. Shown diagonally on each PDF page (e.g. DRAFT, FINAL).</p>
          <Input
            id="watermark_text"
            bind:value={$form.watermark_text}
            placeholder="DRAFT"
            maxlength="50"
            class="mt-1 max-w-xs"
          />
          {#if $form.errors?.watermark_text}<p class="text-sm text-destructive mt-1">{$form.errors.watermark_text}</p>{/if}
        </div>
```

- [ ] **Step 4: Add watermark input to Edit.svelte**

In `resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte`, add `watermark_text: template?.watermark_text ?? null` to the `useForm` call.

Then add the same watermark input field (same HTML as Create.svelte) in the same position.

- [ ] **Step 5: Run pint + build**

Run: `vendor/bin/pint --dirty --format agent && npm run build 2>&1 | head -20`
Expected: Both succeed.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte app/Http/Controllers/Admin/ResultSheetTemplateController.php app/Http/Requests/StoreResultSheetTemplateRequest.php app/Http/Requests/UpdateResultSheetTemplateRequest.php
git commit -m "feat: add watermark text field to template create/edit forms"
```

---

## Task 9: Cleanup Command + Schedule

**Files:**
- Create: `app/Console/Commands/CleanupPrintJobs.php`
- Modify: `routes/console.php`

- [ ] **Step 1: Create the cleanup command**

Run:
```bash
php artisan make:command CleanupPrintJobs --no-interaction
```

Edit `app/Console/Commands/CleanupPrintJobs.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupPrintJobs extends Command
{
    protected $signature = 'app:cleanup-print-jobs {--hours=24 : Delete jobs older than this many hours}';

    protected $description = 'Remove completed/failed print jobs and their PDF files older than N hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $jobs = PrintJob::whereIn('status', ['completed', 'failed'])
            ->where('created_at', '<', $cutoff)
            ->get();

        $deleted = 0;
        foreach ($jobs as $job) {
            if ($job->pdf_path) {
                Storage::disk('local')->delete($job->pdf_path);
            }
            $job->delete();
            $deleted++;
        }

        $this->info("Cleaned up {$deleted} print job(s) older than {$hours} hours.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Schedule the command**

In `routes/console.php`, add:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:cleanup-print-jobs')->daily()->at('03:00');
```

- [ ] **Step 3: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/CleanupPrintJobs.php routes/console.php
git commit -m "feat: add scheduled cleanup for completed print jobs"
```

---

## Task 10: Feature Tests for Job Dispatch + Download

**Files:**
- Create: `tests/Feature/ReleasePrint/BulkPdfJobTest.php`

- [ ] **Step 1: Write feature tests**

Create `tests/Feature/ReleasePrint/BulkPdfJobTest.php`:

```php
<?php

namespace Tests\Feature\ReleasePrint;

use App\Models\GradingSession;
use App\Models\PrintJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkPdfJobTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Storage::fake('local');
    }

    public function test_dispatch_bulk_pdf_job_creates_print_job()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/release/print/bulk-pdf-job?ids=1,2,3&copies=2');

        $response->assertOk();
        $response->assertJsonStructure(['jobId']);

        $this->assertDatabaseHas('print_jobs', [
            'user_id' => $this->admin->id,
            'copies' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_dispatch_validates_copies_range()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/release/print/bulk-pdf-job?ids=1&copies=0');

        $response->assertStatus(422);

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/release/print/bulk-pdf-job?ids=1&copies=11');

        $response->assertStatus(422);
    }

    public function test_dispatch_requires_applicant_ids()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/admin/release/print/bulk-pdf-job');

        $response->assertStatus(422);
    }

    public function test_print_job_status_returns_progress()
    {
        $job = PrintJob::create([
            'user_id' => $this->admin->id,
            'applicant_ids' => [1, 2],
            'copies' => 1,
            'status' => 'processing',
            'progress' => 50,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/admin/release/print/print-job/{$job->id}");

        $response->assertOk();
        $response->assertJson([
            'status' => 'processing',
            'progress' => 50,
            'pdfUrl' => null,
        ]);
    }

    public function test_print_job_status_returns_pdf_url_when_completed()
    {
        $job = PrintJob::create([
            'user_id' => $this->admin->id,
            'applicant_ids' => [1],
            'copies' => 1,
            'status' => 'completed',
            'progress' => 100,
            'pdf_path' => 'print-jobs/test.pdf',
        ]);

        Storage::disk('local')->put('print-jobs/test.pdf', 'fake-pdf');

        $response = $this->actingAs($this->admin)
            ->getJson("/admin/release/print/print-job/{$job->id}");

        $response->assertOk();
        $response->assertJson([
            'status' => 'completed',
            'progress' => 100,
        ]);
        $this->assertNotNull($response->json('pdfUrl'));
    }

    public function test_download_returns_404_for_processing_job()
    {
        $job = PrintJob::create([
            'user_id' => $this->admin->id,
            'applicant_ids' => [1],
            'copies' => 1,
            'status' => 'processing',
            'progress' => 30,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/release/print/print-job/{$job->id}/download");

        $response->assertStatus(404);
    }

    public function test_download_returns_pdf_for_completed_job()
    {
        $job = PrintJob::create([
            'user_id' => $this->admin->id,
            'applicant_ids' => [1],
            'copies' => 1,
            'status' => 'completed',
            'progress' => 100,
            'pdf_path' => 'print-jobs/test.pdf',
        ]);

        Storage::disk('local')->put('print-jobs/test.pdf', 'fake-pdf-content');

        $response = $this->actingAs($this->admin)
            ->get("/admin/release/print/print-job/{$job->id}/download");

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }
}
```

- [ ] **Step 2: Run the feature tests**

Run: `php artisan test --compact tests/Feature/ReleasePrint/BulkPdfJobTest.php`
Expected: All tests pass.

- [ ] **Step 3: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/ReleasePrint/BulkPdfJobTest.php
git commit -m "test: add feature tests for bulk PDF job dispatch and download"
```

---

## Task 11: Final Verification

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: All tests pass.

- [ ] **Step 2: Run pint on all dirty files**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Verify frontend builds**

Run: `npm run build 2>&1 | head -20`
Expected: Build succeeds.

- [ ] **Step 4: Verify routes**

Run: `php artisan route:list --name=print --columns=method,uri,name`
Expected: Shows all new routes alongside existing ones.
