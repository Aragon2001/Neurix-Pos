<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('actividad'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn" href="<?= site_url('settings/add_actividad'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('actividad'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new NxTable({
        el: '#nxtList',
        url: '<?= site_url('settings/get_actividad'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '520px',
        unit: '<?= lang('actividad'); ?>'.toLowerCase(),
        exportName: 'actividades_economicas',
        search: ['descripcion', 'id_actividad'],
        columns: [
            { key: 'id_actividad', label: '<?= lang('codigo'); ?>', sortable: 'num', width: '110px', render: function (r) {
                return '<span class="nxt-code">' + NxTable.esc(r.id_actividad) + '</span>';
            } },
            { key: 'descripcion', label: '<?= lang('descripcion'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-ent-name">' + NxTable.esc(r.descripcion) + '</span>';
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
