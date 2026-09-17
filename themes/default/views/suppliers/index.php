<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('suppliers'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn" href="<?= site_url('suppliers/add'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('add_supplier'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ID_TYPES = { '01': 'Cédula Física', '02': 'Cédula Jurídica', '03': 'DIMEX', '04': 'NITE',
                     '05': '<?= lang('extranjero_no_domiciliado'); ?>', '06': '<?= lang('no_contribuyente'); ?>' };
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('suppliers/get_suppliers'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '880px',
        unit: '<?= lang('suppliers'); ?>'.toLowerCase(),
        exportName: 'proveedores',
        search: ['name', 'phone', 'email', 'cf2', 'actividad_economica'],
        columns: [
            { key: 'name', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) {
                return NxTable.entity(r.name, r.email || '', r.name);
            } },
            { key: 'phone', label: '<?= lang('phone'); ?>', render: function (r) {
                return r.phone ? '<span class="nxt-dim-mono">' + NxTable.esc(r.phone) + '</span>' : '—';
            } },
            { key: 'cf1', label: '<?= lang('ccf1'); ?>', render: function (r) {
                return r.cf1 ? NxTable.badge(ID_TYPES[r.cf1] || r.cf1, 'info') : '—';
            }, exportValue: function (r) { return ID_TYPES[r.cf1] || r.cf1 || ''; } },
            { key: 'cf2', label: '<?= lang('ccf2'); ?>', sortable: 'str', render: function (r) {
                return r.cf2 ? '<span class="nxt-code">' + NxTable.esc(r.cf2) + '</span>' : '—';
            } },
            { key: 'actividad_economica', label: '<?= lang('actividad_economica'); ?>', render: function (r) {
                return r.actividad_economica ? '<span class="nxt-dim-mono">' + NxTable.esc(r.actividad_economica) + '</span>' : '—';
            } },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '110px' }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_cliente_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });
});
</script>
