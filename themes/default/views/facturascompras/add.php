<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Factura electrónica de compra: el proveedor es el emisor y la tienda el
 * receptor. Los montos que se ven acá son solo para el cajero; los que viajan
 * en el comprobante los calcula `Facturascompras::guardar_fec()`.
 */
$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_buscar = '<circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/>';
$ico_mas    = '<path d="M12 5l0 14"/><path d="M5 12l14 0"/>';

$unidades = array(
    'Unid' => lang('unidad'), 'Sp' => lang('servicios_profesionales'), 'kg' => lang('kilogramo'),
    'm' => lang('metro'), 'm²' => lang('metro_cuadrado'), 'm³' => lang('metro_cubico'),
    'h' => lang('hora'), 'd' => lang('dia'), 'L' => lang('litro'), 't' => lang('tonelada'),
    'Gal' => lang('galon'),
);
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('fec'); ?>
        <small><?= lang('fec_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('facturascompras'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('fec'); ?>
        </a>
    </div>
</div>

<div class="nxf-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err"><div><?= $error; ?></div></div>
    <?php } ?>
    <div class="nxf-note" id="fecAviso" hidden></div>

    <!-- ── Paso 1: el proveedor ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title">
                <?= lang('supplier'); ?>
                <small><?= lang('fec_proveedor_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-8">
                    <label class="nxf-label" for="proveedor"><?= lang('supplier'); ?> <span class="req">*</span></label>
                    <select id="proveedor" class="nxf-select" required>
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ((array) $suppliers as $sp) { ?>
                            <option value="<?= $sp->id; ?>"
                                    data-cf1="<?= html_escape($sp->cf1); ?>"
                                    data-cf2="<?= html_escape($sp->cf2); ?>"
                                    data-provincia="<?= html_escape($sp->codigo_provincia); ?>"
                                    data-email="<?= html_escape($sp->email); ?>"
                                    data-plazo="<?= (int) (isset($sp->plazo_pago_dias) ? $sp->plazo_pago_dias : 0); ?>">
                                <?= html_escape($sp->name . ' — ' . $sp->cf2); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="nxf-hint" id="hintProveedor"></div>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label">&nbsp;</label>
                    <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('suppliers/add'); ?>" target="_blank" rel="noopener">
                        <?= $icono($ico_mas); ?> <?= lang('add_supplier'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 2: los artículos ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title">
                <?= lang('order_items'); ?>
                <small><?= lang('fec_lineas_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-8">
                    <label class="nxf-label" for="fecBuscar"><?= $icono($ico_buscar); ?> <?= lang('search_product_by_name_code'); ?></label>
                    <input type="text" id="fecBuscar" class="nxf-input" autocomplete="off">
                    <div id="fecSugerencias" class="fec-lista" style="display:none;"></div>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label">&nbsp;</label>
                    <button type="button" class="nxf-btn nxf-btn-ghost" id="fecManual">
                        <?= $icono($ico_mas); ?> <?= lang('linea_manual'); ?>
                    </button>
                </div>
            </div>

            <div class="fec-tabla-wrap">
                <table class="fec-tabla" id="fecTabla">
                    <thead>
                        <tr>
                            <th><?= lang('product'); ?></th>
                            <th><?= lang('unidad'); ?></th>
                            <th class="num"><?= lang('quantity'); ?></th>
                            <th class="num"><?= lang('unit_price'); ?></th>
                            <th class="num"><?= lang('descuento'); ?></th>
                            <th><?= lang('impuesto'); ?></th>
                            <th class="num"><?= lang('subtotal'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="fecCuerpo"></tbody>
                    <tfoot>
                        <tr><th colspan="6" class="num"><?= lang('total_sin_impuesto'); ?></th><th class="num" id="fecNeto">0.00</th><th></th></tr>
                        <tr><th colspan="6" class="num"><?= lang('impuesto'); ?></th><th class="num" id="fecImpuesto">0.00</th><th></th></tr>
                        <tr><th colspan="6" class="num"><?= lang('total'); ?></th><th class="num" id="fecTotal">0.00</th><th></th></tr>
                    </tfoot>
                </table>
                <div id="fecVacio" class="fec-vacio"><?= lang('add_product_by_searching_above_field'); ?></div>
            </div>
        </div>
    </div>

    <!-- ── Paso 3: pago y exoneración ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">3</span>
            <div class="nxf-card-title"><?= lang('fec_paso_pago'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="payment_status"><?= lang('payment_status'); ?></label>
                    <select id="payment_status" class="nxf-select">
                        <option value="paid"><?= lang('paid'); ?></option>
                        <option value="partial"><?= lang('partial'); ?></option>
                        <option value="due"><?= lang('due'); ?></option>
                    </select>
                </div>
                <div class="nxf-field sp-4" id="wrapPagoPor">
                    <label class="nxf-label" for="paid_by"><?= lang('paying_by'); ?></label>
                    <select id="paid_by" class="nxf-select">
                        <option value="cash"><?= lang('cash'); ?></option>
                        <option value="CC"><?= lang('tarjeta'); ?></option>
                        <option value="Cheque"><?= lang('cheque'); ?></option>
                        <option value="deposit"><?= lang('transferencia'); ?></option>
                        <option value="sinpe">SINPE Móvil</option>
                    </select>
                </div>
                <div class="nxf-field sp-4" id="wrapPlazo" hidden>
                    <label class="nxf-label" for="plazo"><?= lang('credit_time'); ?></label>
                    <select id="plazo" class="nxf-select">
                        <?php foreach (array(8, 15, 30, 45, 60, 90) as $d) { ?>
                            <option value="<?= $d; ?>" <?= $d === 30 ? 'selected' : ''; ?>><?= $d; ?> <?= lang('dias'); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="nxf-field sp-12">
                    <label class="nxf-label">
                        <input type="checkbox" id="usaExoneracion"> <?= lang('exoneracion'); ?>
                    </label>
                </div>
            </div>

            <div class="nxf-grid" id="bloqueExoneracion" hidden>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="exo_tipo"><?= lang('tipo_documento'); ?></label>
                    <input type="text" id="exo_tipo" class="nxf-input mono" maxlength="2">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="exo_documento"><?= lang('numero_documento'); ?></label>
                    <input type="text" id="exo_documento" class="nxf-input mono" maxlength="40">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="exo_institucion"><?= lang('nombre_institucion'); ?></label>
                    <input type="text" id="exo_institucion" class="nxf-input" maxlength="160">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="exo_fecha"><?= lang('fecha_emision'); ?></label>
                    <input type="date" id="exo_fecha" class="nxf-input">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="exo_porcentaje"><?= lang('porcentaje_exoneracion'); ?></label>
                    <input type="number" step="any" min="0" max="100" id="exo_porcentaje" class="nxf-input mono" value="0">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="exo_monto"><?= lang('monto_exoneracion'); ?></label>
                    <input type="number" step="any" min="0" id="exo_monto" class="nxf-input mono" value="0" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('facturascompras'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="button" class="nxf-btn" id="fecGuardar" disabled>
            <?= $icono($ico_check); ?> <?= lang('emitir'); ?>
        </button>
    </div>
</div>

<style>
    .fec-lista{margin-top:8px;border:1px solid var(--nxf-border,#2a3444);border-radius:10px;overflow:hidden;max-height:300px;overflow-y:auto}
    .fec-op{padding:9px 12px;cursor:pointer;font-size:13px;display:flex;justify-content:space-between;gap:12px}
    .fec-op:hover,.fec-op.marcada{background:rgba(56,189,248,.12)}
    .fec-op small{opacity:.65}
    .fec-tabla-wrap{margin-top:16px;overflow-x:auto}
    .fec-tabla{width:100%;border-collapse:collapse;font-size:13px;min-width:900px}
    .fec-tabla th,.fec-tabla td{padding:8px 10px;border-bottom:1px solid var(--nxf-border,#2a3444);text-align:left}
    .fec-tabla th.num,.fec-tabla td.num{text-align:right}
    .fec-tabla input,.fec-tabla select{width:110px;padding:6px 8px;border-radius:7px;border:1px solid var(--nxf-border,#2a3444);
        background:var(--nxf-surface-2,rgba(148,163,184,.06));color:inherit}
    .fec-tabla td.num input{text-align:right}
    .fec-tabla .nombre input{width:210px}
    .fec-tabla .quitar{background:none;border:0;color:#f87171;cursor:pointer;font-size:16px;line-height:1}
    .fec-vacio{padding:22px;text-align:center;opacity:.6;font-size:13px}
</style>

<script>
(function () {
    'use strict';

    var URL_BUSCAR  = '<?= site_url('products/suggestions'); ?>';
    var URL_GUARDAR = '<?= site_url('facturascompras/guardar_fec'); ?>';
    var CSRF_NOMBRE = '<?= $this->security->get_csrf_token_name(); ?>';
    var CSRF_HASH   = '<?= $this->security->get_csrf_hash(); ?>';
    var TOKEN_POST  = '<?= md5(date('Y-m-d H:i:s') . mt_rand()); ?>';
    var IMPUESTOS   = <?= json_encode(array_map(function ($i) {
                            return array('id' => $i->id_impuesto, 'tasa' => (float) $i->tasa_impuesto,
                                         'desc' => $i->descripcion_impuesto);
                        }, (array) $impuesto)); ?>;
    var DECIMALES   = <?= (int) ($Settings->decimals ?? 2); ?>;

    var T = {
        sinProveedor: <?= json_encode(lang('fec_sin_proveedor')); ?>,
        incompleto:   <?= json_encode(lang('fec_proveedor_incompleto')); ?>,
        sinLineas:    <?= json_encode(lang('fec_sin_lineas')); ?>,
        errorRed:     <?= json_encode(lang('inv_error_red')); ?>,
        emitida:      <?= json_encode(lang('fec_emitida')); ?>
    };

    var UNIDADES = <?= json_encode($unidades); ?>;

    var lineas = [];
    var sugerencias = [];
    var marcada = -1;
    var temporizador = null;

    var $ = function (id) { return document.getElementById(id); };
    var $cuerpo = $('fecCuerpo'), $sug = $('fecSugerencias'), $buscar = $('fecBuscar');

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }
    function num(v) { var n = parseFloat(String(v).replace(',', '.')); return isNaN(n) ? 0 : n; }
    function fmt(n) { return num(n).toFixed(DECIMALES); }

    function tasaDe(id) {
        for (var i = 0; i < IMPUESTOS.length; i++) {
            if (String(IMPUESTOS[i].id) === String(id)) { return IMPUESTOS[i].tasa; }
        }
        return 0;
    }

    // El precio se digita con impuesto incluido, igual que en la factura del
    // proveedor: el neto se despeja, no se suma encima.
    function neto(l) {
        var t = tasaDe(l.id_tax);
        return t > 0 ? l.unit_price - (l.unit_price * t) / (100 + t) : l.unit_price;
    }
    function subtotal(l) { return (neto(l) * l.quantity) - l.discount; }
    function impuesto(l) { return subtotal(l) * (tasaDe(l.id_tax) / 100); }

    function opcionesImpuesto(id) {
        return IMPUESTOS.map(function (i) {
            return '<option value="' + i.id + '"' + (String(i.id) === String(id) ? ' selected' : '') + '>'
                 + esc(i.tasa) + '%</option>';
        }).join('');
    }
    function opcionesUnidad(u) {
        return Object.keys(UNIDADES).map(function (k) {
            return '<option value="' + esc(k) + '"' + (k === u ? ' selected' : '') + '>' + esc(UNIDADES[k]) + '</option>';
        }).join('');
    }

    function pintar() {
        $cuerpo.innerHTML = '';
        var n = 0, imp = 0;

        lineas.forEach(function (l, i) {
            n += neto(l) * l.quantity - l.discount;
            imp += impuesto(l);

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="nombre"><input type="text" data-i="' + i + '" data-campo="name" value="' + esc(l.name) + '">'
              + '<br><small>' + esc(l.code) + '</small></td>'
              + '<td><select data-i="' + i + '" data-campo="unit">' + opcionesUnidad(l.unit) + '</select></td>'
              + '<td class="num"><input type="number" step="any" min="0" data-i="' + i + '" data-campo="quantity" value="' + esc(l.quantity) + '"></td>'
              + '<td class="num"><input type="number" step="any" min="0" data-i="' + i + '" data-campo="unit_price" value="' + esc(l.unit_price) + '"></td>'
              + '<td class="num"><input type="number" step="any" min="0" data-i="' + i + '" data-campo="discount" value="' + esc(l.discount) + '"></td>'
              + '<td><select data-i="' + i + '" data-campo="id_tax">' + opcionesImpuesto(l.id_tax) + '</select></td>'
              + '<td class="num">' + fmt(subtotal(l) + impuesto(l)) + '</td>'
              + '<td><button type="button" class="quitar" data-quitar="' + i + '" aria-label="x">&times;</button></td>';
            $cuerpo.appendChild(tr);
        });

        $('fecNeto').textContent = fmt(n);
        $('fecImpuesto').textContent = fmt(imp);
        $('fecTotal').textContent = fmt(n + imp);
        $('fecVacio').style.display = lineas.length ? 'none' : '';
        $('fecGuardar').disabled = (lineas.length === 0 || !$('proveedor').value);

        // La exoneración se calcula sobre el impuesto, no sobre el total.
        var pct = num($('exo_porcentaje').value);
        $('exo_monto').value = $('usaExoneracion').checked ? (imp * pct / 100).toFixed(5) : 0;
    }

    function agregar(p) {
        lineas.push({
            product_id: p ? p.id : 0,
            code: p ? p.code : '',
            name: p ? p.name : '',
            type: p && p.type === 'service' ? 'service' : 'standard',
            unit: p && p.unit ? p.unit : 'Unid',
            quantity: 1,
            unit_price: p ? p.price : 0,
            discount: 0,
            id_tax: p && p.id_tax ? p.id_tax : (IMPUESTOS.length ? IMPUESTOS[0].id : 0)
        });
        pintar();
    }

    function pintarSugerencias() {
        if (!sugerencias.length) { $sug.style.display = 'none'; return; }
        $sug.innerHTML = sugerencias.map(function (p, i) {
            return '<div class="fec-op' + (i === marcada ? ' marcada' : '') + '" data-sug="' + i + '">'
                 + '<span>' + esc(p.name) + '</span><small>' + esc(p.code) + '</small></div>';
        }).join('');
        $sug.style.display = '';
    }

    $buscar.addEventListener('input', function () {
        var q = $buscar.value.trim();
        clearTimeout(temporizador);
        if (q.length < 2) { sugerencias = []; pintarSugerencias(); return; }
        temporizador = setTimeout(function () {
            fetch(URL_BUSCAR + '?term=' + encodeURIComponent(q), {
                credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (lista) {
                    sugerencias = (Array.isArray(lista) ? lista : []).filter(function (x) { return x.item_id; })
                        .map(function (x) {
                            return { id: x.row.id, code: x.row.code, name: x.row.name, price: num(x.row.price),
                                     type: x.row.type, unit: x.row.unit_of_measurement, id_tax: x.row.id_tax };
                        });
                    marcada = sugerencias.length ? 0 : -1;
                    pintarSugerencias();
                })
                .catch(function () {});
        }, 220);
    });

    $buscar.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!sugerencias.length) { return; }
            marcada = (marcada + (e.key === 'ArrowDown' ? 1 : -1) + sugerencias.length) % sugerencias.length;
            pintarSugerencias();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (marcada >= 0 && sugerencias[marcada]) {
                agregar(sugerencias[marcada]);
                sugerencias = []; marcada = -1; pintarSugerencias();
                $buscar.value = '';
            }
        } else if (e.key === 'Escape') {
            sugerencias = []; marcada = -1; pintarSugerencias();
        }
    });

    $sug.addEventListener('click', function (e) {
        var op = e.target.closest('[data-sug]');
        if (!op) { return; }
        agregar(sugerencias[parseInt(op.dataset.sug, 10)]);
        sugerencias = []; marcada = -1; pintarSugerencias();
        $buscar.value = '';
        $buscar.focus();
    });

    $('fecManual').addEventListener('click', function () { agregar(null); });

    $cuerpo.addEventListener('input', function (e) {
        var campo = e.target.closest('[data-campo]');
        if (!campo) { return; }
        var l = lineas[parseInt(campo.dataset.i, 10)];
        if (!l) { return; }
        l[campo.dataset.campo] = (campo.dataset.campo === 'name' || campo.dataset.campo === 'unit')
            ? campo.value : num(campo.value);
        if (campo.dataset.campo === 'id_tax') { l.id_tax = campo.value; }

        var fila = campo.closest('tr');
        fila.children[6].textContent = fmt(subtotal(l) + impuesto(l));

        var n = 0, imp = 0;
        lineas.forEach(function (x) { n += neto(x) * x.quantity - x.discount; imp += impuesto(x); });
        $('fecNeto').textContent = fmt(n);
        $('fecImpuesto').textContent = fmt(imp);
        $('fecTotal').textContent = fmt(n + imp);
        var pct = num($('exo_porcentaje').value);
        $('exo_monto').value = $('usaExoneracion').checked ? (imp * pct / 100).toFixed(5) : 0;
    });
    $cuerpo.addEventListener('change', function (e) {
        if (e.target.matches('select[data-campo]')) { pintar(); }
    });

    $cuerpo.addEventListener('click', function (e) {
        var b = e.target.closest('[data-quitar]');
        if (!b) { return; }
        lineas.splice(parseInt(b.dataset.quitar, 10), 1);
        pintar();
    });

    /* ── Proveedor ── */
    $('proveedor').addEventListener('change', function () {
        var op = this.selectedOptions[0];
        var aviso = '';
        if (op && op.value) {
            var suelto = (op.dataset.cf1 === '05' || op.dataset.cf1 === '06');
            if (!suelto && (!op.dataset.provincia || !op.dataset.email)) { aviso = T.incompleto; }
            if (op.dataset.plazo && num(op.dataset.plazo) > 0) { $('plazo').value = op.dataset.plazo; }
        }
        $('hintProveedor').textContent = aviso;
        pintar();
    });

    /* ── Pago ── */
    function alternarPago() {
        var credito = $('payment_status').value === 'due';
        $('wrapPlazo').hidden = !credito;
        $('wrapPagoPor').hidden = credito;
    }
    $('payment_status').addEventListener('change', alternarPago);

    /* ── Exoneración ── */
    $('usaExoneracion').addEventListener('change', function () {
        $('bloqueExoneracion').hidden = !this.checked;
        pintar();
    });
    $('exo_porcentaje').addEventListener('input', pintar);

    /* ── Emitir ── */
    $('fecGuardar').addEventListener('click', function () {
        if (!$('proveedor').value) { avisar(T.sinProveedor, 'err'); return; }
        if (!lineas.length) { avisar(T.sinLineas, 'err'); return; }

        var cuerpo = new FormData();
        cuerpo.set(CSRF_NOMBRE, CSRF_HASH);
        cuerpo.set('proveedor', $('proveedor').value);
        cuerpo.set('payment_status', $('payment_status').value);
        cuerpo.set('plazo', $('plazo').value);
        cuerpo.set('paid_by', $('paid_by').value);
        cuerpo.set('token_post', TOKEN_POST);
        cuerpo.set('lineas', JSON.stringify(lineas));

        if ($('usaExoneracion').checked) {
            cuerpo.set('exo_tipo', $('exo_tipo').value);
            cuerpo.set('exo_documento', $('exo_documento').value);
            cuerpo.set('exo_institucion', $('exo_institucion').value);
            cuerpo.set('exo_fecha', $('exo_fecha').value);
            cuerpo.set('exo_porcentaje', $('exo_porcentaje').value);
            cuerpo.set('exo_monto', $('exo_monto').value);
        }

        $('fecGuardar').disabled = true;
        fetch(URL_GUARDAR, { method: 'POST', body: cuerpo, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!Array.isArray(d) || d[0] !== 'success') {
                    avisar((Array.isArray(d) && d[1]) ? d[1] : T.errorRed, 'err');
                    $('fecGuardar').disabled = false;
                    return;
                }
                avisar(T.emitida, 'ok');
                window.location = '<?= site_url('facturascompras/view_fec'); ?>/' + d[2];
            })
            .catch(function () {
                avisar(T.errorRed, 'err');
                $('fecGuardar').disabled = false;
            });
    });

    function avisar(texto, tono) {
        var el = $('fecAviso');
        el.className = 'nxf-note nxf-note-' + tono;
        el.innerHTML = texto;
        el.hidden = false;
    }

    alternarPago();
    pintar();
})();
</script>
