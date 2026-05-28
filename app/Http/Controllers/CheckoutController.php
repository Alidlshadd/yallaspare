<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Discount;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\UserAddress;
use App\Services\CouponService;
use App\Support\UserCommunication;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const REVIEW_SESSION_KEY = 'checkout.review';
    private const COUPON_SESSION_KEY = 'checkout.coupon_code';
    private const BUY_NOW_COUPON_SESSION_KEY = 'checkout.buy_now_coupon_code';

    public function options(Request $request, Product $product): View|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
            'coupon_action' => ['nullable', 'in:apply,remove'],
        ]);

        $quantity = $this->clampQuantityToStock((int) ($data['quantity'] ?? 1), $product);
        if ($quantity < 1) {
            return back()->with('error', __('This product is not available right now.'));
        }

        return $this->showBuyNowReview($request, $product, $quantity, $data);
    }

    public function buyNow(Request $request, Product $product): View|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
            'coupon_action' => ['nullable', 'in:apply,remove'],
        ]);

        $quantity = $this->clampQuantityToStock((int) ($data['quantity'] ?? 1), $product);
        if ($quantity < 1) {
            return back()->with('error', __('This product is not available right now.'));
        }

        return $this->showBuyNowReview($request, $product, $quantity, $data);
    }

    public function placeBuyNow(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'address_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $request->user();
        $quantity = (int) $data['quantity'];
        $couponService = app(CouponService::class);
        $submittedCouponCode = $couponService->normalizeCode((string) ($data['coupon_code'] ?? ''));
        $couponCode = $submittedCouponCode !== ''
            ? $submittedCouponCode
            : $couponService->normalizeCode((string) $request->session()->get(self::BUY_NOW_COUPON_SESSION_KEY, ''));

        $address = UserAddress::query()
            ->where('user_id', $user->id)
            ->find($data['address_id']);

        if (! $address) {
            return back()->withErrors(['address_id' => __('Please select a valid saved address.')])->withInput();
        }

        try {
            $placedOrder = DB::transaction(function () use ($product, $quantity, $address, $data, $user, $couponCode, $couponService): Order {
                $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

                if (! $lockedProduct->is_active) {
                    throw new \RuntimeException(__('This product is not available right now.'));
                }

                if ($lockedProduct->stock_quantity < $quantity) {
                    throw new \RuntimeException(__('Insufficient stock for :product.', ['product' => $lockedProduct->name]));
                }

                $unitPrice = (float) $lockedProduct->priceFor($user);
                $subtotal = round($unitPrice * $quantity, 2);
                $shippingFee = $this->shippingFee();
                $couponPreview = ['valid' => false, 'coupon' => null, 'discount' => 0.0, 'free_shipping' => false, 'code' => ''];
                if ($couponCode !== '') {
                    $couponPreview = $couponService->preview($couponCode, $subtotal, $user);
                    if (! $couponPreview['valid']) {
                        throw new \RuntimeException($couponPreview['message'] ?? __('Coupon could not be applied.'));
                    }
                }
                $couponDiscount = (float) ($couponPreview['discount'] ?? 0);
                $couponShippingDiscount = ($couponPreview['free_shipping'] ?? false) ? $shippingFee : 0.0;
                $discountAmount = round($couponDiscount + $couponShippingDiscount, 2);
                $grandTotal = round(max(0, $subtotal + $shippingFee - $discountAmount), 2);

                $contactMethod = in_array($user?->default_contact_method, ['phone', 'email', 'whatsapp'], true)
                    ? $user->default_contact_method
                    : 'phone';
                $contactDestination = match ($contactMethod) {
                    'email' => (string) ($user?->email ?? $address->phone),
                    default => (string) ($address->phone ?: $user?->phone ?: $user?->email),
                };
                $baseNotes = trim((string) ($data['notes'] ?? $user?->default_delivery_note ?? ''));
                $notes = trim(collect([
                    $baseNotes !== '' ? $baseNotes : null,
                    'Preferred contact: ' . ucfirst($contactMethod),
                ])->filter()->implode(PHP_EOL));

                $order = new Order();
                $order->forceFill([
                    'user_id' => $user->id,
                    'order_number' => 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                    'subtotal_amount' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => $discountAmount,
                    'coupon_id' => ($couponPreview['coupon'] ?? null)?->exists ? $couponPreview['coupon']->id : null,
                    'coupon_code' => ($couponPreview['code'] ?? '') !== '' ? (string) $couponPreview['code'] : null,
                    'grand_total' => $grandTotal,
                    'total_amount' => $grandTotal,
                    'status' => 'pending',
                    'payment_method' => 'cash_on_delivery',
                    'payment_status' => Order::PAYMENT_PENDING,
                    'delivery_address' => trim(collect([$address->address_line1, $address->address_line2])->filter()->implode(', ')),
                    'delivery_city' => $address->city,
                    'delivery_phone' => $contactDestination,
                    'notes' => $notes !== '' ? $notes : null,
                ]);
                $order->save();

                $order->statusHistory()->create([
                    'from_status' => null,
                    'to_status' => $order->status,
                    'changed_by' => $user->id,
                    'note' => 'Order created',
                    'created_at' => now(),
                ]);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $lockedProduct->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $this->recordDiscountRuleUsage($lockedProduct->appliedDiscountRuleIds($user));

                if (($couponPreview['valid'] ?? false) && ($couponPreview['coupon'] ?? null)?->exists) {
                    $couponService->recordUsage($couponPreview['coupon'], (int) $user->id, (int) $order->id, $discountAmount);
                }

                $stockBefore = (int) $lockedProduct->stock_quantity;
                $stockAfter = $stockBefore - $quantity;
                $lockedProduct->update(['stock_quantity' => $stockAfter]);

                InventoryMovement::query()->create([
                    'product_id' => $lockedProduct->id,
                    'user_id' => $user->id,
                    'type' => InventoryMovement::TYPE_OUT,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference' => $order->order_number,
                    'note' => 'Order placed',
                ]);

                return $order;
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        $channels = UserCommunication::sendOrderPlaced($user, $placedOrder);
        $successMessage = __('Order placed successfully.');
        if ($channels !== []) {
            $successMessage .= ' ' . __('Confirmation sent via :channels.', ['channels' => implode(', ', $channels)]);
        }

        $request->session()->forget(self::BUY_NOW_COUPON_SESSION_KEY);

        return redirect()->route('checkout.success', $placedOrder)->with('success', $successMessage);
    }

    public function review(Request $request): View|RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = $request->user();
        if ($request->isMethod('get')) {
            $savedReview = $request->session()->get(self::REVIEW_SESSION_KEY);

            if (!is_array($savedReview)) {
                return redirect()
                    ->route('cart.index')
                    ->with('error', __('Review your cart before placing the order.'));
            }

            $selectedAddressId = isset($savedReview['address_id']) ? (int) $savedReview['address_id'] : null;
            $notes = (string) ($savedReview['notes'] ?? '');
        } else {
            $data = $request->validate([
                'address_id' => ['nullable', 'integer'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'coupon_code' => ['nullable', 'string', 'max:80'],
                'coupon_action' => ['nullable', 'in:apply,remove'],
            ]);

            $selectedAddressId = isset($data['address_id']) ? (int) $data['address_id'] : null;
            $notes = (string) ($data['notes'] ?? '');
        }

        $addressQuery = UserAddress::query()->where('user_id', auth()->id());
        $address = $selectedAddressId
            ? (clone $addressQuery)->find($selectedAddressId)
            : null;

        if (! $address && ($user?->express_checkout ?? false)) {
            $address = (clone $addressQuery)->orderByDesc('is_default')->latest('id')->first();
        }

        if (! $address) {
            if ($request->isMethod('get')) {
                $request->session()->forget(self::REVIEW_SESSION_KEY);

                return redirect()
                    ->route('cart.index')
                    ->with('error', __('Please select a valid saved address before reviewing your order.'));
            }

            return back()->withErrors([
                'address_id' => __('Please select a valid saved address.'),
            ])->withInput();
        }

        $cart = Cart::query()
            ->where('user_id', auth()->id())
            ->with('items.product')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            $request->session()->forget(self::REVIEW_SESSION_KEY);

            return redirect()->route('cart.index')->with('error', __('Cart is empty.'));
        }

        if ($request->isMethod('post')) {
            $request->session()->put(self::REVIEW_SESSION_KEY, [
                'address_id' => $address->id,
                'notes' => $notes,
            ]);
        }

        $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');
        $subtotal = (float) $cart->items->sum(function ($item) use ($user) {
            $product = $item->product;

            if (! $product) {
                return 0;
            }

            return $product->priceFor($user) * $item->quantity;
        });
        $shippingFee = $this->shippingFee();
        $couponService = app(CouponService::class);
        $couponCode = (string) $request->session()->get(self::COUPON_SESSION_KEY, '');

        if ($request->isMethod('post')) {
            $submittedCouponCode = $couponService->normalizeCode((string) ($data['coupon_code'] ?? ''));
            $couponAction = (string) ($data['coupon_action'] ?? '');
            if ($couponAction === '' && $submittedCouponCode !== '') {
                $couponAction = 'apply';
            }

            if ($couponAction === 'remove') {
                $request->session()->forget(self::COUPON_SESSION_KEY);
                $couponCode = '';
            } elseif ($couponAction === 'apply') {
                $couponCode = $submittedCouponCode;
                $couponPreview = $couponService->preview($couponCode, round($subtotal, 2), $user);

                if (! $couponPreview['valid']) {
                    $request->session()->forget(self::COUPON_SESSION_KEY);

                    return back()
                        ->withErrors(['coupon_code' => $couponPreview['message'] ?? __('Coupon could not be applied.')])
                        ->withInput();
                }

                $request->session()->put(self::COUPON_SESSION_KEY, $couponCode);
            }
        }

        $couponPreview = $couponCode !== ''
            ? $couponService->preview($couponCode, round($subtotal, 2), $user)
            : ['valid' => false, 'discount' => 0.0, 'free_shipping' => false, 'code' => '', 'message' => null];
        if (! $couponPreview['valid']) {
            $couponPreview['discount'] = 0.0;
            $couponPreview['free_shipping'] = false;
        }
        $couponDiscount = (float) $couponPreview['discount'];
        $couponShippingDiscount = $couponPreview['free_shipping'] ? $shippingFee : 0.0;
        $discountAmount = round($couponDiscount + $couponShippingDiscount, 2);
        $grandTotal = round(max(0, $subtotal + $shippingFee - $discountAmount), 2);

        return view('shop.checkout-review', [
            'cart' => $cart,
            'items' => $cart->items,
            'address' => $address,
            'notes' => $notes,
            'subtotal' => round($subtotal, 2),
            'shippingFee' => $shippingFee,
            'discountAmount' => $discountAmount,
            'grandTotal' => $grandTotal,
            'couponSummary' => $couponPreview,
            'currencySymbol' => $currencyLabel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = $request->user();
        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $addressQuery = UserAddress::query()->where('user_id', auth()->id());
        $selectedAddressId = isset($data['address_id']) ? (int) $data['address_id'] : null;
        $address = $selectedAddressId
            ? (clone $addressQuery)->find($selectedAddressId)
            : null;

        if (! $address && ($user?->express_checkout ?? false)) {
            $address = (clone $addressQuery)->orderByDesc('is_default')->latest('id')->first();
        }

        if (! $address) {
            return back()->withErrors([
                'address_id' => __('Please select a valid saved address.'),
            ])->withInput();
        }

        $cart = Cart::query()
            ->where('user_id', auth()->id())
            ->with('items.product')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return back()->with('error', __('Cart is empty.'));
        }

        $placedOrder = null;
        $couponCode = app(CouponService::class)->normalizeCode((string) $request->session()->get(self::COUPON_SESSION_KEY, ''));

        try {
            $placedOrder = DB::transaction(function () use ($cart, $data, $address, $user, $couponCode): Order {
                $productIds = $cart->items
                    ->pluck('product_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $products = Product::query()
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $subtotalAmount = 0;
                $lineItems = [];
                foreach ($cart->items as $item) {
                    $product = $products->get($item->product_id);
                    if (!$product) {
                        continue;
                    }

                    if ($product->stock_quantity < $item->quantity) {
                        throw new \RuntimeException(__('Insufficient stock for :product.', ['product' => $product->name]));
                    }

                    $unitPrice = $product->priceFor(auth()->user());
                    $lineItems[] = [
                        'product' => $product,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => round($unitPrice * $item->quantity, 2),
                    ];
                    $subtotalAmount += $unitPrice * $item->quantity;
                }

                $subtotalAmount = round($subtotalAmount, 2);
                $shippingFee = $this->shippingFee();
                $couponPreview = ['valid' => false, 'coupon' => null, 'discount' => 0.0, 'free_shipping' => false, 'code' => ''];
                if ($couponCode !== '') {
                    $couponPreview = app(CouponService::class)->preview($couponCode, $subtotalAmount, $user);
                    if (! $couponPreview['valid']) {
                        throw new \RuntimeException($couponPreview['message'] ?? __('Coupon could not be applied.'));
                    }
                }
                $couponDiscount = (float) ($couponPreview['discount'] ?? 0);
                $couponShippingDiscount = ($couponPreview['free_shipping'] ?? false) ? $shippingFee : 0.0;
                $discountAmount = round($couponDiscount + $couponShippingDiscount, 2);
                $grandTotal = round(max(0, $subtotalAmount + $shippingFee - $discountAmount), 2);

                $contactMethod = in_array($user?->default_contact_method, ['phone', 'email', 'whatsapp'], true)
                    ? $user->default_contact_method
                    : 'phone';
                $contactDestination = match ($contactMethod) {
                    'email' => (string) ($user?->email ?? $address->phone),
                    default => (string) ($address->phone ?: $user?->phone ?: $user?->email),
                };
                $baseNotes = trim((string) ($data['notes'] ?? $user?->default_delivery_note ?? ''));
                $notes = trim(collect([
                    $baseNotes !== '' ? $baseNotes : null,
                    'Preferred contact: ' . ucfirst($contactMethod),
                ])->filter()->implode(PHP_EOL));

                $order = new Order();
                $order->forceFill([
                    'user_id' => auth()->id(),
                    'order_number' => 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                    'subtotal_amount' => $subtotalAmount,
                    'shipping_fee' => $shippingFee,
                    'discount_amount' => $discountAmount,
                    'coupon_id' => ($couponPreview['coupon'] ?? null)?->exists ? $couponPreview['coupon']->id : null,
                    'coupon_code' => ($couponPreview['code'] ?? '') !== '' ? (string) $couponPreview['code'] : null,
                    'grand_total' => $grandTotal,
                    'total_amount' => $grandTotal,
                    'status' => 'pending',
                    'payment_method' => 'cash_on_delivery',
                    'payment_status' => Order::PAYMENT_PENDING,
                    'delivery_address' => trim(collect([$address->address_line1, $address->address_line2])->filter()->implode(', ')),
                    'delivery_city' => $address->city,
                    'delivery_phone' => $contactDestination,
                    'notes' => $notes !== '' ? $notes : null,
                ]);
                $order->save();

                if (!$order->statusHistory()->exists()) {
                    $order->statusHistory()->create([
                        'from_status' => null,
                        'to_status' => $order->status,
                        'changed_by' => auth()->id(),
                        'note' => 'Order created',
                        'created_at' => now(),
                    ]);
                }

                foreach ($lineItems as $line) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'subtotal' => $line['subtotal'],
                    ]);

                    $stockBefore = (int) $line['product']->stock_quantity;
                    $stockAfter = $stockBefore - (int) $line['quantity'];
                    $line['product']->update(['stock_quantity' => $stockAfter]);

                    InventoryMovement::query()->create([
                        'product_id' => $line['product_id'],
                        'user_id' => auth()->id(),
                        'type' => InventoryMovement::TYPE_OUT,
                        'quantity' => (int) $line['quantity'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference' => $order->order_number,
                        'note' => 'Order placed',
                    ]);
                }

                if (($couponPreview['valid'] ?? false) && ($couponPreview['coupon'] ?? null)?->exists) {
                    app(CouponService::class)->recordUsage($couponPreview['coupon'], (int) $user->id, (int) $order->id, $discountAmount);
                }

                $discountRuleIds = [];
                foreach ($lineItems as $line) {
                    $discountRuleIds = array_merge(
                        $discountRuleIds,
                        $line['product']->appliedDiscountRuleIds($user)
                    );
                }
                $this->recordDiscountRuleUsage($discountRuleIds);

                $cart->items()->delete();
                return $order;
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $channels = [];
        if ($placedOrder && $user) {
            $channels = UserCommunication::sendOrderPlaced($user, $placedOrder);
        }

        $successMessage = __('Order placed successfully.');

        if ($channels !== []) {
            $successMessage .= ' ' . __('Confirmation sent via :channels.', ['channels' => implode(', ', $channels)]);
        }

        $request->session()->forget(self::REVIEW_SESSION_KEY);
        $request->session()->forget(self::COUPON_SESSION_KEY);

        return redirect()
            ->route('checkout.success', $placedOrder)
            ->with('success', $successMessage);
    }

    public function success(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');
        $subtotal = (float) ($order->subtotal_amount ?: $order->items()->sum('subtotal'));
        $shippingFee = (float) $order->shipping_fee;
        $discountAmount = (float) $order->discount_amount;
        $grandTotal = (float) ($order->grand_total ?: $subtotal + $shippingFee - $discountAmount);

        return view('shop.checkout-success', [
            'order' => $order->load(['items.product']),
            'currencySymbol' => $currencyLabel,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'discountAmount' => $discountAmount,
            'grandTotal' => $grandTotal,
        ]);
    }

    private function shippingFee(): float
    {
        return max(0, round((float) Setting::getValue('shipping_fee', 5000), 2));
    }

    private function showBuyNowReview(Request $request, Product $product, int $quantity, array $data = []): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $addresses = $user->addresses()->latest('is_default')->latest('id')->get();
        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();
        $selectedAddressId = isset($data['address_id']) ? (int) $data['address_id'] : (int) ($defaultAddress?->id ?? 0);
        $notes = (string) ($data['notes'] ?? $user->default_delivery_note ?? '');

        if (! $defaultAddress) {
            return redirect()->route('user.account.addresses')->with('error', __('Please add a delivery address first.'));
        }

        $currencyLabel = (string) Setting::getValue('currency_code', 'IQD');
        $unitPrice = (float) $product->priceFor($user);
        $subtotal = round($unitPrice * $quantity, 2);
        $shippingFee = $this->shippingFee();
        $couponService = app(CouponService::class);
        $couponCode = (string) $request->session()->get(self::BUY_NOW_COUPON_SESSION_KEY, '');
        $couponAction = (string) ($data['coupon_action'] ?? '');

        if ($couponAction === 'remove') {
            $request->session()->forget(self::BUY_NOW_COUPON_SESSION_KEY);
            $couponCode = '';
        } elseif ($couponAction === 'apply') {
            $couponCode = $couponService->normalizeCode((string) ($data['coupon_code'] ?? ''));
            $couponPreview = $couponService->preview($couponCode, $subtotal, $user);

            if (! $couponPreview['valid']) {
                $request->session()->forget(self::BUY_NOW_COUPON_SESSION_KEY);

                return back()
                    ->withErrors(['coupon_code' => $couponPreview['message'] ?? __('Coupon could not be applied.')])
                    ->withInput();
            }

            $request->session()->put(self::BUY_NOW_COUPON_SESSION_KEY, $couponCode);
        }

        $couponPreview = $couponCode !== ''
            ? $couponService->preview($couponCode, $subtotal, $user)
            : ['valid' => false, 'discount' => 0.0, 'free_shipping' => false, 'code' => '', 'message' => null];
        if (! $couponPreview['valid']) {
            $couponPreview['discount'] = 0.0;
            $couponPreview['free_shipping'] = false;
        }
        $couponDiscount = (float) $couponPreview['discount'];
        $couponShippingDiscount = $couponPreview['free_shipping'] ? $shippingFee : 0.0;
        $discountAmount = round($couponDiscount + $couponShippingDiscount, 2);

        return view('shop.buy-now-review', [
            'product' => $product,
            'quantity' => $quantity,
            'unitPrice' => $unitPrice,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'discountAmount' => $discountAmount,
            'grandTotal' => round(max(0, $subtotal + $shippingFee - $discountAmount), 2),
            'couponSummary' => $couponPreview,
            'currencySymbol' => $currencyLabel,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'selectedAddressId' => $selectedAddressId,
            'defaultDeliveryNote' => $notes,
        ]);
    }

    private function clampQuantityToStock(int $quantity, Product $product): int
    {
        $maxQuantity = min(99, max(0, (int) $product->stock_quantity));

        return min(max(1, $quantity), $maxQuantity);
    }

    /**
     * @param  array<int>  $discountRuleIds
     */
    private function recordDiscountRuleUsage(array $discountRuleIds): void
    {
        $ids = collect($discountRuleIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return;
        }

        Discount::query()->whereKey($ids)->increment('used_count');
    }
}
