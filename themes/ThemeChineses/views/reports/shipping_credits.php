<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";

if ($this->input->post('start_date')) {
    $v .= "&start_date=" . $this->input->post('start_date');
}
if ($this->input->post('end_date')) {
    $v .= "&end_date=" . $this->input->post('end_date');
}

if ($this->input->post('shipping_method')) {
    $v .= "&shipping_method=" . $this->input->post('shipping_method');
}


?>

<script type="text/javascript">
    $(document).ready(function() {

        function status(x) {
            var paid = '<?= lang('paid'); ?>';
            var partial = '<?= lang('partial'); ?>';
            var due = '<?= lang('due'); ?>';
            if (x == 'paid') {
                return '<div class="text-center"><span class="sale_status label label-success">' + paid + '</span></div>';
            } else if (x == 'partial') {
                return '<div class="text-center"><span class="sale_status label label-primary">' + partial + '</span></div>';
            } else if (x == 'due') {
                return '<div class="text-center"><span class="sale_status label label-danger">' + due + '</span></div>';
            } else {
                return '<div class="text-center"><span class="sale_status label label-default">' + x + '</span></div>';
            }
        }

        var table = $('#SLRData').DataTable({

            'ajax': {
                url: '<?= site_url('reports/get_credit_shipping/' . $v); ?>',
                type: 'POST',
                "data": function(d) {
                    d.<?= $this->security->get_csrf_token_name(); ?> = "<?= $this->security->get_csrf_hash() ?>";
                }
            },
            "buttons": [{
                    extend: 'copyHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                    }
                },
                {
                    extend: 'excelHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                    }
                },
                {
                    extend: 'csvHtml5',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    'footer': true,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                    }
                },
                {
                    extend: 'colvis',
                    text: 'Columns'
                },
            ],
            "columns": [
                {
                    "data": "id",
                    "visible": false
                },
                <?php if ($this->input->post('shipping_method')) { ?> 
                {
                    "data": "Actions",
                    "visible": true
                },
                <?php } else { ?> 
                {
                    "data": "Actions",
                    "visible": false
                },
                <?php } ?> 
                {
                    "data": "date",
                    "render": hrld
                },
                {
                    "data": "customer_name"
                },
                {
                    "data": "total",
                    "render": currencyFormat
                },
                {
                    "data": "total_tax",
                    "render": currencyFormat
                },
                {
                    "data": "total_discount",
                    "render": currencyFormat
                },
                {
                    "data": "grand_total",
                    "render": currencyFormat
                },
                {
                    "data": "paid",
                    "render": currencyFormat
                },
                {
                    "data": "balance"
                },
                {
                    "data": "status",
                    "render": status
                }
            ],
            columnDefs: [{
            targets: [10],
            render: $.fn.dataTable.render.number(',', '.', 4)
            }],
            "footerCallback": function(tfoot, data, start, end, display) {
                var api = this.api(),
                    data;

                $(api.column(4).footer()).html(cf(api.column(4).data().reduce(function(a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(5).footer()).html(cf(api.column(5).data().reduce(function(a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(6).footer()).html(cf(api.column(6).data().reduce(function(a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(7).footer()).html(cf(api.column(7).data().reduce(function(a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(8).footer()).html(cf(api.column(8).data().reduce(function(a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(9).footer()).html(cf(api.column(9).data().reduce(function(a, b) {
                    return pf(a) + pf(b);
                }, 0)));

            }

        });

        $('#search_table').on('keyup change', function(e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (((code == 13 && table.search() !== this.value) || (table.search() !== '' && this.value === ''))) {
                table.search(this.value).draw();
            }
        });

        table.columns().every(function() {
            var self = this;
            $('input.datepicker', this.footer()).on('dp.change', function(e) {
                self.search(this.value).draw();
            });
            $('input:not(.datepicker)', this.footer()).on('keyup change', function(e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (((code == 13 && self.search() !== this.value) || (self.search() !== '' && this.value === ''))) {
                    self.search(this.value).draw();
                }
            });
            $('select', this.footer()).on('change', function(e) {
                self.search(this.value).draw();
            });
        });

    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $('.toggle_form').click(function() {
            $("#form").slideToggle();
            return false;
        });
    });
</script>

<section class="content">
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header">
                    <a href="#" class="btn btn-default btn-sm toggle_form pull-right"><?= lang("show_hide"); ?></a>
                    <h3 class="box-title"><?= lang('customize_report'); ?></h3>
                </div>
                <div class="box-body">
                    <div id="form" class="panel panel-warning ">
                        <div class="panel-body">
                            <?= form_open("reports/credit_shipping"); ?>

                            <div class="row">

                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label class="control-label" for="shipping_method"><?= lang("shipping_method"); ?></label>
                                        <?php
                                        $sh[''] = lang("select") . " " . lang("shipping_method");
                                        foreach ($shipping as $ship) {
                                            $sh[$ship->id_shipping_method] = $ship->name;
                                        }
                                        echo form_dropdown('shipping_method', $sh, set_value('shipping_method'), 'class="form-control select2" style="width:100%" id="shipping_method"'); ?>
                                    </div>
                                </div>




                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label class="control-label" for="start_date"><?= lang("start_date"); ?></label>
                                        <?= form_input('start_date', set_value('start_date'), 'class="form-control datetimepicker" id="start_date"'); ?>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <label class="control-label" for="end_date"><?= lang("end_date"); ?></label>
                                        <?= form_input('end_date', set_value('end_date'), 'class="form-control datetimepicker" id="end_date"'); ?>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary">Buscar</button>
                                </div>
                            </div>
                            <?= form_close(); ?>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="table-responsive">
                                <table id="SLRData" class="table table-striped table-bordered table-condensed table-hover">
                                    <thead>
                                        <tr class="active">
                                            <th style="max-width:30px;"><?= lang("id"); ?></th>
                                            <th class="col-sm-1">Sumar</th>
                                            <th class="col-sm-2"><?= lang("date"); ?></th>
                                            <th class="col-sm-2"><?= lang("customer"); ?></th>
                                            <th class="col-sm-1"><?= lang("total"); ?></th>
                                            <th class="col-sm-1"><?= lang("tax"); ?></th>
                                            <th class="col-sm-1"><?= lang("discount"); ?></th>
                                            <th class="col-sm-2"><?= lang("grand_total"); ?></th>
                                            <th class="col-sm-1"><?= lang("paid"); ?></th>
                                            <th class="col-sm-1"><?= lang("balance"); ?></th>
                                            <th class="col-sm-1"><?= lang("status"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="10" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="active">
                                            <th style="max-width:30px;"><input type="text" class="text_filter" placeholder="[<?= lang('id'); ?>]"></th>
                                            <th class="col-sm-1">
                                            <!-- <label class='switch'><input type='checkbox' onclick='checkbox(this)'></label> -->
                                            </th>
                                            <th class="col-sm-2"><span class="datepickercon"><input type="text" class="text_filter datepicker" placeholder="[<?= lang('date'); ?>]"></span>
                                            </th>
                                            <th class="col-sm-2"><input type="text" class="text_filter" placeholder="[<?= lang('customer'); ?>]"></th>
                                            <th class="col-sm-1"><?= lang("total"); ?></th>
                                            <th class="col-sm-1"><?= lang("tax"); ?></th>
                                            <th class="col-sm-1"><?= lang("discount"); ?></th>
                                            <th class="col-sm-2"><?= lang("grand_total"); ?></th>
                                            <th class="col-sm-1"><?= lang("paid"); ?></th>
                                            <th class="col-sm-1"><?= lang("balance"); ?></th>
                                            <th class="col-sm-1">
                                                <select class="select2 select_filter">
                                                    <option value=""><?= lang("all"); ?></option>
                                                    <option value="paid"><?= lang("paid"); ?></option>
                                                    <option value="partial"><?= lang("partial"); ?></option>
                                                    <option value="due"><?= lang("due"); ?></option>
                                                </select>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td colspan="11" class="p0"><input type="text" class="form-control b0" name="search_table" id="search_table" placeholder="<?= lang('type_hit_enter'); ?>" style="width:100%;"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>


                    <?php if ($this->input->post('shipping_method')) { ?>
                        <div class="row">
                            <div class="col-md-3">
                                <button class="btn bg-purple btn-lg btn-block" style="cursor:default;">
                                    <strong><?= $this->tec->formatMoney($total_sales->number, 0); ?></strong>
                                    <?= lang("sales"); ?> acumulada
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary btn-lg btn-block" style="cursor:default;">
                                    <strong><?= $this->tec->formatMoney($total_sales->amount); ?></strong>
                                    Monto acumulado
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-success btn-lg btn-block" style="cursor:default;">
                                    <strong><?= $this->tec->formatMoney($total_sales->paid); ?></strong>
                                    <?= lang("paid"); ?> acumulado
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-warning btn-lg btn-block" style="cursor:default;">
                                    <strong ><?= $this->tec->formatMoney($total_sales->amount - $total_sales->paid); ?></strong>
                                    <?= lang("due"); ?>
                                </button>
                                <input type="hidden" id="amount_due" value="<?= $this->tec->formatMoney($total_sales->amount - $total_sales->paid); ?>">
                            </div>
                        </div>

                        <div class="box-header">
                            <h3 class="box-title">Puede realizar el pago de deuda en el siguiente formulario</h3>
                        </div>
                        <div id="form" class="panel panel-warning ">
                            <div class="panel-body">
                                <?= form_open("reports/credit_shipping/"); ?>


                                <?= form_hidden("shipping_method", $this->input->post('shipping_method')) ?>
                                <?= form_hidden("start_date", $this->input->post('start_date')) ?>
                                <?= form_hidden("end_date", $this->input->post('end_date')) ?>
                                <input type="hidden" id='sales_id' name="sales_id">
                                <div id="payments">

                                    <div class="well well-sm well">
                                        <div class="col-sm-12">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="payment">
                                                        <div class="form-group">
                                                            <?= lang("amount", "amount"); ?>
                                                            <input name="amount-paid" type="text" id="amount" value="<?= $total_sales->amount - $total_sales->paid ?>" class="pa form-control kb-pad amount" required="required" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group">
                                                        <?= lang("paying_by", "paid_by"); ?>
                                                        <select name="paid_by" id="paid_by" class="form-control paid_by select2" style="width:100%" required="required">
                                                            <option value="cash"><?= lang("cash"); ?></option>
                                                            <option value="CC">Tarjeta</option>
                                                            <option value="Cheque"><?= lang("cheque"); ?></option>
                                                            <option value="deposito">Deposito</option>
                                                        </select>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="clearfix"></div>
                                            <div class="form-group gc" style="display: none;">
                                                <?= lang("gift_card_no", "gift_card_no"); ?>
                                                <input name="gift_card_no" type="text" id="gift_card_no" class="pa form-control kb-pad" />

                                                <div id="gc_details"></div>
                                            </div>
                                            <div class="pcc" style="display:none;">
                                                <input type="hidden" id="swipe" class="form-control swipe swipe_input" placeholder="<?= lang('focus_swipe_here') ?>" />

                                                <div class="row">
                                                    <input name="pcc_no" type="hidden" id="pcc_no" class="form-control" placeholder="<?= lang('cc_no') ?>" />


                                                    <input name="pcc_holder" type="hidden" id="pcc_holder" class="form-control" placeholder="<?= lang('cc_holder') ?>" />

                                                    <div class="col-sm-6">
                                                        <div class="form-group">
                                                            <select name="pcc_type" id="pcc_type" class="form-control pcc_type select2" style="width:100%" placeholder="<?= lang('card_type') ?>">
                                                                <option value="Debito">Debito</option>
                                                                <option value="Visa"><?= lang("Visa"); ?></option>
                                                                <option value="MasterCard"><?= lang("MasterCard"); ?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6" >
                                                        <div class="form-group">
                                                            <input name="reference_note" placeholder="<?= lang("type_reference_note");?>" type="text" id="reference_note" class="form-control reference_note" />
                                                        </div>
                                                    </div>
                                                    <input name="pcc_month" type="hidden" id="pcc_month" class="form-control" placeholder="<?= lang('month') ?>" />

                                                    <input name="pcc_year" type="hidden" id="pcc_year" class="form-control" placeholder="<?= lang('year') ?>" />

                                                    <input name="pcc_ccv" type="hidden" id="pcc_cvv2" class="form-control" placeholder="<?= lang('cvv2') ?>" />
                                                </div>
                                            </div>
                                            <div class="pcheque" style="display:none;">
                                                <div class="form-group"><?= lang("cheque_no", "cheque_no"); ?>
                                                    <input name="cheque_no" type="text" id="cheque_no" class="form-control cheque_no" />
                                                </div>
                                            </div>
                                            <div class="pdeposito" style="display:none;">
                                                <div class="form-group"><?= lang("type_reference_note");?>
                                                    <input name="deposito_ref" type="text" id="deposito_ref" class="form-control deposito_ref" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>

                                </div>


                                <div class="col-sm-12">
                                    <button type="submit" onclick="validar();" class="btn btn-primary">Pagar deuda</button>
                                </div>

                                <?= form_close(); ?>
                            </div>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(function() {
        $('.datetimepicker').datetimepicker({
            format: 'YYYY-MM-DD'
        });
        $('.datepicker').datetimepicker({
            format: 'YYYY-MM-DD',
            showClear: true,
            showClose: true,
            useCurrent: false,
            widgetPositioning: {
                horizontal: 'auto',
                vertical: 'bottom'
            },
            widgetParent: $('.dataTable tfoot')
        });
    });
</script>


<script type="text/javascript" charset="UTF-8">
        function checkAll(element) {
            console.log(element);
            // if (this.checked) {
            //     $('input[type=checkbox]').each(function() {
            //         this.checked = true;
            //     });
            // } else {
            //     $('input[type=checkbox]').each(function() {
            //         this.checked = false;
            //     });
            // }
        }
    $(document).ready(function() {
        $('#gift_card_no').inputmask("9999 9999 9999 9999");
        $(document).on('change', '.paid_by', function() {
            var p_val = $(this).val();
            if (p_val == 'gift_card') {
                $('.gc').slideDown();
                $('.ngc').slideUp('fast');
                setTimeout(function() {
                    $('#gift_card_no').focus();
                }, 10);
                $('#amount').attr('readonly', true);
            } else {
                $('.ngc').slideDown();
                $('.gc').slideUp('fast');
                $('#gc_details').html('');
                // $('#amount').attr('readonly', false);
            }
            if (p_val == 'cash' || p_val == 'other') {
                $('.pcash').slideDown();
                $('.pcheque').slideUp('fast');
                $('.pcc').slideUp('fast');
                $('.pdeposito').slideUp('fast');
                setTimeout(function() {
                    $('#amount').focus();
                }, 10);
            } else if (p_val == 'CC' || p_val == 'stripe') {
                $('.pcc').slideDown();
                $('.pcheque').slideUp('fast');
                $('.pcash').slideUp('fast');
                $('.pdeposito').slideUp('fast');
                setTimeout(function() {
                    $('#swipe').val('').focus();
                }, 10);
            } else if (p_val == 'Cheque') {
                $('.pcheque').slideDown();
                $('.pcc').slideUp('fast');
                $('.pcash').slideUp('fast');
                $('.pdeposito').slideUp('fast');
                setTimeout(function() {
                    $('#cheque_no').focus();
                }, 10);
            } else if(p_val == 'deposito'){
                $('.pdeposito').slideDown();
                $('.pcc').slideUp('fast');
                $('.pcash').slideUp('fast');
                $('.pcheque').slideUp('fast');
                setTimeout(function() {
                    $('#deposito_ref').focus();
                }, 10);
            }else {
                $('.pcheque').hide();
                $('.pcc').hide();
                $('.pcash').hide();
                $('.pdeposito').hide();
            }

        });

        $('#amount').val(0);

        $('#pcc_no').change(function(e) {
            var cn = $(this).val();
            var ccn1 = cn.charAt(0);
            if (ccn1 == 4)
                CardType = 'Visa';
            else if (ccn1 == 5)
                CardType = 'MasterCard';
            else if (ccn1 == 3)
                CardType = 'Amex';
            else if (ccn1 == 6)
                CardType = 'Discover';
            else
                CardType = 'Visa';

            $('#pcc_type').select2('val', CardType);
        });



    });

    function checkbox(element) {
        actualizarMonto(element);
        actualizarIds();
        habilitarCheck();
    }

    function actualizarIds() {
        var values = '';
        $('input[type=checkbox]').each(function() {
            if (this.checked) {
                var str = this.id;
                values += str + ';';
                $('#sales_id').val(values);
            }
        });
    }

    function actualizarMonto(element) {
        var old_amount = parseFloat($('#amount').val());
        var amount = 0;
        if (element.checked) {
            var count = 0;
            $(element).closest('td').siblings().each(function() {
                // obtenemos el texto del td 
                if (count === 7) {
                    amount = parseFloat($(this).text().replace(',', ''));
                    amount += old_amount;
                    $('#amount').val(amount.toFixed(4));

                }
                count++;
            });
        } else {
            var count = 0;
            $(element).closest('td').siblings().each(function() {
                // obtenemos el texto del td 
                if (count === 7) {
                    amount = parseFloat($(this).text().replace(',', ''));
                    old_amount = old_amount - amount;
                    $('#amount').val(old_amount.toFixed(4));
                }
                count++;
            });
        }
    }

    function habilitarCheck() {
        var count = 0;
        $('input[type=checkbox]').each(function() {
            if (this.checked) {
                count++;
            }
        });
        if (count > 0) {
            $('#amount').attr('readonly', 'readonly');
        } else if (count === 0) {
            $('#amount').removeAttr('readonly', 'readonly');
            $('#sales_id').val('');
        }
    }

    $('#amount').change(function(){
        var amount_due =$('#amount_due').val().replace(',', '');
        if(parseFloat(this.value) > parseFloat(amount_due)){
            bootbox.alert("El monto ingresado supera al balance total");
            $('#amount').val(0);
            $('#amount').focus();
        }
    });

    function validar(){
        var amount_due =$('#amount_due').val();
        var amount =$('#amount_due').val();
        if(amount > amount_due && amount !== 0){
            bootbox.alert("El monto ingresado supera al balance total");
            $('#amount').val(0);
            $('#amount').focus();
            return false;
        }else if(amount === 0){
            bootbox.alert("El monto ingresado tiene que se mayor a cero");
            $('#amount').val(0);
            $('#amount').focus();
            return false;
        }else{
            return true;
        }
    }
</script>