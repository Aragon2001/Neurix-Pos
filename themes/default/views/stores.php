<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('stores'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new NxTable({
        el: '#nxtList',
        url: '<?= site_url('stores/get_stores'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '860px',
        unit: '<?= lang('stores'); ?>'.toLowerCase(),
        exportName: 'tiendas',
        search: ['name', 'code', 'phone', 'email', 'address1', 'city'],
        columns: [
            { key: 'name', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) {
                return NxTable.entity(r.name, r.email || '', r.name);
            } },
            { key: 'code', label: '<?= lang('code'); ?>', render: function (r) { return r.code ? '<span class="nxt-code">' + NxTable.esc(r.code) + '</span>' : '—'; } },
            { key: 'phone', label: '<?= lang('phone'); ?>', render: function (r) { return r.phone ? '<span class="nxt-dim-mono">' + NxTable.esc(r.phone) + '</span>' : '—'; } },
            { key: 'address1', label: '<?= lang('address'); ?>', render: function (r) { return r.address1 ? '<span class="nxt-ent-meta">' + NxTable.esc(r.address1) + '</span>' : '—'; } },
            { key: 'city', label: '<?= lang('city'); ?>', render: function (r) { return r.city ? NxTable.badge(r.city, 'info') : '—'; } },
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
