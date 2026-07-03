<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
if ($this->input->post('shipping_method')) { $v .= "&shipping_method=" . $this->input->post('shipping_method'); }
$has_method = (bool) $this->input->post('shipping_method');
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('creditos_envios'); ?>
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
    <?= form_open("reports/credit_shipping"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" for="shipping_method"><?= lang('shipping_method'); ?></label>
            <?php
            $sh[''] = lang("select") . " " . lang("shipping_method");
            foreach ($shipping as $ship) { $sh[$ship->id_shipping_method] = $ship->name; }
            echo form_dropdown('shipping_method', $sh, set_value('shipping_method'), 'class="form-select" id="shipping_method"');
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

<?php if ($has_method && isset($total_sales)) { ?>
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
<input type="hidden" id="amount_due" value="<?= $total_sales->amount - $total_sales->paid ?>">
<?php } ?>

<div id="nxtList" style="margin-bottom:20px"></div>

<?php if ($has_method && isset($total_sales)) { ?>
<!-- Pago de deuda -->
<div class="nxt-card" style="padding:16px 18px">
    <h5 style="margin-bottom:14px;color:var(--nx-txt1)"><?= lang('pago_deuda_form'); ?></h5>
    <?= form_open("reports/credit_shipping/"); ?>
    <?= form_hidden("shipping_method", $this->input->post('shipping_method')) ?>
    <?= form_hidden("start_date", $this->input->post('start_date')) ?>
    <?= form_hidden("end_date", $this->input->post('end_date')) ?>
    <input type="hidden" id="sales_id" name="sales_id">
    <div class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" for="amount"><?= lang('amount'); ?></label>
            <input name="amount-paid" type="text" id="amount"
                   value="<?= $total_sales->amount - $total_sales->paid ?>"
                   class="form-control" required="required"/>
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="paid_by"><?= lang('paying_by'); ?></label>
            <select name="paid_by" id="paid_by" class="form-select" required="required">
                <option value="cash"><?= lang('cash'); ?></option>
                <option value="CC"><?= lang('tarjeta'); ?></option>
                <option value="Cheque"><?= lang('cheque'); ?></option>
                <option value="deposito"><?= lang('deposito'); ?></option>
            </select>
        </div>
        <div class="col-sm-4 pcc" style="display:none;">
            <label class="form-label" for="pcc_type"><?= lang('card_type'); ?></label>
            <select name="pcc_type" id="pcc_type" class="form-select">
                <option value="Debito"><?= lang('debito'); ?></option>
                <option value="Visa"><?= lang('Visa'); ?></option>
                <option value="MasterCard"><?= lang('MasterCard'); ?></option>
            </select>
            <input name="pcc_no" type="hidden" id="pcc_no"/>
            <input name="pcc_holder" type="hidden" id="pcc_holder"/>
            <input name="pcc_month" type="hidden" id="pcc_month"/>
            <input name="pcc_year" type="hidden" id="pcc_year"/>
            <input name="pcc_ccv" type="hidden" id="pcc_cvv2"/>
        </div>
        <div class="col-sm-4 pcheque" style="display:none;">
            <label class="form-label" for="cheque_no"><?= lang('cheque_no'); ?></label>
            <input name="cheque_no" type="text" id="cheque_no" class="form-control"/>
        </div>
        <div class="col-sm-4">
            <button type="submit" class="nxt-btn" style="width:100%;justify-content:center"><?= lang('pagar_deuda'); ?></button>
        </div>
    </div>
    <?= form_close(); ?>
</div>
<?php } ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var HAS_METHOD = <?= $has_method ? 'true' : 'false'; ?>;
    var ST = { paid: ['<?= lang('paid'); ?>', 'ok'], partial: ['<?= lang('partial'); ?>', 'info'], due: ['<?= lang('due'); ?>', 'err'] };

    var cols = [];
    if (HAS_METHOD) {
        cols.push({ key: 'Actions', label: '<?= lang('pagar'); ?>', noExport: true, render: function (r) {
            /* el id del pedido viene embebido en el checkbox generado por el servidor;
               el balance se adjunta como data para sumar montos sin leer celdas vecinas */
            var m = String(r.Actions || '').match(/id='([^']+)'|id="([^"]+)"/);
            var id = m ? (m[1] || m[2]) : '';
            return '<input type="checkbox" class="form-check-input js-paysel" data-id="' + NxTable.esc(id) + '" data-balance="' + (parseFloat(r.balance) || 0) + '">';
        } });
    }
    cols = cols.concat([
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
    ]);

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_credit_shipping/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1100px',
        unit: '<?= lang('sales'); ?>'.toLowerCase(),
        exportName: 'creditos_envios',
        search: ['date', 'customer_name'],
        totals: ['total', 'total_tax', 'total_discount', 'grand_total', 'paid', 'balance'],
        columns: cols,
        i18n: {
            searchPlaceholder: '<?= lang('buscar_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>',
            totals: '<?= lang('total'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });

    /* Selección de pedidos a pagar: suma balances y arma sales_id */
    var amountInput = document.getElementById('amount');
    var salesIdInput = document.getElementById('sales_id');
    var amountDue = document.getElementById('amount_due');
    document.getElementById('nxtList').addEventListener('change', function (e) {
        var cb = e.target.closest('.js-paysel');
        if (!cb || !amountInput) return;
        var checked = document.querySelectorAll('.js-paysel:checked');
        var sum = 0, ids = '';
        checked.forEach(function (c) { sum += parseFloat(c.dataset.balance) || 0; ids += c.dataset.id + ';'; });
        if (checked.length) {
            amountInput.value = sum.toFixed(4);
            amountInput.setAttribute('readonly', 'readonly');
            salesIdInput.value = ids;
        } else {
            amountInput.removeAttribute('readonly');
            salesIdInput.value = '';
        }
    });
    if (amountInput && amountDue) {
        amountInput.addEventListener('change', function () {
            if (parseFloat(this.value) > parseFloat(String(amountDue.value).replace(',', ''))) {
                Swal.fire({ icon: 'warning', text: 'El monto ingresado supera al balance total' });
                this.value = 0; this.focus();
            }
        });
    }

    /* Mostrar campos según método de pago */
    var pb = document.getElementById('paid_by');
    if (pb) {
        pb.addEventListener('change', function () {
            document.querySelectorAll('.pcc').forEach(function (e) { e.style.display = pb.value === 'CC' ? '' : 'none'; });
            document.querySelectorAll('.pcheque').forEach(function (e) { e.style.display = pb.value === 'Cheque' ? '' : 'none'; });
        });
    }
});
</script>
