<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\AdminLogger;
use App\Support\Branding;
use App\Support\SqlSafe;
use App\Support\UserCommunication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    public function invoice(Request $request, Order $order): Response
    {
        $order->load([
            'user:id,name,email,phone,locale_preference',
            'items' => fn ($query) => $query
                ->select(['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'])
                ->with(['product:id,name_en,name_ar,name_ku,sku,brand']),
        ]);

        $subtotal = (float) ($order->subtotal_amount ?: $order->items->sum('subtotal'));
        $shipping = (float) $order->shipping_fee;
        $discount = (float) $order->discount_amount;
        $grandTotal = (float) ($order->grand_total ?: ($subtotal + $shipping - $discount));
        $year = optional($order->created_at)->format('Y') ?: now()->format('Y');
        $locale = $this->invoiceLocale($request, $order);
        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            $pdf = Pdf::loadView('admin.orders.invoice', [
                'order' => $order,
                'invoiceNumber' => 'INV-' . $year . '-' . str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                'currency' => 'IQD',
                'logoPath' => $this->invoiceLogoPath(),
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'discount' => $discount,
                'grandTotal' => $grandTotal,
                'locale' => $locale,
                'isRtl' => in_array($locale, ['ar', 'ku'], true),
            ])->setPaper('a4');
        } finally {
            app()->setLocale($previousLocale);
        }

        return $pdf->download('invoice-' . $order->id . '-' . $locale . '.pdf');
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

    private function invoiceLogoPath(): ?string
    {
        $logoValue = (string) Setting::getValue('site_logo', '');
        if ($logoValue === '') {
            return null;
        }

        $storagePath = Branding::storagePathFromValue($logoValue);
        if ($storagePath && Branding::isSafeLogoPath($storagePath)) {
            $publicStoragePath = public_path('storage/' . ltrim($storagePath, '/'));
            if (is_file($publicStoragePath)) {
                return str_replace('\\', '/', $publicStoragePath);
            }
        }

        $normalized = str_replace('\\', '/', trim($logoValue));
        if (
            Branding::isSafeLogoPath($normalized)
            && Str::startsWith($normalized, ['assets/', 'images/', 'storage/', '/assets/', '/images/', '/storage/'])
        ) {
            $publicPath = public_path(ltrim($normalized, '/'));
            if (is_file($publicPath)) {
                return str_replace('\\', '/', $publicPath);
            }
        }

        return null;
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        return $this->updateStatus($request, $order);
    }

    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'payment_status' => ['required', 'string', 'max:32'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $paymentStatus = Order::normalizedPaymentStatus($data['payment_status']);

        $order->forceFill([
            'payment_status' => $paymentStatus,
            'payment_reference' => trim((string) ($data['payment_reference'] ?? '')) ?: null,
        ])->save();

        AdminLogger::log('order.payment_updated', $order, [
            'payment_status' => $paymentStatus,
            'payment_reference' => $order->payment_reference,
        ]);

        return back()->with('success', __('Order #:order payment updated.', ['order' => $order->order_number]));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:32'],
        ]);

        $status = Order::normalizedStatus($data['status']);

        if (!in_array($status, Order::allowedStatuses(), true)) {
            return back()->with('error', __('Invalid order status.'));
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
            return back()->with('error', __('Invalid status transition. :steps', ['steps' => $allowedText]));
        }

        $updatedOrder = DB::transaction(function () use ($order, $status): ?Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->with(['items:id,order_id,product_id,quantity', 'user:id,name,email,phone,notify_order_updates,email_notifications,sms_notifications,whatsapp_notifications'])
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = (string) $lockedOrder->status;

            if ($previousStatus === $status) {
                return null;
            }

            if (!Order::canTransition($previousStatus, $status)) {
                return null;
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

                    $product = Product::query()
                        ->whereKey($item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$product) {
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
                        'reference' => $lockedOrder->order_number,
                        'note' => 'Order cancelled - stock restored',
                    ]);
                }
            }

            $lockedOrder->forceFill(['status' => $status])->save();

            // Cash-on-delivery orders settle at the moment of delivery: physical handover
            // == payment received. Auto-flip payment_status to paid so the admin doesn't
            // have to do a second click and so revenue reports stay accurate.
            if (
                $status === Order::STATUS_DELIVERED
                && strtolower((string) $lockedOrder->payment_method) === 'cash_on_delivery'
                && $lockedOrder->payment_status !== Order::PAYMENT_PAID
            ) {
                $lockedOrder->forceFill(['payment_status' => Order::PAYMENT_PAID])->save();
                AdminLogger::log('order.payment_auto_marked_paid', $lockedOrder, [
                    'reason' => 'cash_on_delivery_delivered',
                ]);
            }

            $lockedOrder->statusHistory()->create([
                'from_status' => $previousStatus,
                'to_status' => $status,
                'changed_by' => auth()->id(),
                'note' => null,
                'created_at' => now(),
            ]);

            AdminLogger::log('order.status_changed', $lockedOrder, [
                'from' => $previousStatus,
                'to' => $status,
            ]);
            $lockedOrder->setAttribute('previous_status_for_notification', $previousStatus);

            return $lockedOrder;
        });

        if ($updatedOrder && $updatedOrder->user) {
            UserCommunication::sendOrderStatusUpdated(
                $updatedOrder->user,
                $updatedOrder,
                (string) $updatedOrder->getAttribute('previous_status_for_notification'),
                (string) $updatedOrder->status
            );
        }

        return back()->with('success', __('Order #:order status updated to :status.', ['order' => $order->order_number, 'status' => __(ucfirst(str_replace('_', ' ', $status)))]));
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
