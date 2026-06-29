<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";

if ($this->input->post('customer')) {
    $v .= "&customer=" . $this->input->post('customer');
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
        var table = new Tabulator('#SLRData', {

            'ajax': {
                url: '<?=site_url('reports/get_sale_fe/' . $v);?>', type: 'POST', "data": function (d) {
                    d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
                }
                
            },
            "buttons": [
                {extend: 'copyHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7,8]}},
                {extend: 'excelHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7,8]}},
                // {extend: 'csvHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]}},
                // {extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]}},
                {extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
                {"data": "name"},
                {"data": "tax_0", "render": currencyFormat},
                {"data": "tax_1", "render": currencyFormat},
                {"data": "tax_2", "render": currencyFormat},
                {"data": "tax_4", "render": currencyFormat},
                {"data": "tax_13", "render": currencyFormat},
                {"data": "exonerado", "render": currencyFormat},
                {"data": "subtotal", "render": currencyFormat},
                {"data": "total", "render": currencyFormat}
                

            ],
            'columnDefs': [
                {
                    "targets": 0, 
                    "className": "text-center",
                    "width": "50%"
                },
                {
                    "targets": 1, 
                    "className": "text-center",
                    "width": "5%"
                },
                {
                    "targets": 2, 
                    "className": "text-center",
                    "width": "20%"
                },
                {
                    "targets": 3, 
                    "className": "text-center",
                    "width": "20%"
                },
                {
                    "targets": 4, 
                    "className": "text-center",
                    "width": "20%"
                },                
                {
                    "targets": 5,
                    "className": "text-center",
                    "width": "20%"
                },                
                {
                    "targets": 6,
                    "className": "text-center",
                    "width": "20%"
                },                
                {
                    "targets": 3, 
                    "className": "text-center",
                    "width": "20%"
                }],
            "footerCallback": function (tfoot, data, start, end, display ) {
                var api = this.api(), data;
                $(api.column(1).footer()).html( cf(api.column(1).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(2).footer()).html( cf(api.column(2).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(3).footer()).html( cf(api.column(3).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(4).footer()).html( cf(api.column(4).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(5).footer()).html( cf(api.column(5).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(6).footer()).html( cf(api.column(6).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                $(api.column(7).footer()).html( cf(api.column(7).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
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
                    <a href="#" class="btn btn-default btn-sm toggle_form float-end"><?= lang("show_hide"); ?></a>
                    <h3 class="box-title"><?= lang('customize_report'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="table-responsive">
                                <table id="SLRData"
                                       class="table table-striped table-bordered table-condensed table-hover">
                                    <thead>
                                    <tr class="active">
                                        <th class="col-sm-1">Cliente</th>
                                        <th class="col-sm-1">Tot. Imp. 0% Cobrado</th>
                                        <th class="col-sm-1">Tot. Imp. 1% Cobrado</th>
                                        <th class="col-sm-1">Tot. Imp. 2% Cobrado</th>
                                        <th class="col-sm-1">Tot. Imp. 4% Cobrado</th>
                                        <th class="col-sm-1">Tot. Imp. 13% Cobrado</th>
                                        <th class="col-sm-1">Total ventas exoneradas</th>
                                        <th class="col-sm-1">Tot Monto Cobrado sin impuesto</th>
                                        <th class="col-sm-1">Tot Monto Cobrado mas impuesto</th>
   
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
    </div>
</section>


<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"
        type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        $('.datetimepicker').tempusDominus = new TempusDominus({
            format: 'YYYY-MM-DD HH:mm'
        });
        $('.datepicker').tempusDominus = new TempusDominus({
            format: 'YYYY-MM-DD',
            showClear: true,
            showClose: true,
            useCurrent: false,
            widgetPositioning: {horizontal: 'auto', vertical: 'bottom'},
            widgetParent: $('.dataTable tfoot')
        });
    });
</script>
