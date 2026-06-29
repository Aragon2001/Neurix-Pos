<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-bs-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
            </button>
            <span type="button" class="close mr10  imprimeweb" ><i class="fa fa-print"></i></span>

            <h4 class="modal-title" id="myModalLabel">
            
            <?php if(!isset($is_report)){
                    echo lang('register_details') . ' (' . lang('opened_at') . ': ' . $this->tec->hrld($this->session->userdata('register_open_time')) . ')'; 
            }else{

                    echo lang('register_details') . ' (' . lang('opened_at') . ': ' . $this->tec->hrld($register_open_time) . ')'; 
                 }?>
        </h4>
        </div>
        <?php if(!isset($is_report)){?>
        <?php echo form_open("pos/close_register/" . $user_id); ?>

        <div style='text-align: center;'>

            <a target="_blank" href='pos/products_sales_in_register' class="btn btn-info">Imprimir articulos Vendidos</a>
            <span id="print-register-details" class="btn btn-warning imprimeweb">Imprimir cierre via web</span>
            <?php echo form_submit('close_register', "Cerrar Caja", 'class="btn btn-primary"'); ?>

        </div>
        <?php }else{?>
            <?php echo form_open("reports/close_register/?user_id=" . $user_id."&date=".$register_open_time); ?>

        <div style='text-align: center;'>
            <a target="_blank" href='pos/products_sales_in_register' class="btn btn-info">Imprimir articulos Vendidos</a>
            <span id="print-register-details" class="btn btn-warning imprimeweb">Imprimir cierre via web</span>
            <?php echo form_submit('close_register', "Imprimir Cierre Caja", 'class="btn btn-primary"'); ?>

        </div>
        <?php }?>
        <?php
        $total_cash = ($cashsales->total ? $cashsales->total + ($cash_in_hand ? $cash_in_hand : $this->session->userdata('cash_in_hand')) : (($cash_in_hand ? $cash_in_hand : $this->session->userdata('cash_in_hand'))));
        $total_cash -= ($expenses->total ? $expenses->total : 0.00);
        $total_cash -= (@$notecredits->total ? $notecredits->total : 0.00);
        ?>
        <?php $apartadoEfect = 0; ?>
        <?php if ($Settings->enable_layaway == 1) { ?>

            <?php $apartadoEfect = $cashsalesApart->total; ?>
        <?php } ?>

        <div class="modal-body" style="padding: 5px 0px;">
            <div class="col-md-12">
                <div class="row" >
                    <div class="col-sm-2">
                    </div>
                    <?php if(!isset($is_report)){?>
                    <div class="col-sm-4">
                        <div class="mb-3">
                            <?php echo lang("total_cash_submitted"); ?>
                            <?php echo form_hidden('total_cash', $total_cash); ?>
                            <?php
                            echo form_input('total_cash_submitted', 0
                                    , 'class="form-control input-tip" id="total_cash_submitted" required="required"');
                            ?>
                        </div>
                           
                    </div>
                    <div class="col-sm-4">
                        <div class="mb-3">
                            <?php echo lang("total_cc_slips"); ?>
                            <?php echo form_hidden('total_cc', $ccsales->total); ?>
                            <?php
                            echo form_input('total_cc_submitted', 0
                                    , 'class="form-control input-tip" id="total_cc_submitted" required="required"');
                            ?>
                        </div>
                    </div>
                    <?php }?>
                    <div class="col-sm-2">
                    </div>
                </div>
                <?php echo form_hidden('total_cc_slips_submitted', (isset($_POST['total_cc_slips_submitted']) ? $_POST['total_cc_slips_submitted'] : '0'), 'class="form-control input-tip" id="total_cc_slips_submitted" required="required"'); ?>
                <?php echo form_hidden('total_cheques', $chsales->total_cheques); ?>
                <?php echo form_hidden('total_cheques_submitted', (isset($_POST['total_cheques_submitted']) ? $_POST['total_cheques_submitted'] : $chsales->total_cheques), 'class="form-control input-tip" id="total_cheques_submitted" required="required"'); ?>
                <?php echo form_hidden('note', (isset($_POST['note']) ? $_POST['note'] : ""), 'class="form-control redactor" id="note" style="margin-top: 10px; height: 50px;"'); ?>

            </div>
            <div  id="imprimeesto" class="col-md-12" style="max-height: 400px; overflow-y: scroll; <?php if ($Settings->enable_detail_register == "0") { ?> display: none; visibility: hidden; <?php } ?>">
                <table style='margin: 0 auto;' >

                    <tr>
                        <td style="border-bottom: 1px solid #008d4c;"><h4><?php echo lang('cash_in_hand'); ?>:</h4></td>
                        <td style="text-align:right; border-bottom: 1px solid #008d4c;"><h4>
                                <?php echo form_hidden('cash_in_hand', $this->session->userdata('cash_in_hand')); ?>
                                <?php if(!isset($is_report)){?>
                                <span><?php echo $this->tec->formatMoney($this->session->userdata('cash_in_hand')); ?></span>
                                <?php }else{?>
                                    <span><?php echo $this->tec->formatMoney($cash_in_hand); ?></span>
                                <?php }?>
                            </h4>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-bottom: 1px solid #EEE;"><h4><?php echo lang('cash_sale'); ?>:</h4></td>
                        <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                <?php echo form_hidden('cash_sale', $cashsales->total ? $cashsales->total - ($notecredits?$notecredits->total:0) : '0.00'); ?>
                                <span><?php echo $this->tec->formatMoney($cashsales->total ? $cashsales->total- ($notecredits?$notecredits->total:0) : '0.00'); ?></span>
                            </h4></td>
                    </tr>


                    <tr>
                        <td style="border-bottom: 1px solid #008d4c; <?php echo (!isset($Settings->stripe)) ? '#DDD' : '#EEE'; ?>;">
                            <h4><?php echo lang('cc_sale'); ?>:</h4></td>
                        <td style="text-align:right;border-bottom: 1px solid #008d4c; <?php echo (!isset($Settings->stripe)) ? '#DDD' : '#EEE'; ?>;">
                            <h4>
                                <?php echo form_hidden('cc_sale', $ccsales->total ? $ccsales->total : '0.00'); ?>
                                <span><?php echo $this->tec->formatMoney($ccsales->total ? $ccsales->total : '0.00'); ?></span>
                            </h4></td>
                    </tr>


                    <tr>
                        <td style=" border-bottom: 1px  ; font-weight:bold#008d4c;;"><h4><b><?php echo lang('total_sales'); ?>:</b></h4></td>
                        <td style="border-bottom: 1px  ; font-weight:bold;text-align:right;"><h4>
                                <?php echo form_hidden('total_sales', $totalsales ? $totalsales : '0.00'); ?>
                                <span><b><?php echo $this->tec->formatMoney($totalsales ? $totalsales : '0.00'); ?></b></span>
                            </h4></td>
                    </tr>
                    <tr>
                        <td style=" border-bottom: 1px ; font-weight:bold#008d4c;;"><h4><b><?php echo lang('total_credits_sales'); ?>:</b></h4></td>
                        <td style="border-bottom: 1px ; font-weight:bold;text-align:right;"><h4>
                                <?php echo form_hidden('total_credits_sales', $creditos->total ? $creditos->total : '0.00'); ?>
                                <span><b><?php echo $this->tec->formatMoney($creditos->total ? $creditos->total : '0.00'); ?></b></span>
                            </h4></td>
                    </tr>
                    <tr>
                        <td style=" border-bottom: 1px solid #008d4c; font-weight:bold#008d4c;;"><h4><b><?php echo lang('grand_total'); ?>:</b></h4></td>
                        <td style="border-bottom: 1px solid #008d4c; font-weight:bold;text-align:right;"><h4>

                                <?php echo form_hidden('grand_total_sales', (int) $totalsales + (int) $creditos->total); ?>
                                <span><b><?php echo $this->tec->formatMoney((int) $totalsales + (int) $creditos->total); ?></b></span>
                            </h4></td>
                    </tr>

                    <? $gravadasTotal = 0; ?>
                    <?php if ($Settings->enabled_tax_split == '1') { ?>
                    <? if ($gravadas1->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 1%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas1', @$gravadas1->total ? @$gravadas1->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas1->total ? $gravadas1->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 1%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas1', @$gravadas1->total ? @$gravadas1->total - (@$gravadas1->total / 1.01) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas1->total ? @$gravadas1->total - (@$gravadas1->total / 1.01) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas1->total ?>
                    <? } ?>

                    <? if ($gravadas2->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 2%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas2', @$gravadas2->total ? @$gravadas2->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas2->total ? $gravadas2->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 2%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas2', @$gravadas2->total ? @$gravadas2->total - (@$gravadas2->total / 1.02) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas2->total ? @$gravadas2->total - (@$gravadas2->total / 1.02) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas2->total ?>
                    <? } ?>

                    <? if ($gravadas3->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 3%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas3', @$gravadas3->total ? @$gravadas3->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas3->total ? $gravadas3->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 3%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas3', @$gravadas3->total ? @$gravadas3->total - (@$gravadas3->total / 1.03) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas3->total ? @$gravadas3->total - (@$gravadas3->total / 1.03) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas3->total ?>
                    <? } ?>

                    <? if ($gravadas4->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 4%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas4', @$gravadas4->total ? @$gravadas4->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas4->total ? $gravadas4->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 4%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas4', @$gravadas4->total ? @$gravadas4->total - (@$gravadas4->total / 1.04) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas4->total ? @$gravadas4->total - (@$gravadas4->total / 1.04) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas4->total ?>
                    <? } ?>

                    <? if ($gravadas5->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 5%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas5', @$gravadas5->total ? @$gravadas5->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas5->total ? $gravadas5->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 5%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas5', @$gravadas5->total ? @$gravadas5->total - (@$gravadas5->total / 1.05) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas5->total ? @$gravadas5->total - (@$gravadas5->total / 1.05) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas5->total ?>
                    <? } ?>

                    <? if ($gravadas6->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 6%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas6', @$gravadas6->total ? @$gravadas6->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas6->total ? $gravadas6->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 6%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas6', @$gravadas6->total ? @$gravadas6->total - (@$gravadas6->total / 1.06) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas6->total ? @$gravadas6->total - (@$gravadas6->total / 1.06) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas6->total ?>
                    <? } ?>

                    <? if ($gravadas7->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 7%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas7', @$gravadas7->total ? @$gravadas7->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas7->total ? $gravadas7->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 7%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas7', @$gravadas7->total ? @$gravadas7->total - (@$gravadas7->total / 1.07) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas7->total ? @$gravadas7->total - (@$gravadas7->total / 1.07) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas7->total ?>
                    <? } ?>

                    <? if ($gravadas8->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 8%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas8', @$gravadas8->total ? @$gravadas8->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas8->total ? $gravadas8->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 8%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas8', @$gravadas8->total ? @$gravadas8->total - (@$gravadas8->total / 1.08) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas8->total ? @$gravadas8->total - (@$gravadas8->total / 1.08) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas8->total ?>
                    <? } ?>

                    <? if ($gravadas9->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 8%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas9', @$gravadas9->total ? @$gravadas9->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas9->total ? $gravadas9->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 9%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas9', @$gravadas9->total ? @$gravadas9->total - (@$gravadas9->total / 1.09) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas9->total ? @$gravadas9->total - (@$gravadas9->total / 1.09) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas9->total ?>
                    <? } ?>

                    <? if ($gravadas10->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 10%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas10', @$gravadas10->total ? @$gravadas10->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas10->total ? $gravadas10->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 10%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas10', @$gravadas10->total ? @$gravadas10->total - (@$gravadas10->total / 1.10) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas10->total ? @$gravadas10->total - (@$gravadas10->total / 1.10) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas10->total ?>
                    <? } ?>

                    <? if ($gravadas11->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 11%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas11', @$gravadas11->total ? @$gravadas11->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas11->total ? $gravadas11->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 11%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas11', @$gravadas11->total ? @$gravadas11->total - (@$gravadas11->total / 1.11) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas11->total ? @$gravadas11->total - (@$gravadas11->total / 1.11) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas11->total ?>
                    <? } ?>

                    <? if ($gravadas12->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 12%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas12', @$gravadas12->total ? @$gravadas12->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas12->total ? $gravadas12->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 12%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas12', @$gravadas12->total ? @$gravadas12->total - (@$gravadas12->total / 1.12) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas12->total ? @$gravadas12->total - (@$gravadas12->total / 1.12) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas12->total ?>
                    <? } ?>

                    <? if ($gravadas13->total) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Gravadas con 13%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('gravadas13', @$gravadas13->total ? @$gravadas13->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas13->total ? $gravadas13->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Total impuesto del 13%:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('impuestogravadas13', @$gravadas13->total ? @$gravadas13->total - (@$gravadas13->total / 1.13) : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney(@$gravadas13->total ? @$gravadas13->total - (@$gravadas13->total / 1.13) : '0.00'); ?></span>
                                </h4></td>
                        </tr>
                        <? $gravadasTotal = $gravadasTotal + $gravadas13->total ?>
                    <? } ?>




                    <tr>
                        <td style="border-bottom: 1px solid #EEE;"><h4>Ventas Excentas:</h4></td>
                        <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                <?php echo form_hidden('exentas', @$exentas->total ? @$exentas->total : '0.00'); ?>
                                <span><?php echo $this->tec->formatMoney(@$exentas->total ? $exentas->total : '0.00'); ?></span>
                            </h4></td>
                    </tr>
                    <tr>
                        <td style="border-bottom: 1px solid #008d4c;"><h4>Total Excentas + Gravadas:</h4></td>
                        <td style="text-align:right; border-bottom: 1px solid #008d4c;"><h4>
                                <?php echo form_hidden('tot_exentas_gravadas', @$exentas->total + $gravadasTotal ? @$exentas->total + $gravadasTotal : '0.00'); ?>
                                <span><?php echo $this->tec->formatMoney(@$exentas->total + $gravadasTotal ? $exentas->total + $gravadasTotal : '0.00'); ?></span>
                            </h4></td>
                    </tr>
                    <?php }?>
                    <tr>
                        <td style="border-bottom: 1px solid #EEE;"><h4><?php echo lang('credit_notes'); ?>:</h4></td>
                        <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>

                                <?php echo form_hidden('credit_notes', @$notecredits->total ? $notecredits->total : '0.00'); ?>
                                <span><?php echo $this->tec->formatMoney(@$notecredits->total ? $notecredits->total : '0.00'); ?></span>
                            </h4></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;"><h4><?php echo lang('Gastos / Retiros'); ?>:</h4></td>
                        <td style="font-weight:bold;text-align:right;"><h4>
                                <?php echo form_hidden('expenses', $expenses->total ? $expenses->total : '0.00'); ?>
                                <span><?php echo $this->tec->formatMoney($expenses->total ? $expenses->total : '0.00'); ?></span>
                            </h4></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;"><h4><?php echo lang('Depositos'); ?>:</h4></td>
                        <td style="font-weight:bold;text-align:right;"><h4>
                                <?php echo form_hidden('depositos', $Totaldepositos->total ? $Totaldepositos->total : '0.00'); ?>
                                <span><?php echo $this->tec->formatMoney($Totaldepositos->total ? $Totaldepositos->total : '0.00'); ?></span>
                            </h4></td>
                    </tr>



                    <?php if ($Settings->enable_layaway == 1) { ?>
                        <tr>
                            <td style="border-bottom: 1px solid #EEE;"><h4>Efectivo de Apartados:</h4></td>
                            <td style="text-align:right; border-bottom: 1px solid #EEE;"><h4>
                                    <?php echo form_hidden('cash_sale', $cashsalesApart->total ? $cashsalesApart->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney($cashsalesApart->total ? $cashsalesApart->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>


                        <tr>
                            <td style="border-bottom: 1px solid #008d4c; <?php echo (!isset($Settings->stripe)) ? '#DDD' : '#EEE'; ?>;">
                                <h4>Tarjetas de Apartados:</h4></td>
                            <td style="text-align:right;border-bottom: 1px solid #008d4c; <?php echo (!isset($Settings->stripe)) ? '#DDD' : '#EEE'; ?>;">
                                <h4>
                                    <?php echo form_hidden('ccsalesApart', $ccsalesApart->total ? $ccsalesApart->total : '0.00'); ?>
                                    <span><?php echo $this->tec->formatMoney($ccsalesApart->total ? $ccsalesApart->total : '0.00'); ?></span>
                                </h4></td>
                        </tr>

                        <?php if ($Settings->propina_enable) { ?>
                            <tr>
                                <td style="border-bottom: 1px solid #008d4c; <?php echo (!isset($Settings->stripe)) ? '#DDD' : '#EEE'; ?>;">
                                    <h4>Servicio (<?= $Settings->propina_rate ?>%):</h4></td>
                                <td style="text-align:right;border-bottom: 1px solid #008d4c; <?php echo (!isset($Settings->stripe)) ? '#DDD' : '#EEE'; ?>;">
                                    <h4>
                                        <?php echo form_hidden('ccsalesTips', $ccsalesTips->total ? $ccsalesTips->total : '0.00'); ?>
                                        <span><?php echo $this->tec->formatMoney($ccsalesTips->total ? $ccsalesTips->total : '0.00'); ?></span>
                                    </h4></td>
                            </tr>
                        <?php } ?>

                    <?php } ?>

                    <tr>
                        <td style="font-weight:bold;"><h4><strong><?php echo lang('total_cash'); ?></strong>:
                            </h4>
                        </td>
                        <td style="text-align:right;"><h4>
                                <?php echo form_hidden('total_cash', $total_cash ? $total_cash + $apartadoEfect + $Totaldepositos->total : '0.00'); ?>
                                <span><strong><?php echo $this->tec->formatMoney($total_cash + $apartadoEfect + $Totaldepositos->total); ?></strong></span>
                            </h4></td>
                    </tr>
                    <tr>
                        <td colspan="2">__________________________________________________</td>
                    </tr>

                    <tr>
                        <td colspan="2"></td>
                    </tr>

                    <tr>
                        <td colspan="2"></td>
                    </tr>

                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>

                </table>
            </div>

            <div class="modal-footer">

            </div>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>


<?php
if ($Settings->remote_printing == 2) {
    ?>
    <script type="text/javascript">



        var socket = null;
        $(document).ready(function () {



            try {
                socket = new WebSocket('ws://127.0.0.1:6441');
                socket.onopen = function () {
                    console.log('Connected');
                    return;
                };
                socket.onclose = function () {
                    console.log('Connection closed');
                    return;
                };
            } catch (e) {
                console.log(e);
            }
            function printRegister(data) {
                if (socket.readyState == 1) {
                    socket.send(JSON.stringify({
                        type: 'print-data',
                        data: data
                    }));
                    return false;
                } else {
                    bootbox.alert('<?php echo lang('pos_print_error'); ?>');
                    return false;
                }
            }

            $('#print-register-details').click(function (e) {
                e.preventDefault();
                $.get('<?php echo site_url('pos/print_register/2'); ?>', function (regData) {
                    printRegister(regData);
                    return false;
                });
                return false;
            });



        });
    </script>

    <?php
}
?>
<script type="text/javascript">
    $(document).ready(function () {
        $(".select2")new TomSelect(this, {minItems: 6});
    });

    $('.imprimeweb').on('click', function () {
        var divElements = document.getElementById("imprimeesto").innerHTML;
        var oldPage = document.body.innerHTML;
        document.body.innerHTML =
                "<html><head><title></title></head><body>" +
                divElements + "</body>";
        window.print();
        document.body.innerHTML = oldPage;
    });
</script>
