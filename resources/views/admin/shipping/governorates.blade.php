@php
    // Keep the operator's current language prominent while retaining English as
    // a quiet reference. This is especially useful when rates are edited in bulk.
    $secondaryName = fn ($governorate) => $governorate->name === $governorate->name_en ? null : $governorate->name_en;

    $fieldClasses = 'gov-field h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 shadow-sm'
        .' focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white';

    $compactFieldClasses = 'gov-field h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-center text-sm font-bold tabular-nums text-slate-900 shadow-sm'
        .' focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white';

    $labelClasses = 'text-[10px] font-extrabold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400';

    $free = $governorates->where('shipping_fee', 0)->count();
    $average = $governorates->count() > 0 ? (int) round($governorates->avg('shipping_fee')) : 0;
    $highest = (int) $governorates->max('shipping_fee');
    $openPanel = $errors->hasAny(['name_en', 'name_ar', 'name_ku']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-1 flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[0.2em] text-accent-ink">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                    {{ __('Operations') }} / {{ __('Shipping') }}
                </div>
                <h2 class="font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Shipping by Governorate') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('Set the delivery time and shipping fee for each governorate.') }}</p>
            </div>
            <div class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/5 text-primary dark:bg-white/10 dark:text-white">
                    <i class="fas fa-map-location-dot" aria-hidden="true"></i>
                </span>
                <span class="tabular-nums">{{ $governorates->count() }}</span>
                <span>{{ __('Governorate') }}</span>
            </div>
        </div>
    </x-slot>

    <style nonce="{{ $cspNonce ?? '' }}">
        .gov-page { --gov-navy: #070740; --gov-orange: #ff6a00; }
        .gov-hero {
            position: relative; overflow: hidden;
            background:
                radial-gradient(circle at 88% 12%, rgba(255,106,0,.18), transparent 27%),
                linear-gradient(135deg, #05052d 0%, #070740 58%, #10105b 100%);
            box-shadow: 0 24px 55px -34px rgba(7,7,64,.78);
        }
        .gov-hero::before {
            content: ""; position: absolute; inset: 0; opacity: .26; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.07) 1px, transparent 1px);
            background-size: 34px 34px;
            mask-image: linear-gradient(90deg, transparent 4%, #000 46%, #000 100%);
        }
        .gov-route-line {
            position: absolute; inset-inline-end: -20px; top: -68px; width: 390px; height: 245px;
            border: 1px solid rgba(255,255,255,.13); border-radius: 50%; transform: rotate(-12deg);
        }
        .gov-route-line::before, .gov-route-line::after {
            content: ""; position: absolute; border-radius: 50%; border: 1px solid rgba(255,106,0,.23);
        }
        .gov-route-line::before { inset: 28px 48px; }
        .gov-route-line::after { inset: 58px 95px; }
        .gov-stat { background: rgba(255,255,255,.075); border: 1px solid rgba(255,255,255,.11); backdrop-filter: blur(8px); }
        .gov-card { box-shadow: 0 18px 45px -34px rgba(15,23,42,.42); }
        .gov-field { transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease; }
        .gov-field:hover:not(:focus) { border-color: #cbd5e1; }
        .gov-field:invalid:not(:placeholder-shown) { border-color: #fb7185; box-shadow: 0 0 0 3px rgba(244,63,94,.1); }
        .gov-row { position: relative; transition: background-color .16s ease, box-shadow .16s ease; }
        .gov-row:hover { background: rgba(248,250,252,.86); }
        .gov-row.is-selected { background: rgba(7,7,64,.035); box-shadow: inset 3px 0 0 rgba(7,7,64,.6); }
        [dir="rtl"] .gov-row.is-selected { box-shadow: inset -3px 0 0 rgba(7,7,64,.6); }
        .gov-row.is-dirty { background: rgba(255,106,0,.045); box-shadow: inset 3px 0 0 var(--gov-orange); }
        [dir="rtl"] .gov-row.is-dirty { box-shadow: inset -3px 0 0 var(--gov-orange); }
        .gov-row.is-dirty .gov-dirty-dot { opacity: 1; transform: scale(1); }
        .gov-row.is-free .gov-fee-shell { background: rgba(255,106,0,.045); border-color: rgba(255,106,0,.2); }
        .gov-dirty-dot { opacity: 0; transform: scale(.65); transition: opacity .16s ease, transform .16s ease; }
        .gov-checkbox { accent-color: var(--gov-orange); }
        .gov-table-head { background: linear-gradient(180deg, #f8fafc 0%, #f4f6f9 100%); }
        .gov-savebar { box-shadow: 0 -16px 34px -28px rgba(15,23,42,.55); }
        :is(.dark .gov-row:hover) { background: rgba(30,41,59,.58); }
        :is(.dark .gov-row.is-selected) { background: rgba(255,255,255,.035); }
        :is(.dark .gov-row.is-dirty) { background: rgba(255,106,0,.07); }
        :is(.dark .gov-table-head) { background: #111827; }
        @media (max-width: 639px) {
            .gov-row { margin: 12px; border: 1px solid #e2e8f0 !important; border-radius: 16px; overflow: hidden; }
            :is(.dark .gov-row) { border-color: #334155 !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            .gov-page *, .gov-page *::before, .gov-page *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>

    <div class="gov-page admin-page-enter py-6 sm:py-8">
        <div
            class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8"
            x-data="governorateShipping"
            @if($openPanel) x-init="openAdding()" @endif
        >
            @if(session('success'))
                <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-800 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200" role="status">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white"><i class="fas fa-check text-xs" aria-hidden="true"></i></span>
                    <span class="pt-1 font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-800 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200" role="alert">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white"><i class="fas fa-exclamation text-xs" aria-hidden="true"></i></span>
                    <span class="pt-1 font-semibold">{{ $errors->first() }}</span>
                </div>
            @endif

            <section class="gov-hero rounded-[24px] px-5 py-6 text-white sm:px-7 sm:py-7" aria-labelledby="shipping-overview-title">
                <div class="gov-route-line" aria-hidden="true"></div>
                <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(430px,.72fr)] lg:items-end">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[.07] px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-[0.18em] text-orange-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#ff8a3d] shadow-[0_0_0_4px_rgba(255,106,0,.12)]"></span>
                            {{ __('Shipping & Delivery') }}
                        </span>
                        <h3 id="shipping-overview-title" class="mt-4 font-display text-2xl font-bold tracking-tight sm:text-[30px]">{{ __('Shipping by Governorate') }}</h3>
                        <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">{{ __('Set the delivery time and shipping fee for each governorate.') }}</p>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
                        <div class="gov-stat rounded-2xl p-3.5 sm:p-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.18em] text-slate-400">{{ __('free') }}</p>
                            <p class="mt-2 font-display text-xl font-bold tabular-nums sm:text-2xl">{{ $free }}</p>
                        </div>
                        <div class="gov-stat rounded-2xl p-3.5 sm:p-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.18em] text-slate-400">{{ __('average') }}</p>
                            <p class="mt-2 font-display text-lg font-bold tabular-nums sm:text-xl">{{ number_format($average) }}</p>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">IQD</span>
                        </div>
                        <div class="gov-stat rounded-2xl p-3.5 sm:p-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.18em] text-slate-400">{{ __('highest') }}</p>
                            <p class="mt-2 font-display text-lg font-bold tabular-nums sm:text-xl">{{ number_format($highest) }}</p>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">IQD</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="gov-card overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-accent-ink dark:bg-orange-500/10 dark:text-orange-300">
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Add a governorate') }}</h3>
                            <p class="mt-0.5 text-xs text-muted">{{ __('For a destination that is not on the standard list.') }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:border-primary/25 hover:bg-slate-50 hover:text-primary focus:outline-none focus:ring-4 focus:ring-primary/10 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        :aria-expanded="adding ? 'true' : 'false'"
                        @click="toggleAdding()"
                    >
                        <i class="fas text-xs" :class="adding ? 'fa-xmark' : 'fa-plus'" aria-hidden="true"></i>
                        <span x-text="adding ? @js(__('Cancel')) : @js(__('New governorate'))"></span>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.shipping.governorates.store') }}" class="border-t border-slate-100 bg-slate-50/75 px-4 py-5 dark:border-slate-700 dark:bg-slate-900/40 sm:px-5" x-show="adding" x-cloak>
                    @csrf
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div><label for="new-name-en" class="mb-2 block {{ $labelClasses }}">{{ __('Name (English)') }}</label><input id="new-name-en" name="name_en" value="{{ old('name_en') }}" required maxlength="64" class="{{ $fieldClasses }}"></div>
                        <div><label for="new-name-ar" class="mb-2 block {{ $labelClasses }}">{{ __('Name (Arabic)') }}</label><input id="new-name-ar" name="name_ar" value="{{ old('name_ar') }}" required maxlength="64" dir="rtl" class="{{ $fieldClasses }}"></div>
                        <div><label for="new-name-ku" class="mb-2 block {{ $labelClasses }}">{{ __('Name (Kurdish)') }}</label><input id="new-name-ku" name="name_ku" value="{{ old('name_ku') }}" required maxlength="64" dir="rtl" class="{{ $fieldClasses }}"></div>
                    </div>
                    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="w-full sm:w-32"><label for="new-days" class="mb-2 block {{ $labelClasses }}">{{ __('Days') }}</label><input id="new-days" type="number" name="delivery_days" value="{{ old('delivery_days', 3) }}" min="1" max="60" step="1" required inputmode="numeric" class="{{ $compactFieldClasses }}"></div>
                        <div class="w-full sm:w-48"><label for="new-fee" class="mb-2 block {{ $labelClasses }}">{{ __('Fee (IQD)') }}</label><div class="relative"><input id="new-fee" type="number" name="shipping_fee" value="{{ old('shipping_fee', 0) }}" min="0" max="1000000" step="250" required inputmode="numeric" class="{{ $compactFieldClasses }} pe-12"><span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-[9px] font-extrabold uppercase tracking-wider text-slate-400">IQD</span></div></div>
                        <button type="submit" class="sm:ms-auto inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-extrabold text-white shadow-[0_10px_24px_-12px_rgba(7,7,64,.8)] transition hover:-translate-y-0.5 hover:bg-primary-hover focus:outline-none focus:ring-4 focus:ring-primary/20"><i class="fas fa-plus text-xs text-orange-300" aria-hidden="true"></i>{{ __('Add') }}</button>
                    </div>
                </form>
            </section>

            <form method="POST" action="{{ route('admin.shipping.governorates.update') }}" class="gov-card overflow-visible rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800" @submit="beginSubmit()">
                @csrf
                @method('PUT')

                <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-[0_8px_20px_-12px_rgba(7,7,64,.9)]"><i class="fas fa-route" aria-hidden="true"></i></span>
                        <div><h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Shipping Costs') }}</h3><p class="mt-0.5 text-xs text-muted"><span x-text="visibleCount"></span> / {{ $governorates->count() }} {{ __('Governorate') }}</p></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span x-show="selectedCount > 0" x-cloak class="inline-flex items-center gap-2 rounded-full bg-primary/5 px-3 py-1.5 font-bold text-primary dark:bg-white/10 dark:text-white"><span class="h-1.5 w-1.5 rounded-full bg-accent"></span><span x-text="selectedCount"></span> {{ __('selected') }}</span>
                        <button type="button" x-show="selectedCount > 0" x-cloak @click="clearSelection()" class="rounded-lg px-2 py-1.5 font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-700 dark:hover:text-white">{{ __('Deselect all') }}</button>
                    </div>
                </div>

                <div class="sticky top-[64px] z-20 border-b border-slate-200 bg-white/95 p-4 backdrop-blur-xl dark:border-slate-700 dark:bg-slate-800/95 sm:p-5">
                    <div class="grid gap-3 lg:grid-cols-[minmax(240px,1fr)_auto] lg:items-center">
                        <div class="relative">
                            <label for="gov-search" class="sr-only">{{ __('Search') }}</label>
                            <i class="fas fa-magnifying-glass pointer-events-none absolute start-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                            <input id="gov-search" type="search" x-model="search" placeholder="{{ __('Search') }}" class="gov-field h-11 w-full rounded-xl border border-slate-200 bg-slate-50 ps-10 pe-4 text-sm font-semibold text-slate-900 focus:border-accent focus:bg-white focus:outline-none focus:ring-4 focus:ring-accent/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-900" role="group" aria-label="{{ __('Shipping Fee') }}">
                                <button type="button" @click="setFeeFilter('all')" :class="feeFilter === 'all' ? 'bg-white text-primary shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="rounded-lg px-3 py-2 text-xs font-extrabold transition">{{ __('All') }}</button>
                                <button type="button" @click="setFeeFilter('free')" :class="feeFilter === 'free' ? 'bg-white text-accent-ink shadow-sm dark:bg-slate-700 dark:text-orange-300' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="rounded-lg px-3 py-2 text-xs font-extrabold transition">{{ __('free') }}</button>
                                <button type="button" @click="setFeeFilter('paid')" :class="feeFilter === 'paid' ? 'bg-white text-primary shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="rounded-lg px-3 py-2 text-xs font-extrabold transition">{{ __('Paid') }}</button>
                            </div>
                            <button type="button" x-show="hasFilters" x-cloak @click="resetFilters()" class="inline-flex h-10 items-center gap-2 rounded-xl px-3 text-xs font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-700 dark:hover:text-white"><i class="fas fa-rotate-left text-[10px]" aria-hidden="true"></i>{{ __('Reset filters') }}</button>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-700 dark:bg-slate-900/60 xl:grid-cols-[auto_140px_210px_minmax(300px,1fr)] xl:items-end">
                        <div class="flex items-center gap-3 px-1 xl:self-center"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-primary shadow-sm dark:bg-slate-800 dark:text-white"><i class="fas fa-sliders text-sm" aria-hidden="true"></i></span><div><p class="text-xs font-extrabold text-slate-900 dark:text-white">{{ __('Operations') }}</p><p class="mt-0.5 text-[10px] font-semibold text-muted"><span x-text="targetCount"></span> {{ __('Governorate') }}</p></div></div>
                        <div><label for="bulk-days" class="mb-1.5 block {{ $labelClasses }}">{{ __('Days') }} <span class="normal-case tracking-normal text-slate-400">1–60</span></label><input id="bulk-days" type="number" min="1" max="60" step="1" inputmode="numeric" x-model="bulkDays" class="{{ $compactFieldClasses }}" :class="bulkDaysInvalid && 'border-rose-400 ring-4 ring-rose-500/10'"></div>
                        <div><label for="bulk-fee" class="mb-1.5 block {{ $labelClasses }}">{{ __('Fee (IQD)') }}</label><div class="relative"><input id="bulk-fee" type="number" min="0" max="1000000" step="250" inputmode="numeric" x-model="bulkFee" class="{{ $compactFieldClasses }} pe-12" :class="bulkFeeInvalid && 'border-rose-400 ring-4 ring-rose-500/10'"><span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-[9px] font-extrabold uppercase tracking-wider text-slate-400">IQD</span></div></div>
                        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                            <button type="button" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-4 text-xs font-extrabold text-white shadow-[0_9px_20px_-13px_rgba(7,7,64,.9)] transition hover:-translate-y-px hover:bg-primary-hover focus:outline-none focus:ring-4 focus:ring-primary/15 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 sm:flex-none" :disabled="! canApplyBulk" @click="applyBulk()"><i class="fas fa-wand-magic-sparkles text-[10px] text-orange-300" aria-hidden="true"></i><span x-text="selectedCount > 0 ? @js(__('Apply to selected')) + ' (' + selectedCount + ')' : @js(__('Apply to all')) + ' (' + targetCount + ')' "></span></button>
                            <button type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-orange-200 bg-white px-3.5 text-xs font-extrabold text-accent-ink shadow-sm transition hover:border-orange-300 hover:bg-orange-50 focus:outline-none focus:ring-4 focus:ring-accent/10 disabled:cursor-not-allowed disabled:opacity-40 dark:border-orange-500/30 dark:bg-slate-800 dark:text-orange-300 dark:hover:bg-orange-500/10" :disabled="targetCount === 0" @click="makeFree()"><i class="fas fa-tag text-[10px]" aria-hidden="true"></i>{{ __('Make free') }}</button>
                            <button type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl px-3 text-xs font-bold text-slate-500 transition hover:bg-white hover:text-slate-900 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-slate-200 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-slate-800 dark:hover:text-white" :disabled="changedRows === 0" @click="revert()"><i class="fas fa-arrow-rotate-left text-[10px]" aria-hidden="true"></i>{{ __('Revert') }}</button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="gov-table-head hidden sm:table-header-group">
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th scope="col" class="w-14 px-5 py-3.5 text-start"><input type="checkbox" aria-label="{{ __('Select all') }}" class="gov-checkbox h-4 w-4 rounded border-slate-300 text-accent focus:ring-4 focus:ring-accent/15 dark:border-slate-600 dark:bg-slate-900" :checked="allVisibleSelected" @change="toggleAll()"></th>
                                <th scope="col" class="px-2 py-3.5 text-start {{ $labelClasses }}">{{ __('Governorate') }}</th>
                                <th scope="col" class="w-36 px-3 py-3.5 text-center {{ $labelClasses }}">{{ __('Delivery time (days)') }}</th>
                                <th scope="col" class="w-52 px-3 py-3.5 text-center {{ $labelClasses }}">{{ __('Shipping fee (IQD)') }}</th>
                                <th scope="col" class="w-16 px-3 py-3.5"><span class="sr-only">{{ __('Remove') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="block sm:table-row-group">
                            @foreach($governorates as $index => $governorate)
                                <tr class="gov-row block border-b border-slate-100 sm:table-row dark:border-slate-700/80" data-governorate-row="{{ $governorate->id }}" data-governorate-index="{{ $index }}" data-governorate-search="{{ $governorate->name }} {{ $governorate->name_en }} {{ $governorate->name_ar }} {{ $governorate->name_ku }}" :class="rowClass('{{ $governorate->id }}', '{{ $index }}')" x-show="matchesRow($el)">
                                    <td class="block px-4 pt-4 align-middle sm:table-cell sm:px-5 sm:py-4"><div class="flex items-center justify-between sm:block"><input type="checkbox" aria-label="{{ __('Select') }} — {{ $governorate->name }}" class="gov-checkbox h-4 w-4 rounded border-slate-300 text-accent focus:ring-4 focus:ring-accent/15 dark:border-slate-600 dark:bg-slate-900" :checked="isSelected('{{ $governorate->id }}')" @change="toggleSelection('{{ $governorate->id }}')"><span class="gov-dirty-dot inline-flex h-2 w-2 rounded-full bg-accent shadow-[0_0_0_4px_rgba(255,106,0,.1)] sm:hidden" aria-hidden="true"></span></div></td>
                                    <td class="block px-4 py-3 align-middle sm:table-cell sm:px-2 sm:py-4">
                                        <input type="hidden" name="rows[{{ $index }}][id]" value="{{ $governorate->id }}">
                                        <div class="flex items-center gap-3">
                                            <span class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xs font-extrabold tabular-nums text-slate-500 lg:flex dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                            <span class="min-w-0"><span class="flex items-center gap-2 font-extrabold text-slate-900 dark:text-white"><span class="truncate">{{ $governorate->name }}</span><span x-show="isRowFree('{{ $governorate->id }}')" class="inline-flex shrink-0 items-center gap-1 rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-[0.12em] text-accent-ink dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-300"><span class="h-1 w-1 rounded-full bg-accent"></span>{{ __('free') }}</span><span class="gov-dirty-dot hidden h-1.5 w-1.5 shrink-0 rounded-full bg-accent shadow-[0_0_0_4px_rgba(255,106,0,.1)] sm:inline-flex" aria-hidden="true"></span></span>@if($secondaryName($governorate))<span class="mt-0.5 block truncate text-xs font-medium text-muted">{{ $secondaryName($governorate) }}</span>@endif</span>
                                        </div>
                                    </td>
                                    <td class="inline-block w-[42%] px-4 pb-4 align-middle sm:table-cell sm:w-36 sm:px-3 sm:py-4 sm:text-center"><span class="mb-1.5 block {{ $labelClasses }} sm:hidden">{{ __('Days') }}</span><input type="number" name="rows[{{ $index }}][delivery_days]" value="{{ old('rows.'.$index.'.delivery_days', $governorate->delivery_days) }}" data-days-input data-original="{{ $governorate->delivery_days }}" min="1" max="60" step="1" required inputmode="numeric" aria-label="{{ __('Days') }} — {{ $governorate->name }}" class="{{ $compactFieldClasses }} mx-auto max-w-24" @input="mark('{{ $index }}-days', $event.target.value, '{{ $governorate->delivery_days }}')"></td>
                                    <td class="inline-block w-[58%] px-4 pb-4 align-middle sm:table-cell sm:w-52 sm:px-3 sm:py-4 sm:text-center"><span class="mb-1.5 block {{ $labelClasses }} sm:hidden">{{ __('Fee (IQD)') }}</span><div class="gov-fee-shell relative mx-auto max-w-40 rounded-xl border border-transparent transition-colors"><input type="number" name="rows[{{ $index }}][shipping_fee]" value="{{ old('rows.'.$index.'.shipping_fee', $governorate->shipping_fee) }}" data-fee-input data-original="{{ $governorate->shipping_fee }}" min="0" max="1000000" step="250" required inputmode="numeric" aria-label="{{ __('Fee (IQD)') }} — {{ $governorate->name }}" class="{{ $compactFieldClasses }} border-0 bg-transparent pe-11 shadow-none" @input="mark('{{ $index }}-fee', $event.target.value, '{{ $governorate->shipping_fee }}')"><span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-[9px] font-extrabold uppercase tracking-wider text-slate-400">IQD</span></div></td>
                                    <td class="hidden px-3 py-4 text-center align-middle sm:table-cell">@unless($governorate->isStandard())<button type="submit" form="remove-{{ $governorate->id }}" aria-label="{{ __('Remove') }} — {{ $governorate->name }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-4 focus:ring-rose-100 dark:hover:bg-rose-500/10"><i class="fas fa-trash text-xs" aria-hidden="true"></i></button>@endunless</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div x-show="visibleCount === 0" x-cloak class="px-6 py-16 text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-900"><i class="fas fa-map-location-dot text-lg" aria-hidden="true"></i></span><p class="mt-4 text-sm font-extrabold text-slate-900 dark:text-white">{{ __('No results found') }}</p><button type="button" @click="resetFilters()" class="mt-2 text-xs font-bold text-accent-ink transition hover:text-accent">{{ __('Reset filters') }}</button></div>
                </div>

                <div class="gov-savebar sticky bottom-0 z-20 flex flex-col gap-3 rounded-b-2xl border-t border-slate-200 bg-white/95 px-4 py-3.5 backdrop-blur-xl dark:border-slate-700 dark:bg-slate-800/95 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl" :class="changedRows > 0 ? 'bg-orange-50 text-accent-ink dark:bg-orange-500/10 dark:text-orange-300' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'"><i class="fas" :class="changedRows > 0 ? 'fa-pen' : 'fa-check'" aria-hidden="true"></i></span><div><p class="text-xs font-extrabold text-slate-900 dark:text-white" x-text="changedRows > 0 ? changedRows + ' ' + @js(__('rows changed')) : @js(__('Saved'))"></p><p class="mt-0.5 text-[10px] font-semibold text-muted" x-text="changedRows > 0 ? @js(__('Save Changes')) : @js(__('Shipping settings saved.'))"></p></div></div>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-extrabold text-white shadow-[0_10px_24px_-13px_rgba(7,7,64,.9)] transition hover:-translate-y-0.5 hover:bg-primary-hover focus:outline-none focus:ring-4 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 disabled:hover:bg-primary" :disabled="changedRows === 0"><i class="fas fa-floppy-disk text-xs text-orange-300" aria-hidden="true"></i>{{ __('Save Changes') }}</button>
                </div>
            </form>

            @foreach($governorates as $governorate)
                @unless($governorate->isStandard())
                    <form id="remove-{{ $governorate->id }}" method="POST" action="{{ route('admin.shipping.governorates.destroy', $governorate) }}" class="hidden" data-danger-confirm data-danger-title="{{ __('Remove') }} — {{ $governorate->name }}" data-danger-description="{{ __('The governorate is removed from the shipping table.') }}">@csrf @method('DELETE')</form>
                @endunless
            @endforeach
        </div>
    </div>
</x-app-layout>
