<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('user')) { $v .= "&user=" . $this->input->post('user'); }
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('registers_report'); ?>
        <small><?php if ($this->input->post('start_date')) { echo html_escape($this->input->post('start_date') . ' — ' . $this->input->post('end_date')); } else { echo lang('customize_report'); } ?></small>
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
    <?= form_open("reports/registers"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" for="user"><?= lang('user'); ?></label>
            <?php
            $us[""] = "";
            foreach ($users as $user) { $us[$user->id] = $user->first_name . " " . $user->last_name; }
            echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-select" id="user"');
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
    /* cash_in_hand / cc_slips / total_cheques vienen como "total (contado)" */
    function splitCell(x) {
        if (x == null || x === '') return '—';
        var y = String(x).split(' (');
        if (y.length < 2) return NxTable.esc(x);
        var real = parseFloat(y[0]) || 0;
        var counted = parseFloat(y[1]) || 0;
        var diff = real - counted;
        return '<span class="nxt-price">' + NxTable.money(real) + '</span>' +
            '<div class="nxt-ent-meta"><span style="color:var(--nx-emerald)">' + NxTable.money(counted) + '</span>' +
            ' · <span style="color:' + (diff ? 'var(--nx-err)' : 'var(--nx-txt4)') + '">' + NxTable.money(diff) + '</span></div>';
    }
    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_register_logs/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1080px',
        unit: '<?= lang('registers'); ?>'.toLowerCase(),
        exportName: 'cierres_caja',
        search: ['date', 'closed_at', 'user', 'note'],
        totals: ['total_cash'],
        columns: [
            { key: 'date', label: '<?= lang('open_date'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.date) + '</span>'; } },
            { key: 'closed_at', label: '<?= lang('close_date'); ?>', sortable: 'str', render: function (r) {
                return r.closed_at ? '<span class="nxt-dim-mono">' + NxTable.esc(r.closed_at) + '</span>' : NxTable.badge('<?= lang('open'); ?>', 'ok');
            } },
            { key: 'user', label: '<?= lang('user'); ?>', render: function (r) { return r.user ? NxTable.badge(r.user, 'info') : '—'; } },
            { key: 'cash_in_hand', label: '<?= lang('cash_in_hand'); ?>', className: 'num', render: function (r) { return splitCell(r.cash_in_hand); } },
            { key: 'cc_slips', label: '<?= lang('total_cc_slips'); ?>', className: 'num', render: function (r) { return splitCell(r.cc_slips); } },
            { key: 'total_cheques', label: '<?= lang('total_cheques'); ?>', className: 'num', render: function (r) { return splitCell(r.total_cheques); } },
            { key: 'total_cash', label: '<?= lang('total_cash'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.total_cash) + '</span>'; } },
            { key: 'note', label: '<?= lang('note'); ?>', render: function (r) { return r.note ? '<span class="nxt-ent-meta">' + NxTable.esc(r.note) + '</span>' : '—'; } },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '90px' }
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
