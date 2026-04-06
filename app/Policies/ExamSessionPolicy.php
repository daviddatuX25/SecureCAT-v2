<?php

namespace App\Policies;

use App\Models\ExamSession;
use App\Models\User;

/**
 * Per 05-SECURITY-CONTROLS and 08-API-SPEC: admin, super_admin for CRUD; proctor can view assigned only.
 * viewRoster / manageRoster: proctor (assigned), admin, super_admin for roster and attendance/submission/start/close.
 */
class ExamSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'proctor']);
    }

    public function view(User $user, ExamSession $examSession): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }
        if ($user->hasAnyRole(['proctor'])) {
            return $examSession->proctors()->where('users.id', $user->id)->exists();
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, ExamSession $examSession): bool
    {
        if ($examSession->status === ExamSession::STATUS_COMPLETED) {
            return false;
        }
        if ($examSession->status === ExamSession::STATUS_CANCELLED) {
            return false;
        }
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, ExamSession $examSession): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /** Roster page and roster data: proctor (assigned), admin, super_admin. */
    public function viewRoster(User $user, ExamSession $examSession): bool
    {
        return $this->view($user, $examSession);
    }

    /** Attendance, submission, start, close: proctor (assigned), admin, super_admin. */
    public function manageRoster(User $user, ExamSession $examSession): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }
        if ($user->hasAnyRole(['proctor'])) {
            return $examSession->proctors()->where('users.id', $user->id)->exists();
        }
        return false;
    }

    /** Reopen a completed session (set back to in_progress). Admin/super_admin only; session must be completed. */
    public function reopen(User $user, ExamSession $examSession): bool
    {
        if ($examSession->status !== ExamSession::STATUS_COMPLETED) {
            return false;
        }
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /** Unpublish a published session (set back to draft). Admin/super_admin only; session must be published. */
    public function unpublish(User $user, ExamSession $examSession): bool
    {
        if ($examSession->status !== ExamSession::STATUS_PUBLISHED) {
            return false;
        }
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
