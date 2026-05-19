# Percentile Conversion Table — Implementation Plan

> **Spec Source**: Approved full spec (May 18, 2026)  
> **Plan Date**: May 19, 2026  
> **Status**: Ready for implementation  
> **Estimated beads**: 15–20 tasks across 11 phases

---

## Summary

Add a **per-aptitude-area toggle** between Formula (existing) and Conversion Table (new), enabling admin-defined raw-score-to-percentile-string lookups. Stores `percentile_string` immutably at grading time, adds `_wunit` template placeholders, and preserves all existing behavior via `scoring_method = 'formula'` default.

---

## Phase 1: Database Migrations

### Task 1.1 — Add `scoring_method` to `aptitude_areas`

**What**: New migration adding `scoring_method` column to `aptitude_areas` table.

- Column: `string('scoring_method', 20)->default('formula')->after('formula')`
- Values: `'formula'` | `'conversion_table'`
- Default `'formula'` preserves backward compatibility — all existing rows continue working

**File**: `database/migrations/<ts>_add_scoring_method_to_aptitude_areas_table.php`

**Verification**: Run migration, confirm `aptitude_areas.scoring_method` exists with default `'formula'`.

---

### Task 1.2 — Create `percentile_conversions` table

**What**: New migration creating the lookup table.

```
percentile_conversions:
  id                  bigint unsigned PK
  aptitude_area_id    FK → aptitude_areas (cascadeOnDelete)
  raw_score           unsignedSmallInteger
  percentile_output   string(20)  — "85", "99", "N/A", "85th", "99+"
  timestamps
  UNIQUE (aptitude_area_id, raw_score)
```

**File**: `database/migrations/<ts>_create_percentile_conversions_table.php`

**Design notes**:
- `percentile_output` is **string** (not numeric) — core reason for this feature; allows ordinal text
- Unique constraint prevents duplicate raw_score mappings per area
- `cascadeOnDelete` auto-cleans when area is removed
- `raw_score` as `unsignedSmallInteger` (0–65535) matches existing `applicant_scores.raw_score` range

**Verification**: Run migration, confirm table and constraints exist.

---

### Task 1.3 — Add `percentile_string` to `applicant_scores`

**What**: New migration adding stored percentile string to scores.

- Column: `string('percentile_string', 20)->nullable()->after('normalized_score')`

**Why store it**: When results are printed/exported, we need the exact string the admin mapped at grading time. Re-computing at display time would mean results change if the admin edits the table later. Storing makes the score **immutable at grading time**.

**File**: `database/migrations/<ts>_add_percentile_string_to_applicant_scores_table.php`

**Verification**: Run migration, confirm column exists and is nullable.

---

## Phase 2: PercentileConversion Model

### Task 2.1 — Create `PercentileConversion` model

**What**: New Eloquent model.

```php
class PercentileConversion extends Model
{
    protected $fillable = ['aptitude_area_id', 'raw_score', 'percentile_output'];

    protected function casts(): array
    {
        return ['raw_score' => 'integer'];
    }

    public function aptitudeArea(): BelongsTo
    {
        return $this->belongsTo(AptitudeArea::class);
    }
}
```

**File**: `app/Models/PercentileConversion.php`

**Verification**: Model exists, fillable set, relationship defined.

---

## Phase 3: AptitudeArea Model Changes

### Task 3.1 — Add `scoring_method` fillable, relationship, and methods

**What**: Modify `AptitudeArea.php`.

Changes:
1. Add `'scoring_method'` to `$fillable`
2. Add `percentileConversions()` HasMany relationship
3. Add `lookupPercentile(int $rawScore): ?string` method
4. Add `resolveScore(float $rawScore): array` method (unified score resolution)

```php
public function percentileConversions(): HasMany
{
    return $this->hasMany(PercentileConversion::class);
}

public function lookupPercentile(int $rawScore): ?string
{
    return $this->percentileConversions()
        ->where('raw_score', $rawScore)
        ->value('percentile_output');
}

public function resolveScore(float $rawScore): array
{
    if ($this->scoring_method === 'conversion_table') {
        $percentileString = $this->lookupPercentile((int) $rawScore);
        return [
            'normalized_score' => null,
            'percentile_string' => $percentileString ?? 'N/A',
        ];
    }
    return [
        'normalized_score' => $this->computeNormalizedScore($rawScore),
        'percentile_string' => null,
    ];
}
```

**File**: `app/Models/AptitudeArea.php`

**Verification**: Model methods work — `lookupPercentile()` returns string or null, `resolveScore()` returns correct array based on `scoring_method`.

---

### Task 3.2 — Add `percentile_string` to `ApplicantScore` model

**What**: Add `'percentile_string'` to `$fillable` array.

**File**: `app/Models/ApplicantScore.php`

**Verification**: Fillable includes `percentile_string`.

---

## Phase 4: Admin Backend — Controller + Validation

### Task 4.1 — Update `StoreAptitudeAreaRequest` validation

**What**: Add validation rules for `scoring_method` and `conversion_table` rows.

```php
'scoring_method' => ['required', 'in:formula,conversion_table'],
'conversion_table' => ['required_if:scoring_method,conversion_table', 'array'],
'conversion_table.*.raw_score' => ['required', 'integer', 'min:0'],
'conversion_table.*.percentile_output' => ['required', 'string', 'max:20'],
```

**File**: `app/Http/Requests/StoreAptitudeAreaRequest.php`

**Verification**: Validation accepts valid `scoring_method` values; requires `conversion_table` array when `scoring_method=conversion_table`.

---

### Task 4.2 — Update `UpdateAptitudeAreaRequest` validation

**What**: Same validation rules as Task 4.1, applied to update.

**File**: `app/Http/Requests/UpdateAptitudeAreaRequest.php`

**Verification**: Same as 4.1 for PUT requests.

---

### Task 4.3 — Modify `AptitudeAreaController::store()` to save conversion table

**What**: After creating the AptitudeArea, save conversion table rows if `scoring_method === 'conversion_table'`.

```php
if ($data['scoring_method'] === 'conversion_table' && !empty($data['conversion_table'])) {
    $area->percentileConversions()->delete();
    foreach ($data['conversion_table'] as $row) {
        $area->percentileConversions()->create([
            'raw_score' => $row['raw_score'],
            'percentile_output' => $row['percentile_output'],
        ]);
    }
}
```

Also add `'scoring_method'` to the `AptitudeArea::create()` call.

**File**: `app/Http/Controllers/Admin/AptitudeAreaController.php` — `store()` method

**Verification**: POST with `scoring_method=conversion_table` and conversion_table rows saves correctly.

---

### Task 4.4 — Modify `AptitudeAreaController::update()` to replace conversion table

**What**: Same pattern as store — delete + recreate rows. Also add `scoring_method` to update data.

**File**: `app/Http/Controllers/Admin/AptitudeAreaController.php` — `update()` method

**Verification**: PUT replaces conversion table rows; switching method preserves both formula and table data.

---

### Task 4.5 — Modify `AptitudeAreaController::edit()` to load conversion table data

**What**: Eager-load `percentileConversions` when returning edit form data.

```php
$aptitudeArea->load('percentileConversions');
```

Pass `scoring_method` and `percentile_conversions` (sorted by `raw_score`) to the Inertia view.

**File**: `app/Http/Controllers/Admin/AptitudeAreaController.php` — `edit()` method

**Verification**: Edit page receives existing conversion table rows and `scoring_method`.

---

### Task 4.6 — Modify `AptitudeAreaController::index()` to show scoring method

**What**: Include `scoring_method` in the index mapping.

**File**: `app/Http/Controllers/Admin/AptitudeAreaController.php` — `index()` method

**Verification**: Index data includes `scoring_method` field.

---

## Phase 5: Admin Frontend — Create/Edit with Grid + Paste

### Task 5.1 — Add scoring method toggle to `Create.svelte`

**What**: Add radio toggle for `scoring_method` and conditional display.

- Radio buttons: `formula` | `conversion_table`
- When `formula`: show existing formula textarea + test panel (unchanged)
- When `conversion_table`: hide formula, show conversion table grid

**File**: `resources/js/Pages/Admin/AptitudeAreas/Create.svelte`

**Verification**: Toggle switches between formula input and conversion table view.

---

### Task 5.2 — Add conversion table grid UI to `Create.svelte`

**What**: Build the table grid with:
- Rows: `[raw_score input] [percentile_output input] [delete button]`
- "Generate 0–N" button: pre-fills rows for raw scores 0 through `max_items` (blank percentile_output, only when grid empty)
- "+ Add Row" button
- "Paste" button: opens textarea overlay for tab-separated data from Excel

**File**: `resources/js/Pages/Admin/AptitudeAreas/Create.svelte`

**Verification**: Grid renders, rows can be added/removed, generate works, paste parses tab data.

---

### Task 5.3 — Add scoring method toggle + grid to `Edit.svelte`

**What**: Same UI as Tasks 5.1 + 5.2, plus:
- Pre-populate `scoring_method` from `aptitude_area.scoring_method`
- Pre-populate grid from `aptitude_area.percentile_conversions` (ordered by `raw_score`)
- Form `scoring_method` field initialized from existing data

**File**: `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte`

**Verification**: Edit page shows existing data, toggle + grid work correctly.

---

### Task 5.4 — Add scoring method badge to `Index.svelte`

**What**: Show a badge/indicator for each area's scoring method.

Pattern:
```
Spatial Awareness  [SA]  25 items  📊 Conversion Table  ✅ Active
Numerical Ability  [NA]  25 items  ƒ(x) Formula         ✅ Active
```

**File**: `resources/js/Pages/Admin/AptitudeAreas/Index.svelte`

**Verification**: Index shows method badge per row.

---

## Phase 6: ScoreInputService — Grading Engine

### Task 6.1 — Branch `saveScores()` on `scoring_method`

**What**: When `enableNormalizedScores` is true, check each area's `scoring_method`:

- If `conversion_table`: lookup percentile, store raw_score + percentile_string, set normalized_score = null
- If `formula`: existing behavior (compute via formula, store normalized_score)

```php
if ($area->scoring_method === 'conversion_table') {
    $percentileString = $area->lookupPercentile($rawScore);
    $attributes['raw_score'] = $rawScore;
    $attributes['max_score'] = $maxScore;
    $attributes['normalized_score'] = null;
    $attributes['percentile_string'] = $percentileString ?? 'N/A';
} else {
    // existing formula path
    $normalizedScore = $area?->computeNormalizedScore((float) $rawScore);
    $attributes['raw_score'] = $rawScore;
    $attributes['max_score'] = $maxScore;
    $attributes['normalized_score'] = $normalizedScore;
    $attributes['percentile_string'] = null;
}
```

**File**: `app/Services/ScoreInputService.php` — `saveScores()` method

**Verification**: 
- Saving scores with `conversion_table` area stores `percentile_string`, null `normalized_score`
- Saving scores with `formula` area stores `normalized_score`, null `percentile_string`
- Unmapped raw score stores `'N/A'`

---

## Phase 7: GradingScoreController + ScoreInput.svelte

### Task 7.1 — Pass `scoring_method` and `conversion_table` to ScoreInput page

**What**: Modify `GradingScoreController::show()` to include `scoring_method` and conversion table data in the `domains` prop.

```php
'domains' => $domains->map(fn ($d) => [
    'id' => $d->id,
    'name' => $d->name,
    'code' => $d->code,
    'max_items' => $d->max_items,
    'formula' => $d->formula,
    'has_formula' => $d->formula !== null,
    'scoring_method' => $d->scoring_method,
    'conversion_table' => $d->scoring_method === 'conversion_table'
        ? $d->percentileConversions()->orderBy('raw_score')->get(['raw_score', 'percentile_output'])->toArray()
        : null,
]),
```

**File**: `app/Http/Controllers/Grading/GradingScoreController.php` — `show()` method

**Verification**: ScoreInput page receives `scoring_method` and `conversion_table` data per domain.

---

### Task 7.2 — Add conversion table lookup preview to `ScoreInput.svelte`

**What**: When a domain uses `conversion_table`:
- Raw score input stays the same (grader enters items correct)
- Instead of formula preview, show the **looked-up percentile string** from the `conversion_table` data
- If no match found, show "Out of Range" in warning color

```svelte
{#if domain.scoring_method === 'conversion_table'}
  {@const raw = getScore(domain.id)}
  {@const match = domain.conversion_table?.find(r => r.raw_score === raw)}
  <span class="text-sm font-medium tabular-nums"
        class:text-green-700={match}
        class:text-amber-600={!match}>
    {match ? match.percentile_output : 'Out of Range'}
  </span>
{:else if domain.has_formula}
  <!-- existing formula preview -->
{/if}
```

**File**: `resources/js/Pages/Grading/ScoreInput.svelte`

**Verification**: 
- Conversion table domains show looked-up percentile string
- Unmapped scores show "Out of Range" warning
- Formula domains still show formula preview
- Manual mode domains still show normalized score input

---

## Phase 8: Result Pipeline — Template Service + Print Controller

### Task 8.1 — Modify `ResultSheetTemplateService::mapScoresFromCollection()`

**What**: Expand score mapping to include `percentile_string`, `pct_string`, and `pct_numeric`.

```php
protected function mapScoresFromCollection(Collection $scores): array
{
    return $scores->map(function ($s) {
        if ($s->percentile_string !== null) {
            $numeric = $this->extractNumeric($s->percentile_string);
            return [
                'domain' => $s->aptitudeArea?->name ?? '—',
                'raw' => $s->raw_score,
                'max' => $s->max_score,
                'pct' => $numeric,
                'pct_string' => $s->percentile_string,
                'pct_numeric' => $numeric,
            ];
        }

        $pctVal = $s->normalized_score
            ?? ($s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0);
        return [
            'domain' => $s->aptitudeArea?->name ?? '—',
            'raw' => $s->normalized_score ?? $s->raw_score,
            'max' => $s->max_score,
            'pct' => (int) $pctVal,
            'pct_string' => null,
            'pct_numeric' => (int) $pctVal,
        ];
    })->values()->all();
}
```

Add helper:
```php
private function extractNumeric(string $percentileStr): int
{
    preg_match('/(\d+)/', $percentileStr, $matches);
    return (int) ($matches[1] ?? 0);
}
```

**Critical**: `pct` stays as `int` — no type change, no breakage for existing consumers.

**File**: `app/Services/ResultSheetTemplateService.php` — `mapScoresFromCollection()` + new `extractNumeric()`

**Verification**: Conversion table scores include `pct_string` and `pct_numeric`; formula scores have them as null/int.

---

### Task 8.2 — Add `_wunit` placeholder variant to `addPerDomainReplacements()`

**What**: Each domain gets two template placeholders:

```php
// {{spatial_awareness}} → "85" (number only — DEFAULT, existing)
$replacements[$slug.$suffix] = (string) $pct;

// {{spatial_awareness_wunit}} → "85th" (with ordinal — NEW)
$pctWithUnit = $score['pct_string'] ?? $this->formatOrdinal((int) $pct);
$replacements[$slug.'_wunit'.$suffix] = $pctWithUnit;
```

Add helper:
```php
private function formatOrdinal(int $n): string
{
    $suffix = match ($n % 10) {
        1 => $n % 100 === 11 ? 'th' : 'st',
        2 => $n % 100 === 12 ? 'th' : 'nd',
        3 => $n % 100 === 13 ? 'th' : 'rd',
        default => 'th',
    };
    return $n . $suffix;
}
```

Update `buildAllKnownPlaceholders()` to include `_wunit` variants.

**File**: `app/Services/ResultSheetTemplateService.php` — `addPerDomainReplacements()` + new `formatOrdinal()` + `buildAllKnownPlaceholders()`

**Verification**: 
- `{{domain_name}}` still outputs number only
- `{{domain_name_wunit}}` outputs ordinal string from conversion table, or auto-generated for formula areas

---

### Task 8.3 — Update `percentileToRating()` to use `pct_numeric`

**What**: Rating lookup should use `pct_numeric` (extracted integer) rather than assuming `pct` is the only source.

In `addPerDomainReplacements()`:
```php
$rating = $this->percentileToRating((int) ($score['pct_numeric'] ?? 0), $ratingScale);
```

**File**: `app/Services/ResultSheetTemplateService.php` — `addPerDomainReplacements()`

**Verification**: Rating lookup works for both formula and conversion table domains.

---

### Task 8.4 — Update `overall_pct` calculation to use `pct_numeric`

**What**: Use `pct_numeric` for averaging in both `buildApplicantDataArray()` and `ReleasePrintController::buildApplicantData()`.

```php
$overallPct = count($scores) > 0
    ? (int) round(collect($scores)->avg('pct_numeric'))
    : 0;
```

**Files**: 
- `app/Services/ResultSheetTemplateService.php` — `buildApplicantDataArray()`
- `app/Http/Controllers/Release/ReleasePrintController.php` — `buildApplicantData()`

**Verification**: Overall percentage calculated correctly with mixed scoring methods.

---

### Task 8.5 — Modify `ReleasePrintController::mapScores()`

**What**: Apply the same dual-type handling as `ResultSheetTemplateService::mapScoresFromCollection()`.

Current code:
```php
'pct' => $s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0,
```

New code — same pattern as 8.1 (check `percentile_string`, add `pct_string` and `pct_numeric`).

Also update `buildApplicantData()` for `overall_pct` using `pct_numeric`.

**File**: `app/Http/Controllers/Release/ReleasePrintController.php` — `mapScores()` + `buildApplicantData()`

**Verification**: Print controller handles both formula and conversion table scores.

---

### Task 8.6 — Update `buildScoresRows()` for display

**What**: The scores table rows still use `pct` as int — **no change needed**. The `%d%%` format stays. This task exists to verify that `buildScoresRows()` continues working as-is.

**File**: `app/Services/ResultSheetTemplateService.php` — `buildScoresRows()` (verify only)

**Verification**: Existing `buildScoresRows()` still works with the updated score array structure.

---

## Phase 9: Settings/Setup Validation Updates

### Task 9.1 — Update `SettingsController` validation for `enable_normalized_scores`

**What**: The existing check "all active areas must have a formula" needs to become: "All active areas must have **either** a formula **or** a populated conversion table."

```php
$areasWithoutScoring = AptitudeArea::where('is_active', true)
    ->where(function ($q) {
        $q->where(function ($q2) {
            $q2->where('scoring_method', 'formula')
               ->where(fn ($q3) => $q3->whereNull('formula')->orWhere('formula', ''));
        })->orWhere(function ($q2) {
            $q2->where('scoring_method', 'conversion_table')
               ->whereDoesntHave('percentileConversions');
        });
    })->count();

if ($areasWithoutScoring > 0) {
    return redirect()->back()->withErrors([
        'enable_normalized_scores' => 'Cannot enable auto-compute: All active aptitude areas must have either a formula or a populated conversion table.',
    ]);
}
```

**File**: `app/Http/Controllers/Admin/SettingsController.php` — `update()` method

**Verification**: 
- Enabling auto-compute fails when any active area using `formula` has no formula
- Enabling auto-compute fails when any active area using `conversion_table` has no rows
- Enabling auto-compute succeeds when all areas are properly configured

---

### Task 9.2 — Update `SetupController` readiness check

**What**: Rename the `aptitude_formulas` check to `aptitude_scoring` and account for both methods.

Current check in `checkAptitudeAreas()`:
```php
$withFormula = AptitudeArea::where('is_active', true)
    ->whereNotNull('formula')
    ->where('formula', '!=', '')
    ->count();
```

New check:
```php
$withScoring = AptitudeArea::where('is_active', true)
    ->where(function ($q) {
        $q->where(function ($q2) {
            $q2->where('scoring_method', 'formula')
               ->whereNotNull('formula')
               ->where('formula', '!=', '');
        })->orWhere(function ($q2) {
            $q2->where('scoring_method', 'conversion_table')
               ->whereHas('percentileConversions');
        });
    })->count();
$withoutScoring = $active - $withScoring;
```

Update key to `aptitude_scoring`, label to "Active areas have scoring configured", and message accordingly.

**File**: `app/Http/Controllers/Admin/SetupController.php` — `checkAptitudeAreas()`

**Verification**: Setup health check accounts for both formula and conversion table methods.

---

## Phase 10: Report Export Updates

### Task 10.1 — Update `ReportExportService::buildScoresReport()` for percentile_string

**What**: When building the scores report Excel export, include `percentile_string` data.

In the dynamic headers per area, add a column:
```
"Area Name (Percentile)" after "Area Name (Normalized)"
```

In the row data:
```php
$row[] = $score?->percentile_string ?? ($score?->normalized_score !== null ? round($score->normalized_score, 2) : '');
```

Only add this column if at least one area uses `conversion_table` (to avoid cluttering reports for formula-only institutions).

**File**: `app/Services/ReportExportService.php` — `buildScoresReport()`

**Verification**: Excel export includes percentile string column when applicable.

---

## Phase 11: Tests

### Task 11.1 — Unit test: `PercentileConversion` model

**What**: Test model creation, relationship, fillable, casts.

**File**: `tests/Unit/PercentileConversionTest.php`

---

### Task 11.2 — Unit test: `AptitudeArea::lookupPercentile()` and `resolveScore()`

**What**: Test lookup returns correct string, returns null for unmapped scores, and `resolveScore()` branches correctly.

**File**: `tests/Unit/AptitudeAreaTest.php`

---

### Task 11.3 — Unit test: `ResultSheetTemplateService::extractNumeric()` and `formatOrdinal()`

**What**: Test numeric extraction from strings like "85th" → 85, "99+" → 99, "N/A" → 0. Test ordinal formatting: 1 → "1st", 11 → "11th", 22 → "22nd", etc.

**File**: `tests/Unit/ResultSheetTemplateServiceTest.php`

---

### Task 11.4 — Feature test: Create aptitude area with conversion table

**What**: POST to store endpoint with `scoring_method=conversion_table` and conversion_table rows. Verify data saved, rows created.

**File**: `tests/Feature/AptitudeAreaConversionTableTest.php`

---

### Task 11.5 — Feature test: Score input saves percentile_string

**What**: Enter scores for an applicant where one domain uses conversion_table. Verify `percentile_string` stored, `normalized_score` null, and vice versa for formula domain.

**File**: `tests/Feature/ScoreInputConversionTableTest.php`

---

### Task 11.6 — Feature test: Settings validation with conversion tables

**What**: Test that enabling `enable_normalized_scores` works when all areas have either formula or populated conversion table, and fails otherwise.

**File**: `tests/Feature/SettingsConversionTableTest.php`

---

### Task 11.7 — Feature test: Result pipeline with conversion table scores

**What**: Test that `mapScoresFromCollection()` returns correct `pct_string` and `pct_numeric` for conversion table scores, and `addPerDomainReplacements()` includes `_wunit` placeholders.

**File**: `tests/Feature/ResultPipelineConversionTableTest.php`

---

## Edge Cases Reference

| Scenario | Behavior |
|----------|----------|
| Raw score not in conversion table | Store `percentile_string = 'N/A'`, display warning on ScoreInput |
| Switching from formula → table | Formula is preserved (not deleted) but ignored. Table is now the source. |
| Switching from table → formula | Table rows preserved but ignored. Formula is now the source. |
| Empty conversion table + `scoring_method = conversion_table` | Blocked by validation — at least 1 row required |
| Duplicate raw_score in table | Blocked by DB unique constraint + frontend validation |
| `enable_normalized_scores = OFF` (manual mode) | Conversion table is irrelevant — grader enters normalized score directly as before |
| Bulk export (ReportExportService) | Shows `percentile_string` when present, else `normalized_score` |
| `overall_pct` averaging with mixed types | Uses `pct_numeric` (extracted integer) for all domains regardless of method |

---

## Resolved Decisions

| # | Question | Decision |
|---|----------|----------|
| 1 | Edge case matching | **Strict exact match only** — return "N/A" for unmapped raw scores. No fuzzy/nearest matching. |
| 2 | Auto-generate rows | **Yes** — "Generate 0–N" button pre-fills rows with blank percentile outputs. Shown only when grid empty. |
| 3 | Bulk entry method | **Paste from clipboard only** — no separate CSV upload. Paste tab-separated data from Excel. |
| 4 | DOCX per-domain placeholders | Number only default: `{{domain}}` → `"85"`. New `_wunit` variant: `{{domain_wunit}}` → `"85th"`. |
| 5 | `overall_pct` with mixed methods | Integer average — extract numeric from percentile strings, average all domains. |
| 6 | Manual percentile entry | Already works today via `enable_normalized_scores = OFF`. Conversion table is the automation upgrade. |

---

## Implementation Order (Dependency Graph)

```
Phase 1: DB Migrations (1.1, 1.2, 1.3)
  ↓
Phase 2: PercentileConversion Model (2.1)
  ↓
Phase 3: AptitudeArea + ApplicantScore Model Changes (3.1, 3.2)
  ↓
Phase 4: Admin Backend — Controller + Validation (4.1–4.6)
  ↓
Phase 5: Admin Frontend — Create/Edit with Grid + Paste (5.1–5.4)
  ↓
Phase 6: ScoreInputService — Grading Engine (6.1)
  ↓
Phase 7: GradingScoreController + ScoreInput.svelte (7.1, 7.2)
  ↓
Phase 8: Result Pipeline — Template Service + Print Controller (8.1–8.6)
  ↓
Phase 9: Settings/Setup Validation Updates (9.1, 9.2)
  ↓
Phase 10: Report Export Updates (10.1)
  ↓
Phase 11: Tests (11.1–11.7)
```

---

## Files That Need Changes — Complete Map

| Phase | File | Change |
|-------|------|--------|
| 1 | `database/migrations/_add_scoring_method_to_aptitude_areas_table.php` | **NEW** |
| 1 | `database/migrations/_create_percentile_conversions_table.php` | **NEW** |
| 1 | `database/migrations/_add_percentile_string_to_applicant_scores_table.php` | **NEW** |
| 2 | `app/Models/PercentileConversion.php` | **NEW** |
| 3 | `app/Models/AptitudeArea.php` | Modify — fillable, relationship, lookup, resolve |
| 3 | `app/Models/ApplicantScore.php` | Modify — add percentile_string to fillable |
| 4 | `app/Http/Requests/StoreAptitudeAreaRequest.php` | Modify — new validation rules |
| 4 | `app/Http/Requests/UpdateAptitudeAreaRequest.php` | Modify — new validation rules |
| 4 | `app/Http/Controllers/Admin/AptitudeAreaController.php` | Modify — store, update, edit, index |
| 5 | `resources/js/Pages/Admin/AptitudeAreas/Create.svelte` | Modify — method toggle, grid UI, paste |
| 5 | `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte` | Modify — same as Create + load existing |
| 5 | `resources/js/Pages/Admin/AptitudeAreas/Index.svelte` | Modify — scoring method badge |
| 6 | `app/Services/ScoreInputService.php` | Modify — branching on scoring_method |
| 7 | `app/Http/Controllers/Grading/GradingScoreController.php` | Modify — pass scoring_method + table |
| 7 | `resources/js/Pages/Grading/ScoreInput.svelte` | Modify — lookup preview |
| 8 | `app/Services/ResultSheetTemplateService.php` | Modify — mapScores, replacement pipeline, helpers |
| 8 | `app/Http/Controllers/Release/ReleasePrintController.php` | Modify — mapScores dual-type |
| 9 | `app/Http/Controllers/Admin/SettingsController.php` | Modify — validation logic |
| 9 | `app/Http/Controllers/Admin/SetupController.php` | Modify — readiness check |
| 10 | `app/Services/ReportExportService.php` | Modify — export percentile_string |
| 11 | `tests/Unit/PercentileConversionTest.php` | **NEW** |
| 11 | `tests/Unit/AptitudeAreaTest.php` | **NEW** (or extend existing) |
| 11 | `tests/Unit/ResultSheetTemplateServiceTest.php` | **NEW** (or extend existing) |
| 11 | `tests/Feature/AptitudeAreaConversionTableTest.php` | **NEW** |
| 11 | `tests/Feature/ScoreInputConversionTableTest.php` | **NEW** |
| 11 | `tests/Feature/SettingsConversionTableTest.php` | **NEW** |
| 11 | `tests/Feature/ResultPipelineConversionTableTest.php` | **NEW** |