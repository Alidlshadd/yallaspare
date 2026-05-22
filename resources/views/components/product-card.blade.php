@props([
    'product',
    'showWishlist' => false,
    'isWishlisted' => false,
])

@php
    $currencySymbol = (string) \App\Models\Setting::getValue('currency_code', 'IQD');
    $productName = $product->name ?? $product->localized_name ?? $product->name_en ?? __('Product');
    $stockCode = $product->stock_code ?? $product->sku ?? __('No SKU');
    $pricing = method_exists($product, 'pricingFor')
        ? $product->pricingFor(auth()->user())
        : [
            'base_price' => (float) data_get($product, 'base_price', data_get($product, 'price', 0)),
            'price' => (float) data_get($product, 'price', 0),
            'discount_amount' => 0,
            'discount_percent' => 0,
            'has_discount' => false,
        ];
    $price = (float) data_get($pricing, 'price', 0);
    $basePrice = (float) data_get($pricing, 'base_price', $price);
    $discountAmount = (float) data_get($pricing, 'discount_amount', max(0, $basePrice - $price));
    $discountPercent = (int) data_get($pricing, 'discount_percent', ($basePrice > 0 ? round(($discountAmount / $basePrice) * 100) : 0));
    $hasDiscount = (bool) data_get($pricing, 'has_discount', $discountAmount > 0);
    $brand = $product->brand ?? null;
    $imageUrl = $product->image_url
        ?? (($product->image ?? null) ? asset('storage/' . ltrim((string) $product->image, '/')) : null);
    $compatibility = $product->compatibility
        ?? collect($product->compatible_models ?? [])
            ->map(fn ($item) => is_array($item) ? ($item['name'] ?? reset($item)) : $item)
            ->filter()
            ->implode(' | ');
    $inStock = $product->in_stock ?? (($product->stock_quantity ?? 0) > 0);
@endphp

<article
    aria-label="{{ $productName }}"
    class="group relative flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-4 text-left shadow-sm shadow-slate-900/5 transition duration-300 hover:-translate-y-1 hover:border-[#070740]/20 hover:shadow-lg hover:shadow-slate-900/10 active:translate-y-0.5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 dark:hover:border-[#070740]/30 dark:hover:shadow-black/20 sm:p-5"
>
    <a
        href="{{ route('shop.show', $product) }}"
        aria-label="{{ __('View details for :product', ['product' => $productName]) }}"
        class="absolute inset-0 z-10 rounded-3xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2"
    ></a>

    @if ($showWishlist)
        <div class="absolute right-4 top-4 z-20">
            @auth
                @if ($isWishlisted)
                    <form method="POST" action="{{ route('user.wishlist.destroy', $product) }}">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-white/95 text-rose-600 shadow-sm transition hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 dark:border-rose-800 dark:bg-slate-900/95 dark:text-rose-300 dark:hover:bg-rose-950/30"
                            aria-label="{{ __('Remove from wishlist') }}"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12.001 20.727 10.59 19.44C5.58 14.905 2.25 11.89 2.25 8.188 2.25 5.173 4.612 2.812 7.626 2.812c1.704 0 3.34.793 4.375 2.037a5.755 5.755 0 0 1 4.373-2.037c3.016 0 5.376 2.361 5.376 5.376 0 3.702-3.328 6.717-8.339 11.252L12 20.727Z" />
                            </svg>
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('user.wishlist.store', $product) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white/95 text-slate-500 shadow-sm transition hover:border-[#070740]/30 hover:text-[#070740] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740]/20 dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-300 dark:hover:border-slate-500"
                            aria-label="{{ __('Add to wishlist') }}"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12.001 20.727 10.59 19.44C5.58 14.905 2.25 11.89 2.25 8.188 2.25 5.173 4.612 2.812 7.626 2.812c1.704 0 3.34.793 4.375 2.037a5.755 5.755 0 0 1 4.373-2.037c3.016 0 5.376 2.361 5.376 5.376 0 3.702-3.328 6.717-8.339 11.252L12 20.727Z" />
                            </svg>
                        </button>
                    </form>
                @endif
            @endauth
        </div>
    @endif

    <div class="flex flex-1 flex-col">
        <div
            class="relative flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-[1.4rem] border border-slate-200/80 bg-slate-50 p-4 transition duration-300 group-hover:border-[#070740]/10 group-hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:group-hover:border-[#070740]/20 dark:group-hover:bg-slate-950"
        >
            @if ($hasDiscount)
                <div class="absolute left-3 top-3 z-10 inline-flex items-center rounded-full bg-rose-600 px-3 py-1 text-xs font-bold text-white shadow-sm">
                    -{{ $discountPercent }}%
                </div>
            @endif

            @if ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt="{{ $productName }}"
                    class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.03]"
                >
            @else
                <div class="flex h-full w-full items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-900 dark:text-slate-600">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16 9 11l4 4 3-3 4 4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16" />
                        <circle cx="9" cy="8" r="1.5" />
                    </svg>
                </div>
            @endif
        </div>

        <div class="mt-4 flex min-h-[1.85rem] flex-wrap items-center gap-1.5">
            @if ($brand)
                <span class="inline-flex items-center rounded-full border border-slate-200/80 bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    {{ $brand }}
                </span>
            @endif

            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] {{ $inStock ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-300' : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-300' }}">
                {{ $inStock ? __('In Stock') : __('Out of Stock') }}
            </span>
        </div>

        <div class="mt-4 space-y-3">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                    {{ __('Stock Code') }}
                </p>
                <p class="mt-1 text-[0.78rem] font-semibold uppercase tracking-[0.14em] text-slate-700 dark:text-slate-300">
                    {{ $stockCode }}
                </p>
            </div>

            <h3 class="line-clamp-2 min-h-[3.2rem] text-[1.05rem] font-semibold leading-6 tracking-[-0.02em] text-slate-950 transition duration-200 group-hover:text-[#070740] dark:text-white dark:group-hover:text-slate-200">
                {{ $productName }}
            </h3>
        </div>

        <div class="mt-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                {{ __('Price') }}
            </p>
            <div class="mt-1 flex flex-wrap items-end gap-2">
                <p class="text-[1.45rem] font-semibold leading-none tracking-[-0.03em] text-[#070740] dark:text-white">
                    {{ number_format($price, 2) }}
                    <span class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-600 dark:text-amber-300">{{ $currencySymbol }}</span>
                </p>
                @if ($hasDiscount)
                    <p class="text-sm font-semibold text-slate-400 line-through dark:text-slate-500">
                        {{ number_format($basePrice, 2) }}
                    </p>
                @endif
            </div>
            @if ($hasDiscount)
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {{ __('You save') }} {{ number_format($discountAmount, 2) }} {{ $currencySymbol }}
                    </span>
                </div>
            @endif
        </div>

        <p class="mt-3 min-h-[2.5rem] text-[0.8rem] leading-5 text-slate-500 dark:text-slate-400">
            {{ $compatibility !== '' ? $compatibility : __('Compatible part details available on request.') }}
        </p>
    </div>

    @auth
        <form method="POST" action="{{ route('cart.add', $product->id) }}" class="relative z-20 mt-4">
            @csrf
            <button
                type="submit"
                @disabled(! $inStock)
                class="inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-[0.8rem] font-semibold uppercase tracking-[0.1em] transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 {{ $inStock ? 'bg-[#070740] text-white hover:bg-[#0a0d3f] active:translate-y-0.5' : 'cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500' }}"
            >
                {{ $inStock ? __('Add to Cart') : __('Out of Stock') }}
            </button>
        </form>
    @else
        <a
            href="{{ $inStock ? route('checkout.options', $product) : '#' }}"
            aria-disabled="{{ $inStock ? 'false' : 'true' }}"
            class="relative z-20 mt-4 inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-[0.8rem] font-semibold uppercase tracking-[0.1em] transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#070740] focus-visible:ring-offset-2 {{ $inStock ? 'bg-[#070740] text-white hover:bg-[#0a0d3f] active:translate-y-0.5' : 'pointer-events-none cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500' }}"
        >
            {{ $inStock ? __('Login or Register to Order') : __('Out of Stock') }}
        </a>
    @endauth
</article>
