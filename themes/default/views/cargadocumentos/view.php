<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-bs-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
            </button>
            <h4 class="modal-title"
                id="myModalLabel"><?= $documento->documento . ' (' . $documento->ConsecutivoDocEmisor . ')'; ?></h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-12">
                    <div class="table-responsive">

                        <?php if (@$documento->Estatus == "0" || $documento->Estatus == null) { ?>
                            <form action="cargadocumentos/setstatusdocumento" method="post">
                            <?php } ?>
                            <?php
                            $csrf = array(
                            'name' => $this->security->get_csrf_token_name(),
                            'hash' => $this->security->get_csrf_hash()
                            );
                            ?>
                            <input type="hidden" name="<?= $csrf['name']; ?>" value="<?= $csrf['hash']; ?>"/>

                            <div class="col-md-4">
                                <fieldset>
                                    <legend> <?php echo lang('action_doc') ?></legend>

                                    <div class="mb-3 ">
                                        <label for="Mensaje"> <?php lang('message') ?>
                                            <span class="asterix"> * </span></label>

                                        <?php $Mensaje_opt = array('1' => lang('accept'), '2' => lang('partially_accept'), '3' => lang('reject_this_document')); ?>

                                        <select <?php
                                        if (@$documento->Estatus != "0" and $documento->Estatus != null) {
                                            echo 'disabled="disabled"';
                                        }
                                        ?> name='Mensaje' rows='5' required class='tom-select '>
                                            <?php
                                            foreach ($Mensaje_opt as $key => $val) {
                                                echo "<option  value ='$key' " . (@$documento->Mensaje == $key ? " selected='selected' " : '') . ">$val</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3 ">
                                        <label for="Mensaje"> Condicion de Impuesto
                                            <span class="asterix"> </span></label>

                                        <?php $condicion_opt = array(
                                            '00' => lang('Seleccione'),
                                            '01' => lang('General Credito IVA'),
                                            '02' => lang('General Credito parcial del IVA'),
                                            '03' => lang('Bienes de Capital'),
                                            '04' => lang('Gasto Corriente no genera credito'),
                                            '05' => lang('Proporcionalidad')
                                            ); ?>

                                        <select <?php
                                        if (@$documento->Estatus != "0" and $documento->Estatus != null) {
                                            echo 'disabled="disabled"';
                                        }
                                        ?> name='CondicionImpuesto' rows='5'  class='tom-select '>
                                            <?php
                                            foreach ($condicion_opt as $key => $val) {
                                                echo "<option  value ='$key' " . (@$documento->Mensaje == $key ? " selected='selected' " : '') . ">$val</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3  ">
                                        <label for="MontoTotalImpuestoAcreditar">
                                            Monto Total Impuesto Acreditar <span class="asterix"> </span></label>
                                        <input <?php
                                        if (@$documento->Estatus != "0" and $documento->Estatus != null) {
                                            echo 'disabled="disabled"';
                                        }
                                        ?> name='MontoTotalImpuestoAcreditar' id='MontoTotalImpuestoAcreditar'
                                            class='form-control '
                                             value="<?php echo @$documento->MontoTotalImpuestoAcreditar ?>" />
                                        <div class="col-md-2">
                                           
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3  ">
                                        <label for="MontoTotalDeGastoAplicable">
                                            Monto Total De Gasto Aplicable <span class="asterix"> </span></label>
                                        <input <?php
                                        if (@$documento->Estatus != "0" and $documento->Estatus != null) {
                                            echo 'disabled="disabled"';
                                        }
                                        ?> name='MontoTotalDeGastoAplicable' id='MontoTotalDeGastoAplicable'
                                            class='form-control '
                                             value="<?php echo @$documento->MontoTotalDeGastoAplicable ?>" />
                                        <div class="col-md-2">
                                           
                                        </div>
                                    </div>
                                  
                                    
                                    
                                    
                                    
                                    
                                    
                                    
                                    
                                    <div class="mb-3  ">
                                        <label for="Detalle Mensaje">
                                            Detalle Mensaje <span class="asterix"> * </span></label>
                                        <textarea <?php
                                        if (@$documento->Estatus != "0" and $documento->Estatus != null) {
                                            echo 'disabled="disabled"';
                                        }
                                        ?> name='DetalleMensaje' rows='5' id='DetalleMensaje'
                                            class='form-control '
                                            required><?php echo @$documento->DetalleMensaje ?></textarea>
                                        <div class="col-md-2">
                                            <a href="#" data-bs-toggle="tooltip" placement="left" class="tips"
                                               title="Motivo por el cual Acepta, Acepta parcialmente o Rechaza, el Comprobante electronico"><i
                                                    class="icon-question2"></i></a>
                                        </div>
                                    </div>
                                    
                                     
                                </fieldset>
                            </div>

                            <div class="col-md-4">
                                <fieldset>
                                    <legend> <?php echo lang('action_doc') ?></legend>

                                    <div class="mb-3  ">
                                        <label for="Documento">
                                            Documento </label>
                                        <input readonly="readonly" name="documento"
                                               value="<?php echo @$documento->documento ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                    <div class="mb-3  ">
                                        <label for="Clave"> Clave </label>
                                        <input readonly="readonly" name="ClaveDocEmisor"
                                               value="<?php echo @$documento->ClaveDocEmisor ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                    <div class="mb-3  ">
                                        <label for="Fecha Emision"> Fecha
                                            Emision </label>
                                        <input readonly="readonly" name="FechaEmisionDoc"
                                               value="<?php echo @$documento->FechaEmisionDoc ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                    <div class="mb-3  ">
                                        <label for="MontoTotal Impuesto">
                                            MontoTotal Impuesto </label>
                                        <input readonly="readonly" name="MontoTotalImpuesto"
                                               value="<?php echo @$documento->MontoTotalImpuesto ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                    <div class="mb-3  ">
                                        <label for="Total"> Total </label>
                                        <input readonly="readonly" name="TotalFactura"
                                               value="<?php echo @$documento->TotalFactura ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                </fieldset>
                            </div>

                            <div class="col-md-4">
                                <fieldset>
                                    <legend> <?php echo lang('issuer_data') ?> </legend>
                                    <input readonly="readonly" value="<?php echo $documento->id_documento ?>"
                                           name="id_documento" type="hidden"/>
                                    <div class="mb-3  ">
                                        <label for="Nombre Emisor"> Nombre
                                            Emisor </label>
                                        <input readonly="readonly" name="nombre_emisor"
                                               value="<?php echo @$documento->nombre_emisor ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                    <div class="mb-3  ">
                                        <label for="Tipo Doc Emisor"> Tipo
                                            Doc Emisor </label>
                                        <input readonly="readonly" name="tipo_doc_emisor"
                                               value="<?php echo @$documento->tipo_doc_emisor ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                    <div class="mb-3  ">
                                        <label for="Numero Cedula"> Numero
                                            Cedula </label>
                                        <input readonly="readonly" name="NumeroCedulaEmisor"
                                               value="<?php echo @$documento->NumeroCedulaEmisor ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                    <div class="mb-3  ">
                                        <label for="Telefono Emisor">
                                            Telefono Emisor </label>
                                        <input readonly="readonly" name="telefono_emisor"
                                               value="<?php echo @$documento->telefono_emisor ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                    <div class="mb-3  ">
                                        <label for="Correo Emisor"> Correo
                                            Emisor </label>
                                        <input readonly="readonly" name="correo_emisor"
                                               value="<?php echo @$documento->correo_emisor ?>"
                                               class="form-control" placeholder=""/>
                                    </div>
                                </fieldset>
                            </div>


                            <div style="clear:both"></div>


                            <div class="mb-3">
                                <label class="col-sm-4 text-right">&nbsp;</label>
                                <div class="col-sm-8"> 
                                    <?php if (@$documento->Estatus == "0" || $documento->Estatus == null) { ?>
                                        <input type="submit" style="float: right;"
                                               class="btn btn-primary btn-sm"
                                               value="<?php echo lang('send_document') ?>"/>
                                           <?php } ?>
                                </div>

                            </div>

                            <?php if (@$documento->Estatus == "0" || $documento->Estatus == null) { ?>
                            <?php } else { ?>
                            </form>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <div class="col-12">
            </div>
        </div>

    </div>
</div>