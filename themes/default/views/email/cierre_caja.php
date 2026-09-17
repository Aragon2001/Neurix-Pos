<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 *
 * Cierre de caja enviado por correo.
 *
 * Tablas y estilos en línea: Gmail y Outlook descartan el <style> del <head>.
 *
 * @var array $r resumen del turno, tal como lo arma PosRegister::_resumen_turno()
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$b1 = 'color:#0f172a;';
$b2 = 'color:#475569;';
$b3 = 'color:#94a3b8;';
$fuente = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";

$cajero = isset($r['cajero']->first_name)
    ? trim($r['cajero']->first_name . ' ' . $r['cajero']->last_name)
    : '';

$fila = function ($etiqueta, $valor, $tono = '') use ($b1, $b2, $b3) {
    return '<tr>'
        . '<td style="padding:7px 0;font-size:13px;' . $b2 . 'border-bottom:1px solid #eef1f5;">' . html_escape($etiqueta) . '</td>'
        . '<td align="right" style="padding:7px 0;font-size:13px;font-weight:600;'
        . ($tono ?: $b1) . 'border-bottom:1px solid #eef1f5;white-space:nowrap;">' . $valor . '</td>'
        . '</tr>';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= lang('cierre_titulo'); ?></title>
</head>
<body style="margin:0;padding:0;background:#eef1f5;<?= $fuente ?>">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef1f5;padding:28px 12px;">
<tr><td align="center">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
           style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #dde3ea;">

        <tr>
            <td style="padding:24px 30px 18px;border-bottom:1px solid #eef1f5;">
                <div style="font-size:17px;font-weight:700;<?= $b1 ?>"><?= lang('cierre_titulo'); ?></div>
                <div style="font-size:12.5px;<?= $b3 ?>margin-top:4px;">
                    <?= html_escape($Settings->nombre_comercial ?: $Settings->site_name); ?>
                    <?php if ($cajero !== ''): ?>
                        &nbsp;·&nbsp; <?= lang('cierre_cajero'); ?>: <?= html_escape($cajero); ?>
                    <?php endif; ?>
                </div>
                <div style="font-size:12.5px;<?= $b3 ?>margin-top:2px;">
                    <?= lang('cierre_abierta_el'); ?> <?= html_escape($this->tec->hrld($r['desde'])); ?>
                    &nbsp;·&nbsp; <?= html_escape(date('d/m/Y H:i')); ?>
                </div>
            </td>
        </tr>

        <!-- Cobrado por forma de pago -->
        <tr>
            <td style="padding:22px 30px 0;">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;<?= $b3 ?>margin-bottom:8px;">
                    <?= lang('cierre_cobrado_por'); ?>
                </div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <?php
                    $conMovimiento = false;
                    foreach ($r['metodos'] as $m) {
                        if (!$m['total'] && empty($m['pagos'])) { continue; }
                        $conMovimiento = true;
                        $etiqueta = $m['etiqueta'] . (empty($m['pagos']) ? '' : '  (' . (int) $m['pagos'] . ')');
                        echo $fila($etiqueta, $this->tec->formatMoney($m['total']));
                    }
                    if (!$conMovimiento) {
                        echo '<tr><td colspan="2" style="padding:7px 0;font-size:13px;' . $b3 . '">'
                           . lang('cierre_sin_movimiento') . '</td></tr>';
                    }
                    ?>
                    <tr>
                        <td style="padding:10px 0 0;font-size:14px;font-weight:700;<?= $b1 ?>"><?= lang('cierre_total_cobrado'); ?></td>
                        <td align="right" style="padding:10px 0 0;font-size:18px;font-weight:700;<?= $b1 ?>white-space:nowrap;">
                            <?= $this->tec->formatMoney($r['cobrado']); ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Movimientos -->
        <tr>
            <td style="padding:24px 30px 0;">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;<?= $b3 ?>margin-bottom:8px;">
                    <?= lang('cierre_movimientos'); ?>
                </div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <?= $fila(lang('cierre_fondo_inicial'), $this->tec->formatMoney($r['fondo'])); ?>
                    <?= $fila(lang('pago_efectivo'), $this->tec->formatMoney($r['metodos']['efectivo']['total'])); ?>
                    <?php if (!empty($r['apartados'])): ?>
                        <?= $fila(lang('cierre_apartados'), $this->tec->formatMoney($r['apartados'])); ?>
                    <?php endif; ?>
                    <?php if ($r['depositos']): ?>
                        <?= $fila(lang('cierre_depositos'), $this->tec->formatMoney($r['depositos'])); ?>
                    <?php endif; ?>
                    <?php if ($r['gastos']): ?>
                        <?= $fila(lang('cierre_gastos'), '− ' . $this->tec->formatMoney($r['gastos']), 'color:#b91c1c;'); ?>
                    <?php endif; ?>
                </table>
            </td>
        </tr>

        <!-- SINPE del turno -->
        <?php if (!empty($r['sinpe']) && $r['sinpe']['entrantes']): ?>
        <tr>
            <td style="padding:24px 30px 0;">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;<?= $b3 ?>margin-bottom:8px;">
                    <?= lang('cierre_sinpe'); ?>
                </div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <?= $fila(lang('cierre_sinpe_entrantes') . '  (' . (int) $r['sinpe']['entrantes'] . ')',
                              $this->tec->formatMoney($r['sinpe']['monto'])); ?>
                    <?= $fila(lang('cierre_sinpe_aplicados') . '  (' . (int) $r['sinpe']['aplicados'] . ')',
                              $this->tec->formatMoney($r['sinpe']['monto_aplicado'])); ?>
                    <?php $sinAplicar = (int) $r['sinpe']['entrantes'] - (int) $r['sinpe']['aplicados']; ?>
                    <?php if ($sinAplicar > 0): ?>
                        <?= $fila(lang('cierre_sinpe_sin_aplicar') . '  (' . $sinAplicar . ')',
                                  $this->tec->formatMoney($r['sinpe']['monto'] - $r['sinpe']['monto_aplicado'])); ?>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Lo que no pasa por la gaveta -->
        <?php if ($r['notas_credito'] || $r['ventas_credito']): ?>
        <tr>
            <td style="padding:24px 30px 0;">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;<?= $b3 ?>margin-bottom:8px;">
                    <?= lang('cierre_otros_movimientos'); ?>
                </div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <?php if ($r['ventas_credito']): ?>
                        <?= $fila(lang('cierre_ventas_credito'), $this->tec->formatMoney($r['ventas_credito'])); ?>
                    <?php endif; ?>
                    <?php if ($r['notas_credito']): ?>
                        <?= $fila(lang('cierre_notas_credito'), $this->tec->formatMoney($r['notas_credito'])); ?>
                    <?php endif; ?>
                </table>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Esperado en caja -->
        <tr>
            <td style="padding:24px 30px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="background:#f7f9fb;border:1px solid #e6ebf1;border-radius:10px;">
                    <tr>
                        <td style="padding:14px 18px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-size:13px;font-weight:600;<?= $b1 ?>"><?= lang('cierre_efectivo_esperado'); ?></td>
                                    <td align="right" style="font-size:17px;font-weight:700;<?= $b1 ?>white-space:nowrap;">
                                        <?= $this->tec->formatMoney($r['efectivo_esperado']); ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <?php if (empty($para_pdf)): ?>
                <div style="margin-top:16px;font-size:12px;<?= $b3 ?>line-height:1.6;">
                    <?= lang('correo_automatico'); ?>
                </div>
                <?php endif; ?>
            </td>
        </tr>

    </table>

</td></tr>
</table>

</body>
</html>
