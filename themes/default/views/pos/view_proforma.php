<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<?php
if ($modal) {
?>

<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-body">

            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
            </button>
            <?php
            } else {
            ?><!doctype html>
            <html<?= $Settings->rtl ? ' dir="rtl"' : ''; ?>>
            <head>
                <meta charset="utf-8">
                <title><?= $page_title . " " . lang("no") . " " . $inv->id; ?></title>
                <base href="<?= base_url() ?>"/>
                <meta http-equiv="cache-control" content="max-age=0"/>
                <meta http-equiv="cache-control" content="no-cache"/>
                <meta http-equiv="expires" content="0"/>
                <meta http-equiv="pragma" content="no-cache"/>
                <link rel="shortcut icon" href="<?= $assets ?>images/icon.png"/>
                <link href="<?= $assets ?>dist/css/styles.css" rel="stylesheet" type="text/css"/>
                <style type="text/css" media="all">
                    body {
                        color: #000;
                        font-size: 16px;
                    }

                    #wrapper {
                        max-width: 520px;
                        margin: 0 auto;
                        padding-top: 20px;
                    }

                    .btn {
                        margin-bottom: 5px;
                    }

                    .table {
                        border-radius: 3px;
                    }

                    .table th {
                        background: #f5f5f5;
                    }

                    .table th, .table td {
                        vertical-align: middle !important;
                    }

                    h3 {
                        margin: 5px 0;
                    }

                    @media print {
                        .no-print {
                            display: none;
                        }

                        #wrapper {
                            max-width: 480px;
                            width: 100%;
                            min-width: 250px;
                            margin: 0 auto;
                        }
                    }

                    <?php if($Settings->rtl) { ?>
                    .text-right {
                        text-align: left;
                    }

                    .text-left {
                        text-align: right;
                    }

                    tfoot tr th:first-child {
                        text-align: left;
                    }

                    <?php } else { ?>
                    tfoot tr th:first-child {
                        text-align: right;
                    }

                    <?php } ?>
                </style>
            </head>
            <body>
            <?php
            }
            ?>
            <div id="wrapper">
                <div id="receiptData"
                     style="width: auto; max-width: 580px; min-width: 250px; margin: 0 auto; background: white;">
                    <div class="no-print">
                        <?php if ($message) { ?>
                            <div class="alert alert-success">
                                <button data-dismiss="alert" class="close" type="button">×</button>
                                <?= is_array($message) ? print_r($message, true) : $message; ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div id="receipt-data">
                        <div>
                            <div style="text-align:center;">
                                <?php
                                if ($store) {
//                                    echo '<img src="' . base_url('uploads/' . $store->logo) . '" alt="' . $store->name . '">';
                                    echo '<p style="text-align:center;">';
                                    echo '<strong>' . $store->name . '</strong><br>';
                                    echo $store->address1 . '<br>' . $store->address2;
                                    echo $store->city . '<br>' . $store->phone;
                                    echo '</p>';
                                    echo '<p>' . nl2br($store->receipt_header) . '</p>';
                                }
                                ?>
                            </div>
                            <p>
                                <b>Proforma</b><br>
                                <?= lang("date") . ': ' . $this->tec->hrld($inv->date); ?> <br>
                                <?= lang('Proforma N°') . ': ' . $inv->id; ?><br>
                                <?= lang("customer") . ': ' . $inv->customer_name; ?> <br>
                                <?= lang("sales_person") . ': ' . $created_by->first_name . " " . $created_by->last_name; ?>
                                <br>
                            </p>
                            <div style="clear:both;"></div>
                            <table class="table table-striped table-condensed">
                                <thead>
                                <tr>
                                    <th class="text-center"
                                        style="width: 50%; border-bottom: 2px solid #ddd;"><?= lang('description'); ?></th>
                                    <th class="text-center"
                                        style="width: 12%; border-bottom: 2px solid #ddd;"><?= lang('quantity'); ?></th>
                                    <th class="text-center"
                                        style="width: 24%; border-bottom: 2px solid #ddd;"><?= lang('price'); ?></th>
                                    <th class="text-center"
                                        style="width: 26%; border-bottom: 2px solid #ddd;"><?= lang('subtotal'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $tax_summary = array();
                                foreach ($rows as $row) {
                                    if ($row->tax > 0) {
                                        $tt = "(G)";
                                    } else {
                                        $tt = "(E)";
                                    }
                                    echo '<tr><td>' . $row->product_name . ' ' . $tt . ' </td>';
                                    echo '<td style="text-align:center;">' . $this->tec->formatQuantity($row->quantity) . '</td>';
                                    echo '<td class="text-right">';
                                    echo $this->tec->formatMoney($row->net_unit_price + ($row->item_tax / $row->quantity)) . '</td><td class="text-right">' . $this->tec->formatMoney($row->subtotal) . '</td></tr>';
                                }
                                ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="2"><?= lang("total"); ?></th>
                                    <th colspan="2"
                                        class="text-right"><?= $this->tec->formatMoney($inv->total + $inv->product_tax); ?></th>
                                </tr>
                                <?php
                                if($inv->MontoExoneracion)
                                {
                                ?>
                                <tr>
                                    <th colspan="2"><?= lang("total"); ?> Exoneracion</th>
                                    <th colspan="2"
                                        class="text-right"><?= $this->tec->formatMoney($inv->MontoExoneracion);  ?></th>
                                </tr>
                                <?php } ?>
                                <?php
                                if ($inv->order_tax != 0) {
                                    echo '<tr><th colspan="2">' . lang("order_tax") . '</th><th colspan="2" class="text-right">' . $this->tec->formatMoney($inv->order_tax) . '</th></tr>';
                                }
                                
                                if ($inv->total_discount != 0) {
                                    echo '<tr><th colspan="2">' . lang("order_discount") . '</th><th colspan="2" class="text-right">' . $this->tec->formatMoney($inv->total_discount) . '</th></tr>';
                                }

                                if ($Settings->rounding) {
                                    $round_total = $this->tec->roundNumber($inv->grand_total, $Settings->rounding);
                                    $rounding = $this->tec->formatDecimal($round_total - $inv->grand_total);
                                    ?>
                                    <tr>
                                        <th colspan="2"><?= lang("rounding"); ?></th>
                                        <th colspan="2"
                                            class="text-right"><?= $this->tec->formatMoney($rounding); ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="2"><?= lang("grand_total"); ?></th>
                                        <th colspan="2"
                                            class="text-right"><?= $this->tec->formatMoney($inv->grand_total + $rounding); ?></th>
                                    </tr>
                                    <?php
                                } else {
                                    $round_total = $inv->grand_total;
                                    ?>
                                    <tr>
                                        <th colspan="2"><?= lang("grand_total"); ?></th>
                                        <th colspan="2"
                                            class="text-right"><?= $this->tec->formatMoney($inv->grand_total); ?></th>
                                    </tr>
                                    <?php
                                }
                                if ($inv->paid < $round_total) { ?>
                                    <tr>
                                        <th colspan="2"><?= lang("paid_amount"); ?></th>
                                        <th colspan="2"
                                            class="text-right"><?= $this->tec->formatMoney($inv->paid); ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="2"><?= lang("due_amount"); ?></th>
                                        <th colspan="2"
                                            class="text-right"><?= $this->tec->formatMoney($inv->grand_total - $inv->paid); ?></th>
                                    </tr>
                                <?php } ?>
                                </tfoot>
                            </table>
                            

                            <?= $inv->note ? '<p style="margin-top:10px; text-align: center;">' . $this->tec->decode_html($inv->note) . '</p>' : ''; ?>
                            
                        </div>
                        <div style="clear:both;"></div>
                    </div>

                    <!-- start -->
                    <div id="buttons" style="padding-top:10px; text-transform:uppercase;" class="no-print">
                        <hr>
                        <?php if ($modal) { ?>
                            <div class="btn-group btn-group-justified" role="group" aria-label="...">
                                <div class="btn-group" role="group">
                                    <?php
                                    if (!$Settings->remote_printing) {
                                        echo '<a href="' . site_url('pos/print_receipt/' . $inv->id . '/0') . '" id="print" class="btn btn-block btn-primary">' . lang("print") . '</a>';
                                    } elseif ($Settings->remote_printing == 1) {
                                        echo '<button onclick="window.print();" class="btn btn-block btn-primary">' . lang("print") . '</button>';
                                    } else {
                                        echo '<button onclick="return printReceipt()" class="btn btn-block btn-primary">' . lang("print") . '</button>';
                                    }
                                    ?>
                                </div>
                                <div class="btn-group" role="group">
                                    <a class="btn btn-block btn-success" href="#" id="email"><?= lang("email"); ?></a>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-default"
                                            data-dismiss="modal"><?= lang('close'); ?></button>
                                </div>
                            </div>
                        <?php } else { ?>
                            <span class="pull-right col-xs-12">
                                <?php
                                if ($printer->type == "windows") {
                                    echo '<a href="' . site_url('pos/print_receipt/' . $inv->id . '/true/21') . '" id="print" class="btn btn-block btn-primary">' . lang("print") . '</a>';
                                } elseif ($printer->type == "web") {
                                    echo '<button onclick="window.print();" class="btn btn-block btn-primary">' . lang("print") . '</button>';
                                } else {
                                    echo '<button onclick="return printReceipt()" class="btn btn-block btn-primary">' . lang("print") . '</button>';
                                }
                                ?>
                            </span>
                            <span class="pull-left col-xs-12"><a class="btn btn-block btn-success" href="#"
                                                                 id="email"><?= lang("email"); ?></a></span>
                            <span class="col-xs-12">
                                <a class="btn btn-block btn-warning"
                                   href="<?= site_url('pos'); ?>"><?= lang("back_to_pos"); ?></a>
                            </span>
                        <?php } ?>
                        <div style="clear:both;"></div>
                    </div>
                    <!-- end -->
                </div>
            </div>
            <!-- start -->
            <?php
            if (!$modal) {
                ?>
                <script type="text/javascript">
                    var base_url = '<?=base_url();?>';
                    var site_url = '<?=site_url();?>';
                    var dateformat = '<?=$Settings->dateformat;?>', timeformat = '<?= $Settings->timeformat ?>';
                    <?php unset($Settings->protocol, $Settings->smtp_host, $Settings->smtp_user, $Settings->smtp_pass, $Settings->smtp_port, $Settings->smtp_crypto, $Settings->mailpath, $Settings->timezone, $Settings->setting_id, $Settings->default_email, $Settings->version, $Settings->stripe, $Settings->stripe_secret_key, $Settings->stripe_publishable_key); ?>
                    var Settings = <?= json_encode($Settings); ?>;
                </script>
                <script src="<?= $assets ?>plugins/jQuery/jquery-3.7.1.min.js"></script>
                <script src="<?= $assets ?>dist/js/libraries.min.js" type="text/javascript"></script>
                <script src="<?= $assets ?>dist/js/scripts.min.js" type="text/javascript"></script>
                <?php
            }
            ?>
            <script type="text/javascript">
                $(document).ready(function () {
                    $('#print').click(function (e) {
                        e.preventDefault();
                        var link = $(this).attr('href');
                        $.get(link);
                        return false;
                    });
                    $('#email').click(function () {
                        bootbox.prompt({
                            title: "<?= lang("email_address"); ?>",
                            inputType: 'email',
                            value: "<?= $customer->email; ?>",
                            callback: function (email) {
                                if (email != null) {
                                    $.ajax({
                                        type: "post",
                                        url: "<?= site_url('pos/email_proforma') ?>",
                                        data: {<?= $this->security->get_csrf_token_name(); ?>:
                                    "<?= $this->security->get_csrf_hash(); ?>", email
                                :
                                    email, id
                                : <?= $inv->id; ?>},
                                    dataType: "json",
                                        success
                                :
                                    function (data) {
                                        bootbox.alert({message: data.msg, size: 'small'});
                                    }

                                ,
                                    error: function () {
                                        bootbox.alert({message: '<?= lang('ajax_request_failed'); ?>', size: 'small'});
                                        return false;
                                    }
                                })
                                    ;
                                }
                            }
                        });
                        return false;
                    });
                });
            </script>
            <?php include 'remote_printing.php'; ?>
            <?php
            if ($modal) {
            ?>
        </div>
    </div>
</div>
<?php
} else {
    ?>
    <!-- end -->
    <style>
        .bcimg {
            width: 61% !important;
            height: 57px !important;
        }
    </style>

    </body>
    </html>
    <?php
}
?>
