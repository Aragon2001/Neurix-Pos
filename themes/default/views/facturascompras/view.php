<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<section class="content" >
<div class="btn-group  float-start" role="group" aria-label="...">
    <div class="btn-group" role="group2">
        <button type="button" class="btn  btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-file-pdf-o"></i>
        PDF <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" role="menu2">
        <li><a onclick="imprimir()"target="_blank"><?= lang('ver_pdf'); ?></a></li>
        </li>
        </ul>
    </div>
</div>
    <div id="divImprimir">
        <!-- Page header -->
                        <div class="row animated fadeInDown">

                            <div class="col-lg-12" id="sysfrm_ajaxrender">
                                <div class="ibox float-e-margins">
                                    <input name="iid" value="<?=$inv->id?>" id="iid" type="hidden">
                                    <div class="ibox-content">

                                        <div class="invoice">

                                            <header class="clearfix">
                                                <div class="row">
                                                    <div class="col-sm-6 mt-md">
                                                        <h2 class="h2 mt-none mb-sm text-dark text-bold">FACTURA</h2>
                                                        <h4 class="h4 m-none text-dark text-bold">
                                                             <?=$hacienda->consecutivo?></h4>
                                                        <i>
                                                            <?php if ($inv->status == 'Unpaid') { ?>
                                                            <h3 class="pluma alert float-start alert-danger"><?= lang('no_pagada'); ?></h3>
                                                            <?php }else if ($inv->status == 'Paid') { ?>
                                                            <h3 class="pluma alert float-start alert-success"><?= lang('pagada'); ?></h3>
                                                            <?php } else if ($inv->status == 'Partially Paid') { ?>
                                                            <h3 class="pluma alert float-start alert-info"><?= lang('parcialmente_pagada'); ?></h3>
                                                            <?php } else { ?>
                                                            <h3 class="pluma alert float-start alert-info"><?= $inv->status ?></h3>
                                                            <?php } ?>
                                                        </i>
                                                        <br/>
                                                        <br/>
                                                        <br/>
                                                        <h4><b><?= lang('mensaje_hacienda'); ?>:</b> <span
                                                                    style="color: <?=$hacienda->estatus_hacienda != 'aceptado'? 'red' : 'green'?>;">Comprobante Electronico <?=$hacienda->estatus_hacienda?></span>
                                                        </h4>
                                                    </div>
                                                    <div class="col-sm-6 text-right mt-md mb-md float-end">
                                                        <div class="ib">
                                                                <div style="text-align: right; width: 100%; font-size: 14px !important;"><b><?= lang('electronic_voucher_key') ?>:<br> <?= $hacienda->clave; ?></b></div>
                                                                <div style="text-align: right;width: 100%;"><br/><?= lang('internal_id') ?>
                                                                    <br/><?= $invoicebarcode; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </header>

                                            <div class="bill-info">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="bill-to">
                                                            <p class="h5 mb-xs text-dark text-semibold"><strong><?= lang('facturado_a'); ?>:</strong></p>
                                                            <address>
                                                                <?= html_escape($customer->name); ?>
                                                                <br>
                                                                <?= $local['direccion'] ?> <br>
                                                                <?=$local['nombre_distrito']?> - <?=$local['nombre_canton']?>
                                                                - <?=$local['nombre_provincia']?>.
                                                                <br>
                                                                <strong><?= lang('phone'); ?>:</strong> <?= html_escape($customer->phone); ?>
                                                                <br>
                                                                <strong><?= lang('email'); ?>:</strong> <?= html_escape($customer->email); ?>
                                                            </address>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="bill-data text-right">
                                                            <p class="mb-none">
                                                                <span class="text-dark"><?= lang('fecha_factura'); ?>:</span>
                                                                <span class="value"><?= date('Y-m-d', strtotime($hacienda->fecha_emision)) ?></span>
                                                            </p>
                                                            <h2><?= lang('importe_total'); ?>:                 ¢ <?= number_format($inv->grand_total, 2, '.', '') ?></h2>
                                                            <?php if ($inv->paid != '0.00') { ?>
                                                            <h2><?= lang('total_pagado'); ?>: ¢ <?= number_format($inv->paid, 2, '.', '') ?> </h2>
                                                            <h2><?= lang('monto_adeudado'); ?>:¢ <?= number_format($inv->grand_total - $inv->paid, 2, '.', '') ?> </h2>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive saltopagina">
                <div class="table-responsive">
                                                <table class="table invoice-items">
                                                    <thead>
                                                    <tr class="h4 text-dark">
                                                        <th id="cell-id" class="text-semibold">#</th>
                                                        <th id="cell-item" class="text-semibold"><?= lang('articulo'); ?></th>
                                                        <th id="cell-price" class="text-center text-semibold"><?= lang('price'); ?></th>
                                                        <th id="cell-qty" class="text-center text-semibold"><?= lang('cant'); ?></th>
                                                        <th id="cell-total" class="text-center text-semibold"><?= lang('monto_total'); ?></th>
                                                        <th id="cell-total" class="text-center text-semibold"><?= lang('discount'); ?></th>
                                                        <th id="cell-total" class="text-center text-semibold"><?= lang('subtotal'); ?></th>
                                                        <th id="cell-total" class="text-center text-semibold"><?= lang('imp'); ?></th>

                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php foreach ($rows as $item) { ?>
                                                    <tr>
                                                        <td><?= $item->product_code ?></td>
                                                        <td class="text-semibold text-dark"><?= html_escape($item->product_name); ?></td>

                                                        <td class="text-right">¢ <?= number_format($item->unit_price, 2, '.', '') ?></td>
                                                        <td class="text-center"><?= $item->quantity ?></td>
                                                        <td class="text-right">¢ <?= number_format($item->unit_price, 2, '.', '') * number_format($item->quantity, 2, '.', '') ?></td>
                                                        <td class="text-right">¢ <?= number_format($item->item_discount, 2, '.', '') ?></td>
                                                        <td class="text-right">¢ <?= number_format($item->subtotal, 2, '.', '') ?></td>
                                                        <td class="text-right">¢ <?= number_format($item->item_tax, 2, '.', '') ?></td>
                                                    </tr>
                                                    <?php } ?>

                                                    </tbody>
                                                </table>
                </div>
                                            </div>

                                            <div class="invoice-summary">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="col-sm-8">
                                                            <p>&nbsp;</p>
                                                        </div>
                                                        <div class="col-sm-4"
                                                             style="position: relative; min-height: 1px; padding-right: 8px; padding-left: 0px;">
                <div class="table-responsive">
                                                            <table class="table h5 text-dark">
                                                                <tbody>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_serv_gravados'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalServGravados'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_serv_exentos'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalServExentos'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_serv_exonerado'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalServExonerado'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_merc_gravadas'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalMercanciasGravadas'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_merc_exentas'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalMercanciasExentas'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_merc_exonerada'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalMercExonerada'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_gravado'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalGravado'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_exento'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalExento'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_exonerado'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalExonerado'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_venta'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalVenta'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_descuentos'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalDescuentos'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_impuesto_fec'); ?></td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalImpuesto'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2"><?= lang('total_comprobante'); ?></td>
                                                                    <td class="text-right"><b>¢  <?= number_format($totales['TotalComprobante'], 2,'.', '' ) ?></b></td>
                                                                </tr>




                                                                </tbody>
                                                            </table>
                </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <input id="_lan_msg_confirm" value="¿Está seguro?" type="hidden">
                        <input id="i_cid" value="1006" type="hidden">


                
  

    </div>
</section>
    <div id="ajax-modal" class="modal container fade" tabindex="-1" style="display: none;"></div>

<style>
    .pluma {
        margin: 1px;
        border-radius: 150px 0;
        padding: 16px 101px;
    }
</style>

<script>
function imprimir(){
    var style= "@media all {div.saltopagina{display: none;}}"+  
    "@media print{div.saltopagina{ display:block; page-break-before:always;}}"+
    ".pluma {margin: 1px;border-radius: 150px 0;padding: 16px 101px;}";
    var mywindow = window.open('', 'PRINT', 'height=400,width=600');
    mywindow.document.write(document.getElementById('impHead').innerHTML);
	// mywindow.document.write('<style>.tabla{width:100%;border-collapse:collapse;margin:16px 0 16px 0;}.tabla th{border:1px solid #ddd;padding:4px;background-color:#d4eefd;text-align:left;font-size:15px;}.tabla td{border:1px solid #ddd;text-align:left;padding:6px;}</style>');
    mywindow.document.write('<body >');
    mywindow.document.write('<style>'+style+'</style>');
    mywindow.document.write(document.getElementById('divImprimir').innerHTML);
    mywindow.document.write('</body>');
    mywindow.document.write(document.getElementById('impFoot').innerHTML);
    mywindow.document.write('</html>');
    mywindow.document.close(); // necesario para IE >= 10
    mywindow.focus(); // necesario para IE >= 10
    mywindow.print();
    mywindow.close();
    return true;}
</script> 



