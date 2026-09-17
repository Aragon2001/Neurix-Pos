<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
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

        // Generar el comprobante de una venta puede fallar por sus propios datos
        // (una linea sin tarifa, un producto borrado). Sin este aislamiento esa
        // venta corta la peticion y ningun comprobante listo llega a Hacienda.
        try {
            $this->getNoXML();
        } catch (\Throwable $e) {
            log_message('error', 'Shacienda: getNoXML fallo, se sigue con el envio: ' . $e->getMessage());
        }

        if ($pendientes) {
            if ($abrirToken) {

                $token_data  = $this->ApiClient->getTokenH();
                $ExpireToken = date('Y-m-d H:i:s', time() + (int)($token_data->expires_in ?? 3600));

                $abrirToken = false;
            }
            foreach ($pendientes as $row) {
                $date_act = date('Y-m-d H:i:s', strtotime('+15 seconds ', strtotime(date('Y-m-d H:i:s'))));

                if ($ExpireToken < $date_act) {
                    $this->ApiClient->refreshTokenH();
                }

                // Sin firma todavia: se firma en el acto para que el comprobante
                // salga en esta misma tanda y no haya que esperar a la siguiente.
                if (!$row->xml_sign) {
                    $this->load->library('firmar', NULL, 'firmar');
                    $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';

                    if (!file_exists($certificado)) {
                        log_message('error', 'Shacienda: no existe el certificado ' . $certificado . ', el comprobante ' . $row->clave . ' no se puede firmar.');
                        continue;
                    }

                    try {
                        $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $row->xml);
                    } catch (\Throwable $e) {
                        $firmado = false;
                        log_message('error', 'Shacienda: fallo la firma de ' . $row->clave . ': ' . $e->getMessage());
                    }

                    if (!$firmado) {
                        continue;
                    }

                    // insertxml() solo sirve para altas: la fila ya existe y hay que actualizarla.
                    $this->hacienda_model->insertHacienda(array('xml_sign' => $firmado), $row->clave);
                    $row->xml_sign = $firmado;
                }

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
            $cierraToken = true;
        }


        $pendientesRD = $this->hacienda_model->getPendientesRD();

        if ($pendientesRD) {
            if ($abrirToken) {
                $token_data  = $this->ApiClient->getTokenH();
                $ExpireToken = date('Y-m-d H:i:s', time() + (int)($token_data->expires_in ?? 3600));

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
                $token_data  = $this->ApiClient->getTokenH();
                $ExpireToken = date('Y-m-d H:i:s', time() + (int)($token_data->expires_in ?? 3600));

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
                            } catch (\Throwable $e) {
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

                $token_data  = $this->ApiClient->getTokenH();
                $ExpireToken = date('Y-m-d H:i:s', time() + (int)($token_data->expires_in ?? 3600));

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
                            } catch (\Throwable $e) {
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

        // --- REP (Recibo Electrónico de Pago, tipo 09) ---
        $pendientesREP = $this->hacienda_model->getPendientesREP();
        if ($pendientesREP) {
            if ($abrirToken) {
                $token_data  = $this->ApiClient->getTokenH();
                $ExpireToken = date('Y-m-d H:i:s', time() + (int)($token_data->expires_in ?? 3600));
                $abrirToken = false;
            }
            foreach ($pendientesREP as $row) {
                $date_act = date('Y-m-d H:i:s', strtotime('+15 seconds ', strtotime(date('Y-m-d H:i:s'))));
                if ($ExpireToken < $date_act) {
                    $this->ApiClient->refreshTokenH();
                }

                if ($row->xml_sign) {
                    $MH = $this->ApiClient->send_invoice([
                        'xml'           => $row->xml,
                        'xml_sign'      => $row->xml_sign,
                        'clave'         => $row->clave,
                        'consecutivo'   => $row->consecutivo,
                        'fecha_emision' => $row->fecha_emision,
                    ]);
                    $mensajeHacienda = '';
                    $indestado = 'Sin Estado';
                    if (isset($MH['mensajeHacienda']->respuestaxml)) {
                        $mensajeHacienda = base64_decode($MH['mensajeHacienda']->respuestaxml);
                    }
                    if (isset($MH['mensajeHacienda']->indestado)) {
                        $indestado = $MH['mensajeHacienda']->indestado;
                    }
                    $this->hacienda_model->insertHaciendaREP([
                        'xml_sign'         => isset($MH['xml_firmado']) ? base64_decode($MH['xml_firmado']) : $row->xml_sign,
                        'xml_hacienda'     => $mensajeHacienda,
                        'estatus_hacienda' => $indestado,
                    ], $row->clave);
                } else {
                    $this->load->library('firmar', NULL, 'firmar');
                    $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
                    if (file_exists($certificado)) {
                        try {
                            $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $row->xml, '09');
                        } catch (Exception $e) {
                            $firmado = false;
                        }
                        if ($firmado) {
                            $this->hacienda_model->insertHaciendaREP(['xml_sign' => $firmado], $row->clave);
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
                $this->data['invoiceqr'] = isset($this->data['hacienda']->consecutivo)
                    ? $this->tec->qrcode($this->data['hacienda']->consecutivo, 4)
                    : '';

                $receipt = $this->load->view($this->theme . 'pos/viewnc', $this->data, TRUE);
                $message = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
                $subject = lang('email_subject') . ' - ' . $this->Settings->site_name;


                $this->data['tipo_documento'] = "Nota de credito Electronica";
                // El PDF adjunto reutiliza la misma vista con estilos que ve el
                // usuario; pos/invoice quedo sin hoja de estilo y salia sin formato.
                $html = $this->tec->pdf_html($receipt);



                $xml_sign = $this->hacienda_model->xmlFirmadoCN($id_cn)->xml_sign;
                $xml_hacienda = $this->hacienda_model->xmlMensajeCN($id_cn)->xml_hacienda;
                $clave = $this->hacienda_model->getClaveCN($id_cn)->clave;

                $attach = array();
                $mpdf = new \Mpdf\Mpdf(array(
                    'tempDir'       => sys_get_temp_dir(),
            'CSSselectMedia' => 'screen',
                    'format'        => 'A4',
                    'margin_left'   => 12,
                    'margin_right'  => 12,
                    'margin_top'    => 12,
                    'margin_bottom' => 12,
                ));
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
            $this->load->library('correo_comprobante');
            foreach ($noenviados as $row) {
                $this->correo_comprobante->enviarSiAceptada($row->sale_id);
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


        $noenviadosREP = $this->hacienda_model->getnoEnviadosREP();
        if ($noenviadosREP) {
            foreach ($noenviadosREP as $row) {
                $payment_id = $row->payment_id;
                $rep = $this->hacienda_model->getREP($payment_id);
                if (!$rep) continue;

                $xml_sign    = $this->hacienda_model->xmlFirmadoREP($payment_id)->xml_sign ?? null;
                $xml_mensaje = $this->hacienda_model->xmlMensajeREP($payment_id)->xml_hacienda ?? null;

                $subject = lang('email_subject') . ' - REP - ' . $this->Settings->site_name;
                $message = '<p>Adjunto encontrará el Recibo Electrónico de Pago (REP) #' . $rep->consecutivo . ' generado por ' . $this->Settings->nombre_emisor . '.</p>';

                $attach = [];
                if ($xml_sign)    $attach['T9_' . $rep->clave] = $xml_sign;
                if ($xml_mensaje) $attach['M9_' . $rep->clave] = $xml_mensaje;

                $sale = $this->pos_model->getSaleByID($rep->sale_id);
                $customer = $sale ? $this->pos_model->getCustomerByID($sale->customer_id) : null;
                $to = $customer ? $customer->email : null;

                if ($to) {
                    $this->load->library('Swiftmailer', NULL, 'Swiftmailer');
                    if ($this->Swiftmailer->send_email($to, $subject, $message, null, null, $attach)) {
                        echo " REP enviado a: " . $to . ",";
                        $this->hacienda_model->MarcaEnviadoREP($payment_id, '1');
                    } else {
                        $this->hacienda_model->MarcaEnviadoREP($payment_id, '0');
                    }
                } else {
                    $this->hacienda_model->MarcaEnviadoREP($payment_id, '1');
                }
            }
        }

        if ($cierraToken) {
            $this->ApiClient->CloseTokenH();
        }
        if ($this->Settings->mail_client_enabled == '1') {
            $this->load->library('lectorcompras');
            $this->lectorcompras->importar();
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

    public function getNoXML()
    {
        $SalesID = $this->hacienda_model->getNoXML();
        if ($SalesID) {

            foreach ($SalesID as $si) {
                $sale_id = $si->id;
                $duplicado = $this->hacienda_model->getInvoice($sale_id);
                if (!$duplicado) {
                    try {
                    $data = $this->pos_model->getSaleByID($sale_id);
                    $products = $this->pos_model->getAllSaleItems($sale_id);
                    $payments = $this->pos_model->getAllSalePayments($sale_id);
                    $this->load->library('firmar', NULL, 'firmar');
                    $this->load->library('Crearxml', NULL, 'Crearxml');
                    $facturadigital = $this->Crearxml->getInvoice((array) $data, json_decode(json_encode($products), true), json_decode(json_encode($payments), true), null);
                    if ($facturadigital != null) {
                        $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
                        if (file_exists($certificado)) {
                            try {
                                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $facturadigital["xml"]);
                            } catch (\Throwable $e) {
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
                    } catch (\Throwable $e) {
                        log_message('error', 'Shacienda: no se pudo generar el comprobante de la venta ' . $sale_id . ': ' . $e->getMessage());
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

                        $facturadigital = $this->Crearxml->getInvoice((array) $data, json_decode(json_encode($products), true), json_decode(json_encode($payments), true), null);
                        if ($facturadigital != null) {
                            $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';

                            if (file_exists($certificado)) {
                                try {
                                    $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $facturadigital["xml"]);
                                } catch (\Throwable $e) {
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
                            } catch (\Throwable $e) {
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
                            } catch (\Throwable $e) {
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
                            } catch (\Throwable $e) {
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

    public function generarND($nd_id = null)
    {
        if (!$nd_id) $nd_id = $this->input->get('nd_id');
        if (!$nd_id) { show_404(); }

        $this->load->model('pos_model');
        $nd = $this->pos_model->getDebitNoteByID($nd_id);
        if (!$nd) {
            $this->session->set_flashdata('error', 'Nota de Débito no encontrada.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        if ($this->hacienda_model->getND($nd_id)) {
            $this->session->set_flashdata('error', 'Ya existe un XML para esta Nota de Débito.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        $referencia = $this->hacienda_model->getInvoice($nd->sale_id);
        if (!$referencia || $referencia->estatus_hacienda !== 'aceptado') {
            $this->session->set_flashdata('error', 'La factura original aún no está aceptada por Hacienda.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        $items = $this->pos_model->getAllDebitNotesItems($nd_id);
        $itemsArr = json_decode(json_encode($items), true);

        $this->load->library('firmar', NULL, 'firmar');
        $this->load->library('Crearxml', NULL, 'Crearxml');

        $NDdigital = $this->Crearxml->getNotaDebito((array) $nd, $itemsArr, $referencia, array());
        if (!$NDdigital) {
            $this->session->set_flashdata('error', 'No se pudo generar el XML de la Nota de Débito.');
            redirect($_SERVER['HTTP_REFERER']);
        }

        $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
        $firmado = false;
        if (file_exists($certificado)) {
            try {
                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $NDdigital['xml'], '02');
            } catch (Exception $e) {
                $firmado = false;
            }
        }

        $dataHacienda = [
            'nd_id'            => $nd_id,
            'sale_id'          => $nd->sale_id,
            'clave'            => $NDdigital['clave'],
            'consecutivo'      => $NDdigital['consecutivo'],
            'fecha_emision'    => $NDdigital['fecha_emision'],
            'estatus_hacienda' => 'procesando',
            'xml'              => $NDdigital['xml'],
            'xml_sign'         => $firmado ? base64_decode($firmado) : null,
            'mail'             => 0,
        ];

        $this->hacienda_model->insertxmlND($dataHacienda);

        if ($firmado) {
            $this->load->library('Apiclient', NULL, 'ApiClient');
            $this->ApiClient->getTokenH();

            $resultado = $this->ApiClient->send_invoice([
                'xml'           => $NDdigital['xml'],
                'xml_sign'      => $dataHacienda['xml_sign'],
                'clave'         => $NDdigital['clave'],
                'consecutivo'   => $NDdigital['consecutivo'],
                'fecha_emision' => $NDdigital['fecha_emision'],
            ]);

            if ($resultado && is_array($resultado)) {
                $mensajeHacienda = '';
                if (isset($resultado['mensajeHacienda']->respuestaxml)) {
                    $mensajeHacienda = base64_decode($resultado['mensajeHacienda']->respuestaxml);
                }
                $indestado = isset($resultado['mensajeHacienda']->indestado) ? $resultado['mensajeHacienda']->indestado : 'procesando';
                $this->hacienda_model->insertHaciendaND([
                    'xml_sign'         => isset($resultado['xml_firmado']) ? base64_decode($resultado['xml_firmado']) : $dataHacienda['xml_sign'],
                    'xml_hacienda'     => $mensajeHacienda,
                    'estatus_hacienda' => $indestado,
                ], $NDdigital['clave']);
            }
        }

        $this->session->set_flashdata('message', 'Nota de Débito generada y enviada a Hacienda.');
        redirect('debitnotes/viewnd/' . $nd_id);
    }
}
