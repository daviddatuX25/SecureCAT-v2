# Design Doc: Refining Result Sheet Templates and Navigation Highlighting

This design document covers two minor refinements:
1. Fixing the sidebar navigation highlight when visiting result templates.
2. Making several template validation placeholders optional.

---

## 1. Sidebar Navigation Highlight

### Problem
When the user visits `http://securecat-v2.test/admin/release/result-templates`, the sidebar highlights the **Release** item instead of the **Setup** item. This occurs because the URL prefix matches `/admin/release/` (Release href is `/admin/release`).

### Solution
We will adjust the `isNavActive` logic in `resources/js/Layouts/AuthenticatedLayout.svelte` to support an `exclude` list for navigation items.
If a navigation item contains an array of routes in `exclude`, any active URL matching those routes or starting with those routes will cause the item to evaluate as *not active*.

We will:
- Add `exclude: ['/admin/release/result-templates']` to the **Release** item in `AuthenticatedLayout.svelte`.
- Add `'/admin/release/result-templates'` to the `activeFor` array of the **Setup** item.
- Update `isNavActive` to check `item.exclude` first:
  ```javascript
  if (item.exclude) {
    for (const prefix of item.exclude) {
      if (url === prefix || url === prefix + '/') return false;
      if (prefix !== '/' && url.startsWith(prefix + '/')) return false;
    }
  }
  ```

---

## 2. Optional Template Placeholders

### Problem
Templates require/recommend a long list of placeholders, causing validation to fail if variables like `{{applicant_name}}`, `{{suffix}}`, `{{exam_date}}`, `{{exam_time}}`, `{{room_name}}`, and `{{overall_pct}}` are missing. These should be optional.

### Solution
In `app/Services/ResultSheetTemplateService.php`, we will modify the `buildCategorizedPlaceholders` method:
- Move `'applicant_name'` from `'required'` to `'optional'`.
- Move `'suffix'`, `'exam_date'`, `'exam_time'`, `'room_name'`, and `'overall_pct'` from `'recommended'` to `'optional'`.
- The only remaining required placeholder for slot 1 will be `'applicant_reference'`.

We will also update tests that assume multiple required placeholders during mock template uploads/creation:
- `tests/Feature/ResultSheetTemplateControllerTest.php`
- `tests/Feature/ResultSheetDocxServiceTest.php`
- `tests/Feature/ResultSheetOdtServiceTest.php`
