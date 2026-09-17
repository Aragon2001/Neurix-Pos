<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 *
 * Artículos vendidos en el turno. Se abre en una pestaña aparte y se imprime
 * con el diálogo del navegador: la impresión automática es solo del tiquete.
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$cajero = isset($cajero->first_name) ? trim($cajero->first_name . ' ' . $cajero->last_name) : '';

$unidades = 0;
$importe  = 0;
foreach ($articulos as $a) {
    $unidades += (float) $a->unidades;
    $importe  += (float) $a->total;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= lang('cierre_articulos'); ?></title>
<style>
    :root { color-scheme: light; }
    body {
        margin: 0; padding: 28px 22px; background: #f1f5f9; color: #0f172a;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
    .hoja { max-width: 820px; margin: 0 auto; background: #fff; border: 1px solid #dde3ea;
            border-radius: 14px; padding: 26px 28px; }
    h1 { margin: 0; font-size: 18px; }
    .meta { margin: 5px 0 0; font-size: 12.5px; color: #64748b; }
    .barra { display: flex; gap: 8px; margin: 18px 0 22px; }
    .btn { padding: 9px 16px; border-radius: 9px; border: 1px solid #cbd5e1; background: #fff;
           color: #334155; font-size: 13px; font-weight: 600; cursor: pointer; }
    .btn:hover { border-color: #94a3b8; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .06em;
         color: #64748b; padding: 0 0 8px; border-bottom: 1px solid #e2e8f0; }
    td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
    th.num, td.num { text-align: right; font-variant-numeric: tabular-nums; }
    tfoot td { border-top: 2px solid #e2e8f0; border-bottom: 0; padding-top: 12px; font-weight: 700; }
    .codigo { color: #64748b; font-family: ui-monospace, Consolas, monospace; font-size: 12px; }
    .vacio { padding: 30px 0; text-align: center; color: #94a3b8; font-size: 13.5px; }
    @media print {
        body { background: #fff; padding: 0; }
        .hoja { border: 0; border-radius: 0; padding: 0; max-width: none; }
        .barra { display: none; }
    }
</style>
</head>
<body>

<div class="hoja">
    <h1><?= lang('cierre_articulos'); ?></h1>
    <p class="meta">
        <?= lang('cierre_abierta_el'); ?> <?= html_escape($this->tec->hrld($desde)); ?>
        <?php if ($cajero !== ''): ?> &nbsp;·&nbsp; <?= lang('cierre_cajero'); ?>: <?= html_escape($cajero); ?><?php endif; ?>
        &nbsp;·&nbsp; <?= date('d/m/Y H:i'); ?>
    </p>

    <div class="barra">
        <button type="button" class="btn" onclick="window.print()"><?= lang('cierre_imprimir'); ?></button>
        <button type="button" class="btn" onclick="window.close()"><?= lang('close'); ?></button>
    </div>

    <?php if (!$articulos): ?>
        <div class="vacio"><?= lang('cierre_sin_movimiento'); ?></div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th><?= lang('product_code'); ?></th>
                <th><?= lang('product_name'); ?></th>
                <th class="num"><?= lang('quantity'); ?></th>
                <th class="num"><?= lang('total'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articulos as $a): ?>
            <tr>
                <td class="codigo"><?= html_escape($a->codigo); ?></td>
                <td><?= html_escape($a->nombre); ?></td>
                <td class="num"><?= (float) $a->unidades == (int) $a->unidades
                        ? (int) $a->unidades
                        : rtrim(rtrim(number_format((float) $a->unidades, 3, '.', ''), '0'), '.'); ?></td>
                <td class="num"><?= $this->tec->formatMoney($a->total); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2"><?= lang('total'); ?></td>
                <td class="num"><?= $unidades == (int) $unidades ? (int) $unidades : number_format($unidades, 2); ?></td>
                <td class="num"><?= $this->tec->formatMoney($importe); ?></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
