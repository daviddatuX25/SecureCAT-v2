# Plan: Migrate Raw HTML Form Elements to shadcn Components

**Date:** 2026-05-15
**Status:** Completed
**Priority:** P2 — UI consistency & dark mode readability

## Problem

Multiple pages use raw `<select>`, `<textarea>`, and `<input type="checkbox|date">` elements with hand-crafted Tailwind classes (`bg-transparent`, `bg-background`, `border-input`). These:

1. **Break dark mode** — native `<select>` dropdowns render with system colors, not theme colors; `bg-transparent` on `<textarea>` makes text hard to read
2. **Inconsistent styling** — hand-crafted classes diverge from the shadcn component defaults (shadow, focus ring, ring-offset, etc.)
3. **Arrow positioning** — native `<select>` arrows can't be styled properly

## Existing shadcn Components

| Component | Path | Used By |
|-----------|------|---------|
| `Select` | `ui/select/` | Release/Index.svelte, ResultSheetTemplates/{Create,Edit}.svelte |
| `Input` | `ui/input/` | Widely used |
| `Textarea` | `ui/textarea/` | Only used via `input-group-textarea` internally |
| `Switch` | `ui/switch/` | ResultSheetTemplates/{Create,Edit}.svelte |
| `ToggleGroup` | `ui/toggle-group/` | ResultSheetTemplates/{Create,Edit}.svelte |
| `Popover`+`Command` | `ui/popover/`, `ui/command/` | Release/Index.svelte |

**Missing (needs install):**
- `Checkbox` — not yet in the project

## Migration Targets

### Phase 1: `<select>` → shadcn `Select` (23 instances, 5 files)

| # | File | Elements | Notes |
|---|------|----------|-------|
| 1 | `Portal/ApplicationEdit.svelte` | sex, course_pref 1/2/3 (4) | Portal-facing, high priority |
| 2 | `Admin/Applications/Edit.svelte` | sex, course_pref 1/2/3, status (5) | Admin CRUD |
| 3 | `Admin/Applications/Create.svelte` | sex, course_pref 1/2/3, status (5) | Admin CRUD |
| 4 | `Applications/Apply.svelte` | sex, course_pref 1/2/3 (4) | Public-facing, high priority |
| 5 | `Applications/Index.svelte` | filter status/pipeline/season (3 desktop + 3 mobile) | Toolbar filters |

Course preference selects benefit most — they have many options and the shadcn Select provides a scrollable dropdown with check indicators.

### Phase 2: `<textarea>` → shadcn `Textarea` (13 instances, 8 files)

| # | File | Count | Notes |
|---|------|-------|-------|
| 1 | `Admin/Applications/Edit.svelte` | 1 | rejection_reason textarea |
| 2 | `Admin/AptitudeAreas/Create.svelte` | 2 | name, description |
| 3 | `Admin/AptitudeAreas/Edit.svelte` | 2 | name, description |
| 4 | `Admin/AiCompanion/Index.svelte` | 1 | prompt textarea |
| 5 | `Admin/KnowledgeDocuments/Create.svelte` | 1 | content |
| 6 | `Admin/KnowledgeDocuments/Edit.svelte` | 1 | content |
| 7 | `Admin/ResultSheetTemplates/Create.svelte` | 1 | HTML content (font-mono, keep custom styling) |
| 8 | `Admin/ResultSheetTemplates/Edit.svelte` | 1 | HTML content (font-mono, keep custom styling) |
| 9 | `Release/Index.svelte` | 1 | counselor comments |
| 10 | `Applications/Show.svelte` | 1 | notes textarea |
| 11 | `AiCompanionChatWidget.svelte` | 1 | chat input |
| 12 | `Portal/AiCompanion.svelte` | 1 | chat input |
| 13 | `ScheduleAssistantPanel.svelte` | 1 | chat input |

Most of these use `bg-background` or `bg-transparent` with manual `focus-visible:ring` classes. Replacing with `Textarea` component gives consistent dark mode, focus rings, and disabled states automatically.

**Special cases:** ResultSheetTemplates HTML content textareas use `font-mono` — pass `class="font-mono"` to the `Textarea` component.

### Phase 3: `<input type="checkbox">` → `Switch` or `Checkbox` (4 instances, 2 files)

| # | File | Current | Replace With |
|---|------|---------|---------------|
| 1 | `Admin/AptitudeAreas/Create.svelte` | raw checkbox `is_active` | `Switch` (matches ResultSheetTemplate pattern) |
| 2 | `Admin/AptitudeAreas/Edit.svelte` | raw checkbox `is_active` | `Switch` |
| 3 | `Grading/ImportPreview.svelte` | raw checkbox select-all | Add `Checkbox` component, or keep raw (bulk-action pattern) |
| 4 | `Grading/ImportPreview.svelte` | raw checkbox per-row | Same as above |

**Recommendation:** Replace `is_active` checkboxes with `Switch` for consistency. For ImportPreview's bulk-selection checkboxes, either install shadcn `Checkbox` or leave as raw (they're internal admin, low priority).

### Phase 4: `<input type="date">` → `Input type="date"` (4 instances, 1 file)

| # | File | Current | Fix |
|---|------|---------|-----|
| 1 | `Applications/Index.svelte` | raw `<input type="date">` (4) | Replace with `<Input type="date" />` |

These already have `bg-background` which works in dark mode for the input itself, but the native date picker calendar may not theme well. The `Input` component adds proper focus rings and ring-offset handling. Low priority since date pickers are system-native.

### Phase 5: Standalone raw `<input>` with manual classes (8+ instances, 5 files)

| # | File | Current | Fix |
|---|------|---------|-----|
| 1 | `AdmissionSlipBulk.svelte` | 2 raw inputs with `bg-transparent` | Replace with `Input` |
| 2 | `ResultSheetBulk.svelte` | 2 raw inputs with `bg-transparent` | Replace with `Input` |
| 3 | `Admin/TestScheduling/Index.svelte` | 6 raw inputs with `bg-background` | Replace with `Input` |
| 4 | `Admin/Logs/Index.svelte` | 7 raw inputs with `bg-background` | Replace with `Input` |
| 5 | `Admin/Users/Index.svelte` | 3 raw inputs with `bg-background` | Replace with `Input` |
| 6 | `Admin/DirectAssessment/Create.svelte` | 1 raw input | Replace with `Input` |
| 7 | `Admin/Applications/Import.svelte` | 1 raw input | Replace with `Input` |

These all use manual `rounded-md border border-input bg-background px-3 py-2 text-sm` — exactly what `Input` provides by default. Just replace with `<Input />`.

## Implementation Order

1. **Phase 1** — Select migration (biggest dark-mode impact, 23 instances)
2. **Phase 5** — Raw Input replacement (easiest, low risk)
3. **Phase 2** — Textarea replacement (moderate effort)
4. **Phase 3** — Checkbox → Switch (small scope)
5. **Phase 4** — Date inputs (lowest priority, native widget)

## Testing Checklist

- [ ] Dark mode: all dropdowns, text areas, and inputs are readable
- [ ] Light mode: no regressions
- [ ] Form submissions still work (Inertia `$form` bindings intact)
- [ ] Course preference dropdowns show all options
- [ ] Filter dropdowns on Applications/Index work
- [ ] Mobile layouts still render correctly (min-h-[44px] touch targets)

## Notes

- Already completed: `ResultSheetTemplates/Create.svelte` and `Edit.svelte` — Layout select migrated to shadcn Select
- The shadcn `Select.Trigger` uses `dark:bg-input/30 dark:hover:bg-input/50` which properly handles dark mode
- For course preference selects with many options, the `Select` component's scrollable dropdown with check indicators is a UX improvement over native `<select>`