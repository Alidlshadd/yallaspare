<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');

        $cart = Cart::query()
            ->where('user_id', $user?->id)
            ->with('items.product.category')
            ->first();

        if ($cart && $this->syncCartQuantitiesToStock($cart)) {
            $cart->load('items.product.category');
            session()->flash('error', __('Some cart quantities were adjusted to available stock.'));
        }

        $items = $cart?->items ?? collect();
        $subtotal = $items->sum(function (CartItem $item): float {
            $product = $item->product;
            if (!$product) {
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
            'defaultContactMethod' => (string) ($user?->default_contact_method ?? 'phone'),
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

        if (! auth()->check()) {
            $this->storePendingAction(
                $product->id,
                $quantity,
                $this->safeInternalUrl($request->headers->get('referer'))
            );
            session()->put('url.intended', route('cart.pending.resume'));

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'login_required' => true,
                    'redirect' => route('login'),
                    'message' => __('Please sign in or register to add items to your cart.'),
                ], 401);
            }

            return redirect()->route('login')
                ->with('status', __('Please sign in or register to add items to your cart.'));
        }

        if (!$product->is_active) {
            return back()->with('error', __('This product is not available right now.'));
        }

        $buyNow = $request->boolean('buy_now');

        $cart = Cart::query()->firstOrCreate(['user_id' => auth()->id()]);

        try {
            [$wasLimited, $cartQuantity] = $this->performAuthenticatedAdd($cart, $product, $quantity);
        } catch (\RuntimeException $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->with('error', $exception->getMessage());
        }

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
                'cart_ref' => '#' . str_pad((string) $cart->id, 6, '0', STR_PAD_LEFT),
                'cart_total_formatted' => trim($currencyLabel . ' ' . number_format($subtotal, 2)),
                'message' => $message,
            ]);
        }

        return back()->with($wasLimited ? 'error' : 'success', $message);
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $cart = Cart::query()->where('user_id', auth()->id())->first();
        if (!$cart || $item->cart_id !== $cart->id) {
            abort(403);
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        [$status, $cartQuantity] = DB::transaction(function () use ($item, $data): array {
            $lockedItem = CartItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $lockedProduct = Product::query()->whereKey($lockedItem->product_id)->lockForUpdate()->first();
            $maxQuantity = $lockedProduct ? $this->maxPurchasableQuantity($lockedProduct) : 0;

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
        $cart = Cart::query()->where('user_id', auth()->id())->first();
        if (!$cart || $item->cart_id !== $cart->id) {
            abort(403);
        }

        $item->delete();

        return back()->with('success', __('Item removed from cart.'));
    }

    public function resumePending(Request $request): RedirectResponse
    {
        $pending = session()->pull('pending_cart_action');

        if (! is_array($pending)) {
            return redirect()->route('user.shop.home');
        }

        $expiresAt = (int) ($pending['expires_at'] ?? 0);
        if ($expiresAt < now()->timestamp) {
            return redirect()->route('user.shop.home')
                ->with('error', __('Your add-to-cart session expired. Please try again.'));
        }

        $safeRedirect = $this->safeInternalUrl($pending['redirect_to'] ?? null);
        $product = Product::query()->find($pending['product_id'] ?? null);

        if (! $product || ! $product->is_active || $this->maxPurchasableQuantity($product) < 1) {
            return redirect()->to($safeRedirect)
                ->with('error', __('The item you were adding is no longer available.'));
        }

        $quantity = max(1, min(99, (int) ($pending['quantity'] ?? 1)));
        $cart = Cart::query()->firstOrCreate(['user_id' => auth()->id()]);

        try {
            [$wasLimited, $cartQuantity] = $this->performAuthenticatedAdd($cart, $product, $quantity);
        } catch (\RuntimeException $exception) {
            return redirect()->to($safeRedirect)->with('error', $exception->getMessage());
        }

        $message = $wasLimited
            ? __('Only :quantity available. Cart quantity was set to :quantity.', ['quantity' => $cartQuantity])
            : __('Added to cart successfully');

        return redirect()->route('cart.index')->with($wasLimited ? 'error' : 'success', $message);
    }

    private function storePendingAction(int $productId, int $quantity, string $redirectTo): void
    {
        session()->put('pending_cart_action', [
            'product_id' => $productId,
            'quantity' => max(1, min(99, $quantity)),
            'redirect_to' => $redirectTo,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);
    }

    private function safeInternalUrl(?string $url): string
    {
        $fallback = route('user.shop.home');

        if (! is_string($url) || $url === '') {
            return $fallback;
        }

        // Protocol-relative URLs (//evil.com/...) are dangerous.
        if (str_starts_with($url, '//')) {
            return $fallback;
        }

        // Same-origin relative paths.
        if (str_starts_with($url, '/')) {
            return $url;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);

        if ($appHost && $urlHost && strcasecmp($appHost, $urlHost) === 0) {
            return $url;
        }

        return $fallback;
    }

    private function performAuthenticatedAdd(Cart $cart, Product $product, int $quantity): array
    {
        return DB::transaction(function () use ($cart, $product, $quantity): array {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $maxQuantity = $this->maxPurchasableQuantity($lockedProduct);

            if (! $lockedProduct->is_active || $maxQuantity < 1) {
                throw new \RuntimeException(__('This product is not available right now.'));
            }

            $item = CartItem::query()->firstOrNew([
                'cart_id' => $cart->id,
                'product_id' => $lockedProduct->id,
            ]);

            $currentQty = $item->exists ? (int) $item->quantity : 0;
            $requestedTotal = $currentQty + $quantity;
            $item->quantity = min($maxQuantity, $requestedTotal);
            $item->save();

            return [$item->quantity < $requestedTotal, (int) $item->quantity];
        });
    }

    private function maxPurchasableQuantity(Product $product): int
    {
        return min(99, max(0, (int) $product->stock_quantity));
    }

    private function syncCartQuantitiesToStock(Cart $cart): bool
    {
        $changed = false;

        $cart->loadMissing('items.product');

        foreach ($cart->items as $item) {
            $product = $item->product;
            $maxQuantity = $product ? $this->maxPurchasableQuantity($product) : 0;

            if ($maxQuantity < 1) {
                $item->delete();
                $changed = true;
                continue;
            }

            if ((int) $item->quantity > $maxQuantity) {
                $item->update(['quantity' => $maxQuantity]);
                $changed = true;
            }
        }

        if ($changed) {
            $cart->unsetRelation('items');
        }

        return $changed;
    }
}
