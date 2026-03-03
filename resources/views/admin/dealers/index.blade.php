<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800">Dealers Management</h2>
            <span class="text-sm text-gray-500">Manage dealer status and discounts</span>
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

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Dealers</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalDealers) }}</p>
                </div>
                <div class="bg-white border border-emerald-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-emerald-700">Active</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">{{ number_format($activeDealers) }}</p>
                </div>
                <div class="bg-white border border-amber-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-amber-700">Inactive</p>
                    <p class="mt-2 text-3xl font-bold text-amber-700">{{ number_format($inactiveDealers) }}</p>
                </div>
                <div class="bg-white border border-rose-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-rose-700">Suspended</p>
                    <p class="mt-2 text-3xl font-bold text-rose-700">{{ number_format($suspendedDealers) }}</p>
                </div>
                <div class="bg-white border border-indigo-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-indigo-700">Avg Discount</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-700">{{ number_format($averageDiscount, 2) }}%</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
                <form method="GET" action="{{ route('admin.dealers.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search name, email, phone..."
                        class="md:col-span-2 rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
                    >
                    <select name="status" class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                        <option value="">All statuses</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                        <option value="suspended" @selected($status === 'suspended')>Suspended</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">Filter</button>
                        <a href="{{ route('admin.dealers.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="p-4 font-semibold">Dealer</th>
                                <th class="p-4 font-semibold">Contact</th>
                                <th class="p-4 font-semibold">Status</th>
                                <th class="p-4 font-semibold">Discount</th>
                                <th class="p-4 font-semibold">Joined</th>
                                <th class="p-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($dealers as $dealer)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-4">
                                        <div class="font-semibold text-slate-800">{{ $dealer->name }}</div>
                                        <div class="text-xs text-slate-500">#{{ $dealer->id }}</div>
                                    </td>
                                    <td class="p-4 text-slate-600">
                                        <div>{{ $dealer->email }}</div>
                                        <div class="text-xs text-slate-500">{{ $dealer->phone ?? 'No phone' }}</div>
                                    </td>
                                    <td class="p-4">
                                        @php
                                            $statusClass = match($dealer->dealer_status) {
                                                'active' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                'inactive' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                default => 'bg-rose-100 text-rose-700 border-rose-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusClass }}">
                                            {{ ucfirst($dealer->dealer_status) }}
                                        </span>
                                    </td>
                                    <td class="p-4 font-semibold text-indigo-700">{{ number_format((float) $dealer->dealer_discount, 2) }}%</td>
                                    <td class="p-4 text-slate-500">{{ $dealer->created_at?->format('d M Y') }}</td>
                                    <td class="p-4">
                                        <div class="flex justify-end">
                                            <details class="relative">
                                                <summary class="list-none cursor-pointer inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                                                    Manage
                                                </summary>
                                                <div class="absolute right-0 mt-2 w-72 z-20 bg-white border border-gray-200 rounded-xl shadow-xl p-3">
                                                    <form method="POST" action="{{ route('admin.dealers.update', $dealer) }}" class="space-y-3">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                                                            <select name="dealer_status" class="w-full rounded-lg border-gray-300 text-sm">
                                                                <option value="active" @selected($dealer->dealer_status === 'active')>Active</option>
                                                                <option value="inactive" @selected($dealer->dealer_status === 'inactive')>Inactive</option>
                                                                <option value="suspended" @selected($dealer->dealer_status === 'suspended')>Suspended</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-600 mb-1">Discount (%)</label>
                                                            <input type="number" name="dealer_discount" min="0" max="100" step="0.01" value="{{ old('dealer_discount', $dealer->dealer_discount) }}" class="w-full rounded-lg border-gray-300 text-sm">
                                                        </div>
                                                        <button type="submit" class="w-full px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Save changes</button>
                                                    </form>

                                                    <form method="POST" action="{{ route('admin.dealers.demote', $dealer) }}" class="mt-2" onsubmit="return confirm('Convert this dealer to regular user?');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold">Convert to user</button>
                                                    </form>
                                                </div>
                                            </details>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-slate-500">
                                        <p class="text-base font-semibold text-slate-700">No dealers found</p>
                                        <p class="mt-1">Try changing search/filter values.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-200">
                    {{ $dealers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
