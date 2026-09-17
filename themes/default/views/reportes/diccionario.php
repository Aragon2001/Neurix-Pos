<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) or exit('No direct script access allowed'); ?>

<div class="nxr-head">
    <div>
        <h1>Diccionario de datos</h1>
        <p>Qué significa cada cifra de los informes y de dónde sale exactamente.
           Todos los informes del sistema usan estas mismas reglas.</p>
    </div>
    <div class="nxr-acciones">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('reportes'); ?>">Centro de Inteligencia</a>
        <button type="button" class="nxt-btn nxt-btn-ghost" onclick="window.print()">Imprimir</button>
    </div>
</div>

<h4 class="nxr-grupo" style="display:block;margin:18px 0 8px">Ámbito de un informe</h4>
<p class="nxr-dim" style="font-size:12.5px;margin:0 0 10px">
    Decide qué comprobantes entran. La diferencia no es cosmética: una factura anulada con nota de
    crédito sigue <b>aceptada</b> ante Hacienda, así que sale de la gestión interna pero tiene que
    seguir en la declaración, porque es su nota la que la compensa.
</p>
<div class="nxr-dic">
    <?php foreach ($ambitos as $k => $a): ?>
    <div class="nxr-dic-i">
        <h4><?= html_escape($a['etiqueta']); ?> <span class="nxr-dic-f"><?= html_escape($k); ?></span></h4>
        <p><?= html_escape($a['ayuda']); ?>
           Anuladas: <b><?= $a['anuladas'] ? 'incluidas' : 'excluidas'; ?></b>.
           Estados admitidos: <b><?= $a['estados'] ? html_escape(implode(', ', $a['estados'])) : 'todos'; ?></b>.</p>
    </div>
    <?php endforeach; ?>
</div>

<h4 class="nxr-grupo" style="display:block;margin:22px 0 8px">Conceptos</h4>
<div class="nxr-dic">
    <?php foreach ($conceptos as $c): ?>
    <div class="nxr-dic-i">
        <h4><?= html_escape($c['titulo']); ?></h4>
        <span class="nxr-dic-f"><?= html_escape($c['formula']); ?></span>
        <p><?= html_escape($c['regla']); ?></p>
    </div>
    <?php endforeach; ?>
</div>

<h4 class="nxr-grupo" style="display:block;margin:22px 0 8px">Tarifas de IVA (v4.4)</h4>
<div class="nxt-card"><div class="nxt-table-wrap"><table class="nxt-table">
    <thead><tr><th>Tarifa</th><th>Etiqueta</th><th>Código de tarifa</th></tr></thead>
    <tbody>
    <?php foreach ($tarifas as $t => $d): ?>
        <tr><td class="num"><?= html_escape($t); ?> %</td>
            <td><?= html_escape($d['etiqueta']); ?></td>
            <td><span class="nxt-code"><?= html_escape($d['codigo']); ?></span></td></tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>

<h4 class="nxr-grupo" style="display:block;margin:22px 0 8px">Comprobaciones de auditoría</h4>
<p class="nxr-dim" style="font-size:12.5px;margin:0 0 10px">
    Cada una corre sobre el período seleccionado y descuenta del índice de confiabilidad según su
    nivel. Ninguna corrige datos: detectan y describen.
</p>
<div class="nxt-card"><div class="nxt-table-wrap"><table class="nxt-table">
    <thead><tr><th>Nivel</th><th>Comprobación</th><th>Causa probable</th><th>Acción recomendada</th></tr></thead>
    <tbody>
    <?php foreach ($reglas as $r): ?>
        <tr>
            <td><span class="nxt-cat" style="--cat-c:var(--nx-<?= $niveles[$r['nivel']]['tono'] === 'err' ? 'err' : ($niveles[$r['nivel']]['tono'] === 'orange' ? 'orange' : ($niveles[$r['nivel']]['tono'] === 'warn' ? 'amber' : 'a1')); ?>)"><?= html_escape($niveles[$r['nivel']]['etiqueta']); ?></span></td>
            <td><b><?= html_escape($r['titulo']); ?></b></td>
            <td><small><?= html_escape($r['causa']); ?></small></td>
            <td><small><?= html_escape($r['accion']); ?></small></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div></div>
