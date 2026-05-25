import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('adminSidebarShell', ({ storageKey = 'admin-sidebar-collapsed' } = {}) => ({
    sidebarOpen: false,
    sidebarCollapsed: false,
    desktopQuery: null,
    resizeHandler: null,
    initialized: false,

    init() {
        if (this.initialized) {
            return;
        }

        this.initialized = true;
        this.desktopQuery = window.matchMedia('(min-width: 1024px)');
        this.sidebarCollapsed = this.isDesktop() && this.storedCollapsed();
        this.syncDocumentState();

        this.$watch('sidebarCollapsed', () => this.syncDocumentState());
        this.$watch('sidebarOpen', (open) => {
            document.documentElement.classList.toggle('admin-sidebar-drawer-open', open);
        });

        this.resizeHandler = () => {
            if (this.isDesktop()) {
                this.sidebarOpen = false;
                this.sidebarCollapsed = this.storedCollapsed();
            } else {
                this.sidebarOpen = false;
                this.sidebarCollapsed = false;
            }

            this.syncDocumentState();
        };

        if (typeof this.desktopQuery.addEventListener === 'function') {
            this.desktopQuery.addEventListener('change', this.resizeHandler);
        } else if (typeof this.desktopQuery.addListener === 'function') {
            this.desktopQuery.addListener(this.resizeHandler);
        }
    },

    destroy() {
        if (!this.desktopQuery || !this.resizeHandler) {
            return;
        }

        if (typeof this.desktopQuery.removeEventListener === 'function') {
            this.desktopQuery.removeEventListener('change', this.resizeHandler);
        } else if (typeof this.desktopQuery.removeListener === 'function') {
            this.desktopQuery.removeListener(this.resizeHandler);
        }
    },

    isDesktop() {
        return this.desktopQuery?.matches === true;
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

    toggleSidebarCollapsed() {
        if (!this.isDesktop()) {
            this.openMobileSidebar();
            return;
        }

        this.sidebarCollapsed = !this.sidebarCollapsed;
        this.persistCollapsed();
    },

    expandSidebar() {
        if (!this.isDesktop()) {
            this.openMobileSidebar();
            return;
        }

        this.sidebarCollapsed = false;
        this.persistCollapsed();
    },

    openMobileSidebar() {
        if (this.isDesktop()) {
            this.expandSidebar();
            return;
        }

        this.sidebarCollapsed = false;
        this.sidebarOpen = true;
        this.syncDocumentState();
    },

    closeMobileSidebar() {
        this.sidebarOpen = false;
    },

    handleSidebarClick(event) {
        const link = event.target.closest('a');
        if (link && !this.isDesktop()) {
            this.closeMobileSidebar();
        }
    },
}));

Alpine.start();
