<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('enter_info'); ?></h3>
                </div>
                <div class="box-body">
                    <?= form_open('auth/create_user'); ?>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?= lang("group", "group"); ?>
                            <?php
                            $gp[""] = "";
                            foreach ($groups as $group) {
                                $gp[$group['id']] = $group['description'];
                            }
                            echo form_dropdown('group', $gp, set_value('group'), 'id="group" data-placeholder="' . lang("select") . ' ' . lang("group") . '" class="form-control input-tip select2" style="width:100%;"');
                            ?>
                        </div>
                        <div class="form-group">
                            <?= lang('first_name', 'first_name'); ?>
                            <?= form_input('first_name', set_value('first_name'), 'class="form-control tip" id="first_name"  required="required"'); ?>
                        </div>
                        <div class="form-group">
                            <?= lang('username', 'username'); ?>
                            <?= form_input('username', set_value('username'), 'class="form-control tip" id="username"  required="required"'); ?>
                        </div>
                        <div class="form-group">
                            <?= lang('password', 'password'); ?>
                            <?= form_password('password', '', 'class="form-control tip" id="password"  required="required"'); ?>
                        </div>
                        <div class="form-group">
                            <?= lang('confirm_password', 'confirm_password'); ?>
                            <?= form_password('confirm_password', '', 'class="form-control tip" id="confirm_password"  required="required"'); ?>
                        </div>
                        <div class="form-group">
                            <?= lang('status', 'status'); ?>
                            <?php
                            $opt = array('' => '', 1 => lang('active'), 0 => lang('inactive'));
                            echo form_dropdown('status', $opt, (isset($_POST['status']) ? $_POST['status'] : ''), 'id="status" data-placeholder="' . lang("select") . ' ' . lang("status") . '" class="form-control input-tip select2" style="width:100%;"');
                            ?>
                        </div>
                        <div class="form-group store-con">
                            <?= lang("store", "store_id"); ?>
                            <?php
                            $st[""] = "";
                            foreach ($stores as $store) {
                                $st[$store->id] = $store->name;
                            }
                            echo form_dropdown('store_id', $st, set_value('store_id'), 'id="store_id" data-placeholder="' . lang("select") . ' ' . lang("store") . '" class="form-control input-tip select2" style="width:100%;"');
                            ?>
                        </div>
                        <div class="form-group">
                            <?= form_submit('add_user', lang('add_user'), 'class="btn btn-primary"'); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">

                            <div class="col-lg-12">
                                Horario    
                            </div>
                            <div class="col-lg-6">
                                Hora de Entrada    
                                <div class='input-group date' id='appointment_start_datetime'>
                                    <input type='text' name="hora_inicio" class="form-control" />
                                    <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                Hora de Salida    
                                <div class='input-group date' id='appointment_end_datetime'>
                                    <input type='text' name="hora_fin" class="form-control" />
                                    <span class="input-group-addon">
                                        <span class="glyphicon glyphicon-calendar"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= $assets ?>dist/js/moment.min.js"></script>
<script src="<?= $assets ?>dist/js/bootstrap-datetimepicker.min.js"></script>
<script>
    $(function () {
        $('#appointment_start_datetime').datetimepicker({
            useCurrent: false,
            format: "H:ss",
            sideBySide: true,
            enabledHours: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24]
        }).on('dp.change', function (e) {
            var beginningTime = moment({h: +e.date.format('H'), s: +e.date.format('ss')});
            var endTime = moment({h: 24, s: 0});
            if (endTime.isBefore(beginningTime)) {
                $('#appointment_end_datetime').data('DateTimePicker').date(e.date.format("H:00"));
            }
        });
        $('#appointment_end_datetime').datetimepicker({
            useCurrent: false,
            format: "H:ss",
            sideBySide: true,
            enabledHours: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24]
        }).on('dp.change', function (e) {
            var beginningTime = moment({h: +e.date.format('H'), s: +e.date.format('ss')});
            var endTime = moment({h: 24, s: 0});
            if (endTime.isBefore(beginningTime)) {
                $('#appointment_end_datetime').data('DateTimePicker').date(e.date.format("H:00"));
            }
        });
    });
</script>
