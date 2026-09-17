<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Formulario compartido por purchases/add y purchases/edit.
 * $modo vale 'add' o 'edit'; en 'edit' llegan ademas $purchase e $items_json.
 */
$nuevo = ($modo === 'add');
$p     = (isset($purchase) && $purchase) ? $purchase : NULL;

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_buscar = '<circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/>';
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= $nuevo ? lang('add_purchase') : lang('edit_purchase'); ?>
        <small><?= lang('compra_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('purchases'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('purchases'); ?>
        </a>
    </div>
</div>

<?= form_open_multipart($nuevo ? 'purchases/add' : 'purchases/edit/' . $p->id, 'id="nxfCompra"'); ?>
<div class="nxf-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err"><div><?= $error; ?></div></div>
    <?php } ?>

    <!-- ── Paso 1: la compra ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title"><?= lang('compra_paso_datos'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="date"><?= lang('date'); ?> <span class="req">*</span></label>
                    <input type="datetime-local" name="date" id="date" class="nxf-input" required
                           value="<?= set_value('date', $p ? date('Y-m-d\TH:i', strtotime($p->date)) : date('Y-m-d\TH:i')); ?>">
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="supplier"><?= lang('supplier'); ?> <span class="req">*</span></label>
                    <select name="supplier" id="supplier" class="nxf-select" required
                            data-plazos='<?= json_encode($plazos_proveedor); ?>'>
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($suppliers as $sup) { ?>
                            <option value="<?= $sup->id; ?>" <?= (set_value('supplier', $p ? $p->supplier_id : '') == $sup->id) ? 'selected' : ''; ?>>
                                <?= html_escape($sup->name); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="nxf-hint" id="hintPlazo"></div>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="received"><?= lang('received'); ?></label>
                    <select name="received" id="received" class="nxf-select">
                        <option value="1" <?= (set_value('received', $p ? $p->received : '1') == '1') ? 'selected' : ''; ?>><?= lang('received'); ?></option>
                        <option value="0" <?= (set_value('received', $p ? $p->received : '1') == '0') ? 'selected' : ''; ?>><?= lang('not_received_yet'); ?></option>
                    </select>
                </div>

                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="reference"><?= lang('reference'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="reference" id="reference" class="nxf-input" maxlength="100"
                           value="<?= set_value('reference', $p ? $p->reference : ''); ?>">
                </div>
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="supplier_invoice_no"><?= lang('supplier_invoice_no'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="supplier_invoice_no" id="supplier_invoice_no" class="nxf-input mono" maxlength="50"
                           value="<?= set_value('supplier_invoice_no', ($p && isset($p->supplier_invoice_no)) ? $p->supplier_invoice_no : ''); ?>">
                    <div class="nxf-hint"><?= lang('supplier_invoice_no_ayuda'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 2: las líneas ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title">
                <?= lang('compra_paso_lineas'); ?>
                <small><?= lang('compra_paso_lineas_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-field">
                <label class="nxf-label" for="add_item"><?= $icono($ico_buscar); ?> <?= lang('search_product_by_name_code'); ?></label>
                <input type="text" id="add_item" class="nxf-input" autocomplete="off"
                       placeholder="<?= lang('search_product_by_name_code'); ?>">
                <div id="cmpSugerencias" class="cmp-sugerencias" style="display:none;"></div>
            </div>

            <div class="cmp-tabla-wrap">
                <table class="cmp-tabla" id="cmpTabla">
                    <thead>
                        <tr>
                            <th><?= lang('product'); ?></th>
                            <th class="num"><?= lang('quantity'); ?></th>
                            <th class="num"><?= lang('unit_cost'); ?></th>
                            <th class="num"><?= lang('descuento'); ?></th>
                            <th class="num"><?= lang('impuesto'); ?></th>
                            <th class="num"><?= lang('margen'); ?></th>
                            <th class="num"><?= lang('price'); ?></th>
                            <th class="num"><?= lang('subtotal'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cmpCuerpo"></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="num"><?= lang('subtotal'); ?></th>
                            <th class="num" id="cmpSubtotal">0.00</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="7" class="num"><?= lang('impuesto'); ?></th>
                            <th class="num" id="cmpImpuesto">0.00</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="7" class="num"><?= lang('total'); ?></th>
                            <th class="num" id="cmpTotal">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <div id="cmpVacio" class="cmp-vacio"><?= lang('add_product_by_searching_above_field'); ?></div>
            </div>
        </div>
    </div>

    <!-- ── Paso 3: adjuntos y nota ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">3</span>
            <div class="nxf-card-title"><?= lang('compra_paso_cierre'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="attachment"><?= lang('attachment'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="file" name="userfile" id="attachment" class="nxf-input">
                </div>
                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="note"><?= lang('note'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <textarea name="note" id="note" class="nxf-textarea"><?= set_value('note', $p ? $p->note : ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('purchases'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="button" class="nxf-btn nxf-btn-ghost" id="cmpVaciar"><?= lang('reset'); ?></button>
        <button type="submit" class="nxf-btn" id="cmpGuardar" name="<?= $nuevo ? 'add_purchase' : 'edit_purchase'; ?>" value="1">
            <?= $icono($ico_check); ?> <?= $nuevo ? lang('add_purchase') : lang('save'); ?>
        </button>
    </div>
</div>
<?= form_close(); ?>

<style>
    .cmp-sugerencias{margin-top:8px;border:1px solid var(--nxf-border,#2a3444);border-radius:10px;overflow:hidden;max-height:320px;overflow-y:auto}
    .cmp-sug{display:flex;justify-content:space-between;gap:12px;padding:9px 12px;cursor:pointer;font-size:13px}
    .cmp-sug:hover,.cmp-sug.marcada{background:rgba(56,189,248,.12)}
    .cmp-sug small{opacity:.65}
    .cmp-tabla-wrap{margin-top:16px;overflow-x:auto}
    .cmp-tabla{width:100%;border-collapse:collapse;font-size:13px;min-width:960px}
    .cmp-tabla th,.cmp-tabla td{padding:8px 10px;border-bottom:1px solid var(--nxf-border,#2a3444);text-align:left}
    .cmp-tabla th.num,.cmp-tabla td.num{text-align:right}
    .cmp-tabla input,.cmp-tabla select{width:100px;text-align:right;padding:6px 8px;border-radius:7px;
        border:1px solid var(--nxf-border,#2a3444);background:var(--nxf-surface-2,rgba(148,163,184,.06));color:inherit}
    .cmp-tabla select{width:130px;text-align:left}
    .cmp-tabla .quitar{background:none;border:0;color:#f87171;cursor:pointer;font-size:16px;line-height:1}
    .cmp-vacio{padding:22px;text-align:center;opacity:.6;font-size:13px}
</style>

<script>
(function () {
    'use strict';

    var URL_BUSCAR = '<?= site_url('purchases/buscar_producto'); ?>';
    var IMPUESTOS  = <?= json_encode($impuestos); ?>;
    var INICIALES  = <?= isset($items_json) ? $items_json : '[]'; ?>;
    var DECIMALES  = <?= (int) ($Settings->decimals ?? 2); ?>;

    var T = {
        sinResultados: <?= json_encode(lang('no_match_found')); ?>,
        errorRed:      <?= json_encode(lang('inv_error_red')); ?>,
        sinLineas:     <?= json_encode(lang('compra_sin_lineas')); ?>,
        plazo:         <?= json_encode(lang('compra_plazo_proveedor')); ?>
    };

    var lineas = [];
    var sugerencias = [];
    var marcada = -1;
    var temporizador = null;

    var $ = function (id) { return document.getElementById(id); };
    var $buscar = $('add_item'), $sug = $('cmpSugerencias'), $cuerpo = $('cmpCuerpo'), $vacio = $('cmpVacio');

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }
    function num(v) { var n = parseFloat(String(v).replace(',', '.')); return isNaN(n) ? 0 : n; }
    function fmt(n) { return num(n).toFixed(DECIMALES); }

    function subtotal(l) { return (num(l.cost) * num(l.quantity)) - num(l.discount); }
    function impuesto(l) { return subtotal(l) * (num(l.tax_rate) / 100); }

    function opcionesImpuesto(codigo) {
        return IMPUESTOS.map(function (i) {
            var sel = (String(i.codigo_tarifa) === String(codigo)) ? ' selected' : '';
            return '<option value="' + esc(i.codigo_tarifa) + '" data-tasa="' + i.tasa + '"' + sel + '>'
                 + esc(i.tasa) + '% — ' + esc(i.codigo_tarifa) + '</option>';
        }).join('');
    }

    function pintar() {
        $cuerpo.innerHTML = '';
        var st = 0, imp = 0;

        lineas.forEach(function (l, i) {
            st += subtotal(l);
            imp += impuesto(l);

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><strong>' + esc(l.name) + '</strong><br><small>' + esc(l.code) + '</small>'
              + '<input type="hidden" name="product_id[]" value="' + esc(l.product_id) + '"></td>'
              + '<td class="num"><input type="number" step="any" min="0" name="quantity[]" data-i="' + i + '" data-campo="quantity" value="' + esc(l.quantity) + '"></td>'
              + '<td class="num"><input type="number" step="any" min="0" name="cost[]" data-i="' + i + '" data-campo="cost" value="' + esc(l.cost) + '"></td>'
              + '<td class="num"><input type="number" step="any" min="0" name="discount[]" data-i="' + i + '" data-campo="discount" value="' + esc(l.discount) + '"></td>'
              + '<td class="num"><select name="tax_code[]" data-i="' + i + '" data-campo="tax_code">' + opcionesImpuesto(l.tax_code) + '</select>'
              + '<input type="hidden" name="tax_rate[]" value="' + esc(l.tax_rate) + '"></td>'
              + '<td class="num"><input type="number" step="any" name="margin[]" data-i="' + i + '" data-campo="margin" value="' + esc(l.margin) + '"></td>'
              + '<td class="num"><input type="number" step="any" min="0" name="price[]" data-i="' + i + '" data-campo="price" value="' + esc(l.price) + '"></td>'
              + '<td class="num">' + fmt(subtotal(l) + impuesto(l)) + '</td>'
              + '<td><button type="button" class="quitar" data-quitar="' + i + '" aria-label="x">&times;</button></td>';
            $cuerpo.appendChild(tr);
        });

        $('cmpSubtotal').textContent = fmt(st);
        $('cmpImpuesto').textContent = fmt(imp);
        $('cmpTotal').textContent    = fmt(st + imp);
        $vacio.style.display = lineas.length ? 'none' : '';
    }

    function agregar(p) {
        var ya = lineas.findIndex(function (l) { return String(l.product_id) === String(p.id); });
        if (ya !== -1) {
            lineas[ya].quantity = num(lineas[ya].quantity) + 1;
            pintar();
            return;
        }
        lineas.push({
            product_id: p.id, code: p.code, name: p.name,
            quantity: 1, cost: p.cost, discount: 0,
            tax_code: p.codigo_tarifa, tax_rate: p.tasa,
            margin: p.margen, price: p.price
        });
        pintar();
    }

    function pintarSugerencias() {
        if (!sugerencias.length) { $sug.style.display = 'none'; return; }
        $sug.innerHTML = sugerencias.map(function (p, i) {
            return '<div class="cmp-sug' + (i === marcada ? ' marcada' : '') + '" data-sug="' + i + '">'
                 + '<span>' + esc(p.name) + ' <small>' + esc(p.code) + '</small></span>'
                 + '<small>' + fmt(p.cost) + '</small></div>';
        }).join('');
        $sug.style.display = '';
    }

    function buscar(term) {
        fetch(URL_BUSCAR + '?term=' + encodeURIComponent(term), {
            credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
        .then(function (d) {
            var lista = d.productos || [];
            if (d.exacto && lista.length === 1) {
                sugerencias = []; marcada = -1; pintarSugerencias();
                $buscar.value = '';
                agregar(lista[0]);
                return;
            }
            sugerencias = lista;
            marcada = lista.length ? 0 : -1;
            pintarSugerencias();
        })
        .catch(function () {});
    }

    $buscar.addEventListener('input', function () {
        var v = $buscar.value.trim();
        clearTimeout(temporizador);
        if (v.length < 2) { sugerencias = []; pintarSugerencias(); return; }
        temporizador = setTimeout(function () { buscar(v); }, 180);
    });

    $buscar.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!sugerencias.length) { return; }
            marcada = (marcada + (e.key === 'ArrowDown' ? 1 : -1) + sugerencias.length) % sugerencias.length;
            pintarSugerencias();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(temporizador);
            if (marcada >= 0 && sugerencias[marcada]) {
                agregar(sugerencias[marcada]);
                sugerencias = []; marcada = -1; pintarSugerencias();
                $buscar.value = '';
            } else if ($buscar.value.trim()) {
                buscar($buscar.value.trim());
            }
        } else if (e.key === 'Escape') {
            sugerencias = []; marcada = -1; pintarSugerencias();
        }
    });

    $sug.addEventListener('click', function (e) {
        var fila = e.target.closest('[data-sug]');
        if (!fila) { return; }
        agregar(sugerencias[parseInt(fila.dataset.sug, 10)]);
        sugerencias = []; marcada = -1; pintarSugerencias();
        $buscar.value = '';
        $buscar.focus();
    });

    // El impuesto y los totales se recalculan sin repintar, para no perder el foco.
    $cuerpo.addEventListener('input', function (e) {
        var campo = e.target.closest('[data-campo]');
        if (!campo) { return; }
        var l = lineas[parseInt(campo.dataset.i, 10)];
        if (!l) { return; }

        if (campo.dataset.campo === 'tax_code') {
            l.tax_code = campo.value;
            l.tax_rate = num(campo.selectedOptions[0].dataset.tasa);
            campo.closest('td').querySelector('input[name="tax_rate[]"]').value = l.tax_rate;
        } else {
            l[campo.dataset.campo] = campo.value;
        }

        var fila = campo.closest('tr');
        fila.children[7].textContent = fmt(subtotal(l) + impuesto(l));

        var st = 0, imp = 0;
        lineas.forEach(function (x) { st += subtotal(x); imp += impuesto(x); });
        $('cmpSubtotal').textContent = fmt(st);
        $('cmpImpuesto').textContent = fmt(imp);
        $('cmpTotal').textContent    = fmt(st + imp);
    });
    $cuerpo.addEventListener('change', function (e) {
        if (e.target.matches('select[data-campo="tax_code"]')) {
            $cuerpo.dispatchEvent(new Event('input', { bubbles: false }));
        }
    });

    $cuerpo.addEventListener('click', function (e) {
        var b = e.target.closest('[data-quitar]');
        if (!b) { return; }
        lineas.splice(parseInt(b.dataset.quitar, 10), 1);
        pintar();
    });

    $('cmpVaciar').addEventListener('click', function () { lineas = []; pintar(); $buscar.focus(); });

    $('nxfCompra').addEventListener('submit', function (e) {
        if (!lineas.length) {
            e.preventDefault();
            alert(T.sinLineas);
        }
    });

    // Plazo de pago del proveedor, para que se vea el vencimiento antes de guardar.
    var $sup = $('supplier');
    var PLAZOS = JSON.parse($sup.dataset.plazos || '{}');
    function mostrarPlazo() {
        var d = PLAZOS[$sup.value];
        $('hintPlazo').textContent = (d === undefined || d === null) ? '' : T.plazo.replace('%s', d);
    }
    $sup.addEventListener('change', mostrarPlazo);
    mostrarPlazo();

    INICIALES.forEach(function (l) { lineas.push(l); });
    pintar();
    $buscar.focus();
})();
</script>
