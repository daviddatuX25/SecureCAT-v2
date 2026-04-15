<?php

namespace App\Policies;

use App\Models\ExamSession;
use App\Models\User;

/**
 * Per 05-SECURITY-CONTROLS and 08-API-SPEC: super_admin, registrar_administrator for CRUD;
 * proctor can view assigned only; test_administrator can view for monitoring/grading.
 */
class ExamSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator', 'test_administrator', 'proctor']);
    }

    public function view(User $user, ExamSession $examSession): bool
    {
        if ($user->hasAnyRole(['super_admin', 'registrar_administrator'])) {
            return true;
        }
        if ($user->hasRole('test_administrator')) {
            return true;
        }
        if ($user->hasRole('proctor')) {
            return $examSession->proctors()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }

    public function update(User $user, ExamSession $examSession): bool
    {
        if ($examSession->status === ExamSession::STATUS_COMPLETED) {
            return false;
        }
        if ($examSession->status === ExamSession::STATUS_CANCELLED) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }

    public function delete(User $user, ExamSession $examSession): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }

    /** Roster page and roster data: proctor (assigned), admin, super_admin. */
    public function viewRoster(User $user, ExamSession $examSession): bool
    {
        return $this->view($user, $examSession);
    }

    /** Attendance, submission, start, close: test_administrator, proctor (assigned), super_admin. */
    public function manageRoster(User $user, ExamSession $examSession): bool
    {
        if ($user->hasAnyRole(['super_admin', 'test_administrator'])) {
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

        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }

    /** Unpublish a published session (set back to draft). Admin/super_admin only; session must be published. */
    public function unpublish(User $user, ExamSession $examSession): bool
    {
        if ($examSession->status !== ExamSession::STATUS_PUBLISHED) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }
}
