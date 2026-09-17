<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * OAuth 2.0 de Google para las dos casillas de correo del sistema: la que envía
 * los comprobantes y la que recibe las facturas de compra.
 *
 * Google dejó de aceptar la contraseña normal de la cuenta en SMTP e IMAP, así
 * que sin OAuth (o sin una contraseña de aplicación) no hay forma de entrar.
 * Se usa el flujo "Authorization Code" de servidor: el administrador autoriza
 * una vez y queda un refresh_token de larga duración con el que se renueva el
 * access_token solo, sin depender de ninguna ventana abierta.
 *
 * El alcance es https://mail.google.com/ porque es el único que Google acepta
 * para autenticar SMTP e IMAP; los alcances de solo lectura de la Gmail API no
 * sirven para esos protocolos.
 */
class Googlemail
{
    const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const INFO_URL  = 'https://www.googleapis.com/oauth2/v2/userinfo';
    const SCOPE     = 'https://mail.google.com/ email';
    const TIMEOUT   = 15;

    /** Access tokens de esta petición, para no pedir uno nuevo por cada envío. */
    private static $cache = array();

    public function __get($var)
    {
        return get_instance()->$var;
    }

    /** Credenciales del cliente OAuth del proveedor (app/config/googlemail.php). */
    private function cliente($clave)
    {
        $ci = get_instance();
        $ci->config->load('googlemail', TRUE);
        return (string) $ci->config->item($clave, 'googlemail');
    }

    private function client_id()     { return $this->cliente('google_mail_client_id'); }
    private function client_secret() { return $this->cliente('google_mail_client_secret'); }

    /** ¿Están cargadas las credenciales del cliente OAuth? */
    public function configurado()
    {
        return $this->client_id() !== '' && $this->client_secret() !== '';
    }

    /**
     * Debe coincidir exacto con lo registrado en Google Cloud Console.
     * Se arma desde base_url para que funcione igual en cualquier host o puerto.
     */
    public function redirect_uri()
    {
        return site_url('mailauth/callback');
    }

    /**
     * URL a la que hay que mandar el navegador para autorizar.
     *
     * @param string $cual 'envio' | 'recepcion', vuelve en el parámetro state
     */
    public function auth_url($cual, $sugerir_correo = '')
    {
        $params = array(
            'client_id'     => $this->client_id(),
            'redirect_uri'  => $this->redirect_uri(),
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            // Sin access_type=offline + prompt=consent Google omite el refresh_token
            // cuando la cuenta ya autorizó antes, y el acceso se cae a la hora.
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $cual,
        );
        if ($sugerir_correo) {
            $params['login_hint'] = $sugerir_correo;
        }
        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /** Cambia el código del callback por access_token + refresh_token. */
    public function canjear_codigo($codigo)
    {
        return $this->_token(array(
            'code'          => $codigo,
            'client_id'     => $this->client_id(),
            'client_secret' => $this->client_secret(),
            'redirect_uri'  => $this->redirect_uri(),
            'grant_type'    => 'authorization_code',
        ));
    }

    /** Access token nuevo a partir del refresh_token guardado. */
    public function refrescar($refresh_token)
    {
        if (empty($refresh_token)) {
            throw new Exception(lang('mail_oauth_sin_token'));
        }
        if (isset(self::$cache[$refresh_token])) {
            return self::$cache[$refresh_token];
        }
        $datos = $this->_token(array(
            'refresh_token' => $refresh_token,
            'client_id'     => $this->client_id(),
            'client_secret' => $this->client_secret(),
            'grant_type'    => 'refresh_token',
        ));
        self::$cache[$refresh_token] = $datos['access_token'];
        return $datos['access_token'];
    }

    /** Correo de la cuenta que autorizó, para mostrarlo en Ajustes. */
    public function correo_de($access_token)
    {
        $r = $this->_http(self::INFO_URL, null, array('Authorization: Bearer ' . $access_token));
        $datos = json_decode($r['body'], true);
        return $datos['email'] ?? '';
    }

    /**
     * Cadena SASL XOAUTH2, el formato que esperan tanto SMTP como IMAP.
     * (RFC 6749 / documentación de Google para XOAUTH2.)
     */
    public function xoauth2($usuario, $access_token)
    {
        return base64_encode("user={$usuario}\1auth=Bearer {$access_token}\1\1");
    }

    // -----------------------------------------------------------------------
    // Privados
    // -----------------------------------------------------------------------

    private function _token($params)
    {
        $r = $this->_http(self::TOKEN_URL, $params);
        $datos = json_decode($r['body'], true);

        if ($r['code'] !== 200 || empty($datos['access_token'])) {
            $detalle = $datos['error_description'] ?? ($datos['error'] ?? 'HTTP ' . $r['code']);
            log_message('error', '[Googlemail] ' . $detalle);
            throw new Exception(lang('mail_oauth_error') . ' ' . $detalle);
        }
        return $datos;
    }

    private function _http($url, $post = null, $headers = array())
    {
        $ch = curl_init($url);
        $opts = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
        );
        if ($post !== null) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        if ($headers) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        curl_setopt_array($ch, $opts);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception(lang('mail_oauth_sin_conexion') . ' ' . $err);
        }
        return array('code' => $code, 'body' => $body);
    }
}
