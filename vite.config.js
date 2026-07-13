import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/storefront.js',
                'resources/js/admin-analytics.js',
                'resources/js/motion/admin.js',
            ],
            refresh: true,
        }),
    ],
});
