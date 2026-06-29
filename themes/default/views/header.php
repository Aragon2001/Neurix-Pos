<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?><!DOCTYPE html>
<html lang="es" <?= $Settings->rtl ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <title><?= $page_title . ' | ' . $Settings->site_name; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="<?= $assets ?>dist/css/styles.css" rel="stylesheet">
    <?= $Settings->rtl ? '<link href="' . $assets . 'dist/css/rtl.css" rel="stylesheet">' : ''; ?>
    <link href="<?= $assets ?>dist/css/www.min.css" rel="stylesheet">
    <link href="<?= $assets ?>dist/css/neurix-theme.css" rel="stylesheet">
    <script>
    /* Anti-FOUC: aplicar tema antes de renderizar (AdminLTE 4 con data-bs-theme) */
    (function(){
        var t = localStorage.getItem('nx-theme') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', t);
        document.body.setAttribute('data-theme', t);
    })();
    </script>
    <!-- Bundle Vite: Bootstrap 5 + AdminLTE 4 + librerías modernas (sin jQuery) -->
    <script src="<?= $assets ?>dist/js/main.min.js" defer></script>
</head>
<body>
<div class="wrapper">

<!-- ═══════════════ NAVBAR (HEADER) ═══════════════ -->
<nav class="main-header navbar navbar-expand-md navbar-dark bg-dark border-bottom border-secondary sticky-top">

    <!-- Logo / Brand -->
    <a href="<?= site_url(); ?>" class="navbar-brand" style="display:flex;align-items:center;gap:9px;padding:0.5rem 1rem;">
        <span class="brand-image" style="
            display:inline-flex;align-items:center;justify-content:center;
            width:36px;height:36px;border-radius:8px;flex-shrink:0;
            background:linear-gradient(135deg,#0369a1,#38bdf8);
            color:#fff;font-size:16px;font-weight:800;
            box-shadow:0 2px 10px rgba(56,189,248,.4);">
            <?= mb_strtoupper(mb_substr($Settings->site_name, 0, 1)); ?>
        </span>
        <span class="brand-text fw-bold">
            <?= $store ? $store->name : $Settings->site_name; ?>
        </span>
    </a>

    <!-- Navbar Toggler (para móvil) -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavRight" aria-controls="navbarNavRight" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Sidebar Toggle (para mostrar/ocultar sidebar) -->
    <a href="#" class="nav-link ms-2" data-bs-toggle="offcanvas" data-bs-target="#mainSidebar" aria-controls="mainSidebar" title="Alternar sidebar">
        <i class="fa fa-bars"></i>
        <span class="d-md-none ms-2">Menú</span>
    </a>

    <!-- Navbar Right Items -->
    <div class="collapse navbar-collapse ms-auto" id="navbarNavRight">
        <ul class="navbar-nav ms-auto"


            <!-- Reloj (oculto en móvil) -->
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link clock" style="cursor:default;padding: 0.5rem;"></a>
            </li>

            <!-- Alerta inventario -->
            <?php if ($Admin && $qty_alert_num && $this->session->userdata('store_id')): ?>
            <li class="nav-item">
                <a href="<?= site_url('reports/alerts'); ?>" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= lang('alerts'); ?>">
                    <i class="fa fa-bell" style="color:#eab308;"></i>
                    <span class="badge bg-warning text-dark ms-2"><?= $qty_alert_num; ?></span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Ventas suspendidas -->
            <?php if ($suspended_sales && $this->session->userdata('store_id')): ?>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fa fa-shopping-cart"></i>
                    <span class="badge bg-warning text-dark ms-2"><?= count($suspended_sales); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header"><?= lang('recent_suspended_sales'); ?></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($suspended_sales as $ss): ?>
                    <li><a href="<?= site_url('pos/?hold=' . $ss->id); ?>" class="dropdown-item load_suspended">
                        <?= $this->tec->hrld($ss->date); ?> (<?= $ss->customer_name; ?>)<br>
                        <small><strong><?= $ss->hold_ref; ?></strong></small>
                    </a></li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="<?= site_url('sales/opened'); ?>" class="dropdown-item"><?= lang('view_all'); ?></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Destienda (multi-store) -->
            <?php if ($Settings->multi_store && !$this->session->userdata('has_store_id') && $this->session->userdata('store_id')): ?>
            <li class="nav-item">
                <a href="<?= site_url('stores/deselect_store'); ?>" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= lang('deselect_store'); ?>">
                    <i class="fa fa-sign-out"></i>
                </a>
            </li>
            <?php endif; ?>

            <!-- Toggle tema -->
            <li class="nav-item">
                <button class="nav-link nx-theme-btn" id="nxThemeToggle" onclick="nxToggleTheme()" title="Cambiar tema" style="border:none;background:transparent;cursor:pointer;">
                    <i class="fa fa-adjust"></i> <span id="nxThemeLabel" class="d-none d-sm-inline">Modo</span>
                </button>
            </li>

            <!-- Menú usuario -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?= base_url('uploads/avatars/thumbs/' . ($this->session->userdata('avatar') ? $this->session->userdata('avatar') : $this->session->userdata('gender') . '.png')); ?>"
                         class="rounded-circle" alt="Avatar" style="width:32px;height:32px;object-fit:cover;">
                    <span class="d-none d-sm-inline ms-2" style="font-weight:600;font-size:14px;">
                        <?= $this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'); ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <!-- Cabecera usuario -->
                    <li class="dropdown-header">
                        <img src="<?= base_url('uploads/avatars/' . ($this->session->userdata('avatar') ? $this->session->userdata('avatar') : $this->session->userdata('gender') . '.png')); ?>" alt="Avatar" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                        <div class="ms-2">
                            <p class="mb-0"><?= $this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'); ?></p>
                            <small><?= $this->session->userdata('email'); ?></small>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>

                    <!-- Links -->
                    <li><a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>" class="dropdown-item">
                        <i class="fa fa-user-circle-o"></i> <?= lang('profile'); ?>
                    </a></li>
                    <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                    <li><a href="<?= site_url('pos/view_bill'); ?>" target="_blank" class="dropdown-item">
                        <i class="fa fa-desktop"></i> <?= lang('view_bill'); ?>
                    </a></li>
                    <?php endif; ?>

                    <!-- Selector de idioma -->
                    <li><a href="#" class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fa fa-globe"></i> Idioma
                        <img src="<?= $assets; ?>images/<?= $Settings->selected_language; ?>.png" style="width:16px;margin-left:4px;" alt="">
                    </a>
                    <ul class="dropdown-menu">
                        <?php
                        $scanned_lang_dir = array_map(function($path){ return basename($path); }, glob(APPPATH . 'language/*', GLOB_ONLYDIR));
                        foreach ($scanned_lang_dir as $entry): ?>
                        <li><a href="<?= site_url('pos/language/' . $entry); ?>" class="dropdown-item">
                            <img src="<?= $assets; ?>images/<?= $entry; ?>.png" class="language-img" style="width:16px;margin-right:6px;" alt="">
                            <?= ucwords($entry); ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <!-- Logout -->
                    <?php if ($this->session->userdata('register_id')): ?>
                    <li><a href="<?= site_url('logout'); ?>" class="dropdown-item nx-btn-logout"
                           data-confirm="<?= htmlspecialchars(lang('register_open_alert') ?: 'Tiene una caja abierta. ¿Cerrar sesión de todas formas?') ?>">
                        <i class="fa fa-sign-out"></i> <?= lang('sign_out'); ?>
                    </a></li>
                    <?php else: ?>
                    <li><a href="<?= site_url('logout'); ?>" class="dropdown-item nx-btn-logout">
                        <i class="fa fa-sign-out"></i> <?= lang('sign_out'); ?>
                    </a></li>
                    <?php endif; ?>
                </ul>
            </li>

        </ul>
    </div>

</nav>

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<aside class="main-sidebar sidebar-dark-primary offcanvas offcanvas-start" id="mainSidebar" tabindex="-1" aria-labelledby="mainSidebarLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="mainSidebarLabel">Menú</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <nav class="offcanvas-body ps-0 pe-0">
        <ul class="nav nav-pills nav-sidebar flex-column"

            <li class="nav-item mm_welcome">
                <a href="<?= site_url(); ?>" class="nav-link">
                    <span class="nx-sico nx-ico-blue"><i class="fa fa-tachometer"></i></span>
                    <span class="nx-menu-txt"><?= lang('dashboard'); ?></span>
                </a>
            </li>

            <?php if ($Settings->multi_store && !$this->session->userdata('store_id')): ?>
            <li class="nav-item mm_stores">
                <a href="<?= site_url('stores'); ?>" class="nav-link">
                    <span class="nx-sico nx-ico-sky"><i class="fa fa-building"></i></span>
                    <span class="nx-menu-txt"><?= lang('stores'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item mm_pos">
                <a href="<?= site_url('pos'); ?>" class="nav-link">
                    <span class="nx-sico nx-ico-cyan"><i class="fa fa-shopping-bag"></i></span>
                    <span class="nx-menu-txt"><?= lang('pos'); ?></span>
                </a>
            </li>

            <?php if ($Admin): ?>
            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>

            <li class="nav-item has-treeview mm_products">
                <a href="#" class="nav-link">
                    <span class="nx-sico nx-ico-orange"><i class="fa fa-cube"></i></span>
                    <span class="nx-menu-txt"><?= lang('products'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="products_index"><a href="<?= site_url('products'); ?>" class="nav-link"><i class="fa fa-list-ul"></i><?= lang('list_products'); ?></a></li>
                    <li class="nav-item" id="products_add"><a href="<?= site_url('products/add'); ?>" class="nav-link"><i class="fa fa-plus-circle"></i><?= lang('add_product'); ?></a></li>
                    <?php if ($this->Settings->enable_fastedition == "1"): ?>
                    <li class="nav-item" id="products_fastedit"><a href="<?= site_url('products/fastedit'); ?>"><i class="fa fa-pencil"></i>Edición Rápida</a></li>
                    <?php endif; ?>
                    <li class="nav-item" id="products_ajuste"><a href="<?= site_url('products/ajuste'); ?>"><i class="fa fa-balance-scale"></i>Ajuste Inventario</a></li>
                    <li class="nav-item" id="products_import"><a href="<?= site_url('products/import'); ?>"><i class="fa fa-upload"></i><?= lang('import_products'); ?></a></li>
                    <li class="nav-item" id="products_print_barcodes"><a href="<?= site_url('products/print_barcodes'); ?>" data-bs-toggle="ajax"><i class="fa fa-barcode"></i><?= lang('print_barcodes'); ?></a></li>
                    <li class="nav-item" id="products_print_labels"><a href="<?= site_url('products/print_labels'); ?>" data-bs-toggle="ajax"><i class="fa fa-tag"></i><?= lang('print_labels'); ?></a></li>
                    <?php if ($this->Settings->multiprice_enabled == 1): ?>
                    <li class="nav-item" id="products_prices"><a href="<?= site_url('products/listprices'); ?>"><i class="fa fa-dollar"></i>Lista de Precios</a></li>
                    <li class="nav-item" id="products_addprices"><a href="<?= site_url('products/addprices'); ?>"><i class="fa fa-plus-circle"></i>Agregar Precios</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li class="nav-item has-treeview mm_categories">
                <a href="#" class="nav-link">
                    <span class="nx-sico nx-ico-teal"><i class="fa fa-tags"></i></span>
                    <span class="nx-menu-txt"><?= lang('categories'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="categories_index"><a href="<?= site_url('categories'); ?>" class="nav-link"><i class="fa fa-th-list"></i><?= lang('list_categories'); ?></a></li>
                    <li class="nav-item" id="categories_add"><a href="<?= site_url('categories/add'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_category'); ?></a></li>
                    <li class="nav-item" id="categories_import"><a href="<?= site_url('categories/import'); ?>"><i class="fa fa-upload"></i><?= lang('import_categories'); ?></a></li>
                </ul>
            </li>

            <?php if ($this->session->userdata('store_id')): ?>
            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>

            <li class="nav-item has-treeview mm_sales">
                <a href="#" class="nav-link">
                    <span class="nx-sico nx-ico-green"><i class="fa fa-line-chart"></i></span>
                    <span class="nx-menu-txt"><?= lang('sales'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="sales_index"><a href="<?= site_url('sales'); ?>"><i class="fa fa-list-alt"></i><?= lang('list_sales'); ?></a></li>
                    <li class="nav-item" id="sales_opened"><a href="<?= site_url('sales/opened'); ?>"><i class="fa fa-clock-o"></i><?= lang('list_opened_bills'); ?></a></li>
                    <?php if ($Settings->enable_layaway): ?>
                    <li class="nav-item" id="sales_apartado"><a href="<?= site_url('sales/apartado'); ?>"><i class="fa fa-bookmark-o"></i><?= lang('list_apartado_sales'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($Settings->enable_quote): ?>
                    <li class="nav-item" id="sales_proforma"><a href="<?= site_url('sales/proforma'); ?>"><i class="fa fa-file-text-o"></i><?= lang('list_quotes_sales'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li class="nav-item has-treeview mm_creditnotes">
                <a href="#" class="nav-link">
                    <span class="nx-sico nx-ico-pink"><i class="fa fa-exchange"></i></span>
                    <span class="nx-menu-txt"><?= lang('credit_notes'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="creditnotes_index"><a href="<?= site_url('CreditNotes'); ?>"><i class="fa fa-minus-circle"></i><?= lang('credit_notes'); ?></a></li>
                    <li class="nav-item" id="debitnotes_index"><a href="<?= site_url('debitnotes'); ?>"><i class="fa fa-plus-circle"></i>Notas de Débito</a></li>
                </ul>
            </li>

            <li class="nav-item has-treeview mm_purchases">
                <a href="#">
                    <span class="nx-sico nx-ico-amber"><i class="fa fa-truck"></i></span>
                    <span class="nx-menu-txt"><?= lang('purchases'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="purchases_index"><a href="<?= site_url('purchases'); ?>"><i class="fa fa-list-alt"></i><?= lang('list_purchases'); ?></a></li>
                    <li class="nav-item" id="purchases_add"><a href="<?= site_url('purchases/add'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_purchase'); ?></a></li>
                    <?php if ($Settings->fe == "1"): ?>
                    <li class="nav-item" id="document_upload"><a href="<?= site_url('cargadocumentos'); ?>"><i class="fa fa-cloud-upload"></i><?= lang('documents_upload'); ?></a></li>
                    <?php endif; ?>
                    <li class="nav-item" id="purchases_expenses"><a href="<?= site_url('purchases/expenses'); ?>"><i class="fa fa-money"></i><?= lang('list_expenses'); ?></a></li>
                    <li class="nav-item" id="purchases_add_expense"><a href="<?= site_url('purchases/add_expense'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_expense'); ?></a></li>
                    <li class="nav-item" id="purchases_fec"><a href="<?= site_url('facturascompras/'); ?>"><i class="fa fa-file-text-o"></i><?= lang('list_fec'); ?></a></li>
                    <li class="nav-item" id="purchases_add_fec"><a href="<?= site_url('facturascompras/create_fec'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_fec'); ?></a></li>
                </ul>
            </li>

            <?php endif; /* store_id */ ?>
            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>

            <li class="nav-item has-treeview mm_auth mm_customers mm_suppliers">
                <a href="#">
                    <span class="nx-sico nx-ico-violet"><i class="fa fa-users"></i></span>
                    <span class="nx-menu-txt"><?= lang('people'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="auth_users"><a href="<?= site_url('users'); ?>"><i class="fa fa-user"></i><?= lang('list_users'); ?></a></li>
                    <li class="nav-item" id="auth_add"><a href="<?= site_url('users/add'); ?>"><i class="fa fa-user-plus"></i><?= lang('add_user'); ?></a></li>
                    <li class="nav-item" id="customers_index"><a href="<?= site_url('customers'); ?>"><i class="fa fa-address-book-o"></i><?= lang('list_customers'); ?></a></li>
                    <li class="nav-item" id="customers_add"><a href="<?= site_url('customers/add'); ?>"><i class="fa fa-user-plus"></i><?= lang('add_customer'); ?></a></li>
                    <li class="nav-item" id="suppliers_index"><a href="<?= site_url('suppliers'); ?>"><i class="fa fa-industry"></i><?= lang('list_suppliers'); ?></a></li>
                    <li class="nav-item" id="suppliers_add"><a href="<?= site_url('suppliers/add'); ?>"><i class="fa fa-plus-circle"></i>Agregar Proveedor</a></li>
                </ul>
            </li>

            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>
            <li class="nav-item has-treeview mm_settings">
                <a href="#">
                    <span class="nx-sico nx-ico-slate"><i class="fa fa-sliders"></i></span>
                    <span class="nx-menu-txt"><?= lang('settings'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="settings_index"><a href="<?= site_url('settings'); ?>"><i class="fa fa-cog"></i><?= lang('settings'); ?></a></li>
                    <li class="nav-item" id="settings_actividad"><a href="<?= site_url('settings/actividad'); ?>"><i class="fa fa-briefcase"></i><?= lang('actividad'); ?></a></li>
                    <li class="nav-item" id="settings_actividad_add"><a href="<?= site_url('settings/add_actividad'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_actividad'); ?></a></li>
                    <?php if ($Settings->is_shipping == 1): ?>
                    <li class="nav-item" id="settings_shipping"><a href="<?= site_url('settings/shipping'); ?>"><i class="fa fa-truck"></i><?= lang('shipping_method'); ?></a></li>
                    <li class="nav-item" id="settings_shipping_add"><a href="<?= site_url('settings/add_shipping'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_shipping'); ?></a></li>
                    <?php endif; ?>
                    <?php if ($Settings->propina_enable == '1'): ?>
                    <li class="nav-item" id="waiting_tables"><a href="<?= site_url('settings/waiting_tables'); ?>"><i class="fa fa-table"></i>Lista de Mesas</a></li>
                    <li class="nav-item" id="settings_add_table"><a href="<?= site_url('settings/add_table'); ?>"><i class="fa fa-plus-circle"></i>Agregar Mesa</a></li>
                    <?php endif; ?>
                    <li class="nav-item" id="settings_stores"><a href="<?= site_url('settings/stores'); ?>"><i class="fa fa-building"></i><?= lang('stores'); ?></a></li>
                    <?php if ($Settings->multi_store): ?>
                    <li class="nav-item" id="settings_add_store"><a href="<?= site_url('settings/add_store'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_store'); ?></a></li>
                    <?php endif; ?>
                    <li class="nav-item" id="settings_printers"><a href="<?= site_url('settings/printers'); ?>"><i class="fa fa-print"></i><?= lang('printers'); ?></a></li>
                    <li class="nav-item" id="settings_add_printer"><a href="<?= site_url('settings/add_printer'); ?>"><i class="fa fa-plus-circle"></i><?= lang('add_printer'); ?></a></li>
                    <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                    <li class="nav-item" id="settings_backups"><a href="<?= site_url('settings/backups'); ?>"><i class="fa fa-database"></i><?= lang('backups'); ?></a></li>
                    <li><a href="<?= site_url('settings/getDownloadxml'); ?>"><i class="fa fa-file-code-o"></i>Backup XMLs</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li style="height:1px;background:var(--nx-border);margin:4px 16px;list-style:none;"></li>
            <li class="nav-item has-treeview mm_reports">
                <a href="#">
                    <span class="nx-sico nx-ico-rose"><i class="fa fa-area-chart"></i></span>
                    <span class="nx-menu-txt"><?= lang('reports'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="reports_credit_customers"><a href="<?= site_url('reports/credit_customers'); ?>"><i class="fa fa-user-circle-o"></i>Cta. clientes</a></li>
                    <?php if ($Settings->is_shipping == 1): ?>
                    <li class="nav-item" id="reports_credit_shipping"><a href="<?= site_url('reports/credit_shipping'); ?>"><i class="fa fa-truck"></i>Cta. envíos</a></li>
                    <?php endif; ?>
                    <li class="nav-item" id="reports_daily_sales"><a href="<?= site_url('reports/daily_sales'); ?>"><i class="fa fa-calendar-check-o"></i><?= lang('daily_sales'); ?></a></li>
                    <li class="nav-item" id="reports_monthly_sales"><a href="<?= site_url('reports/monthly_sales'); ?>"><i class="fa fa-calendar"></i><?= lang('monthly_sales'); ?></a></li>
                    <li class="nav-item" id="reports_monthly_fec"><a href="<?= site_url('reports/monthly_fec'); ?>"><i class="fa fa-cloud"></i><?= lang('monthly_fec'); ?></a></li>
                    <li class="nav-item" id="reports_monthly_sale_tax"><a href="<?= site_url('reports/monthly_sale_tax'); ?>"><i class="fa fa-percent"></i><?= lang('monthly_sale_tax'); ?></a></li>
                    <li class="nav-item" id="sale_fe"><a href="<?= site_url('reports/sale_fe'); ?>"><i class="fa fa-file-pdf-o"></i><?= lang('model_d104'); ?></a></li>
                    <li class="nav-item" id="d151"><a href="<?= site_url('reports/d151'); ?>"><i class="fa fa-file-pdf-o"></i><?= lang('model_d151'); ?></a></li>
                    <li class="nav-item" id="reports_compras_electronicas"><a href="<?= site_url('reports/compras_electronicas'); ?>"><i class="fa fa-shopping-cart"></i>Compras mensuales</a></li>
                    <li class="nav-item" id="reports_payments"><a href="<?= site_url('reports/payments'); ?>"><i class="fa fa-credit-card"></i><?= lang('payments_report'); ?></a></li>
                    <li class="nav-item" id="reports_registers"><a href="<?= site_url('reports/registers'); ?>"><i class="fa fa-calculator"></i><?= lang('registers_report'); ?></a></li>
                    <li class="nav-item" id="reports_top_products"><a href="<?= site_url('reports/top_products'); ?>"><i class="fa fa-trophy"></i><?= lang('top_products'); ?></a></li>
                    <li class="nav-item" id="reports_products"><a href="<?= site_url('reports/products'); ?>"><i class="fa fa-cube"></i><?= lang('products_report'); ?></a></li>
                    <li class="nav-item" id="reports_products_qty"><a href="<?= site_url('reports/products_quantity'); ?>"><i class="fa fa-sort-amount-desc"></i><?= lang('products_quantity'); ?></a></li>
                    <li class="nav-item" id="inventory_adjustment"><a href="<?= site_url('reports/inventory_adjustment'); ?>"><i class="fa fa-refresh"></i><?= lang('inventory_adjustment'); ?></a></li>
                </ul>
            </li>

            <?php else: /* no Admin */ ?>

            <li class="nav-item has-treeview mm_customers">
                <a href="#">
                    <span class="nx-sico nx-ico-violet"><i class="fa fa-users"></i></span>
                    <span class="nx-menu-txt"><?= lang('customers'); ?></span>
                    <i class="fa fa-chevron-right nx-arrow"></i>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item" id="customers_index"><a href="<?= site_url('customers'); ?>"><i class="fa fa-address-book-o"></i><?= lang('list_customers'); ?></a></li>
                    <li class="nav-item" id="customers_add"><a href="<?= site_url('customers/add'); ?>"><i class="fa fa-user-plus"></i><?= lang('add_customer'); ?></a></li>
                    <?php if ($Settings->fe == "1"): ?>
                    <li class="nav-item" id="document_upload"><a href="<?= site_url('cargadocumentos'); ?>"><i class="fa fa-cloud-upload"></i><?= lang('documents_upload'); ?></a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <?php endif; ?>

        </ul>
    </nav>
    </div>
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
