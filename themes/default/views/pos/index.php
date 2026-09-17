<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) or exit('No direct script access allowed'); ?>
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
    <!-- Los iconos fa-* no vienen en www.min.css -->
    <link href="<?= $assets ?>plugins/font-awesome/css/font-awesome.css" rel="stylesheet" />
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
      'cashregister' => '<path d="M21 15h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5"/><path d="M19 21v1m0 -8v1"/><path d="M13 21h-7a3 3 0 0 1 -3 -3v-2h10v2a3 3 0 0 0 3 3z"/><path d="M4 16v-8a2 2 0 0 1 2 -2h2"/><path d="M8 3m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"/><path d="M8 10l0 .01"/><path d="M12 10l0 .01"/><path d="M16 10l0 .01"/><path d="M8 13l0 .01"/><path d="M12 13l0 .01"/>',
      'printercog'   => '<g transform="translate(-0.6,-1.4) scale(0.8)"><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/><path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z"/></g><path d="M18.5 18.5m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M18.5 15v1.5"/><path d="M18.5 20.5v1.5"/><path d="M21.53 16.75l-1.3 .75"/><path d="M16.77 19.5l-1.3 .75"/><path d="M15.47 16.75l1.3 .75"/><path d="M20.23 19.5l1.3 .75"/>',
      'alerttriangle'=> '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>',
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
                    <img src="<?= avatar_usuario($this->session->userdata('avatar'), $this->session->userdata('gender')); ?>"
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

            <!-- Estado de la conexion con Hacienda -->
            <?php $hac = $hacienda_estado ?? array('ok' => TRUE, 'motivo' => 'hacienda_al_dia', 'pendientes' => 0); ?>
            <div class="pos-topbar-chip d-flex align-items-center gap-1 <?= $hac['ok'] ? 'hac-ok' : 'hac-err' ?>"
                 title="<?= html_escape(lang('conexion_hacienda') . ' — ' . lang($hac['motivo']) . ($hac['pendientes'] ? ' (' . $hac['pendientes'] . ')' : '')); ?>">
                <?= pos_ti($hac['ok'] ? $pos_ti['circlecheck'] : $pos_ti['alerttriangle'], 14) ?>
                <span><?= lang('hacienda'); ?></span>
            </div>

            <div class="pos-topbar-spacer"></div>

            <!-- Ventas suspendidas -->
            <?php if ($suspended_sales && count($suspended_sales) > 0): ?>
            <div class="dropdown">
                <button class="pos-topbar-btn position-relative" id="holdBillsBtn" data-bs-toggle="dropdown" title="<?= lang('ventas_suspendidas'); ?>">
                    <?= pos_ti($pos_ti['bell'], 17) ?>
                    <span class="pos-topbar-badge"><?= count($suspended_sales) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end pos-sus-menu">
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
                                <div class="pos-sus-info">
                                    <div class="pos-sus-ref"><?= html_escape($ss->hold_ref ?: lang('no_ref')) ?></div>
                                    <div class="pos-sus-cli"><?= html_escape($ss->customer_name) ?></div>
                                    <div class="pos-sus-fec"><?= $this->tec->hrld($ss->date) ?></div>
                                </div>
                                <div class="pos-sus-monto"><?= $this->tec->formatMoney($ss->grand_total ?? 0) ?></div>
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

            <!-- Cierre de caja. Estando en el POS la caja ya esta abierta:
                 el boton que hace falta aca es el de cerrarla. -->
            <button type="button" class="pos-topbar-btn" id="cerrarCajaBtn" title="<?= lang('cerrar_caja'); ?>">
                <?= pos_ti($pos_ti['cashregister'], 17) ?>
            </button>

            <!-- Configurar impresora de esta computadora (QZ Tray) -->
            <button class="pos-topbar-btn" id="printerConfigBtn" title="<?= lang('configurar_impresora'); ?>" data-bs-toggle="modal" data-bs-target="#printerConfigModal">
                <?= pos_ti($pos_ti['printercog'], 17) ?>
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
                    <img src="<?= avatar_usuario($this->session->userdata('avatar'), $this->session->userdata('gender'), true) ?>"
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
                        <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                           href="<?= $this->session->userdata('register_id') ? site_url('pos') . '?cerrar_caja=1' : site_url('logout') ?>"
                           <?php if ($this->session->userdata('register_id')): ?>title="<?= htmlspecialchars(lang('close_register_before_logout')) ?>"<?php endif; ?>>
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
                        <?php if (!empty($Settings->focus_add_item)): ?>
                        <div class="pos-search-kbds">
                            <kbd><?= html_escape($Settings->focus_add_item); ?></kbd>
                        </div>
                        <?php endif; ?>
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
                        <span><?= lang('customer') ?></span>
                        <button type="button" class="pcp-add-cust-btn"
                                data-bs-toggle="modal" data-bs-target="#customerModal"
                                title="<?= lang('add_customer') ?>">
                            <?= pos_ti($pos_ti['userplus'], 13) ?>
                            <span><?= lang('nuevo') ?></span>
                        </button>
                    </div>

                    <!-- Hidden input para el submit -->
                    <input type="hidden" id="pos-customer-hidden" name="customer_id"
                           value="<?= (int)$Settings->default_customer ?>">

                    <!-- Barra de búsqueda (visible cuando no hay cliente) -->
                    <div class="pcp-cust-search-wrap" id="pos-cust-search-wrap">
                        <p class="pcp-cust-hint"><?= pos_ti($pos_ti['infocircle'], 12) ?> <?= lang('buscar_cliente_hint'); ?></p>
                        <div class="pcp-cust-buscador">
                            <span class="search-icon"><?= pos_ti($pos_ti['magnifier'], 14) ?></span>
                            <input type="text" id="pos-cust-search" autocomplete="off"
                                   placeholder="<?= lang('buscar_cliente_placeholder'); ?>">
                        </div>
                    </div>

                    <!-- Info del cliente: lupa (re-buscar) + avatar + datos + X -->
                    <div class="pcp-cust-card is-default" id="pos-cust-card">
                        <div class="pcp-cust-avatar" id="pos-cust-avatar">C</div>
                        <div class="pcp-cust-info">
                            <div class="pcp-cust-name" id="pos-cust-name"><?= lang('cliente_contado'); ?></div>
                            <div class="pcp-cust-meta" id="pos-cust-doc"></div>
                            <div class="pcp-cust-contact" id="pos-cust-contact"></div>
                        </div>
                        <button type="button" class="pcp-cust-clear-btn" id="pos-cust-buscar"
                                title="<?= lang('buscar_cliente_placeholder'); ?>">
                            <?= pos_ti($pos_ti['magnifier'], 13) ?>
                        </button>
                        <button type="button" class="pcp-cust-clear-btn" id="pos-cust-clear"
                                title="<?= lang('quitar_cliente'); ?>" style="display:none">
                            <?= pos_ti($pos_ti['x'], 14) ?>
                        </button>
                    </div>
                </div>

                <!-- Items list -->
                <div class="pcp-items">
                    <table>
                        <thead>
                            <!-- Con table-layout:fixed los anchos salen de esta fila:
                                 las clases pcp-c-* tienen que estar tambien aca. -->
                            <tr>
                                <th class="pcp-c-prod"><?= lang('product') ?></th>
                                <th class="pcp-c-price"><?= lang('unit_price_abbr'); ?></th>
                                <th class="pcp-c-qty" style="text-align:center;"><?= lang('qty') ?></th>
                                <th class="pcp-c-total"><?= lang('total') ?></th>
                                <th class="pcp-c-del"></th>
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
                        <button type="button" class="tl pcp-ds-btn" id="add_discount">
                            <?= pos_ti($pos_ti['tag'], 12) ?> <?= lang('discount') ?>
                        </button>
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
                            <?= pos_ti($pos_ti['pause'], 17) ?> <?= lang('hold') ?>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="pos-btn pos-btn-danger" id="reset">
                            <?= pos_ti($pos_ti['x'], 17) ?> <?= lang('cancel') ?>
                        </button>
                        <button type="button" class="pos-btn pos-btn-ghost" id="notasBtn"
                                data-bs-toggle="modal" data-bs-target="#ModalNotes"
                                title="<?= lang('notes') ?>">
                            <?= pos_ti($pos_ti['message'], 17) ?> <?= lang('notes') ?>
                        </button>
                    </div>

                    <button type="button"
                            class="pos-btn pos-btn-pay"
                            id="<?= $eid ? 'submit-sale' : 'payment' ?>">
                        <?= pos_ti($pos_ti['circlecheck'], 16) ?>
                        <?= $eid ? lang('submit') : lang('payment') ?>
                        <?php if (!empty($Settings->finalize_sale)): ?>
                        <span class="kh"><?= html_escape($Settings->finalize_sale); ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- Hidden form fields -->
                <!-- Origen de la venta. Sin estos campos Pos.php no sabe que la
                     venta viene de una cuenta en espera, de una edicion, de una
                     proforma o de un apartado: cierra el comprobante nuevo y
                     deja el original abierto. -->
                <input type="hidden" name="did"      id="did_val"      value="<?= (int)($sid ?? 0); ?>">
                <input type="hidden" name="eid"      id="eid_val"      value="<?= (int)($eid ?? 0); ?>">
                <input type="hidden" name="quo"      id="quo_val"      value="<?= (int)($quo ?? 0); ?>">
                <input type="hidden" name="apapost"  id="apapost_val"  value="<?= (int)($apa ?? 0); ?>">
                <input type="hidden" name="token_post" value="<?= md5(uniqid('', TRUE)); ?>">

                <input type="hidden" name="total_tax"     id="total_tax"      value="<?= $total_tax ?>">
                <input type="hidden" name="order_tax"     id="tax_val"        value="">
                <input type="hidden" name="order_discount" id="discount_val"  value="">
                <input type="hidden" name="count"          id="total_item"    value="">
                <input type="hidden" name="amount"         id="amount_val"    value="">
                <input type="hidden" name="paid_by"        id="paid_by_val"   value="cash">
                <input type="hidden" name="payment_note"   id="payment_note_val" value="">
                <!-- Referencia del primer pago (la arma #payModal) -->
                <input type="hidden" name="sinpe_reference" id="sinpe_reference" value="">

                <!-- Pagos 2 a 4. Las referencias van corridas: freferencia1
                     corresponde al pago 2, freferencia2 al 3, y asi. -->
                <input type="hidden" name="amount2"       id="amount2_val"     value="">
                <input type="hidden" name="paid_by2"      id="paid_by2_val"    value="">
                <input type="hidden" name="freferencia1"  id="freferencia1_val" value="">
                <input type="hidden" name="amount3"       id="amount3_val"     value="">
                <input type="hidden" name="paid_by3"      id="paid_by3_val"    value="">
                <input type="hidden" name="freferencia2"  id="freferencia2_val" value="">
                <input type="hidden" name="amount4"       id="amount4_val"     value="">
                <input type="hidden" name="paid_by4"      id="paid_by4_val"    value="">
                <input type="hidden" name="freferencia3"  id="freferencia3_val" value="">
                <input type="hidden" name="balance_amount" id="balance_amount_val" value="0">

                <!-- Lo que el cajero eligio en el cobro. Pos.php lo vuelve a
                     comprobar contra el cliente: si no calza, gana el servidor. -->
                <input type="hidden" name="tipo_doc"       id="tipo_doc_val"       value="">
                <input type="hidden" name="situacion"      id="situacion_val"      value="1">

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
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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

                <!-- ── Identificación: decide si el cliente admite factura ── -->
                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <label class="form-label"><?= lang('cf1') ?> <span class="text-danger">*</span></label>
                        <select name="cf1" class="form-select form-select-sm" id="cf1" required>
                            <option value="01">01 — <?= lang('cedula_identidad'); ?></option>
                            <option value="02">02 — <?= lang('cedula_juridica'); ?></option>
                            <option value="03">03 — DIMEX</option>
                            <option value="04">04 — NITE</option>
                            <option value="05">05 — <?= lang('extranjero_no_domiciliado'); ?></option>
                        </select>
                    </div>
                    <div class="col-8">
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

                <!-- ── Nombre y nombre comercial ── -->
                <div class="row g-2 mb-2">
                    <div class="col-md-7">
                        <label class="form-label"><?= lang('name') ?> <span class="text-danger">*</span></label>
                        <?= form_input('name', '', 'class="form-control form-control-sm" id="cname" maxlength="100" required') ?>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label"><?= lang('nombre_comercial') ?></label>
                        <?= form_input('business_name', '', 'class="form-control form-control-sm" id="cbusiness" maxlength="80"') ?>
                    </div>
                </div>

                <!-- ── Contacto. El codigo de pais es <CodigoPais> del comprobante. ── -->
                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <label class="form-label"><?= lang('email_address') ?></label>
                        <?= form_input('email', '', 'class="form-control form-control-sm" id="cemail" maxlength="160" type="email"') ?>
                    </div>
                    <div class="col-4 col-md-2">
                        <label class="form-label"><?= lang('cod_telefono') ?></label>
                        <?= form_input('cod_telefono', '506', 'class="form-control form-control-sm" id="ccodtel" maxlength="3" inputmode="numeric"') ?>
                    </div>
                    <div class="col-8 col-md-5">
                        <label class="form-label"><?= lang('phone') ?></label>
                        <?= form_input('phone', '', 'class="form-control form-control-sm" id="cphone" maxlength="20" inputmode="tel"') ?>
                    </div>
                </div>

                <!-- ── Actividad economica: la trae el padron y hoy se perdia ── -->
                <div class="mb-2 d-none" id="cActividadWrap">
                    <label class="form-label"><?= lang('cod_act_economica_label') ?></label>
                    <select name="codigo_actividad" id="cactividad" class="form-select form-select-sm"></select>
                    <small class="text-muted" style="font-size:.65rem"><?= lang('actividades_registradas'); ?></small>
                    <div id="cActividadesHidden"></div>
                </div>

                <!-- ── Ubicación del receptor (opcional, pero completa o ninguna) ── -->
                <details class="pos-cust-more mb-2" id="cUbicacionBox">
                    <summary><?= lang('cliente_paso_ubicacion') ?> <span class="text-muted">· <?= lang('opcional') ?></span></summary>
                    <div class="pt-2">
                        <div class="row g-2 mb-2" id="cUbicacionCR">
                            <div class="col-6 col-md-3">
                                <label class="form-label"><?= lang('provincia') ?></label>
                                <select name="codigo_provincia" id="codigo_provincia" class="form-select form-select-sm"
                                        data-hijo="codigo_canton" data-url="<?= site_url('customers/get_cantones'); ?>">
                                    <option value="">— <?= lang('Seleccione'); ?> —</option>
                                    <?php foreach (($provincias ?? []) as $p) { ?>
                                        <option value="<?= $p->codigo; ?>"><?= $p->nombre; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label"><?= lang('canton') ?></label>
                                <select name="codigo_canton" id="codigo_canton" class="form-select form-select-sm"
                                        data-hijo="codigo_distrito" data-url="<?= site_url('customers/get_distritos'); ?>">
                                    <option value="">— <?= lang('Seleccione'); ?> —</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label"><?= lang('distrito') ?></label>
                                <select name="codigo_distrito" id="codigo_distrito" class="form-select form-select-sm"
                                        data-hijo="codigo_barrio" data-url="<?= site_url('customers/get_barrios'); ?>">
                                    <option value="">— <?= lang('Seleccione'); ?> —</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label"><?= lang('barrio') ?></label>
                                <select name="codigo_barrio" id="codigo_barrio" class="form-select form-select-sm">
                                    <option value="">— <?= lang('Seleccione'); ?> —</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label"><?= lang('otras_senas') ?></label>
                                <?= form_input('otras_senas', '', 'class="form-control form-control-sm" id="cotrasenas" maxlength="250"') ?>
                                <small class="text-muted" style="font-size:.65rem"><?= lang('otras_senas_ayuda'); ?></small>
                            </div>
                        </div>
                        <div class="d-none" id="cUbicacionExtranjero">
                            <label class="form-label"><?= lang('otras_senas_extranjero') ?></label>
                            <?= form_input('otras_senas_extranjero', '', 'class="form-control form-control-sm" id="cextranjero" maxlength="300"') ?>
                            <small class="text-muted" style="font-size:.65rem"><?= lang('otras_senas_extranjero_ayuda'); ?></small>
                        </div>
                    </div>
                </details>

                <!-- ── Condiciones comerciales ── -->
                <details class="pos-cust-more" id="cComercialBox">
                    <summary><?= lang('cliente_paso_comercial') ?> <span class="text-muted">· <?= lang('opcional') ?></span></summary>
                    <div class="row g-2 pt-2">
                        <div class="col-6 col-md-3">
                            <label class="form-label"><?= lang('codigo_cliente') ?></label>
                            <?= form_input('codigo_cliente', '', 'class="form-control form-control-sm" id="ccodigo" maxlength="30"') ?>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label"><?= lang('tipo_doc_defecto') ?></label>
                            <select name="tipo_doc_defecto" id="ctipodoc" class="form-select form-select-sm">
                                <option value=""><?= lang('segun_el_cliente'); ?></option>
                                <option value="01"><?= lang('factura'); ?></option>
                                <option value="04"><?= lang('tiquete'); ?></option>
                            </select>
                        </div>
                        <?php if ($Settings->enable_credit == 1) { ?>
                            <div class="col-6 col-md-3">
                                <label class="form-label"><?= lang('tipo_pago_defecto') ?></label>
                                <select name="tipo_pago_defecto" id="ctipopago" class="form-select form-select-sm">
                                    <option value=""><?= lang('sin_definir'); ?></option>
                                    <option value="contado"><?= lang('contado'); ?></option>
                                    <option value="credito"><?= lang('credito'); ?></option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label"><?= lang('dias_credito') ?></label>
                                <?= form_input('dias_credito', '0', 'class="form-control form-control-sm" id="cdiascred" type="number" min="0" max="365"') ?>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label"><?= lang('limitcredit') ?></label>
                                <?= form_input('limitcredit', '0', 'class="form-control form-control-sm" id="climite" inputmode="decimal"') ?>
                            </div>
                        <?php } ?>
                    </div>
                </details>
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
                    <label class="form-label" for="hold_ref"><?= lang('reference_note') ?> <span class="text-danger">*</span></label>
                    <?= form_input('hold_ref', $reference_note ?? '', 'class="form-control" id="hold_ref" autocomplete="off"') ?>
                    <div class="invalid-feedback d-block" id="hold_ref_error" style="display:none !important;"></div>
                </div>
                <!-- Pos.php lee spos_note; la nota libre se retiro del modal. -->
                <input type="hidden" name="spos_note" id="spos_note" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('close') ?></button>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-1" id="notasAceptar">
                    <?= pos_ti($pos_ti['check'], 13) ?><?= lang('accept') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="payModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl pay-dialog">
        <div class="modal-content">
            <div class="modal-header pay-head">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <span style="color:var(--nx-ok);"><?= pos_ti($pos_ti['calculator'], 17) ?></span>
                    <?= lang('payment') ?>
                </h5>
                <div class="pay-head-total">
                    <span><?= lang('total_payable') ?></span>
                    <strong id="payHeadTotal">₡0.00</strong>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pay-body">
                <div class="pay-grid">

                    <!-- ═══ Columna izquierda: qué se emite y cómo se cobra ═══ -->
                    <div class="pay-col">

                        <!-- Comprobante. El que el cliente no admite sale
                             deshabilitado; el motivo va en el tooltip. -->
                        <div class="pay-block">
                            <div class="pay-block-rot"><?= lang('comprobante'); ?></div>
                            <div class="doc-selector-grid">
                                <button type="button" class="doc-btn active" data-doc="04" id="docTiquete">
                                    <?= pos_ti($pos_ti['receipt'], 16) ?>
                                    <strong><?= lang('tiquete_electronico'); ?></strong>
                                </button>
                                <button type="button" class="doc-btn" data-doc="01" id="docFactura">
                                    <?= pos_ti($pos_ti['fileinvoice'], 16) ?>
                                    <strong><?= lang('factura_electronica'); ?></strong>
                                </button>
                            </div>
                            <div class="doc-motivo" id="docMotivo"></div>

                            <!-- Cambia la posicion 42 de la clave: no es cosmetico. -->
                            <label class="doc-conting">
                                <input type="checkbox" id="docContingencia">
                                <span><?= lang('contingencia'); ?></span>
                            </label>
                        </div>

                        <!-- Formas de pago. El credito es una mas: lo que queda
                             sin cubrir es lo que se fia. -->
                        <div class="pay-block">
                            <div class="pay-block-rot"><?= lang('paid_by'); ?></div>
                            <div class="pay-methods-grid<?= ($Settings->enable_credit == 1) ? ' has-credit' : ''; ?>">
                                <button type="button" class="pay-method-btn active" data-method="cash" id="pmCash">
                                    <?= pos_ti($pos_ti['cash'], 17) ?><span><?= lang('cash'); ?></span>
                                </button>
                                <button type="button" class="pay-method-btn" data-method="card" id="pmCard">
                                    <?= pos_ti($pos_ti['creditcard'], 17) ?><span><?= lang('tarjeta'); ?></span>
                                </button>
                                <button type="button" class="pay-method-btn" data-method="sinpe" id="pmSinpe">
                                    <?= pos_ti($pos_ti['phoneall'], 17) ?><span>SINPE</span>
                                </button>
                                <button type="button" class="pay-method-btn" data-method="transfer" id="pmTransfer">
                                    <?= pos_ti($pos_ti['bank'], 17) ?><span><?= lang('transferencia_abr'); ?></span>
                                </button>
                                <?php if ($Settings->enable_credit == 1) { ?>
                                    <button type="button" class="pay-method-btn is-credit" data-method="credito" id="pmCredito">
                                        <?= pos_ti($pos_ti['pausecircle'], 17) ?><span><?= lang('credito'); ?></span>
                                    </button>
                                <?php } ?>
                            </div>
                            <div class="pay-credit-info" id="payCreditInfo" hidden></div>
                        </div>

                        <!-- SINPE entrantes que cubren el total. Solo con método "sinpe". -->
                        <div id="sinpePendingPanel" class="pay-block" style="display:none;">
                            <div class="d-flex align-items-center justify-content-between" style="margin-bottom:.3rem;">
                                <small class="text-muted">
                                    <i class="fa fa-circle" id="sinpeLiveDot" style="color:#adb5bd;font-size:8px;"></i>
                                    SINPE entrantes que cubren el total
                                </small>
                                <small class="text-muted" id="sinpePendingCount"></small>
                            </div>

                            <select id="sinpePendingSelect" class="form-select" style="font-size:13px;">
                                <option value="">Buscando pagos SINPE recientes…</option>
                            </select>

                            <div id="sinpeSelectedInfo" style="display:none;margin-top:.5rem;padding:.55rem .7rem;border-radius:6px;background:rgba(46,204,113,.12);font-size:12.5px;line-height:1.55;">
                                <div class="d-flex justify-content-between align-items-start" style="gap:.5rem;">
                                    <div style="min-width:0;">
                                        <div><i class="fa fa-user" style="width:14px;"></i> <strong id="sinpeSelNombre">—</strong></div>
                                        <div><i class="fa fa-phone" style="width:14px;"></i> <span id="sinpeSelTelefono">—</span></div>
                                        <div><i class="fa fa-university" style="width:14px;"></i> <span id="sinpeSelBanco">—</span> · <span id="sinpeSelFecha">—</span></div>
                                        <div><i class="fa fa-hashtag" style="width:14px;"></i> Referencia: <strong id="sinpeSelectedComprobante">—</strong></div>
                                        <div id="sinpeSelDescRow" style="display:none;"><i class="fa fa-file-text-o" style="width:14px;"></i> <span id="sinpeSelDescripcion"></span></div>
                                    </div>
                                    <div style="text-align:right;white-space:nowrap;">
                                        <div style="font-weight:700;font-size:15px;" id="sinpeSelMonto">—</div>
                                        <button type="button" class="btn btn-link btn-sm p-0" id="sinpeClearSelection">quitar</button>
                                    </div>
                                </div>
                                <div id="sinpeSobrantePago" style="display:none;margin-top:.35rem;font-size:11.5px;" class="text-muted"></div>
                            </div>
                        </div>

                        <!-- Obligatorio en tarjeta y transferencia; en SINPE se autocompleta. -->
                        <div class="pay-amount-group" id="payRefGroup" style="display:none;">
                            <label id="payRefLabel"><?= lang('payment_ref') ?></label>
                            <input type="text" id="payRef" placeholder="<?= lang('numero_transaccion') ?>" autocomplete="off">
                        </div>

                        <!-- Datafono: el cobro devuelve referencia y monto, no se teclean -->
                        <div id="payDatafonoWrap" style="display:none;">
                            <button type="button" class="pay-datafono-btn" id="payDatafonoBtn">
                                <?= pos_ti($pos_ti['creditcard'], 16) ?>
                                <span><?= lang('enviar_cobro_datafono'); ?></span>
                            </button>
                        </div>

                        <!-- Solo hace falta al cobrar con mas de una forma de pago. -->
                        <button type="button" class="pay-add-btn" id="payAddLine">
                            <span class="pay-add-ico"><?= pos_ti($pos_ti['plus'], 14) ?></span>
                            <strong><?= lang('agregar_forma_pago') ?></strong>
                        </button>

                        <!-- Desglose de formas de pago agregadas -->
                        <div id="payLinesWrap" style="display:none;">
                            <div class="pay-block-rot">
                                <?= lang('formas_de_pago') ?> <span id="payLinesCount"></span>
                            </div>
                            <div id="payLines" class="pay-lines"></div>
                        </div>
                    </div>

                    <!-- ═══ Columna derecha: teclado y totales ═══ -->
                    <div class="pay-col pay-col-pad">

                        <div class="pay-display">
                            <label for="amount"><?= lang('dinero_recibido') ?></label>
                            <input type="number" id="amount" name="amount"
                                   placeholder="0.00" inputmode="decimal" min="0" step="any" autocomplete="off">
                        </div>

                        <div class="pay-quick-row" id="payQuickAmounts">
                            <button type="button" class="pay-quick-btn exact" id="payExact">
                                <?= pos_ti($pos_ti['equal'], 13) ?><?= lang('exacto'); ?>
                            </button>
                            <button type="button" class="pay-quick-btn" data-amount="5000">5.000</button>
                            <button type="button" class="pay-quick-btn" data-amount="10000">10.000</button>
                            <button type="button" class="pay-quick-btn" data-amount="20000">20.000</button>
                        </div>

                        <!-- Teclado en pantalla: imprescindible en tableta, y en
                             una caja con teclado fisico no estorba porque el foco
                             sigue en el campo. -->
                        <div class="pay-keypad" id="payKeypad">
                            <button type="button" data-key="7">7</button>
                            <button type="button" data-key="8">8</button>
                            <button type="button" data-key="9">9</button>
                            <button type="button" data-key="back" class="k-act" title="<?= lang('borrar'); ?>">⌫</button>
                            <button type="button" data-key="4">4</button>
                            <button type="button" data-key="5">5</button>
                            <button type="button" data-key="6">6</button>
                            <button type="button" data-key="clear" class="k-act k-clear" title="C">C</button>
                            <button type="button" data-key="1">1</button>
                            <button type="button" data-key="2">2</button>
                            <button type="button" data-key="3">3</button>
                            <button type="button" data-key="0">0</button>
                            <button type="button" data-key=".">.</button>
                            <button type="button" data-key="00">00</button>
                        </div>

                        <div class="pay-totals">
                            <div class="pay-total-line">
                                <span><?= lang('total_payable') ?></span>
                                <strong id="twt">₡0.00</strong>
                            </div>
                            <div class="pay-total-line">
                                <span><?= lang('pagado') ?></span>
                                <strong id="payPagado">₡0.00</strong>
                            </div>
                            <div class="pay-total-line change">
                                <span id="balanceLabel"><?= lang('change') ?></span>
                                <strong id="balance">₡0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer pay-footer">
                <button type="button" class="pay-close-btn" data-bs-dismiss="modal">
                    <?= pos_ti($pos_ti['x'], 14) ?><?= lang('close') ?>
                </button>
                <button type="button" class="pay-submit-btn" id="submit-sale">
                    <?= pos_ti($pos_ti['check'], 15) ?><?= lang('submit') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL: Editar línea del carrito
════════════════════════════════════════════════ -->
<div class="nx-ov" id="editItemModal">
    <div class="nx-ov-caja" role="dialog" aria-modal="true" aria-labelledby="ei-titulo">
        <div class="nx-ov-cab">
            <div class="nx-ov-tit" id="ei-titulo"><?= lang('editar_linea'); ?></div>
            <button type="button" class="nx-ov-x" id="ei-cerrar" aria-label="<?= lang('close'); ?>">&times;</button>
        </div>
        <div class="nx-ov-cuerpo">
            <input type="hidden" id="ei-item">

            <label class="nx-ov-lbl" for="ei-nombre"><?= lang('product_name'); ?></label>
            <input type="text" class="nx-ov-input" id="ei-nombre">

            <div class="nx-ov-fila2">
                <div>
                    <label class="nx-ov-lbl" for="ei-cantidad"><?= lang('quantity'); ?></label>
                    <input type="number" class="nx-ov-input" id="ei-cantidad" min="0.01" step="any">
                </div>
                <div>
                    <label class="nx-ov-lbl" for="ei-precio"><?= lang('price'); ?></label>
                    <input type="number" class="nx-ov-input" id="ei-precio" min="0" step="any">
                </div>
            </div>

            <label class="nx-ov-lbl" for="ei-descuento"><?= lang('descuento_linea'); ?></label>
            <div class="nx-ov-desc">
                <input type="number" class="nx-ov-input" id="ei-descuento" min="0" step="any"
                       placeholder="0" autocomplete="off">
                <div class="nx-ov-desc-tipo" id="ei-desc-tipo">
                    <button type="button" data-tipo="monto" class="activo"><?= $Settings->symbol ? html_escape($Settings->symbol) : '&#8353;' ?></button>
                    <button type="button" data-tipo="pct">%</button>
                </div>
            </div>
            <div class="nx-ov-ayuda" id="ei-desc-ayuda"></div>

            <label class="nx-ov-lbl" for="ei-comentario"><?= lang('comment'); ?></label>
            <input type="text" class="nx-ov-input" id="ei-comentario">

            <div class="nx-ov-stock" id="ei-stock"></div>
            <div class="nx-ov-error" id="ei-error"></div>
        </div>
        <div class="nx-ov-pie">
            <button type="button" class="nx-ov-btn danger" id="ei-quitar">
                <i class="fa fa-trash-o"></i> <?= lang('remove'); ?>
            </button>
            <span style="flex:1;"></span>
            <button type="button" class="nx-ov-btn" id="ei-cancelar"><?= lang('close'); ?></button>
            <button type="button" class="nx-ov-btn primary" id="ei-guardar"><?= lang('save'); ?></button>
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
                <!-- Articulos guardados: un clic llena nombre, CABYS e IVA -->
                <div class="mb-3" id="ah-rapidos-wrap" hidden>
                    <label class="form-label fw-semibold d-block"><?= lang('articulos_rapidos'); ?></label>
                    <div id="ah-rapidos" class="ah-rapidos"></div>
                </div>

                <!-- Nombre -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= lang('name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ah-name" placeholder="<?= lang('desc_prod_servicio'); ?>" autocomplete="off" required>
                </div>

                <!-- CABYS: se elige siempre desde el modal de búsqueda, nunca a mano -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= lang('codigo_cabys'); ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace" id="ah-cabys" placeholder="—" readonly style="max-width:170px;">
                        <input type="text" class="form-control" id="ah-cabys-desc" placeholder="<?= lang('desc_cabys'); ?>" readonly>
                        <button type="button" class="btn btn-info d-flex align-items-center gap-1" id="ah-cabys-search-btn">
                            <span id="ah-cabys-icon"><?= pos_ti($pos_ti['search'], 13) ?></span> <?= lang('buscar_cabys'); ?>
                        </button>
                    </div>
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

                <div class="form-text mb-3" id="ah-iva-ayuda"><?= lang('iva_sale_del_cabys'); ?></div>

                <?php if ($Admin) { ?>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="ah-guardar">
                    <label class="form-check-label" for="ah-guardar"><?= lang('guardar_articulo_rapido'); ?></label>
                </div>
                <?php } ?>

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
                <div class="input-group mb-2">
                    <input type="text" class="form-control" id="cabys-q" placeholder="<?= lang('buscar_cabys_placeholder'); ?>" autocomplete="off">
                    <button type="button" class="btn btn-primary" id="cabys-go-btn">
                        <?= pos_ti($pos_ti['search'], 15) ?>
                    </button>
                </div>
                <div class="text-muted small mb-3"><?= lang('cabys_help_text'); ?></div>
                <div id="cabys-results-list" class="cabys-results-list"></div>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL: Descuento sobre el total de la factura
════════════════════════════════════════════════ -->
<div class="modal fade" id="descuentoModal" tabindex="-1">
    <div class="modal-dialog nx-ds-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2" style="white-space:nowrap;">
                    <span style="color:var(--nx-warn);"><?= pos_ti($pos_ti['tag'], 16) ?></span>
                    <?= lang('descuento_total'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold" for="ds-valor"><?= lang('monto_o_porcentaje'); ?></label>
                <div class="nx-ov-desc">
                    <input type="number" class="form-control" id="ds-valor" min="0" step="any" placeholder="0" autocomplete="off">
                    <div class="nx-ov-desc-tipo" id="ds-tipo">
                        <button type="button" data-tipo="monto" class="activo"><?= $Settings->symbol ? html_escape($Settings->symbol) : '&#8353;' ?></button>
                        <button type="button" data-tipo="pct">%</button>
                    </div>
                </div>
                <div class="nx-ov-ayuda" id="ds-ayuda"></div>
                <div class="nx-ov-error" id="ds-error"></div>
                <div class="pcp-ds-resumen" id="ds-resumen"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="ds-quitar"><?= lang('quitar_descuento'); ?></button>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-1" id="ds-aplicar">
                    <?= pos_ti($pos_ti['check'], 13) ?><?= lang('apply'); ?>
                </button>
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
            <!-- Instalador generado por el servidor: trae la direccion de ESTE POS y
                 el permiso (override.crt) ya dentro, para que la caja no tenga que
                 aceptar nada en QZ Tray despues. Ver PosPrint::qz_installer(). -->
            <a href="<?= base_url('posprint/qz_installer') ?>" download class="btn btn-primary btn-lg" style="width:100%; font-weight:700; margin:14px 0;">
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

    // Tickets que PHP dejo en cola para que esta computadora los imprima por QZ Tray.
    window._nx_qz_pendiente = <?= count((array) $this->session->userdata('qz_cola')); ?>;

    var Settings = <?= json_encode(ajustes_publicos($Settings)); ?>;
    var username = '<?= addslashes($this->session->userdata('username')); ?>';

    // Textos de los avisos del modal de pago (los usa pos-core.js)
    window.LANG_VUELTO          = '<?= addslashes(lang('change')); ?>';
    window.LANG_FALTA           = '<?= addslashes(lang('falta_por_cubrir')); ?>';
    window.LANG_PAGO_INCOMPLETO = '<?= addslashes(lang('pago_incompleto')); ?>';
    window.LANG_MAX_PAGOS       = '<?= addslashes(lang('max_formas_pago')); ?>';
    window.LANG_REF_REQ         = '<?= addslashes(lang('ref_requerida')); ?>';
    window.LANG_MONTO_REQ       = '<?= addslashes(lang('monto_requerido')); ?>';

    window.CSRF_NAME = '<?= $this->security->get_csrf_token_name(); ?>';
    window.CSRF_HASH = '<?= $this->security->get_csrf_hash(); ?>';

    window._pos_cat_id  = <?= (int)$Settings->default_category; ?>;
    window._pos_tcp     = <?= (int)$tcp; ?>;
    window._pos_sid     = <?= (int)$sid; ?>;

    // Venta retomada (en espera, reedicion, proforma, apartado): el carrito lo
    // manda el servidor, no el localStorage de esta computadora.
    <?php
    $precargada = null;
    if (!empty($items)) {
        $origen = $suspend_sale ?? $sale ?? $quotes_sale ?? $apa_sale ?? null;
        $precargada = array(
            'items'   => json_decode($items, TRUE),
            'cliente' => $origen && isset($origen->customer_id) ? (string) $origen->customer_id : '',
            'nota'    => $origen && isset($origen->note) ? $origen->note : '',
        );
    }
    ?>
    window._pos_precargada = <?= $precargada ? json_encode($precargada) : 'null'; ?>;

    <?php
    // El POS no dibuja la barra de mensajes del tema, asi que lo que el
    // servidor deje en flashdata se muestra aca como aviso flotante.
    $aviso_pos = $this->session->flashdata('error') ?: $this->session->flashdata('message');
    ?>
    window._pos_aviso = <?= $aviso_pos
        ? json_encode(array('texto' => strip_tags((string) $aviso_pos),
                            'error' => (bool) $this->session->flashdata('error')))
        : 'null'; ?>;

    <?php $venta_ok = $this->session->flashdata('venta_ok'); ?>
    // Marca que deja Pos.php al cobrar: el POS se encarga del aviso, de limpiar
    // el carrito y de la impresion automatica, para no abrir el comprobante.
    window._pos_venta_ok = <?= $venta_ok ? json_encode(array(
        'id'        => (int) $venta_ok['id'],
        'efectivo'  => (bool) $venta_ok['efectivo'],
        'autoprint' => (bool) $Settings->auto_print,
        'url_bytes' => site_url('posprint/receipt_bytes'),
        'url_cajon' => site_url('posprint/drawer_bytes'),
    )) : 'null'; ?>;
    window._impuestos   = <?= json_encode($impuestos_list ?: []); ?>;
    // Validacion previa al cobro: lo que Hacienda rechazaria se detiene en la caja.
    window._posVR = <?= json_encode(array(
        'fe'      => !empty($Settings->fe),
        'admin'   => (bool) $Admin,
        'lista'   => site_url('ventarapida/lista'),
        'guardar' => site_url('ventarapida/guardar'),
        'borrar'  => site_url('ventarapida/borrar'),
        'verificar' => site_url('ventarapida/verificar_cabys'),
        'asignar' => site_url('ventarapida/asignar_cabys'),
        'buscar'  => site_url('hacienda_proxy/cabys'),
    )); ?>;
    window._urlEstadoCliente = '<?= site_url('pos/estado_cliente'); ?>';
    window._customers   = <?php
        $cmap = [];
        if ($customers) foreach ($customers as $c)
            $cmap[$c->id] = ['name'=>$c->name,'cf1'=>$c->cf1,'cf2'=>$c->cf2,'email'=>$c->email,'phone'=>$c->phone,'company'=>isset($c->business_name)?$c->business_name:'','credito'=>isset($c->limitcredit)?(float)$c->limitcredit:0];
        echo json_encode($cmap);
    ?>;

    var lang = {
        vr_falta_cabys: <?= json_encode(lang('vr_falta_cabys')); ?>,
        vr_cabys_inexistente: <?= json_encode(lang('vr_cabys_inexistente')); ?>,
        vr_cabys_ayuda: <?= json_encode(lang('vr_cabys_ayuda')); ?>,
        vr_no_agregar: <?= json_encode(lang('vr_no_agregar')); ?>,
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
        sin_inventario:           '<?= addslashes(lang('sin_inventario')); ?>',
        business_name:            '<?= addslashes(lang('business_name')); ?>',
        credit_limit:             '<?= addslashes(lang('credit_limit')); ?>',
        email:                    '<?= addslashes(lang('email')); ?>',
        phone:                    '<?= addslashes(lang('phone')); ?>',
        cliente_contado:          '<?= addslashes(lang('cliente_contado')); ?>',
        quantity_low:             '<?= addslashes(lang('quantity_low')); ?>',
        available:                '<?= addslashes(lang('available')); ?>',
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
        atajos_hint:              '<?= addslashes(lang('atajos_hint')); ?>',
        no_se_pudo_cargar:        '<?= addslashes(lang('no_se_pudo_cargar')); ?>',
        credito_excede_limite:    '<?= addslashes(lang('credito_excede_limite')); ?>',
        credito_faltante:         '<?= addslashes(lang('credito_faltante')); ?>',
        atajo_agregar_item:       '<?= addslashes(lang('atajo_agregar_item')); ?>',
        atajo_editar_ultimo:      '<?= addslashes(lang('atajo_editar_ultimo')); ?>',
        atajo_agregar_cliente:    '<?= addslashes(lang('atajo_agregar_cliente')); ?>',
        atajo_alternar_cats:      '<?= addslashes(lang('atajo_alternar_cats')); ?>',
        atajo_cancelar_venta:     '<?= addslashes(lang('atajo_cancelar_venta')); ?>',
        atajo_suspender_venta:    '<?= addslashes(lang('atajo_suspender_venta')); ?>',
        atajo_finalizar_venta:    '<?= addslashes(lang('atajo_finalizar_venta')); ?>',
        atajo_ventas_hoy:         '<?= addslashes(lang('atajo_ventas_hoy')); ?>',
        atajo_retomar:            '<?= addslashes(lang('atajo_retomar')); ?>',
        atajo_cerrar_caja:        '<?= addslashes(lang('atajo_cerrar_caja')); ?>',
        cliente_sin_coincidencias: '<?= addslashes(lang('cliente_sin_coincidencias')); ?>',
        producto_sin_coincidencias:'<?= addslashes(lang('producto_sin_coincidencias')); ?>',
        indique_referencia:       '<?= addslashes(lang('indique_referencia')); ?>',
        ubicacion:                '<?= addslashes(lang('ubicacion')); ?>',
        referencia_requerida:     '<?= addslashes(lang('referencia_requerida')); ?>'
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
