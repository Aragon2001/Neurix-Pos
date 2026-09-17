<?php

defined('BASEPATH') or exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\OAuthTokenProvider;

/**
 * Proveedor XOAUTH2 para PHPMailer. Google ya no acepta la contraseña normal de
 * la cuenta en SMTP: o contraseña de aplicación, o este token.
 */
class NeurixGoogleToken implements OAuthTokenProvider
{
    private $googlemail;
    private $usuario;
    private $refresh;

    public function __construct($googlemail, $usuario, $refresh)
    {
        $this->googlemail = $googlemail;
        $this->usuario    = $usuario;
        $this->refresh    = $refresh;
    }

    public function getOauth64()
    {
        return $this->googlemail->xoauth2($this->usuario, $this->googlemail->refrescar($this->refresh));
    }
}

/**
 * Swiftmailer — wrapper de compatibilidad sobre PHPMailer.
 * Mantiene la misma firma de send_email() que usaba SwiftMailer
 * para que todos los callers (Queue_worker, PosEmail, etc.) funcionen sin cambios.
 * SwiftMailer está deprecado desde 2023; este reemplazo usa phpmailer/phpmailer.
 */
class Swiftmailer
{
    public function __get($var)
    {
        return get_instance()->$var;
    }

    /**
     * @param string      $to
     * @param string      $subject
     * @param string      $body        HTML body
     * @param string|null $from
     * @param string|null $from_name
     * @param array|null  $attachment  ['ruta' => '/path/to/file'] | ['name.xml' => $xmlContent]
     * @param string|null $cc
     * @param string|null $bcc
     */
    public function send_email($to, $subject, $body, $from = null, $from_name = null, $attachment = null, $cc = null, $bcc = null)
    {
        $Settings = $this->site->getSettings();

        $mail = new PHPMailer(true);
        $mail->Timeout = 20;

        try {
            $this->configurar_transporte($mail, $Settings);

            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);

            // Remitente
            $senderEmail = $from ?: remitente_correo($Settings);
            $senderName  = $from_name ?? $Settings->site_name;
            if (!$senderEmail) {
                log_message('error', '[Correo] no hay remitente configurado: revise Ajustes -> Correo.');
                return false;
            }
            $mail->setFrom($senderEmail, $senderName);
            $mail->addReplyTo($senderEmail, $senderName);

            // Destinatarios
            $mail->addAddress($to);
            if ($cc)  $mail->addCC($cc);
            if ($bcc) $mail->addBCC($bcc);

            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            // Adjuntos
            if (!empty($attachment) && is_array($attachment)) {
                foreach ($attachment as $key => $value) {
                    if ($key === 'ruta') {
                        if (file_exists($value)) {
                            $mail->addAttachment($value);
                        }
                    } else {
                        // Contenido en memoria (XMLs firmados)
                        $mail->addStringAttachment((string)$value, $key . '.xml', PHPMailer::ENCODING_BASE64, 'application/xml');
                    }
                }
            }

            $mail->send();
            return true;

        } catch (PHPMailerException $e) {
            log_message('error', '[Swiftmailer→PHPMailer] ' . $mail->ErrorInfo);
            return false;
        } catch (\Exception $e) {
            log_message('error', '[Swiftmailer→PHPMailer] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deja el transporte listo segun lo configurado en Ajustes.
     * Se separo del envio para que la pantalla de Ajustes pueda probar la
     * conexion sin mandar ningun correo.
     */
    public function configurar_transporte($mail, $Settings)
    {
        $protocolo = $Settings->protocol ?: 'mail';

        if ($protocolo === 'sendmail') {
            $mail->isSendmail();
            if (!empty($Settings->mailpath)) {
                $mail->Sendmail = $Settings->mailpath;
            }
            return;
        }

        if ($protocolo !== 'smtp' || empty($Settings->smtp_host)) {
            $mail->isMail();
            return;
        }

        $mail->isSMTP();
        $mail->Host       = $Settings->smtp_host;
        $mail->Port       = (int) ($Settings->smtp_port ?: 587);
        $mail->SMTPSecure = $Settings->smtp_crypto ?: PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth   = true;

        if (($Settings->mail_auth ?? 'password') === 'oauth_google') {
            $CI = get_instance();
            $CI->load->library('googlemail');
            $usuario = $Settings->mail_oauth_email ?: $Settings->smtp_user;
            $mail->Username = $usuario;
            $mail->AuthType = 'XOAUTH2';
            $mail->setOAuth(new NeurixGoogleToken(
                $CI->googlemail,
                $usuario,
                decrypt_credential($Settings->mail_oauth_refresh ?? '')
            ));
            return;
        }

        $mail->Username = $Settings->smtp_user;
        $mail->Password = decrypt_credential($Settings->smtp_pass ?? '');
    }

    public function getBody()
    {
        return '';
    }
}
