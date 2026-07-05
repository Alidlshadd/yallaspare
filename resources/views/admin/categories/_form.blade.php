@php
    $isEdit = isset($category);
    $formAction = $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store');

    $inputBase = 'h-11 w-full px-3 rounded-xl border bg-slate-50 text-sm text-slate-900 placeholder:text-slate-400 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 focus:bg-white dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900';
    $inputOk = 'border-slate-200 dark:border-slate-700';
    $inputErr = 'border-rose-300 dark:border-rose-500/50';
    $labelClass = 'block text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1.5';
@endphp

<style>
    .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
</style>

<form method="POST" action="{{ $formAction }}" class="space-y-4" enctype="multipart/form-data">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- ═════════════ Category details ═════════════ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 sm:p-6 bento-shadow">
        <div class="flex items-center gap-2.5 mb-5">
            <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Category Details') }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Names in all three languages are required.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name_en" class="{{ $labelClass }}">{{ __('Name (English)') }}</label>
                <input id="name_en" type="text" name="name_en" value="{{ old('name_en', $category->name_en ?? '') }}" required
                       class="{{ $inputBase }} {{ $errors->has('name_en') ? $inputErr : $inputOk }}">
                @error('name_en')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name_ar" class="{{ $labelClass }}">{{ __('Name (Arabic)') }}</label>
                <input id="name_ar" type="text" name="name_ar" value="{{ old('name_ar', $category->name_ar ?? '') }}" required dir="rtl"
                       class="{{ $inputBase }} {{ $errors->has('name_ar') ? $inputErr : $inputOk }}">
                @error('name_ar')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name_ku" class="{{ $labelClass }}">{{ __('Name (Kurdish)') }}</label>
                <input id="name_ku" type="text" name="name_ku" value="{{ old('name_ku', $category->name_ku ?? '') }}" required dir="rtl"
                       class="{{ $inputBase }} {{ $errors->has('name_ku') ? $inputErr : $inputOk }}">
                @error('name_ku')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="slug" class="{{ $labelClass }}">{{ __('Slug (optional)') }}</label>
                <input id="slug" type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}"
                       placeholder="{{ __('Auto-generated from English name') }}"
                       class="{{ $inputBase }} {{ $errors->has('slug') ? $inputErr : $inputOk }} font-mono">
                @error('slug')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-4">
            <label for="description" class="{{ $labelClass }}">{{ __('Description') }}</label>
            <textarea id="description" name="description" rows="5" placeholder="{{ __('Optional details...') }}"
                      class="{{ $inputBase }} {{ $errors->has('description') ? $inputErr : $inputOk }} h-auto py-2.5 leading-relaxed">{{ old('description', $category->description ?? '') }}</textarea>
            @error('description')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- ═════════════ Image ═════════════ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-2xl p-5 sm:p-6 bento-shadow">
        <div class="flex items-center gap-2.5 mb-5">
            <div class="h-9 w-9 rounded-xl bg-[#04042a] text-amber-300 grid place-items-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ __('Category Image') }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Shown on the storefront category listing.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 {{ $isEdit && !empty($category->image) ? 'md:grid-cols-[minmax(0,1fr)_auto] md:items-start' : '' }}">
            <div>
                <label for="image" class="{{ $labelClass }}">{{ $isEdit && !empty($category->image) ? __('Replace Image') : __('Upload Image') }}</label>
                <input id="image" type="file" name="image" accept="image/*"
                       class="w-full rounded-xl border {{ $errors->has('image') ? $inputErr : $inputOk }} bg-slate-50 px-3 py-2.5 text-sm text-slate-900 file:mr-3 file:rounded-md file:border-0 file:bg-slate-200 file:px-3 file:py-1 file:text-xs file:font-bold dark:bg-slate-800 dark:text-slate-100">
                @error('image')<p class="text-xs font-medium text-rose-600 dark:text-rose-400 mt-1.5">{{ $message }}</p>@enderror
            </div>

            @if($isEdit && !empty($category->image))
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                    <div class="h-16 w-16 rounded-lg border border-slate-200 bg-white grid place-items-center overflow-hidden p-1.5 dark:border-slate-700 dark:bg-slate-950">
                        <img src="{{ asset('storage/' . ltrim($category->image, '/')) }}" alt="{{ $category->name_en }}" class="max-h-full max-w-full object-contain">
                    </div>
                    <div>
                        <div class="text-[10.5px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ __('Current Image') }}</div>
                        <label class="mt-1.5 flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-amber-500 focus:ring-amber-400 dark:border-slate-600 dark:bg-slate-800">
                            {{ __('Remove') }}
                        </label>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ═════════════ Actions ═════════════ --}}
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('admin.categories.index') }}"
           class="inline-flex items-center gap-2 h-11 px-4 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700 transition">
            {{ __('Cancel') }}
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 h-11 px-6 rounded-xl text-xs font-bold text-[#04042a] shadow-md shadow-amber-500/30 transition hover:brightness-105"
                style="background: linear-gradient(180deg, #fbbf24, #f59e0b);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ $isEdit ? __('Update Category') : __('Create Category') }}
        </button>
    </div>
</form>
