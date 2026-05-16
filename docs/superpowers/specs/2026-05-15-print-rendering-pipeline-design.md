# Print Rendering Pipeline — Design Spec

**Date:** 2026-05-15
**Status:** Approved
**Scope:** Result sheet template rendering, PDF-first output, crosswise layout fix

---

## Problem Statement

Two core issues in the current print pipeline:

1. **Crosswise/half-page layout is broken** — In HTML mode, two applicant sheets stacked vertically don't divide the page accurately. The controller concatenates two independently-wrapped HTML blobs (`$html1 . $html2`), each with its own `<style>` and `<div class="print-template">`, creating layout conflicts inside the flex container.

2. **No accurate print/PDF output** — The current approach relies on browser `window.print()` with `@page` hardcoded to A4. There's no server-side PDF generation, so output varies by browser and the crosswise division is unreliable.

Secondary issues:
- `@page { size }` is hardcoded to A4 regardless of template settings
- No "copies" input for bulk printing
- `_2` placeholder fields have unclear dual meaning (second applicant vs. copy area)
- DOCX-to-HTML preview quality is poor (kept as approximate, no DOCX download needed)

---

## Architecture

### Single Output Pipeline: PDF-First

Both HTML and DOCX template modes now produce PDF as the primary output. The browser preview page shows the PDF inline (via `<iframe>` or `<embed>`). Users print from the PDF viewer, which gives consistent output regardless of browser.

```
┌───────────────────────────────────────────────────┐
│                   Template Mode                     │
├───────────────────┬───────────────────────────────┤
│      HTML         │          DOCX                  │
├───────────────────┼───────────────────────────────┤
│ Server renders    │ Server renders via             │
│ HTML with scoped  │ PHPWord TemplateProcessor,     │
│ Tailwind CSS      │ converts to HTML for preview   │
│                   │                                │
├───────────────────┼───────────────────────────────┤
│ Output:           │ Output:                        │
│  PDF (Spatie)     │  PDF (Spatie, from HTML)       │
│  Browser fallback │  Browser fallback              │
│  (for preview)    │  (for preview)                 │
└───────────────────┴───────────────────────────────┘
```

### PDF Generation Pipeline

```
Template → ResultSheetTemplateService::render()
  → PrintTemplateCssService::wrap() or wrapDual()
  → HTML string (scoped Tailwind)
  → ResultSheetPdfService::generatePdf()
  → Spatie generates PDF with format() + landscape()
  → Streamed to browser as inline PDF or download
```

For DOCX mode, the HTML conversion from PHPWord still feeds into Spatie PDF for PDF generation.

---

## Service Refactoring

### Current Services (Before)

- `ResultSheetTemplateService` — renders both HTML and DOCX, includes placeholder logic, sample data, and CSS wrapping. Returns raw HTML strings.
- `PrintTemplateCssService` — loads and scopes Tailwind CSS
- `PrintBatchService` — marks applicants as printed (trivial)

### Refactored Services (After)

```
PrintTemplateCssService (enhanced)
  ├── wrap(html) → scoped HTML (single applicant)
  ├── wrapDual(html1, html2) → scoped HTML (two applicants, CSS Grid 50/50)
  └── getScopedCss() → scoped CSS string (cached)

ResultSheetTemplateService (refactored)
  ├── render(template, applicants, useSampleData) → RenderResult
  ├── renderDual(template, applicant1, applicant2, useSampleData) → RenderResult
  ├── renderHtmlContent(content, applicants, useSampleData) → RenderResult
  ├── renderDocxFile(path, replacements, useSampleIfEmpty) → RenderResult
  │
  │   RenderResult carries its own metadata (paperSize, orientation, logicalUnit)
  │
  ├── buildApplicantData(session, applicantIds) → array
  ├── buildScoresRows(scores) → string (HTML rows)
  └── sampleApplicantData() → array

ResultSheetPdfService (new)
  ├── generatePdf(html, paperSize, orientation) → StreamedResponse
  ├── generateBulkPdf(sheets, paperSize, orientation, logicalUnit) → StreamedResponse
  └── previewDimensions(paperSize, orientation, logicalUnit) → array

ResultSheetDocxService (new — extracted from ResultSheetTemplateService)
  ├── renderDocxPreview(template, applicants, useSampleData) → RenderResult
  └── validateDocxTemplate(template) → array{valid, missingPlaceholders}

PrintBatchService (unchanged)
  └── markPrinted(session, ids, printed)
```

### RenderResult Value Object

Instead of returning raw HTML strings, services now return a structured result:

```php
class RenderResult
{
    public function __construct(
        public readonly string $html,
        public readonly string $mode, // 'html' | 'docx'
        public readonly string $paperSize, // 'a4' | 'letter' | 'legal'
        public readonly string $orientation, // 'portrait' | 'landscape'
        public readonly string $logicalUnit, // 'full' | 'half_a4' | 'half_letter' | 'half_legal'
    ) {}
}
```

This eliminates the need for controllers to separately pass `paperSize`, `orientation`, and `logicalUnit` alongside the HTML — the render result carries its own metadata.

---

## Crosswise/Half-Page Layout Fix

### Problem

Current code concatenates two independently-wrapped HTML blocks:

```php
// Current: two separate wrap() calls produce two <style> + <div class="print-template"> blocks
$html1 = $this->templateService->render($template, [$chunk[0]], false);
$html2 = $this->templateService->render($template, [$chunk[1]], false);
$sheetsHtml[] = $html1 . $html2;
```

In Svelte, these are inside a flex container with `flex: 0 0 148.5mm` per child, but each `<style>` tag and `.print-template` wrapper creates layout noise.

### Solution

Add a `wrapDual()` method to `PrintTemplateCssService` that wraps two applicant blocks in a single container with shared CSS:

```php
public function wrapDual(string $html1, string $html2): string
{
    $css = $this->getScopedCss();
    return "<style>{$css}</style>\n"
        . "<div class=\"print-template print-template--dual\">\n"
        . "  <div class=\"print-template--half\">{$html1}</div>\n"
        . "  <div class=\"print-template--half\">{$html2}</div>\n"
        . "</div>";
}
```

And in `print-template.css`, add:

```css
.print-template--dual {
  display: grid;
  grid-template-rows: 1fr 1fr;
  height: 100%;
}

.print-template--half {
  overflow: hidden;
}
```

For PDF output, Chromium (Spatie) respects CSS Grid and will split the page correctly.

The `ResultSheetTemplateService::renderDual()` method renders two applicants in one call, returning a single `RenderResult` with the dual-wrapped HTML. No more raw concatenation.

### Controller Refactoring

The `printBulk` and `printBulkAgnostic` methods currently do:

```php
if (count($chunk) === 2) {
    $html1 = $this->templateService->render($template, [$chunk[0]], false);
    $html2 = $this->templateService->render($template, [$chunk[1]], false);
    $sheetsHtml[] = $html1 . $html2; // BUG: concatenating wrapped HTML
}
```

After refactoring:

```php
if (count($chunk) === 2) {
    $result = $this->templateService->renderDual($template, $chunk[0], $chunk[1], false);
    $sheetsHtml[] = $result->html; // Single wrapped container, accurate 50/50 split
} else {
    $result = $this->templateService->render($template, $chunk, false);
    $sheetsHtml[] = $result->html;
}
```

---

## DOCX Mode

### No DOCX Download

DOCX files remain as **template sources only** — they are not downloadable outputs. The user uploads a DOCX template, the system replaces placeholders, converts to HTML, and generates a PDF for preview and printing.

The current PHPWord HTML preview produces approximate output. This is acceptable with a clear disclaimer in the UI: "Preview is approximate. The PDF output may differ slightly from the original DOCX layout."

### Template Validation

New `validateDocxTemplate()` method checks:
- The uploaded DOCX contains required `{{placeholder}}` macros
- In crosswise mode, `{{applicant_name_2}}` (or any `_2` field) exists
- Returns a list of missing placeholders for the admin to fix

---

## Copies Feature

### UI

Add a "Copies" input to the print batch and bulk print pages:

```
[ Paper: A4 ▼ ]  [ Scale: 100% ▼ ]  [ Copies: 1 ▼ ]
```

### Behavior

- **Full-page mode:** Each copy is a separate PDF page. 1 applicant × 3 copies = 3 pages.
- **Crosswise mode:** Each copy is a separate PDF page with both halves filled. 2 applicants × 3 copies = 3 pages (not 6). If odd number of applicants, the last page's bottom half is blank.
- **PDF generation:** Spatie duplicates pages in the PDF based on the copies parameter.

### Server-Side

The `printBulk` and `printBulkAgnostic` methods accept a `copies` query parameter (default 1). The PDF generation service duplicates pages accordingly.

---

## _2 Fields: Dual Meaning Clarification

| Mode | Logical Unit | `applicant_2` / `_2` fields | Bottom half behavior |
|------|-------------|---------------------------|---------------------|
| HTML | full | Not used | Left empty (or omitted) |
| HTML | half_* | Second applicant in pair | Second applicant |
| DOCX | full | Left blank by default | Empty bottom half |
| DOCX | half_* | Second applicant in pair | Second applicant |

No `copy_area` field — copying is handled by the "Copies" input, not by `_2` placeholder mirroring.

---

## Routes

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `/admin/release/print/{gs}/applicants/{applicant}/pdf` | Single applicant PDF |
| GET | `/admin/release/print/{gs}/print-bulk-pdf` | Bulk PDF with copies |
| GET | `/admin/release/print/bulk-pdf` | Agnostic bulk PDF |
| *existing* | `/admin/release/print/{gs}/applicants/{applicant}` | Result sheet HTML preview (kept) |
| *existing* | `/admin/release/print/{gs}/print-bulk` | Bulk print HTML preview (kept) |

Browser preview pages remain as fallback. PDF routes are the primary output.

---

## Dynamic @page CSS

The `@page { size: A4 portrait; margin: 0; }` rule is currently hardcoded in the Svelte `<style>` block. It must reflect the template's `paper_size` and `orientation` settings.

### Svelte Fix

```svelte
<svelte:head>
  {@html `<style>@media print { @page { size: ${paperSize} ${orientation}; margin: 0; } }</style>`}
</svelte:head>
```

### Snappy PDF

```php
$pdf = \Spatie\LaravelPdf\Facades\Pdf::view('pdf.template', ['html' => $html])
    ->format($paperSize)
    ->landscape($orientation === 'landscape')
    ->margins(0, 0, 0, 0);
```

---

## Package Dependencies

### New

- `spatie/laravel-pdf` — Laravel wrapper for Chromium/Puppeteer PDF generation

### Existing (no changes)

- `barryvdh/laravel-dompdf` — Kept for admission slip PDFs (already working)
- `phpoffice/phpword` — Kept for DOCX template processing (no download, preview only)

---

## Implementation Phases

### Phase 1: Crosswise Layout Fix + RenderResult + Dynamic @page
- Add `wrapDual()` to `PrintTemplateCssService`
- Add dual layout CSS (`.print-template--dual`, `.print-template--half`) to `print-template.css`
- Add `RenderResult` value object
- Refactor `ResultSheetTemplateService` to return `RenderResult` (add `renderDual()`)
- Update controllers (`ReleasePrintController`, `GradingPrintController`, `ResultSheetTemplateController`) to use `RenderResult`
- Fix `ResultSheetBulk.svelte` and `ResultSheet.svelte` to use dynamic `@page`
- Add `unwrap()` helper or `renderRaw()` to get inner HTML for dual wrapping without double-wrapping

### Phase 2: PDF Export (Spatie)
- Install `spatie/laravel-pdf` (and configure Puppeteer)
- Create `ResultSheetPdfService`
- Add PDF routes and controller methods
- Add "View PDF" / "Download PDF" buttons to Svelte pages
- Configure Spatie for zero margins + template paper size/orientation

### Phase 3: DOCX Service Extraction + Validation
- Extract `ResultSheetDocxService` from `ResultSheetTemplateService`
- Add `validateDocxTemplate()` method
- Add validation feedback to template editor (missing placeholders warning)
- Add "Preview is approximate" disclaimer to DOCX preview UI

### Phase 4: Copies Feature
- Add "Copies" input to `PrintBatch.svelte` and `ResultSheetBulk.svelte`
- Update controller to accept `copies` parameter
- Implement copy duplication in `ResultSheetPdfService`

---

## Testing Strategy

- Unit tests for `RenderResult` value object
- Unit tests for `ResultSheetPdfService::generatePdf()` with different paper sizes
- Unit tests for `ResultSheetDocxService` placeholder replacement
- Feature test: crosswise layout renders two applicants on one PDF page
- Feature test: full-page layout renders one applicant per page
- Feature test: copies parameter duplicates pages correctly
- Visual regression: compare PDF output against expected fixture PDFs