@extends('layouts.store')

@section('title', ($systemSettings['site_name'] ?? 'YallaSpare') . ' | Shop')

@section('content')
    <section class="store-rise rounded-[28px] border border-white/70 bg-white/80 p-6 shadow-[0_20px_70px_-35px_rgba(16,39,35,0.4)] backdrop-blur sm:p-8">
        <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr] lg:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-700">Auto Parts Marketplace</p>
                <h1 class="store-title mt-3 text-3xl font-bold text-slate-900 sm:text-4xl">
                    Fast spare parts sourcing for garages and drivers
                </h1>
                <p class="mt-3 max-w-2xl text-sm text-slate-600 sm:text-base">
                    Search by SKU, brand, or product name. Stock and pricing are updated from your live inventory.
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-baseline justify-between">
                    <p class="text-sm font-semibold text-slate-700">Available Products</p>
                    <p class="store-title text-3xl font-bold text-emerald-700">{{ number_format($products->total()) }}</p>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    Pricing unit: {{ $currencySymbol }}
                </p>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-[260px_1fr]">
        <aside class="store-rise rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm" style="animation-delay: 110ms;">
            <form action="{{ route('shop.index') }}" method="GET" class="space-y-4">
                <div>
                    <label for="q" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Search</label>
                    <input
                        id="q"
                        name="q"
                        value="{{ $search }}"
                        placeholder="SKU, brand, or product"
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label for="category" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Category</label>
                    <select
                        id="category"
                        name="category"
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                    >
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) $activeCategory === (int) $category->id)>
                                {{ $category->name_en }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="sort" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Sort</label>
                    <select
                        id="sort"
                        name="sort"
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                    >
                        <option value="latest" @selected($sort === 'latest')>Latest</option>
                        <option value="price_asc" @selected($sort === 'price_asc')>Price: Low to High</option>
                        <option value="price_desc" @selected($sort === 'price_desc')>Price: High to Low</option>
                        <option value="stock_desc" @selected($sort === 'stock_desc')>Most in stock</option>
                    </select>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="flex-1 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Apply
                    </button>
                    <a href="{{ route('shop.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </aside>

        <div class="space-y-4">
            @if ($products->count() === 0)
                <div class="store-rise rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <h2 class="store-title text-2xl font-bold text-slate-900">No products found</h2>
                    <p class="mt-2 text-sm text-slate-600">Try changing your filters or clearing search.</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        <article class="store-rise rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg" style="animation-delay: {{ 90 + (($loop->index % 6) * 60) }}ms;">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $product->brand ?: 'Generic' }}</p>
                                    <h3 class="store-title mt-1 text-lg font-bold text-slate-900">{{ $product->name_en }}</h3>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                    {{ $product->sku ?: 'No SKU' }}
                                </span>
                            </div>

                            <p class="line-clamp-2 min-h-[40px] text-sm text-slate-600">
                                {{ $product->description_en ?: 'High-quality replacement part for daily and professional usage.' }}
                            </p>

                            <div class="mt-4 flex items-end justify-between">
                                <div>
                                    <p class="text-xs text-slate-500">Price</p>
                                    <p class="store-title text-2xl font-bold text-emerald-700">
                                        {{ number_format($product->priceFor(auth()->user()), 2) }}
                                        <span class="text-sm font-semibold text-emerald-600">{{ $currencySymbol }}</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-slate-500">Stock</p>
                                    <p class="text-sm font-semibold {{ $product->stock_quantity > 0 ? 'text-slate-700' : 'text-rose-700' }}">
                                        {{ $product->stock_quantity > 0 ? $product->stock_quantity . ' available' : 'Out of stock' }}
                                    </p>
                                </div>
                            </div>

                            @if ($product->stock_quantity > 0)
                                <form action="{{ route('cart.add', $product) }}" method="POST" class="mt-4">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl bg-[var(--store-accent)] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[var(--store-accent-dark)]">
                                        Add to Cart
                                    </button>
                                </form>
                            @else
                                <button type="button" disabled class="mt-4 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-400">
                                    Currently unavailable
                                </button>
                            @endif
                        </article>
                    @endforeach
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
