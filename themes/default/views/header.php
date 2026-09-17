<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?><!DOCTYPE html>
<html lang="es" <?= $Settings->rtl ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <title>Neurix POS — <?= $page_title; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png"/>
    <link rel="apple-touch-icon" href="<?= $assets ?>images/icon-192.png">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <meta name="theme-color" content="#3b82f6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Neurix POS">
    <meta name="application-name" content="Neurix POS">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <?php
    /* Cache-busting: la URL cambia con cada build para que ni el service worker
       ni el caché HTTP sirvan bundles viejos */
    $nx_v = function ($p) { $f = FCPATH.$p; return base_url($p).'?v='.(is_file($f) ? filemtime($f) : '1'); };
    ?>
    <link href="<?= $nx_v('themes/default/assets/dist/css/www.min.css'); ?>" rel="stylesheet">
    <link href="<?= $nx_v('themes/default/assets/dist/css/nx-sidebar.css'); ?>" rel="stylesheet">
    <?= $Settings->rtl ? '<link href="' . base_url('themes/default/assets/dist/css/rtl.css') . '" rel="stylesheet">' : ''; ?>
    <script>
    (function(){
        // document.body todavía no existe aquí (estamos en <head>) — solo tocar
        // document.documentElement, que sí está disponible desde el inicio del parseo.
        // document.body aun no existe en este punto del parseo.
        var t = localStorage.getItem('nx-theme') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', t);
    })();
    // Exponer URL base para AJAX
    window.base_url = '<?= base_url(); ?>';
    // El token rota en cada POST; main.js lo mantiene al dia desde la cabecera
    // X-CSRF-Token de cada respuesta AJAX.
    window.CSRF_NAME = '<?= $this->security->get_csrf_token_name(); ?>';
    window.CSRF_HASH = '<?= $this->security->get_csrf_hash(); ?>';
    // Tickets que PHP dejo en cola para que esta computadora los imprima por QZ Tray.
    window._nx_qz_pendiente = <?= count((array) $this->session->userdata('qz_cola')); ?>;
    </script>
    <script src="<?= $nx_v('themes/default/assets/dist/js/main.min.js'); ?>" defer></script>
</head>
<body class="layout-fixed sidebar-expand-lg">
<script>document.body.setAttribute('data-theme', localStorage.getItem('nx-theme') || 'dark')</script>
<div class="app-wrapper">

<?php
/* ── Tabler Icons helper ─ defined early for header + sidebar + breadcrumb ── */
function ti_svg($paths, $size = 18) {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/>'.$paths.'</svg>';
}
/* Header-specific icons (sidebar icon map defined further below) */
$hti = [
  'panelleftclose' => '<path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M9 4v16"/><path d="M14 10l-2 2l2 2"/>',
  'panelleftopen'  => '<path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M9 4v16"/><path d="M14 10l2 2l-2 2"/>',
  'search'         => '<path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/>',
  'bell'           => '<path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/>',
  'sun'            => '<path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/>',
  'moon'           => '<path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/>',
  'mail'           => '<path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/><path d="M3 7l9 6l9 -6"/>',
  'shield2'        => '<path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/>',
  'globe2'         => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M3.6 9h16.8"/><path d="M3.6 15h16.8"/><path d="M11.5 3a17 17 0 0 0 0 18"/><path d="M12.5 3a17 17 0 0 1 0 18"/>',
  'logout'         => '<path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M9 12h12l-3 -3"/><path d="M18 15l3 -3"/>',
  'chevrondown'    => '<path d="M6 9l6 6l6 -6"/>',
  'cart'           => '<path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/>',
  'receipt'        => '<path d="M5 21v-16a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/>',
  'circleuser'     => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"/>',
  'filetext'       => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 13l6 0"/><path d="M9 17l6 0"/>',
  'dots'           => '<path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>',
];
?>

<!-- ════════════════════════════════════════════════
     APP-HEADER
════════════════════════════════════════════════ -->
<nav class="app-header nx-header" data-bs-theme="dark">
    <div class="container-fluid px-3">

        <!-- ── LEFT: Panel toggle + breadcrumb ── -->
        <div class="nx-hdr-left">
            <button class="nx-panel-btn" data-lte-toggle="sidebar" id="nxPanelBtn" title="<?= lang('expandir_menu'); ?>">
                <span class="nx-panel-close"><?= ti_svg($hti['panelleftclose'], 19) ?></span>
                <span class="nx-panel-open"><?= ti_svg($hti['panelleftopen'], 19) ?></span>
            </button>
            <div class="nx-hdr-breadcrumb d-none d-lg-flex">
                <span class="nx-hdr-brand">Neurix POS</span>
                <span class="nx-hdr-sep">/</span>
                <span class="nx-hdr-page"><?= html_escape($page_title) ?></span>
            </div>
        </div>

        <!-- ── CENTER: Search ── -->
        <div class="nx-hdr-search d-none d-md-flex">
            <span class="nx-search-ico"><?= ti_svg($hti['search'], 15) ?></span>
            <input type="search" class="nx-search-input" placeholder="<?= lang('buscar_en_sistema'); ?>">
            <kbd class="nx-search-kbd">⌘K</kbd>
        </div>

        <!-- ── RIGHT: actions ── -->
        <div class="nx-hdr-right">

            <!-- Reloj -->
            <span class="nx-hdr-clock d-none d-xl-flex" id="nxClock"></span>

            <!-- Alerta inventario -->
            <?php if ($Admin && $qty_alert_num && $this->session->userdata('store_id')): ?>
            <a href="<?= site_url('reports/alerts') ?>" class="nx-hdr-btn nx-hdr-btn-warn" title="<?= lang('alerts') ?>">
                <?= ti_svg($hti['bell'], 18) ?>
                <span class="nx-hdr-badge"><?= $qty_alert_num ?></span>
            </a>
            <?php endif; ?>

            <!-- Ventas suspendidas -->
            <?php if ($suspended_sales && $this->session->userdata('store_id')): ?>
            <div class="dropdown">
                <button class="nx-hdr-btn position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="<?= lang('recent_suspended_sales') ?>">
                    <?= ti_svg($hti['cart'], 18) ?>
                    <span class="nx-hdr-badge"><?= count($suspended_sales) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end nx-dropdown" style="min-width:280px;">
                    <li class="nx-dd-label"><?= lang('recent_suspended_sales') ?></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <?php foreach ($suspended_sales as $ss): ?>
                    <li>
                        <a href="<?= site_url('pos/?hold=' . $ss->id) ?>" class="dropdown-item load_suspended d-flex align-items-center gap-2 py-2">
                            <span style="opacity:.5;flex-shrink:0;"><?= ti_svg($hti['receipt'], 14) ?></span>
                            <div>
                                <div style="font-size:.82rem;font-weight:600;"><?= $this->tec->hrld($ss->date) ?> — <?= html_escape($ss->customer_name); ?></div>
                                <div style="font-size:.72rem;opacity:.55;"><?= html_escape($ss->hold_ref); ?></div>
                            </div>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a href="<?= site_url('sales/opened') ?>" class="dropdown-item text-center" style="font-size:.8rem;"><?= lang('view_all') ?></a></li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Deseleccionar tienda -->
            <?php if ($Settings->multi_store && !$this->session->userdata('has_store_id') && $this->session->userdata('store_id')): ?>
            <a href="<?= site_url('stores/deselect_store') ?>" class="nx-hdr-btn" title="<?= lang('deselect_store') ?>">
                <?= ti_svg($hti['logout'], 18) ?>
            </a>
            <?php endif; ?>

            <!-- Toggle tema -->
            <button class="nx-hdr-btn" onclick="nxToggleTheme()" id="nxThemeBtn" title="<?= lang('cambiar_tema'); ?>">
                <span class="nx-theme-sun"><?= ti_svg($hti['sun'], 18) ?></span>
                <span class="nx-theme-moon"><?= ti_svg($hti['moon'], 18) ?></span>
            </button>

            <!-- Usuario -->
            <div class="dropdown">
                <button class="nx-user-pill" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <img src="<?= avatar_usuario($this->session->userdata('avatar'), $this->session->userdata('gender'), true) ?>"
                         class="nx-avatar-sm" alt="Avatar">
                    <span class="d-none d-lg-inline"><?= html_escape($this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name')) ?></span>
                    <span class="nx-chevron-down d-none d-lg-flex"><?= ti_svg($hti['chevrondown'], 13) ?></span>
                </button>

                <!-- Card perfil -->
                <div class="dropdown-menu dropdown-menu-end nx-user-card p-0">
                    <div class="nx-user-card-header text-center p-4">
                        <div class="position-relative d-inline-block">
                            <img src="<?= avatar_usuario($this->session->userdata('avatar'), $this->session->userdata('gender')) ?>"
                                 class="rounded-circle nx-user-card-avatar" alt="Avatar">
                            <span class="nx-user-card-status-dot"></span>
                        </div>
                        <div class="fw-bold mt-2" style="font-size:15px;color:#f1f5f9;">
                            <?= html_escape($this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name')) ?>
                        </div>
                        <div class="mt-1">
                            <span class="badge nx-role-badge"><?= $Admin ? 'Administrador' : 'Cajero' ?></span>
                        </div>
                    </div>

                    <div class="nx-user-card-info px-4 pb-2">
                        <div class="nx-user-info-row">
                            <span class="nx-info-icon"><?= ti_svg($hti['mail'], 14) ?></span>
                            <span><?= html_escape($this->session->userdata('email')) ?></span>
                        </div>
                        <div class="nx-user-info-row">
                            <span class="nx-info-icon"><?= ti_svg($hti['shield2'], 14) ?></span>
                            <span>Rol: <?= $Admin ? 'Administrador' : 'Cajero' ?></span>
                        </div>
                        <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                        <div class="nx-user-info-row">
                            <span class="nx-info-icon"><?= ti_svg($hti['filetext'], 14) ?></span>
                            <a href="<?= site_url('pos/view_bill') ?>" target="_blank" class="text-decoration-none" style="color:var(--nx-a1,#38bdf8);">
                                <?= lang('view_bill') ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="nx-user-info-row">
                            <span class="nx-info-icon"><?= ti_svg($hti['globe2'], 14) ?></span>
                            <div class="dropdown w-100">
                                <a href="#" class="dropdown-toggle text-decoration-none d-flex align-items-center gap-1"
                                   style="font-size:13px;color:inherit;" data-bs-toggle="dropdown">
                                    <img src="<?= $assets ?>images/<?= $Settings->selected_language ?>.png" style="width:14px;" alt="">
                                    <?= ucwords($Settings->selected_language) ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php
                                    $scanned_lang_dir = array_map(function($p){ return basename($p); }, glob(APPPATH . 'language/*', GLOB_ONLYDIR));
                                    foreach ($scanned_lang_dir as $entry): ?>
                                    <li><a href="<?= site_url('pos/language/' . $entry) ?>" class="dropdown-item">
                                        <img src="<?= $assets ?>images/<?= $entry ?>.png" style="width:14px;margin-right:6px;" alt="">
                                        <?= ucwords($entry) ?>
                                    </a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="nx-user-card-actions px-3 pb-3 d-flex gap-2">
                        <a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')) ?>"
                           class="btn btn-outline-primary btn-sm flex-fill d-flex align-items-center justify-content-center gap-1">
                            <?= ti_svg($hti['circleuser'], 14) ?> <?= lang('profile') ?>
                        </a>
                        <?php if ($this->session->userdata('register_id')): ?>
                        <a href="<?= site_url('pos') ?>?cerrar_caja=1" class="btn btn-danger btn-sm flex-fill nx-btn-logout d-flex align-items-center justify-content-center gap-1"
                           data-confirm="<?= htmlspecialchars(lang('close_register_before_logout')) ?>">
                            <?= ti_svg($hti['logout'], 14) ?> <?= lang('sign_out') ?>
                        </a>
                        <?php else: ?>
                        <a href="<?= site_url('logout') ?>" class="btn btn-danger btn-sm flex-fill nx-btn-logout d-flex align-items-center justify-content-center gap-1">
                            <?= ti_svg($hti['logout'], 14) ?> <?= lang('sign_out') ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</nav>

<!-- ════════════════════════════════════════════════
     APP-SIDEBAR  (grid-area: lte-app-sidebar)
════════════════════════════════════════════════ -->
<?php
/* ti_svg() defined before the header nav above — guard for safety */
if (!function_exists('ti_svg')) {
    function ti_svg($paths, $size = 18) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/>'.$paths.'</svg>';
    }
}
/* ── Main nav icon SVGs (Tabler Icons) ── */
$ti = [
  'home'      => '<path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/>',
  'store'     => '<path d="M3 21l18 0"/><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/><path d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/>',
  'cart'      => '<path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/>',
  'package'   => '<path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12l0 9"/><path d="M12 12l-8 -4.5"/><path d="M16 5.25l-8 4.5"/>',
  'tag'       => '<path d="M7.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M3 6v5.172a2 2 0 0 0 .586 1.414l7.71 7.71a2.41 2.41 0 0 0 3.408 0l5.592 -5.592a2.41 2.41 0 0 0 0 -3.408l-7.71 -7.71a2 2 0 0 0 -1.414 -.586h-5.172a3 3 0 0 0 -3 3z"/>',
  'trending'  => '<path d="M4 19l16 0"/><path d="M4 15l4 -6l4 2l4 -5l4 4"/>',
  'filedollar'=> '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"/><path d="M12 17v1m0 -8v1"/>',
  'truck'     => '<path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M5 17h-2v-11a1 1 0 0 1 1 -1h9v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5"/>',
  'users'     => '<path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>',
  'settings'  => '<path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/>',
  'chartbar'  => '<path d="M3 13a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M15 9a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M9 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M4 20h14"/>',
  'chevron'   => '<path d="M9 6l6 6l-6 6"/>',
  'moon'      => '<path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/>',
  'dots'      => '<path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>',
  'dbarrows'  => '<path d="M11 7l-5 5l5 5"/><path d="M17 7l-5 5l5 5"/>',
  /* ── Sub-item icons (Tabler) ── */
  'list'      => '<path d="M9 6l11 0"/><path d="M9 12l11 0"/><path d="M9 18l11 0"/><path d="M5 6l0 .01"/><path d="M5 12l0 .01"/><path d="M5 18l0 .01"/>',
  'plus'      => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M9 12l6 0"/><path d="M12 9l0 6"/>',
  'pencil'    => '<path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4"/><path d="M13.5 6.5l4 4"/>',
  'scale'     => '<path d="M7 20l10 0"/><path d="M12 20l0 -17"/><path d="M3 6l4 -2l5 4l5 -4l4 2"/><path d="M6 9a3 3 0 0 1 -3 3"/><path d="M21 9a3 3 0 0 1 -3 3"/>',
  'upload'    => '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 9l5 -5l5 5"/><path d="M12 4l0 12"/>',
  'barcode'   => '<path d="M4 7v-1a2 2 0 0 1 2 -2h2"/><path d="M4 17v1a2 2 0 0 0 2 2h2"/><path d="M16 4h2a2 2 0 0 1 2 2v1"/><path d="M16 20h2a2 2 0 0 0 2 -2v-1"/><path d="M5 11l0 2"/><path d="M8 11l0 2"/><path d="M11 11l0 2"/><path d="M14 11l0 2"/><path d="M17 11l0 2"/>',
  'tagsm'     => '<path d="M11 3l9 9a1.5 1.5 0 0 1 0 2l-6 6a1.5 1.5 0 0 1 -2 0l-9 -9v-4a3 3 0 0 1 3 -3h4"/><path d="M9 7l0 .01"/>',
  'dollar'    => '<path d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2"/><path d="M12 3v3m0 12v3"/>',
  'clock'     => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 7l0 5l3 3"/>',
  'bookmark'  => '<path d="M9 4h6a2 2 0 0 1 2 2v14l-5 -3l-5 3v-14a2 2 0 0 1 2 -2"/>',
  'ban'       => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M5.7 5.7l12.6 12.6"/>',
  'filetext'  => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 13l6 0"/><path d="M9 17l6 0"/>',
  'minus'     => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M9 12l6 0"/>',
  'cloudup'   => '<path d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1"/><path d="M9 15l3 -3l3 3"/><path d="M12 12l0 9"/>',
  'coin'      => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 0 0 4h-2a2 2 0 0 1 -1.8 -1"/><path d="M12 7v10"/>',
  'user'      => '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>',
  'userplus'  => '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M16 19h6"/><path d="M19 16v6"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4"/>',
  'contacts'  => '<path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/>',
  'warehouse' => '<path d="M3 21v-13l9 -4l9 4v13"/><path d="M13 13h4v8h-4z"/><path d="M3 21h18"/><path d="M6 12v.01"/><path d="M10 12v.01"/><path d="M6 16v.01"/><path d="M10 16v.01"/>',
  'table'     => '<path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z"/><path d="M3 10h18"/><path d="M10 3v18"/>',
  'printer'   => '<path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/><path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z"/>',
  'database'  => '<path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0"/><path d="M4 6v6a8 3 0 0 0 16 0v-6"/><path d="M4 12v6a8 3 0 0 0 16 0v-6"/>',
  'filecode'  => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M10 13l-1 2l1 2"/><path d="M14 13l1 2l-1 2"/>',
  'briefcase' => '<path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/><path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2"/><path d="M12 12l0 .01"/><path d="M3 13a20 20 0 0 0 18 0"/>',
  'calcheck'  => '<path d="M11.5 21h-5.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v6"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M4 11h16"/><path d="M15 19l2 2l4 -4"/>',
  'calendar'  => '<path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M4 11h16"/>',
  'cloud'     => '<path d="M6.657 18c-2.572 0 -4.657 -2.007 -4.657 -4.483c0 -2.475 2.085 -4.482 4.657 -4.482c.393 -1.762 1.794 -3.2 3.675 -3.773c1.88 -.572 3.956 -.193 5.444 1c1.488 1.19 2.162 3.007 1.77 4.769h.99c1.913 0 3.464 1.56 3.464 3.486c0 1.927 -1.551 3.487 -3.465 3.487h-11.878"/>',
  'percent'   => '<path d="M17 17m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M7 7m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M6 18l12 -12"/>',
  'filepdf'   => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4"/><path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6"/><path d="M17 18h2"/><path d="M20 15h-3v6"/><path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z"/>',
  'cartshop'  => '<path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/>',
  'creditcard'=> '<path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"/><path d="M3 10l18 0"/><path d="M7 15l.01 0"/><path d="M11 15l2 0"/>',
  'calculator'=> '<path d="M4 3m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"/><path d="M8 14l0 .01"/><path d="M12 14l0 .01"/><path d="M16 14l0 .01"/><path d="M8 17l0 .01"/><path d="M12 17l0 .01"/><path d="M16 17l0 .01"/>',
  'trophy'    => '<path d="M8 21l8 0"/><path d="M12 17l0 4"/><path d="M7 4l10 0"/><path d="M17 4v8a5 5 0 0 1 -10 0v-8"/><path d="M5 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M19 9m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>',
  'box'       => '<path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/>',
  'sort'      => '<path d="M3 9l4 -4l4 4m-4 -4v14"/><path d="M21 15l-4 4l-4 -4m4 4v-14"/>',
  'refresh'   => '<path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>',
  'circleuser'=> '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"/>',
  'receipt'   => '<path d="M5 21v-16a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/>',
];
?>
<aside class="app-sidebar nx-sidebar shadow" data-bs-theme="dark">

    <!-- ── Brand ── -->
    <div class="sidebar-brand">
        <a href="<?= site_url(); ?>" class="brand-link d-flex align-items-center gap-3 text-decoration-none flex-grow-1 min-w-0">
            <?php if ($store && !empty($store->image) && is_file(FCPATH . 'uploads/thumbs/' . $store->image)): ?>
                <img src="<?= base_url('uploads/thumbs/' . $store->image); ?>" class="nx-brand-icon" alt="">
            <?php else: ?>
                <span class="nx-brand-icon">
                    <?= mb_strtoupper(mb_substr($store ? $store->name : $Settings->site_name, 0, 1)) ?>
                </span>
            <?php endif; ?>
            <span class="d-flex flex-column min-w-0">
                <span class="brand-text"><?= $store ? html_escape($store->name) : html_escape($Settings->site_name); ?></span>
                <span class="nx-brand-sub">Neurix POS</span>
            </span>
        </a>
    </div>

    <!-- ── Scroll area: profile card + nav ── -->
    <div class="sidebar-wrapper">

        <!-- Profile Card -->
        <div class="nx-sidebar-userblock">
            <div class="nx-sb-avatar-wrap">
                <img src="<?= avatar_usuario($this->session->userdata('avatar'), $this->session->userdata('gender')); ?>"
                     class="nx-sb-avatar" alt="Avatar">
                <span class="nx-sb-status-dot" title="<?= lang('conectado'); ?>"></span>
            </div>
            <div class="nx-sb-info">
                <div class="nx-sb-name">
                    <span class="nx-sb-name-text"><?= html_escape($this->session->userdata('first_name') . ' ' . mb_substr($this->session->userdata('last_name'), 0, 10)); ?></span>
                    <a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>" class="nx-sb-dots" title="<?= lang('profile'); ?>">
                        <?= ti_svg($ti['dots'], 16); ?>
                    </a>
                </div>
                <div class="nx-sb-email"><?= html_escape($this->session->userdata('email')); ?></div>
                <span class="nx-sb-conectado"><?= lang('conectado'); ?></span>
            </div>
        </div>

        <!-- ── Navigation ── -->
        <nav aria-label="Navegación principal" class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="mainSidebarMenu">

                <!-- Dashboard -->
                <li class="nav-item mm_welcome">
                    <a href="<?= site_url(); ?>" class="nav-link">
                        <span class="nx-sico nx-ico-blue"><?= ti_svg($ti['home'], 18); ?></span>
                        <p><?= lang('dashboard'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                </li>

                <?php if ($Settings->multi_store && !$this->session->userdata('store_id')): ?>
                <li class="nav-item mm_stores">
                    <a href="<?= site_url('stores'); ?>" class="nav-link">
                        <span class="nx-sico nx-ico-sky"><?= ti_svg($ti['store'], 18); ?></span>
                        <p><?= lang('stores'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                </li>
                <?php endif; ?>

                <!-- POS -->
                <li class="nav-item mm_pos">
                    <a href="<?= site_url('pos'); ?>" class="nav-link">
                        <span class="nx-sico nx-ico-violet-pos"><?= ti_svg($ti['cart'], 18); ?></span>
                        <p><?= lang('pos'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                </li>

                <?php if ($Admin): ?>
                <li class="nav-header"><?= lang('nav_gestion'); ?></li>

                <!-- Productos -->
                <li class="nav-item has-treeview mm_products">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-orange"><?= ti_svg($ti['package'], 18); ?></span>
                        <p><?= lang('products'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="products_index"><a href="<?= site_url('products'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['list'], 14); ?></span><p><?= lang('list_products'); ?></p></a></li>
                        <li class="nav-item" id="products_add"><a href="<?= site_url('products/add'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('add_product'); ?></p></a></li>
                        <li class="nav-item" id="products_inventario"><a href="<?= site_url('products/inventario'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['scale'], 14); ?></span><p><?= lang('ajuste_inventario'); ?></p></a></li>
                        <li class="nav-item" id="products_import"><a href="<?= site_url('products/import'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['upload'], 14); ?></span><p><?= lang('import_products'); ?></p></a></li>
                        <li class="nav-item" id="products_etiquetas"><a href="<?= site_url('products/etiquetas'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['barcode'], 14); ?></span><p><?= lang('etiquetas_codigos'); ?></p></a></li>
                        <?php if ($this->Settings->multiprice_enabled == 1): ?>
                        <li class="nav-item" id="products_prices"><a href="<?= site_url('products/listprices'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['dollar'], 14); ?></span><p><?= lang('lista_precios'); ?></p></a></li>
                        <li class="nav-item" id="products_addprices"><a href="<?= site_url('products/addprices'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('agregar_precios'); ?></p></a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Categorías -->
                <li class="nav-item has-treeview mm_categories">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-teal"><?= ti_svg($ti['tag'], 18); ?></span>
                        <p><?= lang('categories'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="categories_index"><a href="<?= site_url('categories'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['list'], 14); ?></span><p><?= lang('list_categories'); ?></p></a></li>
                        <li class="nav-item" id="categories_add"><a href="<?= site_url('categories/add'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('add_category'); ?></p></a></li>
                        <li class="nav-item" id="categories_import"><a href="<?= site_url('categories/import'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['upload'], 14); ?></span><p><?= lang('import_categories'); ?></p></a></li>
                    </ul>
                </li>

                <?php if ($this->session->userdata('store_id')): ?>
                <li class="nav-header"><?= lang('nav_comercial'); ?></li>

                <!-- Ventas -->
                <li class="nav-item has-treeview mm_sales mm_creditnotes mm_debitnotes">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-green"><?= ti_svg($ti['trending'], 18); ?></span>
                        <p><?= lang('sales'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="sales_index"><a href="<?= site_url('sales'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['receipt'], 14); ?></span><p><?= lang('list_sales'); ?></p></a></li>
                        <li class="nav-item" id="sales_opened"><a href="<?= site_url('sales/opened'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['clock'], 14); ?></span><p><?= lang('list_opened_bills'); ?></p></a></li>
                        <?php if ($Settings->enable_layaway): ?>
                        <li class="nav-item" id="sales_apartado"><a href="<?= site_url('sales/apartado'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['bookmark'], 14); ?></span><p><?= lang('list_apartado_sales'); ?></p></a></li>
                        <?php endif; ?>
                        <?php if ($Settings->enable_quote): ?>
                        <li class="nav-item" id="sales_proforma"><a href="<?= site_url('sales/proforma'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['filetext'], 14); ?></span><p><?= lang('list_quotes_sales'); ?></p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="sales_anuladas"><a href="<?= site_url('sales/anuladas'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['ban'], 14); ?></span><p><?= lang('sales_anuladas'); ?></p></a></li>
                        <li class="nav-item" id="creditnotes_index"><a href="<?= site_url('CreditNotes'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['minus'], 14); ?></span><p><?= lang('credit_notes'); ?></p></a></li>
                        <li class="nav-item" id="debitnotes_index"><a href="<?= site_url('debitnotes'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('notas_debito'); ?></p></a></li>
                    </ul>
                </li>

                <!-- Compras -->
                <li class="nav-item has-treeview mm_purchases">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-amber"><?= ti_svg($ti['truck'], 18); ?></span>
                        <p><?= lang('purchases'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="purchases_index"><a href="<?= site_url('purchases'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['list'], 14); ?></span><p><?= lang('list_purchases'); ?></p></a></li>
                        <li class="nav-item" id="purchases_add"><a href="<?= site_url('purchases/add'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('add_purchase'); ?></p></a></li>
                        <?php if ($Settings->fe == "1"): ?>
                        <li class="nav-item" id="document_upload"><a href="<?= site_url('cargadocumentos'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['cloudup'], 14); ?></span><p><?= lang('documents_upload'); ?></p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="purchases_expenses"><a href="<?= site_url('purchases/expenses'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['coin'], 14); ?></span><p><?= lang('list_expenses'); ?></p></a></li>
                        <li class="nav-item" id="purchases_add_expense"><a href="<?= site_url('purchases/add_expense'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('add_expense'); ?></p></a></li>
                        <li class="nav-item" id="purchases_fec"><a href="<?= site_url('facturascompras/'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['filetext'], 14); ?></span><p><?= lang('list_fec'); ?></p></a></li>
                        <li class="nav-item" id="purchases_add_fec"><a href="<?= site_url('facturascompras/create_fec'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('add_fec'); ?></p></a></li>
                    </ul>
                </li>

                <?php endif; /* store_id */ ?>

                <li class="nav-header"><?= lang('nav_contactos'); ?></li>

                <!-- Personas -->
                <li class="nav-item has-treeview mm_auth mm_customers mm_suppliers">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-violet"><?= ti_svg($ti['users'], 18); ?></span>
                        <p><?= lang('people'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="auth_users"><a href="<?= site_url('users'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['user'], 14); ?></span><p><?= lang('list_users'); ?></p></a></li>
                        <li class="nav-item" id="auth_add"><a href="<?= site_url('users/add'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['userplus'], 14); ?></span><p><?= lang('add_user'); ?></p></a></li>
                        <li class="nav-item" id="customers_index"><a href="<?= site_url('customers'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['contacts'], 14); ?></span><p><?= lang('list_customers'); ?></p></a></li>
                        <li class="nav-item" id="customers_add"><a href="<?= site_url('customers/add'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['userplus'], 14); ?></span><p><?= lang('add_customer'); ?></p></a></li>
                        <li class="nav-item" id="suppliers_index"><a href="<?= site_url('suppliers'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['warehouse'], 14); ?></span><p><?= lang('list_suppliers'); ?></p></a></li>
                        <li class="nav-item" id="suppliers_add"><a href="<?= site_url('suppliers/add'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('add_supplier'); ?></p></a></li>
                    </ul>
                </li>

                <li class="nav-header"><?= lang('nav_sistema'); ?></li>

                <!-- Configuración -->
                <li class="nav-item has-treeview mm_settings">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-slate"><?= ti_svg($ti['settings'], 18); ?></span>
                        <p><?= lang('settings'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="settings_index"><a href="<?= site_url('settings'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['settings'], 14); ?></span><p><?= lang('settings'); ?></p></a></li>
                        <?php if ($Settings->is_shipping == 1): ?>
                        <li class="nav-item" id="settings_shipping"><a href="<?= site_url('settings/shipping'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['truck'], 14); ?></span><p><?= lang('shipping_method'); ?></p></a></li>
                        <li class="nav-item" id="settings_shipping_add"><a href="<?= site_url('settings/add_shipping'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('add_shipping'); ?></p></a></li>
                        <?php endif; ?>
                        <?php if ($Settings->propina_enable == '1'): ?>
                        <li class="nav-item" id="waiting_tables"><a href="<?= site_url('settings/waiting_tables'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['table'], 14); ?></span><p><?= lang('lista_mesas'); ?></p></a></li>
                        <li class="nav-item" id="settings_add_table"><a href="<?= site_url('settings/add_table'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('agregar_mesa'); ?></p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="settings_stores"><a href="<?= site_url('settings/stores'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['store'], 14); ?></span><p><?= lang('stores'); ?></p></a></li>
                        <?php if ($Settings->multi_store): ?>
                        <li class="nav-item" id="settings_add_store"><a href="<?= site_url('settings/add_store'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['plus'], 14); ?></span><p><?= lang('add_store'); ?></p></a></li>
                        <?php endif; ?>
                        <?php if ($this->db->dbdriver != 'sqlite3'): ?>
                        <li class="nav-item" id="settings_backups"><a href="<?= site_url('settings/backups'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['database'], 14); ?></span><p><?= lang('backups'); ?></p></a></li>
                        <li class="nav-item" id="settings_backups_xml"><a href="<?= site_url('settings/backups_xml'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['filecode'], 14); ?></span><p><?= lang('backup_xmls'); ?></p></a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Informes -->
                <li class="nav-item has-treeview mm_reports">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-rose"><?= ti_svg($ti['chartbar'], 18); ?></span>
                        <p><?= lang('reports'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="reportes_inteligencia"><a href="<?= site_url('reportes'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['trending'], 14); ?></span><p>Centro de Inteligencia</p></a></li>
                        <li class="nav-item" id="reportes_auditoria"><a href="<?= site_url('reportes/auditoria'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['scale'], 14); ?></span><p>Auditoría Integral</p></a></li>
                        <li class="nav-item" id="reportes_anomalias"><a href="<?= site_url('reportes/ver/anomalias'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['ban'], 14); ?></span><p>Detector de anomalías</p></a></li>
                        <li class="nav-item" id="reportes_conciliacion"><a href="<?= site_url('reportes/ver/conciliacion'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['cloud'], 14); ?></span><p>Conciliación con Hacienda</p></a></li>
                        <li class="nav-item" id="reportes_diccionario"><a href="<?= site_url('reportes/diccionario'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['database'], 14); ?></span><p>Diccionario de datos</p></a></li>
                        <li class="nav-item" id="reportes_bitacora"><a href="<?= site_url('reportes/bitacora'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['clock'], 14); ?></span><p>Bitácora de informes</p></a></li>
                        <li class="nav-header" style="padding:8px 16px 4px;font-size:10px;text-transform:uppercase;letter-spacing:.08em;opacity:.55">Informes clásicos</li>
                        <li class="nav-item" id="reports_credit_customers"><a href="<?= site_url('reports/credit_customers'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['circleuser'], 14); ?></span><p><?= lang('cta_clientes'); ?></p></a></li>
                        <?php if ($Settings->is_shipping == 1): ?>
                        <li class="nav-item" id="reports_credit_shipping"><a href="<?= site_url('reports/credit_shipping'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['truck'], 14); ?></span><p><?= lang('cta_envios'); ?></p></a></li>
                        <?php endif; ?>
                        <li class="nav-item" id="reports_daily_sales"><a href="<?= site_url('reports/daily_sales'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['calcheck'], 14); ?></span><p><?= lang('daily_sales'); ?></p></a></li>
                        <li class="nav-item" id="reports_monthly_sales"><a href="<?= site_url('reports/monthly_sales'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['calendar'], 14); ?></span><p><?= lang('monthly_sales'); ?></p></a></li>
                        <li class="nav-item" id="reports_monthly_fec"><a href="<?= site_url('reports/monthly_fec'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['cloud'], 14); ?></span><p><?= lang('monthly_fec'); ?></p></a></li>
                        <li class="nav-item" id="reports_monthly_sale_tax"><a href="<?= site_url('reports/monthly_sale_tax'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['percent'], 14); ?></span><p><?= lang('monthly_sale_tax'); ?></p></a></li>
                        <li class="nav-item" id="sale_fe"><a href="<?= site_url('reports/sale_fe'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['filepdf'], 14); ?></span><p><?= lang('model_d104'); ?></p></a></li>
                        <li class="nav-item" id="d151"><a href="<?= site_url('reports/d151'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['filepdf'], 14); ?></span><p><?= lang('model_d151'); ?></p></a></li>
                        <li class="nav-item" id="reports_compras_electronicas"><a href="<?= site_url('reports/compras_electronicas'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['cartshop'], 14); ?></span><p><?= lang('compras_mensuales'); ?></p></a></li>
                        <li class="nav-item" id="reports_payments"><a href="<?= site_url('reports/payments'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['creditcard'], 14); ?></span><p><?= lang('payments_report'); ?></p></a></li>
                        <li class="nav-item" id="reports_sinpe"><a href="<?= site_url('reports/sinpe'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['receipt'], 14); ?></span><p><?= lang('sinpe_report'); ?></p></a></li>
                        <li class="nav-item" id="reports_registers"><a href="<?= site_url('reports/registers'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['calculator'], 14); ?></span><p><?= lang('registers_report'); ?></p></a></li>
                        <li class="nav-item" id="reports_top_products"><a href="<?= site_url('reports/top_products'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['trophy'], 14); ?></span><p><?= lang('top_products'); ?></p></a></li>
                        <li class="nav-item" id="reports_products"><a href="<?= site_url('reports/products'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['box'], 14); ?></span><p><?= lang('products_report'); ?></p></a></li>
                        <li class="nav-item" id="reports_products_qty"><a href="<?= site_url('reports/products_quantity'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['sort'], 14); ?></span><p><?= lang('products_quantity'); ?></p></a></li>
                        <li class="nav-item" id="inventory_adjustment"><a href="<?= site_url('reports/inventory_adjustment'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['refresh'], 14); ?></span><p><?= lang('inventory_adjustment'); ?></p></a></li>
                    </ul>
                </li>

                <?php else: /* no Admin */ ?>

                <li class="nav-header"><?= lang('mis_ventas'); ?></li>

                <li class="nav-item has-treeview mm_customers">
                    <a href="#" class="nav-link">
                        <span class="nx-sico nx-ico-violet"><?= ti_svg($ti['users'], 18); ?></span>
                        <p><?= lang('customers'); ?> <span class="nav-arrow"><?= ti_svg($ti['chevron'], 14); ?></span></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="customers_index"><a href="<?= site_url('customers'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['contacts'], 14); ?></span><p><?= lang('list_customers'); ?></p></a></li>
                        <li class="nav-item" id="customers_add"><a href="<?= site_url('customers/add'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['userplus'], 14); ?></span><p><?= lang('add_customer'); ?></p></a></li>
                        <?php if ($Settings->fe == "1"): ?>
                        <li class="nav-item" id="document_upload"><a href="<?= site_url('cargadocumentos'); ?>" class="nav-link"><span class="nav-icon"><?= ti_svg($ti['cloudup'], 14); ?></span><p><?= lang('documents_upload'); ?></p></a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <?php endif; ?>

            </ul>
        </nav>

    </div><!-- /sidebar-wrapper -->

    <!-- ── Dark mode footer ── -->
    <div class="nx-sidebar-footer">
        <div class="nx-sidebar-darkmode">
            <div class="nx-dm-moon"><?= ti_svg($ti['moon'], 19); ?></div>
            <div class="nx-dm-text">
                <div class="t1"><?= lang('modo_oscuro'); ?></div>
                <div class="t2" id="nxDmLabel"><?= lang('activado'); ?></div>
            </div>
            <button class="nx-dm-switch" id="nxDmSwitch" onclick="switchTheme()" title="<?= lang('cambiar_tema'); ?>"></button>
        </div>
    </div>

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
                                <a href="<?= site_url(); ?>" class="d-inline-flex align-items-center gap-1"><?= ti_svg($ti['home'], 13) ?> <?= lang('home'); ?></a>
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

            <!-- Unico punto donde se emiten los avisos de sesion. Las vistas no repiten
                 el mensaje: verlo dos veces era el sintoma de tenerlo en los dos lados. -->
            <?php if ($error || $warning || $message): ?>
            <script>
            window._nxAlerts = window._nxAlerts || [];
            <?php
            // validation_errors() llega como varios <p>: se pasa a saltos de linea para
            // que un error de tres campos no salga como un parrafo pegado.
            $nx_flash = function ($texto) {
                return json_encode(nl2br(html_escape(trim(preg_replace('/\n{2,}/', "\n", strip_tags($texto))))));
            };
            ?>
            <?php if ($error): ?>window._nxAlerts.push({icon:'error',html:<?= $nx_flash($error) ?>});<?php endif; ?>
            <?php if ($warning): ?>window._nxAlerts.push({icon:'warning',html:<?= $nx_flash($warning) ?>});<?php endif; ?>
            <?php if ($message): ?>window._nxAlerts.push({icon:'success',html:<?= $nx_flash($message) ?>});<?php endif; ?>
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
