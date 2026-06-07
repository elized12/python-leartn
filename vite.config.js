import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    resolve: {
        alias: {
            'monaco-editor': 'monaco-editor/esm/vs/editor/editor.api.js',
        }
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin/task/task-create.css',
                'resources/css/admin/contests.css',
                'resources/css/auth/login.css',
                'resources/css/components/courses/toolbar-block.css',
                'resources/css/contest/contest.css',
                'resources/css/courses/blocks.css',
                'resources/css/courses/course.css',
                'resources/css/courses/courses.css',
                'resources/css/courses/create.css',
                'resources/css/courses/start-course.css',
                'resources/css/layout/admin.css',
                'resources/css/layout/main.css',
                'resources/css/rating/rating.css',
                'resources/css/shared/markdown.css',
                'resources/css/task/task-not-found.css',
                'resources/css/task/task.css',
                'resources/css/user/not-found-profile.css',
                'resources/css/user/profile.css',
                'resources/js/admin/dashboard.js',
                'resources/js/admin/realtime.js',
                'resources/js/admin/contests.js',
                'resources/js/admin/task/task-create.js',
                'resources/js/components/courses/ToolbarBlock.js',
                'resources/js/courses/course.js',
                'resources/js/courses/create.js',
                'resources/js/courses/start-course.js',
                'resources/js/echo.js',
                'resources/js/notification/notification.js',
                'resources/js/task/task-editor.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    monaco: ['monaco-editor'],
                },
            },
        },
    },
    server: {
        watch: {
            ignored: [
                '**/vendor/**',
                '**/node_modules/**',
                '**/storage/**',
                '**/bootstrap/cache/**',
                '**/public/**'
            ]
        }
    }
});
