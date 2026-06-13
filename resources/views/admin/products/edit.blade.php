<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Edit Product') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Update product information and inventory status.') }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                <i class="fas fa-clock text-indigo-500"></i>
                Last updated {{ optional($product->updated_at)->format('M d, Y - h:i A') }}
            </span>
        </div>
    </x-slot>

    @php
        $inputBase = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 invalid:border-rose-500 invalid:ring-rose-500/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500';
        $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/30';
        $compatibleModelsValue = is_array($product->compatible_models)
            ? implode("\n", $product->compatible_models)
            : $product->compatible_models;
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            <form id="productForm" method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6" data-loading-form data-loading-button-text="Saving...">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_to" value="{{ old('return_to', $returnTo) }}">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Basic Information') }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Product naming, categorization, and descriptions.') }}</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Product Name (EN)') }} <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name_en" value="{{ old('name_en', $product->name_en) }}" class="{{ $inputBase }} @error('name_en') {{ $inputError }} @enderror" required @error('name_en') aria-invalid="true" @enderror>
                                    @error('name_en')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Product Name (AR)') }} <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name_ar" value="{{ old('name_ar', $product->name_ar) }}" class="{{ $inputBase }} @error('name_ar') {{ $inputError }} @enderror" required @error('name_ar') aria-invalid="true" @enderror>
                                    @error('name_ar')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Product Name (KU)') }} <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name_ku" value="{{ old('name_ku', $product->name_ku) }}" class="{{ $inputBase }} @error('name_ku') {{ $inputError }} @enderror" required @error('name_ku') aria-invalid="true" @enderror>
                                    @error('name_ku')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('SKU') }}</label>
                                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="{{ $inputBase }} @error('sku') {{ $inputError }} @enderror" placeholder="{{ __('Auto-generate if empty') }}" @error('sku') aria-invalid="true" @enderror>
                                    @error('sku')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('OEM Number') }}</label>
                                    <input type="text" name="oem_number" value="{{ old('oem_number', $product->oem_number) }}" class="{{ $inputBase }} @error('oem_number') {{ $inputError }} @enderror" placeholder="{{ __('e.g., 17801-0M040') }}" @error('oem_number') aria-invalid="true" @enderror>
                                    @error('oem_number')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Part Number') }}</label>
                                    <input type="text" name="part_number" value="{{ old('part_number', $product->part_number) }}" class="{{ $inputBase }} @error('part_number') {{ $inputError }} @enderror" placeholder="{{ __('Manufacturer part number') }}" @error('part_number') aria-invalid="true" @enderror>
                                    @error('part_number')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Warranty') }}</label>
                                    <input type="text" name="warranty" value="{{ old('warranty', $product->warranty) }}" class="{{ $inputBase }} @error('warranty') {{ $inputError }} @enderror" placeholder="{{ __('e.g., 6 months') }}" @error('warranty') aria-invalid="true" @enderror>
                                    @error('warranty')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Brand') }}</label>
                                    <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" class="{{ $inputBase }} @error('brand') {{ $inputError }} @enderror" placeholder="{{ __('e.g., Bosch, Denso') }}" @error('brand') aria-invalid="true" @enderror>
                                    @error('brand')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Category') }} <span class="text-rose-500">*</span></label>
                                    <select name="category_id" class="{{ $inputBase }} @error('category_id') {{ $inputError }} @enderror" required @error('category_id') aria-invalid="true" @enderror>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Description (EN)') }}</label>
                                    <textarea name="description_en" rows="3" class="{{ $inputBase }} @error('description_en') {{ $inputError }} @enderror" @error('description_en') aria-invalid="true" @enderror>{{ old('description_en', $product->description_en) }}</textarea>
                                    @error('description_en')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Description (AR)') }}</label>
                                    <textarea name="description_ar" rows="3" class="{{ $inputBase }} @error('description_ar') {{ $inputError }} @enderror" @error('description_ar') aria-invalid="true" @enderror>{{ old('description_ar', $product->description_ar) }}</textarea>
                                    @error('description_ar')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Description (KU)') }}</label>
                                    <textarea name="description_ku" rows="3" class="{{ $inputBase }} @error('description_ku') {{ $inputError }} @enderror" @error('description_ku') aria-invalid="true" @enderror>{{ old('description_ku', $product->description_ku) }}</textarea>
                                    @error('description_ku')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Compatible Models') }}</label>
                                    <textarea name="compatible_models" rows="2" class="{{ $inputBase }} @error('compatible_models') {{ $inputError }} @enderror" placeholder="{{ __('Comma or new line separated') }}" @error('compatible_models') aria-invalid="true" @enderror>{{ old('compatible_models', $compatibleModelsValue) }}</textarea>
                                    @error('compatible_models')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Pricing') }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Set pricing and dealer visibility.') }}</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Price') }} <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" class="{{ $inputBase }} pr-16 @error('price') {{ $inputError }} @enderror" required @error('price') aria-invalid="true" @enderror>
                                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-500 dark:text-slate-400">{{ $currencyLabel }}</span>
                                    </div>
                                    @error('price')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Dealer Price') }}</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" name="dealer_price" value="{{ old('dealer_price', $product->dealer_price) }}" class="{{ $inputBase }} pr-16 @error('dealer_price') {{ $inputError }} @enderror" placeholder="{{ __('Optional') }}" @error('dealer_price') aria-invalid="true" @enderror>
                                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-500 dark:text-slate-400">{{ $currencyLabel }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Leave empty to use dealer discount rules.') }}</p>
                                    @error('dealer_price')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Inventory') }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Track stock levels and alert thresholds.') }}</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Stock Quantity') }} <span class="text-rose-500">*</span></label>
                                    <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" class="{{ $inputBase }} @error('stock_quantity') {{ $inputError }} @enderror" required @error('stock_quantity') aria-invalid="true" @enderror>
                                    @error('stock_quantity')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex items-center gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
                                    <i class="fas fa-triangle-exclamation text-amber-500"></i>
                                    {{ __('Low stock alerts trigger at :count units.', ['count' => $lowStockThreshold]) }}
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Media') }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Replace the existing product image if needed.') }}</p>
                            </div>
                            <div class="p-6 space-y-4">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Product Image') }}</label>
                                <label class="group flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-6 text-center text-sm text-slate-500 transition hover:border-indigo-400 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-indigo-400">
                                    <input id="productImage" type="file" name="image" accept="image/*" class="hidden">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm dark:bg-slate-900 dark:text-slate-300">
                                            <i class="fas fa-cloud-upload-alt text-lg"></i>
                                        </span>
                                        <span class="font-medium">{{ __('Drag & drop or click to upload') }}</span>
                                        <span class="text-xs text-slate-400">{{ __('PNG, JPG up to 2MB') }}</span>
                                    </div>
                                </label>

                                <div id="productImagePreview" class="{{ $product->image ? 'flex' : 'hidden' }} items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                                    <img id="productImagePreviewImg" src="{{ $product->image ? asset('storage/' . $product->image) : '' }}" alt="{{ __('Preview') }}" class="h-20 w-20 rounded-lg object-cover">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Current Image') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Upload a new image to replace it.') }}</p>
                                    </div>
                                </div>

                                @if($product->image)
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                        <input id="removeProductImage" type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 dark:border-slate-700">
                                        {{ __('Remove current image') }}
                                    </label>
                                @endif
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Add Gallery Images') }}</label>
                                    <input type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                </div>

                                @if($product->images->isNotEmpty())
                                    <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Gallery') }}</p>
                                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                                            @foreach($product->images as $image)
                                                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                                    <div class="flex gap-3">
                                                        <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $image->alt_text ?: $product->name }}" class="h-16 w-16 rounded-lg object-cover">
                                                        <div class="min-w-0 flex-1 space-y-2">
                                                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                                                <input type="radio" name="primary_image_id" value="{{ $image->id }}" @checked($image->is_primary) class="border-slate-300 text-indigo-600">
                                                                {{ __('Primary') }}
                                                            </label>
                                                            <input type="number" name="gallery_sort_order[{{ $image->id }}]" value="{{ $image->sort_order }}" min="0" max="10000" class="w-full rounded-lg border-slate-300 px-2 py-1 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ __('Sort') }}">
                                                            <input type="text" name="gallery_alt_text[{{ $image->id }}]" value="{{ $image->alt_text }}" maxlength="255" class="w-full rounded-lg border-slate-300 px-2 py-1 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ __('Alt text') }}">
                                                        </div>
                                                    </div>
                                                    <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-rose-700">
                                                        <input type="checkbox" name="remove_gallery_image_ids[]" value="{{ $image->id }}" class="rounded border-slate-300">
                                                        {{ __('Remove') }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Status') }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Control product visibility.') }}</p>
                            </div>
                            <div class="p-6">
                                <label class="inline-flex items-center gap-4">
                                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                    <span class="relative h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-indigo-600 peer-focus:ring-2 peer-focus:ring-indigo-500/40 dark:bg-slate-800">
                                        <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                    </span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Active') }}</span>
                                </label>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide">{{ __('Quick Tips') }}</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-500 dark:text-slate-400">
                                <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-0.5"></i>{{ __('Keep SKUs consistent for faster imports.') }}</li>
                                <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-0.5"></i>{{ __('Update stock after inventory audits.') }}</li>
                                <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-0.5"></i>{{ __('Replace images with high-resolution shots.') }}</li>
                            </ul>
                        </div>
                    </aside>
                </div>

                <div class="sticky bottom-0 z-10 -mx-4 sm:-mx-6 lg:-mx-8 border-t border-slate-200 bg-white/90 px-4 py-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="max-w-6xl mx-auto flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Changes are saved immediately after update.') }}</p>
                        <div class="flex items-center gap-3">
                            <a href="{{ $returnTo }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Cancel') }}</a>
                            <button id="productSubmit" type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                                <span class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white/60 border-t-white dark:border-slate-900/60 dark:border-t-slate-900" data-spinner></span>
                                <span data-label>{{ __('Update Product') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
    <script>
        (function () {
            const form = document.getElementById('productForm');
            const submitButton = document.getElementById('productSubmit');
            const submitLabel = submitButton?.querySelector('[data-label]');
            const spinner = submitButton?.querySelector('[data-spinner]');
            const imageInput = document.getElementById('productImage');
            const preview = document.getElementById('productImagePreview');
            const previewImg = document.getElementById('productImagePreviewImg');
            const removeCheckbox = document.getElementById('removeProductImage');

            if (form && submitButton) {
                form.addEventListener('submit', (event) => {
                    if (submitButton.dataset.loading === '1') {
                        event.preventDefault();
                        return;
                    }
                    submitButton.dataset.loading = '1';
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-80', 'cursor-not-allowed');
                    if (submitLabel) submitLabel.textContent = @json(__('Saving...'));
                    if (spinner) spinner.classList.remove('hidden');
                });
            }

            if (imageInput && preview && previewImg) {
                imageInput.addEventListener('change', (event) => {
                    const file = event.target.files?.[0];
                    if (removeCheckbox) {
                        removeCheckbox.checked = false;
                    }
                    if (!file) {
                        if (!previewImg.src) {
                            preview.classList.add('hidden');
                        }
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImg.src = e.target?.result || '';
                        preview.classList.remove('hidden');
                        preview.classList.add('flex');
                    };
                    reader.readAsDataURL(file);
                });
            }

            if (removeCheckbox && preview && previewImg) {
                removeCheckbox.addEventListener('change', () => {
                    if (removeCheckbox.checked) {
                        preview.classList.add('hidden');
                        previewImg.src = '';
                    }
                });
            }
        })();
    </script>
</x-app-layout>
