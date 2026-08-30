<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_GOALS_VIEW);
    }

    public function view(User $user, Goal $goal): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(User::PERMISSION_GOALS_MANAGE);
    }

    public function update(User $user, Goal $goal): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $this->create($user);
    }
}
