<x-app-layout>
    <x-slot name="header">
        <span>{{ __('Dealers Management') }}</span>
    </x-slot>

    @php
        $money = fn ($value) => number_format((float) $value, $currency['decimals']) . ' ' . $currency['label'];
        $filtered = $search !== '' || $status !== '';
        $canViewUsers = Route::has('admin.users.show') && auth()->user()?->can('manage-users');
        $statusChip = fn (string $dealerStatus) => match ($dealerStatus) {
            'active' => ['label' => __('Active'), 'class' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300'],
            'suspended' => ['label' => __('Suspended'), 'class' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300'],
            default => ['label' => __('Inactive'), 'class' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'],
        };
    @endphp

    <style>
        /* Dealers Management (dl-) */
        .dl-hero {
            background: linear-gradient(135deg, #04042a, #10104a);
            position: relative; overflow: hidden;
        }
        .dl-hero::after {
            content: ""; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 14px);
        }
        .dl-hero > * { position: relative; z-index: 1; }

        .dl-num { font-family: ui-monospace, 'JetBrains Mono', Consolas, monospace; font-variant-numeric: tabular-nums; }

        .dl-avatar {
            width: 38px; height: 38px; border-radius: 11px; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            background: #04042a; color: #fbbf24; font-weight: 900; font-size: 15px;
        }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 px-4 sm:px-6 lg:px-8" x-data="dealerBoard">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-700 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- ============ navy command header ============ --}}
            <section class="dl-hero rounded-3xl p-6 text-white shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-amber-400">{{ __('Dealer Operations') }}</p>
                        <h1 class="mt-1.5 text-2xl font-black tracking-tight sm:text-3xl">
                            <span class="dl-num">{{ number_format($totalDealers) }}</span> {{ __('dealers') }}
                            <span class="text-white/40">·</span>
                            <span class="dl-num text-amber-400">{{ $money($dealerRevenue) }}</span>
                            <span class="text-base font-bold text-white/60">{{ __('dealer revenue') }}</span>
                        </h1>
                        <p class="mt-1.5 max-w-2xl text-sm text-white/60">{{ __('Review dealers, filter the roster, and update status or discount from the quick-edit drawer.') }}</p>
                    </div>
                    <span class="inline-flex shrink-0 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-bold text-white/70">
                        {{ $filtered ? __('Filtered View Active') : __('All Dealers View') }}
                    </span>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-x-8 gap-y-4 sm:grid-cols-3 lg:flex lg:flex-wrap lg:gap-10">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Active') }}</p>
                        <p class="dl-num mt-0.5 text-xl font-black text-emerald-300">{{ number_format($activeDealers) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Inactive') }}</p>
                        <p class="dl-num mt-0.5 text-xl font-black text-slate-300">{{ number_format($inactiveDealers) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Suspended') }}</p>
                        <p class="dl-num mt-0.5 text-xl font-black text-rose-300">{{ number_format($suspendedDealers) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Average Discount') }}</p>
                        <p class="dl-num mt-0.5 text-xl font-black text-amber-400">{{ number_format($averageDiscount, 2) }}%</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-white/45">{{ __('Dealer Orders') }}</p>
                        <p class="dl-num mt-0.5 text-xl font-black">{{ number_format($dealerOrdersTotal) }}</p>
                    </div>
                </div>
            </section>

            {{-- ============ filters ============ --}}
            <form method="GET" action="{{ route('admin.dealers.index') }}" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        id="search"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('Search name, email, phone...') }}"
                        class="min-w-0 flex-[2_1_240px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    >
                    <select id="status" name="status" class="min-w-0 flex-[1_1_150px] rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">{{ __('All statuses') }}</option>
                        <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected($status === 'inactive')>{{ __('Inactive') }}</option>
                        <option value="suspended" @selected($status === 'suspended')>{{ __('Suspended') }}</option>
                    </select>
                    <a href="{{ route('admin.dealers.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Reset') }}</a>
                    <button type="submit" class="rounded-xl bg-[#04042a] px-4 py-2 text-sm font-semibold text-white hover:bg-[#10104a]">{{ __('Apply') }}</button>
                </div>
            </form>

            {{-- ============ dealer roster ============ --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                    <i class="fas fa-handshake text-slate-400"></i>
                    <h3 class="text-sm font-extrabold text-slate-800 dark:text-slate-100">{{ __('Dealer Directory') }}</h3>
                    <span class="ms-auto text-xs font-semibold text-slate-400">
                        {{ __(':from–:to of :total', ['from' => $dealers->firstItem() ?? 0, 'to' => $dealers->lastItem() ?? 0, 'total' => $dealers->total()]) }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">{{ __('Dealer') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Contact') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Discount') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Orders') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Revenue') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('Joined') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($dealers as $dealer)
                                @php
                                    $chip = $statusChip((string) $dealer->dealer_status);
                                    $rowDealer = [
                                        'id' => $dealer->id,
                                        'initial' => strtoupper(substr((string) $dealer->name, 0, 1)),
                                        'name' => $dealer->name,
                                        'meta' => __('Dealer ID') . ' #' . $dealer->id . ' · ' . __('Joined') . ' ' . ($dealer->created_at?->format('d M Y') ?? '—'),
                                        'email' => $dealer->email,
                                        'phone' => $dealer->phone ?: __('No phone number'),
                                        'status' => $dealer->dealer_status,
                                        'discount' => (float) $dealer->dealer_discount,
                                        'orders' => number_format((int) $dealer->orders_count),
                                        'revenue' => $money((float) $dealer->paid_revenue),
                                        'statusLabel' => $chip['label'],
                                        'statusClass' => $chip['class'],
                                        'updateUrl' => route('admin.dealers.update', $dealer),
                                        'demoteUrl' => route('admin.dealers.demote', $dealer),
                                        'viewUrl' => $canViewUsers ? route('admin.users.show', $dealer) : '',
                                    ];
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40" data-dealer="{{ json_encode($rowDealer) }}">
                                    <td class="px-4 py-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="dl-avatar">{{ strtoupper(substr((string) $dealer->name, 0, 1)) }}</span>
                                            <div class="min-w-0">
                                                <div class="truncate font-semibold text-slate-900 dark:text-slate-100">
                                                    @if ($canViewUsers)
                                                        <a href="{{ route('admin.users.show', $dealer) }}" class="hover:text-amber-700 dark:hover:text-amber-400">{{ $dealer->name }}</a>
                                                    @else
                                                        {{ $dealer->name }}
                                                    @endif
                                                </div>
                                                <div class="mt-0.5 text-xs text-slate-400">#{{ $dealer->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="truncate text-slate-700 dark:text-slate-200">{{ $dealer->email }}</div>
                                        <div class="mt-0.5 text-xs text-slate-400">{{ $dealer->phone ?: __('No phone number') }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $chip['class'] }}">{{ $chip['label'] }}</span>
                                    </td>
                                    <td class="dl-num px-4 py-3 text-right font-bold text-slate-900 dark:text-slate-100">{{ number_format((float) $dealer->dealer_discount, 2) }}%</td>
                                    <td class="dl-num px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format((int) $dealer->orders_count) }}</td>
                                    <td class="dl-num px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $money((float) $dealer->paid_revenue) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="text-slate-600 dark:text-slate-300">{{ $dealer->created_at?->format('d M Y') }}</div>
                                        <div class="mt-0.5 text-xs text-slate-400">{{ $dealer->created_at?->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if ($canViewUsers)
                                                <a href="{{ route('admin.users.show', $dealer) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-xs text-slate-500 hover:border-slate-400 hover:text-slate-800 dark:border-slate-700 dark:text-slate-400 dark:hover:text-slate-100" title="{{ __('View dealer') }}" aria-label="{{ __('View dealer') }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endif
                                            <button type="button" class="rounded-lg bg-[#04042a] px-3 py-1.5 text-[11px] font-bold text-white hover:bg-[#10104a]" @click="openDrawer">
                                                {{ __('Manage') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center">
                                        <i class="fas fa-handshake-slash mb-3 block text-2xl text-slate-300 dark:text-slate-600"></i>
                                        <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('No dealers found') }}</p>
                                        <p class="mt-1 text-xs text-slate-400">{{ __('Try adjusting the search query or switching the status filter. Dealers are promoted from user accounts.') }}</p>
                                        <div class="mt-4 flex justify-center gap-2">
                                            <a href="{{ route('admin.dealers.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Reset filters') }}</a>
                                            @if (Route::has('admin.users.index') && auth()->user()?->can('manage-users'))
                                                <a href="{{ route('admin.users.index') }}" class="rounded-xl bg-[#04042a] px-4 py-2 text-xs font-bold text-white hover:bg-[#10104a]">{{ __('Go to Users') }}</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($dealers->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                        {{ $dealers->links() }}
                    </div>
                @endif
            </section>

            {{-- ============ performance strip ============ --}}
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => __('Top by Orders'), 'icon' => 'fa-trophy', 'dealer' => $performance['top_orders'], 'value' => $performance['top_orders'] ? number_format((int) $performance['top_orders']->orders_count) . ' ' . __('orders') : null],
                    ['label' => __('Top by Revenue'), 'icon' => 'fa-sack-dollar', 'dealer' => $performance['top_revenue'], 'value' => $performance['top_revenue'] ? $money((float) $performance['top_revenue']->paid_revenue) : null],
                    ['label' => __('Newest Dealer'), 'icon' => 'fa-user-plus', 'dealer' => $performance['newest'], 'value' => $performance['newest']?->created_at?->format('d M Y')],
                    ['label' => __('Highest Discount'), 'icon' => 'fa-percent', 'dealer' => $performance['top_discount'], 'value' => $performance['top_discount'] ? number_format((float) $performance['top_discount']->dealer_discount, 2) . '%' : null],
                ] as $tile)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.13em] text-slate-400">{{ $tile['label'] }}</p>
                            <i class="fas {{ $tile['icon'] }} text-amber-500"></i>
                        </div>
                        @if ($tile['dealer'])
                            <p class="mt-2 truncate text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ $tile['dealer']->name }}</p>
                            <p class="dl-num mt-0.5 text-sm font-black text-amber-700 dark:text-amber-400">{{ $tile['value'] }}</p>
                        @else
                            <p class="mt-2 text-sm font-semibold text-slate-400">{{ __('No dealer data yet') }}</p>
                        @endif
                    </article>
                @endforeach
            </section>

            {{-- ============ quick-edit drawer ============ --}}
            <div class="fixed inset-0 z-50 bg-[#04042a]/55" x-show="drawerOpen" x-cloak @click.self="closeDrawer" role="dialog" aria-modal="true">
                <aside class="absolute end-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl dark:bg-slate-900">
                    <div class="dl-hero flex items-start justify-between gap-3 px-5 py-4 text-white">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="dl-avatar" style="background: rgba(251,191,36,0.15);" x-text="drawerInitial"></span>
                            <div class="min-w-0">
                                <h3 class="truncate text-[15px] font-extrabold" x-text="drawerName"></h3>
                                <p class="text-[11px] text-white/60" x-text="drawerMeta"></p>
                            </div>
                        </div>
                        <button type="button" class="shrink-0 px-1 text-lg" @click="closeDrawer" aria-label="{{ __('Close') }}">✕</button>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                        {{-- contact --}}
                        <div class="rounded-xl border border-slate-200 p-3.5 dark:border-slate-700">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.13em] text-slate-400">{{ __('Contact') }}</p>
                            <p class="mt-1.5 truncate text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="drawerEmail"></p>
                            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400" x-text="drawerPhone"></p>
                        </div>

                        {{-- performance --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-slate-200 p-3.5 dark:border-slate-700">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.13em] text-slate-400">{{ __('Orders') }}</p>
                                <p class="dl-num mt-1 text-lg font-black text-slate-900 dark:text-slate-100" x-text="drawerOrdersLabel"></p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-3.5 dark:border-slate-700">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.13em] text-slate-400">{{ __('Revenue') }}</p>
                                <p class="dl-num mt-1 truncate text-lg font-black text-amber-700 dark:text-amber-400" x-text="drawerRevenueLabel"></p>
                            </div>
                        </div>

                        {{-- update form (existing route) --}}
                        <form method="POST" :action="drawerUpdateUrl" class="space-y-3 rounded-xl border border-slate-200 p-3.5 dark:border-slate-700">
                            @csrf
                            @method('PATCH')
                            <div class="flex items-center justify-between">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.13em] text-slate-400">{{ __('Update Dealer') }}</p>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide" :class="drawerStatusClass" x-text="drawerStatusLabel"></span>
                            </div>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Status') }}</span>
                                <select name="dealer_status" x-model="form.status" class="w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="inactive">{{ __('Inactive') }}</option>
                                    <option value="suspended">{{ __('Suspended') }}</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('Discount') }} (%)</span>
                                <input type="number" name="dealer_discount" x-model="form.discount" min="0" max="100" step="0.01" class="dl-num w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-2 focus:ring-amber-400/25 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </label>
                            <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-emerald-500">
                                {{ __('Save Changes') }}
                            </button>
                        </form>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 px-5 py-3.5 dark:border-slate-800">
                        <a x-show="drawerHasView" :href="drawerViewUrl" class="rounded-lg border border-slate-200 px-3.5 py-2 text-xs font-extrabold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <i class="fas fa-eye me-1"></i>{{ __('View profile') }}
                        </a>
                        <form method="POST" :action="drawerDemoteUrl" class="ms-auto" data-confirm="{{ __('Convert this dealer to regular user?') }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg border border-rose-200 px-3.5 py-2 text-xs font-extrabold text-rose-600 hover:bg-rose-50 dark:border-rose-900/60 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                {{ __('Convert To User') }}
                            </button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
