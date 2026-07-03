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

            <?php echo form_open_multipart("settings", 'class="validation" id="settings-form" novalidate'); ?>
            <?php echo form_hidden('default_discount', $settings->default_discount ?? '0'); ?>
            <?php echo form_hidden('tax_rate', $settings->default_tax_rate ?? '0'); ?>
            <?php echo form_hidden('rtl', $settings->rtl ?? 0); ?>
            <?php echo form_hidden('stripe', $settings->stripe ?? 0); ?>
            <?php echo form_hidden('stripe_secret_key', $settings->stripe_secret_key ?? ''); ?>
            <?php echo form_hidden('stripe_publishable_key', $settings->stripe_publishable_key ?? ''); ?>
            <?php echo form_hidden('remote_printing', $settings->remote_printing ?? 0); ?>
            <?php echo form_hidden('local_printers', $settings->local_printers ?? 0); ?>
            <?php echo form_hidden('print_img', $settings->print_img ?? 0); ?>
            <?php echo form_hidden('multi_store', $settings->multi_store ?? 0); ?>
            <?php echo form_hidden('bill_header', $settings->header ?? ''); ?>
            <?php echo form_hidden('bill_footer', $settings->footer ?? ''); ?>

            <style>
            .nx-settings-nav { border-right: 3px solid var(--nx-border); padding-right: 0; }
            .nx-settings-nav .nav-pills > li > a {
                border-radius: 0;
                padding: 14px 16px;
                color: var(--nx-txt2);
                font-size: 13px;
                font-weight: 600;
                border-left: 3px solid transparent;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: all .15s;
            }
            .nx-settings-nav .nav-pills > li > a .fa {
                font-size: 20px;
                width: 26px;
                text-align: center;
                flex-shrink: 0;
            }
            .nx-settings-nav .nav-pills > li > a:hover { background: rgba(56,189,248,.07); color: var(--nx-a1); }
            .nx-settings-nav .nav-pills > li.active > a,
            .nx-settings-nav .nav-pills > li.active > a:hover {
                background: rgba(56,189,248,.12);
                color: var(--nx-a1);
                border-left: 3px solid var(--nx-a1);
            }
            .nx-settings-nav .nav-pills > li > a .nx-nav-label { display: flex; flex-direction: column; }
            .nx-settings-nav .nav-pills > li > a .nx-nav-sub { font-size: 10px; font-weight: 400; color: var(--nx-txt3); margin-top: 1px; }
            .nx-settings-nav .nav-pills > li.active > a .nx-nav-sub { color: var(--nx-a1); }

            /* ── File inputs modernos ── */
            input[type="file"] { display: block; }
            input[type="file"]::file-selector-button {
                background: linear-gradient(135deg, var(--nx-a1) 0%, var(--nx-a2) 100%);
                color: var(--nx-bg, #06091a); padding: 8px 16px; border: none; border-radius: 4px;
                cursor: pointer; font-weight: 600; font-size: 13px; transition: all .2s;
            }
            input[type="file"]::file-selector-button:hover {
                background: linear-gradient(135deg, var(--nx-a2) 0%, var(--nx-a1) 100%); transform: translateY(-1px);
            }
            </style>

            <!-- NAV SETTINGS -->
            <div class="row">
            <div class="col-md-2 nx-settings-nav">
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
                                    <?php echo form_input('currency_prefix', $settings->currency_prefix ?? 'CRC', 'class="form-control" id="currency_prefix" maxlength="3" required="required" placeholder="CRC"'); ?>
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
                                <div class="mb-3">
                                    <label for="dateformat"><i class="fa fa-calendar"></i> <?php echo lang('dateformat'); ?> <a href="http://php.net/manual/en/function.date.php" target="_blank"><i class="fa fa-external-link"></i></a></label>
                                    <?php echo form_input('dateformat', $settings->dateformat ?? 'd/m/Y', 'class="form-control" id="dateformat" required="required"'); ?>
                                </div>
                                <div class="mb-3">
                                    <label for="timeformat"><i class="fa fa-clock-o"></i> <?php echo lang('timeformat'); ?></label>
                                    <?php echo form_input('timeformat', $settings->timeformat ?? 'h:i A', 'class="form-control" id="timeformat" required="required"'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rows_per_page"><i class="fa fa-list"></i> <?php echo lang('row_per_page'); ?></label>
                                    <?php
                                    $rw = array('10' => '10', '25' => '25', '50' => '50', '100' => '100');
                                    echo form_dropdown('rows_per_page', $rw, $settings->rows_per_page ?? '25', 'class="form-control tom-select" id="rows_per_page" style="width:100%;" required="required"');
                                    ?>
                                </div>
                                <div class="mb-3">
                                    <label for="pin_code"><i class="fa fa-lock"></i> <?php echo lang('delete_code'); ?> (PIN)</label>
                                    <input type="password" name="pin_code" id="pin_code" value="<?php echo htmlspecialchars($settings->pin_code ?? ''); ?>" class="form-control" pattern="[0-9]{4,8}" placeholder="<?= lang('placeholder_pin'); ?>">
                                    <span class="help-block"><?= lang('pin_seguridad_help'); ?></span>
                                </div>
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
                                    <input type="file" name="userfile" id="logo" class="form-control" accept="image/gif,image/jpeg,image/png">
                                    <span class="help-block"><i class="fa fa-info-circle"></i> GIF/JPG/PNG, máx 300x80px, 300KB</span>
                                </div>
                                <div class="mb-3">
                                    <label><i class="fa fa-th-large"></i> <?= lang('panel_categorias_pos'); ?></label>
                                    <select name="show_categories" id="show_categories" class="form-control tom-select" style="width:100%;">
                                        <option value="1" <?= (($settings->show_categories ?? '1') == '1') ? 'selected' : ''; ?>><?= lang('mostrar_categorias'); ?></option>
                                        <option value="0" <?= (($settings->show_categories ?? '1') == '0') ? 'selected' : ''; ?>><?= lang('ocultar_categorias'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div><!-- /#tab-general -->

                    <!-- ==================== TAB 2: EMISOR FE ==================== -->
                    <div class="tab-pane" id="tab-emisor">

                        <?php if (($settings->block_hacienda ?? '0') == '1'): ?>
                        <div class="alert alert-warning" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                            <span><i class="fa fa-lock fa-lg"></i> <?= lang('config_bloqueada_msg'); ?></span>
                            <a href="<?= site_url('settings/desbloquear_hacienda') ?>"
                               class="btn btn-warning btn-sm"
                               onclick="return confirm('<?= lang(''desbloquear_confirm''); ?>')">
                                <i class="fa fa-unlock"></i> <?= lang('desbloquear'); ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <fieldset <?= (($settings->block_hacienda ?? '0') == '1') ? 'disabled' : '' ?>>

                        <!-- AMBIENTE -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-exchange"></i> <?= lang('ambiente_hacienda'); ?></div>
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
                                            <span class="label label-success" style="font-size:13px;padding:5px 10px;display:inline-block;margin-top:5px;"><i class="fa fa-check"></i> <?= lang('produccion_activa_label'); ?></span>
                                            <?php else: ?>
                                            <span class="label label-warning" style="font-size:13px;padding:5px 10px;display:inline-block;margin-top:5px;"><i class="fa fa-flask"></i> <?= lang('pruebas_activa_label'); ?></span>
                                            <?php endif; ?>
                                            <span class="help-block"><i class="fa fa-exclamation-triangle text-warning"></i> <?= lang('cambie_a_produccion'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IDENTIFICACION DEL EMISOR -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-id-card-o"></i> <?= lang('identificacion_emisor'); ?></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="tipo_doc_emisor"><i class="fa fa-id-badge"></i> <?= lang('tipo_doc_cedula'); ?></label>
                                            <?php
                                            $tipo_doc_emisor = array("01" => lang('Cedula Identidad'), "02" => lang('Cedula Juridica'), "03" => lang('Dimex'), "04" => lang('NITE'));
                                            echo form_dropdown('tipo_doc_emisor', $tipo_doc_emisor, $settings->tipo_doc_emisor ?? '02', 'class="form-control tom-select" id="tipo_doc_emisor" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="cedula_emisor"><i class="fa fa-hashtag"></i> <?= lang('cedula_documento'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->cedula_emisor ?? '') ?>" class="form-control" id="cedula_emisor" name="cedula_emisor" type="text" placeholder="3101000000">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombre_emisor"><i class="fa fa-user"></i> <?= lang('nombre_obligado'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->nombre_emisor ?? '') ?>" class="form-control" id="nombre_emisor" name="nombre_emisor" type="text">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombre_comercial"><i class="fa fa-briefcase"></i> <?= lang('nombre_comercial_fantasia'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->nombre_comercial ?? '') ?>" class="form-control" id="nombre_comercial" name="nombre_comercial" type="text">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="email_emisor"><i class="fa fa-envelope-o"></i> <?= lang('correo_electronico'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->email_emisor ?? '') ?>" class="form-control" id="email_emisor" name="email_emisor" type="email">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="cod_telefono_emisor"><i class="fa fa-flag"></i> <?= lang('cod_pais'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->cod_telefono_emisor ?? '506') ?>" class="form-control" id="cod_telefono_emisor" name="cod_telefono_emisor" type="text" placeholder="506" maxlength="3">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="telefono_emisor"><i class="fa fa-phone"></i> <?= lang('telefono_sin_guiones'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->telefono_emisor ?? '') ?>" class="form-control" id="telefono_emisor" name="telefono_emisor" type="text" placeholder="22220000">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="fax_emisor"><i class="fa fa-fax"></i> <?= lang('fax_sin_guiones'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->fax_emisor ?? '') ?>" class="form-control" id="fax_emisor" name="fax_emisor" type="text" placeholder="22220000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DIRECCION -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-map-marker"></i> <?= lang('direccion_tributario'); ?>
                                <small><a target="_blank" href="https://tribunet.hacienda.go.cr/docs/esquemas/2016/v4.2/Codificacionubicacion_V4.2.zip"><i class="fa fa-download"></i> Codigos de ubicacion</a></small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="cod_provincia"><i class="fa fa-map"></i> Provincia <small>(1-7)</small></label>
                                            <input value="<?= htmlspecialchars($settings->cod_provincia ?? '') ?>" maxlength="1" class="form-control" id="cod_provincia" name="cod_provincia" type="text" placeholder="1">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="cod_canton"><i class="fa fa-map"></i> Canton <small>(2 dig)</small></label>
                                            <input value="<?= htmlspecialchars($settings->cod_canton ?? '') ?>" maxlength="2" class="form-control" id="cod_canton" name="cod_canton" type="text" placeholder="01">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="cod_distrito"><i class="fa fa-map"></i> Distrito <small>(2 dig)</small></label>
                                            <input value="<?= htmlspecialchars($settings->cod_distrito ?? '') ?>" maxlength="2" class="form-control" id="cod_distrito" name="cod_distrito" type="text" placeholder="01">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="cod_barrio"><i class="fa fa-map"></i> Barrio <small>(2 dig)</small></label>
                                            <input value="<?= htmlspecialchars($settings->cod_barrio ?? '') ?>" maxlength="2" class="form-control" id="cod_barrio" name="cod_barrio" type="text" placeholder="01">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="otras_senas"><i class="fa fa-home"></i> <?= lang('otras_senas_label'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->otras_senas ?? '') ?>" class="form-control" id="otras_senas" name="otras_senas" type="text" placeholder="<?= lang('placeholder_dir_desc'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACTIVIDAD ECONOMICA -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-industry"></i> <?= lang('actividad_economica'); ?></div>
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOKENS API HACIENDA -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-key"></i> <?= lang('tokens_api_hacienda'); ?></div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- PRUEBAS -->
                                    <div class="col-md-12">
                                        <h4><span class="label label-warning"><i class="fa fa-flask"></i> Pruebas (Sandbox)</span></h4>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label for="user_token_test"><i class="fa fa-user-o"></i> <?= lang('usuario_prueba'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->user_token_test ?? '') ?>" class="form-control" id="user_token_test" name="user_token_test" type="text" placeholder="cpj-3-101-000000@stag.comprobanteselectronicos.go.cr">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="password_token_test"><i class="fa fa-lock"></i> <?= lang('password_prueba'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->password_token_test ?? '') ?>" class="form-control" id="password_token_test" name="password_token_test" type="password" placeholder="<?= lang('password_prueba'); ?>">
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
                                            <input value="<?= htmlspecialchars($settings->user_token_prod ?? '') ?>" class="form-control" id="user_token_prod" name="user_token_prod" type="text" placeholder="cpj-3-101-000000@prod.comprobanteselectronicos.go.cr">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="password_token_prod"><i class="fa fa-lock"></i> <?= lang('password_produccion'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->password_token_prod ?? '') ?>" class="form-control" id="password_token_prod" name="password_token_prod" type="password" placeholder="<?= lang('password_produccion'); ?>">
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
                        <div class="card">
                            <div class="card-header"><i class="fa fa-certificate"></i> <?= lang('certificado_digital'); ?></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="certificado_ced"><i class="fa fa-file-o"></i> <?= lang('nombre_certificado_label'); ?></label>
                                            <input value="<?= htmlspecialchars($settings->certificado_ced ?? '') ?>" class="form-control" id="certificado_ced" name="certificado_ced" type="text" placeholder="310100000000">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="certificado_pin"><i class="fa fa-key"></i> <?= lang('pin_certificado'); ?></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->certificado_pin ?? '') ?>" class="form-control" id="certificado_pin" name="certificado_pin" type="password" placeholder="0000">
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
                                                <input type="file" name="certificado_p12" accept=".p12" class="form-control" style="flex:1;" required>
                                                <button type="submit" class="btn btn-warning btn-sm" style="white-space:nowrap;"><i class="fa fa-upload"></i> <?= lang('subir'); ?></button>
                                            </form>
                                            <span class="help-block"><?= lang('ambiente_activo_info'); ?> <strong><?= htmlspecialchars($settings->ambiente ?? 'test') ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER FE -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-align-left"></i> <?= lang('textos_comprobantes'); ?></div>
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
                        <div class="card">
                            <div class="card-header"><i class="fa fa-database"></i> <?= lang('sincronizacion_bloqueo'); ?></div>
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
                                        <h4><i class="fa fa-lock"></i> <?= lang('bloqueo_config_hacienda'); ?></h4>
                                        <p><small><?= lang('bloqueo_advertencia'); ?></small></p>
                                        <div class="mb-3">
                                            <?php
                                            $block_opts = array(0 => lang('no_bloqueada'), 1 => lang('bloquear_configuracion'));
                                            echo form_dropdown('block_hacienda', $block_opts, $settings->block_hacienda ?? 0, 'class="form-control tom-select" id="block_hacienda" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        </fieldset>
                    </div><!-- /#tab-emisor -->

                    <!-- ==================== TAB 3: EMAIL ==================== -->
                    <div class="tab-pane" id="tab-email">
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

                        <!-- SENDMAIL -->
                        <div id="sendmail_config" style="display:none;">
                            <div class="card">
                                <div class="card-header"><i class="fa fa-terminal"></i> Configuracion Sendmail</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="mailpath"><i class="fa fa-folder-o"></i> <?php echo lang('mailpath'); ?></label>
                                                <?php echo form_input('mailpath', $settings->mailpath ?? '/usr/sbin/sendmail', 'class="form-control" id="mailpath" placeholder="/usr/sbin/sendmail"'); ?>
                                                <span class="help-block"><?= lang('ruta_sendmail'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMTP -->
                        <div id="smtp_config" style="display:none;">
                            <div class="card">
                                <div class="card-header"><i class="fa fa-envelope"></i> Configuracion SMTP</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_host"><i class="fa fa-server"></i> <?php echo lang('smtp_host'); ?></label>
                                                <?php echo form_input('smtp_host', $settings->smtp_host ?? '', 'class="form-control" id="smtp_host" placeholder="smtp.gmail.com"'); ?>
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
                                                <?php echo form_input('smtp_port', $settings->smtp_port ?? '587', 'class="form-control" id="smtp_port" placeholder="587"'); ?>
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
                        <div class="card">
                            <div class="card-header"><i class="fa fa-cog"></i> <?= lang('settings_tab_pos'); ?></div>
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
                                            <label for="auto_print"><i class="fa fa-print"></i> <?php echo lang('auto_print'); ?></label>
                                            <?php
                                            $yn2 = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('auto_print', $yn2, $settings->auto_print ?? 0, 'class="form-control tom-select" id="auto_print" required="required" style="width:100%;"');
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
                                            <?php echo form_input('pro_limit', $settings->pro_limit ?? '12', 'class="form-control" id="pro_limit" required="required"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="display_kb"><i class="fa fa-keyboard-o"></i> <?php echo lang('display_kb'); ?></label>
                                            <?php
                                            $dtime = array('1' => lang('yes'), '0' => lang('no'));
                                            echo form_dropdown('display_kb', $dtime, $settings->display_kb ?? '0', 'class="form-control tom-select" id="display_kb" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="focus_add_item"><i class="fa fa-crosshairs"></i> <?= lang('atajo_agregar_item'); ?></label>
                                            <?php echo form_input('focus_add_item', $settings->focus_add_item ?? 'ALT+I', 'class="form-control" id="focus_add_item"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="edit_last_product"><i class="fa fa-edit"></i> <?= lang('atajo_editar_ultimo'); ?></label>
                                            <?php echo form_input('edit_last_product', $settings->edit_last_product ?? 'F1', 'class="form-control" id="edit_last_product"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="add_customer"><i class="fa fa-user-plus"></i> <?= lang('atajo_agregar_cliente'); ?></label>
                                            <?php echo form_input('add_customer', $settings->add_customer ?? 'ALT+C', 'class="form-control" id="add_customer"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="toggle_category_slider"><i class="fa fa-bars"></i> <?= lang('atajo_alternar_cats'); ?></label>
                                            <?php echo form_input('toggle_category_slider', $settings->toggle_category_slider ?? 'ALT+C', 'class="form-control" id="toggle_category_slider"'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BOTONES VISIBLES EN POS -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-th-list"></i> <?= lang('sec_atajos_pos'); ?></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cancel_sale"><i class="fa fa-times-circle"></i> <?= lang('atajo_cancelar_venta'); ?></label>
                                            <?php echo form_input('cancel_sale', $settings->cancel_sale ?? 'F9', 'class="form-control" id="cancel_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="suspend_sale"><i class="fa fa-pause-circle"></i> <?= lang('atajo_suspender_venta'); ?></label>
                                            <?php echo form_input('suspend_sale', $settings->suspend_sale ?? 'F5', 'class="form-control" id="suspend_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="print_order"><i class="fa fa-print"></i> <?= lang('atajo_imprimir_orden'); ?></label>
                                            <?php echo form_input('print_order', $settings->print_order ?? '', 'class="form-control" id="print_order"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="print_bill"><i class="fa fa-file-text-o"></i> <?= lang('atajo_imprimir_factura'); ?></label>
                                            <?php echo form_input('print_bill', $settings->print_bill ?? '', 'class="form-control" id="print_bill"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="finalize_sale"><i class="fa fa-check-circle"></i> <?= lang('atajo_finalizar_venta'); ?></label>
                                            <?php echo form_input('finalize_sale', $settings->finalize_sale ?? 'F12', 'class="form-control" id="finalize_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="today_sale"><i class="fa fa-calendar-check-o"></i> <?= lang('atajo_ventas_hoy'); ?></label>
                                            <?php echo form_input('today_sale', $settings->today_sale ?? 'ALT+V', 'class="form-control" id="today_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="open_hold_bills"><i class="fa fa-folder-open-o"></i> <?= lang('atajo_retomar'); ?></label>
                                            <?php echo form_input('open_hold_bills', $settings->open_hold_bills ?? '', 'class="form-control" id="open_hold_bills"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="close_register"><i class="fa fa-sign-out"></i> <?= lang('atajo_cerrar_caja'); ?></label>
                                            <?php echo form_input('close_register', $settings->close_register ?? 'ALT+R', 'class="form-control" id="close_register"'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- INVENTARIO -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-cubes"></i> <?= lang('inventory_label'); ?></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="overselling"><i class="fa fa-exclamation-triangle"></i> <?php echo lang('overselling'); ?></label>
                                            <?php
                                            $enodis = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('overselling', $enodis, $settings->overselling ?? 0, 'class="form-control tom-select" id="overselling" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_fractions"><i class="fa fa-percent"></i> <?= lang('ventas_fracciones'); ?></label>
                                            <?php
                                            $frac = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_fractions', $frac, $settings->enable_fractions ?? '0', 'class="form-control tom-select" id="enable_fractions" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_credit"><i class="fa fa-credit-card"></i> <?php echo lang('question_enable_credit'); ?></label>
                                            <?php
                                            $cr_opts = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('enable_credit', $cr_opts, $settings->enable_credit ?? 0, 'class="form-control tom-select" id="enable_credit" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_fastedition"><i class="fa fa-pencil-square-o"></i> <?= lang('edicion_rapida_prod'); ?></label>
                                            <?php
                                            $fe_opts = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_fastedition', $fe_opts, $settings->enable_fastedition ?? '0', 'class="form-control tom-select" id="enable_fastedition" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IMPRESION -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-print"></i> <?= lang('sec_impresion'); ?></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="receipt_printer"><i class="fa fa-print"></i> <?php echo lang('receipt_printer'); ?></label>
                                            <?php
                                            $printer_opts = array('' => lang('none'));
                                            if (!empty($printers)) {
                                                foreach ($printers as $printer) {
                                                    $printer_opts[$printer->id] = $printer->title;
                                                }
                                            }
                                            echo form_dropdown('receipt_printer', $printer_opts, $settings->printer ?? '', 'class="form-control tom-select" id="receipt_printer" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="ip_printer"><i class="fa fa-wifi"></i> <?= lang('ip_impresora'); ?></label>
                                            <?php echo form_input('ip_printer', $settings->ip_printer ?? '', 'class="form-control" id="ip_printer" placeholder="127.0.0.1"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombrecompartido"><i class="fa fa-share-alt"></i> <?= lang('nombre_compartido_imp'); ?></label>
                                            <?php echo form_input('nombrecompartido', $settings->nombrecompartido ?? '', 'class="form-control" id="nombrecompartido" placeholder="epsontm-t20"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="cash_drawer_codes"><i class="fa fa-money"></i> <?php echo lang('cash_drawer_codes'); ?></label>
                                            <?php echo form_input('cash_drawer_codes', $settings->cash_drawer_codes ?? '', 'class="form-control" id="cash_drawer_codes" placeholder="\x1C"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="prt_invo_after"><i class="fa fa-file-text"></i> <?php echo lang('question_print_inoice'); ?></label>
                                            <?php
                                            $prtopt = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('prt_invo_after', $prtopt, $settings->prt_invo_after ?? 0, 'class="form-control tom-select" id="prt_invo_after" style="width:100%;" required="required"');
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
                        <div class="card">
                            <div class="card-header"><i class="fa fa-bookmark"></i> <?= lang('sec_apartados'); ?></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_layaway"><i class="fa fa-bookmark-o"></i> <?= lang('apartados'); ?></label>
                                            <?php
                                            $layw = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_layaway', $layw, $settings->enable_layaway ?? '0', 'class="form-control tom-select" id="enable_layaway" style="width:100%;" required="required"');
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
                                            <label for="enable_quote"><i class="fa fa-file-o"></i> <?= lang('cotizaciones'); ?></label>
                                            <?php
                                            $qt_opts = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_quote', $qt_opts, $settings->enable_quote ?? '0', 'class="form-control tom-select" id="enable_quote" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="is_shipping"><i class="fa fa-truck"></i> <?= lang('metodo_envio'); ?></label>
                                            <?php
                                            $ship_opts = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('is_shipping', $ship_opts, $settings->is_shipping ?? '0', 'class="form-control tom-select" id="is_shipping" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NUMERALES / DECIMALES -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-hashtag"></i> <?= lang('sec_numerales_moneda'); ?></div>
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
                                            <label for="sac"><i class="fa fa-exchange"></i> <?php echo lang('sac'); ?></label>
                                            <?php
                                            $sac_opts = array('0' => lang('disable'), '1' => lang('enable'));
                                            echo form_dropdown('sac', $sac_opts, set_value('sac', $settings->sac ?? '0'), 'class="form-control tom-select" id="sac" required="required"');
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
                                            <?php echo form_input('symbol', $settings->symbol ?? '₡', 'class="form-control" id="symbol"'); ?>
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
                        <div class="card">
                            <div class="card-header"><i class="fa fa-search"></i> <?= lang('sec_busqueda_prod'); ?></div>
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
                        <div class="card">
                            <div class="card-header"><i class="fa fa-tags"></i> <?= lang('sec_cats_clientes'); ?></div>
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
                        <div class="card">
                            <div class="card-header"><i class="fa fa-cash-register"></i> <?= lang('sec_registro_caja'); ?></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_detail_register"><i class="fa fa-list-alt"></i> <?= lang('detalles_cierre_caja'); ?></label>
                                            <?php
                                            $dreg = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_detail_register', $dreg, $settings->enable_detail_register ?? '0', 'class="form-control tom-select" id="enable_detail_register" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_detail_caschier"><i class="fa fa-user-circle-o"></i> <?= lang('detalles_cajero'); ?></label>
                                            <?php
                                            $dcash = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_detail_caschier', $dcash, $settings->enable_detail_caschier ?? '0', 'class="form-control tom-select" id="enable_detail_caschier" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_auth_open"><i class="fa fa-key"></i> <?= lang('cierre_unico'); ?></label>
                                            <?php
                                            $authop = array('1' => lang('habilitada'), '0' => lang('deshabilitada'));
                                            echo form_dropdown('enable_auth_open', $authop, $settings->enable_auth_open ?? '0', 'class="form-control tom-select" id="enable_auth_open" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IMPUESTO Y OTROS -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-percent"></i> <?= lang('sec_impuesto_propina'); ?></div>
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
                                            <label for="propina_enable"><i class="fa fa-thumbs-up"></i> <?= lang('propina'); ?></label>
                                            <?php
                                            $prop = array('0' => lang('deshabilitada'), '1' => lang('habilitada'));
                                            echo form_dropdown('propina_enable', $prop, $settings->propina_enable ?? '0', 'class="form-control tom-select" id="propina_enable" style="width:100%;"');
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
            <div class="box-footer" style="background:var(--nx-card-bg);border-top:2px solid var(--nx-border);padding:15px 20px;position:sticky;bottom:0;z-index:100;box-shadow:0 -4px 16px rgba(0,0,0,.3);">
                <button type="submit" name="update" class="btn btn-primary btn-lg">
                    <i class="fa fa-save"></i> <?= lang('guardar_configuracion'); ?>
                </button>
                <a href="<?= site_url('settings') ?>" class="btn btn-default btn-lg" style="margin-left:10px;">
                    <i class="fa fa-undo"></i> <?= lang('cancel'); ?>
                </a>
            </div>

            <?php echo form_close(); ?>

        </div>
    </div>
</section>

<script type="text/javascript">
$(document).ready(function () {

    // Inicializar order_printers
    var orderPrintersVal = <?php echo (!empty($settings->order_printers) ? $settings->order_printers : '[]'); ?>;
    if (orderPrintersVal && orderPrintersVal.length > 0) {
        $("#order_printers")new TomSelect(this).val(orderPrintersVal).trigger("change");
    }

    // Toggle protocolo email
    function toggleEmailProtocol() {
        var proto = $('#protocol').val();
        $('#smtp_config').hide();
        $('#sendmail_config').hide();
        if (proto === 'smtp') {
            $('#smtp_config').slideDown();
        } else if (proto === 'sendmail') {
            $('#sendmail_config').slideDown();
        }
    }
    toggleEmailProtocol();
    $('#protocol').change(toggleEmailProtocol);

    // Toggle ver/ocultar password
    $(document).on('click', '.btn-toggle-pw', function () {
        var targetId = $(this).data('target');
        var $field = $('#' + targetId);
        if ($field.attr('type') === 'password') {
            $field.attr('type', 'text');
            $(this).html('<i class="fa fa-eye-slash"></i>');
        } else {
            $field.attr('type', 'password');
            $(this).html('<i class="fa fa-eye"></i>');
        }
    });

    // Reactivar tab activa despues de envio (por hash en URL)
    var activeTab = localStorage.getItem('settings_active_tab');
    if (activeTab) {
        $('a[href="' + activeTab + '"]').tab('show');
    }
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('settings_active_tab', $(e.target).attr('href'));
    });

    // Probar credenciales Hacienda
    var urlComprueba = "<?= base_url() ?>settings/compruebausers";

    $('#comprueba_test').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Probando...');
        $.post(urlComprueba, {
            user:     $('#user_token_test').val(),
            password: $('#password_token_test').val(),
            ambiente: "test",
            <?= $this->security->get_csrf_token_name(); ?>: "<?= $this->security->get_csrf_hash(); ?>"
        }).done(function (data) {
            alert(data);
        }).fail(function () {
            alert('Error de conexion al servidor de Hacienda.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> <?= lang('probar_cred_prueba'); ?>');
        });
    });

    $('#comprueba_prod').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Probando...');
        $.post(urlComprueba, {
            user:     $('#user_token_prod').val(),
            password: $('#password_token_prod').val(),
            ambiente: "prod",
            <?= $this->security->get_csrf_token_name(); ?>: "<?= $this->security->get_csrf_hash(); ?>"
        }).done(function (data) {
            alert(data);
        }).fail(function () {
            alert('Error de conexion al servidor de Hacienda.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> <?= lang('probar_cred_prod'); ?>');
        });
    });

    // Limpiar cache CABYS
    $('#btn-limpiar-cabys').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Limpiando...');
        $.post('<?= site_url("hacienda_proxy/limpiar_cache_cabys") ?>', {
            <?= $this->security->get_csrf_token_name(); ?>: "<?= $this->security->get_csrf_hash(); ?>"
        }).done(function (data) {
            $('#cabys-sync-result').html('<span class="text-success"><i class="fa fa-check"></i> Cache limpiado (' + (data.eliminados || 0) + ' registros)</span>').show();
        }).fail(function () {
            $('#cabys-sync-result').html('<span class="text-danger"><i class="fa fa-times"></i> Error al limpiar cache</span>').show();
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> <?= lang('limpiar_cache_cabys'); ?>');
        });
    });

});
</script>
