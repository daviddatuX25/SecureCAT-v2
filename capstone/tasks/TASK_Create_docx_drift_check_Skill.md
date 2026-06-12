# Task: Create docx-drift-check Skill (Pre-Sync Drift Detection)

## Context

Before running `md-updates-docx`, we must verify the master DOCX on Drive (or local) hasn't been modified externally — by co-authors, manual edits, or sync conflicts. This skill compares the DOCX's current SHA256 against the `docx-sha256` stored in the manuscript MD's META tag.

If drift is detected, the skill blocks the update and reports which sections likely changed, so the user can resolve (accept cloud, keep local, merge, or snapshot cloud version).

## Skill Specification

**Location:** `capstone/manuscript/skills/docx-drift-check/`

**Files to create:**
- `SKILL.md` — skill definition
- `scripts/check_drift.py` — core implementation

### SKILL.md Frontmatter

```yaml
---
name: docx-drift-check
description: Verify master DOCX matches manuscript MD META tag before sync
category: capstone
trigger: automatic (pre-hook for md-updates-docx)
inputs:
  - manuscript_md: path to manuscript/SecureCAT_Ch1_Ch2_Manuscript.md
  - master_docx: path to output/SecureCAT_Ch1_Ch2_Manuscript.docx
outputs:
  - PASS / DRIFT_DETECTED / MISSING_DOCX / NO_META with diff summary
---
```

### Algorithm

1. **Parse META tag from manuscript MD:**
   - Extract `docx-sha256` and `docx-sync-version`
   - If META missing → return `NO_META`

2. **Check master DOCX exists:**
   - If not → return `MISSING_DOCX` with last sync version

3. **Compute current DOCX SHA256:**
   - `hashlib.sha256(Path(docx_path).read_bytes()).hexdigest()`

4. **Compare:**
   - **Match** → return `PASS` with sync timestamp
   - **Mismatch** → return `DRIFT_DETECTED` with:
     - Expected SHA256 (from META)
     - Actual SHA256 (current)
     - Last sync version (from META)
     - Text diff hint: extract DOCX paragraphs, compare with last-known state

5. **Diff hint (best effort):**
   - If git has previous `output/` snapshot → `git diff` for text
   - Else → report "DOCX changed since last sync; sections unknown without cached snapshot"
   - Optional: compare paragraph count, character count as proxy

### Resolution Options (User Decision)

When `DRIFT_DETECTED`, user chooses:

| Option | Action |
|--------|--------|
| `accept-cloud` | Use cloud DOCX as new base: update META tag with current SHA256 + new timestamp, then proceed |
| `keep-local` | Keep local DOCX, overwrite cloud on next push (no META update needed) |
| `merge` | Manual merge — open both, resolve conflicts, then re-run drift check |
| `snapshot-cloud` | Save cloud DOCX to `archive/cloud-snapshots/YYYYMMDD_HHMMSS.docx`, then proceed with local |

## Source Documents to Reference

| Document | Purpose |
|----------|---------|
| `capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md` | Contains META tag with expected SHA256 |
| `capstone/output/SecureCAT_Ch1_Ch2_Manuscript.docx` | Master DOCX to verify |
| `capstone/manuscript/skills/md-updates-docx/scripts/update_docx.py` | Integration point (pre-flight) |

## Deliverable

1. **`capstone/manuscript/skills/docx-drift-check/SKILL.md`** — complete skill definition
2. **`capstone/manuscript/skills/docx-drift-check/scripts/check_drift.py`** — working implementation with:
   - `parse_meta(md_path)` → `meta_dict`
   - `compute_docx_sha256(docx_path)` → hex digest
   - `extract_docx_text(docx_path)` → plain text for diff
   - `compare_with_git_snapshot(docx_path)` → diff hint (if possible)
   - CLI: `--md --docx` args
   - Exit codes: 0=PASS, 1=DRIFT_DETECTED, 2=MISSING_DOCX, 3=NO_META, 4=PARSE_ERROR
3. **Skill loads via `skill_view('docx-drift-check')`** in Hermes
4. **Integration:** `md-updates-docx` skill calls this as pre-flight (or user runs manually)

## Constraints

- **Do not start until explicitly directed**
- Must be fast (< 2 seconds for ~10MB DOCX)
- Must not modify any files — read-only check
- Diff hint is best-effort; acceptable to say "sections unknown without snapshot"
- Output must be machine-parseable (last line = status code) AND human-readable

## Related Tasks (Dependencies)

- **Prerequisite:** TASK_Migrate_Capstone_Folder_Structure.md (manuscript/ folder exists)
- **Enables:** TASK_Create_md_updates_docx_Skill.md (pre-flight for surgical update)
- **Run order:** Always before `md-updates-docx`

## Usage Example

```bash
# Pre-sync check
python capstone/manuscript/skills/docx-drift-check/scripts/check_drift.py \
  --md capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md \
  --docx capstone/output/SecureCAT_Ch1_Ch2_Manuscript.docx

# Output on PASS:
# ✅ PASS: DOCX matches META (synced 2026-06-12T10:30:00Z)

# Output on DRIFT_DETECTED:
# ❌ DRIFT_DETECTED: DOCX has unwatched changes
#    Expected SHA256: a1b2c3d4... (from MD META, synced 2026-06-12T10:30:00Z)
#    Actual SHA256:   e5f6g7h8...
#    Current DOCX length: 45,231 chars (was ~43,000)
#    Sections likely changed: (need git snapshot to pinpoint)
#    Resolution: accept-cloud | keep-local | merge | snapshot-cloud
```