<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Debitnotes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->loggedIn) redirect('login');
        $this->load->helper('pos');
        $this->load->model('pos_model');
        $this->load->model('hacienda_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = 'Notas de Débito';
        $bc = array(array('link' => '#', 'page' => 'Notas de Débito'));
        $meta = array('page_title' => 'Notas de Débito', 'bc' => $bc);
        $this->page_construct('debitnotes/index', $this->data, $meta);
    }

    public function get_debitnotes()
    {
        $this->load->library('datatables');
        $this->datatables->select("nd.id, DATE_FORMAT(nd.date, '%Y-%m-%d %H:%i') as date, nd.customer_name, nd.grand_total, nd.hold_ref, hn.estatus_hacienda, hn.consecutivo");
        $this->datatables->from('note_debits nd');
        $this->datatables->join('hacienda_nd hn', 'hn.nd_id = nd.id', 'left');
        $this->db->order_by('nd.date', 'desc');
        $this->datatables->where('nd.store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column('xmls', "<div class='text-center'><div class='btn-group'>
            <a target='_blank' href='" . site_url('XmlHacienda/xmlFirmadoND/$1') . "' title='Ver XML Firmado' class='tip btn btn-info btn-xs'><i class='fa fa-list'></i></a>
            <a target='_blank' href='" . site_url('XmlHacienda/xmlMensajeND/$1') . "' title='Ver Respuesta Hacienda' class='tip btn btn-warning btn-xs'><i class='fa fa-list'></i></a>
            </div></div>", "nd.id");
        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'>
            <a href='" . site_url('debitnotes/viewnd/$1') . "' title='Ver ND' class='tip btn btn-primary btn-xs'><i class='fa fa-list'></i></a>
            <a href='" . site_url('Shacienda/generarND/$1') . "' title='Enviar a Hacienda' class='tip btn btn-success btn-xs' data-confirm=\"¿Generar y enviar esta ND a Hacienda?\"><i class='fa fa-send'></i></a>
            </div></div>", "nd.id");
        echo $this->datatables->generate();
    }

    public function add($sale_id = null)
    {
        if (!$sale_id) $sale_id = $this->input->get('sale_id');
        if (!$sale_id) { show_404(); }

        $sale = $this->pos_model->getSaleByID($sale_id);
        if (!$sale) { show_404(); }

        $this->data['sale'] = $sale;
        $this->data['sale_items'] = $this->pos_model->getSaleItems($sale_id);
        $this->data['customer'] = $this->pos_model->getCustomerByID($sale->customer_id);
        $this->data['error'] = $this->session->flashdata('error');
        $this->data['page_title'] = 'Nueva Nota de Débito';
        $bc = array(
            array('link' => site_url('debitnotes'), 'page' => 'Notas de Débito'),
            array('link' => '#', 'page' => 'Nueva')
        );
        $meta = array('page_title' => 'Nueva Nota de Débito', 'bc' => $bc);
        $this->page_construct('debitnotes/addnd', $this->data, $meta);
    }

    public function create()
    {
        if (!$this->input->post('sale_id')) {
            $this->session->set_flashdata('error', 'Datos inválidos.');
            redirect('debitnotes');
        }

        $sale_id = (int) $this->input->post('sale_id');
        $sale = $this->pos_model->getSaleByID($sale_id);
        if (!$sale) { show_404(); }

        $items_names  = $this->input->post('item_name');
        $items_qty    = $this->input->post('item_qty');
        $items_price  = $this->input->post('item_price');
        $items_tax    = $this->input->post('item_tax');

        if (empty($items_names)) {
            $this->session->set_flashdata('error', 'Debe agregar al menos un ítem.');
            redirect('debitnotes/add/' . $sale_id);
        }

        $items = array();
        $total = 0;
        $total_tax = 0;
        foreach ($items_names as $k => $name) {
            if (empty($name)) continue;
            $qty   = floatval($items_qty[$k]);
            $price = floatval($items_price[$k]);
            $tax_p = floatval($items_tax[$k]);
            $subtotal = $qty * $price;
            $tax_amt  = $subtotal * ($tax_p / 100);
            $total += $subtotal;
            $total_tax += $tax_amt;
            $items[] = array(
                'product_id'           => 0,
                'product_name'         => $name,
                'product_code'         => 'ND-' . time() . '-' . $k,
                'quantity'             => $qty,
                'unit_price'           => $price,
                'item_tax'             => $tax_amt,
                'tax'                  => $tax_p . '%',
                'discount'             => '0',
                'id_tax'               => 8,
                'unit_of_measurement'  => 'Unid',
            );
        }

        if (empty($items)) {
            $this->session->set_flashdata('error', 'Debe agregar al menos un ítem válido.');
            redirect('debitnotes/add/' . $sale_id);
        }

        $grand_total = $total + $total_tax;

        $data = array(
            'sale_id'        => $sale_id,
            'date'           => date('Y-m-d H:i:s'),
            'customer_id'    => $sale->customer_id,
            'customer_name'  => isset($sale->customer_name) ? $sale->customer_name : '',
            'created_by'     => $this->session->userdata('user_id'),
            'store_id'       => $this->session->userdata('store_id'),
            'total'          => $total,
            'total_tax'      => $total_tax,
            'total_discount' => 0,
            'grand_total'    => $grand_total,
            'motivo_nd'      => $this->input->post('motivo_nd') ? $this->input->post('motivo_nd') : '01',
            'hold_ref'       => $this->input->post('hold_ref') ? $this->input->post('hold_ref') : 'Ajuste de precio',
            'type_nd'        => $this->input->post('type_nd') ? $this->input->post('type_nd') : '01',
            'id_actividad'   => isset($sale->id_actividad) ? $sale->id_actividad : $this->Settings->default_actividad,
        );

        $id_nd = $this->pos_model->addNoteDebit($data, $items);
        if ($id_nd) {
            $this->session->set_flashdata('message', 'Nota de Débito creada. Puede enviarla a Hacienda desde la lista.');
            redirect('debitnotes/viewnd/' . $id_nd);
        } else {
            $this->session->set_flashdata('error', 'Error al guardar la Nota de Débito.');
            redirect('debitnotes/add/' . $sale_id);
        }
    }

    public function viewnd($id_nd = null)
    {
        if ($this->input->get('id')) $id_nd = $this->input->get('id');
        $nd = $this->pos_model->getDebitNoteByID($id_nd);
        if (!$nd) { show_404(); }
        $this->data['nd']       = $nd;
        $this->data['items']    = $this->pos_model->getAllDebitNotesItems($id_nd);
        $this->data['hacienda'] = $this->hacienda_model->getND($id_nd);
        $this->data['error']    = $this->session->flashdata('error');
        $this->data['message']  = $this->session->flashdata('message');
        $this->data['page_title'] = 'Nota de Débito #' . $id_nd;
        $bc = array(
            array('link' => site_url('debitnotes'), 'page' => 'Notas de Débito'),
            array('link' => '#', 'page' => '#' . $id_nd)
        );
        $meta = array('page_title' => 'Nota de Débito', 'bc' => $bc);
        $this->page_construct('debitnotes/viewnd', $this->data, $meta);
    }
}
