// Importar Bootstrap 5 y AdminLTE 4
import 'bootstrap'
import 'admin-lte'
import '@fortawesome/fontawesome-free/css/all.css'

// Importar librerías modernas
import TomSelect from 'tom-select'
import { Tabulator } from 'tabulator-tables'
import { TempusDominus } from '@eonasdan/tempus-dominus'
import Swal from 'sweetalert2'

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

// ═════════════════ SIDEBAR TREEVIEW (AdminLTE 4 nativo) ═════════════════
const initTreeview = () => {
  const treeviewItems = document.querySelectorAll('.has-treeview > .nav-link')

  treeviewItems.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault()
      const parent = link.parentElement
      const treeview = parent.querySelector('.nav-treeview')

      if (treeview) {
        // Colapsar otros treeview
        document.querySelectorAll('.has-treeview').forEach(item => {
          if (item !== parent) {
            item.classList.remove('menu-open')
            const otherTreeview = item.querySelector('.nav-treeview')
            if (otherTreeview) {
              otherTreeview.style.display = 'none'
            }
          }
        })

        // Toggle actual
        parent.classList.toggle('menu-open')
        treeview.style.display = treeview.style.display === 'none' ? 'block' : 'none'
      }
    })
  })
}

// ═════════════════ MAIN - Ejecutar al cargar ═════════════════
document.addEventListener('DOMContentLoaded', () => {
  // Tema
  initTheme()

  // Bootstrap 5 componentes
  initTooltips()
  initPopovers()

  // AdminLTE 4 componentes
  initTreeview()

  // Exportar librerías globales para usar en scripts embebidos (legacy support)
  window.TomSelect = TomSelect
  window.Tabulator = Tabulator
  window.TempusDominus = TempusDominus
  window.Swal = Swal
  window.nxToggleTheme = (theme) => switchTheme(theme)
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
