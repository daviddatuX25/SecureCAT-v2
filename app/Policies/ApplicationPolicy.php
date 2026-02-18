<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * Roles that can view applications list and details.
     * Per 05-SECURITY-CONTROLS: staff, admin, counselor, super_admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin', 'counselor']);
    }

    public function view(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin', 'counselor']);
    }
}
