# Solidify Pipeline Status Design

## Objective
To resolve the P0 Scalability issue identified in the architectural audit by eliminating the dynamic `pipelineStatus()` and `pipelineDetails()` Eloquent accessors on the `Application` model. We will replace these computationally expensive in-memory operations with native database columns that are kept perfectly in sync via an Event-Driven Observer Architecture.

## Architecture & Data Flow

### 1. Database Modifications
We will expand the existing `status` column on the `applications` table. Instead of being limited to pre-exam states, it will represent the full pipeline lifecycle:
*   `pending`
*   `accepted`
*   `draft_scheduled`
*   `scheduled`
*   `printed`
*   `attended`
*   `submitted`
*   `graded`
*   `released`
*   `dismissed`

We will also add a `status_milestones` JSON column to the `applications` table. This column will store timestamps for each milestone achieved (e.g., `{"accepted": "2026-05-17T12:00:00Z", "scheduled": "2026-05-18T09:00:00Z"}`), eliminating the need to query related pivot tables and logs dynamically.

### 2. Event-Driven Observer Pattern
To ensure the `status` and `status_milestones` columns always reflect the system's actual state without duplicating logic, we will implement an Event-Driven Observer pattern.

**Core Service:**
*   `ApplicationPipelineService`: A centralized service that defines the state machine rules and transitions an application safely between statuses.

**Observers (The Triggers):**
*   **`ExamSessionObserver`**: Triggers on status changes (e.g., `published`, `in_progress`). It finds all related applications and transitions them to `scheduled`.
*   **Action Hooks (SessionRosterController)**: Hooks into the attendance and submission logic to transition specific applicants to `attended` or `submitted`.
*   **`ApplicantScoreObserver`**: Triggers when scores are imported or manually saved to transition the application to `graded`.
*   **`ConsultationSummaryObserver`**: Triggers when a summary is marked as `released` to transition the application to `released`.

### 3. Safety & Fallback
We will implement an artisan command (`php artisan app:sync-pipeline-statuses`) that recalculates and explicitly updates the `status` and `status_milestones` for applications by tracing their source-of-truth relations. This serves as a self-healing fallback for database integrity.

### 4. Controller & Frontend Refactoring
*   **`ApplicationController::index`**: Will be refactored to use standard Eloquent pagination (`$query->orderBy('status')->paginate()`) at the database level, completely resolving the P0 memory exhaustion issue.
*   **Model**: The `pipelineStatus()` and `pipelineDetails()` methods will be removed. The `status_milestones` will be cast to an array.
*   **Frontend**: `resources/js/lib/pipeline-helpers.js` and pages like `Applications/Show.svelte` will map UI elements (badges, progress bars) directly to the new native `status` and `status_milestones` properties.

## Trade-offs
*   **Pros:** Real-time updates, massive performance improvement for listing and filtering applications, database acts as the single source of truth, adheres to enterprise Laravel state-machine patterns.
*   **Cons:** Requires setup of multiple observers and ensuring edge cases (like manually deleting scores) trigger the appropriate backwards transitions if necessary.
