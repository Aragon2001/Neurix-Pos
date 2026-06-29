import { defineConfig } from 'vite'

export default defineConfig({
  build: {
    outDir: 'themes/default/assets/dist',
    lib: {
      entry: 'themes/default/assets/src/main.js',
      name: 'NeurixApp',
      formats: ['iife']
    },
    minify: 'terser',
    sourcemap: false,
    rollupOptions: {
      output: {
        entryFileNames: 'js/[name].min.js',
        assetFileNames: assetInfo => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name].min.css'
          }
          return '[name]'
        }
      }
    }
  }
})
