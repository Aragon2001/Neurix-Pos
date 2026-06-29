<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
  <div class="row">
    <div class="col-xs-12">
      <div class="box box-primary">
        <div class="box-header">
          <h3 class="box-title"><?= lang('update_info'); ?></h3>
        </div>
        <div class="box-body">
          <?php echo form_open("customers/edit/".$customer->id);?>

          <div class="col-md-6">
            <div class="form-group">
              <label class="control-label" for="code"><?= $this->lang->line("name"); ?></label>
              <?= form_input('name', set_value('name', $customer->name), 'class="form-control" id="name"'); ?>
            </div>

          <div class="form-group">
              <label class="control-label" for="code">Nombre Comercial</label>
              <?= form_input('business_name', set_value('business_name', $customer->business_name), 'class="form-control" id="business_name"'); ?>
          </div>

            <div class="form-group">
              <label class="control-label" for="email_address"><?= $this->lang->line("email_address"); ?></label>
              <?= form_input('email', set_value('email', $customer->email), 'class="form-control" id="email_address"'); ?>
            </div>

            <div class="form-group">
              <label class="control-label" for="phone"><?= $this->lang->line("phone"); ?></label>
              <?= form_input('phone', set_value('phone', $customer->phone), 'class="form-control" id="phone"');?>
            </div>


              <div class="form-group">
                  <label class="control-label" for="cf1"><?= $this->lang->line("ccf1"); ?></label>
                  <select name="cf1" class="form-control select2" id="cf1" style="width:100%;">
                      <option <?= ($customer->cf1 == '01') ? 'selected="selected"' : ''; ?> value="01">Cédula de Identidad</option>
                      <option <?= ($customer->cf1 == '02') ? 'selected="selected"' : ''; ?> value="02">Cédula Jurídica</option>
                      <option <?= ($customer->cf1 == '03') ? 'selected="selected"' : ''; ?> value="03">DIMEX</option>
                      <option <?= ($customer->cf1 == '04') ? 'selected="selected"' : ''; ?> value="04">NITE</option>
                      <option <?= ($customer->cf1 == '05') ? 'selected="selected"' : ''; ?> value="05">Pasaporte</option>
                  </select>
              </div>

              <div class="form-group">
                  <label class="control-label" for="cf2"><?= $this->lang->line("ccf2"); ?></label>
                  <div class="input-group">
                      <?= form_input('cf2', set_value('cf2', @$customer->cf2), 'class="form-control" id="cf2"');?>
                      <span class="input-group-btn">
                          <button type="button" id="btn-hacienda" class="btn btn-info btn-sm" title="Consultar nombre y actividades en Hacienda">
                              <i class="fa fa-search"></i> Buscar en Hacienda
                          </button>
                      </span>
                  </div>
              </div>

              <div id="hacienda-alert" class="alert" style="display:none; margin-top:4px;"></div>

              <div class="form-group">
                  <label class="control-label" for="codigo_actividad">Código Actividad Económica (Hacienda)</label>
                  <?= form_input('codigo_actividad', set_value('codigo_actividad', @$customer->codigo_actividad), 'class="form-control" id="codigo_actividad" maxlength="6" placeholder="Ej: 811001"');?>
                  <span class="help-block">Solo para clientes jurídicos que emiten comprobantes (receptor obligado).</span>
              </div>

              <div id="hacienda-actividades-wrap" class="form-group" style="display:none;">
                  <label class="control-label">Actividad Económica — seleccione la correcta</label>
                  <select id="hacienda-actividades-sel" class="form-control"></select>
                  <span class="help-block">Las actividades corresponden a las registradas en Hacienda para esta identificación.</span>
              </div>

              <? if($Settings->enable_credit == 1) { ?>
              <div class="form-group">
                  <label class="control-label" for="limitcredit"><?= $this->lang->line("limitcredit"); ?></label>
                  <?= form_input('limitcredit', set_value('limitcredit', @$customer->limitcredit), 'class="form-control" id="cf2"');?>
              </div>
              <? } ?>


            <div class="form-group">
              <?php echo form_submit('edit_customer', $this->lang->line("edit_customer"), 'class="btn btn-primary"');?>
            </div>
          </div>
          <?php echo form_close();?>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
$(function() {
    var proxyUrl = '<?= site_url("hacienda_proxy/ae/") ?>';

    function consultarHacienda(cedula) {
        cedula = cedula.replace(/\D/g, '');
        if (cedula.length < 9 || cedula.length > 12) {
            showAlert('warning', 'La cédula debe tener entre 9 y 12 dígitos para consultar Hacienda.');
            return;
        }
        var $btn = $('#btn-hacienda').prop('disabled', true).text('Consultando...');
        $('#hacienda-alert').hide();
        $('#hacienda-actividades-wrap').hide();

        $.ajax({
            url: proxyUrl + cedula,
            method: 'GET',
            dataType: 'json',
            timeout: 15000,
            success: function(data) {
                if (data.error) { showAlert('warning', data.error); return; }
                if (data.nombre) $('#name').val(data.nombre);
                if (data.tipoIdentificacion) {
                    $('#cf1').val(data.tipoIdentificacion).trigger('change');
                }
                var alertas = [];
                if (data.situacion) {
                    if (data.situacion.moroso) alertas.push('MOROSO');
                    if (data.situacion.omiso)  alertas.push('OMISO');
                }
                if (alertas.length) {
                    showAlert('danger', '&#9888; Contribuyente: ' + alertas.join(', ') + ' ante Hacienda. Verifique antes de facturar a crédito.');
                } else {
                    showAlert('success', '&#10003; Contribuyente encontrado. Revise y corrija los datos si es necesario.');
                }
                var acts = data.actividades || [];
                if (acts.length === 1) {
                    $('#codigo_actividad').val(acts[0].codigo);
                } else if (acts.length > 1) {
                    var $sel = $('#hacienda-actividades-sel').empty();
                    $.each(acts, function(i, a) {
                        $sel.append($('<option>').val(a.codigo).text(a.codigo + ' — ' + a.descripcion));
                    });
                    $('#codigo_actividad').val(acts[0].codigo);
                    $sel.off('change').on('change', function() { $('#codigo_actividad').val($(this).val()); });
                    $('#hacienda-actividades-wrap').show();
                }
            },
            error: function(xhr) {
                var msg = 'No se pudo consultar Hacienda. Registre manualmente.';
                try { var d = JSON.parse(xhr.responseText); if (d.error) msg = d.error; } catch(e) {}
                showAlert('warning', msg);
            },
            complete: function() { $btn.prop('disabled', false).html('<i class="fa fa-search"></i> Buscar en Hacienda'); }
        });
    }

    function showAlert(type, msg) {
        $('#hacienda-alert').removeClass('alert-success alert-warning alert-danger alert-info')
            .addClass('alert-' + type).html(msg).show();
    }

    $('#btn-hacienda').on('click', function() { consultarHacienda($('#cf2').val()); });
    $('#cf2').on('blur', function() {
        var v = $(this).val().replace(/\D/g, '');
        if (v.length >= 9 && v.length <= 12) consultarHacienda(v);
    });
});
</script>
