<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");

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

        return view('admin.users.show', [
            'user' => $user,
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
        ]);
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
            return back()->with('error', 'You cannot demote your own super admin account.');
        }

        if (
            $user->role === User::ROLE_SUPER_ADMIN &&
            $newRole !== User::ROLE_SUPER_ADMIN &&
            User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1
        ) {
            return back()->with('error', 'At least one super admin account must remain.');
        }

        $user->update([
            'role' => $newRole,
        ]);

        return back()->with('success', 'User role updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === User::ROLE_SUPER_ADMIN && User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1) {
            return back()->with('error', 'Cannot delete the last super admin account.');
        }

        if ($user->orders()->exists()) {
            return back()->with('error', 'Cannot delete users with existing orders.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
