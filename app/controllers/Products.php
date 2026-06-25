<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller {

    function __construct() {
        parent::__construct();


        if (!$this->loggedIn) {
            redirect('login');
        }

        $this->load->library('form_validation');
        $this->load->model('products_model');
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
            $this->datatables->select($this->db->dbprefix('products') . ".id as pid, ubicacion, " . $this->db->dbprefix('products') . ".image as image, " . $this->db->dbprefix('products') . ".code as code, " . $this->db->dbprefix('products') . ".name as pname, type, " . $this->db->dbprefix('categories') . ".name as cname, psq.quantity, tax, tax_method, cost,"
                    . " (CASE WHEN psq.price > 0 THEN "
                    . "     CASE WHEN tax_method = 0 THEN psq.price ELSE psq.price + (psq.price * (tax / 100)) END  "
                    . "ELSE "
                    . "     CASE WHEN tax_method = 0 THEN {$this->db->dbprefix('products')}.price ELSE {$this->db->dbprefix('products')}.price + ({$this->db->dbprefix('products')}.price * (tax / 100)) END  END) as price, {$this->db->dbprefix('products')}.offer_price as offer_price, barcode_symbology", FALSE);
        } else {
            $this->datatables->select($this->db->dbprefix('products') . ".id as pid, " . $this->db->dbprefix('products') . ".image as image, " . $this->db->dbprefix('products') . ".code as code, " . $this->db->dbprefix('products') . ".name as pname, type, " . $this->db->dbprefix('categories') . ".name as cname, psq.quantity, tax, tax_method, (CASE WHEN psq.price > 0 THEN psq.price ELSE {$this->db->dbprefix('products')}.price END) as price, {$this->db->dbprefix('products')}.offer_price as offer_price, barcode_symbology", FALSE);
        }

        $this->datatables->from('products')
                ->join('categories', 'categories.id=products.category_id', 'left')
                // ->join('product_store_qty', 'product_store_qty.product_id=products.id', 'left')
                ->join("( SELECT * from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$store_id}) psq", 'products.id=psq.product_id', 'left')
                // ->where('product_store_qty.store_id', $store_id)
                ->group_by('products.id');

        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'>"
                . "<a href='" . site_url('products/view/$1') . "' title='" . lang("view") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-file-text-o'></i></a>"
                . "<a href='" . site_url('products/single_barcode/$1') . "' title='" . lang('print_barcodes') . "' class='tip btn btn-default btn-xs' data-toggle='ajax-modal'><i class='fa fa-print'></i></a> "
                . "<a href='" . site_url('products/single_label/$1') . "' title='" . lang('print_labels') . "' class='tip btn btn-default btn-xs' data-toggle='ajax-modal'><i class='fa fa-print'></i></a> "
            
                . "<a href='" . site_url('products/edit/$1') . "' title='" . lang("edit_product") . "' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> "
                . "<a href='" . site_url('products/delete/$1') . "' onClick=\"return confirm('" . lang('alert_x_product') . "')\" title='" . lang("delete_product") . "' class='tip btn btn-danger btn-xs'>"
                . "<i class='fa fa-trash-o'></i></a></div></div>", "pid, image, code, pname, barcode_symbology");

        $this->datatables->unset_column('pid')->unset_column('barcode_symbology');

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

    function print_barcodes() {
        $limit = 10;
        $this->load->helper('pagination');
        $page = $this->input->get('page');
        $total = $this->products_model->products_count();
        $info = ['page' => $page, 'total' => ceil($total / $limit)];
        $pagination = pagination('products/print_barcodes', $total, $limit, true);
        $products = $this->products_model->fetch_products($limit, (!empty($page) ? (($page - 1) * $limit) : 0));
        $r = 1;
        $html = "";
        $html .= '<table class="table table-bordered table-centered mb0">
        <tbody><tr>';
        foreach ($products as $pr) {
            if ($r != 1) {
                $rw = (bool) ($r & 1);
                $html .= $rw ? '</tr><tr>' : '';
            }
            $html .= '<td><h4>' . $this->Settings->site_name . '</h4><strong>' . $pr->name . '</strong><br>' . $this->product_barcode($pr->code, $pr->barcode_symbology, 60) . '<br><span class="price" style="font-size: 100%">' . lang('price') . ': ' . $this->Settings->currency_prefix . ' ' . $this->tec->formatMoney($pr->price) . '</span></td>';
            $r++;
        }
        $html .= '</tr></tbody>
        </table>';
        $this->data['links'] = $pagination;
        $this->data['html'] = $html;
        $this->data['page_title'] = lang("print_barcodes");
        $this->load->view($this->theme . 'products/print_barcodes', $this->data);
    }

    function print_labels() {
        $limit = 10;
        $this->load->helper('pagination');
        $page = $this->input->get('page');
        $total = $this->products_model->products_count();
        $info = ['page' => $page, 'total' => ceil($total / $limit)];
        $pagination = pagination('products/print_labels', $total, $limit, true);
        $products = $this->products_model->fetch_products($limit, (!empty($page) ? (($page - 1) * $limit) : 0));
        $html = "";
        foreach ($products as $pr) {
            $html .= '<div class="text-center labels break-after"><strong>' . $pr->name . '</strong><br>' . $this->product_barcode($pr->code, $pr->barcode_symbology, 25) . '<br><span class="price" style="font-size: 150%">' . lang('price') . ': ' . $this->Settings->currency_prefix . ' ' . $this->tec->formatMoney($pr->price) . '</span></div>';
        }
        $this->data['links'] = $pagination;
        $this->data['html'] = $html;
        $this->data['page_title'] = lang("print_labels");
        $this->load->view($this->theme . 'products/print_labels', $this->data);
    }

    function single_barcode($product_id = NULL) {

        $product = $this->site->getProductByID($product_id);

        $html = "";
        $html .= '<table class="table table-bordered table-centered mb0">
        <tbody><tr>';
        if ($product->quantity > 0) {
            for ($r = 1; $r <= $product->quantity; $r++) {
                if ($r != 1) {
                    $rw = (bool) ($r & 1);
                    $html .= $rw ? '</tr><tr>' : '';
                }
                $html .= '<td><h4>' . $this->Settings->site_name . '</h4><strong>' . $product->name . '</strong><br>' . $this->product_barcode($product->code, $product->barcode_symbology, 60) . ' <br><span class="price">' . lang('price') . ': ' . $this->Settings->currency_prefix . ' ' . $this->tec->formatMoney($product->price) . '</span></td>';
            }
        } else {
            for ($r = 1; $r <= 10; $r++) {
                if ($r != 1) {
                    $rw = (bool) ($r & 1);
                    $html .= $rw ? '</tr><tr>' : '';
                }
                $html .= '<td><h4>' . $this->Settings->site_name . '</h4><strong>' . $product->name . '</strong><br>' . $this->product_barcode($product->code, $product->barcode_symbology, 60) . ' <br><span class="price">' . lang('price') . ': ' . $this->Settings->currency_prefix . ' ' . $this->tec->formatMoney($product->price) . '</span></td>';
            }
        }
        $html .= '</tr></tbody>
        </table>';

        $this->data['html'] = $html;
        $this->data['page_title'] = lang("print_barcodes") . ' (' . $product->name . ')';
        $this->load->view($this->theme . 'products/single_barcode', $this->data);
    }

    function single_label($product_id = NULL, $warehouse_id = NULL) {

        $product = $this->site->getProductByID($product_id);
        $html = "";
        if ($product->quantity > 0) {
            for ($r = 1; $r <= $product->quantity; $r++) {
                $html .= '<div class="text-center labels"><strong>' . $product->name . '</strong><br>' . $this->product_barcode($product->code, $product->barcode_symbology, 25) . ' <br><span class="price">' . lang('price') . ': ' . $this->Settings->currency_prefix . ' ' . $this->tec->formatMoney($product->price) . '</span></div>';
            }
        } else {
            for ($r = 1; $r <= 10; $r++) {
                $html .= '<div class="text-center labels"><strong>' . $product->name . '</strong><br>' . $this->product_barcode($product->code, $product->barcode_symbology, 25) . ' <br><span class="price">' . lang('price') . ': ' . $this->Settings->currency_prefix . ' ' . $this->tec->formatMoney($product->price) . '</span></div>';
            }
        }
        $this->data['html'] = $html;
        $this->data['page_title'] = lang("print_labels") . ' (' . $product->name . ')';
        $this->load->view($this->theme . 'products/single_label', $this->data);
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
        $this->form_validation->set_rules('alert_quantity', lang("alert_quantity"), 'is_numeric');

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
                'id_tax' => $id_tax
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

            if ($_FILES['userfile']['size'] > 0) {

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
        $this->form_validation->set_rules('alert_quantity', lang("alert_quantity"), 'is_numeric');

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
                'id_tax' => $id_tax
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

            if ($_FILES['userfile']['size'] > 0) {

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

    function import() {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->load->helper('security');
        $this->form_validation->set_rules('userfile', lang("upload_file"), 'xss_clean');

        if ($this->form_validation->run() == true) {
            if (DEMO) {
                $this->session->set_flashdata('warning', lang("disabled_in_demo"));
                redirect('pos');
            }

            if (isset($_FILES["userfile"])) {

                $this->load->library('upload');

                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = 'csv';
                $config['max_size'] = '500';
                $config['overwrite'] = TRUE;

                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect("products/import");
                }


                $csv = $this->upload->file_name;

                $arrResult = array();
                $handle = fopen("uploads/" . $csv, "r");
                if ($handle) {
                    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $arrResult[] = $row;
                    }
                    fclose($handle);
                }
                array_shift($arrResult);

                $keys = array('code', 'name', 'cost', 'tax', 'price', 'category');

                $final = array();
                foreach ($arrResult as $key => $value) {
                    $final[] = array_combine($keys, $value);
                }

                if (sizeof($final) > 1001) {
                    $this->session->set_flashdata('error', lang("more_than_allowed"));
                    redirect("products/import");
                }

                foreach ($final as $csv_pr) {
                    if ($this->products_model->getProductByCode($csv_pr['code'])) {
                        $this->session->set_flashdata('error', lang("check_product_code") . " (" . $csv_pr['code'] . "). " . lang("code_already_exist"));
                        redirect("products/import");
                    }
                    if (!is_numeric($csv_pr['tax'])) {
                        $this->session->set_flashdata('error', lang("check_product_tax") . " (" . $csv_pr['tax'] . "). " . lang("tax_not_numeric"));
                        redirect("products/import");
                    }
                    if (!($category = $this->site->getCategoryByCode($csv_pr['category']))) {
                        $this->session->set_flashdata('error', lang("check_category") . " (" . $csv_pr['category'] . "). " . lang("category_x_exist"));
                        redirect("products/import");
                    }
                    $data[] = array(
                        'type' => 'standard',
                        'code' => $csv_pr['code'],
                        'name' => $csv_pr['name'],
                        'cost' => $csv_pr['cost'],
                        'tax' => $csv_pr['tax'],
                        'price' => $csv_pr['price'],
                        'category_id' => $category->id
                    );
                }
                //print_r($data); die();
            }
        }

        if ($this->form_validation->run() == true && $this->products_model->add_products($data)) {

            $this->session->set_flashdata('message', lang("products_added"));
            redirect('products');
        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['categories'] = $this->site->getAllCategories();
            $bc = array(array('link' => '#', 'page' => "Edicion Rapida"));
            $meta = array('page_title' => "Edicion Rapida", 'bc' => $bc);
            $this->page_construct('products/import', $this->data, $meta);
        }
    }

    function delete($id = NULL) {
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

    function fastedit($id = 1) {
        $bc = array(array('link' => '#', 'page' => "Edicion Rapida"));
        $meta = array('page_title' => "Edicion Rapida", 'bc' => $bc);
        $this->data['mmm'] = false;
        $this->page_construct('products/fasteditproduct', $this->data, $meta);
    }

    function ajuste($id = 1) {
        $bc = array(array('link' => '#', 'page' => "Ajuste de Inventario"));
        $meta = array('page_title' => "Ajuste de Inventario", 'bc' => $bc);
        $this->data['mmm'] = true;
        $this->page_construct('products/fasteditproduct', $this->data, $meta);
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
        ->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('products/editprices/$1') . "' class='tip btn btn-warning btn-xs' title='Editar precio'><i class='fa fa-edit'></i></a> <a href='" . site_url('products/deleteprices/$1') . "' onClick=\"return confirm('¿Seguro de eliminar precio?')\" class='tip btn btn-danger btn-xs' title='Precio eliminado exitosamente'><i class='fa fa-trash-o'></i></a></div></div>", "id_lista_precios");
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
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }
        if ($this->products_model->deletePrices($id)) {
            $this->session->set_flashdata('message', "Precio eliminado exitosamente");
            redirect('products/listprices');
        }
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
