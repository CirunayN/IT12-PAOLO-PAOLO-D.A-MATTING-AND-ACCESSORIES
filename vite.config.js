import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            buildDirectory: 'assets/build',
            refresh: true,
        }),
    ],
    build: {
        // Commit the finished assets so starting the app never needs Node or internet.
        outDir: 'public/assets/build',
        manifest: false,
        rollupOptions: {
            input: {
                styles: 'resources/css/app.css',
                app: 'resources/js/app.js',
            },
            output: {
                entryFileNames: '[name].js',
                assetFileNames: '[name][extname]',
                chunkFileNames: '[name]-[hash].js',
            },
        },
    },
});
