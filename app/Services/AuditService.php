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
     * Log an audit event. Insert-only; no update/delete.
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
        ];
        return $map[$prefix] ?? 'other';
    }
}
