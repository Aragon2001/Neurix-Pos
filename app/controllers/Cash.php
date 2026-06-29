<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Cash extends MY_Controller
{


    function __construct()
    {
        parent::__construct();


        if (!$this->loggedIn) {
            redirect('login');
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        }
        $this->load->library('form_validation');
        $this->load->model('purchases_model');
        $this->load->model('cash_model');
        $this->load->model('pos_model');

    }

    function index()
    {

        $solicitud = $this->input->post('solicitud');

        if ($solicitud == 1) {

            $this->retiro();

        } elseif ($solicitud == 0) {

            $this->deposito();

        }


    }

    function deposito()
    {

        $date = date('Y-m-d H:i:s');

        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        }
        $this->load->helper('security');

        $this->form_validation->set_rules('amount', lang("amount"), 'required');
        $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');

        if ($this->form_validation->run() == true) {

            $data = array(
                'date' => $date,
                'reference' => $this->input->post('reference'),
                'amount' => $this->input->post('amount'),
                'created_by' => $this->session->userdata('user_id'),
                'store_id' => $this->session->userdata('store_id'),
                'note' => $this->input->post('note', TRUE)
            );

        }

        if ($this->form_validation->run() == true && $this->cash_model->addDeposit($data)) {
            $this->print_register($data, "Deposito");
            $this->session->set_flashdata('message', 'Deposito Guardado correctamente');
            redirect('pos');

        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['page_title'] = lang('add_expense');
            $bc = array(array('link' => site_url('pos'), 'page' => 'pos'), array('link' => site_url('pos'), 'page' => 'efectivo'), array('link' => '#', 'page' => 'efectivo'));
            $meta = array('page_title' => 'Efectivo', 'bc' => $bc);
            $this->page_construct('pos', $this->data, $meta);

        }

    }

    function retiro()
    {

        $date = date('Y-m-d H:i:s');

        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        }
        $this->load->helper('security');

        $this->form_validation->set_rules('amount', lang("amount"), 'required');
        $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');

        if ($this->form_validation->run() == true) {

            $data = array(
                'date' => $date,
                'reference' => $this->input->post('reference'),
                'amount' => $this->input->post('amount'),
                'created_by' => $this->session->userdata('user_id'),
                'store_id' => $this->session->userdata('store_id'),
                'note' => $this->input->post('note', TRUE)
            );
        }

        if ($this->form_validation->run() == true && $this->purchases_model->addExpense($data)) {

            $this->print_register($data, "Retiro");
            $this->session->set_flashdata('message', 'Retiro Guardado correctamente');
            redirect('pos');

        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['page_title'] = lang('add_expense');
            $bc = array(array('link' => site_url('pos'), 'page' => 'pos'), array('link' => site_url('pos'), 'page' => 'efectivo'), array('link' => '#', 'page' => 'efectivo'));
            $meta = array('page_title' => 'Efectivo', 'bc' => $bc);
            $this->page_construct('pos', $this->data, $meta);
        }
    }

    function print_register($data, $tmov)
    {

        if ($data) {
            $user = $this->pos_model->getUser($this->session->userdata('user_id'));


            $info = array(
                (object)array('label' => 'line', 'value' => ''),
                (object)array('label' => lang('user'), 'value' => $user->first_name . ' ' . $user->last_name),
                (object)array('label' => 'Fecha', 'value' => $this->tec->hrld(date($data['date']))),
                (object)array('label' => 'line', 'value' => ''),
                (object)array('label' => 'space', 'value' => ''),
                (object)array('label' => 'Referencia', 'value' => $data['reference']),
                (object)array('label' => 'Monto Recibido: ', 'value' => number_format($data['amount'], 2, ",", ".")),
                (object)array('label' => 'Descripcion del  ' . $tmov . ': ', 'value' => $data['note']),
                (object)array('label' => 'space', 'value' => ''),
                (object)array('label' => 'line', 'value' => ''),
                (object)array('label' => 'space', 'value' => ''),
                (object)array('label' => 'Recibido por', 'value' => ''),
                (object)array('label' => 'space', 'value' => ''),
                (object)array('label' => 'space', 'value' => ''),
                (object)array('label' => 'Firma', 'value' => ''),
            );

        }
        $data = (object)array(
            'heading' => lang('Recibo de ' . $tmov),
            'info' => $info
        );
        $store = $this->site->getStoreByID($this->session->userdata('store_id'));
        $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        if ($printer && $printer->type != "web") {
            $this->load->library('escpos');
            $this->escpos->load($printer);
            $this->escpos->print_data($data, $store);
        }
    }

}
