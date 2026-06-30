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
                        color: var(--nx-txt1, #1e293b);
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
                        background: var(--table-head-bg, #f5f5f5);
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
                <div class="row">
                    <div class="col-md-12">
                        <h2 style="text-align: center; width: 100%; color: green;"><i>Consulta de Articulos</i> </h2>
                        <div class="form-group">
                            <legend style="text-align: center">Busca por codigo del Producto *</legend>
                            <input id="search_code" class="form-control"/>
                        </div>
                        <label id="productoname"
                               style="width: 100%;text-align: center; font-size: 37px; color: red;"></label>
                        <label id="productoprice"
                               style="width: 100%;text-align: center; font-size: 37px; color: red;"></label>
                    </div>
                </div>
            </div>

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

        .modal-content {
            border-radius: 63px !important;
        }
    </style>

    </body>
    </html>
    <?php
}
?>
