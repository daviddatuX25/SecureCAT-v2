# Answer Sheet Template Specification — OMR Scanner

**Status:** Draft  
**Created:** 2026-07-21  
**Part of:** OMR Scanner Feature (PRD: `openspec/specs/omr-scanner-prd.md`)  
**Library Target:** OMRChecker (`template.json` format)  

---

## 1. Design Principles

- **Anchor-corner-based**: 4 printed corner markers for perspective correction — the OMR pipeline does NOT rely on static pixel coordinates
- **Grid-relative**: Bubble positions are calculated relative to anchor corners, not absolute page positions
- **A4 portrait**: Standard A4 (210×297mm) — compatible with office printers and commodity scanners
- **High-contrast**: Black ink on white paper; no colored backgrounds in bubble zones
- **300 DPI target**: Designed for 300 DPI scans; degrades gracefully to 200 DPI

---

## 2. Sheet Layout

```
┌──────────────────────────────────────┐
│  ●──┐                                │  ← Corner Marker 1 (top-left)
│  └──┘  NEXIAM ICAT ANSWER SHEET      │
│         ISPSC Tagudin Campus          │
│                                       │
│   Applicant ID: [  APP-2026-XXXX  ]   │  ← Identifier field (OCR-readable below)
│   ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓   │  ← Barcode / number grid
│                                       │
│   ┌─ SECTION A: MATHEMATICS ───────┐ │
│   │  ○  ○  ○  ○  ○    ○  ○  ○  ○  ○ │ │  ← 5 columns (A-E), rows = Q1-Q20
│   │  ○  ○  ○  ○  ○    ○  ○  ○  ○  ○ │ │
│   │  ○  ○  ○  ○  ○    ○  ○  ○  ○  ○ │ │
│   │  ... (20 questions × 5 choices)  │ │
│   └──────────────────────────────────┘ │
│                                       │
│   ┌─ SECTION B: LOGIC ─────────────┐ │
│   │  ○  ○  ○  ○  ○    ○  ○  ○  ○  ○ │ │  ← Q21-Q40
│   │  ... (20 questions × 5 choices)  │ │
│   └──────────────────────────────────┘ │
│                                       │
│   ┌─ SECTION C: READING ───────────┐ │
│   │  ○  ○  ○  ○  ○    ○  ○  ○  ○  ○ │ │  ← Q41-Q60
│   │  ... (20 questions × 5 choices)  │ │
│   └──────────────────────────────────┘ │
│                                       │
│   ┌─ SECTION D: SUBJECT-SPECIFIC ──┐ │
│   │  ○  ○  ○  ○  ○    ○  ○  ○  ○  ○ │ │  ← Q61-Q80 (if applicable)
│   │  ... (20 questions × 5 choices)  │ │
│   └──────────────────────────────────┘ │
│                                       │
│  └──┐                               │  ← Corner Marker 4 (bottom-right)
│     ●──┘                             │
└──────────────────────────────────────┘
```

### 2.1 Sections (Mapped to AptitudeArea)

| Section | AptitudeArea Code | Questions | Choices |
|---|---|---|---|
| A — Mathematics | `math` | 20 (Q1–Q20) | A B C D E |
| B — Logic | `logic` | 20 (Q21–Q40) | A B C D E |
| C — Reading Comprehension | `reading` | 20 (Q41–Q60) | A B C D E |
| D — Subject-Specific | `subject` | 20 (Q61–Q80) | A B C D E |

**Note**: `max_items` in `AptitudeArea` drives per-section question count. Config table above is the baseline.

### 2.2 Corner Markers
- **Shape**: Solid black square, 10×10mm
- **Inner cutout**: White square, 5×5mm (forms a "target" shape for reliable contour detection)
- **Positions**:
  - Top-left: 10mm from left, 10mm from top
  - Top-right: 10mm from right, 10mm from top  
  - Bottom-left: 10mm from left, 10mm from bottom
  - Bottom-right: 10mm from right, 10mm from bottom
- **Purpose**: The CV pipeline detects these 4 corners → computes perspective transform → warps to flat rectangle before reading bubbles

### 2.3 Applicant Identifier
- **Primary**: Human-readable applicant reference number printed at top (e.g., `APP-2026-0451`)
- **Secondary (optional)**: Barcode or machine-readable number grid below the ID field for automated applicant matching
- **For Phase 1**: Counselor manually maps uploads to applicants via dropdown (reuse existing `ScoreImportService` pattern)

---

## 3. OMRChecker `template.json` Specification

The answer sheet is defined as an OMRChecker template. This JSON config tells the engine where to find bubbles, how to group them, and what the answer key maps to.

```json
{
  "template": {
    "name": "nexiam-icat-v1",
    "description": "NEXIAM ICAT Answer Sheet — 4 sections × 20 questions × 5 choices",
    "page_size": "A4",
    "orientation": "portrait",
    "dpi": 300,
    "units": "mm"
  },
  "registration_marks": {
    "type": "corner_squares",
    "count": 4,
    "size_mm": 10,
    "expected_positions": [
      { "x": 10,  "y": 10  },
      { "x": 190, "y": 10  },
      { "x": 10,  "y": 277 },
      { "x": 190, "y": 277 }
    ]
  },
  "sections": [
    {
      "id": "math",
      "label": "MATHEMATICS",
      "start_question": 1,
      "end_question": 20,
      "choices": ["A", "B", "C", "D", "E"],
      "bubble_grid": {
        "top_left": { "x": 20, "y": 50 },
        "row_spacing_mm": 8,
        "col_spacing_mm": 12,
        "rows": 20,
        "cols": 5
      }
    },
    {
      "id": "logic",
      "label": "LOGIC",
      "start_question": 21,
      "end_question": 40,
      "choices": ["A", "B", "C", "D", "E"],
      "bubble_grid": {
        "top_left": { "x": 20, "y": 100 },
        "row_spacing_mm": 8,
        "col_spacing_mm": 12,
        "rows": 20,
        "cols": 5
      }
    },
    {
      "id": "reading",
      "label": "READING COMPREHENSION",
      "start_question": 41,
      "end_question": 60,
      "choices": ["A", "B", "C", "D", "E"],
      "bubble_grid": {
        "top_left": { "x": 20, "y": 150 },
        "row_spacing_mm": 8,
        "col_spacing_mm": 12,
        "rows": 20,
        "cols": 5
      }
    },
    {
      "id": "subject",
      "label": "SUBJECT-SPECIFIC",
      "start_question": 61,
      "end_question": 80,
      "choices": ["A", "B", "C", "D", "E"],
      "bubble_grid": {
        "top_left": { "x": 20, "y": 200 },
        "row_spacing_mm": 8,
        "col_spacing_mm": 12,
        "rows": 20,
        "cols": 5
      }
    }
  ],
  "scoring": {
    "confidence_threshold": 0.75,
    "partial_fill_min_pixels": 60,
    "full_fill_min_pixels": 120,
    "bubble_diameter_mm": 4.5
  }
}
```

### 3.1 Key Configuration Parameters

| Parameter | Value | Notes |
|---|---|---|
| `bubble_diameter_mm` | 4.5mm | Standard OMR bubble size |
| `row_spacing_mm` | 8mm | Center-to-center vertical |
| `col_spacing_mm` | 12mm | Center-to-center horizontal |
| `confidence_threshold` | 0.75 | Bubbles below this → flagged for review |
| `partial_fill_min_pixels` | 60 | Threshold below which bubble is "empty" at 300 DPI |
| `full_fill_min_pixels` | 120 | Threshold above which bubble is "filled" at 300 DPI |

---

## 4. Answer Key Database Schema

New migration required. The answer key maps questions → correct answers per exam session.

```sql
CREATE TABLE answer_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_session_id BIGINT UNSIGNED NOT NULL,
    aptitude_area_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    key_data JSON NOT NULL COMMENT '{"1":"A","2":"C","3":"B",...}',
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (exam_session_id) REFERENCES exam_sessions(id),
    FOREIGN KEY (aptitude_area_id) REFERENCES aptitude_areas(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Track per-applicant OMR processing results
CREATE TABLE omr_processing_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grading_session_id BIGINT UNSIGNED NOT NULL,
    applicant_id BIGINT UNSIGNED NOT NULL,
    sheet_image_path VARCHAR(255) NOT NULL,
    raw_results JSON NOT NULL COMMENT 'Full OMRChecker output: per-question answers + confidence',
    confidence_summary JSON NOT NULL COMMENT 'Per-section confidence stats',
    flag_count INT UNSIGNED DEFAULT 0,
    flags_resolved_at TIMESTAMP NULL,
    flags_resolved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,

    FOREIGN KEY (grading_session_id) REFERENCES grading_sessions(id),
    FOREIGN KEY (applicant_id) REFERENCES applicants(id)
);

-- Individual flagged items for review
CREATE TABLE omr_flagged_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    processing_result_id BIGINT UNSIGNED NOT NULL,
    question_number INT UNSIGNED NOT NULL,
    section_code VARCHAR(20) NOT NULL,
    detected_answer VARCHAR(5) NULL,
    confidence_score DECIMAL(5,4) NOT NULL,
    status ENUM('pending', 'accepted', 'overridden', 'skipped') DEFAULT 'pending',
    overridden_to VARCHAR(5) NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,

    FOREIGN KEY (processing_result_id) REFERENCES omr_processing_results(id) ON DELETE CASCADE
);
```

---

## 5. Processing Flow (Per Sheet)

```
Upload Image
    │
    ▼
Validate Image (format, DPI, file size)
    │
    ▼
Send to Python Microservice
    │
    ▼
OMRChecker Pipeline:
  1. Read image (cv2.imread)
  2. Grayscale (cv2.cvtColor)
  3. Blur (cv2.GaussianBlur)
  4. Adaptive threshold (cv2.adaptiveThreshold)
  5. Find contours (cv2.findContours)
  6. Locate 4 registration markers
  7. Perspective transform (cv2.warpPerspective)
  8. For each section in template.json:
     a. Extract bubble grid ROI
     b. Segment into per-question ROIs
     c. Count filled pixels per bubble
     d. Classify: filled / empty / uncertain
     e. Compare against answer key
     f. Compute per-question confidence
  9. Build JSON response
    │
    ▼
Laravel receives JSON:
  - Create omr_processing_results record
  - Create omr_flagged_items for low-confidence items
  - Show in Flag Review UI
    │
    ▼
Counselor resolves flags →
    │
    ▼
Publish → Create ApplicantScore records
```

---

## 6. Open Questions

| # | Question | Resolution Needed |
|---|---|---|
| T-1 | Does the existing ICAT sheet already have registration marks (corner markers)? Or must we design a NEW sheet? | Awaiting Guidance Office sample photo |
| T-2 | Are ICAT sub-tests exactly 20 questions each? The `max_items` field in `AptitudeArea` varies per config | Verify against actual test |
| T-3 | Is the applicant identifier printed on existing sheets, or is it handwritten? | Affects OCR approach |
| T-4 | What scan hardware is available? (office scanner → 300 DPI; phone camera → variable) | Affects preprocessing params |

---

## 7. Template Generation Tool (Future)

For Phase 2, build a Laravel command that auto-generates the answer sheet PDF from `AptitudeArea` config:
```bash
php artisan omr:generate-template --session-id=5 --output=sheet.pdf
```
This reads active `AptitudeArea` records, builds a PDF with anchor corners + bubble grids, and outputs a printable file. Eliminates manual template design per exam variant.
