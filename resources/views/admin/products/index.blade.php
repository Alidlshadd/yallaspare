<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Products
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
                <h3 class="text-lg font-semibold">All Products</h3>

               <a href="{{ route('admin.products.create') }}"

                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    + Add Product
                </a>
            </div>

            <div class="mb-4 grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <h4 class="font-semibold text-gray-800 mb-3">Bulk Import</h4>
                    <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="flex flex-col md:flex-row md:items-center gap-3">
                        @csrf
                        <input type="file" name="import_file" accept=".csv,.txt,.xls" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">
                            Import CSV
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2">
                        Supported files: CSV, TXT, Excel-compatible XLS (text-delimited).
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Required columns: <span class="font-medium">name_en, name_ar, name_ku, price, stock_quantity</span>.
                        Optional: dealer_price, sku, brand, description_*, category_id/category_slug/category_name, is_active.
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                    <h4 class="font-semibold text-gray-800 mb-3">Export</h4>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('admin.products.export-excel') }}" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold text-center transition">
                            Export Excel (.xlsx)
                        </a>
                    </div>
                </div>
            </div>

            @if(count($importErrors) > 0)
                <div class="mb-4 bg-white rounded-xl border border-red-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-red-50 border-b border-red-200 text-sm font-semibold text-red-700">
                        Import Error Report ({{ count($importErrors) }} rows)
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="p-3">Row</th>
                                    <th class="p-3">SKU</th>
                                    <th class="p-3">Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($importErrors as $errorRow)
                                    <tr class="border-t">
                                        <td class="p-3 font-medium text-gray-700">{{ $errorRow['row'] ?? '-' }}</td>
                                        <td class="p-3 text-gray-600">{{ $errorRow['sku'] ?? '-' }}</td>
                                        <td class="p-3 text-red-700">{{ $errorRow['message'] ?? 'Unknown error' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Low stock threshold is set to <span class="font-semibold">{{ $lowStockThreshold }}</span>.
                Current low stock products: <span class="font-semibold">{{ $lowStockCount }}</span>.
            </div>

            @php
                $currentSort = $sort ?? request('sort', 'created_at');
                $currentDir = $direction ?? request('dir', 'desc');
                $sortUrl = function ($field) use ($currentSort, $currentDir) {
                    $dir = $currentSort === $field && $currentDir === 'asc' ? 'desc' : 'asc';
                    return route('admin.products.index', array_merge(request()->query(), [
                        'sort' => $field,
                        'dir' => $dir,
                    ]));
                };
            @endphp

            <!-- 🔎 SEARCH + FILTER -->
            <form method="GET" action="{{ route('admin.products.index') }}" class="mb-4 flex flex-wrap gap-2 items-center">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search product..."
                       class="border px-3 py-2 rounded w-64">

                <select name="category_id" class="border px-3 py-2 rounded">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ (string)request('category_id') === (string)$category->id ? 'selected' : '' }}>
                            {{ $category->name_en }}
                        </option>
                    @endforeach
                </select>

                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="low_stock" value="1" class="rounded border-gray-300"
                           {{ request('low_stock') ? 'checked' : '' }}>
                    <span class="ml-2">Low stock only (≤ {{ $lowStockThreshold }})</span>
                </label>

                <button type="submit"
                        class="bg-gray-800 text-white px-4 py-2 rounded">
                    Apply
                </button>

                <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-600 underline">
                    Reset
                </a>
            </form>

            <!-- TABLE -->
            <div class="bg-white rounded shadow overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">
                                <a href="{{ $sortUrl('id') }}" class="hover:underline">ID</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('name_en') }}" class="hover:underline">Name</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('sku') }}" class="hover:underline">SKU</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('brand') }}" class="hover:underline">Brand</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('is_active') }}" class="hover:underline">Status</a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('price') }}" class="hover:underline">Price</a>
                            </th>
                            <th>Dealer Price</th>
                            <th>
                                <a href="{{ $sortUrl('stock_quantity') }}" class="hover:underline">Stock</a>
                            </th>
                            <th class="text-right pr-4">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($products as $product)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-3">{{ $product->id }}</td>
                                <td>{{ $product->name_en }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->brand ?? '-' }}</td>
                                <td>
                                    <span class="px-2 py-1 text-xs rounded {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $currencyLabel }} {{ number_format($product->price, $currencyDecimals) }}</td>
                                <td>
                                    @if($product->dealer_price !== null)
                                        <span class="inline-flex items-center px-2 py-1 text-xs rounded bg-indigo-100 text-indigo-700 font-semibold">
                                            {{ $currencyLabel }} {{ number_format($product->dealer_price, $currencyDecimals) }}
                                        </span>
                                        @if((float) $product->dealer_price >= (float) $product->price)
                                            <span class="ml-2 inline-flex items-center px-2 py-1 text-xs rounded bg-amber-100 text-amber-800 font-semibold">
                                                Margin warning
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-500">Use dealer discount</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $product->stock_quantity <= $lowStockThreshold ? 'text-red-700 font-semibold' : 'text-gray-800' }}">
                                        {{ $product->stock_quantity }} units
                                    </span>
                                    @if($product->stock_quantity === 0)
                                        <span class="ml-2 inline-block px-2 py-1 text-xs rounded bg-red-200 text-red-800 font-semibold">
                                            Out of stock
                                        </span>
                                    @elseif($product->stock_quantity <= $lowStockThreshold)
                                        <span class="ml-2 inline-block px-2 py-1 text-xs rounded bg-amber-100 text-amber-800 font-semibold">
                                            Low stock
                                        </span>
                                    @else
                                        <span class="ml-2 inline-block px-2 py-1 text-xs rounded bg-emerald-100 text-emerald-700">
                                            In stock
                                        </span>
                                    @endif
                                </td>

                                <td class="text-right pr-4 space-x-4">

                                    <!-- EDIT -->
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                       class="text-blue-600 hover:underline">
                                        Edit
                                    </a>

                                    <!-- DELETE -->
                                    <form action="{{ route('admin.products.destroy', $product) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                onclick="return confirm('Are you sure?')"
                                                class="text-red-600 hover:underline">
                                            Delete
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-4 text-center text-gray-500">
                                    No products found.
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
