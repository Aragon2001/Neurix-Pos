<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('payment_ref')) { $v .= "&payment_ref=" . $this->input->post('payment_ref'); }
if ($this->input->post('sale_no')) { $v .= "&sale_no=" . $this->input->post('sale_no'); }
if ($this->input->post('customer')) { $v .= "&customer=" . $this->input->post('customer'); }
if ($this->input->post('paid_by')) { $v .= "&paid_by=" . $this->input->post('paid_by'); }
if ($this->input->post('user')) { $v .= "&user=" . $this->input->post('user'); }
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('payments_report'); ?>
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
    <?= form_open("reports/payments"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-3">
            <label class="form-label" for="payment_ref"><?= lang('payment_ref'); ?></label>
            <?= form_input('payment_ref', set_value('payment_ref'), 'class="form-control" id="payment_ref"'); ?>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="sale_no"><?= lang('sale_no'); ?></label>
            <?= form_input('sale_no', set_value('sale_no'), 'class="form-control" id="sale_no"'); ?>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="customer"><?= lang('customer'); ?></label>
            <?php
            $cu[0] = lang("select") . " " . lang("customer");
            foreach ($customers as $customer) { $cu[$customer->id] = $customer->name; }
            echo form_dropdown('customer', $cu, set_value('customer'), 'class="form-select" id="customer"');
            ?>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="user"><?= lang('created_by'); ?></label>
            <?php
            $us[""] = "";
            foreach ($users as $user) { $us[$user->id] = $user->first_name . " " . $user->last_name; }
            echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-select" id="user"');
            ?>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="paid_by"><?= lang('paid_by'); ?></label>
            <select name="paid_by" id="paid_by" class="form-select">
                <option value=""><?= lang('todas'); ?></option>
                <option value="cash"><?= lang('cash'); ?></option>
                <option value="CC"><?= lang('cc'); ?></option>
                <option value="Cheque"><?= lang('cheque'); ?></option>
                <option value="gift_card"><?= lang('gift_card'); ?></option>
                <?= isset($Settings->stripe) ? '<option value="stripe">' . lang("stripe") . '</option>' : ''; ?>
                <option value="other"><?= lang('other'); ?></option>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="start_date"><?= lang('start_date'); ?></label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= isset($_POST['start_date']) ? html_escape($_POST['start_date']) : ''; ?>">
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="end_date"><?= lang('end_date'); ?></label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= isset($_POST['end_date']) ? html_escape($_POST['end_date']) : ''; ?>">
        </div>
        <div class="col-sm-3">
            <button type="submit" class="nxt-btn" style="width:100%;justify-content:center"><?= lang('submit'); ?></button>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var PB = {
        cash: ['<?= lang('cash'); ?>', 'ok'],
        CC: ['<?= lang('CC'); ?>', 'info'],
        Cheque: ['<?= lang('Cheque'); ?>', 'violet'],
        stripe: ['<?= lang('stripe'); ?>', 'violet'],
        gift_card: ['<?= lang('gift_card'); ?>', 'orange']
    };
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_payments/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '860px',
        unit: '<?= lang('payments'); ?>'.toLowerCase(),
        exportName: 'pagos',
        search: ['date', 'ref', 'sale_no', 'paid_by'],
        chips: { key: 'paid_by', all: '<?= lang('todas'); ?>', label: function (v) { return (PB[v] || [v])[0]; }, sort: false },
        totals: ['amount'],
        columns: [
            { key: 'date', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.date) + '</span>'; } },
            { key: 'ref', label: '<?= lang('payment_ref'); ?>', render: function (r) { return r.ref ? '<span class="nxt-code">' + NxTable.esc(r.ref) + '</span>' : '—'; } },
            { key: 'sale_no', label: '<?= lang('sale_no'); ?>', render: function (r) { return r.sale_no ? '<span class="nxt-code">#' + NxTable.esc(r.sale_no) + '</span>' : '—'; } },
            { key: 'paid_by', label: '<?= lang('paid_by'); ?>', render: function (r) {
                var p = PB[r.paid_by] || [r.paid_by, 'muted'];
                return NxTable.badge(p[0], p[1]);
            }, exportValue: function (r) { return (PB[r.paid_by] || [r.paid_by])[0]; } },
            { key: 'amount', label: '<?= lang('amount'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.amount) + '</span>'; } }
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
