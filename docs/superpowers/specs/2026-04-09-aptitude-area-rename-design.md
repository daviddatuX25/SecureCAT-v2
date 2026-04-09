# Aptitude Area Rename — Full Rename from ExamDomain/Exam Pillar

## Date
2026-04-09

## Status
Draft — awaiting user review

---

## 1. Overview

**What:** Rename all `exam_domain` / `ExamDomain` / "exam pillar" references to `aptitude_area` / `AptitudeArea` / "Aptitude Area" across the entire SecureCAT-v2 stack.

**Why:** "Aptitude Area" was agreed as the canonical term (per [2026-04-08-nav-header-consistency.md](../plans/2026-04-08-nav-header-consistency.md)). The UI layer was partially updated but the backend still uses legacy naming. This rename completes the work end-to-end.

**Scope:** Full rename — DB table, model, controller, request classes, seeder, FK columns, services, Svelte pages, routes, nav labels, and template placeholders.

**Strategy:** Dual-write (copy-paste) — new `AptitudeArea` files created alongside existing `ExamDomain` files. Data migrated via seeder. Old files kept as rollback path until verification passes, then deleted.

**Data:** Fresh start (Option B from brainstorming) — `exam_domains` table dropped and reseeded. Acceptable since this is dev/staging.

---

## 2. Files to Create (New)

| File | Purpose |
|------|---------|
| `database/migrations/YYYY_MM_DD_create_aptitude_areas_table.php` | New table with same schema as `exam_domains` |
| `app/Models/AptitudeArea.php` | New Eloquent model |
| `app/Http/Controllers/Admin/AptitudeAreaController.php` | New CRUD controller |
| `app/Http/Requests/Admin/StoreAptitudeAreaRequest.php` | New form request |
| `app/Http/Requests/Admin/UpdateAptitudeAreaRequest.php` | New form request |
| `database/seeders/AptitudeAreaSeeder.php` | Renamed from ExamDomainSeeder |
| `app/Policies/AptitudeAreaPolicy.php` | Renamed from ExamDomainPolicy |
| `resources/js/Pages/Admin/AptitudeAreas/Index.svelte` | Moved from ExamDomains |
| `resources/js/Pages/Admin/AptitudeAreas/Create.svelte` | Moved from ExamDomains |
| `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte` | Moved from ExamDomains |
| `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte` | Moved from ExamDomains |
| `routes/web.php` | Add new route entries for AptitudeArea |

---

## 3. Files to Modify (Existing)

| File | Changes |
|------|---------|
| `routes/web.php` | Update route names and URI: `admin.exam-domains` → `admin.aptitude-areas`, `/admin/exam-domains` → `/admin/aptitude-areas` |
| `app/Providers/AppServiceProvider.php` or PolicyRegistrar | Register `AptitudeAreaPolicy` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Update nav label: `'admin.exam-domains.index' => 'Aptitude Areas'` |
| `app/Services/ResultSheetTemplateService.php` | Rename `domainSlug()` → `aptitudeAreaSlug()` |
| `resources/js/utils/domains.js` | Rename to `aptitudeAreas.js`, update `domainSlug` → `aptitudeAreaSlug` |
| `resources/js/Pages/ResultSheet/Index.svelte` | Update breadcrumb label if present |
| `routes/web.php` | Remove old `ExamDomain` routes after verification |

---

## 4. FK Column Updates

| Migration | Change |
|-----------|--------|
| `YYYY_MM_DD_create_applicant_scores_table.php` | Rename `domain_id` → `aptitude_area_id` |
| `YYYY_MM_DD_create_decision_rules_table.php` | Rename `domain_id` → `aptitude_area_id` |

Note: These are new migrations that ALTER the existing tables. They are separate from the table rename to allow independent rollback.

---

## 5. Svelte Page Updates (per existing audit plan)

Already defined in `2026-04-08-nav-header-consistency.md` — canonical label is **"Aptitude Areas"**.

**AptitudeAreas/Index.svelte:**
```js
const breadcrumbs = [{ label: 'Aptitude Areas' }];
```

**AptitudeAreas/Create.svelte:**
```js
const breadcrumbs = [
  { label: 'Aptitude Areas', href: '/admin/aptitude-areas' },
  { label: 'Create' },
];
```

**AptitudeAreas/Edit.svelte:**
```js
const breadcrumbs = [
  { label: 'Aptitude Areas', href: '/admin/aptitude-areas' },
  { label: 'Edit' },
];
```

All three: remove `<svelte:head>`, change layout tag to `<AuthenticatedLayout {breadcrumbs}>`, remove `<h1>` headings.

---

## 6. Database Migrations Order

1. **Create new table** — `create_aptitude_areas_table.php` — same columns as `exam_domains` (id, name, code, description, max_score, weight, is_active, display_order)
2. **Migrate data** — copy data from `exam_domains` to `aptitude_areas` via seeder (seeder reads from old table during transition)
3. **Add FK columns to existing tables** — `applicant_scores` and `decision_rules` get `aptitude_area_id` columns added alongside `domain_id` (dual-write during transition)
4. **Verify** — all scores and rules work via new `aptitude_area_id`
5. **Drop old table** — `dropIfExists('exam_domains')` — only after full verification

> **Note:** Steps 3-5 happen AFTER the new table, controller, and Svelte layers are live and verified. This ensures the old `domain_id` columns serve as rollback if anything goes wrong.

---

## 7. Verification Checklist

After each phase:

- [ ] New table seeded — AptitudeAreas Index page shows data
- [ ] Create works — form submits, redirects to Index with new record
- [ ] Edit works — form loads existing record, updates persist
- [ ] Delete works — record removed from list
- [ ] Grading page — still loads and computes correctly
- [ ] Result sheet template — placeholder slug renders `{{spatial_awareness}}` etc. still work
- [ ] Decision rules — existing rules still evaluate correctly
- [ ] Browser nav — sidebar label shows "Aptitude Areas"

---

## 8. Rollback Plan

- Keep `ExamDomain`, `ExamDomainController`, `ExamDomainSeeder`, `ExamDomains/` Svelte folder until verification passes
- If any issue: revert routes to point back to old controllers
- After full verification: delete old files and commit "cleanup: remove legacy ExamDomain files"

---

## 9. Commit Sequence

1. `feat: create aptitude_areas table and AptitudeArea model`
2. `feat: add AptitudeAreaController CRUD and request classes`
3. `feat: add AptitudeAreaPolicy`
4. `feat: migrate exam_domains FK columns to aptitude_area_id`
5. `feat: rename ExamDomains Svelte pages to AptitudeAreas`
6. `feat: update nav label and routes to Aptitude Areas`
7. `feat: update domainSlug() to aptitudeAreaSlug()`
8. `cleanup: remove legacy ExamDomain files`
