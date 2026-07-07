<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('enter_info'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="well well-sm col-sm-6">
                                <?php if ($open_cash) { ?>
                                    <?php
                                    $attrib = array('data-toggle' => 'validator', 'role' => 'form', 'id' => 'open-register-form');
                                    echo form_open_multipart("pos/open_register", $attrib);
                                    ?>
                                    <div class="mb-3">
                                        <?= lang('cash_in_hand', 'cash_in_hand') ?>
                                        <?= form_input('cash_in_hand', '', 'id="cash_in_hand" class="form-control"'); ?>
                                    </div>
                                    <?php // echo form_submit('open_register', lang('open_register'), 'class="btn btn-primary"');   ?>
                                    <span class="btn btn-primary" id="open_register"><?= lang('aperturar_caja'); ?></span>
                                    <?php echo form_close(); ?>
                                    <div class="clearfix"></div>
                                <?php } else { ?>
                                    <div><?= lang('no_puede_aperturar'); ?></div>

                                    <div class="clearfix"></div>
                                <?php } ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        var cashInput = document.getElementById('cash_in_hand');
        var openBtn = document.getElementById('open_register');
        var form = document.getElementById('open-register-form');

        if (!cashInput || !openBtn || !form) {
            return;
        }

        cashInput.addEventListener('keypress', function (event) {
            if (event.keyCode === 10 || event.keyCode === 13) {
                event.preventDefault();
                return false;
            }
        });

        openBtn.addEventListener('click', function () {
            var cih = cashInput.value;
            if (cih && is_numeric(cih)) {
                form.submit();
                openBtn.style.display = 'none';
            }
        });
    })();
</script>