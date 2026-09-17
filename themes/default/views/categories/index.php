<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_hoja  = '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>';
$ico_subir = '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 9l5 -5l5 5"/><path d="M12 4l0 12"/>';
$ico_mas   = '<path d="M12 5v14M5 12h14"/>';
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('categories'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <?= $icono($ico_hoja); ?> <?= lang('exportar'); ?>
        </button>
        <?php if ($Admin) { ?>
            <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('categories/import'); ?>">
                <?= $icono($ico_subir); ?> <?= lang('import_categories'); ?>
            </a>
            <a class="nxt-btn" href="<?= site_url('categories/add'); ?>">
                <?= $icono($ico_mas); ?> <?= lang('add_category'); ?>
            </a>
        <?php } ?>
    </div>
</div>

<?php if (!empty($error)) { ?>
    <div class="nxf-note nxf-note-err" style="margin-bottom:16px"><div><?= $error; ?></div></div>
<?php } ?>
<?php if (!empty($message)) { ?>
    <div class="nxf-note nxf-note-ok" style="margin-bottom:16px"><div><?= $message; ?></div></div>
<?php } ?>

<div class="nxc-kpis-top" id="catKpis" hidden>
    <div class="nxc-kpi info">
        <span class="nxc-kpi-n" id="kpiCats">0</span>
        <span class="nxc-kpi-t"><?= lang('categories'); ?></span>
    </div>
    <div class="nxc-kpi ok">
        <span class="nxc-kpi-n" id="kpiProds">0</span>
        <span class="nxc-kpi-t"><?= lang('cat_productos'); ?></span>
    </div>
    <div class="nxc-kpi mudo">
        <span class="nxc-kpi-n" id="kpiVacias">0</span>
        <span class="nxc-kpi-t"><?= lang('cat_sin_uso'); ?></span>
    </div>
</div>

<div id="nxtList"></div>

<!-- Ficha de la categoría y confirmación de borrado comparten la capa. -->
<div class="nxc-capa" id="catCapa" hidden>
    <div class="nxc-caja" id="catCaja" role="dialog" aria-modal="true"></div>
</div>

<style>
    .nxc-kpis-top{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px}

    /* ── Tarjetas de cifras (listado y ficha) ── */
    .nxc-kpi{
        position:relative;overflow:hidden;padding:14px 16px;border-radius:var(--nx-radius);
        border:1px solid var(--nx-border);background:var(--nx-card-bg2);
        display:flex;flex-direction:column;gap:3px;
    }
    .nxc-kpi::before{content:"";position:absolute;inset:0 auto 0 0;width:3px;background:var(--kpi-c,var(--nx-slate))}
    .nxc-kpi.ok{--kpi-c:var(--nx-ok)}
    .nxc-kpi.info{--kpi-c:var(--nx-a1)}
    .nxc-kpi.warn{--kpi-c:var(--nx-warn)}
    .nxc-kpi.mudo{--kpi-c:var(--nx-txt4)}
    .nxc-kpi-n{font-size:22px;font-weight:700;color:var(--nx-txt1);letter-spacing:-.02em;font-variant-numeric:tabular-nums}
    .nxc-kpi-t{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--nx-txt4)}
    .nxc-kpi-pie{font-size:11.5px;color:var(--nx-txt3);margin-top:2px}

    /* La fila entera abre la ficha. */
    .nxc-fila{cursor:pointer}

    /* ── Columna de productos del listado ── */
    .nxc-cuenta{
        display:inline-flex;align-items:center;gap:7px;font-variant-numeric:tabular-nums;
        font-size:13px;font-weight:600;color:var(--nx-txt2);
    }
    .nxc-cuenta i{
        font-style:normal;min-width:26px;text-align:center;padding:2px 7px;border-radius:999px;
        border:1px solid var(--nx-border);background:var(--nx-card-bg2);
    }
    .nxc-cuenta.viva i{border-color:var(--nx-border3);background:var(--nx-active-bg);color:var(--nx-a1)}
    .nxc-cuenta.vacia{color:var(--nx-txt4)}

    /* ── Capa y caja del modal ── */
    .nxc-capa{
        position:fixed;inset:0;z-index:1085;display:flex;align-items:center;justify-content:center;padding:24px;
        background:rgba(3,7,18,.72);backdrop-filter:blur(4px);
    }
    .nxc-capa[hidden]{display:none}
    body.nxc-bloqueado{overflow:hidden}
    .nxc-caja{
        display:flex;flex-direction:column;width:min(760px,100%);max-height:min(760px,100%);
        background:var(--nx-card-bg);border:1px solid var(--nx-border3);
        border-radius:var(--nx-radius-lg);box-shadow:var(--nx-shadow-lg);
        overflow:hidden;animation:nxc-entra .18s cubic-bezier(.4,0,.2,1);
    }
    .nxc-caja.angosta{width:min(440px,100%)}
    @keyframes nxc-entra{from{opacity:0;transform:translateY(12px) scale(.985)}to{opacity:1;transform:none}}
    @media (prefers-reduced-motion:reduce){.nxc-caja{animation:none}}

    /* ── Cabecera ── */
    .nxc-cab{
        display:flex;align-items:center;gap:14px;padding:16px 20px;
        border-bottom:1px solid var(--nx-border);background:var(--nx-card-bg2);
    }
    .nxc-cab-ico,.nxc-cab-img{width:44px;height:44px;flex-shrink:0;border-radius:12px}
    .nxc-cab-ico{
        display:grid;place-items:center;font-size:15px;font-weight:700;letter-spacing:.02em;
        background:linear-gradient(135deg,var(--nx-a1),var(--nx-indigo));color:#06121f;
        box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
    }
    [data-theme="light"] .nxc-cab-ico{color:#fff}
    .nxc-cab-img{object-fit:cover;border:1px solid var(--nx-border);background:var(--nx-bg3);cursor:zoom-in}
    .nxc-cab-txt{min-width:0;flex:1}
    .nxc-cab-tit{font-size:17px;font-weight:700;letter-spacing:-.01em;color:var(--nx-txt1);line-height:1.3}
    .nxc-cab-sub{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:5px;font-size:12.5px;color:var(--nx-txt3)}
    .nxc-x{
        width:34px;height:34px;flex-shrink:0;border-radius:9px;border:1px solid var(--nx-border);
        background:transparent;color:var(--nx-txt3);cursor:pointer;
        display:grid;place-items:center;transition:var(--nx-transition);
    }
    .nxc-x:hover{color:var(--nx-err);border-color:color-mix(in srgb,var(--nx-err) 45%,transparent)}

    /* ── Cuerpo ── */
    .nxc-cuerpo{padding:18px 20px;overflow:auto;display:flex;flex-direction:column;gap:16px}
    .nxc-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:11px}
    .nxc-seccion{
        display:flex;align-items:center;gap:7px;
        font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--nx-txt4);
    }
    .nxc-seccion svg{color:var(--nx-a1)}
    .nxc-tabla-wrap{border:1px solid var(--nx-border);border-radius:var(--nx-radius);overflow:auto;max-height:290px;margin-top:-6px}
    .nxc-tabla{width:100%;border-collapse:collapse;font-size:12.5px;min-width:440px}
    .nxc-tabla thead th{
        position:sticky;top:0;z-index:1;padding:9px 12px;text-align:left;white-space:nowrap;
        font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--nx-txt4);
        background:var(--nx-bg3);border-bottom:1px solid var(--nx-border);
    }
    .nxc-tabla td{padding:8px 12px;border-bottom:1px solid var(--nx-border2);color:var(--nx-txt2)}
    .nxc-tabla tbody tr:last-child td{border-bottom:0}
    .nxc-tabla tbody tr:hover{background:var(--nx-hover-bg)}
    .nxc-tabla th.num,.nxc-tabla td.num{text-align:right}
    .nxc-tabla td.mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;color:var(--nx-txt3);font-variant-numeric:tabular-nums}
    .nxc-qty{font-weight:600;font-variant-numeric:tabular-nums;color:var(--nx-txt1)}
    .nxc-qty.cero{color:var(--nx-err)}
    .nxc-dim{color:var(--nx-txt4)}
    .nxc-mas{font-size:12px;color:var(--nx-txt4)}
    .nxc-vacio{
        display:flex;flex-direction:column;align-items:center;gap:10px;padding:34px 20px;text-align:center;
        border:1px dashed var(--nx-border3);border-radius:var(--nx-radius);color:var(--nx-txt4);
    }
    .nxc-vacio p{margin:0;font-size:13px}
    .nxc-texto{font-size:13.5px;line-height:1.55;color:var(--nx-txt2);margin:0}
    .nxc-texto b{color:var(--nx-txt1)}

    /* ── Pie ── */
    .nxc-pie{
        display:flex;align-items:center;gap:10px;flex-wrap:wrap;
        padding:14px 20px;border-top:1px solid var(--nx-border);background:var(--nx-card-bg2);
    }
    .nxc-pie .nxf-spacer{margin-left:auto}
    .nxf-btn.peligro{background:linear-gradient(135deg,var(--nx-err),#b91c1c);box-shadow:0 6px 18px -6px rgba(239,68,68,.55);color:#fff}
    .nxf-btn.peligro:hover:not(:disabled){box-shadow:0 10px 24px -6px rgba(239,68,68,.65);color:#fff}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var BASE  = '<?= base_url(); ?>';
    var SITE  = '<?= site_url(); ?>';
    var TOKEN = '<?= $token; ?>';
    var ADMIN = <?= $Admin ? 'true' : 'false'; ?>;

    var T = {
        borrarTitulo:  <?= json_encode(lang('delete_category')); ?>,
        borrarConfirma:<?= json_encode(lang('cat_borrar_confirma')); ?>,
        noBorrable:    <?= json_encode(lang('cat_no_borrable')); ?>,
        enUso:         <?= json_encode(lang('categoria_con_productos')); ?>,
        cancelar:      <?= json_encode(lang('cancel')); ?>,
        borrar:        <?= json_encode(lang('delete')); ?>,
        ver:           <?= json_encode(lang('view')); ?>,
        editar:        <?= json_encode(lang('edit_category')); ?>,
        cerrar:        <?= json_encode(lang('close')); ?>
    };

    var esc = NxTable.esc;

    function svg(d, s) {
        return '<svg width="' + (s || 14) + '" height="' + (s || 14) + '" viewBox="0 0 24 24" fill="none"' +
               ' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + d + '</svg>';
    }
    var ICO = {
        ver:    '<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6s-6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6s6.6 2 9 6"/>',
        editar: '<path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3z"/>',
        borrar: '<path d="M4 7h16M10 11v6M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>',
        cerrar: '<path d="M18 6l-12 12"/><path d="M6 6l12 12"/>',
        alerta: '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>'
    };

    /* ── Capa compartida por la ficha y la confirmación ── */
    var $capa = document.getElementById('catCapa');
    var $caja = document.getElementById('catCaja');

    function abrir(html, angosta) {
        $caja.className = 'nxc-caja' + (angosta ? ' angosta' : '');
        $caja.innerHTML = html;
        $capa.hidden = false;
        document.body.classList.add('nxc-bloqueado');
        var foco = $caja.querySelector('[data-nxc-cerrar],button,a');
        if (foco) { foco.focus(); }
    }
    function cerrar() {
        $capa.hidden = true;
        $caja.innerHTML = '';
        document.body.classList.remove('nxc-bloqueado');
    }
    $capa.addEventListener('click', function (e) {
        if (e.target === $capa || e.target.closest('[data-nxc-cerrar]')) { cerrar(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !$capa.hidden) { cerrar(); }
    });

    function cargando() {
        return '<div class="nxc-cuerpo"><div class="nxc-vacio"><p>' + esc('<?= lang('loading_data_from_server'); ?>') + '</p></div></div>';
    }

    /* ── Ficha ── */
    function verFicha(id) {
        abrir(cargando());
        fetch(SITE + 'categories/view/' + id, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) {
                if (!r.ok) { throw new Error(r.status); }
                return r.text();
            })
            .then(function (html) { abrir(html); })
            .catch(function () { cerrar(); });
    }

    /* ── Borrado: se confirma aquí porque data-confirm no tiene manejador ── */
    function confirmarBorrado(fila) {
        var usados = parseInt(fila.productos, 10) || 0;
        var bloqueado = usados > 0;
        var cuerpo = bloqueado
            ? '<p class="nxc-texto">' + esc(T.enUso.replace('%s', usados)) + '</p>'
            : '<p class="nxc-texto">' + esc(T.borrarConfirma.replace('%s', fila.name)) + '</p>';

        abrir(
            '<div class="nxc-cab">' +
                '<div class="nxc-cab-ico" style="background:linear-gradient(135deg,var(--nx-err),#b91c1c);color:#fff">' +
                    svg(ICO.alerta, 20) + '</div>' +
                '<div class="nxc-cab-txt"><div class="nxc-cab-tit">' + esc(T.borrarTitulo) + '</div>' +
                '<div class="nxc-cab-sub"><span class="nxt-code">' + esc(fila.code) + '</span></div></div>' +
                '<button type="button" class="nxc-x" data-nxc-cerrar aria-label="' + esc(T.cerrar) + '">' + svg(ICO.cerrar, 17) + '</button>' +
            '</div>' +
            '<div class="nxc-cuerpo">' + cuerpo + '</div>' +
            '<div class="nxc-pie"><span class="nxf-spacer"></span>' +
                '<button type="button" class="nxf-btn nxf-btn-sm nxf-btn-ghost" data-nxc-cerrar>' + esc(T.cancelar) + '</button>' +
                (bloqueado ? '' :
                    '<a class="nxf-btn nxf-btn-sm peligro" href="' + SITE + 'categories/delete/' + fila.id + '?t=' + encodeURIComponent(TOKEN) + '">' +
                    svg(ICO.borrar, 14) + ' ' + esc(T.borrar) + '</a>') +
            '</div>',
            true
        );
    }

    /* ── Listado ── */
    var filas = {};

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('categories/get_categories'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '620px',
        unit: '<?= lang('categories'); ?>'.toLowerCase(),
        exportName: 'categorias',
        search: ['code', 'name'],
        rowAttr: function (r) { return 'class="nxc-fila" data-id="' + r.id + '"'; },
        onData: function (rows) {
            filas = {};
            var productos = 0, vacias = 0;
            rows.forEach(function (r) {
                filas[r.id] = r;
                var n = parseInt(r.productos, 10) || 0;
                productos += n;
                if (n === 0) { vacias++; }
            });
            document.getElementById('kpiCats').textContent = rows.length;
            document.getElementById('kpiProds').textContent = productos;
            document.getElementById('kpiVacias').textContent = vacias;
            document.getElementById('catKpis').hidden = false;
        },
        columns: [
            { key: 'name', label: '<?= lang('name'); ?>', sortable: 'str', render: function (r) {
                if (r.image && r.image !== 'no_image.png') {
                    return '<div class="nxt-ent"><div class="nxt-avatar">' +
                        '<img src="' + BASE + 'uploads/thumbs/' + esc(r.image) + '" alt="" loading="lazy"></div>' +
                        '<div><div class="nxt-ent-name">' + esc(r.name) + '</div></div></div>';
                }
                return NxTable.entity(r.name, null, r.name);
            } },
            { key: 'code', label: '<?= lang('code'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-code">' + esc(r.code) + '</span>';
            } },
            { key: 'productos', label: '<?= lang('cat_productos'); ?>', className: 'num', sortable: 'num', render: function (r) {
                var n = parseInt(r.productos, 10) || 0;
                return '<span class="nxc-cuenta ' + (n ? 'viva' : 'vacia') + '"><i>' + n + '</i></span>';
            } },
            { key: 'id', label: '<?= lang('actions'); ?>', className: 'num', width: '132px', noExport: true, render: function (r) {
                var n = parseInt(r.productos, 10) || 0;
                var html = '<div class="nxt-actions">' +
                    '<a class="nxt-icon-btn" href="#" data-ver="' + r.id + '" title="' + esc(T.ver) + '">' + svg(ICO.ver) + '</a>';
                if (ADMIN) {
                    html += '<a class="nxt-icon-btn warn" href="' + SITE + 'categories/edit/' + r.id + '" title="' + esc(T.editar) + '">' + svg(ICO.editar) + '</a>' +
                            '<a class="nxt-icon-btn danger" href="#" data-borrar="' + r.id + '" title="' +
                            esc(n ? T.noBorrable : T.borrarTitulo) + '">' + svg(ICO.borrar) + '</a>';
                }
                return html + '</div>';
            } }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_nombre_codigo'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>'
        }
    });

    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });

    /* Toda la fila abre la ficha; los botones de acción mandan sobre ella. */
    document.getElementById('nxtList').addEventListener('click', function (e) {
        var borrar = e.target.closest('[data-borrar]');
        if (borrar) {
            e.preventDefault();
            var f = filas[borrar.dataset.borrar];
            if (f) { confirmarBorrado(f); }
            return;
        }
        var ver = e.target.closest('[data-ver]');
        if (ver) {
            e.preventDefault();
            verFicha(ver.dataset.ver);
            return;
        }
        if (e.target.closest('a')) { return; }
        var fila = e.target.closest('.nxc-fila');
        if (fila) { verFicha(fila.dataset.id); }
    });
});
</script>
