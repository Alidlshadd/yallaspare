@extends('layouts.user')

@section('content')
    @php
        $heroVideoPath = trim((string) data_get($heroSettings ?? [], 'video', '')) ?: 'home/hero-video.mp4';
        $heroImagePath = trim((string) data_get($heroSettings ?? [], 'image', '')) ?: 'home/hero-banner.jpg';
        $heroVideoUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($heroVideoPath)
            ? asset('storage/' . $heroVideoPath)
            : null;
        $heroImageUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($heroImagePath)
            ? asset('storage/' . $heroImagePath)
            : null;
        $heroTitle = trim((string) data_get($heroSettings ?? [], 'title', '')) ?: __('Find the right spare parts faster');
        $heroSubtitle = trim((string) data_get($heroSettings ?? [], 'subtitle', '')) ?: __('Browse saved categories, filter by vehicle, and shop available parts from one clean catalog.');
        $heroButtonLabel = trim((string) data_get($heroSettings ?? [], 'button_label', '')) ?: __('Shop now');
        $heroButtonUrl = trim((string) data_get($heroSettings ?? [], 'button_url', '')) ?: route('shop.index');
    @endphp

    <div class="space-y-6 sm:space-y-8 lg:space-y-10">
        <section class="mx-auto w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-950 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:shadow-black/10 sm:rounded-3xl">
            <div class="relative min-h-[210px] overflow-hidden sm:min-h-[280px] lg:min-h-[360px]">
                @if ($heroVideoUrl)
                    <video
                        class="h-full w-full object-cover"
                        autoplay
                        muted
                        loop
                        playsinline
                        @if ($heroImageUrl) poster="{{ $heroImageUrl }}" @endif
                    >
                        <source src="{{ $heroVideoUrl }}" type="video/mp4">
                    </video>
                @elseif ($heroImageUrl)
                    <img src="{{ $heroImageUrl }}" alt="{{ __('Auto parts banner') }}" class="h-full w-full object-cover">
                @else
                    <div class="h-full w-full bg-[linear-gradient(135deg,#070740_0%,#111827_52%,#1f2937_100%)]"></div>
                @endif

                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/85 via-slate-950/45 to-slate-950/10"></div>

                <div class="absolute inset-0 flex items-center">
                    <div class="max-w-2xl px-4 py-6 sm:px-8 lg:px-10">
                        <h1 class="text-xl font-semibold tracking-[-0.03em] text-white sm:text-2xl lg:text-3xl">
                            {{ $heroTitle }}
                        </h1>
                        <p class="mt-2 max-w-xl text-xs leading-5 text-slate-200 sm:mt-3 sm:text-sm sm:leading-6 lg:text-base">
                            {{ $heroSubtitle }}
                        </p>
                        <a
                            href="{{ $heroButtonUrl }}"
                            class="mt-4 inline-flex items-center justify-center rounded-xl bg-white px-3.5 py-2 text-xs font-semibold text-[#070740] transition duration-200 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/50 sm:mt-5 sm:rounded-2xl sm:px-4 sm:py-2.5 sm:text-sm"
                        >
                            {{ $heroButtonLabel }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section id="vehicle-finder" class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6">
            <div class="flex flex-col gap-2">
                <h2 class="text-lg font-semibold text-slate-950 dark:text-white sm:text-xl">{{ __('Vehicle Finder') }}</h2>
                <p class="text-xs leading-5 text-slate-500 dark:text-slate-400 sm:text-sm">{{ __('Pick your vehicle once and browse matching parts faster.') }}</p>
            </div>

            <form
                method="GET"
                action="{{ route('shop.index') }}"
                class="mt-4 grid grid-cols-1 gap-2.5 sm:mt-6 sm:gap-3 md:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto]"
                data-vehicle-finder
                data-model-map='@json($modelOptionsByBrand)'
                data-model-placeholder="{{ __('Model') }}"
                data-all-models-placeholder="{{ __('Select brand first') }}"
                data-no-models-placeholder="{{ __('No models for this brand yet') }}"
            >
                <select
                    name="brand"
                    data-vehicle-brand
                    class="w-full rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/20 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:border-[#070740]/30 dark:focus:ring-[#070740]/10 sm:rounded-2xl sm:px-4 sm:py-3"
                >
                    <option value="">{{ __('Brand') }}</option>
                    @foreach ($brandOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>

                <select
                    name="model"
                    data-vehicle-model
                    class="w-full rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/20 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:border-[#070740]/30 dark:focus:ring-[#070740]/10 sm:rounded-2xl sm:px-4 sm:py-3"
                >
                    <option value="">{{ __('Model') }}</option>
                    @foreach ($modelOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>

                <select
                    name="vehicle"
                    class="w-full rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition duration-200 focus:border-[#070740]/20 focus:ring-4 focus:ring-[#070740]/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white dark:focus:border-[#070740]/30 dark:focus:ring-[#070740]/10 sm:rounded-2xl sm:px-4 sm:py-3"
                >
                    <option value="">{{ __('Engine / Year') }}</option>
                    @foreach ($engineOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-[#070740] px-4 py-2.5 text-sm font-medium text-white transition duration-200 hover:bg-[#0a0a55] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#070740]/20 sm:rounded-2xl sm:px-5 sm:py-3 md:col-span-2 lg:col-span-1"
                >
                    {{ __('Find parts') }}
                </button>
            </form>
        </section>

        <section class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('Browse Categories') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Start from the system you need and narrow down quickly.') }}</p>
                </div>
                <a
                    href="{{ route('categories.index') }}"
                    class="inline-flex items-center rounded-full px-3 py-2 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-[#070740]/30"
                >
                    {{ __('View catalog') }}
                </a>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-5">
                @forelse ($categories as $category)
                    <a
                        href="{{ route('shop.index', ['category' => data_get($category, 'slug') ?: data_get($category, 'id')]) }}"
                        class="group flex min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 transition duration-200 hover:-translate-y-0.5 hover:border-[#070740]/20 hover:shadow-md hover:shadow-slate-900/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 dark:hover:border-[#070740]/30 dark:hover:shadow-black/20 sm:min-h-56 sm:rounded-3xl"
                    >
                        <div class="flex h-28 items-center justify-center overflow-hidden bg-slate-100 p-1.5 text-[#070740] transition duration-200 group-hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:group-hover:bg-slate-700 sm:h-36">
                            @if (data_get($category, 'image'))
                                <img src="{{ data_get($category, 'image') }}" alt="{{ data_get($category, 'name') }}" class="h-full w-full scale-[1.08] object-contain transition duration-300 group-hover:scale-[1.1]" loading="lazy">
                            @else
                                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6M9 14h4" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ data_get($category, 'name') }}</p>
                            @if (filled(data_get($category, 'description')))
                                <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ data_get($category, 'description') }}</p>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 sm:col-span-2 lg:col-span-5">
                        {{ __('No categories found.') }}
                    </div>
                @endforelse
            </div>
        </section>

        <section class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('Popular Right Now') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Fast-moving essentials selected from current inventory.') }}</p>
                </div>
                <a
                    href="{{ route('shop.index') }}"
                    class="inline-flex items-center rounded-full px-3 py-2 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-[#070740]/30"
                >
                    {{ __('View catalog') }}
                </a>
            </div>

            <div class="grid auto-rows-fr grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    @php
                        $models = collect(data_get($product, 'compatible_models', []))->values();
                        $visibleModels = $models->take(2);
                        $extraCount = max(0, $models->count() - 2);
                        $wishlistCount = (int) data_get($product, 'wishlist_count', 0);
                        $isWishlisted = in_array((int) data_get($product, 'id', 0), $wishlistedProductIds ?? [], true);
                        $hasDiscount = (bool) data_get($product, 'has_discount', false);
                        $discountPercent = (int) data_get($product, 'discount_percent', 0);
                        $discountAmount = (float) data_get($product, 'discount_amount', 0);
                    @endphp
                    <article class="flex h-full min-h-full flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 transition duration-200 hover:-translate-y-0.5 hover:border-[#070740]/20 hover:shadow-lg hover:shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 dark:hover:border-[#070740]/30 dark:hover:shadow-black/20">
                        <div class="relative h-52 overflow-hidden bg-slate-100 p-4 dark:bg-slate-800/80">
                            @if ($hasDiscount)
                                <span class="absolute left-3 top-3 z-10 inline-flex rounded-full bg-rose-600 px-3 py-1 text-xs font-bold text-white shadow-sm">
                                    -{{ $discountPercent }}%
                                </span>
                            @endif
                            @auth
                                @php
                                    $productId = (int) data_get($product, 'id');
                                    $storeUrl = route('user.wishlist.store', $productId);
                                    $destroyUrl = route('user.wishlist.destroy', $productId);
                                @endphp
                                <div class="absolute right-3 top-3 z-10">
                                    <form
                                        method="POST"
                                        action="{{ $isWishlisted ? $destroyUrl : $storeUrl }}"
                                        class="js-wishlist-form"
                                        data-wishlisted="{{ $isWishlisted ? '1' : '0' }}"
                                        data-store-url="{{ $storeUrl }}"
                                        data-destroy-url="{{ $destroyUrl }}"
                                    >
                                        @csrf
                                        @if ($isWishlisted)
                                            @method('DELETE')
                                        @endif
                                        <button
                                            type="submit"
                                            class="js-wishlist-button inline-flex items-center justify-center rounded-full border bg-white/95 p-1.5 text-[11px] font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 dark:bg-slate-900/95 {{ $isWishlisted ? 'border-rose-200 text-rose-700 hover:bg-rose-50 focus-visible:ring-rose-300 dark:border-rose-900/60 dark:text-rose-300' : 'border-slate-200 text-slate-500 hover:border-[#070740]/30 hover:text-[#070740] focus-visible:ring-[#070740]/20 dark:border-slate-700 dark:text-slate-400' }}"
                                            aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="m12 20.25-1.45-1.32C5.4 14.36 2.25 11.5 2.25 7.97c0-2.48 1.95-4.47 4.43-4.47 1.4 0 2.75.65 3.57 1.66.82-1.01 2.17-1.66 3.57-1.66 2.48 0 4.43 1.99 4.43 4.47 0 3.53-3.15 6.39-8.3 10.96L12 20.25Z" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="absolute right-3 top-3 inline-flex items-center justify-center rounded-full border bg-white/95 p-1.5 text-[11px] font-semibold shadow-sm dark:bg-slate-900/95 {{ $wishlistCount > 0 ? 'border-rose-200 text-rose-700 dark:border-rose-900/60 dark:text-rose-300' : 'border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400' }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="m12 20.25-1.45-1.32C5.4 14.36 2.25 11.5 2.25 7.97c0-2.48 1.95-4.47 4.43-4.47 1.4 0 2.75.65 3.57 1.66.82-1.01 2.17-1.66 3.57-1.66 2.48 0 4.43 1.99 4.43 4.47 0 3.53-3.15 6.39-8.3 10.96L12 20.25Z" />
                                    </svg>
                                </span>
                            @endauth
                            @if (data_get($product, 'image'))
                                <a href="{{ data_get($product, 'detail_url') }}" class="block h-full w-full">
                                    <img src="{{ data_get($product, 'image') }}" alt="{{ data_get($product, 'name') }}" class="h-full w-full object-contain" loading="lazy">
                                </a>
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
                                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16 9 11l4 4 3-3 4 4" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16" />
                                        <circle cx="9" cy="8" r="1.5" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col space-y-4 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ data_get($product, 'detail_url') }}" class="line-clamp-2 block min-h-[3rem] text-base font-semibold leading-6 text-slate-950 transition hover:text-[#070740] dark:text-white dark:hover:text-slate-200">
                                        {{ data_get($product, 'name') }}
                                    </a>
                                    <div class="mt-2 space-y-1">
                                        <div class="flex flex-wrap items-end gap-2">
                                            <p class="text-xl font-bold tracking-[-0.02em] text-[#070740] dark:text-white">
                                                {{ number_format((float) data_get($product, 'price', 0), 0) }} {{ $currencySymbol }}
                                            </p>
                                            @if ($hasDiscount)
                                                <p class="text-sm font-semibold text-slate-400 line-through dark:text-slate-500">
                                                    {{ number_format((float) data_get($product, 'base_price', 0), 0) }}
                                                </p>
                                            @endif
                                        </div>
                                        @if ($hasDiscount)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                {{ __('Save') }} {{ number_format($discountAmount, 0) }} {{ $currencySymbol }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if ((int) data_get($product, 'stock_quantity', 0) > 0)
                                    <span class="inline-flex shrink-0 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-500/10 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        {{ __('In stock') }}
                                    </span>
                                @else
                                    <span class="inline-flex shrink-0 rounded-full border border-rose-100 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:border-rose-500/10 dark:bg-rose-500/10 dark:text-rose-300">
                                        {{ __('Out of stock') }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex min-h-[3.25rem] flex-wrap content-start gap-2">
                                @foreach ($visibleModels as $model)
                                    <span class="inline-flex rounded-full border border-slate-200/80 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $model }}
                                    </span>
                                @endforeach
                                @if ($extraCount > 0)
                                    <span class="inline-flex rounded-full border border-slate-200/80 bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        +{{ $extraCount }} more
                                    </span>
                                @endif
                            </div>

                            <div class="mt-auto grid grid-cols-2 items-stretch gap-3">
                                <a
                                    href="{{ data_get($product, 'detail_url') }}"
                                    class="inline-flex h-full items-center justify-center rounded-2xl border border-slate-200/80 px-4 py-3 text-sm font-medium text-slate-700 transition duration-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:border-slate-800 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-[#070740]/30"
                                >
                                    {{ __('View') }}
                                </a>
                                <form method="POST" action="{{ route('cart.add', (int) data_get($product, 'id')) }}" class="js-add-cart-form h-full">
                                    @csrf
                                    <button
                                        type="submit"
                                        @disabled((int) data_get($product, 'stock_quantity', 0) <= 0)
                                        class="js-add-cart-button inline-flex h-full w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-medium transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 {{ (int) data_get($product, 'stock_quantity', 0) > 0 ? 'bg-[#070740] text-white hover:bg-[#0a0a55]' : 'cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500' }}"
                                    >
                                        {{ __('Add to Cart') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
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
                const modelPlaceholder = form.dataset.modelPlaceholder || 'Model';
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

@push('scripts')
    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) return;
            const wishlistBadges = () => Array.from(document.querySelectorAll('[data-wishlist-count-badge]'));
            const cartBadges = () => Array.from(document.querySelectorAll('[data-cart-count-badge]'));
            const cartItemsLabels = () => Array.from(document.querySelectorAll('[data-cart-items-label]'));
            const cartRefs = () => Array.from(document.querySelectorAll('[data-cart-ref]'));
            const cartTotals = () => Array.from(document.querySelectorAll('[data-cart-total]'));
            const currentWishlistCount = () => {
                const badge = wishlistBadges()[0];
                if (!badge) return 0;
                const raw = Number.parseInt(badge.dataset.wishlistCountValue || '0', 10);
                return Number.isNaN(raw) ? 0 : Math.max(0, raw);
            };
            const currentCartCount = () => {
                const badge = cartBadges()[0];
                if (!badge) return 0;
                const raw = Number.parseInt(badge.dataset.cartCountValue || '0', 10);
                return Number.isNaN(raw) ? 0 : Math.max(0, raw);
            };
            const setWishlistCount = (count) => {
                const normalized = Math.max(0, count);
                wishlistBadges().forEach((badge) => {
                    badge.dataset.wishlistCountValue = String(normalized);
                    badge.textContent = normalized > 99 ? '99+' : String(normalized);
                });
            };
            const setCartCount = (count) => {
                const normalized = Math.max(0, count);
                cartBadges().forEach((badge) => {
                    badge.dataset.cartCountValue = String(normalized);
                    badge.textContent = normalized > 99 ? '99+' : String(normalized);
                });
            };
            const setCartSummary = (count, payload = null) => {
                const normalized = Math.max(0, count);
                setCartCount(normalized);

                const itemsLabel = payload?.cart_items_label || `Items (${normalized})`;
                cartItemsLabels().forEach((element) => {
                    element.textContent = itemsLabel;
                });

                if (typeof payload?.cart_ref === 'string' && payload.cart_ref !== '') {
                    cartRefs().forEach((element) => {
                        element.textContent = payload.cart_ref;
                    });
                }

                if (typeof payload?.cart_total_formatted === 'string' && payload.cart_total_formatted !== '') {
                    cartTotals().forEach((element) => {
                        element.textContent = payload.cart_total_formatted;
                    });
                }
            };

            const ensureMethodInput = (form, method) => {
                let methodInput = form.querySelector('input[name="_method"]');
                if (!methodInput && method) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    form.appendChild(methodInput);
                }
                if (methodInput) {
                    if (method) {
                        methodInput.value = method;
                    } else {
                        methodInput.remove();
                    }
                }
            };

            const setButtonState = (form, isWishlisted) => {
                const button = form.querySelector('.js-wishlist-button');
                if (!button) return;

                form.dataset.wishlisted = isWishlisted ? '1' : '0';
                form.action = isWishlisted ? form.dataset.destroyUrl : form.dataset.storeUrl;
                ensureMethodInput(form, isWishlisted ? 'DELETE' : '');

                button.setAttribute('aria-label', isWishlisted ? 'Remove from wishlist' : 'Add to wishlist');
                button.classList.remove(
                    'border-slate-200', 'text-slate-500', 'hover:border-[#070740]/30', 'hover:text-[#070740]',
                    'focus-visible:ring-[#070740]/20', 'dark:border-slate-700', 'dark:text-slate-400',
                    'border-rose-200', 'text-rose-700', 'hover:bg-rose-50', 'focus-visible:ring-rose-300',
                    'dark:border-rose-900/60', 'dark:text-rose-300'
                );

                if (isWishlisted) {
                    button.classList.add(
                        'border-rose-200', 'text-rose-700', 'hover:bg-rose-50', 'focus-visible:ring-rose-300',
                        'dark:border-rose-900/60', 'dark:text-rose-300'
                    );
                } else {
                    button.classList.add(
                        'border-slate-200', 'text-slate-500', 'hover:border-[#070740]/30', 'hover:text-[#070740]',
                        'focus-visible:ring-[#070740]/20', 'dark:border-slate-700', 'dark:text-slate-400'
                    );
                }
            };

            document.querySelectorAll('.js-wishlist-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const currentlyWishlisted = form.dataset.wishlisted === '1';
                    const button = form.querySelector('.js-wishlist-button');
                    if (button) button.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json, text/html',
                            },
                            body: new FormData(form),
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            throw new Error('Wishlist request failed');
                        }

                        setButtonState(form, !currentlyWishlisted);
                        setWishlistCount(currentWishlistCount() + (currentlyWishlisted ? -1 : 1));
                    } catch (error) {
                        form.submit();
                    } finally {
                        if (button) button.disabled = false;
                    }
                });
            });

            document.querySelectorAll('.js-add-cart-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const button = form.querySelector('.js-add-cart-button');
                    if (button) button.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: new FormData(form),
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            throw new Error('Cart request failed');
                        }

                        let payload = null;
                        let nextCount = currentCartCount() + 1;
                        const contentType = response.headers.get('content-type') || '';
                        if (contentType.includes('application/json')) {
                            payload = await response.json();
                            if (Number.isInteger(payload?.cart_count)) {
                                nextCount = payload.cart_count;
                            }
                        }

                        setCartSummary(nextCount, payload);
                    } catch (error) {
                        form.submit();
                    } finally {
                        if (button) button.disabled = false;
                    }
                });
            });
        })();
    </script>
@endpush

