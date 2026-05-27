import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('adminSidebarShell', ({ storageKey = 'admin-sidebar-collapsed' } = {}) => ({
    sidebarCollapsed: false,
    mobileSidebarOpen: false,
    isRtl: false,
    desktopQuery: null,
    resizeHandler: null,
    escapeHandler: null,
    pageHideHandler: null,
    initialized: false,

    init() {
        if (this.initialized) {
            return;
        }

        this.initialized = true;
        this.isRtl = document.documentElement.getAttribute('dir') === 'rtl';
        this.desktopQuery = window.matchMedia('(min-width: 1024px)');
        this.sidebarCollapsed = this.isDesktop() && this.storedCollapsed();
        this.mobileSidebarOpen = false;
        this.syncMobileDrawerState(false);
        this.syncDocumentState();

        this.$watch('sidebarCollapsed', () => this.syncDocumentState());
        this.$watch('mobileSidebarOpen', (open) => {
            this.syncMobileDrawerState(open);
        });

        this.resizeHandler = () => {
            if (this.isDesktop()) {
                this.mobileSidebarOpen = false;
                this.sidebarCollapsed = this.storedCollapsed();
            } else {
                this.mobileSidebarOpen = false;
                this.sidebarCollapsed = false;
            }

            this.syncMobileDrawerState(false);
            this.syncDocumentState();
        };

        if (typeof this.desktopQuery.addEventListener === 'function') {
            this.desktopQuery.addEventListener('change', this.resizeHandler);
        } else if (typeof this.desktopQuery.addListener === 'function') {
            this.desktopQuery.addListener(this.resizeHandler);
        }

        this.escapeHandler = (event) => {
            if (event.key === 'Escape' && this.mobileSidebarOpen) {
                this.closeMobileSidebar();
            }
        };
        this.pageHideHandler = () => this.syncMobileDrawerState(false);

        window.addEventListener('keydown', this.escapeHandler);
        window.addEventListener('pagehide', this.pageHideHandler);
    },

    destroy() {
        this.mobileSidebarOpen = false;
        this.syncMobileDrawerState(false);

        if (this.desktopQuery && this.resizeHandler) {
            if (typeof this.desktopQuery.removeEventListener === 'function') {
                this.desktopQuery.removeEventListener('change', this.resizeHandler);
            } else if (typeof this.desktopQuery.removeListener === 'function') {
                this.desktopQuery.removeListener(this.resizeHandler);
            }
        }

        if (this.escapeHandler) {
            window.removeEventListener('keydown', this.escapeHandler);
        }

        if (this.pageHideHandler) {
            window.removeEventListener('pagehide', this.pageHideHandler);
        }
    },

    isDesktop() {
        return this.desktopQuery ? this.desktopQuery.matches === true : window.innerWidth >= 1024;
    },

    storedCollapsed() {
        try {
            return window.localStorage.getItem(storageKey) === '1';
        } catch (error) {
            return false;
        }
    },

    persistCollapsed() {
        try {
            window.localStorage.setItem(storageKey, this.sidebarCollapsed ? '1' : '0');
        } catch (error) {}
    },

    syncDocumentState() {
        document.documentElement.classList.toggle('admin-sidebar-precollapsed', this.isDesktop() && this.sidebarCollapsed);
    },

    syncMobileDrawerState(open) {
        const shouldLock = open && !this.isDesktop();

        document.documentElement.classList.toggle('admin-sidebar-drawer-open', shouldLock);
        document.body?.classList.toggle('admin-sidebar-drawer-open', shouldLock);
        this.$root?.classList.toggle('admin-mobile-drawer-open', shouldLock);
    },

    toggleSidebarCollapsed() {
        if (!this.isDesktop()) {
            this.openMobileSidebar();
            return;
        }

        this.mobileSidebarOpen = false;
        this.syncMobileDrawerState(false);
        this.sidebarCollapsed = !this.sidebarCollapsed;
        this.persistCollapsed();
        this.syncDocumentState();
    },

    expandSidebar() {
        if (!this.isDesktop()) {
            this.openMobileSidebar();
            return;
        }

        this.mobileSidebarOpen = false;
        this.syncMobileDrawerState(false);
        this.sidebarCollapsed = false;
        this.persistCollapsed();
        this.syncDocumentState();
    },

    openMobileSidebar() {
        if (this.isDesktop()) {
            this.expandSidebar();
            return;
        }

        this.sidebarCollapsed = false;
        this.mobileSidebarOpen = true;
        this.syncDocumentState();
        this.syncMobileDrawerState(true);
    },

    closeMobileSidebar() {
        this.mobileSidebarOpen = false;
        this.syncMobileDrawerState(false);
    },

    handleShellClick(event) {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        const toggle = target.closest('[data-admin-sidebar-toggle]');
        if (toggle) {
            event.preventDefault();
            event.stopPropagation();
            this.toggleSidebarCollapsed();
            return;
        }

        const expand = target.closest('[data-admin-sidebar-expand]');
        if (expand) {
            event.preventDefault();
            event.stopPropagation();
            this.expandSidebar();
            return;
        }

        const mobileOpen = target.closest('[data-admin-mobile-sidebar-toggle]');
        if (mobileOpen) {
            event.preventDefault();
            event.stopPropagation();
            this.openMobileSidebar();
            return;
        }

        const mobileClose = target.closest('[data-admin-mobile-sidebar-close]');
        if (mobileClose) {
            event.preventDefault();
            event.stopPropagation();
            this.closeMobileSidebar();
        }
    },

    handleSidebarClick(event) {
        const target = event.target instanceof Element ? event.target : null;
        const link = target?.closest('a');
        if (link && !this.isDesktop()) {
            this.closeMobileSidebar();
        }
    },
}));

Alpine.start();
