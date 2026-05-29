<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $paidStatuses = ['delivered', 'completed'];
        $lowStockThreshold = max((int) Setting::getValue('low_stock_threshold', config('inventory.low_stock_threshold', 5)), 0);
        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;
        $allowedAnalyticsDays = [7, 14, 30];
        $analyticsDays = (int) $request->query('analytics_days', 14);
        if (!in_array($analyticsDays, $allowedAnalyticsDays, true)) {
            $analyticsDays = 14;
        }
        $cacheTtl = max((int) config('performance.dashboard_cache_ttl', 300), 15);
        $bucketMinutes = max((int) config('performance.cache_bucket_minutes', 5), 1);
        $cacheBucket = $now->copy()
            ->second(0)
            ->minute((int) floor($now->minute / $bucketMinutes) * $bucketMinutes)
            ->format('YmdHi');
        $cacheKey = sprintf(
            'admin:dashboard:v2:days:%d:threshold:%d:bucket:%s',
            $analyticsDays,
            $lowStockThreshold,
            $cacheBucket
        );

        $dashboardData = Cache::remember($cacheKey, now()->addSeconds($cacheTtl), function () use (
            $analyticsDays,
            $lowStockThreshold,
            $now,
            $paidStatuses
        ) {
            $startCurrentMonth = (clone $now)->startOfMonth();
            $startPreviousMonth = (clone $startCurrentMonth)->subMonth();
            $endPreviousMonth = (clone $startCurrentMonth)->subSecond();

            $totalProducts = Product::count();
            $totalOrders = Order::count();
            $totalUsers = User::count();
            $totalRevenue = (float) Order::whereIn('status', $paidStatuses)->sum('total_amount');
            $lowStockCount = Product::where('stock_quantity', '<=', $lowStockThreshold)->count();
            $outOfStockCount = Product::where('stock_quantity', '<=', 0)->count();
            $lowStockProducts = Product::query()
                ->select(['id', 'name_en', 'name_ar', 'name_ku', 'sku', 'stock_quantity', 'created_at'])
                ->where('stock_quantity', '<=', $lowStockThreshold)
                ->orderBy('stock_quantity')
                ->limit(5)
                ->get();
            $recentProducts = Product::query()
                ->select(['id', 'name_en', 'name_ar', 'name_ku', 'sku', 'stock_quantity', 'created_at'])
                ->latest()
                ->limit(5)
                ->get();
            $recentProductsCount = Product::whereBetween('created_at', [$startCurrentMonth, $now])->count();

            $currentProductsAdded = Product::whereBetween('created_at', [$startCurrentMonth, $now])->count();
            $previousProductsAdded = Product::whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])->count();
            $productsTrendPercent = $this->percentageChange($currentProductsAdded, $previousProductsAdded);

            $currentUsersAdded = User::whereBetween('created_at', [$startCurrentMonth, $now])->count();
            $previousUsersAdded = User::whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])->count();
            $usersTrendPercent = $this->percentageChange($currentUsersAdded, $previousUsersAdded);

            $currentLowStockNew = Product::where('stock_quantity', '<=', $lowStockThreshold)
                ->whereBetween('created_at', [$startCurrentMonth, $now])
                ->count();
            $previousLowStockNew = Product::where('stock_quantity', '<=', $lowStockThreshold)
                ->whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])
                ->count();
            $lowStockTrendPercent = $this->percentageChange($currentLowStockNew, $previousLowStockNew);

            $currentOutOfStockNew = Product::where('stock_quantity', '<=', 0)
                ->whereBetween('created_at', [$startCurrentMonth, $now])
                ->count();
            $previousOutOfStockNew = Product::where('stock_quantity', '<=', 0)
                ->whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])
                ->count();
            $outOfStockTrendPercent = $this->percentageChange($currentOutOfStockNew, $previousOutOfStockNew);
            $recentProductsTrendPercent = $productsTrendPercent;

            $currentMonthOrders = Order::whereBetween('created_at', [$startCurrentMonth, $now])->count();
            $previousMonthOrders = Order::whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])->count();
            $ordersGrowth = $this->percentageChange($currentMonthOrders, $previousMonthOrders);

            $currentMonthUsers = User::whereBetween('created_at', [$startCurrentMonth, $now])->count();
            $previousMonthUsers = User::whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])->count();
            $usersGrowth = $this->percentageChange($currentMonthUsers, $previousMonthUsers);

            $currentMonthRevenue = (float) Order::whereIn('status', $paidStatuses)
                ->whereBetween('created_at', [$startCurrentMonth, $now])
                ->sum('total_amount');
            $previousMonthRevenue = (float) Order::whereIn('status', $paidStatuses)
                ->whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])
                ->sum('total_amount');
            $revenueGrowth = $this->percentageChange($currentMonthRevenue, $previousMonthRevenue);

            $todaySales = (float) Order::whereIn('status', $paidStatuses)
                ->whereDate('created_at', $now->toDateString())
                ->sum('total_amount');
            $yesterdaySales = (float) Order::whereIn('status', $paidStatuses)
                ->whereDate('created_at', $now->copy()->subDay()->toDateString())
                ->sum('total_amount');
            $salesChangePercent = $this->percentageChange($todaySales, $yesterdaySales);

            $pendingOrders = Order::whereIn('status', ['pending', 'processing'])->count();
            $unpaidOrders = Order::where('payment_status', Order::PAYMENT_PENDING)->count();
            $newCustomers = $currentMonthUsers;
            $todayPendingOrders = Order::query()
                ->whereNull('archived_at')
                ->where('status', Order::STATUS_PENDING)
                ->whereDate('created_at', $now->toDateString())
                ->count();
            $needsShippingOrders = Order::query()
                ->whereNull('archived_at')
                ->where('status', Order::STATUS_PROCESSING)
                ->count();
            $cancellationRequestOrders = Order::query()
                ->whereNull('archived_at')
                ->whereNotNull('cancellation_requested_at')
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->count();
            $openReturnRequests = Schema::hasTable('return_requests')
                ? ReturnRequest::query()
                    ->whereIn('status', [
                        ReturnRequest::STATUS_REQUESTED,
                        ReturnRequest::STATUS_APPROVED,
                        ReturnRequest::STATUS_RECEIVED,
                    ])
                    ->count()
                : 0;
            $operationsQueue = [
                [
                    'label' => __('Today pending orders'),
                    'count' => $todayPendingOrders,
                    'description' => __('New orders placed today waiting for first action.'),
                    'tone' => 'amber',
                    'icon' => 'fa-clock',
                    'url' => route('admin.orders.index', ['attention' => 'today_pending']),
                ],
                [
                    'label' => __('Needs shipping'),
                    'count' => $needsShippingOrders,
                    'description' => __('Processing orders ready to move toward shipped.'),
                    'tone' => 'blue',
                    'icon' => 'fa-truck',
                    'url' => route('admin.orders.index', ['attention' => 'needs_shipping']),
                ],
                [
                    'label' => __('Cancellation requests'),
                    'count' => $cancellationRequestOrders,
                    'description' => __('Customer cancellation requests that need review.'),
                    'tone' => 'rose',
                    'icon' => 'fa-ban',
                    'url' => route('admin.orders.index', ['attention' => 'cancellation_requests']),
                ],
                [
                    'label' => __('Return requests'),
                    'count' => $openReturnRequests,
                    'description' => __('Open return, exchange, or refund cases.'),
                    'tone' => 'indigo',
                    'icon' => 'fa-rotate-left',
                    'url' => route('admin.returns.index'),
                ],
            ];
            $operationOrders = Order::query()
                ->select([
                    'id',
                    'user_id',
                    'order_number',
                    'status',
                    'total_amount',
                    'cancellation_requested_at',
                    'created_at',
                ])
                ->with(['user:id,name'])
                ->whereNull('archived_at')
                ->where(function ($query) use ($now): void {
                    $query
                        ->where(function ($todayPending) use ($now): void {
                            $todayPending
                                ->where('status', Order::STATUS_PENDING)
                                ->whereDate('created_at', $now->toDateString());
                        })
                        ->orWhere('status', Order::STATUS_PROCESSING)
                        ->orWhere(function ($cancellation): void {
                            $cancellation
                                ->whereNotNull('cancellation_requested_at')
                                ->where('status', '!=', Order::STATUS_CANCELLED);
                        });
                })
                ->orderByRaw("CASE WHEN cancellation_requested_at IS NOT NULL THEN 0 WHEN status = 'processing' THEN 1 WHEN status = 'pending' THEN 2 ELSE 3 END")
                ->latest('id')
                ->limit(6)
                ->get();

            $categoryData = Category::query()
                ->select(['id', 'name_en', 'name_ar', 'name_ku'])
                ->withCount('products')
                ->get();
            $categoryNames = $categoryData->map(fn (Category $category) => $category->name)->toArray();
            $categoryCounts = $categoryData->pluck('products_count')->toArray();

            $monthlyOrders = Order::select(
                DB::raw($this->monthExpression('created_at') . ' as month'),
                DB::raw('COUNT(*) as total')
            )
                ->whereYear('created_at', $now->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $monthLabels = [];
            $monthCounts = [];
            foreach ($monthlyOrders as $m) {
                $monthLabels[] = Carbon::create()->month((int) $m->month)->format('M');
                $monthCounts[] = $m->total;
            }

            $stockTrendLabels = [];
            $stockTrendValues = [];
            $movementLabels = [];
            $movementInValues = [];
            $movementOutValues = [];

            if (Schema::hasTable('inventory_movements')) {
                $stockTrendStart = $now->copy()->subDays($analyticsDays - 1)->startOfDay();
                $stockMovements = InventoryMovement::query()
                    ->select(
                        DB::raw('DATE(created_at) as movement_date'),
                        DB::raw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) as net_quantity")
                    )
                    ->whereDate('created_at', '>=', $stockTrendStart->toDateString())
                    ->groupBy('movement_date')
                    ->orderBy('movement_date')
                    ->get()
                    ->keyBy('movement_date');

                for ($i = $analyticsDays - 1; $i >= 0; $i--) {
                    $day = $now->copy()->subDays($i);
                    $dateKey = $day->toDateString();
                    $stockTrendLabels[] = $day->format('M d');
                    $stockTrendValues[] = (int) ($stockMovements[$dateKey]->net_quantity ?? 0);
                }

                $movementStart = $now->copy()->subDays($analyticsDays - 1)->startOfDay();
                $movementRows = InventoryMovement::query()
                    ->select(
                        DB::raw('DATE(created_at) as movement_date'),
                        DB::raw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END), 0) as stock_in"),
                        DB::raw("COALESCE(SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END), 0) as stock_out")
                    )
                    ->whereDate('created_at', '>=', $movementStart->toDateString())
                    ->groupBy('movement_date')
                    ->orderBy('movement_date')
                    ->get()
                    ->keyBy('movement_date');

                for ($i = $analyticsDays - 1; $i >= 0; $i--) {
                    $day = $now->copy()->subDays($i);
                    $dateKey = $day->toDateString();
                    $movementLabels[] = $day->format('D');
                    $movementInValues[] = (int) ($movementRows[$dateKey]->stock_in ?? 0);
                    $movementOutValues[] = (int) ($movementRows[$dateKey]->stock_out ?? 0);
                }
            }

            $recentOrders = Order::query()
                ->select(['id', 'user_id', 'status', 'total_amount', 'created_at'])
                ->with(['user:id,name'])
                ->latest()
                ->limit(5)
                ->get();

            $topProducts = Product::query()
                ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
                ->select(
                    'products.id',
                    'products.name_en',
                    'products.name_ar',
                    'products.name_ku',
                    'products.image',
                    DB::raw("COALESCE(SUM(CASE WHEN orders.status <> 'cancelled' THEN order_items.quantity ELSE 0 END), 0) as total_sold"),
                    DB::raw("COALESCE(SUM(CASE WHEN orders.status <> 'cancelled' THEN order_items.subtotal ELSE 0 END), 0) as total_revenue")
                )
                ->groupBy('products.id', 'products.name_en', 'products.name_ar', 'products.name_ku', 'products.image')
                ->orderByDesc('total_sold')
                ->limit(5)
                ->get();

            return [
                'totalProducts' => $totalProducts,
                'totalOrders' => $totalOrders,
                'totalUsers' => $totalUsers,
                'totalRevenue' => $totalRevenue,
                'ordersGrowth' => $ordersGrowth,
                'usersGrowth' => $usersGrowth,
                'revenueGrowth' => $revenueGrowth,
                'todaySales' => $todaySales,
                'salesChangePercent' => $salesChangePercent,
                'pendingOrders' => $pendingOrders,
                'unpaidOrders' => $unpaidOrders,
                'newCustomers' => $newCustomers,
                'todayPendingOrders' => $todayPendingOrders,
                'needsShippingOrders' => $needsShippingOrders,
                'cancellationRequestOrders' => $cancellationRequestOrders,
                'openReturnRequests' => $openReturnRequests,
                'operationsQueue' => $operationsQueue,
                'operationOrders' => $operationOrders,
                'lowStockCount' => $lowStockCount,
                'outOfStockCount' => $outOfStockCount,
                'recentProductsCount' => $recentProductsCount,
                'productsTrendPercent' => $productsTrendPercent,
                'usersTrendPercent' => $usersTrendPercent,
                'lowStockTrendPercent' => $lowStockTrendPercent,
                'outOfStockTrendPercent' => $outOfStockTrendPercent,
                'recentProductsTrendPercent' => $recentProductsTrendPercent,
                'lowStockProducts' => $lowStockProducts,
                'recentProducts' => $recentProducts,
                'categoryNames' => $categoryNames,
                'categoryCounts' => $categoryCounts,
                'monthLabels' => $monthLabels,
                'monthCounts' => $monthCounts,
                'recentOrders' => $recentOrders,
                'topProducts' => $topProducts,
                'stockTrendLabels' => $stockTrendLabels,
                'stockTrendValues' => $stockTrendValues,
                'movementLabels' => $movementLabels,
                'movementInValues' => $movementInValues,
                'movementOutValues' => $movementOutValues,
            ];
        });

        $defaults = [
            'totalProducts' => 0,
            'totalOrders' => 0,
            'totalUsers' => 0,
            'totalRevenue' => 0.0,
            'ordersGrowth' => 0.0,
            'usersGrowth' => 0.0,
            'revenueGrowth' => 0.0,
            'todaySales' => 0.0,
            'salesChangePercent' => 0.0,
            'pendingOrders' => 0,
            'unpaidOrders' => 0,
            'newCustomers' => 0,
            'todayPendingOrders' => 0,
            'needsShippingOrders' => 0,
            'cancellationRequestOrders' => 0,
            'openReturnRequests' => 0,
            'operationsQueue' => [],
            'operationOrders' => collect(),
            'lowStockCount' => 0,
            'outOfStockCount' => 0,
            'recentProductsCount' => 0,
            'productsTrendPercent' => 0.0,
            'usersTrendPercent' => 0.0,
            'lowStockTrendPercent' => 0.0,
            'outOfStockTrendPercent' => 0.0,
            'recentProductsTrendPercent' => 0.0,
            'lowStockProducts' => collect(),
            'recentProducts' => collect(),
            'categoryNames' => [],
            'categoryCounts' => [],
            'monthLabels' => [],
            'monthCounts' => [],
            'recentOrders' => collect(),
            'topProducts' => collect(),
            'stockTrendLabels' => [],
            'stockTrendValues' => [],
            'movementLabels' => [],
            'movementInValues' => [],
            'movementOutValues' => [],
        ];

        $data = array_merge($defaults, $dashboardData);
        extract($data);

        return view('admin.dashboard', compact(
            'totalProducts',
            'totalOrders',
            'totalUsers',
            'totalRevenue',
            'ordersGrowth',
            'usersGrowth',
            'revenueGrowth',
            'todaySales',
            'salesChangePercent',
            'pendingOrders',
            'unpaidOrders',
            'newCustomers',
            'todayPendingOrders',
            'needsShippingOrders',
            'cancellationRequestOrders',
            'openReturnRequests',
            'operationsQueue',
            'operationOrders',
            'lowStockThreshold',
            'lowStockCount',
            'outOfStockCount',
            'recentProductsCount',
            'productsTrendPercent',
            'usersTrendPercent',
            'lowStockTrendPercent',
            'outOfStockTrendPercent',
            'recentProductsTrendPercent',
            'lowStockProducts',
            'recentProducts',
            'categoryNames',
            'categoryCounts',
            'monthLabels',
            'monthCounts',
            'stockTrendLabels',
            'stockTrendValues',
            'movementLabels',
            'movementInValues',
            'movementOutValues',
            'analyticsDays',
            'allowedAnalyticsDays',
            'recentOrders',
            'topProducts',
            'currencySymbol',
            'currencyLabel',
            'currencyDecimals'
        ));
    }

    private function percentageChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function monthExpression(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(strftime('%m', {$column}) AS INTEGER)";
        }

        return "MONTH({$column})";
    }
}
