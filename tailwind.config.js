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
                navy: {
                    DEFAULT: 'rgb(var(--brand-navy-rgb) / <alpha-value>)',
                },
                surface: {
                    DEFAULT: 'var(--surface)',
                    sunk: 'var(--surface-sunk)',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
