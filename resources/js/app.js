import './bootstrap';

import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

// Alpine.data() registrations — keeps component logic out of inline directives,
// so we can eventually switch to the CSP build of Alpine without rewrites.
// New components should follow this pattern: register here, reference by name
// via x-data="componentName" instead of inlining JS in Blade.

Alpine.data('dropdown', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
}));

Alpine.data('passwordInput', (showLabel, hideLabel) => ({
    show: false,
    _showLabel: showLabel,
    _hideLabel: hideLabel,
    toggle() { this.show = !this.show; },
    get inputType() { return this.show ? 'text' : 'password'; },
    get toggleLabel() { return this.show ? this._hideLabel : this._showLabel; },
}));

Alpine.data('mobileNav', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    get drawerClasses() { return this.open ? 'block' : 'hidden'; },
    get menuIconClasses() { return this.open ? 'hidden' : 'inline-flex'; },
    get closeIconClasses() { return this.open ? 'inline-flex' : 'hidden'; },
}));

Alpine.data('modal', (name, initialShow, focusable) => ({
    show: Boolean(initialShow),
    _name: name,
    _focusable: Boolean(focusable),
    init() {
        this.$watch('show', (value) => {
            if (value) {
                document.body.classList.add('overflow-y-hidden');
                if (this._focusable) {
                    setTimeout(() => this.firstFocusable()?.focus(), 100);
                }
            } else {
                document.body.classList.remove('overflow-y-hidden');
            }
        });
    },
    focusables() {
        const selector = 'a, button, input:not([type="hidden"]), textarea, select, details, [tabindex]:not([tabindex="-1"])';
        return Array.from(this.$el.querySelectorAll(selector))
            .filter((el) => !el.hasAttribute('disabled'));
    },
    firstFocusable() { return this.focusables()[0]; },
    lastFocusable() { return this.focusables().slice(-1)[0]; },
    nextFocusableIndex() {
        const items = this.focusables();
        return (items.indexOf(document.activeElement) + 1) % (items.length + 1);
    },
    prevFocusableIndex() {
        return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1;
    },
    nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable(); },
    prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable(); },
    onOpen(event) { if (event.detail === this._name) this.show = true; },
    onClose(event) { if (event.detail === this._name) this.show = false; },
    closeNow() { this.show = false; },
    onTabForward() { this.nextFocusable()?.focus(); },
    onTabBackward() { this.prevFocusable()?.focus(); },
}));

// --- CSP-build-safe components ------------------------------------------------
// The Alpine CSP build forbids inline expressions in x-*/@*/: attributes
// (assignments, template literals, arrow functions, global calls). Every bit of
// that logic lives here as component data/methods/getters instead, so Blade only
// references names. Behaviour is identical on the standard build.

// Generic open/close panel: replaces inline x-data="{ open:false }" + @click="open=!open".
Alpine.data('toggle', (initial = false) => ({
    open: Boolean(initial),
    toggle() { this.open = !this.open; },
    openNow() { this.open = true; },
    close() { this.open = false; },
    get ariaExpanded() { return this.open ? 'true' : 'false'; },
}));

// Password/field reveal: replaces x-data="{ show:true }" + x-init auto-hide arrow fns.
Alpine.data('reveal', (initial = true) => ({
    show: Boolean(initial),
    hide() { this.show = false; },
    autoHide(ms = 2000) { setTimeout(() => { this.show = false; }, ms); },
}));

// Store top menu: collapses on small screens, always visible ≥ 640px.
Alpine.data('storeMenu', () => ({
    open: false,
    wide: window.innerWidth >= 640,
    init() {
        this._onResize = () => { this.wide = window.innerWidth >= 640; };
        window.addEventListener('resize', this._onResize, { passive: true });
    },
    destroy() { window.removeEventListener('resize', this._onResize); },
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
    get visible() { return this.open || this.wide; },
    get ariaExpanded() { return this.open ? 'true' : 'false'; },
}));

// Account dropdown used by the user layout and account header.
Alpine.data('accountMenu', () => ({
    accountOpen: false,
    toggleAccount() { this.accountOpen = !this.accountOpen; },
    closeAccount() { this.accountOpen = false; },
    get accountAriaExpanded() { return this.accountOpen ? 'true' : 'false'; },
}));

// User storefront shell: account dropdown + mobile nav drawer in one scope.
Alpine.data('userShell', () => ({
    accountOpen: false,
    mobileNavOpen: false,
    toggleAccount() { this.accountOpen = !this.accountOpen; },
    closeAccount() { this.accountOpen = false; },
    toggleMobileNav() { this.mobileNavOpen = !this.mobileNavOpen; },
    closeMobileNav() { this.mobileNavOpen = false; },
    get mobileNavAriaExpanded() { return this.mobileNavOpen ? 'true' : 'false'; },
}));

// Orders bulk-selection checkbox state. IDs come from a data-all-ids JSON
// attribute so the x-data expression stays a bare component reference (the CSP
// build cannot evaluate array/object literals passed as arguments).
Alpine.data('ordersBulk', () => ({
    selected: [],
    allIds: [],
    init() {
        try { this.allIds = JSON.parse(this.$el.dataset.allIds || '[]'); }
        catch (e) { this.allIds = []; }
        if (!Array.isArray(this.allIds)) this.allIds = [];
    },
    toggleAll(event) { this.selected = event.target.checked ? [...this.allIds] : []; },
    clearSelection() { this.selected = []; },
    allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
}));

// Viewport-positioned dropdown menu (teleported), used by the orders row actions.
Alpine.data('positionedMenu', (width = 218, height = 174) => ({
    open: false,
    x: 0,
    y: 0,
    _w: Number(width) || 218,
    _h: Number(height) || 174,
    toggle(event) {
        if (this.open) { this.open = false; return; }
        const r = event.currentTarget.getBoundingClientRect();
        let left = r.right - this._w;
        if (document.documentElement.dir === 'rtl') { left = r.left; }
        if (left < 8) left = 8;
        if (left + this._w > window.innerWidth - 8) left = window.innerWidth - this._w - 8;
        let top = r.bottom + 6;
        if (top + this._h > window.innerHeight - 8) top = r.top - this._h - 6;
        this.x = left;
        this.y = top;
        this.open = true;
    },
    close() { this.open = false; },
    get menuStyle() { return `top:${this.y}px; left:${this.x}px;`; },
}));

// Account appearance (theme) form. Initial preference comes from a
// data-theme attribute to keep the x-data expression CSP-safe.
Alpine.data('appearanceForm', () => ({
    themePreference: 'light',
    init() {
        const normalize = (value) => (['light', 'dark'].includes(value) ? value : null);
        const initial = normalize(this.$el.dataset.theme) || 'light';
        this.themePreference = initial;
        try { localStorage.setItem('user-theme', initial); } catch (e) {}
        document.documentElement.classList.toggle('dark', initial === 'dark');
    },
    applyTheme(value) {
        const selected = ['light', 'dark'].includes(value) ? value : 'light';
        this.themePreference = selected;
        document.documentElement.classList.toggle('dark', selected === 'dark');
        try { localStorage.setItem('user-theme', selected); } catch (e) {}
    },
}));

// Storefront primary nav: category mega-menu with hover-intent open/close.
Alpine.data('storeNav', () => ({
    categoriesOpen: false,
    closeTimer: null,
    isDesktop() { return window.innerWidth >= 1024; },
    openNow() {
        this.cancelClose();
        this.categoriesOpen = true;
    },
    toggleMenu() {
        if (this.isDesktop()) {
            this.openNow();
            return;
        }
        this.categoriesOpen = !this.categoriesOpen;
    },
    queueClose() {
        if (!this.isDesktop()) return;
        this.cancelClose();
        this.closeTimer = setTimeout(() => { this.categoriesOpen = false; }, 180);
    },
    cancelClose() {
        if (this.closeTimer) {
            clearTimeout(this.closeTimer);
            this.closeTimer = null;
        }
    },
    closeNow() {
        this.cancelClose();
        this.categoriesOpen = false;
    },
}));

// Inventory "Add Movement" product autocomplete. Config (products list, i18n
// labels, restored old() values) is read from a data-config JSON attribute so
// the x-data expression stays CSP-safe (no object literal argument).
Alpine.data('inventoryForm', () => ({
    products: [],
    labels: { na: 'N/A', part: 'Part:', oem: 'OEM:', stock: 'Stock:' },
    // CSS class strings come from data-config so they live in the blade file,
    // where Tailwind's content scanner can see them.
    ui: { typeInActive: '', typeOutActive: '', typeIdle: '', projectedOk: '', projectedNegative: '' },
    productId: '',
    productSearch: '',
    productOpen: false,
    productActiveIndex: 0,
    type: 'in',
    quantity: 1,
    init() {
        let config = {};
        try { config = JSON.parse(this.$el.dataset.config || '{}'); } catch (e) { config = {}; }
        this.products = Array.isArray(config.products) ? config.products : [];
        this.labels = config.labels || this.labels;
        this.ui = config.ui || this.ui;
        this.productId = config.productId != null ? String(config.productId) : '';
        this.productSearch = config.productSearch || '';
        this.type = config.type || 'in';
        this.quantity = Number(config.quantity) || 1;
    },
    setTypeIn() { this.type = 'in'; },
    setTypeOut() { this.type = 'out'; },
    get typeInClass() { return this.type === 'in' ? this.ui.typeInActive : this.ui.typeIdle; },
    get typeOutClass() { return this.type === 'out' ? this.ui.typeOutActive : this.ui.typeIdle; },
    get hasProductSearch() { return this.productSearch !== ''; },
    get selectedStockText() { return this.selectedProduct ? String(this.selectedProduct.stock) : ''; },
    get projectedStockText() { return this.projectedStock === null ? '' : String(this.projectedStock); },
    get projectedNegative() { return this.projectedStock !== null && this.projectedStock < 0; },
    get projectedStockClass() { return this.projectedNegative ? this.ui.projectedNegative : this.ui.projectedOk; },
    get selectedProduct() {
        return this.products.find((product) => String(product.id) === String(this.productId)) || null;
    },
    get filteredProducts() {
        const term = this.productSearch.toLowerCase().trim();
        const matches = term === ''
            ? this.products
            : this.products.filter((product) => [
                product.name, product.sku, product.part_number, product.oem_number, product.brand,
            ].some((value) => String(value || '').toLowerCase().includes(term)));
        return matches.slice(0, 50);
    },
    get filteredProductsEmpty() { return this.filteredProducts.length === 0; },
    get projectedStock() {
        if (!this.selectedProduct) return null;
        return this.type === 'in'
            ? Number(this.selectedProduct.stock) + Number(this.quantity || 0)
            : Number(this.selectedProduct.stock) - Number(this.quantity || 0);
    },
    productLabel(product) {
        return product ? `${product.name} (${product.sku || this.labels.na})` : '';
    },
    productMeta(product) {
        return [
            product.brand,
            product.part_number ? `${this.labels.part} ${product.part_number}` : '',
            product.oem_number ? `${this.labels.oem} ${product.oem_number}` : '',
            `${this.labels.stock} ${product.stock}`,
        ].filter(Boolean).join(' | ');
    },
    selectProduct(product) {
        this.productId = String(product.id);
        this.productSearch = this.productLabel(product);
        this.productOpen = false;
    },
    clearProduct() {
        this.productId = '';
        this.productSearch = '';
        this.productActiveIndex = 0;
        this.productOpen = true;
        this.$nextTick(() => this.$refs.productSearch?.focus());
    },
    openList() { this.productOpen = true; },
    closeList() { this.productOpen = false; },
    onSearchInput() { this.productId = ''; this.productOpen = true; this.productActiveIndex = 0; },
    setActive(index) { this.productActiveIndex = index; },
    arrowDown() { this.productOpen = true; this.moveProductHighlight(1); },
    arrowUp() { this.productOpen = true; this.moveProductHighlight(-1); },
    moveProductHighlight(step) {
        const count = this.filteredProducts.length;
        if (!count) return;
        this.productActiveIndex = (this.productActiveIndex + step + count) % count;
    },
    commitHighlightedProduct() {
        const product = this.filteredProducts[this.productActiveIndex] || this.filteredProducts[0];
        if (product) this.selectProduct(product);
    },
}));

// Shared localStorage key for admin purchase lists: written by the Purchase
// Planning builder below and by the Stock Requests board's "Add to Purchase
// List" action, so both pages operate on the same lists.
const PURCHASE_LISTS_STORAGE_KEY = 'ys-purchase-lists-v1';

// Purchase Planning "Purchase List Builder" side dock. Multiple named lists
// with inline-editable qty/cost/notes, persisted to localStorage (no backend).
// Config (currency, page budget, i18n labels) comes from a data-config JSON
// attribute so the x-data expression stays CSP-safe. Item-level handlers read
// the item key from data-key attributes (bound via :data-key in the template)
// instead of call arguments, and add buttons in the server-rendered product
// table are synced imperatively via refreshAddButtons().
Alpine.data('purchaseBuilder', () => ({
    lists: [],
    activeIndex: 0,
    viewIndex: null,
    abcOpen: false,
    flashMessage: '',
    abc: { code: '', name: '', qty: '', cost: '', note: '' },
    currency: { label: 'IQD', decimals: 0 },
    budget: 0,
    labels: {},
    storageKey: PURCHASE_LISTS_STORAGE_KEY,
    _flashTimer: null,

    init() {
        let config = {};
        try { config = JSON.parse(this.$el.dataset.config || '{}'); } catch (e) { config = {}; }
        this.currency = config.currency || this.currency;
        this.budget = Number(config.budget) || 0;
        this.labels = config.labels || {};
        this.restore();
        this.$nextTick(() => this.refreshAddButtons());
    },

    /* ---------- persistence ---------- */
    restore() {
        let saved = null;
        try { saved = JSON.parse(safeLocalStorageGet(this.storageKey) || 'null'); } catch (e) { saved = null; }
        if (saved && Array.isArray(saved.lists) && saved.lists.length > 0) {
            this.lists = saved.lists.map((list) => this.normalizeList(list));
            this.activeIndex = Math.min(Math.max(0, Number(saved.activeIndex) || 0), this.lists.length - 1);
        } else {
            this.lists = [1, 2, 3, 4].map((n) => this.blankList(`${this.labels.listName || 'Order List'} ${n}`));
            this.activeIndex = 0;
        }
    },
    persist() {
        safeLocalStorageSet(this.storageKey, JSON.stringify({
            lists: this.lists,
            activeIndex: this.activeIndex,
        }));
    },
    // persist + resync the ✓/+ state of the product table's add buttons.
    sync() {
        this.persist();
        this.refreshAddButtons();
    },
    blankList(name) {
        return { name, status: 'draft', items: [], updatedAt: new Date().toISOString() };
    },
    normalizeList(list) {
        const statuses = ['draft', 'saved', 'ordered'];
        return {
            name: String(list.name || ''),
            status: statuses.includes(list.status) ? list.status : 'draft',
            updatedAt: String(list.updatedAt || new Date().toISOString()),
            items: (Array.isArray(list.items) ? list.items : []).map((item) => ({
                key: String(item.key || ''),
                name: String(item.name || ''),
                sku: String(item.sku || ''),
                manual: Boolean(item.manual),
                qty: Math.max(1, parseInt(item.qty, 10) || 1),
                cost: Math.max(0, Number(item.cost) || 0),
                note: String(item.note || ''),
            })),
        };
    },

    /* ---------- helpers ---------- */
    money(value) {
        const decimals = Number(this.currency.decimals) || 0;
        const amount = Number(value) || 0;
        return `${amount.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })} ${this.currency.label}`;
    },
    activeList() { return this.lists[this.activeIndex]; },
    findItem(list, key) { return list.items.find((item) => item.key === key) || null; },
    itemFromEvent(event) {
        const key = event.target.closest('[data-key]')?.dataset.key || '';
        return this.findItem(this.activeList(), key);
    },
    touch(list) { list.updatedAt = new Date().toISOString(); },
    flash(message) {
        this.flashMessage = message;
        if (this._flashTimer) clearTimeout(this._flashTimer);
        this._flashTimer = setTimeout(() => { this.flashMessage = ''; }, 2200);
    },
    get hasFlash() { return this.flashMessage !== ''; },

    /* ---------- product table ---------- */
    addFromRow(event) {
        const data = event.currentTarget.dataset;
        const list = this.activeList();
        const key = `p:${data.id}`;
        const existing = this.findItem(list, key);
        if (existing) {
            existing.qty += 1;
        } else {
            list.items.push({
                key,
                name: data.name || '',
                sku: data.sku || '',
                manual: false,
                qty: Math.max(1, parseInt(data.qty, 10) || 1),
                cost: Math.max(0, Number(data.cost) || 0),
                note: '',
            });
        }
        this.touch(list);
        this.sync();
    },
    refreshAddButtons() {
        const list = this.activeList();
        this.$root.querySelectorAll('[data-pp-add]').forEach((button) => {
            const inList = Boolean(this.findItem(list, `p:${button.dataset.id}`));
            button.classList.toggle('pp-added', inList);
            button.textContent = inList ? '✓' : '+';
            button.title = inList ? (this.labels.alreadyAdded || '') : (this.labels.addToList || '');
        });
    },

    /* ---------- tabs / lists ---------- */
    selectList(index) {
        this.activeIndex = index;
        this.sync();
    },
    newList() {
        this.lists.push(this.blankList(`${this.labels.listName || 'Order List'} ${this.lists.length + 1}`));
        this.activeIndex = this.lists.length - 1;
        this.sync();
    },
    tabLabel(index) { return `${this.labels.listShort || 'List'} ${index + 1}`; },
    tabCount(index) { return String(this.lists[index].items.length); },
    tabClass(index) { return index === this.activeIndex ? 'pp-tab-on' : ''; },
    get activeName() { return this.activeList().name; },
    onNameInput(event) {
        this.activeList().name = event.target.value;
        this.persist();
    },
    statusLabel(status) { return this.labels[`status_${status}`] || status; },
    statusClass(status) { return `pp-chip-${status}`; },
    get activeStatusLabel() { return this.statusLabel(this.activeList().status); },
    get activeStatusClass() { return this.statusClass(this.activeList().status); },

    /* ---------- dock items ---------- */
    get activeItems() { return this.activeList().items; },
    get activeEmpty() { return this.activeItems.length === 0; },
    rowTotalLabel(item) { return this.money(item.qty * item.cost); },
    onQtyInput(event) {
        const item = this.itemFromEvent(event);
        const value = parseInt(event.target.value, 10);
        if (item && Number.isFinite(value) && value >= 1) {
            item.qty = value;
            this.persist();
        }
    },
    onQtyChange(event) {
        const item = this.itemFromEvent(event);
        if (item) event.target.value = String(item.qty);
    },
    onCostInput(event) {
        const item = this.itemFromEvent(event);
        const value = Number(event.target.value);
        if (item && Number.isFinite(value) && value >= 0 && event.target.value !== '') {
            item.cost = value;
            this.persist();
        }
    },
    onCostChange(event) {
        const item = this.itemFromEvent(event);
        if (item) event.target.value = String(item.cost);
    },
    onNoteInput(event) {
        const item = this.itemFromEvent(event);
        if (item) {
            item.note = event.target.value;
            this.persist();
        }
    },
    removeItem(event) {
        const key = event.target.closest('[data-key]')?.dataset.key || '';
        const list = this.activeList();
        list.items = list.items.filter((item) => item.key !== key);
        this.touch(list);
        this.sync();
    },

    /* ---------- totals / budget ---------- */
    get itemsCountLabel() { return String(this.activeItems.length); },
    get qtyTotalLabel() {
        return this.activeItems.reduce((sum, item) => sum + item.qty, 0).toLocaleString('en-US');
    },
    get grandTotal() {
        return this.activeItems.reduce((sum, item) => sum + item.qty * item.cost, 0);
    },
    get grandTotalLabel() { return this.money(this.grandTotal); },
    get overBudget() { return this.budget > 0 && this.grandTotal > this.budget; },
    get budgetWarningLabel() {
        return `${this.labels.overBudget || 'Over budget'} — ${this.money(this.grandTotal - this.budget)}`;
    },
    get budgetRemainingLabel() {
        if (this.budget <= 0) return '';
        return `${this.labels.budgetRemaining || 'Budget remaining'}: ${this.money(this.budget - this.grandTotal)}`;
    },
    get hasBudget() { return this.budget > 0; },

    /* ---------- add by code (manual items) ---------- */
    toggleAbc() { this.abcOpen = !this.abcOpen; },
    get abcChevron() { return this.abcOpen ? '▴' : '▾'; },
    addManual() {
        const code = this.abc.code.trim();
        const qty = parseInt(this.abc.qty, 10);
        if (code === '' || !Number.isFinite(qty) || qty < 1) return;
        const list = this.activeList();
        const key = `m:${code.toLowerCase()}`;
        const existing = this.findItem(list, key);
        if (existing) {
            existing.qty += qty;
        } else {
            list.items.push({
                key,
                name: this.abc.name.trim() || code,
                sku: code,
                manual: true,
                qty,
                cost: Math.max(0, Number(this.abc.cost) || 0),
                note: this.abc.note.trim(),
            });
        }
        this.abc = { code: '', name: '', qty: '', cost: '', note: '' };
        this.touch(list);
        this.sync();
        this.flash(this.labels.manualAdded || 'Added');
    },

    /* ---------- actions ---------- */
    saveList() {
        const list = this.activeList();
        list.status = 'saved';
        this.touch(list);
        this.persist();
        this.flash(this.labels.savedFlash || 'Saved');
    },
    saveDraft() {
        const list = this.activeList();
        list.status = 'draft';
        this.touch(list);
        this.persist();
        this.flash(this.labels.draftFlash || 'Draft saved');
    },
    clearList() {
        if (!window.confirm(this.labels.confirmClear || 'Clear this list?')) return;
        const list = this.activeList();
        list.items = [];
        this.touch(list);
        this.sync();
    },
    printList() { window.print(); },
    exportCsv() {
        const list = this.activeList();
        const headers = this.labels.csvHeaders || ['Product', 'SKU', 'Quantity', 'Unit Cost', 'Row Total', 'Notes', 'Manual'];
        const rows = list.items.map((item) => [
            item.name, item.sku, item.qty, item.cost, item.qty * item.cost,
            item.note, item.manual ? 'yes' : 'no',
        ]);
        const escapeCell = (value) => {
            let cell = String(value ?? '');
            // Guard against CSV formula injection (same policy as server exports).
            if (/^[=+\-@\t\r]/.test(cell)) cell = `'${cell}`;
            return `"${cell.replace(/"/g, '""')}"`;
        };
        const csv = '\ufeff' + [headers, ...rows]
            .map((row) => row.map(escapeCell).join(','))
            .join('\r\n');
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        link.download = `${(list.name || 'purchase-list').replace(/[^\w؀-ۿ-]+/g, '-').toLowerCase()}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(link.href);
        this.flash(this.labels.exportedFlash || 'Exported');
    },

    /* ---------- recent lists ---------- */
    get recentEntries() {
        return this.lists
            .map((list, index) => ({ list, index }))
            .filter((entry) => entry.list.items.length > 0 || entry.list.status !== 'draft')
            .sort((a, b) => (a.list.updatedAt < b.list.updatedAt ? 1 : -1))
            .map((entry) => ({
                index: entry.index,
                name: entry.list.name,
                meta: `${String(entry.list.updatedAt).slice(0, 10)} · ${entry.list.items.length} ${this.labels.itemsWord || 'items'}`,
                totalLabel: this.money(entry.list.items.reduce((sum, item) => sum + item.qty * item.cost, 0)),
                statusLabel: this.statusLabel(entry.list.status),
                statusClass: this.statusClass(entry.list.status),
            }));
    },
    get recentEmpty() { return this.recentEntries.length === 0; },
    get hasRecent() { return this.recentEntries.length > 0; },
    openView(entry) { this.viewIndex = entry.index; },
    closeView() { this.viewIndex = null; },
    get viewOpen() { return this.viewIndex !== null; },
    viewedList() { return this.viewIndex === null ? null : this.lists[this.viewIndex]; },
    get viewTitle() { return this.viewedList()?.name || ''; },
    get viewMeta() {
        const list = this.viewedList();
        if (!list) return '';
        return `${String(list.updatedAt).slice(0, 10)} · ${this.statusLabel(list.status)}`;
    },
    get viewItems() {
        const list = this.viewedList();
        return (list ? list.items : []).map((item) => ({
            key: item.key,
            name: item.name,
            sku: item.sku,
            manual: item.manual,
            qtyLabel: String(item.qty),
            unitLabel: this.money(item.cost),
            totalLabel: this.money(item.qty * item.cost),
        }));
    },
    get viewTotalLabel() {
        const list = this.viewedList();
        return this.money((list ? list.items : []).reduce((sum, item) => sum + item.qty * item.cost, 0));
    },
    markOrdered() {
        const list = this.viewedList();
        if (!list) return;
        list.status = 'ordered';
        this.touch(list);
        this.persist();
        this.flash(this.labels.orderedFlash || 'Marked as ordered');
    },
    deleteViewedList() {
        if (this.viewIndex === null) return;
        if (!window.confirm(this.labels.confirmDelete || 'Delete this list?')) return;
        this.lists.splice(this.viewIndex, 1);
        if (this.lists.length === 0) {
            this.lists.push(this.blankList(`${this.labels.listName || 'Order List'} 1`));
        }
        if (this.activeIndex >= this.lists.length) this.activeIndex = this.lists.length - 1;
        this.viewIndex = null;
        this.sync();
    },

    /* ---------- print ---------- */
    get printDateLabel() { return new Date().toISOString().slice(0, 10); },
    get printItems() {
        return this.activeItems.map((item) => ({
            key: item.key,
            name: item.name,
            sku: item.sku,
            manual: item.manual,
            note: item.note,
            qtyLabel: String(item.qty),
            unitLabel: this.money(item.cost),
            totalLabel: this.money(item.qty * item.cost),
        }));
    },
}));

// Stock Requests board: waiting-customers drawer per product plus a
// cross-page "Add to Purchase List" action that writes into the same
// localStorage lists as the Purchase Planning builder. Row data arrives
// through data-product / data-subs JSON attributes rendered by Blade, so all
// x-* expressions stay CSP-safe.
Alpine.data('stockRequestsBoard', () => ({
    labels: {},
    flashMessage: '',
    inListCount: 0,
    drawerOpen: false,
    drawer: { product: null, subs: [] },
    _flashTimer: null,

    init() {
        let config = {};
        try { config = JSON.parse(this.$el.dataset.config || '{}'); } catch (e) { config = {}; }
        this.labels = config.labels || {};
        this.$nextTick(() => this.refreshAddButtons());
    },

    /* ---------- shared purchase-list storage ---------- */
    readListsState() {
        let saved = null;
        try { saved = JSON.parse(safeLocalStorageGet(PURCHASE_LISTS_STORAGE_KEY) || 'null'); } catch (e) { saved = null; }
        if (!saved || !Array.isArray(saved.lists) || saved.lists.length === 0) {
            saved = {
                lists: [1, 2, 3, 4].map((n) => ({
                    name: `${this.labels.listName || 'Order List'} ${n}`,
                    status: 'draft',
                    items: [],
                    updatedAt: new Date().toISOString(),
                })),
                activeIndex: 0,
            };
        }
        saved.activeIndex = Math.min(Math.max(0, Number(saved.activeIndex) || 0), saved.lists.length - 1);
        return saved;
    },
    writeListsState(state) {
        safeLocalStorageSet(PURCHASE_LISTS_STORAGE_KEY, JSON.stringify(state));
    },
    keyInAnyList(state, key) {
        return state.lists.some((list) => (list.items || []).some((item) => item.key === key));
    },

    /* ---------- add to purchase list ---------- */
    addProduct(product) {
        if (!product || !product.id) return;
        const state = this.readListsState();
        const list = state.lists[state.activeIndex];
        if (!Array.isArray(list.items)) list.items = [];
        const key = `p:${product.id}`;
        const existing = list.items.find((item) => item.key === key);
        if (existing) {
            existing.qty = (parseInt(existing.qty, 10) || 1) + 1;
        } else {
            list.items.push({
                key,
                name: String(product.name || ''),
                sku: String(product.sku || ''),
                manual: false,
                qty: Math.max(1, parseInt(product.qty, 10) || 1),
                cost: Math.max(0, Number(product.cost) || 0),
                note: this.labels.fromStockRequests || '',
            });
        }
        list.updatedAt = new Date().toISOString();
        this.writeListsState(state);
        this.refreshAddButtons();
        this.flash(existing ? (this.labels.qtyBumped || 'Quantity +1') : (this.labels.addedFlash || 'Added to purchase list'));
    },
    addFromRow(event) {
        let product = {};
        try { product = JSON.parse(event.currentTarget.closest('[data-product]')?.dataset.product || '{}'); } catch (e) { product = {}; }
        this.addProduct(product);
    },
    addFromDrawer() { this.addProduct(this.drawer.product); },
    refreshAddButtons() {
        const state = this.readListsState();
        let count = 0;
        this.$root.querySelectorAll('[data-sr-add]').forEach((button) => {
            const inList = this.keyInAnyList(state, `p:${button.dataset.id}`);
            if (inList) count += 1;
            button.classList.toggle('sr-added', inList);
            button.title = inList
                ? (this.labels.alreadyAdded || 'In purchase list — click to add +1')
                : (this.labels.addToList || 'Add to purchase list');
        });
        this.inListCount = count;
    },
    get inListCountLabel() { return String(this.inListCount); },
    flash(message) {
        this.flashMessage = message;
        if (this._flashTimer) clearTimeout(this._flashTimer);
        this._flashTimer = setTimeout(() => { this.flashMessage = ''; }, 2200);
    },
    get hasFlash() { return this.flashMessage !== ''; },

    /* ---------- waiting-customers drawer ---------- */
    openDrawer(event) {
        const host = event.currentTarget.closest('[data-product]');
        let product = null;
        let subs = [];
        try { product = JSON.parse(host?.dataset.product || 'null'); } catch (e) { product = null; }
        try { subs = JSON.parse(host?.dataset.subs || '[]'); } catch (e) { subs = []; }
        if (!product) return;
        this.drawer = { product, subs: Array.isArray(subs) ? subs : [] };
        this.drawerOpen = true;
    },
    closeDrawer() { this.drawerOpen = false; },
    get drawerSubs() { return this.drawer.subs; },
    get drawerEmpty() { return this.drawer.subs.length === 0; },
    get drawerName() { return this.drawer.product?.name || ''; },
    get drawerMeta() {
        const product = this.drawer.product;
        if (!product) return '';
        const parts = [];
        if (product.sku) parts.push(product.sku);
        parts.push(`${this.labels.stockWord || 'Stock'}: ${product.stock}`);
        parts.push(`${product.pending} ${this.labels.pendingWord || 'pending'}`);
        return parts.join(' · ');
    },
    get drawerNotifyUrl() { return this.drawer.product?.notifyUrl || ''; },
    get drawerHasPending() { return (this.drawer.product?.pending || 0) > 0; },
    subStatusLabel(sub) {
        return sub.status === 'notified' ? (this.labels.notified || 'Notified') : (this.labels.waiting || 'Waiting');
    },
    subChipClass(sub) {
        return sub.status === 'notified' ? 'sr-chip-notified' : 'sr-chip-waiting';
    },
}));

// Dealers Management quick-edit drawer. Row data (identity, current values,
// form URLs, preformatted labels) arrives via data-dealer JSON attributes so
// every x-* expression stays CSP-safe. The drawer's forms are plain POST
// forms hitting the existing update/demote routes.
Alpine.data('dealerBoard', () => ({
    drawerOpen: false,
    dealer: null,
    form: { status: 'active', discount: '' },

    openDrawer(event) {
        let dealer = null;
        try { dealer = JSON.parse(event.currentTarget.closest('[data-dealer]')?.dataset.dealer || 'null'); } catch (e) { dealer = null; }
        if (!dealer) return;
        this.dealer = dealer;
        this.form.status = dealer.status || 'active';
        this.form.discount = dealer.discount != null ? String(dealer.discount) : '';
        this.drawerOpen = true;
    },
    closeDrawer() { this.drawerOpen = false; },

    get drawerInitial() { return this.dealer?.initial || ''; },
    get drawerName() { return this.dealer?.name || ''; },
    get drawerMeta() { return this.dealer?.meta || ''; },
    get drawerEmail() { return this.dealer?.email || ''; },
    get drawerPhone() { return this.dealer?.phone || ''; },
    get drawerOrdersLabel() { return this.dealer?.orders || '0'; },
    get drawerRevenueLabel() { return this.dealer?.revenue || ''; },
    get drawerStatusLabel() { return this.dealer?.statusLabel || ''; },
    get drawerStatusClass() { return this.dealer?.statusClass || ''; },
    get drawerUpdateUrl() { return this.dealer?.updateUrl || ''; },
    get drawerDemoteUrl() { return this.dealer?.demoteUrl || ''; },
    get drawerViewUrl() { return this.dealer?.viewUrl || ''; },
    get drawerHasView() { return Boolean(this.dealer?.viewUrl); },
}));

const ADMIN_SIDEBAR_DEFAULT_STORAGE_KEY = 'admin-sidebar-collapsed';
const ADMIN_DESKTOP_QUERY = '(min-width: 1024px)';

const safeLocalStorageGet = (key) => {
    try {
        return window.localStorage.getItem(key);
    } catch (error) {
        return null;
    }
};

const safeLocalStorageSet = (key, value) => {
    try {
        window.localStorage.setItem(key, value);
    } catch (error) {}
};

const initAdminSidebarShell = (shell) => {
    if (!shell || shell.dataset.adminSidebarReady === '1') {
        return;
    }

    shell.dataset.adminSidebarReady = '1';

    const storageKey = shell.dataset.sidebarStorageKey || ADMIN_SIDEBAR_DEFAULT_STORAGE_KEY;
    const collapsedClass = shell.dataset.adminSidebarCollapsedClass || 'admin-sidebar-collapsed';
    const desktopQuery = window.matchMedia(ADMIN_DESKTOP_QUERY);
    const sidebar = shell.querySelector('[data-admin-sidebar]');
    const main = shell.querySelector('[data-admin-main]');
    const backdrop = shell.querySelector('[data-admin-sidebar-backdrop]');
    const desktopToggle = shell.querySelector('[data-admin-sidebar-toggle]');
    const desktopExpand = shell.querySelector('[data-admin-sidebar-expand]');
    const mobileToggle = shell.querySelector('[data-admin-mobile-sidebar-toggle]');
    const mobileClose = shell.querySelector('[data-admin-mobile-sidebar-close]');

    if (!sidebar || !main) {
        return;
    }

    let sidebarCollapsed = desktopQuery.matches && safeLocalStorageGet(storageKey) === '1';
    let mobileSidebarOpen = false;

    const isDesktop = () => desktopQuery.matches;

    const setHtmlCollapsedHint = () => {
        document.documentElement.classList.toggle(
            'admin-sidebar-precollapsed',
            isDesktop() && sidebarCollapsed,
        );
    };

    const setScrollLock = (locked) => {
        document.documentElement.classList.toggle('admin-sidebar-drawer-open', locked);
        document.body?.classList.toggle('admin-sidebar-drawer-open', locked);
        shell.classList.toggle('admin-mobile-drawer-open', locked);
    };

    const updateButtonState = () => {
        if (desktopToggle) {
            const expandLabel = desktopToggle.dataset.expandLabel || 'Expand sidebar';
            const collapseLabel = desktopToggle.dataset.collapseLabel || 'Collapse sidebar';
            const label = sidebarCollapsed ? expandLabel : collapseLabel;

            desktopToggle.setAttribute('aria-expanded', String(!sidebarCollapsed));
            desktopToggle.setAttribute('aria-label', label);
            desktopToggle.setAttribute('title', label);
        }

        if (desktopExpand) {
            desktopExpand.setAttribute('aria-expanded', String(!sidebarCollapsed));
        }

        if (mobileToggle) {
            mobileToggle.setAttribute('aria-expanded', String(mobileSidebarOpen));
        }

        if (mobileClose) {
            mobileClose.setAttribute('aria-expanded', String(mobileSidebarOpen));
        }
    };

    const applyState = () => {
        const desktop = isDesktop();
        const drawerOpen = !desktop && mobileSidebarOpen;

        if (!desktop) {
            sidebarCollapsed = false;
            shell.classList.remove(collapsedClass);
            document.documentElement.classList.remove('admin-sidebar-precollapsed');
        } else {
            shell.classList.toggle(collapsedClass, sidebarCollapsed);
            setHtmlCollapsedHint();
        }

        sidebar.classList.toggle('admin-sidebar-open', drawerOpen);
        sidebar.setAttribute('aria-hidden', String(!desktop && !drawerOpen));

        if (backdrop) {
            backdrop.hidden = !drawerOpen;
            backdrop.classList.toggle('is-open', drawerOpen);
        }

        setScrollLock(drawerOpen);
        updateButtonState();
    };

    const setDesktopCollapsed = (collapsed) => {
        if (!isDesktop()) {
            mobileSidebarOpen = true;
            applyState();
            return;
        }

        mobileSidebarOpen = false;
        sidebarCollapsed = collapsed;
        safeLocalStorageSet(storageKey, collapsed ? '1' : '0');
        applyState();
    };

    const toggleDesktopCollapsed = () => {
        setDesktopCollapsed(!sidebarCollapsed);
    };

    const openMobileSidebar = () => {
        if (isDesktop()) {
            setDesktopCollapsed(false);
            return;
        }

        sidebarCollapsed = false;
        mobileSidebarOpen = true;
        applyState();
    };

    const closeMobileSidebar = () => {
        mobileSidebarOpen = false;
        applyState();
    };

    const handleClick = (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        if (target.closest('[data-admin-sidebar-toggle]')) {
            event.preventDefault();
            event.stopPropagation();
            toggleDesktopCollapsed();
            return;
        }

        if (target.closest('[data-admin-sidebar-expand]')) {
            event.preventDefault();
            event.stopPropagation();
            setDesktopCollapsed(false);
            return;
        }

        if (target.closest('[data-admin-mobile-sidebar-toggle]')) {
            event.preventDefault();
            event.stopPropagation();
            openMobileSidebar();
            return;
        }

        if (target.closest('[data-admin-mobile-sidebar-close]')) {
            event.preventDefault();
            event.stopPropagation();
            closeMobileSidebar();
            return;
        }

        if (backdrop && target === backdrop) {
            event.preventDefault();
            event.stopPropagation();
            closeMobileSidebar();
            return;
        }

        if (!isDesktop() && mobileSidebarOpen && target.closest('[data-admin-sidebar] a')) {
            closeMobileSidebar();
        }
    };

    const handleEscape = (event) => {
        if (event.key === 'Escape' && mobileSidebarOpen) {
            closeMobileSidebar();
        }
    };

    const handleViewportChange = () => {
        if (isDesktop()) {
            mobileSidebarOpen = false;
            sidebarCollapsed = safeLocalStorageGet(storageKey) === '1';
        } else {
            sidebarCollapsed = false;
            mobileSidebarOpen = false;
        }

        applyState();
    };

    const handlePageHide = () => {
        mobileSidebarOpen = false;
        setScrollLock(false);
    };

    shell.addEventListener('click', handleClick, true);
    window.addEventListener('keydown', handleEscape);
    window.addEventListener('pagehide', handlePageHide);

    if (typeof desktopQuery.addEventListener === 'function') {
        desktopQuery.addEventListener('change', handleViewportChange);
    } else if (typeof desktopQuery.addListener === 'function') {
        desktopQuery.addListener(handleViewportChange);
    }

    applyState();
};

const initAdminSidebars = () => {
    document.querySelectorAll('[data-admin-shell]').forEach(initAdminSidebarShell);
};

// Declarative replacements for inline on* handlers, which the nonce-based
// CSP blocks (event handler attributes cannot carry a nonce).
const initDeclarativeInteractions = () => {
    // <form data-confirm="..."> — capture phase so the cancel wins before
    // the loading-state submit listener reacts.
    document.addEventListener('submit', (event) => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        const message = form?.dataset.confirm;
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    }, true);

    document.addEventListener('click', (event) => {
        const origin = event.target instanceof Element ? event.target : null;
        if (!origin) {
            return;
        }

        const stepper = origin.closest('[data-quantity-step]');
        if (stepper) {
            const input = document.getElementById(stepper.dataset.quantityTarget || '');
            if (input) {
                if (stepper.dataset.quantityStep === 'up') {
                    input.stepUp();
                } else {
                    input.stepDown();
                }
                input.form?.requestSubmit();
            }
            return;
        }

        const submitLink = origin.closest('[data-submit-closest-form]');
        if (submitLink) {
            event.preventDefault();
            submitLink.closest('form')?.submit();
            return;
        }

        if (origin.closest('[data-history-back]')) {
            window.history.back();
        }
    });

    document.addEventListener('change', (event) => {
        const input = event.target instanceof Element ? event.target.closest('[data-submit-on-change]') : null;
        input?.form?.requestSubmit();
    });

    // Brand logo fallback. The error event does not bubble, so listen in
    // capture phase — and sweep images that already failed before this ran.
    const applyBrandFallback = (img) => {
        img.style.display = 'none';
        const fallback = img.nextElementSibling;
        if (fallback instanceof HTMLElement) {
            fallback.style.display = 'inline-flex';
        }
    };
    document.addEventListener('error', (event) => {
        const img = event.target;
        if (img instanceof HTMLImageElement && img.hasAttribute('data-brand-logo')) {
            applyBrandFallback(img);
        }
    }, true);
    document.querySelectorAll('img[data-brand-logo]').forEach((img) => {
        if (img.complete && img.naturalWidth === 0) {
            applyBrandFallback(img);
        }
    });
};

const findVisibleElement = (elements) => elements.find((element) => {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    const rect = element.getBoundingClientRect();
    const styles = window.getComputedStyle(element);

    return rect.width > 0
        && rect.height > 0
        && styles.visibility !== 'hidden'
        && styles.display !== 'none';
});

const cartBadges = () => Array.from(document.querySelectorAll('[data-cart-count-badge]'));
const cartItemsLabels = () => Array.from(document.querySelectorAll('[data-cart-items-label]'));
const cartRefs = () => Array.from(document.querySelectorAll('[data-cart-ref]'));
const cartTotals = () => Array.from(document.querySelectorAll('[data-cart-total]'));

const currentCartCount = () => {
    const badge = cartBadges()[0];
    if (!badge) {
        return 0;
    }

    const raw = Number.parseInt(badge.dataset.cartCountValue || '0', 10);
    return Number.isNaN(raw) ? 0 : Math.max(0, raw);
};

const setCartCount = (count) => {
    const normalized = Math.max(0, count);

    cartBadges().forEach((badge) => {
        badge.dataset.cartCountValue = String(normalized);
        badge.textContent = normalized > 99 ? '99+' : String(normalized);
    });
};

const setCartSummary = (count, payload = null) => {
    const normalized = Math.max(0, count);

    setCartCount(normalized);

    const itemsLabel = payload?.cart_items_label || `Items (${normalized})`;
    cartItemsLabels().forEach((element) => {
        element.textContent = itemsLabel;
    });

    if (typeof payload?.cart_ref === 'string' && payload.cart_ref !== '') {
        cartRefs().forEach((element) => {
            element.textContent = payload.cart_ref;
        });
    }

    if (typeof payload?.cart_total_formatted === 'string' && payload.cart_total_formatted !== '') {
        cartTotals().forEach((element) => {
            element.textContent = payload.cart_total_formatted;
        });
    }
};

const bumpCartBadge = () => {
    const target = findVisibleElement(cartBadges());
    if (!target) {
        return;
    }

    target.classList.remove('cart-badge-bump');
    window.requestAnimationFrame(() => {
        target.classList.add('cart-badge-bump');
    });
};

const showCartToast = (message) => {
    const text = message || 'Added to cart successfully';
    const existing = document.querySelector('.cart-toast');

    if (existing) {
        existing.remove();
    }

    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.textContent = text;

    document.body.appendChild(toast);

    window.requestAnimationFrame(() => {
        toast.classList.add('is-visible');
    });

    window.setTimeout(() => {
        toast.classList.remove('is-visible');
        window.setTimeout(() => toast.remove(), 220);
    }, 2200);
};

const setTemporaryButtonSuccess = (button) => {
    if (!button) {
        return;
    }

    const originalText = button.dataset.originalText || button.textContent.trim();
    button.dataset.originalText = originalText;
    button.textContent = button.dataset.addedText || 'Added ✓';
    button.classList.add('cart-button-added');

    window.clearTimeout(Number(button.dataset.resetTimer || 0));
    button.dataset.resetTimer = String(window.setTimeout(() => {
        button.textContent = originalText;
        button.classList.remove('cart-button-added');
        delete button.dataset.resetTimer;
    }, 1500));
};

const initAddToCartAnimations = () => {
    if (window.YallaCartFeedbackInitialized) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        return;
    }

    window.YallaCartFeedbackInitialized = true;

    const cartT = (key, fallback) => (window.YallaI18n && window.YallaI18n[key]) || fallback;
    const Loader = () => window.YallaLoading || null;

    document.addEventListener('submit', async (event) => {
        const form = event.target instanceof HTMLFormElement
            ? event.target
            : event.target?.closest?.('form');

        if (!form || !form.classList.contains('js-add-cart-form')) {
            return;
        }

        const submitter = event.submitter;

        if (submitter && !submitter.classList.contains('js-add-cart-button')) {
            return;
        }

        event.preventDefault();

        const button = form.querySelector('.js-add-cart-button');

        // Synchronous double-click guard: runs before fetch starts.
        if (button && (button.dataset.inFlight === 'true' || button.dataset.cooldownActive === 'true')) {
            return;
        }
        if (button) {
            button.dataset.inFlight = 'true';
        }

        const formData = new FormData(form);
        const action = submitter?.hasAttribute?.('formaction') ? submitter.formAction : form.action;
        const previousScrollY = window.scrollY || document.documentElement.scrollTop || 0;
        const loader = Loader();

        // Level 1: button text + disabled state immediately.
        if (loader) {
            loader.setButtonLoading(button, true, cartT('adding', 'Adding...'));
        } else if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        }

        // Level 2: schedule branded overlay only if request > 400ms.
        if (loader) {
            loader.showSlowRequestLoading();
        }

        let inCooldown = false;

        try {
            const response = await fetch(action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
                credentials: 'same-origin',
            });

            if (loader) {
                loader.hideSlowRequestLoading();
            }

            // Level 3: rate limit — branded cooldown, NOT the toast/error flow.
            if (response.status === 429) {
                const headerValue = parseInt(response.headers.get('Retry-After'), 10);
                const seconds = Number.isFinite(headerValue) && headerValue > 0 ? headerValue : 10;
                inCooldown = true;
                if (loader) {
                    loader.showRateLimitCooldown(seconds, button);
                }
                return;
            }

            let payload = null;
            let nextCount = currentCartCount() + 1;
            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = null;
                }
            }

            if (!response.ok) {
                throw new Error(payload?.message || 'Could not add product. Please try again.');
            }

            if (payload) {
                if (Number.isInteger(payload?.cart_count)) {
                    nextCount = payload.cart_count;
                }
            }

            // Restore Level 1 text before success animation captures original.
            if (loader) {
                loader.setButtonLoading(button, false);
            }
            setCartSummary(nextCount, payload);
            setTemporaryButtonSuccess(button);
            showCartToast(payload?.message || 'Added to cart successfully');
            bumpCartBadge();
        } catch (error) {
            if (loader) {
                loader.hideSlowRequestLoading();
                loader.setButtonLoading(button, false);
            }
            showCartToast(error?.message || 'Could not add product. Please try again.');
        } finally {
            window.scrollTo(0, previousScrollY);
            if (button) {
                delete button.dataset.inFlight;
                if (!inCooldown && button.dataset.cooldownActive !== 'true') {
                    if (loader) {
                        loader.setButtonLoading(button, false);
                    } else {
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                    }
                }
            }
        }
    });
};

const initLoadingSystem = () => {
    if (window.YallaLoadingInitialized) {
        return;
    }

    window.YallaLoadingInitialized = true;

    const globalLoader = document.querySelector('[data-loading-overlay]');
    let showTimer = null;
    let fallbackTimer = null;

    const setLoaderMessage = (message) => {
        const messageElement = globalLoader?.querySelector('[data-loading-message]');
        if (messageElement && message) {
            messageElement.textContent = message;
        }
    };

    const hideGlobalLoader = () => {
        window.clearTimeout(showTimer);
        window.clearTimeout(fallbackTimer);
        showTimer = null;
        fallbackTimer = null;

        if (!globalLoader) {
            return;
        }

        globalLoader.classList.add('is-hidden');
        globalLoader.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('ys-loading-active');
    };

    const showGlobalLoader = (message = 'Loading', delay = 120, timeout = 15000) => {
        if (!globalLoader) {
            return;
        }

        window.clearTimeout(showTimer);
        window.clearTimeout(fallbackTimer);
        setLoaderMessage(message);

        showTimer = window.setTimeout(() => {
            globalLoader.classList.remove('is-hidden');
            globalLoader.setAttribute('aria-hidden', 'false');
            document.documentElement.classList.add('ys-loading-active');

            fallbackTimer = window.setTimeout(() => {
                hideGlobalLoader();
            }, timeout);
        }, delay);
    };

    const t = (key, fallback) => (window.YallaI18n && window.YallaI18n[key]) || fallback;

    const setButtonLoading = (button, isLoading, loadingText = null) => {
        if (!button) return;
        if (isLoading) {
            if (loadingText && !button.dataset.loadingPreviousText) {
                button.dataset.loadingPreviousText = button.textContent;
                button.textContent = loadingText;
            }
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        } else {
            if (button.dataset.loadingPreviousText !== undefined) {
                button.textContent = button.dataset.loadingPreviousText;
                delete button.dataset.loadingPreviousText;
            }
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    };

    const showSlowRequestLoading = (message) => {
        showGlobalLoader(message || t('processing', 'Processing your request...'), 800);
    };

    const hideSlowRequestLoading = () => {
        hideGlobalLoader();
    };

    let cooldownInterval = null;
    let cooldownButton = null;

    const clearCooldownState = () => {
        if (cooldownInterval) {
            window.clearInterval(cooldownInterval);
            cooldownInterval = null;
        }
        if (cooldownButton) {
            delete cooldownButton.dataset.cooldownActive;
            setButtonLoading(cooldownButton, false);
            cooldownButton = null;
        }
    };

    const showRateLimitCooldown = (seconds, button) => {
        // Cancel any pending slow-request show timer; the cooldown takes over immediately.
        hideGlobalLoader();
        clearCooldownState();

        let remaining = Math.max(1, Math.floor(Number(seconds)) || 10);
        // sr-only message — visually nothing changes during countdown.
        setLoaderMessage(t('waitBeforeRetry', 'Please wait before trying again.'));

        if (globalLoader) {
            window.clearTimeout(showTimer);
            window.clearTimeout(fallbackTimer);
            globalLoader.classList.remove('is-hidden');
            globalLoader.setAttribute('aria-hidden', 'false');
            document.documentElement.classList.add('ys-loading-active');
        }

        if (button) {
            cooldownButton = button;
            button.dataset.cooldownActive = 'true';
            setButtonLoading(button, true);
        }

        cooldownInterval = window.setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                hideGlobalLoader();
                clearCooldownState();
            }
            // No visible re-render — countdown is hidden from the user.
        }, 1000);
    };

    window.YallaLoading = {
        show: showGlobalLoader,
        hide: hideGlobalLoader,
        setButtonLoading,
        showSlowRequestLoading,
        hideSlowRequestLoading,
        showRateLimitCooldown,
    };

    const shouldSkipFormLoading = (form) => {
        if (!form || form.dataset.loadingSkip === 'true' || form.dataset.loading === 'false') {
            return true;
        }

        if (form.matches('.js-add-cart-form, .js-wishlist-form')) {
            return true;
        }

        if (form.target && form.target !== '_self') {
            return true;
        }

        if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') {
            return true;
        }

        return !(
            form.matches('[data-loading-form], [data-auth-form]')
            || form.querySelector('input[type="file"]')
        );
    };

    const preserveSubmitterValue = (form, submitter) => {
        if (!submitter?.name || submitter.disabled) {
            return;
        }

        const mirror = document.createElement('input');
        mirror.type = 'hidden';
        mirror.name = submitter.name;
        mirror.value = submitter.value || '';
        mirror.dataset.loadingSubmitterMirror = 'true';
        form.appendChild(mirror);
    };

    const markButtonLoading = (button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        if (!Array.from(button.children).some((child) => child.classList?.contains('ys-button-spinner'))) {
            const spinner = document.createElement('span');
            spinner.className = 'ys-button-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            button.prepend(spinner);
        }

        const loadingText = button.dataset.loadingText || button.closest('form')?.dataset.loadingButtonText;
        const textNode = Array.from(button.childNodes).find((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '');
        if (loadingText && !button.dataset.loadingOriginalText) {
            button.dataset.loadingOriginalText = button.textContent.trim();
            if (textNode) {
                textNode.textContent = ` ${loadingText}`;
            } else {
                button.append(document.createTextNode(` ${loadingText}`));
            }
        }

        button.classList.add('ys-button-loading');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
    };

    const markFormSubmitting = (form, submitter) => {
        if (form.dataset.loadingSubmitting === '1') {
            return false;
        }

        form.dataset.loadingSubmitting = '1';
        preserveSubmitterValue(form, submitter);

        const buttons = submitter instanceof HTMLButtonElement
            ? [submitter]
            : Array.from(form.querySelectorAll('button[type="submit"], button:not([type])'));

        buttons.forEach(markButtonLoading);

        return true;
    };

    document.addEventListener('submit', (event) => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || event.defaultPrevented || shouldSkipFormLoading(form)) {
            return;
        }

        const submitter = event.submitter || document.activeElement;
        if (!markFormSubmitting(form, submitter)) {
            event.preventDefault();
            return;
        }

        const hasFile = Array.from(form.querySelectorAll('input[type="file"]')).some((input) => input.files?.length > 0);
        const shouldShowOverlay = form.dataset.loadingOverlay === 'true' || hasFile || form.dataset.loadingKind === 'checkout';
        if (shouldShowOverlay) {
            const fallbackMessage = form.dataset.loadingKind === 'checkout'
                ? 'Placing your order securely...'
                : (hasFile ? 'Uploading, please wait...' : 'Processing, please wait...');
            showGlobalLoader(form.dataset.loadingMessage || fallbackMessage, 80, 15000);
        }
    });

    window.addEventListener('pageshow', hideGlobalLoader);
    window.addEventListener('load', hideGlobalLoader);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            hideGlobalLoader();
        }
    });

    hideGlobalLoader();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initAdminSidebars();
        initAddToCartAnimations();
        initLoadingSystem();
        initDeclarativeInteractions();
    }, { once: true });
} else {
    initAdminSidebars();
    initAddToCartAnimations();
    initLoadingSystem();
    initDeclarativeInteractions();
}

Alpine.start();
