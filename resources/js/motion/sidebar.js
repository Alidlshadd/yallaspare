// The admin sidebar's motion: the rail that marks the current page, the
// coordinated hover, the cursor highlight, the collapse sequence and the
// tooltips that stand in for labels once the panel is narrow.
//
// Everything here animates translate/scale/opacity only. Nothing measures the
// document during a gesture except the rail, which measures once per move and
// reads from a cached rect the rest of the time.
import { animate } from 'motion/mini';
import { hover, press, spring } from 'motion';
import { isRtl } from './config';

const RAIL_MOVE = { type: spring, stiffness: 420, damping: 34, mass: 0.7 };
const RAIL_SETTLE = { type: spring, stiffness: 520, damping: 30 };
const MICRO = { duration: 0.22, ease: [0.2, 0, 0, 1] };

/**
 * The orange rail beside the current page.
 *
 * It travels rather than jumps: on the way it stretches along its direction of
 * travel and settles back to height at the end, which is what makes the eye
 * follow it instead of hunting for it again.
 */
const initRail = (sidebar) => {
    const nav = sidebar.querySelector('.admin-nav');
    const rail = sidebar.querySelector('[data-admin-nav-rail]');

    if (!nav || !rail) {
        return;
    }

    let current = null;

    const place = (link, animated) => {
        if (!link) {
            rail.classList.remove('is-ready');
            nav.classList.remove('rail-on');

            return;
        }

        const navBox = nav.getBoundingClientRect();
        const linkBox = link.getBoundingClientRect();
        const top = linkBox.top - navBox.top + nav.scrollTop;
        const height = linkBox.height;
        // The rail is shorter than the row it marks — it points at the row
        // rather than boxing it in.
        const inset = Math.round(height * 0.18);
        const y = Math.round(top + inset);
        const h = Math.round(height - inset * 2);

        rail.style.height = `${h}px`;

        if (!animated) {
            rail.style.translate = `0 ${y}px`;
            rail.classList.add('is-ready');
            // Hands the job over from the static per-row bar.
            nav.classList.add('rail-on');

            return;
        }

        const from = current ?? y;
        const distance = Math.abs(y - from);
        // A long trip earns more stretch than a neighbouring row.
        const stretch = Math.min(1 + distance / 900, 1.35);

        animate(rail, { translate: `0 ${y}px` }, RAIL_MOVE);
        animate(rail, { scaleY: [1, stretch, 1] }, {
            ...RAIL_SETTLE,
            duration: 0.42,
        });

        current = y;
    };

    const active = () => nav.querySelector('.admin-nav-link.is-active');

    const settle = () => {
        const link = active();

        if (link) {
            const navBox = nav.getBoundingClientRect();
            const linkBox = link.getBoundingClientRect();
            current = Math.round(linkBox.top - navBox.top + nav.scrollTop + linkBox.height * 0.18);
        }

        place(link, false);
    };

    settle();

    // A full page load brings its own active row, so the rail only has to
    // travel when something moves it here: a hash change, or a click that is
    // handled before the browser leaves.
    nav.addEventListener('click', (event) => {
        const link = event.target instanceof Element
            ? event.target.closest('.admin-nav-link')
            : null;

        if (link && !link.classList.contains('is-active')) {
            place(link, true);
        }
    });

    let frame = 0;
    const reflow = () => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(settle);
    };

    window.addEventListener('resize', reflow);
    nav.addEventListener('scroll', reflow, { passive: true });

    return reflow;
};

/**
 * Hover: the row lights, then the icon leads and the label follows a beat
 * later. The gap is small enough to read as one movement rather than two.
 */
const initRows = (sidebar) => {
    const shift = isRtl() ? -2 : 2;

    hover('.admin-nav-link', (link) => {
        const icon = link.querySelector('.admin-nav-icon');
        const label = link.querySelector('.admin-nav-label');

        if (icon) {
            animate(icon, { translate: `${shift}px 0` }, MICRO);
        }

        if (label) {
            animate(label, { translate: `${shift}px 0` }, { ...MICRO, delay: 0.03 });
        }

        return () => {
            if (icon) {
                animate(icon, { translate: '0px 0' }, MICRO);
            }

            if (label) {
                animate(label, { translate: '0px 0' }, MICRO);
            }
        };
    });

    // A short press, well short of a layout shift.
    press('.admin-nav-link', (link) => {
        animate(link, { scale: 0.985 }, { duration: 0.08, ease: 'easeOut' });

        return () => {
            animate(link, { scale: 1 }, { type: spring, stiffness: 520, damping: 32 });
        };
    });
};

/**
 * The cursor highlight. Written to CSS custom properties inside one animation
 * frame, so moving the mouse never triggers a layout read.
 */
const initSpotlight = (sidebar) => {
    let x = 0;
    let y = 0;
    let queued = false;

    const paint = () => {
        queued = false;
        sidebar.style.setProperty('--sb-x', `${x}px`);
        sidebar.style.setProperty('--sb-y', `${y}px`);
    };

    sidebar.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'mouse') {
            return;
        }

        const box = sidebar.getBoundingClientRect();
        x = event.clientX - box.left;
        y = event.clientY - box.top;

        if (!queued) {
            queued = true;
            requestAnimationFrame(paint);
        }
    }, { passive: true });

    sidebar.addEventListener('pointerenter', (event) => {
        if (event.pointerType === 'mouse') {
            sidebar.classList.add('is-lit');
        }
    });

    sidebar.addEventListener('pointerleave', () => {
        sidebar.classList.remove('is-lit');
    });
};

/**
 * Tooltips for the collapsed panel, where the labels are gone.
 *
 * Placed on the document rather than inside the panel: the panel scrolls and
 * clips, and a label that gets cut in half is worse than none.
 */
const initTooltips = (shell, sidebar) => {
    let tip = null;
    let showing = null;

    const collapsed = () => shell.classList.contains('admin-sidebar-collapsed');

    const hide = () => {
        showing = null;

        if (tip) {
            tip.style.opacity = '0';
        }
    };

    const show = (link) => {
        const text = link.getAttribute('data-admin-sidebar-tooltip');

        if (!text || !collapsed()) {
            return;
        }

        if (!tip) {
            tip = document.createElement('div');
            tip.className = 'admin-sidebar-tip';
            tip.setAttribute('role', 'tooltip');
            document.body.appendChild(tip);
        }

        tip.textContent = text;
        tip.style.opacity = '0';

        const box = link.getBoundingClientRect();
        const rtl = isRtl();
        // Measured once it holds the real text, so a long label is placed
        // against its own width rather than a guess.
        const width = tip.offsetWidth;

        tip.style.top = `${Math.round(box.top + box.height / 2 - tip.offsetHeight / 2)}px`;
        tip.style.left = rtl
            ? `${Math.round(box.left - width - 10)}px`
            : `${Math.round(box.right + 10)}px`;

        showing = link;

        animate(tip, {
            opacity: [0, 1],
            translate: [rtl ? '4px 0' : '-4px 0', '0px 0'],
        }, { duration: 0.16, ease: [0.2, 0, 0, 1] });
    };

    hover('.admin-nav-link', (link) => {
        show(link);

        return () => {
            if (showing === link) {
                hide();
            }
        };
    });

    // Keyboard users get the same label, and scrolling never leaves one
    // stranded over the wrong row.
    sidebar.addEventListener('focusin', (event) => {
        const link = event.target instanceof Element
            ? event.target.closest('.admin-nav-link')
            : null;

        if (link) {
            show(link);
        }
    });

    sidebar.addEventListener('focusout', hide);
    sidebar.addEventListener('scroll', hide, { passive: true });
    window.addEventListener('resize', hide);
};

/**
 * Collapsing is a sequence, not a width change.
 *
 * Going in: the words leave first — section headings, then labels — and only
 * then does the panel narrow, so nothing is caught mid-wrap. Coming out, the
 * panel opens first and the words arrive into the space that is already there.
 */
const initCollapseSequence = (shell, sidebar, onSettled) => {
    const sections = () => Array.from(sidebar.querySelectorAll('.admin-nav-section'));
    const labels = () => Array.from(sidebar.querySelectorAll('.admin-nav-label, .admin-sidebar-brand-block'));

    let collapsed = shell.classList.contains('admin-sidebar-collapsed');

    const fade = (elements, to, delayStep, base) => {
        elements.forEach((element, index) => {
            animate(element, { opacity: to }, {
                duration: 0.16,
                ease: [0.2, 0, 0, 1],
                delay: base + index * delayStep,
            });
        });
    };

    const observer = new MutationObserver(() => {
        const next = shell.classList.contains('admin-sidebar-collapsed');

        if (next === collapsed) {
            return;
        }

        collapsed = next;

        if (collapsed) {
            fade(sections(), 0, 0.012, 0);
            fade(labels(), 0, 0.008, 0.05);
        } else {
            // Cleared so the class-driven styles take back over once the
            // panel is wide again.
            [...sections(), ...labels()].forEach((element) => {
                element.style.opacity = '';
            });

            fade(sections(), [0, 1], 0.02, 0.22);
            fade(labels(), [0, 1], 0.012, 0.18);
        }

        // The rail is measured against rows whose width is still moving.
        window.setTimeout(() => onSettled?.(), 480);
    });

    observer.observe(shell, { attributes: true, attributeFilter: ['class'] });
};

export const initSidebar = () => {
    const shell = document.querySelector('[data-admin-shell]');
    const sidebar = shell?.querySelector('[data-admin-sidebar]');

    if (!shell || !sidebar) {
        return;
    }

    const reflow = initRail(sidebar);

    initRows(sidebar);
    initSpotlight(sidebar);
    initTooltips(shell, sidebar);
    initCollapseSequence(shell, sidebar, reflow);
};
