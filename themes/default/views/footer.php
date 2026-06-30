<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

</div>
<footer class="main-footer" id="impFoot">
    <div class="pull-right hidden-xs" style="color:var(--nx-a1);font-size:12px;">
        <span style="opacity:.5;">v</span><strong style="color:var(--nx-a1);"><?= $Settings->version; ?></strong>
        &nbsp;&middot;&nbsp;<span style="opacity:.5;">ARASOFT SOLUTIONS</span>
    </div>
    <span style="color:var(--nx-txt3);">Copyright &copy; <?= date('Y'); ?></span>
    <strong style="background:linear-gradient(90deg,var(--nx-a1),var(--nx-a2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;margin:0 4px;"><?= $Settings->site_name; ?></strong>
</footer>
</div>
<div class="modal" data-easein="flipYIn" id="posModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"></div>
<div class="modal" data-easein="flipYIn" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
<div id="ajaxCall"><i class="fa fa-spinner fa-pulse"></i></div>
<script type="text/javascript">
    var base_url = '<?=base_url();?>';
    var site_url = '<?=site_url();?>';
    var dateformat = '<?=$Settings->dateformat;?>', timeformat = '<?= $Settings->timeformat ?>';
    <?php unset($Settings->protocol, $Settings->smtp_host, $Settings->smtp_user, $Settings->smtp_pass, $Settings->smtp_port, $Settings->smtp_crypto, $Settings->mailpath, $Settings->timezone, $Settings->setting_id, $Settings->default_email, $Settings->version, $Settings->stripe, $Settings->stripe_secret_key, $Settings->stripe_publishable_key); ?>
    var Settings = <?= json_encode($Settings); ?>;
    $(window).load(function () {
        $('.mm_<?=$m?>').addClass('active');
        $('#<?=$m?>_<?=$v?>').addClass('active');
    });
    var lang = new Array();
    lang['code_error'] = '<?= lang('code_error'); ?>';
    lang['r_u_sure'] = '<?= lang('r_u_sure'); ?>';
    lang['register_open_alert'] = '<?= lang('register_open_alert'); ?>';
    lang['code_error'] = '<?= lang('code_error'); ?>';
    lang['r_u_sure'] = '<?= lang('r_u_sure'); ?>';
    lang['no_match_found'] = '<?= lang('no_match_found'); ?>';
    lang['invalid_mail'] = '<?= lang('invalid_mail'); ?>';
    lang['invalid_phone'] = '<?= lang('invalid_phone'); ?>';
</script>

<script src="<?= $assets ?>dist/js/libraries.min.js" type="text/javascript"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<style>
.swal-nx-flash { min-width: 360px !important; font-size: 15px !important; }
.swal2-popup.swal-nx-flash .swal2-title { font-size: 1.2em !important; }
</style>
<script>
/* ── SweetAlert2 shims & helpers ── */

// Read brand colors from CSS variables so Swal buttons respect the active theme
var _cv = function(v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim() || undefined; };

// Redirige alert() nativo a Swal
window.alert = function(msg) {
    Swal.fire({ icon: 'warning', text: String(msg), confirmButtonColor: _cv('--primary') });
};

// Shim bootbox → SweetAlert2 (para código compilado en scripts.min.js)
var bootbox = {
    alert: function(msg, cb) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: String(msg), showConfirmButton: false, timer: 3500, timerProgressBar: true })
            .then(function() { if (cb) cb(); });
    },
    confirm: function(msg, cb) {
        Swal.fire({ title: String(msg), icon: 'question', showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'Cancelar', confirmButtonColor: _cv('--primary'), cancelButtonColor: _cv('--nx-txt3') })
            .then(function(r) { if (cb) cb(r.isConfirmed); });
    }
};

// Delegación global para enlaces con data-confirm (DataTables + cualquier botón)
$(document).on('click', 'a[data-confirm]', function(e) {
    e.preventDefault();
    var href = $(this).attr('href'), msg = $(this).data('confirm');
    Swal.fire({
        title: msg || '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: _cv('--danger'),
        cancelButtonColor: _cv('--nx-txt3'),
        reverseButtons: true
    }).then(function(r) { if (r.isConfirmed) window.location.href = href; });
});

// Procesa cola de alertas flash generada por header.php
$(function() {
    if (window._nxAlerts && window._nxAlerts.length) {
        var queue = window._nxAlerts.slice();
        function showNext() {
            if (!queue.length) return;
            var cfg = queue.shift();
            Swal.fire({
                icon: cfg.icon,
                title: cfg.title,
                timer: 4000,
                timerProgressBar: true,
                showConfirmButton: false,
                position: 'top',
                customClass: { popup: 'swal-nx-flash' }
            }).then(showNext);
        }
        showNext();
    }
});
</script>

<script src="<?= $assets ?>dist/js/scripts.min.js" type="text/javascript"></script>

<script>
/* ── Theme toggle ── */
function nxToggleTheme() {
    var html = document.documentElement;
    var current = html.getAttribute('data-theme') || 'dark';
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('nx-theme', next);
    nxUpdateThemeBtn(next);
}
function nxUpdateThemeBtn(theme) {
    var lbl = document.getElementById('nxThemeLabel');
    var btn = document.getElementById('nxThemeToggle');
    if (!lbl || !btn) return;
    if (theme === 'light') {
        lbl.textContent = 'Oscuro';
        btn.querySelector('i').className = 'fa fa-moon-o';
    } else {
        lbl.textContent = 'Claro';
        btn.querySelector('i').className = 'fa fa-sun-o';
    }
}
(function(){
    var t = document.documentElement.getAttribute('data-theme') || 'dark';
    nxUpdateThemeBtn(t);
})();
</script>
</body>
</html>
