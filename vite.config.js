import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', '@inertiajs/vue3'],
                    'vendor-utils': ['axios', '@vueuse/core'],
                },
            },
        },
        target: 'es2020',
        minify: 'esbuild',
    },
});
