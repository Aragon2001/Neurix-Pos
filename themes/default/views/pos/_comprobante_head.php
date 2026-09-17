<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<?php
/* =====================================================================
 | Encabezado del comprobante: marca del emisor a la izquierda y datos
 | del documento a la derecha.
 |
 | Requiere haber incluido antes _comprobante_datos.php.
 | Variables opcionales:
 |   $doc_num_label / $doc_num_valor  para documentos sin consecutivo
 |                                    de Hacienda (proforma, apartado...)
 * =================================================================== */
$doc_num_label = isset($doc_num_label) ? $doc_num_label : null;
$doc_num_valor = isset($doc_num_valor) ? $doc_num_valor : null;
?>
<table class="inv-head">
    <tr>
        <td class="col-brand">
            <table style="border-collapse:collapse;">
                <tr>
                    <td class="inv-logo">
                        <?php if ($logo_file) { ?>
                            <img src="<?= base_url('uploads/' . $logo_file) ?>" alt="<?= html_escape($emisor_nombre) ?>">
                        <?php } else { ?>
                            <div class="inv-monogram"><?= html_escape($monograma) ?></div>
                        <?php } ?>
                    </td>
                    <td class="inv-brandtxt">
                        <div class="inv-company"><?= html_escape($emisor_nombre) ?></div>
                        <?php if ($emisor_razon && $emisor_razon !== $emisor_nombre) { ?>
                            <div class="inv-legal"><?= html_escape($emisor_razon) ?></div>
                        <?php } ?>
                        <div class="inv-contact">
                            <?php if (!empty($Settings->cedula_emisor)) { ?>
                                Céd. Jurídica <?= html_escape($Settings->cedula_emisor) ?><br>
                            <?php } ?>
                            <?php if (!empty($Settings->otras_senas)) { ?>
                                <?= html_escape($Settings->otras_senas) ?><br>
                            <?php } elseif (!empty($store->address1)) { ?>
                                <?= html_escape(trim($store->address1 . ' ' . @$store->address2 . ' ' . @$store->city)) ?><br>
                            <?php } ?>
                            <?php if (!empty($Settings->telefono_emisor)) { ?>
                                Tel. <?= html_escape($Settings->telefono_emisor) ?>
                            <?php } elseif (!empty($store->phone)) { ?>
                                Tel. <?= html_escape($store->phone) ?>
                            <?php } ?>
                            <?php if (!empty($Settings->email_emisor)) { ?>
                                &nbsp;·&nbsp; <?= html_escape($Settings->email_emisor) ?>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        <td class="col-doc">
            <div class="inv-doc-badge"><?= html_escape($doc_titulo) ?></div>
            <?php if ($doc_num_label !== null) { ?>
                <div class="inv-doc-line"><?= html_escape($doc_num_label) ?>
                    <b class="inv-consec"><?= html_escape($doc_num_valor) ?></b>
                </div>
            <?php } elseif (!empty($hacienda->consecutivo)) { ?>
                <div class="inv-doc-line">Consecutivo
                    <b class="inv-consec"><?= html_escape($hacienda->consecutivo) ?></b>
                </div>
            <?php } else { ?>
                <div class="inv-doc-line"><?= lang('reference') ?>
                    <b>#<?= html_escape($inv->id) ?></b>
                </div>
            <?php } ?>
            <div class="inv-doc-line"><?= lang('date') ?>
                <b><?= $this->tec->hrld($inv->date) ?></b>
            </div>
        </td>
    </tr>
</table>
