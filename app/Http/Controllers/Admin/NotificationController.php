<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotificationRead;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\LowStockNotificationService;
use App\Services\LowStockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);
        $cacheTtl = max((int) config('performance.notification_cache_ttl', 300), 5);
        $bucketMinutes = max((int) config('performance.cache_bucket_minutes', 5), 1);
        $now = now();
        $cacheBucket = $now->copy()
            ->second(0)
            ->minute((int) floor($now->minute / $bucketMinutes) * $bucketMinutes)
            ->format('YmdHi');
        $cacheKey = sprintf(
            'admin:notifications:v1:threshold:%d:bucket:%s',
            $lowStockThreshold,
            $cacheBucket
        );

        $sharedPayload = Cache::remember($cacheKey, now()->addSeconds($cacheTtl), function () use ($lowStockThreshold) {
            $outOfStockProducts = Product::query()
                ->where('stock_quantity', '<=', 0)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(['id', 'name_en', 'name_ar', 'name_ku', 'stock_quantity', 'updated_at']);

            $lowStockProducts = app(LowStockService::class)->getLowStockProducts(5);

            $dealerRequests = User::query()
                ->where('role', User::ROLE_DEALER)
                ->where('dealer_status', User::DEALER_STATUS_INACTIVE)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'email', 'updated_at']);

            $outItems = $outOfStockProducts->map(fn ($product) => [
                'key' => $this->makeKey('out_of_stock', $product->id, $product->updated_at?->timestamp),
                'id' => $product->id,
                'title' => $product->name,
                'subtitle' => __('Out of stock'),
                'meta' => __('Stock: :count', ['count' => $product->stock_quantity]),
                'url' => route('admin.products.index', ['low_stock' => 1]),
                'updated_at' => optional($product->updated_at)->toIso8601String(),
            ])->values()->toArray();

            $lowItems = $lowStockProducts->map(fn ($product) => [
                'key' => app(LowStockNotificationService::class)->makeKey($product->id),
                'id' => $product->id,
                'title' => $product->name,
                'subtitle' => __('Low stock alert'),
                'meta' => __('Stock: :count', ['count' => $product->stock_quantity]),
                'url' => route('admin.products.index', ['low_stock' => 1]),
                'updated_at' => optional($product->updated_at)->toIso8601String(),
            ])->values()->toArray();

            $dealerItems = $dealerRequests->map(fn ($dealer) => [
                'key' => $this->makeKey('dealer_request', $dealer->id, $dealer->updated_at?->timestamp),
                'id' => $dealer->id,
                'title' => $dealer->name,
                'subtitle' => __('Dealer request pending review'),
                'meta' => $dealer->email,
                'url' => route('admin.dealers.index', ['status' => 'inactive']),
                'updated_at' => optional($dealer->updated_at)->toIso8601String(),
            ])->values()->toArray();

            $outCount = Product::where('stock_quantity', '<=', 0)->count();
            $lowCount = app(LowStockService::class)->getLowStockCount();
            $dealerRequestCount = User::where('role', User::ROLE_DEALER)
                ->where('dealer_status', User::DEALER_STATUS_INACTIVE)
                ->count();

            return [
                'counts' => [
                    'total' => $outCount + $lowCount + $dealerRequestCount,
                    'out_of_stock' => $outCount,
                    'low_stock' => $lowCount,
                    'dealer_requests' => $dealerRequestCount,
                ],
                'items' => [
                    'out_of_stock' => $outItems,
                    'low_stock' => $lowItems,
                    'dealer_requests' => $dealerItems,
                ],
            ];
        });

        $outItems = collect($sharedPayload['items']['out_of_stock'] ?? [])
            ->map(fn ($item) => $this->normalizeTimeField($item))
            ->values();
        $lowItems = collect($sharedPayload['items']['low_stock'] ?? [])
            ->map(fn ($item) => $this->normalizeTimeField($item))
            ->values();
        $dealerItems = collect($sharedPayload['items']['dealer_requests'] ?? [])
            ->map(fn ($item) => $this->normalizeTimeField($item))
            ->values();

        $allItems = collect()
            ->concat($outItems)
            ->concat($lowItems)
            ->concat($dealerItems);

        $readKeys = $this->readKeysFor(auth()->id(), $allItems);

        $outItems = $outItems->map(fn ($item) => array_merge($item, ['read' => in_array($item['key'], $readKeys, true)]));
        $lowItems = $lowItems->map(fn ($item) => array_merge($item, ['read' => in_array($item['key'], $readKeys, true)]));
        $dealerItems = $dealerItems->map(fn ($item) => array_merge($item, ['read' => in_array($item['key'], $readKeys, true)]));

        $outCount = (int) ($sharedPayload['counts']['out_of_stock'] ?? 0);
        $lowCount = (int) ($sharedPayload['counts']['low_stock'] ?? 0);
        $dealerRequestCount = (int) ($sharedPayload['counts']['dealer_requests'] ?? 0);

        $lowUnreadCount = app(LowStockNotificationService::class)
            ->getUnreadLowStockCount((int) auth()->id());

        $unreadTotal = collect()
            ->concat($outItems)
            ->concat($lowItems)
            ->concat($dealerItems)
            ->filter(fn ($item) => !$item['read'])
            ->count();

        return response()->json([
            'counts' => [
                'total' => (int) ($sharedPayload['counts']['total'] ?? ($outCount + $lowCount + $dealerRequestCount)),
                'unread_total' => $unreadTotal,
                'out_of_stock' => $outCount,
                'low_stock' => $lowCount,
                'low_stock_unread' => $lowUnreadCount,
                'dealer_requests' => $dealerRequestCount,
            ],
            'threshold' => $lowStockThreshold,
            'items' => [
                'out_of_stock' => $outItems,
                'low_stock' => $lowItems,
                'dealer_requests' => $dealerItems,
            ],
            'fetched_at' => now()->toIso8601String(),
        ]);
    }

    private function normalizeTimeField(array $item): array
    {
        $updatedAt = $item['updated_at'] ?? null;
        $humanTime = null;

        if (is_string($updatedAt) && $updatedAt !== '') {
            try {
                $humanTime = Carbon::parse($updatedAt)->diffForHumans();
            } catch (\Throwable $e) {
                $humanTime = null;
            }
        }

        $item['time'] = $humanTime;
        unset($item['updated_at']);

        return $item;
    }

    public function markRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'notification_key' => ['required', 'string', 'max:255'],
        ]);

        AdminNotificationRead::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'notification_key' => $data['notification_key'],
            ],
            [
                'read_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $keys = collect($request->input('notification_keys', []))
            ->filter(fn ($key) => is_string($key) && $key !== '')
            ->take(200)
            ->values();

        foreach ($keys as $key) {
            AdminNotificationRead::query()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'notification_key' => $key,
                ],
                [
                    'read_at' => now(),
                ]
            );
        }

        return response()->json(['ok' => true, 'count' => $keys->count()]);
    }

    private function readKeysFor(int $userId, Collection $items): array
    {
        if (!Schema::hasTable('admin_notification_reads')) {
            return [];
        }

        $keys = $items
            ->pluck('key')
            ->filter(fn ($key) => is_string($key) && $key !== '')
            ->values();

        if ($keys->isEmpty()) {
            return [];
        }

        return AdminNotificationRead::query()
            ->where('user_id', $userId)
            ->whereIn('notification_key', $keys->all())
            ->pluck('notification_key')
            ->toArray();
    }

    private function makeKey(string $type, int $id, ?int $timestamp): string
    {
        return $type . ':' . $id . ':' . ((string) ($timestamp ?? 0));
    }
}
