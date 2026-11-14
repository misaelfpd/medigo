import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

// Semua file CSS/JS per-halaman (resources/css/pages, resources/js/pages, resources/js/admin)
// otomatis jadi entry Vite, jadi tidak perlu daftar manual setiap ada halaman baru.
const filesIn = (dir, ext) => {
    const full = path.resolve(process.cwd(), dir);
    if (!fs.existsSync(full)) return [];
    return fs
        .readdirSync(full)
        .filter((file) => file.endsWith(ext))
        .map((file) => `${dir}/${file}`);
};

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/medigo-ui.css',
                'resources/js/app.js',
                'resources/js/core/toast.js',
                'resources/js/map.js',
                'resources/js/beranda.js',
                'resources/js/detail.js',
                'resources/js/chat.js',
                'resources/js/search.js',
                ...filesIn('resources/css/pages', '.css'),
                ...filesIn('resources/js/pages', '.js'),
                ...filesIn('resources/js/admin', '.js'),
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
