<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<?php
/* =====================================================================
 | Tabla de lineas del comprobante.
 |
 | Sirve para facturas, tiquetes, notas de credito/debito y proformas:
 | nx_linea_comprobante() (en _comprobante_datos.php) unifica los nombres
 | de columna de cada tabla de lineas.
 |
 | Requiere: $rows, $Settings
 * =================================================================== */
?>
<table class="inv-items">
    <thead>
    <tr>
        <th style="width:6%;" class="mid">#</th>
        <th style="width:44%;"><?= lang('description') ?></th>
        <th style="width:14%;" class="mid"><?= lang('quantity') ?></th>
        <th style="width:18%;" class="num"><?= lang('price') ?></th>
        <th style="width:18%;" class="num"><?= lang('subtotal') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $ln = 0;
    if (!empty($rows)) {
        foreach ($rows as $row) {
            $ln++;
            $l = nx_linea_comprobante($row);
            ?>
            <tr<?= $ln % 2 == 0 ? ' class="alt"' : '' ?>>
                <td class="mid" style="color:#94a3b8;"><?= $ln ?></td>
                <td>
                    <span class="it-name"><?= html_escape($row->product_name) ?></span>
                    <span class="it-tag <?= $l['gravado'] ? 'it-tag-g' : 'it-tag-e' ?>"><?= html_escape($l['iva']) ?></span>
                    <?php if (!empty($row->product_code)) { ?>
                        <div class="it-code"><?= html_escape($row->product_code) ?></div>
                    <?php } ?>
                </td>
                <td class="mid">
                    <?= $this->tec->formatQuantity($row->quantity) ?><?= $l['unidad'] !== '' ? ' ' . html_escape($l['unidad']) : '' ?>
                </td>
                <td class="num"><?= number_format($l['precio'], $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                <td class="num"><?= number_format($l['subtotal'], $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
            </tr>
            <?php
        }
    }
    if ($ln === 0) { ?>
        <tr><td colspan="5" class="inv-empty"><?= lang('no_data_available') ?></td></tr>
    <?php } ?>
    </tbody>
</table>
