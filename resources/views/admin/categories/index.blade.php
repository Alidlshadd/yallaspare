<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800">Categories Management</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Categories</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalCategories) }}</p>
                </div>
                <div class="bg-white border border-amber-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-wide text-amber-700">Empty Categories</p>
                    <p class="mt-2 text-3xl font-bold text-amber-800">{{ number_format($emptyCategories) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto lg:flex-1">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search by name, slug, or description..."
                            class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
                        >
                        <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">
                            Search
                        </button>
                        @if($search !== '')
                            <a href="{{ route('admin.categories.index') }}" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition text-center">
                                Clear
                            </a>
                        @endif
                    </form>
                    <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition whitespace-nowrap">
                        Add Category
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="p-4 font-semibold">ID</th>
                                <th class="p-4 font-semibold">Category</th>
                                <th class="p-4 font-semibold">Slug</th>
                                <th class="p-4 font-semibold">Products</th>
                                <th class="p-4 font-semibold">Created</th>
                                <th class="p-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($categories as $category)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-4 font-medium text-slate-700">#{{ $category->id }}</td>
                                    <td class="p-4">
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ $category->name_en }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                <span dir="rtl" class="inline-block">{{ $category->name_ar }}</span>
                                                <span class="mx-1">/</span>
                                                <span dir="rtl" class="inline-block">{{ $category->name_ku }}</span>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="p-4 text-slate-600 font-mono text-xs">{{ $category->slug }}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $category->products_count > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ number_format($category->products_count) }} product{{ $category->products_count === 1 ? '' : 's' }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500">{{ $category->created_at?->format('d M Y') }}</td>
                                    <td class="p-4">
                                        <div class="flex justify-end items-center gap-3">
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Edit</a>
                                            @if($category->products_count > 0)
                                                <span class="text-xs text-slate-400 cursor-not-allowed" title="Cannot delete category with assigned products">
                                                    Delete
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-slate-500">
                                        <p class="text-base font-semibold text-slate-700">No categories found</p>
                                        @if($search !== '')
                                            <p class="mt-1">No results for "{{ $search }}".</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-200">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
