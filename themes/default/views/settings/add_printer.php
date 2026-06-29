<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('enter_info'); ?></h3>
                </div>
                <div class="box-body">
                    <?php echo form_open_multipart("settings/add_printer"); ?>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="title"><?= $this->lang->line("title"); ?></label>
                            <?= form_input('title', set_value('title'), 'class="form-control input-sm" id="title"'); ?>
                        </div>

                        <div class="mb-3">
                            <?= lang('type', 'type'); ?>
                            <?php
                            $topts = array('windows' => lang('windows'), 'web' => 'web');
                            ?>
                            <?= form_dropdown('type', $topts, set_value('type', 'network'), 'class="form-control tom-select" id="type" required="required" style="width:100%;"'); ?>
                        </div>
                        <div class="path">
                            <div class="mb-3">
                                <?= lang('profile', 'profile'); ?>
                                <?php
                                $popts = array('default' => lang('default'), 'simple' => lang('simple'), 'SP2000' => lang('star_branded'), 'TEP-200M' => lang('epson_tep'), 'P822D' => lang('P822D'));
                                ?>
                                <?= form_dropdown('profile', $popts, set_value('profile', 'default'), 'class="form-control tom-select" id="profile" required="required" style="width:100%;"'); ?>
                            </div>

                            <div class="mb-3">
                                <?= lang('char_per_line', 'char_per_line'); ?>
                                <?= form_input('char_per_line', '45', 'class="form-control" id="char_per_line" required="required"'); ?>
                            </div>

                            <div class="mb-3">
                                <?= lang('ip_address', 'ip_address'); ?>
                                <?= form_input('ip_address', set_value('ip_address'), 'class="form-control" id="ip_address"'); ?>
                            </div>
                            <div class="path" style="display:none;">
                                <div class="mb-3">
                                    <?= lang('path', 'path'); ?>
                                    <?= form_input('path', set_value('path'), 'class="form-control" id="path"'); ?>
                                    <span class="help-block">
                                    <strong>For Windows:</strong> (Local USB, Serial or Parallel Printer): Share the printer and enter the share name for your printer here or for Server Message Block (SMB): enter as a smb:// url format such as <code>smb://computername/Receipt Printer</code><br>
                                    <strong>For Linux:</strong> Parallel as <code>/dev/lp0</code>, USB as <code>/dev/usb/lp1</code>, USB-Serial as <code>/dev/ttyUSB0</code>, Serial as <code>/dev/ttyS0</code><br>
                                </span>
                                </div>
                            </div>

                            <div class="network" style="display:none;">
                                <div class="mb-3">
                                    <?= lang('ip_address', 'ip_address'); ?>
                                    <?= form_input('ip_address', set_value('ip_address'), 'class="form-control" id="ip_address"'); ?>
                                </div>

                                <div class="mb-3">
                                    <?= lang('port', 'port'); ?>
                                    <?= form_input('port', set_value('port', '9100'), 'class="form-control" id="port"'); ?>
                                    <span class="help-block">Most printers are open on port <strong>9100</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <?php echo form_submit('add_printer', $this->lang->line("add_printer"), 'class="btn btn-primary"'); ?>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    $(document).ready(function () {
        $('#type').change(function () {
            var type = $(this).val();
            if (type == 'web') {
                $('.network').slideDown();
                $('.path').slideUp();
            } else {
                $('.network').slideUp();
                $('.path').slideDown();
            }
        });
        var type = $('#type').val();
        if (type == 'network') {
            $('.network').slideDown();
            $('.path').slideUp();
        } else {
            $('.network').slideUp();
            $('.path').slideDown();
        }
    });
</script>
