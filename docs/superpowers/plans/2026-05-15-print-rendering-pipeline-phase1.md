# Print Rendering Pipeline — Phase 1: Crosswise Layout Fix + RenderResult + Dynamic @page

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the broken crosswise/hhalf-page layout, introduce a `RenderResult` value object to carry template metadata alongside HTML, and make `@page` CSS dynamic based on template settings.

**Architecture:** Add `wrapDual()` to `PrintTemplateCssService` for proper 50/50 dual-applicant wrapping with CSS Grid. Introduce `RenderResult` value object so services return structured data instead of raw strings. Refactor `ResultSheetTemplateService` to return `RenderResult` and add a `renderDual()` method. Update all controllers and Svelte components to consume `RenderResult`. Fix `@page { size }` to be dynamic in Svelte components.

**Tech Stack:** Laravel 12, Svelte 5, PHP 8.4, Tailwind CSS v4, PHPUnit 11

---

## File Structure

| File | Action | Responsibility |
|------|--------|---------------|
| `app/ValueObjects/RenderResult.php` | Create | Value object carrying html, mode, paperSize, orientation, logicalUnit |
| `app/Services/PrintTemplateCssService.php` | Modify | Add `wrapDual()` method for crosswise layout |
| `app/Services/ResultSheetTemplateService.php` | Modify | Change return types to `RenderResult`, add `renderDual()`, add `renderRaw()` for unwrapped HTML |
| `app/Http/Controllers/Release/ReleasePrintController.php` | Modify | Use `RenderResult` instead of separate HTML/metadata props |
| `app/Http/Controllers/Admin/ResultSheetTemplateController.php` | Modify | Use `RenderResult` for preview endpoint |
| `resources/css/print-template.css` | Modify | Add `.print-template--dual` and `.print-template--half` CSS Grid rules |
| `resources/css/print-template-safelist.html` | Modify | Add dual-layout classes to safelist |
| `resources/js/Pages/Release/ResultSheet.svelte` | Modify | Dynamic `@page` from props, consume `RenderResult` metadata |
| `resources/js/Pages/Release/ResultSheetBulk.svelte` | Modify | Dynamic `@page` from props, use `RenderResult` metadata, fix crosswise layout |
| `tests/Feature/ReleasePrintControllerTest.php` | Modify | Update tests for `RenderResult` return type |
| `tests/Unit/RenderResultTest.php` | Create | Unit tests for `RenderResult` value object |
| `tests/Unit/ResultSheetTemplateServiceTest.php` | Create | Unit tests for `renderDual()` and `RenderResult` returns |
| `tests/Unit/PrintTemplateCssServiceTest.php` | Create | Unit tests for `wrapDual()` |

---

### Task 1: Create RenderResult Value Object

**Files:**
- Create: `app/ValueObjects/RenderResult.php`
- Create: `tests/Unit/RenderResultTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/RenderResultTest.php

namespace Tests\Unit;

use App\ValueObjects\RenderResult;
use PHPUnit\Framework\TestCase;

class RenderResultTest extends TestCase
{
    public function test_constructs_with_all_properties(): void
    {
        $result = new RenderResult(
            html: '<div>test</div>',
            mode: 'html',
            paperSize: 'a4',
            orientation: 'portrait',
            logicalUnit: 'full',
        );

        $this->assertSame('<div>test</div>', $result->html);
        $this->assertSame('html', $result->mode);
        $this->assertSame('a4', $result->paperSize);
        $this->assertSame('portrait', $result->orientation);
        $this->assertSame('full', $result->logicalUnit);
    }

    public function test_is_half_returns_true_for_half_units(): void
    {
        $halfA4 = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'half_a4');
        $halfLetter = new RenderResult(html: '', mode: 'html', paperSize: 'letter', orientation: 'portrait', logicalUnit: 'half_letter');
        $full = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');

        $this->assertTrue($halfA4->isHalf());
        $this->assertTrue($halfLetter->isHalf());
        $this->assertFalse($full->isHalf());
    }

    public function test_page_dimensions_returns_correct_mm(): void
    {
        $a4Portrait = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $legalLandscape = new RenderResult(html: '', mode: 'html', paperSize: 'legal', orientation: 'landscape', logicalUnit: 'full');
        $halfA4 = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'half_a4');

        $this->assertEquals(['width' => 210, 'height' => 297], $a4Portrait->pageDimensions());
        $this->assertEquals(['width' => 356, 'height' => 216], $legalLandscape->pageDimensions());
        $this->assertEquals(['width' => 210, 'height' => 148], $halfA4->pageDimensions());
    }

    public function test_css_page_size_returns_css_string(): void
    {
        $a4Portrait = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $letterLandscape = new RenderResult(html: '', mode: 'html', paperSize: 'letter', orientation: 'landscape', logicalUnit: 'full');

        $this->assertSame('a4 portrait', $a4Portrait->cssPageSize());
        $this->assertSame('letter landscape', $letterLandscape->cssPageSize());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/RenderResultTest.php`
Expected: FAIL — `Class "App\ValueObjects\RenderResult" not found`

- [ ] **Step 3: Write the RenderResult value object**

```php
<?php
// app/ValueObjects/RenderResult.php

namespace App\ValueObjects;

readonly class RenderResult
{
    private const PAGE_SIZES = [
        'a4' => ['width' => 210, 'height' => 297],
        'legal' => ['width' => 216, 'height' => 356],
        'letter' => ['width' => 216, 'height' => 279],
    ];

    public function __construct(
        public readonly string $html,
        public readonly string $mode,
        public readonly string $paperSize,
        public readonly string $orientation,
        public readonly string $logicalUnit,
    ) {}

    public function isHalf(): bool
    {
        return str_starts_with($this->logicalUnit, 'half_');
    }

    /**
     * @return array{width: int, height: int} Dimensions in mm
     */
    public function pageDimensions(): array
    {
        $dims = self::PAGE_SIZES[$this->paperSize] ?? self::PAGE_SIZES['a4'];

        if ($this->orientation === 'landscape') {
            $dims = ['width' => $dims['height'], 'height' => $dims['width']];
        }

        if ($this->isHalf()) {
            $dims['height'] = (int) ($dims['height'] / 2);
        }

        return $dims;
    }

    /**
     * Returns the CSS @page size string, e.g. "a4 portrait".
     */
    public function cssPageSize(): string
    {
        return "{$this->paperSize} {$this->orientation}";
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Unit/RenderResultTest.php`
Expected: PASS (4 tests)

- [ ] **Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/ValueObjects/RenderResult.php tests/Unit/RenderResultTest.php
git commit -m "feat: add RenderResult value object for print template metadata"
```

---

### Task 2: Add `wrapDual()` to PrintTemplateCssService

**Files:**
- Modify: `app/Services/PrintTemplateCssService.php`
- Create: `tests/Unit/PrintTemplateCssServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Unit/PrintTemplateCssServiceTest.php

namespace Tests\Unit;

use App\Services\PrintTemplateCssService;
use PHPUnit\Framework\TestCase;

class PrintTemplateCssServiceTest extends TestCase
{
    private PrintTemplateCssService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PrintTemplateCssService();
    }

    public function test_wrap_wraps_html_in_print_template_div(): void
    {
        $result = $this->service->wrap('<p>Hello</p>');

        $this->assertStringContainsString('<div class="print-template"><p>Hello</p></div>', $result);
        $this->assertStringContainsString('<style>', $result);
        $this->assertStringContainsString('@scope (.print-template)', $result);
    }

    public function test_wrap_dual_wraps_two_blocks_in_dual_container(): void
    {
        $result = $this->service->wrapDual('<p>Applicant 1</p>', '<p>Applicant 2</p>');

        $this->assertStringContainsString('print-template--dual', $result);
        $this->assertStringContainsString('print-template--half', $result);
        $this->assertStringContainsString('<p>Applicant 1</p>', $result);
        $this->assertStringContainsString('<p>Applicant 2</p>', $result);
        // Only one <style> block (shared CSS)
        $this->assertEquals(1, substr_count($result, '<style>'));
    }

    public function test_wrap_dual_contains_both_applicant_blocks(): void
    {
        $result = $this->service->wrapDual('<p>A1</p>', '<p>A2</p>');

        // The structure should be: <style>...</style>\n<div class="print-template print-template--dual">\n  <div class="print-template--half"><p>A1</p></div>\n  <div class="print-template--half"><p>A2</p></div>\n</div>
        $this->assertMatchesRegularExpression('/print-template--dual.*print-template--half.*A1.*print-template--half.*A2/s', $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/PrintTemplateCssServiceTest.php`
Expected: FAIL — `wrapDual` method does not exist

- [ ] **Step 3: Add `wrapDual()` method to PrintTemplateCssService**

Add this method after the existing `wrap()` method in `app/Services/PrintTemplateCssService.php`:

```php
/**
 * Wrap two applicant HTML blocks in a single dual-layout container with shared CSS.
 * Uses CSS Grid for accurate 50/50 vertical split.
 */
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

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Unit/PrintTemplateCssServiceTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Services/PrintTemplateCssService.php tests/Unit/PrintTemplateCssServiceTest.php
git commit -m "feat: add wrapDual() to PrintTemplateCssService for crosswise layout"
```

---

### Task 3: Add dual-layout CSS to print-template.css and safelist

**Files:**
- Modify: `resources/css/print-template.css`
- Modify: `resources/css/print-template-safelist.html`

- [ ] **Step 1: Add CSS Grid dual-layout rules to print-template.css**

Add after the existing `@layer base` block in `resources/css/print-template.css`:

```css
/* Dual-layout: two applicants on one page, 50/50 vertical split */
.print-template--dual {
    display: grid;
    grid-template-rows: 1fr 1fr;
    height: 100%;
}

.print-template--half {
    overflow: hidden;
}

/* Print-mode resets for dual layout */
@media print {
    .print-template--dual {
        grid-template-rows: 1fr 1fr;
        height: 100%;
        page-break-inside: avoid;
    }
}
```

- [ ] **Step 2: Add dual-layout classes to the safelist**

In `resources/css/print-template-safelist.html`, add inside the first `<div>` tag's `class` attribute:

```
print-template--dual print-template--half
```

So the first div's class list includes these two new classes alongside the existing ones.

- [ ] **Step 3: Build assets to verify CSS compiles**

Run: `npm run build`

- [ ] **Step 4: Verify the CSS was built correctly**

Check `public/build/manifest.json` for the `print-template.css` entry and verify the built CSS file exists and contains `.print-template--dual`.

- [ ] **Step 5: Commit**

```bash
git add resources/css/print-template.css resources/css/print-template-safelist.html
git commit -m "feat: add CSS Grid dual-layout rules for crosswise print templates"
```

---

### Task 4: Refactor ResultSheetTemplateService to return RenderResult

**Files:**
- Modify: `app/Services/ResultSheetTemplateService.php`
- Create: `tests/Unit/ResultSheetTemplateServiceTest.php`

This is the largest task. The key changes:
1. Change `render()`, `renderHtmlContent()`, `renderDocxFile()` to return `RenderResult` instead of `string`
2. Add `renderDual()` method for crosswise layout
3. Add `renderRaw()` private method that returns unwrapped HTML (for use inside `wrapDual()`)
4. Remove `previewDimensions()` — it moves to `RenderResult::pageDimensions()`

- [ ] **Step 1: Write the failing test for `render()` returning RenderResult**

```php
<?php
// tests/Unit/ResultSheetTemplateServiceTest.php

namespace Tests\Unit;

use App\Models\AptitudeArea;
use App\Models\ResultSheetTemplate;
use App\Services\ResultSheetTemplateService;
use App\ValueObjects\RenderResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultSheetTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResultSheetTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResultSheetTemplateService::class);
        // Seed aptitude areas for domain replacements
        AptitudeArea::factory()->create(['name' => 'Spatial Awareness', 'is_active' => true, 'display_order' => 0]);
        AptitudeArea::factory()->create(['name' => 'Numerical Ability', 'is_active' => true, 'display_order' => 1]);
    }

    public function test_render_returns_render_result(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'content' => '<p>{{applicant_name}}</p>',
            'is_active' => true,
        ]);

        $result = $this->service->render($template, [], true);

        $this->assertInstanceOf(RenderResult::class, $result);
        $this->assertSame('html', $result->mode);
        $this->assertSame('a4', $result->paperSize);
        $this->assertSame('portrait', $result->orientation);
        $this->assertSame('full', $result->logicalUnit);
        $this->assertStringContainsString('print-template', $result->html);
    }

    public function test_render_dual_returns_render_result_with_dual_html(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'half_a4',
            'content' => '<p>{{applicant_name}}</p>',
            'is_active' => true,
        ]);

        $applicant1 = ['name' => 'Alice', 'reference' => 'REF-001', 'exam_date' => '2026-01-01', 'room_name' => 'Room A', 'scores' => [], 'overall_pct' => 85];
        $applicant2 = ['name' => 'Bob', 'reference' => 'REF-002', 'exam_date' => '2026-01-01', 'room_name' => 'Room B', 'scores' => [], 'overall_pct' => 90];

        $result = $this->service->renderDual($template, $applicant1, $applicant2, true);

        $this->assertInstanceOf(RenderResult::class, $result);
        $this->assertTrue($result->isHalf());
        $this->assertStringContainsString('print-template--dual', $result->html);
        $this->assertStringContainsString('Alice', $result->html);
        $this->assertStringContainsString('Bob', $result->html);
        // Should have exactly one <style> block (shared CSS)
        $this->assertEquals(1, substr_count($result->html, '<style>'));
    }

    public function test_render_full_page_has_no_dual_class(): void
    {
        $template = ResultSheetTemplate::factory()->create([
            'mode' => 'html',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'content' => '<p>{{applicant_name}}</p>',
            'is_active' => true,
        ]);

        $result = $this->service->render($template, [], true);

        $this->assertStringNotContainsString('print-template--dual', $result->html);
        $this->assertStringContainsString('print-template', $result->html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/ResultSheetTemplateServiceTest.php`
Expected: FAIL — `render()` currently returns `string`, not `RenderResult`

- [ ] **Step 3: Refactor ResultSheetTemplateService**

The key changes to `app/Services/ResultSheetTemplateService.php`:

1. Add `use App\ValueObjects\RenderResult;` import
2. Add a private `renderRaw()` method that returns unwrapped HTML (same as current `renderHtml()` but without CSS wrapping)
3. Change `render()` to return `RenderResult`:
   - For HTML mode: `$this->cssService->wrap($this->renderRaw(...))` wrapped in a `RenderResult`
   - For DOCX mode: same structure but using `renderDocx()`
4. Add `renderDual()` method that calls `wrapDual()` with two sets of rendered HTML
5. Change `renderHtmlContent()` to return `RenderResult`
6. Change `renderDocxFile()` to return `RenderResult`
7. Remove `previewDimensions()` (now in `RenderResult::pageDimensions()`)

The full refactored `render()` method:

```php
public function render(ResultSheetTemplate $template, array $applicants, bool $useSampleData = false): RenderResult
{
    $applicants = array_values($applicants);
    $replacements = $this->buildReplacements($applicants, $useSampleData);

    if ($template->mode === ResultSheetTemplate::MODE_HTML) {
        $html = $this->cssService->wrap(
            $this->renderRaw($template->content ?: '', $replacements)
        );
    } else {
        $html = $this->renderDocx($template->docx_path, $replacements);
    }

    return new RenderResult(
        html: $html,
        mode: $template->mode,
        paperSize: $template->paper_size ?? ResultSheetTemplate::PAPER_A4,
        orientation: $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT,
        logicalUnit: $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL,
    );
}

public function renderDual(ResultSheetTemplate $template, array $applicant1, array $applicant2, bool $useSampleData = false): RenderResult
{
    $replacements1 = $this->buildReplacements([$applicant1], $useSampleData);
    $replacements2 = $this->buildReplacements([$applicant2], $useSampleData);

    if ($template->mode === ResultSheetTemplate::MODE_HTML) {
        $html1 = $this->renderRaw($template->content ?: '', $replacements1);
        $html2 = $this->renderRaw($template->content ?: '', $replacements2);
        $html = $this->cssService->wrapDual($html1, $html2);
    } else {
        // For DOCX mode, each applicant gets their own processed document
        $html1 = $this->renderDocx($template->docx_path, $replacements1);
        $html2 = $this->renderDocx($template->docx_path, $replacements2);
        $html = $this->cssService->wrapDual($html1, $html2);
    }

    return new RenderResult(
        html: $html,
        mode: $template->mode,
        paperSize: $template->paper_size ?? ResultSheetTemplate::PAPER_A4,
        orientation: $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT,
        logicalUnit: $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL,
    );
}
```

Add `renderRaw()` (extracts the string replacement logic from `renderHtml()`):

```php
private function renderRaw(string $content, array $replacements): string
{
    foreach ($replacements as $key => $value) {
        $content = str_replace('{{'.$key.'}}', $value, $content);
    }

    // Replace structural placeholders for scores_rows
    foreach (['scores_rows_2' => 'scores-rows-placeholder-2', 'scores_rows' => 'scores-rows-placeholder'] as $key => $class) {
        $rows = $replacements[$key] ?? '';
        $content = preg_replace_callback(
            '/<\s*tr\s+class\s*=\s*["\']'.preg_quote($class, '/').'["\'][^>]*>.*?<\s*\/\s*tr\s*>/s',
            fn () => $rows ?: '<tr class="'.$class.'"><td colspan="3"></td></tr>',
            $content,
            1
        );
    }

    return $content;
}
```

Extract `buildReplacements()` from the duplicate logic in `render()` and `renderHtmlContent()`:

```php
private function buildReplacements(array $applicants, bool $useSampleData): array
{
    $sample = $this->sampleApplicantData();
    $replacements = [];

    foreach ([1 => 0, 2 => 1] as $slot => $idx) {
        $app = $applicants[$idx] ?? null;
        $data = $app ?? ($useSampleData ? $sample : null);
        $suffix = $slot === 1 ? '' : '_2';
        if ($data) {
            $replacements["applicant_name{$suffix}"] = $data['name'] ?? '—';
            $replacements["applicant_reference{$suffix}"] = $data['reference'] ?? '—';
            $replacements["exam_date{$suffix}"] = $data['exam_date'] ?? '—';
            $replacements["room_name{$suffix}"] = $data['room_name'] ?? '—';
            $replacements["scores_rows{$suffix}"] = $this->buildScoresRows($data['scores'] ?? []);
            $replacements["overall_pct{$suffix}"] = (string) ($data['overall_pct'] ?? 0);
        } else {
            $replacements["applicant_name{$suffix}"] = '—';
            $replacements["applicant_reference{$suffix}"] = '—';
            $replacements["exam_date{$suffix}"] = '—';
            $replacements["room_name{$suffix}"] = '—';
            $replacements["scores_rows{$suffix}"] = '';
            $replacements["overall_pct{$suffix}"] = '—';
        }
    }

    $this->addPerDomainReplacements($replacements, $applicants, $sample, $useSampleData);

    return $replacements;
}
```

Update `renderHtmlContent()` similarly:

```php
public function renderHtmlContent(string $content, array $applicants = [], bool $useSampleData = true): RenderResult
{
    $replacements = $this->buildReplacements($applicants, $useSampleData);

    return new RenderResult(
        html: $this->cssService->wrap($this->renderRaw($content, $replacements)),
        mode: ResultSheetTemplate::MODE_HTML,
        paperSize: ResultSheetTemplate::PAPER_A4,
        orientation: ResultSheetTemplate::ORIENTATION_PORTRAIT,
        logicalUnit: ResultSheetTemplate::LOGICAL_FULL,
    );
}
```

Note: `renderHtmlContent()` is used by the preview endpoint where paperSize/orientation/logicalUnit come from the form, not a saved template. The controller will need to pass these. Add an optional parameters overload or the controller creates the RenderResult. We'll handle this in the controller update task.

Update `renderDocxFile()`:

```php
public function renderDocxFile(string $path, array $replacements = [], bool $useSampleIfEmpty = true): RenderResult
{
    if (empty($replacements) && $useSampleIfEmpty) {
        $sample = $this->sampleApplicantData();
        $replacements = [
            'applicant_name' => $sample['name'],
            'applicant_reference' => $sample['reference'],
            'exam_date' => $sample['exam_date'],
            'room_name' => $sample['room_name'],
            'scores_rows' => $this->buildScoresRows($sample['scores']),
            'overall_pct' => (string) $sample['overall_pct'],
            'applicant_name_2' => $sample['name_2'] ?? '—',
            'applicant_reference_2' => $sample['reference_2'] ?? '—',
            'room_name_2' => $sample['room_name_2'] ?? '—',
            'scores_rows_2' => $this->buildScoresRows($sample['scores_2'] ?? []),
            'overall_pct_2' => (string) ($sample['overall_pct_2'] ?? 0),
        ];
        $this->addPerDomainReplacements($replacements, [], $sample, true);
    }

    $html = $this->renderDocxFromFullPath($path, $replacements);

    return new RenderResult(
        html: $html,
        mode: ResultSheetTemplate::MODE_DOCX,
        paperSize: ResultSheetTemplate::PAPER_A4,
        orientation: ResultSheetTemplate::ORIENTATION_PORTRAIT,
        logicalUnit: ResultSheetTemplate::LOGICAL_FULL,
    );
}
```

Delete the `previewDimensions()` method — it's now `RenderResult::pageDimensions()`.

- [ ] **Step 4: Run the unit test**

Run: `php artisan test --compact tests/Unit/ResultSheetTemplateServiceTest.php`
Expected: PASS (3 tests)

- [ ] **Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Services/ResultSheetTemplateService.php app/ValueObjects/RenderResult.php tests/Unit/ResultSheetTemplateServiceTest.php
git commit -m "refactor: ResultSheetTemplateService returns RenderResult, adds renderDual()"
```

---

### Task 5: Update ReleasePrintController to use RenderResult

**Files:**
- Modify: `app/Http/Controllers/Release/ReleasePrintController.php`
- Modify: `tests/Feature/ReleasePrintControllerTest.php`

The controller currently passes `templateHtml` (string) + separate `paperSize`, `orientation`, `logicalUnit` props. After this change, it passes `templateHtml` from `RenderResult->html` and the metadata from `RenderResult` properties.

- [ ] **Step 1: Update `resultSheet()` method**

Change from:

```php
$templateHtml = $this->templateService->render($template, [$applicantData], false);
```

To:

```php
$result = $this->templateService->render($template, [$applicantData], false);

return Inertia::render('Release/ResultSheet', [
    // ... existing props ...
    'templateHtml' => $result->html,
    'paperSize' => $result->paperSize,
    'orientation' => $result->orientation,
    'logicalUnit' => $result->logicalUnit,
]);
```

Remove the separate `$template->paper_size ?? 'a4'` fallbacks — the `RenderResult` already handles defaults.

- [ ] **Step 2: Update `printBulk()` and `printBulkAgnostic()` methods**

Change the bulk rendering loop from:

```php
$sheetsHtml = [];
foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
    if (count($chunk) === 2) {
        $html1 = $this->templateService->render($template, [$chunk[0]], false);
        $html2 = $this->templateService->render($template, [$chunk[1]], false);
        $sheetsHtml[] = $html1 . $html2;
    } else {
        $sheetsHtml[] = $this->templateService->render($template, $chunk, false);
    }
}
```

To:

```php
$sheetsHtml = [];
foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
    if (count($chunk) === 2) {
        $result = $this->templateService->renderDual($template, $chunk[0], $chunk[1], false);
        $sheetsHtml[] = $result->html;
    } else {
        $result = $this->templateService->render($template, $chunk, false);
        $sheetsHtml[] = $result->html;
    }
}

// Get metadata from the template for the Inertia response
$paperSize = $template->paper_size ?? ResultSheetTemplate::PAPER_A4;
$orientation = $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT;
$logicalUnit = $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL;
```

Pass `$paperSize`, `$orientation`, `$logicalUnit` to Inertia::render (same prop names as before).

- [ ] **Step 3: Update existing feature test**

In `tests/Feature/ReleasePrintControllerTest.php`, update any assertions that check for `templateHtml` as a plain string. The prop name stays the same (`templateHtml`) but its value now comes from `RenderResult->html`. The test structure shouldn't need major changes since we're still passing the same props to Inertia, just the source is now `RenderResult`.

- [ ] **Step 4: Run all ReleasePrintController tests**

Run: `php artisan test --compact tests/Feature/ReleasePrintControllerTest.php`
Expected: PASS

- [ ] **Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Release/ReleasePrintController.php tests/Feature/ReleasePrintControllerTest.php
git commit -m "refactor: ReleasePrintController uses RenderResult for template rendering"
```

---

### Task 6: Update ResultSheetTemplateController to use RenderResult

**Files:**
- Modify: `app/Http/Controllers/Admin/ResultSheetTemplateController.php`

The `preview()` method currently calls `renderHtmlContent()` and `renderDocxFile()` which now return `RenderResult`. The controller needs to return the `html` property from the result, along with dimensions from `RenderResult::pageDimensions()`.

- [ ] **Step 1: Update the `preview()` method**

The preview endpoint returns JSON with `html` and `dimensions`. Update it to use `RenderResult`:

```php
public function preview(Request $request): JsonResponse
{
    $mode = $request->input('mode', 'html');
    $content = $request->input('content', '');
    $docx = $request->file('docx');
    $templateId = $request->input('template_id');

    if ($mode === 'docx') {
        if ($docx) {
            $path = $docx->storeAs('result-sheet-templates', 'preview_'.time().'.docx', 'local');
            $fullPath = Storage::path($path);
            $result = $this->templateService->renderDocxFile($fullPath, [], true);
            Storage::delete($path);
        } elseif ($templateId) {
            $template = ResultSheetTemplate::findOrFail($templateId);
            $result = $this->templateService->renderDocxFile(
                Storage::path($template->docx_path), [], true
            );
        } else {
            return response()->json(['html' => '', 'dimensions' => ['width' => '210mm', 'height' => '297mm']]);
        }
    } else {
        $result = $this->templateService->renderHtmlContent($content, [], true);
    }

    $paperSize = $request->input('paper_size', $result->paperSize);
    $orientation = $request->input('orientation', $result->orientation);
    $logicalUnit = $request->input('logical_unit', $result->logicalUnit);

    $renderResult = new RenderResult(
        html: $result->html,
        mode: $mode,
        paperSize: $paperSize,
        orientation: $orientation,
        logicalUnit: $logicalUnit,
    );

    $dims = $renderResult->pageDimensions();

    return response()->json([
        'html' => $result->html,
        'dimensions' => [
            'width' => $dims['width'].'mm',
            'height' => $dims['height'].'mm',
        ],
    ]);
}
```

Note: The preview endpoint accepts form data with `paper_size`, `orientation`, `logical_unit` from the editor. We use those values to create the `RenderResult` for dimension calculation, but the HTML comes from the service's render result.

- [ ] **Step 2: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/ResultSheetTemplateController.php
git commit -m "refactor: ResultSheetTemplateController uses RenderResult for preview"
```

---

### Task 7: Fix Svelte components — dynamic @page and crosswise layout

**Files:**
- Modify: `resources/js/Pages/Release/ResultSheet.svelte`
- Modify: `resources/js/Pages/Release/ResultSheetBulk.svelte`

- [ ] **Step 1: Update `ResultSheet.svelte`**

Replace the hardcoded max-width and add dynamic `@page`:

```svelte
<!-- Replace the existing <div> wrapper for the template content -->
<div
  class="border border-foreground/20 rounded-lg p-6 print:border print:rounded-none print:p-6 result-sheet-content"
  style="max-width: {pageWidthMm}mm;"
>
  {@html templateHtml}
</div>

<!-- Add dynamic @page in svelte:head -->
<svelte:head>
  {@html `<style>@media print { @page { size: ${paperSize} ${orientation}; margin: 0; } }</style>`}
</svelte:head>
```

Add the computed `pageWidthMm`:

```svelte
const pageWidthMm = $derived(
  logicalUnit.startsWith('half_') ?
    (paperSize === 'letter' ? 216 : paperSize === 'legal' ? 216 : 210) :
    (paperSize === 'letter' ? 216 : paperSize === 'legal' ? 216 : 210)
);
```

Wait — the Svelte component already receives `paperSize` as a prop (it comes from the controller). Remove the hardcoded `max-w-[210mm]` and replace with the dynamic value.

- [ ] **Step 2: Update `ResultSheetBulk.svelte`**

Replace the hardcoded `@page` in the `<style>` block:

```svelte
<!-- Remove this from <style>: -->
@page {
  size: A4 portrait;
  margin: 0;
}

<!-- Add dynamic @page via svelte:head -->
<svelte:head>
  {@html `<style>@media print { @page { size: ${paperSize} ${orientation}; margin: 0; } }</style>`}
</svelte:head>
```

Fix the crosswise layout to use the new dual-wrapped HTML:

The current crosswise code uses a `half-layout-page` flex container. After the server-side `wrapDual()` change, crosswise sheets come as a single HTML blob with `print-template--dual` class. The Svelte component no longer needs to manually split or style halves.

Update the rendering section:

```svelte
{#each sheetsHtml as html}
  <div
    class="border border-foreground/20 rounded-lg p-6 result-sheet-content bg-white {isHalf ? 'half-layout-page' : ''}"
    style={isHalf ? '' : ''}
  >
    {@html html}
  </div>
{/each}
```

The `half-layout-page` CSS can be simplified since the dual layout CSS Grid is now in the server-rendered HTML. Keep the existing print styles but remove the `.half-layout-page :global(> *)` flex rule:

```svelte
<style>
  @media print {
    .result-sheet-content {
      page-break-after: always;
      border: none;
      border-radius: 0;
      box-shadow: none;
    }
    .result-sheet-content:last-child {
      page-break-after: auto;
    }
  }
</style>
```

The `@page` rule is now dynamic via `svelte:head`.

- [ ] **Step 3: Build and verify**

Run: `npm run build`

- [ ] **Step 4: Manual verification**

Visit `http://securecat-v2.test/admin/release/print/1` (or appropriate print batch URL) and verify:
- Single applicant result sheet renders correctly
- Dynamic `@page` size is applied (check via browser DevTools print preview)
- Crosswise layout shows two applicants with 50/50 split

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Release/ResultSheet.svelte resources/js/Pages/Release/ResultSheetBulk.svelte
git commit -m "fix: dynamic @page CSS and crosswise layout using RenderResult metadata"
```

---

### Task 8: Run full test suite and final verification

- [ ] **Step 1: Run all tests**

Run: `php artisan test --compact`
Expected: All tests pass

- [ ] **Step 2: Run pint on all changed files**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Build frontend assets**

Run: `npm run build`

- [ ] **Step 4: Final commit if any formatting changes**

```bash
git add -A
git commit -m "chore: formatting and build after Phase 1 refactoring"
```

---

## Self-Review Checklist

- [x] **Spec coverage:** Every spec section maps to a task:
  - Crosswise layout fix → Tasks 2, 3, 4, 7
  - RenderResult value object → Task 1
  - Dynamic @page → Task 7
  - Service refactoring → Tasks 4, 5, 6
- [x] **Placeholder scan:** No TBDs, TODOs, or "implement later" patterns. All steps have complete code.
- [x] **Type consistency:** `RenderResult` is defined in Task 1 and used consistently in Tasks 4, 5, 6. `wrapDual()` is defined in Task 2 and used in Task 4. Method names match across tasks.