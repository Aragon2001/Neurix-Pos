<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

</div>

<!-- Footer (AdminLTE 4 + Bootstrap 5) -->
<footer class="main-footer bg-body border-top border-secondary mt-4">
    <div class="float-end d-none d-sm-inline" style="color:var(--nx-a1);font-size:12px;">
        <span style="opacity:.5;">v</span><strong style="color:var(--nx-a1);"><?= $Settings->version; ?></strong>
        &nbsp;&middot;&nbsp;<span style="opacity:.5;">ARASOFT SOLUTIONS</span>
    </div>
    <span style="color:var(--nx-txt3);">Copyright &copy; <?= date('Y'); ?></span>
    <strong style="background:linear-gradient(90deg,var(--nx-a1),var(--nx-a2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;margin:0 4px;"><?= $Settings->site_name; ?></strong>
</footer>

</div>
</div>
<!-- Modales de aplicación -->
<div class="modal fade" id="posModal" tabindex="-1" aria-hidden="true"></div>
<div class="modal fade" id="myModal" tabindex="-1" aria-hidden="true"></div>
<div id="ajaxCall" class="position-fixed top-50 start-50 translate-middle d-none">
    <i class="fa fa-spinner fa-pulse fa-2x text-primary"></i>
</div>

<!-- Configuración global y lenguaje -->
<script>
window._appConfig = {
    base_url: '<?=base_url();?>',
    site_url: '<?=site_url();?>',
    dateformat: '<?=$Settings->dateformat;?>',
    timeformat: '<?= $Settings->timeformat ?>',
    module: '<?=$m;?>',
    view: '<?=$v;?>'
};
<?php unset($Settings->protocol, $Settings->smtp_host, $Settings->smtp_user, $Settings->smtp_pass, $Settings->smtp_port, $Settings->smtp_crypto, $Settings->mailpath, $Settings->timezone, $Settings->setting_id, $Settings->default_email, $Settings->version, $Settings->stripe, $Settings->stripe_secret_key, $Settings->stripe_publishable_key); ?>
window._appSettings = <?= json_encode($Settings); ?>;
window._appLang = {
    code_error: '<?= lang('code_error'); ?>',
    r_u_sure: '<?= lang('r_u_sure'); ?>',
    register_open_alert: '<?= lang('register_open_alert'); ?>',
    no_match_found: '<?= lang('no_match_found'); ?>',
    invalid_mail: '<?= lang('invalid_mail'); ?>',
    invalid_phone: '<?= lang('invalid_phone'); ?>'
};
</script>
<style>
.swal-nx-flash { min-width: 360px !important; font-size: 15px !important; }
.swal2-popup.swal-nx-flash .swal2-title { font-size: 1.2em !important; }
</style>

<script>
/* ── Inicializar menú activo (vanilla JS) ── */
document.addEventListener('DOMContentLoaded', () => {
    const m = window._appConfig.module;
    const v = window._appConfig.view;
    if (m) document.querySelector('.mm_' + m)?.classList.add('active');
    if (v && m) document.getElementById(m + '_' + v)?.classList.add('active');
});

/* ── SweetAlert2 shims & helpers (reemplaza jQuery) ── */
window.alert = function(msg) {
    Swal.fire({ icon: 'warning', text: String(msg), confirmButtonColor: '#0369a1' });
};

window.bootbox = {
    alert: function(msg, cb) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: String(msg), showConfirmButton: false, timer: 3500, timerProgressBar: true })
            .then(() => { if (cb) cb(); });
    },
    confirm: function(msg, cb) {
        Swal.fire({ title: String(msg), icon: 'question', showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'Cancelar', confirmButtonColor: '#0369a1', cancelButtonColor: '#6b7280' })
            .then(r => { if (cb) cb(r.isConfirmed); });
    }
};

/* ── Delegación para data-confirm (reemplaza jQuery) ── */
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[data-confirm]');
    if (!link) return;
    e.preventDefault();
    const msg = link.dataset.confirm || '¿Está seguro?';
    Swal.fire({
        title: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then(r => {
        if (r.isConfirmed) window.location.href = link.href;
    });
});

/* ── Procesar alertas flash ── */
document.addEventListener('DOMContentLoaded', () => {
    if (window._nxAlerts && window._nxAlerts.length) {
        const queue = [...window._nxAlerts];
        function showNext() {
            if (!queue.length) return;
            const cfg = queue.shift();
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
</body>
</html>
