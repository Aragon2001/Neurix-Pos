<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

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
        ->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('suppliers/edit/$1') . "' class='tip btn btn-warning btn-xs' title='".$this->lang->line("edit_supplier")."'><i class='fa fa-edit'></i></a> <a href='" . site_url('suppliers/delete/$1' . '?t=' . $this->token_accion()) . "' data-confirm=\"". $this->lang->line('alert_x_supplier') ."\" class='tip btn btn-danger btn-xs' title='".$this->lang->line("delete_supplier")."'><i class='fa fa-trash-o'></i></a></div></div>", "id")
        ->unset_column('id');

        echo $this->datatables->generate();

    }

    function add() {
        $this->form_validation->set_rules('txtNombre', lang("name"), 'required');
        $this->form_validation->set_rules('txtEmail', lang("email_address"), 'required|valid_email');

        $error = '';
        $duplicado = NULL;

        if ($this->form_validation->run() == true) {
            $data = $this->_datos_proveedor();
            $error = $this->_verificar_identificacion($data) ?: $this->_verificar_pago($data);
            $duplicado = $data['cf2'] ? $this->suppliers_model->getSupplierByCedula($data['cf2']) : FALSE;
            if ($duplicado) {
                $error = sprintf(lang('proveedor_cedula_duplicada'), $duplicado->cf2, $duplicado->name);
            }
        }

        if ($this->form_validation->run() == true && !$error && $cid = $this->suppliers_model->addSupplier($data)) {

            if($this->input->is_ajax_request()) {
                echo json_encode(array('status' => 'success', 'msg' => lang("supplier_added"), 'id' => $cid, 'val' => $data['name']));
                die();
            }
            $this->session->set_flashdata('message', lang("supplier_added"));
            if($this->input->post('formFC') != "FEC"){
                redirect("suppliers");
            } else {
                redirect("facturascompras/create_fec");
            }

        } else {
            $error = $error ?: validation_errors();

            if($this->input->post('formFC') == "FEC"){
                $this->session->set_flashdata('error', $error);
                redirect("facturascompras/create_fec");
            }
            if($this->input->is_ajax_request()) {
                echo json_encode(array('status' => 'failed', 'msg' => $error)); die();
            }

            $this->data['error'] = $error ?: $this->session->flashdata('error');
            $this->data['duplicado'] = $duplicado ?: NULL;
            $this->_cargar_ubicaciones();
            $this->data['page_title'] = lang('add_supplier');
            $bc = array(array('link' => site_url('suppliers'), 'page' => lang('suppliers')), array('link' => '#', 'page' => lang('add_supplier')));
            $meta = array('page_title' => lang('add_supplier'), 'bc' => $bc);
            $this->page_construct('suppliers/add', $this->data, $meta);
        }
    }

    function edit($id = NULL) {
        if($this->input->get('id')) { $id = $this->input->get('id', TRUE); }

        $this->form_validation->set_rules('txtNombre', lang("name"), 'required');
        $this->form_validation->set_rules('txtEmail', lang("email_address"), 'required|valid_email');

        $error = '';
        $duplicado = NULL;

        if ($this->form_validation->run() == true) {
            $data = $this->_datos_proveedor();
            $error = $this->_verificar_identificacion($data) ?: $this->_verificar_pago($data);
            $duplicado = $data['cf2'] ? $this->suppliers_model->getSupplierByCedula($data['cf2'], $id) : FALSE;
            if ($duplicado) {
                $error = sprintf(lang('proveedor_cedula_duplicada'), $duplicado->cf2, $duplicado->name);
            }
        }

        if ($this->form_validation->run() == true && !$error && $this->suppliers_model->updateSupplier($id, $data)) {

            $this->session->set_flashdata('message', lang("supplier_updated"));
            redirect("suppliers");

        } else {

            $this->data['supplier'] = $this->suppliers_model->getSupplierByID($id);
            if (!$this->data['supplier']) {
                $this->session->set_flashdata('error', lang('supplier_add_failed'));
                redirect('suppliers');
            }
            $this->data['error'] = $error ?: (validation_errors() ?: $this->session->flashdata('error'));
            $this->data['duplicado'] = $duplicado ?: NULL;
            $this->_cargar_ubicaciones($this->data['supplier']);
            $this->data['page_title'] = lang('edit_supplier');
            $bc = array(array('link' => site_url('suppliers'), 'page' => lang('suppliers')), array('link' => '#', 'page' => lang('edit_supplier')));
            $meta = array('page_title' => lang('edit_supplier'), 'bc' => $bc);
            $this->page_construct('suppliers/edit', $this->data, $meta);

        }
    }

    /**
     * Arma la fila de suppliers desde el POST, ya normalizada.
     *
     * Los nombres de campo antiguos se conservan porque el modal de la factura
     * electronica de compra envia a este mismo metodo.
     */
    private function _datos_proveedor() {
        $cf1 = $this->input->post('tcedula') ?: '02';
        $cf2 = normalizar_identificacion($cf1, (string) $this->input->post('txtIdentificacion'));
        $senas = trim((string) $this->input->post('txtOtraSe'));
        // Guardar el dato bancario del medio que no se eligio dejaria un IBAN
        // viejo en un proveedor que ahora cobra en efectivo.
        $medio = trim((string) $this->input->post('medio_pago_habitual'));
        $exige = medio_pago_exige($medio);

        $data = array(
            'name'                => trim((string) $this->input->post('txtNombre')),
            'company'             => trim((string) $this->input->post('company')) ?: NULL,
            'email'               => trim((string) $this->input->post('txtEmail')) ?: NULL,
            'phone'               => trim((string) $this->input->post('txtTel')) ?: NULL,
            'codigo_pais_tel'     => preg_replace('/\D/', '', (string) $this->input->post('codigo_pais_tel')) ?: '506',
            'cf1'                 => $cf1,
            'cf2'                 => $cf2 !== '' ? $cf2 : NULL,
            'actividad_economica' => trim((string) $this->input->post('txtCodActEco')),
            'codigo_provincia'    => trim((string) $this->input->post('codigo_provincia')),
            'codigo_canton'       => trim((string) $this->input->post('codigo_canton')),
            'codigo_distrito'     => trim((string) $this->input->post('codigo_distrito')),
            'codigo_barrio'       => trim((string) $this->input->post('codigo_barrio')),
            // `direccion` se mantiene al dia porque la factura de compra vieja
            // todavia la lee; la fuente es `otras_senas`, que admite 250.
            'otras_senas'         => mb_substr($senas, 0, 250) ?: NULL,
            'direccion'           => mb_substr($senas, 0, 100),
            'contacto_nombre'     => trim((string) $this->input->post('contacto_nombre')) ?: NULL,
            'contacto_telefono'   => trim((string) $this->input->post('contacto_telefono')) ?: NULL,
            'plazo_pago_dias'     => (int) $this->input->post('plazo_pago_dias'),
            'medio_pago_habitual' => $medio ?: NULL,
            'medio_pago_detalle'  => in_array($exige, array('plataforma', 'otros'), true) ? (mb_substr(trim((string) $this->input->post('medio_pago_detalle')), 0, 100) ?: NULL) : NULL,
            'cuenta_iban'         => $exige === 'iban'  ? (strtoupper(preg_replace('/\s+/', '', (string) $this->input->post('cuenta_iban'))) ?: NULL) : NULL,
            'sinpe_telefono'      => $exige === 'sinpe' ? (preg_replace('/\D/', '', (string) $this->input->post('sinpe_telefono')) ?: NULL) : NULL,
            'moneda'              => in_array($this->input->post('moneda'), array('CRC', 'USD', 'EUR'), true) ? $this->input->post('moneda') : 'CRC',
            'notas'               => trim((string) $this->input->post('notas')) ?: NULL,
            'activo'              => $this->input->post('activo') === '0' ? 0 : 1,
            'updated_at'          => date('Y-m-d H:i:s'),
        );

        return $data;
    }

    /**
     * Reglas que Hacienda comprueba despues de emitir, comprobadas antes.
     *
     * @return string cadena vacia si todo esta bien, o el mensaje al usuario
     */
    private function _verificar_identificacion(array $data) {
        $r = identificacion_valida($data['cf1'], (string) $data['cf2']);
        if (!$r['ok']) {
            return lang($r['error']);
        }

        // El proveedor es el emisor de la factura electronica de compra y ahi su
        // codigo de actividad es obligatorio, salvo que no este inscrito.
        if (!in_array($data['cf1'], array('05', '06'), true) && $data['actividad_economica'] === '') {
            return lang('proveedor_falta_actividad');
        }

        // <Ubicacion> va completa o no va: si aparece, el XSD exige provincia,
        // canton, distrito y otras senias de 5 caracteres para arriba.
        if (!in_array($data['cf1'], array('05', '06'), true)) {
            $incompleta = $data['codigo_provincia'] === '' || $data['codigo_canton'] === ''
                       || $data['codigo_distrito'] === '' || mb_strlen((string) $data['otras_senas']) < 5;
            if ($incompleta) {
                return lang('proveedor_falta_ubicacion');
            }
        }

        if ($data['cuenta_iban'] && !preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $data['cuenta_iban'])) {
            return lang('cuenta_iban_invalida');
        }

        return '';
    }

    /**
     * El medio de pago habitual y el dato que ese medio arrastra.
     *
     * @return string cadena vacia si todo esta bien, o el mensaje al usuario
     */
    private function _verificar_pago(array $data) {
        if (empty($data['medio_pago_habitual'])) {
            return lang('medio_pago_falta');
        }

        switch (medio_pago_exige($data['medio_pago_habitual'])) {
            case 'iban':
                if (!$data['cuenta_iban']) { return lang('medio_pago_falta_iban'); }
                break;
            case 'sinpe':
                if (!$data['sinpe_telefono']) { return lang('medio_pago_falta_sinpe'); }
                if (strlen($data['sinpe_telefono']) !== 8) { return lang('sinpe_telefono_invalido'); }
                break;
            case 'plataforma':
                if (!$data['medio_pago_detalle']) { return lang('medio_pago_falta_plataforma'); }
                break;
            case 'otros':
                if (!$data['medio_pago_detalle']) { return lang('medio_pago_falta_otros'); }
                break;
        }

        return '';
    }

    /**
     * Provincias siempre; los niveles siguientes solo si el proveedor ya tiene
     * ubicacion. El resto lo pide el formulario por AJAX conforme se elige.
     *
     * @param object|null $supplier proveedor en edicion, o nada al dar de alta
     */
    private function _cargar_ubicaciones($supplier = NULL) {
        $this->data['provincias'] = $this->db->select('codigo_provincia as codigo, nombre_provincia as nombre')
            ->order_by('nombre_provincia', 'ASC')->get('provincia_cr')->result();
        $this->data['cantones_actuales'] = array();
        $this->data['distritos_actuales'] = array();
        $this->data['barrios_actuales'] = array();

        if (empty($supplier) || empty($supplier->codigo_provincia)) {
            return;
        }
        $this->data['cantones_actuales'] = $this->db->select('codigo_canton as codigo, nombre_canton as nombre')
            ->where('codigo_provincia', $supplier->codigo_provincia)
            ->order_by('nombre_canton', 'ASC')->get('canton_cr')->result();

        if (empty($supplier->codigo_canton)) {
            return;
        }
        $this->data['distritos_actuales'] = $this->db->select('codigo_distrito as codigo, nombre_distrito as nombre')
            ->where('codigo_provincia', $supplier->codigo_provincia)
            ->where('codigo_canton', $supplier->codigo_canton)
            ->order_by('nombre_distrito', 'ASC')->get('distrito_cr')->result();

        if (empty($supplier->codigo_distrito)) {
            return;
        }
        $this->data['barrios_actuales'] = $this->db->select('codigo_barrio as codigo, nombre_barrio as nombre')
            ->where('codigo_provincia', $supplier->codigo_provincia)
            ->where('codigo_canton', $supplier->codigo_canton)
            ->where('codigo_distrito', $supplier->codigo_distrito)
            ->order_by('nombre_barrio', 'ASC')->get('barrio_cr')->result();
    }

    /**
     * AJAX: ¿ya existe un proveedor con esta identificacion?
     * Diez proveedores con la misma cedula dejaban que la factura de compra
     * eligiera uno al azar.
     */
    function buscar_cedula() {
        $cf2     = $this->input->post('cf2') ?: $this->input->get('cf2');
        $excluir = (int) ($this->input->post('excluir') ?: $this->input->get('excluir'));
        $prov    = $this->suppliers_model->getSupplierByCedula($cf2, $excluir ?: NULL);

        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(array(
            'existe'    => (bool) $prov,
            'proveedor' => $prov ? array(
                'id'   => $prov->id,
                'name' => $prov->name,
                'cf1'  => $prov->cf1,
                'cf2'  => $prov->cf2,
                'url'  => site_url('suppliers/edit/' . $prov->id),
            ) : NULL,
        ), JSON_UNESCAPED_UNICODE));
    }

    function delete($id = NULL) {
        // El enlace tiene que venir de una pantalla de esta sesion.
        $this->exigir_token_accion();

        if(DEMO) {
            $this->session->set_flashdata('error', lang("disabled_in_demo"));
            redirect('pos');
        }

        if($this->input->get('id')) { $id = $this->input->get('id', TRUE); }

        if ( $this->suppliers_model->deleteSupplier($id) )
        {
            $this->session->set_flashdata('message', lang("supplier_deleted"));
            redirect("suppliers");
        }

    }

}
