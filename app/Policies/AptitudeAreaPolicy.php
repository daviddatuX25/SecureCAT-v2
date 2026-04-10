<?php

namespace App\Policies;

use App\Models\AptitudeArea;
use App\Models\User;

/**
 * Aptitude areas are managed by registrar_administrator and super_admin only.
 */
class AptitudeAreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }

    public function update(User $user, AptitudeArea $aptitudeArea): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }
}
