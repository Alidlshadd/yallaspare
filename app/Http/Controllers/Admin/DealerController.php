<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DealerController extends Controller
{
    private const PAID_STATUSES = [Order::STATUS_DELIVERED, 'completed'];

    public function index(Request $request): View
    {
        Gate::authorize('manage-dealers');

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $paidRevenueSum = fn ($query) => $query->whereIn('status', self::PAID_STATUSES);

        $dealersQuery = User::query()
            ->where('role', User::ROLE_DEALER)
            ->withCount('orders')
            ->withSum(['orders as paid_revenue' => $paidRevenueSum], 'total_amount');

        if ($search !== '') {
            $dealersQuery->where(function ($query) use ($search) {
                SqlSafe::whereLike($query, 'name', $search);
                SqlSafe::orWhereLike($query, 'email', $search);
                SqlSafe::orWhereLike($query, 'phone', $search);
            });
        }

        if ($status !== '' && in_array($status, User::allowedDealerStatuses(), true)) {
            $dealersQuery->where('dealer_status', $status);
        }

        $dealers = $dealersQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalDealers = User::where('role', User::ROLE_DEALER)->count();
        $activeDealers = User::where('role', User::ROLE_DEALER)->where('dealer_status', User::DEALER_STATUS_ACTIVE)->count();
        $inactiveDealers = User::where('role', User::ROLE_DEALER)->where('dealer_status', User::DEALER_STATUS_INACTIVE)->count();
        $suspendedDealers = User::where('role', User::ROLE_DEALER)->where('dealer_status', User::DEALER_STATUS_SUSPENDED)->count();
        $averageDiscount = (float) User::where('role', User::ROLE_DEALER)->avg('dealer_discount');

        // Read-only rollups for the command header and performance strip.
        $dealerOrdersTotal = Order::query()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('users.role', User::ROLE_DEALER)
            ->count();
        $dealerRevenue = (float) Order::query()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('users.role', User::ROLE_DEALER)
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->sum('orders.total_amount');

        $dealerBase = fn () => User::query()->where('role', User::ROLE_DEALER);
        $performance = [
            'top_orders' => $dealerBase()->withCount('orders')->orderByDesc('orders_count')->first(),
            'top_revenue' => $dealerBase()
                ->withSum(['orders as paid_revenue' => $paidRevenueSum], 'total_amount')
                ->orderByDesc('paid_revenue')
                ->first(),
            'newest' => $dealerBase()->latest()->first(),
            'top_discount' => $dealerBase()->orderByDesc('dealer_discount')->first(),
        ];

        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currency = [
            'label' => $currencyCode !== '' ? $currencyCode : $currencySymbol,
            'decimals' => strtoupper($currencyCode) === 'IQD' ? 0 : 2,
        ];

        return view('admin.dealers.index', compact(
            'dealers',
            'search',
            'status',
            'totalDealers',
            'activeDealers',
            'inactiveDealers',
            'suspendedDealers',
            'averageDiscount',
            'dealerOrdersTotal',
            'dealerRevenue',
            'performance',
            'currency'
        ));
    }

    public function update(Request $request, User $dealer): RedirectResponse
    {
        Gate::authorize('manage-dealers');

        if ($dealer->role !== User::ROLE_DEALER) {
            abort(404);
        }

        $data = $request->validate([
            'dealer_status' => ['required', 'in:' . implode(',', User::allowedDealerStatuses())],
            'dealer_discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $dealer->forceFill([
            'dealer_status' => $data['dealer_status'],
            'dealer_discount' => round((float) $data['dealer_discount'], 2),
        ])->save();

        return back()->with('success', __('Dealer updated successfully.'));
    }

    public function demote(User $dealer): RedirectResponse
    {
        Gate::authorize('manage-dealers');

        if ($dealer->role !== User::ROLE_DEALER) {
            abort(404);
        }

        $dealer->forceFill([
            'role' => User::ROLE_USER,
            'dealer_status' => User::DEALER_STATUS_INACTIVE,
            'dealer_discount' => 0,
        ])->save();

        return back()->with('success', __('Dealer was converted to regular user.'));
    }
}
