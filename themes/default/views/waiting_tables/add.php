<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
	<div class="row">
		<div class="col-12">
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title"><?= lang('enter_info'); ?></h3>
				</div>
				<div class="box-body">
					<?php echo form_open("settings/add_table");?>

					<div class="col-md-6">
						<div class="mb-3">
							<label class="form-label" for="code"><?= $this->lang->line("name"); ?></label>
							<?= form_input('name', set_value('name'), 'class="form-control input-sm" id="name"'); ?>
						</div>

						<div class="mb-3">
                            <label class="form-label" for="status"><?= $this->lang->line("status"); ?></label>
                            <input type="checkbox" class="form-control input-lg" id="status" name="status">
						</div>
						<div class="mb-3">
							<?php echo form_submit('add_prices', 'Agregar', 'class="btn btn-primary"');?>
						</div>
					</div>
					<?php echo form_close();?>
				</div>
			</div>
		</div>
	</div>
</section>
q