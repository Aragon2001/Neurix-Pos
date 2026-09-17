<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) or exit('No direct script access allowed');

$js = 'themes/default/assets/dist/js/nx-reportes.js';
$v  = is_file(FCPATH . $js) ? filemtime(FCPATH . $js) : time();
?>
<script src="<?= base_url($js); ?>?v=<?= $v; ?>"></script>

<div class="nxr-head">
    <div>
        <h1>Centro de Inteligencia</h1>
        <p id="nxrSub">Indicadores, tendencias y estado de integridad del período.</p>
    </div>
    <div class="nxr-acciones">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('reportes/diccionario'); ?>">Diccionario de datos</a>
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('reportes/bitacora'); ?>">Bitácora</a>
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('reportes/auditoria'); ?>">Auditoría Integral</a>
        <button type="button" class="nxt-btn" id="nxrMaestro"
                title="PDF consolidado del período, con semáforos y anomalías">Informe maestro</button>
    </div>
</div>

<?php $this->load->view($this->theme . 'reportes/_filtros', array('catalogos' => $catalogos, 'ambitos' => $ambitos)); ?>

<div id="nxrKpis"></div>
<div id="nxrConf"></div>
<div id="nxrGrafs" class="nxr-grafs"></div>

<h4 class="nxr-grupo" style="display:block;margin:20px 0 8px">Informes disponibles</h4>
<div class="nxr-tabs">
    <?php
    $grupo = '';
    foreach ($catalogo as $k => $c):
        if ($c['grupo'] !== $grupo) {
            $grupo = $c['grupo'];
            echo '<span class="nxr-grupo">' . html_escape($grupo) . '</span>';
        }
        ?>
        <a class="nxr-tab" href="<?= site_url('reportes/ver/' . $k); ?>"><?= html_escape($c['titulo']); ?></a>
    <?php endforeach; ?>
</div>

<h4 class="nxr-grupo" style="display:block;margin:20px 0 8px">Anomalías más relevantes del período</h4>
<div id="nxrAnom"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    window._nxrCsrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    window._nxrBase     = '<?= site_url(); ?>';

    var R = window.NxReportes;

    function cargar () {
        document.getElementById('nxrAnom').innerHTML = '<div class="nxr-cargando">Analizando el período…</div>';
        R.Filtros.estado('Consultando…');

        R.pedir('<?= site_url('reportes/panel'); ?>', function () { return R.Filtros.cuerpo({}) })
            .then(function (j) {
                var s = j.series;
                document.getElementById('nxrKpis').innerHTML = R.kpis(indicadores(j.resumen));
                document.getElementById('nxrConf').innerHTML = R.confiabilidad(j.confiabilidad);

                document.getElementById('nxrGrafs').innerHTML =
                    R.barras(s.dia,        'nombre', 'total', 'Ventas por día') +
                    R.barras(s.hora,       'nombre', 'total', 'Ventas por hora') +
                    R.barras(s.producto,   'nombre', 'total', 'Productos líderes') +
                    R.barras(s.categoria,  'nombre', 'total', 'Categorías líderes') +
                    R.barras(s.cliente,    'nombre', 'total', 'Clientes principales') +
                    R.barras(s.usuario,    'nombre', 'total', 'Ventas por usuario') +
                    R.barras(s.medio_pago, 'nombre', 'total', 'Cobrado por medio de pago') +
                    R.barras(s.tarifa,     'nombre', 'total', 'Ventas por tarifa de IVA') +
                    R.barras(s.estado,     'nombre', 'total', 'Ventas por estado ante Hacienda');

                document.getElementById('nxrAnom').innerHTML = R.anomalias(j.anomalias, 20) +
                    (j.anomalias_total > j.anomalias.length
                        ? '<p class="nxr-dim" style="margin-top:8px;font-size:12.5px">' +
                          'Hay ' + j.anomalias_total + ' hallazgos en total. ' +
                          '<a href="<?= site_url('reportes/ver/anomalias'); ?>">Ver todos</a>.</p>'
                        : '');

                R.Filtros.estado(j.resumen.documentos + ' comprobantes · confiabilidad ' +
                                 R.num(j.confiabilidad.pct, 1) + ' %');
            })
            .catch(function (e) {
                R.Filtros.estado(e.message, 'err');
                document.getElementById('nxrAnom').innerHTML =
                    '<div class="nxr-error"><b>El panel no se pudo resolver.</b><br>' + R.esc(e.message) + '</div>';
            });
    }

    /** Los mismos indicadores que llevan el PDF y el Excel, con igual formato. */
    function indicadores (r) {
        return [
            { etiqueta: 'Ventas del período', texto: R.money(r.total),  pie: R.num(r.documentos, 0) + ' comprobantes' },
            { etiqueta: 'Base gravable',      texto: R.money(r.base),   pie: 'IVA ' + R.money(r.impuesto) },
            { etiqueta: 'Venta neta',         texto: R.money(r.neto),   pie: 'descontadas notas de crédito' },
            { etiqueta: 'Ticket promedio',    texto: R.money(r.ticket), pie: R.num(r.clientes, 0) + ' clientes' },
            { etiqueta: 'Utilidad estimada',  texto: R.money(r.utilidad),
              pie: 'margen ' + R.num(r.margen, 2) + ' %', tono: r.utilidad >= 0 ? 'ok' : 'err' },
            { etiqueta: 'Compras del período', texto: R.money(r.costo), pie: 'costo de lo vendido' },
            { etiqueta: 'Descuentos',         texto: R.money(r.descuento), pie: 'sobre el detalle' },
            { etiqueta: 'Unidades vendidas',  texto: R.num(r.unidades, 0), pie: R.num(r.lineas, 0) + ' líneas' },
            { etiqueta: 'Notas de crédito',   texto: R.money(r.nc_total),
              pie: R.num(r.nc_cantidad, 0) + ' notas', tono: r.nc_cantidad > 0 ? 'warn' : '' },
            { etiqueta: 'Notas de débito',    texto: R.money(r.nd_total), pie: R.num(r.nd_cantidad, 0) + ' notas' },
            { etiqueta: 'Anulaciones',        texto: R.num(r.anuladas, 0),
              pie: R.money(r.anuladas_total), tono: r.anuladas > 0 ? 'warn' : '' },
            { etiqueta: 'Impuesto neto',      texto: R.money(r.impuesto_neto), pie: 'ajustado por notas' }
        ];
    }

    R.Filtros.iniciar(cargar);
    document.getElementById('nxrMaestro').addEventListener('click', function () {
        window.open('<?= site_url('reportes/maestro'); ?>?' + R.Filtros.query(), '_blank');
    });
    cargar();
});
</script>
