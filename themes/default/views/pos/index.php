<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="<?= $this->config->item('language'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title . ' | ' . $Settings->site_name; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png" />
    <link rel="apple-touch-icon" href="<?= $assets ?>images/icon-192.png">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <meta name="theme-color" content="#3b82f6">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Neurix POS">
    <!-- Cache-busting: la URL cambia con cada build para que ni el service worker
         ni el caché HTTP sirvan bundles viejos -->
    <link href="<?= $assets ?>dist/css/www.min.css?v=<?= @filemtime(FCPATH.'themes/default/assets/dist/css/www.min.css') ?: '1'; ?>" rel="stylesheet" />
    <link href="<?= $assets ?>dist/css/nx-sidebar.css?v=<?= @filemtime(FCPATH.'themes/default/assets/dist/css/nx-sidebar.css') ?: '1'; ?>" rel="stylesheet" />
    <?= $Settings->rtl ? '<link href="' . $assets . 'dist/css/rtl.css" rel="stylesheet" />' : ''; ?>
    <!-- Anti-FOUC: aplicar tema antes de pintar -->
    <script>
        (function(){
            var t = localStorage.getItem('nx-theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    <script src="<?= $assets ?>dist/js/main.min.js?v=<?= @filemtime(FCPATH.'themes/default/assets/dist/js/main.min.js') ?: '1'; ?>"></script>
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
    <?php
    function pos_ti($paths, $size = 18) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/>'.$paths.'</svg>';
    }
    $pos_ti = [
      'home'    => '<path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/>',
      'cart'    => '<path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/>',
      'receipt' => '<path d="M5 21v-16a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/>',
      'users'   => '<path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>',
      'package' => '<path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12l0 9"/><path d="M12 12l-8 -4.5"/><path d="M16 5.25l-8 4.5"/>',
      'truck'   => '<path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M5 17h-2v-11a1 1 0 0 1 1 -1h9v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5"/>',
      'chart'   => '<path d="M3 13a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M15 9a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M9 5a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M4 20h14"/>',
      'settings'=> '<path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/>',
      'dbarrows'     => '<path d="M11 7l-5 5l5 5"/><path d="M17 7l-5 5l5 5"/>',
      'dots'         => '<path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>',
      'moon'         => '<path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/>',
      'sun'          => '<path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/>',
      'panelleftclose'=> '<path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M9 4v16"/><path d="M14 10l-2 2l2 2"/>',
      'panelleftopen' => '<path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M9 4v16"/><path d="M14 10l2 2l-2 2"/>',
      'store'        => '<path d="M3 21l18 0"/><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/><path d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/>',
      'circlecheck'  => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M9 12l2 2l4 -4"/>',
      'bell'         => '<path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/>',
      'pausecircle'  => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M10 9v6"/><path d="M14 9v6"/>',
      'globe'        => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M3.6 9h16.8"/><path d="M3.6 15h16.8"/><path d="M11.5 3a17 17 0 0 0 0 18"/><path d="M12.5 3a17 17 0 0 1 0 18"/>',
      'bolt'         => '<path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"/>',
      'keyboard'     => '<path d="M2 6m0 2a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2z"/><path d="M6 10l0 .01"/><path d="M10 10l0 .01"/><path d="M14 10l0 .01"/><path d="M18 10l0 .01"/><path d="M6 14l0 .01"/><path d="M18 14l0 .01"/><path d="M10 14l4 0"/>',
      'calculator'   => '<path d="M4 3m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"/><path d="M8 14l0 .01"/><path d="M12 14l0 .01"/><path d="M16 14l0 .01"/><path d="M8 17l0 .01"/><path d="M12 17l0 .01"/><path d="M16 17l0 .01"/>',
      'fileinvoice'  => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 14l2 0"/><path d="M9 10l6 0"/><path d="M9 17l6 0"/>',
      'search'       => '<path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/>',
      'chevrondown'  => '<path d="M6 9l6 6l6 -6"/>',
      'circleuser'   => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"/>',
      'user'         => '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>',
      'logout'       => '<path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M9 12h12l-3 -3"/><path d="M18 15l3 -3"/>',
      'grid2'        => '<path d="M3 3h8v8h-8z"/><path d="M13 3h8v8h-8z"/><path d="M3 13h8v8h-8z"/><path d="M13 13h8v8h-8z"/>',
      'tag'          => '<path d="M7.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M3 6v5.172a2 2 0 0 0 .586 1.414l7.71 7.71a2.41 2.41 0 0 0 3.408 0l5.592 -5.592a2.41 2.41 0 0 0 0 -3.408l-7.71 -7.71a2 2 0 0 0 -1.414 -.586h-5.172a3 3 0 0 0 -3 3z"/>',
      'magnifier'    => '<path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/>',
      'printer'      => '<path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/><path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z"/>',
      'chevronleft'  => '<path d="M15 6l-6 6l6 6"/>',
      'chevronright' => '<path d="M9 6l6 6l-6 6"/>',
      'inbox'        => '<path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"/><path d="M4 13h3l3 3h4l3 -3h3"/>',
      'userplus'     => '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M16 19h6"/><path d="M19 16v6"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 2.5 1"/>',
      'infocircle'   => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 8l.01 0"/><path d="M11 12l1 0l0 4l1 0"/>',
      'x'            => '<path d="M18 6l-12 12"/><path d="M6 6l12 12"/>',
      'message'      => '<path d="M8 9h8"/><path d="M8 13h6"/><path d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z"/>',
      'percent'      => '<path d="M17 17m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M7 7m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M6 18l12 -12"/>',
      'hash'         => '<path d="M5 9l14 0"/><path d="M5 15l14 0"/><path d="M11 4l-4 16"/><path d="M17 4l-4 16"/>',
      'list'         => '<path d="M9 6l11 0"/><path d="M9 12l11 0"/><path d="M9 18l11 0"/><path d="M5 6l0 .01"/><path d="M5 12l0 .01"/><path d="M5 18l0 .01"/>',
      'pause'        => '<path d="M6 5m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"/><path d="M14 5m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z"/>',
      'plus'         => '<path d="M12 5l0 14"/><path d="M5 12l14 0"/>',
      'check'        => '<path d="M5 12l5 5l10 -10"/>',
      'cash'         => '<path d="M7 9m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"/><path d="M14 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 9v-2a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v6a2 2 0 0 0 2 2h2"/>',
      'creditcard'   => '<path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"/><path d="M3 10l18 0"/><path d="M7 15l.01 0"/><path d="M11 15l2 0"/>',
      'phoneall'     => '<path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/>',
      'bank'         => '<path d="M3 21l18 0"/><path d="M3 10l18 0"/><path d="M5 6l7 -3l7 3"/><path d="M4 10l0 11"/><path d="M20 10l0 11"/><path d="M8 14l0 3"/><path d="M12 14l0 3"/><path d="M16 14l0 3"/>',
      'equal'        => '<path d="M5 9h14"/><path d="M5 15h14"/>',
      'wand'         => '<path d="M6 21l15 -15l-3 -3l-15 15l3 3"/><path d="M15 6l3 3"/>',
    ];
    ?>
    <aside class="pos-nav" id="posNav">

        <!-- ── Brand ── -->
        <div class="pos-nav-brand">
            <div class="brand-logo">
                <img src="<?= $assets ?>images/icon.png" alt="Neurix POS" style="width:42px;height:42px;object-fit:cover;border-radius:11px;">
            </div>
            <div class="brand-text">
                <div class="brand-name"><?= html_escape($store ? $store->name : $Settings->site_name) ?></div>
                <div class="brand-sub">Neurix POS</div>
            </div>
        </div>

        <!-- ── Scroll area: profile card + nav ── -->
        <div class="pos-nav-scroller">

            <!-- Profile Card -->
            <div class="nx-sidebar-userblock">
                <div class="nx-sb-avatar-wrap">
                    <img src="<?= base_url('uploads/avatars/' . ($this->session->userdata('avatar') ?: $this->session->userdata('gender') . '.png')); ?>"
                         class="nx-sb-avatar" alt="Avatar">
                    <span class="nx-sb-status-dot" title="<?= lang('conectado'); ?>"></span>
                </div>
                <div class="nx-sb-info">
                    <div class="nx-sb-name">
                        <span class="nx-sb-name-text"><?= html_escape($this->session->userdata('first_name') . ' ' . mb_substr($this->session->userdata('last_name'), 0, 10)); ?></span>
                        <a href="<?= site_url('users/profile/' . $this->session->userdata('user_id')); ?>" class="nx-sb-dots" title="<?= lang('profile'); ?>">
                            <?= pos_ti($pos_ti['dots'], 16) ?>
                        </a>
                    </div>
                    <div class="nx-sb-email"><?= html_escape($this->session->userdata('email')); ?></div>
                    <span class="nx-sb-conectado"><?= lang('conectado'); ?></span>
                </div>
            </div>

            <!-- Nav links -->
            <nav class="pos-nav-links">

                <div class="pos-nav-group-label"><?= lang('home'); ?></div>

                <a href="<?= site_url('welcome') ?>" class="pos-nav-link" title="<?= lang('dashboard'); ?>">
                    <span class="nx-sico nx-ico-blue"><?= pos_ti($pos_ti['home'], 18) ?></span>
                    <span class="nav-label"><?= lang('dashboard'); ?></span>
                </a>
                <a href="<?= site_url('pos') ?>" class="pos-nav-link active" title="<?= lang('nueva_venta'); ?>">
                    <span class="nx-sico nx-ico-violet-pos"><?= pos_ti($pos_ti['cart'], 18) ?></span>
                    <span class="nav-label"><?= lang('nueva_venta'); ?></span>
                </a>
                <a href="<?= site_url('sales/opened') ?>" class="pos-nav-link" title="<?= lang('ventas_abiertas'); ?>">
                    <span class="nx-sico nx-ico-teal"><?= pos_ti($pos_ti['receipt'], 18) ?></span>
                    <span class="nav-label"><?= lang('ventas_abiertas'); ?></span>
                </a>

                <div class="pos-nav-group-label"><?= lang('catalogo'); ?></div>

                <a href="<?= site_url('customers') ?>" class="pos-nav-link" title="<?= lang('customers'); ?>">
                    <span class="nx-sico nx-ico-violet"><?= pos_ti($pos_ti['users'], 18) ?></span>
                    <span class="nav-label"><?= lang('customers'); ?></span>
                </a>
                <a href="<?= site_url('products') ?>" class="pos-nav-link" title="<?= lang('products'); ?>">
                    <span class="nx-sico nx-ico-orange"><?= pos_ti($pos_ti['package'], 18) ?></span>
                    <span class="nav-label"><?= lang('products'); ?></span>
                </a>
                <a href="<?= site_url('purchases') ?>" class="pos-nav-link" title="<?= lang('purchases'); ?>">
                    <span class="nx-sico nx-ico-amber"><?= pos_ti($pos_ti['truck'], 18) ?></span>
                    <span class="nav-label"><?= lang('purchases'); ?></span>
                </a>

                <div class="pos-nav-group-label"><?= lang('analitica'); ?></div>

                <a href="<?= site_url('reports') ?>" class="pos-nav-link" title="<?= lang('reports'); ?>">
                    <span class="nx-sico nx-ico-rose"><?= pos_ti($pos_ti['chart'], 18) ?></span>
                    <span class="nav-label"><?= lang('reports'); ?></span>
                </a>

                <?php if ($Admin): ?>
                <div class="pos-nav-group-label"><?= lang('nav_sistema'); ?></div>
                <a href="<?= site_url('settings') ?>" class="pos-nav-link" title="<?= lang('settings'); ?>">
                    <span class="nx-sico nx-ico-slate"><?= pos_ti($pos_ti['settings'], 18) ?></span>
                    <span class="nav-label"><?= lang('settings'); ?></span>
                </a>
                <?php endif; ?>

            </nav>
        </div>

        <!-- ── Dark mode footer ── -->
        <div class="pos-nav-footer">
            <div class="nx-sidebar-darkmode">
                <div class="nx-dm-moon"><?= pos_ti($pos_ti['moon'], 18) ?></div>
                <div class="nx-dm-text" id="pos-dm-text">
                    <div class="t1"><?= lang('modo_oscuro'); ?></div>
                    <div class="t2" id="nxDmLabel"><?= lang('activado'); ?></div>
                </div>
                <button class="nx-dm-switch" id="pos-dm-switch" onclick="switchTheme()" title="<?= lang('cambiar_tema'); ?>"></button>
            </div>
        </div>

    </aside>

    <!-- ── MAIN ─────────────────────────────────── -->
    <div class="pos-main">

        <!-- ── TOPBAR ──────────────────────────── -->
        <header class="pos-topbar">

            <!-- Panel toggle (colapsa el sidebar) -->
            <button class="pos-topbar-btn pos-panel-btn" id="navToggle" title="<?= lang('expandir_menu'); ?>">
                <span class="pos-panel-close" id="navToggleIcon"><?= pos_ti($pos_ti['panelleftclose'], 18) ?></span>
                <span class="pos-panel-open"><?= pos_ti($pos_ti['panelleftopen'], 18) ?></span>
            </button>

            <div class="pos-topbar-sep"></div>

            <!-- Store info -->
            <div class="pos-topbar-store">
                <div class="store-name d-flex align-items-center gap-1">
                    <?= pos_ti($pos_ti['store'], 13) ?>
                    <?= html_escape($store ? $store->name : $Settings->site_name) ?>
                </div>
                <div class="store-sub">
                    <?php if ($this->session->userdata('register_id')): ?>
                        <?= lang('caja'); ?> #<?= $this->session->userdata('register_id') ?>
                        &nbsp;·&nbsp; <?= lang('sucursal'); ?> <?= $this->session->userdata('store_id') ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pos-topbar-sep"></div>

            <!-- Hacienda status chip -->
            <div class="pos-topbar-chip ok d-flex align-items-center gap-1" title="<?= lang('conexion_hacienda'); ?>">
                <?= pos_ti($pos_ti['circlecheck'], 14) ?>
                <span><?= lang('hacienda'); ?></span>
            </div>

            <div class="pos-topbar-spacer"></div>

            <!-- Ventas suspendidas -->
            <?php if ($suspended_sales && count($suspended_sales) > 0): ?>
            <div class="dropdown">
                <button class="pos-topbar-btn position-relative" data-bs-toggle="dropdown" title="<?= lang('ventas_suspendidas'); ?>">
                    <?= pos_ti($pos_ti['bell'], 17) ?>
                    <span class="pos-topbar-badge"><?= count($suspended_sales) ?></span>
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
                                <span style="color:#f59e0b;flex-shrink:0;"><?= pos_ti($pos_ti['pausecircle'], 14) ?></span>
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

            <!-- Idioma -->
            <div class="dropdown">
                <button class="pos-topbar-btn" data-bs-toggle="dropdown" title="<?= lang('language'); ?>">
                    <?= pos_ti($pos_ti['globe'], 17) ?>
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

            <!-- Producto rápido -->
            <button class="pos-topbar-btn" id="adHocBtn" title="<?= lang('producto_rapido'); ?>" data-bs-toggle="modal" data-bs-target="#adHocModal">
                <?= pos_ti($pos_ti['bolt'], 17) ?>
            </button>

            <!-- Atajos de teclado -->
            <button class="pos-topbar-btn" id="kbdShortcutsBtn" title="<?= lang('atajos_teclado'); ?>">
                <?= pos_ti($pos_ti['keyboard'], 17) ?>
            </button>

            <!-- Impresión -->
            <button class="pos-topbar-btn" id="printToggleBtn" title="<?= lang('impresion_auto_off'); ?>">
                <?= pos_ti($pos_ti['printer'], 17) ?>
            </button>

            <!-- Apertura de caja -->
            <a href="<?= site_url('pos/open_register') ?>" class="pos-topbar-btn" title="<?= lang('apertura_caja'); ?>">
                <?= pos_ti($pos_ti['calculator'], 17) ?>
            </a>

            <!-- Configurar impresora de esta computadora (QZ Tray) -->
            <button class="pos-topbar-btn" id="printerConfigBtn" title="<?= lang('configurar_impresora'); ?>" data-bs-toggle="modal" data-bs-target="#printerConfigModal">
                <?= pos_ti($pos_ti['settings'], 17) ?>
            </button>

            <!-- Abrir cajón (requiere PIN de administrador) -->
            <button class="pos-topbar-btn" id="drawerBtn" title="<?= lang('abrir_cajon'); ?>" data-bs-toggle="modal" data-bs-target="#drawerPinModal">
                <?= pos_ti($pos_ti['cash'], 17) ?>
            </button>

            <!-- Historial ventas -->
            <a href="<?= site_url('sales') ?>" class="pos-topbar-btn" title="<?= lang('sales') ?>">
                <?= pos_ti($pos_ti['receipt'], 17) ?>
            </a>

            <!-- Proformas -->
            <a href="<?= site_url('sales/proforma') ?>" class="pos-topbar-btn" title="<?= lang('proformas'); ?>">
                <?= pos_ti($pos_ti['fileinvoice'], 17) ?>
            </a>

            <!-- Tema -->
            <button class="pos-topbar-btn" onclick="switchTheme()" id="posThemeBtn" title="<?= lang('cambiar_tema'); ?>">
                <span class="pos-theme-sun"><?= pos_ti($pos_ti['sun'], 17) ?></span>
                <span class="pos-theme-moon"><?= pos_ti($pos_ti['moon'], 17) ?></span>
            </button>

            <?php if ($Admin): ?>
            <a href="<?= site_url('settings') ?>" class="pos-topbar-btn" title="<?= lang('settings') ?>">
                <?= pos_ti($pos_ti['settings'], 17) ?>
            </a>
            <?php endif; ?>

            <!-- Reloj -->
            <div class="pos-topbar-clock pos-clock"></div>

            <!-- Usuario -->
            <div class="dropdown">
                <button class="pos-user-btn" data-bs-toggle="dropdown">
                    <img src="<?= base_url('uploads/avatars/thumbs/' . ($this->session->userdata('avatar') ?: $this->session->userdata('gender') . '.png')) ?>"
                         class="pos-user-avatar-img" alt="Avatar">
                    <span><?= html_escape($this->session->userdata('first_name')) ?></span>
                    <span style="opacity:.5;display:flex;align-items:center;"><?= pos_ti($pos_ti['chevrondown'], 12) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('users/profile/'.$this->session->userdata('user_id')) ?>">
                            <?= pos_ti($pos_ti['circleuser'], 14) ?><?= lang('profile') ?>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?= site_url('logout') ?>">
                            <?= pos_ti($pos_ti['logout'], 14) ?><?= lang('sign_out') ?>
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
                        <span class="search-icon"><?= pos_ti($pos_ti['magnifier'], 15) ?></span>
                        <input type="text" id="add_item"
                               placeholder="<?= lang('search__scan') ?>"
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
                        <?= pos_ti($pos_ti['grid2'], 13) ?>
                        <?= lang('all') ?>
                    </button>

                    <?php if ($categories): foreach ($categories as $cat): ?>
                    <button class="pos-cat-btn category"
                            id="<?= (int)$cat->id ?>"
                            data-id="<?= (int)$cat->id ?>">
                        <?= pos_ti($pos_ti['tag'], 13) ?>
                        <?= html_escape($cat->name) ?>
                    </button>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Controls bar -->
                <div class="pos-controls-bar">
                    <input type="text" id="filter-categories"
                           placeholder="<?= lang('filter_categories') ?>"
                           autocomplete="off">

                    <div style="flex:1;"></div>

                    <button class="pos-pg-btn" id="previous" title="<?= lang('anterior'); ?>" disabled>
                        <?= pos_ti($pos_ti['chevronleft'], 14) ?>
                    </button>
                    <button class="pos-pg-btn" id="next" title="<?= lang('siguiente'); ?>">
                        <?= pos_ti($pos_ti['chevronright'], 14) ?>
                    </button>
                    <span class="pos-pg-info" id="pgInfo"></span>
                </div>

                <!-- Product grid -->
                <div class="pos-product-grid" id="item-list">
                    <?php if (!$t_nc): ?>
                        <?= $products ?>
                    <?php else: ?>
                        <div class="pos-grid-empty">
                            <?= pos_ti($pos_ti['inbox'], 36) ?>
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
                        <?= pos_ti($pos_ti['circleuser'], 14) ?>
                        <?= lang('customer') ?>
                        <button type="button" class="pcp-add-cust-btn ms-auto"
                                data-bs-toggle="modal" data-bs-target="#customerModal"
                                title="<?= lang('add_customer') ?>">
                            <?= pos_ti($pos_ti['userplus'], 14) ?>
                        </button>
                    </div>

                    <!-- Hidden input para el submit -->
                    <input type="hidden" id="pos-customer-hidden" name="customer_id"
                           value="<?= (int)$Settings->default_customer ?>">

                    <!-- Barra de búsqueda (visible cuando no hay cliente) -->
                    <div class="pcp-cust-search-wrap" id="pos-cust-search-wrap">
                        <p class="pcp-cust-hint"><?= pos_ti($pos_ti['infocircle'], 12) ?> <?= lang('buscar_cliente_hint'); ?></p>
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
                                title="<?= lang('cambiar_cliente'); ?>" style="display:none">
                            <?= pos_ti($pos_ti['search'], 13) ?>
                        </button>
                        <div class="pcp-cust-avatar" id="pos-cust-avatar">C</div>
                        <div class="pcp-cust-info">
                            <div class="pcp-cust-name" id="pos-cust-name"><?= lang('cliente_contado'); ?></div>
                            <div class="pcp-cust-meta" id="pos-cust-doc"></div>
                            <div class="pcp-cust-contact" id="pos-cust-contact"></div>
                        </div>
                        <button type="button" class="pcp-cust-clear-btn" id="pos-cust-clear"
                                title="<?= lang('quitar_cliente'); ?>" style="display:none">
                            <?= pos_ti($pos_ti['x'], 13) ?>
                        </button>
                    </div>
                </div>

                <!-- Cart header -->
                <div class="pcp-cart-bar">
                    <div class="pcp-cart-title">
                        <?= pos_ti($pos_ti['cart'], 14) ?>
                        <?= lang('sale_details') ?>
                    </div>
                    <span class="pcp-cart-badge" id="count">0</span>

                    <!-- Extra buttons -->
                    <div style="display:flex;gap:.3rem;margin-left:.5rem;">
                        <?php if (!$t_nc): ?>
                        <button type="button" class="pcp-cart-clear-btn" id="print_order"
                                title="<?= lang('order') ?>">
                            <?= pos_ti($pos_ti['printer'], 13) ?>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="pcp-cart-clear-btn"
                                data-bs-toggle="modal" data-bs-target="#ModalNotes"
                                title="<?= lang('notes') ?>">
                            <?= pos_ti($pos_ti['message'], 13) ?>
                        </button>
                        <?php if ($Settings->propina_enable == '1'): ?>
                        <button type="button" class="pcp-cart-clear-btn" id="add_tips">
                            <?= pos_ti($pos_ti['percent'], 13) ?>
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
                                <th style="text-align:right;"><?= lang('unit_price_abbr'); ?></th>
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
                        <span class="tl"><?= pos_ti($pos_ti['hash'], 12) ?> <?= lang('items') ?></span>
                        <span class="tv" id="count-items">0</span>
                    </div>
                    <div class="pcp-total-row">
                        <span class="tl"><?= pos_ti($pos_ti['list'], 12) ?> <?= lang('subtotal') ?></span>
                        <span class="tv" id="total">₡0.00</span>
                    </div>
                    <div class="pcp-total-row">
                        <a href="#" class="tl text-decoration-none" id="add_discount" style="color:var(--nx-txt4);">
                            <?= pos_ti($pos_ti['tag'], 12) ?> <?= lang('discount') ?>
                        </a>
                        <span class="tv" id="ds_con" style="color:var(--nx-warn);">₡0.00</span>
                    </div>
                    <div class="pcp-total-row">
                        <span class="tl"><?= pos_ti($pos_ti['percent'], 12) ?> <?= lang('tax'); ?></span>
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
                            <?= pos_ti($pos_ti['pause'], 13) ?> <?= lang('hold') ?>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="pos-btn pos-btn-danger" id="reset">
                            <?= pos_ti($pos_ti['x'], 13) ?> <?= lang('cancel') ?>
                        </button>
                        <?php if (!$t_nc): ?>
                        <button type="button" class="pos-btn pos-btn-ghost" id="print_bill">
                            <?= pos_ti($pos_ti['printer'], 14) ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <button type="button"
                            class="pos-btn pos-btn-pay"
                            id="<?= $eid ? 'submit-sale' : 'payment' ?>">
                        <?= pos_ti($pos_ti['circlecheck'], 16) ?>
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
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span style="color:var(--nx-a1);"><?= pos_ti($pos_ti['userplus'], 16) ?></span>
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
                            <option value="01">01 — <?= lang('cedula_identidad'); ?></option>
                            <option value="02">02 — <?= lang('cedula_juridica'); ?></option>
                            <option value="03">03 — DIMEX</option>
                            <option value="04">04 — NITE</option>
                            <option value="05">05 — <?= lang('pasaporte'); ?></option>
                        </select>
                    </div>
                    <div class="col-7">
                        <label class="form-label"><?= lang('cf2') ?> <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <?= form_input('cf2', '', 'class="form-control" id="cf2" required autocomplete="off" placeholder="' . lang('placeholder_cedula') . '"') ?>
                            <button type="button" class="btn btn-outline-info" id="btn-hac-lookup"
                                    title="<?= lang('consultar_hacienda_title'); ?>"
                                    style="font-size:.75rem;padding:.25rem .5rem;">
                                <span id="hac-icon"><?= pos_ti($pos_ti['search'], 13) ?></span>
                            </button>
                        </div>
                        <small class="text-muted d-flex align-items-center gap-1" style="font-size:.65rem">
                            <?= pos_ti($pos_ti['wand'], 11) ?> <?= lang('autocompleta_hacienda_hint'); ?>
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
                <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                    <?= pos_ti($pos_ti['plus'], 13) ?><?= lang('add_customer') ?>
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
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span style="color:var(--nx-a1);"><?= pos_ti($pos_ti['message'], 16) ?></span>
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
                <button type="button" class="btn btn-primary d-flex align-items-center gap-1" data-bs-dismiss="modal">
                    <?= pos_ti($pos_ti['check'], 13) ?><?= lang('accept') ?>
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
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span style="color:var(--nx-ok);"><?= pos_ti($pos_ti['calculator'], 16) ?></span>
                    <?= lang('payment') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Payment method selector -->
                <div class="pay-methods-grid">
                    <button type="button" class="pay-method-btn active" data-method="cash" id="pmCash">
                        <?= pos_ti($pos_ti['cash'], 16) ?><?= lang('cash'); ?>
                    </button>
                    <button type="button" class="pay-method-btn" data-method="card" id="pmCard">
                        <?= pos_ti($pos_ti['creditcard'], 16) ?><?= lang('tarjeta'); ?>
                    </button>
                    <button type="button" class="pay-method-btn" data-method="sinpe" id="pmSinpe">
                        <?= pos_ti($pos_ti['phoneall'], 16) ?>SINPE
                    </button>
                    <button type="button" class="pay-method-btn" data-method="transfer" id="pmTransfer">
                        <?= pos_ti($pos_ti['bank'], 16) ?><?= lang('transferencia_abr'); ?>
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
                    <button type="button" class="pay-quick-btn exact d-flex align-items-center gap-1" id="payExact">
                        <?= pos_ti($pos_ti['equal'], 13) ?><?= lang('exacto'); ?>
                    </button>
                    <button type="button" class="pay-quick-btn" data-amount="5000">₡5,000</button>
                    <button type="button" class="pay-quick-btn" data-amount="10000">₡10,000</button>
                    <button type="button" class="pay-quick-btn" data-amount="20000">₡20,000</button>
                    <button type="button" class="pay-quick-btn" data-amount="50000">₡50,000</button>
                </div>
            </div>
            <div class="modal-footer" style="padding:.875rem 1.25rem;">
                <button type="button" class="btn btn-secondary d-flex align-items-center gap-1" data-bs-dismiss="modal">
                    <?= pos_ti($pos_ti['x'], 13) ?><?= lang('close') ?>
                </button>
                <button type="button" class="pay-submit-btn d-flex align-items-center justify-content-center gap-1" id="submit-sale" style="flex:1;max-width:200px;">
                    <?= pos_ti($pos_ti['check'], 15) ?>
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
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span style="color:var(--nx-ok);"><?= pos_ti($pos_ti['bolt'], 16) ?></span>
                    <?= lang('modal_producto_rapido'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Nombre -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= lang('name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ah-name" placeholder="<?= lang('desc_prod_servicio'); ?>" autocomplete="off" required>
                </div>

                <!-- CABYS -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= lang('codigo_cabys'); ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="ah-cabys" placeholder="0000000000000" maxlength="13" autocomplete="off" style="max-width:170px;">
                        <input type="text" class="form-control" id="ah-cabys-desc" placeholder="<?= lang('desc_cabys'); ?>" readonly>
                        <button type="button" class="btn btn-outline-info d-flex align-items-center gap-1" id="ah-cabys-search-btn">
                            <span id="ah-cabys-icon"><?= pos_ti($pos_ti['search'], 13) ?></span> <?= lang('buscar'); ?>
                        </button>
                    </div>
                    <!-- Panel resultados CABYS -->
                    <div id="ah-cabys-results" class="mt-2" style="display:none;max-height:220px;overflow-y:auto;border:1px solid var(--bs-border-color);border-radius:.375rem;"></div>
                </div>

                <!-- Cantidad + Costo + Precio -->
                <div class="row g-2 mb-3">
                    <div class="col-3">
                        <label class="form-label fw-semibold"><?= lang('quantity'); ?> <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="ah-qty" value="1" min="0.01" step="any">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold"><?= lang('cost'); ?></label>
                        <input type="number" class="form-control" id="ah-cost" placeholder="0.00" min="0" step="any">
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-semibold"><?= lang('precio_sin_impuesto'); ?> <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="ah-price" placeholder="0.00" min="0" step="any">
                    </div>
                </div>

                <!-- IVA -->
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="ah-iva-switch" role="switch">
                            <label class="form-check-label fw-semibold" for="ah-iva-switch"><?= lang('lleva_iva'); ?></label>
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
                            <div class="text-muted small"><?= lang('precio_unit'); ?></div>
                            <div class="fw-bold" id="ah-preview-price">₡0.00</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small"><?= lang('impuesto_unit'); ?></div>
                            <div class="fw-bold text-warning" id="ah-preview-tax">₡0.00</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small"><?= lang('total_linea'); ?></div>
                            <div class="fw-bold text-success" id="ah-preview-total">₡0.00</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel'); ?></button>
                <button type="button" class="btn btn-success px-4 d-flex align-items-center gap-1" id="ah-confirm-btn">
                    <?= pos_ti($pos_ti['plus'], 14) ?> <?= lang('agregar_carrito'); ?>
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
                <h5 class="modal-title d-flex align-items-center gap-2"><?= pos_ti($pos_ti['search'], 16) ?><?= lang('buscar_cabys'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="cabys-q" placeholder="<?= lang('buscar_cabys_placeholder'); ?>">
                    <button type="button" class="btn btn-primary" id="cabys-go-btn">
                        <?= pos_ti($pos_ti['search'], 15) ?>
                    </button>
                </div>
                <div id="cabys-results-list" style="max-height:380px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL: Configurar impresora de esta computadora (QZ Tray)
════════════════════════════════════════════════ -->
<div class="modal fade" id="printerConfigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2"><?= pos_ti($pos_ti['printer'], 16) ?><?= lang('configurar_impresora'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:.85rem;"><?= lang('printer_config_help'); ?></p>
                <div class="form-group">
                    <label class="form-label fw-semibold"><?= lang('impresora'); ?></label>
                    <select id="qzPrinterSelect" class="form-control"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="printerConfigSaveBtn"><?= lang('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL: Abrir cajón (PIN de administrador)
════════════════════════════════════════════════ -->
<div class="modal fade" id="drawerPinModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2"><?= pos_ti($pos_ti['cash'], 16) ?><?= lang('abrir_cajon'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="drawerPinError"></div>
                <div class="form-group">
                    <label class="form-label fw-semibold"><?= lang('pin_cajon'); ?></label>
                    <input type="password" class="form-control" id="drawerPinInput" inputmode="numeric" pattern="[0-9]*" autocomplete="off" placeholder="••••">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('cancel'); ?></button>
                <button type="button" class="btn btn-danger" id="drawerPinConfirmBtn"><?= lang('abrir_cajon'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     OVERLAY: bloqueo total mientras QZ Tray no esté conectado
     (una instalación por terminal — ver qz.io/download)
════════════════════════════════════════════════ -->
<div id="qzBlockOverlay" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.9);color:#fff;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px;">
    <div class="spinner-border text-light mb-3" role="status" style="width:3rem;height:3rem;"></div>

    <!-- Estado: revisando conexión (arranque, muy breve) -->
    <div id="qzPanelChecking">
        <h4><?= lang('qz_verificando'); ?></h4>
    </div>

    <!-- Estado: QZ Tray instalado, esperando que el usuario acepte su ventana de permiso -->
    <div id="qzPanelWaiting" style="display:none;">
        <h4><?= lang('qz_esperando_permiso'); ?></h4>
        <p style="max-width:420px;opacity:.85;"><?= lang('qz_esperando_permiso_ayuda'); ?></p>
    </div>

    <!-- Estado: QZ Tray no detectado, ofrecer instalación -->
    <div id="qzPanelInstall" style="display:none;">
        <h4><?= lang('qz_desconectado'); ?></h4>
        <p style="max-width:420px;opacity:.85;">
            <?= lang('qz_desconectado_ayuda'); ?>
        </p>

        <div style="background:#fff; color:#222; border-radius:14px; padding:26px 30px; max-width:380px; width:100%; box-shadow:0 8px 30px rgba(0,0,0,.4);">
            <div style="font-size:2.4rem; line-height:1;">🖨️</div>
            <a href="<?= $assets ?>instalar-qz-tray.bat" download class="btn btn-primary btn-lg" style="width:100%; font-weight:700; margin:14px 0;">
                ⬇ <?= lang('descargar_e_instalar'); ?>
            </a>
            <ol style="text-align:left; font-size:.95rem; padding-left:20px; margin:0; color:#444;">
                <li><?= lang('qz_paso_1'); ?></li>
                <li><?= lang('qz_paso_2'); ?></li>
                <li><?= lang('qz_paso_3'); ?></li>
            </ol>
        </div>

        <p style="max-width:420px; opacity:.6; font-size:.8rem; margin-top:16px;"><?= lang('qz_instalar_una_vez'); ?></p>
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

    window.CSRF_NAME = '<?= $this->security->get_csrf_token_name(); ?>';
    window.CSRF_HASH = '<?= $this->security->get_csrf_hash(); ?>';

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
        type_reference_note: '<?= addslashes(lang('type_reference_note')); ?>',
        usando_precio_oferta:     '<?= addslashes(lang('usando_precio_oferta')); ?>',
        precio_oferta_disponible: '<?= addslashes(lang('precio_oferta_disponible')); ?>',
        error_obtener_producto:   '<?= addslashes(lang('error_obtener_producto')); ?>',
        error_busqueda:           '<?= addslashes(lang('error_busqueda')); ?>',
        error:                    '<?= addslashes(lang('error')); ?>',
        error_agregar_cliente:    '<?= addslashes(lang('error_agregar_cliente')); ?>',
        cedula_identidad:         '<?= addslashes(lang('cedula_identidad')); ?>',
        cedula_juridica:          '<?= addslashes(lang('cedula_juridica')); ?>',
        pasaporte:                '<?= addslashes(lang('pasaporte')); ?>',
        cliente_contado:          '<?= addslashes(lang('cliente_contado')); ?>',
        documento_label:          '<?= addslashes(lang('documento_label')); ?>',
        buscar_cliente_placeholder: '<?= addslashes(lang('buscar_cliente_placeholder')); ?>',
        impresion_auto_on:        '<?= addslashes(lang('impresion_auto_on')); ?>',
        impresion_auto_off_title: '<?= addslashes(lang('impresion_auto_off_title')); ?>',
        impresion_auto_desactivada: '<?= addslashes(lang('impresion_auto_desactivada')); ?>',
        impresion_auto_activada:  '<?= addslashes(lang('impresion_auto_activada')); ?>',
        atajos_teclado:           '<?= addslashes(lang('atajos_teclado')); ?>',
        modal_producto_rapido:    '<?= addslashes(lang('modal_producto_rapido')); ?>',
        buscar:                   '<?= addslashes(lang('buscar')); ?>',
        kbd_cobrar:               '<?= addslashes(lang('kbd_cobrar')); ?>',
        kbd_cancelar_busqueda:    '<?= addslashes(lang('kbd_cancelar_busqueda')); ?>',
        kbd_navegar_lista:        '<?= addslashes(lang('kbd_navegar_lista')); ?>',
        kbd_agregar_producto:     '<?= addslashes(lang('kbd_agregar_producto')); ?>',
        kbd_foco_busqueda:        '<?= addslashes(lang('kbd_foco_busqueda')); ?>',
        ingrese_nombre_producto:  '<?= addslashes(lang('ingrese_nombre_producto')); ?>',
        ingrese_cabys_valido:     '<?= addslashes(lang('ingrese_cabys_valido')); ?>',
        ingrese_precio_valido:    '<?= addslashes(lang('ingrese_precio_valido')); ?>',
        carrito_compras:          '<?= addslashes(lang('carrito_compras')); ?>',
        catalogo_productos:       '<?= addslashes(lang('catalogo_productos')); ?>',
        producto_agregado:        '<?= addslashes(lang('producto_agregado')); ?>',
        no_products_found:        '<?= addslashes(lang('no_products_found')); ?>',
        atajos_hint:              '<?= addslashes(lang('atajos_hint')); ?>'
    };
</script>
<script src="<?= $assets ?>dist/js/pos-core.js?v=<?= @filemtime(FCPATH.'themes/default/assets/dist/js/pos-core.js') ?: '1'; ?>"></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?= base_url('sw.js') ?>');
    });
}
</script>
</body>
</html>
