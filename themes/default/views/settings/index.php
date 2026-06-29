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
                            <span class="nx-nav-label">General<span class="nx-nav-sub">Negocio, tema, PIN</span></span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-emisor" data-bs-toggle="pill">
                            <i class="fa fa-file-text-o"></i>
                            <span class="nx-nav-label">Emisor FE<span class="nx-nav-sub">Hacienda, tokens, cert.</span></span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-email" data-bs-toggle="pill">
                            <i class="fa fa-envelope"></i>
                            <span class="nx-nav-label">Email<span class="nx-nav-sub">SMTP, protocolo</span></span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-pos" data-bs-toggle="pill">
                            <i class="fa fa-shopping-cart"></i>
                            <span class="nx-nav-label">POS / Caja<span class="nx-nav-sub">Impresión, botones</span></span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-avanzado" data-bs-toggle="pill">
                            <i class="fa fa-wrench"></i>
                            <span class="nx-nav-label">Avanzado<span class="nx-nav-sub">Búsqueda, categ., más</span></span>
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
                                    echo form_dropdown('language', $available_langs, $settings->language ?? 'spanish', 'class="form-control select2" id="language" required="required" style="width:100%;"');
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
                                    echo form_dropdown('rows_per_page', $rw, $settings->rows_per_page ?? '25', 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"');
                                    ?>
                                </div>
                                <div class="mb-3">
                                    <label for="pin_code"><i class="fa fa-lock"></i> <?php echo lang('delete_code'); ?> (PIN)</label>
                                    <input type="password" name="pin_code" id="pin_code" value="<?php echo htmlspecialchars($settings->pin_code ?? ''); ?>" class="form-control" pattern="[0-9]{4,8}" placeholder="4-8 dígitos numéricos">
                                    <span class="help-block">PIN numérico de 4 a 8 dígitos para acciones sensibles</span>
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
                                    echo form_dropdown('theme_style', $ths, $settings->theme_style ?? 'black', 'class="form-control select2" id="theme_style" required="required" style="width:100%;"');
                                    ?>
                                </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label><i class="fa fa-image"></i> <?php echo lang('login_logo'); ?></label>
                                    <input type="file" name="userfile" id="logo" class="form-control" accept="image/gif,image/jpeg,image/png">
                                    <span class="help-block"><i class="fa fa-info-circle"></i> GIF/JPG/PNG, máx 300x80px, 300KB</span>
                                </div>
                                <div class="mb-3">
                                    <label><i class="fa fa-th-large"></i> Panel de categorías en POS</label>
                                    <select name="show_categories" id="show_categories" class="form-control select2" style="width:100%;">
                                        <option value="1" <?= (($settings->show_categories ?? '1') == '1') ? 'selected' : ''; ?>>Mostrar categorías</option>
                                        <option value="0" <?= (($settings->show_categories ?? '1') == '0') ? 'selected' : ''; ?>>Ocultar categorías</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div><!-- /#tab-general -->

                    <!-- ==================== TAB 2: EMISOR FE ==================== -->
                    <div class="tab-pane" id="tab-emisor">

                        <?php if (($settings->block_hacienda ?? '0') == '1'): ?>
                        <div class="alert alert-warning" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                            <span><i class="fa fa-lock fa-lg"></i> <strong>Configuracion Hacienda bloqueada.</strong> Los datos del emisor son de solo lectura.</span>
                            <a href="<?= site_url('settings/desbloquear_hacienda') ?>"
                               class="btn btn-warning btn-sm"
                               onclick="return confirm('¿Seguro que desea desbloquear la configuracion de Hacienda?')">
                                <i class="fa fa-unlock"></i> Desbloquear
                            </a>
                        </div>
                        <?php endif; ?>

                        <fieldset <?= (($settings->block_hacienda ?? '0') == '1') ? 'disabled' : '' ?>>

                        <!-- AMBIENTE -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-exchange"></i> Ambiente Hacienda</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="ambiente"><i class="fa fa-server"></i> Ambiente activo</label>
                                            <?php
                                            $amb_opts = ['test' => 'Pruebas (Sandbox)', 'prod' => 'Produccion'];
                                            $amb_actual = $settings->ambiente ?? 'test';
                                            echo form_dropdown('ambiente', $amb_opts, $amb_actual, 'class="form-control select2" id="ambiente" style="width:100%;"');
                                            ?>
                                            <?php if ($amb_actual == 'prod'): ?>
                                            <span class="label label-success" style="font-size:13px;padding:5px 10px;display:inline-block;margin-top:5px;"><i class="fa fa-check"></i> PRODUCCION activa</span>
                                            <?php else: ?>
                                            <span class="label label-warning" style="font-size:13px;padding:5px 10px;display:inline-block;margin-top:5px;"><i class="fa fa-flask"></i> PRUEBAS activa</span>
                                            <?php endif; ?>
                                            <span class="help-block"><i class="fa fa-exclamation-triangle text-warning"></i> Cambie a <b>Produccion</b> solo con credenciales y certificado de produccion confirmados.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IDENTIFICACION DEL EMISOR -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-id-card-o"></i> Identificacion del Emisor</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="tipo_doc_emisor"><i class="fa fa-id-badge"></i> Tipo de Cedula / Documento</label>
                                            <?php
                                            $tipo_doc_emisor = array("01" => "Cedula de Identidad", "02" => "Cedula Juridica", "03" => "DIMEX", "04" => "NITE");
                                            echo form_dropdown('tipo_doc_emisor', $tipo_doc_emisor, $settings->tipo_doc_emisor ?? '02', 'class="form-control select2" id="tipo_doc_emisor" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="cedula_emisor"><i class="fa fa-hashtag"></i> Cedula / N° Documento</label>
                                            <input value="<?= htmlspecialchars($settings->cedula_emisor ?? '') ?>" class="form-control" id="cedula_emisor" name="cedula_emisor" type="text" placeholder="3101000000">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombre_emisor"><i class="fa fa-user"></i> Nombre del Obligado Tributario</label>
                                            <input value="<?= htmlspecialchars($settings->nombre_emisor ?? '') ?>" class="form-control" id="nombre_emisor" name="nombre_emisor" type="text">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombre_comercial"><i class="fa fa-briefcase"></i> Nombre Comercial / Fantasia</label>
                                            <input value="<?= htmlspecialchars($settings->nombre_comercial ?? '') ?>" class="form-control" id="nombre_comercial" name="nombre_comercial" type="text">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="email_emisor"><i class="fa fa-envelope-o"></i> Correo Electronico</label>
                                            <input value="<?= htmlspecialchars($settings->email_emisor ?? '') ?>" class="form-control" id="email_emisor" name="email_emisor" type="email">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="cod_telefono_emisor"><i class="fa fa-flag"></i> Cod. Pais</label>
                                            <input value="<?= htmlspecialchars($settings->cod_telefono_emisor ?? '506') ?>" class="form-control" id="cod_telefono_emisor" name="cod_telefono_emisor" type="text" placeholder="506" maxlength="3">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="telefono_emisor"><i class="fa fa-phone"></i> Telefono <small>(sin guiones)</small></label>
                                            <input value="<?= htmlspecialchars($settings->telefono_emisor ?? '') ?>" class="form-control" id="telefono_emisor" name="telefono_emisor" type="text" placeholder="22220000">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="fax_emisor"><i class="fa fa-fax"></i> Fax <small>(sin guiones)</small></label>
                                            <input value="<?= htmlspecialchars($settings->fax_emisor ?? '') ?>" class="form-control" id="fax_emisor" name="fax_emisor" type="text" placeholder="22220000">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DIRECCION -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-map-marker"></i> Direccion del Tributario
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
                                            <label for="otras_senas"><i class="fa fa-home"></i> Otras Senas</label>
                                            <input value="<?= htmlspecialchars($settings->otras_senas ?? '') ?>" class="form-control" id="otras_senas" name="otras_senas" type="text" placeholder="Descripcion de la direccion">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACTIVIDAD ECONOMICA -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-industry"></i> Actividad Economica</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="default_actividad"><i class="fa fa-list-alt"></i> Actividad Predeterminada</label>
                                            <?php
                                            $act_opts = array();
                                            foreach ($actividadeconomica as $actividad) {
                                                $act_opts[$actividad->id_actividad] = $actividad->id_actividad . ' - ' . $actividad->descripcion;
                                            }
                                            echo form_dropdown('default_actividad', $act_opts, $settings->default_actividad ?? '', 'class="form-control select2" style="width:100%;" id="default_actividad" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOKENS API HACIENDA -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-key"></i> Tokens API Hacienda</div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- PRUEBAS -->
                                    <div class="col-md-12">
                                        <h4><span class="label label-warning"><i class="fa fa-flask"></i> Pruebas (Sandbox)</span></h4>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label for="user_token_test"><i class="fa fa-user-o"></i> Usuario Prueba</label>
                                            <input value="<?= htmlspecialchars($settings->user_token_test ?? '') ?>" class="form-control" id="user_token_test" name="user_token_test" type="text" placeholder="cpj-3-101-000000@stag.comprobanteselectronicos.go.cr">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="password_token_test"><i class="fa fa-lock"></i> Password Prueba</label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->password_token_test ?? '') ?>" class="form-control" id="password_token_test" name="password_token_test" type="password" placeholder="Contrasena de prueba">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="password_token_test" title="Ver/ocultar"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label><br>
                                            <span id="comprueba_test" class="btn btn-success btn-block"><i class="fa fa-check-circle"></i> Probar credenciales prueba</span>
                                        </div>
                                    </div>

                                    <!-- PRODUCCION -->
                                    <div class="col-md-12">
                                        <h4><span class="label label-success"><i class="fa fa-check"></i> Produccion</span></h4>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label for="user_token_prod"><i class="fa fa-user-o"></i> Usuario Produccion</label>
                                            <input value="<?= htmlspecialchars($settings->user_token_prod ?? '') ?>" class="form-control" id="user_token_prod" name="user_token_prod" type="text" placeholder="cpj-3-101-000000@prod.comprobanteselectronicos.go.cr">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="password_token_prod"><i class="fa fa-lock"></i> Password Produccion</label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->password_token_prod ?? '') ?>" class="form-control" id="password_token_prod" name="password_token_prod" type="password" placeholder="Contrasena de produccion">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="password_token_prod" title="Ver/ocultar"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label>&nbsp;</label><br>
                                            <span id="comprueba_prod" class="btn btn-success btn-block"><i class="fa fa-check-circle"></i> Probar credenciales produccion</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CERTIFICADO DIGITAL -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-certificate"></i> Certificado Digital (.p12)</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="certificado_ced"><i class="fa fa-file-o"></i> Nombre del Certificado <small>(sin la extension .p12)</small></label>
                                            <input value="<?= htmlspecialchars($settings->certificado_ced ?? '') ?>" class="form-control" id="certificado_ced" name="certificado_ced" type="text" placeholder="310100000000">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="certificado_pin"><i class="fa fa-key"></i> PIN del Certificado <small>(longitud 4)</small></label>
                                            <div class="input-group">
                                                <input value="<?= htmlspecialchars($settings->certificado_pin ?? '') ?>" class="form-control" id="certificado_pin" name="certificado_pin" type="password" placeholder="0000">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-default btn-toggle-pw" data-target="certificado_pin" title="Ver/ocultar"><i class="fa fa-eye"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label><i class="fa fa-upload"></i> Subir Certificado .p12 <small>(Sobrescribe el actual)</small></label>
                                            <?php
                                            $certFile = FCPATH . 'files/certificados/' . ($settings->ambiente ?? 'test') . '/' . ($settings->certificado_ced ?? '') . '.p12';
                                            $certExists = !empty($settings->certificado_ced) && file_exists($certFile);
                                            ?>
                                            <?php if ($certExists): ?>
                                                <p class="text-success" style="margin:0 0 4px;"><i class="fa fa-check-circle"></i> Certificado cargado: <strong><?= htmlspecialchars($settings->certificado_ced) ?>.p12</strong></p>
                                            <?php else: ?>
                                                <p class="text-warning" style="margin:0 0 4px;"><i class="fa fa-exclamation-triangle"></i> No hay certificado en el servidor.</p>
                                            <?php endif; ?>
                                            <form action="<?= site_url('settings/upload_certificado') ?>" method="post" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center;">
                                                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                                                <input type="file" name="certificado_p12" accept=".p12" class="form-control" style="flex:1;" required>
                                                <button type="submit" class="btn btn-warning btn-sm" style="white-space:nowrap;"><i class="fa fa-upload"></i> Subir</button>
                                            </form>
                                            <span class="help-block">Ambiente activo: <strong><?= htmlspecialchars($settings->ambiente ?? 'test') ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER FE -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-align-left"></i> Textos en comprobantes electronicos</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="footer_hacienda_fe"><i class="fa fa-file-text"></i> Footer Factura Electronica</label>
                                            <textarea name="footer_hacienda_fe" id="footer_hacienda_fe" class="form-control" rows="3" placeholder="Texto al pie de las facturas electronicas"><?= htmlspecialchars($settings->footer_hacienda_fe ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="footer_hacienda_nc"><i class="fa fa-file-text-o"></i> Footer Nota de Credito</label>
                                            <textarea name="footer_hacienda_nc" id="footer_hacienda_nc" class="form-control" rows="3" placeholder="Texto al pie de las notas de credito"><?= htmlspecialchars($settings->footer_hacienda_nc ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BLOQUEO / SINCRONIZACION CABYS -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-database"></i> Sincronizacion y Bloqueo</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4><i class="fa fa-refresh"></i> Catalogo CABYS</h4>
                                        <p>Si Hacienda publico una actualizacion del catalogo CABYS, limpie el cache local para consultar las versiones mas recientes.</p>
                                        <button type="button" id="btn-limpiar-cabys" class="btn btn-warning">
                                            <i class="fa fa-refresh"></i> Limpiar cache CABYS
                                        </button>
                                        <span id="cabys-sync-result" style="margin-left:10px;display:none;"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <h4><i class="fa fa-lock"></i> Bloqueo de Configuracion Hacienda</h4>
                                        <p><small>Si ya probó la configuracion y esta 100% seguro de que todo funciona, bloquee para evitar cambios accidentales.</small></p>
                                        <div class="mb-3">
                                            <?php
                                            $block_opts = array(0 => "No bloqueada", 1 => "Bloquear Configuracion");
                                            echo form_dropdown('block_hacienda', $block_opts, $settings->block_hacienda ?? 0, 'class="form-control select2" id="block_hacienda" style="width:100%;"');
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
                                    echo form_dropdown('protocol', $popt, $settings->protocol ?? 'mail', 'class="form-control select2" id="protocol" style="width:100%;" required="required"');
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
                                                <span class="help-block">Ruta al binario de sendmail en el servidor</span>
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
                                                <?php echo form_input('smtp_user', $settings->smtp_user ?? '', 'class="form-control" id="smtp_user" placeholder="usuario@gmail.com"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="smtp_pass"><i class="fa fa-lock"></i> <?php echo lang('smtp_pass'); ?></label>
                                                <div class="input-group">
                                                    <input type="password" name="smtp_pass" id="smtp_pass" value="<?= htmlspecialchars($settings->smtp_pass ?? '') ?>" class="form-control" placeholder="Contrasena SMTP">
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-default btn-toggle-pw" data-target="smtp_pass" title="Ver/ocultar"><i class="fa fa-eye"></i></button>
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
                                                echo form_dropdown('smtp_crypto', $crypto_opt, $settings->smtp_crypto ?? 'tls', 'class="form-control select2" id="smtp_crypto" style="width:100%;"');
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
                            <div class="card-header"><i class="fa fa-cog"></i> Comportamiento General POS</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="after_sale_page"><i class="fa fa-arrow-right"></i> <?php echo lang('after_sale_page'); ?></label>
                                            <?php
                                            $asp = array(0 => lang('receipt'), 1 => lang('pos'));
                                            echo form_dropdown('after_sale_page', $asp, $settings->after_sale_page ?? 0, 'class="form-control select2" id="after_sale_page" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="auto_print"><i class="fa fa-print"></i> <?php echo lang('auto_print'); ?></label>
                                            <?php
                                            $yn2 = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('auto_print', $yn2, $settings->auto_print ?? 0, 'class="form-control select2" id="auto_print" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="display_product"><i class="fa fa-th"></i> <?php echo lang('display_product'); ?></label>
                                            <?php
                                            $dprv = array('1' => 'Nombre', '2' => 'Foto', '3' => 'Ambos');
                                            echo form_dropdown('display_product', $dprv, $settings->bsty ?? '1', 'class="form-control select2" id="display_product" style="width:100%;" required="required"');
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
                                            echo form_dropdown('display_kb', $dtime, $settings->display_kb ?? '0', 'class="form-control select2" id="display_kb" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="focus_add_item"><i class="fa fa-crosshairs"></i> Atajo: Agregar item (focus)</label>
                                            <?php echo form_input('focus_add_item', $settings->focus_add_item ?? 'ALT+I', 'class="form-control" id="focus_add_item"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="edit_last_product"><i class="fa fa-edit"></i> Atajo: Editar ultimo producto</label>
                                            <?php echo form_input('edit_last_product', $settings->edit_last_product ?? 'F1', 'class="form-control" id="edit_last_product"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="add_customer"><i class="fa fa-user-plus"></i> Atajo: Agregar cliente</label>
                                            <?php echo form_input('add_customer', $settings->add_customer ?? 'ALT+C', 'class="form-control" id="add_customer"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="toggle_category_slider"><i class="fa fa-bars"></i> Atajo: Alternar categorias</label>
                                            <?php echo form_input('toggle_category_slider', $settings->toggle_category_slider ?? 'ALT+C', 'class="form-control" id="toggle_category_slider"'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BOTONES VISIBLES EN POS -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-th-list"></i> Atajos / Botones del POS</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cancel_sale"><i class="fa fa-times-circle"></i> Atajo: Cancelar venta</label>
                                            <?php echo form_input('cancel_sale', $settings->cancel_sale ?? 'F9', 'class="form-control" id="cancel_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="suspend_sale"><i class="fa fa-pause-circle"></i> Atajo: Suspender venta</label>
                                            <?php echo form_input('suspend_sale', $settings->suspend_sale ?? 'F5', 'class="form-control" id="suspend_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="print_order"><i class="fa fa-print"></i> Atajo: Imprimir orden</label>
                                            <?php echo form_input('print_order', $settings->print_order ?? '', 'class="form-control" id="print_order"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="print_bill"><i class="fa fa-file-text-o"></i> Atajo: Imprimir factura</label>
                                            <?php echo form_input('print_bill', $settings->print_bill ?? '', 'class="form-control" id="print_bill"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="finalize_sale"><i class="fa fa-check-circle"></i> Atajo: Finalizar venta</label>
                                            <?php echo form_input('finalize_sale', $settings->finalize_sale ?? 'F12', 'class="form-control" id="finalize_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="today_sale"><i class="fa fa-calendar-check-o"></i> Atajo: Ventas hoy</label>
                                            <?php echo form_input('today_sale', $settings->today_sale ?? 'ALT+V', 'class="form-control" id="today_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="open_hold_bills"><i class="fa fa-folder-open-o"></i> Atajo: Retomar pendientes</label>
                                            <?php echo form_input('open_hold_bills', $settings->open_hold_bills ?? '', 'class="form-control" id="open_hold_bills"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="close_register"><i class="fa fa-sign-out"></i> Atajo: Cerrar caja</label>
                                            <?php echo form_input('close_register', $settings->close_register ?? 'ALT+R', 'class="form-control" id="close_register"'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- INVENTARIO -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-cubes"></i> Inventario</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="overselling"><i class="fa fa-exclamation-triangle"></i> <?php echo lang('overselling'); ?></label>
                                            <?php
                                            $enodis = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('overselling', $enodis, $settings->overselling ?? 0, 'class="form-control select2" id="overselling" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_fractions"><i class="fa fa-percent"></i> Ventas en fracciones</label>
                                            <?php
                                            $frac = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                            echo form_dropdown('enable_fractions', $frac, $settings->enable_fractions ?? '0', 'class="form-control select2" id="enable_fractions" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_credit"><i class="fa fa-credit-card"></i> <?php echo lang('question_enable_credit'); ?></label>
                                            <?php
                                            $cr_opts = array(0 => lang('disable'), 1 => lang('enable'));
                                            echo form_dropdown('enable_credit', $cr_opts, $settings->enable_credit ?? 0, 'class="form-control select2" id="enable_credit" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_fastedition"><i class="fa fa-pencil-square-o"></i> Edicion rapida de productos</label>
                                            <?php
                                            $fe_opts = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                            echo form_dropdown('enable_fastedition', $fe_opts, $settings->enable_fastedition ?? '0', 'class="form-control select2" id="enable_fastedition" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IMPRESION -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-print"></i> Impresion</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="receipt_printer"><i class="fa fa-print"></i> <?php echo lang('receipt_printer'); ?></label>
                                            <?php
                                            $printer_opts = array('' => '-- Sin impresora --');
                                            if (!empty($printers)) {
                                                foreach ($printers as $printer) {
                                                    $printer_opts[$printer->id] = $printer->title;
                                                }
                                            }
                                            echo form_dropdown('receipt_printer', $printer_opts, $settings->printer ?? '', 'class="form-control select2" id="receipt_printer" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="ip_printer"><i class="fa fa-wifi"></i> IP de Impresora</label>
                                            <?php echo form_input('ip_printer', $settings->ip_printer ?? '', 'class="form-control" id="ip_printer" placeholder="127.0.0.1"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="nombrecompartido"><i class="fa fa-share-alt"></i> Nombre compartido de impresora</label>
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
                                            echo form_dropdown('prt_invo_after', $prtopt, $settings->prt_invo_after ?? 0, 'class="form-control select2" id="prt_invo_after" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="barcode_symbology"><i class="fa fa-barcode"></i> Simbologia de codigos de barra</label>
                                            <?php
                                            $bsyms = array('C128' => 'Code 128', 'C39' => 'Code 39', 'EAN13' => 'EAN-13', 'EAN8' => 'EAN-8', 'UPCA' => 'UPC-A', 'UPCE' => 'UPC-E');
                                            echo form_dropdown('barcode_symbology', $bsyms, $settings->barcode_symbology ?? 'C128', 'class="form-control select2" id="barcode_symbology" style="width:100%;"');
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
                                            echo form_dropdown('order_printers[]', $printer_opts2, '', 'multiple class="form-control select2" id="order_printers" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- APARTADOS Y COTIZACIONES -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-bookmark"></i> Apartados, Cotizaciones y Envios</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_layaway"><i class="fa fa-bookmark-o"></i> Apartados</label>
                                            <?php
                                            $layw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                            echo form_dropdown('enable_layaway', $layw, $settings->enable_layaway ?? '0', 'class="form-control select2" id="enable_layaway" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="footer_apartado"><i class="fa fa-align-left"></i> Footer de Apartado</label>
                                            <?php echo form_input('footer_apartado', $settings->footer_apartado ?? '', 'class="form-control" id="footer_apartado"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_quote"><i class="fa fa-file-o"></i> Cotizaciones / Proformas</label>
                                            <?php
                                            $qt_opts = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                            echo form_dropdown('enable_quote', $qt_opts, $settings->enable_quote ?? '0', 'class="form-control select2" id="enable_quote" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="is_shipping"><i class="fa fa-truck"></i> Metodo de Envio</label>
                                            <?php
                                            $ship_opts = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                            echo form_dropdown('is_shipping', $ship_opts, $settings->is_shipping ?? '0', 'class="form-control select2" id="is_shipping" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NUMERALES / DECIMALES -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-hashtag"></i> Numerales y Formato de Moneda</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="decimals"><i class="fa fa-calculator"></i> <?php echo lang('decimals'); ?></label>
                                            <?php
                                            $dec_opts = array(0 => lang('disable'), 1 => '1', 2 => '2', 3 => '3', 4 => '4');
                                            echo form_dropdown('decimals', $dec_opts, $settings->decimals ?? 0, 'class="form-control select2" id="decimals" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="qty_decimals"><i class="fa fa-sort-numeric-asc"></i> <?php echo lang('qty_decimals'); ?></label>
                                            <?php
                                            $qdec = array(0 => lang('disable'), 1 => '1', 2 => '2');
                                            echo form_dropdown('qty_decimals', $qdec, $settings->qty_decimals ?? 0, 'class="form-control select2" id="qty_decimals" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="decimals_sep"><i class="fa fa-minus"></i> <?php echo lang('decimals_sep'); ?></label>
                                            <?php
                                            $dec_point = array('.' => lang('dot'), ',' => lang('comma'));
                                            echo form_dropdown('decimals_sep', $dec_point, $settings->decimals_sep ?? '.', 'class="form-control select2" id="decimals_sep" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="thousands_sep"><i class="fa fa-ellipsis-h"></i> <?php echo lang('thousands_sep'); ?></label>
                                            <?php
                                            $th_sep = array('.' => lang('dot'), ',' => lang('comma'), '0' => lang('space'));
                                            echo form_dropdown('thousands_sep', $th_sep, $settings->thousands_sep ?? ',', 'class="form-control select2" id="thousands_sep" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="sac"><i class="fa fa-exchange"></i> <?php echo lang('sac'); ?></label>
                                            <?php
                                            $sac_opts = array('0' => lang('disable'), '1' => lang('enable'));
                                            echo form_dropdown('sac', $sac_opts, set_value('sac', $settings->sac ?? '0'), 'class="form-control select2" id="sac" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="display_symbol"><i class="fa fa-dollar"></i> <?php echo lang('display_currency_symbol'); ?></label>
                                            <?php
                                            $ds_opts = array(0 => lang('disable'), 1 => lang('before'), 2 => lang('after'));
                                            echo form_dropdown('display_symbol', $ds_opts, $settings->display_symbol ?? 0, 'class="form-control select2" id="display_symbol" style="width:100%;" required="required"');
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
                                            echo form_dropdown('rounding', $rnd, $settings->rounding ?? '0', 'class="form-control select2" id="rounding" required="required" style="width:100%;"');
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
                            <div class="card-header"><i class="fa fa-search"></i> Busqueda de Productos</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="sensibility_search"><i class="fa fa-sliders"></i> <?php echo lang('search_sensibility'); ?></label>
                                            <?php
                                            $sensibility = array(0 => lang('0_search'), 1 => lang('1_search'), 2 => lang('2_search'), 3 => lang('3_search'));
                                            echo form_dropdown('sensibility_search', $sensibility, $settings->sensibility_search ?? 0, 'class="form-control select2" id="sensibility_search" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="quantity_suggest"><i class="fa fa-list-ol"></i> Sugerencias a mostrar</label>
                                            <?php
                                            $qsug = array('10' => '10', '25' => '25', '50' => '50', '100' => '100');
                                            echo form_dropdown('quantity_suggest', $qsug, $settings->quantity_suggest ?? '10', 'class="form-control select2" id="quantity_suggest" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="item_addition"><i class="fa fa-plus-circle"></i> <?php echo lang('item_addition'); ?></label>
                                            <?php
                                            $ia = array(0 => lang('add_new_item'), 1 => lang('increase_quantity_if_item_exist'));
                                            echo form_dropdown('item_addition', $ia, $settings->item_addition ?? 0, 'id="item_addition" class="form-control select2" required="required" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CATEGORIAS Y CLIENTES -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-tags"></i> Categorias y Clientes por Defecto</div>
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
                                            echo form_dropdown('default_category', $ct, $settings->default_category ?? 0, 'class="form-control select2" style="width:100%;" id="default_category"');
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
                                            echo form_dropdown('default_customer', $cu, $settings->default_customer ?? '', 'class="form-control select2" style="width:100%;" id="default_customer" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- REGISTRO Y CAJA -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-cash-register"></i> Registro y Cierre de Caja</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_detail_register"><i class="fa fa-list-alt"></i> Detalles del cierre de caja</label>
                                            <?php
                                            $dreg = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                            echo form_dropdown('enable_detail_register', $dreg, $settings->enable_detail_register ?? '0', 'class="form-control select2" id="enable_detail_register" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_detail_caschier"><i class="fa fa-user-circle-o"></i> Detalles al cajero</label>
                                            <?php
                                            $dcash = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                            echo form_dropdown('enable_detail_caschier', $dcash, $settings->enable_detail_caschier ?? '0', 'class="form-control select2" id="enable_detail_caschier" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_auth_open"><i class="fa fa-key"></i> Cierre unico de caja</label>
                                            <?php
                                            $authop = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                            echo form_dropdown('enable_auth_open', $authop, $settings->enable_auth_open ?? '0', 'class="form-control select2" id="enable_auth_open" style="width:100%;" required="required"');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IMPUESTO Y OTROS -->
                        <div class="card">
                            <div class="card-header"><i class="fa fa-percent"></i> Impuesto y Propina</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="enable_show_tax"><i class="fa fa-percent"></i> Mostrar impuesto en recibo como</label>
                                            <?php
                                            $stax = array('' => 'No mostrar', 'IVI' => 'IVI', 'IVA' => 'IVA', 'Impuesto' => 'Impuesto');
                                            echo form_dropdown('enable_show_tax', $stax, $settings->enable_show_tax ?? '', 'class="form-control select2" id="enable_show_tax" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="propina_enable"><i class="fa fa-thumbs-up"></i> Propina</label>
                                            <?php
                                            $prop = array('0' => 'Deshabilitada', '1' => 'Habilitada');
                                            echo form_dropdown('propina_enable', $prop, $settings->propina_enable ?? '0', 'class="form-control select2" id="propina_enable" style="width:100%;"');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="propina_rate"><i class="fa fa-percent"></i> Tasa de Propina (%)</label>
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
                    <i class="fa fa-save"></i> Guardar configuracion
                </button>
                <a href="<?= site_url('settings') ?>" class="btn btn-default btn-lg" style="margin-left:10px;">
                    <i class="fa fa-undo"></i> Cancelar
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
        $("#order_printers").select2().val(orderPrintersVal).trigger("change");
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
            $btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> Probar credenciales prueba');
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
            $btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> Probar credenciales produccion');
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
            $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Limpiar cache CABYS');
        });
    });

});
</script>
