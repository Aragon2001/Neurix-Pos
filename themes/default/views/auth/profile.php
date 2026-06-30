<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">
                        <i class="fa fa-user-circle-o"></i>
                        <?= lang('edit'); ?> — <?= html_escape($user->first_name . ' ' . $user->last_name); ?>
                    </h3>
                </div>
                <div class="box-body" style="padding: 0;">

                    <div class="row" style="margin:0;">

                        <!-- ── Sidebar nav ── -->
                        <div class="col-md-2 nx-settings-nav" style="padding: 20px 0;">
                            <ul class="nav nav-pills nav-stacked">
                                <li class="active">
                                    <a href="#tab-info" data-toggle="pill">
                                        <i class="fa fa-user"></i>
                                        <span class="nx-nav-label"><?= lang('edit'); ?><span class="nx-nav-sub">Información personal</span></span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#tab-avatar" data-toggle="pill">
                                        <i class="fa fa-image"></i>
                                        <span class="nx-nav-label"><?= lang('avatar'); ?><span class="nx-nav-sub">Foto de perfil</span></span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#tab-horario" data-toggle="pill">
                                        <i class="fa fa-clock-o"></i>
                                        <span class="nx-nav-label">Horario<span class="nx-nav-sub">Entrada y salida</span></span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#tab-caja" data-toggle="pill">
                                        <i class="fa fa-unlock-alt"></i>
                                        <span class="nx-nav-label">Apertura de Caja<span class="nx-nav-sub">Permiso de apertura</span></span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#tab-password" data-toggle="pill">
                                        <i class="fa fa-lock"></i>
                                        <span class="nx-nav-label"><?= lang('change_password'); ?><span class="nx-nav-sub">Seguridad de cuenta</span></span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- ── Tab content ── -->
                        <div class="col-md-10" style="padding: 24px 30px;">
                            <div class="tab-content">

                                <!-- ── Info personal ── -->
                                <div id="tab-info" class="tab-pane active">
                                    <h4 style="margin-top:0; margin-bottom:20px; font-weight:600; color:var(--nx-txt1);">
                                        <i class="fa fa-user" style="color:var(--primary); margin-right:8px;"></i>
                                        Información Personal
                                    </h4>
                                    <?= form_open('auth/edit_user/' . $user->id); ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?= lang('first_name', 'first_name'); ?>
                                                <?= form_input('first_name', $user->first_name, 'class="form-control" id="first_name" required="required"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?= lang('last_name', 'last_name'); ?>
                                                <?= form_input('last_name', $user->last_name, 'class="form-control" id="last_name" required="required"'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?= lang('phone', 'phone'); ?>
                                                <?= form_input('phone', $user->phone, 'class="form-control" id="phone" required="required"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?= lang('gender', 'gender'); ?>
                                                <?php $gnders = array('male' => lang('male'), 'female' => lang('female')); ?>
                                                <?= form_dropdown('gender', $gnders, $user->gender, 'class="form-control select2" style="width:100%;" id="gender" required="required"'); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($Admin && $id != $this->session->userdata('user_id')): ?>
                                    <hr style="border-color:var(--nx-border); margin: 20px 0;">
                                    <h5 style="font-weight:600; color:var(--nx-txt2); margin-bottom:16px;">
                                        <i class="fa fa-shield" style="color:var(--warning); margin-right:6px;"></i>
                                        Acceso y Permisos
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?= lang("group", "group"); ?>
                                                <?php
                                                $gp[""] = "";
                                                foreach ($groups as $group) { $gp[$group['id']] = $group['name']; }
                                                echo form_dropdown('group', $gp, $user->group_id, 'id="group" data-placeholder="' . lang("select") . ' ' . lang("group") . '" class="form-control select2" style="width:100%;"');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?= lang('status', 'status'); ?>
                                                <?php
                                                $opt = array('' => '', 1 => lang('active'), 0 => lang('inactive'));
                                                echo form_dropdown('status', $opt, $user->active, 'id="status" class="form-control select2" style="width:100%;"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?= lang('username', 'username'); ?>
                                                <?= form_input('username', $user->username, 'class="form-control" id="username" required="required"'); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?= lang('email', 'email'); ?>
                                                <?= form_input('email', $user->email, 'class="form-control" id="email" required="required"'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group store-con">
                                                <?= lang("store", "store_id"); ?>
                                                <?php
                                                $st[""] = "";
                                                foreach ($stores as $store) { $st[$store->id] = $store->name; }
                                                echo form_dropdown('store_id', $st, $user->store_id, 'id="store_id" class="form-control select2" style="width:100%;"');
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel panel-warning" style="margin-top:8px;">
                                        <div class="panel-heading">
                                            <i class="fa fa-key"></i> <?= lang('if_you_need_to_rest_password_for_user') ?>
                                        </div>
                                        <div class="panel-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <?php echo lang('password', 'password'); ?>
                                                        <?php echo form_input($password); ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <?php echo lang('confirm_password', 'password_confirm'); ?>
                                                        <?php echo form_input($password_confirm); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php echo form_hidden('id', $id); ?>
                                    <?php echo form_hidden($csrf); ?>
                                    <div class="form-group" style="margin-top:20px;">
                                        <?= form_submit('update_user', lang('update'), 'class="btn btn-primary"'); ?>
                                    </div>
                                    <?= form_close(); ?>
                                </div>

                                <!-- ── Avatar ── -->
                                <div id="tab-avatar" class="tab-pane">
                                    <h4 style="margin-top:0; margin-bottom:20px; font-weight:600; color:var(--nx-txt1);">
                                        <i class="fa fa-image" style="color:var(--primary); margin-right:8px;"></i>
                                        Foto de Perfil
                                    </h4>
                                    <div class="row">
                                        <div class="col-md-3" style="text-align:center; margin-bottom:20px;">
                                            <?= $user->avatar
                                                ? '<img alt="" src="' . base_url() . 'uploads/avatars/' . $user->avatar . '" class="avatar img-thumbnail img-rounded" style="width:120px;height:120px;object-fit:cover;">'
                                                : '<img alt="" src="' . base_url() . 'uploads/avatars/' . $user->gender . '.png" class="avatar img-thumbnail img-rounded" style="width:120px;height:120px;object-fit:cover;">';
                                            ?>
                                        </div>
                                        <div class="col-md-5">
                                            <?= form_open_multipart("auth/update_avatar"); ?>
                                            <div class="form-group">
                                                <?= lang("change_avatar", "change_avatar"); ?>
                                                <input type="file" name="avatar" id="product_image" required="required"
                                                    data-show-upload="false" data-show-preview="false" accept="image/*"
                                                    class="form-control file" />
                                            </div>
                                            <div class="form-group">
                                                <?php echo form_hidden('id', $id); ?>
                                                <?php echo form_hidden($csrf); ?>
                                                <?php echo form_submit('update_avatar', lang('update_avatar'), 'class="btn btn-primary"'); ?>
                                            </div>
                                            <?= form_close(); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- ── Horario ── -->
                                <div id="tab-horario" class="tab-pane">
                                    <h4 style="margin-top:0; margin-bottom:20px; font-weight:600; color:var(--nx-txt1);">
                                        <i class="fa fa-clock-o" style="color:var(--primary); margin-right:8px;"></i>
                                        Horario del Usuario
                                    </h4>
                                    <?= form_open('auth/edit_horario/' . $user->id); ?>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Hora de Entrada</label>
                                                <div class="input-group date" id="appointment_start_datetime">
                                                    <input type="text" value="<?= $user->hora_inicio ?>" name="hora_inicio" class="form-control" />
                                                    <span class="input-group-addon"><span class="glyphicon glyphicon-time"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Hora de Salida</label>
                                                <div class="input-group date" id="appointment_end_datetime">
                                                    <input type="text" value="<?= $user->hora_fin ?>" name="hora_fin" class="form-control" />
                                                    <span class="input-group-addon"><span class="glyphicon glyphicon-time"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php echo form_hidden('id', $id); ?>
                                    <?php echo form_hidden($csrf); ?>
                                    <div class="form-group" style="margin-top:8px;">
                                        <?= form_submit('update_user', lang('update'), 'class="btn btn-primary"'); ?>
                                    </div>
                                    <?= form_close(); ?>
                                </div>

                                <!-- ── Apertura de Caja ── -->
                                <div id="tab-caja" class="tab-pane">
                                    <h4 style="margin-top:0; margin-bottom:20px; font-weight:600; color:var(--nx-txt1);">
                                        <i class="fa fa-unlock-alt" style="color:var(--primary); margin-right:8px;"></i>
                                        Permiso de Apertura de Caja
                                    </h4>
                                    <?= form_open('auth/auth_open_cash/' . $user->id); ?>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label>¿Habilitar apertura de caja para este usuario?</label>
                                                <?php $auth_open_options = array('1' => 'Sí, Habilitar', '0' => 'No permitir'); ?>
                                                <?= form_dropdown('auth_open', $auth_open_options, $user->auth_open, 'class="form-control select2" style="width:100%;" id="auth_open" required="required"'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php echo form_hidden('id', $id); ?>
                                    <?php echo form_hidden($csrf); ?>
                                    <div class="form-group">
                                        <?= form_submit('update_user', lang('update'), 'class="btn btn-primary"'); ?>
                                    </div>
                                    <?= form_close(); ?>
                                </div>

                                <!-- ── Cambiar contraseña ── -->
                                <div id="tab-password" class="tab-pane">
                                    <h4 style="margin-top:0; margin-bottom:20px; font-weight:600; color:var(--nx-txt1);">
                                        <i class="fa fa-lock" style="color:var(--primary); margin-right:8px;"></i>
                                        <?= lang('change_password'); ?>
                                    </h4>
                                    <?= form_open("auth/change_password"); ?>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <?php echo lang('old_password', 'curr_password'); ?>
                                                <?php echo form_password('old_password', '', 'class="form-control" id="curr_password"'); ?>
                                            </div>
                                            <div class="form-group">
                                                <label for="new_password"><?php echo sprintf(lang('new_password'), $min_password_length); ?></label>
                                                <?php echo form_password('new_password', '', 'class="form-control" id="new_password" pattern=".{8,}"'); ?>
                                            </div>
                                            <div class="form-group">
                                                <?php echo lang('confirm_password', 'new_password_confirm'); ?>
                                                <?php echo form_password('new_password_confirm', '', 'class="form-control" id="new_password_confirm" pattern=".{8,}"'); ?>
                                            </div>
                                            <?php echo form_input($user_id); ?>
                                            <div class="form-group">
                                                <?php echo form_submit('change_password', lang('change_password'), 'class="btn btn-primary"'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?= form_close(); ?>
                                </div>

                            </div><!-- /.tab-content -->
                        </div><!-- /.col-md-10 -->

                    </div><!-- /.row -->
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div>
    </div>
</section>

<script src="<?= $assets ?>dist/js/moment.min.js"></script>
<script src="<?= $assets ?>dist/js/bootstrap-datetimepicker.min.js"></script>
<script>
    $(function () {
        $('.select2').select2({ minimumResultsForSearch: 6 });

        $('#appointment_start_datetime').datetimepicker({
            useCurrent: false, format: 'HH:mm', sideBySide: true,
        });
        $('#appointment_end_datetime').datetimepicker({
            useCurrent: false, format: 'HH:mm', sideBySide: true,
        });
    });
</script>
