# Switchable List View Component Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Create a `SwitchableListView` component to encapsulate the table/card responsive logic, and adopt it in the Applications index and Grading Dashboard.

**Architecture:** A Svelte 5 component that accepts `table` and `cards` snippets, utilizing Tailwind for responsive state display based on the `viewMode` toggle.

**Tech Stack:** Svelte 5, TailwindCSS, Inertia.js

---

### Task 1: Create SwitchableListView Component

**Files:**
- Create: `resources/js/Components/SwitchableListView.svelte`

**Step 1: Write the implementation**

Create the component using Svelte 5 snippets syntax.

```svelte
<script>
  import ViewModeToggle from '@/Components/ViewModeToggle.svelte';
  
  let { 
    viewMode = $bindable('responsive'),
    table, 
    cards,
    hideToggle = false,
    class: className = ''
  } = $props();
</script>

<div class="space-y-3 {className}">
  {#if !hideToggle}
    <div class="flex justify-end">
      <ViewModeToggle bind:value={viewMode} />
    </div>
  {/if}

  <!-- Table View -->
  <div class="{viewMode === 'cards' ? 'hidden' : viewMode === 'table' ? 'block' : 'hidden md:block'} min-w-0">
    {@render table?.()}
  </div>

  <!-- Cards View -->
  <div class="{viewMode === 'table' ? 'hidden' : viewMode === 'cards' ? 'block' : 'block md:hidden'}">
    {@render cards?.()}
  </div>
</div>
```

**Step 2: Commit**

```bash
git add resources/js/Components/SwitchableListView.svelte
git commit -m "feat(ui): add SwitchableListView component"
```

### Task 2: Refactor Applications Index

**Files:**
- Modify: `resources/js/Pages/Applications/Index.svelte`

**Step 1: Write the implementation**

Replace the inline `ViewModeToggle` and layout toggle with the new `SwitchableListView` component.

1. Import `SwitchableListView` at the top.
2. Remove the inline `ViewModeToggle` `div` wrapper.
3. Wrap the table `div` in `{#snippet table()}`.
4. Wrap the cards `div` in `{#snippet cards()}`.
5. Wrap both snippets inside `<SwitchableListView bind:viewMode>`.

**Step 2: Run linter/build to verify it compiles**

Run: `npm run build`
Expected: Successful build with no Svelte compiler errors.

**Step 3: Commit**

```bash
git add resources/js/Pages/Applications/Index.svelte
git commit -m "refactor(applications): use SwitchableListView component"
```

### Task 3: Adopt in Grading Dashboard (Active Sessions)

**Files:**
- Modify: `resources/js/Pages/Grading/Dashboard.svelte`

**Step 1: Write the implementation**

1. Import `SwitchableListView` at the top.
2. Add `let sessionsViewMode = $state('responsive');` and `let examsViewMode = $state('responsive');` to script.
3. For the "Grading sessions" table, wrap it in `<SwitchableListView bind:viewMode={sessionsViewMode}>`.
4. Create the `{#snippet table()}` with the existing table.
5. Create the `{#snippet cards()}` duplicating the iteration but using a card layout (similar to Applications index).

**Step 2: Run linter/build to verify it compiles**

Run: `npm run build`
Expected: Successful build.

**Step 3: Commit**

```bash
git add resources/js/Pages/Grading/Dashboard.svelte
git commit -m "feat(grading): adopt SwitchableListView for grading sessions"
```

### Task 4: Adopt in Grading Dashboard (Completed Exams)

**Files:**
- Modify: `resources/js/Pages/Grading/Dashboard.svelte`

**Step 1: Write the implementation**

1. For the "Completed exams" table, wrap it in `<SwitchableListView bind:viewMode={examsViewMode}>`.
2. Create the `{#snippet table()}` with the existing table.
3. Create the `{#snippet cards()}` using a card layout.

**Step 2: Run linter/build to verify it compiles**

Run: `npm run build`
Expected: Successful build.

**Step 3: Commit**

```bash
git add resources/js/Pages/Grading/Dashboard.svelte
git commit -m "feat(grading): adopt SwitchableListView for completed exams"
```
