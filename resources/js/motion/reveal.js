// Scroll-reveal driven by data attributes:
//   data-animate="fade-up" | "fade"   — reveal the element itself
//   data-animate-stagger[="fade"]     — reveal direct children with a stagger
//
// The hidden state is applied from JS (not CSS), so content stays fully
// visible without JavaScript and when this module is never booted (reduced
// motion). The CSS `translate` property is animated instead of `transform`
// so existing Tailwind transform utilities keep working untouched.
import { animate } from 'motion/mini';
import { inView, stagger } from 'motion';
import { revealTransition } from './config';

const OFFSET = '24px';
const VIEW_MARGIN = '0px 0px -48px 0px';

const hide = (element, variant) => {
    element.style.opacity = '0';
    if (variant !== 'fade') {
        element.style.translate = `0 ${OFFSET}`;
    }
};

const reveal = (elements, staggerDelay = 0) => {
    animate(
        elements,
        { opacity: 1, translate: '0px 0px' },
        { ...revealTransition, delay: staggerDelay > 0 ? stagger(staggerDelay) : 0 },
    );
};

export const initReveal = () => {
    document.querySelectorAll('[data-animate]').forEach((element) => {
        hide(element, element.dataset.animate);
        inView(element, () => {
            reveal(element);
        }, { margin: VIEW_MARGIN });
    });

    document.querySelectorAll('[data-animate-stagger]').forEach((group) => {
        const children = Array.from(group.children);
        if (children.length === 0) {
            return;
        }

        children.forEach((child) => hide(child, group.dataset.animateStagger));

        // Larger groups get a tighter stagger so late items don't lag behind.
        const delay = children.length > 12 ? 0.04 : 0.07;
        inView(group, () => {
            reveal(children, delay);
        }, { margin: VIEW_MARGIN });
    });
};
