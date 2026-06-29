<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Suppliers extends MY_Controller
{

    function __construct() {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }

        $this->load->library('form_validation');
        $this->load->model('suppliers_model');
    }

    function index() {

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('suppliers');
        $bc = array(array('link' => '#', 'page' => lang('suppliers')));
        $meta = array('page_title' => lang('suppliers'), 'bc' => $bc);
        $this->page_construct('suppliers/index', $this->data, $meta);
    }

    function get_suppliers() {

        $this->load->library('datatables');
        $this->datatables
        ->select("id, name, phone, email, cf1, cf2,actividad_economica")
        ->from("suppliers")
        ->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('suppliers/edit/$1') . "' class='tip btn btn-warning btn-xs' title='".$this->lang->line("edit_supplier")."'><i class='fa fa-edit'></i></a> <a href='" . site_url('suppliers/delete/$1') . "' data-confirm=\"". $this->lang->line('alert_x_supplier') ."\" class='tip btn btn-danger btn-xs' title='".$this->lang->line("delete_supplier")."'><i class='fa fa-trash-o'></i></a></div></div>", "id")
        ->unset_column('id');

        echo $this->datatables->generate();

    }

    function add() {
        $this->form_validation->set_rules('txtNombre', $this->lang->line("name"), 'required');
        $this->form_validation->set_rules('txtEmail', $this->lang->line("email_address"), 'valid_email');
        if ($this->form_validation->run() == true) {
            $data = array('name' => $this->input->post('txtNombre'),
                'email' => $this->input->post('txtEmail'),
                'phone' => $this->input->post('txtTel'),
                'cf1' => $this->input->post('tcedula'),
                'cf2' => $this->input->post('txtIdentificacion'),
                'codigo_provincia' => $this->input->post('codigo_provincia'),
                'codigo_canton' => $this->input->post('codigo_canton'),
                'codigo_distrito' => $this->input->post('codigo_distrito'),
                'codigo_barrio' => $this->input->post('codigo_barrio'),
                'direccion' => $this->input->post('txtOtraSe'),
                'actividad_economica' => $this->input->post('txtCodActEco')
            );
        }
        if ( $this->form_validation->run() == true && $cid = $this->suppliers_model->addSupplier($data)) {

            if($this->input->is_ajax_request()) {
                echo json_encode(array('status' => 'success', 'msg' =>  $this->lang->line("supplier_added"), 'id' => $cid, 'val' => $data['name']));
                die();
            }
            $this->session->set_flashdata('message', $this->lang->line("supplier_added"));
            if($this->input->post('formFC')!="FEC"){
                redirect("suppliers");
            }else{
                redirect("facturascompras/create_fec");
            }

        } else {
            if($this->input->post('formFC') == "FEC"){
                $this->session->set_flashdata('error', validation_errors());
               redirect("facturascompras/create_fec");
            }else{
            if($this->input->is_ajax_request()) {
                echo json_encode(array('status' => 'failed', 'msg' => validation_errors())); die();
            }
            $this->data['provincia'] = $this->suppliers_model->get_provincia();
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('add_supplier');
            $bc = array(array('link' => site_url('suppliers'), 'page' => lang('suppliers')), array('link' => '#', 'page' => lang('add_supplier')));
            $meta = array('page_title' => lang('add_supplier'), 'bc' => $bc);
            $this->page_construct('suppliers/add', $this->data, $meta);
            }
        }
    }

    function edit($id = NULL) {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', $this->lang->line('access_denied'));
            redirect('pos');
        }
        if($this->input->get('id')) { $id = $this->input->get('id', TRUE); }
        // dd($this->input->post('txtNombre'));
        $this->form_validation->set_rules('txtNombre', $this->lang->line("name"), 'required');
        $this->form_validation->set_rules('txtEmail', $this->lang->line("email_address"), 'valid_email');
        if ($this->form_validation->run() == true) {
            $data = array('name' => $this->input->post('txtNombre'),
                'email' => $this->input->post('txtEmail'),
                'phone' => $this->input->post('txtTel'),
                'cf1' => $this->input->post('tcedula'),
                'cf2' => $this->input->post('txtIdentificacion'),
                'codigo_provincia' => $this->input->post('codigo_provincia'),
                'codigo_canton' => $this->input->post('codigo_canton'),
                'codigo_distrito' => $this->input->post('codigo_distrito'),
                'codigo_barrio' => $this->input->post('codigo_barrio'),
                'direccion' => $this->input->post('txtOtraSe'),
                'actividad_economica' => $this->input->post('txtCodActEco')
            );
        }
        if ( $this->form_validation->run() == true && $this->suppliers_model->updateSupplier($id, $data)) {

            $this->session->set_flashdata('message', $this->lang->line("supplier_updated"));
            redirect("suppliers");

        } else {

            $this->data['supplier'] = $this->suppliers_model->getSupplierByID($id);
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['provincia'] = $this->suppliers_model->get_provincia();
            $this->data['page_title'] = lang('edit_supplier');
            $bc = array(array('link' => site_url('suppliers'), 'page' => lang('suppliers')), array('link' => '#', 'page' => lang('edit_supplier')));
            $meta = array('page_title' => lang('edit_supplier'), 'bc' => $bc);
            $this->page_construct('suppliers/edit', $this->data, $meta);

        }
    }

    function delete($id = NULL) {
        if(DEMO) {
            $this->session->set_flashdata('error', $this->lang->line("disabled_in_demo"));
            redirect('pos');
        }

        if($this->input->get('id')) { $id = $this->input->get('id', TRUE); }

        if (!$this->Admin)
        {
            $this->session->set_flashdata('error', lang("access_denied"));
            redirect('pos');
        }

        if ( $this->suppliers_model->deleteSupplier($id) )
        {
            $this->session->set_flashdata('message', lang("supplier_deleted"));
            redirect("suppliers");
        }

    }

}
