// Header theme switch. The layouts already apply the stored theme before paint;
// this module only keeps the switch in sync and reacts to clicks.
const currentTheme = () => (document.documentElement.classList.contains('dark') ? 'dark' : 'light');

const storeTheme = (key, theme) => {
    try {
        localStorage.setItem(key, theme);
    } catch (error) {
        // Storage can be unavailable (private mode, blocked cookies) — the
        // theme still applies for the current page.
    }
};

// Authenticated storefront users read their theme from theme_preference on the
// server, so the switch has to write it back or the choice is lost on reload.
const persistTheme = (url, theme) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    if (! token) {
        return;
    }

    const body = new FormData();
    body.append('_method', 'PATCH');
    body.append('_token', token);
    body.append('theme_preference', theme);

    fetch(url, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        redirect: 'manual',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).catch(() => {});
};

export const initThemeToggles = () => {
    const roots = Array.from(document.querySelectorAll('[data-theme-toggle]'));

    if (roots.length === 0) {
        return;
    }

    const paint = (theme) => {
        roots.forEach((root) => {
            root.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            root.title = theme === 'dark'
                ? (root.dataset.themeLabelLight || '')
                : (root.dataset.themeLabelDark || '');
        });
    };

    const applyTheme = (theme) => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        paint(theme);
    };

    paint(currentTheme());

    // Enable the icon transition only after the stored theme is on screen,
    // otherwise a dark reload animates its way in from the wrong icon.
    requestAnimationFrame(() => {
        roots.forEach((root) => root.setAttribute('data-theme-ready', ''));
    });

    // The appearance settings page flips the theme on its own, so mirror any
    // change made outside the switch instead of going stale.
    new MutationObserver(() => paint(currentTheme())).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });

    roots.forEach((root) => {
        root.addEventListener('click', () => {
            const theme = currentTheme() === 'dark' ? 'light' : 'dark';

            applyTheme(theme);
            storeTheme(root.dataset.themeStorage || 'user-theme', theme);

            if (root.dataset.themePersist) {
                persistTheme(root.dataset.themePersist, theme);
            }
        });
    });
};
