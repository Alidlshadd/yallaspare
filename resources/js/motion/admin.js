// Admin panel motion entry (Vite input, loaded only on admin.* routes).
// Central gates: never boots under prefers-reduced-motion, never boots
// outside the admin shell.
import { prefersReducedMotion } from './config';
import { initReveal } from './reveal';
import { initInteractions } from './interactions';

const boot = () => {
    if (prefersReducedMotion() || !document.querySelector('[data-admin-shell]')) {
        return;
    }

    initReveal();
    initInteractions();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
