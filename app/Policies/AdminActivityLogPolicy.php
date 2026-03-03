<?php

namespace App\Policies;

use App\Models\User;

class AdminActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === User::ROLE_SUPER_ADMIN;
    }
}
