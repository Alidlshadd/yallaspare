<x-app-layout>
    <x-slot name="header">
        <span>{{ __('Dealers Management') }}</span>
    </x-slot>

    @php
        $visibleDealers = $dealers->getCollection();
        $visibleActiveDealers = $visibleDealers->where('dealer_status', 'active')->count();
        $visibleInactiveDealers = $visibleDealers->where('dealer_status', 'inactive')->count();
        $visibleSuspendedDealers = $visibleDealers->where('dealer_status', 'suspended')->count();
        $filteredRatio = $totalDealers > 0 ? ($dealers->total() / $totalDealers) * 100 : 0;
        $topDiscountDealer = $visibleDealers->sortByDesc('dealer_discount')->first();
        $newestDealer = $visibleDealers->sortByDesc('created_at')->first();
        $filterState = $search !== '' || $status !== '' ? __('Filtered View Active') : __('All Dealers View');
        $statusPillClass = static function (string $dealerStatus): string {
            return match ($dealerStatus) {
                'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300',
                'inactive' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-300',
                default => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300',
            };
        };
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-[92rem] space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-950">
                <div class="grid gap-5 border-b border-slate-200/80 p-5 dark:border-slate-800 lg:grid-cols-[minmax(0,1fr)_minmax(520px,0.9fr)] lg:items-end">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-cyan-700 dark:border-cyan-900/60 dark:bg-cyan-950/20 dark:text-cyan-300">{{ __('Dealer Operations') }}</span>
                            <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">{{ $filterState }}</span>
                        </div>
                        <h1 class="mt-3 text-3xl font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('Dealers Management') }}</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('Review dealers, filter the roster, and update status or discount without a cramped table.') }}</p>
                    </div>

                    <form method="GET" action="{{ route('admin.dealers.index') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_180px_auto_auto]">
                        <input
                            id="search"
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="{{ __('Search name, email, phone...') }}"
                            class="w-full rounded-xl border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        >
                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-xl border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                        >
                            <option value="">{{ __('All statuses') }}</option>
                            <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected($status === 'inactive')>{{ __('Inactive') }}</option>
                            <option value="suspended" @selected($status === 'suspended')>{{ __('Suspended') }}</option>
                        </select>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0a0a55]">
                            {{ __('Apply') }}
                        </button>
                        <a href="{{ route('admin.dealers.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            {{ __('Reset') }}
                        </a>
                    </form>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-5">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Total Dealers') }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-950 dark:text-white">{{ number_format($totalDealers) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __(':percent visible in current view', ['percent' => number_format($filteredRatio, 1) . '%']) }}</p>
                    </article>
                    <article class="rounded-2xl border border-emerald-200 bg-emerald-50/75 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ __('Active') }}</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($activeDealers) }}</p>
                        <p class="mt-1 text-xs text-emerald-700/75 dark:text-emerald-300/75">{{ __('Operational dealers') }}</p>
                    </article>
                    <article class="rounded-2xl border border-amber-200 bg-amber-50/75 p-4 dark:border-amber-900/60 dark:bg-amber-950/20">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">{{ __('Inactive') }}</p>
                        <p class="mt-2 text-3xl font-bold text-amber-700 dark:text-amber-300">{{ number_format($inactiveDealers) }}</p>
                        <p class="mt-1 text-xs text-amber-700/75 dark:text-amber-300/75">{{ __('Awaiting activation') }}</p>
                    </article>
                    <article class="rounded-2xl border border-rose-200 bg-rose-50/75 p-4 dark:border-rose-900/60 dark:bg-rose-950/20">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-700 dark:text-rose-300">{{ __('Suspended') }}</p>
                        <p class="mt-2 text-3xl font-bold text-rose-700 dark:text-rose-300">{{ number_format($suspendedDealers) }}</p>
                        <p class="mt-1 text-xs text-rose-700/75 dark:text-rose-300/75">{{ __('Blocked accounts') }}</p>
                    </article>
                    <article class="rounded-2xl border border-cyan-200 bg-cyan-50/75 p-4 dark:border-cyan-900/60 dark:bg-cyan-950/20">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-cyan-700 dark:text-cyan-300">{{ __('Average Discount') }}</p>
                        <p class="mt-2 text-3xl font-bold text-cyan-700 dark:text-cyan-300">{{ number_format($averageDiscount, 2) }}%</p>
                        <p class="mt-1 text-xs text-cyan-700/75 dark:text-cyan-300/75">{{ __('Dealer baseline') }}</p>
                    </article>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-950">
                <div class="border-b border-slate-200/80 p-5 dark:border-slate-800">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Dealer Directory') }}</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">{{ __('Manage statuses, discounts, and account posture') }}</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                {{ number_format($dealers->total()) }} {{ __('Dealers') }}
                            </span>
                            <span class="inline-flex rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700 dark:border-cyan-900/60 dark:bg-cyan-950/20 dark:text-cyan-300">
                                {{ __('Page') }} {{ $dealers->currentPage() }} / {{ $dealers->lastPage() }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Visible Average Discount') }}</p>
                            <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ number_format((float) $visibleDealers->avg('dealer_discount'), 2) }}%</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Top Discount Visible') }}</p>
                            <p class="mt-2 truncate text-lg font-bold text-slate-950 dark:text-white">{{ data_get($topDiscountDealer, 'name', __('N/A')) }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ number_format((float) data_get($topDiscountDealer, 'dealer_discount', 0), 2) }}% {{ __('rate') }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Newest Visible Dealer') }}</p>
                            <p class="mt-2 truncate text-lg font-bold text-slate-950 dark:text-white">{{ data_get($newestDealer, 'name', __('N/A')) }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ optional(data_get($newestDealer, 'created_at'))->format('d M Y') ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="hidden rounded-2xl bg-slate-50 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:bg-slate-900 dark:text-slate-400 2xl:grid 2xl:grid-cols-[minmax(190px,1fr)_minmax(210px,1fr)_110px_120px_120px_minmax(190px,0.75fr)] 2xl:items-center 2xl:gap-3">
                        <span>{{ __('Dealer') }}</span>
                        <span>{{ __('Contact') }}</span>
                        <span>{{ __('Status') }}</span>
                        <span>{{ __('Discount') }}</span>
                        <span>{{ __('Joined') }}</span>
                        <span class="text-right">{{ __('Actions') }}</span>
                    </div>

                    @forelse ($dealers as $dealer)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/5 transition hover:border-cyan-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 dark:hover:border-cyan-900/60">
                            <div class="grid gap-3 p-4 md:grid-cols-2 2xl:grid-cols-[minmax(190px,1fr)_minmax(210px,1fr)_110px_120px_120px_minmax(190px,0.75fr)] 2xl:items-center">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ strtoupper(substr((string) $dealer->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-slate-950 dark:text-white">
                                            @if (Route::has('admin.users.show') && auth()->user()?->can('manage-users'))
                                                <a href="{{ route('admin.users.show', $dealer) }}" class="inline-flex max-w-full items-center gap-1 transition hover:text-cyan-700 dark:hover:text-cyan-300">
                                                    <span class="truncate">{{ $dealer->name }}</span>
                                                    <i class="fas fa-arrow-up-right-from-square text-[11px]"></i>
                                                </a>
                                            @else
                                                {{ $dealer->name }}
                                            @endif
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Dealer ID') }} #{{ $dealer->id }}</div>
                                    </div>
                                </div>

                                <div class="min-w-0 text-sm">
                                    <div class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $dealer->email }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $dealer->phone ?: __('No phone number') }}</div>
                                </div>

                                <div>
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $statusPillClass((string) $dealer->dealer_status) }}">
                                        {{ ucfirst($dealer->dealer_status) }}
                                    </span>
                                </div>

                                <div class="inline-flex w-fit min-w-[104px] flex-col rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800">
                                    <span class="text-sm font-semibold text-slate-950 dark:text-white">{{ number_format((float) $dealer->dealer_discount, 2) }}%</span>
                                    <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400 dark:text-slate-500">{{ __('Dealer Rate') }}</span>
                                </div>

                                <div class="text-sm text-slate-600 dark:text-slate-300">
                                    <div class="font-medium">{{ $dealer->created_at?->format('d M Y') }}</div>
                                    <div class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $dealer->created_at?->diffForHumans() }}</div>
                                </div>

                                <div class="flex min-w-0 flex-wrap justify-start gap-2 md:justify-end">
                                    @if (Route::has('admin.users.show') && auth()->user()?->can('manage-users'))
                                        <a
                                            href="{{ route('admin.users.show', $dealer) }}"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-cyan-900/60 dark:hover:bg-cyan-950/20 dark:hover:text-cyan-300"
                                        >
                                            <i class="fas fa-eye text-[11px]"></i>
                                            {{ __('View') }}
                                        </a>
                                    @endif

                                    <details class="group w-full md:w-auto 2xl:w-full">
                                        <summary class="inline-flex w-full cursor-pointer list-none items-center justify-center gap-2 rounded-xl bg-primary px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-white transition hover:bg-[#0a0a55] md:w-auto 2xl:w-full">
                                            <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                                            {{ __('Manage') }}
                                        </summary>

                                        <div class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-950 md:min-w-[20rem] 2xl:min-w-0 2xl:bg-white 2xl:shadow-[0_18px_44px_rgba(15,23,42,0.12)] 2xl:dark:bg-slate-900">
                                            <div class="mb-3 flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">{{ __('Update Dealer') }}</p>
                                                    <p class="mt-1 truncate text-base font-semibold text-slate-950 dark:text-white">{{ $dealer->name }}</p>
                                                    <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $dealer->email }}</p>
                                                </div>
                                                <span class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] {{ $statusPillClass((string) $dealer->dealer_status) }}">
                                                    {{ ucfirst($dealer->dealer_status) }}
                                                </span>
                                            </div>

                                            <form method="POST" action="{{ route('admin.dealers.update', $dealer) }}" class="grid gap-3">
                                                @csrf
                                                @method('PATCH')

                                                <label class="block">
                                                    <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">{{ __('Status') }}</span>
                                                    <select name="dealer_status" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                                        <option value="active" @selected($dealer->dealer_status === 'active')>{{ __('Active') }}</option>
                                                        <option value="inactive" @selected($dealer->dealer_status === 'inactive')>{{ __('Inactive') }}</option>
                                                        <option value="suspended" @selected($dealer->dealer_status === 'suspended')>{{ __('Suspended') }}</option>
                                                    </select>
                                                </label>

                                                <label class="block">
                                                    <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">{{ __('Discount') }}</span>
                                                    <input
                                                        type="number"
                                                        name="dealer_discount"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        value="{{ old('dealer_discount', $dealer->dealer_discount) }}"
                                                        class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                                    >
                                                </label>

                                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-700">
                                                    {{ __('Save Changes') }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.dealers.demote', $dealer) }}" class="mt-3" onsubmit="return confirm('Convert this dealer to regular user?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                                    {{ __('Convert To User') }}
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                            <p class="text-base font-semibold text-slate-800 dark:text-slate-100">{{ __('No dealers found') }}</p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('Try adjusting the search query or switching the status filter.') }}</p>
                        </div>
                    @endforelse
                </div>

                @if ($dealers->hasPages())
                    <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-800">
                        {{ $dealers->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
