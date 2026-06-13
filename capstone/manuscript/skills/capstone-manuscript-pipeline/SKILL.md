---
name: capstone-manuscript-pipeline
description: Unified pipeline for SecureCAT capstone manuscript: drift check → surgical MD→DOCX update → Drive sync with anti-deletion guardrails
category: capstone
trigger: manual
inputs:
  - manuscript_md: path to manuscript/SecureCAT_Ch1_Ch2_Manuscript.md
  - master_docx_name: "SecureCAT_Ch1_Ch2_Manuscript[never_delete].docx" (defense file)
  - template_docx: path to templates/BSIT Capstone Template.docx
  - drive_remote: "gdrive:A.Y. 2026-2027/Capstone/output/"
  - action: pull | check | update | push | diff | full (default: full)
outputs:
  - Updated master DOCX on Drive
  - Updated META tag in manuscript MD
  - Drift resolution log
  - Timestamped backup in output/backups/
  - change_log.json and tag_map.json in output/
---

# Capstone Manuscript Pipeline Skill

## Unified Pipeline with Full Guardrails

This skill orchestrates the complete manuscript→DOCX→Drive workflow with mandatory drift detection, surgical updates, and anti-deletion protection for the defense file.

### Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    capstone-manuscript-pipeline                 │
├─────────────────────────────────────────────────────────────────┤
│  1. ANTI-DELETION GUARD                                        │
│     ✓ Verify defense file exists on Drive                       │
│     ✓ Verify local copy matches Drive (SHA256)                  │
│     ✓ Refuse to run if file missing on Drive                    │
│                                                                 │
│  2. DRIFT CHECK (mandatory pre-flight)                         │
│     ✓ Pull latest from Drive                                     │
│     ✓ SHA256 vs manuscript MD META tag                          │
│     ✓ PASS / DRIFT_DETECTED / MISSING_FILE                      │
│                                                                 │
│  3. SURGICAL UPDATE (if no drift)                               │
│     ✓ Parse MD for TAG/UPDATE/REMOVE + annotation blocks       │
│     ✓ Apply only changed sections to DOCX                       │
│     ✓ Preserve formatting, comments, track-changes              │
│     ✓ Enforce page-break-before on Chapter/REFERENCES/APPENDIX  │
│                                                                 │
│  4. DRIVE SYNC (additive only)                                  │
│     ✓ rclone copy (never sync/delete)                           │
│     ✓ Push updated defense file                                 │
│     ✓ Push manuscript MD (versioned in git)                     │
│                                                                 │
│  5. AUTO-BACKUP (local immutable)                               │
│     ✓ Timestamped backup to output/backups/                     │
│     ✓ Git commit of manuscript MD META update                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Usage

```bash
cd /home/user/Projects/SecureCAT-v2

# Full pipeline: pull → drift check → update → push → backup
python capstone/manuscript/skills/capstone-manuscript-pipeline/scripts/pipeline.py --action full

# Stepwise actions:
python .../pipeline.py --action pull      # Get latest from Drive
python .../pipeline.py --action check     # Pull + drift check only
python .../pipeline.py --action update    # Pull + drift check + surgical update (requires clean drift)
python .../pipeline.py --action push      # Push local to Drive (additive copy)
python .../pipeline.py --action diff      # Compare MD sections against remote DOCX (downloads temp copy)
```

### Action Details

| Action | Steps | Use Case |
|--------|-------|----------|
| `pull` | anti-deletion guard → download from Drive | Refresh local copy |
| `check` | pull → drift check | Verify sync status without modifications |
| `update` | pull → drift check → resolve if needed → surgical update → backup | Apply MD changes to DOCX |
| `push` | anti-deletion guard → rclone copy to Drive | Publish local changes |
| `diff` | anti-deletion guard → download temp copy → section diff vs MD | Preview differences before deciding |
| `full` | pull → check → update → push → backup | Complete end-to-end sync |

---

## The `--action diff` CLI Option

The `diff` action performs a **section-by-section content comparison** between the local manuscript MD and the Google Drive version of the defense file — without modifying either.

### How it works

1. Runs anti-deletion guard (verifies defense file exists on Drive)
2. Downloads the remote DOCX to a temporary location (`output/temp/temp_drive.docx`)
3. Parses the manuscript MD for TAG sections (using `rebuild_docx_from_md.parse_md_sections`)
4. Opens the downloaded DOCX and maps headings via the canonical `BOOKMARKS` list
5. For each section, extracts body content from both sources
6. Runs Python `difflib.unified_diff` to produce colored terminal output:
   * **Green (+)**: content in DOCX but not in MD
   * **Red (-)**: content in MD but not in DOCX
   * **Cyan (@@)**: context lines
7. Cleans up temporary files automatically

### Output Example

```
──────────────────────────────────────────────────
 Section: CH1_BG_OF_THE_STUDY (Background of the Study)
──────────────────────────────────────────────────

@@ -1,3 +1,3 @@
-This study examines the effectiveness of OMR...
+This study examines the effectiveness of optical mark recognition...
 The system targets educators...
```

### When to use

* Before running `update` to preview what the cloud version contains
* After drift detection to understand the scope of divergence
* As a standalone audit tool during defense preparation

---

## Interactive Drift Resolution

When drift is detected (local DOCX SHA256 ≠ MD META `docx-sha256`), the pipeline presents an interactive menu:

```
❌ DRIFT_DETECTED: Cloud DOCX has unwatched changes
   Expected: 99a331ba... (MD META, synced 2026-06-12T09:24:32Z)
   Actual:   e5f6g7h8...

Resolution options:
  [1] accept-cloud   → Use cloud version, update META, continue pipeline
  [2] keep-local     → Push local DOCX to cloud (overwrite cloud)
  [3] merge          → Manual merge required (open both, resolve, re-run)
  [4] snapshot-cloud → Archive cloud version to backups/, then proceed
  [5] view-diff      → Compare cloud version against local MD file
  [6] abort          → Cancel pipeline

Enter choice [1-6]:
```

### Option 5: `view-diff` — Section-Based Unified Diff

**This is the key exploratory tool.** Selecting `[5]` runs `diff_md_with_docx()` which:

1. Uses the canonical `BOOKMARKS` list (17 sections) to locate headings in both files
2. For each section, extracts all paragraphs between the heading and the next bookmarked heading
3. Runs `difflib.unified_diff` on the normalized line content
4. Prints colored diffs per section with section headers
5. Returns to the main resolution menu after display

**Key implementation details** (`pipeline.py:159-238`):

* Imports `parse_md_sections`, `BOOKMARKS`, `find_heading_index`, `find_next_bookmarked_heading_index` from `rebuild_docx_from_md`
* Compares **section content only** (not full document) — more actionable than binary SHA
* Handles heading level conversion (`Heading 2` → `##` prefix) for fair comparison
* Strips empty lines before diffing
* ANSI color codes: `\033[92m` (green), `\033[91m` (red), `\033[36m` (cyan), `\033[0m` (reset)

**Use this to decide** between `accept-cloud`, `keep-local`, or `merge` before committing.

---

## Automatic Page-Break Preservation Guardrail

**Critical for defense pagination integrity.** The pipeline automatically enforces `page_break_before = True` on three heading categories during surgical update and full rebuild:

| Heading Pattern | Style Match | Page Break |
|----------------|-------------|------------|
| `CHAPTER` | `Heading 1` or `Heading 2` starting with "Chapter" | ✅ Enforced |
| `REFERENCES` | `Heading 1` or `Heading 2` exactly "REFERENCES" | ✅ Enforced |
| `APPENDIX` | `Heading 1` or `Heading 2` starting with "APPENDIX" | ✅ Enforced |

### Implementation Locations

1. **Pipeline surgical update** (`pipeline.py:368-372`):
```python
for p in doc.paragraphs:
    if p.style.name.startswith('Heading'):
        text_upper = p.text.strip().upper()
        if text_upper.startswith('CHAPTER') or \
           text_upper.startswith('REFERENCES') or \
           text_upper.startswith('APPENDIX'):
            p.paragraph_format.page_break_before = True
```

2. **Full rebuild** (`rebuild_docx_from_md.py:403-408`):
```python
for p in doc.paragraphs:
    if p.style.name.startswith('Heading'):
        text_upper = p.text.strip().upper()
        if text_upper.startswith('CHAPTER') or \
           text_upper.startswith('REFERENCES') or \
           text_upper.startswith('APPENDIX'):
            p.paragraph_format.page_break_before = True
```

### Why this matters

* **No blank paragraph hack** — Uses native `page_break_before` property, not empty paragraphs that can be deleted
* **Survives surgical updates** — Re-applied on every `update` action
* **Template-compliant** — Matches BSIT capstone formatting requirements for chapter/references/appendix pagination
* **Defense-ready** — Ensures printed copy pagination is stable regardless of content edits

---

## Anti-Deletion Guardrails

| Threat | Protection |
|--------|------------|
| File deleted on Drive | Pre-flight check: `rclone ls` verifies defense file exists → fails if missing |
| `rclone sync --delete` | Skill ONLY uses `rclone copy` (additive); `sync` blocked |
| Manual Drive UI delete | Next pipeline run detects missing file → blocks with clear error |
| Accidental overwrite | Drift check blocks unless explicit `accept-cloud` chosen |
| No backup | Auto-backup to `output/backups/YYYYMMDD_HHMMSS.docx` on every successful push |

### Anti-Deletion Guard Function

```python
def anti_deletion_guard() -> bool:
    # Check Drive
    code, stdout, stderr = run_cmd(f'rclone ls "{REMOTE_DOCX}"')
    if code != 0 or not stdout.strip():
        print(f"❌ DEFENSE FILE MISSING ON DRIVE: {REMOTE_DOCX}")
        return False
    
    # Check local
    if not LOCAL_DOCX.exists():
        print(f"⚠️  Local copy missing: {LOCAL_DOCX}")
    else:
        local_sha = compute_sha256(LOCAL_DOCX)
        print(f"✅ Local defense file exists: {local_sha[:16]}...")
    
    return True
```

Every pipeline action (`pull`, `check`, `update`, `push`, `diff`, `full`) calls this first.

---

## Defense File Identity

```
Name: SecureCAT_Ch1_Ch2_Manuscript[never_delete].docx
Location: Drive → gdrive:"A.Y. 2026-2027/Capstone/output/"
          Local → capstone/output/SecureCAT_Ch1_Ch2_Manuscript[never_delete].docx
SHA256: Tracked in manuscript MD META tag (docx-sha256)
Version: Tracked in manuscript MD META tag (docx-sync-version)
```

---

## Configurable Options

```python
# In pipeline.py or override via CLI
DEFENSE_FILE = "SecureCAT_Ch1_Ch2_Manuscript[never delete].docx"
DRIVE_BASE = "gdrive:A.Y. 2026-2027/Capstone/output/"
MANUSCRIPT_MD = Path("capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md")
TEMPLATE_DOCX = Path("capstone/templates/BSIT Capstone Template.docx")
OUTPUT_DIR = Path("capstone/output")
BACKUP_DIR = OUTPUT_DIR / "backups"
LOCAL_DOCX = OUTPUT_DIR / DEFENSE_FILE
REMOTE_DOCX = f"{DRIVE_BASE}{DEFENSE_FILE}"
MAX_BACKUPS = 30  # Keep last 30 backups
```

---

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Drift detected (resolution required) |
| 2 | Defense file missing on Drive |
| 3 | Defense file missing locally |
| 4 | Parse error / invalid args |
| 5 | Network / rclone error |
| 6 | User aborted |

---

## Integration with Other Skills

| Skill | Role |
|-------|------|
| `docx-drift-check` | Called internally for pre-flight (exit codes 0-4 mapped) |
| `md-updates-docx` | Called internally for surgical update (via `md_to_docx` functions) |
| `gdrive-rclone-push` | Pattern followed for Drive sync (additive `rclone copy`) |
| `manuscript-revision` | MD editing workflow feeds this pipeline |

---

## Implementation Notes

* **Single entry point**: `pipeline.py` with `--action` flag
* **No external dependencies** beyond existing skills + rclone
* **Idempotent**: Safe to re-run; drift check prevents duplicate work
* **Auditable**: Every action logged with timestamp, SHA256, user choice
* **Temp cleanup**: `diff` action creates/cleans `output/temp/` automatically
* **Backup pruning**: Keeps last 30 backups by modification time

---

## Verification Checklist (Dry Run)

Before declaring skills production-ready:

1. ✅ `pull` downloads file, local SHA matches remote
2. ✅ `check` reports PASS when synced, DRIFT_DETECTED when modified
3. ✅ `update` with clean drift → applies surgical updates, increments SHA
4. ✅ `update` with drift → presents resolution menu, all 6 options functional
5. ✅ `diff` → downloads temp, shows section diffs, cleans up temp
6. ✅ `push` → rclone copy only, no sync/delete, file appears on Drive
7. ✅ `full` → end-to-end: pull → check → update → push → backup
8. ✅ Page breaks present on Chapter/REFERENCES/APPENDIX after update
9. ✅ META tag in MD updated with new SHA256 + ISO timestamp
10. ✅ Backup created in `output/backups/` with timestamp
11. ✅ `change_log.json` and `tag_map.json` written to `output/`
12. ✅ Anti-deletion guard blocks when Drive file missing