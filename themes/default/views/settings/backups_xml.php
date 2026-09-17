<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$inventario = isset($inventario) ? (array) $inventario : array();

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_baja   = '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4l0 12"/>';
$ico_alerta = '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>';
$ico_firma  = '<path d="M3 21c3 -1 6.5 -2 9.5 -5c3 -3 3.5 -6.5 2 -8c-1.5 -1.5 -4 -0.5 -4 2c0 4 3 7 8 7"/>';
$ico_nube   = '<path d="M12 18.004h-5.343c-2.572 -.004 -4.657 -2.011 -4.657 -4.487c0 -2.475 2.085 -4.482 4.657 -4.482c.393 -1.762 1.794 -3.2 3.675 -3.773c1.88 -.572 3.956 -.193 5.444 .995c1.488 1.188 2.162 3.007 1.77 4.769h.99c1.913 0 3.464 1.56 3.464 3.486c0 1.927 -1.551 3.487 -3.465 3.487h-1.535"/><path d="M12 13v9"/><path d="M9 19l3 3l3 -3"/>';

$firmados   = array_sum(array_column($inventario, 'firmados'));
$respuestas = array_sum(array_column($inventario, 'respuestas'));
$archivos   = $firmados + $respuestas;
$hay_zip    = !empty($hay_zip);
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('backup_xmls'); ?>
        <small><?= lang('xml_respaldo_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('settings/backups'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('backups'); ?>
        </a>
        <a class="nxt-btn<?= ($archivos && $hay_zip) ? '' : ' es-inerte'; ?>" id="btnXml"
           href="<?= site_url('settings/getDownloadxml'); ?>">
            <?= $icono($ico_baja); ?> <?= lang('xml_descargar'); ?>
        </a>
    </div>
</div>

<div class="nxf-page nxb-page">

    <?php if (!$hay_zip) { ?>
        <div class="nxf-note nxf-note-err">
            <?= $icono($ico_alerta, 17); ?>
            <div><?= lang('xml_sin_zip'); ?></div>
        </div>
    <?php } ?>

    <div class="nxb-tiles">
        <div class="nxb-tile">
            <span class="nxb-tile-ico"><?= $icono($ico_firma, 18); ?></span>
            <div>
                <div class="nxb-tile-dato"><?= number_format($firmados); ?></div>
                <div class="nxb-tile-rotulo"><?= lang('xml_firmados'); ?></div>
            </div>
        </div>
        <div class="nxb-tile">
            <span class="nxb-tile-ico"><?= $icono($ico_nube, 18); ?></span>
            <div>
                <div class="nxb-tile-dato"><?= number_format($respuestas); ?></div>
                <div class="nxb-tile-rotulo"><?= lang('xml_respuestas'); ?></div>
            </div>
        </div>
        <div class="nxb-tile">
            <span class="nxb-tile-ico"><?= $icono($ico_baja, 18); ?></span>
            <div>
                <div class="nxb-tile-dato"><?= number_format($archivos); ?></div>
                <div class="nxb-tile-rotulo"><?= lang('xml_archivos_zip'); ?></div>
            </div>
        </div>
    </div>

    <div class="nxf-card">
        <div class="nxf-card-head">
            <div class="nxf-card-title">
                <?= lang('xml_contenido'); ?>
                <small><?= lang('xml_contenido_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <?php if (!$archivos) { ?>
                <div class="nxb-vacio">
                    <?= $icono($ico_firma, 30); ?>
                    <p><?= lang('xml_sin_comprobantes'); ?></p>
                </div>
            <?php } else { ?>
                <div class="nxb-tabla-marco">
                    <table class="nxb-tabla">
                        <thead>
                            <tr>
                                <th><?= lang('tipo_documento'); ?></th>
                                <th class="num"><?= lang('xml_firmados'); ?></th>
                                <th class="num"><?= lang('xml_respuestas'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventario as $f) { ?>
                                <tr<?= ($f['firmados'] + $f['respuestas']) ? '' : ' class="es-vacia"'; ?>>
                                    <td><?= html_escape($f['rotulo']); ?></td>
                                    <td class="num"><?= number_format($f['firmados']); ?></td>
                                    <td class="num"><?= number_format($f['respuestas']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><?= lang('total'); ?></td>
                                <td class="num"><?= number_format($firmados); ?></td>
                                <td class="num"><?= number_format($respuestas); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php } ?>

            <div class="nxf-note nxf-note-warn" style="margin-top:16px">
                <?= $icono($ico_alerta, 17); ?>
                <div><?= lang('xml_aviso_firmados'); ?></div>
            </div>
            <div class="nxf-note nxf-note-info" style="margin-top:10px">
                <div><?= lang('xml_nombres_ayuda'); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="nxb-espera" id="nxbEspera" hidden>
    <div class="nxb-espera-caja">
        <span class="nxb-girador"></span>
        <b><?= lang('please_wait'); ?></b>
        <small><?= lang('backup_modal_msg'); ?></small>
    </div>
</div>

<style>
    .nxb-page{max-width:820px}

    .nxb-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
    .nxb-tile{
        display:flex;align-items:center;gap:13px;padding:15px 17px;
        background:var(--nx-card-bg);border:1px solid var(--nx-border);border-radius:var(--nx-radius-lg);
        backdrop-filter:blur(16px);box-shadow:var(--nx-shadow-sm);
    }
    .nxb-tile-ico{
        flex:0 0 auto;width:38px;height:38px;border-radius:11px;display:grid;place-items:center;
        border:1px solid var(--nx-border3);background:var(--nx-active-bg);color:var(--nx-a1);
    }
    .nxb-tile-dato{font-size:19px;font-weight:700;color:var(--nx-txt1);letter-spacing:-.02em}
    .nxb-tile-rotulo{
        margin-top:3px;font-size:11px;font-weight:600;text-transform:uppercase;
        letter-spacing:.07em;color:var(--nx-txt4);
    }

    .nxb-tabla-marco{overflow-x:auto}
    .nxb-tabla{width:100%;border-collapse:collapse;font-size:13.5px;min-width:360px}
    .nxb-tabla th{
        text-align:left;padding:0 12px 9px;font-size:11px;font-weight:600;
        text-transform:uppercase;letter-spacing:.07em;color:var(--nx-txt4);
        border-bottom:1px solid var(--nx-border);
    }
    .nxb-tabla td{padding:11px 12px;border-bottom:1px solid var(--nx-border);color:var(--nx-txt2)}
    .nxb-tabla tbody tr:hover td{background:var(--nx-hover-bg)}
    .nxb-tabla tr.es-vacia td{color:var(--nx-txt4)}
    .nxb-tabla .num{
        text-align:right;font-family:ui-monospace,"SFMono-Regular",Menlo,Consolas,monospace;
        font-variant-numeric:tabular-nums;
    }
    .nxb-tabla tfoot td{font-weight:700;color:var(--nx-txt1);border-bottom:0}

    .nxb-vacio{display:flex;flex-direction:column;align-items:center;gap:12px;padding:34px 20px;color:var(--nx-txt4)}
    .nxb-vacio p{margin:0;font-size:13.5px;text-align:center;max-width:400px}

    .nxt-btn.es-inerte{opacity:.5;pointer-events:none}

    .nxb-espera{position:fixed;inset:0;z-index:1200;display:grid;place-items:center;background:rgba(2,6,23,.62);backdrop-filter:blur(3px)}
    .nxb-espera-caja{
        display:flex;flex-direction:column;align-items:center;gap:11px;text-align:center;
        padding:28px 34px;border-radius:var(--nx-radius-lg);
        background:var(--nx-bg2);border:1px solid var(--nx-border);box-shadow:var(--nx-shadow);
    }
    .nxb-espera-caja b{font-size:15px;color:var(--nx-txt1)}
    .nxb-espera-caja small{font-size:12.5px;color:var(--nx-txt3)}
    .nxb-girador{
        width:30px;height:30px;border-radius:50%;
        border:3px solid var(--nx-border3);border-top-color:var(--nx-a1);
        animation:nxb-turn .8s linear infinite;
    }
    @keyframes nxb-turn{to{transform:rotate(360deg)}}
</style>

<script>
(function () {
    'use strict';

    // Armar el zip recorre todas las tablas de comprobantes: sin este aviso la
    // pantalla se queda quieta hasta que el navegador ofrece el archivo.
    var $boton = document.getElementById('btnXml');
    var $velo  = document.getElementById('nxbEspera');
    if (!$boton) { return; }

    $boton.addEventListener('click', function () {
        $velo.hidden = false;
        setTimeout(function () { $velo.hidden = true; }, 20000);
    });

    // La descarga no recarga la pantalla: al volver a ella se quita el velo.
    window.addEventListener('pageshow', function () { $velo.hidden = true; });
})();
</script>
