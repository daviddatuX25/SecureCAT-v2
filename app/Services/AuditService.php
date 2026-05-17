<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Keys that must never be stored in old_values/new_values.
     * Per 05-SECURITY-CONTROLS §7.
     */
    protected static array $redactKeys = [
        'password',
        'password_confirmation',
        'token',
        'setup_token',
        'remember_token',
        'api_token',
    ];

    /**
     * Map of technical event names to human-readable labels.
     * Used for UI display and auto-summary generation.
     *
     * Format: 'event_prefix.action' => ['label' => 'User-friendly label', 'action' => 'past tense verb']
     */
    protected static array $eventLabels = [
        // Authentication events
        'user.login' => ['label' => 'Staff login', 'action' => 'logged in'],
        'user.login_failed' => ['label' => 'Failed staff login', 'action' => 'failed to log in'],
        'user.logout' => ['label' => 'Staff logout', 'action' => 'logged out'],
        'user.created' => ['label' => 'User created', 'action' => 'created a user account'],
        'user.updated' => ['label' => 'User updated', 'action' => 'updated a user account'],
        'user.deleted' => ['label' => 'User deleted', 'action' => 'deleted a user account'],

        // Applicant authentication events
        'applicant.login' => ['label' => 'Applicant login', 'action' => 'logged in'],
        'applicant.login_failed' => ['label' => 'Failed applicant login', 'action' => 'failed to log in'],
        'applicant.logout' => ['label' => 'Applicant logout', 'action' => 'logged out'],
        'applicant.setup_completed' => ['label' => 'Profile setup completed', 'action' => 'completed profile setup'],
        'applicant.password_reset' => ['label' => 'Password reset', 'action' => 'reset their password'],
        'applicant.registered' => ['label' => 'Applicant registered', 'action' => 'registered an account'],

        // Application events
        'application.created' => ['label' => 'Application created', 'action' => 'submitted an application'],
        'application.updated' => ['label' => 'Application updated', 'action' => 'updated an application'],
        'application.deleted' => ['label' => 'Application deleted', 'action' => 'deleted an application'],
        'application.status_changed' => ['label' => 'Application status changed', 'action' => 'changed application status'],

        // Exam session events
        'exam_session.created' => ['label' => 'Exam session created', 'action' => 'created an exam session'],
        'exam_session.updated' => ['label' => 'Exam session updated', 'action' => 'updated an exam session'],
        'exam_session.deleted' => ['label' => 'Exam session deleted', 'action' => 'deleted an exam session'],
        'exam_session.started' => ['label' => 'Exam started', 'action' => 'started an exam'],
        'exam_session.completed' => ['label' => 'Exam completed', 'action' => 'completed an exam'],
        'exam_session.archived' => ['label' => 'Exam archived', 'action' => 'archived an exam'],

        // Attendance events
        'attendance.recorded' => ['label' => 'Attendance recorded', 'action' => 'recorded attendance'],
        'attendance.updated' => ['label' => 'Attendance updated', 'action' => 'updated attendance'],

        // Submission events
        'submission.created' => ['label' => 'Answer submitted', 'action' => 'submitted an answer'],
        'submission.updated' => ['label' => 'Answer updated', 'action' => 'updated an answer'],

        // Grading events
        'score.entered' => ['label' => 'Score entered', 'action' => 'entered a score'],
        'score.updated' => ['label' => 'Score updated', 'action' => 'updated a score'],
        'grading_session.finalized' => ['label' => 'Grading finalized', 'action' => 'finalized grading'],

        // Release events
        'consultation.released' => ['label' => 'Results released', 'action' => 'released consultation results'],

        // Role management events
        'role.created' => ['label' => 'Role created', 'action' => 'created a role'],
        'role.updated' => ['label' => 'Role updated', 'action' => 'updated a role'],
        'role.deleted' => ['label' => 'Role deleted', 'action' => 'deleted a role'],
        'role.assigned' => ['label' => 'Role assigned', 'action' => 'assigned a role'],

        // Audit log events
        'audit_log.viewed' => ['label' => 'Audit log viewed', 'action' => 'viewed audit log'],
        'audit_log.exported' => ['label' => 'Audit log exported', 'action' => 'exported audit log'],
    ];

    /**
     * Map of category codes to human-readable labels.
     */
    protected static array $categoryLabels = [
        'auth' => 'Authentication',
        'application' => 'Applications',
        'exam_session' => 'Exam Sessions',
        'grading' => 'Grading',
        'consultation' => 'Release',
        'user_management' => 'User Management',
        'system' => 'System',
        'other' => 'Other',
    ];

    /**
     * Get human-readable label for an event.
     */
    public static function getEventLabel(string $event): string
    {
        return self::$eventLabels[$event]['label'] ?? $event;
    }

    /**
     * Get human-readable label for a category.
     */
    public static function getCategoryLabel(string $category): string
    {
        return self::$categoryLabels[$category] ?? $category;
    }

    /**
     * Get all events with their labels, sorted by label.
     * For UI dropdowns.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function getEventOptions(): array
    {
        $options = [];
        foreach (self::$eventLabels as $value => $data) {
            $options[] = ['value' => $value, 'label' => $data['label']];
        }
        usort($options, fn ($a, $b) => $a['label'] <=> $b['label']);

        return $options;
    }

    /**
     * Get all categories with their labels.
     * For UI dropdowns.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function getCategoryOptions(): array
    {
        $options = [];
        foreach (self::$categoryLabels as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        usort($options, fn ($a, $b) => $a['label'] <=> $b['label']);

        return $options;
    }

    /**
     * Log an audit event. Insert-only; no update/delete.
     * Auto-generates summary from event if not provided.
     */
    public function log(
        string $event,
        ?string $auditableType = null,
        ?int $auditableId = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $summary = null
    ): void {
        $actorType = null;
        $actorId = null;

        if (auth()->check()) {
            $user = auth()->user();
            $actorType = $user->getMorphClass();
            $actorId = $user->getKey();
        } elseif (auth()->guard('applicant')->check()) {
            $applicant = auth()->guard('applicant')->user();
            $actorType = $applicant->getMorphClass();
            $actorId = $applicant->getKey();
        }

        $request = Request::instance();
        $ipAddress = $request ? $request->ip() : null;
        $userAgent = $request && $request->userAgent() ? $request->userAgent() : null;

        $category = $this->categoryForEvent($event);
        $oldValues = $this->redact($oldValues);
        $newValues = $this->redact($newValues);

        // Auto-generate summary if not provided
        $summary ??= $this->generateSummary($event, $newValues);

        AuditLog::create([
            'auditable_type' => $auditableType ?? '',
            'auditable_id' => $auditableId ?? 0,
            'event' => $event,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'category' => $category,
            'summary' => $summary,
        ]);
    }

    /**
     * Generate a human-readable summary from event and values.
     */
    protected function generateSummary(string $event, array $newValues): string
    {
        $action = self::$eventLabels[$event]['action'] ?? 'performed an action';
        $actorName = $newValues['name'] ?? $newValues['email'] ?? null;

        if ($actorName) {
            return "User {$action}: {$actorName}";
        }

        return ucfirst($action);
    }

    protected function redact(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $lower = strtolower((string) $key);
            if (in_array($lower, self::$redactKeys, true)) {
                continue;
            }
            if (is_array($value)) {
                $out[$key] = $this->redact($value);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    protected function categoryForEvent(string $event): string
    {
        $prefix = explode('.', $event)[0] ?? '';
        $map = [
            'user' => 'auth',
            'applicant' => 'auth',
            'application' => 'application',
            'attendance' => 'exam_session',
            'submission' => 'exam_session',
            'session' => 'exam_session',
            'score' => 'grading',
            'grading_session' => 'grading',
            'consultation' => 'consultation',
            'role' => 'user_management',
            'audit_log' => 'system',
            'exam_session' => 'exam_session',
        ];

        return $map[$prefix] ?? 'other';
    }
}
