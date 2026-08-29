<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Analytics\AddToCartTracker;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function index(): View
    {
        $user = auth()->user();
        $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');

        $cart = $this->carts->current()?->load('items.product.category');

        if ($cart && $this->carts->syncToStock($cart)) {
            $cart->load('items.product.category');
            session()->flash('error', __('Some cart quantities were adjusted to available stock.'));
        }

        $items = $cart?->items ?? collect();
        $subtotal = $items->sum(function (CartItem $item): float {
            $product = $item->product;
            if (! $product) {
                return 0;
            }

            return $product->priceFor(auth()->user()) * $item->quantity;
        });

        $addresses = $user
            ? $user->addresses()->latest('is_default')->latest('id')->get()
            : collect();

        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        return view('shop.cart', [
            'cart' => $cart,
            'items' => $items,
            'subtotal' => round((float) $subtotal, 2),
            'currencySymbol' => $currencyLabel,
            'cartCount' => (int) $items->sum('quantity'),
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'defaultDeliveryNote' => (string) ($user?->default_delivery_note ?? ''),
            'defaultContactMethod' => in_array((string) $user?->default_contact_method, ['phone', 'email', 'sms'], true)
                ? (string) $user->default_contact_method
                : 'sms',
            'expressCheckout' => (bool) ($user?->express_checkout ?? false),
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'buy_now' => ['nullable', 'boolean'],
        ]);
        $quantity = (int) ($data['quantity'] ?? 1);

        if (! $product->is_active) {
            return back()->with('error', __('This product is not available right now.'));
        }

        $buyNow = $request->boolean('buy_now');

        $cart = $this->carts->currentOrCreate();

        try {
            [$wasLimited, $cartQuantity] = $this->carts->addProduct($cart, $product, $quantity);
        } catch (\RuntimeException $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->with('error', $exception->getMessage());
        }

        app(AddToCartTracker::class)->record($request, $product, $quantity);

        $message = $wasLimited
            ? __('Only :quantity available. Cart quantity was set to :quantity.', ['quantity' => $cartQuantity])
            : __('Added to cart successfully');

        if ($buyNow) {
            return redirect()->route('cart.index')->with($wasLimited ? 'error' : 'success', $wasLimited ? $message : __('Product added. Review and place your order.'));
        }

        if ($request->expectsJson() || $request->ajax()) {
            $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');
            $cart->loadMissing('items.product');
            $cartCount = (int) $cart->items->sum('quantity');
            $subtotal = (float) $cart->items->sum(function (CartItem $item): float {
                if (! $item->product) {
                    return 0;
                }

                return $item->product->priceFor(auth()->user()) * (int) $item->quantity;
            });

            return response()->json([
                'ok' => true,
                'cart_count' => $cartCount,
                'cart_items_label' => __('Items (:count)', ['count' => $cartCount]),
                'cart_ref' => '#'.str_pad((string) $cart->id, 6, '0', STR_PAD_LEFT),
                'cart_total_formatted' => trim($currencyLabel.' '.number_format($subtotal, 2)),
                'message' => $message,
            ]);
        }

        return back()->with($wasLimited ? 'error' : 'success', $message);
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $cart = $this->carts->current();
        if (! $cart || $item->cart_id !== $cart->id) {
            abort(403);
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        [$status, $cartQuantity] = DB::transaction(function () use ($item, $data): array {
            $lockedItem = CartItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $lockedProduct = Product::query()->whereKey($lockedItem->product_id)->lockForUpdate()->first();
            $maxQuantity = $lockedProduct ? $this->carts->maxPurchasableQuantity($lockedProduct) : 0;

            if ($maxQuantity < 1) {
                $lockedItem->delete();

                return ['removed', 0];
            }

            $requestedQuantity = (int) $data['quantity'];
            $newQuantity = min($maxQuantity, $requestedQuantity);
            $lockedItem->update(['quantity' => $newQuantity]);

            return [$newQuantity < $requestedQuantity ? 'limited' : 'updated', $newQuantity];
        });

        if ($status === 'removed') {
            return back()->with('error', __('This product is out of stock and was removed from your cart.'));
        }

        if ($status === 'limited') {
            return back()->with('error', __('Only :quantity available. Cart quantity was set to :quantity.', ['quantity' => $cartQuantity]));
        }

        return back()->with('success', __('Cart item updated.'));
    }

    public function remove(CartItem $item): RedirectResponse
    {
        $cart = $this->carts->current();
        if (! $cart || $item->cart_id !== $cart->id) {
            abort(403);
        }

        $item->delete();

        return back()->with('success', __('Item removed from cart.'));
    }
}
