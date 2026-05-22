<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LowStockNotificationService
{
    private const CACHE_TTL_SECONDS = 30;

    public function makeKey(int $productId): string
    {
        return 'low_stock:' . $productId;
    }

    public function getUnreadLowStockCount(int $userId): int
    {
        $cacheKey = 'notifications:low_stock:unread_count:user:' . $userId;

        return (int) Cache::remember($cacheKey, now()->addSeconds(self::CACHE_TTL_SECONDS), function () use ($userId) {
            return Product::query()
                ->lowStock()
                ->leftJoin('admin_notification_reads as anr', function ($join) use ($userId) {
                    $join->on('anr.notification_key', '=', DB::raw($this->notificationKeyExpression()))
                        ->where('anr.user_id', '=', $userId);
                })
                ->whereNull('anr.id')
                ->count();
        });
    }

    public function getUnreadLowStockProducts(int $userId, int $limit = 10)
    {
        $limit = max(1, min($limit, 100));

        return Product::query()
            ->lowStock()
            ->leftJoin('admin_notification_reads as anr', function ($join) use ($userId) {
                $join->on('anr.notification_key', '=', DB::raw($this->notificationKeyExpression()))
                    ->where('anr.user_id', '=', $userId);
            })
            ->whereNull('anr.id')
            ->orderBy('products.stock_quantity')
            ->orderByDesc('products.updated_at')
            ->limit($limit)
            ->get([
                'products.id',
                'products.name_en',
                'products.stock_quantity',
                'products.updated_at',
            ]);
    }

    private function notificationKeyExpression(): string
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return "CONCAT('low_stock:', products.id)";
        }

        return "'low_stock:' || products.id";
    }
}
