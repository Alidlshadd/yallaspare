import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('passwordVisibility', (config = {}) => ({
    visible: false,
    showLabel: config.showLabel || 'Show password',
    hideLabel: config.hideLabel || 'Hide password',
    get label() {
        return this.visible ? this.hideLabel : this.showLabel;
    },
    toggle() {
        this.visible = !this.visible;
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminSidebars, { once: true });
} else {
    initAdminSidebars();
}

Alpine.start();
