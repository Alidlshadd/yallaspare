<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermission(User::PERMISSION_USERS_VIEW);
    }

    public function manageUsers(User $authUser): bool
    {
        return $authUser->canManageUsers();
    }

    public function manageDealers(User $authUser): bool
    {
        return $authUser->canManageDealers();
    }

    public function updateRole(User $authUser, User $targetUser): bool
    {
        return $authUser->canManageUsers();
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        return $authUser->canManageUsers();
    }
}
