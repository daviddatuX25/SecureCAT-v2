# Release Page Print Restructure

## Current State

- **Release Index page** shows individual "Print batch Session #X" buttons — one per finalized grading session, stacked in the header toolbar
- **PrintBatch page** (`/admin/release/print/{gs}`) has breadcrumb: `Release > Session #X > Print Batch`
- **ResultSheet page** (`/admin/release/print/{gs}/applicants/{applicant}`) has manual "Back to print batch" link, no breadcrumb trail
- **ResultSheetBulk page** (`/admin/release/print/{gs}/print-bulk`) has manual "Back to print batch" link, no breadcrumb trail
- Both ResultSheet and ResultSheetBulk pages escape the admin layout context — they feel disconnected

---

## Desired Changes

### 1. Replace per-session print buttons with "Print by Exam Session" popover

**Before:** Multiple `<Button>` elements, one per grading session, rendered directly on the Release Index toolbar:
```
[Print batch Session #1] [Print batch Session #2] [Print batch Session #3]
```

**After:** Single "Print by Exam Session" button that opens a **Popover with a Command list** (not a full Dialog/Modal) listing available grading sessions. User picks one session, then navigates to that session's print batch page.

- Removes visual clutter when many sessions exist
- Groups print actions under one UI affordance
- The popover shows: session ID, exam date, room — enough context to pick the right one
- **Why Popover over Dialog:** Session data is a simple list (ID, date, room). A searchable Popover is fewer clicks than a full Dialog — users scan and select without a modal interruption.

### 2. Wrap print pages inside the admin layout

**Before:** ResultSheet and ResultSheetBulk pages render with their own minimal layout — no sidebar, no admin navigation.

**After:** All print pages (PrintBatch, ResultSheet, ResultSheetBulk) render inside `AuthenticatedLayout` — same sidebar, same top nav as the rest of the admin area.

- The printable content area uses `@media print` rules to hide navigation during actual printing
- Navigation is visible on-screen so users don't feel "lost" in a bare page
- **Must add `@media print` CSS** in AuthenticatedLayout (or a global stylesheet) to hide `<nav>`, `<aside>` (sidebar), and breadcrumb areas during print. Currently only the ResultSheetBulk toolbar has `print:hidden` — no global print styles exist for the layout chrome.

### 3. Fix breadcrumbs on ResultSheet and ResultSheetBulk pages

**Before:** Manual "← Back to print batch" link, no breadcrumb trail.

**After:** Proper breadcrumb trail that reflects the navigation flow:
- If arrived via session flow: `Release > Session #X > Print` (shows which session context you're in)
- If arrived via bulk selection: `Release > Print` (session-agnostic, since the user selected across sessions)

**Important:** Do NOT remove the "Back to print batch" link entirely. Since `PrintBatch.svelte` opens result sheets with `target="_blank"` (line 160), users land in an orphaned tab with no browser history. Breadcrumb items must be **clickable links** — specifically the "Session #X" item should link to `/admin/release/print/{gs}` so users can navigate out of the orphaned tab.

### 4. Add "Bulk Print" button on Release Index page (session-agnostic)

**Before:** No way to print applicants across sessions from the Release Index page. Only per-session print batch was available.

**After:** A "Print Selected" button on the Release Index page toolbar. This button:
- Is **disabled** when 0 applicants are selected
- **Activates** when 1+ applicants are selected (via the existing row selection checkboxes)
- Shows count: "Print Selected (3)" when applicants are selected
- On click, navigates to the bulk print page with the selected applicant IDs

**Why 1+ instead of 2+:** A single-applicant bulk print is useful — it's the same result as clicking "Result sheet" per-row, just from the index page. There's no reason to block this at 2.

This is separate from the "Print by Exam Session" flow. The bulk print page reached this way shows breadcrumb: `Release > Print` (no session segment).

---

## Navigation Flows (after changes)

### Flow A: Print by Exam Session (session-scoped)

```
Release Index
  → Click "Print by Exam Session" button
    → Popover opens with session list
    → User picks Session #3
  → PrintBatch page (/admin/release/print/3)
    Breadcrumb: Release > Session #3 > Print (Session #3 is a clickable link)
    → Click individual applicant
      → ResultSheet page (/admin/release/print/3/applicants/7) [opens in new tab]
        Breadcrumb: Release > Session #3 > Print (links back to print batch)
    → Click "Print selected"
      → ResultSheetBulk page (/admin/release/print/3/print-bulk?ids=...)
        Breadcrumb: Release > Session #3 > Print
```

### Flow B: Bulk Print (session-agnostic, from release index)

```
Release Index
  → Select 1+ applicants via checkboxes
  → Click "Print Selected (N)" button (disabled when 0 selected)
  → ResultSheetBulk page (/admin/release/print/bulk?ids=...)
    Breadcrumb: Release > Print
```

**Key distinction:** Flow A always carries a `{grading_session}` segment in the URL. Flow B does not — it's a new route that takes applicant IDs directly without requiring a session context.

---

## Route Ordering Note

The new `GET /admin/release/print/bulk` route **must** be registered before the `{grading_session}` catch-all. Laravel matches routes top-down, so `bulk` would be captured by `{grading_session}` if not explicitly placed first:

```php
Route::prefix('print')->name('print.')->group(function () {
    Route::get('bulk', [ReleasePrintController::class, 'printBulkAgnostic'])->name('bulk');
    Route::get('{grading_session}', [ReleasePrintController::class, 'index'])->name('index');
    Route::post('{grading_session}/mark-printed', [ReleasePrintController::class, 'markPrinted'])->name('mark-printed');
    Route::get('{grading_session}/applicants/{applicant}', [ReleasePrintController::class, 'resultSheet'])->name('result-sheet');
    Route::get('{grading_session}/print-bulk', [ReleasePrintController::class, 'printBulk'])->name('print-bulk');
});
```

---

## Session Picker Data

The `ReleaseController::index()` currently passes:
```php
'gradingSessions' => GradingSession::where('status', 'finalized')
    ->get()->map(fn ($s) => ['id' => $s->id, 'label' => 'Session #'.$s->id])
```

This only has `id` and `label`. The session picker popover needs to show exam date and room for context. Update to:
```php
'gradingSessions' => GradingSession::where('status', GradingSession::STATUS_FINALIZED)
    ->with('examSession.room')
    ->get()
    ->map(fn ($s) => [
        'id' => $s->id,
        'label' => 'Session #'.$s->id,
        'exam_date' => $s->examSession?->date?->format('M j, Y'),
        'room_name' => $s->examSession?->room?->name ?? '—',
    ])
    ->values()->all(),
```

---

## Implementation Checklist

- [ ] Replace per-session `Print batch` buttons with single "Print by Exam Session" button + Popover (use shadcn Command in Popover pattern)
- [ ] Update `ReleaseController::index()` to eager-load `examSession.room` on grading sessions and pass `exam_date` + `room_name`
- [ ] Add "Print Selected (N)" button on Release Index (disabled when 0 selected; N=1 opens single sheet, N>1 opens bulk)
- [ ] Add new route: `GET /admin/release/print/bulk?ids=...` (session-agnostic bulk print) — **must be registered before `{grading_session}` wildcard**
- [ ] Wrap ResultSheet and ResultSheetBulk in AuthenticatedLayout
- [ ] Add `@media print` CSS to AuthenticatedLayout to hide sidebar, topbar, and breadcrumbs during printing
- [ ] Verify ResultSheet and ResultSheetBulk print output is clean (no layout chrome in printed page)
- [ ] Replace "Back to print batch" links with proper breadcrumbs where breadcrumb items are clickable links
- [ ] Update PrintBatch breadcrumb: `Release > Session #X > Print`
- [ ] Update ResultSheet breadcrumb: `Release > Session #X > Print` (Session #X links to `/admin/release/print/{gs}`)
- [ ] Update ResultSheetBulk breadcrumb: `Release > Session #X > Print` (session-scoped) or `Release > Print` (session-agnostic)
- [ ] Keep `target="_blank"` on individual result sheet links from PrintBatch, since they're print views