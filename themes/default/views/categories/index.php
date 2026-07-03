<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('categories'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn" href="<?= site_url('categories/add'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('add_category'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<div class="modal fade" id="picModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="picModalLabel"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center"><img id="product_image" src="" alt="" style="max-width:100%" /></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var BASE = '<?= base_url(); ?>';
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('categories/get_categories'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '640px',
        unit: '<?= lang('categories'); ?>'.toLowerCase(),
        exportName: 'categorias',
        search: ['code', 'name'],
        columns: [
            { key: 'name', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) {
                if (r.image && r.image !== 'no_image.png') {
                    return '<div class="nxt-ent"><div class="nxt-avatar"><img src="' + BASE + 'uploads/thumbs/' + NxTable.esc(r.image) + '" alt="" loading="lazy" class="js-zoom" data-full="' + BASE + 'uploads/' + NxTable.esc(r.image) + '" data-code="' + NxTable.esc(r.name) + '"></div><div><div class="nxt-ent-name">' + NxTable.esc(r.name) + '</div></div></div>';
                }
                return NxTable.entity(r.name, null, r.name);
            } },
            { key: 'code', label: '<?= lang('code'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-code">' + NxTable.esc(r.code) + '</span>'; } },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, className: 'num', width: '130px' }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_nombre_codigo'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });

    /* Zoom de imagen (avatar y botón lupa legacy .image) */
    document.getElementById('nxtList').addEventListener('click', function (e) {
        var z = e.target.closest('.js-zoom');
        var legacy = e.target.closest('a.image');
        if (!z && !legacy) return;
        e.preventDefault(); e.stopPropagation();
        document.getElementById('picModalLabel').textContent = z ? z.dataset.code : (legacy.id || '');
        document.getElementById('product_image').src = z ? z.dataset.full : legacy.href;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('picModal')).show();
    }, true);
});
</script>
