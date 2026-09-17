<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Formulario compartido por suppliers/add y suppliers/edit.
 * $modo vale 'add' o 'edit'; en 'edit' llega ademas $supplier.
 *
 * Los nombres de campo antiguos (txtNombre, tcedula, …) se conservan porque el
 * modal de la factura electronica de compra envia a este mismo controlador.
 */
$s   = (isset($supplier) && $supplier) ? $supplier : NULL;
$val = function ($campo, $def = '') use ($s) {
    return set_value($campo, ($s && isset($s->$campo)) ? $s->$campo : $def);
};
$nuevo = ($modo === 'add');

// A diferencia del cliente, el proveedor sí puede ser "No Contribuyente" (06):
// en la factura electrónica de compra es el emisor (Anexos v4.4, nota 4 pie 17).
$tipos_id = array(
    '01' => lang('cedula_identidad'),
    '02' => lang('cedula_juridica'),
    '03' => 'DIMEX',
    '04' => 'NITE',
    '05' => lang('extranjero_no_domiciliado'),
    '06' => lang('no_contribuyente'),
);

// Codigos de la nota 6 del anexo v4.4: el 05 lo recauda un tercero y no
// describe cobro del proveedor, y el 08 y el 09 no existen en esa version.
$medios_pago = array(
    '01' => lang('medio_pago_01'),
    '02' => lang('medio_pago_02'),
    '03' => lang('medio_pago_03'),
    '04' => lang('medio_pago_04'),
    '06' => lang('medio_pago_06'),
    '07' => lang('medio_pago_07'),
    '99' => lang('medio_pago_99'),
);

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_buscar = '<circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/>';
$ico_alerta = '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>';
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';

$tipo_actual = $val('cf1', '02');
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= $nuevo ? lang('add_supplier') : lang('edit_supplier'); ?>
        <small><?= lang('proveedor_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('suppliers'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('suppliers'); ?>
        </a>
    </div>
</div>

<?= form_open($nuevo ? 'suppliers/add' : 'suppliers/edit/' . $s->id, 'id="nxfProveedor"'); ?>
<div class="nxf-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err">
            <?= $icono($ico_alerta, 17); ?>
            <div><?= $error; ?></div>
            <?php if (!empty($duplicado)) { ?>
                <a class="nxf-btn nxf-btn-sm nxf-btn-ghost" href="<?= site_url('suppliers/edit/' . $duplicado->id); ?>">
                    <?= lang('abrir_ese_proveedor'); ?>
                </a>
            <?php } ?>
        </div>
    <?php } ?>

    <!-- ── Paso 1: identificación fiscal ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title">
                <?= lang('proveedor_paso_identificacion'); ?>
                <small><?= lang('proveedor_paso_identificacion_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="tcedula"><?= lang('identificacion'); ?> <span class="req">*</span></label>
                    <select name="tcedula" id="tcedula" class="nxf-select" required>
                        <?php foreach ($tipos_id as $cod => $etiqueta) { ?>
                            <option value="<?= $cod; ?>" <?= ($tipo_actual === $cod) ? 'selected' : ''; ?>>
                                <?= $cod . ' — ' . $etiqueta; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-8">
                    <label class="nxf-label" for="txtIdentificacion"><?= lang('n_identificacion'); ?> <span class="req">*</span></label>
                    <div class="nxf-inline">
                        <input type="text" name="txtIdentificacion" id="txtIdentificacion" class="nxf-input mono"
                               autocomplete="off" inputmode="numeric" maxlength="20"
                               placeholder="<?= lang('placeholder_cedula'); ?>" value="<?= $val('cf2'); ?>">
                        <button type="button" class="nxf-btn" id="btnHacienda" title="<?= lang('consultar_hacienda_title'); ?>">
                            <span id="icoHacienda"><?= $icono($ico_buscar); ?></span>
                            <span id="txtHacienda"><?= lang('buscar_hacienda_btn'); ?></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="nxf-note" id="avisoCedula" hidden></div>
            <div class="nxf-note" id="avisoHacienda" hidden></div>
            <div class="nxf-note nxf-note-info" id="avisoSinPadron" hidden>
                <?= $icono($ico_alerta, 17); ?>
                <div><?= lang('no_contribuyente_ayuda'); ?></div>
            </div>
        </div>
    </div>

    <!-- ── Paso 2: datos del proveedor ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title"><?= lang('proveedor_paso_datos'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-7">
                    <label class="nxf-label" for="txtNombre"><?= lang('name'); ?> <span class="req">*</span></label>
                    <input type="text" name="txtNombre" id="txtNombre" class="nxf-input" maxlength="150" required
                           value="<?= $val('name'); ?>">
                </div>
                <div class="nxf-field sp-5">
                    <label class="nxf-label" for="company"><?= lang('nombre_comercial'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="company" id="company" class="nxf-input" maxlength="80"
                           value="<?= $val('company'); ?>">
                </div>

                <div class="nxf-field sp-5">
                    <label class="nxf-label" for="txtCodActEco">
                        <?= lang('cod_act_economica'); ?> <span class="req" id="reqActividad">*</span>
                    </label>
                    <input type="text" name="txtCodActEco" id="txtCodActEco" class="nxf-input mono" maxlength="6" required
                           placeholder="<?= lang('placeholder_cod_act'); ?>" value="<?= $val('actividad_economica'); ?>">
                </div>
                <div class="nxf-field sp-7" id="wrapActividades" hidden>
                    <label class="nxf-label" for="selActividades"><?= lang('actividad_seleccione'); ?></label>
                    <select id="selActividades" class="nxf-select"></select>
                    <div class="nxf-hint"><?= lang('actividades_registradas'); ?></div>
                </div>

                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="txtEmail"><?= lang('email_address'); ?> <span class="req">*</span></label>
                    <input type="email" name="txtEmail" id="txtEmail" class="nxf-input" maxlength="100" required
                           value="<?= $val('email'); ?>">
                </div>
                <div class="nxf-field sp-2">
                    <label class="nxf-label" for="codigo_pais_tel"><?= lang('cod_telefono'); ?></label>
                    <input type="text" name="codigo_pais_tel" id="codigo_pais_tel" class="nxf-input mono" maxlength="3"
                           inputmode="numeric" value="<?= $val('codigo_pais_tel', '506'); ?>">
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="txtTel"><?= lang('phone'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="txtTel" id="txtTel" class="nxf-input mono" maxlength="20"
                           inputmode="tel" value="<?= $val('phone'); ?>">
                </div>

                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="contacto_nombre"><?= lang('contacto_nombre'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="contacto_nombre" id="contacto_nombre" class="nxf-input" maxlength="100"
                           value="<?= $val('contacto_nombre'); ?>">
                </div>
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="contacto_telefono"><?= lang('contacto_telefono'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="contacto_telefono" id="contacto_telefono" class="nxf-input mono" maxlength="20"
                           inputmode="tel" value="<?= $val('contacto_telefono'); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 3: ubicación ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">3</span>
            <div class="nxf-card-title">
                <?= lang('proveedor_paso_ubicacion'); ?>
                <small><?= lang('proveedor_paso_ubicacion_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="codigo_provincia"><?= lang('provincia'); ?> <span class="req ubi-req">*</span></label>
                    <select name="codigo_provincia" id="codigo_provincia" class="nxf-select"
                            data-hijo="codigo_canton" data-url="<?= site_url('customers/get_cantones'); ?>">
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($provincias as $p) { ?>
                            <option value="<?= $p->codigo; ?>" <?= ($val('codigo_provincia') === $p->codigo) ? 'selected' : ''; ?>><?= html_escape($p->nombre); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="codigo_canton"><?= lang('canton'); ?> <span class="req ubi-req">*</span></label>
                    <select name="codigo_canton" id="codigo_canton" class="nxf-select"
                            data-hijo="codigo_distrito" data-url="<?= site_url('customers/get_distritos'); ?>">
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($cantones_actuales as $ct) { ?>
                            <option value="<?= $ct->codigo; ?>" <?= ($val('codigo_canton') === $ct->codigo) ? 'selected' : ''; ?>><?= html_escape($ct->nombre); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="codigo_distrito"><?= lang('distrito'); ?> <span class="req ubi-req">*</span></label>
                    <select name="codigo_distrito" id="codigo_distrito" class="nxf-select"
                            data-hijo="codigo_barrio" data-url="<?= site_url('customers/get_barrios'); ?>">
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($distritos_actuales as $d) { ?>
                            <option value="<?= $d->codigo; ?>" <?= ($val('codigo_distrito') === $d->codigo) ? 'selected' : ''; ?>><?= html_escape($d->nombre); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="codigo_barrio"><?= lang('barrio'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <select name="codigo_barrio" id="codigo_barrio" class="nxf-select">
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($barrios_actuales as $b) { ?>
                            <option value="<?= $b->codigo; ?>" <?= ($val('codigo_barrio') === $b->codigo) ? 'selected' : ''; ?>><?= html_escape($b->nombre); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="txtOtraSe"><?= lang('otras_senas'); ?> <span class="req ubi-req">*</span></label>
                    <textarea name="txtOtraSe" id="txtOtraSe" class="nxf-textarea" maxlength="250" minlength="5"><?= $val('otras_senas', $s ? $s->direccion : ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 4: condiciones comerciales ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">4</span>
            <div class="nxf-card-title">
                <?= lang('proveedor_paso_comercial'); ?>
                <small><?= lang('proveedor_paso_comercial_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="plazo_pago_dias"><?= lang('plazo_pago_dias'); ?></label>
                    <input type="number" name="plazo_pago_dias" id="plazo_pago_dias" class="nxf-input mono" min="0" max="365"
                           value="<?= $val('plazo_pago_dias', '0'); ?>">
                    <div class="nxf-hint"><?= lang('plazo_pago_dias_ayuda'); ?></div>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="medio_pago_habitual"><?= lang('medio_pago_habitual'); ?> <span class="req">*</span></label>
                    <select name="medio_pago_habitual" id="medio_pago_habitual" class="nxf-select" required>
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($medios_pago as $cod => $etiqueta) { ?>
                            <option value="<?= $cod; ?>" <?= ($val('medio_pago_habitual') === (string) $cod) ? 'selected' : ''; ?>>
                                <?= $cod . ' — ' . $etiqueta; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="moneda"><?= lang('moneda'); ?></label>
                    <select name="moneda" id="moneda" class="nxf-select">
                        <?php foreach (array('CRC', 'USD', 'EUR') as $mon) { ?>
                            <option value="<?= $mon; ?>" <?= ($val('moneda', 'CRC') === $mon) ? 'selected' : ''; ?>><?= $mon; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="activo"><?= lang('estado'); ?></label>
                    <select name="activo" id="activo" class="nxf-select">
                        <option value="1" <?= ($val('activo', '1') === '1') ? 'selected' : ''; ?>><?= lang('activo'); ?></option>
                        <option value="0" <?= ($val('activo', '1') === '0') ? 'selected' : ''; ?>><?= lang('inactivo'); ?></option>
                    </select>
                </div>

                <div class="nxf-field sp-6" id="wrapIban" hidden>
                    <label class="nxf-label" for="cuenta_iban"><?= lang('cuenta_iban'); ?> <span class="req">*</span></label>
                    <input type="text" name="cuenta_iban" id="cuenta_iban" class="nxf-input mono" maxlength="34"
                           placeholder="CR00000000000000000000" value="<?= $val('cuenta_iban'); ?>">
                </div>
                <div class="nxf-field sp-6" id="wrapSinpe" hidden>
                    <label class="nxf-label" for="sinpe_telefono"><?= lang('telefono_sinpe_movil'); ?> <span class="req">*</span></label>
                    <input type="text" name="sinpe_telefono" id="sinpe_telefono" class="nxf-input mono" maxlength="8"
                           inputmode="tel" placeholder="88887777" value="<?= $val('sinpe_telefono'); ?>">
                </div>
                <div class="nxf-field sp-6" id="wrapDetalle" hidden>
                    <label class="nxf-label" for="medio_pago_detalle">
                        <span id="rotuloDetalle"><?= lang('plataforma_cual'); ?></span> <span class="req">*</span>
                    </label>
                    <input type="text" name="medio_pago_detalle" id="medio_pago_detalle" class="nxf-input" maxlength="100"
                           list="listaPlataformas" autocomplete="off" value="<?= $val('medio_pago_detalle'); ?>">
                    <datalist id="listaPlataformas">
                        <?php foreach (plataformas_digitales() as $plataforma) { ?>
                            <option value="<?= html_escape($plataforma); ?>"></option>
                        <?php } ?>
                    </datalist>
                </div>

                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="notas"><?= lang('notas'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <textarea name="notas" id="notas" class="nxf-textarea" maxlength="1000"><?= $val('notas'); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('suppliers'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="submit" class="nxf-btn" name="<?= $nuevo ? 'add_supplier' : 'edit_supplier'; ?>" value="1">
            <?= $icono($ico_check); ?> <?= $nuevo ? lang('add_supplier') : lang('save'); ?>
        </button>
    </div>
</div>
<?= form_close(); ?>

<script>
(function () {
    'use strict';

    var URL_HACIENDA = '<?= site_url('hacienda_proxy/ae'); ?>/';
    var URL_BUSCAR   = '<?= site_url('suppliers/buscar_cedula'); ?>';
    var EXCLUIR      = <?= $nuevo ? '0' : (int) $s->id; ?>;

    var T = {
        largo:     <?= json_encode(lang('cedula_largo_invalido')); ?>,
        duplicada: <?= json_encode(lang('cedula_proveedor_repetida')); ?>,
        libre:     <?= json_encode(lang('cedula_libre')); ?>,
        abrir:     <?= json_encode(lang('abrir_ese_proveedor')); ?>,
        hallado:   <?= json_encode(lang('hacienda_encontrado')); ?>,
        situacion: <?= json_encode(lang('hacienda_alerta_situacion')); ?>,
        sinResp:   <?= json_encode(lang('hacienda_sin_respuesta')); ?>,
        buscar:    <?= json_encode(lang('buscar_hacienda_btn')); ?>,
        consultando: <?= json_encode(lang('consultando')); ?>,
        plataforma: <?= json_encode(lang('plataforma_cual')); ?>,
        otroMedio:  <?= json_encode(lang('medio_pago_otros_cual')); ?>
    };

    var $ = function (id) { return document.getElementById(id); };
    var tipo = $('tcedula'), ced = $('txtIdentificacion');
    var btn = $('btnHacienda'), ico = $('icoHacienda'), txt = $('txtHacienda');
    var avisoCed = $('avisoCedula'), avisoHac = $('avisoHacienda'), avisoSin = $('avisoSinPadron');

    // Ni el extranjero no domiciliado ni el no contribuyente están en el padrón:
    // no tienen actividad económica ni ubicación en el país (Anexos v4.4, nota 4).
    var SIN_PADRON = ['05', '06'];

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function nota(el, tono, html) {
        if (!el) { return; }
        el.className = 'nxf-note nxf-note-' + tono;
        el.innerHTML = html;
        el.hidden = false;
    }
    function ocultar(el) { if (el) { el.hidden = true; } }

    function sueltos() { return SIN_PADRON.indexOf(tipo.value) !== -1; }

    function limpio() {
        var v = ced.value.trim();
        return sueltos() ? v.replace(/[^A-Za-z0-9]/g, '').toUpperCase() : v.replace(/\D/g, '');
    }

    /* ── ¿Ya existe este proveedor? ── */
    var timer;
    function verificarDuplicado() {
        var v = limpio();
        if (!v) { ocultar(avisoCed); return; }

        fetch(URL_BUSCAR + '?cf2=' + encodeURIComponent(v) + '&excluir=' + EXCLUIR, {
            credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (res) {
                if (!res) { return; }
                if (res.existe) {
                    nota(avisoCed, 'err',
                        '<div><b>' + esc(T.duplicada) + '</b><br>' + esc(res.proveedor.name) +
                        ' — <span style="font-family:monospace">' + esc(res.proveedor.cf2) + '</span></div>' +
                        '<a class="nxf-btn nxf-btn-sm nxf-btn-ghost" href="' + esc(res.proveedor.url) + '">' + esc(T.abrir) + '</a>');
                } else {
                    nota(avisoCed, 'ok', '<div>' + esc(T.libre) + '</div>');
                }
            })
            .catch(function () { ocultar(avisoCed); });
    }

    /* ── Padrón de Hacienda ── */
    function cargando(activo) {
        btn.disabled = activo;
        txt.textContent = activo ? T.consultando : T.buscar;
        if (ico.firstChild && ico.firstChild.classList) { ico.firstChild.classList.toggle('nxf-spin', activo); }
    }

    function consultarHacienda() {
        var v = limpio().replace(/\D/g, '');
        if (v.length < 9 || v.length > 12) { nota(avisoHac, 'warn', '<div>' + esc(T.largo) + '</div>'); return; }

        cargando(true);
        ocultar(avisoHac);
        fetch(URL_HACIENDA + v, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                var d = res.d || {};
                if (!res.ok || d.error) { nota(avisoHac, 'warn', '<div>' + esc(d.error || T.sinResp) + '</div>'); return; }

                if (d.nombre) { $('txtNombre').value = d.nombre; }
                if (d.tipoIdentificacion) { tipo.value = d.tipoIdentificacion; aplicarTipo(); }

                var alertas = [];
                if (d.situacion) {
                    if (d.situacion.moroso) { alertas.push('MOROSO'); }
                    if (d.situacion.omiso) { alertas.push('OMISO'); }
                }
                nota(avisoHac, alertas.length ? 'warn' : 'ok',
                     '<div>' + esc(alertas.length ? T.situacion.replace('%s', alertas.join(', ')) : T.hallado) + '</div>');

                var acts = d.actividades || [];
                var wrap = $('wrapActividades'), sel = $('selActividades');
                if (acts.length === 1) {
                    $('txtCodActEco').value = acts[0].codigo;
                    wrap.hidden = true;
                } else if (acts.length > 1) {
                    sel.innerHTML = '';
                    acts.forEach(function (a) {
                        var o = document.createElement('option');
                        o.value = a.codigo;
                        o.textContent = a.codigo + ' — ' + a.descripcion;
                        sel.appendChild(o);
                    });
                    $('txtCodActEco').value = acts[0].codigo;
                    wrap.hidden = false;
                }
            })
            .catch(function () { nota(avisoHac, 'warn', '<div>' + esc(T.sinResp) + '</div>'); })
            .then(function () { cargando(false); });
    }

    btn.addEventListener('click', consultarHacienda);
    $('selActividades').addEventListener('change', function () { $('txtCodActEco').value = this.value; });

    ced.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(verificarDuplicado, 450);
    });
    ced.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); consultarHacienda(); }
    });

    function aplicarTipo() {
        var suelto = sueltos();
        avisoSin.hidden = (tipo.value !== '06');
        btn.disabled = suelto;
        ced.setAttribute('inputmode', suelto ? 'text' : 'numeric');

        // Ni el 05 ni el 06 tienen actividad ni ubicacion en el pais, y el XSD
        // pide la ubicacion completa o ninguna: los cuatro campos van juntos.
        $('txtCodActEco').required = !suelto;
        ['codigo_provincia', 'codigo_canton', 'codigo_distrito', 'txtOtraSe'].forEach(function (id) {
            $(id).required = !suelto;
        });
        document.querySelectorAll('.ubi-req').forEach(function (m) { m.hidden = suelto; });
        $('reqActividad').hidden = suelto;
    }
    tipo.addEventListener('change', function () { aplicarTipo(); verificarDuplicado(); });
    aplicarTipo();

    /* ── El medio de pago decide que dato hace falta ── */
    var medio = $('medio_pago_habitual');

    function exigeDe(codigo) {
        return { '04': 'iban', '06': 'sinpe', '07': 'plataforma', '99': 'otros' }[codigo] || '';
    }

    // El servidor descarta el dato del medio que no se eligio; dejarlo en
    // pantalla haria creer que ese IBAN sigue vigente.
    function alternar(wrap, campo, activo) {
        $(wrap).hidden = !activo;
        $(campo).required = activo;
        if (!activo) { $(campo).value = ''; }
    }

    function aplicarMedio() {
        var exige = exigeDe(medio.value);
        var detalle = (exige === 'plataforma' || exige === 'otros');

        alternar('wrapIban',    'cuenta_iban',        exige === 'iban');
        alternar('wrapSinpe',   'sinpe_telefono',     exige === 'sinpe');
        alternar('wrapDetalle', 'medio_pago_detalle', detalle);

        if (!detalle) { return; }
        $('rotuloDetalle').textContent = (exige === 'plataforma') ? T.plataforma : T.otroMedio;
        if (exige === 'plataforma') {
            $('medio_pago_detalle').setAttribute('list', 'listaPlataformas');
        } else {
            $('medio_pago_detalle').removeAttribute('list');
        }
    }
    medio.addEventListener('change', aplicarMedio);
    aplicarMedio();

    /* ── Ubicación en cascada ── */
    document.querySelectorAll('[data-hijo]').forEach(function (padre) {
        padre.addEventListener('change', function () {
            var hijo = $(padre.dataset.hijo);
            var vacia = '<option value="">— <?= lang('Seleccione'); ?> —</option>';

            var actual = hijo;
            while (actual) {
                actual.innerHTML = vacia;
                actual = actual.dataset.hijo ? $(actual.dataset.hijo) : null;
            }
            if (!padre.value) { return; }

            var ruta = [$('codigo_provincia').value];
            if (padre.id !== 'codigo_provincia') { ruta.push($('codigo_canton').value); }
            if (padre.id === 'codigo_distrito') { ruta.push($('codigo_distrito').value); }

            fetch(padre.dataset.url + '/' + ruta.join('/'), {
                credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (filas) {
                    filas.forEach(function (f) {
                        var o = document.createElement('option');
                        o.value = f.codigo;
                        o.textContent = f.nombre;
                        hijo.appendChild(o);
                    });
                })
                .catch(function () {});
        });
    });
})();
</script>
