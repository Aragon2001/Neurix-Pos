<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

        </div><!-- /.container-fluid -->
    </div><!-- /.app-content -->
</main><!-- /.app-main -->

<!-- ════════════════════════════════════════════════
     APP-FOOTER — Neurix POS Premium
════════════════════════════════════════════════ -->
<footer class="nx-app-footer">

    <!-- Brand -->
    <div class="nx-footer-brand">
        <img src="<?= $assets ?>images/icon.png" class="nx-footer-logo" alt="Neurix POS">
        <div>
            <div class="nx-footer-name">Neurix POS <span style="font-weight:400;opacity:.5;font-size:11px;">v<?= $Settings->version ?></span></div>
            <div class="nx-footer-sub">Desarrollado por Arasoft Solutions</div>
        </div>
    </div>

    <!-- Contact -->
    <div class="nx-footer-contact">
        <a href="tel:+50660407517" class="nx-footer-contact-item" title="<?= lang('llamar'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/></svg>
            <span>+506 6040-7517</span>
        </a>
        <a href="mailto:arasoftsolutions@outlook.com?subject=Consulta%20Neurix%20POS" class="nx-footer-contact-item" title="<?= lang('enviar_correo'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/><path d="M3 7l9 6l9 -6"/></svg>
            arasoftsolutions@outlook.com
        </a>
    </div>

    <!-- Social -->
    <div class="nx-footer-social">
        <div class="nx-footer-social-btn nx-footer-social-fb" title="Facebook">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3"/></svg>
        </div>
        <div class="nx-footer-social-btn nx-footer-social-ig" title="Instagram">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 4a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4z"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M16.5 7.5l0 .01"/></svg>
        </div>
        <div class="nx-footer-social-btn nx-footer-social-tt" title="TikTok">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 7.917v4.034a9.948 9.948 0 0 1 -5 -1.951v4.5a6.5 6.5 0 1 1 -8 -6.326v4.326a2.5 2.5 0 1 0 3.5 2.3v-11.8h4.5a6.969 6.969 0 0 0 5 6.917z"/></svg>
        </div>
        <a href="https://wa.me/50660407517?text=Hola%2C%20me%20interesa%20conocer%20m%C3%A1s%20sobre%20Neurix%20POS%20y%20los%20servicios%20de%20Arasoft%20Solutions.%20%C2%BFPodr%C3%ADan%20brindarme%20informaci%C3%B3n%3F" target="_blank" rel="noopener" class="nx-footer-social-btn nx-footer-social-wa" title="WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9"/><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1"/></svg>
        </a>
    </div>

    <!-- Copyright -->
    <div class="nx-footer-copy">
        &copy; <?= date('Y') ?> Arasoft Solutions · Todos los derechos reservados
    </div>

</footer>

</div><!-- /.app-wrapper -->

<!-- Modales -->
<div class="modal fade" id="posModal" tabindex="-1" aria-hidden="true"></div>
<div class="modal fade" id="myModal" tabindex="-1" aria-hidden="true"></div>
<div id="ajaxCall" class="position-fixed top-50 start-50 translate-middle d-none" style="z-index:9999;">
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="nx-spin"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
</div>

<!-- Configuración global -->
<script>
window._appConfig = {
    base_url: '<?= base_url(); ?>',
    site_url: '<?= site_url(); ?>',
    dateformat: '<?= $Settings->dateformat; ?>',
    timeformat: '<?= $Settings->timeformat ?>',
    module: '<?= $m; ?>',
    view: '<?= $v; ?>'
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
.swal-nx-flash { min-width:360px !important; font-size:15px !important; }
.swal2-popup.swal-nx-flash .swal2-title { font-size:1.2em !important; }
@keyframes nx-spin { to { transform: rotate(360deg); } }
.nx-spin { animation: nx-spin 1s linear infinite; }
</style>

<script>
/* ── Marcar ítem activo del sidebar ── */
document.addEventListener('DOMContentLoaded', function() {
    var m = window._appConfig.module;
    var v = window._appConfig.view;

    if (m) {
        var mmLi = document.querySelector('.mm_' + m);
        if (mmLi) {
            var mmLink = mmLi.querySelector('> .nav-link');
            if (mmLink) mmLink.classList.add('active');
            if (mmLi.querySelector('.nav-treeview')) {
                mmLi.classList.add('menu-open');
            }
        }
    }

    if (v && m) {
        var viewLi = document.getElementById(m + '_' + v);
        if (viewLi) {
            viewLi.classList.add('active');
            var viewLink = viewLi.querySelector('.nav-link');
            if (viewLink) viewLink.classList.add('active');
        }
    }
});

/* ── Panel button icon swap on sidebar toggle ── */
document.addEventListener('DOMContentLoaded', function() {
    var obs = new MutationObserver(function() {
        var collapsed = document.body.classList.contains('sidebar-collapse');
        var btn = document.getElementById('nxPanelBtn');
        if (btn) {
            btn.querySelector('.nx-panel-close').style.display = collapsed ? 'none' : 'flex';
            btn.querySelector('.nx-panel-open').style.display  = collapsed ? 'flex' : 'none';
        }
    });
    obs.observe(document.body, { attributes: true, attributeFilter: ['class'] });
});

/* ── SweetAlert2 shims ── */
window.alert = function(msg) {
    Swal.fire({ icon:'warning', text:String(msg), confirmButtonColor:'#0369a1' });
};

window.bootbox = {
    alert: function(msg, cb) {
        Swal.fire({ toast:true, position:'top-end', icon:'warning', title:String(msg), showConfirmButton:false, timer:3500, timerProgressBar:true })
            .then(function(){ if (cb) cb(); });
    },
    confirm: function(msg, cb) {
        Swal.fire({ title:String(msg), icon:'question', showCancelButton:true, confirmButtonText:'Sí', cancelButtonText:'Cancelar', confirmButtonColor:'#0369a1', cancelButtonColor:'#6b7280' })
            .then(function(r){ if (cb) cb(r.isConfirmed); });
    }
};

/* ── data-confirm delegado ── */
document.addEventListener('click', function(e) {
    var link = e.target.closest('a[data-confirm]');
    if (!link) return;
    e.preventDefault();
    Swal.fire({
        title: link.dataset.confirm || '¿Está seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then(function(r){ if (r.isConfirmed) window.location.href = link.href; });
});

/* ── Alertas flash ── */
document.addEventListener('DOMContentLoaded', function() {
    if (window._nxAlerts && window._nxAlerts.length) {
        var queue = window._nxAlerts.slice();
        function showNext() {
            if (!queue.length) return;
            var cfg = queue.shift();
            Swal.fire({ icon:cfg.icon, title:cfg.title, timer:4000, timerProgressBar:true, showConfirmButton:false, position:'top', customClass:{ popup:'swal-nx-flash' } }).then(showNext);
        }
        showNext();
    }
});
</script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?= base_url('sw.js') ?>');
    });
}
</script>
</body>
</html>
