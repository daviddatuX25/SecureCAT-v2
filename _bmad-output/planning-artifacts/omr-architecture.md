# OMR Scanner — Architecture & Processing Pipeline

**Status:** Draft  
**Created:** 2026-07-21  
**PRD:** `openspec/specs/omr-scanner-prd.md`  
**Template:** `openspec/specs/omr-answer-sheet-template.md`  

---

## 1. System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        NEXIAM (Laravel)                             │
│                                                                     │
│  ┌──────────┐  ┌──────────────┐  ┌────────────┐  ┌──────────────┐ │
│  │ Upload   │  │ Flag Review  │  │ Score      │  │ Answer Key   │ │
│  │ Controller│  │ Controller   │  │ Publication│  │ Controller   │ │
│  └────┬─────┘  └──────┬───────┘  └─────┬──────┘  └──────┬───────┘ │
│       │               │                │                │         │
│  ┌────▼─────┐  ┌──────▼───────┐  ┌─────▼──────┐  ┌──────▼───────┐ │
│  │ OMR      │  │ Flag Review  │  │ Score      │  │ AnswerKey    │ │
│  │ Service  │  │ Service      │  │ Service    │  │ Service      │ │
│  └────┬─────┘  └──────┬───────┘  └─────┬──────┘  └──────────────┘ │
│       │               │                │                           │
│  ┌────▼────────────────▼────────────────▼──────────────────────┐  │
│  │                    Models                                     │  │
│  │  omr_processing_results  omr_flagged_items  answer_keys       │  │
│  │  ApplicantScore  GradingSession  ConsultationSummary          │  │
│  └──────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────┬──────────────────────────────┘
                                       │
                              HTTP POST /process
                              (image bytes + key_id)
                                       │
                              JSON response
                              {answers, confidence, flags}
                                       │
┌──────────────────────────────────────▼──────────────────────────────┐
│                   Python Microservice (OMRChecker)                  │
│                                                                     │
│  ┌─────────┐  ┌──────────┐  ┌──────────┐  ┌────────────────────┐  │
│  │ FastAPI │  │ OMRChecker│  │ Answer   │  │ Confidence         │  │
│  │ Endpoint│──│ Pipeline  │──│ Key Match│──│ Scorer + Flag Gen  │  │
│  └─────────┘  └──────────┘  └──────────┘  └────────────────────┘  │
│                                                                     │
│  Dependencies: opencv-python, numpy, omrchecker (or custom logic)  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. Component Design

### 2.1 Laravel Side

#### Controllers

| Controller | Routes | Purpose |
|---|---|---|
| `OmrUploadController` | `POST /omr/upload`, `GET /omr/batch/{id}` | Upload sheets, view batch status |
| `OmrFlagReviewController` | `GET /omr/flags`, `POST /omr/flags/{id}/resolve` | Review + resolve flagged items |
| `OmrPublishController` | `POST /omr/batch/{id}/publish` | Publish scores → `ApplicantScore` |
| `AnswerKeyController` | `CRUD /answer-keys` | Manage answer keys per exam session |

#### Services

| Service | Responsibility |
|---|---|
| `OmrProcessingService` | Orchestrates: call Python microservice → parse JSON → store `omr_processing_results` + `omr_flagged_items` |
| `OmrFlagReviewService` | Resolve flags (accept/override/skip), track review state, check all-flags-resolved gate |
| `OmrScorePublicationService` | On publish: write `ApplicantScore` records, transition `GradingSession`, trigger `ConsultationSummary` |
| `OmrImageValidationService` | Validate upload: format, DPI estimation, file size, basic quality check |

#### New Models + Migrations

| Model | Table | Key Columns |
|---|---|---|
| `AnswerKey` | `answer_keys` | `exam_session_id`, `aptitude_area_id`, `version`, `key_data` (JSON), `is_active` |
| `OmrProcessingResult` | `omr_processing_results` | `grading_session_id`, `applicant_id`, `sheet_image_path`, `raw_results` (JSON), `flag_count` |
| `OmrFlaggedItem` | `omr_flagged_items` | `processing_result_id`, `question_number`, `section_code`, `detected_answer`, `confidence_score`, `status`, `overridden_to` |

### 2.2 Python Microservice

**Stack**: FastAPI + OpenCV + NumPy + OMRChecker

| Endpoint | Method | Input | Output |
|---|---|---|---|
| `/health` | GET | — | `{"status": "ok"}` |
| `/process` | POST | `image` (multipart), `template_config` (JSON), `answer_key` (JSON) | `{"applicant_ref", "sections": [{ "code", "score", "max", "items": [{ "q", "detected", "correct", "confidence" }] }], "flagged_items": [{ "section", "q", "detected", "confidence" }] }` |
| `/process-batch` | POST | `images[]` (multipart array), ... | `{"results": [...]}` |

**Processing Pipeline** (OMRChecker-executed):

```
receive_image()
  → validate_format()
  → grayscale() [cv2.cvtColor]
  → denoise() [cv2.GaussianBlur]
  → binarize() [cv2.adaptiveThreshold]
  → find_contours() [cv2.findContours]
  → locate_registration_marks() [4 corner squares]
  → perspective_correct() [cv2.getPerspectiveTransform + cv2.warpPerspective]
  → For each section in template:
      → extract_bubble_grid_roi()
      → segment_into_question_rois()
      → for each question:
          → for each choice bubble:
              → count_filled_pixels()
              → classify_fill_status()
              → compute_confidence()
          → select_highest_confidence_choice()
      → compare_against_key()
      → calculate_section_score()
  → compile_flagged_items(confidence < threshold)
  → return_json()
```

---

## 3. Flag Review Workflow

### 3.1 State Machine

```
┌──────────┐     Counselor reviews     ┌──────────┐
│          │──────────────────────────►│          │
│  PENDING │   Zoom → see bubble ROI   │ RESOLVED │
│          │◄──────────────────────────│          │
└──────────┘     Re-open (rare)        └──────────┘
       │                                       │
       │  Three resolution actions:             │
       │                                       │
       ├──► ACCEPT    — keep system prediction  │
       ├──► OVERRIDE  — counselor picks diff    │
       └──► SKIP      — leave unscored          │
                                               │
       All flags resolved?                      │
       │                                        │
       ├── YES → Enable "Publish Scores" btn    │
       └── NO  → Block publication              │
```

### 3.2 UI Design (from Wireframe)

```
┌─────────────────────────────────────────────────────────────┐
│  OMR Scoring — Batch #3 (July 2026)   42 sheets             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─ Scoring Results ──────────────────────────────────────┐ │
│  │  Applicant ID  │ Math │ Logic │ Reading │ Subject │ Sts │ │
│  │  APP-0451      │ 85%  │ 72%   │ 90%     │ 68%     │ ✅  │ │
│  │  APP-0452      │ 62%  │ 55%   │ 78%     │ 80%     │ ⚠️  │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                             │
│  ┌─ Flagged for Review (1 item) ───────────────────────────┐ │
│  │                                                         │ │
│  │  [ Zoomed Bubble Grid — APP-0452, Question 14 ]         │ │
│  │                                                         │ │
│  │  Section: LOGIC     System: B (confidence: 0.62)       │ │
│  │                                                         │ │
│  │  ○ A    ● B    ○ C    ○ D    ○ E                       │ │
│  │   (smudge detected — partial fill)                      │ │
│  │                                                         │ │
│  │  [Accept (B)]  [Override (C)]  [Skip]                   │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                             │
│  [Publish Scores]  ← disabled until all flags resolved      │
└─────────────────────────────────────────────────────────────┘
```

### 3.3 Flag Resolution Actions

| Action | Effect | Audit Log Entry |
|---|---|---|
| **Accept** | Keep system's detected answer. Item status → `accepted` | `omr_flag_accepted: {item_id, section, q, answer, confidence, reviewer_id}` |
| **Override** | Counselor selects different answer. Item status → `overridden`, `overridden_to` set | `omr_flag_overridden: {item_id, section, q, from, to, reviewer_id}` |
| **Skip** | Question excluded from scoring. Item status → `skipped` | `omr_flag_skipped: {item_id, section, q, reviewer_id}` |

### 3.4 Score Calculation on Publish

For each applicant in the batch:

1. Load `omr_processing_results` for this applicant + grading session
2. For each `AptitudeArea`:
   - Count correctly answered questions (using final resolved answers)
   - `raw_score = correct_count`
   - `max_score = aptitudeArea.max_items`
   - Call `aptitudeArea.resolveScore(raw_score)` for normalized/percentile
   - Create `ApplicantScore` record (same columns as `ScoreImportService` pattern)
3. If all active `AptitudeArea` records scored:
   - Create/find `ConsultationSummary` with status `draft`
   - Transition application to `scored` via `ApplicationPipelineService`
4. Set `GradingSession.status = 'review'` (or `'finalized'` if no flags needed)

---

## 4. API Contract (Laravel ↔ Python Microservice)

### 4.1 POST /process

**Request** (multipart/form-data):
```
image: <file>
template: <json-string>  // OMRChecker template.json content
answer_key: <json-string> // {"math": {"1":"A","2":"C",...}, "logic": {...}}
```

**Response** (200):
```json
{
  "status": "success",
  "sections": [
    {
      "code": "math",
      "score": 17,
      "max": 20,
      "items": [
        {"q": 1, "detected": "A", "correct": "A", "confidence": 0.98, "flagged": false},
        {"q": 2, "detected": "B", "correct": "C", "confidence": 0.62, "flagged": true}
      ]
    }
  ],
  "flagged_items": [
    {"section": "math", "q": 2, "detected": "B", "confidence": 0.62, "reason": "low_confidence"}
  ],
  "processing_time_ms": 245
}
```

**Error Response** (422):
```json
{
  "status": "error",
  "error": "image_too_blurry",
  "detail": "Image resolution below minimum threshold (estimated <150 DPI)"
}
```

### 4.2 POST /process-batch

Same as `/process` but accepts `images[]` array. Returns array of per-sheet results.

---

## 5. Database Migrations

### 5.1 `create_answer_keys_table`
```php
Schema::create('answer_keys', function (Blueprint $table) {
    $table->id();
    $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
    $table->foreignId('aptitude_area_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('version')->default(1);
    $table->json('key_data');   // {"1":"A","2":"C","3":"B",...}
    $table->boolean('is_active')->default(true);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->unique(['exam_session_id', 'aptitude_area_id', 'version']);
});
```

### 5.2 `create_omr_processing_results_table`
```php
Schema::create('omr_processing_results', function (Blueprint $table) {
    $table->id();
    $table->foreignId('grading_session_id')->constrained()->cascadeOnDelete();
    $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
    $table->string('sheet_image_path');
    $table->json('raw_results');
    $table->json('confidence_summary');
    $table->unsignedInteger('flag_count')->default(0);
    $table->timestamp('flags_resolved_at')->nullable();
    $table->foreignId('flags_resolved_by')->nullable()->constrained('users');
    $table->timestamps();
});
```

### 5.3 `create_omr_flagged_items_table`
```php
Schema::create('omr_flagged_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('processing_result_id')->constrained('omr_processing_results')->cascadeOnDelete();
    $table->unsignedInteger('question_number');
    $table->string('section_code', 20);
    $table->string('detected_answer', 5)->nullable();
    $table->decimal('confidence_score', 5, 4);
    $table->string('status', 20)->default('pending'); // pending, accepted, overridden, skipped
    $table->string('overridden_to', 5)->nullable();
    $table->foreignId('reviewed_by')->nullable()->constrained('users');
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamps();

    $table->index(['processing_result_id', 'status']);
});
```

---

## 6. Error Handling

| Scenario | Detection | User Feedback |
|---|---|---|
| Blurry image | Python: estimate Laplacian variance → below threshold | "Image too blurry. Please re-scan at 300 DPI." |
| Wrong orientation | Python: can't find 4 registration marks | "Cannot detect answer sheet layout. Check scan orientation." |
| No applicant ID | Python: barcode/OCR fails | "Applicant ID not readable. Enter manually." |
| Partial sheet | Python: fewer sections detected than template expects | "Only 3 of 4 sections detected. Check sheet is complete." |
| Microservice down | Laravel: HTTP timeout | "Scoring service unavailable. Try again later." |

---

## 7. Deployment

### 7.1 Python Microservice
- **Runtime**: Python 3.11+, dependencies via `uv` or `pip`
- **Process manager**: Supervisor or Docker container alongside Laravel
- **Port**: 8001 (internal network only — not exposed to public)
- **Startup**: `uvicorn main:app --host 127.0.0.1 --port 8001`

### 7.2 Laravel Integration
- **Config**: `config/omr.php` — microservice URL, timeout, confidence defaults
- **Queue**: Processing dispatched to queue for large batches
- **Timeout**: 30s per sheet, 120s per batch of 50

---

## 8. Sequence: End-to-End Flow

```
Counselor               Laravel                 Python               Database
   │                       │                       │                    │
   │  Upload 50 sheets     │                       │                    │
   │──────────────────────►│                       │                    │
   │                       │  Validate images      │                    │
   │                       │  Dispatch batch job   │                    │
   │                       │──────────────────────►│                    │
   │                       │  POST /process-batch  │                    │
   │                       │    (50 images)        │                    │
   │                       │                       │  OMRChecker flow   │
   │                       │                       │  per sheet:        │
   │                       │                       │   corner detect    │
   │                       │                       │   perspective corr │
   │                       │                       │   bubble detect    │
   │                       │                       │   key match        │
   │                       │                       │   confidence calc  │
   │                       │                       │                    │
   │                       │  ← JSON results       │                    │
   │                       │                       │                    │
   │                       │  Store results        │──────────────────► │
   │                       │  Create flagged items │   omr_processing   │
   │                       │                       │   _results         │
   │                       │                       │   omr_flagged      │
   │                       │                       │   _items           │
   │                       │                       │                    │
   │  ← Batch complete     │                       │                    │
   │                       │                       │                    │
   │  Open Flag Review     │                       │                    │
   │──────────────────────►│                       │                    │
   │  See 3 flagged items  │  Fetch flags          │                    │
   │  Accept 2, Override 1 │──────────────────────►│                    │
   │                       │  Update flag status   │──────────────────► │
   │                       │                       │                    │
   │  Click Publish        │                       │                    │
   │──────────────────────►│                       │                    │
   │                       │  Compute scores       │                    │
   │                       │  Create ApplicantScore│──────────────────► │
   │                       │  Transition Grading   │                    │
   │                       │  Session → review     │──────────────────► │
   │                       │  Trigger Consultation │                    │
   │                       │  Summary              │──────────────────► │
   │                       │                       │                    │
   │  ← Scores published   │                       │                    │
```
