import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd());
    const host = env.VITE_HOST || 'localhost';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
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
            tailwindcss(),
        ],
        server: {
            host: host,
            hmr: {
                host: host,
            },
            cors: true,
        },
        build: {
            rollupOptions: {
                output: {
                    manualChunks: {
                        'vendor': ['vue', '@inertiajs/vue3', 'axios', 'lodash'],
                        'ui': ['sweetalert2', 'lucide-vue-next', 'driver.js'],
                        'charts': ['apexcharts', 'vue3-apexcharts'],
                    }
                }
            }
        }
    };
});
