<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title . ' | ' . $Settings->site_name; ?></title>
    <link rel="shortcut icon" href="<?= $assets ?>images/icon.png"/>
    <?php if ($this->db->dbdriver != 'sqlite3') { ?>
    <script>if (parent.frames.length !== 0) { top.location = '<?= site_url('login') ?>'; }</script>
    <?php } ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <link href="<?= $assets ?>dist/css/styles.css" rel="stylesheet">
    <?= $Settings->rtl ? '<link href="' . $assets . 'dist/css/rtl.css" rel="stylesheet">' : ''; ?>
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --a1: #38bdf8;
        --a2: #818cf8;
        --a3: #22d3ee;
        --bg: #05091a;
        --txt1: #f1f5f9;
        --txt2: #94a3b8;
        --border: rgba(56,189,248,.18);
        --inp-bg: rgba(56,189,248,.05);
        --inp-focus: rgba(56,189,248,.12);
        --glow: rgba(56,189,248,.25);
    }

    html, body { height: 100%; }

    body.nlx-body {
        margin: 0;
        background: var(--bg);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        display: flex; align-items: center; justify-content: center;
        min-height: 100vh; overflow: hidden; padding: 20px;
    }

    /* ── Canvas ── */
    #nlx-cv {
        position: fixed; inset: 0;
        z-index: 0; pointer-events: none;
    }

    /* ── Ambient blobs ── */
    .nlx-blob {
        position: fixed; border-radius: 50%;
        filter: blur(110px); opacity: .1;
        pointer-events: none; z-index: 0;
    }
    .nlx-blob-1 { width:650px; height:650px; background:#0ea5e9; top:-220px; left:-180px;
        animation: bfloat 9s ease-in-out infinite; }
    .nlx-blob-2 { width:500px; height:500px; background:#7c3aed; bottom:-150px; right:-120px;
        animation: bfloat 11s ease-in-out infinite reverse; }
    .nlx-blob-3 { width:380px; height:380px; background:#0891b2; top:45%; left:42%;
        animation: bfloat 7s ease-in-out infinite 2s; }
    @keyframes bfloat {
        0%,100% { transform:translate(0,0) scale(1); }
        40%      { transform:translate(25px,-18px) scale(1.06); }
        70%      { transform:translate(-15px,12px) scale(.96); }
    }

    /* ── Card wrapper ── */
    .nlx-outer {
        position: relative; z-index: 1;
        width: 100%; max-width: 940px;
    }
    /* Animated gradient border */
    .nlx-outer::before {
        content: '';
        position: absolute; inset: -1.5px;
        border-radius: 26px;
        background: linear-gradient(135deg, var(--a1), var(--a2), var(--a3), var(--a2), var(--a1));
        background-size: 300% 300%;
        animation: bgrad 6s ease infinite;
        z-index: -1; opacity: .75;
    }
    @keyframes bgrad {
        0%  { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100%{ background-position: 0% 50%; }
    }

    .nlx-card {
        display: flex; border-radius: 24px; overflow: hidden;
        box-shadow:
            0 0 0 1px rgba(56,189,248,.08),
            0 30px 90px -10px rgba(0,0,0,.75),
            0 0 80px -25px rgba(56,189,248,.18);
    }

    /* ════════════ LEFT BRAND PANEL ════════════ */
    .nlx-left {
        width: 41%;
        background: linear-gradient(155deg, #0d1528 0%, #111827 45%, #160e30 100%);
        padding: 50px 38px;
        display: flex; flex-direction: column; justify-content: space-between;
        position: relative; overflow: hidden; flex-shrink: 0;
    }
    /* Dot-grid */
    .nlx-left::before {
        content: '';
        position: absolute; inset: 0;
        background-image:
            radial-gradient(circle, rgba(56,189,248,.12) 1px, transparent 1px);
        background-size: 28px 28px;
        pointer-events: none;
    }
    /* Bottom-right decorative rings */
    .nlx-left::after {
        content: '';
        position: absolute; bottom:-80px; right:-80px;
        width: 320px; height: 320px; border-radius: 50%;
        border: 1px solid rgba(56,189,248,.07);
        box-shadow:
            0 0 0 50px rgba(56,189,248,.04),
            0 0 0 100px rgba(56,189,248,.022),
            0 0 0 160px rgba(56,189,248,.01);
        pointer-events: none;
    }

    .nlx-brand-top { position: relative; z-index: 1; }

    /* Logo icon */
    .nlx-logo-box {
        width: 74px; height: 74px; border-radius: 20px;
        background: linear-gradient(135deg, #0369a1 0%, #0ea5e9 60%, #38bdf8 100%);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 30px;
        box-shadow: 0 8px 36px rgba(56,189,248,.4), 0 2px 8px rgba(0,0,0,.35);
        position: relative; overflow: hidden;
    }
    .nlx-logo-box::after {
        content: '';
        position: absolute; top:-60%; left:-60%; width: 55%; height: 220%;
        background: rgba(255,255,255,.18); transform: rotate(25deg);
        animation: lshine 4s ease-in-out infinite;
    }
    @keyframes lshine {
        0%,100% { left:-60%; opacity:0; }
        45%     { opacity:1; }
        65%     { left:160%; opacity:0; }
    }
    .nlx-logo-box img { width:44px; height:44px; object-fit:contain; }
    .nlx-logo-letter  { color:#fff; font-size:34px; font-weight:800; line-height:1; }

    .nlx-bname {
        font-size: 27px; font-weight: 800; letter-spacing: -.4px; line-height: 1.1;
        background: linear-gradient(130deg, #f1f5f9 0%, var(--a1) 55%, var(--a2) 100%);
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
        margin-bottom: 8px;
    }
    .nlx-btag {
        font-size: 11px; color: var(--a1); letter-spacing: 2px;
        text-transform: uppercase; font-weight: 600; opacity: .75;
        margin-bottom: 38px;
    }

    .nlx-feats { display:flex; flex-direction:column; gap:16px; }
    .nlx-feat {
        display:flex; align-items:center; gap:13px;
        color: #cbd5e1; font-size: 13.5px;
    }
    .nlx-ficon {
        width:34px; height:34px; border-radius:9px; flex-shrink:0;
        background: rgba(56,189,248,.08);
        border: 1px solid rgba(56,189,248,.18);
        display:flex; align-items:center; justify-content:center;
    }
    .nlx-ficon svg { width:15px; height:15px; stroke: var(--a1); fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }

    .nlx-brand-bot { position:relative; z-index:1; }
    .nlx-bver { font-size:11px; color:rgba(148,163,184,.4); letter-spacing:1px; }

    /* Horizontal scan line animation */
    .nlx-scan {
        position: absolute; left:0; right:0; height:1px;
        background: linear-gradient(90deg, transparent, rgba(56,189,248,.4), transparent);
        animation: scanline 5s ease-in-out infinite;
        pointer-events: none; z-index: 2;
    }
    @keyframes scanline {
        0%   { top: 0%;   opacity:0; }
        10%  { opacity:.8; }
        90%  { opacity:.8; }
        100% { top: 100%; opacity:0; }
    }

    /* ════════════ RIGHT FORM PANEL ════════════ */
    .nlx-right {
        flex: 1;
        background: rgba(8,14,28,.97);
        padding: 50px 50px;
        display: flex; flex-direction: column; justify-content: center;
    }

    .nlx-fhdr { margin-bottom: 30px; }

    .nlx-online {
        display:flex; align-items:center; gap:8px;
        margin-bottom: 16px;
    }
    .nlx-dot {
        width: 7px; height: 7px; border-radius:50%;
        background: #22c55e; box-shadow: 0 0 8px #22c55e;
        animation: dpulse 2.2s ease-in-out infinite;
    }
    @keyframes dpulse {
        0%,100% { opacity:1; box-shadow:0 0 8px #22c55e; }
        50%      { opacity:.45; box-shadow:0 0 3px #22c55e; }
    }
    .nlx-online-txt {
        font-size:11px; color:#22c55e; letter-spacing:1.8px;
        text-transform:uppercase; font-weight:600;
    }

    .nlx-ftitle {
        font-size: 26px; font-weight: 700;
        color: var(--txt1); letter-spacing: -.3px;
        margin-bottom: 6px;
    }
    .nlx-fsub { font-size:14px; color:var(--txt2); }

    /* Alerts */
    .nlx-alert {
        border-radius: 11px; padding: 11px 14px;
        font-size: 13px; margin-bottom: 18px; border: none;
    }

    /* Fields */
    .nlx-field { margin-bottom: 20px; }
    .nlx-lbl {
        display:block; font-size:11.5px; font-weight:600;
        color:var(--txt2); margin-bottom:7px;
        letter-spacing:.8px; text-transform:uppercase;
    }
    .nlx-iwrap { position:relative; }
    .nlx-iico {
        position:absolute; left:15px; top:50%; transform:translateY(-50%);
        color:var(--txt2); pointer-events:none;
        transition: color .2s;
    }
    .nlx-iico svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }

    .nlx-inp {
        width:100%; padding:13px 44px; border-radius:12px;
        border: 1px solid var(--border);
        background: var(--inp-bg);
        color: var(--txt1); font-size:14.5px;
        outline:none; transition: all .25s;
        font-family: inherit;
    }
    .nlx-inp::placeholder { color:#3f5068; }
    .nlx-inp:focus {
        border-color: var(--a1);
        background: var(--inp-focus);
        box-shadow: 0 0 0 3px rgba(56,189,248,.14), 0 0 24px rgba(56,189,248,.07);
    }
    .nlx-iwrap:focus-within .nlx-iico { color: var(--a1); }

    .nlx-eye {
        position:absolute; right:13px; top:50%; transform:translateY(-50%);
        background:none; border:none; color:var(--txt2);
        cursor:pointer; padding:5px; line-height:1;
        transition: color .2s;
    }
    .nlx-eye:hover { color:var(--a1); }
    .nlx-eye svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }

    .nlx-row {
        display:flex; justify-content:flex-end;
        margin:-6px 0 22px;
    }
    .nlx-fgot {
        font-size:12.5px; color:var(--a1); opacity:.8;
        cursor:pointer; background:none; border:none;
        text-decoration:none; font-family:inherit;
        transition: opacity .2s, color .2s;
    }
    .nlx-fgot:hover { opacity:1; color:#fff; text-decoration:none; }

    /* ── Submit Button ── */
    .nlx-btn {
        width:100%; padding:14px; border:none; border-radius:13px;
        background: linear-gradient(115deg, #0369a1, #0ea5e9 50%, #38bdf8);
        background-size: 200% 100%;
        color:#fff; font-size:15px; font-weight:700; letter-spacing:.3px;
        cursor:pointer; font-family:inherit;
        display:flex; align-items:center; justify-content:center; gap:11px;
        box-shadow: 0 4px 22px rgba(14,165,233,.38);
        position:relative; overflow:hidden;
        transition: transform .25s, box-shadow .25s, background-position .4s;
    }
    .nlx-btn::after {
        content:'';
        position:absolute; top:-50%; left:-80%; width:55%; height:200%;
        background:rgba(255,255,255,.13); transform:skewX(-18deg);
        animation:bshine 3.5s ease-in-out infinite;
    }
    @keyframes bshine {
        0%,100% { left:-80%; opacity:0; }
        40%      { opacity:1; }
        65%      { left:160%; opacity:0; }
    }
    .nlx-btn:hover {
        background-position: right center;
        transform:translateY(-2px);
        box-shadow: 0 10px 34px rgba(14,165,233,.52);
    }
    .nlx-btn:active { transform:translateY(0); }
    .nlx-btn svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:2.5; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }

    /* Footer */
    .nlx-foot {
        text-align:center; color:#1e2d42;
        font-size:11px; margin-top:26px; letter-spacing:.5px;
    }

    /* ── Responsive ── */
    @media (max-width:720px) {
        .nlx-left { display:none; }
        .nlx-right { padding:38px 28px; border-radius:22px; }
        .nlx-outer::before { border-radius:24px; }
        .nlx-card { border-radius:22px; }
    }
    @media (max-width:420px) {
        .nlx-right { padding:30px 22px; }
    }
    </style>
</head>
<body class="nlx-body login-page login-page-<?= $Settings->theme_style; ?>">

<canvas id="nlx-cv"></canvas>
<div class="nlx-blob nlx-blob-1"></div>
<div class="nlx-blob nlx-blob-2"></div>
<div class="nlx-blob nlx-blob-3"></div>

<div class="nlx-outer">
    <div class="nlx-card">

        <!-- ══ LEFT BRAND PANEL ══ -->
        <div class="nlx-left">
            <div class="nlx-scan"></div>

            <div class="nlx-brand-top">
                <div class="nlx-logo-box">
                    <?php if ($Settings->theme_style === 'purple'): ?>
                        <img src="<?= $assets ?>dist/css/base/logo1.png" alt="<?= $Settings->site_name; ?>">
                    <?php elseif (!empty($Settings->logo)): ?>
                        <img src="<?= base_url('uploads/' . $Settings->logo); ?>" alt="<?= $Settings->site_name; ?>">
                    <?php else: ?>
                        <span class="nlx-logo-letter"><?= mb_strtoupper(mb_substr($Settings->site_name, 0, 1)); ?></span>
                    <?php endif; ?>
                </div>

                <div class="nlx-bname"><?= $Settings->site_name; ?></div>
                <div class="nlx-btag">Facturación Electrónica CR</div>

                <div class="nlx-feats">
                    <div class="nlx-feat">
                        <div class="nlx-ficon">
                            <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        </div>
                        <span>Certificado Hacienda v4.4</span>
                    </div>
                    <div class="nlx-feat">
                        <div class="nlx-ficon">
                            <svg viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <span>Acceso Seguro con Cifrado</span>
                    </div>
                    <div class="nlx-feat">
                        <div class="nlx-ficon">
                            <svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <span>Reportes en Tiempo Real</span>
                    </div>
                    <div class="nlx-feat">
                        <div class="nlx-ficon">
                            <svg viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <span>Tiquetes y Facturas Electrónicas</span>
                    </div>
                </div>
            </div>

            <div class="nlx-brand-bot">
                <div class="nlx-bver">ARASOFT SOLUTIONS &middot; v4.4</div>
            </div>
        </div>

        <!-- ══ RIGHT FORM PANEL ══ -->
        <div class="nlx-right">
            <div class="nlx-fhdr">
                <div class="nlx-online">
                    <div class="nlx-dot"></div>
                    <span class="nlx-online-txt">Sistema en línea</span>
                </div>
                <div class="nlx-ftitle"><?= lang('login_to_your_account'); ?></div>
                <div class="nlx-fsub">Ingrese sus credenciales para continuar</div>
            </div>

            <?php if ($error): ?>
            <div class="nlx-alert alert alert-danger alert-dismissable">
                <button data-dismiss="alert" class="close" type="button">&times;</button>
                <?= $error; ?>
            </div>
            <?php endif; if ($message): ?>
            <div class="nlx-alert alert alert-success alert-dismissable">
                <button data-dismiss="alert" class="close" type="button">&times;</button>
                <?= $message; ?>
            </div>
            <?php endif; ?>

            <?= form_open("auth/login"); ?>

            <div class="nlx-field">
                <label class="nlx-lbl"><?= lang('email'); ?></label>
                <div class="nlx-iwrap">
                    <span class="nlx-iico">
                        <svg viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <input class="nlx-inp" type="text" name="identity" id="identity"
                        value="<?= set_value('identity', DEMO ? 'arasoftsolutions@outlook.com' : ''); ?>"
                        placeholder="correo@empresa.com"
                        autocomplete="username">
                </div>
            </div>

            <div class="nlx-field">
                <label class="nlx-lbl"><?= lang('password'); ?></label>
                <div class="nlx-iwrap">
                    <span class="nlx-iico">
                        <svg viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </span>
                    <input class="nlx-inp" type="password" name="password" id="nlx-pwd"
                        value="<?= DEMO ? '12345678' : ''; ?>"
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                        autocomplete="current-password">
                    <button type="button" class="nlx-eye" id="nlx-eye" aria-label="Mostrar contraseña">
                        <svg id="nlx-eye-o" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg id="nlx-eye-c" style="display:none" viewBox="0 0 24 24"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>

            <div class="nlx-row">
                <a class="nlx-fgot" href="#" data-toggle="modal" data-target="#myModal"><?= lang('forgot_password'); ?></a>
            </div>

            <button type="submit" class="nlx-btn">
                <span><?= lang('sign_in'); ?></span>
                <svg viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>

            <?= form_close(); ?>

            <div class="nlx-foot">&copy; <?= date('Y'); ?> <?= $Settings->site_name; ?> &mdash; ARASOFT SOLUTIONS</div>
        </div>

    </div><!-- /.nlx-card -->
</div><!-- /.nlx-outer -->

<!-- Forgot Password Modal -->
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="fwdTitle">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= form_open("auth/forgot_password"); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="fwdTitle"><?= lang('forgot_password'); ?></h4>
            </div>
            <div class="modal-body">
                <p><?= lang('forgot_password_heading'); ?></p>
                <input type="email" name="forgot_email" placeholder="<?= lang('email'); ?>"
                       class="form-control" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button data-dismiss="modal" class="btn btn-default pull-left" type="button"><?= lang('close'); ?></button>
                <button class="btn btn-primary" type="submit"><?= lang('submit'); ?></button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>

<script src="<?= $assets ?>plugins/jQuery/jquery-3.7.1.min.js"></script>
<script src="<?= $assets ?>bootstrap/js/bootstrap.min.js"></script>
<script>
(function () {
    /* ── Particle network ── */
    var cv = document.getElementById('nlx-cv');
    var cx = cv.getContext('2d');
    var W, H, pts;
    var colors = ['56,189,248', '129,140,248', '34,211,238'];

    function resize() { W = cv.width = innerWidth; H = cv.height = innerHeight; }

    function Pt() { this.r(); }
    Pt.prototype.r = function () {
        this.x  = Math.random() * W;
        this.y  = Math.random() * H;
        this.vx = (Math.random() - .5) * .35;
        this.vy = (Math.random() - .5) * .35;
        this.rad = Math.random() * 1.6 + .5;
        this.a  = Math.random() * .45 + .08;
        this.col = colors[Math.floor(Math.random() * colors.length)];
    };
    Pt.prototype.u = function () {
        this.x += this.vx; this.y += this.vy;
        if (this.x < 0 || this.x > W || this.y < 0 || this.y > H) this.r();
    };

    function init() {
        var n = Math.min(Math.max(Math.floor(W * H / 16000), 45), 110);
        pts = [];
        for (var i = 0; i < n; i++) pts.push(new Pt());
    }

    function draw() {
        cx.clearRect(0, 0, W, H);
        for (var i = 0; i < pts.length; i++) {
            var p = pts[i]; p.u();
            cx.beginPath();
            cx.arc(p.x, p.y, p.rad, 0, Math.PI * 2);
            cx.fillStyle = 'rgba(' + p.col + ',' + p.a + ')';
            cx.fill();
            for (var j = i + 1; j < pts.length; j++) {
                var q = pts[j];
                var dx = p.x - q.x, dy = p.y - q.y;
                var d = Math.sqrt(dx * dx + dy * dy);
                if (d < 130) {
                    cx.beginPath();
                    cx.strokeStyle = 'rgba(' + p.col + ',' + (.07 * (1 - d / 130)) + ')';
                    cx.lineWidth = .6;
                    cx.moveTo(p.x, p.y);
                    cx.lineTo(q.x, q.y);
                    cx.stroke();
                }
            }
        }
        requestAnimationFrame(draw);
    }

    resize(); init(); draw();
    window.addEventListener('resize', function () { resize(); init(); });

    /* ── Password toggle ── */
    var eye = document.getElementById('nlx-eye');
    var pwd = document.getElementById('nlx-pwd');
    var eo  = document.getElementById('nlx-eye-o');
    var ec  = document.getElementById('nlx-eye-c');
    if (eye) {
        eye.addEventListener('click', function () {
            if (pwd.type === 'password') {
                pwd.type = 'text'; eo.style.display = 'none'; ec.style.display = '';
            } else {
                pwd.type = 'password'; eo.style.display = ''; ec.style.display = 'none';
            }
        });
    }

    /* ── Auto-focus ── */
    var id = document.getElementById('identity');
    if (id) { if (id.value) pwd && pwd.focus(); else id.focus(); }
})();
</script>
</body>
</html>
