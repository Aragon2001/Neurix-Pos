<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Formulario compartido por products/add y products/edit.
 * $modo vale 'add' o 'edit'; en 'edit' llegan ademas $product, $ubicaciones,
 * $stores_quantities, $product_prices y, si es combo, $items.
 */
$nuevo = ($modo === 'add');
$p     = (isset($product) && $product) ? $product : NULL;

$val = function ($campo, $def = '') use ($p) {
    return set_value($campo, ($p && isset($p->$campo)) ? $p->$campo : $def);
};

// Existencia y precio por tienda, indexados para no recorrer en cada fila.
$por_tienda = array();
if (!empty($stores_quantities)) {
    foreach ((array) $stores_quantities as $sq) {
        if (is_object($sq)) { $por_tienda[$sq->store_id] = $sq; }
    }
}

$precios_lista = array();
if (!empty($product_prices)) {
    foreach ((array) $product_prices as $pp) {
        $precios_lista[$pp->price_group_id] = $pp;
    }
}

$unidades = array('Unid', 'Sp', 'kg', 'g', 'L', 'ml', 'm', 'cm', 'Al', 'Alc', 'Cm', 'I', 'Otros');

$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_buscar = '<circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/>';
$ico_check  = '<path d="M5 12l5 5l10 -10"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= $nuevo ? lang('add_product') : lang('edit_product'); ?>
        <small><?= lang('producto_ayuda'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('products'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('products'); ?>
        </a>
    </div>
</div>

<?= form_open_multipart($nuevo ? 'products/add' : 'products/edit/' . $p->id, 'id="nxfProducto"'); ?>
<div class="nxf-page nxf-page-wide">

    <?php if (!empty($error)) { ?>
        <div class="nxf-note nxf-note-err"><div><?= $error; ?></div></div>
    <?php } ?>

    <!-- ── Paso 1: identificación ── -->
    <div class="nxf-card nxf-card-c1">
        <div class="nxf-card-head">
            <span class="nxf-step">1</span>
            <div class="nxf-card-title"><?= lang('producto_paso_identificacion'); ?></div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="code"><?= lang('product_code'); ?> <span class="req">*</span></label>
                    <input type="text" name="code" id="code" class="nxf-input mono" required
                           minlength="2" maxlength="50" pattern="[A-Za-z0-9]+" value="<?= $val('code'); ?>">
                    <div class="nxf-hint"><?= lang('producto_codigo_ayuda'); ?></div>
                </div>
                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="name"><?= lang('product_name'); ?> <span class="req">*</span></label>
                    <input type="text" name="name" id="name" class="nxf-input" required maxlength="150"
                           value="<?= $val('name'); ?>">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="type"><?= lang('type'); ?></label>
                    <select name="type" id="type" class="nxf-select">
                        <?php foreach (array('standard' => lang('standard'), 'service' => lang('service'), 'combo' => lang('combo')) as $k => $etiqueta) { ?>
                            <option value="<?= $k; ?>" <?= $val('type', 'standard') === $k ? 'selected' : ''; ?>><?= $etiqueta; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="category"><?= lang('category'); ?> <span class="req">*</span></label>
                    <select name="category" id="category" class="nxf-select" required>
                        <option value="">— <?= lang('Seleccione'); ?> —</option>
                        <?php foreach ((array) $categories as $c) { ?>
                            <option value="<?= $c->id; ?>" <?= $val('category_id') == $c->id ? 'selected' : ''; ?>>
                                <?= html_escape($c->name); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="barcode_symbology"><?= lang('barcode_symbology'); ?></label>
                    <select name="barcode_symbology" id="barcode_symbology" class="nxf-select">
                        <?php foreach (array('code128', 'code39', 'upca', 'upce', 'ean8', 'ean13') as $sim) { ?>
                            <option value="<?= $sim; ?>" <?= $val('barcode_symbology', 'code128') === $sim ? 'selected' : ''; ?>><?= $sim; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="userfile"><?= lang('product_image'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="file" name="userfile" id="userfile" class="nxf-input" accept="image/*">
                    <?php if ($p && $p->image) { ?>
                        <div class="nxf-hint"><?= html_escape($p->image); ?></div>
                    <?php } ?>
                </div>

                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="details"><?= lang('product_details'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <textarea name="details" id="details" class="nxf-textarea" maxlength="500"><?= $val('details'); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 2: fiscal ── -->
    <div class="nxf-card nxf-card-c2">
        <div class="nxf-card-head">
            <span class="nxf-step">2</span>
            <div class="nxf-card-title">
                <?= lang('producto_paso_fiscal'); ?>
                <small><?= lang('producto_paso_fiscal_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-12">
                    <label class="nxf-label" for="cabys"><?= lang('codigo_cabys'); ?> <span class="req">*</span></label>
                    <div class="nxf-cabys-box">
                        <div class="nxf-cabys-info">
                            <div class="nxf-cabys-code" id="cabysCodigoTxt"><?= $val('cabys') ? html_escape($val('cabys')) : '— — — — — — — — — — — — —'; ?></div>
                            <div class="nxf-cabys-desc" id="cabysDescTxt"></div>
                        </div>
                        <button type="button" class="nxf-btn" id="btnCabys">
                            <?= $icono($ico_buscar); ?> <?= lang('buscar_cabys'); ?>
                        </button>
                    </div>
                    <input type="hidden" name="cabys" id="cabys" value="<?= $val('cabys'); ?>">
                    <div class="nxf-hint"><?= lang('cabys_help_text'); ?></div>
                    <div class="nxf-note" id="cabysAviso" hidden></div>
                </div>

                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="product_tax"><?= lang('product_tax'); ?> <span class="req">*</span></label>
                    <select name="product_tax" id="product_tax" class="nxf-select" required>
                        <?php foreach ((array) $impuestos as $imp) { ?>
                            <option value="<?= $imp->id_impuesto; ?>" data-tasa="<?= $imp->tasa_impuesto; ?>"
                                    <?= $val('id_tax') == $imp->id_impuesto ? 'selected' : ''; ?>>
                                <?= html_escape($imp->descripcion_impuesto); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <?php /* El controlador lee la tasa de `pit{id}` al guardar; fuera del <select>
                              porque un <input> como hijo directo le hace romper el parseo del navegador
                              y expone todas las <option> como texto suelto en la pagina. */ ?>
                    <?php foreach ((array) $impuestos as $imp) { ?>
                        <input type="hidden" name="pit<?= $imp->id_impuesto; ?>" value="<?= $imp->tasa_impuesto; ?>">
                    <?php } ?>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="tax_method"><?= lang('tax_method'); ?></label>
                    <select name="tax_method" id="tax_method" class="nxf-select">
                        <option value="1" <?= $val('tax_method', '1') == '1' ? 'selected' : ''; ?>><?= lang('exclusive'); ?></option>
                        <option value="0" <?= $val('tax_method') === '0' ? 'selected' : ''; ?>><?= lang('inclusive'); ?></option>
                    </select>
                    <div class="nxf-hint"><?= lang('tax_method_ayuda'); ?></div>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="unit_of_measurement"><?= lang('unit_of_measurement'); ?> <span class="req">*</span></label>
                    <select name="unit_of_measurement" id="unit_of_measurement" class="nxf-select" required>
                        <?php foreach ($unidades as $u) { ?>
                            <option value="<?= $u; ?>" <?= $val('unit_of_measurement', 'Unid') === $u ? 'selected' : ''; ?>><?= $u; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 3: precios ── -->
    <div class="nxf-card nxf-card-c3">
        <div class="nxf-card-head">
            <span class="nxf-step">3</span>
            <div class="nxf-card-title">
                <?= lang('producto_paso_precios'); ?>
                <small><?= lang('producto_paso_precios_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="cost"><?= lang('product_cost'); ?> <span class="req" data-solo-producto>*</span></label>
                    <input type="number" step="any" min="0" name="cost" id="cost" class="nxf-input mono"
                           value="<?= $val('cost', '0'); ?>">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="margen"><?= lang('margen'); ?> (%)</label>
                    <input type="number" step="any" name="margen" id="margen" class="nxf-input mono"
                           value="<?= $val('margen', '0'); ?>">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="price"><?= lang('product_price'); ?> <span class="req">*</span></label>
                    <input type="number" step="any" min="0" name="price" id="price" class="nxf-input mono" required
                           value="<?= $val('price', '0'); ?>">
                </div>
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="offer_price"><?= lang('offer_price'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <input type="number" step="any" min="0" name="offer_price" id="offer_price" class="nxf-input mono"
                           value="<?= $val('offer_price', '0'); ?>">
                </div>

                <div class="nxf-field sp-12">
                    <div class="nxf-hint" id="hintMargen"></div>
                </div>

                <?php if (!empty($prices)) { ?>
                    <div class="nxf-field sp-12">
                        <label class="nxf-label"><?= lang('listas_de_precios'); ?></label>
                        <div class="prod-tabla-wrap">
                            <table class="prod-tabla">
                                <thead>
                                    <tr>
                                        <th><?= lang('lista'); ?></th>
                                        <th class="num"><?= lang('margen'); ?> (%)</th>
                                        <th class="num"><?= lang('price'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ((array) $prices as $lp) {
                                    $actual = isset($precios_lista[$lp->id_lista_precios]) ? $precios_lista[$lp->id_lista_precios] : NULL; ?>
                                    <tr>
                                        <td>
                                            <?= html_escape($lp->nombre_l_precio); ?>
                                            <input type="hidden" name="id_lista_precio[]" value="<?= $lp->id_lista_precios; ?>">
                                            <?php if (!$nuevo) { ?>
                                                <input type="hidden" name="id_product_prices[]" value="<?= $actual ? $actual->id_product_prices : ''; ?>">
                                            <?php } ?>
                                        </td>
                                        <td class="num">
                                            <input type="number" step="any" name="listmargen[]" class="lista-margen"
                                                   value="<?= $actual ? $actual->margen : 0; ?>">
                                        </td>
                                        <td class="num">
                                            <input type="number" step="any" name="listprice[]" class="lista-precio"
                                                   value="<?= $actual ? $actual->price : 0; ?>">
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="nxf-hint"><?= lang('listas_precios_ayuda'); ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- ── Paso 4: inventario ── -->
    <div class="nxf-card nxf-card-c4" data-solo-producto>
        <div class="nxf-card-head">
            <span class="nxf-step">4</span>
            <div class="nxf-card-title">
                <?= lang('producto_paso_inventario'); ?>
                <small><?= lang('producto_paso_inventario_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-3">
                    <label class="nxf-label" for="alert_quantity"><?= lang('alert_quantity'); ?> <span class="req">*</span></label>
                    <input type="number" step="any" min="0" name="alert_quantity" id="alert_quantity" class="nxf-input mono" required
                           value="<?= $val('alert_quantity', '0'); ?>">
                    <div class="nxf-hint"><?= lang('alert_quantity_ayuda'); ?></div>
                </div>

                <div class="nxf-field sp-6">
                    <label class="nxf-label" for="supplier_id"><?= lang('proveedor_preferido'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <select name="supplier_id" id="supplier_id" class="nxf-select">
                        <option value=""><?= lang('sin_proveedor'); ?></option>
                        <?php foreach ((array) ($proveedores ?? array()) as $pv) { ?>
                            <option value="<?= $pv->id; ?>" <?= (string) $val('supplier_id') === (string) $pv->id ? 'selected' : ''; ?>><?= html_escape($pv->company ? $pv->company . ' · ' . $pv->name : $pv->name); ?></option>
                        <?php } ?>
                    </select>
                    <div class="nxf-hint"><?= lang('proveedor_preferido_ayuda'); ?></div>
                </div>

                <?php if ($Settings->multi_store) {
                    foreach ((array) $stores as $store) {
                        $sq = isset($por_tienda[$store->id]) ? $por_tienda[$store->id] : NULL; ?>
                        <div class="nxf-field sp-3">
                            <label class="nxf-label" for="quantity<?= $store->id; ?>">
                                <?= lang('quantity'); ?> — <?= html_escape($store->name); ?> <span class="req">*</span>
                            </label>
                            <input type="number" step="any" name="quantity<?= $store->id; ?>" id="quantity<?= $store->id; ?>"
                                   class="nxf-input mono" value="<?= $sq ? $sq->quantity : 0; ?>" required <?= $nuevo ? '' : 'readonly'; ?>>
                            <?php if (!$nuevo) { ?><div class="nxf-hint"><?= lang('existencia_solo_ajuste'); ?></div><?php } ?>
                        </div>
                        <?php if ($Settings->enable_fractions == 1) { ?>
                            <div class="nxf-field sp-3">
                                <label class="nxf-label" for="qty_fracc<?= $store->id; ?>"><?= lang('cantidad_fracciones'); ?></label>
                                <input type="number" step="any" name="qty_fracc<?= $store->id; ?>" id="qty_fracc<?= $store->id; ?>"
                                       class="nxf-input mono" value="<?= $sq ? $sq->qty_fracc : 0; ?>" <?= $nuevo ? '' : 'readonly'; ?>>
                            </div>
                        <?php } ?>
                        <div class="nxf-field sp-3">
                            <label class="nxf-label" for="price<?= $store->id; ?>"><?= lang('price'); ?> — <?= html_escape($store->name); ?></label>
                            <input type="number" step="any" name="price<?= $store->id; ?>" id="price<?= $store->id; ?>"
                                   class="nxf-input mono" value="<?= $sq ? $sq->price : ''; ?>"
                                   placeholder="<?= lang('optional'); ?>">
                        </div>
                    <?php }
                } else {
                    $sq = isset($por_tienda[1]) ? $por_tienda[1] : (is_object($stores_quantities ?? null) ? $stores_quantities : NULL); ?>
                    <div class="nxf-field sp-3">
                        <label class="nxf-label" for="quantity"><?= lang('quantity'); ?> <span class="req">*</span></label>
                        <input type="number" step="any" name="quantity" id="quantity" class="nxf-input mono"
                               value="<?= $sq ? $sq->quantity : 0; ?>" required <?= $nuevo ? '' : 'readonly'; ?>>
                        <?php if (!$nuevo) { ?><div class="nxf-hint"><?= lang('existencia_solo_ajuste'); ?></div><?php } ?>
                    </div>
                    <?php if ($Settings->enable_fractions == 1) { ?>
                        <div class="nxf-field sp-3">
                            <label class="nxf-label" for="qty_fracc"><?= lang('cantidad_fracciones'); ?></label>
                            <input type="number" step="any" name="qty_fracc" id="qty_fracc" class="nxf-input mono"
                                   value="<?= $sq ? $sq->qty_fracc : 0; ?>" <?= $nuevo ? '' : 'readonly'; ?>>
                        </div>
                    <?php }
                } ?>

                <div class="nxf-field sp-12">
                    <label class="nxf-label"><?= lang('ubicaciones'); ?><span class="opt"><?= lang('opcional'); ?></span></label>
                    <div class="nxf-hint nxf-ubi-intro"><?= lang('ubicaciones_ayuda'); ?></div>

                    <div class="nxf-ubi-box">
                        <div class="nxf-ubi-head">
                            <span><?= lang('seccion'); ?></span>
                            <span><?= lang('tramo'); ?></span>
                            <span></span>
                        </div>
                        <div class="nxf-ubi-list" id="cuerpoUbicaciones">
                            <?php foreach ((array) (!empty($ubicaciones) ? $ubicaciones : array()) as $u) { ?>
                                <div class="nxf-ubi-row">
                                    <input type="text" name="seccion[]" class="nxf-input" maxlength="50"
                                           placeholder="<?= lang('seccion_placeholder'); ?>" value="<?= html_escape($u->seccion); ?>">
                                    <input type="text" name="tramo[]" class="nxf-input" maxlength="50"
                                           placeholder="<?= lang('tramo_placeholder'); ?>" value="<?= html_escape($u->tramo); ?>">
                                    <button type="button" class="nxf-ubi-quitar" data-quitar-ubicacion title="<?= lang('quitar_ubicacion'); ?>" aria-label="<?= lang('quitar_ubicacion'); ?>">&times;</button>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="nxf-ubi-empty" id="ubiVacio" <?= empty($ubicaciones) ? '' : 'hidden'; ?>><?= lang('sin_ubicacion'); ?></div>
                    </div>
                    <button type="button" class="nxf-btn nxf-btn-sm nxf-btn-ghost" id="agregarUbicacion">
                        + <?= lang('agregar_ubicacion'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Paso 5: presentaciones ── -->
    <?php if ($Settings->enable_fractions == 1) { ?>
    <div class="nxf-card nxf-card-c5" data-solo-producto>
        <div class="nxf-card-head">
            <span class="nxf-step">5</span>
            <div class="nxf-card-title">
                <?= lang('producto_paso_presentaciones'); ?>
                <small><?= lang('producto_paso_presentaciones_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-grid">
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="present_caja"><?= lang('present_caja'); ?></label>
                    <select name="present_caja" id="present_caja" class="nxf-select">
                        <option value="0" <?= $val('present_caja') == '0' ? 'selected' : ''; ?>><?= lang('no'); ?></option>
                        <option value="1" <?= $val('present_caja') == '1' ? 'selected' : ''; ?>><?= lang('yes'); ?></option>
                    </select>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="present_fraccion"><?= lang('present_fraccion'); ?></label>
                    <select name="present_fraccion" id="present_fraccion" class="nxf-select">
                        <option value="0" <?= $val('present_fraccion') == '0' ? 'selected' : ''; ?>><?= lang('no'); ?></option>
                        <option value="1" <?= $val('present_fraccion') == '1' ? 'selected' : ''; ?>><?= lang('yes'); ?></option>
                    </select>
                </div>
                <div class="nxf-field sp-4">
                    <label class="nxf-label" for="caja_fraccionada"><?= lang('caja_fraccionada'); ?></label>
                    <input type="number" step="1" min="0" name="caja_fraccionada" id="caja_fraccionada" class="nxf-input mono"
                           value="<?= $val('caja_fraccionada', '0'); ?>">
                    <div class="nxf-hint"><?= lang('caja_fraccionada_ayuda'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- ── Combo ── -->
    <div class="nxf-card nxf-card-c6" id="tarjetaCombo" hidden>
        <div class="nxf-card-head">
            <span class="nxf-step">+</span>
            <div class="nxf-card-title">
                <?= lang('combo'); ?>
                <small><?= lang('combo_ayuda'); ?></small>
            </div>
        </div>
        <div class="nxf-card-body">
            <div class="nxf-field">
                <label class="nxf-label" for="comboBuscar"><?= lang('search_product_by_name_code'); ?></label>
                <input type="text" id="comboBuscar" class="nxf-input" autocomplete="off">
                <div id="comboSugerencias" class="cabys-lista" style="display:none;"></div>
            </div>
            <div class="prod-tabla-wrap">
                <table class="prod-tabla">
                    <thead>
                        <tr>
                            <th><?= lang('product'); ?></th>
                            <th class="num"><?= lang('quantity'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoCombo">
                        <?php foreach ((array) (!empty($items) ? $items : array()) as $it) { ?>
                            <tr>
                                <td><?= html_escape($it['row']->name); ?>
                                    <input type="hidden" name="combo_item_code[]" value="<?= html_escape($it['row']->code); ?>"></td>
                                <td class="num"><input type="number" step="any" min="0" name="combo_item_quantity[]" value="<?= $it['row']->qty; ?>"></td>
                                <td><button type="button" class="quitar" data-quitar-combo aria-label="x">&times;</button></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="nxf-actions">
        <a class="nxf-btn nxf-btn-ghost" href="<?= site_url('products'); ?>"><?= lang('cancel'); ?></a>
        <span class="nxf-spacer"></span>
        <button type="submit" class="nxf-btn" name="<?= $nuevo ? 'add_product' : 'edit_product'; ?>" value="1">
            <?= $icono($ico_check); ?> <?= $nuevo ? lang('add_product') : lang('save'); ?>
        </button>
    </div>
</div>
<?= form_close(); ?>

<!-- ── Modal de búsqueda CABYS ── -->
<div class="nxf-modal-back" id="cabysModal" hidden>
    <div class="nxf-modal" role="dialog" aria-modal="true" aria-labelledby="cabysModalTitulo">
        <div class="nxf-modal-head">
            <div class="nxf-modal-title" id="cabysModalTitulo"><?= $icono($ico_buscar, 18); ?> <?= lang('buscar_cabys'); ?></div>
            <button type="button" class="nxt-icon-btn" id="cabysModalCerrar" title="<?= lang('close'); ?>"><i class="fa fa-times"></i></button>
        </div>
        <div class="nxf-modal-body">
            <input type="text" id="cabysModalBuscar" class="nxf-input nxf-modal-search" autocomplete="off"
                   placeholder="<?= lang('buscar_cabys_placeholder'); ?>">
            <div class="nxf-hint"><?= lang('cabys_help_text'); ?></div>
            <div class="nxf-cabys-results" id="cabysModalResultados">
                <div class="nxf-cabys-empty"><?= lang('buscar_cabys_desc_min3'); ?></div>
            </div>
        </div>
    </div>
</div>

<style>
    .cabys-lista{margin-top:8px;border:1px solid var(--nxf-border,#2a3444);border-radius:10px;overflow:hidden;max-height:300px;overflow-y:auto}
    .cabys-op{padding:9px 12px;cursor:pointer;font-size:13px;display:flex;justify-content:space-between;gap:12px}
    .cabys-op:hover{background:rgba(56,189,248,.12)}
    .cabys-op small{opacity:.65;white-space:nowrap}
    .prod-tabla-wrap{overflow-x:auto}
    .prod-tabla{width:100%;border-collapse:collapse;font-size:13px}
    .prod-tabla th,.prod-tabla td{padding:7px 9px;border-bottom:1px solid var(--nxf-border,#2a3444);text-align:left}
    .prod-tabla th.num,.prod-tabla td.num{text-align:right}
    .prod-tabla input{width:100%;padding:6px 8px;border-radius:7px;border:1px solid var(--nxf-border,#2a3444);
        background:var(--nxf-surface-2,rgba(148,163,184,.06));color:inherit}
    .prod-tabla td.num input{text-align:right}
    .prod-tabla .quitar{background:none;border:0;color:#f87171;cursor:pointer;font-size:16px;line-height:1}

    /* ── Formulario de producto: ancho completo y pasos coloreados ── */
    @media (min-width:1100px){ .nxf-page-wide{max-width:1180px} }

    #nxfProducto .nxf-card-c1 .nxf-step{background:linear-gradient(135deg,var(--nx-a1),var(--nx-info))}
    #nxfProducto .nxf-card-c2 .nxf-step{background:linear-gradient(135deg,var(--nx-violet),var(--nx-indigo));box-shadow:0 4px 12px -4px color-mix(in srgb,var(--nx-violet) 60%,transparent)}
    #nxfProducto .nxf-card-c3 .nxf-step{background:linear-gradient(135deg,var(--nx-emerald),var(--nx-teal));box-shadow:0 4px 12px -4px color-mix(in srgb,var(--nx-emerald) 60%,transparent)}
    #nxfProducto .nxf-card-c4 .nxf-step{background:linear-gradient(135deg,var(--nx-amber),var(--nx-orange));box-shadow:0 4px 12px -4px color-mix(in srgb,var(--nx-amber) 60%,transparent)}
    #nxfProducto .nxf-card-c5 .nxf-step{background:linear-gradient(135deg,var(--nx-orange),var(--nx-rose));box-shadow:0 4px 12px -4px color-mix(in srgb,var(--nx-orange) 60%,transparent)}
    #nxfProducto .nxf-card-c6 .nxf-step{background:linear-gradient(135deg,var(--nx-pink),var(--nx-violet));box-shadow:0 4px 12px -4px color-mix(in srgb,var(--nx-pink) 60%,transparent)}
    #nxfProducto .nxf-card-c2{border-top:3px solid color-mix(in srgb,var(--nx-violet) 55%,transparent)}
    #nxfProducto .nxf-card-c3{border-top:3px solid color-mix(in srgb,var(--nx-emerald) 55%,transparent)}
    #nxfProducto .nxf-card-c4{border-top:3px solid color-mix(in srgb,var(--nx-amber) 55%,transparent)}
    #nxfProducto .nxf-card-c5{border-top:3px solid color-mix(in srgb,var(--nx-orange) 55%,transparent)}
    #nxfProducto .nxf-card-c6{border-top:3px solid color-mix(in srgb,var(--nx-pink) 55%,transparent)}

    /* ── Selector de CABYS elegido ── */
    .nxf-cabys-box{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:12px 16px;
        background:var(--nx-card-bg2);border:1px solid var(--nx-border);border-radius:var(--nx-radius)}
    .nxf-cabys-info{flex:1 1 260px;min-width:0}
    .nxf-cabys-code{font-family:ui-monospace,"SFMono-Regular",Menlo,Consolas,monospace;font-size:16px;
        font-weight:700;letter-spacing:.04em;color:var(--nx-txt1)}
    .nxf-cabys-desc{font-size:12.5px;color:var(--nx-txt3);margin-top:2px}

    /* ── Modal de búsqueda CABYS ── */
    .nxf-modal-back{position:fixed;inset:0;z-index:1200;display:flex;align-items:center;justify-content:center;
        padding:24px;background:rgba(6,9,20,.6);backdrop-filter:blur(4px)}
    .nxf-modal{width:100%;max-width:720px;max-height:min(680px,88vh);display:flex;flex-direction:column;
        background:var(--nx-card-bg);border:1px solid var(--nx-border);border-radius:var(--nx-radius-lg);
        box-shadow:var(--nx-shadow);overflow:hidden}
    .nxf-modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;
        padding:16px 20px;border-bottom:1px solid var(--nx-border);background:var(--nx-card-bg2)}
    .nxf-modal-title{display:flex;align-items:center;gap:9px;font-size:15.5px;font-weight:700;color:var(--nx-txt1)}
    .nxf-modal-body{padding:18px 20px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;min-height:0}
    .nxf-modal-search{font-size:15px;padding:12px 14px}

    .nxf-cabys-results{margin-top:8px;display:flex;flex-direction:column;gap:8px;overflow-y:auto}
    .nxf-cabys-empty{padding:26px 10px;text-align:center;font-size:13px;color:var(--nx-txt4)}
    .nxf-cabys-row{display:flex;align-items:center;gap:14px;padding:12px 14px;border:1px solid var(--nx-border);
        border-radius:var(--nx-radius-sm);background:var(--nx-card-bg2);cursor:pointer;text-align:left;
        transition:var(--nx-transition)}
    .nxf-cabys-row:hover{border-color:var(--nx-border3);background:var(--nx-hover-bg);transform:translateY(-1px)}
    .nxf-cabys-row-txt{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px}
    .nxf-cabys-row-desc{display:block;font-size:13.5px;font-weight:600;color:var(--nx-txt1);line-height:1.35}
    .nxf-cabys-row-code{display:inline-block;width:fit-content;font-family:ui-monospace,"SFMono-Regular",Menlo,Consolas,monospace;
        font-size:11px;font-weight:600;letter-spacing:.03em;color:var(--nx-txt3);
        background:var(--nx-bg5);border:1px solid var(--nx-border);border-radius:6px;padding:2px 7px}
    .nxf-cabys-row-tax{flex:0 0 auto;font:700 12px inherit;padding:5px 11px;border-radius:999px;white-space:nowrap;
        color:var(--nx-a1);background:color-mix(in srgb,var(--nx-a1) 14%,transparent);
        border:1px solid color-mix(in srgb,var(--nx-a1) 35%,transparent)}
    body.nxf-modal-open{overflow:hidden}

    /* ── Ubicaciones: zona (área) + posición (punto exacto dentro de ella) ── */
    .nxf-ubi-intro{margin-top:0;margin-bottom:10px}
    .nxf-ubi-box{border:1px solid var(--nx-border);border-radius:var(--nx-radius);overflow:hidden;background:var(--nx-card-bg2)}
    .nxf-ubi-head{display:grid;grid-template-columns:1fr 1fr 34px;gap:10px;padding:9px 14px;
        font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--nx-txt4);
        border-bottom:1px solid var(--nx-border)}
    .nxf-ubi-list{display:flex;flex-direction:column}
    .nxf-ubi-row{display:grid;grid-template-columns:1fr 1fr 34px;gap:10px;align-items:center;
        padding:8px 14px;border-bottom:1px solid var(--nx-border2)}
    .nxf-ubi-row:last-child{border-bottom:0}
    .nxf-ubi-row .nxf-input{background:var(--nx-input-bg)}
    .nxf-ubi-quitar{
        width:30px;height:30px;border-radius:8px;border:1px solid var(--nx-border);background:var(--nx-card-bg);
        color:var(--nx-txt4);cursor:pointer;font-size:17px;line-height:1;transition:var(--nx-transition);
    }
    .nxf-ubi-quitar:hover{color:var(--nx-err);border-color:color-mix(in srgb,var(--nx-err) 45%,transparent);
        background:color-mix(in srgb,var(--nx-err) 10%,transparent)}
    .nxf-ubi-empty{padding:20px 14px;text-align:center;font-size:12.5px;color:var(--nx-txt4)}
    @media (max-width:640px){
        .nxf-ubi-head{display:none}
        .nxf-ubi-row{grid-template-columns:1fr;gap:8px;padding:12px 14px}
        .nxf-ubi-quitar{justify-self:end}
    }
</style>

<script>
(function () {
    'use strict';

    var URL_CABYS = '<?= site_url('hacienda_proxy/cabys'); ?>';
    var URL_PROD  = '<?= site_url('products/suggestions'); ?>';

    var T = {
        cabysFalta:  <?= json_encode(lang('import_cabys_invalido')); ?>,
        cabysHallado:<?= json_encode(lang('cabys_encontrado')); ?>,
        sinResp:     <?= json_encode(lang('hacienda_sin_respuesta')); ?>,
        sinResultados:<?= json_encode(lang('sin_resultados')); ?>,
        buscando:    <?= json_encode(lang('buscando')); ?>,
        margenCalc:  <?= json_encode(lang('margen_calculado')); ?>
    };

    var $ = function (id) { return document.getElementById(id); };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }
    function num(v) { var n = parseFloat(String(v).replace(',', '.')); return isNaN(n) ? 0 : n; }

    function nota(el, tono, texto) {
        el.className = 'nxf-note nxf-note-' + tono;
        el.textContent = texto;
        el.hidden = false;
    }

    /* ── Un servicio no lleva existencia ni presentaciones ── */
    function alternarTipo() {
        var servicio = $('type').value === 'service';
        document.querySelectorAll('[data-solo-producto]').forEach(function (el) { el.hidden = servicio; });
        $('tarjetaCombo').hidden = ($('type').value !== 'combo');
        $('cost').required = !servicio;
        if (servicio) { $('unit_of_measurement').value = 'Sp'; }
    }
    $('type').addEventListener('change', alternarTipo);

    /* ── Margen, costo y precio se despejan entre sí ── */
    function desdeMargen() {
        var c = num($('cost').value), m = num($('margen').value);
        if (c > 0) { $('price').value = (c * (1 + m / 100)).toFixed(2); }
        pintarMargen();
    }
    function desdePrecio() {
        var c = num($('cost').value), p = num($('price').value);
        if (c > 0) { $('margen').value = (((p - c) / c) * 100).toFixed(2); }
        pintarMargen();
    }
    function pintarMargen() {
        var c = num($('cost').value), p = num($('price').value);
        $('hintMargen').textContent = (c > 0 && p > 0)
            ? T.margenCalc.replace('%1', (p - c).toFixed(2)).replace('%2', (((p - c) / c) * 100).toFixed(2))
            : '';
    }
    $('margen').addEventListener('input', desdeMargen);
    $('cost').addEventListener('input', desdeMargen);
    $('price').addEventListener('input', desdePrecio);

    /* ── CABYS: se elige siempre desde el modal de búsqueda, nunca a mano ── */
    // El CABYS trae la tarifa que le corresponde: se marca la del catálogo.
    function marcarTarifa(tasa) {
        var sel = $('product_tax');
        for (var i = 0; i < sel.options.length; i++) {
            if (num(sel.options[i].dataset.tasa) === num(tasa)) { sel.selectedIndex = i; return; }
        }
    }

    function elegirCabys(codigo, descripcion, tasa) {
        $('cabys').value = codigo;
        $('cabysCodigoTxt').textContent = codigo;
        $('cabysDescTxt').textContent = descripcion || '';
        if (tasa !== undefined && tasa !== null) { marcarTarifa(tasa); }
        nota($('cabysAviso'), 'ok', T.cabysHallado + ' ' + (descripcion || ''));
    }

    function abrirModalCabys() {
        $('cabysModal').hidden = false;
        document.body.classList.add('nxf-modal-open');
        $('cabysModalBuscar').value = '';
        pintarResultadosCabys([], <?= json_encode(lang('buscar_cabys_desc_min3')); ?>);
        setTimeout(function () { $('cabysModalBuscar').focus(); }, 30);
    }
    function cerrarModalCabys() {
        $('cabysModal').hidden = true;
        document.body.classList.remove('nxf-modal-open');
    }

    function pintarResultadosCabys(lista, mensajeVacio) {
        var cont = $('cabysModalResultados');
        if (!lista.length) {
            cont.innerHTML = '<div class="nxf-cabys-empty">' + esc(mensajeVacio) + '</div>';
            return;
        }
        cont.innerHTML = lista.map(function (c) {
            return '<button type="button" class="nxf-cabys-row" data-codigo="' + esc(c.codigo) + '" data-desc="' + esc(c.descripcion) + '" data-tasa="' + esc(c.impuesto) + '">'
                 + '<span class="nxf-cabys-row-txt"><span class="nxf-cabys-row-desc">' + esc(c.descripcion) + '</span>'
                 + '<span class="nxf-cabys-row-code">' + esc(c.codigo) + '</span></span>'
                 + '<span class="nxf-cabys-row-tax">' + num(c.impuesto) + '%</span></button>';
        }).join('');
    }

    $('btnCabys').addEventListener('click', abrirModalCabys);
    $('cabysModalCerrar').addEventListener('click', cerrarModalCabys);
    $('cabysModal').addEventListener('click', function (e) {
        if (e.target === $('cabysModal')) { cerrarModalCabys(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !$('cabysModal').hidden) { cerrarModalCabys(); }
    });

    var tCabysModal;
    $('cabysModalBuscar').addEventListener('input', function () {
        var q = $('cabysModalBuscar').value.trim();
        clearTimeout(tCabysModal);
        if (q.length < 3) { pintarResultadosCabys([], <?= json_encode(lang('buscar_cabys_desc_min3')); ?>); return; }
        tCabysModal = setTimeout(function () {
            $('cabysModalResultados').innerHTML = '<div class="nxf-cabys-empty"><i class="fa fa-spinner fa-spin"></i> ' + esc(T.buscando) + '</div>';
            fetch(URL_CABYS + '?q=' + encodeURIComponent(q), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (lista) {
                    if (!Array.isArray(lista)) { lista = []; }
                    pintarResultadosCabys(lista, T.sinResultados);
                })
                .catch(function () { pintarResultadosCabys([], T.sinResp); });
        }, 300);
    });

    $('cabysModalResultados').addEventListener('click', function (e) {
        var fila = e.target.closest('[data-codigo]');
        if (!fila) { return; }
        elegirCabys(fila.dataset.codigo, fila.dataset.desc, fila.dataset.tasa);
        cerrarModalCabys();
    });

    // Producto existente: se conoce el código pero no su descripción, se completa sola.
    if ($('cabys').value) {
        fetch(URL_CABYS + '?codigo=' + $('cabys').value, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) { if (d && d.descripcion) { $('cabysDescTxt').textContent = d.descripcion; } })
            .catch(function () {});
    }

    /* ── Ubicaciones: zona (área general) + posición (punto exacto dentro) ── */
    function marcarVacioUbicaciones() {
        var vacio = $('ubiVacio');
        if (vacio) { vacio.hidden = $('cuerpoUbicaciones').children.length > 0; }
    }
    $('agregarUbicacion').addEventListener('click', function () {
        var fila = document.createElement('div');
        fila.className = 'nxf-ubi-row';
        fila.innerHTML =
            '<input type="text" name="seccion[]" class="nxf-input" maxlength="50" placeholder="' + esc(<?= json_encode(lang('seccion_placeholder')); ?>) + '">'
          + '<input type="text" name="tramo[]" class="nxf-input" maxlength="50" placeholder="' + esc(<?= json_encode(lang('tramo_placeholder')); ?>) + '">'
          + '<button type="button" class="nxf-ubi-quitar" data-quitar-ubicacion title="' + esc(<?= json_encode(lang('quitar_ubicacion')); ?>) + '" aria-label="' + esc(<?= json_encode(lang('quitar_ubicacion')); ?>) + '">&times;</button>';
        $('cuerpoUbicaciones').appendChild(fila);
        marcarVacioUbicaciones();
        fila.querySelector('input').focus();
    });
    $('cuerpoUbicaciones').addEventListener('click', function (e) {
        if (e.target.closest('[data-quitar-ubicacion]')) {
            e.target.closest('.nxf-ubi-row').remove();
            marcarVacioUbicaciones();
        }
    });

    /* ── Combo ── */
    var tCombo;
    $('comboBuscar').addEventListener('input', function () {
        var q = $('comboBuscar').value.trim();
        clearTimeout(tCombo);
        if (q.length < 2) { $('comboSugerencias').style.display = 'none'; return; }
        tCombo = setTimeout(function () {
            fetch(URL_PROD + '?term=' + encodeURIComponent(q), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (lista) {
                    if (!Array.isArray(lista) || !lista.length || !lista[0].item_id) { $('comboSugerencias').style.display = 'none'; return; }
                    $('comboSugerencias').innerHTML = lista.map(function (x) {
                        return '<div class="cabys-op" data-code="' + esc(x.row.code) + '" data-nombre="' + esc(x.row.name) + '">'
                             + '<span>' + esc(x.label) + '</span></div>';
                    }).join('');
                    $('comboSugerencias').style.display = '';
                })
                .catch(function () {});
        }, 250);
    });

    $('comboSugerencias').addEventListener('click', function (e) {
        var op = e.target.closest('[data-code]');
        if (!op) { return; }
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + esc(op.dataset.nombre)
                     + '<input type="hidden" name="combo_item_code[]" value="' + esc(op.dataset.code) + '"></td>'
                     + '<td class="num"><input type="number" step="any" min="0" name="combo_item_quantity[]" value="1"></td>'
                     + '<td><button type="button" class="quitar" data-quitar-combo aria-label="x">&times;</button></td>';
        $('cuerpoCombo').appendChild(tr);
        $('comboSugerencias').style.display = 'none';
        $('comboBuscar').value = '';
    });
    $('cuerpoCombo').addEventListener('click', function (e) {
        if (e.target.closest('[data-quitar-combo]')) { e.target.closest('tr').remove(); }
    });

    /* El código CABYS viaja en un input oculto (no se puede marcar "required"
       en un <input type="hidden">), así que la validación va al enviar. */
    $('nxfProducto').addEventListener('submit', function (e) {
        if (!$('cabys').value) {
            e.preventDefault();
            nota($('cabysAviso'), 'err', T.cabysFalta);
            $('btnCabys').scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    });

    alternarTipo();
    pintarMargen();
})();
</script>
