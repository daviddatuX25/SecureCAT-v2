# Task: Implement Core Converter Scripts (md_to_docx.py + Bookmark Mapping)

## Context

The `md-updates-docx` skill needs a working core implementation. This task builds the actual Python scripts that:
1. Parse manuscript MD for TAG/UPDATE/REMOVE blocks
2. Map TAGs to DOCX bookmarks/headings
3. Apply surgical updates preserving styles
4. Handle full rebuild from template

This is the engine behind the skill — the skill definition wraps this script.

## Files to Create/Update

| File | Purpose |
|------|---------|
| `capstone/manuscript/md_to_docx.py` | Main converter (replaces `assemble_manuscript.py`) |
| `capstone/manuscript/tag_map.json` | Persistent TAG ↔ bookmark mapping (generated) |
| `capstone/manuscript/skills/md-updates-docx/scripts/update_docx.py` | Skill entry point (thin wrapper) |

## md_to_docx.py — Full Specification

### CLI Interface

```bash
# Full rebuild from template
python md_to_docx.py \
  --md manuscript/SecureCAT_Ch1_Ch2_Manuscript.md \
  --template templates/BSIT\ Capstone\ Template.docx \
  --output output/SecureCAT_Ch1_Ch2_Manuscript.docx \
  --full-rebuild

# Surgical update (tag-specific)
python md_to_docx.py \
  --md manuscript/SecureCAT_Ch1_Ch2_Manuscript.md \
  --docx output/SecureCAT_Ch1_Ch2_Manuscript.docx \
  --output output/SecureCAT_Ch1_Ch2_Manuscript.docx \
  --update-tags ch1-bg-of-the-study ch2-research-design

# Drift check only
python md_to_docx.py --check-drift --md ... --docx ...
```

### Core Functions

#### 1. `parse_manuscript(md_path) → (meta: dict, tags: dict)`

Parses the manuscript MD and returns:

```python
meta = {
    'docx-sync-version': '2026-06-12T10:30:00Z',
    'docx-sha256': 'a1b2c3d4...'
}

tags = {
    'ch1-bg-of-the-study': {
        'heading': '1.1 Background of the Study',
        'level': 2,
        'update': 'New content...',  # from UPDATE block, or None
        'remove': 'Old content...',  # from REMOVE block, or None
        'raw_content': 'Full section text...'
    },
    'ch2-research-design': { ... }
}
```

**Parsing rules:**
- META tag: first `<!-- META: ... -->` in file
- TAG anchors: `<!-- TAG: tag-name -->` — each starts a new section
- Section content: from TAG to next TAG or META or EOF
- UPDATE block: `<!-- UPDATE:START -->...<!-- UPDATE:END -->` within section
- REMOVE block: `<!-- REMOVE:START -->...<!-- REMOVE:END -->` within section

#### 2. `build_tag_map(docx_path, tags_dict) → tag_map: dict`

Maps each TAG to its location in the DOCX:

```python
tag_map = {
    'ch1-bg-of-the-study': {
        'bookmark': 'tag_ch1_bg_of_the_study',
        'heading_paragraph_idx': 42,
        'section_start_idx': 43,
        'section_end_idx': 58,
        'paragraph_indices': [43, 44, 45, ...]
    }
}
```

**Mapping strategy:**
1. First, try to find existing bookmark named `tag_<sanitized-tag>`
2. If not found, find heading paragraph matching TAG's heading text (fuzzy match)
3. Create bookmark at that heading if missing
4. Determine section boundaries: from heading to next heading of same or higher level

#### 3. `apply_updates(doc, tag_map, tags_dict, update_tags: list)`

For each tag in `update_tags` (or all if not specified):

```python
def apply_updates(doc, tag_map, tags_dict, update_tags):
    for tag_name in update_tags:
        if tag_name not in tag_map:
            continue
        mapping = tag_map[tag_name]
        tag_data = tags_dict.get(tag_name, {})
        
        # DELETE remove-block content first
        if tag_data.get('remove'):
            delete_matching_paragraphs(doc, mapping, tag_data['remove'])
        
        # INSERT/REPLACE with update-block content
        if tag_data.get('update'):
            replace_section_content(doc, mapping, tag_data['update'])
        
        # If neither update nor remove → no-op (preserve existing)
```

**Style preservation rules:**
- New paragraphs inherit style from first paragraph in section
- If section has mixed styles (Heading 2 + Normal), apply proportionally
- Bullet/numbered lists: preserve list formatting
- Tables: not handled in v1 (flag as unsupported)

#### 4. `full_rebuild(template_path, tags_dict, output_path)`

Creates new DOCX from template + manuscript content:

1. Load template DOCX
2. Clear body content (keep styles, headers/footers)
3. For each TAG in manuscript order:
   - Add heading (level from TAG data)
   - Add content paragraphs (from `raw_content` or `update`)
   - Create bookmark `tag_<sanitized>`
4. Save to output_path

#### 5. `compute_docx_sha256(docx_path) → str`

```python
hashlib.sha256(Path(docx_path).read_bytes()).hexdigest()
```

#### 6. `update_manuscript_meta(md_path, new_sha256, new_version)`

Updates the META tag in-place in the manuscript MD.

#### 7. `save_tag_map(tag_map, json_path)` / `load_tag_map(json_path)`

Persists tag_map to `manuscript/tag_map.json` for repeatability.

## tag_map.json — Format

```json
{
  "version": 1,
  "generated": "2026-06-12T10:30:00Z",
  "tags": {
    "ch1-bg-of-the-study": {
      "bookmark": "tag_ch1_bg_of_the_study",
      "heading_paragraph_idx": 42,
      "section_start_idx": 43,
      "section_end_idx": 58,
      "paragraph_indices": [43, 44, 45]
    }
  }
}
```

## Source Documents to Reference

| Document | Purpose |
|----------|---------|
| `capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md` | Input manuscript with TAGs |
| `capstone/templates/BSIT Capstone Template.docx` | Template for full rebuild |
| `capstone/guides/GUIDE-1-FORMATTING.md` | Style rules for DOCX output |
| `capstone/manuscript/assemble_manuscript.py` | Reference implementation (legacy) |
| `capstone/manuscript/skills/md-updates-docx/scripts/update_docx.py` | Skill wrapper |

## Deliverable

1. **`capstone/manuscript/md_to_docx.py`** — complete implementation with all functions above
2. **`capstone/manuscript/tag_map.json`** — generated on first run, committed to git
3. **Updated skill wrapper** at `capstone/manuscript/skills/md-updates-docx/scripts/update_docx.py` that calls `md_to_docx.py` functions

## Verification Checklist

After implementation, verify:

- [ ] `python md_to_docx.py --full-rebuild` creates valid DOCX from template + manuscript
- [ ] `python md_to_docx.py --update-tags ch1-bg-of-the-study` surgically updates only that section
- [ ] Styles preserved: Heading 1, Heading 2, Normal, List Bullet, List Number
- [ ] Comments and track-changes in DOCX survive surgical update
- [ ] META tag in MD updated with new SHA256 + timestamp
- [ ] tag_map.json generated and usable for subsequent runs
- [ ] Drift check integration works (returns proper exit codes)

## Constraints

- **Do not start until explicitly directed**
- Use `python-docx` only (no pandoc, no libreoffice)
- Must work with existing `BSIT Capstone Template.docx` styles
- Surgical update must be idempotent (running twice = same result)
- tag_map.json must be human-readable and diffable
- Handle missing bookmarks gracefully (create at heading)
- Handle missing sections gracefully (log warning, continue)

## Related Tasks (Dependencies)

- **Prerequisite:** TASK_Migrate_Capstone_Folder_Structure.md
- **Prerequisite:** TASK_Tag_Manuscript_MD.md (manuscript must have TAGs)
- **Prerequisite:** TASK_Create_md_updates_docx_Skill.md (skill definition)
- **Prerequisite:** TASK_Create_docx_drift_check_Skill.md (pre-flight)
- **Enables:** Full MD→Drive workflow