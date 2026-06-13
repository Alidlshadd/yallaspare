<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-slate-100">{{ __('Categories Management') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            @php
                $importErrors = session('import_errors', []);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Total Categories') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($totalCategories) }}</p>
                </div>
                <div class="bg-white border border-amber-200 rounded-2xl p-5 shadow-sm dark:border-amber-900/50 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300">{{ __('Empty Categories') }}</p>
                    <p class="mt-2 text-3xl font-bold text-amber-800 dark:text-amber-300">{{ number_format($emptyCategories) }}</p>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2 bg-white rounded-xl border border-gray-200 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h4 class="font-semibold text-gray-800 mb-3 dark:text-slate-100">{{ __('Bulk Import') }}</h4>
                    <form method="POST" action="{{ route('admin.categories.import') }}" enctype="multipart/form-data" class="flex flex-col md:flex-row md:items-center gap-3" data-loading-form data-loading-message="Uploading, please wait..." data-loading-button-text="Uploading...">
                        @csrf
                        <input type="file" name="import_file" accept=".csv,.txt,.xls,.xlsx" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" required>
                        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">
                            {{ __('Import File') }}
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2 dark:text-slate-400">
                        {{ __('Supported files: CSV, TXT, XLS, XLSX.') }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1 dark:text-slate-400">
                        {{ __('Required columns:') }} <span class="font-medium">{{ __('name_en, name_ar, name_ku') }}</span>{{ __('. Optional: slug, description.') }}
                    </p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h4 class="font-semibold text-gray-800 mb-3 dark:text-slate-100">{{ __('Export') }}</h4>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('admin.categories.export-excel') }}" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold text-center transition">
                            {{ __('Export Excel (.xlsx)') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto lg:flex-1">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="{{ __('Search by name, slug, or description...') }}"
                            class="w-full rounded-lg border-gray-300 bg-white text-slate-900 focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        >
                        <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition">
                            {{ __('Search') }}
                        </button>
                        @if($search !== '')
                            <a href="{{ route('admin.categories.index') }}" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition text-center dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                    <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition whitespace-nowrap">
                        {{ __('Add Category') }}
                    </a>
                </div>
            </div>

            @if(count($importErrors) > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-amber-200 overflow-hidden dark:border-amber-900/50 dark:bg-slate-900">
                    <div class="px-4 py-3 border-b border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/20">
                        <h4 class="font-semibold text-amber-900 dark:text-amber-200">
                            {{ __('Import Error Report') }} ({{ count($importErrors) }} {{ __('rows') }})
                        </h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-amber-50 text-amber-900 dark:bg-amber-950/20 dark:text-amber-200">
                                <tr>
                                    <th class="p-3 font-semibold">{{ __('Row') }}</th>
                                    <th class="p-3 font-semibold">{{ __('Name (EN)') }}</th>
                                    <th class="p-3 font-semibold">{{ __('Error') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-100 dark:divide-amber-900/40">
                                @foreach($importErrors as $errorRow)
                                    <tr>
                                        <td class="p-3 align-top">{{ $errorRow['row'] ?? '-' }}</td>
                                        <td class="p-3 align-top font-mono text-xs text-slate-700 dark:text-slate-300">{{ $errorRow['name_en'] ?? '-' }}</td>
                                        <td class="p-3 align-top text-red-700 dark:text-red-300">{{ $errorRow['message'] ?? __('Unknown error') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
                            <tr>
                                <th class="p-4 font-semibold">{{ __('ID') }}</th>
                                <th class="p-4 font-semibold">{{ __('Image') }}</th>
                                <th class="p-4 font-semibold">{{ __('Category') }}</th>
                                <th class="p-4 font-semibold">{{ __('Slug') }}</th>
                                <th class="p-4 font-semibold">{{ __('Products') }}</th>
                                <th class="p-4 font-semibold">{{ __('Created') }}</th>
                                <th class="p-4 font-semibold text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                            @forelse($categories as $category)
                                <tr class="hover:bg-slate-50 transition dark:hover:bg-slate-800/60">
                                    <td class="p-4 font-medium text-slate-700 dark:text-slate-200">#{{ $category->id }}</td>
                                    <td class="p-4">
                                        @if($category->image)
                                            <img
                                                src="{{ asset('storage/' . ltrim((string) $category->image, '/')) }}"
                                                alt="{{ $category->name }}"
                                                class="h-12 w-12 rounded-lg border border-slate-200 bg-white object-cover dark:border-slate-700 dark:bg-slate-950"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-600">
                                                <i class="fas fa-image text-sm"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $category->name }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5 dark:text-slate-400">
                                                <span dir="rtl" class="inline-block">{{ $category->name_ar }}</span>
                                                <span class="mx-1">/</span>
                                                <span dir="rtl" class="inline-block">{{ $category->name_ku }}</span>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="p-4 text-slate-600 font-mono text-xs dark:text-slate-300">{{ $category->slug }}</td>
                                    <td class="p-4">
                                        @if($category->products_count > 0)
                                            <a
                                                href="{{ route('admin.products.index', ['category_id' => $category->id]) }}"
                                                class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-200 hover:text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            >
                                                {{ trans_choice(':count product|:count products', $category->products_count, ['count' => number_format($category->products_count)]) }}
                                            </a>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-100 text-xs font-semibold text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                                {{ __('0 products') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-slate-500 dark:text-slate-400">{{ $category->created_at?->format('d M Y') }}</td>
                                    <td class="p-4">
                                        <div class="flex justify-end items-center gap-3">
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">{{ __('Edit') }}</a>
                                            @if($category->products_count > 0)
                                                <span class="text-xs text-slate-400 cursor-not-allowed dark:text-slate-500" title="{{ __('Cannot delete category with assigned products') }}">
                                                    {{ __('Delete') }}
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-danger-confirm data-danger-title="{{ __('Delete Category') }}" data-danger-description="{{ __('This action is permanent. The selected category will be deleted and cannot be recovered.') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">{{ __('Delete') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-10 text-center text-slate-500 dark:text-slate-400">
                                        <p class="text-base font-semibold text-slate-700 dark:text-slate-200">{{ __('No categories found') }}</p>
                                        @if($search !== '')
                                            <p class="mt-1">{{ __('No results for ":search".', ['search' => $search]) }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-slate-800">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
