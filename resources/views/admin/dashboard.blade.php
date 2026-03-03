<x-app-layout>
<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-2xl text-gray-800">
            <i class="fas fa-chart-line mr-2"></i> Admin Dashboard
        </h2>
        <div class="text-sm text-gray-600">
            <i class="far fa-calendar-alt mr-2"></i>
            {{ now()->format('l, F d, Y') }}
        </div>
    </div>
</x-slot>

<div class="py-8">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ================= INVENTORY OVERVIEW ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-8">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Products</p>
            <div class="mt-2 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ number_format($totalProducts) }}</h3>
                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">Catalog</span>
            </div>
            <p class="mt-2 text-xs {{ $productsTrendPercent > 0 ? 'text-emerald-600' : ($productsTrendPercent < 0 ? 'text-red-600' : 'text-gray-500') }}">
                <i class="fas fa-arrow-{{ $productsTrendPercent > 0 ? 'up' : ($productsTrendPercent < 0 ? 'down' : 'right') }} mr-1"></i>
                {{ number_format(abs($productsTrendPercent), 1) }}% vs previous month
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Users</p>
            <div class="mt-2 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-slate-900">{{ number_format($totalUsers) }}</h3>
                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-violet-100 text-violet-700">Accounts</span>
            </div>
            <p class="mt-2 text-xs {{ $usersTrendPercent > 0 ? 'text-emerald-600' : ($usersTrendPercent < 0 ? 'text-red-600' : 'text-gray-500') }}">
                <i class="fas fa-arrow-{{ $usersTrendPercent > 0 ? 'up' : ($usersTrendPercent < 0 ? 'down' : 'right') }} mr-1"></i>
                {{ number_format(abs($usersTrendPercent), 1) }}% vs previous month
            </p>
        </div>

        <div class="bg-white border border-amber-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs uppercase tracking-wide text-amber-700">Low Stock</p>
            <div class="mt-2 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-amber-800">{{ number_format($lowStockCount) }}</h3>
                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-700">≤ {{ $lowStockThreshold }}</span>
            </div>
            <p class="mt-2 text-xs {{ $lowStockTrendPercent > 0 ? 'text-rose-600' : ($lowStockTrendPercent < 0 ? 'text-emerald-600' : 'text-gray-500') }}">
                <i class="fas fa-arrow-{{ $lowStockTrendPercent > 0 ? 'up' : ($lowStockTrendPercent < 0 ? 'down' : 'right') }} mr-1"></i>
                {{ number_format(abs($lowStockTrendPercent), 1) }}% vs previous month
            </p>
        </div>

        <div class="bg-white border border-rose-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs uppercase tracking-wide text-rose-700">Out Of Stock</p>
            <div class="mt-2 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-rose-800">{{ number_format($outOfStockCount) }}</h3>
                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-rose-100 text-rose-700">Critical</span>
            </div>
            <p class="mt-2 text-xs {{ $outOfStockTrendPercent > 0 ? 'text-rose-600' : ($outOfStockTrendPercent < 0 ? 'text-emerald-600' : 'text-gray-500') }}">
                <i class="fas fa-arrow-{{ $outOfStockTrendPercent > 0 ? 'up' : ($outOfStockTrendPercent < 0 ? 'down' : 'right') }} mr-1"></i>
                {{ number_format(abs($outOfStockTrendPercent), 1) }}% vs previous month
            </p>
        </div>

        <div class="bg-white border border-emerald-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Recent Products</p>
            <div class="mt-2 flex items-end justify-between">
                <h3 class="text-3xl font-bold text-emerald-800">{{ number_format($recentProductsCount) }}</h3>
                <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-700">This month</span>
            </div>
            <p class="mt-2 text-xs {{ $recentProductsTrendPercent > 0 ? 'text-emerald-600' : ($recentProductsTrendPercent < 0 ? 'text-red-600' : 'text-gray-500') }}">
                <i class="fas fa-arrow-{{ $recentProductsTrendPercent > 0 ? 'up' : ($recentProductsTrendPercent < 0 ? 'down' : 'right') }} mr-1"></i>
                {{ number_format(abs($recentProductsTrendPercent), 1) }}% vs previous month
            </p>
        </div>
    </div>

    {{-- ================= STATS CARDS ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">

        {{-- Products Card --}}
        <div class="group relative bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white opacity-10 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-box text-2xl"></i>
                    </div>
                    <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full backdrop-blur-sm">
                        Active
                    </span>
                </div>
                <p class="text-sm opacity-90 uppercase tracking-wide font-medium">Total Products</p>
                <h3 class="text-4xl font-bold mt-2 mb-3">{{ number_format($totalProducts) }}</h3>
                @if(Route::has('admin.products.index'))
                    <a href="{{ route('admin.products.index') }}" class="text-xs opacity-75 hover:opacity-100 transition-opacity inline-flex items-center group-hover:gap-2 gap-1">
                        View Details 
                        <i class="fas fa-arrow-right text-xs transition-all"></i>
                    </a>
                @endif
            </div>
        </div>

        {{-- Orders Card --}}
        <div class="group relative bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-6 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white opacity-10 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-shopping-cart text-2xl"></i>
                    </div>
                    @if($ordersGrowth > 0)
                        <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full backdrop-blur-sm">
                            <i class="fas fa-arrow-up text-xs mr-1"></i> {{ number_format(abs($ordersGrowth), 1) }}%
                        </span>
                    @elseif($ordersGrowth < 0)
                        <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full backdrop-blur-sm">
                            <i class="fas fa-arrow-down text-xs mr-1"></i> {{ number_format(abs($ordersGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-sm opacity-90 uppercase tracking-wide font-medium">Total Orders</p>
                <h3 class="text-4xl font-bold mt-2 mb-3">{{ number_format($totalOrders) }}</h3>
                @if(Route::has('admin.orders.index'))
                    <a href="{{ route('admin.orders.index') }}" class="text-xs opacity-75 hover:opacity-100 transition-opacity inline-flex items-center group-hover:gap-2 gap-1">
                        View Details 
                        <i class="fas fa-arrow-right text-xs transition-all"></i>
                    </a>
                @endif
            </div>
        </div>

        {{-- Users Card --}}
        <div class="group relative bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white opacity-10 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    @if($usersGrowth > 0)
                        <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full backdrop-blur-sm">
                            <i class="fas fa-arrow-up text-xs mr-1"></i> {{ number_format(abs($usersGrowth), 1) }}%
                        </span>
                    @elseif($usersGrowth < 0)
                        <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full backdrop-blur-sm">
                            <i class="fas fa-arrow-down text-xs mr-1"></i> {{ number_format(abs($usersGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-sm opacity-90 uppercase tracking-wide font-medium">Total Users</p>
                <h3 class="text-4xl font-bold mt-2 mb-3">{{ number_format($totalUsers) }}</h3>
                @if(Route::has('admin.users.index'))
                    <a href="{{ route('admin.users.index') }}" class="text-xs opacity-75 hover:opacity-100 transition-opacity inline-flex items-center group-hover:gap-2 gap-1">
                        View Details 
                        <i class="fas fa-arrow-right text-xs transition-all"></i>
                    </a>
                @endif
            </div>
        </div>

        {{-- Revenue Card --}}
        <div class="group relative bg-gradient-to-br from-amber-500 to-orange-500 text-white p-6 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white opacity-10 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white bg-opacity-20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-dollar-sign text-2xl"></i>
                    </div>
                    @if($revenueGrowth > 0)
                        <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full backdrop-blur-sm">
                            <i class="fas fa-arrow-up text-xs mr-1"></i> {{ number_format(abs($revenueGrowth), 1) }}%
                        </span>
                    @elseif($revenueGrowth < 0)
                        <span class="text-xs bg-white bg-opacity-20 px-3 py-1 rounded-full backdrop-blur-sm">
                            <i class="fas fa-arrow-down text-xs mr-1"></i> {{ number_format(abs($revenueGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-sm opacity-90 uppercase tracking-wide font-medium">Total Revenue</p>
                <h3 class="text-4xl font-bold mt-2 mb-3">{{ $currencyLabel }} {{ number_format($totalRevenue, $currencyDecimals) }}</h3>
                <p class="text-xs opacity-75">Completed orders only</p>
            </div>
        </div>

    </div>

    {{-- ================= SECONDARY STATS ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        
        {{-- Today's Sales --}}
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Today's Sales</p>
                    <h4 class="text-2xl font-bold text-gray-800">{{ $currencyLabel }} {{ number_format($todaySales, $currencyDecimals) }}</h4>
                    @if($salesChangePercent != 0)
                        <p class="text-xs mt-2 {{ $salesChangePercent > 0 ? 'text-emerald-500' : 'text-red-500' }}">
                            <i class="fas fa-arrow-{{ $salesChangePercent > 0 ? 'up' : 'down' }} mr-1"></i> 
                            {{ number_format(abs($salesChangePercent), 1) }}% from yesterday
                        </p>
                    @else
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-minus mr-1"></i> No change
                        </p>
                    @endif
                </div>
                <div class="p-4 bg-emerald-100 rounded-full">
                    <i class="fas fa-chart-line text-2xl text-emerald-600"></i>
                </div>
            </div>
        </div>

        {{-- Pending Orders --}}
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Pending Orders</p>
                    <h4 class="text-2xl font-bold text-gray-800">{{ number_format($pendingOrders) }}</h4>
                    <p class="text-xs text-amber-500 mt-2">
                        <i class="fas fa-clock mr-1"></i> Needs attention
                    </p>
                </div>
                <div class="p-4 bg-amber-100 rounded-full">
                    <i class="fas fa-hourglass-half text-2xl text-amber-600"></i>
                </div>
            </div>
        </div>

        {{-- New Customers --}}
        <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">New Customers</p>
                    <h4 class="text-2xl font-bold text-gray-800">{{ number_format($newCustomers) }}</h4>
                    <p class="text-xs text-blue-500 mt-2">
                        <i class="fas fa-user-plus mr-1"></i> This month
                    </p>
                </div>
                <div class="p-4 bg-blue-100 rounded-full">
                    <i class="fas fa-user-friends text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="bg-white p-6 rounded-xl shadow-md border border-red-200 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Low Stock</p>
                    <h4 class="text-2xl font-bold text-gray-800">{{ number_format($lowStockCount) }}</h4>
                    <p class="text-xs text-red-500 mt-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i> ≤ {{ $lowStockThreshold }} units
                    </p>
                    @if(Route::has('admin.products.index'))
                        <a href="{{ route('admin.products.index', ['low_stock' => 1]) }}" class="text-xs text-red-600 hover:text-red-700 inline-flex items-center mt-2">
                            View low stock <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    @endif
                </div>
                <div class="p-4 bg-red-100 rounded-full">
                    <i class="fas fa-boxes-stacked text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

    </div>

    {{-- ================= CHARTS ROW ================= --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">

        {{-- Monthly Orders Chart (2/3 width) --}}
        <div class="xl:col-span-2 bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-semibold text-lg flex items-center">
                        <i class="fas fa-chart-area mr-2 text-indigo-500"></i>
                        Monthly Orders Trend
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Sales performance overview for {{ date('Y') }}</p>
                </div>
            </div>
            
            @if(count($monthLabels) > 0 && array_sum($monthCounts) > 0)
                <div style="height:350px">
                    <canvas id="ordersChart"></canvas>
                </div>
            @else
                <div class="h-80 flex flex-col items-center justify-center text-gray-400">
                    <i class="fas fa-chart-line text-6xl mb-4 opacity-20"></i>
                    <p class="text-lg font-medium">No orders data available</p>
                    <p class="text-sm mt-1">Start receiving orders to see the chart</p>
                </div>
            @endif
        </div>

        {{-- Products by Category Chart (1/3 width) --}}
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
            <div class="mb-6">
                <h3 class="font-semibold text-lg flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-blue-500"></i>
                    Products Distribution
                </h3>
                <p class="text-sm text-gray-500 mt-1">By category</p>
            </div>
            
            @if(count($categoryNames) > 0)
                <div style="height:300px" class="mb-4">
                    <canvas id="categoryChart"></canvas>
                </div>
                {{-- Category List --}}
                <div class="space-y-3 mt-6">
                    @foreach($categoryNames as $index => $category)
                        @php
                            $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
                            $color = $colors[$index % count($colors)];
                        @endphp
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $color }}"></div>
                                <span class="text-gray-700">{{ $category }}</span>
                            </div>
                            <span class="font-semibold text-gray-800">{{ $categoryCounts[$index] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-80 flex flex-col items-center justify-center text-gray-400">
                    <i class="fas fa-chart-pie text-6xl mb-4 opacity-20"></i>
                    <p class="text-lg font-medium">No categories</p>
                    <p class="text-sm mt-1">Add products to see distribution</p>
                </div>
            @endif
        </div>

    </div>

    {{-- ================= ADVANCED ANALYTICS ================= --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-4">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-3">
            <label for="analytics_days" class="text-sm font-medium text-gray-700">Analytics Range</label>
            <select id="analytics_days" name="analytics_days" class="rounded-lg border-gray-300 text-sm">
                @foreach($allowedAnalyticsDays as $dayOption)
                    <option value="{{ $dayOption }}" @selected((int) $analyticsDays === (int) $dayOption)>
                        Last {{ $dayOption }} days
                    </option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">
                Apply
            </button>
            @if(request()->has('analytics_days'))
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
            <div class="mb-6">
                <h3 class="font-semibold text-lg flex items-center">
                    <i class="fas fa-wave-square mr-2 text-emerald-500"></i>
                    Stock Trend (Net Movement)
                </h3>
                <p class="text-sm text-gray-500 mt-1">Last {{ $analyticsDays }} days (in minus out)</p>
            </div>
            @if(count($stockTrendLabels) > 0 && array_sum(array_map('abs', $stockTrendValues)) > 0)
                <div style="height:300px">
                    <canvas id="stockTrendChart"></canvas>
                </div>
            @else
                <div class="h-64 flex flex-col items-center justify-center text-gray-400">
                    <i class="fas fa-chart-line text-5xl mb-3 opacity-20"></i>
                    <p class="text-sm">No stock movement trend data yet</p>
                </div>
            @endif
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
            <div class="mb-6">
                <h3 class="font-semibold text-lg flex items-center">
                    <i class="fas fa-arrows-up-down mr-2 text-cyan-500"></i>
                    Recent Inventory Movements
                </h3>
                <p class="text-sm text-gray-500 mt-1">Stock in vs stock out (last {{ $analyticsDays }} days)</p>
            </div>
            @if(count($movementLabels) > 0 && (array_sum($movementInValues) > 0 || array_sum($movementOutValues) > 0))
                <div style="height:300px">
                    <canvas id="movementChart"></canvas>
                </div>
            @else
                <div class="h-64 flex flex-col items-center justify-center text-gray-400">
                    <i class="fas fa-warehouse text-5xl mb-3 opacity-20"></i>
                    <p class="text-sm">No recent inventory movement data</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ================= LOW STOCK ALERTS ================= --}}
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-red-200 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-semibold text-lg flex items-center">
                <i class="fas fa-triangle-exclamation mr-2 text-red-500"></i>
                Low Stock Alerts
            </h3>
            @if(Route::has('admin.products.index'))
                <a href="{{ route('admin.products.index', ['low_stock' => 1]) }}" class="text-sm text-red-600 hover:text-red-700">
                    View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            @endif
        </div>

        @if($lowStockProducts->count() > 0)
            <div class="space-y-3">
                @foreach($lowStockProducts as $product)
                    <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-box text-red-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ $product->name_en }}</p>
                                <p class="text-xs text-gray-500">SKU: {{ $product->sku }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-2 py-1 text-xs rounded bg-red-100 text-red-700">
                                {{ $product->stock_quantity }} left
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="h-24 flex flex-col items-center justify-center text-gray-400">
                <i class="fas fa-check-circle text-3xl mb-2 opacity-40"></i>
                <p class="text-sm">All products have healthy stock</p>
            </div>
        @endif
    </div>

    {{-- ================= RECENT PRODUCTS ================= --}}
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-semibold text-lg flex items-center">
                <i class="fas fa-box-open mr-2 text-emerald-500"></i>
                Recent Products
            </h3>
            @if(Route::has('admin.products.index'))
                <a href="{{ route('admin.products.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                    View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            @endif
        </div>

        @if($recentProducts->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
                @foreach($recentProducts as $product)
                    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50">
                        <p class="font-medium text-gray-800 truncate">{{ $product->name_en }}</p>
                        <p class="text-xs text-gray-500 mt-1">SKU: {{ $product->sku ?? 'N/A' }}</p>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span class="{{ $product->stock_quantity <= $lowStockThreshold ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                Stock: {{ $product->stock_quantity }}
                            </span>
                            <span class="text-gray-500">{{ optional($product->created_at)->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="h-24 flex flex-col items-center justify-center text-gray-400">
                <i class="fas fa-box text-3xl mb-2 opacity-40"></i>
                <p class="text-sm">No recent products added yet</p>
            </div>
        @endif
    </div>

    {{-- ================= RECENT ACTIVITY & TOP PRODUCTS ================= --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
        {{-- Recent Orders --}}
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-lg flex items-center">
                    <i class="fas fa-clock mr-2 text-purple-500"></i>
                    Recent Orders
                </h3>
                @if(Route::has('admin.orders.index'))
                    <a href="{{ route('admin.orders.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                        View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                @endif
            </div>
            
            @if($recentOrders->count() > 0)
                <div class="space-y-4">
                    @foreach($recentOrders as $order)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-xs">
                                    #{{ $order->id }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">
                                        {{ $order->user->name ?? 'Guest' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $order->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-800">
                                    {{ $currencyLabel }} {{ number_format($order->total_amount, $currencyDecimals) }}
                                </p>
                                <span class="inline-block px-2 py-1 text-xs rounded-full 
    {{ $order->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : '' }}
    {{ $order->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
    {{ $order->status === 'processing' ? 'bg-blue-100 text-blue-700' : '' }}
    {{ !in_array($order->status, ['completed', 'pending', 'processing']) ? 'bg-red-100 text-red-700' : '' }}">
    {{ ucfirst($order->status) }}
</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-64 flex flex-col items-center justify-center text-gray-400">
                    <i class="fas fa-shopping-cart text-5xl mb-3 opacity-20"></i>
                    <p class="text-sm">No recent orders</p>
                </div>
            @endif
        </div>

        {{-- Top Products --}}
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-semibold text-lg flex items-center">
                    <i class="fas fa-fire mr-2 text-orange-500"></i>
                    Top Selling Products
                </h3>
                @if(Route::has('admin.products.index'))
                    <a href="{{ route('admin.products.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                        View All <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                @endif
            </div>
            
            @if($topProducts->count() > 0)
                <div class="space-y-4">
                    @foreach($topProducts as $product)
                        @php
                            $maxSold = $topProducts->max('total_sold') ?: 1;
                            $percentage = $maxSold > 0 ? ($product->total_sold / $maxSold) * 100 : 0;
                        @endphp
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-pink-500 rounded-lg flex items-center justify-center overflow-hidden">
                                    @if(isset($product->image) && $product->image && file_exists(public_path('storage/' . $product->image)))
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name_en ?? 'Product' }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="fas fa-box text-white"></i>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ Str::limit($product->name_en ?? 'Product', 20) }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->total_sold ?? 0 }} sales</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-800">
                                    {{ $currencyLabel }} {{ number_format($product->total_revenue ?? 0, $currencyDecimals) }}
                                </p>
                                <div class="flex items-center justify-end mt-1">
                                    <div class="w-16 h-1 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-orange-400 to-pink-500" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-64 flex flex-col items-center justify-center text-gray-400">
                    <i class="fas fa-box-open text-5xl mb-3 opacity-20"></i>
                    <p class="text-sm">No product sales yet</p>
                </div>
            @endif
        </div>

    </div>

</div>
</div>

{{-- ================= FONT AWESOME ================= --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

{{-- ================= CHART JS ================= --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Dark mode detection
const isDark = document.documentElement.classList.contains('dark');
const gridColor = isDark ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)';
const textColor = isDark ? '#9CA3AF' : '#4B5563';
const tooltipBg = isDark ? '#1F2937' : '#ffffff';
const tooltipBorder = isDark ? '#374151' : '#E5E7EB';

@if(count($categoryNames) > 0)
// ============= CATEGORY DOUGHNUT CHART =============
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: @json($categoryNames),
        datasets: [{
            data: @json($categoryCounts),
            backgroundColor: [
                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
                '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'
            ],
            borderWidth: 3,
            borderColor: isDark ? '#1F2937' : '#ffffff',
            hoverOffset: 8,
            hoverBorderWidth: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: tooltipBg,
                titleColor: isDark ? '#F9FAFB' : '#111827',
                bodyColor: isDark ? '#F9FAFB' : '#111827',
                borderColor: tooltipBorder,
                borderWidth: 1,
                padding: 12,
                boxPadding: 6,
                usePointStyle: true,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${value} products (${percentage}%)`;
                    }
                }
            }
        },
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1200,
            easing: 'easeInOutQuart'
        },
        cutout: '65%'
    }
});
@endif

@if(count($monthLabels) > 0 && array_sum($monthCounts) > 0)
// ============= MONTHLY ORDERS LINE CHART =============
const ordersCtx = document.getElementById('ordersChart').getContext('2d');
const gradient = ordersCtx.createLinearGradient(0, 0, 0, 350);
gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');

new Chart(ordersCtx, {
    type: 'line',
    data: {
        labels: @json($monthLabels),
        datasets: [{
            label: 'Orders',
            data: @json($monthCounts),
            borderColor: '#6366f1',
            backgroundColor: gradient,
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 3,
            pointHoverBackgroundColor: '#6366f1',
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 4,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: tooltipBg,
                titleColor: isDark ? '#F9FAFB' : '#111827',
                bodyColor: isDark ? '#F9FAFB' : '#111827',
                borderColor: tooltipBorder,
                borderWidth: 1,
                padding: 16,
                displayColors: true,
                boxPadding: 8,
                usePointStyle: true,
                callbacks: {
                    title: function(context) {
                        return `${context[0].label} {{ date('Y') }}`;
                    },
                    label: function(context) {
                        return `Orders: ${context.parsed.y}`;
                    },
                    afterLabel: function(context) {
                        const currentValue = context.parsed.y;
                        const previousValue = context.dataset.data[context.dataIndex - 1] || currentValue;
                        const change = currentValue - previousValue;
                        const percentage = previousValue !== 0 ? ((change / previousValue) * 100).toFixed(1) : 0;
                        
                        if (change > 0) return `↑ +${percentage}% from last month`;
                        else if (change < 0) return `↓ ${percentage}% from last month`;
                        return '→ No change';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: textColor, font: { size: 12, weight: '500' }, padding: 10 }
            },
            y: {
                beginAtZero: true,
                grid: { color: gridColor, drawBorder: false, lineWidth: 1 },
                ticks: { color: textColor, font: { size: 12, weight: '500' }, padding: 10, precision: 0 },
                border: { display: false }
            }
        },
        animation: { duration: 1500, easing: 'easeInOutQuart' }
    }
});
@endif

@if(count($stockTrendLabels) > 0 && array_sum(array_map('abs', $stockTrendValues)) > 0)
// ============= STOCK TREND CHART =============
const stockTrendCtx = document.getElementById('stockTrendChart').getContext('2d');
new Chart(stockTrendCtx, {
    type: 'line',
    data: {
        labels: @json($stockTrendLabels),
        datasets: [{
            label: 'Net Stock Movement',
            data: @json($stockTrendValues),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.12)',
            fill: true,
            tension: 0.35,
            borderWidth: 3,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: tooltipBg,
                titleColor: isDark ? '#F9FAFB' : '#111827',
                bodyColor: isDark ? '#F9FAFB' : '#111827',
                borderColor: tooltipBorder,
                borderWidth: 1
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: textColor } },
            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }
        }
    }
});
@endif

@if(count($movementLabels) > 0 && (array_sum($movementInValues) > 0 || array_sum($movementOutValues) > 0))
// ============= INVENTORY MOVEMENT CHART =============
const movementCtx = document.getElementById('movementChart').getContext('2d');
new Chart(movementCtx, {
    type: 'bar',
    data: {
        labels: @json($movementLabels),
        datasets: [
            {
                label: 'Stock In',
                data: @json($movementInValues),
                backgroundColor: 'rgba(16, 185, 129, 0.75)',
                borderColor: '#10b981',
                borderWidth: 1,
                borderRadius: 6
            },
            {
                label: 'Stock Out',
                data: @json($movementOutValues),
                backgroundColor: 'rgba(239, 68, 68, 0.75)',
                borderColor: '#ef4444',
                borderWidth: 1,
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { color: textColor } },
            tooltip: {
                backgroundColor: tooltipBg,
                titleColor: isDark ? '#F9FAFB' : '#111827',
                bodyColor: isDark ? '#F9FAFB' : '#111827',
                borderColor: tooltipBorder,
                borderWidth: 1
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: textColor } },
            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }
        }
    }
});
@endif

// ============= ANIMATIONS ON SCROLL =============
const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -100px 0px' };
const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.grid > div').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.6s ease-out';
    observer.observe(el);
});
</script>

</x-app-layout>
