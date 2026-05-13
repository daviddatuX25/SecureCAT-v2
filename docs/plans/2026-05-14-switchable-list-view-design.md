# Switchable List View Design

## Overview
The application currently uses a Table/Card `viewMode` toggle in several core Admin interfaces (like Users, Applications, Test Scheduling). However, this toggle is implemented inline, resulting in duplicated view logic and missing implementations in critical operational interfaces like Grading and Proctoring.

This design introduces a `<SwitchableListView>` component to encapsulate the responsive display logic and provides a roadmap for rolling it out across the application.

## Component Design
`resources/js/Components/SwitchableListView.svelte`

This component uses Svelte 5 snippets to accept the table and card layouts and handles the responsive visibility logic automatically.

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

## Rollout Strategy

### Phase 1: Component Creation & Refactoring
1. Create `SwitchableListView.svelte`.
2. Refactor existing implementations to use the new wrapper to validate it works as a drop-in replacement.
   - `Applications/Index.svelte`
   - `Admin/Users/Index.svelte`
   - `Admin/TestScheduling/Index.svelte`

### Phase 2: Grading & Proctoring Adoption
These interfaces are frequently used on tablets/mobile devices during live exams, making card views highly valuable.
1. `Grading/Dashboard.svelte`
2. `Grading/Session.svelte`
3. `Grading/PrintBatch.svelte`
4. `Proctor/SessionRoster.svelte`

### Phase 3: Other Admin & Operational Interfaces
Standardizing the remaining views for consistency.
1. `Release/Index.svelte`
2. `Applications/PrintSlips.svelte`
3. `Admin/TestScheduling/Show.svelte`
4. `Admin/TestScheduling/Monitoring.svelte`
5. `Admin/KnowledgeDocuments/Index.svelte`
6. `Admin/ResultSheetTemplates/Index.svelte`
7. `Admin/AdmissionSlipTemplates/Index.svelte`
8. `Admin/AiCompanion/Index.svelte` (Knowledge Documents tab)
