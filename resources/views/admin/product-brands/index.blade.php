<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">{{ __('Product Brands') }}</h2>
                <p class="text-sm text-slate-500">{{ __('Create brands with logos and assign products to them.') }}</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-accent">
                <i class="fas fa-box"></i> {{ __('View Products') }}
            </a>
        </div>
    </x-slot>

    @php
        $inputClass = 'h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-900 transition focus:border-accent focus:bg-white focus:ring-2 focus:ring-accent/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:bg-slate-900';
        $assignmentUrl = function (string $value) {
            $params = request()->except('page', 'assignment');
            if ($value !== '') {
                $params['assignment'] = $value;
            }

            return route('admin.product-brands.index', $params);
        };
    @endphp

    <div class="min-h-screen bg-slate-100 py-7">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ __('Total Brands') }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalBrands) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ __('Brands With Products') }}</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600">{{ number_format($assignedBrands) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ __('Assigned Products') }}</p>
                    <p class="mt-2 text-3xl font-bold text-accent">{{ number_format($totalAssignedProducts) }}</p>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-[340px_minmax(0,1fr)]">
                <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-5">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-navy-deep text-accent"><i class="fas fa-plus"></i></span>
                        <div>
                            <h3 class="font-bold text-slate-900">{{ __('Add Product Brand') }}</h3>
                            <p class="text-xs text-slate-500">{{ __('Name and logo can be changed later.') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.product-brands.store') }}" enctype="multipart/form-data" class="space-y-4" data-loading-form>
                        @csrf
                        <div>
                            <label for="brand-name" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Brand Name') }}</label>
                            <input id="brand-name" name="name" value="{{ old('name') }}" required maxlength="120" class="{{ $inputClass }}" placeholder="{{ __('e.g., Bosch, Denso') }}">
                        </div>
                        <div>
                            <label for="brand-logo" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Brand Logo') }}</label>
                            <input id="brand-logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-xs text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-navy-deep file:px-3 file:py-2 file:font-bold file:text-accent dark:border-slate-700">
                            <p class="mt-1.5 text-xs text-muted">{{ __('PNG, JPG or WebP up to 2MB.') }}</p>
                        </div>
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-navy-deep px-4 text-sm font-bold text-accent transition hover:bg-[#090946]">
                            <i class="fas fa-plus"></i> {{ __('Create Brand') }}
                        </button>
                    </form>
                </aside>

                <main class="space-y-4">
                    <form method="GET" action="{{ route('admin.product-brands.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_180px_auto]">
                            <input name="search" value="{{ $search }}" class="{{ $inputClass }}" placeholder="{{ __('Search brand name...') }}">
                            <select name="assignment" class="{{ $inputClass }}">
                                <option value="">{{ __('All Brands') }}</option>
                                <option value="assigned" @selected($assignment === 'assigned')>{{ __('With Products') }}</option>
                                <option value="empty" @selected($assignment === 'empty')>{{ __('Without Products') }}</option>
                            </select>
                            <button class="h-11 rounded-xl bg-accent px-5 text-sm font-bold text-navy-deep hover:bg-accent">{{ __('Filter') }}</button>
                        </div>
                    </form>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $assignmentUrl('') }}" class="font-display rounded-full border px-3 py-1.5 text-sm font-bold {{ $assignment === '' ? 'border-navy-deep bg-navy-deep text-accent dark:border-accent dark:bg-accent dark:text-navy-deep' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">{{ __('All') }}</a>
                        <a href="{{ $assignmentUrl('assigned') }}" class="font-display rounded-full border px-3 py-1.5 text-sm font-bold {{ $assignment === 'assigned' ? 'border-navy-deep bg-navy-deep text-accent dark:border-accent dark:bg-accent dark:text-navy-deep' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">{{ __('With Products') }}</a>
                        <a href="{{ $assignmentUrl('empty') }}" class="font-display rounded-full border px-3 py-1.5 text-sm font-bold {{ $assignment === 'empty' ? 'border-navy-deep bg-navy-deep text-accent dark:border-accent dark:bg-accent dark:text-navy-deep' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">{{ __('Without Products') }}</a>
                    </div>

                    @if($brands->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700">
                            <i class="fas fa-tags text-4xl text-slate-300"></i>
                            <h3 class="mt-4 font-bold text-slate-900">{{ __('No product brands found.') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('Add the first brand using the form.') }}</p>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($brands as $brand)
                                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-accent hover:shadow-md">
                                    <div class="grid h-32 place-items-center border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white p-5 dark:from-slate-800 dark:to-slate-900">
                                        @if($brand->logo_path)
                                            <img src="{{ asset('storage/' . ltrim((string) $brand->logo_path, '/')) }}" alt="{{ $brand->name }}" class="max-h-20 max-w-[75%] object-contain">
                                        @else
                                            <span class="grid h-16 w-16 place-items-center rounded-2xl bg-navy-deep text-2xl font-bold text-accent">{{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="p-4">
                                        <h3 class="truncate text-base font-bold text-slate-900">{{ $brand->name }}</h3>
                                        <p class="mt-1 truncate font-mono text-[11px] text-muted">{{ $brand->slug }}</p>

                                        <a href="{{ route('admin.products.index', ['product_brand_id' => $brand->id]) }}" class="mt-3 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100">
                                            <span><i class="fas fa-box me-1"></i> {{ __('Products') }}</span>
                                            <span>{{ number_format($brand->products_count) }}</span>
                                        </a>

                                        <details class="mt-3 group">
                                            <summary class="flex h-9 cursor-pointer list-none items-center justify-center gap-2 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800">
                                                <i class="fas fa-pen"></i> {{ __('Edit Brand') }}
                                            </summary>
                                            <form method="POST" action="{{ route('admin.product-brands.update', $brand) }}" enctype="multipart/form-data" class="mt-3 space-y-3 rounded-xl bg-slate-50 p-3" data-loading-form>
                                                @csrf
                                                @method('PUT')
                                                <input name="name" value="{{ $brand->name }}" required maxlength="120" class="{{ $inputClass }}">
                                                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-500 file:me-2 file:rounded-md file:border-0 file:bg-slate-200 file:px-2 file:py-1.5 file:font-bold dark:file:bg-slate-700 dark:file:text-slate-100">
                                                @if($brand->logo_path)
                                                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                                                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-accent"> {{ __('Remove current logo') }}
                                                    </label>
                                                @endif
                                                <button class="h-9 w-full rounded-lg bg-navy-deep text-sm font-bold text-accent dark:bg-accent dark:text-navy-deep">{{ __('Save Changes') }}</button>
                                            </form>
                                        </details>

                                        <form method="POST" action="{{ route('admin.product-brands.destroy', $brand) }}" class="mt-2" data-danger-form data-danger-title="{{ __('Delete Brand') }}" data-danger-description="{{ __('This brand will be removed permanently.') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-9 w-full rounded-lg border border-rose-200 text-sm font-bold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-45 dark:hover:bg-rose-500/10" @disabled($brand->products_count > 0) title="{{ $brand->products_count > 0 ? __('Remove product assignments before deleting this brand.') : __('Delete Brand') }}">
                                                <i class="fas fa-trash me-1"></i> {{ __('Delete Brand') }}
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div>{{ $brands->links() }}</div>
                    @endif
                </main>
            </div>
        </div>
    </div>
</x-app-layout>
