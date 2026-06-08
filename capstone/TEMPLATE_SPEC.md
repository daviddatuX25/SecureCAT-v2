# ISPSC Capstone Manuscript — Formatting Specification

> Extracted from `Sarmiento Manuscript Chapter 1 and 2.docx` (FlexiQueue reference manuscript)

## Page Setup
| Property | Value |
|---|---|
| Paper size | 8.5 × 11 in (Letter) |
| Left margin | 1.50 in |
| Right margin | 1.00 in |
| Top margin | 1.00 in |
| Bottom margin | 1.00 in |

## Default Font
| Property | Value |
|---|---|
| Font family | **Times New Roman** |
| Font size | **12 pt** (24 half-points) |
| Language | English |

## Line Spacing
| Property | Value |
|---|---|
| Line spacing | **Double (2.0)** — `w:line="480"` with `lineRule="auto"` |
| Space after paragraphs | **0** (`w:after="0"`) |
| Space before paragraphs | 0 (default, not explicitly set) |

## Paragraph Alignment
| Element | Alignment |
|---|---|
| Body text | **JUSTIFY** |
| Chapter headings ("Chapter 1") | **CENTER** |
| Chapter titles ("INTRODUCTION") | **CENTER** |
| Section headings ("Background of the Study") | **JUSTIFY** |
| Title page elements | **CENTER** |
| Figure/Table captions | **CENTER** |

## Paragraph Indentation
| Element | Setting |
|---|---|
| Body paragraphs (first line) | **0.312 in** (285750 EMU / ~0.79 cm) first-line indent |
| Enumerated items | Left indent 0.312 in + hanging indent -0.312 in |
| Section headings | No indent |
| Chapter headings | No indent |

## Text Formatting by Element

### Title Page
- All text: **Times New Roman, 12pt, BOLD, CENTER**
- Title: ALL CAPS
- Author name: ALL CAPS
- Institution: ALL CAPS
- Program: ALL CAPS
- Date: ALL CAPS
- Title page left indent: ~0.04 in (35560 EMU) on some lines

### Chapter Headings
- "Chapter 1" / "Chapter 2" — Center, Bold, Double-spaced, 12pt
- "INTRODUCTION" / "METHODOLOGY" — Center, Bold, Double-spaced, 12pt
- No extra space between "Chapter N" and chapter title

### Section Headings
- e.g., "Background of the Study", "Objectives of the Study"
- **JUSTIFY** alignment, **Bold**, Double-spaced, 12pt
- No extra spacing before/after (same 0 after as body)

### Body Text
- **JUSTIFY** alignment
- **12pt Times New Roman, NOT bold**
- **Double-spaced**
- **First-line indent: 0.312 in** (~0.79 cm)
- Space after: 0

### Enumerated Items
- e.g., "1. To identify..."
- **JUSTIFY** alignment
- Left indent: 0.312 in
- Hanging indent: -0.312 in
- Tab before number
- 12pt Times New Roman, NOT bold

### Figure/Table Captions
- e.g., "Figure 1. Conceptual Framework of the Study"
- **CENTER** alignment
- **Bold** for table captions ("Table 1. Project Roles...")
- Regular for figure captions
- 12pt Times New Roman

### References
- **JUSTIFY** alignment
- 12pt Times New Roman, NOT bold
- Double-spaced
- APA format (Author, A. A. (Year). Title...)

## Tables
- Used for: Project Assignments, Respondent Distribution, Score Interpretation
- Header row: Bold
- Content: Regular weight
- Borders: Standard Word table borders

## Files in capstone/
| File | Purpose |
|---|---|
| `Template_ISPSC.docx` | IT-106 class template (header: ISPSC + BSIT branding) — NOT capstone manuscript template |
| `Sarmiento Manuscript Chapter 1 and 2.docx` | **ACTUAL capstone manuscript reference** — FlexiQueue Ch1+Ch2 with correct formatting |
