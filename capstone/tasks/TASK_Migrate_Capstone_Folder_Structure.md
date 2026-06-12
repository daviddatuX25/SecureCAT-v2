# Task: Migrate Capstone Folder to Lean Post-Manuscript Structure

## Context

The capstone folder currently has 60+ markdown files scattered across `drafts/`, `research/`, root, and subdirectories. Since the manuscript MD (`SecureCAT_Ch1_Ch2_Manuscript.md`) now consolidates all 19 chapter drafts, we can eliminate the draft copies and reorganize into a lean structure with clear separation of concerns:

- **references/** — immutable grounded truth (argument bank, study extractions, integration spec, protocols)
- **manuscript/** — single source of truth + converter scripts + skills
- **guides/**, **strategy/**, **team_meta/**, **templates/** — stable, rarely changed
- **output/** — generated DOCX (gitignored) — master for Drive sync
- **archive/** — June 10 completion snapshot (read-only)

This migration must preserve git history for all moved files.

## Current State (Pre-Migration)

```
capstone/
├── drafts/                    # 19 files — NOW CONSOLIDATED IN MANUSCRIPT
├── research/                  # 30+ files — mixed grounded truth + working notes
├── guides/ (6)                # stable
├── strategy/ (3)              # stable
├── team_meta/ (8+)            # frozen post-Jun10
├── SecureCAT_Ch1_Ch2_Manuscript.md
├── assemble_manuscript.py
├── *.docx files (5+)          # generated artifacts in root
├── _archive/ (2 files)
├── diagrams/ (7 files)
├── index.html, june8-field-day.html
├── README.md, ROADMAP.md, STRATEGY_NOTES.md, SYSTEM_FEATURES.md, TEMPLATE_SPEC.md
```

## Target Structure (Post-Migration)

```
capstone/
├── references/
│   ├── arguments/RESEARCH_ARGUMENT_BANK.md
│   ├── studies/STUDY1_YUKEE_EXTRACTION.md
│   ├── studies/STUDY2_BALLESTEROS_EXTRACTION.md
│   ├── integration/INTEGRATION_SPEC_2025STUDIES.md
│   ├── instruments/FEATURE_VERIFICATION_PROTOCOL.md
│   └── evaluation/EVALUATION_STRATEGY.md
├── manuscript/
│   ├── SecureCAT_Ch1_Ch2_Manuscript.md
│   ├── md_to_docx.py                 # renamed from assemble_manuscript.py
│   ├── diagrams/                     # moved from capstone/diagrams/
│   └── skills/
│       ├── md-updates-docx/
│       │   ├── SKILL.md
│       │   └── scripts/update_docx.py
│       └── docx-drift-check/
│           ├── SKILL.md
│           └── scripts/check_drift.py
├── guides/ (6)                      # unchanged
├── strategy/ (3)                    # unchanged + STRATEGY_NOTES.md moved here
├── team_meta/ (8+)                  # unchanged
├── templates/
│   ├── TEMPLATE_SPEC.md
│   ├── BSIT Capstone Template.docx
│   └── Template_ISPSC.docx
├── output/                          # gitignored
│   ├── SecureCAT_Ch1_Ch2_Manuscript.docx
│   ├── SecureCAT_Ch1_Ch2_Manuscript For Defense.docx
│   └── Sarmiento Manuscript Chapter 1 and 2.docx
└── archive/
    ├── task_division/
    │   ├── Task_Division_Chapter1_2.md
    │   └── member3/DIRECTION.md
    ├── meta_guide_session1/
    │   ├── christine_self_assessment.md
    │   └── david_self_assessment.md
    ├── drafts/                      # 19 drafts — historical only
    ├── research_working_notes/      # 30+ files — superseded by manuscript
    ├── ROADMAP.md
    ├── SYSTEM_FEATURES.md
    └── index.html
```

## Migration Rules

1. **Use `git mv` for all moves** — preserves history
2. **Do not delete files** — move to `archive/` if superseded
3. **Grounded truth files (6)** → `references/` — these are immutable cite-only sources
4. **19 drafts** → `archive/drafts/` — historical reference only, no longer maintained
5. **Research working notes (30+)** → `archive/research_working_notes/` — superseded by manuscript
6. **Generated DOCX** → `output/` — add `capstone/output/` to `.gitignore`
7. **Templates** → `templates/`
8. **June 10 snapshot docs** → `archive/`
9. **Skills** → `manuscript/skills/` — versioned with manuscript

## Source Documents to Reference

| Document | Purpose |
|----------|---------|
| `capstone/STRATEGY_NOTES.md` | Strategic decisions (AIDLC, SUS+TLX, Option C) |
| `capstone/guides/GUIDE-2-CHAPTER1-CONTENT.md` | Chapter 1 requirements |
| `capstone/guides/GUIDE-3-CHAPTER2-CONTENT.md` | Chapter 2 requirements |
| `capstone/research/RESEARCH_ARGUMENT_BANK.md` | Grounded truth — must go to references/ |
| `capstone/research/STUDY1_YUKEE_EXTRACTION.md` | Grounded truth — must go to references/ |
| `capstone/research/STUDY2_BALLESTEROS_EXTRACTION.md` | Grounded truth — must go to references/ |
| `capstone/research/INTEGRATION_SPEC_2025STUDIES.md` | Grounded truth — must go to references/ |
| `capstone/research/FEATURE_VERIFICATION_PROTOCOL.md` | Grounded truth — must go to references/ |
| `capstone/research/EVALUATION_STRATEGY.md` | Grounded truth — must go to references/ |
| `capstone/drafts/*.md` (19 files) | Archive — historical only |
| `capstone/research/*` (30+ files) | Archive — superseded by manuscript |

## Deliverable

1. **Executed migration** using `git mv` commands (provided in migration script)
2. **Updated `.gitignore`** with `capstone/output/`
3. **Verified structure** matches target above
4. **Git commit** with message:
   ```
   restructure(capstone): lean post-manuscript structure

   - references/: 6 immutable grounded-truth files (argument bank, 2 studies, integration spec, 2 protocols)
   - manuscript/: single source (Manuscript.md + md_to_docx.py + skills)
   - guides/, strategy/, team_meta/, templates/: unchanged
   - output/: generated DOCX (gitignored) — master for Drive sync
   - archive/: June 10 snapshot + 19 drafts + research working notes (read-only)
   - Eliminated: drafts/ (19 files), research/ (30+ files), root DOCX clutter
   ```

## Constraints

- **Do not start until explicitly directed**
- Migration must be atomic — all moves in one commit or staged sequence
- Verify all 6 grounded truth files land in `references/` with correct subfolder structure
- Verify 19 drafts land in `archive/drafts/`
- Verify `output/` is gitignored
- After migration, `git status` should show only the restructure changes + any pre-existing uncommitted changes

## Related Tasks (After Completion)

- TASK_Create_md_updates_docx_Skill.md
- TASK_Create_docx_drift_check_Skill.md
- TASK_Tag_Manuscript_MD.md
- TASK_Implement_Core_Converter_Scripts.md