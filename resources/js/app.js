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
