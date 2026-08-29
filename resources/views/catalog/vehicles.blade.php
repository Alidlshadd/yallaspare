@extends('layouts.user')

@section('title', __('Shop by car') . ' · ' . __('Yalla Spare'))
@section('meta_description', __('Find spare parts by car. Pick your make, then your model, and see only the parts that fit it.'))

@push('head')
    @include('catalog.partials.head')
@endpush

@section('content')
    <div class="space-y-4 sm:space-y-5">
        <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-6 lg:p-8">
            <x-catalog.breadcrumbs :breadcrumbs="$breadcrumbs" />

            <h1 class="mt-4 text-2xl font-semibold tracking-[-0.03em] text-slate-950 sm:text-3xl">{{ __('Shop by car') }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                {{ __('Pick the make, then the model. You will only see parts recorded as fitting that car.') }}
            </p>
        </section>

        @if ($makes === [])
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 text-center shadow-sm shadow-slate-900/5 dark:bg-slate-900 dark:shadow-black/10 sm:rounded-3xl sm:p-8">
                <h2 class="text-xl font-semibold tracking-[-0.03em] text-slate-950">{{ __('No cars listed yet') }}</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Browse the full catalogue while we build the vehicle pages.') }}</p>
                <a href="{{ route('shop.index') }}" class="font-display mt-5 inline-flex items-center justify-center rounded-2xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-raised">
                    {{ __('Browse all parts') }}
                </a>
            </section>
        @else
            <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($makes as $make)
                    <a
                        href="{{ route('catalog.vehicle-brand', $make['slug']) }}"
                        class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md dark:bg-slate-900 dark:shadow-black/10"
                    >
                        <span class="block text-sm font-semibold text-slate-950 group-hover:text-primary dark:group-hover:text-white">{{ $make['name'] }}</span>
                        <span class="mt-1 block text-xs text-slate-500">
                            @if ($make['fitted_models_count'] > 0)
                                {{ $make['fitted_models_count'] === 1 ? __('1 model with parts') : __(':count models with parts', ['count' => number_format($make['fitted_models_count'])]) }}
                            @elseif ($make['models_count'] > 0)
                                {{ __('Coming soon') }}
                            @else
                                {{ __('No models yet') }}
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
