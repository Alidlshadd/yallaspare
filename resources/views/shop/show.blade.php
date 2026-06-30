@extends('layouts.user')

@php
    $siteName = (string) ($systemSettings['site_name'] ?? 'YallaSpare');
    $name = $product->name;
    $description = $product->localizedDescription();

    $imageUrl = $product->image
        ? asset('storage/' . ltrim((string) $product->image, '/'))
        : asset('images/placeholder-product.png');
    $galleryImages = $product->images->isNotEmpty()
        ? $product->images->map(fn ($image) => asset('storage/' . ltrim((string) $image->path, '/')))->values()
        : collect([$imageUrl])->values();
    $imageUrl = $galleryImages->first() ?: $imageUrl;

    $compatibleModels = collect($product->compatible_models ?? [])
        ->map(fn ($item) => is_array($item) ? ($item['name'] ?? reset($item)) : $item)
        ->filter()
        ->values();

    $pricing = $product->pricingFor(auth()->user());
    $currentPrice = (float) $pricing['price'];
    $basePrice = (float) $pricing['base_price'];
    $discountAmount = (float) $pricing['discount_amount'];
    $hasDiscount = (bool) $pricing['has_discount'];
    $discountPercent = (int) $pricing['discount_percent'];
    $inStock = (int) $product->stock_quantity > 0;
    $maxPurchasableQuantity = min(99, max(0, (int) $product->stock_quantity));
    $sku = (string) ($product->sku ?: __('N/A'));
    $oem = (string) ($product->oem_number ?: __('N/A'));
    $partNumber = (string) ($product->part_number ?: __('N/A'));
    $warranty = (string) ($product->warranty ?: __('Available on request'));
    $brand = (string) ($product->brand ?: __('Generic'));
    $categoryName = (string) ($product->category?->name ?? __('Auto Parts'));
    $canonicalUrl = route('shop.show', $product);
    $seoTitle = trim($name . ' | ' . $brand . ' | ' . $siteName);
    $seoDescriptionSource = trim((string) ($description ?: "{$brand} {$name} {$categoryName} spare part with price, stock, SKU, warranty, and delivery details from {$siteName}."));
    $seoDescription = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', strip_tags($seoDescriptionSource)), 158, '');
    $availabilityUrl = $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $name,
        'description' => $seoDescription,
        'image' => $galleryImages->values()->all(),
        'sku' => $product->sku ?: null,
        'mpn' => $product->part_number ?: ($product->oem_number ?: null),
        'brand' => [
            '@type' => 'Brand',
            'name' => $brand,
        ],
        'category' => $categoryName,
        'offers' => [
            '@type' => 'Offer',
            'url' => $canonicalUrl,
            'priceCurrency' => $currencySymbol,
            'price' => number_format($currentPrice, 2, '.', ''),
            'availability' => $availabilityUrl,
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => [
                '@type' => 'Organization',
                'name' => $siteName,
            ],
        ],
    ];

    if (($reviewCount ?? 0) > 0 && ($averageRating ?? 0) > 0) {
        $productSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float) $averageRating, 1, '.', ''),
            'reviewCount' => (int) $reviewCount,
        ];
    }

    $productSchema = array_filter($productSchema, fn ($value) => $value !== null && $value !== '');

@endphp

@section('title', $seoTitle)
@section('meta_description', $seoDescription)

@push('head')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $imageUrl }}">
    <meta property="product:price:amount" content="{{ number_format($currentPrice, 2, '.', '') }}">
    <meta property="product:price:currency" content="{{ $currencySymbol }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $imageUrl }}">
    <script type="application/ld+json">
        {!! json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>
@endpush

@section('content')
    <div class="space-y-6">
        @if (session('status') || session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('status') ?: session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6 lg:p-8">
            <nav class="mb-6 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 sm:text-sm">
                <a href="{{ route('home') }}" class="transition hover:text-slate-900 dark:hover:text-white">{{ __('Home') }}</a>
                <span>/</span>
                <a href="{{ route('shop.index') }}" class="transition hover:text-slate-900 dark:hover:text-white">{{ __('Shop') }}</a>
                @if ($product->category)
                    <span>/</span>
                    <a href="{{ route('shop.index', ['category' => $product->category->id]) }}" class="transition hover:text-slate-900 dark:hover:text-white">{{ $categoryName }}</a>
                @endif
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200">{{ $name }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <aside class="space-y-4">
                    <div class="group overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl">
                        <div class="aspect-square w-full overflow-hidden bg-slate-50 p-4 dark:bg-slate-950 sm:p-8">
                            <img
                                id="product-main-image"
                                src="{{ $imageUrl }}"
                                alt="{{ $name }}"
                                class="h-full w-full object-contain transition duration-500 ease-out group-hover:scale-[1.04]"
                            >
                        </div>
                    </div>

                    <div class="flex gap-3 overflow-x-auto pb-1">
                        @foreach ($galleryImages as $index => $thumb)
                            <button
                                type="button"
                                data-gallery-thumb
                                data-image-src="{{ $thumb }}"
                                class="inline-flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl border {{ $index === 0 ? 'border-primary bg-slate-100 dark:bg-slate-800' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' }} p-2 transition hover:border-primary/50 hover:bg-slate-100 dark:hover:bg-slate-800"
                                aria-label="{{ __('Product image :number', ['number' => $index + 1]) }}"
                            >
                                <img src="{{ $thumb }}" alt="{{ __(':name thumbnail :number', ['name' => $name, 'number' => $index + 1]) }}" class="h-full w-full object-contain">
                            </button>
                        @endforeach
                    </div>
                </aside>

                <article class="space-y-5">
                    <div class="space-y-2">
                        <p class="break-mobile text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400 sm:tracking-[0.12em]">
                            {{ $brand }} | {{ __('SKU:') }} {{ $sku }} | {{ __('OEM:') }} {{ $oem }} | {{ __('Part:') }} {{ $partNumber }}
                        </p>
                        <h1 class="break-mobile text-2xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white sm:text-4xl">{{ $name }}</h1>
                        <p class="text-sm leading-7 text-slate-600 dark:text-slate-300">
                            {{ $description ?: __('High-quality spare part engineered for reliable performance and daily workshop use.') }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Category') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $categoryName }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Stock Status') }}</p>
                            <p class="mt-1 text-sm font-semibold {{ $inStock ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $inStock ? __('In stock') : __('Out of stock') }}
                            </p>
                        </div>
                    </div>

                    <section class="rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Compatibility') }}</p>
                        @if ($compatibleModels->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($compatibleModels->take(8) as $model)
                                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $model }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                                {{ __('Compatibility details are available on request.') }}
                            </p>
                        @endif
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                        @if ($hasDiscount)
                            <div class="mb-4 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-rose-600 px-3 py-1 text-xs font-bold uppercase tracking-[0.08em] text-white shadow-sm">
                                    -{{ $discountPercent }}%
                                </span>
                                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                                    {{ __('You save') }} {{ number_format($discountAmount, 2) }} {{ $currencySymbol }}
                                </span>
                            </div>
                        @endif
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ $hasDiscount ? __('Discounted Price') : __('Price') }}</p>
                                <div class="mt-1 flex flex-wrap items-end gap-2">
                                    <p class="break-all text-3xl font-bold tracking-[-0.03em] text-primary dark:text-white sm:text-4xl">{{ number_format($currentPrice, 2) }}</p>
                                    <span class="pb-1 text-sm font-semibold uppercase tracking-[0.1em] text-slate-600 dark:text-slate-300">{{ $currencySymbol }}</span>
                                </div>
                            </div>
                            @if ($hasDiscount)
                                <div class="pb-1">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Old Price') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-rose-600 line-through dark:text-rose-400">{{ number_format($basePrice, 2) }} {{ $currencySymbol }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4">
                            <label for="purchase-qty" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ __('Quantity') }}</label>
                            <div class="mt-2 flex items-center overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                                <button type="button" data-qty-minus class="inline-flex h-11 w-11 items-center justify-center text-slate-600 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">-</button>
                                <input id="purchase-qty" type="text" inputmode="numeric" value="1" min="1" max="{{ $maxPurchasableQuantity }}" data-max-quantity="{{ $maxPurchasableQuantity }}" class="h-11 w-full border-0 bg-white text-center text-sm font-semibold text-slate-900 focus:ring-0 dark:bg-slate-900 dark:text-white">
                                <button type="button" data-qty-plus class="inline-flex h-11 w-11 items-center justify-center text-slate-600 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">+</button>
                            </div>
                            @if ($inStock && $maxPurchasableQuantity <= 5)
                                <p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ __('Only :quantity left in stock', ['quantity' => $maxPurchasableQuantity]) }}</p>
                            @endif
                        </div>

                        <div class="mt-4 space-y-2.5">
                            @auth
                                @if ($inStock)
                                    <form action="{{ route('cart.add', $product) }}" method="POST" id="purchase-form" class="js-add-cart-form space-y-2.5">
                                        @csrf
                                        <input type="hidden" name="quantity" id="purchase-qty-hidden" value="1">
                                        <button type="submit" class="js-add-cart-button inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0a0d55] disabled:cursor-wait disabled:opacity-80">
                                            {{ __('Add to Cart') }}
                                        </button>
                                        <button
                                            type="submit"
                                            formaction="{{ route('checkout.buy-now', $product) }}"
                                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                                        >
                                            {{ __('Buy Now') }}
                                        </button>
                                    </form>
                                    @if (!empty($isWishlisted))
                                        <form action="{{ route('user.wishlist.destroy', $product) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:border-rose-400 hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-300 dark:hover:border-rose-800 dark:hover:bg-rose-950/30">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="m12 20.25-1.45-1.32C5.4 14.36 2.25 11.5 2.25 7.97c0-2.48 1.95-4.47 4.43-4.47 1.4 0 2.75.65 3.57 1.66.82-1.01 2.17-1.66 3.57-1.66 2.48 0 4.43 1.99 4.43 4.47 0 3.53-3.15 6.39-8.3 10.96L12 20.25Z" />
                                                </svg>
                                                <span>{{ __('Remove from Wishlist') }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('user.wishlist.store', $product) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-primary/40 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 20.25-1.45-1.32C5.4 14.36 2.25 11.5 2.25 7.97c0-2.48 1.95-4.47 4.43-4.47 1.4 0 2.75.65 3.57 1.66.82-1.01 2.17-1.66 3.57-1.66 2.48 0 4.43 1.99 4.43 4.47 0 3.53-3.15 6.39-8.3 10.96L12 20.25Z" />
                                                </svg>
                                                <span>{{ __('Add to Wishlist') }}</span>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-400">
                                        {{ __('Currently unavailable') }}
                                    </button>
                                    @if (!empty($isBackInStockSubscribed))
                                        <form action="{{ route('shop.back-in-stock.destroy', $product) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-300 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-50 dark:border-emerald-900/50 dark:text-emerald-300 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/30">
                                                {{ __('Notification enabled') }}
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('shop.back-in-stock.store', $product) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-primary/40 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                                                {{ __('Notify me when available') }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            @else
                                @if ($inStock)
                                    <form action="{{ route('checkout.options', $product) }}" method="GET" class="space-y-2.5">
                                        <input type="hidden" name="quantity" id="purchase-qty-hidden-guest" value="1">
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0a0d55]">
                                            {{ __('Login or Register to Order') }}
                                        </button>
                                    </form>
                                @else
                                    <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-400">
                                        {{ __('Currently unavailable') }}
                                    </button>
                                @endif
                                <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-primary/40 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                                    <span>{{ $inStock ? __('Login for wishlist') : __('Login for stock notification') }}</span>
                                </a>
                            @endauth

                        </div>
                    </section>
                </article>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Product Details') }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                    {{ $description ?: 'Reliable auto spare part selected for consistent quality and fit.' }}
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
                    <li>{{ __('Original quality standards') }}</li>
                    <li>{{ __('Carefully packed before dispatch') }}</li>
                    <li>{{ __('Verified for category-level compatibility') }}</li>
                </ul>
            </article>

            <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Specifications & Shipping') }}</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-slate-800">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('SKU') }}</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $sku }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-slate-800">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('OEM Number') }}</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $oem }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-slate-800">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Part Number') }}</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $partNumber }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-slate-800">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Brand') }}</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $brand }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-slate-800">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Category') }}</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $categoryName }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 dark:border-slate-800">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('Warranty') }}</dt>
                        <dd class="font-semibold text-slate-900 dark:text-white">{{ $warranty }}</dd>
                    </div>
                    <div class="pt-1 text-slate-600 dark:text-slate-300">
                        {{ __('Fast shipping with trusted delivery partners. 7-day return policy for eligible items.') }}
                    </div>
                </dl>
            </article>
        </section>

        <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Customer Reviews') }}</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('Real buyer feedback') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Ratings from customers who purchased this product.') }}</p>
                </div>
                <div class="w-full rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-900/40 dark:bg-amber-900/15 sm:w-auto sm:min-w-56">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300">{{ __('Average rating') }}</p>
                            <p class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">
                                {{ ($reviewCount ?? 0) > 0 ? number_format((float) $averageRating, 1) : '0.0' }}
                                <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">/ 5</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-0.5" aria-hidden="true">
                            @for ($rating = 1; $rating <= 5; $rating++)
                                <svg class="h-4 w-4 {{ $rating <= (int) round((float) ($averageRating ?? 0)) ? 'text-amber-500' : 'text-amber-200 dark:text-slate-700' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.1 2.3c.3-.9 1.5-.9 1.8 0l1.4 4.2h4.4c.9 0 1.3 1.2.6 1.8l-3.6 2.6 1.4 4.2c.3.9-.7 1.6-1.5 1.1L10 13.6l-3.6 2.6c-.8.5-1.8-.2-1.5-1.1l1.4-4.2-3.6-2.6c-.7-.6-.3-1.8.6-1.8h4.4l1.4-4.2Z" />
                                </svg>
                            @endfor
                        </div>
                    </div>
                    <p class="mt-2 text-xs font-medium text-slate-600 dark:text-slate-300">{{ $reviewCount ?? 0 }} {{ __('reviews') }}</p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-2">
                @forelse ($reviews as $review)
                    @php
                        $reviewerName = trim((string) ($review->user?->name ?? ''));
                        $reviewerFirstName = $reviewerName !== '' ? \Illuminate\Support\Str::before($reviewerName, ' ') : __('Customer');
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-950">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold uppercase text-white shadow-sm">{{ \Illuminate\Support\Str::substr($reviewerFirstName, 0, 1) }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $review->title ?: __('Customer review') }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $reviewerFirstName }}
                                    @if ($review->reviewed_at || $review->created_at)
                                        · {{ optional($review->reviewed_at ?? $review->created_at)->format('M d, Y') }}
                                    @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 rounded-full border border-amber-200 bg-white px-2.5 py-1 dark:border-amber-900/40 dark:bg-slate-900" aria-label="{{ __(':rating out of 5', ['rating' => (int) $review->rating]) }}">
                                @for ($rating = 1; $rating <= 5; $rating++)
                                    <svg class="h-3.5 w-3.5 {{ $rating <= (int) $review->rating ? 'text-amber-500' : 'text-slate-300 dark:text-slate-700' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M9.1 2.3c.3-.9 1.5-.9 1.8 0l1.4 4.2h4.4c.9 0 1.3 1.2.6 1.8l-3.6 2.6 1.4 4.2c.3.9-.7 1.6-1.5 1.1L10 13.6l-3.6 2.6c-.8.5-1.8-.2-1.5-1.1l1.4-4.2-3.6-2.6c-.7-.6-.3-1.8.6-1.8h4.4l1.4-4.2Z" />
                                    </svg>
                                @endfor
                            </div>
                        </div>
                        @if ($review->comment)
                            <p class="mt-3 text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $review->comment }}</p>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400 lg:col-span-2">
                        {{ __('No reviews yet. Delivered customers can be the first to rate this product from their order page.') }}
                    </div>
                @endforelse
            </div>
        </section>

        @if (($recentlyViewedProducts ?? collect())->isNotEmpty())
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Recently viewed') }}</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-[-0.02em] text-slate-950 dark:text-white">{{ __('Your product history') }}</h2>
                    </div>
                    <a href="{{ route('shop.index') }}" class="text-sm font-semibold text-primary transition hover:text-[#10105c] dark:text-slate-200 dark:hover:text-white">
                        {{ __('Shop') }}
                    </a>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($recentlyViewedProducts as $recentProduct)
                        <x-product-card :product="$recentProduct" />
                    @endforeach
                </div>
            </section>
        @endif

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mainImage = document.getElementById('product-main-image');
            const thumbs = Array.from(document.querySelectorAll('[data-gallery-thumb]'));

            thumbs.forEach((thumb) => {
                thumb.addEventListener('click', () => {
                    const imageSrc = thumb.getAttribute('data-image-src');
                    if (mainImage && imageSrc) {
                        mainImage.src = imageSrc;
                    }

                    thumbs.forEach((node) => {
                        node.classList.remove('border-primary', 'bg-slate-100', 'dark:bg-slate-800');
                        node.classList.add('border-slate-200', 'bg-white', 'dark:border-slate-800', 'dark:bg-slate-900');
                    });

                    thumb.classList.remove('border-slate-200', 'bg-white', 'dark:border-slate-800', 'dark:bg-slate-900');
                    thumb.classList.add('border-primary', 'bg-slate-100', 'dark:bg-slate-800');
                });
            });

            const qtyInput = document.getElementById('purchase-qty');
            const qtyHiddenInputs = Array.from(document.querySelectorAll('#purchase-qty-hidden, #purchase-qty-hidden-guest'));
            const qtyMinus = document.querySelector('[data-qty-minus]');
            const qtyPlus = document.querySelector('[data-qty-plus]');

            const syncQty = () => {
                if (!qtyInput) return;
                const maxQty = Math.max(1, Number.parseInt(qtyInput.dataset.maxQuantity || qtyInput.getAttribute('max') || '99', 10) || 99);
                let value = Number.parseInt(qtyInput.value, 10);
                if (Number.isNaN(value) || value < 1) value = 1;
                if (value > maxQty) value = maxQty;
                qtyInput.value = value;
                qtyHiddenInputs.forEach((input) => {
                    input.value = value;
                });
            };

            if (qtyMinus && qtyInput) {
                qtyMinus.addEventListener('click', () => {
                    qtyInput.value = Math.max(1, (Number.parseInt(qtyInput.value, 10) || 1) - 1);
                    syncQty();
                });
            }

            if (qtyPlus && qtyInput) {
                qtyPlus.addEventListener('click', () => {
                    const maxQty = Math.max(1, Number.parseInt(qtyInput.dataset.maxQuantity || qtyInput.getAttribute('max') || '99', 10) || 99);
                    qtyInput.value = Math.min(maxQty, (Number.parseInt(qtyInput.value, 10) || 1) + 1);
                    syncQty();
                });
            }

            if (qtyInput) {
                qtyInput.addEventListener('change', syncQty);
                qtyInput.addEventListener('input', syncQty);
            }

            syncQty();
        });
    </script>
@endsection
