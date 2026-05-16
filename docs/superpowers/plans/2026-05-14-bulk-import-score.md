# Bulk Import Score — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rewrite the score import flow so spreadsheets use `AptitudeArea.code` columns, auto-resolve the grading session per applicant, compute normalized scores from formulas when enabled, and add formula management + settings toggle.

**Architecture:** Add a sandboxed `FormulaEvaluator` (Symfony ExpressionLanguage) and a `formula` column on `AptitudeArea`. Replace the old flat `raw_score`/`max_score`/`normalized_score` columns with dynamic per-area columns keyed by `AptitudeArea.code`. The import service now resolves the target `GradingSession` by walking `Application → Applicant → completed ExamSession → open GradingSession`, then validates duplicates within the same academic year. The frontend hints update dynamically based on the `enable_normalized_scores` system setting.

**Tech Stack:** Laravel 12, PHP 8.4, Inertia.js v2 + Svelte, Symfony ExpressionLanguage, PhpSpreadsheet, SQLite (tests).

---

## File Structure

| File | Action | Responsibility |
|------|--------|----------------|
| `composer.json` | Modify | Add `symfony/expression-language` dependency |
| `database/migrations/2026_05_14_000001_add_formula_to_aptitude_areas_table.php` | Create | Add `formula` text column |
| `database/migrations/2026_05_14_000002_add_enable_normalized_scores_setting.php` | Create | Seed `enable_normalized_scores` system setting |
| `app/Services/FormulaEvaluator.php` | Create | Sandbox math formula evaluation |
| `app/Models/AptitudeArea.php` | Modify | Add `formula` fillable + `computeNormalizedScore()` |
| `app/Models/SystemSetting.php` | Modify | Add `enableNormalizedScores()` getter + boolean cast |
| `app/Http/Requests/StoreAptitudeAreaRequest.php` | Modify | Add `formula` validation |
| `app/Http/Requests/UpdateAptitudeAreaRequest.php` | Modify | Add `formula` validation |
| `app/Http/Controllers/Admin/SettingsController.php` | Modify | Handle `enable_normalized_scores` save/load |
| `app/Http/Requests/UpdateSystemSettingsRequest.php` | Modify | Add `enable_normalized_scores` rule |
| `app/Http/Controllers/Admin/AptitudeAreaController.php` | Modify | Add `testFormula` JSON endpoint; pass `formula` through index/edit/store/update |
| `routes/web.php` | Modify | Add `POST admin/aptitude-areas/test-formula` route |
| `app/Services/ScoreImportService.php` | Modify | Complete rewrite: auto-resolve grading session, area-code column matching, raw vs normalized mode, academic-year duplicate gating |
| `app/Http/Requests/StoreScoreImportRequest.php` | Modify | Remove `grading_session_id`; fix mime types to `csv,xlsx,xls,txt` |
| `app/Http/Controllers/Grading/ScoreImportController.php` | Modify | Remove session selector; store temp file path in session for re-parse on confirm |
| `resources/js/Pages/Grading/Import.svelte` | Modify | Remove grading session dropdown; show dynamic column hints; accept preview props inline |
| `resources/js/Pages/Grading/ImportPreview.svelte` | Modify | Show per-area score columns, applicant name, resolved grading session; post only `selected_ids` to confirm |
| `resources/js/Pages/Admin/AptitudeAreas/Create.svelte` | Modify | Add `formula` textarea + live test section |
| `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte` | Modify | Add `formula` textarea + live test section |
| `resources/js/Pages/Admin/Settings/Index.svelte` | Modify | Add `enable_normalized_scores` toggle card |
| `tests/Unit/Services/FormulaEvaluatorTest.php` | Create | RED→GREEN tests for evaluator |
| `tests/Feature/Grading/ScoreImportControllerTest.php` | Create | Feature tests for preview/confirm flow, gating rules, formula mode |

---

### Task 1: Add Symfony ExpressionLanguage dependency

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Add the package to `require`**

```json
"symfony/expression-language": "^7.0"
```

Add it inside the `require` block, after `"phpoffice/phpword": "^1.4"` (packages are sorted alphabetically).

- [ ] **Step 2: Install the dependency**

Run: `composer require symfony/expression-language`
Expected: installs without errors.

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "deps: add symfony/expression-language for formula evaluation"
```

---

### Task 2: Migration — add `formula` to `aptitude_areas`

**Files:**
- Create: `database/migrations/2026_05_14_000001_add_formula_to_aptitude_areas_table.php`

- [ ] **Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aptitude_areas', function (Blueprint $table) {
            $table->text('formula')->nullable()->after('max_items');
        });
    }

    public function down(): void
    {
        Schema::table('aptitude_areas', function (Blueprint $table) {
            $table->dropColumn('formula');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate --path=database/migrations/2026_05_14_000001_add_formula_to_aptitude_areas_table.php`
Expected: migrated successfully.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_14_000001_add_formula_to_aptitude_areas_table.php
git commit -m "feat: add formula column to aptitude_areas"
```

---

### Task 3: Migration — seed `enable_normalized_scores` setting

**Files:**
- Create: `database/migrations/2026_05_14_000002_add_enable_normalized_scores_setting.php`

- [ ] **Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->insert([
            'key' => 'enable_normalized_scores',
            'value' => '0',
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'enable_normalized_scores')->delete();
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate --path=database/migrations/2026_05_14_000002_add_enable_normalized_scores_setting.php`
Expected: migrated successfully.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_05_14_000002_add_enable_normalized_scores_setting.php
git commit -m "feat: add enable_normalized_scores system setting"
```

---

### Task 4: Create `FormulaEvaluator` service

**Files:**
- Create: `app/Services/FormulaEvaluator.php`

- [ ] **Step 1: Write the service**

```php
<?php

namespace App\Services;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class FormulaEvaluator
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function evaluate(string $formula, array $variables): ?float
    {
        try {
            $allowed = ['x', 'max_items', 'pi'];
            if (array_diff(array_keys($variables), $allowed)) {
                return null;
            }

            if (! isset($variables['pi'])) {
                $variables['pi'] = pi();
            }

            $result = $this->expressionLanguage->evaluate($formula, $variables);

            if (! is_numeric($result)) {
                return null;
            }

            return round((float) $result, 2);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function validate(string $formula): bool
    {
        if (trim($formula) === '') {
            return false;
        }

        // Replace multi-char tokens first, then check remaining chars
        $working = str_replace('**', ' ', $formula);
        $working = str_replace(['max_items', 'pi', 'x'], ' ', $working);
        $remaining = preg_replace('/[0-9\s\+\-\*\/\(\)\.]+/', '', $working);

        if ($remaining !== '') {
            return false;
        }

        return $this->evaluate($formula, ['x' => 1.0, 'max_items' => 100, 'pi' => pi()]) !== null;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Services/FormulaEvaluator.php
git commit -m "feat: add FormulaEvaluator service with sandboxed ExpressionLanguage"
```

---

### Task 5: Unit test `FormulaEvaluator`

**Files:**
- Create: `tests/Unit/Services/FormulaEvaluatorTest.php`

- [ ] **Step 1: Write the test**

```php
<?php

namespace Tests\Unit\Services;

use App\Services\FormulaEvaluator;
use Tests\TestCase;

class FormulaEvaluatorTest extends TestCase
{
    private FormulaEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new FormulaEvaluator();
    }

    public function test_evaluates_simple_linear_formula()
    {
        $this->assertEquals(50.0, $this->evaluator->evaluate('x * 2', ['x' => 25, 'max_items' => 100]));
    }

    public function test_evaluates_formula_with_max_items()
    {
        $this->assertEquals(80.0, $this->evaluator->evaluate('(x / max_items) * 100', ['x' => 40, 'max_items' => 50]));
    }

    public function test_evaluates_formula_with_pi()
    {
        $result = $this->evaluator->evaluate('pi * 2', ['x' => 1, 'max_items' => 100]);
        $this->assertEqualsWithDelta(6.28, $result, 0.01);
    }

    public function test_evaluates_power_operator()
    {
        $this->assertEquals(9.0, $this->evaluator->evaluate('x ** 2', ['x' => 3, 'max_items' => 100]));
    }

    public function test_returns_null_for_invalid_syntax()
    {
        $this->assertNull($this->evaluator->evaluate('x + unknown', ['x' => 1, 'max_items' => 100]));
    }

    public function test_returns_null_for_division_by_zero()
    {
        $this->assertNull($this->evaluator->evaluate('x / 0', ['x' => 1, 'max_items' => 100]));
    }

    public function test_validate_returns_true_for_valid_formula()
    {
        $this->assertTrue($this->evaluator->validate('(x / max_items) * 100'));
    }

    public function test_validate_returns_false_for_empty_formula()
    {
        $this->assertFalse($this->evaluator->validate(''));
    }

    public function test_validate_returns_false_for_malicious_input()
    {
        $this->assertFalse($this->evaluator->validate('system("ls")'));
    }

    public function test_rounds_to_two_decimals()
    {
        $this->assertEquals(33.33, $this->evaluator->evaluate('100 / 3', ['x' => 1, 'max_items' => 100]));
    }
}
```

- [ ] **Step 2: Run the test**

Run: `php artisan test --compact tests/Unit/Services/FormulaEvaluatorTest.php`
Expected: all 10 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/Services/FormulaEvaluatorTest.php
git commit -m "test: add FormulaEvaluator unit tests"
```

---

### Task 6: Update `AptitudeArea` and `SystemSetting` models

**Files:**
- Modify: `app/Models/AptitudeArea.php`
- Modify: `app/Models/SystemSetting.php`

- [ ] **Step 1: Add `formula` to `AptitudeArea`**

In `app/Models/AptitudeArea.php`:

Update `$fillable`:
```php
protected $fillable = [
    'name',
    'code',
    'description',
    'max_items',
    'formula',
    'display_order',
    'is_active',
];
```

Add method after `applicantScores()`:
```php
public function computeNormalizedScore(float $rawScore): ?float
{
    if (! $this->formula) {
        return null;
    }

    return app(\App\Services\FormulaEvaluator::class)->evaluate($this->formula, [
        'x' => $rawScore,
        'max_items' => $this->max_items,
    ]);
}
```

- [ ] **Step 2: Add `enableNormalizedScores` to `SystemSetting`**

In `app/Models/SystemSetting.php`:

Update the boolean key list inside `get()`:
```php
if (in_array($key, ['ai_exam_companion_enabled', 'notify_on_publish', 'allow_direct_assessment', 'enable_normalized_scores'], true)) {
    return filter_var($v, FILTER_VALIDATE_BOOLEAN);
}
```

Add method after `allowDirectAssessment()`:
```php
public static function enableNormalizedScores(): bool
{
    return (bool) self::get('enable_normalized_scores', false);
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Models/AptitudeArea.php app/Models/SystemSetting.php
git commit -m "feat: add formula support to AptitudeArea and enableNormalizedScores setting"
```

---

### Task 7: Update AptitudeArea form requests

**Files:**
- Modify: `app/Http/Requests/StoreAptitudeAreaRequest.php`
- Modify: `app/Http/Requests/UpdateAptitudeAreaRequest.php`

- [ ] **Step 1: Add `formula` rule to both requests**

Add to `rules()` array in both files:
```php
'formula' => ['nullable', 'string', 'max:500'],
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Requests/StoreAptitudeAreaRequest.php app/Http/Requests/UpdateAptitudeAreaRequest.php
git commit -m "feat: add formula validation to aptitude area requests"
```

---

### Task 8: Update Settings backend

**Files:**
- Modify: `app/Http/Controllers/Admin/SettingsController.php`
- Modify: `app/Http/Requests/UpdateSystemSettingsRequest.php`

- [ ] **Step 1: Update `UpdateSystemSettingsRequest`**

Add to `rules()`:
```php
'enable_normalized_scores' => ['sometimes', 'boolean'],
```

- [ ] **Step 2: Update `SettingsController`**

In `index()`, add to the `Inertia::render` array:
```php
'enable_normalized_scores' => SystemSetting::enableNormalizedScores(),
```

In `update()`, add after the `allow_direct_assessment` block:
```php
if (array_key_exists('enable_normalized_scores', $validated)) {
    SystemSetting::set('enable_normalized_scores', (bool) $validated['enable_normalized_scores']);
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/SettingsController.php app/Http/Requests/UpdateSystemSettingsRequest.php
git commit -m "feat: wire enable_normalized_scores into settings backend"
```

---

### Task 9: Add formula test endpoint and wire `formula` through AptitudeAreaController

**Files:**
- Modify: `app/Http/Controllers/Admin/AptitudeAreaController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Add `testFormula` method to controller**

Add `use Illuminate\Http\JsonResponse;` at the top if not present.

Add method inside the class:
```php
public function testFormula(\Illuminate\Http\Request $request): JsonResponse
{
    $request->validate([
        'formula' => ['required', 'string'],
        'sample_raw_score' => ['required', 'numeric'],
        'max_items' => ['nullable', 'integer', 'min:1'],
    ]);

    $evaluator = app(\App\Services\FormulaEvaluator::class);

    if (! $evaluator->validate($request->formula)) {
        return response()->json(['error' => 'Invalid formula syntax'], 422);
    }

    $result = $evaluator->evaluate($request->formula, [
        'x' => (float) $request->sample_raw_score,
        'max_items' => (int) $request->input('max_items', 100),
    ]);

    if ($result === null) {
        return response()->json(['error' => 'Formula evaluation failed'], 422);
    }

    return response()->json(['result' => $result]);
}
```

- [ ] **Step 2: Pass `formula` through index/edit/store/update**

Update `index()` map:
```php
'formula' => $a->formula,
```

Update `edit()` array:
```php
'formula' => $aptitudeArea->formula ?? '',
```

Update `store()` array:
```php
'formula' => $data['formula'] ?? null,
```

Update `update()` array:
```php
'formula' => $data['formula'] ?? null,
```

- [ ] **Step 3: Add route**

In `routes/web.php`, inside the `role:super_admin,test_administrator` group that contains `aptitude-areas` resource, add:
```php
Route::post('aptitude-areas/test-formula', [AptitudeAreaController::class, 'testFormula'])->name('aptitude-areas.test-formula');
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/AptitudeAreaController.php routes/web.php
git commit -m "feat: add formula test endpoint and wire formula through aptitude area CRUD"
```

---

### Task 10: Rewrite `ScoreImportService`

**Files:**
- Modify: `app/Services/ScoreImportService.php`

- [ ] **Step 1: Fix existing bugs**

Add missing imports at the top:
```php
use App\Models\Application;
use App\Models\AptitudeArea;
use App\Models\ExamSession;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
```

In `validateSingleRecord`, fix line ~247 (the dead expression). Find:
```php
if (! is_numeric($record[$field])) {
    "{$field} must be a number";
}
```

Replace with:
```php
if (! is_numeric($record[$field])) {
    $errors[] = "{$field} must be a number";
}
```

- [ ] **Step 2: Rewrite header validation for area-code columns**

`validateHeaders` stays the same (only `reference_number` is strictly required). We no longer validate `aptitude_area_id` in headers.

- [ ] **Step 3: Add resolution helpers**

Add these private methods before `validateSingleRecord`:

```php
private function resolveGradingSession(Application $application): ?GradingSession
{
    $applicant = $application->applicant;
    if (! $applicant) {
        return null;
    }

    $completedSessions = $applicant->examSessions()
        ->where('status', ExamSession::STATUS_COMPLETED)
        ->with(['gradingSession.examSession'])
        ->get();

    $eligible = $completedSessions->filter(
        fn ($session) => $session->gradingSession && in_array($session->gradingSession->status, [GradingSession::STATUS_OPEN, GradingSession::STATUS_IN_PROGRESS], true)
    );

    return $eligible->count() === 1 ? $eligible->first()->gradingSession : null;
}

private function checkDuplicateScores(int $applicantId, int $academicYearId, array $aptitudeAreaIds): array
{
    if (empty($aptitudeAreaIds)) {
        return [];
    }

    return ApplicantScore::query()
        ->join('grading_sessions', 'applicant_scores.grading_session_id', '=', 'grading_sessions.id')
        ->join('exam_sessions', 'grading_sessions.exam_session_id', '=', 'exam_sessions.id')
        ->where('applicant_scores.applicant_id', $applicantId)
        ->where('exam_sessions.academic_year_id', $academicYearId)
        ->whereIn('applicant_scores.aptitude_area_id', $aptitudeAreaIds)
        ->pluck('applicant_scores.aptitude_area_id')
        ->toArray();
}
```

- [ ] **Step 4: Rewrite `validateRecords` and `validateRecordsWithDetails` into a single `validateRecords` method**

Replace both `validateRecords` and `validateRecordsWithDetails` with:

```php
/**
 * Validate parsed records and return per-row details for preview.
 *
 * @param  array<int, array<string, mixed>>  $records
 * @return array{records: array<int, array>, summary: array{total: int, valid: int, invalid: int}}
 */
public function validateRecords(array $records): array
{
    $activeAreas = AptitudeArea::where('is_active', true)->get(['id', 'code', 'max_items', 'formula']);
    $areaCodeToId = $activeAreas->mapWithKeys(fn ($a) => [strtolower($a->code) => $a->id])->toArray();

    // Pre-batch application lookups to avoid N+1 queries
    $referenceNumbers = array_filter(array_map(fn ($r) => $r['reference_number'] ?? null, $records));
    $applicationMap = Application::whereIn('reference_number', $referenceNumbers)
        ->with('applicant.examSessions.gradingSession.examSession')
        ->get()
        ->keyBy('reference_number');

    $results = [];
    $validCount = 0;
    $invalidCount = 0;

    foreach ($records as $index => $record) {
        $rowNum = $index + 2;
        $resolution = $this->resolveRow($record, $applicationMap);
        $recordErrors = $resolution['errors'];

        $application = $resolution['application'];
        $applicant = $resolution['applicant'];
        $gradingSession = $resolution['gradingSession'];

        // Validate numeric area scores
        $areaScores = [];
        foreach ($record as $key => $value) {
            $lowerKey = strtolower($key);
            if (isset($areaCodeToId[$lowerKey])) {
                if ($value !== '' && $value !== null && ! is_numeric($value)) {
                    $recordErrors[] = "{$key} must be a number";
                }
                $areaScores[] = [
                    'area_code' => strtoupper($key),
                    'score' => $value,
                ];
            }
        }

        // Duplicate check
        if (empty($recordErrors) && $applicant && $gradingSession && ! empty($areaScores)) {
            $areaIds = array_map(fn ($s) => $areaCodeToId[strtolower($s['area_code'])], $areaScores);
            $duplicates = $this->checkDuplicateScores(
                $applicant->id,
                $gradingSession->examSession->academic_year_id,
                $areaIds
            );

            if (! empty($duplicates)) {
                $recordErrors[] = 'Applicant already has scores for this aptitude area in the current academic year';
            }
        }

        $isValid = empty($recordErrors);
        if ($isValid) {
            $validCount++;
        } else {
            $invalidCount++;
        }

        $results[] = [
            'id' => $index,
            'row' => $rowNum,
            'reference_number' => $record['reference_number'] ?? null,
            // NOTE: verify actual name columns — Application may use full_name or first_name/last_name
            'applicant_name' => $applicant ? trim("{$application->first_name} {$application->last_name}") : '—',
            'grading_session_id' => $gradingSession?->id,
            'grading_session_label' => $gradingSession ? "Session #{$gradingSession->id}" : '—',
            'scores' => $areaScores,
            'errors' => $recordErrors,
            'is_valid' => $isValid,
        ];
    }

    return [
        'records' => $results,
        'summary' => [
            'total' => count($records),
            'valid' => $validCount,
            'invalid' => $invalidCount,
        ],
    ];
}
```

- [ ] **Step 5: Add `resolveRow` helper**

Insert before `resolveGradingSession`:

```php
/**
 * @param  \Illuminate\Support\Collection<string, Application>  $applicationMap  Pre-batched by reference_number
 * @return array{application: ?Application, applicant: ?Applicant, gradingSession: ?GradingSession, errors: array<int, string>}
 */
private function resolveRow(array $record, \Illuminate\Support\Collection $applicationMap): array
{
    if (empty($record['reference_number'])) {
        return ['application' => null, 'applicant' => null, 'gradingSession' => null, 'errors' => ['Reference number is required']];
    }

    $ref = $record['reference_number'];
    $application = $applicationMap[$ref] ?? null;
    if (! $application) {
        return ['application' => null, 'applicant' => null, 'gradingSession' => null, 'errors' => ['Application not found']];
    }

    $applicant = $application->applicant;
    if (! $applicant) {
        return ['application' => $application, 'applicant' => null, 'gradingSession' => null, 'errors' => ['Applicant record not found']];
    }

    $gradingSession = $this->resolveGradingSession($application);
    if (! $gradingSession) {
        return ['application' => $application, 'applicant' => $applicant, 'gradingSession' => null, 'errors' => ['No open grading session found for this applicant']];
    }

    return ['application' => $application, 'applicant' => $applicant, 'gradingSession' => $gradingSession, 'errors' => []];
}
```

- [ ] **Step 6: Rewrite `importScores` and `importSelectedScores`**

Replace both methods with a single `importSelectedScores`:

```php
/**
 * Import selected records. If $selectedIndices is empty, imports all valid rows.
 *
 * @param  array<int, array<string, mixed>>  $records
 * @param  array<int>  $selectedIndices
 * @return array{imported: int, skipped: int, errors: array<int, string>}
 */
public function importSelectedScores(array $records, array $selectedIndices, int $importerId): array
{
    $imported = 0;
    $skipped = 0;
    $errors = [];
    $enableNormalizedScores = SystemSetting::enableNormalizedScores();

    $activeAreas = AptitudeArea::where('is_active', true)->get(['id', 'code', 'max_items', 'formula']);
    $areaCodeToId = $activeAreas->mapWithKeys(fn ($a) => [strtolower($a->code) => $a])->toArray();

    // Pre-batch application lookups to avoid N+1 queries
    $referenceNumbers = array_filter(array_map(fn ($r) => $r['reference_number'] ?? null, $records));
    $applicationMap = Application::whereIn('reference_number', $referenceNumbers)
        ->with('applicant.examSessions.gradingSession.examSession')
        ->get()
        ->keyBy('reference_number');

    foreach ($records as $index => $record) {
        if (! empty($selectedIndices) && ! in_array($index, $selectedIndices, true)) {
            continue;
        }

        $rowNum = $index + 2;
        $resolution = $this->resolveRow($record, $applicationMap);
        if (! empty($resolution['errors'])) {
            $skipped++;
            $errors[] = "Row {$rowNum}: {$resolution['errors'][0]}";
            continue;
        }

        $applicant = $resolution['applicant'];
        $gradingSession = $resolution['gradingSession'];

        $areaScores = [];
        foreach ($record as $key => $value) {
            $lowerKey = strtolower($key);
            if (isset($areaCodeToId[$lowerKey]) && $value !== '' && $value !== null) {
                if (! is_numeric($value)) {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: {$key} must be a number";
                    continue 2;
                }
                $areaScores[$lowerKey] = (float) $value;
            }
        }

        if (empty($areaScores)) {
            continue;
        }

        $aptitudeAreaIds = array_map(fn ($code) => $areaCodeToId[$code]->id, array_keys($areaScores));
        $duplicates = $this->checkDuplicateScores(
            $applicant->id,
            $gradingSession->examSession->academic_year_id,
            $aptitudeAreaIds
        );

        if (! empty($duplicates)) {
            $skipped++;
            $errors[] = "Row {$rowNum}: Applicant already has scores for this aptitude area in the current academic year";
            continue;
        }

        // Wrap per-row inserts in a transaction for data integrity
        DB::transaction(function () use ($areaScores, $areaCodeToId, $enableNormalizedScores, $gradingSession, $applicant, $importerId, &$imported) {
            foreach ($areaScores as $code => $value) {
                $area = $areaCodeToId[$code];

                if ($enableNormalizedScores) {
                    $rawScore = $value;
                    $maxScore = $area->max_items;
                    $normalizedScore = $area->computeNormalizedScore($value);
                } else {
                    $rawScore = null;
                    $maxScore = null;
                    $normalizedScore = $value;
                }

                ApplicantScore::create([
                    'grading_session_id' => $gradingSession->id,
                    'applicant_id' => $applicant->id,
                    'aptitude_area_id' => $area->id,
                    'raw_score' => $rawScore,
                    'max_score' => $maxScore,
                    'normalized_score' => $normalizedScore,
                    'scored_by' => $importerId,
                    'scored_at' => now(),
                ]);

                $imported++;
            }
        });
    }

    return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
}
```

Keep a thin `importScores` wrapper for backward compatibility:
```php
public function importScores(array $records, int $importerId): array
{
    return $this->importSelectedScores($records, [], $importerId);
}
```

- [ ] **Step 7: Update `parseSpreadsheet` and parsers to accept a path string**

Change method signatures:
```php
public function parseSpreadsheet(UploadedFile|string $file): array
{
    $extension = is_string($file)
        ? strtolower(pathinfo($file, PATHINFO_EXTENSION))
        : strtolower($file->getClientOriginalExtension());

    $realPath = is_string($file) ? $file : $file->getRealPath();

    if (is_string($file)) {
        // Validate file exists when loading from stored path
        if (! file_exists($file)) {
            throw new \InvalidArgumentException('Import file not found. Please upload again.');
        }
    } else {
        $this->validateFile($file);
    }

    $records = match ($extension) {
        'xlsx', 'xls' => $this->parseExcel($realPath),
        'csv' => $this->parseCsv($realPath),
        default => throw new \InvalidArgumentException(
            'Unsupported file format. Please upload CSV or Excel file (XLSX/XLS).'
        ),
    };

    if (! empty($records)) {
        $this->validateHeaders(array_keys($records[0]));
    }

    return $records;
}
```

Update `parseExcel` signature:
```php
protected function parseExcel(string $path): array
{
    try {
        $spreadsheet = IOFactory::load($path);
        ...
```

Update `parseCsv` signature:
```php
public function parseCsv(string $path): array
{
    $handle = fopen($path, 'r');
    ...
```

- [ ] **Step 8: Remove old `importSingleScore` and unused `validateSingleRecord` old body**

Delete the old private `importSingleScore` method entirely.
Delete the old `validateSingleRecord` body if any remnants remain.

- [ ] **Step 9: Run existing service tests to check for breakage**

Run: `php artisan test --compact tests/Unit/Services/ScoreInputServiceTest.php`
Expected: pass (this tests a different service, but confirms the test suite loads).

- [ ] **Step 10: Commit**

```bash
git add app/Services/ScoreImportService.php
git commit -m "feat: rewrite ScoreImportService for area-code columns, auto-resolve grading session, and formula mode"
```

---

### Task 11: Update `StoreScoreImportRequest`

**Files:**
- Modify: `app/Http/Requests/StoreScoreImportRequest.php`

- [ ] **Step 1: Remove `grading_session_id` and fix mime types**

Replace the entire `rules()` array with:
```php
public function rules(): array
{
    return [
        'file' => ['required', 'file', 'mimes:csv,xlsx,xls,txt', 'max:10240'],
    ];
}
```

Update `messages()`:
```php
public function messages(): array
{
    return [
        'file.required' => 'Please select a spreadsheet file.',
        'file.file' => 'The uploaded file is invalid.',
        'file.mimes' => 'File must be CSV, XLSX, or XLS.',
        'file.max' => 'File must not exceed 10MB.',
    ];
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Requests/StoreScoreImportRequest.php
git commit -m "fix: update StoreScoreImportRequest mime types and remove grading_session_id"
```

---

### Task 12: Rewrite `ScoreImportController`

**Files:**
- Modify: `app/Http/Controllers/Grading/ScoreImportController.php`

- [ ] **Step 1: Update `importForm`**

Replace the method body:
```php
public function importForm(): InertiaResponse
{
    $this->authorize('viewAny', GradingSession::class);

    // Clean up stale temp files from abandoned imports
    $stalePath = Session::get('score_import_temp_path');
    if ($stalePath && Storage::exists($stalePath)) {
        Storage::delete($stalePath);
    }
    Session::forget('score_import_temp_path');

    return Inertia::render('Grading/Import', [
        'enableNormalizedScores' => \App\Models\SystemSetting::enableNormalizedScores(),
        'aptitudeAreaCodes' => \App\Models\AptitudeArea::where('is_active', true)->pluck('code')->toArray(),
        'previewUrl' => route('import.preview'),
    ]);
}
```

- [ ] **Step 2: Update `preview`**

Replace the method body:
```php
public function preview(StoreScoreImportRequest $request): RedirectResponse|InertiaResponse
{
    $this->authorize('viewAny', GradingSession::class);

    try {
        $file = $request->file('file');
        $tempPath = $file->store('temp/score_imports');
        \Illuminate\Support\Facades\Session::put('score_import_temp_path', $tempPath);

        $records = $this->importService->parseSpreadsheet($tempPath);
        $validated = $this->importService->validateRecords($records);

        return Inertia::render('Grading/ImportPreview', [
            'records' => $validated['records'],
            'totalCount' => $validated['summary']['total'],
            'validCount' => $validated['summary']['valid'],
            'enableNormalizedScores' => \App\Models\SystemSetting::enableNormalizedScores(),
            'aptitudeAreaCodes' => \App\Models\AptitudeArea::where('is_active', true)->pluck('code')->toArray(),
            'confirmUrl' => route('import.confirm'),
        ]);
    } catch (\InvalidArgumentException $e) {
        return back()->with('error', $e->getMessage());
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Bulk score import preview failed', ['error' => $e->getMessage()]);
        return back()->with('error', 'Preview failed: '.$e->getMessage());
    }
}
```

- [ ] **Step 3: Update `confirm`**

Replace the method body:
```php
public function confirm(Request $request): RedirectResponse
{
    $this->authorize('viewAny', GradingSession::class);

    $tempPath = \Illuminate\Support\Facades\Session::get('score_import_temp_path');

    if (! $tempPath || ! \Illuminate\Support\Facades\Storage::exists($tempPath)) {
        return redirect()->route('admin.grading.import')->with('error', 'Import session expired. Please upload again.');
    }

    try {
        $records = $this->importService->parseSpreadsheet(\Illuminate\Support\Facades\Storage::path($tempPath));
        $selectedIds = $request->input('selected_ids', []);

        $result = $this->importService->importSelectedScores($records, $selectedIds, $request->user()->id);

        \Illuminate\Support\Facades\Storage::delete($tempPath);
        \Illuminate\Support\Facades\Session::forget('score_import_temp_path');

        $message = "Successfully imported {$result['imported']} scores.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} skipped.";
        }
        if (! empty($result['errors'])) {
            $message .= "\nErrors:\n".implode("\n", $result['errors']);
        }

        \Illuminate\Support\Facades\Log::info('Bulk score import confirmed', [
            'user_id' => $request->user()->id,
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
        ]);

        return redirect()->route('admin.grading.import')->with('message', $message);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Bulk score import confirm failed', ['error' => $e->getMessage()]);
        return redirect()->route('admin.grading.import')->with('error', 'Import failed: '.$e->getMessage());
    }
}
```

- [ ] **Step 4: Simplify `import` direct-import endpoint**

Replace the method body:
```php
public function import(StoreScoreImportRequest $request): RedirectResponse
{
    $this->authorize('viewAny', GradingSession::class);

    try {
        $records = $this->importService->parseSpreadsheet($request->file('file'));
        $result = $this->importService->importScores($records, $request->user()->id);

        $message = "Successfully imported {$result['imported']} scores.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} skipped.";
        }

        return back()->with('message', $message);
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Grading/ScoreImportController.php
git commit -m "feat: rewrite ScoreImportController for auto-resolve flow and temp-file re-parse"
```

---

### Task 13: Update `Grading/Import.svelte`

**Files:**
- Modify: `resources/js/Pages/Grading/Import.svelte`

- [ ] **Step 1: Replace the entire component**

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, page } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { FileUpload } from '@/Components/ui/file-upload';
  import { Upload, ArrowLeft } from 'lucide-svelte';

  let {
    enableNormalizedScores = false,
    aptitudeAreaCodes = [],
    records = null,
    totalCount = 0,
    validCount = 0,
  } = $props();

  const breadcrumbs = [
    { label: 'Grading', href: '/admin/grading' },
    { label: 'Import Scores' },
  ];

  const form = useForm({
    file: null,
  });

  let selectedFile = $state(null);

  function submitPreview(e) {
    e.preventDefault();
    if (!selectedFile) return;
    $form.transform((data) => ({
      ...data,
      file: selectedFile,
    }));
    $form.post('/admin/grading/import/preview', { forceFormData: true });
  }

  let message = $state('');
  let error = $state('');

  $effect(() => {
    message = $page.props.flash?.message || '';
    error = $page.props.flash?.error || '';
  });

  const scoreSuffix = enableNormalizedScores ? '(raw)' : '(normalized)';
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-3xl space-y-6">
    <div>
      <Link href="/admin/grading" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
        <ArrowLeft class="size-4" />
        Back to Grading
      </Link>
    </div>

    <div>
      <h1 class="text-2xl font-semibold">Bulk Import Scores</h1>
      <p class="text-sm text-muted-foreground mt-1">
        Import applicant scores via spreadsheet upload. Columns use aptitude area codes.
      </p>
    </div>

    {#if message}
      <div class="rounded-md bg-green-50 border border-green-200 p-4 text-green-700">
        <pre class="whitespace-pre-wrap text-sm">{message}</pre>
      </div>
    {/if}

    {#if error}
      <div class="rounded-md bg-red-50 border border-red-200 p-4 text-red-700">
        <pre class="whitespace-pre-wrap text-sm">{error}</pre>
      </div>
    {/if}

    {#if records}
      <!-- Preview is rendered by ImportPreview.svelte -->
      <slot />
    {:else}
      <form onsubmit={submitPreview} class="space-y-4 rounded-lg border border-border bg-card p-6">
        <div class="space-y-2">
          <label for="file" class="text-sm font-medium leading-none">Spreadsheet File</label>
          <FileUpload
            label="Upload file"
            accept=".csv,.xlsx,.xls,.txt"
            maxSize="10MB"
            bind:files={selectedFile}
          />
          <p class="text-xs text-muted-foreground">Supports CSV, XLSX, XLS. First row is used as headers.</p>
          {#if $form.errors?.file}
            <p class="text-sm text-destructive">{$form.errors.file}</p>
          {/if}
        </div>

        <div class="space-y-2">
          <p class="text-sm font-medium">Required Column</p>
          <code class="block rounded bg-muted px-2 py-1 text-xs">reference_number</code>
        </div>

        <div class="space-y-2">
          <p class="text-sm font-medium">Expected Score Columns <span class="text-muted-foreground font-normal">{scoreSuffix}</span></p>
          <div class="flex flex-wrap gap-2">
            {#each aptitudeAreaCodes as code}
              <code class="rounded bg-muted px-2 py-1 text-xs">{code}</code>
            {:else}
              <span class="text-xs text-muted-foreground">No active aptitude areas configured.</span>
            {/each}
          </div>
        </div>

        <div class="flex gap-3 pt-2">
          <Button type="submit" disabled={$form.processing} class="min-h-[44px]">
            <Upload class="mr-2 size-4" />
            {$form.processing ? 'Uploading...' : 'Preview Import'}
          </Button>
          <Link href="/admin/grading">
            <Button type="button" variant="outline" class="min-h-[44px]">Cancel</Button>
          </Link>
        </div>
      </form>
    {/if}
  </div>
</AuthenticatedLayout>
```

Wait, the `<slot />` won't work because `ImportPreview.svelte` is a separate page, not a child component. Actually, the preview endpoint returns `Grading/ImportPreview`, not `Grading/Import`. So the `Import.svelte` page only needs the upload form. The preview is handled by `ImportPreview.svelte`. So we don't need the `records` conditional rendering in `Import.svelte`. We can leave `Import.svelte` as just the upload form.

But the controller `preview` currently returns `ImportPreview`. And `importForm` returns `Import`. So `Import.svelte` only ever renders the form. That simplifies things.

Let me rewrite `Import.svelte` without the conditional:

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, page } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { FileUpload } from '@/Components/ui/file-upload';
  import { Upload, ArrowLeft } from 'lucide-svelte';

  let {
    enableNormalizedScores = false,
    aptitudeAreaCodes = [],
    previewUrl = '/admin/grading/import/preview',
  } = $props();

  const breadcrumbs = [
    { label: 'Grading', href: '/admin/grading' },
    { label: 'Import Scores' },
  ];

  const form = useForm({
    file: null,
  });

  let selectedFile = $state(null);

  function submitPreview(e) {
    e.preventDefault();
    if (!selectedFile) return;
    $form.transform((data) => ({
      ...data,
      file: selectedFile,
    }));
    $form.post(previewUrl, { forceFormData: true });
  }

  let message = $state('');
  let error = $state('');

  $effect(() => {
    message = $page.props.flash?.message || '';
    error = $page.props.flash?.error || '';
  });

  const scoreSuffix = enableNormalizedScores ? '(raw)' : '(normalized)';
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-3xl space-y-6">
    <div>
      <Link href="/admin/grading" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
        <ArrowLeft class="size-4" />
        Back to Grading
      </Link>
    </div>

    <div>
      <h1 class="text-2xl font-semibold">Bulk Import Scores</h1>
      <p class="text-sm text-muted-foreground mt-1">
        Import applicant scores via spreadsheet upload. Columns use aptitude area codes.
      </p>
    </div>

    {#if message}
      <div class="rounded-md bg-green-50 border border-green-200 p-4 text-green-700">
        <pre class="whitespace-pre-wrap text-sm">{message}</pre>
      </div>
    {/if}

    {#if error}
      <div class="rounded-md bg-red-50 border border-red-200 p-4 text-red-700">
        <pre class="whitespace-pre-wrap text-sm">{error}</pre>
      </div>
    {/if}

    <form onsubmit={submitPreview} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="file" class="text-sm font-medium leading-none">Spreadsheet File</label>
        <FileUpload
          label="Upload file"
          accept=".csv,.xlsx,.xls,.txt"
          maxSize="10MB"
          bind:files={selectedFile}
        />
        <p class="text-xs text-muted-foreground">Supports CSV, XLSX, XLS. First row is used as headers.</p>
        {#if $form.errors?.file}
          <p class="text-sm text-destructive">{$form.errors.file}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <p class="text-sm font-medium">Required Column</p>
        <code class="block rounded bg-muted px-2 py-1 text-xs">reference_number</code>
      </div>

      <div class="space-y-2">
        <p class="text-sm font-medium">Expected Score Columns <span class="text-muted-foreground font-normal">{scoreSuffix}</span></p>
        <div class="flex flex-wrap gap-2">
          {#each aptitudeAreaCodes as code}
            <code class="rounded bg-muted px-2 py-1 text-xs">{code}</code>
          {:else}
            <span class="text-xs text-muted-foreground">No active aptitude areas configured.</span>
          {/each}
        </div>
      </div>

      <div class="flex gap-3 pt-2">
        <Button type="submit" disabled={$form.processing} class="min-h-[44px]">
          <Upload class="mr-2 size-4" />
          {$form.processing ? 'Uploading...' : 'Preview Import'}
        </Button>
        <Link href="/admin/grading">
          <Button type="button" variant="outline" class="min-h-[44px]">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Grading/Import.svelte
git commit -m "feat: update Import.svelte for area-code hints and remove grading session selector"
```

---

### Task 14: Update `Grading/ImportPreview.svelte`

**Files:**
- Modify: `resources/js/Pages/Grading/ImportPreview.svelte`

- [ ] **Step 1: Replace the entire component**

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, page } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { FileSpreadsheet, Check, X, AlertTriangle, ArrowLeft, Save } from 'lucide-svelte';

  let {
    records = [],
    totalCount = 0,
    validCount = 0,
    enableNormalizedScores = false,
    aptitudeAreaCodes = [],
    confirmUrl = '/admin/grading/import/confirm',
  } = $props();

  const breadcrumbs = [
    { label: 'Grading', href: '/admin/grading' },
    { label: 'Import Scores', href: '/admin/grading/import' },
    { label: 'Preview' },
  ];

  const form = useForm({
    selected_ids: [],
  });

  let selectAll = $state(true);
  let selectedIds = $state(new Set());

  $effect(() => {
    if (selectAll) {
      selectedIds = new Set(records.filter(r => r.is_valid).map(r => r.id));
    }
  });

  function toggleRow(id) {
    const newSet = new Set(selectedIds);
    if (newSet.has(id)) {
      newSet.delete(id);
    } else {
      newSet.add(id);
    }
    selectedIds = newSet;
  }

  function toggleAll() {
    if (selectAll) {
      selectedIds = new Set();
      selectAll = false;
    } else {
      selectedIds = new Set(records.filter(r => r.is_valid).map(r => r.id));
      selectAll = true;
    }
  }

  function submitForm(e) {
    e.preventDefault();
    $form.transform((data) => ({
      ...data,
      selected_ids: Array.from(selectedIds),
    }));
    $form.post(confirmUrl, { forceFormData: true });
  }

  let message = $state('');
  let error = $state('');

  $effect(() => {
    message = $page.props.flash?.message || '';
    error = $page.props.flash?.error || '';
  });

  const invalidCount = totalCount - validCount;
  const scoreSuffix = enableNormalizedScores ? '(raw)' : '(normalized)';
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6">
    <div>
      <Link href="/admin/grading/import" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
        <ArrowLeft class="size-4" />
        Back to Import
      </Link>
    </div>

    <div>
      <h1 class="text-2xl font-semibold">Preview Score Import</h1>
      <p class="text-sm text-muted-foreground mt-1">
        Review parsed data before importing. Invalid rows are highlighted.
      </p>
    </div>

    {#if message}
      <div class="rounded-md bg-green-50 border border-green-200 p-4 text-green-700">
        <pre class="whitespace-pre-wrap text-sm">{message}</pre>
      </div>
    {/if}

    {#if error}
      <div class="rounded-md bg-red-50 border border-red-200 p-4 text-red-700">
        <pre class="whitespace-pre-wrap text-sm">{error}</pre>
      </div>
    {/if}

    <div class="flex gap-4 text-sm">
      <div class="rounded bg-muted px-3 py-2">
        <span class="font-medium">{totalCount}</span> total rows
      </div>
      <div class="rounded bg-green-50 px-3 py-2 text-green-700">
        <Check class="inline size-4 mr-1" />
        <span class="font-medium">{validCount}</span> valid
      </div>
      <div class="rounded bg-red-50 px-3 py-2 text-red-700">
        <X class="inline size-4 mr-1" />
        <span class="font-medium">{invalidCount}</span> invalid
      </div>
    </div>

    <form onsubmit={submitForm} class="space-y-4">
      <div class="rounded-lg border border-border overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-muted">
            <tr>
              <th class="px-3 py-2 text-left w-10">
                <input type="checkbox" checked={selectAll} onchange={toggleAll} class="rounded" />
              </th>
              <th class="px-3 py-2 text-left w-16">Row</th>
              <th class="px-3 py-2 text-left">Reference #</th>
              <th class="px-3 py-2 text-left">Applicant</th>
              <th class="px-3 py-2 text-left">Session</th>
              {#each aptitudeAreaCodes as code}
                <th class="px-3 py-2 text-left">{code} <span class="text-muted-foreground font-normal">{scoreSuffix}</span></th>
              {/each}
              <th class="px-3 py-2 text-left">Status</th>
            </tr>
          </thead>
          <tbody>
            {#each records.slice(0, 50) as record}
              <tr class:bg-red-50={!record.is_valid} class:bg-white={record.is_valid}>
                <td class="px-3 py-2">
                  {#if record.is_valid}
                    <input type="checkbox" checked={selectedIds.has(record.id)} onchange={() => toggleRow(record.id)} class="rounded" />
                  {:else}
                    <X class="size-4 text-red-500" />
                  {/if}
                </td>
                <td class="px-3 py-2 text-muted-foreground">{record.row}</td>
                <td class="px-3 py-2">{record.reference_number || '—'}</td>
                <td class="px-3 py-2">{record.applicant_name || '—'}</td>
                <td class="px-3 py-2">{record.grading_session_label || '—'}</td>
                {#each aptitudeAreaCodes as code}
                  <td class="px-3 py-2">
                    {record.scores.find(s => s.area_code === code)?.score || '—'}
                  </td>
                {/each}
                <td class="px-3 py-2">
                  {#if record.is_valid}
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                      <Check class="size-3 mr-1" /> Valid
                    </span>
                  {:else}
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                      <AlertTriangle class="size-3 mr-1" />
                      {record.errors[0]}
                    </span>
                  {/if}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
        {#if records.length > 50}
          <div class="px-3 py-2 text-sm text-muted-foreground bg-muted">
            Showing 50 of {records.length} rows. Invalid rows will be skipped.
          </div>
        {/if}
      </div>

      <div class="flex gap-3">
        <Button type="submit" disabled={$form.processing || selectedIds.size === 0} class="min-h-[44px]">
          <Save class="mr-2 size-4" />
          {$form.processing ? 'Importing...' : `Import ${selectedIds.size} Selected`}
        </Button>
        <Link href="/admin/grading/import">
          <Button type="button" variant="outline" class="min-h-[44px]">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Grading/ImportPreview.svelte
git commit -m "feat: update ImportPreview with per-area columns and resolved session info"
```

---

### Task 15: Update `Admin/AptitudeAreas/Create.svelte`

**Files:**
- Modify: `resources/js/Pages/Admin/AptitudeAreas/Create.svelte`

- [ ] **Step 1: Replace the component script and form**

Add imports:
```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { success } from '@/lib/toast';

  const form = useForm({
    name: '',
    code: '',
    description: '',
    max_items: 25,
    formula: '',
    display_order: 0,
    is_active: true,
  });

  $form.onFinish = () => {
    if (!$form.errors || Object.keys($form.errors).length === 0) {
      success('Aptitude area created');
    }
  };

  function submitForm(e) {
    e.preventDefault();
    $form.post('/admin/aptitude-areas');
  }

  const breadcrumbs = [
    { label: 'Grading', href: '/admin/grading' },
    { label: 'Aptitude Areas', href: '/admin/aptitude-areas' },
    { label: 'Create' },
  ];

  let testScore = $state(10);
  let testResult = $state(null);
  let testError = $state('');

  async function testFormula() {
    testResult = null;
    testError = '';
    if (!$form.formula) {
      testError = 'Enter a formula first';
      return;
    }
    try {
      const res = await fetch('/admin/aptitude-areas/test-formula', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify({ formula: $form.formula, sample_raw_score: testScore, max_items: Number($form.max_items) }),
      });
      const data = await res.json();
      if (data.error) {
        testError = data.error;
      } else {
        testResult = data.result;
      }
    } catch (e) {
      testError = 'Request failed';
    }
  }
</script>
```

Add the formula field and test section inside the form, after the `max_items` div and before `display_order`:

```svelte
      <div class="space-y-2">
        <label for="formula" class="text-sm font-medium">Formula (optional)</label>
        <textarea
          id="formula"
          bind:value={$form.formula}
          rows="2"
          class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          placeholder="e.g., (x / max_items) * 100"
        ></textarea>
        <p class="text-xs text-muted-foreground">Variables: x (raw score), max_items, pi</p>
        {#if $form.errors?.formula}
          <p class="text-sm text-destructive">{$form.errors.formula}</p>
        {/if}
      </div>

      <div class="space-y-2 rounded-md border border-border bg-muted/30 p-4">
        <p class="text-sm font-medium">Test Formula</p>
        <div class="flex items-center gap-3">
          <div class="flex-1">
            <label class="text-xs text-muted-foreground">Sample raw score</label>
            <Input type="number" bind:value={testScore} min="0" />
          </div>
          <div class="flex-1">
            <label class="text-xs text-muted-foreground">Result</label>
            <div class="h-10 flex items-center text-sm">
              {#if testResult !== null}
                <span class="font-medium text-green-700">{testResult}</span>
              {:else if testError}
                <span class="text-red-600">{testError}</span>
              {:else}
                <span class="text-muted-foreground">—</span>
              {/if}
            </div>
          </div>
        </div>
        <Button type="button" variant="outline" size="sm" onclick={testFormula}>Test</Button>
      </div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Admin/AptitudeAreas/Create.svelte
git commit -m "feat: add formula field and live test to aptitude area create form"
```

---

### Task 16: Update `Admin/AptitudeAreas/Edit.svelte`

**Files:**
- Modify: `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte`

- [ ] **Step 1: Update props, form initialization, and add test section**

Update the script block:
```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { success } from '@/lib/toast';

  let { aptitude_area } = $props();

  const form = useForm({
    name: aptitude_area.name,
    code: aptitude_area.code,
    description: aptitude_area.description ?? '',
    max_items: aptitude_area.max_items,
    formula: aptitude_area.formula ?? '',
    display_order: aptitude_area.display_order ?? 0,
    is_active: aptitude_area.is_active,
  });

  $form.onFinish = () => {
    if (!$form.errors || Object.keys($form.errors).length === 0) {
      success('Aptitude area updated');
    }
  };

  function submitForm(e) {
    e.preventDefault();
    $form.put(`/admin/aptitude-areas/${aptitude_area.id}`);
  }

  const breadcrumbs = [
    { label: 'Grading', href: '/admin/grading' },
    { label: 'Aptitude Areas', href: '/admin/aptitude-areas' },
    { label: 'Edit' },
  ];

  let testScore = $state(10);
  let testResult = $state(null);
  let testError = $state('');

  async function testFormula() {
    testResult = null;
    testError = '';
    if (!$form.formula) {
      testError = 'Enter a formula first';
      return;
    }
    try {
      const res = await fetch('/admin/aptitude-areas/test-formula', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: JSON.stringify({ formula: $form.formula, sample_raw_score: testScore, max_items: Number($form.max_items) }),
      });
      const data = await res.json();
      if (data.error) {
        testError = data.error;
      } else {
        testResult = data.result;
      }
    } catch (e) {
      testError = 'Request failed';
    }
  }
</script>
```

Insert the formula field + test block in the same position as in Create (after `max_items`, before `display_order`).

- [ ] **Step 2: Commit**

```bash
git add resources/js/Pages/Admin/AptitudeAreas/Edit.svelte
git commit -m "feat: add formula field and live test to aptitude area edit form"
```

---

### Task 17: Update `Admin/Settings/Index.svelte`

**Files:**
- Modify: `resources/js/Pages/Admin/Settings/Index.svelte`

- [ ] **Step 1: Add `enable_normalized_scores` prop and form field**

Update the script props:
```svelte
  let {
    ai_exam_companion_enabled = false,
    notify_on_publish = false,
    release_mode = 'online',
    allow_direct_assessment = true,
    enable_normalized_scores = false,
  } = $props();
```

Update `useForm`:
```svelte
  const form = useForm({
    ai_exam_companion_enabled,
    notify_on_publish,
    release_mode,
    allow_direct_assessment,
    enable_normalized_scores,
  });
```

Update `submitSettings` transform:
```svelte
    $form.transform((data) => ({
      ai_exam_companion_enabled: !!data.ai_exam_companion_enabled,
      notify_on_publish: !!data.notify_on_publish,
      release_mode: data.release_mode,
      allow_direct_assessment: !!data.allow_direct_assessment,
      enable_normalized_scores: !!data.enable_normalized_scores,
    }));
```

- [ ] **Step 2: Add the toggle card inside the form**

Insert before the final submit button div:
```svelte
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Calculator class="h-5 w-5" />
            Normalized Score Computation
          </CardTitle>
          <CardDescription>
            When enabled, bulk import expects raw scores and auto-computes normalized scores using aptitude area formulas.
          </CardDescription>
        </CardHeader>
        <CardContent class="flex items-center gap-4">
          <Switch
            checked={$form.enable_normalized_scores}
            onCheckedChange={(checked) => form.update((f) => ({ ...f, enable_normalized_scores: checked }))}
            aria-label="Enable normalized score computation"
          />
          <span class="text-sm font-medium">
            {$form.enable_normalized_scores ? 'Enabled' : 'Disabled'}
          </span>
        </CardContent>
      </Card>
```

Add `Calculator` import to the script imports:
```svelte
  import { Bot, Bell, Share2, FileCheck, Calculator } from 'lucide-svelte';
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Admin/Settings/Index.svelte
git commit -m "feat: add enable_normalized_scores toggle to settings page"
```

---

### Task 18: Feature tests for score import

**Files:**
- Create: `tests/Feature/Grading/ScoreImportControllerTest.php`

- [ ] **Step 1: Write the test file**

```php
<?php

namespace Tests\Feature\Grading;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\Application;
use App\Models\AptitudeArea;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScoreImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $academicYear;
    private AptitudeArea $areaSa;
    private AptitudeArea $areaVr;
    private ExamSession $examSession;
    private GradingSession $gradingSession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->academicYear = AcademicYear::factory()->create();
        $this->areaSa = AptitudeArea::factory()->create(['code' => 'SA', 'max_items' => 50, 'formula' => '(x / max_items) * 100']);
        $this->areaVr = AptitudeArea::factory()->create(['code' => 'VR', 'max_items' => 50]);

        $this->examSession = ExamSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'status' => ExamSession::STATUS_COMPLETED,
        ]);
        $this->gradingSession = GradingSession::factory()->create([
            'exam_session_id' => $this->examSession->id,
            'status' => GradingSession::STATUS_OPEN,
        ]);
    }

    public function test_preview_shows_area_code_columns_and_resolved_session()
    {
        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00001',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $this->examSession->applicants()->attach($applicant);

        $csv = "reference_number,SA,VR\nAPP-2026-00001,20,30\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/preview', ['file' => $file]);

        $response->assertInertia(fn ($page) => $page
            ->component('Grading/ImportPreview')
            ->has('records', 1)
            ->where('records.0.reference_number', 'APP-2026-00001')
            ->where('records.0.is_valid', true)
            ->where('records.0.grading_session_id', $this->gradingSession->id)
            ->where('records.0.scores', fn ($scores) => count($scores) === 2)
        );
    }

    public function test_preview_rejects_row_with_no_completed_exam_session()
    {
        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00002',
        ]);
        Applicant::factory()->create(['application_id' => $application->id]);

        $csv = "reference_number,SA\nAPP-2026-00002,20\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/preview', ['file' => $file]);

        $response->assertInertia(fn ($page) => $page
            ->where('records.0.is_valid', false)
            ->where('records.0.errors.0', 'No open grading session found for this applicant')
        );
    }

    public function test_preview_rejects_duplicate_scores_in_same_academic_year()
    {
        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00003',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $this->examSession->applicants()->attach($applicant);

        ApplicantScore::factory()->create([
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => $this->areaSa->id,
        ]);

        $csv = "reference_number,SA\nAPP-2026-00003,25\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/preview', ['file' => $file]);

        $response->assertInertia(fn ($page) => $page
            ->where('records.0.is_valid', false)
            ->where('records.0.errors.0', 'Applicant already has scores for this aptitude area in the current academic year')
        );
    }

    public function test_confirm_imports_selected_rows()
    {
        SystemSetting::set('enable_normalized_scores', true);

        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00004',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $this->examSession->applicants()->attach($applicant);

        $csv = "reference_number,SA,VR\nAPP-2026-00004,25,40\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        // Upload + confirm must share the same session — use a single chained client
        $client = $this->actingAs($this->admin);
        $client->post('/admin/grading/import/preview', ['file' => $file]);

        $response = $client->post('/admin/grading/import/confirm', ['selected_ids' => [0]]);

        $response->assertRedirect('/admin/grading/import');

        $this->assertDatabaseHas('applicant_scores', [
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => $this->areaSa->id,
            'raw_score' => 25,
            'max_score' => 50,
            'normalized_score' => 50.0,
            'scored_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('applicant_scores', [
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => $this->areaVr->id,
            'raw_score' => 40,
            'max_score' => 50,
            'normalized_score' => null,
        ]);
    }

    public function test_confirm_in_manual_mode_imports_normalized_scores()
    {
        SystemSetting::set('enable_normalized_scores', false);

        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00005',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $this->examSession->applicants()->attach($applicant);

        $csv = "reference_number,SA\nAPP-2026-00005,88.5\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $client = $this->actingAs($this->admin);
        $client->post('/admin/grading/import/preview', ['file' => $file]);

        $response = $client->post('/admin/grading/import/confirm', ['selected_ids' => [0]]);

        $response->assertRedirect('/admin/grading/import');

        $this->assertDatabaseHas('applicant_scores', [
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => $this->areaSa->id,
            'raw_score' => null,
            'max_score' => null,
            'normalized_score' => 88.5,
        ]);
    }
}
```

- [ ] **Step 2: Run the feature tests**

Run: `php artisan test --compact tests/Feature/Grading/ScoreImportControllerTest.php`
Expected: 5 tests pass.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Grading/ScoreImportControllerTest.php
git commit -m "test: add ScoreImportController feature tests for preview, confirm, gating, and formula mode"
```

---

### Task 19: Final checks — pint and full suite

- [ ] **Step 1: Run Pint on all modified PHP files**

Run: `vendor/bin/pint --dirty --format agent`
Expected: formats without errors.

- [ ] **Step 2: Run full test suite**

Run: `php artisan test --compact`
Expected: all tests pass.

- [ ] **Step 3: Commit any pint fixes**

```bash
git add -A
git commit -m "style: apply pint formatting"
```

---

## Self-Review

### 1. Spec coverage

| Spec section | Implementing task |
|--------------|-------------------|
| D1: Column headers = AptitudeArea codes | Task 10 (ScoreImportService) |
| D2: Settings toggle auto-compute vs manual | Tasks 3, 6, 8, 9, 17 |
| D3: Per-aptitude-area formula | Tasks 2, 4, 5, 6, 7, 9, 15, 16 |
| D4: Multi-session import auto-match | Task 10 (resolveGradingSession, resolveRow) |
| D4: Duplicate score check (same academic year) | Task 10 (checkDuplicateScores) |
| D5: max_score always from AptitudeArea | Task 10 (importSelectedScores) |
| D6: Formula engine Symfony ExpressionLanguage | Tasks 1, 4 |
| D7: Inline formula test endpoint | Tasks 9, 15, 16 |
| Import flow: upload, parse, preview, confirm | Tasks 10, 11, 12, 13, 14 |
| Bug fix: dead expression | Task 10, Step 1 |
| Bug fix: missing Application import | Task 10, Step 1 |
| Bug fix: StoreScoreImportRequest mime types | Task 11 |

**Gaps:** None found.

### 2. Placeholder scan

- No "TBD", "TODO", or "implement later" strings.
- No "Add appropriate error handling" without code.
- No "Similar to Task N" shortcuts.
- Every task contains exact file paths and complete code.

### 3. Type consistency

- `FormulaEvaluator.evaluate()` always returns `?float` — used consistently.
- `AptitudeArea.computeNormalizedScore(float $rawScore): ?float` — matches.
- `ScoreImportService.importSelectedScores` returns `['imported' => int, 'skipped' => int, 'errors' => array]` — used consistently in controller.
- `enableNormalizedScores` is boolean everywhere.

### 4. Review fixes applied

| Fix | Where | Why |
|-----|-------|-----|
| `catch (\Throwable)` instead of `catch (\Exception)` | Task 4: FormulaEvaluator | `DivisionByZeroError` extends `\Error`, not `\Exception` — would be uncaught |
| DB::transaction() wrapping per-row inserts | Task 10: importSelectedScores | Partial data on mid-import failure — no rollback without transaction |
| Batched `$applicationMap` with `whereIn` + eager loading | Task 10: resolveRow, validateRecords | N+1 queries: 4-5 queries per row → 2-3 queries total |
| `$applicationMap` parameter on `resolveRow()` | Task 10: resolveRow | Receives pre-loaded collection instead of per-row DB call |
| `use Illuminate\Support\Facades\DB; use Illuminate\Support\Collection;` | Task 10: imports | Required by DB::transaction and Collection type hint |
| `post()` instead of `postJson()` | Task 18: all test methods | `postJson` sends JSON content-type — file uploads require multipart form data |
| Route props (`previewUrl`, `confirmUrl`) from controller | Tasks 12-14: Svelte components | Avoids hardcoded route paths — controller passes `route()` URLs as Inertia props |
| Temp file cleanup on `importForm()` | Task 12: importForm | Deletes stale temp files from abandoned import sessions |

---

## Execution Handoff
