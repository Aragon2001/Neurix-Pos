<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('purchases'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn" href="<?= site_url('purchases/add'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('add_purchase'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($this->session->userdata('remove_spo')) { $this->tec->unset_data('remove_spo'); ?>
    try { if (localStorage.getItem('spoitems')) localStorage.removeItem('spoitems'); localStorage.removeItem('remove_spo'); } catch (e) {}
    <?php } ?>
    var BASE = '<?= base_url(); ?>';
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('purchases/get_purchases'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '860px',
        unit: '<?= lang('purchases'); ?>'.toLowerCase(),
        exportName: 'compras',
        search: ['date', 'reference', 'note'],
        totals: ['total'],
        columns: [
            { key: 'date', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.date) + '</span>';
            } },
            { key: 'reference', label: '<?= lang('reference'); ?>', sortable: 'str', render: function (r) {
                return r.reference ? '<span class="nxt-code">' + NxTable.esc(r.reference) + '</span>' : '—';
            } },
            { key: 'total', label: '<?= lang('total'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-price">' + NxTable.money(r.total) + '</span>';
            } },
            { key: 'note', label: '<?= lang('note'); ?>', render: function (r) {
                return r.note ? '<span class="nxt-ent-meta">' + NxTable.esc(r.note) + '</span>' : '—';
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
