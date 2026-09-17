<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 *
 * Cuerpo del correo que acompaña a un comprobante.
 *
 * Maquetado con tablas y estilos en línea a propósito: Gmail y Outlook
 * descartan el <style> del <head>, así que cualquier regla que viva ahí
 * no llega. Tampoco se incrusta el logo: la URL apuntaría a este servidor,
 * que el cliente no alcanza desde su correo.
 *
 * @var array $correo  emisor, tipo, saludo, consecutivo, clave, fecha, total,
 *                     adjuntos, nota
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$c = $correo;

$b1 = 'color:#0f172a;';                       // texto principal
$b2 = 'color:#475569;';                       // texto secundario
$b3 = 'color:#94a3b8;';                       // texto de apoyo
$fuente = "font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= html_escape($c['tipo']); ?></title>
</head>
<body style="margin:0;padding:0;background:#eef1f5;<?= $fuente ?>">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef1f5;padding:28px 12px;">
<tr><td align="center">

    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
           style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #dde3ea;">

        <!-- Encabezado -->
        <tr>
            <td style="padding:26px 32px 20px;border-bottom:1px solid #eef1f5;">
                <div style="font-size:17px;font-weight:700;<?= $b1 ?>letter-spacing:-.01em;">
                    <?= html_escape($c['emisor']); ?>
                </div>
                <?php if (!empty($c['cedula_emisor'])): ?>
                <div style="font-size:12px;<?= $b3 ?>margin-top:3px;">
                    <?= html_escape($c['cedula_emisor']); ?>
                </div>
                <?php endif; ?>
            </td>
        </tr>

        <!-- Saludo -->
        <tr>
            <td style="padding:26px 32px 0;">
                <p style="margin:0 0 12px;font-size:15px;<?= $b1 ?>">
                    <?= html_escape($c['saludo']); ?>
                </p>
                <p style="margin:0;font-size:14px;line-height:1.6;<?= $b2 ?>">
                    <?= html_escape($c['intro']); ?>
                </p>
            </td>
        </tr>

        <!-- Resumen -->
        <tr>
            <td style="padding:22px 32px 0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="background:#f7f9fb;border:1px solid #e6ebf1;border-radius:10px;">
                    <tr>
                        <td style="padding:16px 18px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-size:12px;<?= $b3 ?>padding-bottom:2px;"><?= lang('correo_comprobante'); ?></td>
                                    <td align="right" style="font-size:13px;font-weight:600;<?= $b1 ?>padding-bottom:2px;">
                                        <?= html_escape($c['tipo']); ?>
                                    </td>
                                </tr>
                                <?php if (!empty($c['consecutivo'])): ?>
                                <tr>
                                    <td style="font-size:12px;<?= $b3 ?>padding:4px 0 2px;"><?= lang('correo_numero'); ?></td>
                                    <td align="right" style="font-size:13px;font-weight:600;<?= $b1 ?>padding:4px 0 2px;font-family:ui-monospace,Consolas,monospace;">
                                        <?= html_escape($c['consecutivo']); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($c['fecha'])): ?>
                                <tr>
                                    <td style="font-size:12px;<?= $b3 ?>padding:4px 0 2px;"><?= lang('date'); ?></td>
                                    <td align="right" style="font-size:13px;font-weight:600;<?= $b1 ?>padding:4px 0 2px;">
                                        <?= html_escape($c['fecha']); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="font-size:13px;font-weight:700;<?= $b1 ?>padding-top:12px;border-top:1px solid #e6ebf1;">
                                        <?= lang('correo_total'); ?>
                                    </td>
                                    <td align="right" style="font-size:19px;font-weight:700;<?= $b1 ?>padding-top:12px;border-top:1px solid #e6ebf1;">
                                        <?= html_escape($c['total']); ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Clave -->
        <?php if (!empty($c['clave'])): ?>
        <tr>
            <td style="padding:14px 32px 0;">
                <div style="font-size:11px;<?= $b3 ?>text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">
                    <?= lang('correo_clave'); ?>
                </div>
                <div style="font-size:11.5px;<?= $b2 ?>font-family:ui-monospace,Consolas,monospace;word-break:break-all;line-height:1.5;">
                    <?= html_escape($c['clave']); ?>
                </div>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Adjuntos -->
        <?php if (!empty($c['adjuntos'])): ?>
        <tr>
            <td style="padding:22px 32px 0;">
                <div style="font-size:13px;font-weight:600;<?= $b1 ?>margin-bottom:8px;">
                    <?= lang('correo_adjuntos'); ?>
                </div>
                <?php foreach ($c['adjuntos'] as $adjunto): ?>
                <div style="font-size:13px;<?= $b2 ?>line-height:1.9;">&bull;&nbsp; <?= html_escape($adjunto); ?></div>
                <?php endforeach; ?>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Nota -->
        <?php if (!empty($c['nota'])): ?>
        <tr>
            <td style="padding:20px 32px 0;">
                <p style="margin:0;font-size:13px;line-height:1.6;<?= $b2 ?>">
                    <?= html_escape($c['nota']); ?>
                </p>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Pie -->
        <tr>
            <td style="padding:26px 32px 28px;">
                <div style="border-top:1px solid #eef1f5;padding-top:18px;font-size:12px;line-height:1.7;<?= $b3 ?>">
                    <?php if (!empty($c['contacto'])): ?>
                        <div style="<?= $b2 ?>"><?= html_escape($c['contacto']); ?></div>
                    <?php endif; ?>
                    <div style="margin-top:6px;"><?= lang('correo_automatico'); ?></div>
                </div>
            </td>
        </tr>

    </table>

</td></tr>
</table>

</body>
</html>
