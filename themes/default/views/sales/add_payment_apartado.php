<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-bs-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
            </button>
            <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_payment'); ?></h4>
        </div>
        <?= form_open_multipart("sales/add_payment_apartado/" . $inv->id . "/" . $inv->customer_id); ?>
        <div class="modal-body">
            <p><?= lang('enter_info'); ?></p>

            <div class="row">
                <?php if ($Admin) { ?>
                    <div class="col-sm-6">
                        <div class="mb-3">
                            <?= lang("date", "date"); ?>
                            <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : date('Y-m-d H:i')), 'class="form-control datetimepicker" id="date" required="required"'); ?>
                        </div>
                    </div>
                <?php } ?>
                <div class="col-sm-6">
                    <div class="mb-3">
                        <?= lang("reference", "reference"); ?>
                        <?= form_input('reference', set_value('reference'), 'class="form-control tip" id="reference"'); ?>
                    </div>
                </div>

                <input type="hidden" value="<?php echo $inv->id; ?>" name="sale_id"/>
            </div>
            <div class="clearfix"></div>
            <div id="payments">

                <div class="well well-sm well">
                    <div class="col-sm-12">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="payment">
                                    <div class="mb-3">
                                        <?= lang("amount", "amount"); ?>
                                        <input name="amount-paid" type="text" id="amount"
                                               value="<?= ($inv->grand_total - $inv->paid) > 0 ? $this->tec->formatDecimal($inv->grand_total - $inv->paid) : 0; ?>"
                                               class="pa form-control kb-pad amount" required="required"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-3">
                                    <?= lang("paying_by", "paid_by"); ?>
                                    <select name="paid_by" id="paid_by" class="form-control paid_by tom-select"
                                            style="width:100%"
                                            required="required">
                                        <option value="cash"><?= lang("cash"); ?></option>
                                        <option value="CC">Tarjeta</option>
                                        <option value="Cheque"><?= lang("cheque"); ?></option>
                                        <!--                                    <option value="gift_card">-->
                                        <? //= lang("gift_card"); ?><!--</option>-->
                                        <!--                                    --><? //= isset($Settings->stripe) ? '<option value="stripe">' . lang("stripe") . '</option>' : ''; ?>
                                        <!--                                    <option value="other">-->
                                        <? //= lang("other"); ?><!--</option>-->
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="clearfix"></div>
                        <div class="mb-3 gc" style="display: none;">
                            <?= lang("gift_card_no", "gift_card_no"); ?>
                            <input name="gift_card_no" type="text" id="gift_card_no" class="pa form-control kb-pad"/>

                            <div id="gc_details"></div>
                        </div>
                        <div class="pcc" style="display:none;">
                            <input type="hidden" id="swipe" class="form-control swipe swipe_input"
                                   placeholder="<?= lang('focus_swipe_here') ?>"/>

                            <div class="row">
                                <input name="pcc_no" type="hidden" id="pcc_no" class="form-control"
                                       placeholder="<?= lang('cc_no') ?>"/>


                                <input name="pcc_holder" type="hidden" id="pcc_holder" class="form-control"
                                       placeholder="<?= lang('cc_holder') ?>"/>

                                <div class="col-sm-6">
                                    <div class="mb-3">
                                        <select name="pcc_type" id="pcc_type" class="form-control pcc_type tom-select"
                                                style="width:100%"
                                                placeholder="<?= lang('card_type') ?>">
                                            <option value="Debito">Debito</option>
                                            <option value="Visa"><?= lang("Visa"); ?></option>
                                            <option value="MasterCard"><?= lang("MasterCard"); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <input name="pcc_month" type="hidden" id="pcc_month" class="form-control"
                                       placeholder="<?= lang('month') ?>"/>

                                <input name="pcc_year" type="hidden" id="pcc_year" class="form-control"
                                       placeholder="<?= lang('year') ?>"/>

                                <input name="pcc_ccv" type="hidden" id="pcc_cvv2" class="form-control"
                                       placeholder="<?= lang('cvv2') ?>"/>
                            </div>
                        </div>
                        <div class="pcheque" style="display:none;">
                            <div class="mb-3"><?= lang("cheque_no", "cheque_no"); ?>
                                <input name="cheque_no" type="text" id="cheque_no" class="form-control cheque_no"/>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>

            </div>

            <div class="mb-3">
                <?= lang("attachment", "attachment") ?>
                <input id="attachment" type="file" name="userfile" class="form-control file">
            </div>

            <div class="mb-3">
                <?= lang("note", "note"); ?>
                <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ""), 'class="form-control redactor" id="note"'); ?>
            </div>

        </div>
        <div class="modal-footer">
            <?php echo form_submit('add_payment_apartado', lang('add_payment'), 'class="btn btn-primary"'); ?>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>

<script type="text/javascript" charset="UTF-8">
    $(document).ready(function () {

        $('#gift_card_no').inputmask("9999 9999 9999 9999");
        $(document).on('change', '.paid_by', function () {
            var p_val = $(this).val();
            if (p_val == 'gift_card') {
                $('.gc').slideDown();
                $('.ngc').slideUp('fast');
                setTimeout(function () {
                    $('#gift_card_no').focus();
                }, 10);
                $('#amount').attr('readonly', true);
            } else {
                $('.ngc').slideDown();
                $('.gc').slideUp('fast');
                $('#gc_details').html('');
                $('#amount').attr('readonly', false);
            }
            if (p_val == 'cash' || p_val == 'other') {
                $('.pcash').slideDown();
                $('.pcheque').slideUp('fast');
                $('.pcc').slideUp('fast');
                setTimeout(function () {
                    $('#amount').focus();
                }, 10);
            } else if (p_val == 'CC' || p_val == 'stripe') {
                $('.pcc').slideDown();
                $('.pcheque').slideUp('fast');
                $('.pcash').slideUp('fast');
                setTimeout(function () {
                    $('#swipe').val('').focus();
                }, 10);
            } else if (p_val == 'Cheque') {
                $('.pcheque').slideDown();
                $('.pcc').slideUp('fast');
                $('.pcash').slideUp('fast');
                setTimeout(function () {
                    $('#cheque_no').focus();
                }, 10);
            } else {
                $('.pcheque').hide();
                $('.pcc').hide();
                $('.pcash').hide();
            }
        });

        $(document).on('change', '#gift_card_no', function () {
            var cn = $(this).val() ? $(this).val() : '';
            if (cn != '') {
                $.ajax({
                    type: "get", async: false,
                    url: base_url + "pos/validate_gift_card/" + cn,
                    dataType: "json",
                    success: function (data) {
                        if (data === false) {
                            bootbox.alert('<?= lang('incorrect_gift_card'); ?>');
                        } else {
                            $('#gc_details').html('<?= lang('card_no'); ?>: ' + data.card_no + '<br><?= lang('value'); ?>: ' + data.value + '<?= lang('balance'); ?>: ' + data.balance);
                            var g_total = <?= $this->tec->formatDecimal($inv->grand_total - $inv->paid); ?>;
                            $('#amount').val((g_total > data.balance) ? data.balance : g_total).change().focus();
                        }
                    }
                });
            }
            return false;
        });

        $('.swipe').keypress(function (e) {
            var TrackData = $(this).val() ? $(this).val() : '';
            if (TrackData != '') {
                if (e.keyCode == 13) {
                    e.preventDefault();
                    var p = new SwipeParserObj(TrackData);

                    if (p.hasTrack1) {

                        var CardType = null;
                        var ccn1 = p.account.charAt(0);
                        if (ccn1 == 4)
                            CardType = 'Visa';
                        else if (ccn1 == 5)
                            CardType = 'MasterCard';
                        else if (ccn1 == 3)
                            CardType = 'Amex';
                        else if (ccn1 == 6)
                            CardType = 'Discover';
                        else
                            CardType = 'Visa';

                        $('#pcc_no').val(p.account).change();
                        $('#pcc_holder').val(p.account_name).change();
                        $('#pcc_month').val(p.exp_month).change();
                        $('#pcc_year').val(p.exp_year).change();
                        $('#pcc_cvv2').val('');
                        $('#pcc_type').setValue(CardType);

                    } else {
                        $('#pcc_no').val('').change();
                        $('#pcc_holder').val('').change();
                        $('#pcc_month').val('').change();
                        $('#pcc_year').val('').change();
                        $('#pcc_cvv2').val('').change();
                        $('#pcc_type').val('').change();
                    }

                    $('#pcc_cvv2').focus();
                }
            }

        }).blur(function (e) {
            $(this).val('');
        }).focus(function (e) {
            $(this).val('');
        });

        $('#pcc_no').change(function (e) {
            var cn = $(this).val();
            var ccn1 = cn.charAt(0);
            if (ccn1 == 4)
                CardType = 'Visa';
            else if (ccn1 == 5)
                CardType = 'MasterCard';
            else if (ccn1 == 3)
                CardType = 'Amex';
            else if (ccn1 == 6)
                CardType = 'Discover';
            else
                CardType = 'Visa';

            $('#pcc_type').setValue(CardType);
        });

    });
</script>


<script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"
        type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        $('.datetimepicker').tempusDominus = new TempusDominus({
            format: 'YYYY-MM-DD HH:mm'
        });
    });
</script>

