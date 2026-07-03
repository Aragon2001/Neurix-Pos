<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('notas_debito_electronicas'); ?>
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
    var HAC = {
        aceptado:   ['Aceptado', 'ok'],
        recibido:   ['Recibido', 'info'],
        procesando: ['Procesando', 'info'],
        rechazado:  ['Rechazado', 'err'],
        anulado:    ['Anulado', 'muted'],
        error:      ['Error', 'err']
    };
    function hacLabel(v) { return (HAC[v] || ['No Enviado', 'warn'])[0]; }

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('debitnotes/get_debitnotes'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1040px',
        unit: '<?= lang('notas_debito_electronicas'); ?>'.toLowerCase(),
        exportName: 'notas_debito',
        search: ['consecutivo', 'customer_name', 'date', 'hold_ref'],
        chips: { key: 'estatus_hacienda', all: '<?= lang('todas'); ?>', label: hacLabel, sort: false },
        totals: ['grand_total'],
        map: function (r) { if (r.estatus_hacienda == null || r.estatus_hacienda === '') r.estatus_hacienda = 'noenviado'; return r; },
        columns: [
            { key: 'consecutivo', label: '<?= lang('consecutive'); ?>', sortable: 'str', render: function (r) {
                return r.consecutivo ? '<span class="nxt-code">' + NxTable.esc(r.consecutivo) + '</span>' : '—';
            } },
            { key: 'date', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.date) + '</span>';
            } },
            { key: 'customer_name', label: '<?= lang('customer'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-ent-name">' + NxTable.esc(r.customer_name) + '</span>';
            } },
            { key: 'grand_total', label: '<?= lang('total'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-price">' + NxTable.money(r.grand_total) + '</span>';
            } },
            { key: 'hold_ref', label: '<?= lang('razon'); ?>', render: function (r) {
                return r.hold_ref ? '<span class="nxt-ent-meta">' + NxTable.esc(r.hold_ref) + '</span>' : '—';
            } },
            { key: 'estatus_hacienda', label: '<?= lang('estado_hacienda'); ?>', render: function (r) {
                var h = HAC[r.estatus_hacienda] || ['No Enviado', 'warn'];
                return NxTable.badge(h[0], h[1]);
            }, exportValue: function (r) { return hacLabel(r.estatus_hacienda); } },
            { key: 'xmls', label: '<?= lang('xmls'); ?>', actions: true },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '130px' }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_venta_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>',
            totals: '<?= lang('total'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });
});
</script>
