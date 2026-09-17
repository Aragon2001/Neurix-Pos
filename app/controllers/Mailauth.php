<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Autorización con Google y pruebas de conexión de las dos casillas de correo:
 * la que envía los comprobantes y la que recibe las facturas de compra.
 */
class Mailauth extends MY_Controller
{
    /** Casillas que se pueden autorizar, con la columna que guarda cada dato. */
    private $casillas = [
        'envio'     => ['refresh' => 'mail_oauth_refresh',        'email' => 'mail_oauth_email',        'auth' => 'mail_auth'],
        'recepcion' => ['refresh' => 'mail_client_oauth_refresh', 'email' => 'mail_client_oauth_email', 'auth' => 'mail_client_auth'],
    ];

    function __construct()
    {
        parent::__construct();
        if (!$this->loggedIn) {
            redirect('login');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->load->library('googlemail');
    }

    /** GET mailauth/conectar/envio|recepcion — manda el navegador a Google. */
    public function conectar($cual = 'envio')
    {
        if (!isset($this->casillas[$cual])) {
            show_404();
        }
        if (!$this->googlemail->configurado()) {
            $this->session->set_flashdata('error', lang('mail_oauth_sin_credenciales'));
            redirect('settings#tab-email');
        }

        $sugerido = $cual === 'envio' ? $this->Settings->smtp_user : $this->Settings->mail_client_user;
        redirect($this->googlemail->auth_url($cual, $sugerido));
    }

    /** GET mailauth/callback — retorno de Google con el código de autorización. */
    public function callback()
    {
        $cual   = (string) $this->input->get('state');
        $codigo = (string) $this->input->get('code');

        if ($error = $this->input->get('error')) {
            $this->session->set_flashdata('error', lang('mail_oauth_denegado') . ' ' . $error);
            redirect('settings#tab-email');
        }
        if (!isset($this->casillas[$cual]) || $codigo === '') {
            $this->session->set_flashdata('error', lang('mail_oauth_respuesta_invalida'));
            redirect('settings#tab-email');
        }

        try {
            $tokens = $this->googlemail->canjear_codigo($codigo);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('settings#tab-email');
        }

        if (empty($tokens['refresh_token'])) {
            // Sin refresh token el acceso se cae a la hora: hay que revocar el permiso
            // en la cuenta de Google y autorizar de nuevo para que lo vuelva a emitir.
            $this->session->set_flashdata('error', lang('mail_oauth_sin_refresh'));
            redirect('settings#tab-email');
        }

        $campos = $this->casillas[$cual];
        $correo = $this->googlemail->correo_de($tokens['access_token']);

        $data = [
            $campos['refresh'] => encrypt_credential($tokens['refresh_token']),
            $campos['email']   => $correo,
            $campos['auth']    => 'oauth_google',
        ];

        // Autorizar tiene que dejar la casilla lista para enviar o leer, no solo
        // "conectada": sin esto el protocolo/host se quedaban en lo que hubiera
        // en el formulario (a veces nada, porque autorizar saca de la pantalla
        // antes de guardar) y el correo seguía saliendo por mail() o el IMAP
        // deshabilitado. Gmail tiene host, puerto y cifrado fijos: no hace
        // falta preguntarlos.
        if ($cual === 'envio') {
            $data['protocol']    = 'smtp';
            $data['smtp_host']   = 'smtp.gmail.com';
            $data['smtp_port']   = 587;
            $data['smtp_crypto'] = 'tls';
            $data['smtp_user']   = $correo;
            if (empty($this->Settings->default_email)) {
                $data['default_email'] = $correo;
            }
        } else {
            $data['mail_client_enabled'] = 1;
            $data['mail_client_host']    = 'imap.gmail.com';
            $data['mail_client_port']    = 993;
            $data['mail_client_crypto']  = 'ssl';
            $data['mail_client_carpeta'] = $this->Settings->mail_client_carpeta ?: 'INBOX';
            $data['mail_client_user']    = $correo;
        }

        $this->db->update('settings', $data, ['setting_id' => 1]);
        $this->_limpiar_cache();

        $this->session->set_flashdata('message', lang('mail_oauth_conectado'));
        redirect('settings#tab-email');
    }

    /** GET mailauth/desconectar/envio|recepcion */
    public function desconectar($cual = 'envio')
    {
        if (!isset($this->casillas[$cual])) {
            show_404();
        }
        $campos = $this->casillas[$cual];
        $this->db->update('settings', [
            $campos['refresh'] => null,
            $campos['email']   => null,
            $campos['auth']    => 'password',
        ], ['setting_id' => 1]);
        $this->_limpiar_cache();

        $this->session->set_flashdata('message', lang('mail_oauth_desconectado'));
        redirect('settings#tab-email');
    }

    /**
     * POST mailauth/probar_envio
     * Abre la conexión SMTP y autentica, sin mandar ningún correo.
     */
    public function probar_envio()
    {
        $this->load->library('swiftmailer');
        $Settings = $this->site->getSettings();

        if (($Settings->protocol ?: 'mail') !== 'smtp') {
            $this->_json(['ok' => true, 'aviso' => lang('mail_prueba_no_smtp')]);
        }

        $mail = new PHPMailer(true);
        $mail->Timeout = 15;
        try {
            $this->swiftmailer->configurar_transporte($mail, $Settings);
            if (!$mail->smtpConnect()) {
                throw new Exception($mail->ErrorInfo ?: lang('mail_prueba_sin_detalle'));
            }
            $mail->smtpClose();
            $this->_json(['ok' => true, 'detalle' => sprintf(lang('mail_prueba_ok'), $Settings->smtp_host, $Settings->smtp_port)]);
        } catch (Exception $e) {
            log_message('error', '[Mailauth] prueba de envío: ' . $e->getMessage());
            $this->_json(['ok' => false, 'error' => $e->getMessage() ?: $mail->ErrorInfo]);
        }
    }

    // -----------------------------------------------------------------------
    // Privados
    // -----------------------------------------------------------------------

    private function _limpiar_cache()
    {
        $this->load->driver('cache', ['adapter' => 'file']);
        $this->cache->file->delete('app_settings');
    }

    private function _json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->_display();
        // _display() explicito: CI3 vuelca la salida al terminar el controlador, y
        // este exit no llega ahi. Sin esto la respuesta sale con cuerpo vacio.
        exit;
    }
}
