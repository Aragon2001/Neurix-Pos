<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?><!DOCTYPE html>
<html lang="es" <?= $Settings->rtl ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <title><?= $page_title . ' | ' . $Settings->site_name; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="<?= base_url('themes/default/assets/dist/css/www.min.css'); ?>" rel="stylesheet">
    <?= $Settings->rtl ? '<link href="' . base_url('themes/default/assets/dist/css/rtl.css') . '" rel="stylesheet">' : ''; ?>
    <script>
    (function(){
        var t = localStorage.getItem('nx-theme') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', t);
        document.body.setAttribute('data-theme', t);
    })();
    </script>
    <script src="<?= base_url('themes/default/assets/dist/js/main.min.js'); ?>" defer></script>
</head>
<body class="layout-fixed sidebar-expand-lg">
<div class="app-wrapper">

<!-- ════════════════════════════════════════════════
     APP-HEADER  (grid-area: lte-app-header)
════════════════════════════════════════════════ -->
<nav class="app-header navbar navbar-expand">
    <div class="container-fluid px-3">

        <!-- Sidebar toggle -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link nx-sidebar-toggle" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fa fa-bars"></i>
                </a>
            </li>
        </ul>

        <!-- ── Derecha ── -->
        <ul class="navbar-nav ms-auto align-items-center gap-1">

            <!-- Reloj -->
            <li class="nav-item d-none d-md-inline-block">
                <span class="nav-link nx-clock" id="nxClock" style="cursor:default;font-size:13px;font-variant-numeric:tabular-nums;"></span>
            </li>

            <!-- Alerta inventario -->
            <?php if ($Admin && $qty_alert_num && $this->session->userdata('store_id')): ?>
            <li class="nav-item">
                <a href="<?= site_url('reports/alerts'); ?>" class="nav-link position-relative"
                   data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= lang('alerts'); ?>">
                    <i class="fa fa-bell"></i>
                    <span class="navbar-badge badge text-bg-warning"><?= $qty_alert_num; ?></span>
                </a>
            </li>
            <?php endif; ?>

            <!-- Ventas suspendidas -->
            <?php if ($suspended_sales && $this->session->userdata('store_id')): ?>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link position-relative dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="fa fa-cart-shopping"></i>
                    <span class="navbar-badge badge text-bg-warning"><?= count($suspended_sales); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:260px;">
                    <li class="dropdown-header py-2"><?= lang('recent_suspended_sales'); ?></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php foreach ($suspended_sales as $ss): ?>
                    <li><a href="<?= site_url('pos/?hold=' . $ss->id); ?>" class="dropdown-item load_suspended">
                        <i class="fa fa-clock fa-sm me-2 opacity-50"></i>
                        <?= $this->tec->hrld($ss->date); ?> — <?= $ss->customer_name; ?><br>
                        <small class="text-muted ms-3"><?= $ss->hold_ref; ?></small>
                    </a></li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a href="<?= site_url('sales/opened'); ?>" class="dropdown-item text-center"><?= lang('view_all'); ?></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <!-- Deseleccionar tienda -->
            <?php if ($Settings->multi_store && !$this->session->userdata('has_store_id') && $this->session->userdata('store_id')): ?>
            <li class="nav-item">
                <a href="<?= site_url('stores/deselect_store'); ?>" class="nav-link"
                   data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= lang('deselect_store'); ?>">
                    <i class="fa fa-right-from-bracket"></i>
                </a>
            </li>
            <?php endif; ?>

            <!-- Toggle tema -->
            <li class="nav-item">
                <button class="nav-link nx-theme-btn" onclick="nxToggleTheme()" title="Cambiar tema">
                    <i class="fa fa-circle-half-stroke" id="nxThemeIcon"></i>
                </button>
            </li>

            <!-- ── Dropdown USUARIO (card estilo referencia) ── -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link d-flex align-items-center gap-2 nx-user-toggle"
                   data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <img src="<?= base_url('uploads/avatars/thumbs/' . ($this->session->userdata('avatar') ?: $this->session->userdata('gender') . '.png')); ?>"
                         class="rounded-circle nx-header-avatar" alt="Avatar">
                    <span class="d-none d-lg-inline fw-semibold" style="font-size:13px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= $this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'); ?>
                    </span>
                    <i class="fa fa-angle-down fa-xs opacity-75 d-none d-lg-inline"></i>
                </a>

                <!-- Card de perfil -->
                <div class="dropdown-menu dropdown-menu-end nx-user-card p-0">
                    <!-- Cabecera del card -->
                    <div class="nx-user-card-header text-center p-4">
                        <div class="position-relative d-inline-block">
                            <img src="<?= base_url('uploads/avatars/' . ($this->session->userdata('avatar') ?: $this->session->userdata('gender') . '.png')); ?>"
                                 class="rounded-circle nx-user-card-avatar" alt="Avatar">
                            <span class="nx-user-card-status-dot" title="Conectado"></span>
                        </div>
                        <div class="fw-bold mt-2" style="font-size:15px;color:#f1f5f9;">
                            <?= $this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'); ?>
                        </div>
                        <div class="mt-1">
                            <span class="badge nx-role-badge"><?= $Admin ? 'Administrador' : 'Cajero'; ?></span>
                        </div>
                    </div>

                    <!-- Info rows -->
                    <div class="nx-user-card-info px-4 pb-2">
                        <div class="nx-user-info-row">
                            <i class="fa fa-envelope nx-info-icon"></i>
                            <span><?= $this->session->userdata('email'); ?></span>
                        </div>
                        <div class="nx-user-info-row">
                            <i class="fa fa-shield nx-info-icon"></i>
                            <span>Rol: <?= $Admin ? 'Administrador' : 'Cajero'; ?></span>
                        </div>
                        <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                        <div class="nx-user-info-row">
                            <i class="fa fa-desktop nx-info-icon"></i>
                            <a href="<?= site_url('pos/view_bill'); ?>" target="_blank" class="text-decoration-none" style="color:var(--nx-a1,#38bdf8);">
                                <?= lang('view_bill'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <!-- Idioma -->
                        <div class="nx-user-info-row">
                            <i class="fa fa-globe nx-info-icon"></i>
                            <div class="dropdown w-100">
                                <a href="#" class="dropdown-toggle text-decoration-none d-flex align-items-center gap-1"
                                   style="font-size:13px;color:inherit;" data-bs-toggle="dropdown">
                                    <img src="<?= $assets; ?>images/<?= $Settings->selected_language; ?>.png" style="width:14px;" alt="">
                                    <?= ucwords($Settings->selected_language); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php
                                    $scanned_lang_dir = array_map(function($p){ return basename($p); }, glob(APPPATH . 'language/*', GLOB_ONLYDIR));
                                    foreach ($scanned_lang_dir as $entry): ?>
                                    <li><a href="<?= site_url('pos/language/' . $entry); ?>" class="dropdown-item">
                                        <img src="<?= $assets; ?>images/<?= $entry; ?>.png" style="width:14px;margin-right:6px;" alt="">
                                        <?= ucwords($entry); ?>
                                    </a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="nx-user-card-actions px-3 pb-3 d-flex gap-2">
                        <a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>"
                           class="btn btn-outline-primary btn-sm flex-fill">
                            <i class="fa fa-circle-user me-1"></i> <?= lang('profile'); ?>
                        </a>
                        <?php if ($this->session->userdata('register_id')): ?>
                        <a href="<?= site_url('logout'); ?>" class="btn btn-danger btn-sm flex-fill nx-btn-logout"
                           data-confirm="<?= htmlspecialchars(lang('register_open_alert') ?: 'Tiene una caja abierta. ¿Cerrar sesión?') ?>">
                            <i class="fa fa-right-from-bracket me-1"></i> <?= lang('sign_out'); ?>
                        </a>
                        <?php else: ?>
                        <a href="<?= site_url('logout'); ?>" class="btn btn-danger btn-sm flex-fill nx-btn-logout">
                            <i class="fa fa-right-from-bracket me-1"></i> <?= lang('sign_out'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </li>

        </ul>
    </div>
</nav>

<!-- ════════════════════════════════════════════════
     APP-SIDEBAR  (grid-area: lte-app-sidebar)
════════════════════════════════════════════════ -->
<aside class="app-sidebar nx-sidebar shadow" data-bs-theme="dark">

    <!-- Marca / Logo -->
    <div class="sidebar-brand">
        <a href="<?= site_url(); ?>" class="brand-link d-flex align-items-center gap-2 text-decoration-none">
            <span class="nx-brand-icon">
                <?= mb_strtoupper(mb_substr($Settings->site_name, 0, 1)); ?>
            </span>
            <span class="brand-text fw-bold" style="font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:155px;">
                <?= $store ? $store->name : $Settings->site_name; ?>
            </span>
        </a>
    </div>

    <!-- ── Bloque de usuario dentro del sidebar ── -->
    <div class="nx-sidebar-userblock">
        <div class="nx-sb-avatar-wrap">
            <img src="<?= base_url('uploads/avatars/' . ($this->session->userdata('avatar') ?: $this->session->userdata('gender') . '.png')); ?>"
                 class="nx-sb-avatar" alt="Avatar">
            <span class="nx-sb-status-dot" title="Conectado"></span>
        </div>
        <div class="nx-sb-info">
            <div class="nx-sb-name">
                <?= $this->session->userdata('first_name') . ' ' . mb_substr($this->session->userdata('last_name'), 0, 12); ?>
            </div>
            <div class="nx-sb-email"><?= $this->session->userdata('email'); ?></div>
            <div class="nx-sb-status-row">
                <span class="nx-sb-dot-green"></span>
                <span>Conectado</span>
                <span class="nx-sb-role"><?= $Admin ? 'Admin' : 'Cajero'; ?></span>
            </div>
        </div>
        <a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>"
           class="nx-sb-edit-btn" title="Editar perfil">
            <i class="fa fa-pen fa-xs"></i>
        </a>
    </div>

    <!-- ── Menú de navegación ── -->
    <div class="sidebar-wrapper">
        <nav aria-label="Navegación principal">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="mainSidebarMenu">

                <!-- Dashboard -->
                <li class="nav-item mm_welcome">
                    <a href="<?= site_url(); ?>" class="nav-link">
                        <span class="nx-sico nx-ico-blue"><i class="fa fa-gauge"></i></span>
                        <p><?= lang('dashboard'); ?></p>
                    </a>
                </li>

                <?php if ($Settings->multi_store && !$this->session->userdata('store_id')): ?>
                <li class="nav-item mm_stores">
                    <a href="<?= site_url('stores'); ?>" class="nav-link">
                        <span class="nx-sico nx-ico-sky"><i class="fa fa-building"></i></span>
                        <p><?= lang('stores'); ?></p>
                    </a>
                </li>
                <?php endif; ?>

                <!-- POS -->
                <li class="nav-item mm_pos">
                    <a href="<?= site_url('pos'); ?>" class="nav-link">
                        <span class="nx-sico nx-ico-cyan"><i class="fa fa-cash-register"></i></span>
                        <p><?= lang('pos'); ?></p>
                    </a>
                </li>

                <?php if ($Admin): ?>
                <li class="nav-header">Gestión</li>

                <!-- Productos -->
                <li class="nav-item has-treeview mm_products">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-orange"><i class="fa fa-box"></i></span>
                        <p><?= lang('products'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="products_index"><a href="<?= site_url('products'); ?>" class="nav-link"><i class="fa fa-list-ul nav-icon"></i><p><?= lang('list_products'); ?></p></a></li>
                        <li class="nav-item" id="products_add"><a href="<?= site_url('products/add'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_product'); ?></p></a></li>
                        <?php if ($this->Settings->enable_fastedition == "1"): ?>
                        <li class="nav-item" id="products_fastedit"><a href="<?= site_url('products/fastedit'); ?>" class="nav-link"><i class="fa fa-pencil nav-icon"></i><p>Edición Rápida</p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="products_ajuste"><a href="<?= site_url('products/ajuste'); ?>" class="nav-link"><i class="fa fa-scale-balanced nav-icon"></i><p>Ajuste Inventario</p></a></li>
                        <li class="nav-item" id="products_import"><a href="<?= site_url('products/import'); ?>" class="nav-link"><i class="fa fa-upload nav-icon"></i><p><?= lang('import_products'); ?></p></a></li>
                        <li class="nav-item" id="products_print_barcodes"><a href="<?= site_url('products/print_barcodes'); ?>" class="nav-link" data-bs-toggle="ajax"><i class="fa fa-barcode nav-icon"></i><p><?= lang('print_barcodes'); ?></p></a></li>
                        <li class="nav-item" id="products_print_labels"><a href="<?= site_url('products/print_labels'); ?>" class="nav-link" data-bs-toggle="ajax"><i class="fa fa-tag nav-icon"></i><p><?= lang('print_labels'); ?></p></a></li>
                        <?php if ($this->Settings->multiprice_enabled == 1): ?>
                        <li class="nav-item" id="products_prices"><a href="<?= site_url('products/listprices'); ?>" class="nav-link"><i class="fa fa-dollar-sign nav-icon"></i><p>Lista de Precios</p></a></li>
                        <li class="nav-item" id="products_addprices"><a href="<?= site_url('products/addprices'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p>Agregar Precios</p></a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Categorías -->
                <li class="nav-item has-treeview mm_categories">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-teal"><i class="fa fa-tags"></i></span>
                        <p><?= lang('categories'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="categories_index"><a href="<?= site_url('categories'); ?>" class="nav-link"><i class="fa fa-table-list nav-icon"></i><p><?= lang('list_categories'); ?></p></a></li>
                        <li class="nav-item" id="categories_add"><a href="<?= site_url('categories/add'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_category'); ?></p></a></li>
                        <li class="nav-item" id="categories_import"><a href="<?= site_url('categories/import'); ?>" class="nav-link"><i class="fa fa-upload nav-icon"></i><p><?= lang('import_categories'); ?></p></a></li>
                    </ul>
                </li>

                <?php if ($this->session->userdata('store_id')): ?>
                <li class="nav-header">Comercial</li>

                <!-- Ventas -->
                <li class="nav-item has-treeview mm_sales">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-green"><i class="fa fa-chart-line"></i></span>
                        <p><?= lang('sales'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="sales_index"><a href="<?= site_url('sales'); ?>" class="nav-link"><i class="fa fa-list-alt nav-icon"></i><p><?= lang('list_sales'); ?></p></a></li>
                        <li class="nav-item" id="sales_opened"><a href="<?= site_url('sales/opened'); ?>" class="nav-link"><i class="fa fa-clock nav-icon"></i><p><?= lang('list_opened_bills'); ?></p></a></li>
                        <?php if ($Settings->enable_layaway): ?>
                        <li class="nav-item" id="sales_apartado"><a href="<?= site_url('sales/apartado'); ?>" class="nav-link"><i class="fa fa-bookmark nav-icon"></i><p><?= lang('list_apartado_sales'); ?></p></a></li>
                        <?php endif; ?>
                        <?php if ($Settings->enable_quote): ?>
                        <li class="nav-item" id="sales_proforma"><a href="<?= site_url('sales/proforma'); ?>" class="nav-link"><i class="fa fa-file-lines nav-icon"></i><p><?= lang('list_quotes_sales'); ?></p></a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Notas de Crédito -->
                <li class="nav-item has-treeview mm_creditnotes">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-pink"><i class="fa fa-right-left"></i></span>
                        <p><?= lang('credit_notes'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="creditnotes_index"><a href="<?= site_url('CreditNotes'); ?>" class="nav-link"><i class="fa fa-circle-minus nav-icon"></i><p><?= lang('credit_notes'); ?></p></a></li>
                        <li class="nav-item" id="debitnotes_index"><a href="<?= site_url('debitnotes'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p>Notas de Débito</p></a></li>
                    </ul>
                </li>

                <!-- Compras -->
                <li class="nav-item has-treeview mm_purchases">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-amber"><i class="fa fa-truck"></i></span>
                        <p><?= lang('purchases'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="purchases_index"><a href="<?= site_url('purchases'); ?>" class="nav-link"><i class="fa fa-list-alt nav-icon"></i><p><?= lang('list_purchases'); ?></p></a></li>
                        <li class="nav-item" id="purchases_add"><a href="<?= site_url('purchases/add'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_purchase'); ?></p></a></li>
                        <?php if ($Settings->fe == "1"): ?>
                        <li class="nav-item" id="document_upload"><a href="<?= site_url('cargadocumentos'); ?>" class="nav-link"><i class="fa fa-cloud-upload nav-icon"></i><p><?= lang('documents_upload'); ?></p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="purchases_expenses"><a href="<?= site_url('purchases/expenses'); ?>" class="nav-link"><i class="fa fa-money-bill nav-icon"></i><p><?= lang('list_expenses'); ?></p></a></li>
                        <li class="nav-item" id="purchases_add_expense"><a href="<?= site_url('purchases/add_expense'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_expense'); ?></p></a></li>
                        <li class="nav-item" id="purchases_fec"><a href="<?= site_url('facturascompras/'); ?>" class="nav-link"><i class="fa fa-file-lines nav-icon"></i><p><?= lang('list_fec'); ?></p></a></li>
                        <li class="nav-item" id="purchases_add_fec"><a href="<?= site_url('facturascompras/create_fec'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_fec'); ?></p></a></li>
                    </ul>
                </li>

                <?php endif; /* store_id */ ?>

                <li class="nav-header">Contactos</li>

                <!-- Personas -->
                <li class="nav-item has-treeview mm_auth mm_customers mm_suppliers">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-violet"><i class="fa fa-users"></i></span>
                        <p><?= lang('people'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="auth_users"><a href="<?= site_url('users'); ?>" class="nav-link"><i class="fa fa-user nav-icon"></i><p><?= lang('list_users'); ?></p></a></li>
                        <li class="nav-item" id="auth_add"><a href="<?= site_url('users/add'); ?>" class="nav-link"><i class="fa fa-user-plus nav-icon"></i><p><?= lang('add_user'); ?></p></a></li>
                        <li class="nav-item" id="customers_index"><a href="<?= site_url('customers'); ?>" class="nav-link"><i class="fa fa-address-book nav-icon"></i><p><?= lang('list_customers'); ?></p></a></li>
                        <li class="nav-item" id="customers_add"><a href="<?= site_url('customers/add'); ?>" class="nav-link"><i class="fa fa-user-plus nav-icon"></i><p><?= lang('add_customer'); ?></p></a></li>
                        <li class="nav-item" id="suppliers_index"><a href="<?= site_url('suppliers'); ?>" class="nav-link"><i class="fa fa-industry nav-icon"></i><p><?= lang('list_suppliers'); ?></p></a></li>
                        <li class="nav-item" id="suppliers_add"><a href="<?= site_url('suppliers/add'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p>Agregar Proveedor</p></a></li>
                    </ul>
                </li>

                <li class="nav-header">Sistema</li>

                <!-- Configuración -->
                <li class="nav-item has-treeview mm_settings">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-slate"><i class="fa fa-sliders"></i></span>
                        <p><?= lang('settings'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="settings_index"><a href="<?= site_url('settings'); ?>" class="nav-link"><i class="fa fa-cog nav-icon"></i><p><?= lang('settings'); ?></p></a></li>
                        <li class="nav-item" id="settings_actividad"><a href="<?= site_url('settings/actividad'); ?>" class="nav-link"><i class="fa fa-briefcase nav-icon"></i><p><?= lang('actividad'); ?></p></a></li>
                        <li class="nav-item" id="settings_actividad_add"><a href="<?= site_url('settings/add_actividad'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_actividad'); ?></p></a></li>
                        <?php if ($Settings->is_shipping == 1): ?>
                        <li class="nav-item" id="settings_shipping"><a href="<?= site_url('settings/shipping'); ?>" class="nav-link"><i class="fa fa-truck nav-icon"></i><p><?= lang('shipping_method'); ?></p></a></li>
                        <li class="nav-item" id="settings_shipping_add"><a href="<?= site_url('settings/add_shipping'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_shipping'); ?></p></a></li>
                        <?php endif; ?>
                        <?php if ($Settings->propina_enable == '1'): ?>
                        <li class="nav-item" id="waiting_tables"><a href="<?= site_url('settings/waiting_tables'); ?>" class="nav-link"><i class="fa fa-table nav-icon"></i><p>Lista de Mesas</p></a></li>
                        <li class="nav-item" id="settings_add_table"><a href="<?= site_url('settings/add_table'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p>Agregar Mesa</p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="settings_stores"><a href="<?= site_url('settings/stores'); ?>" class="nav-link"><i class="fa fa-building nav-icon"></i><p><?= lang('stores'); ?></p></a></li>
                        <?php if ($Settings->multi_store): ?>
                        <li class="nav-item" id="settings_add_store"><a href="<?= site_url('settings/add_store'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_store'); ?></p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="settings_printers"><a href="<?= site_url('settings/printers'); ?>" class="nav-link"><i class="fa fa-print nav-icon"></i><p><?= lang('printers'); ?></p></a></li>
                        <li class="nav-item" id="settings_add_printer"><a href="<?= site_url('settings/add_printer'); ?>" class="nav-link"><i class="fa fa-circle-plus nav-icon"></i><p><?= lang('add_printer'); ?></p></a></li>
                        <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                        <li class="nav-item" id="settings_backups"><a href="<?= site_url('settings/backups'); ?>" class="nav-link"><i class="fa fa-database nav-icon"></i><p><?= lang('backups'); ?></p></a></li>
                        <li class="nav-item"><a href="<?= site_url('settings/getDownloadxml'); ?>" class="nav-link"><i class="fa fa-file-code nav-icon"></i><p>Backup XMLs</p></a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Informes -->
                <li class="nav-item has-treeview mm_reports">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-rose"><i class="fa fa-chart-area"></i></span>
                        <p><?= lang('reports'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="reports_credit_customers"><a href="<?= site_url('reports/credit_customers'); ?>" class="nav-link"><i class="fa fa-circle-user nav-icon"></i><p>Cta. clientes</p></a></li>
                        <?php if ($Settings->is_shipping == 1): ?>
                        <li class="nav-item" id="reports_credit_shipping"><a href="<?= site_url('reports/credit_shipping'); ?>" class="nav-link"><i class="fa fa-truck nav-icon"></i><p>Cta. envíos</p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="reports_daily_sales"><a href="<?= site_url('reports/daily_sales'); ?>" class="nav-link"><i class="fa fa-calendar-check nav-icon"></i><p><?= lang('daily_sales'); ?></p></a></li>
                        <li class="nav-item" id="reports_monthly_sales"><a href="<?= site_url('reports/monthly_sales'); ?>" class="nav-link"><i class="fa fa-calendar nav-icon"></i><p><?= lang('monthly_sales'); ?></p></a></li>
                        <li class="nav-item" id="reports_monthly_fec"><a href="<?= site_url('reports/monthly_fec'); ?>" class="nav-link"><i class="fa fa-cloud nav-icon"></i><p><?= lang('monthly_fec'); ?></p></a></li>
                        <li class="nav-item" id="reports_monthly_sale_tax"><a href="<?= site_url('reports/monthly_sale_tax'); ?>" class="nav-link"><i class="fa fa-percent nav-icon"></i><p><?= lang('monthly_sale_tax'); ?></p></a></li>
                        <li class="nav-item" id="sale_fe"><a href="<?= site_url('reports/sale_fe'); ?>" class="nav-link"><i class="fa fa-file-pdf nav-icon"></i><p><?= lang('model_d104'); ?></p></a></li>
                        <li class="nav-item" id="d151"><a href="<?= site_url('reports/d151'); ?>" class="nav-link"><i class="fa fa-file-pdf nav-icon"></i><p><?= lang('model_d151'); ?></p></a></li>
                        <li class="nav-item" id="reports_compras_electronicas"><a href="<?= site_url('reports/compras_electronicas'); ?>" class="nav-link"><i class="fa fa-cart-shopping nav-icon"></i><p>Compras mensuales</p></a></li>
                        <li class="nav-item" id="reports_payments"><a href="<?= site_url('reports/payments'); ?>" class="nav-link"><i class="fa fa-credit-card nav-icon"></i><p><?= lang('payments_report'); ?></p></a></li>
                        <li class="nav-item" id="reports_registers"><a href="<?= site_url('reports/registers'); ?>" class="nav-link"><i class="fa fa-calculator nav-icon"></i><p><?= lang('registers_report'); ?></p></a></li>
                        <li class="nav-item" id="reports_top_products"><a href="<?= site_url('reports/top_products'); ?>" class="nav-link"><i class="fa fa-trophy nav-icon"></i><p><?= lang('top_products'); ?></p></a></li>
                        <li class="nav-item" id="reports_products"><a href="<?= site_url('reports/products'); ?>" class="nav-link"><i class="fa fa-box nav-icon"></i><p><?= lang('products_report'); ?></p></a></li>
                        <li class="nav-item" id="reports_products_qty"><a href="<?= site_url('reports/products_quantity'); ?>" class="nav-link"><i class="fa fa-sort-amount-desc nav-icon"></i><p><?= lang('products_quantity'); ?></p></a></li>
                        <li class="nav-item" id="inventory_adjustment"><a href="<?= site_url('reports/inventory_adjustment'); ?>" class="nav-link"><i class="fa fa-arrows-rotate nav-icon"></i><p><?= lang('inventory_adjustment'); ?></p></a></li>
                    </ul>
                </li>

                <?php else: /* no Admin */ ?>

                <li class="nav-item has-treeview mm_customers">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-violet"><i class="fa fa-users"></i></span>
                        <p><?= lang('customers'); ?> <i class="nav-arrow fa fa-angle-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="customers_index"><a href="<?= site_url('customers'); ?>" class="nav-link"><i class="fa fa-address-book nav-icon"></i><p><?= lang('list_customers'); ?></p></a></li>
                        <li class="nav-item" id="customers_add"><a href="<?= site_url('customers/add'); ?>" class="nav-link"><i class="fa fa-user-plus nav-icon"></i><p><?= lang('add_customer'); ?></p></a></li>
                        <?php if ($Settings->fe == "1"): ?>
                        <li class="nav-item" id="document_upload"><a href="<?= site_url('cargadocumentos'); ?>" class="nav-link"><i class="fa fa-cloud-arrow-up nav-icon"></i><p><?= lang('documents_upload'); ?></p></a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <?php endif; ?>

            </ul>
        </nav>
    </div><!-- /sidebar-wrapper -->

</aside>

<!-- ════════════════════════════════════════════════
     APP-MAIN  (grid-area: lte-app-main)
════════════════════════════════════════════════ -->
<main class="app-main">

    <!-- Breadcrumb header -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="mb-0"><?= $page_title; ?></h3>
                </div>
                <div class="col-sm-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb float-sm-end mb-0">
                            <li class="breadcrumb-item">
                                <a href="<?= site_url(); ?>"><i class="fa fa-house fa-sm"></i> <?= lang('home'); ?></a>
                            </li>
                            <?php foreach ($bc as $b): ?>
                                <?php if ($b['link'] === '#'): ?>
                                    <li class="breadcrumb-item active" aria-current="page"><?= $b['page']; ?></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item"><a href="<?= $b['link']; ?>"><?= $b['page']; ?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Content body -->
    <div class="app-content">
        <div class="container-fluid">

            <!-- Flash alerts -->
            <div id="custom-alerts" style="display:none;">
                <div class="alert alert-dismissable fade show" role="alert">
                    <div class="custom-msg"></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

            <!-- Clock JS inline (sin jQuery) -->
            <script>
            (function(){
                function tick(){
                    var el = document.getElementById('nxClock');
                    if (!el) return;
                    var now = new Date();
                    var h = String(now.getHours()).padStart(2,'0');
                    var m = String(now.getMinutes()).padStart(2,'0');
                    var s = String(now.getSeconds()).padStart(2,'0');
                    el.textContent = h + ':' + m + ':' + s;
                }
                tick();
                setInterval(tick, 1000);
            })();
            </script>
