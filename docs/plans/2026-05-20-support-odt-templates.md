# Support ODT Templates Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Allow users to import ODT (Open Document Text) files as Result Sheet templates under the existing DOCX mode. ODT is treated as a file format variant of `mode=docx`, not a new mode.
**Architecture:** ODT files, like DOCX, are ZIP archives containing XML. A new `ResultSheetOdtService` handles `content.xml` inside ODT archives. The database column `docx_path` is renamed to `document_path` (stores paths like `result-sheet-templates/1.docx` or `result-sheet-templates/2.odt`). A dispatch method in `ResultSheetTemplateService` routes to the correct service based on file extension. LibreOffice converts both formats to PDF — no changes to `DocxToPdfService`.
**Tech Stack:** PHP, Laravel, ZipArchive, DOMDocument, Svelte 5, Inertia.
**Scope:** `ResultSheetTemplate` only. `AdmissionSlipTemplate` (also has `docx_path`) is out of scope — handled in a follow-up if needed.

---

### Task 1: Database & Model — Rename `docx_path` → `document_path`

- Create: Migration to rename column
- Modify: `app/Models/ResultSheetTemplate.php`
- Modify: All PHP files referencing `docx_path` on `ResultSheetTemplate`

**Step 1: Create and implement the migration**
Run: `php artisan make:migration rename_docx_path_to_document_path_on_result_sheet_templates_table`
```php
public function up(): void
{
    Schema::table('result_sheet_templates', function (Blueprint $table) {
        $table->renameColumn('docx_path', 'document_path');
    });
}

public function down(): void
{
    Schema::table('result_sheet_templates', function (Blueprint $table) {
        $table->renameColumn('document_path', 'docx_path');
    });
}
```
No data migration needed — existing file paths like `result-sheet-templates/1.docx` remain valid since `document_path` stores the full relative path including extension.

**Step 2: Update the Model**
In `app/Models/ResultSheetTemplate.php`, change `'docx_path'` to `'document_path'` in `$fillable`.

**Step 3: Update all PHP references** (59 grep hits across these files):

| File | What changes |
|------|-------------|
| `app/Models/ResultSheetTemplate.php` | `$fillable`: `docx_path` → `document_path` |
| `app/Services/ResultSheetTemplateService.php` | All `$template->docx_path` → `$template->document_path`; method renames (see Task 3) |
| `app/Http/Controllers/Admin/ResultSheetTemplateController.php` | All `$data['docx_path']`, `$result_template->docx_path` → `document_path`; file upload uses `getClientOriginalExtension()` instead of hardcoding `.docx` |
| `app/Http/Controllers/Release/ReleasePrintController.php` | All `$template->docx_path` → `$template->document_path` |
| `database/factories/ResultSheetTemplateFactory.php` | `'docx_path' => null` → `'document_path' => null` |
| `database/seeders/ResultSheetTemplateSeeder.php` | Same |
| `tests/Unit/ResultSheetTemplateActiveTest.php` | All `docx_path` → `document_path` |
| `tests/Unit/ResultSheetTemplateServiceDocxTest.php` | All `docx_path` → `document_path` |
| `tests/Feature/ResultSheetTemplateControllerTest.php` | All `docx_path` refs → `document_path` |

**Step 4: Run the migration**
Run: `php artisan migrate`

**Step 5: Commit**
```bash
git add -A
git commit -m "feat: rename docx_path to document_path in result sheet templates"
```

---

### Task 2: Create `ResultSheetOdtService`

- Create: `app/Services/ResultSheetOdtService.php`

**Step 1: Write the ODT Service**

Create `app/Services/ResultSheetOdtService.php` modeled on `ResultSheetDocxService.php`. Key differences:

- ODT ZIP contains `content.xml` (not `word/document.xml`)
- ODT text elements: `<text:p>`, `<text:span>`, `<text:s>`, `<text:tab>`
- Placeholder syntax is `{{key}}` inside text nodes, identical to DOCX

Public methods (matching `ResultSheetDocxService` signatures):
```php
class ResultSheetOdtService
{
    public function renderFromStoragePath(?string $path, array $replacements): string
    public function renderFromFullPath(string $fullPath, array $replacements): string
    public function validateTemplate(string $fullPath, array $categorizedPlaceholders, bool $isCrosswise): DocxValidationResult
    public function getRepairedTemplate(string $originalPath): ?string
}
```

Internal repair methods:
- `repairOdtXml(string $content): string` — applies both repair strategies
- `mergeOdtTextSpans(string $content): string` — merges adjacent `<text:span>` elements with identical `<text:span>` style that split `{{placeholder}}` across runs (analogous to `mergeAdjacentRuns` in DocxService)
- `stripXmlFromOdtMacros(string $content): string` — strips XML tags from `{{...}}` content that still contains markup (analogous to `stripXmlFromMacros`)

Rendering approach:
- `renderFromFullPath()`: Extract `content.xml` from ZIP via `ZipArchive`, apply replacements with `TemplateProcessor`-style `setValue()` after repair, then convert to HTML via `DOMDocument` parsing of `content.xml`
- `renderFromStoragePath()`: Delegates to `renderFromFullPath()` after resolving storage path
- Error handling mirrors DocxService: catch exceptions, log errors, return user-friendly HTML error messages

**Step 2: Commit**
```bash
git add app/Services/ResultSheetOdtService.php
git commit -m "feat: add ResultSheetOdtService to parse and render ODT templates"
```

---

### Task 3: Update `ResultSheetTemplateService` — Extension-Based Dispatch

- Modify: `app/Services/ResultSheetTemplateService.php`

**Step 1: Inject `ResultSheetOdtService` and add dispatch method**

Add to constructor:
```php
public function __construct(
    protected PrintTemplateCssService $cssService,
    protected ResultSheetDocxService $docxService,
    protected ResultSheetOdtService $odtService,
) {}
```

Add dispatch helper:
```php
protected function documentService(ResultSheetTemplate $template): ResultSheetDocxService|ResultSheetOdtService
{
    $ext = strtolower(pathinfo($template->document_path ?? '', PATHINFO_EXTENSION));
    return $ext === 'odt' ? $this->odtService : $this->docxService;
}
```

**Step 2: Replace all `$this->docxService->` calls with dispatch**

In `render()`, `renderDual()`, `buildRawFragment()`, `buildRawDualFragment()`:
```php
// Before:
$html = $this->docxService->renderDocxFromStoragePath($template->docx_path, ...);
// After:
$html = $this->documentService($template)->renderFromStoragePath($template->document_path, ...);
```

**Step 3: Rename public methods**
- `renderDocxFile()` → `renderDocumentFile()` — dispatches by extension
- `getDocxValidation()` → `getDocumentValidation()` — dispatches by extension
- `buildFilledDocxFiles()` → `buildFilledDocumentFiles()` — dispatches by extension

For `buildFilledDocumentFiles()` when ODT: use `ZipArchive` to write `content.xml` with replacements directly (instead of `TemplateProcessor` which is DOCX-only). LibreOffice can convert ODT → PDF just like DOCX → PDF.

**Step 4: Update all `$template->docx_path` references to `$template->document_path`**

**Step 5: Commit**
```bash
git add app/Services/ResultSheetTemplateService.php
git commit -m "refactor: add ODT dispatch and rename docx methods to document methods"
```

---

### Task 4: Update `ReleasePrintController`

- Modify: `app/Http/Controllers/Release/ReleasePrintController.php`

**Step 1: Update references**
All `$template->docx_path` → `$template->document_path` (7 occurrences across lines 148, 194, 198, 287, 349, 380, 396).

**Step 2: Verify PDF conversion works for ODT**
`DocxToPdfService` uses LibreOffice which auto-detects format — `.odt` converts to PDF just like `.docx`. No changes needed to `DocxToPdfService` itself.

The guard pattern `$template->mode === ResultSheetTemplate::MODE_DOCX && $template->document_path` remains correct — ODT files use `mode = 'docx'`.

**Step 3: Update download method error messages**
- `downloadDocx()`: "No active DOCX template found" → "No active document template found"

**Step 4: Commit**
```bash
git add app/Http/Controllers/Release/ReleasePrintController.php
git commit -m "refactor: update ReleasePrintController to use document_path"
```

---

### Task 5: Update Controller & Form Requests

- Modify: `app/Http/Controllers/Admin/ResultSheetTemplateController.php`
- Modify: `app/Http/Requests/StoreResultSheetTemplateRequest.php`
- Modify: `app/Http/Requests/UpdateResultSheetTemplateRequest.php`
- Modify: `routes/web.php`

**Step 1: Update form request validation**

`StoreResultSheetTemplateRequest`:
```php
'document' => ['required_if:mode,docx', 'nullable', 'file', 'mimes:docx,odt', 'max:5120'],
```

`UpdateResultSheetTemplateRequest`:
```php
'document' => ['nullable', 'file', 'mimes:docx,odt', 'max:5120'],
```

**Step 2: Update `ResultSheetTemplateController`**
- All `$request->file('docx')` → `$request->file('document')`
- All `$request->hasFile('docx')` → `$request->hasFile('document')`
- File storage: `$destPath = 'result-sheet-templates/'.$template->id.'.'.$file->getClientOriginalExtension()` (was hardcoded `.docx`)
- `$data['docx_path']` / `$result_template->docx_path` → `$data['document_path']` / `$result_template->document_path`
- Rename method `validateDocx()` → `validateDocument()`
- Accept both `docx` and `odt` MIME types in validation:
  ```php
  $request->validate([
      'document' => ['nullable', 'file', 'mimes:docx,odt', 'max:5120'],
      // ...
  ]);
  ```
- The `preview()` method's inline validation of `docx` field → `document` field
- Preview fallback message: "No DOCX file" → "No document file"

**Step 3: Update web route**
In `routes/web.php`, change:
```php
Route::post('result-templates/validate-docx', [ResultSheetTemplateController::class, 'validateDocx'])->name('result-templates.validate-docx');
```
to:
```php
Route::post('result-templates/validate-document', [ResultSheetTemplateController::class, 'validateDocument'])->name('result-templates.validate-document');
```

**Step 4: Commit**
```bash
git add app/Http/Controllers/Admin/ResultSheetTemplateController.php app/Http/Requests/ routes/web.php
git commit -m "feat: accept ODT uploads, rename validate-docx to validate-document"
```

---

### Task 6: Update Frontend UI

- Modify: `resources/js/Pages/Admin/ResultSheetTemplates/Create.svelte`
- Modify: `resources/js/Pages/Admin/ResultSheetTemplates/Edit.svelte`

**Step 1: Update Create.svelte**

| Find | Replace |
|------|---------|
| `docxFile` | `documentFile` |
| `rawDocxFile` | `rawDocumentFile` |
| `$form.docx` | `$form.document` |
| `fd.append('docx', rawDocxFile)` | `fd.append('document', rawDocumentFile)` |
| `fetch('/admin/release/result-templates/validate-docx', {` | `fetch('/admin/release/result-templates/validate-document', {` |
| `handleDocxFile` | `handleDocumentFile` |
| `accept=".docx"` | `accept=".docx,.odt"` |
| `"DOCX file"` label text | `"Document file (DOCX or ODT)"` |
| `"Upload a DOCX file to preview."` | `"Upload a document file to preview."` |
| `if ($form.mode !== 'docx')` in fetchValidation | stays as-is (mode remains `'docx'`) |
| ToggleGroupItem `<span class="text-sm">DOCX</span>` | `<span class="text-sm">DOCX / ODT</span>` |
| GuideSection `"DOCX Notes"` | `"Document Notes"` |
| `docxPlaceholderNote` prop | `docxPlaceholderNote` stays (prop name from backend unchanged) |

**Step 2: Update Edit.svelte** — same changes as Create.svelte, plus:

| Find | Replace |
|------|---------|
| `template?.docx_path` | `template?.document_path` |
| `"Upload a DOCX file to replace..."` | `"Upload a document file to replace..."` |
| Existing file display of `template.docx_path` | `template.document_path` |

**Step 3: Build and verify**
Run: `npm run build`

**Step 4: Commit**
```bash
git add resources/js/Pages/Admin/ResultSheetTemplates/
git commit -m "feat: update frontend UI to accept ODT template files"
```

---

### Task 7: Update Factory & Seeder

- Modify: `database/factories/ResultSheetTemplateFactory.php`
- Modify: `database/seeders/ResultSheetTemplateSeeder.php`

**Step 1: Update factory**
`'docx_path' => null` → `'document_path' => null`

**Step 2: Update seeder**
`'docx_path' => null` → `'document_path' => null`

**Step 3: Commit**
```bash
git add database/factories/ResultSheetTemplateFactory.php database/seeders/ResultSheetTemplateSeeder.php
git commit -m "refactor: update factory and seeder to use document_path"
```

---

### Task 8: Tests

- Create: `tests/Unit/Services/ResultSheetOdtServiceTest.php`
- Modify: `tests/Unit/ResultSheetTemplateActiveTest.php`
- Modify: `tests/Unit/ResultSheetTemplateServiceDocxTest.php`
- Modify: `tests/Feature/ResultSheetTemplateControllerTest.php`

**Step 1: Update existing tests for `document_path` rename**

`tests/Unit/ResultSheetTemplateActiveTest.php`:
- All `'docx_path'` → `'document_path'`
- Method name: `test_build_filled_docx_files_throws_on_null_docx_path` → `test_build_filled_document_files_throws_on_null_document_path`

`tests/Unit/ResultSheetTemplateServiceDocxTest.php`:
- Keep the filename and test class name (still testing DOCX-specific behavior)
- Any `'docx_path'` refs → `'document_path'`

`tests/Feature/ResultSheetTemplateControllerTest.php`:
- Upload key `'docx'` → `'document'` in test requests
- Add test for uploading `.odt` file

**Step 2: Create `ResultSheetOdtServiceTest`**

```php
class ResultSheetOdtServiceTest extends TestCase
{
    // test_render_from_full_path_replaces_placeholders
    // test_render_from_full_path_returns_error_on_missing_file
    // test_validate_template_detects_missing_required_placeholders
    // test_validate_template_passes_with_all_required_placeholders
    // test_get_repaired_template_merges_split_placeholders
}
```

These tests need an ODT fixture file. Create `tests/fixtures/test_template.odt` — a minimal ODT (ZIP with `content.xml` containing `{{applicant_name}}` and other placeholders, `META-INF/manifest.xml`, `mimetype`).

**Step 3: Run all tests**
```bash
php artisan test --compact
```

**Step 4: Commit**
```bash
git add tests/ tests/fixtures/
git commit -m "test: update tests for document_path rename and add ODT service tests"
```

---

### Risk & Mitigation Summary

| Risk | Mitigation |
|------|-----------|
| ODT → HTML conversion quality unknown | Start with DOMDocument extraction of `content.xml`; LibreOffice PDF path as fallback |
| ODT `<text:span>` fragments `{{placeholder}}` across runs | `mergeOdtTextSpans()` + `stripXmlFromOdtMacros()` mirrors the proven DOCX repair strategy |
| LibreOffice ODT → PDF | Works out of the box — LibreOffice auto-detects format, no `DocxToPdfService` changes needed |
| `AdmissionSlipTemplate` also uses `docx_path` | Out of scope — separate follow-up |
| Breaking route change `validate-docx` → `validate-document` | Backend + frontend updated in same deploy |
| Existing file paths on disk | Paths like `result-sheet-templates/1.docx` remain valid — `document_path` stores full path with extension |