<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<style>
		.ui-widget-content {
			border: 1px solid var(--nx-border);
			background: var(--nx-card-bg);
			color: var(--nx-txt1);
		}
		.ui-menu .ui-menu-item {
			position: relative;
			margin: 0;
			padding: 3px 1em 3px .4em;
			cursor: pointer;
			min-height: 0;
			list-style-image: url(data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7);
		}
		.ui-state-hover, .ui-widget-content .ui-state-hover, .ui-widget-header .ui-state-hover,
		.ui-state-focus, .ui-widget-content .ui-state-focus, .ui-widget-header .ui-state-focus {
			border: 1px solid var(--nx-a1);
			background: rgba(56,189,248,.15);
			font-weight: bold;
			color: var(--nx-a1);
		}
		span.ui-helper-hidden-accessible { display: none; }
		.table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th,
		.table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
			border-top: 1px solid var(--nx-border);
			line-height: 1.42857;
			padding: 3px 8px;
			font-size: 15px;
			vertical-align: middle;
		}
		.ts-control { width: 100%; }
	</style>
<section class="content">
    <div class="row">

        <div class="col-12">
            <div class="box box-primary">
			<div class="col-md-6">
				<legend><?= lang('proveedor_simplificado'); ?></legend>
				<div class="mb-3  ">
					<label for="Cliente" class=" form-label col-md-4 text-left"><?= lang('supplier'); ?> <span
					class="asterix"> * </span></label>
					<div class="col-md-5">
						<select name='userid' rows='5' id='userid' class='form-control' required>
						<?php
							echo "<option  value ='' selected>". lang('Seleccione') ."</option>";
							foreach ($suppliers as $sup) {
								echo '<option value='.$sup->id.'>'.$sup->name .'</option>';
							}
						?>
						</select>
					</div>
					<div class="col-md-1">
						<a class="btn btn-xs btn-info" data-bs-toggle="modal"
						data-target="#modalAgregarCliente">
						<i class="fa fa-plus"></i>
						</a>
					</div>
					<div class="col-md-2">

					</div>
				</div>
				<input name='id_origen' type="hidden" value="0"/>
			</div>
			<!-- <div class="col-md-4">
				<legend> Datos de la Factura</legend>
				<div class="mb-3  ">
					<label for="id_moneda" class=" form-label col-md-4 text-left"> Moneda <span
					class="asterix"> * </span></label>
					<div class="col-md-6">
						<select name='id_moneda' rows='5' id='id_moneda' class='form-control ' required>
							<option value=""></option>
							<option value="COL">Colones</option>
							<option value="USD">Dólares</option>
						</select>
					</div>
					<div class="col-md-2">
					</div>
				</div> -->
				<div class="mb-3 hidden " hidden style="display: none;">
					<label for="Fecha" class=" form-label col-md-4 text-left"> Fecha <span
					class="asterix"> * </span></label>
					<div class="col-md-6">
						<div class="input-group m-b hidden" hidden style="width:150px !important;">
							<input hidden value="{{date('Y-m-d')}}" type="hidden" name="date"
							class="form-control date"/>
							<input hidden value="{{date('Y-m-d')}}" name="date" type="hidden"
							class="form-control date"/>
							<span class="input-group-text"><i class="fa fa-calendar"></i></span>
						</div>
					</div>
					<div class="col-md-2">
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<legend><?= lang('exoneracion_factura'); ?></legend>
				<div class="mb-3  ">
					<div class="col-md-6 add_exo">
						<span class="btn btn-success add_exo"><?= lang('agregar_exoneracion'); ?></span>
					</div>
					<div class="col-md-6 hide_exo" style="display: none;">
						<span class="btn btn-danger hide_exo" onclick="quitarValidaciones('#divexoneracion')"><?= lang('ocultar_formulario'); ?></span>
					</div>
				</div>
					<div id="divexoneracion" style="display: none;">
						<div class="row"></div>
						<div class="mb-3">
							<label for="exo_t_doc"><?= lang('tipo_doc_referencia'); ?></label>

								<select name='ExoTipoDocumento' id='exo_t_doc' class='form-control ' required>
									<option value=''></option>
									<option value='01'>Compras Autorizadas</option>
									<option value='02'>Ventas Exentas a Diplomaticos</option>
									<option value='03'>Orden de compra (Instituciones públicas y otros organismos)
									</option>
									<option value='04'>Exenciones Dirección General de Hacienda</option>
									<option value='05'>Transitorio V</option>
									<option value='06'>Transitorio IX</option>
									<option value='07'>Transitorio XVII</option>
									<option value='99'><?= lang('otros'); ?></option>
								</select>
							
						</div>

						<div class="mb-3">
							<label for="exo_numero_documento" ><?= lang('exo_numero_doc'); ?></label>
							<input name="ExoNumeroDocumento" id="exo_numero_documento" class="form-control" required/>
						</div>

						<div class="mb-3">
							<label for="exo_nombre_institucion" ><?= lang('exo_nombre_inst'); ?></label>
							<input name="ExoNombreInstitucion" id="exo_nombre_institucion" class="form-control" required/>
						</div>

						<div class="mb-3">
							<label for="exo_fecha_emision" ><?= lang('exo_fecha_emision'); ?></label>
							<input name="ExoFechaEmision" placeholder="<?= lang('placeholder_fecha_emision'); ?>" id="exo_fecha_emision" class="form-control" required/>
						</div>

						<div class="mb-3">
							<label for="exo_porcentaje" ><?= lang('exo_porcentaje'); ?></label>
							<input type="text"  style="text-align: right;"name="ExoPorcentajeExoneracion" id="exo_porcentaje" class="form-control" required/>
						</div>

						<div class="mb-3">
							<label for="aplicaExo"></label>
							<span  id="aplicaExo" class="btn btn-warning text-center" ><?= lang('apply_exoneracion'); ?></span>
						</div>
					</div>
			</div>
			<hr/>
			<div class="clr clear"></div>


			<div class="col-md-12" id="sticker">
				<hr/>
				<a href="#" id="addManually" class="tip btn btn-success" title=""
				   data-original-title="<?= lang('agregar_producto_manual'); ?>" tabindex="-1">
					<i class="fa fa-2x fa-plus-circle addIcon" id="addIcon"></i>
					<?= lang('agregar_articulo_comprado'); ?>
				</a>
				<div class="clearfix"></div>
				<hr/>
			</div>
			<div class="clearfix"></div>
			<div class="col-md-12">
					<div class="control-group table-group">
						<label class="table-label"><?= lang('items_factura'); ?>*</label>

						<div class="controls table-controls">
							<table id="slTable"
								   class="table items table-striped table-bordered table-condensed table-hover sortable_table">
								<thead>
								<tr>
									<th class="col-md-4"><?= lang('product'); ?> (<?= lang('code'); ?> - <?= lang('name'); ?>)</th>
									<th class="col-md-2"><?= lang('serial'); ?> Nº</th>
									<th class="col-md-1"><?= lang('price'); ?></th>
									<th class="col-md-1"><?= lang('qty'); ?>.</th>
									<th class="col-md-1"><?= lang('monto_total'); ?></th>
									<th class="col-md-1"><?= lang('discount'); ?></th>
									<th class="col-md-1"><?= lang('subtotal'); ?></th>
									<th class="col-md-1"><?= lang('tax'); ?>.</th>
									<th class="col-md-1"><?= lang('exoneracion'); ?></th>
									<th class="col-md-1"><?= lang('tax'); ?>.Neto</th>
									<th class="col-md-1"><?= lang('total'); ?></th>
									</th>
									<th style="width: 30px !important; text-align: center;">
										<i class="fa fa-trash-o" style="opacity:0.5; filter:alpha(opacity=50);"></i>
									</th>
								</tr>
								</thead>
								<tbody></tbody>
								<tfoot></tfoot>
							</table>
                </div>
						</div>
					</div>
				</div>
				<div class="col-md-12">
						<div class="table-responsive">
								<table class="table table-bordered table-condensed" style="width: 100%">
									<tr>
										<td rowspan="14" style="padding: 0; margin: 0; border: 1px solid var(--nx-border);">
											<div class="col-md-12">
												<h2><?= lang('formulario_pago'); ?></h2>
											</div>
											<div class="col-md-12">

												<div class="col-sm-6">
													<div class="mb-3">
														<?= lang('estado_pago'); ?>
														<select name="payment_status" class=" input-tip" required="required"
																id="slpayment_status">
															<option value="due" disabled><?= lang('a_credito'); ?></option>
															<option value="partial" disabled><?= lang('partial'); ?></option>
															<option value="paid" selected><?= lang('paid'); ?></option>
														</select>
		
													</div>
												</div>
												<input type="hidden" name="token_post" id="token_post" value="<?= md5(date('Y-m-d H:i:s')) ?>"/>
		
												<div class="col-sm-6" id="credit_time">
													<div class="mb-3">
														<?= lang('tiempo_credito'); ?>
														<?php
														// $paymentmethod = explode(',', $row['paymentmethod']);
														$paymentmethod_opt = array(
																'0' => 'Seleccione...',
																'4' => 'Credito 4 dias',
																'8' => 'Credito 8 dias',
																'15' => 'Credito 15 dias',
																'30' => 'Credito 1 mes',
																'45' => 'Credito 1 mes y medio',
																'60' => 'Credito 2 meses',
																'75' => 'Credito 2 meses y medio',
																'90' => 'Credito 3 meses',
																'120' => 'Credito 4 meses',
																'150' => 'Credito 5 meses',
																'180' => 'Credito 6 meses',
																'210' => 'Credito 7 meses',
																'240' => 'Credito 8 meses',
																'270' => 'credito 9 meses',
																'300' => 'credito 10 meses',
																'330' => 'Credito 11 meses',
																'360' => 'Credito 1 año',);
														?>
														<select name='paymentmethod' id="paymentmethod" disabled>
															<?php
															foreach ($paymentmethod_opt as $key => $val) {
																echo "<option  value ='$key'>$val</option>";
															}
															?></select>
		
													</div>
												</div>
		
												<div id="payments" class="col-sm-6" style="display: none;">
		
													<div class="mb-3">
														<?= lang('pagar_por'); ?>
														<select required name="paid_by_1" id="paid_by_1" class="paid_by">
															<option value="cash"><?= lang('cash'); ?></option>
															<option value="CC"><?= lang('tarjeta_cd'); ?>
															</option>
															<option value="Cheque"><?= lang('cheque'); ?></option>
															<option value="deposit"><?= lang('deposito'); ?></option>
														</select>
		
													</div>
		
												</div>
											</div>
										</td>
										<td align="right"><?= lang('total_serv_gravados'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalServGravados">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_serv_exentos'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalServExentos">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_serv_exonerado'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalServExonerado">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_merc_gravadas'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalMercanciasGravadas">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_merc_exentas'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalMercanciasExentas">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_merc_exonerada'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalMercExonerada">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_gravado'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalGravado">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_exento'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalExento">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_exonerado'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalExonerado">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_venta'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalVenta">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_descuentos'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalDescuentos">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_venta_neta'); ?></td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalVentaNeta">0.00
										</td>
									</tr>
									<tr>
										<td align="right"><?= lang('total_impuesto_fec'); ?></td>
										<td style="width: 18%;padding: 0px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalImpuesto">0.00
										</td>
									</tr>
									<tr>
										<td style=" font-size: 14px; font-weight: bold;" align="right"><?= lang('total_comprobante'); ?></td>
										<td style="width: 18%;padding: 0px 4% 0; font-size: 14px; font-weight: bold;"
											align="right" id="TotalComprobante">0.00
										</td>
									</tr>
		
								</table>
                </div>
								<input name="TotalServGravados" id="inp_TotalServGravados" type="hidden"/>
								<input name="TotalServExentos" id="inp_TotalServExentos" type="hidden"/>
								<input name="TotalServExonerado" id="inp_TotalServExonerado" type="hidden"/>
								<input name="TotalMercanciasGravadas" id="inp_TotalMercanciasGravadas" type="hidden"/>
								<input name="TotalMercanciasExentas" id="inp_TotalMercanciasExentas" type="hidden"/>
								<input name="TotalMercExonerada" id="inp_TotalMercExonerada" type="hidden"/>
								<input name="TotalGravado" id="inp_TotalGravado" type="hidden"/>
								<input name="TotalExento" id="inp_TotalExento" type="hidden"/>
								<input name="TotalExonerado" id="inp_TotalExonerado" type="hidden"/>
								<input name="TotalVenta" id="inp_TotalVenta" type="hidden"/>
								<input name="TotalDescuentos" id="inp_TotalDescuentos" type="hidden"/>
								<input name="TotalVentaNeta" id="inp_TotalVentaNeta" type="hidden"/>
								<input name="TotalImpuesto" id="inp_TotalImpuesto" type="hidden"/>
								<input name="TotalComprobante" id="inp_TotalComprobante" type="hidden"/>
		
								<div class="mb-3">
								<label class="col-sm-4 text-right">&nbsp;</label>
								<div class="col-sm-8">

									<button type="button" onclick="saveFec();"
											class="btn btn-info btn-sm "><i
												class="icon-bubble-check "></i> <?= lang('save') ?> </button>
									<button type="button" onclick="cancelar();"
											class="btn btn-warning btn-sm "><i
												class="icon-cancel-circle2 "></i> <?= lang('cancel') ?></button>
								</div>

							</div>
						</div>
				</div>
		</div>    
   
	</div>
		<!-- Modal agregar cliente -->
	<div class="modal fade" id="modalAgregarCliente">
			<?php echo form_open("suppliers/add");?>
		<div class="modal-dialog" role="document" style="background-color: white">
				<div class="modal-header">
					<h3 class="modal-title"><?= lang('agregar_proveedor_simplificado'); ?></h3>
				</div>
			<div class="modal-content">
			<!-- <form> -->
					<div class="col-md-12"> 
								<div class="mb-3">
									<label for="mname" class="col-sm-4 form-label"><?= lang('cod_act_economica'); ?> *</label>
									<div class="col-sm-8">
											<!-- <input required type="text" class="form-control" name="txtCodActEco" id="txtCodActEco" /> -->
											<select required type="text" class="form-control" id="txtCodActEco" name="txtCodActEco" data-bs-toggle="tooltip" data-placement="left">
												
											</select>
											<input  type="hidden" class="form-control" name="formFC" id="formFC" value="FEC" />
									</div>
								</div>
								<div class="mb-3">
									<label for="tcedula" class="col-sm-4 form-label"><?= lang('identificacion'); ?> *</label>
									<div class="col-sm-8">
									<?php
								    // $pre_id_number = explode(',', $row['pre_id_number']);
									$pre_id_number_opt = array('01' => lang('Cedula Identidad'), '02' => lang('Cedula Juridica'), '03' => lang('Dimex'), '04' => lang('NITE'), '05' => lang('passaporte'));
									?>
									<select required name='tcedula' id="tcedula" rows='5' class='form-control ' >
										<?php
										echo "<option  value ='' selected>". lang('Seleccione') ."</option>";
										foreach ($pre_id_number_opt as $key => $val) {
											echo "<option  value =".$key.">".$val."</option>";
										}
										?>
									</select>
									</div>
								</div>
								<div class="mb-3">
									<label for="mname" class="col-sm-4 form-label"><?= lang('n_identificacion'); ?> *</label>
									<div class="col-sm-8">
											<input required  onkeyup="obtenerActividades(this.value , '#txtCodActEco','#txtNombre','#tcedula')"  type="text" class="form-control" id="txtIdentificacion" name="txtIdentificacion"/>
									</div>
								</div>
								<div class="mb-3">
									<label for="mname" class="col-sm-4 form-label"><?= lang('name'); ?> *</label>
									<div class="col-sm-8">
											<input required type="text" class="form-control" id="txtNombre" name="txtNombre" />
									</div>
								</div>
								<div class="clearfix"></div>
								<hr/>
								<legend><?= lang('direccion'); ?></legend>
								<div class="mb-3">
									<label for="tipo_persona" class="col-sm-4 form-label"><?= lang('provincia'); ?></label>
									<div class="col-sm-8">
										<select required name='codigo_provincia' id='codigo_provincia' class='form-control' onchange="obtenerCanton(this.value)">
										<?php
											echo '<option value="" selected>'. lang('Seleccione') .'</option>';
											foreach ($provincia as $pro) {
												echo '<option value='.$pro->codigo_provincia.'>'.$pro->nombre_provincia .'</option>';
											}
										?>
										</select>
									</div>
								</div>
								<div class="mb-3">
									<label for="Canton" class="col-sm-4 form-label"><?= lang('canton'); ?></label>
									<div class="col-sm-8">
									<select required name='codigo_canton' id='codigo_canton'
									class='form-control' onchange="obtenerDistrito(this.value)"></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="Distrito" class="col-sm-4 form-label"><?= lang('distrito'); ?></label>
									<div class="col-sm-8">
									<select required name='codigo_distrito' id='codigo_distrito'
									class='form-control' onchange="obtenerBarrio(this.value)"></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="Barrio" class="col-sm-4 form-label"><?= lang('barrio'); ?></label>
									<div class="col-sm-8">
									<select required name='codigo_barrio' id='codigo_barrio'
									class='form-control'></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="Barrio" class="col-sm-4 form-label"><?= lang('otras_senas'); ?></label>
									<div class="col-sm-8">
											<input required type="text" class="form-control" id="txtOtraSe" name="txtOtraSe" />
									</div>
								</div>
								<legend><?= lang('datos_contacto'); ?></legend>
								<div class="mb-3  ">
									<label for="Telefonos" class=" form-label col-md-4 text-left"><?= lang('phone'); ?> <span class="asterix"> * </span></label>
									<div class="col-md-6">
										<input required type="text" class="form-control" id="txtTel" name="txtTel" />
									</div>
									<div class="col-md-2">
									</div>
								</div>
								<div class="mb-3  ">
									<label for="Email" class=" form-label col-md-4 text-left"><?= lang('email'); ?> <span class="asterix"> * </span></label>
									<div class="col-md-6">
											<input type="text" class="form-control" id="txtEmail" name="txtEmail" />
									</div>
									<div class="col-md-2">
									</div>
								</div>
							</div>
					
			</div>
			<div class="modal-footer">
					<label class="text-right">&nbsp;</label>
					<div class="text-center">
						<?php echo form_submit('add_supplier', $this->lang->line("add_supplier"), 'class="btn btn-primary btn-sm"');?>
						<button type="button" data-bs-dismiss="modal"
						class="btn btn-warning btn-sm "><i
						class="icon-cancel-circle2 "></i><?= lang('cancel'); ?> </button>
					</div>
			
			</div>
		</div>
		<?php echo form_close();?>
	</div>

	<div class="modal" id="prModal" tabindex="-1" role="dialog" aria-labelledby="prModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-bs-dismiss="modal"><span aria-hidden="true"><i
										class="fa fa-2x">&times;</i></span><span class="sr-only"><?= lang('cancel'); ?></span></button>
						<h4 class="modal-title" id="prModalLabel"></h4>
					</div>
					<div class="modal-body needs-validation" id="pr_popover_content" novalidate>
						<form class="form-horizontal" role="form">
	
							<div class="mb-3">
								<label class="col-sm-4 form-label"><?= lang('tax'); ?></label>
								<div class="col-sm-8">
	
									<select style="padding: 0;" name="ptax" id="ptax" class="form-control"
											tabindex="-1"
											title="" data-original-title="<?= lang('impuesto_producto'); ?> *" required>
										<option value="" selected="selected"></option>
										<?php
											foreach($impuesto as $imp){
												echo '<option value='.$imp->id_impuesto.'>'.$imp->descripcion_impuesto .'</option>';
											}
										?>
									</select>
								</div>
							</div>
	
	
							<div class="mb-3">
								<label for="pserial" class="col-sm-4 form-label"><?= lang('serial'); ?></label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="pserial" >
								</div>
							</div>
	
							<div class="mb-3">
								<label for="pquantity" class="col-sm-4 form-label"><?= lang('qty'); ?></label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="pquantity" required>
								</div>
							</div>
							<div class="mb-3">
								<label for="punit" class="col-sm-4 form-label"><?= lang('unidad'); ?></label>
								<div class="col-sm-8">
									<select style="padding: 0;" name="punit" id="punit"
											class="col-md-12  form-control input-tip select" tabindex="-1"
											title="" data-original-title="<?= lang('unidad'); ?> *" required>
										<option value=""></option>
										<option value="Sp"><?= lang('servicios_profesionales'); ?></option>
										<option value="m"><?= lang('metro'); ?></option>
										<option value="kg"><?= lang('kilogramo'); ?></option>
										<option value="m²"><?= lang('metro_cuadrado'); ?></option>
										<option value="m³"><?= lang('metro_cubico'); ?></option>
										<option value="´"><?= lang('minuto'); ?></option>
										<option value="h"><?= lang('hora'); ?></option>
										<option value="d"><?= lang('dia'); ?></option>
										<option value="L"><?= lang('litro'); ?></option>
										<option value="t"><?= lang('tonelada'); ?></option>
										<option value="Unid"><?= lang('unidad'); ?></option>
										<option value="Gal"><?= lang('galon'); ?></option>
									</select>
								</div>
							</div>
	
							<div class="mb-3">
								<label for="pdiscount" class="col-sm-4 form-label"><?= lang('discount'); ?></label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="pdiscount">
								</div>
							</div>
	
							<div class="mb-3">
								<label for="pprice" class="col-sm-4 form-label"><?= lang('price'); ?></label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="pprice" required>
								</div>
							</div>
                <div class="table-responsive">
							<table class="table table-bordered table-striped">
								<tr>
									<th style="width:25%;"><?= lang('precio_neto'); ?></th>
									<th style="width:25%;"><span id="net_price"></span></th>
									<th style="width:25%;"><?= lang('impuesto_producto'); ?></th>
									<th style="width:25%;"><span id="pro_tax"></span></th>
	
								</tr>
							</table>
                </div>
							<input type="hidden" id="punit_price" value=""/>
							<input type="hidden" id="old_tax" value=""/>
							<input type="hidden" id="old_qty" value=""/>
							<input type="hidden" id="old_price" value=""/>
							<input type="hidden" id="row_id" value=""/>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" id="editItem"><?= lang('edit'); ?></button>
					</div>
				</div>
			</div>
		</div>
	
		<div class="modal" id="mModal" tabindex="-1" role="dialog" aria-labelledby="mModalLabel" aria-hidden="true"
			 style="display: none;">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-bs-dismiss="modal" tabindex="-1"><span aria-hidden="true"><i
										class="fa fa-2x">×</i></span><span class="sr-only"><?= lang('cancel'); ?></span></button>
						<h4 class="modal-title" id="mModalLabel"><?= lang('agregar_producto_manual'); ?></h4>
					</div>
					<div class="modal-body needs-validation" id="pr_popover_content2" novalidate>
						<form class="form-horizontal" role="form">
							<div class="mb-3">
								<label for="mitem_type" class="col-sm-4 form-label"><?= lang('tipo_producto'); ?></label>
	
								<div class="col-sm-8">
	
									<select required="required" style="padding: 0;" name="mitem_type" id="mitem_type"
											class="col-md-12  form-control input-tip select" tabindex="-1"
											title="" data-original-title="<?= lang('tipo_items'); ?>" required>
										<option value="standard"><?= lang('mercancia'); ?></option>
										<option value="service"><?= lang('service'); ?></option>
									</select>
								</div>
							</div>
							<div class="mb-3">
								<label for="mcode" class="col-sm-4 form-label"><?= lang('product_code'); ?> *</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mcode" value="" required="required">
								</div>
							</div>
							<div class="mb-3">
								<label for="mname" class="col-sm-4 form-label"><?= lang('name'); ?> *</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mname" value="" required="required">
								</div>
							</div>
							<div class="mb-3">
								<label for="mtax" class="col-sm-4 form-label"><?= lang('tipo_impuesto'); ?> *</label>
	
								<div class="col-sm-8">
	
									<select style="padding: 0;" name="mtax" id="mtax" class="form-control"
											tabindex="-1"
											title="" data-original-title="<?= lang('impuesto_producto'); ?> *">
										<option value="" selected="selected"></option>
										<?php
											foreach($impuesto as $imp){
												echo '<option value='.$imp->id_impuesto.'>'.$imp->descripcion_impuesto .'</option>';
											}
										?>
									</select>
								</div>
							</div>
	
	
							<div class="mb-3">
								<label for="mquantity" class="col-sm-4 form-label"><?= lang('qty'); ?> *</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mquantity" value="1" required="required">
								</div>
							</div>
							<div class="mb-3">
								<label for="munit" class="col-sm-4 form-label"><?= lang('unidad'); ?> *</label>
	
								<div class="col-sm-8">
	
									<select style="padding: 0;" name="munit" id="munit" class="form-control" tabindex="-1"
											title="" data-original-title="<?= lang('unidad'); ?> *" required="required">
										<option value=""></option>
										<option value="Sp"><?= lang('servicios_profesionales'); ?></option>
										<option value="m"><?= lang('metro'); ?></option>
										<option value="kg"><?= lang('kilogramo'); ?></option>
										<option value="m²"><?= lang('metro_cuadrado'); ?></option>
										<option value="m³"><?= lang('metro_cubico'); ?></option>
										<option value="´"><?= lang('minuto'); ?></option>
										<option value="h"><?= lang('hora'); ?></option>
										<option value="d"><?= lang('dia'); ?></option>
										<option value="L"><?= lang('litro'); ?></option>
										<option value="t"><?= lang('tonelada'); ?></option>
										<option value="Unid"><?= lang('unidad'); ?></option>
										<option value="Gal"><?= lang('galon'); ?></option>
									</select>
								</div>
							</div>
							<div class="mb-3">
								<label for="mdiscount" class="col-sm-4 form-label"><?= lang('discount'); ?></label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mdiscount" >
								</div>
							</div>
							<div class="mb-3">
								<label for="mprice" class="col-sm-4 form-label"><?= lang('unit_price'); ?> *</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mprice" value="" required>
								</div>
							</div>
                <div class="table-responsive">
							<table class="table table-bordered table-striped">
								<tbody>
								<tr>
									<th style="width:25%;"><?= lang('precio_unitario_neto'); ?></th>
									<th style="width:25%;"><span id="mnet_price">0.00</span></th>
									<th style="width:25%;"><?= lang('impuesto_producto'); ?></th>
									<th style="width:25%;"><span id="mpro_tax">0.00</span></th>
								</tr>
								</tbody>
							</table>
                </div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" id="addItemManually" tabindex="-1"><?= lang('submit'); ?></button>
					</div>
				</div>
			</div>
		</div>
</section>
<script>
$(document).ready(function(){
	localStorage.setItem("tax_rates_fec", '<?= json_encode((array)$impuesto) ?>');
});
</script>
<script src="<?= $assets ?>dist/js/fec.min.js?v=<?= rand(); ?>" type="text/javascript"></script>
