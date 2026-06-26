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
                                <b>Recibo de paraqueo</b><br>
                                <?= lang("Fecha") . ': ' . $this->tec->hrld($inv->date); ?> <br>
                                <?= lang('Tiquete N°') . ': ' . $inv->id; ?><br>
                                <br>
                            </p>
                            <div style="clear:both;"></div>
                            <table class="table table-striped table-condensed">
                                <thead>
                                <tr>
                                    <th class="text-center"
                                        style="width: 50%; border-bottom: 2px solid #ddd;"><?= lang('Placa Vehiculo'); ?></th>
                                    <th class="text-center"
                                        style="width: 12%; border-bottom: 2px solid #ddd;"><?= lang('Hora de entrada'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $hora = explode(' ',$inv->date);
                                $tax_summary = array();
                                    echo '<tr><td>' . $inv->hold_ref . '</td>';
                                    echo '<td style="text-align:center;">' . $hora[1] . '</td>';
                                    echo '</tr>';
                                   
                                ?>
                                </tbody>
                                
                            </table>
                           

                            <p style="margin-top:10px; text-align: center;">No pierda su ticket de estacionamiento</p>
                           
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
                                    <button type="button" class="btn btn-default"
                                            data-dismiss="modal"><?= lang('close'); ?></button>
                                </div>
                            </div>
                        <?php } else { ?>
                            <span class="pull-right col-xs-12">
                                <?php
                                if ($printer->type == "windows") {
                                    echo '<a href="' . site_url('pos/print_receipt/' . $inv->id . '/1') . '" id="print" class="btn btn-block btn-primary">' . lang("print") . '</a>';
                                    echo '<a target="_blank" href="' . site_url('pos/open_drawer/') . '" class="btn btn-block btn-default">' . lang("open_cash_drawer") . '</a>';
                                } elseif ($printer->type == "web") {
                                    echo '<button onclick="window.print();" class="btn btn-block btn-primary">' . lang("print") . '</button>';
                                } else {
                                    echo '<button onclick="return printReceipt()" class="btn btn-block btn-primary">' . lang("print") . '</button>';
                                    echo '<button onclick="return openCashDrawer()" class="btn btn-block btn-default">' . lang("open_cash_drawer") . '</button>';
                                }
                                ?>
                            </span>
                         
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
                                        url: "<?= site_url('pos/email_receipt') ?>",
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
