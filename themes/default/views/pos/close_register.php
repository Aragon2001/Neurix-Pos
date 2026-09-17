<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 *
 * Cierre de caja. Se carga dentro de un modal del POS (`abrirParcial`) y también
 * lo reusa Reports para ver un turno ya cerrado (`$is_report`).
 *
 * El formulario solo manda lo que el cajero cuenta: el resto de las cifras las
 * recalcula PosRegister::close_register() al guardar.
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$soloLectura = isset($is_report);
$r           = isset($resumen) ? $resumen : null;
$cajero      = isset($r['cajero']->first_name) ? trim($r['cajero']->first_name . ' ' . $r['cajero']->last_name) : '';
$apertura    = $soloLectura ? ($register_open_time ?? '') : $this->session->userdata('register_open_time');

// El admin puede abrir el modal sin indicar cajero: entonces es el suyo.
$user_id = $user_id ?: $this->session->userdata('user_id');

$accion = $soloLectura
    ? 'reports/close_register/?user_id=' . $user_id . '&date=' . $register_open_time
    : 'pos/close_register/' . $user_id;

$impuestos = $r && !empty($r['impuestos']) ? $r['impuestos'] : array();

/**
 * Iconos en SVG. La hoja de Font Awesome del tema es la 4.1 y no trae varios
 * de los glifos que hacen falta acá: salían como un recuadro vacío.
 */
$svg = function ($trazos, $tam = 17) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $tam . '" height="' . $tam . '"'
        . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $trazos . '</svg>';
};
$ico = array(
    'caja'     => '<rect x="4" y="3" width="16" height="18" rx="2"/><rect x="8" y="7" width="8" height="3" rx="1"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01M16 17h.01"/>',
    'correo'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    'imprimir' => '<path d="M6 9V3h12v6"/><rect x="6" y="14" width="12" height="7"/><path d="M6 18H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2"/>',
    'candado'  => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'lista'    => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    'celular'  => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
    'anulada'  => '<circle cx="12" cy="12" r="9"/><path d="M5.7 5.7l12.6 12.6"/>',
);
?>

<style>
.nx-cc { --pad: 22px; color: var(--nx-txt1); }
.nx-cc-head {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 20px var(--pad) 18px; border-bottom: 1px solid var(--nx-border2);
}
.nx-cc-ico {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(56,189,248,.13); color: var(--nx-a1);
}
.nx-cc-head h2 { margin: 0; font-size: 16px; font-weight: 700; }
.nx-cc-head p  { margin: 3px 0 0; font-size: 12px; color: var(--nx-txt3); line-height: 1.5; }
.nx-cc-close { margin-left: auto; background: none; border: 0; color: var(--nx-txt3);
    font-size: 20px; cursor: pointer; line-height: 1; padding: 2px 6px; }
.nx-cc-close:hover { color: var(--nx-txt1); }

.nx-cc-body { padding: 20px var(--pad); display: grid; gap: 18px; align-items: start; }
@media (min-width: 780px) { .nx-cc-body { grid-template-columns: 1fr 1fr; } }

.nx-cc-bloque { background: var(--nx-bg4); border: 1px solid var(--nx-border2); border-radius: 12px; padding: 16px 18px; }
.nx-cc-bloque h3 {
    display: flex; align-items: center; gap: 6px;
    margin: 0 0 12px; font-size: 10.5px; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: var(--nx-txt3);
}
.nx-cc-fila { display: flex; justify-content: space-between; align-items: baseline; gap: 12px;
    padding: 6px 0; font-size: 13px; border-bottom: 1px solid var(--nx-border2); }
.nx-cc-fila:last-of-type { border-bottom: 0; }
.nx-cc-fila span { color: var(--nx-txt2); }
.nx-cc-fila em { font-style: normal; font-size: 11px; color: var(--nx-txt3); margin-left: 5px; }
.nx-cc-fila b { font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap; }
.nx-cc-fila.resta b { color: var(--nx-err); }
.nx-cc-total { display: flex; justify-content: space-between; align-items: baseline; gap: 12px;
    margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--nx-border); }
.nx-cc-total span { font-size: 13px; font-weight: 700; }
.nx-cc-total b { font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; }
.nx-cc-vacio { font-size: 12.5px; color: var(--nx-txt3); padding: 6px 0; }
.nx-cc-nota { margin: 10px 0 0; font-size: 11px; line-height: 1.5; color: var(--nx-txt3); }

.nx-cc-campo { margin-bottom: 8px; }
.nx-cc-campo label { display: block; font-size: 12px; color: var(--nx-txt2); margin-bottom: 5px; }
.nx-cc-campo .esperado { float: right; font-variant-numeric: tabular-nums; color: var(--nx-txt3); }
.nx-cc-campo input, .nx-cc-campo textarea {
    width: 100%; padding: 10px 12px; background: var(--nx-bg3);
    border: 1px solid var(--nx-border); border-radius: 9px; color: var(--nx-txt1);
    font-size: 16px; font-weight: 600; font-variant-numeric: tabular-nums; outline: none;
}
.nx-cc-campo textarea { font-size: 13px; font-weight: 400; resize: vertical; min-height: 58px; }
.nx-cc-campo input:focus, .nx-cc-campo textarea:focus {
    border-color: var(--nx-a1); box-shadow: 0 0 0 3px rgba(56,189,248,.16);
}
.nx-cc-dif { display: flex; justify-content: space-between; font-size: 12.5px; min-height: 18px; }
.nx-cc-dif b { font-variant-numeric: tabular-nums; }
.nx-cc-dif.ok  b, .nx-cc-dif.ok  span { color: var(--nx-ok); }
.nx-cc-dif.mal b, .nx-cc-dif.mal span { color: var(--nx-err); }

.nx-cc-det { grid-column: 1 / -1; }
.nx-cc-det summary { cursor: pointer; font-size: 12.5px; color: var(--nx-txt3); padding: 4px 0; }
.nx-cc-det summary:hover { color: var(--nx-a1); }
.nx-cc-det table { width: 100%; margin-top: 10px; font-size: 12.5px; border-collapse: collapse; }
.nx-cc-det td, .nx-cc-det th { padding: 6px 0; border-bottom: 1px solid var(--nx-border2); text-align: left; }
.nx-cc-det th { font-size: 10.5px; text-transform: uppercase; letter-spacing: .05em;
    color: var(--nx-txt3); font-weight: 600; }
.nx-cc-det td.num, .nx-cc-det th.num { text-align: right; font-variant-numeric: tabular-nums; }
.nx-cc-det tfoot td { border-bottom: 0; border-top: 1px solid var(--nx-border); }

.nx-cc-pie {
    display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
    padding: 16px var(--pad); border-top: 1px solid var(--nx-border2);
}
.nx-cc-btn {
    padding: 10px 18px; border-radius: 10px; border: 1px solid var(--nx-border);
    background: transparent; color: var(--nx-txt2); font-size: 13px; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center; gap: 7px;
    text-decoration: none !important; transition: all .18s;
}
.nx-cc-btn:hover { border-color: var(--nx-border3); color: var(--nx-txt1); }
.nx-cc-btn.principal { margin-left: auto; background: var(--nx-err); border-color: var(--nx-err); color: #fff; }
.nx-cc-btn.principal:hover { filter: brightness(1.08); color: #fff; }
.nx-cc-btn[disabled] { opacity: .6; cursor: progress; }
.nx-cc-error {
    margin: 0 var(--pad); padding: 11px 14px; border-radius: 10px;
    background: rgba(239,68,68,.12); border: 1px solid var(--nx-err);
    color: var(--nx-err); font-size: 12.5px; line-height: 1.5;
}
.nx-cc-error p { margin: 0; }
.nx-cc-campo.exige input { border-color: var(--nx-err); }

</style>

<div class="nx-cc" id="nxCierre">

    <div class="nx-cc-head">
        <div class="nx-cc-ico"><?= $svg($ico['caja'], 21); ?></div>
        <div>
            <h2><?= lang('cierre_titulo'); ?></h2>
            <p>
                <?= lang('cierre_abierta_el'); ?> <b><?= html_escape($this->tec->hrld($apertura)); ?></b>
                <?php if ($cajero !== ''): ?>
                    &nbsp;·&nbsp; <?= lang('cierre_cajero'); ?>: <b><?= html_escape($cajero); ?></b>
                <?php endif; ?>
            </p>
        </div>
        <button type="button" class="nx-cc-close" data-bs-dismiss="modal" aria-label="Cerrar">&times;</button>
    </div>

    <?php echo form_open($accion, array('id' => 'nxCierreForm')); ?>

    <?php $avisoError = validation_errors() ?: ($error ?? ''); ?>
    <?php if ($avisoError): ?>
    <div class="nx-cc-error"><?= $avisoError; ?></div>
    <?php endif; ?>

    <div class="nx-cc-body">

        <!-- Cobrado por forma de pago -->
        <div class="nx-cc-bloque">
            <h3><?= lang('cierre_cobrado_por'); ?></h3>
            <?php
            $conMovimiento = false;
            if ($r) {
                foreach ($r['metodos'] as $m) {
                    if (!$m['total'] && !$m['pagos']) { continue; }
                    $conMovimiento = true;
                    $cuantos = $m['pagos']
                        ? '<em>' . (int) $m['pagos'] . ' ' . ($m['pagos'] == 1 ? lang('cierre_pago') : lang('cierre_pagos')) . '</em>'
                        : '';
                    echo '<div class="nx-cc-fila"><span>' . html_escape($m['etiqueta']) . $cuantos . '</span><b>'
                       . $this->tec->formatMoney($m['total']) . '</b></div>';
                }
            }
            if (!$conMovimiento) {
                echo '<div class="nx-cc-vacio">' . lang('cierre_sin_movimiento') . '</div>';
            }
            ?>
            <div class="nx-cc-total">
                <span><?= lang('cierre_total_cobrado'); ?></span>
                <b><?= $this->tec->formatMoney($r ? $r['cobrado'] : 0); ?></b>
            </div>
        </div>

        <!-- Efectivo: solo lo que entra o sale del cajon -->
        <div class="nx-cc-bloque">
            <h3><?= lang('cierre_movimientos'); ?></h3>
            <div class="nx-cc-fila">
                <span><?= lang('cierre_fondo_inicial'); ?></span>
                <b><?= $this->tec->formatMoney($r ? $r['fondo'] : 0); ?></b>
            </div>
            <div class="nx-cc-fila">
                <span><?= lang('pago_efectivo'); ?></span>
                <b><?= $this->tec->formatMoney($r ? $r['metodos']['efectivo']['total'] : 0); ?></b>
            </div>
            <?php if ($r && !empty($r['apartados'])): ?>
            <div class="nx-cc-fila">
                <span><?= lang('cierre_apartados'); ?></span><b><?= $this->tec->formatMoney($r['apartados']); ?></b>
            </div>
            <?php endif; ?>
            <?php if ($r && $r['depositos']): ?>
            <div class="nx-cc-fila">
                <span><?= lang('cierre_depositos'); ?></span><b><?= $this->tec->formatMoney($r['depositos']); ?></b>
            </div>
            <?php endif; ?>
            <?php if ($r && $r['gastos']): ?>
            <div class="nx-cc-fila resta">
                <span><?= lang('cierre_gastos'); ?></span><b>− <?= $this->tec->formatMoney($r['gastos']); ?></b>
            </div>
            <?php endif; ?>
            <?php if ($r && !empty($r['anulaciones'])): ?>
            <div class="nx-cc-fila resta">
                <span><?= lang('anu_movimiento_caja'); ?><em><?= (int) $r['anulaciones_n']; ?></em></span>
                <b>− <?= $this->tec->formatMoney($r['anulaciones']); ?></b>
            </div>
            <?php endif; ?>
            <div class="nx-cc-total">
                <span><?= lang('cierre_efectivo_esperado'); ?></span>
                <b id="nxEsperado" data-monto="<?= $r ? (float) $r['efectivo_esperado'] : 0; ?>">
                    <?= $this->tec->formatMoney($r ? $r['efectivo_esperado'] : 0); ?>
                </b>
            </div>
        </div>

        <!-- Facturas anuladas en el turno: que se anulo, por que y que paso con el dinero -->
        <?php if ($r && !empty($r['anulaciones_detalle'])): ?>
        <div class="nx-cc-bloque">
            <h3><?= $svg($ico['anulada'], 13); ?> <?= lang('sales_anuladas'); ?></h3>
            <?php foreach ($r['anulaciones_detalle'] as $anu): ?>
            <div class="nx-cc-fila">
                <span>
                    #<?= (int) $anu->sale_id; ?>
                    <em><?= html_escape(character_limiter($anu->motivo, 46)); ?></em>
                </span>
                <b><?= (float) $anu->monto_devuelto > 0
                        ? $this->tec->formatMoney($anu->monto_devuelto)
                        : lang('anu_sin_devolucion'); ?></b>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- SINPE del turno -->
        <?php if ($r && !empty($r['sinpe']) && $r['sinpe']['entrantes']): ?>
        <div class="nx-cc-bloque">
            <h3><?= $svg($ico['celular'], 13); ?> <?= lang('cierre_sinpe'); ?></h3>
            <div class="nx-cc-fila">
                <span><?= lang('cierre_sinpe_entrantes'); ?><em><?= (int) $r['sinpe']['entrantes']; ?></em></span>
                <b><?= $this->tec->formatMoney($r['sinpe']['monto']); ?></b>
            </div>
            <div class="nx-cc-fila">
                <span><?= lang('cierre_sinpe_aplicados'); ?><em><?= (int) $r['sinpe']['aplicados']; ?></em></span>
                <b><?= $this->tec->formatMoney($r['sinpe']['monto_aplicado']); ?></b>
            </div>
            <?php $sinAplicar = (int) $r['sinpe']['entrantes'] - (int) $r['sinpe']['aplicados']; ?>
            <?php if ($sinAplicar > 0): ?>
            <div class="nx-cc-fila">
                <span><?= lang('cierre_sinpe_sin_aplicar'); ?><em><?= $sinAplicar; ?></em></span>
                <b><?= $this->tec->formatMoney($r['sinpe']['monto'] - $r['sinpe']['monto_aplicado']); ?></b>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Lo que no pasa por la gaveta -->
        <?php if ($r && ($r['notas_credito'] || $r['ventas_credito'])): ?>
        <div class="nx-cc-bloque">
            <h3><?= lang('cierre_otros_movimientos'); ?></h3>
            <?php if ($r['ventas_credito']): ?>
            <div class="nx-cc-fila">
                <span><?= lang('cierre_ventas_credito'); ?></span><b><?= $this->tec->formatMoney($r['ventas_credito']); ?></b>
            </div>
            <?php endif; ?>
            <?php if ($r['notas_credito']): ?>
            <div class="nx-cc-fila">
                <span><?= lang('cierre_notas_credito'); ?></span><b><?= $this->tec->formatMoney($r['notas_credito']); ?></b>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Arqueo: solo se cuenta el efectivo del cajon -->
        <?php if (!$soloLectura): ?>
        <div class="nx-cc-bloque" style="grid-column: 1 / -1;">
            <h3><?= lang('cierre_arqueo'); ?></h3>
            <p style="margin:-6px 0 14px;font-size:12px;color:var(--nx-txt3);"><?= lang('cierre_arqueo_ayuda'); ?></p>

            <div style="display:grid;gap:0 20px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                <div>
                    <div class="nx-cc-campo">
                        <label for="total_cash_submitted">
                            <?= lang('cierre_efectivo_contado'); ?>
                            <span class="esperado"><?= $this->tec->formatMoney($r ? $r['efectivo_esperado'] : 0); ?></span>
                        </label>
                        <input type="text" inputmode="decimal" id="total_cash_submitted" name="total_cash_submitted"
                               value="" placeholder="0" autocomplete="off" required>
                    </div>
                    <div class="nx-cc-dif" id="nxDifEfectivo"></div>
                </div>
                <div class="nx-cc-campo">
                    <label for="note"><?= lang('cierre_nota'); ?></label>
                    <textarea id="note" name="note" rows="2" placeholder="—"></textarea>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detalle de impuestos -->
        <?php if ($impuestos): ?>
        <details class="nx-cc-det">
            <summary><?= lang('cierre_detalle_impuestos'); ?></summary>
            <table>
                <thead>
                    <tr>
                        <th><?= lang('tax'); ?></th>
                        <th class="num"><?= lang('cierre_lineas'); ?></th>
                        <th class="num"><?= lang('total'); ?></th>
                        <th class="num"><?= lang('cierre_impuesto'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sumSub = 0; $sumImp = 0;
                foreach ($impuestos as $t):
                    $sumSub += $t['subtotal']; $sumImp += $t['impuesto']; ?>
                    <tr>
                        <td><?= $t['tasa'] > 0
                                ? rtrim(rtrim(number_format($t['tasa'], 2, '.', ''), '0'), '.') . '%'
                                : lang('ventas_exentas'); ?></td>
                        <td class="num"><?= (int) $t['lineas']; ?></td>
                        <td class="num"><?= $this->tec->formatMoney($t['subtotal']); ?></td>
                        <td class="num"><?= $t['impuesto'] ? $this->tec->formatMoney($t['impuesto']) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><b><?= lang('total'); ?></b></td>
                        <td class="num"><b><?= $this->tec->formatMoney($sumSub); ?></b></td>
                        <td class="num"><b><?= $this->tec->formatMoney($sumImp); ?></b></td>
                    </tr>
                </tfoot>
            </table>
        </details>
        <?php endif; ?>

    </div>

    <?php
    // Lo unico que el controlador lee del POST ademas del efectivo contado y la
    // nota. Los vouchers ya no se recuentan: se guarda lo que dice el sistema.
    echo form_hidden('total_cc_submitted', $r ? number_format($r['metodos']['tarjeta']['total'], 2, '.', '') : '0');
    echo form_hidden('total_cc_slips_submitted', $r ? (int) $r['metodos']['tarjeta']['pagos'] : 0);
    echo form_hidden('total_cheques_submitted', $r ? number_format($r['metodos']['cheque']['total'], 2, '.', '') : '0');
    ?>

    <div class="nx-cc-pie">
        <?php if (!$soloLectura): ?>
        <a href="<?= site_url('pos/products_sales_in_register'); ?>" target="_blank" class="nx-cc-btn">
            <?= $svg($ico['lista'], 15); ?> <span><?= lang('cierre_articulos_corto'); ?></span>
        </a>
        <button type="button" class="nx-cc-btn" id="nxCierreCorreo">
            <?= $svg($ico['correo'], 15); ?> <span><?= lang('cierre_enviar_correo'); ?></span>
        </button>
        <?php endif; ?>
        <a href="<?= site_url('pos/cierre_pdf'); ?>" target="_blank" class="nx-cc-btn" id="nxCierreImprimir">
            <?= $svg($ico['imprimir'], 15); ?> <span><?= lang('cierre_imprimir'); ?></span>
        </a>
        <?php if (!$soloLectura): ?>
        <button type="submit" name="close_register" value="1" class="nx-cc-btn principal" id="nxCierreGuardar">
            <?= $svg($ico['candado'], 15); ?> <span><?= lang('cierre_cerrar'); ?></span>
        </button>
        <?php endif; ?>
    </div>

    <?php echo form_close(); ?>
</div>

<script>
(function () {
    var raiz = document.getElementById('nxCierre');
    if (!raiz) { return; }

    var nodoEsp  = document.getElementById('nxEsperado');
    var esperado = nodoEsp ? (parseFloat(nodoEsp.getAttribute('data-monto')) || 0) : 0;
    var contado  = document.getElementById('total_cash_submitted');
    var difCaja  = document.getElementById('nxDifEfectivo');

    var T = {
        sobrante: <?= json_encode(lang('cierre_sobrante')); ?>,
        faltante: <?= json_encode(lang('cierre_faltante')); ?>,
        cuadra:   <?= json_encode(lang('cierre_cuadra')); ?>,
        confirma: <?= json_encode(lang('cierre_confirmar')); ?>,
        exigeNota: <?= json_encode(lang('cierre_nota_obligatoria')); ?>,
        simbolo:  <?= json_encode($Settings->symbol ?? '₡'); ?>
    };

    function money(n) {
        return (n < 0 ? '-' : '') + T.simbolo +
               Math.abs(n).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function pintarDiferencia() {
        if (!contado || !difCaja) { return; }
        var v = parseFloat(String(contado.value).replace(',', '.'));
        if (isNaN(v)) { difCaja.className = 'nx-cc-dif'; difCaja.innerHTML = ''; return; }
        var d = v - esperado;
        var cuadra = Math.abs(d) < 0.005;
        difCaja.className = 'nx-cc-dif ' + (cuadra ? 'ok' : 'mal');
        difCaja.innerHTML = '<span>' + (cuadra ? T.cuadra : (d > 0 ? T.sobrante : T.faltante)) +
                            '</span><b>' + (cuadra ? '' : money(d)) + '</b>';
    }

    if (contado) {
        contado.addEventListener('input', pintarDiferencia);
        contado.focus();
    }

    var btnCorreo = document.getElementById('nxCierreCorreo');
    if (btnCorreo) {
        btnCorreo.addEventListener('click', function () {
            var texto = btnCorreo.querySelector('span');
            btnCorreo.disabled = true;
            var cuerpo = new FormData();
            cuerpo.set('user_id', '<?= (int) $user_id; ?>');
            cuerpo.set('<?= $this->security->get_csrf_token_name(); ?>', '<?= $this->security->get_csrf_hash(); ?>');
            fetch('<?= site_url('pos/enviar_cierre'); ?>', {
                method: 'POST', body: cuerpo, credentials: 'same-origin'
            })
            .then(function (res) { return res.json().catch(function () { return { ok: res.ok }; }); })
            .then(function (data) {
                if (texto && data.msg) { texto.textContent = data.msg; }
                if (!data.ok) { btnCorreo.disabled = false; }
            })
            .catch(function () { btnCorreo.disabled = false; });
        });
    }

    var nota = document.getElementById('note');

    /** Un faltante hay que explicarlo; el servidor lo vuelve a revisar. */
    function faltaDinero() {
        if (!contado) { return false; }
        var v = parseFloat(String(contado.value).replace(',', '.'));
        return !isNaN(v) && v + 0.005 < esperado;
    }

    var form = document.getElementById('nxCierreForm');
    var btnGuardar = document.getElementById('nxCierreGuardar');
    if (form && btnGuardar) {
        form.addEventListener('submit', function (e) {
            if (faltaDinero() && nota && nota.value.trim() === '') {
                e.preventDefault();
                nota.parentNode.classList.add('exige');
                nota.setAttribute('placeholder', T.exigeNota);
                nota.focus();
                return;
            }
            if (!window.confirm(T.confirma)) { e.preventDefault(); return; }
            btnGuardar.disabled = true;
        });
    }
    if (nota) {
        nota.addEventListener('input', function () { nota.parentNode.classList.remove('exige'); });
    }
})();
</script>
