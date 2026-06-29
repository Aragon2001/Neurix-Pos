<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<section class="content">
  <div class="row">
    <div class="col-md-8">
      <div class="box box-warning">
        <div class="box-header">
          <h3 class="box-title">Nota de Débito #<?= $nd->id ?></h3>
          <div class="box-tools">
            <a href="<?= site_url('debitnotes') ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Volver</a>
          </div>
        </div>
        <div class="box-body">

          <div class="row">
            <div class="col-md-6">
              <p><strong>Factura origen:</strong> #<?= $nd->sale_id ?></p>
              <p><strong>Cliente:</strong> <?= htmlspecialchars($nd->customer_name) ?></p>
              <p><strong>Fecha:</strong> <?= $nd->date ?></p>
              <p><strong>Razón:</strong> <?= htmlspecialchars($nd->hold_ref) ?></p>
            </div>
            <div class="col-md-6">
              <p><strong>Subtotal:</strong> <?= number_format($nd->total, 2) ?></p>
              <p><strong>IVA:</strong> <?= number_format($nd->total_tax, 2) ?></p>
              <p><strong>Total:</strong> <?= number_format($nd->grand_total, 2) ?></p>
              <?php if ($hacienda): ?>
                <p><strong>Estado Hacienda:</strong> <span class="label label-<?= $hacienda->estatus_hacienda === 'aceptado' ? 'success' : 'warning' ?>"><?= $hacienda->estatus_hacienda ?></span></p>
                <p><strong>Consecutivo:</strong> <?= $hacienda->consecutivo ?></p>
              <?php else: ?>
                <a href="<?= site_url('Shacienda/generarND/' . $nd->id) ?>"
                   class="btn btn-warning"
                   onclick="return confirm('¿Generar y enviar esta ND a Hacienda?');">
                  <i class="fa fa-send"></i> Generar y enviar a Hacienda
                </a>
              <?php endif; ?>
            </div>
          </div>
          <hr>
                <div class="table-responsive">
          <table class="table table-bordered table-condensed">
            <thead><tr><th>Descripción</th><th>Cant.</th><th>Precio Unit.</th><th>IVA</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item->product_name) ?></td>
                <td><?= $item->quantity ?></td>
                <td><?= number_format($item->unit_price, 2) ?></td>
                <td><?= $item->tax ?></td>
                <td><?= number_format($item->quantity * $item->unit_price, 2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
                </div>

          <?php if ($hacienda): ?>
          <div class="btn-group">
            <a target="_blank" href="<?= site_url('XmlHacienda/xmlFirmadoND/' . $nd->id) ?>" class="btn btn-info btn-sm"><i class="fa fa-list"></i> XML Firmado</a>
            <a target="_blank" href="<?= site_url('XmlHacienda/xmlMensajeND/' . $nd->id) ?>" class="btn btn-warning btn-sm"><i class="fa fa-list"></i> Respuesta Hacienda</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
