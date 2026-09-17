<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Cuerpo del modal de ficha. Lo pide el listado por AJAX y lo inyecta en su
 * propia capa, asi que aqui no van ni <html> ni la envoltura del modal.
 */
$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_cerrar = '<path d="M18 6l-12 12"/><path d="M6 6l12 12"/>';
$ico_editar = '<path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3z"/>';
$ico_lista  = '<path d="M9 6l11 0"/><path d="M9 12l11 0"/><path d="M9 18l11 0"/><path d="M5 6l0 .01"/><path d="M5 12l0 .01"/><path d="M5 18l0 .01"/>';
$ico_caja   = '<path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12l0 9"/><path d="M12 12l-8 -4.5"/>';

$r = $resumen;
$n_productos = (int) $r->productos;
$servicios   = (int) $r->servicios;
$existencias = (float) $r->existencias;
$sin_stock   = (int) $r->sin_existencias;

// Las iniciales sustituyen a la imagen cuando la categoria no tiene ninguna.
$iniciales = mb_strtoupper(mb_substr(trim($category->name), 0, 2));
$miniatura = (!empty($category->image) && $category->image !== 'no_image.png'
              && is_file('uploads/thumbs/' . $category->image))
    ? base_url('uploads/thumbs/' . $category->image)
    : NULL;
$grande = !empty($category->image) && is_file('uploads/' . $category->image)
    ? base_url('uploads/' . $category->image)
    : $miniatura;
?>

<div class="nxc-cab">
    <?php if ($miniatura) { ?>
        <img class="nxc-cab-img" src="<?= $miniatura; ?>" alt="" data-full="<?= $grande; ?>">
    <?php } else { ?>
        <div class="nxc-cab-ico"><?= html_escape($iniciales); ?></div>
    <?php } ?>
    <div class="nxc-cab-txt">
        <div class="nxc-cab-tit"><?= html_escape($category->name); ?></div>
        <div class="nxc-cab-sub">
            <span class="nxt-code"><?= html_escape($category->code); ?></span>
            <span><?= $n_productos; ?> <?= mb_strtolower(lang('cat_productos')); ?></span>
        </div>
    </div>
    <button type="button" class="nxc-x" data-nxc-cerrar aria-label="<?= lang('close'); ?>">
        <?= $icono($ico_cerrar, 17); ?>
    </button>
</div>

<div class="nxc-cuerpo">

    <div class="nxc-kpis">
        <div class="nxc-kpi info">
            <span class="nxc-kpi-n"><?= $n_productos; ?></span>
            <span class="nxc-kpi-t"><?= lang('cat_productos'); ?></span>
            <?php if ($servicios > 0) { ?>
                <span class="nxc-kpi-pie"><?= $servicios; ?> <?= mb_strtolower(lang('cat_servicios')); ?></span>
            <?php } ?>
        </div>
        <div class="nxc-kpi <?= $sin_stock > 0 ? 'warn' : 'ok'; ?>">
            <span class="nxc-kpi-n"><?= $this->tec->formatNumber($existencias); ?></span>
            <span class="nxc-kpi-t"><?= lang('cat_existencias'); ?></span>
            <?php if ($sin_stock > 0) { ?>
                <span class="nxc-kpi-pie"><?= $sin_stock; ?> <?= mb_strtolower(lang('cat_sin_existencias')); ?></span>
            <?php } ?>
        </div>
        <?php if ($Admin) { ?>
        <div class="nxc-kpi mudo">
            <span class="nxc-kpi-n"><?= $this->tec->formatMoney($r->valor_costo); ?></span>
            <span class="nxc-kpi-t"><?= lang('cat_valor_costo'); ?></span>
        </div>
        <?php } ?>
        <div class="nxc-kpi ok">
            <span class="nxc-kpi-n"><?= $this->tec->formatMoney($r->valor_venta); ?></span>
            <span class="nxc-kpi-t"><?= lang('cat_valor_venta'); ?></span>
        </div>
    </div>

    <?php if ($productos) { ?>
        <div class="nxc-seccion"><?= $icono($ico_caja, 14); ?> <?= lang('cat_primeros'); ?></div>
        <div class="nxc-tabla-wrap">
            <table class="nxc-tabla">
                <thead>
                    <tr>
                        <th><?= lang('code'); ?></th>
                        <th><?= lang('name'); ?></th>
                        <th class="num"><?= lang('cat_existencias'); ?></th>
                        <th class="num"><?= lang('price'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p) {
                        $servicio = ($p->type === 'service');
                        $agotado  = (!$servicio && (float) $p->quantity <= 0);
                    ?>
                        <tr>
                            <td class="mono"><?= html_escape($p->code); ?></td>
                            <td><?= html_escape($p->name); ?></td>
                            <td class="num">
                                <?php if ($servicio) { ?>
                                    <span class="nxc-dim">&mdash;</span>
                                <?php } else { ?>
                                    <span class="nxc-qty<?= $agotado ? ' cero' : ''; ?>"><?= $this->tec->formatNumber($p->quantity); ?></span>
                                <?php } ?>
                            </td>
                            <td class="num mono"><?= $this->tec->formatMoney($p->price); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($n_productos > count($productos)) { ?>
            <div class="nxc-mas"><?= sprintf(lang('cat_en_uso'), $n_productos); ?></div>
        <?php } ?>
    <?php } else { ?>
        <div class="nxc-vacio">
            <?= $icono($ico_caja, 30); ?>
            <p><?= lang('cat_vacia'); ?></p>
        </div>
    <?php } ?>
</div>

<div class="nxc-pie">
    <?php if ($n_productos > 0) { ?>
        <a class="nxf-btn nxf-btn-sm nxf-btn-ghost"
           href="<?= site_url('products') . '?cat=' . rawurlencode($category->name); ?>">
            <?= $icono($ico_lista, 14); ?> <?= lang('cat_ver_todos'); ?>
        </a>
    <?php } ?>
    <span class="nxf-spacer"></span>
    <?php if ($Admin) { ?>
        <a class="nxf-btn nxf-btn-sm" href="<?= site_url('categories/edit/' . $category->id); ?>">
            <?= $icono($ico_editar, 14); ?> <?= lang('edit_category'); ?>
        </a>
    <?php } ?>
</div>
