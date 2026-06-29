<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('enter_info'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="col-md-6">
                        <?= form_open_multipart("purchases/add_expense"); ?>

                        <?php if ($Admin) { ?>
                            <div class="mb-3">
                                <?= lang("date", "date"); ?>
                                <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : ""), 'class="form-control datetimepicker" id="date" required="required"'); ?>
                            </div>
                            <?php } ?>

                            <div class="mb-3">
                                <?= lang("reference", "reference"); ?>
                                <?= form_input('reference', (isset($_POST['reference']) ? $_POST['reference'] : ''), 'class="form-control tip" id="reference"'); ?>
                            </div>

                            <div class="mb-3">
                                <?= lang("amount", "amount"); ?>
                                <input name="amount" type="text" id="amount" value="" class="pa form-control kb-pad amount"
                                required="required"/>
                            </div>

                            <div class="mb-3">
                                <?= lang("attachment", "attachment") ?>
                                <input id="attachment" type="file" name="userfile" data-show-upload="false" data-show-preview="false"
                                class="form-control file">
                            </div>

                            <div class="mb-3">
                                <?= lang("note", "note"); ?>
                                <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ""), 'class="form-control redactor" id="note"'); ?>
                            </div>

                            <div class="mb-3">
                                <?php echo form_submit('add_expense', lang('add_expense'), 'class="btn btn-primary"'); ?>
                            </div>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
</section>



<script type="text/javascript">
    $(function () {
        $('.datetimepicker').tempusDominus = new TempusDominus({
            format: 'YYYY-MM-DD HH:mm'
        });
    });
</script>
