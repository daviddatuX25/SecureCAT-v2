# Reusable Guide System Design

**Date:** 2026-05-15
**Status:** Draft
**Scope:** Modular, composable guide components for import pages and result sheet templates

## Problem

The ResultSheetTemplates Create/Edit pages have an inline guide implementation (~100 lines duplicated per page) with a toggleable panel, copy-to-clipboard pills, and dynamic sections. Three import pages (Applicant Import, Score Import, Knowledge Document Import) need similar guide functionality but with different content and varying feature needs — some need copyable items, some don't.

Current issues:
- Duplicated guide code across Create/Edit pages
- `copiedKeys` state grows without bound (never resets)
- No reusable guide components exist
- Import pages have no guidance at all (users guess at formats, constraints, column names)

## Architecture: Slot-Based Component Library

Small, focused Svelte components that compose via slots. Pages build guides by nesting components in the template. No config-object schema to maintain. Pages only import what they use.

### File Structure

```
resources/js/Components/Guide/
  GuidePanel.svelte       # Collapsible wrapper
  GuideSection.svelte     # Titled section with conditional render
  CopyableItem.svelte     # Single copy-to-clipboard pill
  CopyableGroup.svelte    # Group of copyable pills with subtitle
  GuideNote.svelte        # Info/warning/tip callout block
  index.ts                # Re-exports
```

## Component Specifications

### GuidePanel.svelte

The outer collapsible container. Manages open/close state.

**Props:**
- `title: string` — header text
- `icon?: Component` — Lucide icon (defaults to `HelpCircle`)
- `defaultOpen?: boolean` — initial state (default `false`)
- `class?: string` — merge extra classes

**Behavior:**
- Session-only toggle (no localStorage persistence)
- Click header to expand/collapse
- Animated slide transition (Svelte `slide` or `fly`)
- Default slot for content

### GuideSection.svelte

A titled section within a guide. Supports conditional rendering.

**Props:**
- `title: string` — section heading
- `visible?: boolean` — when `false`, renders nothing. Default `true`. Pass `undefined` to always show.
- `class?: string`

**Behavior:**
- `{#if visible !== false}` conditional render
- Small heading with separator styling
- Default slot for content

### CopyableItem.svelte

A single pill/button that copies text to clipboard on click.

**Props:**
- `value: string` — text copied to clipboard
- `label?: string` — display text (defaults to `value` with `{{}}` stripped)
- `class?: string`

**Behavior:**
- Click copies `value` using Clipboard API with `execCommand` fallback
- Shows `Copy` icon normally, `Check` icon indefinitely after copy (resets on page refresh)
- Calls `success()` toast with "Copied {label}"
- Self-contained — no parent state management needed (uses internal `$state`)

### CopyableGroup.svelte

Renders a flex-wrap row of CopyableItems with an optional subtitle.

**Props:**
- `items: Array<{ value: string, label?: string }>`
- `subtitle?: string` — text above the pills
- `class?: string`

**Behavior:**
- Renders subtitle (if provided) then a flex-wrap row of `CopyableItem`s

### GuideNote.svelte

A styled callout block with icon and color variant.

**Props:**
- `variant: 'info' | 'warning' | 'tip'` — controls icon and color
- `title?: string` — optional bold heading inside the note
- `class?: string`

**Slots:** Default slot for content (supports `{@html}`)

**Visual variants:**
- `info` → blue accent, `Info` icon
- `warning` → amber accent, `AlertTriangle` icon
- `tip` → green accent, `Lightbulb` icon

## Per-Page Guide Content

### 1. ResultSheetTemplates (Create + Edit) — Migration

Refactor the existing inline guide to use the components. Functionally identical output.

```svelte
<GuidePanel title="Placeholder Reference & Templates Guide">
  <GuideSection title="Applicant 1 Placeholders">
    <CopyableGroup items={applicant1Placeholders} />
  </GuideSection>

  <GuideSection title="Applicant 2 Placeholders" visible={isCrosswise}>
    <CopyableGroup items={applicant2Placeholders} />
  </GuideSection>

  <GuideSection title="Domain Tags" visible={domainPlaceholders.length > 0}>
    <CopyableGroup items={domainItems} subtitle="Click to copy" />
  </GuideSection>

  <GuideSection title="HTML Rules" visible={$form.mode === 'html'}>
    <GuideNote variant="info">{@html htmlTemplateRules}</GuideNote>
    {#if htmlScoresNote}
      <GuideNote variant="info" title="Scores Table">{@html htmlScoresNote}</GuideNote>
    {/if}
  </GuideSection>

  <GuideSection title="DOCX Notes" visible={$form.mode === 'docx'}>
    <GuideNote variant="info">{docxPlaceholderNote}</GuideNote>
  </GuideSection>
</GuidePanel>
```

The `domainItems` computed property maps domain placeholders like so:
- Full mode: `domainPlaceholders.map(d => ({ value: d.example, label: d.slug }))`
- Crosswise mode: each domain produces two items: `{ value: d.example, label: d.slug }` and `{ value: d.example.replace('}}', '_2}}'), label: d.slug + '_2' }`

### 2. Applicant Import

```svelte
<GuidePanel title="Applicant Import Guide">
  <GuideSection title="Required Columns">
    <CopyableGroup items={requiredColumns} subtitle="These columns must be present in your CSV" />
  </GuideSection>

  <GuideSection title="Optional Columns">
    <CopyableGroup items={optionalColumns} subtitle="Additional columns you can include" />
  </GuideSection>

  <GuideSection title="Available Courses">
    <CopyableGroup items={courseItems} subtitle="Use course codes in course_preference columns" />
    <GuideNote variant="tip" title="CSV Matching">
      Use the course <strong>code</strong> (e.g., BSCS), not the course name or ID.
      This matches the column header directly.
    </GuideNote>
  </GuideSection>

  <GuideSection title="Field Rules">
    <GuideNote variant="info" title="Email">
      Must be unique per academic year. Duplicates will be skipped.
    </GuideNote>
    <GuideNote variant="info" title="Birthdate">
      Accepted formats: YYYY-MM-DD, M/D/YYYY, or MM/DD/YYYY.
    </GuideNote>
    <GuideNote variant="warning" title="File Format">
      Only CSV files are accepted. Maximum size: 10MB. First row must be headers.
    </GuideNote>
  </GuideSection>
</GuidePanel>
```

**Backend changes:** `ApplicationImportController::importForm()` passes additional Inertia props:
- `courses` — active courses with `code` + `name`
- `requiredColumns` — from `ApplicantImportService::REQUIRED_COLUMNS`
- `optionalColumns` — from `ApplicantImportService::OPTIONAL_COLUMNS`

`courseItems` is computed on the frontend: `courses.map(c => ({ value: c.code, label: '${c.code} — ${c.name}' }))`.

**Note:** Currently `course_preference_1/2/3` accept numeric IDs. A separate refactor will switch these to use course codes for spreadsheet matching. The guide anticipates this by showing course codes.

### 3. Score Import

```svelte
<GuidePanel title="Score Import Guide">
  <GuideSection title="Required Column">
    <CopyableGroup
      items={[{ value: 'reference_number', label: 'reference_number' }]}
      subtitle="Must match an existing application reference"
    />
  </GuideSection>

  <GuideSection title="Score Columns">
    <CopyableGroup items={aptitudeAreaItems} subtitle={scoreColumnSubtitle} />
    <GuideNote variant="tip" title="Column Meaning">
      {#if normalizedEnabled}
        Columns show <strong>(raw)</strong> — enter the raw score and the system computes the normalized value.
      {:else}
        Enter the final normalized score directly.
      {/if}
    </GuideNote>
  </GuideSection>

  <GuideSection title="Prerequisites">
    <GuideNote variant="warning" title="Before Importing">
      Applicants must have a completed exam session with an open grading session.
      Entries without this will be marked invalid.
    </GuideNote>
  </GuideSection>

  <GuideSection title="File Rules">
    <GuideNote variant="info">
      Accepted formats: CSV, XLSX, XLS, TXT. Maximum size: 10MB. First row must be headers.
    </GuideNote>
    <GuideNote variant="info" title="Duplicate Scores">
      If an applicant already has a score for an aptitude area, the import will mark that row as invalid.
    </GuideNote>
  </GuideSection>
</GuidePanel>
```

**Dynamic activation:** `scoreColumnSubtitle` computed based on `enableNormalizedScores` setting — "(raw)" suffix when enabled, "(normalized)" when disabled.

### 4. Knowledge Document Import

```svelte
<GuidePanel title="CSV Import Guide">
  <GuideSection title="How It Works">
    <GuideNote variant="info" title="Conversion">
      Each row becomes a narrative sentence: <code>Row N: col1: val1; col2: val2; ...</code>
      Empty cells are skipped.
    </GuideNote>
  </GuideSection>

  <GuideSection title="File Rules">
    <GuideNote variant="warning">Maximum 5,000 rows per file.</GuideNote>
    <GuideNote variant="info">
      Accepted formats: CSV, TXT. Maximum size: 2MB. UTF-8 encoding required. First row is used as headers.
    </GuideNote>
  </GuideSection>

  <GuideSection title="Metadata Tips">
    <GuideNote variant="tip" title="Category">
      Use broad categories like "Engineering" or "Health Sciences" for better retrieval.
    </GuideNote>
    <GuideNote variant="tip" title="Tags">
      Comma-separated keywords that help the AI find this document.
      e.g., <code>success_rates, engineering</code>
    </GuideNote>
  </GuideSection>
</GuidePanel>
```

No copyable items — purely informational.

## Robustness Additions

### Accessibility

- **GuidePanel** must include `aria-expanded`, `aria-controls` (pointing to content region `id`), and `role="region"` on the collapsible content area
- **CopyableItem** must include `aria-label` (e.g., "Copy reference_number") and `role="button"`, with `aria-live="polite"` feedback on the icon swap (or a visually-hidden status span announcing "Copied")
- **CopyableGroup** uses `role="list"` with items as `role="listitem"` for screen reader navigation
- Focus-visible ring on all interactive elements (`focus-visible:ring-2 focus-visible:ring-offset-2`)

### Clipboard Error Handling

`CopyableItem` must handle clipboard failure gracefully:
1. Try `navigator.clipboard.writeText()` first
2. Fallback to `document.execCommand('copy')` with a hidden textarea
3. If both fail: call `error()` toast with "Could not copy — please select and copy manually"
4. Clean up the hidden textarea in a `finally` block (prevent DOM leak)

### Component Lifecycle

- `CopyableItem` uses `onDestroy` to clear its `setTimeout` if the component unmounts during the 3-second feedback window (prevents stale state writes)
- `GuidePanel` does NOT need lifecycle cleanup (no timers)

### SSR Safety

- `navigator.clipboard` and `document.execCommand` are browser-only. Wrap clipboard logic in `import { browser } from '$app/environment'` guard — on SSR, the copy button renders but click is a no-op that shows an info toast suggesting the user copy manually

### Transition Standardization

- GuidePanel uses Svelte's `slide` transition with `duration: 200` for open/close
- No custom easing — Svelte's default `cubicOut` is fine for this UI

### Type Safety

Export a `CopyableItemData` interface from `index.ts`:
```ts
export interface CopyableItemData {
  value: string;
  label?: string;
}
```
Used by both `CopyableGroup` (as `items: CopyableItemData[]`) and `CopyableItem` (destructured props).

### Label Stripping Rule

When `label` is not provided, `CopyableItem` derives it from `value` by:
1. Stripping `{{` and `}}` → e.g., `{{applicant_name}}` → `applicant_name`
2. Replacing `_2` suffix with ` (Applicant 2)` → e.g., `domain_code_2` → `domain_code (Applicant 2)`
3. Replacing underscores with spaces → `applicant name`

## Bug Fix: copiedKeys Growing Array

The existing ResultSheetTemplates guide tracks copied items in a `copiedKeys` array that grows without bound and persists incorrectly across navigations. The new `CopyableItem` component replaces this with an internal `$state<boolean>` that persists the check mark indefinitely during the page lifecycle (serving as a session checklist) but resets automatically when the page is refreshed or fully unmounted. No parent state management needed.

## Implementation Order

1. Build the 5 Guide components (`GuidePanel`, `GuideSection`, `CopyableItem`, `CopyableGroup`, `GuideNote`) + `index.ts`
2. Refactor `ResultSheetTemplates/Create.svelte` and `Edit.svelte` to use the new components (validates reusability, removes ~200 lines of duplication)
3. Add guide to `Applications/Import.svelte` with backend prop changes
4. Add guide to `Grading/Import.svelte` with backend prop changes
5. Add guide to `KnowledgeDocuments/Import.svelte` (no backend changes needed)
6. Run `vendor/bin/pint --dirty --format agent` + tests

## Out of Scope

- localStorage persistence for guide open state (session-only per decision)
- Preview step for Knowledge Document Import (separate feature)
- Switching `course_preference` from IDs to codes (separate refactor, guide anticipates it)
- InfoPopover component (separate concern — tooltips vs reference panels)