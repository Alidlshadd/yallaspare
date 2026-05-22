@php
    $isEdit = isset($category);
    $formAction = $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store');
@endphp

<form method="POST" action="{{ $formAction }}" class="space-y-6" enctype="multipart/form-data">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Name (English)') }}</label>
            <input type="text" name="name_en" value="{{ old('name_en', $category->name_en ?? '') }}" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
            @error('name_en')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Name (Arabic)') }}</label>
            <input type="text" name="name_ar" value="{{ old('name_ar', $category->name_ar ?? '') }}" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
            @error('name_ar')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Name (Kurdish)') }}</label>
            <input type="text" name="name_ku" value="{{ old('name_ku', $category->name_ku ?? '') }}" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
            @error('name_ku')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Slug (optional)') }}</label>
            <input type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="{{ __('Auto-generated from English name') }}">
            @error('slug')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Description') }}</label>
        <textarea name="description" rows="5" class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="{{ __('Optional details...') }}">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">{{ __('Category Image') }}</label>
            <input type="file" name="image" accept="image/*" class="w-full rounded-lg border border-gray-300 bg-white text-sm text-slate-900 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:file:bg-slate-800 dark:file:text-slate-200">
            @error('image')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        @if($isEdit && !empty($category->image))
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-2 dark:border-slate-700 dark:bg-slate-900">
                <img src="{{ asset('storage/' . ltrim($category->image, '/')) }}" alt="{{ $category->name_en }}" class="h-12 w-12 rounded-md object-cover">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    {{ __('Remove') }}
                </label>
            </div>
        @endif
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-slate-700 text-sm font-semibold transition dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancel') }}</a>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
            {{ $isEdit ? 'Update Category' : 'Create Category' }}
        </button>
    </div>
</form>
