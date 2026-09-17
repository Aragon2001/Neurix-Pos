<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('product')) { $v .= "&product=" . $this->input->post('product'); }
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('products_quantity'); ?>
        <small><?= lang('customize_report'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="nxt-card" style="padding:16px 18px;margin-bottom:20px">
    <?= form_open("reports/products"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" for="product"><?= lang('product'); ?></label>
            <?php
            $pr[0] = lang("select") . " " . lang("product");
            foreach ($products as $product) { $pr[$product->id] = $product->name; }
            echo form_dropdown('product', $pr, set_value('product'), 'class="form-select" id="product"');
            ?>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="start_date"><?= lang('start_date'); ?></label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= set_value('start_date'); ?>">
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="end_date"><?= lang('end_date'); ?></label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= set_value('end_date'); ?>">
        </div>
        <div class="col-sm-2">
            <button type="submit" class="nxt-btn" style="width:100%;justify-content:center"><?= lang('submit'); ?></button>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_products/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '760px',
        unit: '<?= lang('products'); ?>'.toLowerCase(),
        exportName: 'productos_cantidades',
        search: ['code', 'name'],
        totals: ['cost'],
        columns: [
            { key: 'code', label: '<?= lang('code'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-code">' + NxTable.esc(r.code) + '</span>'; } },
            { key: 'name', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-ent-name">' + NxTable.esc(r.name) + '</span>'; } },
            { key: 'quantity', label: '<?= lang('quantity'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.qty(r.quantity) + '</span>'; } },
            { key: 'cost', label: '<?= lang('cost'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.cost) + '</span>'; } }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_nombre_codigo'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>',
            totals: '<?= lang('total'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });
});
</script>
