<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('product_alerts'); ?>
        <small><?= html_escape($store->name); ?> · <?= lang('requieren_reorden'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn" href="<?= site_url('purchases/add'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('add_purchase'); ?>
        </a>
    </div>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var IS_ADMIN = <?= $Admin ? 'true' : 'false'; ?>;
    var BASE = '<?= base_url(); ?>';
    var TYPES = { standard: '<?= lang('standard'); ?>', combo: '<?= lang('combo'); ?>', service: '<?= lang('service'); ?>' };

    var cols = [
        { key: 'pname', label: '<?= lang('product'); ?>', sortable: 'str', render: function (r) {
            if (r.image && r.image !== 'no_image.png') {
                return '<div class="nxt-ent"><div class="nxt-avatar"><img src="' + BASE + 'uploads/thumbs/' + NxTable.esc(r.image) + '" alt="" loading="lazy"></div><div><div class="nxt-ent-name">' + NxTable.esc(r.pname) + '</div><div class="nxt-ent-meta">' + NxTable.esc(TYPES[r.type] || r.type) + '</div></div></div>';
            }
            return NxTable.entity(r.pname, TYPES[r.type] || r.type, r.cname);
        } },
        { key: 'code', label: '<?= lang('code'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-code">' + NxTable.esc(r.code) + '</span>'; } },
        { key: 'cname', label: '<?= lang('category'); ?>', render: function (r) { return r.cname ? NxTable.badge(r.cname, 'info') : '—'; } },
        { key: 'quantity', label: '<?= lang('quantity'); ?>', className: 'num', sortable: 'num', render: function (r) {
            var qty = parseFloat(r.quantity) || 0;
            var min = parseFloat(r.alert_quantity) || 0;
            var st = qty <= 0 ? 's-out' : 's-low';
            var lbl = qty <= 0 ? '<?= lang('agotado'); ?>' : '<?= lang('stock_bajo_tag'); ?>';
            var pct = min > 0 ? Math.max(6, Math.min(100, qty / (min * 3) * 100)) : 0;
            return '<div class="nxt-stock ' + st + '"><div class="nxt-stock-top"><span class="nxt-stock-n">' + NxTable.qty(qty) + '</span><span class="nxt-stock-state">' + lbl + '</span></div><div class="nxt-bar"><i style="width:' + pct + '%"></i></div></div>';
        } },
        { key: 'alert_quantity', label: '<?= lang('alert_quantity'); ?>', className: 'num', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.qty(r.alert_quantity) + '</span>'; } }
    ];
    if (IS_ADMIN) {
        cols.push({ key: 'cost', label: '<?= lang('cost'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.cost) + '</span>'; } });
    }
    cols.push({ key: 'price', label: '<?= lang('price'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.price) + '</span>'; } });
    cols.push({ key: 'Actions', label: '<?= lang('actions'); ?>', noExport: true, render: function (r) {
        return '<div class="nxt-actions"><a href="#" class="nxt-icon-btn js-ap" data-id="' + NxTable.esc(r.id) + '" title="<?= lang('add_to_purcahse_order'); ?>"><i class="fa fa-plus"></i></a></div>';
    } });

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_alerts'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1000px',
        unit: '<?= lang('products'); ?>'.toLowerCase(),
        exportName: 'alertas_stock',
        search: ['code', 'pname', 'cname'],
        chips: { key: 'cname', all: '<?= lang('todas'); ?>' },
        columns: cols,
        i18n: {
            searchPlaceholder: '<?= lang('buscar_nombre_codigo'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });

    /* Agregar a orden de compra (spoitems en localStorage, igual que purchases/add) */
    document.getElementById('nxtList').addEventListener('click', function (e) {
        var a = e.target.closest('.js-ap');
        if (!a) return;
        e.preventDefault();
        fetch('<?= site_url('purchases/suggestions'); ?>/' + a.dataset.id, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (item) {
                var S = window._appSettings || {};
                var spoitems = {};
                try { spoitems = JSON.parse(localStorage.getItem('spoitems')) || {}; } catch (err) {}
                var item_id = S.item_addition == 1 ? item.item_id : item.id;
                if (spoitems[item_id]) {
                    spoitems[item_id].row.qty = parseFloat(spoitems[item_id].row.qty) + 1;
                } else {
                    spoitems[item_id] = item;
                }
                localStorage.setItem('spoitems', JSON.stringify(spoitems));
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: '<?= lang('po_item_added'); ?> ' + spoitems[item_id].label + ' = ' + spoitems[item_id].row.qty, showConfirmButton: false, timer: 2500 });
            });
    });
});
</script>
