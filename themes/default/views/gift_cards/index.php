<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('gift_cards'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn" href="<?= site_url('gift_cards/add'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('add_gift_card'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('gift_cards/get_gift_cards'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '760px',
        unit: '<?= lang('gift_cards'); ?>'.toLowerCase(),
        exportName: 'tarjetas_regalo',
        search: ['card_no', 'created_by'],
        totals: ['value', 'balance'],
        columns: [
            { key: 'card_no', label: '<?= lang('card_no'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-code">' + NxTable.esc(r.card_no) + '</span>';
            } },
            { key: 'value', label: '<?= lang('value'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-cost">' + NxTable.money(r.value) + '</span>';
            } },
            { key: 'balance', label: '<?= lang('balance'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-price">' + NxTable.money(r.balance) + '</span>';
            } },
            { key: 'created_by', label: '<?= lang('user'); ?>', render: function (r) {
                return r.created_by ? NxTable.badge(r.created_by, 'info') : '—';
            } },
            { key: 'expiry', label: '<?= lang('expiry_date'); ?>', sortable: 'str', render: function (r) {
                return r.expiry ? '<span class="nxt-dim-mono">' + NxTable.esc(r.expiry) + '</span>' : '—';
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
