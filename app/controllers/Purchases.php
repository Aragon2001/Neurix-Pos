<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchases extends MY_Controller
{

    function __construct() {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        if ( ! $this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        }
        $this->load->library('form_validation');
        $this->load->model('purchases_model');
        $this->allowed_types = 'gif|jpg|png|pdf|doc|docx|xls|xlsx|zip';
    }

    function index() {
        if ( ! $this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = lang('purchases');
        $bc = array(array('link' => '#', 'page' => lang('purchases')));
        $meta = array('page_title' => lang('purchases'), 'bc' => $bc);
        $this->page_construct('purchases/index', $this->data, $meta);

    }

    function get_purchases() {
        if ( ! $this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->load->library('datatables');
        $pu = $this->db->dbprefix('purchases');
        $su = $this->db->dbprefix('suppliers');
        $this->datatables->select("{$pu}.id as id, {$pu}.date as date, {$pu}.reference as reference, {$pu}.total as total, {$pu}.note as note, {$pu}.attachment as attachment, {$pu}.documento_id as documento_id, COALESCE(NULLIF({$su}.company, ''), {$su}.name) as supplier", FALSE);
        $this->datatables->from('purchases');
        $this->datatables->join('suppliers', 'suppliers.id = purchases.supplier_id', 'left');
        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('purchases.created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('purchases.store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='".site_url('purchases/view/$1')."' title='".lang('view_purchase')."' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-file-text-o'></i></a> <a href='" . site_url('purchases/edit/$1') . "' title='" . lang("edit_purchase") . "' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('purchases/delete/$1' . '?t=' . $this->token_accion()) . "' data-confirm=\"" . lang('alert_x_purchase') . "\" title='" . lang("delete_purchase") . "' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", "id");

        $this->datatables->unset_column('id');
        echo $this->datatables->generate();

    }

    function view($id = NULL) {
        if ( ! $this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->data['purchase'] = $this->purchases_model->getPurchaseByID($id);
        $this->data['items'] = $this->purchases_model->getAllPurchaseItems($id);
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = lang('view_purchase');
        $this->load->view($this->theme.'purchases/view', $this->data);

    }

    /**
     * Proveedores, catalogo de tarifas y plazos: lo que el formulario dibuja.
     */
    private function _datos_formulario() {
        $this->data['suppliers'] = $this->db->select('id, name, plazo_pago_dias, moneda')
            ->where('deleted', 0)->order_by('name', 'ASC')->get('suppliers')->result();

        $plazos = array();
        foreach ($this->data['suppliers'] as $sup) {
            $plazos[$sup->id] = (int) $sup->plazo_pago_dias;
        }
        $this->data['plazos_proveedor'] = $plazos;

        $this->data['impuestos'] = $this->db
            ->select('codigo_tarifa, tasa_impuesto as tasa, descripcion_impuesto as descripcion')
            ->where('codigo_impuesto', '01')
            ->where('status_impuestos', 1)
            ->order_by('tasa_impuesto', 'DESC')
            ->get('impuestos')->result();
    }

    /**
     * Productos para la tabla de lineas, con costo, precio y tarifa de IVA.
     *
     * Busca por codigo exacto primero: la compra se captura leyendo el codigo
     * de barras de cada articulo recibido.
     */
    function buscar_producto() {
        if (!$this->Admin) {
            $this->output->set_status_header(403);
            return;
        }

        $term = trim((string) $this->input->get('term', TRUE));
        if ($term === '') {
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(array('productos' => array())));
            return;
        }

        $exacto = $this->purchases_model->getProductByCode($term);
        $filas  = $exacto ? array($exacto) : ($this->purchases_model->getProductNames($term, 15) ?: array());

        $tarifas = array();
        $por_codigo = array();
        foreach ($this->db->select('id_impuesto, codigo_tarifa, tasa_impuesto')->get('impuestos')->result() as $t) {
            $tarifas[$t->id_impuesto] = $t;
            $por_codigo[$t->codigo_tarifa] = $t;
        }
        // Un producto sin tarifa asignada entra con la general (13%, codigo 08);
        // el usuario puede cambiarla en la linea antes de guardar.
        $defecto = isset($por_codigo['08']) ? $por_codigo['08'] : NULL;

        $productos = array();
        foreach ($filas as $f) {
            $t = isset($tarifas[$f->id_tax]) ? $tarifas[$f->id_tax] : $defecto;
            $productos[] = array(
                'id'             => (int) $f->id,
                'code'           => $f->code,
                'name'           => $f->name,
                'cost'           => (float) $f->cost,
                'price'          => (float) $f->price,
                'margen'         => (float) $f->margen,
                'codigo_tarifa'  => $t ? $t->codigo_tarifa : '08',
                'tasa'           => $t ? (float) $t->tasa_impuesto : (float) $f->tax,
                'cabys'          => $f->cabys,
            );
        }

        $this->output->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode(array('productos' => $productos, 'exacto' => (bool) $exacto), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Lineas de la compra desde el POST, con su impuesto.
     *
     * El costo que se teclea es sin impuesto; el IVA soportado se guarda aparte
     * porque es lo que se concilia contra el D-104.
     *
     * @param  string $volver ruta a la que redirigir si una linea es invalida
     * @return array{items: array, total: float, impuesto: float, descuento: float}
     */
    private function _lineas_compra($volver) {
        $items = array();
        $total = 0;
        $impuesto = 0;
        $descuento = 0;

        $ids = isset($_POST['product_id']) ? (array) $_POST['product_id'] : array();
        foreach ($ids as $r => $item_id) {
            $item_qty  = isset($_POST['quantity'][$r]) ? (float) $_POST['quantity'][$r] : 0;
            $item_cost = isset($_POST['cost'][$r])     ? (float) $_POST['cost'][$r]     : 0;
            if (!$item_id || $item_qty <= 0) {
                continue;
            }

            if (!$this->site->getProductByID($item_id)) {
                $this->session->set_flashdata('error', lang("product_not_found") . " ( " . $item_id . " ).");
                redirect($volver);
            }

            $tasa      = isset($_POST['tax_rate'][$r]) ? (float) $_POST['tax_rate'][$r] : 0;
            $codigo    = isset($_POST['tax_code'][$r]) ? substr((string) $_POST['tax_code'][$r], 0, 2) : NULL;
            $desc_linea= isset($_POST['discount'][$r]) ? (float) $_POST['discount'][$r] : 0;
            $subtotal  = ($item_cost * $item_qty) - $desc_linea;
            $iva       = round($subtotal * ($tasa / 100), 4);

            $items[] = array(
                'product_id'        => $item_id,
                'cost'              => $item_cost,
                'quantity'          => $item_qty,
                'subtotal'          => $subtotal,
                'tax_code'          => $codigo,
                'tax_rate'          => $tasa,
                'tax_amount'        => $iva,
                'quantity_received' => $this->input->post('received') ? $item_qty : 0,
                'margin'            => isset($_POST['margin'][$r]) ? $_POST['margin'][$r] : NULL,
                'price'             => isset($_POST['price'][$r])  ? $_POST['price'][$r]  : NULL,
            );

            $total     += $subtotal;
            $impuesto  += $iva;
            $descuento += $desc_linea;
        }

        return array('items' => $items, 'total' => $total, 'impuesto' => $impuesto, 'descuento' => $descuento);
    }

    /**
     * Cabecera de la compra, con el vencimiento sacado del plazo del proveedor.
     */
    private function _cabecera_compra(array $lineas) {
        $recibida = (int) $this->input->post('received');
        $fecha    = $this->input->post('date');

        $proveedor = $this->input->post('supplier')
            ? $this->db->get_where('suppliers', array('id' => (int) $this->input->post('supplier')), 1)->row()
            : NULL;
        $plazo = ($proveedor && isset($proveedor->plazo_pago_dias)) ? (int) $proveedor->plazo_pago_dias : 0;

        return array(
            'date'                => $fecha,
            'reference'           => $this->input->post('reference'),
            'supplier_id'         => $this->input->post('supplier') ?: NULL,
            'supplier_invoice_no' => trim((string) $this->input->post('supplier_invoice_no')) ?: NULL,
            'note'                => $this->input->post('note', TRUE),
            'received'            => $recibida,
            'status'              => $recibida ? 'recibida' : 'pendiente',
            'payment_status'      => $plazo > 0 ? 'pendiente' : 'pagada',
            'due_date'            => $plazo > 0 ? date('Y-m-d', strtotime($fecha . ' +' . $plazo . ' days')) : NULL,
            'total'               => $lineas['total'] + $lineas['impuesto'],
            'tax_total'           => $lineas['impuesto'],
            'discount_total'      => $lineas['descuento'],
            'currency'            => ($proveedor && !empty($proveedor->moneda)) ? $proveedor->moneda : 'CRC',
            'exchange_rate'       => 1,
        );
    }

    function add() {
        if ( ! $this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        }
        if ( ! $this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->form_validation->set_rules('date', lang('date'), 'required');

        if ($this->form_validation->run() == true) {
            $lineas = $this->_lineas_compra('purchases/add');
            $products = $lineas['items'];

            if (empty($products)) {
                $this->form_validation->set_rules('product', lang("order_items"), 'required');
            }

            $data = $this->_cabecera_compra($lineas);
            $data['created_by'] = $this->session->userdata('user_id');
            $data['store_id']   = $this->session->userdata('store_id');

            if (!empty($_FILES['userfile']['size'])) {

                $this->load->library('upload');
                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = $this->allowed_types;
                $config['max_size'] = '2000';
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect("purchases/add");
                }

                $data['attachment'] = $this->upload->file_name;

            }
            // $this->tec->print_arrays($data, $products);
        }

        if ($this->form_validation->run() == true && $this->purchases_model->addPurchase($data, $products)) {
            $this->session->set_userdata('remove_spo', 1);
            $this->session->set_flashdata('message', lang('purchase_added'));
            redirect("purchases");

        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->_datos_formulario();
            $this->data['page_title'] = lang('add_purchase');
            $bc = array(array('link' => site_url('purchases'), 'page' => lang('purchases')), array('link' => '#', 'page' => lang('add_purchase')));
            $meta = array('page_title' => lang('add_purchase'), 'bc' => $bc);
            $this->page_construct('purchases/add', $this->data, $meta);

        }
    }

    function edit($id = NULL) {
        if ( ! $this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->_bloquear_si_electronico('purchases', $id, 'purchases');
        $this->form_validation->set_rules('date', lang('date'), 'required');

        if ($this->form_validation->run() == true) {
            $lineas = $this->_lineas_compra('purchases/edit/' . $id);
            $products = $lineas['items'];

            if (empty($products)) {
                $this->form_validation->set_rules('product', lang("order_items"), 'required');
            }

            $data = $this->_cabecera_compra($lineas);

            if (!empty($_FILES['userfile']['size'])) {

                $this->load->library('upload');
                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = $this->allowed_types;
                $config['max_size'] = '2000';
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect("purchases/add");
                }

                $data['attachment'] = $this->upload->file_name;

            }
            // $this->tec->print_arrays($data, $products);
        }

        if ($this->form_validation->run() == true && $this->purchases_model->updatePurchase($id, $data, $products)) {

            $this->session->set_userdata('remove_spo', 1);
            $this->session->set_flashdata('message', lang('purchase_updated'));
            redirect("purchases");

        } else {

            $this->data['purchase'] = $this->purchases_model->getPurchaseByID($id);
            if (!$this->data['purchase']) {
                $this->session->set_flashdata('error', lang('purchase_not_found'));
                redirect('purchases');
            }

            // La tabla de lineas se dibuja en el navegador desde este arreglo.
            $lineas_vista = array();
            foreach ($this->purchases_model->getAllPurchaseItems($id) as $item) {
                $row = $this->site->getProductByID($item->product_id);
                if (!$row) { continue; }
                $lineas_vista[] = array(
                    'product_id' => (int) $item->product_id,
                    'code'       => $row->code,
                    'name'       => $row->name,
                    'quantity'   => (float) $item->quantity,
                    'cost'       => (float) $item->cost,
                    'discount'   => 0,
                    'tax_code'   => isset($item->tax_code) ? $item->tax_code : '08',
                    'tax_rate'   => isset($item->tax_rate) ? (float) $item->tax_rate : 0,
                    'margin'     => (float) $row->margen,
                    'price'      => (float) $row->price,
                );
            }

            $this->data['items_json'] = json_encode($lineas_vista, JSON_UNESCAPED_UNICODE);
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->_datos_formulario();
            $this->data['page_title'] = lang('edit_purchase');
            $bc = array(array('link' => site_url('purchases'), 'page' => lang('purchases')), array('link' => '#', 'page' => lang('edit_purchase')));
            $meta = array('page_title' => lang('edit_purchase'), 'bc' => $bc);
            $this->page_construct('purchases/edit', $this->data, $meta);

        }
    }

    function delete($id = NULL) {
        // El enlace tiene que venir de una pantalla de esta sesion.
        $this->exigir_token_accion();

        if(DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if ( ! $this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->_bloquear_si_electronico('purchases', $id, 'purchases');
        if ($this->purchases_model->deletePurchase($id)) {
            $this->session->set_flashdata('message', lang("purchase_deleted"));
            redirect('purchases');
        }
    }

    function suggestions($id = NULL) {
        if($id) {
            $row = $this->site->getProductByID($id);
            $row->qty = 1;
            $pr = array('id' => str_replace(".", "", microtime(true)), 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row);
            echo json_encode($pr);
            die();
        }
        $term = $this->input->get('term', TRUE);
        $rows = $this->purchases_model->getProductNames($term);
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

     /* ----------------------------------------------------------------- */

     function expenses($id = NULL) {

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('expenses');
        $bc = array(array('link' => site_url('purchases'), 'page' => lang('purchases')), array('link' => '#', 'page' => lang('expenses')));
        $meta = array('page_title' => lang('expenses'), 'bc' => $bc);
        $this->page_construct('purchases/expenses', $this->data, $meta);

    }

    function get_expenses($user_id = NULL) {

        $detail_link = anchor('purchases/expense_note/$1', '<i class="fa fa-file-text-o"></i> ' . lang('expense_note'), 'data-toggle="modal" data-target="#myModal2"');
        $edit_link = anchor('purchases/edit_expense/$1', '<i class="fa fa-edit"></i> ' . lang('edit_expense'), 'data-toggle="modal" data-target="#myModal"');
        $delete_link = "<a href='#' class='po' title='<b>" . $this->lang->line("delete_expense") . "</b>' data-content=\"<p>"
            . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('purchases/delete_expense/$1' . '?t=' . $this->token_accion()) . "'>"
            . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i> "
            . lang('delete_expense') . "</a>";
        $action = '<div class="text-center"><div class="btn-group text-left">'
            . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
            . lang('actions') . ' <span class="caret"></span></button>
        <ul class="dropdown-menu pull-right" role="menu">
            <li>' . $detail_link . '</li>
            <li>' . $edit_link . '</li>
            <li>' . $delete_link . '</li>
        </ul>
    </div></div>';

        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select($this->db->dbprefix('expenses') . ".id as id, date, reference, amount, note, (" . $this->db->dbprefix('users') . ".first_name || ' ' || " . $this->db->dbprefix('users') . ".last_name) as user, attachment", FALSE);
        } else {
            $ex = $this->db->dbprefix('expenses');
            $su = $this->db->dbprefix('suppliers');
            $this->datatables->select("{$ex}.id as id, date, reference, amount, {$ex}.impuesto as impuesto, note, CONCAT(" . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . ".last_name) as user, attachment, {$ex}.documento_id as documento_id, " . $this->db->dbprefix('categorias_gasto') . ".nombre as categoria, COALESCE(NULLIF({$su}.company, ''), {$su}.name) as supplier", FALSE);
        }
        $this->datatables->from('expenses')
            ->join('users', 'users.id=expenses.created_by', 'left')
            ->join('categorias_gasto', 'categorias_gasto.id=expenses.category_id', 'left')
            ->join('suppliers', 'suppliers.id=expenses.supplier_id', 'left');

        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('expenses.created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('expenses.store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='".site_url('purchases/expense_note/$1')."' title='".lang('expense_note')."' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-file-text-o'></i></a> <a href='" . site_url('purchases/edit_expense/$1') . "' title='" . lang("edit_expense") . "' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('purchases/delete_expense/$1' . '?t=' . $this->token_accion()) . "' data-confirm=\"" . lang('alert_x_expense') . "\" title='" . lang("delete_expense") . "' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", "id");
        $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    function expense_note($id = NULL) {
        if ( ! $this->Admin) {
            if($expense->created_by != $this->session->userdata('user_id')) {
                $this->session->set_flashdata('error', lang('access_denied'));
                redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'pos');
            }
        }

        $expense = $this->purchases_model->getExpenseByID($id);
        $this->data['user'] = $this->site->getUser($expense->created_by);
        $this->data['expense'] = $expense;
        $this->data['page_title'] = $this->lang->line("expense_note");
        $this->load->view($this->theme . 'purchases/expense_note', $this->data);

    }

    function add_expense() {
        if ( ! $this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        }
        $this->load->helper('security');

        $this->form_validation->set_rules('amount', lang("amount"), 'required|numeric');
        $this->form_validation->set_rules('category_id', lang("categoria_gasto"), 'required|is_natural_no_zero');
        $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $date = trim($this->input->post('date'));
            } else {
                $date = date('Y-m-d H:i:s');
            }
            $data = array_merge(array(
                'date' => $date,
                'reference' => $this->input->post('reference') ? $this->input->post('reference') : $this->site->getReference('ex'),
                'amount' => $this->input->post('amount'),
                'created_by' => $this->session->userdata('user_id'),
                'store_id' => $this->session->userdata('store_id'),
                'note' => $this->input->post('note', TRUE)
            ), $this->_datos_gasto());

            if (!empty($_FILES['userfile']['size'])) {
                $this->load->library('upload');
                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = $this->allowed_types;
                $config['max_size'] = '2000';
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER["HTTP_REFERER"]);
                }
                $photo = $this->upload->file_name;
                $data['attachment'] = $photo;
            }

            //$this->tec->print_arrays($data);

        } elseif ($this->input->post('add_expense')) {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER["HTTP_REFERER"]);
        }

        if ($this->form_validation->run() == true && $this->purchases_model->addExpense($data)) {

            $this->session->set_flashdata('message', lang("expense_added"));
            redirect('purchases/expenses');

        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->_opciones_gasto();
            $this->data['page_title'] = lang('add_expense');
            $bc = array(array('link' => site_url('purchases'), 'page' => lang('purchases')), array('link' => site_url('purchases/expenses'), 'page' => lang('expenses')), array('link' => '#', 'page' => lang('add_expense')));
            $meta = array('page_title' => lang('add_expense'), 'bc' => $bc);
            $this->page_construct('purchases/add_expense', $this->data, $meta);

        }
    }

    function edit_expense($id = NULL) {
        if ( ! $this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->load->helper('security');
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->_bloquear_si_electronico('expenses', $id, 'purchases/expenses');
        $this->form_validation->set_rules('reference', lang("reference"), 'required');
        $this->form_validation->set_rules('amount', lang("amount"), 'required|numeric');
        $this->form_validation->set_rules('category_id', lang("categoria_gasto"), 'required|is_natural_no_zero');
        $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $date = trim($this->input->post('date'));
            } else {
                $date = date('Y-m-d H:i:s');
            }
            $data = array_merge(array(
                'date' => $date,
                'reference' => $this->input->post('reference'),
                'amount' => $this->input->post('amount'),
                'note' => $this->input->post('note', TRUE)
            ), $this->_datos_gasto());
            if (!empty($_FILES['userfile']['size'])) {
                $this->load->library('upload');
                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = $this->allowed_types;
                $config['max_size'] = '2000';
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER["HTTP_REFERER"]);
                }
                $photo = $this->upload->file_name;
                $data['attachment'] = $photo;
            }

            //$this->tec->print_arrays($data);

        } elseif ($this->input->post('edit_expense')) {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER["HTTP_REFERER"]);
        }


        if ($this->form_validation->run() == true && $this->purchases_model->updateExpense($id, $data)) {
            $this->session->set_flashdata('message', lang("expense_updated"));
            redirect("purchases/expenses");
        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['expense'] = $this->purchases_model->getExpenseByID($id);
            $this->_opciones_gasto();
            $this->data['page_title'] = lang('edit_expense');
            $bc = array(array('link' => site_url('purchases'), 'page' => lang('purchases')), array('link' => site_url('purchases/expenses'), 'page' => lang('expenses')), array('link' => '#', 'page' => lang('edit_expense')));
            $meta = array('page_title' => lang('edit_expense'), 'bc' => $bc);
            $this->page_construct('purchases/edit_expense', $this->data, $meta);

        }
    }

    function delete_expense($id = NULL) {
        // El enlace tiene que venir de una pantalla de esta sesion.
        $this->exigir_token_accion();

        if(DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if ( ! $this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->_bloquear_si_electronico('expenses', $id, 'purchases/expenses');
        $expense = $this->purchases_model->getExpenseByID($id);
        if ($this->purchases_model->deleteExpense($id)) {
            if ($expense->attachment) {
                unlink($this->upload_path . $expense->attachment);
            }
            $this->session->set_flashdata('message', lang("expense_deleted"));
            redirect('purchases/expenses');
        }
    }
    /**
     * Una compra o un gasto registrado desde un documento electronico se
     * corrige alla: editarlo aca desincroniza el inventario del comprobante.
     */
    private function _bloquear_si_electronico($tabla, $id, $volver) {
        $fila = $this->db->select('documento_id')->get_where($tabla, array('id' => (int) $id), 1)->row();
        if ($fila && $fila->documento_id) {
            $this->session->set_flashdata('error', lang('registro_de_documento_electronico'));
            redirect($volver);
        }
    }

    /** Proveedor, categoria e impuesto del formulario de gasto. */
    private function _datos_gasto() {
        $impuesto = trim((string) $this->input->post('impuesto'));
        return array(
            'category_id' => (int) $this->input->post('category_id') ?: null,
            'supplier_id' => (int) $this->input->post('supplier_id') ?: null,
            'impuesto'    => $impuesto !== '' ? (float) $impuesto : null,
        );
    }

    private function _opciones_gasto() {
        $this->data['categorias_gasto'] = $this->db->order_by('nombre', 'ASC')->get_where('categorias_gasto', array('activo' => 1))->result();
        $this->data['suppliers'] = $this->db->select('id, name, company')->where('deleted', 0)->order_by('name', 'ASC')->get('suppliers')->result();
    }
}
