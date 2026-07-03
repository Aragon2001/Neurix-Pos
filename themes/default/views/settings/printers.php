<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('printers'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn" href="<?= site_url('settings/add_printer'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('add_printer'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new NxTable({
        el: '#nxtList',
        url: '<?= site_url('settings/get_printers'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '860px',
        unit: '<?= lang('printers'); ?>'.toLowerCase(),
        exportName: 'impresoras',
        search: ['title', 'type', 'path', 'ip_address'],
        columns: [
            { key: 'title', label: '<?= lang('title'); ?>', sortable: 'str', render: function (r) {
                return NxTable.entity(r.title, r.profile || '', r.title);
            } },
            { key: 'type', label: '<?= lang('type'); ?>', render: function (r) { return r.type ? NxTable.badge(r.type, 'info') : '—'; } },
            { key: 'path', label: '<?= lang('path'); ?>', render: function (r) { return r.path ? '<span class="nxt-code">' + NxTable.esc(r.path) + '</span>' : '—'; } },
            { key: 'ip_address', label: '<?= lang('ip_address'); ?>', render: function (r) {
                return r.ip_address ? '<span class="nxt-dim-mono">' + NxTable.esc(r.ip_address) + (r.port ? ':' + NxTable.esc(r.port) : '') + '</span>' : '—';
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
