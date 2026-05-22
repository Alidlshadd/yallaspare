@push('scripts')
<script>
    window.discountProductPicker = function (config) {
        return {
            scope: config.initialScope ?? 'all',
            searchUrl: config.searchUrl,
            categoryOptions: Array.isArray(config.categoryOptions) ? config.categoryOptions : [],
            brandOptions: Array.isArray(config.brandOptions) ? config.brandOptions : [],
            filters: {
                query: '',
                categoryId: '',
                brand: '',
                stock: '',
            },
            products: [],
            loading: false,
            initializedProductResults: false,
            requestId: 0,
            selectedCategoryCount: 0,
            selectedBrandCount: 0,
            selectedProductIds: Array.from(new Set((config.initialSelectedIds || [])
                .map((id) => Number(id))
                .filter((id) => Number.isInteger(id) && id > 0))),
            selectedProductMap: {},
            meta: {
                currentPage: 1,
                lastPage: 1,
                perPage: 18,
                total: 0,
                hasMore: false,
            },
            init() {
                const initialSelectedProducts = Array.isArray(config.initialSelectedProducts) ? config.initialSelectedProducts : [];
                initialSelectedProducts.forEach((product) => {
                    const normalized = this.normalizeProduct(product);
                    this.selectedProductMap[String(normalized.id)] = normalized;
                });

                this.syncCategorySelection();
                this.syncBrandSelection();

                this.$watch('scope', (value) => {
                    if (value === 'products' && !this.initializedProductResults) {
                        this.refreshProducts();
                    }
                });

                if (this.scope === 'products') {
                    this.refreshProducts();
                }
            },
            get selectedProductList() {
                return this.selectedProductIds
                    .map((id) => this.selectedProductMap[String(id)])
                    .filter(Boolean);
            },
            get scopeBadgeLabel() {
                if (this.scope === 'products') return @json(__('Product Targeting'));
                if (this.scope === 'categories') return @json(__('Category Targeting'));
                if (this.scope === 'brands') return @json(__('Brand Targeting'));
                return @json(__('Storewide'));
            },
            get scopeUtilizationLabel() {
                if (this.scope === 'products') {
                    const count = this.selectedProductIds.length;
                    return `${count.toLocaleString()} product${count === 1 ? '' : 's'} selected`;
                }

                if (this.scope === 'categories') {
                    return `${this.selectedCategoryCount.toLocaleString()} of ${this.categoryOptions.length.toLocaleString()} categories`;
                }

                if (this.scope === 'brands') {
                    return `${this.selectedBrandCount.toLocaleString()} of ${this.brandOptions.length.toLocaleString()} brands`;
                }

                return 'Applies across the full catalog';
            },
            normalizeProduct(product) {
                return {
                    id: Number(product.id),
                    name: String(product.name || ''),
                    sku: product.sku ? String(product.sku) : '',
                    brand: product.brand ? String(product.brand) : '',
                    category: product.category ? String(product.category) : '',
                    stock_quantity: Number(product.stock_quantity || 0),
                    stock_state: String(product.stock_state || 'in_stock'),
                };
            },
            isSelected(id) {
                return this.selectedProductIds.includes(Number(id));
            },
            toggleProduct(product) {
                const normalized = this.normalizeProduct(product);

                if (this.isSelected(normalized.id)) {
                    this.removeProduct(normalized.id);
                    return;
                }

                this.selectedProductIds = [...this.selectedProductIds, normalized.id];
                this.selectedProductMap[String(normalized.id)] = normalized;
            },
            removeProduct(id) {
                const numericId = Number(id);
                this.selectedProductIds = this.selectedProductIds.filter((productId) => productId !== numericId);
                delete this.selectedProductMap[String(numericId)];
            },
            clearSelectedProducts() {
                this.selectedProductIds = [];
                this.selectedProductMap = {};
            },
            syncCategorySelection() {
                this.selectedCategoryCount = this.$root.querySelectorAll('input[name="discount_category_ids[]"]:checked').length;
            },
            syncBrandSelection() {
                this.selectedBrandCount = this.$root.querySelectorAll('input[name="discount_brands[]"]:checked').length;
            },
            stockLabel(product) {
                if (product.stock_state === 'out_of_stock') return @json(__('Out of stock'));
                if (product.stock_state === 'low_stock') return `${@json(__('Low stock'))}: ${Number(product.stock_quantity || 0)}`;
                return `${@json(__('In stock'))}: ${Number(product.stock_quantity || 0)}`;
            },
            stockTone(stockState) {
                if (stockState === 'out_of_stock') return 'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300';
                if (stockState === 'low_stock') return 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300';
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300';
            },
            resetFilters() {
                this.filters = {
                    query: '',
                    categoryId: '',
                    brand: '',
                    stock: '',
                };

                this.refreshProducts();
            },
            refreshProducts() {
                if (this.scope !== 'products') {
                    return;
                }

                this.fetchProducts(1, false);
            },
            loadMoreProducts() {
                if (this.loading || !this.meta.hasMore) {
                    return;
                }

                this.fetchProducts(this.meta.currentPage + 1, true);
            },
            async fetchProducts(page = 1, append = false) {
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(this.meta.perPage || 18),
                });

                if (this.filters.query.trim() !== '') {
                    params.set('q', this.filters.query.trim());
                }

                if (this.filters.categoryId !== '') {
                    params.set('category_id', this.filters.categoryId);
                }

                if (this.filters.brand !== '') {
                    params.set('brand', this.filters.brand);
                }

                if (this.filters.stock !== '') {
                    params.set('stock', this.filters.stock);
                }

                const requestId = ++this.requestId;
                this.initializedProductResults = true;
                this.loading = true;

                try {
                    const response = await fetch(`${this.searchUrl}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error(`Product search failed with status ${response.status}`);
                    }

                    const payload = await response.json();

                    if (requestId !== this.requestId) {
                        return;
                    }

                    const nextProducts = Array.isArray(payload.data)
                        ? payload.data.map((product) => this.normalizeProduct(product))
                        : [];
                    const nextMeta = payload.meta || {};

                    nextProducts.forEach((product) => {
                        if (this.isSelected(product.id)) {
                            this.selectedProductMap[String(product.id)] = product;
                        }
                    });

                    if (append) {
                        const existingIds = new Set(this.products.map((product) => product.id));
                        this.products = [...this.products, ...nextProducts.filter((product) => !existingIds.has(product.id))];
                    } else {
                        this.products = nextProducts;
                    }

                    this.meta = {
                        currentPage: Number(nextMeta.current_page || page),
                        lastPage: Number(nextMeta.last_page || page),
                        perPage: Number(nextMeta.per_page || this.meta.perPage || 18),
                        total: Number(nextMeta.total || 0),
                        hasMore: Boolean(nextMeta.has_more),
                    };
                } catch (error) {
                    if (requestId !== this.requestId) {
                        return;
                    }

                    if (!append) {
                        this.products = [];
                    }

                    this.meta = {
                        currentPage: 1,
                        lastPage: 1,
                        perPage: this.meta.perPage || 18,
                        total: 0,
                        hasMore: false,
                    };
                } finally {
                    if (requestId === this.requestId) {
                        this.loading = false;
                    }
                }
            },
        };
    };
</script>
@endpush
