<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="<?= $this->config->item('language'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title . ' | ' . $Settings->site_name; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png" />
    <link href="<?= $assets ?>dist/css/styles.css" rel="stylesheet" type="text/css" />
    <?= $Settings->rtl ? '<link href="' . $assets . 'dist/css/rtl.css" rel="stylesheet" />' : ''; ?>
    <script src="<?= $assets ?>dist/js/main.min.js"></script>
    <style>
        .pos-container { display: flex; flex-direction: column; height: 100vh; }
        .pos-content { flex: 1; overflow: hidden; display: flex; }
        .pos-cart { background: var(--bs-light); border-right: 1px solid var(--bs-border-color); overflow-y: auto; }
        .pos-products { flex: 1; overflow-y: auto; }
        .pos-header { background: var(--bs-body-bg); border-bottom: 1px solid var(--bs-border-color); padding: 0.75rem 1rem; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; padding: 0.75rem; }
        .product-card { cursor: pointer; padding: 0.5rem; border: 1px solid var(--bs-border-color); border-radius: 0.375rem; text-align: center; transition: all 0.2s; }
        .product-card:hover { background: var(--bs-primary); color: white; border-color: var(--bs-primary); }
        .cart-item { padding: 0.75rem; border-bottom: 1px solid var(--bs-border-color); }
        .cart-totals { position: sticky; bottom: 0; background: white; border-top: 2px solid var(--bs-primary); padding: 1rem; }
        @media (max-width: 768px) {
            .pos-content { flex-direction: column; }
            .pos-cart { border-right: none; border-bottom: 1px solid var(--bs-border-color); }
            .product-grid { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); }
        }
    </style>
</head>
<body class="pos-page">
    <div class="pos-container">
        <!-- Header -->
        <header class="pos-header">
            <div class="container-fluid">
                <div class="row align-items-center g-2">
                    <div class="col-auto">
                        <h6 class="mb-0">
                            <?php if ($store) { ?>
                                <strong><?= html_escape($store->name); ?></strong>
                            <?php } else { ?>
                                <strong><?= html_escape($Settings->site_name); ?></strong>
                            <?php } ?>
                        </h6>
                    </div>
                    <div class="col">
                        <!-- Language Selector -->
                        <div class="btn-group btn-group-sm" role="group">
                            <?php
                            $scanned_lang_dir = array_map(function ($path) {
                                return basename($path);
                            }, glob(APPPATH . 'language/*', GLOB_ONLYDIR));
                            foreach ($scanned_lang_dir as $entry) {
                            ?>
                                <a href="<?= site_url('pos/language/' . $entry); ?>" class="btn btn-outline-secondary btn-sm">
                                    <img src="<?= $assets; ?>images/<?= $entry; ?>.png" alt="<?= $entry; ?>" height="16">
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <span class="clock me-3"></span>
                        <?php if ($suspended_sales && sizeof($suspended_sales) > 0) { ?>
                            <button class="btn btn-warning btn-sm me-2" data-bs-toggle="dropdown">
                                <i class="fa fa-bell"></i> <span class="badge bg-danger"><?= sizeof($suspended_sales); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><input type="text" class="form-control form-control-sm m-2" placeholder="<?= lang('filter_by_reference'); ?>" data-list=".list-suspended-sales" id="filter-suspended-sales"></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php foreach ($suspended_sales as $ss) { ?>
                                    <li><a class="dropdown-item list-suspended-sales" href="<?= site_url('pos/?hold=' . $ss->id); ?>">
                                        <?= $this->tec->hrld($ss->date); ?> (<?= $ss->customer_name; ?>)
                                        <div class="fw-bold"><?= $ss->hold_ref; ?></div>
                                    </a></li>
                                <?php } ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= site_url('sales/opened'); ?>"><?= lang('view_all'); ?></a></li>
                            </ul>
                        <?php } ?>

                        <?php if ($Admin) { ?>
                            <a href="<?= site_url('settings'); ?>" class="btn btn-outline-secondary btn-sm me-2" title="<?= lang('settings'); ?>">
                                <i class="fa fa-cog"></i>
                            </a>
                        <?php } ?>

                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fa fa-user-circle"></i> <?= html_escape($this->session->userdata('username')); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>"><?= lang('profile'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= site_url('logout'); ?>"><?= lang('sign_out'); ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="pos-content">
            <!-- Left Panel: Shopping Cart -->
            <div class="pos-cart" style="width: 100%; max-width: 450px;">
                <div class="p-3">
                    <h6 class="mb-3"><i class="fa fa-shopping-cart me-2"></i><?= lang('sale_details'); ?></h6>

                    <?= form_open('pos', 'id="pos-sale-form" class="h-100"'); ?>

                    <!-- Customer & Config Section -->
                    <div class="mb-3">
                        <?php
                        $cus = array();
                        foreach ($customers as $customer) {
                            $cus[$customer->id] = $customer->name . ' (' . $customer->cf2 . ')';
                        }
                        ?>
                        <?= form_dropdown('customer_id', $cus, set_value('customer_id', $Settings->default_customer), 'id="spos_customer" class="form-select form-select-sm tom-select" required'); ?>
                    </div>

                    <?php if ($Settings->enable_parquimetro != "1") { ?>
                        <div class="mb-3">
                            <input type="text" name="code" id="add_item" class="form-control form-control-sm" placeholder="<?= lang('search__scan'); ?>" autocomplete="off">
                        </div>
                    <?php } ?>

                    <!-- Activity Section -->
                    <div class="btn-group w-100 mb-3" role="group">
                        <?php if ($Settings->propina_enable == '1') { ?>
                            <button type="button" class="btn btn-sm btn-warning external add_tips" id="add_tips">
                                <i class="fa fa-percent"></i> <?= lang('tip'); ?>
                            </button>
                        <?php } ?>
                        <button type="button" class="btn btn-sm btn-success" id="add-customer" data-bs-toggle="modal" data-bs-target="#customerModal">
                            <i class="fa fa-plus"></i> <?= lang('customer'); ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#ModalNotes">
                            <i class="fa fa-comment"></i> <?= lang('notes'); ?>
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive mb-3" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="w-50"><?= lang('product'); ?></th>
                                    <th class="text-center"><?= lang('qty'); ?></th>
                                    <th class="text-end w-25"><?= lang('total'); ?></th>
                                    <th class="text-center" style="width: 30px;"><i class="fa fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody id="posTable"></tbody>
                        </table>
                    </div>

                    <!-- Totals Section -->
                    <div class="cart-totals">
                        <div class="row g-2 mb-2">
                            <div class="col">
                                <small class="text-muted"><?= lang('items'); ?>:</small>
                                <div class="fw-bold" id="count">0</div>
                            </div>
                            <div class="col text-end">
                                <small class="text-muted"><?= lang('subtotal'); ?>:</small>
                                <div class="fw-bold" id="total">₡0.00</div>
                            </div>
                        </div>
                        <?php if ($Settings->enable_discount) { ?>
                            <div class="row g-2 mb-2">
                                <div class="col">
                                    <a href="#" class="link-secondary text-decoration-none" id="add_discount" style="font-size: 0.875rem;">
                                        <i class="fa fa-minus-circle"></i> <?= lang('discount'); ?>
                                    </a>
                                </div>
                                <div class="col text-end">
                                    <small id="ds_con" class="fw-bold">₡0.00</small>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="row g-2 border-top pt-2">
                            <div class="col">
                                <small class="text-muted"><?= lang('total_payable'); ?>:</small>
                                <div class="h5 text-primary mb-0" id="total-payable">₡0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="btn-group w-100 mt-3" role="group">
                        <?php if (!$t_nc && !$apa) { ?>
                            <button type="button" class="btn btn-warning" id="suspend">
                                <i class="fa fa-pause"></i> <?= lang('hold'); ?>
                            </button>
                        <?php } ?>
                        <button type="button" class="btn btn-danger" id="reset">
                            <i class="fa fa-times"></i> <?= lang('cancel'); ?>
                        </button>
                    </div>

                    <div class="btn-group w-100 mt-2" role="group">
                        <?php if (!$t_nc) { ?>
                            <button type="button" class="btn btn-primary" id="print_order">
                                <i class="fa fa-print"></i> <?= lang('order'); ?>
                            </button>
                            <button type="button" class="btn btn-primary" id="print_bill">
                                <i class="fa fa-print"></i> <?= lang('invoice'); ?>
                            </button>
                        <?php } ?>
                        <button type="button" class="btn btn-success" id="<?= $eid ? 'submit-sale' : 'payment'; ?>">
                            <i class="fa fa-check"></i> <?= $eid ? lang('submit') : lang('payment'); ?>
                        </button>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="total_tax" id="total_tax" value="<?= $total_tax; ?>">
                    <input type="hidden" name="customer" id="customer" value="<?= $Settings->default_customer ?>">
                    <input type="hidden" name="order_tax" id="tax_val" value="">
                    <input type="hidden" name="order_discount" id="discount_val" value="">
                    <input type="hidden" name="count" id="total_item" value="">
                    <input type="hidden" name="amount" id="amount_val" value="">
                    <input type="hidden" name="paid_by" id="paid_by_val" value="cash">
                    <input type="hidden" name="payment_note" id="payment_note_val" value="">
                    <input type="hidden" id="submit" type="submit" style="display: none;">

                    <?= form_close(); ?>
                </div>
            </div>

            <!-- Right Panel: Products -->
            <div class="pos-products">
                <div class="pos-header">
                    <div class="row g-2">
                        <div class="col">
                            <select id="tipo_precio" class="form-select form-select-sm">
                                <option value="price"><?= lang('price'); ?></option>
                                <option value="offer_price"><?= lang('offer_price'); ?></option>
                            </select>
                        </div>
                        <div class="col">
                            <input type="text" id="filter-categories" class="form-control form-control-sm" placeholder="<?= lang('filter_categories'); ?>">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-outline-secondary" id="previous">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="next">
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="product-grid" id="item-list">
                    <?php if (!$t_nc) { ?>
                        <?= $products; ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang('add_customer'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <?= form_open('pos/add_customer', 'id="customer-form"'); ?>
                <div class="modal-body">
                    <div id="c-alert" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label class="form-label"><?= lang('name'); ?> <span class="text-danger">*</span></label>
                        <?= form_input('name', '', 'class="form-control" id="cname" required'); ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= lang('email_address'); ?></label>
                        <?= form_input('email', '', 'class="form-control" id="cemail"'); ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= lang('phone'); ?></label>
                        <?= form_input('phone', '', 'class="form-control" id="cphone"'); ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?= lang('cf1'); ?> <span class="text-danger">*</span></label>
                                <select name="cf1" class="form-select" id="cf1" required>
                                    <option value="01"><?= lang('id_card'); ?></option>
                                    <option value="02"><?= lang('legal_id'); ?></option>
                                    <option value="03">DIMEX</option>
                                    <option value="04">NITE</option>
                                    <option value="05"><?= lang('passport'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?= lang('cf2'); ?> <span class="text-danger">*</span></label>
                                <?= form_input('cf2', '', 'class="form-control" id="cf2" required'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= lang('add_customer'); ?></button>
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
                    <h5 class="modal-title"><?= lang('notes'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= lang('reference_note'); ?></label>
                        <?= form_input('hold_ref', $reference_note ?? '', 'class="form-control" id="hold_ref"'); ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('note'); ?></label>
                        <textarea name="spos_note" id="spos_note" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('close'); ?></button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?= lang('accept'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="payModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang('payment'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6><?= lang('total_payable'); ?></h6>
                            <h3 class="text-primary" id="twt">₡0.00</h3>
                        </div>
                        <div class="col-md-6">
                            <h6><?= lang('change'); ?></h6>
                            <h3 class="text-success" id="balance">₡0.00</h3>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><?= lang('payment_method'); ?></label>
                            <select id="paid_by1" name="paid_by1" class="form-select" value="<?= lang('cash'); ?>" readonly>
                                <option selected><?= lang('cash'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= lang('amount'); ?></label>
                            <input type="text" name="amount" id="amount" class="form-control kb-pad" inputmode="decimal">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= lang('reference'); ?></label>
                            <input type="text" name="fpreferenciaNA" id="fpreferenciaNA" class="form-control" value="N/A" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('close'); ?></button>
                    <button type="button" class="btn btn-success" id="submit-sale"><?= lang('submit'); ?></button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var base_url = '<?= base_url(); ?>',
            assets = '<?= $assets ?>';
        var Settings = <?= json_encode($Settings); ?>;
        var username = '<?= $this->session->userdata('username'); ?>';
    </script>
    <script src="<?= $assets ?>pos/pos.min.js"></script>
</body>
</html>
