<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800">Edit Category</h2>
            <a href="{{ route('admin.categories.index') }}" class="text-sm text-slate-600 hover:text-slate-800">Back to categories</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                Editing <span class="font-semibold">{{ $category->name_en }}</span> ({{ $category->products()->count() }} products linked).
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
                @include('admin.categories._form', ['category' => $category])
            </div>
        </div>
    </div>
</x-app-layout>
