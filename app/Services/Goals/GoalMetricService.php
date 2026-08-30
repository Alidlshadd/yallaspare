<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GoalMetricService
{
    public const REVENUE = 'revenue';

    public const ORDERS = 'orders';

    public const DELIVERED_ORDERS = 'delivered_orders';

    public const NEW_CUSTOMERS = 'new_customers';

    public const PRODUCTS_ADDED = 'products_added';

    public const UNITS_SOLD = 'units_sold';

    public const PENDING_ORDERS = 'pending_orders';

    public const LOW_STOCK_PRODUCTS = 'low_stock_products';

    public const OUT_OF_STOCK_PRODUCTS = 'out_of_stock_products';

    public function definitions(): array
    {
        return [
            self::REVENUE => ['label' => __('Revenue'), 'unit' => 'iqd', 'direction' => 'increase', 'filterable' => false],
            self::ORDERS => ['label' => __('Orders'), 'unit' => 'orders', 'direction' => 'increase', 'filterable' => false],
            self::DELIVERED_ORDERS => ['label' => __('Delivered orders'), 'unit' => 'orders', 'direction' => 'increase', 'filterable' => false],
            self::NEW_CUSTOMERS => ['label' => __('New customers'), 'unit' => 'customers', 'direction' => 'increase', 'filterable' => false],
            self::PRODUCTS_ADDED => ['label' => __('Products added'), 'unit' => 'products', 'direction' => 'increase', 'filterable' => false],
            self::UNITS_SOLD => ['label' => __('Units sold'), 'unit' => 'products', 'direction' => 'increase', 'filterable' => true],
            self::PENDING_ORDERS => ['label' => __('Pending orders'), 'unit' => 'orders', 'direction' => 'decrease', 'filterable' => false],
            self::LOW_STOCK_PRODUCTS => ['label' => __('Low-stock products'), 'unit' => 'products', 'direction' => 'decrease', 'filterable' => false],
            self::OUT_OF_STOCK_PRODUCTS => ['label' => __('Out-of-stock products'), 'unit' => 'products', 'direction' => 'decrease', 'filterable' => false],
        ];
    }

    public function keys(): array
    {
        return array_keys($this->definitions());
    }

    public function value(Goal $goal, array $range): float
    {
        return $this->valueFor((string) $goal->metric_key, $goal->metric_config ?? [], $range);
    }

    public function valueFor(string $metric, array $config, array $range): float
    {
        $start = $range['start'];
        $end = $range['end_exclusive'];

        return match ($metric) {
            self::REVENUE => (float) $this->ordersBase()->whereIn('status', [Order::STATUS_DELIVERED, 'completed'])->where('created_at', '>=', $start)->where('created_at', '<', $end)->sum('total_amount'),
            self::ORDERS => (float) $this->ordersBase()->whereNotIn('status', [Order::STATUS_CANCELLED, 'canceled'])->where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            self::DELIVERED_ORDERS => (float) $this->ordersBase()->whereIn('status', [Order::STATUS_DELIVERED, 'completed'])->where('delivered_at', '>=', $start)->where('delivered_at', '<', $end)->count(),
            self::NEW_CUSTOMERS => (float) User::query()->where('role', User::ROLE_USER)->where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            self::PRODUCTS_ADDED => (float) Product::query()->where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            self::UNITS_SOLD => $this->unitsSold($config, $start, $end),
            self::PENDING_ORDERS => (float) $this->ordersBase()->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])->count(),
            self::LOW_STOCK_PRODUCTS => (float) Product::query()->lowStock()->count(),
            self::OUT_OF_STOCK_PRODUCTS => (float) Product::query()->where('is_active', true)->where('stock_quantity', '<=', 0)->count(),
            default => 0.0,
        };
    }

    private function ordersBase(): Builder
    {
        return Order::query()->whereNull('archived_at');
    }

    private function unitsSold(array $config, mixed $start, mixed $end): float
    {
        $query = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereNull('orders.archived_at')->whereIn('orders.status', [Order::STATUS_DELIVERED, 'completed'])
            ->where('orders.created_at', '>=', $start)->where('orders.created_at', '<', $end);

        foreach (['category_id', 'product_brand_id'] as $column) {
            if (! empty($config[$column])) {
                $query->where('products.'.$column, (int) $config[$column]);
            }
        }
        if (! empty($config['vehicle_brand_id'])) {
            $query->whereExists(function ($subquery) use ($config): void {
                $subquery->selectRaw('1')->from('product_vehicle_fitments')->whereColumn('product_vehicle_fitments.product_id', 'products.id')
                    ->where('product_vehicle_fitments.vehicle_brand_id', (int) $config['vehicle_brand_id']);
            });
        }

        return (float) $query->sum('order_items.quantity');
    }
}
