<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
	<div class="row">
		<div class="col-12">
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title"><?= lang('enter_info'); ?></h3>
				</div>
				<div class="box-body">
					<?php echo form_open("settings/edit_table");?>

					<div class="col-md-6">
						<div class="mb-3">
							<label class="form-label" for="code"><?= $this->lang->line("name"); ?></label>
                            <?= form_input('name', $table->name,  'class="form-control input-sm" id="name"'); ?>
                            <?= form_input('id_waiting_tables', $table->id_waiting_tables,  'class="form-control input-sm" id="id_waiting_tables" style="display:none"'); ?>
						</div>

						<div class="mb-3">
                            <label class="form-label" for="status"><?= $this->lang->line("status"); ?></label>
                            <input type="checkbox" class="form-control input-lg" id="status" name="status">
						</div>
						<div class="mb-3">
							<?php echo form_submit('add_prices', 'Editar', 'class="btn btn-primary"');?>
						</div>
					</div>
					<?php echo form_close();?>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
    $(document).ready(function(){
        var checked = "<?php echo $table->status ?>";
        if(checked === '1')
        {
            $("#status").attr('checked', true);
        }else
        {
            $("#status").attr('checked', false);
        }
    });
</script>
