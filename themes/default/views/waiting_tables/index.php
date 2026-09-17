<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('lista_mesas'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new NxTable({
        el: '#nxtList',
        url: '<?= site_url('settings/get_waiting_tables'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '620px',
        unit: '<?= lang('lista_mesas'); ?>'.toLowerCase(),
        exportName: 'mesas',
        search: ['name', 'entry_by'],
        columns: [
            { key: 'name', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) {
                return NxTable.entity(r.name, null, r.name);
            } },
            { key: 'entry_by', label: '<?= lang('user'); ?>', render: function (r) {
                return r.entry_by ? NxTable.badge(r.entry_by, 'info') : '—';
            } },
            { key: 'status', label: '<?= lang('status'); ?>', render: function (r) {
                return r.status == '1'
                    ? NxTable.badge('<?= lang('activado'); ?>', 'ok')
                    : NxTable.badge('<?= lang('desactivado'); ?>', 'muted');
            }, exportValue: function (r) { return r.status == '1' ? '<?= lang('activado'); ?>' : '<?= lang('desactivado'); ?>'; } },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '110px' }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>'
        }
    });
});
</script>
