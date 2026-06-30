<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title . ' | ' . $Settings->site_name; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png" />
    <!-- Anti-FOUC: apply saved theme before first paint -->
    <script>
        (function(){var t=localStorage.getItem('nx-theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= $assets ?>dist/css/neurix-theme.css" rel="stylesheet" type="text/css" />
    <link href="<?= $assets ?>dist/css/styles.css" rel="stylesheet" type="text/css" />
    <?= $Settings->rtl ? '<link href="' . $assets . 'dist/css/rtl.css" rel="stylesheet" />' : ''; ?>
    <script src="<?= $assets ?>plugins/jQuery/jquery-3.7.1.min.js"></script>
</head>

<body class="skin-<?= $Settings->theme_style; ?> sidebar-collapse sidebar-mini pos">
    <div class="wrapper rtl rtl-inv">

        <header class="main-header">
            <a href="<?= site_url(); ?>" class="logo">
                <?php if ($store) { ?>
                    <span class="logo-mini"><?= html_escape($store->code); ?></span>
                    <span class="logo-lg"><?= html_escape($store->name); ?></span>
                <?php } else { ?>
                    <span class="logo-mini">POS</span>
                    <span class="logo-lg"><?= html_escape($Settings->site_name); ?></span>
                <?php } ?>
            </a>
            <nav class="navbar navbar-static-top" role="navigation">
                <ul class="nav navbar-nav pull-left">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><img src="<?= $assets; ?>images/<?= $Settings->selected_language; ?>.png" alt="<?= $Settings->selected_language; ?>"></a>
                        <ul class="dropdown-menu">
                            <?php
                            $scanned_lang_dir = array_map(function ($path) {
                                return basename($path);
                            }, glob(APPPATH . 'language/*', GLOB_ONLYDIR));
                            foreach ($scanned_lang_dir as $entry) {
                            ?>
                                <li><a href="<?= site_url('pos/language/' . $entry); ?>"><img src="<?= $assets; ?>images/<?= $entry; ?>.png" class="language-img"> &nbsp;&nbsp;<?= ucwords($entry); ?></a></li>
                            <?php } ?>
                        </ul>
                    </li>
                </ul>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li><a href="#" class="clock"></a></li>

                        <?php if ($Settings->theme_style == "purple") { ?>
                            <li><a target="_blank" title="Clientes" href="<?= site_url('customers'); ?>"><i class="fa fa-users"></i></a></li>
                        <?php } ?>
                        <?php if ($Settings->theme_style == "purple") { ?>
                            <li><a target="_blank" title="Lista de Ventas" href="<?= site_url('sales'); ?>"><i class="fa fa-shopping-cart"></i></a></li>
                        <?php } ?>
                        <?php if ($Admin) { ?>
                            <li><a href="<?= site_url('settings'); ?>"><i class="fa fa-cogs"></i></a></li>
                        <?php } ?>
                        <?php if ($this->db->dbdriver != 'sqlite3') { ?>
                            <li><a href="<?= site_url('pos/view_bill'); ?>" target="_blank"><i class="fa fa-desktop"></i></a></li>
                        <?php } ?>


                        <li><a href="" data-toggle="ajax" id="btnCloseregister"><?= lang('close_register'); ?></a></li>


                        <li><a href="" data-toggle="ajax" id="btnCreditNote">Notas de Credito</a></li>



                        <?php if ($suspended_sales) { ?>
                            <li class="dropdown notifications-menu" id="suspended_sales">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                    <i class="fa fa-bell-o"></i>
                                    <span class="label label-warning"><?= sizeof($suspended_sales); ?></span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li class="header">
                                        <input type="text" autocomplete="off" data-list=".list-suspended-sales" name="filter-suspended-sales" id="filter-suspended-sales" class="form-control input-sm kb-text clearfix" placeholder="<?= lang('filter_by_reference'); ?>">
                                    </li>
                                    <li>
                                        <ul class="menu">
                                            <li class="list-suspended-sales">
                                                <?php
                                                foreach ($suspended_sales as $ss) {
                                                    if ($Settings->propina_enable == "1")
                                                    {
                                                        echo '<a href="' . site_url('pos/?hold=' . $ss->id) . '" class="load_suspended">' . $this->tec->hrsd($ss->date) . ' (' . $ss->customer_name . ')<br><div class="bold">' . $ss->hold_ref."(".(strlen ($ss->note) > 0 ?$ss->note:"") .")". '</div></a>';
                                                    }else
                                                    {
                                                        echo '<a href="' . site_url('pos/?hold=' . $ss->id) . '" class="load_suspended">' . $this->tec->hrld($ss->date) . ' (' . $ss->customer_name . ')<br><div class="bold">' . $ss->hold_ref . '</div></a>';
                                                    }
                                                }
                                                ?>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="footer"><a href="<?= site_url('sales/opened'); ?>"><?= lang('view_all'); ?></a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <li class="dropdown user user-menu">
                            <a style="height: 50px;" href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="<?= base_url('uploads/avatars/thumbs/' . ($this->session->userdata('avatar') ? $this->session->userdata('avatar') : $this->session->userdata('gender') . '.png')) ?>" class="user-image" alt="Avatar" />

                            </a>
                            <ul class="dropdown-menu">
                                <li class="user-header">
                                    <img src="<?= base_url('uploads/avatars/' . ($this->session->userdata('avatar') ? $this->session->userdata('avatar') : $this->session->userdata('gender') . '.png')) ?>" class="img-circle" alt="Avatar" />
                                    <p>
                                        <?= html_escape($this->session->userdata('email')); ?>
                                        <small><?= lang('member_since') . ' ' . $this->session->userdata('created_on'); ?></small>
                                    </p>
                                </li>
                                <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>" class="btn btn-default btn-flat"><?= lang('profile'); ?></a>
                                    </div>
                                    <div class="pull-right">
                                        <a href="<?= site_url('logout'); ?>" class="btn btn-default btn-flat<?= $this->session->userdata('register_id') ? ' sign_out' : ''; ?>"><?= lang('sign_out'); ?></a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        <?php if (!isset($Settings->show_categories) || $Settings->show_categories != "0") { ?>
                            <li>
                                <a href="#" data-toggle="control-sidebar" class="sidebar-icon"><i class="fa fa-folder sidebar-icon"></i></a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </nav>
        </header>

        <aside class="main-sidebar <?= "menu_side_" . $Settings->theme_style ?>">
            <section class="sidebar">
                <ul class="sidebar-menu">
                    <li class="mm_welcome"><a href="<?= site_url(); ?>"><i class="fa fa-dashboard"></i>
                            <span><?= lang('dashboard'); ?></span></a></li>
                    <?php if ($Settings->multi_store && !$this->session->userdata('store_id')) { ?>
                        <li class="mm_stores"><a href="<?= site_url('stores'); ?>"><i class="fa fa-building-o"></i>
                                <span><?= lang('stores'); ?></span></a></li>
                    <?php } ?>
                    <li class="mm_pos"><a href="<?= site_url('pos'); ?>"><i class="fa fa-th"></i>
                            <span><?= lang('pos'); ?></span></a></li>

                    <?php if ($Admin) { ?>
                        <li class="treeview mm_products">
                            <a href="#">
                                <i class="fa fa-barcode"></i>
                                <span><?= lang('products'); ?></span>
                                <i class="fa fa-angle-left pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li id="products_index"><a href="<?= site_url('products'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_products'); ?></a></li>
                                <li id="products_add"><a href="<?= site_url('products/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_product'); ?></a></li>
                                <li id="products_import"><a href="<?= site_url('products/import'); ?>"><i class="fa fa-circle-o"></i> <?= lang('import_products'); ?></a></li>
                                <li class="divider"></li>
                                <li id="products_print_barcodes">
                                    <a onclick="window.open('<?= site_url('products/print_barcodes'); ?>', 'pos_popup', 'width=900,height=600,menubar=yes,scrollbars=yes,status=no,resizable=yes,screenx=0,screeny=0'); return false;" href="#"><i class="fa fa-circle-o"></i> <?= lang('print_barcodes'); ?></a>
                                </li>
                                <li id="products_print_labels">
                                    <a onclick="window.open('<?= site_url('products/print_labels'); ?>', 'pos_popup', 'width=900,height=600,menubar=yes,scrollbars=yes,status=no,resizable=yes,screenx=0,screeny=0'); return false;" href="#"><i class="fa fa-circle-o"></i> <?= lang('print_labels'); ?></a>
                                </li>
                            </ul>
                        </li>
                        <li class="treeview mm_categories">
                            <a href="#">
                                <i class="fa fa-folder"></i>
                                <span><?= lang('categories'); ?></span>
                                <i class="fa fa-angle-left pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li id="categories_index"><a href="<?= site_url('categories'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_categories'); ?></a></li>
                                <li id="categories_add"><a href="<?= site_url('categories/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_category'); ?></a></li>
                                <li id="categories_import"><a href="<?= site_url('categories/import'); ?>"><i class="fa fa-circle-o"></i> <?= lang('import_categories'); ?></a></li>
                            </ul>
                        </li>
                        <?php if ($this->session->userdata('store_id')) { ?>
                            <li class="treeview mm_sales">
                                <a href="#">
                                    <i class="fa fa-shopping-cart"></i>
                                    <span><?= lang('sales'); ?></span>
                                    <i class="fa fa-angle-left pull-right"></i>
                                </a>
                                <ul class="treeview-menu">
                                    <li id="sales_index"><a href="<?= site_url('sales'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_sales'); ?></a></li>
                                    <li id="sales_opened"><a href="<?= site_url('sales/opened'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_opened_bills'); ?></a></li>

                                    <?php if ($Settings->enable_layaway) { ?>
                                        <li id="sales_apartado"><a href="<?= site_url('sales/apartado'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_apartado_sales'); ?></a>
                                        </li>
                                    <?php } ?>

                                    <?php if ($Settings->enable_quote) { ?>
                                        <li id="sales_proforma"><a href="<?= site_url('sales/proforma'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_quotes_sales'); ?></a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </li>
                            <li class="treeview mm_creditnotes">
                                <a href="#">
                                    <i class="fa fa-shopping-cart"></i>
                                    <span><?= lang('credit_notes'); ?></span>
                                    <i class="fa fa-angle-left pull-right"></i>
                                </a>
                                <ul class="treeview-menu">
                                    <li id="creditnotes_index"><a href="<?= site_url('creditnotes'); ?>"><i class="fa fa-circle-o"></i> <?= lang('credit_notes'); ?></a></li>
                                    <li id="debitnotes_index"><a href="<?= site_url('debitnotes'); ?>"><i class="fa fa-circle-o"></i> Notas de Débito</a></li>
                                </ul>
                            </li>
                            <li class="treeview mm_purchases">
                                <a href="#">
                                    <i class="fa fa-plus"></i>
                                    <span><?= lang('purchases'); ?></span>
                                    <i class="fa fa-angle-left pull-right"></i>
                                </a>
                                <ul class="treeview-menu">
                                    <li id="purchases_index"><a href="<?= site_url('purchases'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_purchases'); ?></a></li>
                                    <li id="purchases_add"><a href="<?= site_url('purchases/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_purchase'); ?></a></li>
                                    <li class="divider"></li>
                                    <li id="purchases_expenses"><a href="<?= site_url('purchases/expenses'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_expenses'); ?></a></li>
                                    <li id="purchases_add_expense"><a href="<?= site_url('purchases/add_expense'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_expense'); ?></a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <li class="treeview mm_gift_cards">
                            <a href="#">
                                <i class="fa fa-credit-card"></i>
                                <span><?= lang('gift_cards'); ?></span>
                                <i class="fa fa-angle-left pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li id="gift_cards_index"><a href="<?= site_url('gift_cards'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_gift_cards'); ?></a></li>
                                <li id="gift_cards_add"><a href="<?= site_url('gift_cards/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_gift_card'); ?></a></li>
                            </ul>
                        </li>

                        <li class="treeview mm_auth mm_customers mm_suppliers">
                            <a href="#">
                                <i class="fa fa-users"></i>
                                <span><?= lang('people'); ?></span>
                                <i class="fa fa-angle-left pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li id="auth_users"><a href="<?= site_url('users'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_users'); ?></a></li>
                                <li id="auth_add"><a href="<?= site_url('users/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_user'); ?></a></li>
                                <li class="divider"></li>
                                <li id="customers_index"><a href="<?= site_url('customers'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_customers'); ?></a></li>
                                <li id="customers_add"><a href="<?= site_url('customers/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_customer'); ?></a></li>
                                <li class="divider"></li>
                                <li id="suppliers_index"><a href="<?= site_url('suppliers'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_suppliers'); ?></a></li>
                                <li id="suppliers_add"><a href="<?= site_url('suppliers/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_supplier'); ?></a></li>
                            </ul>
                        </li>

                        <li class="treeview mm_settings">
                            <a href="#">
                                <i class="fa fa-cogs"></i>
                                <span><?= lang('settings'); ?></span>
                                <i class="fa fa-angle-left pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li id="settings_index"><a href="<?= site_url('settings'); ?>"><i class="fa fa-circle-o"></i> <?= lang('settings'); ?></a></li>
                                <li class="divider"></li>
                                <?php if ($Settings->multi_store) { ?>
                                    <li id="settings_stores"><a href="<?= site_url('settings/stores'); ?>"><i class="fa fa-circle-o"></i> <?= lang('stores'); ?></a></li>
                                    <li id="settings_add_store"><a href="<?= site_url('settings/add_store'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_store'); ?></a></li>
                                    <li class="divider"></li>
                                <?php } ?>
                                <li id="settings_printers"><a href="<?= site_url('settings/printers'); ?>"><i class="fa fa-circle-o"></i> <?= lang('printers'); ?></a></li>
                                <li id="settings_add_printer"><a href="<?= site_url('settings/add_printer'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_printer'); ?></a></li>
                                <li class="divider"></li>
                                <?php if ($this->db->dbdriver != 'sqlite3') { ?>
                                    <li id="settings_backups"><a href="<?= site_url('settings/backups'); ?>"><i class="fa fa-circle-o"></i> <?= lang('backups'); ?></a></li>
                                <?php } ?>
                                <!-- <li id="settings_updates"><a href="<?= site_url('settings/updates'); ?>"><i class="fa fa-circle-o"></i> <?= lang('updates'); ?></a></li> -->
                            </ul>
                        </li>
                        <li class="treeview mm_reports">
                            <a href="#">
                                <i class="fa fa-bar-chart-o"></i>
                                <span><?= lang('reports'); ?></span>
                                <i class="fa fa-angle-left pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li id="reports_credit_customers"><a href="<?= site_url('reports/credit_customers'); ?>"><i class="fa fa-circle-o"></i> Estado de cuenta clientes</a></li>
                                <li id="reports_daily_sales"><a href="<?= site_url('reports/daily_sales'); ?>"><i class="fa fa-circle-o"></i> <?= lang('daily_sales'); ?></a></li>
                                <li id="reports_monthly_sales"><a href="<?= site_url('reports/monthly_sales'); ?>"><i class="fa fa-circle-o"></i> <?= lang('monthly_sales'); ?></a></li>
                                <!--                                    <li id="reports_index"><a href="<?= site_url('reports'); ?>"><i
                                                class="fa fa-circle-o"></i> <?= lang('sales_report'); ?></a></li>-->
                                <li class="divider"></li>
                                <li id="reports_compras_electronicas"><a href="<?= site_url('reports/compras_electronicas'); ?>"><i class="fa fa-circle-o"></i> Compras mensuales</a></li>
                                <li class="divider"></li>
                                <li id="reports_payments"><a href="<?= site_url('reports/payments'); ?>"><i class="fa fa-circle-o"></i> <?= lang('payments_report'); ?></a></li>
                                <li class="divider"></li>
                                <li id="reports_registers"><a href="<?= site_url('reports/registers'); ?>"><i class="fa fa-circle-o"></i> <?= lang('registers_report'); ?></a></li>
                                <li class="divider"></li>
                                <li id="reports_top_products"><a href="<?= site_url('reports/top_products'); ?>"><i class="fa fa-circle-o"></i> <?= lang('top_products'); ?></a></li>
                                <li id="reports_products"><a href="<?= site_url('reports/products'); ?>"><i class="fa fa-circle-o"></i> <?= lang('products_report'); ?></a></li>
                            </ul>
                        </li>
                    <?php } else { ?>
                        <li class="mm_products"><a href="<?= site_url('products'); ?>"><i class="fa fa-barcode"></i>
                                <span><?= lang('products'); ?></span></a></li>
                        <li class="mm_categories"><a href="<?= site_url('categories'); ?>"><i class="fa fa-folder-open"></i>
                                <span><?= lang('categories'); ?></span></a></li>
                        <?php if ($this->session->userdata('store_id')) { ?>
                            <li class="treeview mm_sales">
                                <a href="#">
                                    <i class="fa fa-shopping-cart"></i>
                                    <span><?= lang('sales'); ?></span>
                                    <i class="fa fa-angle-left pull-right"></i>
                                </a>
                                <ul class="treeview-menu">
                                    <li id="sales_index"><a href="<?= site_url('sales'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_sales'); ?></a></li>
                                    <li id="sales_opened"><a href="<?= site_url('sales/opened'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_opened_bills'); ?></a></li>
                                    <li id="sales_opened"><a href="<?= site_url('sales/proforma'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_quotes_sales'); ?></a></li>
                                    <li id="sales_opened"><a href="<?= site_url('sales/apartado'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_apartado_sales'); ?></a></li>

                                </ul>
                            </li>
                            <li class="treeview mm_purchases">
                                <a href="#">
                                    <i class="fa fa-plus"></i>
                                    <span><?= lang('expenses'); ?></span>
                                    <i class="fa fa-angle-left pull-right"></i>
                                </a>
                                <ul class="treeview-menu">
                                    <li id="purchases_expenses"><a href="<?= site_url('purchases/expenses'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_expenses'); ?></a></li>
                                    <li id="purchases_add_expense"><a href="<?= site_url('purchases/add_expense'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_expense'); ?></a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <li class="treeview mm_gift_cards">
                            <a href="#">
                                <i class="fa fa-credit-card"></i>
                                <span><?= lang('gift_cards'); ?></span>
                                <i class="fa fa-angle-left pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li id="gift_cards_index"><a href="<?= site_url('gift_cards'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_gift_cards'); ?></a></li>
                                <li id="gift_cards_add"><a href="<?= site_url('gift_cards/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_gift_card'); ?></a></li>
                            </ul>
                        </li>
                        <li class="treeview mm_customers">
                            <a href="#">
                                <i class="fa fa-users"></i>
                                <span><?= lang('customers'); ?></span>
                                <i class="fa fa-angle-left pull-right"></i>
                            </a>
                            <ul class="treeview-menu">
                                <li id="customers_index"><a href="<?= site_url('customers'); ?>"><i class="fa fa-circle-o"></i> <?= lang('list_customers'); ?></a></li>
                                <li id="customers_add"><a href="<?= site_url('customers/add'); ?>"><i class="fa fa-circle-o"></i> <?= lang('add_customer'); ?></a></li>
                            </ul>
                        </li>
                    <?php } ?>
                </ul>
            </section>
        </aside>

        <div class="<?= $Settings->theme_style == "purple" ? "content_wrapper_" . $Settings->theme_style : "content-wrapper" ?>">


            <table style="width:100%;" class="layout-table">
                <tr>
                    <td style="width: 460px;">

                        <div id="pos" style="padding:0;">
                            <?php if ($t_nc) { ?>
                                <?= form_open('pos/nota_credito', 'id="pos-sale-form"'); ?>
                                <?= form_hidden('t_nc', set_value('t_nc', $t_nc), 'id="t_nc"'); ?>
                            <?php } else { ?>
                                <?= form_open('pos', 'id="pos-sale-form"'); ?>
                            <?php } ?>
                            <div class="well well-sm" id="leftdiv">
                                <div id="lefttop" style="margin-bottom:5px;">
                                    <div class="form-group" style="margin-bottom:5px;">
                                        <?php if ($Settings->propina_enable == '1') { ?>
                                            <button type="button" class="btn btn-warning  btn-block btn-flat external add_tips" id="add_tips">Servicio (<?php echo $Settings->propina_rate ?>%)
                                            </button>
                                        <?php } ?>
                                        <button type="button" class="btn btn-success  btn-block btn-flat external boton_nuevo_cliente" id="add-customer" data-toggle="modal" data-target="#myModal">Nuevo Cliente
                                        </button>
                                        <button type="button" class="btn btn-info btn-block btn-flat boton_agregar_notas" data-toggle="modal" data-target="#ModalNotes">Agregar Notas
                                        </button>

                                        <button type="button" class="" style="visibility: hidden;" data-toggle="modal" data-target="#exoneracionModal" id="boiniciomodal">Exoneracion
                                        </button>

                                        <?php if ($Settings->enablebtn_retiro) { ?>
                                            <button type="button" class="btn btn-danger btn-block btn-flat add_retiro" id="retiro" data-toggle="modal" data-target="#exampleModal" data-whatever="@Retiro">
                                                Retiro
                                            </button>
                                        <?php } ?>
                                        <?php if ($Settings->enablebtn_deposito) { ?>
                                            <button type="button" class="btn btn-warning  btn-block btn-flat add_deposito" id="deposito" data-toggle="modal" data-target="#exampleModal" data-whatever="@Deposito">
                                                Deposito
                                            </button>
                                        <?php } ?>
                                        <!--<button type="button" style="width: 15%; height: 34px; float: left; margin-top: 5px;" class="btn btn-warning  btn-block btn-flat" data-toggle="modal" data-target="#ModalNotes">Agregar Exoneracion</button>-->

                                        <div style="clear:both;"></div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:5px;">
                                        <input type="hidden" name="total_tax" id="total_tax" value="<?= $total_tax; ?>">
                                        <input type="hidden" name="ETipoDocumento" id="ETipoDocumento">
                                        <input type="hidden" name="ENombreInstitucion" id="ENombreInstitucion">
                                        <input type="hidden" name="ENumeroDocumento" id="ENumeroDocumento">
                                        <input type="hidden" name="EFechaEmision" id="EFechaEmision">
                                        <input type="hidden" name="PorcentajeExoneracion" id="PorcentajeExoneracion">
                                        <input type="hidden" name="MontoExoneracion" id="MontoExoneracion" value="0">

                                        <?php
                                        $cuss = array();
                                        if ($actividadeconomica) {
                                            foreach ($actividadeconomica as $actividad) {

                                                $cuss[$actividad->id_actividad] = $actividad->descripcion . ' (' . $actividad->id_actividad . ')';
                                            }
                                        }
                                        ?>
                                        <?= form_dropdown('actividad_id', $cuss, set_value('actividad_id', $Settings->default_actividad), 'id="actividad_id" required="required" class="form-control select2" style="display:block;width:100%;float: left;"'); ?>
                                        <div style="clear:both;"></div>
                                    </div>
                                    <div class="form-group" style="margin-bottom:5px;">
                                        <?php
                                        $cus = array();
                                        foreach ($customers as $customer) {
                                            $cus[$customer->id] = $customer->name . ' (' . $customer->cf2 . ' - ' . $customer->email . ')';
                                        }
                                        ?>




                                        <?= form_dropdown('customer_id', $cus, set_value('customer_id', $Settings->default_customer), 'id="spos_customer" data-placeholder="' . lang("select") . ' ' . lang("customer") . '" required="required" class="form-control select2" style="display:block;width:100%;float: left;"'); ?>
                                        <div style="clear:both;"></div>
                                    </div>
                                        <input type="hidden" name ="input_transfer_table" id="input_transfer_table">
                                    <?php if ($eid && $Admin) { ?>
                                        <div class="form-group" style="margin-bottom:5px;">
                                            <?= form_input('date', set_value('date', $sale->date), 'id="date" required="required" readonly="readonly" class="form-control"'); ?>
                                        </div>
                                    <?php } ?>
                                    <?php if ($t_nc) { ?>
                                        <div class="form-group" style="margin-bottom:5px;">
                                            <input type="text" name="hold_ref" required="required" value="" id="hold_ref" class="form-control kb-text" placeholder="<?= lang("reason_credit_note") ?>" />
                                        </div>
                                    <?php } ?>

                                    <input type="hidden" name="token_post" value="<?= md5(date('Y-m-d H:i:s')) ?>" />
                                    <?php if ($Settings->enable_parquimetro == "0") { ?>
                                        <?php if (!$t_nc && !$apa) { ?>
                                            <div class="form-group" style="margin-bottom:5px; overflow: hidden;">
                                                <select id="tipo_precio" style="float: left; width: 15%;" class="form-control ">
                                                    <option value="price"><?= lang('price') ?></option>
                                                    <option value="offer_price"><?= lang('offer_price') ?></option>
                                                    <option value="price_rate"><?= lang('price_rate') ?></option>
                                                </select>
                                                <input type="text" style="float: left; width: 85%;" name="code" id="add_item" class="form-control" placeholder="<?= lang('search__scan') ?>" />
                                            </div>
                                        <?php } ?>
                                    <?php } ?>

                                </div>
                                <div id="printhead" class="print">
                                    <?= $Settings->header; ?>
                                    <p><?= lang('date'); ?>: <?= date($Settings->dateformat) ?></p>
                                </div>
                                <div id="print" class="fixed-table-container <?= "panel_" . $Settings->theme_style ?>">
                                    <div id="list-table-div">
                                        <div class="fixed-table-header">
                                            <table class="table table-striped table-condensed table-hover list-table" style="margin:0;">
                                                <thead>
                                                    <tr class="success">
                                                        <th><?= lang('product') ?></th>
                                                        <?php if ($Settings->enable_fractions == 1) { ?>
                                                            <th style="width: 10%;text-align:center;">PxFR</th>
                                                            <th style="width: 10%;text-align:center;">PxUnd</th>
                                                        <?php } ?>
                                                        <th style="width: 10%;text-align:center;"><?= lang('price') ?></th>
                                                        <th style="width: 5%;text-align:center;">QTY.</th>
                                                        <th style="width: 5%;text-align:center;">UD</th>
                                                        <th style="width: 10%;text-align:center;"><?= lang('subtotal') ?></th>
                                                        <th style="width: 20px;" class="satu"><i class="fa fa-trash-o"></i></th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                        <table id="posTable" class="table table-striped table-condensed table-hover list-table" style="margin:0px;" data-height="100">
                                            <thead>
                                                <tr class="success">
                                                    <th><?= lang('product') ?></th>
                                                    <?php if ($Settings->enable_fractions == 1) { ?>
                                                        <th style="width: 10%;text-align:center;">PxFR</th>
                                                        <th style="width: 10%;text-align:center;">PxUnd</th>
                                                    <?php } ?>
                                                    <th style="width: 10%;text-align:center;"><?= lang('price') ?></th>
                                                    <th style="width: 5%;text-align:center;">QTY.</th>
                                                    <th style="width: 5%;text-align:center;">UD</th>
                                                    <th style="width: 10%;text-align:center;"><?= lang('subtotal') ?></th>
                                                    <?php if (!$apa) { ?>
                                                        <th style="width: 20px;" class="satu"><i class="fa fa-trash-o"></i>
                                                        <?php } ?>
                                                        </th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                    <div style="clear:both;"></div>

                                    <div id="totaldiv">
                                        <table id="totaltbl" class="table table-condensed totals" style="margin-bottom:10px;">
                                            <tbody>
                                                <tr class="info">
                                                    <td width="25%"><?= lang('total_items') ?></td>
                                                    <td class="text-right" style="padding-right:10px;"><span id="count">0</span>
                                                    </td>
                                                    <td width="25%"><?= lang('total') ?></td>
                                                    <td class="text-right" colspan="2"><span id="total">0</span></td>
                                                </tr>
                                                <tr class="info">
                                                    <td colspan="2" width="25%"><a href="#" id="add_discount"><?= lang('discount') ?> General</a>
                                                    </td>
                                                    <td colspan="2" class="text-right" style="padding-right:10px;"><span id="ds_con">0</span></td>
                                                    <!--                                                        <td width="25%"><a href="#" id="add_tax"><?= lang('order_tax') ?></a></td>
                                                        <td class="text-right"><span id="ts_con">0</span></td>-->
                                                </tr>
                                                <tr class="success">
                                                    <td colspan="2" style="font-weight:bold;">
                                                        <?= lang('total_payable') ?>
                                                        <a role="button" data-toggle="modal" data-target="#noteModal">
                                                            <i class="fa fa-comment"></i>
                                                        </a>
                                                    </td>
                                                    <td class="text-right" colspan="2" style="font-weight:bold;"><span id="total-payable">0</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div id="botbuttons" class="col-xs-12 text-center">
                                    <div class="row">
                                        <div class="
                                                 <?php if ($Admin) { ?>
                                                     col-xs-4
                                                 <?php } else { ?>
                                                     col-xs-4
                                                 <?php } ?>

                                                 " style="padding: 0;">
                                            <?php if ($Settings->enable_layaway === "1" && $Settings->enable_quote === "1") { ?>
                                                <div class="btn-group-vertical btn-block">
                                                    <?php if (!$t_nc && !$apa) { ?>

                                                        <button type="button" class="btn btn-warning btn-block btn-flat" id="suspend" style="width: 50%; float: left;"><?= $Settings->enable_parquimetro == "0" ? lang('hold') : "Registrar Vehiculo" ?></button>
                                                    <?php } ?>

                                                    <?php if (!$t_nc && !$apa) { ?>
                                                        <button type="button" style="width: 50%; float: left; height: 50px; margin-top: 0px;" class="btn bg-purple btn-block btn-flat" id="quotes">
                                                            Proforma
                                                        </button>
                                                    <?php } ?>

                                                    <?php if (!$apa) { ?>
                                                        <button type="button" class="btn btn-danger btn-block btn-flat" id="reset" style="width: 50%; float: left; <?php if ($t_nc) { ?> height: 67px;<?php } ?>"><?= lang('cancel'); ?></button>
                                                    <?php } else { ?>
                                                        <button type="button" class="btn btn-danger btn-block btn-flat" id="reset" style="width: 100%; float: left; height: 97px;"><?= lang('cancel'); ?></button>
                                                    <?php } ?>

                                                    <?php if (!$t_nc && !$apa) { ?>
                                                        <button type="button" style="width: 50%; float: left; height: 48px;" class="btn bg-navy btn-block btn-flat" id="Apartado">
                                                            Apartado
                                                        </button>
                                                    <?php } ?>

                                                </div>
                                            <?php } elseif ($Settings->enable_layaway === "1" && $Settings->enable_quote === "0") { ?>
                                                <div class="btn-group-vertical btn-block">
                                                    <?php if (!$apa) { ?>
                                                        <button type="button" class="btn btn-danger btn-block btn-flat" id="reset" style="    margin-top: -1px; height: 47px; width: 50%; float: left; <?php if ($t_nc) { ?> height: 67px;<?php } ?>"><?= lang('cancel'); ?></button>
                                                    <?php } else { ?>
                                                        <button type="button" class="btn btn-danger btn-block btn-flat" id="reset" style="    margin-top: -1px; height: 47px; width: 100%; float: left; height: 97px;"><?= lang('cancel'); ?></button>
                                                    <?php } ?>
                                                    <?php if (!$t_nc && !$apa) { ?>
                                                        <button type="button" class="btn btn-warning btn-block btn-flat" id="suspend" style="height: 47px; width: 50%; float: left;"><?= $Settings->enable_parquimetro == "0" ? lang('hold') : "Registrar Vehiculo" ?></button>
                                                    <?php } ?>
                                                    <?php if (!$t_nc && !$apa) { ?>
                                                        <button type="button" style="width: 100%; float: left; height: 52px;" class="btn bg-navy btn-block btn-flat" id="Apartado">
                                                            Apartado
                                                        </button>
                                                    <?php } ?>


                                                </div>
                                            <?php } elseif ($Settings->enable_layaway === "0" && $Settings->enable_quote === "1") { ?>
                                                <div class="btn-group-vertical btn-block">
                                                    <?php if ($is_suspender != 'N') { ?>
                                                        <input id="id_cuenta_suspendida" type="hidden" value="<?= $is_suspender ?>" />
                                                        <span style="max-height: 30px;" class="btn btn-info btn-block btn-flat" id="print_suspend">Imprimir</span>
                                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#cuentasModal" onclick="cargarComboFac();">Dividir cuentas</button>
                                                    <?php } ?>
                                                    <?php if (!$apa) { ?>
                                                        <button type="button" class="btn btn-danger btn-block btn-flat" id="reset" style="    margin-top: -1px; height: 47px; width: 50%; float: left; <?php if ($t_nc) { ?> height: 67px;<?php } ?>"><?= lang('cancel'); ?></button>
                                                    <?php } else { ?>
                                                        <button type="button" class="btn btn-danger btn-block btn-flat" id="reset" style="    margin-top: -1px; height: 47px; width: 100%; float: left; height: 97px;"><?= lang('cancel'); ?></button>
                                                    <?php } ?>
                                                    <?php if (!$t_nc && !$apa) { ?>
                                                        <button type="button" class="btn btn-warning btn-block btn-flat" id="suspend" style="height: 47px; width: 50%; float: left;"><?= $Settings->enable_parquimetro == "0" ? lang('hold') : "Registrar Vehiculo" ?></button>
                                                    <?php } ?>

                                                    <?php if (!$t_nc && !$apa) { ?>
                                                        <button type="button" style="width: 100%; float: left; height: 50px; margin-top: 0px;" class="btn bg-purple btn-block btn-flat" id="quotes">
                                                            Proforma
                                                        </button>
                                                    <?php } ?>

                                                </div>
                                            <?php } else { ?>
                                                <div class="col-xs-12" style="padding: 0;">
                                                    <div class="btn-group-vertical btn-block">
                                                        <?php if (!$t_nc) { ?>
                                                            <?php
                                                            if ($Settings->enable_parquimetro == "1") {
                                                                $etiquetaBtn = "Registrar Vehiculo";
                                                            } elseif ($Settings->propina_enable == "1") {
                                                                $etiquetaBtn = "Mandar a Cocina";
                                                            } else {
                                                                $etiquetaBtn = lang('hold');
                                                            }

                                                            ?>
                                                            <button type="button" class="btn btn-warning btn-block btn-flat" id="suspend"><?= $etiquetaBtn ?></button>
                                                        <?php } ?>
                                                        <button <?php if ($t_nc) { ?> style="height: 67px;" <?php } ?> type="button" class="btn btn-danger btn-block btn-flat" id="reset"><?= lang('cancel'); ?></button>

                                                    </div>
                                                </div>
                                            <?php } ?>

                                        </div>
                                        <?php if (!$t_nc) { ?>
                                            <div class="col-xs-4" style="padding: 0 5px;">
                                                <div class="btn-group-vertical btn-block">
                                                    <button type="button" class="btn bg-purple btn-block btn-flat" id="print_order"><?= lang('print_order'); ?></button>

                                                    <button type="button" class="btn bg-navy btn-block btn-flat" id="print_bill"><?= lang('print_bill'); ?></button>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <?php if ($Admin || $Settings->enable_btn_pay == 1) { ?>
                                            <div <?php if (!$t_nc) { ?> class="col-xs-4" <?php } else { ?> class="col-xs-8" style="width: 50%" <?php } ?> style="padding: 0;">
                                                <?php if (!$apa) { ?>
                                                    <button type="button" class="btn btn-success btn-block btn-flat" id="<?= $eid ? 'submit-sale' : 'payment'; ?>" style="height:67px;">
                                                        <?php if ($t_nc) { ?>
                                                            <?= lang('credit_note') ?>
                                                        <?php } else { ?>
                                                            <?= $eid ? lang('submit') : lang('payment'); ?>
                                                        <?php } ?>

                                                    </button>
                                                <?php } else { ?>
                                                    <button type="button" class="btn btn-success btn-block btn-flat" id="submit-sale" style="height:97px;">Realizar Factura de Apartado
                                                    </button>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>


                                    </div>

                                </div>
                                <div class="clearfix"></div>
                                <span id="hidesuspend"></span>

                                <div id="payment-con">
                                    <input type="hidden" name="amount" id="amount_val" value="<?= $eid ? $sale->paid : ''; ?>" />
                                    <input type="hidden" name="amount2" id="amount_val2" value="<?= $apa_grand_total; ?>" />
                                    <input type="hidden" name="amount3" id="amount_val3" value="" />
                                    <input type="hidden" name="amount4" id="amount_val4" value="" />
                                    <input type="hidden" name="shipping_method" id="shipping_method_val" value="" />
                                    <input type="hidden" name="freferencia1" id="freferencia1" value="" />
                                    <input type="hidden" name="freferencia2" id="freferencia2" value="" />
                                    <input type="hidden" name="freferencia3" id="freferencia3" value="" />
                                    <input type="hidden" name="balance_amount" id="balance_val" value="" />
                                    <input type="hidden" name="paid_by" id="paid_by_val" value="cash" />
                                    <input type="hidden" name="paid_by2" id="paid_by_val2" value="CC" />
                                    <input type="hidden" name="paid_by3" id="paid_by_val3" value="CC" />
                                    <input type="hidden" name="paid_by4" id="paid_by_val4" value="CC" />
                                    <input type="hidden" name="cc_no" id="cc_no_val" value="" />
                                    <input type="hidden" name="paying_gift_card_no" id="paying_gift_card_no_val" value="" />
                                    <input type="hidden" name="cc_holder" id="cc_holder_val" value="" />
                                    <input type="hidden" name="cheque_no" id="cheque_no_val" value="" />
                                    <input type="hidden" name="cc_month" id="cc_month_val" value="" />
                                    <input type="hidden" name="cc_year" id="cc_year_val" value="" />
                                    <input type="hidden" name="cc_type1" id="cc_type_val1" value="" />
                                    <input type="hidden" name="cc_type2" id="cc_type_val2" value="" />
                                    <input type="hidden" name="cc_type3" id="cc_type_val3" value="" />
                                    <input type="hidden" name="cc_cvv2" id="cc_cvv2_val" value="" />
                                    <input type="hidden" name="balance" id="balance_val" value="" />
                                    <input type="hidden" name="payment_note" id="payment_note_val" value="" />
                                </div>
                                <input type="hidden" name="customer" id="customer" value="<?= $Settings->default_customer ?>" />
                                <input type="hidden" name="order_tax" id="tax_val" value="" />
                                <input type="hidden" name="order_discount" id="discount_val" value="" />
                                <input type="hidden" name="count" id="total_item" value="" />
                                <input type="hidden" name="did" id="is_delete" value="<?= (int)$sid; ?>" />
                                <input type="hidden" name="quo" id="quo" value="<?= (int)$quo; ?>" />
                                <input type="hidden" name="eid" id="is_delete" value="<?= (int)$eid; ?>" />
                                <input type="hidden" name="rid" id="is_delete" value="<?= (int)$rid; ?>" />
                                <input type="hidden" name="apa" id="apa" value="<?= (int)$apa; ?>" />
                                <input type="hidden" name="apapost" id="apapost" value="<?= (int)$apa; ?>" />
                                <input type="hidden" name="total_items" id="total_items" value="0" />
                                <input type="hidden" name="total_quantity" id="total_quantity" value="0" />
                                <input type="submit" id="submit" value="Submit Sale" style="display: none;" />
                            </div>

                            <!-- Modal -->
                            <div class="modal" data-easein="flipYIn" id="ModalNotes" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLongTitle">Notas y otros textos</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <?php if (!$t_nc) { ?>
                                                <input style="width: 49%; float:left; margin-right: 2%;" type="text" name="hold_ref" value="<?= $reference_note; ?>" id="hold_ref" class="form-control kb-text" placeholder="<?= $Settings->enable_parquimetro == "0" ? lang('reference_note') : "Placa del Vehiculo" ?>" />
                                            <?php } ?>
                                            <input style="width: 49%; float:left;" type="text" name="spos_note" id="spos_note" value="<?= $Settings->enable_parquimetro == "0" ? "" : "Placa numero " . $reference_note ?>" class="form-control kb-text" placeholder="Nota de La factura" <?= $Settings->enable_parquimetro == "0" ? "" : "readonly='readonly'" ?> />
                                            <div class="col-md-12">
                                                <hr style="border: 1px solid var(--nx-border);" />
                                                <h4>Otros Textos
                                                    <small>(Agregue otros textos en esta seccion estos apareceran en el xml
                                                        del tiquete electronico)
                                                    </small>
                                                </h4>
                                                <button type="button" style="width: 100%; height: 34px; float: left; margin-top: 5px;" class="btn btn-info btn-block btn-flat agregatexto">Agregar otro
                                                    texto
                                                </button>
                                                <table style="width: 100%">
                                                    <thead>
                                                        <tr style="background: var(--table-head-bg); font-size: 16px; font-weight: bold;">
                                                            <td style="width: 25%; text-align: center;">Asunto / Codigo</td>
                                                            <td style="width: 73%; border-left: 1px solid var(--nx-border); text-align: center;">
                                                                Texto
                                                            </td>
                                                            <td style="width: 2%; border-left: 1px solid var(--nx-border); text-align: center;">
                                                                <i class="fa fa-trash-o" style="padding: 0 5px; font-size: 18px;"></i></td>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="cuerpo_otrotexto">
                                                        <?php if (isset($otrostextos) and $otrostextos) { ?>

                                                            <?php foreach ($otrostextos as $texto) { ?>
                                                                <tr>
                                                                    <td style="width: 25%; font-size: 22px;"><input placeholder="Orden de compras" name="titulo_texto[]" style="width: 100%;font-size: 13px;" value="<?= $texto->titulo_texto ?>"></td>
                                                                    <td style="width: 75%; font-size: 22px;"><input placeholder="123456789" name="otrotexto[]" style="width: 100%;font-size: 13px;" value="<?= $texto->otrotexto ?>"></td>
                                                                    <td><span style="padding: 0 5px;"><i class="fa fa-trash-o eliminatexto" style="font-size: 18px; cursor:pointer;"></i></span>
                                                                    </td>
                                                                </tr>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>

                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" data-dismiss="modal">Aceptar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?= form_close(); ?>
                            <script>
                                $(function() {
                                    $('.agregatexto').on('click', function() {
                                        $('#cuerpo_otrotexto').append('<tr>\n\
                                                    <td style="width: 25%; font-size: 22px;"><input placeholder="orden_de_compras" name="titulo_texto[]" style="width: 100%;font-size: 13px;"></td>\n\
                                                    <td style="width: 75%; font-size: 22px;"><input placeholder="123456789" name="otrotexto[]" style="width: 100%;font-size: 13px;"></td> \n\
                                                    <td><span style="padding: 0 5px;"><i class="fa fa-trash-o eliminatexto" style="font-size: 18px; cursor:pointer;"></i></span></td></tr>');
                                    });
                                });
                                $('td').on('click', '.eliminatexto', function() {
                                    $(this).closest('tr').remove();
                                });

                                $(function() {
                                    $('#add_exoneracion').on('click', function() {
                                        $("#ETipoDocumento").val($("#TipoDocumentoE").val());
                                        $("#ENumeroDocumento").val($("#NumeroDocumentoE").val());
                                        $("#ENombreInstitucion").val($("#NombreInstitucionE").val());
                                        $("#EFechaEmision").val($("#FechaEmisionE").val());
                                        $("#PorcentajeExoneracion").val($("#PorcentajeExoneracionE").val());
                                        //$('#exoneracionModal').removeClass('modal in');
                                        //$('#exoneracionModal').toggleClass('modal');
                                        $('#exoneracionModal').modal("hide");
                                        loadItems();

                                        // alert(document.getElementById('exoneracionModal').className);

                                    });
                                })
                            </script>

                        </div>

                    </td>
                    <td>
                        <div class="contents" id="right-col">
                            <div id="item-list">
                                <div class="items">
                                    <?php if (!$t_nc) { ?>
                                        <?php echo $products; ?>
                                    <?php } ?>

                                </div>
                            </div>
                            <div class="product-nav">
                                <div class="btn-group btn-group-justified">
                                    <div class="btn-group">
                                        <button style="z-index:10002;" class="btn btn-warning pos-tip btn-flat" type="button" id="previous"><i class="fa fa-chevron-left"></i></button>
                                    </div>
                                    <div class="btn-group">
                                        <button style="z-index:10003;" class="btn btn-success pos-tip btn-flat" type="button" id="sellGiftCard"><i class="fa fa-credit-card" id="addIcon"></i> <?= lang('sell_gift_card') ?>
                                        </button>
                                    </div>
                                    <div class="btn-group">
                                        <button style="z-index:10004;" class="btn btn-warning pos-tip btn-flat" type="button" id="next"><i class="fa fa-chevron-right"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <aside class="control-sidebar control-sidebar-dark" id="categories-list">
        <div class="tab-content sb">
            <div class="tab-pane active sb" id="control-sidebar-home-tab">
                <div id="filter-categories-con">
                    <input type="text" autocomplete="off" data-list=".control-sidebar-menu" name="filter-categories" id="filter-categories" class="form-control sb col-xs-12 kb-text" placeholder="<?= lang('filter_categories'); ?>" style="margin-bottom: 20px;">
                </div>
                <div class="clearfix sb"></div>
                <div id="category-sidebar-menu">
                    <ul class="control-sidebar-menu">
                        <?php
                        foreach ($categories as $category) {
                            echo '<li><a href="#" class="category' . ($category->id == $Settings->default_category ? ' active' : '') . '" id="' . $category->id . '">';
                            if ($category->image) {
                                echo '<div class="menu-icon"><img src="' . base_url('uploads/thumbs/' . $category->image) . '" alt="" class="img-thumbnail img-responsive"></div>';
                            } else {
                                echo '<i class="menu-icon fa fa-folder-open bg-red"></i>';
                            }
                            echo '<div class="menu-info"><h4 class="control-sidebar-subheading">' . $category->code . '</h4><p>' . $category->name . '</p></div>
                            </a></li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </aside>
    <div class="control-sidebar-bg sb"></div>

    <div id="order_tbl" style="display:none;"><span id="order_span"></span>
        <table id="order-table" class="prT table table-striped table-condensed" style="width:100%;margin-bottom:0;"></table>
    </div>
    <div id="bill_tbl" style="display:none;"><span id="bill_span"></span>
        <table id="bill-table" width="100%" class="prT table table-striped table-condensed" style="width:100%;margin-bottom:0;"></table>
        <table id="bill-total-table" width="100%" class="prT table table-striped table-condensed" style="width:100%;margin-bottom:0;"></table>
    </div>
    <div style="width:500px;background:var(--nx-card-bg);display:block">
        <div id="order-data" style="display:none;" class="text-center">
            <h1><?= html_escape($store->name); ?></h1>
            <h2><?= lang('order'); ?></h2>
            <div id="preo" class="text-left"></div>
        </div>
        <div id="bill-data" style="display:none;" class="text-center">
            <h1><?= html_escape($store->name); ?></h1>
            <h2><?= lang('bill'); ?></h2>
            <div id="preb" class="text-left"></div>
        </div>
    </div>

    <div id="ajaxCall"><i class="fa fa-spinner fa-pulse"></i></div>


    </div>

    <script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/moment.min.js" type="text/javascript"></script>
    <script src="<?= $assets ?>plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        $(function() {
            $('.datetimepicker').datetimepicker({
                format: 'YYYY-MM-DD HH:mm'
            });
        });
    </script>

    <script type="text/javascript">
        $('#exampleModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var recipient = button.data('whatever')
            var modal = $(this)
            modal.find('.modal-title').text('Accion a Realizar ' + recipient)

            var action = '<?= base_url() ?>cash';

            if (recipient == "@Retiro") {
                //$('formCash#solicitud').val(1);
                $("[name='solicitud']").val(1);
                $("[name='botonSubmit']").val('Retiro');

            } else if (recipient == "@Deposito") {
                //$('formCash#solicitud').val(0);

                $("[name='solicitud']").val(0);

                $("[name='botonSubmit']").val('Deposito');
            }

            //$('form#dinero-caja').attr('action', action);
            //modal.find('.modal-body input').val(recipient)
        })
    </script>
        <div class="modal" data-easein="flipYIn" class="modal fade" id="cuentasModal" tabindex="-1" role="dialog" aria-labelledby="mModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <?= form_open('pos/save_receivable', 'id="pos-receivable-form"'); ?>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">Dividir cuentas</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_suspended" value ="<?= $sid ?>">
                    <input type="hidden" name="customer_id" value ="<?=  $Settings->default_customer ?>">
                    <input type="hidden" name="actividad_id" value ="<?=  $Settings->default_actividad ?>">
                    <input type="hidden" name="token_post" value="<?= md5(date('Y-m-d H:i:s')) ?>" />
                    <select class="form-control" name = "qty_cuentas" id="qty_cuentas">
                    <?php echo "<option value=''>Seleccione una opci&oacute;n</option>"; ?>
                        <?php for ($x = 2; $x <= 20; $x++) { ?>
                            <?php echo "<option value='" . $x . "'>" . $x . "</option>"; ?>
                        <?php } ?>
                    </select>
                    <input type="hidden" id="ctaselected">
                    <div class="content" style="padding: 0;">
                        <div class="tableFixHead col-lg-5 col-md-5 col-sm-5" style="padding: 0;min-height: 250px;max-height: 250px; ">
                            <table class="col-md-12" id="tblReceivable">
                                <thead>
                                    <tr>
                                        <td>Articulo</td>
                                        <td>Cantidad</td>
                                    </tr>
                                </thead>
                                <tbody id="artDiv"></tbody>
                            </table>
                        </div>
                        <div class="col-lg-7 col-md-7 col-sm-7 tableFixHead" style="padding: 0;min-height: 250px;max-height: 250px; " id="divCuentas">

                        </div>
                    </div>
                   
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary pull-center" id="add_receivable"><?= lang('save') ?></button>  
                    <button type="button" class="btn btn-default pull-center" data-dismiss="modal"><?= lang('close') ?></button>

                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
    <style>
        .tableFixHead {
            overflow-y: auto;
            height: 100px;
        }

        .tableFixHead thead th {
            position: sticky;
            top: 0;
        }
    </style>
    <div class="modal" data-easein="flipYIn" id="gcModal" tabindex="-1" role="dialog" aria-labelledby="mModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="myModalLabel"><?= lang('sell_gift_card'); ?></h4>
                </div>
                <div class="modal-body">
                    <p><?= lang('enter_info'); ?></p>

                    <div class="alert alert-danger gcerror-con" style="display: none;">
                        <button data-dismiss="alert" class="close" type="button">×</button>
                        <span id="gcerror"></span>
                    </div>
                    <div class="form-group">
                        <?= lang("card_no", "gccard_no"); ?> *
                        <div class="input-group">
                            <?php echo form_input('gccard_no', '', 'class="form-control" id="gccard_no"'); ?>
                            <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;"><a href="#">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="gcname" value="<?= lang('gift_card') ?>" id="gcname" />
                    <div class="form-group">
                        <?= lang("value", "gcvalue"); ?> *
                        <?php echo form_input('gcvalue', '', 'class="form-control" id="gcvalue"'); ?>
                    </div>
                    <div class="form-group">
                        <?= lang("price", "gcprice"); ?> *
                        <?php echo form_input('gcprice', '', 'class="form-control" id="gcprice"'); ?>
                    </div>
                    <div class="form-group">
                        <?= lang("expiry_date", "gcexpiry"); ?>
                        <?php echo form_input('gcexpiry', '', 'class="form-control" id="gcexpiry"'); ?>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?= lang('close') ?></button>
                    <button type="button" id="addGiftCard" class="btn btn-primary"><?= lang('sell_gift_card') ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" data-easein="flipYIn" id="dsModal" tabindex="-1" role="dialog" aria-labelledby="dsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="dsModalLabel"><?= lang('discount_title'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type='text' class='form-control input-sm kb-pad' id='get_ds' onClick='this.select();' value=''>

                    <!--                        <label class="checkbox" for="apply_to_order">
                                                    <input style="dys" type="radio" name="apply_to" value="order" id="apply_to_order"/>
                        <?= lang('apply_to_order') ?>
                                                </label>-->
                    <label class="checkbox" for="apply_to_products">
                        <input type="radio" name="apply_to" value="products" checked="checked" id="apply_to_products" />
                        <?= lang('apply_to_products') ?>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal"><?= lang('close') ?></button>
                    <button type="button" id="updateDiscount" class="btn btn-primary btn-sm"><?= lang('update') ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" data-easein="flipYIn" id="tsModal" tabindex="-1" role="dialog" aria-labelledby="tsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="tsModalLabel"><?= lang('tax_title'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type='text' class='form-control input-sm kb-pad' id='get_ts' onClick='this.select();' value=''>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal"><?= lang('close') ?></button>
                    <button type="button" id="updateTax" class="btn btn-primary btn-sm"><?= lang('update') ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" data-easein="flipYIn" id="noteModal" tabindex="-1" role="dialog" aria-labelledby="noteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="noteModalLabel"><?= lang('note'); ?></h4>
                </div>
                <div class="modal-body">
                    <textarea name="snote" id="snote" class="pa form-control kb-text"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal"><?= lang('close') ?></button>
                    <button type="button" id="update-note" class="btn btn-primary btn-sm"><?= lang('update') ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" data-easein="flipYIn" id="proModal" tabindex="-1" role="dialog" aria-labelledby="proModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-primary">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="proModalLabel">
                        <?= lang('payment') ?>
                    </h4>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th style="width:25%;"><?= lang('net_price'); ?></th>
                            <th style="width:25%;"><span id="net_price"></span></th>
                            <th style="width:25%;"><?= lang('product_tax'); ?></th>
                            <th style="width:25%;"><span id="pro_tax"></span> <span id="pro_tax_method"></span></th>
                        </tr>
                    </table>
                    <input type="hidden" id="row_id" />
                    <input type="hidden" id="item_id" />
                    <input type="hidden" id="cf" />
                    <input type="hidden" id="cprice" />
                    <div class="row">
                        <?php if ($Settings->enable_fractions == 1) { ?>
                            <div class="col-sm-12" id="price_frac">
                                <div class="form-group">
                                    <b>Seleccione aqui si el precio es fraccionado <input type="checkbox" class="fraccionado" /></b>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <?= lang('new_price', 'new_price') ?>

                                <input type="text" class="form-control input-sm kb-pad" id="nPriceConimp" onClick="this.select();" placeholder="<?= lang('new_price') ?>">
                            </div>
                            <div style="display: none;" class="form-group">
                                <?= lang('new_price', 'new_price') ?>

                                <input type="text" class="form-control input-sm kb-pad" id="nPrice" onClick="this.select();" placeholder="<?= lang('new_price') ?>">
                            </div>

                            <div class="form-group">
                                <?= lang('discount', 'nDiscount') ?>
                                <input type="text" class="form-control input-sm kb-pad" readonly="readonly" id="nDiscount" onClick="this.select();" placeholder="<?= lang('discount') ?>">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <?= lang('quantity', 'nQuantity') ?>
                                <input type="text" class="form-control input-sm kb-pad" id="nQuantity" onClick="this.select();" placeholder="<?= lang('current_quantity') ?>">
                            </div>
                        </div>
                    </div>
                    <textarea style="display:none;" class="form-control kb-text" id="nComment"></textarea>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?= lang('close') ?></button>
                    <button class="btn btn-success" id="editItem"><?= lang('update') ?></button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal" data-easein="flipYIn" id="susModal" tabindex="-1" role="dialog" aria-labelledby="susModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <?php
                    if ($Settings->enable_parquimetro == "1") {
                        $titulo = "Registro de Placa del Vehiculo";
                        $titulo2 = "Escriba la placa del vehiculo";
                    } elseif ($Settings->propina_enable == "1") {
                        $titulo = "Guardar Comanda y Enviar a cocina";
                        $titulo2 = "Seleccione la Mesa que a realizado el pedido";
                    } else {
                        $titulo = lang('hold');
                        $titulo2 = lang('type_reference_note');
                    }
                    ?>
                    <h4 class="modal-title" id="susModalLabel"><?= $titulo ?></h4>

                </div>
                <div class="modal-body">

                    <p><?= $titulo2 ?></p>

                    <?php $is_exist = false; if ($Settings->propina_enable == "1") { ?>
                        <div class="form-group">
                            <select name="reference_note" class="form-control kb-text" id="reference_note">
                                <option value=""> Seleeccione una.. </option>
                                <?php foreach ($waiting_tables as $item) {
                                 if ($reference_note == $item->id_waiting_tables) { $is_exist = true;?>
                                 
                                        <option selected selected='selected' value="<?= $item->id_waiting_tables ?>"><?= $item->name ?></option>
                                    <?php } if ($reference_note != $item->id_waiting_tables) { 
                                        $mesaExists = false;
                                        foreach ($suspended_sales as $ss) {
                                            if ($item->id_waiting_tables == $ss->id_waiting_tables) {
                                                $mesaExists = true;
                                            }
                                        }
                                     if (!$mesaExists && $reference_note == null || $reference_note == $item->id_waiting_tables) { ?>
                                        <option value="<?=  $item->id_waiting_tables ?>"><?= $item->name ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>

                            </select>
                            <?php if($is_exist){?>
                                <p>Mesa transferir</p>
                                <div class="form-group">
                                    <select name="transfer_table" class="form-control kb-text" id="transfer_table">
                                        <option value=""> Seleeccione una.. </option>
                                        <?php foreach ($waiting_tables as $item) {?>
                                            <option value="<?=  $item->id_waiting_tables ?>"><?= $item->name ?></option>
                                        <?php } ?>   
                                    </select>
                                </div>
                            <?php } ?>
                            <div id="inputllevar">

                            </div>
                            <script>
                                $(function() {
                                    $('#reference_note').on('change', function() {
                                        console.log("Entro putito");
                                        $("#inputllevar").html("");
                                        $('#reference_note').attr("name", "reference_note");
                                        var llevar = $('#reference_note').val();
                                        if (llevar == "llev") {
                                            $('#reference_note').removeAttr("name");
                                            $("#inputllevar").html("Nombre del Cliente: <input name='reference_note' class='form-control' id='notepllevar' required />");

                                            $('#notepllevar').on('keyup', function() {
                                                $("#hold_ref").val('TakeOut - ' + $(this).val());
                                            });
                                        }
                                    });
                                    $('#transfer_table').on('change', function() {
                                        // console.log($(this).val());
                                        $("#input_transfer_table").val($(this).val());
                                    });
                                    $("#suspend").on('click',function() {
                                        $('#transfer_table').val('');
                                    } );
                                });
                            </script>
                        </div>
                    <?php } else { ?>
                        <div class="form-group">
                            <?= $Settings->enable_parquimetro == "0" ? lang('reference_note') : "Placa del Vehiculo" ?>
                            <?php echo form_input('reference_note', $reference_note, 'class="form-control kb-text" id="reference_note"'); ?>
                        </div>
                    <?php } ?>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"> <?= lang('close') ?> </button>
                    <button type="button" id="suspend_sale" class="btn btn-primary"><?= lang('save') ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" data-easein="flipYIn" id="ProforModal" tabindex="-1" role="dialog" aria-labelledby="ProforModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="ProforModalLabel"><?= lang('qoutes_sale'); ?></h4>
                </div>
                <div class="modal-body">
                    <p><?= lang('type_reference_note'); ?></p>

                    <div class="form-group">
                        <?= lang("reference_note", "reference_note"); ?>
                        <?php echo form_input('reference_note2', $reference_note, 'class="form-control kb-text" id="reference_note2"'); ?>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"> <?= lang('close') ?> </button>
                    <button type="button" id="qoutes_sale" class="btn btn-primary"><?= lang('submit') ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" data-easein="flipYIn" id="ApartadoModal" tabindex="-1" role="dialog" aria-labelledby="ApartadoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="ApartadoModalLabel"><?= lang('apartado_sale'); ?></h4>
                </div>
                <div class="modal-body">
                    <p><?= lang('type_reference_note'); ?></p>

                    <div class="form-group">
                        <?= lang("reference_note", "reference_note"); ?>
                        <?php echo form_input('reference_note3', $reference_note, 'class="form-control kb-text" id="reference_note3"'); ?>

                        <select id="paid_by_apartado" class="form-control paid_by " style="font-size: 18px;
                                    width: 99%;
                                    display: none; visivility:hidden;
                                    font-weight: bold;
                                    padding: 0;
                                    border: 1px solid var(--nx-border);">
                            <option value="cash"><?= lang("cash"); ?></option>
                            <option value="CC">Tarjeta</option>
                        </select>

                        <?= lang("Monto de Apartado", "Monto de Apartado"); ?>
                        <input name="amount_apartado" style="text-align:right; border:none; font-size:18px;border: 1px solid var(--nx-border); color: var(--nx-txt1);" type="number" id="amount_apartado" class="pa form-control kb-pad amount" />
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"> <?= lang('close') ?> </button>
                    <button type="button" id="apartado_sale" class="btn btn-primary"><?= lang('submit') ?></button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal" data-easein="flipYIn" id="saleModal" tabindex="-1" role="dialog" aria-labelledby="saleModalLabel" aria-hidden="true"></div>
    <div class="modal" data-easein="flipYIn" id="opModal" tabindex="-1" role="dialog" aria-labelledby="opModalLabel" aria-hidden="true"></div>
    <div class="modal" data-easein="flipYIn" id="cpModal" tabindex="-1" role="dialog" aria-labelledby="cpModalLabel" aria-hidden="true"></div>


    <div class="modal" data-easein="flipYIn" id="payModal" tabindex="-1" role="dialog" aria-labelledby="payModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-success" style="width:1000px !important; margin: 200px auto;">
            <div style="width:1000px;" class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="payModalLabel">
                        <?= lang('payment') ?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="font16">
                                <table class="table table-bordered table-condensed" style="width:100%; margin-bottom: 0; font-size:40px;">
                                    <tbody>
                                        <tr>
                                            <td width="25%" style="border-right-color: var(--nx-border) !important;"><?= lang("total_payable"); ?></td>
                                            <td colspan="2" width="75%" class="text-right"><span id="twt">0.00</span></td>

                                        </tr>
                                        <tr>
                                            <td width="33%" align="center">Forma de Pago</td>
                                            <td width="33%" align="center">Monto</td>
                                            <td width="33%" align="center">Referencia</td>
                                        </tr>
                                        <tr>
                                            <td class="text-left">
                                                <input name="paid_by1" style="text-align:left; border:none; font-size:30px; color: var(--nx-txt1);" type="text" id="paid_by1" class="pa form-control kb-pad paid_by" value="<?= lang("cash"); ?>" readonly />
                                            </td>
                                            <td class="text-right">
                                                <?php if ($apa_grand_total != 0) { ?>
                                                    <input name="amount" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="amount" value="<?php echo $apa_grand_total; ?>" class="pa form-control kb-pad amount" />
                                                <?php } else { ?>
                                                    <input name="amount" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="amount" class="pa form-control kb-pad amount" />
                                                <?php } ?>
                                            </td>
                                            <td class="text-right">
                                                <input name="fpreferenciaNA" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="fpreferenciaNA" class="pa form-control kb-pad amount" value="N/A" readonly />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-right">
                                                <select id="pcc_type1" name="pcc_type1" placeholder="Tarjeta" class="form-control paid_by " style="font-size: 30px;
                                                            width: 99%;
                                                            color: var(--nx-txt1);
                                                            font-weight: bold;
                                                            height: 35px;
                                                            padding: 0;
                                                            border: none;
                                                            background: var(--nx-bg2);">
                                                    <option value="Debito" selected="selected">Debito</option>
                                                    <option value="Visa"><?= lang("Visa"); ?></option>
                                                    <option value="MasterCard"><?= lang("MasterCard"); ?></option>
                                                </select>
                                            </td>
                                            <td style="border-left: 1px solid var(--nx-border) !important;">
                                                <input name="amount2" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="amount2" class="pa form-control kb-pad amount" />
                                            </td>
                                            <td class="text-right">
                                                <input name="fpreferencia1" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="fpreferencia1" class="pa form-control kb-pad amount" value="0" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-right">
                                                <select id="pcc_type2" name="pcc_type2" placeholder="Tarjeta" class="form-control paid_by " style="font-size: 30px;
                                                            width: 99%;
                                                            color: var(--nx-txt1);
                                                            font-weight: bold;
                                                            height: 35px;
                                                            padding: 0;
                                                            border: none;
                                                            background: var(--nx-bg2);">
                                                    <option value="Debito">Debito</option>
                                                    <option value="Visa" selected="selected"><?= lang("Visa"); ?></option>
                                                    <option value="MasterCard"><?= lang("MasterCard"); ?></option>
                                                </select>
                                            </td>
                                            <td style="border-left: 1px solid var(--nx-border) !important;">
                                                <input name="amount3" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="amount3" class="pa form-control kb-pad amount" />
                                            </td>
                                            <td class="text-right">
                                                <input name="fpreferencia2" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="fpreferencia2" class="pa form-control kb-pad amount" value="0" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-right">
                                                <select id="pcc_type3" name="pcc_type3" placeholder="Tarjeta" class="form-control paid_by " style="font-size: 30px;
                                                            width: 99%;
                                                            color: var(--nx-txt1);
                                                            font-weight: bold;
                                                            height: 35px;
                                                            padding: 0;
                                                            border: none;
                                                            background: var(--nx-bg2);">
                                                    <option value="Debito">Debito</option>
                                                    <option value="Visa"><?= lang("Visa"); ?></option>
                                                    <option value="MasterCard" selected="selected"><?= lang("MasterCard"); ?></option>
                                                </select>
                                            </td>
                                            <td style="border-left: 1px solid var(--nx-border) !important;">
                                                <input name="amount4" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="amount4" class="pa form-control kb-pad amount" />
                                            </td>
                                            <td class="text-right">
                                                <input name="fpreferencia3" style="text-align:right; border:none; font-size:30px; color: var(--nx-txt1); background: var(--nx-bg2);" type="text" id="fpreferencia3" class="pa form-control kb-pad amount" value="0" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <?php if ($Settings->is_shipping) { ?>
                                                <td>
                                                    <select class="form-control paid_by " style="font-size: 30px;
                                                            width: 99%;
                                                            color: var(--nx-txt1);
                                                            font-weight: bold;
                                                            height: 35px;
                                                            padding: 0;
                                                            border: none;
                                                            background: var(--nx-bg2);" id="shipping_method" name="shipping_method">
                                                        <option value="">Metodo de envio</option>
                                                        <?php foreach ($shipping as $sh) { ?>
                                                        <?php
                                                            echo "<option value =" . $sh->id_shipping_method . ">" . $sh->name . "</option>";
                                                        } ?>
                                                    </select>
                                                </td>
                                            <?php } ?>
                                            <td style="border-right-color: var(--nx-border) !important;">Cambio</td>
                                            <td colspan="2" class="text-right"><span id="balance">0.00</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="clearfix"></div>
                            </div>
                            <div class="row" style="display:none;">
                                <div class="col-xs-12">
                                    <div class="form-group gc" style="display: none;">
                                        <?= lang("gift_card_no", "gift_card_no"); ?>
                                        <input type="text" id="gift_card_no" class="pa form-control kb-pad gift_card_no gift_card_input" />

                                        <div id="gc_details"></div>
                                    </div>
                                    <div class="pcc" style="display:none;">

                                        <input type="hidden" id="swipe" class="form-control swipe swipe_input" placeholder="<?= lang('focus_swipe_here') ?>" />

                                        <div class="row">

                                            <input type="hidden" id="pcc_no" class="form-control kb-pad" placeholder="<?= lang('cc_no') ?>" />


                                            <input type="hidden" id="pcc_holder" class="form-control kb-text" placeholder="<?= lang('cc_holder') ?>" />

                                            <div class="col-xs-6">
                                                <div class="form-group">

                                                </div>
                                            </div>

                                            <input type="hidden" id="pcc_month" class="form-control kb-pad" placeholder="<?= lang('month') ?>" />

                                            <input type="hidden" id="pcc_year" class="form-control kb-pad" placeholder="<?= lang('year') ?>" />


                                            <input type="hidden" id="pcc_cvv2" class="form-control kb-pad" placeholder="<?= lang('cvv2') ?>" />

                                        </div>
                                    </div>
                                    <div class="pcheque" style="display:none;">
                                        <div class="form-group"><?= lang("cheque_no", "cheque_no"); ?>
                                            <input type="text" id="cheque_no" class="form-control cheque_no kb-text" />
                                        </div>
                                    </div>
                                    <div class="pcash">
                                        <div class="form-group"><?= lang("payment_note", "payment_note"); ?>
                                            <input type="text" id="payment_note" class="form-control payment_note kb-text" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-3 text-center" style="display:none;">
                            <!-- <span style="font-size: 1.2em; font-weight: bold;"><?= lang('quick_cash'); ?></span> -->

                            <div class="btn-group btn-group-vertical" style="width:100%;">
                                <button type="button" class="btn btn-info btn-block quick-cash" id="quick-payable">0.00
                                </button>
                                <?php
                                foreach (lang('quick_cash_notes') as $cash_note_amount) {
                                    echo '<button type="button" class="btn btn-block btn-warning quick-cash">' . $cash_note_amount . '</button>';
                                }
                                ?>
                                <button type="button" class="btn btn-block btn-danger" id="clear-cash-notes"><?= lang('clear'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="botdeenvioapago">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"> <?= lang('close') ?> </button>
                    <button class="btn btn-primary" id="<?= $eid ? '' : 'submit-sale'; ?>"><?= lang('submit') ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Retiro - Deposito -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">New message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <?php $attributes = array('name' => 'formCash', 'id' => 'formCash'); ?>

                    <?= form_open_multipart("cash", $attributes); ?>

                    <?php if ($Admin) { ?>
                        <div class="form-group">
                            <?= lang("date", "date"); ?>
                            <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : date('Y-m-d h:i:s')), ' readonly="readonly" class="form-control datetimepicker" id="date" required="required"'); ?>
                        </div>
                    <?php } ?>

                    <div class="form-group">
                        <?= lang("reference", "reference"); ?>
                        <?= form_input('reference', (isset($_POST['reference']) ? $_POST['reference'] : ''), 'class="form-control tip" id="reference"'); ?>
                    </div>

                    <div class="form-group">
                        <?= lang("amount", "amount"); ?>
                        <input name="amount" type="text" value="" class="pa form-control kb-pad " required="required" />
                    </div>

                    <div class="form-group">
                        <?= lang("note", "note"); ?>
                        <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ""), 'class="form-control redactor" id="note"'); ?>
                    </div>

                    <div class="form-group">
                        <?php echo form_submit('botonSubmit', '', 'class="btn btn-primary"'); ?>
                    </div>

                    <?= form_hidden('solicitud', ''); ?>

                </div>
                <?php echo form_close(); ?>

            </div>
            <div class="modal-footer">

            </div>
        </div>
    </div>


    <div class="modal" data-easein="flipYIn" id="customerModal" tabindex="-1" role="dialog" aria-labelledby="cModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-primary">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="cModalLabel">
                        <?= lang('add_customer') ?>
                    </h4>
                </div>
                <?= form_open('pos/add_customer', 'id="customer-form"'); ?>
                <div class="modal-body">
                    <div id="c-alert" class="alert alert-danger" style="display:none;"></div>


                    <?php if ($Settings->enable_credit) { ?>
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="form-group">
                                    <label class="control-label" for="limitcredit">
                                        <?= lang("limitcredit"); ?>
                                    </label>
                                    <?= form_input('limitcredit', '', 'class="form-control input-sm kb-text" id="limitcredit" placeholder="Formato ejemplo: 5000.00   ó   0.00 " required="required"'); ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>


                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group">
                                <label class="control-label" for="name">
                                    <?= lang("name"); ?><span style="color:var(--danger)">*</span>
                                </label>
                                <?= form_input('name', '', 'class="form-control input-sm kb-text" id="cname" required="required"'); ?>
                            </div>
                        </div>

                        <div class="col-xs-12">
                            <div class="form-group">
                                <label class="control-label" for="business_name">
                                    Nombre Comercial
                                </label>
                                <?= form_input('business_name', '', 'class="form-control input-sm kb-text" id="business_name"'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label" for="cemail">
                                    <?= lang("email_address"); ?>
                                </label>
                                <?= form_input('email', '', 'class="form-control input-sm kb-text" id="cemail"'); ?>
                                <span id="emailspan"></span>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label" for="phone">
                                    <?= lang("phone"); ?>
                                </label>
                                <?= form_input('phone', '', 'class="form-control input-sm kb-pad" id="cphone" '); ?>
                                <span id="phonespan"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label" for="cf1">
                                    <?php echo lang("cf1"); ?><span style="color:var(--danger)">*</span>
                                </label>
                                <!--                            --><?php //= form_input('cf1', '', 'class="form-control input-sm kb-text" id="cf1"');  
                                                                    ?>
                                <select name="cf1" class="form-control input-sm selct2" id="cf1" required="required">
                                    <option <?php @$customer->cf1 == '01' ? ' selected selected="selected"' : '' ?> value="01">Cedula de Identidad
                                    </option>
                                    <option <?php @$customer->cf1 == '02' ? ' selected selected="selected"' : '' ?> value="02">Cedula Juridica
                                    </option>
                                    <option <?php @$customer->cf1 == '03' ? ' selected selected="selected"' : '' ?> value="03">DIMEX
                                    </option>
                                    <option <?php @$customer->cf1 == '04' ? ' selected selected="selected"' : '' ?> value="04">
                                        NITE
                                    </option>
                                    <option <?php @$customer->cf1 == '05' ? ' selected selected="selected"' : '' ?> value="05">
                                        Passaporte / Extranjero
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label" for="cf2">
                                    <?php echo lang("cf2"); ?><span style="color:var(--danger)">*</span>
                                </label>
                                <?php echo form_input('cf2', '', 'class="form-control input-sm kb-text" id="cf2"  required="required"'); ?>
                                <span id="identifispan"></span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="margin-top:0;">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"> <?= lang('close') ?> </button>
                    <button type="submit" class="btn btn-primary" id="add_customer"> <?= lang('add_customer') ?> </button>
                </div>


                <?= form_close(); ?>
            </div>
        </div>
    </div>

    <div class="modal" data-easein="flipYIn" id="exoneracionModal" tabindex="-1" role="dialog" aria-labelledby="exoneracionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-primary">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
                    </button>
                    <h4 class="modal-title" id="cModalLabel">
                        <?= lang('add_exoneracion') ?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div id="c-alert" class="alert alert-danger" style="display:none;"></div>

                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label" for="name">
                                    <?= lang("TipoDocumentoE"); ?><span style="color:var(--danger)">*</span>
                                </label>
                                <?php
                                $tipoDocE = [
                                    '0' => '',
                                    '01' => 'Compras Autorizadas',
                                    '02' => 'Ventas Exentas a Diplomaticos',
                                    '03' => 'Orden de compra (Instituciones públicas y otros organismos)',
                                    '04' => 'Exenciones Dirección General de Hacienda',
                                    '05' => 'Transitorio V',
                                    '06' => 'Transitorio IX',
                                    '07' => 'Transitorio XVII',
                                    '99' => 'Otros'
                                ]
                                ?>
                                <select name="TipoDocumentoE" rows="5" id="TipoDocumentoE" class="form-control input-sm selct2" required="required">
                                    <?php foreach ($tipoDocE as $key => $val) {
                                        echo "<option value=" . $key . ">" . $val . "</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label" for="business_name">
                                    <?= lang("NumeroDocumentoE"); ?><span style="color:var(--danger)">*</span>
                                </label>
                                <?= form_input('NumeroDocumentoE', '', 'class="form-control input-sm kb-text" id="NumeroDocumentoE"'); ?>
                            </div>
                        </div>

                        <div class="col-xs-12">
                            <div class="form-group">
                                <label class="control-label" for="business_name">
                                    <?= lang("NombreInstitucionE"); ?><span style="color:var(--danger)">*</span>
                                </label>
                                <?= form_input('NombreInstitucionE', '', 'class="form-control input-sm kb-text" id="NombreInstitucionE"'); ?>
                            </div>
                        </div>

                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label" for="business_name">
                                    <?= lang("FechaEmisionE"); ?><span style="color:var(--danger)">*</span>
                                </label>
                                <?php $dateE = date('Y-m-d') . 'T' . date('H:i:s'); ?>
                                <input type="text" name="FechaEmisionE" id="FechaEmisionE" readonly="" value="<?php echo $dateE; ?>" class="form-control input-sm kb-text">
                            </div>
                        </div>

                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label" for="business_name">
                                    <?= lang("PorcentajeExoneracionE"); ?>
                                </label>
                                <select name="PorcentajeExoneracionE" class="form-control input-sm selct2" id="PorcentajeExoneracionE" required="required">
                                    <?
                                    for ($pe = 1; $pe <= 100; $pe++) {
                                        if ($pe < 10)
                                            $por = '0' . $pe;
                                        else
                                            $por = $pe;

                                        echo '<option value="' . $por . '">' . $por . '</option>';

                                        /*
                                <option <?php @$customer->cf1 == '02' ? ' selected selected="selected"' : '' ?>
                                        value="02">Cedula Juridica
                                </option>*/
                                    }
                                    ?>
                                </select>

                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="margin-top:0;">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal"> <?= lang('close') ?> </button>

                    <button type="button" class="btn btn-primary" id="add_exoneracion" name="add_exoneracion"> <?= lang('add_exoneracion') ?> </button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal" data-easein="flipYIn" id="posModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"></div>
    <div class="modal" data-easein="flipYIn" id="posModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2" aria-hidden="true"></div>


    <input type="hidden" value="" id="id_last_items" />
    <?php
    if ($this->session->has_userdata('last_sale_id')) {
        $sale_id = $this->session->userdata("last_sale_id");
    } else {
        $sale_id = 0;
    }
    ?>

    <input type="hidden" value="<?php echo $sale_id; ?>" id="id_last_facture" />
    <style>
        #ui-id-1 {
            overflow-y: scroll;
            max-height: 300px;
        }
    </style>
    <script type="text/javascript">
        var base_url = '<?= base_url(); ?>',
            assets = '<?= $assets ?>';
        var dateformat = '<?= $Settings->dateformat; ?>',
            timeformat = '<?= $Settings->timeformat ?>';
        <?php unset($Settings->protocol, $Settings->smtp_host, $Settings->smtp_user, $Settings->smtp_pass, $Settings->smtp_port, $Settings->smtp_crypto, $Settings->mailpath, $Settings->timezone, $Settings->setting_id, $Settings->default_email, $Settings->version, $Settings->stripe, $Settings->stripe_secret_key, $Settings->stripe_publishable_key); ?>
        var Settings = <?= json_encode($Settings); ?>;
        var sid = false,
            username = '<?= $this->session->userdata('username'); ?>',
            spositems = {};
        var quo = false,
            username = '<?= $this->session->userdata('username'); ?>',
            spositems = {};
        var apa = false,
            username = '<?= $this->session->userdata('username'); ?>',
            spositems = {};

        var is_mutiPrice = '<?= $Settings->multiprice_enabled ?>';
        $(window).load(function() {
            $('#mm_<?= $m ?>').addClass('active');
            $('#<?= $m ?>_<?= $v ?>').addClass('active');
        });
        var pro_limit = <?= $Settings->pro_limit ?>,
            java_applet = 0,
            count = 1,
            total = 0,
            an = 1,
            p_page = 0,
            page = 0,
            cat_id = <?= $Settings->default_category ?>,
            tcp = <?= $tcp ?>;
        var gtotal = 0,
            order_discount = 0,
            order_tax = 0,
            protect_delete = <?= ($Admin) ? 0 : ($Settings->pin_code ? 1 : 0); ?>,
            protect_closeregister = 1,
            protect_changeprice = 1,
            protect_opendrawer = 1,
            protect_creditnote = 1,
            type_printer = "<?= ($printer_default ? $printer_default->type : '') ?>";
        var order_data = {},
            bill_data = {};
        var csrf_hash = '<?= $this->security->get_csrf_hash(); ?>';
        <?php
        if ($Settings->remote_printing == 2) {
        ?>
            var ob_store_name = "<?= printText($store->name, (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n";
            order_data.store_name = ob_store_name;
            bill_data.store_name = ob_store_name;

            ob_header = "";
            ob_header += "<?= printText($store->name . ' (' . $store->code . ')', (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n";
            <?php if ($store->address1) { ?>
                ob_header += "<?= printText($store->address1, (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n";
            <?php
            }
            if ($store->address2) {
            ?>
                ob_header += "<?= printText($store->address2, (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n";
            <?php
            }
            if ($store->city) {
            ?>
                ob_header += "<?= printText($store->city, (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n";
            <?php }
            ?>
            ob_header += "<?= printText(lang('tel') . ': ' . $store->phone, (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n\r\n";
            ob_header += "<?= printText(str_replace(array("\n", "\r"), array("\\n", "\\r"), $store->receipt_header), (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n\r\n";

            order_data.header = ob_header + "<?= printText(lang('order'), (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n\r\n";
            bill_data.header = ob_header + "<?= printText(lang('bill'), (!empty($printer) ? $printer->char_per_line : '')); ?>\r\n\r\n";
            order_data.totals = '';
            order_data.payments = '';
            bill_data.payments = '';
            order_data.footer = '';
            bill_data.footer = "<?= lang('merchant_copy'); ?> \n";
        <?php
        }
        ?>
        var lang = new Array();
        lang['submit'] = '<?= lang('submit') ?>';
        lang['code_error'] = '<?= lang('code_error'); ?>';
        lang['r_u_sure'] = '<?= lang('r_u_sure'); ?>';
        lang['please_add_product'] = '<?= lang('please_add_product'); ?>';
        lang['paid_less_than_amount'] = '<?= lang('paid_less_than_amount'); ?>';
        lang['x_suspend'] = '<?= lang('x_suspend'); ?>';
        lang['discount_title'] = '<?= lang('discount_title'); ?>';
        lang['update'] = '<?= lang('update'); ?>';
        lang['tax_title'] = '<?= lang('tax_title'); ?>';
        lang['leave_alert'] = '<?= lang('leave_alert'); ?>';
        lang['close'] = '<?= lang('close'); ?>';
        lang['delete'] = '<?= lang('delete'); ?>';
        lang['no_match_found'] = '<?= lang('no_match_found'); ?>';
        lang['wrong_pin'] = '<?= lang('wrong_pin'); ?>';
        lang['file_required_fields'] = '<?= lang('file_required_fields'); ?>';
        lang['enter_pin_code'] = '<?= lang('enter_pin_code'); ?>';
        lang['incorrect_gift_card'] = '<?= lang('incorrect_gift_card'); ?>';
        lang['card_no'] = '<?= lang('card_no'); ?>';
        lang['value'] = '<?= lang('value'); ?>';
        lang['balance'] = '<?= lang('balance'); ?>';
        lang['unexpected_value'] = '<?= lang('unexpected_value'); ?>';
        lang['inclusive'] = '<?= lang('inclusive'); ?>';
        lang['exclusive'] = '<?= lang('exclusive'); ?>';
        lang['total'] = '<?= lang('total'); ?>';
        lang['total_items'] = '<?= lang('total_items'); ?>';
        lang['order_tax'] = '<?= lang('order_tax'); ?>';
        lang['order_discount'] = '<?= lang('order_discount'); ?>';
        lang['total_payable'] = '<?= lang('total_payable'); ?>';
        lang['rounding'] = '<?= lang('rounding'); ?>';
        lang['grand_total'] = '<?= lang('grand_total'); ?>';
        lang['register_open_alert'] = '<?= lang('register_open_alert'); ?>';
        lang['discount'] = '<?= lang('discount'); ?>';
        lang['order'] = '<?= lang('order'); ?>';
        lang['bill'] = '<?= lang('bill'); ?>';
        lang['merchant_copy'] = '<?= lang('merchant_copy'); ?>';
        lang['invalid_mail'] = '<?= lang('invalid_mail'); ?>';
        lang['invalid_phone'] = '<?= lang('invalid_phone'); ?>';
        lang['invalid_identify'] = '<?= lang('invalid_identify'); ?>';

        async function actExo(TipoDocumentoE, NumeroDocumentoE, NombreInstitucionE, PorcentajeExoneracion) {
            if (TipoDocumentoE < 10) {
                $('#TipoDocumentoE').val("0" + TipoDocumentoE);
            } else {
                $('#TipoDocumentoE').val(TipoDocumentoE);
            }
            $('#NumeroDocumentoE').val(NumeroDocumentoE);
            $('#NombreInstitucionE').val(NombreInstitucionE);
            if (PorcentajeExoneracion < 10) {
                $('#PorcentajeExoneracionE').val("0" + PorcentajeExoneracion);
            } else {
                $('#PorcentajeExoneracionE').val(PorcentajeExoneracion);
            }

            await sleep(300);
            $('#add_exoneracion').click();
        }
        $(document).ready(function() {
            $('#spos_customer').on('change', function() {
                // console.log(this.value);
                if (this.value != 1) {
                    $('#shipping_method').val('');
                    // $('#shipping_method').change();  
                }
            });
            store('enable_credit', <?= $Settings->enable_credit ?>);
            store('enable_fractions', <?= $Settings->enable_fractions ?>);
            store('propina_rate', <?= $Settings->propina_rate ?>);
            <?php if ($this->session->userdata('rmspos')) { ?>
                if (get('spositems')) {
                    remove('spositems');
                }
                if (get('spos_discount')) {
                    remove('spos_discount');
                }
                if (get('spos_tax')) {
                    remove('spos_tax');
                }
                if (get('spos_note')) {
                    remove('spos_note');
                }
                if (get('spos_customer')) {
                    remove('spos_customer');
                }
                if (get('amount')) {
                    remove('amount');
                }
            <?php
                $this->tec->unset_data('rmspos');
            }
            ?>

            if (get('rmspos')) {
                if (get('spositems')) {
                    remove('spositems');
                }
                if (get('spos_discount')) {
                    remove('spos_discount');
                }
                if (get('spos_tax')) {
                    remove('spos_tax');
                }
                if (get('spos_note')) {
                    remove('spos_note');
                }
                if (get('spos_customer')) {
                    remove('spos_customer');
                }
                if (get('amount')) {
                    remove('amount');
                }
                remove('rmspos');
            }
            <?php if ($sid) { ?>

                store('spositems', JSON.stringify(<?= $items; ?>));
                store('spos_discount', '<?= $suspend_sale->order_discount_id; ?>');
                store('spos_tax', '<?= $suspend_sale->order_tax_id; ?>');
                store('spos_customer', '<?= $suspend_sale->customer_id; ?>');
                $('#spos_customer').select2().select2('val', '<?= $suspend_sale->customer_id; ?>');
                store('rmspos', '1');
                $('#tax_val').val('<?= $suspend_sale->order_tax_id; ?>');
                $('#discount_val').val('<?= $suspend_sale->order_discount_id; ?>');
            <?php } elseif ($quo) { ?>
                store('spositems', JSON.stringify(<?= $items; ?>));
                store('spos_discount', '<?= $quotes_sale->order_discount_id; ?>');
                store('spos_tax', '<?= $quotes_sale->order_tax_id; ?>');
                store('spos_customer', '<?= $quotes_sale->customer_id; ?>');
                $('#spos_customer').select2().select2('val', '<?= $quotes_sale->customer_id; ?>');
                store('rmspos', '1');
                $('#tax_val').val('<?= $quotes_sale->order_tax_id; ?>');
                $('#discount_val').val('<?= $quotes_sale->order_discount_id; ?>');
                var TipoDocumentoE = '<?= $quotes_sale->TipoDocumentoE; ?>';
                var NumeroDocumentoE = '<?= $quotes_sale->NumeroDocumentoE; ?>';
                var NombreInstitucionE = '<?= $quotes_sale->NombreInstitucionE; ?>';
                var PorcentajeExoneracion = '<?= $quotes_sale->PorcentajeExoneracion; ?>';
                actExo(TipoDocumentoE, NumeroDocumentoE, NombreInstitucionE, PorcentajeExoneracion);
            <?php } elseif ($apa) { ?>
                store('spositems', JSON.stringify(<?= $items; ?>));
                store('spos_discount', '<?= $apa_sale->order_discount_id; ?>');
                store('spos_tax', '<?= $apa_sale->order_tax_id; ?>');
                store('spos_customer', '<?= $apa_sale->customer_id; ?>');
                $('#spos_customer').select2().select2('val', '<?= $apa_sale->customer_id; ?>');
                store('rmspos', '1');
                $('#tax_val').val('<?= $apa_sale->order_tax_id; ?>');
                $('#discount_val').val('<?= $apa_sale->order_discount_id; ?>');
            <?php } elseif ($eid) { ?>
                <?php if ($is_tip) { ?>
                    triggerTip();
                <?php } ?>
                $('#date').inputmask("y-m-d h:s:s", {
                    "placeholder": "YYYY/MM/DD HH:mm:ss"
                });
                store('spositems', JSON.stringify(<?= $items; ?>));
                store('spos_discount', '<?= $sale->order_discount_id; ?>');
                store('spos_tax', '<?= $sale->order_tax_id; ?>');
                store('spos_customer', '<?= $sale->customer_id; ?>');
                store('sale_date', '<?= $sale->date; ?>');
                $('#spos_customer').select2().select2('val', '<?= $sale->customer_id; ?>');
                $('#date').val('<?= $sale->date; ?>');
                store('rmspos', '1');
                $('#tax_val').val('<?= $sale->order_tax_id; ?>');
                $('#discount_val').val('<?= $sale->order_discount_id; ?>');
            <?php } elseif ($rid) { ?>
                <?php if ($is_tip) { ?>
                    triggerTip();
                <?php } ?>

                store('spositems', JSON.stringify(<?= $items; ?>));
                store('spos_discount', '<?= $redo_sale->order_discount_id; ?>');
                store('spos_tax', '<?= $redo_sale->order_tax_id; ?>');
                store('spos_customer', '<?= $redo_sale->customer_id; ?>');
                $('#spos_customer').select2().select2('val', '<?= $redo_sale->customer_id; ?>');
                store('rmspos', '1');
                $('#tax_val').val('<?= $redo_sale->order_tax_id; ?>');
                $('#discount_val').val('<?= $redo_sale->order_discount_id; ?>');
            <?php } else { ?>
                if (!get('spos_discount')) {
                    store('spos_discount', '<?= $Settings->default_discount; ?>');
                    $('#discount_val').val('<?= $Settings->default_discount; ?>');
                }
                if (!get('spos_tax')) {
                    store('spos_tax', '<?= $Settings->default_tax_rate; ?>');
                    $('#tax_val').val('<?= $Settings->default_tax_rate; ?>');
                }
            <?php } ?>

            if (ots = get('spos_tax')) {
                $('#tax_val').val(ots);
            }
            if (ods = get('spos_discount')) {
                $('#discount_val').val(ods);
            }
            bootbox.addLocale('bl', {
                OK: '<?= lang('ok'); ?>',
                CANCEL: '<?= lang('no'); ?>',
                CONFIRM: '<?= lang('yes'); ?>'
            });
            bootbox.setDefaults({
                closeButton: false,
                locale: "bl"
            });
            <?php if ($eid) { ?>
                $('#suspend').attr('disabled', true);
                $('#print_order').attr('disabled', true);
                $('#print_bill').attr('disabled', true);
            <?php } ?>



        });

        $('#quotes').click(function() {
            if (count <= 1) {
                alert("Por Favor agrege un producto");
                return false;
            } else {
                $('#ProforModal').modal({
                    backdrop: 'static'
                });
                $('#reference_note2').val($('#hold_ref').val());
            }
        });

        $('#qoutes_sale').click(function() {
            ref = $('#reference_note2').val();
            if (!ref || ref == '') {
                alert("Debe agregar una referencia");
                return false;
            } else {
                if ($('#reference_note2').val()) {
                    $('#hold_ref').val($('#reference_note2').val());
                    $('#total_items').val(an - 1);
                    $('#total_quantity').val(count - 1);
                    document.getElementById('hidesuspend').innerHTML = '<input type="hidden" name="quote" value="yes" /><input type="hidden" name="quote_note" value="' + ref + '" />';
                    $('#submit').click();
                    $('#pos-sale-form').submit();
                }

            }
        });

        $('#Apartado').click(function() {
            if (count <= 1) {
                alert("Por Favor agrege un producto");
                return false;
            } else {
                $('#ApartadoModal').modal({
                    backdrop: 'static'
                });
                $('#reference_note3').val($('#hold_ref').val());
            }
        });

        $('#apartado_sale').click(function() {
            ref = $('#reference_note3').val();
            forma = $('#paid_by_apartado').val();
            montos = $('#amount_apartado').val();
            if (!ref || ref == '') {
                alert("Debe agregar una referencia");
                return false;
            } else if (!forma || forma == '') {
                alert("Debe agregar una forma de pago");
                return false;
            } else if (!montos || montos == '') {
                alert("Debe agregar un monto");
                return false;
            } else {

                if ($('#reference_note3').val()) {
                    $('#hold_ref').val($('#reference_note3').val());
                    $('#paid_by').val($('#paid_by_apartado').val());
                    $('#amount').val($('#amount_apartado').val());
                    $('#amount_val').val($('#amount_apartado').val());

                    $('#total_items').val(an - 1);
                    $('#total_quantity').val(count - 1);
                    document.getElementById('hidesuspend').innerHTML = '<input type="hidden" name="apart" value="yes" /><input type="hidden" name="apart_note" value="' + ref + '" />';
                    $('#submit').click();
                    $('#pos-sale-form').submit();
                }
            }
        });
    </script>

    <script type="text/javascript">
        var socket = null;
        <?php
        if ($Settings->remote_printing == 2) {
        ?>
            try {
                socket = new WebSocket('ws://127.0.0.1:6441');
                socket.onopen = function() {
                    console.log('Connected');
                    return;
                };
                socket.onclose = function() {
                    console.log('Connection closed');
                    return;
                };
            } catch (e) {
                console.log(e);
            }
        <?php
        }
        ?>

        function printBill(bill) {
            if (Settings.remote_printing == 1) {
                Popup($('#bill_tbl').html());
            } else if (Settings.remote_printing == 2) {
                if (socket.readyState == 1) {
                    var socket_data = {
                        'printer': <?= $Settings->local_printers ? "''" : json_encode($printer); ?>,
                        'logo': '<?= !empty($store->logo) ? base_url('uploads/' . $store->logo) : ''; ?>',
                        'text': bill
                    };
                    socket.send(JSON.stringify({
                        type: 'print-receipt',
                        data: socket_data
                    }));
                    return false;
                } else {
                    bootbox.alert('<?= lang('pos_print_error'); ?>');
                    return false;
                }
            }
        }

        var order_printers = <?= $Settings->local_printers ? "''" : json_encode($order_printers); ?>;

        function printOrder(order) {
            if (Settings.remote_printing == 1) {
                Popup($('#order_tbl').html());
            } else if (Settings.remote_printing == 2) {
                if (socket.readyState == 1) {
                    if (order_printers == '') {

                        var socket_data = {
                            'printer': false,
                            'order': true,
                            'logo': '<?= !empty($store->logo) ? base_url('uploads/' . $store->logo) : ''; ?>',
                            'text': order
                        };
                        socket.send(JSON.stringify({
                            type: 'print-receipt',
                            data: socket_data
                        }));

                    } else {

                        $.each(order_printers, function() {
                            var socket_data = {
                                'printer': this,
                                'logo': '<?= !empty($store->logo) ? base_url('uploads/' . $store->logo) : ''; ?>',
                                'text': order
                            };
                            socket.send(JSON.stringify({
                                type: 'print-receipt',
                                data: socket_data
                            }));
                        });

                    }
                    return false;
                } else {
                    bootbox.alert('<?= lang('pos_print_error'); ?>');
                    return false;
                }
            }
        }

        async function triggerTip() {
            await sleep(600);
            $('#add_tips').click();
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
    </script>

    <script type="text/javascript">
        $(document).ready(function()
        {
            var url = base_url+"/pos/item_list_prices/";
            var is_multiprice = '<?php echo  $Settings->multiprice_enabled ?>';
            if(is_multiprice === '1'){
                $.get(url, function(data) {
                    if(data){
                        store("list_prices",JSON.stringify(data));
                        $("#tipo_precio").empty();
                        var option = "<option value='price' selected='selected'>Precio</option>";
                       $.each(JSON.parse(data), function()
                       {
                            option += "<option value ='"+this.code+"'>"+this.name+"</option>";
                       });
                       $("#tipo_precio").append(option);
                    }
                    remove("tipo_precio");
                    $("#tipo_precio").change();
                });
            }
        });
    </script>
    <?php
    if (isset($print) && !empty($print)) {
        include 'remote_printing.php';
    }
    ?>

    <script src="<?= $assets ?>dist/js/libraries.min.js?v=<?= rand() ?>" type="text/javascript"></script>
    <script src="<?= $assets ?>dist/js/scripts.min.js?v=<?= rand() ?>" type="text/javascript"></script>
    <script src="<?= $assets ?>dist/js/pos.min.js?v=<?= rand() ?>" type="text/javascript"></script>
    <script src="<?= $assets ?>dist/js/suspended_receivable.min.js?v=<?= rand() ?>"></script>
    <?php if ($Settings->remote_printing != 1 && $Settings->print_img) { ?>
        <script src="<?= $assets ?>dist/js/htmlimg.js?v=<?= rand() ?>"></script>
    <?php } ?>

    <?php if ($t_nc == '1') { ?>
        <script>
            $(function() {
                $('.rquantity').attr("readonly", true);
                $('.posdel').attr("hidden", true);
                $('.rquantity').parent().prev().prev().children().removeClass("edit");
            })
        </script>
    <?php } ?>
</body>

</html>