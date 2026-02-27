import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
        port: process.env.VITE_PORT ? parseInt(process.env.VITE_PORT, 10) : 5173,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
