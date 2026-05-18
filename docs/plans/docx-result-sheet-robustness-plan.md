# Implementation Plan: DOCX Result Sheet Robustness & Institution Config

> Production-ready result sheet generation for ISPSC beta deployment.

---

## Architecture Decision

**Institution data** uses a hybrid approach:
- `.env` provides **deployment defaults** (set once during install)
- `config/institution.php` maps env vars to Laravel config
- `SystemSetting` table stores **admin overrides** (editable via UI)
- Resolution: `SystemSetting::get('institution.name') ?? config('institution.name')`

This means: deployer sets `.env` during install, admin can tweak in UI without touching files.

---

## Task Breakdown

### Task 1: Institution Config File
**File:** `config/institution.php`

> [!IMPORTANT]
> **Key Personnel ≠ Admin Users.** These are static name/title strings for display on result sheets, admission slips, the homepage, and other public-facing documents. They exist independently from the `users` table. A guidance counselor listed here may or may not have an admin account — it doesn't matter. This is purely template/display data.

```php
return [
    // ── Institution Profile ──
    'name'            => env('INSTITUTION_NAME', 'My Institution'),
    'campus'          => env('INSTITUTION_CAMPUS', ''),
    'address'         => env('INSTITUTION_ADDRESS', ''),
    'contact_number'  => env('INSTITUTION_CONTACT_NUMBER', ''),
    'email'           => env('INSTITUTION_EMAIL', ''),
    'website'         => env('INSTITUTION_WEBSITE', ''),

    // ── Exam Branding ──
    'exam_name'       => env('INSTITUTION_EXAM_NAME', 'College Admission Test'),
    'exam_acronym'    => env('INSTITUTION_EXAM_ACRONYM', 'CAT'),

    // ── Key Personnel (static display data for templates & documents) ──
    // These are NOT admin/user accounts — just names that appear on printed documents.
    'personnel' => [
        'guidance_counselor' => [
            'name'  => env('INSTITUTION_GUIDANCE_COUNSELOR', ''),
            'title' => env('INSTITUTION_GUIDANCE_COUNSELOR_TITLE', 'Guidance Counselor'),
            'credentials' => env('INSTITUTION_GUIDANCE_COUNSELOR_CREDENTIALS', ''),
        ],
        'registrar' => [
            'name'  => env('INSTITUTION_REGISTRAR', ''),
            'title' => env('INSTITUTION_REGISTRAR_TITLE', 'Registrar'),
            'credentials' => env('INSTITUTION_REGISTRAR_CREDENTIALS', ''),
        ],
        'college_president' => [
            'name'  => env('INSTITUTION_COLLEGE_PRESIDENT', ''),
            'title' => env('INSTITUTION_COLLEGE_PRESIDENT_TITLE', 'College President'),
            'credentials' => env('INSTITUTION_COLLEGE_PRESIDENT_CREDENTIALS', ''),
        ],
        'campus_director' => [
            'name'  => env('INSTITUTION_CAMPUS_DIRECTOR', ''),
            'title' => env('INSTITUTION_CAMPUS_DIRECTOR_TITLE', 'Campus Director'),
            'credentials' => env('INSTITUTION_CAMPUS_DIRECTOR_CREDENTIALS', ''),
        ],
        'vp_academic_affairs' => [
            'name'  => env('INSTITUTION_VP_ACADEMIC_AFFAIRS', ''),
            'title' => env('INSTITUTION_VP_ACADEMIC_AFFAIRS_TITLE', 'VP for Academic Affairs'),
            'credentials' => env('INSTITUTION_VP_ACADEMIC_AFFAIRS_CREDENTIALS', ''),
        ],
        'dean' => [
            'name'  => env('INSTITUTION_DEAN', ''),
            'title' => env('INSTITUTION_DEAN_TITLE', 'Dean'),
            'credentials' => env('INSTITUTION_DEAN_CREDENTIALS', ''),
        ],
        'testing_coordinator' => [
            'name'  => env('INSTITUTION_TESTING_COORDINATOR', ''),
            'title' => env('INSTITUTION_TESTING_COORDINATOR_TITLE', 'Testing Coordinator'),
            'credentials' => env('INSTITUTION_TESTING_COORDINATOR_CREDENTIALS', ''),
        ],
    ],
];
```

**`.env.example` additions:**
```env
# ── Institution Configuration ──────────────────────────────
# These values appear on result sheets, admission slips, homepage, and reports.
# They can be overridden from Setup > Institution in the admin UI.
# NOTE: Academic year is managed separately via Admin > Academic Years CRUD.

# Profile
INSTITUTION_NAME="ISPSC"
INSTITUTION_CAMPUS="Tagudin Campus"
INSTITUTION_ADDRESS=""
INSTITUTION_CONTACT_NUMBER=""
INSTITUTION_EMAIL=""
INSTITUTION_WEBSITE=""

# Exam branding
INSTITUTION_EXAM_NAME="ISPSC College Admission Test"
INSTITUTION_EXAM_ACRONYM="ICAT"

# Key Personnel — static names/titles for documents (NOT admin user accounts)
INSTITUTION_GUIDANCE_COUNSELOR="RAVEENA GALOPE, RGC, RPm"
INSTITUTION_GUIDANCE_COUNSELOR_TITLE="Guidance Counselor"
INSTITUTION_GUIDANCE_COUNSELOR_CREDENTIALS="RGC, RPm"
INSTITUTION_REGISTRAR=""
INSTITUTION_REGISTRAR_TITLE="Registrar"
INSTITUTION_COLLEGE_PRESIDENT=""
INSTITUTION_COLLEGE_PRESIDENT_TITLE="College President"
INSTITUTION_CAMPUS_DIRECTOR=""
INSTITUTION_CAMPUS_DIRECTOR_TITLE="Campus Director"
INSTITUTION_VP_ACADEMIC_AFFAIRS=""
INSTITUTION_VP_ACADEMIC_AFFAIRS_TITLE="VP for Academic Affairs"
INSTITUTION_DEAN=""
INSTITUTION_DEAN_TITLE="Dean"
INSTITUTION_TESTING_COORDINATOR=""
INSTITUTION_TESTING_COORDINATOR_TITLE="Testing Coordinator"
```

**Estimated time:** 20 min

---

### Task 2: Institution Settings Page (Setup Hub)
**Files:**
- `app/Http/Controllers/Admin/InstitutionController.php`
- `resources/js/Pages/Admin/Institution/Index.svelte`
- Route in `web.php` under Setup group

**Behavior:**
1. Controller reads all `config('institution')` values as the **defaults**
2. For each key, checks `SystemSetting::get("institution.{$key}")` for admin **override**
3. Merges: override wins, env/config is fallback
4. Shows a two-card layout: Institution Profile + Key Personnel
5. On save, writes only changed values to `SystemSetting` table
6. "Reset to .env defaults" button deletes overrides (reverts to config)
7. Each personnel entry shows: name, title, credentials — all optional

**Resolution helper in SystemSetting:**
```php
// Add to SystemSetting model:
public static function institution(string $key, mixed $default = null): mixed
{
    return self::get("institution.{$key}") ?? config("institution.{$key}", $default);
}
```

**UI concept:**
```
┌─────────────────────────────────────────────────────┐
│ 🏛️ Institution Profile                              │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─│
│ Institution Name    [ISPSC                         ] │
│ Campus              [Tagudin Campus                ] │
│ Address             [Tagudin, Ilocos Sur           ] │
│ Contact Number      [(077) 123-4567                ] │
│ Email               [admissions@ispsc.edu.ph       ] │
│ Website             [https://ispsc.edu.ph          ] │
│ Exam Name           [ISPSC College Admission Test  ] │
│ Exam Acronym        [ICAT                          ] │
│ ℹ️ Academic year is managed via Academic Years CRUD  │
├─────────────────────────────────────────────────────┤
│ 👤 Key Personnel                                     │
│ These names appear on result sheets, admission       │
│ slips, and public-facing pages — NOT admin accounts. │
│ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─│
│                                                      │
│ ┌── Guidance Counselor ──────────────────┐           │
│ │ Name         [RAVEENA GALOPE          ] │           │
│ │ Title        [Guidance Counselor      ] │           │
│ │ Credentials  [RGC, RPm               ] │           │
│ └────────────────────────────────────────┘           │
│                                                      │
│ ┌── Registrar ───────────────────────────┐           │
│ │ Name         [                        ] │           │
│ │ Title        [Registrar               ] │           │
│ │ Credentials  [                        ] │           │
│ └────────────────────────────────────────┘           │
│                                                      │
│ ┌── College President ───────────────────┐           │
│ │ Name         [                        ] │           │
│ │ ...                                     │           │
│ └────────────────────────────────────────┘           │
│                                                      │
│ (+ Campus Director, VP Academic Affairs,             │
│    Dean, Testing Coordinator)                        │
│                                                      │
├─────────────────────────────────────────────────────┤
│              [Reset to Defaults]          [Save]     │
└─────────────────────────────────────────────────────┘
```

**Key design notes:**
- Personnel cards are collapsible — only filled ones are expanded by default
- Empty personnel slots show as dimmed/collapsed ("Not configured")
- Each field shows a subtle `.env` badge if using the env default vs a ✏️ badge if admin-overridden
- These are strictly display data — no FK to users table, no auth implications

**Route:** `GET/PUT admin/setup/institution` → `admin.setup.institution.index` / `.update`

**Estimated time:** 1.5 hours

---

### Task 3: Rating Scale CRUD
**Files:**
- Migration: `create_rating_scales_table`
- `app/Models/RatingScale.php`
- `app/Http/Controllers/Admin/RatingScaleController.php`
- `resources/js/Pages/Admin/RatingScales/Index.svelte`
- `resources/js/Pages/Admin/RatingScales/Create.svelte`
- `resources/js/Pages/Admin/RatingScales/Edit.svelte`
- Add `rating_scale_id` to `result_sheet_templates` table

**Migration:**
```php
Schema::create('rating_scales', function (Blueprint $table) {
    $table->id();
    $table->string('name');           // "ISPSC Standard", "DepEd K-12"
    $table->json('ranges');           // [{"min":90,"max":100,"label":"Outstanding"}, ...]
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});

// Add FK to result_sheet_templates
Schema::table('result_sheet_templates', function (Blueprint $table) {
    $table->foreignId('rating_scale_id')->nullable()->constrained('rating_scales')->nullOnDelete();
});
```

**`ranges` JSON structure:**
```json
[
    {"min": 90, "max": 100, "label": "Outstanding"},
    {"min": 75, "max": 89,  "label": "Above Average"},
    {"min": 50, "max": 74,  "label": "Average"},
    {"min": 25, "max": 49,  "label": "Below Average"},
    {"min": 0,  "max": 24,  "label": "Needs Improvement"}
]
```

**Model helper:**
```php
public function ratingFor(int $percentile): string
{
    foreach ($this->ranges as $range) {
        if ($percentile >= $range['min'] && $percentile <= $range['max']) {
            return $range['label'];
        }
    }
    return '—';
}
```

**Seeder** — ship with ISPSC defaults pre-loaded.

**UI:** Same card/table pattern as Aptitude Areas. The `ranges` editor is a small repeater (min, max, label) with add/remove rows.

**Route:** Under Setup group, `admin/setup/rating-scales`

**Estimated time:** 2 hours

---

### Task 4: Expand Placeholder System (12 new fields)
**File:** `app/Services/ResultSheetTemplateService.php`

#### 4a. Update `buildApplicantDataArray()` 

Add all missing fields. Requires eager-loading `consultationSummary.recommendedCourse` and `consultationSummary.counselor`:

```php
protected function buildApplicantDataArray(Applicant $applicant, ?GradingSession $session, array $scores): array
{
    $app = $applicant->application;
    $summary = $applicant->consultationSummary;
    
    $overallPct = count($scores) > 0
        ? (int) round(collect($scores)->avg('pct'))
        : 0;

    return [
        'id'                    => $applicant->id,
        // Identity — split fields for DOCX
        'name'                  => trim(implode(' ', array_filter([
            $app?->first_name, $app?->middle_name, $app?->last_name, $app?->suffix,
        ]))) ?: '—',
        'family_name'           => $app?->last_name ?? '—',
        'first_name'            => $app?->first_name ?? '—',
        'middle_name'           => $app?->middle_name ?? '—',
        'suffix'                => $app?->suffix ?? '',
        'sex'                   => $app?->sex ?? '—',
        'gwa'                   => $app?->gwa ?? '—',
        // Academic
        'course_applied'        => $app?->coursePreference1?->name ?? '—',
        'strand'                => $app?->strand ?? $app?->last_school_enrolled ?? '—',
        'applicant_type'        => $app?->applicant_type ?? '—',
        // Exam context
        'reference'             => $app?->reference_number ?? '—',
        'exam_date'             => $session?->examSession?->date?->format('F j, Y') ?? '—',
        'exam_time'             => $session?->examSession?->start_time
                                    ? \Carbon\Carbon::parse($session->examSession->start_time)->format('g:i A')
                                    : '—',
        'room_name'             => $session?->examSession?->room?->name ?? '—',
        // Scores
        'scores'                => $scores,
        'overall_pct'           => $overallPct,
        // Counselor / release
        'recommended_course'    => $summary?->recommendedCourse?->name ?? '—',
        'counselor_comments'    => $summary?->counselor_comments ?? '—',
        'counselor_name'        => $summary?->counselor?->name ?? '—',
    ];
}
```

#### 4b. Update `buildReplacements()` 

Wire all new keys into the replacement map (same pattern as existing fields):

```php
// Inside the foreach ([1 => 0, 2 => 1]) loop:
$newFields = [
    'family_name', 'first_name', 'middle_name', 'suffix', 'sex', 'gwa',
    'course_applied', 'strand', 'applicant_type',
    'exam_time',
    'recommended_course', 'counselor_comments', 'counselor_name',
];
foreach ($newFields as $field) {
    $replacements["{$field}{$suffix}"] = $data[$field] ?? '—';
}
```

#### 4c. Add institution placeholders

After the applicant loop, inject institution-level data:

```php
// Institution profile (not per-applicant)
$replacements['institution_name']      = SystemSetting::institution('name', '—');
$replacements['institution_campus']    = SystemSetting::institution('campus', '—');
$replacements['institution_address']   = SystemSetting::institution('address', '—');
$replacements['institution_contact']   = SystemSetting::institution('contact_number', '—');
$replacements['institution_email']     = SystemSetting::institution('email', '—');
$replacements['institution_website']   = SystemSetting::institution('website', '—');
$replacements['institution_exam_name'] = SystemSetting::institution('exam_name', '—');
$replacements['institution_exam_acronym'] = SystemSetting::institution('exam_acronym', '—');

// Key personnel — all personnel roles auto-mapped as placeholders
// e.g. {{personnel_guidance_counselor_name}}, {{personnel_registrar_title}}
$personnel = config('institution.personnel', []);
foreach ($personnel as $role => $defaults) {
    foreach (['name', 'title', 'credentials'] as $field) {
        $key = "personnel.{$role}.{$field}";
        $val = SystemSetting::institution($key) ?? ($defaults[$field] ?? '');
        $replacements["personnel_{$role}_{$field}"] = $val ?: '—';
    }
}
```

This auto-generates placeholders for ALL personnel roles, so adding a new role to `config/institution.php` immediately makes it available in DOCX templates without code changes.

#### 4d. Add per-domain descriptive rating

In `addPerDomainReplacements()`, after computing `$pct`:

```php
// Look up rating from template's scale (or default)
$rating = $ratingScale?->ratingFor((int) $pct) ?? $this->defaultRating((int) $pct);
$replacements[$slug.'_rating'.$suffix] = $rating;
```

#### 4e. Update `PLACEHOLDERS` constant and `buildAllKnownPlaceholders()`

Add all new placeholder names to the constant and the validation builder.

#### 4f. Update `sampleApplicantData()` 

Add sample values for all new fields so DOCX preview works with realistic data.

#### 4g. Update eager loading in `fetchApplicantsForSession()` and `fetchApplicantsAgnostic()`

Add: `consultationSummary.recommendedCourse`, `consultationSummary.counselor`, `application.coursePreference1`

**Estimated time:** 2 hours

---

### Task 5: Strand Field Migration
**File:** New migration

```php
Schema::table('applications', function (Blueprint $table) {
    $table->string('strand')->nullable()->after('last_school_enrolled');
});
```

Update:
- `Application` model fillable
- `StoreApplicationRequest` / `UpdateApplicationRequest` validation
- Application import (add `strand` column mapping)
- Application create/edit forms
- Application detail view

**Estimated time:** 45 min

---

### Task 6: DOCX Rendering Robustness
**File:** `app/Services/ResultSheetDocxService.php`

#### 6a. Error handling
```php
public function renderDocxFromFullPath(string $fullPath, array $replacements): string
{
    if (! is_file($fullPath)) {
        return '<p class="text-destructive">DOCX file not found.</p>';
    }

    try {
        // ... existing logic ...
    } catch (\PhpOffice\PhpWord\Exception\Exception $e) {
        Log::error('DOCX render failed', ['path' => $fullPath, 'error' => $e->getMessage()]);
        return '<p class="text-destructive">Failed to process DOCX template: ' 
               . htmlspecialchars($e->getMessage()) . '</p>';
    } catch (\Throwable $e) {
        Log::error('DOCX render unexpected error', ['path' => $fullPath, 'error' => $e->getMessage()]);
        return '<p class="text-destructive">Unexpected error rendering template.</p>';
    }
}
```

#### 6b. Sanitize replacement values
```php
// Before applying replacements:
$replacements = array_map(function ($value) {
    $v = (string) $value;
    return str_replace(['{{', '}}'], ['{ {', '} }'], $v);
}, $replacements);
```

#### 6c. DOCX download endpoint (print-accurate output)

New method in `ReleasePrintController`:
```php
public function downloadDocx(GradingSession $gradingSession, Applicant $applicant)
{
    // Fill template, return .docx file directly (no HTML conversion)
    // This gives perfect print fidelity
}
```

New route:
```php
Route::get('{grading_session}/applicants/{applicant}/docx', 
    [ReleasePrintController::class, 'downloadDocx'])->name('result-sheet-docx');
```

#### 6d. Audit logging on render
```php
app(AuditService::class)->log('result_sheet.rendered', ResultSheetTemplate::class, $template->id, [], [
    'applicant_ids' => $applicantIds,
    'mode' => $template->mode,
]);
```

**Estimated time:** 1.5 hours

---

### Task 7: Setup Hub Integration
**File:** `app/Http/Controllers/Admin/SetupController.php`

Add institution + rating scale health checks:

```php
protected function checkInstitution(): array
{
    $required = ['name', 'guidance_counselor', 'exam_name'];
    $missing = [];
    foreach ($required as $key) {
        $val = SystemSetting::get("institution.{$key}") ?? config("institution.{$key}");
        if (empty($val)) $missing[] = $key;
    }
    
    return [
        'label' => 'Institution',
        'icon' => 'building',
        'items' => [...],
        'severity' => count($missing) > 0 ? 'warning' : 'ok',
    ];
}

protected function checkRatingScales(): array
{
    $count = RatingScale::count();
    $hasDefault = RatingScale::where('is_default', true)->exists();
    
    return [
        'label' => 'Rating Scales',
        'items' => [...],
        'severity' => $count === 0 ? 'warning' : 'ok',
    ];
}
```

**Estimated time:** 30 min

---

### Task 8: Tests
- `InstitutionControllerTest` — CRUD, .env fallback, override precedence
- `RatingScaleControllerTest` — CRUD, default assignment, validation
- `ResultSheetPlaceholderTest` — verify all 24+ placeholders fill correctly
- `DocxRenderTest` — error handling, sanitization, download endpoint
- `StrandMigrationTest` — import with strand field

**Estimated time:** 2 hours

---

## Execution Order (dependency-aware)

```
Task 1 → Task 2 → Task 7      (Institution config → page → health check)
Task 3                          (Rating Scales — independent)
Task 5                          (Strand migration — independent)
Task 1,3,5 → Task 4            (Placeholders need config, scales, strand)
Task 4 → Task 6                (DOCX robustness needs expanded placeholders)
Task * → Task 8                (Tests after implementation)
```

**Parallelizable:** Tasks 1, 3, and 5 have no dependencies on each other.

---

## Total Estimate: ~10 hours

| Task | Effort |
|------|--------|
| 1. Institution config file | 15 min |
| 2. Institution settings page | 1.5 hr |
| 3. Rating Scale CRUD | 2 hr |
| 4. Expand placeholders | 2 hr |
| 5. Strand migration | 45 min |
| 6. DOCX robustness | 1.5 hr |
| 7. Setup hub integration | 30 min |
| 8. Tests | 2 hr |

---

## Full Placeholder Reference (after implementation)

| Placeholder | Source | Example |
|---|---|---|
| `{{applicant_name}}` | Concatenated full name | Juan M. Dela Cruz |
| `{{family_name}}` | `applications.last_name` | Dela Cruz |
| `{{first_name}}` | `applications.first_name` | Juan |
| `{{middle_name}}` | `applications.middle_name` | M. |
| `{{suffix}}` | `applications.suffix` | Jr. |
| `{{sex}}` | `applications.sex` | Male |
| `{{gwa}}` | `applications.gwa` | 1.50 |
| `{{course_applied}}` | Course preference 1 name | BS Information Technology |
| `{{strand}}` | `applications.strand` | STEM |
| `{{applicant_type}}` | `applications.applicant_type` | Freshman |
| `{{applicant_reference}}` | `applications.reference_number` | ICAT-2026-00042 |
| `{{exam_date}}` | Exam session date | May 15, 2026 |
| `{{exam_time}}` | Exam session start time | 8:00 AM |
| `{{room_name}}` | Room name | Conference Hall A |
| `{{general_ability}}` | Percentile (dynamic per area) | 85 |
| `{{general_ability_raw}}` | Raw/Max score | 42 / 50 |
| `{{general_ability_rating}}` | Descriptive rating | Above Average |
| `{{overall_pct}}` | Average percentile | 82 |
| `{{recommended_course}}` | Counselor recommendation | BSIT |
| `{{counselor_comments}}` | Counselor remarks | Good aptitude scores... |
| `{{counselor_name}}` | Counselor user name | Maria Santos |
| `{{institution_name}}` | Institution profile | ISPSC |
| `{{institution_campus}}` | Institution profile | Tagudin Campus |
| `{{institution_address}}` | Institution profile | Tagudin, Ilocos Sur |
| `{{institution_contact}}` | Institution profile | (077) 123-4567 |
| `{{institution_email}}` | Institution profile | admissions@ispsc.edu.ph |
| `{{institution_website}}` | Institution profile | https://ispsc.edu.ph |
| `{{institution_exam_name}}` | Institution profile | ISPSC College Admission Test |
| `{{institution_exam_acronym}}` | Institution profile | ICAT |
| `{{personnel_guidance_counselor_name}}` | Key personnel | RAVEENA GALOPE |
| `{{personnel_guidance_counselor_title}}` | Key personnel | Guidance Counselor |
| `{{personnel_guidance_counselor_credentials}}` | Key personnel | RGC, RPm |
| `{{personnel_registrar_name}}` | Key personnel | (configured value) |
| `{{personnel_registrar_title}}` | Key personnel | Registrar |
| `{{personnel_college_president_name}}` | Key personnel | (configured value) |
| `{{personnel_campus_director_name}}` | Key personnel | (configured value) |
| `{{personnel_vp_academic_affairs_name}}` | Key personnel | (configured value) |
| `{{personnel_dean_name}}` | Key personnel | (configured value) |
| `{{personnel_testing_coordinator_name}}` | Key personnel | (configured value) |
| `{{scores_rows}}` | HTML table rows (HTML mode) | `<tr>...</tr>` |
