<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function index(Request $request): View
    {
        $allowedDays = [7, 30, 90, 365];
        $days = (int) $request->query('days', 30);
        if (!in_array($days, $allowedDays, true)) {
            $days = 30;
        }

        $paidStatuses = ['delivered', 'completed'];
        $pendingStatuses = ['pending', 'processing'];
        $cancelledStatuses = ['cancelled', 'canceled'];
        $refundedStatuses = ['refunded'];
        $now = Carbon::now();
        $end = $now->copy()->endOfDay();
        $start = $now->copy()->subDays($days - 1)->startOfDay();
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $customRange = false;

        try {
            if ($from !== '' && $to !== '') {
                $customStart = Carbon::parse($from)->startOfDay();
                $customEnd = Carbon::parse($to)->endOfDay();
                if ($customStart->lessThanOrEqualTo($customEnd)) {
                    $start = $customStart;
                    $end = $customEnd;
                    $customRange = true;
                }
            }
        } catch (\Throwable $e) {
            // Keep default days filter if date parsing fails.
        }

        $rangeDays = max(1, $start->diffInDays($end) + 1);
        $previousStart = $start->copy()->subDays($rangeDays);
        $previousEnd = $start->copy()->subSecond();

        $currencySymbol = (string) Setting::getValue('currency_symbol', 'IQD');
        $currencyCode = (string) Setting::getValue('currency_code', 'IQD');
        $currencyLabel = $currencyCode !== '' ? $currencyCode : $currencySymbol;
        $currencyDecimals = strtoupper($currencyCode) === 'IQD' ? 0 : 2;
        $localizedProductColumn = match (true) {
            str_starts_with(app()->getLocale(), 'ar') => 'products.name_ar',
            str_starts_with(app()->getLocale(), 'ku') => 'products.name_ku',
            default => 'products.name_en',
        };
        $localizedCategoryColumn = match (true) {
            str_starts_with(app()->getLocale(), 'ar') => 'categories.name_ar',
            str_starts_with(app()->getLocale(), 'ku') => 'categories.name_ku',
            default => 'categories.name_en',
        };

        $totalRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->sum('total_amount');

        $periodRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $previousRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total_amount');

        $growthPercent = $this->percentageChange($periodRevenue, $previousRevenue);

        $periodPaidOrders = (int) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $averageOrderValue = $periodPaidOrders > 0
            ? ($periodRevenue / $periodPaidOrders)
            : 0.0;

        $todayRevenue = (float) Order::query()
            ->whereIn('status', $paidStatuses)
            ->whereDate('created_at', $now->toDateString())
            ->sum('total_amount');

        $statusCards = [
            'paid' => [
                'label' => __('Paid'),
                'amount' => (float) Order::query()
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total_amount'),
                'orders' => (int) Order::query()
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
            'pending' => [
                'label' => __('Pending'),
                'amount' => (float) Order::query()
                    ->whereIn('status', $pendingStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total_amount'),
                'orders' => (int) Order::query()
                    ->whereIn('status', $pendingStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
            'cancelled' => [
                'label' => __('Cancelled'),
                'amount' => (float) Order::query()
                    ->whereIn('status', $cancelledStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total_amount'),
                'orders' => (int) Order::query()
                    ->whereIn('status', $cancelledStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
            'refunded' => [
                'label' => __('Refunded'),
                'amount' => (float) Order::query()
                    ->whereIn('status', $refundedStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total_amount'),
                'orders' => (int) Order::query()
                    ->whereIn('status', $refundedStatuses)
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
        ];

        $dailyRows = Order::query()
            ->selectRaw('DATE(created_at) as order_day, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->whereIn('status', $paidStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('order_day')
            ->orderBy('order_day')
            ->get()
            ->keyBy('order_day');

        $dailyRevenue = collect();
        for ($i = $rangeDays - 1; $i >= 0; $i--) {
            $day = $end->copy()->subDays($i);
            $key = $day->toDateString();
            $dailyRevenue->push([
                'label' => $day->format('M d'),
                'date' => $key,
                'amount' => (float) ($dailyRows[$key]->total_revenue ?? 0),
            ]);
        }

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'products.id',
                DB::raw("COALESCE(NULLIF({$localizedProductColumn}, ''), products.name_en) as name"),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as units_sold'),
                DB::raw('COALESCE(SUM(order_items.subtotal), 0) as revenue_total')
            )
            ->groupBy('products.id', 'products.name_en', 'products.name_ar', 'products.name_ku')
            ->orderByDesc('revenue_total')
            ->limit(10)
            ->get();

        $topCategories = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                DB::raw("COALESCE(categories.id, 0) as category_id"),
                DB::raw("COALESCE(NULLIF({$localizedCategoryColumn}, ''), categories.name_en, 'Uncategorized') as category_name"),
                DB::raw('COALESCE(SUM(order_items.subtotal), 0) as revenue_total'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as units_sold')
            )
            ->groupBy('categories.id', 'categories.name_en', 'categories.name_ar', 'categories.name_ku')
            ->orderByDesc('revenue_total')
            ->limit(8)
            ->get();

        $topCustomers = Order::query()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'users.id',
                'users.name',
                'users.role',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('COALESCE(SUM(orders.total_amount), 0) as revenue_total')
            )
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('revenue_total')
            ->limit(10)
            ->get();

        $topDealers = Order::query()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->whereIn('orders.status', $paidStatuses)
            ->where('users.role', 'dealer')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('COALESCE(SUM(orders.total_amount), 0) as revenue_total')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue_total')
            ->limit(10)
            ->get();

        $totalOrders = (int) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $cancelledOrders = (int) Order::query()
            ->whereIn('status', $cancelledStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $periodOrderValue = (float) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');
        $conversionSummary = [
            'total_orders' => $totalOrders,
            'paid_orders' => $periodPaidOrders,
            'avg_order_value' => $totalOrders > 0 ? ($periodOrderValue / $totalOrders) : 0.0,
            'cancel_rate' => $totalOrders > 0 ? (($cancelledOrders / $totalOrders) * 100) : 0.0,
            'paid_rate' => $totalOrders > 0 ? (($periodPaidOrders / $totalOrders) * 100) : 0.0,
        ];

        $recentPaidOrders = Order::query()
            ->with('user:id,name')
            ->whereIn('status', $paidStatuses)
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'status', 'total_amount', 'created_at']);

        return view('admin.revenue.index', [
            'days' => $days,
            'allowedDays' => $allowedDays,
            'start' => $start,
            'end' => $end,
            'now' => $now,
            'rangeDays' => $rangeDays,
            'customRange' => $customRange,
            'from' => $customRange ? $start->toDateString() : '',
            'to' => $customRange ? $end->toDateString() : '',
            'totalRevenue' => $totalRevenue,
            'periodRevenue' => $periodRevenue,
            'previousRevenue' => $previousRevenue,
            'growthPercent' => $growthPercent,
            'periodPaidOrders' => $periodPaidOrders,
            'averageOrderValue' => $averageOrderValue,
            'todayRevenue' => $todayRevenue,
            'statusCards' => $statusCards,
            'dailyRevenue' => $dailyRevenue,
            'topProducts' => $topProducts,
            'topCategories' => $topCategories,
            'topCustomers' => $topCustomers,
            'topDealers' => $topDealers,
            'conversionSummary' => $conversionSummary,
            'recentPaidOrders' => $recentPaidOrders,
            'currencyLabel' => $currencyLabel,
            'currencyDecimals' => $currencyDecimals,
        ]);
    }

    private function percentageChange(float|int $current, float|int $previous): float
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }
}
