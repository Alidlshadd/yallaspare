<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800">
                <i class="fas fa-plus-circle mr-2"></i> Add New Product
            </h2>
            <a href="{{ route('admin.products.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Products
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- Main Card --}}
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    
                    {{-- Card Header --}}
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 border-b border-blue-600">
                        <h3 class="text-lg font-semibold text-white">
                            <i class="fas fa-info-circle mr-2"></i> Product Information
                        </h3>
                    </div>

                    {{-- Card Body --}}
                    <div class="p-6 space-y-6">

                        {{-- Product Names Section --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {{-- English Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-globe mr-1 text-blue-500"></i> Name (English)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="name_en" 
                                    value="{{ old('name_en') }}"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('name_en') border-red-500 @enderror"
                                    placeholder="Enter English name"
                                    required>
                                @error('name_en')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Arabic Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-language mr-1 text-green-500"></i> Name (Arabic)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="name_ar" 
                                    value="{{ old('name_ar') }}"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('name_ar') border-red-500 @enderror"
                                    placeholder="أدخل الاسم بالعربية"
                                    dir="rtl"
                                    required>
                                @error('name_ar')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Kurdish Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-language mr-1 text-orange-500"></i> Name (Kurdish)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="name_ku" 
                                    value="{{ old('name_ku') }}"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('name_ku') border-red-500 @enderror"
                                    placeholder="ناوی بەکوردی بنووسە"
                                    required>
                                @error('name_ku')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-gray-200"></div>

                        {{-- Category & Price Row --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {{-- Category --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-tags mr-1 text-purple-500"></i> Category
                                    <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    name="category_id" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('category_id') border-red-500 @enderror"
                                    required>
                                    <option value="">Select Category</option>
                                    @foreach(\App\Models\Category::all() as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name_en }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Price --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-dollar-sign mr-1 text-green-500"></i> Price
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ $systemSettings['currency_label'] ?? 'IQD' }}</span>
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        name="price" 
                                        value="{{ old('price') }}"
                                        class="w-full pl-8 pr-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('price') border-red-500 @enderror"
                                        placeholder="0.00"
                                        min="0"
                                        required>
                                </div>
                                @error('price')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Stock Quantity --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-boxes mr-1 text-blue-500"></i> Stock Quantity
                                    <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    name="stock_quantity" 
                                    value="{{ old('stock_quantity') }}"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('stock_quantity') border-red-500 @enderror"
                                    placeholder="0"
                                    min="0"
                                    required>
                                @error('stock_quantity')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-gray-200"></div>

                        {{-- Descriptions (Optional) --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {{-- English Description --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-align-left mr-1 text-blue-500"></i> Description (EN)
                                    <span class="text-gray-400 text-xs ml-1">(Optional)</span>
                                </label>
                                <textarea 
                                    name="description_en" 
                                    rows="4"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none @error('description_en') border-red-500 @enderror"
                                    placeholder="Enter product description...">{{ old('description_en') }}</textarea>
                                @error('description_en')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Arabic Description --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-align-left mr-1 text-green-500"></i> Description (AR)
                                    <span class="text-gray-400 text-xs ml-1">(Optional)</span>
                                </label>
                                <textarea 
                                    name="description_ar" 
                                    rows="4"
                                    dir="rtl"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none @error('description_ar') border-red-500 @enderror"
                                    placeholder="أدخل وصف المنتج...">{{ old('description_ar') }}</textarea>
                                @error('description_ar')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Kurdish Description --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-align-left mr-1 text-orange-500"></i> Description (KU)
                                    <span class="text-gray-400 text-xs ml-1">(Optional)</span>
                                </label>
                                <textarea 
                                    name="description_ku" 
                                    rows="4"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none @error('description_ku') border-red-500 @enderror"
                                    placeholder="وەسفی بەرهەم بنووسە...">{{ old('description_ku') }}</textarea>
                                @error('description_ku')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-gray-200"></div>

                        {{-- Image Upload --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-image mr-1 text-pink-500"></i> Product Image
                                <span class="text-gray-400 text-xs ml-1">(Optional)</span>
                            </label>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-32 h-32 bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden">
                                        <img id="imagePreview" class="hidden w-full h-full object-cover" alt="Preview">
                                        <div id="imagePlaceholder" class="text-center">
                                            <i class="fas fa-camera text-4xl text-gray-400 mb-2"></i>
                                            <p class="text-xs text-gray-500">Preview</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <input 
                                        type="file" 
                                        name="image" 
                                        id="imageInput"
                                        accept="image/*"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition @error('image') border-red-500 @enderror">
                                    <p class="mt-2 text-xs text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Recommended: JPG, PNG or WEBP. Max size: 2MB
                                    </p>
                                    @error('image')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Card Footer --}}
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-asterisk text-red-500 text-xs mr-1"></i>
                            Required fields are marked with an asterisk
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('admin.products.index') }}" 
                               class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition font-medium shadow-lg shadow-blue-500/50">
                                <i class="fas fa-save mr-2"></i> Save Product
                            </button>
                        </div>
                    </div>

                </div>

            </form>

        </div>
    </div>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Image Preview Script --}}
    <script>
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                    document.getElementById('imagePlaceholder').classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</x-app-layout>
