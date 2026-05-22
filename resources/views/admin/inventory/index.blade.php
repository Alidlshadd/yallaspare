<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-slate-100">{{ __('Inventory Movements') }}</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Track stock adjustments with product, warehouse, user, date, and reference history.') }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                {{ __('Stock Operations') }}
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
        $metricCards = [
            [
                'label' => __('Movements'),
                'value' => number_format($totalMovements),
                'detail' => __('Matching current filters'),
                'class' => 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900',
                'valueClass' => 'text-slate-900 dark:text-slate-100',
            ],
            [
                'label' => __('Stock In'),
                'value' => '+' . number_format($totalStockIn),
                'detail' => __('Units added'),
                'class' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20',
                'valueClass' => 'text-emerald-800 dark:text-emerald-200',
            ],
            [
                'label' => __('Stock Out'),
                'value' => '-' . number_format($totalStockOut),
                'detail' => __('Units removed'),
                'class' => 'border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/20',
                'valueClass' => 'text-rose-800 dark:text-rose-200',
            ],
            [
                'label' => __('Net Change'),
                'value' => ($netMovement >= 0 ? '+' : '') . number_format($netMovement),
                'detail' => number_format($todayMovements) . ' ' . __('movements today'),
                'class' => 'border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-950/20',
                'valueClass' => $netMovement >= 0 ? 'text-blue-800 dark:text-blue-200' : 'text-amber-800 dark:text-amber-200',
            ],
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach($metricCards as $card)
                    <article class="rounded-2xl border p-5 shadow-sm {{ $card['class'] }}">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold {{ $card['valueClass'] }}">{{ $card['value'] }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $card['detail'] }}</p>
                    </article>
                @endforeach
            </section>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <section
                    class="xl:col-span-1 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                    x-data="{
                        products: @js($productOptions),
                        labels: {
                            na: @js(__('N/A')),
                            part: @js(__('Part:')),
                            oem: @js(__('OEM:')),
                            stock: @js(__('Stock:')),
                        },
                        productId: '{{ old('product_id', '') }}',
                        productSearch: @js($selectedProductLabel),
                        productOpen: false,
                        productActiveIndex: 0,
                        type: '{{ old('type', 'in') }}',
                        quantity: Number('{{ old('quantity', 1) }}') || 1,
                        get selectedProduct() {
                            return this.products.find((product) => String(product.id) === String(this.productId)) || null;
                        },
                        get filteredProducts() {
                            const term = this.productSearch.toLowerCase().trim();
                            const matches = term === ''
                                ? this.products
                                : this.products.filter((product) => {
                                    return [
                                        product.name,
                                        product.sku,
                                        product.part_number,
                                        product.oem_number,
                                        product.brand,
                                    ].some((value) => String(value || '').toLowerCase().includes(term));
                                });

                            return matches.slice(0, 50);
                        },
                        get projectedStock() {
                            if (!this.selectedProduct) return null;
                            return this.type === 'in'
                                ? Number(this.selectedProduct.stock) + Number(this.quantity || 0)
                                : Number(this.selectedProduct.stock) - Number(this.quantity || 0);
                        },
                        productLabel(product) {
                            return product ? `${product.name} (${product.sku || this.labels.na})` : '';
                        },
                        productMeta(product) {
                            return [
                                product.brand,
                                product.part_number ? `${this.labels.part} ${product.part_number}` : '',
                                product.oem_number ? `${this.labels.oem} ${product.oem_number}` : '',
                                `${this.labels.stock} ${product.stock}`,
                            ].filter(Boolean).join(' | ');
                        },
                        selectProduct(product) {
                            this.productId = String(product.id);
                            this.productSearch = this.productLabel(product);
                            this.productOpen = false;
                        },
                        clearProduct() {
                            this.productId = '';
                            this.productSearch = '';
                            this.productActiveIndex = 0;
                            this.productOpen = true;
                            this.$nextTick(() => this.$refs.productSearch?.focus());
                        },
                        moveProductHighlight(step) {
                            const count = this.filteredProducts.length;
                            if (!count) return;
                            this.productActiveIndex = (this.productActiveIndex + step + count) % count;
                        },
                        commitHighlightedProduct() {
                            const product = this.filteredProducts[this.productActiveIndex] || this.filteredProducts[0];
                            if (product) this.selectProduct(product);
                        }
                    }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Adjustment') }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('Add Movement') }}</h3>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ __('Manual') }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.inventory.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="relative" @click.outside="productOpen = false">
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Product') }}</label>
                            <input type="hidden" name="product_id" x-model="productId">
                            <div class="flex rounded-lg border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-blue-500 dark:border-slate-700 dark:bg-slate-950">
                                <input
                                    type="text"
                                    x-ref="productSearch"
                                    x-model="productSearch"
                                    @focus="productOpen = true"
                                    @input="productId = ''; productOpen = true; productActiveIndex = 0"
                                    @keydown.arrow-down.prevent="productOpen = true; moveProductHighlight(1)"
                                    @keydown.arrow-up.prevent="productOpen = true; moveProductHighlight(-1)"
                                    @keydown.enter.prevent="commitHighlightedProduct()"
                                    @keydown.escape="productOpen = false"
                                    placeholder="{{ __('Search product by name, SKU, part number, OEM, or brand...') }}"
                                    class="min-w-0 flex-1 rounded-l-lg border-0 bg-transparent text-slate-900 focus:ring-0 dark:text-slate-100"
                                    autocomplete="off"
                                    required
                                >
                                <button
                                    type="button"
                                    x-show="productSearch !== ''"
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
                                        @mouseenter="productActiveIndex = index"
                                        class="block w-full px-3 py-2 text-left transition"
                                        :class="productActiveIndex === index ? 'bg-blue-50 dark:bg-blue-950/40' : 'hover:bg-slate-50 dark:hover:bg-slate-900'"
                                    >
                                        <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="productLabel(product)"></span>
                                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400" x-text="productMeta(product)"></span>
                                    </button>
                                </template>
                                <div x-show="filteredProducts.length === 0" class="px-3 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
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
                                <select name="warehouse_id" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
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

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Type') }}</label>
                                <select name="type" x-model="type" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
                                    <option value="in" @selected(old('type') === 'in')>{{ __('Stock In') }}</option>
                                    <option value="out" @selected(old('type') === 'out')>{{ __('Stock Out') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Quantity') }}</label>
                                <input type="number" name="quantity" min="1" x-model.number="quantity" value="{{ old('quantity', 1) }}" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
                            </div>
                        </div>

                        <div x-show="selectedProduct" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-500 dark:text-slate-400">{{ __('Current stock') }}</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100" x-text="selectedProduct?.stock"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <span class="text-slate-500 dark:text-slate-400">{{ __('Projected stock') }}</span>
                                <span class="font-semibold" :class="projectedStock < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-900 dark:text-slate-100'" x-text="projectedStock"></span>
                            </div>
                            <p x-show="projectedStock < 0" class="mt-2 text-xs font-medium text-rose-700 dark:text-rose-300">{{ __('Stock out cannot exceed current stock.') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Reference') }}</label>
                            <input type="text" name="reference" value="{{ old('reference') }}" placeholder="{{ __('PO-1001, Return-22...') }}" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Note') }}</label>
                            <textarea name="note" rows="3" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="{{ __('Optional note...') }}">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                            {{ __('Save Movement') }}
                        </button>
                    </form>
                </section>

                <section class="xl:col-span-2 space-y-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <form method="GET" action="{{ route('admin.inventory.index') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('Search product, user, reference...') }}" class="lg:col-span-4 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <select name="type" class="lg:col-span-2 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="">{{ __('All types') }}</option>
                                <option value="in" @selected($type === 'in')>{{ __('Stock In') }}</option>
                                <option value="out" @selected($type === 'out')>{{ __('Stock Out') }}</option>
                            </select>
                            <select name="product_id" class="lg:col-span-3 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="0">{{ __('All products') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected((int) $productId === (int) $product->id)>{{ $product->name }}</option>
                                @endforeach
                            </select>
                            @if($hasWarehouseSupport)
                                <select name="warehouse_id" class="lg:col-span-3 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="0">{{ __('All warehouses') }}</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" @selected((int) $warehouseId === (int) $warehouse->id)>{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <input type="date" name="from" value="{{ $from }}" class="lg:col-span-3 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <input type="date" name="to" value="{{ $to }}" class="lg:col-span-3 rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <div class="lg:col-span-6 flex flex-wrap gap-2">
                                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Filter') }}</button>
                                <a href="{{ route('admin.inventory.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Reset') }}</a>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 text-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
                                    <tr>
                                        <th class="p-4 font-semibold">{{ __('Date') }}</th>
                                        <th class="p-4 font-semibold">{{ __('Product') }}</th>
                                        @if($hasWarehouseSupport)
                                            <th class="p-4 font-semibold">{{ __('Warehouse') }}</th>
                                        @endif
                                        <th class="p-4 font-semibold">{{ __('Type') }}</th>
                                        <th class="p-4 font-semibold">{{ __('Qty') }}</th>
                                        <th class="p-4 font-semibold">{{ __('Stock Change') }}</th>
                                        <th class="p-4 font-semibold">{{ __('By User') }}</th>
                                        <th class="p-4 font-semibold">{{ __('Reference') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                                    @forelse($movements as $movement)
                                        @php
                                            $movementDate = $movement->performed_at ?? $movement->created_at;
                                        @endphp
                                        <tr class="hover:bg-slate-50 transition dark:hover:bg-slate-800/60">
                                            <td class="p-4 text-slate-500 dark:text-slate-400">
                                                <div>{{ $movementDate?->format('d M Y') }}</div>
                                                <div class="text-xs">{{ $movementDate?->format('H:i') }}</div>
                                            </td>
                                            <td class="p-4">
                                                <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $movement->product->name ?? __('Deleted Product') }}</div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('SKU:') }} {{ $movement->product->sku ?? __('N/A') }}</div>
                                            </td>
                                            @if($hasWarehouseSupport)
                                                <td class="p-4 text-slate-600 dark:text-slate-300">
                                                    @if($movement->warehouse)
                                                        <div class="font-medium text-slate-800 dark:text-slate-100">{{ $movement->warehouse->name }}</div>
                                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $movement->warehouse->code }} / {{ $movement->warehouse->city }}</div>
                                                    @else
                                                        <span class="text-slate-400">{{ __('General') }}</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="p-4">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $movement->type === 'in' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300' }}">
                                                    {{ $movement->type === 'in' ? __('Stock In') : __('Stock Out') }}
                                                </span>
                                            </td>
                                            <td class="p-4 font-semibold {{ $movement->type === 'in' ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                                {{ $movement->type === 'in' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                                            </td>
                                            <td class="p-4 text-slate-600 dark:text-slate-300">
                                                <span class="font-medium">{{ $movement->stock_before }}</span>
                                                <i class="fas fa-arrow-right mx-1 text-xs text-slate-400"></i>
                                                <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $movement->stock_after }}</span>
                                            </td>
                                            <td class="p-4 text-slate-600 dark:text-slate-300">
                                                <div>{{ $movement->user->name ?? __('Unknown') }}</div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $movement->user->email ?? '' }}</div>
                                            </td>
                                            <td class="p-4 text-slate-600 dark:text-slate-300">
                                                <div>{{ $movement->reference ?: '-' }}</div>
                                                @if($movement->note)
                                                    <div class="text-xs text-slate-500 mt-1 max-w-[220px] truncate dark:text-slate-400" title="{{ $movement->note }}">{{ $movement->note }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $hasWarehouseSupport ? 8 : 7 }}" class="p-10 text-center text-slate-500 dark:text-slate-400">
                                                <p class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ __('No movement history found') }}</p>
                                                <p class="mt-1">{{ __('Add stock movements or adjust the filters to build history logs.') }}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-t border-gray-200 dark:border-slate-800">
                            {{ $movements->links() }}
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
