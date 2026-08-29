@extends('layouts.user')

@php
    $vehicleName = trim($make->name . ' ' . $model->localizedName());
    $productionYears = $model->production_start_year
        ? $model->production_start_year . ' – ' . ($model->production_end_year ?: __('present'))
        : null;
@endphp

@section('title', __(':vehicle spare parts', ['vehicle' => $vehicleName]) . ' · ' . __('Yalla Spare'))
@section('meta_description', __('Spare parts that fit the :vehicle. Check compatibility, see prices in Iraqi dinars and order with delivery to your governorate.', ['vehicle' => $vehicleName]))

@push('head')
    @include('catalog.partials.head')
@endpush

@section('content')
    <div class="space-y-4 sm:space-y-5">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6 lg:p-8">
            <x-catalog.breadcrumbs :breadcrumbs="$breadcrumbs" />

            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                @if ($model->image_path)
                    <span class="flex h-20 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <img src="{{ asset('storage/' . ltrim((string) $model->image_path, '/')) }}" alt="" class="h-full w-full object-cover">
                    </span>
                @endif

                <div>
                    <h1 class="text-2xl font-semibold tracking-[-0.03em] text-slate-950 sm:text-3xl">{{ __(':vehicle spare parts', ['vehicle' => $vehicleName]) }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        {{ __('These parts are recorded as fitting the :vehicle. Open a part to see the exact years and engines it covers.', ['vehicle' => $vehicleName]) }}
                    </p>

                    @if ($productionYears)
                        <p class="mt-2 inline-flex items-center rounded-full border border-slate-200/80 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300">
                            {{ __('Production years: :years', ['years' => $productionYears]) }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('shop.index', ['brand' => $make->name, 'model' => $model->name]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:bg-slate-950 dark:hover:bg-slate-800">
                    {{ __('Filter this car in the shop') }}
                </a>
                <a href="{{ route('catalog.vehicle-brand', $make->slug) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:bg-slate-950 dark:hover:bg-slate-800">
                    {{ __('All :make parts', ['make' => $make->name]) }}
                </a>
            </div>
        </section>

        <x-catalog.product-grid
            :products="$products"
            :wishlisted-product-ids="$wishlistedProductIds"
            :empty-title="__('No parts listed for the :vehicle yet', ['vehicle' => $vehicleName])"
            :empty-body="__('We have not recorded parts for this car yet. Tell us what you need and we will source it.')"
        />

        @if ($siblingModels->isNotEmpty())
            <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6">
                <h2 class="text-sm font-semibold text-slate-950">{{ __('Other :make models', ['make' => $make->name]) }}</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($siblingModels as $sibling)
                        <li>
                            <a
                                href="{{ route('catalog.vehicle-model', [$make->slug, $sibling->slug]) }}"
                                class="inline-flex items-center rounded-full border border-slate-200/80 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-primary/30 hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
                            >
                                {{ $sibling->localizedName() }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
