<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ __('Delivery Zones') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Manage city and district delivery fees, timing, and cash-on-delivery availability.') }}</p>
        </div>
    </x-slot>

    @php $money = fn ($value) => $value === null ? __('N/A') : number_format((float) $value, $currency['decimals']) . ' ' . $currency['label']; @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">{{ $errors->first() }}</div>
            @endif

            <section class="grid gap-4 md:grid-cols-4">
                @foreach([
                    __('Total Zones') => $summary['total'],
                    __('Active') => $summary['active'],
                    __('Inactive') => $summary['inactive'],
                    __('Cash On Delivery') => $summary['cod'],
                ] as $label => $value)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-black text-slate-900 dark:text-slate-100">{{ number_format($value) }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                <form method="POST" action="{{ route('admin.delivery-zones.store') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    @csrf
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ __('Add Delivery Zone') }}</h3>
                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-500">{{ __('City') }}</label>
                            <input name="city" value="{{ old('city') }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-500">{{ __('District') }}</label>
                            <input name="district" value="{{ old('district') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">{{ __('Shipping Fee') }}</label>
                                <input type="number" step="0.01" min="0" name="shipping_fee" value="{{ old('shipping_fee', '0') }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">{{ __('Free Over') }}</label>
                                <input type="number" step="0.01" min="0" name="free_shipping_min" value="{{ old('free_shipping_min') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-500">{{ __('Min Days') }}</label>
                                <input type="number" min="0" max="365" name="delivery_days_min" value="{{ old('delivery_days_min', 1) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">{{ __('Max Days') }}</label>
                                <input type="number" min="0" max="365" name="delivery_days_max" value="{{ old('delivery_days_max', 3) }}" required class="mt-1 w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="cash_on_delivery_enabled" value="1" checked class="rounded border-slate-300 text-slate-900">
                            {{ __('Cash on delivery') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-slate-900">
                            {{ __('Active') }}
                        </label>
                        <textarea name="notes" rows="3" placeholder="{{ __('Notes') }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ old('notes') }}</textarea>
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">{{ __('Create Zone') }}</button>
                    </div>
                </form>

                <div class="space-y-4 xl:col-span-2">
                    <form method="GET" action="{{ route('admin.delivery-zones.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="grid gap-3 md:grid-cols-4">
                            <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search city or district') }}" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 md:col-span-2">
                            <select name="status" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="all" @selected($status === 'all')>{{ __('All') }}</option>
                                <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                                <option value="inactive" @selected($status === 'inactive')>{{ __('Inactive') }}</option>
                            </select>
                            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">{{ __('Filter') }}</button>
                        </div>
                    </form>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Zone') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Fee') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Delivery') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Status') }}</th>
                                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse($zones as $zone)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $zone->name }}</div>
                                                <div class="text-xs text-slate-500">{{ $zone->notes ?: __('No notes') }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="font-semibold text-slate-700 dark:text-slate-200">{{ $money($zone->shipping_fee) }}</div>
                                                <div class="text-xs text-slate-500">{{ __('Free over') }} {{ $money($zone->free_shipping_min) }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $zone->delivery_days_min }}-{{ $zone->delivery_days_max }} {{ __('days') }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="space-y-1">
                                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-bold {{ $zone->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ $zone->is_active ? __('Active') : __('Inactive') }}</span>
                                                    <div class="text-xs text-slate-500">{{ $zone->cash_on_delivery_enabled ? __('COD enabled') : __('No COD') }}</div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <details class="inline-block text-left">
                                                    <summary class="cursor-pointer rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('Edit') }}</summary>
                                                    <div class="absolute right-6 z-20 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                                        <form method="POST" action="{{ route('admin.delivery-zones.update', $zone) }}" class="space-y-3">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input name="city" value="{{ $zone->city }}" required class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                            <input name="district" value="{{ $zone->district }}" placeholder="{{ __('District') }}" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <input type="number" step="0.01" min="0" name="shipping_fee" value="{{ $zone->shipping_fee }}" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                                <input type="number" step="0.01" min="0" name="free_shipping_min" value="{{ $zone->free_shipping_min }}" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <input type="number" min="0" max="365" name="delivery_days_min" value="{{ $zone->delivery_days_min }}" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                                <input type="number" min="0" max="365" name="delivery_days_max" value="{{ $zone->delivery_days_max }}" required class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                            </div>
                                                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200"><input type="checkbox" name="cash_on_delivery_enabled" value="1" @checked($zone->cash_on_delivery_enabled)> {{ __('Cash on delivery') }}</label>
                                                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200"><input type="checkbox" name="is_active" value="1" @checked($zone->is_active)> {{ __('Active') }}</label>
                                                            <textarea name="notes" rows="2" class="w-full rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">{{ $zone->notes }}</textarea>
                                                            <button class="w-full rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white">{{ __('Save') }}</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.delivery-zones.destroy', $zone) }}" class="mt-2" data-danger-confirm data-danger-title="{{ __('Delete Delivery Zone') }}" data-danger-description="{{ __('This delivery zone will be removed.') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="w-full rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900/60 dark:text-rose-300 dark:hover:bg-rose-950/20">{{ __('Delete') }}</button>
                                                        </form>
                                                    </div>
                                                </details>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">{{ __('No delivery zones found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $zones->links() }}</div>
                    </section>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
