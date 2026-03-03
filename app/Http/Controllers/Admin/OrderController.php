<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Support\AdminLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusInput = strtolower(trim((string) $request->query('status', '')));
        $status = Order::normalizedStatus($statusInput);
        $association = strtolower(trim((string) $request->query('association', '')));
        $from = $request->query('from');
        $to = $request->query('to');

        $query = Order::query()
            ->select([
                'id',
                'user_id',
                'order_number',
                'total_amount',
                'status',
                'payment_method',
                'delivery_city',
                'created_at',
            ])
            ->with(['user:id,name,email,role,dealer_status'])
            ->withCount('items');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%')
                    ->orWhere('delivery_phone', 'like', '%' . $search . '%')
                    ->orWhere('delivery_city', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($statusInput !== '' && in_array($status, Order::allowedStatuses(), true)) {
            $query->where('status', $status);
        }

        if ($association === 'dealer') {
            $query->whereHas('user', fn ($q) => $q->where('role', User::ROLE_DEALER));
        } elseif ($association === 'user') {
            $query->whereHas('user', fn ($q) => $q->where('role', User::ROLE_USER));
        }

        if (!empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }
        if (!empty($to)) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->latest('id')->paginate(12)->withQueryString();

        $statsQuery = Order::query();
        if (!empty($from)) {
            $statsQuery->whereDate('created_at', '>=', $from);
        }
        if (!empty($to)) {
            $statsQuery->whereDate('created_at', '<=', $to);
        }
        if ($association === 'dealer') {
            $statsQuery->whereHas('user', fn ($q) => $q->where('role', User::ROLE_DEALER));
        } elseif ($association === 'user') {
            $statsQuery->whereHas('user', fn ($q) => $q->where('role', User::ROLE_USER));
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', Order::STATUS_PENDING)->count(),
            'processing' => (clone $statsQuery)->where('status', Order::STATUS_PROCESSING)->count(),
            'shipped' => (clone $statsQuery)->where('status', Order::STATUS_SHIPPED)->count(),
            'delivered' => (clone $statsQuery)->where('status', Order::STATUS_DELIVERED)->count(),
            'cancelled' => (clone $statsQuery)->where('status', Order::STATUS_CANCELLED)->count(),
        ];

        $transitionOptions = $orders->getCollection()
            ->mapWithKeys(function (Order $order) {
                $allowed = Order::nextStatuses((string) $order->status);
                return [$order->id => array_values(array_unique(array_merge([$order->status], $allowed)))];
            })
            ->toArray();

        return view('admin.orders.index', [
            'orders' => $orders,
            'stats' => $stats,
            'statusOptions' => Order::allowedStatuses(),
            'transitionOptions' => $transitionOptions,
            'association' => $association,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user:id,name,email,phone,role,dealer_status,dealer_discount',
            'items' => fn ($q) => $q->select(['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'])
                ->with(['product:id,name_en,sku,image']),
            'statusHistory' => fn ($q) => $q->limit(20)->with(['changedBy:id,name']),
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'statusOptions' => Order::allowedStatuses(),
            'nextStatuses' => Order::nextStatuses((string) $order->status),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        return $this->updateStatus($request, $order);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:32'],
        ]);

        $status = Order::normalizedStatus($data['status']);

        if (!in_array($status, Order::allowedStatuses(), true)) {
            return back()->with('error', 'Invalid order status.');
        }

        $currentStatus = (string) $order->status;
        if (!Order::canTransition($currentStatus, $status)) {
            $allowed = Order::nextStatuses($currentStatus);
            $allowedText = empty($allowed)
                ? 'No transitions allowed from current state.'
                : 'Allowed transitions: ' . implode(', ', array_map(
                    fn ($s) => ucfirst(str_replace('_', ' ', $s)),
                    $allowed
                )) . '.';
            return back()->with('error', 'Invalid status transition. ' . $allowedText);
        }

        DB::transaction(function () use ($order, $status): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->with('items:id,order_id,product_id,quantity')
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = (string) $lockedOrder->status;

            if ($previousStatus === $status) {
                return;
            }

            if (!Order::canTransition($previousStatus, $status)) {
                return;
            }

            if (
                $status === Order::STATUS_CANCELLED
                && $previousStatus !== Order::STATUS_CANCELLED
                && $previousStatus !== Order::STATUS_DELIVERED
            ) {
                foreach ($lockedOrder->items as $item) {
                    if (!$item->product_id) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $item->product_id)
                        ->increment('stock_quantity', (int) $item->quantity);
                }
            }

            $lockedOrder->update(['status' => $status]);

            $lockedOrder->statusHistory()->create([
                'from_status' => $previousStatus,
                'to_status' => $status,
                'changed_by' => auth()->id(),
                'note' => null,
            ]);

            AdminLogger::log('order.status_changed', $lockedOrder, [
                'from' => $previousStatus,
                'to' => $status,
            ]);
        });

        return back()->with('success', "Order #{$order->order_number} status updated to " . ucfirst(str_replace('_', ' ', $status)) . '.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Order deleted successfully.');
    }
}
