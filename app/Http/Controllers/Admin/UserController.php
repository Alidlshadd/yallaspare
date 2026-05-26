<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SqlSafe;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));

        $usersQuery = User::query();

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
        $adminUsers = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orWhere('role', User::ROLE_SUPER_ADMIN)
            ->count();
        $dealerUsers = User::where('role', User::ROLE_DEALER)->count();
        $regularUsers = User::query()
            ->where('role', User::ROLE_USER)
            ->orWhere('role', 'customer')
            ->orWhereNull('role')
            ->count();
        $roleOptions = User::allowedRoles();
        $currentUserId = (int) $request->user()->id;

        return view('admin.users.index', compact(
            'users',
            'search',
            'totalUsers',
            'superAdminUsers',
            'adminUsers',
            'dealerUsers',
            'regularUsers',
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

    public function updateDetails(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().٠-٩۰-۹]+$/u', User::uniquePhoneRule($user->id)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'role' => ['required', 'string', 'in:' . implode(',', User::allowedRoles())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(User::allowedPermissions())],
            'dealer_status' => ['nullable', 'string', 'in:' . implode(',', User::allowedDealerStatuses())],
            'dealer_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $newRole = User::normalizeRole($data['role']);
        $authUser = $request->user();

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
        $permissions = $newRole === User::ROLE_SUPER_ADMIN
            ? null
            : User::normalizePermissions($data['permissions'] ?? []);

        $user->fill([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'role' => $newRole,
            'permissions' => $permissions,
            'dealer_status' => $dealerStatus,
            'dealer_discount' => $dealerDiscount,
        ]);

        $user->forceFill([
            'email_verified_at' => $request->boolean('email_verified')
                ? ($user->email_verified_at ?? now())
                : null,
        ])->save();

        return back()->with('success', __('User details updated successfully.'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->authorize('updateRole', $user);

        $data = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', User::allowedRoles())],
        ]);

        $newRole = User::normalizeRole($data['role']);
        $authUser = $request->user();

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

        $user->update([
            'role' => $newRole,
            'permissions' => null,
        ]);

        return back()->with('success', __('User role updated successfully.'));
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('success', __('User password updated successfully.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

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
}
