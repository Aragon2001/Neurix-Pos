<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Formulario compartido por settings/add_store y settings/edit_store.
 * $modo vale 'add' o 'edit'; en 'edit' llega ademas $store.
 */
$s     = (isset($store) && $store) ? $store : NULL;
$nuevo = ($modo === 'add');

$val = function ($campo, $def = '') use ($s) {
    return set_value($campo, ($s && isset($s->$campo)) ? $s->$campo : $def);
};

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_alerta = '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>';
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_imagen = '<path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"/><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"/>';

$logo_actual = ($s && !empty($s->logo) && is_file('uploads/' . $s->logo))
    ? base_url('uploads/' . $s->logo)
    : NULL;
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= $nuevo ? lang('add_store') : lang('edit_store'); ?>
        <small><?= $nuevo ? lang('tienda_identidad_ayuda') : lang('update_info'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('settings/stores'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('stores'); ?>
        </a>
    </div>
</div>

<?= form_open_multipart($nuevo ? 'settings/add_store' : 'settings/edit_store/' . $s->id, array('id' => 'nxfTienda')); ?>
<div class="nxf-page nxs-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err">
            <?= $icono($ico_alerta, 17); ?>
            <div><?= $error; ?></div>
        </div>
    <?php } ?>

    <!-- ── Paso 1: identidad ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title">
                <?= lang('tienda_identidad'); ?>
                <small><?= lang('tienda_identidad_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-8">
                    <label class="nxf-label" for="name"><?= lang('name'); ?> <span class="req">*</span></label>
                    <input type="text" name="name" id="name" class="nxf-input" required maxlength="100"
                           autocomplete="organization" value="<?= html_escape($val('name')); ?>">
                    <div class="nxf-hint"><?= lang('tienda_nombre_ayuda'); ?></div>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="code"><?= lang('code'); ?> <span class="req">*</span></label>
                    <input type="text" name="code" id="code" class="nxf-input mono" required
                           minlength="2" maxlength="20" autocomplete="off" spellcheck="false"
                           placeholder="001" value="<?= html_escape($val('code')); ?>">
                    <div class="nxf-hint" id="pistaCodigo"><?= lang('tienda_codigo_ayuda'); ?></div>
                </div>

                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="phone"><?= lang('phone'); ?> <span class="req">*</span></label>
                    <input type="text" name="phone" id="phone" class="nxf-input mono" required maxlength="20"
                           inputmode="tel" autocomplete="tel" placeholder="2222-2222"
                           value="<?= html_escape($val('phone')); ?>">
                </div>
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="email_address">
                        <?= lang('email_address'); ?><span class="opt"><?= lang('opcional'); ?></span>
                    </label>
                    <input type="email" name="email" id="email_address" class="nxf-input" maxlength="100"
                           autocomplete="email" value="<?= html_escape($val('email')); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 2: logo ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title">
                <?= lang('tienda_logo'); ?>
                <small><?= lang('tienda_logo_paso_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxs-foto">
                <div class="nxs-previo" id="tdaPrevio">
                    <?php if ($logo_actual) { ?>
                        <img src="<?= $logo_actual; ?>" alt="">
                    <?php } else { ?>
                        <span class="nxs-previo-vacio"><?= $icono($ico_imagen, 26); ?></span>
                    <?php } ?>
                </div>

                <div class="nxs-foto-campo">
                    <label class="nxs-zona" id="tdaZona" for="logo">
                        <input type="file" name="userfile" id="logo" accept="image/png,image/jpeg,image/gif">
                        <span class="nxs-zona-txt">
                            <b id="tdaNombre"><?= lang('logo'); ?></b>
                            <small id="tdaDetalle"><?= lang('tienda_logo_ayuda'); ?></small>
                        </span>
                        <span class="nxs-zona-btn"><?= lang('import_elegir'); ?></span>
                    </label>
                    <div class="nxf-note nxf-note-warn" id="tdaAvisoLogo" hidden></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 3: dirección ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">3</span>
            <div class="nxf-card-title">
                <?= lang('tienda_direccion'); ?>
                <small><?= lang('tienda_direccion_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="address1"><?= lang('address1'); ?></label>
                    <input type="text" name="address1" id="address1" class="nxf-input" maxlength="150"
                           value="<?= html_escape($val('address1')); ?>">
                </div>
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="address2">
                        <?= lang('address2'); ?><span class="opt"><?= lang('opcional'); ?></span>
                    </label>
                    <input type="text" name="address2" id="address2" class="nxf-input" maxlength="150"
                           value="<?= html_escape($val('address2')); ?>">
                </div>

                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="city"><?= lang('city'); ?></label>
                    <input type="text" name="city" id="city" class="nxf-input" maxlength="100"
                           value="<?= html_escape($val('city')); ?>">
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="state"><?= lang('state'); ?></label>
                    <input type="text" name="state" id="state" class="nxf-input" maxlength="100"
                           value="<?= html_escape($val('state')); ?>">
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="postal_code">
                        <?= lang('postal_code'); ?><span class="opt"><?= lang('opcional'); ?></span>
                    </label>
                    <input type="text" name="postal_code" id="postal_code" class="nxf-input mono" maxlength="20"
                           inputmode="numeric" value="<?= html_escape($val('postal_code')); ?>">
                </div>

                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="country"><?= lang('country'); ?></label>
                    <input type="text" name="country" id="country" class="nxf-input" maxlength="100"
                           value="<?= html_escape($val('country', $nuevo ? 'Costa Rica' : '')); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 4: tiquete ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">4</span>
            <div class="nxf-card-title">
                <?= lang('tienda_tiquete'); ?>
                <small><?= lang('tienda_tiquete_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxs-tiquete">
                <div class="nxs-tiquete-campos">
                    <div class="nxf-field">
                        <label class="nxf-label" for="receipt_header">
                            <?= lang('receipt_header'); ?>
                            <span class="opt" id="cuentaCab">0 / 240</span>
                        </label>
                        <textarea name="receipt_header" id="receipt_header" class="nxf-textarea"
                                  maxlength="240" rows="3"><?= html_escape($val('receipt_header')); ?></textarea>
                        <div class="nxf-hint"><?= lang('tienda_encabezado_ayuda'); ?></div>
                    </div>

                    <div class="nxf-field">
                        <label class="nxf-label" for="receipt_footer">
                            <?= lang('receipt_footer'); ?>
                            <span class="opt" id="cuentaPie">0 / 240</span>
                        </label>
                        <textarea name="receipt_footer" id="receipt_footer" class="nxf-textarea"
                                  maxlength="240" rows="3"><?= html_escape($val('receipt_footer')); ?></textarea>
                        <div class="nxf-hint"><?= lang('tienda_pie_ayuda'); ?></div>
                    </div>
                </div>

                <!-- Solo muestra como quedaria: el tiquete real lo arma PosPrint. -->
                <figure class="nxs-papel">
                    <figcaption><?= lang('tienda_previa'); ?></figcaption>
                    <div class="nxs-papel-hoja">
                        <div class="nxs-papel-logo" id="pvLogo"<?= $logo_actual ? '' : ' hidden'; ?>>
                            <?php if ($logo_actual) { ?><img src="<?= $logo_actual; ?>" alt=""><?php } ?>
                        </div>
                        <div class="nxs-papel-nombre" id="pvNombre"></div>
                        <div class="nxs-papel-linea" id="pvDireccion"></div>
                        <div class="nxs-papel-linea" id="pvContacto"></div>
                        <div class="nxs-papel-cab" id="pvCab"></div>
                        <div class="nxs-papel-regla"></div>
                        <div class="nxs-papel-fila"><span>1 &times; <?= lang('tienda_previa_articulo'); ?></span><span>1 000,00</span></div>
                        <div class="nxs-papel-regla"></div>
                        <div class="nxs-papel-fila nxs-papel-total"><span><?= lang('total'); ?></span><span>1 000,00</span></div>
                        <div class="nxs-papel-pie" id="pvPie"></div>
                    </div>
                </figure>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('settings/stores'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="submit" class="nxf-btn" name="<?= $nuevo ? 'add_store' : 'edit_store'; ?>" value="1">
            <?= $icono($ico_check); ?> <?= $nuevo ? lang('add_store') : lang('save'); ?>
        </button>
    </div>
</div>
<?= form_close(); ?>

<style>
    /* ── Logo ── */
    .nxs-foto{display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap}
    .nxs-previo{
        flex:0 0 auto;width:186px;height:82px;border-radius:var(--nx-radius);overflow:hidden;padding:8px;
        display:grid;place-items:center;
        border:1px solid var(--nx-border);background:var(--nx-bg3);color:var(--nx-txt4);
    }
    .nxs-previo img{max-width:100%;max-height:100%;object-fit:contain}
    .nxs-foto-campo{flex:1 1 300px;min-width:0;display:flex;flex-direction:column;gap:12px}

    .nxs-zona{
        position:relative;display:flex;align-items:center;gap:14px;padding:18px;cursor:pointer;
        border:1.5px dashed var(--nx-border3);border-radius:var(--nx-radius);
        background:var(--nx-input-bg);transition:var(--nx-transition);
    }
    .nxs-zona:hover,.nxs-zona.encima{background:var(--nx-input-focus);border-color:var(--nx-a1)}
    .nxs-zona.cargado{border-style:solid;border-color:rgba(34,197,94,.45);background:rgba(34,197,94,.07)}
    .nxs-zona input[type=file]{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
    .nxs-zona-txt{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
    .nxs-zona-txt b{font-size:14px;font-weight:600;color:var(--nx-txt1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .nxs-zona-txt small{font-size:12px;color:var(--nx-txt4)}
    .nxs-zona-btn{
        flex:0 0 auto;font:600 12.5px inherit;font-family:inherit;padding:8px 14px;border-radius:9px;
        border:1px solid var(--nx-border3);color:var(--nx-a1);background:var(--nx-active-bg);
    }

    /* ── Tiquete: campos a la izquierda, papel a la derecha ── */
    .nxs-tiquete{display:flex;gap:22px;align-items:flex-start;flex-wrap:wrap}
    .nxs-tiquete-campos{flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:15px}

    .nxs-papel{flex:0 0 auto;width:250px;margin:0;display:flex;flex-direction:column;gap:8px}
    .nxs-papel figcaption{
        font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--nx-txt3);
    }
    /* El papel imita el rollo de 80 mm: va claro en los dos temas. */
    .nxs-papel-hoja{
        background:#f8fafc;color:#0f172a;border-radius:var(--nx-radius-sm);
        border:1px solid var(--nx-border);box-shadow:var(--nx-shadow-sm);
        padding:16px 14px;font:400 11.5px ui-monospace,"SFMono-Regular",Menlo,Consolas,monospace;
        line-height:1.55;text-align:center;overflow-wrap:anywhere;
    }
    .nxs-papel-logo{margin-bottom:8px}
    .nxs-papel-logo img{max-width:120px;max-height:46px;object-fit:contain}
    .nxs-papel-nombre{font-weight:700;font-size:13px;letter-spacing:.02em}
    .nxs-papel-linea{color:#475569}
    .nxs-papel-cab,.nxs-papel-pie{white-space:pre-wrap;margin-top:8px;color:#334155}
    .nxs-papel-pie{margin-top:10px}
    .nxs-papel-regla{border-top:1px dashed #94a3b8;margin:10px 0}
    .nxs-papel-fila{display:flex;justify-content:space-between;gap:10px;text-align:left}
    .nxs-papel-total{font-weight:700}

    @media (max-width:860px){ .nxs-papel{width:100%} }
</style>

<script>
(function () {
    'use strict';

    var LIMITE_KB = 500, ANCHO_MAX = 300, ALTO_MAX = 100;
    var T = {
        titulo:  <?= json_encode(lang('logo')); ?>,
        ayuda:   <?= json_encode(lang('tienda_logo_ayuda')); ?>,
        noEsImg: <?= json_encode(lang('tienda_logo_formato')); ?>,
        pesado:  <?= json_encode(lang('tienda_logo_pesado')); ?>,
        grande:  <?= json_encode(lang('tienda_logo_grande')); ?>,
        libre:   <?= json_encode(lang('tienda_codigo_libre')); ?>,
        usado:   <?= json_encode(lang('tienda_codigo_usado')); ?>,
        pista:   <?= json_encode(lang('tienda_codigo_ayuda')); ?>,
        sinNom:  <?= json_encode(lang('name')); ?>
    };

    /* ── Logo: vista previa local y las mismas cotas que valida el servidor ── */
    var $zona    = document.getElementById('tdaZona');
    var $input   = document.getElementById('logo');
    var $nombre  = document.getElementById('tdaNombre');
    var $detalle = document.getElementById('tdaDetalle');
    var $previo  = document.getElementById('tdaPrevio');
    var $aviso   = document.getElementById('tdaAvisoLogo');
    var $pvLogo  = document.getElementById('pvLogo');
    var urlPrevia = null;

    function tamano(bytes) {
        return bytes < 1024 * 1024
            ? Math.max(1, Math.round(bytes / 1024)) + ' KB'
            : (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    function avisar(texto) {
        $aviso.textContent = texto || '';
        $aviso.hidden = !texto;
    }

    function soltarPrevia() {
        if (urlPrevia) { URL.revokeObjectURL(urlPrevia); urlPrevia = null; }
    }

    function limpiar(mensaje) {
        $zona.classList.remove('cargado');
        $nombre.textContent = T.titulo;
        $detalle.textContent = T.ayuda;
        $input.value = '';
        soltarPrevia();
        avisar(mensaje);
    }

    function pintarPrevia(src) {
        $previo.innerHTML = '<img alt="">';
        $previo.firstChild.src = src;
        $pvLogo.innerHTML = '<img alt="">';
        $pvLogo.firstChild.src = src;
        $pvLogo.hidden = false;
    }

    function revisar() {
        var f = $input.files && $input.files[0];
        if (!f) { limpiar(); return; }
        if (!/^image\/(png|jpeg|gif)$/.test(f.type)) { limpiar(T.noEsImg); return; }
        if (f.size > LIMITE_KB * 1024) { limpiar(T.pesado.replace('%s', LIMITE_KB)); return; }

        $zona.classList.add('cargado');
        $nombre.textContent = f.name;
        $detalle.textContent = tamano(f.size);
        avisar('');

        soltarPrevia();
        urlPrevia = URL.createObjectURL(f);
        pintarPrevia(urlPrevia);

        // El servidor rechaza el archivo por encima de 300 x 100 px y solo deja
        // un flashdata: avisar antes evita perder lo escrito en el formulario.
        var medidor = new Image();
        medidor.onload = function () {
            $detalle.textContent = tamano(f.size) + ' · ' + medidor.width + ' × ' + medidor.height + ' px';
            if (medidor.width > ANCHO_MAX || medidor.height > ALTO_MAX) {
                avisar(T.grande.replace('%s', ANCHO_MAX).replace('%s', ALTO_MAX));
            }
        };
        medidor.src = urlPrevia;
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

    /* ── Vista previa del tiquete ── */
    var campos = {
        name: document.getElementById('name'),
        address1: document.getElementById('address1'),
        address2: document.getElementById('address2'),
        city: document.getElementById('city'),
        state: document.getElementById('state'),
        phone: document.getElementById('phone'),
        email: document.getElementById('email_address'),
        cab: document.getElementById('receipt_header'),
        pie: document.getElementById('receipt_footer')
    };
    var pv = {
        nombre: document.getElementById('pvNombre'),
        direccion: document.getElementById('pvDireccion'),
        contacto: document.getElementById('pvContacto'),
        cab: document.getElementById('pvCab'),
        pie: document.getElementById('pvPie')
    };
    var $cuentaCab = document.getElementById('cuentaCab');
    var $cuentaPie = document.getElementById('cuentaPie');

    function unir(lista, sep) {
        return lista.map(function (c) { return c && c.value.trim(); })
                    .filter(Boolean).join(sep);
    }

    function repintar() {
        pv.nombre.textContent    = campos.name.value.trim() || T.sinNom;
        pv.direccion.textContent = unir([campos.address1, campos.address2, campos.city, campos.state], ', ');
        pv.contacto.textContent  = unir([campos.phone, campos.email], ' · ');
        pv.cab.textContent       = campos.cab.value.trim();
        pv.pie.textContent       = campos.pie.value.trim();
        $cuentaCab.textContent   = campos.cab.value.length + ' / 240';
        $cuentaPie.textContent   = campos.pie.value.length + ' / 240';
    }

    Object.keys(campos).forEach(function (k) {
        if (campos[k]) { campos[k].addEventListener('input', repintar); }
    });
    repintar();

    /* ── Código: viaja tal cual a `stores`.`code` y la tabla lo exige único ── */
    var $code  = document.getElementById('code');
    var $pista = document.getElementById('pistaCodigo');
    var codigoOriginal = $code.value;
    var espera = null;

    function consultarCodigo() {
        var codigo = $code.value;
        fetch(<?= json_encode(site_url('settings/codigo_tienda_libre')); ?> +
              '?code=' + encodeURIComponent(codigo) +
              '&id=' + encodeURIComponent(<?= json_encode($nuevo ? '' : (string) $s->id); ?>))
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d || $code.value !== codigo) { return; }
                $code.classList.toggle('is-good', !!d.libre);
                $code.classList.toggle('is-bad', !d.libre);
                $pista.textContent = d.libre ? T.libre : T.usado;
            })
            .catch(function () { /* sin red, la unicidad la resuelve el servidor */ });
    }

    $code.addEventListener('input', function () {
        var limpio = this.value.toUpperCase().replace(/[^A-Z0-9._-]/g, '');
        if (limpio !== this.value) { this.value = limpio; }

        clearTimeout(espera);
        this.classList.remove('is-good', 'is-bad');
        $pista.textContent = T.pista;
        if (this.value.length < 2 || this.value === codigoOriginal) { return; }
        espera = setTimeout(consultarCodigo, 350);
    });
})();
</script>
