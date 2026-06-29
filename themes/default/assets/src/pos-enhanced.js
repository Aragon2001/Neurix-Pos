/**
 * POS Enhanced - Mejoras de UX/UI y funcionalidad
 * Moderno, responsive y con máxima accesibilidad
 */

class POSEnhanced {
  constructor() {
    this.cart = {};
    this.selectedCategory = null;
    this.init();
  }

  init() {
    this.initEventListeners();
    this.initThemeToggle();
    this.initKeyboardShortcuts();
    this.initAutoFocus();
    this.setupAccessibility();
    console.log('✨ POS Enhanced initialized');
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // EVENT LISTENERS
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  initEventListeners() {
    // Product cards - click to add to cart
    document.addEventListener('click', (e) => {
      const productCard = e.target.closest('.product-card');
      if (productCard) {
        this.selectProduct(productCard);
        this.showAddedNotification();
      }
    });

    // Cart quantity changes
    document.addEventListener('change', (e) => {
      if (e.target.matches('.rquantity')) {
        this.updateCartTotal();
        this.animateTotals();
      }
    });

    // Delete button
    document.addEventListener('click', (e) => {
      if (e.target.matches('.posdel')) {
        this.removeFromCart(e.target);
      }
    });

    // Filter categories (debounced)
    const filterInput = document.getElementById('filter-categories');
    if (filterInput) {
      filterInput.addEventListener('input', (e) => {
        this.debounce(() => this.filterProducts(e.target.value), 300);
      });
    }

    // Search products (debounced)
    const searchInput = document.getElementById('add_item');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        this.debounce(() => this.searchProducts(e.target.value), 200);
      });
    }
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // PRODUCT SELECTION
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  selectProduct(card) {
    // Visual feedback
    card.classList.add('selected');
    setTimeout(() => card.classList.remove('selected'), 200);

    // Trigger ripple effect
    this.createRipple(card);

    // Add haptic feedback if available
    if (navigator.vibrate) {
      navigator.vibrate(10);
    }
  }

  createRipple(element) {
    const ripple = document.createElement('span');
    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.background = 'rgba(255, 255, 255, 0.6)';
    ripple.style.width = '40px';
    ripple.style.height = '40px';
    ripple.style.animation = 'ripple-animation 0.6s ease-out';
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // CART MANAGEMENT
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  updateCartTotal() {
    const cartTotals = document.querySelector('.cart-totals');
    if (cartTotals) {
      cartTotals.classList.add('updated');
      setTimeout(() => cartTotals.classList.remove('updated'), 400);
    }
  }

  removeFromCart(element) {
    const row = element.closest('tr');
    if (row) {
      row.style.animation = 'slideOutLeft 0.3s ease-out forwards';
      setTimeout(() => {
        row.remove();
        this.updateCartTotal();
      }, 300);
    }
  }

  animateTotals() {
    const totalElement = document.getElementById('total-payable');
    if (totalElement) {
      totalElement.style.animation = 'fadeInScale 0.3s ease-out';
      setTimeout(() => {
        totalElement.style.animation = '';
      }, 300);
    }
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SEARCH & FILTER
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  filterProducts(query) {
    const products = document.querySelectorAll('.product-card');
    let visibleCount = 0;

    products.forEach((product) => {
      const text = product.textContent.toLowerCase();
      const matches = text.includes(query.toLowerCase());

      if (matches) {
        product.style.display = '';
        product.style.animation = 'fadeInScale 0.2s ease-out';
        visibleCount++;
      } else {
        product.style.display = 'none';
      }
    });

    this.showSearchResult(visibleCount, query);
  }

  searchProducts(query) {
    this.filterProducts(query);
  }

  showSearchResult(count, query) {
    if (query && count === 0) {
      this.showToast('No se encontraron productos', 'warning');
    }
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // NOTIFICATIONS & FEEDBACK
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  showAddedNotification() {
    this.showToast('✓ Producto agregado', 'success', 1500);
  }

  showToast(message, type = 'info', duration = 2000) {
    const toast = document.createElement('div');
    const bgClass = {
      success: 'bg-success',
      danger: 'bg-danger',
      warning: 'bg-warning',
      info: 'bg-info'
    }[type] || 'bg-info';

    toast.innerHTML = `
      <div class="alert alert-dismissible fade show ${bgClass} mb-0" style="
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
        min-width: 250px;
        animation: slideInRight 0.3s ease-out;
      " role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    `;

    document.body.appendChild(toast);
    setTimeout(() => {
      toast.querySelector('.alert').style.animation = 'slideOutRight 0.3s ease-out forwards';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // THEME MANAGEMENT
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  initThemeToggle() {
    // Mantener sincronización con tema del sistema
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('nx-theme')) {
          this.applyTheme(e.matches ? 'dark' : 'light');
        }
      });
    }
  }

  applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.body.setAttribute('data-theme', theme);
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // KEYBOARD SHORTCUTS
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  initKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      // Alt+P: Payment
      if (e.altKey && e.key === 'p') {
        e.preventDefault();
        document.getElementById('payment')?.click();
      }

      // Alt+C: Cancel
      if (e.altKey && e.key === 'c') {
        e.preventDefault();
        document.getElementById('reset')?.click();
      }

      // Alt+H: Hold
      if (e.altKey && e.key === 'h') {
        e.preventDefault();
        document.getElementById('suspend')?.click();
      }

      // Alt+S: Search focus
      if (e.altKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('add_item')?.focus();
      }

      // Escape: Clear search
      if (e.key === 'Escape') {
        const searchInput = document.getElementById('add_item');
        if (searchInput && searchInput.value) {
          searchInput.value = '';
          this.filterProducts('');
          searchInput.focus();
        }
      }
    });
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // AUTO FOCUS
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  initAutoFocus() {
    // Focus on search input when page loads
    const searchInput = document.getElementById('add_item');
    if (searchInput) {
      searchInput.focus();
      // Show hint
      this.showToast('Alt+S para buscar, Alt+P para pagar, Alt+C para cancelar', 'info', 4000);
    }
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // ACCESSIBILITY
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  setupAccessibility() {
    // Add ARIA labels
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach((btn) => {
      if (!btn.getAttribute('aria-label')) {
        btn.setAttribute('aria-label', btn.textContent.trim());
      }
    });

    // Add role to cart
    const cart = document.getElementById('posTable');
    if (cart) {
      cart.setAttribute('role', 'grid');
      cart.setAttribute('aria-label', 'Carrito de compras');
    }

    // Add role to products
    const products = document.getElementById('item-list');
    if (products) {
      products.setAttribute('role', 'list');
      products.setAttribute('aria-label', 'Catálogo de productos');
    }

    // Make products keyboard accessible
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        const focused = document.activeElement;
        if (focused?.classList.contains('product-card')) {
          focused.click();
        }
      }
    });
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // UTILITIES
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  debounce(fn, delay) {
    clearTimeout(this._debounceTimer);
    this._debounceTimer = setTimeout(fn, delay);
  }

  formatCurrency(value) {
    return new Intl.NumberFormat('es-CR', {
      style: 'currency',
      currency: 'CRC'
    }).format(value);
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // PERFORMANCE MONITORING
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  logPerformance() {
    if (window.performance) {
      const perfData = window.performance.timing;
      const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
      console.log(`⚡ Page Load Time: ${pageLoadTime}ms`);
    }
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.posEnhanced = new POSEnhanced();
  window.posEnhanced.logPerformance();
});

// Estilos CSS para animaciones adicionales
const style = document.createElement('style');
style.textContent = `
  @keyframes slideOutLeft {
    to { transform: translateX(-100%); opacity: 0; }
  }

  @keyframes slideOutRight {
    to { transform: translateX(100%); opacity: 0; }
  }

  @keyframes ripple-animation {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }

  @keyframes fadeInScale {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
  }

  .pos-page * {
    -webkit-tap-highlight-color: transparent;
  }

  /* Reduce motion for users who prefer it */
  @media (prefers-reduced-motion: reduce) {
    * {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
    }
  }
`;
document.head.appendChild(style);

export { POSEnhanced };
