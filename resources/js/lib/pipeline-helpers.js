/**
 * Shared helpers for applicant pipeline status display.
 * Used by Applications/Index, Applications/Show, and related pages.
 */

const PIPELINE_ORDER = {
  pending: 0,
  accepted: 1,
  draft_scheduled: 2,
  scheduled: 3,
  attended: 4,
  submitted: 5,
  graded: 6,
  dismissed: 7,
};

const PIPELINE_LABELS = {
  pending: 'Pending',
  accepted: 'Accepted',
  draft_scheduled: 'Draft Scheduled',
  scheduled: 'Scheduled',
  attended: 'Attended',
  submitted: 'Submitted',
  graded: 'Graded',
  dismissed: 'Dismissed',
};

const PIPELINE_VARIANTS = {
  pending: 'warning',
  accepted: 'success',
  draft_scheduled: 'muted',
  scheduled: 'outline',
  attended: 'info',
  submitted: 'info',
  graded: 'success',
  dismissed: 'danger',
};

const PIPELINE_ICONS = {
  pending: 'clock',
  accepted: 'check-circle',
  draft_scheduled: 'file-edit',
  scheduled: 'calendar',
  attended: 'user-check',
  submitted: 'send',
  graded: 'award',
  dismissed: 'x-circle',
};

/**
 * Returns the badge variant (color) for a pipeline status.
 * Maps to shadcn-svelte Badge variant names.
 */
export function pipelineBadgeVariant(status) {
  return PIPELINE_VARIANTS[status] ?? 'muted';
}

/**
 * Returns the human-readable label for a pipeline status.
 */
export function pipelineStatusLabel(status) {
  return PIPELINE_LABELS[status] ?? status;
}

/**
 * Returns the sort order number for a pipeline status.
 * Lower = earlier in pipeline.
 */
export function pipelineOrder(status) {
  return PIPELINE_ORDER[status] ?? 99;
}

/**
 * Returns the full list of pipeline statuses for filter dropdowns.
 */
export function pipelineStatusOptions() {
  return Object.entries(PIPELINE_LABELS).map(([value, label]) => ({ value, label }));
}

/**
 * Returns all pipeline milestones in order for the progress bar.
 */
export function pipelineMilestones() {
  return [
    { key: 'pending', label: 'Pending' },
    { key: 'accepted', label: 'Accepted' },
    { key: 'draft_scheduled', label: 'Draft' },
    { key: 'scheduled', label: 'Scheduled' },
    { key: 'attended', label: 'Attended' },
    { key: 'submitted', label: 'Submitted' },
    { key: 'graded', label: 'Graded' },
  ];
}
