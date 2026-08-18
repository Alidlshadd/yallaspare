{{-- Shared by the create and edit pages. Expects: mode, model, brands,
     families, fuelTypes. --}}
@php
    $mode = $mode ?? 'create';
    $model = $model ?? null;
    $brands = $brands ?? collect();
    $families = $families ?? collect();
    $fuelTypes = $fuelTypes ?? [];
    $isEdit = $mode === 'edit';

    // On a failed submit the operator's rows come back from old(); otherwise
    // show what is stored, and fall back to one empty row on a fresh form.
    $engineRows = old('engines');
    if (! is_array($engineRows)) {
        $engineRows = $isEdit
            ? $model->engineTypes->map(fn ($engine) => [
                'fuel_type' => $engine->fuel_type,
                'engine_size' => $engine->engine_size,
                'aspiration' => $engine->aspiration,
            ])->all()
            : [];
    }

    $brandFamilyMap = $brands->mapWithKeys(fn ($brand) => [
        (string) $brand->id => $brand->modelFamilies
            ->map(fn ($family) => ['id' => (int) $family->id, 'name' => $family->localizedName()])
            ->values()
            ->all(),
    ]);

    $currentImage = $isEdit && $model->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($model->image_path)
        ? asset('storage/'.ltrim($model->image_path, '/'))
        : null;
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('admin.vehicle-fitments.models.update', $model) : route('admin.vehicle-fitments.models.store') }}"
    enctype="multipart/form-data"
    class="space-y-5"
    data-variant-form
    @unless($isEdit) data-family-map='@json($brandFamilyMap)' @endunless
>
    @csrf
    @if($isEdit) @method('PATCH') @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-5">
            {{-- ── Where the variant belongs ── --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Vehicle') }}</h2>
                <p class="mb-4 mt-0.5 text-[11.5px] text-slate-500 dark:text-slate-400">{{ __('A variant belongs to one model family, and a family belongs to one brand.') }}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    @if($isEdit)
                        <div>
                            <span class="vf-lbl">{{ __('Brand') }}</span>
                            <p class="flex h-[38px] items-center rounded-[10px] border border-slate-200 bg-slate-100 px-3 text-[13px] font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ $model->brand?->name }}
                            </p>
                        </div>
                        <div>
                            <label class="vf-lbl" for="vf-family">{{ __('Model Family') }}</label>
                            <select id="vf-family" name="vehicle_model_family_id" required class="vf-sel" @error('vehicle_model_family_id') aria-invalid="true" @enderror>
                                @foreach($families as $family)
                                    <option value="{{ $family->id }}" @selected(old('vehicle_model_family_id', $model->vehicle_model_family_id) == $family->id)>{{ $family->localizedName() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div>
                            <label class="vf-lbl" for="vf-brand">{{ __('Brand') }}</label>
                            <select id="vf-brand" name="vehicle_brand_id" required class="vf-sel" data-family-brand @error('vehicle_brand_id') aria-invalid="true" @enderror>
                                <option value="">{{ __('Select brand') }}</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected(old('vehicle_brand_id') == $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="vf-lbl" for="vf-family">{{ __('Model Family') }}</label>
                            <select id="vf-family" name="vehicle_model_family_id" class="vf-sel" data-family-select>
                                <option value="">{{ __('Select existing family') }}</option>
                            </select>
                        </div>
                    @endif
                </div>

                @unless($isEdit)
                    <div class="mt-4 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700" data-new-family-block>
                        <p class="vf-lbl">{{ __('Or create a new model family') }}</p>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <input name="new_family_name_en" value="{{ old('new_family_name_en') }}" maxlength="120" placeholder="{{ __('Family Name — English') }}" class="vf-inp" aria-label="{{ __('Family Name — English') }}" data-new-family>
                            <input name="new_family_name_ar" value="{{ old('new_family_name_ar') }}" maxlength="120" dir="rtl" placeholder="{{ __('Family Name — Arabic') }}" class="vf-inp" aria-label="{{ __('Family Name — Arabic') }}">
                            <input name="new_family_name_ku" value="{{ old('new_family_name_ku') }}" maxlength="120" dir="rtl" placeholder="{{ __('Family Name — Kurdish') }}" class="vf-inp" aria-label="{{ __('Family Name — Kurdish') }}">
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ __('Leave empty to use the family selected above.') }}</p>
                    </div>
                @endunless
            </section>

            {{-- ── Names and years ── --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Variant Details') }}</h2>
                <p class="mb-4 mt-0.5 text-[11.5px] text-slate-500 dark:text-slate-400">{{ __('Arabic and Kurdish names are optional; the English name is shown when one is missing.') }}</p>

                <div class="grid gap-4">
                    <div>
                        <label class="vf-lbl" for="vf-name-en">{{ __('Variant Name — English') }}</label>
                        <input id="vf-name-en" name="name_en" value="{{ old('name_en', $isEdit ? ($model->name_en ?: $model->name) : '') }}" required maxlength="120" class="vf-inp" @error('name_en') aria-invalid="true" @enderror>
                        @error('name_en')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="vf-lbl" for="vf-name-ar">{{ __('Variant Name — Arabic') }}</label>
                            <input id="vf-name-ar" name="name_ar" value="{{ old('name_ar', $isEdit ? $model->name_ar : '') }}" maxlength="120" dir="rtl" class="vf-inp">
                        </div>
                        <div>
                            <label class="vf-lbl" for="vf-name-ku">{{ __('Variant Name — Kurdish') }}</label>
                            <input id="vf-name-ku" name="name_ku" value="{{ old('name_ku', $isEdit ? $model->name_ku : '') }}" maxlength="120" dir="rtl" class="vf-inp">
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="vf-lbl" for="vf-year-from">{{ __('Production Start Year') }}</label>
                            <input id="vf-year-from" name="production_start_year" type="number" min="1900" max="2100" value="{{ old('production_start_year', $isEdit ? $model->production_start_year : '') }}" class="vf-inp" @error('production_start_year') aria-invalid="true" @enderror>
                        </div>
                        <div>
                            <label class="vf-lbl" for="vf-year-to">{{ __('Production End Year') }}</label>
                            <input id="vf-year-to" name="production_end_year" type="number" min="1900" max="2100" value="{{ old('production_end_year', $isEdit ? $model->production_end_year : '') }}" class="vf-inp" @error('production_end_year') aria-invalid="true" @enderror>
                            @error('production_end_year')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Engines ── --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Engines') }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-slate-500 dark:text-slate-400">{{ __('Add one row per engine offered on this variant. Electric variants need no engine size.') }}</p>
                    </div>
                    <button type="button" class="vf-btn sm" data-engine-add>
                        <i class="fas fa-plus text-[9px]"></i> {{ __('Add Engine') }}
                    </button>
                </div>

                @error('engine_types')<p class="mt-3 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror

                <div class="mt-4 space-y-3" data-engine-rows>
                    @foreach($engineRows as $index => $row)
                        @include('admin.vehicle-fitments.partials.engine-row', [
                            'index' => $index,
                            'row' => $row,
                            'fuelTypes' => $fuelTypes,
                        ])
                    @endforeach
                </div>

                <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-400" data-engine-empty @if(count($engineRows) > 0) hidden @endif>
                    {{ __('No engines yet. Products can still be linked to this variant without one.') }}
                </p>

                {{-- The template the Add button clones. Kept out of the form data
                     by the browser, and reindexed in JS on every change. --}}
                <template data-engine-template>
                    @include('admin.vehicle-fitments.partials.engine-row', [
                        'index' => '__INDEX__',
                        'row' => [],
                        'fuelTypes' => $fuelTypes,
                    ])
                </template>
            </section>
        </div>

        {{-- ── Image + actions ── --}}
        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Vehicle Image') }}</h2>
                <p class="mb-4 mt-0.5 text-[11.5px] text-slate-500 dark:text-slate-400">{{ __('JPG, PNG or WEBP up to 2 MB.') }}</p>

                <div data-image-picker>
                    <div class="grid aspect-[4/3] w-full place-items-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-600 dark:bg-slate-950/50">
                        <img src="{{ $currentImage }}" alt="" class="h-full w-full object-cover" data-image-preview @unless($currentImage) hidden @endunless>
                        <span class="flex flex-col items-center gap-2 px-4 text-center text-slate-400" data-image-placeholder @if($currentImage) hidden @endif>
                            <i class="fas fa-car-side text-3xl"></i>
                            <span class="text-[11px] font-semibold">{{ __('No image selected') }}</span>
                        </span>
                    </div>

                    <label class="vf-btn mt-3 w-full cursor-pointer">
                        <i class="fas fa-arrow-up-from-bracket text-[10px]"></i>
                        <span data-image-button-label>{{ $currentImage ? __('Replace Image') : __('Choose Image') }}</span>
                        <input type="file" name="image" class="sr-only" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-image-input>
                    </label>

                    <p class="mt-2 truncate text-[11px] text-slate-500 dark:text-slate-400" data-image-filename></p>
                    @error('image')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror

                    @if($currentImage)
                        <label class="mt-3 flex items-center gap-2 text-[11px] font-semibold text-rose-600">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300">
                            {{ __('Remove current image') }}
                        </label>
                    @endif
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-2">
                    <button class="vf-btn gold w-full">
                        <i class="fas fa-check text-[10px]"></i>
                        {{ $isEdit ? __('Save Changes') : __('Create Variant') }}
                    </button>
                    <a href="{{ route('admin.vehicle-fitments.index') }}" class="vf-btn w-full">{{ __('Cancel') }}</a>
                </div>
            </section>
        </div>
    </div>
</form>
