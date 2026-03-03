<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <h2 class="font-semibold text-2xl text-gray-800">Orders Management</h2>
            <p class="text-sm text-gray-500">Track orders, update status, and review customer or dealer activity.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Total</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($stats['total'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <p class="text-xs uppercase text-amber-700">Pending</p>
                    <p class="mt-2 text-2xl font-bold text-amber-800">{{ number_format($stats['pending'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                    <p class="text-xs uppercase text-blue-700">Processing</p>
                    <p class="mt-2 text-2xl font-bold text-blue-800">{{ number_format($stats['processing'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                    <p class="text-xs uppercase text-indigo-700">Shipped</p>
                    <p class="mt-2 text-2xl font-bold text-indigo-800">{{ number_format($stats['shipped'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs uppercase text-emerald-700">Delivered</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format($stats['delivered'] ?? 0) }}</p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <p class="text-xs uppercase text-rose-700">Cancelled</p>
                    <p class="mt-2 text-2xl font-bold text-rose-800">{{ number_format($stats['cancelled'] ?? 0) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-4">
                    <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search order #, city, phone, user..."
                            class="md:col-span-2 rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <select name="status" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                        <select name="association" class="rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Users</option>
                            <option value="user" @selected(($association ?? '') === 'user')>Retail Users</option>
                            <option value="dealer" @selected(($association ?? '') === 'dealer')>Dealers</option>
                        </select>
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Apply Filters
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">User / Dealer</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Items</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">Date</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($orders as $order)
                                @php
                                    $isDealer = $order->user && $order->user->role === \App\Models\User::ROLE_DEALER;
                                    $statusMeta = \App\Models\Order::statusMeta((string) $order->status);
                                    $orderTransitions = $transitionOptions[$order->id] ?? [$order->status];
                                @endphp
                                <tr>
                                    <td class="px-4 py-4">
                                        <p class="text-sm font-semibold text-slate-900">{{ $order->order_number }}</p>
                                        <p class="text-xs text-slate-500">ID #{{ $order->id }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="text-sm font-medium text-slate-900">{{ $order->user?->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-slate-500">{{ $order->user?->email ?? '-' }}</p>
                                        @if($order->user)
                                            <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $isDealer ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $isDealer ? 'Dealer' : 'User' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $order->items_count }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">${{ number_format((float) $order->total_amount, 2) }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">
                                            {{ $statusMeta['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="text-sm text-slate-700">{{ $order->created_at?->format('M d, Y') }}</p>
                                        <p class="text-xs text-slate-500">{{ $order->created_at?->format('h:i A') }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                                View
                                            </a>
                                            <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="flex items-center gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="rounded-md border-slate-300 py-1 text-xs focus:border-blue-500 focus:ring-blue-500">
                                                    @foreach($orderTransitions as $status)
                                                        <option value="{{ $status }}" @selected($order->status === $status)>
                                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="rounded-md bg-blue-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                                    Save
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order permanently?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <p class="text-sm font-medium text-slate-700">No orders found for the current filter.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                    <div class="border-t border-slate-200 px-4 py-4">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
