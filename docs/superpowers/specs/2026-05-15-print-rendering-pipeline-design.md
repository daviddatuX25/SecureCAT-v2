# Print Rendering Pipeline — Design Spec

**Date:** 2026-05-15
**Status:** Draft
**Scope:** Result sheet template rendering, PDF export, DOCX download, crosswise layout fix

---

## Problem Statement

Two core issues in the current print pipeline:

1. **Crosswise/half-page layout is broken** — In HTML mode, two applicant sheets stacked vertically don't divide the page accurately. The controller concatenates two independently-wrapped HTML blobs (`$html1 . $html2`), each with its own `<style>` and `<div class="print-template">`, creating layout conflicts inside the flex container.

2. **DOCX preview quality is poor** — PHPWord's HTML writer produces basic inline styles that lose layout fidelity. There's no direct DOCX download; the DOCX is only used as a template source for an inferior HTML conversion.

Secondary issues:
- `@page { size }` is hardcoded to A4 regardless of template settings
- No "copies" input for bulk printing
- `_2` placeholder fields have unclear dual meaning (second applicant vs. copy area)
- No PDF export option at all

---

## Architecture

### Rendering Modes

The system supports two template modes that now produce output through distinct pipelines:

```
┌─────────────────────────────────────────────────┐
│                  Template Mode                    │
├──────────────────┬──────────────────────────────┤
│     HTML         │         DOCX                  │
├──────────────────┼──────────────────────────────┤
│ Server renders   │ Server renders via            │
│ HTML with        │ PHPWord TemplateProcessor,    │
│ scoped Tailwind  │ then:                         │
│ CSS              │  a) Download .docx directly   │
│                  │  b) Preview as HTML (fallback)│
├──────────────────┼──────────────────────────────┤
│ Output:          │ Output:                       │
│  - PDF (Snappy)  │  - .docx download             │
│  - Browser print │  - PDF (Snappy, from HTML)     │
│    (fallback)    │  - Browser print (fallback)   │
└──────────────────┴──────────────────────────────┘
```

### PDF Generation Pipeline

```
Template → ResultSheetTemplateService::render()
  → PrintTemplateCssService::wrap()
  → HTML string (scoped Tailwind)
  → Snappy generates PDF with setPaper() + setOrientation()
  → Streamed to browser
```

For DOCX mode, the preview HTML path also goes through Snappy for PDF output.

---

## Service Refactoring

### Current Services (Before)

- `ResultSheetTemplateService` — renders both HTML and DOCX, includes placeholder logic, sample data, and CSS wrapping
- `PrintTemplateCssService` — loads and scopes Tailwind CSS
- `PrintBatchService` — marks applicants as printed (trivial)

### Refactored Services (After)

```
PrintTemplateCssService (unchanged)
  └── wrap(html) → scoped HTML

ResultSheetTemplateService (refactored)
  ├── render(template, applicants, useSampleData) → RenderResult
  ├── renderHtmlContent(content, applicants, useSampleData) → RenderResult
  ├── renderDocxFile(path, replacements, useSampleIfEmpty) → RenderResult
  │
  │   RenderResult = { html: string, mode: 'html'|'docx', paperSize, orientation, logicalUnit }
  │
  ├── buildApplicantData(session, applicantIds) → array
  ├── buildScoresRows(scores) → string (HTML rows)
  └── sampleApplicantData() → array

ResultSheetPdfService (new)
  ├── generatePdf(html, paperSize, orientation) → StreamedResponse
  ├── generateBulkPdf(sheets, paperSize, orientation, logicalUnit) → StreamedResponse
  └── previewDimensions(paperSize, orientation, logicalUnit) → array

ResultSheetDocxService (new — extracted from ResultSheetTemplateService)
  ├── downloadDocx(template, applicants, useSampleData) → StreamedResponse
  ├── renderDocxPreview(template, applicants, useSampleData) → string (HTML)
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

For PDF output, Snappy respects CSS Grid and will split the page correctly.

The `ResultSheetTemplateService::render()` method gets a new `renderDual()` method that renders two applicants in one call, returning a single `RenderResult` with the dual-wrapped HTML. No more raw concatenation.

### Render Method Signatures

```php
// Single applicant (full page)
public function render(ResultSheetTemplate $template, array $applicants, bool $useSampleData = false): RenderResult

// Two applicants (crosswise/half-page)
public function renderDual(ResultSheetTemplate $template, array $applicant1, array $applicant2, bool $useSampleData = false): RenderResult

// Render from raw HTML (for preview)
public function renderHtmlContent(string $content, array $applicants = [], bool $useSampleData = true): RenderResult
```

---

## DOCX Mode Improvements

### Direct Download

New endpoint: `GET /admin/release/result-templates/{id}/download-docx`

This streams the processed DOCX file (with placeholders replaced) directly to the browser. No HTML conversion — the user gets an editable `.docx` file.

For crosswise DOCX templates, `applicant_2` fields are filled with the second applicant in the pair. For full-page DOCX templates, `_2` fields are left blank unless the "copy area" feature is enabled.

### Preview Quality

The current PHPWord HTML writer produces low-quality output. Two options:

1. **Keep PHPWord HTML preview as-is** — Accept the quality limitation and clearly label it "Preview (approximate). Download DOCX for accurate rendering."
2. **Generate a PDF preview** — Use Snappy to render a PDF directly from the DOCX-to-HTML conversion. Still approximate, but PDF output is more controlled than raw HTML.

**Decision:** Option 1 for now. The DOCX download is the authoritative output. Preview is a rough approximation with a clear disclaimer.

### Template Validation

New `validateDocxTemplate()` method checks:
- The uploaded DOCX contains required `{{placeholder}}` macros
- In crosswise mode, `{{applicant_name_2}}` (or any `_2` field) exists
- Returns a list of missing placeholders for the admin to fix

---

## Copies Feature

### UI

Add a "Copies" input to the print batch page (`PrintBatch.svelte`):

```
[ Paper: A4 ▼ ]  [ Scale: 100% ▼ ]  [ Copies: 1 ▼ ]
```

### Behavior

- **Full-page mode:** Each copy is a separate printed page. 1 applicant × 3 copies = 3 pages.
- **Crosswise mode:** Each copy is a separate printed page with both halves filled. 2 applicants × 3 copies = 3 pages (not 6). If odd number of applicants, the last page's bottom half is blank.
- **DOCX mode:** Each copy duplicates the content. If "copy area" is enabled for full-page mode, `_2` fields mirror the same applicant.

### Server-Side

The `printBulk` and `printBulkAgnostic` methods accept a `copies` query parameter (default 1). The PDF generation service duplicates pages accordingly.

---

## _2 Fields: Dual Meaning Clarification

| Mode | Logical Unit | `applicant_2` / `_2` fields | Bottom half behavior |
|------|-------------|---------------------------|---------------------|
| HTML | full | Not used | Left empty (or omitted) |
| HTML | half_* | Second applicant in pair | Second applicant |
| DOCX | full | Left blank by default | Empty bottom half |
| DOCX | full + copy_area | Same applicant as top | Duplicate of applicant |
| DOCX | half_* | Second applicant in pair | Second applicant |

A new `copy_area` boolean field on `ResultSheetTemplate` (default false) controls whether `_2` fields mirror the same applicant in full-page DOCX mode. This is a per-template setting.

---

## New/Modified Routes

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `/admin/release/print/{gs}/applicants/{applicant}/pdf` | Single applicant PDF |
| GET | `/admin/release/print/{gs}/print-bulk-pdf` | Bulk PDF with copies |
| GET | `/admin/release/print/bulk-pdf` | Agnostic bulk PDF |
| GET | `/admin/release/result-templates/{id}/download-docx` | Download processed DOCX |
| *existing* | `/admin/release/print/{gs}/applicants/{applicant}` | Result sheet (keep for browser preview) |
| *existing* | `/admin/release/print/{gs}/print-bulk` | Bulk print (keep for browser preview) |

Browser preview pages remain as-is but with fixed crosswise layout and dynamic `@page`. PDF routes are additive.

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
$pdf = SnappyPdf::loadHTML($html)
    ->setPaper($paperSize, $orientation)
    ->setOption('margin-top', '0mm')
    ->setOption('margin-right', '0mm')
    ->setOption('margin-bottom', '0mm')
    ->setOption('margin-left', '0mm');
```

---

## Package Dependencies

### New

- `barryvdh/laravel-snappy` — Laravel wrapper for wkhtmltopdf PDF generation
- `h4cc/wkhtmltopdf-amd64` (Linux) or `wemersonrv/wkhtmltopdf-windows` (Windows/Laragon) — Binary packages

### Existing (no changes)

- `barryvdh/laravel-dompdf` — Kept for admission slip PDFs (already working)
- `phpoffice/phpword` — Kept for DOCX template processing

---

## Implementation Phases

### Phase 1: Crosswise Layout Fix + Dynamic @page
- Add `wrapDual()` to `PrintTemplateCssService`
- Add dual layout CSS to `print-template.css`
- Fix `ResultSheetBulk.svelte` to use dynamic `@page`
- Add `RenderResult` value object
- Refactor `ResultSheetTemplateService` to return `RenderResult`
- Update controllers to use `RenderResult`
- Fix Svelte components to consume `RenderResult` metadata

### Phase 2: PDF Export (Snappy)
- Install `barryvdh/laravel-snappy`
- Create `ResultSheetPdfService`
- Add PDF routes and controller methods
- Add "Download PDF" and "Download Bulk PDF" buttons to Svelte pages
- Configure Snappy for zero margins + template paper size

### Phase 3: DOCX Improvements
- Extract `ResultSheetDocxService` from `ResultSheetTemplateService`
- Add `downloadDocx()` method (direct .docx download)
- Add `validateDocxTemplate()` method
- Add download route and button
- Improve preview with disclaimer label

### Phase 4: Copies + Copy Area
- Add `copy_area` column to `result_sheet_templates` table
- Add "Copies" input to `PrintBatch.svelte` and `ResultSheetBulk.svelte`
- Update controller to accept `copies` parameter
- Implement copy duplication in `ResultSheetPdfService` and `ResultSheetDocxService`

---

## Testing Strategy

- Unit tests for `RenderResult` value object
- Unit tests for `ResultSheetPdfService::generatePdf()` with different paper sizes
- Unit tests for `ResultSheetDocxService::downloadDocx()` placeholder replacement
- Feature test: crosswise layout renders two applicants on one PDF page
- Feature test: full-page layout renders one applicant per page
- Feature test: DOCX download replaces all placeholders correctly
- Feature test: copies parameter duplicates pages correctly
- Visual regression: compare PDF output against expected fixture PDFs