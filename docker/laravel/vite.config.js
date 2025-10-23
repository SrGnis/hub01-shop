import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/welcome.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        hmr: {
            host: '192.168.0.105',
            protocol: 'ws',
            port: 5173,
        },
        host: true,
        strictPort: true,
        port: 5173,
        cors: '192.168.0.105',
    },
});