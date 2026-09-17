<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-bs-dismiss="modal"><span aria-hidden="true"><i class="fa fa-times"></i></span><span class="sr-only"><?=lang('close');?></span></button>
            <h4 class="modal-title" id="mModalLabel"><?= lang('shortcut_keys') ?></h4>
        </div>
        <div class="modal-body" id="pr_popover_content">
            <table class="table table-bordered table-condensed"
            style="margin-bottom: 0px;">
            <thead>
                <tr>
                    <th><?= lang('shortcut_keys') ?></th>
                    <th><?= lang('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>F2</td>
                    <td><?= lang('producto_rapido') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->edit_last_product ?></td>
                    <td><?= lang('edit_product_pos') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->focus_add_item ?></td>
                    <td><?= lang('focus_add_item') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->add_customer ?></td>
                    <td><?= lang('add_customer') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->toggle_category_slider ?></td>
                    <td><?= lang('toggle_category_slider') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->cancel_sale ?></td>
                    <td><?= lang('cancel_sale') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->suspend_sale ?></td>
                    <td><?= lang('suspend_sale') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->finalize_sale ?></td>
                    <td><?= lang('finalize_sale') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->today_sale ?></td>
                    <td><?= lang('today_sale') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->open_hold_bills ?></td>
                    <td><?= lang('open_hold_bills') ?></td>
                </tr>
                <tr>
                    <td><?= $Settings->close_register ?></td>
                    <td><?= lang('close_register') ?></td>
                </tr>
                <tr>
                    <td>ESC</td>
                    <td><?= lang('kbd_cancelar_busqueda') ?></td>
                </tr>
                <tr>
                    <td>+</td>
                    <td><?= lang('kbd_foco_busqueda') ?></td>
                </tr>
            </tbody>
        </table>
                </div>
    </div>
</div>
</div>