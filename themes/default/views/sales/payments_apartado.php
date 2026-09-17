<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-bs-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
            <button type="button" class="close mr10 imprimirALL" id="<?= $id_apartado ?>" ><i class="fa fa-print"></i></button>
            <h4 class="modal-title" id="myModalLabel"><?php echo lang('view_payments'); ?></h4>
        </div>
        <div class="modal-body">
            <div class="table-responsive">
                <table id="CompTable" cellpadding="0" cellspacing="0" border="0"
                       class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr>
                            <th style="width:30%;"><?= lang("date"); ?></th>
                            <th style="width:30%;"><?= lang("reference"); ?></th>
                            <th style="width:15%;"><?= lang("amount"); ?></th>
                            <th style="width:15%;"><?= lang("paid_by"); ?></th>
                            <th style="width:10%;"><?= lang("actions"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)) {
                            foreach ($payments as $payment) {
                                ?>
                                <tr class="row<?= $payment->id ?>">
                                    <td><?= $this->tec->hrld($payment->date); ?></td>
                                    <td><?= lang($payment->reference); ?></td>
                                    <td class="text-right"><?= $this->tec->formatMoney($payment->amount) . ' ' . ((!empty($payment->attachment)) ? '<a href="' . base_url('assets/uploads/' . $payment->attachment) . '" target="_blank"><i class="fa fa-chain"></i></a>' : ''); ?></td>
                                    <td><?= lang($payment->paid_by); ?></td>
                                    <td>
                                        <div class="text-center">
                                            <span style="cursor:pointer" class="imprimir" id="<?= $payment->id ?>" title="<?= lang('imprimir_recibo'); ?>"><i class="fa fa-print"></i></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                            }
                        } else {
                            echo "<tr><td colspan='5'>" . lang('no_data_available') . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';

    var BASE = '<?= base_url(); ?>';

    // El recibo se arma en el servidor y sale por QZ Tray; aquí solo se pide.
    function imprimir(ruta, id, boton) {
        boton.style.opacity = '.5';
        fetch(BASE + ruta + id, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                if (!r.ok) { throw new Error(r.status); }
            })
            .catch(function () {
                alert(<?= json_encode(lang('inv_error_red')); ?>);
            })
            .then(function () { boton.style.opacity = ''; });
    }

    document.addEventListener('click', function (e) {
        var uno = e.target.closest('.imprimir');
        if (uno) { imprimir('sales/print_receipt/', uno.id, uno); return; }

        var todos = e.target.closest('.imprimirALL');
        if (todos) { imprimir('sales/printAllReceiptApartado/', todos.id, todos); }
    });
})();
</script>