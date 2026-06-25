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
                url: '<?=site_url('reports/get_missing_inventory/' . $v);?>', type: 'POST', "data": function (d) {
                    d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
                }
            },
            "buttons": [
                {extend: 'copyHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3]}},
                {extend: 'excelHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3]}},
                {extend: 'csvHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3]}},
                {
                    extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true,
                    exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]}
                },
                {extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
                {"data": "id", "visible": false},
                {"data": "product_id"},
                {"data": "producto"},
                {"data": "faltante", "render": formatNumber},
                {"data": "emitido", "render": hrld}

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
                
            "footerCallback": function (tfoot, data, start, end, display) {
                var api = this.api(), data;

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
                            <?= form_open("reports/missing_inventory"); ?>
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
                                        <th style="max-width:30px;"><?= lang("id"); ?></th>
                                        <th class="col-sm-2"><?= lang("id"); ?></th>
                                        <th class="col-sm-2"><?= lang("Producto"); ?></th>
                                        <th class="col-sm-1"><?= lang("Faltante"); ?></th>
                                        <th class="col-sm-1"><?= lang("Emitido"); ?></th>
   
                                    </tr>
                                    </thead>
                                    <tbody>
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
