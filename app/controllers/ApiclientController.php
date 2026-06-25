<?php namespace App\Http\Controllers;

use App\Models\Apiclient;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Http\Request;
use League\Flysystem\Exception;
use Validator, Input, Redirect;
use GuzzleHttp\Client;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class ApiclientController extends Controller
{

    protected $layout = "layouts.main";
    protected $data = array();
    public $module = 'apiclient';
    static $per_page = '10';

    private $accessToken;
    private $refreshToken;
    private $baseUrl;
    private $authUrl;
    private $clientId;
    private $client;
    private $username;
    private $password;
    private $ambiente;

    public function __construct($type = AMBIENTE)
    {
        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();
        parent::__construct();


        $this->ambiente = $type;
        $this->model = new Apiclient();
        $this->info = $this->model->makeInfo($this->module);
        $this->access = array();

        $this->data = array_merge(array(
            'pageTitle' => $this->info['title'],
            'pageNote' => $this->info['note'],
            'pageModule' => 'apiclient',
            'return' => self::returnUrl()

        ), $this->data);

        switch ($type) {
            case 'prod':
                $this->baseUrlComprobante = 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/comprobantes';
                $this->baseUrlRecepcion = 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/recepcion';
                $this->authUrl = 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token';
                $this->clientId = 'api-prod';
                $this->username = USER_TOKEN_HACIENDA_PROD;
                $this->password = PASS_TOKEN_HACIENDA_PROD;
                break;
            case 'test':
                $this->baseUrlComprobante = 'https://api.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/comprobantes';
                $this->baseUrlRecepcion = 'https://api.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/recepcion';
                $this->authUrl = 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token';
                $this->clientId = 'api-stag';
                $this->username = USER_TOKEN_HACIENDA_TEST;
                $this->password = PASS_TOKEN_HACIENDA_TEST;
                break;
        }
        $this->accessToken = NULL;
        $this->refreshToken = NULL;
        $this->client = new Client([
            'timeout' => 40.0,
        ]);

        $result = self::authenticateROPC($this->username, $this->password, $this->authUrl);

        if (!empty($result->access_token) && !empty($result->refresh_token)) {
            $this->setAccessToken($result->access_token);
            $this->setRefreshToken($result->refresh_token);
        }

    }


    /**
     * Auth and get access token.
     */
    public function authenticateROPC($username, $password, $authUrl)
    {
        $client_secret = '';
        $scope = '';
        $grant_type = 'password';
        $body = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'username' => trim($username),
            'password' => trim($password),
            'client_secret' => trim($client_secret),
            'scope' => $scope,
            'grant_type' => $grant_type,
            'authorization_grants' => $grant_type,
        ];
        try{
		$response = $this->client->request('POST', $authUrl, ['form_params' => $body]);
        $body = $response->getBody();
        $content = $body->getContents();
		 }catch(\Exception $e){
			return false;
		}
        return $result = json_decode($content);
    }

    public function setAccessToken($access_token)
    {
        $this->accessToken = $access_token;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setRefreshToken($refresh_token)
    {
        $this->refreshToken = $refresh_token;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }


    //send invoice xml to Hacienda API
    public function send_invoice($invoice, $parametros)
    {
        $data = [
            'clave' => $parametros['clave'],
            'fecha' => $parametros['fecha'],
            'emisor' => [
				'tipoIdentificacion' => TIPO_PERSONA,
                'numeroIdentificacion' => trim(str_replace("-", "", ID_COMPANY_CONF))
            ],
            'comprobanteXml' => $invoice
        ];
        $resultado['comprobante'] = $this->getComprobantes($parametros['clave']);
		
        if (!$resultado['comprobante']) {
            $this->postHacienda($data);
            $resultado['comprobante'] = $this->getComprobantes($parametros['clave']);
        }
        $resultado['mensajeHacienda'] = $this->getMensajeHacienda($parametros['clave']);

        return $resultado;
    }

    public function transaccion($transaccion)
    {
        $handle = fopen("file.txt", "w");
        fwrite($handle, $transaccion);
        fclose($handle);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename('file.txt'));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize('file.txt'));
        readfile('file.txt');
    }

    public function postHacienda($data)
    {
        $messageError = "";
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrlRecepcion,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLINFO_HEADER_OUT => true,
            CURLOPT_POSTFIELDS => \json_encode($data),
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->getAccessToken(),
                "content-type: application/json charset=utf-8"
            ),
        ));
		try{
			$r = curl_exec($curl);
			$infoReponse = curl_getinfo($curl);
        }catch(\Exception $e){
		}
		curl_close($curl);;
    }

    public function getMensajeHacienda($key)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrlRecepcion . '/' . $key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->getAccessToken(),
            ),
        ));

		try{
        $response2 = curl_exec($curl);
        $response2 = str_replace('respuesta-xml', 'respuestaxml', $response2);
        $response2 = str_replace('ind-estado', 'indestado', $response2);
        dd("Entro");
        }catch(\Exception $e){
			return false;
		}
		
        return \json_decode($response2);
    }


    public function getComprobantes($key = null)
    {
        if ($key) {
            $url = $this->baseUrlComprobante . '/' . $key;
        } else {
            $url = $this->baseUrlComprobante;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->getAccessToken(),
            ),
        ));
		try{
			$response2 = curl_exec($curl);
        }catch(\Exception $e){
			return false;
		}
		return \json_decode($response2);
		
    }


    public function json_decode_nice($json, $assoc = FALSE)
    {
        $json = str_replace(array("\n", "\r"), "", $json);
        $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $json);
        $json = preg_replace('/(,)\s*}$/', '}', $json);
        return json_decode($json, $assoc);
    }

    public function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public function LimpiaString($string)
    {
        $string = str_replace(array("\n", "\r"), "", $string);
        $string = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $string);
        $string = preg_replace('/(,)\s*}$/', '}', $string);
        return $string;
    }

    public function getMsgXMLHacienda($xml)
    {
        $dom = new \DOMDocument;
        try {
            $dom->loadXML($xml);
        } catch (Exception $e) {
            return "";
        }

        $DetalleMensaje = $dom->getElementsByTagName('DetalleMensaje');
        foreach ($DetalleMensaje as $row) {
            $resultado['detalleMensaje'] = $this->LimpiaString($row->nodeValue);
        }

        $MensajeStatus = $dom->getElementsByTagName('Mensaje');
        foreach ($MensajeStatus as $row) {
            $resultado['StatusMensaje'] = $this->LimpiaString($row->nodeValue);
        }

        return $resultado;
    }

    public static function Firmarhacienda($idfactura, $tipo = "1", $motivo = null)
    {
        $consecutivo = "";
        $clave = "";
        $xmlHacienda = "";
        if ($tipo == "1") {
            $validXMLs = \DB::table('sys_invoices')->where('id', $idfactura)->first();
            $validMsmHacienda = \DB::table('comprobanteelectronico')->where('n_factura', $idfactura)->first();
        } elseif ($tipo == "3") {
            $validXMLs = \DB::table('sys_nc')->where('id_nc', $idfactura)->get();
            $validMsmHacienda = \DB::table('comprobanteelectronico_nc')->where('id_nc', $idfactura)->first();
        }
        if ($validMsmHacienda) {
            if (!$validMsmHacienda->consecutivo && $validMsmHacienda->clave) {
                $consecutivo = substr($validMsmHacienda->clave, 21, 20);
                $clave = $validMsmHacienda->clave;
                if ($tipo == '1') {
                    \DB::table('comprobanteelectronico')->where('n_factura', $idfactura)->update(array('clave' => $validMsmHacienda->clave, 'consecutivo' => $consecutivo));
                } elseif ($tipo == '3') {
                    \DB::table('comprobanteelectronico_nc')->where('id_nc', $idfactura)->update(array('clave' => $validMsmHacienda->clave, 'consecutivo' => $consecutivo));
                }
            } else {
                $clave = $validMsmHacienda->clave;
                $consecutivo = $validMsmHacienda->consecutivo;
            }
        }
        if ($validMsmHacienda->respuestaxml) {
            if (base64_decode($validMsmHacienda->respuestaxml)) {
                if ($tipo == "1") {
                    \DB::table('comprobanteelectronico')->where('n_factura', $idfactura)->update(array('respuestaxml' => base64_decode($validMsmHacienda->respuestaxml)));
                    \DB::table('sys_invoices')->where('id', $idfactura)->update(array('XML_HACIENDA' => base64_decode($validMsmHacienda->respuestaxml)));
                } elseif ($tipo == "3") {
                    \DB::table('comprobanteelectronico_nc')->where('id_nc', $idfactura)->update(array('respuestaxml' => base64_decode($validMsmHacienda->respuestaxml)));
                    \DB::table('sys_nc')->where('id_nc', $idfactura)->update(array('XML_HACIENDA' => base64_decode($validMsmHacienda->respuestaxml)));
                }
                $xmlHacienda = base64_decode($validMsmHacienda->respuestaxml);
            } else {
                $xmlHacienda = $validMsmHacienda->respuestaxml;
            }
        }
        if (!$validXMLs->XML) {
            switch ($tipo) {
                case '1':
                    $invoice_param = \App\Http\Controllers\CrearxmlController::getInvoice($idfactura);
                    break;
                case '2':
                    $invoice_param = \App\Http\Controllers\CrearxmlController::getNotaDebito($idfactura);
                    break;
                case '3':
                    $invoice_param = \App\Http\Controllers\CrearxmlController::getNotaCredito($idfactura, $motivo);
                    break;
            }
        } else {
            if (!$clave || $consecutivo) {
                $doc = \simplexml_load_string($validXMLs->XML);
                $clave = (array)$doc->Clave;
                $clave = $clave[0];
                $consecutivo = substr($clave, 21, 20);
            }
            $invoice_param = array($validXMLs->XML, $clave, $consecutivo);
        }

$invoice_param = (array)$invoice_param ;
        if ($tipo == "1") {
            if ($validMsmHacienda) {
                \DB::table('comprobanteelectronico')->where('n_factura', $idfactura)->update(array('clave' => $invoice_param[1], 'consecutivo' => $invoice_param[2]));
            } else {
                \DB::table('comprobanteelectronico')->insert(array('n_factura' => $idfactura, 'clave' => $invoice_param[1], 'consecutivo' => $invoice_param[2]));
            }
            \DB::table('sys_invoices')->where('id', $idfactura)->update(array('XML' => $invoice_param[0]));
        } elseif ($tipo == "3") {
            if ($validMsmHacienda) {
                \DB::table('comprobanteelectronico_nc')->where('id_nc', $idfactura)->update(array('clave' => $invoice_param[1], 'consecutivo' => $invoice_param[2]));
            } else {
                \DB::table('comprobanteelectronico_nc')->insert(array('id_nc' => $idfactura, 'clave' => $invoice_param[1], 'consecutivo' => $invoice_param[2]));
            }
            \DB::table('sys_nc')->where('id_nc', $idfactura)->update(array('XML' => $invoice_param[0]));
        }

        $consecutivo = $invoice_param[2];

        if (!$validXMLs->XML_FIRMADO) {
			try{
				$client = new Client(['timeout' => 30.0,]);
				$body = ['username' => APISIGN_USER,
					'password' => APISIGN_USER_PASS,
					'grant_type' => password];
				$response = $client->request('POST', APISIGN_URL . "Token",
					['form_params' => $body,
						'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);

				$body = $response->getBody();
				$content = $body->getContents();
				$tokenSign = json_decode($content);

				$response = $client->request('POST', APISIGN_URL . "api/v1/signxml",
					['form_params' => ['documento' => base64_encode($invoice_param[0]),
						'cedula' => CARPETA_CERT,
						'clave' => CLAVE_CERT,
						'ambiente' => AMBIENTE],
						'headers' => ['Authorization' => 'bearer ' . $tokenSign->access_token]]);
				$body = $response->getBody();
				$content = $body->getContents();
				$respuesta = json_decode($content);
				$xmlFirmado = $respuesta->Data;

				if ($tipo == "1") {
					\DB::table('sys_invoices')->where('id', $idfactura)->update(array('XML_FIRMADO' => base64_decode($xmlFirmado)));
				} elseif ($tipo == "3") {
					\DB::table('sys_nc')->where('id_nc', $idfactura)->update(array('XML_FIRMADO' => base64_decode($xmlFirmado)));
				} elseif ($tipo == "5") {
					\DB::table('documentoshacienda')->where('id_documento', $idfactura)->update(array('xml_firmado' => base64_decode($xmlFirmado)));
				}
			}catch(\Exception $e)
			{
				$xmlFirmado = '';
			}
        } else {
            $xmlFirmado = base64_encode($validXMLs->XML_FIRMADO);
        }


        $result['xml_firmado'] = $xmlFirmado;
        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();
        $fecha = date('Y-m-d\TH:i:s');

        $paramsendinvoice = [
            'clave' => $invoice_param[1],
            'fecha' => $fecha,
            'emisor' => [
                'tipoIdentificacion' => TIPO_PERSONA,
                'numeroIdentificacion' => trim(str_replace("-", "", ID_COMPANY_CONF))
            ]
        ];


        $sendinvoice = new ApiclientController();
        $result = $sendinvoice->send_invoice($xmlFirmado, $paramsendinvoice);
        $MsgHacienda = $result['mensajeHacienda'];
        $comprobante = "";
        $respuestaxml = '';
        $detallemensaje = '';
        $indestado = @$MsgHacienda->indestado;
        $fecha = @$MsgHacienda->fecha;
        if ($MsgHacienda->respuestaxml) {
            if (base64_decode(@$MsgHacienda->respuestaxml)) {
                $respuestaxml = base64_decode(@$MsgHacienda->respuestaxml);
            }
            $detallemensaje = $sendinvoice->getMsgXMLHacienda(base64_decode($MsgHacienda->respuestaxml));
        }

        if ($result['comprobante']) {
            $comprobante = serialize($result['comprobante']);
        }

        switch (strtolower($MsgHacienda->indestado)) {
            case "error" :
                $status = 0;
                break;
            case "rechazado" :
                $status = 1;
                break;
            case "aceptado" :
                $status = 1;
                break;
            case "procesando" :
                $status = 0;
                break;
            default:
                $status = "0";
                break;
        }

        if ($tipo == "1") {
            \DB::table('sys_invoices')->where('id', $idfactura)->update(array('XML_HACIENDA' => $respuestaxml, 'comprobanteElectronico' => $comprobante, 'hacienda' => $status));
            \DB::table('comprobanteelectronico')->where('n_factura', $idfactura)->update(
                array('comprobante' => $comprobante,
                    'indestado' => $indestado,
                    'fecha' => $fecha,
                    'respuestaxml' => $respuestaxml,
                    'consecutivo' => $consecutivo,
                    'detalleMensaje' => serialize($detallemensaje)));
        } elseif ($tipo == "3") {
            \DB::table('sys_nc')->where('id_nc', $idfactura)->update(array('XML_HACIENDA' => $respuestaxml, 'hacienda' => $status));
            \DB::table('comprobanteelectronico_nc')->where('id_nc', $idfactura)->update(
                array('comprobante' => $comprobante,
                    'indestado' => $indestado,
                    'fecha' => $fecha,
                    'respuestaxml' => $respuestaxml,
                    'consecutivo' => $consecutivo,
                    'detalleMensaje' => serialize($detallemensaje)));
        }
        return $result;
    }


    public static function MensajeAprobacion($id)
    {

        $documento = \DB::table('documentoshacienda')->where('id_documento', $id)->first();
        if (!$documento->xml_hacienda) {
            $apiclient = new ApiclientController(AMBIENTE);

            if ($documento->xml_mensajereceptor and $documento->consecutivo) {
                $xml = $documento->xml_mensajereceptor;
                $consecutivo = $documento->consecutivo;
            } else {
                $param = \App\Http\Controllers\CrearxmlController::getMensajeReceptor($id);
                $xml = $param[0];
                $consecutivo = $param[1];
                \DB::table('documentoshacienda')->where('id_documento', $id)->update(array('xml_mensajereceptor' => $xml, 'consecutivo' => $consecutivo));
            }

            if ($documento->xml_firmado) {
                $xmlFirmado = base64_encode($documento->xml_firmado);
            } else {

                $client = new Client(['timeout' => 30.0,]);
                $body = ['username' => APISIGN_USER,
                    'password' => APISIGN_USER_PASS,
                    'grant_type' => 'password'];
                $response = $client->request('POST', APISIGN_URL . "Token",
                    ['form_params' => $body,
                        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);

                $body = $response->getBody();
                $content = $body->getContents();
                $tokenSign = json_decode($content);

                $response = $client->request('POST', APISIGN_URL . "api/v1/signxml",
                    ['form_params' => ['documento' => base64_encode($xml),
                        'cedula' => CARPETA_CERT,
                        'clave' => CLAVE_CERT,
                        'ambiente' => AMBIENTE],
                        'headers' => ['Authorization' => 'bearer ' . $tokenSign->access_token]]);
                $body = $response->getBody();
                $content = $body->getContents();
                $respuesta = json_decode($content);
                $xmlFirmado = $respuesta->Data;
                \DB::table('documentoshacienda')->where('id_documento', $id)->update(array('xml_firmado' => base64_decode($xmlFirmado)));
            }

            $parametros = array($xml, trim($documento->Clave), trim($consecutivo), $xmlFirmado);
            if ($xmlFirmado) {
                try {
                    $fecha = date('Y-m-d\TH:i:s');
                    $paramsendinvoice = [
                        'clave' => $documento->Clave,
                        'fecha' => $fecha,
                        'emisor' => [
                            'tipoIdentificacion' => $documento->tipo_doc_emisor,
                            'numeroIdentificacion' => trim(str_replace("-", "", $documento->NumeroCedulaEmisor))
                        ],
                        'receptor' => [
                            'tipoIdentificacion' => TIPO_PERSONA,
                            'numeroIdentificacion' => trim(str_replace("-", "", ID_COMPANY_CONF))
                        ],
                        "consecutivoReceptor" => trim($consecutivo),
                        'comprobanteXml' => $xmlFirmado
                    ];

                    /****************************************************
                     * Envio de Factura
                     */

                    $result['mensajeHacienda'] = $apiclient->getMensajeHacienda($documento->Clave.'-'.trim($consecutivo));
                    if (!$result['mensajeHacienda']) {
                        $apiclient->postHacienda($paramsendinvoice);
                        $result['mensajeHacienda'] = $apiclient->getMensajeHacienda($documento->Clave.'-'.trim($consecutivo));
                    }
                    $result['mensajeHacienda'] = $apiclient->getMensajeHacienda($documento->Clave.'-'.trim($consecutivo));

                    if ($result['mensajeHacienda'])
                        \DB::table('documentoshacienda')->where('id_documento', $id)->update(
                            array(
                                'xml_hacienda' => base64_decode($result['mensajeHacienda']->respuestaxml),
                                "Estatus" => $result['mensajeHacienda']->indestado,
                                "Fecha_aceptacion" => $result['mensajeHacienda']->fecha)
                        );

                } catch (Exception $e) {
                }
            }

        }else{
			if($documento->consecutivo and $documento->Clave){
			$result['mensajeHacienda'] = $apiclient->getMensajeHacienda($documento->Clave.'-'.trim($documento->consecutivo));
                if (!$result['mensajeHacienda']) {
                    $apiclient->postHacienda($paramsendinvoice);
                    $result['mensajeHacienda'] = $apiclient->getMensajeHacienda($documento->Clave.'-'.trim($documento->consecutivo));
                }
                $result['mensajeHacienda'] = $apiclient->getMensajeHacienda($documento->Clave.'-'.trim($documento->consecutivo));

                if ($result['mensajeHacienda'])
                    \DB::table('documentoshacienda')->where('id_documento', $id)->update(
                        array(
                            'xml_hacienda' => base64_decode($result['mensajeHacienda']->respuestaxml),
                            "Estatus" => $result['mensajeHacienda']->indestado,
                            "Fecha_aceptacion" => $result['mensajeHacienda']->fecha)
                );
			}
		}

    }


    public static function getConsultaComprobantes($key = null)
    {
        switch (AMBIENTE) {
            case 'prod':
                $baseUrlComprobante = 'https://api.comprobanteselectronicos.go.cr/recepcion/v1/comprobantes';

                break;
            case 'test':
                $baseUrlComprobante = 'https://api.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/comprobantes';

                break;
        }

        $url = $baseUrlComprobante . '/' . $key;
        $token = new ApiclientController;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $token->getRefreshToken(),
            ),
        ));

        $response2 = curl_exec($curl);
        return \json_decode($response2);
    }


}
