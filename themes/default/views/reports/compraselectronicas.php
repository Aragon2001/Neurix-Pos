<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
$v = "?v=1";
if ($this->input->post('user')) { $v .= "&user=" . $this->input->post('user'); }
if ($this->input->post('start_date')) { $v .= "&start_date=" . $this->input->post('start_date'); }
if ($this->input->post('end_date')) { $v .= "&end_date=" . $this->input->post('end_date'); }
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('informe_compras_mensuales'); ?>
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
    <?= form_open("reports/compras_electronicas"); ?>
    <div class="row g-3 align-items-end">
        <div class="col-sm-4">
            <label class="form-label" for="user"><?= lang('vendedor'); ?></label>
            <?php
            $us[""] = "";
            foreach ($users as $user) { $us[$user->id] = $user->first_name . " " . $user->last_name; }
            echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-select" id="user"');
            ?>
        </div>
        <div class="col-sm-4">
            <label class="form-label" for="start_date"><?= lang('mes'); ?></label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= set_value('start_date'); ?>">
        </div>
        <div class="col-sm-4">
            <button type="submit" class="nxt-btn" style="width:100%;justify-content:center"><?= lang('buscar'); ?></button>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<div id="nxtList"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var EST = {
        aceptado:   ['<?= lang('aceptado'); ?>', 'ok'],
        recibido:   ['<?= lang('recibido'); ?>', 'warn'],
        procesando: ['<?= lang('procesando'); ?>', 'info'],
        rechazado:  ['<?= lang('rechazado'); ?>', 'err'],
        error:      ['<?= lang('error'); ?>', 'err'],
        '5':        ['<?= lang('enviado_hacienda'); ?>', 'info']
    };
    function estLabel(v) { return (EST[v] || ['<?= lang('no_procesado'); ?>', 'muted'])[0]; }

    var MONEY = [
        ['TotalServGravados', '<?= lang('total_serv_gravados'); ?>'],
        ['TotalServExentos', '<?= lang('total_serv_exentos'); ?>'],
        ['TotalMercanciasGravadas', '<?= lang('total_merc_gravados'); ?>'],
        ['TotalMercanciasExentas', '<?= lang('total_merc_exentos'); ?>'],
        ['TotalGravado', '<?= lang('total_gravados'); ?>'],
        ['TotalExento', '<?= lang('total_exentos'); ?>'],
        ['MontoTotalImpuesto', '<?= lang('monto_total_impuesto'); ?>'],
        ['TotalVenta', '<?= lang('total_compra'); ?>'],
        ['TotalVentaNeta', '<?= lang('total_compra_neta'); ?>'],
        ['TotalFactura', '<?= lang('total_comprobante'); ?>']
    ];
    var cols = [
        { key: 'FechaEmisionDoc', label: '<?= lang('fecha_emision_label'); ?>', sortable: 'str', render: function (r) { return '<span class="nxt-dim-mono">' + NxTable.esc(r.FechaEmisionDoc) + '</span>'; } },
        { key: 'Estatus', label: '<?= lang('status_hacienda_col'); ?>', render: function (r) {
            var s = EST[r.Estatus] || ['<?= lang('no_procesado'); ?>', 'muted'];
            return NxTable.badge(s[0], s[1]);
        }, exportValue: function (r) { return estLabel(r.Estatus); } },
        { key: 'documento', label: '<?= lang('t_doc'); ?>', render: function (r) { return r.documento ? NxTable.badge(r.documento, 'violet') : '—'; } },
        { key: 'ConsecutivoDocEmisor', label: '<?= lang('n_doc'); ?>', sortable: 'str', render: function (r) { return r.ConsecutivoDocEmisor ? '<span class="nxt-code">' + NxTable.esc(r.ConsecutivoDocEmisor) + '</span>' : '—'; } },
        { key: 'nombre_emisor', label: '<?= lang('supplier'); ?>', sortable: 'str', render: function (r) {
            return '<span class="nxt-ent-name">' + NxTable.esc(r.nombre_emisor) + '</span>' +
                (r.NumeroCedulaEmisor ? '<div class="nxt-ent-meta">' + NxTable.esc(r.NumeroCedulaEmisor) + '</div>' : '');
        } },
        { key: 'correo_emisor', label: '<?= lang('email'); ?>', render: function (r) { return r.correo_emisor ? '<span class="nxt-ent-meta">' + NxTable.esc(r.correo_emisor) + '</span>' : '—'; } }
    ];
    MONEY.forEach(function (mc) {
        cols.push({ key: mc[0], label: mc[1], className: 'num', render: function (r) { return '<span class="nxt-cost">' + NxTable.money(r[mc[0]]) + '</span>'; } });
    });

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('reports/get_compras_electronicas' . $v); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '2200px',
        unit: '<?= lang('purchases'); ?>'.toLowerCase(),
        exportName: 'compras_electronicas',
        search: ['ConsecutivoDocEmisor', 'nombre_emisor', 'NumeroCedulaEmisor', 'FechaEmisionDoc', 'documento'],
        chips: { key: 'Estatus', all: '<?= lang('todas'); ?>', label: estLabel, sort: false },
        totals: MONEY.map(function (mc) { return mc[0]; }),
        map: function (r) { if (r.Estatus == null || r.Estatus === '') r.Estatus = 'noproc'; return r; },
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
});
</script>
