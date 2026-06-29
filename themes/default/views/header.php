<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title . ' | ' . $Settings->site_name; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="<?= $assets ?>dist/css/styles.css" rel="stylesheet">
    <?= $Settings->rtl ? '<link href="' . $assets . 'dist/css/rtl.css" rel="stylesheet">' : ''; ?>
    <link href="<?= $assets ?>dist/css/neurix-theme.css" rel="stylesheet">
    <script>
    /* Anti-FOUC: aplicar tema antes de renderizar (Bootstrap 5 / AdminLTE 4) */
    (function(){
        var t = localStorage.getItem('nx-theme') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', t);
        document.documentElement.setAttribute('data-theme', t); /* Compatibilidad Neurix */
    })();
    </script>
    <!-- Bundle Vite: Bootstrap 5 + AdminLTE 4 + librerías modernas -->
    <script src="<?= $assets ?>dist/js/main.min.js"></script>
    <link rel="stylesheet" href="<?= $assets ?>dist/css/www.min.css">
</head>
<body>
<div class="wrapper">

<!-- ═══════════════ HEADER ═══════════════ -->
<nav class="main-header navbar navbar-expand-md navbar-light bg-white border-bottom">

    <!-- Logo -->
    <a href="<?= site_url(); ?>" class="navbar-brand ps-0 pe-3">
        <span class="brand-image elevation-3" style="
            display:inline-flex;align-items:center;justify-content:center;
            width:40px;height:40px;border-radius:8px;
            background:linear-gradient(135deg,#0369a1,#38bdf8);
            color:#fff;font-size:16px;font-weight:800;
            box-shadow:0 2px 10px rgba(56,189,248,.4);">
            <?= mb_strtoupper(mb_substr($Settings->site_name, 0, 1)); ?>
        </span>
        <span class="brand-text">
            <?= $store ? $store->name : $Settings->site_name; ?>
        </span>
    </a>

    <!-- Navbar Toggle -->
    <button class="navbar-toggler order-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar Links -->
    <div class="collapse navbar-collapse order-3" id="navbarCollapse">
        <a href="#" class="sidebar-toggle" data-bs-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </a>

        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">

                <!-- Reloj -->
                <li class="hidden-xs hidden-sm">
                    <a href="#" class="clock" style="cursor:default;"></a>
                </li>

                <!-- Alerta inventario -->
                <?php if ($Admin && $qty_alert_num && $this->session->userdata('store_id')): ?>
                <li>
                    <a href="<?= site_url('reports/alerts'); ?>" data-bs-toggle="tooltip" data-placement="bottom" title="<?= lang('alerts'); ?>">
                        <i class="fa fa-bell" style="color:#eab308;"></i>
                        <span class="label label-warning"><?= $qty_alert_num; ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Ventas suspendidas -->
                <?php if ($suspended_sales && $this->session->userdata('store_id')): ?>
                <li class="dropdown notifications-menu">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fa fa-shopping-cart"></i>
                        <span class="label label-warning"><?= count($suspended_sales); ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="header"><?= lang('recent_suspended_sales'); ?></li>
                        <li>
                            <ul class="menu">
                                <li>
                                    <?php foreach ($suspended_sales as $ss): ?>
                                    <a href="<?= site_url('pos/?hold=' . $ss->id); ?>" class="load_suspended">
                                        <?= $this->tec->hrld($ss->date); ?> (<?= $ss->customer_name; ?>)<br>
                                        <strong><?= $ss->hold_ref; ?></strong>
                                    </a>
                                    <?php endforeach; ?>
                                </li>
                            </ul>
                        </li>
                        <li class="footer"><a href="<?= site_url('sales/opened'); ?>"><?= lang('view_all'); ?></a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- Destienda (multi-store) -->
                <?php if ($Settings->multi_store && !$this->session->userdata('has_store_id') && $this->session->userdata('store_id')): ?>
                <li>
                    <a href="<?= site_url('stores/deselect_store'); ?>" data-bs-toggle="tooltip" data-placement="bottom" title="<?= lang('deselect_store'); ?>">
                        <i class="fa fa-sign-out"></i>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Toggle tema -->
                <li style="display:flex;align-items:center;padding:0 8px;">
                    <button class="nx-theme-btn" id="nxThemeToggle" onclick="nxToggleTheme()" title="Cambiar tema">
                        <i class="fa fa-adjust"></i> <span id="nxThemeLabel">Modo</span>
                    </button>
                </li>

                <!-- Menú usuario -->
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?= base_url('uploads/avatars/thumbs/' . ($this->session->userdata('avatar') ? $this->session->userdata('avatar') : $this->session->userdata('gender') . '.png')); ?>"
                             class="user-image" alt="Avatar" style="border-radius:50%;object-fit:cover;">
                        <span class="hidden-xs" style="font-weight:600;font-size:13px;">
                            <?= $this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'); ?>
                        </span>
                        <i class="fa fa-angle-down hidden-xs" style="margin-left:4px;font-size:11px;opacity:.6;"></i>
                    </a>
                    <ul class="dropdown-menu" style="padding:0;overflow:hidden;">

                        <!-- Cabecera usuario -->
                        <li class="user-header">
                            <img src="<?= base_url('uploads/avatars/' . ($this->session->userdata('avatar') ? $this->session->userdata('avatar') : $this->session->userdata('gender') . '.png')); ?>" alt="Avatar">
                            <p><?= $this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'); ?></p>
                            <small><?= $this->session->userdata('email'); ?></small>
                        </li>

                        <!-- Links -->
                        <li>
                            <a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>" class="nx-user-menu-link">
                                <i class="fa fa-user-circle-o"></i> <?= lang('profile'); ?>
                            </a>
                        </li>
                        <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                        <li>
                            <a href="<?= site_url('pos/view_bill'); ?>" target="_blank" class="nx-user-menu-link">
                                <i class="fa fa-desktop"></i> <?= lang('view_bill'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Selector de idioma -->
                        <li>
                            <a href="#" class="nx-user-menu-link dropdown-toggle" data-bs-toggle="dropdown" style="border-bottom:none;">
                                <i class="fa fa-globe"></i> Idioma
                                <img src="<?= $assets; ?>images/<?= $Settings->selected_language; ?>.png" style="width:18px;margin-left:4px;" alt="">
                            </a>
                            <ul class="dropdown-menu" style="left:-230px;top:0;min-width:140px;">
                                <?php
                                $scanned_lang_dir = array_map(function($path){ return basename($path); }, glob(APPPATH . 'language/*', GLOB_ONLYDIR));
                                foreach ($scanned_lang_dir as $entry): ?>
                                <li>
                                    <a href="<?= site_url('pos/language/' . $entry); ?>">
                                        <img src="<?= $assets; ?>images/<?= $entry; ?>.png" class="language-img" style="width:18px;margin-right:6px;" alt="">
                                        <?= ucwords($entry); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>

                        <!-- Pie: perfil + logout -->
                        <li class="user-footer" style="padding:0;">
                            <div class="nx-user-footer">
                                <a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>" class="nx-btn-profile">
                                    <i class="fa fa-user-o"></i> <?= lang('profile'); ?>
                                </a>
                                <?php if ($this->session->userdata('register_id')): ?>
                                <a href="<?= site_url('logout'); ?>" class="nx-btn-logout"
                                   data-confirm="<?= htmlspecialchars(lang('register_open_alert') ?: 'Tiene una caja abierta. ¿Cerrar sesión de todas formas?') ?>">
                                    <i class="fa fa-sign-out"></i> <?= lang('sign_out'); ?>
                                </a>
                                <?php else: ?>
                                <a href="<?= site_url('logout'); ?>" class="nx-btn-logout">
                                    <i class="fa fa-sign-out"></i> <?= lang('sign_out'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </li>

                    </ul>
                </li>

            </ul>
        </div>
    </div>
</nav>

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<aside class="main-sidebar">
    <section class="sidebar">
        <ul class="sidebar-menu">

            <li class="mm_welcome">
                <a href="<?= site_url(); ?>">
                    <span class="nx-sico nx-ico-blue"><i class="fa fa-tachometer"></i></span>
                    <span class="nx-menu-txt"><?= lang('dashboard'); ?></span>
                </a>
            </li>

            <?php if ($Settings->multi_store && !$this->session->userdata('store_id')): ?>
            <li class="mm_stores">
                <a href="<?= site_url('stores'); ?>">
                    <span class="nx-sico nx-ico-sky"><i class="fa fa-building"></i></span>
                    <span class="nx-menu-txt"><?= lang('stores'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <li class="mm_pos">
                <a href="<?= site_url('pos'); ?>">
                    <span class="nx-sico nx-ico-cyan"><i class="fa fa-shopping-bag"></i></span>
                    <span class="nx-menu-txt"><?= lang('pos'); ?></span>
                </a>
            </li>

            <?php if ($Admin): ?>
            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>

            <li class="treeview mm_products">
                <a href="#">
                    <span class="nx-sico nx-ico-orange"><i class="fa fa-cube"></i></span>
                    <span class="nx-menu-txt"><?= lang('products'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="products_index"><a href="<?= site_url('products'); ?>"><i class="fa fa-list-ul"></i><?= lang('list_products'); ?></a></li>
                    <li id="products_add"><a href="<?= site_url('products/add'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_product'); ?></a></li>
                    <?php if ($this->Settings->enable_fastedition == "1"): ?>
                    <li id="products_fastedit"><a href="<?= site_url('products/fastedit'); ?>"><i class="fa fa-pencil"></i>Edición Rápida</a></li>
                    <?php endif; ?>
                    <li id="products_ajuste"><a href="<?= site_url('products/ajuste'); ?>"><i class="fa fa-balance-scale"></i>Ajuste Inventario</a></li>
                    <li id="products_import"><a href="<?= site_url('products/import'); ?>"><i class="fa fa-upload"></i><?= lang('import_products'); ?></a></li>
                    <li id="products_print_barcodes"><a href="<?= site_url('products/print_barcodes'); ?>" data-toggle="ajax"><i class="fa fa-barcode"></i><?= lang('print_barcodes'); ?></a></li>
                    <li id="products_print_labels"><a href="<?= site_url('products/print_labels'); ?>" data-toggle="ajax"><i class="fa fa-tag"></i><?= lang('print_labels'); ?></a></li>
                    <?php if ($this->Settings->multiprice_enabled == 1): ?>
                    <li id="products_prices"><a href="<?= site_url('products/listprices'); ?>"><i class="fa fa-dollar"></i>Lista de Precios</a></li>
                    <li id="products_addprices"><a href="<?= site_url('products/addprices'); ?>"><i class="fa fa-plus-circle"></i>Agregar Precios</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li class="treeview mm_categories">
                <a href="#">
                    <span class="nx-sico nx-ico-teal"><i class="fa fa-tags"></i></span>
                    <span class="nx-menu-txt"><?= lang('categories'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="categories_index"><a href="<?= site_url('categories'); ?>"><i class="fa fa-th-list"></i><?= lang('list_categories'); ?></a></li>
                    <li id="categories_add"><a href="<?= site_url('categories/add'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_category'); ?></a></li>
                    <li id="categories_import"><a href="<?= site_url('categories/import'); ?>"><i class="fa fa-upload"></i><?= lang('import_categories'); ?></a></li>
                </ul>
            </li>

            <?php if ($this->session->userdata('store_id')): ?>
            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>

            <li class="treeview mm_sales">
                <a href="#">
                    <span class="nx-sico nx-ico-green"><i class="fa fa-line-chart"></i></span>
                    <span class="nx-menu-txt"><?= lang('sales'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="sales_index"><a href="<?= site_url('sales'); ?>"><i class="fa fa-list-alt"></i><?= lang('list_sales'); ?></a></li>
                    <li id="sales_opened"><a href="<?= site_url('sales/opened'); ?>"><i class="fa fa-clock-o"></i><?= lang('list_opened_bills'); ?></a></li>
                    <?php if ($Settings->enable_layaway): ?>
                    <li id="sales_apartado"><a href="<?= site_url('sales/apartado'); ?>"><i class="fa fa-bookmark-o"></i><?= lang('list_apartado_sales'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($Settings->enable_quote): ?>
                    <li id="sales_proforma"><a href="<?= site_url('sales/proforma'); ?>"><i class="fa fa-file-text-o"></i><?= lang('list_quotes_sales'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li class="treeview mm_creditnotes">
                <a href="#">
                    <span class="nx-sico nx-ico-pink"><i class="fa fa-exchange"></i></span>
                    <span class="nx-menu-txt"><?= lang('credit_notes'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="creditnotes_index"><a href="<?= site_url('CreditNotes'); ?>"><i class="fa fa-minus-circle"></i><?= lang('credit_notes'); ?></a></li>
                    <li id="debitnotes_index"><a href="<?= site_url('debitnotes'); ?>"><i class="fa fa-plus-circle"></i>Notas de Débito</a></li>
                </ul>
            </li>

            <li class="treeview mm_purchases">
                <a href="#">
                    <span class="nx-sico nx-ico-amber"><i class="fa fa-truck"></i></span>
                    <span class="nx-menu-txt"><?= lang('purchases'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="purchases_index"><a href="<?= site_url('purchases'); ?>"><i class="fa fa-list-alt"></i><?= lang('list_purchases'); ?></a></li>
                    <li id="purchases_add"><a href="<?= site_url('purchases/add'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_purchase'); ?></a></li>
                    <?php if ($Settings->fe == "1"): ?>
                    <li id="document_upload"><a href="<?= site_url('cargadocumentos'); ?>"><i class="fa fa-cloud-upload"></i><?= lang('documents_upload'); ?></a></li>
                    <?php endif; ?>
                    <li id="purchases_expenses"><a href="<?= site_url('purchases/expenses'); ?>"><i class="fa fa-money"></i><?= lang('list_expenses'); ?></a></li>
                    <li id="purchases_add_expense"><a href="<?= site_url('purchases/add_expense'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_expense'); ?></a></li>
                    <li id="purchases_fec"><a href="<?= site_url('facturascompras/'); ?>"><i class="fa fa-file-text-o"></i><?= lang('list_fec'); ?></a></li>
                    <li id="purchases_add_fec"><a href="<?= site_url('facturascompras/create_fec'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_fec'); ?></a></li>
                </ul>
            </li>

            <?php endif; /* store_id */ ?>
            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>

            <li class="treeview mm_auth mm_customers mm_suppliers">
                <a href="#">
                    <span class="nx-sico nx-ico-violet"><i class="fa fa-users"></i></span>
                    <span class="nx-menu-txt"><?= lang('people'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="auth_users"><a href="<?= site_url('users'); ?>"><i class="fa fa-user"></i><?= lang('list_users'); ?></a></li>
                    <li id="auth_add"><a href="<?= site_url('users/add'); ?>"><i class="fa fa-user-plus"></i><?= lang('add_user'); ?></a></li>
                    <li id="customers_index"><a href="<?= site_url('customers'); ?>"><i class="fa fa-address-book-o"></i><?= lang('list_customers'); ?></a></li>
                    <li id="customers_add"><a href="<?= site_url('customers/add'); ?>"><i class="fa fa-user-plus"></i><?= lang('add_customer'); ?></a></li>
                    <li id="suppliers_index"><a href="<?= site_url('suppliers'); ?>"><i class="fa fa-industry"></i><?= lang('list_suppliers'); ?></a></li>
                    <li id="suppliers_add"><a href="<?= site_url('suppliers/add'); ?>"><i class="fa fa-plus-circle"></i>Agregar Proveedor</a></li>
                </ul>
            </li>

            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>
            <li class="treeview mm_settings">
                <a href="#">
                    <span class="nx-sico nx-ico-slate"><i class="fa fa-sliders"></i></span>
                    <span class="nx-menu-txt"><?= lang('settings'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="settings_index"><a href="<?= site_url('settings'); ?>"><i class="fa fa-cog"></i><?= lang('settings'); ?></a></li>
                    <li id="settings_actividad"><a href="<?= site_url('settings/actividad'); ?>"><i class="fa fa-briefcase"></i><?= lang('actividad'); ?></a></li>
                    <li id="settings_actividad_add"><a href="<?= site_url('settings/add_actividad'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_actividad'); ?></a></li>
                    <?php if ($Settings->is_shipping == 1): ?>
                    <li id="settings_shipping"><a href="<?= site_url('settings/shipping'); ?>"><i class="fa fa-truck"></i><?= lang('shipping_method'); ?></a></li>
                    <li id="settings_shipping_add"><a href="<?= site_url('settings/add_shipping'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_shipping'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($Settings->propina_enable == '1'): ?>
                    <li id="waiting_tables"><a href="<?= site_url('settings/waiting_tables'); ?>"><i class="fa fa-table"></i>Lista de Mesas</a></li>
                    <li id="settings_add_table"><a href="<?= site_url('settings/add_table'); ?>"><i class="fa fa-plus-circle"></i>Agregar Mesa</a></li>
                    <?php endif; ?>
                    <li id="settings_stores"><a href="<?= site_url('settings/stores'); ?>"><i class="fa fa-building"></i><?= lang('stores'); ?></a></li>
                    <?php if ($Settings->multi_store): ?>
                    <li id="settings_add_store"><a href="<?= site_url('settings/add_store'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_store'); ?></a></li>
                    <?php endif; ?>
                    <li id="settings_printers"><a href="<?= site_url('settings/printers'); ?>"><i class="fa fa-print"></i><?= lang('printers'); ?></a></li>
                    <li id="settings_add_printer"><a href="<?= site_url('settings/add_printer'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_printer'); ?></a></li>
                    <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                    <li id="settings_backups"><a href="<?= site_url('settings/backups'); ?>"><i class="fa fa-database"></i><?= lang('backups'); ?></a></li>
                    <li><a href="<?= site_url('settings/getDownloadxml'); ?>"><i class="fa fa-file-code-o"></i>Backup XMLs</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>
            <li class="treeview mm_reports">
                <a href="#">
                    <span class="nx-sico nx-ico-rose"><i class="fa fa-area-chart"></i></span>
                    <span class="nx-menu-txt"><?= lang('reports'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="reports_credit_customers"><a href="<?= site_url('reports/credit_customers'); ?>"><i class="fa fa-user-circle-o"></i>Cta. clientes</a></li>
                    <?php if ($Settings->is_shipping == 1): ?>
                    <li id="reports_credit_shipping"><a href="<?= site_url('reports/credit_shipping'); ?>"><i class="fa fa-truck"></i>Cta. envíos</a></li>
                    <?php endif; ?>
                    <li id="reports_daily_sales"><a href="<?= site_url('reports/daily_sales'); ?>"><i class="fa fa-calendar-check-o"></i><?= lang('daily_sales'); ?></a></li>
                    <li id="reports_monthly_sales"><a href="<?= site_url('reports/monthly_sales'); ?>"><i class="fa fa-calendar"></i><?= lang('monthly_sales'); ?></a></li>
                    <li id="reports_monthly_fec"><a href="<?= site_url('reports/monthly_fec'); ?>"><i class="fa fa-cloud"></i><?= lang('monthly_fec'); ?></a></li>
                    <li id="reports_monthly_sale_tax"><a href="<?= site_url('reports/monthly_sale_tax'); ?>"><i class="fa fa-percent"></i><?= lang('monthly_sale_tax'); ?></a></li>
                    <li id="sale_fe"><a href="<?= site_url('reports/sale_fe'); ?>"><i class="fa fa-file-pdf-o"></i><?= lang('model_d104'); ?></a></li>
                    <li id="d151"><a href="<?= site_url('reports/d151'); ?>"><i class="fa fa-file-pdf-o"></i><?= lang('model_d151'); ?></a></li>
                    <li id="reports_compras_electronicas"><a href="<?= site_url('reports/compras_electronicas'); ?>"><i class="fa fa-shopping-cart"></i>Compras mensuales</a></li>
                    <li id="reports_payments"><a href="<?= site_url('reports/payments'); ?>"><i class="fa fa-credit-card"></i><?= lang('payments_report'); ?></a></li>
                    <li id="reports_registers"><a href="<?= site_url('reports/registers'); ?>"><i class="fa fa-calculator"></i><?= lang('registers_report'); ?></a></li>
                    <li id="reports_top_products"><a href="<?= site_url('reports/top_products'); ?>"><i class="fa fa-trophy"></i><?= lang('top_products'); ?></a></li>
                    <li id="reports_products"><a href="<?= site_url('reports/products'); ?>"><i class="fa fa-cube"></i><?= lang('products_report'); ?></a></li>
                    <li id="reports_products_qty"><a href="<?= site_url('reports/products_quantity'); ?>"><i class="fa fa-sort-amount-desc"></i><?= lang('products_quantity'); ?></a></li>
                    <li id="inventory_adjustment"><a href="<?= site_url('reports/inventory_adjustment'); ?>"><i class="fa fa-refresh"></i><?= lang('inventory_adjustment'); ?></a></li>
                </ul>
            </li>

            <?php else: /* no Admin */ ?>

            <li class="treeview mm_customers">
                <a href="#">
                    <span class="nx-sico nx-ico-violet"><i class="fa fa-users"></i></span>
                    <span class="nx-menu-txt"><?= lang('customers'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="treeview-menu">
                    <li id="customers_index"><a href="<?= site_url('customers'); ?>"><i class="fa fa-address-book-o"></i><?= lang('list_customers'); ?></a></li>
                    <li id="customers_add"><a href="<?= site_url('customers/add'); ?>"><i class="fa fa-user-plus"></i><?= lang('add_customer'); ?></a></li>
                    <?php if ($Settings->fe == "1"): ?>
                    <li id="document_upload"><a href="<?= site_url('cargadocumentos'); ?>"><i class="fa fa-cloud-upload"></i><?= lang('documents_upload'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <?php endif; ?>

        </ul>
    </section>
</aside>

<!-- ═══════════════ CONTENT WRAPPER ═══════════════ -->
<div class="content-wrapper">
    <section class="content-header">
        <h1><?= $page_title; ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= site_url(); ?>"><i class="fa fa-tachometer"></i> <?= lang('home'); ?></a></li>
            <?php foreach ($bc as $b): ?>
                <?php if ($b['link'] === '#'): ?>
                    <li class="active"><?= $b['page']; ?></li>
                <?php else: ?>
                    <li><a href="<?= $b['link']; ?>"><?= $b['page']; ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </section>

    <div class="col-lg-12 alerts" style="padding-top:10px;">
        <div id="custom-alerts" style="display:none;">
            <div class="alert alert-dismissable">
                <div class="custom-msg"></div>
            </div>
        </div>
        <?php if ($error || $warning || $message): ?>
        <script>
        window._nxAlerts = window._nxAlerts || [];
        <?php if ($error): ?>window._nxAlerts.push({icon:'error',title:<?= json_encode(strip_tags($error)) ?>});<?php endif; ?>
        <?php if ($warning): ?>window._nxAlerts.push({icon:'warning',title:<?= json_encode(strip_tags($warning)) ?>});<?php endif; ?>
        <?php if ($message): ?>window._nxAlerts.push({icon:'success',title:<?= json_encode(strip_tags($message)) ?>});<?php endif; ?>
        </script>
        <?php endif; ?>
    </div>
    <div class="clearfix"></div>
