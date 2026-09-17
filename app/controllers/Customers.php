<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends MY_Controller
{

    function __construct() {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }

        $this->load->library('form_validation');
        $this->load->model('customers_model');
    }

    function index() {

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('customers');
        $bc = array(array('link' => '#', 'page' => lang('customers')));
        $meta = array('page_title' => lang('customers'), 'bc' => $bc);
        $this->page_construct('customers/index', $this->data, $meta);
    }

    function get_customers() {

        $this->load->library('datatables');
        $this->datatables
        ->select("id, name, phone, email, cf1, cf2, limitcredit")
        ->from("customers")
        ->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('customers/edit/$1') . "' class='tip btn btn-warning btn-xs' title='".$this->lang->line("edit_customer")."'><i class='fa fa-edit'></i></a> <a href='" . site_url('customers/delete/$1' . '?t=' . $this->token_accion()) . "' data-confirm=\"". $this->lang->line('alert_x_customer') ."\" class='tip btn btn-danger btn-xs' title='".$this->lang->line("delete_customer")."'><i class='fa fa-trash-o'></i></a></div></div>", "id")
        ->unset_column('id');

        echo $this->datatables->generate();

    }

    function add() {

        $this->form_validation->set_rules('name', $this->lang->line("name"), 'required');
        $this->form_validation->set_rules('email', $this->lang->line("email_address"), 'valid_email');

        $duplicado = NULL;

        if ($this->form_validation->run() == true) {
            $data      = $this->_datos_cliente();
            $duplicado = $data['cf2'] ? $this->customers_model->getCustomerByCedula($data['cf2']) : FALSE;
        }

        if ( $this->form_validation->run() == true && !$duplicado && $cid = $this->customers_model->addCustomer($data)) {

            // Antes de cualquier salida: el alta por AJAX tambien guarda las
            // actividades que devolvio el padron.
            $this->_guardar_actividades($cid);

            if($this->input->is_ajax_request()) {
                echo json_encode(array(
                    'status' => 'success',
                    'msg'    => $this->lang->line("customer_added"),
                    'id'     => $cid,
                    'val'    => $data['name'],
                    'customer' => array(
                        'name'    => $data['name'],
                        'cf1'     => $data['cf1'] ?? '',
                        'cf2'     => $data['cf2'] ?? '',
                        'email'   => $data['email'] ?? '',
                        'phone'   => $data['phone'] ?? '',
                        'company' => $data['business_name'] ?? '',
                        'credito' => (float) ($data['limitcredit'] ?? 0),
                    ),
                ));
                die();
            }
            $this->session->set_flashdata('message', $this->lang->line("customer_added"));
            redirect("customers");

        } else {
            $error = validation_errors()
                ? validation_errors()
                : ($duplicado ? sprintf(lang('cliente_cedula_duplicada'), $duplicado->cf2, $duplicado->name) : '');

            if($this->input->is_ajax_request()) {
                echo json_encode(array(
                    'status'      => 'failed',
                    'msg'         => $error ?: lang('customer_add_failed'),
                    'duplicado'   => $duplicado ? array('id' => $duplicado->id, 'name' => $duplicado->name, 'cf1' => $duplicado->cf1, 'cf2' => $duplicado->cf2) : NULL,
                ));
                die();
            }

            $this->data['error'] = $error ?: $this->session->flashdata('error');
            $this->data['duplicado'] = $duplicado ?: NULL;
            $this->_cargar_ubicaciones();
            $this->_cargar_actividades();
            $this->data['page_title'] = lang('add_customer');
            $bc = array(array('link' => site_url('customers'), 'page' => lang('customers')), array('link' => '#', 'page' => lang('add_customer')));
            $meta = array('page_title' => lang('add_customer'), 'bc' => $bc);
            $this->page_construct('customers/add', $this->data, $meta);

        }
    }

    function edit($id = NULL) {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', $this->lang->line('access_denied'));
            redirect('pos');
        }
        if($this->input->get('id')) { $id = $this->input->get('id', TRUE); }

        $this->form_validation->set_rules('name', $this->lang->line("name"), 'required');
        $this->form_validation->set_rules('email', $this->lang->line("email_address"), 'valid_email');

        $duplicado = NULL;

        if ($this->form_validation->run() == true) {
            $data      = $this->_datos_cliente();
            $duplicado = $data['cf2'] ? $this->customers_model->getCustomerByCedula($data['cf2'], $id) : FALSE;
        }

        if ( $this->form_validation->run() == true && !$duplicado && $this->customers_model->updateCustomer($id, $data)) {

            $this->_guardar_actividades($id);
            $this->session->set_flashdata('message', $this->lang->line("customer_updated"));
            redirect("customers");

        } else {

            $this->data['customer'] = $this->customers_model->getCustomerByID($id);
            if (!$this->data['customer']) {
                $this->session->set_flashdata('error', lang('customer_add_failed'));
                redirect('customers');
            }
            $this->data['error'] = validation_errors()
                ? validation_errors()
                : ($duplicado ? sprintf(lang('cliente_cedula_duplicada'), $duplicado->cf2, $duplicado->name) : $this->session->flashdata('error'));
            $this->data['duplicado'] = $duplicado ?: NULL;
            $this->_cargar_ubicaciones($this->data['customer']);
            $this->_cargar_actividades($this->data['customer']);
            $this->data['page_title'] = lang('edit_customer');
            $bc = array(array('link' => site_url('customers'), 'page' => lang('customers')), array('link' => '#', 'page' => lang('edit_customer')));
            $meta = array('page_title' => lang('edit_customer'), 'bc' => $bc);
            $this->page_construct('customers/edit', $this->data, $meta);

        }
    }

    /**
     * Arma la fila de customers desde el POST, ya normalizada.
     * cf2 vacio se guarda como NULL: la columna es UNIQUE y MySQL solo admite
     * NULL repetido, no cadena vacia.
     */
    private function _datos_cliente() {
        $cf1 = $this->input->post('cf1') ?: '01';
        $cf2 = trim((string) $this->input->post('cf2'));
        // 05 es "Extranjero No Domiciliado" y su documento puede traer letras;
        // las identificaciones de Costa Rica (01-04) son solo digitos.
        $cf2 = ($cf1 === '05')
            ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cf2))
            : preg_replace('/\D/', '', $cf2);

        $data = array(
            'name'                   => trim((string) $this->input->post('name')),
            'business_name'          => trim((string) $this->input->post('business_name')) ?: NULL,
            'email'                  => trim((string) $this->input->post('email')) ?: NULL,
            'phone'                  => trim((string) $this->input->post('phone')) ?: NULL,
            'cod_telefono'           => preg_replace('/\D/', '', (string) $this->input->post('cod_telefono')) ?: '506',
            'cf1'                    => $cf1,
            'cf2'                    => $cf2 !== '' ? $cf2 : NULL,
            'codigo_actividad'       => trim((string) $this->input->post('codigo_actividad')) ?: NULL,
            'codigo_provincia'       => trim((string) $this->input->post('codigo_provincia')) ?: NULL,
            'codigo_canton'          => trim((string) $this->input->post('codigo_canton')) ?: NULL,
            'codigo_distrito'        => trim((string) $this->input->post('codigo_distrito')) ?: NULL,
            'codigo_barrio'          => trim((string) $this->input->post('codigo_barrio')) ?: NULL,
            'otras_senas'            => trim((string) $this->input->post('otras_senas')) ?: NULL,
            'otras_senas_extranjero' => trim((string) $this->input->post('otras_senas_extranjero')) ?: NULL,
            'notas'                  => trim((string) $this->input->post('notas')) ?: NULL,
            'limitcredit'            => $this->input->post('limitcredit') ? $this->input->post('limitcredit') : 0,
            'dias_credito'           => (int) $this->input->post('dias_credito'),
            'tipo_doc_defecto'       => in_array($this->input->post('tipo_doc_defecto'), array('01', '04'), true)
                                        ? $this->input->post('tipo_doc_defecto') : NULL,
            'tipo_pago_defecto'      => in_array($this->input->post('tipo_pago_defecto'), array('contado', 'credito'), true)
                                        ? $this->input->post('tipo_pago_defecto') : NULL,
            'codigo_cliente'         => trim((string) $this->input->post('codigo_cliente')) ?: NULL,
            'whatsapp'               => preg_replace('/[^0-9+]/', '', (string) $this->input->post('whatsapp')) ?: NULL,
        );

        // La exoneracion es una resolucion vigente del cliente: se guarda con el
        // y se copia a la venta al facturar.
        if ($this->input->post('usa_exoneracion')) {
            // Los dos son codigos del anexo: fuera del catalogo Hacienda rechaza.
            $tipo = (string) $this->input->post('exo_tipo_documento');
            $inst = (string) $this->input->post('exo_institucion');
            $data['exo_tipo_documento'] = isset(tipos_exoneracion()[$tipo]) ? $tipo : NULL;
            $data['exo_institucion']    = isset(instituciones_exoneracion()[$inst]) ? $inst : NULL;
            $data['exo_numero']         = mb_substr(trim((string) $this->input->post('exo_numero')), 0, 40) ?: NULL;
            $data['exo_fecha_emision']  = $this->input->post('exo_fecha_emision') ?: NULL;
            $data['exo_fecha_vence']    = $this->input->post('exo_fecha_vence') ?: NULL;
            $data['exo_porcentaje']     = min(100, max(0, (float) $this->input->post('exo_porcentaje')));
        } else {
            $data['exo_tipo_documento'] = NULL;
            $data['exo_numero']         = NULL;
            $data['exo_institucion']    = NULL;
            $data['exo_fecha_emision']  = NULL;
            $data['exo_fecha_vence']    = NULL;
            $data['exo_porcentaje']     = 0;
        }

        return $data;
    }

    /**
     * Provincias siempre, y los niveles siguientes solo si el cliente ya tiene
     * ubicacion: el resto lo pide el formulario por AJAX conforme se elige.
     *
     * @param object|false $customer  Cliente en edicion, o nada al dar de alta
     */
    /**
     * Reemplaza las actividades economicas del cliente por las del formulario.
     *
     * El padron devuelve todas las inscritas y antes se guardaba solo la
     * elegida: al facturar con otra habia que volver a consultar.
     */
    private function _guardar_actividades($customer_id) {
        $customer_id = (int) $customer_id;
        if (!$customer_id) {
            return;
        }

        $codigos = (array) $this->input->post('actividades');
        $descrip = (array) $this->input->post('actividades_desc');

        $this->db->delete('customer_actividades', array('customer_id' => $customer_id));

        $vistos = array();
        foreach ($codigos as $i => $codigo) {
            $codigo = trim((string) $codigo);
            if ($codigo === '' || isset($vistos[$codigo])) {
                continue;
            }
            $vistos[$codigo] = true;
            $this->db->insert('customer_actividades', array(
                'customer_id' => $customer_id,
                'codigo'      => mb_substr($codigo, 0, 6),
                'descripcion' => isset($descrip[$i]) ? mb_substr(trim((string) $descrip[$i]), 0, 255) : NULL,
            ));
        }
    }

    /** Actividades guardadas del cliente, para dibujarlas en el formulario. */
    private function _cargar_actividades($customer = NULL) {
        $this->data['actividades'] = array();
        if (empty($customer) || empty($customer->id)) {
            return;
        }
        $this->data['actividades'] = $this->db
            ->select('codigo, descripcion')
            ->where('customer_id', (int) $customer->id)
            ->order_by('codigo', 'ASC')
            ->get('customer_actividades')->result();
    }

    private function _cargar_ubicaciones($customer = NULL) {
        $this->data['provincias'] = $this->db->select('codigo_provincia as codigo, nombre_provincia as nombre')
            ->order_by('nombre_provincia', 'ASC')->get('provincia_cr')->result();
        $this->data['cantones_actuales'] = array();
        $this->data['distritos_actuales'] = array();
        $this->data['barrios_actuales']  = array();

        if (empty($customer) || empty($customer->codigo_provincia)) {
            return;
        }
        $this->data['cantones_actuales'] = $this->db->select('codigo_canton as codigo, nombre_canton as nombre')
            ->where('codigo_provincia', $customer->codigo_provincia)
            ->order_by('nombre_canton', 'ASC')->get('canton_cr')->result();

        if (empty($customer->codigo_canton)) {
            return;
        }
        $this->data['distritos_actuales'] = $this->db->select('codigo_distrito as codigo, nombre_distrito as nombre')
            ->where('codigo_provincia', $customer->codigo_provincia)
            ->where('codigo_canton', $customer->codigo_canton)
            ->order_by('nombre_distrito', 'ASC')->get('distrito_cr')->result();

        if (empty($customer->codigo_distrito)) {
            return;
        }
        $this->data['barrios_actuales'] = $this->db->select('codigo_barrio as codigo, nombre_barrio as nombre')
            ->where('codigo_provincia', $customer->codigo_provincia)
            ->where('codigo_canton', $customer->codigo_canton)
            ->where('codigo_distrito', $customer->codigo_distrito)
            ->order_by('nombre_barrio', 'ASC')->get('barrio_cr')->result();
    }

    /**
     * AJAX: ¿ya existe un cliente con esta identificacion?
     * El formulario pregunta antes de dejar llenar el resto de los datos.
     */
    function buscar_cedula() {
        $cf2      = $this->input->post('cf2') ?: $this->input->get('cf2');
        $excluir  = (int) ($this->input->post('excluir') ?: $this->input->get('excluir'));
        $cliente  = $this->customers_model->getCustomerByCedula($cf2, $excluir ?: NULL);

        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(array(
            'existe'  => (bool) $cliente,
            'cliente' => $cliente ? array(
                'id'               => $cliente->id,
                'name'             => $cliente->name,
                'business_name'    => $cliente->business_name,
                'cf1'              => $cliente->cf1,
                'cf2'              => $cliente->cf2,
                'email'            => $cliente->email,
                'phone'            => $cliente->phone,
                'url'              => site_url('customers/edit/' . $cliente->id),
            ) : NULL,
        ), JSON_UNESCAPED_UNICODE));
    }

    // AJAX: ubicaciones en cascada para el bloque Receptor/Ubicacion del comprobante
    function get_cantones($codigo_provincia = '') {
        $this->_json_ubicacion($this->db->select('codigo_canton as codigo, nombre_canton as nombre')
            ->where('codigo_provincia', $codigo_provincia)
            ->order_by('nombre_canton', 'ASC')->get('canton_cr')->result());
    }

    function get_distritos($codigo_provincia = '', $codigo_canton = '') {
        $this->_json_ubicacion($this->db->select('codigo_distrito as codigo, nombre_distrito as nombre')
            ->where('codigo_provincia', $codigo_provincia)
            ->where('codigo_canton', $codigo_canton)
            ->order_by('nombre_distrito', 'ASC')->get('distrito_cr')->result());
    }

    function get_barrios($codigo_provincia = '', $codigo_canton = '', $codigo_distrito = '') {
        $this->_json_ubicacion($this->db->select('codigo_barrio as codigo, nombre_barrio as nombre')
            ->where('codigo_provincia', $codigo_provincia)
            ->where('codigo_canton', $codigo_canton)
            ->where('codigo_distrito', $codigo_distrito)
            ->order_by('nombre_barrio', 'ASC')->get('barrio_cr')->result());
    }

    private function _json_ubicacion($rows) {
        $this->output->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($rows, JSON_UNESCAPED_UNICODE));
    }

    function getcustomer($id = NULL) {
        if($this->input->get('id')) { $id = $this->input->get('id', TRUE); }
            echo $this->customers_model->getCustomerByID($id)->limitcredit;
    }

    function getdeuda($id=null){
		
		$this->db->select("sum(grand_total-paid) as balance")
		->where('status <>', 'paid')
            ->where('customer_id ', $id);	
		$q = $this->db->get("sales");
		
		if ($q->num_rows() > 0) {
            $row = $q->row();
        }
		
        echo $row->balance ? $row->balance : "0.0000";
    }

    function delete($id = NULL) {
        // El enlace tiene que venir de una pantalla de esta sesion.
        $this->exigir_token_accion();

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

        if ( $this->customers_model->deleteCustomer($id) )
        {
            $this->session->set_flashdata('message', lang("customer_deleted"));
            redirect("customers");
        }

    }




}
