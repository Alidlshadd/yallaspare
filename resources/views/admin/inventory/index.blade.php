<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-slate-100">{{ __('Inventory Movements') }}</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Track stock adjustments with product, warehouse, user, date, and reference history.') }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                {{ __('Warehouse Dock') }}
            </span>
        </div>
    </x-slot>

    @php
        $productOptions = $products->map(fn ($product) => [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? __('N/A')),
            'part_number' => (string) ($product->part_number ?? ''),
            'oem_number' => (string) ($product->oem_number ?? ''),
            'brand' => (string) ($product->brand ?? ''),
            'stock' => (int) $product->stock_quantity,
        ])->values();
        $oldProductId = (int) old('product_id', 0);
        $selectedProduct = $productOptions->firstWhere('id', $oldProductId);
        $selectedProductLabel = $selectedProduct ? $selectedProduct['name'] . ' (' . $selectedProduct['sku'] . ')' : '';

        // Tailwind must see these class strings in the blade file, so they are
        // defined here and handed to the Alpine component via data-config.
        $formUiClasses = [
            'typeInActive' => 'border-amber-400 bg-amber-50 text-amber-800 shadow-sm dark:border-amber-500/60 dark:bg-amber-950/40 dark:text-amber-300',
            'typeOutActive' => 'border-rose-400 bg-rose-50 text-rose-800 shadow-sm dark:border-rose-500/60 dark:bg-rose-950/40 dark:text-rose-300',
            'typeIdle' => 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400 dark:hover:text-slate-200',
            'projectedOk' => 'font-bold text-slate-900 dark:text-slate-100',
            'projectedNegative' => 'font-bold text-rose-700 dark:text-rose-300',
        ];

        $pageMovements = $movements->getCollection();
        $inboundMovements = $pageMovements->where('type', 'in')->values();
        $outboundMovements = $pageMovements->where('type', 'out')->values();
        $inboundPageQty = (int) $inboundMovements->sum('quantity');
        $outboundPageQty = (int) $outboundMovements->sum('quantity');
        $singleLane = in_array($type, ['in', 'out'], true);

        // Gate cards toggle the type filter while keeping every other filter.
        $gateUrl = fn (string $gateType) => route('admin.inventory.index', array_filter([
            'search' => $search !== '' ? $search : null,
            'type' => $type === $gateType ? null : $gateType,
            'product_id' => $productId > 0 ? $productId : null,
            'warehouse_id' => $warehouseId > 0 ? $warehouseId : null,
            'from' => $from !== '' ? $from : null,
            'to' => $to !== '' ? $to : null,
        ], fn ($param) => $param !== null));
        $csvTemplateHref = 'data:text/csv;charset=utf-8,' . rawurlencode(
            "product_sku,type,quantity,warehouse_code,reference,note,performed_at\n"
            . "BRK-1001,in,10,,PO-1001,,\n"
            . "FLT-2002,out,3,,ORD-5001,damaged unit,\n"
        );
    @endphp

    <style>
        .inv-hero {
            position: relative; overflow: hidden;
            background: linear-gradient(135deg, #04042a, #10104a);
            border-radius: 16px; color: #fff;
        }
        .inv-hero::after {
            content: ""; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
        }
        .inv-hero > * { position: relative; z-index: 1; }
        .inv-mono { font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- ===== Dock hero: IN gate / net / OUT gate ===== --}}
            <section class="inv-hero">
                <div class="grid items-center gap-4 px-5 py-5 sm:px-6 md:grid-cols-[1fr_auto_1fr]">
                    <a href="{{ $gateUrl('in') }}" class="block rounded-xl border-2 px-4 py-3 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 {{ $type === 'in' ? 'border-amber-300 bg-amber-400/15' : 'border-amber-400/60 bg-white/5' }}">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/55">{{ __('Stock In') }} &mdash; {{ __('Gate A') }}</p>
                        <p class="inv-mono mt-1 text-2xl font-black text-amber-400">+{{ number_format($totalStockIn) }} <span class="float-right">&#8594;</span></p>
                        <p class="text-[11px] text-white/60">{{ __('Units added') }} &middot; {{ $type === 'in' ? __('Filter active — click to clear') : __('Click to filter') }}</p>
                    </a>
                    <div class="text-center md:px-6">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/55">{{ __('Net Movement') }}</p>
                        <p class="inv-mono text-3xl font-black {{ $netMovement >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ $netMovement >= 0 ? '+' : '' }}{{ number_format($netMovement) }}</p>
                        <p class="mt-1 text-[11px] text-white/60">{{ number_format($totalMovements) }} {{ __('movements') }} &middot; {{ number_format($todayMovements) }} {{ __('today') }}</p>
                    </div>
                    <a href="{{ $gateUrl('out') }}" class="block rounded-xl border-2 px-4 py-3 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 md:text-right {{ $type === 'out' ? 'border-rose-300 bg-rose-400/15' : 'border-rose-400/60 bg-white/5' }}">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/55">{{ __('Stock Out') }} &mdash; {{ __('Gate B') }}</p>
                        <p class="inv-mono mt-1 text-2xl font-black text-rose-300"><span class="float-left">&#8594;</span> &minus;{{ number_format($totalStockOut) }}</p>
                        <p class="text-[11px] text-white/60">{{ __('Units removed') }} &middot; {{ $type === 'out' ? __('Filter active — click to clear') : __('Click to filter') }}</p>
                    </a>
                </div>
            </section>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                {{-- ===== Left: dock control (adjustment + import) ===== --}}
                <div class="xl:col-span-1 space-y-5">
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                        x-data="inventoryForm"
                        data-config="{{ json_encode([
                            'products' => $productOptions,
                            'labels' => ['na' => __('N/A'), 'part' => __('Part:'), 'oem' => __('OEM:'), 'stock' => __('Stock:')],
                            'ui' => $formUiClasses,
                            'productId' => old('product_id', ''),
                            'productSearch' => $selectedProductLabel,
                            'type' => old('type', 'in'),
                            'quantity' => (int) old('quantity', 1),
                        ], JSON_UNESCAPED_UNICODE) }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('Adjustment') }}</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('Dock Control') }}</h3>
                            </div>
                            <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300">
                                {{ __('Manual') }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('admin.inventory.store') }}" class="mt-5 space-y-4">
                            @csrf
                            <div class="relative" @click.outside="closeList()">
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Product') }}</label>
                                <input type="hidden" name="product_id" x-model="productId">
                                <div class="flex rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-amber-400 dark:border-slate-700 dark:bg-slate-950">
                                    <input
                                        type="text"
                                        x-ref="productSearch"
                                        x-model="productSearch"
                                        @focus="openList()"
                                        @input="onSearchInput()"
                                        @keydown.arrow-down.prevent="arrowDown()"
                                        @keydown.arrow-up.prevent="arrowUp()"
                                        @keydown.enter.prevent="commitHighlightedProduct()"
                                        @keydown.escape="closeList()"
                                        placeholder="{{ __('Search product by name, SKU, part number, OEM, or brand...') }}"
                                        class="min-w-0 flex-1 rounded-l-lg border-0 bg-transparent text-slate-900 focus:ring-0 dark:text-slate-100"
                                        autocomplete="off"
                                        required
                                    >
                                    <button
                                        type="button"
                                        x-show="hasProductSearch"
                                        x-cloak
                                        @click="clearProduct()"
                                        class="border-l border-gray-200 px-3 text-sm font-semibold text-slate-500 transition hover:text-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-slate-100"
                                        aria-label="{{ __('Clear') }}"
                                    >
                                        &times;
                                    </button>
                                </div>
                                <div
                                    x-show="productOpen"
                                    x-cloak
                                    class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-950"
                                >
                                    <template x-for="(product, index) in filteredProducts" :key="product.id">
                                        <button
                                            type="button"
                                            @click="selectProduct(product)"
                                            @mouseenter="setActive(index)"
                                            class="block w-full px-3 py-2 text-left transition hover:bg-amber-50 dark:hover:bg-slate-900"
                                        >
                                            <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="productLabel(product)"></span>
                                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400" x-text="productMeta(product)"></span>
                                        </button>
                                    </template>
                                    <div x-show="filteredProductsEmpty" class="px-3 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                        {{ __('No products found') }}
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('Type a few letters or a part number, then choose the matching product.') }}
                                </p>
                            </div>

                            @if($hasWarehouseSupport)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Warehouse') }}</label>
                                    <select name="warehouse_id" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                        <option value="">{{ __('General stock only') }}</option>
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}" @selected((int) old('warehouse_id') === (int) $warehouse->id)>
                                                {{ $warehouse->name }} ({{ $warehouse->code }}) - {{ $warehouse->city }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $hasWarehouseStockSupport ? __('Selecting a warehouse also updates its warehouse stock balance.') : __('Selecting a warehouse records the warehouse on the movement history.') }}
                                    </p>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Direction') }}</label>
                                <input type="hidden" name="type" :value="type">
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="setTypeIn" :class="typeInClass" class="rounded-xl border-2 px-3 py-2.5 text-sm font-bold transition">
                                        &#8595; {{ __('Stock In') }}
                                    </button>
                                    <button type="button" @click="setTypeOut" :class="typeOutClass" class="rounded-xl border-2 px-3 py-2.5 text-sm font-bold transition">
                                        &#8593; {{ __('Stock Out') }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Quantity') }}</label>
                                <input type="number" name="quantity" min="1" x-model.number="quantity" value="{{ old('quantity', 1) }}" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
                            </div>

                            <div x-show="selectedProduct" x-cloak class="rounded-xl border border-amber-200/70 bg-amber-50/60 px-4 py-3 text-sm dark:border-amber-900/40 dark:bg-amber-950/15">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-amber-700/80 dark:text-amber-300/80">{{ __('Manifest') }}</p>
                                <div class="mt-1.5 flex items-center justify-center gap-3">
                                    <span class="inv-mono text-lg font-bold text-slate-700 dark:text-slate-200" x-text="selectedStockText"></span>
                                    <span class="text-amber-500">&#8594;</span>
                                    <span class="inv-mono text-lg" :class="projectedStockClass" x-text="projectedStockText"></span>
                                </div>
                                <div class="mt-1 flex items-center justify-center gap-3 text-[10px] uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">
                                    <span>{{ __('Current stock') }}</span><span></span><span>{{ __('Projected stock') }}</span>
                                </div>
                                <p x-show="projectedNegative" x-cloak class="mt-2 text-center text-xs font-medium text-rose-700 dark:text-rose-300">{{ __('Stock out cannot exceed current stock.') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Reference') }}</label>
                                <input type="text" name="reference" value="{{ old('reference') }}" placeholder="{{ __('PO-1001, Return-22...') }}" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Note') }}</label>
                                <textarea name="note" rows="3" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="{{ __('Optional note...') }}">{{ old('note') }}</textarea>
                            </div>

                            <button type="submit" class="w-full rounded-lg bg-[#04042a] px-4 py-2.5 text-sm font-bold text-amber-400 transition hover:bg-[#10104a]">
                                {{ __('Save Movement') }}
                            </button>
                        </form>
                    </section>

                    {{-- Bulk CSV import (route existed without any UI) --}}
                    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">{{ __('Bulk Delivery') }}</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('Import CSV') }}</h3>
                            </div>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ __('Batch') }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('admin.inventory.import') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                            @csrf
                            <input type="file" name="import_file" accept=".csv,.txt" required
                                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#04042a] file:px-4 file:py-2 file:text-sm file:font-bold file:text-amber-400 hover:file:bg-[#10104a] dark:text-slate-300">
                            <button type="submit" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('Upload & Import') }}
                            </button>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('Required columns:') }} <code class="inv-mono">product_sku, type, quantity</code><br>
                                {{ __('Optional:') }} <code class="inv-mono">warehouse_code, reference, note, performed_at</code>
                            </p>
                            <a href="{{ $csvTemplateHref }}" download="inventory-import-template.csv" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 underline decoration-amber-300 underline-offset-2 transition hover:text-amber-800 dark:text-amber-300 dark:hover:text-amber-200">
                                &#8681; {{ __('Download template CSV') }}
                            </a>
                        </form>
                    </section>
                </div>

                {{-- ===== Right: filters + dock lanes ===== --}}
                <section class="xl:col-span-2 space-y-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <form method="GET" action="{{ route('admin.inventory.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('Search product, user, reference...') }}" class="lg:col-span-4 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <select name="type" class="lg:col-span-2 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="">{{ __('All types') }}</option>
                                <option value="in" @selected($type === 'in')>{{ __('Stock In') }}</option>
                                <option value="out" @selected($type === 'out')>{{ __('Stock Out') }}</option>
                            </select>
                            <select name="product_id" class="lg:col-span-3 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="0">{{ __('All products') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected((int) $productId === (int) $product->id)>{{ $product->name }}</option>
                                @endforeach
                            </select>
                            @if($hasWarehouseSupport)
                                <select name="warehouse_id" class="lg:col-span-3 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="0">{{ __('All warehouses') }}</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" @selected((int) $warehouseId === (int) $warehouse->id)>{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <input type="date" name="from" value="{{ $from }}" class="lg:col-span-3 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <input type="date" name="to" value="{{ $to }}" class="lg:col-span-3 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-amber-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <div class="lg:col-span-6 flex flex-wrap gap-2">
                                <button type="submit" class="rounded-lg bg-[#04042a] px-4 py-2 text-sm font-bold text-amber-400 transition hover:bg-[#10104a]">{{ __('Filter') }}</button>
                                <a href="{{ route('admin.inventory.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Reset') }}</a>
                                <a href="{{ route('admin.inventory.export', request()->query()) }}" class="ml-auto rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-400 hover:text-amber-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:text-amber-300">&#8681; {{ __('Export CSV') }}</a>
                            </div>
                        </form>
                    </div>

                    <div class="grid gap-4 {{ $singleLane ? '' : 'lg:grid-cols-2' }}">
                        @if(!$singleLane || $type === 'in')
                            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <header class="flex items-baseline justify-between gap-3 border-b border-amber-100 bg-amber-50/80 px-4 py-3 dark:border-amber-900/40 dark:bg-amber-950/20">
                                    <h4 class="text-xs font-extrabold uppercase tracking-[0.16em] text-amber-700 dark:text-amber-300">&#8595; {{ __('Inbound') }}</h4>
                                    <p class="text-[11px] font-bold text-amber-700/80 dark:text-amber-300/80">{{ number_format($inboundMovements->count()) }} {{ __('on page') }} &middot; <span class="inv-mono">+{{ number_format($inboundPageQty) }}</span></p>
                                </header>
                                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse($inboundMovements as $movement)
                                        @php $movementDate = $movement->performed_at ?? $movement->created_at; @endphp
                                        <div class="flex items-start gap-3 px-4 py-3 transition hover:bg-amber-50/50 dark:hover:bg-slate-800/60">
                                            <span class="inv-mono w-14 shrink-0 pt-0.5 text-base font-black text-amber-600 dark:text-amber-400">+{{ number_format($movement->quantity) }}</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $movement->product->name ?? __('Deleted Product') }}</p>
                                                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $movement->product->sku ?? __('N/A') }}
                                                    @if($hasWarehouseSupport && $movement->warehouse) &middot; {{ $movement->warehouse->name }} @endif
                                                    @if($movement->reference) &middot; {{ $movement->reference }} @endif
                                                </p>
                                                @if($movement->note)
                                                    <p class="mt-0.5 max-w-full truncate text-xs italic text-slate-400 dark:text-slate-500" title="{{ $movement->note }}">{{ $movement->note }}</p>
                                                @endif
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <p class="inv-mono text-xs text-slate-600 dark:text-slate-300">{{ $movement->stock_before }} <span class="text-amber-500">&#8594;</span> <b class="text-slate-900 dark:text-slate-100">{{ $movement->stock_after }}</b></p>
                                                <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">{{ $movement->user->name ?? __('Unknown') }} &middot; {{ $movementDate?->format('d M H:i') }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-4 py-10 text-center">
                                            <p class="mx-auto max-w-xs rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-4 py-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400">{{ __('No inbound movements on this page.') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </article>
                        @endif

                        @if(!$singleLane || $type === 'out')
                            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <header class="flex items-baseline justify-between gap-3 border-b border-rose-100 bg-rose-50/80 px-4 py-3 dark:border-rose-900/40 dark:bg-rose-950/20">
                                    <h4 class="text-xs font-extrabold uppercase tracking-[0.16em] text-rose-700 dark:text-rose-300">&#8593; {{ __('Outbound') }}</h4>
                                    <p class="text-[11px] font-bold text-rose-700/80 dark:text-rose-300/80">{{ number_format($outboundMovements->count()) }} {{ __('on page') }} &middot; <span class="inv-mono">&minus;{{ number_format($outboundPageQty) }}</span></p>
                                </header>
                                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse($outboundMovements as $movement)
                                        @php $movementDate = $movement->performed_at ?? $movement->created_at; @endphp
                                        <div class="flex items-start gap-3 px-4 py-3 transition hover:bg-rose-50/50 dark:hover:bg-slate-800/60">
                                            <span class="inv-mono w-14 shrink-0 pt-0.5 text-base font-black text-rose-600 dark:text-rose-400">&minus;{{ number_format($movement->quantity) }}</span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $movement->product->name ?? __('Deleted Product') }}</p>
                                                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                                    {{ $movement->product->sku ?? __('N/A') }}
                                                    @if($hasWarehouseSupport && $movement->warehouse) &middot; {{ $movement->warehouse->name }} @endif
                                                    @if($movement->reference) &middot; {{ $movement->reference }} @endif
                                                </p>
                                                @if($movement->note)
                                                    <p class="mt-0.5 max-w-full truncate text-xs italic text-slate-400 dark:text-slate-500" title="{{ $movement->note }}">{{ $movement->note }}</p>
                                                @endif
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <p class="inv-mono text-xs text-slate-600 dark:text-slate-300">{{ $movement->stock_before }} <span class="text-rose-500">&#8594;</span> <b class="text-slate-900 dark:text-slate-100">{{ $movement->stock_after }}</b></p>
                                                <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">{{ $movement->user->name ?? __('Unknown') }} &middot; {{ $movementDate?->format('d M H:i') }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-4 py-10 text-center">
                                            <p class="mx-auto max-w-xs rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-4 py-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400">{{ __('No outbound movements on this page.') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </article>
                        @endif
                    </div>

                    @if($pageMovements->isEmpty())
                        <div class="rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ __('No movement history found') }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Add stock movements or adjust the filters to build history logs.') }}</p>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        {{ $movements->links() }}
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
