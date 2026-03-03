@php
    $isEdit = isset($category);
    $formAction = $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store');
@endphp

<form method="POST" action="{{ $formAction }}" class="space-y-6">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name (English)</label>
            <input type="text" name="name_en" value="{{ old('name_en', $category->name_en ?? '') }}" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" required>
            @error('name_en')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name (Arabic)</label>
            <input type="text" name="name_ar" value="{{ old('name_ar', $category->name_ar ?? '') }}" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" required>
            @error('name_ar')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name (Kurdish)</label>
            <input type="text" name="name_ku" value="{{ old('name_ku', $category->name_ku ?? '') }}" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" required>
            @error('name_ku')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Slug (optional)</label>
            <input type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="Auto-generated from English name">
            @error('slug')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
        <textarea name="description" rows="5" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="Optional details...">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-slate-700 text-sm font-semibold transition">Cancel</a>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
            {{ $isEdit ? 'Update Category' : 'Create Category' }}
        </button>
    </div>
</form>
