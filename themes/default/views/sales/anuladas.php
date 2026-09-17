<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$nxv_estados = array();
foreach (array('', 'pendiente', 'sin estado', 'procesando', 'recibido', 'aceptado',
               'aceptado parcialmente', 'rechazado', 'error', 'anulado') as $crudo) {
    $nxv_estados[$crudo] = estado_hacienda_info($crudo);
}
$nxv_estados['*'] = estado_hacienda_info('__sin_clasificar__');
$nxv_tipos = tipos_comprobante();
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('sales_anuladas'); ?>
        <small><?= lang('sales_anuladas_sub'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1-2-2v-14a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('sales'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M5 12l6 6M5 12l6-6"/></svg>
            <?= lang('sales'); ?>
        </a>
    </div>
</div>

<div class="nxt-kpis">
    <div class="nxt-kpi" style="--kpi-c:var(--nx-err)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M5.7 5.7l12.6 12.6"/></svg> <?= lang('sales_anuladas'); ?></div>
        <div class="nxt-kpi-value" id="kpiCount">—</div>
        <div class="nxt-kpi-sub">&nbsp;</div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-amber)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6l4 4l8-8"/><path d="M14 7h7v7"/></svg> <?= lang('grand_total'); ?></div>
        <div class="nxt-kpi-value" id="kpiTotal">—</div>
        <div class="nxt-kpi-sub"><?= lang('sales_anuladas_sub'); ?></div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-violet)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><rect x="2" y="6" width="20" height="12" rx="2"/></svg> <?= lang('anu_total_devuelto'); ?></div>
        <div class="nxt-kpi-value" id="kpiDevuelto">—</div>
        <div class="nxt-kpi-sub" id="kpiDevueltoCount">&nbsp;</div>
    </div>
    <div class="nxt-kpi" style="--kpi-c:var(--nx-a1)">
        <div class="nxt-kpi-label"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5h-2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-12a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg> <?= lang('anu_tipo_fiscal'); ?></div>
        <div class="nxt-kpi-value" id="kpiFiscal">—</div>
        <div class="nxt-kpi-sub"><?= lang('nxd_nota_credito'); ?></div>
    </div>
</div>

<div id="nxtList"></div>

<?php include FCPATH . 'themes/default/views/sales/_nxdoc_lang.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ESTADOS = <?= json_encode($nxv_estados, JSON_UNESCAPED_UNICODE); ?>;
    var TIPOS   = <?= json_encode($nxv_tipos, JSON_UNESCAPED_UNICODE); ?>;
    var MEDIOS  = {
        efectivo: '<?= lang('anu_medio_efectivo'); ?>',
        sinpe: '<?= lang('anu_medio_sinpe'); ?>',
        transferencia: '<?= lang('anu_medio_transferencia'); ?>',
        tarjeta: '<?= lang('anu_medio_tarjeta'); ?>'
    };

    function infoEstado(v) {
        return ESTADOS[String(v == null ? '' : v).toLowerCase()] || ESTADOS['*'];
    }

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('sales/get_anuladas'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1200px',
        unit: '<?= lang('sales_anuladas'); ?>'.toLowerCase(),
        exportName: 'facturas_anuladas',
        search: ['consecutivo', 'customer_name', 'motivo', 'anulada_por', 'fecha_anulacion', 'sale_id'],
        chips: { key: 'tipo', all: '<?= lang('todas'); ?>', sort: false, label: function (v) {
            return v === 'fiscal' ? '<?= lang('anu_tipo_fiscal'); ?>' : '<?= lang('anu_tipo_interna'); ?>';
        } },
        totals: ['grand_total', 'monto_devuelto'],
        rowAttr: function (r) { return 'data-doc="' + NxTable.esc(r.sale_id) + '"'; },
        onData: function (rows) {
            var total = 0, devuelto = 0, conDevolucion = 0, fiscales = 0;
            rows.forEach(function (r) {
                total += parseFloat(r.grand_total) || 0;
                var d = parseFloat(r.monto_devuelto) || 0;
                devuelto += d;
                if (d > 0) { conDevolucion++; }
                if (r.tipo === 'fiscal') { fiscales++; }
            });
            document.getElementById('kpiCount').textContent = rows.length;
            document.getElementById('kpiTotal').textContent = NxTable.money(total);
            document.getElementById('kpiDevuelto').textContent = NxTable.money(devuelto);
            document.getElementById('kpiDevueltoCount').textContent = conDevolucion + ' / ' + rows.length;
            document.getElementById('kpiFiscal').textContent = fiscales;
        },
        columns: [
            { key: 'consecutivo', label: '<?= lang('consecutive'); ?>', sortable: 'str', render: function (r) {
                var tipo = TIPOS[r.tipo_doc] || '';
                return (r.consecutivo
                    ? '<span class="nxt-code">' + NxTable.esc(r.consecutivo) + '</span>'
                    : '<span class="nxt-ent-meta">#' + NxTable.esc(r.sale_id) + '</span>') +
                    (tipo ? '<div class="nxt-ent-meta">' + NxTable.esc(tipo) + '</div>' : '');
            } },
            { key: 'fecha_venta', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.fecha_venta || '—') + '</span>';
            } },
            { key: 'customer_name', label: '<?= lang('customer'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-ent-name">' + NxTable.esc(r.customer_name || '—') + '</span>';
            } },
            { key: 'grand_total', label: '<?= lang('grand_total'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-price">' + NxTable.money(r.grand_total) + '</span>';
            } },
            { key: 'fecha_anulacion', label: '<?= lang('anu_ya_anulada'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.fecha_anulacion) + '</span>' +
                       '<div class="nxt-ent-meta">' + NxTable.esc(r.anulada_por || '') + '</div>';
            } },
            { key: 'motivo', label: '<?= lang('anu_motivo'); ?>', render: function (r) {
                return '<span style="color:var(--nx-txt2)">' + NxTable.esc(r.motivo || '—') + '</span>';
            } },
            { key: 'monto_devuelto', label: '<?= lang('anu_devuelto'); ?>', className: 'num', sortable: 'num', render: function (r) {
                var d = parseFloat(r.monto_devuelto) || 0;
                if (d <= 0) { return '<span style="color:var(--nx-txt4)">—</span>'; }
                return '<span class="nxt-offer">' + NxTable.money(d) + '</span>' +
                       '<div class="nxt-ent-meta">' + NxTable.esc(MEDIOS[r.medio_devolucion] || r.medio_devolucion || '') +
                       (r.cajon_abierto == 1 ? ' · <?= lang('anu_cajon_abierto'); ?>' : '') + '</div>';
            }, exportValue: function (r) { return r.monto_devuelto; } },
            { key: 'tipo', label: '<?= lang('nxd_tab_notas'); ?>', render: function (r) {
                if (r.tipo !== 'fiscal') {
                    return NxTable.badge('<?= lang('anu_tipo_interna'); ?>', 'muted');
                }
                var e = infoEstado(r.estado_nc);
                return '<span class="nxt-code">' + NxTable.esc(r.consecutivo_nc || '—') + '</span>' +
                       '<div style="margin-top:4px">' + NxTable.badge(e.etiqueta, e.tono) + '</div>';
            }, exportValue: function (r) { return r.tipo === 'fiscal' ? (r.consecutivo_nc || '') : ''; } },
            { key: 'acciones', label: '<?= lang('actions'); ?>', width: '110px', noExport: true, render: function (r) {
                return '<div class="nxt-actions">' +
                    '<button type="button" class="nxt-icon-btn js-doc" data-id="' + NxTable.esc(r.sale_id) + '" title="<?= lang('nxd_ver_detalle'); ?>">' +
                      '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6s-6.6-2-9-6c2.4-4 5.4-6 9-6s6.6 2 9 6"/></svg>' +
                    '</button>' +
                    (r.cn_id ? '<a class="nxt-icon-btn" href="<?= site_url('creditnotes/viewnc'); ?>/' + NxTable.esc(r.cn_id) + '" target="_blank" rel="noopener" title="<?= lang('anu_ver_nota'); ?>">' +
                      '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5h-2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-12a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6M9 16h4"/></svg></a>' : '') +
                    '</div>';
            } }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_venta_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('anu_sin_anuladas'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>',
            totals: '<?= lang('total'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });

    document.getElementById('nxtList').addEventListener('click', function (e) {
        var boton = e.target.closest('.js-doc');
        if (boton) { e.preventDefault(); window.NxDoc.abrir(boton.dataset.id, { alCambiar: function () { t.reload(); } }); return; }
        if (e.target.closest('a, button, input, select')) { return; }
        var fila = e.target.closest('tr[data-doc]');
        if (fila) { window.NxDoc.abrir(fila.dataset.doc, { alCambiar: function () { t.reload(); } }); }
    });
});
</script>
