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
        <h1>Auditoría Integral</h1>
        <p id="nxrSub">Un semáforo por área. Cada uno lleva a la pantalla donde se resuelve.</p>
    </div>
    <div class="nxr-acciones">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('reportes'); ?>">Centro de Inteligencia</a>
        <button type="button" class="nxt-btn" id="nxrMaestro">Informe maestro (PDF)</button>
    </div>
</div>

<?php $this->load->view($this->theme . 'reportes/_filtros', array(
    'catalogos' => $catalogos,
    'ambitos'   => $ambitos,
    'campos'    => array('fechas', 'ambito', 'store_id'),
)); ?>

<div id="nxrConf"></div>
<div id="nxrSem"></div>

<h4 class="nxr-grupo" style="display:block;margin:20px 0 8px">Índice de integridad de datos</h4>
<p class="nxr-dim" style="font-size:12.5px;margin:0 0 10px">
    El mismo período medido por vías distintas tiene que dar lo mismo. Cada fila compara dos de
    esas vías y muestra dónde está la diferencia, no solo cuánto suma.
</p>
<div id="nxrInte"></div>

<h4 class="nxr-grupo" style="display:block;margin:20px 0 8px">Conciliación con Hacienda</h4>
<div id="nxrConc"></div>

<h4 class="nxr-grupo" style="display:block;margin:20px 0 8px">Detector de anomalías</h4>
<div id="nxrAnom"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    window._nxrCsrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    window._nxrBase     = '<?= site_url(); ?>';

    var R = window.NxReportes;

    function cargar () {
        document.getElementById('nxrSem').innerHTML = '<div class="nxr-cargando">Auditando el período…</div>';
        R.Filtros.estado('Auditando…');

        R.pedir('<?= site_url('reportes/auditoria_datos'); ?>', function () { return R.Filtros.cuerpo({}) })
            .then(function (j) {
                document.getElementById('nxrConf').innerHTML = R.confiabilidad(j.confiabilidad);
                document.getElementById('nxrSem').innerHTML  = R.semaforos(j.semaforos);
                document.getElementById('nxrInte').innerHTML = R.integridad(j.integridad);
                document.getElementById('nxrConc').innerHTML = conciliacion(j.conciliacion);
                document.getElementById('nxrAnom').innerHTML = R.anomalias(j.anomalias);
                R.Filtros.estado('Confiabilidad ' + R.num(j.confiabilidad.pct, 1) + ' % · ' +
                                 j.anomalias.length + ' hallazgos');
            })
            .catch(function (e) {
                R.Filtros.estado(e.message, 'err');
                document.getElementById('nxrSem').innerHTML =
                    '<div class="nxr-error"><b>La auditoría no se pudo completar.</b><br>' + R.esc(e.message) + '</div>';
            });
    }

    /** Resumen de la conciliación y solo los comprobantes que no cuadran. */
    function conciliacion (c) {
        var k = c.conteo;
        var tarjetas = [
            { etiqueta: 'Conciliados',            texto: R.num(k.conciliado, 0), pie: 'de ' + c.revisados + ' revisados', tono: 'ok' },
            { etiqueta: 'Sin comprobante',        texto: R.num(k.sin_enviar, 0), pie: 'nunca se enviaron',  tono: k.sin_enviar  ? 'err'  : '' },
            { etiqueta: 'Sin aceptar',            texto: R.num(k.sin_aceptar, 0), pie: 'rechazado o en proceso', tono: k.sin_aceptar ? 'warn' : '' },
            { etiqueta: 'Sin XML firmado',        texto: R.num(k.sin_xml, 0),    pie: 'no se puede probar qué se envió', tono: k.sin_xml ? 'warn' : '' },
            { etiqueta: 'Consecutivo o clave',    texto: R.num(k.descuadre, 0),  pie: 'no coinciden con la venta', tono: k.descuadre ? 'err' : '' },
            { etiqueta: 'Reemitidos',             texto: R.num(k.duplicado, 0),  pie: 'con más de un comprobante', tono: k.duplicado ? 'warn' : '' }
        ];

        var malos = c.filas.filter(function (f) { return !f.conciliado; });
        if (!malos.length) {
            return R.kpis(tarjetas) + '<div class="nxr-ok-vacio">🟢 Todos los comprobantes del período concilian.</div>';
        }

        return R.kpis(tarjetas) +
            '<div class="nxt-card"><div class="nxt-table-wrap"><table class="nxt-table">' +
            '<thead><tr><th>Fecha</th><th>Consecutivo</th><th>Cliente</th><th>Estado</th>' +
            '<th class="num">Total</th><th>Diferencias encontradas</th></tr></thead><tbody>' +
            malos.slice(0, 200).map(function (f) {
                return '<tr><td class="ctr">' + R.fecha(f.fecha, true) + '</td>' +
                    '<td><span class="nxt-code">' + R.esc(f.consecutivo) + '</span></td>' +
                    '<td>' + R.esc(f.cliente) + '</td>' +
                    '<td class="ctr">' + R.badge(f.estado) + '</td>' +
                    '<td class="num">' + R.money(f.total) + '</td>' +
                    '<td>' + R.esc(f.problemas) + '</td></tr>';
            }).join('') +
            '</tbody></table></div><div class="nxt-foot"><span class="nxt-foot-info">' +
            malos.length + ' comprobantes con diferencias, de ' + c.revisados + ' revisados' +
            '</span></div></div>';
    }

    R.Filtros.iniciar(cargar);
    document.getElementById('nxrMaestro').addEventListener('click', function () {
        window.open('<?= site_url('reportes/maestro'); ?>?' + R.Filtros.query(), '_blank');
    });
    cargar();
});
</script>
