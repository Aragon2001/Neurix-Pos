// Importar Bootstrap 5 y AdminLTE 4
// NOTA: los paquetes npm "bootstrap" y "admin-lte" solo exponen su JS por defecto
// (ver "main" en su package.json); el CSS hay que importarlo explícitamente o
// nunca queda incluido en el bundle final (esto es lo que rompió toda la app).
import 'bootstrap/dist/css/bootstrap.min.css'
import * as bootstrap from 'bootstrap'
import 'admin-lte/dist/css/adminlte.min.css'
import 'admin-lte'
import '@fortawesome/fontawesome-free/css/all.css'
// Shims de compatibilidad v4: la app usa nombres de iconos antiguos (fa-building-o,
// fa-clock-o, fa-money, fa-file-text-o, etc.) por todas las vistas; sin este import
// esos nombres no existen en FA7 y el icono se renderiza vacío.
import '@fortawesome/fontawesome-free/css/v4-shims.css'

// Sistema de variables CSS de Neurix (--nx-a1, --nx-txt3, --nx-card-bg, etc.)
// Debe ir ANTES de neurix-adminlte4.css para que las variables existan cuando se usen
import './neurix-theme-vars.css'
// Importar CSS personalizado de Neurix para AdminLTE 4
import './neurix-adminlte4.css'
// Estilos específicos del módulo POS (pos-container, product-card, pos-cart, etc.)
import './pos-redesign.css'
// Sistema de diseño para listados/tablas de datos (.nxt-*) — reusable en todos los módulos
import './nx-tables.css'

// Importar librerías modernas
import TomSelect from 'tom-select'
import { Tabulator } from 'tabulator-tables'
import { TempusDominus } from '@eonasdan/tempus-dominus'
import Swal from 'sweetalert2'
import qz from 'qz-tray'

// Importar mejoras del POS
import { POSEnhanced } from './pos-enhanced'

// Motor reusable de listados (window.NxTable) — diseño nx-tables
import './nx-table'

// Sistema de búsqueda global
import './nx-search'

// ═════════════════ TEMA OSCURO/CLARO (AdminLTE 4) ═════════════════
const initTheme = () => {
  const theme = localStorage.getItem('nx-theme') || 'dark'
  document.documentElement.setAttribute('data-bs-theme', theme)
  document.body.setAttribute('data-theme', theme)
  updateThemeLabel(theme)
}

const updateThemeLabel = (theme) => {
  const label = document.getElementById('nxThemeLabel')
  if (label) label.textContent = theme === 'dark' ? '🌙 Oscuro' : '☀️ Claro'
  const dmLabel = document.getElementById('nxDmLabel')
  if (dmLabel) dmLabel.textContent = theme === 'dark' ? 'Activado' : 'Desactivado'
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

// Exponer librerías globales SINCRÓNICAMENTE — deben estar disponibles antes de que
// pos-core.js (cargado al final del <body>) ejecute init(), ya que en ese momento
// DOMContentLoaded aún no ha disparado y window.TomSelect sería undefined.
window.TomSelect = TomSelect
window.Tabulator = Tabulator
window.TempusDominus = TempusDominus
window.Swal = Swal
// Exponer Bootstrap 5 para uso programático (bootstrap.Modal, etc.) — pos-core.js
// y las vistas lo consultan vía window.bootstrap; sin esto los modales fallan
window.bootstrap = bootstrap
// QZ Tray: puente local (una instalación por terminal/computadora) que permite
// listar las impresoras del sistema operativo de ESA PC e imprimir ESC/POS crudo
// sin diálogo del navegador. v1 corre sin firma de certificado (qz.io/download) —
// migrar a modo firmado más adelante si se requiere eliminar el diálogo nativo
// de "Allow always" que QZ Tray muestra una sola vez por origen.
window.qz = qz

// ═════════════════ MAIN - Ejecutar al cargar ═════════════════
// Nota: treeview y sidebar toggle los maneja AdminLTE4 JS nativo
// (data-lte-toggle="treeview" / data-lte-toggle="sidebar") — no duplicar aquí
document.addEventListener('DOMContentLoaded', () => {
  initTheme()
  initTooltips()
  initPopovers()

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

// ═════════════════ HELPERS LEGACY (emulan funciones PHP usadas en vistas inline) ═════════════════
// is_numeric: usado por pos/open_register.php; vivía en themes/default/assets/dev/js/custom.js,
// que ya no se carga desde la migración a Vite/Bootstrap5 — sin esto el botón "Aperturar Caja"
// lanzaba ReferenceError y no hacía nada.
window.is_numeric = (mixed_var) => {
  const whitespace = ' \n\r\t\f\x0b\xa0           ​  　'
  return (typeof mixed_var === 'number' || (typeof mixed_var === 'string' && whitespace.indexOf(mixed_var.slice(-1)) === -1)) &&
    mixed_var !== '' && !isNaN(mixed_var)
}

export { TomSelect, Tabulator, TempusDominus, Swal }
