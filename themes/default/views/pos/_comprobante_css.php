<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<?php
/* =====================================================================
 | Hoja de estilo compartida de los comprobantes (facturas, tiquetes,
 | notas de credito/debito, proformas).
 |
 | Se emite EN LINEA a proposito, no como archivo .css enlazado:
 |   * mPDF no descarga hojas externas de forma fiable (y Tec::pdf_html()
 |     elimina las etiquetas <link>), asi que el PDF quedaria sin formato.
 |   * los comprobantes son paginas sueltas de impresion; ahorra una peticion.
 |
 | Requiere en el ambito: $modal (bool) y $Settings.
 * =================================================================== */
$modal = isset($modal) ? $modal : false;
?>
            <style type="text/css" media="all">
                /* =====================================================
                 | Comprobante electrónico — hoja de estilo
                 | Sin variables CSS ni flexbox: mPDF no las interpreta,
                 | así que el mismo marcado sirve para pantalla y PDF.
                 * =================================================== */
                <?php if (!$modal) { ?>
                body {
                    background: #eef2f7;
                    color: #0f172a;
                    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                    font-size: 13px;
                    margin: 0;
                    padding: 0;
                }
                #wrapper { max-width: 760px; margin: 0 auto; padding: 24px 12px 40px; }
                <?php } ?>

                #receiptData {
                    background: #ffffff;
                    max-width: 760px;
                    margin: 0 auto;
                    color: #0f172a;
                    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                    font-size: 13px;
                    line-height: 1.45;
                }
                <?php if (!$modal) { ?>
                #receiptData { box-shadow: 0 10px 34px rgba(15,23,42,.10); border: 1px solid #e2e8f0; }
                <?php } ?>

                /* Franja superior de acento */
                .inv-topbar { height: 5px; background: #0284c7; font-size: 0; line-height: 0; }
                .inv-pad { padding: 26px 30px; }

                /* ---------- Encabezado ---------- */
                .inv-head { width: 100%; border-collapse: collapse; }
                .inv-head td { vertical-align: top; padding: 0; border: 0; }
                .inv-head .col-brand { width: 58%; }
                .inv-head .col-doc   { width: 42%; text-align: right; }

                .inv-logo { width: 62px; height: 62px; vertical-align: top; }
                .inv-logo img { max-width: 62px; max-height: 62px; }
                .inv-monogram {
                    width: 58px; height: 58px;
                    background: #0284c7; color: #ffffff;
                    text-align: center; font-size: 23px; font-weight: bold;
                    letter-spacing: 1px; border-radius: 8px;
                    line-height: 58px;
                }
                .inv-brandtxt { padding-left: 14px; vertical-align: top; }
                .inv-company {
                    font-size: 19px; font-weight: bold; color: #0f172a;
                    letter-spacing: -.2px; margin: 0 0 2px;
                }
                .inv-legal { font-size: 11px; color: #64748b; margin: 0 0 5px; }
                .inv-contact { font-size: 11px; color: #475569; line-height: 1.55; margin: 0; }

                .inv-doc-badge {
                    display: inline-block;
                    background: #e0f2fe; color: #075985;
                    border: 1px solid #bae6fd; border-radius: 4px;
                    padding: 5px 11px;
                    font-size: 11px; font-weight: bold;
                    letter-spacing: .9px; text-transform: uppercase;
                    margin-bottom: 10px;
                }
                .inv-doc-line { font-size: 11px; color: #64748b; margin-top: 6px; }
                .inv-doc-line b { display: block; color: #0f172a; font-size: 13px; font-weight: bold; }
                .inv-consec { font-family: "Courier New", monospace; letter-spacing: .3px; }

                .inv-rule { border-top: 1px solid #e2e8f0; margin: 20px 0; font-size: 0; line-height: 0; }

                /* ---------- Bloques cliente / detalles ---------- */
                .inv-parties { width: 100%; border-collapse: collapse; }
                .inv-parties td { vertical-align: top; padding: 0; border: 0; width: 50%; }
                .inv-parties td.right { padding-left: 22px; }
                .inv-label {
                    font-size: 9.5px; font-weight: bold; color: #0284c7;
                    letter-spacing: 1.1px; text-transform: uppercase; margin-bottom: 5px;
                }
                .inv-party-name { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
                .inv-party-line { font-size: 11.5px; color: #475569; }
                .inv-kv { width: 100%; border-collapse: collapse; }
                .inv-kv td { border: 0; padding: 1px 0; font-size: 11.5px; }
                .inv-kv td.k { color: #64748b; width: 44%; }
                .inv-kv td.v { color: #0f172a; text-align: right; }

                /* ---------- Tabla de líneas ---------- */
                .inv-items { width: 100%; border-collapse: collapse; margin-top: 4px; }
                .inv-items thead th {
                    background: #0f172a; color: #ffffff;
                    font-size: 9.5px; font-weight: bold;
                    letter-spacing: .9px; text-transform: uppercase;
                    padding: 9px 10px; text-align: left; border: 0;
                }
                .inv-items thead th.num { text-align: right; }
                .inv-items thead th.mid { text-align: center; }
                .inv-items tbody td {
                    padding: 9px 10px; border-bottom: 1px solid #eef2f7;
                    font-size: 12px; vertical-align: top;
                }
                .inv-items tbody tr.alt td { background: #f8fafc; }
                .inv-items td.num { text-align: right; white-space: nowrap; }
                .inv-items td.mid { text-align: center; white-space: nowrap; }
                .inv-items .it-name { font-weight: bold; color: #0f172a; }
                .inv-items .it-code { font-size: 10px; color: #94a3b8; }
                .it-tag {
                    display: inline-block; font-size: 8.5px; font-weight: bold;
                    padding: 1px 5px; border-radius: 3px; margin-left: 5px;
                    letter-spacing: .5px; vertical-align: middle;
                }
                .it-tag-g { background: #dcfce7; color: #166534; }
                .it-tag-e { background: #f1f5f9; color: #64748b; }
                .inv-empty { padding: 22px 10px; text-align: center; color: #94a3b8; font-size: 12px; }

                /* ---------- Totales ---------- */
                .inv-sum { width: 100%; border-collapse: collapse; margin-top: 18px; }
                .inv-sum td { border: 0; padding: 0; vertical-align: top; }
                .inv-sum td.spacer { width: 52%; }
                .inv-sum td.box { width: 48%; }
                .inv-tot { width: 100%; border-collapse: collapse; }
                .inv-tot td { border: 0; padding: 5px 12px; font-size: 12.5px; }
                .inv-tot td.k { color: #475569; text-align: right; }
                .inv-tot td.v { text-align: right; white-space: nowrap; font-weight: bold; }
                .inv-tot tr.sep td { border-top: 1px solid #e2e8f0; }
                .inv-tot tr.grand td {
                    background: #0f172a; color: #ffffff;
                    font-size: 15px; font-weight: bold; padding: 11px 12px;
                }
                .inv-tot tr.grand td.k { color: #cbd5e1; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; }
                .inv-tot tr.due td.v { color: #b91c1c; }

                /* ---------- Pagos ---------- */
                .inv-pay { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .inv-pay th {
                    background: #f8fafc; color: #475569;
                    font-size: 9.5px; letter-spacing: .9px; text-transform: uppercase;
                    padding: 7px 10px; text-align: left;
                    border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;
                }
                .inv-pay td {
                    padding: 7px 10px; font-size: 11.5px;
                    border-bottom: 1px solid #f1f5f9; color: #334155;
                }
                .inv-pay td.num { text-align: right; white-space: nowrap; }

                /* ---------- Pie ---------- */
                .inv-note {
                    margin-top: 18px; padding: 11px 13px;
                    background: #f8fafc; border-left: 3px solid #0284c7;
                    font-size: 11.5px; color: #475569;
                }
                /* Bloque de documento referenciado (notas de credito/debito) */
                .inv-ref {
                    margin-top: 20px; padding: 13px 15px;
                    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;
                }
                .inv-ref .inv-label { margin-bottom: 7px; }
                .inv-ref .inv-kv td { font-size: 11.5px; padding: 2px 0; }

                .inv-foot { margin-top: 22px; padding-top: 16px; border-top: 1px solid #e2e8f0; text-align: center; }
                .inv-clave-label { font-size: 9.5px; color: #64748b; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
                .inv-clave {
                    font-family: "Courier New", monospace;
                    font-size: 12px; font-weight: bold; color: #0f172a;
                    letter-spacing: .6px; word-break: break-all;
                    background: #f1f5f9; border: 1px solid #e2e8f0;
                    padding: 7px 10px; border-radius: 4px;
                    display: inline-block; margin-bottom: 12px;
                }
                .inv-barcode img { max-width: 100%; height: auto; }
                .inv-qr img {
                    width: 132px; height: 132px;
                    border: 1px solid #e2e8f0; border-radius: 6px;
                    padding: 6px; background: #ffffff;
                }
                .inv-qr-cap {
                    font-family: "Courier New", monospace;
                    font-size: 10px; color: #64748b;
                    letter-spacing: .4px; margin-top: 5px;
                }
                .inv-legalnote { font-size: 10px; color: #94a3b8; line-height: 1.6; margin-top: 12px; }
                .inv-thanks { font-size: 12px; color: #0284c7; font-weight: bold; margin-top: 10px; }

                .no-print { }

                /* =====================================================
                 | Cromo de la pagina (barra de acciones). Todo esto es
                 | .no-print y vive dentro de los marcadores de recorte
                 | de la vista, asi que no llega ni al PDF ni al correo.
                 * =================================================== */
                .pagebar {
                    position: sticky; top: 0; z-index: 40;
                    background: rgba(255,255,255,.92);
                    backdrop-filter: saturate(180%) blur(12px);
                    -webkit-backdrop-filter: saturate(180%) blur(12px);
                    border-bottom: 1px solid #e2e8f0;
                    box-shadow: 0 1px 3px rgba(15,23,42,.05);
                }
                .pagebar-in {
                    max-width: 1060px; margin: 0 auto;
                    padding: 11px 20px;
                    display: flex; align-items: center; gap: 16px;
                }
                .pb-back {
                    display: inline-flex; align-items: center; gap: 7px;
                    color: #475569; text-decoration: none;
                    font-size: 13px; font-weight: 600;
                    padding: 8px 13px; border-radius: 8px;
                    border: 1px solid #e2e8f0; background: #fff;
                    transition: all .16s ease; white-space: nowrap;
                }
                .pb-back:hover { color: #0f172a; border-color: #cbd5e1; background: #f8fafc; text-decoration: none; }

                .pb-title { flex: 1; min-width: 0; }
                .pb-doc {
                    display: block; font-size: 14px; font-weight: 700;
                    color: #0f172a; letter-spacing: -.2px;
                    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
                }
                .pb-sub { font-size: 11.5px; color: #94a3b8; font-family: "Courier New", monospace; }

                .pb-chip {
                    display: inline-block; vertical-align: middle;
                    font-size: 10px; font-weight: 700;
                    letter-spacing: .7px; text-transform: uppercase;
                    padding: 3px 9px; border-radius: 999px; margin-left: 8px;
                }
                .pb-chip-ok   { background: #dcfce7; color: #15803d; }
                .pb-chip-wait { background: #fef3c7; color: #b45309; }
                .pb-chip-bad  { background: #fee2e2; color: #b91c1c; }

                .pb-actions { display: flex; align-items: center; gap: 9px; flex-shrink: 0; }

                .nx-btn {
                    display: inline-flex; align-items: center; gap: 7px;
                    font-size: 13px; font-weight: 600; line-height: 1;
                    padding: 10px 15px; border-radius: 8px;
                    border: 1px solid transparent; cursor: pointer;
                    text-decoration: none; white-space: nowrap;
                    transition: all .16s ease;
                }
                .nx-btn:focus { outline: 2px solid #7dd3fc; outline-offset: 2px; }
                .nx-btn i { font-size: 13px; }

                .nx-btn-primary {
                    background: #0284c7; color: #fff;
                    box-shadow: 0 1px 2px rgba(2,132,199,.35);
                }
                .nx-btn-primary:hover {
                    background: #0369a1; color: #fff;
                    text-decoration: none; transform: translateY(-1px);
                    box-shadow: 0 4px 10px rgba(2,132,199,.32);
                }
                .nx-btn-ghost {
                    background: #fff; color: #334155; border-color: #e2e8f0;
                }
                .nx-btn-ghost:hover {
                    background: #f8fafc; color: #0f172a;
                    border-color: #cbd5e1; text-decoration: none;
                    transform: translateY(-1px);
                }
                .nx-btn-danger {
                    background: #fff; color: #b91c1c; border-color: #fecaca;
                }
                .nx-btn-danger:hover { background: #fef2f2; border-color: #fca5a5; color: #991b1b; text-decoration: none; }

                .pb-extra { max-width: 1060px; margin: 14px auto 0; padding: 0 20px; text-align: right; }

                /* ---------- Ventana de envio por correo ----------
                 | Marcado propio: la pagina del comprobante no carga
                 | bootstrap ni bootbox.
                 * ------------------------------------------------ */
                .nx-modal {
                    position: fixed; inset: 0; z-index: 90;
                    background: rgba(15, 23, 42, .55);
                    display: none;
                    align-items: flex-start; justify-content: center;
                    padding: 40px 16px;
                    overflow-y: auto;
                }
                .nx-modal.abierto { display: flex; }
                .nx-modal-caja {
                    background: #ffffff; width: 100%; max-width: 480px;
                    border-radius: 12px; overflow: hidden;
                    box-shadow: 0 20px 50px rgba(15, 23, 42, .28);
                }
                .nx-modal-cab {
                    padding: 16px 20px; border-bottom: 1px solid #e2e8f0;
                    display: flex; align-items: center; justify-content: space-between; gap: 12px;
                }
                .nx-modal-tit { font-size: 15px; font-weight: 700; color: #0f172a; }
                .nx-modal-sub { font-size: 11.5px; color: #94a3b8; margin-top: 2px; }
                .nx-modal-x {
                    background: transparent; border: 0; cursor: pointer;
                    font-size: 22px; line-height: 1; color: #94a3b8; padding: 0 4px;
                }
                .nx-modal-x:hover { color: #0f172a; }
                .nx-modal-cuerpo { padding: 20px; }
                .nx-modal-pie {
                    padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #f8fafc;
                    display: flex; justify-content: flex-end; gap: 9px;
                }
                .nx-campo-lbl {
                    display: block; font-size: 11px; font-weight: 700; color: #475569;
                    letter-spacing: .5px; text-transform: uppercase; margin-bottom: 6px;
                }
                /* www.min.css trae una regla `input{}` generica (appearance:none,
                   width:1em y colores del tema) que dejaba el campo negro sobre
                   negro; por eso aqui se fija todo de forma explicita. */
                .nx-input {
                    display: block;
                    width: 100%; height: auto;
                    box-sizing: border-box;
                    margin: 0;
                    padding: 10px 12px;
                    font-family: inherit; font-size: 13.5px; line-height: 1.4;
                    -webkit-appearance: none; appearance: none;
                    background: #ffffff !important;
                    background-image: none !important;
                    color: #0f172a !important;
                    -webkit-text-fill-color: #0f172a;
                    border: 1px solid #cbd5e1 !important;
                    border-radius: 8px;
                    outline: none;
                }
                .nx-input::placeholder { color: #94a3b8; opacity: 1; }
                .nx-input:focus {
                    border-color: #0284c7 !important;
                    box-shadow: 0 0 0 3px rgba(2, 132, 199, .15);
                }

                .nx-fila { display: flex; gap: 8px; align-items: stretch; }
                .nx-fila .nx-input { flex: 1; }
                .nx-mas {
                    flex: 0 0 auto;
                    width: 42px;
                    border: 0; border-radius: 8px; cursor: pointer;
                    background: #0284c7; color: #ffffff;
                    font-size: 20px; line-height: 1;
                }
                .nx-mas:hover { background: #0369a1; }
                .nx-mas:disabled { background: #cbd5e1; cursor: default; }

                .nx-ayuda { font-size: 11.5px; color: #94a3b8; margin-top: 7px; }

                /* Destinatarios ya agregados */
                .nx-destinos { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 6px; }
                .nx-destino {
                    display: inline-flex; align-items: center; gap: 7px;
                    background: #e0f2fe; color: #075985;
                    border: 1px solid #bae6fd; border-radius: 999px;
                    padding: 5px 6px 5px 12px;
                    font-size: 12.5px; max-width: 100%;
                }
                .nx-destino span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                .nx-destino button {
                    border: 0; background: transparent; cursor: pointer;
                    color: #0369a1; font-size: 15px; line-height: 1; padding: 0 3px;
                }
                .nx-destino button:hover { color: #b91c1c; }
                .nx-vacio { font-size: 12px; color: #94a3b8; margin-top: 12px; }

                .nx-aviso {
                    margin-top: 12px; padding: 9px 12px; border-radius: 7px;
                    font-size: 12.5px; display: none;
                }
                .nx-aviso.ok  { display: block; background: #dcfce7; color: #15803d; }
                .nx-aviso.err { display: block; background: #fee2e2; color: #b91c1c; }
                .nx-aviso.info{ display: block; background: #e0f2fe; color: #075985; }

                /* En pantallas angostas la barra se apila */
                @media (max-width: 780px) {
                    .pagebar-in { flex-wrap: wrap; gap: 10px; }
                    .pb-title { order: -1; width: 100%; flex: none; }
                    .pb-actions { width: 100%; }
                    .nx-btn { flex: 1; justify-content: center; }
                    .nx-btn span.lbl { display: none; }
                }

                /* =====================================================
                 | Impresion: el comprobante se remaqueta como tiquete de
                 | 80 mm para impresora termica. El rollo no tiene alto
                 | fijo, de ahi `size: 80mm auto`, y se imprime a sangre
                 | porque este tipo de impresora no maneja margenes.
                 * =================================================== */
                @media print {
                    /* Dentro del bloque print a proposito: mPDF lee las reglas
                       de pantalla y una @page suelta le cambiaria el tamano
                       de hoja del PDF. */
                    @page { size: 80mm auto; margin: 0; }

                    html, body {
                        width: 80mm;
                        margin: 0;
                        padding: 0;
                        background: #ffffff;
                        color: #000000;
                        font-size: 10px;
                        line-height: 1.35;
                    }
                    .no-print { display: none !important; }

                    #wrapper { max-width: 80mm; width: 80mm; margin: 0; padding: 0; }
                    #receiptData {
                        max-width: 80mm; width: 80mm;
                        margin: 0; border: 0; box-shadow: none;
                    }
                    .inv-topbar { display: none; }
                    .inv-pad { padding: 3mm 2mm; }

                    /* El encabezado pasa a una sola columna centrada */
                    .inv-head, .inv-head tbody, .inv-head tr, .inv-head td { display: block; width: 100%; }
                    .inv-head .col-brand, .inv-head .col-doc { width: 100%; text-align: center; }
                    .inv-head .col-doc { margin-top: 2mm; }
                    .inv-logo, .inv-brandtxt { display: block; text-align: center; padding: 0; }
                    .inv-logo img { max-width: 36mm; max-height: 14mm; margin: 0 auto 1mm; }
                    .inv-monogram {
                        margin: 0 auto 1mm; width: 12mm; height: 12mm; line-height: 12mm;
                        font-size: 14px; border-radius: 2mm;
                        background: #000000; color: #ffffff;
                    }
                    .inv-company { font-size: 13px; }
                    .inv-legal, .inv-contact { font-size: 9px; color: #000000; }
                    .inv-doc-badge {
                        background: transparent; border: 0; color: #000000;
                        font-size: 10px; padding: 0; margin: 1mm 0;
                        display: block; text-transform: uppercase;
                    }
                    .inv-doc-line { font-size: 9px; color: #000000; }
                    .inv-doc-line b { display: inline; font-size: 9px; }

                    .inv-rule { border-top: 1px dashed #000000; margin: 2mm 0; }

                    /* Cliente y detalles, tambien apilados */
                    .inv-parties, .inv-parties tbody, .inv-parties tr, .inv-parties td { display: block; width: 100%; }
                    .inv-parties td.right { padding-left: 0; margin-top: 1.5mm; }
                    .inv-label { font-size: 8px; color: #000000; letter-spacing: .5px; }
                    .inv-party-name { font-size: 10px; }
                    .inv-party-line, .inv-kv td { font-size: 9px; color: #000000; }
                    .inv-kv td.k { width: 50%; }

                    /* Lineas: se sacrifica el codigo y la numeracion por ancho */
                    .inv-items { font-size: 9px; }
                    .inv-items thead th {
                        background: transparent; color: #000000;
                        border-top: 1px dashed #000000; border-bottom: 1px dashed #000000;
                        padding: 1mm 0; font-size: 8px; letter-spacing: 0;
                    }
                    .inv-items tbody td { padding: 1mm 0; border-bottom: 0; }
                    .inv-items tbody tr.alt td { background: transparent; }
                    .inv-items thead th:first-child, .inv-items tbody td:first-child { display: none; }
                    .inv-items .it-code { display: none; }
                    .it-tag { background: transparent !important; color: #000000 !important; padding: 0 0 0 1mm; }

                    /* Totales a todo el ancho */
                    .inv-sum, .inv-sum tbody, .inv-sum tr, .inv-sum td { display: block; width: 100%; }
                    .inv-sum td.spacer { display: none; }
                    .inv-tot td { padding: .6mm 0; font-size: 9px; }
                    .inv-tot tr.grand td {
                        background: transparent; color: #000000;
                        font-size: 12px; padding: 1mm 0;
                        border-top: 1px dashed #000000; border-bottom: 1px dashed #000000;
                    }
                    .inv-tot tr.grand td.k { color: #000000; font-size: 9px; }
                    .inv-tot tr.due td.v { color: #000000; }

                    .inv-pay { margin-top: 2mm; font-size: 9px; }
                    .inv-pay th { background: transparent; color: #000000; font-size: 8px; padding: 1mm 0; }
                    .inv-pay td { padding: .6mm 0; border-bottom: 0; color: #000000; }

                    .inv-ref { background: transparent; border: 0; border-top: 1px dashed #000000; border-radius: 0; padding: 1.5mm 0; }
                    .inv-note { background: transparent; border-left: 0; padding: 1mm 0; font-size: 9px; color: #000000; }

                    .inv-foot { margin-top: 2mm; padding-top: 1.5mm; border-top: 1px dashed #000000; }
                    .inv-clave {
                        background: transparent; border: 0; padding: 0;
                        font-size: 8px; letter-spacing: 0; color: #000000;
                    }
                    .inv-clave-label { font-size: 8px; color: #000000; }
                    .inv-qr img { width: 26mm; height: 26mm; border: 0; padding: 0; }
                    .inv-qr-cap { font-size: 8px; }
                    .inv-legalnote { font-size: 7.5px; color: #000000; }
                    .inv-thanks { color: #000000; font-size: 10px; }

                    /* Evita cortar una linea a la mitad entre dos trozos de papel */
                    tr, .inv-foot, .inv-tot { page-break-inside: avoid; }
                }

                <?php if ($Settings->rtl) { ?>
                .text-right { text-align: left; }
                .text-left { text-align: right; }
                <?php } ?>
            </style>