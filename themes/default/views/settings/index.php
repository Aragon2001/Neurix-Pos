<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-12">
        <div class="ns-mockup">

            <?php echo form_open_multipart("settings", 'class="validation" id="settings-form" novalidate'); ?>
            <?php echo form_hidden('default_discount', $settings->default_discount ?? '0'); ?>
            <?php echo form_hidden('tax_rate', $settings->default_tax_rate ?? '0'); ?>
            <?php echo form_hidden('rtl', $settings->rtl ?? 0); ?>
            <?php echo form_hidden('stripe', $settings->stripe ?? 0); ?>
            <?php echo form_hidden('stripe_secret_key', $settings->stripe_secret_key ?? ''); ?>
            <?php echo form_hidden('stripe_publishable_key', $settings->stripe_publishable_key ?? ''); ?>
            <?php echo form_hidden('print_img', $settings->print_img ?? 0); ?>
            <?php echo form_hidden('multi_store', $settings->multi_store ?? 0); ?>
            <?php echo form_hidden('bill_header', $settings->header ?? ''); ?>
            <?php echo form_hidden('bill_footer', $settings->footer ?? ''); ?>

            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
            <style>
            /* ============================================================
               NS = Neurix Settings — skin fiel a la maqueta de diseño
               Paleta y tipografías propias de la maqueta, escopadas bajo
               .ns-mockup para no afectar el resto de la app. Reacciona a
               [data-theme] (el mismo atributo que ya usa toda la app en <body>).
               ============================================================ */

            .ns-mockup {
                --ns-bg: #0b0f1c;
                --ns-surface: #121728;
                --ns-surface-2: #171e35;
                --ns-surface-3: #1d2540;
                --ns-border: #262f4d;
                --ns-border-soft: #1c2440;
                --ns-text-1: #eef1f9;
                --ns-text-2: #9aa4c4;
                --ns-text-3: #67719a;
                --ns-primary: #4c7cf5;
                --ns-primary-dim: #3a63d6;
                --ns-primary-soft: rgba(76,124,245,.14);
                --ns-primary-ring: rgba(76,124,245,.35);
                --ns-seal: #e8a53d;
                --ns-seal-dim: #c98a2b;
                --ns-seal-soft: rgba(232,165,61,.13);
                --ns-success: #34c77b;
                --ns-success-soft: rgba(52,199,123,.14);
                --ns-danger: #f2555a;
                --ns-radius-sm: 8px;
                --ns-radius: 12px;
                --ns-radius-lg: 18px;
                --ns-shadow-lg: 0 24px 48px -16px rgba(0,0,0,.55);
                --ns-shadow-sm: 0 2px 8px rgba(0,0,0,.25);
                --ns-ease: cubic-bezier(.2,.8,.2,1);
                font-family: 'Inter', system-ui, sans-serif;
                color: var(--ns-text-1);
            }
            [data-theme="light"] .ns-mockup {
                --ns-bg: #f4f6fb;
                --ns-surface: #ffffff;
                --ns-surface-2: #f8fafc;
                --ns-surface-3: #eef1f9;
                --ns-border: rgba(76,124,245,.20);
                --ns-border-soft: rgba(76,124,245,.10);
                --ns-text-1: #0f1526;
                --ns-text-2: #45516e;
                --ns-text-3: #6b7594;
                --ns-primary: #3a63d6;
                --ns-primary-dim: #2c4fb8;
                --ns-primary-soft: rgba(58,99,214,.10);
            }
            .ns-mockup h1, .ns-mockup h2, .ns-mockup h3, .ns-mockup h4,
            .ns-mockup .card-header, .ns-mockup .ns-card-head-text strong { font-family: 'Manrope', sans-serif; }

            /* ── Tarjetas: reskin del .card de AdminLTE con la piel de la maqueta ── */
            .ns-mockup .card {
                background: var(--ns-surface); border: 1px solid var(--ns-border);
                border-radius: var(--ns-radius-lg); box-shadow: none;
                margin-bottom: 20px;
            }
            .ns-mockup .card:last-child { margin-bottom: 0; }
            .ns-mockup .card-header {
                background: transparent; border-bottom: 1px solid var(--ns-border-soft);
                padding: 16px 20px; color: var(--ns-text-1);
            }
            .ns-mockup .card-body { padding: 20px; }
            .ns-mockup .card fieldset[disabled] .card-body { opacity: .5; }
            /* El navegador dibuja un recuadro alrededor de todo <fieldset> por defecto;
               tab-emisor envuelve varias tarjetas en uno para poder deshabilitarlas juntas
               y ese recuadro terminaba tapando las tarjetas de adentro. */
            .ns-mockup fieldset { border: 0; margin: 0; padding: 0; min-width: 0; }
            /* Aire entre el bloque de nav+contenido y el resto de secciones sueltas
               (banner de Hacienda, tabla de puestos, etc.) que no viven dentro de un
               .card pero igual quedaban pegadas unas a otras. */
            .ns-mockup .tab-pane > * + * { margin-top: 20px; }

            /* ── Formularios: reskin de .form-control / selects / textarea ── */
            .ns-mockup label { font-size: 12.5px; font-weight: 600; color: var(--ns-text-2); display: flex; align-items: center; gap: 7px; margin-bottom: 7px; }
            .ns-mockup label .fa { color: var(--ns-text-3); width: 15px; text-align: center; }
            .ns-mockup .form-control, .ns-mockup select.form-control, .ns-mockup textarea.form-control {
                background: var(--ns-surface-2); border: 1px solid var(--ns-border); border-radius: 9px;
                color: var(--ns-text-1); height: 40px; font-size: 13.5px; box-shadow: none;
            }
            .ns-mockup textarea.form-control { height: auto; padding-top: 10px; }
            .ns-mockup .form-control:focus {
                border-color: var(--ns-primary); background: var(--ns-surface-3);
                box-shadow: 0 0 0 3px var(--ns-primary-soft); color: var(--ns-text-1);
            }
            .ns-mockup .form-control::placeholder { color: var(--ns-text-3); }
            .ns-mockup .help-block { font-size: 11.5px; color: var(--ns-text-3); margin-top: 5px; }

            /* ── Botones: reskin del sistema de .btn ── */
            .ns-mockup .btn { border-radius: 10px; font-weight: 600; font-size: 13.5px; }
            .ns-mockup .btn-primary { background: var(--ns-primary); border-color: var(--ns-primary); box-shadow: 0 8px 20px -8px rgba(76,124,245,.6); }
            .ns-mockup .btn-primary:hover { background: var(--ns-primary-dim); border-color: var(--ns-primary-dim); }
            .ns-mockup .btn-default { background: var(--ns-surface-2); border-color: var(--ns-border); color: var(--ns-text-2); }
            .ns-mockup .btn-warning { background: var(--ns-seal-soft); border-color: rgba(232,165,61,.3); color: var(--ns-seal); }
            .ns-mockup .btn-warning:hover { background: rgba(232,165,61,.22); }
            .ns-mockup .btn-success { background: var(--ns-success-soft); border-color: rgba(52,199,123,.3); color: var(--ns-success); }
            /* Bootstrap 5 no trae .btn-block (se reemplazó por utilidades "d-grid"),
               así que los botones "Probar credenciales" no se estiraban como el resto
               de los campos de la fila y quedaban chicos/desalineados. */
            .ns-mockup .btn-block { display: flex; align-items: center; justify-content: center; width: 100%; height: 40px; }

            /* ── Grupos input+botón (buscar cédula, leer clave, mostrar/ocultar
               contraseña, grabar atajo…): sin esto, cada botón quedaba con las
               cuatro esquinas redondeadas igual que el campo de texto y sin
               separación real entre ambos — se veían amontonados. ── */
            .ns-mockup .input-group { display: flex; align-items: stretch; }
            .ns-mockup .input-group .form-control {
                border-top-right-radius: 0; border-bottom-right-radius: 0;
            }
            .ns-mockup .input-group-btn { display: flex; }
            .ns-mockup .input-group-btn .btn {
                background: var(--ns-surface-3); border: 1px solid var(--ns-border); color: var(--ns-text-2);
                border-radius: 0; border-left: 0; height: 40px;
            }
            .ns-mockup .input-group-btn .btn:last-child { border-top-right-radius: 9px; border-bottom-right-radius: 9px; }
            .ns-mockup .input-group-btn .btn:hover,
            .ns-mockup .input-group-btn .btn:focus {
                background: var(--ns-surface-2); color: var(--ns-primary); border-color: var(--ns-primary);
                box-shadow: none; outline: none;
            }

            .ns-settings-nav { border-right: 3px solid var(--ns-border); padding-right: 0; }
            .ns-settings-nav .nav-pills {
                list-style: none; margin: 0; padding: 0;
                display: flex; flex-direction: column; gap: 2px;
            }
            .ns-settings-nav .nav-pills > li { list-style: none; }
            .ns-settings-nav .nav-pills > li > a {
                border-radius: var(--ns-radius-sm);
                padding: 12px 14px;
                margin-bottom: 2px;
                color: var(--ns-text-2);
                font-size: 13px;
                font-weight: 600;
                border-left: 3px solid transparent;
                display: flex;
                align-items: center;
                gap: 10px;
                position: relative;
                transition: all .15s var(--ns-ease);
                /* Bootstrap 5 subraya <a> por defecto salvo que tenga .nav-link;
                   estos anchors no la usan (son hijos directos de .nav-pills > li),
                   así que sin esto se ven como texto plano subrayado. */
                text-decoration: none !important;
                cursor: pointer;
            }
            .ns-settings-nav .nav-pills > li > a:hover,
            .ns-settings-nav .nav-pills > li > a:focus {
                text-decoration: none !important;
            }
            .ns-settings-nav .nav-pills > li > a .fa {
                font-size: 19px;
                width: 26px;
                text-align: center;
                flex-shrink: 0;
                color: var(--ns-text-3);
                transition: color .15s;
            }
            .ns-settings-nav .nav-pills > li > a:hover { background: var(--ns-surface-2); color: var(--ns-primary); }
            .ns-settings-nav .nav-pills > li > a:hover .fa { color: var(--ns-primary); }
            .ns-settings-nav .nav-pills > li.active > a,
            .ns-settings-nav .nav-pills > li.active > a:hover {
                background: var(--ns-primary-soft);
                color: var(--ns-primary);
                border-left: 3px solid var(--ns-primary);
            }
            .ns-settings-nav .nav-pills > li.active > a .fa { color: var(--ns-primary); }
            .ns-settings-nav .nav-pills > li > a .nx-nav-label { display: flex; flex-direction: column; }
            .ns-settings-nav .nav-pills > li > a .nx-nav-sub { font-size: 10px; font-weight: 400; color: var(--ns-text-3); margin-top: 1px; }
            .ns-settings-nav .nav-pills > li.active > a .nx-nav-sub { color: var(--ns-primary); opacity: .85; }
            .ns-nav-badge {
                margin-left: auto; width: 7px; height: 7px; border-radius: 50%;
                background: var(--ns-seal); box-shadow: 0 0 0 3px rgba(232,165,61,.18); flex-shrink: 0;
            }

            /* nav móvil (chips horizontales) */
            .ns-nav-mobile { display: none; }
            @media (max-width: 900px) {
                .ns-settings-nav { display: none; }
                .ns-nav-mobile {
                    display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px; margin-bottom: 14px;
                    -webkit-overflow-scrolling: touch;
                }
                .ns-nav-mobile::-webkit-scrollbar { display: none; }
                .ns-nav-chip {
                    flex-shrink: 0; display: flex; align-items: center; gap: 7px;
                    padding: 9px 14px; border-radius: 999px; border: 1px solid var(--ns-border);
                    background: var(--ns-surface); color: var(--ns-text-2); font-size: 12.5px; font-weight: 600;
                    cursor: pointer; white-space: nowrap;
                }
                .ns-nav-chip.active { background: var(--ns-primary-soft); border-color: var(--ns-primary); color: var(--ns-primary); }
            }

            /* ── Toolbar de búsqueda ── */
            .ns-toolbar { display: flex; justify-content: flex-end; margin-bottom: 14px; }
            .ns-search-box { position: relative; width: 280px; max-width: 100%; }
            .ns-search-box .fa {
                position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
                color: var(--ns-text-3); pointer-events: none; font-size: 13px;
            }
            .ns-search-box input {
                width: 100%; height: 38px; padding: 0 12px 0 32px;
                background: var(--ns-surface); border: 1px solid var(--ns-border); border-radius: var(--ns-radius-sm);
                color: var(--ns-text-1); font-size: 13px;
            }
            .ns-search-box input:focus { outline: none; border-color: var(--ns-primary); background: var(--ns-surface-2); }
            .ns-no-results { display: none; text-align: center; padding: 40px 20px; color: var(--ns-text-3); }
            .ns-no-results.show { display: block; }
            .ns-no-results .fa { font-size: 26px; margin-bottom: 8px; display: block; }

            /* ── Tarjetas con avatar de icono ── */
            .ns-card-icon {
                width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
                display: flex; align-items: center; justify-content: center;
                background: var(--ns-primary-soft); color: var(--ns-primary); font-size: 15px;
            }
            .ns-card-icon.seal { background: var(--ns-seal-soft); color: var(--ns-seal); }
            .card-header.ns-card-head { display: flex; align-items: center; gap: 12px; }
            .ns-card-head-text { display: flex; flex-direction: column; line-height: 1.3; }
            .ns-card-head-text strong { font-size: 14px; font-weight: 700; }
            .ns-card-head-text small { font-weight: 400; color: var(--ns-text-3); font-size: 11.5px; }

            /* ── Inputs monoespaciados (códigos, tokens) ── */
            .ns-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 13px; }

            /* ── Toggle switch ligado a un <select> real (oculto) ── */
            .ns-select-hidden { display: none !important; }
            .ns-toggle-row {
                display: flex; align-items: center; justify-content: space-between; gap: 14px;
                padding: 11px 14px; background: var(--ns-surface-2); border: 1px solid var(--ns-border-soft);
                border-radius: var(--ns-radius-sm); height: 100%;
            }
            .ns-toggle-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
            .ns-toggle-text b { font-size: 13px; font-weight: 600; color: var(--ns-text-1); display: flex; align-items: center; gap: 7px; }
            .ns-toggle-text b .fa { color: var(--ns-text-3); width: 16px; text-align: center; }
            .ns-toggle-text span { font-size: 11px; color: var(--ns-text-3); }
            .ns-switch { position: relative; width: 42px; height: 24px; flex-shrink: 0; }
            .ns-switch input { position: absolute; inset: 0; opacity: 0; margin: 0; cursor: pointer; z-index: 1; }
            .ns-switch .ns-track {
                position: absolute; inset: 0; background: var(--ns-surface-3); border: 1px solid var(--ns-border);
                border-radius: 999px; transition: background .18s, border-color .18s;
            }
            .ns-switch .ns-thumb {
                position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; border-radius: 50%;
                background: var(--ns-text-3); transition: transform .18s, background .18s;
            }
            .ns-switch input:checked + .ns-track { background: var(--ns-primary); border-color: var(--ns-primary); }
            .ns-switch input:checked + .ns-track + .ns-thumb { transform: translateX(18px); background: #fff; }
            .ns-switch input:focus-visible + .ns-track { box-shadow: 0 0 0 3px var(--ns-primary-ring); }

            /* ── Banner de bloqueo de Hacienda ── */
            .ns-banner-locked {
                display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
                padding: 14px 18px; border-radius: var(--ns-radius); margin-bottom: 18px;
                background: linear-gradient(135deg, var(--ns-seal-soft), rgba(232,165,61,.04));
                border: 1px solid rgba(232,165,61,.3); color: var(--ns-seal); font-size: 13px;
            }
            .ns-banner-locked .ns-banner-left { display: flex; align-items: center; gap: 12px; }
            .ns-seal-badge {
                width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
                border: 1.5px dashed rgba(232,165,61,.55);
                display: flex; align-items: center; justify-content: center;
                color: var(--ns-seal); background: rgba(232,165,61,.08); font-size: 16px;
            }

            /* ── Pruebas de conexion de correo ── */
            .ns-prueba-resultado { margin-left: 10px; font-size: 12.5px; }
            .ns-prueba-resultado.ok  { color: var(--ns-success, #16a34a); }
            .ns-prueba-resultado.err { color: var(--ns-danger, #dc2626); }
            .ns-prueba-detalle { margin-top: 10px; font-size: 12.5px; line-height: 1.7; }

            /* ── Puestos de trabajo ── */
            .ns-puestos td { vertical-align: middle; }
            .ns-puestos .ns-puesto-nombre { min-width: 140px; }
            .ns-puestos .ns-puesto-impresora { min-width: 200px; }

            /* ── Captura de atajos ── */
            .ns-atajo-campo { cursor: pointer; text-align: center; font-weight: 600; letter-spacing: .5px; }
            .ns-atajo.grabando .ns-atajo-campo { border-color: var(--ns-primary); box-shadow: 0 0 0 3px var(--ns-primary-soft); color: var(--ns-primary); }
            .ns-atajo-aviso { color: var(--ns-danger, #dc2626); min-height: 16px; }
            .ns-atajo-aviso:empty { min-height: 0; }

            /* ── Continuidad de la numeracion ── */
            .ns-numeracion-aviso {
                display: flex; gap: 10px; align-items: flex-start;
                padding: 12px 14px; border-radius: var(--ns-radius-sm);
                background: var(--ns-seal-soft); color: var(--ns-seal);
                font-size: 12.5px; line-height: 1.5;
            }
            .ns-tipo-doc {
                display: inline-block; min-width: 26px; text-align: center;
                font-family: var(--ns-mono, monospace); font-size: 11px; font-weight: 700;
                padding: 1px 5px; margin-right: 4px; border-radius: 5px;
                background: var(--ns-surface-3); color: var(--ns-text-2);
            }
            .ns-clave-leida {
                margin-top: 8px; padding: 10px 12px; border-radius: var(--ns-radius-sm);
                background: var(--ns-surface-2); font-size: 12.5px; line-height: 1.6;
            }
            .ns-clave-leida b { font-family: var(--ns-mono, monospace); }

            /* ── Badge de ambiente ── */
            .ns-env-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 999px; margin-top: 6px; }
            .ns-env-badge.test { background: var(--ns-seal-soft); color: var(--ns-seal); }
            .ns-env-badge.prod { background: var(--ns-success-soft); color: var(--ns-success); }

            /* ── Dropzone de archivos ── */
            .ns-dropzone {
                position: relative; display: flex; align-items: center; gap: 12px;
                padding: 12px 14px; border: 1.5px dashed var(--ns-border); border-radius: var(--ns-radius-sm);
                background: var(--ns-surface-2); transition: border-color .15s, background .15s;
            }
            .ns-dropzone:hover, .ns-dropzone.ns-dragover { border-color: var(--ns-primary); background: var(--ns-primary-soft); }
            .ns-dropzone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
            .ns-dropzone-icon {
                width: 34px; height: 34px; border-radius: 9px; background: var(--ns-surface-3); color: var(--ns-text-2);
                display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;
            }
            .ns-dropzone.has-file .ns-dropzone-icon { background: var(--ns-success-soft); color: var(--ns-success); }
            .ns-dropzone-text b { font-size: 12.5px; display: block; color: var(--ns-text-1); }
            .ns-dropzone-text span { font-size: 11px; color: var(--ns-text-3); }

            /* Fila dropzone + botón "Subir" del certificado: antes el botón
               era btn-sm (más bajo que el dropzone) y quedaba centrado a la
               mitad de su altura, se veía flotando y desalineado. Con
               align-items:stretch el botón toma la misma altura del dropzone. */
            .ns-cert-upload-row { display: flex; gap: 8px; align-items: stretch; }
            .ns-cert-upload-row .ns-dropzone { flex: 1; }
            .ns-cert-upload-btn { white-space: nowrap; }

            /* ── File inputs modernos (input nativo, sin dropzone) ── */
            .ns-mockup input[type="file"] { display: block; }
            .ns-mockup input[type="file"]::file-selector-button {
                background: linear-gradient(135deg, var(--ns-primary) 0%, var(--ns-seal) 100%);
                color: #fff; padding: 8px 16px; border: none; border-radius: 4px;
                cursor: pointer; font-weight: 600; font-size: 13px; transition: all .2s;
            }
            .ns-mockup input[type="file"]::file-selector-button:hover { transform: translateY(-1px); }

            /* ── Barra de guardado ──
               Antes era "position: sticky", lo que la hacía flotar sobre el
               contenido al hacer scroll y a la vez quedar pegada al último
               recuadro cuando no había scroll — un ancla normal, con separación
               real arriba, es más predecible. */
            .ns-save-bar {
                background: var(--ns-surface-2); border: 1px solid var(--ns-border);
                padding: 14px 20px; margin-top: 24px;
                box-shadow: var(--ns-shadow-sm); border-radius: var(--ns-radius);
                display: flex; align-items: center; gap: 12px;
            }
            </style>

            <!-- NAV MOVIL -->
            <div class="ns-nav-mobile" id="nsNavMobile"></div>

            <!-- BUSCADOR -->
            <div class="ns-toolbar">
                <div class="ns-search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" id="ns-search-input" placeholder="Buscar un campo…" autocomplete="off">
                </div>
            </div>
            <div class="ns-no-results" id="ns-no-results">
                <i class="fa fa-search"></i>
                <div>No hay campos que coincidan con tu búsqueda.</div>
            </div>

            <!-- NAV SETTINGS -->
            <div class="row">
            <div class="col-md-2 ns-settings-nav" id="nsNavDesktop">
                <ul class="nav nav-pills nav-stacked">
                    <li class="active">
                        <a href="#tab-general" data-bs-toggle="pill">
                            <i class="fa fa-cog"></i>
                            <span class="nx-nav-label"><?= lang('settings_tab_general'); ?><span class="nx-nav-sub"><?= lang('settings_tab_general_sub'); ?></span></span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-emisor" data-bs-toggle="pill">
                            <i class="fa fa-file-text-o"></i>
                            <span class="nx-nav-label"><?= lang('settings_tab_emisor'); ?><span class="nx-nav-sub"><?= lang('settings_tab_emisor_sub'); ?></span></span>
                            <?php if (($settings->block_hacienda ?? '0') == '1'): ?>
                            <span class="ns-nav-badge" title="<?= lang('config_bloqueada_msg'); ?>"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-email" data-bs-toggle="pill">
                            <i class="fa fa-envelope"></i>
                            <span class="nx-nav-label"><?= lang('email'); ?><span class="nx-nav-sub"><?= lang('settings_tab_email_sub'); ?></span></span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-pos" data-bs-toggle="pill">
                            <i class="fa fa-shopping-cart"></i>
                            <span class="nx-nav-label"><?= lang('settings_tab_pos'); ?><span class="nx-nav-sub"><?= lang('settings_tab_pos_sub'); ?></span></span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-avanzado" data-bs-toggle="pill">
                            <i class="fa fa-wrench"></i>
                            <span class="nx-nav-label"><?= lang('settings_tab_avanzado'); ?><span class="nx-nav-sub"><?= lang('settings_tab_avanzado_sub'); ?></span></span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-sinpe" data-bs-toggle="pill" id="navTabSinpe">
                            <i class="fa fa-mobile"></i>
                            <span class="nx-nav-label"><?= lang('settings_tab_sinpe'); ?><span class="nx-nav-sub"><?= lang('settings_tab_sinpe_sub'); ?></span></span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-md-10">
                <div class="tab-content">

                    <!-- ==================== TAB 1: GENERAL ==================== -->
                    <div class="tab-pane active" id="tab-general">

                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-building-o"></i></div>
                                <div class="ns-card-head-text"><strong>Datos del negocio</strong><small>Se muestran en el POS y en los reportes</small></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="site_name"><i class="fa fa-building-o"></i> <?php echo lang('site_name'); ?></label>
                                            <?php echo form_input('site_name', $settings->site_name ?? '', 'class="form-control" id="site_name" required="required"'); ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tel"><i class="fa fa-phone"></i> <?php echo lang('tel'); ?></label>
                                            <?php echo form_input('tel', $settings->tel ?? '', 'class="form-control" id="tel" required="required"'); ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="currency_prefix"><i class="fa fa-money"></i> <?php echo lang('currency_code'); ?></label>
                                            <?php echo form_input('currency_prefix', $settings->currency_prefix ?? 'CRC', 'class="form-control ns-mono" id="currency_prefix" maxlength="3" required="required" placeholder="CRC"'); ?>
                                            <span class="help-block">3 letras, ej: CRC, USD</span>
                                        </div>
                                        <div class="mb-3">
                                            <label for="language"><i class="fa fa-globe"></i> <?php echo lang('language'); ?></label>
                                            <?php
                                            $available_langs = array(
                                                'spanish' => 'Español',
                                                'chinese' => 'Chino (Simplificado)',
                                                'english' => 'English'
                                            );
                                            echo form_dropdown('language', $available_langs, $settings->language ?? 'spanish', 'class="form-control tom-select" id="language" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="dateformat"><i class="fa fa-calendar"></i> <?php echo lang('dateformat'); ?></label>
                                            <?php
                                            $fmt_fecha = $settings->dateformat ?? 'd/m/Y';
                                            echo form_dropdown('dateformat', formatos_fecha($fmt_fecha), $fmt_fecha, 'class="form-control tom-select" id="dateformat" required="required" style="width:100%;"');
                                            ?>
                                            <span class="help-block"><?= lang('fmt_ayuda_fecha'); ?></span>
                                        </div>
                                        <div class="mb-3">
                                            <label for="timeformat"><i class="fa fa-clock-o"></i> <?php echo lang('timeformat'); ?></label>
                                            <?php
                                            $fmt_hora = $settings->timeformat ?? 'h:i A';
                                            echo form_dropdown('timeformat', formatos_hora($fmt_hora), $fmt_hora, 'class="form-control tom-select" id="timeformat" required="required" style="width:100%;"');
                                            ?>
                                            <span class="help-block"><?= lang('fmt_ayuda_hora'); ?></span>
                                        </div>
                                        <div class="mb-3">
                                            <label for="rows_per_page"><i class="fa fa-list"></i> <?php echo lang('row_per_page'); ?></label>
                                            <?php
                                            $rw = array('10' => '10', '25' => '25', '50' => '50', '100' => '100');
                                            echo form_dropdown('rows_per_page', $rw, $settings->rows_per_page ?? '25', 'class="form-control tom-select" id="rows_per_page" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="pin_code"><i class="fa fa-lock"></i> <?php echo lang('delete_code'); ?> (PIN)</label>
                                            <div class="input-group">
                                                <input type="password" name="pin_code" id="pin_code" value="<?php echo htmlspecialchars($settings->pin_code ?? ''); ?>" class="form-control ns-mono" pattern="[0-9]{4,8}" placeholder="<?= lang('placeholder_pin'); ?>">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="pin_code" title="<?= lang('ver_ocultar'); ?>"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                            <span class="help-block"><?= lang('pin_seguridad_help'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-paint-brush"></i></div>
                                <div class="ns-card-head-text"><strong>Apariencia</strong><small>Tema visual y logotipo de acceso</small></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?php if (($settings->theme_style ?? '') != 'purple' && ($settings->theme_style ?? '') != 'green'): ?>
                                        <div class="mb-3">
                                            <label for="theme_style"><i class="fa fa-paint-brush"></i> <?php echo lang('theme_style'); ?></label>
                                            <?php
                                            $ths = array(
                                                'black'        => 'Black',
                                                'black-light'  => 'Black Light',
                                                'blue'         => 'Blue',
                                                'blue-light'   => 'Blue Light',
                                                'green-light'  => 'Green Light',
                                                'purple-light' => 'Purple Light',
                                                'red'          => 'Red',
                                                'red-light'    => 'Red Light',
                                                'yellow'       => 'Yellow',
                                                'yellow-light' => 'Yellow Light',
                                                'green'        => 'Green',
                                                'purple'       => 'Purple',
                                            );
                                            echo form_dropdown('theme_style', $ths, $settings->theme_style ?? 'black', 'class="form-control tom-select" id="theme_style" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="mb-3">
                                            <label><i class="fa fa-image"></i> <?php echo lang('login_logo'); ?></label>
                                            <div class="ns-dropzone" id="ns-logo-dropzone">
                                                <input type="file" name="userfile" id="logo" accept="image/gif,image/jpeg,image/png">
                                                <div class="ns-dropzone-icon"><i class="fa fa-upload"></i></div>
                                                <div class="ns-dropzone-text">
                                                    <b id="ns-logo-label"><?php echo lang('login_logo'); ?></b>
                                                    <span>GIF/JPG/PNG · máx 300×80px · 300KB</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text">
                                                    <b><i class="fa fa-th-large"></i> <?= lang('panel_categorias_pos'); ?></b>
                                                    <span><?= lang('mostrar_categorias'); ?> / <?= lang('ocultar_categorias'); ?></span>
                                                </div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="show_categories" <?= (($settings->show_categories ?? '1') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <select name="show_categories" id="show_categories" class="form-control ns-select-hidden">
                                                <option value="1" <?= (($settings->show_categories ?? '1') == '1') ? 'selected' : ''; ?>><?= lang('mostrar_categorias'); ?></option>
                                                <option value="0" <?= (($settings->show_categories ?? '1') == '0') ? 'selected' : ''; ?>><?= lang('ocultar_categorias'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /#tab-general -->

                    <!-- ==================== TAB 2: EMISOR FE ==================== -->
                    <div class="tab-pane" id="tab-emisor">

                        <?php if (($settings->block_hacienda ?? '0') == '1'): ?>
                        <div class="ns-banner-locked">
                            <div class="ns-banner-left">
                                <div class="ns-seal-badge"><i class="fa fa-lock"></i></div>
                                <span><?= lang('config_bloqueada_msg'); ?></span>
                            </div>
                            <a href="<?= site_url('settings/desbloquear_hacienda') ?>"
                               class="btn btn-warning btn-sm"
                               onclick="return confirm('<?= lang('desbloquear_confirm'); ?>')">
                                <i class="fa fa-unlock"></i> <?= lang('desbloquear'); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <fieldset <?= (($settings->block_hacienda ?? '0') == '1') ? 'disabled' : '' ?>>

                        <!-- AMBIENTE -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-exchange"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('ambiente_hacienda'); ?></strong><small>Sandbox de pruebas o producción real</small></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="ambiente"><i class="fa fa-server"></i> <?= lang('ambiente_activo_label'); ?></label>
                                            <?php
                                            $amb_opts = ['test' => lang('pruebas_sandbox'), 'prod' => lang('produccion')];
                                            $amb_actual = $settings->ambiente ?? 'test';
                                            echo form_dropdown('ambiente', $amb_opts, $amb_actual, 'class="form-control tom-select" id="ambiente" style="width:100%;"');
                                            ?>
                                            <?php if ($amb_actual == 'prod'): ?>
                                            <span class="ns-env-badge prod"><i class="fa fa-check"></i> <?= lang('produccion_activa_label'); ?></span>
                                            <?php else: ?>
                                            <span class="ns-env-badge test"><i class="fa fa-flask"></i> <?= lang('pruebas_activa_label'); ?></span>
                                            <?php endif; ?>
                                            <span class="help-block"><i class="fa fa-exclamation-triangle text-warning"></i> <?= lang('cambie_a_produccion'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IDENTIFICACION DEL EMISOR -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-id-card-o"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('identificacion_emisor'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="tipo_doc_emisor_display"><i class="fa fa-id-badge"></i> <?= lang('tipo_doc_cedula'); ?></label>
                                            <?php
                                            $tipo_doc_emisor = array("01" => lang('Cedula Identidad'), "02" => lang('Cedula Juridica'), "03" => lang('Dimex'), "04" => lang('NITE'));
                                            echo form_dropdown('tipo_doc_emisor_display', $tipo_doc_emisor, $settings->tipo_doc_emisor ?? '02', 'class="form-control" id="tipo_doc_emisor_display" style="width:100%;" disabled');
                                            ?>
                                            <input type="hidden" id="tipo_doc_emisor" name="tipo_doc_emisor" value="<?= htmlspecialchars($settings->tipo_doc_emisor ?? '02') ?>">
                                            <span class="help-block">Se determina automáticamente según el formato de la cédula.</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="cedula_emisor"><i class="fa fa-hashtag"></i> <?= lang('cedula_documento'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->cedula_emisor ?? '') ?>" class="form-control ns-mono" id="cedula_emisor" name="cedula_emisor" type="text" placeholder="3101000000">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-primary" id="btn-buscar-cedula" title="Buscar en Hacienda"><i class="fa fa-search"></i> Buscar</button>
                                                </span>
                                            </div>
                                            <span class="help-block" id="ae-status">Completa el nombre y la actividad económica desde Hacienda.</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombre_emisor"><i class="fa fa-user"></i> <?= lang('nombre_obligado'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->nombre_emisor ?? '') ?>" class="form-control" id="nombre_emisor" name="nombre_emisor" type="text" readonly required placeholder="Se completa al buscar la cédula">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombre_comercial"><i class="fa fa-briefcase"></i> <?= lang('nombre_comercial_fantasia'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->nombre_comercial ?? '') ?>" class="form-control" id="nombre_comercial" name="nombre_comercial" type="text" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="email_emisor"><i class="fa fa-envelope-o"></i> <?= lang('correo_electronico'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->email_emisor ?? '') ?>" class="form-control" id="email_emisor" name="email_emisor" type="email" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="cod_telefono_emisor"><i class="fa fa-flag"></i> <?= lang('cod_pais'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->cod_telefono_emisor ?? '506') ?>" class="form-control ns-mono" id="cod_telefono_emisor" name="cod_telefono_emisor" type="text" placeholder="506" maxlength="3">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="telefono_emisor"><i class="fa fa-phone"></i> <?= lang('telefono_sin_guiones'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->telefono_emisor ?? '') ?>" class="form-control ns-mono" id="telefono_emisor" name="telefono_emisor" type="text" placeholder="22220000" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="fax_emisor"><i class="fa fa-fax"></i> <?= lang('fax_sin_guiones'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->fax_emisor ?? '') ?>" class="form-control ns-mono" id="fax_emisor" name="fax_emisor" type="text" placeholder="22220000" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DIRECCION -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-map-marker"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('direccion_tributario'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cod_provincia"><i class="fa fa-map"></i> Provincia</label>
                                            <select class="form-control" id="cod_provincia" name="cod_provincia" required>
                                                <option value="">Seleccione…</option>
                                                <?php foreach ($provincias as $provincia): ?>
                                                <option value="<?= htmlspecialchars($provincia->codigo) ?>" <?= (($settings->cod_provincia ?? '') == $provincia->codigo) ? 'selected' : '' ?>><?= htmlspecialchars($provincia->nombre) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cod_canton"><i class="fa fa-map"></i> Cantón</label>
                                            <select class="form-control" id="cod_canton" name="cod_canton" required>
                                                <option value="">Seleccione…</option>
                                                <?php foreach ($cantones_actuales as $canton): ?>
                                                <option value="<?= htmlspecialchars($canton->codigo) ?>" <?= (($settings->cod_canton ?? '') == $canton->codigo) ? 'selected' : '' ?>><?= htmlspecialchars($canton->nombre) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cod_distrito"><i class="fa fa-map"></i> Distrito</label>
                                            <select class="form-control" id="cod_distrito" name="cod_distrito" required>
                                                <option value="">Seleccione…</option>
                                                <?php foreach ($distritos_actuales as $distrito): ?>
                                                <option value="<?= htmlspecialchars($distrito->codigo) ?>" <?= (($settings->cod_distrito ?? '') == $distrito->codigo) ? 'selected' : '' ?>><?= htmlspecialchars($distrito->nombre) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cod_barrio"><i class="fa fa-map"></i> Barrio</label>
                                            <select class="form-control" id="cod_barrio" name="cod_barrio">
                                                <option value="">Seleccione…</option>
                                                <?php foreach ($barrios_actuales as $barrio): ?>
                                                <option value="<?= htmlspecialchars($barrio->codigo) ?>" <?= (($settings->cod_barrio ?? '') == $barrio->codigo) ? 'selected' : '' ?>><?= htmlspecialchars($barrio->nombre) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="otras_senas"><i class="fa fa-home"></i> <?= lang('otras_senas_label'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->otras_senas ?? '') ?>" class="form-control" id="otras_senas" name="otras_senas" type="text" placeholder="<?= lang('placeholder_dir_desc'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACTIVIDAD ECONOMICA -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-industry"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('actividad_economica'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="default_actividad"><i class="fa fa-list-alt"></i> <?= lang('actividad_predeterminada'); ?></label>
                                            <?php
                                            // El valor es el codigo de actividad de Hacienda, no el id de la fila:
                                            // va tal cual a <CodigoActividadEmisor>, que exige 6 caracteres exactos.
                                            $act_actual = (string) ($settings->default_actividad ?? '');
                                            $act_opts = array();
                                            foreach ($actividadeconomica as $actividad) {
                                                $act_opts[$actividad->codigo] = $actividad->codigo . ' — ' . $actividad->descripcion;
                                            }
                                            // Las actividades del emisor las trae el boton "Buscar" desde Hacienda y
                                            // no estan en la tabla local: sin esta opcion, reguardar borraria el codigo.
                                            if ($act_actual !== '' && !isset($act_opts[$act_actual])) {
                                                $act_opts = array($act_actual => $act_actual) + $act_opts;
                                            }
                                            echo form_dropdown('default_actividad', $act_opts, $act_actual, 'class="form-control tom-select" style="width:100%;" id="default_actividad" required="required"');
                                            ?>
                                            <span class="help-block">Se completa automáticamente según la cédula del emisor.</span>
                                            <?php if ($act_actual !== '' && strlen($act_actual) !== 6): ?>
                                            <span class="help-block text-danger"><i class="fa fa-exclamation-triangle"></i> <?= lang('actividad_largo_invalido'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CONTINUIDAD DE LA NUMERACION -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-sort-numeric-asc"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('continuidad_numeracion'); ?></strong><small><?= lang('continuidad_numeracion_sub'); ?></small></div>
                            </div>
                            <div class="card-body">
                                <div class="ns-numeracion-aviso">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    <span><?= lang('continuidad_advertencia'); ?></span>
                                </div>

                                <div class="row" style="margin-top:14px;">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="clave_ultima"><i class="fa fa-key"></i> <?= lang('ultima_clave_label'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->clave_ultima ?? '') ?>" class="form-control ns-mono" id="clave_ultima" name="clave_ultima" type="text" maxlength="50" inputmode="numeric" placeholder="50 <?= lang('digitos'); ?>">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-primary" id="btnLeerClave"><i class="fa fa-magic"></i> <?= lang('leer_clave_btn'); ?></button>
                                                </span>
                                            </div>
                                            <span class="help-block"><?= lang('ultima_clave_ayuda'); ?></span>
                                            <div id="claveLeida" class="ns-clave-leida" style="display:none;"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <?php foreach (tipos_comprobante() as $tipo => $etiqueta): ?>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="consec_inicial_<?= $tipo ?>"><span class="ns-tipo-doc"><?= $tipo ?></span> <?= $etiqueta ?></label>
                                            <input value="<?= (int) ($settings->{'consec_inicial_' . $tipo} ?? 0) ?>" class="form-control ns-mono" id="consec_inicial_<?= $tipo ?>" name="consec_inicial_<?= $tipo ?>" type="number" min="0" step="1">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <span class="help-block"><i class="fa fa-info-circle"></i> <?= lang('continuidad_ayuda'); ?></span>
                            </div>
                        </div>

                        <!-- TOKENS API HACIENDA -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-key"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('tokens_api_hacienda'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- PRUEBAS -->
                                    <div class="col-md-12">
                                        <h4><span class="label label-warning"><i class="fa fa-flask"></i> Pruebas (Sandbox)</span></h4>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label for="user_token_test"><i class="fa fa-user-o"></i> <?= lang('usuario_prueba'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->user_token_test ?? '') ?>" class="form-control ns-mono" id="user_token_test" name="user_token_test" type="text" placeholder="cpj-3-101-000000@stag.comprobanteselectronicos.go.cr">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="password_token_test"><i class="fa fa-lock"></i> <?= lang('password_prueba'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->password_token_test ?? '') ?>" class="form-control ns-mono" id="password_token_test" name="password_token_test" type="password" placeholder="<?= lang('password_prueba'); ?>">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="password_token_test" title="<?= lang('ver_ocultar'); ?>"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label><br>
                                            <span id="comprueba_test" class="btn btn-success btn-block"><i class="fa fa-check-circle"></i> <?= lang('probar_cred_prueba'); ?></span>
                                        </div>
                                    </div>

                                    <!-- PRODUCCION -->
                                    <div class="col-md-12">
                                        <h4><span class="label label-success"><i class="fa fa-check"></i> Produccion</span></h4>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label for="user_token_prod"><i class="fa fa-user-o"></i> <?= lang('usuario_produccion'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->user_token_prod ?? '') ?>" class="form-control ns-mono" id="user_token_prod" name="user_token_prod" type="text" placeholder="cpj-3-101-000000@prod.comprobanteselectronicos.go.cr">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="password_token_prod"><i class="fa fa-lock"></i> <?= lang('password_produccion'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->password_token_prod ?? '') ?>" class="form-control ns-mono" id="password_token_prod" name="password_token_prod" type="password" placeholder="<?= lang('password_produccion'); ?>">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="password_token_prod" title="<?= lang('ver_ocultar'); ?>"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label><br>
                                            <span id="comprueba_prod" class="btn btn-success btn-block"><i class="fa fa-check-circle"></i> <?= lang('probar_cred_prod'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CERTIFICADO DIGITAL -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-certificate"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('certificado_digital'); ?></strong><small><?= lang('certificado_por_ambiente_info'); ?></small></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    $cert_en_uso = ($settings->ambiente ?? 'test') === 'prod' ? 'prod' : 'test';
                                    $cert_ambientes = array(
                                        'test' => array('titulo' => lang('pruebas_sandbox'), 'label' => 'warning', 'icono' => 'fa-flask'),
                                        'prod' => array('titulo' => lang('produccion'),      'label' => 'success', 'icono' => 'fa-check'),
                                    );
                                    foreach ($cert_ambientes as $amb => $cfg):
                                        $cert_disponibles = certificados_p12($amb);
                                        $cert_ced = $settings->{'certificado_ced_' . $amb} ?? '';
                                        // Con un solo .p12 en la carpeta no hay nada que elegir: queda puesto.
                                        if ($cert_ced === '' && count($cert_disponibles) === 1) {
                                            $cert_ced = key($cert_disponibles);
                                        }
                                        $cert_existe = isset($cert_disponibles[$cert_ced]);
                                    ?>
                                    <div class="col-md-12">
                                        <h4>
                                            <span class="label label-<?= $cfg['label'] ?>"><i class="fa <?= $cfg['icono'] ?>"></i> <?= $cfg['titulo'] ?></span>
                                            <?php if ($amb === $cert_en_uso): ?>
                                            <span class="ns-env-badge <?= $amb ?>"><i class="fa fa-bolt"></i> <?= lang('certificado_en_uso'); ?></span>
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="certificado_ced_<?= $amb ?>"><i class="fa fa-file-o"></i> <?= lang('archivo_certificado_label'); ?></label>
                                            <?php if ($cert_disponibles): ?>
                                                <?php echo form_dropdown('certificado_ced_' . $amb, $cert_disponibles, $cert_ced, 'class="form-control tom-select ns-mono" id="certificado_ced_' . $amb . '" style="width:100%;"'); ?>
                                                <span class="help-block"><?= lang('archivo_certificado_ayuda'); ?></span>
                                            <?php else: ?>
                                                <input type="hidden" name="certificado_ced_<?= $amb ?>" value="">
                                                <p class="text-muted" style="margin:6px 0 0;"><i class="fa fa-arrow-right"></i> <?= lang('archivo_certificado_vacio'); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="certificado_pin_<?= $amb ?>"><i class="fa fa-key"></i> <?= lang('pin_certificado'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->{'certificado_pin_' . $amb} ?? '') ?>" class="form-control ns-mono" id="certificado_pin_<?= $amb ?>" name="certificado_pin_<?= $amb ?>" type="password" placeholder="0000">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="certificado_pin_<?= $amb ?>" title="<?= lang('ver_ocultar'); ?>"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label><i class="fa fa-upload"></i> <?= lang('subir_certificado_label'); ?></label>
                                            <!-- HTML no admite formularios anidados: estos campos se atan con form= a los #ns-cert-form-* que viven fuera del formulario de ajustes. -->
                                            <div class="ns-cert-upload-row">
                                                <div class="ns-dropzone" id="ns-cert-dropzone-<?= $amb ?>">
                                                    <input type="file" name="certificado_p12" accept=".p12" form="ns-cert-form-<?= $amb ?>" required>
                                                    <div class="ns-dropzone-icon"><i class="fa fa-certificate"></i></div>
                                                    <div class="ns-dropzone-text">
                                                        <b id="ns-cert-label-<?= $amb ?>"><?= lang('subir_certificado_label'); ?></b>
                                                        <span>files/certificados/<?= $amb ?>/</span>
                                                    </div>
                                                </div>
                                                <button type="submit" form="ns-cert-form-<?= $amb ?>" class="btn btn-primary ns-cert-upload-btn"><i class="fa fa-upload"></i> <?= lang('subir'); ?></button>
                                            </div>
                                            <?php if ($cert_existe): ?>
                                                <span class="help-block text-success"><i class="fa fa-check-circle"></i> <?= lang('certificado_cargado'); ?> <strong><?= htmlspecialchars($cert_ced) ?>.p12</strong></span>
                                            <?php else: ?>
                                                <span class="help-block text-warning"><i class="fa fa-exclamation-triangle"></i> <?= lang('no_hay_certificado'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <span class="help-block"><i class="fa fa-info-circle"></i> <?= lang('certificado_ambiente_ayuda'); ?></span>
                            </div>
                        </div>

                        <!-- FOOTER FE -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-align-left"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('textos_comprobantes'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="footer_hacienda_fe"><i class="fa fa-file-text"></i> <?= lang('footer_fe_label'); ?></label>
                                            <textarea name="footer_hacienda_fe" id="footer_hacienda_fe" class="form-control" rows="3" placeholder="<?= lang('placeholder_footer_fe'); ?>"><?= htmlspecialchars($settings->footer_hacienda_fe ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="footer_hacienda_nc"><i class="fa fa-file-text-o"></i> <?= lang('footer_nc_label'); ?></label>
                                            <textarea name="footer_hacienda_nc" id="footer_hacienda_nc" class="form-control" rows="3" placeholder="<?= lang('placeholder_footer_nc'); ?>"><?= htmlspecialchars($settings->footer_hacienda_nc ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUEO DE LA CONFIGURACION -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-lock"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('bloqueo_config_hacienda'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="ns-toggle-row">
                                            <div class="ns-toggle-text">
                                                <b><i class="fa fa-lock"></i> <?= lang('bloquear_configuracion'); ?></b>
                                                <span><?= lang('bloqueo_advertencia'); ?></span>
                                            </div>
                                            <label class="ns-switch">
                                                <input type="checkbox" class="ns-switch-bind" data-bind="block_hacienda" <?= (($settings->block_hacienda ?? 0) == 1) ? 'checked' : ''; ?>>
                                                <span class="ns-track"></span><span class="ns-thumb"></span>
                                            </label>
                                        </div>
                                        <?php
                                        $block_opts = array(0 => lang('no_bloqueada'), 1 => lang('bloquear_configuracion'));
                                        echo form_dropdown('block_hacienda', $block_opts, $settings->block_hacienda ?? 0, 'class="form-control ns-select-hidden" id="block_hacienda"');
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        </fieldset>

                    </div><!-- /#tab-emisor -->

                    <!-- ==================== TAB 3: EMAIL ==================== -->
                    <div class="tab-pane" id="tab-email">

                        <?php
                        // Host, puerto y cifrado de cada proveedor. Evita que el usuario
                        // tenga que buscarlos y equivocarse en el puerto o el cifrado.
                        $proveedores_correo = array(
                            'gmail'     => array('etiqueta' => 'Gmail / Google Workspace', 'smtp' => 'smtp.gmail.com',        'smtp_port' => '587', 'smtp_crypto' => 'tls', 'imap' => 'imap.gmail.com',          'imap_port' => '993', 'imap_crypto' => 'ssl', 'oauth' => 1),
                            'microsoft' => array('etiqueta' => 'Outlook / Microsoft 365',  'smtp' => 'smtp.office365.com',    'smtp_port' => '587', 'smtp_crypto' => 'tls', 'imap' => 'outlook.office365.com',  'imap_port' => '993', 'imap_crypto' => 'ssl', 'oauth' => 0),
                            'zoho'      => array('etiqueta' => 'Zoho Mail',                'smtp' => 'smtp.zoho.com',         'smtp_port' => '587', 'smtp_crypto' => 'tls', 'imap' => 'imap.zoho.com',          'imap_port' => '993', 'imap_crypto' => 'ssl', 'oauth' => 0),
                            'yahoo'     => array('etiqueta' => 'Yahoo Mail',               'smtp' => 'smtp.mail.yahoo.com',   'smtp_port' => '587', 'smtp_crypto' => 'tls', 'imap' => 'imap.mail.yahoo.com',    'imap_port' => '993', 'imap_crypto' => 'ssl', 'oauth' => 0),
                            'otro'      => array('etiqueta' => lang('otro_proveedor'),     'smtp' => '',                      'smtp_port' => '587', 'smtp_crypto' => 'tls', 'imap' => '',                       'imap_port' => '993', 'imap_crypto' => 'ssl', 'oauth' => 0),
                        );
                        $opts_proveedor = array();
                        foreach ($proveedores_correo as $k => $v) { $opts_proveedor[$k] = $v['etiqueta']; }
                        $mail_auth        = $settings->mail_auth ?? 'password';
                        $mail_client_auth = $settings->mail_client_auth ?? 'password';
                        ?>
                        <script>window._proveedoresCorreo = <?= json_encode($proveedores_correo) ?>;</script>

                        <!-- CORREO DE SALIDA -->
                        <style>
                            .ns-correo-sin-remitente{border:1px solid var(--ns-danger,#dc2626);border-radius:8px;padding:14px 16px;margin-bottom:14px;background:var(--ns-danger-soft,rgba(220,38,38,.07));font-size:13px;line-height:1.55;}
                            .ns-gmail-shortcut{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;border:1px solid var(--ns-primary,#2563eb);border-radius:8px;padding:14px 16px;margin-bottom:16px;background:var(--ns-primary-soft,rgba(37,99,235,.06));}
                            .ns-gmail-shortcut-text{display:flex;align-items:flex-start;gap:10px;font-size:13px;line-height:1.5;}
                            .ns-gmail-shortcut-text i.fa-google{font-size:20px;margin-top:2px;color:#ea4335;}
                            .ns-gmail-shortcut-text strong{display:block;}
                            .ns-gmail-shortcut-text span{color:var(--ns-txt2,#666);}
                            .ns-gmail-shortcut-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
                        </style>
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-paper-plane-o"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('correo_salida'); ?></strong><small><?= lang('correo_salida_sub'); ?></small></div>
                            </div>
                            <div class="card-body">
                                <div class="ns-gmail-shortcut">
                                    <div class="ns-gmail-shortcut-text">
                                        <i class="fa fa-google"></i>
                                        <div>
                                            <strong><?= lang('gmail_atajo_titulo'); ?></strong>
                                            <span><?= lang('gmail_atajo_envio_sub'); ?></span>
                                        </div>
                                    </div>
                                    <div class="ns-gmail-shortcut-actions">
                                        <?php if ($mail_auth === 'oauth_google' && !empty($settings->mail_oauth_email)): ?>
                                            <span class="ns-env-badge prod"><i class="fa fa-check"></i> <?= htmlspecialchars($settings->mail_oauth_email) ?></span>
                                            <a href="<?= site_url('mailauth/desconectar/envio') ?>" class="btn btn-default btn-sm"><i class="fa fa-unlink"></i> <?= lang('desconectar'); ?></a>
                                        <?php else: ?>
                                            <a href="<?= site_url('mailauth/conectar/envio') ?>" class="btn btn-primary"><i class="fa fa-google"></i> <?= lang('conectar_con_gmail'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!remitente_correo($settings)): ?>
                                <div class="ns-correo-sin-remitente">
                                    <div><i class="fa fa-exclamation-triangle"></i> <strong><?= lang('correo_sin_remitente_titulo'); ?></strong></div>
                                    <p style="margin:6px 0 0;"><?= lang('correo_sin_remitente'); ?></p>
                                </div>
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="default_email"><i class="fa fa-envelope-o"></i> <?= lang('default_email'); ?></label>
                                            <?php echo form_input('default_email', $settings->default_email ?? '', 'class="form-control" id="default_email" type="email" required="required"'); ?>
                                            <span class="help-block"><?= lang('remitente_ayuda'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="protocol"><i class="fa fa-cogs"></i> <?= lang('email_protocol'); ?></label>
                                            <?php
                                            $popt = array('smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'mail' => lang('funcion_mail_php'));
                                            echo form_dropdown('protocol', $popt, $settings->protocol ?? 'smtp', 'class="form-control tom-select" id="protocol" style="width:100%;" required="required"');
                                            ?>
                                            <span class="help-block"><?= lang('protocolo_ayuda'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 ns-solo-smtp">
                                        <div class="mb-3">
                                            <label for="proveedor_smtp"><i class="fa fa-magic"></i> <?= lang('proveedor_correo'); ?></label>
                                            <select id="proveedor_smtp" class="form-control" data-destino="smtp">
                                                <?php foreach ($opts_proveedor as $k => $v): ?>
                                                <option value="<?= $k ?>"><?= $v ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="help-block"><?= lang('proveedor_ayuda'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row ns-solo-sendmail" style="display:none;">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mailpath"><i class="fa fa-folder-o"></i> <?= lang('mailpath'); ?></label>
                                            <?php echo form_input('mailpath', $settings->mailpath ?? '/usr/sbin/sendmail', 'class="form-control ns-mono" id="mailpath" placeholder="/usr/sbin/sendmail"'); ?>
                                            <span class="help-block"><?= lang('ruta_sendmail'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="ns-solo-smtp">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="smtp_host"><i class="fa fa-server"></i> <?= lang('smtp_host'); ?></label>
                                                <?php echo form_input('smtp_host', $settings->smtp_host ?? '', 'class="form-control ns-mono" id="smtp_host" placeholder="smtp.gmail.com"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="mb-3">
                                                <label for="smtp_port"><i class="fa fa-plug"></i> <?= lang('smtp_port'); ?></label>
                                                <?php echo form_input('smtp_port', $settings->smtp_port ?? '587', 'class="form-control ns-mono" id="smtp_port" placeholder="587"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="mb-3">
                                                <label for="smtp_crypto"><i class="fa fa-shield"></i> <?= lang('smtp_crypto'); ?></label>
                                                <?php
                                                $crypto_opt = array('tls' => 'STARTTLS', 'ssl' => 'SSL/TLS', '' => lang('none'));
                                                echo form_dropdown('smtp_crypto', $crypto_opt, $settings->smtp_crypto ?? 'tls', 'class="form-control" id="smtp_crypto" style="width:100%;"');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="smtp_user"><i class="fa fa-user-o"></i> <?= lang('smtp_user'); ?></label>
                                                <?php echo form_input('smtp_user', $settings->smtp_user ?? '', 'class="form-control" id="smtp_user" placeholder="' . lang('placeholder_smtp_user') . '"'); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="mail_auth"><i class="fa fa-key"></i> <?= lang('tipo_autenticacion'); ?></label>
                                                <?php
                                                $auth_opts = array('password' => lang('auth_password'), 'oauth_google' => lang('auth_oauth_google'));
                                                echo form_dropdown('mail_auth', $auth_opts, $mail_auth, 'class="form-control ns-auth-select" id="mail_auth" data-grupo="envio" style="width:100%;"');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4 ns-auth-envio-password" <?= $mail_auth === 'oauth_google' ? 'style="display:none;"' : '' ?>>
                                            <div class="mb-3">
                                                <label for="smtp_pass"><i class="fa fa-lock"></i> <?= lang('smtp_pass'); ?></label>
                                                <div class="input-group">
                                                    <input type="password" name="smtp_pass" id="smtp_pass" value="<?= htmlspecialchars($settings->smtp_pass ?? '') ?>" class="form-control" placeholder="<?= lang('placeholder_smtp_pass'); ?>" autocomplete="new-password">
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-default btn-toggle-pw" data-target="smtp_pass" title="<?= lang('ver_ocultar'); ?>"><i class="fa fa-eye"></i></button>
                                                    </span>
                                                </div>
                                                <span class="help-block"><?= lang('password_app_ayuda'); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-5 ns-auth-envio-oauth" <?= $mail_auth === 'oauth_google' ? '' : 'style="display:none;"' ?>>
                                            <div class="mb-3">
                                                <label><i class="fa fa-google"></i> <?= lang('cuenta_google'); ?></label>
                                                <p class="help-block" style="margin:4px 0;"><?= lang('gmail_gestion_arriba'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-default" id="btnProbarEnvio"><i class="fa fa-plug"></i> <?= lang('probar_conexion'); ?></button>
                                <span id="resultadoEnvio" class="ns-prueba-resultado"></span>
                            </div>
                        </div>

                        <!-- CORREO DE ENTRADA -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-inbox"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('correo_entrada'); ?></strong><small><?= lang('correo_entrada_sub'); ?></small></div>
                            </div>
                            <div class="card-body">
                                <div class="ns-gmail-shortcut">
                                    <div class="ns-gmail-shortcut-text">
                                        <i class="fa fa-google"></i>
                                        <div>
                                            <strong><?= lang('gmail_atajo_titulo'); ?></strong>
                                            <span><?= lang('gmail_atajo_recepcion_sub'); ?></span>
                                        </div>
                                    </div>
                                    <div class="ns-gmail-shortcut-actions">
                                        <?php if ($mail_client_auth === 'oauth_google' && !empty($settings->mail_client_oauth_email)): ?>
                                            <span class="ns-env-badge prod"><i class="fa fa-check"></i> <?= htmlspecialchars($settings->mail_client_oauth_email) ?></span>
                                            <a href="<?= site_url('mailauth/desconectar/recepcion') ?>" class="btn btn-default btn-sm"><i class="fa fa-unlink"></i> <?= lang('desconectar'); ?></a>
                                        <?php else: ?>
                                            <a href="<?= site_url('mailauth/conectar/recepcion') ?>" class="btn btn-primary"><i class="fa fa-google"></i> <?= lang('conectar_con_gmail'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text">
                                                    <b><i class="fa fa-download"></i> <?= lang('leer_facturas_compra'); ?></b>
                                                    <span><?= lang('leer_facturas_compra_sub'); ?></span>
                                                </div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="mail_client_enabled" <?= (($settings->mail_client_enabled ?? 0) == 1) ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $rec_opts = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('mail_client_enabled', $rec_opts, $settings->mail_client_enabled ?? 0, 'class="form-control ns-select-hidden" id="mail_client_enabled"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="proveedor_imap"><i class="fa fa-magic"></i> <?= lang('proveedor_correo'); ?></label>
                                            <select id="proveedor_imap" class="form-control" data-destino="imap">
                                                <?php foreach ($opts_proveedor as $k => $v): ?>
                                                <option value="<?= $k ?>"><?= $v ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mail_client_carpeta"><i class="fa fa-folder-open-o"></i> <?= lang('carpeta_imap'); ?></label>
                                            <?php echo form_input('mail_client_carpeta', $settings->mail_client_carpeta ?? 'INBOX', 'class="form-control ns-mono" id="mail_client_carpeta" placeholder="INBOX"'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mail_client_host"><i class="fa fa-server"></i> <?= lang('imap_host'); ?></label>
                                            <?php echo form_input('mail_client_host', $settings->mail_client_host ?? '', 'class="form-control ns-mono" id="mail_client_host" placeholder="imap.gmail.com"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="mail_client_port"><i class="fa fa-plug"></i> <?= lang('smtp_port'); ?></label>
                                            <?php echo form_input('mail_client_port', $settings->mail_client_port ?? '993', 'class="form-control ns-mono" id="mail_client_port" placeholder="993"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="mail_client_crypto"><i class="fa fa-shield"></i> <?= lang('smtp_crypto'); ?></label>
                                            <?php
                                            $crypto_imap = array('ssl' => 'SSL/TLS', 'tls' => 'STARTTLS', '' => lang('none'));
                                            echo form_dropdown('mail_client_crypto', $crypto_imap, $settings->mail_client_crypto ?? 'ssl', 'class="form-control" id="mail_client_crypto" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mail_client_user"><i class="fa fa-user-o"></i> <?= lang('smtp_user'); ?></label>
                                            <?php echo form_input('mail_client_user', $settings->mail_client_user ?? '', 'class="form-control" id="mail_client_user" placeholder="compras@empresa.com"'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mail_client_auth"><i class="fa fa-key"></i> <?= lang('tipo_autenticacion'); ?></label>
                                            <?php echo form_dropdown('mail_client_auth', $auth_opts, $mail_client_auth, 'class="form-control ns-auth-select" id="mail_client_auth" data-grupo="recepcion" style="width:100%;"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 ns-auth-recepcion-password" <?= $mail_client_auth === 'oauth_google' ? 'style="display:none;"' : '' ?>>
                                        <div class="mb-3">
                                            <label for="mail_client_pass"><i class="fa fa-lock"></i> <?= lang('smtp_pass'); ?></label>
                                            <div class="input-group">
                                                <input type="password" name="mail_client_pass" id="mail_client_pass" value="<?= htmlspecialchars($settings->mail_client_pass ?? '') ?>" class="form-control" autocomplete="new-password">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="mail_client_pass" title="<?= lang('ver_ocultar'); ?>"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                            <span class="help-block"><?= lang('password_app_ayuda'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-5 ns-auth-recepcion-oauth" <?= $mail_client_auth === 'oauth_google' ? '' : 'style="display:none;"' ?>>
                                        <div class="mb-3">
                                            <label><i class="fa fa-google"></i> <?= lang('cuenta_google'); ?></label>
                                            <p class="help-block" style="margin:4px 0;"><?= lang('gmail_gestion_arriba'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-default" id="btnProbarRecepcion"><i class="fa fa-plug"></i> <?= lang('probar_conexion'); ?></button>
                                <button type="button" class="btn btn-primary" id="btnImportarCompras"><i class="fa fa-download"></i> <?= lang('importar_ahora'); ?></button>
                                <button type="button" class="btn btn-default" id="btnImportarHistorico"><i class="fa fa-history"></i> <?= lang('importar_historico'); ?></button>
                                <span id="resultadoRecepcion" class="ns-prueba-resultado"></span>
                            </div>
                        </div>

                    </div><!-- /#tab-email -->

                    <!-- ==================== TAB 4: POS / CAJA ==================== -->
                    <div class="tab-pane" id="tab-pos">

                        <!-- COMPORTAMIENTO POST-VENTA -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-cog"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('settings_tab_pos'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="tope_descuento"><i class="fa fa-percent"></i> <?= lang('tope_descuento'); ?></label>
                                            <input type="number" step="0.01" min="0" max="100" name="tope_descuento" id="tope_descuento"
                                                   class="form-control" value="<?= html_escape($settings->tope_descuento ?? 100); ?>">
                                            <span class="help-block"><?= lang('tope_descuento_ayuda'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="after_sale_page"><i class="fa fa-arrow-right"></i> <?php echo lang('after_sale_page'); ?></label>
                                            <?php
                                            $asp = array(0 => lang('receipt'), 1 => lang('pos'));
                                            echo form_dropdown('after_sale_page', $asp, $settings->after_sale_page ?? 0, 'class="form-control tom-select" id="after_sale_page" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-print"></i> <?php echo lang('auto_print'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="auto_print" <?= (($settings->auto_print ?? 0) == 1) ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $yn2 = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('auto_print', $yn2, $settings->auto_print ?? 0, 'class="form-control ns-select-hidden" id="auto_print" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="display_product"><i class="fa fa-th"></i> <?php echo lang('display_product'); ?></label>
                                            <?php
                                            $dprv = array('1' => lang('name'), '2' => lang('foto'), '3' => lang('ambos'));
                                            echo form_dropdown('display_product', $dprv, $settings->bsty ?? '1', 'class="form-control tom-select" id="display_product" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="pro_limit"><i class="fa fa-sort-numeric-asc"></i> <?php echo lang('pro_limit'); ?></label>
                                            <?php echo form_input('pro_limit', $settings->pro_limit ?? '12', 'class="form-control ns-mono" id="pro_limit" required="required"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-keyboard-o"></i> <?php echo lang('display_kb'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="display_kb" <?= (($settings->display_kb ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $dtime = array('1' => lang('yes'), '0' => lang('no'));
                                            echo form_dropdown('display_kb', $dtime, $settings->display_kb ?? '0', 'class="form-control ns-select-hidden" id="display_kb" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ATAJOS DE TECLADO -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-keyboard-o"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sec_atajos_pos'); ?></strong><small><?= lang('atajos_ayuda'); ?></small></div>
                            </div>
                            <div class="card-body">
                                <?php
                                // Las combinaciones por omision evitan F11 y F12: el navegador se
                                // queda con esas teclas y el POS nunca las llega a recibir.
                                $atajos = array(
                                    'focus_add_item'         => array('atajo_agregar_item',     'F3',    'fa-crosshairs'),
                                    'finalize_sale'          => array('atajo_finalizar_venta',  'F4',    'fa-check-circle'),
                                    'add_customer'           => array('atajo_agregar_cliente',  'F6',    'fa-user-plus'),
                                    'edit_last_product'      => array('atajo_editar_ultimo',    'F7',    'fa-edit'),
                                    'toggle_category_slider' => array('atajo_alternar_cats',    'F8',    'fa-bars'),
                                    'cancel_sale'            => array('atajo_cancelar_venta',   'F9',    'fa-times-circle'),
                                    'suspend_sale'           => array('atajo_suspender_venta',  'F10',   'fa-pause-circle'),
                                    'open_hold_bills'        => array('atajo_retomar',          'ALT+S', 'fa-folder-open-o'),
                                    'today_sale'             => array('atajo_ventas_hoy',       'ALT+V', 'fa-calendar-check-o'),
                                    'close_register'         => array('atajo_cerrar_caja',      'ALT+R', 'fa-sign-out'),
                                );
                                ?>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label><i class="fa fa-bolt"></i> <?= lang('atajo_producto_rapido'); ?></label>
                                            <input class="form-control ns-mono" value="F2" disabled>
                                        </div>
                                    </div>
                                    <?php foreach ($atajos as $campo => $meta): ?>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="<?= $campo ?>"><i class="fa <?= $meta[2] ?>"></i> <?= lang($meta[0]); ?></label>
                                            <div class="input-group ns-atajo" data-defecto="<?= $meta[1] ?>">
                                                <input value="<?= htmlspecialchars($settings->$campo ?? $meta[1]) ?>" class="form-control ns-mono ns-atajo-campo" id="<?= $campo ?>" name="<?= $campo ?>" type="text" readonly>
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default ns-atajo-grabar" title="<?= lang('atajos_grabar'); ?>"><i class="fa fa-circle text-danger"></i></button>
                                                    <button type="button" class="btn btn-default ns-atajo-borrar" title="<?= lang('atajos_limpiar'); ?>"><i class="fa fa-eraser"></i></button>
                                                </span>
                                            </div>
                                            <span class="help-block ns-atajo-aviso"></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-default btn-sm" id="atajosRestaurar">
                                    <i class="fa fa-undo"></i> <?= lang('atajos_restaurar'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- INVENTARIO -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-cubes"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('inventory_label'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-exclamation-triangle"></i> <?php echo lang('overselling'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="overselling" <?= (($settings->overselling ?? 0) == 1) ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $enodis = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('overselling', $enodis, $settings->overselling ?? 0, 'class="form-control ns-select-hidden" id="overselling" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-percent"></i> <?= lang('ventas_fracciones'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="enable_fractions" <?= (($settings->enable_fractions ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $frac = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_fractions', $frac, $settings->enable_fractions ?? '0', 'class="form-control ns-select-hidden" id="enable_fractions" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-credit-card"></i> <?php echo lang('question_enable_credit'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="enable_credit" <?= (($settings->enable_credit ?? 0) == 1) ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $cr_opts = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('enable_credit', $cr_opts, $settings->enable_credit ?? 0, 'class="form-control ns-select-hidden" id="enable_credit" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-pencil-square-o"></i> <?= lang('edicion_rapida_prod'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="enable_fastedition" <?= (($settings->enable_fastedition ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $fe_opts = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_fastedition', $fe_opts, $settings->enable_fastedition ?? '0', 'class="form-control ns-select-hidden" id="enable_fastedition" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IMPRESION -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-print"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sec_impresion'); ?></strong><small><?= lang('printer_settings_moved_help'); ?></small></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h4><i class="fa fa-desktop"></i> <?= lang('puestos_trabajo'); ?></h4>
                                        <p class="help-block"><?= lang('puestos_ayuda'); ?></p>
                                        <?php if (empty($puestos)): ?>
                                            <p class="text-muted"><i class="fa fa-info-circle"></i> <?= lang('puestos_vacio'); ?></p>
                                        <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-condensed ns-puestos">
                                                <thead>
                                                    <tr>
                                                        <th><?= lang('puesto'); ?></th>
                                                        <th><?= lang('ip_address'); ?></th>
                                                        <th><?= lang('impresora'); ?></th>
                                                        <th><?= lang('ancho_papel'); ?></th>
                                                        <th><?= lang('ultimo_uso'); ?></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($puestos as $pto): ?>
                                                    <tr data-puesto="<?= (int) $pto->id ?>">
                                                        <td>
                                                            <input type="text" class="form-control input-sm ns-puesto-nombre"
                                                                   value="<?= htmlspecialchars($pto->nombre ?? '') ?>" maxlength="100">
                                                        </td>
                                                        <td class="ns-mono"><?= htmlspecialchars($pto->ip ?? '—') ?></td>
                                                        <td>
                                                            <?php
                                                            // Solo el navegador de esa maquina puede enumerar sus impresoras (QZ Tray
                                                            // corre ahi), asi que aca se ofrecen las que el POS reporto desde ella.
                                                            $pto_impresoras = json_decode((string) ($pto->impresoras ?? ''), true) ?: array();
                                                            if (!empty($pto->qz_printer) && !in_array($pto->qz_printer, $pto_impresoras, TRUE)) {
                                                                array_unshift($pto_impresoras, $pto->qz_printer);
                                                            }
                                                            ?>
                                                            <?php if ($pto_impresoras): ?>
                                                                <select class="form-control input-sm ns-puesto-impresora">
                                                                    <option value=""><?= lang('puesto_sin_impresora'); ?></option>
                                                                    <?php foreach ($pto_impresoras as $imp): ?>
                                                                    <option value="<?= htmlspecialchars($imp) ?>" <?= ($imp === $pto->qz_printer) ? 'selected' : '' ?>><?= htmlspecialchars($imp) ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            <?php else: ?>
                                                                <span class="text-muted"><i class="fa fa-info-circle"></i> <?= lang('puesto_sin_reportar'); ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $pto_cpl = !empty($pto->caracteres) ? (int) $pto->caracteres : get_printer_chars_per_line();
                                                            $anchos = array(32 => lang('papel_58'), 42 => lang('papel_80'), 48 => lang('papel_80_completo'));
                                                            if (!isset($anchos[$pto_cpl])) { $anchos[$pto_cpl] = $pto_cpl . ' ' . lang('caracteres'); }
                                                            ?>
                                                            <select class="form-control input-sm ns-puesto-papel">
                                                                <?php foreach ($anchos as $cpl => $rotulo): ?>
                                                                <option value="<?= $cpl ?>" <?= $cpl === $pto_cpl ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-muted"><?= $pto->ultimo_uso ? $this->tec->hrld($pto->ultimo_uso) : '—' ?></td>
                                                        <td class="text-end" style="white-space:nowrap">
                                                            <button type="button" class="btn btn-default btn-sm ns-puesto-vista" title="<?= lang('vista_previa_tiquete'); ?>">
                                                                <i class="fa fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-default btn-sm ns-puesto-borrar" title="<?= lang('delete'); ?>">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Vista previa del tiquete del puesto elegido: sale del mismo codigo que imprime -->
                                        <div class="tk-config mb-3" id="tkPanel">
                                            <div class="tk-campos">
                                                <h5 id="tkTitulo" style="margin-top:0"></h5>
                                                <p class="help-block"><?= lang('tiquete_vista_ayuda'); ?></p>
                                                <button type="button" class="btn btn-default btn-sm" id="tkPrueba">
                                                    <i class="fa fa-print"></i> <?= lang('imprimir_prueba'); ?>
                                                </button>
                                                <div class="ns-prueba-resultado" id="tkPruebaMsg"></div>
                                                <p class="text-muted small mt-2"><?= lang('prueba_esta_computadora'); ?></p>
                                            </div>
                                            <figure class="tk-vista">
                                                <figcaption><?= lang('vista_previa_tiquete'); ?> <span id="tkVenta"></span></figcaption>
                                                <div class="tk-papel" id="tkPapel"></div>
                                            </figure>
                                        </div>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-default btn-sm" id="detectarImpresoras">
                                            <i class="fa fa-search"></i> <?= lang('detectar_impresoras'); ?>
                                        </button>
                                        <span id="detectarResultado" class="ns-prueba-resultado"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- APARTADOS Y COTIZACIONES -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-bookmark"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sec_apartados'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-bookmark-o"></i> <?= lang('apartados'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="enable_layaway" <?= (($settings->enable_layaway ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $layw = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_layaway', $layw, $settings->enable_layaway ?? '0', 'class="form-control ns-select-hidden" id="enable_layaway" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="footer_apartado"><i class="fa fa-align-left"></i> <?= lang('footer_apartado_label'); ?></label>
                                            <?php echo form_input('footer_apartado', $settings->footer_apartado ?? '', 'class="form-control" id="footer_apartado"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-file-o"></i> <?= lang('cotizaciones'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="enable_quote" <?= (($settings->enable_quote ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $qt_opts = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_quote', $qt_opts, $settings->enable_quote ?? '0', 'class="form-control ns-select-hidden" id="enable_quote" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-truck"></i> <?= lang('metodo_envio'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="is_shipping" <?= (($settings->is_shipping ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $ship_opts = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('is_shipping', $ship_opts, $settings->is_shipping ?? '0', 'class="form-control ns-select-hidden" id="is_shipping" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NUMERALES / DECIMALES -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-hashtag"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sec_numerales_moneda'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="decimals"><i class="fa fa-calculator"></i> <?php echo lang('decimals'); ?></label>
                                            <?php
                                            $dec_opts = array(0 => lang('disable'), 1 => '1', 2 => '2', 3 => '3', 4 => '4');
                                            echo form_dropdown('decimals', $dec_opts, $settings->decimals ?? 0, 'class="form-control tom-select" id="decimals" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="qty_decimals"><i class="fa fa-sort-numeric-asc"></i> <?php echo lang('qty_decimals'); ?></label>
                                            <?php
                                            $qdec = array(0 => lang('disable'), 1 => '1', 2 => '2');
                                            echo form_dropdown('qty_decimals', $qdec, $settings->qty_decimals ?? 0, 'class="form-control tom-select" id="qty_decimals" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="decimals_sep"><i class="fa fa-minus"></i> <?php echo lang('decimals_sep'); ?></label>
                                            <?php
                                            $dec_point = array('.' => lang('dot'), ',' => lang('comma'));
                                            echo form_dropdown('decimals_sep', $dec_point, $settings->decimals_sep ?? '.', 'class="form-control tom-select" id="decimals_sep" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="thousands_sep"><i class="fa fa-ellipsis-h"></i> <?php echo lang('thousands_sep'); ?></label>
                                            <?php
                                            $th_sep = array('.' => lang('dot'), ',' => lang('comma'), '0' => lang('space'));
                                            echo form_dropdown('thousands_sep', $th_sep, $settings->thousands_sep ?? ',', 'class="form-control tom-select" id="thousands_sep" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-exchange"></i> <?php echo lang('sac'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="sac" <?= (set_value('sac', $settings->sac ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $sac_opts = array('0' => lang('disable'), '1' => lang('enable'));
                                            echo form_dropdown('sac', $sac_opts, set_value('sac', $settings->sac ?? '0'), 'class="form-control ns-select-hidden" id="sac" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="display_symbol"><i class="fa fa-dollar"></i> <?php echo lang('display_currency_symbol'); ?></label>
                                            <?php
                                            $ds_opts = array(0 => lang('disable'), 1 => lang('before'), 2 => lang('after'));
                                            echo form_dropdown('display_symbol', $ds_opts, $settings->display_symbol ?? 0, 'class="form-control tom-select" id="display_symbol" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="symbol"><i class="fa fa-tag"></i> <?php echo lang('currency_symbol'); ?></label>
                                            <?php echo form_input('symbol', $settings->symbol ?? '₡', 'class="form-control ns-mono" id="symbol"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="rounding"><i class="fa fa-circle-o-notch"></i> <?php echo lang('rounding'); ?></label>
                                            <?php
                                            $rnd = array('0' => lang('disable'), '1' => lang('to_nearest_005'), '2' => lang('to_nearest_050'), '3' => lang('to_nearest_number'), '4' => lang('to_next_number'));
                                            echo form_dropdown('rounding', $rnd, $settings->rounding ?? '0', 'class="form-control tom-select" id="rounding" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /#tab-pos -->

                    <!-- ==================== TAB 5: AVANZADO ==================== -->
                    <div class="tab-pane" id="tab-avanzado">

                        <!-- ESTADO DEL DESPLIEGUE -->
                        <?php
                        $avisos_despliegue = array();
                        if (llave_cifrado_de_fabrica()) {
                            $avisos_despliegue[] = lang('aviso_llave_fabrica');
                        }
                        if (ENVIRONMENT !== 'production') {
                            $avisos_despliegue[] = sprintf(lang('aviso_entorno'), ENVIRONMENT);
                        }
                        if (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) === 'off') {
                            $avisos_despliegue[] = lang('aviso_sin_https');
                        }
                        ?>
                        <?php if ($avisos_despliegue) { ?>
                        <style>
                            .ns-despliegue{border:1px solid var(--ns-warning,#d97706);border-radius:8px;padding:14px 16px;margin-bottom:14px;
                                background:var(--ns-warning-soft,rgba(217,119,6,.08));font-size:13px;line-height:1.6;}
                            .ns-despliegue ul{margin:8px 0 0;padding-left:18px;}
                            .ns-despliegue code{padding:2px 6px;border-radius:4px;background:var(--ns-surface-3,#eee);font-size:12px;}
                        </style>
                        <div class="ns-despliegue">
                            <div><i class="fa fa-shield"></i> <strong><?= lang('aviso_despliegue_titulo'); ?></strong></div>
                            <ul>
                                <?php foreach ($avisos_despliegue as $a) { ?>
                                    <li><?= $a; ?></li>
                                <?php } ?>
                            </ul>
                            <div style="margin-top:8px;"><?= lang('aviso_despliegue_ayuda'); ?></div>
                        </div>
                        <?php } ?>

                        <!-- BUSQUEDA -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-search"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sec_busqueda_prod'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="sensibility_search"><i class="fa fa-sliders"></i> <?php echo lang('search_sensibility'); ?></label>
                                            <?php
                                            $sensibility = array(0 => lang('0_search'), 1 => lang('1_search'), 2 => lang('2_search'), 3 => lang('3_search'));
                                            echo form_dropdown('sensibility_search', $sensibility, $settings->sensibility_search ?? 0, 'class="form-control tom-select" id="sensibility_search" style="width:100%;" required="required"');
                                            ?>
                                            <span class="help-block"><?= lang('search_sensibility_ayuda'); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="quantity_suggest"><i class="fa fa-list-ol"></i> <?= lang('sugerencias_mostrar'); ?></label>
                                            <?php
                                            $qsug = array('10' => '10', '25' => '25', '50' => '50', '100' => '100');
                                            echo form_dropdown('quantity_suggest', $qsug, $settings->quantity_suggest ?? '10', 'class="form-control tom-select" id="quantity_suggest" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="item_addition"><i class="fa fa-plus-circle"></i> <?php echo lang('item_addition'); ?></label>
                                            <?php
                                            $ia = array(0 => lang('add_new_item'), 1 => lang('increase_quantity_if_item_exist'));
                                            echo form_dropdown('item_addition', $ia, $settings->item_addition ?? 0, 'id="item_addition" class="form-control tom-select" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CATEGORIAS Y CLIENTES -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-tags"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sec_cats_clientes'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="default_category"><i class="fa fa-tag"></i> <?php echo lang('default_category'); ?></label>
                                            <?php
                                            $ct = array(0 => lang('select') . ' ' . lang('default_category'));
                                            foreach ($categories as $catrgory) {
                                                $ct[$catrgory->id] = $catrgory->name;
                                            }
                                            echo form_dropdown('default_category', $ct, $settings->default_category ?? 0, 'class="form-control tom-select" style="width:100%;" id="default_category"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="default_customer"><i class="fa fa-user-o"></i> <?php echo lang('default_customer'); ?></label>
                                            <?php
                                            $cu = array();
                                            foreach ($customers as $customer) {
                                                $cu[$customer->id] = $customer->name;
                                            }
                                            echo form_dropdown('default_customer', $cu, $settings->default_customer ?? '', 'class="form-control tom-select" style="width:100%;" id="default_customer" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- REGISTRO Y CAJA -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-cash-register"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sec_registro_caja'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-list-alt"></i> <?= lang('detalles_cierre_caja'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="enable_detail_register" <?= (($settings->enable_detail_register ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $dreg = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_detail_register', $dreg, $settings->enable_detail_register ?? '0', 'class="form-control ns-select-hidden" id="enable_detail_register" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-user-circle-o"></i> <?= lang('detalles_cajero'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="enable_detail_caschier" <?= (($settings->enable_detail_caschier ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $dcash = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_detail_caschier', $dcash, $settings->enable_detail_caschier ?? '0', 'class="form-control ns-select-hidden" id="enable_detail_caschier" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-key"></i> <?= lang('cierre_unico'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="enable_auth_open" <?= (($settings->enable_auth_open ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $authop = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_auth_open', $authop, $settings->enable_auth_open ?? '0', 'class="form-control ns-select-hidden" id="enable_auth_open" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IMPUESTO Y OTROS -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-percent"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sec_impuesto_propina'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_show_tax"><i class="fa fa-percent"></i> <?= lang('mostrar_imp_como'); ?></label>
                                            <?php
                                            $stax = array('' => lang('no_mostrar'), 'IVI' => 'IVI', 'IVA' => 'IVA', 'Impuesto' => 'Impuesto');
                                            echo form_dropdown('enable_show_tax', $stax, $settings->enable_show_tax ?? '', 'class="form-control tom-select" id="enable_show_tax" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>&nbsp;</label>
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-thumbs-up"></i> <?= lang('propina'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="propina_enable" <?= (($settings->propina_enable ?? '0') == '1') ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $prop = array('0' => lang('deshabilitada'), '1' => lang('habilitada'));
                                            echo form_dropdown('propina_enable', $prop, $settings->propina_enable ?? '0', 'class="form-control ns-select-hidden" id="propina_enable"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="propina_rate"><i class="fa fa-percent"></i> <?= lang('tasa_propina'); ?></label>
                                            <input type="number" name="propina_rate" id="propina_rate" value="<?= htmlspecialchars($settings->propina_rate ?? '10') ?>" class="form-control" min="0" max="100" step="0.5">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /#tab-avanzado -->

                    <!-- ==================== TAB: SINPE MÓVIL ==================== -->
                    <div class="tab-pane" id="tab-sinpe">
                        <style>
                            .ns-sinpe-status-box{border:1px solid var(--bs-border-color,#e2e2e2);border-radius:8px;padding:16px;}
                            .ns-sinpe-dot{width:10px;height:10px;border-radius:50%;background:#adb5bd;display:inline-block;}
                            .ns-sinpe-dot.on{background:#2ecc71;box-shadow:0 0 0 3px rgba(46,204,113,.25);}
                            .ns-sinpe-dot.off{background:#e74c3c;}
                            .ns-sinpe-perfil{display:flex;align-items:center;gap:10px;}
                            .ns-sinpe-avatar{width:38px;height:38px;border-radius:50%;flex:0 0 38px;object-fit:cover;border:1px solid var(--bs-border-color,#e2e2e2);}
                            .ns-sinpe-avatar-ph{display:flex;align-items:center;justify-content:center;background:var(--ns-surface-3,#eee);color:var(--ns-text-3,#888);}
                            .ns-sinpe-perfil-nombre{font-weight:600;line-height:1.25;}
                            .ns-sinpe-perfil-email{font-size:12.5px;color:var(--ns-text-3,#888);}
                            .ns-sinpe-stats-box{display:flex;gap:12px;height:100%;}
                            .ns-sinpe-stat{flex:1;border:1px solid var(--bs-border-color,#e2e2e2);border-radius:8px;padding:14px;text-align:center;}
                            .ns-sinpe-stat-value{font-size:22px;font-weight:700;}
                            .ns-sinpe-stat-label{font-size:11.5px;color:var(--ns-text-3,#888);}
                            .ns-sinpe-caido{border:1px solid var(--ns-danger,#dc2626);border-radius:8px;padding:14px 16px;margin-bottom:14px;background:var(--ns-danger-soft,rgba(220,38,38,.07));font-size:13px;line-height:1.55;}
                            .ns-sinpe-caido code{display:inline-block;margin-top:2px;padding:3px 8px;border-radius:5px;background:var(--ns-surface-3,#eee);font-size:12px;}
                        </style>
                        <div class="card" data-card>
                            <div class="card-header">
                                <i class="fa fa-mobile"></i> <?= lang('settings_tab_sinpe'); ?>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Conectá la cuenta de Gmail que recibe las notificaciones de SINPE Móvil.
                                    Los pagos aparecen en tiempo real en el cobro del POS al elegir "SINPE"
                                    como método de pago.
                                </p>

                                <div id="sinpeServicioCaido" class="ns-sinpe-caido" style="display:none;">
                                    <div><i class="fa fa-plug"></i> <strong><?= lang('sinpe_servicio_caido_titulo'); ?></strong></div>
                                    <p style="margin:6px 0 8px;"><?= lang('sinpe_servicio_caido'); ?></p>
                                    <code>cd www/sinpe-service &amp;&amp; npm start</code>
                                    <p style="margin:8px 0 0;"><?= lang('sinpe_servicio_permanente'); ?></p>
                                    <code>pm2 start ecosystem.config.cjs &amp;&amp; pm2 save</code>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="ns-sinpe-status-box">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span id="sinpeStatusDot" class="ns-sinpe-dot"></span>
                                                <strong id="sinpeStatusText">Consultando estado…</strong>
                                            </div>
                                            <div id="sinpeGmailEmailRow" class="ns-sinpe-perfil" style="display:none;">
                                                <!-- Google devuelve 403 en las fotos de perfil si se manda el Referer. -->
                                                <img id="sinpeGmailFoto" class="ns-sinpe-avatar" alt="" referrerpolicy="no-referrer" style="display:none;">
                                                <span id="sinpeGmailFotoPh" class="ns-sinpe-avatar ns-sinpe-avatar-ph"><i class="fa fa-user"></i></span>
                                                <div>
                                                    <div id="sinpeGmailNombre" class="ns-sinpe-perfil-nombre" style="display:none;"></div>
                                                    <div class="ns-sinpe-perfil-email">
                                                        <i class="fa fa-envelope-o"></i> <span id="sinpeGmailEmail"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">Última revisión: <span id="sinpeUltimaRevision">—</span></small>
                                            </div>
                                            <div class="mt-3">
                                                <a href="<?= site_url('sinpe/conectar') ?>" id="sinpeConnectBtn" class="btn btn-primary" style="display:none;">
                                                    <i class="fa fa-google"></i> Conectar con Gmail
                                                </a>
                                                <button type="button" id="sinpeDisconnectBtn" class="btn btn-default" style="display:none;">
                                                    <i class="fa fa-unlink"></i> Desconectar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ns-sinpe-stats-box">
                                            <div class="ns-sinpe-stat">
                                                <div class="ns-sinpe-stat-value" id="sinpeStatPendientes">—</div>
                                                <div class="ns-sinpe-stat-label">Pendientes de usar</div>
                                            </div>
                                            <div class="ns-sinpe-stat">
                                                <div class="ns-sinpe-stat-value" id="sinpeStatHoy">—</div>
                                                <div class="ns-sinpe-stat-label">Recibidos hoy</div>
                                            </div>
                                            <div class="ns-sinpe-stat">
                                                <div class="ns-sinpe-stat-value" id="sinpeStatMonto">—</div>
                                                <div class="ns-sinpe-stat-label">Monto total registrado</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="row" id="sinpeConfigRow" style="opacity:.5;pointer-events:none;">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="sinpeBancoSelect"><i class="fa fa-university"></i> Formato del banco</label>
                                            <select id="sinpeBancoSelect" class="form-control">
                                                <option value="auto">Detección automática</option>
                                                <option value="davivienda">Davivienda</option>
                                                <option value="bac">BAC Credomatic</option>
                                                <option value="bcr">Banco de Costa Rica</option>
                                                <option value="bn">Banco Nacional</option>
                                                <option value="popular">Banco Popular</option>
                                                <option value="promerica">Promerica</option>
                                                <option value="scotiabank">Scotiabank</option>
                                                <option value="lafise">Lafise</option>
                                                <option value="coopealianza">Coopealianza</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="sinpeIntervaloInput"><i class="fa fa-clock-o"></i> Intervalo de vigilancia (segundos)</label>
                                            <input type="number" id="sinpeIntervaloInput" class="form-control" min="5" max="300" value="15">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label>Vigilancia en tiempo real</label>
                                            <div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" id="sinpeActivoSwitch">
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                                <span id="sinpeActivoLabel" class="ms-2">Pausada</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- La vigilancia normal solo cubre 2 dias; esto trae el historial. -->
                                <div class="row" id="sinpeImportRow" style="opacity:.5;pointer-events:none;margin-bottom:1rem;">
                                    <div class="col-sm-12">
                                        <hr>
                                        <label><i class="fa fa-history"></i> Importar SINPE anteriores</label>
                                        <p class="text-muted" style="font-size:12.5px;margin-bottom:.5rem;">
                                            Revisa los correos ya recibidos en esta cuenta y guarda los pagos SINPE que
                                            todavia no esten registrados. Los que ya existen no se duplican.
                                        </p>
                                        <div class="d-flex align-items-center" style="gap:.5rem;flex-wrap:wrap;">
                                            <select id="sinpeImportDias" class="form-control" style="max-width:220px;">
                                                <option value="30">Ultimos 30 dias</option>
                                                <option value="90" selected>Ultimos 3 meses</option>
                                                <option value="180">Ultimos 6 meses</option>
                                                <option value="365">Ultimo ano</option>
                                                <option value="0">Todo el historial</option>
                                            </select>
                                            <button type="button" id="sinpeImportBtn" class="btn btn-outline-secondary">
                                                <i class="fa fa-download"></i> Importar
                                            </button>
                                            <span id="sinpeImportEstado" class="text-muted" style="font-size:12.5px;"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /#tab-sinpe -->

                </div><!-- /.tab-content -->
            </div><!-- /.col-md-10 -->
            </div><!-- /.row settings nav -->

            <!-- STICKY SAVE BUTTON -->
            <div class="ns-save-bar">
                <button type="submit" name="update" class="btn btn-primary btn-lg">
                    <i class="fa fa-save"></i> <?= lang('guardar_configuracion'); ?>
                </button>
                <a href="<?= site_url('settings') ?>" class="btn btn-default btn-lg">
                    <i class="fa fa-undo"></i> <?= lang('cancel'); ?>
                </a>
            </div>

            <?php echo form_close(); ?>

            <?php foreach (array('test', 'prod') as $amb): ?>
            <form id="ns-cert-form-<?= $amb ?>" action="<?= site_url('settings/upload_certificado') ?>" method="post" enctype="multipart/form-data" hidden>
                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                <input type="hidden" name="ambiente_cert" value="<?= $amb ?>">
            </form>
            <?php endforeach; ?>

        </div>
        </div>
    </div>
</section>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    "use strict";

    // Toggle ver/ocultar password
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-toggle-pw');
        if (!btn) { return; }
        var field = document.getElementById(btn.dataset.target);
        if (!field) { return; }
        if (field.getAttribute('type') === 'password') {
            field.setAttribute('type', 'text');
            btn.innerHTML = '<i class="fa fa-eye-slash"></i>';
        } else {
            field.setAttribute('type', 'password');
            btn.innerHTML = '<i class="fa fa-eye"></i>';
        }
    });

    // ── Buscador de campos: recorre TODAS las pestañas, no solo la activa.
    // Si la pestaña visible no tiene coincidencias pero otra sí, salta a esa
    // pestaña sola (sin vaciar lo que la persona escribió en el buscador).
    var NS_TAB_ORDER = ['tab-general', 'tab-emisor', 'tab-email', 'tab-pos', 'tab-avanzado', 'tab-sinpe'];
    window.nsFilterCards = function (q) {
        q = (q || '').trim().toLowerCase();
        var panes = Array.prototype.slice.call(document.querySelectorAll('.tab-pane'));

        if (!q) {
            panes.forEach(function (pane) {
                pane.querySelectorAll('[data-card]').forEach(function (card) { card.style.display = ''; });
            });
            document.getElementById('ns-no-results').classList.remove('show');
            return;
        }

        // Primero se marca, por pestaña, cuáles tarjetas coinciden.
        var matchesByPane = {};
        panes.forEach(function (pane) {
            var anyMatch = false;
            pane.querySelectorAll('[data-card]').forEach(function (card) {
                var match = card.textContent.toLowerCase().indexOf(q) !== -1;
                card.dataset.nsMatch = match ? '1' : '0';
                if (match) { anyMatch = true; }
            });
            matchesByPane[pane.id] = anyMatch;
        });

        // Si la pestaña activa se quedó sin resultados, saltar a la primera
        // (en el orden del menú) que sí tenga algo.
        var activePane = document.querySelector('.tab-pane.active');
        if (activePane && !matchesByPane[activePane.id]) {
            var target = NS_TAB_ORDER.filter(function (id) { return matchesByPane[id]; })[0];
            if (target && target !== activePane.id) {
                activateSettingsTab('#' + target, true);
                activePane = document.querySelector('.tab-pane.active');
            }
        }

        var anyVisible = false;
        panes.forEach(function (pane) {
            var isActive = pane.classList.contains('active');
            pane.querySelectorAll('[data-card]').forEach(function (card) {
                var match = card.dataset.nsMatch === '1';
                if (isActive) {
                    card.style.display = match ? '' : 'none';
                    if (match) { anyVisible = true; }
                }
            });
        });
        document.getElementById('ns-no-results').classList.toggle('show', !anyVisible);
    };
    var searchInputEl = document.getElementById('ns-search-input');
    searchInputEl.addEventListener('input', function () {
        nsFilterCards(searchInputEl.value);
    });

    // ── Nav movil (chips) generado a partir del nav desktop ──
    var navMobile = document.getElementById('nsNavMobile');
    function nsNavLabelText(a) {
        // El texto va suelto dentro de .nx-nav-label, seguido de un <span class="nx-nav-sub">
        // con el subtitulo: hay que clonar y quitar el subtitulo para quedarse solo con el titulo.
        var label = a.querySelector('.nx-nav-label');
        if (!label) { return ''; }
        var clone = label.cloneNode(true);
        var sub = clone.querySelector('.nx-nav-sub');
        if (sub) { sub.remove(); }
        return clone.textContent.trim();
    }
    document.querySelectorAll('#nsNavDesktop .nav-pills > li > a').forEach(function (a) {
        var chip = document.createElement('div');
        chip.className = 'ns-nav-chip';
        var icon = a.querySelector('.fa');
        chip.innerHTML = '<i class="' + (icon ? icon.className : '') + '"></i><span>' + nsNavLabelText(a) + '</span>';
        chip.dataset.href = a.getAttribute('href');
        if (a.closest('li').classList.contains('active')) { chip.classList.add('active'); }
        navMobile.appendChild(chip);
    });

    // ── Cambio de pestaña: manejo propio (no delegar en bootstrap.Tab, que no
    // desactiva bien el <li> anterior cuando los triggers están envueltos en <li>) ──
    // keepSearch=true se usa cuando el buscador nos manda a otra pestaña: no hay
    // que borrar lo que la persona escribió ni reiniciar el filtro a medias.
    function activateSettingsTab(href, keepSearch) {
        document.querySelectorAll('.tab-pane').forEach(function (pane) {
            pane.classList.toggle('active', ('#' + pane.id) === href);
        });
        document.querySelectorAll('#nsNavDesktop .nav-pills > li').forEach(function (li) {
            var a = li.querySelector('a');
            li.classList.toggle('active', a && a.getAttribute('href') === href);
        });
        navMobile.querySelectorAll('.ns-nav-chip').forEach(function (c) {
            c.classList.toggle('active', c.dataset.href === href);
        });
        localStorage.setItem('settings_active_tab', href);
        if (!keepSearch) {
            searchInputEl.value = '';
            nsFilterCards('');
        }
    }
    document.querySelectorAll('#nsNavDesktop .nav-pills > li > a').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            activateSettingsTab(a.getAttribute('href'));
        });
    });
    navMobile.addEventListener('click', function (e) {
        var chip = e.target.closest('.ns-nav-chip');
        if (!chip) { return; }
        activateSettingsTab(chip.dataset.href);
    });
    var initialTab = localStorage.getItem('settings_active_tab') || '#tab-general';
    if (document.querySelector(initialTab)) {
        activateSettingsTab(initialTab);
    }

    // Probar credenciales Hacienda
    var urlComprueba = "<?= base_url() ?>settings/compruebausers";
    var csrfName = "<?= $this->security->get_csrf_token_name(); ?>";
    var csrfHash = "<?= $this->security->get_csrf_hash(); ?>";

    function wireComprueba(btnId, userId, passId, ambiente, labelLang) {
        var btn = document.getElementById(btnId);
        if (!btn) { return; }
        btn.addEventListener('click', function () {
            var originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i> Probando...';
            var body = new URLSearchParams();
            body.set('user', document.getElementById(userId).value);
            body.set('password', document.getElementById(passId).value);
            body.set('ambiente', ambiente);
            // window.CSRF_HASH la refresca main.js con la cabecera X-CSRF-Token de
            // cada respuesta AJAX; csrfHash es solo el valor con el que arrancó la
            // página. csrf_regenerate está activo: reusar el de arranque en la
            // segunda petición de la pantalla responde 403.
            body.set(window.CSRF_NAME || csrfName, window.CSRF_HASH || csrfHash);
            fetch(urlComprueba, { method: 'POST', body: body })
                .then(function (res) { return res.text(); })
                .then(function (data) { alert(data); })
                .catch(function () { alert('Error de conexion al servidor de Hacienda.'); })
                .finally(function () { btn.innerHTML = originalHtml; });
        });
    }
    wireComprueba('comprueba_test', 'user_token_test', 'password_token_test', 'test');
    wireComprueba('comprueba_prod', 'user_token_prod', 'password_token_prod', 'prod');

    // ── Interruptores (switches) ligados a un <select> oculto ──
    document.querySelectorAll('.ns-switch-bind').forEach(function (chk) {
        var select = document.getElementById(chk.dataset.bind);
        if (!select) { return; }
        chk.checked = (select.value == '1');
        chk.addEventListener('change', function () {
            select.value = chk.checked ? '1' : '0';
            select.dispatchEvent(new Event('change'));
        });
    });

    // ── Propina: habilitar tasa segun switch ──
    var propinaChk = document.querySelector('.ns-switch-bind[data-bind="propina_enable"]');
    var propinaRate = document.getElementById('propina_rate');
    if (propinaChk && propinaRate) {
        function syncPropinaRate() { propinaRate.disabled = !propinaChk.checked; }
        syncPropinaRate();
        propinaChk.addEventListener('change', syncPropinaRate);
    }

    // ── Dropzones de archivo (logo, certificado) ──
    function wireDropzone(zoneId, labelId) {
        var zone = document.getElementById(zoneId);
        if (!zone) { return; }
        var input = zone.querySelector('input[type=file]');
        var label = document.getElementById(labelId);
        var defaultLabel = label.textContent;
        input.addEventListener('change', function () {
            if (input.files && input.files[0]) {
                zone.classList.add('has-file');
                label.textContent = input.files[0].name;
            } else {
                zone.classList.remove('has-file');
                label.textContent = defaultLabel;
            }
        });
    }
    wireDropzone('ns-logo-dropzone', 'ns-logo-label');
    wireDropzone('ns-cert-dropzone-test', 'ns-cert-label-test');
    wireDropzone('ns-cert-dropzone-prod', 'ns-cert-label-prod');

    // ── Correo ──
    (function () {
        var protocolo = document.getElementById('protocol');
        if (!protocolo) { return; }

        function postCorreo(url, extra) {
            var body = new URLSearchParams(extra || {});
            // window.CSRF_HASH la refresca main.js con la cabecera X-CSRF-Token de
            // cada respuesta AJAX; csrfHash es solo el valor con el que arrancó la
            // página. csrf_regenerate está activo: reusar el de arranque en la
            // segunda petición de la pantalla responde 403.
            body.set(window.CSRF_NAME || csrfName, window.CSRF_HASH || csrfHash);
            return fetch(url, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) { throw new Error('HTTP ' + r.status); }
                    return r.json();
                });
        }

        function pintar(el, ok, texto) {
            el.className = 'ns-prueba-resultado ' + (ok ? 'ok' : 'err');
            el.innerHTML = (ok ? '<i class="fa fa-check-circle"></i> ' : '<i class="fa fa-times-circle"></i> ') + texto;
        }

        // Solo se muestran los campos del protocolo elegido.
        function alternarProtocolo() {
            var v = protocolo.value;
            document.querySelectorAll('.ns-solo-smtp').forEach(function (el) {
                el.style.display = (v === 'smtp') ? '' : 'none';
            });
            document.querySelectorAll('.ns-solo-sendmail').forEach(function (el) {
                el.style.display = (v === 'sendmail') ? '' : 'none';
            });
        }
        protocolo.addEventListener('change', alternarProtocolo);
        alternarProtocolo();

        // Contraseña de aplicación y OAuth son excluyentes.
        document.querySelectorAll('.ns-auth-select').forEach(function (sel) {
            function alternarAuth() {
                var g = sel.dataset.grupo;
                var oauth = sel.value === 'oauth_google';
                document.querySelectorAll('.ns-auth-' + g + '-password').forEach(function (el) { el.style.display = oauth ? 'none' : ''; });
                document.querySelectorAll('.ns-auth-' + g + '-oauth').forEach(function (el) { el.style.display = oauth ? '' : 'none'; });
            }
            sel.addEventListener('change', alternarAuth);
            alternarAuth();
        });

        // Presets: el usuario elige el proveedor y no tiene que buscar host ni puerto.
        var PROV = window._proveedoresCorreo || {};
        document.querySelectorAll('#proveedor_smtp, #proveedor_imap').forEach(function (sel) {
            var esImap = sel.dataset.destino === 'imap';
            var campos = esImap
                ? { host: 'mail_client_host', port: 'mail_client_port', crypto: 'mail_client_crypto' }
                : { host: 'smtp_host', port: 'smtp_port', crypto: 'smtp_crypto' };

            // Preseleccionar el proveedor cuyo host coincide con lo ya guardado.
            var hostActual = (document.getElementById(campos.host) || {}).value || '';
            Object.keys(PROV).forEach(function (k) {
                if (PROV[k][esImap ? 'imap' : 'smtp'] && PROV[k][esImap ? 'imap' : 'smtp'] === hostActual) { sel.value = k; }
            });
            if (!sel.value) { sel.value = 'otro'; }

            sel.addEventListener('change', function () {
                var p = PROV[sel.value];
                if (!p) { return; }
                var host = esImap ? p.imap : p.smtp;
                if (host === '') { return; }  // "Otro": se deja lo que haya escrito.
                document.getElementById(campos.host).value = host;
                document.getElementById(campos.port).value = esImap ? p.imap_port : p.smtp_port;
                var cr = document.getElementById(campos.crypto);
                if (cr) { cr.value = esImap ? p.imap_crypto : p.smtp_crypto; }
            });
        });

        var btnEnvio = document.getElementById('btnProbarEnvio');
        if (btnEnvio) {
            btnEnvio.addEventListener('click', function () {
                var salida = document.getElementById('resultadoEnvio');
                var original = btnEnvio.innerHTML;
                btnEnvio.disabled = true;
                btnEnvio.innerHTML = '<i class="fa fa-spin fa-spinner"></i> ' + <?= json_encode(lang('probando')) ?>;
                salida.textContent = '';
                postCorreo('<?= site_url('mailauth/probar_envio') ?>')
                    .then(function (r) { pintar(salida, r.ok, r.detalle || r.aviso || r.error || ''); })
                    .catch(function (e) { pintar(salida, false, e.message); })
                    .finally(function () { btnEnvio.disabled = false; btnEnvio.innerHTML = original; });
            });
        }

        function correrRecepcion(url, boton, extra) {
            var salida = document.getElementById('resultadoRecepcion');
            var original = boton.innerHTML;
            boton.disabled = true;
            boton.innerHTML = '<i class="fa fa-spin fa-spinner"></i> ' + <?= json_encode(lang('probando')) ?>;
            salida.textContent = '';
            return postCorreo(url, extra)
                .then(function (r) {
                    if (!r.ok) { pintar(salida, false, r.error || ''); return; }
                    if (r.revisados === undefined) {
                        pintar(salida, true, <?= json_encode(lang('imap_conectado')) ?>
                            .replace('%s', r.carpeta).replace('%d', r.total).replace('%n', r.sin_leer));
                        return;
                    }
                    var partes = [
                        <?= json_encode(lang('import_revisados')) ?> + ': ' + r.revisados,
                        <?= json_encode(lang('import_registrados')) ?> + ': ' + r.registrados,
                        <?= json_encode(lang('import_repetidos')) ?> + ': ' + r.repetidos,
                        <?= json_encode(lang('import_sin_xml')) ?> + ': ' + r.sin_xml
                    ];
                    pintar(salida, true, partes.join(' · '));
                    if (r.errores && r.errores.length) {
                        salida.innerHTML += '<div class="ns-prueba-detalle err">' +
                            r.errores.map(function (e) { return '• ' + e; }).join('<br>') + '</div>';
                    }
                })
                .catch(function (e) { pintar(salida, false, e.message); })
                .finally(function () { boton.disabled = false; boton.innerHTML = original; });
        }

        var btnProbarRec = document.getElementById('btnProbarRecepcion');
        if (btnProbarRec) {
            btnProbarRec.addEventListener('click', function () {
                correrRecepcion('<?= site_url('correocompras/probar') ?>', btnProbarRec);
            });
        }
        var btnImportar = document.getElementById('btnImportarCompras');
        if (btnImportar) {
            btnImportar.addEventListener('click', function () {
                correrRecepcion('<?= site_url('correocompras/importar') ?>', btnImportar);
            });
        }
        var btnHistorico = document.getElementById('btnImportarHistorico');
        if (btnHistorico) {
            btnHistorico.addEventListener('click', function () {
                if (!confirm(<?= json_encode(lang('importar_historico_confirmar')) ?>)) { return; }
                correrRecepcion('<?= site_url('correocompras/importar') ?>', btnHistorico, { todo: '1' });
            });
        }
    })();

    // ── Impresoras de la computadora desde la que se abre Ajustes ──
    // Atajo para el caso normal: el administrador está sentado en la caja que
    // quiere configurar. QZ Tray solo enumera las impresoras de SU máquina, así
    // que desde otra computadora hay que abrir el POS en la caja una vez.
    (function () {
        var btn = document.getElementById('detectarImpresoras');
        if (!btn) { return; }
        var salida = document.getElementById('detectarResultado');

        function pintar(ok, texto) {
            salida.className = 'ns-prueba-resultado ' + (ok ? 'ok' : 'err');
            salida.innerHTML = (ok ? '<i class="fa fa-check-circle"></i> ' : '<i class="fa fa-times-circle"></i> ') + texto;
        }

        // Misma clave que usa el POS: así el puesto es el mismo, no uno nuevo.
        function idDeEsteEquipo() {
            var id = localStorage.getItem('nx-device-id');
            if (id && /^[a-f0-9-]{16,64}$/i.test(id)) { return id; }
            id = (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
               : 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                   var r = Math.random() * 16 | 0;
                   return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                 });
            localStorage.setItem('nx-device-id', id);
            return id;
        }

        function postWs(ruta, datos) {
            var body = new URLSearchParams();
            body.set('device_id', idDeEsteEquipo());
            Object.keys(datos || {}).forEach(function (k) {
                if (Array.isArray(datos[k])) { datos[k].forEach(function (v) { body.append(k + '[]', v); }); }
                else { body.set(k, datos[k]); }
            });
            // window.CSRF_HASH la refresca main.js con la cabecera X-CSRF-Token de
            // cada respuesta AJAX; csrfHash es solo el valor con el que arrancó la
            // página. csrf_regenerate está activo: reusar el de arranque en la
            // segunda petición de la pantalla responde 403.
            body.set(window.CSRF_NAME || csrfName, window.CSRF_HASH || csrfHash);
            return fetch('<?= site_url('workstation') ?>/' + ruta, {
                method: 'POST', body: body, credentials: 'same-origin'
            }).then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json();
            });
        }

        btn.addEventListener('click', function () {
            if (!window.qz) {
                pintar(false, <?= json_encode(lang('qz_desconectado')) ?>);
                return;
            }
            var original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i> ' + <?= json_encode(lang('qz_verificando')) ?>;

            // Modo sin firma de certificado, igual que en el POS.
            qz.security.setCertificatePromise(function (resolve) { resolve(); });
            qz.security.setSignaturePromise(function () { return function (resolve) { resolve(); }; });

            var conectar = qz.websocket.isActive() ? Promise.resolve() : qz.websocket.connect();
            conectar
                .then(function () { return qz.printers.find(); })
                .then(function (lista) {
                    var nombres = (Array.isArray(lista) ? lista : [lista]).filter(Boolean);
                    if (!nombres.length) { throw new Error(<?= json_encode(lang('puesto_sin_impresora')) ?>); }
                    return postWs('registrar', {})
                        .then(function () { return postWs('impresoras', { impresoras: nombres }); })
                        .then(function () {
                            pintar(true, nombres.join(', '));
                            setTimeout(function () { window.location.reload(); }, 1500);
                        });
                })
                .catch(function (e) {
                    pintar(false, e.message || <?= json_encode(lang('qz_desconectado_ayuda')) ?>);
                })
                .finally(function () { btn.disabled = false; btn.innerHTML = original; });
        });
    })();

    // ── Puestos de trabajo: renombrar al salir del campo, eliminar con confirmación ──
    (function () {
        var tabla = document.querySelector('.ns-puestos');
        if (!tabla) { return; }

        function postPuesto(ruta, datos) {
            var body = new URLSearchParams();
            Object.keys(datos).forEach(function (k) { body.set(k, datos[k]); });
            // window.CSRF_HASH la refresca main.js con la cabecera X-CSRF-Token de
            // cada respuesta AJAX; csrfHash es solo el valor con el que arrancó la
            // página. csrf_regenerate está activo: reusar el de arranque en la
            // segunda petición de la pantalla responde 403.
            body.set(window.CSRF_NAME || csrfName, window.CSRF_HASH || csrfHash);
            return fetch('<?= site_url('workstation') ?>/' + ruta, {
                method: 'POST', body: body, credentials: 'same-origin'
            }).then(function (r) {
                if (!r.ok) { throw new Error(r.status); }
                return r.json();
            });
        }

        tabla.addEventListener('change', function (e) {
            var fila = e.target.closest('tr');
            if (!fila) { return; }

            if (e.target.closest('.ns-puesto-nombre')) {
                postPuesto('renombrar', { id: fila.dataset.puesto, nombre: e.target.value })
                    .then(function () { nxAlerta('ok', <?= json_encode(lang('puesto_renombrado')) ?>); })
                    .catch(function () { nxAlerta('error', <?= json_encode(lang('puesto_error')) ?>); });
                return;
            }

            if (e.target.closest('.ns-puesto-impresora')) {
                postPuesto('impresora', { id: fila.dataset.puesto, qz_printer: e.target.value })
                    .then(function () { nxAlerta('ok', <?= json_encode(lang('puesto_impresora_guardada')) ?>); })
                    .catch(function () { nxAlerta('error', <?= json_encode(lang('puesto_error')) ?>); });
                return;
            }

            if (e.target.closest('.ns-puesto-papel')) {
                postPuesto('papel', { id: fila.dataset.puesto, caracteres: e.target.value })
                    .then(function () {
                        nxAlerta('ok', <?= json_encode(lang('puesto_papel_guardado')) ?>);
                        verTiquete(fila);
                    })
                    .catch(function () { nxAlerta('error', <?= json_encode(lang('puesto_error')) ?>); });
            }
        });

        // ── Vista previa y prueba del tiquete del puesto ──
        var panel = document.getElementById('tkPanel');
        var papel = document.getElementById('tkPapel');
        var titulo = document.getElementById('tkTitulo');
        var prueba = document.getElementById('tkPrueba');
        var pruebaMsg = document.getElementById('tkPruebaMsg');
        var filaActual = null;

        function datosFila(fila) {
            var imp = fila.querySelector('.ns-puesto-impresora');
            return {
                nombre: fila.querySelector('.ns-puesto-nombre').value,
                impresora: imp ? imp.value : '',
                caracteres: fila.querySelector('.ns-puesto-papel').value
            };
        }

        function verTiquete(fila) {
            filaActual = fila;
            var d = datosFila(fila);
            titulo.textContent = <?= json_encode(lang('tiquete_de_puesto')) ?>.replace('%s', d.nombre);
            pruebaMsg.textContent = '';
            pruebaMsg.className = 'ns-prueba-resultado';
            papel.style.setProperty('--tk-cpl', d.caracteres);
            papel.innerHTML = '<div class="tk-vacio"><i class="fa fa-spin fa-spinner"></i></div>';
            fetch('<?= site_url('posprint/receipt_preview') ?>?cpl=' + encodeURIComponent(d.caracteres), {
                credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    document.getElementById('tkVenta').textContent = j.venta ? '· #' + j.venta : '';
                    if (!j.status || !j.lineas || !j.lineas.length) {
                        papel.innerHTML = '<div class="tk-vacio">' + <?= json_encode(lang('tiquete_sin_ventas')) ?> + '</div>';
                        return;
                    }
                    papel.innerHTML = '';
                    j.lineas.forEach(function (l) {
                        var div = document.createElement('div');
                        if (l.corte) { div.className = 'tk-corte'; }
                        else if (l.imagen) { div.className = 'tk-img'; div.style.textAlign = l.a; div.textContent = '[ logo ]'; }
                        else {
                            div.className = 'tk-linea' + (l.b ? ' tk-b' : '') + (l.w > 1 ? ' tk-w' : '');
                            div.style.textAlign = l.a;
                            div.textContent = l.t === '' ? '\u00a0' : l.t;
                        }
                        papel.appendChild(div);
                    });
                    papel.dataset.venta = j.venta || '';
                })
                .catch(function () {
                    papel.innerHTML = '<div class="tk-vacio">' + <?= json_encode(lang('puesto_error')) ?> + '</div>';
                });
        }

        tabla.addEventListener('click', function (e) {
            var b = e.target.closest('.ns-puesto-vista');
            if (b) { verTiquete(b.closest('tr')); }
        });

        // La prueba imprime desde este navegador: QZ Tray solo alcanza las
        // impresoras de la computadora donde corre.
        if (prueba) {
            prueba.addEventListener('click', function () {
                var d = filaActual ? datosFila(filaActual) : null;
                var avisar = function (ok, texto) {
                    pruebaMsg.className = 'ns-prueba-resultado ' + (ok ? 'ok' : 'err');
                    pruebaMsg.textContent = texto;
                };
                if (!d || !d.impresora) { avisar(false, <?= json_encode(lang('puesto_sin_impresora')) ?>); return; }
                if (!papel.dataset.venta) { avisar(false, <?= json_encode(lang('tiquete_sin_ventas')) ?>); return; }
                if (!window.qz) { avisar(false, <?= json_encode(lang('qz_desconectado')) ?>); return; }
                prueba.disabled = true;
                qz.security.setCertificatePromise(function (resolve) { resolve(); });
                qz.security.setSignaturePromise(function () { return function (resolve) { resolve(); }; });
                var conectar = window.nxQzConectar ? window.nxQzConectar() : (qz.websocket.isActive() ? Promise.resolve() : qz.websocket.connect());
                conectar
                    .then(function () { return qz.printers.find(d.impresora); })
                    .then(function () {
                        return fetch('<?= site_url('posprint/receipt_bytes') ?>/' + papel.dataset.venta + '/1?cpl=' + encodeURIComponent(d.caracteres), {
                            credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(function (r) { return r.json(); });
                    })
                    .then(function (res) {
                        if (!res || res.status !== 1) { throw new Error('print_bytes_failed'); }
                        return qz.print(qz.configs.create(d.impresora), [
                            { type: 'raw', format: 'command', flavor: 'base64', data: res.bytes }
                        ]);
                    })
                    .then(function () { avisar(true, <?= json_encode(lang('prueba_enviada')) ?>); })
                    .catch(function (e) { avisar(false, (e && e.message) || 'error'); })
                    .finally(function () { prueba.disabled = false; });
            });
        }

        var primera = tabla.querySelector('tbody tr');
        if (primera && panel) { verTiquete(primera); }

        tabla.addEventListener('click', function (e) {
            var btn = e.target.closest('.ns-puesto-borrar');
            if (!btn) { return; }
            var fila = btn.closest('tr');
            Swal.fire({
                title: <?= json_encode(lang('puesto_borrar_confirma')) ?>,
                icon: 'warning', showCancelButton: true,
                confirmButtonText: <?= json_encode(lang('yes')) ?>,
                cancelButtonText: <?= json_encode(lang('cancel')) ?>,
                confirmButtonColor: '#dc2626'
            }).then(function (r) {
                if (!r.isConfirmed) { return; }
                postPuesto('eliminar', { id: fila.dataset.puesto })
                    .then(function () { fila.remove(); })
                    .catch(function () { nxAlerta('error', <?= json_encode(lang('puesto_error')) ?>); });
            });
        });
    })();

    // ── Atajos: se graban pulsando la combinación, no escribiéndola ──
    (function () {
        var grupos = Array.prototype.slice.call(document.querySelectorAll('.ns-atajo'));
        if (!grupos.length) { return; }

        var TXT_PULSE     = <?= json_encode(lang('atajos_pulse_tecla')) ?>;
        var TXT_COLISION  = <?= json_encode(lang('atajos_colision')) ?>;
        var TXT_RESERVADA = <?= json_encode(lang('atajos_reservada')) ?>;
        // El navegador nunca entrega estas teclas a la página.
        var RESERVADAS = ['F11', 'F12'];

        function campo(g)  { return g.querySelector('.ns-atajo-campo'); }
        function aviso(g)  { return g.parentNode.querySelector('.ns-atajo-aviso'); }

        function revisar() {
            var vistos = {};
            grupos.forEach(function (g) {
                var v = campo(g).value.trim().toUpperCase();
                if (!v) { return; }
                (vistos[v] = vistos[v] || []).push(g);
            });
            grupos.forEach(function (g) {
                var v = campo(g).value.trim().toUpperCase();
                var msg = '';
                if (v && vistos[v] && vistos[v].length > 1) { msg = TXT_COLISION; }
                else if (RESERVADAS.indexOf(v) !== -1) { msg = TXT_RESERVADA; }
                aviso(g).textContent = msg;
            });
        }

        function grabar(g) {
            var input = campo(g);
            var previo = input.value;
            g.classList.add('grabando');
            input.value = TXT_PULSE;

            function terminar() {
                g.classList.remove('grabando');
                document.removeEventListener('keydown', alPulsar, true);
                document.removeEventListener('click', alClic, true);
                revisar();
            }
            function alPulsar(e) {
                e.preventDefault();
                e.stopPropagation();
                // Un modificador suelto no es una combinación todavía.
                if (['Alt', 'Control', 'Shift', 'Meta'].indexOf(e.key) !== -1) { return; }
                if (e.key === 'Escape') { input.value = previo; terminar(); return; }
                var partes = [];
                if (e.ctrlKey || e.metaKey) { partes.push('CTRL'); }
                if (e.altKey)   { partes.push('ALT'); }
                if (e.shiftKey) { partes.push('SHIFT'); }
                partes.push(e.key.length === 1 ? e.key.toUpperCase() : e.key.toUpperCase());
                input.value = partes.join('+');
                terminar();
            }
            function alClic() { input.value = previo; terminar(); }

            document.addEventListener('keydown', alPulsar, true);
            setTimeout(function () { document.addEventListener('click', alClic, true); }, 0);
        }

        grupos.forEach(function (g) {
            g.querySelector('.ns-atajo-grabar').addEventListener('click', function (e) { e.stopPropagation(); grabar(g); });
            campo(g).addEventListener('click', function (e) { e.stopPropagation(); grabar(g); });
            g.querySelector('.ns-atajo-borrar').addEventListener('click', function () { campo(g).value = ''; revisar(); });
        });

        var btnRestaurar = document.getElementById('atajosRestaurar');
        if (btnRestaurar) {
            btnRestaurar.addEventListener('click', function () {
                grupos.forEach(function (g) { campo(g).value = g.dataset.defecto; });
                revisar();
            });
        }

        revisar();
    })();

    // ── Continuidad de la numeración: la clave de 50 dígitos ya trae el consecutivo ──
    var btnLeerClave = document.getElementById('btnLeerClave');
    if (btnLeerClave) {
        var inputClave  = document.getElementById('clave_ultima');
        var salidaClave = document.getElementById('claveLeida');
        var TIPOS = <?= json_encode(tipos_comprobante()) ?>;

        btnLeerClave.addEventListener('click', function () {
            var clave = (inputClave.value || '').replace(/\D/g, '');
            if (clave.length !== 50) {
                nxAlerta('warn', <?= json_encode(lang('clave_debe_tener_50')) ?>);
                return;
            }
            inputClave.value = clave;

            // Anexos v4.4: país 3, fecha 6, cédula 12, consecutivo 20, situación 1, seguridad 8.
            var consecutivo = clave.substr(21, 20);
            var tipo   = consecutivo.substr(8, 2);
            var numero = parseInt(consecutivo.substr(10, 10), 10);
            var campo  = document.getElementById('consec_inicial_' + tipo);

            salidaClave.style.display = '';
            if (!campo) {
                salidaClave.innerHTML = <?= json_encode(lang('clave_tipo_desconocido')) ?> + ' <b>' + tipo + '</b>';
                return;
            }
            campo.value = numero;
            salidaClave.innerHTML =
                <?= json_encode(lang('clave_leida_ok')) ?> +
                '<br>' + <?= json_encode(lang('tipo_comprobante')) ?> + ': <b>' + tipo + ' — ' + (TIPOS[tipo] || '') + '</b>' +
                '<br>' + <?= json_encode(lang('casa_matriz_terminal')) ?> + ': <b>' + consecutivo.substr(0, 3) + ' / ' + consecutivo.substr(3, 5) + '</b>' +
                '<br>' + <?= json_encode(lang('ultimo_numero_emitido')) ?> + ': <b>' + numero + '</b>';
            campo.focus();
        });
    }

    // ── Ubicación en cascada: provincia -> cantón -> distrito -> barrio (solo nombres) ──
    function fillSelect(select, items, placeholder) {
        select.innerHTML = '';
        var optPh = document.createElement('option');
        optPh.value = '';
        optPh.textContent = placeholder;
        select.appendChild(optPh);
        items.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.codigo;
            opt.textContent = item.nombre;
            select.appendChild(opt);
        });
    }
    var selProvincia = document.getElementById('cod_provincia');
    var selCanton = document.getElementById('cod_canton');
    var selDistrito = document.getElementById('cod_distrito');
    var selBarrio = document.getElementById('cod_barrio');
    var urlBase = "<?= site_url('settings') ?>";

    if (selProvincia) {
        selProvincia.addEventListener('change', function () {
            fillSelect(selCanton, [], 'Seleccione…');
            fillSelect(selDistrito, [], 'Seleccione…');
            fillSelect(selBarrio, [], 'Seleccione…');
            if (!selProvincia.value) { return; }
            fetch(urlBase + '/get_cantones/' + encodeURIComponent(selProvincia.value))
                .then(function (res) { return res.json(); })
                .then(function (data) { fillSelect(selCanton, data, 'Seleccione…'); });
        });
        selCanton.addEventListener('change', function () {
            fillSelect(selDistrito, [], 'Seleccione…');
            fillSelect(selBarrio, [], 'Seleccione…');
            if (!selCanton.value) { return; }
            fetch(urlBase + '/get_distritos/' + encodeURIComponent(selProvincia.value) + '/' + encodeURIComponent(selCanton.value))
                .then(function (res) { return res.json(); })
                .then(function (data) { fillSelect(selDistrito, data, 'Seleccione…'); });
        });
        selDistrito.addEventListener('change', function () {
            fillSelect(selBarrio, [], 'Seleccione…');
            if (!selDistrito.value) { return; }
            fetch(urlBase + '/get_barrios/' + encodeURIComponent(selProvincia.value) + '/' + encodeURIComponent(selCanton.value) + '/' + encodeURIComponent(selDistrito.value))
                .then(function (res) { return res.json(); })
                .then(function (data) { fillSelect(selBarrio, data, 'Seleccione…'); });
        });
    }

    // ── Tipo de documento: NO se selecciona manualmente, se calcula según el formato de la cédula ──
    var cedulaEmisorEl = document.getElementById('cedula_emisor');
    var tipoDocEmisorEl = document.getElementById('tipo_doc_emisor');
    var tipoDocDisplayEl = document.getElementById('tipo_doc_emisor_display');
    function tipoDocSegunCedula(cedula) {
        var len = cedula.length;
        if (len === 9) { return '01'; }  // Cédula física
        if (len === 10) { return '02'; } // Cédula jurídica
        if (len === 11 || len === 12) { return '03'; } // DIMEX
        return null; // formato no reconocido (ej. NITE): se deja el valor actual
    }
    function actualizarTipoDoc() {
        var cedula = cedulaEmisorEl.value.replace(/[^0-9]/g, '');
        var tipo = tipoDocSegunCedula(cedula);
        if (tipo) {
            tipoDocEmisorEl.value = tipo;
            if (tipoDocDisplayEl) { tipoDocDisplayEl.value = tipo; }
        }
    }
    if (cedulaEmisorEl && tipoDocEmisorEl) {
        cedulaEmisorEl.addEventListener('input', actualizarTipoDoc);
        actualizarTipoDoc();
    }

    // ── Datos del emisor: auto-completar desde Hacienda según la cédula (solo al presionar "Buscar") ──
    var actividadSelect = document.getElementById('default_actividad');
    var nombreEmisorEl = document.getElementById('nombre_emisor');
    var aeStatus = document.getElementById('ae-status');
    var btnBuscarCedula = document.getElementById('btn-buscar-cedula');
    if (btnBuscarCedula && cedulaEmisorEl && actividadSelect) {
        btnBuscarCedula.addEventListener('click', function () {
            var cedula = cedulaEmisorEl.value.replace(/[^0-9]/g, '');
            if (!cedula) {
                aeStatus.textContent = 'Escriba la cédula antes de buscar.';
                return;
            }
            var originalBtnHtml = btnBuscarCedula.innerHTML;
            btnBuscarCedula.disabled = true;
            btnBuscarCedula.innerHTML = '<i class="fa fa-spin fa-spinner"></i> Buscando...';
            aeStatus.textContent = 'Consultando datos del emisor en Hacienda...';

            // Limpia todo el bloque de Identificación del Emisor para que no queden datos
            // de la búsqueda anterior mezclados con los de la nueva cédula
            ['nombre_comercial', 'email_emisor', 'telefono_emisor', 'fax_emisor', 'otras_senas'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) { el.value = ''; }
            });
            if (selProvincia) { selProvincia.value = ''; }
            fillSelect(selCanton, [], 'Seleccione…');
            fillSelect(selDistrito, [], 'Seleccione…');
            fillSelect(selBarrio, [], 'Seleccione…');

            // (los <select> de nombre/tipo no se pueden "vaciar" sin una opción en blanco, se sobrescriben al llegar la respuesta)
            nombreEmisorEl.value = '';
            actividadSelect.innerHTML = '';

            fetch("<?= site_url('hacienda_proxy/ae/') ?>" + cedula)
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.error) {
                        aeStatus.textContent = 'No se pudo consultar Hacienda: ' + data.error;
                        return;
                    }
                    if (data.nombre && nombreEmisorEl) {
                        nombreEmisorEl.value = data.nombre;
                    }
                    // El tipo de documento NO se toma de Hacienda: se calcula según el formato de la cédula (ver actualizarTipoDoc)
                    var acts = data.actividades || [];
                    var statusMsg = data.nombre ? 'Datos del emisor completados desde Hacienda. ' : '';
                    if (acts.length === 0) {
                        aeStatus.textContent = statusMsg + 'Hacienda no devolvió actividades económicas para esta cédula.';
                    } else {
                        actividadSelect.innerHTML = '';
                        acts.forEach(function (a) {
                            var opt = document.createElement('option');
                            opt.value = a.codigo;
                            opt.textContent = a.codigo + ' — ' + a.descripcion;
                            actividadSelect.appendChild(opt);
                        });
                        actividadSelect.value = acts[0].codigo;
                        statusMsg += acts.length === 1
                            ? 'Actividad asignada automáticamente.'
                            : acts.length + ' actividades encontradas — seleccione una.';
                        aeStatus.textContent = statusMsg;
                    }
                    if (data.situacion && (data.situacion.moroso === true || data.situacion.omiso === true)) {
                        aeStatus.textContent += ' ⚠ Aviso de Hacienda: contribuyente ' +
                            (data.situacion.moroso ? 'moroso' : '') +
                            (data.situacion.moroso && data.situacion.omiso ? ' y ' : '') +
                            (data.situacion.omiso ? 'omiso' : '') + '.';
                    }
                })
                .catch(function () {
                    aeStatus.textContent = 'Error de conexión al consultar Hacienda.';
                })
                .finally(function () {
                    btnBuscarCedula.disabled = false;
                    btnBuscarCedula.innerHTML = originalBtnHtml;
                });
        });
    }

});
</script>

<!-- ==================== SINPE MÓVIL: estado en vivo + acciones ==================== -->
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    "use strict";

    var $dot        = document.getElementById('sinpeStatusDot');
    var $text       = document.getElementById('sinpeStatusText');
    var $emailRow   = document.getElementById('sinpeGmailEmailRow');
    var $email      = document.getElementById('sinpeGmailEmail');
    var $nombre     = document.getElementById('sinpeGmailNombre');
    var $foto       = document.getElementById('sinpeGmailFoto');
    var $fotoPh     = document.getElementById('sinpeGmailFotoPh');
    var $ultima     = document.getElementById('sinpeUltimaRevision');
    var $connectBtn = document.getElementById('sinpeConnectBtn');
    var $disconnBtn = document.getElementById('sinpeDisconnectBtn');
    var $configRow  = document.getElementById('sinpeConfigRow');
    var $banco      = document.getElementById('sinpeBancoSelect');
    var $intervalo  = document.getElementById('sinpeIntervaloInput');
    var $activo     = document.getElementById('sinpeActivoSwitch');
    var $activoLbl  = document.getElementById('sinpeActivoLabel');
    var $statPend   = document.getElementById('sinpeStatPendientes');
    var $statHoy    = document.getElementById('sinpeStatHoy');
    var $statMonto  = document.getElementById('sinpeStatMonto');
    var $importRow  = document.getElementById('sinpeImportRow');
    var $importBtn  = document.getElementById('sinpeImportBtn');
    var $importDias = document.getElementById('sinpeImportDias');
    var $importEst  = document.getElementById('sinpeImportEstado');

    if (!$dot) return; // la pestaña SINPE no está en esta vista

    var sinpeUrl = function (path) { return (window.base_url || '<?= base_url() ?>') + 'sinpe/' + path; };

    // csrf_protection esta activo y 'sinpe/*' no esta excluido: sin el token
    // todo POST se rechaza con 403 antes de llegar al controlador.
    var sinpeCsrfName = "<?= $this->security->get_csrf_token_name(); ?>";
    var sinpeCsrfHash = "<?= $this->security->get_csrf_hash(); ?>";

    function sinpePost(path, campos) {
        var body = new URLSearchParams(campos || {});
        body.set(sinpeCsrfName, sinpeCsrfHash);
        return fetch(sinpeUrl(path), { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json().catch(function () { return {}; });
            })
            .then(function (data) {
                if (data && data.error) { throw new Error(data.error); }
                return data;
            });
    }

    function money(n) {
        n = parseFloat(n) || 0;
        return '₡' + n.toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function mostrarAlerta(tipo, msg) {
        nxAlerta(tipo, msg);
    }

    function pintarEstado(s) {
        var conectado = !!s.conectado;
        var enVivo = !!s.watching;
        // Servicio caído y cuenta sin conectar son problemas distintos: con el proceso
        // Node abajo, el botón de Gmail no puede hacer nada y no debe ofrecerse.
        var servicioArriba = s.servicio !== false;

        var $caido = document.getElementById('sinpeServicioCaido');
        if ($caido) { $caido.style.display = servicioArriba ? 'none' : ''; }

        $dot.className = 'ns-sinpe-dot ' + (enVivo ? 'on' : (conectado ? 'off' : ''));
        $text.textContent = !servicioArriba
            ? <?= json_encode(lang('sinpe_servicio_caido_titulo')) ?>
            : (enVivo ? '● EN VIVO' : (conectado ? 'Conectado — vigilancia pausada' : 'No conectado'));

        if (!servicioArriba) {
            $connectBtn.style.display = 'none';
            $disconnBtn.style.display = 'none';
            $emailRow.style.display = 'none';
            $configRow.style.opacity = '.5';
            $configRow.style.pointerEvents = 'none';
            $ultima.textContent = '—';
            $statPend.textContent = $statHoy.textContent = $statMonto.textContent = '—';
            return;
        }

        pintarPerfil(conectado, s);

        $ultima.textContent = s.ultimaRevision
            ? new Date(s.ultimaRevision).toLocaleTimeString('es-CR')
            : (s.lastCheck ? new Date(s.lastCheck).toLocaleTimeString('es-CR') : '—');

        $connectBtn.style.display = conectado ? 'none' : '';
        $disconnBtn.style.display = conectado ? '' : 'none';

        $configRow.style.opacity = conectado ? '1' : '.5';
        $configRow.style.pointerEvents = conectado ? '' : 'none';

        // El estado se refresca cada 10s: si el usuario ya tocó estos campos y
        // todavía no guardó, el poll no debe pisarle lo que eligió.
        if (!configTocada) {
            if (s.banco) $banco.value = s.banco;
            if (s.intervalo) $intervalo.value = s.intervalo;
            $activo.checked = !!s.activo;
        }
        $activoLbl.textContent = $activo.checked ? 'Activa' : 'Pausada';

        if ($importRow) {
            $importRow.style.opacity = conectado ? '1' : '.5';
            $importRow.style.pointerEvents = conectado ? '' : 'none';
        }
        pintarImportacion(s.importacion);

        var st = s.stats || {};
        $statPend.textContent = st.pendientes != null ? st.pendientes : '—';
        $statHoy.textContent = st.hoy != null ? st.hoy : '—';
        $statMonto.textContent = st.monto_total != null ? money(st.monto_total) : '—';
    }

    /** Foto, nombre y correo de la cuenta de Gmail conectada. */
    function pintarPerfil(conectado, s) {
        var perfil = s.perfil || {};
        var correo = perfil.email || s.gmailEmail;

        if (!conectado || !correo) {
            $emailRow.style.display = 'none';
            return;
        }

        $email.textContent = correo;

        // El nombre puede faltar si Google no contestó el userinfo todavía.
        $nombre.textContent = perfil.nombre || '';
        $nombre.style.display = perfil.nombre ? '' : 'none';

        if (perfil.foto) {
            $foto.src = perfil.foto;
            $foto.alt = perfil.nombre || correo;
            $foto.style.display = '';
            $fotoPh.style.display = 'none';
        } else {
            $foto.style.display = 'none';
            $fotoPh.style.display = '';
        }

        $emailRow.style.display = '';
    }

    // Si la foto de Google no carga, queda el ícono genérico en su lugar.
    if ($foto) {
        $foto.addEventListener('error', function () {
            $foto.style.display = 'none';
            $fotoPh.style.display = '';
        });
    }

    var configTocada = false;
    [$banco, $intervalo, $activo].forEach(function (el) {
        if (el) el.addEventListener('change', function () { configTocada = true; });
    });

    function pintarImportacion(imp) {
        if (!$importEst || !$importBtn) return;
        if (!imp) { $importEst.textContent = ''; $importBtn.disabled = false; return; }

        if (imp.corriendo) {
            $importBtn.disabled = true;
            $importEst.textContent = 'Importando… ' + imp.revisados + ' correos revisados, ' +
                imp.guardados + ' guardados.';
        } else {
            $importBtn.disabled = false;
            if (imp.error) {
                $importEst.textContent = 'La importacion fallo: ' + imp.error;
            } else if (imp.fin) {
                $importEst.textContent = 'Listo: ' + imp.guardados + ' SINPE nuevos de ' +
                    imp.revisados + ' correos revisados.';
            }
        }
    }

    if ($importBtn) {
        $importBtn.addEventListener('click', function () {
            var dias = $importDias ? $importDias.value : '90';
            var etiqueta = dias === '0' ? 'todo el historial' : 'los ultimos ' + dias + ' dias';
            if (!confirm('¿Revisar ' + etiqueta + ' de la cuenta de Gmail en busca de pagos SINPE?')) return;
            $importBtn.disabled = true;
            $importEst.textContent = 'Iniciando…';
            sinpePost('importar', { dias: dias })
                .then(function (d) { pintarImportacion(d.importacion); })
                .catch(function (e) {
                    $importBtn.disabled = false;
                    $importEst.textContent = 'No se pudo iniciar: ' + e.message;
                })
                .finally(cargarEstado);
        });
    }

    function cargarEstado() {
        fetch(sinpeUrl('estado'), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(pintarEstado)
            .catch(function () {
                $dot.className = 'ns-sinpe-dot off';
                $text.textContent = 'El servicio SINPE no responde';
            });
    }

    cargarEstado();
    var pollTimer = setInterval(cargarEstado, 10000);
    window.addEventListener('beforeunload', function () { clearInterval(pollTimer); });

    if ($disconnBtn) {
        $disconnBtn.addEventListener('click', function () {
            if (!confirm('¿Desconectar la cuenta de Gmail? La vigilancia de SINPE se detiene hasta que se vuelva a conectar.')) return;
            $disconnBtn.disabled = true;
            sinpePost('desconectar')
                .then(function () { mostrarAlerta('info', 'Cuenta de Gmail desconectada.'); })
                .catch(function (e) { mostrarAlerta('danger', 'No se pudo desconectar: ' + e.message); })
                .finally(function () { $disconnBtn.disabled = false; cargarEstado(); });
        });
    }

    // El formato de banco, el intervalo y la vigilancia no son campos del
    // formulario: viven en el servicio Node y hay que empujarlos aparte antes
    // de dejar pasar el submit de "Guardar configuración".
    var $form = document.getElementById('settings-form');
    var sinpeYaEmpujado = false;

    if ($form) {
        $form.addEventListener('submit', function (e) {
            if (sinpeYaEmpujado || !configTocada) return;

            e.preventDefault();
            var boton = e.submitter;

            Promise.all([
                sinpePost('banco', { banco: $banco.value }),
                sinpePost('vigilancia', { activar: $activo.checked ? '1' : '0', intervalo: $intervalo.value }),
            ]).then(function () {
                configTocada = false;
                sinpeYaEmpujado = true;
                // requestSubmit conserva el name del botón; submit() lo perdería.
                if (boton && $form.requestSubmit) { $form.requestSubmit(boton); }
                else { $form.submit(); }
            }).catch(function (err) {
                mostrarAlerta('danger', 'No se pudo guardar la configuración de SINPE: ' + err.message +
                    '. No se guardó ningún ajuste; revisá el servicio SINPE y volvé a intentarlo.');
                cargarEstado();
            });
        });
    }

    if ($activo) {
        $activo.addEventListener('change', function () {
            $activoLbl.textContent = $activo.checked ? 'Activa' : 'Pausada';
        });
    }

    // Si venimos de volver del flujo de Google (sinpe/conectar → oauth2/callback → acá),
    // abrir la pestaña SINPE sola y mostrar el resultado.
    var qp = new URLSearchParams(window.location.search);
    if (qp.get('sinpe')) {
        var navTab = document.getElementById('navTabSinpe');
        if (navTab && window.bootstrap) {
            new bootstrap.Tab(navTab).show();
        } else if (navTab) {
            navTab.click();
        }
        if (qp.get('sinpe') === 'conectado') {
            mostrarAlerta('success', '¡Gmail conectado! La vigilancia de SINPE ya está activa.');
        } else {
            mostrarAlerta('danger', 'No se pudo conectar con Gmail (' + (qp.get('motivo') || 'error desconocido') + '). Volvé a intentarlo.');
        }
        cargarEstado();
    }
});
</script>
