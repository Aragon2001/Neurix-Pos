<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxf-page">
    <div class="row">
        <div class="col-12">
            <div class="nxf-card">
                <div class="nxf-card-head">
                    <div class="nxf-card-title"><?= lang('enter_info'); ?></div>
                </div>
                <div class="nxf-card-body">
                    <div class="col-lg-12">
                        <?php $attrib = array('class' => 'validation', 'role' => 'form');
                        echo form_open("gift_cards/add", $attrib); ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <?= lang("card_no", "card_no"); ?>
                                    <div class="input-group">
                                        <?php echo form_input('card_no', '', 'class="form-control" id="card_no" required="required"'); ?>
                                        <div class="input-group-text" style="padding-left: 10px; padding-right: 10px;"><a href="#"
                                           id="genNo"><i
                                           class="fa fa-cogs"></i></a></div>
                                       </div>
                                   </div>
                                   <div class="mb-3">
                                    <?= lang("value", "value"); ?>
                                    <?php echo form_input('value', '', 'class="form-control" id="value" required="required"'); ?>
                                </div>
                                <div class="mb-3">
                                    <?= lang("expiry_date", "expiry"); ?>
                                    <?php echo form_input('expiry', '', 'class="form-control" id="expiry" type="date"'); ?>
                                </div>
                                <div class="mb-3">
                                    <?= form_submit('add_gift_Card', lang('add_gift_Card'), 'class="nxf-btn"'); ?>
                                </div>
                            </div>
                        </div>
                        <?php echo form_close(); ?>

                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';

    var $ = function (id) { return document.getElementById(id); };
    var numero = $('card_no');

    // El numero se agrupa de cuatro en cuatro mientras se escribe.
    if (numero) {
        numero.setAttribute('inputmode', 'numeric');
        numero.setAttribute('maxlength', '19');
        numero.addEventListener('input', function () {
            var v = numero.value.replace(/[^0-9]/g, '').slice(0, 16);
            numero.value = v.replace(/(.{4})(?=.)/g, '$1 ').trim();
        });
    }

    var boton = $('genNo');
    if (boton && numero) {
        boton.addEventListener('click', function (e) {
            e.preventDefault();
            var n = '';
            for (var i = 0; i < 16; i++) { n += Math.floor(Math.random() * 10); }
            numero.value = n.replace(/(.{4})(?=.)/g, '$1 ');
        });
    }
})();
</script>    