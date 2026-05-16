# Applicant Pipeline Status — Design Spec

**Date:** 2026-05-13
**Status:** Draft

## Problem

Admins cannot see where applicants are in the exam pipeline from the Applications index or show pages. The current UI only shows application workflow status (pending/accepted/dismissed) — it doesn't reflect whether an applicant has been scheduled, attended, submitted, or graded.

## Pipeline Status Definitions

Each status represents the most advanced milestone the applicant has reached. The computed status picks the furthest stage achieved.

| Pipeline Status | Badge Color | Meaning | Condition |
|---|---|---|---|
| `pending` | yellow/warning | Application not yet reviewed | `application.status = 'pending'` |
| `accepted` | green/success | Accepted, no session assigned yet | `application.status = 'accepted'` AND no exam session |
| `draft_scheduled` | gray/muted | Assigned to a draft (unpublished) session | `application.status = 'accepted'` AND assigned session status = `draft` |
| `scheduled` | blue/outline | Assigned to a published/active session | `application.status = 'accepted'` AND assigned session status in (`published`, `in_progress`) |
| `attended` | teal/info | Marked present in the session | Pivot `attendance_status = 'present'` |
| `submitted` | indigo/info | Exam submitted in the session | Pivot `submission_status` indicates submitted |
| `graded` | emerald/success | Has a finalized score | Has ApplicantScore in a finalized GradingSession |
| `dismissed` | red/danger | Application dismissed | `application.status = 'dismissed'` |

### Edge Cases

- **Dismissed overrides everything** — if `application.status = 'dismissed'`, pipeline status is always `dismissed` regardless of exam data.
- **Applicant deleted or missing** — if an accepted application has no `Applicant` record yet, pipeline status is `accepted` (setup email may not have been processed).
- **Multiple exam sessions** — uses the most advanced session (the one furthest in the pipeline).

## Backend Changes

### 1. Application Model — `pipelineStatus()` accessor

New method on `Application` that computes the pipeline status string. Logic:

```
if status = 'pending' → 'pending'
if status = 'dismissed' → 'dismissed'
if status = 'accepted':
  load applicant → examSessions (most advanced)
  if no applicant or no exam session → 'accepted'
  session = examSessions->first()
  if session.status = 'draft' → 'draft_scheduled'
  if session.status in ('published', 'in_progress') → check pivot
    if pivot.attendance_status = 'present' → check submission
      if pivot.submission_status indicates submitted → check grading
        if has finalized ApplicantScore → 'graded'
        else → 'submitted'
      else → 'attended'
    else → 'scheduled'
  if session.status = 'completed' → check pivot (same logic as published)
  if session.status = 'cancelled' → 'accepted' (session cancelled, not scheduled)
```

### 2. Application Model — `pipelineDetails()` accessor

Returns a structured object with timestamps for each milestone reached:

```php
[
  'status' => 'submitted',
  'milestones' => [
    'accepted' => ['at' => '2026-04-01T10:00:00Z'],
    'scheduled' => ['at' => '2026-04-05T08:00:00Z', 'session_date' => '2026-04-27', 'session_label' => 'Session #14'],
    'attended' => ['at' => '2026-04-27T09:05:00Z'],
    'submitted' => ['at' => '2026-04-27T10:45:00Z'],
  ],
]
```

### 3. Controller Changes — `ApplicationController@index`

- Eager-load `applicant.examSessions` with pivot data and `applicant.examSessions.gradingSession.applicantScores` to avoid N+1
- Add `pipeline_status` and `pipeline_details` to the transformed collection
- Support `?sort=pipeline_status` query parameter for server-side sorting
- Support `?pipeline_status=submitted` filter parameter

### 4. Controller Changes — `ApplicationController@show`

- Load pipeline details and pass to the Inertia view

## Frontend Changes

### 1. Index Page — Pipeline Column

- Replace the current "Status" column badge with a **Pipeline** column
- Show computed pipeline status as a colored badge (using `statusVariant` mapping)
- Add `pipeline_status` to the filter dropdown options
- Support column sorting by `pipeline_status`

Card view: show the pipeline badge below the applicant name.

### 2. Show Page — Pipeline Progress Section

Add a **Pipeline Progress** section below the status action bar showing all 8 stages as a horizontal row:

```
Pending → Accepted → Draft Scheduled → Scheduled → Attended → Submitted → Graded
  ✓         ✓          ✓               ✓           ✓          ✓        ●
```

- Completed stages: filled circle/dot with label, dimmed
- Current stage: highlighted/emphasized badge
- Future stages: hollow circle, muted text
- Where timestamps exist, show them on hover or as small text below

### 3. Helper — `pipelineBadgeVariant()` and `pipelineStatusLabel()`

Extract shared functions (like existing `statusVariant`/`statusLabel`) into a `pipeline-helpers.js` module under `resources/js/lib/`.

## Files to Modify

- `app/Models/Application.php` — add `pipelineStatus()` and `pipelineDetails()` accessors
- `app/Http/Controllers/ApplicationController.php` — eager-load, transform, sort, filter
- `resources/js/Pages/Applications/Index.svelte` — pipeline column, filter, sort
- `resources/js/Pages/Applications/Show.svelte` — pipeline progress section
- `resources/js/lib/pipeline-helpers.js` — new shared helper module

## Out of Scope

- Portal show/edit pages already have `assigned_session_status` — no changes needed there
- Proctor session roster — separate concern, not part of this feature
- Email notifications when pipeline status changes — future consideration
- Bulk pipeline status updates — future consideration