import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('adminSidebarShell', ({ storageKey = 'admin-sidebar-collapsed' } = {}) => ({
    sidebarOpen: false,
    sidebarCollapsed: false,
    desktopQuery: null,

    init() {
        this.desktopQuery = window.matchMedia('(min-width: 1024px)');
        this.sidebarCollapsed = this.desktopQuery.matches && this.storedCollapsed();
        this.syncDocumentState();

        this.$watch('sidebarCollapsed', () => this.syncDocumentState());
        this.$watch('sidebarOpen', (open) => {
            document.documentElement.classList.toggle('admin-sidebar-drawer-open', open);
        });

        const handleResize = () => {
            if (this.desktopQuery.matches) {
                this.sidebarOpen = false;
                this.sidebarCollapsed = this.storedCollapsed();
            }
        };

        if (typeof this.desktopQuery.addEventListener === 'function') {
            this.desktopQuery.addEventListener('change', handleResize);
        } else if (typeof this.desktopQuery.addListener === 'function') {
            this.desktopQuery.addListener(handleResize);
        }
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
        document.documentElement.classList.toggle('admin-sidebar-precollapsed', this.sidebarCollapsed);
    },

    toggleSidebarCollapsed() {
        if (!this.desktopQuery?.matches) {
            this.openMobileSidebar();
            return;
        }

        this.sidebarCollapsed = !this.sidebarCollapsed;
        this.persistCollapsed();
    },

    openMobileSidebar() {
        this.sidebarOpen = true;
    },

    closeMobileSidebar() {
        this.sidebarOpen = false;
    },

    handleSidebarClick(event) {
        const link = event.target.closest('a');
        if (link && !this.desktopQuery?.matches) {
            this.closeMobileSidebar();
        }
    },
}));

Alpine.start();
