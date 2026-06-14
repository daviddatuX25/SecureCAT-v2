---
name: docx-drift-check
description: Pre-flight check to verify if the local/remote DOCX document has diverged from the MD source file's metadata tracking
category: capstone
trigger: pre-flight check before document updates or syncs
inputs:
  - md_path: path to manuscript MD source file (e.g. capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md)
  - docx_path: path to local master DOCX file (e.g. capstone/output/SecureCAT_Ch1_Ch2_Manuscript[never delete].docx)
outputs:
  - Exit code indicating synchronization status
  - SHA256 comparison details printed to stdout
---

# docx-drift-check

This skill performs a fast cryptographic check to verify if the DOCX document contains modifications that are not yet recorded in the Markdown source file's metadata block.

## How it works

1. **Read Metadata**: Parses the `docx-sha256` value tracked in the Markdown file's HTML comments block (e.g. `<!-- META: docx-sha256="hash" docx-sync-version="..." -->`).
2. **Compute Document Hash**: Calculates the SHA256 of the target DOCX file using `hashlib.sha256()` on the raw file bytes.
3. **Compare**:
   * If the SHA256 hashes match: The files are in sync (exit code 0).
   * If the hashes differ: Drift is detected, meaning either the local file has changes or a new version was pulled from Google Drive (exit code 1).
   * If the DOCX file does not exist locally: Exit code 2.
   * If the MD file is missing the `docx-sha256` tag: Exit code 3.
   * If reading or hashing fails: Exit code 4.

## Exit Code Schema

| Code | Constant | Meaning |
|------|----------|---------|
| 0 | `PASS` | Document matches metadata; safe to proceed |
| 1 | `DRIFT_DETECTED` | Document hash does not match expected metadata |
| 2 | `MISSING_DOCX` | The DOCX file does not exist locally |
| 3 | `NO_META` | MD file is missing the `docx-sha256` tag |
| 4 | `PARSE_ERROR` | Reading or hashing failed |

## Usage

Run the checker script directly:

```bash
python capstone/manuscript/skills/docx-drift-check/scripts/check_drift.py --md <path_to_md> --docx <path_to_docx> [--verbose]
```

### Options

* `--md`: Path to manuscript MD file (required)
* `--docx`: Path to master DOCX file (required)
* `--verbose`: Show full SHA256 hash on PASS

## Integration as Pre-Flight Guard

Other skills (especially `capstone-manuscript-pipeline` and `md-updates-docx`) **must** call this check before performing any modifications to ensure they are working with a synchronized document:

```python
# Example: invoke from Python
import subprocess
result = subprocess.run([
    'python', 'capstone/manuscript/skills/docx-drift-check/scripts/check_drift.py',
    '--md', md_path,
    '--docx', docx_path
], capture_output=True, text=True)

if result.returncode != 0:
    # Handle drift or error before proceeding
    handle_drift(result.returncode, result.stdout)
```

## Reverse Sync Companion

The skill directory also includes `sync_md_from_docx.py` for **reverse synchronization** — extracting content from a cloud DOCX and rebuilding the manuscript MD with updated TAG/UPDATE blocks and a new META tag. This is used when accepting cloud changes (Option 1 in drift resolution).

### Reverse Sync Usage

```bash
python capstone/manuscript/skills/docx-drift-check/scripts/sync_md_from_docx.py
```

This script:
1. Computes the SHA256 of the cloud DOCX
2. Extracts sections using bold+centered/pattern heading detection (template-specific)
3. Maps headings to canonical tag names via `heading_to_tag()`
4. Rewrites the manuscript MD with:
   - Updated `<!-- META: docx-sha256="..." docx-sync-version="..." -->`
   - All 17 canonical sections with `<!-- TAG: ... -->`, headings, and `<!-- UPDATE:START -->...<!-- UPDATE:END -->` blocks
   - Placeholder text `[Content synchronized from cloud DOCX — review and edit here]` in UPDATE blocks