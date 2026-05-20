# Range-Based Conversion Table Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Refactor the conversion table editor in Create and Edit views to manage ranges/single values natively in UI state while preserving flat raw score mapping in the database.

**Architecture:** Maintain a local `uiTable` state variable in Svelte 5. Run a reactive `$effect` to validate ranges and flatten them into `$form.conversion_table` on change. Group raw score arrays from DB back into `uiTable` ranges/singles on mount.

**Tech Stack:** Svelte 5, Inertia v2, Laravel 12.

---

### Task 1: Refactor Admin/AptitudeAreas/Edit.svelte

**Files:**
- Modify: `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte`
- Test: `tests/Feature/Admin/AptitudeAreaConversionTableTest.php`

**Step 1: Set up local uiTable state and bidirectional mapping helpers**

Define `uiTable`, `collapseTable`, and `flattenTable` at the top of the script tag in `Edit.svelte`:
```javascript
  let uiTable = $state([]);
  let uiTableError = $state('');

  function collapseTable(flatRows) {
    if (!flatRows || flatRows.length === 0) return [];
    const sorted = [...flatRows]
      .filter(r => r.raw_score !== '' && r.raw_score !== null && !isNaN(parseInt(r.raw_score, 10)))
      .sort((a, b) => parseInt(a.raw_score, 10) - parseInt(b.raw_score, 10));
    
    const uiRows = [];
    let currentGroup = null;

    for (const row of sorted) {
      const score = parseInt(row.raw_score, 10);
      const pct = row.percentile_output;
      
      if (!currentGroup) {
        currentGroup = { start: score, end: score, percentile: pct };
        continue;
      }
      
      if (score === currentGroup.end + 1 && pct === currentGroup.percentile) {
        currentGroup.end = score;
      } else {
        uiRows.push(currentGroup);
        currentGroup = { start: score, end: score, percentile: pct };
      }
    }
    if (currentGroup) {
      uiRows.push(currentGroup);
    }
    
    return uiRows.map(g => {
      if (g.start === g.end) {
        return { type: 'single', raw_score: g.start, percentile_output: g.percentile };
      } else {
        return { type: 'range', range_start: g.start, range_end: g.end, percentile_output: g.percentile };
      }
    });
  }

  function flattenTable(uiRows) {
    const flat = [];
    for (const row of uiRows) {
      if (row.type === 'single') {
        const val = parseInt(row.raw_score, 10);
        if (!isNaN(val)) {
          flat.push({ raw_score: val, percentile_output: row.percentile_output });
        }
      } else if (row.type === 'range') {
        const start = parseInt(row.range_start, 10);
        const end = parseInt(row.range_end, 10);
        if (!isNaN(start) && !isNaN(end) && start <= end) {
          for (let s = start; s <= end; s++) {
            flat.push({ raw_score: s, percentile_output: row.percentile_output });
          }
        }
      }
    }
    return flat.sort((a, b) => a.raw_score - b.raw_score);
  }
```

Initialize `uiTable` on component load:
```javascript
  $effect(() => {
    untrack(() => {
      uiTable = collapseTable($form.conversion_table);
    });
  });
```

**Step 2: Implement Reactive Validation and Synchronization**

Replace the current `$effect` or add a new one that watches `uiTable` changes:
```javascript
  $effect(() => {
    // Read uiTable reactively
    const rows = uiTable;
    uiTableError = '';

    const seenScores = new Set();
    let hasError = false;

    for (let idx = 0; idx < rows.length; idx++) {
      const row = rows[idx];
      const label = `Row ${idx + 1}`;

      // 1. Check empty values
      if (row.percentile_output === '') {
        uiTableError = `${label}: Percentile output cannot be empty.`;
        hasError = true;
        break;
      }

      if (row.type === 'single') {
        if (row.raw_score === '' || row.raw_score === null || isNaN(parseInt(row.raw_score, 10))) {
          uiTableError = `${label}: Raw score is missing or invalid.`;
          hasError = true;
          break;
        }
        const val = parseInt(row.raw_score, 10);
        if (val < 0) {
          uiTableError = `${label}: Raw score cannot be negative.`;
          hasError = true;
          break;
        }
        if (seenScores.has(val)) {
          uiTableError = `${label}: Raw score ${val} is defined more than once.`;
          hasError = true;
          break;
        }
        seenScores.add(val);
      } else if (row.type === 'range') {
        if (row.range_start === '' || row.range_start === null || isNaN(parseInt(row.range_start, 10))) {
          uiTableError = `${label}: Range start is missing or invalid.`;
          hasError = true;
          break;
        }
        if (row.range_end === '' || row.range_end === null || isNaN(parseInt(row.range_end, 10))) {
          uiTableError = `${label}: Range end is missing or invalid.`;
          hasError = true;
          break;
        }
        const start = parseInt(row.range_start, 10);
        const end = parseInt(row.range_end, 10);
        if (start < 0 || end < 0) {
          uiTableError = `${label}: Range boundaries cannot be negative.`;
          hasError = true;
          break;
        }
        if (start > end) {
          uiTableError = `${label}: Range start (${start}) cannot be greater than range end (${end}).`;
          hasError = true;
          break;
        }
        for (let s = start; s <= end; s++) {
          if (seenScores.has(s)) {
            uiTableError = `${label}: Raw score ${s} in range ${start}-${end} is defined more than once.`;
            hasError = true;
            break;
          }
          seenScores.add(s);
        }
        if (hasError) break;
      }
    }

    untrack(() => {
      if (!hasError) {
        $form.conversion_table = flattenTable(rows);
      } else {
        $form.conversion_table = [];
      }
    });
  });
```

**Step 3: Update copy/paste and row modification actions**

Modify `addRow`, `removeRow`, `updateRow`, `generateRows`, and copy/paste functions to operate on `uiTable`:
```javascript
  function addSingleRow() {
    uiTable = [...uiTable, { type: 'single', raw_score: '', percentile_output: '' }];
  }

  function addRangeRow() {
    uiTable = [...uiTable, { type: 'range', range_start: '', range_end: '', percentile_output: '' }];
  }

  function removeRow(index) {
    uiTable = uiTable.filter((_, i) => i !== index);
  }

  function updateRow(index, field, value) {
    const updated = [...uiTable];
    updated[index] = { ...updated[index], [field]: value };
    uiTable = updated;
  }

  function generateRows() {
    const max = parseInt($form.max_items, 10) || 0;
    if (max <= 0) return;
    uiTable = [{ type: 'range', range_start: 0, range_end: max, percentile_output: '' }];
  }

  function copyTable() {
    if (uiTable.length === 0) {
      success('The conversion table is empty.');
      return;
    }
    const text = uiTable.map(r => {
      if (r.type === 'single') {
        return `${r.raw_score}\t${r.percentile_output}`;
      } else {
        return `${r.range_start}-${r.range_end}\t${r.percentile_output}`;
      }
    }).join('\n');
    navigator.clipboard.writeText(text);
    success('Copied conversion table to clipboard.');
  }

  function applyPaste() {
    pasteError = '';
    if (!pasteText.trim()) {
      pasteError = 'Paste area is empty.';
      return;
    }
    const lines = pasteText.trim().split(/\r?\n/).filter(l => l.trim());
    const parsedRows = [];
    for (const line of lines) {
      let separatorIndex = line.search(/[\t:=]|\s{2,}/);
      let scorePart = '';
      let out = '';
      
      if (separatorIndex !== -1) {
        scorePart = line.substring(0, separatorIndex).trim();
        const matchedDelim = line.match(/[\t:=]|\s{2,}/);
        const delimLength = matchedDelim ? matchedDelim[0].length : 1;
        out = line.substring(separatorIndex + delimLength).trim();
      } else {
        const spaceIndex = line.indexOf(' ');
        if (spaceIndex !== -1) {
          scorePart = line.substring(0, spaceIndex).trim();
          out = line.substring(spaceIndex + 1).trim();
        } else {
          continue;
        }
      }

      if (scorePart && out) {
        const rangeMatch = scorePart.match(/^(\d+)\s*(?:-|_|~|\/|to)\s*(\d+)$/i);
        if (rangeMatch) {
          const start = parseInt(rangeMatch[1], 10);
          const end = parseInt(rangeMatch[2], 10);
          if (!isNaN(start) && !isNaN(end) && start <= end) {
            parsedRows.push({ type: 'range', range_start: start, range_end: end, percentile_output: out });
          }
        } else {
          const raw = parseInt(scorePart, 10);
          if (!isNaN(raw)) {
            parsedRows.push({ type: 'single', raw_score: raw, percentile_output: out });
          }
        }
      }
    }
    if (parsedRows.length === 0) {
      pasteError = 'No valid rows found. Supported format:\n- 0-10 [tab] 85th\n- 15: 90th';
      return;
    }
    uiTable = [...uiTable, ...parsedRows];
    closePaste();
  }
```

**Step 4: Update UI Template**

Modify the conversion table panel to conditionally render input fields based on `row.type`, show "Add Single Value" and "Add Range" buttons, and display `uiTableError`.

**Step 5: Run tests to verify**

Run: `php artisan test --filter=ConversionTable`
Expected: PASS

---

### Task 2: Refactor Admin/AptitudeAreas/Create.svelte

Apply the exact same Svelte scripts, computed validators, UI buttons, inputs, and paste parsing logic to `Create.svelte`.

**Step 1: Implement same scripts and helpers in Create.svelte**

**Step 2: Update UI elements**

**Step 3: Run tests to verify**

Run: `php artisan test --filter=ConversionTable`
Expected: PASS
