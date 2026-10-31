import { fileURLToPath, URL } from 'node:url';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

/**
 * Editor build.
 *
 * The bundle is emitted as a single IIFE so it can be enqueued with a regular
 * <script> tag on WordPress 6.2 (script modules require 6.5). The frontend
 * never loads any of this.
 */
export default defineConfig({
  base: './',
  plugins: [react()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  define: {
    __BREEFER_VERSION__: JSON.stringify(process.env.npm_package_version ?? '0.1.0'),
  },
  build: {
    outDir: 'build',
    emptyOutDir: true,
    manifest: 'manifest.json',
    target: 'es2020',
    cssCodeSplit: false,
    sourcemap: false,
    assetsInlineLimit: 4096,
    rollupOptions: {
      input: {
        editor: fileURLToPath(new URL('./src/editor.tsx', import.meta.url)),
      },
      output: {
        format: 'iife',
        inlineDynamicImports: true,
        name: 'BreeferEditor',
        entryFileNames: 'js/[name]-[hash].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name ?? '';

          if (name.endsWith('.css')) {
            return 'css/[name]-[hash][extname]';
          }

          return 'media/[name]-[hash][extname]';
        },
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
  },
});
