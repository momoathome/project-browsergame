import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import viteCompression from 'vite-plugin-compression';
import vueDevTools from 'vite-plugin-vue-devtools';

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
        vueDevTools({
            appendTo: "app.js"
        }),
        viteCompression({ algorithm: 'brotliCompress', ext: '.br', deleteOriginFile: false }),
        viteCompression({ algorithm: 'gzip', ext: '.gz', deleteOriginFile: false })
    ],
});
