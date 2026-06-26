<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PosView extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('pos_model');
        $this->load->model('hacienda_model');
    }

    function view($sale_id = NULL, $noprint = NULL) {
        if ($noprint != NULL) {
            $noprint = NULL;
        }
        if ($this->input->get('id')) {
            $sale_id = $this->input->get('id');
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv = $this->pos_model->getSaleByID($sale_id);
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        } elseif ($this->session->userdata('store_id') != $inv->store_id) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('welcome');
        }
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows'] = $this->pos_model->getAllSaleItems($sale_id);
        $this->data['customer'] = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['store'] = $this->site->getStoreByID($inv->store_id);
        $this->data['inv'] = $inv;
        $this->data['sid'] = $sale_id;
        $this->data['noprint'] = $noprint;
        $this->data['modal'] = $noprint ? true : false;
        $this->data['payments'] = $this->pos_model->getAllSalePayments($sale_id);
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['printer'] = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        //$this->data['store'] = $this->site->getStoreByID($inv->store_id);
        $this->data['page_title'] = lang("invoice");
        $this->data['hacienda'] = $this->hacienda_model->getInvoice($sale_id);
        $this->data['invoicebarcode'] = isset($this->data['hacienda']->consecutivo) ? $this->invice_barcode($this->data['hacienda']->consecutivo, 'code128', 60) : null;
        $this->load->view($this->theme . 'pos/' . ($this->Settings->print_img ? 'eview' : 'view'), $this->data);
    }

    function view_proforma($sale_id = NULL, $noprint = NULL) {
        if ($noprint != NULL) {
            $noprint = NULL;
        }
        if ($this->input->get('id')) {
            $sale_id = $this->input->get('id');
        }


        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv = $this->pos_model->getQuoteByID($sale_id);
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        } elseif ($this->session->userdata('store_id') != $inv->store_id) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('welcome');
        }
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows'] = $this->pos_model->getAllQuoteItems($sale_id);
        $this->data['customer'] = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['store'] = $this->site->getStoreByID($inv->store_id);
        $this->data['inv'] = $inv;
        $this->data['sid'] = $sale_id;
        $this->data['noprint'] = $noprint;
        $this->data['modal'] = $noprint ? true : false;
        $this->data['payments'] = null;
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['printer'] = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        $this->data['store'] = $this->site->getStoreByID($inv->store_id);
        $this->data['page_title'] = lang("invoice");

        $this->load->view($this->theme . 'pos/' . ($this->Settings->print_img ? 'view_proforma' : 'view_proforma'), $this->data);
    }
 
    function viewnc($id_cn = NULL, $noprint = NULL) {
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

        $this->load->view($this->theme . 'pos/' . ($this->Settings->print_img ? 'eviewnc' : 'viewnc'), $this->data);
    }

    function view_close_register($id) {
        echo "En desarrollo....";
        exit();
        if (!$this->Admin) {
            $user_id = $this->session->userdata('user_id');
        }

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

        $this->data['all'] = $this->pos_model->getCierre($id);

        $this->data['ccsales'] = $this->pos_model->getRegisterCCSales('2018-06-25', $user_id);
        $this->data['cashsales'] = $this->pos_model->getRegisterCashSales($register_open_time, $user_id);
        $this->data['chsales'] = $this->pos_model->getRegisterChSales($register_open_time, $user_id);
        $this->data['other_sales'] = $this->pos_model->getRegisterOtherSales($register_open_time, $user_id);
        $this->data['gcsales'] = $this->pos_model->getRegisterGCSales($register_open_time, $user_id);
        $this->data['stripesales'] = $this->pos_model->getRegisterStripeSales($register_open_time, $user_id);
        $this->data['totalsales'] = $this->pos_model->getRegisterSales($register_open_time, $user_id);
        $this->data['expenses'] = $this->pos_model->getRegisterExpenses($register_open_time);
        $this->data['users'] = $this->tec->getUsers($user_id);
        $this->data['suspended_bills'] = $this->pos_model->getSuspendedsales($user_id);

        $this->data['user_id'] = $user_id;


        $this->load->view($this->theme . 'pos/view_close_register', $this->data);
    }

    function invice_barcode($id_invoice = NULL, $bcs = 'code128', $height = 60) {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode($id_invoice, $bcs, $height);
    }

    function invice_barcode_2($id_invoice = NULL, $bcs = 'code128', $height = 60) {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode64($id_invoice, $bcs, $height);
    }
}