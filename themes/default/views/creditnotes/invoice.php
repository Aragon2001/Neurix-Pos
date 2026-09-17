<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed') ?>
<?php include FCPATH . 'themes/default/views/pos/_comprobante_datos.php'; ?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Example 2</title>
        <link href="<?= $assets ?>dist/css/invoice/style.css" rel="stylesheet" type="text/css" media="all"  />
    </head>
    <body>
        <header class="clearfix">
            <div id="logo" >
                <?php if ($logo_file) { ?>
                    <img id="logoimg" src="<?= base_url('uploads/' . $logo_file) ?>" alt="<?= html_escape($emisor_nombre) ?>">
                <?php } else { ?>
                    <div id="logomono"><?= html_escape($monograma) ?></div>
                <?php } ?>
            </div>
            <div id="company">
                <h2 class="name"><?= $Settings->nombre_emisor ?></h2>
                <div><b><?= $Settings->cedula_emisor ?></b></div>
                <div><?= html_escape($Settings->otras_senas); ?></div>
                <div>(506) <?= $Settings->telefono_emisor ?></div>
                <div><a href="mailto:<?= $Settings->email_emisor ?>"><?= $Settings->email_emisor ?></a></div>
            </div>
    </header>
    <main>
        <div id="details" class="clearfix">
            <div id="client">
                <div class="to">Facturado a:</div>
                <h2 class="name"><?= html_escape($customer->name); ?> <?= $customer->business_name ? "(" . $customer->business_name . ")" : '' ?></h2>
                <div class="email"><a href="<?= html_escape($customer->email); ?>"><?= html_escape($customer->email); ?></a></div>
                <div class="email">
                    <?php if($customer->cf1 == "01"){ ?>
                        <?= lang("Cedula Identidad") . ': ' . $customer->cf2; ?>
                    <?php }else if($customer->cf1 == "02"){ ?>
                        <?= lang("Cedula Juridica") . ': ' . $customer->cf2; ?>
                    <?php }else if($customer->cf1 == "03"){ ?>
                        <?= lang("Dimex") . ': ' . $customer->cf2; ?>
                    <?php }else if($customer->cf1 == "04"){ ?>
                        <?= lang("NITE") . ': ' . $customer->cf2; ?>
                    <?php } ?>
                </div>
            </div>
            <div id="invoice">
                <h1>
                        <?= lang('elect_credit_note'); ?>
                </h1>
                <p><b><?= $hacienda->consecutivo ?></b></p>
                <h1><?= lang('clave'); ?></h1>
                <p><b><?= $hacienda->clave ?></b></p>
                <div class="date"><?= lang('date'); ?>: <?= $inv->date ?></div>
            </div>
        </div>
                <div class="table-responsive">
        <table border="0" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th class="no"><?= lang('col_cod'); ?></th>
                    <th class="desc"><?= lang('col_descripcion'); ?></th>
                    <th class="unit"><?= lang('col_precio'); ?></th>
                    <th class="qty"><?= lang('col_cantidad'); ?></th>
                    <th class="total"><?= lang('col_total'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) { ?>
                    <tr>
                        <td class="no"><?= $row->product_code ?></td>
                        <td class="desc"><?= html_escape($row->product_name); ?></td>
                        <td class="unit"><?= number_format(nx_linea_comprobante($row)['precio'], $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                        <td class="qty"><?= $row->quantity ?></td>

                        <td class="total"><?= number_format($row->subtotal, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>

                <tr>
                    <td colspan="2"></td>
                    <td colspan="2"><?= lang('subtotal'); ?></td>
                    <td><?= number_format($inv->total, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                </tr>
                <?php if ($Settings->enable_show_tax) { ?>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2"><?= $Settings->enable_show_tax ?></td>
                        <td><?= number_format(($inv->product_tax ?? 0), $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2"><?= lang('grand_total'); ?></td>
                    <td><?= number_format($inv->grand_total, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                </tr>
            </tfoot>
        </table>
                </div>
        <div id="thanks">Gracias por su compra!</div>

        <?php if ($inv->note) { ?>
            <div id="notices">
                <div><?= lang('notas_label'); ?>:</div>
                <div class="notice"><?= html_escape($inv->note); ?></div>
            </div>
        <?php } ?>
    </main>
    <footer>

        <?= $Settings->footer_hacienda_fe ?>
    </footer>
</body>
</html>