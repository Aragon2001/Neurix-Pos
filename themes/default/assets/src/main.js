// Importar Bootstrap 5 y AdminLTE 4
import 'bootstrap'
import 'admin-lte'
import '@fortawesome/fontawesome-free/css/all.css'

// Importar CSS personalizado de Neurix para AdminLTE 4
import './neurix-adminlte4.css'

// Importar CSS del POS redesigned
import './pos-redesign.css'

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
      const parent = link.parentElement
      const treeview = parent.querySelector('.nav-treeview')

      if (treeview) {
        e.preventDefault()

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

        // Toggle actual con animación suave
        parent.classList.toggle('menu-open')
        const isOpen = parent.classList.contains('menu-open')

        if (isOpen) {
          treeview.style.display = 'block'
          // Pequeña animación
          treeview.style.opacity = '0'
          setTimeout(() => {
            treeview.style.transition = 'opacity 0.3s ease'
            treeview.style.opacity = '1'
          }, 0)
        } else {
          treeview.style.opacity = '1'
          treeview.style.transition = 'opacity 0.3s ease'
          treeview.style.opacity = '0'
          setTimeout(() => {
            treeview.style.display = 'none'
            treeview.style.transition = ''
          }, 300)
        }
      }
    })
  })

  // Mantener abierto si tiene item activo
  document.querySelectorAll('.has-treeview').forEach(parent => {
    const activeItem = parent.querySelector('.nav-treeview .nav-link.active')
    if (activeItem) {
      parent.classList.add('menu-open')
      const treeview = parent.querySelector('.nav-treeview')
      if (treeview) {
        treeview.style.display = 'block'
      }
    }
  })
}

// ═════════════════ MENU ACTIVO (Highlight current page) ═════════════════
const initActiveMenu = () => {
  const currentUrl = window.location.pathname
  const navLinks = document.querySelectorAll('.nav-sidebar .nav-link')

  navLinks.forEach(link => {
    const href = link.getAttribute('href')
    if (href && currentUrl.includes(href.replace(/^[^/]*\//, ''))) {
      link.classList.add('active')
      // Abrir padre si es submenu
      const parent = link.closest('.has-treeview')
      if (parent) {
        parent.classList.add('menu-open')
        const treeview = parent.querySelector('.nav-treeview')
        if (treeview) {
          treeview.style.display = 'block'
        }
      }
    }
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
  initActiveMenu()

  // Cerrar offcanvas sidebar al hacer click en un link (mobile)
  const sidebar = document.getElementById('mainSidebar')
  if (sidebar) {
    const offcanvasInstance = bootstrap.Offcanvas.getOrCreateInstance(sidebar)
    document.querySelectorAll('#mainSidebar .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        // Solo cerrar si no es un treeview parent
        if (!link.parentElement.classList.contains('has-treeview')) {
          offcanvasInstance.hide()
        }
      })
    })
  }

  // Exportar librerías globales para usar en scripts embebidos (legacy support)
  window.TomSelect = TomSelect
  window.Tabulator = Tabulator
  window.TempusDominus = TempusDominus
  window.Swal = Swal
  window.nxToggleTheme = (theme) => switchTheme(theme)

  // Mostrar versión en console
  console.log('%c🚀 Neurix POS v1.0', 'font-size: 16px; color: #0369a1; font-weight: bold;')
  console.log('%cAdminLTE 4 + Bootstrap 5 + Vite', 'font-size: 12px; color: #38bdf8;')
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
