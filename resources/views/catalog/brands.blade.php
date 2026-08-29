@extends('layouts.user')

@section('title', __('Spare part brands') . ' · ' . __('Yalla Spare'))
@section('meta_description', __('Every spare part brand stocked at Yalla Spare, with the parts we carry from each one.'))

@push('head')
    @include('catalog.partials.head')
@endpush

@section('content')
    <div class="space-y-4 sm:space-y-5">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6 lg:p-8">
            <x-catalog.breadcrumbs :breadcrumbs="$breadcrumbs" />

            <h1 class="mt-4 text-2xl font-semibold tracking-[-0.03em] text-slate-950 sm:text-3xl">{{ __('Spare part brands') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                {{ __('The manufacturers we stock. Open a brand to see every part we carry from it.') }}
            </p>

            @if ($stockedBrandCount > 0)
                <p class="mt-4 inline-flex items-center rounded-full border border-slate-200/80 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300">
                    {{ $stockedBrandCount === 1 ? __('1 brand in stock') : __(':count brands in stock', ['count' => number_format($stockedBrandCount)]) }}
                </p>
            @endif
        </section>

        @if ($brands === [])
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 text-center shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-8">
                <h2 class="text-xl font-semibold tracking-[-0.03em] text-slate-950">{{ __('No brands listed yet') }}</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Browse the full catalogue while we organise the brand pages.') }}</p>
                <a href="{{ route('shop.index') }}" class="font-display mt-5 inline-flex items-center justify-center rounded-2xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-raised">
                    {{ __('Browse all parts') }}
                </a>
            </section>
        @else
            <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($brands as $brand)
                    <a
                        href="{{ route('catalog.brand', $brand['slug']) }}"
                        class="group flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md dark:bg-slate-900 dark:shadow-black/10"
                    >
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                            @if ($brand['logo_path'])
                                <img src="{{ asset('storage/' . ltrim((string) $brand['logo_path'], '/')) }}" alt="" class="max-h-10 max-w-10 object-contain">
                            @else
                                <span aria-hidden="true" class="text-sm font-semibold uppercase text-slate-400">{{ mb_substr($brand['name'], 0, 2) }}</span>
                            @endif
                        </span>

                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-950 group-hover:text-primary dark:group-hover:text-white">{{ $brand['name'] }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">
                                @if ($brand['products_count'] > 0)
                                    {{ $brand['products_count'] === 1 ? __('1 part') : __(':count parts', ['count' => number_format($brand['products_count'])]) }}
                                @else
                                    {{ __('Coming soon') }}
                                @endif
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
