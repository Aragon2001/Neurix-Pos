<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('stores'); ?>
        <small><?= lang('tienda_elegir_ayuda'); ?></small>
    </div>
</div>

<div id="nxtList"></div>

<style>
    /* El logo llega apaisado (300 × 100): se muestra completo, no recortado. */
    .nxt-avatar.nxt-logo{background:var(--nx-bg3);padding:4px}
    .nxt-avatar.nxt-logo img{width:100%;height:100%;object-fit:contain;cursor:default}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var SUBIDAS = '<?= base_url('uploads/'); ?>';

    function tienda(r) {
        if (!r.logo) { return NxTable.entity(r.name, r.email || '', r.name); }
        return '<div class="nxt-ent"><div class="nxt-avatar nxt-logo"><img src="' +
            NxTable.esc(SUBIDAS + r.logo) + '" alt=""></div><div><div class="nxt-ent-name">' +
            NxTable.esc(r.name) + '</div>' +
            (r.email ? '<div class="nxt-ent-meta">' + NxTable.esc(r.email) + '</div>' : '') +
            '</div></div>';
    }

    new NxTable({
        el: '#nxtList',
        url: '<?= site_url('stores/get_stores'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '860px',
        unit: '<?= lang('stores'); ?>'.toLowerCase(),
        exportName: 'tiendas',
        search: ['name', 'code', 'phone', 'email', 'address1', 'city'],
        columns: [
            { key: 'name', label: '<?= lang('name'); ?>', sortable: 'str', render: tienda },
            { key: 'code', label: '<?= lang('code'); ?>', sortable: 'str', render: function (r) { return r.code ? '<span class="nxt-code">' + NxTable.esc(r.code) + '</span>' : '—'; } },
            { key: 'phone', label: '<?= lang('phone'); ?>', render: function (r) { return r.phone ? '<span class="nxt-dim-mono">' + NxTable.esc(r.phone) + '</span>' : '—'; } },
            { key: 'address1', label: '<?= lang('address'); ?>', render: function (r) { return r.address1 ? '<span class="nxt-ent-meta">' + NxTable.esc(r.address1) + '</span>' : '—'; } },
            { key: 'city', label: '<?= lang('city'); ?>', sortable: 'str', render: function (r) { return r.city ? NxTable.badge(r.city, 'info') : '—'; } },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '150px' }
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
