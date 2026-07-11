<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\PhoneNumber;
use App\Services\Security\UserPrivilegeService;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));

        $managerRoles = [
            User::ROLE_PRODUCT_MANAGER,
            User::ROLE_ORDER_MANAGER,
            User::ROLE_FINANCE_MANAGER,
            User::ROLE_INVENTORY_MANAGER,
            User::ROLE_SETTINGS_MANAGER,
        ];

        $filter = (string) $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'super_admin', 'admin', 'manager', 'dealer', 'user', 'verified', 'unverified'], true)) {
            $filter = 'all';
        }

        $usersQuery = User::query();

        match ($filter) {
            'super_admin' => $usersQuery->where('role', User::ROLE_SUPER_ADMIN),
            'admin' => $usersQuery->where('role', User::ROLE_ADMIN),
            'manager' => $usersQuery->whereIn('role', $managerRoles),
            'dealer' => $usersQuery->where('role', User::ROLE_DEALER),
            'user' => $usersQuery->where(function (Builder $query) {
                $query
                    ->where('role', User::ROLE_USER)
                    ->orWhere('role', 'customer')
                    ->orWhereNull('role');
            }),
            'verified' => $usersQuery->whereNotNull('email_verified_at'),
            'unverified' => $usersQuery->whereNull('email_verified_at'),
            default => $usersQuery,
        };

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                SqlSafe::whereLike($query, 'name', $search);
                SqlSafe::orWhereLike($query, 'email', $search);
                SqlSafe::orWhereLike($query, 'phone', $search);
                SqlSafe::orWhereLike($query, 'role', $search);

                if (is_numeric($search)) {
                    $query->orWhere('id', (int) $search);
                }

                $normalizedSearch = strtolower($search);
                if (in_array($normalizedSearch, ['admin', 'administrator'], true)) {
                    $query->orWhere(function (Builder $adminQuery) {
                        $adminQuery
                            ->where('role', User::ROLE_ADMIN)
                            ->orWhere('role', User::ROLE_SUPER_ADMIN);
                    });
                }
                if (in_array($normalizedSearch, ['super_admin', 'superadmin', 'super-admin', 'super admin'], true)) {
                    $query->orWhere('role', User::ROLE_SUPER_ADMIN);
                }
                if (in_array($normalizedSearch, ['dealer'], true)) {
                    $query->orWhere('role', User::ROLE_DEALER);
                }
                if (in_array($normalizedSearch, ['user', 'customer', 'member'], true)) {
                    $query->orWhere(function (Builder $userQuery) {
                        $userQuery
                            ->where('role', User::ROLE_USER)
                            ->orWhere('role', 'customer')
                            ->orWhereNull('role');
                    });
                }
            });
        }

        $users = $usersQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalUsers = User::count();
        $superAdminUsers = User::where('role', User::ROLE_SUPER_ADMIN)->count();
        $adminUsers = User::where('role', User::ROLE_ADMIN)->count();
        $managerUsers = User::whereIn('role', $managerRoles)->count();
        $dealerUsers = User::where('role', User::ROLE_DEALER)->count();
        $regularUsers = User::query()
            ->where('role', User::ROLE_USER)
            ->orWhere('role', 'customer')
            ->orWhereNull('role')
            ->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = $totalUsers - $verifiedUsers;
        $roleOptions = User::allowedRoles();
        $currentUserId = (int) $request->user()->id;

        return view('admin.users.index', compact(
            'users',
            'search',
            'filter',
            'totalUsers',
            'superAdminUsers',
            'adminUsers',
            'managerUsers',
            'dealerUsers',
            'regularUsers',
            'verifiedUsers',
            'unverifiedUsers',
            'roleOptions',
            'currentUserId'
        ));
    }

    public function show(User $user): View
    {
        $this->authorize('manage-users');

        $statusCounts = $user->orders()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentOrders = $user->orders()
            ->select(['id', 'order_number', 'status', 'total_amount', 'created_at'])
            ->latest('id')
            ->limit(8)
            ->get();

        $lastOrder = $user->orders()
            ->select(['id', 'created_at'])
            ->latest('created_at')
            ->first();

        $userReviews = $user->productReviews()
            ->with('product:id,name_en,name_ar,name_ku,sku,slug,image')
            ->latest('reviewed_at')
            ->latest('id')
            ->get();

        return view('admin.users.show', [
            'user' => $user,
            'roleOptions' => User::allowedRoles(),
            'dealerStatuses' => User::allowedDealerStatuses(),
            'permissionGroups' => User::permissionGroups(),
            'stats' => [
                'orders_total' => (int) $statusCounts->sum(),
                'orders_pending' => (int) ($statusCounts['pending'] ?? 0),
                'orders_processing' => (int) ($statusCounts['processing'] ?? 0),
                'orders_shipped' => (int) ($statusCounts['shipped'] ?? 0),
                'orders_delivered' => (int) ($statusCounts['delivered'] ?? 0),
                'orders_cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
                'spent_total' => (float) $user->orders()->sum('total_amount'),
                'last_order_at' => $lastOrder?->created_at,
            ],
            'recentOrders' => $recentOrders,
            'userReviews' => $userReviews,
        ]);
    }

    public function updateDetails(Request $request, User $user, UserPrivilegeService $privileges): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', new PhoneNumber(), User::uniquePhoneRule($user->id)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'role' => ['required', 'string', 'in:' . implode(',', User::allowedRoles())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(User::allowedPermissions())],
            'dealer_status' => ['nullable', 'string', 'in:' . implode(',', User::allowedDealerStatuses())],
            'dealer_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $newRole = User::normalizeRole($data['role']);
        $authUser = $request->user();

        try {
            $privileges->assertCanAssignRoleAndPermissions($authUser, $user, $newRole, $data['permissions'] ?? []);
        } catch (AuthorizationException $exception) {
            AdminLogger::log('security.role_permission_change_blocked', $user, [
                'attempted_role' => $newRole,
                'attempted_permissions' => User::normalizePermissions($data['permissions'] ?? []),
                'actor_role' => $authUser->role,
            ]);

            return back()->with('error', $exception->getMessage());
        }

        if ((int) $authUser->id === (int) $user->id && $newRole !== User::ROLE_SUPER_ADMIN) {
            return back()->with('error', __('You cannot demote your own super admin account.'));
        }

        if (
            $user->role === User::ROLE_SUPER_ADMIN &&
            $newRole !== User::ROLE_SUPER_ADMIN &&
            User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1
        ) {
            return back()->with('error', __('At least one super admin account must remain.'));
        }

        $dealerStatus = $newRole === User::ROLE_DEALER
            ? ($data['dealer_status'] ?? User::DEALER_STATUS_INACTIVE)
            : User::DEALER_STATUS_INACTIVE;

        $dealerDiscount = $newRole === User::ROLE_DEALER
            ? round((float) ($data['dealer_discount'] ?? 0), 2)
            : 0;
        $permissions = $privileges->permissionsForAssignment($authUser, $newRole, $data['permissions'] ?? []);

        $user->fill([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
        ]);

        // Privilege/role fields are guarded against mass assignment; set explicitly
        // after validation against allowlists above.
        $user->forceFill([
            'role' => $newRole,
            'permissions' => $permissions,
            'dealer_status' => $dealerStatus,
            'dealer_discount' => $dealerDiscount,
            'email_verified_at' => $request->boolean('email_verified')
                ? ($user->email_verified_at ?? now())
                : null,
        ])->save();

        return back()->with('success', __('User details updated successfully.'));
    }

    public function updateRole(Request $request, User $user, UserPrivilegeService $privileges): RedirectResponse
    {
        $this->authorize('updateRole', $user);

        $data = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', User::allowedRoles())],
        ]);

        $newRole = User::normalizeRole($data['role']);
        $authUser = $request->user();

        try {
            $privileges->assertCanAssignRoleAndPermissions($authUser, $user, $newRole);
        } catch (AuthorizationException $exception) {
            AdminLogger::log('security.role_permission_change_blocked', $user, [
                'attempted_role' => $newRole,
                'actor_role' => $authUser->role,
            ]);

            return back()->with('error', $exception->getMessage());
        }

        if ((int) $authUser->id === (int) $user->id && $newRole !== User::ROLE_SUPER_ADMIN) {
            return back()->with('error', __('You cannot demote your own super admin account.'));
        }

        if (
            $user->role === User::ROLE_SUPER_ADMIN &&
            $newRole !== User::ROLE_SUPER_ADMIN &&
            User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1
        ) {
            return back()->with('error', __('At least one super admin account must remain.'));
        }

        $user->forceFill([
            'role' => $newRole,
            'permissions' => null,
        ])->save();

        return back()->with('success', __('User role updated successfully.'));
    }

    public function updatePassword(Request $request, User $user, UserPrivilegeService $privileges): RedirectResponse
    {
        $this->authorize('manage-users');
        $actor = $request->user();

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $privileges->assertCanResetPassword($actor, $user);
        } catch (AuthorizationException $exception) {
            AdminLogger::log('security.password_reset_blocked', $user, [
                'actor_role' => $actor?->role,
                'target_role' => $user->role,
            ]);

            return back()->with('error', $exception->getMessage());
        }

        DB::transaction(function () use ($user, $data, $actor): void {
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'remember_token' => Str::random(60),
            ])->save();

            // A privileged password reset must invalidate every active session
            // and token for the target account, otherwise an attacker who already
            // holds a session can continue using it after the password changes.
            $user->tokens()->delete();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            AdminLogger::log('security.password_reset_completed', $user, [
                'actor_role' => $actor?->role,
                'target_role' => $user->role,
                'sessions_revoked' => Schema::hasTable('sessions'),
                'sanctum_tokens_revoked' => true,
            ]);
        });

        return back()->with('success', __('User password updated successfully.'));
    }

    public function updateBan(Request $request, User $user, UserPrivilegeService $privileges): RedirectResponse
    {
        $this->authorize('manageUsers', User::class);
        $actor = $request->user();

        try {
            $privileges->assertCanBan($actor, $user);
        } catch (AuthorizationException $exception) {
            AdminLogger::log('security.user_ban_blocked', $user, [
                'actor_role' => $actor?->role,
                'target_role' => $user->role,
            ]);

            return back()->with('error', $exception->getMessage());
        }

        if ((int) $actor->id === (int) $user->id) {
            return back()->with('error', __('You cannot ban your own account.'));
        }

        if (
            $user->role === User::ROLE_SUPER_ADMIN
            && ! User::query()
                ->where('role', User::ROLE_SUPER_ADMIN)
                ->whereKeyNot($user->getKey())
                ->where(function (Builder $query): void {
                    $query->whereNull('banned_at')
                        ->orWhere('banned_until', '<=', now());
                })
                ->exists()
        ) {
            return back()->with('error', __('At least one active super admin account must remain.'));
        }

        $data = $request->validate([
            'ban_type' => ['required', Rule::in(['temporary', 'permanent'])],
            'duration_days' => ['required_if:ban_type,temporary', 'nullable', 'integer', Rule::in([1, 7, 30, 90, 365])],
            'ban_reason' => ['required', 'string', 'max:500'],
        ]);

        $bannedAt = now();
        $bannedUntil = $data['ban_type'] === 'temporary'
            ? $bannedAt->copy()->addDays((int) $data['duration_days'])
            : null;

        DB::transaction(function () use ($user, $data, $bannedAt, $bannedUntil): void {
            $user->forceFill([
                'banned_at' => $bannedAt,
                'banned_until' => $bannedUntil,
                'ban_reason' => trim($data['ban_reason']),
                'remember_token' => Str::random(60),
            ])->save();

            $user->tokens()->delete();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        AdminLogger::log('security.user_banned', $user, [
            'ban_type' => $data['ban_type'],
            'banned_until' => $bannedUntil?->toIso8601String(),
            'sessions_revoked' => Schema::hasTable('sessions'),
            'sanctum_tokens_revoked' => true,
        ]);

        return back()->with('success', $data['ban_type'] === 'permanent'
            ? __('User permanently banned.')
            : __('User temporarily banned until :date.', ['date' => $bannedUntil?->format('d M Y H:i')]));
    }

    public function destroyBan(Request $request, User $user, UserPrivilegeService $privileges): RedirectResponse
    {
        $this->authorize('manageUsers', User::class);
        $actor = $request->user();

        try {
            $privileges->assertCanBan($actor, $user);
        } catch (AuthorizationException $exception) {
            AdminLogger::log('security.user_unban_blocked', $user, [
                'actor_role' => $actor?->role,
                'target_role' => $user->role,
            ]);

            return back()->with('error', $exception->getMessage());
        }

        $user->forceFill([
            'banned_at' => null,
            'banned_until' => null,
            'ban_reason' => null,
        ])->save();

        AdminLogger::log('security.user_unbanned', $user, [
            'actor_role' => $actor?->role,
            'target_role' => $user->role,
        ]);

        return back()->with('success', __('User ban removed.'));
    }

    public function destroy(Request $request, User $user, UserPrivilegeService $privileges): RedirectResponse
    {
        $this->authorize('delete', $user);
        $actor = $request->user();

        try {
            $privileges->assertCanDelete($actor, $user);
        } catch (AuthorizationException $exception) {
            AdminLogger::log('security.user_delete_blocked', $user, [
                'actor_role' => $actor?->role,
                'target_role' => $user->role,
            ]);

            return back()->with('error', $exception->getMessage());
        }

        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        if ($user->role === User::ROLE_SUPER_ADMIN && User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1) {
            return back()->with('error', __('Cannot delete the last super admin account.'));
        }

        if ($user->orders()->exists()) {
            return back()->with('error', __('Cannot delete users with existing orders.'));
        }

        $user->delete();

        return back()->with('success', __('User deleted successfully.'));
    }

    public function exportExcel(Request $request)
    {
        try {
            return Excel::download(
                new UsersExport([
                    'role' => $request->query('role'),
                    'dealer_status' => $request->query('dealer_status'),
                ]),
                'users.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('Users Excel export failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('Failed to export users. Please try again.'));
        }
    }
}
