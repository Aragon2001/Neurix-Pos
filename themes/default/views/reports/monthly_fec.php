<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";

if ($this->input->post('customer')) {
    $v .= "&customer=" . $this->input->post('customer');
}
if ($this->input->post('user')) {
    $v .= "&user=" . $this->input->post('user');
}
if ($this->input->post('start_date')) {
    $v .= "&start_date=" . $this->input->post('start_date');
}
if ($this->input->post('end_date')) {
    $v .= "&end_date=" . $this->input->post('end_date');
}

?>

<script type="text/javascript">
    $(document).ready(function () {
        var table = $('#SLRData').DataTable({

            'ajax': {
                url: '<?=site_url('reports/get_monthly_fec/' . $v);?>', type: 'POST', "data": function (d) {
                    d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
                }
                
            },
            "buttons": [
                {extend: 'copyHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26]}},
                {extend: 'excelHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26]}},
                {extend: 'csvHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]}},
                {extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]}},
                {extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
                {"data": "id", "visible": true},
                {"data": "identificacion"},
                {"data": "NombreCompleto"},
                {"data": "FechaFactura", "render": hrld},
                {"data": "codigoMoneda"},
                {"data": "tipoCambio"},
                {"data": "creditoCompra"},
                {"data": "consecutivo"},
                {"data": "TotalServGravados", "render": currencyFormat},
                {"data": "TotalServExentos", "render": currencyFormat},
                {"data": "TotalMercanciasGravadas", "render": currencyFormat},
                {"data": "TotalMercanciasExentas", "render": currencyFormat},
                {"data": "TotalMercExonerada", "render": currencyFormat},
                {"data": "TotalServExonerado", "render": currencyFormat},
                {"data": "TotalGravado", "render": currencyFormat},
                {"data": "TotalExento", "render": currencyFormat},
                {"data": "TotalExonerado", "render": currencyFormat},
                {"data": "TotalVenta", "render": currencyFormat},
                {"data": "TotalDescuentos", "render": currencyFormat},
                {"data": "TotalVentaNeta", "render": currencyFormat},
                {"data": "TotalImpuesto", "render": currencyFormat},
                {"data": "tarifa0", "render": currencyFormat},
                {"data": "tarifa1", "render": currencyFormat},
                {"data": "tarifa2", "render": currencyFormat},
                {"data": "tarifa4", "render": currencyFormat},
                {"data": "tarifa13", "render": currencyFormat},
                {"data": "TotalComprobante", "render": currencyFormat}
                

            ],
            'columnDefs': [
                {
                    "targets": 3, // your case first column
                    "className": "text-center",
                    "width": "4%"
                },
                {
                    "targets": 4, // your case first column
                    "className": "text-center",
                    "width": "4%"
                },
                {
                    "targets": 2, // your case first column
                    "className": "text-center",
                    "width": "20%"
                },
                {
                    "targets": 1, // your case first column
                    "className": "text-center",
                    "width": "4%"
                }],
            "footerCallback": function (tfoot, data, start, end, display ) {
                var api = this.api(), data;
                
                $(api.column(8).footer()).html( cf(api.column(8).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(9).footer()).html( cf(api.column(9).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(10).footer()).html( cf(api.column(10).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(11).footer()).html( cf(api.column(11).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(12).footer()).html( cf(api.column(12).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(13).footer()).html( cf(api.column(13).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(14).footer()).html( cf(api.column(14).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(15).footer()).html( cf(api.column(15).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(16).footer()).html( cf(api.column(16).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(17).footer()).html( cf(api.column(17).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(18).footer()).html( cf(api.column(18).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(19).footer()).html( cf(api.column(19).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(20).footer()).html( cf(api.column(20).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(21).footer()).html( cf(api.column(21).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(22).footer()).html( cf(api.column(22).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(23).footer()).html( cf(api.column(23).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(24).footer()).html( cf(api.column(24).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(25).footer()).html( cf(api.column(25).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(26).footer()).html( cf(api.column(26).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
            }

        });

        $('#search_table').on('keyup change', function (e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (((code == 13 && table.search() !== this.value) || (table.search() !== '' && this.value === ''))) {
                table.search(this.value).draw();
            }
        });

        table.columns().every(function () {
            var self = this;
            $('input.datepicker', this.footer()).on('dp.change', function (e) {
                self.search(this.value).draw();
            });
            $('input:not(.datepicker)', this.footer()).on('keyup change', function (e) {
                var code = (e.keyCode ? e.keyCode : e.which);
                if (((code == 13 && self.search() !== this.value) || (self.search() !== '' && this.value === ''))) {
                    self.search(this.value).draw();
                }
            });
            $('select', this.footer()).on('change', function (e) {
                self.search(this.value).draw();
            });
        });

    });
</script>

<script type="text/javascript">
    $(document).ready(function () {
        $('.toggle_form').click(function () {
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
                            <?= form_open("reports/monthly_fec"); ?>
                            <div class="row">
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
                                    <button type="submit" class="btn btn-primary"><?= lang("submit"); ?></button>
                                </div>
                            </div>
                            <?= form_close(); ?>
                        </div>
                    </div>


                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="table-responsive">
                                <table id="SLRData"
                                       class="table table-striped table-bordered table-condensed table-hover">
                                    <thead>
                                    <tr class="active">
                                        <th style="max-width:30px;">Id</th>
                                        <th class="col-sm-2">Identificaci&oacute;n</th>
                                        <th class="col-sm-1">Nombre completo</th>
                                        <th class="col-sm-1">Fecha factura</th>
                                        <th class="col-sm-1">Codigo moneda</th>
                                        <th class="col-sm-1">Tipo cambio</th>
                                        <th class="col-sm-1">Credito compras</th>
                                        <th class="col-sm-1">N&uacute;mero de factura</th>
                                        <th class="col-sm-1">Total Servicios Gravados</th>
                                        <th class="col-sm-1">Total Servicios Exentos</th>
                                        <th class="col-sm-1">Total Mercancia Gravadas</th>
                                        <th class="col-sm-1">Total Mercancia Exentas</th>
                                        <th class="col-sm-1">Total Mercancia Exonerada</th>
                                        <th class="col-sm-1">Total Servicio Exonerado</th>
                                        <th class="col-sm-1">Total Gravado</th>
                                        <th class="col-sm-1">Total Exento</th>
                                        <th class="col-sm-1">Total Exonerado</th>
                                        <th class="col-sm-1">Total Venta</th>
                                        <th class="col-sm-1">Descuento Compra</th>
                                        <th class="col-sm-1">Total Venta Neta</th>
                                        <th class="col-sm-1">Full Tax Compra</th>
                                        <th class="col-sm-1">Tarifa 0%</th>
                                        <th class="col-sm-1">Tarifa 1%</th>
                                        <th class="col-sm-1">Tarifa 2%</th>
                                        <th class="col-sm-1">Tarifa 4%</th>
                                        <th class="col-sm-1">Tarifa 13%</th>
                                        <th class="col-sm-1">Total Comprobante</th>
   
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tfoot>
                                        <tr class="active">
                                            <th style="max-width:30px;"></th>
                                            <th class="col-sm-1"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-1"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-1"></th>
                                            <th class="col-sm-1"></th>
                                            <th class="col-sm-1"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                            <th class="col-sm-2"></th>
                                        </tr>
                                    </tfoot>
                                    <tr>
                                        <td colspan="10"
                                            class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
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
</section>

<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"
        type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        $('.datetimepicker').datetimepicker({
            format: 'YYYY-MM-DD HH:mm'
        });
        $('.datepicker').datetimepicker({
            format: 'YYYY-MM-DD',
            showClear: true,
            showClose: true,
            useCurrent: false,
            widgetPositioning: {horizontal: 'auto', vertical: 'bottom'},
            widgetParent: $('.dataTable tfoot')
        });
    });
</script>
