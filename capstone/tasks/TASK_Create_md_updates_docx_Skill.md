# Task: Create md-updates-docx Skill (Surgical MD→DOCX Updater)

## Context

The manuscript MD (`manuscript/SecureCAT_Ch1_Ch2_Manuscript.md`) is the single source of truth. We need a skill that parses descriptive tags and `<update>`/`<remove>` blocks in the MD and surgically updates the master DOCX (`output/SecureCAT_Ch1_Ch2_Manuscript.docx`) — preserving formatting, comments, and track-changes.

This skill replaces the old full-rebuild approach. It enables the workflow:
1. Edit MD with descriptive tags + UPDATE/REMOVE blocks
2. Run drift check (separate skill)
3. Run md-updates-docx → surgical DOCX update
4. Push to Drive

## Tag & Block Syntax (in Manuscript MD)

```markdown
<!-- META: docx-sync-version="2026-06-12T10:30:00Z" docx-sha256="a1b2c3d4..." -->

# Chapter 1: Introduction

## 1.1 Background of the Study
<!-- TAG: ch1-bg-of-the-study -->
Content here...

## 1.2 Problem Statement
<!-- TAG: ch1-problem-statement -->
Content...

# Chapter 2: Methodology

## 2.1 Research Design
<!-- TAG: ch2-research-design -->
<!-- UPDATE:START -->
Updated content for research design (live K-Means Option C)...
<!-- UPDATE:END -->

## 2.2 Software Model
<!-- TAG: ch2-software-model -->
<!-- REMOVE:START -->
Old RAD model description...
<!-- REMOVE:END -->
Content for AIDLC model...
```

| Marker | Purpose |
|--------|---------|
| `<!-- TAG: ch1-bg-of-the-study -->` | Anchor — maps to DOCX bookmark/heading |
| `<!-- UPDATE:START -->...<!-- UPDATE:END -->` | Content to **replace** in DOCX section |
| `<!-- REMOVE:START -->...<!-- REMOVE:END -->` | Content to **delete** from DOCX section |
| `<!-- META: docx-sync-version="..." docx-sha256="..." -->` | Sync metadata (top of file) |

## Skill Specification

**Location:** `capstone/manuscript/skills/md-updates-docx/`

**Files to create:**
- `SKILL.md` — skill definition (frontmatter + documentation)
- `scripts/update_docx.py` — core implementation

### SKILL.md Frontmatter

```yaml
---
name: md-updates-docx
description: Parse manuscript MD for TAG/UPDATE/REMOVE blocks and surgically update master DOCX
category: capstone
trigger: manual
inputs:
  - manuscript_md: path to manuscript/SecureCAT_Ch1_Ch2_Manuscript.md
  - master_docx: path to output/SecureCAT_Ch1_Ch2_Manuscript.docx
  - template_docx: path to templates/BSIT Capstone Template.docx
outputs:
  - updated master_docx
  - updated META tag in manuscript_md (docx-sync-version, docx-sha256)
---
```

### Algorithm

1. **Parse manuscript MD:**
   - Extract META tag (`docx-sync-version`, `docx-sha256`)
   - Find all `<!-- TAG: xxx -->` anchors
   - For each TAG, capture:
     - `<!-- UPDATE:START -->...<!-- UPDATE:END -->` block (optional)
     - `<!-- REMOVE:START -->...<!-- REMOVE:END -->` block (optional)
     - Raw content between this TAG and next TAG/META (fallback)

2. **Verify DOCX matches META (pre-flight):**
   - Compute SHA256 of master DOCX
   - Compare with `docx-sha256` from META
   - **Mismatch → exit with DRIFT_DETECTED error** (docx-drift-check skill handles this separately)

3. **Load or create master DOCX:**
   - If `master_docx` exists → load it
   - Else → create from `template_docx`

4. **Map TAGs to DOCX locations:**
   - Each TAG maps to a DOCX bookmark or heading
   - Bookmark naming convention: `tag_<sanitized-tag-name>` (e.g., `tag_ch1_bg_of_the_study`)
   - If bookmark missing → create at matching heading level

5. **Apply updates per TAG:**
   - **If UPDATE block exists:** Replace all paragraphs in that section with UPDATE content, preserving paragraph styles
   - **If REMOVE block exists:** Delete paragraphs matching REMOVE content in that section
   - **If neither:** Skip (no change to this section)

6. **Save updated DOCX**

7. **Update META tag in MD:**
   - New `docx-sync-version` = current UTC timestamp (ISO 8601)
   - New `docx-sha256` = SHA256 of saved DOCX
   - Write back to manuscript MD

## Source Documents to Reference

| Document | Purpose |
|----------|---------|
| `capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md` | Target manuscript with TAG/UPDATE/REMOVE syntax |
| `capstone/templates/BSIT Capstone Template.docx` | Template for initial DOCX creation |
| `capstone/guides/GUIDE-1-FORMATTING.md` | Formatting rules for DOCX output |
| `capstone/manuscript/skills/docx-drift-check/scripts/check_drift.py` | Drift check integration (pre-flight) |

## Deliverable

1. **`capstone/manuscript/skills/md-updates-docx/SKILL.md`** — complete skill definition
2. **`capstone/manuscript/skills/md-updates-docx/scripts/update_docx.py`** — working implementation with:
   - `parse_manuscript(md_path)` → `(meta_dict, tags_dict)`
   - `find_or_create_bookmark(doc, tag_name)` → bookmark reference
   - `apply_update(doc, bookmark, update_content)` → replace section
   - `apply_remove(doc, bookmark, remove_content)` → delete section
   - `update_meta_in_md(md_path, new_meta)` → write META tag
   - `compute_docx_sha256(docx_path)` → hex digest
   - CLI: `--md --docx --template` args
3. **Skill loads via `skill_view('md-updates-docx')`** in Hermes

## Constraints

- **Do not start until explicitly directed**
- Must use `python-docx` library (already in env)
- Must preserve paragraph styles (Heading 1, Heading 2, Normal, etc.)
- Must preserve comments and track-changes in DOCX
- UPDATE/REMOVE blocks are **per-TAG** — scoped to that section only
- META tag must be at top of MD (first 5 lines)
- Exit codes: 0=success, 1=drift detected, 2=missing DOCX, 3=parse error

## Related Tasks (Dependencies)

- **Prerequisite:** TASK_Migrate_Capstone_Folder_Structure.md (manuscript/ folder exists)
- **Prerequisite:** TASK_Create_docx_drift_check_Skill.md (pre-flight check)
- **Follows:** TASK_Tag_Manuscript_MD.md (manuscript must have TAGs)
- **Enables:** Full MD→Drive workflow