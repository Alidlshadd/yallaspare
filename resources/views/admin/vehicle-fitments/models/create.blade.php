<x-app-layout>
    <x-slot name="header">{{ __('Create Vehicle Variant') }}</x-slot>

    <style>
        @include('admin.vehicle-fitments.partials.controls-css')
    </style>

    <div class="mx-auto max-w-6xl space-y-5 px-3 py-5 sm:px-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <nav class="flex items-center gap-2 text-[11px] font-bold text-slate-500 dark:text-slate-400">
                    <a href="{{ route('admin.vehicle-fitments.index') }}" class="hover:text-slate-900 dark:hover:text-white">{{ __('Vehicle Finder') }}</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-slate-900 dark:text-white">{{ __('Create Vehicle Variant') }}</span>
                </nav>
                <h1 class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ __('Create Vehicle Variant') }}</h1>
            </div>
            <a href="{{ route('admin.vehicle-fitments.index') }}" class="vf-btn sm">
                <i class="fas fa-arrow-left text-[9px]"></i> {{ __('Back to Vehicle Finder') }}
            </a>
        </div>

        @if($errors->any())
            <div role="alert" class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-[12.5px] font-semibold text-rose-800 dark:border-rose-500/40 dark:bg-rose-950/30 dark:text-rose-200">
                {{ __('Please correct the highlighted fields.') }}
            </div>
        @endif

        @include('admin.vehicle-fitments.partials.variant-form', [
            'mode' => 'create',
            'brands' => $brands,
            'fuelTypes' => $fuelTypes,
        ])
    </div>

    @include('admin.vehicle-fitments.partials.variant-form-js')
</x-app-layout>
