<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
	<div class="row">
		<div class="col-xs-12">
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title"><?= lang('enter_info'); ?></h3>
				</div>
				<div class="box-body">
					<?php echo form_open("products/editprices");?>

					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label" for="code"><?= $this->lang->line("name"); ?></label>
                            <?= form_input('name', $prices->nombre_l_precio,  'class="form-control input-sm" id="name"'); ?>
                            <?= form_input('id_lista_precios', $prices->id_lista_precios,  'class="form-control input-sm" id="id_lista_precios" style="display:none"'); ?>
						</div>

						<div class="form-group">
                            <label class="control-label" for="status"><?= $this->lang->line("status"); ?>(Activado/Desactivado)</label>
                            <input type="checkbox" class="form-control input-lg" id="status" name="status">
						</div>
						<div class="form-group">
							<?php echo form_submit('add_prices', 'Agregar', 'class="btn btn-primary"');?>
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
        var checked = "<?php echo $prices->status_l_precio ?>";
        if(checked === '1')
        {
            $("#status").attr('checked', true);
        }else
        {
            $("#status").attr('checked', false);
        }
    });
</script>
