import { defineConfig } from "vite";
import laravel, { refreshPaths } from "laravel-vite-plugin";
import tailwindcss from "tailwindcss";
import autoprefixer from "autoprefixer";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/live-updates.js",
                "resources/js/websocket-client.js",
            ],
            refresh: [
                ...refreshPaths,
                "app/**/*.php",
                "resources/views/**/*.php",
            ],
        }),
    ],
    css: {
        postcss: {
            plugins: [tailwindcss, autoprefixer],
        },
    },
    server: {
        port: 5173,
    },
});
