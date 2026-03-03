<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800">Inventory Movements</h2>
            <span class="text-sm text-gray-500">Track stock in/out with full history</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Movements</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalMovements) }}</p>
                </div>
                <div class="bg-white border border-emerald-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-emerald-700">Total Stock In</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">+{{ number_format($totalStockIn) }}</p>
                </div>
                <div class="bg-white border border-rose-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-rose-700">Total Stock Out</p>
                    <p class="mt-2 text-3xl font-bold text-rose-700">-{{ number_format($totalStockOut) }}</p>
                </div>
                <div class="bg-white border border-indigo-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-indigo-700">Today</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-700">{{ number_format($todayMovements) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-1 bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Add Movement</h3>
                    <form method="POST" action="{{ route('admin.inventory.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Product</label>
                            <select name="product_id" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Select product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected((int) old('product_id') === (int) $product->id)>
                                        {{ $product->name_en }} ({{ $product->sku ?? 'N/A' }}) - Stock: {{ $product->stock_quantity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                                <select name="type" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" required>
                                    <option value="in" @selected(old('type') === 'in')>Stock In</option>
                                    <option value="out" @selected(old('type') === 'out')>Stock Out</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Quantity</label>
                                <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Reference</label>
                            <input type="text" name="reference" value="{{ old('reference') }}" placeholder="PO-1001, Return-22..." class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Note</label>
                            <textarea name="note" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="Optional note...">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit" class="w-full px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">
                            Save Movement
                        </button>
                    </form>
                </div>

                <div class="xl:col-span-2 space-y-4">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
                        <form method="GET" action="{{ route('admin.inventory.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search product, user, reference..." class="md:col-span-2 rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                            <select name="type" class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <option value="">All types</option>
                                <option value="in" @selected($type === 'in')>Stock In</option>
                                <option value="out" @selected($type === 'out')>Stock Out</option>
                            </select>
                            <select name="product_id" class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <option value="0">All products</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected((int) $productId === (int) $product->id)>{{ $product->name_en }}</option>
                                @endforeach
                            </select>
                            <div class="md:col-span-4 flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">Filter</button>
                                <a href="{{ route('admin.inventory.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="p-4 font-semibold">Date</th>
                                        <th class="p-4 font-semibold">Product</th>
                                        <th class="p-4 font-semibold">Type</th>
                                        <th class="p-4 font-semibold">Qty</th>
                                        <th class="p-4 font-semibold">Stock Change</th>
                                        <th class="p-4 font-semibold">By User</th>
                                        <th class="p-4 font-semibold">Reference</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse($movements as $movement)
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="p-4 text-slate-500">{{ $movement->created_at?->format('d M Y H:i') }}</td>
                                            <td class="p-4">
                                                <div class="font-semibold text-slate-800">{{ $movement->product->name_en ?? 'Deleted Product' }}</div>
                                                <div class="text-xs text-slate-500">SKU: {{ $movement->product->sku ?? 'N/A' }}</div>
                                            </td>
                                            <td class="p-4">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $movement->type === 'in' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                    {{ $movement->type === 'in' ? 'Stock In' : 'Stock Out' }}
                                                </span>
                                            </td>
                                            <td class="p-4 font-semibold {{ $movement->type === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                                                {{ $movement->type === 'in' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                                            </td>
                                            <td class="p-4 text-slate-600">
                                                <span class="font-medium">{{ $movement->stock_before }}</span>
                                                <i class="fas fa-arrow-right mx-1 text-xs text-slate-400"></i>
                                                <span class="font-semibold text-slate-800">{{ $movement->stock_after }}</span>
                                            </td>
                                            <td class="p-4 text-slate-600">
                                                <div>{{ $movement->user->name ?? 'Unknown' }}</div>
                                                <div class="text-xs text-slate-500">{{ $movement->user->email ?? '' }}</div>
                                            </td>
                                            <td class="p-4 text-slate-600">
                                                <div>{{ $movement->reference ?: '-' }}</div>
                                                @if($movement->note)
                                                    <div class="text-xs text-slate-500 mt-1 max-w-[220px] truncate" title="{{ $movement->note }}">{{ $movement->note }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="p-10 text-center text-slate-500">
                                                <p class="text-base font-semibold text-slate-700">No movement history found</p>
                                                <p class="mt-1">Add stock movements to build history logs.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            {{ $movements->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
