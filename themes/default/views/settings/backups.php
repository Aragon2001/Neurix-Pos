<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$copias = isset($copias) ? (array) $copias : array();

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_base    = '<path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0"/><path d="M4 6v6a8 3 0 0 0 16 0v-6"/><path d="M4 12v6a8 3 0 0 0 16 0v-6"/>';
$ico_baja    = '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4l0 12"/>';
$ico_volver  = '<path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>';
$ico_borrar  = '<path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>';
$ico_alerta  = '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>';
$ico_xml     = '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4"/><path d="M4 15l4 6"/><path d="M8 15l-4 6"/><path d="M19 20.25c0 .414 -.336 .75 -.75 .75h-2.25v-6h2.25c.414 0 .75 .336 .75 .75v4.5z"/><path d="M11 15v6h3"/>';
$ico_reloj   = '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 7v5l3 3"/>';
$ico_caja    = '<path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12l0 9"/><path d="M12 12l-8 -4.5"/>';

$peso = function ($bytes) {
    if ($bytes >= 1073741824) { return number_format($bytes / 1073741824, 1) . ' GB'; }
    if ($bytes >= 1048576)    { return number_format($bytes / 1048576, 1) . ' MB'; }
    return max(1, (int) round($bytes / 1024)) . ' KB';
};

// Cuántos días lleva la copia más reciente: pasada una semana el aviso cambia
// de tono, que es lo único que distingue un respaldo vivo de uno olvidado.
$ultima  = $copias ? $copias[0] : NULL;
$dias    = $ultima ? (int) floor((time() - strtotime($ultima['fecha'])) / 86400) : NULL;
$ocupado = array_sum(array_column($copias, 'bytes'));
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('backups'); ?>
        <small><?= lang('respaldo_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('settings/backups_xml'); ?>">
            <?= $icono($ico_xml); ?> <?= lang('backup_xmls'); ?>
        </a>
        <a class="nxt-btn" href="<?= site_url('settings/backup_database'); ?>" id="btnRespaldar">
            <?= $icono($ico_base); ?> <?= lang('backup_database'); ?>
        </a>
    </div>
</div>

<div class="nxf-page nxb-page">

    <!-- ── Estado del respaldo ── -->
    <div class="nxb-tiles">
        <div class="nxb-tile">
            <span class="nxb-tile-ico"><?= $icono($ico_reloj, 18); ?></span>
            <div>
                <div class="nxb-tile-dato"><?= $ultima ? html_escape($this->tec->hrld($ultima['fecha'])) : '—'; ?></div>
                <div class="nxb-tile-rotulo"><?= lang('respaldo_ultima'); ?></div>
            </div>
        </div>
        <div class="nxb-tile">
            <span class="nxb-tile-ico"><?= $icono($ico_base, 18); ?></span>
            <div>
                <div class="nxb-tile-dato"><?= count($copias); ?></div>
                <div class="nxb-tile-rotulo"><?= lang('respaldo_guardadas'); ?></div>
            </div>
        </div>
        <div class="nxb-tile">
            <span class="nxb-tile-ico"><?= $icono($ico_caja, 18); ?></span>
            <div>
                <div class="nxb-tile-dato"><?= $copias ? $peso($ocupado) : '—'; ?></div>
                <div class="nxb-tile-rotulo"><?= lang('respaldo_espacio'); ?></div>
            </div>
        </div>
    </div>

    <?php if (!$copias) { ?>
        <div class="nxf-note nxf-note-warn">
            <?= $icono($ico_alerta, 17); ?>
            <div><?= lang('respaldo_ninguna'); ?></div>
        </div>
    <?php } elseif ($dias >= 7) { ?>
        <div class="nxf-note nxf-note-warn">
            <?= $icono($ico_alerta, 17); ?>
            <div><?= sprintf(lang('respaldo_vieja'), $dias); ?></div>
        </div>
    <?php } ?>

    <!-- ── Copias guardadas ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <div class="nxf-card-title">
                <?= lang('database_backups'); ?>
                <small><?= lang('restore_heading'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <?php if (!$copias) { ?>
                <div class="nxb-vacio">
                    <?= $icono($ico_base, 30); ?>
                    <p><?= lang('respaldo_vacio_ayuda'); ?></p>
                    <a class="nxf-btn" href="<?= site_url('settings/backup_database'); ?>">
                        <?= lang('backup_database'); ?>
                    </a>
                </div>
            <?php } else { ?>
                <ul class="nxb-lista">
                    <?php foreach ($copias as $i => $c) { ?>
                        <li class="nxb-fila<?= $i === 0 ? ' es-ultima' : ''; ?>">
                            <div class="nxb-fila-datos">
                                <div class="nxb-fila-fecha">
                                    <?= html_escape($this->tec->hrld($c['fecha'])); ?>
                                    <?php if ($i === 0) { ?>
                                        <span class="nxb-chip"><?= lang('respaldo_reciente'); ?></span>
                                    <?php } ?>
                                </div>
                                <div class="nxb-fila-meta">
                                    <span class="nxb-mono"><?= html_escape($c['nombre']); ?>.txt</span>
                                    <span>·</span>
                                    <span><?= $peso($c['bytes']); ?></span>
                                </div>
                            </div>
                            <div class="nxb-fila-acciones">
                                <a class="nxb-accion" href="<?= site_url('settings/download_database/' . $c['nombre']); ?>"
                                   title="<?= lang('download'); ?>">
                                    <?= $icono($ico_baja); ?> <span><?= lang('download'); ?></span>
                                </a>
                                <a class="nxb-accion es-aviso restaurar"
                                   href="<?= site_url('settings/restore_database/' . $c['nombre'] . '?t=' . $token_accion); ?>"
                                   data-fecha="<?= html_escape($this->tec->hrld($c['fecha'])); ?>"
                                   title="<?= lang('restore'); ?>">
                                    <?= $icono($ico_volver); ?> <span><?= lang('restore'); ?></span>
                                </a>
                                <a class="nxb-accion es-peligro borrar"
                                   href="<?= site_url('settings/delete_database/' . $c['nombre'] . '?t=' . $token_accion); ?>"
                                   data-fecha="<?= html_escape($this->tec->hrld($c['fecha'])); ?>"
                                   title="<?= lang('delete'); ?>">
                                    <?= $icono($ico_borrar); ?> <span><?= lang('delete'); ?></span>
                                </a>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>

            <div class="nxf-note nxf-note-err" style="margin-top:16px">
                <?= $icono($ico_alerta, 17); ?>
                <div><?= lang('respaldo_restaurar_aviso'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Respaldar y restaurar tardan: el aviso queda mientras el archivo se genera
     o se aplica, porque la navegación no da señal de avance. -->
<div class="nxb-espera" id="nxbEspera" hidden>
    <div class="nxb-espera-caja">
        <span class="nxb-girador"></span>
        <b id="nxbEsperaTitulo"><?= lang('please_wait'); ?></b>
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
    .nxb-tile-dato{font-size:15px;font-weight:700;color:var(--nx-txt1);letter-spacing:-.01em}
    .nxb-tile-rotulo{
        margin-top:3px;font-size:11px;font-weight:600;text-transform:uppercase;
        letter-spacing:.07em;color:var(--nx-txt4);
    }

    .nxb-lista{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px}
    .nxb-fila{
        display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:13px 15px;
        border:1px solid var(--nx-border);border-radius:var(--nx-radius);background:var(--nx-card-bg2);
        transition:var(--nx-transition);
    }
    .nxb-fila:hover{border-color:var(--nx-border3)}
    .nxb-fila.es-ultima{border-color:var(--nx-border3);background:var(--nx-active-bg)}
    .nxb-fila-datos{flex:1 1 240px;min-width:0}
    .nxb-fila-fecha{display:flex;align-items:center;gap:9px;flex-wrap:wrap;font-weight:600;color:var(--nx-txt1)}
    .nxb-fila-meta{
        display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:4px;
        font-size:11.5px;color:var(--nx-txt4);
    }
    .nxb-mono{font-family:ui-monospace,"SFMono-Regular",Menlo,Consolas,monospace}
    .nxb-chip{
        font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;
        padding:2px 8px;border-radius:99px;border:1px solid var(--nx-border3);
        background:var(--nx-bg3);color:var(--nx-a1);
    }

    .nxb-fila-acciones{display:flex;gap:7px;flex-wrap:wrap}
    .nxb-accion{
        display:inline-flex;align-items:center;gap:7px;text-decoration:none;white-space:nowrap;
        font:600 12.5px inherit;font-family:inherit;padding:7px 12px;border-radius:9px;
        border:1px solid var(--nx-border);background:var(--nx-bg3);color:var(--nx-txt2);
        transition:var(--nx-transition);
    }
    .nxb-accion:hover{border-color:var(--nx-border3);color:var(--nx-txt1)}
    .nxb-accion.es-aviso:hover{border-color:rgba(234,179,8,.5);color:var(--nx-warn);background:rgba(234,179,8,.1)}
    .nxb-accion.es-peligro:hover{border-color:rgba(239,68,68,.5);color:var(--nx-err);background:rgba(239,68,68,.1)}
    @media (max-width:620px){ .nxb-accion span{display:none} }

    .nxb-vacio{display:flex;flex-direction:column;align-items:center;gap:12px;padding:34px 20px;color:var(--nx-txt4)}
    .nxb-vacio p{margin:0;font-size:13.5px;text-align:center;max-width:380px}

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

    var $velo   = document.getElementById('nxbEspera');
    var $titulo = document.getElementById('nxbEsperaTitulo');

    function esperar(texto) {
        $titulo.textContent = texto;
        $velo.hidden = false;
    }

    var $respaldar = document.getElementById('btnRespaldar');
    if ($respaldar) {
        $respaldar.addEventListener('click', function () {
            esperar(<?= json_encode(lang('backup_modal_heading')); ?>);
        });
    }

    document.addEventListener('click', function (e) {
        var restaurar = e.target.closest('.restaurar');
        if (restaurar) {
            e.preventDefault();
            var aviso = <?= json_encode(lang('respaldo_restaurar_confirma')); ?>;
            if (!confirm(aviso.replace('%s', restaurar.dataset.fecha))) { return; }
            esperar(<?= json_encode(lang('restore_modal_heading')); ?>);
            window.location.href = restaurar.getAttribute('href');
            return;
        }

        var borrar = e.target.closest('.borrar');
        if (borrar) {
            e.preventDefault();
            var texto = <?= json_encode(lang('respaldo_borrar_confirma')); ?>;
            if (!confirm(texto.replace('%s', borrar.dataset.fecha))) { return; }
            window.location.href = borrar.getAttribute('href');
        }
    });
})();
</script>
