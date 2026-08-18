{{-- One engine in the repeater. Expects: index, row, fuelTypes.
     `index` is __INDEX__ inside the clone template and is rewritten in JS. --}}
@php
    $row = is_array($row ?? null) ? $row : [];
    $fuel = (string) ($row['fuel_type'] ?? '');
    $size = $row['engine_size'] ?? '';
    $aspiration = (string) ($row['aspiration'] ?? '');
    // Only an electric row hides the displacement fields; an empty row still
    // shows them so the operator sees what a row is made of.
    $hidesDisplacement = $fuel === 'electric';
@endphp

<div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-950/50" data-engine-row>
    <div class="grid gap-3 sm:grid-cols-[minmax(0,1.1fr)_minmax(0,.8fr)_minmax(0,.9fr)_auto] sm:items-end">
        <div>
            <label class="vf-lbl">{{ __('Fuel Type') }}</label>
            <select name="engines[{{ $index }}][fuel_type]" class="vf-sel" data-engine-fuel required>
                <option value="">{{ __('Select fuel type') }}</option>
                @foreach($fuelTypes as $fuelType)
                    <option value="{{ $fuelType['value'] }}" data-has-displacement="{{ $fuelType['has_displacement'] ? '1' : '0' }}" @selected($fuel === $fuelType['value'])>{{ $fuelType['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div data-engine-displacement @if($hidesDisplacement) hidden @endif>
            <label class="vf-lbl">{{ __('Engine Size') }}</label>
            <input
                name="engines[{{ $index }}][engine_size]"
                type="number" step="0.1" min="0.1" max="99.9" inputmode="decimal"
                value="{{ $size }}"
                placeholder="2.0"
                class="vf-inp"
                data-engine-size
            >
        </div>

        <div data-engine-aspiration @if($hidesDisplacement) hidden @endif>
            <label class="vf-lbl">{{ __('Aspiration') }}</label>
            <select name="engines[{{ $index }}][aspiration]" class="vf-sel">
                <option value="" @selected($aspiration === '')>{{ __('Naturally Aspirated') }}</option>
                <option value="turbo" @selected($aspiration === 'turbo')>{{ __('Turbo') }}</option>
            </select>
        </div>

        <button type="button" class="vf-btn danger sm" data-engine-remove aria-label="{{ __('Remove Engine') }}">
            <i class="fas fa-trash text-[9px]"></i>
        </button>
    </div>

    <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400" data-engine-electric-note @unless($hidesDisplacement) hidden @endunless>
        {{ __('An electric variant is saved without an engine size.') }}
    </p>
</div>
