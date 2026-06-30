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
				<legend> Proveedor con regimen Simplificado</legend>
				<div class="mb-3  ">
					<label for="Cliente" class=" form-label col-md-4 text-left"> Proveedor <span
					class="asterix"> * </span></label>
					<div class="col-md-5">
						<select name='userid' rows='5' id='userid' class='form-control' required>
						<?php
							echo "<option  value ='' selected>-- Please Select --</option>";
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
				<legend> Exoneracion de la Factura</legend>
				<div class="mb-3  ">
					<div class="col-md-6 add_exo">
						<span class="btn btn-success add_exo">Agregar Exoneracion</span>
					</div>
					<div class="col-md-6 hide_exo" style="display: none;">
						<span class="btn btn-danger hide_exo" onclick="quitarValidaciones('#divexoneracion')">Ocultar formulario</span>
					</div>
				</div>
					<div id="divexoneracion" style="display: none;">
						<div class="row"></div>
						<div class="mb-3">
							<label for="exo_t_doc" >Tipo de Documento</label>

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
									<option value='99'>Otros</option>
								</select>
							
						</div>

						<div class="mb-3">
							<label for="exo_numero_documento" >Número de documento de exoneración o autorización </label>
							<input name="ExoNumeroDocumento" id="exo_numero_documento" class="form-control" required/>
						</div>

						<div class="mb-3">
							<label for="exo_nombre_institucion" > Nombre de la institución o dependencia que emitió la exoneración </label>
							<input name="ExoNombreInstitucion" id="exo_nombre_institucion" class="form-control" required/>
						</div>

						<div class="mb-3">
							<label for="exo_fecha_emision" > Fecha y hora de la emisión del documento de exoneración o autorización. (Formato: 2019-07-31 13:53:00)</label>
							<input name="ExoFechaEmision" placeholder="Formato: 2019-07-31 13:53:00" id="exo_fecha_emision" class="form-control" required/>
						</div>

						<div class="mb-3">
							<label for="exo_porcentaje" > Porcentaje de la exoneración </label>
							<input type="text"  style="text-align: right;"name="ExoPorcentajeExoneracion" id="exo_porcentaje" class="form-control" required/>
						</div>

						<div class="mb-3">
							<label for="aplicaExo"></label>
							<span  id="aplicaExo" class="btn btn-warning text-center" >Aplicar exoneracion</span>
						</div>
					</div>
			</div>
			<hr/>
			<div class="clr clear"></div>


			<div class="col-md-12" id="sticker">
				<hr/>
				<a href="#" id="addManually" class="tip btn btn-success" title=""
				   data-original-title="Agregar Producto manualmente" tabindex="-1">
					<i class="fa fa-2x fa-plus-circle addIcon" id="addIcon"></i>
					Agregar articulo comprado
				</a>
				<div class="clearfix"></div>
				<hr/>
			</div>
			<div class="clearfix"></div>
			<div class="col-md-12">
					<div class="control-group table-group">
						<label class="table-label">Items de la factura*</label>

						<div class="controls table-controls">
							<table id="slTable"
								   class="table items table-striped table-bordered table-condensed table-hover sortable_table">
								<thead>
								<tr>
									<th class="col-md-4">Producto (codigo - nombre)</th>
									<th class="col-md-2">Serial Nº</th>
									<th class="col-md-1">Precio</th>
									<th class="col-md-1">Cant.</th>
									<th class="col-md-1">MontoTotal</th>
									<th class="col-md-1">Descuento</th>
									<th class="col-md-1">SubTotal</th>
									<th class="col-md-1">Imp.</th>
									<th class="col-md-1">Exoneracion</th>
									<th class="col-md-1">Imp.Neto</th>
									<th class="col-md-1">Total</th>
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
                <div class="table-responsive">
								<table style="width: 100%">
									<tr>
										<td rowspan="14" style="padding: 0; margin: 0; border: 1px solid var(--nx-border);">
											<div class="col-md-12">
												<h2>Formulario de Pago</h2>
											</div>
											<div class="col-md-12">

												<div class="col-sm-6">
													<div class="mb-3">
														Estado del pago
														<select name="payment_status" class=" input-tip" required="required"
																id="slpayment_status">
															<option value="due" disabled>A Credito</option>
															<option value="partial" disabled>Parcial</option>
															<option value="paid" selected>Pagado</option>
														</select>
		
													</div>
												</div>
												<input type="hidden" name="token_post" id="token_post" value="<?= md5(date('Y-m-d H:i:s')) ?>"/>
		
												<div class="col-sm-6" id="credit_time">
													<div class="mb-3">
														Seleccione el Tiempo del Credito
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
														Pagar por
														<select required name="paid_by_1" id="paid_by_1" class="paid_by">
															<option value="cash">Efectivo</option>
															<option value="CC">Tarjeta Credito / Debito
															</option>
															<option value="Cheque">Cheque</option>
															<option value="deposit">Deposito</option>
														</select>
		
													</div>
		
												</div>
											</div>
										</td>
										<td align="right">TotalServGravados</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalServGravados">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalServExentos</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalServExentos">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalServExonerado</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalServExonerado">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalMercanciasGravadas</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalMercanciasGravadas">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalMercanciasExentas</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalMercanciasExentas">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalMercExonerada</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalMercExonerada">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalGravado</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalGravado">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalExento</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalExento">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalExonerado</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalExonerado">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalVenta</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalVenta">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalDescuentos</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalDescuentos">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalVentaNeta</td>
										<td style="width: 18%;padding: 2px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalVentaNeta">0.00
										</td>
									</tr>
									<tr>
										<td align="right">TotalImpuesto</td>
										<td style="width: 18%;padding: 0px 4% 0; border-bottom: solid 1px var(--nx-border);"
											align="right" id="TotalImpuesto">0.00
										</td>
									</tr>
									<tr>
										<td style=" font-size: 14px; font-weight: bold;" align="right">TotalComprobante</td>
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
					<h3 class="modal-title">Agregar proveedor con regimen simplificado</h3>
				</div>
			<div class="modal-content">
			<!-- <form> -->
					<div class="col-md-12"> 
								<div class="mb-3">
									<label for="mname" class="col-sm-4 form-label">Codigo Act. economica *</label>
									<div class="col-sm-8">
											<!-- <input required type="text" class="form-control" name="txtCodActEco" id="txtCodActEco" /> -->
											<select required type="text" class="form-control" id="txtCodActEco" name="txtCodActEco" data-bs-toggle="tooltip" data-placement="left">
												
											</select>
											<input  type="hidden" class="form-control" name="formFC" id="formFC" value="FEC" />
									</div>
								</div>
								<div class="mb-3">
									<label for="tcedula" class="col-sm-4 form-label">Identificacion *</label>
									<div class="col-sm-8">
									<?php
								    // $pre_id_number = explode(',', $row['pre_id_number']);
									$pre_id_number_opt = array('01' => 'Cedula de Identidad', '02' => 'Cedula Juridica', '03' => 'DIMEX', '04' => 'NITE', '05' => 'Passaporte');
									?>
									<select required name='tcedula' id="tcedula" rows='5' class='form-control ' >
										<?php
										echo "<option  value ='' selected>-- Please Select --</option>";
										foreach ($pre_id_number_opt as $key => $val) {
											echo "<option  value =".$key.">".$val."</option>";
										}
										?>
									</select>
									</div>
								</div>
								<div class="mb-3">
									<label for="mname" class="col-sm-4 form-label">N° identificacion *</label>
									<div class="col-sm-8">
											<input required  onkeyup="obtenerActividades(this.value , '#txtCodActEco','#txtNombre','#tcedula')"  type="text" class="form-control" id="txtIdentificacion" name="txtIdentificacion"/>
									</div>
								</div>
								<div class="mb-3">
									<label for="mname" class="col-sm-4 form-label">Nombre *</label>
									<div class="col-sm-8">
											<input required type="text" class="form-control" id="txtNombre" name="txtNombre" />
									</div>
								</div>
								<div class="clearfix"></div>
								<hr/>
								<legend> Direccion</legend>
								<div class="mb-3">
									<label for="tipo_persona" class="col-sm-4 form-label">Provincia</label>
									<div class="col-sm-8">
										<select required name='codigo_provincia' id='codigo_provincia' class='form-control' onchange="obtenerCanton(this.value)">
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
									<label for="Canton" class="col-sm-4 form-label">Canton</label>
									<div class="col-sm-8">
									<select required name='codigo_canton' id='codigo_canton'
									class='form-control' onchange="obtenerDistrito(this.value)"></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="Distrito" class="col-sm-4 form-label">Distrito</label>
									<div class="col-sm-8">
									<select required name='codigo_distrito' id='codigo_distrito'
									class='form-control' onchange="obtenerBarrio(this.value)"></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="Barrio" class="col-sm-4 form-label">Barrio</label>
									<div class="col-sm-8">
									<select required name='codigo_barrio' id='codigo_barrio'
									class='form-control'></select>
									</div>
								</div>
								<div class="mb-3">
									<label for="Barrio" class="col-sm-4 form-label">Otras se&ntilde;as</label>
									<div class="col-sm-8">
											<input required type="text" class="form-control" id="txtOtraSe" name="txtOtraSe" />
									</div>
								</div>
								<legend> Datos de contacto</legend>
								<div class="mb-3  ">
									<label for="Telefonos" class=" form-label col-md-4 text-left"> Telefono  <span
									class="asterix"> * </span></label>
									<div class="col-md-6">
										<input required type="text" class="form-control" id="txtTel" name="txtTel" />
									</div>
									<div class="col-md-2">
									</div>
								</div>
								<div class="mb-3  ">
									<label for="Email" class=" form-label col-md-4 text-left"> Email <span
									class="asterix"> * </span></label>
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
						class="icon-cancel-circle2 "></i>Cancelar </button>
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
										class="fa fa-2x">&times;</i></span><span class="sr-only">Cerrar</span></button>
						<h4 class="modal-title" id="prModalLabel"></h4>
					</div>
					<div class="modal-body needs-validation" id="pr_popover_content" novalidate>
						<form class="form-horizontal" role="form">
	
							<div class="mb-3">
								<label class="col-sm-4 form-label">Impuesto</label>
								<div class="col-sm-8">
	
									<select style="padding: 0;" name="ptax" id="ptax" class="form-control"
											tabindex="-1"
											title="" data-original-title="Impuesto sobre Producto *" required>
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
								<label for="pserial" class="col-sm-4 form-label">Serial</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="pserial" >
								</div>
							</div>
	
							<div class="mb-3">
								<label for="pquantity" class="col-sm-4 form-label">Cantidad</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="pquantity" required>
								</div>
							</div>
							<div class="mb-3">
								<label for="punit" class="col-sm-4 form-label">Unidad</label>
								<div class="col-sm-8">
									<select style="padding: 0;" name="punit" id="punit"
											class="col-md-12  form-control input-tip select" tabindex="-1"
											title="" data-original-title="Unidad *" required>
										<option value=""></option>
										<option value="Sp">Servicios Profesionales</option>
										<option value="m">Metro</option>
										<option value="kg">Kilogramo</option>
										<option value="m²">Metro Cuadrado</option>
										<option value="m³">Metro Cubico</option>
										<option value="´">Minuto</option>
										<option value="h">Hora</option>
										<option value="d">Dia</option>
										<option value="L">Litro</option>
										<option value="t">Tonelada</option>
										<option value="Unid">Unidad</option>
										<option value="Gal">Galon</option>
									</select>
								</div>
							</div>
	
							<div class="mb-3">
								<label for="pdiscount"
									   class="col-sm-4 form-label">Descuento</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="pdiscount">
								</div>
							</div>
	
							<div class="mb-3">
								<label for="pprice" class="col-sm-4 form-label">Precio</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="pprice" required>
								</div>
							</div>
                <div class="table-responsive">
							<table class="table table-bordered table-striped">
								<tr>
									<th style="width:25%;">Precio Neto</th>
									<th style="width:25%;"><span id="net_price"></span></th>
									<th style="width:25%;">Impuesto sobre Producto</th>
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
						<button type="button" class="btn btn-primary" id="editItem">Editar</button>
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
										class="fa fa-2x">×</i></span><span class="sr-only">Cerrar</span></button>
						<h4 class="modal-title" id="mModalLabel">Agregar Producto manualmente</h4>
					</div>
					<div class="modal-body needs-validation" id="pr_popover_content2" novalidate>
						<form class="form-horizontal" role="form">
							<div class="mb-3">
								<label for="mitem_type" class="col-sm-4 form-label">Tipo de Producto </label>
	
								<div class="col-sm-8">
	
									<select required="required" style="padding: 0;" name="mitem_type" id="mitem_type"
											class="col-md-12  form-control input-tip select" tabindex="-1"
											title="" data-original-title="Tipo de Items " required>
										<option value="standard">Mercancia</option>
										<option value="service">Servicio</option>
									</select>
								</div>
							</div>
							<div class="mb-3">
								<label for="mcode" class="col-sm-4 form-label">Código de producto *</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mcode" value="" required="required">
								</div>
							</div>
							<div class="mb-3">
								<label for="mname" class="col-sm-4 form-label">Nombre *</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mname" value="" required="required">
								</div>
							</div>
							<div class="mb-3">
								<label for="mtax" class="col-sm-4 form-label">Tipo de Impuesto *</label>
	
								<div class="col-sm-8">
	
									<select style="padding: 0;" name="mtax" id="mtax" class="form-control"
											tabindex="-1"
											title="" data-original-title="Impuesto sobre Producto *">
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
								<label for="mquantity" class="col-sm-4 form-label">Cantidad *</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mquantity" value="1" required="required">
								</div>
							</div>
							<div class="mb-3">
								<label for="munit" class="col-sm-4 form-label">Unidad *</label>
	
								<div class="col-sm-8">
	
									<select style="padding: 0;" name="munit" id="munit" class="form-control" tabindex="-1"
											title="" data-original-title="Unidad *" required="required">
										<option value=""></option>
										<option value="Sp">Servicios Profesionales</option>
										<option value="m">Metro</option>
										<option value="kg">Kilogramo</option>
										<option value="m²">Metro Cuadrado</option>
										<option value="m³">Metro Cubico</option>
										<option value="´">Minuto</option>
										<option value="h">Hora</option>
										<option value="d">Dia</option>
										<option value="L">Litro</option>
										<option value="t">Tonelada</option>
										<option value="Unid">Unidad</option>
										<option value="Gal">Galon</option>
									</select>
								</div>
							</div>
							<div class="mb-3">
								<label for="mdiscount" class="col-sm-4 form-label">Descuento del producto</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mdiscount" >
								</div>
							</div>
							<div class="mb-3">
								<label for="mprice" class="col-sm-4 form-label">Precio Unitario *</label>
	
								<div class="col-sm-8">
									<input type="text" class="form-control" id="mprice" value="" required>
								</div>
							</div>
                <div class="table-responsive">
							<table class="table table-bordered table-striped">
								<tbody>
								<tr>
									<th style="width:25%;">Precio Unitario Neto</th>
									<th style="width:25%;"><span id="mnet_price">0.00</span></th>
									<th style="width:25%;">Impuesto sobre Producto</th>
									<th style="width:25%;"><span id="mpro_tax">0.00</span></th>
								</tr>
								</tbody>
							</table>
                </div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" id="addItemManually" tabindex="-1">Enviar</button>
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
