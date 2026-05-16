# Phase 2: PDF Export — Implementation Plan
# Library: spatie/laravel-pdf (Chrome driver)

**Date:** 2026-05-15  
**Spec:** `docs/superpowers/specs/2026-05-15-print-rendering-pipeline-design.md`  
**Phase 1 status:** ✅ Done (`RenderResult`, `renderDual`, dynamic `@page`)

---

## Why spatie/laravel-pdf over Snappy

| Concern | Snappy (rejected) | spatie/laravel-pdf (chosen) |
|---|---|---|
| Rendering engine | wkhtmltopdf (ancient WebKit 534, ~2013) | Chromium — same as user's browser |
| CSS Grid / Tailwind support | Partial / broken | Full |
| Active maintenance | Abandoned by maintainer | Actively maintained by Spatie |
| Windows / Laragon | Binary download required | Uses installed Google Chrome |
| Docker dependency | Optional binary | **None** — system Chrome |

---

## Driver Choice: `chrome-php/chrome`

`spatie/laravel-pdf` supports multiple drivers. We use the **Chrome driver**:

- Spawns headless Chrome as a subprocess via `chrome-php/chrome`
- No Docker, no Puppeteer/Node, no extra services
- On Windows/Laragon: points to the installed Google Chrome binary via `.env`
- On production (Laragon server): same — Chrome must be installed once

> **Not using:** Browsershot (requires Node + Puppeteer), Gotenberg (requires Docker), DOMPDF (poor CSS support).

---

## Architecture

```
ResultSheetTemplateService::render() / renderDual()
  → RenderResult (html, paperSize, orientation, logicalUnit)
  → ResultSheetPdfService::generatePdf(RenderResult)
      → Pdf::html($result->html)
           ->format($result->paperSize)
           ->landscape($result->orientation === 'landscape')
           ->margins(0, 0, 0, 0)
      → inline() or download()
  → StreamedResponse to browser
```

Existing HTML preview routes are **kept** — PDF routes are additive (`-pdf` suffix).

---

## Tasks

### Task 1 — Install `spatie/laravel-pdf` + Chrome driver

**Files touched:** `composer.json`, `config/laravel-pdf.php`, `.env`, `.env.example`

```bash
composer require spatie/laravel-pdf chrome-php/chrome --ignore-platform-req=ext-zip
php artisan vendor:publish --tag=laravel-pdf-config
```

**`config/laravel-pdf.php`** (published, then edit):
```php
'driver' => env('PDF_DRIVER', 'chrome'),

'drivers' => [
    'chrome' => [
        'driver' => \Spatie\LaravelPdf\Drivers\ChromeDriver::class,
        'chrome_binary' => env('CHROME_BINARY', null), // null = auto-detect
    ],
],
```

**`.env` additions:**
```env
PDF_DRIVER=chrome
CHROME_BINARY="C:/Program Files/Google/Chrome/Application/chrome.exe"
```

**`.env.example` additions:**
```env
PDF_DRIVER=chrome
# Windows/Laragon: path to chrome.exe
CHROME_BINARY="C:/Program Files/Google/Chrome/Application/chrome.exe"
```

**Verification:** `php artisan tinker --execute '\Spatie\LaravelPdf\Facades\Pdf::html("<h1>Test</h1>")->save(storage_path("test.pdf")); echo "ok";'`

---

### Task 2 — Create `ResultSheetPdfService`

**File:** `app/Services/ResultSheetPdfService.php`

```php
<?php

namespace App\Services;

use App\ValueObjects\RenderResult;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultSheetPdfService
{
    /**
     * Stream a single-applicant result sheet PDF inline (for "View PDF").
     */
    public function inline(RenderResult $result, string $filename = 'result-sheet.pdf'): StreamedResponse
    {
        return $this->builder($result)->inline($filename);
    }

    /**
     * Download a single-applicant result sheet PDF (for "Download PDF").
     */
    public function download(RenderResult $result, string $filename = 'result-sheet.pdf'): StreamedResponse
    {
        return $this->builder($result)->download($filename);
    }

    /**
     * Stream a multi-applicant bulk PDF inline, with page breaks between sheets.
     *
     * @param  string[]  $sheetsHtml  One HTML blob per logical sheet.
     */
    public function bulkInline(array $sheetsHtml, RenderResult $meta, string $filename = 'result-sheets.pdf'): StreamedResponse
    {
        return $this->builder($meta, $this->combineSheets($sheetsHtml))->inline($filename);
    }

    /**
     * Download a multi-applicant bulk PDF.
     *
     * @param  string[]  $sheetsHtml  One HTML blob per logical sheet.
     */
    public function bulkDownload(array $sheetsHtml, RenderResult $meta, string $filename = 'result-sheets.pdf'): StreamedResponse
    {
        return $this->builder($meta, $this->combineSheets($sheetsHtml))->download($filename);
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

    private function builder(RenderResult $meta, ?string $html = null): \Spatie\LaravelPdf\PdfBuilder
    {
        $html ??= $meta->html;

        $builder = Pdf::html($html)
            ->format($meta->paperSize)
            ->margins(0, 0, 0, 0);

        if ($meta->orientation === 'landscape') {
            $builder->landscape();
        }

        return $builder;
    }

    private function combineSheets(array $sheetsHtml): string
    {
        return implode('<div style="page-break-after: always;"></div>', $sheetsHtml);
    }
}
```

**Unit tests:** `tests/Unit/ResultSheetPdfServiceTest.php`

Test cases:
- `previewDimensions` returns correct mm for A4 portrait
- `previewDimensions` halves height for `half_a4` logical unit
- `previewDimensions` swaps width/height for landscape
- Mocked `Pdf` facade: `inline()` and `download()` called with correct format/orientation/margins

---

### Task 3 — DRY refactor + add PDF methods to `ReleasePrintController`

**File:** `app/Http/Controllers/Release/ReleasePrintController.php`

**3a. Inject `ResultSheetPdfService`** into constructor:
```php
public function __construct(
    private PrintBatchService $printService,
    private ResultSheetTemplateService $templateService,
    private ResultSheetPdfService $pdfService,
) {}
```

**3b. Extract shared private helpers** (currently the name/score mapping is duplicated 3x):
```php
private function formatName(Applicant $applicant): string { ... }
private function buildApplicantData(Applicant $applicant, GradingSession $session, Collection $scores): array { ... }
private function buildSheetsFromApplicants(array $applicantsWithScores, ResultSheetTemplate $template): array { ... }
```

**3c. Add three new public methods:**
- `resultSheetPdf(GradingSession, Applicant): StreamedResponse` — inline single PDF
- `printBulkPdf(GradingSession): StreamedResponse` — download bulk PDF
- `printBulkAgnosticPdf(): StreamedResponse` — download cross-session bulk PDF

All three reuse the extracted private helpers, so zero new data-fetching logic is introduced.

---

### Task 4 — Register PDF routes

**File:** `routes/web.php`

```php
// ⚠ Register agnostic routes BEFORE session-scoped routes
Route::get('/admin/release/print/bulk-pdf',    [ReleasePrintController::class, 'printBulkAgnosticPdf'])->name('release.print.bulk-agnostic-pdf');

Route::get('/admin/release/print/{grading_session}/applicants/{applicant}/pdf', [ReleasePrintController::class, 'resultSheetPdf'])->name('release.print.result-sheet-pdf');
Route::get('/admin/release/print/{grading_session}/print-bulk-pdf',             [ReleasePrintController::class, 'printBulkPdf'])->name('release.print.bulk-pdf');
```

---

### Task 5 — Svelte UI integration

Three pages gain "View PDF" / "Download PDF" links. The current `window.print()` button is kept as a fallback, not removed.

**`ResultSheet.svelte`** — add below the existing print button:
```svelte
<a href={route('release.print.result-sheet-pdf', { grading_session: sessionId, applicant: applicantId })}
   target="_blank">
  View PDF
</a>
```

**`PrintBatch.svelte`** — add next to "Print" action:
```svelte
<a href={route('release.print.bulk-pdf', { grading_session: sessionId }) + '?ids=' + selectedIds.join(',')}>
  Download PDF
</a>
```

**`ResultSheetBulk.svelte`** — add Download PDF link using the agnostic route.

---

### Task 6 — Feature tests

Add to `tests/Feature/ReleasePrintControllerTest.php`:

- `test_result_sheet_pdf_returns_inline_pdf_for_valid_applicant`
- `test_result_sheet_pdf_returns_404_for_applicant_not_in_session`
- `test_print_bulk_pdf_returns_downloadable_pdf`
- `test_print_bulk_agnostic_pdf_returns_downloadable_pdf`

Use `Pdf::fake()` to avoid actually spawning Chrome during tests.

---

### Task 7 — Deployment documentation

Append to `docs/deployment/installation-pathway-spec.md`:

```markdown
## Chrome PDF Dependency (Result Sheet PDF Export)

SecureCAT uses `spatie/laravel-pdf` (Chrome driver) to generate server-side PDFs.
The server must have Google Chrome installed. No Docker or extra services needed.

### Install
Download from https://www.google.com/chrome/ and install normally.

### Configure `.env`
PDF_DRIVER=chrome
CHROME_BINARY="C:/Program Files/Google/Chrome/Application/chrome.exe"

### Verify
php artisan tinker --execute '\Spatie\LaravelPdf\Facades\Pdf::html("<h1>ok</h1>")->save(storage_path("test.pdf")); echo "saved";'

### Troubleshooting
- "Chrome binary not found" → verify CHROME_BINARY path
- Blank PDF → add --no-sandbox to chrome options in config/laravel-pdf.php
- Timeout → increase timeout value in chrome driver config
```

---

## Execution Checklist

- [ ] Task 1: Install packages, publish + configure `config/laravel-pdf.php`, update `.env.*`
- [ ] Task 2: Write `ResultSheetPdfService` + unit tests (green before moving on)
- [ ] Task 3: DRY refactor controller + add 3 PDF methods
- [ ] Task 4: Register routes (ordering matters)
- [ ] Task 5: Update 3 Svelte pages
- [ ] Task 6: Feature tests (use `Pdf::fake()`)
- [ ] Task 7: Append Chrome section to deployment doc
- [ ] `vendor/bin/pint --dirty` — no formatting issues
- [ ] `php artisan test --compact` — all green
