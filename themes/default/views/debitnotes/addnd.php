<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<section class="content">
  <div class="row">
    <div class="col-md-8">
      <div class="box box-warning">
        <div class="box-header"><h3 class="box-title">Nueva Nota de Débito — Factura #<?= $sale->id ?></h3></div>
        <div class="box-body">
          <p><strong>Cliente:</strong> <?= htmlspecialchars(isset($customer->name) ? $customer->name : '') ?> | <strong>Fecha venta:</strong> <?= $sale->date ?> | <strong>Total original:</strong> <?= number_format($sale->grand_total, 2) ?></p>
          <hr>
          <?= form_open('debitnotes/create') ?>
          <?= form_hidden('sale_id', $sale->id) ?>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Tipo Documento Referencia</label>
                <select name="type_nd" class="form-control">
                  <option value="01">01 — Factura Electrónica</option>
                  <option value="04">04 — Tiquete Electrónico</option>
                  <option value="08">08 — FEC</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Código de Razón (Motivo ND)</label>
                <select name="motivo_nd" class="form-control">
                  <option value="01">01 — Error de monto</option>
                  <option value="02">02 — Homologación</option>
                  <option value="99">99 — Otros</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Razón (texto libre)</label>
                <input type="text" name="hold_ref" class="form-control" placeholder="Descripción del ajuste" maxlength="255">
              </div>
            </div>
          </div>
          <h4>Ítems adicionales a facturar</h4>
          <table class="table table-bordered" id="nd-items">
            <thead><tr><th>Descripción</th><th>Cantidad</th><th>Precio Unit.</th><th>IVA %</th><th></th></tr></thead>
            <tbody>
              <tr>
                <td><input type="text" name="item_name[]" class="form-control" placeholder="Descripción" required></td>
                <td><input type="number" name="item_qty[]" class="form-control" value="1" min="0.001" step="0.001"></td>
                <td><input type="number" name="item_price[]" class="form-control" value="0" min="0" step="0.01"></td>
                <td><input type="number" name="item_tax[]" class="form-control" value="13" min="0" max="100"></td>
                <td><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
              </tr>
            </tbody>
          </table>
          <button type="button" id="add-row" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> Agregar línea</button>
          <hr>
          <button type="submit" class="btn btn-warning"><i class="fa fa-save"></i> Guardar Nota de Débito</button>
          <a href="<?= site_url('debitnotes') ?>" class="btn btn-default">Cancelar</a>
          <?= form_close() ?>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
$(function(){
  $('#add-row').on('click', function(){
    var row = '<tr><td><input type="text" name="item_name[]" class="form-control" placeholder="Descripción" required></td><td><input type="number" name="item_qty[]" class="form-control" value="1" min="0.001" step="0.001"></td><td><input type="number" name="item_price[]" class="form-control" value="0" min="0" step="0.01"></td><td><input type="number" name="item_tax[]" class="form-control" value="13" min="0" max="100"></td><td><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td></tr>';
    $('#nd-items tbody').append(row);
  });
  $(document).on('click', '.remove-row', function(){ $(this).closest('tr').remove(); });
});
</script>
