<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('update_info'); ?></h3>
                </div>
                <div class="box-body">
                    <?php echo form_open_multipart("settings/edit_shipping/".$shipping->id_shipping_method);?>
                    <div class="col-md-6">


                        <div class="form-group">
                            <label class="control-label" for="name"><?= $this->lang->line("name"); ?></label>
                            <?= form_input('name', set_value('name', $shipping->name), 'class="form-control input-sm" id="name"'); ?>
                        </div>
                        <div class="form-group">
                            <?php echo form_submit('edit_shipping', $this->lang->line("edit_shipping"), 'class="btn btn-primary"');?>
                        </div>

                    </div>
                    <?php echo form_close();?>
                </div>
            </div>
        </div>
    </div>
</section>
