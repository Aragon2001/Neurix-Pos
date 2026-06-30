// Importar Bootstrap 5 y AdminLTE 4
// NOTA: los paquetes npm "bootstrap" y "admin-lte" solo exponen su JS por defecto
// (ver "main" en su package.json); el CSS hay que importarlo explícitamente o
// nunca queda incluido en el bundle final (esto es lo que rompió toda la app).
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'
import 'admin-lte/dist/css/adminlte.min.css'
import 'admin-lte'
import '@fortawesome/fontawesome-free/css/all.css'

// Sistema de variables CSS de Neurix (--nx-a1, --nx-txt3, --nx-card-bg, etc.)
// Debe ir ANTES de neurix-adminlte4.css para que las variables existan cuando se usen
import './neurix-theme-vars.css'
// Importar CSS personalizado de Neurix para AdminLTE 4
import './neurix-adminlte4.css'
// Estilos específicos del módulo POS (pos-container, product-card, pos-cart, etc.)
import './pos-redesign.css'

// Importar librerías modernas
import TomSelect from 'tom-select'
import { Tabulator } from 'tabulator-tables'
import { TempusDominus } from '@eonasdan/tempus-dominus'
import Swal from 'sweetalert2'

// Importar mejoras del POS
import { POSEnhanced } from './pos-enhanced'

// ═════════════════ TEMA OSCURO/CLARO (AdminLTE 4) ═════════════════
const initTheme = () => {
  const theme = localStorage.getItem('nx-theme') || 'dark'
  document.documentElement.setAttribute('data-bs-theme', theme)
  document.body.setAttribute('data-theme', theme)
  updateThemeLabel(theme)
}

const updateThemeLabel = (theme) => {
  const label = document.getElementById('nxThemeLabel')
  if (label) {
    label.textContent = theme === 'dark' ? '🌙 Oscuro' : '☀️ Claro'
  }
}

// ═════════════════ INICIALIZACIÓN DE COMPONENTES ═════════════════
const initTooltips = () => {
  // Inicializar tooltips de Bootstrap 5
  const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]')
  tooltipElements.forEach(el => {
    new bootstrap.Tooltip(el)
  })
}

const initPopovers = () => {
  // Inicializar popovers de Bootstrap 5
  const popoverElements = document.querySelectorAll('[data-bs-toggle="popover"]')
  popoverElements.forEach(el => {
    new bootstrap.Popover(el)
  })
}

// ═════════════════ MAIN - Ejecutar al cargar ═════════════════
// Nota: treeview y sidebar toggle los maneja AdminLTE4 JS nativo
// (data-lte-toggle="treeview" / data-lte-toggle="sidebar") — no duplicar aquí
document.addEventListener('DOMContentLoaded', () => {
  initTheme()
  initTooltips()
  initPopovers()

  // Exportar librerías globales para scripts embebidos en vistas PHP
  window.TomSelect = TomSelect
  window.Tabulator = Tabulator
  window.TempusDominus = TempusDominus
  window.Swal = Swal
  window.nxToggleTheme = (theme) => switchTheme(theme)

  console.log('%c Neurix POS v1.0', 'font-size:14px;color:#38bdf8;font-weight:bold;')
})

// ═════════════════ TEMA GLOBAL ═════════════════
window.switchTheme = (theme) => {
  const newTheme = theme || (document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark')
  localStorage.setItem('nx-theme', newTheme)
  document.documentElement.setAttribute('data-bs-theme', newTheme)
  document.body.setAttribute('data-theme', newTheme)
  updateThemeLabel(newTheme)

  // Trigger custom event para otros scripts
  document.dispatchEvent(new CustomEvent('themeChange', { detail: newTheme }))
}

// Alias para compatibilidad legacy
window.nxToggleTheme = () => switchTheme()

export { TomSelect, Tabulator, TempusDominus, Swal }
