<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Apiclient {

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

    public function __construct() {
        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();

        $this->load->library('HttpClient');

        $this->ambiente = $this->Settings->ambiente;

        switch ($this->ambiente) {
            case 'prod':
                $this->baseUrlComprobante = 'https://api.hacienda.go.cr/fe/ae';
                $this->baseUrlRecepcion   = 'https://api.hacienda.go.cr/fe/ae';
                $this->authUrl            = 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token';
                $this->CloseauthUrl       = 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/logout';
                $this->clientId           = 'api-prod';
                $this->username           = $this->Settings->user_token_prod;
                $this->password           = $this->Settings->password_token_prod;
                break;
            case 'test':
                $this->baseUrlComprobante = 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/comprobantes';
                $this->baseUrlRecepcion   = 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/recepcion';
                $this->authUrl            = 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token';
                $this->CloseauthUrl       = 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/logout';
                $this->clientId           = 'api-stag';
                $this->username           = $this->Settings->user_token_test;
                $this->password           = $this->Settings->password_token_test;
                break;
        }

        $this->accessToken = NULL;
        $this->refreshToken = NULL;
    }

    public function __get($var) {
        return get_instance()->$var;
    }

    private function queryData($d) {
        if (is_array($d)) {
            $data = http_build_query($d);
        } else {
            $data = $d;
        }
        return $data;
    }

    public function getTokenH() {
        $client_secret = '';
        $scope = '';
        $grant_type = 'password';
        $body = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'username' => trim($this->username),
            'password' => trim($this->password),
            'client_secret' => trim($client_secret),
            'scope' => $scope,
            'grant_type' => $grant_type,
            'authorization_grants' => $grant_type,
        ];


        $post_data = $this->queryData($body);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->authUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLINFO_HEADER_OUT => true,
            CURLOPT_POST => $post_data,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false
        ));
        try {
            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            if (!empty($result->access_token) && !empty($result->refresh_token)) {
                $this->setAccessToken($result->access_token);
                $this->setRefreshToken($result->refresh_token);
                return $result;
            } else if (isset($result->error_description)) {
                echo "Error: Usuario: {$username} / Password: {$password} del token de hacienda invalidos compruebe nuevamente.";
                exit();
            } else {
                echo "Error: Al intentar de obtener el token";
                exit();
            }
        } catch (\Exception $e) {
            var_dump($e);
            exit();
        }

     
    }

    public function refreshTokenH() {
        $client_secret = '';
        $scope = '';
        $grant_type = 'refresh_token';
        $body = [
            'client_id' => $this->clientId,
            'grant_type' => $grant_type,
            'refresh_token' => $this->refreshToken,
        ];

        $this->httpclient->setOptions(
                array(
                    'data' => $body,
                    'url' => $this->authUrl,
        ));

        try {
            if ($this->httpclient->post()) {
                $result = json_decode($this->httpclient->getResults());
                if (!empty($result->access_token) && !empty($result->refresh_token)) {
                    $this->setAccessToken($result->access_token);
                    $this->setRefreshToken($result->refresh_token);
                    return $result;
                }
            } else {
                echo $this->httpclient->getErrorMsg();
            }
        } catch (\Exception $e) {
            var_dump($e);
            exit();
        }
    }

    public function CloseTokenH() {
        $body = [
            'client_id' => $this->clientId,
            'refresh_token' => $this->refreshToken,
        ];

        $this->httpclient->setOptions(
                array(
                    'curl_header' => true,
                    'data' => $body,
                    'url' => $this->CloseauthUrl,
        ));

        try {
            if ($this->httpclient->post()) {
                $result = json_decode($this->httpclient->getResults());
                return $result;
            } else {
                echo $this->httpclient->getErrorMsg();
            }

            if (isset($result->error_description)) {
                echo "Error: Usuario: {$username} / Password: {$password} del token de hacienda invalidos compruebe nuevamente.";
                exit();
            } else if ($result->access_token) {
                return $result;
            }
        } catch (\Exception $e) {
            echo "Error: Usuario y/o Contraseña invalida compruebe.";
            exit();
        }
    }

    //send invoice xml to Hacienda API
    public function send_invoice($parametros) {

        if ($this->accessToken) {

            if ($parametros['xml_sign']) {
                $xmlFirmado = base64_encode($parametros['xml_sign']);
            } else {
                $xmlFirmado = $this->Firmarhacienda($parametros['xml']);
            }
if ( base64_encode(base64_decode($xmlFirmado, true)) === $xmlFirmado){
            $data = [
                'clave' => $parametros['clave'],
                'fecha' => $parametros['fecha_emision'],
                'emisor' => [
                    'tipoIdentificacion' => $this->Settings->tipo_doc_emisor,
                    'numeroIdentificacion' => trim(str_replace("-", "", $this->Settings->cedula_emisor))
                ],
                'comprobanteXml' => $xmlFirmado,
            ];
            $resultado['comprobante'] = $this->getComprobantes($parametros['clave']);
            if (!$resultado['comprobante']) {
                $this->postHacienda($data);
                $resultado['comprobante'] = $this->getComprobantes($parametros['clave']);
            }
            $resultado['mensajeHacienda'] = $this->getMensajeHacienda($parametros['clave']);
            $resultado['xml_firmado'] = $xmlFirmado;
            return $resultado;
}
return "documento sin firma";
        } else {
            echo "No hemos podido obtener el token por favor revise el usuario y el password del token de hacienda";
        }
    }

    public function getComprobantes($key = null) {
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
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->getAccessToken(),
            ),
        ));
        try {
            $response2 = curl_exec($curl);
            curl_close($curl);
        } catch (\Exception $e) {
            return false;
        }
        return \json_decode($response2);
    }

    public function postHacienda($data): bool
    {
        return $this->sendWithRetry(function () use ($data): bool {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $this->baseUrlRecepcion,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLINFO_HEADER_OUT    => true,
                CURLOPT_POSTFIELDS     => \json_encode($data),
                CURLOPT_HEADER         => false,
                CURLOPT_HTTPHEADER     => [
                    'authorization: Bearer ' . $this->getAccessToken(),
                    'content-type: application/json charset=utf-8',
                ],
            ]);
            curl_exec($curl);
            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_errno($curl);
            curl_close($curl);

            // 202 Accepted = éxito; 4xx = error del cliente, no reintentar
            if ($curlError !== 0 || $httpCode >= 500) {
                return false;
            }
            return true;
        });
    }

    private function sendWithRetry(callable $fn, int $maxAttempts = 3): bool
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                if ($fn() !== false) {
                    return true;
                }
            } catch (\Exception $e) {
                log_message('error', "[Apiclient] intento $attempt/$maxAttempts: " . $e->getMessage());
            }
            if ($attempt < $maxAttempts) {
                sleep((int) pow(2, $attempt)); // 2s, 4s
            }
        }
        log_message('error', '[Apiclient] postHacienda falló tras ' . $maxAttempts . ' intentos');
        return false;
    }

    public function getMensajeHacienda($key) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrlRecepcion . '/' . $key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->getAccessToken(),
            ),
        ));

        try {
            $response2 = curl_exec($curl);
            curl_close($curl);
            $response2 = str_replace('respuesta-xml', 'respuestaxml', $response2);
            $response2 = str_replace('ind-estado', 'indestado', $response2);
        } catch (\Exception $e) {
            return false;
        }
        return \json_decode($response2);
    }

    public function json_decode_nice($json, $assoc = FALSE) {
        $json = str_replace(array("\n", "\r"), "", $json);
        $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $json);
        $json = preg_replace('/(,)\s*}$/', '}', $json);
        return json_decode($json, $assoc);
    }

    public function get_string_between($string, $start, $end) {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0)
            return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public function LimpiaString($string) {
        $string = str_replace(array("\n", "\r"), "", $string);
        $string = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $string);
        $string = preg_replace('/(,)\s*}$/', '}', $string);
        return $string;
    }

    public function getMsgXMLHacienda($xml) {
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

    public function Firmarhacienda($xml) {
        $body = ['username' => $this->Settings->usuario_lic,
            'password' => $this->Settings->num_lic,
            'grant_type' => 'password'];

        $this->httpclient->setOptions(
                array(
                    'headers' => array(
                        'Content-Type: application/x-www-form-urlencoded',
                    ),
                    'data' => $body,
                    'url' => $this->Settings->server_lic . "/Token",
        ));

        if ($this->httpclient->post()) {
            $tokenSign = json_decode($this->httpclient->getResults());
        } else {
            echo $this->httpclient->getErrorMsg();
        }
        if (isset($tokenSign->access_token)) {

            $bodyfactura = ['documento' => base64_encode($xml),
                'cedula' => $this->Settings->certificado_ced,
                'clave' => $this->Settings->certificado_pin,
                'ambiente' => $this->Settings->ambiente];
            $this->httpclient->setOptions(
                    array(
                        'headers' => array(
                            'Content-Type: application/x-www-form-urlencoded',
                            'Authorization: bearer ' . $tokenSign->access_token
                        ),
                        'data' => $bodyfactura,
                        'url' => $this->Settings->server_lic . "/api/v1/signxml",
            ));

            if ($this->httpclient->post()) {
                $respuesta = json_decode($this->httpclient->getResults());
            } else {
                echo $this->httpclient->getErrorMsg();
            }
			
            return $respuesta->Data;
        }
    }

    public function MensajeAprobacion($documento) {
        if (!$documento->xml_hacienda) {

            $xml = $documento->xml_mensajereceptor;
            $consecutivo = $documento->consecutivo;

            if ($documento->xml_firmado) {
                $xmlFirmado = base64_encode($documento->xml_firmado);
            } else {
                $xmlFirmado = $this->Firmarhacienda($documento->xml_mensajereceptor);
                $this->hacienda_model->setRespuestaxmlfirmado($xmlFirmado, $documento->id_documento);
            }
            $parametros = array($xml, trim($documento->ClaveDocEmisor), trim($consecutivo), $xmlFirmado);

            if ($xmlFirmado) {
                try {
                    $fecha = date('Y-m-d\TH:i:s');
                    $paramsendinvoice = [
                        'clave' => $documento->ClaveDocEmisor,
                        'fecha' => $fecha,
                        'emisor' => [
                            'tipoIdentificacion' => $documento->tipo_doc_emisor,
                            'numeroIdentificacion' => trim(str_replace("-", "", $documento->NumeroCedulaEmisor))
                        ],
                        'receptor' => [
                            'tipoIdentificacion' => $this->Settings->tipo_doc_emisor,
                            'numeroIdentificacion' => trim(str_replace("-", "", $this->Settings->cedula_emisor))
                        ],
                        "consecutivoReceptor" => trim($consecutivo),
                        'comprobanteXml' => $xmlFirmado
                    ];

                    $result['mensajeHacienda'] = $this->getMensajeHacienda($documento->ClaveDocEmisor . '-' . trim($consecutivo));
                    if (!$result['mensajeHacienda']) {
                        $this->postHacienda($paramsendinvoice);
                        $result['mensajeHacienda'] = $this->getMensajeHacienda($documento->ClaveDocEmisor . '-' . trim($consecutivo));
                    }
                    $result['mensajeHacienda'] = $this->getMensajeHacienda($documento->ClaveDocEmisor . '-' . trim($consecutivo));
                    if ($result['mensajeHacienda']) {
                        // dd($result['mensajeHacienda']);
                        $this->hacienda_model->setRespuestaMensaje([
                            'xml_hacienda' => base64_decode($result['mensajeHacienda']->respuestaxml),
                            'Estatus' => $result['mensajeHacienda']->indestado,
                            'Fecha_aceptacion' => $result['mensajeHacienda']->fecha
                                ], $documento->id_documento);
                    }
                } catch (Exception $e) {
                    
                }
            }
        }
    }

    public static function getConsultaComprobantes($key = null) {
        switch (AMBIENTE) {
            case 'prod':
                $baseUrlComprobante = 'https://api.hacienda.go.cr/fe/ae';
                break;
            case 'test':
                $baseUrlComprobante = 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/comprobantes';
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
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $token->getRefreshToken(),
            ),
        ));

        $response2 = curl_exec($curl);
        curl_close($curl);
        return \json_decode($response2);
    }

    public function setAccessToken($access_token) {
        $this->accessToken = $access_token;
    }

    public function getAccessToken() {
        return $this->accessToken;
    }

    public function setRefreshToken($refresh_token) {
        $this->refreshToken = $refresh_token;
    }

    public function getRefreshToken() {
        return $this->refreshToken;
    }

}
