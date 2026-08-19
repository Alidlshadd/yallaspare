import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                // Bound to the CSS variables in resources/css/app.css rather
                // than repeated as hex here. Two definitions of `primary` used
                // to exist — a static one from this file and a var-based
                // utility in app.css — and they disagreed: `text-primary`
                // resolved to #070b1f, not the brand navy anyone expected.
                //
                // This tracks the *fill* role, because bg-primary (128 uses)
                // and ring-primary (71) dominate the usage and both want navy.
                // app.css keeps a narrower `.text-primary` override for the
                // text role, which has to lift off a dark ground where a fill
                // must stay dark — one variable cannot do both.
                //
                // The `<alpha-value>` placeholder needs channel triplets, which
                // is why the vars come in an -rgb form.
                primary: {
                    DEFAULT: 'rgb(var(--primary-solid-rgb) / <alpha-value>)',
                    hover: 'var(--primary-solid-hover)',
                },
                accent: {
                    DEFAULT: 'rgb(var(--accent-rgb) / <alpha-value>)',
                    hover: 'var(--brand-orange-hover)',
                    ink: 'var(--brand-orange-ink)',
                },
                // The three sanctioned navy steps. Named here so the markup can
                // stop writing bg-[#04041f] by hand — arbitrary values are
                // invisible to any selector, which is how button-shaped links
                // wearing a navy fill slipped past the display-face rule.
                navy: {
                    DEFAULT: 'rgb(var(--brand-navy-rgb) / <alpha-value>)',
                    deep: 'rgb(4 4 31 / <alpha-value>)',
                    raised: 'rgb(18 18 74 / <alpha-value>)',
                },
                // One informational hue replacing five. cyan, indigo, violet,
                // sky and blue were all in play at once across the admin,
                // carrying the same meaning in different colours — leftovers
                // from an earlier theme rather than a decision.
                info: {
                    DEFAULT: 'rgb(var(--info-rgb) / <alpha-value>)',
                },
                surface: {
                    DEFAULT: 'var(--surface)',
                    sunk: 'var(--surface-sunk)',
                },
            },
            // Nine radii collapsed onto three, at the scale rather than in
            // the markup: rewriting ~2,000 class names to say the same thing
            // is all risk and no gain, and the names people already reach for
            // keep working. A card and the image inside it used to differ by
            // 4px for no reason anyone could state.
            //
            //   sm/DEFAULT/md/lg -> 8px   inputs, chips, small controls
            //   xl/2xl/3xl/app   -> 14px  cards, buttons, panels, modals
            //   full             -> pill
            borderRadius: {
                none: '0px',
                sm: 'var(--radius-sm)',
                DEFAULT: 'var(--radius-sm)',
                md: 'var(--radius-sm)',
                lg: 'var(--radius-sm)',
                xl: 'var(--radius-md)',
                '2xl': 'var(--radius-md)',
                '3xl': 'var(--radius-md)',
                app: 'var(--radius-md)',
                full: 'var(--radius-pill)',
            },
            // Two roles, not one family. Sora earns its place on headings and
            // prices and loses it everywhere else — it widens long text and
            // has no tabular figures, which admin tables depend on.
            //
            // 'IBM Plex Sans Arabic' sits in both stacks rather than behind a
            // dir="rtl" rule: fallback is per glyph, so Latin resolves to the
            // face in front of it and Arabic script falls through to Plex on
            // its own. Sora ships no Arabic at all, so without this the ar and
            // ku pages would silently render in a system font.
            fontFamily: {
                sans: ['Inter', 'IBM Plex Sans Arabic', ...defaultTheme.fontFamily.sans],
                display: ['Sora', 'Inter', 'IBM Plex Sans Arabic', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
