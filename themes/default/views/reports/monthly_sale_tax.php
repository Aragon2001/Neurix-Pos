<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('customer')) { $v .= "&customer=" . $this->input->post('customer'); }
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('monthly_sale_tax'); ?>
        <small><?= lang('customize_report'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="nxt-card" style="padding:16px 18px;margin-bottom:20px">
    <?= form_open("reports/monthly_sale_tax"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" for="customer"><?= lang('customer'); ?></label>
            <?php
            $cu[0] = lang("select") . " " . lang("customer");
            foreach ($customers as $customer) { $cu[$customer->id] = $customer->name; }
            echo form_dropdown('customer', $cu, set_value('customer'), 'class="form-select" id="customer"');
            ?>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="start_date"><?= lang('start_date'); ?></label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= set_value('start_date'); ?>">
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="end_date"><?= lang('end_date'); ?></label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= set_value('end_date'); ?>">
        </div>
        <div class="col-sm-2">
            <button type="submit" class="nxt-btn" style="width:100%;justify-content:center"><?= lang('submit'); ?></button>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var MONEY = [
        ['TotalServGravados', '<?= lang('total_serv_gravados_f'); ?>'],
        ['TotalServExentos', '<?= lang('total_serv_exentos_f'); ?>'],
        ['TotalMercanciasGravadas', '<?= lang('total_merc_gravadas'); ?>'],
        ['TotalMercanciasExentas', '<?= lang('total_merc_exentas'); ?>'],
        ['TotalMercExonerada', '<?= lang('total_merc_exonerada'); ?>'],
        ['TotalServExonerado', '<?= lang('total_serv_exonerado'); ?>'],
        ['TotalGravado', '<?= lang('total_gravado'); ?>'],
        ['TotalExento', '<?= lang('total_exento'); ?>'],
        ['TotalExonerado', '<?= lang('total_exonerado'); ?>'],
        ['TotalVenta', '<?= lang('total_venta'); ?>'],
        ['TotalDescuentos', '<?= lang('descuento_compra'); ?>'],
        ['TotalVentaNeta', '<?= lang('total_venta_neta'); ?>'],
        ['TotalImpuesto', '<?= lang('full_tax_col'); ?>'],
        ['tarifa0', '<?= lang('tarifa_0'); ?>'],
        ['tarifa1', '<?= lang('tarifa_1'); ?>'],
        ['tarifa2', '<?= lang('tarifa_2'); ?>'],
        ['tarifa4', '<?= lang('tarifa_4'); ?>'],
        ['tarifa13', '<?= lang('tarifa_13'); ?>'],
        ['TotalComprobante', '<?= lang('total_comprobante'); ?>']
    ];
    var cols = [
        { key: 'identificacion', label: '<?= lang('identificacion'); ?>', render: function (r) { return r.identificacion ? '<span class="nxt-code">' + NxTable.esc(r.identificacion) + '</span>' : '—'; } },
        { key: 'NombreCompleto', label: '<?= lang('nombre_completo'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-ent-name">' + NxTable.esc(r.NombreCompleto) + '</span>'; } },
        { key: 'FechaFactura', label: '<?= lang('fecha_factura_col'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.FechaFactura) + '</span>'; } },
        { key: 'codigoMoneda', label: '<?= lang('codigo_moneda'); ?>', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.codigoMoneda || '—') + '</span>'; } },
        { key: 'tipoCambio', label: '<?= lang('tipo_cambio_col'); ?>', className: 'num', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.tipoCambio == null ? '—' : r.tipoCambio) + '</span>'; } },
        { key: 'creditoCompra', label: '<?= lang('credito_ventas'); ?>', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.creditoCompra == null ? '—' : r.creditoCompra) + '</span>'; } },
        { key: 'consecutivo', label: '<?= lang('numero_factura'); ?>', sortable: 'str', render: function (r) { return r.consecutivo ? '<span class="nxt-code">' + NxTable.esc(r.consecutivo) + '</span>' : '—'; } }
    ];
    MONEY.forEach(function (mc) {
        cols.push({ key: mc[0], label: mc[1], className: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r[mc[0]]) + '</span>'; } });
    });
    cols.push({ key: 'tipo', label: '<?= lang('tipo_col'); ?>', render: function (r) { return r.tipo ? NxTable.badge(r.tipo, 'info') : '—'; } });

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_monthly_sale_tax/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '2600px',
        unit: '<?= lang('sales'); ?>'.toLowerCase(),
        exportName: 'ventas_impuestos',
        search: ['identificacion', 'NombreCompleto', 'consecutivo', 'FechaFactura'],
        totals: MONEY.map(function (mc) { return mc[0]; }),
        columns: cols,
        i18n: {
            searchPlaceholder: '<?= lang('buscar_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>',
            totals: '<?= lang('total'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });
});
</script>
