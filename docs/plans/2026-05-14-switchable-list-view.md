# Switchable List View Component Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Create `SwitchableListView` and `SimplePagination` components to encapsulate the repeated table/card toggle and pagination patterns, then refactor all 7 existing pages that duplicate this logic.

**Architecture:** Two Svelte 5 components:
1. `SwitchableListView` — accepts `table`/`cards` snippets plus an `overflow` prop, wrapping `ViewModeToggle` and responsive visibility classes.
2. `SimplePagination` — accepts a paginated data object and a `variant` prop, eliminating duplicated pagination markup across 7 pages.

**Tech Stack:** Svelte 5, TailwindCSS, Inertia.js

---

### Observations (Why This Plan Changed)

The original plan only targeted Applications/Index and Grading/Dashboard. Analysis of the codebase reveals:

1. **7 pages** use `ViewModeToggle` with the same toggle + visibility pattern — all should benefit from the extraction.
2. **Dashboard.svelte** uses a different layout pattern (Card components with nested tables) and has no existing card view. Adding cards there requires a separate design spec — out of scope for this refactor.
3. **Logs/Index** imports `ViewModeToggle` but never implements card content — the toggle is visible but does nothing in card mode. This should be fixed.
4. Pages vary in `overflow-x-*` classes (`scrollbar-hide` vs `overscroll-x-contain`). The component needs an `overflow` prop.
5. **Pagination is duplicated across all 7 pages** with two layout variants: `border-t justify-between` (table footer style) and `mt-4 justify-center gap-2` (centered, used in card views). A `SimplePagination` component can eliminate this duplication with a `variant` prop.

---

### Task 1: Create SwitchableListView Component

**Files:**
- Create: `resources/js/Components/SwitchableListView.svelte`

**Step 1: Write the implementation**

```svelte
<script>
  import ViewModeToggle from '@/Components/ViewModeToggle.svelte';

  let {
    viewMode = $bindable('responsive'),
    table,
    cards,
    hideToggle = false,
    overflow = 'auto',
    class: className = ''
  } = $props();

  const overflowClasses = {
    auto: 'overflow-x-auto scrollbar-hide',
    scroll: 'overflow-x-scroll overscroll-x-contain',
    none: ''
  };
</script>

<div class="space-y-3 {className}">
  {#if !hideToggle}
    <div class="flex justify-end">
      <ViewModeToggle bind:value={viewMode} />
    </div>
  {/if}

  <!-- Table View -->
  <div class="{viewMode === 'cards' ? 'hidden' : viewMode === 'table' ? 'block' : 'hidden md:block'} min-w-0">
    <div class="w-full min-w-0 {overflowClasses[overflow] ?? overflowClasses.auto}">
      {@render table?.()}
    </div>
  </div>

  <!-- Cards View -->
  <div class="{viewMode === 'table' ? 'hidden' : viewMode === 'cards' ? 'block' : 'block md:hidden'}">
    {@render cards?.()}
  </div>
</div>
```

**Why the `overflow` prop:** Pages use different scroll behaviors:
- `auto` (default): Applications, AcademicYears, TestScheduling use `overflow-x-auto scrollbar-hide`
- `scroll`: Courses, Users use `overflow-x-scroll overscroll-x-contain`
- `none`: Logs has no scroll wrapper

**Step 2: Verify component compiles**

Run: `npm run build`
Expected: Successful build with no Svelte compiler errors.

**Step 3: Commit**

```bash
git add resources/js/Components/SwitchableListView.svelte
git commit -m "feat(ui): add SwitchableListView component"
```

---

### Task 2: Create SimplePagination Component

**Files:**
- Create: `resources/js/Components/SimplePagination.svelte`

**Step 1: Write the implementation**

```svelte
<script>
  import { Link } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';

  let {
    data,
    variant = 'table',
    class: className = ''
  } = $props();
</script>

{#if data != null && data.last_page > 1}
  <div class="{variant === 'table' ? 'flex items-center justify-between border-t border-border px-4 py-2' : 'mt-4 flex justify-center gap-2'} {className}">
    {#if variant === 'table'}
      <p class="text-sm text-muted-foreground">
        Page {data.current_page} of {data.last_page}
      </p>
      <div class="flex gap-2">
        {#if data.prev_page_url}
          <Link href={data.prev_page_url}>
            <Button variant="outline" size="sm">Previous</Button>
          </Link>
        {/if}
        {#if data.next_page_url}
          <Link href={data.next_page_url}>
            <Button variant="outline" size="sm">Next</Button>
          </Link>
        {/if}
      </div>
    {:else}
      {#if data.prev_page_url}
        <Link href={data.prev_page_url}>
          <Button variant="outline" size="sm">Previous</Button>
        </Link>
      {/if}
      {#if data.next_page_url}
        <Link href={data.next_page_url}>
          <Button variant="outline" size="sm">Next</Button>
        </Link>
      {/if}
    {/if}
  </div>
{/if}
```

**`variant` prop:**
- `"table"` (default): Border-top separator, "Page X of Y" label, justify-between layout. Used inside or near table views.
- `"centered"`: Centered buttons only, no border or page label. Used in card views.

**`data` prop:** Any Inertia paginated object with `last_page`, `current_page`, `prev_page_url`, `next_page_url`.

**Step 2: Verify component compiles**

Run: `npm run build`
Expected: Successful build.

**Step 3: Commit**

```bash
git add resources/js/Components/SimplePagination.svelte
git commit -m "feat(ui): add SimplePagination component"
```

---

### Task 3: Refactor Applications Index

**Files:**
- Modify: `resources/js/Pages/Applications/Index.svelte`

**Step 1: Write the implementation**

1. Replace `import ViewModeToggle` with `import SwitchableListView` and `import SimplePagination`.
2. Remove the inline `ViewModeToggle` div and the two visibility-wrapper divs.
3. Replace both pagination blocks with `<SimplePagination data={applications} />` (table variant inside table snippet, centered variant inside cards snippet).
4. Wrap the table content in `{#snippet table()}`.
5. Wrap the cards content in `{#snippet cards()}`.
6. Replace both with `<SwitchableListView bind:viewMode overflow="auto">`.

**Step 2: Verify**

Run: `npm run build`
Check: Table/card toggle still works, pagination renders in both views at `/admin/applications`.

**Step 3: Commit**

```bash
git add resources/js/Pages/Applications/Index.svelte
git commit -m "refactor(applications): use SwitchableListView and SimplePagination"
```

---

### Task 4: Refactor Users Index

**Files:**
- Modify: `resources/js/Pages/Admin/Users/Index.svelte`

**Step 1: Write the implementation**

1. Replace `import ViewModeToggle` with `import SwitchableListView` and `import SimplePagination`.
2. Remove the inline `ViewModeToggle` div and visibility-wrapper divs.
3. Replace the pagination block with `<SimplePagination data={users} />` placed after `</SwitchableListView>` (shared between views).
4. Wrap table content in `{#snippet table()}`.
5. Wrap card content in `{#snippet cards()}`.
6. Use `<SwitchableListView bind:viewMode overflow="scroll">` (this page uses `overscroll-x-contain`).

**Step 2: Verify**

Run: `npm run build`
Check: Table/card toggle works, pagination shows, delete modal works at `/admin/users`.

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/Users/Index.svelte
git commit -m "refactor(users): use SwitchableListView and SimplePagination"
```

---

### Task 5: Refactor AcademicYears Index

**Files:**
- Modify: `resources/js/Pages/Admin/AcademicYears/Index.svelte`

**Step 1: Write the implementation**

Same pattern as Task 3. Uses `overflow-x-auto scrollbar-hide` and `SimplePagination data={academic_years}`.
Use `<SwitchableListView bind:viewMode overflow="auto">`.

**Step 2: Verify**

Run: `npm run build`

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/AcademicYears/Index.svelte
git commit -m "refactor(academic-years): use SwitchableListView and SimplePagination"
```

---

### Task 6: Refactor Courses Index

**Files:**
- Modify: `resources/js/Pages/Admin/Courses/Index.svelte`

**Step 1: Write the implementation**

Same pattern as Task 4. Uses `overflow-x-scroll overscroll-x-contain` and `SimplePagination data={courses}`.
Use `<SwitchableListView bind:viewMode overflow="scroll">`.

**Step 2: Verify**

Run: `npm run build`

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/Courses/Index.svelte
git commit -m "refactor(courses): use SwitchableListView and SimplePagination"
```

---

### Task 7: Refactor Rooms Index

**Files:**
- Modify: `resources/js/Pages/Admin/Rooms/Index.svelte`

**Step 1: Write the implementation**

Uses `overflow-x-auto scrollbar-hide` and `SimplePagination data={rooms}`.
Use `<SwitchableListView bind:viewMode overflow="auto">`.

**Step 2: Verify**

Run: `npm run build`

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/Rooms/Index.svelte
git commit -m "refactor(rooms): use SwitchableListView and SimplePagination"
```

---

### Task 8: Refactor TestScheduling Index

**Files:**
- Modify: `resources/js/Pages/Admin/TestScheduling/Index.svelte`

**Step 1: Write the implementation**

Uses `overflow-x-auto scrollbar-hide` and `SimplePagination data={sessions}`.
Use `<SwitchableListView bind:viewMode overflow="auto">`.

**Step 2: Verify**

Run: `npm run build`

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/TestScheduling/Index.svelte
git commit -m "refactor(test-scheduling): use SwitchableListView and SimplePagination"
```

---

### Task 9: Fix Logs Index (Remove Dead Toggle)

**Files:**
- Modify: `resources/js/Pages/Admin/Logs/Index.svelte`

**Step 1: Write the implementation**

This page imports `ViewModeToggle` but never implements cards — the toggle is visible but useless in card mode. Two options:

**Option A (recommended):** Remove the toggle entirely since audit logs are inherently tabular.
- Remove `ViewModeToggle` import.
- Remove `viewMode` state.
- Remove visibility classes from the table div.
- Replace pagination block with `<SimplePagination data={logs} />`.
- No `SwitchableListView` needed.

**Option B:** Add a card view. Extra work for a data type that doesn't benefit from card layout.

Use Option A.

**Step 2: Verify**

Run: `npm run build`
Check: Table renders correctly at `/admin/logs` with no toggle visible, detail modal still works.

**Step 3: Commit**

```bash
git add resources/js/Pages/Admin/Logs/Index.svelte
git commit -m "fix(logs): remove unused ViewModeToggle, add SimplePagination"
```

---

### Task 10: Final Verification

**Step 1: Run full build and lint**

```bash
npm run build
vendor/bin/pint --dirty --format agent
```

**Step 2: Visual regression check**

Visit each refactored page and verify:
- `/admin/applications` — table/card toggle works, filters intact, pagination works
- `/admin/users` — table/card toggle works, delete modal still works
- `/admin/academic-years` — table/card toggle works
- `/admin/courses` — table/card toggle works
- `/admin/rooms` — table/card toggle works
- `/admin/test-scheduling` — table/card toggle works, monitoring links intact
- `/admin/logs` — table renders, no toggle visible, detail modal works

**Step 3: Verify ViewModeToggle is only imported in SwitchableListView**

```bash
grep -r "ViewModeToggle" resources/js/Pages/
```

Expected: No results (all imports moved to SwitchableListView).

**Step 4: Commit any lint fixes**

```bash
git add -A
git commit -m "chore: lint fixes from SwitchableListView refactor"
```

---

### Out of Scope (Future Work)

- **Grading Dashboard cards**: Dashboard.svelte uses Card components with nested tables — a fundamentally different pattern. Adding card views requires a dedicated design spec for grading session cards and completed exam cards. This should be a separate feature, not a refactor.