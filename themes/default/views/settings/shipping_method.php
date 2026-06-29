<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<script type="text/javascript">
    $(document).ready(function() {

        var table = $('#StData').DataTable({

            'ajax' : { url: '<?=site_url('settings/get_shipping');?>', type: 'POST', "data": function ( d ) {
                d.<?=$this->security->get_csrf_token_name();?> = "<?=$this->security->get_csrf_hash()?>";
            }},
            "buttons": [{ extend: 'colvis', text: 'Columns'}],
            "columns": [
            { "data": "id_shipping_method", "visible": true },
            { "data": "name" },
            { "data": "Actions", "searchable": false, "orderable": false }
            ]

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
                        <table id="StData" class="table table-bordered table-hover table-striped">
                            <thead>
                                <tr>
                                    <th><?= lang("id"); ?></th>
                                    <th><?= lang("name"); ?></th>
                                    <th style="width:65px;"><?= lang("actions"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
                                </tr>
                            </tbody>

                        </table>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
</section>
