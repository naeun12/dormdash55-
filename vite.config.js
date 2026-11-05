import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue(),
    ],
    optimizeDeps: {
        include: ["lodash"], // ensure lodash is bundled during build
    },
    base: "https://dormdash55-production.up.railway.app/", // base URL for production
});
