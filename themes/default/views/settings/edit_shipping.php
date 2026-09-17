<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxf-page">
    <div class="row">
        <div class="col-12">
            <div class="nxf-card">
                <div class="nxf-card-head">
                    <div class="nxf-card-title"><?= lang('update_info'); ?></div>
                </div>
                <div class="nxf-card-body">
                    <?php echo form_open_multipart("settings/edit_shipping/".$shipping->id_shipping_method);?>
                    <div class="col-md-6">


                        <div class="mb-3">
                            <label class="form-label" for="name"><?= $this->lang->line("name"); ?></label>
                            <?= form_input('name', set_value('name', $shipping->name), 'class="form-control input-sm" id="name"'); ?>
                        </div>
                        <div class="mb-3">
                            <?php echo form_submit('edit_shipping', $this->lang->line("edit_shipping"), 'class="nxf-btn"');?>
                        </div>

                    </div>
                    <?php echo form_close();?>
                </div>
            </div>
        </div>
    </div>
</div>
