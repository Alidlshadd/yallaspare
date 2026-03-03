<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DealerController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-dealers');

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $dealersQuery = User::query()->where('role', User::ROLE_DEALER);

        if ($search !== '') {
            $dealersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
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

        return view('admin.dealers.index', compact(
            'dealers',
            'search',
            'status',
            'totalDealers',
            'activeDealers',
            'inactiveDealers',
            'suspendedDealers',
            'averageDiscount'
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

        $dealer->update([
            'dealer_status' => $data['dealer_status'],
            'dealer_discount' => round((float) $data['dealer_discount'], 2),
        ]);

        return back()->with('success', 'Dealer updated successfully.');
    }

    public function demote(User $dealer): RedirectResponse
    {
        Gate::authorize('manage-dealers');

        if ($dealer->role !== User::ROLE_DEALER) {
            abort(404);
        }

        $dealer->update([
            'role' => User::ROLE_USER,
            'dealer_status' => User::DEALER_STATUS_INACTIVE,
            'dealer_discount' => 0,
        ]);

        return back()->with('success', 'Dealer was converted to regular user.');
    }
}
