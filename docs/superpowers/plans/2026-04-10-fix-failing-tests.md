# Fix Failing Tests Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Resolve all 36 failing tests across 6 root-cause groups, bringing the suite to 0 failures before phase 3A begins.

**Architecture:** Six independent batches ordered by risk (lowest first). Each batch targets a single root cause and commits independently. No batch touches another batch's files. All fixes are minimal — no logic changes beyond what the tests require.

**Tech Stack:** Laravel 12, PHP 8.2, PHPUnit 11, SQLite (in-memory for tests)

---

## Failure Map

| Batch | Tests Fixed | Root Cause |
|-------|------------|------------|
| A | 4 | `GradingSession` model missing `HasFactory` trait |
| B | 1 | `DashboardTest` asserts wrong Inertia prop names |
| C | 8 | `ConsultationScheduleControllerTest` — feature/routes removed |
| D | 9 | `admin.knowledge-documents.index` redirect has no route name |
| E | 6 | SQLite FK to dropped `exam_domains` table in `applicant_scores` |
| F | 2 | `UpdateSystemSettingsRequest` missing `ai_companion_persona` rule + no `strip_tags` |

---

## File Map

### Batch A — modified
- `app/Models/GradingSession.php`

### Batch B — modified
- `tests/Feature/DashboardTest.php`

### Batch C — deleted
- `tests/Feature/Consultation/ConsultationScheduleControllerTest.php`

### Batch D — modified
- `routes/web.php`

### Batch E — modified
- `database/migrations/2026_02_19_000004_create_applicant_scores_table.php`
- `database/migrations/2026_04_09_000002_rename_domain_id_in_applicant_scores.php`

### Batch F — modified
- `app/Http/Requests/UpdateSystemSettingsRequest.php`
- `app/Http/Controllers/Admin/SettingsController.php`

---

## Task 1: Batch A — GradingSession Missing HasFactory

**Root cause:** `GradingSession` model has `GradingSessionFactory.php` but doesn't use the `HasFactory` trait. Every test that calls `GradingSession::factory()` throws `BadMethodCallException: Call to undefined method App\Models\GradingSession::factory()`.

**Files:**
- Modify: `app/Models/GradingSession.php`

- [ ] **Step 1: Add HasFactory to GradingSession**

Open `app/Models/GradingSession.php`. It currently has these use statements:
```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Add the import and trait:
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

And inside the class body, add the trait (place it as the first line inside the class):
```php
class GradingSession extends Model
{
    use HasFactory;
    // ... rest of the class
```

- [ ] **Step 2: Run affected tests**

```bash
php artisan test --compact tests/Unit/Services/ScoreInputServiceTest.php
```

Expected: `Tests: 4 passed`

- [ ] **Step 3: Run pint**

```bash
vendor/bin/pint app/Models/GradingSession.php --format agent
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/GradingSession.php
git commit -m "fix: add HasFactory trait to GradingSession model"
```

---

## Task 2: Batch B — DashboardTest Wrong Prop Assertions

**Root cause:** `tests/Feature/DashboardTest.php` asserts `has('stats')` and `has('dashboard')` — props that were planned but never implemented. The actual controller returns `applicationStats`, `sessionStats`, and `gradingStats` (verified against `DashboardControllerTest` which passes).

**Files:**
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Update the test assertions**

Replace the entire `assertInertia` block in `test_dashboard_renders_and_includes_dashboard_payload_for_admin_roles`:

Current (wrong):
```php
$response->assertInertia(fn ($page) => $page
    ->component('Dashboard')
    ->has('stats')
    ->has('dashboard')
    ->has('dashboard.kpis')
    ->has('dashboard.breakdowns')
    ->has('dashboard.series')
    ->has('dashboard.queues')
    ->has('dashboard.health')
);
```

Replace with:
```php
$response->assertInertia(fn ($page) => $page
    ->component('Dashboard')
    ->has('applicationStats')
    ->has('sessionStats')
    ->has('gradingStats')
);
```

- [ ] **Step 2: Run the test**

```bash
php artisan test --compact tests/Feature/DashboardTest.php
```

Expected: `Tests: 1 passed`

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/DashboardTest.php
git commit -m "fix: update DashboardTest assertions to match actual controller props"
```

---

## Task 3: Batch C — Delete Removed-Feature Tests

**Root cause:** `ConsultationScheduleControllerTest` tests a `consultation.schedule.index` route that no longer exists (consultation/rules engine was removed in April 2026). These tests cannot pass without re-implementing the removed feature.

**Files:**
- Delete: `tests/Feature/Consultation/ConsultationScheduleControllerTest.php`

- [ ] **Step 1: Verify the route doesn't exist**

```bash
php artisan route:list --name=consultation.schedule 2>&1
```

Expected: `Your application doesn't have any routes matching the given criteria.`

- [ ] **Step 2: Delete the test file**

```bash
rm tests/Feature/Consultation/ConsultationScheduleControllerTest.php
```

- [ ] **Step 3: Run remaining Consultation tests to confirm no collateral**

```bash
php artisan test --compact tests/Feature/Consultation/
```

Expected: Only `Consultation\DashboardTest` runs (2 tests — these fail due to Batch E, not Batch C).

- [ ] **Step 4: Commit**

```bash
git add -A tests/Feature/Consultation/ConsultationScheduleControllerTest.php
git commit -m "chore: remove ConsultationScheduleControllerTest for deleted route"
```

---

## Task 4: Batch D — Name the Knowledge Documents Index Redirect

**Root cause:** `routes/web.php` has a redirect from the old knowledge-documents index to the new AI Companion hub, but that redirect has no route name. Tests call `route('admin.knowledge-documents.index')` which throws `RouteNotFoundException`.

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add name to the redirect route**

Find this line in `routes/web.php` (around line 92):
```php
Route::get('knowledge-documents', fn () => redirect()->route('admin.ai-companion.index'));
```

Replace with:
```php
Route::get('knowledge-documents', fn () => redirect()->route('admin.ai-companion.index'))->name('knowledge-documents.index');
```

- [ ] **Step 2: Verify the route is now registered**

```bash
php artisan route:list --name=knowledge-documents.index
```

Expected: One route listed: `GET admin/knowledge-documents` with name `admin.knowledge-documents.index`.

- [ ] **Step 3: Run affected tests**

```bash
php artisan test --compact tests/Feature/Admin/KnowledgeDocumentControllerTest.php tests/Feature/Admin/KnowledgeDocumentCsvImportTest.php
```

Expected: All 9 tests pass.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php
git commit -m "fix: name knowledge-documents.index redirect route for test compatibility"
```

---

## Task 5: Batch E — Fix exam_domains SQLite FK Breakage

**Root cause:** `create_applicant_scores_table.php` creates `applicant_scores` with `domain_id` referencing the `exam_domains` table. Later migrations rename the column to `aptitude_area_id` and drop `exam_domains`. In SQLite (used by tests), column renaming recreates the table — but preserves the original FK to `exam_domains` in the schema. After `drop_exam_domains_table` runs, the FK is broken. With `foreign_key_constraints = true` in `config/database.php`, any INSERT into `applicant_scores` fails with `no such table: main.exam_domains`.

**Fix strategy:** Update the **source** migration to reflect the final desired schema (`aptitude_area_id` → `aptitude_areas`). Guard the rename migration so it skips if the column was already named correctly (i.e., on fresh installs using the updated create migration).

**Files:**
- Modify: `database/migrations/2026_02_19_000004_create_applicant_scores_table.php`
- Modify: `database/migrations/2026_04_09_000002_rename_domain_id_in_applicant_scores.php`

- [ ] **Step 1: Update the create migration to use aptitude_area_id**

In `database/migrations/2026_02_19_000004_create_applicant_scores_table.php`, replace the entire `up()` method:

```php
public function up(): void
{
    Schema::create('applicant_scores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('grading_session_id')->constrained()->cascadeOnDelete();
        $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
        $table->foreignId('aptitude_area_id')->constrained('aptitude_areas')->cascadeOnDelete();
        $table->unsignedSmallInteger('raw_score');
        $table->unsignedSmallInteger('max_score');
        $table->decimal('normalized_score', 5, 2)->nullable();
        $table->foreignId('scored_by')->constrained('users')->cascadeOnDelete();
        $table->timestamp('scored_at');
        $table->timestamps();

        $table->unique(['grading_session_id', 'applicant_id', 'aptitude_area_id'], 'app_scores_gs_app_area_unique');
    });
}
```

Also update `down()` — it can stay as `Schema::dropIfExists('applicant_scores');` (no change needed).

- [ ] **Step 2: Guard the rename migration**

In `database/migrations/2026_04_09_000002_rename_domain_id_in_applicant_scores.php`, wrap the entire `up()` body in a guard that skips if the column was already named `aptitude_area_id` (meaning the create migration was already updated):

Replace the opening of the `up()` method:
```php
public function up(): void
{
    if (! Schema::hasColumn('applicant_scores', 'domain_id')) {
        // Column already renamed (fresh install using updated create migration).
        return;
    }
```

Close the guard before the final `}` of `up()`. The full `up()` method should look like:

```php
public function up(): void
{
    if (! Schema::hasColumn('applicant_scores', 'domain_id')) {
        // Column already renamed (fresh install uses updated create migration).
        return;
    }

    // Only run MySQL-specific FK detection when using MySQL.
    if (DB::getDriverName() === 'mysql') {
        // ... (existing MySQL FK detection code — leave unchanged)
    }

    Schema::table('applicant_scores', function (Blueprint $table) {
        $table->dropUnique('app_scores_gs_app_dom_unique');
        $table->renameColumn('domain_id', 'aptitude_area_id');
    });

    Schema::table('applicant_scores', function (Blueprint $table) {
        $table->foreign('grading_session_id')
            ->references('id')->on('grading_sessions')
            ->cascadeOnDelete();
        $table->foreign('aptitude_area_id')
            ->references('id')->on('aptitude_areas')
            ->cascadeOnDelete();
        $table->unique(
            ['grading_session_id', 'applicant_id', 'aptitude_area_id'],
            'app_scores_gs_app_area_unique'
        );
    });
}
```

- [ ] **Step 3: Run pint on both migrations**

```bash
vendor/bin/pint database/migrations/2026_02_19_000004_create_applicant_scores_table.php database/migrations/2026_04_09_000002_rename_domain_id_in_applicant_scores.php --format agent
```

- [ ] **Step 4: Run the affected tests**

```bash
php artisan test --compact tests/Feature/DefenseDemoSeederTest.php tests/Feature/Consultation/DashboardTest.php
```

Expected: `Tests: 6 passed`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_02_19_000004_create_applicant_scores_table.php
git add database/migrations/2026_04_09_000002_rename_domain_id_in_applicant_scores.php
git commit -m "fix: update applicant_scores create migration to use aptitude_area_id, guard rename migration for SQLite compat"
```

---

## Task 6: Batch F — Settings Persona Validation + HTML Sanitization

**Root cause:** `UpdateSystemSettingsRequest::rules()` does not include `ai_companion_persona`. Laravel's `validated()` strips any key not in the rules, so `$validated['ai_companion_persona']` is never present, and `SystemSetting::set('ai_companion_persona', ...)` is never called. Additionally, the test `test_persona_strips_html` expects HTML to be stripped before saving, but the controller saves the raw value.

**Files:**
- Modify: `app/Http/Requests/UpdateSystemSettingsRequest.php`
- Modify: `app/Http/Controllers/Admin/SettingsController.php`

- [ ] **Step 1: Add persona rule to UpdateSystemSettingsRequest**

In `app/Http/Requests/UpdateSystemSettingsRequest.php`, add the persona rule to `rules()`:

```php
public function rules(): array
{
    return [
        'ai_exam_companion_enabled' => ['sometimes', 'boolean'],
        'notify_on_publish'         => ['sometimes', 'boolean'],
        'release_mode'              => ['sometimes', 'in:online,f2f,both'],
        'ai_companion_persona'      => ['sometimes', 'nullable', 'string', 'max:5000'],
    ];
}
```

- [ ] **Step 2: Strip HTML tags before saving in SettingsController**

In `app/Http/Controllers/Admin/SettingsController.php`, find the persona save block (around line 44):

```php
if (array_key_exists('ai_companion_persona', $validated)) {
    SystemSetting::set('ai_companion_persona', $validated['ai_companion_persona'] ?? '');
}
```

Replace with:

```php
if (array_key_exists('ai_companion_persona', $validated)) {
    SystemSetting::set('ai_companion_persona', strip_tags($validated['ai_companion_persona'] ?? ''));
}
```

- [ ] **Step 3: Run pint**

```bash
vendor/bin/pint app/Http/Requests/UpdateSystemSettingsRequest.php app/Http/Controllers/Admin/SettingsController.php --format agent
```

- [ ] **Step 4: Run the affected tests**

```bash
php artisan test --compact tests/Feature/Admin/SettingsControllerTest.php
```

Expected: `Tests: 8 passed` (all pass, including the 2 previously failing).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/UpdateSystemSettingsRequest.php
git add app/Http/Controllers/Admin/SettingsController.php
git commit -m "fix: add ai_companion_persona validation rule and strip HTML tags before saving"
```

---

## Task 7: Final Verification

- [ ] **Step 1: Run the full test suite**

```bash
php artisan test --compact
```

Expected: `Tests: 0 failed, X skipped, Y passed`

- [ ] **Step 2: Confirm 36 fewer failures**

Compare against pre-fix baseline: `36 failed, 1 skipped, 120 passed`.
Post-fix expected: `0 failed, 1 skipped, 156 passed`.

- [ ] **Step 3: Commit phase 2 uncommitted changes if still pending**

Before declaring done, check `git status`. If phase 2 Svelte/AppServiceProvider changes are still uncommitted:

```bash
git add resources/js/Components/SessionRoster.svelte
git add resources/js/Pages/Admin/TestAdmin/Index.svelte
git add resources/js/Pages/Admin/TestScheduling/Monitoring.svelte
git add resources/js/Pages/Grading/ScoreInput.svelte
git add resources/js/Pages/Grading/Session.svelte
git add resources/js/Pages/Portal/Dashboard.svelte
git add app/Providers/AppServiceProvider.php
git add .env.example
git commit -m "feat: phase 2 UI/UX fixes — label renames, dashboard cleanup, env additions"
```
