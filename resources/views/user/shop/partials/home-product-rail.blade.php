@php
    $railProducts = $products ?? collect();
    $railBadgeLabel = match ($badge) {
        'new' => __('New'),
        'popular' => __('Popular'),
        default => __('Best Seller'),
    };
    $railBadgeEmoji = match ($badge) {
        'new' => '🆕',
        'popular' => '⭐',
        default => '🔥',
    };
    $railRibbonClass = match ($badge) {
        'new' => 'hpr-ribbon-new',
        'popular' => 'hpr-ribbon-popular',
        default => '',
    };
@endphp

@if ($railProducts->isNotEmpty())
    <section class="space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ $title }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
            </div>
            <a
                href="{{ $viewAllUrl }}"
                class="inline-flex items-center rounded-full px-3 py-2 text-sm font-medium text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-primary/30"
            >
                {{ __('View catalog') }}
            </a>
        </div>

        <div class="hpr-marquee" aria-hidden="true">
            <div class="hpr-marquee-track">
                @foreach ($railProducts->concat($railProducts) as $tickerProduct)
                    <span>{{ $railBadgeEmoji }} {{ data_get($tickerProduct, 'name') }} — {{ $railBadgeLabel }}</span>
                @endforeach
            </div>
        </div>

        <div class="relative" data-category-rail>
            <button
                type="button"
                data-category-prev
                aria-label="{{ __('Scroll backward') }}"
                class="hpr-arrow hpr-arrow-prev"
            >
                <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 6-6 6 6 6" />
                </svg>
            </button>

            <div class="relative min-w-0">
                <div data-category-fade-start class="hpr-fade hpr-fade-start" aria-hidden="true"></div>
                <div data-category-fade-end class="hpr-fade hpr-fade-end" aria-hidden="true"></div>

                <div data-category-scroll class="hpr-rail">
                    @foreach ($railProducts as $product)
                        @php
                            $wishlistCount = (int) data_get($product, 'wishlist_count', 0);
                            $isWishlisted = in_array((int) data_get($product, 'id', 0), $wishlistedProductIds ?? [], true);
                            $hasDiscount = (bool) data_get($product, 'has_discount', false);
                            $discountPercent = (int) data_get($product, 'discount_percent', 0);
                        @endphp
                        <article class="hpr-card group">
                            <div class="relative h-40 overflow-hidden bg-slate-100 p-4 dark:bg-slate-800/80">
                                <span class="hpr-ribbon {{ $railRibbonClass }}">
                                    {{ $railBadgeLabel }}
                                </span>

                                @if ($hasDiscount)
                                    <span class="absolute bottom-2 left-2 z-10 inline-flex rounded-full bg-rose-600 px-2.5 py-0.5 text-[11px] font-bold text-white shadow-sm">
                                        -{{ $discountPercent }}%
                                    </span>
                                @endif

                                @if ($isCustomerAuthenticated)
                                    @php
                                        $productId = (int) data_get($product, 'id');
                                        $storeUrl = route('user.wishlist.store', $productId);
                                        $destroyUrl = route('user.wishlist.destroy', $productId);
                                    @endphp
                                    <div class="absolute right-2 top-2 z-10">
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
                                                class="js-wishlist-button inline-flex items-center justify-center rounded-full border bg-white/95 p-1.5 text-[11px] font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 dark:bg-slate-900/95 {{ $isWishlisted ? 'border-rose-200 text-rose-700 hover:bg-rose-50 focus-visible:ring-rose-300 dark:border-rose-900/60 dark:text-rose-300' : 'border-slate-200 text-slate-500 hover:border-primary/30 hover:text-primary focus-visible:ring-primary/20 dark:border-slate-700 dark:text-slate-400' }}"
                                                aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}"
                                            >
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="m12 20.25-1.45-1.32C5.4 14.36 2.25 11.5 2.25 7.97c0-2.48 1.95-4.47 4.43-4.47 1.4 0 2.75.65 3.57 1.66.82-1.01 2.17-1.66 3.57-1.66 2.48 0 4.43 1.99 4.43 4.47 0 3.53-3.15 6.39-8.3 10.96L12 20.25Z" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="absolute right-2 top-2 inline-flex items-center justify-center rounded-full border bg-white/95 p-1.5 text-[11px] font-semibold shadow-sm dark:bg-slate-900/95 {{ $wishlistCount > 0 ? 'border-rose-200 text-rose-700 dark:border-rose-900/60 dark:text-rose-300' : 'border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400' }}">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="m12 20.25-1.45-1.32C5.4 14.36 2.25 11.5 2.25 7.97c0-2.48 1.95-4.47 4.43-4.47 1.4 0 2.75.65 3.57 1.66.82-1.01 2.17-1.66 3.57-1.66 2.48 0 4.43 1.99 4.43 4.47 0 3.53-3.15 6.39-8.3 10.96L12 20.25Z" />
                                        </svg>
                                    </span>
                                @endif

                                @if (data_get($product, 'image'))
                                    <a href="{{ data_get($product, 'detail_url') }}" class="block h-full w-full">
                                        <img src="{{ data_get($product, 'image') }}" alt="{{ data_get($product, 'name') }}" class="h-full w-full object-contain" loading="lazy">
                                    </a>
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-slate-400 dark:text-slate-500">
                                        <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16 9 11l4 4 3-3 4 4" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16" />
                                            <circle cx="9" cy="8" r="1.5" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-1 flex-col gap-3 p-4">
                                <a href="{{ data_get($product, 'detail_url') }}" class="line-clamp-2 block min-h-[2.6rem] text-sm font-semibold leading-5 text-slate-950 transition hover:text-primary dark:text-white dark:hover:text-slate-200">
                                    {{ data_get($product, 'name') }}
                                </a>

                                <div class="flex flex-wrap items-end gap-2">
                                    <p class="text-lg font-bold tracking-[-0.02em] text-primary dark:text-white">
                                        {{ number_format((float) data_get($product, 'price', 0), 0) }} {{ $currencySymbol }}
                                    </p>
                                    @if ($hasDiscount)
                                        <p class="text-xs font-semibold text-slate-400 line-through dark:text-slate-500">
                                            {{ number_format((float) data_get($product, 'base_price', 0), 0) }}
                                        </p>
                                    @endif
                                </div>

                                <div class="mt-auto grid grid-cols-2 items-stretch gap-2">
                                    <a
                                        href="{{ data_get($product, 'detail_url') }}"
                                        class="inline-flex h-full items-center justify-center rounded-xl border border-slate-200/80 px-3 py-2.5 text-xs font-medium text-slate-700 transition duration-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 dark:border-slate-800 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-primary/30"
                                    >
                                        {{ __('View') }}
                                    </a>
                                    @if ((int) data_get($product, 'stock_quantity', 0) > 0)
                                        <form method="POST" action="{{ route('cart.add', (int) data_get($product, 'id')) }}" class="js-add-cart-form h-full">
                                            @csrf
                                            <button type="submit" class="js-add-cart-button inline-flex h-full w-full items-center justify-center rounded-xl bg-primary px-3 py-2.5 text-xs font-medium text-white transition duration-200 hover:bg-[#0a0a55] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                                                {{ __('Add to Cart') }}
                                            </button>
                                        </form>
                                    @elseif ($isCustomerAuthenticated)
                                        <form method="POST" action="{{ route('shop.back-in-stock.store', (int) data_get($product, 'id')) }}" class="h-full">
                                            @csrf
                                            <button type="submit" class="inline-flex h-full w-full items-center justify-center rounded-xl bg-primary px-3 py-2.5 text-xs font-medium text-white transition duration-200 hover:bg-[#0a0a55] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                                                {{ __('Send Request') }}
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('login') }}" class="inline-flex h-full w-full items-center justify-center rounded-xl bg-primary px-3 py-2.5 text-center text-xs font-medium text-white transition duration-200 hover:bg-[#0a0a55] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20">
                                            {{ __('Send Request') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <button
                type="button"
                data-category-next
                aria-label="{{ __('Scroll forward') }}"
                class="hpr-arrow hpr-arrow-next"
            >
                <svg class="h-3.5 w-3.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" />
                </svg>
            </button>
        </div>
    </section>
@endif
