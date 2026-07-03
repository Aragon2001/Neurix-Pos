<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('customer')) { $v .= "&customer=" . $this->input->post('customer'); }
if ($this->input->post('user')) { $v .= "&user=" . $this->input->post('user'); }
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('sales_report'); ?>
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
    <?= form_open("reports"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-3">
            <label class="form-label" for="customer"><?= lang('customer'); ?></label>
            <?php
            $cu[0] = lang("select") . " " . lang("customer");
            foreach ($customers as $customer) { $cu[$customer->id] = $customer->name; }
            echo form_dropdown('customer', $cu, set_value('customer'), 'class="form-select" id="customer"');
            ?>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="user"><?= lang('user'); ?></label>
            <?php
            $us[""] = "";
            foreach ($users as $user) { $us[$user->id] = $user->first_name . " " . $user->last_name; }
            echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-select" id="user"');
            ?>
        </div>
        <div class="col-sm-2">
            <label class="form-label" for="start_date"><?= lang('start_date'); ?></label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= set_value('start_date'); ?>">
        </div>
        <div class="col-sm-2">
            <label class="form-label" for="end_date"><?= lang('end_date'); ?></label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= set_value('end_date'); ?>">
        </div>
        <div class="col-sm-2">
            <button type="submit" class="nxt-btn" style="width:100%;justify-content:center"><?= lang('submit'); ?></button>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<?php if ($this->input->post('customer') && isset($total_sales)) { ?>
<div class="nxt-kpis">
    <div class="nxt-kpi" style="--kpi-c:var(--nx-violet)">
        <div class="nxt-kpi-label"><?= lang('sales'); ?></div>
        <div class="nxt-kpi-value"><?= $this->tec->formatMoney($total_sales->number, 0); ?></div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-a1)">
        <div class="nxt-kpi-label"><?= lang('amount'); ?></div>
        <div class="nxt-kpi-value"><?= $this->tec->formatMoney($total_sales->amount); ?></div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-emerald)">
        <div class="nxt-kpi-label"><?= lang('paid'); ?></div>
        <div class="nxt-kpi-value"><?= $this->tec->formatMoney($total_sales->paid); ?></div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-amber)">
        <div class="nxt-kpi-label"><?= lang('due'); ?></div>
        <div class="nxt-kpi-value"><?= $this->tec->formatMoney($total_sales->amount - $total_sales->paid); ?></div>
    </div>
</div>
<?php } ?>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ST = { paid: ['<?= lang('paid'); ?>', 'ok'], partial: ['<?= lang('partial'); ?>', 'info'], due: ['<?= lang('due'); ?>', 'err'] };
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_sales/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1100px',
        unit: '<?= lang('sales'); ?>'.toLowerCase(),
        exportName: 'reporte_ventas',
        search: ['id', 'date', 'customer_name'],
        chips: { key: 'status', all: '<?= lang('todas'); ?>', label: function (v) { return (ST[v] || [v])[0]; }, sort: false },
        totals: ['total', 'total_tax', 'total_discount', 'grand_total', 'paid', 'balance'],
        columns: [
            { key: 'id', label: '<?= lang('id'); ?>', sortable: 'num', render: function (r) { return '<span class="nxt-code">#' + NxTable.esc(r.id) + '</span>'; } },
            { key: 'date', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.date) + '</span>'; } },
            { key: 'customer_name', label: '<?= lang('customer'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-ent-name">' + NxTable.esc(r.customer_name) + '</span>'; } },
            { key: 'total', label: '<?= lang('total'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.total) + '</span>'; } },
            { key: 'total_tax', label: '<?= lang('tax'); ?>', className: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.total_tax) + '</span>'; } },
            { key: 'total_discount', label: '<?= lang('discount'); ?>', className: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.total_discount) + '</span>'; } },
            { key: 'grand_total', label: '<?= lang('grand_total'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.grand_total) + '</span>'; } },
            { key: 'paid', label: '<?= lang('paid'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-offer">' + NxTable.money(r.paid) + '</span>'; } },
            { key: 'balance', label: '<?= lang('balance'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.balance) + '</span>'; } },
            { key: 'status', label: '<?= lang('status'); ?>', render: function (r) {
                var s = ST[r.status] || [r.status, 'muted'];
                return NxTable.badge(s[0], s[1]);
            }, exportValue: function (r) { return (ST[r.status] || [r.status])[0]; } }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>',
            totals: '<?= lang('total'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });
});
</script>
