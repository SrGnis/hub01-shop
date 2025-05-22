import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/welcome.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '%': '/resources/css',
        },
    },    server: {
        hmr: {
            host: '0.0.0.0',
            protocol: 'ws',
            port: 5173,
        },
        host: true,
        strictPort: true,
        port: 5173,
    },

});
