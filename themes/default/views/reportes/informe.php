<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) or exit('No direct script access allowed');

/**
 * Pantalla única de todos los informes del catálogo.
 *
 * No hay una vista por informe: la definición del servidor ya dice qué columnas
 * tiene y con qué formato, así que esta pantalla las pinta todas. Añadir un
 * informe es añadir una entrada al catálogo del motor, no otro archivo.
 */
$js = 'themes/default/assets/dist/js/nx-reportes.js';
$v  = is_file(FCPATH . $js) ? filemtime(FCPATH . $js) : time();
?>
<script src="<?= base_url($js); ?>?v=<?= $v; ?>"></script>

<div class="nxr-head">
    <div>
        <h1><?= html_escape($definicion['titulo']); ?></h1>
        <p id="nxrSub">Elegí el período y aplicá para resolver el informe.</p>
    </div>
    <div class="nxr-acciones">
        <div class="nxt-search" style="max-width:230px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="10" cy="10" r="7"/><path d="M21 21l-6-6"/></svg>
            <input type="search" id="nxrBuscar" placeholder="Buscar en el resultado…" autocomplete="off">
        </div>
        <button type="button" class="nxt-btn nxt-btn-ghost" id="nxrPdf" title="Abre el PDF en una pestaña nueva">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            PDF
        </button>
        <button type="button" class="nxt-btn nxt-btn-ghost" id="nxrExcel" title="Cinco hojas: resumen, detalle, estadísticas, auditoría y parámetros">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M10 12l4 5"/><path d="M14 12l-4 5"/></svg>
            Excel
        </button>
    </div>
</div>

<div class="nxr-tabs">
    <?php
    $grupo = '';
    foreach ($catalogo as $k => $c):
        if ($c['grupo'] !== $grupo) {
            $grupo = $c['grupo'];
            echo '<span class="nxr-grupo">' . html_escape($grupo) . '</span>';
        }
        ?>
        <a class="nxr-tab<?= $k === $clave ? ' on' : ''; ?>"
           href="<?= site_url('reportes/ver/' . $k); ?>"><?= html_escape($c['titulo']); ?></a>
    <?php endforeach; ?>
</div>

<?php $this->load->view($this->theme . 'reportes/_filtros', array('catalogos' => $catalogos, 'ambitos' => $ambitos)); ?>

<div id="nxrKpis"></div>
<div id="nxrConf"></div>
<div id="nxrAnalisis"></div>
<div id="nxrTabla"><div class="nxr-cargando">Aplicá un período para resolver el informe.</div></div>
<div id="nxrAud"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    window._nxrCsrfName = '<?= $this->security->get_csrf_token_name(); ?>';
    window._nxrBase     = '<?= site_url(); ?>';

    var R      = window.NxReportes;
    var CLAVE  = '<?= html_escape($clave); ?>';
    var URL    = '<?= site_url('reportes/datos'); ?>/' + CLAVE;
    var doc    = null;

    function pintar () {
        if (!doc) return;
        var q = document.getElementById('nxrBuscar').value.trim();
        document.getElementById('nxrSub').textContent = doc.subtitulo + ' · ' + doc.folio;
        document.getElementById('nxrKpis').innerHTML     = R.kpis(doc.kpis);
        document.getElementById('nxrConf').innerHTML     = R.confiabilidad(doc.confiabilidad);
        document.getElementById('nxrAnalisis').innerHTML = R.analisis(doc.analisis);
        document.getElementById('nxrTabla').innerHTML    = R.tabla(doc, q);
        document.getElementById('nxrAud').innerHTML      =
            (doc.auditoria && doc.auditoria.length)
                ? '<h4 class="nxr-grupo" style="display:block;margin:18px 0 8px">Observaciones de auditoría</h4>' +
                  R.anomalias(doc.auditoria.map(function (a) {
                      return { nivel: a.nivel, tono: a.tono, titulo: a.descripcion, causa: '',
                               descripcion: '', documento: a.documento, monto: a.monto, accion: a.accion };
                  }))
                : '';
    }

    function cargar () {
        document.getElementById('nxrTabla').innerHTML = '<div class="nxr-cargando">Resolviendo el informe…</div>';
        R.Filtros.estado('Consultando…');
        R.pedir(URL, function () { return R.Filtros.cuerpo({ auditar: 1 }) })
            .then(function (j) {
                doc = j;
                R.Filtros.estado(j.registros + ' registros · ' + j.folio);
                pintar();
            })
            .catch(function (e) {
                doc = null;
                R.Filtros.estado(e.message, 'err');
                document.getElementById('nxrTabla').innerHTML =
                    '<div class="nxr-error"><b>El informe no se pudo resolver.</b><br>' + R.esc(e.message) + '</div>';
            });
    }

    R.Filtros.iniciar(cargar);
    document.getElementById('nxrBuscar').addEventListener('input', pintar);

    // Los mismos filtros viajan en la URL: el PDF y el Excel salen del período
    // que se está viendo, no de uno por defecto.
    document.getElementById('nxrPdf').addEventListener('click', function () {
        window.open('<?= site_url('reportes/pdf'); ?>/' + CLAVE + '?' + R.Filtros.query(), '_blank');
    });
    document.getElementById('nxrExcel').addEventListener('click', function () {
        window.location = '<?= site_url('reportes/excel'); ?>/' + CLAVE + '?' + R.Filtros.query();
    });

    cargar();
});
</script>
