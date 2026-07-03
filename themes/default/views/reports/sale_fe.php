<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('customer')) { $v .= "&customer=" . $this->input->post('customer'); }
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('customize_report'); ?>
        <small><?= lang('sales'); ?> FE</small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function m(k) { return function (r) { return '<span class="nxt-cost">' + NxTable.money(r[k]) + '</span>'; }; }
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_sale_fe/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1100px',
        unit: '<?= lang('customers'); ?>'.toLowerCase(),
        exportName: 'ventas_fe',
        search: ['name'],
        totals: ['tax_0', 'tax_1', 'tax_2', 'tax_4', 'tax_13', 'exonerado', 'subtotal', 'total'],
        columns: [
            { key: 'name', label: '<?= lang('customer'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-ent-name">' + NxTable.esc(r.name) + '</span>'; } },
            { key: 'tax_0', label: '<?= lang('tot_imp_0_cobrado'); ?>', className: 'num', render: m('tax_0') },
            { key: 'tax_1', label: '<?= lang('tot_imp_1_cobrado'); ?>', className: 'num', render: m('tax_1') },
            { key: 'tax_2', label: '<?= lang('tot_imp_2_cobrado'); ?>', className: 'num', render: m('tax_2') },
            { key: 'tax_4', label: '<?= lang('tot_imp_4_cobrado'); ?>', className: 'num', render: m('tax_4') },
            { key: 'tax_13', label: '<?= lang('tot_imp_13_cobrado'); ?>', className: 'num', render: m('tax_13') },
            { key: 'exonerado', label: '<?= lang('total_ventas_exoneradas'); ?>', className: 'num', render: m('exonerado') },
            { key: 'subtotal', label: '<?= lang('tot_monto_sin_imp'); ?>', className: 'num', sortable: 'num', render: m('subtotal') },
            { key: 'total', label: '<?= lang('tot_monto_con_imp'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.total) + '</span>'; } }
        ],
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
