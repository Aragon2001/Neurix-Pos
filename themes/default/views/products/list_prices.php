<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('lista_precios'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn" href="<?= site_url('products/add_list_prices'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('lista_precios'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new NxTable({
        el: '#nxtList',
        url: '<?= site_url('products/get_list_prices'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '640px',
        unit: '<?= lang('lista_precios'); ?>'.toLowerCase(),
        exportName: 'listas_precios',
        search: ['nombre_l_precio', 'entry_by'],
        columns: [
            { key: 'nombre_l_precio', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) {
                return NxTable.entity(r.nombre_l_precio, null, r.nombre_l_precio);
            } },
            { key: 'entry_by', label: '<?= lang('user'); ?>', render: function (r) {
                return r.entry_by ? NxTable.badge(r.entry_by, 'info') : '—';
            } },
            { key: 'status_l_precio', label: '<?= lang('status'); ?>', render: function (r) {
                return r.status_l_precio == '1'
                    ? NxTable.badge('<?= lang('activado'); ?>', 'ok')
                    : NxTable.badge('<?= lang('desactivado'); ?>', 'muted');
            }, exportValue: function (r) { return r.status_l_precio == '1' ? '<?= lang('activado'); ?>' : '<?= lang('desactivado'); ?>'; } },
            { key: 'id_lista_precios', label: '<?= lang('actions'); ?>', width: '110px', render: function (r) {
                return '<div class="nxt-acciones">'
                     + '<a class="nxt-ico" href="<?= site_url('products/editprices'); ?>/' + r.id_lista_precios + '" title="<?= lang('edit'); ?>">&#9998;</a>'
                     + '<button type="button" class="nxt-ico peli" data-borrar="' + r.id_lista_precios + '" title="<?= lang('delete'); ?>">&times;</button>'
                     + '</div>';
            } }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>'
        }
    });

    // Borrar cambia estado: va por POST y con el testigo CSRF, nunca por enlace.
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-borrar]');
        if (!b) { return; }
        if (!confirm('<?= lang('alert_x_lista_precio'); ?>')) { return; }

        var f = document.createElement('form');
        f.method = 'post';
        f.action = '<?= site_url('products/deleteprices'); ?>';
        f.innerHTML = '<input type="hidden" name="id" value="' + b.dataset.borrar + '">'
                    + '<input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">';
        document.body.appendChild(f);
        f.submit();
    });
});
</script>

<style>
    .nxt-acciones{display:flex;gap:6px;justify-content:center}
    .nxt-ico{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:7px;
        border:1px solid var(--nxf-border,#2a3444);background:transparent;color:inherit;cursor:pointer;text-decoration:none}
    .nxt-ico:hover{background:rgba(56,189,248,.12)}
    .nxt-ico.peli:hover{background:rgba(248,113,113,.15);color:#f87171}
</style>
