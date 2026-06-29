<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class CreditNotes extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        $this->load->helper('pos');
        $this->load->model('pos_model');
        $this->load->model('hacienda_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('credit_notes');
        $bc = array(array('link' => '#', 'page' => lang('credit_notes')));
        $meta = array('page_title' => lang('credit_notes'), 'bc' => $bc);
        $this->page_construct('creditnotes/index', $this->data, $meta);
    }

    function get_creditnotes() {

        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("id, strftime('%Y-%m-%d %H:%M', date) as date, customer_name, total, total_tax, total_discount, grand_total, paid, estatus_hacienda as status,cn.consecutivo as consecutivo");
        } else {
            $this->datatables->select("note_credits.id as id, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date, customer_name, total, total_tax, total_discount, grand_total, paid, note_credits.estatus_hacienda as status, cn.consecutivo as consecutivo");
        }
        $this->datatables->from('note_credits');
        $this->datatables->join('hacienda_cn cn', 'cn.id_cn = note_credits.id', 'left');
        $this->db->order_by("date","desc");
        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column('status_hacienda', "<div class='text-center'><div class='btn-group'>
            <a target='_blank' href='" . site_url('XmlHacienda/xmlFirmadoCN/$1') . "' title='Ver XML Firmado' class='tip btn btn-info btn-xs' ><i class='fa fa-list'></i></a>
            <a target='_blank' href='" . site_url('XmlHacienda/xmlMensajeCN/$1') . "' title='Ver XML de Respuesta' class='tip btn btn-warning btn-xs' ><i class='fa fa-list'></i></a> 
            </div></div>", "id");
        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a target='_blank' href='" . site_url('creditnotes/viewnc/$1') . "' title='".lang("view_credit_note")."' class='tip btn btn-primary btn-xs'><i class='fa fa-list'></i></a>  </div></div>", "id");

        echo $this->datatables->generate();

    }

    function viewnc($id_cn = NULL, $noprint = NULL)
    {
        if ($noprint != NULL) {
            $noprint = NULL;
        }
        if ($this->input->get('id')) {
            $id_cn = $this->input->get('id');
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv = $this->pos_model->getCreditNoteByID($id_cn);
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        } elseif ($this->session->userdata('store_id') != $inv->store_id) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('welcome');
        }
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows'] = $this->pos_model->getAllCreditNotesItems($id_cn);
        $this->data['customer'] = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['store'] = $this->site->getStoreByID($inv->store_id);
        $this->data['inv'] = $inv;
        $this->data['sid'] = $id_cn;
        $this->data['noprint'] = $noprint;
        $this->data['modal'] = $noprint ? true : false;
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['printer'] = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        $this->data['store'] = $this->site->getStoreByID($inv->store_id);
        $this->data['page_title'] = lang("invoice");
        $this->data['hacienda'] = $this->hacienda_model->getCN($id_cn);
        $this->data['haciendaInvo'] = $this->hacienda_model->getInvoice($inv->sale_id);
        $this->data['invoicebarcode'] = $this->invice_barcode($this->data['hacienda']->consecutivo, 'code128', 60);

        $this->load->view($this->theme . 'creditnotes/' . ($this->Settings->print_img ? 'eviewnc' : 'viewnc'), $this->data);

    }

    function open_drawer()
    {
        $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        if (!$printer) { redirect($_SERVER['HTTP_REFERER']); return; }
        $printer->ip = $this->Settings->ip_printer;
        $printer->nombrecompartido = $this->Settings->nombrecompartido;
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->open_drawer();
        redirect($_SERVER['HTTP_REFERER']);

    }


    function print_receipt($id, $open_drawer = false, $type_document = 3)
    {
        if ($type_document == 3) {
            $sale = $this->pos_model->getCreditNoteByID($id);
            $sale->hacienda = $this->hacienda_model->getCN($id);
            $sale->type_doc = lang("elect_credit_note");
            $sale->footerhacienda = $this->Settings->footer_hacienda_nc;
            $items = $this->pos_model->getAllCreditNotesItems($id);

        }
        $haciendaInvo = $this->hacienda_model->getInvoice($sale->sale_id);
        $sale->invice_barcode = $this->invice_barcode_2($sale->hacienda->consecutivo, 'code128', 60);
        $sale->haciendaInvo = $haciendaInvo;
        $payments = $this->pos_model->getAllSalePayments($id);
        $store = $this->site->getStoreByID($sale->store_id);
        $created_by = $this->site->getUser($sale->created_by);
        $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->print_receipt($store, $sale, $items, $payments, $created_by, $open_drawer);

    }

    function invice_barcode($id_invoice = NULL, $bcs = 'code128', $height = 60)
    {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode($id_invoice, $bcs, $height);
    }

    function invice_barcode_2($id_invoice = NULL, $bcs = 'code128', $height = 60)
    {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode64($id_invoice, $bcs, $height);
    }

}
