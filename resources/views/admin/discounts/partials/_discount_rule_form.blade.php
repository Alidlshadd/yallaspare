        <form
            id="discount-rule-form"
            action="{{ route('admin.discounts.update-rules') }}"
            method="POST"
            x-data="discountProductPicker({
                initialScope: @js($discountScope),
                searchUrl: @js(route('admin.discounts.products.search')),
                categoryOptions: @js(collect($categories)->map(fn ($category) => ['id' => (int) $category->id, 'name' => (string) $category->name])->values()),
                brandOptions: @js(collect($brands)->map(fn ($brand) => (string) $brand)->values()),
                initialSelectedIds: @js(array_values($selectedProducts)),
                initialSelectedProducts: @js(($selectedProductsData ?? collect())->values()),
            })"
            x-init="init()"
            class="space-y-6"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="discount_id" value="{{ $discountId }}">

            <section class="space-y-6">
                <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                    <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.95),rgba(248,250,252,0.92))] px-6 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Step 1') }}</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Basic Setup') }}</h2>
                        </div>
                        <label class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <input type="hidden" name="discounts_enabled" value="0">
                            <input type="checkbox" name="discounts_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked($discountsEnabled)>
                            {{ __('Enable Rule') }}
                        </label>
                    </div>
                    </div>

                    <div class="grid gap-4 p-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="discount_label" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Display Label') }}</label>
                            <input id="discount_label" type="text" name="discount_label" value="{{ $discountLabel }}" placeholder="{{ __('Summer Campaign, Brand Event, Clearance...') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_type" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Discount Type') }}</label>
                            <select id="discount_type" name="discount_type" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="percent" @selected($discountType === 'percent')>{{ __('Percent') }}</option>
                                <option value="fixed" @selected($discountType === 'fixed')>{{ __('Fixed') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="discount_value" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Discount Value') }}</label>
                            <input id="discount_value" type="number" step="0.01" min="0" max="100000000" name="discount_value" value="{{ $discountValue }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_minimum_subtotal" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Minimum Subtotal') }}</label>
                            <input id="discount_minimum_subtotal" type="number" step="0.01" min="0" max="100000000" name="discount_minimum_subtotal" value="{{ $discountMinimumSubtotal }}" placeholder="{{ __('Optional') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_usage_limit" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Usage Limit') }}</label>
                            <input id="discount_usage_limit" type="number" min="0" max="1000000" name="discount_usage_limit" value="{{ $discountUsageLimit }}" placeholder="{{ __('Unlimited') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                    </div>
                </article>

                <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                    <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(20,184,166,0.10),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.95),rgba(248,250,252,0.92))] px-6 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(20,184,166,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Step 2') }}</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Schedule Window') }}</h2>
                    </div>

                    <div class="grid gap-4 p-6 md:grid-cols-2">
                        <div>
                            <label for="discount_starts_at" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Starts At') }}</label>
                            <input id="discount_starts_at" type="datetime-local" name="discount_starts_at" value="{{ $discountStartsAt }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                        <div>
                            <label for="discount_ends_at" class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Ends At') }}</label>
                            <input id="discount_ends_at" type="datetime-local" name="discount_ends_at" value="{{ $discountEndsAt }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        </div>
                    </div>
                </article>

                <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                    <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(34,197,94,0.10),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.95),rgba(248,250,252,0.92))] px-6 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(34,197,94,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Step 3') }}</p>
                            <h2 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Scope Builder') }}</h2>
                        </div>
                        <div class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" x-text="scopeBadgeLabel">
                            {{ $scopeLabel }}
                        </div>
                    </div>
                    </div>

                    <div class="p-6">
                    <div>
                        <p class="mb-3 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Discount Scope') }}</p>
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <label
                                class="cursor-pointer rounded-[1.4rem] border p-4 transition"
                                :class="scope === 'all'
                                    ? 'border-emerald-400 bg-emerald-50 shadow-[0_12px_30px_rgba(16,185,129,0.12)] dark:border-emerald-500/60 dark:bg-emerald-950/20'
                                    : 'border-slate-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/40 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-emerald-900/40 dark:hover:bg-emerald-950/10'"
                            >
                                <input type="radio" name="discount_scope" value="all" x-model="scope" class="sr-only">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white dark:bg-emerald-600">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('All Products') }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Apply the rule across the full catalog.') }}</div>
                                    </div>
                                </div>
                            </label>

                            <label
                                class="cursor-pointer rounded-[1.4rem] border p-4 transition"
                                :class="scope === 'products'
                                    ? 'border-emerald-400 bg-emerald-50 shadow-[0_12px_30px_rgba(16,185,129,0.12)] dark:border-emerald-500/60 dark:bg-emerald-950/20'
                                    : 'border-slate-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/40 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-emerald-900/40 dark:hover:bg-emerald-950/10'"
                            >
                                <input type="radio" name="discount_scope" value="products" x-model="scope" class="sr-only">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white dark:bg-emerald-600">
                                        <i class="fas fa-box"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Specific Products') }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Select exact products for a focused rule.') }}</div>
                                    </div>
                                </div>
                            </label>

                            <label
                                class="cursor-pointer rounded-[1.4rem] border p-4 transition"
                                :class="scope === 'categories'
                                    ? 'border-emerald-400 bg-emerald-50 shadow-[0_12px_30px_rgba(16,185,129,0.12)] dark:border-emerald-500/60 dark:bg-emerald-950/20'
                                    : 'border-slate-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/40 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-emerald-900/40 dark:hover:bg-emerald-950/10'"
                            >
                                <input type="radio" name="discount_scope" value="categories" x-model="scope" class="sr-only">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white dark:bg-emerald-600">
                                        <i class="fas fa-layer-group"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Specific Categories') }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Target grouped product families at once.') }}</div>
                                    </div>
                                </div>
                            </label>

                            <label
                                class="cursor-pointer rounded-[1.4rem] border p-4 transition"
                                :class="scope === 'brands'
                                    ? 'border-emerald-400 bg-emerald-50 shadow-[0_12px_30px_rgba(16,185,129,0.12)] dark:border-emerald-500/60 dark:bg-emerald-950/20'
                                    : 'border-slate-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/40 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-emerald-900/40 dark:hover:bg-emerald-950/10'"
                            >
                                <input type="radio" name="discount_scope" value="brands" x-model="scope" class="sr-only">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-white dark:bg-emerald-600">
                                        <i class="fas fa-tags"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Specific Brands') }}</div>
                                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Run brand-led discounts without touching the rest.') }}</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200" x-show="scope === 'all'" x-cloak>
                        {{ __('This discount will apply across the full catalog. No additional targeting is required.') }}
                    </div>

                    <template x-for="productId in selectedProductIds" :key="productId">
                        <input type="hidden" name="discount_product_ids[]" :value="productId">
                    </template>

                    <div x-show="scope === 'products'" x-cloak x-transition.opacity.duration.150ms class="mt-6 rounded-[1.6rem] border border-slate-200/90 bg-slate-50/80 p-5 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/70">
                        <div class="grid gap-5 xl:grid-cols-[minmax(0,2.55fr)_minmax(20rem,1fr)] 2xl:grid-cols-[minmax(0,2.8fr)_minmax(21rem,1fr)]">
                            <div class="rounded-[1.5rem] border border-slate-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-950/90">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Specific Products') }}</p>
                                        <h3 class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Search and select products') }}</h3>
                                    </div>
                                    <div class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        <span x-text="meta.total"></span> {{ __('results') }}
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-4 xl:grid-cols-12">
                                    <label class="block xl:col-span-12">
                                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Search') }}</span>
                                        <input
                                            type="text"
                                            x-model="filters.query"
                                            @input.debounce.300ms="refreshProducts()"
                                            placeholder="{{ __('Search name, SKU, or brand') }}"
                                            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                        >
                                    </label>
                                    <label class="block xl:col-span-4">
                                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Category') }}</span>
                                        <select x-model="filters.categoryId" @change="refreshProducts()" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                            <option value="">{{ __('All categories') }}</option>
                                            <template x-for="category in categoryOptions" :key="category.id">
                                                <option :value="category.id" x-text="category.name"></option>
                                            </template>
                                        </select>
                                    </label>
                                    <label class="block xl:col-span-4">
                                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Brand') }}</span>
                                        <select x-model="filters.brand" @change="refreshProducts()" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                            <option value="">{{ __('All brands') }}</option>
                                            <template x-for="brand in brandOptions" :key="brand">
                                                <option :value="brand" x-text="brand"></option>
                                            </template>
                                        </select>
                                    </label>
                                    <label class="block xl:col-span-4">
                                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Stock') }}</span>
                                        <select x-model="filters.stock" @change="refreshProducts()" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                            <option value="">{{ __('All stock') }}</option>
                                            <option value="in_stock">{{ __('In stock') }}</option>
                                            <option value="low_stock">{{ __('Low stock') }}</option>
                                            <option value="out_of_stock">{{ __('Out of stock') }}</option>
                                        </select>
                                    </label>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                    <div class="flex flex-wrap items-center gap-4">
                                        <span><strong x-text="meta.total"></strong> {{ __('products found') }}</span>
                                        <span><strong x-text="selectedProductIds.length"></strong> {{ __('selected') }}</span>
                                    </div>
                                    <button type="button" @click="resetFilters()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800">
                                        {{ __('Reset Filters') }}
                                    </button>
                                </div>

                                <div class="mt-5 min-h-[24rem] rounded-[1.4rem] border border-slate-200/90 bg-slate-50/60 p-4 dark:border-slate-700/80 dark:bg-slate-900/70">
                                    <div x-show="loading" class="flex h-full min-h-[17rem] items-center justify-center text-sm text-slate-500 dark:text-slate-400" x-cloak>
                                        {{ __('Loading products...') }}
                                    </div>
                                    <div x-show="!loading && products.length === 0" class="flex h-full min-h-[17rem] items-center justify-center rounded-[1.2rem] border border-dashed border-slate-200 bg-white px-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400" x-cloak>
                                        {{ __('No products matched the current search and filters.') }}
                                    </div>
                                    <div x-show="!loading && products.length > 0" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" x-cloak>
                                        <template x-for="product in products" :key="product.id">
                                            <button
                                                type="button"
                                                @click="toggleProduct(product)"
                                                class="flex min-h-[12.5rem] flex-col rounded-[1.3rem] border p-4 text-left transition"
                                                :class="isSelected(product.id)
                                                    ? 'border-emerald-400 bg-emerald-50 shadow-[0_12px_24px_rgba(16,185,129,0.10)] dark:border-emerald-500/60 dark:bg-emerald-950/20'
                                                    : 'border-slate-200 bg-white hover:border-emerald-200 hover:bg-emerald-50/40 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-emerald-900/40 dark:hover:bg-emerald-950/10'"
                                            >
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="line-clamp-2 text-sm font-semibold leading-5 text-slate-900 dark:text-slate-100" x-text="product.name"></div>
                                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                                            <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 dark:border-slate-700 dark:bg-slate-800" x-text="product.sku || 'No SKU'"></span>
                                                            <span x-show="product.brand" class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 dark:border-slate-700 dark:bg-slate-800" x-text="product.brand"></span>
                                                        </div>
                                                    </div>
                                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border text-xs font-bold"
                                                        :class="isSelected(product.id)
                                                            ? 'border-emerald-500 bg-emerald-500 text-white'
                                                            : 'border-slate-300 bg-white text-slate-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-500'">
                                                        <span x-show="isSelected(product.id)" x-cloak><i class="fas fa-check"></i></span>
                                                        <span x-show="!isSelected(product.id)" x-cloak>+</span>
                                                    </span>
                                                </div>
                                                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" x-text="product.category || 'Uncategorized'"></span>
                                                    <span class="rounded-full px-2.5 py-1 font-semibold" :class="stockTone(product.stock_state)" x-text="stockLabel(product)"></span>
                                                </div>
                                                <div class="mt-auto pt-4">
                                                    <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                                        <span class="inline-flex h-4 w-4 items-center justify-center rounded border"
                                                            :class="isSelected(product.id)
                                                                ? 'border-emerald-500 bg-emerald-500 text-white'
                                                                : 'border-slate-300 bg-white text-transparent dark:border-slate-600 dark:bg-slate-900'">
                                                            <i class="fas fa-check text-[10px]"></i>
                                                        </span>
                                                        <span x-text="isSelected(product.id) ? 'Selected' : 'Select product'"></span>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ __('Page') }} <span x-text="meta.currentPage"></span> {{ __('of') }} <span x-text="meta.lastPage"></span>
                                    </p>
                                    <button
                                        type="button"
                                        x-show="meta.hasMore"
                                        @click="loadMoreProducts()"
                                        class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                        x-cloak
                                    >
                                        {{ __('Load More Products') }}
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-[1.5rem] border border-slate-200/90 bg-white p-5 shadow-sm xl:sticky xl:top-4 dark:border-slate-700/80 dark:bg-slate-950/90">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Selected Products') }}</p>
                                        <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100"><span x-text="selectedProductIds.length"></span> {{ __('chosen') }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="clearSelectedProducts()"
                                        :disabled="selectedProductIds.length === 0"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        {{ __('Clear All') }}
                                    </button>
                                </div>

                                <div class="mt-4 max-h-[23rem] space-y-3 overflow-y-auto pe-1" x-show="selectedProductList.length > 0" x-cloak>
                                    <template x-for="product in selectedProductList" :key="product.id">
                                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100" x-text="product.name"></div>
                                                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
                                                        <span class="rounded-full border border-slate-200 bg-white px-2 py-1 dark:border-slate-700 dark:bg-slate-800" x-text="product.sku || 'No SKU'"></span>
                                                        <span x-show="product.brand" class="rounded-full border border-slate-200 bg-white px-2 py-1 dark:border-slate-700 dark:bg-slate-800" x-text="product.brand"></span>
                                                    </div>
                                                </div>
                                                <button type="button" @click="removeProduct(product.id)" class="rounded-xl border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-950/20 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                                    {{ __('Remove') }}
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="selectedProductList.length === 0" class="mt-4 rounded-[1.2rem] border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400" x-cloak>
                                    {{ __('No products selected yet. Search the catalog and add products from the results list.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                        <div x-show="scope === 'categories'" x-cloak x-transition.opacity.duration.150ms class="rounded-[1.5rem] border border-slate-200/90 bg-slate-50/80 p-4 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/70">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Categories') }}</p>
                            <div class="mt-3 max-h-72 space-y-2 overflow-y-auto pe-1" @change="syncCategorySelection()">
                                @foreach ($categories as $category)
                                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50/60 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-emerald-900/40 dark:hover:bg-emerald-950/10">
                                        <input type="checkbox" name="discount_category_ids[]" value="{{ $category->id }}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(in_array((int) $category->id, $selectedCategories, true))>
                                        <span class="block truncate font-semibold">{{ $category->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div x-show="scope === 'brands'" x-cloak x-transition.opacity.duration.150ms class="rounded-[1.5rem] border border-slate-200/90 bg-slate-50/80 p-4 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/70">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Brands') }}</p>
                            <div class="mt-3 max-h-72 space-y-2 overflow-y-auto pe-1" @change="syncBrandSelection()">
                                @foreach ($brands as $brand)
                                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50/60 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-emerald-900/40 dark:hover:bg-emerald-950/10">
                                        <input type="checkbox" name="discount_brands[]" value="{{ $brand }}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" @checked(in_array((string) $brand, $selectedBrands, true))>
                                        <span class="block truncate font-semibold">{{ $brand }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <aside class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(20rem,0.9fr)]">
                <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                    <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.95),rgba(248,250,252,0.92))] px-6 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(16,185,129,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Preview') }}</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $isEditingDiscount ? 'Edit Summary' : 'Create Summary' }}</h2>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="rounded-[1.5rem] border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700 dark:text-emerald-300">{{ __('Discount Output') }}</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $discountValueLabel }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $discountLabel !== '' ? $discountLabel : 'No label set' }}</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Scope Summary') }}</p>
                            <p class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100" x-text="scopeBadgeLabel">{{ $scopeLabel }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="scopeUtilizationLabel">{{ $scopeUtilizationLabel }}</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Schedule Summary') }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $scheduleWindowLabel }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $discountsEnabled ? 'Ready for live pricing' : 'Safe in draft mode' }}</p>
                            <p class="mt-2 text-xs font-medium text-slate-600 dark:text-slate-300">{{ $discountLimitLabel }}</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-slate-200/90 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">{{ __('Quick Notes') }}</p>
                            <div class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/80">{{ __('Use `Storewide` for fast campaign launches.') }}</div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/80">{{ __('Targeted scopes require at least one selected item group.') }}</div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/80">{{ __('Percent rules should stay within margin tolerance.') }}</div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(248,250,252,0.98))] shadow-[0_22px_52px_rgba(15,23,42,0.08)] dark:border-slate-800 dark:bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.94))]">
                    <div class="border-b border-slate-200/80 bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.10),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.95),rgba(248,250,252,0.92))] px-6 py-5 dark:border-slate-800 dark:bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.10),transparent_28%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(15,23,42,0.92))]">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ $isEditingDiscount ? 'Update' : 'Create' }}</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $builderTitle }}</h2>
                    </div>
                    <div class="space-y-3 p-6">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-emerald-600 dark:hover:bg-emerald-700">
                            {{ $builderActionLabel }}
                        </button>
                        <a href="{{ $isEditingDiscount ? route('admin.discounts.rules') . '#discount-rule-form' : route('admin.discounts.edit') }}" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            {{ $isEditingDiscount ? 'Create New Rule' : 'Return To Coupons' }}
                        </a>
                        <div class="rounded-[1.3rem] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                            {{ $builderSupportLabel }}
                        </div>
                    </div>
                </article>
            </aside>
        </form>
