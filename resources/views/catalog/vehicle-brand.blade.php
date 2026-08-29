@extends('layouts.user')

@section('title', __(':make spare parts', ['make' => $make->name]) . ' · ' . __('Yalla Spare'))
@section('meta_description', __('Spare parts for :make in Iraq. Pick your model to see only the parts that fit it, or browse everything we stock for the make.', ['make' => $make->name]))

@push('head')
    @include('catalog.partials.head')
@endpush

@section('content')
    <div class="space-y-4 sm:space-y-5">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6 lg:p-8">
            <x-catalog.breadcrumbs :breadcrumbs="$breadcrumbs" />

            <h1 class="mt-4 text-2xl font-semibold tracking-[-0.03em] text-slate-950 sm:text-3xl">{{ __(':make spare parts', ['make' => $make->name]) }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                {{ __('Everything we stock for :make. Choose your model below to narrow it down to the parts that fit your car.', ['make' => $make->name]) }}
            </p>

            @php($fittedModels = $models->where('fitments_count', '>', 0))

            @if ($fittedModels->isNotEmpty())
                <h2 class="mt-6 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('Models') }}</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($fittedModels as $vehicleModel)
                        <li>
                            <a
                                href="{{ route('catalog.vehicle-model', [$make->slug, $vehicleModel->slug]) }}"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-primary/30 hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200"
                            >
                                {{ $vehicleModel->localizedName() }}
                                <span class="text-slate-400">{{ number_format($vehicleModel->fitments_count) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <x-catalog.product-grid
            :products="$products"
            :wishlisted-product-ids="$wishlistedProductIds"
            :empty-title="__('No :make parts listed yet', ['make' => $make->name])"
            :empty-body="__('We have not recorded parts for this make yet. Tell us your car and the part you need, and we will source it.')"
        />
    </div>
@endsection
