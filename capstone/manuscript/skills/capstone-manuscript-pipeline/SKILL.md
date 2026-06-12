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
  - action: pull | check | update | push | full (default: full)
outputs:
  - Updated master DOCX on Drive
  - Updated META tag in manuscript MD
  - Drift resolution log
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
│     ✓ Parse MD for TAG/UPDATE/REMOVE                            │
│     ✓ Apply only changed sections to DOCX                       │
│     ✓ Preserve formatting, comments, track-changes              │
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

### Usage

```bash
# Full pipeline: pull → drift check → update → push → backup
python manuscript/skills/capstone-manuscript-pipeline/scripts/pipeline.py --action full

# Stepwise:
python .../pipeline.py --action pull      # Get latest from Drive
python .../pipeline.py --action check     # Drift check only
python .../pipeline.py --action update    # Surgical MD→DOCX (requires clean drift)
python .../pipeline.py --action push      # Push to Drive (additive copy)
```

### Drift Resolution (Interactive)

When drift detected, pipeline presents:

```
❌ DRIFT_DETECTED: Cloud DOCX has unwatched changes
   Expected: 99a331ba... (MD META, synced 2026-06-12T09:24:32Z)
   Actual:   e5f6g7h8...

Resolution options:
  [1] accept-cloud   → Use cloud version, update META, continue
  [2] keep-local     → Push local DOCX to cloud, overwrite cloud
  [3] merge          → Manual merge required (open both, resolve)
  [4] snapshot-cloud → Archive cloud version, then proceed
  [5] abort          → Cancel pipeline

Enter choice [1-5]:
```

### Anti-Deletion Guardrails

| Threat | Protection |
|--------|------------|
| File deleted on Drive | Pre-flight check: `rclone ls` verifies defense file exists → fails if missing |
| `rclone sync --delete` | Skill ONLY uses `rclone copy` (additive); sync blocked |
| Manual Drive UI delete | Next pipeline run detects missing file → blocks with clear error |
| Accidental overwrite | Drift check blocks unless explicit `accept-cloud` chosen |
| No backup | Auto-backup to `output/backups/YYYYMMDD_HHMMSS.docx` on every successful push |

### Defense File Identity

```
Name: SecureCAT_Ch1_Ch2_Manuscript[never_delete].docx
Location: Drive → gdrive:"A.Y. 2026-2027/Capstone/output/"
          Local → capstone/output/SecureCAT_Ch1_Ch2_Manuscript[never_delete].docx
SHA256: Tracked in manuscript MD META tag (docx-sha256)
Version: Tracked in manuscript MD META tag (docx-sync-version)
```

### Configurable Options

```python
# In pipeline.py or via CLI
DEFENSE_FILE = "SecureCAT_Ch1_Ch2_Manuscript[never_delete].docx"
DRIVE_BASE = "gdrive:A.Y. 2026-2027/Capstone/output/"
MANUSCRIPT_MD = "capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md"
TEMPLATE_DOCX = "capstone/templates/BSIT Capstone Template.docx"
BACKUP_DIR = "capstone/output/backups/"
MAX_BACKUPS = 30  # Keep last 30 backups
```

### Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Drift detected (resolution required) |
| 2 | Defense file missing on Drive |
| 3 | Defense file missing locally |
| 4 | Parse error / invalid args |
| 5 | Network / rclone error |
| 6 | User aborted |

### Integration with Other Skills

| Skill | Role |
|-------|------|
| `docx-drift-check` | Called internally for pre-flight |
| `md-updates-docx` | Called internally for surgical update |
| `gdrive-rclone-push` | Pattern followed for Drive sync |
| `manuscript-revision` | MD editing workflow feeds this pipeline |

---

## Implementation Notes

- **Single entry point**: `pipeline.py` with `--action` flag
- **No external dependencies** beyond existing skills + rclone
- **Idempotent**: Safe to re-run; drift check prevents duplicate work
- **Auditable**: Every action logged with timestamp, SHA256, user choice
