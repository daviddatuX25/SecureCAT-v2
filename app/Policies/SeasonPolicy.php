<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;

class SeasonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, Season $season): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function activate(User $user, Season $season): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
