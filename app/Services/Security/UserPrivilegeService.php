<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UserPrivilegeService
{
    public function assertCanAssignRoleAndPermissions(User $actor, User $target, string $role, array $permissions = []): void
    {
        $role = User::normalizeRole($role);
        $permissions = User::normalizePermissions($permissions);

        if ($actor->isSuperAdmin()) {
            return;
        }

        // Non-super user managers are limited to customer/dealer lifecycle
        // operations. Admin roles and explicit permission grants are reserved
        // for super admins because they unlock finance, settings, stock, and
        // audit capabilities.
        if (! $this->isCustomerOrDealerRole($target->role) || ! $this->isCustomerOrDealerRole($role)) {
            throw new AuthorizationException(__('Only super admins can assign admin roles.'));
        }

        if ((int) $actor->id === (int) $target->id && $role !== $target->role) {
            throw new AuthorizationException(__('You cannot change your own role.'));
        }

        if ($permissions !== []) {
            throw new AuthorizationException(__('Only super admins can assign admin permissions.'));
        }
    }

    public function permissionsForAssignment(User $actor, string $role, array $permissions = []): ?array
    {
        if (User::normalizeRole($role) === User::ROLE_SUPER_ADMIN) {
            return null;
        }

        if (! $actor->isSuperAdmin()) {
            return null;
        }

        return User::normalizePermissions($permissions);
    }

    public function assertCanResetPassword(User $actor, User $target): void
    {
        $this->assertCanPerformPrivilegedUserAction(
            $actor,
            $target,
            __('Only super admins can reset privileged account passwords.')
        );
    }

    public function assertCanDelete(User $actor, User $target): void
    {
        $this->assertCanPerformPrivilegedUserAction(
            $actor,
            $target,
            __('Only super admins can delete privileged accounts.')
        );
    }

    public function assertCanBan(User $actor, User $target): void
    {
        if (! in_array(User::normalizeRole($actor->role), [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true)) {
            throw new AuthorizationException(__('Only super admins and admins can ban user accounts.'));
        }

        $this->assertCanPerformPrivilegedUserAction(
            $actor,
            $target,
            __('Only super admins can suspend privileged accounts.')
        );
    }

    public function assertCanManageDealerLifecycle(User $actor, User $target): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ((int) $actor->id === (int) $target->id) {
            throw new AuthorizationException(__('You cannot change your own dealer lifecycle state.'));
        }

        if (! $this->isCustomerOrDealerRole($target->role) || $this->isPrivilegedAccount($target)) {
            throw new AuthorizationException(__('Only super admins can modify privileged accounts.'));
        }
    }

    private function assertCanPerformPrivilegedUserAction(User $actor, User $target, string $message): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if ((int) $actor->id === (int) $target->id) {
            throw new AuthorizationException(__('Use your own account security page to change your password.'));
        }

        if ($this->isPrivilegedAccount($target)) {
            throw new AuthorizationException($message);
        }
    }

    private function isPrivilegedAccount(User $user): bool
    {
        if (! $this->isCustomerOrDealerRole($user->role)) {
            return true;
        }

        return array_intersect($user->effectivePermissions(), [
            User::PERMISSION_FINANCE_MANAGE,
            User::PERMISSION_SETTINGS_MANAGE,
            User::PERMISSION_STOCK_MANAGE,
            User::PERMISSION_ACTIVITY_LOGS_VIEW,
            User::PERMISSION_USERS_MANAGE,
            User::PERMISSION_DEALERS_MANAGE,
        ]) !== [];
    }

    private function isCustomerOrDealerRole(string $role): bool
    {
        return in_array(User::normalizeRole($role), [User::ROLE_USER, User::ROLE_DEALER], true);
    }
}
