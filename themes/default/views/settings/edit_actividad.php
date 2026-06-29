<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('update_info'); ?></h3>
                </div>
                <div class="box-body">
                    <?php echo form_open_multipart("settings/edit_actividad/".$actividad->id_actividad);?>
                    <div class="col-md-6">
                        <div class="mb-3" style="display:block;">
                            <label class="form-label" for="id_actividad"><?= $this->lang->line("code_actividad"); ?></label>
                            <?= form_input('id_actividad', set_value('id_actividad', $actividad->id_actividad), 'class="form-control input-sm" id="id_actividad"'); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="descripcion"><?= $this->lang->line("description"); ?></label>
                            <?= form_input('descripcion', set_value('descripcion', $actividad->descripcion), 'class="form-control input-sm" id="descripcion"'); ?>
                        </div>
                        <div class="mb-3">
                            <?php echo form_submit('edit_actividad', $this->lang->line("edit_actividad"), 'class="btn btn-primary"');?>
                        </div>

                    </div>
                    <?php echo form_close();?>
                </div>
            </div>
        </div>
    </div>
</section>
