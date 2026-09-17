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
        <h1>Bitácora de informes</h1>
        <p>Cada informe generado deja su folio, quién lo pidió, con qué filtros y qué devolvió.
           Es lo que permite volver de un papel impreso a la consulta que lo produjo.</p>
    </div>
    <div class="nxr-acciones">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('reportes'); ?>">Centro de Inteligencia</a>
    </div>
</div>

<?php $this->load->view($this->theme . 'reportes/_filtros', array('campos' => array('fechas'))); ?>

<div id="nxrTabla"><div class="nxr-cargando">Cargando la bitácora…</div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    window._nxrCsrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    var R = window.NxReportes;

    function cargar () {
        R.pedir('<?= site_url('reportes/bitacora_datos'); ?>', function () { return R.Filtros.cuerpo({}) })
            .then(function (j) {
                var f = j.data || [];
                if (!f.length) {
                    document.getElementById('nxrTabla').innerHTML =
                        '<div class="nxr-ok-vacio">No se generó ningún informe en este período.</div>';
                    return;
                }
                document.getElementById('nxrTabla').innerHTML =
                    '<div class="nxt-card"><div class="nxt-table-wrap"><table class="nxt-table">' +
                    '<thead><tr><th>Folio</th><th>Generado</th><th>Informe</th><th>Formato</th>' +
                    '<th>Usuario</th><th>Período</th><th>Ámbito</th><th class="num">Registros</th>' +
                    '<th class="num">Total</th><th class="num">Confiab.</th><th>Filtros</th><th>IP</th></tr></thead><tbody>' +
                    f.map(function (r) {
                        var filtros = '';
                        try {
                            var o = JSON.parse(r.filtros || '{}');
                            filtros = Object.keys(o).map(function (k) { return k + ': ' + o[k]; }).join(' · ');
                        } catch (e) { filtros = r.filtros || ''; }
                        return '<tr>' +
                            '<td><span class="nxt-code">' + R.esc(r.folio) + '</span></td>' +
                            '<td class="ctr">' + R.fecha(r.fecha, true) + '</td>' +
                            '<td>' + R.esc(r.titulo || r.reporte) + '</td>' +
                            '<td class="ctr">' + R.badge(r.formato) + '</td>' +
                            '<td>' + R.esc(r.usuario) + '</td>' +
                            '<td class="ctr"><small>' + R.fecha(r.desde) + ' – ' + R.fecha(r.hasta) + '</small></td>' +
                            '<td class="ctr">' + R.esc(r.ambito) + '</td>' +
                            '<td class="num">' + R.num(r.registros, 0) + '</td>' +
                            '<td class="num">' + R.money(r.total) + '</td>' +
                            '<td class="num">' + (r.confiabilidad === null ? '—' : R.num(r.confiabilidad, 1) + ' %') + '</td>' +
                            '<td><small class="nxr-dim">' + R.esc(filtros) + '</small></td>' +
                            '<td><small class="nxr-dim">' + R.esc(r.ip) + '</small></td>' +
                            '</tr>';
                    }).join('') +
                    '</tbody></table></div><div class="nxt-foot"><span class="nxt-foot-info">' +
                    f.length + ' generaciones</span></div></div>';
                R.Filtros.estado(f.length + ' generaciones');
            })
            .catch(function (e) {
                R.Filtros.estado(e.message, 'err');
                document.getElementById('nxrTabla').innerHTML =
                    '<div class="nxr-error">' + R.esc(e.message) + '</div>';
            });
    }

    R.Filtros.iniciar(cargar);
    cargar();
});
</script>
