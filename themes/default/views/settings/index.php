<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-bs-dismiss="alert"><span>&times;</span></button>
    <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

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
            }
            .ns-mockup .card-header {
                background: transparent; border-bottom: 1px solid var(--ns-border-soft);
                padding: 16px 20px; color: var(--ns-text-1);
            }
            .ns-mockup .card-body { padding: 20px; }
            .ns-mockup .card fieldset[disabled] .card-body { opacity: .5; }

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
            .ns-mockup .input-group-btn .btn { background: var(--ns-surface-3); border: 1px solid var(--ns-border); color: var(--ns-text-2); }

            /* ── Botones: reskin del sistema de .btn ── */
            .ns-mockup .btn { border-radius: 10px; font-weight: 600; font-size: 13.5px; }
            .ns-mockup .btn-primary { background: var(--ns-primary); border-color: var(--ns-primary); box-shadow: 0 8px 20px -8px rgba(76,124,245,.6); }
            .ns-mockup .btn-primary:hover { background: var(--ns-primary-dim); border-color: var(--ns-primary-dim); }
            .ns-mockup .btn-default { background: var(--ns-surface-2); border-color: var(--ns-border); color: var(--ns-text-2); }
            .ns-mockup .btn-warning { background: var(--ns-seal-soft); border-color: rgba(232,165,61,.3); color: var(--ns-seal); }
            .ns-mockup .btn-warning:hover { background: rgba(232,165,61,.22); }
            .ns-mockup .btn-success { background: var(--ns-success-soft); border-color: rgba(52,199,123,.3); color: var(--ns-success); }

            .ns-settings-nav { border-right: 3px solid var(--ns-border); padding-right: 0; }
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

            /* ── File inputs modernos (input nativo, sin dropzone) ── */
            .ns-mockup input[type="file"] { display: block; }
            .ns-mockup input[type="file"]::file-selector-button {
                background: linear-gradient(135deg, var(--ns-primary) 0%, var(--ns-seal) 100%);
                color: #fff; padding: 8px 16px; border: none; border-radius: 4px;
                cursor: pointer; font-weight: 600; font-size: 13px; transition: all .2s;
            }
            .ns-mockup input[type="file"]::file-selector-button:hover { transform: translateY(-1px); }

            /* ── Barra de guardado ── */
            .ns-save-bar {
                background: var(--ns-surface-2); border-top: 2px solid var(--ns-border);
                padding: 14px 20px; position: sticky; bottom: 0; z-index: 100;
                box-shadow: var(--ns-shadow-lg); border-radius: var(--ns-radius-sm) var(--ns-radius-sm) 0 0;
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
                                            <label for="dateformat"><i class="fa fa-calendar"></i> <?php echo lang('dateformat'); ?> <a href="http://php.net/manual/en/function.date.php" target="_blank"><i class="fa fa-external-link"></i></a></label>
                                            <?php echo form_input('dateformat', $settings->dateformat ?? 'd/m/Y', 'class="form-control ns-mono" id="dateformat" required="required"'); ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="timeformat"><i class="fa fa-clock-o"></i> <?php echo lang('timeformat'); ?></label>
                                            <?php echo form_input('timeformat', $settings->timeformat ?? 'h:i A', 'class="form-control ns-mono" id="timeformat" required="required"'); ?>
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
                                <div class="ns-card-icon seal"><i class="fa fa-exchange"></i></div>
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
                                <div class="ns-card-icon seal"><i class="fa fa-id-card-o"></i></div>
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
                                                    <button type="button" class="btn btn-warning" id="btn-buscar-cedula" title="Buscar en Hacienda"><i class="fa fa-search"></i> Buscar</button>
                                                </span>
                                            </div>
                                            <span class="help-block" id="ae-status">Escriba la cédula y presione «Buscar» para completar automáticamente el nombre, tipo de documento y actividad económica desde Hacienda.</span>
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
                                <div class="ns-card-icon seal"><i class="fa fa-map-marker"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('direccion_tributario'); ?></strong></div>
                                <div style="margin-left:auto;">
                                    <a target="_blank" href="https://tribunet.hacienda.go.cr/docs/esquemas/2016/v4.2/Codificacionubicacion_V4.2.zip" style="font-size:12px;display:flex;align-items:center;gap:5px;"><i class="fa fa-download"></i> Codigos de ubicacion</a>
                                </div>
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
                                <div class="ns-card-icon seal"><i class="fa fa-industry"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('actividad_economica'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="default_actividad"><i class="fa fa-list-alt"></i> <?= lang('actividad_predeterminada'); ?></label>
                                            <?php
                                            $act_opts = array();
                                            foreach ($actividadeconomica as $actividad) {
                                                $act_opts[$actividad->id_actividad] = $actividad->id_actividad . ' - ' . $actividad->descripcion;
                                            }
                                            echo form_dropdown('default_actividad', $act_opts, $settings->default_actividad ?? '', 'class="form-control tom-select" style="width:100%;" id="default_actividad" required="required"');
                                            ?>
                                            <span class="help-block">Se completa automáticamente según la cédula del emisor.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOKENS API HACIENDA -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon seal"><i class="fa fa-key"></i></div>
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
                                <div class="ns-card-icon seal"><i class="fa fa-certificate"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('certificado_digital'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="certificado_ced"><i class="fa fa-file-o"></i> <?= lang('nombre_certificado_label'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->certificado_ced ?? '') ?>" class="form-control ns-mono" id="certificado_ced" name="certificado_ced" type="text" placeholder="310100000000">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="certificado_pin"><i class="fa fa-key"></i> <?= lang('pin_certificado'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->certificado_pin ?? '') ?>" class="form-control ns-mono" id="certificado_pin" name="certificado_pin" type="password" placeholder="0000">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="certificado_pin" title="<?= lang('ver_ocultar'); ?>"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label><i class="fa fa-upload"></i> <?= lang('subir_certificado_label'); ?></label>
                                            <?php
                                            $certFile = FCPATH . 'files/certificados/' . ($settings->ambiente ?? 'test') . '/' . ($settings->certificado_ced ?? '') . '.p12';
                                            $certExists = !empty($settings->certificado_ced) && file_exists($certFile);
                                            ?>
                                            <?php if ($certExists): ?>
                                                <p class="text-success" style="margin:0 0 4px;"><i class="fa fa-check-circle"></i> <?= lang('certificado_cargado'); ?> <strong><?= htmlspecialchars($settings->certificado_ced) ?>.p12</strong></p>
                                            <?php else: ?>
                                                <p class="text-warning" style="margin:0 0 4px;"><i class="fa fa-exclamation-triangle"></i> <?= lang('no_hay_certificado'); ?></p>
                                            <?php endif; ?>
                                            <form action="<?= site_url('settings/upload_certificado') ?>" method="post" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;">
                                                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                                                <div class="ns-dropzone" id="ns-cert-dropzone" style="flex:1;">
                                                    <input type="file" name="certificado_p12" accept=".p12" required>
                                                    <div class="ns-dropzone-icon"><i class="fa fa-certificate"></i></div>
                                                    <div class="ns-dropzone-text">
                                                        <b id="ns-cert-label"><?= lang('subir_certificado_label'); ?></b>
                                                        <span>.p12</span>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-warning btn-sm" style="white-space:nowrap;"><i class="fa fa-upload"></i> <?= lang('subir'); ?></button>
                                            </form>
                                            <span class="help-block"><?= lang('ambiente_activo_info'); ?> <strong><?= htmlspecialchars($settings->ambiente ?? 'test') ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER FE -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon seal"><i class="fa fa-align-left"></i></div>
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

                        <!-- BLOQUEO / SINCRONIZACION CABYS -->
                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-database"></i></div>
                                <div class="ns-card-head-text"><strong><?= lang('sincronizacion_bloqueo'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4><i class="fa fa-refresh"></i> <?= lang('catalogo_cabys_label'); ?></h4>
                                        <p><?= lang('cabys_cache_info'); ?></p>
                                        <button type="button" id="btn-limpiar-cabys" class="btn btn-warning">
                                            <i class="fa fa-refresh"></i> <?= lang('limpiar_cache_cabys'); ?>
                                        </button>
                                        <span id="cabys-sync-result" style="margin-left:10px;display:none;"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ns-toggle-row">
                                            <div class="ns-toggle-text">
                                                <b><i class="fa fa-lock"></i> <?= lang('bloqueo_config_hacienda'); ?></b>
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

                        <div class="card" data-card>
                            <div class="card-header ns-card-head">
                                <div class="ns-card-icon"><i class="fa fa-envelope-o"></i></div>
                                <div class="ns-card-head-text"><strong><?php echo lang('email'); ?></strong></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="default_email"><i class="fa fa-envelope-o"></i> <?php echo lang('default_email'); ?></label>
                                            <?php echo form_input('default_email', $settings->default_email ?? '', 'class="form-control" id="default_email" type="email" required="required"'); ?>
                                        </div>
                                        <div class="mb-3">
                                            <label for="protocol"><i class="fa fa-cogs"></i> <?php echo lang('email_protocol'); ?></label>
                                            <?php
                                            $popt = array('mail' => 'PHP Mail Function', 'sendmail' => 'Send Mail', 'smtp' => 'SMTP');
                                            echo form_dropdown('protocol', $popt, $settings->protocol ?? 'mail', 'class="form-control tom-select" id="protocol" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SENDMAIL -->
                        <div id="sendmail_config" style="display:none;">
                            <div class="card" data-card>
                                <div class="card-header ns-card-head">
                                    <div class="ns-card-icon"><i class="fa fa-terminal"></i></div>
                                    <div class="ns-card-head-text"><strong>Configuracion Sendmail</strong></div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="mailpath"><i class="fa fa-folder-o"></i> <?php echo lang('mailpath'); ?></label>
                                                <?php echo form_input('mailpath', $settings->mailpath ?? '/usr/sbin/sendmail', 'class="form-control ns-mono" id="mailpath" placeholder="/usr/sbin/sendmail"'); ?>
                                                <span class="help-block"><?= lang('ruta_sendmail'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMTP -->
                        <div id="smtp_config" style="display:none;">
                            <div class="card" data-card>
                                <div class="card-header ns-card-head">
                                    <div class="ns-card-icon"><i class="fa fa-envelope"></i></div>
                                    <div class="ns-card-head-text"><strong>Configuracion SMTP</strong></div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_host"><i class="fa fa-server"></i> <?php echo lang('smtp_host'); ?></label>
                                                <?php echo form_input('smtp_host', $settings->smtp_host ?? '', 'class="form-control ns-mono" id="smtp_host" placeholder="smtp.gmail.com"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_user"><i class="fa fa-user-o"></i> <?php echo lang('smtp_user'); ?></label>
                                                <?php echo form_input('smtp_user', $settings->smtp_user ?? '', 'class="form-control" id="smtp_user" placeholder="' . lang('placeholder_smtp_user') . '"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_pass"><i class="fa fa-lock"></i> <?php echo lang('smtp_pass'); ?></label>
                                                <div class="input-group">
                                                    <input type="password" name="smtp_pass" id="smtp_pass" value="<?= htmlspecialchars($settings->smtp_pass ?? '') ?>" class="form-control" placeholder="<?= lang('placeholder_smtp_pass'); ?>">
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-default btn-toggle-pw" data-target="smtp_pass" title="<?= lang('ver_ocultar'); ?>"><i class="fa fa-eye"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="smtp_port"><i class="fa fa-plug"></i> <?php echo lang('smtp_port'); ?></label>
                                                <?php echo form_input('smtp_port', $settings->smtp_port ?? '587', 'class="form-control ns-mono" id="smtp_port" placeholder="587"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="smtp_crypto"><i class="fa fa-shield"></i> <?php echo lang('smtp_crypto'); ?></label>
                                                <?php
                                                $crypto_opt = array('' => lang('none'), 'tls' => 'TLS', 'ssl' => 'SSL');
                                                echo form_dropdown('smtp_crypto', $crypto_opt, $settings->smtp_crypto ?? 'tls', 'class="form-control tom-select" id="smtp_crypto" style="width:100%;"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                            <label for="after_sale_page"><i class="fa fa-arrow-right"></i> <?php echo lang('after_sale_page'); ?></label>
                                            <?php
                                            $asp = array(0 => lang('receipt'), 1 => lang('pos'));
                                            echo form_dropdown('after_sale_page', $asp, $settings->after_sale_page ?? 0, 'class="form-control tom-select" id="after_sale_page" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
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
                                <div class="ns-card-head-text"><strong><?= lang('sec_atajos_pos'); ?></strong><small>Combinación de teclas para cada acción en el POS</small></div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="focus_add_item"><i class="fa fa-crosshairs"></i> <?= lang('atajo_agregar_item'); ?></label>
                                            <?php echo form_input('focus_add_item', $settings->focus_add_item ?? 'ALT+I', 'class="form-control ns-mono" id="focus_add_item"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="edit_last_product"><i class="fa fa-edit"></i> <?= lang('atajo_editar_ultimo'); ?></label>
                                            <?php echo form_input('edit_last_product', $settings->edit_last_product ?? 'F1', 'class="form-control ns-mono" id="edit_last_product"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="add_customer"><i class="fa fa-user-plus"></i> <?= lang('atajo_agregar_cliente'); ?></label>
                                            <?php echo form_input('add_customer', $settings->add_customer ?? 'ALT+C', 'class="form-control ns-mono" id="add_customer"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="toggle_category_slider"><i class="fa fa-bars"></i> <?= lang('atajo_alternar_cats'); ?></label>
                                            <?php echo form_input('toggle_category_slider', $settings->toggle_category_slider ?? 'ALT+C', 'class="form-control ns-mono" id="toggle_category_slider"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cancel_sale"><i class="fa fa-times-circle"></i> <?= lang('atajo_cancelar_venta'); ?></label>
                                            <?php echo form_input('cancel_sale', $settings->cancel_sale ?? 'F9', 'class="form-control ns-mono" id="cancel_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="suspend_sale"><i class="fa fa-pause-circle"></i> <?= lang('atajo_suspender_venta'); ?></label>
                                            <?php echo form_input('suspend_sale', $settings->suspend_sale ?? 'F5', 'class="form-control ns-mono" id="suspend_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="print_order"><i class="fa fa-print"></i> <?= lang('atajo_imprimir_orden'); ?></label>
                                            <?php echo form_input('print_order', $settings->print_order ?? '', 'class="form-control ns-mono" id="print_order"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="print_bill"><i class="fa fa-file-text-o"></i> <?= lang('atajo_imprimir_factura'); ?></label>
                                            <?php echo form_input('print_bill', $settings->print_bill ?? '', 'class="form-control ns-mono" id="print_bill"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="finalize_sale"><i class="fa fa-check-circle"></i> <?= lang('atajo_finalizar_venta'); ?></label>
                                            <?php echo form_input('finalize_sale', $settings->finalize_sale ?? 'F12', 'class="form-control ns-mono" id="finalize_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="today_sale"><i class="fa fa-calendar-check-o"></i> <?= lang('atajo_ventas_hoy'); ?></label>
                                            <?php echo form_input('today_sale', $settings->today_sale ?? 'ALT+V', 'class="form-control ns-mono" id="today_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="open_hold_bills"><i class="fa fa-folder-open-o"></i> <?= lang('atajo_retomar'); ?></label>
                                            <?php echo form_input('open_hold_bills', $settings->open_hold_bills ?? '', 'class="form-control ns-mono" id="open_hold_bills"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="close_register"><i class="fa fa-sign-out"></i> <?= lang('atajo_cerrar_caja'); ?></label>
                                            <?php echo form_input('close_register', $settings->close_register ?? 'ALT+R', 'class="form-control ns-mono" id="close_register"'); ?>
                                        </div>
                                    </div>
                                </div>
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
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="ns-toggle-row">
                                                <div class="ns-toggle-text"><b><i class="fa fa-file-text"></i> <?php echo lang('question_print_inoice'); ?></b></div>
                                                <label class="ns-switch">
                                                    <input type="checkbox" class="ns-switch-bind" data-bind="prt_invo_after" <?= (($settings->prt_invo_after ?? 0) == 1) ? 'checked' : ''; ?>>
                                                    <span class="ns-track"></span><span class="ns-thumb"></span>
                                                </label>
                                            </div>
                                            <?php
                                            $prtopt = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('prt_invo_after', $prtopt, $settings->prt_invo_after ?? 0, 'class="form-control ns-select-hidden" id="prt_invo_after" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="barcode_symbology"><i class="fa fa-barcode"></i> <?= lang('simbologia_barras'); ?></label>
                                            <?php
                                            $bsyms = array('C128' => 'Code 128', 'C39' => 'Code 39', 'EAN13' => 'EAN-13', 'EAN8' => 'EAN-8', 'UPCA' => 'UPC-A', 'UPCE' => 'UPC-E');
                                            echo form_dropdown('barcode_symbology', $bsyms, $settings->barcode_symbology ?? 'C128', 'class="form-control tom-select" id="barcode_symbology" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="order_printers"><i class="fa fa-list-ol"></i> <?php echo lang('order_printers'); ?></label>
                                            <?php
                                            $printer_opts2 = array();
                                            if (!empty($printers)) {
                                                foreach ($printers as $printer) {
                                                    $printer_opts2[$printer->id] = $printer->title;
                                                }
                                            }
                                            echo form_dropdown('order_printers[]', $printer_opts2, '', 'multiple class="form-control tom-select" id="order_printers" style="width:100%;"');
                                            ?>
                                        </div>
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

        </div>
        </div>
    </div>
</section>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    "use strict";

    // Inicializar order_printers
    var orderPrintersVal = <?php echo (!empty($settings->order_printers) ? $settings->order_printers : '[]'); ?>;
    var orderPrintersEl = document.getElementById('order_printers');
    if (orderPrintersEl) {
        var orderPrintersTS = new TomSelect(orderPrintersEl, {});
        if (orderPrintersVal && orderPrintersVal.length > 0) {
            orderPrintersTS.setValue(orderPrintersVal);
        }
    }

    // Toggle protocolo email
    var protocolEl = document.getElementById('protocol');
    function toggleEmailProtocol() {
        var proto = protocolEl.value;
        document.getElementById('smtp_config').style.display = (proto === 'smtp') ? '' : 'none';
        document.getElementById('sendmail_config').style.display = (proto === 'sendmail') ? '' : 'none';
    }
    if (protocolEl) {
        toggleEmailProtocol();
        protocolEl.addEventListener('change', toggleEmailProtocol);
    }

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

    // ── Buscador de campos (filtra tarjetas dentro de la pestana activa) ──
    window.nsFilterCards = function (q) {
        q = (q || '').trim().toLowerCase();
        var activePane = document.querySelector('.tab-pane.active');
        if (!activePane) { return; }
        var cards = activePane.querySelectorAll('[data-card]');
        var anyVisible = false;
        cards.forEach(function (card) {
            var text = card.textContent.toLowerCase();
            var match = !q || text.indexOf(q) !== -1;
            card.style.display = match ? '' : 'none';
            if (match) { anyVisible = true; }
        });
        document.getElementById('ns-no-results').classList.toggle('show', !!q && !anyVisible);
    };
    var searchInputEl = document.getElementById('ns-search-input');
    searchInputEl.addEventListener('input', function () {
        nsFilterCards(searchInputEl.value);
    });

    // ── Nav movil (chips) generado a partir del nav desktop ──
    var navMobile = document.getElementById('nsNavMobile');
    document.querySelectorAll('#nsNavDesktop .nav-pills > li > a').forEach(function (a) {
        var chip = document.createElement('div');
        chip.className = 'ns-nav-chip';
        var icon = a.querySelector('.fa');
        var label = a.querySelector('.nx-nav-label b');
        chip.innerHTML = '<i class="' + (icon ? icon.className : '') + '"></i><span>' + (label ? label.textContent : '') + '</span>';
        chip.dataset.href = a.getAttribute('href');
        if (a.closest('li').classList.contains('active')) { chip.classList.add('active'); }
        navMobile.appendChild(chip);
    });

    // ── Cambio de pestaña: manejo propio (no delegar en bootstrap.Tab, que no
    // desactiva bien el <li> anterior cuando los triggers están envueltos en <li>) ──
    function activateSettingsTab(href) {
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
        searchInputEl.value = '';
        nsFilterCards('');
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
            body.set(csrfName, csrfHash);
            fetch(urlComprueba, { method: 'POST', body: body })
                .then(function (res) { return res.text(); })
                .then(function (data) { alert(data); })
                .catch(function () { alert('Error de conexion al servidor de Hacienda.'); })
                .finally(function () { btn.innerHTML = originalHtml; });
        });
    }
    wireComprueba('comprueba_test', 'user_token_test', 'password_token_test', 'test');
    wireComprueba('comprueba_prod', 'user_token_prod', 'password_token_prod', 'prod');

    // Limpiar cache CABYS
    var btnCabys = document.getElementById('btn-limpiar-cabys');
    if (btnCabys) {
        btnCabys.addEventListener('click', function () {
            var originalHtml = btnCabys.innerHTML;
            btnCabys.innerHTML = '<i class="fa fa-spin fa-spinner"></i> Limpiando...';
            var body = new URLSearchParams();
            body.set(csrfName, csrfHash);
            var resultEl = document.getElementById('cabys-sync-result');
            fetch('<?= site_url("hacienda_proxy/limpiar_cache_cabys") ?>', { method: 'POST', body: body })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    resultEl.innerHTML = '<span class="text-success"><i class="fa fa-check"></i> Cache limpiado (' + (data.eliminados || 0) + ' registros)</span>';
                    resultEl.style.display = '';
                })
                .catch(function () {
                    resultEl.innerHTML = '<span class="text-danger"><i class="fa fa-times"></i> Error al limpiar cache</span>';
                    resultEl.style.display = '';
                })
                .finally(function () {
                    btnCabys.innerHTML = '<i class="fa fa-refresh"></i> <?= lang('limpiar_cache_cabys'); ?>';
                });
        });
    }

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
    wireDropzone('ns-cert-dropzone', 'ns-cert-label');

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
