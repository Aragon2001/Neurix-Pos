<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Sesión de inventario: se agregan todos los productos que haga falta y se
 * confirma una sola vez. El precio y el costo se editan en cualquier modo, así
 * que reprecificar es esta misma pantalla sin tocar cantidades.
 */
$modo_inicial = isset($modo_inicial) ? $modo_inicial : 'conteo';

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_barra  = '<path d="M4 7v-1a2 2 0 0 1 2 -2h2"/><path d="M4 17v1a2 2 0 0 0 2 2h2"/><path d="M16 4h2a2 2 0 0 1 2 2v1"/><path d="M16 20h2a2 2 0 0 0 2 -2v-1"/><path d="M5 11h1v2h-1z"/><path d="M10 11l0 2"/><path d="M14 11h1v2h-1z"/><path d="M19 11l0 2"/>';
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_borrar = '<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>';

$modos = array(
    'conteo'  => array(lang('inv_modo_conteo'),  lang('inv_modo_conteo_ayuda'),  '<path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"/><path d="M9 14l2 2l4 -4"/>'),
    'entrada' => array(lang('inv_modo_entrada'), lang('inv_modo_entrada_ayuda'), '<path d="M12 5l0 14"/><path d="M18 11l-6 -6"/><path d="M6 11l6 -6"/>'),
    'salida'  => array(lang('inv_modo_salida'),  lang('inv_modo_salida_ayuda'),  '<path d="M12 5l0 14"/><path d="M18 13l-6 6"/><path d="M6 13l6 6"/>'),
);
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('ajuste_inventario'); ?>
        <small><?= lang('inv_sesion_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('products'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('products'); ?>
        </a>
    </div>
</div>

<div class="inv-page">

    <div id="invAviso" class="nxf-note" style="display:none;"></div>

    <!-- ── Paso 1: qué se va a hacer ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title">
                <?= lang('inv_paso_modo'); ?>
                <small><?= lang('inv_paso_modo_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="inv-modos" id="invModos">
                <?php foreach ($modos as $clave => $texto): ?>
                <button type="button" class="inv-modo<?= $clave === $modo_inicial ? ' activo' : ''; ?>" data-modo="<?= $clave; ?>">
                    <span class="inv-modo-ico"><?= $icono($texto[2], 18); ?></span>
                    <span class="inv-modo-txt">
                        <strong><?= html_escape($texto[0]); ?></strong>
                        <small><?= html_escape($texto[1]); ?></small>
                    </span>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="nxf-note nxf-note-info" style="margin-top:14px;">
                <?= $icono('<circle cx="12" cy="12" r="9"/><path d="M12 8h.01"/><path d="M11 12h1v4h1"/>', 16); ?>
                <div><?= lang('inv_precio_siempre'); ?></div>
            </div>

            <div class="nxf-grid" style="margin-top:14px;">
                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="invMotivo"><?= lang('inv_motivo'); ?></label>
                    <input type="text" id="invMotivo" class="nxf-input" maxlength="255"
                           placeholder="<?= lang('inv_motivo_placeholder'); ?>">
                    <div class="nxf-hint" id="invMotivoHint"><?= lang('inv_motivo_ayuda'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 2: productos ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title">
                <?= lang('inv_paso_productos'); ?>
                <small><?= lang('inv_paso_productos_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">

            <div class="inv-buscador" id="invBuscador">
                <div class="inv-buscador-caja">
                    <span class="inv-buscador-ico"><?= $icono($ico_barra, 17); ?></span>
                    <input type="text" id="invBuscar" autocomplete="off" spellcheck="false"
                           placeholder="<?= lang('inv_escanear_ph'); ?>">
                    <kbd class="inv-kbd">Enter</kbd>
                </div>
                <div id="invSugerencias" class="inv-ac"></div>
            </div>
            <div class="nxf-hint" style="margin-top:8px;"><?= lang('inv_escanear_ayuda'); ?></div>

            <div class="inv-tabla-wrap">
                <table class="inv-tabla" id="invTabla">
                    <thead>
                        <tr>
                            <th><?= lang('product'); ?></th>
                            <th class="num"><?= lang('inv_existencia'); ?></th>
                            <th class="num" data-col="quantity"><span id="invColCant"><?= lang('quantity'); ?></span></th>
                            <th class="num" data-col="qty_fracc"><?= lang('cantidad_fracciones'); ?></th>
                            <th class="num" data-col="price"><?= lang('price'); ?></th>
                            <th class="num" data-col="cost"><?= lang('cost'); ?></th>
                            <th class="num"><?= lang('inv_resultado'); ?></th>
                            <th class="inv-th-x"></th>
                        </tr>
                    </thead>
                    <tbody id="invCuerpo"></tbody>
                </table>
                <div id="invVacio" class="inv-vacio">
                    <?= $icono($ico_barra, 30); ?>
                    <p><?= lang('add_product_by_searching_above_field'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <span class="inv-resumen" id="invResumen"></span>
        <span class="nxf-spacer"></span>
        <button type="button" class="nxf-btn nxf-btn-ghost" id="invLimpiar">
            <?= $icono($ico_borrar); ?> <?= lang('inv_vaciar'); ?>
        </button>
        <button type="button" class="nxf-btn" id="invGuardar" disabled>
            <?= $icono($ico_check); ?> <?= lang('inv_confirmar'); ?>
        </button>
    </div>
</div>

<style>
    .inv-page{max-width:1120px;margin:0 auto;display:flex;flex-direction:column;gap:16px}

    /* ── Modos ── */
    .inv-modos{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:11px}
    .inv-modo{
        display:flex;align-items:flex-start;gap:11px;text-align:left;padding:13px 15px;border-radius:12px;
        border:1px solid var(--nx-border);background:var(--nx-card-bg2);color:var(--nx-txt2);
        cursor:pointer;transition:var(--nx-transition);position:relative;overflow:hidden;
    }
    .inv-modo:hover{border-color:var(--nx-border3);color:var(--nx-txt1)}
    .inv-modo-ico{
        flex:0 0 auto;width:32px;height:32px;border-radius:9px;display:grid;place-items:center;
        background:var(--nx-hover-bg);color:var(--nx-txt3);transition:var(--nx-transition);
    }
    .inv-modo-txt{display:flex;flex-direction:column;gap:3px;min-width:0}
    .inv-modo-txt strong{font-size:14px;color:var(--nx-txt1);letter-spacing:-.01em}
    .inv-modo-txt small{font-size:11.5px;color:var(--nx-txt4);line-height:1.4}
    .inv-modo.activo{border-color:var(--nx-border3);background:var(--nx-active-bg);box-shadow:0 0 0 1px var(--nx-border3) inset}
    .inv-modo.activo .inv-modo-ico{background:linear-gradient(135deg,var(--nx-a1),var(--nx-info));color:#06121f}
    [data-theme="light"] .inv-modo.activo .inv-modo-ico{color:#fff}
    .inv-modo.activo .inv-modo-txt strong{color:var(--nx-a1)}

    /* ── Buscador con autosugerencia (mismo comportamiento que el POS) ── */
    .inv-buscador{position:relative}
    .inv-buscador-caja{
        display:flex;align-items:center;gap:11px;padding:12px 14px;border-radius:12px;
        border:1px solid var(--nx-border);background:var(--nx-input-bg);transition:var(--nx-transition);
    }
    .inv-buscador-caja:focus-within{border-color:var(--nx-border3);background:var(--nx-input-focus);box-shadow:0 0 0 3px rgba(56,189,248,.14)}
    .inv-buscador-ico{color:var(--nx-txt4);display:flex}
    .inv-buscador-caja:focus-within .inv-buscador-ico{color:var(--nx-a1)}
    .inv-buscador-caja input{flex:1;min-width:0;background:none;border:0;outline:0;color:var(--nx-txt1);font:400 15px inherit;font-family:inherit}
    .inv-buscador-caja input::placeholder{color:var(--nx-txt4)}
    .inv-kbd{
        font:600 10.5px ui-monospace,SFMono-Regular,Menlo,monospace;color:var(--nx-txt4);
        border:1px solid var(--nx-border);border-radius:6px;padding:2px 6px;background:var(--nx-card-bg2);
    }

    .inv-ac{
        display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:40;
        border:1px solid var(--nx-border3);border-radius:12px;overflow-x:hidden;overflow-y:auto;max-height:340px;
        background:var(--nx-modal-bg);backdrop-filter:blur(18px);box-shadow:var(--nx-shadow-lg);
    }
    .inv-ac.abierto{display:block}
    .inv-ac-item{
        display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:12px;
        padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--nx-border2);
    }
    .inv-ac-item:last-child{border-bottom:0}
    .inv-ac-item.marcada,.inv-ac-item:hover{background:var(--nx-active-bg)}
    .inv-ac-nom{min-width:0;display:flex;flex-direction:column;gap:2px}
    .inv-ac-nom b{font-size:13.5px;font-weight:600;color:var(--nx-txt1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .inv-ac-nom span{font:400 11.5px ui-monospace,SFMono-Regular,Menlo,monospace;color:var(--nx-txt4);letter-spacing:.03em}
    .inv-ac-nom mark{background:rgba(56,189,248,.22);color:inherit;border-radius:3px;padding:0 1px}
    .inv-ac-precio{font-size:13px;font-weight:600;color:var(--nx-txt2);font-variant-numeric:tabular-nums;white-space:nowrap}
    .inv-ac-stock{
        font:700 11.5px inherit;font-family:inherit;padding:3px 9px;border-radius:999px;white-space:nowrap;
        border:1px solid var(--nx-border);color:var(--nx-txt3);background:var(--nx-card-bg2);font-variant-numeric:tabular-nums;
    }
    .inv-ac-stock.bajo{border-color:rgba(234,179,8,.4);background:rgba(234,179,8,.12);color:var(--nx-warn)}
    .inv-ac-stock.sin{border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.12);color:var(--nx-err)}
    .inv-ac-nada{padding:14px;text-align:center;font-size:13px;color:var(--nx-txt4)}

    /* ── Tabla de líneas ── */
    .inv-tabla-wrap{margin-top:18px;border:1px solid var(--nx-border);border-radius:var(--nx-radius);overflow-x:auto}
    .inv-tabla{width:100%;border-collapse:collapse;font-size:13px;min-width:860px}
    .inv-tabla thead th{
        padding:10px 12px;text-align:left;font-size:10.5px;font-weight:700;text-transform:uppercase;
        letter-spacing:.08em;color:var(--nx-txt4);background:var(--nx-card-bg2);border-bottom:1px solid var(--nx-border);
        white-space:nowrap;
    }
    .inv-tabla td{padding:9px 12px;border-bottom:1px solid var(--nx-border2);vertical-align:middle}
    .inv-tabla tbody tr:last-child td{border-bottom:0}
    .inv-tabla tbody tr:hover{background:var(--nx-hover-bg)}
    .inv-tabla th.num,.inv-tabla td.num{text-align:right}
    .inv-tabla .inv-th-x{width:44px}
    .inv-prod{display:flex;flex-direction:column;gap:2px;min-width:180px}
    .inv-prod b{font-size:13.5px;font-weight:600;color:var(--nx-txt1)}
    .inv-prod span{font:400 11.5px ui-monospace,SFMono-Regular,Menlo,monospace;color:var(--nx-txt4);letter-spacing:.03em}
    .inv-exist{font-variant-numeric:tabular-nums;color:var(--nx-txt3)}
    .inv-tabla input{
        width:108px;text-align:right;padding:7px 9px;border-radius:8px;font:600 13px inherit;font-family:inherit;
        border:1px solid var(--nx-border);background:var(--nx-input-bg);color:var(--nx-txt1);
        outline:0;transition:var(--nx-transition);font-variant-numeric:tabular-nums;
    }
    .inv-tabla input::placeholder{font-weight:400;color:var(--nx-txt4)}
    .inv-tabla input:focus{border-color:var(--nx-border3);background:var(--nx-input-focus);box-shadow:0 0 0 3px rgba(56,189,248,.14)}
    .inv-tabla input.tocado{border-color:var(--nx-border3);color:var(--nx-a1)}
    .inv-res{font-weight:700;font-variant-numeric:tabular-nums;color:var(--nx-txt1)}
    .inv-res.sube{color:var(--nx-ok)}
    .inv-res.baja{color:var(--nx-warn)}
    .inv-res.neg{color:var(--nx-err)}
    .inv-quitar{
        display:grid;place-items:center;width:28px;height:28px;border-radius:8px;margin-left:auto;
        background:none;border:1px solid transparent;color:var(--nx-txt4);cursor:pointer;transition:var(--nx-transition);
    }
    .inv-quitar:hover{border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.10);color:var(--nx-err)}
    .inv-vacio{display:flex;flex-direction:column;align-items:center;gap:10px;padding:38px 20px;color:var(--nx-txt4)}
    .inv-vacio p{margin:0;font-size:13px}
    .inv-tabla td[data-oculta],.inv-tabla th[data-oculta]{display:none}
    .inv-resumen{font-size:13px;color:var(--nx-txt3)}
    .inv-resumen b{color:var(--nx-txt1)}
</style>

<script>
(function () {
    'use strict';

    var URL_BUSCAR  = '<?= site_url('products/buscar_inventario'); ?>';
    var URL_GUARDAR = '<?= site_url('products/guardar_sesion_inventario'); ?>';
    var CSRF_NOMBRE = '<?= $this->security->get_csrf_token_name(); ?>';
    var FRACCIONES  = <?= (int) ($Settings->enable_fractions == '1'); ?>;

    var T = {
        existenciaNegativa: <?= json_encode(lang('inv_existencia_negativa')); ?>,
        motivoRequerido:    <?= json_encode(lang('inv_motivo_requerido')); ?>,
        motivoAyuda:        <?= json_encode(lang('inv_motivo_ayuda')); ?>,
        sinResultados:      <?= json_encode(lang('no_match_found')); ?>,
        errorRed:           <?= json_encode(lang('inv_error_red')); ?>,
        resumen:            <?= json_encode(lang('inv_resumen_sesion')); ?>,
        colConteo:          <?= json_encode(lang('inv_col_contado')); ?>,
        colEntrada:         <?= json_encode(lang('inv_col_entran')); ?>,
        colSalida:          <?= json_encode(lang('inv_col_salen')); ?>
    };

    var ROTULO_CANT = { conteo: T.colConteo, entrada: T.colEntrada, salida: T.colSalida };

    var modo = '<?= $modo_inicial; ?>';
    var lineas = [];
    var sugerencias = [];
    var marcada = -1;
    var temporizador = null;

    var $buscar  = document.getElementById('invBuscar');
    var $sug     = document.getElementById('invSugerencias');
    var $cuerpo  = document.getElementById('invCuerpo');
    var $vacio   = document.getElementById('invVacio');
    var $guardar = document.getElementById('invGuardar');
    var $limpiar = document.getElementById('invLimpiar');
    var $motivo  = document.getElementById('invMotivo');
    var $aviso   = document.getElementById('invAviso');
    var $resumen = document.getElementById('invResumen');
    var $tabla   = document.getElementById('invTabla');
    var $colCant = document.getElementById('invColCant');

    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Resalta el trozo buscado dentro del nombre, como la lista del POS.
    function resaltar(texto, term) {
        var t = esc(texto);
        if (!term) { return t; }
        var i = t.toLowerCase().indexOf(esc(term).toLowerCase());
        if (i === -1) { return t; }
        return t.slice(0, i) + '<mark>' + t.slice(i, i + term.length) + '</mark>' + t.slice(i + term.length);
    }

    function num(v) {
        var n = parseFloat(String(v).replace(',', '.'));
        return isNaN(n) ? null : n;
    }

    // La existencia llega como 30.0000: en pantalla estorba más de lo que informa.
    function corta(n) {
        return String(Math.round((Number(n) || 0) * 1000) / 1000);
    }

    function avisar(texto, tono) {
        if (!texto) { $aviso.style.display = 'none'; $aviso.className = 'nxf-note'; return; }
        $aviso.className = 'nxf-note nxf-note-' + (tono || 'info');
        $aviso.textContent = texto;
        $aviso.style.display = '';
    }

    /* ── Columnas: las fracciones solo si el sistema las usa ── */
    function columnasVisibles() {
        var usadas = ['quantity', 'qty_fracc', 'price', 'cost'];
        if (!FRACCIONES) { usadas = usadas.filter(function (c) { return c !== 'qty_fracc'; }); }
        $tabla.querySelectorAll('[data-col]').forEach(function (th) {
            if (usadas.indexOf(th.dataset.col) !== -1) { th.removeAttribute('data-oculta'); }
            else { th.setAttribute('data-oculta', '1'); }
        });
        return usadas;
    }

    function resultado(l) {
        var q = l.quantity;
        if (q === null || q === undefined) { return l.existencia; }
        if (modo === 'conteo')  { return q; }
        if (modo === 'entrada') { return l.existencia + q; }
        return l.existencia - q;
    }

    function tonoResultado(l) {
        var r = resultado(l);
        if (r < 0) { return 'neg'; }
        if (l.quantity === null || l.quantity === undefined) { return ''; }
        if (r > l.existencia) { return 'sube'; }
        if (r < l.existencia) { return 'baja'; }
        return '';
    }

    function pintar() {
        var usadas = columnasVisibles();
        $cuerpo.innerHTML = '';

        lineas.forEach(function (l, i) {
            var tr = document.createElement('tr');
            var celdas = '<td><div class="inv-prod"><b>' + esc(l.name) + '</b><span>' + esc(l.code) + '</span></div></td>'
                       + '<td class="num"><span class="inv-exist">' + corta(l.existencia) + '</span></td>';

            [['quantity', modo === 'conteo' ? corta(l.existencia) : '0'],
             ['qty_fracc', '0'],
             ['price', corta(l.precio_actual)],
             ['cost', corta(l.costo_actual)]].forEach(function (par) {
                var campo  = par[0];
                var oculta = usadas.indexOf(campo) === -1 ? ' data-oculta="1"' : '';
                var valor  = (l[campo] === null || l[campo] === undefined) ? '' : l[campo];
                celdas += '<td class="num"' + oculta + '>'
                        + '<input type="number" step="any" inputmode="decimal" data-i="' + i + '" data-campo="' + campo + '"'
                        + ' placeholder="' + esc(par[1]) + '" value="' + esc(valor) + '"'
                        + (valor === '' ? '' : ' class="tocado"') + '></td>';
            });

            celdas += '<td class="num"><span class="inv-res ' + tonoResultado(l) + '">' + corta(resultado(l)) + '</span></td>'
                    + '<td><button type="button" class="inv-quitar" data-quitar="' + i + '" aria-label="x">'
                    + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>'
                    + '</button></td>';

            tr.innerHTML = celdas;
            $cuerpo.appendChild(tr);
        });

        $vacio.style.display = lineas.length ? 'none' : '';
        revisar();
    }

    /* ── Estado del botón de confirmar y del resumen ── */
    function revisar() {
        var negativa = lineas.some(function (l) { return resultado(l) < 0; });
        var conDatos = lineas.filter(function (l) {
            return ['quantity', 'qty_fracc', 'price', 'cost'].some(function (c) {
                return l[c] !== null && l[c] !== undefined;
            });
        }).length;

        $guardar.disabled = negativa || conDatos === 0;
        $resumen.innerHTML = lineas.length
            ? T.resumen.replace('%1', '<b>' + lineas.length + '</b>').replace('%2', '<b>' + conDatos + '</b>')
            : '';

        if (negativa) { avisar(T.existenciaNegativa, 'warn'); }
        else if ($aviso.classList.contains('nxf-note-warn')) { avisar(''); }
    }

    function agregar(p) {
        var ya = -1;
        lineas.forEach(function (l, i) { if (l.id === p.id) { ya = i; } });

        if (ya !== -1) {
            // Volver a leer el mismo código suma una unidad en vez de duplicar.
            lineas[ya].quantity = (lineas[ya].quantity || 0) + 1;
            pintar();
            enfocar(ya);
            return;
        }

        lineas.push({
            id: p.id, code: p.code, name: p.name,
            existencia: p.existencia,
            precio_actual: p.precio,
            costo_actual: p.costo,
            quantity: modo === 'conteo' ? null : 1,
            qty_fracc: null,
            price: null,
            cost: null
        });
        pintar();
        enfocar(lineas.length - 1);
    }

    function enfocar(i) {
        var input = $cuerpo.querySelector('input[data-i="' + i + '"][data-campo="quantity"]');
        if (input) { input.focus(); input.select(); }
    }

    /* ── Autosugerencia ── */
    function cerrarSug() {
        sugerencias = []; marcada = -1;
        $sug.classList.remove('abierto');
        $sug.innerHTML = '';
    }

    function pintarSugerencias(term) {
        if (!sugerencias.length) {
            $sug.innerHTML = '<div class="inv-ac-nada">' + esc(T.sinResultados) + '</div>';
            $sug.classList.add('abierto');
            return;
        }
        $sug.innerHTML = sugerencias.map(function (p, i) {
            var tono = !(p.existencia > 0) ? 'sin' : (p.alerta > 0 && p.existencia <= p.alerta ? 'bajo' : '');
            return '<div class="inv-ac-item' + (i === marcada ? ' marcada' : '') + '" data-sug="' + i + '">'
                 + '<span class="inv-ac-nom"><b>' + resaltar(p.name, term) + '</b><span>' + resaltar(p.code, term) + '</span></span>'
                 + '<span class="inv-ac-precio">' + esc(p.precio_fmt) + '</span>'
                 + '<span class="inv-ac-stock ' + tono + '">' + corta(p.existencia) + '</span>'
                 + '</div>';
        }).join('');
        $sug.classList.add('abierto');
    }

    function buscar(term, autoAgregar) {
        fetch(URL_BUSCAR + '?term=' + encodeURIComponent(term), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) {
            if (!r.ok) { throw new Error(r.status); }
            return r.json();
        })
        .then(function (d) {
            var lista = d.productos || [];
            // Código exacto: el lector manda el código completo y entra solo.
            if ((d.exacto || autoAgregar) && lista.length === 1) {
                cerrarSug();
                $buscar.value = '';
                agregar(lista[0]);
                return;
            }
            sugerencias = lista;
            marcada = lista.length ? 0 : -1;
            pintarSugerencias(term);
        })
        .catch(function () { avisar(T.errorRed, 'err'); });
    }

    $buscar.addEventListener('input', function () {
        var v = $buscar.value.trim();
        clearTimeout(temporizador);
        if (v.length < 2) { cerrarSug(); return; }
        temporizador = setTimeout(function () { buscar(v, false); }, 200);
    });

    $buscar.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (!sugerencias.length) { return; }
            e.preventDefault();
            marcada = (marcada + (e.key === 'ArrowDown' ? 1 : -1) + sugerencias.length) % sugerencias.length;
            pintarSugerencias($buscar.value.trim());
            var m = $sug.querySelector('.marcada');
            if (m) { m.scrollIntoView({ block: 'nearest' }); }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(temporizador);
            if (marcada >= 0 && sugerencias[marcada]) {
                var p = sugerencias[marcada];
                cerrarSug();
                $buscar.value = '';
                agregar(p);
            } else if ($buscar.value.trim()) {
                buscar($buscar.value.trim(), true);
            }
        } else if (e.key === 'Escape') {
            cerrarSug();
        }
    });

    $sug.addEventListener('mousedown', function (e) {
        var fila = e.target.closest('[data-sug]');
        if (!fila) { return; }
        e.preventDefault();
        var p = sugerencias[parseInt(fila.dataset.sug, 10)];
        cerrarSug();
        $buscar.value = '';
        agregar(p);
        $buscar.focus();
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('invBuscador').contains(e.target)) { cerrarSug(); }
    });

    /* ── Edición de las líneas ── */
    $cuerpo.addEventListener('input', function (e) {
        var input = e.target.closest('input[data-campo]');
        if (!input) { return; }
        var l = lineas[parseInt(input.dataset.i, 10)];
        if (!l) { return; }

        l[input.dataset.campo] = input.value === '' ? null : num(input.value);
        input.classList.toggle('tocado', input.value !== '');

        // Repintar entero movería el foco: solo se recalcula el resultado.
        var celda = input.closest('tr').querySelector('.inv-res');
        celda.textContent = corta(resultado(l));
        celda.className = 'inv-res ' + tonoResultado(l);
        revisar();
    });

    $cuerpo.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();
        $buscar.focus();
        $buscar.select();
    });

    $cuerpo.addEventListener('click', function (e) {
        var b = e.target.closest('[data-quitar]');
        if (!b) { return; }
        lineas.splice(parseInt(b.dataset.quitar, 10), 1);
        pintar();
    });

    /* ── Cambio de modo ── */
    function aplicarModo() {
        $colCant.textContent = ROTULO_CANT[modo];
        document.getElementById('invMotivoHint').textContent = modo === 'salida' ? T.motivoRequerido : T.motivoAyuda;
    }

    document.getElementById('invModos').addEventListener('click', function (e) {
        var b = e.target.closest('[data-modo]');
        if (!b || b.dataset.modo === modo) { return; }
        modo = b.dataset.modo;
        this.querySelectorAll('.inv-modo').forEach(function (x) { x.classList.toggle('activo', x === b); });

        // La cantidad significa otra cosa en cada modo: los productos se quedan,
        // lo tecleado en esa columna no.
        lineas.forEach(function (l) {
            l.quantity = modo === 'conteo' ? null : 1;
            l.qty_fracc = null;
        });
        aplicarModo();
        pintar();
        $buscar.focus();
    });

    $limpiar.addEventListener('click', function () {
        lineas = [];
        avisar('');
        pintar();
        $buscar.focus();
    });

    /* ── Confirmar ── */
    $guardar.addEventListener('click', function () {
        if (!lineas.length) { return; }
        if (modo === 'salida' && !$motivo.value.trim()) {
            avisar(T.motivoRequerido, 'err');
            $motivo.focus();
            return;
        }

        var cuerpo = new FormData();
        cuerpo.set(CSRF_NOMBRE, window.CSRF_HASH);
        cuerpo.set('modo', modo);
        cuerpo.set('descripcion_mov', $motivo.value.trim());
        cuerpo.set('lineas', JSON.stringify(lineas.map(function (l) {
            var o = { product_id: l.id };
            ['quantity', 'qty_fracc', 'price', 'cost'].forEach(function (c) {
                if (l[c] !== null && l[c] !== undefined) { o[c] = l[c]; }
            });
            return o;
        })));

        $guardar.disabled = true;
        fetch(URL_GUARDAR, { method: 'POST', body: cuerpo, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (!res.ok || !res.d.ok) {
                    avisar((res.d && res.d.msg) || T.errorRed, 'err');
                    revisar();
                    return;
                }
                lineas = [];
                pintar();
                avisar(res.d.msg, 'ok');
                $buscar.focus();
            })
            .catch(function () {
                avisar(T.errorRed, 'err');
                revisar();
            });
    });

    aplicarModo();
    pintar();
    $buscar.focus();
})();
</script>
