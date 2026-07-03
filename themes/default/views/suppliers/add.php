<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
	<div class="row">
		<div class="col-12">
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title"><?= lang('enter_info'); ?></h3>
				</div>
				<div class="box-body">
					<?php echo form_open("suppliers/add");?>
						<div class="col-md-6">
								<div class="mb-3">
									<label for="mname" class="col-sm-4 form-label"><?= lang('cod_act_economica'); ?> *</label>
									<div class="col-sm-8">
											<!-- <input required type="text" class="form-control" id="txtCodActEco" name="txtCodActEco" /> -->
											<select required type="text" class="form-control" id="txtCodActEco" name="txtCodActEco" data-bs-toggle="tooltip" data-placement="left">
												
											</select>
									</div>
								</div>
								<div class="mb-3">
									<label for="tcedula" class="col-sm-4 form-label"><?= lang('identificacion'); ?> *</label>
									<div class="col-sm-8">
									<?php
								    // $pre_id_number = explode(',', $row['pre_id_number']);
									$pre_id_number_opt = array('01' => lang('cedula_identidad'), '02' => lang('cedula_juridica'), '03' => 'DIMEX', '04' => 'NITE', '05' => lang('pasaporte'));
									?>
									<select required name='tcedula' id="tcedula"  class='form-control' >
										<?php
										echo "<option  value ='' selected>" . lang('please_select') . "</option>";
										foreach ($pre_id_number_opt as $key => $val) {
											echo "<option  value =".$key.">".$val."</option>";
										}
										?>
									</select>
									</div>
								</div>
								<div class="mb-3">
									<label for="txtIdentificacion" class="col-sm-4 form-label"><?= lang('n_identificacion'); ?> *</label>
									<div class="col-sm-8">
											<input required  type="text" class="form-control" id="txtIdentificacion" name="txtIdentificacion"
											onkeyup="obtenerActividades(this.value , '#txtCodActEco','#txtNombre','#tcedula')" />
									</div>
								</div>
								<div class="mb-3">
									<label for="txtNombre" class="col-sm-4 form-label"><?= lang('name'); ?> *</label>
									<div class="col-sm-8">
											<input required type="text" class="form-control" id="txtNombre" name="txtNombre" />
									</div>
								</div>
								<div class="clearfix"></div>
								<hr/>
								<legend> <?= lang('direccion'); ?></legend>
								<div class="mb-3">
									<label for="codigo_provincia" class="col-sm-4 form-label"><?= lang('provincia'); ?></label>
									<div class="col-sm-8">
										<select required  id='codigo_provincia' name="codigo_provincia" class='form-control' onchange="obtenerCanton(this.value)">
										<?php
											echo '<option value="" selected>-- Please Select --</option>';
											foreach ($provincia as $pro) {
												echo '<option value='.$pro->codigo_provincia.'>'.$pro->nombre_provincia .'</option>';
											}
										?>
										</select>
									</div>
								</div>
								<div class="mb-3">
									<label for="codigo_canton" class="col-sm-4 form-label"><?= lang('canton'); ?></label>
									<div class="col-sm-8">
									<select required id='codigo_canton' name="codigo_canton"
									class='form-control' onchange="obtenerDistrito(this.value)"></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="codigo_distrito" class="col-sm-4 form-label"><?= lang('distrito'); ?></label>
									<div class="col-sm-8">
									<select required  id='codigo_distrito' name="codigo_distrito"
									class='form-control' onchange="obtenerBarrio(this.value)"></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="codigo_barrio" class="col-sm-4 form-label"><?= lang('barrio'); ?></label>
									<div class="col-sm-8">
									<select required id='codigo_barrio' name="codigo_barrio"
									class='form-control'></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="txtOtraSe" class="col-sm-4 form-label"><?= lang('otras_senas_label'); ?></label>
									<div class="col-sm-8">
											<input type="text" class="form-control" id="txtOtraSe" name="txtOtraSe" />
									</div>
								</div>
								<legend> <?= lang('datos_contacto'); ?></legend>
								<div class="mb-3  ">
									<label for="txtTel" class=" form-label col-md-4 text-left"> <?= lang('phone'); ?>  <span
									class="asterix"> * </span></label>
									<div class="col-md-6">
										<input required type="text" class="form-control" id="txtTel"  name="txtTel"/>
									</div>
									<div class="col-md-2">
									</div>
								</div>
								<div class="mb-3  ">
									<label for="txtEmail" class=" form-label col-md-4 text-left"> <?= lang('email_address'); ?> <span
									class="asterix"> * </span></label>
									<div class="col-md-6">
											<input type="text" class="form-control" id="txtEmail" name="txtEmail" />
									</div>
									<div class="col-md-2">
									</div>
								</div>
								<div class="mb-3  ">
									<div class="col-md-6">
										<?php echo form_submit('add_supplier', $this->lang->line("add_supplier"), 'class="btn btn-primary btn-sm"');?>
									</div>
									<div class="col-md-2">
									</div>
								<div>
							</div>
						</div>
					</div>
					<?php echo form_close();?>
				</div>
			</div>
		</div>
	</div>
	<script src="<?= $assets ?>dist/js/fec.min.js" type="text/javascript"></script>
</section>
