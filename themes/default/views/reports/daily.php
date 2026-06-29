<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";

if ($this->input->post('customer')){
    $v .= "&customer=".$this->input->post('customer');
}
if ($this->input->post('user')){
    $v .= "&user=".$this->input->post('user');
}
if ($this->input->post('start_date')){
    $v .= "&start_date=".$this->input->post('start_date');
}
if ($this->input->post('end_date')) {
    $v .= "&end_date=".$this->input->post('end_date');
}

?>

<script type="text/javascript">
    $(document).ready(function() {


        var table = new Tabulator('#SLRData', {

            'ajax' : { url: '<?=site_url('reports/get_daily_sales/'. $v);?>', type: 'POST', "data": function ( d ) {
                d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
            }},
            "buttons": [
            { extend: 'copyHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ] } },
            { extend: 'excelHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ] } },
            { extend: 'csvHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ] } },
            { extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true,
            exportOptions: { columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ] } },
            { extend: 'colvis', text: 'Columns'},
            ],
                
                
            "columns": [
            { "data": "id", "visible": false },
            <? if($Settings->fe == '1'){ ?>{ "data": "consecutivo" } <?}else{ ?>{ "data": "id" } <?} ?>,
            { "data": "date", "render": hrld },
            { "data": "identificacion" },
            { "data": "customer_name" },
            { "data": "gravado", "render": currencyFormat },
            { "data": "excento", "render": currencyFormat },
            { "data": "total_discount", "render": currencyFormat },
            { "data": "total", "render": currencyFormat },
            { "data": "impuesto", "render": currencyFormat },
            { "data": "grand_total", "render": currencyFormat }
            ],
            "footerCallback": function (tfoot, data, start, end, display ) {
                var api = this.api(), data;
                $(api.column(5).footer()).html( cf(api.column(5).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(6).footer()).html( cf(api.column(6).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(7).footer()).html( cf(api.column(7).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(8).footer()).html( cf(api.column(8).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(9).footer()).html( cf(api.column(9).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(10).footer()).html( cf(api.column(10).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
            }

        });

        $('#search_table').on( 'keyup change', function (e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (((code == 13 && table.search() !== this.value) || (table.search() !== '' && this.value === ''))) {
                table.search( this.value ).draw();
            }
        });

        table.columns().every(function () {
            var self = this;
            $( 'input.datepicker', this.footer() ).on('dp.change', function (e) {
                self.search( this.value ).draw();
            });
            $( 'input:not(.datepicker)', this.footer() ).on('keyup change', function (e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (((code == 13 && self.search() !== this.value) || (self.search() !== '' && this.value === ''))) {
                    self.search( this.value ).draw();
                }
            });
            $( 'select', this.footer() ).on( 'change', function (e) {
                self.search( this.value ).draw();
            });
        });

    });
</script>

<script type="text/javascript">
    $(document).ready(function(){
        $('.toggle_form').click(function(){
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
                    <a href="#" style="display: block; overflow: hidden; margin-bottom: 5px;" class="btn btn-default btn-sm toggle_form float-end"><?= lang("show_hide"); ?></a>
                    <hr style="display: block;overflow: hidden;clear: both;"/>
                    <h3 class="box-title"><b>Informe de ventas diarias</b> - <small style="font-size: 15px;"><i>cuando se abre este reporte se presentan las ventas diarias del dia de hoy <?= date('d-m-Y') ?>,
                                si desea puede usar el siguiente formulario para realizar busquedas personalizadas</i></small></h3>
                </div>
                <div class="box-body">
                    <div id="form" class="card border-warning">
                        <div class="card-body">
                            <?= form_open("reports/daily_sales");?>

                            <div class="row">
                                <div class="col-sm-3">
                                    <div class="mb-3">
                                        <label class="form-label" for="user">Vendedor</label>
                                        <?php
                                        $us[""] = "";
                                        foreach ($users as $user) {
                                            $us[$user->id] = $user->first_name . " " . $user->last_name;
                                        }
                                        echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-control tom-select" id="user" data-placeholder="' . lang("select") . " " . lang("user") . '" style="width:100%;"');
                                        ?>
                                    </div>
                                </div>

                                <div class="col-sm-3">
                                    <div class="mb-3">
                                        <label class="form-label" for="start_date"><?= lang("Dia"); ?></label>
                                        <?= form_input('start_date', set_value('start_date'), 'class="form-control datetimepicker" id="start_date"');?>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <button type="submit" style="width: 100%" class="btn btn-primary">Buscar</button>
                                </div>
                            </div>
                            <?= form_close();?>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="table-responsive">
                <div class="table-responsive">
                                <table id="SLRData" class="table table-striped table-bordered table-condensed table-hover">
                                    <thead>
                                        <tr>
                                            <td colspan="11" class="p0"><input type="text" class="form-control b0" name="search_table" id="search_table" placeholder="<?= lang('type_hit_enter'); ?>" style="width:100%;"></td>
                                        </tr>
                                        <tr class="active">
                                            <th style="max-width:30px;"><?= lang("id"); ?></th>
                                            <th class="col-sm-2"><? if($Settings->fe == '1'){ echo lang("Consecutivo"); }else{ echo lang("N° Fctura"); } ?></th>
                                            <th class="col-sm-2"><?= lang("date"); ?></th>
                                            <th class="col-sm-2"><?= lang("Identificacion"); ?></th>
                                            <th class="col-sm-2"><?= lang("customer"); ?></th>
                                            <th class="col-sm-2"><?= lang("Monto Gravado"); ?></th>
                                            <th class="col-sm-2"><?= lang("Monto Exento"); ?></th>
                                            <th class="col-sm-1"><?= lang("discount"); ?></th>
                                            <th class="col-sm-1"><?= lang("subtotal"); ?></th>
                                            <th class="col-sm-1"><?= lang("Impuesto"); ?></th>
                                            <th class="col-sm-2"><?= lang("grand_total"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="11" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="active">
                                            <th style="max-width:30px;"><input type="text" class="text_filter" placeholder="[<?= lang('id'); ?>]"></th>
                                            <th class="col-sm-1"><input type="text" class="text_filter" placeholder="[<? if($Settings->fe == '1'){ echo lang("Consecutivo"); }else{ echo lang("N° Fctura"); } ?>]"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"><input type="text" class="text_filter" placeholder="[<?= lang('identificacion'); ?>]"></th>
                                            <th class="col-sm-2"><input type="text" class="text_filter" placeholder="[<?= lang('customer'); ?>]"></th>
                                            <th class="col-sm-1"><?= lang("Monto Gravado"); ?></th>
                                            <th class="col-sm-2"><?= lang("Monto Exento"); ?></th>
                                            <th class="col-sm-1"><?= lang("discount"); ?></th>
                                            <th class="col-sm-1"><?= lang("subtotal"); ?></th>
                                            <th class="col-sm-1"><?= lang("Impuesto"); ?></th>
                                            <th class="col-sm-2"><?= lang("grand_total"); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($this->input->post('customer')) { ?>
                    <div class="row">
                        <div class="col-md-3">
                            <button class="btn bg-purple btn-lg btn-block" style="cursor:default;">
                                <strong><?= $this->tec->formatMoney($total_sales->number, 0); ?></strong>
                                <?= lang("sales"); ?>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary btn-lg btn-block" style="cursor:default;">
                                <strong><?= $this->tec->formatMoney($total_sales->amount); ?></strong>
                                <?= lang("amount"); ?>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success btn-lg btn-block" style="cursor:default;">
                                <strong><?= $this->tec->formatMoney($total_sales->paid); ?></strong>
                                <?= lang("paid"); ?>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-warning btn-lg btn-block" style="cursor:default;">
                                <strong><?= $this->tec->formatMoney($total_sales->amount-$total_sales->paid); ?></strong>
                                <?= lang("due"); ?>
                            </button>
                        </div>
                    </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</section>



<script type="text/javascript">
    $(function () {
        $('.datetimepicker').tempusDominus = new TempusDominus({
            format: 'YYYY-MM-DD'
        });
        $('.datepicker').tempusDominus = new TempusDominus({format: 'YYYY-MM-DD', showClear: true, showClose: true, useCurrent: false, widgetPositioning: {horizontal: 'auto', vertical: 'bottom'}, widgetParent: $('.dataTable tfoot')});
    });
</script>
