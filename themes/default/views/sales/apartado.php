<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('apartados'); ?>
        <small><?= lang('list_results'); ?></small>
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
    var ST = { paid: ['<?= lang('paid'); ?>', 'ok'], partial: ['<?= lang('partial'); ?>', 'info'], due: ['<?= lang('due'); ?>', 'err'] };
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('sales/get_apartado'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1000px',
        unit: '<?= lang('apartados'); ?>'.toLowerCase(),
        exportName: 'apartados',
        search: ['id', 'date', 'customer_name'],
        chips: { key: 'status', all: '<?= lang('todas'); ?>', label: function (v) { return (ST[v] || [v])[0]; }, sort: false },
        totals: ['total', 'total_tax', 'grand_total', 'paid'],
        columns: [
            { key: 'id', label: '<?= lang('num_apartado'); ?>', sortable: 'num', render: function (r) {
                return '<span class="nxt-code">#' + NxTable.esc(r.id) + '</span>';
            } },
            { key: 'date', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.date) + '</span>';
            } },
            { key: 'customer_name', label: '<?= lang('customer'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-ent-name">' + NxTable.esc(r.customer_name) + '</span>';
            } },
            { key: 'total', label: '<?= lang('total'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.total) + '</span>'; } },
            { key: 'total_tax', label: '<?= lang('tax'); ?>', className: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.total_tax) + '</span>'; } },
            { key: 'grand_total', label: '<?= lang('grand_total'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.grand_total) + '</span>'; } },
            { key: 'paid', label: '<?= lang('paid'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-offer">' + NxTable.money(r.paid) + '</span>'; } },
            { key: 'status', label: '<?= lang('status'); ?>', render: function (r) {
                var s = ST[r.status] || [r.status, 'muted'];
                return NxTable.badge(s[0], s[1]);
            }, exportValue: function (r) { return (ST[r.status] || [r.status])[0]; } },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '150px' }
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
