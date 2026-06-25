<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Shacienda extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('hacienda_model');
        $this->load->model('pos_model');
        $this->load->model('sales_model');
    }

    public function index()
    {

        $cierraToken = false;
        $abrirToken = true;
        $this->load->library('Apiclient', NULL, 'ApiClient');

        $pendientes = $this->hacienda_model->getPendientes();
        $this->getNoXML();

        if ($pendientes) {
            if ($abrirToken) {

                $token_data = $this->ApiClient->getTokenH();
                $expires_in = date("i:s", $token_data->expires_in);
                $expires_in = explode(":", $expires_in);
                $ExpireToken = date('Y-m-d H:i:s', strtotime('+' . $expires_in[0] . ' minutes +' . $expires_in[0] . ' seconds ', strtotime(date('Y-m-d H:i:s'))));

                $abrirToken = false;
            }
            foreach ($pendientes as $row) {
                $date_act = date('Y-m-d H:i:s', strtotime('+15 seconds ', strtotime(date('Y-m-d H:i:s'))));

                if ($ExpireToken < $date_act) {
                    $this->ApiClient->refreshTokenH();
                }

                if ($row->xml_sign) {

                    $facturadigital = [
                        'xml' => $row->xml,
                        'xml_sign' => $row->xml_sign,
                        'clave' => $row->clave,
                        'consecutivo' => $row->consecutivo,
                        'fecha_emision' => $row->fecha_emision
                    ];

                    $MH = $this->ApiClient->send_invoice($facturadigital);
                    if (isset($MH["mensajeHacienda"]->respuestaxml)) {
                        $mensajeHacienda = base64_decode($MH["mensajeHacienda"]->respuestaxml);
                        $indestado = $MH["mensajeHacienda"]->indestado;
                    } else {
                        $mensajeHacienda = "";
                        $indestado = "Sin Estado";
                    }
                    if (isset($MH["mensajeHacienda"]->indestado)) {
                        $indestado = $MH["mensajeHacienda"]->indestado;
                    }
                    $datosHacienda = [
                        'xml_sign' => base64_decode($MH['xml_firmado']),
                        'xml_hacienda' => $mensajeHacienda,
                        'estatus_hacienda' => $indestado
                    ];

                    $this->hacienda_model->insertHacienda($datosHacienda, $row->clave);
                } else {

                    $this->load->library('firmar', NULL, 'firmar');
                    $facturadigital = ['xml' => $row->xml, 'clave' => $row->clave, 'consecutivo' => $row->consecutivo, 'fecha_emision' => $row->fecha_emision];

                    $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
                    if (file_exists($certificado)) {
                        try {
                            $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $facturadigital["xml"]);
                        } catch (Exception $e) {
                            $firmado = false;
                        }

                        if ($firmado) {
                            $facturadigital['xml_sign'] = $firmado;
                        }

                        $facturadigital['sale_id'] = $row->sale_id;

                        $this->hacienda_model->insertxml($facturadigital);
                    } else {
                        $facturadigital = [
                            'xml' => $row->xml,
                            'xml_sign' => $row->xml_sign,
                            'clave' => $row->clave,
                            'consecutivo' => $row->consecutivo,
                            'fecha_emision' => $row->fecha_emision
                        ];

                        $MH = $this->ApiClient->send_invoice($facturadigital);
                        if (isset($MH["mensajeHacienda"]->respuestaxml)) {
                            $mensajeHacienda = base64_decode($MH["mensajeHacienda"]->respuestaxml);
                            $indestado = $MH["mensajeHacienda"]->indestado;
                        } else {
                            $mensajeHacienda = "";
                            $indestado = "Sin Estado";
                        }
                        if (isset($MH["mensajeHacienda"]->indestado)) {
                            $indestado = $MH["mensajeHacienda"]->indestado;
                        }
                        $datosHacienda = [
                            'xml_sign' => base64_decode($MH['xml_firmado']),
                            'xml_hacienda' => $mensajeHacienda,
                            'estatus_hacienda' => $indestado
                        ];

                        $this->hacienda_model->insertHacienda($datosHacienda, $row->clave);
                    }
                }
            }
            $cierraToken = true;
        }


        $pendientesRD = $this->hacienda_model->getPendientesRD();

        if ($pendientesRD) {
            if ($abrirToken) {
                $token_data = $this->ApiClient->getTokenH();
                $expires_in = date("i:s", $token_data->expires_in);
                $expires_in = explode(":", $expires_in);
                $ExpireToken = date('Y-m-d H:i:s', strtotime('+' . $expires_in[0] . ' minutes +' . $expires_in[0] . ' seconds ', strtotime(date('Y-m-d H:i:s'))));

                $abrirToken = false;
            }
            foreach ($pendientesRD as $row) {
                $date_act = date('Y-m-d H:i:s', strtotime('+15 seconds ', strtotime(date('Y-m-d H:i:s'))));
                if ($ExpireToken < $date_act) {
                    $this->ApiClient->refreshTokenH();
                }
                $MH = $this->ApiClient->MensajeAprobacion($row);
            }
            $cierraToken = true;
        }
        $this->getNoXMLCn();
        $pendientesNC = $this->hacienda_model->getPendientesCN();
        if ($pendientesNC) {
            if ($abrirToken) {
                $token_data = $this->ApiClient->getTokenH();

                $expires_in = date("i:s", $token_data->expires_in);
                $expires_in = explode(":", $expires_in);
                $ExpireToken = date('Y-m-d H:i:s', strtotime('+' . $expires_in[0] . ' minutes +' . $expires_in[0] . ' seconds ', strtotime(date('Y-m-d H:i:s'))));

                $abrirToken = false;
            }
            foreach ($pendientesNC as $row) {
                $date_act = date('Y-m-d H:i:s', strtotime('+15 seconds ', strtotime(date('Y-m-d H:i:s'))));
                if ($ExpireToken < $date_act) {
                    $this->ApiClient->refreshTokenH();
                }




                if ($row->xml_sign) {

                    $facturadigital = [
                        'xml' => $row->xml,
                        'xml_sign' => $row->xml_sign,
                        'clave' => $row->clave,
                        'consecutivo' => $row->consecutivo,
                        'fecha_emision' => $row->fecha_emision
                    ];

                    $MH = $this->ApiClient->send_invoice($facturadigital);
                    if (isset($MH["mensajeHacienda"]->respuestaxml)) {
                        $mensajeHacienda = base64_decode($MH["mensajeHacienda"]->respuestaxml);
                        $indestado = $MH["mensajeHacienda"]->indestado;
                    } else {
                        $mensajeHacienda = "";
                        $indestado = "Sin Estado";
                    }
                    if (isset($MH["mensajeHacienda"]->indestado)) {
                        $indestado = $MH["mensajeHacienda"]->indestado;
                    }
                    $datosHaciendaNC = [
                        'xml_sign' => base64_decode($MH['xml_firmado']),
                        'xml_hacienda' => $mensajeHacienda,
                        'estatus_hacienda' => $indestado
                    ];

                    $this->hacienda_model->insertHaciendaCN($datosHaciendaNC, $row->clave);
                } else {
                    $this->load->library('firmar', NULL, 'firmar');
                    $facturadigital = ['xml' => $row->xml, 'clave' => $row->clave, 'consecutivo' => $row->consecutivo, 'fecha_emision' => $row->fecha_emision];
                    $duplicado = $this->hacienda_model->getCN($row->id_cn);
                    $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
                    if (file_exists($certificado)) {
                        if (!$duplicado) {
                            try {
                                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $facturadigital["xml"]);
                            } catch (Exception $e) {
                                $firmado = false;
                            }

                            if ($firmado) {
                                $facturadigital['xml_sign'] = $firmado;
                            }

                            $facturadigital['sale_id'] = $row->sale_id;

                            $this->hacienda_model->insertxml($facturadigital);
                        }
                    } else {
                        $facturadigital = [
                            'xml' => $row->xml,
                            'xml_sign' => $row->xml_sign,
                            'clave' => $row->clave,
                            'consecutivo' => $row->consecutivo,
                            'fecha_emision' => $row->fecha_emision
                        ];

                        $MH = $this->ApiClient->send_invoice($facturadigital);
                        if (isset($MH["mensajeHacienda"]->respuestaxml)) {
                            $mensajeHacienda = base64_decode($MH["mensajeHacienda"]->respuestaxml);
                            $indestado = $MH["mensajeHacienda"]->indestado;
                        } else {
                            $mensajeHacienda = "";
                            $indestado = "Sin Estado";
                        }
                        if (isset($MH["mensajeHacienda"]->indestado)) {
                            $indestado = $MH["mensajeHacienda"]->indestado;
                        }
                        $datosHaciendaNC = [
                            'xml_sign' => base64_decode($MH['xml_firmado']),
                            'xml_hacienda' => $mensajeHacienda,
                            'estatus_hacienda' => $indestado
                        ];

                        $this->hacienda_model->insertHaciendaCN($datosHaciendaNC, $row->clave);
                    }
                }
            }
            $cierraToken = true;


            $this->getNoXML();
        }

        $this->getNoXMLFec();
        $pendientesFec = $this->hacienda_model->getPendientesFec();
        if ($pendientesFec) {
            if ($abrirToken) {

                $token_data = $this->ApiClient->getTokenH();
                $expires_in = date("i:s", $token_data->expires_in);
                $expires_in = explode(":", $expires_in);
                $ExpireToken = date('Y-m-d H:i:s', strtotime('+' . $expires_in[0] . ' minutes +' . $expires_in[0] . ' seconds ', strtotime(date('Y-m-d H:i:s'))));

                $abrirToken = false;
            }
            foreach ($pendientesFec as $row) {
                if ($row->sale_id > 0) {
                    $date_act = date('Y-m-d H:i:s', strtotime('+15 seconds ', strtotime(date('Y-m-d H:i:s'))));

                    if ($ExpireToken < $date_act) {
                        $this->ApiClient->refreshTokenH();
                    }
                    if ($row->xml_sign) {
                        // dd($row->xml);
                        $facturadigital = [
                            'xml' => $row->xml,
                            'xml_sign' => $row->xml_sign,
                            'clave' => $row->clave,
                            'consecutivo' => $row->consecutivo,
                            'fecha_emision' => $row->fecha_emision
                        ];
                        $MH = $this->ApiClient->send_invoice($facturadigital);
                        if (isset($MH["mensajeHacienda"]->respuestaxml)) {
                            $mensajeHacienda = base64_decode($MH["mensajeHacienda"]->respuestaxml);
                            $indestado = $MH["mensajeHacienda"]->indestado;
                        } else {
                            $mensajeHacienda = "";
                            $indestado = "Sin Estado";
                        }
                        if (isset($MH["mensajeHacienda"]->indestado)) {
                            $indestado = $MH["mensajeHacienda"]->indestado;
                        }
                        $datosHacienda = [
                            'xml_sign' => base64_decode($MH['xml_firmado']),
                            'xml_hacienda' => $mensajeHacienda,
                            'estatus_hacienda' => $indestado
                        ];

                        $this->hacienda_model->insertHaciendaFec($datosHacienda, $row->clave);
                    } else {
                        $this->load->library('firmar', NULL, 'firmar');
                        $facturadigital = ['xml' => $row->xml, 'clave' => $row->clave, 'consecutivo' => $row->consecutivo, 'fecha_emision' => $row->fecha_emision];

                        $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
                        if (file_exists($certificado)) {
                            try {
                                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $facturadigital["xml"]);
                            } catch (Exception $e) {
                                $firmado = false;
                            }
                            if ($firmado) {
                                $facturadigital['xml_sign'] = $firmado;
                            }

                            $facturadigital['sale_id'] = $row->sale_id;

                            $this->hacienda_model->insertxmlfec($facturadigital);
                        } else {
                            $facturadigital = [
                                'xml' => $row->xml,
                                'xml_sign' => $row->xml_sign,
                                'clave' => $row->clave,
                                'consecutivo' => $row->consecutivo,
                                'fecha_emision' => $row->fecha_emision
                            ];

                            $MH = $this->ApiClient->send_invoice($facturadigital);
                            if (isset($MH["mensajeHacienda"]->respuestaxml)) {
                                $mensajeHacienda = base64_decode($MH["mensajeHacienda"]->respuestaxml);
                                $indestado = $MH["mensajeHacienda"]->indestado;
                            } else {
                                $mensajeHacienda = "";
                                $indestado = "Sin Estado";
                            }
                            if (isset($MH["mensajeHacienda"]->indestado)) {
                                $indestado = $MH["mensajeHacienda"]->indestado;
                            }
                            $datosHacienda = [
                                'xml_sign' => base64_decode($MH['xml_firmado']),
                                'xml_hacienda' => $mensajeHacienda,
                                'estatus_hacienda' => $indestado
                            ];

                            $this->hacienda_model->insertHaciendaFec($datosHacienda, $row->clave);
                        }
                    }
                }
            }
            $cierraToken = true;
        }

        $noenviadosNC = $this->hacienda_model->getnoEnviadosCN();
        if ($noenviadosNC) {
            foreach ($noenviadosNC as $row) {
                $id_cn = $row->id_cn;
                $to = NULL;
                $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
                $this->data['message'] = $this->session->flashdata('message');
                $inv = $this->pos_model->getCreditNoteByID($id_cn);

                $this->load->helper('text');
                $this->data['rows'] = $this->pos_model->getAllCreditNotesItems($id_cn);
                $this->data['customer'] = $this->pos_model->getCustomerByID($inv->customer_id);
                $this->data['store'] = $this->site->getStoreByID($inv->store_id);
                $this->data['inv'] = $inv;
                $this->data['sid'] = $id_cn;
                $this->data['noprint'] = NULL;
                $this->data['modal'] = false;
                $this->data['page_title'] = "Nota de Credito";
                $this->data['created_by'] = $this->site->getUser($inv->created_by);
                $this->data['hacienda'] = $this->hacienda_model->getCN($id_cn);
                $this->data['haciendaInvo'] = $this->hacienda_model->getInvoice($inv->sale_id);
                $this->data['invoicebarcode'] = $this->invice_barcode($this->data['hacienda']->consecutivo, 'code128', 60);


                $receipt = $this->load->view($this->theme . 'pos/viewnc', $this->data, TRUE);
                $message = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
                $subject = lang('email_subject') . ' - ' . $this->Settings->site_name;


                $this->data['tipo_documento'] = "Nota de credito Electronica";
                $html = $this->load->view($this->theme . 'pos/invoice', $this->data, true);



                $xml_sign = $this->hacienda_model->xmlFirmadoCN($id_cn)->xml_sign;
                $xml_hacienda = $this->hacienda_model->xmlMensajeCN($id_cn)->xml_hacienda;
                $clave = $this->hacienda_model->getClaveCN($id_cn)->clave;

                $attach = array();
                $mpdf = new \Mpdf\Mpdf();
                $mpdf->WriteHTML($html);
                $mpdf->Output(sys_get_temp_dir() . '/' . 'T3_' . $clave . '.pdf', 'F');

                $attach = [
                    'T3_' . $clave => $xml_sign,
                    'M3_' . $clave => $xml_hacienda,
                    'ruta' => sys_get_temp_dir() . '/' . 'T3_' . $clave . '.pdf',
                ];

                if ($this->data['customer']->email) {
                    $to = $this->data['customer']->email;
                    $this->load->library('Swiftmailer', NULL, 'Swiftmailer');
                    if ($this->Swiftmailer->send_email($to, $subject, $message, null, null, $attach)) {
                        echo " Enviada a: " . $to . ",";
                        $this->hacienda_model->MarcaEnviadoCN($id_cn, '1');
                    } else {
                        $this->hacienda_model->MarcaEnviadoCN($id_cn, '0');
                    }
                } else {
                    $this->hacienda_model->MarcaEnviadoCN($id_cn, '1');
                }
            }
        }

        $noenviados = $this->hacienda_model->getnoEnviados();
        if ($noenviados) {
            foreach ($noenviados as $row) {
                $sale_id = $row->sale_id;
                $to = NULL;
                $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
                $this->data['message'] = $this->session->flashdata('message');
                $inv = $this->pos_model->getSaleByID($sale_id);
                $this->load->helper('text');
                $this->data['rows'] = $this->pos_model->getAllSaleItems($sale_id);
                $this->data['customer'] = $this->pos_model->getCustomerByID($inv->customer_id);
                $this->data['inv'] = $inv;
                $this->data['sid'] = $sale_id;
                $this->data['noprint'] = NULL;
                $this->data['page_title'] = lang('invoice');
                $this->data['modal'] = false;
                $this->data['payments'] = $this->pos_model->getAllSalePayments($sale_id);
                $this->data['created_by'] = $this->site->getUser($inv->created_by);
                $this->data['hacienda'] = $this->hacienda_model->getInvoice($sale_id);
                $this->data['invoicebarcode'] = $this->invice_barcode($this->data['hacienda']->consecutivo, 'code128', 60);

                $receipt = $this->load->view($this->theme . 'pos/view', $this->data, TRUE);
                $message = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
                $subject = lang('email_subject') . ' - ' . $this->Settings->site_name;

                $xml_sign = $this->hacienda_model->xmlFirmado($sale_id)->xml_sign;
                $xml_hacienda = $this->hacienda_model->xmlMensaje($sale_id)->xml_hacienda;
                $clave = $this->hacienda_model->getClave($sale_id)->clave;

                $this->data['tipo_documento'] = "Tiquete Electronico";
                $html = $this->load->view($this->theme . 'pos/invoice', $this->data, true);

                $attach = array();
                $mpdf = new \Mpdf\Mpdf();
                $mpdf->WriteHTML($html);
                $mpdf->Output(sys_get_temp_dir() . '/' . 'T3_' . $clave . '.pdf', 'F');

                $attach = [
                    'T4_' . $clave => $xml_sign,
                    'M4_' . $clave => $xml_hacienda,
                    'ruta' => sys_get_temp_dir() . '/' . 'T3_' . $clave . '.pdf',
                ];


                try {

                    if ($this->data['customer']->email) {
                        $to = $this->data['customer']->email;
                        $this->load->library('Swiftmailer', NULL, 'Swiftmailer');
                        if ($this->Swiftmailer->send_email($to, $subject, $message, null, null, $attach)) {
                            echo " Enviada a: " . $to . ",";
                            $this->hacienda_model->MarcaEnviado($sale_id, '1');
                        } else {
                            $this->hacienda_model->MarcaEnviado($sale_id, '0');
                        }
                    } else {
                        $this->hacienda_model->MarcaEnviado($sale_id, '1');
                    }
                } catch (Exception $e) { }
            }
        }

        $noenviadosRecep = $this->hacienda_model->getnoEnviadosRecepcion();
        if ($noenviadosRecep) {
            foreach ($noenviadosRecep as $row) {
                $id_documento = $row->id_documento;
                $documento = $this->hacienda_model->getHaciendaDocByID($id_documento);
                $to = NULL;

                if ($documento->Mensaje == '1') {
                    $stat = 'aceptado';
                } elseif ($documento->Mensaje == '2') {
                    $stat = 'aceptado parcialmente';
                } elseif ($documento->Mensaje == '3') {
                    $stat = 'rechazado';
                }
                $subject = "Hemos " . $stat . " la factura #" . $documento->ConsecutivoDocEmisor;

                $message = "<div style='width: 600px; margin: 0 auto; border: solid 1px #9c9c9c; border-radius: 10px; padding: 10px;'>
            <h1 style='width: 100%; text-align: center;'> Estimado {$documento->nombre_emisor} </h1>
            <p>En conformidad de hacienda nos comprometemos a informarles que:</p>
            <p>Hemos {$stat} ante hacienda la factura <b>#{$documento->ConsecutivoDocEmisor}</b>, enviando como mensaje o motivo <b>\"{$documento->DetalleMensaje}\"</b></p>
            <p>Si se encuentra inconforme con el estatus dado a la factura por favor comuniquese con nosotros</p>
            </div>";

                $attach = [
                    'T5_' . $documento->consecutivo => $documento->xml_firmado,
                    'M5_' . $documento->consecutivo => $documento->xml_hacienda,
                ];


                $to = $documento->correo_emisor;
                $this->load->library('Swiftmailer', NULL, 'Swiftmailer');
                if ($this->Swiftmailer->send_email($to, $subject, $message, null, null, $attach)) {
                    echo " Enviada a: " . $to . ",";
                    $this->hacienda_model->MarcaEnviadoRecepcion($id_documento, '1');
                } else {
                    $this->hacienda_model->MarcaEnviadoRecepcion($id_documento, '0');
                }
            }
        }


        if ($cierraToken) {
            $this->ApiClient->CloseTokenH();
        }
        if($this->Settings->enabled_massive_mail == "1")
        {
            $this->cargamasivadexmldesdeemail();
        }
    }

    function invice_barcode($id_invoice = NULL, $bcs = 'code128', $height = 60)
    {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode($id_invoice, $bcs, $height);
    }

    function invoicedesign()
    {
        $this->load->view($this->theme . 'pos/invoice', $this->data);
    }

    function cargamasivadexmldesdeemail() {

        // $hostname = '{'.env('MAIL_CLIENTE_HOST').':'.env('MAIL_CLIENTE_PORT').'/'.env('MAIL_CLIENTE_TIPO').'/notls/novalidate-cert}';
        // $username = env('MAIL_CLIENTE_USER');
        // $password = env('MAIL_CLIENTE_PASSWORD');
        $canbus = 1;
        if($this->Settings->is_gmail == "1")
        {
            $hostname = '{'.$this->Settings->mail_client_host.':'.$this->mail_client_port.'/'.$this->Settings->mail_client_tipo.'/ssl/novalidate-cert}';
        }else
        {
            $hostname = '{'.$this->Settings->mail_client_host.':'.$this->mail_client_port.'/'.$this->Settings->mail_client_tipo.'/notls/novalidate-cert}';
        }
        $username = $this->Settings->mail_client_user;
        $password = $this->Settings->mail_client_pass;

        $lista=array(
            "0"=>$hostname."INBOX",
            "1"=>$hostname."Spam",
        );

        for($canlis=0;$canlis<=count($lista)-1;$canlis++)
        {
        echo '<BR>';
        echo 'CARPERTA '. $lista[$canlis];
        echo '<BR>';

        $inbox = imap_open($lista[$canlis], $username, $password) or die('Ha fallado la conexiÃ³n: ' . imap_last_error());
        if ($inbox) {
            echo 'conectado';
            echo '<br>';
            echo 'buscando emails';
            echo '<br>';
            $ficheroBusqueda = 'XML';
            $siesfactura = 0;
            $asunto = '';
            $cantiemail = 0;
            $dirfichero = base_path() . '/files/xmltemp/';
            $dirficheroPDF = base_path() . '/files/InvoicePdfs/';
             //    $dirfichero='';
            $emails = imap_search($inbox, "UNSEEN");
            if ($emails) 
            {

                rsort($emails);

                foreach ($emails as $email) {
                    $cantiemail++;
                    if ($cantiemail < $canbus) {
                        echo '<br>';
                        echo '<br>';
                        echo 'examinando email ' . $email;

                        $propiedades = imap_fetch_overview($inbox, $email);
                        $structure = imap_fetchstructure($inbox, $email);

                        // compruebo si hay ficheros adjuntos
                        $attachments = array();
                        $banregisto = '';
                        $typePDF = '';
                        $nombreFicheroPDF = '';
                        $adjuntoPDF = '';
                        $namePDF = '';
                        //print_r($structure);

                        if ($structure->type === 1) 
                        {

                            echo ' CANTIDAD DE ARCHIVOS ADJUNTOS ';
                            echo count($structure->parts);
                            echo '<br>';
                            //    for($i = 0; $i < 10; $i ++) 
                            for ($i = 0; $i < count($structure->parts); $i ++) {

                                echo '<br>';
                                echo '-----------------Archivo ' . $i . '-----------------------';
                                echo '<br>';

                                $attachments [$i] = array(
                                    'is_attachment' => false,
                                    'filename' => '',
                                    'name' => '',
                                    'type' => '',
                                    'attachment' => ''
                                );

                                if ($structure->parts [$i]->ifdparameters) {
                                    foreach ($structure->parts [$i]->dparameters as $object) {

                                        echo '<br> Tipo de Atributo';
                                        echo strtolower($object->attribute);
                                        echo '<br>';
                                        // if (strtolower ( $object->attribute ) == 'filename') {
                                        $attachments [$i] ['is_attachment'] = true;
                                        echo 'Nombre de archivo: ';
                                        echo $attachments [$i] ['filename'] = $object->value;
                                        echo '<br>';
                                        echo 'Tipo de archivo: ';
                                        echo $attachments [$i] ['type'] = $structure->parts [$i]->subtype;
                                        echo '<br>';
                                        // }
                                    }
                                } else
                                if ($structure->parts [$i]->ifparameters) {
                                    foreach ($structure->parts [$i]->parameters as $object) {
                                        echo '<br> Tipo de Atributo: ';
                                        echo strtolower($object->attribute);
                                        echo '<br>';
                                        // if (strtolower ( $object->attribute ) == 'filename') {
                                        $attachments [$i] ['is_attachment'] = true;
                                        echo 'Nombre de archivo: ';
                                        echo $attachments [$i] ['filename'] = $object->value;
                                        echo '<br>';
                                        echo 'Tipo de archivo: ';
                                        echo $attachments [$i] ['type'] = $structure->parts [$i]->subtype;
                                        echo '<br>';
                                        // }
                                    }
                                }

                                if ($attachments [$i] ['is_attachment']) {

                                    $attachments [$i] ['attachment'] = imap_fetchbody($inbox, $email, $i + 1);
                                    if ($structure->parts [$i]->encoding == 3) { // 3 = BASE64
                                        $attachments [$i] ['attachment'] = base64_decode($attachments [$i] ['attachment']);
                                    } elseif ($structure->parts [$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                                        $attachments [$i] ['attachment'] = quoted_printable_decode($attachments [$i] ['attachment']);
                                    }


                                    $nombreFichero = $attachments [$i] ['filename'];
                                    $adjunto = $attachments [$i] ['attachment'];
                                    $name = $attachments [$i] ['name'];
                                    $type = $attachments [$i] ['type'];
                                    //echo "Comparando TIPO DE FICHERO PDF <br />";
                                    //if ((substr($nombreFichero, -4) == '.pdf' && $typePDF == '') || (substr($nombreFichero, -4) == '.PDF' && $typePDF == '')) {
                                    //    echo '<br>';
                                    //    echo ' Email ' . $email . ' con PDF archivo numero ' . $i;
                                    //    $typePDF = 'SI';
                                    //    $nombreFicheroPDF = $nombreFichero;
                                    //    $adjuntoPDF = $adjunto;
                                     //   $namePDF = $name;
                                    //}

                                    //echo "Comparando TIPO DE FICHERO XML <br />";
                                        //                            if ($ficheroBusqueda == $type || $type == 'OCTET-STREAM' || $type=='ALTERNATIVE' || $type == 'MIXED')
                                    if ($ficheroBusqueda == $type || $type == 'OCTET-STREAM' || $type == 'ALTERNATIVE' || $type == 'RELATED' || $type == 'MIXED') {
                                        echo '<br> extens: ';
                                        echo substr($nombreFichero, -4);
                                        echo '<br>';
                                        if (substr($nombreFichero, -4) != '.pdf') {
                                            $exten = '';
                                            if (substr($nombreFichero, -4) != '.xml')
                                                $exten = '.xml';

                                            echo '<br>';
                                            echo ' Email ' . $email . ' con XML archivo numero ' . $i;
                                            //echo '<br>';
                                            $pase = 'NO';
                                            //verificar los parametros de un archivo xml
                                            if (strpos($adjunto, '<Clave>') !== false) {
                                                echo '<br>';
                                                echo ' Email ' . $email . ' con CLAVE';
                                                if (strpos($adjunto, '<Emisor>') !== false) {
                                                    echo '<br>';
                                                    echo ' Email ' . $email . ' con EMISOR';
                                                    if (strpos($adjunto, '<Receptor>') !== false) {
                                                        echo '<br>';
                                                        echo ' Email ' . $email . ' con RECEPTOR';
                                                        $pase = 'SI';
                                                    }
                                                }
                                            }

                                            if ($adjunto && $pase == 'SI') {
                                                //agregar salto de linea en el archivo
                                                $adjunto = str_replace('Ã¡', 'a', $adjunto);
                                                $adjunto = str_replace('Ã©', 'e', $adjunto);
                                                $adjunto = str_replace('Ã­', 'i', $adjunto);
                                                $adjunto = str_replace('Ã³', 'o', $adjunto);
                                                $adjunto = str_replace('Ãº', 'u', $adjunto);
                                                $adjunto = str_replace('Ã', 'A', $adjunto);
                                                $adjunto = str_replace('Ã‰', 'E', $adjunto);
                                                $adjunto = str_replace('Ã', 'I', $adjunto);
                                                $adjunto = str_replace('Ã“', 'O', $adjunto);
                                                $adjunto = str_replace('Ãš', 'U', $adjunto);
                                                $adjunto = str_replace('&aacute;', 'a', $adjunto);
                                                $adjunto = str_replace('&eacute;', 'e', $adjunto);
                                                $adjunto = str_replace('&iacute;', 'i', $adjunto);
                                                $adjunto = str_replace('&oacute;', 'o', $adjunto);
                                                $adjunto = str_replace('&uacute;', 'u', $adjunto);
                                                $adjunto = str_replace('&Aacute;', 'A', $adjunto);
                                                $adjunto = str_replace('&Eacute;', 'E', $adjunto);
                                                $adjunto = str_replace('&Iacute;', 'I', $adjunto);
                                                $adjunto = str_replace('&Oacute;', 'O', $adjunto);
                                                $adjunto = str_replace('&Uacute;', 'U', $adjunto);

                                                $adjunto = str_replace('?>', '?>
                                             ', $adjunto);
                                                $adjunto = str_replace('">', '">
                                             ', $adjunto);
                                                $adjunto = str_replace('</Clave>', '</Clave>
                                             ', $adjunto);
                                                $adjunto = str_replace('</NumeroConsecutivo>', '</NumeroConsecutivo>
                                             ', $adjunto);
                                                $adjunto = str_replace('</FechaEmision>', '</FechaEmision>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Emisor>', '<Emisor>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Nombre>', '</Nombre>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Identificacion>', '<Identificacion>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Tipo>', '</Tipo>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Numero>', '</Numero>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Identificacion>', '</Identificacion>
                                             ', $adjunto);
                                                $adjunto = str_replace('</NombreComercial>', '</NombreComercial>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Ubicacion>', '<Ubicacion>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Provincia>', '</Provincia>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Canton>', '</Canton>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Distrito>', '</Distrito>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Barrio>', '</Barrio>
                                             ', $adjunto);
                                                $adjunto = str_replace('</OtrasSenas>', '</OtrasSenas>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Ubicacion>', '</Ubicacion>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Telefono>', '<Telefono>
                                             ', $adjunto);
                                                $adjunto = str_replace('</CodigoPais>', '</CodigoPais>
                                             ', $adjunto);
                                                $adjunto = str_replace('</NumTelefono>', '</NumTelefono>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Telefono>', '</Telefono>
                                             ', $adjunto);
                                                $adjunto = str_replace('</CorreoElectronico>', '</CorreoElectronico>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Emisor>', '</Emisor>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Receptor>', '<Receptor>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Nombre>', '</Nombre>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Identificacion>', '<Identificacion>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Tipo>', '</Tipo>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Numero>', '</Numero>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Identificacion>', '</Identificacion>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Ubicacion>', '<Ubicacion>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Provincia>', '</Provincia>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Canton>', '</Canton>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Distrito>', '</Distrito>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Barrio>', '</Barrio>
                                             ', $adjunto);
                                                $adjunto = str_replace('</OtrasSenas>', '</OtrasSenas>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Ubicacion>', '</Ubicacion>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Telefono>', '<Telefono>
                                             ', $adjunto);
                                                $adjunto = str_replace('</CodigoPais>', '</CodigoPais>
                                             ', $adjunto);
                                                $adjunto = str_replace('</NumTelefono>', '</NumTelefono>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Telefono>', '</Telefono>
                                             ', $adjunto);
                                                $adjunto = str_replace('</CorreoElectronico>', '</CorreoElectronico>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Receptor>', '</Receptor>
                                             ', $adjunto);
                                                $adjunto = str_replace('</CondicionVenta>', '</CondicionVenta>
                                             ', $adjunto);
                                                $adjunto = str_replace('</MedioPago>', '</MedioPago>
                                             ', $adjunto);
                                                $adjunto = str_replace('<DetalleServicio>', '<DetalleServicio>
                                             ', $adjunto);
                                                $adjunto = str_replace('<LineaDetalle>', '<LineaDetalle>
                                             ', $adjunto);
                                                $adjunto = str_replace('</NumeroLinea>', '</NumeroLinea>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Codigo>', '<Codigo>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Tipo>', '</Tipo>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Codigo>', '</Codigo>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Codigo>', '</Codigo>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Cantidad>', '</Cantidad>
                                             ', $adjunto);
                                                $adjunto = str_replace('</UnidadMedida>', '</UnidadMedida>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Detalle>', '</Detalle>
                                             ', $adjunto);
                                                $adjunto = str_replace('</PrecioUnitario>', '</PrecioUnitario>
                                             ', $adjunto);
                                                $adjunto = str_replace('</MontoTotal>', '</MontoTotal>
                                             ', $adjunto);
                                                $adjunto = str_replace('</SubTotal>', '</SubTotal>
                                             ', $adjunto);
                                                $adjunto = str_replace('</MontoTotalLinea>', '</MontoTotalLinea>
                                             ', $adjunto);
                                                $adjunto = str_replace('</LineaDetalle>', '</LineaDetalle>
                                             ', $adjunto);
                                                $adjunto = str_replace('</DetalleServicio>', '</DetalleServicio>
                                             ', $adjunto);
                                                $adjunto = str_replace('<ResumenFactura>', '<ResumenFactura>
                                             ', $adjunto);
                                                $adjunto = str_replace('</CodigoMoneda>', '</CodigoMoneda>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TipoCambio>', '</TipoCambio>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalServGravados>', '</TotalServGravados>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalServExentos>', '</TotalServExentos>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalMercanciasGravadas>', '</TotalMercanciasGravadas>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalMercanciasExentas>', '</TotalMercanciasExentas>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalGravado>', '</TotalGravado>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalExento>', '</TotalExento>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalVenta>', '</TotalVenta>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalDescuentos>', '</TotalDescuentos>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalVentaNeta>', '</TotalVentaNeta>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalImpuesto>', '</TotalImpuesto>
                                             ', $adjunto);
                                                $adjunto = str_replace('</TotalComprobante>', '</TotalComprobante>
                                             ', $adjunto);
                                                $adjunto = str_replace('</ResumenFactura>', '</ResumenFactura>
                                             ', $adjunto);
                                                $adjunto = str_replace('<Normativa>', '<Normativa>
                                             ', $adjunto);
                                                $adjunto = str_replace('</NumeroResolucion>', '</NumeroResolucion>
                                             ', $adjunto);
                                                $adjunto = str_replace('</FechaResolucion>', '</FechaResolucion>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Normativa>', '</Normativa>
                                             ', $adjunto);
                                                $adjunto = str_replace('</Tarifa>', '</Tarifa>
                                             ', $adjunto);
                                                $adjunto = str_replace('<ds', '
                                             <ds', $adjunto);
                                                $adjunto = str_replace('</ds', '
                                             </ds', $adjunto);
                                                $adjunto = str_replace('<xa', '
                                             <xa', $adjunto);
                                                $adjunto = str_replace('</xa', '
                                             </xa', $adjunto);
                                                $adjunto = str_replace('</Monto>', '</Monto>
                                         ', $adjunto);
                                                $adjunto = str_replace('</Impuesto>', '</Impuesto>
                                         ', $adjunto);
                                                $adjunto = str_replace('&amp;', '', $adjunto);
                                                //$adjunto=str_replace('PALABRADECAMBIO', 'PALABRADECAMBIO
                                                // ',$adjunto);

                                                $adjunto = str_replace('</FacturaElectronica>', '</FacturaElectronica>
                                         ', $adjunto);
                                                $adjunto = utf8_encode($adjunto);

                                                $adjunto = str_replace('ÃƒÂ¡', 'a', $adjunto);
                                                $adjunto = str_replace('ÃƒÂ©', 'e', $adjunto);
                                                $adjunto = str_replace('Ãƒ*', 'i', $adjunto);
                                                $adjunto = str_replace('ÃƒÂ³', 'o', $adjunto);
                                                $adjunto = str_replace('ÃƒÂº', 'u', $adjunto);
                                                $adjunto = str_replace('Ãƒ', 'A', $adjunto);
                                                $adjunto = str_replace('Ãƒâ€°', 'E', $adjunto);
                                                $adjunto = str_replace('Ãƒ', 'I', $adjunto);
                                                $adjunto = str_replace('Ãƒâ€œ', 'O', $adjunto);
                                                $adjunto = str_replace('ÃƒÅ¡', 'U', $adjunto);
                                                $adjunto = str_replace('ÃƒÂ±', 'Ã±', $adjunto);
                                                $adjunto = str_replace('Ãƒâ€˜', 'Ã‘', $adjunto);
                                                $adjunto = str_replace('Ã‚Âº', '', $adjunto);
                                                $adjunto = str_replace('Ã‚Âª', '', $adjunto);
                                                $adjunto = str_replace('Ã‚Â¿', 'Â¿', $adjunto);
                                                $adjunto = str_replace('Ã¯Â»Â¿', '', $adjunto);

                                                //echo $adjunto;    
                                                //$gestor = fopen ( $dirfichero.$nombreFichero.$exten, 'w');
                                                //fwrite ( $gestor, $adjunto );
                                                //fclose ( $gestor );
                                                //abrimos el archivo para verificar si es una factura o no
                                                //$xml = simplexml_load_file($dirfichero.$nombreFichero.$exten);
                                                //$xml=  new SimpleXMLElement($adjunto);

                                                $xml = simplexml_load_string($adjunto);
                                                $xmlstr = $xml;
                                                $documento = $xmlstr;
                                                $canarticulo = 0;
                                                if ($documento->Emisor->Nombre || $documento->Receptor->Nombre) {
                                                    echo '<br>';
                                                    echo ' Email ' . $email . ' Si es factura archivo numero ' . $i;
                                                    $existe = $this->db->where('ClaveDocEmisor', $documento->Clave)->get($this->db->dbprefix('documentoshacienda'))->limit(1);
                                                    //echo '<br>';
                                                    $clavebd = '';
                                                    $siesfactura++;
                                                    $pro='';
                                                    foreach ($existe as $pro) {
                                                        $clavebd = $pro->Clave;
                                                    }
                                                        echo '<br>';
                                                        echo ' DATOS DE CLAVES - '.$clavebd.' - '.$documento->Clave;

                                                    if ($documento->Clave != $clavebd) {
                                                        echo '<br>';
                                                        echo ' Email ' . $email . ' No existe debe registrarse archivo numero ' . $i;
                                                        
                                                        //Cabecera
                                                        $Clavee = $documento->Clave;

                                                        $consultipodoc='FacturaElectronica';
                                                        $tdoc = 'Factura Electronica';

                                                        if (strpos($xml, "TiqueteElectronico")) {
                                                        $consultipodoc='TiqueteElectronico';
                                                            $tdoc = "Tiquete Electronico";
                                                        } elseif (strpos($xml, "NotaCreditoElectronica")) {
                                                        $consultipodoc='NotaCreditoElectronica';
                                                            $tdoc = "Nota de Credito Electronica";
                                                        } elseif (strpos($xml, "NotaDebitoElectronica")) {
                                                        $consultipodoc='NotaDebitoElectronica';
                                                            $tdoc = "Nota de Debito Electronica";
                                                        }                                                        //EMISOR
                                                        $NombreE = $documento->Emisor->Nombre;
                                                        $TipodocEmisor = $documento->Emisor->Identificacion->Tipo;
                                                        $NumerocedulaE = $documento->Emisor->Identificacion->Numero;
                                                        $NombreComercialE = $documento->Emisor->NombreComercial;
                                                        $ProvinciaE = $documento->Emisor->Ubicacion->Provincia;
                                                        $CantonE = $documento->Emisor->Ubicacion->Canton;
                                                        $DistritoE = $documento->Emisor->Ubicacion->Distrito;
                                                        $BarrioE = $documento->Emisor->Ubicacion->Barrio;
                                                        $OtrasSenasE = $documento->Emisor->Ubicacion->OtrasSenas;
                                                        $CodigoPaisE = $documento->Emisor->Telefono->CodigoPais;
                                                        $NumTelefonoE = $documento->Emisor->Telefono->NumTelefono;
                                                        $CorreoElectronicoE = $documento->Emisor->CorreoElectronico;

                                                        //Receptor
                                                        $NombreR = $documento->Receptor->Nombre;
                                                        $NumerocedulaR = $documento->Receptor->Identificacion->Numero;
                                                        $NombreComercialR = $documento->Receptor->NombreComercial;
                                                        $ProvinciaR = $documento->Receptor->Ubicacion->Provincia;
                                                        $CantonR = $documento->Receptor->Ubicacion->Canton;
                                                        $DistritoR = $documento->Receptor->Ubicacion->Distrito;
                                                        $BarrioR = $documento->Receptor->Ubicacion->Barrio;
                                                        $OtrasSenasR = $documento->Receptor->Ubicacion->OtrasSenas;
                                                        $CodigoPaisR = $documento->Receptor->Telefono->CodigoPais;
                                                        $NumTelefonoR = $documento->Receptor->Telefono->NumTelefono;
                                                        $CorreoElectronicoR = $documento->Receptor->CorreoElectronico;

                                                        //DATOS DE FACTURA
                                                        $NumeroConsecutivo = $documento->NumeroConsecutivo;
                                                        $FechaEmision = $documento->FechaEmision;
                                                        //$FechaEmision=substr($FechaEmision,0,10);
                                                        $FechaVencimiento = $FechaEmision;

                                                        $CondicionVenta = $documento->CondicionVenta;
                                                        $MedioPago = $documento->MedioPago;
                                                        $CodigoMoneda = $documento->ResumenFactura->CodigoMoneda;
                                                        $TipoCambio = $documento->ResumenFactura->TipoCambio;
                                                        $TotalServGravados = $documento->ResumenFactura->TotalServGravados;
                                                        $TotalServExentos = $documento->ResumenFactura->TotalServExentos;
                                                        $TotalMercanciasGravadas = $documento->ResumenFactura->TotalMercanciasGravadas;
                                                        $TotalMercanciasExentas = $documento->ResumenFactura->TotalMercanciasExentas;
                                                        $TotalGravado = $documento->ResumenFactura->TotalGravado;
                                                        $TotalExento = $documento->ResumenFactura->TotalExento;
                                                        $TotalVenta = $documento->ResumenFactura->TotalVenta;
                                                        $TotalDescuentos = $documento->ResumenFactura->TotalDescuentos;
                                                        $TotalVentaNeta = $documento->ResumenFactura->TotalVentaNeta;
                                                        $TotalImpuesto = $documento->ResumenFactura->TotalImpuesto;
                                                        $TotalComprobante = $documento->ResumenFactura->TotalComprobante;

                                                        //Normativa
                                                        $NumeroResolucion = $documento->Normativa->NumeroResolucion;
                                                        $FechaResolucion = $documento->Normativa->FechaResolucion;
                                                        
                                                        /*if (strpos($xml, "</".$consultipodoc.">")) 
                                                        {*/

                                                        $compra = array(
                                                            "documento" => $tdoc,
                                                            "nombre_emisor" => $NombreE,
                                                            "tipo_doc_emisor" => $TipodocEmisor,
                                                            "telefono_emisor" => isset($NumTelefonoE)?$NumTelefonoE:"",
                                                            "correo_emisor" => isset($CorreoElectronicoE)?$CorreoElectronicoE:"",
                                                            "NumeroCedulaEmisor" => $NumerocedulaE,
                                                            "FechaEmisionDoc" => $FechaEmision,
                                                            "ClaveDocEmisor" => $Clavee,
                                                            "ConsecutivoDocEmisor" => $NumeroConsecutivo,
                                                            "Mensaje" => "",
                                                            "DetalleMensaje" => "",
                                                            "CondicionVenta" => $CondicionVenta,
                                                            "MedioPago"=> isset($MedioPago)&& is_array($MedioPago) ? $MedioPago[0] : $MedioPago,
                                                            "CodigoMoneda"=>isset($CodigoMoneda)?$CodigoMoneda:"CRC",
                                                            "TipoCambio"=>isset($TipoCambio)?$TipoCambio:'1',
                                                            "TotalServGravados" => isset($TotalServGravados)?$TotalServGravados:0,
                                                            "TotalServExentos" =>isset($TotalServExentos)?$TotalServExentos:0 ,
                                                            "TotalMercanciasGravadas" => isset($TotalMercanciasGravadas)?$TotalMercanciasGravadas:0,
                                                            "TotalMercanciasExentas" => isset($TotalMercanciasExentas)?$TotalMercanciasExentas:0,
                                                            "TotalGravado" => isset($TotalGravado)?$TotalGravado:0,
                                                            "TotalExento" => isset($TotalExento)?$TotalExento:0,
                                                            "TotalVenta" => isset($TotalVenta)?$TotalVenta:0,
                                                            "TotalVentaNeta" =>isset($TotalVentaNeta)?$TotalVentaNeta:0,
                                                            "MontoTotalImpuesto" => isset($TotalImpuesto)?$TotalImpuesto:0,
                                                            "TotalFactura" => $TotalComprobante,
                                                            "xml_compra" => $adjunto,
                                                            "store_id" => $this->session->userdata('store_id'),

                                                        );
                                                        
                                                        $suppliers['name'] = $NombreE;
                                                        $suppliers['cf1'] = $TipodocEmisor;
                                                        $suppliers['cf2'] = $NumerocedulaE;
                                                        $suppliers['phone'] = isset($NumTelefonoE) ? $NumTelefonoE : '';
                                                        $suppliers['email'] = isset($CorreoElectronicoE) ? $CorreoElectronicoE : '';
                                                        if (isset($documento->DetalleServicio->LineaDetalle[0])){
                                                            $items_invoice = $documento->DetalleServicio->LineaDetalle;
                                                        }else{
                                                            $items_invoice = $documento->DetalleServicio;
                                                        }
                                                        $x = 0;
                                                        foreach ($items_invoice as $items) {
                                                            $itms = $items;
                                                            if(isset($itms['Codigo'][0]['Codigo'])){
                                                                $item_compra[$x]['code'] = $itms->Codigo[0]->Codigo;
                                                            }else{
                                                                $item_compra[$x]['code'] = isset($itms->Codigo->Codigo) ? $itms->Codigo->Codigo:0;
                                                            }
                                                            $item_compra[$x]['clave'] = $Clavee;
                                                            $item_compra[$x]['consecutivo'] = $NumeroConsecutivo;
                                                            $item_compra[$x]['name'] = $itms->Detalle;
                                                            $item_compra[$x]['quantity'] = $itms->Cantidad;
                                                            $itms['Cantidad'] = str_replace('.', ',',$itms->Cantidad);
                                                            $item_compra[$x]['cost'] = $itms->MontoTotalLinea /  $this->tofloat($itms->Cantidad);
                                                            $item_compra[$x]['type'] = $itms->UnidadMedida == 'Sp' ? 'service' : 'standard';
                                                            $item_compra[$x]['unit_of_measurement'] = $itms->UnidadMedida;
                                                            $item_compra[$x]['precio_unitario'] = $itms->PrecioUnitario;
                                                            $item_compra[$x]['tarifa_impuesto'] = isset($itms->Impuesto->Tarifa) ? $itms->Impuesto->Tarifa : 0;
                                                            $item_compra[$x]['monto_impuesto'] = isset($itms->Impuesto->Monto) ? $itms->Impuesto->Monto : 0;
                                                            $item_compra[$x]['monto_descuento'] = isset($itms->MontoDescuento) ? $itms->MontoDescuento : 0;
                                                            $item_compra[$x]['SubTotal'] = $itms->SubTotal;
                                                            $item_compra[$x]['MontoTotalLinea'] = $itms->MontoTotalLinea;
                                                            $x = $x + 1;
                                                        }
                                                        $this->load->model('hacienda_model');
                
                                                        if ($this->hacienda_model->getHaciendaDocByClave($compra['ClaveDocEmisor'])) {
                                                            echo '<li style="word-wrap: break-word; color:#ff7800;">El documento (' . $i . ') ya se encuentra cargado en el sistema';
                                                            continue;
                                                        }
                                                        
                                                        if ($this->hacienda_model->setDocumento($compra, $suppliers, $item_compra)) {
                                                            echo '<li  style="word-wrap: break-word; color:#009688;>Documento (' . $i . ') cargado correctamente';
                                                            continue;
                                                        } else {
                                                            echo '<li  style="word-wrap: break-word; color:red;">Hubo un error al guardar el documento (' . $i . ') intentelo nuevamente, si el error persiste comuniquese con el administrador';
                                                            continue;
                                                        }
                                                        echo '<br>';
                                                        echo ' REGISTRO: ' . $email . ' archivo numero ' . $i;

                                                        $banregisto = 'SI HUBO REGISTRO';
                                                        /*}
                                                        else
                                                        {
                                                        echo 'ERROR EN EL XML NO HAY ETIQUETA DE CIERRE DE DOCUMENTO ' .$consultipodoc;
                                                        }*/

                                                    }//fin de no existe    
                                                    else {
                                                        echo '<br>';
                                                        echo ' Email ' . $email . ' Ya existe archivo numero ' . $i;
                                                    }
                                                }//fin del if que si es factura
                                                else {
                                                    echo '<br>';
                                                    echo ' Email ' . $email . ' No es factura archivo numero ' . $i;
                                                }
                                                //return $nombreFichero;  
                                                //unlink($dirfichero.$nombreFichero.$exten);
                                            }
                                        }
                                    } // Fin de nombre fichero
                                }
                            }//fin del for

                            //if ($banregisto != '' && $typePDF == 'SI') {

                            //    $dirficheroPDFFinal = $dirficheroPDF . $NumerocedulaR . '/' . $NumerocedulaE . '/';

                            //    $carpeta = $dirficheroPDFFinal;
                            //    if (!file_exists($carpeta)) {
                            //        mkdir($carpeta, 0777, true);
                            //    }

                            //    $gestor = fopen($dirficheroPDFFinal . $nombreFicheroPDF, 'w');
                            //    fwrite($gestor, $adjuntoPDF);
                            //    fclose($gestor);

                             //   \DB::table('documentoshacienda')
                            //            ->where('Clave', $Clavee)
                            //            ->update(array(
                            //                'pdf' => '1',
                             //               'namepdf' => $nombreFicheroPDF));

                            //    echo '<br>';
                            //    echo ' Email ' . $email . ' Registro de PDF archivo numero ' . $i;
                            //}
                        }//fin del archivo adjunto     
                        else
                            echo 'NO SE ENCONTRARON ARCHIVOS ADJUNTOS ';
                    } // Fin de si coincide el asunto            
                } // Fin de foreach email
            } // Fin de si hay emails
            
            //return false; // SÃ³lo sino encontrÃ³ ficheros
        } else
            echo 'no conecto';

        imap_close($inbox);
        }//fin del for de carpetas
        echo '<br>';
        echo 'DE ' . $canbus . ' SE ENCONTRARON ' . $siesfactura . ' FACTURAS';

        //  return Redirect::to('documentoshacienda')->with('messagetext', '<p class="alert alert-success">' . \Lang::get('core.note_success') . '</p>')->with('msgstatus', 'success');
    }

    public function getNoXML()
    {
        $SalesID = $this->hacienda_model->getNoXML();
        if ($SalesID) {

            foreach ($SalesID as $si) {
                $sale_id = $si->id;
                $duplicado = $this->hacienda_model->getInvoice($sale_id);
                if (!$duplicado) {
                    $data = $this->pos_model->getSaleByID($sale_id);
                    $products = $this->pos_model->getAllSaleItems($sale_id);
                    $payments = $this->pos_model->getAllSalePayments($sale_id);
                    $this->load->library('firmar', NULL, 'firmar');
                    $this->load->library('Crearxml', NULL, 'Crearxml');
                    $facturadigital = $this->Crearxml->getInvoice((array) $data, $array = json_decode(json_encode($products), true), (array) $payments[0], null);
                    if ($facturadigital != null) {
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

                        $facturadigital['sale_id'] = $sale_id;
                        $facturadigital['tipo_doc'] = $data->tipo_doc;

                        $this->session->unset_userdata("last_sale_id");
                        $this->session->set_userdata("last_sale_id", $sale_id);
                        $this->session->userdata();
                        $this->hacienda_model->insertxml($facturadigital);
                    }
                }
            }
            $SalesIDsin = $this->hacienda_model->getsinXML();
            if ($SalesIDsin) {

                foreach ($SalesIDsin as $si) {
                    $sale_id = $si->id;
                    $duplicado = $this->hacienda_model->getInvoice($sale_id);
                    if (!$duplicado) {
                        $data = $this->pos_model->getSaleByID($sale_id);
                        $products = $this->pos_model->getAllSaleItems($sale_id);
                        $payments = $this->pos_model->getAllSalePayments($sale_id);
                        $this->load->library('firmar', NULL, 'firmar');
                        $this->load->library('Crearxml', NULL, 'Crearxml');

                        $facturadigital = $this->Crearxml->getInvoice((array) $data, $array = json_decode(json_encode($products), true), (array) $payments[0], null);
                        if ($facturadigital != null) {
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

                            $facturadigital['sale_id'] = $sale_id;
                            $facturadigital['tipo_doc'] = $data->tipo_doc;

                            $this->session->unset_userdata("last_sale_id");
                            $this->session->set_userdata("last_sale_id", $sale_id);
                            $this->session->userdata();
                            $this->hacienda_model->insertxml($facturadigital);
                        }
                    }
                }
            }
        }
    }

    public function getNoXMLFec()
    {
        $SalesID = $this->hacienda_model->getNoXMLFec();
        if ($SalesID) {
            foreach ($SalesID as $si) {
                $sale_id = $si->id;
                $duplicado = $this->hacienda_model->getFEC($sale_id);
                if (!$duplicado) {
                    $data = $this->pos_model->getFecByID($sale_id);
                    $products = $this->pos_model->getAllFecItems($sale_id);
                    $payments = $this->pos_model->getAllFecPayments($sale_id);
                    $this->load->library('firmar', NULL, 'firmar');
                    $this->load->library('Crearxml', NULL, 'Crearxml');

                    $facturadigital = $this->Crearxml->getFEC((array) $data, $array = json_decode(json_encode($products), true), (array) $payments[0], null);
                    if ($facturadigital != null) {
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

                        $facturadigital['sale_id'] = $sale_id;
                        $facturadigital['tipo_doc'] = $data->tipo_doc;

                        $this->session->unset_userdata("last_fec_id");
                        $this->session->set_userdata("last_fec_id", $sale_id);
                        $this->session->userdata();
                        $this->hacienda_model->insertxmlfec($facturadigital);
                    }
                }
            }
        }
        $SalesIDsin = $this->hacienda_model->getsinXMLFec();
        if ($SalesIDsin) {

            foreach ($SalesIDsin as $si) {
                $sale_id = $si->id;
                $duplicado = $this->hacienda_model->getFEC($sale_id);
                if (!$duplicado) {
                    $data = $this->pos_model->getFecByID($sale_id);
                    $products = $this->pos_model->getAllFecItems($sale_id);
                    $payments = $this->pos_model->getAllFecPayments($sale_id);
                    $this->load->library('firmar', NULL, 'firmar');
                    $this->load->library('Crearxml', NULL, 'Crearxml');

                    $facturadigital = $this->Crearxml->getFEC((array) $data, $array = json_decode(json_encode($products), true), (array) $payments[0], null);
                    if ($facturadigital != null) {
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

                        $facturadigital['sale_id'] = $sale_id;
                        $facturadigital['tipo_doc'] = $data->tipo_doc;

                        $this->session->unset_userdata("last_fec_id");
                        $this->session->set_userdata("last_fec_id", $sale_id);
                        $this->session->userdata();
                        $this->hacienda_model->insertxmlfec($facturadigital);
                    }
                }
            }
        }
    }

    public function getNoXMLCn()
    {
        $SalesID = $this->hacienda_model->getNoXMLCn();
        if ($SalesID) {

            foreach ($SalesID as $si) {
                $sale_id = $si->sale_id;
                $id = $si->id;
                $invoice = $this->hacienda_model->getInvoice($sale_id);
                $data = $this->pos_model->getCreditNoteByID($id);
                $products = $this->pos_model->getAllCreditNotesItems($id);
                $otrostextos = $this->pos_model->getCreditnoteOtrosTextos($id);
                $this->load->library('firmar', NULL, 'firmar');
                $this->load->library('Crearxml', NULL, 'Crearxml');
                $NotaCreditodigital = $this->Crearxml->getNotaCredito((array) $data, $array = json_decode(json_encode($products), true), $invoice, (array)$otrostextos);
                if ($NotaCreditodigital != null) {
                    $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
                    $firmado = false;
                    if (file_exists($certificado)) {
                        try {
                            $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $NotaCreditodigital["xml"]);
                        } catch (Exception $e) {
                            $firmado = false;
                        }
                    }

                    if ($firmado) {
                        $NotaCreditodigital['xml_sign'] = $firmado;
                    }

                    $NotaCreditodigital['id_cn'] = $id;

                    $this->session->unset_userdata("last_cn_id");
                    $this->session->set_userdata("last_cn_id", $id);
                    $this->session->userdata();
                    $this->hacienda_model->insertxmlCN($NotaCreditodigital);
                }
                
            }
            $SalesIDsin = $this->hacienda_model->getsinXMLCn();
            if ($SalesIDsin) {

                foreach ($SalesIDsin as $si) {
                    $sale_id = $si->sale_id;
                    $id = $si->id;
                    $invoice = $this->hacienda_model->getInvoice($sale_id);
                    $data = $this->pos_model->getCreditNoteByID($id);
                    $products = $this->pos_model->getAllCreditNotesItems($id);
                    $otrostextos = $this->pos_model->getCreditnoteOtrosTextos($id);
                    $this->load->library('firmar', NULL, 'firmar');
                    $this->load->library('Crearxml', NULL, 'Crearxml');
                    $NotaCreditodigital = $this->Crearxml->getNotaCredito((array) $data, $array = json_decode(json_encode($products), true), $invoice, (array)$otrostextos);
                    if ($NotaCreditodigital != null) {
                        $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
                        $firmado = false;
                        if (file_exists($certificado)) {
                            try {
                                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $NotaCreditodigital["xml"]);
                            } catch (Exception $e) {
                                $firmado = false;
                            }
                        }
    
                        if ($firmado) {
                            $NotaCreditodigital['xml_sign'] = $firmado;
                        }
    
                        $NotaCreditodigital['id_cn'] = $id;
    
                        $this->session->unset_userdata("last_cn_id");
                        $this->session->set_userdata("last_cn_id", $id);
                        $this->session->userdata();
                        $this->hacienda_model->insertxmlCN($NotaCreditodigital);
                    }
                }
            }
        }
    }

    public function getCodeDisk()
    {
        $q = $this->db->select('diskdrive_code')->get('settings');
        if ($q->num_rows() > 0) {
            echo json_encode($q->result_array());
        }
        echo FALSE;
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

    public function generarREP($payment_id = null)
    {
        if (!$payment_id) {
            $payment_id = $this->input->get('payment_id');
        }
        if (!$payment_id) {
            show_404();
        }

        $this->load->model('sales_model');
        $payment = $this->sales_model->getPaymentByID($payment_id);
        if (!$payment) {
            $this->session->set_flashdata('error', 'Pago no encontrado.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        $sale = $this->sales_model->getSaleByID($payment->sale_id);
        if (!$sale) {
            $this->session->set_flashdata('error', 'Venta no encontrada.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        $referencia = $this->hacienda_model->getInvoice($payment->sale_id);
        if (!$referencia || $referencia->estatus_hacienda !== 'aceptado') {
            $this->session->set_flashdata('error', 'La factura original aún no está aceptada por Hacienda.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        if ($this->hacienda_model->getREP($payment_id)) {
            $this->session->set_flashdata('error', 'Ya existe un REP para este pago.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        $this->load->library('firmar', NULL, 'firmar');
        $this->load->library('Crearxml', NULL, 'Crearxml');

        $REPdigital = $this->Crearxml->getREP(
            (array) $payment,
            (array) $sale,
            $referencia
        );

        if (!$REPdigital) {
            $this->session->set_flashdata('error', 'No se pudo generar el XML del REP.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
        $firmado = false;
        if (file_exists($certificado)) {
            try {
                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $REPdigital['xml'], '09');
            } catch (Exception $e) {
                $firmado = false;
            }
        }

        $dataHacienda = [
            'payment_id'       => $payment_id,
            'sale_id'          => $payment->sale_id,
            'clave'            => $REPdigital['clave'],
            'consecutivo'      => $REPdigital['consecutivo'],
            'fecha_emision'    => $REPdigital['fecha_emision'],
            'tipo_doc'         => '09',
            'estatus_hacienda' => 'procesando',
            'xml'              => $REPdigital['xml'],
            'xml_sign'         => $firmado ? base64_decode($firmado) : null,
            'mail'             => 0,
        ];

        $this->hacienda_model->insertxmlREP($dataHacienda);

        if ($firmado) {
            $this->load->library('Apiclient', NULL, 'ApiClient');
            $this->ApiClient->getTokenH();

            $resultado = $this->ApiClient->send_invoice([
                'xml'           => $REPdigital['xml'],
                'xml_sign'      => $dataHacienda['xml_sign'],
                'clave'         => $REPdigital['clave'],
                'consecutivo'   => $REPdigital['consecutivo'],
                'fecha_emision' => $REPdigital['fecha_emision'],
            ]);

            if ($resultado && is_array($resultado)) {
                $mensajeHacienda = '';
                if (isset($resultado['mensajeHacienda']->respuestaxml)) {
                    $mensajeHacienda = base64_decode($resultado['mensajeHacienda']->respuestaxml);
                }
                $indestado = isset($resultado['mensajeHacienda']->indestado)
                    ? $resultado['mensajeHacienda']->indestado
                    : 'procesando';

                $this->hacienda_model->insertHaciendaREP([
                    'xml_sign'         => isset($resultado['xml_firmado']) ? base64_decode($resultado['xml_firmado']) : $dataHacienda['xml_sign'],
                    'xml_hacienda'     => $mensajeHacienda,
                    'estatus_hacienda' => $indestado,
                ], $REPdigital['clave']);
            }
        }

        $this->session->set_flashdata('message', 'REP generado y enviado a Hacienda.');
        redirect($_SERVER['HTTP_REFERER']);
    }
}
