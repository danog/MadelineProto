import react from "@vitejs/plugin-react";
import { fileURLToPath } from "node:url";
import { defineConfig } from "vitest/config";

const assets = fileURLToPath(new URL("../src/WebUI/templates/assets", import.meta.url));

// The bundle is inlined in the pages by the PHP templates: one self-contained script, no CSS (the stylesheet
// is assets/madeline.css, shared with the server-rendered markup), no chunks.
export default defineConfig(({ command }) => ({
    plugins: [react()],
    // `npm run dev` serves index.html, with the stylesheet of the templates
    publicDir: false,
    server: { fs: { allow: [".", assets] } },
    // Library builds keep process.env.NODE_ENV: the bundle runs in browsers, as a production build
    define: command === "build" ? { "process.env.NODE_ENV": JSON.stringify("production") } : {},
    build: {
        outDir: assets,
        emptyOutDir: false,
        target: "es2020",
        lib: {
            entry: "src/main.tsx",
            formats: ["iife"],
            name: "MadelineProtoWebUI",
            fileName: () => "app.js",
        },
    },
    test: {
        environment: "jsdom",
        include: ["src/**/*.test.tsx", "src/**/*.test.ts"],
    },
}));
