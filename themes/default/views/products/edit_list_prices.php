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
					<div class="nxf-card-title"><?= lang('enter_info'); ?></div>
				</div>
				<div class="nxf-card-body">
					<?php echo form_open("products/editprices");?>

					<div class="col-md-6">
						<div class="mb-3">
							<label class="form-label" for="code"><?= $this->lang->line("name"); ?></label>
                            <?= form_input('name', $prices->nombre_l_precio,  'class="form-control input-sm" id="name"'); ?>
                            <?= form_input('id_lista_precios', $prices->id_lista_precios,  'class="form-control input-sm" id="id_lista_precios" style="display:none"'); ?>
						</div>

						<div class="mb-3">
                            <label class="form-label" for="status"><?= $this->lang->line("status"); ?>(Activado/Desactivado)</label>
                            <input type="checkbox" class="form-check-input" id="status" name="status" value="1"
                                   <?= ($prices->status_l_precio == 1) ? 'checked' : ''; ?>>
						</div>
						<div class="mb-3">
							<?php echo form_submit('add_prices', 'Agregar', 'class="nxf-btn"');?>
						</div>
					</div>
					<?php echo form_close();?>
				</div>
			</div>
		</div>
	</div>
</div>
