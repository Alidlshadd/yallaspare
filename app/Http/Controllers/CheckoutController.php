<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\UserAddress;
use App\Services\Analytics\CheckoutStartTracker;
use App\Services\Checkout\CheckoutService;
use App\Services\CouponService;
use App\Services\Payments\PaymentService;
use App\Support\UserCommunication;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const REVIEW_SESSION_KEY = 'checkout.review';
    private const COUPON_SESSION_KEY = 'checkout.coupon_code';
    private const BUY_NOW_COUPON_SESSION_KEY = 'checkout.buy_now_coupon_code';

    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly PaymentService $paymentService,
    )
    {
    }

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

        app(CheckoutStartTracker::class)->record($request, $product, $quantity);

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

        app(CheckoutStartTracker::class)->record($request, $product, $quantity);

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
            'payment_method' => ['nullable', Rule::in($this->paymentService->allowedCheckoutMethods())],
        ]);

        $user = $request->user();
        $quantity = (int) $data['quantity'];
        $couponService = app(CouponService::class);
        $submittedCouponCode = $couponService->normalizeCode((string) ($data['coupon_code'] ?? ''));
        $couponCode = $submittedCouponCode !== ''
            ? $submittedCouponCode
            : $couponService->normalizeCode((string) $request->session()->get(self::BUY_NOW_COUPON_SESSION_KEY, ''));
        $paymentMethod = (string) ($data['payment_method'] ?? PaymentService::METHOD_COD);

        $address = UserAddress::query()
            ->where('user_id', $user->id)
            ->find($data['address_id']);

        if (! $address) {
            return back()->withErrors(['address_id' => __('Please select a valid saved address.')])->withInput();
        }

        try {
            $placedOrder = $this->checkoutService->placeBuyNowOrder(
                $product,
                $quantity,
                $user,
                $address,
                $data['notes'] ?? null,
                $couponCode,
                $paymentMethod
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        if ($this->paymentService->isOnlineMethod($paymentMethod)) {
            try {
                $payment = $this->paymentService->start($placedOrder, $paymentMethod);
            } catch (\Throwable) {
                return redirect()
                    ->route('account.orders.show', $placedOrder)
                    ->with('error', __('Payment could not be started. Please contact support with your order number.'));
            }

            $request->session()->forget(self::BUY_NOW_COUPON_SESSION_KEY);

            return redirect()->away((string) $payment->redirect_url);
        }

        $channels = UserCommunication::sendOrderPlaced($user, $placedOrder);
        $successMessage = __('Order placed successfully.');
        if ($channels !== []) {
            $successMessage .= ' ' . __('Confirmation sent via :channels.', ['channels' => implode(', ', $channels)]);
        }

        $request->session()->forget(self::BUY_NOW_COUPON_SESSION_KEY);

        return redirect()->route('checkout.success', $placedOrder)->with('success', $successMessage);
    }

    public function delivery(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->with('items.product')
            ->first();

        $items = $cart?->items ?? collect();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('Cart is empty.'));
        }

        $subtotal = (float) $items->sum(function ($item) use ($user) {
            $product = $item->product;

            return $product ? $product->priceFor($user) * $item->quantity : 0;
        });

        $addresses = $user->addresses()->latest('is_default')->latest('id')->get();
        $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        return view('shop.checkout-delivery', [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'currencySymbol' => (string) Setting::getValue('currency_code', 'IQD'),
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'defaultDeliveryNote' => (string) ($user->default_delivery_note ?? ''),
            'defaultContactMethod' => (string) ($user->default_contact_method ?? 'phone'),
            'expressCheckout' => (bool) ($user->express_checkout ?? false),
        ]);
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
            'paymentMethods' => $this->paymentService->checkoutMethods(),
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
            'payment_method' => ['nullable', Rule::in($this->paymentService->allowedCheckoutMethods())],
        ]);
        $paymentMethod = (string) ($data['payment_method'] ?? PaymentService::METHOD_COD);

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
            $placedOrder = $this->checkoutService->placeCartOrder(
                $cart,
                $user,
                $address,
                $data['notes'] ?? null,
                $couponCode,
                $paymentMethod
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($this->paymentService->isOnlineMethod($paymentMethod)) {
            try {
                $payment = $this->paymentService->start($placedOrder, $paymentMethod);
            } catch (\Throwable) {
                return redirect()
                    ->route('account.orders.show', $placedOrder)
                    ->with('error', __('Payment could not be started. Please contact support with your order number.'));
            }

            $request->session()->forget(self::REVIEW_SESSION_KEY);
            $request->session()->forget(self::COUPON_SESSION_KEY);

            return redirect()->away((string) $payment->redirect_url);
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
            'paymentMethods' => $this->paymentService->checkoutMethods(),
        ]);
    }

    private function clampQuantityToStock(int $quantity, Product $product): int
    {
        $maxQuantity = min(99, max(0, (int) $product->stock_quantity));

        return min(max(1, $quantity), $maxQuantity);
    }

}
