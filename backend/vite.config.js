import { defineConfig } from 'vite';
import { resolve } from 'path';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    root: resolve(__dirname, 'web'),

    plugins: [
        tailwindcss(),
    ],

    build: {
        outDir: resolve(__dirname, 'web/dist'),
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: resolve(__dirname, 'web/resources/js/app.js'),
            },
        },
    },

    server: {
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',
        cors: true,
    },

    resolve: {
        alias: {
            '@': resolve(__dirname, 'web/resources'),
        },
    },
});
