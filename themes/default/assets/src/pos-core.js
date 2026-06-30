/**
 * POS Core — Motor del punto de venta en vanilla JS
 * Reemplaza pos.js (jQuery) sin dependencias externas.
 * Se carga únicamente en pos/index.php.
 */
(function (window, document) {
  'use strict';

  /* ──────────────────────────────────────────────────────
     ESTADO GLOBAL DEL CARRITO
  ────────────────────────────────────────────────────── */
  var spositems = {};
  var count = 1,
    total = 0,
    an = 1;
  var product_tax = 0,
    product_discount = 0;
  var order_tax = 0,
    order_discount = 0;
  var grand_total = 0,
    gtotal = 0;
  var cat_id = 0;
  var p_page = 'n';
  var tcp = 0;
  var pro_limit = 20;
  var protect_delete = 0;
  var sid = 0;
  var order_data = { info: '', items: '' };
  var bill_data = { info: '', items: '' };

  /* ──────────────────────────────────────────────────────
     HELPERS: localStorage
  ────────────────────────────────────────────────────── */
  function store(key, val) {
    try { localStorage.setItem(key, val); } catch (e) {}
  }
  function get(key) {
    try { return localStorage.getItem(key); } catch (e) { return null; }
  }
  function remove(key) {
    try { localStorage.removeItem(key); } catch (e) {}
  }

  /* ──────────────────────────────────────────────────────
     HELPERS: formato numérico
  ────────────────────────────────────────────────────── */
  function formatDecimal(value, precision) {
    if (typeof precision === 'undefined') precision = 2;
    var multiplier = Math.pow(10, precision);
    return parseFloat(
      (Math.round(parseFloat(value) * multiplier) / multiplier).toFixed(precision)
    );
  }

  function formatMoney(value) {
    var S = window.Settings || {};
    var dp = parseInt(S.decimal_places) || 2;
    var ds = S.decimal_sep || '.';
    var ts = S.thousands_sep;
    if (ts === 0 || ts === '0') ts = '';
    if (typeof ts === 'undefined' || ts === null) ts = ',';
    var sym = S.currency_symbol || '₡';
    var placement = S.currency_symbol_placement || 'before';
    var num = Math.abs(parseFloat(value) || 0).toFixed(dp);
    var parts = num.split('.');
    if (ts !== '') {
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ts);
    }
    var formatted = parts.join(ds);
    var negative = parseFloat(value) < 0 ? '-' : '';
    return negative + (placement === 'before' ? sym + formatted : formatted + sym);
  }

  /* ──────────────────────────────────────────────────────
     HELPERS: lenguaje
  ────────────────────────────────────────────────────── */
  function t(key, fallback) {
    var L = window.lang || {};
    return L[key] || fallback || key;
  }

  /* ──────────────────────────────────────────────────────
     HELPERS: DOM
  ────────────────────────────────────────────────────── */
  function $(id) { return document.getElementById(id); }
  function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
  function qsa(sel, ctx) { return (ctx || document).querySelectorAll(sel); }

  /* ──────────────────────────────────────────────────────
     CARRITO: agregar ítem
  ────────────────────────────────────────────────────── */
  function add_invoice_item(item) {
    if (count === 1) spositems = {};
    if (!item) return;
    var S = window.Settings || {};
    var item_id = S.item_addition == 1 ? item.item_id : item.id;
    if (spositems[item_id]) {
      spositems[item_id].row.qty = parseFloat(spositems[item_id].row.qty) + 1;
    } else {
      // Preservar precio original para el toggle por producto
      if (!item.row._orig_price) item.row._orig_price = item.row.price;
      item.row._price_mode = 'normal';
      spositems[item_id] = item;
    }
    store('spositems', JSON.stringify(spositems));
    loadItems();
    return true;
  }

  /* ──────────────────────────────────────────────────────
     CARRITO: renderizar
  ────────────────────────────────────────────────────── */
  function loadItems() {
    if (count === 1) spositems = {};
    var saved = get('spositems');
    if (!saved) {
      // carrito vacío — limpiar totales
      var tbody = qs('#posTable tbody') || $('posTable');
      if (tbody && tbody.tagName === 'TBODY') tbody.innerHTML = '';
      updateTotalsDisplay(0, 0, 0, 0, 0);
      return;
    }

    total = 0;
    count = 1;
    an = 1;
    product_tax = 0;
    product_discount = 0;
    order_discount = 0;
    order_tax = 0;

    var tbody = qs('#posTable tbody');
    if (!tbody) {
      // Fallback: puede que sea el tbody directamente con id="posTable"
      var el = $('posTable');
      if (el && el.tagName === 'TBODY') tbody = el;
    }
    if (tbody) tbody.innerHTML = '';

    try {
      spositems = JSON.parse(saved);
    } catch (e) {
      remove('spositems');
      return;
    }

    var S = window.Settings || {};

    Object.keys(spositems).forEach(function (key) {
      var item = spositems[key];
      var item_id = S.item_addition == 1 ? item.item_id : item.id;
      spositems[item_id] = item;

      var row = item.row;
      var product_id = row.id;
      var item_type = row.type;
      var item_tax_method = parseFloat(row.tax_method);
      var item_qty = parseFloat(row.qty);
      var item_aqty = parseFloat(row.quantity);
      var ds = row.discount || '0';
      var item_code = row.code || '';
      var item_name = (row.name || '').replace(/"/g, '&#034;').replace(/'/g, '&#039;');
      var unit_price = parseFloat(row.real_unit_price);
      var net_price = unit_price;
      var item_comment = row.comment || '';
      var item_was_ordered = row.ordered || 0;

      // Descuento
      var item_discount = formatDecimal(parseFloat(ds), 4);
      if (ds.indexOf('%') !== -1) {
        var pds = ds.split('%');
        if (!isNaN(pds[0])) {
          item_discount = formatDecimal((net_price * parseFloat(pds[0])) / 100, 4);
        }
      }
      product_discount += formatDecimal(item_discount * item_qty, 4);
      net_price = formatDecimal(net_price - item_discount, 4);

      // Impuesto
      var pr_tax = parseInt(row.tax) || 0;
      var pr_tax_val = 0;
      var tax_label = '';
      if (pr_tax !== 0) {
        if (item_tax_method === 0) {
          pr_tax_val = formatDecimal((net_price * pr_tax) / (100 + pr_tax), 4);
          net_price -= pr_tax_val;
          tax_label = t('inclusive', 'Incluido');
        } else {
          pr_tax_val = formatDecimal((net_price * pr_tax) / 100, 4);
          tax_label = t('exclusive', 'Excluido');
        }
      }
      product_tax += formatDecimal(pr_tax_val * item_qty, 4);

      var row_no = Date.now() + Math.random();
      var line_total = (net_price + pr_tax_val) * item_qty;
      total += formatDecimal(line_total, 4);
      count += item_qty;
      an++;

      // Construir fila HTML
      var tr = document.createElement('tr');
      tr.id = row_no;
      tr.className = item_id;
      tr.setAttribute('data-item-id', item_id);
      tr.setAttribute('data-id', product_id);

      tr.innerHTML =
        '<td>' +
          '<input name="product_id[]" type="hidden" class="rid" value="' + product_id + '">' +
          '<input name="item_comment[]" type="hidden" class="ritem_comment" value="' + item_comment + '">' +
          '<input name="product_code[]" type="hidden" value="' + item_code + '">' +
          '<input name="product_name[]" type="hidden" value="' + row.name + '">' +
          '<input name="id_tax[]" type="hidden" value="' + (row._id_tax || 0) + '">' +
          '<button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start edit" data-item="' + item_id + '">' +
            '<span class="sname">' + item_name + ' (' + item_code + ')</span>' +
          '</button>' +
        '</td>' +
        '<td class="text-end align-middle">' +
          '<input class="realuprice" name="real_unit_price[]" type="hidden" value="' + row.real_unit_price + '">' +
          '<input class="rdiscount" name="product_discount[]" type="hidden" value="' + ds + '">' +
          '<small class="sprice">' + formatMoney(net_price + pr_tax_val) + '</small>' +
          (row.offer_price && parseFloat(row.offer_price) > 0
            ? '<button type="button" class="price-toggle-btn ' + (row._price_mode === 'offer' ? 'active' : '') + '"' +
              ' data-item="' + item_id + '" title="' + (row._price_mode === 'offer' ? 'Usando precio oferta — click para precio normal' : 'Precio oferta disponible — click para activar') + '">' +
              '<i class="fa fa-tag"></i></button>'
            : '') +
        '</td>' +
        '<td class="align-middle" style="min-width:70px">' +
          '<input name="item_was_ordered[]" type="hidden" class="riwo" value="' + item_was_ordered + '">' +
          '<input class="form-control form-control-sm text-center rquantity" name="quantity[]" type="number" min="0.01" step="any" value="' + item_qty + '" data-id="' + row_no + '" data-item="' + item_id + '" style="width:70px">' +
        '</td>' +
        '<td class="text-end align-middle">' +
          '<span class="ssubtotal">' + formatMoney(line_total) + '</span>' +
        '</td>' +
        '<td class="text-center align-middle">' +
          '<i class="fa fa-trash text-danger pointer posdel" data-id="' + row_no + '" data-item="' + item_id + '" style="cursor:pointer" title="' + t('remove', 'Eliminar') + '"></i>' +
        '</td>';

      if (tbody) tbody.prepend(tr);

      // Marcar stock bajo
      if (item_type === 'standard' && item_qty > item_aqty) {
        tr.classList.add('table-warning');
      }
    });

    // Descuento de orden
    var ds_saved = get('spos_discount') || '0';
    order_discount = parseFloat(ds_saved) || 0;
    if (ds_saved.indexOf('%') !== -1) {
      var pds2 = ds_saved.split('%');
      order_discount = parseFloat((total * parseFloat(pds2[0])) / 100) || 0;
    }

    // Impuesto de orden
    var ts_saved = get('spos_tax') || '0';
    order_tax = parseFloat(ts_saved) || 0;
    if (ts_saved.indexOf('%') !== -1) {
      var pts = ts_saved.split('%');
      order_tax = ((total - order_discount) * parseFloat(pts[0])) / 100 || 0;
    }

    grand_total = formatDecimal(total - order_discount + order_tax, 4);
    updateTotalsDisplay(total, product_discount, order_discount, order_tax, grand_total);

    // Re-focus búsqueda
    var si = $('add_item');
    if (si) si.focus();
  }

  function updateTotalsDisplay(tot, prod_ds, ord_ds, ord_tx, g_total) {
    var elCount    = $('count');
    var elCountItems = $('count-items');
    var elTotal    = $('total');
    var elDs       = $('ds_con');
    var elPayable  = $('total-payable');
    var elTotalTax = $('total_tax');
    var elTaxDisp  = $('total_tax_display');

    var itemsLabel = (an - 1) + ' líneas (' + formatDecimal(count - 1) + ' uds.)';
    if (elCount)      elCount.textContent = (an - 1);
    if (elCountItems) elCountItems.textContent = itemsLabel;
    if (elTotal)      elTotal.textContent = formatMoney(tot);
    if (elDs)         elDs.textContent = formatMoney(prod_ds + ord_ds);
    if (elTaxDisp)    elTaxDisp.textContent = formatMoney(product_tax);
    if (elTotalTax)   elTotalTax.value = product_tax;

    if (elPayable) {
      elPayable.textContent = formatMoney(g_total);
      // Animación pulso en el total
      elPayable.classList.remove('bump');
      void elPayable.offsetWidth; // reflow
      elPayable.classList.add('bump');
    }

    // Campos hidden del form
    var elAmount = $('amount_val');
    if (elAmount) elAmount.value = formatDecimal(g_total);
  }

  /* ──────────────────────────────────────────────────────
     AJAX: obtener producto por código
  ────────────────────────────────────────────────────── */
  function addProductByCode(code, btnEl) {
    fetch(window.base_url + 'pos/get_product/' + encodeURIComponent(code))
      .then(function (r) { return r.text(); })
      .then(function (text) {
        var data = null;
        try { data = JSON.parse(text); } catch (e) {}
        if (data && data.id !== undefined) {
          if (btnEl) animateProductBtn(btnEl);
          add_invoice_item(data);
          showToast(data.row ? data.row.name : code);
          var si = $('add_item');
          if (si) { si.value = ''; si.focus(); }
        } else {
          showAlert(t('no_match_found', 'Producto no encontrado'));
          var si = $('add_item');
          if (si) { si.value = ''; si.focus(); }
        }
      })
      .catch(function () {
        showAlert('Error al obtener producto');
      });
  }

  /* ──────────────────────────────────────────────────────
     BÚSQUEDA: autocomplete en #add_item
  ────────────────────────────────────────────────────── */
  function initSearch() {
    var searchInput = $('add_item');
    if (!searchInput) return;

    // Contenedor del dropdown
    var wrapper = searchInput.parentNode;
    wrapper.style.position = 'relative';

    var dropdown = document.createElement('div');
    dropdown.id = 'pos-autocomplete';
    dropdown.className = 'list-group shadow-sm';
    dropdown.style.cssText =
      'position:absolute;left:0;right:0;top:100%;z-index:9999;display:none;max-height:320px;overflow-y:auto;border:1px solid var(--bs-border-color);background:var(--bs-body-bg);';
    wrapper.appendChild(dropdown);

    var searchTimer;

    function hideDropdown() {
      dropdown.style.display = 'none';
      dropdown.innerHTML = '';
    }

    function showDropdown(items) {
      dropdown.innerHTML = '';
      var hasItems = false;
      items.forEach(function (item) {
        if (!item || item.id == 0) return;
        hasItems = true;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'list-group-item list-group-item-action py-2 px-3';
        btn.style.fontSize = '0.9rem';
        btn.textContent = item.label || item.value || '';
        btn.addEventListener('mousedown', function (e) {
          e.preventDefault();
          hideDropdown();
          searchInput.value = '';
          add_invoice_item(item);
          showToast(item.row ? item.row.name : (item.label || ''));
          searchInput.focus();
        });
        dropdown.appendChild(btn);
      });
      dropdown.style.display = hasItems ? 'block' : 'none';
    }

    function doSearch(term, autoSelect) {
      if (!term) return;
      fetch(window.base_url + 'pos/suggestions?term=' + encodeURIComponent(term))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !Array.isArray(data) || data.length === 0) {
            if (autoSelect) {
              showAlert(t('no_match_found', 'Producto no encontrado'));
              searchInput.value = '';
            }
            hideDropdown();
            return;
          }
          // Sin resultados reales: endpoint devuelve [{id:0, label:'no_match...'}]
          if (data[0].id == 0 || !data[0].item_id) {
            if (autoSelect) {
              showAlert(t('no_match_found', 'Producto no encontrado'));
              searchInput.value = '';
            }
            hideDropdown();
            return;
          }
          // Un único resultado real → agregar directo
          if (data.length === 1) {
            hideDropdown();
            searchInput.value = '';
            add_invoice_item(data[0]);
            return;
          }
          // Múltiples → mostrar dropdown de selección
          showDropdown(data);
        })
        .catch(function () {
          showAlert('Error en la búsqueda');
        });
    }

    // Input: búsqueda con delay
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      var term = this.value.trim();
      if (!term) { hideDropdown(); return; }
      searchTimer = setTimeout(function () { doSearch(term, false); }, 300);
    });

    // Enter: buscar inmediatamente
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.keyCode === 13) {
        e.preventDefault();
        clearTimeout(searchTimer);
        var term = this.value.trim();
        if (!term) return;
        // Si hay items en dropdown, seleccionar el primero
        var first = qs('button', dropdown);
        if (first && dropdown.style.display !== 'none') {
          first.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
          return;
        }
        doSearch(term, true);
      }
      if (e.key === 'ArrowDown') {
        var first = qs('button', dropdown);
        if (first) first.focus();
        e.preventDefault();
      }
      if (e.key === 'Escape') {
        hideDropdown();
        this.value = '';
      }
    });

    // Navegación con teclado en el dropdown
    dropdown.addEventListener('keydown', function (e) {
      var focused = document.activeElement;
      var items = Array.from(qsa('button', dropdown));
      var idx = items.indexOf(focused);
      if (e.key === 'ArrowDown' && idx < items.length - 1) {
        items[idx + 1].focus();
        e.preventDefault();
      } else if (e.key === 'ArrowUp') {
        if (idx > 0) items[idx - 1].focus();
        else searchInput.focus();
        e.preventDefault();
      } else if (e.key === 'Escape') {
        hideDropdown();
        searchInput.focus();
        searchInput.value = '';
      } else if (e.key === 'Enter' && focused) {
        focused.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        e.preventDefault();
      }
    });

    // Click fuera: cerrar dropdown
    document.addEventListener('click', function (e) {
      if (!wrapper.contains(e.target)) hideDropdown();
    });
  }

  /* ──────────────────────────────────────────────────────
     CLICK EN BOTÓN DE PRODUCTO (.product)
  ────────────────────────────────────────────────────── */
  function initProductClick() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.product');
      if (!btn) return;
      var code = btn.value || btn.dataset.code || btn.getAttribute('value');
      if (code) addProductByCode(code, btn);
    });
  }

  /* ──────────────────────────────────────────────────────
     CATEGORÍAS: click + paginación
  ────────────────────────────────────────────────────── */
  function initCategoryNav() {
    // Click en categoría
    document.addEventListener('click', function (e) {
      var catBtn = e.target.closest('.category');
      if (!catBtn) return;
      var newCatId = catBtn.id || catBtn.dataset.id;
      if (!newCatId || newCatId === cat_id) return;
      cat_id = newCatId;

      fetch(window.base_url + 'pos/ajaxproducts?category_id=' + cat_id + '&tcp=1')
        .then(function (r) { return r.json(); })
        .then(function (data) {
          p_page = 'n';
          tcp = data.tcp;
          var grid = $('item-list');
          if (grid) grid.innerHTML = data.products;
          qsa('.category').forEach(function (c) { c.classList.remove('active'); });
          var active = document.getElementById('category-' + cat_id) || document.getElementById(cat_id);
          if (active) active.classList.add('active');
          navPointer();
        })
        .catch(function () {});
    });

    // Botón siguiente
    var nextBtn = $('next');
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (p_page === 'n') p_page = 0;
        p_page += pro_limit;
        if (tcp >= pro_limit && p_page < tcp) {
          fetch(window.base_url + 'pos/ajaxproducts?category_id=' + cat_id + '&per_page=' + p_page)
            .then(function (r) { return r.text(); })
            .then(function (html) {
              var grid = $('item-list');
              if (grid) grid.innerHTML = html;
              navPointer();
            });
        } else {
          p_page -= pro_limit;
        }
      });
    }

    // Botón anterior
    var prevBtn = $('previous');
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        if (p_page === 'n') p_page = 0;
        if (p_page === 0) return;
        p_page -= pro_limit;
        if (p_page === 0) p_page = 'n';
        var pp = p_page === 'n' ? 0 : p_page;
        fetch(window.base_url + 'pos/ajaxproducts?category_id=' + cat_id + '&per_page=' + pp)
          .then(function (r) { return r.text(); })
          .then(function (html) {
            var grid = $('item-list');
            if (grid) grid.innerHTML = html;
            navPointer();
          });
      });
    }

    navPointer();
  }

  function navPointer() {
    var pp = p_page === 'n' ? 0 : p_page;
    var prevBtn = $('previous');
    var nextBtn = $('next');
    if (prevBtn) prevBtn.disabled = pp === 0;
    if (nextBtn) nextBtn.disabled = (pp + pro_limit) >= tcp;
  }

  /* ──────────────────────────────────────────────────────
     FILTRO de categorías (client-side)
  ────────────────────────────────────────────────────── */
  function initCategoryFilter() {
    var filterInput = $('filter-categories');
    if (!filterInput) return;
    filterInput.addEventListener('input', function () {
      var term = this.value.toLowerCase();
      qsa('.product').forEach(function (btn) {
        var text = (btn.textContent || btn.dataset.name || '').toLowerCase();
        btn.style.display = text.includes(term) ? '' : 'none';
      });
    });
  }

  /* ──────────────────────────────────────────────────────
     CARRITO: eliminar ítem
  ────────────────────────────────────────────────────── */
  function initDeleteItem() {
    document.addEventListener('click', function (e) {
      var delBtn = e.target.closest('.posdel');
      if (!delBtn) return;
      var item_id = delBtn.dataset.item || delBtn.getAttribute('data-item');
      if (!item_id) return;

      function doDelete() {
        delete spositems[item_id];
        store('spositems', JSON.stringify(spositems));
        loadItems();
      }

      if (protect_delete == 1) {
        var pin = window.prompt(t('enter_pin_code', 'Ingrese PIN'));
        if (pin === null) return;
        // Comparar con MD5 del PIN (si está disponible)
        var S = window.Settings || {};
        if (typeof md5 === 'function') {
          if (md5(pin) !== S.pin_code) {
            showAlert(t('wrong_pin', 'PIN incorrecto'));
            return;
          }
        }
        doDelete();
      } else {
        doDelete();
      }
    });
  }

  /* ──────────────────────────────────────────────────────
     CARRITO: cambio de cantidad
  ────────────────────────────────────────────────────── */
  function initQuantityChange() {
    document.addEventListener('change', function (e) {
      if (!e.target.classList.contains('rquantity')) return;
      var input = e.target;
      var item_id = input.dataset.item;
      var new_qty = parseFloat(input.value);
      if (isNaN(new_qty) || new_qty <= 0) {
        loadItems();
        showAlert(t('unexpected_value', 'Cantidad inválida'));
        return;
      }
      if (!spositems[item_id]) {
        // Intentar reconstruir desde localStorage
        var saved = get('spositems');
        if (saved) {
          try { spositems = JSON.parse(saved); } catch (e) {}
        }
      }
      if (spositems[item_id]) {
        spositems[item_id].row.qty = new_qty;
        store('spositems', JSON.stringify(spositems));
        loadItems();
      }
    });
  }

  /* ──────────────────────────────────────────────────────
     PRECIO POR PRODUCTO: toggle normal/oferta (Fase 4)
  ────────────────────────────────────────────────────── */
  function initPriceToggle() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.price-toggle-btn');
      if (!btn) return;
      var item_id = btn.dataset.item;
      if (!item_id || !spositems[item_id]) return;

      var row = spositems[item_id].row;
      var origPrice  = parseFloat(row._orig_price  || row.price);
      var offerPrice = parseFloat(row.offer_price);

      if (row._price_mode === 'offer') {
        row.real_unit_price = origPrice;
        row.price           = origPrice;
        row._price_mode     = 'normal';
      } else {
        row.real_unit_price = offerPrice;
        row.price           = offerPrice;
        row._price_mode     = 'offer';
      }

      store('spositems', JSON.stringify(spositems));
      loadItems();
    });
  }

  /* ──────────────────────────────────────────────────────
     PAGO: abrir modal
  ────────────────────────────────────────────────────── */
  function initPayment() {
    var payBtn = $('payment');
    if (!payBtn) return;
    payBtn.addEventListener('click', function () {
      if (count <= 1 || !get('spositems')) {
        showAlert(t('please_add_product', 'Agregue productos al carrito'));
        return;
      }
      gtotal = formatDecimal(total - order_discount + order_tax, 4);
      var S = window.Settings || {};
      var displayTotal = gtotal;
      if (S.rounding && parseInt(S.rounding) !== 0) {
        displayTotal = roundNumber(gtotal, parseInt(S.rounding));
      }
      var twt = $('twt');
      if (twt) twt.textContent = formatMoney(displayTotal);

      var balanceEl = $('balance');
      if (balanceEl) balanceEl.textContent = '0.00';

      // Abrir modal de pago via Bootstrap 5
      var payModal = document.getElementById('payModal');
      if (payModal && window.bootstrap) {
        var modal = window.bootstrap.Modal.getOrCreateInstance(payModal);
        modal.show();
      }
    });

    // Al mostrar el modal: focus en el monto
    var payModalEl = $('payModal');
    if (payModalEl) {
      payModalEl.addEventListener('shown.bs.modal', function () {
        var amountInput = $('amount');
        if (amountInput) { amountInput.focus(); amountInput.value = ''; }
      });

      // Cambio en el campo monto
      payModalEl.addEventListener('input', function (e) {
        if (e.target.id !== 'amount') return;
        var paying = parseFloat(e.target.value) || 0;
        var balanceEl = $('balance');
        if (balanceEl) balanceEl.textContent = formatMoney(paying - gtotal);
        var amountVal = $('amount_val');
        if (amountVal) amountVal.value = formatDecimal(paying);
      });
    }
  }

  /* ──────────────────────────────────────────────────────
     SUBMIT: enviar venta
  ────────────────────────────────────────────────────── */
  function initSubmit() {
    var submitBtn = $('submit-sale');
    if (submitBtn) {
      submitBtn.addEventListener('click', function () {
        var elCount = $('total_item');
        var elAmt = $('amount_val');
        if (elCount) elCount.value = an - 1;
        // Actualizar monto
        var amtInput = $('amount');
        if (amtInput && elAmt) elAmt.value = amtInput.value || formatDecimal(gtotal);
        // Cerrar modal y enviar form
        var payModalEl = $('payModal');
        if (payModalEl && window.bootstrap) {
          var modal = window.bootstrap.Modal.getInstance(payModalEl);
          if (modal) modal.hide();
        }
        // Pequeño delay para que el modal cierre antes del submit
        setTimeout(function () {
          var form = $('pos-sale-form');
          if (form) form.submit();
        }, 150);
      });
    }
  }

  /* ──────────────────────────────────────────────────────
     RESET: cancelar venta
  ────────────────────────────────────────────────────── */
  function initReset() {
    var resetBtn = $('reset');
    if (!resetBtn) return;
    resetBtn.addEventListener('click', function () {
      if (count <= 1) return;
      if (!window.confirm(t('r_u_sure', '¿Está seguro de cancelar la venta?'))) return;
      remove('spositems');
      remove('spos_tax');
      remove('spos_discount');
      remove('spos_customer');
      window.location.href = window.base_url + 'pos';
    });
  }

  /* ──────────────────────────────────────────────────────
     SUSPENDER VENTA
  ────────────────────────────────────────────────────── */
  function initSuspend() {
    var suspBtn = $('suspend');
    if (!suspBtn) return;
    suspBtn.addEventListener('click', function () {
      if (count <= 1) {
        showAlert(t('please_add_product', 'Agregue productos al carrito'));
        return;
      }
      var modal = document.getElementById('ModalNotes');
      if (modal && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(modal).show();
      }
    });
  }

  /* ──────────────────────────────────────────────────────
     AGREGAR CLIENTE (form AJAX)
  ────────────────────────────────────────────────────── */
  function initCustomerForm() {
    var form = $('customer-form');
    if (!form) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var alertEl = $('c-alert');
      var formData = new FormData(form);

      fetch(form.action || (window.base_url + 'customers/add'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams(formData)
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.status === 'success') {
            // Agregar al TomSelect del cliente y actualizar _customers map
            if (window._customers && res.customer) {
              window._customers[res.id] = res.customer;
            }
            var sel = document.getElementById('spos_customer');
            var hiddenCust = document.getElementById('pos-customer-hidden');
            if (sel && sel.tomselect) {
              var cust = res.customer || {};
              var optText = res.val + (cust.cf2 ? ' (' + cust.cf2 + ')' : '');
              sel.tomselect.addOption({ value: String(res.id), text: optText });
              sel.tomselect.setValue(String(res.id));
              if (hiddenCust) hiddenCust.value = String(res.id);
            } else if (sel) {
              var opt = document.createElement('option');
              opt.value = res.id;
              opt.textContent = res.val;
              opt.selected = true;
              sel.appendChild(opt);
            }
            renderCustomerCard(res.id);
            var modal = document.getElementById('customerModal');
            if (modal && window.bootstrap) {
              window.bootstrap.Modal.getInstance(modal).hide();
            }
          } else {
            if (alertEl) { alertEl.textContent = res.msg || 'Error'; alertEl.classList.remove('d-none'); }
          }
        })
        .catch(function () {
          if (alertEl) { alertEl.textContent = 'Error al agregar cliente'; alertEl.classList.remove('d-none'); }
        });
    });

    // Limpiar form al cerrar modal
    var modal = document.getElementById('customerModal');
    if (modal) {
      modal.addEventListener('hidden.bs.modal', function () {
        form.reset();
        var alertEl = $('c-alert');
        if (alertEl) alertEl.classList.add('d-none');
        var hacAlert = $('hac-alert');
        if (hacAlert) hacAlert.classList.add('d-none');
      });
    }
  }

  /* ──────────────────────────────────────────────────────
     HACIENDA AE LOOKUP en modal de cliente (Fase 6)
  ────────────────────────────────────────────────────── */
  function initHaciendaLookup() {
    var btn    = $('btn-hac-lookup');
    var cf2El  = $('cf2');
    var cf1El  = $('cf1');
    var nameEl = $('cname');
    var alertEl = $('hac-alert');
    var iconEl  = $('hac-icon');
    if (!btn || !cf2El) return;

    var debounceTimer;

    function showHacAlert(type, msg) {
      if (!alertEl) return;
      alertEl.className = 'alert alert-' + type;
      alertEl.style.fontSize = '.82rem';
      alertEl.innerHTML = msg;
    }

    function hideHacAlert() {
      if (alertEl) alertEl.className = 'alert d-none';
    }

    function setLoading(loading) {
      if (!btn || !iconEl) return;
      btn.disabled = loading;
      iconEl.className = loading ? 'fa fa-spinner fa-spin' : 'fa fa-search';
    }

    function consultarHacienda(cedula) {
      cedula = cedula.replace(/\D/g, '');
      if (cedula.length < 9 || cedula.length > 12) return;

      setLoading(true);
      hideHacAlert();

      fetch(window.base_url + 'hacienda_proxy/ae/' + encodeURIComponent(cedula), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.error) {
            showHacAlert('warning', '&#9888; ' + data.error);
            return;
          }
          if (data.nombre && nameEl && !nameEl.value) {
            nameEl.value = data.nombre;
          }
          if (data.tipoIdentificacion && cf1El) {
            cf1El.value = data.tipoIdentificacion;
          }
          var alertas = [];
          if (data.situacion) {
            if (data.situacion.moroso) alertas.push('MOROSO');
            if (data.situacion.omiso)  alertas.push('OMISO');
          }
          if (alertas.length) {
            showHacAlert('danger', '&#9888; Contribuyente: ' + alertas.join(', ') + ' — verifique antes de facturar a crédito.');
          } else if (data.nombre) {
            showHacAlert('success', '&#10003; Contribuyente encontrado. Verifique y corrija si es necesario.');
          }
        })
        .catch(function () {
          showHacAlert('warning', 'No se pudo consultar Hacienda. Registre manualmente.');
        })
        .finally(function () {
          setLoading(false);
        });
    }

    btn.addEventListener('click', function () {
      consultarHacienda(cf2El.value);
    });

    // Auto-lookup al perder el foco (solo para cédulas de 9-12 dígitos)
    cf2El.addEventListener('blur', function () {
      clearTimeout(debounceTimer);
      var v = this.value.replace(/\D/g, '');
      if (v.length >= 9 && v.length <= 12) {
        debounceTimer = setTimeout(function () { consultarHacienda(v); }, 300);
      }
    });
  }

  /* ──────────────────────────────────────────────────────
     TOM SELECT: cliente
  ────────────────────────────────────────────────────── */
  var CF1_LABELS = { '01':'Cédula', '02':'Jurídica', '03':'DIMEX', '04':'NITE', '05':'Pasaporte' };

  function getDefaultCustomerId() {
    return String((window.Settings || {}).default_customer || '');
  }

  function renderCustomerCard(id) {
    var card    = document.getElementById('pos-cust-card');
    var avatar  = document.getElementById('pos-cust-avatar');
    var nameEl  = document.getElementById('pos-cust-name');
    var metaEl  = document.getElementById('pos-cust-doc');
    var contEl  = document.getElementById('pos-cust-contact');
    var clearBtn = document.getElementById('pos-cust-clear');
    if (!card) return;

    var defaultId = getDefaultCustomerId();
    var cmap = window._customers || {};
    var c    = cmap[id];

    // Sin cliente seleccionado → estado "de contado"
    if (!c) {
      card.className = 'pcp-cust-card is-default';
      if (avatar) { avatar.textContent = 'C'; }
      if (nameEl) nameEl.textContent = 'Cliente de Contado';
      if (metaEl) metaEl.innerHTML = '';
      if (contEl) contEl.innerHTML = '';
      if (clearBtn) clearBtn.style.display = 'none';
      return;
    }

    var name     = c.name || '—';
    var initials = name.trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase() || '?';
    var isDefault = String(id) === defaultId;

    card.className = 'pcp-cust-card' + (isDefault ? ' is-default' : '');
    if (avatar) avatar.textContent = initials;
    if (nameEl) nameEl.textContent = name;

    // Badge: tipo + número documento
    var meta = '';
    if (c.cf2) {
      var label = CF1_LABELS[c.cf1] || 'Doc.';
      meta += '<span class="pcp-cust-badge"><i class="fa fa-id-card"></i>' + label + ': ' + c.cf2 + '</span>';
    }
    if (metaEl) metaEl.innerHTML = meta;

    // Contacto: email + teléfono
    var contact = '';
    if (c.email) contact += '<span class="pcp-cust-contact-item"><i class="fa fa-envelope"></i>' + c.email + '</span>';
    if (c.phone) contact += '<span class="pcp-cust-contact-item"><i class="fa fa-phone"></i>' + c.phone + '</span>';
    if (contEl) contEl.innerHTML = contact;

    // Botón X: visible solo cuando hay un cliente real (no el de contado)
    if (clearBtn) clearBtn.style.display = (isDefault ? 'none' : 'flex');
  }

  function initCustomerSelect() {
    var sel = document.getElementById('spos_customer');
    if (!sel || sel.tomselect) return;
    if (!window.TomSelect) return;

    var defaultId = getDefaultCustomerId();

    var hiddenInput = document.getElementById('pos-customer-hidden');

    function setCustomer(val) {
      store('spos_customer', val || '');
      renderCustomerCard(val || '');
      // Sincronizar el hidden input que se envía en el submit
      if (hiddenInput) hiddenInput.value = val || defaultId;
    }

    var ts = new window.TomSelect(sel, {
      maxItems: 1,
      allowEmptyOption: false,
      placeholder: 'Buscar cliente…',
      // Ítem seleccionado: solo el nombre (sin el número de cédula entre paréntesis)
      render: {
        item: function (data, escape) {
          var name = (data.text || '').replace(/\s*\(.*\)\s*$/, '').trim();
          return '<div>' + escape(name) + '</div>';
        },
        option: function (data, escape) {
          return '<div class="py-1 px-1">' + escape(data.text) + '</div>';
        }
      },
      onChange: function (val) { setCustomer(val); }
    });

    // Restaurar cliente guardado (si sigue existiendo como opción)
    var savedCustomer = get('spos_customer');
    if (savedCustomer && savedCustomer !== defaultId && ts.getOption(savedCustomer)) {
      ts.setValue(savedCustomer, true);
      renderCustomerCard(savedCustomer);
      if (hiddenInput) hiddenInput.value = savedCustomer;
    } else {
      ts.clear(true);
      renderCustomerCard('');
    }

    // Botón X → volver a "Cliente de Contado"
    var clearBtn = document.getElementById('pos-cust-clear');
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        ts.clear(true);
        setCustomer('');
      });
    }
  }

  /* ──────────────────────────────────────────────────────
     RELOJ (ahora busca .pos-clock)
  ────────────────────────────────────────────────────── */
  function initClock() {
    var el = qs('.pos-clock') || qs('.clock');
    if (!el) return;
    function tick() {
      var now = new Date();
      el.textContent = now.toLocaleTimeString('es-CR');
    }
    tick();
    setInterval(tick, 1000);
  }

  /* ──────────────────────────────────────────────────────
     TOAST: notificación visual al agregar producto
  ────────────────────────────────────────────────────── */
  function showToast(name, icon) {
    icon = icon || 'fa-check-circle';
    var wrap = $('pos-toast-wrap');
    if (!wrap) return;
    var el = document.createElement('div');
    el.className = 'pos-toast in';
    el.innerHTML = '<i class="fa ' + icon + '"></i><span><strong>Agregado:</strong> ' +
      (name || '').replace(/</g, '&lt;').substring(0, 48) + '</span>';
    wrap.appendChild(el);
    setTimeout(function () {
      el.classList.remove('in');
      el.classList.add('out');
      setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 220);
    }, 2200);
  }

  /* ──────────────────────────────────────────────────────
     ANIMACIÓN del botón de producto
  ────────────────────────────────────────────────────── */
  function animateProductBtn(btn) {
    btn.classList.add('adding');
    setTimeout(function () { btn.classList.remove('adding'); }, 400);
  }

  /* ──────────────────────────────────────────────────────
     MÉTODOS DE PAGO (cart panel + modal)
  ────────────────────────────────────────────────────── */
  function initPaymentMethods() {
    // Botones en el panel del carrito (.pcp-pay-btn)
    qsa('.pcp-pay-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        qsa('.pcp-pay-btn').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        var method = this.dataset.method || 'cash';
        var pv = $('paid_by_val');
        if (pv) pv.value = method;
        // Sincronizar con el modal
        syncPayModal(method);
      });
    });

    // Botones en el modal de pago (.pay-method-btn)
    qsa('.pay-method-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        qsa('.pay-method-btn').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        var method = this.dataset.method || 'cash';
        var pv = $('paid_by_val');
        if (pv) pv.value = method;
        // Sincronizar con el carrito
        qsa('.pcp-pay-btn').forEach(function (b) {
          b.classList.toggle('active', b.dataset.method === method);
        });
      });
    });
  }

  function syncPayModal(method) {
    qsa('.pay-method-btn').forEach(function (b) {
      b.classList.toggle('active', b.dataset.method === method);
    });
  }

  /* ──────────────────────────────────────────────────────
     MONTOS RÁPIDOS en modal de pago
  ────────────────────────────────────────────────────── */
  function initQuickAmounts() {
    var exactBtn = $('payExact');
    if (exactBtn) {
      exactBtn.addEventListener('click', function () {
        var amountInput = $('amount');
        if (amountInput) {
          amountInput.value = formatDecimal(grand_total || 0);
          amountInput.dispatchEvent(new Event('input'));
        }
      });
    }

    qsa('.pay-quick-btn[data-amount]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var amountInput = $('amount');
        if (amountInput) {
          amountInput.value = parseFloat(this.dataset.amount);
          amountInput.dispatchEvent(new Event('input'));
        }
      });
    });
  }

  /* ──────────────────────────────────────────────────────
     ATAJOS DE TECLADO
  ────────────────────────────────────────────────────── */
  function initKeyboardShortcuts() {
    document.addEventListener('keydown', function (e) {
      // No disparar si estamos escribiendo en un input que no es #add_item
      var tag = document.activeElement ? document.activeElement.tagName : '';
      var isInput = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
      var isSearch = document.activeElement && document.activeElement.id === 'add_item';

      // F2 → Producto rápido ad-hoc
      if (e.key === 'F2') {
        e.preventDefault();
        var ahBtn = document.getElementById('adHocBtn');
        if (ahBtn && window.bootstrap) {
          var m = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('adHocModal'));
          if (m) m.show();
        }
        return;
      }

      // F3 → focus en búsqueda
      if (e.key === 'F3') {
        e.preventDefault();
        var si = $('add_item');
        if (si) { si.focus(); si.select(); }
        return;
      }

      // F4 → activar pago
      if (e.key === 'F4') {
        e.preventDefault();
        var payBtn = $('payment') || $('submit-sale');
        if (payBtn) payBtn.click();
        return;
      }

      // F5 → prevenir recarga accidental (solo dentro del POS)
      if (e.key === 'F5') {
        e.preventDefault();
        return;
      }

      // ESC → si estamos en búsqueda, limpiar
      if (e.key === 'Escape' && isSearch) {
        var si = $('add_item');
        if (si) si.value = '';
        return;
      }

      // + en teclado numérico o = → focus búsqueda (acceso rápido)
      if ((e.key === '+' || e.key === '=') && !isInput) {
        e.preventDefault();
        var si = $('add_item');
        if (si) { si.focus(); si.value = ''; }
      }
    });
  }

  /* ──────────────────────────────────────────────────────
     SIDEBAR TOGGLE
  ────────────────────────────────────────────────────── */
  function initSidebarToggle() {
    var nav = $('posNav');
    var btn = $('navToggle');
    var icon = $('navToggleIcon');
    if (!nav || !btn) return;
    btn.addEventListener('click', function () {
      nav.classList.toggle('collapsed');
      if (icon) {
        icon.className = nav.classList.contains('collapsed')
          ? 'fa fa-indent'
          : 'fa fa-bars';
      }
    });
  }

  /* ──────────────────────────────────────────────────────
     ALERTA SIMPLE (sin dependencias)
  ────────────────────────────────────────────────────── */
  function showAlert(msg) {
    if (window.Swal) {
      Swal.fire({ text: msg, icon: 'warning', confirmButtonText: 'OK', timer: 3000 });
    } else {
      alert(msg);
    }
  }

  /* ──────────────────────────────────────────────────────
     REDONDEO
  ────────────────────────────────────────────────────── */
  function roundNumber(num, dec) {
    return Math.round(num * Math.pow(10, dec)) / Math.pow(10, dec);
  }

  /* ──────────────────────────────────────────────────────
     TOGGLE DE IMPRESIÓN AUTOMÁTICA (Fase 7)
  ────────────────────────────────────────────────────── */
  function initPrintToggle() {
    var btn = document.getElementById('printToggleBtn');
    if (!btn) return;

    function updateBtn() {
      var on = localStorage.getItem('pos_autoprint') === '1';
      btn.style.color = on ? 'var(--nx-ok)' : '';
      btn.title = on ? 'Impresión automática: ON (click para desactivar)' : 'Impresión automática: OFF (click para activar)';
    }

    btn.addEventListener('click', function () {
      var on = localStorage.getItem('pos_autoprint') === '1';
      localStorage.setItem('pos_autoprint', on ? '0' : '1');
      updateBtn();
      showToast(on ? 'Impresión automática desactivada' : 'Impresión automática activada',
                on ? 'fa-print' : 'fa-check-circle');
    });

    updateBtn();
  }

  /* ──────────────────────────────────────────────────────
     POPOVER DE ATAJOS DE TECLADO (Fase 9)
  ────────────────────────────────────────────────────── */
  function initKeyboardPopover() {
    var btn = document.getElementById('kbdShortcutsBtn');
    if (!btn || !window.bootstrap) return;

    var pop = new window.bootstrap.Popover(btn, {
      html: true,
      trigger: 'click',
      placement: 'bottom',
      title: 'Atajos de teclado',
      content:
        '<div style="font-size:.82rem;line-height:2;">' +
        '<div><kbd>F2</kbd>&nbsp; Producto rápido</div>' +
        '<div><kbd>F3</kbd>&nbsp; Buscar</div>' +
        '<div><kbd>F4</kbd>&nbsp; Cobrar</div>' +
        '<div><kbd>ESC</kbd>&nbsp; Cancelar búsqueda</div>' +
        '<div><kbd>↑↓</kbd>&nbsp; Navegar lista</div>' +
        '<div><kbd>Enter</kbd>&nbsp; Agregar producto</div>' +
        '<div><kbd>+</kbd>&nbsp; Foco rápido a búsqueda</div>' +
        '</div>'
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function (e) {
      if (!btn.contains(e.target)) pop.hide();
    });
  }

  /* ──────────────────────────────────────────────────────
     AUTOFOCUS: retornar foco a búsqueda después de modales
  ────────────────────────────────────────────────────── */
  function returnFocusToSearch() {
    // Pequeño delay para que Bootstrap termine de limpiar el modal
    setTimeout(function () {
      var si = $('add_item');
      if (si) si.focus();
    }, 80);
  }

  function initModalFocusReturn() {
    // Retornar foco al campo de búsqueda al cerrar cualquier modal del POS
    ['payModal', 'customerModal', 'ModalNotes'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('hidden.bs.modal', returnFocusToSearch);
    });

    // Retornar foco también cuando cualquier dropdown de Bootstrap se cierra
    document.addEventListener('hidden.bs.dropdown', function () {
      returnFocusToSearch();
    });
  }

  /* ──────────────────────────────────────────────────────
     PRODUCTO AD-HOC (Fase 11)
  ────────────────────────────────────────────────────── */
  function initAdHocProduct() {
    var modal = document.getElementById('adHocModal');
    var cabysModal = document.getElementById('cabysSearchModal');
    if (!modal) return;

    var nameEl    = document.getElementById('ah-name');
    var cabysEl   = document.getElementById('ah-cabys');
    var cabysDesc = document.getElementById('ah-cabys-desc');
    var qtyEl     = document.getElementById('ah-qty');
    var costEl    = document.getElementById('ah-cost');
    var priceEl   = document.getElementById('ah-price');
    var ivaSwitch = document.getElementById('ah-iva-switch');
    var ivaSel    = document.getElementById('ah-iva-selector');
    var ivaSelect = document.getElementById('ah-iva-select');
    var ivaRate   = document.getElementById('ah-iva-rate');
    var previewPrice = document.getElementById('ah-preview-price');
    var previewTax   = document.getElementById('ah-preview-tax');
    var previewTotal = document.getElementById('ah-preview-total');
    var confirmBtn   = document.getElementById('ah-confirm-btn');
    var cabysSearchBtn = document.getElementById('ah-cabys-search-btn');
    var cabysQ    = document.getElementById('cabys-q');
    var cabysGoBtn = document.getElementById('cabys-go-btn');
    var cabysResults = document.getElementById('cabys-results-list');

    var priceManuallyEdited = false;

    function getCurrentTasa() {
      if (!ivaSwitch || !ivaSwitch.checked) return 0;
      var opt = ivaSelect ? ivaSelect.selectedOptions[0] : null;
      return opt ? parseFloat(opt.dataset.tasa || 0) : 0;
    }

    function updatePreview() {
      var price = parseFloat(priceEl ? priceEl.value : 0) || 0;
      var qty   = parseFloat(qtyEl ? qtyEl.value : 1) || 1;
      var tasa  = getCurrentTasa();
      var taxUnit = formatDecimal(price * tasa / 100, 2);
      var total   = formatDecimal((price + taxUnit) * qty, 2);
      if (previewPrice)  previewPrice.textContent  = formatMoney(price);
      if (previewTax)    previewTax.textContent     = formatMoney(taxUnit);
      if (previewTotal)  previewTotal.textContent   = formatMoney(total);
    }

    // Auto-copiar costo al precio si no se ha editado manualmente
    if (costEl) {
      costEl.addEventListener('input', function () {
        if (!priceManuallyEdited && priceEl) {
          priceEl.value = costEl.value;
        }
        updatePreview();
      });
    }
    if (priceEl) {
      priceEl.addEventListener('input', function () {
        priceManuallyEdited = true;
        updatePreview();
      });
    }
    if (qtyEl)    qtyEl.addEventListener('input', updatePreview);

    // IVA switch
    if (ivaSwitch) {
      ivaSwitch.addEventListener('change', function () {
        if (ivaSel) ivaSel.style.display = this.checked ? '' : 'none';
        updatePreview();
      });
    }
    if (ivaSelect) {
      ivaSelect.addEventListener('change', function () {
        var opt = this.selectedOptions[0];
        if (ivaRate) ivaRate.textContent = (opt ? parseFloat(opt.dataset.tasa || 0) : 0) + '%';
        updatePreview();
      });
    }

    // Reset al abrir modal
    modal.addEventListener('show.bs.modal', function () {
      if (nameEl)    nameEl.value = '';
      if (cabysEl)   cabysEl.value = '';
      if (cabysDesc) cabysDesc.value = '';
      if (qtyEl)     qtyEl.value = 1;
      if (costEl)    costEl.value = '';
      if (priceEl)   priceEl.value = '';
      if (ivaSwitch) ivaSwitch.checked = false;
      if (ivaSel)    ivaSel.style.display = 'none';
      priceManuallyEdited = false;
      updatePreview();
    });
    modal.addEventListener('shown.bs.modal', function () {
      if (nameEl) nameEl.focus();
    });

    // Buscar CABYS - abrir sub-modal
    if (cabysSearchBtn) {
      cabysSearchBtn.addEventListener('click', function () {
        if (cabysModal && window.bootstrap) {
          var m = window.bootstrap.Modal.getOrCreateInstance(cabysModal);
          if (m) m.show();
        }
      });
    }

    // CABYS: búsqueda en el sub-modal
    function doCabysSearch() {
      var q = cabysQ ? cabysQ.value.trim() : '';
      if (!q || !cabysResults) return;
      var icon = document.getElementById('ah-cabys-icon');
      if (icon) icon.className = 'fa fa-spinner fa-spin';
      fetch(window.base_url + 'hacienda_proxy/cabys?q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (icon) icon.className = 'fa fa-search';
          cabysResults.innerHTML = '';
          var items = data.data || data.cabys || data || [];
          if (!Array.isArray(items) || !items.length) {
            cabysResults.innerHTML = '<div class="p-3 text-muted text-center">Sin resultados</div>';
            return;
          }
          items.slice(0, 50).forEach(function (it) {
            var row = document.createElement('div');
            row.className = 'p-2 border-bottom d-flex align-items-start justify-content-between gap-2';
            row.style.cssText = 'cursor:pointer;font-size:.85rem;';
            var tasa   = parseFloat(it.impuesto) || 0;
            var codigo = it.codigo || it.Codigo || '';
            var desc   = it.descripcion || it.Descripcion || '';
            row.innerHTML =
              '<div>' +
                '<span class="font-monospace text-info">' + codigo + '</span>' +
                ' &mdash; ' + desc +
              '</div>' +
              '<button type="button" class="btn btn-sm btn-outline-success flex-shrink-0" style="font-size:.75rem;">Aplicar</button>';
            row.querySelector('button').addEventListener('click', function () {
              if (cabysEl)   cabysEl.value = codigo;
              if (cabysDesc) cabysDesc.value = desc;
              // Precargar impuesto sugerido si IVA switch está activo
              if (tasa > 0 && ivaSelect) {
                var opts = ivaSelect.querySelectorAll('option');
                for (var i = 0; i < opts.length; i++) {
                  if (parseFloat(opts[i].dataset.tasa) === parseFloat(tasa)) {
                    ivaSelect.value = opts[i].value;
                    if (!ivaSwitch.checked) {
                      ivaSwitch.checked = true;
                      if (ivaSel) ivaSel.style.display = '';
                    }
                    if (ivaRate) ivaRate.textContent = tasa + '%';
                    break;
                  }
                }
              }
              updatePreview();
              if (cabysModal && window.bootstrap) {
                var m = window.bootstrap.Modal.getInstance(cabysModal);
                if (m) m.hide();
              }
            });
            cabysResults.appendChild(row);
          });
        })
        .catch(function () {
          if (icon) icon.className = 'fa fa-search';
          if (cabysResults) cabysResults.innerHTML = '<div class="p-3 text-danger text-center">Error al consultar CABYS</div>';
        });
    }

    if (cabysGoBtn) cabysGoBtn.addEventListener('click', doCabysSearch);
    if (cabysQ) {
      cabysQ.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') doCabysSearch();
      });
    }
    if (cabysModal) {
      cabysModal.addEventListener('shown.bs.modal', function () {
        if (cabysQ) { cabysQ.value = ''; cabysQ.focus(); }
        if (cabysResults) cabysResults.innerHTML = '';
      });
    }

    // Confirmar agregar al carrito
    if (confirmBtn) {
      confirmBtn.addEventListener('click', function () {
        var name  = nameEl ? nameEl.value.trim() : '';
        var cabys = cabysEl ? cabysEl.value.trim() : '';
        var qty   = parseFloat(qtyEl ? qtyEl.value : 1) || 1;
        var price = parseFloat(priceEl ? priceEl.value : 0) || 0;

        if (!name) { nameEl && nameEl.focus(); showAlert('Ingrese el nombre del producto.'); return; }
        if (!cabys || !/^\d{13}$/.test(cabys)) { cabysEl && cabysEl.focus(); showAlert('Ingrese un código CABYS válido (13 dígitos).'); return; }
        if (!price || price <= 0) { priceEl && priceEl.focus(); showAlert('Ingrese un precio válido.'); return; }

        var tasa  = getCurrentTasa();
        var idTax = 0;
        if (ivaSwitch && ivaSwitch.checked && ivaSelect) {
          idTax = parseInt(ivaSelect.value) || 0;
        }

        var uid = 'adhoc_' + Date.now();
        var item = {
          id: uid,
          item_id: uid,
          label: name + ' (' + cabys + ')',
          row: {
            id: 0,
            type: 'service',
            tax_method: 1,
            qty: qty,
            quantity: 999999,
            discount: '0',
            code: cabys,
            name: name,
            price: price,
            real_unit_price: price,
            offer_price: 0,
            comment: '',
            ordered: 0,
            tax: tasa,
            _orig_price: price,
            _price_mode: 'normal',
            _id_tax: idTax
          }
        };

        add_invoice_item(item);
        showToast(name);

        if (modal && window.bootstrap) {
          var m = window.bootstrap.Modal.getInstance(modal);
          if (m) m.hide();
        }
      });
    }
  }

  /* ──────────────────────────────────────────────────────
     INICIALIZACIÓN
  ────────────────────────────────────────────────────── */
  function init() {
    var S = window.Settings || {};
    // Leer variables del scope global (definidas en la vista PHP)
    cat_id = window._pos_cat_id || (S.default_category || 0);
    tcp = window._pos_tcp || 0;
    pro_limit = parseInt(S.pro_limit) || 20;
    protect_delete = parseInt(S.protect_delete) || 0;
    sid = window._pos_sid || 0;

    initSearch();
    initProductClick();
    initCategoryNav();
    initCategoryFilter();
    initDeleteItem();
    initPriceToggle();
    initQuantityChange();
    initPayment();
    initSubmit();
    initReset();
    initSuspend();
    initCustomerForm();
    initHaciendaLookup();
    initCustomerSelect();
    initClock();
    initPaymentMethods();
    initQuickAmounts();
    initKeyboardShortcuts();
    initSidebarToggle();
    initModalFocusReturn();
    initPrintToggle();
    initKeyboardPopover();
    initAdHocProduct();

    // Renderizar carrito al cargar
    loadItems();

    // Focus en búsqueda
    var si = $('add_item');
    if (si) si.focus();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Exponer para compatibilidad (formulario submit, etc.)
  window.add_invoice_item = add_invoice_item;
  window.loadItems = loadItems;

})(window, document);
