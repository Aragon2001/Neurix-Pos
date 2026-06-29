<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-bs-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
            </button>
            <button type="button" class="close mr10" onclick="window.print();"><i class="fa fa-print"></i></button>
            <h4 class="modal-title" id="myModalLabel"> <?= lang('credit_note') ?> </h4>
        </div>
        <div class="modal-body">
            <?= form_open("pos/creditnote/"); ?>

            <div class="mb-3">
                <?= lang('credit_note') ?>
                <?php $opts = array(
                    '3' => 'Devolucion de Mercancia',
                    '1' => 'Anular Factura'
                ); ?>
                <?= form_dropdown('tipo_nc', $opts, set_value('tipo_nc', '3'), 'class="form-control tip select2" id = "tipo_nc"  required = "required" style = "width:100%;"'); ?>
            </div>
            <div class="mb-3">
                <?= lang('invnum_invcode') ?>
                <?= form_input('nfactura', set_value('nfactura'), 'class="form-control tip" id = "nfactura"  required = "required"'); ?>
            </div>
            <div class="mb-3">
                <?= form_submit('', lang('submit'), 'class="btn btn-primary"'); ?>
            </div>



            <?= form_close(); ?>
        </div>

    </div>

