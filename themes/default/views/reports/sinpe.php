<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
// Los filtros viajan como query string hacia reports/get_sinpe.
$v = "?v=1";
if ($this->input->post('estado'))     { $v .= "&estado="     . urlencode($this->input->post('estado')); }
if ($this->input->post('banco'))      { $v .= "&banco="      . urlencode($this->input->post('banco')); }
if ($this->input->post('telefono'))   { $v .= "&telefono="   . urlencode($this->input->post('telefono')); }
if ($this->input->post('start_date')) { $v .= "&start_date=" . urlencode($this->input->post('start_date')); }
if ($this->input->post('end_date'))   { $v .= "&end_date="   . urlencode($this->input->post('end_date')); }

// Ids de sinpe-service/bancos.js, que es lo que se guarda en la columna `banco`.
$bancos = array(
    ''             => lang('todas'),
    'bac'          => 'BAC Credomatic',
    'bcr'          => 'Banco de Costa Rica (BCR)',
    'bn'           => 'Banco Nacional (BN)',
    'popular'      => 'Banco Popular',
    'davivienda'   => 'Davivienda',
    'promerica'    => 'Promerica',
    'scotiabank'   => 'Scotiabank',
    'lafise'       => 'Lafise',
    'coopealianza' => 'Coopealianza',
    'auto'         => lang('sinpe_banco_no_identificado'),
);
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('sinpe_report'); ?>
        <small><?= lang('sinpe_report_sub'); ?></small>
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
    <?= form_open("reports/sinpe"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-3">
            <label class="form-label" for="estado"><?= lang('status'); ?></label>
            <select name="estado" id="estado" class="form-select">
                <option value=""><?= lang('todas'); ?></option>
                <option value="pendiente" <?= $this->input->post('estado') === 'pendiente' ? 'selected' : ''; ?>><?= lang('sinpe_pendiente'); ?></option>
                <option value="usado" <?= $this->input->post('estado') === 'usado' ? 'selected' : ''; ?>><?= lang('sinpe_usado'); ?></option>
                <option value="descartado" <?= $this->input->post('estado') === 'descartado' ? 'selected' : ''; ?>><?= lang('sinpe_descartado'); ?></option>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="banco"><?= lang('sinpe_banco'); ?></label>
            <select name="banco" id="banco" class="form-select">
                <?php foreach ($bancos as $id => $nombre): ?>
                    <option value="<?= html_escape($id); ?>" <?= $this->input->post('banco') === (string)$id && $id !== '' ? 'selected' : ''; ?>><?= html_escape($nombre); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label" for="telefono"><?= lang('sinpe_telefono'); ?></label>
            <input type="text" name="telefono" id="telefono" class="form-control" value="<?= isset($_POST['telefono']) ? html_escape($_POST['telefono']) : ''; ?>">
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
    var BASE = '<?= base_url(); ?>';
    var CSRF = { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' };
    var BANCOS = <?= json_encode($bancos, JSON_UNESCAPED_UNICODE); ?>;
    var ESTADOS = {
        pendiente: ['<?= lang('sinpe_pendiente'); ?>', 'orange'],
        usado: ['<?= lang('sinpe_usado'); ?>', 'ok'],
        descartado: ['<?= lang('sinpe_descartado'); ?>', 'muted']
    };

    function nombreBanco(v) {
        if (!v) { return '—'; }
        return BANCOS[v] || v;
    }

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_sinpe/' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1040px',
        unit: 'SINPE',
        exportName: 'sinpe_movil',
        search: ['fecha', 'nombre', 'telefono', 'comprobante', 'descripcion'],
        chips: { key: 'estado', all: '<?= lang('todas'); ?>', label: function (v) { return (ESTADOS[v] || [v])[0]; }, sort: false },
        totals: ['monto'],
        columns: [
            { key: 'fecha', label: '<?= lang('date'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.fecha || '—') + '</span>';
            } },
            { key: 'nombre', label: '<?= lang('sinpe_nombre'); ?>', render: function (r) {
                return r.nombre ? NxTable.esc(r.nombre) : '<span class="text-muted">—</span>';
            } },
            { key: 'telefono', label: '<?= lang('sinpe_telefono'); ?>', render: function (r) {
                return r.telefono ? '<span class="nxt-code">' + NxTable.esc(r.telefono) + '</span>' : '—';
            } },
            { key: 'monto', label: '<?= lang('amount'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-price">' + NxTable.money(r.monto) + '</span>';
            } },
            { key: 'banco', label: '<?= lang('sinpe_banco'); ?>', render: function (r) {
                return NxTable.esc(nombreBanco(r.banco));
            }, exportValue: function (r) { return nombreBanco(r.banco); } },
            { key: 'comprobante', label: '<?= lang('sinpe_comprobante'); ?>', render: function (r) {
                return r.comprobante ? '<span class="nxt-code">' + NxTable.esc(r.comprobante) + '</span>' : '<span class="text-muted">—</span>';
            } },
            { key: 'estado', label: '<?= lang('status'); ?>', render: function (r) {
                var e = ESTADOS[r.estado] || [r.estado, 'muted'];
                return NxTable.badge(e[0], e[1]);
            }, exportValue: function (r) { return (ESTADOS[r.estado] || [r.estado])[0]; } },
            { key: 'sale_id', label: '<?= lang('sale_no'); ?>', render: function (r) {
                return r.sale_id ? '<span class="nxt-code">#' + NxTable.esc(r.sale_id) + '</span>' : '<span class="text-muted">—</span>';
            } },
            { key: 'descripcion', label: '<?= lang('sinpe_detalle'); ?>', render: function (r) {
                return r.descripcion ? NxTable.esc(r.descripcion) : '<span class="text-muted">—</span>';
            } },
            { key: 'acciones', label: '<?= lang('actions'); ?>', className: 'text-center', render: function (r) {
                var btns = '<a href="' + BASE + 'sinpe/comprobante/' + encodeURIComponent(r.id) +
                    '" target="_blank" rel="noopener" class="btn btn-xs btn-default" title="<?= lang('sinpe_imprimir'); ?>">' +
                    '<i class="fa fa-print"></i></a>';
                // Solo se puede aplicar un pago suelto y con referencia.
                if (r.estado === 'pendiente' && r.comprobante) {
                    btns += ' <button type="button" class="btn btn-xs btn-primary js-aplicar" data-id="' +
                        NxTable.esc(r.id) + '" title="<?= lang('sinpe_aplicar'); ?>">' +
                        '<i class="fa fa-link"></i></button>';
                }
                return '<div class="text-center" style="white-space:nowrap;">' + btns + '</div>';
            }, exportValue: function () { return ''; } }
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

    /* ── Aplicar un SINPE a una venta ──────────────────────────────────────
       El listado marca que ventas admiten todavia la referencia en el XML:
       un comprobante aceptado por Hacienda es inmutable. */
    function money(n) {
        return '\u20A1' + (parseFloat(n) || 0).toLocaleString('es-CR',
            { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function abrirAplicar(sinpeId) {
        fetch(BASE + 'sinpe/ventas_candidatas/' + encodeURIComponent(sinpeId), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.error) { return Swal.fire('Error', d.error, 'error'); }
                if (!d.ventas || !d.ventas.length) {
                    return Swal.fire({
                        icon: 'info',
                        title: 'Sin ventas candidatas',
                        text: 'No hay ventas de ese dia con un total que este pago alcance a cubrir y que no tengan ya un SINPE aplicado.'
                    });
                }

                var filas = d.ventas.map(function (v) {
                    var aviso = v.corregible
                        ? '<span style="color:#2ecc71;">la referencia si entra en el comprobante</span>'
                        : '<span style="color:#e67e22;">ya aceptada: la referencia no puede incorporarse</span>';
                    return '<label style="display:block;text-align:left;padding:8px;border:1px solid #ccc;' +
                        'border-radius:6px;margin-bottom:6px;cursor:pointer;">' +
                        '<input type="radio" name="nx_venta" value="' + NxTable.esc(v.id) + '"> ' +
                        '<strong>#' + NxTable.esc(v.id) + '</strong> · ' + money(v.grand_total) +
                        ' · ' + NxTable.esc(v.customer_name || '') +
                        '<br><small>' + NxTable.esc(v.date) + ' — ' + aviso + '</small></label>';
                }).join('');

                Swal.fire({
                    title: 'Aplicar ' + money(d.transaccion.monto),
                    html: '<div style="max-height:320px;overflow-y:auto;">' + filas + '</div>',
                    width: 620,
                    showCancelButton: true,
                    confirmButtonText: 'Aplicar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: function () {
                        var sel = document.querySelector('input[name=nx_venta]:checked');
                        if (!sel) { Swal.showValidationMessage('Elegi una venta'); return false; }
                        return sel.value;
                    }
                }).then(function (res) {
                    if (!res.isConfirmed) return;
                    var body = new URLSearchParams({ sinpe_id: sinpeId, sale_id: res.value });
                    body.set(CSRF.name, CSRF.hash);
                    fetch(BASE + 'sinpe/aplicar', { method: 'POST', credentials: 'same-origin', body: body })
                        .then(function (r) { return r.json(); })
                        .then(function (out) {
                            if (out.error) { return Swal.fire('No se pudo aplicar', out.error, 'error'); }
                            Swal.fire({
                                icon: out.corregible ? 'success' : 'warning',
                                title: 'Pago aplicado a la venta #' + out.sale_id,
                                text: out.aviso
                            }).then(function () { t.reload ? t.reload() : location.reload(); });
                        })
                        .catch(function () { Swal.fire('Error', 'No se pudo contactar el servidor.', 'error'); });
                });
            })
            .catch(function () { Swal.fire('Error', 'No se pudieron cargar las ventas.', 'error'); });
    }

    // Delegado: la tabla repinta sus filas al paginar u ordenar.
    document.getElementById('nxtList').addEventListener('click', function (e) {
        var btn = e.target.closest('.js-aplicar');
        if (btn) { abrirAplicar(btn.dataset.id); }
    });
});
</script>
