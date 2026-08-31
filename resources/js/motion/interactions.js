// Hover/press micro-interactions for the admin panel. Individual CSS
// transform properties (scale/translate) are animated so they compose with —
// rather than clobber — any existing Tailwind `transform` utilities.
import { animate } from 'motion/mini';
import { hover, press } from 'motion';
import { softSpring, snappySpring } from './config';

const PRESS_TARGETS = '.admin-content button, .topbar-action, .topbar-profile, [data-motion-press]';
const LIFT_TARGETS = '[data-motion-lift]';

export const initInteractions = () => {
    if (document.querySelector(PRESS_TARGETS)) {
        press(PRESS_TARGETS, (element) => {
            animate(element, { scale: 0.97 }, { duration: 0.1, ease: 'easeOut' });

            return () => {
                animate(element, { scale: 1 }, snappySpring);
            };
        });
    }

    if (document.querySelector(LIFT_TARGETS)) {
        hover(LIFT_TARGETS, (element) => {
            animate(element, { translate: '0px -3px' }, softSpring);

            return () => {
                animate(element, { translate: '0px 0px' }, softSpring);
            };
        });
    }
};
