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
                    <div class="nxf-card-title"><?= lang('update_info'); ?></div>
                </div>
                <div class="nxf-card-body">
                    <div class="col-md-6">
                        <?= form_open_multipart("purchases/edit_expense/".$expense->id); ?>

                        <?php if ($Admin) { ?>
                            <div class="mb-3">
                                <?= lang("date", "date"); ?>
                                <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : $expense->date), 'class="form-control" id="date" type="datetime-local" required="required"'); ?>
                            </div>
                            <?php } ?>

                            <div class="mb-3">
                                <?= lang("reference", "reference"); ?>
                                <?= form_input('reference', (isset($_POST['reference']) ? $_POST['reference'] : $expense->reference), 'class="form-control tip" id="reference"'); ?>
                            </div>

                            <div class="mb-3">
                                <?= lang("amount", "amount"); ?>
                                <?= form_input('amount', (isset($_POST['amount']) ? $_POST['amount'] : $expense->amount), 'class="form-control tip" id="amount"'); ?>
                            </div>

                            <div class="mb-3">
                                <label for="category_id"><?= lang('categoria_gasto'); ?> *</label>
                                <select name="category_id" id="category_id" class="form-control" required>
                                    <option value=""><?= lang('seleccione'); ?></option>
                                    <?php foreach ($categorias_gasto as $c) { ?>
                                        <option value="<?= $c->id; ?>" <?= (string) (set_value('category_id', $expense->category_id)) === (string) $c->id ? 'selected' : ''; ?>><?= html_escape($c->nombre); ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="supplier_id"><?= lang('supplier'); ?> <small class="text-muted">(<?= lang('opcional'); ?>)</small></label>
                                <select name="supplier_id" id="supplier_id" class="form-control">
                                    <option value=""><?= lang('sin_proveedor'); ?></option>
                                    <?php foreach ($suppliers as $s) { ?>
                                        <option value="<?= $s->id; ?>" <?= (string) (set_value('supplier_id', $expense->supplier_id)) === (string) $s->id ? 'selected' : ''; ?>><?= html_escape($s->company ? $s->company . ' · ' . $s->name : $s->name); ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="impuesto"><?= lang('impuesto_incluido'); ?> <small class="text-muted">(<?= lang('opcional'); ?>)</small></label>
                                <input name="impuesto" id="impuesto" type="number" step="any" min="0" class="form-control" value="<?= html_escape(set_value('impuesto', $expense->impuesto)); ?>">
                                <div class="form-text"><?= lang('impuesto_incluido_ayuda'); ?></div>
                            </div>

                            <div class="mb-3">
                                <?= lang("attachment", "attachment") ?>
                                <input type="file" name="userfile" class="form-control file">
                            </div>

                            <div class="mb-3">
                                <?= lang("note", "note"); ?>
                                <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : $expense->note), 'class="form-control redactor" id="note"'); ?>
                            </div>

                            <div class="mb-3">
                                <?php echo form_submit('edit_expense', lang('edit_expense'), 'class="nxf-btn"'); ?>
                            </div>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
</div>
