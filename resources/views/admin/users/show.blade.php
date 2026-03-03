<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-2xl text-gray-800">User Details</h2>
                <p class="text-sm text-gray-500 mt-1">Extended user insights (super admin only)</p>
            </div>
            <a
                href="{{ route('admin.users.index') }}"
                class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium"
            >
                Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">User ID</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">#{{ $user->id }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Name</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">{{ $user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Email</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800 break-all">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Phone</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">{{ $user->phone ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Role</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">{{ ucwords(str_replace('_', ' ', $user->role)) }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Dealer Status</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">{{ ucwords(str_replace('_', ' ', (string) $user->dealer_status)) }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Dealer Discount</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">{{ number_format((float) $user->dealer_discount, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Email Verified</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">
                            {{ $user->email_verified_at ? $user->email_verified_at->format('d M Y H:i') : 'No' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Created</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">{{ $user->created_at?->format('d M Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Updated</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">{{ $user->updated_at?->format('d M Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Last Order</p>
                        <p class="mt-1 text-lg font-semibold text-gray-800">
                            {{ $stats['last_order_at'] ? $stats['last_order_at']->format('d M Y H:i') : '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Total Orders</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800">{{ number_format($stats['orders_total']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-emerald-100 p-4">
                    <p class="text-xs uppercase tracking-wide text-emerald-700">Delivered Orders</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($stats['orders_delivered']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-rose-100 p-4">
                    <p class="text-xs uppercase tracking-wide text-rose-700">Cancelled Orders</p>
                    <p class="mt-2 text-2xl font-bold text-rose-700">{{ number_format($stats['orders_cancelled']) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4">
                    <p class="text-xs uppercase tracking-wide text-blue-700">Total Spent</p>
                    <p class="mt-2 text-2xl font-bold text-blue-700">${{ number_format($stats['spent_total'], 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Recent Orders</h3>
                    <p class="text-xs text-gray-500">Last {{ $recentOrders->count() }} order(s)</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="p-4 font-semibold">Order</th>
                                <th class="p-4 font-semibold">Status</th>
                                <th class="p-4 font-semibold">Amount</th>
                                <th class="p-4 font-semibold">Date</th>
                                <th class="p-4 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($recentOrders as $order)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 font-medium">{{ $order->order_number }}</td>
                                    <td class="p-4">{{ ucwords(str_replace('_', ' ', $order->status)) }}</td>
                                    <td class="p-4">${{ number_format((float) $order->total_amount, 2) }}</td>
                                    <td class="p-4 text-gray-500">{{ $order->created_at?->format('d M Y H:i') }}</td>
                                    <td class="p-4 text-right">
                                        <a
                                            href="{{ route('admin.orders.show', $order) }}"
                                            class="px-3 py-1.5 rounded-md bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800"
                                        >
                                            Open Order
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-10 text-center text-gray-500">No orders found for this user.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
