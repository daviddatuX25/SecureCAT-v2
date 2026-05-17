# Phase 4: Copies + Bulk PDF Batching + Watermarks — Design Spec

**Date:** 2026-05-16
**Status:** Approved
**Depends on:** Phase 1-3 (completed — crosswise layout, PDF export via Spatie, DOCX extraction)
**Scope:** Copies duplication, queue-based bulk PDF generation, CSS watermark overlay

---

## Problem Statement

Three gaps in the current print pipeline for exam-day readiness:

1. **No copies control** — Bulk printing produces one copy per sheet. Exam rooms often need 2-3 copies per applicant (desk copy, file copy, posting copy).

2. **Synchronous bulk generation** — The current `printBulkPdf` and `printBulkAgnosticPdf` methods render all sheets synchronously in a single request. At 50-200 applicants with score lookups and template rendering, this hits PHP max execution time and gives no feedback to the user.

3. **No watermark** — Result sheets have no draft/final indicator. During exam periods, distinguishing working drafts from final printed sheets requires manual stamping.

---

## Architecture

### Approach: Integrated Service Extension

Extend the existing `ResultSheetPdfService` and `ResultSheetTemplateService` with new methods. Add a queued job and a `PrintJob` model for batch processing. No new architectural layers — the existing pipeline structure is clean and deserves direct extension.

```
Controller → dispatches GenerateBulkResultSheetPdf job
              ↓
         PrintJob (database record, tracks progress)
              ↓
         Job chunks applicants (10 per batch)
              ↓
         ResultSheetTemplateService::render() / renderDual()
              ↓
         ResultSheetPdfService (applies copies + watermark)
              ↓
         Stores PDF to disk → marks PrintJob completed
              ↓
         UI polls PrintJob status → download when ready
```

---

## Feature 1: Copies

### Behavior

| Mode | Input | Output |
|------|-------|--------|
| Full-page | 1 applicant × 3 copies | 3 PDF pages (same content) |
| Crosswise | 2 applicants × 3 copies | 3 PDF pages (each page has both halves) |
| Crosswise | 5 applicants × 2 copies | 3 pages × 2 = 6 pages (last page bottom half blank) |

Copies duplicate at the **sheet** level, not the applicant level. A "sheet" is one rendered HTML blob (1 full-page applicant or 2 crosswise applicants). Each sheet is repeated N times before combining into the final PDF.

### Service Changes

`ResultSheetPdfService` — add `$copies` parameter to bulk methods:

```php
public function bulkInline(array $sheetsHtml, RenderResult $meta, string $filename = 'result-sheets.pdf', int $copies = 1): Response
public function bulkDownload(array $sheetsHtml, RenderResult $meta, string $filename = 'result-sheets.pdf', int $copies = 1): Response
```

Implementation: before calling `combineSheets()`, expand the array with **per-sheet interleaving** so each applicant's N copies are collated together (not all applicants printed once then repeated):

```php
$expanded = [];
foreach ($sheetsHtml as $sheet) {
    for ($i = 0; $i < $copies; $i++) {
        $expanded[] = $sheet;
    }
}
```

> ⚠️ **Do NOT use `array_merge($expanded, $sheetsHtml)` in a loop** — that produces uncollated output (all copies of Sheet 1 printed after all copies of Sheet 2), which is wrong for exam-day use.

### Controller Changes

`printBulkPdf` and `printBulkAgnosticPdf` accept `?copies=N` query param:
- Validated as integer, 1-10 range, default 1
- Passed through to `ResultSheetPdfService`

### UI Changes

Add "Copies" number input to `PrintBatch.svelte`:
- Positioned next to paper size selector
- Min: 1, Max: 10, Default: 1
- Passed as query param when requesting bulk PDF

### Tests

- Unit: `ResultSheetPdfService` with copies=3 produces correct page count
- Unit: copies=1 is a no-op (backward compatible)
- Feature: full-page 1 applicant × 3 copies = 3 pages
- Feature: crosswise 2 applicants × 2 copies = 2 pages
- Feature: copies param validation (rejects 0, 11, non-integer)

---

## Feature 2: Watermarks

### Approach: CSS Overlay

The watermark is a positioned `<div>` overlaid on each sheet HTML before PDF generation. Spatie/Chromium renders CSS positioning correctly, so no PDF engine manipulation is needed.

### Schema Change

```sql
ALTER TABLE result_sheet_templates
  ADD COLUMN watermark_text VARCHAR(50) NULL AFTER docx_path;
```

Nullable — null means no watermark. This is a per-template setting, not a per-print setting.

### RenderResult Extension

Add `?string $watermarkText` to `RenderResult`:

```php
public function __construct(
    public readonly string $html,
    public readonly string $mode,
    public readonly string $paperSize,
    public readonly string $orientation,
    public readonly string $logicalUnit,
    public readonly ?string $watermarkText = null,
) {}
```

**Also update `fromTemplate()` to read the new column** (required — this is a named constructor and must be updated explicitly):

```php
public static function fromTemplate(ResultSheetTemplate $template, string $html = ''): self
{
    return new self(
        html: $html,
        mode: $template->mode,
        paperSize: $template->paper_size ?? ResultSheetTemplate::PAPER_A4,
        orientation: $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT,
        logicalUnit: $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL,
        watermarkText: $template->watermark_text,  // new
    );
}
```

### Watermark CSS

Injected by `ResultSheetPdfService::builder()` when watermark is present:

```html
<div class="watermark-overlay">
  <span>DRAFT</span>
</div>
```

```css
.watermark-overlay {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-45deg);
  font-size: 4rem;
  color: rgba(200, 200, 200, 0.4);
  pointer-events: none;
  z-index: 9999;
  white-space: nowrap;
  user-select: none;
}
```

Fixed positioning ensures the watermark covers the entire page in the PDF output. The semi-transparent gray renders behind text content in practice.

### UI Changes

- "Watermark" text input on `ResultSheetTemplates/Create.svelte` and `Edit.svelte`
- Optional field, max 50 characters
- Preview shows the watermark overlay on the rendered template

### Tests

- Unit: `RenderResult::fromTemplate()` carries watermark_text
- Unit: `ResultSheetPdfService` injects watermark HTML when present
- Unit: no watermark HTML injected when watermark_text is null
- Feature: template with watermark produces PDF containing watermark text

---

## Feature 3: Bulk PDF Batching (Queue-based)

### New Model: PrintJob

```
print_jobs
├── id                UUID primary key
├── user_id           FK → users (who requested it)
├── grading_session_id  nullable FK → grading_sessions (null for agnostic mode)
├── applicant_ids     JSON array of integers
├── copies            INT default 1
├── status            ENUM: pending, processing, completed, failed
├── progress          INT 0-100 (percentage)
├── pdf_path          nullable VARCHAR (relative path in storage)
├── error_message     nullable TEXT
├── created_at        TIMESTAMP
├── updated_at        TIMESTAMP
```

PDF stored on disk at `print-jobs/{uuid}.pdf` — keeps the table lightweight and allows direct file serving.

### New Job: GenerateBulkResultSheetPdf

```php
class GenerateBulkResultSheetPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $printJobId,
    ) {}

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes max

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
                // NOTE: Use $templateService->buildSheetsForApplicants() — see architecture note below.
                // This must NOT duplicate the fetch+render logic from ReleasePrintController.
                $sheetsHtml = array_merge(
                    $sheetsHtml,
                    $templateService->buildSheetsForApplicantIds($chunk, $template, $printJob->grading_session_id)
                );
                $progress = (int) round((($i + 1) * $chunkSize / $total) * 100);
                $printJob->update(['progress' => min($progress, 99)]);
            }

            // Generate PDF with copies
            $path = "print-jobs/{$printJob->id}.pdf";
            $pdfContent = $pdfService->generateBulkPdfContent(
                $sheetsHtml, $meta, $printJob->copies
            );
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

### New Service Methods

**`ResultSheetPdfService`** — add a method that returns PDF content as string (for disk storage):

```php
public function generateBulkPdfContent(array $sheetsHtml, RenderResult $meta, int $copies = 1): string
```

This is the same logic as `bulkDownload()` but returns the raw PDF bytes instead of a response.

**`ResultSheetTemplateService`** — extract shared sheet-building logic from the controller into the service so the job and controller share the same code path:

```php
/**
 * Fetch applicants + scores for the given IDs, render into sheet HTML blobs.
 * When $gradingSessionId is null, resolves the best session per applicant (agnostic mode).
 *
 * @param  int[]  $applicantIds
 * @return string[]  One HTML blob per logical sheet
 */
public function buildSheetsForApplicantIds(
    array $applicantIds,
    ResultSheetTemplate $template,
    ?int $gradingSessionId = null,
): array
```

> ⚠️ **Architecture note:** The controller currently has `buildSheetsFromApplicants()` and `buildApplicantData()` as private helpers. These must be **extracted to `ResultSheetTemplateService`** as part of this phase. If they remain private to the controller, the job will either duplicate the logic (maintenance fork) or call the controller (wrong layer). Extraction is required — not optional.

### Routes

| Method | Route | Purpose |
|--------|-------|---------|
| POST | `/admin/release/print/bulk-pdf-job` | Dispatch bulk PDF generation job |
| GET | `/admin/release/print/print-job/{printJob}` | Poll job status + progress |
| GET | `/admin/release/print/print-job/{printJob}/download` | Download completed PDF |

### UI Flow (PrintBatch.svelte)

1. User selects applicants, sets copies, clicks "Generate PDF"
2. POST to `/bulk-pdf-job` → returns `{ jobId }`
3. UI shows progress bar, polls `/print-job/{jobId}` every 2 seconds
4. When `status === 'completed'`, shows "Download" button linking to `/print-job/{jobId}/download`
5. When `status === 'failed'`, shows error message with retry option

### Queue Configuration

- Driver: `database` (confirmed)
- Job timeout: 300 seconds (5 minutes)
- Max tries: 3
- Chunk size: 10 applicants per batch for progress updates

### Cleanup

Artisan command `app:cleanup-print-jobs` to purge completed jobs older than 24 hours. Can be scheduled via `routes/console.php`.

### Tests

- Unit: `GenerateBulkResultSheetPdf` job processes applicants and stores PDF
- Unit: job updates progress correctly
- Unit: job handles failure and stores error message
- Feature: POST `/bulk-pdf-job` creates PrintJob and dispatches job
- Feature: GET `/print-job/{id}` returns correct status and progress
- Feature: GET `/print-job/{id}/download` returns PDF when completed
- Feature: download returns 404 when job is still processing

---

## Summary of All Changes

### New Files
- `app/Jobs/GenerateBulkResultSheetPdf.php`
- `app/Models/PrintJob.php`
- `database/migrations/2026_05_16_000001_create_print_jobs_table.php`
- `database/migrations/2026_05_16_000002_add_watermark_text_to_result_sheet_templates_table.php`

### Modified Files
- `app/Services/ResultSheetPdfService.php` — copies (interleaved expansion) + watermark injection + `generateBulkPdfContent()`
- `app/Services/ResultSheetTemplateService.php` — new `buildSheetsForApplicantIds()` method (extracted from controller)
- `app/ValueObjects/RenderResult.php` — `$watermarkText` property + `fromTemplate()` update
- `app/Http/Controllers/Release/ReleasePrintController.php` — copies param + job dispatch + job status routes; refactor private helpers to delegate to `ResultSheetTemplateService`
- `resources/js/Pages/Release/PrintBatch.svelte` — copies input + progress bar + job flow
- `resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte` — watermark field
- `resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte` — watermark field
- `routes/web.php` — new routes for job dispatch/status/download

### Backward Compatibility
- All new parameters have defaults (copies=1, watermark=null)
- Existing sync `printBulkPdf` route remains functional for small batches
- The queue path is opt-in via the new `/bulk-pdf-job` route

### Deferred to Phase 5
- Template versioning (snapshot + rollback)
- Print job audit log (compliance tracking)
