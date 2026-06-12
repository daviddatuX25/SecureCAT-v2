# Task: Add Descriptive TAGs + UPDATE/REMOVE Blocks to Manuscript MD

## Context

The consolidated manuscript (`manuscript/SecureCAT_Ch1_Ch2_Manuscript.md`) currently has no TAG anchors. We need to add descriptive `<!-- TAG: xxx -->` anchors at every major section boundary, plus `<!-- UPDATE:START -->...<!-- UPDATE:END -->` and `<!-- REMOVE:START -->...<!-- REMOVE:END -->` blocks where content changes are expected.

These tags enable the `md-updates-docx` skill to surgically update the master DOCX.

## Tag Naming Convention

| Pattern | Example |
|---------|---------|
| Chapter-level | `ch1-introduction`, `ch2-methodology` |
| Section-level | `ch1-bg-of-the-study`, `ch1-problem-statement`, `ch1-objectives` |
| Sub-section | `ch2-research-design`, `ch2-software-model`, `ch2-project-plan` |
| Special | `appendix-a-use-case`, `references-list` |

**Rules:**
- Lowercase, hyphens only (no spaces, no underscores in tag name)
- Prefix with `ch1-` or `ch2-` for chapter affiliation
- Descriptive enough to identify section without looking at heading
- One TAG per major manuscript section (heading level 2 or 3)

## Current Manuscript Structure (Target Tags)

Based on the BSIT Capstone Template and current manuscript content:

### Chapter 1 Tags

| Section | Tag |
|---------|-----|
| Chapter 1: Introduction | `ch1-introduction` |
| 1.1 Background of the Study | `ch1-bg-of-the-study` |
| 1.2 Problem Statement | `ch1-problem-statement` |
| 1.3 Objectives of the Study | `ch1-objectives` |
| 1.4 Scope and Delimitations | `ch1-scope-delimitations` |
| 1.5 Significance of the Study | `ch1-significance` |
| 1.6 Conceptual Framework (IPO) | `ch1-conceptual-framework` |

### Chapter 2 Tags

| Section | Tag |
|---------|-----|
| Chapter 2: Methodology | `ch2-methodology` |
| 2.1 Research Design | `ch2-research-design` |
| 2.2 Software Model (AIDLC) | `ch2-software-model` |
| 2.3 Project Plan (Gantt) | `ch2-project-plan` |
| 2.4 Project Assignment | `ch2-project-assignment` |
| 2.5 Population and Locale | `ch2-population-locale` |
| 2.6 Research Instruments (SUS + NASA-TLX) | `ch2-research-instruments` |
| 2.7 Data Analysis (K-Means Live) | `ch2-data-analysis` |

### Appendices & References

| Section | Tag |
|---------|-----|
| Appendix A — Use Case Diagram | `appendix-a-use-case` |
| Appendix B — Letter to Conduct | `appendix-b-letter-conduct` |
| References | `references-list` |

**Total: ~20 TAGs**

## UPDATE/REMOVE Blocks (Initial)

For now, add placeholder blocks where we know content will change:

```markdown
## 2.1 Research Design
<!-- TAG: ch2-research-design -->
<!-- UPDATE:START -->
[Content will be updated when AIDLC framing is finalized]
<!-- UPDATE:END -->
Current descriptive developmental design content...

## 2.2 Software Model
<!-- TAG: ch2-software-model -->
<!-- REMOVE:START -->
[Old RAD model references to be removed]
<!-- REMOVE:END -->
AIDLC model content...
```

**Rule:** Only add UPDATE/REMOVE blocks where changes are **planned or in progress**. Don't add empty blocks everywhere.

## META Tag (Top of File)

Add at line 1-3 of manuscript:

```markdown
<!-- META: docx-sync-version="2026-06-12T00:00:00Z" docx-sha256="pending-first-sync" -->
```

The SHA256 will be populated on first successful sync.

## Source Documents to Reference

| Document | Purpose |
|----------|---------|
| `capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md` | Target file to tag |
| `capstone/guides/GUIDE-2-CHAPTER1-CONTENT.md` | Chapter 1 section structure |
| `capstone/guides/GUIDE-3-CHAPTER2-CONTENT.md` | Chapter 2 section structure |
| `capstone/templates/TEMPLATE_SPEC.md` | ISPSC template section requirements |

## Deliverable

**Updated `capstone/manuscript/SecureCAT_Ch1_Ch2_Manuscript.md` with:**

1. META tag at top (SHA256 = "pending-first-sync")
2. ~20 descriptive TAG anchors at all major sections
3. UPDATE/REMOVE blocks at 3-5 known change points (Research Design, Software Model, Data Analysis, etc.)
4. No other content changes — only tag/block insertion

## Verification Checklist

After tagging, verify:

- [ ] `grep -c "<!-- TAG:" manuscript/SecureCAT_Ch1_Ch2_Manuscript.md` returns ~20
- [ ] All Chapter 1 major sections have TAGs
- [ ] All Chapter 2 major sections have TAGs
- [ ] Appendices have TAGs
- [ ] META tag present at top with correct format
- [ ] UPDATE/REMOVE blocks only where changes planned
- [ ] Tag names follow convention: `ch1-...` / `ch2-...` / `appendix-...` / `references-list`
- [ ] No duplicate TAG names
- [ ] Manuscript still renders correctly (tags are HTML comments)

## Constraints

- **Do not start until explicitly directed**
- **Do not rewrite any content** — only insert HTML comment tags
- Preserve all existing formatting, paragraph breaks, headings
- Tags go **immediately after** the heading they anchor (same line or next line)
- UPDATE/REMOVE blocks go **inside** the tagged section, after the TAG line

## Related Tasks (Dependencies)

- **Prerequisite:** TASK_Migrate_Capstone_Folder_Structure.md (manuscript/ in place)
- **Enables:** TASK_Create_md_updates_docx_Skill.md (skill needs TAGs to work)
- **Enables:** Full MD→Drive workflow