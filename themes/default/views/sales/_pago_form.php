<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Formulario de pago, compartido por la venta y el apartado.
 * $apartado dice cuál de los dos: cambia el destino del formulario y nada más.
 */
$apartado = !empty($apartado);
$destino  = $apartado
    ? 'sales/add_payment_apartado/' . $inv->id . '/' . $inv->customer_id
    : 'sales/add_payment/' . $inv->id . '/' . $inv->customer_id;

$pendiente = (float) $inv->grand_total - (float) $inv->paid;
if ($pendiente < 0) { $pendiente = 0; }

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('add_payment'); ?>
        <small><?= html_escape($inv->reference_no ?? '') ?> — <?= html_escape($inv->customer ?? ''); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url($apartado ? 'sales/apartado' : 'sales'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('sales'); ?>
        </a>
    </div>
</div>

<?= form_open_multipart($destino, 'id="nxfPago"'); ?>
<div class="nxf-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err"><div><?= $error; ?></div></div>
    <?php } ?>

    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title">
                <?= lang('pago_paso_monto'); ?>
                <small><?= lang('total'); ?>: <?= $this->tec->formatMoney($inv->grand_total); ?> ·
                       <?= lang('paid'); ?>: <?= $this->tec->formatMoney($inv->paid); ?> ·
                       <?= lang('balance'); ?>: <strong><?= $this->tec->formatMoney($pendiente); ?></strong></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="amount"><?= lang('amount'); ?> <span class="req">*</span></label>
                    <input type="number" step="any" min="0" name="amount-paid" id="amount" class="nxf-input mono" required
                           value="<?= set_value('amount-paid', $this->tec->formatDecimal($pendiente)); ?>">
                    <div class="nxf-hint" id="hintMonto"></div>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="paid_by"><?= lang('paying_by'); ?> <span class="req">*</span></label>
                    <select name="paid_by" id="paid_by" class="nxf-select" required>
                        <option value="cash"><?= lang('cash'); ?></option>
                        <option value="CC"><?= lang('tarjeta'); ?></option>
                        <option value="Cheque"><?= lang('cheque'); ?></option>
                        <option value="deposit"><?= lang('transferencia'); ?></option>
                        <option value="sinpe">SINPE Móvil</option>
                    </select>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="date"><?= lang('date'); ?></label>
                    <input type="datetime-local" name="date" id="date" class="nxf-input"
                           value="<?= set_value('date', date('Y-m-d\TH:i')); ?>" <?= $Admin ? '' : 'readonly'; ?>>
                    <?php if (!$Admin) { ?><div class="nxf-hint"><?= lang('pago_fecha_solo_admin'); ?></div><?php } ?>
                </div>

                <div class="nxf-field sp-6" data-medio="CC Cheque deposit sinpe">
                    <label class="nxf-label" for="reference"><?= lang('reference'); ?></label>
                    <input type="text" name="reference" id="reference" class="nxf-input mono" maxlength="50"
                           value="<?= set_value('reference'); ?>">
                    <div class="nxf-hint"><?= lang('pago_referencia_ayuda'); ?></div>
                </div>

                <div class="nxf-field sp-6" data-medio="CC" hidden>
                    <label class="nxf-label" for="pcc_type"><?= lang('card_type'); ?></label>
                    <select name="pcc_type" id="pcc_type" class="nxf-select">
                        <option value="Debito"><?= lang('debito'); ?></option>
                        <option value="Visa">Visa</option>
                        <option value="MasterCard">MasterCard</option>
                    </select>
                </div>

                <div class="nxf-field sp-6" data-medio="Cheque" hidden>
                    <label class="nxf-label" for="cheque_no"><?= lang('cheque_no'); ?></label>
                    <input type="text" name="cheque_no" id="cheque_no" class="nxf-input mono" maxlength="50"
                           value="<?= set_value('cheque_no'); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title"><?= lang('pago_paso_respaldo'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="attachment"><?= lang('attachment'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="file" name="userfile" id="attachment" class="nxf-input">
                </div>
                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="note"><?= lang('note'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <textarea name="note" id="note" class="nxf-textarea"><?= set_value('note'); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url($apartado ? 'sales/apartado' : 'sales'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="submit" class="nxf-btn" name="add_payment" value="1">
            <?= $icono($ico_check); ?> <?= lang('add_payment'); ?>
        </button>
    </div>
</div>
<?= form_close(); ?>

<script>
(function () {
    'use strict';

    var PENDIENTE = <?= json_encode((float) $pendiente); ?>;
    var T = {
        sobra: <?= json_encode(lang('pago_excede_saldo')); ?>,
        falta: <?= json_encode(lang('pago_queda_pendiente')); ?>,
        exacto: <?= json_encode(lang('pago_salda_la_venta')); ?>,
        refRequerida: <?= json_encode(lang('pago_referencia_requerida')); ?>
    };

    var $ = function (id) { return document.getElementById(id); };
    var medio = $('paid_by'), monto = $('amount'), hint = $('hintMonto');

    // Cada medio de pago muestra solo sus campos: el resto no se dibuja.
    function aplicarMedio() {
        document.querySelectorAll('[data-medio]').forEach(function (campo) {
            campo.hidden = campo.dataset.medio.split(' ').indexOf(medio.value) === -1;
        });
    }

    function fmt(n) { return n.toFixed(2); }

    function revisarMonto() {
        var v = parseFloat(monto.value.replace(',', '.'));
        if (isNaN(v) || v <= 0) { hint.textContent = ''; return; }
        var dif = +(PENDIENTE - v).toFixed(2);
        if (dif > 0)      { hint.textContent = T.falta.replace('%s', fmt(dif)); }
        else if (dif < 0) { hint.textContent = T.sobra.replace('%s', fmt(-dif)); }
        else              { hint.textContent = T.exacto; }
    }

    medio.addEventListener('change', aplicarMedio);
    monto.addEventListener('input', revisarMonto);

    $('nxfPago').addEventListener('submit', function (e) {
        // Todo lo que no es efectivo deja rastro: sin referencia no se concilia.
        if (medio.value !== 'cash' && !$('reference').value.trim()) {
            e.preventDefault();
            $('reference').focus();
            alert(T.refRequerida);
        }
    });

    aplicarMedio();
    revisarMonto();
})();
</script>
