<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?php echo lang('update_info'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="col-lg-12">
                        <?php echo form_open_multipart("settings", 'class="validation"'); ?>
                        <div class="row">
                            <div class="col-md-6">
                                
                                <div class="form-group">
                                    <?php echo lang("site_name", 'site_name'); ?>
                                    <?php echo form_input('site_name', $settings->site_name, 'class="form-control" id="site_name" required="required"'); ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang("tel", 'tel'); ?>
                                    <?php echo form_input('tel', $settings->tel, 'class="form-control" id="tel" required="required"'); ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang('language', 'language'); ?>
                                    <?php
                                    $available_langs = array(
                                        'spanish' => 'Español',
                                        'chinese' => 'Chino (Simplificado)',
                                        'english' => 'English'
                                    );
                                    ?>
                                    <?php echo form_dropdown('language', $available_langs, $settings->language, 'class="form-control tip select2" id="language"  required="required" style="width:100%;"'); ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang('theme', 'theme'); ?>
                                    <?php
                                    $th = array(
                                        'default' => 'POS con categorias',
                                        'ThemeChineses' => 'POS sin categorias'
                                    );
                                    ?>
                                    <?php echo form_dropdown('theme', $th, $settings->theme, 'class="form-control tip select2" id="theme"  required="required" style="width:100%;"'); ?>
                                </div>
                                <? if($settings->theme_style!='purple' and $settings->theme_style!='green'){ ?>
                                <div class="form-group">
                                    <?php echo lang('theme_style', 'theme_style'); ?>
                                    <?php
                                    $ths = array(
                                        'black' => 'Black',
                                        'black-light' => 'Black Light',
                                        'blue' => 'Blue',
                                        'blue-light' => 'Blue Light',
                                        'green-light' => 'Green Light',
                                        'purple-light' => 'Purple Light',
                                        'red' => 'Red',
                                        'red-light' => 'Red Light',
                                        'yellow' => 'Yellow',
                                        'yellow-light' => 'Yellow Light',
                                        'green' => 'FacturaExpert',
                                        'purple' => 'Gi3-SoftSolutions',
                                    );
                                    ?>
                                    <?php echo form_dropdown('theme_style', $ths, $settings->theme_style, 'class="form-control tip select2" id="theme_style"  required="required" style="width:100%;"'); ?>
                                </div>
                                <? } ?>
                                <div class="form-group">
                                    <?php echo lang("overselling", 'overselling'); ?>
                                    <?php $asp = array(0 => lang('disable'), 1 => lang('enable')); ?>
                                    <?php echo form_dropdown('overselling', $asp, $settings->overselling, 'class="form-control select2" id="overselling" required="required" style="width:100%;"'); ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang("multi_store", 'multi_store'); ?>
                                    <?php $asp = array(0 => lang('disable'), 1 => lang('enable')); ?>
                                    <?php echo form_dropdown('multi_store', $asp, $settings->multi_store, 'class="form-control select2" id="multi_store" required="required" style="width:100%;"'); ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang("currency_code", 'currency_code'); ?>
                                    <?php echo form_input('currency_prefix', $settings->currency_prefix, 'class="form-control" id="currency_code" required="required"'); ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang("auto_print", 'auto_print'); ?>
                                    <?php echo form_dropdown('auto_print', $asp, $settings->auto_print, 'class="form-control select2" id="auto_print" required="required" style="width:100%;"'); ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang("after_sale_page", 'after_sale_page'); ?>
                                    <?php $asp = array(0 => lang('receipt'), 1 => lang('pos')); ?>
                                    <?php echo form_dropdown('after_sale_page', $asp, $settings->after_sale_page, 'class="form-control select2" id="after_sale_page" required="required" style="width:100%;"'); ?>
                                </div>
                                    <?php echo form_hidden('default_discount', $settings->default_discount, 'class="form-control" id="default_discount" required="required"'); ?>
                                    <?php echo form_hidden('tax_rate', $settings->default_tax_rate, 'class="form-control" id="default_tax_rate" required="required"'); ?>
                                <div class="form-group">
                                    <?php echo lang('row_per_page', 'rows_per_page') ?>
                                    <?php
                                    $rw = array('10' => '10', '25' => '25', '50' => '50', '100' => '100');
                                    echo form_dropdown('rows_per_page', $rw, $settings->rows_per_page, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang('Sugerencias a mostrar', 'Sugerencias a mostrar') ?>
                                    <?php
                                    $rw = array('10' => '10', '25' => '25', '50' => '50', '100' => '100');
                                    echo form_dropdown('quantity_suggest', $rw, $settings->quantity_suggest, 'class="form-control select2" id="quantity_suggest" style="width:100%;" required="required"')
                                    ?>
                                </div>
                                <div class="form-group">
                                    <b>Habilitar / Deshabilitar Ventas en fracciones</b>
                                    <?php
                                    $rw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                    echo form_dropdown('enable_fractions', $rw, $settings->enable_fractions, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>
                                <div class="form-group">
                                    <b>Habilitar / Deshabilitar Apartados</b>
                                    <?php
                                    $rw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                    echo form_dropdown('enable_layaway', $rw, $settings->enable_layaway, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>
                                <div class="form-group">
                                    <b>Habilitar / Deshabilitar Detalles del cierre de caja</b>
                                    <?php
                                    $rw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                    echo form_dropdown('enable_detail_register', $rw, $settings->enable_detail_register, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>
                                <div class="form-group">
                                    <b>Habilitar / Deshabilitar Detalles al cajero</b>
                                    <?php
                                    $rw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                    echo form_dropdown('enable_detail_caschier', $rw, $settings->enable_detail_caschier, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>
                                <div class="form-group">
                                    <b>Habilitar / Deshabilitar Edicion Rapida de Productos</b>
                                    <?php
                                    $rw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                    echo form_dropdown('enable_fastedition', $rw, $settings->enable_fastedition, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <?php echo lang('delete_code', 'pin_code'); ?>
                                    <?php echo form_password('pin_code', $settings->pin_code, 'class="form-control" pattern="[0-9]{4,8}"id="pin_code"'); ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang('rounding', 'rounding'); ?>
                                    <?php
                                    $rnd = array('0' => lang('disable'), '1' => lang('to_nearest_005'), '2' => lang('to_nearest_050'), '3' => lang('to_nearest_number'), '4' => lang('to_next_number'));
                                    echo form_dropdown('rounding', $rnd, $settings->rounding, 'class="form-control select2" id="rounding" required="required" style="width:100%;"');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang('display_product', 'display_product') ?>
                                    <?php
                                    $dprv = array('1' => 'Name', '2' => 'Photo', '3' => 'Both');
                                    echo form_dropdown('display_product', $dprv, $settings->bsty, 'class="form-control select2" id="display_product" style="width:100%;" required="required"')
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang('pro_limit', 'pro_limit') ?>
                                    <?php echo form_input('pro_limit', $settings->pro_limit, 'class="form-control" id="pro_limit" required="required"') ?>
                                </div>
                                <div class="form-group demo">
                                    <?php echo lang('display_kb', 'display_kb') ?>
                                    <?php
                                    $dtime = array('1' => lang('yes'), '0' => lang('no'));
                                    echo form_dropdown('display_kb', $dtime, $settings->display_kb, 'class="form-control select2" id="display_kb" style="width:100%;" required="required"')
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang("item_addition", "item_addition"); ?>
                                    <?php
                                    $ia = array(0 => lang('add_new_item'), 1 => lang('increase_quantity_if_item_exist'));
                                    echo form_dropdown('item_addition', $ia, $Settings->item_addition, 'id="item_addition" class="form-control tip select2" required="required" style="width:100%;"');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <?php echo lang('default_category', 'default_category') ?>
                                    <?php
                                    $ct[0] = lang('select') . ' ' . lang('default_category');
                                    foreach ($categories as $catrgory) {
                                        $ct[$catrgory->id] = $catrgory->name;
                                    }
                                    echo form_dropdown('default_category', $ct, $settings->default_category, 'class="form-control select2" style="width:100%;" id="default_category"')
                                    ?>
                                </div>

                                <div class="form-group">
                                    <?php echo lang("default_customer", 'default_customer'); ?>
                                    <?php
                                    foreach ($customers as $customer) {
                                        $cu[$customer->id] = $customer->name;
                                    }
                                    echo form_dropdown('default_customer', $cu, $settings->default_customer, 'class="form-control select2" style="width:100%;" id="default_customer" required="required"');
                                    ?>
                                </div>

                                <div class="form-group">
                                    <?php echo lang("default_actividad", 'Actividad Predeterminada'); ?>
                                    <?php
                                    foreach ($actividadeconomica as $actividad) {
                                        $cu[$actividad->id_actividad] = $actividad->descripcion;
                                    }
                                    echo form_dropdown('default_actividad', $cu, $settings->default_actividad, 'class="form-control select2" style="width:100%;" id="default_actividad" required="required"');
                                    ?>
                                </div>
                                <div class="form-group">
                                    <div class="form-group">
                                        <?php echo lang('dateformat', 'dateformat'); ?> <a href="http://php.net/manual/en/function.date.php" target="_blank"><i class="fa fa-external-link"></i></a>
                                        <?php echo form_input('dateformat', $settings->dateformat, 'class="form-control tip" id="dateformat"  required="required"'); ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <?php echo lang('timeformat', 'timeformat'); ?>
                                    <?php echo form_input('timeformat', $settings->timeformat, 'class="form-control tip" id="timeformat"  required="required"'); ?>
                                </div>

                                
                                <div class="form-group demo">
                                    <?php echo lang('default_email', 'default_email'); ?>
                                    <?php echo form_input('default_email', $settings->default_email, 'class="form-control tip" id="default_email" required="required"'); ?>
                                </div>

                                <div class="form-group" style="display: none; visibility: hidden;">
                                    <?php echo lang('rtl_support', 'rtl'); ?>
                                    <?php $yn = array(0 => lang('disable'), 1 => lang('enable')); ?>
                                    <?php echo form_dropdown('rtl', $yn, $settings->rtl, 'class="form-control select2" id="rtl"'); ?>
                                </div>

                                <div class="form-group" style="display: none; visibility: hidden;">
                                    <?php echo lang("email_protocol", 'protocol'); ?>
                                    <div class="controls">
                                        <?php
                                        $popt = array('mail' => 'PHP Mail Function', 'sendmail' => 'Send Mail', 'smtp' => 'SMTP');
                                        echo form_dropdown('protocol', $popt, ($this->db->dbdriver == 'sqlite3' ? 'smtp' : $Settings->protocol), 'class="form-control tip select2" id="protocol" style="width:100%;" required="required"');
                                        ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <b>Habilitar / Deshabilitar Proformas</b>
                                    <?php
                                    $rw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                    echo form_dropdown('enable_quote', $rw, $settings->enable_quote, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>

                                <div class="form-group">
                                    <b>Habilitar / Deshabilitar cierre unico de caja</b>
                                    <?php
                                    $rw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                    echo form_dropdown('enable_auth_open', $rw, $settings->enable_auth_open, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>

                                <div class="form-group">
                                    <b>Habilitar / Deshabilitar metodo de envio</b>
                                    <?php
                                    $rw = array('1' => 'Habilitada', '0' => 'Deshabilitada');
                                    echo form_dropdown('is_shipping', $rw, $settings->is_shipping, 'class="form-control select2" id="rows_per_page" style="width:100%;" required="required"')
                                    ?>
                                </div>

                                <div class="form-group">
                                    <b>Mostrar impuesto en recibo como</b>
                                    <?php
                                    $rw = array('' => 'No mostrar', 'IVI' => 'IVI', 'IVA' => 'IVA', 'Impuesto' => 'Impuesto',);
                                    echo form_dropdown('enable_show_tax', $rw, $settings->enable_show_tax, 'class="form-control select2" id="rows_per_page" style="width:100%;"')
                                    ?>
                                </div>

                                <div class="form-group">
                                    <b>Footer de Apartado</b>
                                    <?php echo form_input('footer_apartado', $settings->footer_apartado, 'class="form-control tip" id="default_email" '); ?>
                                </div>

                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="row" id="sendmail_config" style="display: none;">
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php echo lang("mailpath", 'mailpath'); ?>
                                        <div class="controls"> <?php echo form_input('mailpath', $Settings->mailpath, 'class="form-control tip" id="mailpath"'); ?> </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if($Settings->demo != "1"){ ?>
                        <div class="clearfix"></div>
                        <?php } ?>
                        
                        <div class="row" <?php echo $Settings->demo == "1" ? "style=' display:none;visibility:hidden;'" : "" ?> id="smtp_config">
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php echo lang("smtp_host", 'smtp_host'); ?>
                                        <div class="controls"> <?php echo form_input('smtp_host', $Settings->smtp_host, 'class="form-control tip" id="smtp_host"'); ?> </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php echo lang("smtp_user", 'smtp_user'); ?>
                                        <div class="controls"> <?php echo form_input('smtp_user', $Settings->smtp_user, 'class="form-control tip" id="smtp_user"'); ?> </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php echo lang("smtp_pass", 'smtp_pass'); ?>
                                        <div class="controls"> <?php echo form_password('smtp_pass', $Settings->smtp_pass, 'class="form-control tip" id="smtp_pass"'); ?> </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php echo lang("smtp_port", 'smtp_port'); ?>
                                        <div class="controls"> <?php echo form_input('smtp_port', $Settings->smtp_port, 'class="form-control tip" id="smtp_port"'); ?> </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <?php echo lang("smtp_crypto", 'smtp_crypto'); ?>
                                        <?php
                                        $crypto_opt = array('' => lang('none'), 'tls' => 'TLS', 'ssl' => 'SSL');
                                        echo form_dropdown('smtp_crypto', $crypto_opt, $Settings->smtp_crypto, 'class="form-control tip select2" id="smtp_crypto" style="width:100%;"');
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <h3><?php echo lang('avanced_settings') ?></h3>
                                <div class="well well-sm">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label" for="search_sensibility"><?php echo lang("search_sensibility"); ?></label>

                                            <div class="controls"> <?php
                                                $sensibility = array(0 => lang('0_search'), 1 => lang('1_search'), 2 => lang('2_search'), 3 => lang('3_search'));
                                                echo form_dropdown('sensibility_search', $sensibility, $Settings->sensibility_search, 'class="form-control tip select2" id="sensibility_search"  style="width:100%;" required="required"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="enable_credit"><?php echo lang("question_enable_credit"); ?></label>

                                            <div class="controls"> <?php
                                                $_enable_credit = array(0 => lang('disable'), 1 => lang('enable'));
                                                echo form_dropdown('enable_credit', $_enable_credit, $Settings->enable_credit, 'class="form-control tip select2" id="enable_credit"  style="width:100%;" required="required"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="qty_decimals"><?php echo lang("question_print_inoice"); ?></label>

                                            <div class="controls"> <?php
                                                $printinvoice = array(0 => lang('disable'), 1 => lang('enable'));
                                                echo form_dropdown('prt_invo_after', $printinvoice, $Settings->prt_invo_after, 'class="form-control tip select2" id="prt_invo_after"  style="width:100%;" required="required"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>



                        <?php if($settings->block_hacienda == "0"){ ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <h3>Configuracion Hacienda</h3>
                                <div class="well well-sm">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="user_token_test">Usuario Prueba</label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->user_token_test ?>" class="form-control parsley-validated" required="true" id="user_token_test" name="user_token_test" type="text"  placeholder="cpj-3-101-000000@stag.comprobanteselectronicos.go.cr">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="password_token_test">Password Prueba</label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->password_token_test ?>" class="form-control parsley-validated"  required="true" id="password_token_test" name="password_token_test" type="text"  placeholder="2ra($%M#)j[5z8:/i];T">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="comprueba_test">&nbsp;</label>

                                            <div class="controls"> 
                                                <span id="comprueba_test" class="btn btn-success">
                                                    Test usuario y password prueba
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="well well-sm">


                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="user_token_prod">Usuario Produccion</label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->user_token_prod ?>" class="form-control" id="user_token_prod" name="user_token_prod" type="text" placeholder="cpj-3-101-000000@prod.comprobanteselectronicos.go.cr">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="password_token_prod">Password Produccion</label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->password_token_prod ?>" class="form-control" id="password_token_prod" name="password_token_prod" type="text" placeholder="2ra($%M#)j[5z8:/i];T">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="comprueba_prod">&nbsp;</label>

                                            <div class="controls"> 
                                                <span id="comprueba_prod" class="btn btn-success">
                                                    Test usuario y password produccion
                                                </span>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <div class="well well-sm">


                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="certificado_ced">Nombre del Certificado .p12 <small>(no colocar la extension.p12)</small></label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->certificado_ced ?>" class="form-control" id="certificado_ced" name="certificado_ced" type="text" placeholder="310100000000">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="certificado_pin">Pin del Certificado .p12 <small>(Este pin tiene una longitud de 4)</small></label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->certificado_pin ?>" class="form-control" id="certificado_pin" name="certificado_pin" type="text" placeholder="0000">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="tipo_doc_emisor">Tipo de Cedula / Documento<br/><small>&nbsp;</small></label>

                                            <div class="controls"> 
                                                <?php
                                                $tipo_doc_emisor = array("01" => "Cedula de Identidad", "02" => "Cedula Juridica", "03" => "DIMEX", "04" => "NITE");
                                                echo form_dropdown('tipo_doc_emisor', $tipo_doc_emisor, $Settings->tipo_doc_emisor, 'class="form-control tip select2" id="tipo_doc_emisor"  style="width:100%;" required="required"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="cedula_emisor">Cedula / N° Documento</label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->cedula_emisor ?>" class="form-control" id="cedula_emisor" name="cedula_emisor" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="nombre_emisor">Nombre del Obligado Tributario</label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->nombre_emisor ?>" class="form-control" id="nombre_emisor" name="nombre_emisor" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="nombre_comercial">Nombre Comercial o de Fantasia</label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->nombre_comercial ?>" class="form-control" id="nombre_comercial" name="nombre_comercial" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="email_emisor">Correo Electronico</label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->email_emisor ?>" class="form-control" id="email_emisor" name="email_emisor" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="telefono_emisor">Numero Telefonico <small>(Sin guiones ni espacios)</small></label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->telefono_emisor ?>" class="form-control" id="telefono_emisor" name="telefono_emisor" type="text" placeholder="22220000">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="fax_emisor">Numero Fax <small>(Sin guiones ni espacios)</small></label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->fax_emisor ?>" class="form-control" id="fax_emisor" name="fax_emisor" type="text" placeholder="22220000">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12">
                                <h3>Direccion del Tributario</h3> <small>(<a target="_blank" href="https://tribunet.hacienda.go.cr/docs/esquemas/2016/v4.2/Codificacionubicacion_V4.2.zip">Ver codificacion de ubicaciones</a>)</small>
                                <div class="well well-sm">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="cod_provincia">Codigo de la provincia <small>(Deben ser Numeros del 1 al 7)</small></label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->cod_provincia ?>" maxlength="1" minlength="1" min="1" max="7" class="form-control" id="cod_provincia" name="cod_provincia" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="cod_canton">Codigo del canton <small>(los codigos del 1 al 9 este debe ser rellenado con un cero a la izquierda)</small></label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->cod_canton ?>" minlength="2" maxlength="2" min="1"  class="form-control" id="cod_canton" name="cod_canton" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="cod_distrito">Codigo del Distrito <small>(los codigos del 1 al 9 este debe ser rellenado con un cero a la izquierda)</small></label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->cod_distrito ?>" minlength="2" maxlength="2" min="1"  class="form-control" id="cod_distrito" name="cod_distrito" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label"
                                                   for="cod_barrio">Codigo del Barrio <small>(los codigos del 1 al 9 este debe ser rellenado con un cero a la izquierda)</small></label>

                                            <div class="controls"> 
                                                <input value="<?= $Settings->cod_barrio ?>" minlength="2" maxlength="2" min="1"  class="form-control" id="cod_barrio" name="cod_barrio" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12">
                                <h3>Bloqueo de Configuracion</h3> <small>(Si ya usted ha probado la configuracion de hacienda y esta 100% seguro de que todo esta bien bloquee esta configuracion para que no sea cambiada)</small>
                                <div class="well well-sm">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <div class="controls"> 
                                                <?php
                                                $block = array(0 => "Seleccione una opcion", 1 => "Bloquear Configuracion");
                                                echo form_dropdown('block_hacienda', $block, $Settings->block_hacienda, 'class="form-control tip select2" id="block_hacienda"  style="width:100%;"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>



                        <div class="row">
                            <div class="col-lg-12">
                                <div class="well well-sm">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label" for="decimals"><?php echo lang("decimals"); ?></label>

                                            <div class="controls"> <?php
                                                $decimals = array(0 => lang('disable'), 1 => '1', 2 => '2', 3 => '3', 4 => '4');
                                                echo form_dropdown('decimals', $decimals, $Settings->decimals, 'class="form-control tip select2" id="decimals"  style="width:100%;" required="required"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label" for="qty_decimals"><?php echo lang("qty_decimals"); ?></label>

                                            <div class="controls"> <?php
                                                $qty_decimals = array(0 => lang('disable'), 1 => '1', 2 => '2');
                                                echo form_dropdown('qty_decimals', $qty_decimals, $Settings->qty_decimals, 'class="form-control tip select2" id="qty_decimals"  style="width:100%;" required="required"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <?php echo lang('sac', 'sac'); ?>
                                            <?php $ed = array('0' => lang('disable'), '1' => lang('enable')); ?>
                                            <?php echo form_dropdown('sac', $ed, set_value('sac', $Settings->sac), 'class="form-control tip select2" id="sac"  required="required"'); ?>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="nsac">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label" for="decimals_sep"><?php echo lang("decimals_sep"); ?></label>

                                                <div class="controls"> <?php
                                                    $dec_point = array('.' => lang('dot'), ',' => lang('comma'));
                                                    echo form_dropdown('decimals_sep', $dec_point, $Settings->decimals_sep, 'class="form-control tip select2" id="decimals_sep"  style="width:100%;" required="required"');
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label" for="thousands_sep"><?php echo lang("thousands_sep"); ?></label>
                                                <div class="controls"> <?php
                                                    $thousands_sep = array('.' => lang('dot'), ',' => lang('comma'), '0' => lang('space'));
                                                    echo form_dropdown('thousands_sep', $thousands_sep, $Settings->thousands_sep, 'class="form-control tip select2" id="thousands_sep"  style="width:100%;" required="required"');
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <?php echo lang('display_currency_symbol', 'display_symbol'); ?>
                                            <?php $opts = array(0 => lang('disable'), 1 => lang('before'), 2 => lang('after')); ?>
                                            <?php echo form_dropdown('display_symbol', $opts, $Settings->display_symbol, 'class="form-control select2" id="display_symbol" style="width:100%;" required="required"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <?php echo lang('currency_symbol', 'symbol'); ?>
                                            <?php echo form_input('symbol', $Settings->symbol, 'class="form-control" id="symbol" style="width:100%;"'); ?>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="display: none; visibility: hidden;">
                            <div class="col-lg-12">
                                <div class="well well-sm">
                                    <?php
                                    if (isset($stripe_balance)) {
                                        echo '<div class="alert alert-success"><button data-dismiss="alert" class="close" type="button">×</button><h2>' . lang('stripe_balance') . '</h2>';
                                        echo '<p>' . lang('pending_amount') . ': ' . $stripe_balance['pending_amount'] . ' (' . $stripe_balance['pending_currency'] . ')';
                                        echo ', ' . lang('available_amount') . ': ' . $stripe_balance['available_amount'] . ' (' . $stripe_balance['available_currency'] . ')</p>';
                                        echo '</div>';
                                    }
                                    ?>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <?php echo lang('stripe', 'stripe'); ?>
                                            <?php $ed = array('0' => lang('disable'), '1' => lang('enable')); ?>
                                            <?php echo form_dropdown('stripe', $ed, $Settings->stripe, 'class="form-control select2" id="stripe" required="required"'); ?>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div id="stripe_con">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="form-group">
                                                <?php echo lang('stripe_secret_key', 'stripe_secret_key'); ?>
                                                <?php echo form_input('stripe_secret_key', $Settings->stripe_secret_key, 'class="form-control tip" id="stripe_secret_key"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="form-group">
                                                <?php echo lang('stripe_publishable_key', 'stripe_publishable_key'); ?>
                                                <?php echo form_input('stripe_publishable_key', $Settings->stripe_publishable_key, 'class="form-control tip" id="stripe_publishable_key"'); ?>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="display: none">
                            <div class="col-lg-12">
                                <div class="well well-sm">
                                    <p><?php echo lang('shortcut_heading') ?></p>

                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('edit_product_pos', 'edit_product_pos'); ?>
                                            <?php echo form_input('edit_last_product', "F1", 'class="form-control tip" id="edit_last_product"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('focus_add_item', 'focus_add_item'); ?>
                                            <?php echo form_input('focus_add_item', "ALT+I", 'class="form-control tip" id="focus_add_item"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('add_customer', 'add_customer'); ?>
                                            <?php echo form_input('add_customer', "ALT+C", 'class="form-control tip" id="add_customer"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('toggle_category_slider', 'toggle_category_slider'); ?>
                                            <?php echo form_input('toggle_category_slider', $Settings->toggle_category_slider, 'class="form-control tip" id="toggle_category_slider"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('cancel_sale', 'cancel_sale'); ?>
                                            <?php echo form_input('cancel_sale', "F9", 'class="form-control tip" id="cancel_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('suspend_sale', 'suspend_sale'); ?>
                                            <?php echo form_input('suspend_sale', "F5", 'class="form-control tip" id="suspend_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('print_order', 'print_order'); ?>
                                            <?php echo form_input('print_order', $Settings->print_order, 'class="form-control tip" id="print_order"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('print_bill', 'print_bill'); ?>
                                            <?php echo form_input('print_bill', $Settings->print_bill, 'class="form-control tip" id="print_bill"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('finalize_sale', 'finalize_sale'); ?>
                                            <?php echo form_input('finalize_sale', "F12", 'class="form-control tip" id="finalize_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('today_sale', 'today_sale'); ?>
                                            <?php echo form_input('today_sale', "ALT+V", 'class="form-control tip" id="today_sale"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('open_hold_bills', 'open_hold_bills'); ?>
                                            <?php echo form_input('open_hold_bills', $Settings->open_hold_bills, 'class="form-control tip" id="open_hold_bills"'); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4" style="display: none">
                                        <div class="form-group">
                                            <?php echo lang('close_register', 'close_register'); ?>
                                            <?php echo form_input('close_register', "ALT+R", 'class="form-control tip" id="close_register"'); ?>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                        <?php if($Settings->demo == "1"){ ?>
                        <style>
                            #smtp_config, .demo{
                                display:none !important;
                                visibility:hidden !important;
                            }
                        </style>
                        <?php } ?>
                        <div class="row" style="display: none; visibility: hidden;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <?php echo lang('login_logo', 'logo'); ?>
                                    <input type="file" name="userfile" id="logo">
                                </div>
                            </div>
                        </div>
                        <div class="row" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <?php echo lang('printing', 'remote_printing'); ?>
                                    <?php
                                    $opts = array(0 => lang('local_install'), 1 => lang('web_browser_print'), 2 => lang('php_pos_print_app'), 3 => "Impresora de red");
                                    ?>
                                    <?php echo form_dropdown('remote_printing', $opts, $settings->remote_printing, 'class="form-control select2" id="remote_printing" style="width:100%;"'); ?>
                                    <span class="help-block"><?php echo lang('print_recommandations'); ?></span>
                                    <?php if (DEMO) { ?>
                                        <span class="help-block">On demo, you can test web printing only.</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    IP Impresora
                                    <?php echo form_input('ip_printer', $settings->ip_printer, 'class="form-control" id="ip_printer" placeholder="127.0.0.1"'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    Nombre compartido de impresora
                                    <?php echo form_input('nombrecompartido', $settings->nombrecompartido, 'class="form-control" id="nombrecompartido" placeholder="epsontm-t20"'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <?php echo lang('cash_drawer_codes', 'cash_drawer_codes'); ?>
                                    <?php echo form_input('cash_drawer_codes', $settings->cash_drawer_codes, 'class="form-control" id="cash_drawer_codes" placeholder="\x1C"'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="row" style="display: none;">
                            <div class="col-md-12">
                                <div class="well well-sm printers">

                                    <div class="ppp">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php echo lang('use_local_printers', 'local_printers'); ?>
                                                <?php $yn = array(1 => lang('yes'), 0 => lang('no')); ?>
                                                <?php echo form_dropdown('local_printers', $yn, set_value('local_printers', $settings->local_printers), 'class="form-control select2" id="local_printers"  required="required"'); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lp">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php echo lang('receipt_printer', 'receipt_printer'); ?> <strong>*</strong>
                                                <?php
                                                $printer_opts = array();
                                                if (!empty($printers)) {
                                                    foreach ($printers as $printer) {
                                                        $printer_opts[$printer->id] = $printer->title;
                                                    }
                                                }
                                                ?>
                                                <?php echo form_dropdown('receipt_printer', $printer_opts, $settings->printer, 'class="form-control select2" id="receipt_printer" style="width:100%;"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php echo lang('order_printers', 'order_printers'); ?> <strong>*</strong>
                                                <?php echo form_dropdown('order_printers[]', $printer_opts, '', 'multiple class="form-control select2" id="order_printers" style="width:100%;"'); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php echo lang('send_print_as', 'print_img'); ?>
                                                <?php $yn = array(0 => lang('text'), 1 => lang('image')); ?>
                                                <?php echo form_dropdown('print_img', $yn, set_value('print_img', $settings->print_img), 'class="form-control select2" id="print_img"  required="required"'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                        <?php echo form_submit('update', lang('update_settings'), 'class="btn btn-primary"'); ?>
                        <?php echo form_close(); ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">
    $(document).ready(function () {
        $("#order_printers").select2().select2('val', <?php echo $settings->order_printers; ?>);
        if ($('#protocol').val() == 'smtp') {
            $('#smtp_config').slideDown();
        } else if ($('#protocol').val() == 'sendmail') {
            $('#sendmail_config').slideDown();
        }
        $('#protocol').change(function () {
            if ($(this).val() == 'smtp') {
                $('#sendmail_config').slideUp();
                $('#smtp_config').slideDown();
            } else if ($(this).val() == 'sendmail') {
                $('#smtp_config').slideUp();
                $('#sendmail_config').slideDown();
            } else {
                $('#smtp_config').slideUp();
                $('#sendmail_config').slideUp();
            }
        });
        if ($('#stripe').val() == 0) {
            $('#stripe_con').slideUp();
        } else {
            $('#stripe_con').slideDown();
        }
        $('#stripe').change(function () {
            if ($(this).val() == 0) {
                $('#stripe_con').slideUp();
            } else {
                $('#stripe_con').slideDown();
            }
        });
        if ($('#remote_printing').val() == 1) {
            $('.printers').slideUp();
            $('.ppp').slideUp();
        } else if ($('#remote_printing').val() == 0) {
            $('.printers').slideDown();
            $('.ppp').slideUp();
            $('.lp').slideDown();
        } else {
            $('.printers').slideDown();
            $('.ppp').slideDown();
            if ($('#local_printers').val() == 1) {
                $('.lp').slideUp();
            } else {
                $('.lp').slideDown();
            }
        }
        $('#remote_printing').change(function () {
            if ($(this).val() == 1) {
                $('.printers').slideUp();
                $('.ppp').slideUp();
            } else if ($(this).val() == 0) {
                $('.printers').slideDown();
                $('.ppp').slideUp();
                $('.lp').slideDown();
            } else {
                $('.printers').slideDown();
                $('.ppp').slideDown();
                if ($('#local_printers').val() == 1) {
                    $('.lp').slideUp();
                } else {
                    $('.lp').slideDown();
                }
            }
        });
        $('#local_printers').change(function () {
            if ($(this).val() == 1) {
                $('.lp').slideUp();
            } else {
                $('.lp').slideDown();
            }
        });

    });
</script>

<script>
    $(function () {

        var url = "<?= base_url() ?>settings/compruebausers";

        $('#comprueba_test').on('click', function () {

            $.post(url, {
                user: $('#user_token_test').val(),
                password: $('#password_token_test').val(),
                ambiente: "test",
<?= $this->security->get_csrf_token_name(); ?>: "<?= $this->security->get_csrf_hash(); ?>"
            })
                    .done(function (data) {
                        alert(data);
                    });
        });

        $('#comprueba_prod').on('click', function () {

            $.post(url, {
                user: $('#user_token_prod').val(),
                password: $('#password_token_prod').val(),
                ambiente: "prod",
<?= $this->security->get_csrf_token_name(); ?>: "<?= $this->security->get_csrf_hash(); ?>"
            })
                    .done(function (data) {
                        alert(data);
                    });
        });


    });
</script>