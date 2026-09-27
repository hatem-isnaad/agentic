import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    build: {
        outDir: 'resources/dist/widget',
        emptyOutDir: true,
        lib: {
            entry: resolve(__dirname, 'resources/js/widget-embed/main.ts'),
            name: 'AgenticChat',
            formats: ['iife'],
            fileName: () => 'agentic-widget.js',
        },
        rollupOptions: {
            output: {
                assetFileNames: 'agentic-widget.[ext]',
            },
        },
    },
});
