<?php

namespace App\Policies;

use App\Models\User;

class AdminActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_ACTIVITY_LOGS_VIEW);
    }
}
