# Bulk Import Score — Aptitude Area Column Matching & Normalized Score Formula

**Date:** 2026-05-13
**Status:** Approved
**Depends on:** Existing `AptitudeArea`, `ApplicantScore`, `GradingSession`, `Application` models

---

## Problem

The current `ScoreImportService` expects `aptitude_area_id` (raw FK) as a column in the spreadsheet, which is fragile for real-world Excel files. There is no formula mechanism for computing `normalized_score` from `raw_score`, and no system toggle between auto-compute and manual scoring modes. The import also requires selecting a single grading session, limiting bulk imports across sessions.

## Design Decisions

### D1: Column headers = AptitudeArea codes

Spreadsheet columns use `AptitudeArea.code` (e.g., `SA`, `NA`, `VR`, `AR`, `LR`, `PSA`) as headers. Each column value is the score. No `aptitude_area_id`, `raw_score`, `max_score`, or `normalized_score` generic columns.

| reference_number | SA | NA | VR | AR | LR | PSA |
|---|---|---|---|---|---|---|
| APP-2026-00001 | 20 | 18 | 22 | 15 | 19 | 16 |

Column matching is case-insensitive against `AptitudeArea.code`.

### D2: Settings toggle — auto-compute vs. manual normalized scores

New system setting `enable_normalized_scores` (boolean, default false):

- **true (auto-compute mode):** Spreadsheet values are `raw_score`. The formula on each `AptitudeArea` auto-computes `normalized_score`. `max_score` is auto-filled from `AptitudeArea.max_items`.
- **false (manual mode):** Spreadsheet values are `normalized_score` directly. `raw_score` and `max_score` are left null.

This setting affects:
- Import UI — column headers show "(raw)" or "(normalized)" suffix
- Import validation — what score type to expect
- Grading UI — which fields appear

### D3: Per-aptitude-area formula

Each `AptitudeArea` gets a `formula` text column (nullable). Only active when `enable_normalized_scores = true`.

- Variables: `x` (raw score), `max_items` (from the aptitude area's `max_items` field), `pi`
- Supported operators: `+`, `-`, `*`, `/`, `()` , `**` (exponent), decimal numbers
- Regex validation on save: only allows safe tokens (numbers, `x`, `max_items`, `pi`, math operators, parentheses)
- If `formula` is null, `normalized_score` is not auto-computed for that area (left null or must be manually provided)
- Results rounded to 2 decimal places

### D4: Multi-session import — auto-match by applicant

No `grading_session_id` column in the spreadsheet. The system resolves the grading session automatically:

**Resolution chain:**
1. `reference_number` → `Application` (by reference_number)
2. `Application.applicant_id` → `Applicant`
3. `Applicant` → `examSessions()` (BelongsToMany via `exam_session_applicant`) → find the exam session with status `completed`
4. `ExamSession` → `gradingSession()` (HasOne) → the grading session for that exam session

**Selection rule when multiple exam sessions exist:**
- Filter to exam sessions with status `completed` that have a `GradingSession` with status `open` or `in_progress`
- If exactly one match → use it
- If zero matches → error: "No open grading session found for this applicant"
- If multiple matches → error: "Applicant has multiple eligible grading sessions; cannot auto-resolve"

**Duplicate score check (same academic year):**
- Query: `ApplicantScore` → `grading_session_id` → `GradingSession` → `exam_session_id` → `ExamSession` → `academic_year_id`
- If any `ApplicantScore` exists for the same `(applicant_id, aptitude_area_id)` within the same `academic_year_id` → reject: "Applicant already has scores for this aptitude area in the current academic year"
- Different academic year → allow (resolves to the current grading session)

### D5: max_score always from AptitudeArea

`ApplicantScore.max_score` is auto-filled from `AptitudeArea.max_items`. Never user-supplied in the spreadsheet. The DB column remains for storage but is never an import input.

### D6: Formula engine — Symfony ExpressionLanguage (new dependency)

Uses `Symfony\Component\ExpressionLanguage\ExpressionLanguage` — **a new production dependency** (not currently in `composer.json`). Must be added via `composer require symfony/expression-language`.

Evaluation is sandboxed:
- Only registered variables: `x`, `max_items`, `pi`
- No function calls, no object access, no property access
- Math operations only
- Results rounded to 2 decimal places

### D7: Inline formula test on AptitudeArea form

On the `AptitudeArea` create/edit form, below the formula input:

- Input field for a sample raw score value
- Live-computed result displayed next to it
- Shows computed value or error message if formula is invalid
- Powered by `POST /admin/aptitude-areas/test-formula`

---

## Data Model Changes

### Migration: Add formula to aptitude_areas

```php
Schema::table('aptitude_areas', function (Blueprint $table) {
    $table->text('formula')->nullable()->after('max_items');
});
```

### Migration: Add enable_normalized_scores setting

Insert into `system_settings`:
```php
DB::table('system_settings')->insert([
    'key' => 'enable_normalized_scores',
    'value' => '0',
]);
```

### Model: AptitudeArea

Add `formula` to `$fillable` and add a `computeNormalizedScore(float $rawScore): ?float` method:

```php
public function computeNormalizedScore(float $rawScore): ?float
{
    if (!$this->formula) {
        return null;
    }

    return app(FormulaEvaluator::class)->evaluate($this->formula, [
        'x' => $rawScore,
        'max_items' => $this->max_items,
    ]);
}
```

### Model: SystemSetting

No structural change — uses existing key-value store.

---

## Import Flow

### Step 1: Upload

- Required columns: `reference_number`
- Dynamic columns: one per active `AptitudeArea.code` (case-insensitive match)
- File types: CSV, XLSX, XLS (bug fix: `StoreScoreImportRequest` currently only allows CSV, but `ScoreImportService` supports XLSX/XLS — update the form request to match)

### Step 2: Parse & validate

For each row:

1. Resolve `reference_number` → `Application` (by reference_number)
2. Resolve `Application` → `Applicant` → `examSessions()` (BelongsToMany) → filter to `completed` status → `gradingSession()` (HasOne) → filter to `open`/`in_progress` status
3. Validate gating rules (see Gating section)
4. If `enable_normalized_scores`:
   - Values treated as `raw_score`
   - `max_score` = `AptitudeArea.max_items`
   - `normalized_score` = `AptitudeArea.computeNormalizedScore(raw_score)`
5. If manual mode:
   - Values treated as `normalized_score`
   - `raw_score` and `max_score` left null

### Step 3: Preview

- Show resolved data: reference_number, applicant name, grading session, each area score
- Show per-row validation errors (red highlight + message)
- Summary counts: total rows, valid, invalid
- Select which rows to import

### Step 4: Confirm import

**Data persistence between preview and confirm:** Re-parse the uploaded file on confirm (do not store resolved data in session). The confirm request re-uploads the file along with the selected row indices. This avoids session size limits for large files and ensures data freshness.

- Re-parse the uploaded file
- Re-validate selected rows (defense-in-depth against stale data)
- Persist `ApplicantScore` records
- Auto-fill `max_score` from `AptitudeArea.max_items` when in auto-compute mode
- Set `scored_by` to current user, `scored_at` to now

---

## Import Gating & Validation

Before import is allowed:

1. **Reference number must resolve** — error if `Application` not found
2. **Application must have an Applicant record** — must be accepted and linked to an exam
3. **Applicant must have completed their exam** — exam session status must be `completed`
4. **Same academic year, 2nd take** — check if `ApplicantScore` already exists for this applicant + aptitude area in any grading session within the same academic year. Reject if found: "Applicant already has scores for this aptitude area in the current academic year". **No upsert for same-year duplicates — always reject.**
5. **Different academic year** — allowed, resolves to current academic year's grading session
6. **Grading session must exist** — error if no open/in_progress grading session exists for the exam session

**Academic-year duplicate check — query path:**

```
ApplicantScore
  → grading_session_id → GradingSession
  → exam_session_id → ExamSession
  → academic_year_id → AcademicYear
```

Implementation: Eager-load the grading session's exam session and academic year for batch duplicate checks. Use a single query per import batch:

```sql
SELECT DISTINCT aptitude_area_id
FROM applicant_scores
JOIN grading_sessions ON applicant_scores.grading_session_id = grading_sessions.id
JOIN exam_sessions ON grading_sessions.exam_session_id = exam_sessions.id
WHERE applicant_scores.applicant_id = ?
  AND exam_sessions.academic_year_id = ?
```

This avoids N+1 per-row queries during validation.

---

## Formula Evaluator Service

New class: `App\Services\FormulaEvaluator`

```php
class FormulaEvaluator
{
    public function evaluate(string $formula, array $variables): ?float
    {
        // Use Symfony ExpressionLanguage with sandboxed variables
        // Only x, max_items, pi registered — no functions, no object access
        // Round result to 2 decimal places
        // Return null on evaluation failure
    }

    public function validate(string $formula): bool
    {
        // Regex check: only allow numbers, x, max_items, pi, +, -, *, /, (, ), **, .
        // Attempt evaluation with sample values to confirm it produces a numeric result
    }
}

// New dependency: composer require symfony/expression-language
```

---

## API Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/admin/aptitude-areas/test-formula` | Test formula with sample value |
| POST | `/admin/grading/import/preview` | Parse & validate (updated) |
| POST | `/admin/grading/import/confirm` | Persist selected scores (updated) |

---

## UI Changes

### AptitudeArea Create/Edit Form
- Add `formula` text input
- Inline "Test Formula" section below formula input
  - Sample raw score input
  - Computed result display (or error)
- `max_items` field remains (used as variable in formula)

### Import Form
- Column hints update dynamically based on `enable_normalized_scores` setting
- Show "(raw)" or "(normalized)" suffix in column hints
- Remove `aptitude_area_id`, `raw_score`, `max_score`, `normalized_score` from optional columns display
- Show active aptitude area codes as expected columns

### Settings Page
- Add `enable_normalized_scores` toggle (boolean)
- Description: "When enabled, import expects raw scores and auto-computes normalized scores using aptitude area formulas."

---

## Out of Scope

- Weighted composite scores / GWA computation (separate feature)
- Import template download (can be added later)
- Bulk edit/update of existing scores within the same academic year (rejected by gating rule #4)
- Formula sharing across aptitude areas (each area has its own)

---

## Existing Bugs to Fix During Implementation

These pre-existing bugs in `ScoreImportService.php` should be fixed as part of this work:

1. **Line 247 — Dead expression:** `{$field} must be a number` is never assigned to `$errors[]`. The string is computed but discarded, so numeric validation silently passes invalid values. Fix: `$errors[] = "{$field} must be a number";`
2. **Line 230 — Missing import:** `Application::where(...)` is called but `use App\Models\Application;` may be missing from imports, causing a fatal error on the validate path. Fix: verify and add the import if missing.