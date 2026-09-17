<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

// Colilla imprimible de un pago SINPE. 76 mm es el area util de una termica de 80 mm.

$bancos = array(
    'bac' => 'BAC Credomatic', 'bcr' => 'Banco de Costa Rica', 'bn' => 'Banco Nacional',
    'popular' => 'Banco Popular', 'davivienda' => 'Davivienda', 'promerica' => 'Promerica',
    'scotiabank' => 'Scotiabank', 'lafise' => 'Lafise', 'coopealianza' => 'Coopealianza',
);
$banco = isset($bancos[$tx->banco]) ? $bancos[$tx->banco] : ($tx->banco ?: 'No identificado');

$estados = array(
    'pendiente'  => 'SIN APLICAR',
    'usado'      => 'APLICADO A VENTA',
    'descartado' => 'DESCARTADO',
);
$estado = isset($estados[$tx->estado]) ? $estados[$tx->estado] : strtoupper($tx->estado);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Comprobante SINPE <?= html_escape($tx->comprobante ?: $tx->id_sinpe_transaction); ?></title>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: "Courier New", Consolas, monospace;
        font-size: 12px; line-height: 1.45; color: #000; background: #f0f0f0;
        margin: 0; padding: 16px;
    }
    .colilla {
        width: 76mm; margin: 0 auto; background: #fff; padding: 6mm 5mm;
        box-shadow: 0 1px 6px rgba(0,0,0,.18);
    }
    .centro { text-align: center; }
    .negocio { font-size: 14px; font-weight: bold; text-transform: uppercase; }
    .titulo {
        font-size: 13px; font-weight: bold; margin: 10px 0 2px;
        border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0;
    }
    .fila { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
    .fila .k { color: #000; white-space: nowrap; }
    .fila .v { text-align: right; word-break: break-all; font-weight: bold; }
    .ref { font-size: 13px; letter-spacing: .5px; word-break: break-all; }
    .monto { font-size: 20px; font-weight: bold; margin: 8px 0; }
    .sep { border-top: 1px dashed #000; margin: 8px 0; }
    .pie { font-size: 10px; text-align: center; margin-top: 10px; }
    .estado { font-weight: bold; letter-spacing: 1px; }
    .acciones { text-align: center; margin: 18px auto 0; max-width: 76mm; }
    .acciones button {
        font: inherit; padding: 8px 16px; margin: 0 4px; cursor: pointer;
        border: 1px solid #888; border-radius: 4px; background: #fff;
    }
    @media print {
        body { background: #fff; padding: 0; }
        .colilla { box-shadow: none; width: auto; padding: 0; }
        .acciones { display: none; }
    }
</style>
</head>
<body>

<div class="colilla">
    <div class="centro negocio"><?= html_escape($Settings->site_name ?: 'Comprobante'); ?></div>
    <div class="centro titulo">COMPROBANTE SINPE MOVIL</div>

    <div class="centro monto">&#8353;<?= number_format((float) $tx->monto, 2); ?></div>

    <div class="sep"></div>

    <div class="fila"><span class="k">Fecha</span><span class="v"><?= html_escape($tx->fecha); ?></span></div>
    <div class="fila"><span class="k">Banco</span><span class="v"><?= html_escape($banco); ?></span></div>
    <?php if ($tx->nombre): ?>
    <div class="fila"><span class="k">Remitente</span><span class="v"><?= html_escape($tx->nombre); ?></span></div>
    <?php endif; ?>
    <?php if ($tx->telefono): ?>
    <div class="fila"><span class="k">Telefono</span><span class="v"><?= html_escape($tx->telefono); ?></span></div>
    <?php endif; ?>

    <div class="sep"></div>

    <div class="k">N. de referencia</div>
    <div class="ref"><?= html_escape($tx->comprobante ?: 'sin referencia'); ?></div>

    <?php if ($tx->descripcion): ?>
    <div class="sep"></div>
    <div class="k">Detalle</div>
    <div><?= html_escape($tx->descripcion); ?></div>
    <?php endif; ?>

    <div class="sep"></div>

    <div class="fila"><span class="k">Estado</span><span class="v estado"><?= html_escape($estado); ?></span></div>
    <?php if ($venta): ?>
    <div class="fila"><span class="k">Venta</span><span class="v">#<?= (int) $venta->id; ?></span></div>
    <?php if ($venta->consecutivo): ?>
    <div class="fila"><span class="k">Consecutivo</span><span class="v"><?= html_escape($venta->consecutivo); ?></span></div>
    <?php endif; ?>
    <div class="fila"><span class="k">Total factura</span><span class="v">&#8353;<?= number_format((float) $venta->grand_total, 2); ?></span></div>
    <?php
        // Excedente sobre el total de la factura: es el vuelto entregado.
        $sobrante = (float) $tx->monto - (float) $venta->grand_total;
        if ($sobrante > 0.5):
    ?>
    <div class="fila"><span class="k">Vuelto</span><span class="v">&#8353;<?= number_format($sobrante, 2); ?></span></div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="pie">
        Documento interno de control &mdash; no es un comprobante fiscal.<br>
        Impreso el <?= date('d/m/Y H:i'); ?>
    </div>
</div>

<div class="acciones">
    <button onclick="window.print()">Imprimir</button>
    <button onclick="window.close()">Cerrar</button>
</div>

<script>
    window.addEventListener('load', function () { window.print(); });
</script>

</body>
</html>
