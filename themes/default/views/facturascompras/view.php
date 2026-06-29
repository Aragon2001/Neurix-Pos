<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<section class="content" >
<div class="btn-group  float-start" role="group" aria-label="...">
    <div class="btn-group" role="group2">
        <button type="button" class="btn  btn-success btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-file-pdf-o"></i>
        PDF <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" role="menu2">
        <li><a onclick="imprimir()"target="_blank">Ver PDF/Descargar PDF</a></li>
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
                                                            <? if ($inv->status == 'Unpaid') { ?>
                                                            <h3 class="pluma alert float-start alert-danger">No
                                                                Pagada</h3>
                                                            <? }else if ($inv->status == 'Paid') { ?>
                                                            <h3 class="pluma alert float-start alert-success">Pagada</h3>
                                                            <? } else if ($inv->status == 'Partially Paid') { ?>
                                                            <h3 class="pluma alert float-start alert-info">Parcialmente
                                                                Pagada</h3>
                                                            <? } else { ?>
                                                            <h3 class="pluma alert float-start alert-info"><?= $inv->status ?></h3>
                                                            <? } ?>
                                                        </i>
                                                        <br/>
                                                        <br/>
                                                        <br/>
                                                        <h4><b>Mensaje de Hacienda:</b> <span
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
                                                            <p class="h5 mb-xs text-dark text-semibold"><strong>Facturado
                                                                    a:</strong></p>
                                                            <address>
                                                                <?= $customer->name ?>
                                                                <br>
                                                                <?= $local['direccion'] ?> <br>
                                                                <?=$local['nombre_distrito']?> - <?=$local['nombre_canton']?>
                                                                - <?=$local['nombre_provincia']?>.
                                                                <br>
                                                                <strong>Telefono(s)
                                                                    :</strong> <?= $customer->phone ?>
                                                                <br>
                                                                <strong>Correo
                                                                    :</strong> <?= $customer->email?>
                                                            </address>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="bill-data text-right">
                                                            <p class="mb-none">
                                                                <span class="text-dark">Fecha de la Factura:</span>
                                                                <span class="value"><?= date('Y-m-d', strtotime($hacienda->fecha_emision)) ?></span>
                                                            </p>
                                                            <h2> Importe Total:
                                                              ¢ <?= number_format($inv->grand_total, 2, '.', '') ?></h2>
                                                            <? if ($inv->paid != '0.00') { ?>
                                                            <h2> Total
                                                                pagado: ¢ <?= number_format($inv->paid, 2, '.', '') ?> </h2>
                                                            <h2> Monto
                                                                adeudado:¢ <?= number_format($inv->grand_total - $inv->paid, 2, '.', '') ?> </h2>
                                                            <? } ?>
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
                                                        <th id="cell-item" class="text-semibold">Artículo</th>
                                                        <th id="cell-price" class="text-center text-semibold">Precio</th>
                                                        <th id="cell-qty" class="text-center text-semibold">Cant.</th>
                                                        <th id="cell-total" class="text-center text-semibold">MontoTotal</th>
                                                        <th id="cell-total" class="text-center text-semibold">Descuento</th>
                                                        <th id="cell-total" class="text-center text-semibold">SubTotal</th>
                                                        <th id="cell-total" class="text-center text-semibold">Imp.</th>

                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <? foreach ($rows as $item) { ?>
                                                    <tr>
                                                        <td><?= $item->product_code ?></td>
                                                        <td class="text-semibold text-dark"><?= $item->product_name  ?></td>

                                                        <td class="text-right">¢ <?= number_format($item->unit_price, 2, '.', '') ?></td>
                                                        <td class="text-center"><?= $item->quantity ?></td>
                                                        <td class="text-right">¢ <?= number_format($item->unit_price, 2, '.', '') * number_format($item->quantity, 2, '.', '') ?></td>
                                                        <td class="text-right">¢ <?= number_format($item->item_discount, 2, '.', '') ?></td>
                                                        <td class="text-right">¢ <?= number_format($item->subtotal, 2, '.', '') ?></td>
                                                        <td class="text-right">¢ <?= number_format($item->item_tax, 2, '.', '') ?></td>
                                                    </tr>
                                                    <? } ?>

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
                                                                    <td colspan="2">TotalServGravados</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalServGravados'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalServExentos</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalServExentos'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalServExonerado</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalServExonerado'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalMercanciasGravadas</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalMercanciasGravadas'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalMercanciasExentas</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalMercanciasExentas'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalMercExonerada</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalMercExonerada'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalGravado</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalGravado'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalExento</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalExento'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalExonerado</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalExonerado'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalVenta</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalVenta'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalDescuentos</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalDescuentos'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalImpuesto</td>
                                                                    <td class="text-right">¢  <?= number_format($totales['TotalImpuesto'], 2,'.', '') ?></td>
                                                                </tr>
                                                                <tr class="b-top-none">
                                                                    <td colspan="2">TotalComprobante</td>
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



