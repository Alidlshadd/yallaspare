<?php

namespace App\Services\Checkout;

use App\Http\View\Composers\HeaderComposer;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\CouponService;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(private readonly CouponService $couponService)
    {
    }

    public function placeCartOrder(
        Cart $cart,
        User $user,
        UserAddress $address,
        ?string $notes,
        string $couponCode,
        string $paymentMethod = PaymentService::METHOD_COD
    ): Order {
        return DB::transaction(function () use ($cart, $user, $address, $notes, $couponCode, $paymentMethod): Order {
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

            $lineItems = [];
            foreach ($cart->items as $item) {
                $product = $products->get($item->product_id);
                if (! $product) {
                    continue;
                }

                if ($product->stock_quantity < $item->quantity) {
                    throw new \RuntimeException(__('Insufficient stock for :product.', ['product' => $product->name]));
                }

                $unitPrice = $product->priceFor($user);
                $lineItems[] = [
                    'product' => $product,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => round($unitPrice * $item->quantity, 2),
                ];
            }

            $order = $this->createOrder($lineItems, $user, $address, $notes, $couponCode, $paymentMethod);

            $cart->items()->delete();
            HeaderComposer::forgetCartCacheForUser((int) $user->id);

            return $order;
        });
    }

    public function placeBuyNowOrder(
        Product $product,
        int $quantity,
        User $user,
        UserAddress $address,
        ?string $notes,
        string $couponCode,
        string $paymentMethod = PaymentService::METHOD_COD
    ): Order {
        return DB::transaction(function () use ($product, $quantity, $user, $address, $notes, $couponCode, $paymentMethod): Order {
            $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if (! $lockedProduct->is_active) {
                throw new \RuntimeException(__('This product is not available right now.'));
            }

            if ($lockedProduct->stock_quantity < $quantity) {
                throw new \RuntimeException(__('Insufficient stock for :product.', ['product' => $lockedProduct->name]));
            }

            $unitPrice = (float) $lockedProduct->priceFor($user);

            return $this->createOrder([
                [
                    'product' => $lockedProduct,
                    'product_id' => $lockedProduct->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => round($unitPrice * $quantity, 2),
                ],
            ], $user, $address, $notes, $couponCode, $paymentMethod);
        });
    }

    /**
     * @param  array<int, array{product:Product,product_id:int,quantity:int,unit_price:float,subtotal:float}>  $lineItems
     */
    private function createOrder(
        array $lineItems,
        User $user,
        UserAddress $address,
        ?string $notesInput,
        string $couponCode,
        string $paymentMethod = PaymentService::METHOD_COD
    ): Order {
        $subtotalAmount = round(array_sum(array_map(
            fn (array $line): float => (float) $line['unit_price'] * (int) $line['quantity'],
            $lineItems
        )), 2);
        $shippingFee = $this->shippingFee();
        $couponPreview = ['valid' => false, 'coupon' => null, 'discount' => 0.0, 'free_shipping' => false, 'code' => ''];

        if ($couponCode !== '') {
            $couponPreview = $this->couponService->preview($couponCode, $subtotalAmount, $user);
            if (! $couponPreview['valid']) {
                throw new \RuntimeException($couponPreview['message'] ?? __('Coupon could not be applied.'));
            }
        }

        $couponDiscount = (float) ($couponPreview['discount'] ?? 0);
        $couponShippingDiscount = ($couponPreview['free_shipping'] ?? false) ? $shippingFee : 0.0;
        $discountAmount = round($couponDiscount + $couponShippingDiscount, 2);
        $grandTotal = round(max(0, $subtotalAmount + $shippingFee - $discountAmount), 2);
        $notes = $this->checkoutNotes($user, $notesInput);
        $paymentStatus = $paymentMethod === PaymentService::METHOD_COD
            ? Order::PAYMENT_PENDING
            : Order::PAYMENT_PENDING_PAYMENT;

        $order = new Order();
        $order->forceFill([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
            'subtotal_amount' => $subtotalAmount,
            'shipping_fee' => $shippingFee,
            'discount_amount' => $discountAmount,
            'coupon_id' => ($couponPreview['coupon'] ?? null)?->exists ? $couponPreview['coupon']->id : null,
            'coupon_code' => ($couponPreview['code'] ?? '') !== '' ? (string) $couponPreview['code'] : null,
            'grand_total' => $grandTotal,
            'total_amount' => $grandTotal,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'delivery_address' => trim(collect([$address->address_line1, $address->address_line2])->filter()->implode(', ')),
            'delivery_city' => $address->city,
            'delivery_phone' => $this->contactDestination($user, $address),
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
                'user_id' => $user->id,
                'type' => InventoryMovement::TYPE_OUT,
                'quantity' => (int) $line['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference' => $order->order_number,
                'note' => 'Order placed',
            ]);
        }

        if (($couponPreview['valid'] ?? false) && ($couponPreview['coupon'] ?? null)?->exists) {
            $this->couponService->recordUsage($couponPreview['coupon'], (int) $user->id, (int) $order->id, $discountAmount);
        }

        $discountRuleIds = [];
        foreach ($lineItems as $line) {
            $discountRuleIds = array_merge(
                $discountRuleIds,
                $line['product']->appliedDiscountRuleIds($user)
            );
        }
        $this->recordDiscountRuleUsage($discountRuleIds);

        return $order;
    }

    private function shippingFee(): float
    {
        return max(0, round((float) Setting::getValue('shipping_fee', 5000), 2));
    }

    private function contactDestination(User $user, UserAddress $address): string
    {
        $contactMethod = in_array($user->default_contact_method, ['phone', 'email', 'whatsapp'], true)
            ? $user->default_contact_method
            : 'phone';

        return match ($contactMethod) {
            'email' => (string) ($user->email ?? $address->phone),
            default => (string) ($address->phone ?: $user->phone ?: $user->email),
        };
    }

    private function checkoutNotes(User $user, ?string $notesInput): string
    {
        $contactMethod = in_array($user->default_contact_method, ['phone', 'email', 'whatsapp'], true)
            ? $user->default_contact_method
            : 'phone';
        $baseNotes = trim((string) ($notesInput ?? $user->default_delivery_note ?? ''));

        return trim(collect([
            $baseNotes !== '' ? $baseNotes : null,
            'Preferred contact: ' . ucfirst($contactMethod),
        ])->filter()->implode(PHP_EOL));
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
