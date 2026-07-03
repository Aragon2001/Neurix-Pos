<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('sales'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn" href="<?= site_url('pos'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            <?= lang('nueva_venta'); ?>
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="nxt-kpis">
    <div class="nxt-kpi" style="--kpi-c:var(--nx-a1)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21v-16a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/></svg> <?= lang('sales'); ?></div>
        <div class="nxt-kpi-value" id="kpiCount">—</div>
        <div class="nxt-kpi-sub">&nbsp;</div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-emerald)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6l4 4l8-8"/><path d="M14 7h7v7"/></svg> <?= lang('grand_total'); ?></div>
        <div class="nxt-kpi-value" id="kpiTotal">—</div>
        <div class="nxt-kpi-sub" id="kpiPaid">&nbsp;</div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-amber)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg> <?= lang('saldo_pendiente'); ?></div>
        <div class="nxt-kpi-value" id="kpiDue">—</div>
        <div class="nxt-kpi-sub" id="kpiDueCount">&nbsp;</div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-err)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.24 3.96l-8.13 14.05a2 2 0 0 0 1.73 3h16.32a2 2 0 0 0 1.73-3l-8.13-14.05a2 2 0 0 0-3.52 0z"/></svg> <?= lang('hacienda_pendientes'); ?></div>
        <div class="nxt-kpi-value" id="kpiHac">—</div>
        <div class="nxt-kpi-sub"><?= lang('sin_aceptar_hacienda'); ?></div>
    </div>
</div>

<div id="nxtList"></div>

<?php if ($Admin) { ?>
<div class="modal fade" id="stModal" tabindex="-1" aria-labelledby="stModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="stModalLabel"><?= lang('update_status'); ?> <span id="status-id"></span></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <?= form_open('sales/status'); ?>
            <div class="modal-body">
                <input type="hidden" value="" id="sale_id" name="sale_id" />
                <div class="mb-3">
                    <?= lang('status', 'status'); ?>
                    <?php $opts = array('paid' => lang('paid'), 'partial' => lang('partial'), 'due' => lang('due')); ?>
                    <?= form_dropdown('status', $opts, set_value('status'), 'class="form-select" id="status" required="required"'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?= lang('update'); ?></button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
<?php } ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var IS_ADMIN = <?= $Admin ? 'true' : 'false'; ?>;
    var ST = { paid: ['<?= lang('paid'); ?>', 'ok'], partial: ['<?= lang('partial'); ?>', 'info'], due: ['<?= lang('due'); ?>', 'err'] };
    var HAC = {
        aceptado:   ['Aceptado', 'ok'],
        recibido:   ['Recibido', 'info'],
        procesando: ['Procesando', 'info'],
        rechazado:  ['Rechazado', 'err'],
        anulado:    ['Anulado', 'muted'],
        error:      ['Error', 'err']
    };
    function hacLabel(v) { return (HAC[v] || ['No Enviado', 'warn'])[0]; }

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('sales/get_sales'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1280px',
        unit: '<?= lang('sales'); ?>'.toLowerCase(),
        exportName: 'ventas',
        search: ['consecutivo', 'customer_name', 'date'],
        chips: { key: 'estatus_hacienda', all: '<?= lang('todas'); ?>', label: hacLabel, sort: false },
        totals: ['total', 'total_tax', 'total_discount', 'grand_total', 'paid'],
        map: function (r) { if (r.estatus_hacienda == null || r.estatus_hacienda === '') r.estatus_hacienda = 'noenviado'; return r; },
        onData: function (rows) {
            var total = 0, paid = 0, due = 0, dueCount = 0, hac = 0;
            rows.forEach(function (r) {
                var g = parseFloat(r.grand_total) || 0, p = parseFloat(r.paid) || 0;
                total += g; paid += p;
                if (r.status !== 'paid') { due += (g - p); dueCount++; }
                if (r.estatus_hacienda !== 'aceptado' && r.estatus_hacienda !== 'anulado') hac++;
            });
            document.getElementById('kpiCount').textContent = rows.length;
            document.getElementById('kpiTotal').textContent = NxTable.money(total);
            document.getElementById('kpiPaid').textContent = '<?= lang('paid'); ?>: ' + NxTable.money(paid);
            document.getElementById('kpiDue').textContent = NxTable.money(due);
            document.getElementById('kpiDueCount').textContent = dueCount + ' <?= lang('facturas_pendientes'); ?>';
            document.getElementById('kpiHac').textContent = hac;
        },
        columns: [
            { key: 'consecutivo', label: '<?= lang('consecutive'); ?>', sortable: 'str', render: function (r) {
                return r.consecutivo ? '<span class="nxt-code">' + NxTable.esc(r.consecutivo) + '</span>' : '—';
            } },
            { key: 'date', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.date) + '</span>';
            } },
            { key: 'customer_name', label: '<?= lang('customer'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-ent-name">' + NxTable.esc(r.customer_name) + '</span>';
            } },
            { key: 'total', label: '<?= lang('total'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.total) + '</span>'; } },
            { key: 'total_tax', label: '<?= lang('tax'); ?>', className: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.total_tax) + '</span>'; } },
            { key: 'total_discount', label: '<?= lang('discount'); ?>', className: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r.total_discount) + '</span>'; } },
            { key: 'grand_total', label: '<?= lang('grand_total'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-price">' + NxTable.money(r.grand_total) + '</span>'; } },
            { key: 'paid', label: '<?= lang('paid'); ?>', className: 'num', sortable: 'num', render: function (r) { return '<span class="nxt-offer">' + NxTable.money(r.paid) + '</span>'; } },
            { key: 'status', label: '<?= lang('status'); ?>', render: function (r) {
                var s = ST[r.status] || [r.status, 'muted'];
                var b = NxTable.badge(s[0], s[1]);
                return IS_ADMIN ? '<a href="#" class="js-status" data-id="' + NxTable.esc(r.id) + '" data-status="' + NxTable.esc(r.status) + '" style="text-decoration:none">' + b + '</a>' : b;
            }, exportValue: function (r) { return (ST[r.status] || [r.status])[0]; } },
            { key: 'estatus_hacienda', label: '<?= lang('estado_hacienda'); ?>', render: function (r) {
                var h = HAC[r.estatus_hacienda] || ['No Enviado', 'warn'];
                return NxTable.badge(h[0], h[1]);
            }, exportValue: function (r) { return hacLabel(r.estatus_hacienda); } },
            { key: 'status_hacienda', label: 'XML', actions: true },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '150px' }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_venta_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>',
            totals: '<?= lang('total'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });

    <?php if ($Admin) { ?>
    /* Cambio de estado de pago (click en badge) */
    document.getElementById('nxtList').addEventListener('click', function (e) {
        var a = e.target.closest('.js-status');
        if (!a) return;
        e.preventDefault();
        document.getElementById('status-id').textContent = '( <?= lang('sale_id'); ?> ' + a.dataset.id + ' )';
        document.getElementById('sale_id').value = a.dataset.id;
        document.getElementById('status').value = a.dataset.status;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('stModal')).show();
    });
    <?php } ?>
});
</script>
