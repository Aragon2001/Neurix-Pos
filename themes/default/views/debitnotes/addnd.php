<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<div class="nxf-page">
  <div class="row">
    <div class="col-md-8">
      <div class="nxf-card">
        <div class="nxf-card-head"><div class="nxf-card-title"><?= lang('nueva_nota_debito'); ?> — <?= lang('invoice'); ?> #<?= $sale->id ?></div></div>
        <div class="nxf-card-body">
          <p><strong><?= lang('customer'); ?>:</strong> <?= htmlspecialchars(isset($customer->name) ? $customer->name : '') ?> | <strong><?= lang('date'); ?>:</strong> <?= $sale->date ?> | <strong><?= lang('grand_total'); ?>:</strong> <?= number_format($sale->grand_total, 2) ?></p>
          <hr>
          <?= form_open('debitnotes/create') ?>
          <?= form_hidden('sale_id', $sale->id) ?>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label><?= lang('tipo_doc_referencia'); ?></label>
                <select name="type_nd" class="form-control">
                  <option value="01">01 — <?= lang('Factura Electronica'); ?></option>
                  <option value="04">04 — <?= lang('tiquete_electronico'); ?></option>
                  <option value="08">08 — <?= lang('fec'); ?></option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label><?= lang('codigo_razon_nd'); ?></label>
                <select name="motivo_nd" class="form-control">
                  <option value="01">01 — <?= lang('razon_error_monto'); ?></option>
                  <option value="02">02 — <?= lang('razon_homologacion'); ?></option>
                  <option value="99">99 — <?= lang('otros'); ?></option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label><?= lang('razon_texto_libre'); ?></label>
                <input type="text" name="hold_ref" class="form-control" placeholder="<?= lang('descripcion_ajuste'); ?>" maxlength="255">
              </div>
            </div>
          </div>
          <h4><?= lang('items_adicionales'); ?></h4>
                <div class="table-responsive">
          <table class="table table-bordered" id="nd-items">
            <thead><tr><th><?= lang('description'); ?></th><th><?= lang('quantity'); ?></th><th><?= lang('precio_unit'); ?></th><th><?= lang('imp'); ?> %</th><th></th></tr></thead>
            <tbody>
              <tr>
                <td><input type="text" name="item_name[]" class="form-control" placeholder="<?= lang('description'); ?>" required></td>
                <td><input type="number" name="item_qty[]" class="form-control" value="1" min="0.001" step="0.001"></td>
                <td><input type="number" name="item_price[]" class="form-control" value="0" min="0" step="0.01"></td>
                <td><input type="number" name="item_tax[]" class="form-control" value="13" min="0" max="100"></td>
                <td><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
              </tr>
            </tbody>
          </table>
                </div>
          <button type="button" id="add-row" class="btn btn-default btn-sm"><i class="fa fa-plus"></i> <?= lang('agregar_linea'); ?></button>
          <hr>
          <button type="submit" class="btn btn-warning"><i class="fa fa-save"></i> <?= lang('guardar_nd'); ?></button>
          <a href="<?= site_url('debitnotes') ?>" class="btn btn-default"><?= lang('cancel'); ?></a>
          <?= form_close() ?>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
    'use strict';

    var cuerpo = document.querySelector('#nd-items tbody');
    var agregar = document.getElementById('add-row');
    if (!cuerpo || !agregar) { return; }

    agregar.addEventListener('click', function () {
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" name="item_name[]" class="form-control" placeholder="<?= lang('description'); ?>" required></td>'
          + '<td><input type="number" name="item_qty[]" class="form-control" value="1" min="0.001" step="0.001"></td>'
          + '<td><input type="number" name="item_price[]" class="form-control" value="0" min="0" step="0.01"></td>'
          + '<td><input type="number" name="item_tax[]" class="form-control" value="13" min="0" max="100"></td>'
          + '<td><button type="button" class="btn btn-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>';
        cuerpo.appendChild(tr);
    });

    cuerpo.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) { e.target.closest('tr').remove(); }
    });
})();
</script>
