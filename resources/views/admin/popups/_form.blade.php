@php
    $isEdit = isset($popup);
    $formAction = $isEdit ? route('admin.popups.update', $popup) : route('admin.popups.store');

    $inputBase = 'h-11 w-full px-3 rounded-xl border bg-slate-50 text-sm text-slate-900 placeholder:text-slate-400 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900';
    $inputOk = 'border-slate-200 dark:border-slate-700';
    $inputErr = 'border-rose-300 dark:border-rose-500/50';
    $labelClass = 'block text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5';
    $checkLabel = 'flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300 cursor-pointer';
    $checkBox = 'rounded border-slate-300 text-amber-500 focus:ring-amber-400 dark:border-slate-600 dark:bg-slate-800';

    $selectedPages = collect(old('pages', $popup->pages ?? ['all']))->all();
    $pageOptions = [
        'all' => __('All pages'),
        'home' => __('Home'),
        'shop' => __('Shop'),
        'product' => __('Product detail'),
        'cart' => __('Cart'),
        'checkout' => __('Checkout'),
    ];
    $frequencyValue = old('frequency', $popup->frequency ?? 'once_per_days');

    $previewTitle = old('title_en', $popup->title_en ?? '');
    $previewDescription = old('description_en', $popup->description_en ?? '');
    $previewButtonLabel = old('button_label_en', $popup->button_label_en ?? '');
    $previewImageUrl = $isEdit && !empty($popup->image_path) ? asset('storage/' . ltrim($popup->image_path, '/')) : '';
@endphp

<style>
    .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
    .popup-preview-scrim { background: linear-gradient(180deg, transparent 40%, rgba(6,12,28,.88) 100%); }
</style>

<form method="POST" action="{{ $formAction }}" class="space-y-4" enctype="multipart/form-data">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        <div class="lg:col-span-2 space-y-4">

            {{-- ═════════════ Content ═════════════ --}}
            <div data-animate="fade-up" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 sm:p-6 bento-shadow">
                <div class="flex items-center gap-2.5 mb-5">
                    <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Popup Content') }}</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('English is required; Arabic and Kurdish fall back to English when empty.') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="title_en" class="{{ $labelClass }}">{{ __('Title (English)') }}</label>
                        <input id="title_en" type="text" name="title_en" value="{{ $previewTitle }}" required
                               class="{{ $inputBase }} {{ $errors->has('title_en') ? $inputErr : $inputOk }}">
                        @error('title_en')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="title_ar" class="{{ $labelClass }}">{{ __('Title (Arabic)') }}</label>
                        <input id="title_ar" type="text" name="title_ar" value="{{ old('title_ar', $popup->title_ar ?? '') }}" dir="rtl"
                               class="{{ $inputBase }} {{ $errors->has('title_ar') ? $inputErr : $inputOk }}">
                        @error('title_ar')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="title_ku" class="{{ $labelClass }}">{{ __('Title (Kurdish)') }}</label>
                        <input id="title_ku" type="text" name="title_ku" value="{{ old('title_ku', $popup->title_ku ?? '') }}" dir="rtl"
                               class="{{ $inputBase }} {{ $errors->has('title_ku') ? $inputErr : $inputOk }}">
                        @error('title_ku')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="description_en" class="{{ $labelClass }}">{{ __('Description (English)') }}</label>
                        <textarea id="description_en" name="description_en" rows="3"
                                  class="{{ $inputBase }} {{ $errors->has('description_en') ? $inputErr : $inputOk }} h-auto py-2.5 leading-relaxed">{{ $previewDescription }}</textarea>
                        @error('description_en')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="description_ar" class="{{ $labelClass }}">{{ __('Description (Arabic)') }}</label>
                        <textarea id="description_ar" name="description_ar" rows="3" dir="rtl"
                                  class="{{ $inputBase }} {{ $errors->has('description_ar') ? $inputErr : $inputOk }} h-auto py-2.5 leading-relaxed">{{ old('description_ar', $popup->description_ar ?? '') }}</textarea>
                        @error('description_ar')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="description_ku" class="{{ $labelClass }}">{{ __('Description (Kurdish)') }}</label>
                        <textarea id="description_ku" name="description_ku" rows="3" dir="rtl"
                                  class="{{ $inputBase }} {{ $errors->has('description_ku') ? $inputErr : $inputOk }} h-auto py-2.5 leading-relaxed">{{ old('description_ku', $popup->description_ku ?? '') }}</textarea>
                        @error('description_ku')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ═════════════ Button ═════════════ --}}
            <div data-animate="fade-up" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 sm:p-6 bento-shadow">
                <div class="flex items-center gap-2.5 mb-5">
                    <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Action Button (optional)') }}</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Shown only when both a label and a link are set.') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="button_label_en" class="{{ $labelClass }}">{{ __('Button Label (English)') }}</label>
                        <input id="button_label_en" type="text" name="button_label_en" value="{{ $previewButtonLabel }}"
                               class="{{ $inputBase }} {{ $errors->has('button_label_en') ? $inputErr : $inputOk }}">
                        @error('button_label_en')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="button_label_ar" class="{{ $labelClass }}">{{ __('Button Label (Arabic)') }}</label>
                        <input id="button_label_ar" type="text" name="button_label_ar" value="{{ old('button_label_ar', $popup->button_label_ar ?? '') }}" dir="rtl"
                               class="{{ $inputBase }} {{ $errors->has('button_label_ar') ? $inputErr : $inputOk }}">
                        @error('button_label_ar')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="button_label_ku" class="{{ $labelClass }}">{{ __('Button Label (Kurdish)') }}</label>
                        <input id="button_label_ku" type="text" name="button_label_ku" value="{{ old('button_label_ku', $popup->button_label_ku ?? '') }}" dir="rtl"
                               class="{{ $inputBase }} {{ $errors->has('button_label_ku') ? $inputErr : $inputOk }}">
                        @error('button_label_ku')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-3">
                        <label for="button_url" class="{{ $labelClass }}">{{ __('Button Link') }}</label>
                        <input id="button_url" type="text" name="button_url" value="{{ old('button_url', $popup->button_url ?? '') }}"
                               placeholder="https://... {{ __('or a relative path like /shop') }}"
                               class="{{ $inputBase }} {{ $errors->has('button_url') ? $inputErr : $inputOk }} font-mono">
                        @error('button_url')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- ═════════════ Image ═════════════ --}}
            <div data-animate="fade-up" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 sm:p-6 bento-shadow">
                <div class="flex items-center gap-2.5 mb-5">
                    <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Popup Image') }}</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Covers the whole card; text sits on a dark gradient at the bottom. Without an image a navy brand background is used.') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 {{ $isEdit && !empty($popup->image_path) ? 'md:grid-cols-[minmax(0,1fr)_auto] md:items-start' : '' }}">
                    <div>
                        <label for="image" class="{{ $labelClass }}">{{ $isEdit && !empty($popup->image_path) ? __('Replace Image') : __('Upload Image') }}</label>
                        <input id="image" type="file" name="image" accept="image/*"
                               class="w-full rounded-xl border {{ $errors->has('image') ? $inputErr : $inputOk }} bg-slate-50 px-3 py-2.5 text-sm text-slate-900 file:mr-3 file:rounded-md file:border-0 file:bg-slate-200 file:px-3 file:py-1 file:text-xs file:font-bold dark:bg-slate-800 dark:text-slate-100">
                        @error('image')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    @if($isEdit && !empty($popup->image_path))
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                            <div class="h-16 w-24 rounded-lg border border-slate-200 bg-white grid place-items-center overflow-hidden dark:border-slate-700 dark:bg-slate-950">
                                <img src="{{ $previewImageUrl }}" alt="{{ $popup->title_en }}" class="h-full w-full object-cover">
                            </div>
                            <div>
                                <div class="text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Current Image') }}</div>
                                <label class="mt-1.5 {{ $checkLabel }}">
                                    <input id="remove_image" type="checkbox" name="remove_image" value="1" class="{{ $checkBox }}">
                                    {{ __('Remove') }}
                                </label>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═════════════ Schedule & Targeting ═════════════ --}}
            <div data-animate="fade-up" class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 sm:p-6 bento-shadow">
                <div class="flex items-center gap-2.5 mb-5">
                    <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Schedule & Display Rules') }}</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Leave dates empty for an open-ended popup.') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="starts_at" class="{{ $labelClass }}">{{ __('Start Date') }}</label>
                        <input id="starts_at" type="datetime-local" name="starts_at"
                               value="{{ old('starts_at', isset($popup->starts_at) && $popup->starts_at ? $popup->starts_at->format('Y-m-d\TH:i') : '') }}"
                               class="{{ $inputBase }} {{ $errors->has('starts_at') ? $inputErr : $inputOk }}">
                        @error('starts_at')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ends_at" class="{{ $labelClass }}">{{ __('End Date') }}</label>
                        <input id="ends_at" type="datetime-local" name="ends_at"
                               value="{{ old('ends_at', isset($popup->ends_at) && $popup->ends_at ? $popup->ends_at->format('Y-m-d\TH:i') : '') }}"
                               class="{{ $inputBase }} {{ $errors->has('ends_at') ? $inputErr : $inputOk }}">
                        @error('ends_at')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-2">
                        <span class="{{ $labelClass }}">{{ __('Show On Pages') }}</span>
                        <div class="flex flex-wrap gap-x-5 gap-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3.5 dark:border-slate-700 dark:bg-slate-800/60">
                            @foreach($pageOptions as $value => $label)
                                <label class="{{ $checkLabel }}">
                                    <input type="checkbox" name="pages[]" value="{{ $value }}" class="{{ $checkBox }}"
                                           @checked(in_array($value, $selectedPages, true))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('pages')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="frequency" class="{{ $labelClass }}">{{ __('Display Frequency') }}</label>
                        <select id="frequency" name="frequency" class="{{ $inputBase }} {{ $errors->has('frequency') ? $inputErr : $inputOk }}">
                            <option value="once_per_days" @selected($frequencyValue === 'once_per_days')>{{ __('Once every X days per visitor') }}</option>
                            <option value="once_per_session" @selected($frequencyValue === 'once_per_session')>{{ __('Once per browsing session') }}</option>
                            <option value="every_visit" @selected($frequencyValue === 'every_visit')>{{ __('On every page visit') }}</option>
                        </select>
                        @error('frequency')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="frequency_days" class="{{ $labelClass }}">{{ __('Days Between Shows') }}</label>
                            <input id="frequency_days" type="number" name="frequency_days" min="1" max="365"
                                   value="{{ old('frequency_days', $popup->frequency_days ?? 7) }}"
                                   class="{{ $inputBase }} {{ $errors->has('frequency_days') ? $inputErr : $inputOk }}">
                            @error('frequency_days')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="delay_seconds" class="{{ $labelClass }}">{{ __('Open Delay (seconds)') }}</label>
                            <input id="delay_seconds" type="number" name="delay_seconds" min="0" max="120"
                                   value="{{ old('delay_seconds', $popup->delay_seconds ?? 3) }}"
                                   class="{{ $inputBase }} {{ $errors->has('delay_seconds') ? $inputErr : $inputOk }}">
                            @error('delay_seconds')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $checkLabel }}">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="{{ $checkBox }}"
                                   @checked((bool) old('is_active', $popup->is_active ?? true))>
                            {{ __('Popup is active') }}
                        </label>
                    </div>
                </div>
            </div>

        </div>

        {{-- ═════════════ Live preview ═════════════ --}}
        <div class="lg:col-span-1">
            <div data-animate="fade-up" class="lg:sticky lg:top-6 space-y-2">
                <div data-motion-lift class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl overflow-hidden bento-shadow">
                    <div class="relative h-52 flex items-end overflow-hidden" style="background: linear-gradient(160deg, #0a1533 0%, #1a2f5f 45%, #35558f 100%);">
                        <img id="popupPreviewImg" src="{{ $previewImageUrl }}" alt=""
                             class="absolute inset-0 h-full w-full object-contain {{ $previewImageUrl === '' ? 'hidden' : '' }}">
                        <div class="absolute inset-0 popup-preview-scrim"></div>
                        <div class="relative px-4 pb-4 pt-2 text-white">
                            <div id="popupPreviewTitle" class="font-bold leading-tight" style="font-family: 'Space Grotesk', sans-serif;">
                                {{ $previewTitle !== '' ? $previewTitle : __('Your popup title') }}
                            </div>
                            <p id="popupPreviewDesc" class="text-[11px] text-white/75 mt-1 {{ $previewDescription === '' ? 'hidden' : '' }}">{{ $previewDescription }}</p>
                            <span id="popupPreviewButton" class="inline-block mt-2 rounded-full bg-[#e85d2a] px-3 py-1 text-[11px] font-bold text-white {{ $previewButtonLabel === '' ? 'hidden' : '' }}">{{ $previewButtonLabel }}</span>
                        </div>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-[10.5px] font-bold text-slate-500 dark:text-slate-400">{{ __('Live preview — this is what visitors will see') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═════════════ Actions ═════════════ --}}
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('admin.popups.index') }}"
           class="inline-flex items-center gap-2 h-11 px-4 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
            {{ __('Cancel') }}
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 h-11 px-6 rounded-xl text-xs font-bold text-[#04042a] shadow-md shadow-amber-500/30 transition hover:brightness-105"
                style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ $isEdit ? __('Update Popup') : __('Create Popup') }}
        </button>
    </div>
</form>

<script nonce="{{ $cspNonce }}">
    (() => {
        const titleInput = document.getElementById('title_en');
        const descInput = document.getElementById('description_en');
        const buttonLabelInput = document.getElementById('button_label_en');
        const imageInput = document.getElementById('image');
        const removeImageInput = document.getElementById('remove_image');

        const previewTitle = document.getElementById('popupPreviewTitle');
        const previewDesc = document.getElementById('popupPreviewDesc');
        const previewButton = document.getElementById('popupPreviewButton');
        const previewImg = document.getElementById('popupPreviewImg');

        const fallbackTitle = @json(__('Your popup title'));

        const syncText = () => {
            if (previewTitle) {
                const title = titleInput?.value.trim() || '';
                previewTitle.textContent = title !== '' ? title : fallbackTitle;
            }
            if (previewDesc) {
                const desc = descInput?.value.trim() || '';
                previewDesc.textContent = desc;
                previewDesc.classList.toggle('hidden', desc === '');
            }
            if (previewButton) {
                const label = buttonLabelInput?.value.trim() || '';
                previewButton.textContent = label;
                previewButton.classList.toggle('hidden', label === '');
            }
        };

        [titleInput, descInput, buttonLabelInput].forEach((el) => {
            el?.addEventListener('input', syncText);
        });

        if (imageInput && previewImg) {
            imageInput.addEventListener('change', (event) => {
                const file = event.target.files?.[0];
                if (!file) {
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target?.result || '';
                    previewImg.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            });
        }

        if (removeImageInput && previewImg) {
            removeImageInput.addEventListener('change', () => {
                if (removeImageInput.checked) {
                    previewImg.classList.add('hidden');
                    previewImg.removeAttribute('src');
                }
            });
        }
    })();
</script>
