<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('expenses'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn" href="<?= site_url('purchases/add_expense'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('add_expense'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var BASE = '<?= base_url(); ?>';
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('purchases/get_expenses'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '880px',
        unit: '<?= lang('expenses'); ?>'.toLowerCase(),
        exportName: 'gastos',
        search: ['date', 'reference', 'note', 'user'],
        totals: ['amount'],
        columns: [
            { key: 'date', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.date) + '</span>';
            } },
            { key: 'reference', label: '<?= lang('reference'); ?>', sortable: 'str', render: function (r) {
                return r.reference ? '<span class="nxt-code">' + NxTable.esc(r.reference) + '</span>' : '—';
            } },
            { key: 'amount', label: '<?= lang('amount'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-price">' + NxTable.money(r.amount) + '</span>';
            } },
            { key: 'note', label: '<?= lang('note'); ?>', render: function (r) {
                return r.note ? '<span class="nxt-ent-meta">' + NxTable.esc(r.note) + '</span>' : '—';
            } },
            { key: 'user', label: '<?= lang('user'); ?>', render: function (r) {
                return r.user ? NxTable.badge(r.user, 'info') : '—';
            } },
            { key: 'attachment', label: '<?= lang('attachment'); ?>', noExport: true, render: function (r) {
                return r.attachment
                    ? '<a class="nxt-icon-btn" target="_blank" href="' + BASE + 'uploads/' + NxTable.esc(r.attachment) + '" title="<?= lang('attachment'); ?>"><i class="fa fa-chain"></i></a>'
                    : '';
            } },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '110px' }
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
