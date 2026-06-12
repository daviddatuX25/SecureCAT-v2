# Technical Infrastructure Task Checklist — MD↔DOCX Pipeline

## SecureCAT-v2 Capstone | Post-Manuscript Consolidation

> This checklist tracks the technical infrastructure tasks for the lean manuscript-to-DOCX pipeline.  
> Owner: **David** (technical lead)  
> Target: Complete before next manuscript revision cycle

---

## Status Key

| Symbol | Meaning |
|--------|---------|
| ⬜ | Not started |
| 🟡 | In progress |
| 🟢 | Draft/structure complete |
| ✅ | Implemented & verified |
| 🔴 | Blocked / needs help |

---

## Tasks

| Task ID | Task | Status | Depends On | Notes |
|---------|------|--------|------------|-------|
| **TI-01** | Migrate Capstone Folder to Lean Structure | ⬜ | — | Run migration script; git commit |
| **TI-02** | Create docx-drift-check Skill | ⬜ | TI-01 | Pre-sync drift detection |
| **TI-03** | Create md-updates-docx Skill | ⬜ | TI-01, TI-02 | Surgical MD→DOCX updater |
| **TI-04** | Add Descriptive TAGs + UPDATE/REMOVE Blocks to Manuscript | ⬜ | TI-01 | ~20 TAGs, META tag, 3-5 UPDATE/REMOVE blocks |
| **TI-05** | Implement Core Converter Scripts (md_to_docx.py) | ⬜ | TI-01, TI-03, TI-04 | Full rebuild + surgical update + tag_map.json |

---

## Execution Order (Dependency Graph)

```
TI-01 (Migrate Structure)
    │
    ├─→ TI-02 (docx-drift-check skill)
    │       │
    │       └─→ TI-03 (md-updates-docx skill) ←── TI-04 (Tag Manuscript)
    │                                           │
    └───────────────────────────────────────────┴─→ TI-05 (Core Converter)
```

**Critical path:** TI-01 → TI-04 → TI-05 → (TI-02 + TI-03 skills wrap TI-05)

---

## Detailed Breakdown

### TI-01: Migrate Capstone Folder Structure
- [ ] Create target directories (`references/`, `manuscript/skills/`, `output/`, `templates/`, `archive/`)
- [ ] `git mv` 6 grounded truth files → `references/`
- [ ] `git mv` 19 drafts → `archive/drafts/`
- [ ] `git mv` 30+ research files → `archive/research_working_notes/`
- [ ] `git mv` manuscript + converter → `manuscript/`
- [ ] `git mv` templates → `templates/`
- [ ] `git mv` DOCX files → `output/` + add to `.gitignore`
- [ ] `git mv` June 10 snapshot → `archive/`
- [ ] Verify structure matches target
- [ ] Commit: `restructure(capstone): lean post-manuscript structure`

### TI-02: docx-drift-check Skill
- [ ] Create `manuscript/skills/docx-drift-check/SKILL.md`
- [ ] Implement `scripts/check_drift.py` with:
  - [ ] META tag parsing
  - [ ] SHA256 computation
  - [ ] Comparison logic (PASS/DRIFT/MISSING/NO_META)
  - [ ] Diff hint extraction
- [ ] Test against current manuscript + DOCX
- [ ] Verify exit codes: 0=PASS, 1=DRIFT, 2=MISSING, 3=NO_META

### TI-03: md-updates-docx Skill
- [ ] Create `manuscript/skills/md-updates-docx/SKILL.md`
- [ ] Implement `scripts/update_docx.py`:
  - [ ] Manuscript parsing (META, TAGs, UPDATE, REMOVE)
  - [ ] DOCX bookmark mapping
  - [ ] Surgical update application
  - [ ] META tag rewrite
- [ ] Integrate with TI-02 (pre-flight drift check)
- [ ] Test with tagged manuscript

### TI-04: Tag Manuscript MD
- [ ] Add META tag at top: `docx-sync-version`, `docx-sha256="pending-first-sync"`
- [ ] Add ~20 TAG anchors:
  - [ ] Chapter 1: `ch1-introduction`, `ch1-bg-of-the-study`, `ch1-problem-statement`, `ch1-objectives`, `ch1-scope-delimitations`, `ch1-significance`, `ch1-conceptual-framework`
  - [ ] Chapter 2: `ch2-methodology`, `ch2-research-design`, `ch2-software-model`, `ch2-project-plan`, `ch2-project-assignment`, `ch2-population-locale`, `ch2-research-instruments`, `ch2-data-analysis`
  - [ ] Appendices: `appendix-a-use-case`, `appendix-b-letter-conduct`
  - [ ] References: `references-list`
- [ ] Add UPDATE/REMOVE blocks at 3-5 known change points
- [ ] Verify: `grep -c "<!-- TAG:" manuscript/SecureCAT_Ch1_Ch2_Manuscript.md` ≈ 20

### TI-05: Core Converter (md_to_docx.py)
- [ ] Implement `parse_manuscript()` → (meta, tags)
- [ ] Implement `build_tag_map()` → tag_map with bookmarks
- [ ] Implement `apply_updates()` — surgical replace preserving styles
- [ ] Implement `full_rebuild()` — template + manuscript → DOCX
- [ ] Implement `compute_docx_sha256()` + `update_manuscript_meta()`
- [ ] Implement `save_tag_map()` / `load_tag_map()` → `tag_map.json`
- [ ] CLI: `--full-rebuild`, `--update-tags`, `--check-drift`
- [ ] Test full rebuild from template
- [ ] Test surgical update on tagged sections
- [ ] Verify styles preserved (Heading 1/2, Normal, Lists)
- [ ] Verify comments/track-changes survive

---

## Verification Gates

| Gate | Criteria |
|------|----------|
| **Post-TI-01** | `git status` clean; structure matches target; `output/` gitignored |
| **Post-TI-04** | 20 TAGs + META + 3-5 UPDATE/REMOVE blocks in manuscript |
| **Post-TI-05** | Full rebuild works; surgical update works; tag_map.json generated |
| **Integration** | `docx-drift-check` → PASS → `md-updates-docx` → DOCX updated → META rewritten → `rclone copy` to Drive succeeds |

---

## Risk Notes

| Risk | Mitigation |
|------|------------|
| TI-01 migration breaks paths in other docs | Use `git mv`; update only `.gitignore` and this checklist |
| TAG mapping fails (bookmarks missing) | `build_tag_map` creates bookmarks at matching headings |
| Surgical update loses formatting | `apply_updates` copies style from first section paragraph |
| DOCX drift undetected | SHA256 is byte-level — any change detected |
| Skill loading fails in Hermes | Skills in `manuscript/skills/` — load via `skill_view('md-updates-docx')` |

---

## Related Capstone Tasks

| Capstone Task | Relation |
|---------------|----------|
| CC-03 Narrative Consistency Review | Uses updated DOCX from pipeline |
| C2-01 Research Design revision | TAG: `ch2-research-design` + UPDATE block |
| C2-02 Software Model (AIDLC) | TAG: `ch2-software-model` + REMOVE old RAD |
| C2-07 Data Analysis (K-Means) | TAG: `ch2-data-analysis` + UPDATE live params |

---

## Next Actions

1. **Start TI-01** when directed — run migration script
2. **TI-04** can begin immediately after TI-01 commit (tag manuscript)
3. **TI-02, TI-03, TI-05** in parallel after TI-01 + TI-04 structure ready

---

**Owner:** David  
**Last Updated:** 2026-06-12  
**Target Completion:** Before next manuscript revision cycle