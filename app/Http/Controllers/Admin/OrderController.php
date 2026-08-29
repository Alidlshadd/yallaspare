<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceRenderer;
use App\Services\Orders\OrderStatusService;
use App\Services\Payments\PaymentService;
use App\Support\AdminLogger;
use App\Support\SqlSafe;
use App\Support\UserCommunication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusInput = strtolower(trim((string) $request->query('status', '')));
        $status = Order::normalizedStatus($statusInput);
        $association = strtolower(trim((string) $request->query('association', '')));
        $attention = strtolower(trim((string) $request->query('attention', '')));
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
                'payment_status',
                'payment_reference',
                'delivery_city',
                'cancellation_requested_at',
                'archived_at',
                'created_at',
            ])
            ->with(['user:id,name,email,role,dealer_status'])
            ->withCount('items')
            ->withCount(['returnRequests as open_returns_count' => fn ($q) => $q->whereIn('status', ['requested', 'approved', 'received'])])
            ->whereNull('archived_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                SqlSafe::whereLike($q, 'order_number', $search);
                SqlSafe::orWhereLike($q, 'delivery_phone', $search);
                SqlSafe::orWhereLike($q, 'delivery_city', $search);
                $q->orWhereHas('user', function ($userQuery) use ($search) {
                    SqlSafe::whereLike($userQuery, 'name', $search);
                    SqlSafe::orWhereLike($userQuery, 'email', $search);
                });
            });
        }

        if ($statusInput !== '' && in_array($status, Order::allowedStatuses(), true)) {
            $query->where('status', $status);
        }

        if ($attention === 'today_pending') {
            $query
                ->where('status', Order::STATUS_PENDING)
                ->whereDate('created_at', now()->toDateString());
        } elseif ($attention === 'needs_shipping') {
            $query->where('status', Order::STATUS_PROCESSING);
        } elseif ($attention === 'cancellation_requests') {
            $query
                ->whereNotNull('cancellation_requested_at')
                ->where('status', '!=', Order::STATUS_CANCELLED);
        } elseif ($attention === 'open_returns') {
            $query->whereHas('returnRequests', fn ($returnQuery) => $returnQuery->whereIn('status', ['requested', 'approved', 'received']));
        }

        if ($association === 'dealer') {
            $query->whereHas('user', fn ($q) => $q->where('role', User::ROLE_DEALER));
        } elseif ($association === 'user') {
            $query->whereHas('user', fn ($q) => $q->where('role', User::ROLE_USER));
        }

        if (! empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }
        if (! empty($to)) {
            $query->whereDate('created_at', '<=', $to);
        }

        $orders = $query->latest('id')->paginate(12)->withQueryString();

        $statsQuery = Order::query();
        if (! empty($from)) {
            $statsQuery->whereDate('created_at', '>=', $from);
        }
        if (! empty($to)) {
            $statsQuery->whereDate('created_at', '<=', $to);
        }
        if ($association === 'dealer') {
            $statsQuery->whereHas('user', fn ($q) => $q->where('role', User::ROLE_DEALER));
        } elseif ($association === 'user') {
            $statsQuery->whereHas('user', fn ($q) => $q->where('role', User::ROLE_USER));
        }

        $statusCounts = (clone $statsQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count);

        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'pending' => (int) ($statusCounts[Order::STATUS_PENDING] ?? 0),
            'processing' => (int) ($statusCounts[Order::STATUS_PROCESSING] ?? 0),
            'shipped' => (int) ($statusCounts[Order::STATUS_SHIPPED] ?? 0),
            'delivered' => (int) ($statusCounts[Order::STATUS_DELIVERED] ?? 0),
            'cancelled' => (int) ($statusCounts[Order::STATUS_CANCELLED] ?? 0),
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
            'attention' => $attention,
        ]);
    }

    public function show(Order $order): View
    {
        $relations = [
            'user:id,name,email,phone,role,dealer_status,dealer_discount',
            'items' => fn ($q) => $q->select(['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'])
                ->with(['product:id,name_en,name_ar,name_ku,sku,brand,image']),
        ];

        $optionalRelations = [
            'order_status_histories' => ['statusHistory', fn ($q) => $q->limit(20)->with(['changedBy:id,name'])],
            'order_admin_notes' => ['adminNotes', fn ($q) => $q->limit(20)->with(['user:id,name'])],
            'return_requests' => ['returnRequests', fn ($q) => $q->limit(10)->with(['user:id,name,email'])],
            'payments' => ['payments', fn ($q) => $q->limit(10)],
        ];

        foreach ($optionalRelations as $table => [$relation, $loader]) {
            if (Schema::hasTable($table)) {
                $relations[$relation] = $loader;
            }
        }

        $order->load($relations);

        foreach ($optionalRelations as $table => [$relation]) {
            if (! Schema::hasTable($table)) {
                $order->setRelation($relation, collect());
            }
        }

        return view('admin.orders.show', [
            'order' => $order,
            'statusOptions' => Order::allowedStatuses(),
            'nextStatuses' => Order::nextStatuses((string) $order->status),
        ]);
    }

    // Renders through the shared InvoiceRenderer rather than a second copy of
    // the same view call, so admin downloads get the same engine — and the same
    // Arabic/Kurdish shaping — as the customer and mobile ones. The locale
    // resolution below stays admin-specific on purpose: an admin's own language
    // preference must not decide the language of someone else's invoice.
    public function invoice(Request $request, Order $order, InvoiceRenderer $renderer): Response
    {
        $order->load(['user:id,name,email,phone,locale_preference']);

        return $renderer->download($order, $this->invoiceLocale($request, $order));
    }

    private function invoiceLocale(Request $request, Order $order): string
    {
        $requestedLocale = strtolower((string) $request->query('lang', $request->query('locale', '')));
        if (in_array($requestedLocale, ['en', 'ar', 'ku'], true)) {
            return $requestedLocale;
        }

        $preferredLocale = strtolower((string) ($order->user?->locale_preference ?: app()->getLocale()));

        return in_array($preferredLocale, ['en', 'ar', 'ku'], true) ? $preferredLocale : 'en';
    }

    public function update(Request $request, Order $order, OrderStatusService $statuses): RedirectResponse
    {
        return $this->updateStatus($request, $order, $statuses);
    }

    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission(User::PERMISSION_FINANCE_MANAGE), 403);

        $data = $request->validate([
            'payment_status' => ['required', 'string', 'max:32'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $paymentStatus = Order::normalizedPaymentStatus($data['payment_status']);

        DB::transaction(function () use ($order, $paymentStatus, $data): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Manual payment edits are exceptional finance operations. Online
            // providers still remain the source of truth through PaymentService.
            $lockedOrder->forceFill([
                'payment_status' => $paymentStatus,
                'payment_reference' => trim((string) ($data['payment_reference'] ?? '')) ?: null,
            ])->save();
        });

        AdminLogger::log('order.payment_updated', $order, [
            'payment_status' => $paymentStatus,
            'payment_reference' => $order->payment_reference,
        ]);

        return back()->with('success', __('Order #:order payment updated.', ['order' => $order->order_number]));
    }

    public function verifyWaylPayment(
        Request $request,
        Order $order,
        Payment $payment,
        PaymentService $payments
    ): RedirectResponse {
        abort_unless($request->user()?->hasPermission(User::PERMISSION_FINANCE_MANAGE), 403);
        abort_unless($payment->order_id === $order->id && $payment->provider === 'wayl', 404);

        try {
            $verified = $payments->verifyAndApply($payment, 'admin');
        } catch (\Throwable $exception) {
            Log::warning('Admin WAYL payment status verification failed', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'admin_id' => $request->user()?->id,
                'error_type' => $exception::class,
            ]);

            return back()->with('error', __('WAYL payment status could not be verified. Please try again.'));
        }

        AdminLogger::log('order.wayl_payment_verified', $order, [
            'payment_id' => $verified->id,
            'payment_status' => $verified->status,
        ]);

        return back()->with('success', __('WAYL payment status verified: :status.', [
            'status' => __(ucfirst(str_replace('_', ' ', (string) $verified->status))),
        ]));
    }

    public function updateStatus(Request $request, Order $order, OrderStatusService $statuses): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:32'],
        ]);

        $status = Order::normalizedStatus($data['status']);

        if (! in_array($status, Order::allowedStatuses(), true)) {
            return back()->with('error', __('Invalid order status.'));
        }

        try {
            $statuses->changeStatus($order, $status, $request->user());
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Order #:order status updated to :status.', ['order' => $order->order_number, 'status' => __(ucfirst(str_replace('_', ' ', $status)))]));
    }

    /**
     * Record who is carrying the parcel and under what number.
     *
     * Kept apart from the status change: an operator usually marks an order
     * shipped before the courier hands back a number, and often corrects the
     * number afterwards without the status moving at all.
     */
    public function updateShipment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:64'],
            'tracking_number' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-\/ ]+$/'],
        ]);

        $carrier = trim((string) ($data['carrier'] ?? '')) ?: null;
        $trackingNumber = trim((string) ($data['tracking_number'] ?? '')) ?: null;

        if ($trackingNumber !== null && $carrier === null) {
            return back()->withErrors([
                'carrier' => __('Name the carrier the tracking number belongs to.'),
            ])->withInput();
        }

        $hadTracking = $order->hasShipmentTracking();
        $previousTracking = [$order->carrier, $order->tracking_number];

        $order->forceFill([
            'carrier' => $carrier,
            'tracking_number' => $trackingNumber,
        ])->save();

        if ($previousTracking === [$order->carrier, $order->tracking_number]) {
            return back()->with('success', __('Shipment details are unchanged.'));
        }

        AdminLogger::log('order.shipment_updated', $order, [
            'carrier' => $carrier,
            'has_tracking_number' => $trackingNumber !== null,
        ]);

        // The status change already announced the shipment. Announce it again
        // only when this is the number the customer did not have yet.
        $shouldNotify = $trackingNumber !== null
            && ! $hadTracking
            && $order->user
            && in_array(Order::normalizedStatus((string) $order->status), [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED], true);

        if ($shouldNotify) {
            UserCommunication::sendShipmentTracking($order->user, $order);
        }

        return back()->with('success', __('Order #:order shipment details saved.', ['order' => $order->order_number]));
    }

    public function destroy(Order $order): RedirectResponse
    {
        if (auth()->user()?->role !== User::ROLE_SUPER_ADMIN) {
            return back()->with('error', __('Only super admins can archive orders.'));
        }

        $order->update(['archived_at' => now()]);

        AdminLogger::log('order.archived', $order, [
            'order_number' => $order->order_number,
        ]);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', __('Order archived successfully.'));
    }

    public function storeAdminNote(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:3000'],
        ]);

        $order->adminNotes()->create([
            'user_id' => auth()->id(),
            'note' => trim((string) $data['note']),
        ]);

        AdminLogger::log('order.admin_note_created', $order, [
            'order_number' => $order->order_number,
        ]);

        return back()->with('success', __('Internal note added.'));
    }

    public function exportExcel(Request $request)
    {
        try {
            return Excel::download(
                new OrdersExport([
                    'search' => $request->query('search'),
                    'from' => $request->query('from'),
                    'to' => $request->query('to'),
                    'status' => $request->query('status'),
                    'association' => $request->query('association'),
                    'attention' => $request->query('attention'),
                ]),
                'orders.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('Orders Excel export failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('Failed to export orders to Excel. Please try again.'));
        }
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1', 'max:200'],
            'order_ids.*' => ['integer', 'min:1'],
            'status' => ['required', 'string', 'max:32'],
        ]);

        $targetStatus = Order::normalizedStatus($data['status']);
        if (! in_array($targetStatus, Order::allowedStatuses(), true)) {
            return back()->with('error', __('Invalid order status.'));
        }

        $updated = 0;
        $skipped = 0;
        $skippedReasons = [];
        $notifications = [];

        foreach (array_unique($data['order_ids']) as $orderId) {
            $result = DB::transaction(function () use ($orderId, $targetStatus): array {
                $order = Order::query()
                    ->whereKey($orderId)
                    ->with(['items:id,order_id,product_id,quantity', 'user:id,name,email,phone,notify_order_updates,email_notifications,sms_notifications,whatsapp_notifications'])
                    ->lockForUpdate()
                    ->first();

                if (! $order) {
                    return ['outcome' => 'skipped', 'reason' => "Order #{$orderId} not found"];
                }

                $previousStatus = (string) $order->status;

                if ($previousStatus === $targetStatus) {
                    return ['outcome' => 'skipped', 'reason' => "Order #{$order->order_number} already {$targetStatus}"];
                }

                if (! Order::canTransition($previousStatus, $targetStatus)) {
                    return ['outcome' => 'skipped', 'reason' => "Order #{$order->order_number} cannot go {$previousStatus}->{$targetStatus}"];
                }

                if (
                    $targetStatus === Order::STATUS_CANCELLED
                    && $previousStatus !== Order::STATUS_CANCELLED
                    && $previousStatus !== Order::STATUS_DELIVERED
                ) {
                    foreach ($order->items as $item) {
                        if (! $item->product_id) {
                            continue;
                        }

                        $product = Product::query()
                            ->whereKey($item->product_id)
                            ->lockForUpdate()
                            ->first();

                        if (! $product) {
                            continue;
                        }

                        $quantity = (int) $item->quantity;
                        $stockBefore = (int) $product->stock_quantity;
                        $stockAfter = $stockBefore + $quantity;

                        $product->update(['stock_quantity' => $stockAfter]);

                        InventoryMovement::query()->create([
                            'product_id' => $product->id,
                            'user_id' => auth()->id(),
                            'type' => InventoryMovement::TYPE_IN,
                            'quantity' => $quantity,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'reference' => $order->order_number,
                            'note' => 'Order cancelled (bulk) - stock restored',
                        ]);
                    }
                }

                $order->forceFill(['status' => $targetStatus])->save();

                // Same COD auto-settle rule as the single-row updateStatus path
                if (
                    $targetStatus === Order::STATUS_DELIVERED
                    && strtolower((string) $order->payment_method) === 'cash_on_delivery'
                    && $order->payment_status !== Order::PAYMENT_PAID
                ) {
                    $order->forceFill(['payment_status' => Order::PAYMENT_PAID])->save();
                    AdminLogger::log('order.payment_auto_marked_paid', $order, [
                        'reason' => 'cash_on_delivery_delivered_bulk',
                    ]);
                }

                $order->statusHistory()->create([
                    'from_status' => $previousStatus,
                    'to_status' => $targetStatus,
                    'changed_by' => auth()->id(),
                    'note' => null,
                    'created_at' => now(),
                ]);

                AdminLogger::log('order.status_changed_bulk', $order, [
                    'from' => $previousStatus,
                    'to' => $targetStatus,
                ]);

                $order->setAttribute('previous_status_for_notification', $previousStatus);

                return ['outcome' => 'updated', 'order' => $order];
            });

            if ($result['outcome'] === 'updated') {
                $updated++;
                $notifications[] = $result['order'];
            } else {
                $skipped++;
                if (count($skippedReasons) < 10) {
                    $skippedReasons[] = $result['reason'];
                }
            }
        }

        foreach ($notifications as $order) {
            if ($order->user) {
                UserCommunication::sendOrderStatusUpdated(
                    $order->user,
                    $order,
                    (string) $order->getAttribute('previous_status_for_notification'),
                    (string) $order->status
                );
            }
        }

        if ($skipped > 0) {
            return back()->with('error', __(':updated updated, :skipped skipped — :reasons', [
                'updated' => $updated,
                'skipped' => $skipped,
                'reasons' => implode(' | ', $skippedReasons),
            ]));
        }

        return back()->with('success', __('Bulk status: :updated orders updated to :status.', [
            'updated' => $updated,
            'status' => $targetStatus,
        ]));
    }
}
