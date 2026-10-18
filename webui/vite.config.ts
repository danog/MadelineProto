import {fileURLToPath} from 'node:url';
import solidPlugin from 'vite-plugin-solid';
import {defineConfig} from 'vitest/config';

const src = fileURLToPath(new URL('./src', import.meta.url));
const outDir = fileURLToPath(new URL('../src/WebUI/templates/assets/tweb', import.meta.url));

// tweb's aliases (vite.config.ts and tsconfig.json there), for the ported files to keep their imports
const alias = {
  '@components': src + '/components',
  '@helpers': src + '/helpers',
  '@lib': src + '/lib',
  '@environment': src + '/environment',
  '@vendor': src + '/vendor',
  '@fonts': src + '/assets/fonts',
  '@': src
};

// The bundle (src/WebUI/templates/assets/tweb/app.js and app.css) is served by MadelineProto's asset route,
// under a query string (`script.php?madelineAsset=assets/tweb/app.css`): nothing can be loaded relatively,
// so the fonts are inlined in the stylesheet, and there are no chunks. The monkeys' animations are the only
// other files (tgs/*.json, copied from tweb), fetched with the asset URL of the state.
export default defineConfig(({command, mode}) => ({
  plugins: [solidPlugin({dev: mode !== 'production' && command === 'serve'})],
  publicDir: false,
  resolve: {
    alias,
    dedupe: ['solid-js'],
    conditions: mode === 'test' ? ['browser', 'development'] : undefined
  },
  css: {
    modules: {
      // stable class names: the build is deterministic, and checked in CI
      generateScopedName: (name: string, filename: string) => {
        const file = filename.split('/').pop().replace(/\.module\.scss$/, '');
        return `${file}_${name}`;
      }
    }
  },
  server: {fs: {allow: ['.', outDir]}, host: true},
  define: command === 'build' ?
    {'process.env.NODE_ENV': JSON.stringify('production')} :
    // `npm run dev` (src/dev.tsx) serves the animations from the built assets
    {ASSETS: JSON.stringify(fileURLToPath(new URL('../src/WebUI/templates/assets', import.meta.url)))},
  build: {
    outDir,
    emptyOutDir: false,
    target: 'es2020',
    cssTarget: ['chrome87', 'edge87', 'firefox78', 'safari14.1'],
    cssCodeSplit: false,
    assetsInlineLimit: () => true,
    reportCompressedSize: false,
    lib: {
      entry: 'src/main.tsx',
      formats: ['iife'],
      name: 'MadelineProtoWebUI',
      fileName: () => 'app.js',
      cssFileName: 'app'
    }
  },
  test: {
    environment: 'jsdom',
    include: ['src/**/*.test.tsx', 'src/**/*.test.ts'],
    setupFiles: ['src/test-setup.ts'],
    server: {deps: {inline: [/solid-js/, /solid-transition-group/, /@solid-primitives/, /@solidjs/]}}
  }
}));
