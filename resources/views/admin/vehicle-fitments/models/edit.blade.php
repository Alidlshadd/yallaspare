<x-app-layout>
    <x-slot name="header">{{ __('Edit Vehicle Variant') }}</x-slot>

    <style>
        @include('admin.vehicle-fitments.partials.controls-css')
    </style>

    <div class="mx-auto max-w-6xl space-y-5 px-3 py-5 sm:px-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <nav class="flex items-center gap-2 text-[11px] font-bold text-slate-500">
                    <a href="{{ route('admin.vehicle-fitments.index') }}" class="hover:text-slate-900 dark:hover:text-white">{{ __('Vehicle Finder') }}</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-slate-900">{{ $model->localizedName() }}</span>
                </nav>
                <h1 class="mt-1 text-xl font-bold text-slate-900">{{ $model->localizedName() }}</h1>
                <p class="mt-1 text-[11.5px] text-slate-500">
                    {{ $model->brand?->name }} · {{ $model->family?->localizedName() }}
                    @if($fitmentCount > 0)
                        · {{ trans_choice('{1} :count fitment rule|[2,*] :count fitment rules', $fitmentCount, ['count' => $fitmentCount]) }}
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.vehicle-fitments.index') }}" class="vf-btn sm">
                <i class="fas fa-arrow-left text-[9px]"></i> {{ __('Back to Vehicle Finder') }}
            </a>
        </div>

        @if($errors->any())
            <div role="alert" class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-[12.5px] font-semibold text-rose-800 dark:border-rose-500/40 dark:text-rose-200">
                {{ __('Please correct the highlighted fields.') }}
            </div>
        @endif

        @if($fitmentCount > 0)
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-[12.5px] text-amber-900 dark:text-amber-200">
                {{ __('Removing an engine here does not remove product fitments that already reference it.') }}
            </div>
        @endif

        @include('admin.vehicle-fitments.partials.variant-form', [
            'mode' => 'edit',
            'model' => $model,
            'families' => $families,
            'fuelTypes' => $fuelTypes,
        ])
    </div>

    @include('admin.vehicle-fitments.partials.variant-form-js')
</x-app-layout>
