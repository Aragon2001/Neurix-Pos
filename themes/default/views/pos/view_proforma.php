<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<?php $type_document = 2; ?>
<?php
// La proforma no es un comprobante de Hacienda: no lleva clave ni consecutivo.
$doc_titulo_forzado = lang('proforma');
$doc_num_label = 'Proforma N°';
$doc_num_valor = $inv->id;
$pie_legal     = '';
$pie_gracias   = '';
$hacienda      = isset($hacienda) ? $hacienda : null;
$invoiceqr     = isset($invoiceqr) ? $invoiceqr : '';
include FCPATH . 'themes/default/views/pos/_comprobante_datos.php';
?>

<?php
if ($modal) {
?>

<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-body">

            <button type="button" class="close" data-bs-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i>
            </button>
            <?php
            } else {
            ?><!doctype html>
            <html<?= $Settings->rtl ? ' dir="rtl"' : ''; ?>>
            <head>
                <meta charset="utf-8">
                <title><?= $page_title . " " . lang("no") . " " . $inv->id; ?></title>
                <base href="<?= base_url() ?>"/>
                <meta http-equiv="cache-control" content="max-age=0"/>
                <meta http-equiv="cache-control" content="no-cache"/>
                <meta http-equiv="expires" content="0"/>
                <meta http-equiv="pragma" content="no-cache"/>
                <link rel="shortcut icon" href="<?= $assets ?>images/icon.png"/>
                <link href="<?= $assets ?>dist/css/www.min.css" rel="stylesheet" type="text/css"/>
                <link href="<?= $assets ?>plugins/font-awesome/css/font-awesome.css" rel="stylesheet" type="text/css"/>
            <?php } ?>

            <?php include FCPATH . 'themes/default/views/pos/_comprobante_css.php'; ?>
            <?php if (!$modal) { ?>
            </head>
            <body>
            <?php } ?>

            <?php if (!$modal) { ?>
                <!-- start -->
                <div class="pagebar no-print">
                    <div class="pagebar-in">
                        <a class="pb-back" href="<?= site_url('pos'); ?>">
                            <i class="fa fa-arrow-left"></i> <?= lang("back_to_pos"); ?>
                        </a>
                        <div class="pb-title">
                            <span class="pb-doc"><?= html_escape($doc_titulo) ?></span>
                            <span class="pb-sub">N° <?= html_escape($inv->id) ?></span>
                        </div>
                        <div class="pb-actions">
                            <button onclick="return printReceipt()" class="nx-btn nx-btn-primary">
                                <i class="fa fa-print"></i> <span class="lbl"><?= lang("print"); ?></span>
                            </button>
                            <a class="nx-btn nx-btn-ghost" href="#" id="email">
                                <i class="fa fa-envelope-o"></i> <span class="lbl"><?= lang("email"); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- end -->
            <?php } ?>

            <div id="wrapper">
                <?php if (!$modal && $message) { ?>
                    <div class="alert alert-success no-print">
                        <button data-bs-dismiss="alert" class="close" type="button">×</button>
                        <?= is_array($message) ? print_r($message, true) : $message; ?>
                    </div>
                <?php } ?>

                <div id="receiptData">
                    <div class="inv-topbar"></div>
                    <div class="inv-pad">
                        <div id="receipt-data">

                            <?php include FCPATH . 'themes/default/views/pos/_comprobante_head.php'; ?>

                            <div class="inv-rule"></div>

                            <table class="inv-parties">
                                <tr>
                                    <td>
                                        <div class="inv-label"><?= lang('customer') ?></div>
                                        <div class="inv-party-name"><?= html_escape($inv->customer_name) ?></div>
                                        <?php if ($ident_label && !empty($customer->cf2)) { ?>
                                            <div class="inv-party-line"><?= html_escape($ident_label) ?>: <?= html_escape($customer->cf2) ?></div>
                                        <?php } ?>
                                        <?php if (!empty($customer->email)) { ?>
                                            <div class="inv-party-line"><?= html_escape($customer->email) ?></div>
                                        <?php } ?>
                                    </td>
                                    <td class="right">
                                        <div class="inv-label"><?= lang('details') ?></div>
                                        <table class="inv-kv">
                                            <?php if (!empty($created_by)) { ?>
                                                <tr>
                                                    <td class="k"><?= lang('sales_person') ?></td>
                                                    <td class="v"><?= html_escape(trim(@$created_by->first_name . ' ' . @$created_by->last_name)) ?></td>
                                                </tr>
                                            <?php } ?>
                                            <?php if (!empty($store->name)) { ?>
                                                <tr>
                                                    <td class="k"><?= lang('store') ?></td>
                                                    <td class="v"><?= html_escape($store->name) ?></td>
                                                </tr>
                                            <?php } ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <div class="inv-rule"></div>

                            <?php include FCPATH . 'themes/default/views/pos/_comprobante_lineas.php'; ?>

                            <table class="inv-sum">
                                <tr>
                                    <td class="spacer">
                                        <?php if (!empty($inv->note)) { ?>
                                            <div class="inv-note" style="margin-top:0;">
                                                <?= nota_segura($inv->note) ?>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="box">
                                        <table class="inv-tot">
                                            <tr>
                                                <td class="k"><?= lang('total') ?></td>
                                                <td class="v"><?= $this->tec->formatMoney($inv->total + ($inv->product_tax ?? 0)) ?></td>
                                            </tr>
                                            <?php if ($inv->MontoExoneracion) { ?>
                                                <tr>
                                                    <td class="k"><?= lang('total') ?> Exoneración</td>
                                                    <td class="v"><?= $this->tec->formatMoney($inv->MontoExoneracion) ?></td>
                                                </tr>
                                            <?php } ?>
                                            <?php
                                            if (!empty($inv->order_tax) && $inv->order_tax != 0) {
                                                echo '<tr><td class="k">' . lang('order_tax') . '</td><td class="v">' . $this->tec->formatMoney($inv->order_tax) . '</td></tr>';
                                            }
                                            if ($inv->total_discount != 0) {
                                                echo '<tr><td class="k">' . lang('order_discount') . '</td><td class="v">' . $this->tec->formatMoney($inv->total_discount) . '</td></tr>';
                                            }
                                            echo '<tr class="grand"><td class="k">' . lang('grand_total') . '</td><td class="v">' . $this->tec->formatMoney($inv->grand_total) . '</td></tr>';
                                            ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <div class="inv-note" style="text-align:center;">
                                Este documento es una <b>proforma</b> y no constituye un comprobante
                                electrónico autorizado por la Dirección General de Tributación.
                            </div>

                            <?php include FCPATH . 'themes/default/views/pos/_comprobante_pie.php'; ?>
                        </div>
                    </div>
                </div>

                <!-- start -->
                <!-- Ventana de envio por correo -->
                <div class="nx-modal no-print" id="modal-correo">
                    <div class="nx-modal-caja" role="dialog" aria-modal="true" aria-labelledby="modal-correo-tit">
                        <div class="nx-modal-cab">
                            <div>
                                <div class="nx-modal-tit" id="modal-correo-tit"><?= lang('email') ?></div>
                                <div class="nx-modal-sub"><?= html_escape($doc_titulo) ?> ·
                                    <?= html_escape(!empty($hacienda->consecutivo) ? $hacienda->consecutivo : '#' . $inv->id) ?></div>
                            </div>
                            <button type="button" class="nx-modal-x" id="correo-cerrar" aria-label="<?= lang('close') ?>">&times;</button>
                        </div>
                        <div class="nx-modal-cuerpo">
                            <label class="nx-campo-lbl" for="correo-nuevo"><?= lang('email_address') ?></label>
                            <div class="nx-fila">
                                <input type="email" id="correo-nuevo" class="nx-input"
                                       placeholder="contacto@ejemplo.com" autocomplete="off">
                                <button type="button" class="nx-mas" id="correo-agregar"
                                        title="Agregar destinatario" aria-label="Agregar destinatario">+</button>
                            </div>
                            <div class="nx-ayuda">Escriba un correo y presione <strong>Enter</strong> o el botón <strong>+</strong> para agregarlo.</div>

                            <div class="nx-destinos" id="correo-lista"></div>
                            <div class="nx-vacio" id="correo-vacio">Sin destinatarios.</div>

                            <div class="nx-aviso" id="correo-aviso"></div>
                        </div>
                        <div class="nx-modal-pie">
                            <button type="button" class="nx-btn nx-btn-ghost" id="correo-cancelar"><?= lang('close') ?></button>
                            <button type="button" class="nx-btn nx-btn-primary" id="correo-enviar">
                                <i class="fa fa-paper-plane"></i> Enviar
                            </button>
                        </div>
                    </div>
                </div>
                <!-- end -->

                <!-- start -->
                <?php if ($modal) { ?>
                    <div id="buttons" class="no-print" style="padding-top:16px;">
                        <div class="pb-actions" style="justify-content:flex-end;">
                            <button onclick="return printReceipt()" class="nx-btn nx-btn-primary">
                                <i class="fa fa-print"></i> <?= lang("print"); ?>
                            </button>
                            <a class="nx-btn nx-btn-ghost" href="#" id="email">
                                <i class="fa fa-envelope-o"></i> <?= lang("email"); ?>
                            </a>
                            <button type="button" class="nx-btn nx-btn-ghost" data-bs-dismiss="modal">
                                <?= lang('close'); ?>
                            </button>
                        </div>
                    </div>
                <?php } ?>
                <!-- end -->
            </div>

            <!-- start -->
            <?php
            if (!$modal) {
                ?>
                <script type="text/javascript">
                    var base_url = '<?=base_url();?>';
                    var site_url = '<?=site_url();?>';
                    var dateformat = '<?=$Settings->dateformat;?>', timeformat = '<?= $Settings->timeformat ?>';
                    <?php unset($Settings->protocol, $Settings->smtp_host, $Settings->smtp_user, $Settings->smtp_pass, $Settings->smtp_port, $Settings->smtp_crypto, $Settings->mailpath, $Settings->timezone, $Settings->setting_id, $Settings->default_email, $Settings->version, $Settings->stripe, $Settings->stripe_secret_key, $Settings->stripe_publishable_key); ?>
                    var Settings = <?= json_encode(ajustes_publicos($Settings)); ?>;
                </script>
                <script src="<?= $assets ?>plugins/jQuery/jquery-3.7.1.min.js"></script>
                <script src="<?= $assets ?>dist/js/main.min.js?v=<?= @filemtime(FCPATH . 'themes/default/assets/dist/js/main.min.js') ?: '1'; ?>"></script>
                <?php
            }
            ?>
            <script type="text/javascript">
            (function () {
                var $$ = function (id) { return document.getElementById(id); };

                // Impresion: dialogo del navegador. La hoja @media print maqueta
                // el comprobante como tiquete de 80 mm.
                window.imprimirTiquete = function () {
                    window.print();
                    return false;
                };

                /* ---------- Ventana de envio por correo ----------
                 | Marcado y logica propios: esta pagina no carga bootstrap
                 | ni bootbox (libraries.min.js / scripts.min.js no existen).
                 * ------------------------------------------------ */
                var modal   = $$('modal-correo');
                var entrada = $$('correo-nuevo');
                var lista   = $$('correo-lista');
                var vacio   = $$('correo-vacio');
                var aviso   = $$('correo-aviso');
                var enviar  = $$('correo-enviar');
                var agregar = $$('correo-agregar');

                var destinos = [];
                var RE_CORREO = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;

                function mostrarAviso(texto, tono) {
                    if (!aviso) { return; }
                    aviso.className = 'nx-aviso ' + (tono || 'info');
                    aviso.innerHTML = texto;
                }
                function limpiarAviso() {
                    if (aviso) { aviso.className = 'nx-aviso'; aviso.innerHTML = ''; }
                }

                function pintarDestinos() {
                    if (!lista) { return; }
                    lista.innerHTML = '';
                    destinos.forEach(function (correo, i) {
                        var chip = document.createElement('span');
                        chip.className = 'nx-destino';
                        var txt = document.createElement('span');
                        txt.textContent = correo;
                        var x = document.createElement('button');
                        x.type = 'button';
                        x.innerHTML = '&times;';
                        x.title = 'Quitar';
                        x.addEventListener('click', function () {
                            destinos.splice(i, 1);
                            pintarDestinos();
                        });
                        chip.appendChild(txt);
                        chip.appendChild(x);
                        lista.appendChild(chip);
                    });
                    if (vacio) { vacio.style.display = destinos.length ? 'none' : 'block'; }
                }

                /** Agrega lo escrito como destinatario. Devuelve true si entro. */
                function agregarCorreo() {
                    if (!entrada) { return false; }
                    var valor = entrada.value.trim().replace(/[;,]+$/, '');
                    if (!valor) { return false; }
                    if (!RE_CORREO.test(valor)) {
                        mostrarAviso('Correo no válido: ' + valor, 'err');
                        return false;
                    }
                    if (destinos.indexOf(valor) === -1) { destinos.push(valor); }
                    entrada.value = '';
                    limpiarAviso();
                    pintarDestinos();
                    return true;
                }

                if (agregar) { agregar.addEventListener('click', agregarCorreo); }
                if (entrada) {
                    // Enter, coma o punto y coma cierran el destinatario en curso
                    entrada.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ',' || e.key === ';') {
                            e.preventDefault();
                            agregarCorreo();
                        }
                    });
                    // Al salir del campo tambien se agrega, para no perder lo escrito
                    entrada.addEventListener('blur', function () {
                        if (entrada.value.trim()) { agregarCorreo(); }
                    });
                }

                function abrir() {
                    if (!modal) { return; }
                    limpiarAviso();
                    // Se parte del correo del cliente, si tiene
                    var delCliente = '<?= html_escape(@$customer->email) ?>';
                    destinos = (delCliente && RE_CORREO.test(delCliente)) ? [delCliente] : [];
                    if (entrada) { entrada.value = ''; }
                    pintarDestinos();
                    modal.classList.add('abierto');
                    if (entrada) { entrada.focus(); }
                }
                function cerrar() {
                    if (modal) { modal.classList.remove('abierto'); }
                }

                var btnCorreo = $$('email');
                if (btnCorreo) {
                    btnCorreo.addEventListener('click', function (e) { e.preventDefault(); abrir(); });
                }
                if ($$('correo-cerrar'))   { $$('correo-cerrar').addEventListener('click', cerrar); }
                if ($$('correo-cancelar')) { $$('correo-cancelar').addEventListener('click', cerrar); }
                if (modal) {
                    modal.addEventListener('click', function (e) { if (e.target === modal) { cerrar(); } });
                }
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && modal && modal.classList.contains('abierto')) { cerrar(); }
                });

                if (enviar) {
                    enviar.addEventListener('click', function () {
                        // Lo que quedo escrito sin agregar tambien cuenta
                        if (entrada && entrada.value.trim() && !agregarCorreo()) { return; }
                        if (!destinos.length) { mostrarAviso('Agregue al menos un destinatario.', 'err'); return; }

                        enviar.disabled = true;
                        mostrarAviso('Enviando…', 'info');

                        var pendientes = destinos.length, ok = 0, fallidos = [];
                        destinos.forEach(function (correo) {
                            var cuerpo = new FormData();
                            cuerpo.append('<?= $this->security->get_csrf_token_name(); ?>',
                                          '<?= $this->security->get_csrf_hash(); ?>');
                            cuerpo.append('email', correo);
                            cuerpo.append('id', '<?= (int) $inv->id; ?>');

                            fetch('<?= site_url('pos/email_proforma') ?>', {
                                method: 'POST',
                                body: cuerpo,
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            }).then(function (r) {
                                // Sin comprobar r.ok, un 403 de CSRF pasaria por exito
                                if (!r.ok) { throw new Error('http_' + r.status); }
                                ok++;
                            }).catch(function () {
                                fallidos.push(correo);
                            }).then(function () {
                                if (--pendientes > 0) { return; }
                                enviar.disabled = false;
                                if (fallidos.length && !ok) {
                                    mostrarAviso('No se pudo enviar a: ' + fallidos.join(', '), 'err');
                                } else if (fallidos.length) {
                                    mostrarAviso('Enviado a ' + ok + ' destinatario(s).<br>Falló: ' + fallidos.join(', '), 'err');
                                } else {
                                    mostrarAviso('Enviado a ' + ok + ' destinatario(s).', 'ok');
                                    setTimeout(cerrar, 1600);
                                }
                            });
                        });
                    });
                }

                // Con Ajustes > POS = "Recibo", el cobro termina en esta pantalla.
                // Al volver se limpia el carrito y se deja la marca para que el
                // POS avise que la venta quedo registrada.
                var volver = $$('volver-pos');
                if (volver) {
                    volver.addEventListener('click', function (e) {
                        e.preventDefault();
                        <?php if (!empty($recien_cobrada)) { ?>
                        // Solo cuando se llego aqui recien cobrando: consultar una
                        // venta vieja no debe borrar el carrito en curso ni avisar.
                        try {
                            ['spositems', 'spos_tax', 'spos_discount', 'spos_customer'].forEach(function (k) {
                                localStorage.removeItem(k);
                            });
                            localStorage.setItem('nx_venta_ok', '1');
                        } catch (err) {}
                        <?php } ?>
                        window.location.href = volver.getAttribute('href');
                    });
                }
            })();
            </script>
            <?php include 'remote_printing.php'; ?>
            <?php
            if ($modal) {
            ?>
        </div>
    </div>
</div>
<?php
} else {
    ?>
    <!-- end -->
    </body>
    </html>
    <?php
}
?>
