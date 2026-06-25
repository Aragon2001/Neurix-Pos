<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cargadocumentos extends MY_Controller {

    public function __construct() {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        $this->load->helper('pos');
        $this->load->model('pos_model');
        $this->load->library('form_validation');
    }

    public function index() {
        if ($_FILES) {
            $filesCount = count($_FILES);

            for ($i = 0; $i < $filesCount; $i++) {
                $_FILES['fil']['name'] = $_FILES['file_' . $i]['name'];
                $_FILES['fil']['type'] = $_FILES['file_' . $i]['type'];
                $_FILES['fil']['tmp_name'] = $_FILES['file_' . $i]['tmp_name'];
                $_FILES['fil']['error'] = $_FILES['file_' . $i]['error'];
                $_FILES['fil']['size'] = $_FILES['file_' . $i]['size'];


                // File upload configuration
                $uploadPath = 'uploads/';
                $config['upload_path'] = $uploadPath;
                $config['allowed_types'] = 'xml';

                // Load and initialize upload library
                $this->load->library('upload', $config);
                $this->upload->initialize($config);

                // Upload file to server
                if ($this->upload->do_upload('fil')) {
                    // Uploaded file data
                    $fileData = $this->upload->data();
                    $uploadData[$i]['file_name'] = $fileData['file_name'];
                    $uploadData[$i]['uploaded_on'] = date("Y-m-d H:i:s");
                }

                $path = $_FILES['fil']['name'];
                $ext = pathinfo($path, PATHINFO_EXTENSION);

                if(strtolower($ext) != "xml"){
                    echo '<li style="word-wrap: break-word; color:red;">El documento (' . $_FILES['fil']['name'] . ') que intenta de Cargar no es un XML por favor revise la extension del archivo antes de cargar.</li>';
                    continue;
                }
                
                $xml = mb_convert_encoding(@file_get_contents($config['upload_path'] . $this->upload->file_name), 'HTML-ENTITIES', "UTF-8");

                $array = json_decode(json_encode((array) simplexml_load_file($config['upload_path'] . $this->upload->file_name)), true);
                if (strpos($xml, "FacturaElectronica")) {
                    $tdoc = "Factura Electronica";
                } elseif (strpos($xml, "NotaCreditoElectronica")) {
                    $tdoc = "Nota de Credito Electronica";
                } elseif (strpos($xml, "NotaDebitoElectronica")) {
                    $tdoc = "Nota de Debito Electronica";
                } elseif (strpos($xml, "TiqueteElectronico")) {
                    $tdoc = "Tiquete Electronico";
                } elseif (strpos($xml, "MensajeHacienda")) {
                    $tdoc = "MensajeHacienda";
                } else {
                    $tdoc = false;
                }

                if (!$tdoc) {
                    echo '<li style="word-wrap: break-word; color:red;">El documento (' . $_FILES['fil']['name'] . ') que intenta de Cargar no es un documento electronico o se encuentra mal formateado, por favor revise.</li>';
                    continue;
                }
                
                if ($tdoc=="MensajeHacienda") {
                    echo '<li style="word-wrap: break-word; color:red;">El documento (' . $_FILES['fil']['name'] . ') que intenta de Cargar es el Mensaje de Hacienda por favor revise su correo y descargue el documento electronico.</li>';
                    continue;
                }

                if(!isset($array['Receptor'])){
                    echo '<li style="word-wrap: break-word; color:red;">El documento (' . $_FILES['fil']['name'] . ') que intenta cargar no tiene receptor, usted debe cargar facturas solo con sus datos como receptor.</li>';
                    continue;
                    
                }
                
                if (trim(str_replace("-", "", $this->Settings->cedula_emisor)) != $array['Receptor']['Identificacion']['Numero']) {
                    echo '<li style="word-wrap: break-word; color:red;">El numero de cedula del documento (' . $_FILES['fil']['name'] . ') que intenta no es la misma del tibutante (' . $this->Settings->nombre_emisor . ' - ' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . ') configurado en este sistema.</li>';
                    continue;
                }

                $compra['documento'] = $tdoc;
                $compra['nombre_emisor'] = $array['Emisor']['Nombre'];
                $compra['tipo_doc_emisor'] = $array['Emisor']['Identificacion']['Tipo'];
                $compra['telefono_emisor'] = isset($array['Emisor']['Telefono']['NumTelefono']) ? $array['Emisor']['Telefono']['NumTelefono'] : "";
                $compra['correo_emisor'] = isset($array['Emisor']['CorreoElectronico']) ? $array['Emisor']['CorreoElectronico'] : "";
                $compra['NumeroCedulaEmisor'] = $array['Emisor']['Identificacion']['Numero'];
                $compra['FechaEmisionDoc'] = $array['FechaEmision'];
                $compra['ClaveDocEmisor'] = $array['Clave'];
                $compra['ConsecutivoDocEmisor'] = $array['NumeroConsecutivo'];
                $compra['Mensaje'] = "";
                $compra['DetalleMensaje'] = "";
                
                $compra['CondicionVenta'] = $array['CondicionVenta'];
                $compra['MedioPago'] = isset($array['MedioPago']) && is_array($array['MedioPago']) ? $array['MedioPago'][0] : $array['MedioPago'];
                $compra['CodigoMoneda'] = isset($array['ResumenFactura']['CodigoMoneda']) ? $array['ResumenFactura']['CodigoMoneda'] : 'CRC';
                $compra['TipoCambio'] = isset($array['ResumenFactura']['TipoCambio']) ? $array['ResumenFactura']['TipoCambio'] : '1';
                
                $compra['TotalServGravados'] = isset($array['ResumenFactura']['TotalServGravados']) ? $array['ResumenFactura']['TotalServGravados'] : 0;
                $compra['TotalServExentos'] = isset($array['ResumenFactura']['TotalServExentos']) ? $array['ResumenFactura']['TotalServExentos'] : 0;
                $compra['TotalMercanciasGravadas'] = isset($array['ResumenFactura']['TotalMercanciasGravadas']) ? $array['ResumenFactura']['TotalMercanciasGravadas'] : 0;
                $compra['TotalMercanciasExentas'] = isset($array['ResumenFactura']['TotalMercanciasExentas']) ? $array['ResumenFactura']['TotalMercanciasExentas'] : 0;
                $compra['TotalGravado'] = isset($array['ResumenFactura']['TotalGravado']) ? $array['ResumenFactura']['TotalGravado'] : 0;
                $compra['TotalExento'] = isset($array['ResumenFactura']['TotalExento']) ? $array['ResumenFactura']['TotalExento'] : 0;
                $compra['TotalVenta'] = isset($array['ResumenFactura']['TotalVenta']) ? $array['ResumenFactura']['TotalVenta'] : 0;
                $compra['TotalVentaNeta'] = isset($array['ResumenFactura']['TotalVentaNeta']) ? $array['ResumenFactura']['TotalVentaNeta'] : 0;
                $compra['MontoTotalImpuesto'] = isset($array['ResumenFactura']['TotalImpuesto']) ? $array['ResumenFactura']['TotalImpuesto'] : 0;
                $compra['TotalFactura'] = $array['ResumenFactura']['TotalComprobante'];
                $compra['xml_compra'] = $xml;
                $compra['store_id'] = $this->session->userdata('store_id');

                $suppliers['name'] = $array['Emisor']['Nombre'];
                $suppliers['cf1'] = $array['Emisor']['Identificacion']['Tipo'];
                $suppliers['cf2'] = $array['Emisor']['Identificacion']['Numero'];
                $suppliers['phone'] = isset($array['Emisor']['Telefono']['NumTelefono']) ? $array['Emisor']['Telefono']['NumTelefono'] : '';
                $suppliers['email'] = isset($array['Emisor']['CorreoElectronico']) ? $array['Emisor']['CorreoElectronico'] : '';

                $x = 0;
                if (isset($array['DetalleServicio']['LineaDetalle'][0])){
                    $items_invoice = $array['DetalleServicio']['LineaDetalle'];
                }else{
                    $items_invoice = $array['DetalleServicio'];
                }
                foreach ($items_invoice as $items) {
                    $itms = $items;
                    if(isset($itms['Codigo'][0]['Codigo'])){
                        $item_compra[$x]['code'] = $itms['Codigo'][0]['Codigo'];
                    }else{
                        $item_compra[$x]['code'] = isset($itms['Codigo']['Codigo']) ? $itms['Codigo']['Codigo'] :0;
                    }
                    $item_compra[$x]['clave'] = $array['Clave'];
                    $item_compra[$x]['consecutivo'] = $array['NumeroConsecutivo'];
                    $item_compra[$x]['name'] = $itms['Detalle'];
                    $item_compra[$x]['quantity'] = $itms['Cantidad'];
                    $itms['Cantidad'] = str_replace('.', ',',$itms['Cantidad']);
                    $item_compra[$x]['cost'] = $itms['MontoTotalLinea'] /  $this->tofloat($itms['Cantidad']);
                    $item_compra[$x]['type'] = $itms['UnidadMedida'] == 'Sp' ? 'service' : 'standard';
                    $item_compra[$x]['unit_of_measurement'] = $itms['UnidadMedida'];
                    $item_compra[$x]['precio_unitario'] = $itms['PrecioUnitario'];
                    $item_compra[$x]['tarifa_impuesto'] = isset($itms['Impuesto']['Tarifa']) ? $itms['Impuesto']['Tarifa'] : 0;
                    $item_compra[$x]['monto_impuesto'] = isset($itms['Impuesto']['Monto']) ? $itms['Impuesto']['Monto'] : 0;
                    $item_compra[$x]['monto_descuento'] = isset($itms['MontoDescuento']) ? $itms['MontoDescuento'] : 0;
                    $item_compra[$x]['SubTotal'] = $itms['SubTotal'];
                    $item_compra[$x]['MontoTotalLinea'] = $itms['MontoTotalLinea'];
                    $x = $x + 1;
                }
                $this->load->model('hacienda_model');
                
                if ($this->hacienda_model->getHaciendaDocByClave($compra['ClaveDocEmisor'])) {
                    echo '<li style="word-wrap: break-word; color:#ff7800;">El documento (' . $_FILES['fil']['name'] . ') ya se encuentra cargado en el sistema';
                    continue;
                }
                
                if ($this->hacienda_model->setDocumento($compra, $suppliers, $item_compra)) {
                    echo '<li  style="word-wrap: break-word; color:#009688;>Documento (' . $_FILES['fil']['name'] . ') cargado correctamente';
                    continue;
                } else {
                    echo '<li  style="word-wrap: break-word; color:red;">Hubo un error al guardar el documento (' . $_FILES['fil']['name'] . ') intentelo nuevamente, si el error persiste comuniquese con el administrador';
                    continue;
                }
            }
            return true;
        }
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('documents_upload');
        $bc = array(array('link' => '#', 'page' => lang('documents_upload')));
        $meta = array('page_title' => lang('documents_upload'), 'bc' => $bc);
        $this->page_construct('cargadocumentos/index', $this->data, $meta);
    }

    public function get_purchases_h() {
        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("id_documento, MontoTotalImpuesto, documento, nombre_emisor, NumeroCedulaEmisor, TotalFactura, ConsecutivoDocEmisor, FechaEmisionDoc, Estatus, CodigoMoneda, TipoCambio");
        } else {
            $this->datatables->select("id_documento, MontoTotalImpuesto, documento, nombre_emisor, NumeroCedulaEmisor, TotalFactura, ConsecutivoDocEmisor, FechaEmisionDoc, Estatus, CodigoMoneda, TipoCambio");
        }

        $this->datatables->from('documentoshacienda');

        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column('status_hacienda', "<div class='text-center'><div class='btn-group'>
            <a target='_blank' href='" . site_url('XmlHacienda/xmlFirmadoRecepcion/$1') . "' title='Ver XML Firmado' class='tip btn btn-info btn-xs' ><i class='fa fa-list'></i></a>
            <a target='_blank' href='" . site_url('XmlHacienda/xmlMensajeRecepcion/$1') . "' title='Ver XML de Respuesta' class='tip btn btn-warning btn-xs' ><i class='fa fa-list'></i></a> 
            </div></div>", "id_documento");
        //        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('pos/view/$1/1') . "' title='".lang("view_invoice")."' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-list'></i></a> <a href='".site_url('sales/payments/$1')."' title='" . lang("view_payments") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-money'></i></a> <a href='".site_url('sales/add_payment/$1')."' title='" . lang("add_payment") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-briefcase'></i></a> <a href='" . site_url('pos/?edit=$1') . "' title='".lang("edit_invoice")."' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('sales/delete/$1') . "' onClick=\"return confirm('". lang('alert_x_sale') ."')\" title='".lang("delete_sale")."' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", "id");
        $this->datatables->add_column("Actions", "<div class='text-center'>
                        <div class='btn-group'>
                           <a target='_blank' href='" . site_url('cargadocumentos/getestadodocumento/$1') . "' class='tip btn btn-primary btn-xs'  data-toggle='ajax'><i class='fa fa-list'></i></a> 
                        </div>
                     </div>", "id_documento");
        // $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    public function getestadodocumento($id) {
        $data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $documento = $this->hacienda_model->getHaciendaDocByID($id);
        $this->data['documento'] = $documento;
        $this->load->view($this->theme . 'cargadocumentos/view', $this->data);
    }

    public function setstatusdocumento() {
        $this->load->library('Crearxml', NULL, 'Crearxml');
        $docreceptor = $this->Crearxml->getMensajeReceptor($this->input->post());

        $this->load->library('firmar', NULL, 'firmar');

        $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
        if (file_exists($certificado)) {
            try {
                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $docreceptor["0"], $docreceptor['3']);
            } catch (Exception $e) {
                $firmado = Null;
            }
        }
        
        $id_documento = $this->input->post('id_documento');
        $datosaceptacion =
        [
            'DetalleMensaje' => $this->input->post('DetalleMensaje'),
            'CondicionImpuesto' => $this->input->post('CondicionImpuesto'),
            'MontoTotalImpuestoAcreditar' => $this->input->post('MontoTotalImpuestoAcreditar'),
            'MontoTotalDeGastoAplicable' => $this->input->post('MontoTotalDeGastoAplicable')
        ];
        
        
        
        $this->hacienda_model->setRespuesta($docreceptor, $datosaceptacion, $id_documento, $firmado);
        redirect("cargadocumentos");
    }

    function tofloat($num) {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
            ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
       
        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        } 
    
        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
        );
    }

}
