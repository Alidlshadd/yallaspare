@extends('layouts.user')

@section('title', $category->name)
@section('meta_description', $category->localized_description ?: __('Browse products in this category.'))

@section('content')
    <div class="space-y-7">
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/10">
            <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-stretch lg:p-8">
                <div class="flex flex-col justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                            <a href="{{ route('categories.index') }}" class="font-semibold text-[#070740] transition hover:text-[#0a0a55] dark:text-slate-200 dark:hover:text-white">{{ __('Categories') }}</a>
                            <span>/</span>
                            <span>{{ $category->name }}</span>
                        </div>

                        <h1 class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-slate-950 dark:text-white">{{ $category->name }}</h1>

                        @if (filled($category->localized_description))
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $category->localized_description }}</p>
                        @else
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Products available in this category.') }}</p>
                        @endif
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ number_format((int) ($category->active_products_count ?? $products->total())) }} {{ __('products') }}
                        </span>
                        <a
                            href="{{ route('shop.index', ['category' => $category->slug ?: $category->id]) }}"
                            class="inline-flex rounded-full bg-[#070740] px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-[#0a0a55]"
                        >
                            {{ __('Use in shop filter') }}
                        </a>
                    </div>
                </div>

                <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-3xl bg-slate-100 p-5 text-[#070740] dark:bg-slate-800 dark:text-slate-200">
                    @if ($categoryImageUrl)
                        <img src="{{ $categoryImageUrl }}" alt="{{ $category->name }}" class="h-full max-h-64 w-full object-contain" loading="lazy">
                    @else
                        <svg class="h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6M9 14h4" />
                        </svg>
                    @endif
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('Products') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Available items from this category.') }}</p>
                </div>
                <a href="{{ route('categories.index') }}" class="text-sm font-semibold text-[#070740] transition hover:text-[#0a0a55] dark:text-slate-200 dark:hover:text-white">
                    {{ __('All categories') }}
                </a>
            </div>

            @if ($products->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                    <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ __('No products found') }}</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('There are no active products in this category yet.') }}</p>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
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
        </section>
    </div>
@endsection
