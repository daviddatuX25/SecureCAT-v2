# PRD: Computer-Vision OMR Scoring Engine

**Status:** Draft  
**Created:** 2026-07-21  
**Product:** NEXIAM — Role-Based College Admission Testing System  
**Feature:** Computer-Vision Optical Mark Recognition (OMR) Scoring  
**Priority:** P0 — Core Pipeline

---

## 1. Problem Statement

### 1.1 Current State
The Guidance Office at ISPSC Tagudin Campus scores entrance exam answer sheets using the **stencil method** — a physical overlay placed over each sheet to manually compare answers against a key. This is the sole method; no OMR hardware or software is used.

### 1.2 Operational Gap
| Metric | Current (Manual Stencil) | Target (OMR) |
|---|---|---|
| Time per 50-applicant batch | 2–3 days | < 15 minutes |
| Scoring errors per cycle | Occasional (transcription, missed comparison) | Zero — gated by confidence threshold |
| Result release delay | 5–14 days from exam | < 48 hours |
| Staff hours consumed | Full counselor workload for days | Upload → review flags only |

### 1.3 Research Precedent
Two Philippine-context studies validate the approach:
- **Cuerdo et al. (2021)** — EvalBee OMR in Philippine public schools; significantly reduced scoring time, improved accuracy
- **Catalan (2017)** — DLSU framework for automated MC exam scoring using readily available software; 800 answer sheets across 8 courses

NEXIAM's contribution is **not inventing OMR** — it's **integrating OMR into a governed admission pipeline** with RBAC, audit trails, and the Triage Dashboard.

---

## 2. Users & Stakeholders

| Role | Interaction with OMR |
|---|---|
| **Guidance Counselor** | Uploads scanned sheets, reviews flagged uncertain items, publishes scores |
| **Guidance Administrator** | Same as Counselor + configures answer keys per exam session |
| **Applicant** | Receives scores (no direct OMR interaction) |

---

## 3. Feature Scope

### 3.1 In Scope
- Batch upload of scanned answer sheets (JPG, PNG, PDF; 300 DPI recommended)
- Perspective correction via 4 anchor corner detection
- Bubble grid detection and fill-status classification
- Answer key matching per sub-test (Math, Logic, Reading, Subject-specific)
- Raw score calculation per `AptitudeArea`
- Confidence threshold gating — uncertain bubbles flagged for human review
- Flag review interface (zoom, accept/override/skip per item)
- Score integration with existing `ApplicantScore` model + `GradingSession` workflow
- Answer key configuration per exam session (Guidance Admin)

### 3.2 Out of Scope
- OMR classification accuracy as a standalone metric (validated through TAM only)
- Real-time scanning (batch upload only)
- Answer sheet generation/printing (separate feature — answer sheet template design is a prerequisite)
- Handwriting recognition or written answer scoring
- Proprietary OMR hardware integration (uses commodity scanner or phone camera)

### 3.3 Scope Boundary (from Manuscript)
> *"The study does not measure OMR classification accuracy as a standalone metric. OMR is validated through the overall TAM acceptance evaluation — specifically, the Perceived Usefulness items measuring whether the automated scoring improves over manual hand-scoring — not through per-sheet accuracy benchmarking against a ground-truth dataset."*

---

## 4. Functional Requirements

### FR-1: Answer Sheet Upload
| ID | Requirement | Priority |
|---|---|---|
| FR-1.1 | User selects a grading session and uploads scanned answer sheets in batch | P0 |
| FR-1.2 | Accepts JPG, PNG, PDF formats at ≥200 DPI (300 DPI recommended) | P0 |
| FR-1.3 | Validates image quality — rejects blurry or insufficient-resolution images with user feedback | P1 |
| FR-1.4 | Associates each upload with applicant reference number (from sheet barcode or manual mapping) | P0 |

### FR-2: Computer-Vision Processing
| ID | Requirement | Priority |
|---|---|---|
| FR-2.1 | Pipeline: grayscale → Gaussian blur → adaptive threshold → contour detection | P0 |
| FR-2.2 | Detect four printed anchor corners on answer sheet; apply perspective transform to correct skew | P0 |
| FR-2.3 | Locate bubble grid relative to anchor corners; segment into per-question ROIs | P0 |
| FR-2.4 | Classify each bubble as filled/unfilled based on pixel density in ROI | P0 |
| FR-2.5 | Compute per-bubble confidence score (0.0–1.0) based on fill density relative to threshold | P0 |
| FR-2.6 | Support section-wise scoring per `AptitudeArea` (Math, Logic, Reading, Subject) | P0 |
| FR-2.7 | Flag bubbles where confidence < configurable threshold (default: 0.75) for human review | P0 |
| FR-2.8 | Return per-sheet JSON: `{applicant_ref, sub_test_scores[], flagged_items[], confidence_map}` | P0 |

### FR-3: Answer Key Management
| ID | Requirement | Priority |
|---|---|---|
| FR-3.1 | Guidance Admin defines answer key per exam session: correct answer per question per sub-test | P0 |
| FR-3.2 | Key stored in `answer_keys` table linked to `exam_session_id` | P0 |
| FR-3.3 | Key is versioned — edits create new version, preserving audit trail of who changed what | P1 |

### FR-4: Flag Review & Verification
| ID | Requirement | Priority |
|---|---|---|
| FR-4.1 | Review screen shows flagged items grouped by applicant | P0 |
| FR-4.2 | Each flag shows zoomed bubble ROI + system's predicted answer | P0 |
| FR-4.3 | Counselor actions: Accept (use system prediction), Override (select different answer), Skip (leave unscored) | P0 |
| FR-4.4 | All review actions logged to audit trail with counselor identity + timestamp | P0 |
| FR-4.5 | Batch mode: "Accept all flags for this applicant" | P1 |

### FR-5: Score Publication
| ID | Requirement | Priority |
|---|---|---|
| FR-5.1 | On publish: create `ApplicantScore` records per sub-test per applicant | P0 |
| FR-5.2 | Map raw scores through `AptitudeArea.resolveScore()` for normalized/percentile conversion | P0 |
| FR-5.3 | Automatically transition `GradingSession` status: `in_progress` → `review` → `finalized` | P0 |
| FR-5.4 | Trigger `ConsultationSummary` creation when all active areas scored (reuse existing pipeline) | P0 |
| FR-5.5 | Block publish until all flags resolved | P1 |

---

## 5. Non-Functional Requirements

### NFR-1: Performance
- Process **200+ sheets per minute** (OMRChecker baseline; < 500ms per sheet on server hardware)
- Upload-to-results for 50-sheet batch: < 5 minutes (including processing + review)

### NFR-2: Accuracy
- Target ≥95% classification accuracy on clean 300 DPI scans
- Confidence threshold default p=0.75; configurable per grading session
- Human review gate prevents auto-acceptance of low-confidence items

### NFR-3: Security
- All score data written through existing `ApplicantScore` model with `scored_by` attribution
- Every flag resolution logged to `AuditLog` with HMAC-SHA256 chaining
- Answer key access restricted to Guidance role via existing `AptitudeAreaPolicy`

### NFR-4: Maintainability
- Vision processing decoupled as Python microservice (can swap OMRChecker ↔ custom OpenCV without touching Laravel)
- `template.json` defines sheet layout — change template without code changes
- Poetry/uv for Python dependency management; GPLv3 compliance documented (OMRChecker)

---

## 6. Technical Architecture

### 6.1 High-Level Design
```
┌──────────────────────┐     HTTP/JSON      ┌──────────────────────┐
│   Laravel (PHP)      │ ◄───────────────── │  Python Microservice  │
│                      │                    │                      │
│  - Upload management │   POST /process    │  - OMRChecker/OpenCV │
│  - Answer key CRUD   │   image=base64     │  - Perspective corr. │
│  - Flag review UI    │                    │  - Bubble detection  │
│  - Score publication │   ← {answers,      │  - Confidence scoring│
│  - Audit trail       │     confidence,    │  - JSON output       │
│                      │     flags}         │                      │
└──────────────────────┘                    └──────────────────────┘
```

### 6.2 Processing Pipeline
```
Upload Image → Validate → Send to Python → 
  1. Grayscale (cv2.cvtColor)
  2. Noise Reduction (cv2.GaussianBlur)
  3. Adaptive Threshold (cv2.adaptiveThreshold)
  4. Find Contours (cv2.findContours)
  5. Detect 4 Anchor Corners
  6. Perspective Transform (cv2.warpPerspective)
  7. Locate Bubble Grid ROIs
  8. Classify Each Bubble (pixel count in ROI)
  9. Compare Against Answer Key
  10. Compute Confidence per Item
  11. Return JSON →
Laravel stores results → Flagged items shown in UI → Counselor reviews → Publish → ApplicantScore created
```

### 6.3 Library Decision: OMRChecker (Python)
OMRChecker is the documented library in the IPO (Input item #8). It provides:
- Built-in perspective correction with 4-corner anchor detection
- `template.json` layout definition — template = sheet format
- Per-bubble confidence scoring
- Section-wise scoring
- GPLv3 license (compatible with academic use)

**Fallback**: Custom OpenCV pipeline if OMRChecker template requirements don't match ICAT sheet format.

---

## 7. Existing Integration Points

| Existing Component | How OMR Connects |
|---|---|
| `AptitudeArea` | OMR sub-test scores map to active aptitudes by `code` |
| `ApplicantScore` | OMR output writes directly to this model |
| `GradingSession` | OMR processing lives inside a grading session workflow |
| `ScoreImportService` | OMR replaces CSV parse step; reuses `importSelectedScores` transaction logic |
| `ConsultationSummary` | Auto-created when all areas scored (reuse existing trigger) |
| `ApplicationPipelineService` | Status transition to `scored` (reuse existing) |
| `AuditLog` | Every flag resolution logged with HMAC chaining |
| SystemSetting::enableNormalizedScores() | Controls normalized/percentile conversion path |

---

## 8. User Journeys

### UJ-1: Batch Scoring (Guidance Counselor)
1. Counselor logs in → navigates to OMR Scoring → selects active grading session
2. Uploads 45 scanned answer sheets (JPEG from office scanner)
3. System processes: progress bar shows "Processing 45 sheets..."
4. Results appear in table: 42 ✅ scored, 3 ⚠️ flagged
5. Counselor opens flagged items — sees zoomed bubbles for smudge on Q14, half-erased mark on Q23
6. Accepts system predictions for two; overrides one from B→C
7. Clicks "Publish Scores" — all scores written, grading session advances to `review`

### UJ-2: Answer Key Setup (Guidance Admin)
1. Admin creates new exam session → configures answer key
2. For each `AptitudeArea`, enters correct answer per question number
3. Saves key — version recorded, old key preserved in audit trail
4. Key becomes active for all OMR uploads in that session

---

## 9. Success Metrics (TAM)

Measured through Guidance Personnel TAM items (n=2):

| Construct | Item | Target |
|---|---|---|
| PU-5 | "Automated OMR scoring saves time compared to manual stencil checking" | ≥ 4.0 / 5.0 |
| PEOU-8 | "It is easy to navigate AI-assisted scheduling, OMR scoring, and admission support in NEXIAM" | ≥ 3.5 / 5.0 |

**Counter-metric**: If perceived ease of use < 3.0, flag review workflow needs UX iteration.

---

## 10. Dependencies & Prerequisites

| Dependency | Owner | Status |
|---|---|---|
| **Answer sheet template design** (anchor corners, bubble grid, applicant ID field) | Design | 🔴 Not started |
| OMRChecker Python environment on server | DevOps | 🔴 Not started |
| Python microservice scaffold + API contract | Dev | 🔴 Not started |
| ICAT answer sheet sample (photo from Guidance Office) | Research | 🟡 G1 interview question pending |
| Confidence threshold default validation | Dev | 🟡 Configurable, default TBD |
| Answer key database migration | Dev | 🔴 Not started |

---

## 11. Open Questions

| # | Question | Resolution |
|---|---|---|
| OQ-1 | Does the existing ICAT answer sheet have printed anchor corners, or do we need to redesign? | Awaiting Guidance Office sample photo (interview G1) |
| OQ-2 | Is OMRChecker's `template.json` format compatible with ICAT's bubble layout, or do we need custom OpenCV? | Assess after obtaining sheet sample |
| OQ-3 | Who reviews flagged items — Guidance Counselor or Guidance Admin? | Interview G2 — default: Counselor |
| OQ-4 | Should flagged items block batch publication, or publish confidently-scored items first? | Interview G2 — default: block until all flags resolved |

---

## 12. Future Considerations (Addendum-Worthy)

- Answer sheet generation tool (print own bubble sheets with anchor corners)
- Mobile phone capture support (camera-based — OMRChecker handles phone photos at ~90% accuracy)
- Per-question difficulty tracking (aggregate across sessions)
- Multi-version answer keys per session (form A / form B)
