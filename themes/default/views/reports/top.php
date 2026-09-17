<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Cuatro rankings de productos más vendidos. Las barras se dibujan con CSS:
 * la gráfica anterior dependía de Highcharts 4 con adaptador de jQuery, que
 * dejó de cargarse con la migración a Vite.
 */
$bloques = array(
    array('titulo' => lang('this_month') . ' (' . date('F Y') . ')',                                        'datos' => $topProducts),
    array('titulo' => lang('last_month') . ' (' . date('F Y', strtotime('last month')) . ')',                'datos' => $topProducts1),
    array('titulo' => lang('last_3_months') . ' (' . lang('from') . ' ' . date('F Y', strtotime('-3 month')) . ')',  'datos' => $topProducts3),
    array('titulo' => lang('last_12_months') . ' (' . lang('from') . ' ' . date('F Y', strtotime('-12 month')) . ')', 'datos' => $topProducts12),
);
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('top_products_heading'); ?>
        <small><?= lang('top_products_ayuda'); ?></small>
    </div>
</div>

<div class="rank-grid">
    <?php foreach ($bloques as $b) {
        $filas = array();
        foreach ((array) $b['datos'] as $r) {
            if ((float) $r->quantity > 0) { $filas[] = $r; }
        }
        $tope = 0;
        foreach ($filas as $r) { $tope = max($tope, (float) $r->quantity); }
        ?>
        <div class="rank-card">
            <div class="rank-head"><?= html_escape($b['titulo']); ?></div>
            <div class="rank-body">
                <?php if (!$filas) { ?>
                    <div class="rank-vacio"><?= lang('sin_datos'); ?></div>
                <?php } else {
                    foreach ($filas as $i => $r) {
                        $ancho = $tope > 0 ? round(((float) $r->quantity / $tope) * 100, 2) : 0; ?>
                        <div class="rank-fila">
                            <div class="rank-pos"><?= $i + 1; ?></div>
                            <div class="rank-datos">
                                <div class="rank-nombre" title="<?= html_escape($r->product_name); ?>">
                                    <?= html_escape($r->product_name); ?>
                                    <small><?= html_escape($r->product_code); ?></small>
                                </div>
                                <div class="rank-barra"><span style="width:<?= $ancho; ?>%"></span></div>
                            </div>
                            <div class="rank-cant"><?= $this->tec->formatQuantity($r->quantity); ?></div>
                        </div>
                    <?php }
                } ?>
            </div>
        </div>
    <?php } ?>
</div>

<style>
    .rank-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px}
    .rank-card{border:1px solid var(--nxf-border,#2a3444);border-radius:12px;overflow:hidden;
        background:var(--nxf-surface,rgba(148,163,184,.04))}
    .rank-head{padding:12px 16px;font-weight:600;font-size:14px;border-bottom:1px solid var(--nxf-border,#2a3444)}
    .rank-body{padding:12px 16px;display:flex;flex-direction:column;gap:10px}
    .rank-vacio{opacity:.6;font-size:13px;padding:16px 0;text-align:center}
    .rank-fila{display:flex;align-items:center;gap:10px}
    .rank-pos{width:22px;text-align:center;font-size:12px;opacity:.6}
    .rank-datos{flex:1;min-width:0}
    .rank-nombre{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .rank-nombre small{opacity:.55;margin-left:6px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
    .rank-barra{height:7px;border-radius:4px;background:rgba(148,163,184,.15);margin-top:4px;overflow:hidden}
    .rank-barra span{display:block;height:100%;border-radius:4px;background:var(--nxf-accent,#38bdf8)}
    .rank-cant{width:64px;text-align:right;font-size:13px;font-variant-numeric:tabular-nums}
</style>
