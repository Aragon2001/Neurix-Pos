<?php (defined('BASEPATH')) or exit('No direct script access allowed'); ?>

<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?= lang('enter_info'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="col-lg-12">
                        <?= form_open_multipart("products/add", 'class="validation"'); ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <?= lang('type', 'type'); ?>
                                    <?php $opts = array('standard' => lang('standard'), 'combo' => lang('combo'), 'service' => lang('service')); ?>
                                    <?= form_dropdown('type', $opts, set_value('type', 'standard'), 'class="form-control tip select2" id="type"  required="required" style="width:100%;"'); ?>
                                </div>
                                <div class="form-group">
                                    <?= lang('unit_of_measurement', 'unit_of_measurement'); ?>
                                    <?php $opts = array(
                                        'Sp' => lang('Servicios Profesionales'),
                                        'Unid' => ' unidad',

                                        'Oz' => 'Onza',
                                        'g' => 'Gramo',
                                        'kg' => 'Kilogramo',
                                        't' => 'Tonelada',

                                        'mL' => 'Mililitro',
                                        'L' => ' Litro',
                                        'Gal' => 'Galón',

                                        'mm' => 'Milimetro',
                                        'cm' => 'Centimetro',
                                        'm' => 'Metro',
                                        'Km' => 'Kilometro',
                                        'ln' => 'Pulgada',

                                        'm³' => 'Metro cúbico',

                                        's' => 'Segundo',
                                        'min' => 'Minuto',
                                        'h' => 'Hora',
                                        'd' => 'Día',

                                        '1' => 'Uno (indice de refracción)',
                                        'rad' => 'Radián',

                                        'W' => 'Watt',
                                        'C' => 'Coulomb',
                                        'V' => 'Volt',
                                        'Ω' => 'Ohm',
                                    ); ?>
                                    <?= form_dropdown('unit_of_measurement', $opts, set_value('unit_of_measurement', 'Unid'), 'class="form-control tip select2" id = "unit_of_measurement"  required = "required" style = "width:100%;"'); ?>
                                </div>
                                <div class="form-group">
                                    <?= lang('name', 'name'); ?>
                                    <?= form_input('name', set_value('name'), 'class="form-control tip" id = "name"  required = "required"'); ?>
                                </div>

                                <div class="form-group">
                                    <?= lang('code', 'code'); ?> <?= lang('can_use_barcode'); ?>
                                    <?= form_input('code', set_value('code'), 'class="form-control tip" id = "code"  required = "required"'); ?>
                                </div>
                                <div class="form-group all" style="display: none;">
                                    <?= lang("barcode_symbology", "barcode_symbology") ?>
                                    <?php
                                    $bs = array('code25' => 'Code25', 'code39' => 'Code39', 'code128' => 'Code128', 'ean8' => 'EAN8', 'ean13' => 'EAN13', 'upca ' => 'UPC - A', 'upce' => 'UPC - E');
                                    echo form_dropdown('barcode_symbology', $bs, set_value('barcode_symbology', 'code128'), 'class="form-control select2" id = "barcode_symbology" required = "required" style = "width:100%;"');
                                    ?>
                                </div>

                                <div class="form-group">
                                    <?= lang('category', 'category'); ?>
                                    <?php
                                    $cat[''] = lang("select") . " " . lang("category");
                                    foreach ($categories as $category) {
                                        $cat[$category->id] = $category->name;
                                    }
                                    ?>
                                    <?= form_dropdown('category', $cat, set_value('category'), 'class="form-control select2 tip" id = "category"  required = "required" style = "width:100%;"'); ?>
                                </div>

                                <div class="form-group st">
                                    <?= lang('cost', 'cost'); ?>
                                    <?= form_input('cost', set_value('cost'), 'class="form-control tip" id = "cost"'); ?>
                                </div>


                                <div class="form-group st">
                                    <b>Margen de ganancia</b> <i>indique el porcentaje sin el simbolo %</i>
                                    <?= form_input('margen', set_value('margen'), 'class="form-control tip" id = "margen"'); ?>
                                </div>

                                <?php if ($Settings->enable_fractions == 1) { ?>
                                    <div class="form-group">
                                        <b>Presentacion de venta del Articulo</b><br />

                                        <b>Caja</b>
                                        <?= form_checkbox('present_caja', set_value('present_caja'), 'class="form-control tip" id = "present_caja" '); ?>

                                        <b>Fraccion</b>
                                        <?= form_checkbox('present_fraccion', set_value('present_fraccion'), 'class="form-control tip" id = "present_fraccion" '); ?>

                                    </div>
                                    <div class="form-group" style="display: none;" id="div_fracciones">
                                        <b>Cantidad de fracciones en la caja</b>
                                        <?= form_input('caja_fraccionada', set_value('caja_fraccionada'), 'class="form-control tip" id = "caja_fraccionada" '); ?>
                                    </div>
                                <?php } ?>

                                <div class="form-group">
                                    <?= lang('price', 'price'); ?>
                                    <?= form_input('price', set_value('price'), 'class="form-control tip" id = "price"  required = "required"'); ?>
                                </div>
                                <?php if($this->Settings->multiprice_enabled != 1){ ?>
                                <div class="form-group">
                                    <?= lang('price_rate', 'price_rate'); ?>
                                    <?= form_input('price_rate', set_value('price_rate'), 'class="form-control tip" id = "price_rate" '); ?>
                                </div>

                                <div class="form-group">
                                    <?= lang('offer_price', 'offer_price'); ?>
                                    <?= form_input('offer_price', set_value('offer_price'), 'class="form-control tip" id = "offer_price" '); ?>
                                </div>
                                <?php } ?>
                                <div class="form-group">
                                    <?= lang('product_tax', 'product_tax'); ?> <?= lang('external_percentage'); ?>
                                    <?php
                                    $optiones = '';
                                    $ocultos = '';
                                    foreach ($impuestos as $impuesto) {
                                        $optiones .= '<option value="' . $impuesto->id_impuesto . '">' . $impuesto->descripcion_impuesto . '</option>';

                                        $ocultos .= '<input name="pit' . $impuesto->id_impuesto . '" id="pit' . $impuesto->id_impuesto . '" value="' . $impuesto->tasa_impuesto . '" type="hidden">';
                                    }
                                    ?>
                                    <select id="product_tax" name="product_tax" class="form-control  tip" required="required" style="width:100%;">
                                        <? echo $optiones; ?>
                                    </select>
                                    <? echo $ocultos; ?>
                                    <?
                                    /*aca va el cambio
                                    <?= form_input('product_tax', set_value('product_tax', 0), 'class="form-control tip" id = "product_tax"  required = "required"'); ?>
                                    */ ?>
                                </div>
                                <div class="form-group">
                                    <?= lang('tax_method', 'tax_method'); ?>
                                    <?php $tm = array(0 => lang('inclusive'), 1 => lang('exclusive')); ?>
                                    <?= form_dropdown('tax_method', $tm, set_value('tax_method'), 'class="form-control tip select2" id = "tax_method"  required = "required" style = "width:100%;"'); ?>
                                </div>
                                <div class="form-group st">
                                    <?= lang('alert_quantity', 'alert_quantity'); ?>
                                    <?= form_input('alert_quantity', set_value('alert_quantity', 0), 'class="form-control tip" id = "alert_quantity"  required = "required"'); ?>
                                </div>

                                <div class="form-group">
                                    <label for="cabys">Código CABYS <small>(Clasificador Hacienda — 13 dígitos)</small></label>
                                    <input type="text" id="cabys-buscar" class="form-control tip" placeholder="Buscar por descripción (mín. 3 caracteres)..." autocomplete="off" style="margin-bottom:4px;">
                                    <?= form_input('cabys', set_value('cabys'), 'class="form-control tip" id="cabys" maxlength="13" placeholder="Ej: 8101102000000"'); ?>
                                    <span id="cabys-info" class="help-block" style="display:none;color:#3c763d;"></span>
                                    <span class="help-block">Busque por descripción o escriba el código de 13 dígitos directamente.</span>
                                </div>

                                <div class="form-group">
                                    <?= lang('image', 'image'); ?>
                                    <p class="help-block">Peso máximo: 500 KB · Dimensiones máximas: 1000 × 1000 px</p>
                                    <input type="file" name="userfile" id="image">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="ct" style="display:none;">
                                    <div class="form-group">
                                        <?= lang("add_product", "add_item"); ?>
                                        <?php echo form_input('add_item', '', 'class="form-control ttip" id = "add_item" data - placement = "top" data - trigger = "focus" data - bv - notEmpty - message = "' . lang('please_add_items_below') . '" placeholder = "' . $this->lang->line("add_item") . '"'); ?>
                                    </div>
                                    <div class="control-group table-group">
                                        <label class="table-label" for="combo"><?= lang("combo_products"); ?></label>

                                        <div class="controls table-controls">
                                            <table id="prTable" class="table items table-striped table-bordered table-condensed table-hover">
                                                <thead>
                                                    <tr>
                                                        <th class="col-xs-9"><?= lang("product_name") . " (" . $this->lang->line("product_code") . ")"; ?></th>
                                                        <th class="col-xs-2"><?= lang("quantity"); ?></th>
                                                        <th class=" col-xs-1 text-center"><i class="fa fa-trash-o trash-opacity-50"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($Settings->multi_store) {
                                    foreach ($stores as $store) { ?>
                                        <div class="">
                                            <div class="well well-sm">
                                                <h4><?= $store->name . ' (' . $store->code . ')'; ?></h4>
                                                <div class="form-group st">
                                                    <?= lang('quantity', 'quantity' . $store->id); ?>
                                                    <?= form_input('quantity' . $store->id, set_value('quantity', 0), 'class="form-control tip" id = "quantity' . $store->id . '"'); ?>
                                                </div>
                                                <?php if ($Settings->enable_fractions == 1) { ?>
                                                    <div class="form-group st">
                                                        <?= lang('Cantidad de fracciones' . $store->id); ?>
                                                        <?= form_input('qty_fracc' . $store->id, set_value('qty_fracc', 0), 'class="form-control tip" id = "qty_fracc' . $store->id . '"'); ?>
                                                    </div>
                                                    <div class="form-group" style="margin-bottom:0;">
                                                        <?= lang('price', 'price' . $store->id); ?>
                                                        <?= form_input('price' . $store->id, set_value('price' . $store->id), 'class="form-control tip" id = "price' . $store->id . '" placeholder = "' . lang('optional') . '"'); ?>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php }
                                } else { ?>
                                    <div class="st">
                                        <div class="form-group">
                                            <?= lang('quantity', 'quantity'); ?>
                                            <?= form_input('quantity', set_value('quantity', 0), 'class="form-control tip" id = "quantity" required = "required"'); ?>
                                        </div>
                                        <?php if ($Settings->enable_fractions == 1) { ?>
                                            <div class="form-group" style="display: none;" id="div_qty_fracc">
                                                <b>Cantidad de Fracciones</b>
                                                <?= form_input('qty_fracc', set_value('qty_fracc', 0), 'class="form-control tip" id = "qty_fracc"'); ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>

                                <div class="st">
                                    <div class="form-group">
                                        <?= lang('Ubicacion', 'Ubicacion'); ?>
                                        <input id="seccion" class="form-control" placeholder="Seccion">
                                        <input id="tramo" class="form-control" placeholder="Tramo">
                                        <span id='addubicacion' class="btn btn-success">Agregar Ubicacion</span>
                                    </div>
                                </div>

                                <div class="st">

                                    <script>
                                        $(function() {
                                            $('#addubicacion').on('click', function() {

                                                var seccion = $("#seccion").val();
                                                var tramo = $("#tramo").val();
                                                var markup = "<tr><td><input type='checkbox' class='record'></td>" +
                                                    "<td><input name='seccion[]' class='form-control'  value='" + seccion + "' /></td>" +
                                                    "<td><input name='tramo[]' class='form-control'  value='" + tramo + "' /></td>" +
                                                    "</tr>";
                                                $("#tbodyubicacion").append(markup);
                                                $("#seccion").val('');
                                                $("#tramo").val('');

                                            })

                                            // Find and remove selected table rows
                                            $(".delete-row").click(function() {
                                                $("table tbody").find('input[class="record"]').each(function() {
                                                    if ($(this).is(":checked")) {
                                                        $(this).parents("tr").remove();
                                                    }
                                                });
                                            });
                                        });
                                    </script>

                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>SECCIÓN</th>
                                                <th>TRAMO</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyubicacion">
                                        </tbody>
                                    </table>
                                    <button type="button" class="delete-row btn btn-warning">Eliminar Seccion</button>
                                </div>
                            </div>
                        </div>
                        <?php if($this->Settings->multiprice_enabled == 1){ ?>
                        <div class="box box-info" style="margin-top:16px;">
                            <div class="box-header">
                                <h3 class="box-title"><i class="fa fa-dollar" style="color:var(--nx-a3);margin-right:6px;"></i>Lista de Precios</h3>
                            </div>
                            <div class="box-body" style="padding:0;">
                            <table class="table table-bordered" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>Lista de Precio</th>
                                        <th>Margen de ganancia (%)</th>
                                        <th>Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($prices): foreach ($prices as $item): ?>
                                    <tr>
                                        <td class="text-center" style="font-weight:600;">
                                            <?= $item->nombre_l_precio; ?>
                                            <input type="hidden" value="<?= $item->id_lista_precios; ?>" name="id_lista_precio[]">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control margen_ganancia text-center" name="listmargen[]" value="0" required="required" data-bv-field="margen">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control precio_item" name="listprice[]" value="0" required="required" data-bv-field="price">
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php }?>
                        <div class="form-group">
                            <?= lang('details', 'details'); ?>
                            <?= form_textarea('details', set_value('details'), 'class="form-control tip redactor" id = "details"'); ?>
                        </div>
                        <div class="form-group">
                            <?= form_submit('add_product', lang('add_product'), 'class="btn btn-primary"'); ?>
                        </div>
                        <?= form_close(); ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
</section>


<script type="text/javascript" charset="utf-8">
    var price = 0;
    cost = 0;
    items = {};
    $(document).ready(function() {

        $('input').iCheck('uncheck');
        $('#margen').on('keyup', function() {
            cost = parseFloat($('#cost').val());
            marg = parseFloat($(this).val());
            ganancia = (cost * marg) / 100;
            $('#price').val(parseFloat(cost + ganancia).toFixed(2));
        });

        $('input').on('ifChecked', function(event) {
            nme = event.target.name;
            if (nme == 'present_fraccion') {
                $('#div_qty_fracc').css('display', 'block');
                $('#div_fracciones').css('display', 'block');
                $('input[name=present_fraccion]').val('1')
            }
            if (nme == 'present_caja') {
                $('input[name=present_caja]').val('1')
            }
        });
        $('input').on('ifUnchecked', function(event) {
            nme = event.target.name;
            if (nme == 'present_fraccion') {
                $('#div_qty_fracc').css('display', 'none');
                $('#div_fracciones').css('display', 'none');
                $('input[name=present_fraccion]').val('0')
            }
            if (nme == 'present_caja') {
                $('input[name=present_caja]').val('0')
            }
        });



        $('#type').change(function(e) {
            var type = $(this).val();
            if (type == 'combo') {
                $('.st').slideUp();
                $('#ct').slideDown();
                //$('#cost').attr('readonly', true);
            } else if (type == 'service') {
                $('.st').slideUp();
                $('#ct').slideUp();
                //$('#cost').attr('readonly', false);
            } else {
                $('#ct').slideUp();
                $('.st').slideDown();
                //$('#cost').attr('readonly', false);
            }
        });

        $("#add_item").autocomplete({
            source: '<?= site_url('products/suggestions'); ?>',
            minLength: 1,
            autoFocus: false,
            delay: 200,
            response: function(event, ui) {
                if ($(this).val().length >= 16 && ui.content[0].id == 0) {
                    bootbox.alert('<?= lang('no_product_found') ?>', function() {
                        $('#add_item').focus();
                    });
                    $(this).val('');
                } else if (ui.content.length == 1 && ui.content[0].id != 0) {
                    ui.item = ui.content[0];
                    $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
                    $(this).autocomplete('close');
                    $(this).removeClass('ui-autocomplete-loading');
                } else if (ui.content.length == 1 && ui.content[0].id == 0) {
                    bootbox.alert('<?= lang('no_product_found') ?>', function() {
                        $('#add_item').focus();
                    });
                    $(this).val('');

                }
            },
            select: function(event, ui) {
                event.preventDefault();
                if (ui.item.id !== 0) {
                    var
                        row = add_product_item(ui.item);
                    if (row) {
                        $(this).val('');
                    }
                } else {
                    bootbox.alert('<?= lang('no_product_found') ?>');
                }
            }
        });
        $('#add_item').bind('keypress', function(e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                $(this).autocomplete("search");
            }
        });

        $(document).on('click', '.del', function() {
            var
                id = $(this).attr('id');
            delete items[id];
            $(this).closest('#row_' + id).remove();
        });


        $(document).on('change', '.rqty', function() {
            var
                item_id = $(this).attr('data-item');
            items[item_id].row.qty = (parseFloat($(this).val())).toFixed(2);
            add_product_item(null, 1);
        });

        $(document).on('change', '.rprice', function() {
            var
                item_id = $(this).attr('data-item');
            items[item_id].row.price = (parseFloat($(this).val())).toFixed(2);
            add_product_item(null, 1);
        });

        function add_product_item(item, noitem) {
            if (item == null && noitem == null) {
                return false;
            }
            if (noitem != 1) {
                item_id = item.row.id;
                if (items[item_id]) {
                    items[item_id].row.qty = (parseFloat(items[item_id].row.qty) + 1).toFixed(2);
                } else {
                    items[item_id] = item;
                }
            }
            price = 0;
            cost = 0;

            $("#prTable tbody").empty();
            $.each(items, function() {
                var
                    item = this.row;
                var
                    row_no = item.id;
                var
                    newTr = $('<tr id="row_' + row_no + '" class="item_' + item.id + '"></tr>');
                tr_html = '<td><input name="combo_item_id[]" type="hidden" value="' + item.id + '"><input name="combo_item_code[]" type="hidden" value="' + item.code + '"><input name="combo_item_name[]" type="hidden" value="' + item.name + '"><input name="combo_item_cost[]" type="hidden" value="' + item.cost + '"><span id="name_' + row_no + '">' + item.name + ' (' + item.code + ')</span></td>';
                tr_html += '<td><input class="form-control text-center rqty" name="combo_item_quantity[]" type="text" value="' + formatDecimal(item.qty) + '" data-id="' + row_no + '" data-item="' + item.id + '" id="quantity_' + row_no + '" onClick="this.select();"></td>';
                //tr_html += '<td><input class="form-control text-center rprice" name="combo_item_price[]" type="text" value="' + formatDecimal(item.price) + '" data-id="' + row_no + '" data-item="' + item.id + '" id="combo_item_price_' + row_no + '" onClick="this.select();"></td>';
                tr_html += '<td class="text-center"><i class="fa fa-times tip del" id="' + row_no + '" title="Remove" style="cursor:pointer;"></i></td>';
                newTr.html(tr_html);
                newTr.prependTo("#prTable");
                //price += formatDecimal(item.price*item.qty);
                cost += formatDecimal(item.cost * item.qty);
            });
            $('#cost').val(cost);
            return true;

        }
        <?php
        if ($this->input->post('type') == 'combo') {
            $c = sizeof($_POST['combo_item_code']);
            $items = array();
            for ($r = 0; $r <= $c; $r++) {
                if (isset($_POST['combo_item_code'][$r]) && isset($_POST['combo_item_quantity'][$r])) {
                    $items[] = array('id' => $_POST['combo_item_id'][$r], 'row' => array('id' => $_POST['combo_item_id'][$r], 'name' => $_POST['combo_item_name'][$r], 'code' => $_POST['combo_item_code'][$r], 'qty' => $_POST['combo_item_quantity'][$r], 'cost' => $_POST['combo_item_cost'][$r]));
                }
            }
            echo '
            var ci = ' . json_encode($items) . ';
            $.each(ci, function() { add_product_item(this); });
            ';
        }
        if ($this->input->post('type')) {
        ?>
            var type = '<?= $this->input->post('type'); ?>';
            if (type == 'combo') {
                $('.st').slideUp();
                $('#ct').slideDown();
                //$('#cost').attr('readonly', true);
            } else if (type == 'service') {
                $('.st').slideUp();
                $('#ct').slideUp();
                //$('#cost').attr('readonly', false);
            } else {
                $('#ct').slideUp();
                $('.st').slideDown();
                //$('#cost').attr('readonly', false);
            }

        <?php }
        ?>
    });
</script>

<script>
$(function() {
    $('.margen_ganancia').on('keyup', function() {
        var costo = $('#cost').val();
        var margen = $(this).val();
        precio = parseFloat(costo) + (costo * (margen / 100));
        $(this).parent().next().children().val(precio)

    });
    
    $('#cost').on('keyup', function() {
        var costo = $(this).val();
        $('.margen_ganancia').each(function(){
            var margen =  $(this).val();
            precio = parseFloat(costo) + (costo * (margen / 100));
            $(this).parent().next().children().val(precio.toFixed(2))
        })
        

    });
    

    $('.precio_item').on('keyup', function() {
        var costo = $('#cost').val();
        var precio = $(this).val();
        var diferencia = precio-costo;
        
        var margen =  diferencia * 100 / costo;
        $(this).parent().prev().children().val(margen.toFixed(2))

    })

})
</script>
<script>
$(function() {
    var cabysUrl = '<?= site_url("hacienda_proxy/cabys") ?>';
    $('#cabys-buscar').autocomplete({
        minLength: 3,
        delay: 400,
        source: function(req, resp) {
            $.getJSON(cabysUrl, { q: req.term, top: 20 }, function(data) {
                if (!Array.isArray(data)) { resp([]); return; }
                resp($.map(data, function(item) {
                    return {
                        label: item.codigo + ' — ' + item.descripcion + ' (IVA ' + item.impuesto + '%)',
                        value: item.descripcion,
                        codigo: item.codigo,
                        descripcion: item.descripcion,
                        impuesto: item.impuesto
                    };
                }));
            }).fail(function() { resp([]); });
        },
        select: function(event, ui) {
            event.preventDefault();
            $(this).val(ui.item.descripcion);
            $('#cabys').val(ui.item.codigo);
            $('#cabys-info').html('<i class="fa fa-check"></i> <strong>' + ui.item.codigo + '</strong> — ' + ui.item.descripcion + ' (IVA ' + ui.item.impuesto + '%)').show();
        }
    });
    $('#cabys').on('input', function() {
        if ($(this).val().length > 0) $('#cabys-info').hide();
    });
});
</script>