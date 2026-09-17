<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_buscar = '<circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/>';
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('add_user'); ?>
        <small><?= lang('usuario_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('auth/users'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('users'); ?>
        </a>
    </div>
</div>

<?= form_open('auth/create_user', 'id="nxfUsuario"'); ?>
<div class="nxf-page">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err"><div><?= $error; ?></div></div>
    <?php } ?>

    <!-- ── Paso 1: identidad ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title"><?= lang('usuario_paso_identidad'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="cedula"><?= lang('n_identificacion'); ?> <span class="req">*</span></label>
                    <div class="nxf-inline">
                        <input type="text" name="cedula" id="cedula" class="nxf-input mono" required
                               autocomplete="off" inputmode="numeric" maxlength="20" value="<?= set_value('cedula'); ?>">
                        <button type="button" class="nxf-btn" id="btnHacienda">
                            <span id="icoHacienda"><?= $icono($ico_buscar); ?></span>
                            <span id="txtHacienda"><?= lang('buscar_hacienda_btn'); ?></span>
                        </button>
                    </div>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="gender"><?= lang('gender'); ?> <span class="req">*</span></label>
                    <select name="gender" id="gender" class="nxf-select" required>
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <option value="male"   <?= set_value('gender') === 'male' ? 'selected' : ''; ?>><?= lang('male'); ?></option>
                        <option value="female" <?= set_value('gender') === 'female' ? 'selected' : ''; ?>><?= lang('female'); ?></option>
                    </select>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="phone"><?= lang('phone'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="phone" id="phone" class="nxf-input mono" maxlength="20"
                           inputmode="tel" value="<?= set_value('phone'); ?>">
                </div>

                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="first_name"><?= lang('first_name'); ?> <span class="req">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="nxf-input" required
                           minlength="2" maxlength="50" value="<?= set_value('first_name'); ?>">
                </div>
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="last_name"><?= lang('last_name'); ?> <span class="req">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="nxf-input" required
                           minlength="2" maxlength="50" value="<?= set_value('last_name'); ?>">
                </div>

                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="email"><?= lang('email_address'); ?> <span class="req">*</span></label>
                    <input type="email" name="email" id="email" class="nxf-input" required maxlength="100"
                           value="<?= set_value('email'); ?>">
                    <div class="nxf-hint"><?= lang('usuario_correo_ayuda'); ?></div>
                </div>
            </div>

            <div class="nxf-note" id="avisoHacienda" hidden></div>
        </div>
    </div>

    <!-- ── Paso 2: acceso ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title">
                <?= lang('usuario_paso_acceso'); ?>
                <small><?= lang('usuario_paso_acceso_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="username"><?= lang('username'); ?> <span class="req">*</span></label>
                    <input type="text" name="username" id="username" class="nxf-input mono" required
                           minlength="3" maxlength="100" pattern="[A-Za-z0-9_\-]+" value="<?= set_value('username'); ?>">
                    <div class="nxf-hint"><?= lang('usuario_username_ayuda'); ?></div>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="password"><?= lang('password'); ?> <span class="req">*</span></label>
                    <input type="password" name="password" id="password" class="nxf-input" required
                           minlength="8" maxlength="25" autocomplete="new-password">
                    <div class="nxf-hint" id="fuerza"><?= lang('password_minimo'); ?></div>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="confirm_password"><?= lang('confirm_password'); ?> <span class="req">*</span></label>
                    <input type="password" name="confirm_password" id="confirm_password" class="nxf-input" required
                           minlength="8" maxlength="25" autocomplete="new-password">
                    <div class="nxf-hint" id="coincide"></div>
                </div>

                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="group"><?= lang('group'); ?> <span class="req">*</span></label>
                    <select name="group" id="group" class="nxf-select" required>
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($groups as $group) { ?>
                            <option value="<?= $group['id']; ?>" <?= set_value('group') == $group['id'] ? 'selected' : ''; ?>>
                                <?= html_escape($group['description']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="store_id"><?= lang('store'); ?> <span class="req">*</span></label>
                    <select name="store_id" id="store_id" class="nxf-select" required>
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ($stores as $store) { ?>
                            <option value="<?= $store->id; ?>" <?= set_value('store_id') == $store->id ? 'selected' : ''; ?>>
                                <?= html_escape($store->name); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="status"><?= lang('status'); ?> <span class="req">*</span></label>
                    <select name="status" id="status" class="nxf-select" required>
                        <option value="1" <?= set_value('status', '1') == '1' ? 'selected' : ''; ?>><?= lang('active'); ?></option>
                        <option value="0" <?= set_value('status') === '0' ? 'selected' : ''; ?>><?= lang('inactive'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 3: turno y caja ── -->
    <div class="nxf-card">
        <div class="nxf-card-head">
            <span class="nxf-step">3</span>
            <div class="nxf-card-title"><?= lang('usuario_paso_turno'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="hora_inicio"><?= lang('hora_entrada'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="time" name="hora_inicio" id="hora_inicio" class="nxf-input" value="<?= set_value('hora_inicio'); ?>">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="hora_fin"><?= lang('hora_salida'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="time" name="hora_fin" id="hora_fin" class="nxf-input" value="<?= set_value('hora_fin'); ?>">
                    <div class="nxf-hint" id="hintTurno"></div>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="auth_open"><?= lang('apertura_caja'); ?></label>
                    <select name="auth_open" id="auth_open" class="nxf-select">
                        <option value="0" <?= set_value('auth_open') === '0' ? 'selected' : ''; ?>><?= lang('no'); ?></option>
                        <option value="1" <?= set_value('auth_open') === '1' ? 'selected' : ''; ?>><?= lang('yes'); ?></option>
                    </select>
                    <div class="nxf-hint"><?= lang('permiso_apertura'); ?></div>
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="notify"><?= lang('notificar_por_correo'); ?></label>
                    <select name="notify" id="notify" class="nxf-select">
                        <option value="0"><?= lang('no'); ?></option>
                        <option value="1"><?= lang('yes'); ?></option>
                    </select>
                </div>

                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="notes"><?= lang('notas'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="text" name="notes" id="notes" class="nxf-input" maxlength="255" value="<?= set_value('notes'); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('auth/users'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="submit" class="nxf-btn" name="add_user" value="1">
            <?= $icono($ico_check); ?> <?= lang('add_user'); ?>
        </button>
    </div>
</div>
<?= form_close(); ?>

<script>
(function () {
    'use strict';

    var URL_HACIENDA = '<?= site_url('hacienda_proxy/ae'); ?>/';

    var T = {
        debil:   <?= json_encode(lang('password_debil')); ?>,
        media:   <?= json_encode(lang('password_media')); ?>,
        fuerte:  <?= json_encode(lang('password_fuerte')); ?>,
        minimo:  <?= json_encode(lang('password_minimo')); ?>,
        igual:   <?= json_encode(lang('password_coincide')); ?>,
        distinta:<?= json_encode(lang('password_no_coincide')); ?>,
        turno:   <?= json_encode(lang('horario_salida_antes')); ?>,
        largo:   <?= json_encode(lang('usuario_cedula_largo')); ?>,
        hallado: <?= json_encode(lang('usuario_padron_hallado')); ?>,
        sinNombre: <?= json_encode(lang('usuario_padron_sin_nombre')); ?>,
        sinResp: <?= json_encode(lang('hacienda_sin_respuesta')); ?>,
        buscar:  <?= json_encode(lang('buscar_hacienda_btn')); ?>,
        consultando: <?= json_encode(lang('consultando')); ?>
    };

    var $ = function (id) { return document.getElementById(id); };
    var pass = $('password'), conf = $('confirm_password');
    var ced = $('cedula'), btn = $('btnHacienda'), ico = $('icoHacienda'), txt = $('txtHacienda');
    var aviso = $('avisoHacienda');

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    function nota(tono, texto) {
        aviso.className = 'nxf-note nxf-note-' + tono;
        aviso.innerHTML = '<div>' + esc(texto) + '</div>';
        aviso.hidden = false;
    }

    // El padrón devuelve el nombre completo en una sola cadena y en Costa Rica
    // los dos últimos términos son los apellidos.
    function partirNombre(completo) {
        var partes = String(completo).trim().split(/\s+/).filter(Boolean);
        if (partes.length <= 1) { return { nombre: partes.join(' '), apellidos: '' }; }
        if (partes.length === 2) { return { nombre: partes[0], apellidos: partes[1] }; }
        return { nombre: partes.slice(0, -2).join(' '), apellidos: partes.slice(-2).join(' ') };
    }

    function cargando(activo) {
        btn.disabled = activo;
        txt.textContent = activo ? T.consultando : T.buscar;
        if (ico.firstChild && ico.firstChild.classList) { ico.firstChild.classList.toggle('nxf-spin', activo); }
    }

    function consultarHacienda() {
        var v = ced.value.replace(/\D/g, '');
        if (v.length < 9 || v.length > 12) { nota('warn', T.largo); return; }

        cargando(true);
        aviso.hidden = true;
        fetch(URL_HACIENDA + v, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                var d = res.d || {};
                if (!res.ok || d.error) { nota('warn', d.error || T.sinResp); return; }
                if (!d.nombre) { nota('warn', T.sinNombre); return; }

                var partido = partirNombre(d.nombre);
                $('first_name').value = partido.nombre;
                if (partido.apellidos) { $('last_name').value = partido.apellidos; }
                nota('ok', T.hallado);
            })
            .catch(function () { nota('warn', T.sinResp); })
            .then(function () { cargando(false); });
    }

    btn.addEventListener('click', consultarHacienda);
    ced.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); consultarHacienda(); }
    });

    // Una cédula física son 9 dígitos: al completarlos ya hay algo que consultar,
    // pero nunca se pisa un nombre que el administrador haya escrito.
    var timer;
    ced.addEventListener('input', function () {
        clearTimeout(timer);
        if (ced.value.replace(/\D/g, '').length < 9 || $('first_name').value.trim()) { return; }
        timer = setTimeout(consultarHacienda, 500);
    });

    function fuerza(v) {
        var puntos = 0;
        if (v.length >= 8) { puntos++; }
        if (v.length >= 12) { puntos++; }
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) { puntos++; }
        if (/\d/.test(v)) { puntos++; }
        if (/[^A-Za-z0-9]/.test(v)) { puntos++; }
        return puntos;
    }

    pass.addEventListener('input', function () {
        var v = pass.value;
        if (!v) { $('fuerza').textContent = T.minimo; return; }
        var p = fuerza(v);
        $('fuerza').textContent = p <= 2 ? T.debil : (p === 3 ? T.media : T.fuerte);
        revisarConfirmacion();
    });

    function revisarConfirmacion() {
        if (!conf.value) { $('coincide').textContent = ''; return; }
        $('coincide').textContent = (conf.value === pass.value) ? T.igual : T.distinta;
    }
    conf.addEventListener('input', revisarConfirmacion);

    function revisarTurno() {
        var a = $('hora_inicio').value, b = $('hora_fin').value;
        $('hintTurno').textContent = (a && b && b <= a) ? T.turno : '';
    }
    $('hora_inicio').addEventListener('change', revisarTurno);
    $('hora_fin').addEventListener('change', revisarTurno);

    $('nxfUsuario').addEventListener('submit', function (e) {
        if (pass.value !== conf.value) {
            e.preventDefault();
            conf.focus();
            $('coincide').textContent = T.distinta;
        }
    });
})();
</script>
