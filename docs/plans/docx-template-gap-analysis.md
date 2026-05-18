# DOCX Result Sheet Template — Gap Analysis & Robustness Audit

## 1. Template Field Mapping (ISPSC ICAT Result Sheet)

Mapping every field from the DOCX template against our data model:

| # | Template Field | DB Source | Current Placeholder | Status |
|---|---|---|---|---|
| 1 | **ICAT Application No.** | `applications.reference_number` | `{{applicant_reference}}` | ✅ Exists |
| 2 | **Family Name** | `applications.last_name` | ❌ None | 🔴 **MISSING** |
| 3 | **First Name** | `applications.first_name` | ❌ None | 🔴 **MISSING** |
| 4 | **Middle Name** | `applications.middle_name` | ❌ None | 🔴 **MISSING** |
| 5 | **Sex** | `applications.sex` | ❌ None | 🔴 **MISSING** |
| 6 | **GWA** | `applications.gwa` | ❌ None | 🔴 **MISSING** |
| 7 | **Course Applied** | `applications.course_preference_1` → `courses.name` | ❌ None | 🔴 **MISSING** |
| 8 | **Strand/Prev. Course** | `applications.last_school_enrolled` (partial) | ❌ None | 🟡 **PARTIAL** — field exists but is "last school enrolled", not strand |
| 9 | **Date of Examination** | `exam_sessions.date` | `{{exam_date}}` | ✅ Exists |
| 10 | **Time of Examination** | `exam_sessions.start_time` | ❌ None | 🔴 **MISSING** |
| 11 | **Room Name** | `exam_sessions.room` → `rooms.name` | `{{room_name}}` | ✅ Exists |
| 12 | **General Ability** score | `applicant_scores` per aptitude area | `{{general_ability}}` (dynamic) | ✅ Per-domain auto-generated |
| 13 | **Verbal Aptitude** score | same | `{{verbal_aptitude}}` (dynamic) | ✅ Per-domain auto-generated |
| 14 | **Numerical Aptitude** score | same | `{{numerical_aptitude}}` (dynamic) | ✅ Per-domain auto-generated |
| 15 | **Spatial Aptitude** score | same | `{{spatial_aptitude}}` (dynamic) | ✅ Per-domain auto-generated |
| 16 | **Perceptual Aptitude** score | same | `{{perceptual_aptitude}}` (dynamic) | ✅ Per-domain auto-generated |
| 17 | **Manual Dexterity** score | same | `{{manual_dexterity}}` (dynamic) | ✅ Per-domain auto-generated |
| 18 | **Percentile Rank** per area | `applicant_scores.normalized_score` | `{{domain_slug}}` gives pct | ✅ Exists (as percentage) |
| 19 | **Descriptive Rating** per area | ❌ Not computed | ❌ None | 🔴 **MISSING** — needs percentile→rating mapping |
| 20 | **Recommended Programs** | `consultation_summaries.recommended_course_id` → `courses.name` | ❌ None | 🔴 **MISSING** |
| 21 | **Counselor Comments** | `consultation_summaries.counselor_comments` | ❌ None | 🔴 **MISSING** |
| 22 | **Guidance Counselor Name** | `consultation_summaries.counselor_id` → `users.name` | ❌ None | 🔴 **MISSING** |
| 23 | **Overall Percentile** | Computed average of all areas | `{{overall_pct}}` | ✅ Exists |
| 24 | **Applicant Full Name** | Concatenated | `{{applicant_name}}` | ✅ Exists (but DOCX needs separate fields) |

### Summary: 12 missing placeholders, 1 partial field

---

## 2. Current Placeholder System

### What exists today (in `ResultSheetTemplateService`):

```
CORE:        applicant_name, applicant_reference, exam_date, room_name, scores_rows, overall_pct
DUAL SLOT:   applicant_name_2, applicant_reference_2, scores_rows_2, overall_pct_2
PER-DOMAIN:  {{domain_slug}} (pct), {{domain_slug_raw}} (raw/max) — auto-generated from AptitudeArea table
```

### What the ISPSC DOCX template NEEDS:

```
IDENTITY:    family_name, first_name, middle_name, suffix, sex, gwa
ACADEMIC:    course_applied, strand
EXAM:        exam_date, exam_time, room_name
PER-DOMAIN:  {{domain_slug}} (percentile), {{domain_slug_rating}} (descriptive rating)
COUNSELOR:   recommended_course, counselor_comments, counselor_name
COMPUTED:    overall_pct
```

---

## 3. DOCX Rendering Pipeline — Accuracy Audit

### Current flow:
```
DOCX template file
  → PhpWord TemplateProcessor (replace {{placeholders}})
  → Save temp .docx
  → PhpWord IOFactory::load()
  → PhpWord HTML Writer
  → HTML string for preview/print
  → Cleanup temp files
```

### Accuracy Concerns:

| Issue | Severity | Detail |
|---|---|---|
| **PhpWord HTML Writer fidelity** | 🔴 High | Complex DOCX layouts (nested tables, borders, logos, watermarks) render poorly in PhpWord's HTML writer. The ISPSC template has headers, table borders, logos — these WILL break. |
| **No error handling on render** | 🔴 High | `renderDocxFromFullPath()` has no try-catch around `TemplateProcessor` or `IOFactory::load()`. A corrupt DOCX crashes the entire request. |
| **No DOCX-native output** | 🟡 Medium | We convert DOCX→HTML for everything. For print accuracy, we should offer DOCX download (keep original formatting) or DOCX→PDF via LibreOffice. |
| **Temp file cleanup fragility** | 🟡 Medium | `@unlink` in `finally` block — if `$tempHtml` is never set (exception before line 52), cleanup skips silently. |
| **No placeholder escape** | 🟡 Medium | If applicant data contains `{{` or `}}`, it could be interpreted as a placeholder. Need sanitization. |
| **No render logging** | 🟡 Medium | No audit trail of who generated what result sheet. Critical for a production exam system. |
| **No caching** | 🟢 Low | Re-renders on every request. Not critical for small batches but matters for bulk print. |

---

## 4. The "Descriptive Rating" Problem

The ISPSC template has a **Descriptive Rating** column (e.g., "Above Average", "Average", "Below Average"). This doesn't exist in our data model. We need a configurable mapping:

```
Percentile Range → Rating Label
90-100           → Outstanding
75-89            → Above Average
50-74            → Average
25-49            → Below Average
0-24             → Needs Improvement
```

> [!IMPORTANT]
> This mapping should be **configurable per deployment** (stored in settings or on the AptitudeArea/template), not hardcoded. Different schools may use different scales.

---

## 5. Recommended Implementation Plan

### Phase A: Expand Placeholders (Data Availability)

All the data exists in the database. We just need to surface it as placeholders.

**In `buildApplicantDataArray()`** — add these fields:

```php
return [
    'id'                  => $applicant->id,
    'name'                => $name,                                    // existing
    'family_name'         => $applicant->application?->last_name ?? '—',      // NEW
    'first_name'          => $applicant->application?->first_name ?? '—',     // NEW
    'middle_name'         => $applicant->application?->middle_name ?? '—',    // NEW
    'suffix'              => $applicant->application?->suffix ?? '',           // NEW
    'sex'                 => $applicant->application?->sex ?? '—',            // NEW
    'gwa'                 => $applicant->application?->gwa ?? '—',            // NEW
    'course_applied'      => $applicant->application?->coursePreference1?->name ?? '—', // NEW
    'strand'              => $applicant->application?->last_school_enrolled ?? '—',     // NEW
    'reference'           => $applicant->application?->reference_number ?? '—',
    'exam_date'           => $session?->examSession?->date?->format('F j, Y') ?? '—',
    'exam_time'           => $session?->examSession?->start_time?->format('g:i A') ?? '—', // NEW
    'room_name'           => $session?->examSession?->room?->name ?? '—',
    'scores'              => $scores,
    'overall_pct'         => $overallPct,
    'recommended_course'  => $applicant->consultationSummary?->recommendedCourse?->name ?? '—', // NEW
    'counselor_comments'  => $applicant->consultationSummary?->counselor_comments ?? '—',       // NEW
    'counselor_name'      => $applicant->consultationSummary?->counselor?->name ?? '—',         // NEW
];
```

**In `buildReplacements()`** — wire them into the replacement map:

```php
$replacements["family_name{$suffix}"]         = $data['family_name'] ?? '—';
$replacements["first_name{$suffix}"]          = $data['first_name'] ?? '—';
$replacements["middle_name{$suffix}"]         = $data['middle_name'] ?? '—';
$replacements["suffix{$suffix}"]              = $data['suffix'] ?? '';
$replacements["sex{$suffix}"]                 = $data['sex'] ?? '—';
$replacements["gwa{$suffix}"]                 = $data['gwa'] ?? '—';
$replacements["course_applied{$suffix}"]      = $data['course_applied'] ?? '—';
$replacements["strand{$suffix}"]              = $data['strand'] ?? '—';
$replacements["exam_time{$suffix}"]           = $data['exam_time'] ?? '—';
$replacements["recommended_course{$suffix}"]  = $data['recommended_course'] ?? '—';
$replacements["counselor_comments{$suffix}"]  = $data['counselor_comments'] ?? '—';
$replacements["counselor_name{$suffix}"]      = $data['counselor_name'] ?? '—';
```

### Phase B: Descriptive Rating Per Domain

**In `addPerDomainReplacements()`** — add a rating derivation:

```php
$rating = $this->percentileToRating((int) $pct);
$replacements[$slug.'_rating'.$suffix] = $rating;
```

New helper:
```php
protected function percentileToRating(int $pct): string
{
    // TODO: Make configurable via settings table
    return match (true) {
        $pct >= 90 => 'Outstanding',
        $pct >= 75 => 'Above Average',
        $pct >= 50 => 'Average',
        $pct >= 25 => 'Below Average',
        default    => 'Needs Improvement',
    };
}
```

### Phase C: DOCX Rendering Robustness

1. **Wrap in try-catch** with proper error logging
2. **Add DOCX download endpoint** — skip HTML conversion, serve the filled `.docx` directly for print-accurate output
3. **Sanitize replacement values** — strip `{{` / `}}` from data
4. **Add render audit logging** — who generated, when, which template, which applicants
5. **Improve temp file management** — use `finally` with guaranteed cleanup

### Phase D: Validation & UI Updates

1. **Update `PLACEHOLDERS` constant** with all new placeholder names
2. **Update `buildAllKnownPlaceholders()`** to include new fields
3. **Update the template editor UI** to show the full placeholder reference
4. **Add a "strand" field** to the Application model (separate from `last_school_enrolled`) if ISPSC needs a distinct SHS strand field

---

## 6. The "Strand" Field Question

> [!WARNING]
> The ISPSC template has "Strand/Prev. Course" which is the **SHS strand** (e.g., STEM, ABM, HUMSS). Our `last_school_enrolled` field captures the school name, NOT the strand. These are different data points.

**Options:**
- **A)** Add a `strand` column to `applications` table (migration + seeder + import)
- **B)** Repurpose `applicant_type` or `last_school_enrolled` (risky — breaks existing data)
- **C)** Use `last_school_enrolled` as-is and document the mismatch

**Recommendation:** Option A — add a proper `strand` field. It's a real data point for college admissions.

---

## 7. DOCX vs HTML Mode Feature Parity

| Feature | HTML Mode | DOCX Mode | Gap |
|---|---|---|---|
| All 24 placeholders | 🟡 12 of 24 | 🟡 12 of 24 | Same — both need Phase A |
| Per-domain scores | ✅ | ✅ | Parity |
| Descriptive ratings | ❌ | ❌ | Both need Phase B |
| Preview rendering | ✅ Good | 🟡 Lossy (PhpWord HTML writer) | DOCX preview will never match original |
| Print accuracy | 🟡 CSS-dependent | 🔴 HTML conversion loses formatting | **DOCX download needed** |
| Crosswise / half-page | ✅ | ✅ | Parity |
| Template validation | ✅ | ✅ | Parity |
| Error handling | 🟡 | 🔴 No try-catch | Phase C |
| Audit logging | ❌ | ❌ | Both need Phase C |

---

## 8. Recommended Approach for Print Accuracy

For the ISPSC deployment, the result sheet needs to look **exactly** like their official document (with logos, borders, the specific table layout). 

> [!TIP]
> **Best strategy for DOCX mode print accuracy:**
> 1. Keep DOCX→HTML for **on-screen preview** (approximate is fine)
> 2. Add a **"Download as DOCX"** button that fills placeholders and serves the `.docx` file directly
> 3. Staff opens in Word/LibreOffice and prints — **perfect fidelity guaranteed**
> 4. Optionally: add server-side LibreOffice conversion (`soffice --headless --convert-to pdf`) for a **"Download as PDF"** option

This sidesteps PhpWord's HTML writer limitations entirely for the actual printed output.
