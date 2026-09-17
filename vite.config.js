import { defineConfig } from 'vite'
import { copyFileSync, mkdirSync } from 'fs'
import { resolve } from 'path'

// Plugin para copiar assets estáticos (no bundleados) después del build
function copyStaticAssets() {
  return {
    name: 'copy-static-assets',
    closeBundle() {
      mkdirSync('themes/default/assets/dist/js', { recursive: true })
      copyFileSync(
        resolve('themes/default/assets/src/pos-core.js'),
        resolve('themes/default/assets/dist/js/pos-core.js')
      )
      // nx-reportes.js tampoco se bundlea: las vistas del Centro de
      // Inteligencia lo piden como archivo suelto, igual que pos-core.js
      copyFileSync(
        resolve('themes/default/assets/src/nx-reportes.js'),
        resolve('themes/default/assets/dist/js/nx-reportes.js')
      )
      // nx-sidebar.css se enlaza aparte en header.php (después de www.min.css
      // para poder sobreescribirlo); emptyOutDir lo borra en cada build si no
      // se vuelve a copiar desde src
      mkdirSync('themes/default/assets/dist/css', { recursive: true })
      copyFileSync(
        resolve('themes/default/assets/src/nx-sidebar.css'),
        resolve('themes/default/assets/dist/css/nx-sidebar.css')
      )
      // La hoja del comprobante imprimible no entra al bundle: la piden
      // pos/invoice y creditnotes/invoice como archivo suelto
      mkdirSync('themes/default/assets/dist/css/invoice', { recursive: true })
      copyFileSync(
        resolve('themes/default/assets/src/invoice.css'),
        resolve('themes/default/assets/dist/css/invoice/style.css')
      )
    }
  }
}

export default defineConfig({
  plugins: [copyStaticAssets()],
  build: {
    outDir: 'themes/default/assets/dist',
    emptyOutDir: true,
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
