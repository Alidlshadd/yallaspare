@props([
    'products',
    'wishlistedProductIds' => [],
    'emptyTitle' => null,
    'emptyBody' => null,
])

@if ($products->isEmpty())
    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 text-center shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-8">
        <h2 class="text-xl font-semibold tracking-[-0.03em] text-slate-950 sm:text-2xl">{{ $emptyTitle ?: __('Nothing here yet') }}</h2>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600">{{ $emptyBody ?: __('We have not listed parts for this yet. Browse the full catalogue or tell us what you are looking for.') }}</p>
        <div class="mt-5 flex flex-wrap justify-center gap-2">
            <a href="{{ route('shop.index') }}" class="font-display inline-flex items-center justify-center rounded-2xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-raised">
                {{ __('Browse all parts') }}
            </a>
            <a href="{{ route('legal.contact') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:bg-slate-950 dark:hover:bg-slate-800">
                {{ __('Ask us for it') }}
            </a>
        </div>
    </section>
@else
    <div class="flex flex-wrap items-center justify-between gap-2 px-1">
        <p class="text-sm text-slate-600">
            <span class="font-semibold text-slate-950">{{ number_format($products->total()) }}</span>
            {{ __('Products') }}
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($products as $product)
            <x-product-card
                :product="$product"
                :compact="true"
                :show-wishlist="true"
                :is-wishlisted="in_array((int) $product->id, $wishlistedProductIds, true)"
            />
        @endforeach
    </div>

    @if ($products->hasPages())
        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10">
            {{ $products->links() }}
        </div>
    @endif
@endif
