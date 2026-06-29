<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<script type="text/javascript">
    $(document).ready(function() {

        function status(x) {

            if (x == '1') {
                return '<div class="text-center"><span class="sale_status label label-success">Activado</span></div>';
            } else {
                return '<div class="text-center"><span class="sale_status label label-primary">Desactivado</span></div>';
            }
        }

        var table = new Tabulator('#SLData', {

            'ajax' : { url: '<?=site_url('products/get_list_prices');?>', type: 'POST', "data": function ( d ) {
                d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
            }},
            "buttons": [
                { extend: 'copyHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3 ] } },
                { extend: 'excelHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3 ] } },
                { extend: 'csvHtml5', 'footer': true, exportOptions: { columns: [ 0, 1, 2, 3 ] } },
                { extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true,
                    exportOptions: { columns: [ 0, 1, 2, 3 ] } },
                { extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
                { "data": "id_lista_precios", "visible": false },
                { "data": "nombre_l_precio" },
                { "data": "entry_by" },
                { "data": "status_l_precio", "render": status },
                { "data": "Actions", "searchable": false, "orderable": false }
            ],
            'columnDefs': [
                {
                    "targets": 0, 
                    "className": "text-center",
                    "width": "20%"
                },
                {
                    "targets": 1, 
                    "className": "text-center",
                    "width": "20%"
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
                }],
            "fnRowCallback": function (nRow, aData, iDisplayIndex) {
                nRow.id = aData.id;
                return nRow;
            },
            "footerCallback": function (  tfoot, data, start, end, display ) {
                var api = this.api(), data;
                // $(api.column(3).footer()).html( cf(api.column(3).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                // $(api.column(4).footer()).html( cf(api.column(4).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                // $(api.column(5).footer()).html( cf(api.column(5).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                // $(api.column(6).footer()).html( cf(api.column(6).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
                // $(api.column(7).footer()).html( cf(api.column(7).data().reduce( function (a, b) { return pf(a) + pf(b); }, 0)) );
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

<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('list_results'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                <div class="table-responsive">
                        <table id="SLData" class="table table-striped table-bordered table-condensed table-hover">
                            <thead>
                            <tr class="active">
                                <th style="max-width:30px;"><?= lang("id"); ?></th>
                                <th class="col-1 text-center"><?= lang("name"); ?></th>
                                <th><?= lang("user"); ?></th>
                                <th class="col-1"><?= lang("status"); ?></th>
                                <th style="min-width:115px; max-width:115px; text-align:center;"><?= lang("actions"); ?></th>
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
                                <th class="col-2"><?= lang("name"); ?></th>
                                <th class="col-2"><?= lang("user"); ?></th>
                                <th class="col-sm-1">
                                    <select class="tom-select select_filter">
                                        <option value="0">Activado</option>
                                        <option value="1">Desactivado</option>
                                    </select></th>

                                <th class="col-sm-1"></th>
                            </tr>
                            <tr>
                                <td colspan="12" class="p0"><input type="text" class="form-control b0" name="search_table" id="search_table" placeholder="<?= lang('type_hit_enter'); ?>" style="width:100%;"></td>
                            </tr>
                            </tfoot>
                        </table>
                </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
</section>


<script type="text/javascript">
    $(document).ready(function() {
        $('.datepicker').tempusDominus = new TempusDominus({format: 'YYYY-MM-DD', showClear: true, showClose: true, useCurrent: false, widgetPositioning: {horizontal: 'auto', vertical: 'bottom'}, widgetParent: $('.dataTable tfoot')});
    });
</script>

