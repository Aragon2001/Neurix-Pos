// Importar Bootstrap 5 y AdminLTE 4
import 'bootstrap'
import 'admin-lte'
import '@fortawesome/fontawesome-free/css/all.css'

// Importar librerías modernas
import TomSelect from 'tom-select'
import { Tabulator } from 'tabulator-tables'
import { TempusDominus } from '@eonasdan/tempus-dominus'
import Swal from 'sweetalert2'

// Sistema de tema oscuro/claro (AdminLTE 4 nativo)
const initTheme = () => {
  const theme = localStorage.getItem('nx-theme') || 'dark'
  document.documentElement.setAttribute('data-bs-theme', theme)
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', () => {
  initTheme()

  // Exportar librerías globales para usar en scripts embebidos
  window.TomSelect = TomSelect
  window.Tabulator = Tabulator
  window.TempusDominus = TempusDominus
  window.Swal = Swal
})

// Permitir cambio de tema
window.switchTheme = (theme) => {
  localStorage.setItem('nx-theme', theme)
  document.documentElement.setAttribute('data-bs-theme', theme)
  // Trigger custom event para que otros scripts reaccionen
  document.dispatchEvent(new CustomEvent('themeChange', { detail: theme }))
}

export { TomSelect, Tabulator, TempusDominus, Swal }
