<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="<?= $this->config->item('language'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title . ' | ' . $Settings->site_name; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png" />
    <link href="<?= $assets ?>dist/css/www.min.css" rel="stylesheet" />
    <?= $Settings->rtl ? '<link href="' . $assets . 'dist/css/rtl.css" rel="stylesheet" />' : ''; ?>
    <!-- Anti-FOUC: aplicar tema antes de pintar -->
    <script>
        (function(){
            var t = localStorage.getItem('nx-theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    <script src="<?= $assets ?>dist/js/main.min.js"></script>
</head>
<body class="pos-v2">
<!-- Anti-FOUC body -->
<script>document.body.setAttribute('data-theme', localStorage.getItem('nx-theme')||'dark')</script>

<!-- ═══════════════════════════════════════════════
     TOAST CONTAINER
════════════════════════════════════════════════ -->
<div id="pos-toast-wrap"></div>

<!-- ═══════════════════════════════════════════════
     POS WRAPPER
════════════════════════════════════════════════ -->
<div class="pos-wrapper">

    <!-- ── SIDEBAR ──────────────────────────────── -->
    <aside class="pos-nav" id="posNav">
        <div class="pos-nav-brand">
            <div class="brand-logo">
                <?php if ($store && !empty($store->image)): ?>
                    <img src="<?= $assets ?>uploads/thumbs/<?= $store->image ?>" alt="" style="width:34px;height:34px;object-fit:cover;border-radius:8px;">
                <?php else: ?>
                    <i class="fa fa-bolt"></i>
                <?php endif; ?>
            </div>
            <div class="brand-text">
                <div class="brand-name"><?= html_escape($store ? $store->name : $Settings->site_name) ?></div>
                <div class="brand-sub">Punto de Venta</div>
            </div>
        </div>

        <nav class="pos-nav-links">
            <a href="<?= site_url('welcome') ?>" class="pos-nav-link" title="Dashboard">
                <span class="nav-icon"><i class="fa fa-home"></i></span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="<?= site_url('pos') ?>" class="pos-nav-link active" title="Nueva Venta">
                <span class="nav-icon"><i class="fa fa-cash-register"></i></span>
                <span class="nav-label">Nueva Venta</span>
            </a>
            <a href="<?= site_url('sales/opened') ?>" class="pos-nav-link" title="Ventas">
                <span class="nav-icon"><i class="fa fa-receipt"></i></span>
                <span class="nav-label">Ventas</span>
            </a>

            <div class="pos-nav-divider"></div>
            <div class="pos-nav-group-label">Catálogo</div>

            <a href="<?= site_url('customers') ?>" class="pos-nav-link" title="Clientes">
                <span class="nav-icon"><i class="fa fa-users"></i></span>
                <span class="nav-label">Clientes</span>
            </a>
            <a href="<?= site_url('products') ?>" class="pos-nav-link" title="Productos">
                <span class="nav-icon"><i class="fa fa-box-open"></i></span>
                <span class="nav-label">Productos</span>
            </a>
            <a href="<?= site_url('purchases') ?>" class="pos-nav-link" title="Compras">
                <span class="nav-icon"><i class="fa fa-truck"></i></span>
                <span class="nav-label">Compras</span>
            </a>

            <div class="pos-nav-divider"></div>
            <div class="pos-nav-group-label">Reportes</div>

            <a href="<?= site_url('reports') ?>" class="pos-nav-link" title="Reportes">
                <span class="nav-icon"><i class="fa fa-chart-bar"></i></span>
                <span class="nav-label">Reportes</span>
            </a>

            <?php if ($Admin): ?>
            <div class="pos-nav-divider"></div>
            <a href="<?= site_url('settings') ?>" class="pos-nav-link" title="Configuración">
                <span class="nav-icon"><i class="fa fa-cog"></i></span>
                <span class="nav-label">Configuración</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="pos-nav-toggle">
            <button id="navToggle" title="Colapsar menú">
                <i class="fa fa-bars" id="navToggleIcon"></i>
            </button>
        </div>
    </aside>

    <!-- ── MAIN ─────────────────────────────────── -->
    <div class="pos-main">

        <!-- ── TOPBAR ──────────────────────────── -->
        <header class="pos-topbar">
            <!-- Store info -->
            <div class="pos-topbar-store">
                <div class="store-name">
                    <i class="fa fa-store me-1" style="color:var(--nx-a1);font-size:.8rem;"></i>
                    <?= html_escape($store ? $store->name : $Settings->site_name) ?>
                </div>
                <div class="store-sub">
                    <?php if ($this->session->userdata('register_id')): ?>
                        Caja #<?= $this->session->userdata('register_id') ?>
                        &nbsp;·&nbsp; Sucursal <?= $this->session->userdata('store_id') ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pos-topbar-sep"></div>

            <!-- Hacienda status chip -->
            <div class="pos-topbar-chip ok" title="Conexión Hacienda">
                <i class="fa fa-circle-check"></i>
                <span>Hacienda</span>
            </div>

            <div class="pos-topbar-spacer"></div>

            <!-- Suspended sales bell -->
            <?php if ($suspended_sales && count($suspended_sales) > 0): ?>
            <div class="dropdown">
                <button class="pos-topbar-btn" data-bs-toggle="dropdown" title="Ventas suspendidas">
                    <i class="fa fa-bell"></i>
                    <span class="badge bg-danger"><?= count($suspended_sales) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:280px;">
                    <li class="px-2 py-1">
                        <input type="text" class="form-control form-control-sm"
                               placeholder="<?= lang('filter_by_reference') ?>"
                               data-list=".list-sus-sales" id="filter-suspended-sales">
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <?php foreach ($suspended_sales as $ss): ?>
                    <li>
                        <a class="dropdown-item list-sus-sales" href="<?= site_url('pos/?hold=' . $ss->id) ?>">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-pause-circle text-warning"></i>
                                <div>
                                    <div class="fw-semibold" style="font-size:.82rem;"><?= $ss->hold_ref ?: lang('no_ref') ?></div>
                                    <div class="text-muted" style="font-size:.72rem;"><?= $ss->customer_name ?> · <?= $this->tec->hrld($ss->date) ?></div>
                                </div>
                            </div>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a class="dropdown-item text-center" style="font-size:.78rem;" href="<?= site_url('sales/opened') ?>"><?= lang('view_all') ?></a></li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Language flags -->
            <div class="dropdown">
                <button class="pos-topbar-btn" data-bs-toggle="dropdown" title="Idioma">
                    <i class="fa fa-language"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php
                    $scanned_lang_dir = array_map(function($p){ return basename($p); }, glob(APPPATH.'language/*', GLOB_ONLYDIR));
                    foreach ($scanned_lang_dir as $entry): ?>
                    <li>
                        <a class="dropdown-item" href="<?= site_url('pos/language/'.$entry) ?>">
                            <img src="<?= $assets ?>images/<?= $entry ?>.png" alt="<?= $entry ?>" height="14" class="me-2">
                            <?= ucfirst($entry) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Producto rápido ad-hoc (Fase 11) -->
            <button class="pos-topbar-btn" id="adHocBtn" title="Producto rápido (F2)" data-bs-toggle="modal" data-bs-target="#adHocModal">
                <i class="fa fa-bolt"></i>
            </button>

            <!-- Keyboard shortcuts (Fase 9) -->
            <button class="pos-topbar-btn" id="kbdShortcutsBtn" title="Atajos de teclado">
                <i class="fa fa-keyboard"></i>
            </button>

            <!-- Print auto-toggle (Fase 7) -->
            <button class="pos-topbar-btn" id="printToggleBtn" title="Impresión automática: OFF">
                <i class="fa fa-print"></i>
            </button>

            <!-- Caja (Fase 13) -->
            <a href="<?= site_url('pos/open_register') ?>" class="pos-topbar-btn" title="Apertura de caja">
                <i class="fa fa-cash-register"></i>
            </a>

            <!-- Historial ventas + proformas (Fase 14) -->
            <a href="<?= site_url('sales') ?>" class="pos-topbar-btn" title="<?= lang('sales') ?>">
                <i class="fa fa-receipt"></i>
            </a>
            <a href="<?= site_url('sales/proforma') ?>" class="pos-topbar-btn" title="Proformas">
                <i class="fa fa-file-invoice"></i>
            </a>

            <!-- Theme toggle -->
            <button class="pos-topbar-btn" onclick="switchTheme()" title="Cambiar tema">
                <i class="fa fa-circle-half-stroke"></i>
            </button>

            <?php if ($Admin): ?>
            <a href="<?= site_url('settings') ?>" class="pos-topbar-btn" title="<?= lang('settings') ?>">
                <i class="fa fa-cog"></i>
            </a>
            <?php endif; ?>

            <!-- Clock -->
            <div class="pos-topbar-clock pos-clock"></div>

            <!-- User dropdown -->
            <div class="dropdown">
                <button class="pos-user-btn" data-bs-toggle="dropdown">
                    <div class="pos-user-avatar">
                        <?= strtoupper(substr($this->session->userdata('username'), 0, 2)) ?>
                    </div>
                    <span><?= html_escape($this->session->userdata('username')) ?></span>
                    <i class="fa fa-chevron-down" style="font-size:.65rem;opacity:.6;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;">
                    <li>
                        <a class="dropdown-item" href="<?= site_url('users/profile/'.$this->session->userdata('user_id')) ?>">
                            <i class="fa fa-user me-2"></i><?= lang('profile') ?>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= site_url('logout') ?>">
                            <i class="fa fa-right-from-bracket me-2"></i><?= lang('sign_out') ?>
                        </a>
                    </li>
                </ul>
            </div>
        </header>

        <!-- ── BODY ───────────────────────────── -->
        <div class="pos-body">

            <!-- ═══ CENTER: búsqueda + categorías + productos ═══ -->
            <div class="pos-center">

                <!-- Search bar -->
                <?php if ($Settings->enable_parquimetro != "1"): ?>
                <div class="pos-search-section">
                    <div class="pos-search-wrapper">
                        <span class="search-icon"><i class="fa fa-magnifying-glass"></i></span>
                        <input type="text" id="add_item"
                               placeholder="<?= lang('search__scan') ?> — nombre, código, SKU..."
                               autocomplete="off">
                        <div class="pos-search-kbds">
                            <kbd>F3</kbd>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Category tabs -->
                <div class="pos-cat-bar" id="posCatBar">
                    <?php
                    $allCatId = (int)$Settings->default_category;
                    ?>
                    <button class="pos-cat-btn active category"
                            id="<?= $allCatId ?>"
                            data-id="<?= $allCatId ?>">
                        <i class="fa fa-grid-2"></i>
                        <?= lang('all') ?: 'Todos' ?>
                    </button>

                    <?php if ($categories): foreach ($categories as $cat): ?>
                    <button class="pos-cat-btn category"
                            id="<?= (int)$cat->id ?>"
                            data-id="<?= (int)$cat->id ?>">
                        <i class="fa fa-tag"></i>
                        <?= html_escape($cat->name) ?>
                    </button>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Controls bar -->
                <div class="pos-controls-bar">
                    <input type="text" id="filter-categories"
                           placeholder="<?= lang('filter_categories') ?: 'Filtrar productos...' ?>"
                           autocomplete="off">

                    <div style="flex:1;"></div>

                    <button class="pos-pg-btn" id="previous" title="Anterior" disabled>
                        <i class="fa fa-chevron-left"></i>
                    </button>
                    <button class="pos-pg-btn" id="next" title="Siguiente">
                        <i class="fa fa-chevron-right"></i>
                    </button>
                    <span class="pos-pg-info" id="pgInfo"></span>
                </div>

                <!-- Product grid -->
                <div class="pos-product-grid" id="item-list">
                    <?php if (!$t_nc): ?>
                        <?= $products ?>
                    <?php else: ?>
                        <div class="pos-grid-empty">
                            <i class="fa fa-box-open"></i>
                            <p><?= lang('category_is_empty') ?></p>
                        </div>
                    <?php endif; ?>
                </div>

            </div><!-- /pos-center -->

            <!-- ═══ CART PANEL ═══════════════════════════════ -->
            <div class="pos-cart-panel">
                <?= form_open('pos', 'id="pos-sale-form"'); ?>

                <!-- Customer -->
                <div class="pcp-customer">
                    <!-- Label row -->
                    <div class="pcp-section-label">
                        <i class="fa fa-user-circle"></i>
                        <?= lang('customer') ?>
                        <button type="button" class="pcp-add-cust-btn ms-auto"
                                data-bs-toggle="modal" data-bs-target="#customerModal"
                                title="<?= lang('add_customer') ?>">
                            <i class="fa fa-user-plus"></i>
                        </button>
                    </div>

                    <!-- Hidden input para el submit -->
                    <input type="hidden" id="pos-customer-hidden" name="customer_id"
                           value="<?= (int)$Settings->default_customer ?>">

                    <!-- Barra de búsqueda (visible cuando no hay cliente) -->
                    <div class="pcp-cust-search-wrap" id="pos-cust-search-wrap">
                        <p class="pcp-cust-hint"><i class="fa fa-info-circle"></i> Escriba el nombre o número de cédula del cliente</p>
                        <?php
                        $cus = [];
                        foreach ($customers as $customer) {
                            if ((int)$customer->id === (int)$Settings->default_customer) continue;
                            $cus[$customer->id] = $customer->name . ' (' . $customer->cf2 . ')';
                        }
                        ?>
                        <?= form_dropdown('_customer_search', $cus, '',
                            'id="spos_customer" class="form-select tom-select"'); ?>
                    </div>

                    <!-- Info del cliente: lupa (re-buscar) + avatar + datos + X -->
                    <div class="pcp-cust-card is-default" id="pos-cust-card">
                        <button type="button" class="pcp-cust-lupa" id="pos-cust-lupa"
                                title="Cambiar cliente" style="display:none">
                            <i class="fa fa-search"></i>
                        </button>
                        <div class="pcp-cust-avatar" id="pos-cust-avatar">C</div>
                        <div class="pcp-cust-info">
                            <div class="pcp-cust-name" id="pos-cust-name">Cliente de Contado</div>
                            <div class="pcp-cust-meta" id="pos-cust-doc"></div>
                            <div class="pcp-cust-contact" id="pos-cust-contact"></div>
                        </div>
                        <button type="button" class="pcp-cust-clear-btn" id="pos-cust-clear"
                                title="Quitar cliente" style="display:none">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Cart header -->
                <div class="pcp-cart-bar">
                    <div class="pcp-cart-title">
                        <i class="fa fa-shopping-cart"></i>
                        <?= lang('sale_details') ?>
                    </div>
                    <span class="pcp-cart-badge" id="count">0</span>

                    <!-- Extra buttons -->
                    <div style="display:flex;gap:.3rem;margin-left:.5rem;">
                        <?php if (!$t_nc): ?>
                        <button type="button" class="pcp-cart-clear-btn" id="print_order"
                                title="<?= lang('order') ?>">
                            <i class="fa fa-print"></i>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="pcp-cart-clear-btn"
                                data-bs-toggle="modal" data-bs-target="#ModalNotes"
                                title="<?= lang('notes') ?>">
                            <i class="fa fa-comment"></i>
                        </button>
                        <?php if ($Settings->propina_enable == '1'): ?>
                        <button type="button" class="pcp-cart-clear-btn" id="add_tips">
                            <i class="fa fa-percent"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Items list -->
                <div class="pcp-items">
                    <table>
                        <thead>
                            <tr>
                                <th><?= lang('product') ?></th>
                                <th style="text-align:right;">P/U</th>
                                <th style="text-align:center;"><?= lang('qty') ?></th>
                                <th style="text-align:right;"><?= lang('total') ?></th>
                                <th style="width:24px;"></th>
                            </tr>
                        </thead>
                        <tbody id="posTable"></tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="pcp-totals">
                    <div class="pcp-total-row">
                        <span class="tl"><i class="fa fa-hashtag"></i> <?= lang('items') ?></span>
                        <span class="tv" id="count-items">0</span>
                    </div>
                    <div class="pcp-total-row">
                        <span class="tl"><i class="fa fa-list"></i> <?= lang('subtotal') ?></span>
                        <span class="tv" id="total">₡0.00</span>
                    </div>
                    <div class="pcp-total-row">
                        <a href="#" class="tl text-decoration-none" id="add_discount" style="color:var(--nx-txt4);">
                            <i class="fa fa-tag"></i> <?= lang('discount') ?>
                        </a>
                        <span class="tv" id="ds_con" style="color:var(--nx-warn);">₡0.00</span>
                    </div>
                    <div class="pcp-total-row">
                        <span class="tl"><i class="fa fa-percent"></i> IVA</span>
                        <span class="tv" id="total_tax_display">₡0.00</span>
                    </div>
                    <div class="pcp-total-divider"></div>
                    <div class="pcp-grand-total">
                        <span class="gl"><?= lang('total_payable') ?></span>
                        <span class="gv" id="total-payable">₡0.00</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="pcp-actions">
                    <div class="pcp-action-row">
                        <?php if (!$t_nc && !$apa): ?>
                        <button type="button" class="pos-btn pos-btn-warn" id="suspend">
                            <i class="fa fa-pause"></i> <?= lang('hold') ?>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="pos-btn pos-btn-danger" id="reset">
                            <i class="fa fa-times"></i> <?= lang('cancel') ?>
                        </button>
                        <?php if (!$t_nc): ?>
                        <button type="button" class="pos-btn pos-btn-ghost" id="print_bill">
                            <i class="fa fa-print"></i>
                        </button>
                        <?php endif; ?>
                    </div>

                    <button type="button"
                            class="pos-btn pos-btn-pay"
                            id="<?= $eid ? 'submit-sale' : 'payment' ?>">
                        <i class="fa fa-check-circle"></i>
                        <?= $eid ? lang('submit') : lang('payment') ?>
                        <span class="kh">F4</span>
                    </button>
                </div>

                <!-- Hidden form fields -->
                <input type="hidden" name="total_tax"     id="total_tax"      value="<?= $total_tax ?>">
                <input type="hidden" name="order_tax"     id="tax_val"        value="">
                <input type="hidden" name="order_discount" id="discount_val"  value="">
                <input type="hidden" name="count"          id="total_item"    value="">
                <input type="hidden" name="amount"         id="amount_val"    value="">
                <input type="hidden" name="paid_by"        id="paid_by_val"   value="cash">
                <input type="hidden" name="payment_note"   id="payment_note_val" value="">
                <input type="hidden" id="submit" style="display:none;">

                <?= form_close(); ?>
            </div><!-- /pos-cart-panel -->

        </div><!-- /pos-body -->
    </div><!-- /pos-main -->
</div><!-- /pos-wrapper -->

<!-- ════════════════════════════════════════════════
     MODALES
════════════════════════════════════════════════ -->

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-user-plus me-2" style="color:var(--nx-a1);"></i>
                    <?= lang('add_customer') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <?= form_open('customers/add', 'id="customer-form"'); ?>
            <div class="modal-body">
                <div id="c-alert" class="alert alert-danger d-none"></div>
                <div id="hac-alert" class="alert d-none" style="font-size:.82rem;padding:.5rem .75rem;"></div>

                <!-- Tipo + Número de identificación (Hacienda AE lookup) -->
                <div class="row g-2 mb-3">
                    <div class="col-5">
                        <label class="form-label"><?= lang('cf1') ?> <span class="text-danger">*</span></label>
                        <select name="cf1" class="form-select form-select-sm" id="cf1" required>
                            <option value="01">01 — Cédula Física</option>
                            <option value="02">02 — Cédula Jurídica</option>
                            <option value="03">03 — DIMEX</option>
                            <option value="04">04 — NITE</option>
                            <option value="05">05 — Pasaporte</option>
                        </select>
                    </div>
                    <div class="col-7">
                        <label class="form-label"><?= lang('cf2') ?> <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <?= form_input('cf2', '', 'class="form-control" id="cf2" required autocomplete="off" placeholder="Ej: 112340567"') ?>
                            <button type="button" class="btn btn-outline-info" id="btn-hac-lookup"
                                    title="Buscar en Hacienda (padrón de contribuyentes)"
                                    style="font-size:.75rem;padding:.25rem .5rem;">
                                <i class="fa fa-search" id="hac-icon"></i>
                            </button>
                        </div>
                        <small class="text-muted" style="font-size:.65rem">
                            <i class="fa fa-magic"></i> Al ingresar el número se autocompleta con datos de Hacienda
                        </small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= lang('name') ?> <span class="text-danger">*</span></label>
                    <?= form_input('name', '', 'class="form-control" id="cname" required') ?>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('email_address') ?></label>
                        <?= form_input('email', '', 'class="form-control" id="cemail"') ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('phone') ?></label>
                        <?= form_input('phone', '', 'class="form-control" id="cphone"') ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('close') ?></button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i><?= lang('add_customer') ?>
                </button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div class="modal fade" id="ModalNotes" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-comment me-2" style="color:var(--nx-a1);"></i>
                    <?= lang('notes') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?= lang('reference_note') ?></label>
                    <?= form_input('hold_ref', $reference_note ?? '', 'class="form-control" id="hold_ref"') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= lang('note') ?></label>
                    <textarea name="spos_note" id="spos_note" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('close') ?></button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fa fa-check me-1"></i><?= lang('accept') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="payModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-cash-register me-2" style="color:var(--nx-ok);"></i>
                    <?= lang('payment') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Payment method selector -->
                <div class="pay-methods-grid">
                    <button type="button" class="pay-method-btn active" data-method="cash" id="pmCash">
                        <i class="fa fa-money-bill-wave"></i>Efectivo
                    </button>
                    <button type="button" class="pay-method-btn" data-method="card" id="pmCard">
                        <i class="fa fa-credit-card"></i>Tarjeta
                    </button>
                    <button type="button" class="pay-method-btn" data-method="sinpe" id="pmSinpe">
                        <i class="fa fa-mobile-screen-button"></i>SINPE
                    </button>
                    <button type="button" class="pay-method-btn" data-method="transfer" id="pmTransfer">
                        <i class="fa fa-building-columns"></i>Transfer.
                    </button>
                </div>

                <!-- Totals display -->
                <div class="pay-totals-row">
                    <div class="pay-total-box">
                        <div class="ptb-label"><?= lang('total_payable') ?></div>
                        <div class="ptb-value" id="twt">₡0.00</div>
                    </div>
                    <div class="pay-total-box change">
                        <div class="ptb-label"><?= lang('change') ?></div>
                        <div class="ptb-value" id="balance">₡0.00</div>
                    </div>
                </div>

                <!-- Amount input -->
                <div class="pay-amount-group">
                    <label><?= lang('amount') ?></label>
                    <input type="number" id="amount" name="amount"
                           placeholder="0.00" inputmode="decimal" min="0" step="any">
                </div>

                <!-- Quick amounts -->
                <div class="pay-quick-row" id="payQuickAmounts">
                    <button type="button" class="pay-quick-btn exact" id="payExact">
                        <i class="fa fa-equals me-1"></i>Exacto
                    </button>
                    <button type="button" class="pay-quick-btn" data-amount="5000">₡5,000</button>
                    <button type="button" class="pay-quick-btn" data-amount="10000">₡10,000</button>
                    <button type="button" class="pay-quick-btn" data-amount="20000">₡20,000</button>
                    <button type="button" class="pay-quick-btn" data-amount="50000">₡50,000</button>
                </div>
            </div>
            <div class="modal-footer" style="padding:.875rem 1.25rem;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= lang('close') ?>
                </button>
                <button type="button" class="pay-submit-btn" id="submit-sale" style="flex:1;max-width:200px;">
                    <i class="fa fa-check"></i>
                    <?= lang('submit') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL: Producto rápido ad-hoc (Fase 11)
════════════════════════════════════════════════ -->
<div class="modal fade" id="adHocModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-bolt me-2" style="color:var(--nx-ok);"></i>
                    Producto rápido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Nombre -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ah-name" placeholder="Descripción del producto/servicio" autocomplete="off" required>
                </div>

                <!-- CABYS -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Código CABYS <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="ah-cabys" placeholder="0000000000000" maxlength="13" autocomplete="off" style="max-width:170px;">
                        <input type="text" class="form-control" id="ah-cabys-desc" placeholder="Descripción CABYS" readonly>
                        <button type="button" class="btn btn-outline-info" id="ah-cabys-search-btn">
                            <i class="fa fa-search" id="ah-cabys-icon"></i> Buscar
                        </button>
                    </div>
                    <!-- Panel resultados CABYS -->
                    <div id="ah-cabys-results" class="mt-2" style="display:none;max-height:220px;overflow-y:auto;border:1px solid var(--bs-border-color);border-radius:.375rem;"></div>
                </div>

                <!-- Cantidad + Costo + Precio -->
                <div class="row g-2 mb-3">
                    <div class="col-3">
                        <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="ah-qty" value="1" min="0.01" step="any">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Costo</label>
                        <input type="number" class="form-control" id="ah-cost" placeholder="0.00" min="0" step="any">
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-semibold">Precio (sin impuesto) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="ah-price" placeholder="0.00" min="0" step="any">
                    </div>
                </div>

                <!-- IVA -->
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="ah-iva-switch" role="switch">
                            <label class="form-check-label fw-semibold" for="ah-iva-switch">¿Lleva IVA?</label>
                        </div>
                        <div id="ah-iva-selector" style="display:none;" class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" id="ah-iva-select" style="max-width:240px;">
                                <?php if (!empty($impuestos_list)): ?>
                                    <?php foreach ($impuestos_list as $imp): ?>
                                        <?php if ($imp->tasa_impuesto == 0) continue; ?>
                                        <option value="<?= (int)$imp->id_impuesto ?>"
                                                data-tasa="<?= (float)$imp->tasa_impuesto ?>"
                                            <?= $imp->tasa_impuesto == 13 ? 'selected' : '' ?>>
                                            <?= html_escape($imp->descripcion_impuesto) ?> (<?= (float)$imp->tasa_impuesto ?>%)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <span class="badge bg-secondary" id="ah-iva-rate">13%</span>
                        </div>
                    </div>
                </div>

                <!-- Totales en tiempo real -->
                <div class="p-3 rounded" style="background:var(--bs-tertiary-bg);font-size:.9rem;">
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <div class="text-muted small">Precio unit.</div>
                            <div class="fw-bold" id="ah-preview-price">₡0.00</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Impuesto unit.</div>
                            <div class="fw-bold text-warning" id="ah-preview-tax">₡0.00</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Total línea</div>
                            <div class="fw-bold text-success" id="ah-preview-total">₡0.00</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-4" id="ah-confirm-btn">
                    <i class="fa fa-plus me-1"></i> Agregar al carrito
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal búsqueda CABYS -->
<div class="modal fade" id="cabysSearchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-search me-2"></i>Buscar CABYS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="cabys-q" placeholder="Buscar por código o descripción...">
                    <button type="button" class="btn btn-primary" id="cabys-go-btn">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
                <div id="cabys-results-list" style="max-height:380px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     INLINE PHP → JS VARIABLES
════════════════════════════════════════════════ -->
<script type="text/javascript">
    var base_url = '<?= base_url(); ?>',
        assets   = '<?= $assets ?>';

    var Settings = <?= json_encode($Settings); ?>;
    var username = '<?= addslashes($this->session->userdata('username')); ?>';

    window._pos_cat_id  = <?= (int)$Settings->default_category; ?>;
    window._pos_tcp     = <?= (int)$tcp; ?>;
    window._pos_sid     = <?= (int)$sid; ?>;
    window._impuestos   = <?= json_encode($impuestos_list ?: []); ?>;
    window._customers   = <?php
        $cmap = [];
        if ($customers) foreach ($customers as $c)
            $cmap[$c->id] = ['name'=>$c->name,'cf1'=>$c->cf1,'cf2'=>$c->cf2,'email'=>$c->email,'phone'=>$c->phone,'company'=>isset($c->company)?$c->company:''];
        echo json_encode($cmap);
    ?>;

    var lang = {
        no_match_found:      '<?= addslashes(lang('no_match_found')); ?>',
        please_add_product:  '<?= addslashes(lang('please_add_product')); ?>',
        r_u_sure:            '<?= addslashes(lang('r_u_sure')); ?>',
        unexpected_value:    '<?= addslashes(lang('unexpected_value')); ?>',
        remove:              '<?= addslashes(lang('delete')); ?>',
        inclusive:           '<?= addslashes(lang('inclusive')); ?>',
        exclusive:           '<?= addslashes(lang('exclusive')); ?>',
        enter_pin_code:      '<?= addslashes(lang('enter_pin_code')); ?>',
        wrong_pin:           '<?= addslashes(lang('wrong_pin')); ?>',
        type_reference_note: '<?= addslashes(lang('type_reference_note')); ?>'
    };
</script>
<script src="<?= $assets ?>dist/js/pos-core.js"></script>
</body>
</html>
