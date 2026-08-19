@php
    $row = is_array($fitmentRow ?? null) ? $fitmentRow : [];
    $rowIndex = (string) ($fitmentIndex ?? 0);
    $selectedBrandId = (string) ($row['vehicle_brand_id'] ?? '');
    $selectedFamilyId = (string) ($row['vehicle_model_family_id'] ?? '');
    $selectedModelId = (string) ($row['vehicle_model_id'] ?? '');
@endphp

<section class="vf-fitment-card" data-fitment-row data-fitment-index="{{ $rowIndex }}">
    <div class="vf-fitment-card-head">
        <span class="inline-flex items-center gap-2">
            <span class="vf-fitment-number" data-fitment-row-number>{{ is_numeric($rowIndex) ? ((int) $rowIndex + 1) : 1 }}</span>
            <span class="text-[11px] font-bold uppercase tracking-[.12em] text-slate-600">{{ __('Vehicle Fitment') }}</span>
        </span>
        <button type="button" class="vf-btn danger sm" data-remove-fitment-row>
            <i class="fas fa-trash text-[9px]"></i> {{ __('Remove') }}
        </button>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <div>
            <label class="vf-lbl">{{ __('Vehicle Brand') }}</label>
            <select name="fitments[{{ $rowIndex }}][vehicle_brand_id]" required data-admin-vehicle-brand class="vf-sel">
                <option value="">{{ __('Select brand') }}</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected($selectedBrandId === (string) $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="vf-lbl">{{ __('Model Family') }}</label>
            <select name="fitments[{{ $rowIndex }}][vehicle_model_family_id]" required data-admin-vehicle-family class="vf-sel">
                <option value="">{{ __('Select family') }}</option>
                @foreach($brands as $brand)
                    @foreach($brand->modelFamilies as $family)
                        <option value="{{ $family->id }}" @selected($selectedFamilyId === (string) $family->id)>{{ $family->name }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <div>
            <label class="vf-lbl">{{ __('Vehicle Variant') }}</label>
            <select name="fitments[{{ $rowIndex }}][vehicle_model_id]" required data-admin-vehicle-model class="vf-sel">
                <option value="">{{ __('Select variant') }}</option>
                @foreach($brands as $brand)
                    @foreach($brand->models as $model)
                        <option value="{{ $model->id }}" @selected($selectedModelId === (string) $model->id)
                                data-family-id="{{ $model->vehicle_model_family_id }}" data-engines='@json($model->engineTypes->pluck('name')->values())'
                                data-year-from="{{ $model->production_start_year }}" data-year-to="{{ $model->production_end_year }}">
                            {{ $model->name }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <div>
            <label class="vf-lbl">{{ __('Engine') }}</label>
            <select name="fitments[{{ $rowIndex }}][engine]" class="vf-sel" data-admin-engine>
                <option value="">{{ __('Any configured petrol engine') }}</option>
                @if(trim((string) ($row['engine'] ?? '')) !== '')
                    <option value="{{ $row['engine'] }}" selected>{{ $row['engine'] }}</option>
                @endif
            </select>
        </div>
        <div>
            <label class="vf-lbl">{{ __('Year From') }}</label>
            <input name="fitments[{{ $rowIndex }}][year_from]" type="number" min="1900" max="2100"
                   value="{{ $row['year_from'] ?? '' }}" placeholder="{{ __('Any') }}" class="vf-inp" data-admin-year-from>
        </div>
        <div>
            <label class="vf-lbl">{{ __('Year To') }}</label>
            <input name="fitments[{{ $rowIndex }}][year_to]" type="number" min="1900" max="2100"
                   value="{{ $row['year_to'] ?? '' }}" placeholder="{{ __('Any') }}" class="vf-inp" data-admin-year-to>
        </div>
        <div>
            <label class="vf-lbl">{{ __('Notes') }}</label>
            <input name="fitments[{{ $rowIndex }}][notes]" maxlength="255" value="{{ $row['notes'] ?? '' }}"
                   placeholder="{{ __('Optional fitment notes') }}" class="vf-inp">
        </div>
    </div>
</section>
