<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Formulario compartido por customers/add y customers/edit.
 * $modo vale 'add' o 'edit'; en 'edit' llega ademas $customer.
 */
$c   = (isset($customer) && $customer) ? $customer : NULL;
$val = function ($campo, $def = '') use ($c) {
    return set_value($campo, ($c && isset($c->$campo)) ? $c->$campo : $def);
};
$nuevo = ($modo === 'add');

// El codigo 06 "No Contribuyente" de la nota 4 no se ofrece: Hacienda solo lo
// admite en el emisor de una factura electronica de compra, nunca en el receptor
// de un comprobante de venta (Anexos v4.4, nota 4 pie 17).
$tipos_id = array(
    '01' => lang('cedula_identidad'),
    '02' => lang('cedula_juridica'),
    '03' => 'DIMEX',
    '04' => 'NITE',
    '05' => lang('extranjero_no_domiciliado'),
);

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_buscar = '<circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/>';
$ico_alerta = '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>';
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= $nuevo ? lang('add_customer') : lang('edit_customer'); ?>
        <small><?= $nuevo ? lang('cliente_paso_identificacion_ayuda') : lang('update_info'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('customers'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('customers'); ?>
        </a>
    </div>
</div>

<?= form_open($nuevo ? 'customers/add' : 'customers/edit/' . $c->id, 'id="nxfCliente"'); ?>
<div class="nxf-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err">
            <?= $icono($ico_alerta, 17); ?>
            <div><?= $error; ?></div>
            <?php if (!empty($duplicado)) { ?>
                <a class="nxf-btn nxf-btn-sm nxf-btn-ghost" href="<?= site_url('customers/edit/' . $duplicado->id); ?>">
                    <?= lang('abrir_ese_cliente'); ?>
                </a>
            <?php } ?>
        </div>
    <?php } ?>

    <!-- ── Paso 1: identificación ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title">
                <?= lang('cliente_paso_identificacion'); ?>
                <small><?= lang('cliente_paso_identificacion_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="cf1"><?= lang('ccf1'); ?></label>
                    <select name="cf1" id="cf1" class="nxf-select">
                        <?php foreach ($tipos_id as $cod => $etiqueta) { ?>
                            <option value="<?= $cod; ?>" <?= ($val('cf1', '01') === $cod) ? 'selected' : ''; ?>>
                                <?= $cod . ' — ' . $etiqueta; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-8">
                    <label class="nxf-label" for="cf2">
                        <?= lang('ccf2'); ?>
                        <span class="opt"><?= lang('opcional'); ?></span>
                    </label>
                    <div class="nxf-inline">
                        <input type="text" name="cf2" id="cf2" class="nxf-input mono" autocomplete="off"
                               inputmode="numeric" maxlength="20"
                               placeholder="<?= lang('placeholder_cedula'); ?>" value="<?= $val('cf2'); ?>">
                        <button type="button" class="nxf-btn" id="btnHacienda"
                                title="<?= lang('consultar_hacienda_title'); ?>">
                            <span id="icoHacienda"><?= $icono($ico_buscar); ?></span>
                            <span id="txtHacienda"><?= lang('buscar_hacienda_btn'); ?></span>
                        </button>
                    </div>
                    <div class="nxf-hint"><?= lang('autocomplete_padron'); ?></div>
                </div>
            </div>

            <div class="nxf-note" id="avisoCedula" hidden></div>
            <div class="nxf-note" id="avisoHacienda" hidden></div>

            <?php if ($nuevo) { ?>
                <div class="nxf-note nxf-note-info" id="avisoSinCedula">
                    <?= $icono($ico_alerta, 17); ?>
                    <div><?= lang('cliente_sin_cedula_ayuda'); ?></div>
                    <button type="button" class="nxf-btn nxf-btn-sm nxf-btn-ghost" id="btnSinCedula">
                        <?= lang('continuar_sin_cedula'); ?>
                    </button>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ── Paso 2: datos del cliente ── -->
    <div class="nxf-card<?= $nuevo ? ' is-locked' : ''; ?>" data-bloqueable>
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title"><?= lang('cliente_paso_datos'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-7">
                    <label class="nxf-label" for="name"><?= lang('name'); ?> <span class="req">*</span></label>
                    <input type="text" name="name" id="name" class="nxf-input" maxlength="100" required
                           value="<?= $val('name'); ?>">
                </div>
                <div class="nxf-field sp-5">
                    <label class="nxf-label" for="business_name">
                        <?= lang('nombre_comercial'); ?><span class="opt"><?= lang('opcional'); ?></span>
                    </label>
                    <input type="text" name="business_name" id="business_name" class="nxf-input" maxlength="80"
                           value="<?= $val('business_name'); ?>">
                </div>

                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="email"><?= lang('email_address'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="email" name="email" id="email" class="nxf-input" maxlength="160"
                           value="<?= $val('email'); ?>">
                </div>
                <div class="nxf-field sp-2">
                    <label class="nxf-label" for="cod_telefono"><?= lang('cod_telefono'); ?></label>
                    <input type="text" name="cod_telefono" id="cod_telefono" class="nxf-input mono" maxlength="3"
                           inputmode="numeric" value="<?= $val('cod_telefono', '506'); ?>">
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="phone"><?= lang('phone'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="phone" id="phone" class="nxf-input mono" maxlength="20"
                           inputmode="tel" value="<?= $val('phone'); ?>">
                </div>

                <div class="nxf-field sp-5">
                    <label class="nxf-label" for="codigo_actividad"><?= lang('cod_act_economica_label'); ?></label>
                    <input type="text" name="codigo_actividad" id="codigo_actividad" class="nxf-input mono"
                           maxlength="6" placeholder="<?= lang('placeholder_cod_act'); ?>"
                           value="<?= $val('codigo_actividad'); ?>">
                    <div class="nxf-hint"><?= lang('cod_act_economica_help'); ?></div>
                </div>
                <div class="nxf-field sp-7" id="wrapActividades" hidden>
                    <label class="nxf-label" for="selActividades"><?= lang('actividad_seleccione'); ?></label>
                    <select id="selActividades" class="nxf-select"></select>
                    <div class="nxf-hint"><?= lang('actividades_registradas'); ?></div>
                </div>

                <!-- Todas las actividades inscritas, no solo la elegida: un
                     cliente puede facturar con una distinta según lo que compre. -->
                <div class="nxf-field sp-12">
                    <label class="nxf-label"><?= lang('actividades_del_cliente'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <div class="cli-actividades" id="listaActividades">
                        <?php foreach ((array) (!empty($actividades) ? $actividades : array()) as $a) { ?>
                            <span class="cli-act">
                                <span class="mono"><?= html_escape($a->codigo); ?></span>
                                <?php if ($a->descripcion) { ?><small><?= html_escape($a->descripcion); ?></small><?php } ?>
                                <input type="hidden" name="actividades[]" value="<?= html_escape($a->codigo); ?>">
                                <input type="hidden" name="actividades_desc[]" value="<?= html_escape($a->descripcion); ?>">
                                <button type="button" class="cli-act-x" aria-label="x">&times;</button>
                            </span>
                        <?php } ?>
                    </div>
                    <div class="nxf-hint"><?= lang('actividades_del_cliente_ayuda'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 3: ubicación (Receptor/Ubicacion del comprobante) ── -->
    <div class="nxf-card<?= $nuevo ? ' is-locked' : ''; ?>" data-bloqueable>
        <div class="nxf-card-head">
            <span class="nxf-step">3</span>
            <div class="nxf-card-title">
                <?= lang('cliente_paso_ubicacion'); ?>
                <small><?= lang('cliente_paso_ubicacion_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="codigo_provincia"><?= lang('provincia'); ?></label>
                    <select name="codigo_provincia" id="codigo_provincia" class="nxf-select"
                            data-hijo="codigo_canton" data-url="<?= site_url('customers/get_cantones'); ?>">
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($provincias as $p) { ?>
                            <option value="<?= $p->codigo; ?>" <?= ($val('codigo_provincia') === $p->codigo) ? 'selected' : ''; ?>><?= html_escape($p->nombre); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="codigo_canton"><?= lang('canton'); ?></label>
                    <select name="codigo_canton" id="codigo_canton" class="nxf-select"
                            data-hijo="codigo_distrito" data-url="<?= site_url('customers/get_distritos'); ?>">
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($cantones_actuales as $ct) { ?>
                            <option value="<?= $ct->codigo; ?>" <?= ($val('codigo_canton') === $ct->codigo) ? 'selected' : ''; ?>><?= html_escape($ct->nombre); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="codigo_distrito"><?= lang('distrito'); ?></label>
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
                    <label class="nxf-label" for="otras_senas"><?= lang('otras_senas'); ?></label>
                    <textarea name="otras_senas" id="otras_senas" class="nxf-textarea" maxlength="250"><?= $val('otras_senas'); ?></textarea>
                    <div class="nxf-hint"><?= lang('otras_senas_ayuda'); ?></div>
                </div>

                <div class="nxf-field sp-12" id="wrapExtranjero" hidden>
                    <label class="nxf-label" for="otras_senas_extranjero"><?= lang('otras_senas_extranjero'); ?></label>
                    <textarea name="otras_senas_extranjero" id="otras_senas_extranjero" class="nxf-textarea" maxlength="300"><?= $val('otras_senas_extranjero'); ?></textarea>
                    <div class="nxf-hint"><?= lang('otras_senas_extranjero_ayuda'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 4: condiciones comerciales ── -->
    <div class="nxf-card<?= $nuevo ? ' is-locked' : ''; ?>" data-bloqueable>
        <div class="nxf-card-head">
            <span class="nxf-step">4</span>
            <div class="nxf-card-title"><?= lang('cliente_paso_comercial'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <?php if ($Settings->enable_credit == 1) { ?>
                    <div class="nxf-field sp-4">
                        <label class="nxf-label" for="limitcredit"><?= lang('limitcredit'); ?></label>
                        <input type="text" name="limitcredit" id="limitcredit" class="nxf-input mono"
                               inputmode="decimal" value="<?= $val('limitcredit', '0'); ?>">
                    </div>
                <?php } ?>
                <?php if ($Settings->enable_credit == 1) { ?>
                    <div class="nxf-field sp-4">
                        <label class="nxf-label" for="dias_credito"><?= lang('dias_credito'); ?></label>
                        <input type="number" name="dias_credito" id="dias_credito" class="nxf-input mono"
                               min="0" max="365" value="<?= $val('dias_credito', '0'); ?>">
                        <div class="nxf-hint"><?= lang('dias_credito_ayuda'); ?></div>
                    </div>
                <?php } ?>

                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="tipo_doc_defecto"><?= lang('tipo_doc_defecto'); ?></label>
                    <select name="tipo_doc_defecto" id="tipo_doc_defecto" class="nxf-select">
                        <option value=""   <?= $val('tipo_doc_defecto') === ''   ? 'selected' : ''; ?>><?= lang('segun_el_cliente'); ?></option>
                        <option value="01" <?= $val('tipo_doc_defecto') === '01' ? 'selected' : ''; ?>><?= lang('factura'); ?></option>
                        <option value="04" <?= $val('tipo_doc_defecto') === '04' ? 'selected' : ''; ?>><?= lang('tiquete'); ?></option>
                    </select>
                    <div class="nxf-hint"><?= lang('tipo_doc_defecto_ayuda'); ?></div>
                </div>

                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="tipo_pago_defecto"><?= lang('tipo_pago_defecto'); ?></label>
                    <select name="tipo_pago_defecto" id="tipo_pago_defecto" class="nxf-select">
                        <option value=""        <?= $val('tipo_pago_defecto') === ''        ? 'selected' : ''; ?>><?= lang('sin_definir'); ?></option>
                        <option value="contado" <?= $val('tipo_pago_defecto') === 'contado' ? 'selected' : ''; ?>><?= lang('contado'); ?></option>
                        <option value="credito" <?= $val('tipo_pago_defecto') === 'credito' ? 'selected' : ''; ?>><?= lang('credito'); ?></option>
                    </select>
                </div>

                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="codigo_cliente"><?= lang('codigo_cliente'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="codigo_cliente" id="codigo_cliente" class="nxf-input mono" maxlength="30"
                           value="<?= $val('codigo_cliente'); ?>">
                </div>

                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="whatsapp">WhatsApp<span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="whatsapp" id="whatsapp" class="nxf-input mono" maxlength="20"
                           inputmode="tel" value="<?= $val('whatsapp'); ?>">
                    <div class="nxf-hint"><?= lang('whatsapp_ayuda'); ?></div>
                </div>

                <!-- La exoneración es una resolución vigente del cliente. Se
                     copia a la venta al facturar; no se teclea venta por venta. -->
                <div class="nxf-field sp-12">
                    <label class="nxf-label">
                        <input type="checkbox" id="usaExoCliente" name="usa_exoneracion" value="1"
                               <?= $val('exo_numero') ? 'checked' : ''; ?>>
                        <?= lang('exoneracion'); ?>
                    </label>
                    <div class="nxf-hint"><?= lang('exoneracion_cliente_ayuda'); ?></div>
                </div>

                <div class="nxf-field sp-6 exo-campo" <?= $val('exo_numero') ? '' : 'hidden'; ?>>
                    <label class="nxf-label" for="exo_tipo_documento"><?= lang('tipo_documento'); ?></label>
                    <select name="exo_tipo_documento" id="exo_tipo_documento" class="nxf-select">
                        <?php foreach (tipos_exoneracion() as $cod => $etiqueta) { ?>
                            <option value="<?= $cod; ?>" <?= $val('exo_tipo_documento') === $cod ? 'selected' : ''; ?>>
                                <?= $cod . ' — ' . html_escape($etiqueta); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-3 exo-campo" <?= $val('exo_numero') ? '' : 'hidden'; ?>>
                    <label class="nxf-label" for="exo_numero"><?= lang('numero_documento'); ?></label>
                    <input type="text" name="exo_numero" id="exo_numero" class="nxf-input mono"
                           maxlength="40" value="<?= $val('exo_numero'); ?>">
                </div>
                <div class="nxf-field sp-6 exo-campo" <?= $val('exo_numero') ? '' : 'hidden'; ?>>
                    <label class="nxf-label" for="exo_institucion"><?= lang('nombre_institucion'); ?></label>
                    <select name="exo_institucion" id="exo_institucion" class="nxf-select">
                        <?php foreach (instituciones_exoneracion() as $cod => $etiqueta) { ?>
                            <option value="<?= $cod; ?>" <?= $val('exo_institucion') === $cod ? 'selected' : ''; ?>>
                                <?= $cod . ' — ' . html_escape($etiqueta); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="nxf-hint"><?= lang('institucion_codigo_ayuda'); ?></div>
                </div>
                <div class="nxf-field sp-4 exo-campo" <?= $val('exo_numero') ? '' : 'hidden'; ?>>
                    <label class="nxf-label" for="exo_fecha_emision"><?= lang('fecha_emision'); ?></label>
                    <input type="date" name="exo_fecha_emision" id="exo_fecha_emision" class="nxf-input"
                           value="<?= $val('exo_fecha_emision'); ?>">
                </div>
                <div class="nxf-field sp-4 exo-campo" <?= $val('exo_numero') ? '' : 'hidden'; ?>>
                    <label class="nxf-label" for="exo_fecha_vence"><?= lang('fecha_vencimiento'); ?></label>
                    <input type="date" name="exo_fecha_vence" id="exo_fecha_vence" class="nxf-input"
                           value="<?= $val('exo_fecha_vence'); ?>">
                    <div class="nxf-hint"><?= lang('exo_vence_ayuda'); ?></div>
                </div>
                <div class="nxf-field sp-4 exo-campo" <?= $val('exo_numero') ? '' : 'hidden'; ?>>
                    <label class="nxf-label" for="exo_porcentaje"><?= lang('porcentaje_exoneracion'); ?></label>
                    <input type="number" step="0.01" min="0" max="100" name="exo_porcentaje" id="exo_porcentaje"
                           class="nxf-input mono" value="<?= $val('exo_porcentaje', '0'); ?>">
                </div>

                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="notas"><?= lang('notas_cliente'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="notas" id="notas" class="nxf-input" maxlength="255"
                           value="<?= $val('notas'); ?>">
                    <div class="nxf-hint"><?= lang('notas_cliente_ayuda'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('customers'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="submit" class="nxf-btn" name="<?= $nuevo ? 'add_customer' : 'edit_customer'; ?>" value="1">
            <?= $icono($ico_check); ?> <?= $nuevo ? lang('add_customer') : lang('save'); ?>
        </button>
    </div>
</div>
<?= form_close(); ?>

<style>
    .cli-actividades { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; min-height: 30px; }
    .cli-act { display: inline-flex; align-items: center; gap: 6px; padding: 4px 8px; border-radius: 7px;
        border: 1px solid var(--nxf-border, #2a3444); background: var(--nxf-surface-2, rgba(148,163,184,.06)); font-size: 12px; }
    .cli-act .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .cli-act small { opacity: .7; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cli-act-x { background: none; border: 0; color: #f87171; cursor: pointer; font-size: 15px; line-height: 1; }
</style>

<script>
(function () {
    'use strict';

    var URL_HACIENDA = '<?= site_url('hacienda_proxy/ae'); ?>/';
    var URL_BUSCAR   = '<?= site_url('customers/buscar_cedula'); ?>';
    var EXCLUIR      = <?= $nuevo ? '0' : (int) $c->id; ?>;
    var NUEVO        = <?= $nuevo ? 'true' : 'false'; ?>;

    var T = {
        largo:     <?= json_encode(lang('cedula_largo_invalido')); ?>,
        duplicada: <?= json_encode(lang('cedula_ya_registrada')); ?>,
        libre:     <?= json_encode(lang('cedula_libre')); ?>,
        abrir:     <?= json_encode(lang('abrir_ese_cliente')); ?>,
        hallado:   <?= json_encode(lang('hacienda_encontrado')); ?>,
        situacion: <?= json_encode(lang('hacienda_alerta_situacion')); ?>,
        sinResp:   <?= json_encode(lang('hacienda_sin_respuesta')); ?>,
        buscar:    <?= json_encode(lang('buscar_hacienda_btn')); ?>,
        consultando: <?= json_encode(lang('consultando')); ?>
    };

    var $ = function (id) { return document.getElementById(id); };
    var cf1 = $('cf1'), cf2 = $('cf2');
    var btn = $('btnHacienda'), ico = $('icoHacienda'), txt = $('txtHacienda');
    var avisoCed = $('avisoCedula'), avisoHac = $('avisoHacienda'), avisoSin = $('avisoSinCedula');

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

    // Los pasos 2 a 4 esperan a que haya identificación (o a que se decida no ponerla).
    function desbloquear() {
        var tarjetas = document.querySelectorAll('[data-bloqueable]');
        for (var i = 0; i < tarjetas.length; i++) { tarjetas[i].classList.remove('is-locked'); }
        ocultar(avisoSin);
    }
    if (!NUEVO || (cf2 && cf2.value.trim())) { desbloquear(); }
    if ($('btnSinCedula')) {
        $('btnSinCedula').addEventListener('click', function () { desbloquear(); $('name').focus(); });
    }
    // Un campo requerido dentro de una tarjeta bloqueada no podria corregirse.
    $('nxfCliente').addEventListener('submit', desbloquear);

    function limpio() {
        // 05 es Extranjero No Domiciliado: su documento puede traer letras.
        var v = cf2.value.trim();
        return cf1.value === '05' ? v.replace(/[^A-Za-z0-9]/g, '').toUpperCase() : v.replace(/\D/g, '');
    }

    /* ── ¿Ya existe este cliente? ── */
    var timer;
    function verificarDuplicado() {
        var v = limpio();
        cf2.classList.remove('is-bad', 'is-good');
        if (!v) { ocultar(avisoCed); return; }

        fetch(URL_BUSCAR + '?cf2=' + encodeURIComponent(v) + '&excluir=' + EXCLUIR, {
            credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (res) {
                if (!res) { return; }
                if (res.existe) {
                    cf2.classList.add('is-bad');
                    nota(avisoCed, 'err',
                        '<div><b>' + esc(T.duplicada) + '</b><br>' + esc(res.cliente.name) +
                        ' — <span style="font-family:monospace">' + esc(res.cliente.cf2) + '</span></div>' +
                        '<a class="nxf-btn nxf-btn-sm nxf-btn-ghost" href="' + esc(res.cliente.url) + '">' + esc(T.abrir) + '</a>');
                } else {
                    cf2.classList.add('is-good');
                    nota(avisoCed, 'ok', '<div>' + esc(T.libre) + '</div>');
                }
            })
            .catch(function () { ocultar(avisoCed); });
    }

    /* ── Padrón de Hacienda ── */
    function cargando(activo) {
        btn.disabled = activo;
        txt.textContent = activo ? T.consultando : T.buscar;
        ico.firstChild && ico.firstChild.classList && ico.firstChild.classList.toggle('nxf-spin', activo);
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

                if (d.nombre) { $('name').value = d.nombre; }
                if (d.tipoIdentificacion) { cf1.value = d.tipoIdentificacion; alternarExtranjero(); }
                desbloquear();

                var alertas = [];
                if (d.situacion) {
                    if (d.situacion.moroso) { alertas.push('MOROSO'); }
                    if (d.situacion.omiso) { alertas.push('OMISO'); }
                }
                if (alertas.length) {
                    nota(avisoHac, 'warn', '<div>' + esc(T.situacion.replace('%s', alertas.join(', '))) + '</div>');
                } else {
                    nota(avisoHac, 'ok', '<div>' + esc(T.hallado) + '</div>');
                }

                var acts = d.actividades || [];
                // El padrón las devuelve todas: se guardan todas.
                acts.forEach(function (a) { agregarActividad(a.codigo, a.descripcion); });
                var wrap = $('wrapActividades'), sel = $('selActividades');
                if (acts.length === 1) {
                    $('codigo_actividad').value = acts[0].codigo;
                    wrap.hidden = true;
                } else if (acts.length > 1) {
                    sel.innerHTML = '';
                    acts.forEach(function (a) {
                        var o = document.createElement('option');
                        o.value = a.codigo;
                        o.textContent = a.codigo + ' — ' + a.descripcion;
                        sel.appendChild(o);
                    });
                    $('codigo_actividad').value = acts[0].codigo;
                    wrap.hidden = false;
                }
            })
            .catch(function () { nota(avisoHac, 'warn', '<div>' + esc(T.sinResp) + '</div>'); })
            .then(function () { cargando(false); });
    }

    btn.addEventListener('click', consultarHacienda);
    $('selActividades').addEventListener('change', function () { $('codigo_actividad').value = this.value; });

    /* ── Actividades inscritas del cliente ── */
    function agregarActividad(codigo, descripcion) {
        var lista = $('listaActividades');
        if (!lista || !codigo) { return; }
        var ya = lista.querySelector('input[value="' + codigo.replace(/"/g, '') + '"]');
        if (ya) { return; }

        var chip = document.createElement('span');
        chip.className = 'cli-act';
        chip.innerHTML = '<span class="mono">' + esc(codigo) + '</span>' +
            (descripcion ? '<small>' + esc(descripcion) + '</small>' : '') +
            '<input type="hidden" name="actividades[]" value="' + esc(codigo) + '">' +
            '<input type="hidden" name="actividades_desc[]" value="' + esc(descripcion || '') + '">' +
            '<button type="button" class="cli-act-x" aria-label="x">&times;</button>';
        lista.appendChild(chip);
    }
    window.nxAgregarActividad = agregarActividad;

    var listaAct = $('listaActividades');
    if (listaAct) {
        listaAct.addEventListener('click', function (e) {
            if (e.target.closest('.cli-act-x')) { e.target.closest('.cli-act').remove(); }
        });
    }

    /* ── Exoneración ── */
    var chkExo = $('usaExoCliente');
    if (chkExo) {
        chkExo.addEventListener('change', function () {
            document.querySelectorAll('.exo-campo').forEach(function (c) { c.hidden = !chkExo.checked; });
        });
    }

    cf2.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(verificarDuplicado, 450);
        if (this.value.trim()) { desbloquear(); }
    });
    cf2.addEventListener('keydown', function (e) {
        // Enter en la identificación consulta el padrón, no envía el formulario.
        if (e.key === 'Enter') { e.preventDefault(); consultarHacienda(); }
    });

    /* ── Dirección en el extranjero solo para el tipo 05 ── */
    function alternarExtranjero() {
        $('wrapExtranjero').hidden = (cf1.value !== '05');
        cf2.setAttribute('inputmode', cf1.value === '05' ? 'text' : 'numeric');
    }
    cf1.addEventListener('change', function () { alternarExtranjero(); verificarDuplicado(); });
    alternarExtranjero();

    /* ── Ubicación en cascada ── */
    document.querySelectorAll('[data-hijo]').forEach(function (padre) {
        padre.addEventListener('change', function () {
            var hijo = $(padre.dataset.hijo);
            var vacia = '<option value="">— <?= lang('Seleccione'); ?> —</option>';

            // Cada nivel invalida a los de abajo: cantón y distrito solo existen
            // dentro del padre elegido.
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
