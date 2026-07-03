/**
 * Sistema de búsqueda global de Neurix POS
 * Busca en: Menú, Productos, Clientes, Ventas
 */

export class NxGlobalSearch {
  constructor() {
    this.searchInput = document.querySelector('.nx-search-input')
    this.searchContainer = document.querySelector('.nx-hdr-search')
    this.resultsList = null
    this.debounceTimer = null
    this.debounceDelay = 300

    if (!this.searchInput) return

    this.init()
  }

  init() {
    this.createResultsPanel()
    this.bindEvents()
  }

  createResultsPanel() {
    // Crear panel de resultados
    this.resultsList = document.createElement('div')
    this.resultsList.className = 'nx-search-results'
    this.resultsList.innerHTML = ''
    this.searchContainer.appendChild(this.resultsList)

    // Crear estilos dinámicamente
    if (!document.getElementById('nx-search-styles')) {
      const style = document.createElement('style')
      style.id = 'nx-search-styles'
      style.textContent = this.getStyles()
      document.head.appendChild(style)
    }
  }

  bindEvents() {
    this.searchInput.addEventListener('input', (e) => this.onInput(e))
    this.searchInput.addEventListener('keydown', (e) => this.onKeydown(e))
    document.addEventListener('click', (e) => this.onDocumentClick(e))

    // Atajo de teclado CMD+K / Ctrl+K
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        this.searchInput.focus()
        this.searchInput.select()
      }
    })
  }

  onInput(e) {
    clearTimeout(this.debounceTimer)
    const query = e.target.value.trim()

    if (query.length < 2) {
      this.resultsList.innerHTML = ''
      return
    }

    this.debounceTimer = setTimeout(() => {
      this.search(query)
    }, this.debounceDelay)
  }

  onKeydown(e) {
    if (e.key === 'Escape') {
      this.resultsList.innerHTML = ''
      this.searchInput.value = ''
    }
  }

  onDocumentClick(e) {
    // Cerrar resultados si hace click fuera
    if (!this.searchContainer.contains(e.target)) {
      this.resultsList.innerHTML = ''
    }
  }

  async search(query) {
    try {
      const response = await fetch(`${window.base_url}search/global_search?q=${encodeURIComponent(query)}`)
      const data = await response.json()

      this.renderResults(data, query)
    } catch (error) {
      console.error('Error en búsqueda global:', error)
    }
  }

  renderResults(data, query) {
    if (Object.keys(data).length === 0) {
      this.resultsList.innerHTML = '<div class="nx-search-empty">Sin resultados</div>'
      return
    }

    let html = ''

    // Categorías de resultados
    const categories = [
      { key: 'menu', label: '📋 Menú', icon: 'menu' },
      { key: 'products', label: '📦 Productos', icon: 'package' },
      { key: 'customers', label: '👥 Clientes', icon: 'users' },
      { key: 'sales', label: '🛒 Ventas', icon: 'cart' },
    ]

    for (const cat of categories) {
      if (!data[cat.key] || data[cat.key].length === 0) continue

      html += `<div class="nx-search-category">
        <div class="nx-search-category-label">${cat.label}</div>`

      for (const result of data[cat.key]) {
        html += this.renderResultItem(result)
      }

      html += '</div>'
    }

    this.resultsList.innerHTML = html

    // Bind click events
    this.resultsList.querySelectorAll('.nx-search-item').forEach((item) => {
      item.addEventListener('click', () => {
        const url = item.getAttribute('data-url')
        if (url) {
          window.location.href = url
        }
      })
    })
  }

  renderResultItem(result) {
    const icon = this.getIcon(result.icon || result.type)
    const title = this.highlightQuery(result.title, this.searchInput.value)
    const subtitle = result.subtitle ? `<div class="nx-search-subtitle">${result.subtitle}</div>` : ''

    return `
      <div class="nx-search-item" data-url="${result.url}">
        <div class="nx-search-item-icon">${icon}</div>
        <div class="nx-search-item-content">
          <div class="nx-search-title">${title}</div>
          ${subtitle}
        </div>
        <div class="nx-search-item-arrow">→</div>
      </div>
    `
  }

  highlightQuery(text, query) {
    if (!query) return text
    const regex = new RegExp(`(${query})`, 'gi')
    return text.replace(regex, '<strong>$1</strong>')
  }

  getIcon(type) {
    const icons = {
      'menu': '📋',
      'package': '📦',
      'product': '📦',
      'users': '👥',
      'customer': '👤',
      'cart': '🛒',
      'sale': '🛒',
      'chart-line': '📈',
      'shopping-cart': '🛒',
      'receipt': '📋',
      'tags': '🏷️',
      'truck': '🚚',
      'shopping-bag': '🛍️',
      'bar-chart': '📊',
      'file-text': '📄',
      'settings': '⚙️',
      'user': '👤',
    }
    return icons[type] || '📌'
  }

  getStyles() {
    return `
      .nx-hdr-search {
        position: relative;
      }

      .nx-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--nx-card-bg, #1f2937);
        border: 1px solid var(--nx-border, #374151);
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        max-height: 400px;
        overflow-y: auto;
        z-index: 1050;
        margin-top: 0.5rem;
      }

      .nx-search-empty {
        padding: 1rem;
        text-align: center;
        color: var(--nx-text3, #9ca3af);
        font-size: 0.875rem;
      }

      .nx-search-category {
        padding: 0.5rem 0;
      }

      .nx-search-category-label {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--nx-text2, #d1d5db);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--nx-border, #374151);
      }

      .nx-search-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: background-color 0.15s ease;
        border-left: 3px solid transparent;
      }

      .nx-search-item:hover {
        background-color: var(--nx-hover-bg, #374151);
        border-left-color: #3b82f6;
      }

      .nx-search-item-icon {
        font-size: 1.25rem;
        margin-right: 0.75rem;
        flex-shrink: 0;
      }

      .nx-search-item-content {
        flex: 1;
        min-width: 0;
      }

      .nx-search-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--nx-text1, #f3f4f6);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .nx-search-title strong {
        color: #3b82f6;
        font-weight: 600;
      }

      .nx-search-subtitle {
        font-size: 0.75rem;
        color: var(--nx-text3, #9ca3af);
        margin-top: 0.25rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .nx-search-item-arrow {
        margin-left: 0.5rem;
        color: var(--nx-text3, #9ca3af);
        font-size: 0.875rem;
        flex-shrink: 0;
      }

      /* Scrollbar personalizada */
      .nx-search-results::-webkit-scrollbar {
        width: 6px;
      }

      .nx-search-results::-webkit-scrollbar-track {
        background: transparent;
      }

      .nx-search-results::-webkit-scrollbar-thumb {
        background: var(--nx-border, #374151);
        border-radius: 3px;
      }

      .nx-search-results::-webkit-scrollbar-thumb:hover {
        background: var(--nx-text3, #9ca3af);
      }

      /* Tema claro */
      [data-bs-theme="light"] .nx-search-results {
        background: #ffffff;
        border-color: #e5e7eb;
      }

      [data-bs-theme="light"] .nx-search-item:hover {
        background-color: #f3f4f6;
      }

      [data-bs-theme="light"] .nx-search-category-label {
        color: #6b7280;
        border-color: #e5e7eb;
      }

      [data-bs-theme="light"] .nx-search-title {
        color: #111827;
      }

      [data-bs-theme="light"] .nx-search-subtitle {
        color: #9ca3af;
      }

      [data-bs-theme="light"] .nx-search-item-arrow {
        color: #9ca3af;
      }
    `
  }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.nxSearch = new NxGlobalSearch()
  })
} else {
  window.nxSearch = new NxGlobalSearch()
}
