<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller {

    function __construct() {
        parent::__construct();


        if (!$this->loggedIn) {
            redirect('login');
        }

        $this->load->library('form_validation');
        $this->load->model('products_model');
        $this->load->model('AuditLog_model', 'audit_log');
    }

    function index() {

        $stores = $this->site->getAllStores();
        if ($this->input->get('store_id') && !$this->session->userdata('has_store_id')) {
            $this->data['store'] = $this->site->getStoreByID($this->input->get('store_id', TRUE));
        } elseif ($this->session->userdata('store_id')) {
            $this->data['store'] = $this->site->getStoreByID($this->session->userdata('store_id'));
        } else {
            $this->data['store'] = current($stores);
        }
        $this->data['stores'] = $stores;
        $data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('products');
        $bc = array(array('link' => '#', 'page' => lang('products')));
        $meta = array('page_title' => lang('products'), 'bc' => $bc);
        $this->page_construct('products/index', $this->data, $meta);
    }

    function get_products($store_id) {

        $this->load->library('datatables');
        if ($this->Admin) {
            $this->datatables->select($this->db->dbprefix('products') . ".id as pid, ubicacion, " . $this->db->dbprefix('products') . ".alert_quantity as alert_quantity, " . $this->db->dbprefix('products') . ".image as image, " . $this->db->dbprefix('products') . ".code as code, " . $this->db->dbprefix('products') . ".name as pname, type, " . $this->db->dbprefix('categories') . ".name as cname, psq.quantity, tax, tax_method, cost,"
                    . " (CASE WHEN psq.price > 0 THEN "
                    . "     CASE WHEN tax_method = 0 THEN psq.price ELSE psq.price + (psq.price * (tax / 100)) END  "
                    . "ELSE "
                    . "     CASE WHEN tax_method = 0 THEN {$this->db->dbprefix('products')}.price ELSE {$this->db->dbprefix('products')}.price + ({$this->db->dbprefix('products')}.price * (tax / 100)) END  END) as price, {$this->db->dbprefix('products')}.offer_price as offer_price, barcode_symbology", FALSE);
        } else {
            $this->datatables->select($this->db->dbprefix('products') . ".id as pid, ubicacion, " . $this->db->dbprefix('products') . ".alert_quantity as alert_quantity, " . $this->db->dbprefix('products') . ".image as image, " . $this->db->dbprefix('products') . ".code as code, " . $this->db->dbprefix('products') . ".name as pname, type, " . $this->db->dbprefix('categories') . ".name as cname, psq.quantity, tax, tax_method, (CASE WHEN psq.price > 0 THEN psq.price ELSE {$this->db->dbprefix('products')}.price END) as price, {$this->db->dbprefix('products')}.offer_price as offer_price, barcode_symbology", FALSE);
        }

        $this->datatables->from('products')
                ->join('categories', 'categories.id=products.category_id', 'left')
                // ->join('product_store_qty', 'product_store_qty.product_id=products.id', 'left')
                ->join("( SELECT product_id, SUM(quantity) as quantity, MAX(price) as price FROM {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$store_id} GROUP BY product_id) psq", 'products.id=psq.product_id', 'left');
                // psq subquery already aggregates 1 row per product — no outer GROUP BY needed

        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'>"
                . "<a href='" . site_url('products/view/$1') . "' title='" . lang("view") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-file-text-o'></i></a>"
                . "<a href='" . site_url('products/etiquetas') . "?id=$1' title='" . lang('etiquetas_codigos') . "' class='tip btn btn-default btn-xs'><i class='fa fa-print'></i></a> "
            
                . "<a href='" . site_url('products/edit/$1') . "' title='" . lang("edit_product") . "' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> "
                . "<a href='" . site_url('products/delete/$1' . '?t=' . $this->token_accion()) . "' data-confirm=\"" . lang('alert_x_product') . "\" title='" . lang("delete_product") . "' class='tip btn btn-danger btn-xs'>"
                . "<i class='fa fa-trash-o'></i></a></div></div>", "pid, image, code, pname, barcode_symbology");

        // pid se conserva en la respuesta: la vista rediseñada construye las acciones
        // en el cliente y necesita el id del producto
        $this->datatables->unset_column('barcode_symbology');

        echo $this->datatables->generate();
    }

    function view($id = NULL) {
        $data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $product = $this->site->getProductByID($id);
        if ($product) {
            $this->data['product'] = $product;
            $this->data['category'] = $this->site->getCategoryByID($product->category_id);
            $this->data['combo_items'] = $product->type == 'combo' ? $this->products_model->getComboItemsByPID($id) : NULL;
            $this->load->view($this->theme . 'products/view', $this->data);
        } else {
            $this->load->view($this->theme . 'products/view2', $this->data);
        }
    }

    function barcode($product_code = NULL) {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        $data['product_details'] = $this->products_model->getProductByCode($product_code);
        $data['img'] = "<img src='" . base_url() . "index.php?products/gen_barcode&code={$product_code}' alt='{$product_code}' />";
        $this->load->view('barcode', $data);
    }

    function product_barcode($product_code = NULL, $bcs = 'code128', $height = 60) {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode($product_code, $bcs, $height);
    }

    function gen_barcode($product_code = NULL, $bcs = 'code128', $height = 60, $text = 1) {
        return $this->tec->barcode($product_code, $bcs, $height, $text);
    }

    /**
     * Etiquetas y codigos de barras: una sola pantalla.
     *
     * Antes eran dos —`print_barcodes` y `print_labels`— que hacian lo mismo
     * con otro preajuste. Lo que las diferenciaba (tamano del rotulo y que
     * lleva impreso) hoy son controles de la misma pantalla.
     */
    function etiquetas() {
        $this->data['categorias'] = $this->site->getAllCategories();
        $this->data['precargado'] = NULL;

        // El rótulo lleva el nombre de la tienda, no el del sistema: son dos
        // cosas distintas y el negocio puede tener varias tiendas con nombre propio.
        $store_id = (int) ($this->session->userdata('store_id') ?: 0);
        $store = $store_id ? $this->site->getStoreByID($store_id) : FALSE;
        $this->data['nombre_negocio'] = $store ? $store->name : $this->Settings->site_name;

        $id = (int) $this->input->get('id');
        if ($id) {
            $p = $this->site->getProductByID($id);
            if ($p) {
                $this->data['precargado'] = $this->_producto_etiqueta($p);
            }
        }

        $titulo = lang('etiquetas_codigos');
        $bc = array(array('link' => site_url('products'), 'page' => lang('products')),
                    array('link' => '#', 'page' => $titulo));
        $this->data['page_title'] = $titulo;
        $this->page_construct('products/etiquetas', $this->data, array('page_title' => $titulo, 'bc' => $bc));
    }

    /* ── Rutas viejas: las dos pantallas ya son una ── */

    function print_barcodes() {
        redirect('products/etiquetas');
    }

    function print_labels() {
        redirect('products/etiquetas');
    }

    function single_barcode($product_id = NULL) {
        redirect('products/etiquetas?id=' . (int) $product_id);
    }

    function single_label($product_id = NULL, $warehouse_id = NULL) {
        redirect('products/etiquetas?id=' . (int) $product_id);
    }

    /**
     * Un producto tal como lo consume la hoja de etiquetas.
     *
     * El precio neto y el precio con impuesto viajan por separado: la etiqueta
     * de gondola lleva el precio final, el rotulo de bodega no siempre.
     */
    private function _producto_etiqueta($fila) {
        if (isset($fila->psq_price)) {
            $precio     = (float) $fila->psq_price;
            $existencia = (float) $fila->psq_qty;
        } else {
            $sq         = $this->products_model->getStoreQuantity($fila->id);
            $precio     = ($sq && $sq->price !== NULL) ? (float) $sq->price : (float) $fila->price;
            $existencia = $sq ? (float) $sq->quantity : 0;
        }

        $tasa    = isset($fila->tax) ? (float) $fila->tax : 0;
        $con_iva = $precio * (1 + ($tasa / 100));

        return array(
            'id'             => (int) $fila->id,
            'code'           => (string) $fila->code,
            'name'           => (string) $fila->name,
            'simbologia'     => $fila->barcode_symbology ? $fila->barcode_symbology : 'code128',
            'existencia'     => $existencia,
            'alerta'         => isset($fila->alert_quantity) ? (float) $fila->alert_quantity : 0,
            'precio'         => round($precio, 4),
            'precio_fmt'     => $this->tec->formatMoney($precio),
            'precio_iva'     => round($con_iva, 4),
            'precio_iva_fmt' => $this->tec->formatMoney($con_iva),
            'impuesto'       => $tasa,
        );
    }

    /**
     * El codigo de barras como imagen suelta, en SVG.
     *
     * Sale como imagen y no como HTML porque una hoja de etiquetas repite el
     * mismo codigo decenas de veces: asi el navegador lo pide una sola vez. El
     * texto no viaja dentro del SVG —lo maqueta la etiqueta en HTML— para que
     * use la misma tipografia que el resto del rotulo.
     */
    function barcode_img() {
        $code   = trim((string) $this->input->get('code'));
        $sim    = (string) $this->input->get('sym');
        $alto   = (int) $this->input->get('h');
        $ajuste = $this->input->get('fit') === 'meet' ? 'meet' : 'none';

        if ($code === '') { show_404(); }
        if (!in_array($sim, array('code128', 'code39', 'upca', 'upce', 'ean8', 'ean13'), TRUE)) {
            $sim = 'code128';
        }
        $alto = max(20, min(400, $alto ?: 100));

        // Laminas rechaza el texto que la simbologia no admite: antes de tumbar
        // la hoja entera se cae a code128, que acepta cualquier codigo.
        try {
            $svg = $this->tec->barcode_svg($code, $sim, $alto, $ajuste);
        } catch (Throwable $e) {
            try {
                $svg = $this->tec->barcode_svg($code, 'code128', $alto, $ajuste);
            } catch (Throwable $e2) {
                log_message('error', 'barcode_img: ' . $e2->getMessage());
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>';
            }
        }

        $this->output
            ->set_content_type('image/svg+xml', 'utf-8')
            ->set_header('Cache-Control: public, max-age=86400')
            ->set_output($svg);
    }

    /** Productos para la pantalla de etiquetas: busqueda por codigo o nombre. */
    function buscar_etiquetas() {
        $term = trim((string) $this->input->get('term', TRUE));
        if ($term === '') {
            $this->_json_inventario(array('productos' => array()));
            return;
        }

        list($filas, $exacto) = $this->_buscar_productos($term);
        $productos = array();
        foreach ($filas as $f) {
            $productos[] = $this->_producto_etiqueta($f);
        }
        $this->_json_inventario(array('productos' => $productos, 'exacto' => $exacto));
    }

    /** Toda una categoria de una vez, para rotular una gondola entera. */
    function productos_categoria($categoria_id = NULL) {
        $filas = $this->products_model->getProductsByCategory((int) $categoria_id);
        $productos = array();
        foreach ($filas as $f) {
            $productos[] = $this->_producto_etiqueta($f);
        }
        $this->_json_inventario(array('productos' => $productos));
    }


    function add() {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }

        $this->form_validation->set_rules('code', lang("product_code"), 'trim|is_unique[products.code]|min_length[2]|max_length[50]|required|alpha_numeric');
        $this->form_validation->set_rules('name', lang("product_name"), 'required');
        $this->form_validation->set_rules('category', lang("category"), 'required');
        $this->form_validation->set_rules('price', lang("product_price"), 'required|is_numeric');
        if ($this->input->post('type') != 'service') {
            $this->form_validation->set_rules('cost', lang("product_cost"), 'required|is_numeric');
        }
        $this->form_validation->set_rules('product_tax', lang("product_tax"), 'required|is_numeric');
        $this->form_validation->set_rules('alert_quantity', lang("alert_quantity"), 'required|is_numeric');
        // Sin CABYS de 13 digitos ni unidad de medida, Hacienda rechaza toda
        // venta que incluya el producto: se exigen al darlo de alta.
        $this->form_validation->set_rules('cabys', lang("codigo_cabys"), 'trim|required|exact_length[13]|numeric');
        $this->form_validation->set_rules('unit_of_measurement', lang("unit_of_measurement"), 'trim|required');

        if ($this->form_validation->run() == true) {
            $r = 0;
            $id_lista_precio = $this->input->post('id_lista_precio');
            $margen = $this->input->post('listmargen');
            $price = $this->input->post('listprice');
            $id_impuesto=$this->input->post('product_tax');
            $id_tax=$this->input->post('pit'.$id_impuesto);
            $product_tax=$id_tax;
            $id_tax=$id_impuesto;

            $data = array(
                'unit_of_measurement' => $this->input->post('unit_of_measurement'),
                'type' => $this->input->post('type'),
                'code' => $this->input->post('code'),
                'name' => $this->input->post('name'),
                'category_id' => $this->input->post('category'),
                'price' => $this->input->post('price'),
                'price_rate' => $this->input->post('price_rate'),
                'offer_price' => $this->input->post('offer_price'),
                'cost' => $this->input->post('cost'),
                'tax' => $product_tax,
                'tax_method' => $this->input->post('tax_method'),
                'alert_quantity' => $this->input->post('alert_quantity'),
                'details' => $this->input->post('details'),
                'barcode_symbology' => $this->input->post('barcode_symbology'),
                'present_caja' => $this->input->post('present_caja') ? $this->input->post('present_caja') : 0,
                'present_fraccion' => $this->input->post('present_fraccion') ? $this->input->post('present_fraccion') : 0,
                'caja_fraccionada' => $this->input->post('caja_fraccionada') ? $this->input->post('present_fraccion') : 0,
                'margen' => $this->input->post('margen'),
                'id_tax' => $id_tax,
                'cabys' => $this->input->post('cabys') ? $this->input->post('cabys') : null,
                'supplier_id' => (int) $this->input->post('supplier_id') ?: null,
            );

            if ($this->Settings->multi_store) {
                $stores = $this->site->getAllStores();
                foreach ($stores as $store) {
                    $store_quantities[] = array(
                        'store_id' => $store->id,
                        'quantity' => $this->input->post('quantity' . $store->id),
                        'qty_fracc' => $this->input->post('qty_fracc' . $store->id) ? $this->input->post('qty_fracc' . $store->id) : 0,
                        'price' => $this->input->post('price' . $store->id) ? $this->input->post('price' . $store->id) : $this->input->post('price')
                    );
                }
            } else {
                $store_quantities[] = array(
                    'store_id' => 1,
                    'quantity' => $this->input->post('quantity'),
                    'qty_fracc' => $this->input->post('qty_fracc') ? $this->input->post('qty_fracc') : 0,
                    'price' => $this->input->post('price'),
                );
            }

            if ($this->input->post('type') == 'combo') {
                $c = sizeof($_POST['combo_item_code']) - 1;
                for ($r = 0; $r <= $c; $r++) {
                    if (isset($_POST['combo_item_code'][$r]) && isset($_POST['combo_item_quantity'][$r])) {
                        $items[] = array(
                            'item_code' => $_POST['combo_item_code'][$r],
                            'quantity' => $_POST['combo_item_quantity'][$r]
                        );
                    }
                }
            } else {
                $items = array();
            }

            if (!empty($_FILES['userfile']['size'])) {

                $this->load->library('upload');

                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '500';
                $config['max_width'] = '800';
                $config['max_height'] = '800';
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect("products/add");
                }

                $photo = $this->upload->file_name;
                $data['image'] = $photo;

                $this->load->library('image_lib');
                $config['image_library'] = 'gd2';
                $config['source_image'] = 'uploads/' . $photo;
                $config['new_image'] = 'uploads/thumbs/' . $photo;
                $config['maintain_ratio'] = TRUE;
                $config['width'] = 110;
                $config['height'] = 110;

                $this->image_lib->clear();
                $this->image_lib->initialize($config);

                if (!$this->image_lib->resize()) {
                    $this->session->set_flashdata('error', $this->image_lib->display_errors());
                    redirect("products/add");
                }
            }
            // $this->tec->print_arrays($data, $items);
        }
		
		
        if ($this->form_validation->run() == true && ($productid = $this->products_model->addProduct($data, $store_quantities, $items)) != false ) {
            $ubicaciones = null;
			$i = isset($_POST['seccion']) ? sizeof($_POST['seccion']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $ubicaciones[] = array(
                    'seccion' => $_POST['seccion'][$r],
                    'tramo' => $_POST['tramo'][$r],
                    'id_producto' => $productid
                );
            }
            if ($ubicaciones) {

                $this->products_model->addUbicaciones($ubicaciones, $productid);
            }
            $r = 0;
            foreach ($id_lista_precio as $lp) {
    
                $merg=$margen[$r];
                if($merg=='Infinity')
                    $merg=0;
                
                $precio =
                    [
                        'product_id' => $productid,
                        'margen' =>  str_replace(",", "",$merg),
                        'price' => str_replace(",", "", $price[$r]),
                        'price_group_id' => $id_lista_precio[$r]
                    ];
    
                $r = $r + 1;
                $this->db->insert($this->db->dbprefix('product_prices'), $precio);
            }
            $this->session->set_flashdata('message', lang("product_added"));
            redirect('products/add');
        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['stores'] = $this->site->getAllStores();
            $this->data['categories'] = $this->site->getAllCategories();
            $this->data['proveedores'] = $this->db->select('id, name, company')->where('deleted', 0)->order_by('name', 'ASC')->get('suppliers')->result();
            $this->data['impuestos'] = $this->site->getAllImpuestos();
            $this->data['prices'] = $this->site->getAllListPrices();
            $this->data['page_title'] = lang('add_product');
            $bc = array(array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => lang('add_product')));
            $meta = array('page_title' => lang('add_product'), 'bc' => $bc);
            $this->page_construct('products/add', $this->data, $meta);
        }
    }

    function edit($id = NULL) {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $pr_details = $this->site->getProductByID($id);
		 $this->data['ubicaciones'] = $this->products_model->getUbicacionesbyId($id);
        if ($this->input->post('code') != $pr_details->code) {
            $this->form_validation->set_rules('code', lang("product_code"), 'is_unique[products.code]');
        }
        $this->form_validation->set_rules('code', lang("product_code"), 'trim|min_length[2]|max_length[50]|required|alpha_numeric');
        $this->form_validation->set_rules('name', lang("product_name"), 'required');
        $this->form_validation->set_rules('category', lang("category"), 'required');
        $this->form_validation->set_rules('price', lang("product_price"), 'required|is_numeric');
        $this->form_validation->set_rules('cost', lang("product_cost"), 'required|is_numeric');
        $this->form_validation->set_rules('product_tax', lang("product_tax"), 'required|is_numeric');
        $this->form_validation->set_rules('alert_quantity', lang("alert_quantity"), 'required|is_numeric');
        // Sin CABYS de 13 digitos ni unidad de medida, Hacienda rechaza toda
        // venta que incluya el producto: se exigen al darlo de alta.
        $this->form_validation->set_rules('cabys', lang("codigo_cabys"), 'trim|required|exact_length[13]|numeric');
        $this->form_validation->set_rules('unit_of_measurement', lang("unit_of_measurement"), 'trim|required');

        if ($this->form_validation->run() == true) {
            $id_impuesto=$this->input->post('product_tax');
            $id_tax=$this->input->post('pit'.$id_impuesto);
            $product_tax=$id_tax;
            $id_tax=$id_impuesto;
            $id_lista_precio = $this->input->post('id_lista_precio');
            $id_product_prices = $this->input->post('id_product_prices');
            $margen = $this->input->post('listmargen');
            $price = $this->input->post('listprice');

            $data = array(
                'unit_of_measurement' => $this->input->post('unit_of_measurement'),
                'type' => $this->input->post('type'),
                'code' => $this->input->post('code'),
                'name' => $this->input->post('name'),
                'category_id' => $this->input->post('category'),
                'price' => $this->input->post('price'),
                'price_rate' => $this->input->post('price_rate'),
                'offer_price' => $this->input->post('offer_price'),
                'cost' => $this->input->post('cost'),
                'tax' => $product_tax,
                'tax_method' => $this->input->post('tax_method'),
                'alert_quantity' => $this->input->post('alert_quantity'),
                'details' => $this->input->post('details'),
                'barcode_symbology' => $this->input->post('barcode_symbology'),
                'present_caja' => $this->input->post('present_caja') ? $this->input->post('present_caja') : 0,
                'present_fraccion' => $this->input->post('present_fraccion') ? $this->input->post('present_fraccion') : 0,
                'caja_fraccionada' => $this->input->post('caja_fraccionada') ? $this->input->post('caja_fraccionada') : 0,
                'margen' => $this->input->post('margen'),
                'id_tax' => $id_tax,
                'cabys' => $this->input->post('cabys') ? $this->input->post('cabys') : null,
                'supplier_id' => (int) $this->input->post('supplier_id') ?: null,
                );
            if ($this->Settings->multi_store) {
                $stores = $this->site->getAllStores();
                foreach ($stores as $store) {
                    $store_quantities[] = array(
                        'store_id' => $store->id,
                        'quantity' => $this->input->post('quantity' . $store->id),
                        'qty_fracc' => $this->input->post('qty_fracc' . $store->id) ? $this->input->post('qty_fracc' . $store->id) : 0,
                        'price' => $this->input->post('price' . $store->id) ? $this->input->post('price' . $store->id) : $this->input->post('price')
                    );
                }
            } else {
                $store_quantities[] = array(
                    'store_id' => 1,
                    'quantity' => $this->input->post('quantity'),
                    'qty_fracc' => $this->input->post('qty_fracc') ? $this->input->post('qty_fracc') : 0,
                    'price' => $this->input->post('price'),
                );
            }
            if ($this->input->post('type') == 'combo') {
                $c = sizeof($_POST['combo_item_code']) - 1;
                for ($r = 0; $r <= $c; $r++) {
                    if (isset($_POST['combo_item_code'][$r]) && isset($_POST['combo_item_quantity'][$r])) {
                        $items[] = array(
                            'item_code' => $_POST['combo_item_code'][$r],
                            'quantity' => $_POST['combo_item_quantity'][$r]
                        );
                    }
                }
            } else {
                $items = array();
            }

            if (!empty($_FILES['userfile']['size'])) {

                $this->load->library('upload');

                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '500';
                $config['max_width'] = '800';
                $config['max_height'] = '800';
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect("products/edit/" . $id);
                }

                $photo = $this->upload->file_name;

                $this->load->helper('file');
                $this->load->library('image_lib');
                $config['image_library'] = 'gd2';
                $config['source_image'] = 'uploads/' . $photo;
                $config['new_image'] = 'uploads/thumbs/' . $photo;
                $config['maintain_ratio'] = TRUE;
                $config['width'] = 110;
                $config['height'] = 110;

                $this->image_lib->clear();
                $this->image_lib->initialize($config);

                if (!$this->image_lib->resize()) {
                    $this->session->set_flashdata('error', $this->image_lib->display_errors());
                    redirect("products/edit/" . $id);
                }
            } else {
                $photo = NULL;
            }
        }

        if ($this->form_validation->run() == true && $this->products_model->updateProduct($id, $data, $store_quantities, $items, $photo)) {
            $ubicaciones = null;
			$i = isset($_POST['seccion']) ? sizeof($_POST['seccion']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $ubicaciones[] = array(
                    'seccion' => $_POST['seccion'][$r],
                    'tramo' => $_POST['tramo'][$r],
                    'id_producto' => $id
                );
            }
            if ($ubicaciones) {

                $this->products_model->addUbicaciones($ubicaciones, $id);
            }
            $r = 0;
            // dd($id);
            foreach ($id_lista_precio as $lp) {
                $merg=$margen[$r];
                if($merg=='Infinity')
                    $merg=0;
                
                $precio =
                    [
                        'product_id' => $id,
                        'margen' =>  str_replace(",", "",$merg),
                        'price' => str_replace(",", "", $price[$r]),
                        'price_group_id' => $id_lista_precio[$r]
                    ];
                    $product_prices = $id_product_prices[$r];
                    
                    // $this->db->save_queries = TRUE;
                    if($product_prices)
                    {
                        $this->db->update('product_prices', $precio, array('id_product_prices' => $product_prices));
                    }else
                    {
                        $this->db->insert($this->db->dbprefix('product_prices'), $precio);
                    }
                    // var_dump('<pre>');
                    // var_dump($this->db->last_query());
                    // var_dump('</pre>');
                    $r = $r + 1;
            }
            // dd($id_lista_precio);
            $this->session->set_flashdata('message', lang("product_updated"));
            redirect("products");
        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $product = $this->site->getProductByID($id);
            if ($product->type == 'combo') {
                $combo_items = $this->products_model->getComboItemsByPID($id);
                foreach ($combo_items as $combo_item) {
                    $cpr = $this->site->getProductByID($combo_item->id);
                    $cpr->qty = $combo_item->qty;
                    $items[] = array('id' => $cpr->id, 'row' => $cpr);
                }
                $this->data['items'] = $items;
            }
            $this->data['product'] = $product;
            $this->data['stores'] = $this->site->getAllStores();
            $this->data['stores_quantities'] = $this->Settings->multi_store ? $this->products_model->getStoresQuantity($id) : $this->products_model->getStoreQuantity($id);
            $this->data['categories'] = $this->site->getAllCategories();
            $this->data['proveedores'] = $this->db->select('id, name, company')->where('deleted', 0)->order_by('name', 'ASC')->get('suppliers')->result();
            $this->data['impuestos'] = $this->site->getAllImpuestos();
            $this->data['prices'] = $this->site->getAllListPrices();
            $this->data['product_prices'] = $this->site->getProductPriceById($id);
            $this->data['page_title'] = lang('edit_product');
            $bc = array(array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => lang('edit_product')));
            $meta = array('page_title' => lang('edit_product'), 'bc' => $bc);
            $this->page_construct('products/edit', $this->data, $meta);
        }
    }

    function postFastedit($id = NULL, $m = null) {
        $this->_solo_admin();

        if (isset($_POST['ajuste'])) {
            /*
             * tipo de movimiento tipo_mov 0 Disminucion
             * tipo de movimiento tipo_mov 1 Aumento de Inventario
             * tipo de movimiento tipo_mov 2 Edicion Rapida
             * tipo de movimiento tipo_mov 3 Cambio de precio
             */

            if ($this->input->post('tipo_mov') == "3") {
                $desc = "Cambio de precio realizado por: " . $this->session->userdata('first_name') . " " . $this->session->userdata('last_name');
                if (!$this->input->post('price')) {
                    $this->session->set_flashdata('error', "Es requerido el precio a cambiar");
                    redirect("products/ajuste");
                }
            } else if ($this->input->post('tipo_mov') == "1" || $this->input->post('tipo_mov') == "0") {
                $desc = "Cambio en inventario realizado por: " . $this->session->userdata('first_name') . " " . $this->session->userdata('last_name');
                if (!$this->input->post('quantity') and ! $this->input->post('qty_fracc_mov')) {
                    $this->session->set_flashdata('error', "Es requerida las cantidades a cambiar");
                    redirect("products/ajuste");
                }
            } else if ($this->input->post('tipo_mov') == "2") {
                $desc = "Edicion Rapida en inventario realizado por: " . $this->session->userdata('first_name') . " " . $this->session->userdata('last_name');
                if (!$this->input->post('quantity') and ! $this->input->post('qty_fracc_mov') and ! $this->input->post('price')) {
                    $this->session->set_flashdata('error', "Algun cambio es requerido en el inventario");
                    redirect("products/ajuste");
                }
            }

            $quantity = isset($_POST["quantity"]) ? $this->input->post('quantity') : NULL;
            $qtyfracc = isset($_POST["qty_fracc_mov"]) ? $this->input->post('qty_fracc_mov') : NULL;
            $price = isset($_POST["price"]) ? $this->input->post('price') : NULL;

            if ($this->input->post('tipo_mov') == "0") {
                $this->products_model->DisminuyeInventario($quantity, $qtyfracc, $price, $this->input->post('product_id'));
            }
            if ($this->input->post('tipo_mov') == "1") {
                $this->products_model->AumentaInventario($quantity, $qtyfracc, $price, $this->input->post('product_id'));
            }
            if ($this->input->post('tipo_mov') == "2") {
                $this->products_model->EdicionRapida($quantity, $qtyfracc, $price, $this->input->post('product_id'));
            }
            if ($this->input->post('tipo_mov') == "3") {
                $this->products_model->CambioPrecio($price, $this->input->post('product_id'));
            }


            $mov = [
                "tipo_mov" => $this->input->post('tipo_mov'),
                "descripcion_mov" => $this->input->post('descripcion_mov') != "" ? $this->input->post('descripcion_mov') : $desc,
                "quantity_mov" => isset($_POST["quantity"]) ? $this->input->post('quantity') : 0,
                "qty_fracc_mov" => isset($_POST["qty_fracc_mov"]) ? $this->input->post('qty_fracc_mov') : 0,
                "id_product" => $this->input->post('product_id'),
                "id_usuario" => $this->session->userdata('user_id'),
                "precio_ant" => isset($_POST["price"]) ? $this->input->post('precio_ant') : 0,
                "precio_act" => isset($_POST["price"]) ? $this->input->post('price') : 0,
            ];

            $this->products_model->AddMovimento($mov);

            $this->session->set_flashdata('message', lang("product_updated"));
            redirect("products/ajuste");
        } else {

            if (!$this->Admin) {
                $this->session->set_flashdata('error', lang('access_denied'));
                redirect('pos');
            }
            if ($this->input->post('product_id')) {
                $id = $this->input->post('product_id');
            }
            $data = array(
                'price' => $this->input->post('price'),
            );

            if ($this->Settings->multi_store) {
                $stores = $this->site->getAllStores();
                foreach ($stores as $store) {
                    $store_quantities[] = array(
                        'store_id' => $store->id,
                        'quantity' => $this->input->post('quantity'),
                        'price' => $this->input->post('price')
                    );
                }
            } else {
                $store_quantities[] = array(
                    'store_id' => 1,
                    'quantity' => $this->input->post('quantity'),
                    'price' => $this->input->post('price')
                );
            }

            $items = array();
            $photo = NULL;


            if ($this->products_model->updateProduct($id, $data, $store_quantities, $items, $photo)) {
                $this->session->set_flashdata('message', lang("product_updated"));
                redirect("products/fastedit");
            } else {
                $this->session->set_flashdata('message', lang("error"));
                redirect("products/fastedit");
            }
        }
    }

    /**
     * Importacion de productos en dos pasos: revisar y confirmar.
     *
     * El CABYS es obligatorio linea por linea desde la v4.4; un catalogo
     * importado sin el deja rechazada la primera factura que lo incluya.
     */
    function import() {
        $this->_solo_admin();

        $this->data['error']      = $this->session->flashdata('error');
        $this->data['message']    = $this->session->flashdata('message');
        $this->data['categories'] = $this->site->getAllCategories();
        $this->data['impuestos']  = $this->site->getAllImpuestos();
        // En userdata y no en flashdata: recargar la pantalla no puede perder
        // la revision, que es lo unico que el paso de confirmacion acepta.
        $this->data['revision']   = $this->session->userdata('revision_import');

        $this->data['page_title'] = lang('import_products');
        $bc = array(array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => lang('import_products')));
        $meta = array('page_title' => lang('import_products'), 'bc' => $bc);
        $this->page_construct('products/import', $this->data, $meta);
    }

    /** Columnas admitidas del CSV, buscadas por nombre y no por posicion. */
    private function _columnas_import() {
        return array('code', 'name', 'cabys', 'category', 'cost', 'price',
                     'tax_code', 'unit', 'quantity', 'alert_quantity', 'supplier_code');
    }

    /**
     * Paso 1: lee el archivo, valida fila por fila y no escribe nada.
     */
    function revisar_import() {
        $this->_solo_admin();

        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect('products/import');
        }

        $modo = $this->input->post('modo');
        if (!in_array($modo, array('crear', 'actualizar', 'ambos'), true)) {
            $modo = 'crear';
        }

        if (empty($_FILES['userfile']['size'])) {
            $this->session->set_flashdata('error', lang('import_falta_archivo'));
            redirect('products/import');
        }

        $this->load->library('upload');
        $this->upload->initialize(array(
            'upload_path'   => 'uploads/',
            'allowed_types' => 'csv',
            'max_size'      => 4096,
            'overwrite'     => FALSE,
            'encrypt_name'  => TRUE,
        ));

        if (!$this->upload->do_upload()) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('products/import');
        }

        $ruta = 'uploads/' . $this->upload->file_name;
        $revision = $this->_revisar_csv($ruta, $modo);
        $revision['modo']    = $modo;
        $revision['archivo'] = $this->upload->file_name;

        $this->session->set_userdata('revision_import', $revision);
        redirect('products/import');
    }

    /**
     * Lee el CSV y clasifica cada fila en crear, actualizar o rechazada.
     *
     * @return array{cabecera: array, crear: array, actualizar: array, errores: array, total: int}
     */
    private function _revisar_csv($ruta, $modo) {
        $contenido = file_get_contents($ruta);

        // Excel guarda con BOM y a veces en Latin-1: sin normalizar, las tildes
        // entran corruptas al catalogo y el primer encabezado no coincide.
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1');
        }

        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $filas  = array();
        foreach ($lineas as $n => $linea) {
            if (trim($linea) === '') { continue; }
            $filas[$n + 1] = str_getcsv($linea, $this->_separador_csv($linea));
        }

        if (!$filas) {
            return array('cabecera' => array(), 'crear' => array(), 'actualizar' => array(),
                         'errores' => array(array('linea' => 0, 'error' => lang('import_archivo_vacio'))), 'total' => 0);
        }

        // array_shift() reindexaria las claves y con ellas el numero de linea
        // que se le muestra al usuario para que encuentre la fila en su archivo.
        $primer_n = array_key_first($filas);
        $primera  = $filas[$primer_n];
        unset($filas[$primer_n]);

        $cabecera = array_map(function ($c) {
            return strtolower(trim(preg_replace('/[^A-Za-z_]/', '', $c)));
        }, $primera);

        $admitidas = $this->_columnas_import();
        $faltan = array_diff(array('code', 'name', 'cabys'), $cabecera);
        if ($faltan) {
            return array('cabecera' => $cabecera, 'crear' => array(), 'actualizar' => array(),
                         'errores' => array(array('linea' => 1, 'error' => sprintf(lang('import_faltan_columnas'), implode(', ', $faltan)))),
                         'total' => 0);
        }

        if (count($filas) > 1000) {
            return array('cabecera' => $cabecera, 'crear' => array(), 'actualizar' => array(),
                         'errores' => array(array('linea' => 0, 'error' => lang('more_than_allowed'))), 'total' => count($filas));
        }

        $tarifas = array();
        foreach ($this->site->getAllImpuestos() as $t) {
            $tarifas[$t->codigo_tarifa] = $t;
        }

        $crear = array();
        $actualizar = array();
        $errores = array();
        $vistos = array();

        foreach ($filas as $n => $columnas) {
            $fila = array();
            foreach ($cabecera as $i => $nombre) {
                if (in_array($nombre, $admitidas, true)) {
                    $fila[$nombre] = isset($columnas[$i]) ? trim($columnas[$i]) : '';
                }
            }

            $error = $this->_validar_fila_import($fila, $tarifas, $vistos);
            if ($error) {
                $errores[] = array('linea' => $n, 'code' => isset($fila['code']) ? $fila['code'] : '', 'error' => $error);
                continue;
            }

            $vistos[$fila['code']] = true;
            $existente = $this->products_model->getProductByCode($fila['code']);

            if ($existente && $modo === 'crear') {
                $errores[] = array('linea' => $n, 'code' => $fila['code'], 'error' => lang('code_already_exist'));
                continue;
            }
            if (!$existente && $modo === 'actualizar') {
                $errores[] = array('linea' => $n, 'code' => $fila['code'], 'error' => lang('import_no_existe'));
                continue;
            }

            $fila['linea'] = $n;
            if ($existente) {
                $fila['id'] = $existente->id;
                $actualizar[] = $fila;
            } else {
                $crear[] = $fila;
            }
        }

        return array('cabecera' => $cabecera, 'crear' => $crear, 'actualizar' => $actualizar,
                     'errores' => $errores, 'total' => count($filas));
    }

    /** Coma o punto y coma: Excel en espanol exporta con punto y coma. */
    private function _separador_csv($linea) {
        return (substr_count($linea, ';') > substr_count($linea, ',')) ? ';' : ',';
    }

    /**
     * @return string cadena vacia si la fila esta bien, o el motivo del rechazo
     */
    private function _validar_fila_import(array $fila, array $tarifas, array $vistos) {
        if (empty($fila['code']))  { return lang('import_falta_codigo'); }
        if (empty($fila['name']))  { return lang('import_falta_nombre'); }
        if (isset($vistos[$fila['code']])) { return lang('import_codigo_repetido'); }
        if (!preg_match('/^[A-Za-z0-9]{2,50}$/', $fila['code'])) { return lang('import_codigo_invalido'); }

        // Sin CABYS de 13 digitos Hacienda rechaza la factura que lo incluya.
        if (empty($fila['cabys']) || !preg_match('/^\d{13}$/', $fila['cabys'])) {
            return lang('import_cabys_invalido');
        }
        if (!empty($fila['tax_code']) && !isset($tarifas[$fila['tax_code']])) {
            return lang('import_tarifa_desconocida');
        }
        if (!empty($fila['category']) && !$this->site->getCategoryByCode($fila['category'])) {
            return lang('category_x_exist');
        }
        foreach (array('cost', 'price', 'quantity', 'alert_quantity') as $campo) {
            if (isset($fila[$campo]) && $fila[$campo] !== '' && !is_numeric(str_replace(',', '.', $fila[$campo]))) {
                return sprintf(lang('import_no_numerico'), $campo);
            }
        }
        return '';
    }

    /**
     * Paso 2: aplica lo que el paso 1 dejo aprobado, todo o nada.
     */
    function confirmar_import() {
        $this->_solo_admin();

        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect('products/import');
        }

        $revision = json_decode((string) $this->input->post('revision'), true);
        if (!is_array($revision) || (empty($revision['crear']) && empty($revision['actualizar']))) {
            $this->session->set_flashdata('error', lang('import_nada_que_aplicar'));
            redirect('products/import');
        }

        $tarifas = array();
        foreach ($this->site->getAllImpuestos() as $t) {
            $tarifas[$t->codigo_tarifa] = $t;
        }
        $store_id = (int) ($this->session->userdata('store_id') ?: 1);

        $this->db->trans_begin();
        $creados = 0;
        $editados = 0;

        foreach ((array) $revision['crear'] as $fila) {
            $datos = $this->_fila_a_producto($fila, $tarifas);
            $existencias = array(array(
                'store_id'  => $store_id,
                'quantity'  => isset($fila['quantity']) && $fila['quantity'] !== '' ? (float) str_replace(',', '.', $fila['quantity']) : 0,
                'qty_fracc' => 0,
                'price'     => $datos['price'],
            ));
            if ($this->products_model->addProduct($datos, $existencias, array())) {
                $creados++;
            }
        }

        foreach ((array) $revision['actualizar'] as $fila) {
            $datos = $this->_fila_a_producto($fila, $tarifas);
            if ($this->db->update('products', $datos, array('id' => (int) $fila['id']))) {
                $editados++;
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', lang('import_fallo_transaccion'));
            redirect('products/import');
        }
        $this->db->trans_commit();
        $this->session->unset_userdata('revision_import');

        $this->audit_log->log('productos_importados', 'product', 0, $creados . ' creados / ' . $editados . ' actualizados');
        $this->session->set_flashdata('message', sprintf(lang('import_resultado'), $creados, $editados));
        redirect('products');
    }

    /** Traduce una fila del CSV a la fila de `products`. */
    private function _fila_a_producto(array $fila, array $tarifas) {
        $categoria = !empty($fila['category']) ? $this->site->getCategoryByCode($fila['category']) : NULL;
        $tarifa    = (!empty($fila['tax_code']) && isset($tarifas[$fila['tax_code']])) ? $tarifas[$fila['tax_code']] : NULL;

        $numero = function ($v) {
            return ($v === null || $v === '') ? 0 : (float) str_replace(',', '.', $v);
        };

        $datos = array(
            'type'                => 'standard',
            'code'                => $fila['code'],
            'name'                => $fila['name'],
            'cabys'               => $fila['cabys'],
            'cost'                => $numero(isset($fila['cost']) ? $fila['cost'] : 0),
            'price'               => $numero(isset($fila['price']) ? $fila['price'] : 0),
            'alert_quantity'      => $numero(isset($fila['alert_quantity']) ? $fila['alert_quantity'] : 0),
            'unit_of_measurement' => !empty($fila['unit']) ? $fila['unit'] : 'Unid',
            'tax_method'          => 1,
        );
        if ($categoria) { $datos['category_id'] = $categoria->id; }
        if ($tarifa) {
            $datos['tax']    = $tarifa->tasa_impuesto;
            $datos['id_tax'] = $tarifa->id_impuesto;
        }
        return $datos;
    }

    /** Las filas rechazadas, en CSV, con el numero de linea del archivo. */
    function descargar_rechazos_import() {
        $this->_solo_admin();

        $errores = json_decode((string) $this->input->post('errores'), true);
        if (!is_array($errores)) { $errores = array(); }

        $salida = "linea,codigo,error\n";
        foreach ($errores as $e) {
            $salida .= sprintf("%d,\"%s\",\"%s\"\n",
                isset($e['linea']) ? (int) $e['linea'] : 0,
                str_replace('"', '""', isset($e['code']) ? $e['code'] : ''),
                str_replace('"', '""', isset($e['error']) ? $e['error'] : ''));
        }

        $this->output
            ->set_content_type('text/csv', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="filas-rechazadas.csv"')
            ->set_output("\xEF\xBB\xBF" . $salida);
    }

    function delete($id = NULL) {
        // El enlace tiene que venir de una pantalla de esta sesion.
        $this->exigir_token_accion();

        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }

        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }

        if ($this->products_model->deleteProduct($id)) {
            $this->session->set_flashdata('message', lang("product_deleted"));
            redirect('products');
        }
    }

    function suggestions() {
        $term = $this->input->get('term', TRUE);

        $rows = $this->products_model->getProductNames($term);
        if ($rows) {
            foreach ($rows as $row) {
                $row->qty = 1;
                $pr[] = array('id' => str_replace(".", "", microtime(true)), 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row);
            }
            echo json_encode($pr);
        } else {
            echo json_encode(array(array('id' => 0, 'label' => lang('no_match_found'), 'value' => $term)));
        }
    }

    function suggestionsFastedit($id = NULL) {
        if ($id) {
            $row = $this->site->getProductByID($id);
            $row->qty = 1;
            $pr = array('id' => str_replace(".", "", microtime(true)), 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row);
            echo json_encode($pr);
            die();
        }
        $term = $this->input->get('term', TRUE);

        $rows = $this->products_model->getProductNames($term);
        foreach ($rows as $rw) {
            try {
                $rw->quantity = $this->products_model->getStoreQuantity($rows[0]->id)->quantity;
                $rw->qty_fracc = $this->products_model->getStoreQuantity($rows[0]->id)->qty_fracc;
            } catch (Exception $e) {
                $rw->quantity = "0";
                $rw->qty_fracc = "0";
            }
        }
        if ($rows) {
            foreach ($rows as $row) {
                $row->qty = 1;
                $pr[] = array('id' => str_replace(".", "", microtime(true)), 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row);
            }
            echo json_encode($pr);
        } else {
            echo json_encode(array(array('id' => 0, 'label' => lang('no_match_found'), 'value' => $term)));
        }
    }

    /**
     * Sesion de inventario: contar, entrar, sacar y reprecificar en una sola
     * pantalla. El precio y el costo se editan en cualquier modo, asi que la
     * antigua edicion rapida es esta misma pantalla sin tocar cantidades.
     */
    function inventario() {
        $this->_solo_admin();

        $modo = (string) $this->input->get('modo');
        $this->data['modo_inicial'] = in_array($modo, array('conteo', 'entrada', 'salida'), TRUE) ? $modo : 'conteo';

        $bc = array(array('link' => site_url('products'), 'page' => lang('products')),
                    array('link' => '#', 'page' => lang('ajuste_inventario')));
        $meta = array('page_title' => lang('ajuste_inventario'), 'bc' => $bc);
        $this->page_construct('products/inventario', $this->data, $meta);
    }

    /** Las dos mitades viejas de la sesion de inventario, ya unificadas. */
    function fastedit($id = 1) {
        redirect('products/inventario');
    }

    function ajuste($id = 1) {
        redirect('products/inventario');
    }

    /** Ajustar existencias y precios cambia dinero: es de administrador. */
    private function _solo_admin() {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
            exit;   // en CI3 redirect() no corta la peticion desde un metodo llamado
        }
    }

    private function _json_inventario($datos, $codigo = 200) {
        $this->output
            ->set_status_header($codigo)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($datos));
    }

    /**
     * Busqueda compartida por las pantallas de inventario y de etiquetas.
     *
     * El codigo exacto va primero: al leer un codigo de barras el escaner manda
     * el codigo completo y el producto tiene que entrar sin ambiguedad.
     *
     * @return array{0: array, 1: bool} filas encontradas y si fue codigo exacto
     */
    private function _buscar_productos($term, $limite = 12) {
        $exacto = $this->products_model->getProductByCode($term);
        $filas  = $exacto ? array($exacto) : ($this->products_model->getProductNames($term, $limite) ?: array());
        return array($filas, (bool) $exacto);
    }

    /** Productos para la sesion de inventario, con la existencia de la tienda. */
    function buscar_inventario() {
        $this->_solo_admin();

        $term = trim((string) $this->input->get('term', TRUE));
        $store_id = (int) ($this->session->userdata('store_id') ?: 1);
        if ($term === '') {
            $this->_json_inventario(array('productos' => array()));
            return;
        }

        list($filas, $exacto) = $this->_buscar_productos($term);

        $productos = array();
        foreach ($filas as $fila) {
            $sq = $this->products_model->getStoreQuantity($fila->id, $store_id);
            $precio = $sq && $sq->price !== NULL ? (float) $sq->price : (float) $fila->price;
            $productos[] = array(
                'id'        => (int) $fila->id,
                'code'      => $fila->code,
                'name'      => $fila->name,
                'unidad'    => isset($fila->unit_of_measurement) ? $fila->unit_of_measurement : '',
                'existencia'=> $sq ? (float) $sq->quantity : 0,
                'fracciones'=> $sq ? (float) $sq->qty_fracc : 0,
                'alerta'    => isset($fila->alert_quantity) ? (float) $fila->alert_quantity : 0,
                'precio'    => round($precio, 4),
                'precio_fmt'=> $this->tec->formatMoney($precio),
                'costo'     => round((float) $fila->cost, 4),
            );
        }

        $this->_json_inventario(array('productos' => $productos, 'exacto' => (bool) $exacto));
    }

    /**
     * Confirma una sesion de inventario completa.
     *
     * Un solo envio y una sola transaccion: si una linea no se puede aplicar
     * —una salida que dejaria la existencia negativa— no entra ninguna.
     */
    function guardar_sesion_inventario() {
        $this->_solo_admin();

        $modo = $this->input->post('modo');
        if (!in_array($modo, array('conteo', 'entrada', 'salida', 'precio'), true)) {
            $this->_json_inventario(array('ok' => false, 'msg' => lang('inv_modo_invalido')), 400);
            return;
        }

        $lineas_post = json_decode((string) $this->input->post('lineas'), true);
        if (!is_array($lineas_post) || !$lineas_post) {
            $this->_json_inventario(array('ok' => false, 'msg' => lang('inv_sin_lineas')), 400);
            return;
        }

        $motivo = trim((string) $this->input->post('descripcion_mov'));
        if ($modo === 'salida' && $motivo === '') {
            $this->_json_inventario(array('ok' => false, 'msg' => lang('inv_motivo_requerido')), 400);
            return;
        }
        if ($motivo === '') {
            $motivo = lang('inv_motivo_por_defecto') . ' ' . $this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name');
        }

        $store_id = (int) ($this->session->userdata('store_id') ?: 1);
        $lineas = array();
        foreach ($lineas_post as $l) {
            if (empty($l['product_id'])) { continue; }

            $cantidad = isset($l['quantity'])  && $l['quantity']  !== '' ? $l['quantity']  : null;
            $fracc    = isset($l['qty_fracc']) && $l['qty_fracc'] !== '' ? $l['qty_fracc'] : null;
            $precio   = isset($l['price'])     && $l['price']     !== '' ? $l['price']     : null;
            $costo    = isset($l['cost'])      && $l['cost']      !== '' ? $l['cost']      : null;

            // Una linea sin nada que aplicar solo ensuciaria mov_inventario.
            if ($cantidad === null && $fracc === null && $precio === null && $costo === null) { continue; }

            // La linea que no toca existencias es un cambio de precio, y asi
            // tiene que quedar registrada aunque la sesion sea de conteo.
            $modo_linea = ($cantidad === null && $fracc === null) ? 'precio' : $modo;

            $lineas[] = array(
                'modo'            => $modo_linea,
                'product_id'      => (int) $l['product_id'],
                'store_id'        => $store_id,
                'quantity'        => $cantidad,
                'qty_fracc'       => $fracc,
                'price'           => $precio,
                'cost'            => $costo,
                'descripcion_mov' => $motivo,
            );
        }

        if (!$lineas) {
            $this->_json_inventario(array('ok' => false, 'msg' => lang('inv_sin_lineas')), 400);
            return;
        }

        $r = $this->products_model->aplicarSesion($lineas);
        if (!$r['ok']) {
            $this->_json_inventario(array('ok' => false, 'msg' => lang('inv_sesion_rechazada'), 'errores' => $r['errores']), 409);
            return;
        }

        $this->audit_log->log('inventario_ajustado', 'product', 0, $modo . ' / ' . $r['id_sesion'] . ' / ' . count($lineas) . ' lineas');
        $this->_json_inventario(array('ok' => true, 'aplicadas' => $r['aplicadas'], 'id_sesion' => $r['id_sesion'], 'msg' => lang('inv_sesion_guardada')));
    }

    function listprices() {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = "Lista de precios";
        $bc = array(array('link' => '#', 'page' => "Lista de precios"));
        $meta= array('page_title' => "Lista de precios", 'bc' => $bc);
        $this->page_construct('products/list_prices', $this->data, $meta);
    }

    function get_list_prices()
    {
        $this->load->library('datatables');
        $this->datatables->select("lista_precios.id_lista_precios, lista_precios.nombre_l_precio, lista_precios.status_l_precio,users.username as entry_by", FALSE);
        $this->datatables->from('lista_precios')->group_by('lista_precios.id_lista_precios')
        ->join('users', 'users.id = lista_precios.entry_by', 'left')
        ;   // Las acciones las dibuja la vista: el borrado va por POST.
        echo $this->datatables->generate();
    }

    function addprices()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->form_validation->set_rules('name', lang("product_name"), 'required');
        if ($this->form_validation->run() == true) 
        {
            // dd($this->input->post('status'));   
            $data = array(
                "nombre_l_precio" => $this->input->post('name'),
                "status_l_precio" => $this->input->post('status') == null ? 0:1,
                "code" => strtolower(str_replace(" ","_",$this->input->post('name'))),
                "entry_by" =>  $this->session->userdata('user_id')
            );
           if($this->products_model->addPrices($data))
           {
            $this->session->set_flashdata('message', "Agregado exitosamente");
            redirect('products/listprices');
           }
        }
        else
        {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['page_title'] = "Agregar precios";
            $bc = array(array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => "Agregar precios"));
            $meta = array('page_title' => "Agregar precios", 'bc' => $bc);
            $this->page_construct('products/add_list_prices', $this->data, $meta);
        }
    }

    function deleteprices($id = NULL) {
        $this->_solo_admin();

        // Borrar por GET deja que un enlace ajeno lo dispare: solo por POST.
        if ($this->input->method(TRUE) !== 'POST') {
            $this->session->set_flashdata('error', lang('accion_requiere_post'));
            redirect('products/listprices');
        }
        if ($this->input->post('id')) {
            $id = $this->input->post('id');
        }

        if ($this->products_model->deletePrices($id)) {
            $this->audit_log->log('lista_precio_borrada', 'product', (int) $id);
            $this->session->set_flashdata('message', lang('precio_eliminado'));
        }
        redirect('products/listprices');
    }

    function editprices($id = NULL)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        if($this->input->get('id')){
            $id= $this->input->get('id');
        }
        $this->form_validation->set_rules('name', lang("product_name"), 'required');
        $this->form_validation->set_rules('id_lista_precios',"id_lista_precios", 'required');
        if ($this->form_validation->run() == true) 
        {
            $id=   $this->input->post('id_lista_precios');
            $data = array(
                "nombre_l_precio" => $this->input->post('name'),
                "status_l_precio" => $this->input->post('status') == null ? 0:1,
                "code" => strtolower(str_replace(" ","_",$this->input->post('name'))),
                "entry_by" =>  $this->session->userdata('user_id')
            );
           if($this->products_model->updateListPrices($data, $id))
           {
            $this->session->set_flashdata('message', "Editado exitosamente");
            redirect('products/listprices');
           }
        }
        else
        {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['page_title'] = "Editar precios";
            $this->data['prices']     = $this->products_model->getPricesById($id);
            $bc = array(array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => "Editar precios"));
            $meta = array('page_title' => "Editar precios", 'bc' => $bc);
            $this->page_construct('products/edit_list_prices', $this->data, $meta);
        }

    }

}
