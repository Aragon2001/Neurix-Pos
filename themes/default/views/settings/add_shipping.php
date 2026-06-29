<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('update_info'); ?></h3>
                </div>
                <div class="box-body">
                    <?php echo form_open_multipart("settings/add_shipping");?>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="name"><?= $this->lang->line("name"); ?></label>
                            <?= form_input('name', set_value('name',''), 'class="form-control input-sm" id="name"'); ?>
                        </div>
                        <div class="mb-3">
                            <?php echo form_submit('add_shipping', $this->lang->line("add_shipping"), 'class="btn btn-primary"');?>
                        </div>

                    </div>
                    <?php echo form_close();?>
                </div>
            </div>
        </div>
    </div>
</section>
