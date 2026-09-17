<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('ajuste_inventario'); ?>
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
    <?= form_open("reports/inventory_adjustment"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" for="start_date"><?= lang('start_date'); ?></label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= set_value('start_date'); ?>">
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="end_date"><?= lang('end_date'); ?></label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= set_value('end_date'); ?>">
        </div>
        <div class="col-sm-4">
            <button type="submit" class="nxt-btn" style="width:100%;justify-content:center"><?= lang('submit'); ?></button>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_inventory_adjustment/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1080px',
        unit: '<?= lang('movimientos'); ?>'.toLowerCase(),
        exportName: 'ajustes_inventario',
        search: ['product_name', 'tipo_mov', 'descripcion_mov', 'user', 'fecha_mov'],
        chips: { key: 'tipo_mov', all: '<?= lang('todas'); ?>' },
        columns: [
            { key: 'id_movimiento', label: '<?= lang('id'); ?>', sortable: 'num', render: function (r) { return '<span class="nxt-code">#' + NxTable.esc(r.id_movimiento) + '</span>'; } },
            { key: 'product_name', label: '<?= lang('product'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-ent-name">' + NxTable.esc(r.product_name) + '</span>'; } },
            { key: 'tipo_mov', label: '<?= lang('tipo_movimiento'); ?>', render: function (r) { return r.tipo_mov ? NxTable.badge(r.tipo_mov, 'info') : '—'; } },
            { key: 'descripcion_mov', label: '<?= lang('descripcion'); ?>', render: function (r) { return r.descripcion_mov ? '<span class="nxt-ent-meta">' + NxTable.esc(r.descripcion_mov) + '</span>' : '—'; } },
            { key: 'quantity_mov', label: '<?= lang('quantity'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.qty(r.quantity_mov) + '</span>'; } },
            { key: 'qty_fracc_mov', label: '<?= lang('fraccion'); ?>', className: 'num', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.qty_fracc_mov == null ? '—' : r.qty_fracc_mov) + '</span>'; } },
            { key: 'precio_ant', label: '<?= lang('precio_anterior'); ?>', className: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.precio_ant) + '</span>'; } },
            { key: 'precio_act', label: '<?= lang('precio_actual'); ?>', className: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.precio_act) + '</span>'; } },
            { key: 'user', label: '<?= lang('user'); ?>', render: function (r) { return r.user ? NxTable.badge(r.user, 'violet') : '—'; } },
            { key: 'fecha_mov', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.fecha_mov) + '</span>'; } }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });
});
</script>
