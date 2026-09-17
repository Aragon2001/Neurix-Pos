<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<?php
/* =====================================================================
 | Hoja de estilo de los comprobantes en formato tirilla (variantes "e",
 | que se usan cuando Ajustes > print_img esta activo, y el tiquete de
 | parquimetro).
 |
 | Es un formato distinto al comprobante A4: fuente grande, una sola
 | columna, pensado para impresora termica. Comparte la paleta de
 | _comprobante_css.php para que todo se vea como el mismo sistema.
 |
 | Sin variables CSS: asi tambien funciona si el HTML pasa por mPDF.
 * =================================================================== */
?>
<style type="text/css" media="all">
    body {
        color: #0f172a;
        background: #eef2f7;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 16px;
    }

    #wrapper {
        max-width: 500px;
        margin: 0 auto;
        padding: 20px 10px 40px;
        font-size: 26px;
    }

    #receiptData {
        background: #ffffff;
        padding: 22px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        box-shadow: 0 8px 26px rgba(15, 23, 42, .10);
    }

    /* Marca del emisor */
    .tk-brand { text-align: center; margin-bottom: 14px; }
    .tk-monogram {
        width: 64px; height: 64px; line-height: 64px;
        margin: 0 auto 10px;
        background: #0284c7; color: #ffffff;
        font-size: 26px; font-weight: bold; letter-spacing: 1px;
        border-radius: 10px;
    }
    .tk-brand img,
    .tk-logo { max-width: 190px; max-height: 80px; margin-bottom: 8px; }
    .tk-name { font-size: 30px; font-weight: bold; letter-spacing: -.3px; }
    .tk-meta { font-size: 20px; color: #475569; line-height: 1.5; margin-top: 4px; }

    .tk-doc {
        display: inline-block;
        background: #e0f2fe; color: #075985;
        border: 1px solid #bae6fd; border-radius: 5px;
        padding: 5px 13px; margin: 12px 0 6px;
        font-size: 19px; font-weight: bold;
        letter-spacing: 1px; text-transform: uppercase;
    }

    .tk-rule { border-top: 2px dashed #cbd5e1; margin: 14px 0; }

    h3 { margin: 5px 0; font-size: 34px; }

    /* Tabla de lineas */
    .table { width: 100%; border-collapse: collapse; border-radius: 3px; }
    .table th {
        background: #0f172a; color: #ffffff;
        font-size: 18px; letter-spacing: .6px; text-transform: uppercase;
        padding: 8px 6px;
    }
    .table th, .table td { vertical-align: middle !important; }
    .table td { padding: 8px 6px; border-bottom: 1px solid #eef2f7; }
    .table tbody tr:nth-child(even) td { background: #f8fafc; }
    .table tfoot th { background: #f1f5f9; color: #0f172a; font-size: 22px; }

    .text-right { text-align: right; }
    .text-center { text-align: center; }

    /* Pie */
    .tk-foot { text-align: center; margin-top: 16px; }
    .tk-clave {
        font-family: "Courier New", monospace;
        font-size: 18px; font-weight: bold;
        word-break: break-all;
        background: #f1f5f9; border: 1px solid #e2e8f0;
        border-radius: 4px; padding: 7px 9px;
        display: inline-block; margin: 6px 0 10px;
    }
    .tk-qr img {
        width: 170px; height: 170px;
        border: 1px solid #e2e8f0; border-radius: 6px;
        padding: 6px; background: #ffffff;
    }
    .tk-legal { font-size: 16px; color: #94a3b8; line-height: 1.5; margin-top: 12px; }
    .bcimg { width: 61% !important; height: 57px !important; }

    .btn { margin-bottom: 5px; }

    @media print {
        body { background: #ffffff; color: #000 !important; }
        .no-print { display: none !important; }
        #wrapper { max-width: 500px; width: 100%; min-width: 250px; margin: 0 auto; padding: 0; }
        #receiptData { border: 0; box-shadow: none; padding: 0; }
        .table th { background: #f5f5f5 !important; color: #000 !important; }
    }
</style>
