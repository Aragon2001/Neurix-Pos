<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
	<div class="row">
		<div class="col-xs-12">
			<div class="box box-primary">
				<div class="box-header">
					<h3 class="box-title"><?= lang('enter_info'); ?></h3>
				</div>
				<div class="box-body">
					<?php echo form_open("suppliers/add");?>
						<div class="col-md-6">
								<div class="form-group">
									<label for="mname" class="col-sm-4 control-label">Codigo Act. economica *</label>
									<div class="col-sm-8">
											<!-- <input required type="text" class="form-control" id="txtCodActEco" name="txtCodActEco" /> -->
											<select required type="text" class="form-control" id="txtCodActEco" name="txtCodActEco" data-toggle="tooltip" data-placement="left">
												
											</select>
									</div>
								</div>
								<div class="form-group">
									<label for="tcedula" class="col-sm-4 control-label">Identificacion *</label>
									<div class="col-sm-8">
									<?php
								    // $pre_id_number = explode(',', $row['pre_id_number']);
									$pre_id_number_opt = array('01' => 'Cedula de Identidad', '02' => 'Cedula Juridica', '03' => 'DIMEX', '04' => 'NITE', '05' => 'Passaporte');
									?>
									<select required name='tcedula' id="tcedula"  class='form-control' >
										<?php
										echo "<option  value ='' selected>-- Please Select --</option>";
										foreach ($pre_id_number_opt as $key => $val) {
											echo "<option  value =".$key.">".$val."</option>";
										}
										?>
									</select>
									</div>
								</div>
								<div class="form-group">
									<label for="txtIdentificacion" class="col-sm-4 control-label">N° identificacion *</label>
									<div class="col-sm-8">
											<input required  type="text" class="form-control" id="txtIdentificacion" name="txtIdentificacion"
											onkeyup="obtenerActividades(this.value , '#txtCodActEco','#txtNombre','#tcedula')" />
									</div>
								</div>
								<div class="form-group">
									<label for="txtNombre" class="col-sm-4 control-label">Nombre *</label>
									<div class="col-sm-8">
											<input required type="text" class="form-control" id="txtNombre" name="txtNombre" />
									</div>
								</div>
								<div class="clearfix"></div>
								<hr/>
								<legend> Direccion</legend>
								<div class="form-group">
									<label for="codigo_provincia" class="col-sm-4 control-label">Provincia</label>
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
								<div class="form-group">
									<label for="codigo_canton" class="col-sm-4 control-label">Canton</label>
									<div class="col-sm-8">
									<select required id='codigo_canton' name="codigo_canton"
									class='form-control' onchange="obtenerDistrito(this.value)"></select>
									</div>
								</div>
								<div class="form-group">
									<label for="codigo_distrito" class="col-sm-4 control-label">Distrito</label>
									<div class="col-sm-8">
									<select required  id='codigo_distrito' name="codigo_distrito"
									class='form-control' onchange="obtenerBarrio(this.value)"></select>
									</div>
								</div>
								<div class="form-group">
									<label for="codigo_barrio" class="col-sm-4 control-label">Barrio</label>
									<div class="col-sm-8">
									<select required id='codigo_barrio' name="codigo_barrio"
									class='form-control'></select>
									</div>
								</div>
								<div class="form-group">
									<label for="txtOtraSe" class="col-sm-4 control-label">Otras se&ntilde;as</label>
									<div class="col-sm-8">
											<input type="text" class="form-control" id="txtOtraSe" name="txtOtraSe" />
									</div>
								</div>
								<legend> Datos de contacto</legend>
								<div class="form-group  ">
									<label for="txtTel" class=" control-label col-md-4 text-left"> Telefono  <span
									class="asterix"> * </span></label>
									<div class="col-md-6">
										<input required type="text" class="form-control" id="txtTel"  name="txtTel"/>
									</div>
									<div class="col-md-2">
									</div>
								</div>
								<div class="form-group  ">
									<label for="txtEmail" class=" control-label col-md-4 text-left"> Email <span
									class="asterix"> * </span></label>
									<div class="col-md-6">
											<input type="text" class="form-control" id="txtEmail" name="txtEmail" />
									</div>
									<div class="col-md-2">
									</div>
								</div>
								<div class="form-group  ">
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
