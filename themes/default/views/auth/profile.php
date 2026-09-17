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

$soy_yo   = ($id == $this->session->userdata('user_id'));
$gestiona = ($Admin && !$soy_yo);

$rol = $rol_slug = '';
foreach ($groups as $g) {
    if ($g['id'] == $user->group_id) { $rol = $g['description']; $rol_slug = $g['name']; }
}
// El PIN del cajon es la credencial con la que se abre y se autoriza una
// devolucion: solo lo pone su dueno, y solo si su rol lo habilita.
$pin_propio = ($soy_yo && in_array($rol_slug, array('admin', 'supervisor'), true));
$tienda = '';
foreach ($stores as $st) {
    if ($st->id == $user->store_id) { $tienda = $st->name; }
}

$fecha = function ($marca) {
    return $marca ? date('d/m/Y H:i', (int) $marca) : lang('perfil_nunca');
};
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= html_escape(trim($user->first_name . ' ' . $user->last_name)); ?>
        <small>@<?= html_escape($user->username); ?></small>
    </div>
    <?php if ($Admin) { ?>
        <div class="nxt-head-actions">
            <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('auth/users'); ?>">
                <?= $icono($ico_flecha); ?> <?= lang('users'); ?>
            </a>
        </div>
    <?php } ?>
</div>

<div class="nxf-page nxp-page">

    <!-- ── Ficha ── -->
    <div class="nxf-card nxp-hero">
        <img class="nxp-avatar" alt="" src="<?= avatar_usuario($user->avatar, $user->gender); ?>">
        <div class="nxp-hero-datos">
            <div class="nxp-hero-nombre"><?= html_escape(trim($user->first_name . ' ' . $user->last_name)); ?></div>
            <div class="nxp-chips">
                <?php if ($rol) { ?><span class="nxt-cat" style="--cat-c:var(--nx-violet)"><?= html_escape($rol); ?></span><?php } ?>
                <?php if ($tienda) { ?><span class="nxt-cat" style="--cat-c:var(--nx-a1)"><?= html_escape($tienda); ?></span><?php } ?>
                <span class="nxt-cat" style="--cat-c:var(--nx-<?= $user->active ? 'emerald' : 'err'; ?>)">
                    <?= $user->active ? lang('active') : lang('inactive'); ?>
                </span>
            </div>
            <dl class="nxp-meta">
                <div><dt><?= lang('email_address'); ?></dt><dd><?= html_escape($user->email); ?></dd></div>
                <div><dt><?= lang('perfil_resumen_alta'); ?></dt><dd><?= $fecha($user->created_on); ?></dd></div>
                <div><dt><?= lang('perfil_resumen_ultimo_ingreso'); ?></dt><dd><?= $fecha($user->last_login); ?></dd></div>
            </dl>
        </div>
    </div>

    <!-- ── Pestañas ── -->
    <div class="nxp-tabs" role="tablist">
        <button type="button" class="nxp-tab" data-panel="perfil" role="tab"><?= lang('perfil_datos'); ?></button>
        <button type="button" class="nxp-tab" data-panel="foto" role="tab"><?= lang('foto_de_perfil'); ?></button>
        <button type="button" class="nxp-tab" data-panel="horario" role="tab"><?= lang('horario'); ?></button>
        <button type="button" class="nxp-tab" data-panel="caja" role="tab"><?= lang('apertura_caja'); ?></button>
        <?php if ($soy_yo) { ?>
            <button type="button" class="nxp-tab" data-panel="clave" role="tab"><?= lang('change_password'); ?></button>
        <?php } ?>
    </div>

    <!-- ── Datos y acceso ── -->
    <?= form_open('auth/edit_user/' . $user->id, 'id="nxfPerfil"'); ?>
    <div class="nxp-panel" data-panel="perfil">

        <div class="nxf-card">
            <div class="nxf-card-head">
                <div class="nxf-card-title"><?= lang('info_personal'); ?></div>
            </div>
            <div class="nxf-card-body">
                <div class="nxf-grid">
                    <div class="nxf-field sp-6">
                        <label class="nxf-label" for="cedula"><?= lang('n_identificacion'); ?> <span class="req">*</span></label>
                        <div class="nxf-inline">
                            <input type="text" name="cedula" id="cedula" class="nxf-input mono" required
                                   autocomplete="off" inputmode="numeric" maxlength="20"
                                   value="<?= html_escape($user->cedula); ?>">
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
                            <option value="male"   <?= $user->gender === 'male'   ? 'selected' : ''; ?>><?= lang('male'); ?></option>
                            <option value="female" <?= $user->gender === 'female' ? 'selected' : ''; ?>><?= lang('female'); ?></option>
                        </select>
                    </div>
                    <div class="nxf-field sp-3">
                        <label class="nxf-label" for="phone"><?= lang('phone'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                        <input type="text" name="phone" id="phone" class="nxf-input mono" maxlength="20"
                               inputmode="tel" value="<?= html_escape($user->phone); ?>">
                    </div>

                    <div class="nxf-field sp-6">
                        <label class="nxf-label" for="first_name"><?= lang('first_name'); ?> <span class="req">*</span></label>
                        <input type="text" name="first_name" id="first_name" class="nxf-input" required
                               minlength="2" maxlength="50" value="<?= html_escape($user->first_name); ?>">
                    </div>
                    <div class="nxf-field sp-6">
                        <label class="nxf-label" for="last_name"><?= lang('last_name'); ?> <span class="req">*</span></label>
                        <input type="text" name="last_name" id="last_name" class="nxf-input" required
                               minlength="2" maxlength="50" value="<?= html_escape($user->last_name); ?>">
                    </div>

                    <?php if ($Admin) { ?>
                        <div class="nxf-field sp-12">
                            <label class="nxf-label" for="notes"><?= lang('notas'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                            <input type="text" name="notes" id="notes" class="nxf-input" maxlength="255"
                                   value="<?= html_escape($user->notes); ?>">
                        </div>
                    <?php } ?>
                </div>

                <div class="nxf-note" id="avisoHacienda" hidden></div>
            </div>
        </div>

        <?php if ($pin_propio) { ?>
            <div class="nxf-card">
                <div class="nxf-card-head">
                    <div class="nxf-card-title">
                        <?= lang('pin_cajon'); ?>
                        <small><?= lang('pin_cajon_ayuda'); ?></small>
                    </div>
                </div>
                <div class="nxf-card-body">
                    <div class="nxf-grid">
                        <div class="nxf-field sp-4">
                            <label class="nxf-label" for="drawer_pin"><?= lang('pin_cajon_nuevo'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                            <input type="password" name="drawer_pin" id="drawer_pin" class="nxf-input mono"
                                   inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="8"
                                   autocomplete="new-password" placeholder="••••">
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if ($gestiona) { ?>
            <div class="nxf-card">
                <div class="nxf-card-head">
                    <div class="nxf-card-title"><?= lang('acceso_y_permisos'); ?></div>
                </div>
                <div class="nxf-card-body">
                    <div class="nxf-grid">
                        <div class="nxf-field sp-6">
                            <label class="nxf-label" for="username"><?= lang('username'); ?> <span class="req">*</span></label>
                            <input type="text" name="username" id="username" class="nxf-input mono" required
                                   minlength="3" maxlength="100" pattern="[A-Za-z0-9_\-]+" value="<?= html_escape($user->username); ?>">
                        </div>
                        <div class="nxf-field sp-6">
                            <label class="nxf-label" for="email"><?= lang('email_address'); ?> <span class="req">*</span></label>
                            <input type="email" name="email" id="email" class="nxf-input" required maxlength="100"
                                   value="<?= html_escape($user->email); ?>">
                        </div>

                        <div class="nxf-field sp-4">
                            <label class="nxf-label" for="group"><?= lang('group'); ?> <span class="req">*</span></label>
                            <select name="group" id="group" class="nxf-select" required>
                                <option value="">— <?= lang('Seleccione'); ?> —</option>
                                <?php foreach ($groups as $g) { ?>
                                    <option value="<?= $g['id']; ?>" <?= $user->group_id == $g['id'] ? 'selected' : ''; ?>>
                                        <?= html_escape($g['description']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="nxf-field sp-4">
                            <label class="nxf-label" for="store_id"><?= lang('store'); ?> <span class="req">*</span></label>
                            <select name="store_id" id="store_id" class="nxf-select" required>
                                <option value="">— <?= lang('Seleccione'); ?> —</option>
                                <?php foreach ($stores as $st) { ?>
                                    <option value="<?= $st->id; ?>" <?= $user->store_id == $st->id ? 'selected' : ''; ?>>
                                        <?= html_escape($st->name); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="nxf-field sp-4">
                            <label class="nxf-label" for="status"><?= lang('status'); ?> <span class="req">*</span></label>
                            <select name="status" id="status" class="nxf-select" required>
                                <option value="1" <?= $user->active ? 'selected' : ''; ?>><?= lang('active'); ?></option>
                                <option value="0" <?= $user->active ? '' : 'selected'; ?>><?= lang('inactive'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nxf-card">
                <div class="nxf-card-head">
                    <div class="nxf-card-title">
                        <?= lang('perfil_restablecer_password'); ?>
                        <small><?= lang('perfil_password_vacia_ayuda'); ?></small>
                    </div>
                </div>
                <div class="nxf-card-body">
                    <div class="nxf-grid">
                        <div class="nxf-field sp-6">
                            <label class="nxf-label" for="password"><?= lang('password'); ?></label>
                            <input type="password" name="password" id="password" class="nxf-input"
                                   minlength="8" maxlength="25" autocomplete="new-password">
                        </div>
                        <div class="nxf-field sp-6">
                            <label class="nxf-label" for="password_confirm"><?= lang('confirm_password'); ?></label>
                            <input type="password" name="password_confirm" id="password_confirm" class="nxf-input"
                                   minlength="8" maxlength="25" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?= form_hidden('id', $id); ?>
        <?= form_hidden($csrf); ?>
        <div class="nxf-actions">
            <span class="nxf-spacer"></span>
            <button type="submit" class="nxf-btn" name="update_user" value="1">
                <?= $icono($ico_check); ?> <?= lang('update'); ?>
            </button>
        </div>
    </div>
    <?= form_close(); ?>

    <!-- ── Foto de perfil ── -->
    <div class="nxp-panel" data-panel="foto" hidden>
        <div class="nxf-card">
            <div class="nxf-card-head">
                <div class="nxf-card-title"><?= lang('foto_de_perfil'); ?></div>
            </div>
            <div class="nxf-card-body">
                <?= form_open_multipart('auth/update_avatar'); ?>
                <div class="nxp-foto">
                    <img class="nxp-avatar" alt="" src="<?= avatar_usuario($user->avatar, $user->gender); ?>">
                    <div class="nxf-field">
                        <label class="nxf-label" for="avatar"><?= lang('change_avatar'); ?></label>
                        <input type="file" name="avatar" id="avatar" class="nxf-input" accept="image/*" required>
                    </div>
                </div>
                <?= form_hidden('id', $id); ?>
                <?= form_hidden($csrf); ?>
                <div class="nxf-actions">
                    <span class="nxf-spacer"></span>
                    <button type="submit" class="nxf-btn" name="update_avatar" value="1">
                        <?= $icono($ico_check); ?> <?= lang('update_avatar'); ?>
                    </button>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>

    <!-- ── Horario ── -->
    <div class="nxp-panel" data-panel="horario" hidden>
        <div class="nxf-card">
            <div class="nxf-card-head">
                <div class="nxf-card-title">
                    <?= lang('horario_usuario'); ?>
                    <small><?= lang('entrada_y_salida'); ?></small>
                </div>
            </div>
            <div class="nxf-card-body">
                <?= form_open('auth/edit_horario/' . $user->id); ?>
                <div class="nxf-grid">
                    <div class="nxf-field sp-4">
                        <label class="nxf-label" for="hora_inicio"><?= lang('hora_entrada'); ?></label>
                        <input type="time" name="hora_inicio" id="hora_inicio" class="nxf-input"
                               value="<?= html_escape($user->hora_inicio); ?>">
                    </div>
                    <div class="nxf-field sp-4">
                        <label class="nxf-label" for="hora_fin"><?= lang('hora_salida'); ?></label>
                        <input type="time" name="hora_fin" id="hora_fin" class="nxf-input"
                               value="<?= html_escape($user->hora_fin); ?>">
                        <div class="nxf-hint" id="hintTurno"></div>
                    </div>
                </div>
                <?= form_hidden('id', $id); ?>
                <?= form_hidden($csrf); ?>
                <div class="nxf-actions">
                    <span class="nxf-spacer"></span>
                    <button type="submit" class="nxf-btn" name="update_user" value="1">
                        <?= $icono($ico_check); ?> <?= lang('update'); ?>
                    </button>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>

    <!-- ── Apertura de caja ── -->
    <div class="nxp-panel" data-panel="caja" hidden>
        <div class="nxf-card">
            <div class="nxf-card-head">
                <div class="nxf-card-title">
                    <?= lang('permiso_apertura_caja'); ?>
                    <small><?= lang('permiso_apertura'); ?></small>
                </div>
            </div>
            <div class="nxf-card-body">
                <?= form_open('auth/auth_open_cash/' . $user->id); ?>
                <div class="nxf-grid">
                    <div class="nxf-field sp-5">
                        <label class="nxf-label" for="auth_open"><?= lang('habilitar_apertura'); ?></label>
                        <select name="auth_open" id="auth_open" class="nxf-select" required>
                            <option value="1" <?= $user->auth_open ? 'selected' : ''; ?>><?= lang('si_habilitar'); ?></option>
                            <option value="0" <?= $user->auth_open ? '' : 'selected'; ?>><?= lang('no_permitir'); ?></option>
                        </select>
                    </div>
                </div>
                <?= form_hidden('id', $id); ?>
                <?= form_hidden($csrf); ?>
                <div class="nxf-actions">
                    <span class="nxf-spacer"></span>
                    <button type="submit" class="nxf-btn" name="update_user" value="1">
                        <?= $icono($ico_check); ?> <?= lang('update'); ?>
                    </button>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>

    <?php if ($soy_yo) { ?>
        <!-- ── Contraseña ── -->
        <div class="nxp-panel" data-panel="clave" hidden>
            <div class="nxf-card">
                <div class="nxf-card-head">
                    <div class="nxf-card-title">
                        <?= lang('change_password'); ?>
                        <small><?= lang('seguridad_cuenta'); ?></small>
                    </div>
                </div>
                <div class="nxf-card-body">
                    <?= form_open('auth/change_password'); ?>
                    <div class="nxf-grid">
                        <div class="nxf-field sp-4">
                            <label class="nxf-label" for="old_password"><?= lang('old_password'); ?> <span class="req">*</span></label>
                            <input type="password" name="old_password" id="old_password" class="nxf-input" required
                                   autocomplete="current-password">
                        </div>
                        <div class="nxf-field sp-4">
                            <label class="nxf-label" for="new_password"><?= sprintf(lang('new_password'), $min_password_length); ?> <span class="req">*</span></label>
                            <input type="password" name="new_password" id="new_password" class="nxf-input" required
                                   minlength="8" maxlength="25" autocomplete="new-password">
                        </div>
                        <div class="nxf-field sp-4">
                            <label class="nxf-label" for="new_password_confirm"><?= lang('confirm_password'); ?> <span class="req">*</span></label>
                            <input type="password" name="new_password_confirm" id="new_password_confirm" class="nxf-input" required
                                   minlength="8" maxlength="25" autocomplete="new-password">
                            <div class="nxf-hint" id="coincide"></div>
                        </div>
                    </div>
                    <?= form_input($user_id); ?>
                    <div class="nxf-actions">
                        <span class="nxf-spacer"></span>
                        <button type="submit" class="nxf-btn" name="change_password" value="1">
                            <?= $icono($ico_check); ?> <?= lang('change_password'); ?>
                        </button>
                    </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    <?php } ?>

</div>

<script>
(function () {
    'use strict';

    var URL_HACIENDA = '<?= site_url('hacienda_proxy/ae'); ?>/';

    var T = {
        largo:   <?= json_encode(lang('usuario_cedula_largo')); ?>,
        hallado: <?= json_encode(lang('usuario_padron_hallado')); ?>,
        sinNombre: <?= json_encode(lang('usuario_padron_sin_nombre')); ?>,
        sinResp: <?= json_encode(lang('hacienda_sin_respuesta')); ?>,
        buscar:  <?= json_encode(lang('buscar_hacienda_btn')); ?>,
        consultando: <?= json_encode(lang('consultando')); ?>,
        turno:   <?= json_encode(lang('horario_salida_antes')); ?>,
        igual:   <?= json_encode(lang('password_coincide')); ?>,
        distinta:<?= json_encode(lang('password_no_coincide')); ?>
    };

    var $ = function (id) { return document.getElementById(id); };

    /* ── Pestañas ── */
    var CLAVE = 'nx_perfil_tab';
    var pestanas = document.querySelectorAll('.nxp-tab');

    function abrir(nombre) {
        var destino = document.querySelector('.nxp-panel[data-panel="' + nombre + '"]');
        if (!destino) { return; }
        document.querySelectorAll('.nxp-panel').forEach(function (p) { p.hidden = true; });
        pestanas.forEach(function (b) { b.classList.remove('is-on'); });
        destino.hidden = false;
        document.querySelector('.nxp-tab[data-panel="' + nombre + '"]').classList.add('is-on');
    }

    pestanas.forEach(function (b) {
        b.addEventListener('click', function () {
            abrir(b.dataset.panel);
            try { localStorage.setItem(CLAVE, b.dataset.panel); } catch (err) {}
        });
    });

    var guardada = 'perfil';
    try { guardada = localStorage.getItem(CLAVE) || 'perfil'; } catch (err) {}
    abrir(document.querySelector('.nxp-tab[data-panel="' + guardada + '"]') ? guardada : 'perfil');

    /* ── Nombre y apellidos desde el padrón ── */
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

    /* ── Turno ── */
    var entrada = $('hora_inicio'), salida = $('hora_fin');
    if (entrada && salida) {
        var revisarTurno = function () {
            $('hintTurno').textContent = (entrada.value && salida.value && salida.value <= entrada.value) ? T.turno : '';
        };
        entrada.addEventListener('change', revisarTurno);
        salida.addEventListener('change', revisarTurno);
    }

    /* ── Contraseña propia ── */
    var nueva = $('new_password'), repetida = $('new_password_confirm');
    if (nueva && repetida) {
        var revisarClave = function () {
            if (!repetida.value) { $('coincide').textContent = ''; return; }
            $('coincide').textContent = (repetida.value === nueva.value) ? T.igual : T.distinta;
        };
        nueva.addEventListener('input', revisarClave);
        repetida.addEventListener('input', revisarClave);
    }
})();
</script>
