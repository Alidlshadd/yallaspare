@extends('layouts.user')

@section('content')
    <div class="space-y-4 sm:space-y-5">
        @guest
            <section class="overflow-hidden rounded-2xl border border-primary/10 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl">
                <div class="grid gap-4 p-4 sm:gap-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-600 dark:text-orange-300">{{ __('Account checkout') }}</p>
                        <h1 class="mt-2 text-xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white sm:text-2xl">{{ __('Login or create an account to order') }}</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ __('Pick a product, then sign in or create an account to place a cash-on-delivery order.') }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap lg:justify-end">
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800 sm:rounded-2xl sm:px-4 sm:py-2.5">
                            {{ __('Login') }}
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a0a55] sm:rounded-2xl sm:px-4 sm:py-2.5">
                            {{ __('Create Account') }}
                        </a>
                    </div>
                </div>
            </section>
        @endguest

        @php
            $activeCategoryModel = $categories->firstWhere('id', (int) $activeCategory);
            $activeFilterCount = collect([$search, $activeCategory, $brand, $model, $vehicle])
                ->filter(fn ($value) => filled((string) $value) && (string) $value !== '0')
                ->count();
            $clearFilterUrl = fn (array $filters) => route('shop.index', request()->except([...$filters, 'page']));
            $categoryUrl = fn ($value) => route('shop.index', array_merge(request()->except(['category', 'page']), array_filter(['category' => $value])));
            $vehicleActiveFilters = [
                ['key' => 'brand', 'label' => __('Brand'), 'value' => $brand],
                ['key' => 'model', 'label' => __('Model'), 'value' => $model],
                ['key' => 'vehicle', 'label' => __('Engine / Year'), 'value' => $vehicle],
            ];
            $sortLabels = [
                'latest' => __('Latest'),
                'price_asc' => __('Price: Low to High'),
                'price_desc' => __('Price: High to Low'),
                'stock_desc' => __('Most in stock'),
            ];
            $selectClasses = 'block w-full rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:border-primary/30 focus:bg-white focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900';
        @endphp

        <section
            x-data="{ filtersOpen: false }"
            class="z-30 rounded-2xl border border-slate-200/90 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20 sm:rounded-[1.4rem] lg:sticky lg:top-3"
        >
            <form
                action="{{ route('shop.index') }}"
                method="GET"
                data-vehicle-finder
                data-model-map='@json($modelOptionsByBrand)'
                data-model-placeholder="{{ __('All models') }}"
                data-all-models-placeholder="{{ __('Select brand first') }}"
                data-no-models-placeholder="{{ __('No models for this brand yet') }}"
            >
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategoryModel?->slug ?: $activeCategory }}">
                @endif

                <div class="flex items-center gap-2 p-3 sm:p-3.5">
                    <div class="relative min-w-0 flex-1">
                        <span class="pointer-events-none absolute inset-y-0 start-3 flex items-center text-slate-400 dark:text-slate-500">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35" />
                                <circle cx="11" cy="11" r="6" />
                            </svg>
                        </span>
                        <label for="shop-filter-search" class="sr-only">{{ __('Search catalog') }}</label>
                        <input
                            type="search"
                            id="shop-filter-search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="{{ __('Search part name, OEM number, SKU...') }}"
                            autocomplete="off"
                            class="block w-full rounded-xl border border-slate-200/80 bg-slate-50 py-2.5 pe-3 ps-10 text-sm text-slate-900 outline-none transition duration-200 placeholder:text-slate-400 focus:border-primary/30 focus:bg-white focus:ring-4 focus:ring-primary/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
                        />
                    </div>

                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 lg:hidden"
                        @click="filtersOpen = !filtersOpen"
                        :aria-expanded="filtersOpen ? 'true' : 'false'"
                        aria-controls="shop-filter-fields"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4" />
                        </svg>
                        {{ __('Filters') }}
                        @if ($activeFilterCount > 0)
                            <span class="inline-flex min-w-[1.2rem] items-center justify-center rounded-full bg-orange-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                {{ $activeFilterCount }}
                            </span>
                        @endif
                    </button>

                    <button
                        type="submit"
                        class="hidden shrink-0 items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-[#0a0a55] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary/20 lg:inline-flex"
                    >
                        {{ __('Apply filters') }}
                    </button>
                </div>

                <div
                    id="shop-filter-fields"
                    class="border-t border-slate-200/80 p-3 dark:border-slate-800 sm:p-3.5 lg:grid lg:grid-cols-[repeat(4,minmax(0,1fr))_auto] lg:items-center lg:gap-2"
                    :class="filtersOpen ? 'grid grid-cols-1 gap-2 sm:grid-cols-2' : 'hidden lg:grid'"
                >
                    <div>
                        <label for="brand" class="sr-only">{{ __('Brand') }}</label>
                        <select id="brand" name="brand" data-vehicle-brand class="{{ $selectClasses }}">
                            <option value="">{{ __('All brands') }}</option>
                            @foreach ($brandOptions as $option)
                                <option value="{{ $option }}" @selected(($brand ?? '') === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="model" class="sr-only">{{ __('Model') }}</label>
                        <select id="model" name="model" data-vehicle-model class="{{ $selectClasses }} disabled:cursor-not-allowed disabled:opacity-60">
                            <option value="">{{ __('All models') }}</option>
                            @foreach ($modelOptions as $option)
                                <option value="{{ $option }}" @selected(($model ?? '') === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="vehicle" class="sr-only">{{ __('Engine / Year') }}</label>
                        <select id="vehicle" name="vehicle" class="{{ $selectClasses }}">
                            <option value="">{{ __('Any engine / year') }}</option>
                            @foreach ($engineOptions as $option)
                                <option value="{{ $option }}" @selected(($vehicle ?? '') === (string) $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="sort" class="sr-only">{{ __('Sort') }}</label>
                        <select id="sort" name="sort" class="{{ $selectClasses }}">
                            @foreach ($sortLabels as $value => $label)
                                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-1 grid grid-cols-2 gap-2 sm:col-span-2 lg:col-span-1 lg:mt-0 lg:flex">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-[#0a0a55] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary/20 lg:hidden"
                        >
                            {{ __('Apply filters') }}
                        </button>
                        <a
                            href="{{ route('shop.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200/80 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </div>
            </form>

            @if ($categories->isNotEmpty())
                <div class="border-t border-slate-200/80 dark:border-slate-800">
                    <div class="flex gap-2 overflow-x-auto p-3 sm:p-3.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <a
                            href="{{ $categoryUrl(null) }}"
                            @class([
                                'inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition duration-200',
                                'border-primary bg-primary text-white' => ! $activeCategory,
                                'border-slate-200/80 bg-white text-slate-600 hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white' => (bool) $activeCategory,
                            ])
                        >
                            {{ __('All parts') }}
                            <span class="{{ ! $activeCategory ? 'text-white/70' : 'text-slate-400 dark:text-slate-500' }} text-[10px] font-bold">{{ number_format($categories->sum('products_count')) }}</span>
                        </a>

                        @foreach ($categories as $category)
                            <a
                                href="{{ $categoryUrl($category->slug ?: $category->id) }}"
                                @class([
                                    'inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition duration-200',
                                    'border-primary bg-primary text-white' => (int) $activeCategory === (int) $category->id,
                                    'border-slate-200/80 bg-white text-slate-600 hover:border-primary/30 hover:text-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white' => (int) $activeCategory !== (int) $category->id,
                                ])
                            >
                                {{ $category->name }}
                                <span class="{{ (int) $activeCategory === (int) $category->id ? 'text-white/70' : 'text-slate-400 dark:text-slate-500' }} text-[10px] font-bold">{{ number_format($category->products_count) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($activeFilterCount > 0)
                <div class="flex flex-wrap items-center gap-2 border-t border-slate-200/80 p-3 dark:border-slate-800 sm:p-3.5">
                    @if ($search !== '')
                        <a href="{{ $clearFilterUrl(['search', 'q']) }}" class="inline-flex max-w-full items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-300">
                            <span class="truncate">{{ __('Search') }}: {{ $search }}</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @if ($activeCategoryModel)
                        <a href="{{ $clearFilterUrl(['category']) }}" class="inline-flex max-w-full items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                            <span class="truncate">{{ $activeCategoryModel->name }}</span>
                            <span aria-hidden="true">&times;</span>
                        </a>
                    @endif
                    @foreach ($vehicleActiveFilters as $filter)
                        @if (filled($filter['value']))
                            <a href="{{ $clearFilterUrl([$filter['key']]) }}" class="inline-flex max-w-full items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
                                <span class="truncate">{{ $filter['label'] }}: {{ $filter['value'] }}</span>
                                <span aria-hidden="true">&times;</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>

        @if ($products->isEmpty())
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 text-center shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-8">
                <h2 class="text-xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white sm:text-2xl">{{ __('No products found') }}</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Try changing the filter or clearing your search.') }}</p>
            </section>
        @else
            <div class="flex flex-wrap items-center justify-between gap-2 px-1">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    <span class="font-semibold text-slate-950 dark:text-white">{{ number_format($products->total()) }}</span>
                    {{ __('Products') }}
                    @if ($activeCategoryModel)
                        · {{ $activeCategoryModel->name }}
                    @endif
                </p>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500">
                    {{ $sortLabels[$sort] ?? $sortLabels['latest'] }}
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card
                        :product="$product"
                        :compact="true"
                        :show-wishlist="true"
                        :is-wishlisted="in_array((int) $product->id, $wishlistedProductIds ?? [], true)"
                    />
                @endforeach
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection
