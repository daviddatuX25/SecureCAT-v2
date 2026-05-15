import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/print-template.css', 'resources/js/app.js'],
            refresh: true,
        }),
        svelte({
            // Svelte 5 supports mixed mode - our components use runes, library uses Svelte 4 syntax
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        // In Sail, use VITE_PORT (e.g. 5174) so Docker can map host:container same port; browser uses VITE_DEV_SERVER_URL
        port: process.env.LARAVEL_SAIL
            ? (parseInt(process.env.VITE_PORT, 10) || 5174)
            : (process.env.VITE_PORT ? parseInt(process.env.VITE_PORT, 10) : 5173),
        origin: process.env.LARAVEL_SAIL ? (process.env.VITE_DEV_SERVER_URL || 'http://localhost:5174') : undefined,
        // Allow app origin so the page at APP_URL can load Vite assets (fixes CORS when app is on 8080, Vite on 5174/5175)
        cors: process.env.LARAVEL_SAIL
            ? { origin: ['http://localhost:8080', 'http://localhost:5174', 'http://localhost:5175', 'http://localhost:5176'] }
            : undefined,
        strictPort: false,
        allowedHosts: ['ad9c-136-239-226-123.ngrok-free.app'],
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
