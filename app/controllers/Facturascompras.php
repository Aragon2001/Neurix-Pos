<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class FacturasCompras extends MY_Controller {

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
        $this->load->model('FEC_model');
        $this->load->model('hacienda_model');
        $this->load->helper('text');
        $this->digital_file_types = 'zip|pdf|doc|docx|xls|xlsx|jpg|png|gif';
    }

    function index() {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('fec');
        $bc = array(array('link' => '#', 'page' => lang('fec')));
        $meta= array('page_title' => lang('fec'), 'bc' => $bc);
        $this->page_construct('facturascompras/index', $this->data, $meta);
    }

    function get_fec() {
        $this->load->library('datatables');
        $this->datatables->select("fec.id as id, DATE_FORMAT(date, '%Y-%m-%d %H:%i') as date, customer_name, total, total_tax, total_discount, grand_total, paid, ht.estatus_hacienda, ht.consecutivo, status");
        $this->datatables->from('fec');
        $this->datatables->join('hacienda_fec ht', 'ht.sale_id = fec.id', 'left');
        $this->db->order_by("date","desc");

        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column('status_hacienda', "<div class='text-center'><div class='btn-group'>
            <a target='_blank' href='" . site_url('xmlhacienda/xmlFirmadoFec/$1') . "' title='Ver XML Firmado' class='tip btn btn-info btn-xs' ><i class='fa fa-list'></i></a>
            <a target='_blank' href='" . site_url('xmlhacienda/xmlMensajeFec/$1') . "' title='Ver XML de Respuesta' class='tip btn btn-warning btn-xs' ><i class='fa fa-list'></i></a> 
            </div></div>", "id");
        // $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('pos/view/$1/1') . "' title='".lang("view_invoice")."' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-list'></i></a> <a href='".site_url('sales/payments/$1')."' title='" . lang("view_payments") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-money'></i></a> <a href='".site_url('sales/add_payment/$1')."' title='" . lang("add_payment") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-briefcase'></i></a> <a href='" . site_url('pos/?edit=$1') . "' title='".lang("edit_invoice")."' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('sales/delete/$1') . "' onClick=\"return confirm('". lang('alert_x_sale') ."')\" title='".lang("delete_sale")."' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", "id");
        
         $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a  href='" . site_url('facturascompras/view_fec/$1') . "' title='" . lang("Ver") . "' class='tip btn btn-primary btn-xs'><i class='fa fa-search'></i></div></div>", "id");
        // $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    function get_suppliers() {
        $q = $this->db->get('suppliers');
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;

    }

    function get_provincia() {
        $q = $this->db->get('provincia_cr');
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;

    }

    function get_canton($id=null) {
        $id = $this->input->get('id');
        $q = $this->db->get_where('canton_cr', array('codigo_provincia' => $id));
        echo '<option value="" selected>-- Please Select --</option>';
        foreach($q->result() as $row){
            echo '<option value="'.$row->codigo_canton.'">'.$row->nombre_canton.'</option>';
        }

    }

    function get_distrito($id=null) {
        $id = $this->input->get('id');
        $q = $this->db->get_where('distrito_cr', array('codigo_canton' => $id));
        echo '<option value="" selected>-- Please Select --</option>';
        foreach($q->result() as $row){
            echo '<option value="'. $row->codigo_distrito.'">'.$row->nombre_distrito.'</option>';
        }

    }

    function get_barrio($id=null) {
        $id = $this->input->get('id');
        $q = $this->db->get_where('barrio_cr', array('codigo_distrito' => $id));
        echo '<option value="" selected>-- Please Select --</option>';
        foreach($q->result() as $row){
            echo '<option value="'.$row->codigo_barrio.'">'.$row->nombre_barrio.'</option>';
        }

    }

    function get_impuesto(){
        $q = $this->db->get('impuestos');
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    function create_fec(){
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('fec');
        $this->data['suppliers'] = $this->get_suppliers();
        $this->data['provincia'] = $this->get_provincia();
        $this->data['impuesto'] = $this->get_impuesto();
        $bc = array(array('link' => '#', 'page' => lang('fec')));
        $meta= array('page_title' => lang('fec'), 'bc' => $bc);
        $this->page_construct('facturascompras/add', $this->data, $meta);  
    }

    function save_fec(){
        $MontoLinea = $_GET["linea"];
        $MontoLinea= utf8_encode($MontoLinea); 
        $objLinea = json_decode($MontoLinea,true); 
        $totales = $_GET["totales"];
        $objTot = json_decode($totales,true);
        $obj = $_GET["myData"];
        $obj = utf8_encode($obj); 
        $results = json_decode($obj,true);
        $articulos = array();
        $impuestos = array();
        $exoneraciones= array();
        if(!empty($objTot["id_proveedor"])){
            $provedor = $this->db->get_where('tec_suppliers', array('id' => $objTot["id_proveedor"]), 1);
            if($provedor->row()->codigo_provincia ==''){
                echo json_encode(array('error', "Debe completar la información del proveedor <a href='".base_url()."/suppliers/edit/".$provedor->row()->id."'>".$provedor->row()->name."</a><br/>"));
                exit();
            }else if($provedor->row()->email == ''){
                echo json_encode(array('error', "Debe completar la información del proveedor <a href='".base_url()."/suppliers/edit/".$provedor->row()->id."'>".$provedor->row()->name."</a><br/>"));
                exit();
            }
        }else{
            echo json_encode(array('error', 'Seleccione un proveedor con regimen simplificado <br/>'));
            exit();
        }
        if(sizeof($results) <= 0){
            echo json_encode(array('error', 'No se encuentra ningun articulo agragado <br/>'));
            exit();
        }
        foreach($results as $res){
            array_push($impuestos,$res['tax_rate']); 
        }
        
        foreach($results as $res){
            array_push($articulos,$res['row']); 
        }
        array_push($exoneraciones,$objTot['exoneracion']); 
        $fecha = date('Y-m-d');
        if ($objTot['payment_status'] == 'partial' || $objTot['payment_status'] == 'paid') {
            $due_date = null;
            $credito = false;
        } else {
            $due_date =$objTot['paymentmethod']  ? date('Y-m-d', strtotime('+' . $objTot['paymentmethod'] . ' days', strtotime($fecha))) : null;
            $credito = true;
        }
        if ($due_date == null and $credito == true) {
            echo json_encode(array('error', 'Seleccione el tiempo de credito en el formulario de pago<br/>'));
            exit();
        }
        if($articulos==null || $articulos==''){

        }
        if ($objTot["payment_status"] == "due") {
            $status = "Unpaid";
        } else if ($objTot["payment_status"] == "partial") {
            $status = "partially";
        } else if ($objTot["payment_status"] == "paid") {
            $status = "paid";
        }

        $totalItems = 0;
        $TipoDocumentoE = trim($exoneraciones[0]['ExoTipoDocumento'])?trim($exoneraciones[0]['ExoTipoDocumento']):'';
        $NombreInstitucionE = trim($exoneraciones[0]['ExoNombreInstitucion'])?trim($exoneraciones[0]['ExoNombreInstitucion']):'';
        $NumeroDocumentoE = trim($exoneraciones[0]['ExoNumeroDocumento'])?trim($exoneraciones[0]['ExoNumeroDocumento']):'';
        $ExoFechaEmision = trim($exoneraciones[0]['ExoFechaEmision'])?trim($exoneraciones[0]['ExoFechaEmision']):'';
        $date = date_create($ExoFechaEmision);
        $ExoFechaEmision = date_format($date, 'Y-m-d H:i:s');
        $PorcentajeExoneracion=trim($exoneraciones[0]['ExoPorcentajeExoneracion'])?trim($exoneraciones[0]['ExoPorcentajeExoneracion']):0;
        $MontoExoneracion = trim($objTot['TotalMercExonerada'])?trim($objTot['TotalMercExonerada']):0;
        foreach($articulos as $art){
            $totalItems += $art['quantity'];
        }
        $receptor = $provedor->row();
            $receptor->pre_id_number = $receptor->cf1;
            $receptor->id_number_proveedor = $receptor->cf2;
            $identificacion = str_replace('-', '', trim($receptor->id_number_proveedor));
            $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));
            $tipo_receptor = $receptor->pre_id_number;
            if (strlen($identificacion) < 12) {
                $dif = 12 - strlen($identificacion);
                $ceros = '';
                for ($ce = 1; $ce <= $dif; $ce++) {
                    $ceros .= '0';
                }
                $identificacion = $ceros . $identificacion;
            }
            $identificacion = substr($identificacion, 0, 12);
            $tipodoc = '08';
        
            $fecha_actual=date('Y-m-d H:i:s');
            $data =  array(
            'date' => $fecha_actual,
            'customer_id' => $provedor->row()->id,
            'token_post' => $objTot['token_post'],
            'customer_name' => $provedor->row()->name,
            'total' => $this->tec->formatDecimal($objTot['TotalVenta']),
            'product_discount' => $this->tec->formatDecimal($objTot['TotalDescuentos']),
            'order_discount_id' =>'' ,
            'order_discount' => '',
            'total_discount' => $this->tec->formatDecimal($objTot['TotalDescuentos']),
            'product_tax' => $this->tec->formatDecimal($objTot['TotalImpuesto']),
            'order_tax_id' => '',
            'order_tax' =>'' ,
            'total_tax' =>$this->tec->formatDecimal($objTot['TotalImpuesto']),
            'grand_total' =>  $this->tec->formatDecimal($objTot['TotalComprobante']),
            'total_items' => $totalItems,
            'total_quantity' =>$totalItems,
            'rounding' => '',
            'paid' =>$this->tec->formatDecimal($objTot['TotalComprobante']) ,
            'status' => $status,
            'created_by' => $this->session->userdata('user_id'),
            'note' =>'' ,
            'hold_ref' => '',
            'id_actividad' =>$provedor->row()->actividad_economica ,
            'TipoDocumentoE' =>$TipoDocumentoE ,
            'NombreInstitucionE' =>$NombreInstitucionE ,
            'NumeroDocumentoE' =>$NumeroDocumentoE ,
            'FechaEmisionE' =>str_replace(" ", "T", strval($ExoFechaEmision)),
            'PorcentajeExoneracion' =>$PorcentajeExoneracion ,
            'MontoExoneracion' =>$MontoExoneracion ,
            'tipo_doc' => $tipodoc,
            'store_id' => $this->session->userdata('store_id')
        );
        $payment = array(
            'date' => $fecha_actual,
            'amount' => $this->tec->formatDecimal($objTot['TotalComprobante']) ,
            'customer_id' => $provedor->row()->id,
            'paid_by' => $objTot['paid_by_1'],
            'cheque_no' => '',
            'cc_no' => '',
            'gc_no' =>'',
            'cc_holder' => '',
            'cc_month' => '',
            'cc_year' => '',
            'cc_type' => '',
            'cc_cvv2' => '',
            'created_by' => $this->session->userdata('user_id'),
            'store_id' => $this->session->userdata('store_id'),
            'note' => '',
            'pos_paid' => $this->tec->formatDecimal($objTot['TotalComprobante']),
            'pos_balance' => ''
        );
        $i =  sizeof($articulos);
        for ($r = 0; $r < $i; $r++) {
            if(($impuestos[$r]?$impuestos[$r]['tasa_impuesto']:0) > 0){
                $item_tax = $this->tec->formatDecimal(((($articulos[$r]['unit_price']) *$impuestos[$r]['tasa_impuesto']) / (100 + $impuestos[$r]['tasa_impuesto'])), 4);
            }else{
                $item_tax = 0;
            }
            $pr_item_tax = $this->tec->formatDecimal(( $item_tax * $articulos[$r]['quantity']), 4);
            $subtotal = $this->tec->formatDecimal((($articulos[$r]['real_unit_price'] * $articulos[$r]['quantity']) + $pr_item_tax), 4);
            $products[] = array(
                'type' => $articulos[$r]['type'],
                'unit_of_measurement' => $articulos[$r]['unit'],
                'product_id' => $articulos[$r]['id'],
                'quantity' => $articulos[$r]['quantity'],
                'unit_price' => $articulos[$r]['unit_price'],
                'net_unit_price' => $objLinea[$r]['PrecioUnitario'],
                'discount' => $objLinea[$r]['MontoDescuento'],
                'comment' => '',
                'item_discount' => $objLinea[$r]['MontoDescuento'],
                'tax' => $impuestos[$r]?$impuestos[$r]['tasa_impuesto']:0,
                'item_tax' => $pr_item_tax,
                'subtotal' => $objLinea[$r]['SubTotal'],
                'real_unit_price' => $articulos[$r]['real_unit_price'],
                'cost' => 0,
                'product_code' => $articulos[$r]['code'],
                'product_name' => $articulos[$r]['name'],
                'quantity_edit' => '',
                'qty_fracc_edit' => '',
                'esta_fraccionado' => '0',
                'id_tax' => $impuestos[$r]?$impuestos[$r]['id_impuesto']:0
            );
        }
        $this->load->library('Crearxml', NULL, 'Crearxml');
        $this->load->library('firmar', NULL, 'firmar');
        $did = null;
        $payment2 = array();
        $payment3 = array();
        $payment4 = array();
        $otrostextos =null;
        
        if ($sale = $this->FEC_model->addFEC($data, $products, $payment, $did, $payment2, $payment3, $payment4, $otrostextos)) {
            $data['paymentmethod']= $objTot['paymentmethod'] ;
            $facturadigital = $this->Crearxml->getFEC($data, $products, $payment, $otrostextos);
            $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
            if (file_exists($certificado)) {
                try {
                    $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $facturadigital["xml"]);
                } catch (Exception $e) {
                    $firmado = false;
                }
            } else {
                $firmado = false;
            }

            if ($firmado) {
                $facturadigital['xml_sign'] = $firmado;
            }

            $facturadigital['sale_id'] = $sale['sale_id'];

            // $this->session->unset_userdata("last_sale_id");
            // $this->session->set_userdata("last_sale_id", $sale['sale_id']);
            // $this->session->userdata();

            //$facturadigital=array("xml"=>"","clave"=>"","consecutivo"=>"","fecha_emision"=>"","tipo_doc"=>"");
            //print_r($facturadigital);               
            $this->hacienda_model->insertxmlfec($facturadigital);
            // $this->session->set_userdata('rmspos', 1);
            $msg = lang("sale_added");


            // $this->session->set_flashdata('message', $msg);
            header('Content-type: application/json');
            echo json_encode(array('success', $msg,$sale['sale_id']));
            // $redirect_to = $this->Settings->after_sale_page ? "pos" : "FacturasCompras/view/" . $sale['sale_id'];

            
        } else {
            header('Content-type: application/json');
            echo json_encode(array('error', lang("action_failed")));
            // $this->session->set_flashdata('error', lang("action_failed"));
        }
    }

    function view_fec($sale_id = NULL, $noprint = NULL){
        if ($noprint != NULL) {
            $noprint = NULL;
        }
        if ($this->input->get('id')) {
            $sale_id = $this->input->get('id');
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv = $this->FEC_model->getFecByID($sale_id);
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        } elseif ($this->session->userdata('store_id') != $inv->store_id) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('welcome');
        }
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows'] = $this->FEC_model->getAllFecItems($sale_id);
        $customer =$this->FEC_model->getSuppliersByID($inv->customer_id);
        $hacienda = $this->hacienda_model->getFEC($sale_id);
        $this->data['customer'] = $customer;
        $d = array(
            'nombre_distrito'=> $this->FEC_model->getNombreDistrito($customer->codigo_distrito),
            'nombre_canton'=> $this->FEC_model->getNombreCanton($customer->codigo_canton),
            'nombre_provincia'=> $this->FEC_model->getNombreProvincia($customer->codigo_provincia),
            'direccion'=>$customer->direccion
        );
        $totales = array();
        $TotalServGravados = 0.0;
        $TotalServExentos= 0.0;
        $TotalServExonerado= 0.0;
        $TotalMercanciasGravadas= 0.0;
        $TotalMercanciasExentas= 0.0;
        $TotalMercExonerada= 0.0;
        $TotalGravado= 0.0;
        $TotalExento= 0.0;
        $TotalExonerado= 0.0;
        $TotalVenta= 0.0;
        $TotalDescuentos= 0.0;
        $TotalVentaNeta= 0.0;
        $TotalImpuesto= 0.0;
        $TotalComprobante = 0.0;
        $DOM = new \DOMDocument('1.0', 'utf-8');
        // $DOM->validateOnParse = true;
        libxml_use_internal_errors(true);
        $sxe = simplexml_load_string($hacienda->xml_sign);
        if($sxe){
            $DOM->loadXML($hacienda->xml_sign);
            $TotalServGravados = $DOM->getElementsByTagName('TotalServGravados')->item(0)?$DOM->getElementsByTagName('TotalServGravados')->item(0)->nodeValue:'0';
            $TotalServExentos = $DOM->getElementsByTagName('TotalServExentos')->item(0)?$DOM->getElementsByTagName('TotalServExentos')->item(0)->nodeValue:'0';
            $TotalServExonerado =$DOM->getElementsByTagName('TotalServExonerado')->item(0)?$DOM->getElementsByTagName('TotalServExonerado')->item(0)->nodeValue:'0';
            $TotalMercanciasGravadas = $DOM->getElementsByTagName('TotalMercanciasGravadas')->item(0)? $DOM->getElementsByTagName('TotalMercanciasGravadas')->item(0)->nodeValue:'0';
            $TotalMercanciasExentas = $DOM->getElementsByTagName('TotalMercanciasExentas')->item(0)?$DOM->getElementsByTagName('TotalMercanciasExentas')->item(0)->nodeValue:'0';
            $TotalMercExonerada = $DOM->getElementsByTagName('TotalMercExonerada')->item(0)?$DOM->getElementsByTagName('TotalMercExonerada')->item(0)->nodeValue:'0';
            $TotalGravado = $DOM->getElementsByTagName('TotalGravado')->item(0)?$DOM->getElementsByTagName('TotalGravado')->item(0)->nodeValue:'0';
            $TotalExento = $DOM->getElementsByTagName('TotalExento')->item(0)?$DOM->getElementsByTagName('TotalExento')->item(0)->nodeValue:'0';
            $TotalExonerado = $DOM->getElementsByTagName('TotalExonerado')->item(0)?$DOM->getElementsByTagName('TotalExonerado')->item(0)->nodeValue:'0';
            $TotalVenta = $DOM->getElementsByTagName('TotalVenta')->item(0)?$DOM->getElementsByTagName('TotalVenta')->item(0)->nodeValue:'0';
            $TotalDescuentos = $DOM->getElementsByTagName('TotalDescuentos')->item(0)?$DOM->getElementsByTagName('TotalDescuentos')->item(0)->nodeValue:'0';
            $TotalVentaNeta = $DOM->getElementsByTagName('TotalVentaNeta')->item(0)?$DOM->getElementsByTagName('TotalVentaNeta')->item(0)->nodeValue:'0';
            $TotalImpuesto = $DOM->getElementsByTagName('TotalImpuesto')->item(0)?$DOM->getElementsByTagName('TotalImpuesto')->item(0)->nodeValue:'0';
            $TotalComprobante = $DOM->getElementsByTagName('TotalComprobante')->item(0)?$DOM->getElementsByTagName('TotalComprobante')->item(0)->nodeValue:'0';
        }else{
            $error = libxml_get_errors()[0];
            $count = 0;
            $msj = '';
            foreach($error as $e){
                if($count==3){
                    $msj = $e;
                }
                $count ++;  
            }
            $this->session->set_flashdata('error', $msj);
        }
            $totales = array(
                'TotalServGravados'=> $TotalServGravados,
                'TotalServExentos'=> $TotalServExentos,
                'TotalMercanciasGravadas'=> $TotalMercanciasGravadas,
                'TotalMercanciasExentas'=> $TotalMercanciasExentas,
                'TotalMercExonerada'=> $TotalMercExonerada,
                'TotalServExonerado'=>$TotalServExonerado,
                'TotalGravado'=> $TotalGravado,
                'TotalExento'=> $TotalExento,
                'TotalExonerado'=> $TotalExonerado,
                'TotalVenta'=> $TotalVenta,
                'TotalDescuentos'=> $TotalDescuentos,
                'TotalVentaNeta'=> $TotalVentaNeta,
                'TotalImpuesto'=> $TotalImpuesto,
                'TotalComprobante'=> $TotalComprobante
            );
            $this->data['local']= $d;
            $this->data['store'] = $this->site->getStoreByID($inv->store_id);
            $this->data['inv'] = $inv;
            $this->data['sid'] = $sale_id;
            $this->data['noprint'] = $noprint;
            $this->data['modal'] = $noprint ? true : false;
            $this->data['payments'] = $this->FEC_model->getAllFecPayments($sale_id);
            $this->data['created_by'] = $this->site->getUser($inv->created_by);
            $this->data['printer'] = $this->site->getPrinterByID($this->session->userdata('printer_default'));
            //$this->data['store'] = $this->site->getStoreByID($inv->store_id);
            $this->data['totales'] =$totales;
            $this->data['page_title'] = lang("invoice");
            $this->data['hacienda'] = $hacienda;
            $this->data['invoicebarcode'] = isset($this->data['hacienda']->consecutivo) ? $this->invice_barcode($this->data['hacienda']->consecutivo, 'code128', 60) : null;
            // $this->load->view($this->theme . 'facturascompras/' . ($this->Settings->print_img ? 'view' : 'view'), $this->data);
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('fec');
            $bc = array(array('link' => '#', 'page' => lang('fec')));
            $meta= array('page_title' => lang('fec'), 'bc' => $bc);
            $this->page_construct('facturascompras/view', $this->data, $meta);
        
    }

    function invice_barcode($id_invoice = NULL, $bcs = 'code128', $height = 60) {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode($id_invoice, $bcs, $height);
    }   

    function getActividad(){
        $ide = $_GET["ide"];
        $opciones = array(
            'http'=>array(
              'method'=>"GET",
              'header'=>"Accept-language: en\r\n" .
                        "Cookie: foo=bar\r\n"
            )
          );
          
          $contexto = stream_context_create($opciones);
        $json = file_get_contents('https://api.hacienda.go.cr/fe/ae?identificacion='.$ide, false, $contexto);
        echo($json);
    }

}