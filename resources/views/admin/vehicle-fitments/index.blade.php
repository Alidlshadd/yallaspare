<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ __('Vehicle Finder') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Manage brand, model, year, engine compatibility for products.') }}</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                <i class="fas fa-car-side text-primary dark:text-white"></i>
                {{ __('Catalog compatibility') }}
            </span>
        </div>
    </x-slot>

    @php
        $statsCards = [
            ['label' => __('Vehicle Brands'), 'value' => $stats['brands'] ?? 0, 'icon' => 'fa-car-side', 'tone' => 'text-primary bg-primary/10 dark:bg-primary/25 dark:text-white'],
            ['label' => __('Vehicle Models'), 'value' => $stats['models'] ?? 0, 'icon' => 'fa-layer-group', 'tone' => 'text-cyan-600 bg-cyan-50 dark:bg-cyan-950/40 dark:text-cyan-300'],
            ['label' => __('Fitment Rules'), 'value' => $stats['fitments'] ?? 0, 'icon' => 'fa-link', 'tone' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300'],
            ['label' => __('Covered Products'), 'value' => $stats['covered_products'] ?? 0, 'icon' => 'fa-box-open', 'tone' => 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300'],
        ];

        $brandModelMap = $brands
            ->mapWithKeys(fn ($brand) => [
                (string) $brand->id => $brand->models
                    ->map(fn ($model) => ['id' => (int) $model->id, 'name' => (string) $model->name])
                    ->values()
                    ->all(),
            ])
            ->all();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($statsCards as $card)
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format((int) $card['value']) }}</p>
                                @if((int) $card['value'] === 0)
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ __('Add your first one below') }} ↓</p>
                                @endif
                            </div>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg {{ $card['tone'] }}">
                                <i class="fas {{ $card['icon'] }} text-sm"></i>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="flex items-center text-sm font-semibold text-slate-900 dark:text-slate-100">
                                <span class="me-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary dark:bg-white/10 dark:text-white">1</span>
                                {{ __('Add Vehicle Brand') }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Create the manufacturer list used in fitment rules.') }}</p>
                        </div>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <i class="fas fa-plus text-xs"></i>
                        </span>
                    </div>
                    <form method="POST" action="{{ route('admin.vehicle-fitments.brands.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Brand Name') }}</label>
                        <input name="name" required maxlength="120" placeholder="{{ __('Toyota') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <button class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-hover dark:bg-white dark:text-primary dark:hover:bg-slate-100">{{ __('Create Brand') }}</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="flex items-center text-sm font-semibold text-slate-900 dark:text-slate-100">
                                <span class="me-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary dark:bg-white/10 dark:text-white">2</span>
                                {{ __('Add Vehicle Model') }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Attach models under the correct vehicle brand.') }}</p>
                        </div>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-white/10 dark:text-white">
                            <i class="fas fa-sitemap text-xs"></i>
                        </span>
                    </div>
                    <form method="POST" action="{{ route('admin.vehicle-fitments.models.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Brand') }}</label>
                        <select name="vehicle_brand_id" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            <option value="">{{ __('Select brand') }}</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Model Name') }}</label>
                        <input name="name" required maxlength="120" placeholder="{{ __('Corolla') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <button class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-hover dark:bg-white dark:text-primary dark:hover:bg-slate-100">{{ __('Create Model') }}</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Current Vehicle Data') }}</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Review and remove brands or models.') }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $brands->count() }}</span>
                    </div>
                    <div class="mt-4 max-h-72 space-y-3 overflow-y-auto pe-1">
                        @forelse($brands as $brand)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $brand->name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ trans_choice(':count model|:count models', $brand->models->count(), ['count' => $brand->models->count()]) }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('admin.vehicle-fitments.brands.destroy', $brand) }}" data-danger-confirm data-danger-title="{{ __('Delete Vehicle Brand') }}" data-danger-description="{{ __('This brand and all models under it will be removed from Vehicle Finder.') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-300">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                                @if($brand->models->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($brand->models as $model)
                                            <form method="POST" action="{{ route('admin.vehicle-fitments.models.destroy', $model) }}" class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300" data-danger-confirm data-danger-title="{{ __('Delete Vehicle Model') }}" data-danger-description="{{ __('This model will be removed from Vehicle Finder.') }}">
                                                @csrf
                                                @method('DELETE')
                                                <span>{{ $model->name }}</span>
                                                <button type="submit" class="font-bold text-rose-600 hover:text-rose-700" aria-label="{{ __('Delete :name', ['name' => $model->name]) }}">x</button>
                                            </form>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('No models yet') }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('No vehicle brands yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="flex items-center text-sm font-semibold text-slate-900 dark:text-slate-100">
                                <span class="me-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary dark:bg-white/10 dark:text-white">3</span>
                                {{ __('Add Product Fitment') }}
                            </h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Connect one product to a vehicle brand, model, year range, and engine.') }}</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary dark:bg-white/10 dark:text-white">
                            {{ __('Compatibility rule') }}
                        </span>
                    </div>
                </div>
                <form
                    method="POST"
                    action="{{ route('admin.vehicle-fitments.store') }}"
                    class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.65fr)]"
                    data-admin-vehicle-fitment
                    data-model-map='@json($brandModelMap)'
                    data-any-model-label="{{ __('Any model') }}"
                    data-no-model-label="{{ __('No models for this brand yet') }}"
                    data-any-engine-label="{{ __('Any engine') }}"
                    data-any-year-label="{{ __('Any year') }}"
                    data-product-search-url="{{ route('admin.vehicle-fitments.products.search') }}"
                >
                    @csrf
                    <div class="space-y-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Product') }}</label>
                                <div class="grid gap-2 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                                    <input
                                        type="search"
                                        placeholder="{{ __('Filter by product name, SKU, or brand') }}"
                                        class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                        data-admin-product-filter
                                    >
                                    <select name="product_id" required class="rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" data-admin-product-select>
                                        <option value="">{{ __('Select product') }}</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-search="{{ Str::lower(trim($product->name . ' ' . $product->sku . ' ' . $product->brand)) }}">
                                                {{ $product->name }} @if($product->sku) ({{ $product->sku }}) @endif @if($product->brand) - {{ $product->brand }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Vehicle Brand') }}</label>
                                <select name="vehicle_brand_id" required data-admin-vehicle-brand class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="">{{ __('Select brand') }}</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Vehicle Model') }}</label>
                                <select name="vehicle_model_id" data-admin-vehicle-model class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                    <option value="">{{ __('Any model') }}</option>
                                    @foreach($brands as $brand)
                                        @foreach($brand->models as $model)
                                            <option value="{{ $model->id }}">{{ $brand->name }} / {{ $model->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Year From') }}</label>
                                <input name="year_from" type="number" min="1900" max="2100" placeholder="{{ __('Any') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" data-admin-year-from>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Year To') }}</label>
                                <input name="year_to" type="number" min="1900" max="2100" placeholder="{{ __('Any') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" data-admin-year-to>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Engine') }}</label>
                                <input name="engine" maxlength="120" placeholder="{{ __('e.g. 1.8L, Hybrid, Diesel') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" data-admin-engine>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Notes') }}</label>
                                <input name="notes" maxlength="255" placeholder="{{ __('Optional fitment notes') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            </div>
                        </div>

                        <button class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-primary dark:hover:bg-slate-100 sm:w-auto">
                            <i class="fas fa-link me-2 text-xs"></i>
                            {{ __('Save Fitment') }}
                        </button>
                    </div>

                    <aside class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950 lg:sticky lg:top-24 lg:self-start">
                        <p class="flex items-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            {{ __('Preview') }}
                            <span class="ms-2 inline-flex h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500" aria-hidden="true"></span>
                        </p>
                        <div class="mt-3 space-y-3 text-sm">
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><i class="fas fa-box me-1 text-[10px] text-slate-400"></i>{{ __('Product') }}</p>
                                <p class="font-semibold text-slate-900 dark:text-slate-100" data-admin-preview-product>{{ __('Select product') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><i class="fas fa-car-side me-1 text-[10px] text-slate-400"></i>{{ __('Vehicle') }}</p>
                                <p class="font-semibold text-slate-900 dark:text-slate-100" data-admin-preview-vehicle>{{ __('Select brand') }} / {{ __('Any model') }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><i class="fas fa-calendar-days me-1 text-[10px] text-slate-400"></i>{{ __('Years') }}</p>
                                    <p class="font-semibold text-slate-900 dark:text-slate-100" data-admin-preview-years>{{ __('Any year') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><i class="fas fa-cog me-1 text-[10px] text-slate-400"></i>{{ __('Engine') }}</p>
                                    <p class="font-semibold text-slate-900 dark:text-slate-100" data-admin-preview-engine>{{ __('Any engine') }}</p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Product Fitments') }}</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Search and manage product-to-vehicle compatibility rows.') }}</p>
                        </div>
                        <form method="GET" action="{{ route('admin.vehicle-fitments.index') }}" class="flex w-full gap-2 lg:max-w-xl">
                            <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search product, SKU, brand, model, engine...') }}" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                            @if(request('search'))
                                <a href="{{ route('admin.vehicle-fitments.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Clear') }}</a>
                            @endif
                            <button class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                <i class="fas fa-search me-2 text-xs"></i>
                                {{ __('Search') }}
                            </button>
                        </form>
                    </div>
                    @if(request('search'))
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('Showing results for ":term"', ['term' => request('search')]) }}</p>
                    @endif
                    @if($products->isEmpty())
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                            {{ __('No active products are available for new fitments.') }}
                        </div>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Product') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Vehicle') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Coverage') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Notes') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse($fitments as $fitment)
                                @php
                                    $fitmentProductImage = $fitment->product?->image ? asset('storage/' . ltrim((string) $fitment->product->image, '/')) : null;
                                @endphp
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                    <td class="px-4 py-4">
                                        <div class="flex min-w-72 items-center gap-3">
                                            @if($fitmentProductImage)
                                                <img
                                                    src="{{ $fitmentProductImage }}"
                                                    alt="{{ $fitment->product?->name ?? __('Product') }}"
                                                    class="h-12 w-12 rounded-lg border border-slate-200 object-cover dark:border-slate-700"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-950">
                                                    <i class="fas fa-image text-sm"></i>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $fitment->product?->name ?? '-' }}</p>
                                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                                    <span>{{ $fitment->product?->sku ?: __('No SKU') }}</span>
                                                    @if($fitment->product?->brand)
                                                        <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                        <span>{{ $fitment->product->brand }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex min-w-48 flex-wrap gap-2">
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $fitment->brand?->name ?? __('Any brand') }}</span>
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $fitment->model?->name ?? __('Any model') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300">
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $fitment->year_from ?: '*' }} - {{ $fitment->year_to ?: '*' }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $fitment->engine ?: __('Any engine') }}</p>
                                    </td>
                                    <td class="max-w-xs px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $fitment->notes ? Str::limit($fitment->notes, 90) : '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-end">
                                        <form method="POST" action="{{ route('admin.vehicle-fitments.destroy', $fitment) }}" data-danger-confirm data-danger-title="{{ __('Delete Fitment') }}" data-danger-description="{{ __('This product compatibility row will be removed.') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="inline-flex items-center justify-center rounded-md bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-300">
                                                <i class="fas fa-trash-alt me-2 text-[10px]"></i>
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="mx-auto max-w-sm">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                                <i class="fas fa-link"></i>
                                            </div>
                                            <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('No fitments found.') }}</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Create a product fitment above or adjust your search.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($fitments->hasPages())
                    <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800">{{ $fitments->links() }}</div>
                @endif
            </section>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-admin-vehicle-fitment]').forEach((form) => {
            const productFilter = form.querySelector('[data-admin-product-filter]');
            const productSelect = form.querySelector('[data-admin-product-select]');
            const brandSelect = form.querySelector('[data-admin-vehicle-brand]');
            const modelSelect = form.querySelector('[data-admin-vehicle-model]');
            const yearFrom = form.querySelector('[data-admin-year-from]');
            const yearTo = form.querySelector('[data-admin-year-to]');
            const engineInput = form.querySelector('[data-admin-engine]');
            const previewProduct = form.querySelector('[data-admin-preview-product]');
            const previewVehicle = form.querySelector('[data-admin-preview-vehicle]');
            const previewYears = form.querySelector('[data-admin-preview-years]');
            const previewEngine = form.querySelector('[data-admin-preview-engine]');

            if (!brandSelect || !modelSelect) {
                return;
            }

            const modelMap = JSON.parse(form.dataset.modelMap || '{}');
            const anyModelLabel = form.dataset.anyModelLabel || 'Any model';
            const noModelLabel = form.dataset.noModelLabel || 'No models for this brand yet';
            const anyEngineLabel = form.dataset.anyEngineLabel || 'Any engine';
            const anyYearLabel = form.dataset.anyYearLabel || 'Any year';

            const selectedOptionLabel = (select, fallback) => {
                const option = select?.selectedOptions?.[0];
                if (!option || option.value === '') {
                    return fallback;
                }

                return option.textContent.trim();
            };

            const updatePreview = () => {
                const productLabel = selectedOptionLabel(productSelect, productSelect?.querySelector('option[value=""]')?.textContent.trim() || 'Select product');
                const brandLabel = selectedOptionLabel(brandSelect, brandSelect?.querySelector('option[value=""]')?.textContent.trim() || 'Select brand');
                const modelLabel = selectedOptionLabel(modelSelect, anyModelLabel);
                const from = yearFrom?.value?.trim() || '';
                const to = yearTo?.value?.trim() || '';
                const engine = engineInput?.value?.trim() || '';

                if (previewProduct) {
                    previewProduct.textContent = productLabel;
                }

                if (previewVehicle) {
                    previewVehicle.textContent = `${brandLabel} / ${modelLabel}`;
                }

                if (previewYears) {
                    previewYears.textContent = from || to ? `${from || '*'} - ${to || '*'}` : anyYearLabel;
                }

                if (previewEngine) {
                    previewEngine.textContent = engine || anyEngineLabel;
                }
            };

            // Hybrid product filter: client-side hide for the initial 100
            // rendered options (instant feedback) + debounced AJAX fetch
            // against the search endpoint so operators can find any product
            // in the catalog without the legacy limit(500) cap.
            const productSearchUrl = form.dataset.productSearchUrl || '';
            let productSearchTimer = null;
            let productSearchAbort = null;

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            };

            const filterRenderedOptions = (needle) => {
                Array.from(productSelect.options).forEach((option) => {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }
                    option.hidden = needle !== ''
                        && !(option.dataset.search || option.textContent).toLowerCase().includes(needle);
                });
            };

            const mergeAjaxResults = (results) => {
                if (!Array.isArray(results)) return;
                const previousValue = productSelect.value;
                const existingIds = new Set(
                    Array.from(productSelect.options).map((o) => o.value)
                );
                results.forEach((row) => {
                    if (existingIds.has(String(row.id))) return;
                    const labelParts = [row.name];
                    if (row.sku) labelParts.push(`(${row.sku})`);
                    if (row.brand) labelParts.push(`- ${row.brand}`);
                    const label = labelParts.join(' ');
                    const searchAttr = (row.name + ' ' + row.sku + ' ' + row.brand).toLowerCase();
                    const opt = document.createElement('option');
                    opt.value = String(row.id);
                    opt.dataset.search = searchAttr;
                    opt.textContent = label;
                    productSelect.appendChild(opt);
                });
                if (previousValue) productSelect.value = previousValue;
            };

            const filterProducts = () => {
                if (!productFilter || !productSelect) return;
                const needle = productFilter.value.trim().toLowerCase();

                // Always run the local filter first so the user sees instant feedback.
                filterRenderedOptions(needle);

                if (!productSearchUrl) return;

                if (productSearchTimer) clearTimeout(productSearchTimer);
                if (productSearchAbort) productSearchAbort.abort();

                productSearchTimer = setTimeout(() => {
                    const trimmed = productFilter.value.trim();
                    if (trimmed === '') return;

                    productSearchAbort = new AbortController();
                    fetch(`${productSearchUrl}?q=${encodeURIComponent(trimmed)}&per_page=30`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        signal: productSearchAbort.signal,
                    })
                        .then((res) => res.ok ? res.json() : null)
                        .then((data) => {
                            if (!data) return;
                            mergeAjaxResults(data.results || []);
                            filterRenderedOptions(needle);
                        })
                        .catch(() => { /* aborted or network — ignore */ });
                }, 250);
            };

            const setModelOptions = () => {
                const brandId = brandSelect.value;
                const models = brandId ? (modelMap[brandId] || []) : [];
                modelSelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = models.length > 0 || brandId === '' ? anyModelLabel : noModelLabel;
                modelSelect.appendChild(placeholder);

                models.forEach((model) => {
                    const option = document.createElement('option');
                    option.value = model.id;
                    option.textContent = model.name;
                    modelSelect.appendChild(option);
                });

                modelSelect.disabled = brandId !== '' && models.length === 0;
                updatePreview();
            };

            productFilter?.addEventListener('input', filterProducts);
            productSelect?.addEventListener('change', updatePreview);
            brandSelect.addEventListener('change', setModelOptions);
            modelSelect.addEventListener('change', updatePreview);
            yearFrom?.addEventListener('input', updatePreview);
            yearTo?.addEventListener('input', updatePreview);
            engineInput?.addEventListener('input', updatePreview);
            filterProducts();
            setModelOptions();
            updatePreview();
        });
    </script>
</x-app-layout>
