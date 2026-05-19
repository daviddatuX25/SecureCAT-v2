# Implementation Plan: DOCX Template Robustness Phase 2

> LibreOffice headless DOCX→PDF conversion (single + bulk), placeholder guide completeness, and pipeline integrity.

---

## 0. Audit: What Already Exists vs. What This Plan Covers

> **The megaplan (Tasks 1–5) is substantially complete.** This Phase 2 plan covers ONLY the remaining gaps identified by codebase audit on 2026-05-19.

### Already Implemented (DO NOT re-implement)

| Item | Evidence |
|------|----------|
| `config/institution.php` | File exists with 8 profile + 7 personnel roles |
| `InstitutionController` + routes | 3 routes registered (`setup.institution.*`) |
| `RatingScale` model + migrations | Model exists, 2 migrations exist |
| `RatingScaleController` + routes | Resource routes registered |
| `Application.strand` in `$fillable` | Line 27 of `Application.php` |
| `PLACEHOLDERS` constant expanded | 42 entries including institution + identity fields |
| `buildReplacements()` expanded | Institution vars, personnel, identity fields all present |
| `buildApplicantDataArray()` expanded | All 20+ fields including counselor, strand, course |
| `sampleApplicantData()` expanded | Both slot 1 and slot 2 with all fields |
| `addPerDomainReplacements()` with `_rating` | Rating lookup via `percentileToRating()` |
| `buildAllKnownPlaceholders()` expanded | Includes `_rating`, personnel, institution |
| `downloadDocx()` endpoint | `ReleasePrintController` line 159, route registered |
| `ResultSheetDocxService` error handling | Try/catch, sanitization, temp cleanup all present |
| Audit logging on render paths | `buildSheetsForApplicantIds` + `buildRawSheetsForApplicantIds` |

### Remaining Gaps (This Plan)

| Gap | Severity | Detail |
|-----|----------|--------|
| **A. Placeholder guide incomplete** | HIGH | Controller still sends only 6 hardcoded placeholders per slot. No institution/personnel sections in Svelte. |
| **B. No `DocxToPdfService`** | HIGH | LibreOffice headless service doesn't exist. No `config/docx.php`. |
| **C. No LibreOffice pipeline integration** | HIGH | `resultSheetPdf()`, `printBulkPdf()`, `printBulkAgnosticPdf()` all use Browsershot only. |
| **D. No Docker/infrastructure** | MEDIUM | No published Dockerfile, no `LIBRE_OFFICE_PATH` in `.env.example`. |
| **E. `renderDocxFile()` duplication** | MEDIUM | 50-line inline replacement map duplicates `buildReplacements()` logic. |
| **F. No tests** | MEDIUM | Zero test coverage for new service, placeholder groups, pipeline. |

---

## 1. Architecture Decisions

### D1: LibreOffice Integration — Custom Service, No Package

**Decision:** Thin custom service (`DocxToPdfService`) using `symfony/process`. Zero new packages.

**Rationale:** `symfony/process` is already a Laravel dependency. Config-driven via `config/docx.php` + `LIBRE_OFFICE_PATH` env var. Two new PHP files total.

### D2: RenderResult Stays Untouched

**Decision:** Do NOT extend `RenderResult`. Use a separate streaming path in the controller.

**Rationale:** `RenderResult` is `readonly` with HTML-centric API. Adding `?string $pdfContent` makes `$html` semantically ambiguous.

### D3: LibreOffice Is Opt-In

**Decision:** `LIBRE_OFFICE_PATH` defaults to empty. When empty, falls back to existing PhpWord->HTML->Browsershot pipeline.

### D4: Per-Process Profile Isolation

**Decision:** Each LibreOffice invocation gets unique temp profile directory to avoid lock conflicts.

### D5: PDF Merge via `pdfunite` (poppler-utils)

**Decision:** Use `pdfunite` for bulk PDF merging. Zero PHP deps, handles all PDF versions.

### D6: Placeholder Guide Derived from PLACEHOLDERS Constant

**Decision:** New `getPlaceholderGroups()` derives groups from `PLACEHOLDERS` constant. When `PLACEHOLDERS` grows, guide auto-updates.

### D7: DRY Up renderDocxFile() Sample Path

**Decision:** Extract `buildSampleReplacements()` to eliminate 50-line duplication.

**Critical flaw in current code:** `renderDocxFile()` lines 285-334 manually rebuild what `buildReplacements([], true)` already does. Personnel placeholders in preview mode may show raw tags.

---

## 2. Phase A: Placeholder Guide Completeness

> UI-only changes. Zero infrastructure risk.

### Files Changed

| File | Change |
|------|--------|
| `ResultSheetTemplateService.php` | Add `getPlaceholderGroups()`, `buildSampleReplacements()`, fix `renderDocxFile()` |
| `ResultSheetTemplateController.php` | Replace hardcoded arrays with service call, add `exampleRating` |
| `Create.svelte` (ResultSheetTemplates) | Add Institution + Personnel + annotation sections |
| `Edit.svelte` (ResultSheetTemplates) | Same as Create |

### A.1: Add `buildSampleReplacements()` + Fix `renderDocxFile()`

Replace the 50-line inline map in `renderDocxFile()` lines 285-334 with:

```php
protected function buildSampleReplacements(): array
{
    return $this->buildReplacements([], true);
}
```

Then in `renderDocxFile()`:

```php
if (empty($replacements) && $useSampleIfEmpty) {
    $replacements = $this->buildSampleReplacements();
}
```

### A.2: Add `getPlaceholderGroups()`

Derives groups from `PLACEHOLDERS` constant + dynamic config personnel. Returns `['applicant1' => [...], 'applicant2' => [...], 'institution' => [...], 'personnel' => [...]]` with `{{}}` wrapping.

### A.3: Update Controller — Dynamic Groups + exampleRating

Replace hardcoded `placeholdersApplicant1`/`placeholdersApplicant2` with service call. Add `placeholdersInstitution`, `placeholdersPersonnel`, and `exampleRating` to domain data.

### A.4-A.5: Update Create.svelte and Edit.svelte

Add Institution and Personnel `GuideSection` components. Add `_rating` to domain items. Add `scores_rows` HTML-mode-only annotation.

### Gate: Placeholder group tests + controller prop tests pass

---

## 3. Phase B: DocxToPdfService

> New files only. No changes to existing code.

### New Files

| File | Purpose |
|------|---------|
| `config/docx.php` | LibreOffice binary path, timeout, temp dir, pdfunite path |
| `app/Services/DocxToPdfService.php` | `convert()`, `convertBatch()`, `isAvailable()` |

### B.1: `config/docx.php`

Four config keys: `libreoffice_path`, `conversion_timeout` (120s), `pdfunite_path`, `temp_dir`.

### B.2: DocxToPdfService

**Public API:**
- `isAvailable(): bool` — checks config + binary existence
- `convert(string $docxPath): string` — single DOCX to PDF content
- `convertBatch(array $docxPaths, int $copies = 1): string` — multiple DOCX to merged PDF

**Critical robustness patterns:**
1. Per-process profile isolation with `uniqid()`
2. Timeout control via `symfony/process`
3. Guaranteed cleanup in `finally` blocks (profile + output dirs)
4. Input validation (verify each DOCX file exists)
5. Structured error logging (command, stderr, stdout, exitCode)
6. Output verification (assert each expected PDF exists)
7. Windows compatibility (`is_file()` vs `is_executable()`)
8. Profile dir URI normalization: `file:///` + forward slashes on all platforms

**Output dir cleanup fix:** Both `convert()` and `convertBatch()` must use `try/finally` to delete the output dir after reading results.

### Gate: Unit tests pass, integration tests skip when no LibreOffice

---

## 4. Phase C: Pipeline Integration

> Wire DocxToPdfService into existing endpoints.

### Files Changed

| File | Change |
|------|--------|
| `ResultSheetTemplateService.php` | Add `buildFilledDocxFiles()` |
| `ReleasePrintController.php` | Inject DocxToPdfService, refactor data assembly, LibreOffice paths |

### C.1: Add `buildFilledDocxFiles()` to Service

Mirrors `buildRawSheetsFromApplicantData()` but produces filled DOCX temp files. Chunks by logical unit, builds replacements, fills via TemplateProcessor, saves to temp. Caller MUST clean up.

### C.2: CRITICAL — Refactor Bulk Data Assembly

**BUG FOUND:** `ReleasePrintController::buildApplicantData()` returns only 7 fields (id, name, reference, exam_date, room_name, scores, overall_pct). The service's `buildApplicantDataArray()` returns 20+. The bulk endpoints use the controller's version, so ALL new fields (family_name, sex, gwa, strand, counselor, etc.) are `'—'` in bulk prints.

**Impact:** ALL bulk prints show `'—'` for new fields. Not just LibreOffice — HTML DOCX templates with per-field placeholders too.

**Fix:** Refactor `printBulkPdf()` and `printBulkAgnosticPdf()` to use `$this->templateService->fetchApplicantsWithScores()` instead of their own data assembly. The service already has correct eager loading and full field population.

### C.3: Single Applicant PDF — LibreOffice Path

In `resultSheetPdf()`, add check: if DOCX mode + LibreOffice available, use `buildFilledDocxFiles()` + `convert()` + stream PDF. Otherwise fallback to existing Browsershot.

### C.4: Bulk PDF — LibreOffice Path

Same pattern for `printBulkPdf()` and `printBulkAgnosticPdf()` using `convertBatch()`.

### C.5: Inject DocxToPdfService into Constructor

Add `private DocxToPdfService $docxToPdfService` to controller constructor.

### Gate: Feature tests pass with mocked DocxToPdfService

---

## 5. Phase D: Infrastructure

### D.1: Publish Sail Dockerfile

`./vendor/bin/sail artisan sail:publish` creates `docker/8.4/Dockerfile`.

### D.2: Add LibreOffice Packages

Add to apt-get install: `libreoffice-writer poppler-utils fonts-liberation fonts-dejavu-core fonts-noto-core`. Image +350-400MB.

### D.3: Update docker-compose.yml

Point build context to `./docker/8.4`.

### D.4: Add Env Vars to .env.example

`LIBRE_OFFICE_PATH=` and `DOCX_CONVERSION_TIMEOUT=120`.

### Gate: Rebuild image, verify `soffice` + `pdfunite` exist

---

## 6. Phase E: Tests

### E.1: DocxToPdfServiceTest (Unit)

6 tests: isAvailable false cases, convert throws on missing file, convert/batch integration (skip when no LO), copies handling.

### E.2: Placeholder Groups (Unit)

4 tests: institution vars present, personnel present, mustache wrapping, buildSampleReplacements equals buildReplacements.

### E.3: Controller Props (Feature)

3 tests: create/edit page includes institution/personnel/rating placeholders.

### E.4: PDF Pipeline (Feature)

3 tests: LibreOffice path used when available, fallback when not, batch uses convertBatch.

---

## 7. Critical Issues Summary

| # | Issue | Severity | Phase |
|---|-------|----------|-------|
| 1 | Controller `buildApplicantData()` returns only 7 fields; bulk prints show '—' for 13+ new fields | **HIGH** | C.2 |
| 2 | `renderDocxFile()` 50-line duplication of `buildReplacements()` | MEDIUM | A.1 |
| 3 | Output dir cleanup leak in DocxToPdfService if exception between read and cleanup | MEDIUM | B.2 |
| 4 | LibreOffice profile URI format differs on Windows (`file:///C:/path`) | MEDIUM | B.2 |
| 5 | Concurrent bulk conversion memory (~20MB for 200 applicants) | LOW | Documented |
| 6 | `pdfunite` argument length (200 files × 60 chars = 12KB, well under 128KB limit) | LOW | N/A |

---

## 8. Execution Order

```
Phase A (UI — placeholder guide)
  A.1  buildSampleReplacements() + fix renderDocxFile()
  A.2  getPlaceholderGroups()
  A.3  Controller dynamic groups + exampleRating
  A.4  Create.svelte — Institution + Personnel + annotation
  A.5  Edit.svelte — same
  → Gate: tests pass

Phase B (DocxToPdfService — new files only)
  B.1  config/docx.php
  B.2  DocxToPdfService
  → Gate: unit tests pass

Phase C (Pipeline — modify existing code)
  C.1  buildFilledDocxFiles() in service
  C.2  CRITICAL: Refactor printBulkPdf() data assembly
  C.3  CRITICAL: Refactor printBulkAgnosticPdf() data assembly
  C.4  resultSheetPdf() — LibreOffice path
  C.5  printBulkPdf() — LibreOffice path
  C.6  printBulkAgnosticPdf() — LibreOffice path
  C.7  Inject DocxToPdfService
  → Gate: feature tests pass

Phase D (Infrastructure)
  D.1-D.4  Dockerfile + env
  → Gate: image builds, binaries verified

Phase E (Verification)
  E.1-E.4  All tests
  E.5  Manual smoke tests (single, bulk, crosswise, fallback)
  E.6  Full test suite
  E.7  Pint formatting
```

---

## 9. File Manifest

### New Files

| File | Phase |
|------|-------|
| `config/docx.php` | B |
| `app/Services/DocxToPdfService.php` | B |
| `tests/Unit/Services/DocxToPdfServiceTest.php` | E |
| `tests/Feature/ResultSheetPlaceholderTest.php` | E |

### Modified Files

| File | Phase | What Changes |
|------|-------|-------------|
| `ResultSheetTemplateService.php` | A, C | `getPlaceholderGroups()`, `buildSampleReplacements()`, `buildFilledDocxFiles()`, fix `renderDocxFile()` |
| `ResultSheetTemplateController.php` | A | Dynamic placeholder groups + `exampleRating` |
| `ReleasePrintController.php` | C | Refactor data assembly, inject DocxToPdfService, LibreOffice paths |
| `Create.svelte` (ResultSheetTemplates) | A | Institution + Personnel guide sections |
| `Edit.svelte` (ResultSheetTemplates) | A | Same |
| `docker/8.4/Dockerfile` (published) | D | LibreOffice + fonts |
| `docker-compose.yml` | D | Build context |
| `.env.example` | D | `LIBRE_OFFICE_PATH`, `DOCX_CONVERSION_TIMEOUT` |

### Estimated Time

| Phase | Time |
|-------|------|
| A: Placeholder Guide | 1.5 hr |
| B: DocxToPdfService | 1.5 hr |
| C: Pipeline + Data Fix | 2 hr |
| D: Infrastructure | 45 min |
| E: Tests + Verification | 1.5 hr |
| **Total** | **~7.5 hr** |

---

## 10. Risks & Mitigations

| Risk | Severity | Mitigation |
|------|----------|------------|
| LibreOffice binary missing | Low | Graceful fallback. `isAvailable()` check. |
| Concurrent conversions overload | Medium | Per-process profiles. 200-applicant cap. Future: queue. |
| Docker image +350MB | Low | Writer-only, `--no-install-recommends`. |
| LibreOffice process hangs | Medium | symfony/process 120s timeout. `finally` cleanup. |
| Font rendering differences | Low | Install liberation + dejavu + noto fonts. |
| Windows path spaces | Low | symfony/process array-based command. |
| Temp file accumulation | Low | `finally` blocks. Consider scheduled cleanup. |
| **Bulk data assembly mismatch** | **HIGH** | Phase C.2-C.3 refactor is prerequisite. |
