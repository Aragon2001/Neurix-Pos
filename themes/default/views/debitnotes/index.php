<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<section class="content">
  <div class="row">
    <div class="col-12">
      <div class="box">
        <div class="box-header">
          <h3 class="box-title">Notas de Débito Electrónicas</h3>
        </div>
        <div class="box-body">
          <table id="nd_table" class="table table-bordered table-striped">
            <thead><tr>
              <th>ID</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>Razón</th><th>Estado Hacienda</th><th>Consecutivo</th><th>XMLs</th><th>Acciones</th>
            </tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
$(function() {
  $('#nd_table').DataTable({
    processing: true, serverSide: true,
    ajax: { url: '<?= site_url('debitnotes/get_debitnotes') ?>', type: 'POST' },
    columns: [
      {data:'id'},{data:'date'},{data:'customer_name'},{data:'grand_total'},
      {data:'hold_ref'},{data:'estatus_hacienda'},{data:'consecutivo'},
      {data:'xmls',orderable:false},{data:'Actions',orderable:false}
    ]
  });
});
</script>
