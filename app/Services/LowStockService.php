<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class LowStockService
{
    private const CACHE_KEY = 'inventory_low_stock_count';
    private const PRODUCTS_CACHE_KEY = 'inventory_low_stock_products';

    public function getLowStockCount(): int
    {
        return (int) Cache::remember(self::CACHE_KEY, now()->addMinutes(5), function () {
            return Product::query()->lowStock()->count();
        });
    }

    public function getLowStockProducts(int $limit = 10)
    {
        $limit = max(1, min($limit, 100));
        $cacheKey = self::PRODUCTS_CACHE_KEY . ':' . $limit;

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($limit) {
            return Product::query()
                ->lowStock()
                ->orderBy('stock_quantity')
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get(['id', 'name_en', 'stock_quantity', 'updated_at']);
        });
    }
}
