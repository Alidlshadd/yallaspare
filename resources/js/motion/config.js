// Shared tokens for the admin motion system. Every module animates
// transform/opacity-family properties only, and must be booted through
// admin.js so the prefers-reduced-motion check stays the single kill-switch.
import { spring } from 'motion';

export const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export const isRtl = () => document.documentElement.dir === 'rtl';

// Expo-style settle for scroll reveals — fast start, soft landing, no overshoot.
export const revealTransition = { duration: 0.6, ease: [0.22, 1, 0.36, 1] };

// Springs for interactive feedback (hover lift, press release).
export const softSpring = { type: spring, stiffness: 320, damping: 26 };
export const snappySpring = { type: spring, stiffness: 520, damping: 32 };
