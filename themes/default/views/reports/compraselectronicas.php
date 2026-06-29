<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";

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

        function status(x) {
            var a = 'No Procesado';
            var b = 'Aceptado';
            var c = 'Procesando';
            var d = 'Error';
            var e = 'Rechazado';
            var f = 'Recibido';
            var g = 'Enviado a Hacienda';
            if (x == 'aceptado') {
                return '<div class="text-center"><span class="sale_status label label-success">' + b + '</span></div>';
            } else if (x == 'procesando') {
                return '<div class="text-center"><span class="sale_status label label-primary">' + c + '</span></div>';
            } else if (x == 'error') {
                return '<div class="text-center"><span class="sale_status label label-danger">' + d + '</span></div>';
            } else if (x == 'rechazado') {
                return '<div class="text-center"><span class="sale_status label label-danger">' + e + '</span></div>';
            } else if (x == 'recibido') {
                return '<div class="text-center"><span class="sale_status label label-warning">' + f + '</span></div>';
            } else if (x == '5') {
                return '<div class="text-center"><span class="sale_status label label-info">' + g + '</span></div>';
            } else {
                return '<div class="text-center"><span class="sale_status label label-default">' + a + '</span></div>';
            }
        }

        var table = new Tabulator('#SLData', {

            'ajax': {
                url: '<?=site_url('reports/get_compras_electronicas' . $v);?>', type: 'POST', "data": function (d) {
                    d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
                }
            },
            "buttons": [
                {
                    extend: 'copyHtml5',
                    'footer': true,
                    exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17]}
                },
                {
                    extend: 'excelHtml5',
                    'footer': true,
                    exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17]}
                },
                {
                    extend: 'csvHtml5',
                    'footer': true,
                    exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17]}
                },
                {
                    extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true,
                    exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17]}
                },
                {extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
                {"data": "FechaEmisionDoc", "render": hrld},
                {"data": "Estatus", "render": status},
                {"data": "documento"},
                {"data": "ConsecutivoDocEmisor"},
                {"data": "nombre_emisor"},
                {"data": "tipo_doc_emisor"},
                {"data": "NumeroCedulaEmisor"},
                {"data": "correo_emisor"},
                {"data": "TotalServGravados", "render": currencyFormat},
                {"data": "TotalServExentos", "render": currencyFormat},
                {"data": "TotalMercanciasGravadas", "render": currencyFormat},
                {"data": "TotalMercanciasExentas", "render": currencyFormat},
                {"data": "TotalGravado", "render": currencyFormat},
                {"data": "TotalExento", "render": currencyFormat},
                {"data": "MontoTotalImpuesto", "render": currencyFormat},
                {"data": "TotalVenta", "render": currencyFormat},
                {"data": "TotalVentaNeta", "render": currencyFormat},
                {"data": "TotalFactura", "render": currencyFormat}
//                {"data": "TotalFactura", "render": currencyFormat}

            ],
            "fnRowCallback": function (nRow, aData, iDisplayIndex) {
                nRow.id = aData.id;
                return nRow;
            },
            "footerCallback": function (tfoot, data, start, end, display) {
                var api = this.api(), data;
                $(api.column(8).footer()).html(cf(api.column(8).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(9).footer()).html(cf(api.column(9).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(10).footer()).html(cf(api.column(10).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(11).footer()).html(cf(api.column(11).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(12).footer()).html(cf(api.column(12).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(13).footer()).html(cf(api.column(13).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(14).footer()).html(cf(api.column(14).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(15).footer()).html(cf(api.column(15).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(16).footer()).html(cf(api.column(16).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
                $(api.column(17).footer()).html(cf(api.column(17).data().reduce(function (a, b) {
                    return pf(a) + pf(b);
                }, 0)));
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
                    <a href="#" style="display: block; overflow: hidden; margin-bottom: 5px;"
                       class="btn btn-default btn-sm toggle_form float-end"><?= lang("show_hide"); ?></a>
                    <hr style="display: block;overflow: hidden;clear: both;"/>
                    <h3 class="box-title"><b>Informe de Compras Mensuales</b> -
                        <small style="font-size: 15px;"><i>cuando se abre este reporte se presentan las ventas diarias
                                del mes actual <?= date('m-Y') ?>,
                                si desea puede usar el siguiente formulario para realizar busquedas personalizadas</i>
                        </small>
                    </h3>
                </div>
                <div class="box-body">
                    <div id="form" class="card border-warning">
                        <div class="card-body">
                            <?= form_open("reports/compras_electronicas"); ?>

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
                                        <label class="form-label" for="start_date">Mes</label>
                                        <?= form_input('start_date', set_value('start_date'), 'class="form-control datetimepicker" id="start_date"'); ?>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <button type="submit" style="width: 100%" class="btn btn-primary">Buscar</button>
                                </div>
                            </div>
                            <?= form_close(); ?>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="table-responsive">
                                <table id="SLData"
                                       class="table table-striped table-bordered table-condensed table-hover">
                                    <thead>
                                    <tr>
                                        <td colspan="18" class="p0"><input type="text" class="form-control b0"
                                                                           name="search_table" id="search_table"
                                                                           placeholder="<?= lang('type_hit_enter'); ?>"
                                                                           style="width:100%;"></td>
                                    </tr>
                                    <tr class="active">
                                        <th class="col-2"><?= lang("date"); ?></th>
                                        <th class="col-1"><?= lang("status"); ?> de Hacienda</th>
                                        <th class="col-1"><?= lang("document_type"); ?></th>
                                        <th class="col-1"><?= lang("consecutive"); ?></th>
                                        <th class="col-2">Proveedor</th>
                                        <th class="col-2">T. Doc</th>
                                        <th class="col-2">N° Doc</th>
                                        <th class="col-2">Correo</th>
                                        <th class="col-2">Total Serv. Gravados</th>
                                        <th class="col-2">Total Serv. Exentos</th>
                                        <th class="col-2">Total Merc. Gravados</th>
                                        <th class="col-2">Total Merc. Exentos</th>
                                        <th class="col-2">Total Gravados</th>
                                        <th class="col-2">Total Exentos</th>
                                        <th>Monto Total Impuesto</th>
                                        <th class="col-2">Total Compra</th>
                                        <th class="col-2">Total Compra Neta</th>
                                        <th class="col-2">Total Comprobante</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td colspan="18"
                                            class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                                    </tr>
                                    </tbody>
                                    <tfoot>
                                    <tr class="active">
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
                                        <th class="col-sm-1"></th>
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
                                    <strong><?= $this->tec->formatMoney($total_sales->amount - $total_sales->paid); ?></strong>
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


<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"
        type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        $('.datetimepicker').tempusDominus = new TempusDominus({
            format: 'YYYY-MM'
        });
        $('.datepicker').tempusDominus = new TempusDominus({
            format: 'YYYY-MM',
            showClear: true,
            showClose: true,
            useCurrent: false,
            widgetPositioning: {horizontal: 'auto', vertical: 'bottom'},
            widgetParent: $('.dataTable tfoot')
        });
    });
</script>
