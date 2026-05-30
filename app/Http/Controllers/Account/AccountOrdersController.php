<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReturnRequest;
use App\Services\InvoiceRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class AccountOrdersController extends Controller
{
    public function index(): View
    {
        $orders = auth()->user()
            ->orders()
            ->latest('id')
            ->paginate(10);

        return view('account.orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): View
    {
        $order = auth()->user()
            ->orders()
            ->whereKey($order->id)
            ->with([
                'items.product:id,name_en,name_ar,name_ku,sku,image,slug,is_active',
                'statusHistory.changedBy:id,name',
                'returnRequests',
            ])
            ->firstOrFail();

        $productIds = $order->items
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $userReviews = $productIds->isNotEmpty()
            ? ProductReview::query()
                ->where('user_id', auth()->id())
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id')
            : collect();

        return view('account.orders.show', [
            'order' => $order,
            'userReviews' => $userReviews,
        ]);
    }

    public function invoice(Request $request, Order $order, InvoiceRenderer $renderer): Response
    {
        $order = auth()->user()
            ->orders()
            ->whereKey($order->id)
            ->with([
                'user:id,name,email,phone,locale_preference',
                'items' => fn ($query) => $query
                    ->select(['id', 'order_id', 'product_id', 'quantity', 'unit_price', 'subtotal'])
                    ->with(['product:id,name_en,name_ar,name_ku,sku,brand']),
            ])
            ->firstOrFail();

        $explicit = (string) $request->query('lang', $request->query('locale', ''));
        $locale = $renderer->resolveLocale($explicit !== '' ? $explicit : null, $order, auth()->user());

        return $renderer->render($order, $locale)
            ->download('invoice-' . $order->id . '-' . $locale . '.pdf');
    }

    public function reorder(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'replace_cart' => ['nullable', 'boolean'],
        ]);

        $order = auth()->user()
            ->orders()
            ->whereKey($order->id)
            ->with('items:id,order_id,product_id,quantity')
            ->firstOrFail();

        $replaceCart = (bool) ($data['replace_cart'] ?? false);
        $cart = Cart::query()->firstOrCreate(['user_id' => auth()->id()]);
        $addedCount = 0;
        $skippedCount = 0;
        $wasLimited = false;

        DB::transaction(function () use ($order, $cart, $replaceCart, &$addedCount, &$skippedCount, &$wasLimited): void {
            if ($replaceCart) {
                CartItem::query()
                    ->where('cart_id', $cart->id)
                    ->delete();
            }

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    $skippedCount++;
                    continue;
                }

                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                $maxQuantity = $product && $product->is_active
                    ? min(99, max(0, (int) $product->stock_quantity))
                    : 0;

                if ($maxQuantity < 1) {
                    $skippedCount++;
                    continue;
                }

                $cartItem = CartItem::query()->firstOrNew([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                ]);
                $currentQuantity = $cartItem->exists ? (int) $cartItem->quantity : 0;
                $requestedTotal = $currentQuantity + max(1, (int) $item->quantity);
                $cartItem->quantity = min($maxQuantity, $requestedTotal);
                $cartItem->save();

                $addedQuantity = max(0, (int) $cartItem->quantity - $currentQuantity);
                if ($addedQuantity > 0) {
                    $addedCount += $addedQuantity;
                }

                if ((int) $cartItem->quantity < $requestedTotal) {
                    $wasLimited = true;
                }
            }
        });

        if ($addedCount < 1) {
            return back()->with('error', __('No products from this order can be reordered right now.'));
        }

        $message = $skippedCount > 0 || $wasLimited
            ? __('Available products were added to your cart. Some items were skipped or limited by stock.')
            : __('Order products added to your cart.');

        return redirect()->route('cart.index')->with($skippedCount > 0 || $wasLimited ? 'error' : 'success', $message);
    }

    public function requestCancellation(Request $request, Order $order): RedirectResponse
    {
        $order = auth()->user()
            ->orders()
            ->whereKey($order->id)
            ->firstOrFail();

        $normalizedStatus = Order::normalizedStatus((string) $order->status);

        if (! in_array($normalizedStatus, [Order::STATUS_PENDING, Order::STATUS_PROCESSING], true)) {
            return back()->with('error', __('This order can no longer be requested for cancellation.'));
        }

        $data = $request->validate([
            'reason' => [$normalizedStatus === Order::STATUS_PENDING ? 'nullable' : 'required', 'string', 'max:1000'],
        ]);

        if ($normalizedStatus === Order::STATUS_PENDING) {
            try {
                $this->cancelPendingOrder($order, trim((string) ($data['reason'] ?? '')));
            } catch (\RuntimeException $exception) {
                return back()->with('error', $exception->getMessage());
            }

            return back()->with('status', __('Order cancelled successfully.'));
        }

        $order->update([
            'cancellation_requested_at' => $order->cancellation_requested_at ?: now(),
            'cancellation_reason' => trim((string) $data['reason']),
        ]);

        return back()->with('status', __('Cancellation request sent. Our team will review it shortly.'));
    }

    private function cancelPendingOrder(Order $order, string $reason = ''): void
    {
        DB::transaction(function () use ($order, $reason): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->where('user_id', auth()->id())
                ->with('items:id,order_id,product_id,quantity')
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = Order::normalizedStatus((string) $lockedOrder->status);
            if ($previousStatus !== Order::STATUS_PENDING) {
                throw new \RuntimeException(__('This order can no longer be cancelled directly.'));
            }

            foreach ($lockedOrder->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = \App\Models\Product::query()
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
                    'reference' => $lockedOrder->order_number,
                    'note' => 'Order cancelled by customer - stock restored',
                ]);
            }

            $lockedOrder->forceFill([
                'status' => Order::STATUS_CANCELLED,
                'cancellation_requested_at' => now(),
                'cancellation_reason' => $reason !== '' ? $reason : 'Cancelled by customer before processing',
            ])->save();

            $lockedOrder->statusHistory()->create([
                'from_status' => $previousStatus,
                'to_status' => Order::STATUS_CANCELLED,
                'changed_by' => auth()->id(),
                'note' => 'Cancelled by customer',
                'created_at' => now(),
            ]);
        });
    }

    public function requestReturn(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:return,exchange,refund'],
            'reason' => ['required', 'string', 'max:1500'],
        ]);

        $order = auth()->user()
            ->orders()
            ->whereKey($order->id)
            ->firstOrFail();

        if (Order::normalizedStatus((string) $order->status) !== Order::STATUS_DELIVERED) {
            return back()->with('error', __('Returns can be requested after the order is delivered.'));
        }

        ReturnRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'status' => ReturnRequest::STATUS_REQUESTED,
            'reason' => trim((string) $data['reason']),
            'requested_at' => now(),
        ]);

        return back()->with('status', __('Return request sent. Our team will review it shortly.'));
    }

}
