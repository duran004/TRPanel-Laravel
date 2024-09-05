import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'public/css/fontawesome-free-6.6.0-web/css/all.css',
                'public/css/panel.css',
                'resources/css/figtree.css',
            ],
            refresh: true,
        }),
    ],
});
