<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

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

        var table = $('#SLData').DataTable({

            'ajax': {
                url: '<?=site_url('cargadocumentos/get_purchases_h');?>', type: 'POST', "data": function (d) {
                    d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
                }
            },
            "buttons": [
                {extend: 'copyHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]}},
                {extend: 'excelHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]}},
                {extend: 'csvHtml5', 'footer': true, exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]}},
                {
                    extend: 'pdfHtml5', orientation: 'landscape', pageSize: 'A4', 'footer': true,
                    exportOptions: {columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]}
                },
                {extend: 'colvis', text: 'Columns'},
            ],
            "columns": [
                {"data": "id_documento", "visible": false},
                {"data": "documento"},
                {"data": "ConsecutivoDocEmisor"},
                {"data": "FechaEmisionDoc"},
                {"data": "nombre_emisor"},
                {"data": "NumeroCedulaEmisor"},
                {"data": "CodigoMoneda"},
                {"data": "TipoCambio"},
                {"data": "MontoTotalImpuesto", "render": currencyFormat},
                {"data": "TotalFactura", "render": currencyFormat},
                {"data": "Estatus", "render": status},
                {"data": "status_hacienda"},
                {"data": "Actions", "searchable": false, "orderable": false}
            ],  //("id_documento, MontoTotalImpuesto, documento, nombre_emisor, NumeroCedulaEmisor, TotalFactura, ConsecutivoDocEmisor, FechaEmisionDoc, Estatus, CodigoMoneda, TipoCambio");
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
<style>
    /* layout.css Style */
    .upload-drop-zone {
        height: 100px;
        border-width: 2px;
        margin-bottom: 20px;
    }

    /* skin.css Style*/
    .upload-drop-zone {
        color: #ccc;
        border-style: dashed;
        border-color: #ccc;
        line-height: 100px;
        text-align: center
    }

    .upload-drop-zone.drop {
        color: #222;
        border-color: #222;
    }
</style>
<section class="content">
    <div class="row">
        <div style="col-xs-12">
            <div class="panel panel-default" style="margin-bottom: 0;">
                <div class="panel-body">

                    <!-- Standar Form -->
                    <h4 style="    text-align: center; width: 100%;">Select XML files from your computer</h4>
                    <form action="/cargadocumentos" method="post" enctype="multipart/form-data"
                          style="text-align: center" id="js-upload-form">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>"
                               value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <div class="form-inline">
                            <div class="form-group">
                                <input type="file" name="userfiles[]" id="js-upload-files" multiple>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary" id="js-upload-submit">Upload files
                            </button>
                        </div>
                    </form>

                    <!-- Drop Zone -->
                    <h4>Or drag and drop XML files below</h4>
                    <div class="upload-drop-zone" id="drop-zone">
                        Just drag and drop XML files here
                    </div>


                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('list_results'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="SLData" class="table table-striped table-bordered table-condensed table-hover">
                            <thead>
                            <tr class="active">
                                <th style="max-width:30px;"><?= lang("id"); ?></th>
                                <th class="col-xs-1"><?= lang("document_type"); ?></th>
                                <th class="col-xs-1"><?= lang("consecutive"); ?></th>
                                <th class="col-xs-2"><?= lang("date"); ?></th>
                                <th class="col-xs-2"><?= lang("customer"); ?></th>
                                <th><?= lang("ccf2"); ?></th>
                                <th><?= lang("Moneda"); ?></th>
                                <th><?= lang("Tipo Cambio"); ?></th>
                                <th><?= lang("tax"); ?></th>
                                <th class="col-xs-1"><?= lang("grand_total"); ?></th>
                                <th class="col-xs-1"><?= lang("status"); ?> de Hacienda</th>
                                <th class="col-xs-1">Respuesta Hacienda</th>
                                <th style="min-width:115px; max-width:115px; text-align:center;"><?= lang("actions"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td colspan="13" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr class="active">
                                <th style="max-width:30px;"><?= lang("id"); ?></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th><?= lang("tax"); ?></th>
                                <th><?= lang("grand_total"); ?></th>
                                <th></th>
                                <th></th>
                                <th style="min-width:115px; max-width:115px; text-align:center;"></th>
                            </tr>
                            <tr>
                                <td colspan="13" class="p0"><input type="text" class="form-control b0"
                                                                   name="search_table" id="search_table"
                                                                   placeholder="<?= lang('type_hit_enter'); ?>"
                                                                   style="width:100%;"></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
    
                    <!-- Modal -->
                <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Resultado de la carga de documentos</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <ol id='resultadoslist' style='overflow-y: scroll;    max-height: 300px;' class='lista'>

                                </ol>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
                    
</section>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"
        type="text/javascript"></script>
<script type="text/javascript">
    $(document).ready(function () {
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

<script type="text/javascript">
    +function ($) {
        'use strict';

        // UPLOAD CLASS DEFINITION
        // ======================

        var dropZone = document.getElementById('drop-zone');
        var uploadForm = document.getElementById('js-upload-form');

        var startUpload = function (files) {
            var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
            var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
            var fd = new FormData(); // Create a FormData object
            for (var i = 0; i < files.length; i++) { // Loop all files
                fd.append('file_' + i, files[i]); // Create an append() method, one for each file dropped
            }
            fd.append('nbr_files', i); // The last append is the number of files
            fd.append(csrfName, csrfHash);
            $.ajax({ // JQuery Ajax
                type: 'POST',
                url: 'cargadocumentos', // URL to the PHP file which will insert new value in the database
                data: fd, // We send the data string
                processData: false,
                contentType: false,
                success: function(data) {
                    $('#resultadoslist').html(data);
                    $('#exampleModal').modal('show');
                    $('#SLData').DataTable().ajax.reload();
                },
                xhrFields: { //
                    onprogress: function (e) {
                        if (e.lengthComputable) {
                            var pourc = e.loaded / e.total * 100;
                            $('.progress-bar').attr('style', 'width: ' + pourc + '%').attr('aria-valuenow', pourc).text(pourc + '%');
                        }
                    }
                },
            });

        }

        uploadForm.addEventListener('submit', function (e) {
            var uploadFiles = document.getElementById('js-upload-files').files;
            e.preventDefault()

            startUpload(uploadFiles)
        })

        dropZone.ondrop = function (e) {
            e.preventDefault();
            this.className = 'upload-drop-zone';

            startUpload(e.dataTransfer.files)
        }

        dropZone.ondragover = function () {
            this.className = 'upload-drop-zone drop';
            return false;
        }

        dropZone.ondragleave = function () {
            this.className = 'upload-drop-zone';
            return false;
        }

    }(jQuery);
</script>

