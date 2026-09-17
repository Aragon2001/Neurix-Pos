<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Formulario compartido por categories/add y categories/edit.
 * $modo vale 'add' o 'edit'; en 'edit' llegan ademas $category y $productos.
 */
$c     = (isset($category) && $category) ? $category : NULL;
$nuevo = ($modo === 'add');
$usos  = isset($productos) ? (int) $productos : 0;

$val = function ($campo, $def = '') use ($c) {
    return set_value($campo, ($c && isset($c->$campo)) ? $c->$campo : $def);
};

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_alerta = '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>';
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_imagen = '<path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"/><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"/>';

$imagen_actual = ($c && !empty($c->image) && is_file('uploads/' . $c->image))
    ? base_url('uploads/' . $c->image)
    : NULL;
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= $nuevo ? lang('add_category') : lang('edit_category'); ?>
        <small><?= $nuevo ? lang('cat_datos_ayuda') : lang('update_info'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('categories'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('categories'); ?>
        </a>
    </div>
</div>

<?= form_open_multipart($nuevo ? 'categories/add' : 'categories/edit/' . $c->id, array('id' => 'nxfCategoria')); ?>
<div class="nxf-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err">
            <?= $icono($ico_alerta, 17); ?>
            <div><?= $error; ?></div>
        </div>
    <?php } ?>

    <!-- ── Paso 1: los datos ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title">
                <?= lang('cat_datos'); ?>
                <small><?= lang('cat_datos_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="code">
                        <?= lang('category_code'); ?> <span class="req">*</span>
                    </label>
                    <input type="text" name="code" id="code" class="nxf-input mono" required
                           maxlength="30" autocomplete="off" spellcheck="false"
                           placeholder="BEB" value="<?= html_escape($val('code')); ?>">
                    <div class="nxf-hint"><?= lang('cat_codigo_ayuda'); ?></div>
                </div>
                <div class="nxf-field sp-8">
                    <label class="nxf-label" for="name">
                        <?= lang('category_name'); ?> <span class="req">*</span>
                    </label>
                    <input type="text" name="name" id="name" class="nxf-input" required
                           maxlength="100" autocomplete="off"
                           placeholder="<?= lang('category_name'); ?>" value="<?= html_escape($val('name')); ?>">
                    <div class="nxf-hint"><?= lang('cat_nombre_ayuda'); ?></div>
                </div>
            </div>

            <?php if (!$nuevo) { ?>
                <div class="nxf-note <?= $usos ? 'nxf-note-info' : ''; ?>" style="margin-top:14px">
                    <?= $icono($usos ? $ico_check : $ico_alerta, 17); ?>
                    <div><?= $usos ? sprintf(lang('cat_en_uso'), $usos) : lang('cat_sin_uso'); ?></div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ── Paso 2: la imagen ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title">
                <?= lang('cat_imagen_paso'); ?>
                <small><?= lang('cat_imagen_paso_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="cat-foto">
                <div class="cat-previo" id="catPrevio">
                    <?php if ($imagen_actual) { ?>
                        <img src="<?= $imagen_actual; ?>" alt="">
                    <?php } else { ?>
                        <span class="cat-previo-vacio"><?= $icono($ico_imagen, 26); ?></span>
                    <?php } ?>
                </div>

                <div class="cat-foto-campo">
                    <label class="cat-zona" id="catZona" for="userfile">
                        <input type="file" name="userfile" id="userfile" accept="image/png,image/jpeg,image/gif,image/webp">
                        <span class="cat-zona-txt">
                            <b id="catNombre"><?= lang('cat_imagen'); ?></b>
                            <small id="catDetalle"><?= lang('cat_imagen_ayuda'); ?></small>
                        </span>
                        <span class="cat-zona-btn"><?= lang('import_elegir'); ?></span>
                    </label>

                    <?php if ($imagen_actual) { ?>
                        <label class="cat-quitar">
                            <input type="checkbox" name="quitar_imagen" value="1" id="catQuitar">
                            <span><?= lang('cat_quitar_imagen'); ?></span>
                        </label>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('categories'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="submit" class="nxf-btn">
            <?= $icono($ico_check); ?> <?= $nuevo ? lang('add_category') : lang('edit_category'); ?>
        </button>
    </div>
</div>
<?= form_close(); ?>

<style>
    .cat-foto{display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap}
    .cat-previo{
        flex:0 0 auto;width:118px;height:118px;border-radius:var(--nx-radius);overflow:hidden;
        display:grid;place-items:center;
        border:1px solid var(--nx-border);background:var(--nx-bg3);color:var(--nx-txt4);
    }
    .cat-previo img{width:100%;height:100%;object-fit:cover}
    .cat-previo.apagado{opacity:.35}
    .cat-foto-campo{flex:1 1 300px;min-width:0;display:flex;flex-direction:column;gap:12px}

    .cat-zona{
        position:relative;display:flex;align-items:center;gap:14px;padding:18px;cursor:pointer;
        border:1.5px dashed var(--nx-border3);border-radius:var(--nx-radius);
        background:var(--nx-input-bg);transition:var(--nx-transition);
    }
    .cat-zona:hover,.cat-zona.encima{background:var(--nx-input-focus);border-color:var(--nx-a1)}
    .cat-zona.cargado{border-style:solid;border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.07)}
    .cat-zona input[type=file]{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
    .cat-zona-txt{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
    .cat-zona-txt b{font-size:14px;font-weight:600;color:var(--nx-txt1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .cat-zona-txt small{font-size:12px;color:var(--nx-txt4)}
    .cat-zona-btn{
        flex:0 0 auto;font:600 12.5px inherit;font-family:inherit;padding:8px 14px;border-radius:9px;
        border:1px solid var(--nx-border3);color:var(--nx-a1);background:var(--nx-active-bg);
    }

    .cat-quitar{display:flex;align-items:center;gap:9px;font-size:13px;color:var(--nx-txt3);cursor:pointer}
    .cat-quitar input{width:16px;height:16px;accent-color:var(--nx-err);cursor:pointer}
</style>

<script>
(function () {
    'use strict';

    var LIMITE_MB = 2;
    var T = {
        titulo:  <?= json_encode(lang('cat_imagen')); ?>,
        ayuda:   <?= json_encode(lang('cat_imagen_ayuda')); ?>,
        noEsImg: <?= json_encode(lang('cat_imagen_ayuda')); ?>,
        pesado:  <?= json_encode(lang('import_muy_grande')); ?>
    };

    var $zona    = document.getElementById('catZona');
    var $input   = document.getElementById('userfile');
    var $nombre  = document.getElementById('catNombre');
    var $detalle = document.getElementById('catDetalle');
    var $previo  = document.getElementById('catPrevio');
    var $quitar  = document.getElementById('catQuitar');

    // La vista previa se hace con un blob local: nunca sube nada hasta enviar.
    var urlPrevia = null;

    function tamano(bytes) {
        return bytes < 1024 * 1024
            ? Math.max(1, Math.round(bytes / 1024)) + ' KB'
            : (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    function soltarPrevia() {
        if (urlPrevia) { URL.revokeObjectURL(urlPrevia); urlPrevia = null; }
    }

    function limpiar(mensaje) {
        $zona.classList.remove('cargado');
        $nombre.textContent = T.titulo;
        $detalle.textContent = mensaje || T.ayuda;
        $input.value = '';
        soltarPrevia();
    }

    function revisar() {
        var f = $input.files && $input.files[0];
        if (!f) { limpiar(); return; }
        if (!/^image\//.test(f.type)) { limpiar(T.noEsImg); return; }
        if (f.size > LIMITE_MB * 1024 * 1024) { limpiar(T.pesado.replace('%s', LIMITE_MB)); return; }

        $zona.classList.add('cargado');
        $nombre.textContent = f.name;
        $detalle.textContent = tamano(f.size);

        soltarPrevia();
        urlPrevia = URL.createObjectURL(f);
        $previo.innerHTML = '<img alt="">';
        $previo.firstChild.src = urlPrevia;
        $previo.classList.remove('apagado');
        if ($quitar) { $quitar.checked = false; }
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

    if ($quitar) {
        $quitar.addEventListener('change', function () {
            $previo.classList.toggle('apagado', this.checked);
            if (this.checked) { limpiar(); }
        });
    }

    /* El código viaja tal cual a `categories`.`code`: sin espacios ni acentos. */
    var $code = document.getElementById('code');
    $code.addEventListener('input', function () {
        var limpio = this.value.toUpperCase().replace(/[^A-Z0-9._-]/g, '');
        if (limpio !== this.value) { this.value = limpio; }
    });
})();
</script>
