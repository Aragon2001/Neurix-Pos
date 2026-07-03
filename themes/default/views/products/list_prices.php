<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

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
