<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

        </div><!-- /.container-fluid -->
    </div><!-- /.app-content -->
</main><!-- /.app-main -->

<footer class="app-footer">
    <div class="float-end d-none d-sm-inline" style="font-size:12px;opacity:.7;">
        v<strong><?= $Settings->version; ?></strong> &nbsp;&middot;&nbsp; ARASOFT SOLUTIONS
    </div>
    <span>Copyright &copy; <?= date('Y'); ?> </span>
    <strong><?= $Settings->site_name; ?></strong>
</footer>

</div><!-- /.app-wrapper -->

<!-- Modales -->
<div class="modal fade" id="posModal" tabindex="-1" aria-hidden="true"></div>
<div class="modal fade" id="myModal" tabindex="-1" aria-hidden="true"></div>
<div id="ajaxCall" class="position-fixed top-50 start-50 translate-middle d-none" style="z-index:9999;">
    <i class="fa fa-spinner fa-pulse fa-2x text-primary"></i>
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
</style>

<script>
/* ── Marcar ítem activo del sidebar ── */
document.addEventListener('DOMContentLoaded', function() {
    var m = window._appConfig.module;
    var v = window._appConfig.view;

    if (m) {
        /* Buscar el <li> con clase mm_module y activar su <a.nav-link> */
        var mmLi = document.querySelector('.mm_' + m);
        if (mmLi) {
            var mmLink = mmLi.querySelector('> .nav-link');
            if (mmLink) mmLink.classList.add('active');
            /* Si el <li> tiene un submenú treeview, expandirlo */
            if (mmLi.querySelector('.nav-treeview')) {
                mmLi.classList.add('menu-open');
            }
        }
    }

    if (v && m) {
        /* Activar el ítem específico de la sub-vista */
        var viewLi = document.getElementById(m + '_' + v);
        if (viewLi) {
            viewLi.classList.add('active');
            var viewLink = viewLi.querySelector('.nav-link');
            if (viewLink) viewLink.classList.add('active');
        }
    }
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
</body>
</html>
