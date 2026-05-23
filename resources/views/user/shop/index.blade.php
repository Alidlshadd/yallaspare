@extends('layouts.user')

@section('content')
    <div class="space-y-4 sm:space-y-5">
        @guest
            <section class="overflow-hidden rounded-2xl border border-[#070740]/10 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl">
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
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-[#070740] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#0a0a55] sm:rounded-2xl sm:px-4 sm:py-2.5">
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
        @endphp

        <section class="grid gap-4 lg:gap-5 xl:grid-cols-[22rem_minmax(0,1fr)] xl:items-start">
            <aside class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/20 sm:rounded-[1.6rem] xl:sticky xl:top-4 xl:max-h-[calc(100vh-2rem)] xl:self-start">
                <div class="border-b border-slate-200/80 bg-white p-3.5 dark:border-slate-800 dark:bg-slate-900 sm:p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-orange-600 dark:text-orange-300">{{ __('Filters') }}</p>
                            <h1 class="mt-1 text-lg font-semibold tracking-[-0.02em] text-slate-950 dark:text-white sm:text-xl">{{ __('Find the right part') }}</h1>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full border border-orange-200 bg-orange-50 px-2.5 py-1 text-[11px] font-bold text-orange-700 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300">
                            {{ trans_choice(':count filter|:count filters', $activeFilterCount, ['count' => $activeFilterCount]) }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Pick a category first, then use vehicle fitment or sorting only when needed.') }}</p>

                    @if ($activeFilterCount > 0)
                        <div class="mt-4 flex flex-wrap gap-2">
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
                </div>

                <form
                    action="{{ route('shop.index') }}"
                    method="GET"
                    class="flex min-h-0 flex-col"
                    data-vehicle-finder
                    data-model-map='@json($modelOptionsByBrand)'
                    data-model-placeholder="{{ __('All models') }}"
                    data-all-models-placeholder="{{ __('Select brand first') }}"
                    data-no-models-placeholder="{{ __('No models for this brand yet') }}"
                >
                    <div class="min-h-0 flex-1 divide-y divide-slate-200/80 overflow-visible dark:divide-slate-800 xl:overflow-y-auto">
                    <div class="space-y-3 p-3.5 sm:p-4">
                        <div>
                            <h2 class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Vehicle fitment') }}</h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Brand, model, engine/year') }}</p>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label for="brand" class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Brand') }}</label>
                                <select id="brand" name="brand" data-vehicle-brand class="block w-full rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/30 focus:bg-white focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900">
                                    <option value="">{{ __('All brands') }}</option>
                                    @foreach ($brandOptions as $option)
                                        <option value="{{ $option }}" @selected(($brand ?? '') === (string) $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="model" class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Model') }}</label>
                                <select id="model" name="model" data-vehicle-model class="block w-full rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/30 focus:bg-white focus:ring-4 focus:ring-[#070740]/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900">
                                    <option value="">{{ __('All models') }}</option>
                                    @foreach ($modelOptions as $option)
                                        <option value="{{ $option }}" @selected(($model ?? '') === (string) $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="vehicle" class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">{{ __('Engine / Year') }}</label>
                                <select id="vehicle" name="vehicle" class="block w-full rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/30 focus:bg-white focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900">
                                    <option value="">{{ __('Any engine / year') }}</option>
                                    @foreach ($engineOptions as $option)
                                        <option value="{{ $option }}" @selected(($vehicle ?? '') === (string) $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 p-3.5 sm:p-4">
                        <div>
                            <h2 class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Sort') }}</h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Choose product order') }}</p>
                        </div>

                        <select
                            id="sort"
                            name="sort"
                            class="block w-full rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/30 focus:bg-white focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900"
                        >
                            @foreach ($sortLabels as $value => $label)
                                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    </div>

                    <div class="grid grid-cols-2 gap-2 border-t border-slate-200/80 bg-white p-3.5 dark:border-slate-800 dark:bg-slate-900 sm:p-4">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-[#070740] px-3 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-[#0a0a55] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#070740]/20 sm:px-4 sm:py-3"
                        >
                            {{ __('Apply filters') }}
                        </button>
                        <a
                            href="{{ route('shop.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 sm:px-4 sm:py-3"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </aside>

            <div class="space-y-4">
                @if ($products->isEmpty())
                    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 text-center shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-8">
                        <h2 class="text-xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white sm:text-2xl">{{ __('No products found') }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Try changing the filter or clearing your search.') }}</p>
                    </section>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                        @foreach ($products as $product)
                            <x-product-card
                                :product="$product"
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
        </section>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-vehicle-finder]').forEach((form) => {
                const brandSelect = form.querySelector('[data-vehicle-brand]');
                const modelSelect = form.querySelector('[data-vehicle-model]');

                if (!brandSelect || !modelSelect) {
                    return;
                }

                const modelMap = JSON.parse(form.dataset.modelMap || '{}');
                const selectedModel = modelSelect.value;
                const modelPlaceholder = form.dataset.modelPlaceholder || 'All models';
                const allModelsPlaceholder = form.dataset.allModelsPlaceholder || 'Select brand first';
                const noModelsPlaceholder = form.dataset.noModelsPlaceholder || 'No models for this brand yet';
                const hasStructuredModels = Object.keys(modelMap).length > 0;

                const setOptions = () => {
                    if (!hasStructuredModels) {
                        return;
                    }

                    const brand = brandSelect.value;
                    const models = brand ? (modelMap[brand] || []) : [];
                    modelSelect.innerHTML = '';

                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = brand ? modelPlaceholder : allModelsPlaceholder;
                    modelSelect.appendChild(placeholder);

                    models.forEach((model) => {
                        const option = document.createElement('option');
                        option.value = model;
                        option.textContent = model;
                        option.selected = model === selectedModel;
                        modelSelect.appendChild(option);
                    });

                    modelSelect.disabled = brand === '' || models.length === 0;
                    if (brand !== '' && models.length === 0) {
                        placeholder.textContent = noModelsPlaceholder;
                    }
                };

                brandSelect.addEventListener('change', () => {
                    modelSelect.value = '';
                    setOptions();
                });

                setOptions();
            });
        </script>
    @endpush
@endsection
