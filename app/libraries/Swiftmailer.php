<?php

defined('BASEPATH') or exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

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

        try {
            // Configuración de transporte
            if (!empty($Settings->is_gmail) && $Settings->is_gmail == '1') {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $Settings->mail_client_user ?? $Settings->smtp_user;
                $mail->Password   = $Settings->mail_client_pass ?? $Settings->smtp_pass;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
            } elseif (!empty($Settings->smtp_host)) {
                $mail->isSMTP();
                $mail->Host       = $Settings->smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $Settings->smtp_user;
                $mail->Password   = $Settings->smtp_pass;
                $mail->SMTPSecure = !empty($Settings->smtp_crypto) ? $Settings->smtp_crypto : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = (int)($Settings->smtp_port ?? 587);
            } else {
                $mail->isMail();
            }

            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);

            // Remitente
            $senderEmail = $from ?? $Settings->default_email;
            $senderName  = $from_name ?? $Settings->site_name;
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

    public function getBody()
    {
        return '';
    }
}
