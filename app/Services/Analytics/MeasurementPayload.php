<?php

namespace App\Services\Analytics;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Turns what the shop is looking at into the shape a measurement tag expects.
 *
 * Kept apart from ClientAnalytics so that one stays a transport — collect an
 * event, render it once — while the question of what a product is worth to
 * this particular visitor is answered here, by the same priceFor() the page
 * itself used.
 *
 * Nothing identifying goes in. Names, phone numbers and addresses are the
 * shop's business and stay in the shop's own tables; an ad platform gets an
 * id, a quantity and a price.
 */
class MeasurementPayload
{
    /**
     * @return array{currency: string, value: float, items: array<int, array{id: int, name: string, price: float, quantity: int}>}
     */
    public static function forProduct(Product $product, int $quantity = 1, ?User $user = null): array
    {
        $unitPrice = (float) $product->priceFor($user);
        $quantity = max(1, $quantity);

        return [
            'currency' => self::currency(),
            'value' => round($unitPrice * $quantity, 2),
            'items' => [self::item($product, $quantity, $unitPrice)],
        ];
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @return array{currency: string, value: float, items: array<int, array{id: int, name: string, price: float, quantity: int}>}
     */
    public static function forCart(Collection $items, ?User $user = null): array
    {
        $lines = [];
        $value = 0.0;

        foreach ($items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            $quantity = max(1, (int) $item->quantity);
            $unitPrice = (float) $product->priceFor($user);
            $value += $unitPrice * $quantity;
            $lines[] = self::item($product, $quantity, $unitPrice);
        }

        return [
            'currency' => self::currency(),
            'value' => round($value, 2),
            'items' => $lines,
        ];
    }

    /**
     * The order as placed — its own recorded totals, not today's prices, so a
     * later price change cannot rewrite what a campaign earned.
     *
     * @return array{currency: string, value: float, items: array<int, array{id: int, name: string, price: float, quantity: int}>}
     */
    public static function forOrder(Order $order): array
    {
        $order->loadMissing('items.product');
        $lines = [];

        foreach ($order->items as $item) {
            $lines[] = array_filter([
                'id' => (int) $item->product_id,
                'name' => $item->product?->localizedName() ?: $item->soldName(),
                'price' => round((float) $item->unit_price, 2),
                'quantity' => max(1, (int) $item->quantity),
            ], fn ($value): bool => $value !== '');
        }

        return [
            'currency' => self::currency(),
            'value' => round((float) ($order->grand_total ?: $order->total_amount), 2),
            'items' => $lines,
        ];
    }

    /**
     * @return array{id: int, name: string, price: float, quantity: int}
     */
    private static function item(Product $product, int $quantity, float $unitPrice): array
    {
        return [
            'id' => (int) $product->id,
            'name' => $product->localizedName(),
            'price' => round($unitPrice, 2),
            'quantity' => $quantity,
        ];
    }

    private static function currency(): string
    {
        return (string) Setting::getValue('currency_code', 'IQD');
    }
}
