# Design: Range-Based Conversion Table Frontend

This design moves from a raw list of individual raw scores in the frontend UI to a clean, range-based representation. It allows administrators to add, edit, copy, and paste ranges directly, while transparently flattening the data into individual raw scores when submitting to the backend (for compatibility with the grading engine's exact lookups).

## 1. Key Architecture & Data Structures

### UI Representation (`uiTable`)
An array of objects representing either a single raw score or a range of raw scores:
- **Single Score**:
  ```json
  { "type": "single", "raw_score": 15, "percentile_output": "90th" }
  ```
- **Range of Scores**:
  ```json
  { "type": "range", "range_start": 0, "range_end": 14, "percentile_output": "85th" }
  ```

### Backend / DB Representation (`$form.conversion_table`)
An array of objects representing individual raw score mappings:
```json
[
  { "raw_score": 0, "percentile_output": "85th" },
  { "raw_score": 1, "percentile_output": "85th" },
  ...
  { "raw_score": 14, "percentile_output": "85th" },
  { "raw_score": 15, "percentile_output": "90th" }
]
```

---

## 2. Bidirectional Mapping Algorithms

### A. Flattening / Expansion (`uiTable` -> `$form.conversion_table`)
When the form is submitted (or whenever the `uiTable` is modified and validated), we expand the ranges:
```javascript
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
  // Sort by raw score ascending
  return flat.sort((a, b) => a.raw_score - b.raw_score);
}
```

### B. Grouping / Collapsing (`$form.conversion_table` -> `uiTable`)
On component load, we group sequential raw scores sharing the same percentile output:
```javascript
function collapseTable(flatRows) {
  if (!flatRows || flatRows.length === 0) return [];
  
  // Sort ascending by raw score to guarantee sequence detection
  const sorted = [...flatRows].sort((a, b) => a.raw_score - b.raw_score);
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
      // Push previous group
      uiRows.push(currentGroup);
      currentGroup = { start: score, end: score, percentile: pct };
    }
  }
  if (currentGroup) {
    uiRows.push(currentGroup);
  }
  
  // Convert groups to UI format
  return g => {
    if (g.start === g.end) {
      return { type: 'single', raw_score: g.start, percentile_output: g.percentile };
    } else {
      return { type: 'range', range_start: g.start, range_end: g.end, percentile_output: g.percentile };
    }
  };
}
```

---

## 3. UI Component Updates

We will add two distinct add buttons:
1. **Add Single Value**: Appends `{ type: 'single', raw_score: '', percentile_output: '' }`
2. **Add Range**: Appends `{ type: 'range', range_start: '', range_end: '', percentile_output: '' }`

The table row elements will conditionally render based on the row type:
- If `type === 'single'`: Shows a single input for `raw_score` and `percentile_output`.
- If `type === 'range'`: Shows two inputs for `range_start` and `range_end` separated by a dash, and a `percentile_output`.

---

## 4. Frontend Validation & Error Handling

We validate `uiTable` reactively whenever it changes:
1. **Missing values**: Ensure all entered raw scores/percentile outputs are not empty.
2. **Negative values**: Ensure raw scores are greater than or equal to 0.
3. **Range boundaries**: In range rows, verify `range_start <= range_end`.
4. **Overlaps**: Build a Set of all raw scores generated. If a raw score is mapped more than once, trigger an overlap error.
   - For a single row, the score is `raw_score`.
   - For a range row, the scores are from `range_start` to `range_end` inclusive.
   - If an overlap is detected, flag the error (e.g. "Score X is mapped to multiple ranges/values") and block form submission.

---

## 5. Copy/Paste Support

- **Copy**: Serializes `uiTable` directly. Range rows are copied as `range_start-range_end: percentile_output`. Single rows as `raw_score: percentile_output`.
- **Paste**: Uses the same robust regex to find ranges or single values, and appends them as the appropriate row type in `uiTable` instead of immediate raw score generation. Duplicate keys in paste overwrite existing entries.
