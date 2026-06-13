import './bootstrap';

import Alpine from 'alpinejs';

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
        const formData = new FormData(form);
        const action = submitter?.hasAttribute?.('formaction') ? submitter.formAction : form.action;
        const previousScrollY = window.scrollY || document.documentElement.scrollTop || 0;

        if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        }

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

            setCartSummary(nextCount, payload);
            setTemporaryButtonSuccess(button);
            showCartToast(payload?.message || 'Added to cart successfully');
            bumpCartBadge();
        } catch (error) {
            showCartToast(error?.message || 'Could not add product. Please try again.');
        } finally {
            window.scrollTo(0, previousScrollY);
            if (button) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initAdminSidebars();
        initAddToCartAnimations();
    }, { once: true });
} else {
    initAdminSidebars();
    initAddToCartAnimations();
}

Alpine.start();
