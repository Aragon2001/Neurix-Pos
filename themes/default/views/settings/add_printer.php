<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="fa fa-print"></i> <?= lang('enter_info'); ?></div>
                <div class="card-body">
                    <?php echo form_open_multipart("settings/add_printer"); ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="title"><?= lang("title"); ?></label>
                                <?= form_input('title', set_value('title'), 'class="form-control" id="title" required="required"'); ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="type"><?= lang('type'); ?></label>
                                <?php $topts = array('windows' => lang('windows'), 'web' => 'web'); ?>
                                <?= form_dropdown('type', $topts, set_value('type', 'windows'), 'class="form-control tom-select" id="type" required="required" style="width:100%;"'); ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="profile"><?= lang('profile'); ?></label>
                                <?php $popts = array('default' => lang('default'), 'simple' => lang('simple'), 'SP2000' => lang('star_branded'), 'TEP-200M' => lang('epson_tep'), 'P822D' => lang('P822D')); ?>
                                <?= form_dropdown('profile', $popts, set_value('profile', 'default'), 'class="form-control tom-select" id="profile" required="required" style="width:100%;"'); ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="char_per_line"><?= lang('char_per_line'); ?></label>
                                <?= form_input('char_per_line', '45', 'class="form-control" id="char_per_line" required="required"'); ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div id="printer-path-fields">
                                <div class="mb-3">
                                    <label class="form-label" for="ip_address"><?= lang('ip_address'); ?></label>
                                    <?= form_input('ip_address', set_value('ip_address'), 'class="form-control" id="ip_address"'); ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="path"><?= lang('path'); ?></label>
                                    <?= form_input('path', set_value('path'), 'class="form-control" id="path"'); ?>
                                    <small class="form-text text-muted">
                                        <?= lang('printer_help_windows'); ?><br>
                                        <?= lang('printer_help_linux'); ?>
                                    </small>
                                </div>
                            </div>

                            <div id="printer-network-fields" style="display:none;">
                                <div class="mb-3">
                                    <label class="form-label" for="port"><?= lang('port'); ?></label>
                                    <?= form_input('port', set_value('port', '9100'), 'class="form-control" id="port"'); ?>
                                    <small class="form-text text-muted"><?= lang('printer_port_hint'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <?php echo form_submit('add_printer', lang("add_printer"), 'class="btn btn-primary"'); ?>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    var typeSelect = document.getElementById('type');
    var pathFields = document.getElementById('printer-path-fields');
    var networkFields = document.getElementById('printer-network-fields');
    if (!typeSelect || !pathFields || !networkFields) return;

    function toggleFields() {
        if (typeSelect.value === 'web') {
            networkFields.style.display = '';
            pathFields.style.display = 'none';
        } else {
            networkFields.style.display = 'none';
            pathFields.style.display = '';
        }
    }

    typeSelect.addEventListener('change', toggleFields);
    toggleFields();
});
</script>
