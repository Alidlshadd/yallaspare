<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">{{ __('Add Product') }}</h2>
                <p class="text-sm text-slate-500">{{ __('Create a new product listing with pricing and inventory details.') }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm">
                <i class="fas fa-circle-info text-info" aria-hidden="true"></i>
                {{ __('Required fields are marked') }}
            </span>
        </div>
    </x-slot>

    @php
        $inputBase = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-info focus:ring-2 focus:ring-accent/30 invalid:border-rose-500 invalid:ring-rose-500/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500';
        $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-accent/30';
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="productForm" method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-6" data-loading-form data-loading-button-text="Saving...">
                @csrf
                <input type="hidden" name="return_to" value="{{ old('return_to', $returnTo) }}">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Basic Information') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('Product naming, categorization, and descriptions.') }}</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name_en" class="block text-sm font-medium text-slate-700">{{ __('Product Name (EN)') }} <span class="text-rose-500">*</span></label>
                                    <input id="name_en" type="text" name="name_en" value="{{ old('name_en', (string) request('name', '')) }}" class="{{ $inputBase }} @error('name_en') {{ $inputError }} @enderror" required @error('name_en') aria-invalid="true" @enderror>
                                    @error('name_en')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="name_ar" class="block text-sm font-medium text-slate-700">{{ __('Product Name (AR)') }} <span class="text-rose-500">*</span></label>
                                    <input id="name_ar" type="text" name="name_ar" value="{{ old('name_ar') }}" class="{{ $inputBase }} @error('name_ar') {{ $inputError }} @enderror" required @error('name_ar') aria-invalid="true" @enderror>
                                    @error('name_ar')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="name_ku" class="block text-sm font-medium text-slate-700">{{ __('Product Name (KU)') }} <span class="text-rose-500">*</span></label>
                                    <input id="name_ku" type="text" name="name_ku" value="{{ old('name_ku') }}" class="{{ $inputBase }} @error('name_ku') {{ $inputError }} @enderror" required @error('name_ku') aria-invalid="true" @enderror>
                                    @error('name_ku')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="sku" class="block text-sm font-medium text-slate-700">{{ __('SKU') }}</label>
                                    <input id="sku" type="text" name="sku" value="{{ old('sku') }}" class="{{ $inputBase }} @error('sku') {{ $inputError }} @enderror" placeholder="{{ __('Auto-generate if empty') }}" @error('sku') aria-invalid="true" @enderror>
                                    @error('sku')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="oem_number" class="block text-sm font-medium text-slate-700">{{ __('OEM Number') }}</label>
                                    <input id="oem_number" type="text" name="oem_number" value="{{ old('oem_number') }}" class="{{ $inputBase }} @error('oem_number') {{ $inputError }} @enderror" placeholder="{{ __('e.g., 17801-0M040') }}" @error('oem_number') aria-invalid="true" @enderror>
                                    @error('oem_number')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="part_number" class="block text-sm font-medium text-slate-700">{{ __('Part Number') }}</label>
                                    <input id="part_number" type="text" name="part_number" value="{{ old('part_number') }}" class="{{ $inputBase }} @error('part_number') {{ $inputError }} @enderror" placeholder="{{ __('Manufacturer part number') }}" @error('part_number') aria-invalid="true" @enderror>
                                    @error('part_number')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="warranty" class="block text-sm font-medium text-slate-700">{{ __('Warranty') }}</label>
                                    <input id="warranty" type="text" name="warranty" value="{{ old('warranty') }}" class="{{ $inputBase }} @error('warranty') {{ $inputError }} @enderror" placeholder="{{ __('e.g., 6 months') }}" @error('warranty') aria-invalid="true" @enderror>
                                    @error('warranty')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="product_brand_id" class="block text-sm font-medium text-slate-700">{{ __('Brand') }}</label>
                                    <select id="product_brand_id" name="product_brand_id" class="{{ $inputBase }} @error('product_brand_id') {{ $inputError }} @enderror" @error('product_brand_id') aria-invalid="true" @enderror>
                                        <option value="">{{ __('No brand') }}</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected((string) old('product_brand_id') === (string) $brand->id)>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('admin.product-brands.index') }}" class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-info hover:text-info dark:text-info dark:hover:text-info">
                                        <i class="fas fa-tags" aria-hidden="true"></i> {{ __('Manage product brands') }}
                                    </a>
                                    @error('product_brand_id')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-slate-700">{{ __('Category') }} <span class="text-rose-500">*</span></label>
                                    <select id="category_id" name="category_id" class="{{ $inputBase }} @error('category_id') {{ $inputError }} @enderror" required @error('category_id') aria-invalid="true" @enderror>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label for="description_en" class="block text-sm font-medium text-slate-700">{{ __('Description (EN)') }}</label>
                                    <textarea id="description_en" name="description_en" rows="3" class="{{ $inputBase }} @error('description_en') {{ $inputError }} @enderror" @error('description_en') aria-invalid="true" @enderror>{{ old('description_en') }}</textarea>
                                    @error('description_en')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="description_ar" class="block text-sm font-medium text-slate-700">{{ __('Description (AR)') }}</label>
                                    <textarea id="description_ar" name="description_ar" rows="3" class="{{ $inputBase }} @error('description_ar') {{ $inputError }} @enderror" @error('description_ar') aria-invalid="true" @enderror>{{ old('description_ar') }}</textarea>
                                    @error('description_ar')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="description_ku" class="block text-sm font-medium text-slate-700">{{ __('Description (KU)') }}</label>
                                    <textarea id="description_ku" name="description_ku" rows="3" class="{{ $inputBase }} @error('description_ku') {{ $inputError }} @enderror" @error('description_ku') aria-invalid="true" @enderror>{{ old('description_ku') }}</textarea>
                                    @error('description_ku')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label for="compatible_models" class="block text-sm font-medium text-slate-700">{{ __('Compatible Models') }}</label>
                                    <textarea id="compatible_models" name="compatible_models" rows="2" class="{{ $inputBase }} @error('compatible_models') {{ $inputError }} @enderror" placeholder="{{ __('Comma or new line separated') }}" @error('compatible_models') aria-invalid="true" @enderror>{{ old('compatible_models') }}</textarea>
                                    @error('compatible_models')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Pricing') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('Set pricing and dealer visibility.') }}</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="stock_quantity" class="block text-sm font-medium text-slate-700">{{ __('Price') }} <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input aria-label="{{ __('Price') }}" type="number" step="0.01" name="price" value="{{ old('price') }}" class="{{ $inputBase }} pr-16 @error('price') {{ $inputError }} @enderror" required @error('price') aria-invalid="true" @enderror>
                                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-500">{{ $currencyLabel }}</span>
                                    </div>
                                    @error('price')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Dealer Price') }}</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" name="dealer_price" value="{{ old('dealer_price') }}" class="{{ $inputBase }} pr-16 @error('dealer_price') {{ $inputError }} @enderror" placeholder="{{ __('Optional') }}" @error('dealer_price') aria-invalid="true" @enderror>
                                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-500">{{ $currencyLabel }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">{{ __('Leave empty to use dealer discount rules.') }}</p>
                                    @error('dealer_price')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Inventory') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('Track stock levels and alert thresholds.') }}</p>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">{{ __('Stock Quantity') }} <span class="text-rose-500">*</span></label>
                                    <input id="stock_quantity" type="number" name="stock_quantity" value="{{ old('stock_quantity') }}" class="{{ $inputBase }} @error('stock_quantity') {{ $inputError }} @enderror" required @error('stock_quantity') aria-invalid="true" @enderror>
                                    @error('stock_quantity')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex items-center gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    <i class="fas fa-triangle-exclamation text-accent" aria-hidden="true"></i>
                                    {{ __('Low stock alerts trigger at :count units.', ['count' => $lowStockThreshold]) }}
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Media') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('Upload a product image for the storefront.') }}</p>
                            </div>
                            <div class="p-6">
                                <label for="gallery_images" class="block text-sm font-medium text-slate-700 mb-2">{{ __('Product Image') }}</label>
                                <div class="flex flex-col gap-4">
                                    <label class="group flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-6 text-center text-sm text-slate-500 transition hover:border-info hover:text-info dark:border-slate-700 dark:hover:border-info">
                                        <input id="productImage" type="file" name="image" accept="image/*" class="hidden">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm">
                                                <i class="fas fa-cloud-upload-alt text-lg" aria-hidden="true"></i>
                                            </span>
                                            <span class="font-medium">{{ __('Drag & drop or click to upload') }}</span>
                                            <span class="text-xs text-muted">{{ __('PNG, JPG up to 2MB') }}</span>
                                        </div>
                                    </label>
                                    <div id="productImagePreview" class="hidden items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                                        <img id="productImagePreviewImg" src="" alt="{{ __('Preview') }}" class="h-20 w-20 rounded-lg object-cover">
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ __('Preview') }}</p>
                                            <p class="text-xs text-slate-500">{{ __('Image ready for upload.') }}</p>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <label class="block text-sm font-medium text-slate-700">{{ __('Gallery Images') }}</label>
                                        <input id="gallery_images" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                        <p class="mt-1 text-xs text-slate-500">{{ __('Upload multiple images. The main product image stays primary by default.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Status') }}</h3>
                                <p class="text-xs text-slate-500">{{ __('Control product visibility.') }}</p>
                            </div>
                            <div class="p-6">
                                <label class="inline-flex items-center gap-4">
                                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <span class="relative h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-info peer-focus:ring-2 peer-focus:ring-accent/40">
                                        <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                    </span>
                                    <span class="text-sm font-medium text-slate-700">{{ __('Active') }}</span>
                                </label>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">{{ __('Quick Tips') }}</h3>
                            <ul class="mt-4 space-y-3 text-sm text-slate-500">
                                <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-0.5" aria-hidden="true"></i>{{ __('Use clear product names for easier search.') }}</li>
                                <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-0.5" aria-hidden="true"></i>{{ __('Set dealer pricing to override discount rules.') }}</li>
                                <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-0.5" aria-hidden="true"></i>{{ __('Upload square images for the best fit.') }}</li>
                            </ul>
                        </div>
                    </aside>
                </div>

                <div class="sticky bottom-0 z-10 -mx-4 sm:-mx-6 lg:-mx-8 border-t border-slate-200 bg-white/90 px-4 py-4 backdrop-blur dark:bg-slate-900/80">
                    <div class="max-w-6xl mx-auto flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">{{ __('Make sure all required fields are completed before saving.') }}</p>
                        <div class="flex items-center gap-3">
                            <a href="{{ $returnTo }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:hover:bg-slate-800">{{ __('Cancel') }}</a>
                            <button id="productSubmit" type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:text-slate-900 dark:hover:bg-slate-100">
                                <span class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white/60 border-t-white dark:border-slate-900/60 dark:border-t-slate-900" data-spinner></span>
                                <span data-label>{{ __('Save Product') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        (function () {
            const form = document.getElementById('productForm');
            const submitButton = document.getElementById('productSubmit');
            const submitLabel = submitButton?.querySelector('[data-label]');
            const spinner = submitButton?.querySelector('[data-spinner]');
            const imageInput = document.getElementById('productImage');
            const preview = document.getElementById('productImagePreview');
            const previewImg = document.getElementById('productImagePreviewImg');

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
                    if (!file) {
                        preview.classList.add('hidden');
                        previewImg.src = '';
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
        })();
    </script>
</x-app-layout>
