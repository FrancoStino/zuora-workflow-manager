import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/workflow-graph.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Split JointJS libraries into a separate chunk
                    'jointjs': ['@joint/core', '@joint/layout-directed-graph'],

                    // Split vendor libraries
                    'vendor': ['axios'],

                    // Keep workflow graph components separate
                    'workflow-graph': ['./resources/js/components/workflow-graph.js'],
                }
            }
        },
        chunkSizeWarningLimit: 1000 // Increase warning limit to 1000kB
    }
});
