<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sales extends MY_Controller {

    function __construct() {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        }
        $this->load->library('form_validation');
        $this->load->model('sales_model');
        $this->load->helper('text');

        $this->digital_file_types = 'zip|pdf|doc|docx|xls|xlsx|jpg|png|gif';
    }

    function index() {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('sales');
        $bc = array(array('link' => '#', 'page' => lang('sales')));
        $meta = array('page_title' => lang('sales'), 'bc' => $bc);
        $this->page_construct('sales/index', $this->data, $meta);
    }

    function get_sales() {

        $this->load->library('datatables');
        
        $this->datatables->select("id, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date, customer_name, total, total_tax, total_discount, grand_total, paid, ht.estatus_hacienda,ht.consecutivo, status");
        $this->datatables->from('sales');
        $this->datatables->join('hacienda_tiketes ht', 'ht.sale_id = sales.id', 'left');
        $this->db->order_by("date","desc");

        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column('status_hacienda', "<div class='text-center'><div class='btn-group'>
            <a target='_blank' href='" . site_url('XmlHacienda/xmlFirmado/$1') . "' title='Ver XML Firmado' class='tip btn btn-info btn-xs' ><i class='fa fa-list'></i></a>
            <a target='_blank' href='" . site_url('XmlHacienda/xmlMensaje/$1') . "' title='Ver XML de Respuesta' class='tip btn btn-warning btn-xs' ><i class='fa fa-list'></i></a> 
            </div></div>", "id");
//        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('pos/view/$1/1') . "' title='".lang("view_invoice")."' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-list'></i></a> <a href='".site_url('sales/payments/$1')."' title='" . lang("view_payments") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-money'></i></a> <a href='".site_url('sales/add_payment/$1')."' title='" . lang("add_payment") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-briefcase'></i></a> <a href='" . site_url('pos/?edit=$1') . "' title='".lang("edit_invoice")."' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('sales/delete/$1') . "' onClick=\"return confirm('". lang('alert_x_sale') ."')\" title='".lang("delete_sale")."' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", "id");
        
        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a  href='" . site_url('sales/abort/$1') . "' title='" . lang("view_abort") . "' class='tip btn btn-primary btn-xs'><i class='fa fa-ban'></i></a><a  href='" . site_url('pos/?redo=$1') . "' title='" . lang("re_create") . "' class='tip btn btn-primary btn-xs'><i class='fa fa-repeat'></i></a><a target='_blank' href='" . site_url('pos/view/$1') . "' title='" . lang("view_invoice") . "' class='tip btn btn-primary btn-xs'><i class='fa fa-list'></i></a> <a href='" . site_url('sales/payments/$1') . "' title='" . lang("view_payments") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-money'></i></a> <a href='" . site_url('sales/add_payment/$1') . "' title='" . lang("add_payment") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-briefcase'></i></a> </div></div>", "id");
        // $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    function get_apartado() {

        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("id, strftime('%Y-%m-%d %H:%M', date) as date, customer_name, total, total_tax, total_discount, grand_total, paid, status");
        } else {
            $this->datatables->select("id, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date, customer_name, total, total_tax, total_discount, grand_total, paid, status");
        }

        $this->datatables->from('layaway');

        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));

        $confi = 'return confirm("Esta seguro que desea anular este apartado??");';
        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('pos/?aparta=$1') . "' title='" . lang("click_to_add") . "' class='tip btn btn-info btn-xs' id='aparta$1'><i class='fa fa-th-large'></i></a> 
        
        <a href='" . site_url('sales/payments_apartado/$1') . "' title='" . lang("view_payments") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-money'></i></a> 
        <a href='" . site_url('sales/add_payment_apartado/$1') . "' title='" . lang("add_payment") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-briefcase'></i></a>
         <a href='" . site_url('sales/apartadoAnular/?anular=$1') . "' onclick='" . $confi . "' title='Anular Apartado' class='tip btn btn-danger btn-xs' id='aparta$1'><i class='fa fa-ban'></i></a> </div></div>", "id");
        
        echo $this->datatables->generate();
    }

    function get_proformas() {

        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("id, strftime('%Y-%m-%d %H:%M', date) as date, customer_name, total, total_tax, total_discount, grand_total");
        } else {
            $this->datatables->select("id, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date, customer_name, total, total_tax, total_discount, grand_total");
        }
        $this->datatables->from('quotes');
        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
       $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('pos/?quotes=$1') . "' title='" . lang("click_to_add") . "' class='tip btn btn-info btn-xs'><i class='fa fa-th-large'></i></a> <a target='_blank' href='" . site_url('pos/view_proforma/$1') . "' title='" . lang("view_quotes") . "' class='tip btn btn-primary btn-xs'><i class='fa fa-list'></i></a>", "id");
       
        echo $this->datatables->generate();
    }

    function opened() {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('opened_bills');
        $bc = array(array('link' => '#', 'page' => lang('opened_bills')));
        $meta = array('page_title' => lang('opened_bills'), 'bc' => $bc);
        $this->page_construct('sales/opened', $this->data, $meta);
    }

    function proforma() {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('Quotes_sales');
        $bc = array(array('link' => '#', 'page' => lang('Quotes_sales')));
        $meta = array('page_title' => lang('Quotes_sales'), 'bc' => $bc);
        $this->page_construct('sales/proforma', $this->data, $meta);
    }

    function apartado() {

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('Apartados_sales');
        $bc = array(array('link' => '#', 'page' => lang('Apartados_sales')));
        $meta = array('page_title' => lang('Apartados_sales'), 'bc' => $bc);
        $this->page_construct('sales/apartado', $this->data, $meta);
    }

    function apartadoAnular($anular = NULL) {

        if ($this->input->get('anular')) {
            $anular = $this->input->get('anular');
        }

        if ($anular) {
            if ($mensaje = $this->sales_model->Anularapartado($anular)) {
                $msg = $mensaje['msg'];
                if ($mensaje['Status'] == 1)
                    $this->session->set_flashdata('message', $msg);
                else
                    $this->session->set_flashdata('error', $msg);
                redirect('sales/apartado');
            }
        }
    }

    function get_opened_list() {

        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("id, date, customer_name, CONCAT(hold_ref,'(',note,')')as hold_ref, (total_items || ' (' || total_quantity || ')') as items, grand_total", FALSE);
        } else {
            if($this->Settings->propina_enable == "1")
            {
                $this->datatables->select("id, date, customer_name, CONCAT(hold_ref,'(',note,')')as hold_ref, (total_items || ' (' || total_quantity || ')') as items, grand_total", FALSE);
            }
            else
            {
                $this->datatables->select("id, date, customer_name, hold_ref, CONCAT(total_items, ' (', total_quantity, ')') as items, grand_total", FALSE);
            }
        }
        $this->datatables->from('suspended_sales');
        if (!$this->Admin) {
            $user_id = $this->session->userdata('user_id');
            $this->datatables->where('created_by', $user_id);
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('pos/?hold=$1') . "' title='" . lang("click_to_add") . "' class='tip btn btn-info btn-xs'><i class='fa fa-th-large'></i></a>
            <a href='" . site_url('sales/delete_holded/$1') . "' onClick=\"return confirm('" . lang('alert_x_holded') . "')\" title='" . lang("delete_sale") . "' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", "id")
                ->unset_column('id');

        echo $this->datatables->generate();
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
            $this->session->set_flashdata('error', lang("access_denied"));
            redirect('sales');
        }

        if ($this->sales_model->deleteInvoice($id)) {
            $this->session->set_flashdata('message', lang("invoice_deleted"));
            redirect('sales');
        }
    }

    function delete_holded($id = NULL) {

        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang("access_denied"));
            redirect('sales/opened');
        }

        if ($this->sales_model->deleteOpenedSale($id)) {
            $this->session->set_flashdata('message', lang("opened_bill_deleted"));
            redirect('sales/opened');
        }
    }

    /* -------------------------------------------------------------------------------- */

    function payments($id = NULL) {
        $this->data['payments'] = $this->sales_model->getSalePayments($id);
        $this->load->view($this->theme . 'sales/payments', $this->data);
    }

    function payments_apartado($id = NULL) {
        $this->data['payments'] = $this->sales_model->getSalePaymentsapartado($id);
        $this->data['id_apartado'] = $id;
        $this->load->view($this->theme . 'sales/payments_apartado', $this->data);
    }

    function payment_note($id = NULL) {
        $payment = $this->sales_model->getPaymentByID($id);
        $inv = $this->sales_model->getSaleByID($payment->sale_id);
        $this->data['customer'] = $this->site->getCompanyByID($inv->customer_id);
        $this->data['inv'] = $inv;
        $this->data['payment'] = $payment;
        $this->data['page_title'] = $this->lang->line("payment_note");

        $this->load->model('hacienda_model');
        $this->data['hacienda']    = $this->hacienda_model->getInvoice($inv->id);
        $this->data['rep_exists']  = (bool) $this->hacienda_model->getREP($id);

        $this->load->view($this->theme . 'sales/payment_note', $this->data);
    }

    function add_payment($id = NULL, $cid = NULL) {

        $this->load->helper('security');
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->form_validation->set_rules('amount-paid', lang("amount"), 'required');
        $this->form_validation->set_rules('paid_by', lang("paid_by"), 'required');
        $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $date = $this->input->post('date');
            } else {
                $date = date('Y-m-d H:i:s');
            }
            $payment = array(
                'date' => $date,
                'sale_id' => $id,
                'customer_id' => $cid,
                'reference' => $this->input->post('reference'),
                'amount' => $this->input->post('amount-paid'),
                'paid_by' => $this->input->post('paid_by'),
                'cheque_no' => $this->input->post('cheque_no'),
                'gc_no' => $this->input->post('gift_card_no'),
                'cc_no' => $this->input->post('pcc_no'),
                'cc_holder' => $this->input->post('pcc_holder'),
                'cc_month' => $this->input->post('pcc_month'),
                'cc_year' => $this->input->post('pcc_year'),
                'cc_type' => $this->input->post('pcc_type'),
                'note' => $this->input->post('note'),
                'created_by' => $this->session->userdata('user_id'),
                'store_id' => $this->session->userdata('store_id'),
            );

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path'] = 'files/';
                $config['allowed_types'] = $this->digital_file_types;
                $config['max_size'] = 2048;
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER["HTTP_REFERER"]);
                }
                $photo = $this->upload->file_name;
                $payment['attachment'] = $photo;
            }

            // $this->tec->print_arrays($payment);
        } elseif ($this->input->post('add_payment')) {
            $this->session->set_flashdata('error', validation_errors());
            $this->tec->dd();
        }


        if ($this->form_validation->run() == true && $this->sales_model->addPayment($payment)) {
            $this->session->set_flashdata('message', lang("payment_added"));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $sale = $this->sales_model->getSaleByID($id);
            $this->data['inv'] = $sale;

            $this->load->view($this->theme . 'sales/add_payment', $this->data);
        }
    }

    function add_payment_apartado($id = NULL, $cid = NULL) {

        $this->load->helper('security');
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->form_validation->set_rules('amount-paid', lang("amount"), 'required');
        $this->form_validation->set_rules('paid_by', lang("paid_by"), 'required');
        $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $date = $this->input->post('date');
            } else {
                $date = date('Y-m-d H:i:s');
            }
            $payment = array(
                'date' => $date,
                'apartado_id' => $id,
                'customer_id' => $cid,
                'reference' => $this->input->post('reference'),
                'amount' => $this->input->post('amount-paid'),
                'paid_by' => $this->input->post('paid_by'),
                'cheque_no' => $this->input->post('cheque_no'),
                'gc_no' => $this->input->post('gift_card_no'),
                'cc_no' => $this->input->post('pcc_no'),
                'cc_holder' => $this->input->post('pcc_holder'),
                'cc_month' => $this->input->post('pcc_month'),
                'cc_year' => $this->input->post('pcc_year'),
                'cc_type' => $this->input->post('pcc_type'),
                'note' => $this->input->post('note'),
                'created_by' => $this->session->userdata('user_id'),
                'store_id' => $this->session->userdata('store_id'),
            );

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path'] = 'files/';
                $config['allowed_types'] = $this->digital_file_types;
                $config['max_size'] = 2048;
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER["HTTP_REFERER"]);
                }
                $photo = $this->upload->file_name;
                $payment['attachment'] = $photo;
            }

            // $this->tec->print_arrays($payment);
        } elseif ($this->input->post('add_payment_apartado')) {
            $this->session->set_flashdata('error', validation_errors());
            $this->tec->dd();
        }

        if(isset($payment)){
            $id_payment = $this->sales_model->addPaymentapartado($payment);
        }
                
        if ($this->form_validation->run() == true && $id_payment) {
            $this->print_receipt($id_payment);
            $this->session->set_flashdata('message', lang("payment_added"));
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $sale = $this->sales_model->getSaleByIDapartado($id);
            $this->data['inv'] = $sale;

            $this->load->view($this->theme . 'sales/add_payment_apartado', $this->data);
        }
    }

    function printAllReceiptApartado($apartado_id){
        $payments = $this->sales_model->getSalePaymentsapartado($apartado_id);
        foreach ($payments as $py){
            $this->print_receipt($py->id);
        }
        
        
    }
    
    function print_receipt($id_payment = NULL) {

        $this->load->model('pos_model');
        $payment = $this->sales_model->getPaymentByIDapartado($id_payment);
        $operacion = $this->sales_model->getSaleByIDapartado($payment->apartado_id);
        $operacionItems = $this->sales_model->getAllApartadoItems($payment->apartado_id);
        $cliente = $this->sales_model->getCustomerByID($payment->customer_id);
        $paymentsAfter = $this->sales_model->getPaymentsAfterApartados($id_payment, $payment->apartado_id);
       
        
        if ($payment) {

            $user = $this->pos_model->getUser($payment->created_by);


            $info = array(
                (object) array('label' => "Recibo de Pago # ", 'value' => str_pad($payment->id, 12, "0", STR_PAD_LEFT)),
                (object) array('label' => "Fecha de Pago", 'value' => $this->tec->hrld($payment->date)),
                (object) array('label' => "Cliente", 'value' => $cliente->name),
                (object) array('label' => "Email", 'value' => $cliente->email),
                (object) array('label' => "Cajero", 'value' => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'),
                (object) array('label' => 'line', 'value' => ''),
            );



            $r = 1;
            $totImpuesto = 0;
            $totSimpuesto = 0;
            $totCimpuesto = 0;
            $reg_items = array();
            foreach ($operacionItems as $item) {
                if ($item->tax > 0) {
                    $tt = "(G)";
                } else {
                    $tt = "(E)";
                }
                $totImpuesto = $totImpuesto + $item->item_tax;
                $totSimpuesto = $totSimpuesto + $item->net_unit_price;
                $totCimpuesto = $totCimpuesto + ($item->net_unit_price + ($item->item_tax / $item->quantity));
                array_push($reg_items,
                    (object) array(
                        'product_name' => $item->product_name.$tt, 
                        'quantity' => $item->quantity, 
                        'unit_price' => $this->tec->formatMoney($item->net_unit_price + ($item->item_tax / $item->quantity)), 
                        'item_tax' => $this->tec->formatMoney($item->item_tax), 
                        'subtotal' => $this->tec->formatMoney($item->subtotal)
                        )
                );
                $r++;
            }

            $pymentsAfterThis = 0;
            if($paymentsAfter){
                foreach($paymentsAfter as $paidA){
                    $pymentsAfterThis = $pymentsAfterThis + $paidA->amount;
                    
                }
            }
            if(($payment->amount - $operacion->paid) == 0){
                $saldoA = $operacion->grand_total;
            }else{
                $saldoA = $operacion->grand_total - (($operacion->paid - $pymentsAfterThis) - $payment->amount);
            }
            
            $reg_totals = array(
                (object) array('label' => 'line', 'value' => ''),
                (object) array('label' => 'Total a Pagar', 'value' => $this->tec->formatMoney($operacion->grand_total ? $operacion->grand_total : '0.00')),
                (object) array('label' => 'line', 'value' => ''),
                (object) array('label' => 'Saldo Anterior', 'value' => $this->tec->formatMoney($saldoA ? $saldoA : '0.00')),
                (object) array('label' => 'Abonado', 'value' => $this->tec->formatMoney($payment->amount ? $payment->amount : '0.00')),
                (object) array('label' => 'Saldo Actual', 'value' => $this->tec->formatMoney($saldoA - $payment->amount))
            );
            
            
            $sign = array(
                (object) array('label' => 'line', 'value' => ''),
                (object) array('label' => 'text', 'value' => "Recibido Por:")
            );
            
            $data = (object) array(
                        'heading' => "Recibo de Pago",
                        'info' => $info,
                        'items' => $reg_items,
                        'totals' => $reg_totals,
                        'sign' => $sign
            );
            
        }

        // $this->tec->print_arrays($data);
            $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
            $this->load->library('escpos');
            $this->escpos->load($printer);
            $this->escpos->print_data($data);
    }

    function product_name($name) {
        return character_limiter($name, (get_printer_chars_per_line() - 8));
    }

    function edit_payment($id = NULL, $sid = NULL) {

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang("access_denied"));
            redirect($_SERVER["HTTP_REFERER"]);
        }
        $this->load->helper('security');
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->form_validation->set_rules('amount-paid', lang("amount"), 'required');
        $this->form_validation->set_rules('paid_by', lang("paid_by"), 'required');
        $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');
        if ($this->form_validation->run() == true) {
            $payment = array(
                'sale_id' => $sid,
                'reference' => $this->input->post('reference'),
                'amount' => $this->input->post('amount-paid'),
                'paid_by' => $this->input->post('paid_by'),
                'cheque_no' => $this->input->post('cheque_no'),
                'gc_no' => $this->input->post('gift_card_no'),
                'cc_no' => $this->input->post('pcc_no'),
                'cc_holder' => $this->input->post('pcc_holder'),
                'cc_month' => $this->input->post('pcc_month'),
                'cc_year' => $this->input->post('pcc_year'),
                'cc_type' => $this->input->post('pcc_type'),
                'note' => $this->input->post('note'),
                'updated_by' => $this->session->userdata('user_id'),
                'updated_at' => date('Y-m-d H:i:s'),
            );

            if ($this->Admin) {
                $payment['date'] = $this->input->post('date');
            }

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path'] = 'files/';
                $config['allowed_types'] = $this->digital_file_types;
                $config['max_size'] = 2048;
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER["HTTP_REFERER"]);
                }
                $photo = $this->upload->file_name;
                $payment['attachment'] = $photo;
            }

            //$this->tec->print_arrays($payment);
        } elseif ($this->input->post('edit_payment')) {
            $this->session->set_flashdata('error', validation_errors());
            $this->tec->dd();
        }


        if ($this->form_validation->run() == true && $this->sales_model->updatePayment($id, $payment)) {
            $this->session->set_flashdata('message', lang("payment_updated"));
            redirect("sales");
        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $payment = $this->sales_model->getPaymentByID($id);
            if ($payment->paid_by != 'cash') {
                $this->session->set_flashdata('error', lang('only_cash_can_be_edited'));
                $this->tec->dd();
            }
            $this->data['payment'] = $payment;
            $this->load->view($this->theme . 'sales/edit_payment', $this->data);
        }
    }

    function edit_payment_apartado($id = NULL, $apart = NULL) {

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang("access_denied"));
            redirect($_SERVER["HTTP_REFERER"]);
        }
        $this->load->helper('security');
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->form_validation->set_rules('amount-paid', lang("amount"), 'required');
        $this->form_validation->set_rules('paid_by', lang("paid_by"), 'required');
        $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');
        if ($this->form_validation->run() == true) {
            $payment = array(
                'apartado_id' => $apart,
                'reference' => $this->input->post('reference'),
                'amount' => $this->input->post('amount-paid'),
                'paid_by' => $this->input->post('paid_by'),
                'cheque_no' => $this->input->post('cheque_no'),
                'gc_no' => $this->input->post('gift_card_no'),
                'cc_no' => $this->input->post('pcc_no'),
                'cc_holder' => $this->input->post('pcc_holder'),
                'cc_month' => $this->input->post('pcc_month'),
                'cc_year' => $this->input->post('pcc_year'),
                'cc_type' => $this->input->post('pcc_type'),
                'note' => $this->input->post('note'),
                'updated_by' => $this->session->userdata('user_id'),
                'updated_at' => date('Y-m-d H:i:s'),
            );

            if ($this->Admin) {
                $payment['date'] = $this->input->post('date');
            }

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path'] = 'files/';
                $config['allowed_types'] = $this->digital_file_types;
                $config['max_size'] = 2048;
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER["HTTP_REFERER"]);
                }
                $photo = $this->upload->file_name;
                $payment['attachment'] = $photo;
            }

            //$this->tec->print_arrays($payment);
        } elseif ($this->input->post('edit_payment_apartado')) {
            $this->session->set_flashdata('error', validation_errors());
            $this->tec->dd();
        }


        if ($this->form_validation->run() == true && $this->sales_model->updatePaymentapartado($id, $payment)) {
            $this->session->set_flashdata('message', lang("payment_updated"));
            redirect("sales/apartado");
        } else {

            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $payment = $this->sales_model->getPaymentByIDapartado($id);
            if ($payment->paid_by != 'cash') {
                $this->session->set_flashdata('error', lang('only_cash_can_be_edited'));
                $this->tec->dd();
            }

            $this->data['payment'] = $payment;
            $this->load->view($this->theme . 'sales/edit_payment_apartado', $this->data);
        }
    }

    function delete_payment($id = NULL) {

        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang("access_denied"));
            redirect($_SERVER["HTTP_REFERER"]);
        }

        if ($this->sales_model->deletePayment($id)) {
            $this->session->set_flashdata('message', lang("payment_deleted"));
            redirect('sales');
        }
    }

    function delete_payment_apartado($id = NULL) {

        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang("access_denied"));
            redirect($_SERVER["HTTP_REFERER"]);
        }

        if ($this->sales_model->deletePaymentapartado($id)) {
            $this->session->set_flashdata('message', lang("payment_deleted"));
            redirect('sales/apartado');
        }
    }

    public function status() {
        if (!$this->Admin) {
            $this->session->set_flashdata('warning', lang('access_denied'));
            redirect('sales');
        }
        $this->form_validation->set_rules('sale_id', lang('sale_id'), 'required');
        $this->form_validation->set_rules('status', lang('status'), 'required');

        if ($this->form_validation->run() == true) {

            $this->sales_model->updateStatus($this->input->post('sale_id', TRUE), $this->input->post('status', TRUE));
            $this->session->set_flashdata('message', lang('status_updated'));
            redirect('sales');
        } else {

            $this->session->set_flashdata('error', validation_errors());
            redirect('sales');
        }
    }

    public function abort($id = NULL){
        $salesItems = $this->sales_model->getAllSaleItems($id);
        $data = $this->hacienda_model->getInvoice($id);
        if($data->estatus_hacienda != "anulado"){
            if($data->estatus_hacienda == "rechazado"){
                foreach($salesItems as $dat)
                {
                    $q = $this->db->get_where('tec_product_store_qty', array('product_id' => $dat->product_id), 1);
                    $item = $q->row();
                    $item->quantity+=1;
                    $this->db->update($this->db->dbprefix('product_store_qty'), $item, array('product_id' => $dat->product_id));
                }
            $data->estatus_hacienda = "anulado";
            $this->db->update($this->db->dbprefix('hacienda_tiketes'), $data, array('sale_id' => $id));
            }else{
                $this->session->set_flashdata('error', "El estado de hacienda debe estar en rechazado");
            }
        }else{
            $this->session->set_flashdata('error', "Ya ha sido anulado");
        }
        redirect('sales');
    }

}
