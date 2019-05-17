import {defineConfig} from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import {resolve} from 'path'

const time = new Date().getTime()

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5176,
        hmr: {
            host: '127.0.0.1'
        }
    },
    build: {
        outDir: 'public/dist/' + time,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.scss',
                'resources/js/app.js',
                'resources/js/design.js',
                'resources/js/gsb.js',
            ],
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
    resolve: {
        alias: {
            '@': resolve('./resources/js/'),
            'spatie-media-lib-pro': '/vendor/spatie/laravel-medialibrary-pro/resources/js',
            $fonts: resolve('./public/assets/fonts/'),
        },
    },
})
