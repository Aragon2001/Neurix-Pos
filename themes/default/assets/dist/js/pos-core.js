/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

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
     PUESTO DE TRABAJO
  ────────────────────────────────────────────────────── */
  /**
   * QZ Tray corre en la computadora del cajero, asi que la impresora es de la
   * maquina y no del usuario. El equipo se identifica con un device_id propio y
   * el servidor guarda que impresora le toca; asi cualquiera que entre en esta
   * caja imprime donde corresponde, sin volver a configurar nada.
   *
   * localStorage queda como copia: si el servidor no responde, la caja sigue
   * imprimiendo con lo ultimo que supo.
   */
  var CLAVE_DEVICE   = 'nx-device-id';
  var CLAVE_IMPRESORA = 'nx-qz-printer';
  var _puesto = null;
  var _puestoListo = null;

  function deviceId() {
    var id = get(CLAVE_DEVICE);
    if (id && /^[a-f0-9-]{16,64}$/i.test(id)) { return id; }
    id = (window.crypto && crypto.randomUUID)
      ? crypto.randomUUID()
      : 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
          var r = Math.random() * 16 | 0;
          return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    store(CLAVE_DEVICE, id);
    return id;
  }

  /** Impresora vigente: la que dijo el servidor, o la ultima conocida localmente. */
  function impresoraDelPuesto() {
    return (_puesto && _puesto.qz_printer) || get(CLAVE_IMPRESORA) || '';
  }

  function postPuesto(ruta, datos) {
    var body = new URLSearchParams();
    body.set('device_id', deviceId());
    Object.keys(datos || {}).forEach(function (k) {
      // PHP arma un arreglo con la clave repetida y el sufijo [].
      if (Array.isArray(datos[k])) {
        datos[k].forEach(function (v) { body.append(k + '[]', v); });
      } else {
        body.set(k, datos[k]);
      }
    });
    if (window.CSRF_NAME) { body.set(window.CSRF_NAME, window.CSRF_HASH); }
    return fetch((window.base_url || '') + 'workstation/' + ruta, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      if (!r.ok) { throw new Error(r.status); }
      return r.json();
    });
  }

  function initPuesto() {
    return postPuesto('registrar', {})
      .then(function (p) {
        _puesto = p;
        if (p.qz_printer) { store(CLAVE_IMPRESORA, p.qz_printer); }
        reportarImpresoras();
        return p;
      })
      .catch(function () { return null; });
  }

  /**
   * Manda al servidor las impresoras instaladas en esta maquina. Es el unico
   * momento en que se pueden conocer: QZ Tray solo las enumera desde el navegador
   * del equipo, y Ajustes se abre casi siempre desde otra computadora.
   */
  function reportarImpresoras(intentos) {
    intentos = intentos || 0;
    if (!window.qz || !qz.websocket.isActive()) {
      // QZ tarda en levantar el websocket; se reintenta un rato y se abandona.
      if (intentos < 10) { setTimeout(function () { reportarImpresoras(intentos + 1); }, 3000); }
      return;
    }
    qz.printers.find().then(function (lista) {
      var nombres = Array.isArray(lista) ? lista : [lista];
      nombres = nombres.filter(function (n) { return !!n; });
      if (!nombres.length) { return; }
      postPuesto('impresoras', { impresoras: nombres }).catch(function () {});
    }).catch(function () {});
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
  /* Iconos en linea: las clases fa-* no estan definidas en www.min.css, asi
     que cualquier <i class="fa ..."> se renderiza vacio. */
  var SVG_BASURERO =
    '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" ' +
    'stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/>' +
    '<path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>' +
    '<path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>';

  var SVG_ETIQUETA =
    '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" ' +
    'stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M8.5 8.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>' +
    '<path d="M4 7v3.859c0 .537 .213 1.052 .593 1.432l8.116 8.116a2.025 2.025 0 0 0 2.864 0l4.834 -4.834a2.025 2.025 0 0 0 0 -2.864l-8.117 -8.116a2.025 2.025 0 0 0 -1.431 -.593h-3.859a3 3 0 0 0 -3 3z"/></svg>';

  /**
   * Existencia disponible de una linea, o NaN si el producto no la controla
   * (servicios y productos rapidos no descuentan inventario).
   */
  function disponibleDe(row) {
    if (!row || row.type !== 'standard') { return NaN; }
    return parseFloat(row.quantity);
  }

  /** TRUE si el ajuste de sobreventa esta desactivado. */
  function sobreventaBloqueada() {
    return !parseInt((window.Settings || {}).overselling);
  }

  // Ultima linea agregada o incrementada: loadItems() la resalta y la deja a la
  // vista, porque el carrito se vuelve a dibujar entero en cada cambio.
  var ultimaLinea = null;

  function add_invoice_item(item) {
    if (count === 1) spositems = {};
    if (!item) return;
    var S = window.Settings || {};
    var item_id = S.item_addition == 1 ? item.item_id : item.id;

    // Sin sobreventa, un producto agotado no entra al carrito: dejarlo entrar
    // solo posterga el rechazo hasta el cobro, que tira la venta completa.
    var disp = disponibleDe(item.row);
    var enCarrito = spositems[item_id] ? parseFloat(spositems[item_id].row.qty) : 0;
    if (sobreventaBloqueada() && !isNaN(disp)) {
      if (disp <= 0) {
        showAlert(t('sin_inventario', 'No hay inventario de') + ' ' + (item.row.name || ''));
        return false;
      }
      if (enCarrito + 1 > disp) {
        showAlert(t('quantity_low', 'Cantidad mayor a la disponible') + ' — ' +
                  (item.row.name || '') + ' (' + disp + ')');
        return false;
      }
    }

    if (spositems[item_id]) {
      spositems[item_id].row.qty = parseFloat(spositems[item_id].row.qty) + 1;
    } else {
      // Preservar precio original para el toggle por producto
      if (!item.row._orig_price) item.row._orig_price = item.row.price;
      item.row._price_mode = 'normal';
      spositems[item_id] = item;
    }
    ultimaLinea = String(item_id);
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

      // El precio de la columna es siempre el original: el ahorro se ve en el
      // total de la linea, no escondido en el unitario.
      var unitario_visible = net_price + pr_tax_val;
      var etiqueta_desc = '';
      if (item_discount > 0) {
        unitario_visible += (item_tax_method === 0) ? item_discount : item_discount * (1 + pr_tax / 100);
        etiqueta_desc = (ds.indexOf('%') !== -1) ? ds : formatMoney(item_discount);
      }

      var row_no = Date.now() + Math.random();
      var line_total = (net_price + pr_tax_val) * item_qty;
      total += formatDecimal(line_total, 4);
      count += item_qty;
      an++;

      // Construir fila HTML
      var tr = document.createElement('tr');
      tr.id = row_no;
      tr.className = item_id;
      if (ultimaLinea !== null && String(item_id) === ultimaLinea) {
        tr.classList.add('row-new', 'linea-nueva');
      }
      tr.setAttribute('data-item-id', item_id);
      tr.setAttribute('data-id', product_id);

      tr.innerHTML =
        '<td class="pcp-c-prod">' +
          '<input name="product_id[]" type="hidden" class="rid" value="' + product_id + '">' +
          '<input name="item_comment[]" type="hidden" class="ritem_comment" value="' + item_comment + '">' +
          '<input name="product_code[]" type="hidden" value="' + item_code + '">' +
          '<input name="product_name[]" type="hidden" value="' + row.name + '">' +
          '<input name="id_tax[]" type="hidden" value="' + (row._id_tax || 0) + '">' +
          '<button type="button" class="pcp-item-btn edit" data-item="' + item_id + '"' +
                  ' title="' + t('editar_linea', 'Editar línea') + '">' +
            '<span class="pcp-item-name sname">' + item_name + '</span>' +
            '<span class="pcp-item-code">' + item_code + '</span>' +
          '</button>' +
        '</td>' +
        '<td class="pcp-c-price">' +
          '<input class="realuprice" name="real_unit_price[]" type="hidden" value="' + row.real_unit_price + '">' +
          '<input class="rdiscount" name="product_discount[]" type="hidden" value="' + ds + '">' +
          '<span class="sprice">' + formatMoney(unitario_visible) + '</span>' +
          (item_discount > 0 ? '<span class="pcp-desc-badge" title="' + t('precio_original', 'Precio sin descuento') + '">-' + etiqueta_desc + '</span>' : '') +
          (row.offer_price && parseFloat(row.offer_price) > 0
            ? '<button type="button" class="price-toggle-btn ' + (row._price_mode === 'offer' ? 'active' : '') + '"' +
              ' data-item="' + item_id + '" title="' + (row._price_mode === 'offer' ? t('usando_precio_oferta', 'Usando precio oferta — click para precio normal') : t('precio_oferta_disponible', 'Precio oferta disponible — click para activar')) + '">' + SVG_ETIQUETA + '</button>'
            : '') +
        '</td>' +
        '<td class="pcp-c-qty">' +
          '<input name="item_was_ordered[]" type="hidden" class="riwo" value="' + item_was_ordered + '">' +
          '<div class="pcp-stepper">' +
            '<button type="button" class="pcp-step menos" data-item="' + item_id + '" tabindex="-1" aria-label="-">&minus;</button>' +
            '<input class="rquantity" name="quantity[]" type="number" min="0.01" step="any" value="' + item_qty + '" data-id="' + row_no + '" data-item="' + item_id + '">' +
            '<button type="button" class="pcp-step mas" data-item="' + item_id + '" tabindex="-1" aria-label="+">+</button>' +
          '</div>' +
        '</td>' +
        '<td class="pcp-c-total">' +
          '<span class="ssubtotal' + (item_discount > 0 ? ' con-desc' : '') + '">' + formatMoney(line_total) + '</span>' +
        '</td>' +
        '<td class="pcp-c-del">' +
          '<button type="button" class="pcp-del posdel" data-id="' + row_no + '" data-item="' + item_id + '"' +
                  ' title="' + t('remove', 'Eliminar') + '" aria-label="' + t('remove', 'Eliminar') + '">' +
            SVG_BASURERO +
          '</button>' +
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

    mostrarLineaNueva();

    // Re-focus búsqueda
    var si = $('add_item');
    if (si) si.focus();
  }

  /**
   * Deja a la vista la linea recien agregada. Las filas se insertan con
   * prepend(), asi que la nueva queda arriba: basta subir el scroll del
   * carrito para que nunca se pierda bajo la lista.
   */
  function mostrarLineaNueva() {
    if (ultimaLinea === null) { return; }
    var fila = qs('#posTable .linea-nueva') || qs('#posTable tr.row-new');
    ultimaLinea = null;
    if (!fila) { return; }

    var lista = qs('.pcp-items');
    if (lista) { lista.scrollTop = 0; }

    // El resaltado se quita solo: es un aviso, no un estado del carrito.
    setTimeout(function () { fila.classList.remove('linea-nueva'); }, 1400);
  }

  function updateTotalsDisplay(tot, prod_ds, ord_ds, ord_tx, g_total) {
    var elCountItems = $('count-items');
    var elTotal    = $('total');
    var elDs       = $('ds_con');
    var elPayable  = $('total-payable');
    var elTotalTax = $('total_tax');
    var elTaxDisp  = $('total_tax_display');

    var itemsLabel = (an - 1) + ' líneas (' + formatDecimal(count - 1) + ' uds.)';
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
          agregarConCabys(data).then(function (res) {
            if (res === false) { return; }
            if (btnEl) animateProductBtn(btnEl);
            showToast(data.row ? data.row.name : code);
            var si = $('add_item');
            if (si) { si.value = ''; si.focus(); }
          });
        } else {
          showToast(t('no_match_found', 'Producto no encontrado'), 'fa-exclamation-circle');
          var si = $('add_item');
          if (si) { si.value = ''; si.focus(); }
        }
      })
      .catch(function () {
        showAlert(t('error_obtener_producto', 'Error al obtener producto'));
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
    dropdown.className = 'pos-ac';
    wrapper.appendChild(dropdown);

    var searchTimer;

    function hideDropdown() {
      dropdown.classList.remove('abierto');
      dropdown.innerHTML = '';
    }

    // La existencia llega como 30.0000: en la lista estorba mas de lo que informa.
    function cantidadCorta(n) {
      return String(Math.round((Number(n) || 0) * 1000) / 1000);
    }

    // El tono del stock es la unica lectura urgente de la lista: rojo sin
    // existencias, ambar bajo minimo, normal el resto.
    function tonoStock(cantidad, alerta) {
      if (!(cantidad > 0)) { return 'sin'; }
      if (alerta > 0 && cantidad <= alerta) { return 'bajo'; }
      return 'ok';
    }

    function filaProducto(item) {
      var fila = document.createElement('button');
      fila.type = 'button';
      fila.className = 'pos-ac-item';

      var cant = Number(item.existencias || 0);
      // Una sola linea: nombre y codigo a la izquierda, precio y existencias a
      // la derecha. La ubicacion cabe en el tooltip y no gasta alto.
      if (item.ubicacion) {
        fila.title = t('ubicacion', 'Ubicacion') + ': ' + item.ubicacion;
      }
      fila.innerHTML =
        '<span class="pos-ac-fila">' +
          '<span class="pos-ac-name">' + escaparHtml(item.nombre || item.label || '') + '</span>' +
          '<span class="pos-ac-code">' + escaparHtml(item.codigo || '') + '</span>' +
          '<span class="pos-ac-price">' + escaparHtml(item.precio_fmt || '') + '</span>' +
          '<span class="pos-ac-stock ' + tonoStock(cant, Number(item.row && item.row.alert_quantity)) + '">' +
            escaparHtml(cantidadCorta(cant)) + '</span>' +
        '</span>';
      return fila;
    }

    function showDropdown(items) {
      dropdown.innerHTML = '';
      var hasItems = false;
      items.forEach(function (item) {
        if (!item || item.id == 0) return;
        hasItems = true;
        var fila = filaProducto(item);
        fila.addEventListener('mousedown', function (e) {
          e.preventDefault();
          hideDropdown();
          searchInput.value = '';
          agregarConCabys(item).then(function (res) {
            if (res !== false) { showToast(item.row ? item.row.name : (item.label || '')); }
          });
          searchInput.focus();
        });
        dropdown.appendChild(fila);
      });
      dropdown.classList.toggle('abierto', hasItems);
    }

    function doSearch(term, autoSelect) {
      if (!term) return;
      fetch(window.base_url + 'pos/suggestions?term=' + encodeURIComponent(term))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var vacio = !data || !Array.isArray(data) || data.length === 0 ||
                      data[0].id == 0 || !data[0].item_id;
          if (vacio) {
            // Escribiendo, un modal por tecla es insoportable: el aviso vive
            // dentro de la lista y solo suena al confirmar con Enter.
            sinResultados();
            if (autoSelect) {
              showToast(t('no_match_found', 'Producto no encontrado'), 'fa-exclamation-circle');
              searchInput.value = '';
              hideDropdown();
            }
            return;
          }
          // Un único resultado real → agregar directo
          if (data.length === 1) {
            hideDropdown();
            searchInput.value = '';
            agregarConCabys(data[0]);
            return;
          }
          // Múltiples → mostrar dropdown de selección
          showDropdown(data);
        })
        .catch(function () {
          showAlert(t('error_busqueda', 'Error en la búsqueda'));
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
        var first = qs('.pos-ac-item', dropdown);
        if (first && dropdown.classList.contains('abierto')) {
          first.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
          return;
        }
        doSearch(term, true);
      }
      if (e.key === 'ArrowDown') {
        var first = qs('.pos-ac-item', dropdown);
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
      var items = Array.from(qsa('.pos-ac-item', dropdown));
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

    initCapturaEscaner(searchInput);
  }

  /**
   * El lector de barras teclea igual que una persona, pero el cajero rara vez
   * deja el cursor en la busqueda. Cualquier caracter escrito fuera de un campo
   * se redirige aca, asi el codigo entra sin tener que hacer clic primero.
   */
  function initCapturaEscaner(searchInput) {
    document.addEventListener('keydown', function (e) {
      if (e.altKey || e.ctrlKey || e.metaKey) { return; }
      if (e.key.length !== 1) { return; }

      var act = document.activeElement;
      if (act === searchInput) { return; }
      if (act && (act.isContentEditable ||
                  act.tagName === 'INPUT' || act.tagName === 'TEXTAREA' || act.tagName === 'SELECT')) {
        return;
      }
      // Con un modal abierto el foco es de esa pantalla, no del carrito.
      if (document.querySelector('.modal.show') || document.querySelector('.nx-ov.abierto')) { return; }

      e.preventDefault();
      searchInput.focus();
      searchInput.value += e.key;
      searchInput.dispatchEvent(new Event('input', { bubbles: true }));
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
     CARRITO: editar línea
  ────────────────────────────────────────────────────── */
  /**
   * La fila del carrito ya dibujaba un boton `.edit`, pero no habia ningun
   * listener: ninguna linea se podia editar, y la de producto rapido (que no
   * existe en catalogo) no tenia otra forma de corregirse.
   */
  function initEditItem() {
    var overlay = document.getElementById('editItemModal');
    if (!overlay) { return; }

    var fId    = $('ei-item');
    var fName  = $('ei-nombre');
    var fQty   = $('ei-cantidad');
    var fPrice = $('ei-precio');
    var fCom   = $('ei-comentario');
    var fDesc  = $('ei-descuento');
    var fTipo  = $('ei-desc-tipo');
    var fDescAyuda = $('ei-desc-ayuda');

    /** Monto por unidad o porcentaje: el cajero elige, no escribe el simbolo. */
    function tipoDescuento(tipo) {
      if (!fTipo) { return 'monto'; }
      if (tipo) {
        qsa('button', fTipo).forEach(function (b) {
          b.classList.toggle('activo', b.dataset.tipo === tipo);
        });
        if (fDescAyuda) {
          fDescAyuda.textContent = tipo === 'pct'
            ? t('descuento_tipo_pct', 'Porcentaje sobre el precio')
            : t('descuento_tipo_monto', 'Monto por unidad');
        }
      }
      var activo = qs('button.activo', fTipo);
      return activo ? activo.dataset.tipo : 'monto';
    }

    if (fTipo) {
      fTipo.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) { return; }
        tipoDescuento(b.dataset.tipo);
        if (fDesc) { fDesc.focus(); }
      });
    }
    var fErr   = $('ei-error');
    var fStock = $('ei-stock');

    function error(msg) {
      if (!fErr) { return; }
      fErr.textContent = msg;
      fErr.classList.add('visible');
    }
    function limpiarError() {
      if (fErr) { fErr.classList.remove('visible'); fErr.textContent = ''; }
    }
    function abrir()  { overlay.classList.add('abierto'); }
    function cerrar() { overlay.classList.remove('abierto'); limpiarError(); }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.edit');
      if (!btn) { return; }
      e.preventDefault();

      var item_id = btn.dataset.item || btn.getAttribute('data-item');
      var item = item_id ? spositems[item_id] : null;
      if (!item) { return; }

      limpiarError();
      if (fId)    { fId.value = item_id; }
      if (fName)  { fName.value = item.row.name || ''; }
      if (fQty)   { fQty.value = item.row.qty; }
      if (fPrice) { fPrice.value = item.row.real_unit_price; }
      if (fCom)   { fCom.value = item.row.comment || ''; }
      if (fDesc) {
        var guardado = String(item.row.discount || '0');
        var esPct = guardado.indexOf('%') !== -1;
        fDesc.value = (parseFloat(guardado) > 0) ? parseFloat(guardado) : '';
        tipoDescuento(esPct ? 'pct' : 'monto');
      }
      if (fStock) {
        var disp = parseFloat(item.row.quantity);
        fStock.textContent = (item.row.type === 'standard' && !isNaN(disp))
          ? t('available', 'Disponible') + ': ' + disp
          : '';
      }
      abrir();
      if (fQty) { fQty.focus(); fQty.select(); }
    });

    if ($('ei-cerrar'))   { $('ei-cerrar').addEventListener('click', cerrar); }
    if ($('ei-cancelar')) { $('ei-cancelar').addEventListener('click', cerrar); }
    overlay.addEventListener('click', function (e) { if (e.target === overlay) { cerrar(); } });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('abierto')) { cerrar(); }
    });

    var btnGuardar = $('ei-guardar');
    if (btnGuardar) {
      btnGuardar.addEventListener('click', function () {
        var item = spositems[fId ? fId.value : ''];
        if (!item) { return; }

        var nombre = (fName ? fName.value : '').trim();
        var cant   = parseFloat(fQty ? fQty.value : 0);
        var precio = parseFloat(fPrice ? fPrice.value : 0);

        if (!nombre)                     { error(t('unexpected_value', 'Escriba un nombre.')); return; }
        if (!(cant > 0))                 { error(t('unexpected_value', 'La cantidad debe ser mayor a cero.')); return; }
        if (isNaN(precio) || precio < 0) { error(t('unexpected_value', 'Precio inválido.')); return; }

        // Con sobreventa desactivada el servidor rechaza la venta entera al
        // cobrar; conviene avisarlo aqui y no al final.
        var S = window.Settings || {};
        var disp = parseFloat(item.row.quantity);
        if (!parseInt(S.overselling) && item.row.type === 'standard' && !isNaN(disp) && cant > disp) {
          error(t('quantity_low', 'Cantidad mayor a la disponible') + ' (' + disp + ')');
          return;
        }

        // product_discount[] en Pos.php espera "150" o "10%": el sufijo lo pone
        // el selector, el cajero solo teclea el numero.
        var descNum = parseFloat((fDesc ? fDesc.value : '').toString().replace(',', '.'));
        if (isNaN(descNum) || descNum < 0) { descNum = 0; }
        var esPct = tipoDescuento() === 'pct';
        if (esPct ? descNum > 100 : descNum > precio) {
          error(t('unexpected_value', 'El descuento supera el precio.'));
          return;
        }
        var desc = descNum > 0 ? (esPct ? descNum + '%' : String(descNum)) : '0';

        item.row.name = nombre;
        item.row.qty = cant;
        item.row.discount = desc;
        item.row.comment = (fCom ? fCom.value : '').trim();
        item.row.real_unit_price = precio;
        item.row.price = precio;
        item.row._orig_price = precio;
        item.row._price_mode = 'normal';

        store('spositems', JSON.stringify(spositems));
        loadItems();
        cerrar();
      });
    }

    var btnQuitar = $('ei-quitar');
    if (btnQuitar) {
      btnQuitar.addEventListener('click', function () {
        var id = fId ? fId.value : '';
        if (!id || !spositems[id]) { return; }
        delete spositems[id];
        store('spositems', JSON.stringify(spositems));
        loadItems();
        cerrar();
      });
    }
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
  /** Botones - / + del contador de cada linea. */
  function initStepper() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.pcp-step');
      if (!btn) { return; }
      var item_id = btn.dataset.item;
      var item = item_id ? spositems[item_id] : null;
      if (!item) { return; }

      var paso = btn.classList.contains('mas') ? 1 : -1;
      var nueva = parseFloat(item.row.qty) + paso;
      if (nueva <= 0) {
        delete spositems[item_id];
        store('spositems', JSON.stringify(spositems));
        loadItems();
        return;
      }

      var disp = disponibleDe(item.row);
      if (paso > 0 && sobreventaBloqueada() && !isNaN(disp) && nueva > disp) {
        showAlert(t('quantity_low', 'Cantidad mayor a la disponible') + ' (' + disp + ')');
        return;
      }

      item.row.qty = nueva;
      store('spositems', JSON.stringify(spositems));
      loadItems();
    });
  }

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
        // Con sobreventa desactivada el servidor rechaza la venta completa al
        // cobrar; se avisa aqui para no perder el carrito al final.
        var S = window.Settings || {};
        var fila = spositems[item_id].row;
        var disp = parseFloat(fila.quantity);
        if (!parseInt(S.overselling) && fila.type === 'standard' && !isNaN(disp) && new_qty > disp) {
          loadItems();
          showAlert(t('quantity_low', 'Cantidad mayor a la disponible') + ' (' + disp + ')');
          return;
        }

        fila.qty = new_qty;
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
      // La cabecera del modal repite el total: con el teclado y el desglose de
      // por medio, el cajero deja de verlo si solo esta al pie.
      var cab = $('payHeadTotal');
      if (cab) cab.textContent = formatMoney(displayTotal);

      var balanceEl = $('balance');
      if (balanceEl) balanceEl.textContent = '0.00';

      // Abrir modal de pago via Bootstrap 5
      var payModal = document.getElementById('payModal');
      if (payModal && window.bootstrap) {
        var modal = window.bootstrap.Modal.getOrCreateInstance(payModal);
        modal.show();
      }
    });

    // ── Comprobante y condición de venta ──────────────────────────────────
    // Qué comprobante admite el cliente lo decide el servidor
    // (pos/estado_cliente); acá solo se pinta y se bloquea lo que no puede ser.
    // Pos.php vuelve a comprobarlo al cobrar, que es la comprobación que cuenta.
    var estadoCliente = null;

    function docBotones() {
      return Array.prototype.slice.call(document.querySelectorAll('[data-doc]'));
    }

    function marcarDoc(tipo) {
      var elegido = null;
      docBotones().forEach(function (b) {
        var activo = b.dataset.doc === tipo && !b.disabled;
        b.classList.toggle('active', activo);
        if (activo) { elegido = b.dataset.doc; }
      });
      var campo = $('tipo_doc_val');
      if (campo) { campo.value = elegido || ''; }

      var motivo = $('docMotivo');
      if (motivo) {
        var btn = docBotones().filter(function (b) { return b.dataset.doc === '01'; })[0];
        motivo.textContent = (btn && btn.disabled) ? (btn.dataset.motivo || '') : '';
      }
    }

    function pintarEstadoCliente(d) {
      estadoCliente = d;
      estadoClientePago = d;
      if (!d) { return; }

      docBotones().forEach(function (b) {
        var permiso = (d.comprobantes || {})[b.dataset.doc] || { ok: true, motivo: '' };
        b.disabled = !permiso.ok;
        b.dataset.motivo = permiso.motivo || '';
        b.title = permiso.motivo || '';
      });

      // Precarga: lo que el cliente tenga puesto; si no, factura cuando se
      // puede y tiquete cuando no.
      var porDefecto = (d.defectos && d.defectos.tipo_doc) || '';
      var permitido = function (t) { return ((d.comprobantes || {})[t] || {}).ok; };
      if (!porDefecto || !permitido(porDefecto)) {
        porDefecto = permitido('01') ? '01' : '04';
      }
      marcarDoc(porDefecto);

      var btnCredito = $('pmCredito');
      if (btnCredito) {
        var c = d.credito || {};
        btnCredito.disabled = !c.permitido;
        // El motivo va en el tooltip: la pantalla no necesita el renglon.
        btnCredito.title = c.permitido ? '' : (c.motivo || '');

        // Si el cliente venia con credito elegido y ya no lo admite, se vuelve
        // a efectivo antes de que el cajero lo note al cobrar.
        if (!c.permitido && metodoActual() === 'credito') { seleccionarMetodo('cash'); }
      }
    }

    function cargarEstadoCliente() {
      var hidden = $('pos-customer-hidden');
      var id = hidden ? hidden.value : '';
      if (!id || !window._urlEstadoCliente) { return; }

      fetch(window._urlEstadoCliente + '/' + encodeURIComponent(id), {
        credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) { if (d && !d.error) { pintarEstadoCliente(d); } })
        .catch(function () { /* sin respuesta: el servidor decide igual al cobrar */ });
    }
    window.posCargarEstadoCliente = cargarEstadoCliente;

    var chkConting = $('docContingencia');
    if (chkConting) {
      chkConting.addEventListener('change', function () {
        var campo = $('situacion_val');
        if (campo) { campo.value = chkConting.checked ? '2' : '1'; }
      });
    }

    document.addEventListener('click', function (e) {
      var doc = e.target.closest('[data-doc]');
      if (doc && !doc.disabled) { marcarDoc(doc.dataset.doc); }
    });

    // Al mostrar el modal: focus en el monto
    var payModalEl = $('payModal');
    if (payModalEl) {
      payModalEl.addEventListener('shown.bs.modal', function () {
        payLines = [];
        cargarEstadoCliente();
        var amountInput = $('amount');
        if (amountInput) { amountInput.focus(); amountInput.value = ''; }
        var ref = $('payRef');
        if (ref) ref.value = '';
        var pv = $('paid_by_val');
        onPayMethodChange(pv ? pv.value : 'cash');
        renderPayLines();
      });

      // Al cerrar el modal (sin completar la venta): dejar de sondear SINPE.
      // No se limpia #sinpe_reference acá — si el cajero reabre el modal con
      // el mismo comprobante ya elegido, lo mantiene.
      payModalEl.addEventListener('hidden.bs.modal', function () {
        sinpeStopPolling();
      });

      // Cualquier cambio en el monto o en la referencia recalcula el resumen.
      payModalEl.addEventListener('input', function (e) {
        if (e.target.id !== 'amount' && e.target.id !== 'payRef') { return; }
        // Una referencia con espacios no cuadra con la del banco ni con la del
        // datafono, y el usuario no ve la diferencia.
        if (e.target.id === 'payRef') {
          var limpio = e.target.value.replace(/\s+/g, '');
          if (limpio !== e.target.value) { e.target.value = limpio; }
        }
        recalcPago();
      });

      // El datafono todavia no esta conectado: el boton queda listo para
      // engancharlo sin tocar el resto del modal.
      var datafonoBtn = $('payDatafonoBtn');
      if (datafonoBtn) {
        datafonoBtn.addEventListener('click', function () {
          showToast(t('enviar_cobro_pendiente', 'El datafono aun no esta conectado'), 'fa-credit-card');
        });
      }
    }
  }

  /* ──────────────────────────────────────────────────────
     PAGO DIVIDIDO — hasta 4 formas de pago por venta (tope de Pos.php)
  ────────────────────────────────────────────────────── */
  var payLines = [];
  var MAX_PAGOS = 4;

  // refRequerida: métodos que exigen número de transacción.
  var METODOS = {
    cash:     { etiqueta: 'Efectivo',       refRequerida: false },
    card:     { etiqueta: 'Tarjeta',        refRequerida: true  },
    sinpe:    { etiqueta: 'SINPE',          refRequerida: true  },
    transfer: { etiqueta: 'Transferencia',  refRequerida: true  },
    // El credito no es dinero que entra: cubre el resto de la venta y no viaja
    // como pago al backend, que deduce la condicion de venta de lo que falta.
    credito:  { etiqueta: 'Credito',        refRequerida: false, esCredito: true }
  };

  function esCredito(metodo) { return !!(METODOS[metodo] || {}).esCredito; }

  /** Ultimo estado de credito que devolvio pos/estado_cliente. */
  var estadoClientePago = null;

  function metodoActual() {
    var pv = $('paid_by_val');
    return (pv && pv.value) || 'cash';
  }

  function totalLineas() {
    return payLines.reduce(function (a, l) { return a + (parseFloat(l.monto) || 0); }, 0);
  }

  /** Monto tecleado que aún no se agregó al desglose. */
  function montoEnCurso() {
    var el = $('amount');
    return el ? (parseFloat(el.value) || 0) : 0;
  }

  /**
   * Recalcula pagado / falta / vuelto y sincroniza los campos ocultos.
   * Llamar directamente: los eventos sintéticos no burbujean hasta el modal.
   */
  function recalcPago() {
    var total  = parseFloat(gtotal) || 0;
    var pagado = totalLineas() + montoEnCurso();
    var dif    = pagado - total;

    var elPagado = $('payPagado');
    if (elPagado) elPagado.textContent = formatMoney(pagado);

    var elBal   = $('balance');
    var elLabel = $('balanceLabel');
    if (elBal) {
      // El recuadro muestra vuelto o faltante segun el signo.
      elBal.textContent = formatMoney(Math.abs(dif));
      elBal.style.color = dif < -0.005 ? 'var(--nx-err, #e74c3c)' : '';
    }
    if (elLabel) {
      elLabel.textContent = dif < -0.005 ? (window.LANG_FALTA || 'Falta') : (window.LANG_VUELTO || 'Vuelto');
    }

    var hidden = $('amount_val');
    if (hidden) hidden.value = formatDecimal(pagado);
    var bal = $('balance_amount_val');
    if (bal) bal.value = formatDecimal(dif > 0 ? dif : 0);

    return { total: total, pagado: pagado, dif: dif };
  }

  function renderPayLines() {
    var wrap = $('payLinesWrap');
    var cont = $('payLines');
    var cnt  = $('payLinesCount');
    if (!wrap || !cont) return;

    if (!payLines.length) {
      wrap.style.display = 'none';
      cont.innerHTML = '';
      if (cnt) cnt.textContent = '';
      recalcPago();
      return;
    }

    wrap.style.display = '';
    if (cnt) cnt.textContent = '(' + payLines.length + '/' + MAX_PAGOS + ')';

    cont.innerHTML = payLines.map(function (l, i) {
      var m = METODOS[l.metodo] || { etiqueta: l.metodo };
      return '<div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;' +
        'padding:.4rem .6rem;font-size:12.5px;border-bottom:1px solid rgba(255,255,255,.06);">' +
        '<div style="min-width:0;">' +
          '<strong>' + esc(m.etiqueta) + '</strong>' +
          (l.referencia ? '<div class="text-muted" style="font-size:11px;word-break:break-all;">' +
            esc(l.referencia) + '</div>' : '') +
        '</div>' +
        '<div style="white-space:nowrap;">' +
          '<strong>' + formatMoney(parseFloat(l.monto) || 0) + '</strong>' +
          ' <button type="button" class="btn btn-link btn-sm p-0 ms-2 js-quitar-pago" data-i="' + i +
          '" title="Quitar">&times;</button>' +
        '</div>' +
      '</div>';
    }).join('');

    recalcPago();
  }

  /** Agrega el monto tecleado como una forma de pago del desglose. */
  function agregarLineaPago() {
    if (payLines.length >= MAX_PAGOS) {
      alert(window.LANG_MAX_PAGOS || 'Solo se pueden registrar hasta 4 formas de pago por venta.');
      return false;
    }

    var metodo = metodoActual();
    var monto  = montoEnCurso();
    var refEl  = $('payRef');
    var ref    = refEl ? refEl.value.trim() : '';

    if (monto <= 0) {
      alert(window.LANG_MONTO_REQ || 'Ingrese un monto mayor a cero.');
      return false;
    }
    if ((METODOS[metodo] || {}).refRequerida && !ref) {
      alert(window.LANG_REF_REQ || 'Ingrese el numero de transaccion o referencia.');
      if (refEl) refEl.focus();
      return false;
    }

    payLines.push({ metodo: metodo, monto: monto, referencia: ref });

    // Preparar el campo para la siguiente forma de pago.
    var amountInput = $('amount');
    if (amountInput) { amountInput.value = ''; amountInput.focus(); }
    if (refEl) refEl.value = '';
    if (metodo === 'sinpe') sinpeLimpiarSeleccion();

    renderPayLines();
    return true;
  }

  /**
   * Vuelca el desglose a los campos ocultos del formulario.
   * Pos.php espera amount/paid_by/sinpe_reference para el primer pago y
   * amount2..4 / paid_by2..4 / freferencia1..3 para el resto (freferencia1
   * corresponde al pago 2).
   */
  function volcarPagosAlFormulario() {
    // El credito solo marca que la venta queda debiendo: el backend lo deduce
    // de la diferencia, asi que esas lineas no ocupan un amount/paid_by.
    var lineas = payLines.filter(function (l) { return !esCredito(l.metodo); });

    // El monto tecleado sin agregar cuenta como una forma de pago más.
    if (!esCredito(metodoActual()) && montoEnCurso() > 0 && lineas.length < MAX_PAGOS) {
      var refEl = $('payRef');
      lineas.push({
        metodo: metodoActual(),
        monto: montoEnCurso(),
        referencia: refEl ? refEl.value.trim() : ''
      });
    }
    // Sin monto ni desglose se asume pago exacto con el método seleccionado,
    // salvo a credito: ahi no se recibe nada y la venta queda debiendo entera.
    if (!lineas.length && !esCredito(metodoActual())) {
      lineas.push({ metodo: metodoActual(), monto: parseFloat(gtotal) || 0, referencia: '' });
    }

    var set = function (id, val) { var el = $(id); if (el) el.value = val; };

    // Una venta enteramente a credito no recibe nada: los cuatro pagos van
    // vacios y el backend la deja en 'due'.
    var primera = lineas[0] || { metodo: 'cash', monto: 0, referencia: '' };
    set('paid_by_val', primera.metodo);
    set('amount_val', formatDecimal(primera.monto));
    set('sinpe_reference', primera.referencia || '');

    var mapa = [
      null,
      { monto: 'amount2_val', metodo: 'paid_by2_val', ref: 'freferencia1_val' },
      { monto: 'amount3_val', metodo: 'paid_by3_val', ref: 'freferencia2_val' },
      { monto: 'amount4_val', metodo: 'paid_by4_val', ref: 'freferencia3_val' }
    ];
    for (var i = 1; i < MAX_PAGOS; i++) {
      var m = mapa[i];
      var l = lineas[i];
      set(m.monto, l ? formatDecimal(l.monto) : '');
      set(m.metodo, l ? l.metodo : '');
      set(m.ref, l ? (l.referencia || '') : '');
    }

    // amount_val es el primer pago, no el total: el backend suma los cuatro.
    var pagado = lineas.reduce(function (a, l) { return a + l.monto; }, 0);
    var dif = pagado - (parseFloat(gtotal) || 0);
    set('balance_amount_val', formatDecimal(dif > 0 ? dif : 0));

    return { lineas: lineas, pagado: pagado };
  }

  /**
   * Teclado en pantalla del cobro. Escribe sobre #amount y dispara el mismo
   * recalculo que el tecleo manual.
   */
  function initTecladoCobro() {
    var pad = $('payKeypad');
    var campo = $('amount');
    if (!pad || !campo) { return; }

    pad.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-key]');
      if (!b || b.disabled) { return; }
      var k = b.dataset.key;
      var v = campo.value;

      if (k === 'clear') {
        v = '';
      } else if (k === 'back') {
        v = v.slice(0, -1);
      } else if (k === '.') {
        // Un solo separador decimal, y nunca como primer caracter.
        v = v.indexOf('.') === -1 ? (v === '' ? '0.' : v + '.') : v;
      } else {
        v = v + k;
      }

      campo.value = v;
      // El listener de #amount esta delegado en el modal: un evento sintetico
      // sin bubbles no llegaria.
      campo.dispatchEvent(new Event('input', { bubbles: true }));
      campo.focus();
    });
  }

  function initPagoDividido() {
    var addBtn = $('payAddLine');
    if (addBtn) addBtn.addEventListener('click', agregarLineaPago);

    var cont = $('payLines');
    if (cont) {
      cont.addEventListener('click', function (e) {
        var b = e.target.closest('.js-quitar-pago');
        if (!b) return;
        payLines.splice(parseInt(b.dataset.i, 10), 1);
        renderPayLines();
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
        if (elCount) elCount.value = an - 1;

        // Arma amount/paid_by + amount2..4 desde el desglose.
        var res = volcarPagosAlFormulario();

        // Con la venta de contado, el backend aceptaria un pago parcial en
        // silencio y la factura saldria mal cuadrada. A credito el faltante es
        // justamente lo que se fia, y lo que se comprueba es el limite.
        var total = parseFloat(gtotal) || 0;
        // A credito se vende cuando esa forma de pago esta elegida o ya figura
        // en el desglose: lo que quede sin cubrir es lo que se fia.
        var aCredito = esCredito(metodoActual())
          || payLines.some(function (l) { return esCredito(l.metodo); });
        var falta = total - res.pagado;

        if (!aCredito && falta > 0.005) {
          showAlert(t('pago_incompleto_bloqueo', 'No se puede facturar: falta cubrir %s del total.')
            .replace('%s', formatMoney(falta)));
          return;
        }

        if (aCredito && falta > 0.005) {
          var disponible = estadoCliente && estadoCliente.credito ? estadoCliente.credito.disponible : 0;
          if (falta > disponible + 0.005) {
            showAlert(t('credito_excede_limite', 'La venta supera el crédito disponible.') + ' ' +
              t('credito_faltante', 'Faltan %1: el disponible es %2.')
                .replace('%1$s', formatMoney(falta - disponible))
                .replace('%2$s', formatMoney(disponible)));
            return;
          }
        }

        // Cerrar modal y enviar form
        var payModalEl = $('payModal');
        if (payModalEl && window.bootstrap) {
          var modal = window.bootstrap.Modal.getInstance(payModalEl);
          if (modal) modal.hide();
        }
        // Pequeño delay para que el modal cierre antes del submit
        setTimeout(function () {
          var form = $('pos-sale-form');
          // Un control del formulario cuyo id o name sea "submit" tapa a
          // form.submit y la llamada directa lanzaria TypeError; invocar el
          // metodo del prototipo lo evita.
          if (form) HTMLFormElement.prototype.submit.call(form);
        }, 150);
      });
    }
  }

  /* ──────────────────────────────────────────────────────
     RESET: cancelar venta
  ────────────────────────────────────────────────────── */
  /**
   * Descuento sobre el total de la factura. Se guarda como "150" o "10%" en
   * spos_discount, que es lo que loadItems() y order_discount ya interpretan.
   */
  function initDescuentoTotal() {
    var btn = $('add_discount');
    var modalEl = $('descuentoModal');
    if (!btn || !modalEl || !window.bootstrap) { return; }

    var campo   = $('ds-valor');
    var tipoBox = $('ds-tipo');
    var ayuda   = $('ds-ayuda');
    var err     = $('ds-error');
    var resumen = $('ds-resumen');
    var modal   = window.bootstrap.Modal.getOrCreateInstance(modalEl);

    function tipo(valor) {
      if (!tipoBox) { return 'monto'; }
      if (valor) {
        qsa('button', tipoBox).forEach(function (b) { b.classList.toggle('activo', b.dataset.tipo === valor); });
        if (ayuda) {
          ayuda.textContent = valor === 'pct'
            ? t('descuento_tipo_pct', 'Porcentaje sobre el precio')
            : t('descuento_tipo_monto', 'Monto por unidad');
        }
      }
      var act = qs('button.activo', tipoBox);
      return act ? act.dataset.tipo : 'monto';
    }

    function pintarResumen() {
      if (!resumen) { return; }
      var bruto = parseFloat(total) || 0;
      var val = parseFloat(campo ? campo.value : 0) || 0;
      var desc = tipo() === 'pct' ? (bruto * val) / 100 : val;
      if (desc > bruto) { desc = bruto; }
      resumen.textContent = formatMoney(bruto) + ' − ' + formatMoney(desc) + ' = ' + formatMoney(bruto - desc);
    }

    btn.addEventListener('click', function () {
      var guardado = String(get('spos_discount') || '0');
      var esPct = guardado.indexOf('%') !== -1;
      if (campo) { campo.value = parseFloat(guardado) > 0 ? parseFloat(guardado) : ''; }
      tipo(esPct ? 'pct' : 'monto');
      if (err) { err.classList.remove('visible'); }
      pintarResumen();
      modal.show();
    });

    if (tipoBox) {
      tipoBox.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) { return; }
        tipo(b.dataset.tipo);
        pintarResumen();
        if (campo) { campo.focus(); }
      });
    }
    if (campo) { campo.addEventListener('input', pintarResumen); }

    var aplicar = $('ds-aplicar');
    if (aplicar) {
      aplicar.addEventListener('click', function () {
        var val = parseFloat(campo ? campo.value : 0);
        if (isNaN(val) || val < 0) { val = 0; }
        var esPct = tipo() === 'pct';
        var bruto = parseFloat(total) || 0;
        if (esPct ? val > 100 : val > bruto) {
          if (err) { err.textContent = t('unexpected_value', 'El descuento supera el total.'); err.classList.add('visible'); }
          return;
        }
        store('spos_discount', val > 0 ? (esPct ? val + '%' : String(val)) : '0');
        var dv = $('discount_val');
        if (dv) { dv.value = get('spos_discount'); }
        loadItems();
        modal.hide();
      });
    }

    var quitar = $('ds-quitar');
    if (quitar) {
      quitar.addEventListener('click', function () {
        remove('spos_discount');
        var dv = $('discount_val');
        if (dv) { dv.value = ''; }
        if (campo) { campo.value = ''; }
        loadItems();
        modal.hide();
      });
    }
  }

  function initReset() {
    var resetBtn = $('reset');
    if (!resetBtn) return;
    resetBtn.addEventListener('click', function () {
      if (count <= 1) return;
      confirmar(t('r_u_sure', '¿Está seguro de cancelar la venta?'), function () {
        limpiarCarrito();
        window.location.href = window.base_url + 'pos';
      });
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
      // La referencia identifica la cuenta en la lista de suspendidas; sin
      // ella el cajero no sabe cual retomar.
      var ref = $('hold_ref');
      if (!ref || !ref.value.trim()) {
        pedirReferencia();
        return;
      }
      enviarSuspension();
    });

    // Aceptar en el modal: sin referencia no cierra, marca el campo y avisa.
    var aceptar = $('notasAceptar');
    if (aceptar) {
      aceptar.addEventListener('click', function () {
        var ref = $('hold_ref');
        if (!ref || !ref.value.trim()) {
          marcarReferencia(true);
          ref && ref.focus();
          return;
        }
        marcarReferencia(false);
        var modal = document.getElementById('ModalNotes');
        if (modal && window.bootstrap) {
          var inst = window.bootstrap.Modal.getInstance(modal);
          if (inst) { inst.hide(); }
        }
      });
    }
    var refInput = $('hold_ref');
    if (refInput) {
      refInput.addEventListener('input', function () {
        if (this.value.trim()) { marcarReferencia(false); }
      });
    }
  }

  function marcarReferencia(malo) {
    var ref = $('hold_ref');
    var err = $('hold_ref_error');
    if (ref) { ref.classList.toggle('is-invalid', !!malo); }
    if (err) {
      err.textContent = malo ? t('referencia_requerida', 'Agregue una nota de referencia') : '';
      err.style.setProperty('display', malo ? 'block' : 'none', 'important');
    }
  }

  function pedirReferencia() {
    var modal = document.getElementById('ModalNotes');
    var ref = $('hold_ref');
    if (modal && window.bootstrap) {
      window.bootstrap.Modal.getOrCreateInstance(modal).show();
      modal.addEventListener('shown.bs.modal', function una() {
        modal.removeEventListener('shown.bs.modal', una);
        marcarReferencia(true);
        if (ref) { ref.focus(); }
      });
    }
  }

  /** Manda el carrito como cuenta en espera: Pos.php lo detecta por el campo suspend. */
  function enviarSuspension() {
    var form = $('pos-sale-form');
    if (!form) { return; }
    var elCount = $('total_item');
    if (elCount) { elCount.value = an - 1; }

    var marca = document.createElement('input');
    marca.type = 'hidden';
    marca.name = 'suspend';
    marca.value = '1';
    form.appendChild(marca);
    // Los articulos ya viajan como campos del formulario; el carrito guardado
    // solo reapareceria encima de la venta siguiente.
    limpiarCarrito();
    HTMLFormElement.prototype.submit.call(form);
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
            // El buscador filtra sobre _customers, asi que el alta se ve de
            // inmediato con solo agregarlo al mapa.
            if (window._customers && res.customer) {
              window._customers[res.id] = res.customer;
            }
            if (window.posElegirCliente) {
              window.posElegirCliente(res.id);
            } else {
              renderCustomerCard(res.id);
            }
            var modal = document.getElementById('customerModal');
            if (modal && window.bootstrap) {
              window.bootstrap.Modal.getInstance(modal).hide();
            }
          } else if (res.duplicado) {
            // La identificacion ya esta registrada: el cajero queria ese cliente,
            // no uno nuevo, asi que se elige el que ya existe.
            // El POS ya carga todos los clientes al abrir: solo falta en el mapa
            // si lo dio de alta otra caja mientras esta pantalla estaba abierta.
            if (window._customers && !window._customers[res.duplicado.id]) {
              window._customers[res.duplicado.id] = {
                name: res.duplicado.name, cf1: res.duplicado.cf1, cf2: res.duplicado.cf2,
                email: '', phone: '', company: '', credito: 0
              };
            }
            if (window.posElegirCliente) { window.posElegirCliente(res.duplicado.id); }
            if (typeof avisoVenta === 'function') { avisoVenta(esc(res.msg || ''), 'fa-user'); }
            var mDup = document.getElementById('customerModal');
            if (mDup && window.bootstrap) { window.bootstrap.Modal.getInstance(mDup).hide(); }
          } else {
            if (alertEl) { alertEl.textContent = res.msg || t('error', 'Error'); alertEl.classList.remove('d-none'); }
          }
        })
        .catch(function () {
          if (alertEl) { alertEl.textContent = t('error_agregar_cliente', 'Error al agregar cliente'); alertEl.classList.remove('d-none'); }
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

        // form.reset() devuelve los valores, no las opciones que trajo el padron
        // ni las secciones que se abrieron.
        var act = $('cActividadWrap');
        if (act) { act.classList.add('d-none'); }
        var sel = $('cactividad');
        if (sel) { sel.innerHTML = ''; }
        var ocultos = $('cActividadesHidden');
        if (ocultos) { ocultos.innerHTML = ''; }
        ['codigo_canton', 'codigo_distrito', 'codigo_barrio'].forEach(function (id) {
          var el = $(id);
          if (el && el.options.length) { el.innerHTML = el.options[0].outerHTML; }
        });
        ['cUbicacionBox', 'cComercialBox'].forEach(function (id) {
          var el = $(id);
          if (el) { el.open = false; }
        });
      });
    }
  }

  /* ──────────────────────────────────────────────────────
     ALTA RAPIDA DE CLIENTE: ubicacion y tipo de identificacion
  ────────────────────────────────────────────────────── */
  function initClienteRapido() {
    var cf1 = $('cf1');
    if (!cf1) { return; }

    // El tipo 05 no lleva <Ubicacion> del pais: su direccion va en
    // OtrasSenasExtranjero, que solo ese tipo admite.
    function alternarExtranjero() {
      var extranjero = cf1.value === '05';
      var cr = $('cUbicacionCR');
      var ex = $('cUbicacionExtranjero');
      if (cr) { cr.classList.toggle('d-none', extranjero); }
      if (ex) { ex.classList.toggle('d-none', !extranjero); }
      var num = $('cf2');
      if (num) { num.setAttribute('inputmode', extranjero ? 'text' : 'numeric'); }
    }
    cf1.addEventListener('change', alternarExtranjero);
    alternarExtranjero();

    // Provincia -> canton -> distrito -> barrio. Cada nivel invalida los de abajo.
    var padres = document.querySelectorAll('#customerModal [data-hijo]');
    Array.prototype.forEach.call(padres, function (padre) {
      padre.addEventListener('change', function () {
        var vacia = padre.dataset.hijo === 'codigo_canton'
          ? padre.options[0].outerHTML
          : '<option value=""></option>';

        var actual = $(padre.dataset.hijo);
        while (actual) {
          actual.innerHTML = actual.options.length ? actual.options[0].outerHTML : vacia;
          actual = actual.dataset.hijo ? $(actual.dataset.hijo) : null;
        }
        if (!padre.value) { return; }

        var ruta = [$('codigo_provincia').value];
        if (padre.id !== 'codigo_provincia') { ruta.push($('codigo_canton').value); }
        if (padre.id === 'codigo_distrito') { ruta.push($('codigo_distrito').value); }

        var hijo = $(padre.dataset.hijo);
        fetch(padre.dataset.url + '/' + ruta.join('/'), {
          credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.ok ? r.json() : []; })
          .then(function (filas) {
            filas.forEach(function (f) {
              var o = document.createElement('option');
              o.value = f.codigo;
              o.textContent = f.nombre;
              hijo.appendChild(o);
            });
          })
          .catch(function () {});
      });
    });
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
      // El span envuelve un SVG en linea: se gira el SVG, no se sustituye por
      // una clase de Font Awesome que dibujaria un segundo icono.
      var svg = iconEl.querySelector('svg');
      if (svg) { svg.classList.toggle('nxf-spin', loading); }
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
          volcarActividades(data.actividades || []);

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

    /**
     * Guarda las actividades inscritas del contribuyente: la elegida viaja como
     * codigo_actividad y todas como actividades[] para tec_customer_actividades.
     */
    function volcarActividades(acts) {
      var wrap = $('cActividadWrap');
      var sel = $('cactividad');
      var ocultos = $('cActividadesHidden');
      if (!wrap || !sel || !ocultos) { return; }

      sel.innerHTML = '';
      ocultos.innerHTML = '';
      if (!acts.length) { wrap.classList.add('d-none'); return; }

      acts.forEach(function (a, i) {
        var o = document.createElement('option');
        o.value = a.codigo;
        o.textContent = a.codigo + ' — ' + a.descripcion;
        sel.appendChild(o);

        ocultos.insertAdjacentHTML('beforeend',
          '<input type="hidden" name="actividades[]" value="' + escAttr(a.codigo) + '">' +
          '<input type="hidden" name="actividades_desc[]" value="' + escAttr(a.descripcion || '') + '">');
        if (i === 0) { sel.value = a.codigo; }
      });
      wrap.classList.remove('d-none');
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
  // Tipos de identificación según Hacienda CR (v4.4)
  function getCf1Labels() {
    return {
      '01': t('cedula_identidad', 'Cédula Física'),
      '02': t('cedula_juridica', 'Cédula Jurídica'),
      '03': 'DIMEX',
      '04': 'NITE',
      '05': t('pasaporte', 'Pasaporte')
    };
  }

  function getDefaultCustomerId() {
    return String((window.Settings || {}).default_customer || '');
  }

  function renderCustomerCard(id) {
    var searchWrap = document.getElementById('pos-cust-search-wrap');
    var card       = document.getElementById('pos-cust-card');
    var clearBtn   = document.getElementById('pos-cust-clear');
    var avatar     = document.getElementById('pos-cust-avatar');
    var nameEl     = document.getElementById('pos-cust-name');
    var metaEl     = document.getElementById('pos-cust-doc');
    var contEl     = document.getElementById('pos-cust-contact');
    if (!card) return;

    var cmap = window._customers || {};
    var c    = cmap[id];

    // Sin cliente elegido la venta sale al de contado: basta una linea que lo
    // diga. El buscador aparece con la lupa, no ocupa sitio de entrada.
    if (!c) {
      if (searchWrap) searchWrap.style.display = 'none';
      if (clearBtn)   clearBtn.style.display   = 'none';
      var lupa = document.getElementById('pos-cust-buscar');
      if (lupa) lupa.style.display = 'inline-flex';
      card.className = 'pcp-cust-card is-default';
      if (avatar) avatar.textContent = 'C';
      if (nameEl) nameEl.textContent = t('cliente_contado', 'Cliente de Contado');
      if (metaEl) metaEl.innerHTML   = '';
      if (contEl) contEl.innerHTML   = '';
      return;
    }

    if (searchWrap) searchWrap.style.display = 'none';
    if (clearBtn)   clearBtn.style.display   = 'inline-flex';
    var lupaSel = document.getElementById('pos-cust-buscar');
    if (lupaSel) lupaSel.style.display = 'none';

    var name     = c.name || '—';
    var initials = name.trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase() || '?';

    card.className = 'pcp-cust-card';
    if (avatar) avatar.textContent = initials;
    if (nameEl) {
      nameEl.textContent = name;
      nameEl.setAttribute('title', name);
    }

    // Identificación y razón social
    if (metaEl) {
      var filas = '';
      if (c.cf2) {
        var label = getCf1Labels()[c.cf1] || t('documento_label', 'Doc.');
        filas += '<div class="pcp-cust-dato"><span class="k">' + escaparHtml(label) + '</span>' +
                 '<span class="v mono">' + escaparHtml(c.cf2) + '</span></div>';
      }
      if (c.company) {
        filas += '<div class="pcp-cust-dato"><span class="k">' + t('business_name', 'Razón social') + '</span>' +
                 '<span class="v">' + escaparHtml(c.company) + '</span></div>';
      }
      metaEl.innerHTML = filas;
    }

    // Contacto y credito
    if (contEl) {
      var filas2 = '';
      if (c.email) {
        filas2 += '<div class="pcp-cust-dato"><span class="k">' + t('email', 'Correo') + '</span>' +
                  '<span class="v" title="' + escaparHtml(c.email) + '">' + escaparHtml(c.email) + '</span></div>';
      }
      if (c.phone) {
        filas2 += '<div class="pcp-cust-dato"><span class="k">' + t('phone', 'Teléfono') + '</span>' +
                  '<span class="v">' + escaparHtml(c.phone) + '</span></div>';
      }
      if (c.credito && parseFloat(c.credito) > 0) {
        filas2 += '<div class="pcp-cust-dato"><span class="k">' + t('credit_limit', 'Límite crédito') + '</span>' +
                  '<span class="v">' + formatMoney(c.credito) + '</span></div>';
      }
      contEl.innerHTML = filas2;
    }
  }

  function escaparHtml(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function initCustomerSelect() {
    var input = $('pos-cust-search');
    if (!input) { return; }

    var defaultId   = getDefaultCustomerId();
    var hiddenInput = $('pos-customer-hidden');
    var lista       = document.createElement('div');
    lista.className = 'pos-ac pos-ac-cust';
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(lista);

    function setCustomer(val) {
      store('spos_customer', val || '');
      renderCustomerCard(val || '');
      if (hiddenInput) { hiddenInput.value = val || defaultId; }
      // Otro cliente admite otros comprobantes y otro crédito.
      if (window.posCargarEstadoCliente) { window.posCargarEstadoCliente(); }
    }

    function cerrar() {
      lista.classList.remove('abierto');
      lista.innerHTML = '';
    }

    /** Todas las palabras tecleadas tienen que aparecer en el nombre, la cedula o el correo. */
    function coincide(c, palabras) {
      var heno = ((c.name || '') + ' ' + (c.cf2 || '') + ' ' + (c.company || '') + ' ' +
                  (c.email || '') + ' ' + (c.phone || '')).toLowerCase();
      return palabras.every(function (w) { return heno.indexOf(w) !== -1; });
    }

    function buscar(term) {
      var palabras = term.toLowerCase().split(/\s+/).filter(Boolean);
      if (!palabras.length) { return []; }
      var cmap = window._customers || {};
      var res = [];
      for (var id in cmap) {
        if (!Object.prototype.hasOwnProperty.call(cmap, id)) { continue; }
        if (String(id) === defaultId) { continue; }
        if (coincide(cmap[id], palabras)) {
          res.push({ id: id, c: cmap[id] });
          if (res.length >= 25) { break; }
        }
      }
      return res;
    }

    function pintar(res) {
      lista.innerHTML = '';
      if (!res.length) {
        var vacio = document.createElement('div');
        vacio.className = 'pos-ac-vacio';
        vacio.textContent = t('cliente_sin_coincidencias', 'Ningun cliente coincide');
        lista.appendChild(vacio);
        lista.classList.add('abierto');
        return;
      }
      res.forEach(function (r) {
        var nombre = r.c.name || '';
        var iniciales = nombre.trim().split(/\s+/).slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase() || '?';
        var meta = [];
        if (r.c.cf2)     { meta.push('<span class="pos-ac-code">' + escaparHtml(r.c.cf2) + '</span>'); }
        if (r.c.company) { meta.push('<span>' + escaparHtml(r.c.company) + '</span>'); }
        if (r.c.phone)   { meta.push('<span>' + escaparHtml(r.c.phone) + '</span>'); }

        var fila = document.createElement('button');
        fila.type = 'button';
        fila.className = 'pos-ac-item';
        fila.innerHTML =
          '<span class="pos-ac-fila">' +
            '<span class="pos-ac-av">' + escaparHtml(iniciales) + '</span>' +
            '<span class="pos-ac-main">' +
              '<span class="pos-ac-name">' + escaparHtml(nombre) + '</span>' +
              (meta.length ? '<span class="pos-ac-meta">' + meta.join('<i>·</i>') + '</span>' : '') +
            '</span>' +
          '</span>';
        fila.addEventListener('mousedown', function (e) {
          e.preventDefault();
          cerrar();
          input.value = '';
          setCustomer(r.id);
          var si = $('add_item');
          if (si) { si.focus(); }
        });
        lista.appendChild(fila);
      });
      lista.classList.add('abierto');
    }

    var temporizador;
    input.addEventListener('input', function () {
      clearTimeout(temporizador);
      var term = this.value.trim();
      if (!term) { cerrar(); return; }
      temporizador = setTimeout(function () { pintar(buscar(term)); }, 120);
    });

    input.addEventListener('keydown', function (e) {
      var filas = Array.from(qsa('.pos-ac-item', lista));
      if (e.key === 'ArrowDown' && filas.length) { filas[0].focus(); e.preventDefault(); }
      else if (e.key === 'Enter') {
        e.preventDefault();
        if (filas.length) { filas[0].dispatchEvent(new MouseEvent('mousedown', { bubbles: true })); }
      } else if (e.key === 'Escape') { cerrar(); this.value = ''; }
    });

    lista.addEventListener('keydown', function (e) {
      var filas = Array.from(qsa('.pos-ac-item', lista));
      var i = filas.indexOf(document.activeElement);
      if (e.key === 'ArrowDown' && i < filas.length - 1) { filas[i + 1].focus(); e.preventDefault(); }
      else if (e.key === 'ArrowUp') { if (i > 0) { filas[i - 1].focus(); } else { input.focus(); } e.preventDefault(); }
      else if (e.key === 'Enter' && i >= 0) { filas[i].dispatchEvent(new MouseEvent('mousedown', { bubbles: true })); e.preventDefault(); }
      else if (e.key === 'Escape') { cerrar(); input.focus(); }
    });

    document.addEventListener('click', function (e) {
      if (!input.parentNode.contains(e.target)) { cerrar(); }
    });

    var guardado = get('spos_customer');
    if (guardado && guardado !== defaultId && (window._customers || {})[guardado]) {
      setCustomer(guardado);
    } else {
      renderCustomerCard('');
    }

    var clearBtn = $('pos-cust-clear');
    if (clearBtn) {
      clearBtn.addEventListener('click', function () { setCustomer(''); });
    }

    var lupaBtn = $('pos-cust-buscar');
    if (lupaBtn) {
      lupaBtn.addEventListener('click', function () { window.posCambiarCliente(); });
    }

    window.posCambiarCliente = function () {
      setCustomer('');
      var wrap = $('pos-cust-search-wrap');
      if (wrap) { wrap.style.display = ''; }
      setTimeout(function () { input.value = ''; input.focus(); }, 30);
    };

    // Alta de cliente: el formulario avisa para dejarlo elegido al volver.
    window.posElegirCliente = function (id) { setCustomer(String(id)); };
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
        onPayMethodChange(method);
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
        onPayMethodChange(method);
      });
    });
  }

  /** Elige una forma de pago desde codigo, sincronizando modal y carrito. */
  function seleccionarMetodo(method) {
    var pv = $('paid_by_val');
    if (pv) { pv.value = method; }
    syncPayModal(method);
    qsa('.pcp-pay-btn').forEach(function (b) {
      b.classList.toggle('active', b.dataset.method === method);
    });
    onPayMethodChange(method);
  }

  function syncPayModal(method) {
    qsa('.pay-method-btn').forEach(function (b) {
      b.classList.toggle('active', b.dataset.method === method);
    });
  }

  /* ──────────────────────────────────────────────────────
     LISTA SINPE EN TIEMPO REAL (modal de pago → método "sinpe")
     Sondea pos/ajax_sinpe_pending mientras el método activo sea SINPE. Al
     elegir un pago se rellena el monto y su comprobante, que viaja con el
     formulario para que Pos.php lo marque como usado.
  ────────────────────────────────────────────────────── */
  var sinpePollTimer = null;
  var sinpeCurrentMethod = 'cash';

  function onPayMethodChange(method) {
    sinpeCurrentMethod = method;

    // El campo de referencia solo aparece donde hace falta. En SINPE se rellena
    // solo al elegir un pago del desplegable, pero queda visible y editable.
    var grupoRef = $('payRefGroup');
    if (grupoRef) {
      var necesita = !!(METODOS[method] || {}).refRequerida;
      grupoRef.style.display = necesita ? '' : 'none';
      if (!necesita) { var r = $('payRef'); if (r) r.value = ''; }
    }

    var panel = $('sinpePendingPanel');
    if (panel) {
      if (method === 'sinpe') {
        panel.style.display = '';
        sinpeStartPolling();
      } else {
        panel.style.display = 'none';
        sinpeStopPolling();
      }
    }

    aplicarModoMetodo(method);
    aplicarModoCredito(method);
    recalcPago();
  }

  /**
   * A credito no se recibe dinero: el campo de monto y el teclado se apagan y
   * el disponible del cliente queda a la vista.
   */
  function aplicarModoCredito(method) {
    var credito = esCredito(method);
    var monto = $('amount');
    if (monto) {
      monto.readOnly = credito;
      if (credito) { monto.value = ''; }
    }
    var teclado = $('payKeypad');
    if (teclado) {
      qsa('#payKeypad button').forEach(function (b) { b.disabled = credito; });
    }
    var rapidos = $('payQuickAmounts');
    if (rapidos) { rapidos.style.display = credito ? 'none' : ''; }

    var info = $('payCreditInfo');
    if (info) {
      var c = (estadoClientePago && estadoClientePago.credito) || null;
      if (credito && c && c.permitido) {
        info.textContent = formatMoney(c.disponible) + ' · ' + c.dias + ' d';
        info.hidden = false;
      } else {
        info.hidden = true;
      }
    }
  }

  // En tarjeta y SINPE el monto y la referencia los fija el cobro, no el cajero:
  // los montos sugeridos y el campo editable solo invitan a descuadrar.
  var METODOS_COBRO_EXTERNO = { card: 1, sinpe: 1 };

  function aplicarModoMetodo(method) {
    var externo = !!METODOS_COBRO_EXTERNO[method];

    var rapidos = $('payQuickAmounts');
    if (rapidos) { rapidos.style.display = externo ? 'none' : ''; }

    var datafono = $('payDatafonoWrap');
    if (datafono) { datafono.style.display = (method === 'card') ? '' : 'none'; }

    var monto = $('amount');
    if (monto) {
      monto.readOnly = externo;
      monto.classList.toggle('fijo', externo);
      // Tarjeta: hasta que llegue el cobro, lo que se cobra es lo que falta.
      if (externo && !parseFloat(monto.value)) {
        var pend = (parseFloat(gtotal) || 0) - totalLineas();
        monto.value = pend > 0 ? formatDecimal(pend) : '';
      }
    }

    var ref = $('payRef');
    if (ref) {
      ref.readOnly = externo;
      ref.classList.toggle('fijo', externo);
    }
  }

  function sinpeStartPolling() {
    sinpeStopPolling();
    sinpeFetchPending();
    sinpePollTimer = setInterval(sinpeFetchPending, 4000);
  }

  function sinpeStopPolling() {
    if (sinpePollTimer) { clearInterval(sinpePollTimer); sinpePollTimer = null; }
  }

  function sinpeFetchPending() {
    if (sinpeCurrentMethod !== 'sinpe') { sinpeStopPolling(); return; }
    var dot = $('sinpeLiveDot');
    fetch(window.base_url + 'pos/ajax_sinpe_pending?monto=' + encodeURIComponent(gtotal || 0), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (dot) dot.style.color = '#2ecc71';
        sinpeRenderList((data && data.pendientes) || []);
      })
      .catch(function () {
        if (dot) dot.style.color = '#e74c3c';
      });
  }

  // Detalles de cada pago indexados por comprobante; el <select> solo guarda el id.
  var sinpeCache = {};

  function sinpeRenderList(rows) {
    var sel = $('sinpePendingSelect');
    var countEl = $('sinpePendingCount');
    if (!sel) return;

    var refInput = $('sinpe_reference');
    var seleccionado = refInput ? refInput.value : '';

    sinpeCache = {};
    rows.forEach(function (p) {
      if (p.comprobante) sinpeCache[p.comprobante] = p;
    });

    if (countEl) {
      countEl.textContent = rows.length
        ? (rows.length + (rows.length === 1 ? ' disponible' : ' disponibles'))
        : '';
    }

    // Repintar con la lista desplegada la cierra: solo se toca si cambió el contenido.
    var firma = rows.map(function (p) { return p.comprobante; }).join('|');
    if (sel.dataset.firma === firma) return;
    sel.dataset.firma = firma;

    if (!rows.length) {
      sel.innerHTML = '<option value="">Sin pagos SINPE que cubran ' + formatMoney(parseFloat(gtotal) || 0) + '</option>';
      return;
    }

    var html = '<option value="">Seleccione el pago SINPE recibido…</option>';
    rows.forEach(function (p) {
      var hora = sinpeHora(p.fecha);
      var etiqueta = formatMoney(parseFloat(p.monto) || 0) +
        ' · ' + (p.nombre || 'SINPE') +
        (p.telefono ? ' · ' + p.telefono : '') +
        (hora ? ' · ' + hora : '');
      html += '<option value="' + escAttr(p.comprobante || '') + '"' +
        (seleccionado && p.comprobante === seleccionado ? ' selected' : '') + '>' +
        esc(etiqueta) + '</option>';
    });
    sel.innerHTML = html;
  }

  function sinpeHora(fecha) {
    try {
      return new Date(String(fecha || '').replace(' ', 'T'))
        .toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' });
    } catch (e) { return ''; }
  }

  function sinpeLimpiarSeleccion() {
    var refInput = $('sinpe_reference');
    if (refInput) refInput.value = '';
    var refVis = $('payRef');
    if (refVis) refVis.value = '';
    var sel = $('sinpePendingSelect');
    if (sel) sel.value = '';
    var infoBox = $('sinpeSelectedInfo');
    if (infoBox) infoBox.style.display = 'none';
  }

  function sinpeAplicarSeleccion(comprobante) {
    if (!comprobante) { sinpeLimpiarSeleccion(); return; }

    var p = sinpeCache[comprobante];
    if (!p) return;

    var refInput = $('sinpe_reference');
    if (refInput) refInput.value = comprobante;

    var monto = parseFloat(p.monto) || 0;
    var amountInput = $('amount');
    if (amountInput) {
      amountInput.value = formatDecimal(monto);
    }
    // El comprobante es la referencia de esa forma de pago.
    var refEl = $('payRef');
    if (refEl) refEl.value = comprobante;
    recalcPago();

    var set = function (id, valor) { var el = $(id); if (el) el.textContent = valor; };
    set('sinpeSelNombre', p.nombre || 'SINPE');
    set('sinpeSelTelefono', p.telefono || 'sin numero');
    set('sinpeSelBanco', p.banco || 'banco no identificado');
    set('sinpeSelFecha', sinpeHora(p.fecha) || '—');
    set('sinpeSelectedComprobante', comprobante);
    set('sinpeSelMonto', formatMoney(monto));

    var descRow = $('sinpeSelDescRow');
    if (descRow) {
      if (p.descripcion) {
        set('sinpeSelDescripcion', p.descripcion);
        descRow.style.display = '';
      } else {
        descRow.style.display = 'none';
      }
    }

    // Excedente sobre el total: corresponde vuelto.
    var sobrante = $('sinpeSobrantePago');
    if (sobrante) {
      var dif = monto - (parseFloat(gtotal) || 0);
      if (dif > 0.5) {
        sobrante.textContent = 'El pago supera el total en ' + formatMoney(dif) + ' — corresponde vuelto.';
        sobrante.style.display = '';
      } else {
        sobrante.style.display = 'none';
      }
    }

    var infoBox = $('sinpeSelectedInfo');
    if (infoBox) infoBox.style.display = '';
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function escAttr(s) { return esc(s); }

  function initSinpePending() {
    var sel = $('sinpePendingSelect');
    if (sel) {
      sel.addEventListener('change', function () { sinpeAplicarSeleccion(sel.value); });
    }
    var clearBtn = $('sinpeClearSelection');
    if (clearBtn) {
      clearBtn.addEventListener('click', sinpeLimpiarSeleccion);
    }
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
          // Con pago dividido, "exacto" es el faltante, no el total.
          var falta = (parseFloat(gtotal) || 0) - totalLineas();
          amountInput.value = formatDecimal(falta > 0 ? falta : 0);
          recalcPago();
        }
      });
    }

    qsa('.pay-quick-btn[data-amount]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var amountInput = $('amount');
        if (amountInput) {
          amountInput.value = parseFloat(this.dataset.amount);
          recalcPago();
        }
      });
    });
  }

  /* ──────────────────────────────────────────────────────
     ATAJOS DE TECLADO
  ────────────────────────────────────────────────────── */
  /**
   * Cada accion declara el ajuste que la controla y su combinacion por omision.
   * Si el elemento destino no esta en pantalla (por ejemplo "suspender" en una
   * nota de credito) el atajo simplemente no se registra.
   *
   * Las combinaciones por omision evitan F11 y F12: el navegador se queda con
   * esas teclas (pantalla completa y herramientas de desarrollo) y la pagina
   * nunca llega a verlas.
   */
  var ACCIONES_ATAJO = [
    { id: 'quick_product',          fijo: 'F2',     etiqueta: 'modal_producto_rapido', accion: function () { abrirModal('adHocModal'); } },
    { id: 'focus_add_item',         def: 'F3',      etiqueta: 'atajo_agregar_item', accion: function () { var i = $('add_item'); if (i) { i.focus(); i.select(); } } },
    { id: 'finalize_sale',          def: 'F4',      etiqueta: 'atajo_finalizar_venta', accion: function () { clic('payment') || clic('submit-sale'); } },
    { id: 'add_customer',           def: 'F6',      etiqueta: 'atajo_agregar_cliente', accion: function () { if (window.posCambiarCliente) { window.posCambiarCliente(); } } },
    { id: 'edit_last_product',      def: 'F7',      etiqueta: 'atajo_editar_ultimo', accion: editarUltimaLinea },
    { id: 'toggle_category_slider', def: 'F8',      etiqueta: 'atajo_alternar_cats', accion: alternarCategorias },
    { id: 'cancel_sale',            def: 'F9',      etiqueta: 'atajo_cancelar_venta', accion: function () { clic('reset'); } },
    { id: 'suspend_sale',           def: 'F10',     etiqueta: 'atajo_suspender_venta', accion: function () { clic('suspend'); } },
    { id: 'open_hold_bills',        def: 'ALT+S',   etiqueta: 'atajo_retomar', accion: function () { clic('holdBillsBtn'); } },
    { id: 'today_sale',             def: 'ALT+V',   etiqueta: 'atajo_ventas_hoy', accion: function () { abrirParcial('pos/today_sale'); } },
    { id: 'close_register',         def: 'ALT+R',   etiqueta: 'atajo_cerrar_caja', accion: function () { abrirParcial('pos/close_register'); } }
  ];

  function clic(id) {
    var el = document.getElementById(id);
    if (!el) { return false; }
    el.click();
    return true;
  }

  function abrirModal(id) {
    var el = document.getElementById(id);
    if (el && window.bootstrap) { window.bootstrap.Modal.getOrCreateInstance(el).show(); }
  }

  function alternarCategorias() {
    var bar = $('posCatBar');
    if (bar) { bar.classList.toggle('d-none'); }
  }

  function editarUltimaLinea() {
    var botones = document.querySelectorAll('#posTable .edit');
    if (botones.length) { botones[botones.length - 1].click(); }
  }

  /** Mensaje que el servidor dejo en flashdata (apertura de caja, errores). */
  function initAvisoServidor() {
    var a = window._pos_aviso;
    if (!a || !a.texto) { return; }
    window._pos_aviso = null;
    avisoVenta(a.texto, a.error ? 'fa-exclamation-triangle' : 'fa-info-circle');
  }

  /**
   * Empuja los comprobantes pendientes hacia Hacienda.
   *
   * El envio no ocurre al cobrar: la venta solo deja el XML guardado y
   * shacienda lo firma y lo remite por tandas. Sin alguien que llame a ese
   * endpoint los comprobantes se quedan en "pendiente" para siempre.
   */
  function initEnvioHacienda() {
    function empujar() {
      fetch(window.base_url + 'shacienda', { credentials: 'same-origin' })
        .catch(function () { /* la proxima tanda reintenta */ });
    }
    empujar();
    setInterval(empujar, 120000);
  }

  /** Cierre de caja desde la barra superior, el mismo modal que el atajo. */
  function initCierreCaja() {
    var boton = $('cerrarCajaBtn');
    if (boton) {
      boton.addEventListener('click', function () { abrirParcial('pos/close_register'); });
    }
  }

  /**
   * Vuelve a insertar los <script> de un fragmento para que corran.
   *
   * Asignar innerHTML no ejecuta el script: el navegador lo deja inerte. Sin
   * esto, ninguna pantalla parcial del POS tiene comportamiento.
   */
  function activarScripts(contenedor) {
    var viejos = contenedor.querySelectorAll('script');
    Array.prototype.forEach.call(viejos, function (viejo) {
      var nuevo = document.createElement('script');
      Array.prototype.forEach.call(viejo.attributes, function (a) {
        nuevo.setAttribute(a.name, a.value);
      });
      nuevo.textContent = viejo.textContent;
      viejo.parentNode.replaceChild(nuevo, viejo);
    });
  }

  /**
   * Abre el cierre de caja al cargar cuando el servidor rechazo una salida con
   * la caja abierta (Auth::logout redirige con ?cerrar_caja=1).
   */
  function initCierreForzado() {
    if (new URLSearchParams(window.location.search).get('cerrar_caja') !== '1') { return; }
    abrirParcial('pos/close_register');
    // La marca ya cumplio: si no se limpia, recargar la pagina reabre el modal.
    if (window.history && window.history.replaceState) {
      window.history.replaceState({}, '', window.location.pathname);
    }
  }

  /** Carga una vista parcial del POS (ventas de hoy, cierre de caja) en un modal. */
  function abrirParcial(ruta) {
    if (!window.bootstrap) { window.location.href = (window.base_url || '') + ruta; return; }
    var cont = $('posParcialModal');
    if (!cont) {
      cont = document.createElement('div');
      cont.id = 'posParcialModal';
      cont.className = 'modal fade';
      cont.tabIndex = -1;
      cont.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-scrollable">' +
        '<div class="modal-content"><div class="modal-body" id="posParcialBody"></div></div></div>';
      document.body.appendChild(cont);
    }
    var cuerpo = $('posParcialBody');
    cuerpo.innerHTML = '<div class="p-4 text-center text-muted">…</div>';
    window.bootstrap.Modal.getOrCreateInstance(cont).show();
    fetch((window.base_url || '') + ruta, { credentials: 'same-origin' })
      .then(function (r) {
        if (!r.ok) { throw new Error(r.status); }
        return r.text();
      })
      .then(function (html) {
        cuerpo.innerHTML = html;
        activarScripts(cuerpo);
      })
      .catch(function () {
        cuerpo.innerHTML = '<div class="p-4 text-center text-danger">' +
          t('no_se_pudo_cargar', 'No se pudo cargar la pantalla.') + '</div>';
      });
  }

  /**
   * Lee una combinacion escrita por el usuario ("ALT+I", "F9", "CTRL+SHIFT+P")
   * y la deja comparable con un evento de teclado.
   */
  function leerCombinacion(texto) {
    if (!texto) { return null; }
    var partes = String(texto).toUpperCase().split('+').map(function (x) { return x.trim(); }).filter(Boolean);
    var tecla = partes.pop();
    if (!tecla) { return null; }
    return {
      alt:   partes.indexOf('ALT') !== -1,
      ctrl:  partes.indexOf('CTRL') !== -1 || partes.indexOf('CONTROL') !== -1,
      shift: partes.indexOf('SHIFT') !== -1,
      tecla: tecla
    };
  }

  /** Nombre canonico de la tecla de un evento, en el mismo alfabeto que leerCombinacion. */
  function teclaDeEvento(e) {
    var k = e.key;
    if (!k) { return ''; }
    if (k === 'Escape') { return 'ESC'; }
    if (k === ' ') { return 'SPACE'; }
    if (k === 'Delete') { return 'DEL'; }
    if (k === 'Insert') { return 'INS'; }
    return k.length === 1 ? k.toUpperCase() : k.toUpperCase();
  }

  function coincide(combo, e) {
    return !!combo &&
      combo.alt === e.altKey &&
      combo.ctrl === (e.ctrlKey || e.metaKey) &&
      combo.shift === e.shiftKey &&
      combo.tecla === teclaDeEvento(e);
  }

  function initKeyboardShortcuts() {
    var S = window.Settings || {};

    var registrados = [];
    ACCIONES_ATAJO.forEach(function (a) {
      var texto = a.fijo || (S[a.id] != null && S[a.id] !== '' ? S[a.id] : a.def);
      var combo = leerCombinacion(texto);
      if (combo) { registrados.push({ combo: combo, accion: a.accion, etiqueta: a.etiqueta, texto: texto }); }
    });
    window._posAtajos = registrados;

    document.addEventListener('keydown', function (e) {
      var activo = document.activeElement;
      var tag = activo ? activo.tagName : '';
      var escribiendo = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
      var esBusqueda = activo && activo.id === 'add_item';

      // Escribir en un campo no debe disparar atajos de una sola letra; las teclas
      // de funcion y las combinaciones con modificador si valen en cualquier parte.
      var teclaFn = /^F\d{1,2}$/.test(teclaDeEvento(e));
      var conModificador = e.altKey || e.ctrlKey || e.metaKey;

      for (var i = 0; i < registrados.length; i++) {
        if (!coincide(registrados[i].combo, e)) { continue; }
        if (escribiendo && !teclaFn && !conModificador) { continue; }
        e.preventDefault();
        registrados[i].accion();
        return;
      }

      // F5 recarga y perderia la venta en curso.
      if (e.key === 'F5') { e.preventDefault(); return; }

      if (e.key === 'Escape' && esBusqueda) {
        var si = $('add_item');
        if (si) { si.value = ''; }
        return;
      }

      // + del teclado numerico: volver a la busqueda sin soltar la mano del teclado.
      if ((e.key === '+' || e.key === '=') && !escribiendo) {
        e.preventDefault();
        var sb = $('add_item');
        if (sb) { sb.focus(); sb.value = ''; }
      }
    });
  }

  /* ──────────────────────────────────────────────────────
     SIDEBAR TOGGLE
  ────────────────────────────────────────────────────── */
  function initSidebarToggle() {
    var nav = $('posNav');
    var btn = $('navToggle');
    if (!nav || !btn) return;
    // El swap de icono (panelleftclose/panelleftopen) lo resuelve el CSS via
    // el selector hermano '#posNav.collapsed ~ .pos-main .pos-topbar ...'
    // (nx-sidebar.css). Acá solo se alterna la clase; tocar navToggleIcon.className
    // pisaba esa clase con 'fa fa-bars'/'fa fa-indent' (icono viejo de FontAwesome)
    // y dejaba el SVG nuevo y el glifo FA superpuestos en el mismo botón.
    btn.addEventListener('click', function () {
      var collapsed = nav.classList.toggle('collapsed');
      btn.setAttribute('aria-expanded', String(!collapsed));
    });

    // El POS necesita el ancho para el carrito y la rejilla de productos.
    nav.classList.add('collapsed');
    btn.setAttribute('aria-expanded', 'false');
  }

  /* ──────────────────────────────────────────────────────
     ALERTA SIMPLE (sin dependencias)
  ────────────────────────────────────────────────────── */
  /** Confirmacion con el mismo dialogo que usa el resto del POS. */
  function confirmar(msg, alAceptar) {
    if (window.Swal) {
      Swal.fire({
        text: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: t('accept', 'Aceptar'),
        cancelButtonText: t('cancel', 'Cancelar'),
        reverseButtons: true
      }).then(function (r) { if (r.isConfirmed) { alAceptar(); } });
    } else if (window.confirm(msg)) {
      alAceptar();
    }
  }

  /** El carrito vive en localStorage: al soltarlo hay que vaciarlo. */
  function limpiarCarrito() {
    remove('spositems');
    remove('spos_tax');
    remove('spos_discount');
    remove('spos_customer');
  }

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
     QZ TRAY: puente local de impresión/cajón por computadora.
     Bloqueo total del POS mientras no haya conexión, con
     reconexión automática en segundo plano (sin intervención
     del cajero). La impresora elegida vive en localStorage de
     ESTA computadora/navegador, nunca en el servidor.
  ────────────────────────────────────────────────────── */
  // Estados del aviso: 'checking' (arranque, muy breve), 'waiting' (QZ Tray
  // instalado y corriendo, pero su propia ventana nativa está pidiendo
  // permiso al usuario) e 'install' (QZ Tray no responde, ofrecer descarga).
  function showQzOverlay(state) {
    var el = $('qzBlockOverlay');
    if (!el) return;
    el.style.display = 'flex';
    var panels = { checking: $('qzPanelChecking'), waiting: $('qzPanelWaiting'), install: $('qzPanelInstall') };
    Object.keys(panels).forEach(function (key) {
      if (panels[key]) panels[key].style.display = (key === state) ? '' : 'none';
    });
  }

  function hideQzOverlay() {
    var el = $('qzBlockOverlay');
    if (el) el.style.display = 'none';
  }

  function qzConnect() {
    if (!window.qz) { setTimeout(qzConnect, 1000); return; }
    if (qz.websocket.isActive()) { hideQzOverlay(); return; }

    var settled = false;
    // Si no resuelve rápido, casi siempre es porque QZ Tray SÍ está
    // instalado y está mostrando su propia ventana nativa de "permitir este
    // sitio" — no que falte instalarlo. Avisamos eso en vez de pedir
    // instalar de nuevo.
    var waitingTimer = setTimeout(function () {
      if (!settled) showQzOverlay('waiting');
    }, 1200);

    qz.websocket.connect().then(function () {
      settled = true;
      clearTimeout(waitingTimer);
      hideQzOverlay();
    }).catch(function () {
      settled = true;
      clearTimeout(waitingTimer);
      showQzOverlay('install');
      setTimeout(qzConnect, 3000);
    });
  }

  function initQzGate() {
    if (!window.qz) return;
    // Modo sin firma de certificado (v1): evita el diálogo nativo de
    // "sitio no confiable" en cada request, a costa de mostrar un único
    // aviso de "Allow always" en la primera conexión por origen.
    qz.security.setCertificatePromise(function (resolve) { resolve(); });
    qz.security.setSignaturePromise(function () {
      return function (resolve) { resolve(); };
    });
    qz.websocket.setClosedCallbacks(function () {
      qzConnect();
    });
    showQzOverlay('checking');
    qzConnect();
  }

  /* ──────────────────────────────────────────────────────
     CONFIGURAR IMPRESORA por computadora (Fase 12 — QZ Tray)
  ────────────────────────────────────────────────────── */
  function initPrinterConfigModal() {
    var modalEl = $('printerConfigModal');
    var select = $('qzPrinterSelect');
    var saveBtn = $('printerConfigSaveBtn');
    if (!modalEl || !select) return;

    modalEl.addEventListener('show.bs.modal', function () {
      select.innerHTML = '<option value="">' + t('cargando', 'Cargando…') + '</option>';
      if (!window.qz || !qz.websocket.isActive()) {
        // Sin QZ se ofrecen las que el equipo reporto la ultima vez.
        var conocidas = (_puesto && _puesto.impresoras) || [];
        if (!conocidas.length) {
          select.innerHTML = '<option value="">' + t('qz_desconectado', 'QZ Tray no conectado') + '</option>';
          return;
        }
        select.innerHTML = '';
        var actual = impresoraDelPuesto();
        conocidas.forEach(function (name) {
          var opt = document.createElement('option');
          opt.value = name;
          opt.textContent = name;
          if (name === actual) { opt.selected = true; }
          select.appendChild(opt);
        });
        return;
      }
      qz.printers.find().then(function (list) {
        var names = Array.isArray(list) ? list : [list];
        var current = impresoraDelPuesto();
        if (names.length) { postPuesto('impresoras', { impresoras: names }).catch(function () {}); }
        select.innerHTML = '';
        names.forEach(function (name) {
          var opt = document.createElement('option');
          opt.value = name;
          opt.textContent = name;
          if (name === current) opt.selected = true;
          select.appendChild(opt);
        });
      }).catch(function () {
        select.innerHTML = '<option value="">' + t('error_listar_impresoras', 'Error al listar impresoras') + '</option>';
      });
    });

    if (saveBtn) {
      saveBtn.addEventListener('click', function () {
        if (select.value) {
          store(CLAVE_IMPRESORA, select.value);
          if (_puesto) { _puesto.qz_printer = select.value; }
          // Queda en el servidor: la proxima sesion en esta maquina, sea de quien
          // sea, encuentra la impresora ya elegida.
          postPuesto('impresora', { qz_printer: select.value })
            .then(function () {
              showToast(t('impresora_guardada', 'Impresora configurada para esta computadora'), 'fa-print');
            })
            .catch(function () {
              showToast(t('impresora_guardada_local', 'Impresora guardada solo en esta computadora'), 'fa-print');
            });
        }
        if (window.bootstrap) {
          var modal = window.bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
        }
      });
    }
  }

  /* ──────────────────────────────────────────────────────
     ABRIR CAJÓN con PIN de administrador (Fase 12 — QZ Tray)
  ────────────────────────────────────────────────────── */
  function initDrawerButton() {
    var modalEl = $('drawerPinModal');
    var input = $('drawerPinInput');
    var confirmBtn = $('drawerPinConfirmBtn');
    var errEl = $('drawerPinError');
    if (!modalEl || !input || !confirmBtn) return;

    modalEl.addEventListener('shown.bs.modal', function () {
      input.value = '';
      if (errEl) errEl.classList.add('d-none');
      input.focus();
    });

    function submitPin() {
      var pin = input.value.trim();
      if (!pin) return;
      var body = new URLSearchParams();
      body.set('pin', pin);
      if (window.CSRF_NAME) body.set(window.CSRF_NAME, window.CSRF_HASH);
      confirmBtn.disabled = true;
      fetch(window.base_url + 'posprint/verify_drawer_pin', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          confirmBtn.disabled = false;
          if (res.status === 1) {
            if (window.bootstrap) {
              var modal = window.bootstrap.Modal.getInstance(modalEl);
              if (modal) modal.hide();
            }
            var printerName = impresoraDelPuesto();
            if (window.qz && qz.websocket.isActive() && printerName) {
              var config = qz.configs.create(printerName);
              qz.print(config, [{ type: 'raw', format: 'command', flavor: 'base64', data: res.bytes }])
                .catch(function () { showAlert(t('pos_print_error', 'Error al imprimir')); });
            }
            showToast(t('cajon_abierto', 'Cajón abierto'), 'fa-unlock');
          } else {
            if (errEl) { errEl.textContent = res.msg || t('wrong_pin', 'PIN incorrecto'); errEl.classList.remove('d-none'); }
            input.value = '';
            input.focus();
          }
        })
        .catch(function () {
          confirmBtn.disabled = false;
          showAlert(t('ajax_request_failed', 'Error de conexión'));
        });
    }

    confirmBtn.addEventListener('click', submitPin);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); submitPin(); }
    });
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
      btn.title = on ? t('impresion_auto_on', 'Impresión automática: ON (click para desactivar)') : t('impresion_auto_off_title', 'Impresión automática: OFF (click para activar)');
    }

    btn.addEventListener('click', function () {
      var on = localStorage.getItem('pos_autoprint') === '1';
      localStorage.setItem('pos_autoprint', on ? '0' : '1');
      updateBtn();
      showToast(on ? t('impresion_auto_desactivada', 'Impresión automática desactivada') : t('impresion_auto_activada', 'Impresión automática activada'),
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

    // La lista sale de los atajos que quedaron activos: si se cambia uno en
    // Ajustes, la ayuda cambia con el, sin tocar este archivo.
    // Bootstrap sanea el HTML del popover y <kbd> no esta en su lista blanca:
    // las teclas desaparecian y solo quedaba la descripcion.
    function tecla(txt) {
      return '<span class="pos-kbd">' + escaparHtml(txt) + '</span>';
    }
    function fila(combo, etiqueta) {
      // "+" es a la vez separador y tecla valida: partirlo lo dejaria vacio.
      var partes = String(combo) === '+' ? ['+'] : String(combo).split('+');
      var teclas = partes.map(function (k) { return tecla(k.trim()); }).join('<i class="pos-kbd-mas">+</i>');
      return '<div class="pos-kbd-fila">' + teclas + '<span>' + escaparHtml(etiqueta) + '</span></div>';
    }

    var filas = (window._posAtajos || []).map(function (a) {
      return fila(a.texto, t(a.etiqueta, a.etiqueta));
    });
    filas.push(fila('ESC', t('kbd_cancelar_busqueda', 'Cancelar busqueda')));
    filas.push(fila('↑↓', t('kbd_navegar_lista', 'Navegar lista')));
    filas.push(fila('Enter', t('kbd_agregar_producto', 'Agregar producto')));
    filas.push(fila('+', t('kbd_foco_busqueda', 'Ir a la busqueda')));

    new window.bootstrap.Popover(btn, {
      html: true,
      trigger: 'click',
      placement: 'bottom',
      title: t('atajos_teclado', 'Atajos de teclado'),
      content: '<div class="pos-kbd-lista">' + filas.join('') + '</div>'
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
     CABYS: VERIFICACION ANTES DE COBRAR
     Un CABYS vacio o fuera del catalogo de Hacienda rechaza el comprobante
     entero. Se detiene al agregar el producto, no al emitir.
  ────────────────────────────────────────────────────── */
  var _cabysVerificados = {};

  function agregarConCabys(item) {
    var VR = window._posVR || {};
    var row = item && item.row;
    if (!VR.fe || !row || !(parseInt(row.id, 10) > 0)) {
      return Promise.resolve(add_invoice_item(item));
    }
    var codigo = String(row.cabys || '');
    if (_cabysVerificados[codigo]) {
      return Promise.resolve(add_invoice_item(item));
    }

    var consulta = /^\d{13}$/.test(codigo)
      ? fetch(VR.verificar + '?codigo=' + encodeURIComponent(codigo), {
          credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).catch(function () { return { verificado: false }; })
      : Promise.resolve({ verificado: true, existe: false, falta: true });

    return consulta.then(function (v) {
      // Sin respuesta de Hacienda no se frena la venta: el servidor vuelve a
      // intentarlo al cobrar.
      if (!v.verificado || v.existe) {
        if (v.existe) { _cabysVerificados[codigo] = true; }
        return add_invoice_item(item);
      }
      return pedirCabys(row, v.falta ? 'falta' : 'inexistente').then(function (nuevo) {
        if (!nuevo) { return false; }
        row.cabys = nuevo.cabys;
        row.tax = nuevo.tax;
        if (nuevo.id_tax) { row.id_tax = nuevo.id_tax; }
        _cabysVerificados[nuevo.cabys] = true;
        return add_invoice_item(item);
      });
    });
  }

  /** Pide el CABYS del producto, lo guarda en su ficha y devuelve lo asignado (o null). */
  function pedirCabys(row, motivo) {
    var VR = window._posVR || {};
    return new Promise(function (resolver) {
      var viejo = document.getElementById('vrCabysModal');
      if (viejo) { viejo.remove(); }

      var titulo = motivo === 'falta'
        ? t('vr_falta_cabys', 'Este producto no tiene CABYS')
        : t('vr_cabys_inexistente', 'El CABYS de este producto no existe en Hacienda');
      var el = document.createElement('div');
      el.className = 'modal fade';
      el.id = 'vrCabysModal';
      el.tabIndex = -1;
      el.innerHTML =
        '<div class="modal-dialog modal-lg"><div class="modal-content">' +
          '<div class="modal-header"><h5 class="modal-title"><i class="fa fa-exclamation-triangle text-warning"></i> <span class="vr-tit"></span></h5>' +
          '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
          '<div class="modal-body">' +
            '<p class="mb-2"><strong class="vr-prod"></strong></p>' +
            '<p class="text-muted small mb-3 vr-ayuda"></p>' +
            '<input type="search" class="form-control mb-2 vr-q" autocomplete="off">' +
            '<div class="text-danger small mb-2 vr-err" hidden></div>' +
            '<div class="vr-res" style="max-height:340px;overflow-y:auto"></div>' +
          '</div>' +
          '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"></button></div>' +
        '</div></div>';
      el.querySelector('.vr-tit').textContent = titulo;
      el.querySelector('.vr-prod').textContent = (row.name || '') + (row.cabys ? ' · ' + row.cabys : '');
      el.querySelector('.vr-ayuda').textContent = t('vr_cabys_ayuda', 'Hacienda rechazaría la factura. Busque el CABYS correcto: queda guardado en la ficha del producto y el IVA se toma del catálogo.');
      el.querySelector('.vr-q').placeholder = t('buscar_cabys_desc_min3', 'Buscar por descripción (mín. 3 caracteres)...');
      el.querySelector('.modal-footer .btn').textContent = t('vr_no_agregar', 'No agregar');
      document.body.appendChild(el);

      var q = el.querySelector('.vr-q');
      var res = el.querySelector('.vr-res');
      var err = el.querySelector('.vr-err');
      var resuelto = false;
      var temporizador;

      function buscar() {
        var texto = q.value.trim();
        if (texto.length < 3) { res.innerHTML = ''; return; }
        res.innerHTML = '<div class="cabys-result-empty"><i class="fa fa-spinner fa-spin"></i></div>';
        fetch(VR.buscar + '?q=' + encodeURIComponent(texto), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var items = Array.isArray(data) ? data : (data.data || data.cabys || []);
            res.innerHTML = '';
            if (!items.length) {
              res.innerHTML = '<div class="cabys-result-empty">' + t('sin_resultados', 'Sin resultados') + '</div>';
              return;
            }
            items.slice(0, 50).forEach(function (it) {
              var b = document.createElement('button');
              b.type = 'button';
              b.className = 'cabys-result-row';
              b.innerHTML = '<span class="cabys-result-txt"><span class="cabys-result-desc"></span><span class="cabys-result-code"></span></span><span class="cabys-result-tax"></span>';
              b.querySelector('.cabys-result-desc').textContent = it.descripcion || '';
              b.querySelector('.cabys-result-code').textContent = it.codigo || '';
              b.querySelector('.cabys-result-tax').textContent = (parseFloat(it.impuesto) || 0) + '%';
              b.addEventListener('click', function () { asignar(it.codigo, b); });
              res.appendChild(b);
            });
          })
          .catch(function () {
            res.innerHTML = '<div class="cabys-result-empty">' + t('hacienda_sin_respuesta', 'Hacienda no respondió. Intente de nuevo.') + '</div>';
          });
      }

      function asignar(codigo, boton) {
        boton.disabled = true;
        err.hidden = true;
        var body = new URLSearchParams();
        body.set('product_id', row.id);
        body.set('cabys', codigo);
        if (window.CSRF_NAME) { body.set(window.CSRF_NAME, window.CSRF_HASH); }
        fetch(VR.asignar, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
          .then(function (x) {
            if (!x.ok || x.j.error) { throw new Error(x.j.error || 'error'); }
            resuelto = true;
            resolver(x.j);
            window.bootstrap.Modal.getInstance(el).hide();
          })
          .catch(function (e) {
            boton.disabled = false;
            err.textContent = e.message;
            err.hidden = false;
          });
      }

      q.addEventListener('input', function () {
        clearTimeout(temporizador);
        temporizador = setTimeout(buscar, 350);
      });
      el.addEventListener('shown.bs.modal', function () {
        q.value = row.name || '';
        q.focus();
        buscar();
      });
      el.addEventListener('hidden.bs.modal', function () {
        if (!resuelto) { resolver(null); }
        el.remove();
      });
      window.bootstrap.Modal.getOrCreateInstance(el).show();
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

    var guardarEl = document.getElementById('ah-guardar');
    var rapidosWrap = document.getElementById('ah-rapidos-wrap');
    var rapidosEl = document.getElementById('ah-rapidos');

    // El IVA lo fija el CABYS elegido: una tarifa distinta a la del catalogo
    // es un comprobante mal declarado.
    function bloquearIva(bloquear) {
      if (ivaSwitch) ivaSwitch.disabled = bloquear;
      if (ivaSelect) ivaSelect.disabled = bloquear;
    }

    function cargarRapidos() {
      var VR = window._posVR || {};
      if (!rapidosEl || !VR.lista) return;
      fetch(VR.lista, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          var lista = (j && j.articulos) || [];
          rapidosEl.innerHTML = '';
          if (rapidosWrap) rapidosWrap.hidden = !lista.length;
          lista.forEach(function (a) {
            var chip = document.createElement('span');
            chip.className = 'ah-rapido';
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-outline-success btn-sm';
            b.textContent = a.nombre + ' · ' + a.impuesto + '%' + (a.precio ? ' · ' + formatMoney(a.precio) : '');
            b.title = a.cabys + ' — ' + (a.cabys_desc || '');
            b.addEventListener('click', function () {
              if (nameEl) nameEl.value = a.nombre;
              aplicarCabys(a.cabys, a.cabys_desc || '', a.impuesto);
              if (a.precio && priceEl) { priceEl.value = a.precio; priceManuallyEdited = true; }
              updatePreview();
              if (priceEl) { priceEl.focus(); priceEl.select(); }
            });
            chip.appendChild(b);
            if (VR.admin) {
              var x = document.createElement('button');
              x.type = 'button';
              x.className = 'btn btn-link btn-sm text-danger px-1';
              x.innerHTML = '&times;';
              x.title = t('delete', 'Eliminar');
              x.addEventListener('click', function () {
                if (!window.confirm(t('r_u_sure', '¿Está seguro?'))) return;
                var body = new URLSearchParams();
                if (window.CSRF_NAME) body.set(window.CSRF_NAME, window.CSRF_HASH);
                fetch(VR.borrar + '/' + a.id, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                  .then(cargarRapidos);
              });
              chip.appendChild(x);
            }
            rapidosEl.appendChild(chip);
          });
        })
        .catch(function () {});
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
      if (guardarEl) guardarEl.checked = false;
      bloquearIva(false);
      priceManuallyEdited = false;
      updatePreview();
      cargarRapidos();
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

    // CABYS: búsqueda en el sub-modal. La fila entera es clicable y muestra
    // código, nombre e impuesto — ese impuesto es el que se aplica al elegirla.
    function aplicarCabys(codigo, desc, tasa) {
      tasa = parseFloat(tasa) || 0;
      if (cabysEl)   cabysEl.value = codigo;
      if (cabysDesc) cabysDesc.value = desc;
      if (ivaSwitch) ivaSwitch.checked = tasa > 0;
      if (ivaSel) ivaSel.style.display = tasa > 0 ? '' : 'none';
      if (tasa > 0 && ivaSelect) {
        var opts = ivaSelect.querySelectorAll('option');
        for (var i = 0; i < opts.length; i++) {
          if (parseFloat(opts[i].dataset.tasa) === tasa) {
            ivaSelect.value = opts[i].value;
            break;
          }
        }
        if (ivaRate) ivaRate.textContent = tasa + '%';
      }
      bloquearIva(true);
      updatePreview();
      if (cabysModal && window.bootstrap) {
        var m = window.bootstrap.Modal.getInstance(cabysModal);
        if (m) m.hide();
      }
    }

    function doCabysSearch() {
      var q = cabysQ ? cabysQ.value.trim() : '';
      if (!q || !cabysResults) return;
      var icon = document.getElementById('ah-cabys-icon');
      if (icon) icon.className = 'fa fa-spinner fa-spin';
      cabysResults.innerHTML = '<div class="cabys-result-empty"><i class="fa fa-spinner fa-spin"></i> ' + t('buscando', 'Buscando...') + '</div>';
      fetch(window.base_url + 'hacienda_proxy/cabys?q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (icon) icon.className = 'fa fa-search';
          cabysResults.innerHTML = '';
          var items = data.data || data.cabys || data || [];
          if (!Array.isArray(items) || !items.length) {
            cabysResults.innerHTML = '<div class="cabys-result-empty">' + t('sin_resultados', 'No hay resultados que coincidan con la búsqueda.') + '</div>';
            return;
          }
          items.slice(0, 50).forEach(function (it) {
            var tasa   = parseFloat(it.impuesto) || 0;
            var codigo = it.codigo || it.Codigo || '';
            var desc   = it.descripcion || it.Descripcion || '';
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'cabys-result-row';
            row.innerHTML =
              '<span class="cabys-result-txt">' +
                '<span class="cabys-result-desc"></span>' +
                '<span class="cabys-result-code"></span>' +
              '</span>' +
              '<span class="cabys-result-tax">' + tasa + '%</span>';
            row.querySelector('.cabys-result-desc').textContent = desc;
            row.querySelector('.cabys-result-code').textContent = codigo;
            row.addEventListener('click', function () { aplicarCabys(codigo, desc, tasa); });
            cabysResults.appendChild(row);
          });
        })
        .catch(function () {
          if (icon) icon.className = 'fa fa-search';
          if (cabysResults) cabysResults.innerHTML = '<div class="cabys-result-empty">' + t('hacienda_sin_respuesta', 'Hacienda no respondió. Intente de nuevo.') + '</div>';
        });
    }

    if (cabysGoBtn) cabysGoBtn.addEventListener('click', doCabysSearch);
    if (cabysQ) {
      cabysQ.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') doCabysSearch();
      });
      var cabysTimer;
      cabysQ.addEventListener('input', function () {
        clearTimeout(cabysTimer);
        var q = cabysQ.value.trim();
        if (q.length < 3) {
          if (cabysResults) cabysResults.innerHTML = '<div class="cabys-result-empty">' + t('buscar_cabys_desc_min3', 'Buscar por descripción (mín. 3 caracteres)...') + '</div>';
          return;
        }
        cabysTimer = setTimeout(doCabysSearch, 300);
      });
    }
    if (cabysModal) {
      cabysModal.addEventListener('shown.bs.modal', function () {
        if (cabysQ) { cabysQ.value = ''; cabysQ.focus(); }
        if (cabysResults) cabysResults.innerHTML = '<div class="cabys-result-empty">' + t('buscar_cabys_desc_min3', 'Buscar por descripción (mín. 3 caracteres)...') + '</div>';
      });
    }

    // Confirmar agregar al carrito
    if (confirmBtn) {
      confirmBtn.addEventListener('click', function () {
        var name  = nameEl ? nameEl.value.trim() : '';
        var cabys = cabysEl ? cabysEl.value.trim() : '';
        var qty   = parseFloat(qtyEl ? qtyEl.value : 1) || 1;
        var price = parseFloat(priceEl ? priceEl.value : 0) || 0;

        if (!name) { nameEl && nameEl.focus(); showAlert(t('ingrese_nombre_producto', 'Ingrese el nombre del producto.')); return; }
        if (!cabys || !/^\d{13}$/.test(cabys)) { cabysEl && cabysEl.focus(); showAlert(t('ingrese_cabys_valido', 'Ingrese un código CABYS válido (13 dígitos).')); return; }
        if (!price || price <= 0) { priceEl && priceEl.focus(); showAlert(t('ingrese_precio_valido', 'Ingrese un precio válido.')); return; }

        var tasa  = getCurrentTasa();
        var idTax = 0;
        if (ivaSwitch && ivaSwitch.checked && ivaSelect) {
          idTax = parseInt(ivaSelect.value) || 0;
        }

        // Hacienda clasifica por el CABYS: las secciones 0 a 4 son bienes
        // y de la 5 en adelante servicios.
        var esServicio = parseInt(cabys.charAt(0), 10) >= 5;

        var uid = 'adhoc_' + Date.now();
        var item = {
          id: uid,
          item_id: uid,
          label: name + ' (' + cabys + ')',
          row: {
            id: 0,
            type: esServicio ? 'service' : 'standard',
            unit_of_measurement: esServicio ? 'Sp' : 'Unid',
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

        var VRg = window._posVR || {};
        if (guardarEl && guardarEl.checked && VRg.guardar) {
          var body = new URLSearchParams();
          body.set('nombre', name);
          body.set('cabys', cabys);
          body.set('precio', price);
          if (window.CSRF_NAME) body.set(window.CSRF_NAME, window.CSRF_HASH);
          fetch(VRg.guardar, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (j) { if (j && j.error) showAlert(j.error); })
            .catch(function () {});
        }

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
  /**
   * Cierre de venta visto desde el POS.
   *
   * Al cobrar, Pos.php vuelve al POS (la pantalla del comprobante queda para
   * consultarlo desde el listado de ventas) y deja en `_pos_venta_ok` el id de
   * la venta, si hubo efectivo y si la impresion automatica esta activa.
   * Aqui se limpia el carrito, se avisa y, si corresponde, se manda el tiquete
   * a la impresora termica y se abre el cajon.
   */
  /**
   * Vuelca al carrito la venta que manda el servidor al retomar una cuenta en
   * espera o al reeditar. Pisa el localStorage: lo guardado es de otra venta.
   */
  function precargarVenta() {
    var v = window._pos_precargada;
    if (!v || !v.items) { return; }
    window._pos_precargada = null;

    store('spositems', JSON.stringify(v.items));
    remove('spos_discount');
    remove('spos_tax');

    if (v.cliente && v.cliente !== getDefaultCustomerId() && (window._customers || {})[v.cliente]) {
      store('spos_customer', v.cliente);
    } else {
      remove('spos_customer');
    }

    var nota = $('spos_note');
    if (nota && v.nota) { nota.value = v.nota; }
  }

  function cerrarVentaEnPos() {
    var v = window._pos_venta_ok;

    // Con Ajustes > POS = "Recibo" el cobro pasa por la pantalla del
    // comprobante, que al volver deja esta marca en localStorage. Ahi el
    // tiquete ya se imprimio, asi que solo queda avisar y limpiar.
    if (!v) {
      var desdeRecibo;
      try { desdeRecibo = localStorage.getItem('nx_venta_ok'); } catch (e) { return; }
      if (!desdeRecibo) { return; }
      remove('nx_venta_ok');
      v = { id: -1, efectivo: false, autoprint: false };
    }

    if (!v.id) { return; }
    window._pos_venta_ok = null;

    // El carrito vive en localStorage, asi que sobrevive al redirect
    remove('spositems');
    remove('spos_tax');
    remove('spos_discount');
    remove('spos_customer');

    avisoVenta(t('sale_added', 'Venta agregada exitosamente'), 'fa-check-circle');

    if (v.autoprint) {
      imprimirYAbrirCajon(v);
    }
  }

  function avisoVenta(texto, icono) {
    var wrap = $('pos-toast-wrap');
    if (!wrap) { return; }
    var el = document.createElement('div');
    el.className = 'pos-toast in';
    el.innerHTML = '<i class="fa ' + (icono || 'fa-check-circle') + '"></i><span><strong>' + texto + '</strong></span>';
    wrap.appendChild(el);
    setTimeout(function () {
      el.classList.remove('in');
      el.classList.add('out');
      setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 220);
    }, 3000);
  }

  /**
   * Envia bytes ESC/POS por QZ Tray. Devuelve una promesa que se rechaza si
   * QZ no esta conectado o no hay impresora elegida.
   */
  function caracteresDelPuesto() {
    return (_puesto && _puesto.caracteres) || 42;
  }

  function enviarBytesQz(url) {
    url += (url.indexOf('?') < 0 ? '?' : '&') + 'cpl=' + caracteresDelPuesto();
    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || res.status !== 1) { throw new Error('print_bytes_failed'); }
        if (!(window.qz && qz.websocket.isActive())) { throw new Error('qz_not_connected'); }
        var impresora = impresoraDelPuesto();
        if (!impresora) { throw new Error('no_printer_configured'); }
        return qz.print(qz.configs.create(impresora), [
          { type: 'raw', format: 'command', flavor: 'base64', data: res.bytes }
        ]);
      });
  }

  /**
   * La venta se cierra apenas carga el POS, antes de que QZ Tray termine de
   * conectar y de que el servidor devuelva la impresora del puesto: imprimir en
   * ese instante falla siempre. Se espera a las dos cosas con un tope.
   */
  function esperarImpresion(tope) {
    var limite = Date.now() + tope;
    var qzListo = new Promise(function (listo, falla) {
      (function revisar() {
        if (window.qz && qz.websocket.isActive()) { return listo(); }
        if (Date.now() > limite) { return falla(new Error('qz_not_connected')); }
        setTimeout(revisar, 250);
      })();
    });
    // _puestoListo se asigna despues en init(): se lee cuando QZ ya conecto.
    return qzListo.then(function () { return _puestoListo; });
  }

  function imprimirYAbrirCajon(v) {
    esperarImpresion(15000)
      .then(function () { return enviarBytesQz(v.url_bytes + '/' + v.id + '/1'); })
      .then(function () {
        // El cajon solo se abre si la venta llevo efectivo: en tarjeta o
        // SINPE no hay vuelto que entregar.
        if (v.efectivo) { return enviarBytesQz(v.url_cajon); }
      })
      .catch(function (e) {
        avisoVenta('No se pudo imprimir el tiquete (' + (e && e.message ? e.message : 'error') + ')', 'fa-exclamation-circle');
      });
  }

  function init() {
    var S = window.Settings || {};
    // Leer variables del scope global (definidas en la vista PHP)
    cat_id = window._pos_cat_id || (S.default_category || 0);
    tcp = window._pos_tcp || 0;
    pro_limit = parseInt(S.pro_limit) || 20;
    protect_delete = parseInt(S.protect_delete) || 0;
    sid = window._pos_sid || 0;

    cerrarVentaEnPos();
    precargarVenta();

    initSearch();
    initProductClick();
    initCategoryNav();
    initCategoryFilter();
    initDeleteItem();
    initEditItem();
    initPriceToggle();
    initQuantityChange();
    initStepper();
    initPayment();
    initSubmit();
    initReset();
    initDescuentoTotal();
    initSuspend();
    initCustomerForm();
    initHaciendaLookup();
    initClienteRapido();
    initTecladoCobro();
    initCustomerSelect();
    initClock();
    initPaymentMethods();
    initQuickAmounts();
    initSinpePending();
    initPagoDividido();
    _puestoListo = initPuesto();
    initKeyboardShortcuts();
    initSidebarToggle();
    initModalFocusReturn();
    initPrintToggle();
    initKeyboardPopover();
    initAdHocProduct();
    initQzGate();
    initPrinterConfigModal();
    initDrawerButton();
    initCierreCaja();
    initCierreForzado();
    initAvisoServidor();
    initEnvioHacienda();

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
