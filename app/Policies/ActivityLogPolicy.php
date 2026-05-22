<?php

namespace App\Policies;

use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_ACTIVITY_LOGS_VIEW);
    }
}
