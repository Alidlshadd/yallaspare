<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-100">
            {{ __('Products') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ session('warning') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @php
                $importErrors = session('import_errors', []);
            @endphp

            <!-- HEADER + ADD BUTTON -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold dark:text-slate-100">{{ $statusTabs[$status]['label'] ?? __('All Products') }}</h3>

               <a href="{{ route('admin.products.create') }}"

                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    {{ __('+ Add Product') }}
                </a>
            </div>

            <div class="mb-4 grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2 bg-white rounded-xl border border-gray-200 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h4 class="font-semibold text-gray-800 mb-3 dark:text-slate-100">{{ __('Bulk Import') }}</h4>
                    <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="flex flex-col md:flex-row md:items-center gap-3">
                        @csrf
                        <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">
                            {{ __('Import File') }}
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2 dark:text-slate-400">
                        {{ __('Supported files: CSV, TXT, XLS, XLSX.') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1 dark:text-slate-400">
                        {{ __('Required columns:') }} <span class="font-medium">{{ __('name_en, name_ar, name_ku, price, stock_quantity') }}</span>{{ __('. Optional: dealer_price, sku, oem_number, part_number, warranty, brand, description_*, is_active.') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1 dark:text-slate-400">
                        {{ __('Category is required for each row using one of:') }} <span class="font-medium">{{ __('category_id') }}</span>, <span class="font-medium">{{ __('category_slug') }}</span>{{ __(', or') }} <span class="font-medium">{{ __('category_name') }}</span>.
                    </p>
                    <p class="text-xs text-gray-500 mt-1 dark:text-slate-400">
                        {{ __('Header format is flexible. For example,') }} <span class="font-medium">{{ __('Name EN') }}</span> {{ __('or') }} <span class="font-medium">{{ __('name_en') }}</span> {{ __('both work.') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h4 class="font-semibold text-gray-800 mb-3 dark:text-slate-100">{{ __('Export') }}</h4>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('admin.products.export-excel') }}" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold text-center transition">
                            {{ __('Export Excel (.xlsx)') }}
                        </a>
                    </div>
                </div>
            </div>

            @if(count($importErrors) > 0)
                <div class="mb-4 bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden dark:border-red-900/50 dark:bg-slate-900">
                    <div class="px-4 py-3 bg-red-50 border-b border-red-200 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-300">
                        {{ __('Import Error Report') }} ({{ count($importErrors) }} {{ __('rows') }})
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 dark:bg-slate-800/70 dark:text-slate-300">
                                <tr>
                                    <th class="p-3">{{ __('Row') }}</th>
                                    <th class="p-3">{{ __('SKU') }}</th>
                                    <th class="p-3">{{ __('Error') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($importErrors as $errorRow)
                                    <tr class="border-t dark:border-slate-800">
                                        <td class="p-3 font-medium text-gray-700 dark:text-slate-200">{{ $errorRow['row'] ?? '-' }}</td>
                                        <td class="p-3 text-gray-600 dark:text-slate-300">{{ $errorRow['sku'] ?? '-' }}</td>
                                        <td class="p-3 text-red-700 dark:text-red-300">{{ $errorRow['message'] ?? __('Unknown error') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ __('Low stock threshold is set to') }} <span class="font-semibold">{{ $lowStockThreshold }}</span>{{ __('. Current low stock products:') }} <span class="font-semibold">{{ $lowStockCount }}</span>.
            </div>

            @php
                $currentSort = $sort ?? request('sort', 'id');
                $currentDir = $direction ?? request('dir', 'desc');
                $currentStatus = $status ?? request('status', 'all');
                $sortUrl = function ($field) use ($currentSort, $currentDir) {
                    $dir = $currentSort === $field && $currentDir === 'asc' ? 'desc' : 'asc';
                    return route('admin.products.index', array_merge(request()->except('page'), [
                        'sort' => $field,
                        'dir' => $dir,
                    ]));
                };
                $statusUrl = function ($statusKey) {
                    return route('admin.products.index', array_merge(request()->except('page', 'status', 'low_stock'), [
                        'status' => $statusKey,
                    ]));
                };
            @endphp

            <div class="mb-4 flex flex-wrap gap-2">
                @foreach($statusTabs as $statusKey => $tab)
                    @php
                        $isSelected = $currentStatus === $statusKey;
                    @endphp
                    <a
                        href="{{ $statusUrl($statusKey) }}"
                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold transition {{ $isSelected ? 'border-cyan-300 bg-cyan-100 text-cyan-800 shadow-sm shadow-cyan-900/5 dark:border-cyan-400/40 dark:bg-cyan-400/15 dark:text-cyan-100' : 'border-slate-200 bg-white text-slate-600 hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-800 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-cyan-500/30 dark:hover:bg-cyan-500/10 dark:hover:text-cyan-100' }}"
                    >
                        <span>{{ $tab['label'] }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $isSelected ? 'bg-white/70 text-cyan-900 dark:bg-cyan-300/20 dark:text-cyan-50' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">{{ $tab['count'] }}</span>
                    </a>
                @endforeach
            </div>

            <!-- SEARCH + FILTER -->
            <form method="GET" action="{{ route('admin.products.index') }}" class="mb-4 flex flex-wrap gap-2 items-center">
                <input type="hidden" name="status" value="{{ $currentStatus }}">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="{{ __('Search product...') }}"
                       class="w-64 rounded border border-gray-300 bg-white px-3 py-2 text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">

                <select name="category_id" class="rounded border border-gray-300 bg-white px-3 py-2 text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ (string)request('category_id') === (string)$category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <select name="brand" class="rounded border border-gray-300 bg-white px-3 py-2 text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="">{{ __('All Brands') }}</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand }}"
                            {{ (string)request('brand') === (string)$brand ? 'selected' : '' }}>
                            {{ $brand }}
                        </option>
                    @endforeach
                </select>

                <button type="submit"
                        class="bg-gray-800 text-white px-4 py-2 rounded">
                    {{ __('Apply') }}
                </button>

                <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-600 underline dark:text-slate-400">
                    {{ __('Reset') }}
                </a>
            </form>

            <!-- TABLE -->
            <div class="overflow-x-auto rounded bg-white shadow dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
                <table class="min-w-[980px] w-full text-left">
                    <thead class="bg-gray-100 dark:bg-slate-800/70 dark:text-slate-300">
                        <tr>
                            <th class="p-3">
                                <a href="{{ $sortUrl('id') }}" class="hover:underline">{{ __('ID') }}</a>
                            </th>
                            <th>{{ __('Image') }}</th>
                            <th>
                                <a href="{{ $sortUrl('name_en') }}" class="hover:underline">{{ __('Name') }}</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('sku') }}" class="hover:underline">{{ __('SKU') }}</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('brand') }}" class="hover:underline">{{ __('Brand') }}</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('is_active') }}" class="hover:underline">{{ __('Status') }}</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('price') }}" class="hover:underline">{{ __('Price') }}</a>
                            </th>
                            <th>{{ __('Dealer Price') }}</th>
                            <th>
                                <a href="{{ $sortUrl('stock_quantity') }}" class="hover:underline">{{ __('Stock') }}</a>
                            </th>
                            <th class="text-right pr-4">{{ __('Action') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($products as $product)
                            <tr class="border-t hover:bg-gray-50 dark:border-slate-800 dark:hover:bg-slate-800/60">
                                <td class="p-3 dark:text-slate-200">{{ $product->id }}</td>
                                <td>
                                    @if($product->image)
                                        <img
                                            src="{{ asset('storage/' . ltrim((string) $product->image, '/')) }}"
                                            alt="{{ $product->name }}"
                                            class="h-12 w-12 rounded-lg border border-slate-200 bg-white object-cover dark:border-slate-700 dark:bg-slate-950"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-600">
                                            <i class="fas fa-image text-sm"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="dark:text-slate-100">{{ $product->name }}</td>
                                <td class="dark:text-slate-300">{{ $product->sku }}</td>
                                <td class="dark:text-slate-300">{{ $product->brand ?? '-' }}</td>
                                <td>
                                    <span class="px-2 py-1 text-xs rounded {{ $product->is_active ? 'bg-green-100 text-green-700 dark:bg-green-950/30 dark:text-green-300' : 'bg-gray-200 text-gray-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $product->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="dark:text-slate-200">{{ $currencyLabel }} {{ number_format($product->price, $currencyDecimals) }}</td>
                                <td>
                                    @if($product->dealer_price !== null)
                                        <span class="inline-flex items-center px-2 py-1 text-xs rounded bg-indigo-100 text-indigo-700 font-semibold">
                                            {{ $currencyLabel }} {{ number_format($product->dealer_price, $currencyDecimals) }}
                                        </span>
                                        @if((float) $product->dealer_price >= (float) $product->price)
                                            <span class="ml-2 inline-flex items-center px-2 py-1 text-xs rounded bg-amber-100 text-amber-800 font-semibold">
                                                {{ __('Margin warning') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-slate-400">{{ __('Use dealer discount') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $product->stock_quantity <= $lowStockThreshold ? 'text-red-700 font-semibold dark:text-red-300' : 'text-gray-800 dark:text-slate-200' }}">
                                        {{ __(':count units', ['count' => $product->stock_quantity]) }}
                                    </span>
                                    @if($product->stock_quantity === 0)
                                        <span class="ml-2 inline-block px-2 py-1 text-xs rounded bg-red-200 text-red-800 font-semibold">
                                            {{ __('Out of stock') }}
                                        </span>
                                    @elseif($product->stock_quantity <= $lowStockThreshold)
                                        <span class="ml-2 inline-block px-2 py-1 text-xs rounded bg-amber-100 text-amber-800 font-semibold">
                                            {{ __('Low stock') }}
                                        </span>
                                    @else
                                        <span class="ml-2 inline-block px-2 py-1 text-xs rounded bg-emerald-100 text-emerald-700">
                                            {{ __('In stock') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-right pr-4 space-x-4">

                                    <!-- EDIT -->
                                    <a href="{{ route('admin.products.edit', ['product' => $product, 'return_to' => request()->fullUrl()]) }}"
                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ __('Edit') }}
                                    </a>

                                    <!-- DELETE -->
                                    <form action="{{ route('admin.products.destroy', $product) }}"
                                          method="POST"
                                          data-danger-confirm
                                          data-danger-title="{{ __('Delete Product') }}"
                                          data-danger-description="{{ __('This action is permanent. The selected product will be removed and cannot be restored.') }}"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="text-red-600 hover:underline dark:text-red-400">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="p-4 text-center text-gray-500 dark:text-slate-400">
                                    {{ $statusTabs[$currentStatus]['empty'] ?? __('No products found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="mt-6">
                {{ $products->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
