<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<?php
/* =====================================================================
 | Pie del comprobante: pie de recibo de la tienda, clave de Hacienda,
 | identificacion interna en QR y leyenda legal.
 |
 | $invoiceqr es opcional; si no viene, cae al codigo de barras lineal
 | ($invoicebarcode) y, si tampoco hay, no pinta nada.
 * =================================================================== */
$invoiceqr      = isset($invoiceqr) ? $invoiceqr : '';
$invoicebarcode = isset($invoicebarcode) ? $invoicebarcode : '';

// Leyenda legal: las notas de credito usan la suya. Se puede forzar con $pie_legal.
if (!isset($pie_legal)) {
    $pie_legal = !empty($Settings->footer_hacienda_fe) ? $Settings->footer_hacienda_fe : '';
}
$pie_gracias = isset($pie_gracias) ? $pie_gracias : '¡Gracias por su compra!';
?>
<div class="inv-foot">
    <?php if (!empty($store->receipt_footer)) { ?>
        <div style="font-size:11.5px; color:#475569; margin-bottom:12px;">
            <?= nl2br($store->receipt_footer ?? '') ?>
        </div>
    <?php } ?>

    <?php if (!empty($Settings->fe) && $Settings->fe == 1 && !empty($hacienda->clave)) { ?>
        <div class="inv-clave-label"><?= lang('electronic_voucher_key') ?></div>
        <div class="inv-clave"><?= html_escape($hacienda->clave) ?></div>
    <?php } ?>

    <?php if (!empty($invoiceqr)) { ?>
        <div class="inv-qr">
            <div class="inv-clave-label"><?= lang('internal_id') ?></div>
            <?= $invoiceqr ?>
            <?php if (!empty($hacienda->consecutivo)) { ?>
                <div class="inv-qr-cap"><?= html_escape($hacienda->consecutivo) ?></div>
            <?php } ?>
        </div>
    <?php } elseif (!empty($invoicebarcode)) { ?>
        <div class="inv-barcode">
            <div class="inv-clave-label"><?= lang('internal_id') ?></div>
            <?= $invoicebarcode ?>
        </div>
    <?php } ?>

    <?php if (!empty($pie_legal)) { ?>
        <div class="inv-legalnote"><?= html_escape($pie_legal) ?></div>
    <?php } ?>

    <?php if (!empty($pie_gracias)) { ?>
        <div class="inv-thanks"><?= html_escape($pie_gracias) ?></div>
    <?php } ?>
</div>
