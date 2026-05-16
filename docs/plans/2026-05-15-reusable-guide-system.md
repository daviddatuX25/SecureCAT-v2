# Reusable Guide System — Implementation Plan

**Date:** 2026-05-15
**Spec:** `docs/superpowers/specs/2026-05-15-reusable-guide-system-design.md`

## Phase 1: Build Guide Component Library

Create all 5 components + barrel export + types.

### 1.1 Types & Barrel Export — `resources/js/Components/Guide/index.ts`

- [ ] Create `index.ts` with `CopyableItemData` interface and re-exports
- [ ] Export: `GuidePanel`, `GuideSection`, `CopyableItem`, `CopyableGroup`, `GuideNote`

```ts
export interface CopyableItemData {
  value: string;
  label?: string;
}

export { default as GuidePanel } from './GuidePanel.svelte';
export { default as GuideSection } from './GuideSection.svelte';
export { default as CopyableItem } from './CopyableItem.svelte';
export { default as CopyableGroup } from './CopyableGroup.svelte';
export { default as GuideNote } from './GuideNote.svelte';
```

### 1.2 GuidePanel.svelte

- [ ] Create `GuidePanel.svelte`
- Props: `title: string`, `icon?: Component` (default `HelpCircle`), `defaultOpen?: boolean` (default `false`), `class?: string`
- State: `open = $state(defaultOpen)`
- A11y: `aria-expanded={open}`, `aria-controls={panelId}`, content region with `id={panelId}` and `role="region"`
- Transition: `{#if open}` with `slide={{ duration: 200 }}`
- Header: icon + title + chevron toggle button
- Slot: default slot for content

### 1.3 GuideSection.svelte

- [ ] Create `GuideSection.svelte`
- Props: `title: string`, `visible?: boolean` (default `undefined`), `class?: string`
- Conditional render: `{#if visible !== false}` — pass `undefined` to always show, `false` to hide
- Small heading with separator/border styling
- Default slot for content

### 1.4 CopyableItem.svelte

- [ ] Create `CopyableItem.svelte`
- Props: `value: string`, `label?: string`, `class?: string`
- Computed label: strip `{{`/`}}`, replace `_2` suffix with ` (Applicant 2)`, replace `_` with space
- State: `copied = $state(false)`
- Clipboard logic (browser-guarded):
  1. Try `navigator.clipboard.writeText(value)`
  2. Fallback: create hidden textarea, select, `document.execCommand('copy')`, remove textarea
  3. On success: set `copied = true` and call `success()` toast
  4. On failure: call `error()` toast with fallback message
  5. Cleanup textarea in `finally` block
- A11y: `aria-label="Copy {label}"`, `role="button"`, focus-visible ring
- Icon: `Copy` normally, `Check` and text turns green/primary when `copied === true` (persists until page refresh)

### 1.5 CopyableGroup.svelte

- [ ] Create `CopyableGroup.svelte`
- Props: `items: CopyableItemData[]`, `subtitle?: string`, `class?: string`
- A11y: `role="list"` on container, each `CopyableItem` gets `role="listitem"`
- Render: optional subtitle `<p>` above flex-wrap row of `CopyableItem`s with `gap-2`

### 1.6 GuideNote.svelte

- [ ] Create `GuideNote.svelte`
- Props: `variant: 'info' | 'warning' | 'tip'`, `title?: string`, `class?: string`
- Variant styles (left border accent):
  - `info` → `border-blue-400`, `Info` icon
  - `warning` → `border-amber-400`, `AlertTriangle` icon
  - `tip` → `border-green-400`, `Lightbulb` icon`
- Render: icon + optional title + default slot (supports `{@html}`)
- Styling: `rounded-lg bg-muted/50 p-3 text-sm`

---

## Phase 2: Migrate ResultSheetTemplates Create + Edit

Refactor the existing inline guide to use new components. Functionally identical output.

### 2.1 ResultSheetTemplates/Create.svelte

- [ ] Replace inline guide markup with `GuidePanel` + `GuideSection` + `CopyableGroup` composition
- [ ] Remove `copiedKeys` state and `copyPlaceholder()` function entirely
- [ ] Remove `helpOpen` state (now inside `GuidePanel`)
- [ ] Remove `{#if helpOpen}` block (now inside `GuidePanel`)
- [ ] Build `applicant1Placeholders`, `applicant2Placeholders`, `domainItems` as `CopyableItemData[]`
- [ ] Build `htmlTemplateRules` and `docxPlaceholderNote` as `GuideNote` blocks
- [ ] Verify: visually identical to before (chevron toggle, pills, sections)

### 2.2 ResultSheetTemplates/Edit.svelte

- [ ] Same changes as Create — the guide section is nearly identical
- [ ] Remove all duplicated guide code
- [ ] Verify both pages render correctly

---

## Phase 3: Applicant Import Guide

Add guide to `Applications/Import.svelte` with backend prop changes.

### 3.1 Backend — ApplicationImportController (or equivalent)

- [ ] Locate the controller method that serves the import form (likely `ImportController` or `ApplicationController`)
- [ ] Add Inertia props: `courses` (active courses with `code` + `name`), `requiredColumns` (from `ApplicantImportService::REQUIRED_COLUMNS`), `optionalColumns` (from `ApplicantImportService::OPTIONAL_COLUMNS`)
- [ ] Verify the route passes these props correctly

### 3.2 Frontend — Applications/Import.svelte

- [ ] Import `GuidePanel`, `GuideSection`, `CopyableGroup`, `GuideNote` from `@/Components/Guide`
- [ ] Compute `requiredColumns` items: `requiredColumns.map(c => ({ value: c, label: c }))`
- [ ] Compute `optionalColumns` items: `optionalColumns.map(c => ({ value: c, label: c }))`
- [ ] Compute `courseItems`: `courses.map(c => ({ value: c.code, label: '${c.code} — ${c.name}' }))`
- [ ] Add guide panel markup per spec section 2
- [ ] Verify: guide renders, copy buttons work, sections display correctly

---

## Phase 4: Score Import Guide

Add guide to `Grading/Import.svelte` with backend prop changes.

### 4.1 Backend — GradingImportController (or equivalent)

- [ ] Verify `aptitudeAreaCodes` prop is already passed (confirmed: it is)
- [ ] Verify `enableNormalizedScores` prop is already passed (confirmed: it is)
- [ ] No additional backend changes needed — existing props are sufficient

### 4.2 Frontend — Grading/Import.svelte

- [ ] Import guide components from `@/Components/Guide`
- [ ] Compute `aptitudeAreaItems`: `aptitudeAreaCodes.map(code => ({ value: code, label: code }))`
- [ ] Compute `scoreColumnSubtitle` based on `enableNormalizedScores`
- [ ] Add guide panel markup per spec section 3
- [ ] Verify: guide renders with dynamic score columns and normalized/raw context

---

## Phase 5: Knowledge Document Import Guide

Add guide to `KnowledgeDocuments/Import.svelte`. No backend changes needed.

### 5.1 Frontend — KnowledgeDocuments/Import.svelte

- [ ] Import guide components from `@/Components/Guide`
- [ ] Add guide panel markup per spec section 4 (no copyable items — purely informational)
- [ ] Verify: guide renders correctly, no copy buttons needed

---

## Phase 6: Polish & Verify

- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Run `npm run build` (or `npm run dev`) — verify no build errors
- [ ] Visual check all 5 pages (Create, Edit, Applicant Import, Score Import, Knowledge Import)
- [ ] Test copy-to-clipboard on each page
- [ ] Test keyboard navigation (Tab into guide, Enter to open/close, Tab to copy buttons)
- [ ] Test clipboard fallback (simulate `navigator.clipboard` unavailable)
- [ ] Verify `onDestroy` cleanup — no stale timeouts after navigation