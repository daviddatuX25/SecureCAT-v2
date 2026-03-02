<?php

namespace App\Policies;

use App\Models\ExamDomain;
use App\Models\User;

/**
 * Exam pillars (exam domains) are managed by test administrator and super_admin only, not registrar admin.
 */
class ExamDomainPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'test_administrator']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'test_administrator']);
    }

    public function update(User $user, ExamDomain $examDomain): bool
    {
        return $user->hasAnyRole(['super_admin', 'test_administrator']);
    }
}
