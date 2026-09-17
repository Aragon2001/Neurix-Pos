<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Importación en dos pasos. $revision llega solo después de revisar el archivo
 * y es lo único que el paso de confirmación acepta.
 */
$rev = (isset($revision) && is_array($revision)) ? $revision : NULL;

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_subir  = '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 9l5 -5l5 5"/><path d="M12 4l0 12"/>';
$ico_hoja   = '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>';
$ico_desc   = '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4l0 12"/>';

/* Columnas del CSV: las dos son obligatorias. */
$columnas = array('code' => TRUE, 'name' => TRUE);

$modos = array(
    'crear'      => array(lang('cat_import_modo_crear'),      '<path d="M12 5l0 14"/><path d="M5 12l14 0"/>'),
    'actualizar' => array(lang('cat_import_modo_actualizar'), '<path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>'),
    'ambos'      => array(lang('cat_import_modo_ambos'),      '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>'),
);
$modo_activo = $rev && !empty($rev['modo']) ? $rev['modo'] : 'crear';
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('import_categories'); ?>
        <small><?= lang('cat_import_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= base_url('uploads/csv/sample_categories.csv'); ?>" download>
            <?= $icono($ico_desc); ?> <?= lang('import_ejemplo'); ?>
        </a>
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('categories'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('categories'); ?>
        </a>
    </div>
</div>

<div class="imp-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err"><div><?= $error; ?></div></div>
    <?php } ?>
    <?php if (!empty($message)) { ?>
        <div class="nxf-note nxf-note-ok"><div><?= $message; ?></div></div>
    <?php } ?>

    <!-- ── Paso 1: el archivo ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title">
                <?= lang('import_paso_archivo'); ?>
                <small><?= lang('import_paso_archivo_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <?= form_open_multipart('categories/revisar_import', array('id' => 'impForm')); ?>

            <label class="imp-zona" id="impZona" for="userfile">
                <input type="file" name="userfile" id="userfile" accept=".csv,text/csv" required>
                <span class="imp-zona-ico"><?= $icono($ico_subir, 26); ?></span>
                <span class="imp-zona-txt">
                    <b id="impNombre"><?= lang('import_soltar'); ?></b>
                    <small id="impDetalle"><?= lang('import_limite'); ?></small>
                </span>
                <span class="imp-zona-btn"><?= lang('import_elegir'); ?></span>
            </label>

            <label class="nxf-label" style="margin-top:18px;"><?= lang('import_modo'); ?></label>
            <div class="imp-modos">
                <?php foreach ($modos as $clave => $m) { ?>
                <label class="imp-modo">
                    <input type="radio" name="modo" value="<?= $clave; ?>" <?= $clave === $modo_activo ? 'checked' : ''; ?>>
                    <span class="imp-modo-ico"><?= $icono($m[1], 17); ?></span>
                    <span class="imp-modo-txt"><?= html_escape($m[0]); ?></span>
                </label>
                <?php } ?>
            </div>

            <label class="nxf-label" style="margin-top:18px;"><?= lang('import_columnas'); ?></label>
            <div class="imp-cols">
                <?php foreach ($columnas as $col => $obligatoria) { ?>
                    <span class="imp-col<?= $obligatoria ? ' req' : ''; ?>"><?= $col; ?></span>
                <?php } ?>
            </div>
            <div class="nxf-hint" style="margin-top:8px;"><?= lang('cat_import_columnas_ayuda'); ?></div>

            <div class="imp-acciones">
                <span class="nxf-spacer"></span>
                <button type="submit" class="nxf-btn" id="impEnviar" disabled>
                    <?= $icono($ico_hoja); ?> <?= lang('import_revisar'); ?>
                </button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>

    <?php if ($rev) {
        $n_crear = count($rev['crear']);
        $n_act   = count($rev['actualizar']);
        $n_err   = count($rev['errores']);
        $n_ok    = $n_crear + $n_act;
    ?>
    <!-- ── Paso 2: qué va a pasar ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title">
                <?= lang('import_paso_revision'); ?>
                <small><?= html_escape(isset($rev['archivo']) ? $rev['archivo'] : ''); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">

            <div class="imp-kpis">
                <div class="imp-kpi ok">
                    <span class="imp-kpi-n"><?= $n_crear; ?></span>
                    <span class="imp-kpi-t"><?= lang('import_kpi_crear'); ?></span>
                </div>
                <div class="imp-kpi info">
                    <span class="imp-kpi-n"><?= $n_act; ?></span>
                    <span class="imp-kpi-t"><?= lang('import_kpi_actualizar'); ?></span>
                </div>
                <div class="imp-kpi <?= $n_err ? 'err' : 'mudo'; ?>">
                    <span class="imp-kpi-n"><?= $n_err; ?></span>
                    <span class="imp-kpi-t"><?= lang('import_kpi_rechazadas'); ?></span>
                </div>
            </div>

            <?php if ($n_err) { ?>
                <div class="nxf-note nxf-note-err" style="margin-top:16px;">
                    <div><?= sprintf(lang('import_hay_errores'), $n_err); ?></div>
                    <?= form_open('categories/descargar_rechazos_import', array('class' => 'imp-form-inline')); ?>
                        <input type="hidden" name="errores" value="<?= html_escape(json_encode($rev['errores'])); ?>">
                        <button type="submit" class="nxf-btn nxf-btn-sm nxf-btn-ghost"><?= lang('import_descargar_rechazos'); ?></button>
                    <?= form_close(); ?>
                </div>
            <?php } ?>

            <div class="imp-tabs" id="impTabs">
                <?php if ($n_ok) { ?>
                    <button type="button" class="activo" data-tab="ok"><?= lang('import_tab_aplicar'); ?> <em><?= $n_ok; ?></em></button>
                <?php } ?>
                <?php if ($n_err) { ?>
                    <button type="button" class="<?= $n_ok ? '' : 'activo'; ?>" data-tab="err"><?= lang('import_tab_rechazadas'); ?> <em><?= $n_err; ?></em></button>
                <?php } ?>
            </div>

            <?php if ($n_ok) { ?>
            <div class="imp-panel visible" data-panel="ok">
                <div class="imp-tabla-wrap">
                    <table class="imp-tabla">
                        <thead>
                            <tr>
                                <th class="num"><?= lang('linea'); ?></th>
                                <th><?= lang('accion'); ?></th>
                                <th><?= lang('category_code'); ?></th>
                                <th><?= lang('cat_import_nombre_actual'); ?></th>
                                <th><?= lang('cat_import_nombre_nuevo'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array('crear' => 'ok', 'actualizar' => 'info') as $clave => $tono) {
                                foreach ($rev[$clave] as $f) { ?>
                                    <tr>
                                        <td class="num mono"><?= (int) $f['linea']; ?></td>
                                        <td><span class="imp-tag <?= $tono; ?>"><?= lang('import_' . $clave); ?></span></td>
                                        <td class="mono"><?= html_escape($f['code']); ?></td>
                                        <td><?= isset($f['antes']) ? html_escape($f['antes']) : '<span class="imp-nada">&mdash;</span>'; ?></td>
                                        <td><?= html_escape($f['name']); ?></td>
                                    </tr>
                                <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php } ?>

            <?php if ($n_err) { ?>
            <div class="imp-panel<?= $n_ok ? '' : ' visible'; ?>" data-panel="err">
                <div class="imp-tabla-wrap">
                    <table class="imp-tabla">
                        <thead>
                            <tr>
                                <th class="num"><?= lang('linea'); ?></th>
                                <th><?= lang('category_code'); ?></th>
                                <th><?= lang('error'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rev['errores'] as $e) { ?>
                                <tr>
                                    <td class="num mono"><?= (int) $e['linea']; ?></td>
                                    <td class="mono"><?= html_escape(isset($e['code']) ? $e['code'] : '—'); ?></td>
                                    <td class="imp-msg"><?= html_escape($e['error']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php } ?>

            <div class="imp-acciones">
                <a class="nxf-btn nxf-btn-sm nxf-btn-ghost" href="<?= site_url('categories/descartar_import'); ?>">
                    <?= lang('import_descartar'); ?>
                </a>
                <span class="nxf-spacer"></span>
                <?php if ($n_ok) { ?>
                    <span class="nxf-hint"><?= lang('import_confirmar_ayuda'); ?></span>
                    <?= form_open('categories/confirmar_import', array('class' => 'imp-form-inline')); ?>
                        <input type="hidden" name="revision" value="<?= html_escape(json_encode(array('crear' => $rev['crear'], 'actualizar' => $rev['actualizar']))); ?>">
                        <button type="submit" class="nxf-btn">
                            <?= $icono($ico_check); ?> <?= lang('import_confirmar'); ?>
                        </button>
                    <?= form_close(); ?>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<style>
    .imp-page{max-width:1000px;margin:0 auto;display:flex;flex-direction:column;gap:16px}

    /* ── Zona de archivo ── */
    .imp-zona{
        position:relative;display:flex;align-items:center;gap:15px;padding:22px;cursor:pointer;
        border:1.5px dashed var(--nx-border3);border-radius:var(--nx-radius);
        background:var(--nx-input-bg);transition:var(--nx-transition);
    }
    .imp-zona:hover,.imp-zona.encima{background:var(--nx-input-focus);border-color:var(--nx-a1)}
    .imp-zona.cargado{border-style:solid;border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.07)}
    .imp-zona input[type=file]{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
    .imp-zona-ico{
        flex:0 0 auto;width:50px;height:50px;border-radius:14px;display:grid;place-items:center;
        background:var(--nx-hover-bg);color:var(--nx-a1);transition:var(--nx-transition);
    }
    .imp-zona.cargado .imp-zona-ico{background:rgba(34,197,94,.14);color:var(--nx-ok)}
    .imp-zona-txt{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
    .imp-zona-txt b{font-size:14.5px;font-weight:600;color:var(--nx-txt1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .imp-zona-txt small{font-size:12px;color:var(--nx-txt4)}
    .imp-zona-btn{
        flex:0 0 auto;font:600 12.5px inherit;font-family:inherit;padding:8px 14px;border-radius:9px;
        border:1px solid var(--nx-border3);color:var(--nx-a1);background:var(--nx-active-bg);
    }

    /* ── Modos ── */
    .imp-modos{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px}
    .imp-modo{
        display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:11px;cursor:pointer;
        border:1px solid var(--nx-border);background:var(--nx-card-bg2);transition:var(--nx-transition);
    }
    .imp-modo:hover{border-color:var(--nx-border3)}
    .imp-modo input{position:absolute;opacity:0;width:0;height:0}
    .imp-modo-ico{
        flex:0 0 auto;width:30px;height:30px;border-radius:9px;display:grid;place-items:center;
        background:var(--nx-hover-bg);color:var(--nx-txt3);transition:var(--nx-transition);
    }
    .imp-modo-txt{font-size:13px;font-weight:600;color:var(--nx-txt2);line-height:1.3}
    .imp-modo.marcado{border-color:var(--nx-border3);background:var(--nx-active-bg)}
    .imp-modo.marcado .imp-modo-ico{background:linear-gradient(135deg,var(--nx-a1),var(--nx-info));color:#06121f}
    [data-theme="light"] .imp-modo.marcado .imp-modo-ico{color:#fff}
    .imp-modo.marcado .imp-modo-txt{color:var(--nx-a1)}

    /* ── Columnas admitidas ── */
    .imp-cols{display:flex;flex-wrap:wrap;gap:7px}
    .imp-col{
        font:500 11.5px ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.02em;
        padding:5px 10px;border-radius:999px;border:1px solid var(--nx-border);
        background:var(--nx-card-bg2);color:var(--nx-txt3);
    }
    .imp-col.req{border-color:var(--nx-border3);background:var(--nx-active-bg);color:var(--nx-a1);font-weight:700}
    .imp-col.req::after{content:" *"}

    /* ── Resumen de la revisión ── */
    .imp-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
    .imp-kpi{
        position:relative;overflow:hidden;padding:14px 16px;border-radius:var(--nx-radius);
        border:1px solid var(--nx-border);background:var(--nx-card-bg2);
        display:flex;flex-direction:column;gap:3px;
    }
    .imp-kpi::before{content:"";position:absolute;inset:0 auto 0 0;width:3px;background:var(--kpi-c,var(--nx-slate))}
    .imp-kpi.ok{--kpi-c:var(--nx-ok)}
    .imp-kpi.info{--kpi-c:var(--nx-a1)}
    .imp-kpi.err{--kpi-c:var(--nx-err)}
    .imp-kpi.mudo{--kpi-c:var(--nx-txt4);opacity:.6}
    .imp-kpi-n{font-size:24px;font-weight:700;color:var(--nx-txt1);letter-spacing:-.02em;font-variant-numeric:tabular-nums}
    .imp-kpi-t{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--nx-txt4)}
    .imp-form-inline{margin:0}
    .nxf-note .imp-form-inline{margin-left:auto}

    /* ── Pestañas ── */
    .imp-tabs{display:flex;gap:6px;margin:18px 0 0;border-bottom:1px solid var(--nx-border)}
    .imp-tabs button{
        border:0;background:none;cursor:pointer;padding:10px 14px;margin-bottom:-1px;
        font:600 13px inherit;font-family:inherit;color:var(--nx-txt4);
        border-bottom:2px solid transparent;transition:var(--nx-transition);
    }
    .imp-tabs button:hover{color:var(--nx-txt2)}
    .imp-tabs button.activo{color:var(--nx-a1);border-bottom-color:var(--nx-a1)}
    .imp-tabs em{
        font-style:normal;font-size:11px;font-weight:700;margin-left:6px;padding:1px 7px;border-radius:999px;
        background:var(--nx-card-bg2);border:1px solid var(--nx-border);
    }
    .imp-panel{display:none}
    .imp-panel.visible{display:block}

    /* ── Tablas ── */
    .imp-tabla-wrap{margin-top:14px;border:1px solid var(--nx-border);border-radius:var(--nx-radius);overflow:auto;max-height:440px}
    .imp-tabla{width:100%;border-collapse:collapse;font-size:12.5px;min-width:620px}
    .imp-tabla thead th{
        position:sticky;top:0;z-index:1;padding:10px 12px;text-align:left;white-space:nowrap;
        font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--nx-txt4);
        background:var(--nx-bg3);border-bottom:1px solid var(--nx-border);
    }
    .imp-tabla td{padding:8px 12px;border-bottom:1px solid var(--nx-border2);color:var(--nx-txt2)}
    .imp-tabla tbody tr:last-child td{border-bottom:0}
    .imp-tabla tbody tr:hover{background:var(--nx-hover-bg)}
    .imp-tabla th.num,.imp-tabla td.num{text-align:right}
    .imp-tabla td.mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:var(--nx-txt3);font-variant-numeric:tabular-nums}
    .imp-tabla td.imp-msg{color:var(--nx-err)}
    .imp-nada{color:var(--nx-txt4)}
    .imp-tag{
        display:inline-block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
        padding:3px 9px;border-radius:999px;border:1px solid var(--nx-border);color:var(--nx-txt3);
    }
    .imp-tag.ok{border-color:rgba(34,197,94,.4);background:rgba(34,197,94,.11);color:var(--nx-ok)}
    .imp-tag.info{border-color:var(--nx-border3);background:var(--nx-active-bg);color:var(--nx-a1)}

    .imp-acciones{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px solid var(--nx-border)}
</style>

<script>
(function () {
    'use strict';

    var LIMITE_MB = 4;
    var T = {
        soltar:  <?= json_encode(lang('import_soltar')); ?>,
        ayuda:   <?= json_encode(lang('import_limite')); ?>,
        noEsCsv: <?= json_encode(lang('import_no_es_csv')); ?>,
        pesado:  <?= json_encode(lang('import_muy_grande')); ?>
    };

    var $zona    = document.getElementById('impZona');
    var $input   = document.getElementById('userfile');
    var $nombre  = document.getElementById('impNombre');
    var $detalle = document.getElementById('impDetalle');
    var $enviar  = document.getElementById('impEnviar');

    function tamano(bytes) {
        return bytes < 1024 * 1024
            ? Math.max(1, Math.round(bytes / 1024)) + ' KB'
            : (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    function limpiar(mensaje) {
        $zona.classList.remove('cargado');
        $nombre.textContent = T.soltar;
        $detalle.textContent = mensaje || T.ayuda;
        $enviar.disabled = true;
        $input.value = '';
    }

    function revisar() {
        var f = $input.files && $input.files[0];
        if (!f) { limpiar(); return; }
        if (!/\.csv$/i.test(f.name)) { limpiar(T.noEsCsv); return; }
        if (f.size > LIMITE_MB * 1024 * 1024) { limpiar(T.pesado.replace('%s', LIMITE_MB)); return; }

        $zona.classList.add('cargado');
        $nombre.textContent = f.name;
        $detalle.textContent = tamano(f.size);
        $enviar.disabled = false;
    }

    $input.addEventListener('change', revisar);

    ['dragenter', 'dragover'].forEach(function (ev) {
        $zona.addEventListener(ev, function (e) { e.preventDefault(); $zona.classList.add('encima'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        $zona.addEventListener(ev, function (e) { e.preventDefault(); $zona.classList.remove('encima'); });
    });
    $zona.addEventListener('drop', function (e) {
        if (!e.dataTransfer || !e.dataTransfer.files.length) { return; }
        $input.files = e.dataTransfer.files;
        revisar();
    });

    /* ── Modo elegido: la clase la pone el JS, no :has(), que es reciente ── */
    var $modos = document.querySelectorAll('.imp-modo');
    function marcarModo() {
        $modos.forEach(function (m) { m.classList.toggle('marcado', m.querySelector('input').checked); });
    }
    $modos.forEach(function (m) { m.querySelector('input').addEventListener('change', marcarModo); });
    marcarModo();

    /* ── Pestañas de la revisión ── */
    var $tabs = document.getElementById('impTabs');
    if ($tabs) {
        $tabs.addEventListener('click', function (e) {
            var b = e.target.closest('[data-tab]');
            if (!b) { return; }
            this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('activo', x === b); });
            document.querySelectorAll('.imp-panel').forEach(function (p) {
                p.classList.toggle('visible', p.dataset.panel === b.dataset.tab);
            });
        });
    }
})();
</script>
