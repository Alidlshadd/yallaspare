@extends('layouts.user')

@section('title', __(':brand spare parts', ['brand' => $brand->name]) . ' · ' . __('Yalla Spare'))
@section('meta_description', __('Buy genuine :brand spare parts in Iraq. Browse what we stock, check prices and order with delivery to your governorate.', ['brand' => $brand->name]))

@push('head')
    @include('catalog.partials.head')
@endpush

@section('content')
    <div class="space-y-4 sm:space-y-5">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6 lg:p-8">
            <x-catalog.breadcrumbs :breadcrumbs="$breadcrumbs" />

            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                @if ($brand->logo_path)
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <img src="{{ asset('storage/' . ltrim((string) $brand->logo_path, '/')) }}" alt="" class="max-h-12 max-w-12 object-contain">
                    </span>
                @endif

                <div>
                    <h1 class="text-2xl font-semibold tracking-[-0.03em] text-slate-950 sm:text-3xl">{{ __(':brand spare parts', ['brand' => $brand->name]) }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        {{ __('Everything we stock from :brand, priced in Iraqi dinars and delivered across Iraq.', ['brand' => $brand->name]) }}
                    </p>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('shop.index', ['search' => $brand->name]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:bg-slate-950 dark:hover:bg-slate-800">
                    {{ __('Search this brand in the shop') }}
                </a>
                <a href="{{ route('catalog.brands') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:bg-slate-950 dark:hover:bg-slate-800">
                    {{ __('All part brands') }}
                </a>
            </div>
        </section>

        <x-catalog.product-grid
            :products="$products"
            :wishlisted-product-ids="$wishlistedProductIds"
            :empty-title="__('No :brand parts listed yet', ['brand' => $brand->name])"
            :empty-body="__('We are still adding this brand to the catalogue. Tell us the part you need and we will source it.')"
        />
    </div>
@endsection
