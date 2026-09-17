<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('shipping_method'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn" href="<?= site_url('settings/add_shipping'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('shipping_method'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new NxTable({
        el: '#nxtList',
        url: '<?= site_url('settings/get_shipping'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '520px',
        unit: '<?= lang('shipping_method'); ?>'.toLowerCase(),
        exportName: 'metodos_envio',
        search: ['name'],
        columns: [
            { key: 'id_shipping_method', label: '<?= lang('id'); ?>', sortable: 'num', width: '70px', render: function (r) {
                return '<span class="nxt-code">#' + NxTable.esc(r.id_shipping_method) + '</span>';
            } },
            { key: 'name', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) {
                return NxTable.entity(r.name, null, r.name);
            } },
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
