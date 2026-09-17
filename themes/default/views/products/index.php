<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<!-- ═══ Encabezado de página ═══ -->
<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('products'); ?>
        <small><?= html_escape($store->name); ?> · <?= lang('inventario_general'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <?php if (!$this->session->userdata('has_store_id')) { ?>
        <div class="dropdown">
            <button class="nxt-btn nxt-btn-ghost" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l18 0"/><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/><path d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/></svg>
                <?= html_escape($store->name.' ('.$store->code.')'); ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6l6 -6"/></svg>
            </button>
            <ul class="dropdown-menu">
                <?php
                foreach ($stores as $st) {
                    if ($store->id != $st->id) {
                        echo "<li><a class='dropdown-item' href='".site_url('products/?store_id='.$st->id)."'>".html_escape($st->name.' ('.$st->code.')')."</a></li>";
                    }
                }
                ?>
            </ul>
        </div>
        <?php } ?>
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn" href="<?= site_url('products/add'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('nuevo_producto'); ?>
        </a>
    </div>
</div>

<!-- ═══ KPIs ═══ -->
<div class="nxt-kpis">
    <div class="nxt-kpi" style="--kpi-c:var(--nx-a1)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5"/><path d="M12 12l8 -4.5M12 12v9M12 12l-8 -4.5"/></svg> <?= lang('productos_activos'); ?></div>
        <div class="nxt-kpi-value" id="kpiTotal">—</div>
        <div class="nxt-kpi-sub" id="kpiTotalSub">&nbsp;</div>
    </div>
    <?php if ($Admin) { ?>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-emerald)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6l4 4l8-8"/><path d="M14 7h7v7"/></svg> <?= lang('valor_inventario'); ?></div>
        <div class="nxt-kpi-value" id="kpiValue">—</div>
        <div class="nxt-kpi-sub"><?= lang('a_precio_costo'); ?></div>
    </div>
    <?php } ?>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-amber)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.24 3.96l-8.13 14.05a2 2 0 0 0 1.73 3h16.32a2 2 0 0 0 1.73-3l-8.13-14.05a2 2 0 0 0-3.52 0z"/></svg> <?= lang('stock_bajo'); ?></div>
        <div class="nxt-kpi-value" id="kpiLow">—</div>
        <div class="nxt-kpi-sub"><?= lang('requieren_reorden'); ?></div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-err)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg> <?= lang('agotados'); ?></div>
        <div class="nxt-kpi-value" id="kpiOut">—</div>
        <div class="nxt-kpi-sub"><?= lang('sin_existencias'); ?></div>
    </div>
</div>

<!-- ═══ Tarjeta con tabla ═══ -->
<div class="nxt-card">

    <div class="nxt-toolbar">
        <div class="nxt-search">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7"/><path d="M21 21l-6-6"/></svg>
            <input type="search" id="nxtQ" placeholder="<?= lang('buscar_nombre_codigo'); ?>" autocomplete="off">
        </div>
        <div class="nxt-chips" id="nxtChips">
            <button class="nxt-chip active" data-cat="*"><?= lang('todas'); ?></button>
        </div>
        <span class="nxt-count" id="nxtCount"></span>
    </div>

    <div class="nxt-table-wrap">
        <table class="nxt-table" id="nxtTable" style="min-width:980px">
            <thead>
                <tr>
                    <th style="width:34%"><?= lang('product'); ?></th>
                    <th><?= lang('code'); ?></th>
                    <th><?= lang('category'); ?></th>
                    <th class="sortable" data-k="quantity"><?= lang('stock'); ?> <span class="arrow">↕</span></th>
                    <th class="num"><?= lang('tax'); ?></th>
                    <?php if ($Admin) { ?>
                    <th class="num sortable" data-k="cost"><?= lang('cost'); ?> <span class="arrow">↕</span></th>
                    <?php } ?>
                    <th class="num sortable" data-k="price"><?= lang('price'); ?> <span class="arrow">↕</span></th>
                    <?php if ($Admin) { ?>
                    <th class="num"><?= lang('margen'); ?></th>
                    <?php } ?>
                    <th><?= lang('Ubicación'); ?></th>
                    <th style="text-align:right"><?= lang('actions'); ?></th>
                </tr>
            </thead>
            <tbody id="nxtRows"></tbody>
        </table>
        <div class="nxt-loading" id="nxtLoading">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
            <?= lang('loading_data_from_server'); ?>
        </div>
        <div class="nxt-empty" id="nxtEmpty" style="display:none"><?= lang('sin_resultados'); ?></div>
    </div>

    <div class="nxt-foot">
        <span id="nxtFootInfo"></span>
        <div class="nxt-pages" id="nxtPages"></div>
    </div>
</div>

<!-- Modal para vista previa de imagen -->
<div class="modal fade" id="picModal" tabindex="-1" aria-labelledby="picModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="picModalLabel"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="product_image" src="" alt="" style="max-width:100%" />
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /* ── Config inyectada por PHP ── */
    var IS_ADMIN  = <?= $Admin ? 'true' : 'false'; ?>;
    var BASE_URL  = '<?= base_url(); ?>';
    var SITE_URL  = '<?= site_url(); ?>/';
    var AJAX_URL  = '<?= site_url('products/get_products/'.$store->id); ?>';
    var CSRF_NAME = '<?= $this->security->get_csrf_token_name(); ?>';
    var CSRF_HASH = '<?= $this->security->get_csrf_hash(); ?>';
    var L = {
        standard:   '<?= lang('standard'); ?>',
        combo:      '<?= lang('combo'); ?>',
        service:    '<?= lang('service'); ?>',
        ok:         'OK',
        low:        '<?= lang('stock_bajo_tag'); ?>',
        out:        '<?= lang('agotado'); ?>',
        de:         '<?= lang('de'); ?>',
        products:   '<?= lang('products'); ?>',
        showing:    '<?= lang('mostrando'); ?>',
        categories: '<?= lang('en_categorias'); ?>',
        view:       '<?= lang('view'); ?>',
        etiqueta:   '<?= lang('etiquetas_codigos'); ?>',
        edit:       '<?= lang('edit_product'); ?>',
        del:        '<?= lang('delete_product'); ?>',
        delConfirm: '<?= lang('alert_x_product'); ?>'
    };

    /* ── Formateo de números según configuración del sistema ── */
    var S = window._appSettings || {};
    function fmtNum(x, d) {
        var n = parseFloat(x); if (isNaN(n)) n = 0;
        if (d === undefined || d === null) d = parseInt(S.decimals || 2);
        var ts = (S.thousands_sep == 0 ? ' ' : (S.thousands_sep || ','));
        var ds = S.decimals_sep || '.';
        var parts = Math.abs(n).toFixed(d).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ts);
        return (n < 0 ? '-' : '') + parts[0] + (parts[1] ? ds + parts[1] : '');
    }
    function fmtMoney(x) {
        var sym = S.symbol || '';
        var v = fmtNum(x, parseInt(S.decimals || 2));
        if (S.display_symbol == 2) return v + sym;
        return sym + v;
    }
    function fmtQty(x) { return fmtNum(x, parseInt(S.qty_decimals || 0)); }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    /* ── Paleta para categorías (asignación estable por orden alfabético) ── */
    var PALETTE = [
        ['#0ea5e9','#38bdf8'], ['#9333ea','#c084fc'], ['#d97706','#fbbf24'],
        ['#6366f1','#a5b4fc'], ['#ea580c','#fb923c'], ['#059669','#34d399'],
        ['#db2777','#f472b6'], ['#0d9488','#2dd4bf'], ['#dc2626','#f87171'],
        ['#4f46e5','#818cf8']
    ];
    var catColor = {};

    function initials(s) {
        var w = String(s || '').trim().split(/\s+/).filter(function (x) { return /^[A-Za-zÁÉÍÓÚÑáéíóúñ0-9]/.test(x); });
        return (w.slice(0, 2).map(function (x) { return x[0]; }).join('') || String(s || '??').slice(0, 2)).toUpperCase();
    }

    function ptype(x) { return L[x] || x || ''; }

    function stockState(p) {
        if (p.type === 'service') return ['', '', 0];
        var qty = p.quantity, min = p.alert_quantity;
        if (qty <= 0) return ['s-out', L.out, 0];
        if (min > 0 && qty <= min) return ['s-low', L.low, Math.max(8, qty / (min * 3) * 100)];
        var pct = min > 0 ? Math.min(100, qty / (min * 3) * 100) : 100;
        return ['s-ok', L.ok, pct];
    }

    /* ── Estado ── */
    var DATA = [], activeCat = '*', query = '', sortK = null, sortDir = 1,
        page = 1, PER_PAGE = 25;

    /* ── Carga de datos (protocolo DataTables server-side, sin paginar) ── */
    function load() {
        var body = new URLSearchParams();
        body.append(CSRF_NAME, CSRF_HASH);
        body.append('draw', '1');
        body.append('start', '0');
        body.append('length', '-1');
        body.append('search[value]', '');
        body.append('search[regex]', 'false');
        body.append('columns[0][data]', 'pid');
        body.append('columns[0][name]', '');
        body.append('columns[0][searchable]', 'false');
        body.append('columns[0][orderable]', 'false');
        body.append('columns[0][search][value]', '');
        body.append('columns[0][search][regex]', 'false');

        fetch(AJAX_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body.toString(),
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            DATA = (json.data || []).map(function (r) {
                return {
                    pid: r.pid,
                    image: r.image && r.image !== 'no_image.png' ? r.image : null,
                    code: r.code || '',
                    pname: r.pname || '',
                    type: r.type || 'standard',
                    cname: r.cname || '—',
                    quantity: r.quantity == null ? 0 : parseFloat(r.quantity),
                    alert_quantity: parseFloat(r.alert_quantity) || 0,
                    tax: r.tax,
                    cost: IS_ADMIN ? (parseFloat(r.cost) || 0) : null,
                    price: parseFloat(r.price) || 0,
                    offer_price: parseFloat(r.offer_price) || 0,
                    ubicacion: r.ubicacion || ''
                };
            });
            buildChips();
            renderKPIs();
            document.getElementById('nxtLoading').style.display = 'none';
            render();
        })
        .catch(function (e) {
            document.getElementById('nxtLoading').innerHTML = '<span style="color:var(--nx-err)">Error: ' + esc(e.message) + '</span>';
        });
    }

    /* ── Chips de categorías (dinámicos según datos) ── */
    function buildChips() {
        var cats = {};
        DATA.forEach(function (p) { cats[p.cname] = true; });
        var names = Object.keys(cats).sort(function (a, b) { return a.localeCompare(b); });
        names.forEach(function (c, i) { catColor[c] = PALETTE[i % PALETTE.length]; });
        var box = document.getElementById('nxtChips');
        // ?cat=<nombre> deja el filtro puesto: es como entra quien viene de la
        // ficha de una categoría.
        var pedida = new URLSearchParams(location.search).get('cat');
        names.forEach(function (c) {
            var b = document.createElement('button');
            b.className = 'nxt-chip' + (c === pedida ? ' active' : '');
            b.dataset.cat = c;
            b.textContent = c;
            box.appendChild(b);
        });
        if (names.indexOf(pedida) !== -1) {
            activeCat = pedida;
            box.querySelector('.nxt-chip[data-cat="*"]').classList.remove('active');
        }
    }

    /* ── KPIs ── */
    function renderKPIs() {
        var low = 0, out = 0, value = 0, cats = {};
        DATA.forEach(function (p) {
            cats[p.cname] = true;
            if (p.type !== 'service') {
                if (p.quantity <= 0) out++;
                else if (p.alert_quantity > 0 && p.quantity <= p.alert_quantity) low++;
                if (IS_ADMIN) value += (p.cost || 0) * Math.max(0, p.quantity);
            }
        });
        document.getElementById('kpiTotal').textContent = DATA.length;
        document.getElementById('kpiTotalSub').textContent = L.categories.replace('%d', Object.keys(cats).length);
        document.getElementById('kpiLow').textContent = low;
        document.getElementById('kpiOut').textContent = out;
        var kv = document.getElementById('kpiValue');
        if (kv) kv.textContent = fmtMoney(value);
    }

    /* ── Filtro + orden ── */
    function filtered() {
        var q = query;
        var list = DATA.filter(function (p) {
            return (activeCat === '*' || p.cname === activeCat) &&
                   (!q || p.pname.toLowerCase().indexOf(q) !== -1 || p.code.toLowerCase().indexOf(q) !== -1);
        });
        if (sortK) {
            list = list.slice().sort(function (a, b) { return ((a[sortK] || 0) - (b[sortK] || 0)) * sortDir; });
        }
        return list;
    }

    /* ── Render de filas + paginación ── */
    function render() {
        var list = filtered();
        var pages = Math.max(1, Math.ceil(list.length / PER_PAGE));
        if (page > pages) page = pages;
        var slice = list.slice((page - 1) * PER_PAGE, page * PER_PAGE);

        var html = slice.map(function (p) {
            var st = stockState(p);
            var cc = catColor[p.cname] || PALETTE[0];
            var avatar = p.image
                ? '<div class="nxt-avatar"><img src="' + BASE_URL + 'uploads/thumbs/' + esc(p.image) + '" alt="" loading="lazy" class="js-zoom" data-full="' + BASE_URL + 'uploads/' + esc(p.image) + '" data-code="' + esc(p.code) + '"></div>'
                : '<div class="nxt-avatar" style="--av1:' + cc[0] + ';--av2:' + cc[1] + '">' + esc(initials(p.pname)) + '</div>';

            var stockCell;
            if (p.type === 'service') {
                stockCell = '<span class="nxt-dim-mono">—</span>';
            } else {
                stockCell = '<div class="nxt-stock ' + st[0] + '">' +
                    '<div class="nxt-stock-top"><span class="nxt-stock-n">' + fmtQty(p.quantity) + '</span>' +
                    '<span class="nxt-stock-state">' + st[1] + '</span></div>' +
                    '<div class="nxt-bar"><i style="width:' + st[2] + '%"></i></div></div>';
            }

            var priceCell = p.offer_price > 0
                ? '<span class="nxt-old">' + fmtMoney(p.price) + '</span><span class="nxt-offer">' + fmtMoney(p.offer_price) + '</span>'
                : '<span class="nxt-price">' + fmtMoney(p.price) + '</span>';

            var costCell = '', marginCell = '';
            if (IS_ADMIN) {
                costCell = '<td class="num"><span class="nxt-cost">' + fmtMoney(p.cost) + '</span></td>';
                var m = p.price > 0 ? Math.round((p.price - p.cost) / p.price * 100) : 0;
                var mc = m >= 30 ? '' : (m >= 15 ? 'mid' : 'low');
                marginCell = '<td class="num"><span class="nxt-margin ' + mc + '">' + m + '%</span></td>';
            }

            var u = SITE_URL + 'products/';
            var actions =
                '<a class="nxt-icon-btn js-ajax-modal" href="' + u + 'view/' + p.pid + '" title="' + esc(L.view) + '">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6s-6.6-2-9-6c2.4-4 5.4-6 9-6s6.6 2 9 6"/></svg></a>' +
                '<a class="nxt-icon-btn" href="' + u + 'etiquetas?id=' + p.pid + '" title="' + esc(L.etiqueta) + '">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7v-1a2 2 0 0 1 2 -2h2"/><path d="M4 17v1a2 2 0 0 0 2 2h2"/><path d="M16 4h2a2 2 0 0 1 2 2v1"/><path d="M16 20h2a2 2 0 0 0 2 -2v-1"/><path d="M5 11h1v2h-1z"/><path d="M10 11l0 2"/><path d="M14 11h1v2h-1z"/><path d="M19 11l0 2"/></svg></a>' +
                '<a class="nxt-icon-btn warn" href="' + u + 'edit/' + p.pid + '" title="' + esc(L.edit) + '">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h-1a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97l-8.415 8.385v3h3z"/></svg></a>' +
                '<a class="nxt-icon-btn danger" href="' + u + 'delete/' + p.pid + '" data-confirm="' + esc(L.delConfirm) + '" title="' + esc(L.del) + '">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12"/><path d="M9 7v-3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/></svg></a>';

            return '<tr>' +
                '<td><div class="nxt-ent">' + avatar +
                    '<div><div class="nxt-ent-name">' + esc(p.pname) + '</div>' +
                    '<div class="nxt-ent-meta">' + esc(ptype(p.type)) + '</div></div></div></td>' +
                '<td><span class="nxt-code">' + esc(p.code) + '</span></td>' +
                '<td><span class="nxt-cat" style="--cat-c:' + cc[1] + '">' + esc(p.cname) + '</span></td>' +
                '<td>' + stockCell + '</td>' +
                '<td class="num"><span class="nxt-dim-mono">' + (p.tax != null && p.tax !== '' ? esc(parseFloat(p.tax)) + '%' : '—') + '</span></td>' +
                costCell +
                '<td class="num">' + priceCell + '</td>' +
                marginCell +
                '<td><span class="nxt-ent-meta">' + esc(p.ubicacion || '—') + '</span></td>' +
                '<td><div class="nxt-actions">' + actions + '</div></td>' +
            '</tr>';
        }).join('');

        document.getElementById('nxtRows').innerHTML = html;
        document.getElementById('nxtEmpty').style.display = list.length ? 'none' : 'block';
        document.getElementById('nxtCount').textContent = list.length + ' ' + L.de + ' ' + DATA.length + ' ' + L.products.toLowerCase();
        document.getElementById('nxtFootInfo').textContent =
            L.showing + ' ' + (list.length ? ((page - 1) * PER_PAGE + 1) : 0) + '–' + Math.min(page * PER_PAGE, list.length) + ' ' + L.de + ' ' + list.length;

        renderPager(pages);
    }

    function renderPager(pages) {
        var box = document.getElementById('nxtPages');
        box.innerHTML = '';
        function btn(label, target, opts) {
            opts = opts || {};
            var b = document.createElement('button');
            b.className = 'nxt-page-btn' + (opts.active ? ' active' : '');
            b.innerHTML = label;
            if (opts.disabled) b.disabled = true;
            else b.addEventListener('click', function () { page = target; render(); });
            box.appendChild(b);
        }
        btn('‹', page - 1, { disabled: page <= 1 });
        // ventana de máx. 7 páginas alrededor de la actual
        var from = Math.max(1, page - 3), to = Math.min(pages, from + 6);
        from = Math.max(1, to - 6);
        for (var i = from; i <= to; i++) btn(i, i, { active: i === page });
        btn('›', page + 1, { disabled: page >= pages });
    }

    /* ── Eventos ── */
    document.getElementById('nxtQ').addEventListener('input', function () {
        query = this.value.toLowerCase().trim(); page = 1; render();
    });

    document.getElementById('nxtChips').addEventListener('click', function (e) {
        var b = e.target.closest('.nxt-chip'); if (!b) return;
        this.querySelectorAll('.nxt-chip').forEach(function (c) { c.classList.remove('active'); });
        b.classList.add('active');
        activeCat = b.dataset.cat; page = 1; render();
    });

    document.querySelectorAll('#nxtTable th.sortable').forEach(function (th) {
        th.addEventListener('click', function () {
            var k = th.dataset.k;
            sortDir = (sortK === k) ? -sortDir : 1; sortK = k;
            document.querySelectorAll('#nxtTable th.sortable .arrow').forEach(function (a) { a.textContent = '↕'; });
            th.querySelector('.arrow').textContent = sortDir === 1 ? '↑' : '↓';
            page = 1; render();
        });
    });

    /* Zoom de imagen de producto */
    document.getElementById('nxtRows').addEventListener('click', function (e) {
        var img = e.target.closest('.js-zoom');
        if (!img) return;
        document.getElementById('picModalLabel').textContent = img.dataset.code;
        document.getElementById('product_image').src = img.dataset.full;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('picModal')).show();
    });

    /* Modales AJAX (ver producto, código de barras, etiqueta) */
    document.getElementById('nxtRows').addEventListener('click', function (e) {
        var a = e.target.closest('.js-ajax-modal');
        if (!a) return;
        e.preventDefault();
        fetch(a.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (htm) {
                var modal = document.getElementById('myModal');
                modal.innerHTML = htm;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
    });

    /* Exportar CSV (respeta filtros activos) */
    document.getElementById('nxtExport').addEventListener('click', function () {
        var rows = filtered();
        var head = ['<?= lang('code'); ?>', '<?= lang('name'); ?>', '<?= lang('type'); ?>', '<?= lang('category'); ?>', '<?= lang('quantity'); ?>', '<?= lang('tax'); ?>'];
        if (IS_ADMIN) head.push('<?= lang('cost'); ?>');
        head.push('<?= lang('price'); ?>', '<?= lang('offer_price'); ?>', '<?= lang('Ubicación'); ?>');
        var csv = [head.join(';')].concat(rows.map(function (p) {
            var r = [p.code, p.pname, ptype(p.type), p.cname, p.quantity, p.tax];
            if (IS_ADMIN) r.push(p.cost);
            r.push(p.price, p.offer_price, p.ubicacion);
            return r.map(function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; }).join(';');
        })).join('\r\n');
        var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'productos_<?= $store->code; ?>_' + new Date().toISOString().slice(0, 10) + '.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    });

    load();
});
</script>
