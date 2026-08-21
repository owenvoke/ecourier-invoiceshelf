import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'node:path'

/**
 * InvoiceShelf 2.x loads a module's compiled bundle as a plain script through
 * `ModuleFacade::script()`, so this builds a UMD bundle and leaves Vue
 * external — the host exposes it as `window.Vue`, keeping the module on the
 * host's single Vue instance and shipping no framework copy.
 *
 * The `@` alias points at the host's own `resources` directory, which is where
 * this module sits when installed at `Modules/Ecourier`. That is what lets the
 * module import host stores such as `@/scripts/stores/notification`, and it
 * means the build must run from inside a host checkout.
 */
export default defineConfig({
  build: {
    outDir: './dist',
    emptyOutDir: true,
    lib: {
      entry: resolve(import.meta.dirname, 'Resources/scripts/module.js'),
      name: 'EcourierModule',
      formats: ['umd'],
      fileName: () => 'ecourier.umd.js',
    },
    rollupOptions: {
      // The host exposes both on `window`, so neither is bundled: Vue stays a
      // single instance, and pinia — pulled in transitively by the host stores
      // this module imports — resolves to the host's own store registry.
      external: ['vue', 'pinia'],
      output: {
        globals: {
          vue: 'Vue',
          pinia: 'pinia',
        },
        // The service provider registers `dist/style.css` by name, so pin it
        // rather than letting it inherit the package name.
        assetFileNames: 'style.css',
      },
    },
  },
  resolve: {
    alias: {
      '@': resolve(import.meta.dirname, '../../resources'),
      '~': resolve(import.meta.dirname, 'Resources'),
    },
  },
  plugins: [vue(), tailwindcss()],
})
