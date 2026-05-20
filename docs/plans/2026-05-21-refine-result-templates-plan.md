# Refining Result Sheet Templates Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Fix the sidebar navigation highlighting when viewing result templates, and make several required template placeholders optional.

**Architecture:** 
1. Update `resources/js/Layouts/AuthenticatedLayout.svelte` navigation config with `exclude: [...]` and update `isNavActive` logic to respect it.
2. In `app/Services/ResultSheetTemplateService.php`, reclassify `applicant_name`, `suffix`, `exam_date`, `exam_time`, `room_name`, and `overall_pct` from required/recommended to optional.
3. Fix PHPUnit tests to align with the new minimal required placeholders.

**Tech Stack:** Svelte 5, Inertia.js v2, Laravel 12, PHPUnit 11

---

### Task 1: Sidebar Highlight Update

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.svelte`

**Step 1: Write the changes to AuthenticatedLayout.svelte**
Add `exclude: ['/admin/release/result-templates']` to the **Release** navigation item.
Add `/admin/release/result-templates` to the `activeFor` list of the **Setup** navigation item.
Update `isNavActive` to check for `item.exclude` first:
```javascript
  function isNavActive(item) {
    const rawUrl = $page.url || '';
    // Strip query params and hash so ?search=foo&page=2 doesn't break matching
    const url = rawUrl.split('?')[0].split('#')[0];
    const href = item.href ?? '';

    // Check exclude list first
    if (item.exclude) {
      for (const prefix of item.exclude) {
        if (url === prefix || url === prefix + '/') return false;
        if (prefix !== '/' && url.startsWith(prefix + '/')) return false;
      }
    }

    if (url === href || url === href + '/') return true;
    if (href !== '/' && url.startsWith(href + '/')) return true;

    // Check activeFor prefixes for shared pages & child routes
    if (item.activeFor) {
      for (const prefix of item.activeFor) {
        if (url === prefix || url === prefix + '/') return true;
        if (prefix !== '/' && url.startsWith(prefix + '/')) return true;
      }
    }

    return false;
  }
```

**Step 2: Verify in the frontend**
Verify visually/logically that the logic handles `/admin/release/result-templates` and its sub-paths by returning `false` for Release and `true` for Setup.

**Step 3: Commit**
```bash
git add resources/js/Layouts/AuthenticatedLayout.svelte
git commit -m "style: fix sidebar navigation highlighting for result templates"
```

---

### Task 2: Make Placeholders Optional in ResultSheetTemplateService

**Files:**
- Modify: `app/Services/ResultSheetTemplateService.php`

**Step 1: Write minimal implementation**
Modify `buildCategorizedPlaceholders` in `app/Services/ResultSheetTemplateService.php` to move `applicant_name` from `'required'` to `'optional'`, and move `'suffix'`, `'exam_date'`, `'exam_time'`, `'room_name'`, and `'overall_pct'` from `'recommended'` to `'optional'`.

```php
    protected function buildCategorizedPlaceholders(): array
    {
        $categorized = [
            'required' => ['applicant_reference'],
            'recommended' => [
                'family_name', 'first_name', 'middle_name',
                'sex', 'course_applied', 'applicant_type',
            ],
            'optional' => [
                'applicant_name', 'suffix', 'exam_date', 'exam_time', 'room_name', 'overall_pct',
                'gwa', 'strand',
                'recommended_course', 'counselor_comments', 'counselor_name',
            ],
```

**Step 2: Commit**
```bash
git add app/Services/ResultSheetTemplateService.php
git commit -m "feat: make applicant_name, suffix, exam_date, exam_time, room_name, and overall_pct optional in result templates"
```

---

### Task 3: Fix PHPUnit Tests for Templates

**Files:**
- Modify: `tests/Feature/ResultSheetTemplateControllerTest.php`
- Modify: `tests/Feature/ResultSheetDocxServiceTest.php` (if any tests fail due to the missing required fields in dummy content)
- Modify: `tests/Feature/ResultSheetOdtServiceTest.php` (if any tests fail due to the missing required fields in dummy content)

**Step 1: Run existing tests to identify failures**
Run: `php artisan test --compact` or run targeted tests.
Expected: Some template/docx/odt tests might fail because their dummy content or assertions expect `applicant_name` to be required, or expect `buildCategorizedPlaceholders` to return it in the required array.

**Step 2: Fix failing test cases**
Update any tests that assert that `applicant_name` is required or recommended. For example, in `ResultSheetTemplateControllerTest`, make sure the mock/dummy template content still passes validation, and check assertions on which placeholders are required/recommended.

**Step 3: Run all tests to verify they pass**
Run: `php artisan test --compact`
Expected: All tests pass.

**Step 4: Commit**
```bash
git add tests/Feature/ResultSheetTemplateControllerTest.php
git commit -m "test: align result template tests with new optional placeholders"
```
