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

    // The landing pages this part belongs to. Linking them from here is what
    // lets a search engine — and a customer browsing for their car — walk from
    // one part to everything else that fits.
    $vehicleLandingPages = $product->relationLoaded('vehicleFitments')
        ? $product->vehicleFitments
            ->filter(fn ($fitment) => $fitment->model?->slug && $fitment->model->brand?->slug)
            ->map(fn ($fitment) => [
                'label' => trim($fitment->model->brand->name.' '.$fitment->model->localizedName()),
                'url' => route('catalog.vehicle-model', [$fitment->model->brand->slug, $fitment->model->slug]),
            ])
            ->unique('url')
            ->take(8)
            ->values()
        : collect();

    $compatibleModels = collect($product->compatible_models ?? [])
        ->map(fn ($item) => is_array($item) ? ($item['name'] ?? reset($item)) : $item)
        ->filter()
        ->values();
    $vehicleFitments = $product->relationLoaded('vehicleFitments')
        ? $product->vehicleFitments
            ->map(function ($fitment) {
                $yearFrom = $fitment->year_from ? (int) $fitment->year_from : null;
                $yearTo = $fitment->year_to ? (int) $fitment->year_to : null;
                $yearLabel = match (true) {
                    $yearFrom !== null && $yearTo !== null && $yearFrom === $yearTo => (string) $yearFrom,
                    $yearFrom !== null && $yearTo !== null => $yearFrom.'–'.$yearTo,
                    $yearFrom !== null => $yearFrom.'+',
                    $yearTo !== null => '≤ '.$yearTo,
                    default => __('Any year'),
                };

                return [
                    'brand' => (string) ($fitment->brand?->name ?: __('Any brand')),
                    'family' => (string) ($fitment->model?->family?->localizedName() ?: $fitment->model?->localizedName() ?: __('Other')),
                    'model' => (string) ($fitment->model?->localizedName() ?: __('Any model')),
                    'image' => $fitment->model?->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($fitment->model->image_path)
                        ? asset('storage/'.ltrim($fitment->model->image_path, '/'))
                        : null,
                    'years' => $yearLabel,
                    'engine' => (string) ($fitment->engine ? \App\Support\VehicleLocalization::engine($fitment->engine) : __('Any engine')),
                    'notes' => trim((string) $fitment->notes),
                    // Numeric bounds for the fitment board: null on both means the
                    // whole model is covered rather than that data is missing.
                    'from' => $yearFrom,
                    'to' => $yearTo,
                    'engineRaw' => \App\Support\VehicleLocalization::engine($fitment->engine),
                ];
            })
            ->unique(fn (array $fitment) => implode('|', $fitment))
            ->values()
        : collect();

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
    // What this page says about itself, built by the one place that knows the
    // shapes. The trail matches the links rendered below it, so the crumbs a
    // visitor sees and the crumbs a search engine reads cannot drift apart.
    $breadcrumbTrail = array_values(array_filter([
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('Shop'), 'url' => route('shop.index')],
        $product->category
            ? ['label' => $categoryName, 'url' => route('shop.index', ['category' => $product->category->id])]
            : null,
        ['label' => $name, 'url' => ''],
    ]));

    $pageSchemas = [
        \App\Support\Seo\StructuredData::product(
            product: $product,
            images: $galleryImages->values()->all(),
            price: $currentPrice,
            inStock: $inStock,
            url: $canonicalUrl,
            averageRating: isset($averageRating) ? (float) $averageRating : null,
            reviewCount: (int) ($reviewCount ?? 0),
            description: $seoDescription,
            categoryName: $categoryName,
        ),
        \App\Support\Seo\StructuredData::breadcrumbs($breadcrumbTrail),
    ];

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
    @include('partials.structured-data', ['schemas' => $pageSchemas])
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

        <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6 lg:p-8">
            <nav class="mb-6 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500 sm:text-sm">
                <a href="{{ route('home') }}" class="transition hover:text-slate-900 dark:hover:text-white">{{ __('Home') }}</a>
                <span>/</span>
                <a href="{{ route('shop.index') }}" class="transition hover:text-slate-900 dark:hover:text-white">{{ __('Shop') }}</a>
                @if ($product->category)
                    <span>/</span>
                    <a href="{{ route('shop.index', ['category' => $product->category->id]) }}" class="transition hover:text-slate-900 dark:hover:text-white">{{ $categoryName }}</a>
                @endif
                <span>/</span>
                <span class="text-slate-700">{{ $name }}</span>
            </nav>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <aside class="space-y-4">
                    <div class="group overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl">
                        <div class="aspect-square w-full overflow-hidden bg-slate-50 p-4 sm:p-8">
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
                        <p class="break-mobile text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 sm:tracking-[0.12em]">
                            {{ $brand }} | {{ __('SKU:') }} {{ $sku }} | {{ __('OEM:') }} {{ $oem }} | {{ __('Part:') }} {{ $partNumber }}
                        </p>
                        <h1 class="break-mobile text-2xl font-semibold tracking-[-0.03em] text-slate-950 sm:text-4xl">{{ $name }}</h1>
                        <p class="text-sm leading-7 text-slate-600">
                            {{ $description ?: __('High-quality spare part engineered for reliable performance and daily workshop use.') }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Category') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $categoryName }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Stock Status') }}</p>
                            {{-- The 700 shades are pitched for a light card; on the dark one they
                                 fell to 3.3:1 in stock and 2.3:1 out. The 300s match what the
                                 badges and buttons further down this page already use. --}}
                            <p class="mt-1 text-sm font-semibold {{ $inStock ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">
                                {{ $inStock ? __('In stock') : __('Out of stock') }}
                            </p>
                        </div>
                    </div>

                    {{-- Read-only product compatibility. No saved vehicle or garage state. --}}
                    @if ($vehicleFitments->isNotEmpty())
                        <x-shop.fitment :fitments="$vehicleFitments" />
                    @else
                    <section data-product-compatibility class="rounded-2xl border border-app bg-surface-2 p-5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ __('Compatibility') }}</p>
                        @if ($compatibleModels->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($compatibleModels->take(8) as $model)
                                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800">{{ $model }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-3 rounded-xl border border-dashed border-app bg-surface-1 px-3 py-2 text-sm text-muted">
                                {{ __('Compatibility details are available on request.') }}
                            </p>
                        @endif
                    </section>
                    @endif

                    @if ($product->productBrand?->slug || $vehicleLandingPages->isNotEmpty())
                        <section class="rounded-2xl border border-app bg-surface-2 p-5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">{{ __('Browse related parts') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if ($product->productBrand?->slug)
                                    <a href="{{ route('catalog.brand', $product->productBrand->slug) }}" class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-primary/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        {{ __('All :brand parts', ['brand' => $product->productBrand->name]) }}
                                    </a>
                                @endif

                                @foreach ($vehicleLandingPages as $landingPage)
                                    <a href="{{ $landingPage['url'] }}" class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-primary/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        {{ __('Parts for :vehicle', ['vehicle' => $landingPage['label']]) }}
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
                        @if ($hasDiscount)
                            <div class="mb-4 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-accent px-3 py-1 text-xs font-bold uppercase tracking-[0.08em] text-navy shadow-sm">
                                    -{{ $discountPercent }}%
                                </span>
                                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-300">
                                    {{ __('You save') }} {{ number_format($discountAmount, 2) }} {{ $currencySymbol }}
                                </span>
                            </div>
                        @endif
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $hasDiscount ? __('Discounted Price') : __('Price') }}</p>
                                <div class="mt-1 flex flex-wrap items-end gap-2">
                                    <p class="break-all font-display text-3xl font-bold tracking-[-0.03em] text-primary dark:text-white sm:text-4xl">{{ number_format($currentPrice, 2) }}</p>
                                    <span class="pb-1 text-sm font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $currencySymbol }}</span>
                                </div>
                            </div>
                            @if ($hasDiscount)
                                <div class="pb-1">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Old Price') }}</p>
                                    <p class="mt-1 text-lg font-semibold text-rose-600 line-through dark:text-rose-400">{{ number_format($basePrice, 2) }} {{ $currencySymbol }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4">
                            <label for="purchase-qty" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('Quantity') }}</label>
                            <div data-qty-stepper class="mt-2 flex items-center overflow-hidden rounded-xl border border-slate-200">
                                <button type="button" data-qty-minus class="qty-anim-btn inline-flex h-11 w-11 items-center justify-center text-slate-600 transition duration-150 hover:bg-slate-50 hover:text-primary active:scale-90 dark:hover:bg-slate-800 dark:hover:text-white">-</button>
                                <input id="purchase-qty" type="text" inputmode="numeric" value="1" min="1" max="{{ $maxPurchasableQuantity }}" data-max-quantity="{{ $maxPurchasableQuantity }}" class="h-11 w-full border-0 bg-white text-center text-sm font-semibold text-slate-900 focus:ring-0 dark:bg-slate-900">
                                <button type="button" data-qty-plus class="qty-anim-btn inline-flex h-11 w-11 items-center justify-center text-slate-600 transition duration-150 hover:bg-slate-50 hover:text-primary active:scale-90 dark:hover:bg-slate-800 dark:hover:text-white">+</button>
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
                                        <button type="submit" class="js-add-cart-button inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-navy-raised hover:shadow-lg hover:shadow-primary/25 active:translate-y-0 active:scale-[0.98] disabled:cursor-wait disabled:opacity-80">
                                            {{ __('Add to Cart') }}
                                        </button>
                                        <button
                                            type="submit"
                                            formaction="{{ route('checkout.buy-now', $product) }}"
                                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10 active:translate-y-0 active:scale-[0.98] dark:border-slate-700 dark:hover:border-slate-600 dark:hover:bg-slate-800"
                                        >
                                            {{ __('Buy Now') }}
                                        </button>
                                    </form>
                                    @if (!empty($isWishlisted))
                                        <form action="{{ route('user.wishlist.destroy', $product) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700 transition duration-200 hover:-translate-y-0.5 hover:border-rose-400 hover:bg-rose-50 hover:shadow-md hover:shadow-rose-900/10 active:translate-y-0 active:scale-[0.98] dark:border-rose-900/50 dark:text-rose-300 dark:hover:border-rose-800 dark:hover:bg-rose-950/30">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="m12 20.25-1.45-1.32C5.4 14.36 2.25 11.5 2.25 7.97c0-2.48 1.95-4.47 4.43-4.47 1.4 0 2.75.65 3.57 1.66.82-1.01 2.17-1.66 3.57-1.66 2.48 0 4.43 1.99 4.43 4.47 0 3.53-3.15 6.39-8.3 10.96L12 20.25Z" />
                                                </svg>
                                                <span>{{ __('Remove from Wishlist') }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('user.wishlist.store', $product) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10 active:translate-y-0 active:scale-[0.98] dark:border-slate-700 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 20.25-1.45-1.32C5.4 14.36 2.25 11.5 2.25 7.97c0-2.48 1.95-4.47 4.43-4.47 1.4 0 2.75.65 3.57 1.66.82-1.01 2.17-1.66 3.57-1.66 2.48 0 4.43 1.99 4.43 4.47 0 3.53-3.15 6.39-8.3 10.96L12 20.25Z" />
                                                </svg>
                                                <span>{{ __('Add to Wishlist') }}</span>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-muted">
                                        {{ __('Currently unavailable') }}
                                    </button>
                                    @if (!empty($isBackInStockSubscribed))
                                        <form action="{{ route('shop.back-in-stock.destroy', $product) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-emerald-300 px-4 py-3 text-sm font-semibold text-emerald-700 transition duration-200 hover:-translate-y-0.5 hover:border-emerald-400 hover:bg-emerald-50 hover:shadow-md hover:shadow-emerald-900/10 active:translate-y-0 active:scale-[0.98] dark:border-emerald-900/50 dark:text-emerald-300 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/30">
                                                {{ __('Notification enabled') }}
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('shop.back-in-stock.store', $product) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10 active:translate-y-0 active:scale-[0.98] dark:border-slate-700 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                                                {{ __('Notify me when available') }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            @else
                                {{-- Filling a cart needs no account: it belongs to the session
                                     until the visitor signs in. Buying now does, because it
                                     goes straight to checkout. --}}
                                @if ($inStock)
                                    <form action="{{ route('cart.add', $product) }}" method="POST" id="purchase-form" class="js-add-cart-form space-y-2.5">
                                        @csrf
                                        <input type="hidden" name="quantity" id="purchase-qty-hidden-guest" value="1">
                                        <button type="submit" class="js-add-cart-button inline-flex w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-navy-raised hover:shadow-lg hover:shadow-primary/25 active:translate-y-0 active:scale-[0.98] disabled:cursor-wait disabled:opacity-80">
                                            {{ __('Add to Cart') }}
                                        </button>
                                    </form>
                                @else
                                    <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-muted">
                                        {{ __('Currently unavailable') }}
                                    </button>
                                @endif
                                <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10 active:translate-y-0 active:scale-[0.98] dark:border-slate-700 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                                    <span>{{ $inStock ? __('Login for wishlist') : __('Login for stock notification') }}</span>
                                </a>
                            @endauth

                        </div>
                    </section>
                </article>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
                <h2 class="text-base font-semibold text-slate-900">{{ __('Product Details') }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    {{ $description ?: 'Reliable auto spare part selected for consistent quality and fit.' }}
                </p>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                    <li>{{ __('Original quality standards') }}</li>
                    <li>{{ __('Carefully packed before dispatch') }}</li>
                    <li>{{ __('Verified for category-level compatibility') }}</li>
                </ul>
            </article>

            <article class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
                <h2 class="text-base font-semibold text-slate-900">{{ __('Specifications & Shipping') }}</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">{{ __('SKU') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $sku }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">{{ __('OEM Number') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $oem }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">{{ __('Part Number') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $partNumber }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">{{ __('Brand') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $brand }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">{{ __('Category') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $categoryName }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">{{ __('Warranty') }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $warranty }}</dd>
                    </div>
                    <div class="pt-1 text-slate-600">
                        {{ __('Fast shipping with trusted delivery partners. 7-day return policy for eligible items.') }}
                    </div>
                </dl>
            </article>
        </section>

        <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Customer Reviews') }}</p>
                    <h2 class="mt-1 text-xl font-semibold tracking-[-0.02em] text-slate-950">{{ __('Real buyer feedback') }}</h2>
                </div>
                @if (($reviewCount ?? 0) > 0)
                    <div class="flex items-center gap-2 pb-0.5">
                        <div class="flex items-center gap-0.5" aria-hidden="true">
                            @for ($rating = 1; $rating <= 5; $rating++)
                                <svg class="h-4 w-4 {{ $rating <= (int) round((float) ($averageRating ?? 0)) ? 'text-accent' : 'text-slate-300 dark:text-slate-700' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.1 2.3c.3-.9 1.5-.9 1.8 0l1.4 4.2h4.4c.9 0 1.3 1.2.6 1.8l-3.6 2.6 1.4 4.2c.3.9-.7 1.6-1.5 1.1L10 13.6l-3.6 2.6c-.8.5-1.8-.2-1.5-1.1l1.4-4.2-3.6-2.6c-.7-.6-.3-1.8.6-1.8h4.4l1.4-4.2Z" />
                                </svg>
                            @endfor
                        </div>
                        <p class="text-sm font-semibold text-slate-950">
                            {{ number_format((float) $averageRating, 1) }}
                            <span class="font-medium text-slate-500">· {{ $reviewCount }} {{ __('reviews') }}</span>
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($reviews as $review)
                    @php
                        $reviewerName = trim((string) ($review->user?->name ?? ''));
                        $reviewerFirstName = $reviewerName !== '' ? \Illuminate\Support\Str::before($reviewerName, ' ') : __('Customer');
                    @endphp
                    <article class="relative flex h-full flex-col gap-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-primary/20 hover:shadow-md hover:shadow-slate-900/5 dark:bg-slate-950 dark:hover:border-primary/30 dark:hover:shadow-black/20">
                        <span class="pointer-events-none absolute -top-2 end-4 select-none font-serif text-6xl font-bold leading-none text-slate-200/90 dark:text-slate-800" aria-hidden="true">&rdquo;</span>

                        <div class="flex items-center gap-0.5" aria-label="{{ __(':rating out of 5', ['rating' => (int) $review->rating]) }}">
                            @for ($rating = 1; $rating <= 5; $rating++)
                                <svg class="h-3.5 w-3.5 {{ $rating <= (int) $review->rating ? 'text-accent' : 'text-slate-300 dark:text-slate-700' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M9.1 2.3c.3-.9 1.5-.9 1.8 0l1.4 4.2h4.4c.9 0 1.3 1.2.6 1.8l-3.6 2.6 1.4 4.2c.3.9-.7 1.6-1.5 1.1L10 13.6l-3.6 2.6c-.8.5-1.8-.2-1.5-1.1l1.4-4.2-3.6-2.6c-.7-.6-.3-1.8.6-1.8h4.4l1.4-4.2Z" />
                                </svg>
                            @endfor
                        </div>

                        <div class="flex-1 space-y-1.5">
                            @if ($review->title)
                                <p class="text-sm font-semibold text-slate-900">{{ $review->title }}</p>
                            @endif
                            @if ($review->comment)
                                <p class="text-sm leading-6 text-slate-600">{{ $review->comment }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 border-t border-slate-200/80 pt-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold uppercase text-white shadow-sm">{{ \Illuminate\Support\Str::substr($reviewerFirstName, 0, 1) }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $reviewerFirstName }}</p>
                                <p class="truncate text-xs text-slate-500">
                                    @if ($review->reviewed_at || $review->created_at)
                                        {{ optional($review->reviewed_at ?? $review->created_at)->format('M d, Y') }} ·
                                    @endif
                                    {{ __('Verified') }}
                                </p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500 dark:border-slate-700 sm:col-span-2 lg:col-span-3">
                        {{ __('No reviews yet. Delivered customers can be the first to rate this product from their order page.') }}
                    </div>
                @endforelse
            </div>
        </section>

        @if (($recentlyViewedProducts ?? collect())->isNotEmpty())
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Recently viewed') }}</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-[-0.02em] text-slate-950">{{ __('Your product history') }}</h2>
                    </div>
                    <a href="{{ route('shop.index') }}" class="text-sm font-semibold text-primary transition hover:text-navy-raised dark:text-slate-200 dark:hover:text-white">
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

@endsection
