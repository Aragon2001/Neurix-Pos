<?php (defined('BASEPATH')) OR exit('No direct script access allowed') ?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Example 2</title>
        <link href="<?= $assets ?>dist/css/invoice/style.css" rel="stylesheet" type="text/css" media="all"  />
    </head>
    <body>
        <header class="clearfix">
            <div id="logo" >
                <img id="logoimg" src="<?= base_url('uploads/logo.png') ?>">
            </div>
            <div id="company">
                <h2 class="name"><?= $Settings->nombre_emisor ?></h2>
                <div><b><?= $Settings->cedula_emisor ?></b></div>
                <div><?= $Settings->otras_senas ?></div>
                <div>(506) <?= $Settings->telefono_emisor ?></div>
                <div><a href="mailto:<?= $Settings->email_emisor ?>"><?= $Settings->email_emisor ?></a></div>
            </div>
    </header>
    <main>
        <div id="details" class="clearfix">
            <div id="client">
                <div class="to">Facturado a:</div>
                <h2 class="name"><?= $customer->name ?> <?= $customer->business_name ? "(" . $customer->business_name . ")" : '' ?></h2>
                <div class="email"><a href="<?= $customer->email ?>"><?= $customer->email ?></a></div>
                <div class="email">
                    <? if($customer->cf1 == "01"){ ?>
                        <?= lang("Cedula Identidad") . ': ' . $customer->cf2; ?>
                    <? }else if($customer->cf1 == "02"){ ?>
                        <?= lang("Cedula Juridica") . ': ' . $customer->cf2; ?>
                    <? }else if($customer->cf1 == "03"){ ?>
                        <?= lang("Dimex") . ': ' . $customer->cf2; ?>
                    <? }else if($customer->cf1 == "04"){ ?>
                        <?= lang("NITE") . ': ' . $customer->cf2; ?>
                    <? } ?>
                </div>
            </div>
            <div id="invoice">
                <h1>
                 <? if($hacienda->tipo_doc === "4"){ ?>
                                <b><?= lang("electronic_bill") ?></b><br>
                                <? }else if($hacienda->tipo_doc === "1"){ ?>
                                <b><?= lang("Factura Electronica") ?></b><br>
                                <? } ?>
                </h1>
                <p><b><?= $hacienda->consecutivo ?></b></p>
                <h1>Clave</h1>
                <p><b><?= $hacienda->clave ?></b></p>
                <div class="date">Fecha: <?= $inv->date ?></div>
            </div>
        </div>
                <div class="table-responsive">
        <table border="0" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th class="no">COD.</th>
                    <th class="desc">DESCRIPCION</th>
                    <th class="unit">PRECIO</th>
                    <th class="qty">CANTIDAD</th>
                    <th class="qty">UNIDAD MEDIDA</th>
                    <th class="total">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) { ?>
                    <tr>
                        <td class="no"><?= $row->product_code ?></td>
                        <td class="desc"><?= $row->product_name;            
                             if ($row->tax > 0) {
                                echo  "(G " . $row->tax . "%)";
                            } else {
                                echo "(E)";
                            } ?></td>
                        <td class="unit"><?= number_format($row->net_unit_price, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                        <td class="qty"><?= $row->quantity ?></td>
                        <td class="qty"><?= $row->unit_of_measurement ?></td>
                        <td class="total"><?= number_format($row->subtotal, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>

                <tr>
                    <td colspan="2"></td>
                    <td colspan="2">Sub-Total</td>
                    <td><?= number_format($inv->total, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                </tr>
                <?php if ($Settings->enable_show_tax) { ?>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2"><?= $Settings->enable_show_tax ?></td>
                        <td><?= number_format($inv->product_tax, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                    </tr>
                <?php } ?>
                <?php if ($inv->PorcentajeExoneracion>0) { ?>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2">Total Exoneracion</td>
                        <td><?= number_format(-$inv->MontoExoneracion, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="2">Total General</td>
                    <td><?= number_format($inv->grand_total, $Settings->decimals, $Settings->decimals_sep, $Settings->thousands_sep) ?></td>
                </tr>
            </tfoot>
        </table>
                </div>
        <div class="text-center" >Gravado (G) , Exento (E)</div>
        <div id="thanks">Gracias por su compra!</div>

        <?php if ($inv->note) { ?>
            <div id="notices">
                <div>Notas:</div>
                <div class="notice"><?= $inv->note ?></div>
            </div>
        <?php } ?>
    </main>
    <footer>

        <?= $Settings->footer_hacienda_fe ?>
    </footer>
</body>
</html>